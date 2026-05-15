<?php

namespace Kan\NkOpendata\Collections;

use Kan\NkOpendata\Hydrator\Hydrator;
use Override;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements \IteratorAggregate<TKey, TValue>
 */
class Collection implements \IteratorAggregate, \Countable {
    /** @var array<TKey, TValue> */
    protected array $items;

    /** @param array<TKey, TValue> $items */
    public function __construct(array $items) {
        $this->items = $items;
    }

    /** @return array<TKey, TValue> */
    public function toArray(): array {
        return $this->items;
    }

    /** @return \ArrayIterator<TKey, TValue> */
    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }

    #[Override]
    public function count(): int
    {
        return count($this->items);
    }
}