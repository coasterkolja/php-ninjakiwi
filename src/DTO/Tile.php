<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Enums\TileGameType;

class Tile implements DTOInterface {
    public function __construct(
        public string $id,
        public string $type,
        public TileGameType $gameType
    ) {}
}