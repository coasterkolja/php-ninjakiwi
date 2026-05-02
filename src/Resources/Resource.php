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

    protected function get(string $uri, string $dto): DTOInterface {
        $data = $this->http->get($uri);

        return Hydrator::hydrate($dto, $data['body']);

    }

    protected function map(string $uri, string $dto): array {
        $data = $this->http->get($uri);

        return Hydrator::hydrateCollection($dto, $data['body']);
    }

    protected function extract(string $uri, string $key): mixed {
        $data = $this->http->get($uri);

        if (!array_key_exists($key, $data['body'])) {
            return null;
        }

        return $data['body'][$key];
    }

    protected function transform(string $uri, callable $callback): array {
        $data = $this->http->get($uri);
        
        return $callback($data['body']);
    }
}