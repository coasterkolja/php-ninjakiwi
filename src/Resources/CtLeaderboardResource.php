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

    /** @return array<int, CtLeaderboardPlayer> */
    public function player(): array
    {
        return $this->map(
            "ct/{$this->id}/leaderboard/player",
            CtLeaderboardPlayer::class
        );
    }

    /** @return array<int, CtLeaderboardTeam> */
    public function team(): array
    {
        return $this->map(
            "ct/{$this->id}/leaderboard/team",
            CtLeaderboardTeam::class
        );
    }

    /** @return array<int, CtLeaderboardGroup> */
    public function group(string $groupId): array
    {
        return $this->map(
            "ct/{$this->id}/leaderboard/group/{$groupId}",
            CtLeaderboardGroup::class
        );
    }
}
