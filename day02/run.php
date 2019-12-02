<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

$in = array_map('trim', file($inFile));

$programs = array_map(fn($line) => array_map('intval', explode(',', $line)), $in);

function run(array &$program) {
    $length = count($program);
    $pos = 0;
    while ($pos < $length) {
        $opcode = $program[$pos];
        switch ($opcode) {
            case 1:
                [$posA, $posB, $posResult] = array_slice($program, $pos + 1, 3);
                $program[$posResult] = $program[$posA] + $program[$posB];
                $pos += 4;
                break;
            case 2:
                [$posA, $posB, $posResult] = array_slice($program, $pos + 1, 3);
                $program[$posResult] = $program[$posA] * $program[$posB];
                $pos += 4;
                break;
            case 99:
                break 2;
        }
    }
    return $program[0];
}

$test = [1, 9, 10, 3, 2, 3, 11, 0, 99, 30, 40, 50];
assert(run($test) === 3500);

$backup = $programs[0];

// restore program
$programs[0][1] = 12;
$programs[0][2] = 2;

run($programs[0]);

echo 'Result part1: ', $programs[0][0], PHP_EOL;

$target = 19690720;

foreach (range(0, 99) as $noun) {
    foreach (range(0, 99) as $verb) {
        $program = $backup;
        $program[1] = $noun;
        $program[2] = $verb;
        $result = run($program);
//        var_dump(compact('noun', 'verb', 'result'));
        if ($result === $target) {
            echo 'Result part2: ', ((100 * $noun) + $verb), PHP_EOL;
            exit;
        }
    }
}

echo 'Failed to find output', PHP_EOL;
exit(1);
