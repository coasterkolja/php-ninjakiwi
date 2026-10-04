# Collections

Some DTO fields are lists rather than scalars. Those are modelled as
`Collection` objects instead of bare arrays, so they are iterable and countable
and carry an element type for static analysis.

```php
use Kan\NkOpendata\Collections\Collection;
```

## What you get

`Collection` implements `IteratorAggregate` and `Countable`:

```php
$parts = $entry->scoreParts;

count($parts);                    // int
foreach ($parts as $part) {       // no getIterator() boilerplate
    echo $part->name;
}
$parts->toArray();                // array<int, ScorePart>, if you need a plain array
```

There is no `get()`, `add()` or `filter()`. Collections are read-only views over
data the API returned, and the intended way to transform one is plain PHP:

```php
$names  = array_map(fn (ScorePart $p) => $p->name, $parts->toArray());
$total  = array_sum(array_map(fn (ScorePart $p) => $p->score, $parts->toArray()));
$byType = array_count_values(array_map(fn (ScorePart $p) => $p->type, $parts->toArray()));
```

## Shipped collections

| Class | Element | Used by |
| --- | --- | --- |
| [`ScorePartCollection`](../../src/Collections/ScorePartCollection.php) | [`ScorePart`](dtos.md#scorepart) | `$entry->scoreParts` on boss and race leaderboard entries |
| [`TowerCollection`](../../src/Collections/TowerCollection.php) | [`Tower`](dtos.md#tower) | `$metadata->towers` on [Metadata](dtos.md#metadata) |

```php
foreach ($metadata->towers as $entry) {
    if ($entry->isHero) {
        printf("hero %s, max %d\n", $entry->tower->value, $entry->max);
    }
}
```

## Where they do not appear

Resource methods that return a list of top-level items return a **plain array**,
not a `Collection`:

```php
$tiles    = $client->ct($id)->tiles();              // Tile[]
$entries  = $client->races($id)->leaderboard();     // RaceLeaderboard[]
```

`Collection` is used for *nested* lists inside a DTO, where the type has to be
carried because there is no return type to hang it on. Making top-level lists
return `Collection` as well is a consistency change on the
[roadmap](../roadmap.md#small-polish-items); it would be a breaking change, so it
is not done casually.

## Writing your own

`Collection` is generic, so a typed subclass is a constructor that hydrates each
element:

```php
namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\DTO\Tile;
use Kan\NkOpendata\Hydrator\Hydrator;

/** @extends Collection<int, Tile> */
final class TileCollection extends Collection
{
    /** @param array<int, array<string, mixed>> $items */
    public function __construct(array $items)
    {
        parent::__construct(array_map(
            fn (array $item): Tile => Hydrator::hydrate(Tile::class, $item),
            $items,
        ));
    }
}
```

Then declare the property as `public TileCollection $tiles` and the
[hydrator](hydration.md#type-rules) constructs it automatically, because it
detects the `Collection` subclass from the property type. No extra registration is
needed.

The `@extends` docblock is what gives PHPStan the element type, and it is also
what the [DTO reference generator](../tools/generate-dto-reference.php) reads to
print `TowerCollection<Tower>` rather than an untyped collection.

## See also

- [Data objects](dtos.md)
- [Hydration](hydration.md#type-rules)
- [`src/Collections/`](../../src/Collections)
