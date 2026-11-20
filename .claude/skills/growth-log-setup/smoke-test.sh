#!/usr/bin/env bash
# End-to-end test of the shipped hooks and installer in a throwaway sandbox: its own HOME, its
# own repositories and worktrees, its own growth log. Nothing of the real install or repository is
# touched, so it is safe to run at any time — before installing, after --update, after editing a
# hook. Each simulated tool call runs a real git command and hands its real output to the hook in
# the JSON input Claude Code sends; agents are simulated by their agent_id.
#
#   smoke-test.sh [--keep]    --keep leaves the sandbox directory for inspection
set -uo pipefail

SKILL_DIR="$(cd "$(dirname "$0")" && pwd)"
KEEP=0
[ "${1:-}" = "--keep" ] && KEEP=1

T="$(cd "$(mktemp -d "${TMPDIR:-/tmp}/growth-smoke.XXXXXX")" && pwd -P)"
export HOME="$T/home"
# Run from an agent's shell, the test would inherit the markers Claude Code sets in the shells it
# spawns; only a simulated tool call (capture) carries them.
unset GROWTH_CONFIG GROWTH_LOCK_WAIT CLAUDECODE CLAUDE_CODE_CHILD_SESSION CLAUDE_CODE_SESSION_ID
mkdir -p "$HOME"
export GIT_CONFIG_GLOBAL="$T/gitconfig"
git config --global user.email smoke@example.invalid
git config --global user.name smoke
git config --global init.defaultBranch main

REPO="$T/repo"
DEMO="$T/demo"
OTHER="$T/other"
FOREIGN="$T/foreign"
PATHED="$T/pathed"
LANES="$T/repo-worktrees"
WT="$REPO/docs/superpowers/growth-log"
HOOKS="$HOME/.claude/growth-log/hooks"
STATE="$HOME/.claude/growth-log/state"
DAY="$WT/log/$(date +%F).md"
FAILED=0

ok() { printf 'PASS  %s\n' "$1"; }
bad() { printf 'FAIL  %s\n      %s\n' "$1" "$2"; FAILED=1; }
expect() { # name, condition-command...
    local name="$1"; shift
    if "$@" >/dev/null 2>&1; then ok "$name"; else bad "$name" "$*"; fi
}
has() { grep -qF -- "$2" "$1" 2>/dev/null; }
lacks() { ! grep -qF -- "$2" "$1" 2>/dev/null; }
empty() { [ ! -s "$1" ]; }

# --- simulated Claude Code ------------------------------------------------------------------

agent_fields() { # agent → the fields a subagent's hook input carries
    if [ "$1" = main ]; then echo '{}'; else jq -n --arg a "$1" '{agent_id: $a, agent_type: "general-purpose"}'; fi
}
capture() { # id cwd command [session] — runs the command as the Bash tool would and keeps its output
    (cd "$2" && CLAUDECODE=1 CLAUDE_CODE_CHILD_SESSION=1 CLAUDE_CODE_SESSION_ID="${4:-}" eval "$3") >"$T/so-$1" 2>"$T/se-$1"
    echo $? >"$T/rc-$1"
}
post() { # id session agent [event] — the PostToolUse (or PostToolUseFailure) hook of that call
    local e="${4:-PostToolUse}"
    jq -n --arg s "$2" --arg e "$e" --rawfile so "$T/so-$1" --rawfile se "$T/se-$1" --arg rc "$(cat "$T/rc-$1")" \
        --argjson ag "$(agent_fields "$3")" '
        {session_id: $s, hook_event_name: $e, tool_name: "Bash", tool_input: {command: "git"}}
        + (if $e == "PostToolUseFailure" then {error: ("Exit code " + $rc + "\n" + $so + $se)}
           else {tool_response: {stdout: $so, stderr: $se, interrupted: false, isImage: false}} end)
        + $ag' | "$HOOKS/growth-log-on-commit.sh" >"$T/out-$1"
}
# git_call id session agent cwd command [event] — one Bash call by an agent, its hook after it
git_call() { capture "$1" "$4" "$5" "$2"; post "$1" "$2" "$3" "${6:-}"; }
ctx() { jq -r '.hookSpecificOutput.additionalContext // empty' "$T/out-$1" 2>/dev/null >"$T/ctx-$1"; echo "$T/ctx-$1"; }
stop() { # session [stop_hook_active]
    printf '{"session_id":"%s","stop_hook_active":%s}' "$1" "${2:-false}" | "$HOOKS/growth-log-on-stop.sh"
}
subagent_stop() { # session agent
    jq -n --arg s "$1" --arg a "$2" '{session_id: $s, agent_id: $a, agent_type: "general-purpose", stop_hook_active: false}' |
        "$HOOKS/growth-log-on-subagent-stop.sh"
}
dispatch() { # session tool subagent_type [agent] [cwd]
    jq -n --arg s "$1" --arg t "$2" --arg st "$3" --argjson ag "$(agent_fields "${4:-main}")" --arg cwd "${5:-$REPO}" \
        '{session_id: $s, cwd: $cwd, tool_name: $t, tool_input: (if $st == "" then {} else {subagent_type: $st} end)} + $ag' |
        "$HOOKS/growth-log-on-dispatch.sh"
}
edit_input() { # session agent file
    jq -n --arg s "$1" --arg f "$3" --argjson ag "$(agent_fields "$2")" '{session_id: $s, tool_input: {file_path: $f}} + $ag'
}
denied() { test "$(jq -r '.hookSpecificOutput.permissionDecision // empty' "$1" 2>/dev/null)" = deny; }

# --- helpers over the sandbox ---------------------------------------------------------------

change() { echo "$2" >>"$1/a.txt"; } # worktree line
commit_cmd() { printf "git commit -am '%s'" "$1"; }
queued() { cat "$STATE/queue.tsv" "$STATE"/queue.pending.* 2>/dev/null; } # parked rows included
rows() { queued | awk -F'\t' -v s="$1" -v a="${2:-}" '$6 == s && (a == "" || $5 == a)' | grep -c . || true; }
queue_rows() { grep -c . "$STATE/queue.tsv" 2>/dev/null || true; }
shas() { queued | awk -F'\t' -v s="$1" -v a="$2" '$6 == s && $5 == a && $2 != "-" { print $2 }'; }
entry() { # session agent — appends a block naming the agent's waiting commits, as an agent does
    [ -f "$DAY" ] || printf '# %s\n' "$(date +%F)" >"$DAY"
    { printf '\n## %s %s\n\n' "$1" "$2"; shas "$1" "$2" | sed 's/^/- `/; s/$/`/'; } >>"$DAY"
}
flush() { # session agent [short8]
    local list
    list="$(shas "$1" "$2")"
    [ "${3:-}" = short8 ] && list="$(printf '%s\n' "$list" | cut -c1-8)"
    # shellcheck disable=SC2086
    "$HOOKS/growth-log-flush.sh" --session "$1" --agent "$2" $list
}
head9() { git -C "$1" rev-parse --short=9 HEAD; }
pins() { git -C "$1" for-each-ref refs/growth | grep -c . || true; }
growth_hooks() { jq '[.hooks[][]?.hooks[]? | select(.command | test("growth-log"))] | length' "$1"; }
journal() { cut -f1 "$STATE/commits.tsv" 2>/dev/null | sort -u | grep -c . || true; } # commits, not rows
coverage() { "$HOOKS/growth-log-coverage.sh" "$(date +%F)" "$(date +%F)" 2>&1; }
post_commit_line() { printf 'GROWTH_CONFIG="%s" "%s/growth-log-post-commit.sh"\n' "$HOME/.claude/growth-log/config" "$HOOKS"; }

# --- install --------------------------------------------------------------------------------

for r in "$REPO" "$DEMO" "$OTHER" "$FOREIGN" "$PATHED"; do
    git init -q "$r"
    echo one >"$r/a.txt"
    mkdir -p "$r/.claude"
    printf '%s\n' .claude/settings.local.json /docs/superpowers >"$r/.gitignore"
    git -C "$r" add a.txt .gitignore
    git -C "$r" commit -q -m "chore: initial"
done
# A tracked project settings file with a foreign hook, and a local one the installer writes to.
echo '{"hooks":{"PostToolUse":[{"matcher":"Bash","hooks":[{"type":"command","command":"/bin/true"}]}]}}' \
    >"$REPO/.claude/settings.json"
cp "$REPO/.claude/settings.json" "$REPO/.claude/settings.local.json"
git -C "$REPO" add .claude/settings.json
git -C "$REPO" commit -q -m "chore: settings"
cp "$REPO/.claude/settings.json" "$T/settings.before"
# A watched repository with a post-commit hook of its own, and one that keeps its hooks elsewhere.
printf '#!/bin/sh\necho foreign hook\n' >"$FOREIGN/.git/hooks/post-commit"
chmod +x "$FOREIGN/.git/hooks/post-commit"
cp "$FOREIGN/.git/hooks/post-commit" "$T/foreign.before"
mkdir -p "$T/pathed-hooks"
git -C "$PATHED" config core.hooksPath "$T/pathed-hooks"

bash "$SKILL_DIR/install.sh" --main "$REPO" --log "$REPO/notes/growth-log" >"$T/install-tracked.out" 2>&1
expect "install refuses a log inside the repository that git does not ignore" has "$T/install-tracked.out" 'not git-ignored there'
expect "a refused install leaves no config" test ! -f "$HOME/.claude/growth-log/config"

bash "$SKILL_DIR/install.sh" --main "$REPO" --repos "$DEMO,$FOREIGN,$PATHED" --extraction-guard yes >"$T/install.out" 2>&1
expect "install exits 0" has "$T/install.out" 'installed'
expect "the log is a repository of its own with one commit" \
    test "$(git -C "$WT" rev-parse --show-toplevel)" = "$WT" -a "$(git -C "$WT" rev-list --count HEAD)" = 1
expect "the log repository has no remote" test -z "$(git -C "$WT" remote)"
expect "the main repository does not see the log" test -z "$(git -C "$REPO" status --porcelain)"
expect "templates, index and remap file are in the log" \
    test -f "$WT/TEMPLATE.md" -a -f "$WT/WRITING-RULES.md" -a -f "$WT/GROWTH-LOG.md" -a -f "$WT/GROWTH-INDEX.tsv" -a -f "$WT/GROWTH-REMAP.tsv"
S="$REPO/.claude/settings.local.json"
expect "eight growth hooks registered in settings.local.json" test "$(growth_hooks "$S")" = 8
expect "every event is registered" test "$(jq -r '[.hooks | keys[]] | sort | join(",")' "$S")" = \
    "PostToolUse,PostToolUseFailure,PreToolUse,SessionEnd,Stop,SubagentStop"
expect "the commit hook runs only on git commands" \
    test "$(jq '[.hooks[][]?.hooks[]? | select(.if == "Bash(git *)" and (.command | test("on-commit")))] | length' "$S")" = 2
expect "the watched repository gets the registration too" test "$(growth_hooks "$DEMO/.claude/settings.local.json")" = 8
expect "foreign hook kept" test "$(jq '[.hooks.PostToolUse[].hooks[] | select(.command == "/bin/true")] | length' "$S")" = 1
expect "the tracked settings.json is untouched" cmp -s "$REPO/.claude/settings.json" "$T/settings.before"
expect "no warning for ignored settings files" lacks "$T/install.out" 'not git-ignored'
expect "the install says the hooks are live without a restart" has "$T/install.out" 'no restart needed'
expect "each watched repository gets the post-commit hook" \
    test -x "$REPO/.git/hooks/post-commit" -a -x "$DEMO/.git/hooks/post-commit"
expect "a post-commit hook of the repository's own is left alone, with the line to add" \
    test -n "$(grep 'is not ours' "$T/install.out")" -a -z "$(cmp "$FOREIGN/.git/hooks/post-commit" "$T/foreign.before" 2>&1)"
expect "a repository with core.hooksPath gets no hook written, with the line to add" \
    test -n "$(grep 'core.hooksPath' "$T/install.out")" -a ! -e "$PATHED/.git/hooks/post-commit" -a ! -e "$T/pathed-hooks/post-commit"
expect "the config names the skill and the audit reminder, and no unused key" \
    test -n "$(grep '^GROWTH_SKILL=' "$HOME/.claude/growth-log/config")" -a -n "$(grep '^GROWTH_AUDIT_DAYS=10' "$HOME/.claude/growth-log/config")" \
    -a -z "$(grep GROWTH_MAX_NEW "$HOME/.claude/growth-log/config")"

cp "$REPO/.git/hooks/post-commit" "$T/shim.before"
bash "$SKILL_DIR/install.sh" --main "$REPO" --repos "$DEMO,$FOREIGN,$PATHED" --extraction-guard yes >/dev/null 2>&1
expect "re-run does not duplicate hooks" test "$(growth_hooks "$S")" = 8
expect "re-run leaves the post-commit hook as it was" cmp -s "$REPO/.git/hooks/post-commit" "$T/shim.before"

bash "$SKILL_DIR/install.sh" --check >"$T/check-hooks.out" 2>&1
expect "check names the watched repositories that still lack the post-commit line" \
    test "$(grep -c 'drift: post-commit' "$T/check-hooks.out")" = 2 -a -n "$(grep "in $FOREIGN " "$T/check-hooks.out")" -a -n "$(grep "in $PATHED " "$T/check-hooks.out")"
post_commit_line >>"$FOREIGN/.git/hooks/post-commit"
{ echo '#!/bin/sh'; post_commit_line; } >"$T/pathed-hooks/post-commit"
chmod +x "$T/pathed-hooks/post-commit"
expect "check reports no drift once the line is added by hand" bash "$SKILL_DIR/install.sh" --check

# --- the main agent: one question, then an entry per commit ---------------------------------

change "$REPO" two
git_call c1 smoke main "$REPO" "$(commit_cmd 'feat: smoke change')"
C1="$(ctx c1)"
expect "the main agent's first commit brings the question" has "$C1" 'has not answered its one growth-log question'
expect "it lists the commit" has "$C1" 'feat: smoke change'
expect "the output is additionalContext for the event" test "$(jq -r .hookSpecificOutput.hookEventName "$T/out-c1")" = PostToolUse
expect "the commit is queued under the main agent" test "$(rows smoke main)" = 1
expect "the commit is pinned in refs/growth" test "$(pins "$REPO")" = 1
expect "only the commit the call printed is claimed" lacks "$C1" 'chore: initial'

change "$REPO" terminal
git -C "$REPO" commit -q -am "chore: from the terminal"
git_call c2 smoke main "$REPO" "git status && git log -1"
expect "a commit made outside any agent's call is never claimed" test ! -s "$T/out-c2" -a "$(rows smoke)" = 1
expect "the commit hook notes each commit a call's output named" grep -q "^$(git -C "$REPO" rev-parse HEAD~1)	" "$STATE/seen.tsv"
expect "a commit from the terminal is not in the journal" test "$(journal)" = 1
change "$REPO" hidden
git_call c2q smoke main "$REPO" "git commit -q -am 'chore: output thrown away' 2>/dev/null"
expect "a commit whose output is thrown away is not claimed" test ! -s "$T/out-c2q" -a "$(rows smoke)" = 1
expect "the journal has it, with its session, for the audit to count" \
    test "$(journal)" = 2 -a -n "$(awk -F'\t' '$4 == "smoke" && $5 == "chore: output thrown away"' "$STATE/commits.tsv")"
change "$OTHER" elsewhere
git_call c2o smoke main "$OTHER" "$(commit_cmd 'feat: in an unwatched repository')"
expect "a commit in an unwatched repository is not logged and gets no pin" \
    test ! -s "$T/out-c2o" -a "$(rows smoke)" = 1 -a "$(pins "$OTHER")" = 0

change "$REPO" three
git_call c3 smoke main "$REPO" "$(commit_cmd 'feat: while the question is out')"
expect "no second question while the first is out" empty "$T/out-c3"
stop smoke >"$T/s1.out"
expect "the Stop hook does not ask a session again" empty "$T/s1.out"

"$HOOKS/growth-log-consent.sh" smoke yes >/dev/null
stop smoke true >"$T/s2.out"
expect "a stop already continued by a hook stays quiet" empty "$T/s2.out"
stop smoke >"$T/s3.out"
expect "after yes, the Stop hook brings the main agent's waiting commits" \
    test "$(jq -r .hookSpecificOutput.hookEventName "$T/s3.out")" = Stop -a -n "$(grep 'while the question is out' "$T/s3.out")"

QUEUED_BEFORE="$(queue_rows)"
flush smoke main >"$T/flush-missing.out" 2>&1
expect "flush refuses commits missing from the log" has "$T/flush-missing.out" 'missing from the log'
expect "a refused flush puts the items back in the queue" test "$(queue_rows)" = "$QUEUED_BEFORE"
expect "a refused flush leaves no batch file behind" test -z "$(find "$STATE" -name 'queue.tsv.flushing.*')"
expect "a refused flush releases the lock" test ! -d "$STATE/queue.lock"
"$HOOKS/growth-log-flush.sh" --session smoke --agent main deadbeef1 >"$T/flush-unknown.out" 2>&1
expect "flush refuses a sha that matches no waiting commit, naming it" \
    test $? -ne 0 -a -n "$(grep 'deadbeef1' "$T/flush-unknown.out")"

entry smoke main
chmod a-w "$WT/GROWTH-INDEX.tsv"
flush smoke main >"$T/flush-ro.out" 2>&1
RO_EXIT=$?
chmod u+w "$WT/GROWTH-INDEX.tsv"
expect "flush fails loudly when the index is not writable" test "$RO_EXIT" -ne 0 -a -n "$(grep 'not writable' "$T/flush-ro.out")"
expect "a failed flush leaves the queue full" test "$(queue_rows)" = "$QUEUED_BEFORE"

# Another session appending while the batch is copied into the index: a `cat` that appends one
# line to the queue right after it read the batch opens exactly that window.
mkdir -p "$T/bin"
REAL_CAT="$(command -v cat)"
cat >"$T/bin/cat" <<EOF
#!/usr/bin/env bash
"$REAL_CAT" "\$@"
case "\${1:-}" in *queue.tsv*) printf 'late:1\t-\t-\t-\tmain\tlate\tlate item\n' >>"$STATE/queue.tsv" ;; esac
EOF
chmod +x "$T/bin/cat"
SMOKE_SHA="$(shas smoke main | head -1)"
PATH="$T/bin:$PATH" flush smoke main short8 >"$T/flush.out" 2>&1
expect "flush accepts a shorter sha and commits with an entry" has "$T/flush.out" 'logged 2 item(s)'
expect "the session has nothing left waiting" test "$(rows smoke)" = 0
expect "an item appended during the flush stays in the queue" grep -q '^late:1' "$STATE/queue.tsv"
"$HOOKS/growth-log-flush.sh" --session smoke --agent main "$SMOKE_SHA" >"$T/flush-again.out" 2>&1
expect "flushing a logged sha again says so and changes nothing" has "$T/flush-again.out" 'already logged'
: >"$STATE/queue.tsv"

change "$REPO" four
git_call c4 smoke main "$REPO" "$(commit_cmd 'feat: after yes')"
C4="$(ctx c4)"
expect "after yes, a commit brings the entry instruction" has "$C4" 'feat: after yes'
expect "it asks nothing" lacks "$C4" 'AskUserQuestion'
expect "it names the flush for exactly this agent and commit" has "$C4" "--session smoke --agent main $(head9 "$REPO")"
entry smoke main
flush smoke main >/dev/null 2>&1

change "$REPO" quiet
git_call c4q smoke main "$REPO" "git commit -q -am 'feat: quiet after yes'"
expect "a quiet commit names itself through the post-commit hook and is claimed" has "$(ctx c4q)" 'feat: quiet after yes'
change "$REPO" quiet-heredoc
git_call c4h smoke main "$REPO" "git commit -q -a -F - <<'EOF'
feat: quiet from a heredoc
EOF"
expect "so is one whose message comes from a heredoc" has "$(ctx c4h)" 'feat: quiet from a heredoc'
change "$REPO" quiet-amend
git_call c4a smoke main "$REPO" "git commit -q -a --amend --no-edit"
expect "a quiet amend leaves one row for the amended commit" \
    test "$(rows smoke)" = 2 -a "$(shas smoke main | tail -1)" = "$(head9 "$REPO")"
change "$REPO" quiet-piped
git_call c4p smoke main "$REPO" "git commit -q -am 'feat: quiet into a pipe' | tail -1"
expect "a pipe after the commit does not hide it: the hook's line travels on stderr" has "$(ctx c4p)" 'feat: quiet into a pipe'
entry smoke main
flush smoke main >/dev/null 2>&1

change "$REPO" red
git_call c5 smoke main "$REPO" "$(commit_cmd 'feat: then a red suite') && false" PostToolUseFailure
expect "a commit in a failing call is claimed too" \
    test "$(jq -r .hookSpecificOutput.hookEventName "$T/out-c5")" = PostToolUseFailure -a -n "$(grep 'then a red suite' "$(ctx c5)")"
entry smoke main
flush smoke main >/dev/null 2>&1

# --- every committing dispatch waits for the session's answer ------------------------------

dispatch lanes Agent Explore >"$T/d0.out"
expect "a read-only subagent is dispatched without the question" empty "$T/d0.out"
dispatch lanes Agent general-purpose >"$T/d1.out"
dispatch lanes Agent general-purpose >"$T/d2.out"
expect "every committing dispatch is held back until the answer" \
    test -n "$(grep 'has not answered' "$T/d1.out")" -a "$(denied "$T/d1.out" && denied "$T/d2.out" && echo y)" = y
"$HOOKS/growth-log-consent.sh" lanes yes >/dev/null
dispatch lanes Agent general-purpose >"$T/d3.out"
expect "after the answer the dispatches pass" empty "$T/d3.out"
dispatch wf Workflow "" >"$T/d4.out"
expect "a Workflow is held back the same way" denied "$T/d4.out"
dispatch wf2 Agent general-purpose a9 >"$T/d5.out"
expect "a subagent's own dispatch is never held back" empty "$T/d5.out"
dispatch away Agent general-purpose main "$OTHER" >"$T/d6.out"
expect "a session working outside every watched repository is not held back" empty "$T/d6.out"
dispatch demo Agent general-purpose main "$DEMO" >"$T/d7.out"
dispatch below Agent general-purpose main "$REPO/.claude" >"$T/d8.out"
expect "one working in another watched repository, or below a root, is" \
    test "$(denied "$T/d7.out" && denied "$T/d8.out" && echo y)" = y
# The log is a repository of its own inside the watched one, and its methodology has the agent cd
# into it: a repository nested in a watched checkout still counts as inside.
dispatch inlog Agent general-purpose main "$WT" >"$T/d10.out"
expect "one that moved into the log, a repository nested in the watched one, is too" denied "$T/d10.out"

# --- parallel lanes in one session ------------------------------------------------------------

mkdir -p "$LANES"
git -C "$REPO" worktree add -q "$LANES/l1" -b l1
git -C "$REPO" worktree add -q "$LANES/l2" -b l2
dispatch inlane Agent general-purpose main "$LANES/l1" >"$T/d9.out"
expect "a session working in a lane worktree is in a watched repository too" denied "$T/d9.out"
# Two agents committing in the same worktree, their calls overlapping: each output names its own.
change "$REPO" by-a1
capture p1 "$REPO" "$(commit_cmd 'feat: a1 in the main checkout')"
change "$REPO" by-a2
capture p2 "$REPO" "$(commit_cmd 'feat: a2 in the main checkout')"
post p2 lanes a2
post p1 lanes a1
expect "two overlapping calls in one worktree each get only their own commit" \
    test -n "$(grep 'a1 in the main' "$(ctx p1)")" -a -z "$(grep 'a2 in the main' "$(ctx p1)")" \
    -a -n "$(grep 'a2 in the main' "$(ctx p2)")" -a -z "$(grep 'a1 in the main' "$(ctx p2)")"
expect "each lane's flush names its own agent" has "$(ctx p1)" '--agent a1'
change "$LANES/l1" lane-one
git_call p3 lanes a3 "$REPO" "cd ../repo-worktrees/l1
$(commit_cmd 'feat: lane one, multi-line')"
expect "a multi-line command reaching a lane by cd is seen" has "$(ctx p3)" 'lane one, multi-line'
change "$REPO" main-mention
git_call pm lanes main "$REPO" "$(commit_cmd 'feat: main naming a lane') && git -C ../repo-worktrees/l2 log -1"
expect "a command that also names a lane still credits its own commit" has "$(ctx pm)" 'main naming a lane'

subagent_stop lanes a1 >"$T/ss1.out"
expect "a subagent finishing without its entry is told, as non-error feedback" \
    test "$(jq -r .hookSpecificOutput.hookEventName "$T/ss1.out")" = SubagentStop -a -n "$(grep 'a1 in the main' "$T/ss1.out")"
subagent_stop lanes a1 >"$T/ss2.out"
expect "only once" empty "$T/ss2.out"
entry lanes a1
entry lanes a2
flush lanes a1 >"$T/f1.out" 2>&1 &
flush lanes a2 >"$T/f2.out" 2>&1 &
wait
expect "two agents flushing at once both succeed under the lock" \
    test -n "$(grep logged "$T/f1.out")" -a -n "$(grep logged "$T/f2.out")" -a "$(rows lanes a1)" = 0 -a "$(rows lanes a2)" = 0
expect "the lock is released" test ! -d "$STATE/queue.lock"
subagent_stop lanes a2 >"$T/ss3.out"
expect "a subagent with its entries written finishes freely" empty "$T/ss3.out"
for a in a3 main; do entry lanes "$a"; flush lanes "$a" >/dev/null 2>&1; done

# A commit nobody logged (made in a terminal), then replayed by an agent's rebase: the rebase is
# not the work, so the agent is neither credited with it nor is it journaled.
echo by-hand >"$LANES/l2/h.txt"
git -C "$LANES/l2" add h.txt
git -C "$LANES/l2" commit -q -m "chore: by hand on a lane"
JOURNAL_BEFORE="$(journal)"
git_call rb lanes main "$REPO" "git -C ../repo-worktrees/l2 rebase main"
expect "the rebase replayed the commit" test "$(git -C "$LANES/l2" rev-list --count main..l2)" = 1 -a "$(cat "$T/rc-rb")" = 0
expect "a rebase claims nothing and journals nothing" test ! -s "$T/out-rb" -a "$(rows lanes)" = 0 -a "$(journal)" = "$JOURNAL_BEFORE"

BEFORE_MERGE="$(queue_rows)"
git -C "$LANES/l1" rebase -q main >/dev/null 2>&1
git_call m1 lanes main "$REPO" "git merge --ff-only l1"
expect "a fast-forward merge claims nothing" test ! -s "$T/out-m1" -a "$(queue_rows)" = "$BEFORE_MERGE"

# A lane's logged commit, rebased onto a main that changed the same lines: once the conflict is
# resolved, `git rebase --continue` prints a commit line of its own, without the post-commit hook.
# Replaying is still not the rebasing agent's work.
git -C "$REPO" worktree add -q "$LANES/l3" -b l3
change "$LANES/l3" lane-side
git_call rc1 lanes a5 "$LANES/l3" "$(commit_cmd 'feat: a lane change that will conflict')"
entry lanes a5
flush lanes a5 >/dev/null 2>&1
change "$REPO" main-side
git -C "$REPO" commit -q -am "chore: main moves under the lane"
git_call rc2 lanes main "$REPO" "cd ../repo-worktrees/l3 && { git rebase main >/dev/null 2>&1; echo resolved >a.txt; git add a.txt; GIT_EDITOR=true git rebase --continue; }"
expect "the continued rebase printed a commit line and finished" \
    test -n "$(cat "$T/so-rc2" "$T/se-rc2" | grep 'detached HEAD')" -a "$(git -C "$LANES/l3" rev-list --count main..l3)" = 1
expect "a commit a conflicted rebase replays is not credited to the rebasing agent" test ! -s "$T/out-rc2" -a "$(rows lanes)" = 0

# --- a session that declines -------------------------------------------------------------------

git -C "$REPO" worktree add -q "$LANES/l4" -b l4
echo declined-work >"$LANES/l4/b.txt"
git_call x1 declined main "$LANES/l4" "git add b.txt && $(commit_cmd 'feat: not logged')"
DECLINED_SHA="$(head9 "$LANES/l4")"
PIN_DECLINED="$(awk -F'\t' '$6 == "declined" { print $1 }' "$STATE/queue.tsv")"
"$HOOKS/growth-log-consent.sh" declined no >"$T/decline.out"
expect "no drops the session's waiting items" test "$(rows declined)" = 0
expect "and releases their pins" test -z "$(git -C "$REPO" for-each-ref "refs/growth/$PIN_DECLINED")"
echo more >>"$LANES/l4/b.txt"
git_call x2 declined main "$LANES/l4" "$(commit_cmd 'feat: still not logged')"
edit_input declined main "$HOME/.claude/projects/x/memory/note.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >/dev/null
expect "a declined session is silent and queues nothing" test ! -s "$T/out-x2" -a "$(rows declined)" = 0
stop declined >"$T/s4.out"
expect "its stop stays quiet" empty "$T/s4.out"
git_call x3 lanes main "$REPO" "git cherry-pick $DECLINED_SHA"
expect "the cherry-pick printed its commit line" grep -q 'feat: not logged' "$T/so-x3"
expect "a cherry-pick of a declined commit does not bring it back" test "$(rows lanes)" = 0

# --- amend, sibling branch, heal --------------------------------------------------------------

change "$REPO" five
git_call a1c smoke main "$REPO" "$(commit_cmd 'feat: before the amend')"
BEFORE_AMEND="$(awk -F'\t' '$6 == "smoke" { print $1 }' "$STATE/queue.tsv")"
change "$REPO" six
git_call a2c smoke main "$REPO" "git commit --amend -am 'feat: before the amend (reformatted)'"
expect "an amend before the entry leaves one row, for the new commit" \
    test "$(rows smoke)" = 1 -a "$(shas smoke main)" = "$(head9 "$REPO")"
expect "the superseded commit's pin is released" test -z "$(git -C "$REPO" for-each-ref "refs/growth/$BEFORE_AMEND")"
entry smoke main
flush smoke main >/dev/null 2>&1
LOGGED="$(head9 "$REPO")"
change "$REPO" format-fix
git_call a3c smoke main "$REPO" "git commit --amend -am 'feat: before the amend (format fix)'"
AMENDED="$(head9 "$REPO")"
expect "an amend after the entry queues nothing and asks nothing" test ! -s "$T/out-a3c" -a "$(rows smoke)" = 0
expect "it moves the index row to the new commit" \
    test -n "$(awk -F'\t' -v s="$AMENDED" '$2 == s' "$WT/GROWTH-INDEX.tsv")" -a -z "$(awk -F'\t' -v s="$LOGGED" '$2 == s' "$WT/GROWTH-INDEX.tsv")"
expect "and records old -> new in the remap file" grep -q "^$LOGGED	$AMENDED	" "$WT/GROWTH-REMAP.tsv"
expect "the daily file is left as written" grep -q "$LOGGED" "$DAY"
git_call a4c smoke main "$REPO" "git checkout -q -b sibling HEAD^ && echo s >>a.txt && $(commit_cmd 'feat: on a sibling branch')"
expect "a commit on a sibling branch is queued" test "$(rows smoke)" = 1
git -C "$REPO" checkout -q -f main
entry smoke main
flush smoke main >/dev/null 2>&1

OLD="$(head9 "$REPO")"
PID="$(awk -F'\t' -v old="$OLD" '$2 == old { print $1 }' "$WT/GROWTH-INDEX.tsv")"
# Committed a minute ago: heal must find rewrites from earlier today, not only from this second.
GIT_COMMITTER_DATE="@$(($(date +%s) - 60)) +0000" git -C "$REPO" commit -q --amend -m "feat: amended after logging"
NEW="$(head9 "$REPO")"
cp "$DAY" "$T/day.before"
"$HOOKS/growth-log-heal.sh" >"$T/heal.out" 2>&1
expect "heal moves the rewritten commit's index row" test -n "$(awk -F'\t' -v s="$NEW" '$2 == s' "$WT/GROWTH-INDEX.tsv")"
expect "and records it in the remap file" grep -q "^$OLD	$NEW	" "$WT/GROWTH-REMAP.tsv"
expect "heal never rewrites a daily file" cmp -s "$DAY" "$T/day.before"
expect "the pin follows the remapped commit in the same run" test "$(git -C "$REPO" rev-parse --short=9 "refs/growth/$PID")" = "$NEW"
expect "heal run by hand releases the lock" test ! -d "$STATE/queue.lock"
git -C "$WT" add -A >/dev/null && git -C "$WT" commit -q -m "docs(ai): heal" >/dev/null

# --- tooling rows ------------------------------------------------------------------------------

# The skill directory has to exist: an unquoted absolute glob expands to it and stops matching.
mkdir -p "$HOME/.claude/skills/x"
edit_input tooling main "$HOME/.claude/skills/x/SKILL.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >/dev/null
edit_input tooling b1 "$HOME/.claude/projects/x/memory/note.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >/dev/null
for f in "$REPO/README.md" "$REPO/.claude/rules/x.md" "$DAY" "$WT/GROWTH-LOG.md"; do
    edit_input tooling main "$f" | "$HOOKS/growth-log-on-tooling-edit.sh" >/dev/null
done
expect "personal tooling queued per agent; ordinary files, tracked rules and the log ignored" \
    test "$(rows tooling main)" = 1 -a "$(rows tooling b1)" = 1
"$HOOKS/growth-log-consent.sh" tooling yes >/dev/null
change "$REPO" eight
git_call t1 tooling b1 "$REPO" "$(commit_cmd 'feat: with a memory edit')"
expect "the entry instruction mentions the agent's own tooling edits" \
    test -n "$(grep 'note.md' "$(ctx t1)")" -a -z "$(grep 'SKILL.md' "$(ctx t1)")"
entry tooling b1
flush tooling b1 >/dev/null 2>&1
expect "the agent's flush closes its tooling rows, not another agent's" test "$(rows tooling b1)" = 0 -a "$(rows tooling main)" = 1
edit_input tooling b1 "$HOME/.claude/projects/x/memory/note.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >/dev/null
expect "a tooling edit logged earlier today is not queued again" test "$(rows tooling b1)" = 0
edit_input skilledit main "$SKILL_DIR/SKILL.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >"$T/skilledit.out"
edit_input skilledit main "$(dirname "$SKILL_DIR")/growth-log-audit/SKILL.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >>"$T/skilledit.out"
expect "an edit to either growth-log skill is tooling work, and in step with the install it says nothing" \
    test "$(rows skilledit main)" = 2 -a ! -s "$T/skilledit.out"

# --- a block no commit waits for (the audit's statistics), while another session's commit waits
"$HOOKS/growth-log-consent.sh" busy yes >/dev/null
change "$REPO" busy
git_call w1 busy main "$REPO" "$(commit_cmd 'feat: another session, entry not written yet')"
printf '\n## Audit statistics\n\nnumbers\n' >>"$DAY"
"$HOOKS/growth-log-flush.sh" --session audit --agent main >"$T/flush-block.out" 2>&1
expect "a flush with no sha commits the agent's own block and leaves another session's commit waiting" \
    test -n "$(grep 'logged 0 item' "$T/flush-block.out")" -a "$(rows busy)" = 1 \
    -a -z "$(git -C "$WT" status --porcelain -- log)"
"$HOOKS/growth-log-flush.sh" --session audit --agent main >"$T/flush-nothing.out" 2>&1
expect "and with nothing new it says so" has "$T/flush-nothing.out" 'nothing to close'
entry busy main
flush busy main >/dev/null 2>&1

# --- a subagent-only session, and session end -------------------------------------------------

change "$REPO" nine
git_call so1 subonly b2 "$REPO" "$(commit_cmd 'feat: only a subagent committed')"
expect "a subagent writes without the question" lacks "$(ctx so1)" 'AskUserQuestion'
stop subonly >"$T/s5.out"
expect "the main agent's stop then asks the session's one question" has "$T/s5.out" 'has not answered'
stop subonly >"$T/s6.out"
expect "and only once" empty "$T/s6.out"

# Only the main agent hears the human, so commits its subagents logged come back to it once, for
# a block of its own, unless an entry of its own already follows them.
"$HOOKS/growth-log-consent.sh" subonly yes >/dev/null
stop subonly >"$T/s7.out"
expect "a subagent's commit still waiting for its entry is not handed to the main agent" empty "$T/s7.out"
entry subonly b2
flush subonly b2 >/dev/null 2>&1
edit_input subonly main "$HOME/.claude/projects/x/memory/main-note.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >/dev/null
stop subonly >"$T/s8.out"
expect "once it is logged, the main agent's stop hands it over for a block of its own" \
    test "$(jq -r .hookSpecificOutput.hookEventName "$T/s8.out")" = Stop -a -n "$(grep 'only a subagent committed' "$T/s8.out")"
expect "with the main agent's tooling edits and a closing command that takes no sha" \
    test -n "$(grep 'main-note.md' "$T/s8.out")" -a -n "$(jq -r .hookSpecificOutput.additionalContext "$T/s8.out" | grep -- '--session subonly --agent main$')"
stop subonly >"$T/s9.out"
expect "each commit is handed over once" empty "$T/s9.out"
change "$REPO" nine-b
git_call so2 subonly b3 "$REPO" "$(commit_cmd 'feat: a second subagent commit')"
entry subonly b3
flush subonly b3 >/dev/null 2>&1
change "$REPO" nine-c
git_call so3 subonly main "$REPO" "$(commit_cmd 'feat: then the main agent itself')"
entry subonly main
flush subonly main >/dev/null 2>&1
stop subonly >"$T/s10.out"
expect "an entry the main agent wrote after them covers its subagents' commits" empty "$T/s10.out"
change "$REPO" nine-d
git_call so4 subonly b4 "$REPO" "$(commit_cmd 'feat: a third subagent commit')"
entry subonly b4
flush subonly b4 >/dev/null 2>&1
stop subonly >"$T/s11.out"
expect "a later commit is handed over alone" \
    test -n "$(grep 'a third subagent commit' "$T/s11.out")" -a -z "$(grep 'only a subagent committed' "$T/s11.out")" -a -z "$(grep 'a second subagent commit' "$T/s11.out")"

change "$REPO" ten
git_call e1 ending main "$REPO" "$(commit_cmd 'feat: left without an entry')"
PIN_ENDING="$(awk -F'\t' '$6 == "ending" { print $1 }' "$STATE/queue.tsv")"
printf '{"session_id":"ending","reason":"clear"}' | "$HOOKS/growth-log-on-session-end.sh"
expect "session end drops what the session left" test "$(rows ending)" = 0
expect "and notes it in dropped.log" has "$STATE/dropped.log" 'ending'
expect "the dropped commit is never queued again" grep -qxF "$PIN_ENDING" "$STATE/never-log.tsv"
: >"$STATE/queue.tsv"

# --- the lock -----------------------------------------------------------------------------------

# A live holder that outlasts flush's one-minute wait: only the lock's age can free it.
sleep 600 &
HOLDER=$!
mkdir -p "$STATE/queue.lock" && echo "$HOLDER" >"$STATE/queue.lock/pid"
"$HOOKS/growth-log-consent.sh" pending yes >/dev/null
change "$REPO" eleven
capture k1 "$REPO" "$(commit_cmd 'feat: while the queue is locked')"
GROWTH_LOCK_WAIT=1 post k1 pending main
expect "a commit hook that finds the queue locked parks its rows and still instructs the agent" \
    test -n "$(ls "$STATE"/queue.pending.* 2>/dev/null)" -a -n "$(grep 'while the queue is locked' "$(ctx k1)")"
touch -t "$(date -v-10M +%Y%m%d%H%M.%S)" "$STATE/queue.lock"
entry pending main
flush pending main >"$T/flush-old-lock.out" 2>&1
expect "a lock older than five minutes is taken over, and the parked rows are merged and closed" \
    test -n "$(grep 'logged 1 item' "$T/flush-old-lock.out")" -a -z "$(ls "$STATE"/queue.pending.* 2>/dev/null)"
kill "$HOLDER" 2>/dev/null
wait "$HOLDER" 2>/dev/null
sh -c 'exit 0' &
DEAD=$!
wait "$DEAD"
mkdir -p "$STATE/queue.lock" && echo "$DEAD" >"$STATE/queue.lock/pid"
printf '\n### a note\n' >>"$DAY"
"$HOOKS/growth-log-flush.sh" >"$T/flush-stale.out" 2>&1
expect "a lock whose holder died is taken over" has "$T/flush-stale.out" 'logged'

# --- extraction guard, drift check --------------------------------------------------------------

for d in 2001-01-01 2001-01-02 2001-01-03; do printf '# %s\n' "$d" >"$WT/log/$d.md"; done
for d in 2001-01-01 2001-01-02 2001-01-03; do
    edit_input sub a1 "$WT/log/$d.md" | "$HOOKS/growth-log-on-extraction.sh"
done >"$T/ex.out"
expect "the extraction guard stays quiet inside a subagent" empty "$T/ex.out"
for d in 2001-01-01 2001-01-02; do
    edit_input ex main "$WT/log/$d.md" | "$HOOKS/growth-log-on-extraction.sh" >"$T/ex.out"
done
expect "two old days do not trigger the extraction guard" empty "$T/ex.out"
edit_input ex main "$WT/log/2001-01-03.md" | "$HOOKS/growth-log-on-extraction.sh" >"$T/ex.out"
expect "a third old day triggers it" test -n "$(jq -r '.hookSpecificOutput.additionalContext // empty' "$T/ex.out")"

"$HOOKS/growth-log-consent.sh" byhand yes >/dev/null
change "$FOREIGN" quiet-foreign
git_call fh byhand main "$FOREIGN" "git commit -q -am 'feat: quiet beside a foreign hook'"
change "$PATHED" quiet-pathed
git_call ph byhand main "$PATHED" "git commit -q -am 'feat: quiet under core.hooksPath'"
expect "a quiet commit is claimed in both once the line is there" \
    test -n "$(grep 'beside a foreign hook' "$(ctx fh)")" -a -n "$(grep 'under core.hooksPath' "$(ctx ph)")"
entry byhand main
flush byhand main >/dev/null 2>&1

echo '# local change' >>"$HOOKS/growth-log-flush.sh"
rm "$REPO/.git/hooks/post-commit"
bash "$SKILL_DIR/install.sh" --check >"$T/check.out" 2>&1
expect "check reports a changed hook" has "$T/check.out" 'drift: hook growth-log-flush.sh'
expect "and a watched repository that lost its post-commit hook" has "$T/check.out" "drift: post-commit hook missing in $REPO"
edit_input drift main "$HOOKS/growth-log-flush.sh" | "$HOOKS/growth-log-on-tooling-edit.sh" >"$T/drift1.out"
expect "an edit to an installed hook brings the drift from the skill, as context" \
    test "$(jq -r .hookSpecificOutput.hookEventName "$T/drift1.out")" = PostToolUse -a -n "$(grep 'drift: hook growth-log-flush.sh' "$T/drift1.out")"
edit_input drift main "$HOOKS/growth-log-flush.sh" | "$HOOKS/growth-log-on-tooling-edit.sh" >"$T/drift2.out"
expect "once per file and day" empty "$T/drift2.out"
edit_input drift main "$WT/WRITING-RULES.md" | "$HOOKS/growth-log-on-tooling-edit.sh" >"$T/drift3.out"
expect "an edit to a template in the log brings it too, and is not tooling work" \
    test -n "$(grep 'drift: hook' "$T/drift3.out")" -a "$(rows drift main)" = 1
bash "$SKILL_DIR/install.sh" --update >"$T/update.out" 2>&1
expect "--update restores the copies and the post-commit hook" bash "$SKILL_DIR/install.sh" --check
edit_input drift main "$HOOKS/growth-log-lib.sh" | "$HOOKS/growth-log-on-tooling-edit.sh" >"$T/drift4.out"
expect "an edit that leaves no drift says nothing" empty "$T/drift4.out"

# --- the audit reminder ---------------------------------------------------------------------------

stop remind >"$T/r0.out"
expect "a few days of entries bring no audit reminder" empty "$T/r0.out"
for d in 01 02 03 04 05 06 07 08 09 10; do printf '# 2002-01-%s\n' "$d" >"$WT/log/2002-01-$d.md"; done
stop remind >"$T/r1.out"
expect "ten unprocessed days bring one line for the human, and nothing that continues the turn" \
    test -n "$(jq -r '.systemMessage // empty' "$T/r1.out" | grep 'wait for an audit')" -a "$(jq -r '.hookSpecificOutput // "none"' "$T/r1.out")" = none
stop remind >"$T/r2.out"
expect "not again within the week" empty "$T/r2.out"
rm -f "$STATE/audit-reminded"
awk '{ print } /^\| Event date/ { getline; print; print "| 2002-02-01 | 2001-01-01 → 2002-01-10 | `processed` | smoke |" }' \
    "$WT/GROWTH-LOG.md" >"$T/glog" && cp "$T/glog" "$WT/GROWTH-LOG.md"
stop remind >"$T/r3.out"
expect "days a processed row covers do not count" empty "$T/r3.out"
change "$REPO" twelve
git_call r4 remind main "$REPO" "$(commit_cmd 'feat: asked, with days waiting')"
rm -f "$STATE/audit-reminded" "$WT"/log/2002-01-*.md
for d in 11 12 13 14 15 16 17 18 19 20; do printf '# 2002-01-%s\n' "$d" >"$WT/log/2002-01-$d.md"; done
"$HOOKS/growth-log-consent.sh" remind yes >/dev/null
stop remind >"$T/r4.out"
expect "a stop that brings waiting entries keeps its own message; the reminder waits" \
    test -n "$(grep 'asked, with days waiting' "$T/r4.out")" -a -z "$(grep 'wait for an audit' "$T/r4.out")" -a ! -e "$STATE/audit-reminded"
entry remind main
flush remind main >/dev/null 2>&1

# --- coverage: what the audit reads ----------------------------------------------------------------

coverage >"$T/coverage.out"
expect "coverage counts the commits agents made, and the one no call's output named" \
    test "$(awk -F'\t' '$1 == "made under Claude Code" { print $2 }' "$T/coverage.out")" = "$(journal)" \
    -a "$(awk -F'\t' '$1 == "never named by a call" { print $2 }' "$T/coverage.out")" = 1
expect "it lists that commit" has "$T/coverage.out" 'chore: output thrown away'
cp "$STATE/seen.tsv" "$T/seen.keep"
: >"$STATE/seen.tsv"
coverage >"$T/coverage-unseen.out"
cp "$T/seen.keep" "$STATE/seen.tsv"
expect "when no call's output named anything, every journaled commit counts as never named" \
    test "$(awk -F'\t' '$1 == "never named by a call" { print $2 }' "$T/coverage-unseen.out")" = "$(journal)"
expect "it counts the logged commits and the rows dropped at session end" \
    test "$(awk -F'\t' '$1 == "logged" { print $2 }' "$T/coverage.out")" -gt 5 \
    -a "$(awk -F'\t' '$1 == "dropped at session end" { print $2 }' "$T/coverage.out")" = 1

expect "no hook wrote an error but the deliberate not-writable flush" \
    test -z "$(grep -v 'is not writable' "$STATE/hook-errors.log")"

echo '{}' | GROWTH_CONFIG="$T/missing" "$HOOKS/growth-log-on-stop.sh" >"$T/fail1.out"
echo '{}' | GROWTH_CONFIG="$T/missing" "$HOOKS/growth-log-on-stop.sh" >"$T/fail2.out"
expect "a setup problem is shown to the user once" \
    test -n "$(jq -r '.systemMessage // empty' "$T/fail1.out")" -a ! -s "$T/fail2.out"

# --- a log outside the repository, a settings file git does not ignore --------------------------

export HOME="$T/home2"
mkdir -p "$HOME"
REPO2="$T/repo2"
git init -q "$REPO2"
git -C "$REPO2" commit -q --allow-empty -m "chore: initial"
mkdir -p "$REPO2/.claude/skills"
cp -R "$SKILL_DIR" "$REPO2/.claude/skills/growth-log-setup"
bash "$REPO2/.claude/skills/growth-log-setup/install.sh" --main "$REPO2" --log "$T/log2" --settings project >"$T/install2.out" 2>&1
expect "a log outside the repository needs no ignore rule" test "$(git -C "$T/log2" rev-list --count HEAD)" = 1
jq -n --arg cwd "$T/log2" '{session_id: "outside", cwd: $cwd, tool_name: "Agent", tool_input: {subagent_type: "general-purpose"}}' |
    "$HOME/.claude/growth-log/hooks/growth-log-on-dispatch.sh" >"$T/d11.out"
expect "a session that moved into a log kept outside the repository is held back like any other" denied "$T/d11.out"
expect "a settings file git does not ignore gets a warning" has "$T/install2.out" 'not git-ignored'
expect "so does a skill directory git neither tracks nor excludes, with the line to add" \
    has "$T/install2.out" "echo '/.claude/skills/growth-log-*/' >>"
expect "the first install, from an ignored directory, got no such warning" lacks "$T/install.out" 'neither tracked nor'
echo '/.claude/skills/growth-log-*/' >>"$REPO2/.git/info/exclude"
bash "$REPO2/.claude/skills/growth-log-setup/install.sh" --main "$REPO2" --log "$T/log2" --settings project >"$T/install3.out" 2>&1
expect "once excluded, the warning is gone" lacks "$T/install3.out" 'neither tracked nor'
mkdir -p "$T/gone"
echo "$T/gone" >>"$HOME/.claude/growth-log/state/repos"
bash "$REPO2/.claude/skills/growth-log-setup/install.sh" --update >"$T/update-gone.out" 2>&1
expect "an update survives a watched path that is no repository, writes no hook there and says so" \
    test $? -eq 0 -a -n "$(grep 'is no git repository' "$T/update-gone.out")" -a -z "$(find "$T/gone" -name post-commit)"

if [ "$KEEP" -eq 1 ]; then
    echo "sandbox kept: $T"
else
    rm -rf "$T"
fi
[ "$FAILED" -eq 0 ] && echo "growth-log-setup smoke test: all passed." || echo "growth-log-setup smoke test: FAILED."
exit "$FAILED"
