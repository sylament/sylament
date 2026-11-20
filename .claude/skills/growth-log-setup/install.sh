#!/usr/bin/env bash
# Installs, updates or checks a personal AI growth log. Run from the skill directory or by path;
# the hooks and templates are taken from next to this script.
#
#   install.sh [options]      first install (or re-run: idempotent, never overwrites log files)
#   install.sh --update       re-copy the hooks, re-register them and write a missing post-commit
#                             hook, using the existing config
#   install.sh --check        report drift between this skill and the installed copy; exit 1 on drift
#
# Options for the first install (defaults in brackets):
#   --main PATH               main repository [git toplevel of the current directory]
#   --log PATH                the log: a git repository of its own with no remote; inside the main
#                             repository it must be git-ignored [<main>/docs/superpowers/growth-log]
#   --repos P1,P2             further repositories whose commits are logged too []
#   --settings local|project|user
#                             where the hooks are registered: .claude/settings.local.json,
#                             .claude/settings.json (often tracked) or ~/.claude/settings.json [local]
#   --extraction-guard yes|no register growth-log-on-extraction.sh [no]
#   --tooling yes|no          queue edits of your own tooling that never get a commit [yes]
#   --tooling-globs "G1 G2"   what counts as tooling [your ~/.claude instructions, settings, skills,
#                             hooks, agents and auto-memory, and */.claude/settings.local.json]
#   --audit-days N            remind of the audit once N days of entries wait; 0 never [10]
#   --state PATH              queue and markers [~/.claude/growth-log/state]
set -euo pipefail

SKILL_DIR="$(cd "$(dirname "$0")" && pwd)"
HOME_DIR="$HOME/.claude/growth-log"
CONFIG="${GROWTH_CONFIG:-$HOME_DIR/config}"
HOOK_NAMES=(growth-log-lib.sh growth-log-on-commit.sh growth-log-on-dispatch.sh
    growth-log-on-stop.sh growth-log-on-subagent-stop.sh growth-log-on-session-end.sh growth-log-consent.sh
    growth-log-on-tooling-edit.sh growth-log-on-extraction.sh growth-log-flush.sh growth-log-heal.sh
    growth-log-post-commit.sh growth-log-coverage.sh)

die() { echo "install.sh: $*" >&2; exit 2; }

MODE=install MAIN="" LOG="" REPOS="" SETTINGS_SCOPE=local
GUARD=no TOOLING=yes AUDIT_DAYS=10 STATE="$HOME_DIR/state"
# Tooling that never gets a commit. A repository's tracked .claude/rules/ and .claude/skills/ are
# left out on purpose: their edits reach the log through the commit that carries them.
C="$HOME/.claude"
GLOBS="$C/CLAUDE.md $C/settings*.json $C/skills/* $C/hooks/* $C/agents/* $C/projects/*/memory/* */.claude/settings.local.json"

while [ $# -gt 0 ]; do
    case "$1" in
        --update) MODE=update ;;
        --check) MODE=check ;;
        --main) MAIN="$2"; shift ;;
        --log) LOG="$2"; shift ;;
        --repos) REPOS="$2"; shift ;;
        --settings) SETTINGS_SCOPE="$2"; shift ;;
        --extraction-guard) GUARD="$2"; shift ;;
        --tooling) TOOLING="$2"; shift ;;
        --tooling-globs) GLOBS="$2"; shift ;;
        --audit-days) AUDIT_DAYS="$2"; shift ;;
        --state) STATE="$2"; shift ;;
        *) die "unknown option $1" ;;
    esac
    shift
done

# The watched repositories: the main one and those listed in <state>/repos.
roots() { # main state
    {
        printf '%s\n' "$1"
        [ ! -f "$2/repos" ] || sed -e 's/#.*//' -e 's/[[:space:]]*$//' "$2/repos"
    } | awk 'NF && !seen[$0]++'
}

# --- the post-commit hook of each watched repository ---------------------------------------
#
# `git commit -q` prints nothing, and the growth log credits a commit by the line git prints, so
# each watched repository gets a post-commit hook that prints it (growth-log-post-commit.sh).

HOOK_MARK='growth-log-setup: post-commit hook'

post_commit_line() { # hooks config
    printf 'GROWTH_CONFIG="%s" "%s/growth-log-post-commit.sh"' "$2" "$1"
}

# Where a repository keeps its post-commit hook: core.hooksPath when set, else the hooks
# directory all its worktrees share.
# Fails for a path that is no git repository (a watched repository deleted since the install).
post_commit_path() { # root
    local dir common
    common="$(git -C "$1" rev-parse --git-common-dir 2>/dev/null)" || return 1
    dir="$(git -C "$1" config --type=path core.hooksPath 2>/dev/null)" || dir=""
    if [ -n "$dir" ]; then
        case "$dir" in /*) ;; *) dir="$1/$dir" ;; esac
    else
        dir="$(cd "$1" && cd "$common" && pwd)/hooks"
    fi
    printf '%s/post-commit' "$dir"
}

has_post_commit() { # root
    local hook
    hook="$(post_commit_path "$1")" || return 1
    grep -qF 'growth-log-post-commit.sh' "$hook" 2>/dev/null
}

# Writes the hook where none exists and refreshes ours. A hook of the repository's own, and a
# repository with core.hooksPath, are left alone: there the line is the human's to add.
install_post_commit() { # root hooks config
    local hook
    hook="$(post_commit_path "$1")" || {
        echo "install.sh: warning — $1 is no git repository, so it gets no post-commit hook; drop it from the repos list in the state directory." >&2
        return 0
    }
    if [ -n "$(git -C "$1" config core.hooksPath 2>/dev/null)" ]; then
        has_post_commit "$1" ||
            echo "install.sh: warning — $1 sets core.hooksPath, so no hook is written and a quiet commit there is not logged; add this line to $hook: $(post_commit_line "$2" "$3")" >&2
        return 0
    fi
    if [ -e "$hook" ] && ! grep -qF "$HOOK_MARK" "$hook"; then
        has_post_commit "$1" ||
            echo "install.sh: warning — $hook is not ours, so it is left alone and a quiet commit there is not logged; add this line to it: $(post_commit_line "$2" "$3")" >&2
        return 0
    fi
    mkdir -p "$(dirname "$hook")"
    cat >"$hook" <<EOF
#!/bin/sh
# $HOOK_MARK, written by install.sh. It names each commit in the output of the
# call that made it: \`git commit -q\` prints nothing, and the AI growth log credits a commit by
# that line.
[ -x "$2/growth-log-post-commit.sh" ] || exit 0
$(post_commit_line "$2" "$3")
EOF
    chmod +x "$hook"
}

post_commit_all() { # main state hooks config
    local root
    while IFS= read -r root; do
        install_post_commit "$root" "$3" "$4"
    done < <(roots "$1" "$2")
}

# --- drift check ---------------------------------------------------------------------------

# A section of a file between a start pattern and the setup marker. The parts outside it are
# personal by design (the extraction rows, your own writing rules) and never count as drift.
section() {
    awk -v start="$2" -v stop="$3" '
        start != "" && !on && $0 ~ start { on = 1 }
        start == "" { on = 1 }
        on { print }
        on && $0 ~ stop { exit }' "$1" 2>/dev/null
}

check() {
    # shellcheck source=/dev/null
    . "$CONFIG"
    local drift=0 name tpl root
    for name in "${HOOK_NAMES[@]}"; do
        if ! cmp -s "$SKILL_DIR/hooks/$name" "$GROWTH_HOOKS/$name"; then
            echo "drift: hook $name  (diff -u $SKILL_DIR/hooks/$name $GROWTH_HOOKS/$name)"
            drift=1
        fi
    done
    tpl="$SKILL_DIR/templates"
    if ! cmp -s "$tpl/TEMPLATE.md" "$GROWTH_LOG/TEMPLATE.md"; then
        echo "drift: TEMPLATE.md  (diff -u $tpl/TEMPLATE.md $GROWTH_LOG/TEMPLATE.md)"
        drift=1
    fi
    local marker_rules='growth-log-setup: personal rules below'
    if [ "$(section "$tpl/WRITING-RULES.md" "" "$marker_rules")" != \
        "$(section "$GROWTH_LOG/WRITING-RULES.md" "" "$marker_rules")" ]; then
        echo "drift: WRITING-RULES.md above the personal-rules marker  (diff -u $tpl/WRITING-RULES.md $GROWTH_LOG/WRITING-RULES.md)"
        drift=1
    fi
    local start='^### Methodology' marker_method='growth-log-setup: methodology end'
    if [ "$(section "$tpl/GROWTH-LOG.md" "$start" "$marker_method")" != \
        "$(section "$GROWTH_LOG/GROWTH-LOG.md" "$start" "$marker_method")" ]; then
        echo "drift: GROWTH-LOG.md methodology section  (diff -u $tpl/GROWTH-LOG.md $GROWTH_LOG/GROWTH-LOG.md)"
        drift=1
    fi
    while IFS= read -r root; do
        if ! has_post_commit "$root"; then
            echo "drift: post-commit hook missing in $root  (a quiet commit there is not logged; --update writes it, or add to $(post_commit_path "$root" || echo 'its post-commit hook'): $(post_commit_line "$GROWTH_HOOKS" "$CONFIG"))"
            drift=1
        fi
    done < <(roots "$GROWTH_MAIN" "$GROWTH_STATE")
    [ "$drift" -eq 0 ] && echo "growth-log-setup: no drift between $SKILL_DIR and the installed copy."
    return "$drift"
}

if [ "$MODE" = check ]; then
    [ -f "$CONFIG" ] || die "no config at $CONFIG — install first"
    check
    exit $?
fi

# --- prerequisites -------------------------------------------------------------------------

for bin in git jq python3; do
    command -v "$bin" >/dev/null 2>&1 || die "$bin is not in PATH"
done

copy_hooks() {
    mkdir -p "$1"
    local name
    for name in "${HOOK_NAMES[@]}"; do
        cp "$SKILL_DIR/hooks/$name" "$1/$name"
        chmod +x "$1/$name"
    done
}

# Removes every hook whose command mentions growth-log, then adds ours. Foreign hooks in the
# same matcher group survive, and a re-run never duplicates ours.
register() {
    local settings="$1" hooks="$2" tooling="$3" guard="$4"
    mkdir -p "$(dirname "$settings")"
    [ -f "$settings" ] || echo '{}' >"$settings"
    jq --arg h "$hooks" --argjson tooling "$tooling" --argjson guard "$guard" '
        def foreign: map(.hooks |= map(select((.command // "") | test("growth-log") | not)))
            | map(select((.hooks | length) > 0));
        def on_git($hook): $hook + {if: "Bash(git *)"};
        def run($script; $timeout; $status):
            {type: "command", command: ($h + "/" + $script), timeout: $timeout, statusMessage: $status};
        def ours($event; $groups): .hooks[$event] = ((.hooks[$event] // []) | foreign) + $groups;
        .hooks //= {}
        | ours("PreToolUse"; [
            {matcher: "Agent|Workflow", hooks: [run("growth-log-on-dispatch.sh"; 10; "Checking the AI growth log question")]}])
        | ours("PostToolUse"; [
            {matcher: "Bash", hooks: [on_git(run("growth-log-on-commit.sh"; 30; "Queueing commits for the AI growth log"))]}]
            + (if $tooling then [{matcher: "Write|Edit",
                hooks: [run("growth-log-on-tooling-edit.sh"; 30; "Queueing tooling work for the AI growth log")]}] else [] end)
            + (if $guard then [{matcher: "Read|Bash|Grep|Glob",
                hooks: [run("growth-log-on-extraction.sh"; 10; "Checking AI growth log extraction state")]}] else [] end))
        | ours("PostToolUseFailure"; [
            {matcher: "Bash", hooks: [on_git(run("growth-log-on-commit.sh"; 30; "Queueing commits for the AI growth log"))]}])
        | ours("Stop"; [{hooks: [run("growth-log-on-stop.sh"; 10; "Checking the AI growth log")]}])
        | ours("SubagentStop"; [{hooks: [run("growth-log-on-subagent-stop.sh"; 10; "Checking the AI growth log")]}])
        | ours("SessionEnd"; [{hooks: [run("growth-log-on-session-end.sh"; 10; "Closing the AI growth log queue")]}])
        ' "$settings" >"$settings.tmp"
    mv "$settings.tmp" "$settings"
}

settings_path() {
    case "$1" in
        user) echo "$HOME/.claude/settings.json" ;;
        project) echo "$2/.claude/settings.json" ;;
        *) echo "$2/.claude/settings.local.json" ;;
    esac
}

# Every watched repository gets the registration: a session started in ../demo needs it too.
settings_files() { # scope main state
    if [ "$1" = user ]; then
        settings_path user "$2"
        return
    fi
    roots "$2" "$3" | while IFS= read -r root; do settings_path "$1" "$root"; done
}

register_all() { # scope main state hooks tooling guard
    local settings root
    while IFS= read -r settings; do
        register "$settings" "$4" "$5" "$6"
        root="$(dirname "$(dirname "$settings")")"
        # The registration names paths under your home directory; a tracked settings file would publish them.
        if [ "$1" != user ] && ! git -C "$root" check-ignore -q "$settings"; then
            echo "install.sh: warning — $settings is not git-ignored; the hook paths in it would be committed." >&2
        fi
    done < <(settings_files "$1" "$2" "$3")
}

if [ "$MODE" = update ]; then
    [ -f "$CONFIG" ] || die "no config at $CONFIG — install first"
    # shellcheck source=/dev/null
    . "$CONFIG"
    copy_hooks "$GROWTH_HOOKS"
    register_all "$GROWTH_SETTINGS_SCOPE" "$GROWTH_MAIN" "$GROWTH_STATE" "$GROWTH_HOOKS" \
        "$GROWTH_REGISTER_TOOLING" "$GROWTH_REGISTER_GUARD"
    post_commit_all "$GROWTH_MAIN" "$GROWTH_STATE" "$GROWTH_HOOKS" "$CONFIG"
    echo "growth-log-setup: hooks updated in $GROWTH_HOOKS and re-registered; running sessions pick them up at once."
    exit 0
fi

# --- first install -------------------------------------------------------------------------

[ -n "$MAIN" ] || MAIN="$(git rev-parse --show-toplevel 2>/dev/null)" || die "not in a git repository; pass --main"
MAIN="$(cd "$MAIN" && pwd)"
[ -n "$LOG" ] || LOG="$MAIN/docs/superpowers/growth-log"
mkdir -p "$(dirname "$LOG")"
LOG="$(cd "$(dirname "$LOG")" && pwd -P)/$(basename "$LOG")"
# Inside the main repository the log's entries would otherwise show up as untracked files, one
# `git add -A` away from being committed and published with the code.
case "$LOG" in
    "$(cd "$MAIN" && pwd -P)"/*)
        git -C "$MAIN" check-ignore -q "$LOG" || die "$LOG is inside $MAIN but not git-ignored there; ignore it or pass another --log" ;;
esac
case "$SETTINGS_SCOPE" in local | project | user) ;; *) die "--settings must be local, project or user" ;; esac
case "$AUDIT_DAYS" in '' | *[!0-9]*) die "--audit-days must be a number of days; 0 never reminds" ;; esac
[ -n "$(git -C "$MAIN" config user.email)" ] || die "git config user.email is empty in $MAIN — the hook filters your commits by it"
HOOKS="$HOME_DIR/hooks"

mkdir -p "$STATE"
if [ -n "$REPOS" ]; then
    # Absolute, because the hooks run git from whatever directory a session is in.
    {
        echo "# Repositories watched by the growth log besides $MAIN — one path per line."
        printf '%s\n' "$REPOS" | tr ',' '\n' | sed -e 's/^[[:space:]]*//' -e '/^$/d' | while IFS= read -r r; do
            git -C "$r" rev-parse --show-toplevel 2>/dev/null || die "--repos: $r is not a git repository"
        done
    } >"$STATE/repos.tmp" || die "--repos lists a path that is not a git repository"
    mv "$STATE/repos.tmp" "$STATE/repos"
fi

if [ -d "$LOG" ]; then
    # The log's own repository, not the main one it sits in: inside an ignored directory of the
    # main checkout `rev-parse --is-inside-work-tree` would answer for the main repository.
    [ "$(git -C "$LOG" rev-parse --show-toplevel 2>/dev/null)" = "$LOG" ] ||
        die "$LOG exists and is not a git repository of its own"
    CREATED=0
else
    # No remote is ever added, so the log cannot be pushed by accident.
    git init -q -b main "$LOG"
    CREATED=1
fi

mkdir -p "$LOG/log" "$STATE"
for f in TEMPLATE.md WRITING-RULES.md GROWTH-LOG.md PROFILE.md SKILL-DEBT.md; do
    [ -e "$LOG/$f" ] || cp "$SKILL_DIR/templates/$f" "$LOG/$f"
done
[ -e "$LOG/GROWTH-INDEX.tsv" ] || : >"$LOG/GROWTH-INDEX.tsv"
[ -e "$LOG/GROWTH-REMAP.tsv" ] || : >"$LOG/GROWTH-REMAP.tsv"
[ -n "$(ls -A "$LOG/log")" ] || touch "$LOG/log/.gitkeep"

[ "$TOOLING" = yes ] && REG_TOOLING=true || REG_TOOLING=false
[ "$TOOLING" = yes ] || GLOBS=""
[ "$GUARD" = yes ] && REG_GUARD=true || REG_GUARD=false

mkdir -p "$(dirname "$CONFIG")"
cat >"$CONFIG" <<CONF
# Written by growth-log-setup/install.sh. Re-run the installer rather than editing by hand.
GROWTH_MAIN="$MAIN"
GROWTH_LOG="$LOG"
GROWTH_STATE="$STATE"
GROWTH_HOOKS="$HOOKS"
GROWTH_TOOLING_GLOBS="$GLOBS"
GROWTH_AUDIT_DAYS=$AUDIT_DAYS
GROWTH_SKILL="$SKILL_DIR"
GROWTH_SETTINGS_SCOPE="$SETTINGS_SCOPE"
GROWTH_REGISTER_TOOLING=$REG_TOOLING
GROWTH_REGISTER_GUARD=$REG_GUARD
CONF

copy_hooks "$HOOKS"
register_all "$SETTINGS_SCOPE" "$MAIN" "$STATE" "$HOOKS" "$REG_TOOLING" "$REG_GUARD"
post_commit_all "$MAIN" "$STATE" "$HOOKS" "$CONFIG"
SETTINGS="$(settings_files "$SETTINGS_SCOPE" "$MAIN" "$STATE" | paste -sd ' ' -)"

# The skill itself, when it sits in a checkout that neither tracks nor ignores it, is one
# `git add -A` away from being committed with the code.
SKILL_PREFIX="$(git -C "$SKILL_DIR" rev-parse --show-prefix 2>/dev/null)" || SKILL_PREFIX=""
if [ -n "$SKILL_PREFIX" ] && [ -z "$(git -C "$SKILL_DIR" ls-files -- . | head -1)" ] &&
    ! git -C "$SKILL_DIR" check-ignore -q .; then
    echo "install.sh: warning — $SKILL_DIR is neither tracked nor excluded in its repository; to keep the growth-log skills local: echo '/$(dirname "$SKILL_PREFIX")/growth-log-*/' >>$(git -C "$SKILL_DIR" rev-parse --show-toplevel)/.git/info/exclude" >&2
fi

if [ "$CREATED" -eq 1 ]; then
    git -C "$LOG" add -A
    git -C "$LOG" commit -q --no-verify -m "docs(ai): growth log baseline"
fi

cat <<DONE
growth-log-setup: installed.
  config    $CONFIG
  hooks     $HOOKS (registered in $SETTINGS)
  git hook  post-commit in each watched repository, so a quiet commit names itself
  log       $LOG (its own repository, no remote: never add one)
  state     $STATE
Claude Code reloads settings while it runs: the hooks are live now in every running session of
these repositories, no restart needed.
DONE
