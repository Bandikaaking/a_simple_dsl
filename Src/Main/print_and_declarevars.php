<?php
/*
  ASD PRINT & SETVAR Module
  Handles variable declaration and printing
  Fully modular and line-by-line, no lexer
  Compatible with ASD main engine
  License: MIT
*/

// Only define if not already defined to avoid redeclaration errors
if (!function_exists('replace_vars')) {
    /**
     * Replace variables in a text
     *
     * @param string $text Text with =(var) placeholders
     * @param array $variables Current variables
     * @return string Text with variables replaced
     */
    function replace_vars($text, $variables) {
        return preg_replace_callback('/=\((\w+)\)/', function($matches) use ($variables) {
            return $variables[$matches[1]] ?? '';
        }, $text);
    }
}

/**
 * Process PRINT and SETVAR statements
 *
 * @param string $line Current line from ASD script
 * @param array &$variables Current variable state
 */
function print_and_declarevars($line, &$variables) {
    // SETVAR var value - case insensitive
    if (preg_match('/^SETVAR\s+(\w+)\s+(.+)$/i', $line, $m)) {
        $var = $m[1] ?? '';
        $value = $m[2] ?? '';

        // If value is READLINE(), read input from user
        if (strtoupper($value) === 'READLINE()') {
            $variables[$var] = trim(fgets(STDIN));
        } 
        // If value is RANDOM(min,max), generate random number
        else if (preg_match('/^RANDOM\((\d+),(\d+)\)$/i', $value, $matches)) {
            $min = intval($matches[1]);
            $max = intval($matches[2]);
            $variables[$var] = rand($min, $max);
        }
        else {
            // Replace variables inside value using the main replace_vars
            $variables[$var] = replace_vars($value, $variables);
        }
        return;
    }

    // PRINT statement - ONLY ACCEPT UPPERCASE PRINT
    if (preg_match('/^PRINT\s+(.+)$/', $line, $m)) {
        $text = $m[1];
        // Replace variables in the text
        $output = replace_vars($text, $variables);
        echo $output . "\n";
        return;
    }
    
    // Reject any lowercase or mixed case print commands
    if (preg_match('/^[a-z]rint\s+/i', $line) && !preg_match('/^PRINT\s+/', $line)) {
        // This is a print command but not uppercase PRINT, so ignore it
        return;
    }
}