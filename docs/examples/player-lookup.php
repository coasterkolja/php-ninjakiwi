#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Kan\NkOpendata\Client;
use Kan\NkOpendata\Enums\Tower;
use Kan\NkOpendata\Exceptions\ApiException;

$client = new Client();

// The API has no user list and no name lookup, so start from a leaderboard and
// follow the profile URL. In a real application this is the only way in.
$race = $client->races()->list()[0];
$entries = $client->races($race->id)->leaderboard();
$entry = $entries[0];

printf("Starting from %s on %s\n\n", $entry->name, $race->name);

// A leaderboard entry's profile URL ends in the user ID users()->find() expects.
$userId = basename(parse_url($entry->profile, PHP_URL_PATH));
$user = $client->users()->find($userId);

printf("Profile\n");
printf("  %-22s %s\n", 'name', $user->name);
printf("  %-22s %d\n", 'rank', $user->rank);
printf("  %-22s %d\n", 'veteran rank', $user->veteranRank);
printf("  %-22s %s\n", 'followers', number_format($user->followers));
printf("  %-22s %s\n", 'achievements', $user->achievements);
printf("  %-22s %s\n", 'most played', $user->mostExperiencedMonkey);
printf("  %-22s %s\n", 'avatar', $user->avatarUrl);
printf("\n");

$game = $user->gameplay;

printf("Gameplay\n");
printf("  %-22s %s\n", 'games played', number_format($game->gameCount));
printf("  %-22s %s (%s%%)\n", 'games won', number_format($game->gamesWon), $game->gameCount > 0 ? round($game->gamesWon / $game->gameCount * 100, 1) : '0');
printf("  %-22s %d\n", 'highest round', $game->highestRound);
printf("  %-22s %d\n", 'highest CHIMPS', $game->highestRoundCHIMPS);
printf("  %-22s %d\n", 'highest Deflation', $game->highestRoundDeflation);
printf("  %-22s %s\n", 'cash earned', number_format($game->cashEarned));
printf("  %-22s %s\n", 'monkeys placed', number_format($game->monkeysPlaced));
printf("  %-22s %s\n", 'odyssey stars', number_format($game->totalOdysseyStars));
printf("\n");

$popped = $user->bloonsPopped;

printf("Bloons popped\n");
printf("  %-22s %s\n", 'regular', number_format($popped->bloonsPopped));
printf("  %-22s %s\n", 'MOAB class', number_format($popped->moabsPopped));
printf("  %-22s %s\n", 'BFB', number_format($popped->bfbsPopped));
printf("  %-22s %d\n", 'ZOMG', $popped->zomgsPopped);
printf("  %-22s %s\n", 'leaked', number_format($popped->bloonsLeaked));
printf(
    "  %-22s %s\n",
    'camo popped',
    number_format($popped->camosPopped),
);
printf("\n");

// heroesPlaced and towersPlaced expose get(Tower) so a loop can stay generic.
// get() throws for the wrong category, which is how the two are kept apart.
printf("Most played heroes\n");

$heroes = [];

foreach (Tower::cases() as $tower) {
    try {
        $games = $user->heroesPlaced->get($tower);
    } catch (InvalidArgumentException) {
        continue;   // A primary monkey, not a hero.
    }

    if ($games > 0) {
        $heroes[$tower->value] = $games;
    }
}

arsort($heroes);

foreach (array_slice($heroes, 0, 5, true) as $name => $games) {
    printf("  %-22s %s\n", $name, number_format($games));
}

printf("\nMost played towers\n");

$towers = [];

foreach (Tower::cases() as $tower) {
    try {
        $games = $user->towersPlaced->get($tower);
    } catch (InvalidArgumentException) {
        continue;   // A hero, not a primary tower.
    }

    if ($games > 0) {
        $towers[$tower->value] = $games;
    }
}

arsort($towers);

foreach (array_slice($towers, 0, 5, true) as $name => $games) {
    printf("  %-22s %s\n", $name, number_format($games));
}

printf(
    "\n  note: these totals are incomplete. The live API reports 45 distinct\n" .
    "  towers but the Tower enum defines 43: DanDMonke and Skywarden are missing,\n" .
    "  so they cannot be looked up. See docs/roadmap.md#incomplete-tower-enum\n",
);

// Medal and badge maps are plain arrays keyed by API-defined names, which
// upstream reshuffles between game updates. Read them at runtime.
printf("\nMedals\n");

foreach ([
    'singleplayer' => $user->medalsSingleplayer,
    'multiplayer'  => $user->medalsMultiplayer,
    'race'         => $user->medalsRace,
    'ct local'     => $user->medalsCtLocal,
    'ct global'    => $user->medalsCtGlobal,
    'boss normal'  => $user->medalsBossNormal,
    'boss elite'   => $user->medalsTeamElite,
] as $label => $medals) {
    $total = array_sum($medals);
    printf("  %-14s %d medals across %d categories\n", $label, $total, count($medals));
}

printf("\nBoss badges\n");

foreach (['standard' => $user->bossBadgesNormal, 'elite' => $user->bossBadgesElite] as $label => $badges) {
    arsort($badges);
    $top = array_slice($badges, 0, 3, true);

    printf(
        "  %-10s %s\n",
        $label,
        implode(', ', array_map(
            static fn (string $boss, int $count): string => sprintf('%s %d', $boss, $count),
            array_keys($top),
            $top,
        )),
    );
}

// Errors are uniform, so the call site is what identifies the cause.
printf("\nError handling\n");

try {
    $client->users()->find('this-is-not-a-valid-hash');
} catch (ApiException $e) {
    printf("  bad user id -> ApiException: %s\n", $e->getMessage());
}

exit(0);
