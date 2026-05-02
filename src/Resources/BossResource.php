<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\BossEvent;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Resources\BossLeaderboardResource;

class BossResource extends Resource
{
    protected ?string $id = null;

    public function __construct(HttpClient $http, ?string $id = null)
    {
        $this->id = $id;
        parent::__construct($http);
    }

    public function list(): array {
        return $this->map('bosses', BossEvent::class);
    }

    public function metadata(): BossMetadataResource {
        $this->requireId();

        return new BossMetadataResource($this->http, $this->id);
    }

    public function leaderboard(): BossLeaderboardResource {
        $this->requireId();

        return new BossLeaderboardResource($this->http, $this->id);
    }

    protected function requireId(): void {
        if (!$this->id) {
            throw new \LogicException('Boss id required');
        }
    }
}