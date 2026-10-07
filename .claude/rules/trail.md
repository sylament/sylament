---
paths:
  - "/docs/progress/**"
description: How a port-trail file is shaped, where status lives, what each file owns, and how its prose and tables are written.
---
# Port-trail rules

- **A detail file starts with frontmatter:** `description` (one line naming the subject, never its status), `ports` (the `vendor/filament/` files or directories it ports, `[]` when none) and `owns` (the `packages/*/src/`, `templates/` and `assets/src/` files or directories it documents, a directory ending in `/`); the checks read all three, so a missing key fails the suite.
- **Every ownable file has exactly one owner,** the trail file whose `owns` entry matches it most specifically; a new class, template or asset adds its path to that file in the same change, because an unowned or twice-owned path fails CI.
- **Status is written only on rows,** in the Surface table's Status column; file and package statuses are derived into the README indexes, so a flip is one cell.
- **The Surface table** has exactly the columns `Feature | Ports | Status | Blocked by | Detail`, and keeps a `_not audited yet_` row (`_rest not audited yet_` once it has real rows) until someone walks its vendor source end to end, because an unaudited empty table reads as finished.
- **Statuses** are todo, in progress, blocked, done and skipped, as `docs/progress/README.md` defines them; *Blocked by* names the prerequisite of a blocked row and is `—` on every other row, so a todo row never names one.
- **A Detail cell holds only links** (`[detail](#…)`, `[semantics](#…)`, `[diverges](#…)` or a link into another file) joined by ` · `, or `—`; reasoning goes in the `###` entry it links, because a cell has no anchor of its own.
- **Entries** are `###` statement headings under `## Details` (an unfinished row's gap, deleted with its section once the row is done), `## Semantics` (faithful but easy to misread) or `## Divergences` (a deliberate deviation); never a bullet, because a bullet cannot be linked.
- **Put an entry in the file that owns the code it constrains** and link it from elsewhere; a restated fact drifts.
- **One sentence per line** in prose and list items, never hard-wrapped, so a phrase grep finds it and an edit touches one line.
- **State what the code does and why:** no "used to", "no longer" or "so far" narration and no counts that restate a table, since git holds the history.
- **Renaming or moving a heading** fixes every link to it in the same change, including code comments that cite the section by title; the docs checks fail on a broken one.
- **Keep a file under 80 KB** (aim for 60) by splitting along the code it owns, never by size alone, so it can be read in one call; the checks fail past 90 KB.
- **A rename** of any Filament API name passes the gate in `docs/progress/renames.md` and is recorded there in the same change; migrating users grep for Filament's names.

Background: `docs/internals/trail.md`.
