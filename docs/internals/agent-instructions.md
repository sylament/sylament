# Agent instructions

Why the agent instruction files are shaped as they are: a small root `CLAUDE.md`, path-scoped rules in `.claude/rules/`, procedure skills in `.claude/skills/`, and a check in `composer test`.

## The cost being managed

Claude Code loads the root `CLAUDE.md` into every session and into every subagent except the built-in Explore and Plan agents, and except agents defined with `omitClaudeMd: true`.

A subagent's first request does not read its parent's prompt cache. In practice, the instruction block is paid in full by every agent of a workflow, and then re-read on each of that agent's later requests. When the root file was 131 KB, a session's first request cost about 104k input tokens, and workflow agents started at a median of 83–92k before doing any work. Explore agents, which skip `CLAUDE.md`, started at about 17k.

## How the files load

These facts were verified on Claude Code 2.1.291 with live probes. Each probe was a `claude -p --model haiku` session in a scratch git repository, and every load was attributed through the `attachment` entries of the session transcripts.

**`@path` imports** in `CLAUDE.md` are expanded at launch. They organise a file but save nothing.

**`.claude/rules/**/*.md`** is discovered recursively.
- A rule without `paths:` frontmatter loads at launch, like `CLAUDE.md`.
- A rule with `paths:` loads when the Read, Write or Edit tool touches a matching file, in the main session and in subagents alike.
- `paths` is the only frontmatter field Claude Code reads; other fields, such as our `description`, are ignored without error.
- A rule loads at most once per session.

**Glob matching** is gitignore-like, with one deviation.
- A pattern with a slash in the middle is anchored at the project root. `packages/*/src/**/*.php` matches `packages/x/src/f.php`, but not `vendor/y/packages/x/src/f.php` or `other/packages/x/src/f.php`.
- A leading `/` anchors a root file: `/composer.json` does not match `packages/x/composer.json`.
- A bare name (`composer.json`) matches at any depth.
- So does a single segment followed by `/**`. `src/**` matched `deep/src/c.txt`, where real gitignore would anchor it.
- Brace sets work: `packages/{a,b}/tests/**` matches `a` and not `c`.

**Worktrees.**
- A file in a git worktree outside the project directory triggers nothing: no rules and no nested instruction files. A lane working in such a worktree therefore reads the rule files its task names before it edits.
- A file in a worktree inside the project loads both the worktree's own rules and the main checkout's rules, so every rule loads twice. In-repo worktrees also broke Biome for concurrent sessions, which is why lanes stay outside the repository.

**Nested `CLAUDE.md` files** load on demand when a file in their subtree is read or edited. Three past sessions loaded stale ~120 KB copies of the root file that a plan rig had left under `docs/superpowers/plans/`, at about 50k tokens each.

`claudeMdExcludes` stops that, but its patterns match absolute paths:
- `**/docs/superpowers/**` excluded the copies (both `CLAUDE.md` and `AGENTS.md`).
- The relative `docs/superpowers/**` silently matched nothing.

**Skills** sit in context as a listing of names and descriptions until one is invoked. The listing's budget is 1% of the context window, and descriptions are dropped first when it overflows; a Haiku session listed project skills by name only. That is why skill descriptions stay to one line.

**`.agents/`** is not read by Claude Code.

## Decisions

**Path-scoped rules, with an index in the root file.** A rule that loads when its file is touched needs no judgment from the agent, cannot be skipped, and arrives exactly when it is relevant. An index alone, where the agent must notice a table, judge that its task matches a row and spend a read, misses most often when a change drifts into a new area mid-task. The index still earns its place: a planner reads rules before any file is touched, and lanes outside the repository have nothing else.

**`CLAUDE.md`, not `AGENTS.md`.**
- Claude Code reads `AGENTS.md` only when no `CLAUDE.md`, `.claude/CLAUDE.md` or `CLAUDE.local.md` exists in the working directory or any directory above it. It also depends on a built-in plugin and a user-level `instructionFiles` setting.
- Each of those is a silent off-switch, and everything else in this design is specific to Claude Code anyway.
- Should another tool ever join, the documented interop is cheap: make `AGENTS.md` the real file and leave a `CLAUDE.md` containing only `@AGENTS.md`.

**Rules as real files in `.claude/rules/`.** That is the only directory Claude Code reads rules from. A symlinked directory works when its target sits inside the working directory. A target outside it is treated as an external import, and only its unscoped rules load. Keeping the files there also leaves the `docs/` tree free to be reorganised without switching the rules off.

**A rule, then one clause of why.** A bare rule gets applied literally and misapplied at its edges, while the clause lets a reader generalise. The backstory matters only when changing a rule, so it lives here or in the port trail.

**No history in instruction files.** Milestone tags, dates, hashes and inventories of what shipped cannot be acted on and bury the rules. Git and `docs/progress/` hold them.

**The check.** `packages/schemas/tests/Architecture/InstructionFilesTest.php` enforces:
- the budgets
- that every rule is scoped by anchored globs that each match a tracked file
- that the root index agrees with the frontmatter
- that no tracked `CLAUDE.md` exists below the root
- that instruction files carry no history markers

Without a mechanical limit the root file grew to 131 KB, because a rule asking to keep it current read as an invitation to narrate every change.
