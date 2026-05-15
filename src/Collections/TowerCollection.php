<?php

namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\DTO\Tower;
use Kan\NkOpendata\Hydrator\Hydrator;

/** @extends Collection<int, \Kan\NkOpendata\DTO\Tower> */
class TowerCollection extends Collection {
    /** @param array<int, array<string, mixed>> $items */
    public function __construct(array $items) {
        parent::__construct(array_map(
            fn (array $item): \Kan\NkOpendata\DTO\Tower => Hydrator::hydrate(\Kan\NkOpendata\DTO\Tower::class, $item),
            $items
        ));
    }
}