# Races

Weekly race events. A race has one challenge, one leaderboard, and a scoring
type; every player plays the same map under the same rules.

```php
$races = $client->races();            // collection
$race  = $client->races('Party_Madness_muekt6x6'); // scoped to one event
```

Upstream: [`/btd6/races`](https://data.ninjakiwi.com/btd6/races)

## Methods

| Method | Upstream | Returns | Needs an ID |
| --- | --- | --- | --- |
| `list()` | `GET races` | `Race[]` | no |
| `leaderboard()` | `GET races/{id}/leaderboard` | `RaceLeaderboard[]` | **yes** |
| `metadata()` | `GET races/{id}/metadata` | `RaceMetadata` | **yes** |

## Events

```php
foreach ($client->races()->list() as $race) {
    printf(
        "%-28s %s -> %s  %d scores\n",
        $race->name,
        $race->start->format('Y-m-d'),
        $race->end->format('Y-m-d'),
        $race->totalScores,
    );
}
```

The API returns events newest first, so `list()[0]` is the current or most
recent race.

Fields: [Race(../reference/dtos.md#race#race).

## Leaderboard

```php
$race = $client->races()->list()[0];
$entries = $client->races($race->id)->leaderboard();

foreach ($entries as $rank => $entry) {
    printf("#%d %-24s %s\n", $rank + 1, $entry->name, format_ms($entry->score));
}
```

Races are scored on time, so a **lower** score is better and
`$entries[0]` is the winner. The leaderboard returns the top 50.

> [!IMPORTANT]
> This differs from CT, where a higher score is better. Check the scoring type
> before you sort.

### Score breakdown

Each entry carries a `scoreParts` collection explaining how the total was
computed. For races it is typically the round time plus a bonus for submitting
early:

```php
$entry = $client->races($race->id)->leaderboard()[0];

printf("%s — total %d\n", $entry->name, $entry->score);

foreach ($entry->scoreParts as $part) {
    printf("  %-28s %-6s %d\n", $part->name, $part->type, $part->score);
}
```

```
fixmemoryleak — total 129567
  Game Time                   time    129567
  Time after event start      time    200824000
```

`ScorePartCollection` is `IteratorAggregate` and `Countable`, so `count()` and
`foreach` both work; see [collections(../reference/collections.md).

Fields: [RaceLeaderboard(../reference/dtos.md#raceleaderboard#raceleaderboard),
[ScorePart(../reference/dtos.md#scorepart#scorepart).

### From a leaderboard entry to the player

`$entry->profile` is a full URL whose last segment is the user ID:

```php
$userId = basename(parse_url($entry->profile, PHP_URL_PATH));
$user = $client->users()->find($userId);
```

That helper pattern comes up on every leaderboard. See
[Users from a leaderboard entry](users.md#from-a-leaderboard-entry).

## Challenge metadata

```php
$metadata = $client->races($race->id)->metadata();

printf(
    "%s on %s, rounds %d-%d, %d lives, %d cash\n",
    $metadata->name,
    $metadata->map,
    $metadata->startRound,
    $metadata->endRound,
    $metadata->lives,
    $metadata->startingCash,
);
```

This is the same [Metadata(../reference/dtos.md#metadata#metadata) shape boss challenges use,
so most of the [contested territory](../getting-started.md#navigating-a-challenge)
discussion applies. Official race events report placeholder values:
`$metadata->id` is `"n/a"`, `$metadata->createdAt` is the epoch and
`$metadata->gameVersion` is `"0"`.

> [!WARNING]
> `metadata()` currently throws for live data because the
> [`Tower` enum(../reference/enums.md#tower#tower) is missing cases the API sends.
> See [roadmap](../roadmap.md#blocked-metadata-cannot-be-hydrated).

## See also

- [Getting started](../getting-started.md)
- [Data objects(../reference/dtos.md)
- [`examples/race-leaderboard.php`](../examples/race-leaderboard.php)
