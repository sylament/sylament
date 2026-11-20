# Sourced by every growth-log hook and script. Loads the per-user config written by install.sh.
#
# A hook never kills the agent's turn: on a setup problem it records the cause in hook-errors.log,
# shows it to the user once per cause and day, and exits 0. A script the agent runs
# (GROWTH_SCRIPT=1: flush, consent, heal) fails with exit 1 instead.
#
# Queue and index rows are tab-separated: patch-id, sha, date, repository path, agent ("main" or
# the subagent's agent_id), session, subject. A tooling row has the sha "-".

GROWTH_CONFIG="${GROWTH_CONFIG:-$HOME/.claude/growth-log/config}"

growth_fail() {
    local state="${GROWTH_STATE:-$HOME/.claude/growth-log/state}" name shown msg
    name="$(basename "$0")"
    mkdir -p "$state" 2>/dev/null
    printf '%s\t%s: %s\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$name" "$1" >>"$state/hook-errors.log" 2>/dev/null
    if [ "${GROWTH_SCRIPT:-0}" = 1 ]; then
        echo "growth-log: $1" >&2
        exit 1
    fi
    shown="$state/shown-$(date +%F)-$(printf '%s %s' "$name" "$1" | cksum | cut -d' ' -f1)"
    if [ ! -f "$shown" ] && touch "$shown" 2>/dev/null; then
        msg="$(printf 'AI growth log: %s (%s; log: %s)' "$1" "$name" "$state/hook-errors.log" | sed 's/[\\"]/\\&/g')"
        printf '{"systemMessage": "%s"}\n' "$msg"
    fi
    exit 0
}

growth_field() { # payload jq-path
    printf '%s' "$1" | jq -r "$2 // empty" 2>/dev/null
}

# Hooks fire inside subagents too, and their input then carries `agent_id`. A subagent has no
# AskUserQuestion, so only the main agent is ever asked anything.
growth_in_subagent() {
    [ -n "$(growth_field "$1" .agent_id)" ]
}

growth_agent() {
    local agent
    agent="$(growth_field "$1" .agent_id)"
    printf '%s' "${agent:-main}"
}

[ -f "$GROWTH_CONFIG" ] || growth_fail "config $GROWTH_CONFIG does not exist"
command -v jq >/dev/null 2>&1 || growth_fail "jq is not in PATH"
# shellcheck source=/dev/null
. "$GROWTH_CONFIG"

: "${GROWTH_MAIN:?}" "${GROWTH_LOG:?}" "${GROWTH_STATE:?}" "${GROWTH_HOOKS:?}"
GROWTH_TOOLING_GLOBS="${GROWTH_TOOLING_GLOBS:-}"
GROWTH_LOCK_WAIT="${GROWTH_LOCK_WAIT:-20}"
# The growth-log-setup skill the install came from, for the drift guard; empty turns it off.
GROWTH_SKILL="${GROWTH_SKILL:-}"
# Days of unprocessed entries after which the human is reminded of the audit; 0 never reminds.
GROWTH_AUDIT_DAYS="${GROWTH_AUDIT_DAYS:-10}"

GROWTH_QUEUE="$GROWTH_STATE/queue.tsv"
GROWTH_INDEX="$GROWTH_LOG/GROWTH-INDEX.tsv"
# Extra repositories to watch besides GROWTH_MAIN — one absolute path per line, `#` comments allowed.
GROWTH_REPOS="$GROWTH_STATE/repos"
# Patch-ids that are never queued again: commits of a declined session and commits dropped
# without an entry, so a cherry-pick or rebase that rewrites them does not bring them back.
GROWTH_NEVER="$GROWTH_STATE/never-log.tsv"
# Every commit made in a shell Claude Code spawned (written by growth-log-post-commit.sh) and
# every commit a call's output named (written by growth-log-on-commit.sh): the difference is
# the work no agent was told to log. Both are only appended to, outside the lock.
GROWTH_JOURNAL="$GROWTH_STATE/commits.tsv"
GROWTH_SEEN="$GROWTH_STATE/seen.tsv"

mkdir -p "$GROWTH_STATE" 2>/dev/null

# --- the queue lock ---------------------------------------------------------------------------
#
# Every writer of the queue, the index and log/ holds this lock: parallel lanes commit, queue and
# flush at the same time. mkdir is atomic and needs no flock, which macOS does not ship. The
# holder's pid sits in the lock. A lock is stale when its holder no longer runs, or when it is
# older than five minutes. Taking a stale lock over needs a second lock, so only one waiter at a
# time checks and removes it: a waiter that finds the lock fresh again leaves it alone.

growth_stale() { # lock
    local holder
    holder="$(cat "$1/pid" 2>/dev/null)"
    [ -n "$(find "$1" -maxdepth 0 -mmin +5 2>/dev/null)" ] && return 0
    [ -n "$holder" ] && ! kill -0 "$holder" 2>/dev/null && return 0
    [ -z "$holder" ] && [ -n "$(find "$1" -maxdepth 0 -mmin +1 2>/dev/null)" ] && return 0
    return 1
}

growth_lock() { # [max seconds to wait]
    local lock="$GROWTH_STATE/queue.lock" takeover="$GROWTH_STATE/queue.lock.takeover"
    local waited=0 max="${1:-$GROWTH_LOCK_WAIT}"
    until mkdir "$lock" 2>/dev/null; do
        if growth_stale "$lock"; then
            [ -n "$(find "$takeover" -maxdepth 0 -mmin +1 2>/dev/null)" ] && rmdir "$takeover" 2>/dev/null
            if mkdir "$takeover" 2>/dev/null; then
                growth_stale "$lock" && rm -rf "$lock"
                rmdir "$takeover"
                continue
            fi
        fi
        [ "$waited" -ge "$max" ] && return 1
        sleep 1
        waited=$((waited + 1))
    done
    printf '%s\n' "$$" >"$lock/pid"
    trap 'rm -rf "$GROWTH_STATE/queue.lock"' EXIT
    growth_merge_pending
}

growth_unlock() {
    rm -rf "$GROWTH_STATE/queue.lock"
    trap - EXIT
}

# Rows a commit hook could not queue because the lock stayed held wait in pending files; the
# next lock holder moves them into the queue.
growth_merge_pending() {
    local f
    for f in "$GROWTH_STATE"/queue.pending.*; do
        [ -f "$f" ] || continue
        touch "$GROWTH_QUEUE" "$GROWTH_INDEX" 2>/dev/null
        # A row whose commit is queued or logged meanwhile is not added a second time.
        awk -F'\t' 'FILENAME != ARGV[3] { seen[$1] = 1; next } !($1 in seen) { seen[$1] = 1; print }' \
            "$GROWTH_QUEUE" "$GROWTH_INDEX" "$f" >>"$GROWTH_QUEUE.merge" &&
            cat "$GROWTH_QUEUE.merge" >>"$GROWTH_QUEUE" && rm -f "$f" "$GROWTH_QUEUE.merge"
    done
}

# The queue as readers see it, pending rows included.
growth_rows() {
    cat "$GROWTH_QUEUE" "$GROWTH_STATE"/queue.pending.* 2>/dev/null
}

# The last day a `processed` row of the extraction table covers, empty when there is none: the
# reading step 1 of the methodology in GROWTH-LOG.md gives.
growth_last_processed() {
    awk -F'|' '/^Statuses:/ { exit } $4 ~ /^ *`?processed`? *$/ { d = $3 }
        END { sub(/.*→/, "", d); gsub(/[[:space:]]/, "", d); print d }' "$GROWTH_LOG/GROWTH-LOG.md" 2>/dev/null
}

# --- sessions and agents ----------------------------------------------------------------------

# The human's once-per-session answer, recorded by growth-log-consent.sh: yes, no, or empty.
growth_consent() {
    cat "$GROWTH_STATE/consent-$1" 2>/dev/null
}

# The watched repositories: the main one and those in GROWTH_REPOS, one path per line.
growth_roots() {
    {
        printf '%s\n' "$GROWTH_MAIN"
        [ ! -f "$GROWTH_REPOS" ] || sed -e 's/#.*//' -e 's/[[:space:]]*$//' "$GROWTH_REPOS"
    } | awk 'NF && !seen[$0]++'
}

# Is a directory inside the checkout of a watched repository, a worktree of one included, or
# inside the log? Decided by path, not by the repository git finds there: the log is a repository
# of its own nested in a watched one, and a session that moved into it still works for that one.
growth_watches() { # directory
    local dir root tree
    dir="$(cd "$1" 2>/dev/null && pwd -P)" || return 1
    while IFS= read -r tree; do
        tree="$(cd "$tree" 2>/dev/null && pwd -P)" || continue
        case "$dir/" in "$tree"/*) return 0 ;; esac
    done < <(
        printf '%s\n' "$GROWTH_LOG"
        while IFS= read -r root; do
            git -C "$root" worktree list --porcelain 2>/dev/null | awk '/^worktree /{print substr($0, 10)}'
        done < <(growth_roots)
    )
    return 1
}

# A session's waiting commits by one agent, one "- `sha` (path) — subject" per line, and their sha.
growth_agent_items() { # session agent
    growth_rows | awk -F'\t' -v s="$1" -v a="$2" '$6 == s && $5 == a && $2 != "-" { printf "   - `%s` (%s) — %s\n", $2, $4, $7 }'
}

growth_agent_shas() { # session agent
    growth_rows | awk -F'\t' -v s="$1" -v a="$2" '$6 == s && $5 == a && $2 != "-" { printf "%s%s", sep, $2; sep = " " }'
}

# The tooling an agent edited without a commit in a session, as one line for its entry, or nothing.
growth_tooling_line() { # session agent
    local tooling
    tooling="$(growth_rows | awk -F'\t' -v s="$1" -v a="$2" '$6 == s && $5 == a && $2 == "-" { printf "%s%s", sep, substr($7, 22); sep = ", " }')"
    [ -z "$tooling" ] || printf 'Tooling this agent edited without a commit, to mention in the entry: %s\n' "$tooling"
}

# The context for the agent that made the commits: facts; the procedure lives in WRITING-RULES.md.
growth_write_context() { # session agent
    printf 'AI growth log: these commits by this agent wait for their entry:\n%s\n' "$(growth_agent_items "$1" "$2")"
    growth_tooling_line "$1" "$2"
    printf 'How to write the entry: %s, section "Writing an entry".\n' "$GROWTH_LOG/WRITING-RULES.md"
    printf 'Closing command, once the entry is written: %s --session %s --agent %s %s\n' \
        "$GROWTH_HOOKS/growth-log-flush.sh" "$1" "$2" "$(growth_agent_shas "$1" "$2")"
}

# The commits a session's subagents logged that no entry of the main agent follows in the index,
# past the first <offered> of them, which the main agent was handed before: one
# "- `sha` (path) — subject" per line.
growth_subagent_items() { # session [offered]
    awk -F'\t' -v s="$1" -v skip="${2:-0}" '
        $6 != s { next }
        $5 == "main" { n = 0; next }
        $2 == "-" { next }
        ++seen > skip + 0 { item[++n] = sprintf("   - `%s` (%s) — %s", $2, $4, $7) }
        END { for (i = 1; i <= n; i++) print item[i] }' "$GROWTH_INDEX" 2>/dev/null
}

# How many commits a session's subagents have logged.
growth_subagent_count() { # session
    awk -F'\t' -v s="$1" '$6 == s && $5 != "main" && $2 != "-" { n++ } END { print n + 0 }' "$GROWTH_INDEX" 2>/dev/null
}

# The context for the main agent's own block about its subagents' commits: only it hears the human.
growth_block_context() { # session items
    printf 'AI growth log: subagents of this session logged these commits, and no entry of the main agent follows them:\n%s\n' "$2"
    printf 'What the human corrected or decided about this work reached only the main agent.\n'
    growth_tooling_line "$1" main
    printf 'How to record it: %s, section "The main agent'"'"'s block".\n' "$GROWTH_LOG/WRITING-RULES.md"
    printf 'Closing command, once the block is written: %s --session %s --agent main\n' "$GROWTH_HOOKS/growth-log-flush.sh" "$1"
}

# The once-per-session question, for the main agent.
growth_ask_context() { # session
    cat <<EOF
AI growth log: this session has not answered its one growth-log question yet.
The question, asked with AskUserQuestion under the header "Growth log": "Log this session's
commits to the AI growth log? Each commit then gets its entry right away, written by the agent
that made it." Options: "Yes, log this session" (recommended) and "No, not this session".
The answer is recorded with $GROWTH_HOOKS/growth-log-consent.sh $1 yes (or no; no also drops
the session's waiting items). In an unattended run nobody can answer: the answer is then yes,
flagged in the run's report.
EOF
    if [ -n "$(growth_agent_shas "$1" main)" ]; then
        printf '\nAfter yes, the waiting entries are written:\n'
        growth_write_context "$1" main
    fi
}

# hookSpecificOutput.additionalContext for an event, with an optional line for the user.
growth_context() { # event context [system-message]
    jq -n --arg e "$1" --arg c "$2" --arg m "${3:-}" \
        '{hookSpecificOutput: {hookEventName: $e, additionalContext: $c}} + (if $m == "" then {} else {systemMessage: $m} end)'
}

# Removes a session's queued rows, marks their commits never to queue again and releases their
# pins; prints how many rows went. The caller holds the lock.
growth_drop_session() { # session
    local dropped
    touch "$GROWTH_QUEUE" "$GROWTH_NEVER"
    dropped="$(awk -F'\t' -v s="$1" '$6 == s' "$GROWTH_QUEUE")"
    awk -F'\t' -v s="$1" '$6 != s' "$GROWTH_QUEUE" >"$GROWTH_QUEUE.tmp" && mv "$GROWTH_QUEUE.tmp" "$GROWTH_QUEUE"
    printf '%s\n' "$dropped" | while IFS=$'\t' read -r pid sha _ path _ _ _; do
        [ -n "$sha" ] && [ "$sha" != "-" ] || continue
        printf '%s\n' "$pid" >>"$GROWTH_NEVER"
        git -C "$path" update-ref -d "refs/growth/$pid" 2>/dev/null
    done
    printf '%s' "$dropped" | grep -c . || true
}
