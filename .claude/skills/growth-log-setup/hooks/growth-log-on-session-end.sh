#!/usr/bin/env bash
# SessionEnd hook for the AI growth log: drops what the ending session leaves without an entry
# (a question nobody answered, an agent that never wrote, /clear, which starts a new session) and
# notes the count in dropped.log. An entry comes from the agent that did the work; nothing is
# kept for a later session, which would have only the transcript.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

PAYLOAD="$(cat)"
SESSION="$(growth_field "$PAYLOAD" .session_id)"
[ -n "$SESSION" ] || exit 0
[ -s "$GROWTH_QUEUE" ] || exit 0
awk -F'\t' -v s="$SESSION" '$6 == s { found = 1; exit } END { exit !found }' "$GROWTH_QUEUE" || exit 0

growth_lock 5 || growth_fail "the queue stayed locked at the end of session $SESSION; its rows stay"
COUNT="$(growth_drop_session "$SESSION")"
growth_unlock
printf '%s\t%s\t%s row(s) dropped at session end (%s)\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$SESSION" \
    "$COUNT" "$(growth_field "$PAYLOAD" .reason)" >>"$GROWTH_STATE/dropped.log"
exit 0
