<?php

namespace Kan\NkOpendata\DTO;

class BloonModifiers implements DTOInterface {
    public function __construct(
        public float $speedMultiplier,
        public float $moabSpeedMultiplier,
        public float $bossSpeedMultiplier,
        public float $regrowRateMultiplier,
        public array $healthMultipliers,
        public bool $allCamo,
        public bool $allRegen
    ) {}
}