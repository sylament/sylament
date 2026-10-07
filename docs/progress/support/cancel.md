---
description: The control-flow exception an action closure throws to abort the lifecycle and unmount the action without a failure notification.
ports:
  - vendor/filament/support/src/Exceptions/Cancel.php
owns:
  - packages/support/src/Exceptions/Cancel.php
---
# Cancel (support)

`Sylament\Support\Exceptions\Cancel` ports `Filament\Support\Exceptions\Cancel`.
[`Action::cancel()`](../../../packages/actions/src/Action.php) throws it, and [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) catches it, settles the action's database transaction and falls through to unmount, which closes the modal.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Cancel`](../../../packages/support/src/Exceptions/Cancel.php) marker exception | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | done | — | — |
| [`rollBackDatabaseTransaction()` / `shouldRollbackDatabaseTransaction()`](../../../packages/support/src/Exceptions/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | done | — | [semantics](#the-rollback-flag-defaults-to-false-so-a-thrower-commits-what-ran-before-it) |

## Semantics

### The rollback flag defaults to `false`, so a thrower commits what ran before it

`Cancel` and [`Halt`](halt.md) carry the same flag with Filament's method names unchanged: the setter `rollBackDatabaseTransaction(bool $condition = true)` has a capital B and the reader `shouldRollbackDatabaseTransaction()` a lowercase one.
The flag starts `false`, so an exception thrown without opting in leaves the work that ran before it committed.
[`HasActions::callMountedAction()`](../actions/has-actions.md#callmountedaction-runs-filaments-pipeline-in-filaments-order) reads the flag and calls the action's `rollBackDatabaseTransaction()` or `commitDatabaseTransaction()`, whose mechanics live on [`CanUseDatabaseTransactions`](../actions/can-use-database-transactions.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade).
