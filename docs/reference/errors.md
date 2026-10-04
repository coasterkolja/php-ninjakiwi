# Errors

The library funnels failures into a small, predictable set of exceptions.
Nothing signals an error by returning `null`, `false` or an empty array.

## Summary

| Situation | Exception | Base class |
| --- | --- | --- |
| Upstream answered `success: false` | `ApiException` | `\Exception` |
| Upstream answered with a non-JSON or non-array body | `ApiException` | `\Exception` |
| Connection failed, timed out, non-2xx status | `ApiException` | `\Exception` |
| A required field was missing from the payload | `InvalidArgumentException` | `\LogicException` |
| A method needing an ID was called without one | `LogicException` | `\LogicException` |
| An argument was out of range | `InvalidArgumentException` | `\LogicException` |
| An enum field held an unrecognised value | `UnexpectedValueException` | `\RuntimeException` |
| A value did not fit the declared type | `TypeError` | `\Error` |

The split that matters in practice: `ApiException` is a **data condition** from
upstream that you may reasonably want to tolerate, while `LogicException` means
the calling code is wrong and should propagate.

## `ApiException`

`Kan\NkOpendata\Exceptions\ApiException` covers every transport-level and
upstream-level failure. It extends `\Exception` directly and adds no properties.

```php
use Kan\NkOpendata\Exceptions\ApiException;

try {
    $players = $client->ct($id)->leaderboard()->player();
} catch (ApiException $e) {
    $players = [];
}
```

### The envelope

Every API response has the same shape:

```json
{
  "error": null,
  "success": true,
  "body": { "…": "…" },
  "model": { "name": "_btd6ct", "parameters": { "…": {} } },
  "next": null,
  "prev": null
}
```

`HttpClient::get()` considers a response successful only when `success` is
truthy **and** the decoded body is an array. Otherwise it throws.

- `model` is upstream's own field documentation. It is the source used to write
  the descriptions in the [data object reference](dtos.md), but the client does
  not expose it.
- `next` and `prev` are pagination cursors. The client **discards** them, so
  paginating is not currently possible. See [limitations](client.md#limitations).

### The upstream error message is discarded

When `success` is `false`, the API returns a useful `error` string, and the
client throws it away:

```json
{ "error": "No Scores Available", "success": false }
```

```php
// Always this, regardless of what upstream said:
ApiException: Api call failed
```

The real reason is only visible by calling the endpoint yourself. In practice you
will meet:

| Upstream `error` | Real cause |
| --- | --- |
| `No Scores Available` | The event just opened, so no one has submitted yet. |
| `No Group Available` | CT group data expired for an archived event. |
| `Invalid user ID / Player Does not play this game` | Bad hash, or the account never played BTD6. |
| `Invalid guild ID` | Bad guild ID. |
| `No CT with that ID exists` | Bad or mistyped CT event ID. |
| `Invalid team size` | Boss team size outside 1–4. Caught client-side too. |

Because the message is uniform, the **only** reliable way to distinguish these is
the call you made. Preserving the upstream string is a small, worthwhile fix; see
[roadmap(../roadmap.md#small-polish-items#small-polish-items).

> [!NOTE]
> This is a known wart, and it is the single most common cause of "why is this
> throwing?". In a Laravel application, for instance, every API failure currently
> logs the same useless line.

### Non-2xx responses

Guzzle throws on 4xx and 5xx by default, and the client catches that and
re-throws it as an `ApiException` carrying Guzzle's message **and** status code.
Note the different shape from the case above:

```php
try {
    $client->guild()->find('X');
} catch (ApiException $e) {
    $e->getCode();    // 404
    $e->getMessage(); // "Client error: `GET ...` resulted in a `404 Not Found` response: …"
}
```

This is what you get for a **wrong path**, which is a common mistake: the
endpoint is `guild/{id}`, so `guilds/{id}` returns an HTML 404 page. Anything the
API serves on a path it does not know behaves this way.

Because `getCode()` is only meaningful in this case, do not branch on it to
distinguish upstream rejection from a routing mistake.

### Transport failures

Timeouts, DNS failures and TLS errors are caught and re-thrown as
`ApiException` with Guzzle's message and a code of `0`. There is no retry and no
backoff, so a transient blip fails the call.

## `InvalidArgumentException`

Thrown by the [hydrator](hydration.md#failure-modes) when a required field is
absent from the payload, and by resource methods for out-of-range arguments.

```php
$client->bosses($id)->leaderboard()->team(5);
// InvalidArgumentException: Team size must be between 1 and 4
```

```php
// From a payload missing a field the DTO requires:
InvalidArgumentException: Missing field: name
```

The second form is what you hit when the API changes a response shape, so it is
worth treating as a signal that upstream moved rather than as bad input. It is
not caught anywhere inside the library, so it reaches you directly.

## `LogicException`

A method that needs an ID was called on a resource that does not have one:

```php
$client->ct()->tiles();          // LogicException: Id required
$client->races()->leaderboard(); // LogicException: Id required
$client->bosses()->metadata();   // LogicException: Id required
```

This is a programming error, not a data condition. Do not catch it in
application code; fix the call:

```php
$tiles = $client->ct($eventId)->tiles();
```

## `UnexpectedValueException`

The payload did not match a declared type, most often an unknown enum value:

```php
// Live data today, because the Tower enum is incomplete:
UnexpectedValueException: Invalid enum value: DanDMonke
```

Also raised for a `DateTimeImmutable` field that is not a number, and for a
non-backed enum used as a field type. See
[the tower problem(../roadmap.md#incomplete-tower-enum#incomplete-tower-enum).

## `TypeError`

A PHP-level type error, raised when a value does not fit the declared
constructor type — for example an `int` property receiving a string, because the
hydrator passes values through without coercion:

```php
TypeError: Kan\NkOpendata\DTO\…::__construct(): Argument #3 ($totalScores) must be of type int, string given
```

Same root cause as `InvalidArgumentException` above: upstream changed, and the
DTO no longer matches.

## Recommended handling

```php
use Kan\NkOpendata\Exceptions\ApiException;

function leaderboardOrEmpty(callable $fetch): array
{
    try {
        return $fetch();
    } catch (ApiException) {
        // Upstream refused, was unreachable, or has no data yet.
        return [];
    }
    // LogicException, InvalidArgumentException, TypeError and
    // UnexpectedValueException are deliberately not caught: they mean either a
    // bug in this code or a breaking change upstream, and both should be loud.
}

$players = leaderboardOrEmpty(fn () => $client->ct($id)->leaderboard()->player());
```

If you want to log usefully in the meantime, include the call site, because the
exception message will not tell you:

```php
try {
    $entries = $client->ct($ctId)->leaderboard()->team();
} catch (ApiException $e) {
    Log::warning('CT team leaderboard unavailable', [
        'ct'      => $ctId,
        'message' => $e->getMessage(),
        'code'    => $e->getCode(),
    ]);
    $entries = [];
}
```

## See also

- [Client limitations](client.md#limitations)
- [Hydration failure modes](hydration.md#failure-modes)
- [Roadmap(../roadmap.md)
- [`src/Exceptions/ApiException.php`](../../src/Exceptions/ApiException.php)
