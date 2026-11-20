---
name: growth-log-setup
description: Use when installing, updating or diagnosing the personal AI growth log (the commit-triggered diary of how work with the agent went), or when porting a change between this skill and an install.
---

# Growth log: setup

What gets installed, per user:

| Where | What |
|---|---|
| `~/.claude/growth-log/config` | every path and switch, written by `install.sh`; the extra repositories are in `state/repos` |
| `~/.claude/growth-log/hooks/` | a copy of `hooks/`, outside every checkout, so switching branches never removes it |
| `~/.claude/growth-log/state/` | the queue, the per-session answers and markers, `never-log.tsv`, `dropped.log`, `commits.tsv` and `seen.tsv`, `hook-errors.log`: work in progress, not content |
| the log, `docs/superpowers/growth-log/` by default | a git repository of its own with no remote, holding `log/`, `GROWTH-INDEX.tsv`, `GROWTH-REMAP.tsv` and the templates, inside a directory this repository ignores |
| the local settings file (.claude/settings.local.json) of this and every watched repository | the hook registration, git-ignored because it names paths in your home directory |
| a `post-commit` hook in this and every watched repository, shared by its worktrees | the line that names a commit in the output of the call that made it, so a `git commit -q` is seen |

How it works:

- **Claiming commits.** After a Bash call that runs git, `growth-log-on-commit.sh` reads the
  call's output: every `[<branch> <sha>]` line names a commit of the agent that made the call.
  `git commit`, `--amend`, `cherry-pick` and `revert` print that line, and under `-q` the
  repository's `post-commit` hook prints it (`growth-log-post-commit.sh`), only in a shell
  Claude Code spawned and never while a rebase replays commits; a commit `git rebase --continue`
  itself prints is passed over too. Only commits by `user.email` in a watched repository count.
  A commit whose output never reaches the hook is not logged: stderr thrown away (`2>/dev/null`,
  or `2>&1` into a pipe that cuts it), a call that went to the background, `git commit-tree`.
  `growth-log-coverage.sh` counts those for the audit.
- **The question.** The human is asked once per session, by the main agent: in a session working
  in a watched repository, every dispatch of a subagent that can commit waits for the answer
  (`growth-log-on-dispatch.sh`), and the main agent's first commit asks if nothing did before.
  `growth-log-consent.sh` records the answer; an unattended run answers yes and flags it.
- **Writing.** After yes, each committing agent is told its commits wait for an entry. It writes
  the entry as `WRITING-RULES.md` says under "Writing an entry" and closes it with
  `growth-log-flush.sh --session <id> --agent <agent> <sha>…`.
- **The main agent's block.** Only the main agent hears the human, so at the end of its turn it
  is handed the commits its subagents logged since its own last entry, once each, for a block
  on what the human corrected and what its review found (`growth-log-on-stop.sh`).
- **Unwritten entries.** A subagent about to finish with entries unwritten is told so once
  (`growth-log-on-subagent-stop.sh`); the main agent's own leftovers come up at the end of its
  turn (`growth-log-on-stop.sh`); whatever a session leaves at its end is dropped
  (`growth-log-on-session-end.sh`, counted in `dropped.log`). After "no", nothing of the session
  is queued.
- **Tooling and extractions.** `growth-log-on-tooling-edit.sh` queues edits to tooling that never
  gets a commit (your `~/.claude` instructions, skills, hooks, agents and auto-memory) and to
  these two skills. They are mentioned in the entry of the same agent's next commit of the session;
  an agent that never commits leaves them unlogged. `growth-log-on-extraction.sh` reminds the
  agent to record an extraction of the log.
- **The audit reminder.** At most once a week, a stop with nothing else to say tells the human
  that ten days of entries wait for an audit (`--audit-days N`; 0 never reminds).

The reasons behind each choice, the install questions' included, are in `design.md` beside this
file.

Prerequisites: Claude Code, `git` 2.28+, `jq`, `python3`, and `git config user.email` set,
since your commits are recognised by it.

## 1. Install: ask, then run the installer

Run the smoke test first. It uses a throwaway home directory and repository, so it proves the
scripts work on this machine without touching anything real:

```
bash .claude/skills/growth-log-setup/smoke-test.sh
```

Then ask with `AskUserQuestion` (two calls of up to four questions, the default first):

| Question | Options | Flag |
|---|---|---|
| Where should the log live? | `docs/superpowers/growth-log/`, git-ignored / another path via "Other" | `--log PATH` |
| Log commits from other repositories too? | `../demo` / only this one / list them via "Other" | `--repos P1,P2` |
| Register the hooks per repository or for every project? | per watched repository, in its local settings file / every project, in `~/.claude/settings.json` | `--settings local\|user` |
| Queue edits to your own tooling that never get a commit, for the next entry of the agent that made them? | yes / no; an agent that never commits leaves them unlogged, and no also turns off the drift guard (§2) | `--tooling` |
| Remind the agent to record each extraction of the log? | no / yes | `--extraction-guard` |
| Allow the agents' closing commands without a permission prompt? | yes / no | none: add `Bash(<hooks>/growth-log-flush.sh *)` and `Bash(<hooks>/growth-log-consent.sh *)` to `permissions.allow` in the same settings file |
| Anything that must never appear in your log? | nothing beyond the defaults / list via "Other" | none: bullets below the marker in `WRITING-RULES.md` |

Do not offer `--settings project`: `.claude/settings.json` is tracked here. Not offered at all:
a cadence other than one question per session and an entry per commit, and backfilling past days.

```
bash .claude/skills/growth-log-setup/install.sh [--log …] [--repos …] [--settings user] \
    [--tooling no] [--extraction-guard yes]
```

Claude Code reloads settings while it runs, so the hooks are live at once in every running
session of the watched repositories: install while no other session is working in them. The
installer leaves a `post-commit` hook of the repository's own, and a repository with
`core.hooksPath`, alone and prints the line to add there; until it is added, a quiet commit in
that repository is not logged, and `--check` says so.

The answer to the last question goes as bullets **below** the line
`<!-- growth-log-setup: personal rules below -->` in `<log>/WRITING-RULES.md`, then
`git -C <log> commit --no-verify -am "docs(ai): personal writing rules"`. Everything below that
marker, and the rows of the extraction table, are personal and never count as drift.

The live check covers what the smoke test cannot: it hands JSON straight to the scripts, so the
registration, its `Bash(git *)` filter and the input Claude Code really sends are untested until
a session runs. In the first session after the install:

1. Dispatch a subagent that can commit: the "Growth log" question comes first. Answer yes.
2. Have it commit quietly inside a compound call (`cd <repo> && git add -A && git commit -q -m …`):
   it is handed its entry instruction and writes the entry, and its flush runs without a
   permission prompt when the allow rules were added.
3. End the main agent's turn: it is handed that commit for a block of its own.
4. `~/.claude/growth-log/hooks/growth-log-coverage.sh <today> <today>` reports the commit as
   made under Claude Code and logged, and none as never named by a call.
5. The first time a Workflow agent commits, read column 5 of its row in
   `<log>/GROWTH-INDEX.tsv`: it names that agent, not `main`.

For a step that fails, start from `diagnostics.md`. For the baseline, hand the user the prompt in
`profile-prompt.md` beside this file.

Never add a remote to the log. Being a repository of its own also protects it from
`git clean -fdx` in this checkout, which skips a nested repository; a doubled `-f` deletes it.

## 2. Update an install

After this skill changed:

```
bash .claude/skills/growth-log-setup/smoke-test.sh
bash .claude/skills/growth-log-setup/install.sh --update   # re-copies the hooks, re-registers them, writes a missing post-commit hook
bash .claude/skills/growth-log-setup/install.sh --check    # what still differs
```

`--check` compares the hooks byte for byte, `TEMPLATE.md` whole, `WRITING-RULES.md` above the
personal-rules marker and the methodology section of `GROWTH-LOG.md`. Templates in the log are
never overwritten: a reported difference is either a local customisation to keep or an upstream
change to copy by hand, so show the user the `diff -u` line from the report and let them decide.
A change made in an installed hook or template is carried back into `hooks/` or `templates/`
here, without personal paths or data, until `--check` is clean. The drift guard runs the same
check on its own: after an edit to an installed hook, to a template in the log or to this skill,
the agent is handed what differs, once per file and day.

## 3. Diagnostics

Symptoms, causes and the uninstall steps are in `diagnostics.md` beside this file.

## 4. What stays personal

This skill ships no one's log. Never copied into it, and never committed to this repository:
`log/`, the rows of the extraction table, a filled-in `PROFILE.md`, `SKILL-DEBT.md` rows, the
`repos` list, and paths containing a user name. The hooks read every path from the config, so a
hook with a literal home path in it is a bug. The two growth-log skills hold none of it, so a
repository may track them; the installer warns while their directory is neither tracked nor
excluded, since one `git add -A` would then commit them unasked.
