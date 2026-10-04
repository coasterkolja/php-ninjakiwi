# Roadmap

Everything below was checked against the live API on **28 September 2026** with
the library at commit `d994b40`. Two of these items are not hypothetical: they
break documented, working-looking calls against current data.

Status vocabulary used throughout these docs:

| Marker | Meaning |
| --- | --- |
| *(none)* | Implemented today. |
| **Planned** | Does not exist yet. Documented so the intended shape is agreed before code is written. |

## Blocked: metadata cannot be hydrated

**Severity: high. Breaks a documented feature.**

`bosses()->metadata()` and `races()->metadata()` throw on every call against
live data:

```
UnexpectedValueException: Invalid enum value: DanDMonke
```

The cause is [the incomplete `Tower` enum](#incomplete-tower-enum), not the
metadata resources themselves. The unit suite passes because its fixtures only
contain towers that were already in the enum, so this slipped through.

Workaround until it is fixed — read the endpoint yourself and decode the fields
you need:

```php
$body = json_decode(
    (new GuzzleHttp\Client())->get("https://data.ninjakiwi.com/btd6/races/{$id}/metadata")
        ->getBody()->getContents(),
    true,
)['body'];

echo $body['map'], ' ', $body['lives'], PHP_EOL;
```

## Incomplete `Tower` enum

**Severity: high. Root cause of the item above.**

The live API reports **45** distinct towers in `_towers` and
`heroesPlaced`/`towersPlaced`. `Enums\Tower` defines **43**. Missing:

| Value | Kind | Appears in |
| --- | --- | --- |
| `DanDMonke` | Hero | `_towers`, `heroesPlaced` |
| `Skywarden` | Hero | `_towers`, `towersPlaced` |

Two separate consequences:

1. **Hydration fails.** The hydrator calls `tryFrom()` and throws on an unknown
   value, so any payload containing a new tower is rejected. See
   [hydration failure modes](reference/hydration.md#failure-modes).
2. **Data is silently incomplete.** Even where hydration succeeds, a
   `Tower::cases()` loop cannot see these two, so per-tower statistics are
   understated.

Every new hero or monkey is a release-time landmine. Three options, in order of
preference:

| Option | Trade-off |
| --- | --- |
| **A.** Add the cases, and add a fixture from live data to the test suite | Cheap, and keeps the strong typing. Still breaks on the next new hero. |
| **B.** A plus a lenient fallback case (`case Unknown = ''`) used when `tryFrom()` returns `null` | Survives new content. Costs a `Unknown` case to check everywhere, and hides genuine typos. |
| **C.** Make the hydrator lenient for enums, keeping strictness as opt-in | Most robust, but weakens a guarantee the library currently gives for *all* enums. |

Recommend **B**: add the cases now, add a case for the unknown value, and keep
throwing for enums that are genuinely closed (such as
[`TileGameType`](reference/enums.md#tilegametype)) by simply not adding an
`Unknown` case there.

Whichever is chosen, the test fixture is the real fix: add a `_towers` payload
captured from the live API to `tests/Unit/HydratorTest.php` and assert on it.

### Related: `TileGameType::fromString()`

`Enums\TileGameType` has a hand-written `fromString()` that duplicates the
built-in `from()` with a `match` over the same five values. It is unused, adds
nothing, and will drift the next time a case is added. Delete it.

## No way to inject or configure the HTTP client

**Severity: medium. Affects every consumer.**

`Client` constructs its own `HttpClient` and only accepts a base URL:

```php
public function __construct(string $baseUri = 'https://data.ninjakiwi.com/btd6/')
```

`HttpClient` in turn hardcodes a Guzzle config with only `base_uri`. So it is not
possible to set a timeout, a retry policy, a proxy, a middleware stack or a
mock handler. Testing against a real `Client` requires reflection, as
[testing](reference/testing.md#status) shows.

Planned:

```php
public function __construct(
    ?HttpClient $http = null,
    ?string $baseUri = null,
) {
    $this->http = $http ?? new HttpClient($baseUri ?? 'https://data.ninjakiwi.com/btd6/');
}
```

The argument order keeps `new Client('https://…')` working, so this is not a
breaking change for callers who only set a base URL.

And on `HttpClient`:

```php
public function __construct(string $baseUrl, array $options = [])
{
    $this->client = new Client($options + ['base_uri' => $baseUrl]);
}
```

which is enough for a timeout (`['timeout' => 5.0]`) and, combined with a
handler, for tests.

## Upstream error messages are discarded

**Severity: medium. Hurts everyone in production.**

When the API answers `success: false`, the useful reason is thrown away:

```json
{ "error": "No Scores Available", "success": false }
```

```php
ApiException: Api call failed
```

Every failure looks identical, so a CT leaderboard that is simply empty for the
first few hours of an event is indistinguishable from an outage. In a Laravel app
this means every API failure logs the same useless line.

Planned: carry the upstream string and status on the exception.

```php
class ApiException extends \Exception
{
    public function __construct(
        string $message,
        private readonly ?string $apiError = null,
        private readonly ?int $status = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
```

Keeping `getMessage()` as `'Api call failed'` preserves any existing catch
behaviour, while `getApiError()` exposes the reason. Worth pairing with a set of
named exception subclasses — `NoScoresAvailableException`,
`InvalidIdException` — so callers can branch without string matching.

## Missing convenience: current and recent for every event type

**Severity: low. Asymmetry is a papercut.**

Only [`CtResource`](resources/contested-territory.md#the-running-event) has
`current()` and `recent()`. `RaceResource` and `BossResource` have `list()` only,
so reaching for the newest event means indexing and remembering that the API
returns them newest first:

```php
$race = $client->races()->list()[0];   // works, but unguarded
```

Planned, on both:

```php
public function recent(): Race;
public function current(): Race;   // throws ApiException when not running
```

Both should be factored into the `RequiresId` trait's sibling, a
`HasTimeline` concern, so the three resources cannot drift apart again.

Related: `current()` and `recent()` each fetch the whole list, and
[`list()[0]` is unguarded](resources/contested-territory.md#leaderboards-are-empty-right-after-an-event-opens)
— an empty list produces a PHP warning and a `TypeError` rather than an
`ApiException`. Guard it.

## No pagination

**Severity: low today, will grow.**

The response envelope carries `next` and `prev` cursors and the `HttpClient`
discards them. List endpoints currently fit in one page, so nothing is truncated
— but a library that cannot follow a cursor will silently under-report the day
NK ships a longer history.

Planned: keep the cursors on the client, and return a
[`Collection`](reference/collections.md) that knows how to fetch the next page.

## Small polish items

Collected here rather than given their own sections, because each is small and
none is broken.

| Item | Detail |
| --- | --- |
| `ScoringType` enum | [`BossEvent::$scoringTypeStandard`](reference/dtos.md#bossevent) and `$scoringTypeElite` are `string`s, but upstream documents exactly three values: `LeastCash`, `GameTime`, `LeastTiers`. An enum would match how `TileGameType` is already handled. |
| Immutable difficulty selectors | [`standard()`, `elite()` and `mode()`](resources/bosses.md#difficulty) mutate `BossLeaderboardResource`, so one instance only ever holds the last difficulty selected. Return `new self(...)` instead. |
| `idFromUrl()` helper | Every resource page repeats `basename(parse_url($url, PHP_URL_PATH))`. A single helper on the client, or a `Url` value object, would remove it from user code. |
| `find()` on every resource | Only `CtResource` has a collection-to-instance `find()`; `BossResource` and `RaceResource` rely on the constructor argument alone. Make `find()` uniform. |
| Collections for top-level lists | Resource methods return plain `array`, while nested lists use `Collection`. Returning `Collection` everywhere would be a breaking change, so it needs a major version. |
| `Guild::$status` | Reported verbatim and undocumented upstream. A `GuildStatus` enum is possible but risky; document the observed values instead. |
| `Guild` CT badges | The payload includes a `CtBadges` map that no DTO models. Add a `GuildBadge` collection if it proves useful. |
| `User::$seasonBadges` | Present in the payload, not modelled. The deprecated top-level `highestRound` is correctly absent, since `$gameplay->highestRound` supersedes it. |

## Packaging and repository hygiene

Not user-facing, but worth settling before a first tagged release.

| Issue | Detail |
| --- | --- |
| `"type": "project"` | `composer.json` declares a project, not a library. A published package should be `"type": "library"`. |
| Missing metadata | No `description`, `license`, `keywords`, `authors` or `homepage`, all of which Packagist displays. |
| No `php` constraint | `composer.json` declares no `"php"` requirement, so Composer installs this on PHP 8.0 and the `#[\Override]` attribute in `Collection` fails at runtime. Add `"php": "^8.3"`. |
| Name mismatch | `composer.json` says `kan/nk-opendata`, the GitHub remote is `coasterkolja/php-ninjakiwi`, and the README's install line says `coasterkolja/nk-opendata`. Three names for one package. Pick one and make the README match the final answer. |
| Namespace vs. README | The namespace is `Kan\NkOpendata`. The README documented `Kan\NKOpendata`, which fatals on a case-sensitive autoloader. |
| PHPUnit schema | `phpunit.xml` references the PHPUnit 11.5 schema while PHPUnit 13 is installed and the constraint is `^13.1`. |
| Untracked scratch file | `index.php` sits in the repository root and is a one-off debug script. Gitignore it or move it into `docs/examples/`. |
| Minimal `.gitignore` | Only `/vendor/`. Composer artefacts such as `composer.lock` policy and `.phpunit.result.cache` are unaddressed. |
| CI | No workflow. The obvious first step is `phpstan` (already at level max on `src`), `phpunit`, and `php docs/tools/generate-dto-reference.php --check`. |

## See also

- [Data objects](reference/dtos.md)
- [Errors](reference/errors.md)
- [Enums](reference/enums.md)
- [Documentation conventions](conventions.md)
