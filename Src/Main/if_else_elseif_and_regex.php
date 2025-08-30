<?php
/*
  ASD IF / ELSE / ELSEIF Module
  Checks conditions line by line and executes the associated command
  Includes regex token functionality from regex.php
  Modular, no lexer required
*/

/**
 * Process IF / ELSEIF / ELSE statements
 *
 * @param string $line Current line from ASD script
 * @param array &$variables Current variable state
 * @param array &$lines All lines in the ASD script
 * @param int &$i Current line index (passed by reference)
 */
function if_else_elseif($line, &$variables, &$lines, &$i) {
    $line_upper = strtoupper($line);

    // IF ... THEN ...
    if (preg_match('/^IF (.+) THEN (.+)$/i', $line, $m)) {
        $cond = trim($m[1]);   // condition to evaluate
        $cmd  = trim($m[2]);   // command to execute if true

        // Handle special LINE:MATCH syntax
        if (preg_match('/^(\w+)\s+LINE:MATCH\((.+)\)$/i', $cond, $match_parts)) {
            $array_var = trim($match_parts[1]);
            $pattern = trim($match_parts[2]);
            
            if (isset($variables[$array_var]) && is_array($variables[$array_var])) {
                $found = false;
                foreach ($variables[$array_var] as $line_content) {
                    // Convert readable tokens to PHP regex
                    $regex_pattern = readable_to_php_regex($pattern);
                    
                    // Always use case-insensitive matching for text patterns
                    if (preg_match('/' . $regex_pattern . '/i', $line_content, $line_matches)) {
                        $found = true;
                        // Store the matched line parts in variables
                        for ($j = 1; $j < count($line_matches); $j++) {
                            $variables['line' . $j] = trim($line_matches[$j]);
                        }
                        // Also store in 'line' variable for convenience (for %line)
                        if (isset($variables['line1'])) {
                            $variables['line'] = $variables['line1'];
                        }
                        break;
                    }
                }
                
                if ($found) {
                    execute_line($cmd, $variables);
                    skip_else_blocks($lines, $i);
                }
                return;
            } else {
                // Array variable not found or not an array
                asd_error(ASD_ERROR_VARIABLE, 'variable_undefined', $i+1, "Array variable '$array_var' not found or not an array");
                return;
            }
        }

        // Handle regular conditions with eval
        try {
            if (eval_condition($cond, $variables)) {
                execute_line($cmd, $variables);
                skip_else_blocks($lines, $i);
            }
        } catch (Throwable $e) {
            asd_error(ASD_ERROR_SYNTAX, 'syntax_invalid', $i+1, "Invalid condition: $cond");
        }
        return;
    }

    // ELSEIF ... THEN ... (only after IF)
    if (preg_match('/^ELSEIF (.+) THEN (.+)$/i', $line, $m)) {
        $cond = trim($m[1]);
        $cmd  = trim($m[2]);
        try {
            if (eval_condition($cond, $variables)) {
                execute_line($cmd, $variables);
                skip_else_blocks($lines, $i);
            }
        } catch (Throwable $e) {
            asd_error(ASD_ERROR_SYNTAX, 'syntax_invalid', $i+1, "Invalid condition: $cond");
        }
        return;
    }

    // ELSE ... (only after IF)
    if (preg_match('/^ELSE (.+)$/i', $line, $m)) {
        $cmd = trim($m[1]);
        execute_line($cmd, $variables);
        skip_else_blocks($lines, $i);
    }
}

/**
 * Skip subsequent ELSEIF / ELSE lines after an IF branch was executed
 *
 * @param array &$lines All lines in the ASD script
 * @param int &$i Current line index
 */
function skip_else_blocks(&$lines, &$i) {
    $count = count($lines);
    while ($i + 1 < $count) {
        $next_line = trim($lines[$i + 1]);
        if (preg_match('/^(ELSEIF|ELSE)/i', $next_line)) {
            $i++; // skip this line
        } else {
            break; // next line is not ELSEIF/ELSE
        }
    }
}

/**
 * Evaluate a condition expression
 *
 * @param string $cond Condition expression
 * @param array $variables Current variables
 * @return bool Evaluation result
 */
function eval_condition($cond, $variables) {
    // Replace variables with their string values
    foreach ($variables as $key => $val) {
        if (is_array($val)) {
            // Skip array variables to avoid array to string conversion
            continue;
        }
        $cond = str_replace($key, '"' . addslashes($val) . '"', $cond);
    }
    
    // Handle special cases like array variables
    $cond = preg_replace_callback('/(\w+)\s+LINE:MATCH\(/', function($matches) use ($variables) {
        $var = $matches[1];
        if (isset($variables[$var]) && is_array($variables[$var])) {
            return 'false'; // Already handled separately, return false to avoid eval issues
        }
        return 'false';
    }, $cond);
    
    return eval('return (' . $cond . ');');
}

/**
 * Converts readable tokens to PHP regex
 * This replaces the functionality from regex.php
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
    
    // First escape any regex special characters that aren't part of our tokens
    $pattern = preg_quote($pattern, '/');
    
    // Then replace our tokens with their regex equivalents
    foreach ($map as $token => $regex) {
        $pattern = str_replace(preg_quote($token, '/'), $regex, $pattern);
    }
    
    return $pattern;
}