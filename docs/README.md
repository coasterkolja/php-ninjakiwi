# Documentation

Reference documentation for the PHP Ninjakiwi Open Data API client, a typed PHP
wrapper around the public [data.ninjakiwi.com](https://data.ninjakiwi.com/) BTD6
endpoints.

Start with [Getting started](getting-started.md) if you have never used the
library, or jump straight to the page for the [resource](#resources) you need.

## Contents

### Start here

| Page | What it covers |
| --- | --- |
| [Getting started](getting-started.md) | Requirements, installation, the first three calls, error handling, testing your own code. |
| [Examples](examples/) | Runnable scripts. Clone the repo and run them as-is. |

### Resources

One page per endpoint group. Each lists the methods, the upstream URL, and what
it returns.

| Page | Entry point | Upstream |
| --- | --- | --- |
| [Races](resources/races.md) | `$client->races()` | `/btd6/races` |
| [Contested Territory](resources/contested-territory.md) | `$client->ct()` | `/btd6/ct` |
| [Bosses](resources/bosses.md) | `$client->bosses()` | `/btd6/bosses` |
| [Users](resources/users.md) | `$client->users()` | `/btd6/users` |
| [Guilds](resources/guilds.md) | `$client->guild()` | `/btd6/guild` |

### Reference

| Page | What it covers |
| --- | --- |
| [Client](reference/client.md) | Constructor, base URL, the resource factory methods. |
| [Data objects](reference/dtos.md) | Every DTO, field by field. **Generated**, do not edit. |
| [Hydration](reference/hydration.md) | How JSON becomes DTOs: `MapFrom`, `CastWith`, type rules. |
| [Collections](reference/collections.md) | `Collection` and its typed subclasses. |
| [Enums](reference/enums.md) | `Tower` and `TileGameType`. |
| [Errors](reference/errors.md) | Which exception is thrown where, and how to handle it. |
| [Testing](reference/testing.md) | Stubbing the HTTP layer with Guzzle's mock handler. |

### Project

| Page | What it covers |
| --- | --- |
| [Roadmap](roadmap.md) | Known gaps, open decisions, planned API. |
| [Documentation conventions](conventions.md) | How to keep these pages correct as the code changes. |

## How this documentation is organised

The library has three layers, and the docs follow the same order as a call goes
through them:

```
Client  ->  Resource  ->  HttpClient  ->  JSON  ->  Hydrator  ->  DTO
```

1. **[Client](reference/client.md)** holds the base URL and hands out resources.
   It never performs I/O itself.
2. **Resources** are the API surface you actually program against. One class per
   endpoint group, and they are cheap: a resource is a thin, immutable cursor
   over an optional ID, so `$client->ct('abc')` makes no request until you call a
   method on it.
3. **`HttpClient`** performs the GET and unwraps the
   [`{success, body, error}` envelope](reference/errors.md#the-envelope),
   throwing [`ApiException`](reference/errors.md#apiexception) on anything
   unexpected.
4. **The [Hydrator](reference/hydration.md)** turns the decoded JSON into DTOs
   by reflecting over their constructors. Timestamps become `DateTimeImmutable`,
   strings become enums, nested objects become DTOs, and list-shaped nested
   objects become [Collections](reference/collections.md).
5. **[DTOs](reference/dtos.md)** are the values you work with. Plain classes
   with public promoted constructor properties.

## Why the data object reference is generated

An earlier version of the README carried a hand-written `## Schemas` section
listing every field of every DTO as a code block. It was wrong within days, for
three reasons:

- Duplicated the constructor signatures that already exist in `src/DTO`.
- Could not describe field provenance, so nobody could tell that
  `$bossTypeImage` comes from `bossTypeURL` or that timestamps arrive as
  milliseconds.
- Had no way to notice when a DTO changed.

[reference/dtos.md](reference/dtos.md) is now produced by
[`tools/generate-dto-reference.php`](tools/generate-dto-reference.php), which
reads the constructors by reflection and records which resource method returns
each class. Field prose is seeded from the `model` metadata the public API
returns alongside every payload, so it can be re-verified against upstream at
any time.

Regenerate it after touching a DTO:

```bash
php docs/tools/generate-dto-reference.php
```

In CI, fail the build when it is stale:

```bash
php docs/tools/generate-dto-reference.php --check
```

See [documentation conventions](conventions.md) for the rest of the workflow.
