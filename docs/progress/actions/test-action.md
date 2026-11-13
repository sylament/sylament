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
| [`make(string $name)`](../../../packages/actions/src/Testing/TestAction.php), a name or an action class-string | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | done | — | [diverges](#make-constructs-directly) |
| [`arguments()` / `schemaComponent()` / `table()` / `bulk()`](../../../packages/actions/src/Testing/TestAction.php) | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | done | — | — |
| [`toArray(?string $defaultSchema = null)`](../../../packages/actions/src/Testing/TestAction.php) | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | done | — | [diverges](#an-object-record-keeps-its-place-until-the-host-mints-its-key) |
| `checkArguments()` | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | todo | — | — |

## Divergences

### `make()` constructs directly

Filament resolves `TestAction` through Laravel's container (`app(static::class, ['name' => $name])`); here `make()` is `new static($name)` behind Filament's final constructor, because a test value has no dependency to inject and the port reaches no container from a static method.

### An object record keeps its place until the host mints its key

Filament's `toArray()` turns an Eloquent record into `getKey()` itself.
A Doctrine record's key is minted by the table host from its entity manager (`getTableRecordKey()`), which `TestAction` cannot reach, so an object stays in `context.recordKey` and [`TestsActions`](tests-actions.md#a-table-record-is-keyed-by-the-host) swaps it for the host's key when it sends the action.
An array record still reads its `ArrayRecord` key and a scalar is the key, as in Filament.
