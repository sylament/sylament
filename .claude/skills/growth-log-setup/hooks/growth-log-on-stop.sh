#!/usr/bin/env bash
# Stop hook for the AI growth log, the main agent's check at the end of a turn:
#
# - a session that committed but was never asked gets its one question here;
# - a session that said yes and still has commits of the main agent without an entry has them
#   written now. A subagent's own commits are its business: growth-log-on-subagent-stop.sh;
# - with none of its own waiting, the main agent is handed the commits its subagents logged since
#   its last entry, once each, for a block of its own: what the human corrected or decided about
#   that work reached nobody else. offered-<session> holds how many were handed over.
#
# Tooling rows (sha "-") never trigger it, so a session that only edits memory or settings is
# not interrupted.
#
# A stop with nothing of that to say may carry one line for the human instead, at most once a
# week: enough days of entries wait for an audit, which nothing else would prompt. It goes out
# as a systemMessage, since additionalContext on Stop keeps the turn going.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

PAYLOAD="$(cat)"
[ "$(growth_field "$PAYLOAD" .stop_hook_active)" = true ] && exit 0
SESSION="$(growth_field "$PAYLOAD" .session_id)"
[ -n "$SESSION" ] || exit 0

find "$GROWTH_STATE" -maxdepth 1 -type f \( -name 'asked-*' -o -name 'consent-*' -o -name 'committed-*' \
    -o -name 'reminded-*' -o -name 'shown-*' -o -name 'extraction-asked-*' -o -name 'extraction-seen-*' \
    -o -name 'drift-asked-*' -o -name 'offered-*' \) -mtime +14 -delete 2>/dev/null

# Days are counted as daily files after the last processed day, so no date arithmetic is needed.
quiet() {
    local marker="$GROWTH_STATE/audit-reminded" count first
    case "$GROWTH_AUDIT_DAYS" in '' | *[!0-9]* | 0) exit 0 ;; esac
    [ -n "$(find "$marker" -mtime -7 2>/dev/null)" ] && exit 0
    read -r count first < <(ls "$GROWTH_LOG/log" 2>/dev/null |
        sed -n 's/^\([0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}\)\.md$/\1/p' | sort |
        awk -v last="$(growth_last_processed)" '$0 > last { n++; if (first == "") first = $0 } END { print n + 0, first }')
    [ "${count:-0}" -ge "$GROWTH_AUDIT_DAYS" ] || exit 0
    touch "$marker"
    jq -n --arg m "AI growth log: $count days of entries, from $first on, wait for an audit (the growth-log-audit skill)." \
        '{systemMessage: $m}'
    exit 0
}

[ -f "$GROWTH_STATE/committed-$SESSION" ] || quiet

case "$(growth_consent "$SESSION")" in
    no) quiet ;;
    yes)
        if [ -n "$(growth_agent_shas "$SESSION" main)" ]; then
            CONTEXT="$(growth_write_context "$SESSION" main)"
        else
            OFFERED="$GROWTH_STATE/offered-$SESSION"
            ITEMS="$(growth_subagent_items "$SESSION" "$(cat "$OFFERED" 2>/dev/null)")"
            [ -n "$ITEMS" ] || quiet
            growth_subagent_count "$SESSION" >"$OFFERED"
            CONTEXT="$(growth_block_context "$SESSION" "$ITEMS")"
            MESSAGE="AI growth log: the main agent's block on its subagents' commits is waiting"
        fi
        ;;
    *)
        [ -f "$GROWTH_STATE/asked-$SESSION" ] && quiet
        touch "$GROWTH_STATE/asked-$SESSION"
        CONTEXT="$(growth_ask_context "$SESSION")"
        ;;
esac
growth_context Stop "$CONTEXT" "${MESSAGE:-AI growth log: entries are waiting for this session}"
