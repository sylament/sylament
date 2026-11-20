# Skill debt — fixes that could not be made right away

A queue of changes to `CLAUDE.md`, `.claude/rules/`, `.claude/skills/` and the auto-memory that
could not be made at the moment of the finding — most often because the target file lives on another
branch, or because the human has to choose the target branch.

**Why here.** The log lives outside every branch of the repositories it describes, so it is
reachable from all of them. A file in `docs/` on another branch is
not, so a finding written there drops out of sight when you switch branches.

**Rules.** A row is added by whoever made the finding, in the same session. A row is **deleted only
after verifying** that the change really is on the target branch — not after someone promises it.
When a finding resolves itself meanwhile, it is struck through with a date, so it is visible that
it did not disappear unrecorded.

| Found | What to change | Where it belongs | Why it could not be done right away |
|---|---|---|---|

## Closed
