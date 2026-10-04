#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Kan\NkOpendata\Client;

$client = new Client();

$races = $client->races()->list();

printf("Races (%d, newest first):\n", count($races));
printf("  %-28s  %-10s  %-10s  %s\n", 'NAME', 'START', 'END', 'SCORES');
printf("  %-28s  %-10s  %-10s  %s\n", str_repeat('-', 28), str_repeat('-', 10), str_repeat('-', 10), '------');

foreach (array_slice($races, 0, 5) as $race) {
    printf(
        "  %-28s  %-10s  %-10s  %d\n",
        mb_substr($race->name, 0, 28),
        $race->start->format('Y-m-d'),
        $race->end->format('Y-m-d'),
        $race->totalScores,
    );
}

$race = $races[0];

printf(
    "\nLeaderboard for %s (%d entries, lower score is better):\n",
    $race->name,
    count($entries = $client->races($race->id)->leaderboard()),
);

printf("  %-4s  %-24s  %s\n", '#', 'PLAYER', 'SCORE');
printf("  %-4s  %-24s  %s\n", '---', str_repeat('-', 24), '-----');

foreach (array_slice($entries, 0, 10) as $rank => $entry) {
    printf("  #%-3d  %-24s  %d\n", $rank + 1, mb_substr($entry->name, 0, 24), $entry->score);
}

// How the score is composed. The extra parts are the real differentiator
// between races, so this is worth printing.
$winner = $entries[0];

printf("\nScore breakdown for %s (total %d):\n", $winner->name, $winner->score);

foreach ($winner->scoreParts as $part) {
    printf("  %-28s  %-8s  %d\n", $part->name, $part->type, $part->score);
}

printf("  %d score parts in total\n", count($winner->scoreParts));

// The average of every submitted score. Note that leaderboard() returns a plain
// array, not a Collection: Collections are only used for nested lists inside a
// DTO, such as scoreParts.
$total = array_sum(array_map(
    static fn ($entry): int => $entry->score,
    $entries,
));

printf("\nAverage score across the page: %d\n", intdiv($total, count($entries)));

// A leaderboard entry carries a profile URL whose last path segment is the user
// ID that users()->find() expects. This helper comes up constantly.
$userId = basename(parse_url($winner->profile, PHP_URL_PATH));
$user = $client->users()->find($userId);

printf(
    "\nWinner profile:\n  %s — rank %d, veteran %d, %d followers\n",
    $user->name,
    $user->rank,
    $user->veteranRank,
    $user->followers,
);

printf("  highest round: %d (CHIMPS %d, Deflation %d)\n", $user->gameplay->highestRound, $user->gameplay->highestRoundCHIMPS, $user->gameplay->highestRoundDeflation);

// The name on the leaderboard is a snapshot from submission time, the profile
// name is live. They differ whenever a player has renamed since.
printf(
    "  name at submission: %s / current: %s%s\n",
    $winner->name,
    $user->name,
    $winner->name === $user->name ? '' : '  (renamed)',
);

exit(0);
