# Baseline prompt for PROFILE.md

`PROFILE.md` in the log is only a skeleton. Fill it in a **fresh session**, so that it is
reconstructed from the repository rather than from the setup conversation. Hand the user this
prompt:

```
Reconstruct my AI-assisted engineering trajectory in this repository and write it into
<log>/PROFILE.md following the skeleton already there: fill it in, do not rewrite it.
Sources you may use, nothing else: the history of the instruction files (CLAUDE.md,
.claude/rules/, .claude/skills/, docs/internals/) through git log -p and git show <sha>:<path>;
~/.claude/CLAUDE.md, ~/.claude/skills/ and this project's auto-memory from the file system;
the growth-log hooks; my commits (git log --author=<email>), here and in ../demo.
Rules: mark every claim with the markers the skeleton defines; an observed claim needs its
source (sha, file:line or a command); no unmeasured metrics, no celebratory claims, no emoji;
no phase without evidence; "Baseline numbers" only with the command that measured them; end
with the list of what you had no way to verify.
```
