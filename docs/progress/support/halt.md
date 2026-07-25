# Halt (support)

`Sylament\Support\Exception\Halt` — the control-flow exception an action closure
throws to stop the lifecycle cleanly while keeping its modal mounted (no failure
notification). Filament keeps it in `support` because several packages throw it;
[`actions`](../../../packages/actions/src/Action.php) is the port's first thrower
and [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) its
first catcher. Ported as a bare marker exception; its
database-transaction rollback control is deferred (see [Details](#details)).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php)
  — the exception class and its rollback-control state

## Surface

Every point of this exception. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details), deleted once the row is
**done**. Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Marker exception](../../../packages/support/src/Exception/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | done | — | — |
| Rollback control | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | blocked | panels DB-transaction pages | [detail](#details) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**.

### Rollback control

Filament's `Halt` carries a `shouldRollbackDatabaseTransaction` flag plus its
`rollBackDatabaseTransaction()` fluent setter and `shouldRollbackDatabaseTransaction()`
reader, read where an action runs its handler inside a DB transaction:
[`InteractsWithActions`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)
catches the `Halt` and, per the flag, calls the action's
`rollBackDatabaseTransaction()` (the transaction mechanics live in
[`CanUseDatabaseTransactions`](../../../vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php);
panel pages read the same flag). Sylament's `Halt` is a bare `extends Exception`:
the `actions` we ported throw it from `halt()` and catch it in `HasActions` to
keep the modal mounted, but never open a transaction, so the flag would have no
reader. So the control is **blocked**, not a permanent divergence — it re-lands,
flag and setter and reader and transaction wrapping together, in the same change
as the first Doctrine-transaction-wrapping consumer (the panel pages), where it
finally has behavior to test. Its sibling [`Cancel`](cancel.md) is blocked on the
same layer for the same reason.
