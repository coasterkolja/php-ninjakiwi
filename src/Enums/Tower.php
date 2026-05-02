<?php

namespace Kan\NkOpendata\Enums;

enum Tower: string {
    case ChosenPrimaryHero = 'ChosenPrimaryHero';

    // HEROES
    case Quincy = 'Quincy';
    case Gwendolin = 'Gwendolin';
    case StrikerJones = 'StrikerJones';
    case ObynGreenfoot = 'ObynGreenfoot';
    case CaptainChurchill = 'CaptainChurchill';
    case Benjamin = 'Benjamin';
    case Ezili = 'Ezili';
    case PatFusty = 'PatFusty';
    case Adora = 'Adora';
    case AdmiralBrickell = 'AdmiralBrickell';
    case Etienne = 'Etienne';
    case Sauda = 'Sauda';
    case Psi = 'Psi';
    case Geraldo = 'Geraldo';
    case Corvus = 'Corvus';
    case Rosalia = 'Rosalia';
    case Silas = 'Silas';
    
    // PRIMARY
    case DartMonkey = 'DartMonkey';
    case BoomerangMonkey = 'BoomerangMonkey';
    case BombShooter = 'BombShooter';
    case TackShooter = 'TackShooter';
    case IceMonkey = 'IceMonkey';
    case GlueGunner = 'GlueGunner';
    case Desperado = 'Desperado';

    // MILITIARY
    case SniperMonkey = 'SniperMonkey';
    case MonkeySub = 'MonkeySub';
    case MonkeyBuccaneer = 'MonkeyBuccaneer';
    case MonkeyAce = 'MonkeyAce';
    case HeliPilot = 'HeliPilot';
    case MortarMonkey = 'MortarMonkey';
    case DartlingGunner = 'DartlingGunner';
    
    // MAGIC
    case WizardMonkey = 'WizardMonkey';
    case SuperMonkey = 'SuperMonkey';
    case NinjaMonkey = 'NinjaMonkey';
    case Alchemist = 'Alchemist';
    case Druid = 'Druid';
    case Mermonkey = 'Mermonkey';
    
    // SUPPORT
    case BananaFarm = 'BananaFarm';
    case SpikeFactory = 'SpikeFactory';
    case MonkeyVillage = 'MonkeyVillage';
    case EngineerMonkey = 'EngineerMonkey';
    case BeastHandler = 'BeastHandler';
}