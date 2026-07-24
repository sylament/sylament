# Component (support)

> **Example file.** The structure is what every detail file copies. The entries
> are illustrative, not a completed audit — re-derive them against the Filament
> source before trusting any line.

`Sylament\Support\Component\Component` — the abstract root every Sylament
component extends. The traits it composes are tracked as rows here rather than
in files of their own. `ViewComponent` (the Twig-rendering subclass) is
[its own file](view-component.md); so is
[`ComponentManager`](component-manager.md), which `Configurable` calls into.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Components/Component.php`](../../../vendor/filament/support/src/Components/Component.php)
  — the base class and the five traits it composes
- [`support/src/Concerns/`](../../../vendor/filament/support/src/Concerns)
  — the trait implementations behind each row below

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences). Rows never disappear — only their
detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Configurable`](../../../packages/support/src/Concerns/Configurable.php) | [`Concerns/Configurable.php`](../../../vendor/filament/support/src/Concerns/Configurable.php) | done | — | — |
| [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | in progress | — | [detail](#evaluatesclosures) · [diverges](#closure-resolution-lives-in-a-service) |
| Arity probe | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | blocked | [`CanOpenUrl`](../schemas/README.md) — its only consumer | [detail](#arity-probe) |
| Conditionable | [`Illuminate\Support\Traits\Conditionable`](../../../vendor/laravel/framework/src/Illuminate/Conditionable/Traits/Conditionable.php) | skipped | — | [detail](#conditionable-and-tappable) |
| Tappable | [`Illuminate\Support\Traits\Tappable`](../../../vendor/laravel/framework/src/Illuminate/Support/Traits/Tappable.php) | skipped | — | [detail](#conditionable-and-tappable) |
| Macroable | [`Concerns/Macroable.php`](../../../vendor/filament/support/src/Concerns/Macroable.php) | skipped | — | [diverges](#macroable-is-not-ported) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### EvaluatesClosures

In: `evaluate()`, the by-name and by-type resolver hooks, and
`$evaluationIdentifier` self-resolution — covered by
[`EvaluatesClosuresTest.php`](../../../packages/support/tests/Unit/Concerns/EvaluatesClosuresTest.php)
and [`InvokerTest.php`](../../../packages/support/tests/Unit/Service/InvokerTest.php).

Left: the arity probe, tracked as its own row above because it blocks
separately.

### Arity probe

`evaluationValueIsFunctionAndHasParameter()` — reports whether a closure
declares a given parameter, so a caller can decide what to pass. Filament's only
consumers are the `CanOpenUrl` concerns in schemas and table columns, neither of
which is ported, so there is nothing to verify it against yet. Port it in the
same change as the first consumer.

### Conditionable and Tappable

`when()` / `unless()` and `tap()` — the Laravel fluent-helper traits Filament's
base exposes for userland chains. No Symfony equivalent ships, so porting means
writing both. Ported once already and removed while nothing consumed them —
**skipped** rather than todo because nothing is waiting on them and we are not
scheduling the work; re-add when a component or consumer wants the fluent forms.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above: a row stays **done** or **skipped** and keeps
pointing here. Say what would reverse the decision when it is provisional.

### Macroable is not ported

No runtime method injection. The extension points are subclassing and
`configureUsing()`. Reversing this means accepting `__call()`-based APIs that
neither PHPStan nor an IDE can see.

### Closure resolution lives in a service

Filament inlines the reflection loop in `EvaluatesClosures`; we delegate to
[`Invoker`](../../../packages/support/src/Service/Invoker.php), reached through
the static-instance pattern so components need no constructor injection. Two
consequences worth their own note:

- **`ClosureParameter` replaces the array-wrapping hack.** Filament wraps
  resolver results in an array to tell "resolved null" from "not resolved"; we
  return a [`ClosureParameter`](../../../packages/support/src/Data/ClosureParameter.php)
  value object or `null`.
- **No container auto-wiring.** Filament falls back to `app()->make()` for any
  unbound class; Symfony's container cannot, so only types the container `has()`
  resolve. Anything else throws instead of being silently constructed.

_See also: the global [rename table](../renames.md) — namespace
singularization (`Support\Components\` → `Support\Component\`) is a systematic
translation recorded there, not a per-component divergence._
