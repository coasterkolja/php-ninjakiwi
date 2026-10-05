<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Enums\GameType;
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class BossEvent implements DTOInterface
{
  public function __construct(
    public string $id,
    public string $name,
    public \DateTimeImmutable $start,
    public \DateTimeImmutable $end,
    public string $bossType,

    #[MapFrom('bossTypeURL')]
    public string $bossTypeImage,

    #[MapFrom('totalScores_standard')]
    public int $totalScoresStandard,

    #[MapFrom('totalScores_elite')]
    public int $totalScoresElite,

    #[MapFrom('leaderboard_standard_players_1')]
    public string $leaderboardStandardSingleplayer,

    #[MapFrom('leaderboard_elite_players_1')]
    public string $leaderboardEliteSingleplayer,

    public string $metadataStandard,
    public string $metadataElite,

    #[MapFrom('normalScoringType')]
    public GameType $scoringTypeStandard,

    #[MapFrom('eliteScoringType')]
    public GameType $scoringTypeElite,
  ) {}
}

