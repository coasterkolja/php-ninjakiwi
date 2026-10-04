# Guilds

A guild's public profile. The API has no guild *list* and no name lookup, so this
resource exposes a single `find()` addressed by guild ID.

```php
$guild = $client->guild()->find('9fb9168a8a94f1a449458c4a0a25e522cf021ab59f41df39');
```

Upstream: [`/btd6/guild`](https://data.ninjakiwi.com/btd6/guild)

> [!NOTE]
> The path segment is `guild`, **not** `guilds`. Anything else returns
> `Cannot GET /btd6/guilds/{id}` with a 404 and an HTML body, which the client
> reports as `ApiException`.

## Methods

| Method | Upstream | Returns |
| --- | --- | --- |
| `find(string $id)` | `GET guild/{id}` | `Guild` |

The factory method is `guild()`, singular.

## Reading a guild

```php
printf(
    "%s — %d members, %s\n",
    $guild->name,
    $guild->numMembers,
    $guild->status,
);

echo $guild->iconUrl, PHP_EOL;
```

- `$guild->name` is the guild name.
- `$guild->numMembers` is the current member count.
- `$guild->status` is reported verbatim by the API. Observed values include
  `FILTERED`; the set is not documented upstream, so treat it as an opaque
  string and do not switch exhaustively on it.

### The owner is a URL

`$guild->owner` is **not** a display name. It is the full profile URL of the
owner, whose last path segment is the user ID:

```php
$owner = $client->users()->find(basename(parse_url($guild->owner, PHP_URL_PATH)));

printf("%s founded by %s (rank %d)\n", $guild->name, $owner->name, $owner->rank);
```

Getting this wrong is easy, and it will not fail loudly: the value is a valid
`string`, so it simply prints as a URL.

### Cosmetics

`banner`, `frame` and `icon` are cosmetic identifiers such as
`TeamsBanner16`, and the matching `bannerUrl`, `frameUrl` and `iconUrl` are
fully qualified asset URLs.

```php
printf("%s (%s)\n", $guild->icon ?? 'no icon', $guild->iconUrl);
```

The three cosmetic identifiers are nullable with a `null` default, so a payload
that omits them hydrates to `null` rather than throwing. Every other field is
required.

Fields: [Guild(../reference/dtos.md#guild#guild).

## Where guild IDs come from

There is no search endpoint, so the ID always arrives from somewhere else. In
practice, from a [CT team leaderboard](contested-territory.md#teams-and-groups),
where a team's `profile` points at its guild:

```php
$team  = $client->ct($ctId)->leaderboard()->team()[0];
$guild = $client->guild()->find(basename(parse_url($team->profile, PHP_URL_PATH)));

printf("%s scored %d\n", $guild->name, $team->score);
```

Full chain from CT to the guild owner:

```php
$ct    = $client->ct('mtv62zza');
$team  = $ct->leaderboard()->team()[0];
$guild = $client->guild()->find(basename(parse_url($team->profile, PHP_URL_PATH)));
$owner = $client->users()->find(basename(parse_url($guild->owner, PHP_URL_PATH)));

printf(
    "%s (%d members, %s) is owned by %s\n",
    $guild->name,
    $guild->numMembers,
    $guild->status,
    $owner->name,
);
```

```
CHEESINGTHEALG0RITHM (1 members, FILTERED) is owned by Jemima
```

Because the ID extraction repeats, a shared helper is worth having:

```php
function idFromUrl(string $url): string
{
    return basename(parse_url($url, PHP_URL_PATH));
}
```

A built-in equivalent is on the [roadmap](../roadmap.md#small-polish-items).

## Undocumented fields

The payload also contains a `CtBadges` map of the guild's Contested Territory
medals, keyed by medal ID with a `url` and `id` per entry. The library does not
model it, so it is not reachable. If you need it, fetch the endpoint yourself and
merge the result.

## See also

- [Contested Territory](contested-territory.md) — the main source of guild IDs
- [Users](users.md) — resolving the owner profile
- [Data objects(../reference/dtos.md)
- [Roadmap](../roadmap.md)
