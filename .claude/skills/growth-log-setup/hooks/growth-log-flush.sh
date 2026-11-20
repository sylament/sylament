#!/usr/bin/env bash
# Closes a write to the AI growth log: checks that every closed sha is in log/, moves those rows
# into GROWTH-INDEX.tsv and commits in the log.
#
#   growth-log-flush.sh --session <id> --agent <agent> [<sha>...]
#       closes these commits of the session and the agent's tooling rows: what an agent runs
#       after writing its entry. A sha may be given at any length of 7 or more. With no sha it
#       commits a block no commit waits for (the audit's statistics) and leaves every other
#       agent's rows waiting.
#   growth-log-flush.sh
#       closes the whole queue, or commits a change in log/ when nothing is queued (by hand).
#
# Parallel lanes flush at the same time, so the work runs under the queue lock and closes only
# the rows it was given. A row with a sha passes only when the sha is in log/; tooling rows have
# none, so a batch of only those needs a change in log/ instead.
set -uo pipefail

GROWTH_SCRIPT=1
# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

usage() {
    echo "usage: growth-log-flush.sh [--session <id> --agent <agent> [<sha>...]]"
    exit 2
}

LOGDIR="$GROWTH_LOG/log"
SESSION="" AGENT=""
while [ $# -gt 0 ]; do
    case "$1" in
        --session) SESSION="${2:-}"; shift ;;
        --agent) AGENT="${2:-}"; shift ;;
        -*) usage ;;
        *) break ;;
    esac
    shift
done
[ -z "$SESSION" ] || [ -n "$AGENT" ] || usage
ARGS=" $* "
for a in "$@"; do
    case "$a" in *[!0-9a-f]* | "") usage ;; esac
    [ "${#a}" -ge 7 ] || usage
done

[ -d "$LOGDIR" ] || growth_fail "$LOGDIR does not exist — write the entry first"
for f in "$GROWTH_INDEX" "$GROWTH_STATE"; do
    [ -w "$f" ] || growth_fail "$f is not writable — run this script outside the sandbox; nothing changed"
done

growth_lock 60 || growth_fail "the queue stayed locked for a minute; nothing changed — run this again"
touch "$GROWTH_QUEUE"

# Moving the queue aside is atomic, so an item appended meanwhile by a writer that does not hold
# the lock lands in a fresh queue instead of being emptied unseen.
ALL="$GROWTH_QUEUE.flushing.$$"
BATCH="$ALL.batch"
HITS="$ALL.hits"
mv "$GROWTH_QUEUE" "$ALL" || growth_fail "moving $GROWTH_QUEUE aside failed; nothing changed"
: >"$BATCH"
: >"$HITS"
if [ -n "$SESSION" ]; then
    # A sha argument matches a row when either is a prefix of the other.
    awk -F'\t' -v s="$SESSION" -v a="$AGENT" -v args="$ARGS" -v batch="$BATCH" -v hits="$HITS" -v rest="$GROWTH_QUEUE" '
        BEGIN { n = split(args, want, " ") }
        {
            take = 0
            if ($6 == s && $2 != "-")
                for (i = 1; i <= n; i++)
                    if (index($2, want[i]) == 1 || index(want[i], $2) == 1) { take = 1; print want[i] > hits }
            if ($6 == s && $5 == a && $2 == "-") take = 1
            if (take) print > batch; else print >> rest
        }' "$ALL"
else
    cp "$ALL" "$BATCH"
fi
rm -f "$ALL"

restore_batch() {
    cat "$BATCH" >>"$GROWTH_QUEUE" && rm -f "$BATCH" "$HITS" ||
        echo "growth-log: putting the items back failed — they are in $BATCH, append it to $GROWTH_QUEUE by hand."
}

in_index() { # sha argument
    awk -F'\t' -v a="$1" 'index($2, a) == 1 || index(a, $2) == 1 { found = 1 } END { exit !found }' "$GROWTH_INDEX"
}

UNKNOWN="" LOGGED=""
for a in "$@"; do
    grep -qxF "$a" "$HITS" && continue
    if in_index "$a"; then LOGGED="$LOGGED $a"; else UNKNOWN="$UNKNOWN $a"; fi
done
if [ -n "$UNKNOWN" ]; then
    restore_batch
    echo "growth-log: no waiting commit of session $SESSION matches:$UNKNOWN — nothing changed."
    exit 1
fi
[ -n "$LOGGED" ] && echo "growth-log: already logged:$LOGGED"

COMMITS="$(awk -F'\t' '$2 != "-"' "$BATCH" | grep -c .)"
LOG_CHANGED="$(git -C "$GROWTH_LOG" status --porcelain -- log/ | grep -c .)"
if [ ! -s "$BATCH" ] && [ "$LOG_CHANGED" -eq 0 ]; then
    rm -f "$BATCH" "$HITS"
    echo "growth-log: nothing waiting and no change in log/, nothing to close."
    exit 0
fi
if [ "$COMMITS" -eq 0 ] && [ "$LOG_CHANGED" -eq 0 ]; then
    restore_batch
    echo "growth-log: no change in log/ — the entry is missing, committing nothing."
    exit 1
fi

# The search covers all of log/, not only today's file; seven characters identify a commit.
MISSING=""
while IFS=$'\t' read -r _ sha _ _ _ _ _; do
    [ -n "$sha" ] && [ "$sha" != "-" ] || continue
    grep -rqF "${sha:0:7}" "$LOGDIR" || MISSING="$MISSING $sha"
done <"$BATCH"
if [ -n "$MISSING" ]; then
    restore_batch
    echo "growth-log: these sha are missing from the log:$MISSING"
    echo "growth-log: add them to the entry and run this script again."
    exit 1
fi

COUNT="$(grep -c . "$BATCH")"
cat "$BATCH" >>"$GROWTH_INDEX" || {
    restore_batch
    echo "growth-log: appending to $GROWTH_INDEX failed; the items are back in the queue."
    exit 1
}
rm -f "$BATCH" "$HITS"

# After the entry check: heal moves index rows to their current sha and pins them; it never
# touches the daily files.
GROWTH_LOCKED=1 "$(dirname "$0")/growth-log-heal.sh" >/dev/null || {
    echo "growth-log: heal failed — nothing committed; run growth-log-heal.sh, then commit log/ and the index."
    exit 1
}

touch "$GROWTH_LOG/GROWTH-REMAP.tsv"
git -C "$GROWTH_LOG" add log GROWTH-INDEX.tsv GROWTH-REMAP.tsv || exit 1
git -C "$GROWTH_LOG" commit -q --no-verify -m "docs(ai): log $(date +%F)" || exit 1
growth_unlock

echo "growth-log: logged $COUNT item(s)."
