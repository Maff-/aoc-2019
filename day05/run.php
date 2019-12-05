<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

function run(array $program, int $input = 1): ?int
{
    $output = null;
    $length = count($program);
    $pos = 0;
    while ($pos < $length) {
        $opcode = $program[$pos] % 100;
        $modes = array_map('intval', str_split((string)(int)($program[$pos] / 100)));
        switch ($opcode) {
            case 1: // +
                [$posA, $posB, $posResult] = array_slice($program, $pos + 1, 3);
                $modes = array_reverse(array_pad($modes, -3, 0));
                $program[$posResult] = ($modes[0] ? $posA : $program[$posA]) + ($modes[1] ? $posB : $program[$posB]);
                $pos += 4;
                break;
            case 2: // *
                [$posA, $posB, $posResult] = array_slice($program, $pos + 1, 3);
                $modes = array_reverse(array_pad($modes, -3, 0));
                $program[$posResult] = ($modes[0] ? $posA : $program[$posA]) * ($modes[1] ? $posB : $program[$posB]);
                $pos += 4;
                break;
            case 3: // input
                $posResult = $program[$pos + 1];
                $program[$posResult] = $input;
                $pos += 2;
                break;
            case 4: // output
                $modes = array_reverse(array_pad($modes, -1, 0));
                $posResult = $program[$pos + 1];
                $output = $modes[0] ? $posResult : $program[$posResult];
                $pos += 2;
                break;
            case 5: // jump-if-true
                $modes = array_reverse(array_pad($modes, -2, 0));
                [$param0, $param1] = array_slice($program, $pos + 1, 2);
                if (($modes[0] ? $param0 : $program[$param0]) !== 0) {
                    $pos = $modes[1] ? $param1 : $program[$param1];
                } else {
                    $pos += 3;
                }
                break;
            case 6: // jump-if-false
                $modes = array_reverse(array_pad($modes, -2, 0));
                [$param0, $param1] = array_slice($program, $pos + 1, 2);
                if (($modes[0] ? $param0 : $program[$param0]) === 0) {
                    $pos = $modes[1] ? $param1 : $program[$param1];
                } else {
                    $pos += 3;
                }
                break;
            case 7: // less than
                [$param0, $param1, $posResult] = array_slice($program, $pos + 1, 3);
                $modes = array_reverse(array_pad($modes, -3, 0));
                $program[$posResult] = ($modes[0] ? $param0 : $program[$param0]) < ($modes[1] ? $param1 : $program[$param1]) ? 1 : 0;
                $pos += 4;
                break;
            case 8: // equals
                [$param0, $param1, $posResult] = array_slice($program, $pos + 1, 3);
                $modes = array_reverse(array_pad($modes, -3, 0));
                $program[$posResult] = ($modes[0] ? $param0 : $program[$param0]) === ($modes[1] ? $param1 : $program[$param1]) ? 1 : 0;
                $pos += 4;
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

$test = [
    3,21,1008,21,8,20,1005,20,22,107,8,21,20,1006,20,31,
    1106,0,36,98,0,0,1002,21,125,20,4,20,1105,1,46,104,
    999,1105,1,46,1101,1000,1,20,4,20,1105,1,46,98,99
];
assert(run($test,1 ) === 999);
assert(run($test,7 ) === 999);
assert(run($test,8 ) === 1000);
assert(run($test,9 ) === 1001);
assert(run($test,10 ) === 1001);

echo 'Result part2: ', run($programs[0], 5), PHP_EOL;
