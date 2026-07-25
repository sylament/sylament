# BelongsToLiveComponent (schemas)

[`Sylament\Schemas\Concerns\BelongsToLiveComponent`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php)
— the trait that binds an object to the UX LiveComponent host it lives on: a
nullable `liveComponent` property, the `liveComponent()` setter, and the
`getLiveComponent()` accessor. It ports Filament's `BelongsToLivewire` (renamed
under the Livewire → UX LiveComponent idiom translation) and is shared — both the
[`Schema`](schema.md) root and the actions
[`Action`](../../../packages/actions/src/Action.php) `use` it — so it keeps its
own row rather than folding into either.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/BelongsToLivewire.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToLivewire.php)
  — the property, setter, and getter ported here

## Surface

Every point of this trait. _Detail_ carries a **diverges** link to the permanent
note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`liveComponent()`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) | [`livewire()`](../../../vendor/filament/schemas/src/Concerns/BelongsToLivewire.php) | done | — | — |
| [`getLiveComponent()`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) | [`getLivewire()`](../../../vendor/filament/schemas/src/Concerns/BelongsToLivewire.php) | done | — | [diverges](#getlivecomponent-throws-instead-of-returning-a-possibly-null-component) |

Both rows are **done** — the setter (nullable, so it also detaches) and the
throwing accessor are covered directly by
[`BelongsToLiveComponentTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToLiveComponentTest.php)
(default throw, set-and-return, chaining, clear-and-re-throw), so neither has a
write-up here.

## Divergences

### `getLiveComponent()` throws instead of returning a possibly-null component

Filament types `getLivewire(): Component & HasSchemas` but returns the nullable
`$livewire` property directly, letting a `null` propagate downstream. Sylament's
`getLiveComponent()` throws `RuntimeException('Schema is not part of
LiveComponent')` when unbound — a deliberate fail-fast matching the
no-silent-fallback house rule. The nullable property stays readable through the
closure-injection path (which reads the raw property, so a standalone schema
still evaluates closures with `$liveComponent = null` rather than throwing). The
property type also collapses Filament's `Livewire\Component & HasSchemas`
intersection to the bare host contract
[`InteractsWithSchemas`](interacts-with-schemas.md): UX LiveComponents have no
marker base class to intersect with.

_See also: the global [rename table](../renames.md) — `BelongsToLivewire` →
`BelongsToLiveComponent`, `livewire()` → `liveComponent()`, and `getLivewire()`
→ `getLiveComponent()` are the Livewire → UX LiveComponent idiom translation
recorded there, not per-component divergences._
