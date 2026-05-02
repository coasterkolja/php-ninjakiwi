<?php

namespace Kan\NkOpendata\DTO;

class BossScorePart implements DTOInterface
{
    public function __construct(
        public string $type,
        public int $score,
        public string $name
    ) {}
}