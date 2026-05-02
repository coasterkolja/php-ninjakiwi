<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class CtEvent implements DTOInterface
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        
        #[MapFrom('totalScores_player')]
        public int $totalScoresPlayer,
        
        #[MapFrom('totalScores_team')]
        public int $totalScoresTeam,
        
        public string $tiles,
        
        #[MapFrom('leaderboard_player')]
        public string $leaderboardPlayer,
        
        #[MapFrom('leaderboard_team')]
        public string $leaderboardTeam
    ) {}
}
