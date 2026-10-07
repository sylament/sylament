---
description: 'The two-way binding between an action and the schema it was rendered inside: `schemaComponent()` / `schemaContainer()` set it, and the two getters… (draft)'
ports:
  - vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php
owns:
  - packages/actions/src/Concerns/BelongsToSchemaComponent.php
---
# BelongsToSchemaComponent (actions)

[`Sylament\Actions\Concerns\BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) — the two-way binding between an action and the schema it was rendered inside: `schemaComponent()` / `schemaContainer()` set it, and the two getters resolve it by falling back through the action's own binding, its container's parent component, and its group.

Both [`Action`](action.md) and [`ActionGroup`](action-group.md) use it, and both lean on it for the same thing — the `$get` / `$set` utilities injected into evaluated closures.
It earns a file of its own only because of the divergence below, which constrains the trait rather than either consumer.

## Divergences

### `getSchemaComponentOrFail()` throws where Filament fatals

`getSchemaComponent()` is nullable on both sides.
Filament dereferences it unguarded in the `$get` / `$set` resolver arms of `Action` and `ActionGroup` —

```php
'schemaGet', 'get' => [$this->getSchemaComponent()->makeGetUtility()->skipComponentsChildContainersWhileSearching(false)],
```

— so an action evaluated outside a schema dies with *Call to a member function makeGetUtility() on null*, naming neither the action nor the binding it wanted.

Sylament routes those arms through `getSchemaComponentOrFail()`, which throws a `LogicException` naming the class and what the utilities need.
The failure mode is unchanged — both stop the request on the same input — and an exception message is not API, so nothing a migrating user depends on moves.

The reason it is not simply left as-is: the unguarded dereference was the only thing in the codebase needing an inline `@phpstan-ignore`, once each in `Action` and `ActionGroup` for `$get` plus one more for `$set`.
PHPStan runs at level max with no baseline precisely so nothing is suppressed, and the house pattern for a missing required relationship is already a `LogicException` that says how to fix it — [`Modals::getLiveComponent()`](../../../packages/actions/src/Twig/Components/Modals.php), [`HasName`](../../../packages/actions/src/Concerns/HasName.php), [`IconManager`](../../../packages/support/src/Icons/IconManager.php).
Three suppressions became zero, and [`StaticAnalysisSuppressionTest`](../../../packages/schemas/tests/Architecture/StaticAnalysisSuppressionTest.php) now fails the suite if another is added.
