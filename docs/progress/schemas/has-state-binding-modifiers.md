# HasStateBindingModifiers (schemas)

[`Sylament\Schemas\Components\Concerns\HasStateBindingModifiers`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php)
— the shared concern that lets a component declare *when* its state syncs to the
server: live-on-input, live-on-blur (`lazy`), debounced, or not at all. Both the
[`Schema`](../../../packages/schemas/src/Schema.php) and the schemas base
[`Component`](component.md) `use` it. It ports Filament's identically-named trait,
translating the Livewire `wire:model` modifier grammar into a single Symfony UX
LiveComponent `data-model` string — see [`property-accessor.md`](property-accessor.md)
for the state backend that string drives and [`has-schemas.md`](has-schemas.md)
for how a live component reaches its container.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/HasStateBindingModifiers.php`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php)
  — the single trait ported and split here (relocated to `Components/Concerns/`,
  a systematic namespace translation, not a rename)

## Surface

Every point of this concern. _Blocked by_ names what has to land first. _Detail_
carries a **detail** link to the [Details](#details) write-up (deleted once the
row is **done**), a **diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`live(onBlur, debounce, condition)`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`live()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | done | — | — |
| [`lazy()` → `on(change)` blur binding](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`lazy()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | done | — | — |
| [`debounce(delay)` → `debounce(ms)` binding](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`debounce()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | done | — | — |
| [`getDataModel()` UX `data-model` string builder](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`applyStateBindingModifiers()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | done | — | [diverges](#livewire-modifier-array-grammar-becomes-a-single-getdatamodel-data-model-string) |
| [`getLiveDebounce()`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`getLiveDebounce()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | done | — | — |
| [`reactive()` alias of `live()`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`reactive()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | in progress | — | [detail](#reactive-and-islivedebounced-are-ported-but-untested) |
| [`isLive()` / `isLiveOnBlur()` container fallback](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`isLive()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | in progress | — | [detail](#the-container-and-parent-fallback-arms-are-untested) · [diverges](#the-trait-carries-the-component-flavor-while-schema-overrides-the-getters) |
| [`isLiveDebounced()`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`isLiveDebounced()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | in progress | — | [detail](#reactive-and-islivedebounced-are-ported-but-untested) |
| `stateBindingModifiers()` / `applyStateBindingModifiers()` / `getStateBindingModifiers()` Livewire grammar | [`getStateBindingModifiers()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | skipped | — | [diverges](#livewire-modifier-array-grammar-becomes-a-single-getdatamodel-data-model-string) |
| `isLazy()` alias | [`isLazy()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | todo | — | [detail](#islazy-and-getnormalizedlivedebounce-are-not-yet-ported) |
| `getNormalizedLiveDebounce()` ms/s parser | [`getNormalizedLiveDebounce()`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | todo | — | [detail](#islazy-and-getnormalizedlivedebounce-are-not-yet-ported) |

## Details

The emit path is real: `live()` / `lazy()` / `debounce()` feeding `getDataModel()`
is ported and exercised end-to-end by
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php),
which renders a live host whose four fields span the modifier matrix and asserts
the emitted strings — `data.title` (bare live path), `norender|data.slug` (not
live, the default), `on(change)|data.heading` (blur), `debounce(250)|data.excerpt`
(debounce). That covers `live()`, `lazy()`, `debounce()`, `isLive()`,
`isLiveOnBlur()`, `getLiveDebounce()` and `getDataModel()`. The rows below are
what that coverage does not reach, plus two methods not yet ported.

### `reactive()` and `isLiveDebounced()` are ported but untested

Both exist and read correctly — `reactive()` calls `live()` with defaults;
`isLiveDebounced()` returns `false` on blur, `true` when a local debounce is set,
else delegates to the container. Neither is touched by `LiveStateTest`. **Next
step:** add a focused Unit test asserting `reactive()` produces the same binding
as `live()`, and `isLiveDebounced()` true/false across the blur / debounce / plain
cases.

### The container and parent fallback arms are untested

Every field in `LiveStateTest` sets its own `isLive`, so the delegation branch —
`$this->evaluate($this->isLive) ?? $this->getContainer()->isLive()` (and the same
`?? getParentComponent()` arm on [`Schema`](../../../packages/schemas/src/Schema.php))
— never runs. **Next step:** a Unit test with a field that sets no own `isLive`,
asserting it inherits `isLive` / `isLiveOnBlur` / `getLiveDebounce` from its
container. See [`has-schemas.md`](has-schemas.md) for the container wiring the
fallback walks.

### `isLazy()` and `getNormalizedLiveDebounce()` are not yet ported

Filament's trait also defines `isLazy()` (a bare alias of `isLiveOnBlur()`) and
`getNormalizedLiveDebounce()` (parses `'250ms'` / `'2s'` / numeric into an int of
milliseconds). Neither exists in Sylament. `isLazy()` is a trivial alias;
`getNormalizedLiveDebounce()` only matters once server-side debounce normalization
is needed. **Next step:** port both when a consumer first needs them — provisional,
not a non-goal.

## Divergences

### Livewire modifier-array grammar becomes a single `getDataModel()` data-model string

Filament exposes `applyStateBindingModifiers()` / `getStateBindingModifiers()`,
returning arrays like `['live', 'blur']` or `['live', 'debounce', $ms]` and
special-casing `$entangle(*)` expressions — all consumed by Blade `wire:model`.
Sylament drops that surface entirely (and with it the `$stateBindingModifiers`
array prop) and adds `getDataModel()`, which returns the ux-live-component modifier
grammar (`modifier|…|path`, path last): `norender|<path>` when not live,
`on(change)|<path>` for blur, `debounce(<ms>)|<path>` when debounced, else the bare
path — parsed by `live_controller.js` (`get_model_binding_default`). Note what
each modifier does client-side: `norender` suppresses the re-render but still
syncs state for the next submit, `on(change)` switches the trigger event to
blur/change, `debounce(<ms>)` overrides the default debounce, and a bare path
binds live on `input` with that default. This is the Livewire → UX LiveComponent
idiom translation, and it is stable: the two client runtimes have incompatible
binding grammars, so it will not reverse. `stateBindingModifiers()`,
`applyStateBindingModifiers()`, and `getStateBindingModifiers()` are therefore
intentionally absent (**skipped**), not pending.

### The trait carries the component flavor while `Schema` overrides the getters

Filament's single trait branches at runtime — `if ($this instanceof Component)
return $this->getContainer()->…; elseif ($this->getParentComponent()) …`. Sylament
splits it two-level instead: the trait's `isLive` / `isLiveOnBlur` /
`isLiveDebounced` / `getLiveDebounce` delegate to `getContainer()` (the component
flavor), and [`Schema`](../../../packages/schemas/src/Schema.php) overrides each to
delegate to `getParentComponent()`. This is the same two-level split
[`has-gap.md`](has-gap.md) and [`has-columns.md`](has-columns.md) record, and it
keeps each context statically typed with no `instanceof` sniff. It reverses only
if the two-level pattern is abandoned toolkit-wide.

_See also: the global [rename table](../renames.md) — relocating the trait from
Filament's `Concerns\` to `Components\Concerns\` is a systematic namespace
translation recorded there, not a per-component divergence._
