# Getting started

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | **8.3** or newer, see below. |
| Extensions | `json` (bundled), plus `mbstring` if you want to run the [examples](examples), which use `mb_substr()` to trim player names. The library itself never calls `mb_*`. |
| Dependencies | [`guzzlehttp/guzzle`](https://docs.guzzlephp.org/) `^7.10`, pulled in by Composer. |

No API key, no authentication, no rate-limit token. Every endpoint is public.

### Why 8.3

`Collection::count()` is annotated `#[\Override]`, a built-in attribute added in
PHP 8.3. On 8.2 and older that name does not exist, so the file fails to load
rather than degrading gracefully. Enums, `MatchExpression` and promoted
constructor properties would have allowed 8.1; the `Override` attribute is the
binding constraint.

> [!WARNING]
> `composer.json` declares no `"php"` constraint, so Composer will happily
> install this on PHP 8.0 and the failure appears at runtime. Adding
> `"php": "^8.3"` is on the
> [roadmap](roadmap.md#packaging-and-repository-hygiene). Pin it yourself until
> then.

## Installation

```bash
composer require coasterkolja/nk-opendata
```

> [!NOTE]
> The package has gone by three names: `kan/nk-opendata` in `composer.json`,
> `coasterkolja/nk-opendata` in the README, and a `coasterkolja/php-ninjakiwi`
> Git remote. `composer require` needs whichever name Packagist actually
> serves, which is not settled before the first tagged release. See the
> [roadmap](roadmap.md#packaging-and-repository-hygiene).

## The first call

Everything starts with a `Client`. It holds the base URL and nothing else, so
constructing one is free and does no I/O.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Kan\NkOpendata\Client;

$client = new Client();
```

The namespace is `Kan\NkOpendata`, with a lower-case `k` in `Nk`. PHP class and
namespace names are case-insensitive at runtime, but Composer's PSR-4 autoloader
is case-sensitive on most filesystems, so copy it exactly.

## Resources are cheap handles, not clients

Each factory method on the client returns a **resource**, which is a thin
immutable cursor over an optional ID. Calling one does not send a request:

```php
$ct = $client->ct('muekp4st');   // no request yet
$tiles = $ct->tiles();           // one GET, now
```

The optional ID argument scopes the resource. Without an ID you get the
collection-level methods; with one you also get the sub-resources and
single-item methods.

```php
$client->ct()->list();     // all CT events
$client->ct('muekp4st');   // scoped to one event
```

Most resources require an ID for anything below the list level and throw
`LogicException` otherwise. See [errors](reference/errors.md).

A few resources take a mandatory ID as part of the call instead, because there is
nothing to list:

```php
$client->users()->find('9fbc…');   // GET users/{id}
$client->guild()->find('ABC');     // GET guild/{id}
```

## Three calls that cover most use cases

### 1. The current Contested Territory event

```php
$event = $client->ct()->current();

echo $event->id;                       // "muekp4st"
echo $event->end->format('Y-m-d H:i'); // DateTimeImmutable, not a raw timestamp
echo $event->totalScoresPlayer;        // int, already cast
```

`current()` throws `ApiException` when no event is running, which is the
expected case between seasons. `recent()` is the same lookup without the
liveness check.

See [Contested Territory](resources/contested-territory.md).

### 2. A race and its leaderboard

```php
$race = $client->races()->list()[0];   // newest first

$entries = $client->races($race->id)->leaderboard();

foreach ($entries as $entry) {
    echo $entry->name, ' ', $entry->score, PHP_EOL;

    // scoreParts is a Collection: iterable and countable.
    foreach ($entry->scoreParts as $part) {
        printf("  %-24s %d\n", $part->name, $part->score);
    }
}
```

See [Races](resources/races.md).

### 3. A player profile

The API identifies players by an opaque hash, which you get from a leaderboard
entry or a profile URL.

```php
$entries = $client->races($race->id)->leaderboard();
$top = $entries[0];

$user = $client->users()->find(basename(parse_url($top->profile, PHP_URL_PATH)));

echo $user->name, ' — rank ', $user->rank, PHP_EOL;
echo $user->gameplay->highestRound, PHP_EOL;
echo $user->towersPlaced->get(Tower::DartMonkey), PHP_EOL;
```

```php
use Kan\NkOpendata\Enums\Tower;
```

The helper `basename(parse_url(…, PHP_URL_PATH))` is worth wrapping in your own
utility. [Users from a leaderboard](resources/users.md#from-a-leaderboard-entry)
covers this in more detail.

## Navigating a challenge

Boss and race events each point at a *challenge document* describing the actual
game: map, rules, restrictions, bloon modifiers.

```php
$metadata = $client->bosses($bossId)->metadata()->standard();

echo $metadata->map;                     // "Mesa"
echo $metadata->lives;                   // 200
echo $metadata->endRound;                // 140
echo $metadata->bloonModifiers->speedMultiplier;   // 1.0
```

`towers` is a [`TowerCollection`](reference/collections.md) of per-tower limits,
which is the most useful part for rule-aware tooling:

```php
foreach ($metadata->towers as $entry) {
    if ($entry->max > 0 && $entry->max < 99) {
        printf("%-20s max %d\n", $entry->tower->value, $entry->max);
    }
}
```

> [!WARNING]
> This currently throws for live data. The `Tower` enum is missing cases the API
> sends, so hydration fails. See [roadmap](roadmap.md#blocked-metadata-cannot-be-hydrated).

## Error handling

The library funnels every failure into a small, predictable set of exceptions.
Nothing returns `null` or `false` to signal a problem.

| Situation | Exception |
| --- | --- |
| Upstream returned `success: false`, or the body was not JSON | [`ApiException`](reference/errors.md#apiexception) |
| Connection failed, timed out, non-2xx | [`ApiException`](reference/errors.md#apiexception) |
| A required field was missing from the payload | `InvalidArgumentException` |
| An enum field held an unrecognised value | `UnexpectedValueException` |
| A method needing an ID was called without one | `LogicException` |
| An argument was out of range, e.g. a boss team of 5 | `InvalidArgumentException` |

Because they all extend SPL exceptions, a single catch is a reasonable default:

```php
use Kan\NkOpendata\Exceptions\ApiException;

try {
    $entries = $client->ct($id)->leaderboard()->player();
} catch (ApiException $e) {
    // Upstream refused or was unreachable. Leaderboards are empty for a few
    // hours after an event opens, which is the common case here.
    $entries = [];
} catch (LogicException $e) {
    // Programming error: you called a scoped method without an ID.
    throw $e;
}
```

The distinction matters: `ApiException` is a data condition you may want to
tolerate, while `LogicException` is a bug in the calling code and should not be
swallowed. [Errors](reference/errors.md) covers each case.

## Running the examples

Every snippet in these docs also exists as a runnable script:

```bash
php docs/examples/current-ct-event.php
php docs/examples/race-leaderboard.php
php docs/examples/challenge-restrictions.php
```

They hit the live public API, so they need network access. See the
[examples index](examples/) for the full list.

## Testing your own code

The resources depend on `HttpClient`, not on Guzzle directly, so you can stub
the transport and test without network access. [Testing](reference/testing.md)
has the recipe:

```php
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Kan\NkOpendata\Http\HttpClient;

$mock = new MockHandler([
    new Response(200, [], json_encode([
        'success' => true,
        'body' => [/* ... */],
    ])),
]);

$http = new HttpClient('https://data.ninjakiwi.com/btd6/');
(new ReflectionProperty(HttpClient::class, 'client'))
    ->setValue($http, new GuzzleHttp\Client(['handler' => GuzzleHttp\HandlerStack::create($mock)]));

$client = new Client($http);
```

Note that last line does not compile today: `Client` currently builds its own
`HttpClient` and has no constructor overload for injection. [Testing](reference/testing.md#status)
explains the current workaround and the planned signature.

## Where to go next

- [Client reference](reference/client.md) — base URL and the factory methods.
- [Data objects](reference/dtos.md) — every field, generated from the code.
- [Hydration](reference/hydration.md) — how the JSON mapping rules work.
- [Roadmap](roadmap.md) — what is not implemented yet, clearly marked.
