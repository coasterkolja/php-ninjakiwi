<?php

namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\Hydrator\Hydrator;
use Override;

class Collection implements \IteratorAggregate, \Countable {
    protected array $items;

    public function __construct(array $items) {
        $this->items = $items;
    }

    public function toArray(): array {
        return $this->items;
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }

    #[Override]
    public function count(): int
    {
        return count($this->items);
    }
}