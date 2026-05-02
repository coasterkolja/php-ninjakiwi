<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\BossLeaderboard;
use Kan\NkOpendata\Http\HttpClient;

class BossLeaderboardResource extends Resource {
    protected string $id;
    protected string $mode = "standard";

    public function __construct(HttpClient $http, string $id)
    {
        $this->id = $id;
        return parent::__construct($http);
    }

    public function mode(string $mode): self {
        $this->mode = $mode;

        return $this;
    }

    public function elite(): self {
        return $this->mode("elite");
    }

    public function standard(): self {
        return $this->mode("standard");
    }

    public function team(int $teamSize = 2): array {
        if ($teamSize < 1 || $teamSize > 4) {
            throw new \InvalidArgumentException("Team size must be between 1 and 4");
        }

        return $this->map(
            "bosses/{$this->id}/leaderboard/{$this->mode}/{$teamSize}",
            BossLeaderboard::class
        );
    }

    public function singleplayer(): array {
        return $this->team(1);
    }
}