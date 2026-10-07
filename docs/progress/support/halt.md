---
description: The control-flow exception an action closure throws to stop the lifecycle cleanly while keeping its modal mounted (no failure notification). (draft)
ports:
  - vendor/filament/support/src/Exceptions/Halt.php
owns:
  - packages/support/src/Exceptions/Halt.php
---
# Halt (support)

`Sylament\Support\Exceptions\Halt` — the control-flow exception an action closure throws to stop the lifecycle cleanly while keeping its modal mounted (no failure notification).
Filament keeps it in `support` because several packages throw it; [`actions`](../../../packages/actions/src/Action.php) is the port's first thrower and [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) its first catcher.
Ported whole: the marker exception plus its database-transaction rollback control.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Marker exception](../../../packages/support/src/Exceptions/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | done | — | — |
| [Rollback control](../../../packages/support/src/Exceptions/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | done | — | Filament's own casing pair is kept verbatim — the setter `rollBackDatabaseTransaction(bool $condition = true)` with a capital B, the reader `shouldRollbackDatabaseTransaction()` with a lowercase one — and the flag defaults to `false`, so a thrower **commits** what ran before it unless it opts out. Its reader is [`HasActions::callMountedAction()`](../actions/has-actions.md#callmountedaction-drops-rate-limiting-and-authorization), which catches the exception and calls the action's `rollBackDatabaseTransaction()` or `commitDatabaseTransaction()` accordingly; the mechanics live on [`CanUseDatabaseTransactions`](../actions/action.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade). The panel pages that read the same flag for their own save paths are still unported |
