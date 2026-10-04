# PHP Ninjakiwi Open Data API

This Library is a PHP client for Ninjakiwi's Open Data API.
[https://data.ninjakiwi.com/](https://data.ninjakiwi.com/)

It is by no means complete but I'm working on finishing the BloonsTD6 Endpoints.

Currently supported Resources are: 
- Races
- Contested Territory
- Bosses
- Users
- Guilds

## Installation
```
composer require coasterkolja/nk-opendata
```

## Usage

Create a client:
```php
$client = new Kan\NkOpendata\Client();
```

Use a client:
```php
$client->users();
$client->guild();
$client->bosses();
$client->ct();
$client->races();
```

Each of these methods returns a corresponding Resource e.g. `UserResource` or `CtResource`.

From the Resources you can fetch the data you need:
```php
$client->ct($id)->tiles();
```

Get the most recent Event or the currently running Event:
```php
$client->ct()->current(); // Throws an Exception if there is no active event.
$client->ct()->recent();
```

## Schemas

Every endpoint returns typed data objects instead of associative arrays, and the
full field-by-field reference is generated from the DTO classes themselves:

**[docs/reference/dtos.md](docs/reference/dtos.md)**

In short: timestamps arrive as `DateTimeImmutable`, strings with a fixed value
set are enums, and nested objects become nested objects.

---

## Overview and quickstart

Everything below is the short version. The complete documentation lives in
[`docs/`](docs/), starting at [`docs/README.md`](docs/README.md).

### Requirements

PHP 8.3 or newer, because `Collection::count()` uses the `#[\Override]` attribute
introduced in 8.3. Note that `composer.json` currently declares no `php`
constraint at all, so Composer will not enforce this for you — see the
[roadmap](docs/roadmap.md#packaging-and-repository-hygiene).

No API key and no authentication. Every endpoint is public.

Needs the bundled `json` extension only. The example scripts also use
`mb_substr()` to trim long player names, so they want `mbstring` enabled.

### Three steps

**1. Create a client.** It holds the base URL and does no I/O, so this is free.

```php
require __DIR__ . '/vendor/autoload.php';

use Kan\NkOpendata\Client;

$client = new Client();
```

**2. Pick a resource.** Every factory method returns a thin handle over an
optional ID, and building one sends no request.

```php
$client->races();             // all races
$client->races($raceId);      // scoped to one race
$client->users()->find($id);  // users have no list endpoint, only find()
```

**3. Call it.**

```php
$race = $client->races()->list()[0];      // newest first
$entries = $client->races($race->id)->leaderboard();

echo $race->name, PHP_EOL;                 // "Party Madness"

foreach ($entries as $rank => $entry) {
    printf("  #%-3d  %-24s  %d\n", $rank + 1, mb_substr($entry->name, 0, 24), $entry->score);
}
```

That prints:

```
Party Madness
  #1    fixmemoryleak             129567
  #2    ISAB                      133000
  #3    FR0ST                     133320
```

Real output from `docs/examples/race-leaderboard.php`; scores are a live
leaderboard, so the numbers move. The full 50-entry list runs several thousand
places deep, which is worth knowing before you assume you got everything.

### What the data looks like

Responses are plain PHP objects with public constructor properties:

```php
$event = $client->ct()->current();

$event->id;                  // "muekp4st"      — string
$event->end;                 // DateTimeImmutable
$event->totalScoresPlayer;   // 0               — int, not "0"
$event->leaderboardTeam;     // absolute URL    — string
```

Timestamps are converted from the API's epoch milliseconds, and enums such as a
tile's game type arrive as real enum cases, so you compare against
`TileGameType::Boss` rather than the string `'Boss'`.

### Worked examples

```php
// The current CT event, its tiles, and a tally per scoring mode.
$event = $client->ct()->current();

foreach ($client->ct($event->id)->tiles() as $tile) {
    printf("  %-4s  %-32s  %s\n", $tile->id, $tile->type, $tile->gameType->value);
}
```

```php
// Why a leaderboard score is what it is.
$entry = $client->races($race->id)->leaderboard()[0];

foreach ($entry->scoreParts as $part) {
    printf("  %-28s %d\n", $part->name, $part->score);
}
```

```php
// From a leaderboard entry to the full player profile. Every identity payload
// carries a URL whose last path segment is the ID of the linked resource.
$user = $client->users()->find(
    basename(parse_url($entry->profile, PHP_URL_PATH)),
);

echo $user->name, ' — rank ', $user->rank, PHP_EOL;
```

Five complete, runnable versions of these live in
[`docs/examples/`](docs/examples/) and hit the live API:

```bash
php docs/examples/current-ct-event.php
php docs/examples/race-leaderboard.php
php docs/examples/boss-leaderboards.php
php docs/examples/player-lookup.php
php docs/examples/ct-to-guild.php
```

### Error handling

Every failure throws. Nothing returns `null` to signal a problem.

```php
use Kan\NkOpendata\Exceptions\ApiException;

try {
    $players = $client->ct($id)->leaderboard()->player();
} catch (ApiException) {
    // Leaderboards are empty for a few hours after an event opens, so this is
    // an expected condition rather than a failure.
    $players = [];
}
```

`ApiException` covers upstream and transport failures. `LogicException` means you
called a method that needs an ID without one, which is a bug in your code and
should not be swallowed. See [docs/reference/errors.md](docs/reference/errors.md).

### Two things to know before you rely on it

- `metadata()` is currently broken against live data, because the
  [`Tower` enum](docs/reference/enums.md#tower) is missing cases the API sends.
  Details and a workaround: [roadmap](docs/roadmap.md#incomplete-tower-enum).
- `ApiException` always says `Api call failed`, discarding the upstream reason.
  It is on the [roadmap](docs/roadmap.md#upstream-error-messages-are-discarded).

Both are covered in full, along with everything else that is not implemented
yet, in [docs/roadmap.md](docs/roadmap.md).

### Documentation map

| Page | What it covers |
| --- | --- |
| [Getting started](docs/getting-started.md) | Requirements, the first calls, error handling, testing your own code. |
| [Races](docs/resources/races.md) · [Contested Territory](docs/resources/contested-territory.md) · [Bosses](docs/resources/bosses.md) · [Users](docs/resources/users.md) · [Guilds](docs/resources/guilds.md) | One page per endpoint group. |
| [Client](docs/reference/client.md) | Base URL, resource factories, current limitations. |
| [Data objects](docs/reference/dtos.md) | Every DTO, field by field. Generated from the code. |
| [Hydration](docs/reference/hydration.md) | How JSON becomes DTOs: `MapFrom`, `CastWith`, type rules. |
| [Collections](docs/reference/collections.md) · [Enums](docs/reference/enums.md) · [Errors](docs/reference/errors.md) · [Testing](docs/reference/testing.md) | The remaining mechanisms. |
| [Examples](docs/examples/) | Runnable scripts. |
| [Roadmap](docs/roadmap.md) | Known gaps and planned API. |
| [Conventions](docs/conventions.md) | How to keep the docs correct. |
