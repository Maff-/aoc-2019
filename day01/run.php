<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

$in = array_map('trim', file($inFile));

$mass = array_map('intval', $in);
$fuel = array_map(fn($m) => (int)floor($m / 3) - 2, $mass);
$fuelTotal = array_sum($fuel);

var_dump(compact('fuelTotal'));
