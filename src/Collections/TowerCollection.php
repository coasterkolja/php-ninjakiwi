<?php

namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\DTO\Tower;
use Kan\NkOpendata\Hydrator\Hydrator;

class TowerCollection extends Collection {
    public function __construct(array $items) {
        parent::__construct(array_map(
            fn($item) => Hydrator::hydrate(Tower::class, $item),
            $items
        ));
    }
}