#!/usr/bin/env bash
# Keeps every sha in the daily files log/*.md resolvable, by two independent mechanisms:
#
# 1. REMAP by patch-id (amend / rebase / cherry-pick): the same work has a new sha but the same
#    patch-id. The index row moves to the current sha and GROWTH-REMAP.tsv records old -> new.
#    The daily files are never rewritten: agents append to them at any time, and the old sha in
#    the text stays resolvable through its pin.
# 2. PIN via refs/growth/<patch-id> (squash merge, deleted branch): a squash does not keep the
#    patch-id — the squashed commit has a different diff and no twin exists. A ref of our own
#    keeps the object reachable for good, so `git show <sha>` from the log works even after the
#    branch is deleted and after `git gc`.
#
# There is one log but possibly several repositories (GROWTH_MAIN plus $GROWTH_STATE/repos).
# Column 4 of every index row carries the path the commit was made in, and **both operations
# must run in that repository**: neither `git show` nor `update-ref` sees an object from another
# repository. Rows are therefore grouped by `git rev-parse --git-common-dir`, so a worktree and
# its repository fall into one group and the ref is pinned once.
#
#   --check   only report, change nothing
#
# growth-log-flush.sh runs this before every commit, under the queue lock it already holds
# (GROWTH_LOCKED=1); run by hand, this script takes the lock itself.
set -uo pipefail

GROWTH_SCRIPT=1
# shellcheck source=/dev/null
. "$(dirname "$0")/growth-log-lib.sh"

CHECK=0
[ "${1:-}" = "--check" ] && CHECK=1
if [ "$CHECK" -eq 0 ] && [ "${GROWTH_LOCKED:-0}" != 1 ]; then
    growth_lock 60 || growth_fail "the queue stayed locked for a minute; nothing changed"
fi

python3 - "$GROWTH_LOG/GROWTH-REMAP.tsv" "$GROWTH_INDEX" "$CHECK" "$GROWTH_MAIN" "$GROWTH_REPOS" <<'PY'
import datetime
import os
import subprocess
import sys
from collections import defaultdict

remap_path, index_path, check_only, main, repos_file = (
    sys.argv[1], sys.argv[2], sys.argv[3] == "1", sys.argv[4], sys.argv[5])


def git(repo, *args, ok_fail=False):
    p = subprocess.run(("git", "-C", repo) + args, capture_output=True, text=True)
    if p.returncode != 0 and not ok_fail:
        return ""
    return p.stdout


def patch_ids(repo, *log_args):
    """patch-id -> commits for every commit `git log` lists, in one pipeline: one `git show` per
    commit costs seconds on a few hundred commits, and heal runs inside the queue lock."""
    log = subprocess.run(("git", "-C", repo, "log", "-p", "--format=commit %H") + log_args,
                         capture_output=True, text=True)
    ids = subprocess.run(("git", "-C", repo, "patch-id", "--stable"),
                         input=log.stdout, capture_output=True, text=True)
    by_patch = defaultdict(list)
    for line in ids.stdout.splitlines():
        parts = line.split()
        if len(parts) == 2:
            by_patch[parts[0]].append(parts[1])
    return by_patch


def exists(repo, sha):
    return bool(git(repo, "rev-parse", "--verify", "--quiet", f"{sha}^{{commit}}",
                    ok_fail=True).strip())


def growth_refs(repo):
    """Every refs/growth/<patch-id> of a repository with the commit it holds, read in one call,
    so a flush re-pins only what is missing instead of spawning git twice per index row."""
    refs = {}
    for line in git(repo, "for-each-ref", "--format=%(refname) %(objectname)", "refs/growth/").splitlines():
        name, _, target = line.partition(" ")
        refs[name[len("refs/growth/"):]] = target
    return refs


def is_pinned(refs, patch):
    """Does a refs/growth ref hold the commit, or does the object only remain until `git gc`?"""
    return patch in refs


def pin(repo, refs, patch, sha):
    """Pins only when no ref holds this sha yet: a remapped row moves its ref to the new commit."""
    if refs.get(patch, "").startswith(sha):
        return False
    full = git(repo, "rev-parse", "--verify", "--quiet", f"{sha}^{{commit}}", ok_fail=True).strip()
    if not full:
        return False
    subprocess.run(("git", "-C", repo, "update-ref", f"refs/growth/{patch}", full),
                   capture_output=True, text=True)
    refs[patch] = full
    return True


all_rows, rows = [], []
try:
    with open(index_path) as fh:
        for line in fh:
            parts = line.rstrip("\n").split("\t")
            if len(parts) < 7 or not parts[0]:
                continue
            all_rows.append(parts)
            # tooling: rows are work without a commit — no sha to heal, but they have to be
            # written back to the index.
            if not parts[0].startswith("tooling:"):
                rows.append(parts)
except FileNotFoundError:
    print("growth-log-heal: GROWTH-INDEX.tsv does not exist, nothing to heal.")
    sys.exit(0)

if not rows:
    print("growth-log-heal: the index is empty, nothing to heal.")
    sys.exit(0)

# A path in the index may be a worktree, may be deleted, and several paths may belong to one
# repository. The group key is therefore the shared git dir; the value a path git can run in.
# A deleted path does not mean a lost commit: a worktree shares objects with the repository it
# came from. For a missing path the known repositories are tried — first the one that really
# has the object, otherwise the main one (where a remap by patch-id still has a chance).
fallbacks = [main]
try:
    with open(repos_file) as fh:
        for line in fh:
            line = line.split("#")[0].strip()
            if line and os.path.isdir(line):
                fallbacks.append(line)
except FileNotFoundError:
    pass


def common_dir(path):
    if not os.path.isdir(path):
        return ""
    common = git(path, "rev-parse", "--git-common-dir", ok_fail=True).strip()
    return os.path.realpath(os.path.join(path, common)) if common else ""


groups, repo_of_path = defaultdict(list), {}
for row in rows:
    path, sha = row[3], row[1]
    key = repo_of_path.get(path)
    if key is None:
        key = common_dir(path)
        if key:
            repo_of_path[path] = key
    if not key:
        # per row, because two dead paths may belong to two different repositories
        home = next((r for r in fallbacks if exists(r, sha)), main)
        key = common_dir(home)
        row[3] = home
    groups[key].append(row)

mapping, unresolved, pinned_only, unpinned, ambiguous, gone_repo = [], [], [], [], [], []
pinned = 0

for key, group in sorted(groups.items()):
    repo = next((r[3] for r in group if os.path.isdir(r[3])), "")
    if not key or not repo:
        gone_repo.extend((r[1], r[3], r[6]) for r in group)
        continue

    label = os.path.basename(os.path.dirname(key)) or key
    refs = growth_refs(repo)

    # Our own refs/growth/* are deliberately left out — otherwise a pinned orphaned commit
    # would look healthy and the remap would never look at it again.
    reachable = set(git(repo, "rev-list", "--branches", "--remotes", "--tags").split())
    prefixes = defaultdict(list)
    for full in reachable:
        prefixes[full[:9]].append(full)

    broken = [r for r in group if r[1][:9] not in prefixes]
    if not broken:
        if not check_only:
            pinned += sum(1 for r in group if pin(repo, refs, r[0], r[1]))
        print(f"growth-log-heal: [{label}] {len(group)} sha, all reachable.")
        continue

    print(f"growth-log-heal: [{label}] {len(group)} sha, {len(broken)} invalidated by a history rewrite.")

    oldest = min(r[2] for r in broken)
    # Candidates are only commits on branches since the oldest broken entry, so the cost grows
    # with the size of the rewrite, not with the size of the repository. A bare date means that
    # date at the current time of day to git, hence the explicit midnight.
    by_patch = patch_ids(repo, "--branches", "--remotes", "--tags", "--no-merges",
                         f"--since={oldest} 00:00:00")

    for row in broken:
        pid, old, subject = row[0], row[1], row[6]
        hits = by_patch.get(pid, [])
        if not hits:
            # No twin on a branch, but the object exists = refs/growth holds it. The sha in the
            # log resolves, so there is nothing to fix; that is exactly the squash case.
            if exists(repo, old):
                bucket = pinned_only if is_pinned(refs, pid) else unpinned
            else:
                bucket = unresolved
            bucket.append((label, old, subject))
            continue
        if len(hits) > 1:
            ambiguous.append((label, old, [h[:9] for h in hits], subject))
        mapping.append((row, old, hits[0][:len(old)], subject, repo, refs))

    if not check_only:
        remapped = {id(m[0]) for m in mapping}
        pinned += sum(1 for r in group if id(r) not in remapped and pin(repo, refs, r[0], r[1]))

for _, old, new, subject, _, _ in mapping:
    print(f"  {old} -> {new}  {subject[:70]}")
for label, old, hits, subject in ambiguous:
    print(f"  ! [{label}] {old} has several live twins ({', '.join(hits)}) — taking the first: {subject[:50]}")
for label, old, subject in pinned_only:
    print(f"  = [{label}] {old} is on no branch, refs/growth holds it — resolvable: {subject[:50]}")
for label, old, subject in unpinned:
    print(f"  + [{label}] {old} object exists but no ref holds it yet"
          f"{' — pinned in this run' if not check_only else ' — the first run without --check pins it'}: {subject[:50]}")
for label, old, subject in unresolved:
    print(f"  ? [{label}] {old} has no twin and the object is gone: {subject[:60]}")
for old, path, subject in gone_repo:
    print(f"  ~ {old} repository/worktree no longer exists ({path}) — cannot heal: {subject[:50]}")

if check_only:
    print("growth-log-heal: --check, changed nothing.")
    sys.exit(0)

today = datetime.date.today().isoformat()
with open(remap_path, "a") as fh:
    for row, old, new, subject, repo, refs in mapping:
        fh.write(f"{old}\t{new}\t{today}\t{subject}\n")
        row[1] = new
        pinned += pin(repo, refs, row[0], new)
with open(index_path, "w") as fh:
    for row in all_rows:
        fh.write("\t".join(row) + "\n")

print(f"growth-log-heal: moved {len(mapping)} index row(s) to their current sha, recorded in GROWTH-REMAP.tsv.")
print(f"growth-log-heal: pinned {pinned} object(s) to refs/growth/ (proof against squash merge and gc).")
lost = [f"{lbl}:{old}" for lbl, old, _ in unresolved] + [f"?:{old}" for old, _, _ in gone_repo]
if lost:
    print(f"growth-log-heal: {len(lost)} sha could not be saved: {' '.join(lost)}")
PY
STATUS=$?
[ "$CHECK" -eq 0 ] && [ "${GROWTH_LOCKED:-0}" != 1 ] && growth_unlock
exit "$STATUS"
