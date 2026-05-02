<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Enums\Tower;

class TowersPlaced implements DTOInterface
{
    public function __construct(
        public int $DartMonkey,
        public int $BombShooter,
        public int $NinjaMonkey,
        public int $SuperMonkey,
        public int $WizardMonkey,
        public int $BananaFarm,
        public int $MonkeyVillage,
        public int $HeliPilot,
        public int $Druid,
        public int $EngineerMonkey,
        public int $MortarMonkey,
        public int $Alchemist,
        public int $MonkeyAce,
        public int $BoomerangMonkey,
        public int $SpikeFactory,
        public int $GlueGunner,
        public int $TackShooter,
        public int $MonkeyBuccaneer,
        public int $MonkeySub,
        public int $DartlingGunner,
        public int $BeastHandler,
        public int $IceMonkey,
        public int $SniperMonkey,
        public int $Mermonkey,
        public int $Desperado
    ) {}

    public function get(Tower $hero): int
    {
        return match ($hero) {
            Tower::DartMonkey => $this->DartMonkey,
            Tower::BombShooter => $this->BombShooter,
            Tower::NinjaMonkey => $this->NinjaMonkey,
            Tower::SuperMonkey => $this->SuperMonkey,
            Tower::WizardMonkey => $this->WizardMonkey,
            Tower::BananaFarm => $this->BananaFarm,
            Tower::MonkeyVillage => $this->MonkeyVillage,
            Tower::HeliPilot => $this->HeliPilot,
            Tower::Druid => $this->Druid,
            Tower::EngineerMonkey => $this->EngineerMonkey,
            Tower::MortarMonkey => $this->MortarMonkey,
            Tower::Alchemist => $this->Alchemist,
            Tower::MonkeyAce => $this->MonkeyAce,
            Tower::BoomerangMonkey => $this->BoomerangMonkey,
            Tower::SpikeFactory => $this->SpikeFactory,
            Tower::GlueGunner => $this->GlueGunner,
            Tower::TackShooter => $this->TackShooter,
            Tower::MonkeyBuccaneer => $this->MonkeyBuccaneer,
            Tower::MonkeySub => $this->MonkeySub,
            Tower::DartlingGunner => $this->DartlingGunner,
            Tower::BeastHandler => $this->BeastHandler,
            Tower::IceMonkey => $this->IceMonkey,
            Tower::SniperMonkey => $this->SniperMonkey,
            Tower::Mermonkey => $this->Mermonkey,
            Tower::Desperado => $this->Desperado
        };
    }
}