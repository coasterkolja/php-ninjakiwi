<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class CtLeaderboardPlayer implements DTOInterface {
    public function __construct(
        #[MapFrom('displayName')]
        public string $name,
        public int $score,
        public string $profile,
    ) {}
}