# Growth log writing rules

The shape of a daily file is in [TEMPLATE.md](./TEMPLATE.md). This is what the shape does not say.

## Certainty markers

The same as in [PROFILE.md](./PROFILE.md). Every claim that is not trivially verifiable from the
diff carries one of them:

| Marker | When |
|---|---|
| `Observed` | I saw the output — a line of code read, a test run, a DB row, a server response |
| `Inferred` | follows from what was observed but was not observed itself; give the certainty (low / medium / high) |
| `Unknown` | I had no way to verify it, or deliberately chose not to |

With `Observed` the entry also says **what with** — `file:line`, a test name, a command. A claim
without a source is `Inferred`, however certain it sounds.

## What does not belong in an entry

- Unmeasured metrics. No "saved an hour", "30 % faster" unless it was measured.
- Celebratory claims. The log is material for comparison against the baseline, not an evaluation.
- Emoji — exports of the log go into documents and slides, where characters outside Unicode's
  basic plane can break saving.
- Retelling the diff. The diff is in git; the entry holds what the diff does not show — why, what
  was tried and dropped, what remained open.
- Copying the content of skills and plans. A name and a link are enough.

## What always belongs in an entry

- The short sha of every commit the block is about — without it the entry does not pass
  `growth-log-flush.sh` and the heal script cannot keep it alive after a rebase.
- Your reasoning: why this approach, what you weighed and dropped. The entry is written by the
  agent that did the work, right after its commit, so that this can still be recorded.
- What failed. Including your own mistakes: an offered option that did not fit the code, an
  overlooked file, a wrong diagnosis, and every place the human corrected you. This is the only
  part of the log that cannot be reconstructed from the repository later.
- When you are the main agent: where the human corrected or redirected the work since your last
  entry, whoever's commit it concerned. A subagent never sees the human, so no other entry can
  hold it.
- Work on your own tooling (hooks, skills, configuration, memory), even when it has no commit in
  this repository: the hook lists it beside your commits. In the queue such a row has `sha = -`.

## Writing an entry

The hook that hands an agent its commits names them, the entry file and the closing command.

1. Read what each commit holds: `git -C <path> show --stat <sha>`.
2. Write to `log/<today's date>.md`. If the file does not exist, create it from `TEMPLATE.md` —
   not from the previous day, which does not need to be read.
3. Add a new `##` block with a shell append (`cat >> <file> <<'EOF'`), never by rewriting the
   file: other agents write the same file at the same time. The block has the shape
   `TEMPLATE.md` shows: the heading with the sha, the `Skills:` line naming the skills used for
   this work (or a dash), then the four sections. When the commit continues the last block of
   the file and that block is yours, extend it with the Edit tool instead and add the sha to
   its heading.
4. Name every sha exactly as the hook listed it.
5. Close with the hook's closing command. It refuses a sha missing from the log and names it.

A sha in an entry is never rewritten: one that an amend or rebase replaced stays as written,
and `GROWTH-REMAP.tsv` maps it to the current one.

## The main agent's block

A subagent writes the entry for its own commit, but it never sees the human. When the hook hands
the main agent commits its subagents logged, the main agent adds what only it holds:

1. Append one `##` block of your own, in the shape `TEMPLATE.md` shows, its heading naming those
   commits. It holds where the human corrected or redirected that work, what your review of the
   subagents' results found and what you did about it (a fix, a second dispatch), and what stays
   open.
2. Close it with the hook's closing command, which takes no sha.

When none of that happened since your last entry, write no block: the hook hands each commit
over once.

An entry is never committed by hand: `growth-log-flush.sh` commits it. `GROWTH-LOG.md` is
committed as its methodology says (step 6). The log repository is never pushed.

<!-- growth-log-setup: personal rules below -->
