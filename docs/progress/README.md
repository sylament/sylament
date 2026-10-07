# Progress

The working tracker for the Filament → Sylament port: what the rules are, and what is done and why, component by component.
What to build next is decided outside the repository.

This is the system of record for the Filament → Sylament port.

Editing rules load from `.claude/rules/trail.md` when you edit a file here; the reasoning behind them is in [docs/internals/trail.md](../internals/trail.md).

## Statuses

One vocabulary, used by every table in this tree.

| Status | Means |
| --- | --- |
| **todo** | Not started. |
| **in progress** | Partly ported — the gap is written up in the row's detail section. |
| **blocked** | Wanted for parity, but a prerequisite named in _Blocked by_ must land first. |
| **done** | Ported and covered by a test. The detail section is deleted; the row stays. |
| **skipped** | A deliberate non-goal — the toolkit works without it, so we are not porting it (whether or not Filament ships it). |

**skipped** vs **blocked** is a question of intent, not mechanics.
**blocked** is something we _want_ for parity and will build once the prerequisite in _Blocked by_ lands — even where we could stub it now.
**skipped** is something we have decided the toolkit does not need.
An item we simply have not reached yet, with nothing to wait on, is **todo**.

A class or member Filament marks `@deprecated` is **skipped**, never ported: the port ships no deprecated surface, and the row names the replacement Filament points to.

Divergence is **not** a status — it is orthogonal, and any row can carry one.
A row we ported differently from Filament stays **done** and links its reasoning from _Detail_; a row we are never porting is **skipped** and links a divergence saying so.
Nothing that has no code is ever **done** — that would tell a migrating user the API exists here.

At package level, **done** means every row in that package's tables is settled — done or skipped — and no table still carries its [unaudited row](../internals/trail.md#an-unaudited-table-says-so).

## Packages

The nine bundles under `packages/`, each linked to the Filament package it ports (Filament's panel builder ships as `filament/filament`, so `panels` maps there).

| Package | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`actions`](../../packages/actions) | [`vendor/filament/actions`](../../vendor/filament/actions) | in progress | [actions](actions/README.md) |
| [`forms`](../../packages/forms) | [`vendor/filament/forms`](../../vendor/filament/forms) | in progress | [forms](forms/README.md) |
| [`infolists`](../../packages/infolists) | [`vendor/filament/infolists`](../../vendor/filament/infolists) | todo | [infolists](infolists/README.md) |
| [`notifications`](../../packages/notifications) | [`vendor/filament/notifications`](../../vendor/filament/notifications) | in progress | [notifications](notifications/README.md) |
| [`panels`](../../packages/panels) | [`vendor/filament/filament`](../../vendor/filament/filament) | in progress | [panels](panels/README.md) |
| [`schemas`](../../packages/schemas) | [`vendor/filament/schemas`](../../vendor/filament/schemas) | in progress | [schemas](schemas/README.md) |
| [`support`](../../packages/support) | [`vendor/filament/support`](../../vendor/filament/support) | in progress | [support](support/README.md) |
| [`tables`](../../packages/tables) | [`vendor/filament/tables`](../../vendor/filament/tables) | in progress | [tables](tables/README.md) |
| [`widgets`](../../packages/widgets) | [`vendor/filament/widgets`](../../vendor/filament/widgets) | todo | [widgets](widgets/README.md) |
