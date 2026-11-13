---
description: The testing helpers that mount, call and assert a live host's actions, on the tester a test drives.
ports:
  - vendor/filament/actions/src/Testing/TestsActions.php
owns:
  - packages/actions/src/Testing/TestsActions.php
---
# TestsActions (actions)

[`Sylament\Actions\Testing\TestsActions`](../../../packages/actions/src/Testing/TestsActions.php) is a trait on the [`Testable`](testable.md) tester, where Filament's is a Livewire `Testable` mixin.
An action is named by a string, a dotted path (`'archive.confirm'` nests), a [`TestAction`](test-action.md) or a list of either, and its arguments by name or for every level, as in Filament.
The sending helpers send the host's own `mountAction()` and `callMountedAction()` live actions; the assertions read the re-hydrated host, the state after the last response.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`mountAction()` / `callAction()` / `callMountedAction()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [semantics](#callaction-stops-without-failing-when-the-action-ran-on-mount-or-redirected) |
| [`assertActionExists()` / `assertActionVisible()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [diverges](#a-missing-action-is-always-an-assertion-failure) |
| [`parseNestedActions()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [diverges](#a-table-record-is-keyed-by-the-host) |
| `unmountAction()` / `setActionData()` / `assertActionDataSet()` / `assertHasActionErrors()` / `assertHasNoActionErrors()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionDoesNotExist()` / `assertActionHidden()` / `assertActionEnabled()` / `assertActionDisabled()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionHas…()` / `assertActionDoesNotHave…()` (icon, label, colour, URL) / `assertActionShouldOpenUrlInNewTab()` / `assertActionShouldNotOpenUrlInNewTab()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionMounted()` / `assertActionNotMounted()` / `assertActionHalted()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertMountedActionModalSee()` / `assertMountedActionModalDontSee()` / their `…Html` variants / `getMountedActionModalHtml()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionListInOrder()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionHeld()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | skipped | — | [deprecated for `assertActionHalted()`](../README.md#statuses) |

## Semantics

### `callAction()` stops without failing when the action ran on mount or redirected

`callAction()` asserts the action visible, mounts it, fills its form when given data, and calls it, as Filament's does.
It stops after mounting, without a failure, when the response redirected, when the mounted stack did not grow by the levels it mounted, or when the last mounted action is not the one named: an action with no modal runs inside `mountAction()` and is unmounted again, so there is nothing left to call.
The levels are counted from the parsed path, where Filament counts `Arr::wrap()` of its argument, which reads a dotted path as one level and stops a nested call that did mount.

## Divergences

### A missing action is always an assertion failure

The assertions resolve the path through the host's own resolution, on the copy `component()` hydrates for the read: the parsed levels join that copy's mounted stack, and the host's `getMountedActions()` resolves the whole stack as a mount would, arguments, record and nested modal actions included, without running anything.
The action exists only when every level resolves, so a missing level fails the assertion.
Filament's host skips a level that resolves to nothing and answers the last one that resolved, so a path whose last level is missing can pass on its parent, and a missing nested or table action throws `ActionNotResolvableException` out of the assertion instead of failing it.

### A table record is keyed by the host

`parseNestedActions()` turns a record object in a table action's `recordKey` into the key the table host mints with `getTableRecordKey()`, as Filament's does for an Eloquent model, and throws a `LogicException` naming the host when it is not a table host.
It is protected here, where Filament's mixin method is public, because only the helpers and tables' tester call it.
An action class-string is named by its `getDefaultName()`; Filament reads an `#[ActionName]` attribute first, which is [not ported](README.md#unported).
