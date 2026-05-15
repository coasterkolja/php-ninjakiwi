<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\BossEvent;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Resources\BossLeaderboardResource;
use Kan\NkOpendata\Resources\Concerns\RequiresId;

class BossResource extends Resource
{
    use RequiresId;

    public function __construct(HttpClient $http, ?string $id = null)
    {
        $this->id = $id;
        parent::__construct($http);
    }

    /** @return array<int, BossEvent> */
    public function list(): array {
        return $this->map('bosses', BossEvent::class);
    }

    public function metadata(): BossMetadataResource {
        $this->requireId();

        /** @var string $id */
        $id = $this->id;

        return new BossMetadataResource($this->http, $id);
    }

    public function leaderboard(): BossLeaderboardResource {
        $this->requireId();

        /** @var string $id */
        $id = $this->id;

        return new BossLeaderboardResource($this->http, $id);
    }
}