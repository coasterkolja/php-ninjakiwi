<?php

namespace Kan\NkOpendata\DTO;

class ScorePart implements DTOInterface
{
    public function __construct(
        public string $type,
        public int $score,
        public string $name
    ) {}
}