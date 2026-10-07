---
name: docs-audit
description: Use when closing a milestone or epic, to check that CLAUDE.md, the rules, the skills and the port trail still match the code it changed.
---
# Docs audit

`composer test` already runs the mechanical half on every change:
- `InstructionFilesTest` checks budgets, anchored globs, the rule index and that there is no history.
- `DocumentationFilesTest` checks links and anchors, the paths and scripts the instruction files name, and that no trail file is orphaned.
- `TrailFilesTest` checks the trail's frontmatter, rows and ownership, that the README indexes are current, and that no trail file passes 90 KB.

This skill is the judgment half: are the sentences still true, and what did the milestone add that nothing states yet?

1. **Make the mechanical half green first.** A broken link or a stale glob is not worth an agent's judgment.
   `vendor/bin/pest -c packages/schemas/phpunit.dist.xml --test-directory=packages/schemas/tests packages/schemas/tests/Architecture/InstructionFilesTest.php packages/schemas/tests/Architecture/DocumentationFilesTest.php packages/schemas/tests/Architecture/TrailFilesTest.php`
2. **Fix the range.**
   - BASE is the commit the milestone started from: its first commit's parent, or `git merge-base main HEAD` on a milestone branch.
   - HEAD is the milestone's last commit.
   - `git diff --name-only BASE..HEAD` is the work list.
3. **Map each changed file to everything that documents it:**
   - the rules whose `paths:` match it (the "Rule files" index in `CLAUDE.md` lists every glob)
   - the skills that name it or the commands it changes
   - the trail file whose `owns:` covers it (`grep -rlF -- '- <path>' docs/progress`, or the nearest directory entry)
   - the `docs/internals/` file that explains its subsystem
   - `CLAUDE.md` itself, when the change touches a command, a gate, the map or a package's dependencies
4. **Fan out, one agent per affected file.** Use a Workflow: a cheaper model maps, a strong one verifies. Each agent:
   - splits its file into claims and verifies each against the code at HEAD with file:line evidence, as kept, corrected (old → current), or no longer true
   - lists what the diff introduced that its file should state and does not: a convention or trap for files it covers, a component or concern with no trail row, a command or path that changed
   - lists the rule lines whose files the diff removed or moved
5. **Synthesize** the findings into one local ledger under `docs/superpowers/`, ranked: wrong today, missing, redundant.
6. **Fix in one pass,** following `.claude/rules/instructions.md`:
   - one home per fact
   - each rule followed by one clause of why
   - no history
   - a convention for files no rule covers gets a new rule file and its index row

   Then re-run step 1.
7. **Report** the corrections (old → current), the additions, and anything left for the maintainer's call.

A true fact filed in the wrong place moves; it is never duplicated. A rule the code now enforces by itself, through a test or a refusal that names the fix, can shrink to a pointer.
