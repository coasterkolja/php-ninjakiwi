<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Collections\TowerCollection;
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

abstract class Metadata implements DTOInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public \DateTimeImmutable $createdAt,
        public ?string $creator,
        public string $gameVersion,

        public string $map,

        #[MapFrom('mapURL')]
        public string $mapImage,

        public string $mode,
        public string $difficulty,

        public bool $disableDoubleCash,
        public bool $disableInstas,
        public bool $disableMK,
        public bool $disablePowers,
        public bool $disableSelling,

        public int $startingCash,
        public float $abilityCooldownReductionMultiplier,
        public int $leastCashUsed,
        public int $leastTiersUsed,
        public bool $noContinues,
        public int $seed,
        public float $removeableCostMultiplier,

        /** @var array<int, mixed> */
        public array $roundSets,
        public int $lives,
        public int $maxLives,
        public int $startRound,
        public int $endRound,
        public int $maxTowers,
        public int $maxParagons,
        public int $plays,
        public int $wins,
        public int $restarts,
        public int $losses,
        public int $upvotes,
        public int $playsUnique,
        public int $winsUnique,
        public int $lossesUnique,

        /** @var array<int, mixed> */
        #[MapFrom('_powers')]
        public array $powers,

        #[MapFrom('_bloonModifiers')]
        public BloonModifiers $bloonModifiers,

        #[MapFrom('_towers')]
        public TowerCollection $towers
    ) {}
}
