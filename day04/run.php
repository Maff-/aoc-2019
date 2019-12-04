<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

[$min, $max] = array_map('intval', explode('-', trim(file_get_contents($inFile))));

var_dump(compact('min', 'max'));

$valid = [];

for ($current = $min; $current <= $max; $current++) {
    $digits = array_map('intval', str_split((string)$current));
    $streak = [];
    foreach ($digits as $i => $digit) {
        if (($prev = $digits[$i - 1] ?? null) === null) {
            continue;
        }
        if ($digit < $prev) {
            continue 2;
        }
        if ($digit === $prev) {
            $streak[$digit] = isset($streak[$digit]) ? $streak[$digit] + 1 : 2;
        }
    }
    if ($streak && min($streak) === 2) {
        $valid[] = $current;
    }
}

echo 'Result part2: ', count($valid), PHP_EOL;
