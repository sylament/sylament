# Progress

The working tracker for the Filament → Sylament port: what the rules are, what
is done, and what is next.

This is the system of record for the Filament → Sylament port.

## Rules

### One row per file, one file per thing

A package README indexes **files**, not concepts: every row is a detail file
that exists, and every detail file has exactly one row. A component's own
concerns are folded into its file and appear only in that file's surface table —
never as a second row upstairs. So nothing has a status in two places.

Which section a row sits in follows from what the file covers:

| Section | Holds a file when it covers… |
| --- | --- |
| **Components** | a class the user instantiates — `TextInput`, `Section`, `Action`. |
| **Features** | a concern **shared across components**, promoted to its own file the moment a second component needs it — `HasIcon`, `HasColor`. |
| **Supporting surface** | everything else with no class of its own — enums, contracts, templates, assets, test helpers. |

Looking for one concern? `grep -rn "HasIcon" docs/progress/` finds it wherever
it lives, in one call. The tables are for navigating, not for inventory — don't
grow them into a mirror of `vendor/`.

### An unaudited table says so

Every table starts with a `_not audited yet_` row — `_rest not audited yet_`
once it has real rows — and keeps it until someone has walked the linked vendor
directory end to end. Deleting that row is a claim: _this table lists every item
in that directory._ Without it, "audited, nothing left to add" and "nobody has
looked" are the same empty table, and a package of three finished rows reads
**done** while thirty unlisted components sit in `vendor/`.

### Update the trail in the same commit

A status shows up in three places — the row in its detail file, that file's row
in the package README, and the package's row here. Nothing checks that they
agree, so walk the trail upward in the commit that lands the work: flip the row,
delete the detail section it no longer needs, then fix the two rows above it.
Code and trail in one diff is the whole guarantee — a status updated a commit
later is indistinguishable from one nobody updated at all.

A parent may **lag** its children — a package sits at _in progress_ while most
of its components are still unlisted — but must never **lead** them. Never mark
a package done while a listed row is unfinished.

### Divergences are addressable

One `###` heading per divergence under `## Divergences`, phrased as a statement:
`### Macroable is not ported`. Never a bullet list — a surface table links its
divergence by anchor (`[diverges](#macroable-is-not-ported)`), and a bullet has
no anchor to point at — reshape a deviation into a heading, never a bullet.

Headings also make the whole tree retrievable, so no index file has to be kept
in sync:

```
awk '/^## /{s=$0} /^### /&&s~/Divergences/{print FILENAME": "substr($0,5)}' docs/progress/*/*.md
```

Put a divergence in the file for the thing it constrains, not the file that
discovered it. `Macroable is not ported` is a fact about the base `Component`,
so it belongs in [`support/component.md`](support/component.md) even if a form
field forced the decision — otherwise knowing the rule depends on having read an
unrelated file.

### Faithful-but-subtle behaviour goes under `## Semantics`

`## Details` is deleted when its row flips to **done**, and `## Divergences` is
for deviations. Behaviour that is a *faithful* port yet subtle enough that the
next reader would misread the code has neither home — parked in Details it gets
deleted with the row, and filed as a divergence it claims a deviation that isn't
there. Such notes go under `## Semantics`, same shape as Divergences: one `###`
heading per note, phrased as a statement, permanent, addressable by anchor
(`[semantics](#…)` as a third link type in the _Detail_ column).

The bar is narrow, or it becomes a dumping ground for restated code. Both must
hold: a reader re-deriving the behaviour from the source would plausibly **get
it wrong**, and it stays true once the row is **done**. "What the method does"
is not a note; "why the obvious reading of it is wrong" is.

A file's Surface preamble names the link types **that file** uses, so a file with
no `## Semantics` section keeps saying two.

## Structure

- `README.md` — this index: the package table and the shared status legend.
- `<package>/README.md` — one per bundle, tracking that package's components,
  features, and supporting surface as the same four columns as the table above:
  the Sylament class, the Filament source it ports, its status, and a link to
  its detail file.
- `<package>/<component>.md` — the detail: a surface table of everything the
  component covers, a write-up per unfinished row, any permanent semantics
  notes, and the divergences. What
  earns a file of its own is [the rule above](#one-row-per-file-one-file-per-thing).
- [`support/component.md`](support/component.md) — the reference detail file, a
  filled-in example: a surface table on top, per-row detail sections beneath,
  divergences at the bottom.

Two files cut across every package rather than belonging to one:

- [`renames.md`](renames.md) — the global rename table, plus the systematic
  idiom and namespace translations that are **not** renames. Divergence sections
  link it rather than repeating an entry.
- [`roadmap.md`](roadmap.md) — the build order: the milestone ladder, the epics
  inside each milestone, and the `Now` cursor naming the next epic to pick up.
  The tables here answer _how complete is the port_; the roadmap answers _what
  do we build next_.

## Statuses

One vocabulary, used by every table in this tree.

| Status | Means |
| --- | --- |
| **todo** | Not started. |
| **in progress** | Partly ported — the gap is written up in the row's detail section. |
| **blocked** | Wanted for parity, but a prerequisite named in _Blocked by_ must land first. |
| **done** | Ported and covered by a test. The detail section is deleted; the row stays. |
| **skipped** | A deliberate non-goal — the toolkit works without it, so we are not porting it (whether or not Filament ships it). |

**skipped** vs **blocked** is a question of intent, not mechanics. **blocked** is
something we _want_ for parity and will build once the prerequisite in _Blocked
by_ lands — even where we could stub it now. **skipped** is something we have
decided the toolkit does not need. An item we simply have not reached yet, with
nothing to wait on, is **todo**.

Divergence is **not** a status — it is orthogonal, and any row can carry one.
A row we ported differently from Filament stays **done** and links its reasoning
from _Detail_; a row we are never porting is **skipped** and links a divergence
saying so. Nothing that has no code is ever **done** — that would tell a
migrating user the API exists here.

At package level, **done** means every row in that package's tables is settled —
done or skipped — and no table still carries its
[unaudited row](#an-unaudited-table-says-so).

## Packages

The nine bundles under `packages/`, each linked to the Filament package it ports
(Filament's panel builder ships as `filament/filament`, so `panels` maps there).

| Package | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`actions`](../../packages/actions) | [`vendor/filament/actions`](../../vendor/filament/actions) | in progress | [actions](actions/README.md) |
| [`forms`](../../packages/forms) | [`vendor/filament/forms`](../../vendor/filament/forms) | in progress | [forms](forms/README.md) |
| [`infolists`](../../packages/infolists) | [`vendor/filament/infolists`](../../vendor/filament/infolists) | todo | [infolists](infolists/README.md) |
| [`notifications`](../../packages/notifications) | [`vendor/filament/notifications`](../../vendor/filament/notifications) | in progress | [notifications](notifications/README.md) |
| [`panels`](../../packages/panels) | [`vendor/filament/filament`](../../vendor/filament/filament) | todo | [panels](panels/README.md) |
| [`schemas`](../../packages/schemas) | [`vendor/filament/schemas`](../../vendor/filament/schemas) | in progress | [schemas](schemas/README.md) |
| [`support`](../../packages/support) | [`vendor/filament/support`](../../vendor/filament/support) | in progress | [support](support/README.md) |
| [`tables`](../../packages/tables) | [`vendor/filament/tables`](../../vendor/filament/tables) | in progress | [tables](tables/README.md) |
| [`widgets`](../../packages/widgets) | [`vendor/filament/widgets`](../../vendor/filament/widgets) | todo | [widgets](widgets/README.md) |
