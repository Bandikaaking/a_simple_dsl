<?php
/*
  ASD Other Statements Module
  Handles miscellaneous statements like SLEEP, RANDOM, LEN, UPPER, LOWER, REPLACE
*/

function sleep_line($line) {
    if (preg_match('/^SLEEP\s+(\d+)$/i', $line, $matches)) {
        sleep(intval($matches[1]));
        return true;
    }
    return false;
}

function random_line($line, &$variables) {
    // Handle RANDOM min max (standalone command)
    if (preg_match('/^RANDOM\s+(\d+)\s+(\d+)$/i', $line, $matches)) {
        $result = rand(intval($matches[1]), intval($matches[2]));
        echo $result . "\n";
        return true;
    }
    
    // Handle RANDOM(min,max) for use in SETVAR
    if (preg_match('/^RANDOM\((\d+),(\d+)\)$/i', $line, $matches)) {
        $result = rand(intval($matches[1]), intval($matches[2]));
        echo $result . "\n";
        return true;
    }
    
    return false;
}

function len_line($line, &$variables) {
    if (preg_match('/^LEN\((\w+)\)$/i', $line, $matches)) {
        $var = $matches[1];
        if (isset($variables[$var])) {
            echo strlen($variables[$var]) . "\n";
        } else {
            echo "0\n";
        }
        return true;
    }
    return false;
}

function upper_line($line, &$variables) {
    if (preg_match('/^UPPER\s+PRINT\s+(.+)$/i', $line, $matches)) {
        $text = $matches[1];
        // Replace variables if they exist
        $text = replace_vars($text, $variables);
        echo strtoupper($text) . "\n";
        return true;
    }
    return false;
}

function lower_line($line, &$variables) {
    if (preg_match('/^LOWER\s+PRINT\s+(.+)$/i', $line, $matches)) {
        $text = $matches[1];
        // Replace variables if they exist
        $text = replace_vars($text, $variables);
        echo strtolower($text) . "\n";
        return true;
    }
    return false;
}

function replace_line($line, &$variables) {
    if (preg_match('/^REPLACE\s+(\w+)\s+"([^"]+)"\s+"([^"]+)"$/i', $line, $matches)) {
        $var = $matches[1];
        $search = $matches[2];
        $replace = $matches[3];
        
        if (isset($variables[$var])) {
            $variables[$var] = str_replace($search, $replace, $variables[$var]);
        }
        return true;
    }
    return false;
}

// Helper function to replace variables in text (if not already defined elsewhere)
if (!function_exists('replace_vars')) {
    function replace_vars($text, $variables) {
        return preg_replace_callback('/=\((\w+)\)/', function($matches) use ($variables) {
            return $variables[$matches[1]] ?? '';
        }, $text);
    }
}

function readfile_line($line, &$variables) {
    if (preg_match('/^READFILE\((.+)\)$/i', $line, $m)) {
        $filename = trim($m[1]);
        if (file_exists($filename)) {
            $variables['__FILE__'] = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return true;
        } else {
            asd_error(ASD_ERROR_FILE, 'file_not_found', 0, "File '$filename' not found");
            $variables['__FILE__'] = [];
            return true;
        }
    }
    return false;
}