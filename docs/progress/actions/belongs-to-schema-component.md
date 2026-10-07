---
description: The trait binding an action or group to the schema component and container it renders inside, with the fallback chain the `$get` / `$set` utilities read.
ports:
  - vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php
owns:
  - packages/actions/src/Concerns/BelongsToSchemaComponent.php
---
# BelongsToSchemaComponent (actions)

Both [`Action`](action.md) and [`ActionGroup`](action-group.md) compose it, and both lean on it for the `$get` / `$set` utilities injected into evaluated closures.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schemaComponent()` / `schemaContainer()`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) | [`Concerns/BelongsToSchemaComponent.php`](../../../vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php) | done | — | — |
| [`getSchemaComponent()` / `getSchemaContainer()`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) — the own binding, then the container's parent component, then the group | [`Concerns/BelongsToSchemaComponent.php`](../../../vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php) | done | — | — |
| [`getSchemaComponentOrFail()`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) | — | done | — | [diverges](#getschemacomponentorfail-throws-where-filament-fatals) |

## Divergences

### `getSchemaComponentOrFail()` throws where Filament fatals

`getSchemaComponent()` is nullable on both sides.
Filament dereferences it unguarded in the `$get` / `$set` resolver arms of `Action` and in the `$get` arm of `ActionGroup`:

```php
'schemaGet', 'get' => [$this->getSchemaComponent()->makeGetUtility()->skipComponentsChildContainersWhileSearching(false)],
```

An action evaluated outside a schema therefore dies with *Call to a member function makeGetUtility() on null*, naming neither the action nor the binding it wanted.

Sylament routes those arms, and the by-type `Get` / `Set` arms beside them, through `getSchemaComponentOrFail()`, which throws a `LogicException` naming the class and what the utilities need.
The failure mode is unchanged, since both stop the request on the same input, and an exception message is not API, so nothing a migrating user depends on moves.

The method exists because the unguarded dereference cannot pass PHPStan at level max without an inline `@phpstan-ignore`, and shipped `src/` carries none: [`StaticAnalysisSuppressionTest`](../../../packages/schemas/tests/Architecture/StaticAnalysisSuppressionTest.php) fails the suite on one.
A `LogicException` that says how to fix a missing required relationship is the house pattern, as in [`Modals::getLiveComponent()`](../../../packages/actions/src/Twig/Components/Modals.php), [`HasName`](../../../packages/actions/src/Concerns/HasName.php) and [`IconManager`](../../../packages/support/src/Icons/IconManager.php).
