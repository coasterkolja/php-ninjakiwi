<?php

namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\DTO\ScorePart;
use Kan\NkOpendata\Hydrator\Hydrator;

/** @extends Collection<int, ScorePart> */
class ScorePartCollection extends Collection
{
    /** @param array<int, array<string, mixed>> $items */
    public function __construct(array $items) {
        parent::__construct(array_map(
            fn (array $item): ScorePart => Hydrator::hydrate(ScorePart::class, $item),
            $items
        ));
    }
}