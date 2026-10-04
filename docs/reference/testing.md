# Testing

The resources depend on `HttpClient`, and `HttpClient` depends on Guzzle. That
gives you two seams for testing without network access: stub `HttpClient` for
resource behaviour, or stub Guzzle's handler for transport behaviour.

## Stubbing the transport

`HttpClientTest` in the repo replaces the internal Guzzle client with a mock
handler, which is the closest thing to a real request:

```php
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Kan\NkOpendata\Client;
use Kan\NkOpendata\Http\HttpClient;

$mock = new MockHandler([
    new Response(200, [], json_encode([
        'success' => true,
        'body' => [
            'id' => 'ct_1',
            'start' => (time() - 3600) * 1000,
            'end' => (time() + 3600) * 1000,
            'totalScores_player' => 100,
            'totalScores_team' => 200,
            'tiles' => '',
            'leaderboard_player' => '',
            'leaderboard_team' => '',
        ],
    ])),
]);

$http = new HttpClient('https://data.ninjakiwi.com/btd6/');

(new ReflectionProperty(HttpClient::class, 'client'))
    ->setValue($http, new Guzzle(['handler' => HandlerStack::create($mock)]));

$resource = new \Kan\NkOpendata\Resources\CtResource($http);

$this->assertSame('ct_1', $resource->current()->id);
```

Caveats: `$client` is `protected`, so reaching it needs reflection, and
`MockHandler` is a queue, so the number of responses must match the number of
requests.

## Stubbing the HTTP client

`ResourceTest` takes the simpler route and stubs `HttpClient` itself, which is
the right level for testing what a resource does with a payload:

```php
$http = $this->createStub(HttpClient::class);
$http->method('get')->willReturn(['success' => true, 'body' => $payload]);

$resource = new RaceResource($http);
$entries  = $resource->leaderboard();
```

## Asserting the URI

Because the client is fully constructed inside `Client`, the most valuable
assertions — "does this call the right endpoint?" — need a stub that reports the
URI it was given:

```php
$http = $this->createStub(HttpClient::class);
$http->method('get')->willReturnCallback(function (string $uri) use (&$seen) {
    $seen = $uri;

    return ['success' => true, 'body' => []];
});

(new BossResource($http))->list();

$this->assertSame('bosses', $seen);
```

This is what `ResourceTest::test_*_correct_uri` does for each resource, and it is
worth keeping: the URI strings are duplicated in the resource classes, and
nothing else pins them down.

## Fixtures

`HydratorTest` skips the HTTP layer entirely and feeds raw arrays to the
hydrator, which is the cheapest way to test DTO mapping:

```php
use Kan\NkOpendata\DTO\Race;
use Kan\NkOpendata\Hydrator\Hydrator;

$race = Hydrator::hydrate(Race::class, [
    'id' => 'race_1',
    'name' => 'Race',
    'start' => 1700000000000,
    'end' => 1700086400000,
    'totalScores' => 100,
    'leaderboard' => '',
    'metadata' => '',
]);

$this->assertSame(1700000000, $race->start->getTimestamp());
```

Timestamps must be epoch **milliseconds**, matching the API. See
[hydration](hydration.md#type-rules).

## Running the suite

```bash
./vendor/bin/phpunit
```

## Status

> [!WARNING]
> `Client` cannot be constructed with an injected `HttpClient`. It always builds
> its own from the base URL, so a test that wants to exercise `Client` itself has
> no seam:
>
> ```php
> $client = new Client($http);   // does not compile today
> ```
>
> The workarounds are to construct resources directly, as above, or to reach into
> the client by reflection. Adding an injection point is on the
> [roadmap](../roadmap.md#no-way-to-inject-or-configure-the-http-client).

Two smaller gaps worth knowing about:

- `HttpClient` cannot be given a timeout, retry policy or middleware, because its
  constructor only accepts a base URL. Tests and long-running jobs have no
  control over either.
- `MockHandler` is required for any transport-level test, which means
  `guzzlehttp/guzzle` must stay in `require-dev` scope for the test suite to work
  in a minimal install.

## The examples double as smoke tests

Every script in [`docs/examples`](../examples) runs against the live API and exits
non-zero on failure, so they are a cheap end-to-end check that the library still
talks to the real service:

```bash
for f in docs/examples/*.php; do php "$f" || echo "FAILED: $f"; done
```

They are not a substitute for the unit suite — they depend on live data — but they
catch exactly the class of breakage the unit tests miss, such as upstream adding
a new tower name.

## See also

- [Client](client.md)
- [Errors](errors.md)
- [`tests/Unit/`](../../tests/Unit)
