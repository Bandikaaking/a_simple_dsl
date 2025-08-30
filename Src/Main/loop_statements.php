<?php
/*
  ASD Loop Module
  Handles LOOP N (...) and WHILE (...) blocks
  Fully modular and line-by-line
  License: MIT
*/

/**
 * Read a block enclosed in parentheses
 *
 * @param array &$lines All lines
 * @param int &$i Current line index (passed by reference)
 * @return array Lines inside the block
 */
function read_block(&$lines, &$i) {
    $block = [];
    $i++;
    for (; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if ($line === ')') break;
        $block[] = $line;
    }
    return $block;
}

/**
 * Process LOOP N (...) block
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @param array &$lines All lines
 * @param int &$i Current line index
 */
function loop_line($line, &$variables, &$lines, &$i) {
    if (!preg_match('/^LOOP\s+(\d+)\s*\($/i', $line, $m)) return false;
    $count = intval($m[1]);
    $block = read_block($lines, $i);
    for ($j = 0; $j < $count; $j++) {
        process_block($block, $variables);
    }
    return true;
}

/**
 * Process WHILE (...) block
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @param array &$lines All lines
 * @param int &$i Current line index
 */
function while_line($line, &$variables, &$lines, &$i) {
    if (!preg_match('/^WHILE\s+(.+)\s*\($/i', $line, $m)) return false;
    $cond = $m[1];
    $block = read_block($lines, $i);
    while (eval_condition($cond, $variables)) {
        process_block($block, $variables);
    }
    return true;
}
