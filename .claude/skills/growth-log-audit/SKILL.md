---
name: growth-log-audit
description: Use when asked to audit or process the AI growth log, to turn the stretch since the last audit into decisions on what repeatedly slows the work down and what to change in CLAUDE.md, the rules, the skills or memory.
---

# Growth log: audit

The log is the repository the `growth-log-setup` skill installed: its path is `GROWTH_LOG` in
`~/.claude/growth-log/config`, written `<log>` below. Daily entries are in `log/`, their shape in
`TEMPLATE.md`, the writing rules in `WRITING-RULES.md`. The knowledge base the audit judges is
this repository's `CLAUDE.md`, `.claude/rules/`, `.claude/skills/` and the reasoning in
`docs/internals/`, the user's `~/.claude/CLAUDE.md` and skills, and this project's auto-memory
(`MEMORY.md` and its files). Merely concatenating the log is not this skill: that is the
methodology in `<log>/GROWTH-LOG.md`.

## 1. Range: the human picks the start

1. Read `## Extractions` in `<log>/GROWTH-LOG.md` before anything in `log/`. Its methodology
   gives the last processed day and checks whether that day changed later.
2. **Before the first read of `log/`, ask which day the audit starts from.** One
   `AskUserQuestion`, header "Range":
   - first, recommended: **since the last audit**, the day after the last `processed` row (or
     that day, when it changed later);
   - then **older checkpoints**, the ends of earlier `processed` rows, newest first, at most two;
     with fewer, **from the start of the log**. Each option's description gives the start date,
     the number of days in range and, in one sentence, what that audit produced (its Output);
   - say in the question that a custom range or filter ("only the last week", "only days with a
     self-review") goes through "Other", or the human will not know it exists.

   Translate such an answer into a day range or a block filter and repeat it in one sentence
   before reading; when it does not translate unambiguously, ask before reading anything.
3. **The range binds the extraction script too:** days before it are not read. An older
   checkpoint reaches into processed days, so points decided then are not offered again; new are
   only clusters that recurred since or that the earlier audit missed. Say so in the statistics.
4. Create the Extractions row **at the start**, with status `copied only`. Switching it to
   `processed` is this skill's last step (§7), so the human never has to remind you of it.
5. Read the earlier `processed` rows' Output too: what was dropped, and why, is not offered again
   without new evidence.

## 2. Inputs

Pull the blocks out with the script beside this skill, not by reading day after day. `FROM` and
`TO` are the range:

```
python3 .claude/skills/growth-log-audit/extract-blocks.py <log>/log "$FROM" "$TO" > "$TMPDIR/growth-fails.md"
grep -c '^## ' "$TMPDIR/growth-fails.md"
```

The pattern blocks come out the same way, with `pattern` as a fourth argument.

| Input | How | What it measures |
|---|---|---|
| Coverage of the log | `~/.claude/growth-log/hooks/growth-log-coverage.sh "$FROM" "$TO"` | how many of the range's commits the log holds; it lists each commit no call's output named, which is work the log lacks |
| Recorded skill use | `grep -H '^Skills:' log/<range>.md`, one line per block | what the agent wrote down, not what ran |
| Actual skill use | the `Skill` calls in the session transcripts (§8) | what ran; when the oldest transcript starts after `FROM`, the count is a lower bound |
| Dispatches | the `Agent` and `Workflow` calls in the same transcripts (§8) | how the work was split, by agent type and model; the same lower bound |
| Skill cost and use | ask the human to run `claude -p /skill-doctor` (Claude Code 2.1.252+ with feature-flag fetching; interactively it opens in a tab, which cannot be pasted) and paste the output | what each skill costs in context and how often it fires; no output means `Unknown` |
| Contradictions in instructions | ask the human to run `/doctor prompt-audit <path>` (Claude Code 2.1.283+) before the first edit and after the last, on the same model; it runs through the bundled `/claude-api` skill, so never propose turning that one off | stale or contradictory instructions and dead references; the finding counts go to §8 as before and after |
| Open skill debt | `<log>/SKILL-DEBT.md` | verify that every open row still holds |
| Knowledge-base state | size of `CLAUDE.md` against its 12 KB cap, the rules and their `paths:`, the skills, the `MEMORY.md` index | part of the assessment in §3 |

A prompt-audit finding is evidence like a log block, not an edit to apply: verify it against the
repository (§3) and fold it into the points the human decides on, since applying it directly
would skip the review.

## 3. Analysis: clusters across days, three actors

- Cluster the blocks by cause, not by feature, and keep for each cluster **the list of days** it
  occurred on: recurrence across days is a reason to act, a single occurrence usually is not.
- Judge three actors from the start: **how the agent slowed the work down**, **how the human
  did**, **what the agent could have done and did not**, plus the knowledge base as an artefact
  (size, triggers, unpaid debt).
- **Verify every "the knowledge base does not cover this" by reading the file that should cover
  it**, not by grepping one phrasing, since it may say the same in other words. For a rule, also
  check that its `paths:` match the files the case touched: a rule that exists but never loaded
  for that file is a different gap from a missing rule. For a skill, check that its description
  would have triggered it.
- **Judge the auto-memory with each cluster:** read the entries on the cluster's subject. A trap
  that recurs although memory holds it, an entry the repository contradicts and an entry that
  repeats a rule or skill are each a point of their own; a lesson about certain files belongs
  in the rule whose `paths:` load with them, and the point says to move it.
- **Measure every proposed settings change before offering it:** the entry may already be there
  with something else blocking. A claim without a `file:line`, a command or a quote is offered as
  an estimate, never as a fact.
- A sha in an entry that an amend or rebase replaced stays as written; `<log>/GROWTH-REMAP.tsv`
  gives the current one.
- When the log and the repository disagree, the repository wins: the log proves that something
  happened, not that it holds today.
- **A point resting on how Claude Code behaves** (hooks, skills and their loading, settings and
  permissions, auto mode, memory, subagents, commands, the version something applies from) is
  verified in the official documentation (`https://code.claude.com/docs/en/…`, index at
  `https://code.claude.com/docs/llms.txt`), with the literal sentence and its URL in the point.
  A summary of a page, a subagent's or your own, is a lead to it, not evidence: the sentence
  quoted from the fetched page is.
- Repository conventions, the domain and the code are judged against the repository, not
  against Anthropic's documentation.

## 4. Overview, then stop

The first output is one table:

| # | Area | Evidence: log days and repo `file:line` | Why nothing catches it today | Direction |
|---|---|---|---|---|

Order it by urgency and put the three conclusions about the actors under it. Then wait: the
human may merge, drop or add points before any detail.

## 5. Point by point

- One point per turn: the evidence, why nothing catches it, then your recommendation with the
  artefact it produces, the rule line, skill passage or memory entry as it reads now and as it
  would read after. End with one question: take it, change it, or drop it.
- When the evidence points against what the human proposed, say so directly, never as one option
  among several. Keep `AskUserQuestion` previews for a narrow pick between artefacts already on
  the table.
- Every question says how many points remain.
- A dropped point gets a one-sentence reason in the write-up and in the Extractions row, so the
  next audit does not offer it again.

## 6. Write-up

If the human wants one, keep it running after every decision as
`docs/superpowers/growth-review-<date>.md`, which is git-ignored and kept out of instruction
loading. It is never committed: the human takes it over. If it disappears before you finish, ask
whether to write it again.

Structure: the range and the method's limits (inputs, counts as a lower
bound, the repository wins on conflict), the overview `# | Area | Decision`, one section per point
(state, why nothing catches it, recommendation, **decision**), the conclusions about the actors,
the implementation state, and the dropped points.

## 7. Implementation and closing

1. Before the first edit, ask for the target branch in every repository the changes touch. What
   cannot be done now goes into `<log>/SKILL-DEBT.md`, not into memory.
2. A change to an instruction file follows `.claude/rules/instructions.md` and the "Where a new
   fact goes" table in `CLAUDE.md`: the rule goes in, its reasoning goes to `docs/internals/`.
   Run `vendor/bin/pest tests/Architecture/InstructionFilesTest.php tests/Architecture/DocumentationFilesTest.php tests/Architecture/RuleHookTest.php`
   before asking for review: the first two check the instruction files and `docs/internals/`. A memory change keeps one fact per file with its line in `MEMORY.md`.
3. Ask for the after run of `/doctor prompt-audit` on every changed file, on the same model as
   the before run; a new finding is fixed in the same change.
4. Commit only when the human says so, after their review.
5. Close the Extractions row: ask with `AskUserQuestion` which status closes it (`processed`
   first) and confirm the Output sentence, then write the status, the commits and the dropped
   points with their reasons. `GROWTH-LOG.md` is committed separately (methodology step 6).
6. Each of the audit's commits brings its entry instruction like any other commit. At the end,
   append the statistics (§8) as a block of their own and close it as `statistics.md` says. That
   leaves the audit day open, so the next audit takes it again, which is correct.
7. Finish with the statistics (§8), in chat, in the write-up and in the log.

## 8. Statistics

The last output of the audit: the range and method in three sentences, the table of numbers
with the coverage of the log, the before-counts that let the next audit measure the impact, and
the same in the log. Every number has a source, a command or a file, or it is `Unknown`. The
table, the coverage and transcript commands and the rules for impact and for the log are in
`statistics.md` beside this file.
