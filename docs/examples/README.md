# Examples

Runnable scripts. Clone the repository and run them directly:

```bash
composer install
php docs/examples/current-ct-event.php
```

They call the live public API, so they need network access. Each one exits
non-zero if it fails, which makes them usable as smoke tests:

```bash
for f in docs/examples/*.php; do php "$f" || echo "FAILED: $f"; done
```

| Script | Shows |
| --- | --- |
| [`current-ct-event.php`](current-ct-event.php) | Finding the running event, reading tiles, tallying scoring modes, handling an empty leaderboard. |
| [`race-leaderboard.php`](race-leaderboard.php) | Listing races, reading a leaderboard, breaking a score into parts, walking to a player profile. |
| [`boss-leaderboards.php`](boss-leaderboards.php) | Boss events, comparing Standard and Elite, team sizes, score breakdowns. |
| [`player-lookup.php`](player-lookup.php) | Resolving a player from a leaderboard, statistics, tower usage, medals. |
| [`ct-to-guild.php`](ct-to-guild.php) | Traversing CT team → guild → guild owner, and reading identity URLs. |

Every snippet in the prose pages is drawn from these scripts, so if one is wrong
the other is too.

## Conventions

Each script follows the same shape, so the interesting part is easy to find:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Kan\NkOpendata\Client;

// ... the example ...

exit(0);
```

- `declare(strict_types=1)` throughout, matching the library.
- Errors are caught at the bottom and turned into a non-zero exit, so a failure is
  visible in CI rather than as a stack trace on stdout.
- Output is aligned plain text. No dependencies beyond the library itself, and no
  framework.
