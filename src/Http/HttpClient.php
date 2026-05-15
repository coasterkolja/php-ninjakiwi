<?php

namespace Kan\NkOpendata\Http;

use GuzzleHttp\Client;
use Kan\NkOpendata\Exceptions\ApiException;

class HttpClient
{
    protected Client $client;

    public function __construct(string $baseUrl)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $uri): array {
        try {
            $response = $this->client->get($uri);
        } catch(\Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        /** @var mixed $data */
        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data) || !($data['success'] ?? false)) {
            throw new ApiException('Api call failed');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
