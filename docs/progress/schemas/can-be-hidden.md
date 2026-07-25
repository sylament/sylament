# CanBeHidden (schemas)

[`Sylament\Schemas\Components\Concerns\CanBeHidden`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php)
— the visibility trait: `hidden()` / `visible()` and their `isHidden()` /
`isVisible()` readers, plus `hiddenWhenAllChildComponentsHidden()`. It ports
Filament's identically-named base concern and is shared — the schemas base
[`Component`](component.md) and the actions
[`Action`](../../../packages/actions/src/Action.php) both `use` it — so it keeps
its own row rather than folding into `component.md`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Concerns/CanBeHidden.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php)
  — the visibility core, JS variants, operation/state gates, and the visibility cache

## Surface

Every point of this concern. _Blocked by_ names what has to land first. _Detail_
carries a **detail** link to the [Details](#details) write-up (deleted once the
row is **done**), a **diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`hidden()` / `visible()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`hidden()` / `visible()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | — |
| [`isHidden()` / `isVisible()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`isHidden()` / `isVisible()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | — |
| [`hiddenWhenAllChildComponentsHidden()`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`hiddenWhenAllChildComponentsHidden()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | — |
| `whenTruthy()` / `whenFalsy()` | [`whenTruthy()` / `whenFalsy()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | todo | — | [detail](#only-the-visibility-core-is-ported) |
| `hiddenOn()` / `visibleOn()` | [`hiddenOn()` / `visibleOn()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | blocked | operation concept | [diverges](#operation-scoped-and-state-path-visibility-are-not-ported) |
| `visibleJs()` / `hiddenJs()` (+ getters) | [`visibleJs()` …](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | blocked | live-component-state | [diverges](#js-visibility-variants-and-the-visibility-cache-are-not-ported) |
| static visibility cache | [`$visibilityCache` …](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | skipped | — | [diverges](#js-visibility-variants-and-the-visibility-cache-are-not-ported) |

## Details

### Only the visibility core is ported

`hidden()` / `visible()` / `isHidden()` / `isVisible()` and
`hiddenWhenAllChildComponentsHidden()` are ported and covered by
[`CanBeHiddenTest`](../../../packages/schemas/tests/Unit/Components/Concerns/CanBeHiddenTest.php)
(literal and closure `hidden`/`visible`, and the all-hidden / any-visible /
no-children branches of `hiddenWhenAllChildComponentsHidden`). **Left:**
`whenTruthy()` / `whenFalsy()` — a [`Get`](get.md) state read, unblocked and
merely not reached yet; `hiddenOn()` / `visibleOn()`, the JS variants, and the
visibility cache each carry a divergence below. **Next step:** port
`whenTruthy`/`whenFalsy` now; defer the rest to their prerequisites. Note
`hiddenWhenAllChildComponentsHidden()` walks `getChildSchemas()` without the
`withHidden` flag Filament passes — a knock-on of
[`HasChildComponents` dropping that parameter](component.md#getchildschemas-drops-the-withhidden-parameter);
behaviour matches under the current unfiltered traversal.

## Divergences

### JS visibility variants and the visibility cache are not ported

Filament ships `visibleJs()` / `hiddenJs()` / `getVisibleJs()` / `getHiddenJs()`
(client-side eval'd expressions) and a static visibility cache
(`isCachingVisibility`, `$visibilityCache`, `enable`/`disable`/`with`/`get`/`set`/
`has`). The JS variants are **blocked** on the live-component-state runtime — they
matter only under client-side DOM diffing, consumed by the unported
[`toSchemaHtml`](component.md#the-livewirealpine-render-wrapper-toschemahtml-is-not-ported)
wrapper. The cache is a render-time optimization the toolkit works without;
**skipped**, provisional — revisit if profiling shows repeated `evaluate()` cost
during a render pass.

### Operation-scoped and state-path visibility are not ported

`hiddenOn()` / `visibleOn()` gate on an operation string / instanceof — they need
the operation concept, not yet ported, so they are **blocked**. `whenTruthy()` /
`whenFalsy()` gate on a [`Get`](get.md) state read, which exists, so they are
merely **todo** (unblocked, not reached). All four are wanted for parity.
