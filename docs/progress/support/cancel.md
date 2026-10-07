# Cancel (support)

`Sylament\Support\Exceptions\Cancel` — the control-flow exception an action closure throws to abort the lifecycle and unmount the action (closing the modal) without a failure notification.
It ports Filament's `Filament\Support\Exceptions\Cancel`, and `Sylament\Actions\Action` throws it from `cancel()` for `HasActions` to catch and fall through to unmount.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) — the exception and its DB-transaction rollback control

## Surface

Every point of this exception.
_Blocked by_ names what has to land first.
_Detail_ carries the row's own note; a row that needed a Details section no longer has one.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Marker exception](../../../packages/support/src/Exceptions/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | done | — | — |
| [Rollback-transaction control](../../../packages/support/src/Exceptions/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | done | — | Filament's own casing pair is kept verbatim — the setter `rollBackDatabaseTransaction(bool $condition = true)` with a capital B, the reader `shouldRollbackDatabaseTransaction()` with a lowercase one — and the flag defaults to `false`, so a thrower **commits** what ran before it unless it opts out. Its reader is [`HasActions::callMountedAction()`](../actions/has-actions.md#callmountedaction-drops-rate-limiting-and-authorization), which catches the exception and calls the action's `rollBackDatabaseTransaction()` or `commitDatabaseTransaction()` accordingly; the mechanics live on [`CanUseDatabaseTransactions`](../actions/action.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade). The panel pages that read the same flag for their own save paths are still unported |
