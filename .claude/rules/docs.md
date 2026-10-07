---
paths:
  - "/docs/progress/**"
  - "/docs/internals/**"
description: Where documentation lives, and the rules every docs file follows.
---
# Docs rules

- **Reasoning with no trail home**, meaning why a rule is what it is and how the tests, assets, monorepo and instruction files work, is explained in `docs/internals/<topic>.md`.
- **State what the code does and why, never when or in which milestone or epic it landed:** git holds that history and nothing in the repository defines the labels, so `DocumentationFilesTest` rejects a milestone label anywhere in `docs/`.
- **`DocumentationFilesTest` fails on a broken link or anchor,** so renaming a heading or moving a file fixes its links in the same change.
- **`docs/superpowers/` is local and gitignored**, so a committed file never links into it, and a decision a spec or plan there makes is recorded in the trail file it governs, or in `docs/internals/` when it spans packages, in the change that implements it.

Background: `docs/internals/agent-instructions.md`, `docs/internals/trail.md`.
