# ComponentManager (support)

`Sylament\Support\Configuration\ComponentManager` — the registry behind `configureUsing()`.
The [`Configurable`](../../../packages/support/src/Components/Concerns/Configurable.php) trait calls into it: `X::configureUsing($closure)` registers a class-keyed closure, and every `make()` runs `configure()`, which walks the component's class hierarchy root-first — calling `setUp()` at its declaring class's position, each class's registered closures after it, and `isImportant` closures last.
It is the Filament `configureUsing()` port and is reached through the static-instance pattern (`resolve()`), so components need no constructor injection.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Components/ComponentManager.php`](../../../vendor/filament/support/src/Components/ComponentManager.php) — the manager itself
- [`support/src/Components/Contracts/ScopedComponentManager.php`](../../../vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php) — the contract Filament's manager implements, driving `resolveScoped()`

## Surface

Every point of this component.
_Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in [Details](#details), deleted once the row is **done**; **diverges** → the permanent note in [Divergences](#divergences).
Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`configureUsing()`](../../../packages/support/src/Configuration/ComponentManager.php) | [`ComponentManager::configureUsing()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | done | — | — |
| [`configure()`](../../../packages/support/src/Configuration/ComponentManager.php) | [`ComponentManager::configure()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | done | — | — |
| [`resolve()`](../../../packages/support/src/Configuration/ComponentManager.php) | [`ComponentManager::resolve()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | done | — | [diverges](#resolve-uses-the-static-instance-pattern-not-a-scoped-container) |
| `extractPublicMethods()` | [`ComponentManager::extractPublicMethods()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | skipped | — | [diverges](#extractpublicmethods-is-not-ported) |
| Scoped manager (`resolveScoped()` / `clone()`) | [`Contracts/ScopedComponentManager.php`](../../../vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php) | blocked | live-component-state work | [detail](#the-scoped-component-manager-is-not-ported) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and what the next step is.
Delete a subsection when its row flips to **done**; the table row and any divergence it earned stay behind.

### The scoped component manager is not ported

Filament clones the manager per Livewire request via `clone()` and binds the copy under `ScopedComponentManager`, so `configureUsing()` calls made during one request don't leak into the next; `resolveScoped()` fetches that per-request copy.
Sylament ships a single static instance shared across the process — neither `resolveScoped()`, `clone()`, nor the `ScopedComponentManager` contract is ported yet.
Per-request config isolation is wanted, but it belongs to the live-component-state work, not this class in isolation — so the row is **blocked** on that work: it lands there as Sylament's own per-request scope (not necessarily a `clone()`).
The `ScopedComponentManager` contract is separately tracked as **todo** in the [package README](README.md).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above: a row stays **done** or **skipped** and keeps pointing here.
Say what would reverse the decision when it is provisional.

### `resolve()` uses the static-instance pattern, not a scoped container

Filament's `resolve()` reads Laravel's container: it returns the request-scoped manager when one is bound (`app()->resolved(ScopedComponentManager::class)`), otherwise `singletonIf`s a shared `ComponentManager`.
Sylament has no such container idiom, so `resolve()` returns the booted static instance or lazily creates one via the [static-instance pattern](../../../packages/support/src/DependencyInjection/Concerns/HasStaticInstance.php) (`StaticInstance` + `HasStaticInstance`) — the same object the bundle defines with `factory(ComponentManager::resolve(...))`.
The behavioral delta is the missing scope layer, covered by its own **blocked** row in [Details](#the-scoped-component-manager-is-not-ported).

### The manager lives in `Configuration/`, not `Components/`

Filament files it under `Components/` beside `Component` and `ViewComponent`, which is where the class it configures lives rather than where the class itself belongs — it is not a component.
Sylament names engine directories after the capability, so [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) pairs with [`Evaluation/Invoker`](../../../packages/support/src/Evaluation/Invoker.php) and [`Configurable`](../../../packages/support/src/Components/Concerns/Configurable.php) pairs with `Configuration/ComponentManager`.
Filament itself puts a manager at `src/` root when no directory fits (`CliManager`, `TimezoneManager`), so `Components/` is not a considered placement there either.

The move is also what makes the `Components/*.php` service glob honest: it exists to register component classes, and a non-component sitting at the top of that directory forced an `exclude()`.
That exclude was load-bearing, not cosmetic — without it the glob registered a second definition of the same class, [`StaticInstancePass`](../../../packages/support/src/DependencyInjection/Compiler/StaticInstancePass.php) tagged both, and `StaticInstanceRegistry` called `setInstance()` twice with different objects.
`Configuration/` removes the need for the exclude rather than the need to think about it.
`ScopedComponentManager` lands here too if the [blocked row](#the-scoped-component-manager-is-not-ported) ever ships.

### `extractPublicMethods()` is not ported

Filament's manager exposes a component's public, non-fluent methods to its Blade view (`{{ $getHeading() }}`) by reflecting them into a name→bound-closure map, cached in a `methodCache` property.
Sylament's [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) renders through Twig with the instance itself in context, so templates call `component.getHeading()` directly — no method extraction is needed.
Both `extractPublicMethods()` and the `methodCache` property that backed it are therefore dropped.
Reversible only if a Twig-side reason to expose bound closures by name appears.
