# Port trail

The system of record for the Filament → Sylament port: one file per piece of ported code, holding the status of each of its features and the reasoning behind every choice a reader would otherwise get wrong.
What to build next is decided outside the repository.

Editing rules load from `.claude/rules/trail.md` when you edit a file here; the reasoning behind them is in [docs/internals/trail.md](../internals/trail.md).

## Packages

The nine bundles under `packages/`, each linked to the Filament package it ports (Filament's panel builder ships as `filament/filament`, so `panels` maps there).
The statuses are rolled up from every row of the package's files and its Unported table.

<!-- trail-index:start -->
| Package | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`actions`](../../packages/actions) | [`vendor/filament/actions`](../../vendor/filament/actions) | in progress | [actions](actions/README.md) |
| [`forms`](../../packages/forms) | [`vendor/filament/forms`](../../vendor/filament/forms) | in progress | [forms](forms/README.md) |
| [`infolists`](../../packages/infolists) | [`vendor/filament/infolists`](../../vendor/filament/infolists) | in progress | [infolists](infolists/README.md) |
| [`notifications`](../../packages/notifications) | [`vendor/filament/notifications`](../../vendor/filament/notifications) | in progress | [notifications](notifications/README.md) |
| [`panels`](../../packages/panels) | [`vendor/filament/filament`](../../vendor/filament/filament) | in progress | [panels](panels/README.md) |
| [`schemas`](../../packages/schemas) | [`vendor/filament/schemas`](../../vendor/filament/schemas) | in progress | [schemas](schemas/README.md) |
| [`support`](../../packages/support) | [`vendor/filament/support`](../../vendor/filament/support) | in progress | [support](support/README.md) |
| [`tables`](../../packages/tables) | [`vendor/filament/tables`](../../vendor/filament/tables) | in progress | [tables](tables/README.md) |
| [`widgets`](../../packages/widgets) | [`vendor/filament/widgets`](../../vendor/filament/widgets) | in progress | [widgets](widgets/README.md) |
<!-- trail-index:end -->

Two files cut across every package: [renames.md](renames.md), every Filament API name the port changed, and [translations.md](translations.md), the systematic idiom and namespace translations that are not renames.

## Statuses

One vocabulary, used by every table in this tree.

| Status | Means |
| --- | --- |
| **todo** | Not started, and nothing to wait on. |
| **in progress** | Partly ported: the gap is written up in the row's `## Details` entry. |
| **blocked** | Wanted for parity, but the prerequisite named in _Blocked by_ must land first. |
| **done** | Ported and covered by a test. |
| **skipped** | A deliberate non-goal: the toolkit works without it, so it is not ported (whether or not Filament ships it). |

**skipped** vs **blocked** is a question of intent, not mechanics.
**blocked** is something wanted for parity that is built once the prerequisite in _Blocked by_ lands, even where it could be stubbed now.
**skipped** is something the toolkit has decided it does not need.
An item not reached yet, with nothing to wait on, is **todo**.

A class or member Filament marks `@deprecated` is **skipped**, never ported: the port ships no deprecated surface, and the row names the replacement Filament points to.

Divergence is not a status: it is orthogonal, and any row can carry one.
A row ported differently from Filament stays **done** and links its reasoning from _Detail_; a row that is never ported is **skipped** and links a divergence saying so.
Nothing that has no code is ever **done**, since that would tell a migrating user the API exists here.

The repository runs no JavaScript tests, so a Stimulus controller's behaviour counts as covered when a render test pins its wiring: the controller, its targets, values and actions.

A file's and a package's status are rollups of their rows, rendered into the indexes by `composer docs:index`; the rollup is in [docs/internals/trail.md](../internals/trail.md#status-is-written-once).
