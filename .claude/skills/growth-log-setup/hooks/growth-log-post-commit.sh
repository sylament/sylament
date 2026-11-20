#!/usr/bin/env bash
# git post-commit hook for the AI growth log, run by the post-commit hook install.sh writes into
# each watched repository.
#
# The growth log credits a commit to the Bash call whose output names it
# (growth-log-on-commit.sh), and `git commit -q` prints nothing. This hook prints the line git
# would have printed, `[<branch> <sha>] <subject>`, so a quiet commit names itself in the output
# of the call that made it. Git sends a hook's output to stderr.
#
# It stays silent outside a shell Claude Code spawned (CLAUDE_CODE_CHILD_SESSION), so a commit
# from your own terminal prints nothing extra, and while a rebase replays commits: replaying is
# not the rebasing agent's work, and crediting it would log someone else's commits as its own.
#
# Each commit it names also gets a row in commits.tsv: sha, date, repository, session, subject.
# growth-log-on-commit.sh notes in seen.tsv each commit a call's output named, so a commit in the
# first file and not in the second was made with stderr thrown away (`2>/dev/null`, or `2>&1`
# into a pipe that cut it) or by a call that went to the background; growth-log-coverage.sh
# counts those for the audit. Both files are only appended to.
#
# A git hook: it does not source growth-log-lib.sh, whose failure path prints JSON for Claude
# Code, and it never fails or delays a commit.

[ -n "${CLAUDE_CODE_CHILD_SESSION:-}" ] || exit 0
for state in rebase-merge rebase-apply; do
    [ -d "$(git rev-parse --git-path "$state" 2>/dev/null)" ] && exit 0
done
sha="$(git rev-parse --verify --quiet HEAD 2>/dev/null)" || exit 0
subject="$(git log -1 --format=%s "$sha" 2>/dev/null)"
printf '[%s %s] %s\n' "$(git rev-parse --abbrev-ref HEAD 2>/dev/null)" "${sha:0:9}" "$subject"

config="${GROWTH_CONFIG:-$HOME/.claude/growth-log/config}"
[ -f "$config" ] || exit 0
# shellcheck source=/dev/null
. "$config"
[ -d "${GROWTH_STATE:-}" ] || exit 0
printf '%s\t%s\t%s\t%s\t%s\n' "$sha" "$(date +%F)" "$(git rev-parse --show-toplevel 2>/dev/null)" \
    "${CLAUDE_CODE_SESSION_ID:-}" "$subject" >>"$GROWTH_STATE/commits.tsv" 2>/dev/null
exit 0
