# Contested Territory

Weekly Contested Territory events: a rotating tile map, where each tile belongs
to a scoring mode, and everyone plays the same map.

```php
$ct = $client->ct();          // collection
$ct = $client->ct('mtv62zza'); // scoped to one event
```

Upstream: [`/btd6/ct`](https://data.ninjakiwi.com/btd6/ct)

## Methods

| Method | Upstream | Returns | Needs an ID |
| --- | --- | --- | --- |
| `list()` | `GET ct` | `CtEvent[]` | no |
| `recent()` | `GET ct` | `CtEvent` | no |
| `current()` | `GET ct` | `CtEvent` | no |
| `find(string $id)` | — | `CtResource` | no |
| `tiles()` | `GET ct/{id}/tiles` | `Tile[]` | **yes** |
| `leaderboard()` | — | `CtLeaderboardResource` | **yes** |

`list()`, `recent()` and `current()` each cost one request; `recent()` and
`current()` are not cached, so calling both fetches the list twice.

## Events

```php
$events = $client->ct()->list();      // newest first, by start time

foreach ($events as $event) {
    printf(
        "%s  %s -> %s  %d player / %d team scores\n",
        $event->id,
        $event->start->format('Y-m-d'),
        $event->end->format('Y-m-d'),
        $event->totalScoresPlayer,
        $event->totalScoresTeam,
    );
}
```

The API returns events newest first, so index `0` is the most recent. A CT event
has no `name` field; the ID is the only identifier.

Fields: [CtEvent(../reference/dtos.md#ctevent#ctevent).

## The running event

```php
$event = $client->ct()->current();

$remaining = $event->end->getTimestamp() - time();
printf("%s ends in %dh %dm\n", $event->id, intdiv($remaining, 3600), intdiv($remaining % 3600, 60));
```

`current()` takes the newest event and throws `ApiException` if it has already
ended. `recent()` is the same lookup without the check, which is what you want
when reading historical data.

Timestamps are `DateTimeImmutable`, so `$event->end < new DateTimeImmutable()`
works directly if you prefer that to arithmetic.

## Tiles

```php
$tiles = $client->ct('mtv62zza')->tiles();

foreach ($tiles as $tile) {
    printf("%-4s %-32s %s\n", $tile->id, $tile->type, $tile->gameType->value);
}
```

```
MRX  Banner                         LeastCash
DAG  Regular                        LeastCash
DAB  TeamFirstCapture              LeastTiers
DAA  TeamStart                      TeamStart
AAG  Banner                         Boss
AAF  Relic - GoingTheDistance       Boss
```

- `$tile->id` is the three-letter code printed on the map, so it is what you use
  to correlate a tile with an external map image.
- `$tile->type` is the visual variant and is open-ended: `Regular`, `Banner`,
  `TeamStart`, `TeamFirstCapture`, and one `Relic - <Name>` entry per relic.
- `$tile->gameType` is a closed set and is modelled as the
  [`TileGameType` enum(../reference/enums.md#tilegametype#tilegametype).

Counting tiles per scoring mode is the usual first step when rendering a map:

```php
$counts = [];
foreach ($tiles as $tile) {
    $counts[$tile->gameType->value] = ($counts[$tile->gameType->value] ?? 0) + 1;
}

print_r($counts);
// [ 'LeastTiers' => 61, 'LeastCash' => 61, 'Race' => 31, 'Boss' => 10, 'TeamStart' => 6 ]
```

A map is around 170 tiles, so this is a sizeable response. Cache it if you render
many events.

The API wraps the list in a `tiles` key, which the resource unwraps before
hydrating. Fields: [Tile(../reference/dtos.md#tile#tile).

## Leaderboards

`leaderboard()` returns a sub-resource; it makes no request of its own.

```php
$ct = $client->ct('mtv62zza');

$players = $ct->leaderboard()->player();
$teams   = $ct->leaderboard()->team();
```

| Method | Upstream | Returns |
| --- | --- | --- |
| `player()` | `GET ct/{id}/leaderboard/player` | `CtLeaderboardPlayer[]` |
| `team()` | `GET ct/{id}/leaderboard/team` | `CtLeaderboardTeam[]` |
| `group(string $groupId)` | `GET ct/{id}/leaderboard/group/{groupId}` | `CtLeaderboardGroup[]` |

### Players

```php
foreach ($ct->leaderboard()->player() as $i => $entry) {
    printf("#%d %-24s %d\n", $i + 1, $entry->name, $entry->score);
}
```

### Teams and groups

A team is backed by a **guild**, and belongs to a group. Both arrive as URLs,
so you can walk the whole graph without knowing any IDs up front:

```php
$team = $ct->leaderboard()->team()[0];

$guild = $client->guild()->find(basename(parse_url($team->profile, PHP_URL_PATH)));
echo $guild->name, ' — ', $guild->numMembers, " members\n";

$groupId = basename(parse_url($team->group, PHP_URL_PATH));
foreach ($ct->leaderboard()->group($groupId) as $entry) {
    printf("  %-24s %d\n", $entry->name, $entry->score);
}
```

Chaining all the way to the guild owner:

```php
$owner = $client->users()->find(basename(parse_url($guild->owner, PHP_URL_PATH)));
echo $owner->name, PHP_EOL;
```

> [!NOTE]
> `group()` is implemented, but the upstream group endpoint only serves data
> while group data is live. For an archived event it answers
> `{"error":"No Group Available","success":false}`, which surfaces as
> `ApiException: Api call failed`. Wrap it if you read old events.
> See [errors](../reference/errors.md).

Fields: [CtLeaderboardPlayer(../reference/dtos.md#ctleaderboardplayer#ctleaderboardplayer),
[CtLeaderboardTeam(../reference/dtos.md#ctleaderboardteam#ctleaderboardteam),
[CtLeaderboardGroup(../reference/dtos.md#ctleaderboardgroup#ctleaderboardgroup).

## Leaderboards are empty right after an event opens

A brand new event reports `totalScores_player: 0` and its leaderboard endpoints
answer `{"error":"No Scores Available","success":false}`:

```php
use Kan\NkOpendata\Exceptions\ApiException;

try {
    $players = $ct->leaderboard()->player();
} catch (ApiException) {
    $players = [];   // Event just started, or was archived.
}
```

This is the most common `ApiException` in normal use, so handle it explicitly
rather than letting it abort a job.

## See also

- [Getting started](../getting-started.md)
- [Data objects(../reference/dtos.md)
- [Errors](../reference/errors.md)
- [`examples/current-ct-event.php`](../examples/current-ct-event.php)
