<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\CtLeaderboardGroup;
use Kan\NkOpendata\DTO\CtLeaderboardPlayer;
use Kan\NkOpendata\DTO\CtLeaderboardTeam;
use Kan\NkOpendata\Http\HttpClient;

class CtLeaderboardResource extends Resource
{
    protected string $id;

    public function __construct(HttpClient $http, string $id)
    {
        $this->id = $id;
        parent::__construct($http);
    }

    public function player(): array
    {
        return $this->map(
            "ct/{$this->id}/leaderboard/player",
            CtLeaderboardPlayer::class
        );
    }

    public function team(): array
    {
        return $this->map(
            "ct/{$this->id}/leaderboard/team",
            CtLeaderboardTeam::class
        );
    }

    public function group(string $groupId): array
    {
        return $this->map(
            "ct/{$this->id}/leaderboard/group/{$groupId}",
            CtLeaderboardGroup::class
        );
    }
}
