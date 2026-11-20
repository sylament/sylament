# Growth log diagnostics

The state files are in `~/.claude/growth-log/state/`.

| Symptom | Where to look |
|---|---|
| No question or no entry instruction after a commit | `jq '.hooks' .claude/settings.local.json`; the call's output must name the commit as `[<branch> <sha>]`, which under `git commit -q` only the repository's `post-commit` hook prints: `install.sh --check` names a repository that lacks it; stderr thrown away (`2>/dev/null`, or `2>&1` into a pipe that cuts it) and a call that went to the background are not seen; the commit must be by `user.email` in a watched repository; `cut -f2,5,6,7 queue.tsv`; `ls {asked,consent,committed}-<session>`, since a session is asked once and silent after "no" |
| `growth-log-coverage.sh` lists commits never named by a call | each was made in a shell Claude Code spawned with its stderr thrown away or in the background; nothing to repair, the count is the audit's to judge |
| A message that the install and the skill differ | `install.sh --check` lists what differs; carry a change made in an installed hook or a log template into the skill, then `install.sh --update` |
| No audit reminder, or one too often | `GROWTH_AUDIT_DAYS` in the config (0 never reminds); it comes at most once a week, and `audit-reminded` holds the last time |
| Every dispatch is held back | the session's answer is not recorded: `growth-log-consent.sh <session> yes` (or `no`) |
| No question at a dispatch | the session's working directory lies outside every watched checkout and the log, so only its first commit in a watched repository asks; an agent type that only reads never asks |
| The main agent is not handed its subagents' commits, or is handed them again | they are handed over once logged (`GROWTH-INDEX.tsv`, column 5 is the agent) and only those after the main agent's own last row; `offered-<session>` holds how many were handed over, and deleting it hands them over again |
| A message "AI growth log: …" | the cause is in `hook-errors.log`: missing `jq`, missing log, empty `user.email`, missing config, a held lock |
| `queue.pending.*` files | a commit hook found the queue locked; the next lock holder merges them |
| `flush` refuses | it names the sha missing from `log/`, or the argument that matches no waiting commit of the session |
| `queue.tsv.flushing.<pid>` is left behind | a flush died holding the queue aside: append it to `queue.tsv` and delete it |
| A sha in the log no longer resolves | `~/.claude/growth-log/hooks/growth-log-heal.sh --check`, then without `--check`; `GROWTH-REMAP.tsv` in the log maps a replaced sha to the current one |
| Rows of a session nobody will finish | `~/.claude/growth-log/hooks/growth-log-consent.sh <session> no` drops them; session end does the same |

Uninstall: delete the growth-log entries from every event in each settings file, delete
`~/.claude/growth-log/`, and in each watched repository delete the pins with
`git for-each-ref --format='%(refname)' refs/growth | xargs -n1 git update-ref -d` and the
`post-commit` hook the installer wrote (its second line says `growth-log-setup: post-commit hook`;
from a hook of your own, delete the one line naming `growth-log-post-commit.sh`). The log holds
the data: delete it only on purpose.
