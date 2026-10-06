---
paths:
  - "/docs/**"
description: Where documentation lives and the rules for the port trail.
---
# Docs rules

- **The port trail** (`docs/progress/`) is the system of record for status and reasoning per component; read `docs/progress/README.md` before editing it, since it owns the row, status and sync rules.
- **A rename** of any Filament API name passes the gate in `docs/progress/renames.md` and is recorded there in the same change; migrating users grep for Filament's names.
- **Reasoning with no trail home**, meaning why a rule is what it is and how the tests, assets, monorepo and instruction files work, is explained in `docs/internals/<topic>.md`.
- **`DocumentationFilesTest` fails on a broken link or anchor,** so renaming a heading or moving a file fixes its links in the same change.
- **`docs/superpowers/` is local and gitignored**, so a committed file never links into it.
- **`docs/principles.md`** outranks every other document.

Background: `docs/progress/README.md`.
