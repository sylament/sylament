#!/usr/bin/env bash
# Records the human's once-per-session answer to the growth-log question.
#
#   growth-log-consent.sh <session> yes|no
#
# "no" also drops the session's waiting items and marks their commits never to be queued again.
# The same call clears the rows of a session that ended without answering.
set -uo pipefail

GROWTH_SCRIPT=1
# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

SESSION="${1:-}"
ANSWER="${2:-}"
case "$ANSWER" in yes | no) ;; *) SESSION="" ;; esac
[ -n "$SESSION" ] || {
    echo "usage: growth-log-consent.sh <session> yes|no"
    exit 2
}

printf '%s\n' "$ANSWER" >"$GROWTH_STATE/consent-$SESSION" ||
    growth_fail "$GROWTH_STATE is not writable — run this outside the sandbox"
if [ "$ANSWER" = yes ]; then
    echo "growth-log: this session is logged."
    exit 0
fi

growth_lock || growth_fail "the queue stayed locked; run this again"
COUNT="$(growth_drop_session "$SESSION")"
growth_unlock
echo "growth-log: this session is not logged; $COUNT waiting item(s) dropped."
