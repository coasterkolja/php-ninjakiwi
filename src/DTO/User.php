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

        /** @var array<string, mixed> */
        public array $stats,

        /** @var array<string, mixed> */
        public array $bossBadgesNormal,

        /** @var array<string, mixed> */
        public array $bossBadgesElite,
        
        /** @var array<string, mixed> */
        #[MapFrom('_medalsSinglePlayer')]
        public array $medalsSingleplayer,

        /** @var array<string, mixed> */
        #[MapFrom('_medalsMultiplayer')]
        public array $medalsMultiplayer,

        /** @var array<string, mixed> */
        #[MapFrom('_medalsBoss')]
        public array $medalsBossNormal,

        /** @var array<string, mixed> */
        #[MapFrom('_medalsBossElite')]
        public array $medalsTeamElite,

        /** @var array<string, mixed> */
        #[MapFrom('_medalsCTLocal')]
        public array $medalsCtLocal,

        /** @var array<string, mixed> */
        #[MapFrom('_medalsCTGlobal')]
        public array $medalsCtGlobal,

        /** @var array<string, mixed> */
        #[MapFrom('_medalsRace')]
        public array $medalsRace
    ) {}
}