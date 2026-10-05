#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Kan\NkOpendata\Client;
use Kan\NkOpendata\DTO\Tile;
use Kan\NkOpendata\Enums\GameType;
use Kan\NkOpendata\Exceptions\ApiException;

$client = new Client();

// The current event, or the most recent one if none is running.
try {
    $event = $client->ct()->current();
    printf("Current event: %s\n", $event->id);
} catch (ApiException) {
    $event = $client->ct()->recent();
    printf("No event running. Most recent: %s\n", $event->id);
}

printf(
    "  %s -> %s\n",
    $event->start->format('Y-m-d H:i'),
    $event->end->format('Y-m-d H:i'),
);

$remaining = $event->end->getTimestamp() - time();
if ($remaining > 0) {
    printf("  ends in %dh %dm\n", intdiv($remaining, 3600), intdiv($remaining % 3600, 60));
} else {
    printf("  ended %dh ago\n", intdiv(-$remaining, 3600));
}

printf("  %d player scores, %d team scores\n\n", $event->totalScoresPlayer, $event->totalScoresTeam);

// The tile map. This is the big response: roughly 170 tiles.
$tiles = $client->ct($event->id)->tiles();
printf("Tiles: %d\n\n", count($tiles));

printf("  %-4s  %-32s  %s\n", 'ID', 'TYPE', 'GAME TYPE');
printf("  %-4s  %-32s  %s\n", '---', '---', '---');

foreach (array_slice($tiles, 0, 12) as $tile) {
    printf("  %-4s  %-32s  %s\n", $tile->id, $tile->type, $tile->gameType->value);
}

printf("  … %d more\n\n", max(0, count($tiles) - 12));

// Tally by scoring mode.
$counts = [];
foreach ($tiles as $tile) {
    $key = $tile->gameType->value;
    $counts[$key] = ($counts[$key] ?? 0) + 1;
}

arsort($counts);

printf("Tiles per game type:\n");
foreach ($counts as $mode => $count) {
    printf("  %-12s %3d\n", $mode, $count);
}

printf("\nBoss tiles:\n");

$bosses = array_filter($tiles, static fn (Tile $t): bool => $t->gameType === GameType::Boss);
printf("  %s\n", $bosses === [] ? 'none' : implode(', ', array_map(
    static fn (Tile $t): string => $t->id,
    $bosses,
)));

// Leaderboards are empty for a few hours after an event opens. That is a normal
// data condition, so it is caught rather than allowed to abort the script.
printf("\nLeaderboard:\n");

try {
    $players = $client->ct($event->id)->leaderboard()->player();

    foreach (array_slice($players, 0, 5) as $rank => $entry) {
        printf("  #%d  %-24s %d\n", $rank + 1, $entry->name, $entry->score);
    }
} catch (ApiException $e) {
    printf("  unavailable: %s\n", $e->getMessage());
}

exit(0);
