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
The sending helpers send the host's own `mountAction()`, `callMountedAction()` and `unmountAction()` live actions, and the data and error helpers delegate to forms' and schemas' helpers on the mounted action's schema; the assertions read the re-hydrated host, the state after the last response.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`mountAction()` / `callAction()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [semantics](#callaction-stops-without-failing-when-the-action-ran-on-mount-or-redirected) · [diverges](#callaction-counts-the-levels-it-parsed) |
| [`assertActionExists()` / `assertActionVisible()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [diverges](#a-missing-action-is-always-an-assertion-failure) |
| [`parseNestedActions()` with a name, a `TestAction` or a list of them, and its refusal of a table record on a host that is not a table](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [diverges](#a-table-record-is-keyed-by-the-host) |
| [`callMountedAction()`, and `parseNestedActions()`'s action class-string and table record key](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [diverges](#a-table-record-is-keyed-by-the-host) |
| [`parseNestedActions()`'s `$arguments` for every level, and a level given none](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [semantics](#a-level-carries-arguments-only-when-the-call-gives-some) |
| [`parseNestedActions()`'s dotted path, and the helpers' `$arguments` keyed by action name](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [semantics](#arguments-keyed-by-action-name-stop-at-the-first-level-that-names-one) |
| [`unmountAction()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | — |
| `assertActionDoesNotExist()` / `assertActionHidden()` / `assertActionEnabled()` / `assertActionDisabled()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionHas…()` / `assertActionDoesNotHave…()` (icon, label, colour, URL) / `assertActionShouldOpenUrlInNewTab()` / `assertActionShouldNotOpenUrlInNewTab()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| [`assertActionMounted()` / `assertActionNotMounted()` / `assertActionHalted()`](../../../packages/actions/src/Testing/TestsActions.php) | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | done | — | [semantics](#the-mounted-assertions-compare-the-hosts-raw-frames-from-the-stacks-end) · [diverges](#the-mounted-assertions-name-the-action-they-failed-on) · [diverges](#only-an-empty-array-means-no-actions) · [diverges](#the-closure-check-needs-a-frames-arguments-named) |
| `assertMountedActionModalSee()` / `assertMountedActionModalDontSee()` / their `…Html` variants / `getMountedActionModalHtml()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionListInOrder()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| `assertActionHeld()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | skipped | — | [deprecated for `assertActionHalted()`](../README.md#statuses) |
| `setActionData()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | skipped | — | [deprecated for `fillForm()`](../README.md#statuses) |
| `assertActionDataSet()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | skipped | — | [deprecated for `assertSchemaStateSet()`](../README.md#statuses) |
| `assertHasActionErrors()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | skipped | — | [deprecated for `assertHasFormErrors()`](../README.md#statuses) |
| `assertHasNoActionErrors()` | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | skipped | — | [deprecated for `assertHasNoFormErrors()`](../README.md#statuses) |

## Semantics

### `callAction()` stops without failing when the action ran on mount or redirected

`callAction()` asserts the action visible, mounts it, fills its form when given data, and calls it, as Filament's does.
It stops after mounting, without a failure, when the response redirected, when the mounted stack did not grow by the levels it mounted, or when the last mounted action is not the one named: an action with no modal runs inside `mountAction()` and is unmounted again, so there is nothing left to call.

### A level carries arguments only when the call gives some

`parseNestedActions()` leaves `arguments` out of a level's descriptor when neither the level nor `$arguments` names any, as Filament's does, and `TestAction` leaves them out when its `arguments()` is a closure or unset.
The sending helpers send a missing key as `[]`, the host's own default, and the assertions resolve it as `[]`.
So `assertActionMounted('rename')` passes on an action mounted with arguments, and only a call that names arguments compares them.

### The mounted assertions compare the host's raw frames, from the stack's end

`assertActionMounted()`, `assertActionNotMounted()` and `assertActionHalted()`, its alias as in Filament, read the re-hydrated host's `mountedActions` frames, `{name, arguments, context}` each, where Filament's `assertSet('mountedActions.…')` reads Livewire's state.
They read the frame as the host keeps it, never an action's `getContext()`, which rebuilds a context that can differ from the kept frame.
With no actions, `assertActionMounted()` asserts that the stack is not empty and `assertActionNotMounted()` that it is.
Otherwise the parsed levels, never relative to the mounted stack, are lined up with its last frames, and each level's name and context are compared, its arguments when the call gives some, and a `TestAction`'s closure `arguments()` through `checkArguments()` on the frame's arguments.
`assertActionMounted()` compares loosely, as Livewire's `assertSet()` does, and `assertActionNotMounted()` strictly, failing only when every level matches, as Filament's two do.

### Arguments keyed by action name stop at the first level that names one

`$arguments` reach a level by its action's name once any level is keyed so: from the first level whose name is a key, a later level gets only its own key's arguments, or none.
A level before that one gets the whole array, as Filament's does, so a map that keys only a nested level also reaches its parent; keying the first level keeps every level to its own.

## Divergences

### The mounted assertions name the action they failed on

Filament's `assertActionMounted()` fails with Livewire's `assertSet()` message, PHPUnit's bare comparison; here each comparison names the action: `Failed asserting that the action [<name>] is mounted.`, `… is mounted with the expected arguments.`, `… is mounted in the expected context.`, and with no actions `Failed asserting that an action is mounted.` or `Failed asserting that no action is mounted.`
The closure check and `assertActionNotMounted()` keep Filament's messages.

### Only an empty array means no actions

The mounted assertions read `$actions` as "no actions" only when it is `[]`, where Filament's `empty()` also reads `''` and `'0'` so; here `''` and `'0'` are read as action names, as any other string is.

### The closure check needs a frame's arguments named

Before a `TestAction`'s closure `arguments()` sees a frame's arguments through `checkArguments()`, the mounted assertions refuse a frame whose arguments are a list, or any map with a key that is not a string, with a `LogicException`: `A mounted action frame keeps arguments that are not named.`
Filament hands such arguments to the closure as they are; the host's `mountAction()` accepts any array, but `checkArguments()` takes an argument map, so the assertion names the frame that breaks it rather than passing the closure keys it cannot read by name.

### `callAction()` counts the levels it parsed

`callAction()` checks that the mounted stack grew by the levels of the parsed path, where Filament counts `Arr::wrap()` of its argument, which reads a dotted path as one level and stops a nested call that did mount.

### A missing action is always an assertion failure

The assertions resolve the path through the host's own resolution, on the copy `component()` hydrates for the read: the parsed levels join that copy's mounted stack, and the host's `getMountedActions()` resolves the whole stack as a mount would, arguments, record and nested modal actions included, without running anything.
The action exists only when every level resolves, so a missing level fails the assertion.
Filament's host skips a level that resolves to nothing and answers the last one that resolved, so a path whose last level is missing can pass on its parent, and a missing nested or table action throws `ActionNotResolvableException` out of the assertion instead of failing it.

### A table record is keyed by the host

`parseNestedActions()` turns a record object in a table action's `recordKey` into the key the table host mints with `getTableRecordKey()`, as Filament's does for an Eloquent model, and throws a `LogicException` naming the host when it is not a table host.
An action class-string is named by its `getDefaultName()`; Filament reads an `#[ActionName]` attribute first, which is [not ported](README.md#unported).
