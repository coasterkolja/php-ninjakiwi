# Enums

Two string-backed enums model the closed value sets the API uses. Both are
hydrated automatically from their raw string, so you compare against enum cases
instead of magic strings.

```php
use Kan\NkOpendata\Enums\Tower;
use Kan\NkOpendata\Enums\TileGameType;
```

## `Tower`

`Kan\NkOpendata\Enums\Tower` — every monkey, hero and upgrade in the game. Values
are the upstream camelCase names, so `Tower::DartMonkey->value === 'DartMonkey'`.

It is used in three places:

```php
$entry->tower;                                  // Tower, on DTO\Tower (challenge limits)
$user->heroesPlaced->get(Tower::Quincy);         // int, games with Quincy placed
$user->towersPlaced->get(Tower::DartMonkey);    // int, games with Dart Monkey placed
```

### Cases

**Primary** — `DartMonkey`, `BoomerangMonkey`, `BombShooter`, `TackShooter`,
`IceMonkey`, `GlueGunner`, `Desperado`

**Military** — `SniperMonkey`, `MonkeySub`, `MonkeyBuccaneer`, `MonkeyAce`,
`HeliPilot`, `MortarMonkey`, `DartlingGunner`

**Magic** — `WizardMonkey`, `SuperMonkey`, `NinjaMonkey`, `Alchemist`, `Druid`,
`Mermonkey`

**Support** — `BananaFarm`, `SpikeFactory`, `MonkeyVillage`, `EngineerMonkey`,
`BeastHandler`

**Heroes** — `ChosenPrimaryHero` (the challenge slot, not a real hero), `Quincy`,
`Gwendolin`, `StrikerJones`, `ObynGreenfoot`, `CaptainChurchill`, `Benjamin`,
`Ezili`, `PatFusty`, `Adora`, `AdmiralBrickell`, `Etienne`, `Sauda`, `Psi`,
`Geraldo`, `Corvus`, `Rosalia`, `Silas`

`ChosenPrimaryHero` is a challenge-configuration marker rather than a hero, so
expect it in [`Metadata::$towers`](dtos.md#metadata) with a `max` of `0`.

> [!WARNING]
> **This enum is not complete.** The live API reports 45 distinct towers; the
> enum defines 43. `DanDMonke` and `Skywarden` are missing, and because the
> [hydrator](hydration.md#failure-modes) rejects unknown enum values, that makes
> every `metadata()` call throw. It also means a full `Tower::cases()` loop over
> `heroesPlaced` silently skips those two heroes.
>
> Details and a workaround: [roadmap](../roadmap.md#incomplete-tower-enum).

### Distinguishing heroes from primaries

`$entry->isHero` on [DTO\Tower](dtos.md#tower) is the reliable way to tell them
apart. `heroesPlaced->get()` and `towersPlaced->get()` throw
`InvalidArgumentException` for the wrong category, which also works:

```php
use Kan\NkOpendata\Enums\Tower;

foreach (Tower::cases() as $tower) {
    try {
        $games = $user->towersPlaced->get($tower);
        printf("%-20s %d\n", $tower->value, $games);
    } catch (InvalidArgumentException) {
        // A hero, not a primary tower.
    }
}
```

### Matching against a string

When you have a value from elsewhere — a database row, a config file:

```php
$tower = Tower::tryFrom('DartMonkey');   // Tower::DartMonkey
$tower = Tower::tryFrom('nope');         // null
```

Use `tryFrom()` for untrusted input and `from()` only when a missing case is a
genuine bug you want to hear about.

## `TileGameType`

`Kan\NkOpendata\Enums\TileGameType` — the scoring mode a Contested Territory
tile belongs to. Values match upstream exactly.

| Case | Value | Meaning |
| --- | --- | --- |
| `LeastCash` | `LeastCash` | Tile scores on cash remaining. |
| `LeastTiers` | `LeastTiers` | Tile scores on tiers placed. |
| `Boss` | `Boss` | Tile is a boss encounter. |
| `Race` | `Race` | Tile is race-flavoured. |
| `TeamStart` | `TeamStart` | Tile is a team start position. |

```php
foreach ($tiles as $tile) {
    if ($tile->gameType === TileGameType::Boss) {
        printf("%s is a boss tile\n", $tile->id);
    }
}
```

Tallying by mode, the usual first step when rendering a map:

```php
$counts = [];
foreach ($tiles as $tile) {
    $counts[$tile->gameType->value] = ($counts[$tile->gameType->value] ?? 0) + 1;
}
```

```php
use Kan\NkOpendata\Enums\TileGameType;
```

> [!NOTE]
> This enum has a redundant `fromString()` static method that duplicates
> `from()` with a hand-written `match` over the same five values. It adds
> nothing and can drift out of sync when a case is added. Use the built-in
> `from()` and `tryFrom()` instead. Removing it is on the
> [roadmap](../roadmap.md#small-polish-items).

## Why enums, and what to watch for

Modelling these as enums turns a silent string comparison into an
`UnexpectedValueException` at hydration time, which is a good trade: a typo in
your own code fails immediately rather than quietly matching nothing.

The cost is that any value the API adds becomes a runtime failure. That is
exactly the [`Tower` problem](../roadmap.md#incomplete-tower-enum) above, and it
is the main argument for a lenient fallback on large or open-ended sets.

## See also

- [Data objects](dtos.md)
- [Hydration](hydration.md#type-rules)
- [`src/Enums/`](../../src/Enums)
