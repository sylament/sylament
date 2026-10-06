---
paths:
  - "/CLAUDE.md"
  - "/.claude/**"
description: How instruction files are placed, scoped, sized and kept free of history.
---
# Instruction-file rules

- **One home per fact.** Grep `CLAUDE.md` and `.claude/` before adding a line; a duplicate drifts, and an agent cannot tell which copy wins.
- **Placement.** A fact every task needs goes in `CLAUDE.md`; one about certain files goes in the rule whose `paths:` cover them; a multi-step procedure becomes a skill; a reason, an alternative or history goes to `docs/progress/` or `docs/internals/`; a component's status is its trail row, and a plan, a backlog or what to build next stays out of the repository.
- **Rules, not history.** No milestone tags, dates, commit hashes, card counts or lists of what shipped: an agent cannot act on them and they bury the rules. The check rejects tags, ISO dates and hash-like tokens.
- **One rule per line:** the imperative, then one clause of why. The why is what lets a reader apply the rule to a case it does not name; anything longer is background and belongs in `docs/`.
- **Every rule file** has frontmatter with `paths:` (a YAML list) and `description:` (one line), and ends with a `Background:` line. A rule without `paths:` loads in every session, which is the cost these files exist to avoid, so the check rejects it.
- **Frontmatter must parse as YAML.** Quote a value that contains `: `; Claude Code loads a rule whose frontmatter does not parse in every session, as if it had no `paths:`.
- **Anchor every glob** at the repo root: `packages/*/src/**/*.php`, or a leading `/` for a root file (`/composer.json`). Claude Code matches a bare name or a single segment plus `/**` (`src/**`) at any depth, so an unanchored glob also fires on vendor reads.
- **Size: quality first.** A rule or skill is as long as its content needs and no longer; aim for a page (~6 KB). Never cut a rule that prevents a real mistake to save bytes, and split only by paths, when some lines govern a narrower set of files, never by size alone. `CLAUDE.md` alone has a hard 12 KB cap, because every agent loads it; the check's 16 KB alarm for a rule or skill only catches a file drifting back into narrative.
- **Keep the index true.** Adding, renaming or re-scoping a rule updates its row in the "Rule files" table of `CLAUDE.md` in the same change; the check compares them.
- **Never name a file `CLAUDE.md` below the root.** A nested `CLAUDE.md` loads whenever a file beside or under it is read; local copies belong under `docs/superpowers/`, which `.claude/settings.json` excludes.
- **Skills** carry a `name` equal to their directory and a one-line `description`, since descriptions share the listing budget with every plugin's skills.

Background: `docs/internals/agent-instructions.md`.
