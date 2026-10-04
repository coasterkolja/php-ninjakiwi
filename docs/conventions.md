# Documentation conventions

Notes for whoever changes this documentation next, including future you. The
goal is that a code change and its documentation change are the same commit, and
that nothing here rots silently.

## The split: README versus docs

| File | Audience | Rule |
| --- | --- | --- |
| `README.md` | Someone who landed on the GitHub page | Pitch, install, one runnable snippet, links. Under two screens. |
| `docs/` | Someone who has decided to use the library | Complete reference. Depth is welcome. |

The README links into `docs/`; it never duplicates it. If a fact lives in
`docs/`, the README links to it rather than restating it. That is the whole
reason the old hand-written `## Schemas` block was removed from the README — it
was a second, worse copy of
[the data object reference](reference/dtos.md).

## Layered pages

Reference pages are split by concern rather than by class, so each page stays
short and a reader only loads what they need:

```
getting-started.md          narrative, first contact
resources/*.md              one per endpoint group, usage oriented
reference/*.md              one per mechanism, exhaustive
roadmap.md                  what is broken and what is planned
```

A method appears on exactly one page: the resource page for its endpoint group.
`reference/` documents *mechanisms* that cut across endpoints — hydration,
collections, enums, errors, testing — and links to the resource pages rather
than repeating their tables.

## Never hand-write a field table

[reference/dtos.md](reference/dtos.md) is generated. Do not edit it, and do not
write field tables anywhere else.

```bash
php docs/tools/generate-dto-reference.php          # regenerate
php docs/tools/generate-dto-reference.php --check   # CI: fail if stale
```

The generator reads the DTO constructors by reflection, so it cannot drift from
the code. Two things it cannot infer, both of which need a human:

1. **Field descriptions**, in `$descriptions` in the generator, keyed
   `"DtoShortName::propertyName"`. The current values are seeded from the
   `model.parameters.<field>.description` metadata the public API returns with
   every payload, so they can be re-verified against upstream. Fields with no
   entry render with a blank description, which is the visible signal that
   something was added and not documented.
2. **Class-level notes**, in `$classNotes` — things that apply to the whole class
   and do not belong on a single property.

For per-property families that would otherwise be dozens of near-identical
lines, `$fallbackDescriptions` holds an sprintf template per class. That is how
the 24 `TowersPlaced` counters are covered by one entry.

### The generator must not need editing for new endpoints

It discovers both sides automatically: DTO classes by globbing `src/DTO`, and
which resource method returns each one by tokenising `src/Resources` for
`SomeDto::class`. Adding an endpoint and a DTO needs no change to the generator.
If you find yourself adding a case to it, check whether the new information
could be derived instead.

## Verify examples and snippets against the real API

The scripts in [`examples/`](examples) run against the live service and exit
non-zero on failure. Run them before committing a change that touches resource
behaviour, URI construction, DTO shape or the hydrator:

```bash
for f in docs/examples/*.php; do php "$f" || echo "FAILED: $f"; done
```

Every code block in the prose is drawn from one of those scripts, or is a
fragment of one. When you update a script, update the page that quotes it.

Prefer pasting real output into a fenced block, lightly trimmed, over inventing
plausible numbers. A wrong example is worse than no example, because it is
trusted.

## Describe current behaviour, including the ugly parts

Several pages document things that are not nice: the swallowed
[upstream error message](reference/errors.md#the-upstream-error-message-is-discarded),
the [incomplete tower enum](roadmap.md#incomplete-tower-enum), the
[uniform "Api call failed"](reference/errors.md) string, `current()`'s
unguarded list access. Keep these.

A user hitting a confusing failure searches for what they saw, finds the page,
and learns it is a known issue with a link to the fix. Without that, they file a
duplicate bug. Every such note links to the
[roadmap](roadmap.md) rather than describing a fix that does not exist.

When a roadmap item is implemented, delete both the roadmap entry and the warning
that points at it, in the same commit.

## Mark planned API unambiguously

The code is not finished, so these pages describe some behaviour that does not
exist yet. That is fine, as long as it cannot be mistaken for something you can
run today:

- Use the wording from the [status table](roadmap.md), principally **Planned**.
- Put a callout on the page itself, not only in the roadmap:

  ```markdown
  > [!WARNING]
  > `metadata()` currently throws for live data. See
  > [roadmap](../roadmap.md#incomplete-tower-enum).
  ```

- Never document a planned method in a table of live methods without marking
  the row.

GitHub callouts are supported here and render on the repository page:

| Callout | Use for |
| --- | --- |
| `> [!NOTE]` | Context the reader would otherwise have to infer. |
| `> [!TIP]` | A non-obvious better way to do something. |
| `> [!WARNING]` | Data loss, a live failure, or a footgun. |
| `> [!IMPORTANT]` | Cannot be worked around; changes how the code must be written. |

## Writing style

- British-leaning English, matching the existing prose. Be consistent, not
  dogmatic.
- Present tense, second person, active voice. "The client returns", not "it will
  be returned".
- Lead with the code. Prose explains what the code above cannot show.
- Show a table when there are three or more parallel facts — methods, fields,
  exceptions. Lists of prose for parallel items hide the differences.
- Name the class and method explicitly: `CtResource::current()`, not "current()".
  Readers grep.
- Prefer a real ID from a real response over a placeholder. `mtv62zza` teaches
  the format; `<id>` does not.
- Say *why* where it is not obvious, especially where the upstream API is the
  odd one out. The `guild` versus `guilds` path, `$guild->owner` being a URL,
  and the `totalScores_player` key style are all upstream inconsistencies that
  will look like library bugs without explanation.
- No emoji. No "simple", "easy", "just", or "obviously".

## Relative links

Link by relative path so the docs work from a checkout, a Packagist tarball and
GitHub alike:

```markdown
[Enums](reference/enums.md)          from docs/README.md
[Errors](../reference/errors.md)     from docs/resources/*.md
[Collection](../../src/Collections)  from docs/reference/*.md
```

Anchors are heading slugs, lowercase with hyphens, backticks stripped:

```markdown
[the tower problem](roadmap.md#incomplete-tower-enum)   from ## Incomplete `Tower` enum
```

After renaming a heading, grep for the old slug. GitHub does not redirect
in-page anchors, so a stale link fails silently.

## Checklist for a change

- [ ] `php docs/tools/generate-dto-reference.php` if any DTO changed
- [ ] Descriptions added to the generator for every new field
- [ ] `--check` passes in CI
- [ ] `vendor/bin/phpstan analyse` — level max, `src`
- [ ] `vendor/bin/phpunit` — 38 tests, 86 assertions
- [ ] Every `docs/examples/*.php` script still exits `0`
- [ ] The resource page for the changed endpoint updated
- [ ] Roadmap entries removed for anything now implemented
- [ ] No dead relative links or anchors
