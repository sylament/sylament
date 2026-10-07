# The port trail

Why `docs/progress/` is shaped as it is.
The rules are in `.claude/rules/trail.md`, which loads whenever a trail file is read or edited; the index and the status vocabulary are in [the trail README](../progress/README.md).

## Two jobs, one file per piece of code

The trail does two jobs.
It tracks the port, one status per Filament feature, and it records the design: why the port reads the way it does wherever that is not obvious from the code.
Agents reach it by grep far more often than by reading whole files, so the unit an agent retrieves is a `###` entry and the unit it edits is a sentence.
The shape follows from that: entries are addressable statements, a file is small enough to read in one call, and each file says which code it documents.

## One file per piece of code, one row per feature

A detail file covers one family of ported code, such as a class with its own traits or a trait shared by several components.
It owns those paths in its `owns` frontmatter.
Its Surface table holds one row per feature of that code, and each row carries the feature's status.
A package README lists the detail files in an index generated from their frontmatter and rows, plus a hand-written Unported table for the Filament classes that have no file yet.
The tables are for navigating, not for inventory: grep finds a concern in one call wherever it lives.

## Status is written once

A status written in several places drifts: nothing keeps the copies in agreement, and an agent flipping a row has to find and edit each one.
So a status is written only on its row.
The file and package statuses are rollups that `composer docs:index` renders into the README indexes, and `TrailFilesTest` fails when an index is stale.
The rollup reads:

| Rows | File status |
| --- | --- |
| every row skipped, table audited | skipped |
| every row done or skipped, table audited | done |
| every row todo | todo |
| nothing done or in progress, something blocked | blocked |
| nothing done or in progress | todo |
| anything else, including an unaudited table | in progress |

A package rolls up its files and its Unported rows the same way.
So a parent may lag its children while its table is unaudited, but it never leads them.

## A file declares the code it owns

Every class, template and asset source under `packages/*/src/`, `templates/` and `assets/src/` has exactly one owning trail file: the one whose `owns` entry matches it most specifically.
That answers "which trail file do I update?" from a changed path alone, which the `docs-audit` skill and every lane plan need.
It also makes the rule that a feature lands with its trail entry mechanical: a new class without an owner fails CI.

## An unaudited table says so

A Surface table keeps a `_not audited yet_` row (`_rest not audited yet_` once it has real rows) until someone has walked its vendor source end to end.
Deleting that row is a claim: this table lists every item that source holds.
Without it, "audited, nothing left to add" and "nobody has looked" are the same table, and a file with three finished rows reads done while thirty unlisted methods sit in `vendor/`.

## Divergences are addressable

Each divergence is a `###` heading phrased as a statement, never a bullet, so a row can link it by anchor and `grep '^### '` lists every decision in the tree.
A divergence lives in the file of the code it constrains, not the file that discovered it; otherwise knowing the rule depends on having read an unrelated file.

## Faithful-but-subtle behaviour goes under Semantics

`## Details` is deleted when its row is done, and `## Divergences` is for deviations.
Behaviour that is a faithful port yet easy to misread has neither home, so it goes under `## Semantics`, in the same shape as Divergences.
The bar is narrow, or the section becomes restated code.
Both must hold: a reader re-deriving the behaviour from the source would plausibly get it wrong, and the note stays true once the row is done.
"What the method does" is not a note; "why the obvious reading of it is wrong" is.

## Cells hold links

A Detail cell holds only links to entries, and a Feature or Ports cell holds a name with at most a short qualifier.
A cell has no anchor and no line of its own, so reasoning written into one can be neither linked nor edited without rewriting the whole row, and a long row hides the table in a terminal slice.

## One sentence per line

Prose hard-wrapped at about 80 columns puts a line break inside a third of all five-word phrases.
A grep for a phrase then misses one time in three with no error, and every edit reflows its paragraph.
With one sentence per line, a phrase grep finds the sentence, an edit replaces one line, and a diff shows the sentence that changed.

## Files stay readable in one call

The `Read` tool refuses more than 25,000 tokens, which is roughly 90–100 KB of trail, and an agent that must keep a file true has to be able to read it.
So a file stays under 80 KB, and a file that outgrows that splits along the code it owns, never by size alone.
`TrailFilesTest` fails past 90 KB, which leaves room for an edit in flight but not for drift back past what one call can read.

## What the checks enforce

`TrailFilesTest`:

- **Frontmatter:** every detail file has `description`, `ports` and `owns`, and every `ports` entry exists.
- **Ownership:** every ownable file has exactly one owner, and every `owns` entry exists and is ownable.
- **Rows:** Surface and Unported rows use the five statuses, put a blocker only on a blocked row, and keep Detail cells to links.
- **Details:** a done or skipped file has no `## Details`.
- **Status lines:** no `**Status:**` line remains.
- **Indexes:** every README index equals its rendering.
- **References:** a trail path or quoted section title named in code or in `docs/internals/` resolves.
- **Size:** no trail file exceeds 90 KB.

`DocumentationFilesTest` checks every relative link and anchor in `docs/`, and that `docs/` carries no milestone label.
