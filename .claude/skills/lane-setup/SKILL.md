---
name: lane-setup
description: Use when creating a git worktree for a lane of parallel work, or starting an agent in one.
---
# Lane setup

## 1. Ask first

Never create a worktree without asking Martin, every time, even when an approved plan mentions worktrees. Say what it buys (which lanes could then run at once) and what it costs (a copy-on-write `vendor/` per worktree, a `pnpm install` when it touches assets, its own setup time). With no answer or a no, work in the main checkout, where each lane's test runs set the lane's own `SYLAMENT_TEST_RUN` (`SYLAMENT_TEST_RUN=lane_a composer test:pest`): two root-suite runs in one checkout otherwise share their worker directories and databases, and the later one stops before its first test.

## 2. Create it outside the repository

```sh
git worktree add ../sylament-worktrees/<lane> -b <lane> <sha>
```

- **Outside the repo**, under `../sylament-worktrees/`: a worktree inside the repository loads every rule twice (once from the worktree, once from the main checkout) and has broken Biome for concurrent sessions.
- **Pin the base** with an explicit `<sha>`, the integrated head of the epic: without one, git bases the branch on whatever `HEAD` is, which may be behind an in-flight epic.
- **Not through tooling that picks the location for you.** A Workflow `agent()` takes no working-directory option (its options are `label`, `phase`, `schema`, `model`, `effort`, `isolation` and `agentType`), and its only worktree mode, `isolation: "worktree"`, creates the worktree inside the repository, in a `worktrees` folder under `.claude/`. Create lanes by hand with the command above.

## 3. Install

1. Clone the main checkout's `vendor/` and `composer.lock` copy-on-write (`cp -cR vendor <wt>/vendor`, `cp -c composer.lock <wt>/composer.lock`), never `composer install`: the root `composer.lock` is not committed, so an install there is a fresh floating resolution that can pull newer dev tools than the main checkout runs, and a gate failing only in the lane is then a tool version, not the change.
2. Give the lane its own caches: a `<wt>/phpstan.neon` (gitignored) that includes `phpstan.neon.dist` and sets `tmpDir: var/phpstan`, and `TMPDIR=<wt>/var/tmp` on every composer and `vendor/bin` command, because PHPStan, Rector and the other tools otherwise cache under the temp dir every checkout shares.
3. Run `pnpm install` too if the lane touches assets: the worktree has no `node_modules`, and `pnpm-lock.yaml` is committed, so the toolchain reproduces.

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
