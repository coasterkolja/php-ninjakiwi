<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Enums\GameType;

class Tile implements DTOInterface {
    public function __construct(
        public string $id,
        public string $type,
        public GameType $gameType
    ) {}
}
