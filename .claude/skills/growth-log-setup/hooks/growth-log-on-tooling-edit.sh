#!/usr/bin/env bash
# PostToolUse(Write|Edit) hook for the AI growth log, two jobs:
#
# 1. Work on your own tooling (agent hooks, settings, skills, memory, the two growth-log skills)
#    is git-ignored or lives outside the repository, so no commit names it. Such an edit is
#    queued as a tooling row (sha "-"); it asks nothing itself and is mentioned in the entry the
#    same agent writes for its next commit of the session. An agent that never commits leaves its
#    tooling rows unlogged: they are dropped at session end. Nothing is queued in a session that
#    declined the log.
#
# 2. The drift guard. The hooks run from a copy outside every checkout and the templates from a
#    copy in the log, so an edit to one side leaves the other behind. After an edit to an
#    installed hook, to a template in the log or to the growth-log-setup skill, `install.sh
#    --check` compares the two, and a difference goes to the agent once per file and day.
set -uo pipefail

# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

PAYLOAD="$(cat)"
FILE="$(growth_field "$PAYLOAD" .tool_input.file_path)"
[ -n "$FILE" ] || exit 0
AUDIT_SKILL=""
[ -z "$GROWTH_SKILL" ] || AUDIT_SKILL="$(dirname "$GROWTH_SKILL")/growth-log-audit"

is_tooling() {
    local glob found=1
    # The queue, the markers and the log, its methodology file included, are the log's own data.
    case "$FILE" in "$GROWTH_STATE"/* | "$GROWTH_LOG"/*) return 1 ;; esac
    case "$FILE" in "$GROWTH_HOOKS"/*) return 0 ;; esac
    if [ -n "$GROWTH_SKILL" ]; then
        case "$FILE" in "$GROWTH_SKILL"/* | "$AUDIT_SKILL"/*) return 0 ;; esac
    fi
    # Globbing off while splitting: an absolute glob ($HOME/.claude/skills/*) would otherwise
    # expand to the directories that exist and never match a file below them.
    set -f
    for glob in $GROWTH_TOOLING_GLOBS; do
        # shellcheck disable=SC2254
        case "$FILE" in $glob) found=0 && break ;; esac
    done
    set +f
    return "$found"
}

# The files `install.sh --check` compares between the skill and the install.
in_flow() {
    case "$FILE" in
        "$GROWTH_HOOKS"/* | "$GROWTH_LOG/TEMPLATE.md" | "$GROWTH_LOG/WRITING-RULES.md" | "$GROWTH_LOG/GROWTH-LOG.md") return 0 ;;
    esac
    [ -n "$GROWTH_SKILL" ] && case "$FILE" in "$GROWTH_SKILL"/*) return 0 ;; esac
    return 1
}

SESSION="$(growth_field "$PAYLOAD" .session_id)"
TODAY="$(date +%F)"

if is_tooling && [ "$(growth_consent "$SESSION")" != no ]; then
    AGENT="$(growth_agent "$PAYLOAD")"
    # One row per file, day, session and agent: each agent mentions its own tooling work, once.
    KEY="tooling:$(printf '%s|%s|%s|%s' "$FILE" "$TODAY" "$SESSION" "$AGENT" | cksum | cut -d' ' -f1)"
    growth_lock || growth_fail "the queue stayed locked; the tooling edit of $FILE was not queued"
    touch "$GROWTH_QUEUE" 2>/dev/null
    if ! cut -f1 "$GROWTH_QUEUE" "$GROWTH_INDEX" 2>/dev/null | grep -qxF "$KEY"; then
        printf '%s\t-\t%s\t%s\t%s\t%s\t%s\n' \
            "$KEY" "$TODAY" "$(dirname "$FILE")" "$AGENT" "$SESSION" \
            "tooling (no commit): $(basename "$FILE")" >>"$GROWTH_QUEUE"
    fi
    growth_unlock
fi

in_flow || exit 0
[ -n "$GROWTH_SKILL" ] && [ -f "$GROWTH_SKILL/install.sh" ] || exit 0
MARKER="$GROWTH_STATE/drift-asked-$TODAY-$(printf '%s' "$FILE" | cksum | cut -d' ' -f1)"
[ -f "$MARKER" ] && exit 0
DRIFT="$(bash "$GROWTH_SKILL/install.sh" --check 2>&1)" && exit 0
touch "$MARKER"

read -r -d '' CONTEXT <<EOF || true
AI growth log: after this edit the install and the growth-log-setup skill ($GROWTH_SKILL) differ:
$DRIFT
\`install.sh --update\` copies the skill's hooks over the installed ones and writes a missing
post-commit hook, so a change made in an installed hook is carried into the skill's hooks/ first.
A template in the log is never overwritten: its change is carried into the skill's templates/ by
hand. \`install.sh --check\` reports no drift once both sides agree; a difference kept on purpose
is the human's call.
EOF
growth_context PostToolUse "$CONTEXT" "AI growth log: the install and the growth-log-setup skill differ"
