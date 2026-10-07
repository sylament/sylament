---
description: 'The schemas visibility trait: `hidden()` / `visible()` and their readers, the `*On()` operation forms and `hiddenWhenAllChildComponentsHidden()`.'
ports:
  - vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php
owns:
  - packages/schemas/src/Components/Concerns/CanBeHidden.php
---
# CanBeHidden (schemas)

[`Sylament\Schemas\Components\Concerns\CanBeHidden`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) is composed by the schemas base [`Component`](component.md).
`Action` and `ActionGroup` compose the actions package's own `Sylament\Actions\Concerns\CanBeHidden`, as Filament's compose `Filament\Actions\Concerns\CanBeHidden`, and the tables columns, filters and summarizers each compose a tables trait of the same name.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`hidden()` / `visible()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`hidden()` / `visible()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | — |
| [`isHidden()` / `isVisible()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`isHidden()` / `isVisible()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | — |
| [`hiddenWhenAllChildComponentsHidden()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`hiddenWhenAllChildComponentsHidden()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | [diverges](component.md#getchildschemas-drops-the-withhidden-parameter) |
| `whenTruthy()` / `whenFalsy()` | [`whenTruthy()` / `whenFalsy()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | todo | — | [detail](#whentruthy-and-whenfalsy-have-every-prerequisite) |
| [`hiddenOn()` / `visibleOn()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`hiddenOn()` / `visibleOn()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | [semantics](#arrwrap-maps-to-the-wrap-helper-never-an-array-cast) |
| `visibleJs()` / `hiddenJs()` (+ getters) | [`visibleJs()` …](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | blocked | live-component-state | [detail](#the-js-visibility-variants-wait-on-the-live-component-state-runtime) |
| static visibility cache | [`$visibilityCache` …](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | skipped | — | [diverges](#the-static-visibility-cache-is-not-ported) |

## Details

### `whenTruthy()` and `whenFalsy()` have every prerequisite

Both register a `hidden()` closure that reads paths through [`Get`](get.md), which the port injects, so nothing blocks them.

### The JS visibility variants wait on the live-component-state runtime

Filament's `visibleJs()` / `hiddenJs()` / `getVisibleJs()` / `getHiddenJs()` carry client-side expressions that only its Alpine wrappers read: `Component::toSchemaHtml()`, and the per-child wrappers in `Tabs` and `Flex`.
The client half of the port's render wrapper waits on the same runtime ([component.md](component.md#the-render-wrappers-client-half-waits-on-the-live-component-state-runtime)).

## Semantics

### `Arr::wrap` maps to the `wrap()` helper, never an `(array)` cast

`hiddenOn()` / `visibleOn()` accept one operation or a list, and Filament normalizes with `Arr::wrap()`, whose port is support's free function [`wrap()`](../support/helpers.md).
The shorter `(array) $operations` would be wrong: the parameter also accepts an [`Operation`](../support/enums.md) enum case, and casting an object to an array dumps its properties (`['name' => 'Edit', 'value' => 'edit']`) instead of wrapping it, a silent mismatch rather than an error.
The enum case is unwrapped to its `->value` inside the loop; everything else, including the `$liveComponent instanceof $candidate` host-class arm, is Filament's logic verbatim (`instanceof` against a non-class string like `'create'` is `false`, not an error).
`disabledOn()` in [can-be-disabled.md](can-be-disabled.md) has the same body.

## Divergences

### The static visibility cache is not ported

Filament's `isHidden()` consults a static cache keyed by component key and closure id when `isVisibilityCacheEnabled()` is true, managed by `enableVisibilityCache()`, `disableVisibilityCache()`, `withVisibilityCache()`, `getVisibilityCacheValue()`, `setVisibilityCacheValue()` and `hasVisibilityCacheKey()`.
The port's `isHidden()` evaluates `isHidden` and then `isVisible` on every call, which is Filament's uncached branch.
The cache is a render-time optimization the toolkit works without; port it if a profile shows repeated `evaluate()` cost during a render pass.
