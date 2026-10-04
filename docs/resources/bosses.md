# Bosses

Rotating boss events on two difficulties, Standard and Elite. Each boss has its
own challenge document and its own leaderboards, split by team size.

```php
$bosses = $client->bosses();                        // collection
$boss   = $client->bosses('Dreadbloon38_mug8c17e'); // scoped to one event
```

Upstream: [`/btd6/bosses`](https://data.ninjakiwi.com/btd6/bosses)

## Methods

| Method | Upstream | Returns | Needs an ID |
| --- | --- | --- | --- |
| `list()` | `GET bosses` | `BossEvent[]` | no |
| `metadata()` | — | `BossMetadataResource` | **yes** |
| `leaderboard()` | — | `BossLeaderboardResource` | **yes** |

Both sub-resources make no request until you call a method on them.

## Events

```php
foreach ($client->bosses()->list() as $boss) {
    printf(
        "%-14s %-14s %s -> %s  %d/%d standard/elite\n",
        $boss->bossType,
        $boss->name,
        $boss->start->format('Y-m-d'),
        $boss->end->format('Y-m-d'),
        $boss->totalScoresStandard,
        $boss->totalScoresElite,
    );
}
```

Two scoring types are reported per event, one per difficulty:

```php
$boss = $client->bosses()->list()[0];

echo $boss->scoringTypeStandard;  // "GameTime" | "LeastCash" | "LeastTiers"
echo $boss->scoringTypeElite;     // e.g. "LeastTiers"
```

They are modelled as plain `string`s today. Upstream documents exactly three
values, so a `ScoringType` enum is on the [roadmap](../roadmap.md#small-polish-items).
The upstream `scoringType` field is marked deprecated and is not exposed.

Boss events are listed newest first, and `$boss->id` is the compound ID you pass
to the scoped methods, for example `Dreadbloon38_mug8c17e`. `$boss->bossType` is
the stable lowercase slug (`dreadbloon`, `bloonarius`, `vortex`, `lych`, …) and
is the better key if you want to recognise a boss across seasons.

Fields: [BossEvent(../reference/dtos.md#bossevent#bossevent).

## Leaderboards

```php
$lb = $client->bosses('Dreadbloon38_mug8c17e')->leaderboard();
```

| Method | Upstream | Returns |
| --- | --- | --- |
| `standard()` | — | `self`, selects the Standard difficulty |
| `elite()` | — | `self`, selects the Elite difficulty |
| `mode(string $mode)` | — | `self`, `'standard'` or `'elite'` |
| `singleplayer()` | `GET bosses/{id}/leaderboard/{mode}/1` | `BossLeaderboard[]` |
| `team(int $teamSize = 2)` | `GET bosses/{id}/leaderboard/{mode}/{teamSize}` | `BossLeaderboard[]` |

`mode()` defaults to `standard`, and `singleplayer()` is `team(1)`.

### Difficulty

The difficulty selectors mutate the resource and return it, so they read as a
chain and are safe to keep in a variable:

```php
$standard = $lb->standard()->singleplayer();
$elite    = $lb->elite()->team(3);
```

> [!WARNING]
> Because they mutate `$this`, a single `BossLeaderboardResource` instance only
> ever reflects the last difficulty you selected. If you need both at once,
> call `$client->bosses($id)->leaderboard()` twice, or keep the results of each
> call separately as above. Making the selectors immutable is on the
> [roadmap](../roadmap.md#small-polish-items).

### Team size

`team()` accepts 1 to 4 and throws `InvalidArgumentException` outside that
range. Upstream agrees — a fifth returns `{"error":"Invalid team size"}` — so the
check saves a request:

```php
$lb->team(5);   // InvalidArgumentException: Team size must be between 1 and 4
```

### Reading the results

Boss scores are **time** based, so lower is better, and the leaderboard returns
the top 25:

```php
foreach ($lb->standard()->team(2) as $rank => $entry) {
    printf("#%d %-24s %d\n", $rank + 1, $entry->name, $entry->score);
}
```

Each entry breaks the score down into components, which is the only reliable way
to tell whether a leaderboard is scored on time or on cash:

```php
$entry = $lb->standard()->singleplayer()[0];

printf("%s — %d\n", $entry->name, $entry->score);
foreach ($entry->scoreParts as $part) {
    printf("  %-14s %-6s %d\n", $part->name, $part->type, $part->score);
}
```

```
Thee Player — 5
  Boss Tier      number  5
  Least Cash     time    45833
  Game Time      time    82890000
```

`$entry->submissionTime` is `-1` when upstream does not expose it, which is the
common case for boss entries. Do not treat it as a timestamp without checking.

Fields: [BossLeaderboard(../reference/dtos.md#bossleaderboard#bossleaderboard),
[ScorePart(../reference/dtos.md#scorepart#scorepart).

## Challenge metadata

Boss events ship a challenge document per difficulty, and they can differ:

```php
$metadata = $client->bosses('Dreadbloon38_mug8c17e')->metadata()->standard();

printf("%s on %s, round %d, %d lives\n", $metadata->name, $metadata->map, $metadata->endRound, $metadata->lives);
```

| Method | Upstream | Returns |
| --- | --- | --- |
| `standard()` | `GET bosses/{id}/metadata/standard` | `BossMetadata` |
| `elite()` | `GET bosses/{id}/metadata/elite` | `BossMetadata` |

Both return the shared [Metadata(../reference/dtos.md#metadata#metadata) shape. Official
boss events report `id` as `"n/a"`, `createdAt` as the epoch, `gameVersion` as
`"0"` and `creator` as `null`.

> [!WARNING]
> This currently throws for live data: the API sends tower names the
> [`Tower` enum(../reference/enums.md#tower#tower) does not define, and the hydrator
> rejects unknown enum values. See
> [roadmap](../roadmap.md#blocked-metadata-cannot-be-hydrated) for the details
> and a workaround.

## See also

- [Getting started](../getting-started.md)
- [Data objects(../reference/dtos.md)
- [`examples/boss-leaderboards.php`](../examples/boss-leaderboards.php)
