#!/usr/bin/env bash
# PostToolUse(Read|Bash|Grep|Glob) hook: record an extraction or processing of the growth log.
#
# Extraction and processing always start by reading several daily files (or a glob over log/),
# while an ordinary daily entry touches only today's file. The hook therefore reacts to real
# access to the log, not to the wording of the task, and asks the agent to record the event in
# the extraction table of GROWTH-LOG.md — a step that otherwise depends on the human's memory.
#
# It stays quiet when:
#   - it runs inside a subagent, which has no AskUserQuestion and so cannot record the event,
#   - the extraction table already has a row dated today (the event is recorded),
#   - it already asked once in this session ("no" must not turn into nagging),
#   - only today's file was accessed (that is the daily entry, not an extraction).
#
# Threshold: DISTINCT_MIN different days other than today, or one glob over log/. Reading one
# old day for one fact does not trigger it.
#
# The marker is written only when the reminder is given, so a run that gives none never uses up
# the session's one reminder.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

GLOG="$GROWTH_LOG/GROWTH-LOG.md"
LOGDIR="$GROWTH_LOG/log"
TODAY="$(date +%F)"
DISTINCT_MIN=3

[ -f "$GLOG" ] || growth_fail "$GLOG does not exist"

PAYLOAD="$(cat)"
growth_in_subagent "$PAYLOAD" && exit 0
SESSION="$(printf '%s' "$PAYLOAD" | jq -r '.session_id // "nosession"' 2>/dev/null)"
# The whole tool input as text: `file_path` (Read), `command` (Bash), `path`/`pattern` (Grep, Glob).
BLOB="$(printf '%s' "$PAYLOAD" | jq -r '[.tool_input // {} | .. | strings] | join(" ")' 2>/dev/null)"
[ -n "$BLOB" ] || exit 0

grep -qE "^\| *$TODAY *\|" "$GLOG" && exit 0

MARKER="$GROWTH_STATE/extraction-asked-$TODAY-$SESSION"
[ -f "$MARKER" ] && exit 0

BULK=0
case "$BLOB" in
    *"$LOGDIR"*'*'*) BULK=1 ;;
esac

SEEN="$GROWTH_STATE/extraction-seen-$TODAY-$SESSION"
if [ "$BULK" -eq 0 ]; then
    printf '%s' "$BLOB" | grep -oE "$LOGDIR/[0-9]{4}-[0-9]{2}-[0-9]{2}\.md" |
        grep -v "$LOGDIR/$TODAY.md" >>"$SEEN" 2>/dev/null
    DISTINCT="$(sort -u "$SEEN" 2>/dev/null | grep -c . || true)"
    [ "${DISTINCT:-0}" -ge "$DISTINCT_MIN" ] || exit 0
fi

printf '%s\n' "$TODAY" >"$MARKER"

read -r -d '' REASON <<EOF || true
AI growth log: this session has read several daily entries, and the extraction table in $GLOG
has no row dated today yet. Reading several days is an extraction or a processing of the log,
which its methodology (that file, section "Methodology") records in that table, with a status
the human picks through AskUserQuestion: one of the statuses the methodology defines, or "do not
record". "Do not record" leaves the table as it is. GROWTH-LOG.md is committed on its own;
growth-log-flush.sh adds only log/, the index and the remap file.
EOF

growth_context PostToolUse "$REASON" "AI growth log: record the extraction in GROWTH-LOG.md"
