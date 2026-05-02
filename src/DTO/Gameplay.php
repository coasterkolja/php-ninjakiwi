<?php

namespace Kan\NkOpendata\DTO;

class Gameplay implements DTOInterface
{
    public function __construct(
        public int $cashEarned,
        public int $challengesCompleted,
        public int $collectionChestsOpened,
        public int $coopCashGiven,
        public int $dailyRewards,
        public int $gameCount,
        public int $gamesWon,
        public int $highestRound,
        public int $highestRoundCHIMPS,
        public int $highestRoundDeflation,
        public int $instaMonkeyCollection,
        public int $monkeyTeamsWins,
        public int $powersUsed,
        public int $totalOdysseysCompleted,
        public int $totalOdysseyStars,
        public int $totalTrophiesEarned,
        public int $damageDoneToBosses,
        public int $instaMonkeysUsed,
        public int $abilitiesUsed,
        public int $monkeysPlaced
    ) {}
}