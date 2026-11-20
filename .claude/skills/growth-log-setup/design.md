# The AI growth log

The growth log is a personal diary of AI-assisted work: for each commit, the agent that made it
writes what it did, why, what it verified and how, what failed and where the human corrected it.
An audit later turns causes that recur across days into changes to `CLAUDE.md`, the rules, the
skills or memory. The skills `growth-log-setup` and `growth-log-audit` hold the procedures; this
file, beside the setup skill, holds the reasons behind them.

## Entries come from the agent that did the work

What failed, what was weighed and dropped, and where the human corrected the agent exist only in
the conversation. A later session working from the transcript reads a large, partly compacted
file at a token cost, so the part that makes an entry worth having comes out weakest. An entry
is therefore written right after the commit, by the agent that made it, subagents and workflow
agents included; nothing is kept for a later session to reconstruct. A session that says no, or
ends with entries unwritten, drops its waiting items, and their patch-ids go to `never-log.tsv`,
so a cherry-pick or rebase that rewrites those commits does not queue them again.

A subagent never sees the human, so its entry cannot hold where the human corrected the work or
what the main agent's review of it found, and in a session where only subagents commit nobody
would write that down. The Stop hook therefore hands the main agent the commits its subagents
logged, for a block of its own, closed by a flush that takes no sha. An entry the main agent
wrote after them already covers them, so only commits later in the index than its last row are
handed over, and each only once: the hook cannot tell whether anything was corrected, so the
main agent decides whether there is a block to write, and is not asked again.

## Which agent made a commit

Every subagent and workflow agent shares the main agent's session id, in hook input and in the
Bash environment alike; only hook input tells them apart, by `agent_id`. So a commit is credited
from the output of the tool call that made it: `git commit`, `--amend`, `cherry-pick` and
`revert` print `[<branch> <sha>] <subject>`, the PostToolUse hook of that call reads the line from
`tool_response`, and the hook input names the agent. A failing call counts as well
(PostToolUseFailure), since `git commit … && composer test` fails after committing; its output
comes in `error`.

That keeps out a commit made by a parallel lane, by another session or by the human in a
terminal, whichever worktree it is in, and a fast-forward merge of a lane, which prints no such
line. Earlier designs that compared HEADs or scanned worktrees credited a commit to whichever call
noticed it first, often not the one that made it.

`git commit -q` prints nothing, and agents commit quietly far more often than not. Each watched
repository therefore carries a `post-commit` hook that prints the same line
(`growth-log-post-commit.sh`). The line lands in the output of the call that ran the commit, so
the commit stays tied to its call; a time window or a HEAD comparison in its place would bring
the wrong credit back. The hook prints only in a shell Claude Code spawned
(`CLAUDE_CODE_CHILD_SESSION`, set in the shell of every Bash call, a subagent's included), so the
human's own commits print nothing extra. It also stays silent while a rebase replays commits:
git runs `post-commit` once per replayed commit, and the rebasing agent would otherwise be
credited with every commit nobody had logged. Git does not run it for a stash, a merge or
`commit-tree`. Git itself prints the line once more, for the commit `git rebase --continue`
makes after a conflict, so the commit hook also passes over a commit whose worktree reflog
records a rebase step as the entry that made it.

What no output names is not logged: a call that threw stderr away, where git sends a hook's
output (`2>/dev/null`, or `2>&1` into a pipe that cuts it; a plain pipe leaves the line alone),
and a call that went to the background or was interrupted, since its PostToolUse comes before
the commit or not at all. That gap is counted instead of assumed away: the `post-commit`
hook notes every commit it names in `commits.tsv`, the commit hook notes every commit a call's
output named in `seen.tsv`, and `growth-log-coverage.sh` gives the audit the difference. Both
files are only appended to, since they are written outside the lock. Only commits by
`user.email` in a watched repository count, so a session working elsewhere logs nothing.

Commit identity is `git patch-id --stable`, which survives amend, rebase and cherry-pick. Each
queued commit is pinned in `refs/growth/<patch-id>` of its own repository, so the sha in an entry
survives a squash merge, a deleted branch and `git gc`. An amend of a logged commit moves its index
row to the new commit instead of logging the work twice, and heal does the same for a rebase.

## The daily files are only appended to

Agents of several lanes add their blocks to the same daily file at the same time, so a block is
appended, never written by rewriting the file. Nothing rewrites the files afterwards either: a sha
an amend or rebase replaced stays in the text, resolves through its pin, and `GROWTH-REMAP.tsv`
records old → new. Rewriting the files to the current sha would drop a block another agent
appended in the meantime.

## One question per session

The human is asked once per session whether to log it, by the main agent, since subagents have no
`AskUserQuestion`. Every dispatch of a subagent that can commit waits for the answer, and the main
agent's first commit asks if nothing did before: asked earlier, the question would interrupt
sessions that never commit; asked later, subagents would already have logged work the human may
not want logged. Holding back only the first dispatch would let the others of the same message
start before the answer. An unattended run has nobody to answer, so it answers yes and says so in
its report. The dispatches wait only in a session working in a watched checkout, a worktree
included, or in the log: registered for every project, the hook would otherwise question
sessions that have nothing to log, and a commit such a session still makes in a watched
repository asks then. Inside is decided by path, not by the repository git finds there, since
the log is a repository of its own nested in a watched one and its methodology has the agent
`cd` into it; a working directory the hook cannot read is treated as inside.

## Install questions

- The log may live inside the repository only where git ignores it: its entries would otherwise
  be one `git add -A` away from being published with the code.
- The hook registration names paths in the user's home directory, so it goes into each watched
  repository's local, git-ignored settings file, never into the tracked `.claude/settings.json`.
- The `post-commit` hook is written only where a repository has none. A hook of the repository's
  own, or a `core.hooksPath` directory, may be tracked or shared with other tools, so the
  installer prints the line to add and `--check` reports the repository until it is there.
- A tooling edit has no commit to ask about, so it rides on the same agent's next entry of the
  session, and an agent that never commits leaves it unlogged: asking at the end of the turn
  would interrupt every session that only saves a memory.
- The extraction guard runs on every Read, Grep and Glob call; the audit records its own
  extraction row, so the guard matters only for extractions outside an audit.
- Allow rules for the closing commands keep a background subagent's flush from raising a
  permission prompt in the main session; they widen what runs unprompted, so the human decides.

## The audit reminder

Nothing in a working session calls for an audit, and the log is worth what the audits make of
it. The Stop hook therefore tells the human when ten days of entries wait, at most once a week
and only on a stop with nothing else to say. It is a `systemMessage`, shown to the human:
`additionalContext` on Stop would keep the turn going. Days are counted as daily files after the
last processed day, which needs no date arithmetic and counts days of work, not calendar days.

## Two copies, one guard

The hooks run from `~/.claude/growth-log/hooks/`, outside every checkout, so that switching
branches never removes them, and the templates live in the log. Each is a copy of what the skill
ships, and an edit lands on whichever side the agent was looking at, usually the installed one,
since error messages name it. `install.sh --check` compares the two sides; the tooling hook runs
it after an edit to either and hands the agent what differs, once per file and day.

## Where the skills live

The log, the hooks, the state and the registration are per-user and outside git. The two skills
hold none of it, so this repository tracks them in `.claude/skills/`, and their edits reach the
log through the commits that carry them. The tooling hook queues those edits too, so an edit to
a skill is mentioned twice: as a tooling row and in its commit's entry. The installer warns while
a skill's directory is neither tracked nor excluded, since one `git add -A` would then commit it
unasked. `InstructionFilesTest` holds the skills' name, description, size and freedom from
history, and `DocumentationFilesTest` every link, path and script their files name.

## Concurrency

Every writer of the queue, the index and the remap file holds one lock, a directory created with
`mkdir` (atomic, and macOS ships no `flock`) holding the holder's pid. A lock whose holder no
longer runs, or which is older than five minutes, is stale; taking it over needs a second lock,
so only one waiter at a time checks and removes it, and a waiter that finds the lock fresh again
leaves it. A commit hook that cannot get the lock writes its rows to a pending file, which the
next lock holder merges. A flush closes only the commits it is given, so one agent never waits on
another agent's unwritten entry; given none, it commits a block no commit waits for, which is how
the audit closes its statistics while other sessions work.

## Where the log lives

The log is a git repository of its own with no remote, inside a directory this repository
ignores. Its history shows when an entry changed, which the extraction methodology checks; having
no remote means it cannot be pushed by accident; and `git clean -fdx` in this checkout skips a
nested repository, so only a doubled `-f` deletes it.

## How the hooks talk to the agent

The hooks hand over facts through `additionalContext`, the commits waiting and the command that
closes them, and the procedure lives once in `WRITING-RULES.md`; the docs advise factual
statements over imperative instructions in hook context, and `additionalContext` shows as hook
feedback where `decision: "block"` shows as a hook error. The one exception is the dispatch gate,
which denies a dispatch with the question as its reason. In auto mode a subagent delivers its
report before it stops, so the SubagentStop reminder can come after the report; what a subagent
leaves unwritten is dropped at session end.
