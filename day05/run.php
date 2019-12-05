<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

$opcodes = [
    1 => [
        'argc' => 4,
        'fn' => fn (array &$program, array $args) => (
        $program[$args[2][0]]
            = ($args[0][1] ? $args[0][0] : $program[$args[0][0]])
            + ($args[1][1] ? $args[1][0] : $program[$args[1][0]])
        ),
    ],
    2 => [
        'argc' => 4,
        'fn' => fn (array &$program, array $args) => (
        $program[$args[2][0]]
            = ($args[0][1] ? $args[0][0] : $program[$args[0][0]])
            * ($args[1][1] ? $args[1][0] : $program[$args[1][0]])
        ),
    ],
];

function run(array $program, int $input = 1): ?int
{
    $output = null;
    $length = count($program);
    $pos = 0;
    while ($pos < $length) {
        $opcode = $program[$pos] % 100;
        $modes = array_pad(array_map('intval', str_split((string)(int)($program[$pos] / 100))), -3, 0);
        $modes = array_reverse($modes);
        switch ($opcode) {
            case 1: // +
                [$posA, $posB, $posResult] = array_slice($program, $pos + 1, 3);
                $program[$posResult] = ($modes[0] ? $posA : $program[$posA]) + ($modes[1] ? $posB : $program[$posB]);
                $pos += 4;
                break;
            case 2: // *
                [$posA, $posB, $posResult] = array_slice($program, $pos + 1, 3);
                $program[$posResult] = ($modes[0] ? $posA : $program[$posA]) * ($modes[1] ? $posB : $program[$posB]);
                $pos += 4;
                break;
            case 3: // input
                $posResult = $program[$pos + 1];
                $program[$posResult] = $input;
                $pos += 2;
                break;
            case 4: // output
                $posResult = $program[$pos + 1];
                $output = $program[$posResult];
                $pos += 2;
                break;
            case 99:
                break 2;
            default;
                throw new \RuntimeException('Unexpected opcode ' . var_export($opcode, true));
        }
    }
    return $output;
}

$test = [3,0,4,0,99];
assert(run($test,1337 ) === 1337);
assert(run($test,1 ) === 1);

$test = [1002,4,3,4,33];
assert(run($test,1 ) === null);

$in = array_map('trim', file($inFile));
$programs = array_map(fn($line) => array_map('intval', explode(',', $line)), $in);

echo 'Result part1: ', run($programs[0], 1), PHP_EOL;
