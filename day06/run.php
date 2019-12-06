<?php
declare(strict_types=1);

class O
{
    public ?self $parent;
    public string $name;
    public array $children = [];

    public function __construct(string $name, ?self $parent)
    {
        $this->name = $name;
        $this->parent = $parent;
        if ($parent) {
            $parent->children[$name] = $this;
        }
    }

    public function getOrbitCount(): int
    {
        return $this->parent ? $this->parent->getOrbitCount() + 1 : 0;
    }
}

/** @return O[] */
function readObject(string $input): array
{
    $map = array_map(fn($l) => explode(')', $l, 2), explode("\n", trim($input)));
    /** @var O[] $objects */
    $objects = [];
    foreach ($map as [$parent, $object]) {
        // TODO: improve code
        if (!isset($objects[$parent])) {
            $objects[$parent] = new O($parent, null);
        }
        if (!isset($objects[$object])) {
            $objects[$object] = new O($object, $objects[$parent]);
        } else {
            $objects[$object]->parent = $objects[$parent];
            $objects[$parent]->children[$object] = $objects[$object];
        }
    }

    return $objects;
}

/** @param O[] $objects */
function totalOrbits(array $objects): int
{
    return array_sum(array_map(fn(O $o) => $o->getOrbitCount(), $objects));
}

$in = <<<IN
COM)B
B)C
C)D
D)E
E)F
B)G
G)H
D)I
E)J
J)K
K)L
IN;

assert(totalOrbits(readObject($in)) === 42);

$args = array_slice($_SERVER['argv'], 1);
$inFile = $args[1] ?? 'input.txt';
$in = file_get_contents($inFile);

$objects = readObject($in);
//$com = $objects['COM'];

echo 'Result part1: ', totalOrbits($objects), PHP_EOL;
