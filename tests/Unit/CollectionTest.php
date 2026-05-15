<?php

use Kan\NkOpendata\Collections\Collection;
use Kan\NkOpendata\Collections\ScorePartCollection;
use Kan\NkOpendata\Collections\TowerCollection;
use Kan\NkOpendata\DTO\ScorePart;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function test_collection_is_countable(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertCount(3, $collection);
    }

    public function test_collection_is_iterable(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $result = [];
        foreach ($collection as $item) {
            $result[] = $item;
        }

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    public function test_collection_to_array(): void
    {
        $items = ['x' => 1, 'y' => 2];
        $collection = new Collection($items);

        $this->assertSame($items, $collection->toArray());
    }

    public function test_empty_collection(): void
    {
        $collection = new Collection([]);

        $this->assertCount(0, $collection);
        $this->assertSame([], $collection->toArray());
    }

    public function test_score_part_collection_hydrates_items(): void
    {
        $items = [
            ['type' => 'pop', 'score' => 100, 'name' => 'Round 1'],
            ['type' => 'time', 'score' => 200, 'name' => 'Time Bonus'],
        ];

        $collection = new ScorePartCollection($items);

        $this->assertCount(2, $collection);
        foreach ($collection as $part) {
            $this->assertInstanceOf(ScorePart::class, $part);
        }
    }

    public function test_score_part_collection_to_array_returns_hydrated_objects(): void
    {
        $items = [
            ['type' => 'pop', 'score' => 100, 'name' => 'Test'],
        ];

        $collection = new ScorePartCollection($items);
        $array = $collection->toArray();

        $this->assertCount(1, $array);
        $this->assertInstanceOf(ScorePart::class, $array[0]);
        $this->assertSame(100, $array[0]->score);
    }

    public function test_tower_collection_hydrates_items(): void
    {
        $items = [
            ['tower' => 'DartMonkey', 'max' => 1, 'path1NumBlockedTiers' => 0, 'path2NumBlockedTiers' => 0, 'path3NumBlockedTiers' => 0, 'isHero' => false],
            ['tower' => 'Quincy', 'max' => 1, 'path1NumBlockedTiers' => 0, 'path2NumBlockedTiers' => 0, 'path3NumBlockedTiers' => 0, 'isHero' => true],
        ];

        $collection = new TowerCollection($items);

        $this->assertCount(2, $collection);
        foreach ($collection as $tower) {
            $this->assertInstanceOf(\Kan\NkOpendata\DTO\Tower::class, $tower);
        }
    }
}
