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
    $segmentPatterns = [];
    for ($i = 0; $i < $phases; $i++) {
        $result = [];
        foreach ($input as $k => $value) {
            $segmentPatterns[$k] ??= array_merge(...array_map(fn(int $a) => array_fill(0, $k + 1, $a), $pattern));
            $patternLength = count($segmentPatterns[$k]);
            $result[$k] = 0;
            foreach ($input as $n => $nValue) {
                $multiplier = $segmentPatterns[$k][($n + 1) % $patternLength];
                $result[$k] += $nValue * $multiplier;
            }

            $result[$k] = abs($result[$k]) % 10;
        }
        $input = $result;
    }

    return $result;
}

assert(implode('', run(parseInput('12345678'), 4)) === '01029498');
assert(strpos(implode('', run(parseInput('80871224585914546619083218645595'), 100)), '24176176') === 0);
assert(strpos(implode('', run(parseInput('19617804207202209144916044189917'), 100)), '73745418') === 0);
assert(strpos(implode('', run(parseInput('69317163492948606335995924319873'), 100)), '52432133') === 0);

$input = file_get_contents($inFile);

echo 'Result part1: ', substr(implode('', run(parseInput($input), 100)), 0, 8), PHP_EOL;
