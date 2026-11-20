#!/usr/bin/env bash
# PostToolUse(Bash) and PostToolUseFailure(Bash) hook for the AI growth log: queues the commits
# this call made and tells the agent that made them to write their entry while it still holds
# the context. A failing call counts too: `git commit … && composer test` fails after committing.
#
# A commit belongs to the call whose output names it: `git commit`, `--amend`, `cherry-pick` and
# `revert` print `[<branch> <sha>] <subject>`, and under `git commit -q` the post-commit hook of
# the watched repository prints the same line (growth-log-post-commit.sh). So the output
# identifies the commit and the hook input identifies the agent. A commit no output names (its
# stderr thrown away, a call that went to the background) is not logged; each commit named here
# is noted in seen.tsv, so the audit can count the ones that never were. Only commits by
# user.email in a watched repository count.
#
# Commit identity is `git patch-id --stable`, which survives amend, rebase and cherry-pick.
# The human is asked once per session, by the main agent: here at its first commit, or earlier
# when it dispatches a subagent (growth-log-on-dispatch.sh). A subagent never asks.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

PAYLOAD="$(cat)"
OUTPUT="$(printf '%s' "$PAYLOAD" | jq -r '[.tool_response.stdout?, .tool_response.stderr?, .error?]
    | map(select(type == "string")) | join("\n")' 2>/dev/null)"
SHAS="$(printf '%s\n' "$OUTPUT" | grep -oE '\[[^]]* [0-9a-f]{7,40}\]' | sed -E 's/.* ([0-9a-f]+)\]$/\1/' | sort -u)"
[ -n "$SHAS" ] || exit 0

command -v git >/dev/null 2>&1 || growth_fail "git is not in PATH"
[ -d "$GROWTH_LOG" ] || growth_fail "log repository $GROWTH_LOG does not exist"
AUTHOR="$(git -C "$GROWTH_MAIN" config user.email 2>/dev/null)"
[ -n "$AUTHOR" ] || growth_fail "git config user.email is empty"

EVENT="$(growth_field "$PAYLOAD" .hook_event_name)"
EVENT="${EVENT:-PostToolUse}"
SESSION="$(growth_field "$PAYLOAD" .session_id)"
AGENT="$(growth_agent "$PAYLOAD")"
TODAY="$(date +%F)"
DECLINED=0
[ -n "$SESSION" ] && [ "$(growth_consent "$SESSION")" = no ] && DECLINED=1

listed() { # file key
    [ -n "$2" ] && cut -f1 "$1" 2>/dev/null | grep -qxF "$2"
}

patch_id() {
    git -C "$1" show "$2" 2>/dev/null | git patch-id --stable 2>/dev/null | cut -d' ' -f1
}

# The watched repository holding a commit, and the worktree whose HEAD it is, if any.
locate() { # sha → "root<TAB>full sha<TAB>worktree"
    local root full w
    while IFS= read -r root; do
        full="$(git -C "$root" rev-parse --verify --quiet "$1^{commit}" 2>/dev/null)" || continue
        w="$(git -C "$root" worktree list --porcelain 2>/dev/null | awk '/^worktree /{print substr($0, 10)}' |
            while IFS= read -r w; do
                [ "$(git -C "$w" rev-parse HEAD 2>/dev/null)" = "$full" ] && { printf '%s' "$w"; break; }
            done)"
        printf '%s\t%s\t%s\n' "$root" "$full" "${w:--}"
        return 0
    done < <(growth_roots)
    return 1
}

# An amend made in this call: the worktree's reflog says so, and HEAD@{1} is the commit it
# replaced. A row of the replaced commit is dropped from the queue, or, when it is already
# logged, its index row moves to the new commit and GROWTH-REMAP.tsv records old -> new, instead
# of logging the work twice. The daily files are not touched: other agents append to them.
amended() { # worktree full-sha → old full sha
    [ "$1" != - ] || return 1
    case "$(git -C "$1" reflog -1 --format=%gs HEAD 2>/dev/null)" in "commit (amend):"*) ;; *) return 1 ;; esac
    git -C "$1" rev-parse --verify --quiet 'HEAD@{1}' 2>/dev/null
}

# A commit a rebase step made. `git rebase --continue` after a conflict prints the same
# `[detached HEAD <sha>]` line a commit does, without the post-commit hook, and replaying is not
# the rebasing agent's work. The reflog of the worktree the rebase ran in says so: the oldest
# entry naming the commit, the one that made it, is a rebase step. Start, finish and abort only
# move HEAD to a commit that exists.
replayed() { # root full-sha
    local w
    while IFS= read -r w; do
        case "$(git -C "$w" reflog -n 200 --format='%H %gs' 2>/dev/null |
            awk -v f="$2" '$1 == f { sub(/^[^ ]+ /, ""); made = $0 } END { print made }')" in
            "rebase (start)"* | "rebase (finish)"* | "rebase (abort)"*) ;;
            rebase*) return 0 ;;
        esac
    done < <(git -C "$1" worktree list --porcelain 2>/dev/null | awk '/^worktree /{print substr($0, 10)}')
    return 1
}

ROWS=""
REPOINT=""
while IFS= read -r short; do
    [ -n "$short" ] || continue
    found="$(locate "$short")" || continue
    IFS=$'\t' read -r root full worktree <<<"$found"
    printf '%s\t%s\n' "$full" "$TODAY" >>"$GROWTH_SEEN" 2>/dev/null
    replayed "$root" "$full" && continue
    [ "$(git -C "$root" log -1 --format=%ae "$full" 2>/dev/null)" = "$AUTHOR" ] || continue
    [ -z "$(git -C "$root" rev-list --merges -n 1 "$full^!" 2>/dev/null)" ] || continue
    pid="$(patch_id "$root" "$full")"
    [ -n "$pid" ] || continue
    if [ "$DECLINED" -eq 1 ]; then
        listed "$GROWTH_NEVER" "$pid" || printf '%s\n' "$pid" >>"$GROWTH_NEVER"
        continue
    fi
    listed "$GROWTH_NEVER" "$pid" && continue
    old="$(amended "$worktree" "$full")" || old=""
    [ -n "$old" ] && REPOINT="$REPOINT$root"$'\t'"$old"$'\t'"$full"$'\t'"$pid"$'\n'
    subject="$(git -C "$root" log -1 --format=%s "$full" 2>/dev/null)"
    ROWS="$ROWS$pid"$'\t'"${full:0:9}"$'\t'"$TODAY"$'\t'"$root"$'\t'"$AGENT"$'\t'"$SESSION"$'\t'"$subject"$'\n'
done <<<"$SHAS"
[ -n "$ROWS" ] || exit 0

# Within the lock: queue, refresh, re-point. Without it (the lock stayed held), the rows wait in a
# pending file for the next lock holder and the agent still gets its instruction.
if growth_lock; then
    touch "$GROWTH_QUEUE" "$GROWTH_INDEX"
    LOGGED_NOW=""
    while IFS=$'\t' read -r root old full pid; do
        [ -n "$root" ] || continue
        oldpid="$(awk -F'\t' -v sha="${old:0:9}" '$2 == sha { print $1; exit }' "$GROWTH_QUEUE")"
        if [ -n "$oldpid" ]; then
            awk -F'\t' -v p="$oldpid" '$1 != p' "$GROWTH_QUEUE" >"$GROWTH_QUEUE.tmp" && mv "$GROWTH_QUEUE.tmp" "$GROWTH_QUEUE"
            [ "$oldpid" = "$pid" ] || git -C "$root" update-ref -d "refs/growth/$oldpid" 2>/dev/null
        elif awk -F'\t' -v sha="${old:0:9}" '$2 == sha { f = 1 } END { exit !f }' "$GROWTH_INDEX"; then
            oldpid="$(awk -F'\t' -v sha="${old:0:9}" '$2 == sha { print $1; exit }' "$GROWTH_INDEX")"
            awk -F'\t' -v OFS='\t' -v sha="${old:0:9}" -v new="${full:0:9}" -v pid="$pid" \
                '$2 == sha { $1 = pid; $2 = new } { print }' "$GROWTH_INDEX" >"$GROWTH_INDEX.tmp" && mv "$GROWTH_INDEX.tmp" "$GROWTH_INDEX"
            printf '%s\t%s\t%s\t%s\n' "${old:0:9}" "${full:0:9}" "$TODAY" \
                "$(git -C "$root" log -1 --format=%s "$full" 2>/dev/null)" >>"$GROWTH_LOG/GROWTH-REMAP.tsv"
            [ "$oldpid" = "$pid" ] || git -C "$root" update-ref -d "refs/growth/$oldpid" 2>/dev/null
            git -C "$root" update-ref "refs/growth/$pid" "$full" 2>/dev/null
            LOGGED_NOW="$LOGGED_NOW$pid"$'\n'
        fi
    done <<<"$REPOINT"
    if [ -n "$LOGGED_NOW" ]; then
        git -C "$GROWTH_LOG" add GROWTH-INDEX.tsv GROWTH-REMAP.tsv >/dev/null 2>&1 &&
            git -C "$GROWTH_LOG" commit -q --no-verify -m "docs(ai): amended commit $(date +%F)" >/dev/null 2>&1
    fi

    ADDED=0
    while IFS=$'\t' read -r pid sha date root agent session subject; do
        [ -n "$pid" ] || continue
        printf '%s\n' "$LOGGED_NOW" | grep -qxF "$pid" && continue
        listed "$GROWTH_INDEX" "$pid" && continue
        # Pinned on queueing: the sha in the log then survives a squash merge, a deleted branch
        # and gc. The ref is created in the repository the commit comes from.
        git -C "$root" update-ref "refs/growth/$pid" "$(git -C "$root" rev-parse "$sha")" 2>/dev/null
        if listed "$GROWTH_QUEUE" "$pid"; then
            awk -F'\t' -v OFS='\t' -v p="$pid" -v s="$sha" -v subj="$subject" \
                '$1 == p { $2 = s; $7 = subj } { print }' "$GROWTH_QUEUE" >"$GROWTH_QUEUE.tmp" && mv "$GROWTH_QUEUE.tmp" "$GROWTH_QUEUE"
            continue
        fi
        printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\n' "$pid" "$sha" "$date" "$root" "$agent" "$session" "$subject" >>"$GROWTH_QUEUE"
        ADDED=$((ADDED + 1))
    done <<<"$ROWS"
    growth_unlock
else
    printf '%s' "$ROWS" >"$GROWTH_STATE/queue.pending.$$.$RANDOM"
    ADDED=1
fi

[ "$ADDED" -gt 0 ] && [ -n "$SESSION" ] || exit 0
touch "$GROWTH_STATE/committed-$SESSION"

case "$(growth_consent "$SESSION")" in
    no) exit 0 ;;
    yes) CONTEXT="$(growth_write_context "$SESSION" "$AGENT")" ;;
    *)
        if [ "$AGENT" != main ]; then
            CONTEXT="$(growth_write_context "$SESSION" "$AGENT")"
        elif [ -f "$GROWTH_STATE/asked-$SESSION" ]; then
            exit 0
        else
            touch "$GROWTH_STATE/asked-$SESSION"
            CONTEXT="$(growth_ask_context "$SESSION")"
        fi
        ;;
esac
growth_context "$EVENT" "$CONTEXT" "AI growth log: an entry is waiting for this agent's commit"
