---
description: The value a test names an action by for the testing helpers, and the mount descriptor it becomes.
ports:
  - vendor/filament/actions/src/Testing/TestAction.php
owns:
  - packages/actions/src/Testing/TestAction.php
---
# TestAction (actions)

[`Sylament\Actions\Testing\TestAction`](../../../packages/actions/src/Testing/TestAction.php) names an action for [`TestsActions`](tests-actions.md) where a plain name is not enough: an action inside a schema component, a table row's or a bulk action, with or without arguments.
`toArray()` turns it into the descriptor the host's `mountAction()` takes, `{name, arguments?, context}`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(string $name)` with an action's name](../../../packages/actions/src/Testing/TestAction.php) | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | done | — | [diverges](#make-constructs-directly) |
| [`schemaComponent()` and `toArray(?string $defaultSchema = null)`](../../../packages/actions/src/Testing/TestAction.php) | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | done | — | — |
| [`make()` with an action class-string, and `table()`](../../../packages/actions/src/Testing/TestAction.php) | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | in progress | — | [detail](#a-class-string-and-a-table-record-wait-for-a-table-host-test) · [diverges](#an-object-record-keeps-its-place-until-the-host-mints-its-key) |
| [`bulk()` / `arguments()`](../../../packages/actions/src/Testing/TestAction.php) | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | in progress | — | [detail](#bulk-and-arguments-wait-for-a-test-that-needs-them) |
| `checkArguments()` | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | todo | — | — |

## Details

### A class-string and a table record wait for a table host test

`make()` takes an action class-string, which [`TestsActions`](tests-actions.md) resolves by the class's default name, and `table()` takes a row's record, whose key the table host mints; both are ported, and no test drives them yet.
They are covered when the first table host test, `DeleteActionTest`'s row delete, names `DeleteAction::class` on a record.

### `bulk()` and `arguments()` wait for a test that needs them

Both are ported and land in `toArray()`'s descriptor, and no test drives a bulk action or passes arguments yet; each is covered by the first test that does, since every ported helper is exercised by the suite.

## Divergences

### `make()` constructs directly

Filament resolves `TestAction` through Laravel's container (`app(static::class, ['name' => $name])`); here `make()` is `new static($name)` behind Filament's final constructor, because a test value has no dependency to inject and the port reaches no container from a static method.

### An object record keeps its place until the host mints its key

Filament's `toArray()` turns an Eloquent record into `getKey()` itself.
A Doctrine record's key is minted by the table host from its entity manager (`getTableRecordKey()`), which `TestAction` cannot reach, so an object stays in `context.recordKey` and [`TestsActions`](tests-actions.md#a-table-record-is-keyed-by-the-host) swaps it for the host's key when it sends the action.
An array record still reads its `ArrayRecord` key and a scalar is the key, as in Filament.
