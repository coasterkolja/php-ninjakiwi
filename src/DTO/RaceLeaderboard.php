<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Collections\ScorePartCollection;
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class RaceLeaderboard implements DTOInterface {
    public function __construct(
        #[MapFrom('displayName')]
        public string $name,

        public int $score,
        public ScorePartCollection $scoreParts,
        public int $submissionTime,
        public string $profile,
    ) {}
}