<?php

namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\DTO\ScorePart;
use Kan\NkOpendata\Hydrator\Hydrator;

class ScorePartCollection extends Collection
{
    public function __construct(array $items) {
        parent::__construct(array_map(
            fn ($item) => Hydrator::hydrate(ScorePart::class, $item),
            $items
        ));
    }
}