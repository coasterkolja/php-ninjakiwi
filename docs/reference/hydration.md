# Hydration

The hydrator turns a decoded JSON payload into [DTOs](dtos.md) by reflecting over
their constructors. Nothing is generated at build time and there is no
configuration: a DTO's constructor signature *is* the mapping.

```php
use Kan\NkOpendata\Hydrator\Hydrator;

$race = Hydrator::hydrate(Race::class, ['id' => '…', 'name' => '…', /* … */]);

$all = Hydrator::hydrateCollection(Race::class, [/* list of payloads */]);
```

You rarely call this directly — resources do it for you. It is documented because
you will need it when adding an endpoint.

## How a single object is built

For each constructor parameter, in declaration order:

1. The parameter name is the JSON key to look up. `#[MapFrom('…')]` overrides it.
2. The value is cast according to the declared PHP type.
3. The arguments are passed to `new $class(...)`.

```php
class CtEvent implements DTOInterface
{
    public function __construct(
        public string $id,                              // JSON: "id"
        public \DateTimeImmutable $start,               // JSON: "start"
        public int $totalScoresPlayer,                   // JSON: "totalScores_player"
        #[MapFrom('leaderboard_player')]
        public string $leaderboardPlayer,                // JSON: "leaderboard_player"
    ) {}
}
```

There is no name mangling, no `snake_case` to `camelCase` conversion, and no
per-field configuration. If the key differs from the property name, say so with
`MapFrom`.

## Type rules

| Declared type | Behaviour |
| --- | --- |
| `string`, `int`, `float`, `bool`, `mixed` | Passed through unchanged. The library does **not** coerce, so a string where an `int` is declared surfaces as a `TypeError` at construction. |
| `array` | Passed through as decoded, which means a JSON object becomes an associative array and a JSON list becomes a list. |
| `\DateTimeImmutable` | Built from an epoch **milliseconds** number. Anything non-numeric throws `UnexpectedValueException`. |
| A [backed enum](enums.md) | `tryFrom()` on the raw value. An unrecognised value throws `UnexpectedValueException`. |
| A [Collection](collections.md) subclass | Constructed from the decoded value, which hydrates each element. |
| Another DTO | Hydrated recursively from the nested object. |

Recursion is what makes a `User` usable: the nested `bloonsPopped`, `gameplay`,
`heroesPlaced` and `towersPlaced` objects each become their own DTO without any
extra wiring.

## `MapFrom`

`#[MapFrom('field')]` renames a constructor parameter.

```php
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class User implements DTOInterface
{
    public function __construct(
        #[MapFrom('displayName')]
        public string $name,

        #[MapFrom('avatarURL')]
        public string $avatarUrl,
    ) {}
}
```

Two conventions make the upstream naming survive: `displayName` becomes `$name`,
and the API's shouty `URL` suffixes become idiomatic `$avatarUrl`.

Most renames are mechanical and follow from upstream's own inconsistency —
`totalScores_player`, `leaderboard_standard_players_1`, `_medalsCTLocal`. The
[generated DTO reference](dtos.md) shows the `JSON field` column for every
property, so you can always see what a property maps from.

## `CastWith`

`#[CastWith(CastClass::class)]` hands the raw value to a custom caster, bypassing
the type rules. It is how a non-standard payload shape is handled.

```php
namespace Kan\NkOpendata\Hydrator\Casts;

interface Cast
{
    public function cast(mixed $value): mixed;
}
```

A caster is constructed with no arguments for each field it handles:

```php
use Kan\NkOpendata\Hydrator\Attributes\CastWith;

class DurationCast implements Cast
{
    public function cast(mixed $value): \DateTimeImmutable
    {
        // The API sends "1:23.45" here, not a timestamp.
        [$m, $s] = explode(':', $value);

        return (new \DateTimeImmutable())
            ->setDate(1970, 1, 1)
            ->setTime((int) $m, (int) round(((float) $s) * 60));
    }
}

class RaceResult implements DTOInterface
{
    public function __construct(
        #[CastWith(DurationCast::class)]
        public \DateTimeImmutable $duration,
    ) {}
}
```

> [!WARNING]
> No DTO in `src/DTO` uses `CastWith`, and it has no test coverage yet. The
> hydrator implements it, but treat the snippet above as the intended shape
> rather than a proven pattern. A test belongs in `tests/Unit/HydratorTest.php`
> alongside `MapFrom` coverage.

## Failure modes

| Condition | Exception |
| --- | --- |
| Required field absent from the payload | `InvalidArgumentException: Missing field: <name>` |
| Optional field absent, no default available | `InvalidArgumentException` |
| Optional field absent, default available | Property takes its default |
| Timestamp field is not a number | `UnexpectedValueException` |
| Enum value not in the enum | `UnexpectedValueException: Invalid enum value: <value>` |
| Non-backed enum used as a field type | `UnexpectedValueException` |
| Value does not fit the declared type | PHP `TypeError` |
| Payload is not an array | PHP `TypeError` on the parameter |

### Fields the payload has but the DTO does not

Silently ignored. This is deliberate and is why adding a field upstream does not
break your code — it only means the new data is not exposed yet.

```php
// The API sends `seasonBadges`, which `User` does not declare.
// Nothing throws; there is simply no property to read it from.
```

### Fields the DTO declares but the payload lacks

This throws, unless the property is optional and has a default. The one place
this bites in practice is [additive upstream data and closed
enums(../roadmap.md#incomplete-tower-enum#incomplete-tower-enum): a new tower name reaches the `Tower`
enum, not the DTO, and an unknown enum value is a hard failure.

## Adding a DTO

1. Create the class in `src/DTO`, implement `DTOInterface`, use promoted public
   properties.
2. Add `#[MapFrom('…')]` wherever the JSON key differs from the property name.
3. Prefer `DateTimeImmutable`, an enum or a nested DTO over a bare `string` or
   `array` where the shape is known.
4. Give anything genuinely optional a `?type = null` default.
5. Register it by returning it from a resource method.
6. Regenerate the reference:

   ```bash
   php docs/tools/generate-dto-reference.php
   ```

7. Add a description for each new field to `$descriptions` in the generator, and
   a test fixture in `tests/Unit/HydratorTest.php`.

Step 6 is not optional: the generated page will show blank descriptions for fields
you did not register there.

## See also

- [Data objects](dtos.md) — generated reference
- [Collections](collections.md)
- [Enums](enums.md)
- [`src/Hydrator/Hydrator.php`](../../src/Hydrator/Hydrator.php)
