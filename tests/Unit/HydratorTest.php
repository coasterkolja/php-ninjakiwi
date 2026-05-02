<?php

use Kan\NkOpendata\DTO\CtEvent;
use Kan\NkOpendata\Hydrator\Hydrator;
use PhpUnit\Framework\TestCase;

final class HydratorTest extends TestCase {
    public function test_it_hydrates_ct_event_correctly() {
        $ct = Hydrator::hydrate(CtEvent::class, [
            'id' => '1',
            'name' => 'event name',
            'start' => (time() - 3600) * 1000,
            'end' => (time() + 3600) * 1000,
            'totalScores_player' => 100,
            'totalScores_team' => 200,
            'tiles' => "",
            'leaderboard_player' => "",
            'leaderboard_team' => ""
        ]);

        $this->assertSame('1', $ct->id);
        $this->assertInstanceOf(\DateTimeImmutable::class, $ct->start);
    }
}