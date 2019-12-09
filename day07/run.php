<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

function run(array $program, array $inputs): ?int
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
                if (count($inputs) === 0) {
                    throw new RuntimeException('No inputs left');
                }
                $input = array_shift($inputs);
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
                throw new RuntimeException('Unexpected opcode ' . var_export($opcode, true));
        }
    }
    return $output;
}

// Code based on https://www.geeksforgeeks.org/write-a-c-program-to-print-all-permutations-of-a-given-string/

function heapPermutation(array $a, array &$result, ?int $l = null, ?int $r = null)
{
    $l ??= 0;
    $r ??= count($a) - 1;

    if ($l === $r) {
        $result[] = $a;
        return;
    }

    for ($i = $l; $i <= $r; $i++) {
        $a = swap($a, $l, $i);
        heapPermutation($a, $result, $l + 1, $r);
        $a = swap($a, $l, $i);
    }
}

function swap(array $a, int $i, int $j): array
{
    $temp = $a[$i];
    $a[$i] = $a[$j];
    $a[$j] = $temp;
    return $a;
}

function maximizeThrust(array $phaseSettings, array $program, ?array &$maxPhaseSetting = []): ?int
{
    $maxOutput = null;
    foreach ($phaseSettings as $phaseSetting) {
        $input = 0;
        foreach ($phaseSetting as $amp => $ampPhase) {
            $input = run($program, [$ampPhase, $input]);
        }
        if ($input > $maxOutput) {
            $maxOutput = $input;
            $maxPhaseSetting = $phaseSetting;
        }
    }
    return $maxOutput;
}

$phases = range(0, 4);

$phaseSettings = [];
heapPermutation($phases, $phaseSettings);

$phaseSettingStrs = array_map('implode', $phaseSettings);
sort($phaseSettingStrs);
assert(count(array_unique($phaseSettingStrs)) === 120);

$program = [3, 15, 3, 16, 1002, 16, 10, 16, 1, 16, 15, 15, 4, 15, 99, 0, 0];
assert(maximizeThrust($phaseSettings, $program) === 43210);
$program = [3, 23, 3, 24, 1002, 24, 10, 24, 1002, 23, -1, 23, 101, 5, 23, 23, 1, 24, 23, 23, 4, 23, 99, 0, 0];
assert(maximizeThrust($phaseSettings, $program) === 54321);
$program = [3, 31, 3, 32, 1002, 32, 10, 32, 1001, 31, -2, 31, 1007, 31, 0, 33, 1002, 33, 7, 33, 1, 33, 31, 31, 1, 32, 31, 31, 4, 31, 99, 0, 0, 0];
assert(($result = maximizeThrust($phaseSettings, $program)) === 65210, 'Expected 65210, got ' . $result);

$program = array_map('intval', explode(',', trim(file_get_contents($inFile))));
echo 'Result part1: ', maximizeThrust($phaseSettings, $program), PHP_EOL;
