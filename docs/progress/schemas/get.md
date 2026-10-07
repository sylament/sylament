---
description: The `$get` callable a component hands its evaluated closures so they can read sibling and cross-container state by path.
ports:
  - vendor/filament/schemas/src/Components/Utilities/Get.php
owns:
  - packages/schemas/src/State/Get.php
---
# Get (schemas)

[`Sylament\Schemas\State\Get`](../../../packages/schemas/src/State/Get.php) — the `$get` callable a component hands its evaluated closures so they can read sibling and cross-container state by path.
`__invoke($path, $isAbsolute)` resolves the path against the component's own state, a sibling, or another container, falling back to a raw read off the live component through its [`PropertyAccessor`](property-accessor.md).
The path resolution itself (`resolveRelativeStatePath()`, `getComponentByStatePath()`) lives on the [`Component`](component.md) it wraps.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__invoke()` — own-state / sibling / cross-container resolution + raw-bag fallback](../../../packages/schemas/src/State/Get.php) | [`__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | done | — | — |
| Typed accessors `string()` / `integer()` / `float()` / `boolean()` / `array()` / `date()` / `enum()` | [`string()` …](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | todo | — | [detail](#typed-accessors-are-not-ported) |
| `filled()` / `blank()` convenience predicates | [`filled()` / `blank()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | todo | — | [detail](#typed-accessors-are-not-ported) |
| [`skipComponentsChildContainersWhileSearching(bool)` toggle](../../../packages/schemas/src/State/Get.php) | [`skipComponentsChildContainersWhileSearching()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | done | — | [diverges](#the-skip-child-containers-search-state-is-per-call-with-no-static-re-entrancy-stack) |

## Details

### Typed accessors are not ported

Filament layers thin readers over `__invoke`: the casting accessors `string`, `integer`, `float`, `boolean`, `array`, `date` and `enum`, and the predicates `filled` and `blank`.
Each takes the same `$key` / `$isAbsolute` pair, and each casting accessor also takes an `$isNullable` flag that short-circuits to `null` on a blank read before casting.
The port has `__invoke` and none of the readers.
Filament's `date()` returns `Carbon::parse($state)`, and no Sylament package requires Carbon, so its port needs a `DateTimeInterface` translation.
Filament types each nullable return as a PHPDoc conditional, `(TNullable is true ? ?T : T)`, which PHPStan reads.

## Divergences

### The skip-child-containers search state is per-call, with no static re-entrancy stack

Filament keeps a static `array<Component> $skipComponentsChildContainersWhileSearching`, pushes the current component onto it and pops it in a `finally` around the `getRootContainer()->getComponentByStatePath()` call, and passes `withHidden: true`, `withAbsoluteStatePath: true` and that stack (or `[]` when the `shouldSkipComponentsChildContainersWhileSearching` flag is off) as named arguments.
The port keeps the mutable flag and its mutator, [`skipComponentsChildContainersWhileSearching(bool $condition = true): static`](../../../packages/schemas/src/State/Get.php), parameter name included: it mutates `$this` and returns it.
`__invoke` passes `[$this->component]` or `[]` to the two-argument [`HasComponents::getComponentByStatePath(string, array)`](../../../packages/schemas/src/Concerns/HasComponents.php), so each `Get` skips only its own bound component rather than the chain of components whose nested `$get` calls are in flight.
The port's search takes absolute paths and includes hidden components by default, so it needs neither `withHidden` nor `withAbsoluteStatePath`.
The static stack returns if nested-repeater search, where re-entrancy makes it matter, is ported.
