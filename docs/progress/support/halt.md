---
description: The control-flow exception a closure throws to stop the lifecycle cleanly while its action modal stays mounted, without a failure notification.
ports:
  - vendor/filament/support/src/Exceptions/Halt.php
owns:
  - packages/support/src/Exceptions/Halt.php
---
# Halt (support)

`Sylament\Support\Exceptions\Halt` ports `Filament\Support\Exceptions\Halt`.
Filament keeps it in `support` because several packages throw or catch it: [`Action::halt()`](../../../packages/actions/src/Action.php) and the panels [`Page::halt()`](../../../packages/panels/src/Pages/Page.php) throw it, and [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php), the record pages and the [`Wizard`](../../../packages/schemas/src/Components/Wizard.php) catch it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Halt`](../../../packages/support/src/Exceptions/Halt.php) marker exception | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | done | — | — |
| [`rollBackDatabaseTransaction()` / `shouldRollbackDatabaseTransaction()`](../../../packages/support/src/Exceptions/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | done | — | [semantics](cancel.md#the-rollback-flag-defaults-to-false-so-a-thrower-commits-what-ran-before-it) · [record pages](../panels/custom-pages.md#halts-rollback-flag-is-carried-and-ignored) |
