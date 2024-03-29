<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

if (PHP_INT_SIZE < 8) {
    echo 'You need a 64 bit version of php to run this program.', PHP_EOL;
    return 1;
}

class InputBuffer
{
    private array $buffer = [];

    public function __construct(array $buffer = [])
    {
        $this->buffer = $buffer;
    }

    public function push(int $value): void
    {
        $this->buffer[] = $value;
    }

    public function next(): int
    {
        return array_shift($this->buffer);
    }

    public function hasNext(): bool
    {
        return count($this->buffer) !== 0;
    }
}

function run(array $program, ?InputBuffer $inputs = null, int $relativeBase = 0)
{
    $inputs ??= new InputBuffer();

    $output = null;
    $length = count($program);
    $pos = 0;
    while ($pos < $length) {
        $opcode = $program[$pos] % 100;
        $modes = array_map('intval', str_split((string)(int)($program[$pos] / 100)));
        switch ($opcode) {
            case 1: // +
                [$param0, $param1, $posResult] = readArguments($program, $pos + 1, 3, $modes, $relativeBase, [0, 0, 1]);
                $program[$posResult] = $param0 + $param1;
                $pos += 4;
                break;
            case 2: // *
                [$param0, $param1, $posResult] = readArguments($program, $pos + 1, 3, $modes, $relativeBase, [0, 0, 1]);
                $program[$posResult] = $param0 * $param1;
                $pos += 4;
                break;
            case 3: // input
                [$posResult] = readArguments($program, $pos + 1, 1, $modes, $relativeBase, [1]);
                $input = $inputs->next();
                $program[$posResult] = $input;
                $pos += 2;
                break;
            case 4: // output
                [$output] = readArguments($program, $pos + 1, 1, $modes, $relativeBase);
                yield $output;
                $pos += 2;
                break;
            case 5: // jump-if-true
                [$param0, $param1] = readArguments($program, $pos + 1, 2, $modes, $relativeBase);
                if ($param0 !== 0) {
                    $pos = $param1;
                } else {
                    $pos += 3;
                }
                break;
            case 6: // jump-if-false
                [$param0, $param1] = readArguments($program, $pos + 1, 2, $modes, $relativeBase);
                if ($param0 === 0) {
                    $pos = $param1;
                } else {
                    $pos += 3;
                }
                break;
            case 7: // less than
                [$param0, $param1, $posResult] = readArguments($program, $pos + 1, 3, $modes, $relativeBase, [0, 0, 1]);
                $program[$posResult] = $param0 < $param1 ? 1 : 0;
                $pos += 4;
                break;
            case 8: // equals
                [$param0, $param1, $posResult] = readArguments($program, $pos + 1, 3, $modes, $relativeBase, [0, 0, 1]);
                $program[$posResult] = $param0 === $param1 ? 1 : 0;
                $pos += 4;
                break;
            case 9: // set relative base
                [$relativeBaseOffset] = readArguments($program, $pos + 1, 1, $modes, $relativeBase);
                $relativeBase += $relativeBaseOffset;
                $pos += 2;
                break;
            case 99:
                break 2;
            default;
                throw new RuntimeException('Unexpected opcode ' . var_export($opcode, true));
        }
    }
    return $output;
}

function readArguments(array $program, int $pos, int $count, array $modes, int $relativeBase, array $pointerModes = []): array
{
    $modes = array_reverse(array_pad($modes, 0 - $count, 0));
    $arguments = [];
    for ($i = 0; $i < $count; $i++) {
        $argument = $program[$pos + $i] ?? 0;
        $asPointer = (bool)($pointerModes[$i] ?? 0);
        if ($asPointer) {
            switch ($modes[$i]) {
                case 0: // position mode
                    $arguments[$i] = $argument;
                    break;
                case 1: // immediate mode
                    throw new \InvalidArgumentException('Can\'t get pointer from immediate value');
                    break;
                case 2: // relative mode
                    $arguments[$i] = $relativeBase + $argument;
                    break;
            }
        } else {
            switch ($modes[$i]) {
                case 0: // position mode
                    $arguments[$i] = $program[$argument] ?? 0;
                    break;
                case 1: // immediate mode
                    $arguments[$i] = $argument;
                    break;
                case 2: // relative mode
                    $arguments[$i] = $program[$relativeBase + $argument] ?? 0;
                    break;
            }
        }
    }

    return $arguments;
}

function getRunOutput(\Generator $gen): int
{
    while ($gen->valid()) {
        $gen->next();
    }
    return $gen->getReturn();
}

function debugRun(\Generator $gen): void
{
    while ($gen->valid()) {
        echo 'Output: ', var_export($gen->current(), true), PHP_EOL;
        $gen->next();
    }
    echo 'Return: ', var_export($gen->getReturn(), true), PHP_EOL;
}

$directions = [
    '^' => ['<', '>', [0, -1]],
    '>' => ['^', 'v', [1, 0]],
    'v' => ['>', '<', [0, 1]],
    '<' => ['v', '^', [-1, 0]],
];

function paintPanels(array $program, int $startColor = 0): array
{
    global $directions;

    $input = new InputBuffer([$startColor]);
    $robot = run($program, $input);

    $panels = [];
    $x = $y = 0;
    $direction = '^';
    while ($robot->valid()) {
        $color = $robot->current();
        $panels[$x][$y] = $color;
        $robot->next();
        $turn = $robot->current();
        $direction = $directions[$direction][$turn];
        [$moveX, $moveY] = $directions[$direction][2];
        $x += $moveX;
        $y += $moveY;
        $input->push($panels[$x][$y] ?? 0);
        $robot->next();
    }

    return $panels;
}

$program = array_map('intval', explode(',', trim(file_get_contents($inFile))));

echo 'Result part1: ', count(array_merge(...paintPanels($program, 0))), PHP_EOL;

$panels = paintPanels($program, 1);

echo 'Result part2: ', PHP_EOL;

$width = max(array_keys($panels)) + 1;
$height = max(array_map('max', array_map('array_keys', $panels))) + 1;
// NB: we assume xMin = yMin = 0

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        echo ($panels[$x][$y] ?? 0) ? 'X' : ' ';
    }
    echo PHP_EOL;
}
