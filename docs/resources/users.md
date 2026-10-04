# Users

A player's public profile: identity, lifetime statistics, medals and badges.

The API has no user *list*. Players are addressed by an opaque hash, which you
obtain from a leaderboard entry or a profile URL, so this resource only has a
single `find()` method.

```php
$user = $client->users()->find('9fbc44d8dd97fbf64c11871e0e27e7249d5215bb9914de31');
```

Upstream: [`/btd6/users`](https://data.ninjakiwi.com/btd6/users)

## Methods

| Method | Upstream | Returns |
| --- | --- | --- |
| `find(string $id)` | `GET users/{id}` | `User` |

An unknown or malformed ID produces
`{"error":"Invalid user ID / Player Does not play this game","success":false}`,
which surfaces as [`ApiException`](../reference/errors.md). The same happens for
accounts that have never played BTD6.

## Identity

```php
printf(
    "%s — rank %d, veteran %d, %s followers\n",
    $user->name,
    $user->rank,
    $user->veteranRank,
    number_format($user->followers),
);

echo $user->mostExperiencedMonkey, PHP_EOL;  // "MonkeyBuccaneer"
echo $user->avatarUrl, PHP_EOL;
```

`$user->achievements` is typed `string` because the API sends it as a string, not
a number. Cast it yourself if you need arithmetic.

The cosmetic identifiers `$user->avatar` and `$user->banner` are names like
`ProfileAvatar03`; the matching `*Url` properties are fully qualified asset
URLs. Use the URLs directly and keep the identifiers for display or storage.

Fields: [User(../reference/dtos.md#user#user).

## From a leaderboard entry

Every leaderboard entry carries a `profile` URL. Its last path segment is the ID
this resource expects:

```php
$entries = $client->races($raceId)->leaderboard();
$top = $entries[0];

$user = $client->users()->find(basename(parse_url($top->profile, PHP_URL_PATH)));

echo $top->name, ' is ', $user->name, ' at rank ', $user->rank, PHP_EOL;
```

Worth wrapping in a helper, because the pattern repeats:

```php
function userIdFromProfileUrl(string $url): string
{
    return basename(parse_url($url, PHP_URL_PATH));
}
```

> [!NOTE]
> `$entry->name` comes from the leaderboard snapshot and `$user->name` comes
> from the live profile. They differ whenever a player has renamed since the
> submission, so use the entry name for historical accuracy and the profile name
> for the current one.

## Gameplay statistics

```php
$game = $user->gameplay;

printf("Games: %d played, %d won, highest round %d\n", $game->gameCount, $game->gamesWon, $game->highestRound);
printf("CHIMPS: %d, Deflation: %d\n", $game->highestRoundCHIMPS, $game->highestRoundDeflation);
printf("Cash earned: %s\n", number_format($game->cashEarned));
printf("Monkeys placed: %s\n", number_format($game->monkeysPlaced));
```

Fields: [Gameplay(../reference/dtos.md#gameplay#gameplay).

## Bloons popped

```php
$popped = $user->bloonsPopped;

printf("Total: %s\n", number_format($popped->bloonsPopped));
printf("Leaked: %s\n", number_format($popped->bloonsLeaked));
printf("ZOMGs:  %d, BFBs: %s, MOABs: %s\n", $popped->zomgsPopped, number_format($popped->bfbsPopped), number_format($popped->moabsPopped));
```

Note that `$bloonsPopped` is the *regular* bloon count; MOAB-class bloons are
counted in their own fields and are not included in it. Summing every field gives
a total closer to what the game displays.

Fields: [BloonsPopped(../reference/dtos.md#bloonspopped#bloonspopped).

## Tower and hero usage

`heroesPlaced` and `towersPlaced` are keyed by tower name, one counter each. They
also expose a `get()` that takes a [`Tower` enum(../reference/enums.md#tower#tower) case,
which is what you want in a loop:

```php
use Kan\NkOpendata\Enums\Tower;

$total = 0;
foreach (Tower::cases() as $tower) {
    try {
        $total += $user->heroesPlaced->get($tower);
    } catch (InvalidArgumentException) {
        // A primary monkey, not a hero.
    }
}

printf("Hero games: %s\n", number_format($total));
```

Direct property access works too, and uses PascalCase keys exactly as the API
sends them:

```php
echo $user->heroesPlaced->Quincy, PHP_EOL;      // int
echo $user->towersPlaced->DartMonkey, PHP_EOL;  // int
```

`get()` throws `InvalidArgumentException` when you pass a primary monkey to
`heroesPlaced` or a hero to `towersPlaced`, which is a cheap way to keep the two
apart.

> [!WARNING]
> The `Tower` enum does not cover every tower the API reports. `DanDMonke` and
> `Skywarden` are missing today, so a full `Tower::cases()` loop will not see
> their counters, and `Tower::DanDMonke` does not exist. See
> [roadmap](../roadmap.md#incomplete-tower-enum).

## Medals and badges

Medal and badge counts arrive as loosely typed maps, so they are
`array<string, mixed>` rather than DTOs:

```php
arsort($user->medalsSingleplayer);
foreach (array_slice($user->medalsSingleplayer, 0, 5, true) as $difficulty => $count) {
    printf("%-20s %d\n", $difficulty, $count);
}
```

| Property | Upstream key | Contents |
| --- | --- | --- |
| `$medalsSingleplayer` | `_medalsSinglePlayer` | Count per difficulty, e.g. `CHIMPS-BLACK`, `Easy`, `Deflation`. |
| `$medalsMultiplayer` | `_medalsMultiplayer` | Same, multiplayer. |
| `$medalsBossNormal` | `_medalsBoss` | Standard boss medals per tier. |
| `$medalsTeamElite` | `_medalsBossElite` | Elite boss medals per tier. |
| `$medalsCtLocal` | `_medalsCTLocal` | Local CT medals per tier. |
| `$medalsCtGlobal` | `_medalsCTGlobal` | Global CT medals per tier. |
| `$medalsRace` | `_medalsRace` | Race medals per tier. |
| `$bossBadgesNormal` | `bossBadgesNormal` | Badge count per boss, Standard. |
| `$bossBadgesElite` | `bossBadgesElite` | Badge count per boss, Elite. |
| `$stats` | `stats` | Miscellaneous, keys are API-defined and unstable. |

Upstream reshuffles medal and badge keys between game updates, so always read
them by key at runtime and never persist a fixed key list.

## Traversal: leaderboard to guild to owner

The three identity resources chain, because the API returns URLs:

```php
$team = $client->ct($ctId)->leaderboard()->team()[0];
$guild = $client->guild()->find(userIdFromProfileUrl($team->profile));
$owner = $client->users()->find(userIdFromProfileUrl($guild->owner));

printf("%s is owned by %s (rank %d)\n", $guild->name, $owner->name, $owner->rank);
```

> [!NOTE]
> `userIdFromProfileUrl` is named for users but works for any of these URLs. A
> dedicated `idFromUrl()` helper on the client is on the
> [roadmap](../roadmap.md#small-polish-items).

## See also

- [Getting started](../getting-started.md)
- [Guilds](guilds.md) — the other half of this traversal
- [Data objects(../reference/dtos.md)
- [`examples/player-lookup.php`](../examples/player-lookup.php)
