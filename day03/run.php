<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

define('X', 0);
define('Y', 1);

function findIntersections(array $path1, array $path2): array
{
    $path1Sections = pathSections($path1);
    $path2Sections = pathSections($path2);
    $intersections = [];
    $dist1Tot = 0;
    foreach ($path1Sections as [$from1, $to1, $dir1, $dist1]) {
        $d1 = $from1[X] === $to1[X] ? 'v' : 'h';
        $dist2Tot = 0;
        foreach ($path2Sections as [$from2, $to2, $dir2, $dist2]) {
            $d2 = $from2[X] === $to2[X] ? 'v' : 'h';
            if ($d1 === 'v' && $d2 === 'h'
                && min($from2[X], $to2[X]) < $from1[X] && $from1[X] < max($from2[X], $to2[X])
                && min($from1[Y], $to1[Y]) < $from2[Y] && $from2[Y] < max($from1[Y], $to1[Y])
            ) {
                $dist = $dist1Tot + $dist2Tot + abs($from2[Y] - $from1[Y]) + abs($from2[X] - $from1[X]);
                $intersections[] = [$from1[X], $from2[Y], $dist];
            } elseif ($d1 === 'h' && $d2 === 'v'
                && min($from2[Y], $to2[Y]) < $from1[Y] && $from1[Y] < max($from2[Y], $to2[Y])
                && min($from1[X], $to1[X]) < $from2[X] && $from2[X] < max($from1[X], $to1[X])
            ) {
                $dist = $dist1Tot + $dist2Tot + abs($from2[X] - $from1[X]) + abs($from2[Y] - $from1[Y]);
                $intersections[] = [$from2[X], $from1[Y], $dist];
            }
            $dist2Tot += $dist2;
        }
        $dist1Tot += $dist1;
    }

    return $intersections;
}

function closestIntersectionDistance(array $path1, array $path2): int
{
    $intersections = findIntersections($path1, $path2);
    return _closestIntersectionDistance($intersections);
}

function _closestIntersectionDistance(array $intersections): int
{
    $distances = array_map(fn(array $coord) => abs($coord[X]) + abs($coord[Y]), $intersections);
    return min($distances);
}

function fewestIntersectionSteps(array $path1, array $path2): int
{
    $intersections = findIntersections($path1, $path2);
    return _fewestIntersectionSteps($intersections);
}

function _fewestIntersectionSteps(array $intersections): int
{
    $steps = array_column($intersections, 2);
    return min($steps);
}

function pathSections(array $path): array
{
    $sections = [];
    $pos = [0, 0];
    foreach ($path as $move) {
        $direction = substr($move, 0, 1);
        $distance = (int)substr($move, 1);
        $newPos = $pos;
        switch ($direction) {
            case 'U':
                $newPos[Y] += $distance;
                break;
            case 'D':
                $newPos[Y] -= $distance;
                break;
            case 'L':
                $newPos[X] -= $distance;
                break;
            case 'R':
                $newPos[X] += $distance;
                break;
        }
        $sections[] = [$pos, $newPos, $direction, $distance];
        $pos = $newPos;
    }

    return $sections;
}

$example1 = [['R8', 'U5', 'L5', 'D3'], ['U7', 'R6', 'D4', 'L4']];
$example2 = [['R75', 'D30', 'R83', 'U83', 'L12', 'D49', 'R71', 'U7', 'L72'], ['U62', 'R66', 'U55', 'R34', 'D71', 'R55', 'D58', 'R83']];
$example3 = [['R98', 'U47', 'R26', 'D63', 'R33', 'U87', 'L62', 'D20', 'R33', 'U53', 'R51'], ['U98', 'R91', 'D20', 'R16', 'D67', 'R40', 'U7', 'R15', 'U6', 'R7']];

assert(closestIntersectionDistance($example1[0], $example1[1]) === 6);
assert(closestIntersectionDistance($example2[0], $example2[1]) === 159);
assert(closestIntersectionDistance($example3[0], $example3[1]) === 135);

[$path1, $path2] = array_map(fn($line) => explode(',', $line), array_map('trim', file($inFile)));

echo 'Result part1: ', closestIntersectionDistance($path1, $path2), PHP_EOL;

assert(fewestIntersectionSteps($example1[0], $example1[1]) === 30);
assert(fewestIntersectionSteps($example2[0], $example2[1]) === 610);
assert(fewestIntersectionSteps($example3[0], $example3[1]) === 410);

echo 'Result part2: ', fewestIntersectionSteps($path1, $path2), PHP_EOL;
