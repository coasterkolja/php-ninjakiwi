<?php

namespace Kan\NkOpendata;

use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Resources\BossResource;
use Kan\NkOpendata\Resources\CtResource;

class Client
{
    protected HttpClient $http;

    public function __construct(string $baseUri = 'https://data.ninjakiwi.com/btd6/')
    {
        $this->http = new HttpClient($baseUri);
    }

    public function ct(?string $id = null): CtResource
    {
        return new CtResource($this->http, $id);
    }

    public function boss(?string $id = null): BossResource
    {
        return new BossResource($this->http, $id);
    }
}
