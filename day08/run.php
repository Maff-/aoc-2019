<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

$data = trim(file_get_contents($inFile));

function image(string $data, int $width, int $height): array
{
    $layers = array_map(function (string $layer) use ($height) {
        return array_map(function (string $row) {
            return array_map('intval', str_split($row));
        }, str_split($layer, $height));
    }, str_split($data, $width * $height));

    return $layers;
}

$image = image($data, 25, 6);

$layerStats = array_map(function (array $layer) {
    $pixels = array_merge(...$layer);
    return array_count_values($pixels);
}, $image);

$layerZeroCount = array_column($layerStats, 0);
$layerWithLeastZeros = array_search(min($layerZeroCount), $layerZeroCount, true);

echo 'Result part1: ', ($layerStats[$layerWithLeastZeros][1] * $layerStats[$layerWithLeastZeros][2]), PHP_EOL;
