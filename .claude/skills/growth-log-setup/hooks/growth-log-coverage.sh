#!/usr/bin/env bash
# Counts, for a day range, how much of the work the AI growth log holds: the numbers the audit
# puts into its statistics.
#
#   growth-log-coverage.sh <from YYYY-MM-DD> <to YYYY-MM-DD>
#
# Prints tab-separated rows: a name, a count, where the count comes from. The commits no call's
# output named follow as a list, since each of them is work the log does not hold.
#
# "made under Claude Code" counts what the post-commit hook journaled, so it is zero for days
# before the hook was installed and for a repository that lacks it (install.sh --check).
set -uo pipefail

GROWTH_SCRIPT=1
# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

FROM="${1:-}" TO="${2:-}"
DAY='^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
[[ "$FROM" =~ $DAY && "$TO" =~ $DAY ]] || {
    echo "usage: growth-log-coverage.sh <from YYYY-MM-DD> <to YYYY-MM-DD>"
    exit 2
}

in_range() { # file date-column [filter] — the rows of a tab-separated file dated in the range
    [ -f "$1" ] || return 0
    awk -F'\t' -v lo="$FROM" -v hi="$TO" -v c="$2" 'substr($c, 1, 10) >= lo && substr($c, 1, 10) <= hi' "$1"
}
count() { grep -c . || true; }

touch "$GROWTH_SEEN" 2>/dev/null
# Keyed on the file, not on NR == FNR: with seen.tsv empty that test would hold for every
# journal row and report none of them.
UNSEEN="$(in_range "$GROWTH_JOURNAL" 2 | awk -F'\t' 'FILENAME == ARGV[1] { seen[$1] = 1; next } !($1 in seen)' "$GROWTH_SEEN" -)"
AUTHOR="$(git -C "$GROWTH_MAIN" config user.email 2>/dev/null)"
YOURS=0
while IFS= read -r root; do
    n="$(git -C "$root" log --branches --no-merges --author="$AUTHOR" --since="$FROM 00:00:00" \
        --until="$TO 23:59:59" --format=%H 2>/dev/null | count)"
    YOURS=$((YOURS + n))
done < <(growth_roots)

row() { printf '%s\t%s\t%s\n' "$1" "$2" "$3"; }
row "made under Claude Code" "$(in_range "$GROWTH_JOURNAL" 2 | cut -f1 | sort -u | count)" "commits.tsv, written by the post-commit hook"
row "never named by a call" "$(printf '%s' "$UNSEEN" | count)" "in commits.tsv and not in seen.tsv"
row "logged" "$(in_range "$GROWTH_INDEX" 3 | awk -F'\t' '$2 != "-"' | count)" "GROWTH-INDEX.tsv"
row "waiting for an entry" "$(growth_rows | awk -F'\t' -v lo="$FROM" -v hi="$TO" '$2 != "-" && $3 >= lo && $3 <= hi' | count)" "queue.tsv"
row "dropped at session end" "$(in_range "$GROWTH_STATE/dropped.log" 1 | awk -F'\t' '{ n += $3 + 0 } END { print n + 0 }')" "dropped.log, tooling rows included"
row "your commits on branches" "$YOURS" "git log by commit date in the watched repositories, any source"

if [ -n "$UNSEEN" ]; then
    printf '\nnever named by a call:\n'
    printf '%s\n' "$UNSEEN" | awk -F'\t' '{ printf "  %s  %s  %s  %s\n", substr($1, 1, 9), $2, $3, $5 }'
fi
