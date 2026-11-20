# AI Engineering Growth Log

Daily entries following on from [PROFILE.md](./PROFILE.md). The markers `Observed` / `Inferred` /
`Unknown` mean the same as there.

This file is only a signpost. The entries themselves are in `log/`, one file per day:

| Where | What |
|---|---|
| `log/YYYY-MM-DD.md` | one day's entry; a new day is created from `TEMPLATE.md`, an existing one is appended to |
| [TEMPLATE.md](./TEMPLATE.md) | the shape of a daily file |
| [WRITING-RULES.md](./WRITING-RULES.md) | writing rules — what belongs in an entry and what does not |
| [PROFILE.md](./PROFILE.md) | the baseline, not edited after it is created |
| `GROWTH-INDEX.tsv` | which commits are already logged (key `git patch-id --stable`) |
| `GROWTH-REMAP.tsv` | old sha → current sha, for a logged commit an amend or rebase replaced; the old sha in an entry stays as written and resolves through its pin |
| [Extractions](#extractions) | up to which day the log has been processed into an output — these dates live here, not in the agent's memory |

Nobody writes to the log by hand. The human is asked once per session whether to log it; after
"yes", the agent that made a commit, subagents included, writes its entry right away, and
`growth-log-flush.sh` closes it — it checks that every closed sha really is in `log/`, updates
the index and commits. The main agent adds a block of its own on the commits its subagents
logged, since only it hears the human.

The skills used over time can be listed without reading the entries:

```
grep -h '^Skills:' log/*.md
```

## Extractions

Which stretch of `log/` has already been pulled out of this log and what happened to it. This
table is the single source of truth for that — the dates are not kept in the agent's memory or
anywhere outside the repository.

| Event date | Day range in `log/` | Status | Output |
|---|---|---|---|

Statuses:

| Status | Meaning | Consequence for next time |
|---|---|---|
| `copied only` | entries concatenated into one file, nothing derived from them | the stretch is still unprocessed, the next extraction takes it again |
| `processed` | the stretch produced an output (report, snapshot, presentation, change to `PROFILE.md`) | the next processing may start after this day |
| `dropped` | the stretch was pulled out, but no output came of it and none is expected | same as `copied only`, but the reason is recorded |

### Methodology — what to do on an extraction and on processing

Applies to both events: **pulling** the log into one file and **processing** the log into an
output (summary, report, an update of `PROFILE.md`, material for an evaluation). An agent asked to
"concatenate / extract / process the growth log" goes like this:

0. **You do not have to remember this and the human should not have to remind you.** If the hook
   `growth-log-on-extraction.sh` is installed, reading several daily entries (or a glob over
   `log/`) makes it point the agent here. It stays quiet when a row dated today is already in the
   table, when it already asked in this session, and when only today's day was read (that is the
   daily entry). Without the hook, step 1 below remains.
1. **Read this table before anything from `log/`.** The last row with status `processed` says where
   the unprocessed stretch starts:

   ```
   awk -F'|' '/^Statuses:/ {exit} $4 ~ /^ *`?processed`? *$/ {d=$3} END {sub(/.*→/,"",d); gsub(/[[:space:]]/,"",d); print d}' GROWTH-LOG.md
   ```

2. **Check whether the processed stretch changed afterwards.** A block may be added to a day later
   (a postponed commit, a backfill), so a day that was open at the extraction may differ today:

   ```
   git log --format='%h %ad %s' --date=short -- log/<last processed day>.md | head -3
   ```

   If the file's last commit is from a later day, that day is taken into the range again.

3. **Ask the user via `AskUserQuestion` how to record the event** — never fill it in yourself, even
   when it looks obvious. One question, four options:

   `header` "Growth log", `question` "How should this extraction be recorded in Extractions?"
   - `processed` — the stretch produced an output, the next processing starts after this day
   - `copied only` — only pulled out so far, the stretch stays unprocessed (first for a plain concatenation)
   - `dropped` — no output will come, the reason goes into the Output column
   - do not record — add no row (a one-off look, not an extraction)

   Ask about the output too — what came of it and where it is, in one sentence. "not recorded" is
   a valid answer, a guess is not.

   Exception: during an audit (the `growth-log-audit` skill) the row is created with status
   `copied only` without asking — the human already confirmed the range in §1.2 of the audit; the
   question about status and output comes when it closes.

4. **Append a row to the table** as `| event date | first → last day of the range | status |
   output |`. The range is the days the event concerned, not today's date. The row is created
   **at the start** of the work with the status that holds then, and **completed at the end**:
   when the stretch has meanwhile produced an output, the status becomes `processed` and the
   output is named.

5. **Record only what really happened.** The same markers as in `WRITING-RULES.md`: what was not
   verified is `Unknown` and goes under the table as a bullet, not into a cell as a claim.

6. **Commit `GROWTH-LOG.md` separately** — `growth-log-flush.sh` only adds `log/` and the index,
   so this file would otherwise stay uncommitted:

   ```
   git -C <log> add GROWTH-LOG.md
   git -C <log> commit --no-verify -m 'docs(ai): record log extraction state'
   ```

On **processing** additionally: the range from step 1 is binding. Days before it are not read —
they were processed already and a second output from the same days would claim the same thing
twice. If the user hands over a file for processing (a concatenated export), check its header
against this table; if the ranges disagree, the table wins and the difference is named.

### Concatenating the whole log into one file

```
cd <log>
for f in $(ls log/*.md | sort); do \
    printf '\n<!-- ===== %s ===== -->\n\n' "$f"; sed 's/^#/##/' "$f"; printf '\n---\n'; \
done > growth-log-export-$(date +%F).md
```

`sed 's/^#/##/'` shifts the headings one level down so the days sit under one H1. The output
**does not belong in the log or in any repository** — it is temporary and handed over
elsewhere.

If the export leaves the team, anonymise it: no credentials, e-mails, user names, absolute paths
containing a user name, internal host names, company name, product names, project codes or ticket
keys. The export's header says what is **not** anonymised — without identifiers in code (classes,
repository paths, table names) the entries cannot be analysed, so those stay, and that is a
deliberate decision, not an oversight.

<!-- growth-log-setup: methodology end -->
