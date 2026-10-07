---
description: The registry behind configureUsing(), which runs each component's setUp() and its registered closures in class-hierarchy order.
ports:
  - vendor/filament/support/src/Components/ComponentManager.php
  - vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php
owns:
  - packages/support/src/Configuration/
---
# ComponentManager (support)

The [`Configurable`](../../../packages/support/src/Components/Concerns/Configurable.php) trait calls into [`Sylament\Support\Configuration\ComponentManager`](../../../packages/support/src/Configuration/ComponentManager.php): `X::configureUsing($closure)` registers a class-keyed closure, and `configure()`, which each component's `make()` calls, walks the component's class hierarchy root-first, calling `setUp()` at its declaring class's position, each class's registered closures after it, and `isImportant` closures last.
Components reach it through `resolve()`, the static-instance pattern, so they need no constructor injection.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`configureUsing()`](../../../packages/support/src/Configuration/ComponentManager.php) | [`ComponentManager::configureUsing()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | done | — | — |
| [`configure()`](../../../packages/support/src/Configuration/ComponentManager.php) | [`ComponentManager::configure()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | done | — | — |
| [`resolve()`](../../../packages/support/src/Configuration/ComponentManager.php) | [`ComponentManager::resolve()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | done | — | [diverges](#resolve-uses-the-static-instance-pattern-not-a-scoped-container) |
| `extractPublicMethods()` | [`ComponentManager::extractPublicMethods()`](../../../vendor/filament/support/src/Components/ComponentManager.php) | skipped | — | [diverges](view-component.md#extractpublicmethods-is-not-ported) |
| Scoped manager (`resolveScoped()` / `clone()`) | [`Contracts/ScopedComponentManager.php`](../../../vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php) | blocked | live-component-state | [detail](#the-scoped-component-manager-is-not-ported) |

## Details

### The scoped component manager is not ported

Filament binds a clone of the manager as a Laravel scoped service under `ScopedComponentManager` (`SupportServiceProvider`), so `configureUsing()` calls made during one request are discarded with that request's copy; `resolveScoped()` fetches the copy.
Sylament ships one static instance shared across the process, and ports neither `resolveScoped()`, `clone()` nor the `ScopedComponentManager` contract.
Per-request isolation is wanted, and it lands with the live-component-state work as Sylament's own per-request scope, not necessarily a `clone()`.

## Divergences

### `resolve()` uses the static-instance pattern, not a scoped container

Filament's `resolve()` reads Laravel's container: it returns the request-scoped manager when one is bound (`app()->resolved(ScopedComponentManager::class)`), otherwise it `singletonIf`s a shared `ComponentManager`.
Sylament's `resolve()` returns the booted static instance or lazily creates one through the [static-instance pattern](../../../packages/support/src/DependencyInjection/Concerns/HasStaticInstance.php) (`StaticInstance` + `HasStaticInstance`), the same object the bundle defines with `factory(ComponentManager::resolve(...))`.
The behavioural difference is the missing scope layer, the [blocked row](#the-scoped-component-manager-is-not-ported).

### The manager lives in `Configuration/`, not `Components/`

Filament files it under `Components/` beside `Component` and `ViewComponent`, which is where the class it configures lives, not where the manager belongs: it is not a component.
Sylament files an engine by its capability ([translations](../translations.md#namespace-segments)), so [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) pairs with [`Evaluation/Invoker`](../../../packages/support/src/Evaluation/Invoker.php) and [`Configurable`](../../../packages/support/src/Components/Concerns/Configurable.php) pairs with `Configuration/ComponentManager`.
Filament itself puts a manager at `src/` root when no directory fits (`CliManager`, `TimezoneManager`), so `Components/` is not a considered placement there either.
Under `Components/` it would also fall in the bundle's `Components/*.php` service glob ([`SylamentSupportBundle`](../../../packages/support/src/SylamentSupportBundle.php)), which registers component classes, beside the manager's own factory definition.
[`StaticInstancePass`](../../../packages/support/src/DependencyInjection/Compiler/StaticInstancePass.php) would tag both definitions, and `StaticInstanceRegistry` would call `setInstance()` twice with different objects.
