#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Kan\NkOpendata\Client;
use Kan\NkOpendata\DTO\BossLeaderboard;
use Kan\NkOpendata\Resources\BossLeaderboardResource;

$client = new Client();

$bosses = $client->bosses()->list();

printf("Boss events (%d, newest first):\n", count($bosses));
printf(
    "  %-14s  %-14s  %-11s  %-11s  %s\n",
    'TYPE',
    'ID',
    'STD SCORE',
    'ELITE SCORE',
    'SCORING STD / ELITE',
);
printf(
    "  %-14s  %-14s  %-11s  %-11s  %s\n",
    str_repeat('-', 14),
    str_repeat('-', 14),
    str_repeat('-', 11),
    str_repeat('-', 11),
    str_repeat('-', 24),
);

foreach ($bosses as $boss) {
    printf(
        "  %-14s  %-14s  %-11d  %-11d  %s / %s\n",
        $boss->bossType,
        $boss->name,
        $boss->totalScoresStandard,
        $boss->totalScoresElite,
        $boss->scoringTypeStandard,
        $boss->scoringTypeElite,
    );
}

$boss = $bosses[0];

printf("\n%s (%s)\n", $boss->name, $boss->id);
printf("  %s -> %s\n", $boss->start->format('Y-m-d H:i'), $boss->end->format('Y-m-d H:i'));
printf("  icon: %s\n", $boss->bossTypeImage);

// Each difficulty has its own leaderboard. A resource is cheap, so build one
// per call rather than reusing a single instance: the difficulty selectors
// mutate the resource they are called on.
$standard = $client->bosses($boss->id)->leaderboard()->standard()->singleplayer();
$elite = $client->bosses($boss->id)->leaderboard()->elite()->singleplayer();

printf(
    "\nSingleplayer, %d standard and %d elite entries (lower score is better):\n\n",
    count($standard),
    count($elite),
);

$print = static function (string $label, array $entries): void {
    printf("  %s\n", $label);
    foreach (array_slice($entries, 0, 5) as $rank => $entry) {
        printf("    #%d  %-24s  %d\n", $rank + 1, mb_substr($entry->name, 0, 24), $entry->score);
    }
    printf("\n");
};

$print('Standard', $standard);
$print('Elite', $elite);

// Team leaderboards are a separate endpoint per team size, 1 to 4.
printf("Team leaderboards, standard:\n");

foreach ([1, 2, 3, 4] as $size) {
    $entries = $client->bosses($boss->id)->leaderboard()->standard()->team($size);
    $best = $entries[0] ?? null;

    printf(
        "  size %d: %2d entries, leader %s\n",
        $size,
        count($entries),
        $best instanceof BossLeaderboard ? sprintf('%-24s %d', mb_substr($best->name, 0, 24), $best->score) : '-',
    );
}

// Out of range is rejected before the request is made.
try {
    $client->bosses($boss->id)->leaderboard()->team(5);
} catch (InvalidArgumentException $e) {
    printf("\nteam(5): %s\n", $e->getMessage());
}

// The score parts are the only reliable way to tell how a leaderboard is scored.
$entry = $standard[0];

printf("\nScore breakdown for %s (total %d):\n", $entry->name, $entry->score);

foreach ($entry->scoreParts as $part) {
    printf("  %-14s  %-8s  %d\n", $part->name, $part->type, $part->score);
}

// submissionTime is -1 when upstream does not expose it, which is the norm here.
printf(
    "\n  submissionTime: %d%s\n",
    $entry->submissionTime,
    $entry->submissionTime === -1 ? ' (not exposed upstream)' : '',
);

// Challenge metadata. This is currently broken by an incomplete Tower enum.
printf("\nChallenge metadata:\n");

try {
    $metadata = $client->bosses($boss->id)->metadata()->standard();

    printf(
        "  %s on %s, rounds %d-%d, %d lives, %d cash\n",
        $metadata->name,
        $metadata->map,
        $metadata->startRound,
        $metadata->endRound,
        $metadata->lives,
        $metadata->startingCash,
    );
} catch (Throwable $e) {
    printf("  unavailable: %s: %s\n", $e::class, $e->getMessage());
    printf("  see docs/roadmap.md#incomplete-tower-enum\n");
}

exit(0);
