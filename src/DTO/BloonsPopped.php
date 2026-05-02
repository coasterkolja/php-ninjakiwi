<?php

namespace Kan\NkOpendata\DTO;

class BloonsPopped implements DTOInterface {
    public function __construct(
        public int $badsPopped,
        public int $ddtsPopped,
        public int $bfbsPopped,
        public int $bossesPopped,
        public int $bloonsPopped,
        public int $bloonsLeaked,
        public int $camosPopped,
        public int $ceramicsPopped,
        public int $coopBloonsPopped,
        public int $goldenBloonsPopped,
        public int $leadsPopped,
        public int $moabsPopped,
        public int $necroBloonsReanimated,
        public int $transformingTonicsUsed,
        public int $purplesPopped,
        public int $regrowsPopped,
        public int $zomgsPopped
    ) {}
}