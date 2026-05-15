<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\Guild;

class GuildResource extends Resource {
    public function find(string $id): Guild {
        return $this->get("guild/{$id}", Guild::class);
    }
}