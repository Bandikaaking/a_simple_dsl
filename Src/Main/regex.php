<?php
/*
  ASD Regex Module - Extended
  Supports MATCH:LINE with readable tokens:
    %char      -> any single character
    %digit     -> [0-9]
    %word      -> [a-zA-Z0-9_]+
    %line      -> entire line (.*)
    %id        -> variable/function identifier [a-zA-Z_][a-zA-Z0-9_]*
    %op        -> operators like + - * / = etc.
    %string    -> quoted strings ".*?" or '.*?'
    %number    -> integer or decimal \d+(\.\d+)?
    %space     -> whitespace \s+
    %alpha     -> [a-zA-Z]+
    %alnum     -> [a-zA-Z0-9]+
    %hex       -> [0-9A-Fa-f]+
*/

/**
 * READFILE(filename)
 * Loads file into __FILE__ variable
 */
function regex_line($line, &$variables) {
    if (!preg_match('/^READFILE\((.+)\)$/i', $line, $m)) return;
    $filename = trim($m[1]);
    $variables['__FILE__'] = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES) : [];
}

/**
 * IF __FILE__ LINE:MATCH(regex) THEN ...
 */
function process_match_line($line, &$variables) {
    if (!preg_match('/^IF __FILE__ LINE:MATCH\((.+)\)(?: THEN (.+))?/i', $line, $m)) return;
    $pattern = readable_to_php_regex(trim($m[1]));
    $cmd = $m[2] ?? '';
    $lines_arr = $variables['__FILE__'] ?? [];

    foreach ($lines_arr as $l) {
        if (preg_match('/' . $pattern . '/', $l, $matches)) {
            $out = $l;

            // REMOVE <text> AND ...
            if (preg_match('/REMOVE\s+(.+?)(?:\s+AND)?/i', $cmd, $rm)) {
                $to_remove = trim($rm[1]);
                $out = str_ireplace($to_remove, '', $out);
            }

            // PRINT <text>
            if (preg_match('/PRINT\s*(.+)?/i', $cmd, $pr)) {
                $text = trim($pr[1] ?? '');
                if ($text) {
                    // replace placeholders %1, %2 etc.
                    $text_out = $text;
                    if (preg_match_all('/%(\w+)/', $text, $ph_matches)) {
                        foreach ($ph_matches[1] as $i => $ph) {
                            $capture = $matches[$i + 1] ?? '';
                            $text_out = str_replace("%$ph", $capture, $text_out);
                        }
                    }
                    echo $text_out . "\n";
                } else {
                    echo $out . "\n";
                }
            }

            // SETVAR <var> <value>
            if (preg_match('/SETVAR\s+(\w+)\s+(.+)/i', $cmd, $sv)) {
                $variables[$sv[1]] = str_replace('%line', $out, $sv[2]);
            }
        }
    }
}

/**
 * Converts readable tokens to PHP regex
 */
function readable_to_php_regex($pattern) {
    $map = [
        '%char'   => '.',
        '%digit'  => '\d',
        '%word'   => '\w+',
        '%line'   => '.*',
        '%id'     => '[a-zA-Z_][a-zA-Z0-9_]*',
        '%op'     => '[+\-*\/=<>!]+',
        '%string' => '"[^"]*"|\'[^\']*\'',
        '%number' => '\d+(\.\d+)?',
        '%space'  => '\s+',
        '%alpha'  => '[a-zA-Z]+',
        '%alnum'  => '[a-zA-Z0-9]+',
        '%hex'    => '[0-9A-Fa-f]+',
    ];
    return str_replace(array_keys($map), array_values($map), $pattern);
}
