<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

function parseInput(string $input): array
{
    return array_map('intval', str_split(trim($input)));
}

function run(array $input, int $phases = 1, array $pattern = [0, 1, 0, -1]): array
{
//    echo 'Running ', $phases, ' phases on ', substr(implode('', $input), 0, 100), (count($input) > 100 ? '... length=' . count($input) : null), PHP_EOL;

    if ($pattern === [0, 1, 0, -1] && count($input) % 4 === 0) {
        return runOptimized($input, $phases);
    }

    $result = [];
    for ($i = 0; $i < $phases; $i++) {
        foreach ($input as $k => $value) {
            $segmentPattern = array_merge(...array_map(fn(int $a) => array_fill(0, $k + 1, $a), $pattern));
            $patternLength = count($segmentPattern);
            $result[$k] = 0;
            foreach ($input as $n => $nValue) {
                $multiplier = $segmentPattern[($n + 1) % $patternLength];
                $result[$k] += $nValue * $multiplier;
            }
            $result[$k] = abs($result[$k]) % 10;
        }
        $input = $result;
    }

    return $result;
}

function runOptimized(array $input, int $phases = 1): array
{
    $inputLength = count($input);
    if ($inputLength % 4 !== 0) {
        throw new \RuntimeException('Input length must be dividable by 4');
    }

    $result = [];
    for ($i = 0; $i < $phases; $i++) {
        foreach ($input as $k => $value) {
            $result[$k] = 0;
            $sliceLength = $k + 1;
            // TODO: optimize for lo $k's??
            for ($j = 0, $jMax = ceil($inputLength / ($sliceLength * 4)); $j < $jMax; $j++) {
                $offsetOne = $k + ($j * $sliceLength * 4);
                $offsetMinusOne = $offsetOne + $sliceLength + $sliceLength;
                $result[$k] += array_sum(array_slice($input, $offsetOne, $sliceLength));
                $result[$k] -= array_sum(array_slice($input, $offsetMinusOne, $sliceLength));
            }

            $result[$k] = abs($result[$k]) % 10;
        }
        $input = $result;
    }

    return $result;
}

function decodeRealSignal(array $input, int $inputRepeat = 10000, int $offsetLength = 7, int $messageLength = 8, int $phases = 100, array $pattern = [0, 1, 0, -1]): string
{
    $messageOffset = (int)implode(array_slice($input, 0, $offsetLength));
    $input = str_split(str_repeat(implode($input), $inputRepeat));
    $output = run($input, $phases, $pattern);
    return implode('', array_slice($output, $messageOffset, $messageLength));
}

assert(implode('', run(parseInput('12345678'), 4)) === '01029498');
assert(strpos(implode('', run(parseInput('80871224585914546619083218645595'), 100)), '24176176') === 0);
assert(strpos(implode('', run(parseInput('19617804207202209144916044189917'), 100)), '73745418') === 0);
assert(strpos(implode('', run(parseInput('69317163492948606335995924319873'), 100)), '52432133') === 0);

$input = file_get_contents($inFile);

echo 'Result part1: ', substr(implode('', run(parseInput($input), 100)), 0, 8), PHP_EOL;

assert(decodeRealSignal(parseInput('03036732577212944063491565474664')) === '84462026');
echo 'part2 exmaple1 succeded', PHP_EOL;
assert(decodeRealSignal(parseInput('02935109699940807407585447034323')) === '78725270');
echo 'part2 exmaple2 succeded', PHP_EOL;
assert(decodeRealSignal(parseInput('03081770884921959731165446850517')) === '53553731');
echo 'part2 exmaple3 succeded', PHP_EOL;

echo 'Result part2: ', decodeRealSignal(parseInput($input)), PHP_EOL;
