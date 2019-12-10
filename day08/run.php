<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

$data = trim(file_get_contents($inFile));

function image(string $data, int $width, int $height): array
{
    $layers = array_map(function (string $layer) use ($width) {
        return array_map(function (string $row) {
            return array_map('intval', str_split($row));
        }, str_split($layer, $width));
    }, str_split($data, $width * $height));

    return $layers;
}

$width = 25;
$height = 6;
$image = image($data, $width, $height);

$layerStats = array_map(function (array $layer) {
    $pixels = array_merge(...$layer);
    return array_count_values($pixels);
}, $image);

$layerZeroCount = array_column($layerStats, 0);
$layerWithLeastZeros = array_search(min($layerZeroCount), $layerZeroCount, true);

echo 'Result part1: ', ($layerStats[$layerWithLeastZeros][1] * $layerStats[$layerWithLeastZeros][2]), PHP_EOL;

$lastLayer = array_key_last($image);
$mergedImage = array_fill(0, $height, array_fill(0, $width, '?'));
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        foreach ($image as $layer => $rows) {
            $color = $rows[$y][$x];
            if ($color !== 2) {
                $mergedImage[$y][$x] = $color ? '#' : ' ';
                break;
            }
        }
    }
}

echo 'Result part2:', PHP_EOL;
foreach ($mergedImage as $row) {
    echo implode($row), PHP_EOL;
}
