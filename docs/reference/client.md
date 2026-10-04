# Client

`Kan\NkOpendata\Client` is the entry point. It holds the base URL, owns the HTTP
client, and hands out resources. It performs no I/O itself.

```php
namespace Kan\NkOpendata;

class Client
{
    public function __construct(string $baseUri = 'https://data.ninjakiwi.com/btd6/')
    public function ct(?string $id = null): CtResource
    public function bosses(?string $id = null): BossResource
    public function races(?string $id = null): RaceResource
    public function users(): UserResource
    public function guild(): GuildResource
}
```

## Construction

```php
$client = new Client();
```

The default base URL is `https://data.ninjakiwi.com/btd6/`. The trailing slash
matters: resource URIs are relative and are passed straight to Guzzle, so a base
URL without it resolves against the wrong path.

Point it at a different host to test against a fixture server:

```php
$client = new Client('https://staging.example.com/btd6/');
```

The endpoint is not configurable, and there is no separate constructor for
options such as timeouts, retries or middleware. See
[limitations](#limitations) below.

## Resource factories

Each method returns a new resource instance. They are cheap immutable cursors
over an optional ID, so building one costs nothing and sends no request.

```php
$client->ct();          // CtResource, no ID  -> list(), recent(), current()
$client->ct('mtv62zza'); // CtResource, with ID -> also tiles(), leaderboard()
```

| Method | Resource | ID argument | Collection methods |
| --- | --- | --- | --- |
| `ct()` | `CtResource` | optional | `list()`, `recent()`, `current()`, `find()` |
| `bosses()` | `BossResource` | optional | `list()` |
| `races()` | `RaceResource` | optional | `list()` |
| `users()` | `UserResource` | none | — |
| `guild()` | `GuildResource` | none | — |

Two naming details worth internalising:

- `guild()` is **singular**, matching the upstream `guild/{id}` path.
- Only `ct()`, `bosses()` and `races()` take an ID. `users()` and `guild()` have
  no list endpoint, so they expose no argument and you call `find()` instead.

### Reuse and immutability

Each call returns a **new** instance, so nothing is shared between variables:

```php
$a = $client->ct('mtv62zza');
$b = $client->ct('mtg7h9ny');

$a->tiles();   // still scoped to mtv62zza
$b->tiles();   // scoped to mtg7h9ny
```

The one exception is `BossLeaderboardResource`, whose `standard()` and `elite()`
selectors mutate the instance. See [bosses(../resources/bosses.md#difficulty#difficulty).

## Patterns

### Chaining a resource per iteration

Because resources are cheap, the natural shape is to build one per item:

```php
foreach ($client->bosses()->list() as $boss) {
    $metadata = $client->bosses($boss->id)->metadata()->standard();
    printf("%-14s %s\n", $boss->bossType, $metadata->map);
}
```

### Traversing by URL

Leaderboard and guild payloads contain absolute URLs whose last path segment is
the ID of the linked resource. This pattern appears in every resource page:

```php
$user = $client->users()->find(basename(parse_url($entry->profile, PHP_URL_PATH)));
```

A shared `idFromUrl()` helper is planned; see the
[roadmap(../roadmap.md#small-polish-items#small-polish-items).

### Counting requests

One resource method equals one HTTP GET. There is no request cache, so calling
`recent()` and then `current()` fetches the same list twice. When listing events
and inspecting one, reuse the object you already have:

```php
$events = $client->ct()->list();          // one request
$latest = $events[0];

if ($latest->end->getTimestamp() > time()) {
    $tiles = $client->ct($latest->id)->tiles();   // one more
}
```

## Limitations

These are current gaps, tracked on the [roadmap(../roadmap.md):

- **No option injection.** `Client` always constructs its own `HttpClient`, and
  `HttpClient` accepts only a base URL. You cannot set a timeout, a retry policy,
  a Guzzle handler or a middleware stack, which makes it awkward to configure
  proxies or to test without reflection. In the meantime, see
  [testing(../reference/testing.md) for the reflection workaround.
- **No response caching.** Every call is a fresh GET.
- **No pagination.** The API envelope carries `next` and `prev` keys, but the
  `HttpClient` discards them and list endpoints currently return everything in
  one page. See [errors(../reference/errors.md#the-envelope#the-envelope).
- **No retry or backoff.** Transient 5xx responses become `ApiException`.
- **No async.** The client is strictly synchronous.

## See also

- [Getting started](../getting-started.md)
- [Errors](errors.md)
- [Testing](testing.md)
