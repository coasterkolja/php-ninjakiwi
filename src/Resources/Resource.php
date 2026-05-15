<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\DTOInterface;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Hydrator\Hydrator;

abstract class Resource
{
    protected HttpClient $http;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }

    /**
     * @template T of DTOInterface
     * @param class-string<T> $dto
     * @return T
     */
    protected function get(string $uri, string $dto): DTOInterface {
        $data = $this->http->get($uri);

        /** @var array<string, mixed> $body */
        $body = $data['body'];

        return Hydrator::hydrate($dto, $body);
    }

    /**
     * @template T of DTOInterface
     * @param class-string<T> $dto
     * @return array<int, T>
     */
    protected function map(string $uri, string $dto): array {
        $data = $this->http->get($uri);

        /** @var array<int, array<string, mixed>> $body */
        $body = $data['body'];

        return Hydrator::hydrateCollection($dto, $body);
    }

    /**
     * @return mixed
     */
    protected function extract(string $uri, string $key): mixed {
        $data = $this->http->get($uri);

        /** @var array<string, mixed> $body */
        $body = $data['body'];

        if (!array_key_exists($key, $body)) {
            return null;
        }

        return $body[$key];
    }

    /**
     * @template T
     * @param callable(array<string, mixed>): T $callback
     * @return T
     */
    protected function transform(string $uri, callable $callback): mixed {
        $data = $this->http->get($uri);

        /** @var array<string, mixed> $body */
        $body = $data['body'];

        return $callback($body);
    }
}