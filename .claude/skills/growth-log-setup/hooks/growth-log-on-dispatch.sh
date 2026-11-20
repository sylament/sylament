#!/usr/bin/env bash
# PreToolUse(Agent|Workflow) hook for the AI growth log: holds back the main agent's dispatches
# while the session has not answered the growth-log question, so the question comes before any
# subagent can commit. A subagent that commits writes its own entry without asking; asking first
# makes "no" mean that nothing of the session is logged. Every dispatch waits, not only the
# first: several dispatched in one message would otherwise start before the answer.
#
# Agent types that only read (Explore, Plan, web-fetch, claude-code-guide, statusline-setup) pass
# without the question. Recording the answer, yes or no, lets every dispatch pass.
#
# A session working outside every watched checkout and outside the log passes too: registered for
# every project (--settings user), the gate would otherwise question sessions that have nothing
# to log. A commit such a session still makes in a watched repository brings the question then.
# A working directory that is missing or unknown is held back like one inside.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

PAYLOAD="$(cat)"
growth_in_subagent "$PAYLOAD" && exit 0
SESSION="$(growth_field "$PAYLOAD" .session_id)"
[ -n "$SESSION" ] || exit 0
[ -n "$(growth_consent "$SESSION")" ] && exit 0
CWD="$(growth_field "$PAYLOAD" .cwd)"
[ -d "$CWD" ] && ! growth_watches "$CWD" && exit 0

if [ "$(growth_field "$PAYLOAD" .tool_name)" = Agent ]; then
    case "$(growth_field "$PAYLOAD" .tool_input.subagent_type)" in
        Explore | Plan | web-fetch | claude-code-guide | statusline-setup) exit 0 ;;
    esac
fi

touch "$GROWTH_STATE/asked-$SESSION"
REASON="$(growth_ask_context "$SESSION")
This dispatch was held back for the question, as is every dispatch until the answer is recorded;
then the same dispatches pass."
jq -n --arg r "$REASON" \
    '{hookSpecificOutput: {hookEventName: "PreToolUse", permissionDecision: "deny", permissionDecisionReason: $r}}'
