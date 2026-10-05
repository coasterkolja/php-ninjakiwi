<?php

declare(strict_types=1);

/**
 * Regenerates docs/reference/dtos.md from the DTO classes in src/DTO.
 *
 * The DTO constructors are the single source of truth for the shape of every
 * response, so the reference table is derived from them instead of being
 * maintained by hand. Field descriptions are looked up in $descriptions below;
 * they are seeded from the "model" metadata the public API returns alongside
 * every payload, which keeps the prose in sync with upstream.
 *
 * Usage:
 *   php docs/tools/generate-dto-reference.php
 *   php docs/tools/generate-dto-reference.php --check   # CI: fail if stale
 */

use Kan\NkOpendata\Collections\Collection;
use Kan\NkOpendata\DTO\DTOInterface;
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

$check = in_array('--check', $argv, true);

/**
 * Field descriptions keyed by "DtoShortName::propertyName".
 *
 * Sourced from the `model.parameters.<field>.description` values published by
 * https://data.ninjakiwi.com/btd6/ and supplemented where the API is silent.
 */
$descriptions = [
    'BloonModifiers::speedMultiplier' => 'Global bloon speed multiplier applied to all bloon types.',
    'BloonModifiers::moabSpeedMultiplier' => 'Speed multiplier applied to MOAB-class bloons only.',
    'BloonModifiers::bossSpeedMultiplier' => 'Speed multiplier applied to bosses only.',
    'BloonModifiers::regrowRateMultiplier' => 'Multiplier applied to the regrow rate of regrow bloons.',
    'BloonModifiers::healthMultipliers' => 'Health multipliers keyed by `bloons`, `moabs` and `boss`. Returned by the API as an object, so this hydrates to an associative array.',
    'BloonModifiers::allCamo' => 'Whether all bloons are camo.',
    'BloonModifiers::allRegen' => 'Whether all bloons are regrow.',

    'BloonsPopped::badsPopped' => 'Count of Bad bloons popped.',
    'BloonsPopped::ddtsPopped' => 'Count of DDTs popped.',
    'BloonsPopped::bfbsPopped' => 'Count of BFBs popped.',
    'BloonsPopped::bossesPopped' => 'Count of bosses popped.',
    'BloonsPopped::bloonsPopped' => 'Count of regular bloons popped.',
    'BloonsPopped::bloonsLeaked' => 'Count of bloons that leaked.',
    'BloonsPopped::camosPopped' => 'Count of camo bloons popped.',
    'BloonsPopped::ceramicsPopped' => 'Count of ceramic bloons popped.',
    'BloonsPopped::coopBloonsPopped' => 'Count of bloons popped in co-op.',
    'BloonsPopped::goldenBloonsPopped' => 'Count of golden bloons popped.',
    'BloonsPopped::leadsPopped' => 'Count of lead bloons popped.',
    'BloonsPopped::moabsPopped' => 'Count of MOAB-class bloons popped.',
    'BloonsPopped::necroBloonsReanimated' => 'Count of necro bloons reanimated.',
    'BloonsPopped::transformingTonicsUsed' => 'Count of transforming tonics used.',
    'BloonsPopped::purplesPopped' => 'Count of purple bloons popped.',
    'BloonsPopped::regrowsPopped' => 'Count of regrow bloons popped.',
    'BloonsPopped::zomgsPopped' => 'Count of ZOMG bloons popped.',

    'BossEvent::id' => 'Unique boss event ID, e.g. `Dreadbloon38_mug8c17e`.',
    'BossEvent::name' => 'Boss display name, e.g. `Dreadbloon38`.',
    'BossEvent::start' => 'Event start time.',
    'BossEvent::end' => 'Event end time.',
    'BossEvent::bossType' => 'Lowercase boss identifier, e.g. `dreadbloon`, `bloonarius`, `vortex`.',
    'BossEvent::bossTypeImage' => 'Asset URL of the boss icon.',
    'BossEvent::totalScoresStandard' => 'Number of submissions on the Standard leaderboard.',
    'BossEvent::totalScoresElite' => 'Number of submissions on the Elite leaderboard.',
    'BossEvent::leaderboardStandardSingleplayer' => 'Absolute URL of the Standard singleplayer leaderboard.',
    'BossEvent::leaderboardEliteSingleplayer' => 'Absolute URL of the Elite singleplayer leaderboard.',
    'BossEvent::metadataStandard' => 'Absolute URL of the Standard challenge metadata.',
    'BossEvent::metadataElite' => 'Absolute URL of the Elite challenge metadata.',
    'BossEvent::scoringTypeStandard' => 'Standard scoring type. Upstream values: `LeastCash`, `GameType`, `LeastTiers`.',
    'BossEvent::scoringTypeElite' => 'Elite scoring type. Upstream values: `LeastCash`, `GameType`, `LeastTiers`.',

    'BossLeaderboard::name' => 'Display name of the player or team.',
    'BossLeaderboard::score' => 'Combined score. For time-based scoring this is milliseconds, where lower is better.',
    'BossLeaderboard::scoreParts' => 'Breakdown of `score` into its individual scoring components.',
    'BossLeaderboard::submissionTime' => 'Submission time in epoch milliseconds, or `-1` when the API does not expose it.',
    'BossLeaderboard::profile' => 'Absolute URL of the player profile. The trailing segment is the user ID accepted by `users()->find()`.',

    'CtEvent::id' => 'Unique CT event ID, e.g. `muekp4st`.',
    'CtEvent::start' => 'Event start time.',
    'CtEvent::end' => 'Event end time.',
    'CtEvent::totalScoresPlayer' => 'Number of submissions on the player leaderboard.',
    'CtEvent::totalScoresTeam' => 'Number of submissions on the team leaderboard.',
    'CtEvent::tiles' => 'Absolute URL of the tile map for this event.',
    'CtEvent::leaderboardPlayer' => 'Absolute URL of the player leaderboard.',
    'CtEvent::leaderboardTeam' => 'Absolute URL of the team leaderboard.',

    'CtLeaderboardGroup::name' => 'Display name of the player.',
    'CtLeaderboardGroup::score' => 'Score within the group.',
    'CtLeaderboardGroup::profile' => 'Absolute URL of the player profile.',

    'CtLeaderboardPlayer::name' => 'Display name of the player.',
    'CtLeaderboardPlayer::score' => 'Total player score.',

    'CtLeaderboardTeam::name' => 'Display name of the team.',
    'CtLeaderboardTeam::score' => 'Total team score.',
    'CtLeaderboardTeam::profile' => 'Absolute URL of the **guild** backing the team. The trailing segment is the guild ID accepted by `guild()->find()`.',
    'CtLeaderboardTeam::group' => 'Absolute URL of the leaderboard for the group this team belongs to. The trailing segment is the ID accepted by `leaderboard()->group()`.',
    'CtLeaderboardPlayer::profile' => 'Absolute URL of the player profile. The trailing segment is the user ID accepted by `users()->find()`.',

    'Gameplay::cashEarned' => 'Total cash earned across all games.',
    'Gameplay::challengesCompleted' => 'Number of challenges completed.',
    'Gameplay::collectionChestsOpened' => 'Number of collection chests opened.',
    'Gameplay::coopCashGiven' => 'Total cash given to other players in co-op.',
    'Gameplay::dailyRewards' => 'Number of daily rewards collected.',
    'Gameplay::gameCount' => 'Number of games played.',
    'Gameplay::gamesWon' => 'Number of games won.',
    'Gameplay::highestRound' => 'Highest round reached in any game.',
    'Gameplay::highestRoundCHIMPS' => 'Highest CHIMPS round reached.',
    'Gameplay::highestRoundDeflation' => 'Highest round reached in a Deflation game.',
    'Gameplay::instaMonkeyCollection' => 'Number of Insta Monkeys collected.',
    'Gameplay::monkeyTeamsWins' => 'Number of wins with a monkey team.',
    'Gameplay::powersUsed' => 'Number of hero powers used.',
    'Gameplay::totalOdysseysCompleted' => 'Number of Odysseys completed.',
    'Gameplay::totalOdysseyStars' => 'Total stars earned in the Odyssey.',
    'Gameplay::totalTrophiesEarned' => 'Total trophies earned.',
    'Gameplay::damageDoneToBosses' => 'Total damage dealt to bosses.',
    'Gameplay::instaMonkeysUsed' => 'Number of Insta Monkeys used.',
    'Gameplay::abilitiesUsed' => 'Number of hero abilities used.',
    'Gameplay::monkeysPlaced' => 'Total number of monkeys placed.',

    'Guild::name' => 'Guild name.',
    'Guild::owner' => 'Absolute URL of the owner profile, not a display name. The trailing segment is the user ID accepted by `users()->find()`.',
    'Guild::numMembers' => 'Current member count.',
    'Guild::status' => 'Guild status reported by the API.',
    'Guild::bannerUrl' => 'Absolute URL of the guild banner asset.',
    'Guild::frameUrl' => 'Absolute URL of the guild frame asset.',
    'Guild::iconUrl' => 'Absolute URL of the guild icon asset.',
    'Guild::banner' => 'Cosmetic banner identifier, when the API returns one.',
    'Guild::frame' => 'Cosmetic frame identifier, when the API returns one.',
    'Guild::icon' => 'Cosmetic icon identifier, when the API returns one.',

    'HeroesPlaced::AdmiralBrickell' => 'Games with Admiral Brickell placed.',
    'HeroesPlaced::Adora' => 'Games with Adora placed.',
    'HeroesPlaced::Benjamin' => 'Games with Benjamin placed.',
    'HeroesPlaced::Etienne' => 'Games with Etienne placed.',
    'HeroesPlaced::Geraldo' => 'Games with Geraldo placed.',
    'HeroesPlaced::Gwendolin' => 'Games with Gwendolin placed.',
    'HeroesPlaced::ObynGreenfoot' => 'Games with Obyn Greenfoot placed.',
    'HeroesPlaced::PatFusty' => 'Games with Pat Fusty placed.',
    'HeroesPlaced::Psi' => 'Games with Psi placed.',
    'HeroesPlaced::Quincy' => 'Games with Quincy placed.',
    'HeroesPlaced::Sauda' => 'Games with Sauda placed.',
    'HeroesPlaced::StrikerJones' => 'Games with Striker Jones placed.',
    'HeroesPlaced::Ezili' => 'Games with Ezili placed.',
    'HeroesPlaced::CaptainChurchill' => 'Games with Captain Churchill placed.',
    'HeroesPlaced::Corvus' => 'Games with Corvus placed.',
    'HeroesPlaced::Rosalia' => 'Games with Rosalia placed.',
    'HeroesPlaced::get' => 'Looks a hero count up by `Enums\Tower` case, so you do not need to hardcode property names.',

    'Metadata::id' => 'Unique challenge ID. Boss and Race events report `n/a`.',
    'Metadata::name' => 'Challenge name.',
    'Metadata::createdAt' => 'Challenge creation time. Boss and Race events report `0`.',
    'Metadata::creator' => 'URL of the creator profile, or `null` for official events.',
    'Metadata::gameVersion' => 'Game version the challenge was built in. Boss and Race events report `"0"`.',
    'Metadata::map' => 'Map name, e.g. `Mesa`.',
    'Metadata::mapImage' => 'Asset URL of the map thumbnail.',
    'Metadata::mode' => 'Game mode, e.g. `Standard`.',
    'Metadata::difficulty' => 'Difficulty, e.g. `Medium`.',
    'Metadata::disableDoubleCash' => 'Whether double cash is disabled.',
    'Metadata::disableInstas' => 'Whether Insta Monkeys are disabled.',
    'Metadata::disableMK' => 'Whether monkey knowledge is disabled.',
    'Metadata::disablePowers' => 'Whether hero powers are disabled.',
    'Metadata::disableSelling' => 'Whether selling towers is disabled.',
    'Metadata::startingCash' => 'Cash the player starts with.',
    'Metadata::abilityCooldownReductionMultiplier' => 'Multiplier applied to hero ability cooldowns.',
    'Metadata::leastCashUsed' => 'Cash used by the Least Cash criteria, or `-1` when not applicable.',
    'Metadata::leastTiersUsed' => 'Tiers used by the Least Tiers criteria, or `-1` when not applicable.',
    'Metadata::noContinues' => 'Whether continues are disabled.',
    'Metadata::seed' => 'Map seed.',
    'Metadata::removeableCostMultiplier' => 'Multiplier applied to the cost of removable upgrades.',
    'Metadata::roundSets' => 'Names of the round sets used by the challenge.',
    'Metadata::lives' => 'Lives in the current ruleset.',
    'Metadata::maxLives' => 'Lives in the hardest ruleset.',
    'Metadata::startRound' => 'First round played.',
    'Metadata::endRound' => 'Last round played.',
    'Metadata::maxTowers' => 'Maximum number of towers placeable.',
    'Metadata::maxParagons' => 'Maximum number of Paragons.',
    'Metadata::plays' => 'Total number of plays.',
    'Metadata::wins' => 'Total number of wins.',
    'Metadata::restarts' => 'Total number of restarts.',
    'Metadata::losses' => 'Total number of losses.',
    'Metadata::upvotes' => 'Number of upvotes.',
    'Metadata::playsUnique' => 'Number of unique plays.',
    'Metadata::winsUnique' => 'Number of unique wins.',
    'Metadata::lossesUnique' => 'Number of unique losses.',
    'Metadata::powers' => 'Hero powers enabled in the challenge.',
    'Metadata::bloonModifiers' => 'Bloon speed, regrow and health modifiers for the challenge.',
    'Metadata::towers' => 'Per-tower restrictions and limits.',

    'Race::id' => 'Unique race ID, e.g. `Party_Madness_muekt6x6`.',
    'Race::name' => 'Race name.',
    'Race::start' => 'Event start time.',
    'Race::end' => 'Event end time.',
    'Race::totalScores' => 'Number of submitted scores.',
    'Race::leaderboard' => 'Absolute URL of the leaderboard.',
    'Race::metadata' => 'Absolute URL of the challenge metadata.',

    'RaceLeaderboard::name' => 'Display name of the player.',
    'RaceLeaderboard::score' => 'Combined score in milliseconds. Lower is better.',
    'RaceLeaderboard::scoreParts' => 'Breakdown of `score` into its individual scoring components.',
    'RaceLeaderboard::submissionTime' => 'Submission time in epoch milliseconds, or `-1` when the API does not expose it.',
    'RaceLeaderboard::profile' => 'Absolute URL of the player profile. The trailing segment is the user ID accepted by `users()->find()`.',

    'ScorePart::type' => 'Score category as sent by the API, e.g. `time` or `number`.',
    'ScorePart::score' => 'Raw value of this component. Unit depends on `type`.',
    'ScorePart::name' => 'Human readable label, e.g. `Game Time` or `Boss Tier`.',

    'Tile::id' => 'Tile identifier as printed on the CT map, e.g. `MRX`.',
    'Tile::type' => 'Visual tile type, e.g. `Regular`, `Banner` or `Relic - GoingTheDistance`.',
    'Tile::gameType' => 'Scoring mode the tile belongs to.',

    'Tower::tower' => 'The tower or hero this entry describes.',
    'Tower::max' => 'Maximum number of copies placeable.',
    'Tower::path1NumBlockedTiers' => 'Number of upgrade tiers blocked on path 1.',
    'Tower::path2NumBlockedTiers' => 'Number of upgrade tiers blocked on path 2.',
    'Tower::path3NumBlockedTiers' => 'Number of upgrade tiers blocked on path 3.',
    'Tower::isHero' => 'Whether the entry describes a hero.',

    'TowersPlaced::get' => 'Looks a tower count up by `Enums\Tower` case, so you do not need to hardcode property names.',

    'User::name' => 'Current display name.',
    'User::rank' => 'Player rank.',
    'User::veteranRank' => 'Veteran rank.',
    'User::achievements' => 'Number of achievements unlocked. Sent as a string by the API.',
    'User::mostExperiencedMonkey' => 'Most played monkey, e.g. `MonkeyBuccaneer`.',
    'User::avatar' => 'Avatar cosmetic identifier, e.g. `ProfileAvatar03`.',
    'User::banner' => 'Banner cosmetic identifier, e.g. `ProfileBanner42`.',
    'User::avatarUrl' => 'Absolute URL of the avatar asset.',
    'User::bannerUrl' => 'Absolute URL of the banner asset.',
    'User::followers' => 'Number of followers.',
    'User::bloonsPopped' => 'Lifetime bloon popping statistics.',
    'User::gameplay' => 'Lifetime gameplay statistics.',
    'User::heroesPlaced' => 'Number of games each hero was placed in.',
    'User::towersPlaced' => 'Number of games each tower was placed in.',
    'User::stats' => 'Miscellaneous statistics, keyed by an unstable API-defined name.',
    'User::bossBadgesNormal' => 'Badge count per boss on Standard difficulty.',
    'User::bossBadgesElite' => 'Badge count per boss on Elite difficulty.',
    'User::medalsSingleplayer' => 'Singleplayer medal count per difficulty.',
    'User::medalsMultiplayer' => 'Multiplayer medal count per difficulty.',
    'User::medalsBossNormal' => 'Standard boss medal count per medal tier.',
    'User::medalsTeamElite' => 'Elite boss medal count per medal tier.',
    'User::medalsCtLocal' => 'Local CT medal count per medal tier.',
    'User::medalsCtGlobal' => 'Global CT medal count per medal tier.',
    'User::medalsRace' => 'Race medal count per medal tier.',
];

/**
 * Fallback descriptions, applied per class as a sprintf template. Used where
 * spelling out every property would be pure noise, such as the per-tower
 * placement counters.
 */
$fallbackDescriptions = [
    'HeroesPlaced' => 'Number of games in which %s was placed.',
    'TowersPlaced' => 'Number of games in which %s was placed.',
];

/** Extra notes rendered underneath a class, keyed by DTO short name. */
$classNotes = [
    'BossMetadata' => 'Adds no fields of its own. It is an empty subclass of [Metadata](#metadata) so that a boss metadata call has a distinct return type from a race one.',
    'RaceMetadata' => 'Adds no fields of its own. It is an empty subclass of [Metadata](#metadata) so that a race metadata call has a distinct return type from a boss one.',
    'Guild' => '`banner`, `frame` and `icon` are nullable and default to `null`, so a payload that omits them still hydrates. Every other field is required.',
    'ScorePart' => 'Hydrated inside a [ScorePartCollection](collections.md), which is `IteratorAggregate` and `Countable`.',
    'Tile' => 'Only returned by [contested territory](../resources/contested-territory.md). The API wraps the list in a `tiles` key, so the resource unwraps it before hydrating.',
    'Metadata' => 'Shared by boss and race challenges. The API reports placeholder values on official events: `id` is `"n/a"`, `createdAt` is `0` and `gameVersion` is `"0"`.',
];

$short = static function (string $fqcn): string {
    $pos = strrpos($fqcn, '\\');

    return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
};

/** Extracts the value type from a Collection subclass, e.g. `Collection<int, ScorePart>`. */
$collectionElement = static function (string $collectionClass, \Closure $short): string {
    $reflection = new \ReflectionClass($collectionClass);

    // `@extends Collection<int, ScorePart>` is the authoritative declaration.
    if (preg_match('/@extends\s+[^\s]*Collection\s*<[^,>]+,\s*([^\s>]+)/', $reflection->getDocComment(), $m) === 1) {
        return $short(trim($m[1]));
    }

    // Fall back to the constructor's `@param array<int, X> $items`.
    $doc = $reflection->getConstructor()?->getDocComment();
    if ($doc !== false && preg_match('/@param\s+array<[^,>]+,\s*([^\s>]+)/', $doc, $m) === 1) {
        return $short(trim($m[1]));
    }

    return 'mixed';
};

/** Renders a `ReflectionType` as a short, doc-friendly PHP type string. */
$renderType = static function (?\ReflectionType $type, string $doc = '') use ($short, $collectionElement): string {
    if ($type === null) {
        return 'mixed';
    }

    if ($type instanceof \ReflectionUnionType) {
        return implode('|', array_map(
            static fn(\ReflectionType $inner): string => $renderType($inner),
            $type->getTypes(),
        ));
    }

    /** @var \ReflectionNamedType $type */
    $name = $type->getName();

    if ($type->isBuiltin()) {
        if ($name !== 'array') {
            return $name;
        }

        // Resolve the array shape from the @var docblock on the promoted property.
        if ($doc !== '' && preg_match('/@(?:var|param)\s+(array<[^>]+>)/', $doc, $m) === 1) {
            return trim($m[1]);
        }

        return 'array<array-key, mixed>';
    }

    if ($name === \DateTimeImmutable::class || is_subclass_of($name, \DateTimeInterface::class)) {
        return 'DateTimeImmutable';
    }

    if (enum_exists($name)) {
        return $short($name);
    }

    if (is_subclass_of($name, Collection::class)) {
        return $short($name) . '<' . $collectionElement($name, $short) . '>';
    }

    return $short($name);
};

/** Renders a single constructor parameter as a PHP type string. */
$typeOf = static function (\ReflectionParameter $param, string $doc) use ($renderType): string {
    return $renderType($param->getType(), $doc);
};

/**
 * Scans the resource classes for `SomeDto::class` references and maps them to
 * the resource method they are produced in. Purely token based, so adding a new
 * endpoint is picked up without touching this file.
 */
$usage = [];
$dtoDir = $root . '/src/DTO';

foreach (glob($root . '/src/Resources/*.php') ?: [] as $file) {
    $tokens = token_get_all((string) file_get_contents($file));
    $method = null;
    $class = null;
    $count = count($tokens);

    // Index of the next significant (non whitespace/comment) token.
    $significant = static function (int $from) use ($tokens, $count): int {
        for ($j = $from; $j < $count; $j++) {
            $t = $tokens[$j];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $j;
        }

        return $count;
    };

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if (!is_array($token)) {
            continue;
        }

        $prev = $tokens[$i - 1] ?? null;

        if ($token[0] === T_CLASS) {
            // `Foo::class` is also tokenised as T_CLASS, but must not be read as
            // a class declaration.
            if (is_array($prev) && $prev[0] === T_DOUBLE_COLON) {
                continue;
            }

            // The name follows the keyword: `class Foo`.
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $class = $tokens[$j][1];
                    break;
                }
            }
            continue;
        }

        if ($token[0] === T_FUNCTION) {
            $nameIndex = $significant($i + 1);
            $nameToken = $tokens[$nameIndex] ?? null;

            // T_LIST counts as a name token: `list()` is a reserved word.
            if (is_array($nameToken) && in_array($nameToken[0], [T_STRING, T_LIST], true)) {
                $method = $nameToken[1] === '__construct' ? null : $nameToken[1];
            }
            continue;
        }

        // `Foo::class` -> the class name of a DTO.
        $next = $tokens[$i + 1] ?? null;
        $after = $tokens[$i + 2] ?? null;

        if ($token[0] === T_STRING
            && is_array($next) && $next[0] === T_DOUBLE_COLON
            && is_array($after) && $after[0] === T_CLASS
            && $class !== null
            && is_file($dtoDir . '/' . $token[1] . '.php')
        ) {
            // A closure is still lexically inside a method, so keep the method.
            // A constructor or class-level reference is attributed to the class.
            $usage[$token[1]][($method !== null ? $class . '::' . $method . '()' : $class)] = true;
        }
    }
}

// ---------------------------------------------------------------------------
// Build the document
// ---------------------------------------------------------------------------

$files = glob($dtoDir . '/*.php') ?: [];
sort($files);

$out = [];
$out[] = '<!-- Generated by docs/tools/generate-dto-reference.php. DO NOT EDIT BY HAND. -->';
$out[] = '<!-- Run `php docs/tools/generate-dto-reference.php` after changing a DTO. -->';
$out[] = '';
$out[] = '# Data objects';
$out[] = '';
$out[] = 'Every endpoint returns typed value objects instead of associative arrays. The classes below are';
$out[] = 'plain PHP classes with public, promoted constructor properties: read them, pass them around, but';
$out[] = 'treat them as immutable.';
$out[] = '';
$out[] = 'This page is generated from `src/DTO` by';
$out[] = '[`docs/tools/generate-dto-reference.php`](../tools/generate-dto-reference.php). Field descriptions come';
$out[] = 'from the `model` metadata the public API returns with every payload, so they track upstream.';
$out[] = '';
$out[] = '## Conventions';
$out[] = '';
$out[] = '| Convention | Meaning |';
$out[] = '| --- | --- |';
$out[] = '| Timestamps | The API sends epoch **milliseconds**; the library converts them to `DateTimeImmutable` in **seconds**. |';
$out[] = '| Unknown fields | Ignored. A field the library does not model yet is simply not accessible. |';
$out[] = '| Missing fields | Throw `InvalidArgumentException`, unless the property is optional and has a default. |';
$out[] = '| Renamed fields | The `JSON field` column shows the upstream key when it differs from the PHP property. |';
$out[] = '| `-1` sentinels | Several numeric fields use `-1` to mean "not applicable", for example `leastCashUsed`. |';
$out[] = '';
$out[] = '## Contents';
$out[] = '';

$index = [];
foreach ($files as $file) {
    $name = basename($file, '.php');
    if ($name === 'DTOInterface') {
        continue;
    }
    $index[] = $name;
}

$out[] = implode(' · ', array_map(
    static fn(string $n): string => '[' . $n . '](#' . strtolower($n) . ')',
    $index,
));
$out[] = '';

foreach ($index as $name) {
    $fqcn = 'Kan\\NkOpendata\\DTO\\' . $name;
    $reflection = new \ReflectionClass($fqcn);

    $parents = [];
    for ($p = $reflection->getParentClass(); $p !== false; $p = $p->getParentClass()) {
        if ($p->getShortName() !== 'DTOInterface') {
            $parents[] = $p->getShortName();
        }
    }

    $out[] = '---';
    $out[] = '';
    $out[] = '## ' . $name;
    $out[] = '';
    $out[] = '`Kan\NkOpendata\DTO\\' . $name . '` in `src/DTO/' . $name . '.php`'
        . ($parents !== [] ? ', extends `' . implode('`, extends `', $parents) . '`' : '')
        . '.';
    $out[] = '';

    if (isset($classNotes[$name])) {
        $out[] = $classNotes[$name];
        $out[] = '';
    }

    if (isset($usage[$name])) {
        $out[] = 'Returned by '
            . implode(', ', array_map(
                static fn(string $m): string => '`' . $m . '`',
                array_keys($usage[$name]),
            ))
            . '.';
        $out[] = '';
    }

    $constructor = $reflection->getConstructor();
    $params = $constructor?->getParameters() ?? [];

    if ($params === []) {
        $out[] = 'No fields.';
        $out[] = '';
        continue;
    }

    $out[] = '| Property | Type | JSON field | Description |';
    $out[] = '| --- | --- | --- | --- |';

    foreach ($params as $param) {
        $property = $param->getName();
        $doc = $reflection->hasProperty($property)
            ? (string) $reflection->getProperty($property)->getDocComment()
            : '';
        $type = $typeOf($param, $doc);

        if ($param->getType()?->allowsNull() === true && !str_starts_with($type, 'null|')) {
            $type = '?' . $type;
        }

        if ($param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();
            $type .= $default === null ? ' = null' : ' = ' . var_export($default, true);
        }

        $jsonField = $property;
        $mapFrom = $param->getAttributes(MapFrom::class);
        if ($mapFrom !== []) {
            $jsonField = $mapFrom[0]->newInstance()->field;
        }

        $description = $descriptions[$name . '::' . $property]
            // Inherited constructors are declared on the parent, so look there too.
            ?? $descriptions[$param->getDeclaringClass()->getShortName() . '::' . $property]
            // Per-tower style counters follow a fixed pattern.
            ?? (isset($fallbackDescriptions[$name])
                ? sprintf($fallbackDescriptions[$name], $property)
                : '');
        $description = str_replace('|', '\|', $description);

        $out[] = '| `$' . $property . '` | `' . $type . '` | `' . $jsonField . '` | ' . $description . ' |';
    }

    $out[] = '';

    $methods = array_filter(
        $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
        static fn(\ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === $fqcn
            && $m->getName() !== '__construct',
    );

    if ($methods !== []) {
        $out[] = '### Methods';
        $out[] = '';
        foreach ($methods as $method) {
            $signature = $method->getName() . '(';
            $signature .= implode(', ', array_map(
                static function (\ReflectionParameter $p) use ($renderType): string {
                    $type = $renderType($p->getType());

                    return ($type === 'mixed' ? '' : $type . ' ') . '$' . $p->getName();
                },
                $method->getParameters(),
            ));
            $signature .= ')';
            $returnType = $renderType($method->getReturnType());
            if ($returnType !== 'mixed') {
                $signature .= ': ' . $returnType;
            }

            $doc = $method->getDocComment();
            $text = $doc === false
                ? ''
                : trim((string) preg_replace('#^\s*/?\*+/?\s*|\s*\*/$#m', '', $doc));

            $out[] = '- `' . $signature . '`'
                . ($text !== '' ? ' ' . $text : '');
        }
        $out[] = '';
    }
}

$markdown = implode("\n", $out) . "\n";

if ($check) {
    $existing = is_file($outFile = $root . '/docs/reference/dtos.md') ? (string) file_get_contents($outFile) : '';
    if ($existing !== $markdown) {
        fwrite(STDERR, "docs/reference/dtos.md is out of date. Run: php docs/tools/generate-dto-reference.php\n");
        exit(1);
    }
    echo "docs/reference/dtos.md is up to date.\n";
    exit(0);
}

if (!is_dir($dir = $root . '/docs/reference')) {
    mkdir($dir, 0o755, true);
}

file_put_contents($root . '/docs/reference/dtos.md', $markdown);

echo 'Wrote docs/reference/dtos.md (' . count($index) . " data objects, " . strlen($markdown) . " bytes)\n";
