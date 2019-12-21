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

function displayMap(array $map, ?int $minX = null, ?int $maxX = null, ?int $minY = null, ?int $maxY = null): void
{
    $minX ??= min(array_map(fn($row) => min(array_keys($row)), $map));
    $maxX ??= max(array_map(fn($row) => max(array_keys($row)), $map));
    $minY ??= min(array_keys($map));
    $maxY ??= max(array_keys($map));

    for ($y = $minY; $y <= $maxY; $y++) {
        for ($x = $minX; $x <= $maxX; $x++) {
//            if ($x === 0 && $y === 0) {
//                echo 'S';
//                continue;
//            }
            $tile = $map[$y][$x] ?? null;
            switch ($tile ?? -1) {
                case -1:
                    echo ' ';
                    break;
                case 0:
                    echo '#';
                    break;
                case 1:
                    echo '.';
                    break;
                case 2:
                    echo 'O';
                    break;
            }
        }

        echo PHP_EOL;
    }
}

// Only four movement commands are understood: north (1), south (2), west (3), and east (4)

$movements = [
    1 => [ 0, -1],
    2 => [ 0,  1],
    3 => [-1,  0],
    4 => [ 1,  0],
];

function runProgram(array $program, array &$map = [], int $maxIterations = 10000)
{
    global $movements;

    $x = $y = 0;
    $i = 0;

    $move = random_int(1, 4);
    $input = new InputBuffer([$move]);
    $foo = run($program, $input);

    while ($foo->valid() && (!$maxIterations || $i++ < $maxIterations)) {
        $targetX = $x + $movements[$move][0];
        $targetY = $y + $movements[$move][1];
        $result = $foo->current();
        $map[$targetY][$targetX] = $result;
        switch ($result) {
            case 0: // The repair droid hit a wall. Its position has not changed.
                break;
            case 1: // The repair droid has moved one step in the requested direction.
                $x = $targetX;
                $y = $targetY;
                break;
            case 2: // The repair droid has moved one step in the requested direction; its new position is the location of the oxygen system.
//                echo "\033[2J\033[;H";
//                displayMap($map);
//                echo 'Located oxygen system!! <x=', $targetX, ' y=', $targetY, '>', PHP_EOL;
//                return [$targetX, $targetY];
                $x = $targetX;
                $y = $targetY;
                break;
        }
//        echo "\033[2J\033[;H";
//        printf("x: %d, y: %d\n", $x, $y);
//        displayMap($map);

        // Decide next move
        $possibleMoves = [];
        foreach ($movements as $move => $movement) {
            $tile = $map[$y + $movements[$move][1]][$x + $movements[$move][0]] ?? null;
            if ($tile !== 0) {
                $possibleMoves[$move] = $tile;
            }
        }
        if (count($possibleMoves) === 0) {
            throw new \RuntimeException('WTF got stuck>?');
        }

        // Prefer to move into the unknown
        $preferredMoves = array_filter($possibleMoves, 'is_null');
        $move = array_rand($preferredMoves ?: $possibleMoves);
        $input->push($move);
        $foo->next();
    }

    throw new \RuntimeException('Failed to locate oxygen system?!');
}

$program = array_map('intval', explode(',', trim(file_get_contents($inFile))));

$combinedMap = [];
$mapCacheFile = __DIR__ . '/map.dat';
if (!file_exists($mapCacheFile) || !($mapData = file_get_contents($mapCacheFile))) {
    for ($i = 0; $i < 20; $i++) {
        try {
            $map = [];
            runProgram($program, $map);
        } catch (\RuntimeException $e) {
//            echo $e->getMessage();
        }
        foreach ($map as $y => $row) {
            foreach($row as $x => $title) {
                $combinedMap[$y][$x] ??= $title;
            }
        }
    }
    file_put_contents($mapCacheFile, serialize($combinedMap));
    echo 'Saved map to ', $mapCacheFile, PHP_EOL;
} else {
    $combinedMap = unserialize($mapData, ['allowed_classes' => false]);
}

//var_dump($map);
displayMap($combinedMap);

echo 'Result part1: ', 'count in map :D', PHP_EOL;

$map = $combinedMap;

$steps = 0;
while(true) {
    $oxygenTiles = array_filter(array_map(fn($row) => array_filter($row, fn($tile) => $tile === 2), $map));
    $tilesUpdated = false;
    foreach ($oxygenTiles as $y => $row) {
        foreach($row as $x => $title) {
            foreach ($movements as [$moveX, $moveY]) {
                $targetX = $x + $moveX;
                $targetY = $y + $moveY;
                $target = $map[$targetY][$targetX] ?? null;
                if ($target === 1) {
                    $map[$targetY][$targetX] = 2;
                    $tilesUpdated = true;
                }
            }
        }
    }

    if (!$tilesUpdated) {
        break;
    }

    $steps++;
//    echo "\033[2J\033[;H";
//    echo $steps, PHP_EOL;
//    displayMap($map);
}

echo 'Result part1: ', $steps, PHP_EOL;
