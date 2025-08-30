<?php
/*
  ASD Math Operations Module
  Handles ADD, SUB, MULT, DIV
  Fully modular and line-by-line
  License: MIT
*/

/**
 * Process arithmetic operations on variables or literals
 *
 * Supported operations:
 *   ADD a b    -> addition
 *   SUB a b    -> subtraction
 *   MULT a b   -> multiplication
 *   DIV a b    -> integer division
 *
 * @param string $line Current line from ASD script
 * @param array &$variables Current variable state
 */
function arithmetic_line($line, &$variables) {
    // Check if line starts with a math operation
    if (!preg_match('/^(ADD|SUB|MULT|DIV)\s+/i', $line)) return;

    $parts = explode(' ', $line, 3);
    $op = strtoupper($parts[0]);
    $a = $variables[$parts[1]] ?? $parts[1] ?? 0;
    $b = $variables[$parts[2]] ?? $parts[2] ?? 0;

    // Convert numeric strings to numbers
    $a = is_numeric($a) ? $a + 0 : 0;
    $b = is_numeric($b) ? $b + 0 : 0;

    $res = 0;
    switch ($op) {
        case 'ADD': $res = $a + $b; break;
        case 'SUB': $res = $a - $b; break;
        case 'MULT': $res = $a * $b; break;
        case 'DIV':
            if ($b == 0) {
                echo "Error: divide by zero\n";
                return;
            }
            $res = intdiv($a, $b);
            break;
    }

    // Optionally, store result back in the first operand if it’s a variable
    if (isset($variables[$parts[1]])) $variables[$parts[1]] = $res;

    echo $res . "\n";
}
