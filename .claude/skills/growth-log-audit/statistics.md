# Growth log audit: statistics

The sections named here (§1, §2, §3, §5, §6, §7) are those of `SKILL.md` beside this file.

The last output of the audit. Every number has a source, a command or a file, or it is `Unknown`.

Three sentences first: the day range and which option from §1 the human chose, what the audit
was based on, and how the decisions were made. Then:

| Metric | Value | Source |
|---|---|---|
| Days in range / days with an entry | | the Extractions table, `ls log/` |
| Commits made under Claude Code / never named by a call / logged / dropped at session end | | the coverage command below |
| "What failed" blocks read | | `grep -c '^## '` over the §2 output |
| Clusters / recurring on 2 or more days | | §3 |
| Points proposed / accepted / dropped | | §5 |
| Files changed: `CLAUDE.md` / rules / skills / memory | | `git show --stat <sha>` per commit |
| `/doctor prompt-audit` findings before / after | | the outputs the human pasted |
| Claude Code claims checked in the docs / refuted | | the URLs in the points |
| `SKILL-DEBT.md` rows open / closed | | the file's diff |
| Skills in `Skills:` lines / skills invoked | | §2, and the command below |
| Dispatches: `Agent` calls by type and model / `Workflow` runs | | the same command |

```
~/.claude/growth-log/hooks/growth-log-coverage.sh "$FROM" "$TO"
```

A commit never named by a call is work the log does not hold: the call threw stderr away
(`2>/dev/null`, or `2>&1` into a pipe that cut it) or went to the background. The command lists each one; when
they are more than a few, that is a point for §4, not a footnote. "Made under Claude Code"
counts what the post-commit hook journaled, so it is zero for days before the hook existed;
"your commits on branches" then shows what the range held.

```
P=~/.claude/projects/$(git rev-parse --show-toplevel | sed 's#[/.]#-#g')
# every depth: subagent and workflow transcripts sit under <session>/subagents/
# -R with fromjson? skips the last line of a transcript still being written
find "$P" -name '*.jsonl' -newermt "$FROM" -print0 | xargs -0 cat |
  jq -R -r 'fromjson? | select(.type=="assistant") | .message.content[]? | select(.type=="tool_use")
    | if .name == "Skill" then "Skill:" + .input.skill
      elif .name == "Agent" then "Agent:" + (.input.subagent_type // "general-purpose") + ":" + (.input.model // "inherited")
      elif .name == "Workflow" then "Workflow"
      else empty end' |
  sort | uniq -c | sort -rn
```

An `Agent` row ending in `inherited` passed no model, so the agent type's own or the session's
applied; a Workflow's agents show up only as the one `Workflow` row, since their models are set
in its script.

**Impact is measured by the next audit, never estimated on the day** (`WRITING-RULES.md`: no
unmeasured metrics). For every accepted point, record now, in the write-up and in the Output
column, on how many days its cluster occurred before the change. The next audit counts the same
over the days after the change and opens its statistics with "before N days / after M days" for
each. A `/skill-doctor` output from before and after the change belongs there too, as `Observed`.

The statistics also go into the log, or the next audit has nothing to compare against: append
them to `log/<today>.md` as a block of their own, `## Audit statistics <from> → <to>`, with the
three sentences, the table and the before-counts. Append it; a block already closed is not
edited. Close it with

```
~/.claude/growth-log/hooks/growth-log-flush.sh --session "$CLAUDE_CODE_SESSION_ID" --agent main
```

With no sha, the flush commits the block and the audit's own tooling rows and leaves every
other agent's commits waiting, so it works when the audit committed nothing and while another
session is mid-work. A flush with no arguments at all would try to close the whole queue.
