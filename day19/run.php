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

class GeneratorInput implements InputProvider
{
    private \Generator $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function next(): int
    {
        $value = $this->generator->current();
        $this->generator->next();
        return $value;
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

function outputRun(Generator $gen): void
{
    while ($gen->valid()) {
        echo chr($gen->current());
        $gen->next();
    }
}

$tiles = [0 => '.', 1 => '#', 9 => 'O'];

function displayMap(array $map, ?int $minX = 0, ?int $maxX = null, ?int $minY = 0, ?int $maxY = null, bool $displayCoords = true): void
{
    global $tiles;

    if (!$map) {
        return;
    }

    $minX ??= min(array_map(fn($row) => min(array_keys($row)), $map));
    $maxX ??= max(array_map(fn($row) => max(array_keys($row)), $map));
    $minY ??= min(array_keys($map));
    $maxY ??= max(array_keys($map));

    $yCoordPad = strlen((string)$maxY);

    if ($displayCoords) {
        echo str_repeat(' ', $yCoordPad + 1), $minX, '->', PHP_EOL;
    }

    for ($y = $minY; $y <= $maxY; $y++) {
        if ($displayCoords) {
            printf('%0' . $yCoordPad . 'd ', $y);
        }
        for ($x = $minX; $x <= $maxX; $x++) {
            $tile = $map[$y][$x] ?? -1;
            echo $tiles[$tile] ?? ' ';
        }

        echo PHP_EOL;
    }
}

function discardOutput(\Generator $gen, int $length = 1): void
{
    for ($i = 0; $i < $length; $i++) {
        $gen->next();
    }
}

function createMap(array $program, int $width = 50, int $height = 50, ?int $minX = 0, ?int $minY = 0, array &$map = [], bool $stopOnEdge = true): array
{
    $prev = null;

    // NB: reading the challenge I assumed that we could use the same 'run' for each location, but that didn't work.

    for ($y = $minY, $maxY = $minY + $height; $y < $maxY; $y++) {
        for ($x = $minX, $maxX = $minX + $width; $x < $maxX; $x++) {
            $map[$y][$x] = $result = getRunOutput(run($program, new InputBuffer([$x, $y])));
            if ($stopOnEdge && $prev !== null && $result !== $prev) {
                // edge detected
                break 1;
            }
            $prev = $result;
        }
        $prev = null;
    }

    return $map;
}

$program = array_map('intval', explode(',', trim(file_get_contents($inFile))));

$width = $height = 50;
$map = createMap($program, $width, $height);
displayMap($map, 0, $width - 1, 0,  $height - 1);

echo 'Result part1: ', array_sum(array_map('array_sum', $map)), PHP_EOL;

//echo implode("\n", array_map(fn ($row) => implode("\t", $row), $map));

// The Magic numbers below are for my specific input!! (these were determined with some spreadsheeting)
// estimates
$hor = 0.885842105;
$ver = 0.79936;

//$map = createMap($program, 200, 175, 400, 500);
//displayMap($map, 400, null, 500, null);

$scanWidth = 10; // even
$halfScanWidth = (int)ceil($scanWidth / 2);

$edges = [];
$map = [];

// NB: yMin/yMax are tuned for my specific input
for ($y=2060; $y < 2200; $y++) {
    $xVer = (int)round($y * ($ver / 1));
    $xHor = (int)round($y * ($hor / 1));
    createMap($program, $scanWidth, 1, max($xVer - $halfScanWidth, 0), $y, $map);
    createMap($program, $scanWidth, 1, max($xHor - $halfScanWidth, 0), $y, $map);

    $prev = $prevX = $horEdge = $verEdge = null;
    foreach ($map[$y] as $x => $value) {
        if ($prevX === $x - 1) {
            if ($prev === 0 && $value === 1) {
                // 'vertical' edge detected;
                $verEdge = $x;
            } elseif ($prev === 1 && $value === 0) {
                // 'horizontal' edge detected;
                $horEdge = $x - 1;
            }
        }
        $prev = $value;
        $prevX = $x;
    }
    if ($horEdge === null) {
        displayMap($map, max($xVer - $halfScanWidth, 0), null, $y, $y);
        throw new \RuntimeException('hor edge not found');
    }
    if ($verEdge === null) {
        displayMap($map, max($xVer - $halfScanWidth, 0), null, $y, $y);
        throw new \RuntimeException('ver edge not found');
    }
    $edges[$y] = [$verEdge, $horEdge];
//    var_dump(compact('y', 'verEdge', 'horEdge'));
//    echo $y, "\t", number_format($horEdge, 1, ',', ''), "\t", number_format($verEdge, 1, ',', ''), PHP_EOL;
}


foreach ($edges as $y => [$verEdge, $horEdge]) {
    $x = $horEdge - 99;
    $y100 = $y + 99;
    if ($verEdge > $x) {
        continue;
    }
    if (!isset($edges[$y100])) {
        break;
    }
    $verEdge100 = $edges[$y100][0];
    if ($verEdge100 <= $x) {
        $result = (10000 * $x) + $y;
//        var_dump(compact('x', 'y', 'result', 'verEdge', 'horEdge', 'y100', 'verEdge100'));
        for ($_y=$y; $_y < $y + 100; $_y++) {
            for ($_x=$x; $_x < $x + 100; $_x++) {
                $map[$_y][$_x] = 9;
            }
        }
        echo 'Result part2: ', $result, PHP_EOL;
        break;
    }
}

//displayMap($fooMap, $edges[array_key_first($edges)][0] - 2, $edges[array_key_last($edges)][1] + 2, null);
