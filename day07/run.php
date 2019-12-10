<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

class InputBuffer {
    private array $buffer = [];
    public function __construct(array $buffer = []) { $this->buffer = $buffer; }
    public function push(int $value): void { $this->buffer[] = $value; }
    public function next(): int { return array_shift($this->buffer); }
    public function hasNext(): bool { return count($this->buffer) !== 0; }
}

function run(array $program, InputBuffer $inputs)
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
                $input = $inputs->next();
                $program[$posResult] = $input;
                $pos += 2;
                break;
            case 4: // output
                $modes = array_reverse(array_pad($modes, -1, 0));
                $posResult = $program[$pos + 1];
                $output = $modes[0] ? $posResult : $program[$posResult];
                yield $output;
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
            $input = getRunOutput(run($program, new InputBuffer([$ampPhase, $input])));
        }
        if ($input > $maxOutput) {
            $maxOutput = $input;
            $maxPhaseSetting = $phaseSetting;
        }
    }
    return $maxOutput;
}

function maximizeThrust2(array $phaseSettings, array $program, ?array &$maxPhaseSetting = [])
{
    $maxOutput = null;
    foreach ($phaseSettings as $phaseSetting) {
        $ampCount = count($phaseSetting);
        /** @var InputBuffer[] $inputBuffers */
        $inputBuffers = array_map(fn(int $ampPhase) => new InputBuffer([$ampPhase]), $phaseSetting);
        $inputBuffers[0]->push(0);
        /** @var \Generator[] $amps */
        $amps = array_map(fn(InputBuffer $ampInput) => run($program, $ampInput), $inputBuffers);
        $ampsAdvance = array_fill_keys(array_keys($phaseSetting), false);
        $output = null;
        for ($i = 0; ; $i = ($i + 1) % $ampCount) {
            $nextAmp = ($i + 1) % $ampCount;
            if ($ampsAdvance[$i]) {
                $amps[$i]->next();
            }
            if ($amps[$i]->valid()) {
                $output = $amps[$i]->current();
                $inputBuffers[$nextAmp]->push($output);
                $ampsAdvance[$i] = true;
            } else {
                break;
            }
        }
        if ($output > $maxOutput) {
            $maxOutput = $output;
            $maxPhaseSetting = $phaseSetting;
        }
        continue;
    }
    return $maxOutput;
}

function getRunOutput(\Generator $gen): int
{
    while ($gen->valid()) {
        $gen->next();
    }
    return $gen->getReturn();
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

// --- part 2 --- //

$phases = range(5, 9);
$phaseSettings = [];
heapPermutation($phases, $phaseSettings);

$example = [3, 26, 1001, 26, -4, 26, 3, 27, 1002, 27, 2, 27, 1, 27, 26, 27, 4, 27, 1001, 28, -1, 28, 1005, 28, 6, 99, 0, 0, 5];
assert(maximizeThrust2($phaseSettings, $example) === 139629729);

$example = [3, 52, 1001, 52, -5, 52, 3, 53, 1, 52, 56, 54, 1007, 54, 5, 55, 1005, 55, 26, 1001, 54, -5, 54, 1105, 1, 12, 1, 53, 54, 53, 1008, 54, 0, 55, 1001, 55, 1, 55, 2, 53, 55, 53, 4, 53, 1001, 56, -1, 56, 1005, 56, 6, 99, 0, 0, 0, 0, 10];
assert(maximizeThrust2($phaseSettings, $example) === 18216);

echo 'Result part1: ', maximizeThrust2($phaseSettings, $program), PHP_EOL;
