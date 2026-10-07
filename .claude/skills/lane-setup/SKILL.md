---
name: lane-setup
description: Use when creating a git worktree for a lane of parallel work, or starting an agent in one.
---
# Lane setup

## 1. Ask first

Never create a worktree without asking Martin, every time, even when an approved plan mentions worktrees. Say what it buys (which lanes could then run at once) and what it costs (a `composer install` per worktree, a `pnpm install` when it touches assets, its own setup time). With no answer or a no, work in the main checkout.

## 2. Create it outside the repository

```sh
git worktree add ../sylament-worktrees/<lane> -b <lane> <sha>
```

- **Outside the repo**, under `../sylament-worktrees/`: a worktree inside the repository loads every rule twice (once from the worktree, once from the main checkout) and has broken Biome for concurrent sessions.
- **Pin the base** with an explicit `<sha>`, the integrated head of the epic: without one, git bases the branch on whatever `HEAD` is, which may be behind an in-flight epic.
- **Not through tooling that picks the location for you.** A Workflow `agent()` takes no working-directory option (its options are `label`, `phase`, `schema`, `model`, `effort`, `isolation` and `agentType`), and its only worktree mode, `isolation: "worktree"`, creates the worktree under `.claude/worktrees/` inside the repository. Create lanes by hand with the command above.

## 3. Install

1. Run `composer install` in the worktree. It has no `vendor/`, and the root `composer.lock` is not committed, so this is a fresh floating resolution that can pull newer dev tools than the main checkout has; a gate that fails only there may be a tool version, not your change.
2. Run `pnpm install` too if the lane touches assets: the worktree has no `node_modules`, and `pnpm-lock.yaml` is committed, so the toolchain reproduces.

## 4. Give the lane its rules

A lane agent starts in the main checkout, because an agent cannot be started in another directory, so it gets the main checkout's `CLAUDE.md`. Its reads inside the worktree trigger no `.claude/rules/` file, since the worktree lies outside the project.

1. Every plan task names the rule files its lane needs on a `Rules:` line, by matching its files against the `paths:` in the "Rule files" table of `CLAUDE.md`.
2. The lane reads those files **from its worktree** (`../sylament-worktrees/<lane>/.claude/rules/<file>`) before its first edit, so it follows the rules as they stand at its base.
3. Give it absolute paths to anything it needs from the main checkout, such as a plan under `docs/superpowers/`, which is gitignored and so absent from the worktree; it reads the main checkout but writes only its worktree.

## 5. Keep lanes disjoint

- Split lanes by the files each owns, and check the claim mechanically before starting: one broad search-and-replace or shared test file reaching into another lane breaks it. Give each lane the trail files that own its code (`owns:`); README indexes are generated, so a lane never edits them: regenerate after integrating.
- Exactly one task owns each shared artifact, at the end. Each `packages/*/assets/dist` is committed and CI fails when it differs from a fresh build, so one lane rebuilds `dist` at close-out and the others restore it after their own builds.

## 6. When PHPStan misbehaves in a worktree

- **Internal errors naming another worktree's path:** the PHPStan cache under `$TMPDIR` is shared by every checkout, since `phpstan.neon.dist` sets no `tmpDir`. Clear `$TMPDIR/phpstan` and `$TMPDIR/cache/PHPStan`, then rerun.
- **Errors naming a deleted worktree's path:** a stale `vendor/phpstan` install baked it in. Reinstall it with `composer install`.
