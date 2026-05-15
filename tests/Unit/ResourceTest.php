<?php

use Kan\NkOpendata\DTO\BossEvent;
use Kan\NkOpendata\DTO\CtEvent;
use Kan\NkOpendata\DTO\Race;
use Kan\NkOpendata\Exceptions\ApiException;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Resources\BossResource;
use Kan\NkOpendata\Resources\CtResource;
use Kan\NkOpendata\Resources\GuildResource;
use Kan\NkOpendata\Resources\RaceResource;
use Kan\NkOpendata\Resources\UserResource;
use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
{
    private function mockHttp(array $responseBody): HttpClient
    {
        $http = $this->createStub(HttpClient::class);
        $http->method('get')
            ->willReturn([
                'success' => true,
                'body' => $responseBody,
            ]);

        return $http;
    }

    private function captureHttp(callable $assertion): HttpClient
    {
        $http = $this->createStub(HttpClient::class);
        $http->method('get')
            ->willReturnCallback(fn(string $uri) => $assertion($uri));

        return $http;
    }

    // --- Basic Resource methods ---

    public function test_resource_get_hydrates_dto(): void
    {
        $http = $this->mockHttp([
            'id' => 'race_1',
            'name' => 'Test Race',
            'start' => 1700000000000,
            'end' => 1700086400000,
            'totalScores' => 100,
            'leaderboard' => '',
            'metadata' => '',
        ]);

        $resource = new class ($http) extends \Kan\NkOpendata\Resources\Resource {
            public function fetch(string $uri): \Kan\NkOpendata\DTO\DTOInterface
            {
                return $this->get($uri, Race::class);
            }
        };

        $result = $resource->fetch('test');
        $this->assertInstanceOf(Race::class, $result);
        $this->assertSame('Test Race', $result->name);
    }

    public function test_resource_map_hydrates_collection(): void
    {
        $http = $this->mockHttp([
            ['id' => '1', 'name' => 'Event 1', 'start' => 1700000000000, 'end' => 1700086400000, 'bossType' => 'Vortex', 'bossTypeURL' => '', 'totalScores_standard' => 0, 'totalScores_elite' => 0, 'leaderboard_standard_players_1' => '', 'leaderboard_elite_players_1' => '', 'metadataStandard' => '', 'metadataElite' => '', 'normalScoringType' => '', 'eliteScoringType' => ''],
            ['id' => '2', 'name' => 'Event 2', 'start' => 1700000000000, 'end' => 1700086400000, 'bossType' => 'Blastapopoulos', 'bossTypeURL' => '', 'totalScores_standard' => 0, 'totalScores_elite' => 0, 'leaderboard_standard_players_1' => '', 'leaderboard_elite_players_1' => '', 'metadataStandard' => '', 'metadataElite' => '', 'normalScoringType' => '', 'eliteScoringType' => ''],
        ]);

        $resource = new class ($http) extends \Kan\NkOpendata\Resources\Resource {
            public function fetchAll(string $uri): array
            {
                return $this->map($uri, BossEvent::class);
            }
        };

        $result = $resource->fetchAll('bosses');
        $this->assertCount(2, $result);
        $this->assertInstanceOf(BossEvent::class, $result[0]);
        $this->assertSame('Event 2', $result[1]->name);
    }

    public function test_resource_extract_returns_specific_key(): void
    {
        $http = $this->mockHttp(['tiles' => ['tile1', 'tile2']]);

        $resource = new class ($http) extends \Kan\NkOpendata\Resources\Resource {
            public function pull(string $uri, string $key): mixed
            {
                return $this->extract($uri, $key);
            }
        };

        $result = $resource->pull('ct/1/tiles', 'tiles');
        $this->assertSame(['tile1', 'tile2'], $result);
    }

    public function test_resource_extract_missing_key_returns_null(): void
    {
        $http = $this->mockHttp(['other' => 'value']);

        $resource = new class ($http) extends \Kan\NkOpendata\Resources\Resource {
            public function pull(string $uri, string $key): mixed
            {
                return $this->extract($uri, $key);
            }
        };

        $this->assertNull($resource->pull('test', 'nonexistent'));
    }

    public function test_resource_transform_applies_callback(): void
    {
        $http = $this->mockHttp(['items' => [1, 2, 3]]);

        $resource = new class ($http) extends \Kan\NkOpendata\Resources\Resource {
            public function process(string $uri, callable $callback): mixed
            {
                return $this->transform($uri, $callback);
            }
        };

        $result = $resource->process('test', fn(array $body) => $body['items']);
        $this->assertSame([1, 2, 3], $result);
    }

    // --- BossResource tests ---

    public function test_boss_resource_list_correct_uri(): void
    {
        $http = $this->captureHttp(function (string $uri) {
            $this->assertSame('bosses', $uri);

            return ['success' => true, 'body' => []];
        });

        $resource = new BossResource($http);
        $resource->list();
    }

    public function test_boss_resource_metadata_requires_id(): void
    {
        $this->expectException(\LogicException::class);

        $http = $this->createStub(HttpClient::class);
        $resource = new BossResource($http);
        $resource->metadata();
    }

    // --- CtResource tests ---

    public function test_ct_resource_list_correct_uri(): void
    {
        $http = $this->captureHttp(function (string $uri) {
            $this->assertSame('ct', $uri);

            return ['success' => true, 'body' => []];
        });

        $resource = new CtResource($http);
        $resource->list();
    }

    public function test_ct_resource_current_returns_active_event(): void
    {
        $future = (time() + 86400) * 1000;
        $http = $this->mockHttp([
            [
                'id' => 'ct_1',
                'start' => (time() - 3600) * 1000,
                'end' => $future,
                'totalScores_player' => 100,
                'totalScores_team' => 200,
                'tiles' => '',
                'leaderboard_player' => '',
                'leaderboard_team' => '',
            ],
        ]);

        $resource = new CtResource($http);
        $event = $resource->current();

        $this->assertInstanceOf(CtEvent::class, $event);
        $this->assertSame('ct_1', $event->id);
    }

    public function test_ct_resource_current_throws_when_no_active_event(): void
    {
        $past = (time() - 86400) * 1000;
        $http = $this->mockHttp([
            [
                'id' => 'ct_old',
                'start' => $past - 86400000,
                'end' => $past,
                'totalScores_player' => 100,
                'totalScores_team' => 200,
                'tiles' => '',
                'leaderboard_player' => '',
                'leaderboard_team' => '',
            ],
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('No currently active event');

        $resource = new CtResource($http);
        $resource->current();
    }

    public function test_ct_resource_tiles_requires_id(): void
    {
        $this->expectException(\LogicException::class);

        $http = $this->createStub(HttpClient::class);
        $resource = new CtResource($http);
        $resource->tiles();
    }

    // --- RaceResource tests ---

    public function test_race_resource_list_correct_uri(): void
    {
        $http = $this->captureHttp(function (string $uri) {
            $this->assertSame('races', $uri);

            return ['success' => true, 'body' => []];
        });

        $resource = new RaceResource($http);
        $resource->list();
    }

    public function test_race_resource_leaderboard_requires_id(): void
    {
        $this->expectException(\LogicException::class);

        $http = $this->createStub(HttpClient::class);
        $resource = new RaceResource($http);
        $resource->leaderboard();
    }

    // --- UserResource tests ---

    public function test_user_resource_find_correct_uri(): void
    {
        $http = $this->captureHttp(function (string $uri) {
            $this->assertSame('users/testuser', $uri);

            return ['success' => true, 'body' => [
                'displayName' => 'TestUser',
                'rank' => 1,
                'veteranRank' => 0,
                'achievements' => '',
                'mostExperiencedMonkey' => '',
                'avatar' => '',
                'banner' => '',
                'avatarURL' => '',
                'bannerURL' => '',
                'followers' => 0,
                'bloonsPopped' => ['badsPopped' => 0, 'ddtsPopped' => 0, 'bfbsPopped' => 0, 'bossesPopped' => 0, 'bloonsPopped' => 0, 'bloonsLeaked' => 0, 'camosPopped' => 0, 'ceramicsPopped' => 0, 'coopBloonsPopped' => 0, 'goldenBloonsPopped' => 0, 'leadsPopped' => 0, 'moabsPopped' => 0, 'necroBloonsReanimated' => 0, 'transformingTonicsUsed' => 0, 'purplesPopped' => 0, 'regrowsPopped' => 0, 'zomgsPopped' => 0],
                'gameplay' => ['cashEarned' => 0, 'challengesCompleted' => 0, 'collectionChestsOpened' => 0, 'coopCashGiven' => 0, 'dailyRewards' => 0, 'gameCount' => 0, 'gamesWon' => 0, 'highestRound' => 0, 'highestRoundCHIMPS' => 0, 'highestRoundDeflation' => 0, 'instaMonkeyCollection' => 0, 'monkeyTeamsWins' => 0, 'powersUsed' => 0, 'totalOdysseysCompleted' => 0, 'totalOdysseyStars' => 0, 'totalTrophiesEarned' => 0, 'damageDoneToBosses' => 0, 'instaMonkeysUsed' => 0, 'abilitiesUsed' => 0, 'monkeysPlaced' => 0],
                'heroesPlaced' => ['AdmiralBrickell' => 0, 'Adora' => 0, 'Benjamin' => 0, 'Etienne' => 0, 'Geraldo' => 0, 'Gwendolin' => 0, 'ObynGreenfoot' => 0, 'PatFusty' => 0, 'Psi' => 0, 'Quincy' => 0, 'Sauda' => 0, 'StrikerJones' => 0, 'Ezili' => 0, 'CaptainChurchill' => 0, 'Corvus' => 0, 'Rosalia' => 0],
                'towersPlaced' => ['DartMonkey' => 0, 'BombShooter' => 0, 'NinjaMonkey' => 0, 'SuperMonkey' => 0, 'WizardMonkey' => 0, 'BananaFarm' => 0, 'MonkeyVillage' => 0, 'HeliPilot' => 0, 'Druid' => 0, 'EngineerMonkey' => 0, 'MortarMonkey' => 0, 'Alchemist' => 0, 'MonkeyAce' => 0, 'BoomerangMonkey' => 0, 'SpikeFactory' => 0, 'GlueGunner' => 0, 'TackShooter' => 0, 'MonkeyBuccaneer' => 0, 'MonkeySub' => 0, 'DartlingGunner' => 0, 'BeastHandler' => 0, 'IceMonkey' => 0, 'SniperMonkey' => 0, 'Mermonkey' => 0, 'Desperado' => 0],
                'stats' => [],
                'bossBadgesNormal' => [],
                'bossBadgesElite' => [],
                '_medalsSinglePlayer' => [],
                '_medalsMultiplayer' => [],
                '_medalsBoss' => [],
                '_medalsBossElite' => [],
                '_medalsCTLocal' => [],
                '_medalsCTGlobal' => [],
                '_medalsRace' => [],
            ]];
        });

        $resource = new UserResource($http);
        $user = $resource->find('testuser');

        $this->assertInstanceOf(\Kan\NkOpendata\DTO\User::class, $user);
        $this->assertSame('TestUser', $user->name);
    }

    // --- GuildResource tests ---

    public function test_guild_resource_find_correct_uri(): void
    {
        $http = $this->captureHttp(function (string $uri) {
            $this->assertSame('guild/testguild', $uri);

            return ['success' => true, 'body' => [
                'name' => 'Test Guild',
                'owner' => 'Owner',
                'numMembers' => 50,
                'status' => 'active',
                'bannerURL' => '',
                'frameURL' => '',
                'iconURL' => '',
            ]];
        });

        $resource = new GuildResource($http);
        $guild = $resource->find('testguild');

        $this->assertInstanceOf(\Kan\NkOpendata\DTO\Guild::class, $guild);
        $this->assertSame('Test Guild', $guild->name);
    }

    // --- BossLeaderboardResource error handling ---

    public function test_boss_leaderboard_team_invalid_size(): void
    {
        $http = $this->createStub(HttpClient::class);

        $resource = new \Kan\NkOpendata\Resources\BossLeaderboardResource($http, 'boss_1');

        $this->expectException(\InvalidArgumentException::class);
        $resource->team(5);
    }
}
