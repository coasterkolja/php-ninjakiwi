<?php

namespace Kan\NkOpendata;

use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Resources\BossResource;
use Kan\NkOpendata\Resources\CtResource;
use Kan\NkOpendata\Resources\GuildResource;
use Kan\NkOpendata\Resources\RaceResource;
use Kan\NkOpendata\Resources\UserResource;

class Client
{
  protected HttpClient $http;

  public function __construct(?HttpClient $http = null, ?string $baseUri = null)
  {
    $this->http = $http ?? new HttpClient($baseUri ?? 'https://data.ninjakiwi.com/btd6/');
  }

  public function ct(?string $id = null): CtResource
  {
    return new CtResource($this->http, $id);
  }

  public function bosses(?string $id = null): BossResource
  {
    return new BossResource($this->http, $id);
  }

  public function races(?string $id = null): RaceResource
  {
    return new RaceResource($this->http, $id);
  }

  public function users(): UserResource
  {
    return new UserResource($this->http);
  }

  public function guild(): GuildResource
  {
    return new GuildResource($this->http);
  }
}
