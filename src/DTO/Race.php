<?php

namespace Kan\NkOpendata\DTO;

class Race implements DTOInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public int $totalScores,
        public string $leaderboard,
        public string $metadata
    ) {}
}