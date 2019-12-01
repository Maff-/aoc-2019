<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

$in = array_map('trim', file($inFile));

$mass = array_map('intval', $in);

function mass2fuel(int $mass) {
    $fuel = max((int)floor($mass / 3) - 2, 0);
    return $fuel > 0 ? $fuel + mass2fuel($fuel) : $fuel;
}

$fuel = array_map('mass2fuel', $mass);
$fuelTotal = array_sum($fuel);

var_dump(compact('fuelTotal'));
