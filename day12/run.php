<?php
declare(strict_types=1);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';

class Vec3
{
    public int $x;
    public int $y;
    public int $z;

    public function __construct(int $x = 0, int $y = 0, int $z = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function energy(): int
    {
        return array_sum(array_map('abs', (array)$this));
    }

    public function __toString(): string
    {
        return sprintf('<x=%d, y=%d, z=%d>', $this->x, $this->y, $this->z);
    }
}

class Moon
{
    public Vec3 $pos;
    public Vec3 $vel;

    public function __construct(Vec3 $pos = null, Vec3 $vel = null)
    {
        $this->pos = $pos ?? new Vec3();
        $this->vel = $vel ?? new Vec3();
    }

    public function __toString(): string
    {
        return sprintf('pos=%s, vel=%s', $this->pos, $this->vel);
    }
}

/** @param Moon[] $moons */
function step(array &$moons)
{
    $moonCount = count($moons);
    // apply gravity
    for ($i = 0; $i < $moonCount; $i++) {
        for ($j = $i + 1; $j < $moonCount; $j++) {
            $a = $moons[$i];
            $b = $moons[$j];
            foreach (['x', 'y', 'z'] as $axis) {
                $change = $b->pos->$axis <=> $a->pos->$axis;
                $a->vel->$axis += $change;
                $b->vel->$axis -= $change;
            }
        }
    }
    // apply velocity
    foreach ($moons as $moon) {
        foreach (['x', 'y', 'z'] as $axis) {
            $moon->pos->$axis += $moon->vel->$axis;
        }
    }
}

/** @param Moon[] $moons */
function run(array &$moons, int $steps = 1): void
{
//    echo 'After 0 steps:', PHP_EOL, implode(PHP_EOL, $moons), PHP_EOL, PHP_EOL;
    for ($i = 0; $i < $steps; $i++) {
        step($moons);
//        echo 'After ', $i + 1, ' steps:', PHP_EOL, implode(PHP_EOL, $moons), PHP_EOL, PHP_EOL;
    }
}

/** @param Moon[] $moons */
function totalEnergy(array $moons): int
{
    $sum = 0;
    foreach ($moons as $moon) {
        $sum += $moon->pos->energy() * $moon->vel->energy();
    }
    return $sum;
}

/** @return Vec3[] */
function readInput(string $input): array
{
    if (preg_match_all('/<x=(?<x>-?\d+), y=(?<y>-?\d+), z=(?<z>-?\d+)>/', $input, $matches, PREG_SET_ORDER)) {
        return array_map(
            fn($match) => new Moon(new Vec3((int)$match['x'], (int)$match['y'], (int)$match['z'])),
            $matches
        );
    }
    throw new InvalidArgumentException('Failed to read input');
}

$example = <<<EOF
<x=-1, y=0, z=2>
<x=2, y=-10, z=-7>
<x=4, y=-8, z=8>
<x=3, y=5, z=-1>
EOF;

$moons = readInput($example);
run($moons, 10);
assert(totalEnergy($moons) === 179);

$moons = readInput(trim(file_get_contents($inFile)));
run($moons, 1000);
echo 'Result part1: ', totalEnergy($moons), PHP_EOL;
