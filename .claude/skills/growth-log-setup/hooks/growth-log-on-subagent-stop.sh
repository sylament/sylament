#!/usr/bin/env bash
# SubagentStop hook for the AI growth log: a subagent that is about to finish with commits of its
# own still without an entry is told so once, as feedback that keeps it running. Nobody else
# holds its context. What a subagent leaves anyway is dropped at session end.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

PAYLOAD="$(cat)"
[ "$(growth_field "$PAYLOAD" .stop_hook_active)" = true ] && exit 0
SESSION="$(growth_field "$PAYLOAD" .session_id)"
AGENT="$(growth_field "$PAYLOAD" .agent_id)"
[ -n "$SESSION" ] && [ -n "$AGENT" ] || exit 0
[ "$(growth_consent "$SESSION")" = no ] && exit 0
[ -n "$(growth_agent_shas "$SESSION" "$AGENT")" ] || exit 0

REMINDED="$GROWTH_STATE/reminded-$(printf '%s' "$AGENT" | tr -cd 'A-Za-z0-9_-')"
[ -f "$REMINDED" ] && exit 0
touch "$REMINDED"
growth_context SubagentStop "$(growth_write_context "$SESSION" "$AGENT")"
