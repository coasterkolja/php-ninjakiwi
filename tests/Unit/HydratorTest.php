<?php

use Kan\NkOpendata\Collections\ScorePartCollection;
use Kan\NkOpendata\Collections\TowerCollection;
use Kan\NkOpendata\DTO\BloonModifiers;
use Kan\NkOpendata\DTO\BossEvent;
use Kan\NkOpendata\DTO\CtEvent;
use Kan\NkOpendata\DTO\Race;
use Kan\NkOpendata\DTO\Tile;
use Kan\NkOpendata\Enums\TileGameType;
use Kan\NkOpendata\Hydrator\Hydrator;
use PHPUnit\Framework\TestCase;

final class HydratorTest extends TestCase
{
    public function test_hydrate_simple_dto(): void
    {
        $data = [
            'id' => 'race_123',
            'name' => 'Test Race',
            'start' => 1700000000000,
            'end' => 1700086400000,
            'totalScores' => 5000,
            'leaderboard' => 'https://data.ninjakiwi.com/btd6/races/race_123/leaderboard',
            'metadata' => 'https://data.ninjakiwi.com/btd6/races/race_123/metadata',
        ];

        $race = Hydrator::hydrate(Race::class, $data);

        $this->assertInstanceOf(Race::class, $race);
        $this->assertSame('race_123', $race->id);
        $this->assertSame('Test Race', $race->name);
        $this->assertSame(5000, $race->totalScores);
        $this->assertInstanceOf(\DateTimeImmutable::class, $race->start);
        $this->assertSame(1700000000, $race->start->getTimestamp());
    }

    public function test_hydrate_with_map_from_attribute(): void
    {
        $now = time() * 1000;
        $data = [
            'id' => 'ct_1',
            'start' => $now,
            'end' => $now + 86400000,
            'totalScores_player' => 100,
            'totalScores_team' => 200,
            'tiles' => '',
            'leaderboard_player' => 'https://data.ninjakiwi.com/btd6/ct/ct_1/leaderboard/player',
            'leaderboard_team' => 'https://data.ninjakiwi.com/btd6/ct/ct_1/leaderboard/team',
        ];

        $event = Hydrator::hydrate(CtEvent::class, $data);

        $this->assertSame(100, $event->totalScoresPlayer);
        $this->assertSame(200, $event->totalScoresTeam);
    }

    public function test_hydrate_with_map_from_for_boss_event(): void
    {
        $now = time() * 1000;
        $data = [
            'id' => 'boss_1',
            'name' => 'Boss Event',
            'start' => $now,
            'end' => $now + 86400000,
            'bossType' => 'Vortex',
            'bossTypeURL' => 'https://data.ninjakiwi.com/images/bosses/vortex.png',
            'totalScores_standard' => 1000,
            'totalScores_elite' => 500,
            'leaderboard_standard_players_1' => 'https://data.ninjakiwi.com/btd6/bosses/boss_1/leaderboard/standard/1',
            'leaderboard_elite_players_1' => 'https://data.ninjakiwi.com/btd6/bosses/boss_1/leaderboard/elite/1',
            'metadataStandard' => 'https://data.ninjakiwi.com/btd6/bosses/boss_1/metadata/standard',
            'metadataElite' => 'https://data.ninjakiwi.com/btd6/bosses/boss_1/metadata/elite',
            'normalScoringType' => 'time',
            'eliteScoringType' => 'time',
        ];

        $event = Hydrator::hydrate(BossEvent::class, $data);

        $this->assertSame('Vortex', $event->bossType);
        $this->assertStringContainsString('vortex.png', $event->bossTypeImage);
        $this->assertSame(1000, $event->totalScoresStandard);
        $this->assertSame(500, $event->totalScoresElite);
        $this->assertSame('time', $event->scoringTypeStandard);
        $this->assertSame('time', $event->scoringTypeElite);
    }

    public function test_hydrate_with_enum(): void
    {
        $data = [
            'id' => 'tile_1',
            'type' => 'water',
            'gameType' => 'LeastCash',
        ];

        $tile = Hydrator::hydrate(Tile::class, $data);

        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertSame('tile_1', $tile->id);
        $this->assertInstanceOf(TileGameType::class, $tile->gameType);
        $this->assertSame(TileGameType::LeastCash, $tile->gameType);
    }

    public function test_hydrate_with_nested_dto(): void
    {
        $data = [
            'speedMultiplier' => 1.5,
            'moabSpeedMultiplier' => 1.2,
            'bossSpeedMultiplier' => 1.0,
            'regrowRateMultiplier' => 0.5,
            'healthMultipliers' => [1.0, 1.5, 2.0],
            'allCamo' => true,
            'allRegen' => false,
        ];

        $modifiers = Hydrator::hydrate(BloonModifiers::class, $data);

        $this->assertSame(1.5, $modifiers->speedMultiplier);
        $this->assertSame([1.0, 1.5, 2.0], $modifiers->healthMultipliers);
        $this->assertTrue($modifiers->allCamo);
        $this->assertFalse($modifiers->allRegen);
    }

    public function test_hydrate_with_collection(): void
    {
        $data = [
            'displayName' => 'Player1',
            'score' => 50000,
            'scoreParts' => [
                ['type' => 'pop', 'score' => 10000, 'name' => 'Round 100'],
                ['type' => 'time', 'score' => 40000, 'name' => 'Time Bonus'],
            ],
            'submissionTime' => 1700000000,
            'profile' => 'https://data.ninjakiwi.com/user/player1',
        ];

        $entry = Hydrator::hydrate(\Kan\NkOpendata\DTO\BossLeaderboard::class, $data);

        $this->assertInstanceOf(ScorePartCollection::class, $entry->scoreParts);
        $this->assertCount(2, $entry->scoreParts);
    }

    public function test_hydrate_collection(): void
    {
        $data = [
            [
                'type' => 'pop',
                'score' => 100,
                'name' => 'Test',
            ],
            [
                'type' => 'time',
                'score' => 200,
                'name' => 'Bonus',
            ],
        ];

        $parts = Hydrator::hydrateCollection(\Kan\NkOpendata\DTO\ScorePart::class, $data);

        $this->assertCount(2, $parts);
        $this->assertInstanceOf(\Kan\NkOpendata\DTO\ScorePart::class, $parts[0]);
        $this->assertSame('pop', $parts[0]->type);
        $this->assertSame(100, $parts[0]->score);
        $this->assertSame('time', $parts[1]->type);
    }

    public function test_hydrate_with_nullable_optional_field(): void
    {
        $data = [
            'name' => 'Test Guild',
            'owner' => 'Player1',
            'numMembers' => 50,
            'status' => 'active',
            'bannerURL' => 'https://example.com/banner.png',
            'frameURL' => 'https://example.com/frame.png',
            'iconURL' => 'https://example.com/icon.png',
        ];

        $guild = Hydrator::hydrate(\Kan\NkOpendata\DTO\Guild::class, $data);

        $this->assertSame('Test Guild', $guild->name);
        $this->assertNull($guild->banner);
        $this->assertNull($guild->frame);
        $this->assertNull($guild->icon);
    }

    public function test_hydrate_missing_required_field_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: name');

        Hydrator::hydrate(\Kan\NkOpendata\DTO\Guild::class, []);
    }

    public function test_hydrate_datetime_immutable(): void
    {
        $data = [
            'id' => 'race_1',
            'name' => 'Race',
            'start' => 1700000000000,
            'end' => 1700086400000,
            'totalScores' => 100,
            'leaderboard' => '',
            'metadata' => '',
        ];

        $race = Hydrator::hydrate(Race::class, $data);

        $this->assertInstanceOf(\DateTimeImmutable::class, $race->start);
        $this->assertSame(1700000000, $race->start->getTimestamp());
        $this->assertInstanceOf(\DateTimeImmutable::class, $race->end);
        $this->assertSame(1700086400, $race->end->getTimestamp());
    }
}
