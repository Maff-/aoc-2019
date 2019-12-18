<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

interface InputProvider
{
    public function next(): int;
}

class InputBuffer implements InputProvider
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

    public function peek(): int
    {
        return $this->buffer[array_key_first($this->buffer)];
    }

    public function hasNext(): bool
    {
        return count($this->buffer) !== 0;
    }
}

class CallbackInput implements InputProvider
{
    private \Closure $callback;

    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    public function next(): int
    {
        return ($this->callback)();
    }
}

function run(array $program, ?InputProvider $inputs = null, int $relativeBase = 0)
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

function getRunOutput(Generator $gen): int
{
    while ($gen->valid()) {
        $gen->next();
    }
    return $gen->getReturn();
}

function debugRun(Generator $gen): void
{
    while ($gen->valid()) {
        echo 'Output: ', var_export($gen->current(), true), PHP_EOL;
        $gen->next();
    }
    echo 'Return: ', var_export($gen->getReturn(), true), PHP_EOL;
}

function displayMap(array $map, ?int $minX = 0, ?int $maxX = null, ?int $minY = 0, ?int $maxY = null): void
{
    $minX ??= min(array_map(fn($row) => min(array_keys($row)), $map));
    $maxX ??= max(array_map(fn($row) => max(array_keys($row)), $map));
    $minY ??= min(array_keys($map));
    $maxY ??= max(array_keys($map));

    for ($y = $minY; $y <= $maxY; $y++) {
        for ($x = $minX; $x <= $maxX; $x++) {
            $tile = $map[$y][$x] ?? null;
            echo $tile;
        }

        echo PHP_EOL;
    }
}

function createMap(\Generator $gen): array
{
    $tileMapping = [35 => '#', 46 => '.'];
    $map = [];
    $x = $y = 0;

    while ($gen->valid()) {
        switch ($output = $gen->current()) {
            case 10:
                $y++;
                $x = 0;
                break;
            case 35:
            case 46:
                $map[$y][$x] = $tileMapping[$output];
                $x++;
                break;
        }
        $gen->next();
    }

    return $map;
}

function getAlignmentParams(array $map): int
{
    $sum = 0;

    foreach ($map as $y => $row) {
        foreach ($row as $x => $tile) {
            if ($tile === '#'
                && ($map[$y - 1][$x] ?? null) === '#' // above
                && ($map[$y + 1][$x] ?? null) === '#' // below
                && ($map[$y][$x - 1] ?? null) === '#' // left
                && ($map[$y][$x + 1] ?? null) === '#' // right
            ) {
                // found intersection
                $sum += $x * $y;
            }
        }
    }

    return $sum;
}

$program = array_map('intval', explode(',', trim(file_get_contents($inFile))));

$map = createMap(run($program));
displayMap($map);

echo 'Result part1: ', getAlignmentParams($map), PHP_EOL;

