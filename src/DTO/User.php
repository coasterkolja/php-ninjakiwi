<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\DTO\BloonsPopped;
use Kan\NkOpendata\DTO\HeroesPlaced;
use Kan\NkOpendata\DTO\TowersPlaced;
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class User implements DTOInterface {
    public function __construct(
        #[MapFrom('displayName')]
        public string $name,

        public int $rank,
        public int $veteranRank,
        public string $achievements,
        public string $mostExperiencedMonkey,
        public string $avatar,
        public string $banner,

        #[MapFrom('avatarURL')]
        public string $avatarUrl,
        
        #[MapFrom('bannerURL')]
        public string $bannerUrl,

        public int $followers,

        public BloonsPopped $bloonsPopped,
        public Gameplay $gameplay,
        public HeroesPlaced $heroesPlaced,
        public TowersPlaced $towersPlaced,
        public array $stats,
        public array $bossBadgesNormal,
        public array $bossBadgesElite,
        
        #[MapFrom('_medalsSinglePlayer')]
        public array $medalsSingleplayer,

        #[MapFrom('_medalsMultiplayer')]
        public array $medalsMultiplayer,

        #[MapFrom('_medalsBoss')]
        public array $medalsBossNormal,

        #[MapFrom('_medalsBossElite')]
        public array $medalsTeamElite,

        #[MapFrom('_medalsCTLocal')]
        public array $medalsCtLocal,

        #[MapFrom('_medalsCTGlobal')]
        public array $medalsCtGlobal,

        #[MapFrom('_medalsRace')]
        public array $medalsRace
    ) {}
}