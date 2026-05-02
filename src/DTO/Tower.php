<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Enums\Tower as TowerEnum;

class Tower implements DTOInterface {
    public function __construct(
        public TowerEnum $tower,
        public int $max,
        public int $path1NumBlockedTiers,
        public int $path2NumBlockedTiers,
        public int $path3NumBlockedTiers,
        public bool $isHero
    ) {}
}