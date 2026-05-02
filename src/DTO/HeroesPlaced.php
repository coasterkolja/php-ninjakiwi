<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Enums\Tower;

class HeroesPlaced implements DTOInterface
{
    public function __construct(
        public int $AdmiralBrickell,
        public int $Adora,
        public int $Benjamin,
        public int $Etienne,
        public int $Geraldo,
        public int $Gwendolin,
        public int $ObynGreenfoot,
        public int $PatFusty,
        public int $Psi,
        public int $Quincy,
        public int $Sauda,
        public int $StrikerJones,
        public int $Ezili,
        public int $CaptainChurchill,
        public int $Corvus,
        public int $Rosalia,
    ) {}

    public function get(Tower $hero): int
    {
        return match ($hero) {
            Tower::AdmiralBrickell => $this->AdmiralBrickell,
            Tower::Adora => $this->Adora,
            Tower::Benjamin => $this->Benjamin,
            Tower::Etienne => $this->Etienne,
            Tower::Geraldo => $this->Geraldo,
            Tower::Gwendolin => $this->Gwendolin,
            Tower::ObynGreenfoot => $this->ObynGreenfoot,
            Tower::PatFusty => $this->PatFusty,
            Tower::Psi => $this->Psi,
            Tower::Quincy => $this->Quincy,
            Tower::Sauda => $this->Sauda,
            Tower::StrikerJones => $this->StrikerJones,
            Tower::Ezili => $this->Ezili,
            Tower::CaptainChurchill => $this->CaptainChurchill,
            Tower::Corvus => $this->Corvus,
            Tower::Rosalia => $this->Rosalia,
        };
    }
}