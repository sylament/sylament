# Halt (support)

`Sylament\Support\Exception\Halt` — the control-flow exception an action closure
throws to stop the lifecycle cleanly while keeping its modal mounted (no failure
notification). Filament keeps it in `support` because several packages throw it;
[`actions`](../../../packages/actions/src/Action.php) is the port's first thrower
and [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) its
first catcher. Ported as a bare marker exception minus Filament's
database-transaction rollback control (see [Divergences](#the-database-transaction-rollback-control-is-deferred)).

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php)
  — the exception class and its rollback-control state

## Surface

Every point of this exception. _Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences). Rows never disappear — only their
detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Marker exception](../../../packages/support/src/Exception/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | done | — | — |
| Rollback control | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | skipped | — | [diverges](#the-database-transaction-rollback-control-is-deferred) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

Both rows above are settled — **done** or **skipped** — so none has a write-up
here now. A row moving to **in progress** or **blocked** grows one.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above: a row stays **done** or **skipped** and keeps
pointing here. Say what would reverse the decision when it is provisional.

### The database-transaction rollback control is deferred

Filament's `Halt` carries a `shouldRollbackDatabaseTransaction` flag plus its
`rollBackDatabaseTransaction()` fluent setter and `shouldRollbackDatabaseTransaction()`
reader — read only where an action runs its handler inside a DB transaction
([`InteractsWithActions`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)
catches the `Halt` and, per the flag, calls the action's
`rollBackDatabaseTransaction()`; the transaction mechanics live in
[`CanUseDatabaseTransactions`](../../../vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php),
and the panel pages read the same flag) to decide whether a caught `Halt` rolls
the transaction back. The port has no transaction-wrapping consumer yet: the
`actions` we ported throws `Halt` from `halt()` and catches it in `HasActions`
to keep the modal mounted, but never opens a transaction, so the flag would have
no reader. It is a self-contained pair of methods blocked by nothing, so it is
**skipped**, not blocked — re-add it in the same change as the first
Doctrine-transaction-wrapping consumer, where it finally has behavior to test
against. The class ships as a bare `extends Exception` until then.
