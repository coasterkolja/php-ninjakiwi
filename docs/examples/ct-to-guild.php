#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Kan\NkOpendata\Client;
use Kan\NkOpendata\Exceptions\ApiException;

$client = new Client();

/**
 * Every identity payload in the API carries an absolute URL whose last path
 * segment is the ID of the linked resource. This one helper covers all of them.
 */
$idFromUrl = static fn (string $url): string => basename(parse_url($url, PHP_URL_PATH));

// Find a CT event that actually has scores. A freshly opened event answers
// {"error":"No Scores Available"}, so walking to the newest one is not enough.
$event = null;

foreach ($client->ct()->list() as $candidate) {
    if ($candidate->totalScoresTeam > 0) {
        $event = $candidate;
        break;
    }
}

if ($event === null) {
    fwrite(STDERR, "No CT event with team scores found.\n");
    exit(1);
}

printf("CT event %s (team scores: %d)\n", $event->id, $event->totalScoresTeam);
printf("  %s -> %s\n\n", $event->start->format('Y-m-d H:i'), $event->end->format('Y-m-d H:i'));

$ct = $client->ct($event->id);

$teams = $ct->leaderboard()->team();
printf("Team leaderboard (%d entries)\n", count($teams));
printf("  %-4s  %-28s  %s\n", '#', 'TEAM', 'SCORE');
printf("  %-4s  %-28s  %s\n", '---', str_repeat('-', 28), '-----');

foreach (array_slice($teams, 0, 5) as $rank => $team) {
    printf("  #%-3d  %-28s  %d\n", $rank + 1, mb_substr($team->name, 0, 28), $team->score);
}

$team = $teams[0];

// A CT team is backed by a guild, and $team->profile points at it.
printf("\nTop team %s\n", $team->name);
printf("  profile URL  %s\n", $team->profile);
printf("  group URL    %s\n", $team->group);

$guild = $client->guild()->find($idFromUrl($team->profile));

printf("\nGuild\n");
printf("  %-16s %s\n", 'name', $guild->name);
printf("  %-16s %d\n", 'members', $guild->numMembers);
printf("  %-16s %s\n", 'status', $guild->status);
printf("  %-16s %s\n", 'icon', $guild->icon ?? '(none)');
printf("  %-16s %s\n", 'icon url', $guild->iconUrl);

// $guild->owner is a profile URL, not a display name. Easy to get wrong: it is a
// perfectly valid string, so it prints happily as a URL if you do.
printf("  %-16s %s\n", 'owner URL', $guild->owner);

$owner = $client->users()->find($idFromUrl($guild->owner));

printf("\nGuild owner\n");
printf("  %-16s %s\n", 'name', $owner->name);
printf("  %-16s %d\n", 'rank', $owner->rank);
printf("  %-16s %s\n", 'followers', number_format($owner->followers));
printf("  %-16s %d\n", 'highest round', $owner->gameplay->highestRound);

// Each team belongs to a group. Group data only exists while it is live, so an
// archived event answers {"error":"No Group Available"}.
$groupId = $idFromUrl($team->group);

printf("\nGroup %s\n", $groupId);

try {
    $members = $ct->leaderboard()->group($groupId);

    printf("  %d entries\n", count($members));

    foreach (array_slice($members, 0, 5) as $member) {
        printf("    %-28s  %d\n", mb_substr($member->name, 0, 28), $member->score);
    }
} catch (ApiException $e) {
    printf("  unavailable: %s\n", $e->getMessage());
    printf("  group data is only served while the event is live\n");
}

// The player leaderboard points at users rather than guilds.
printf("\nPlayer leaderboard\n");

$players = $ct->leaderboard()->player();

foreach (array_slice($players, 0, 3) as $rank => $player) {
    printf("  #%-3d  %-28s  %d\n", $rank + 1, mb_substr($player->name, 0, 28), $player->score);
}

$top = $client->users()->find($idFromUrl($players[0]->profile));

printf("\n  top player profile: %s (rank %d)\n", $top->name, $top->rank);

// Fail loudly on a wrong path shape: the endpoint is guild/, not guilds/.
printf("\nWrong path shape:\n");

try {
    $client->guild()->find('not-a-guild');
} catch (ApiException $e) {
    printf("  guild/         -> %s (code %d)\n", $e->getMessage(), $e->getCode());
}

exit(0);
