# Roadmap

Everything below was checked against the live API on **28 September 2026** with
the library at commit `d994b40`. Two of these items are not hypothetical: they
break documented, working-looking calls against current data.

Status vocabulary used throughout these docs:

| Marker | Meaning |
| --- | --- |
| *(none)* | Implemented today. |
| **Planned** | Does not exist yet. Documented so the intended shape is agreed before code is written. |


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

## Missing convenience: current and recent for every event type

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
