---
description: The abstract root every Sylament component extends, with its closure evaluation and the construction seam every `make()` goes through.
ports:
  - vendor/filament/support/src/Components/Component.php
  - vendor/filament/support/src/Concerns/Configurable.php
  - vendor/filament/support/src/Concerns/EvaluatesClosures.php
  - vendor/filament/support/src/Concerns/Macroable.php
owns:
  - packages/support/src/Components/Component.php
  - packages/support/src/Components/Concerns/Configurable.php
  - packages/support/src/Concerns/EvaluatesClosures.php
  - packages/support/src/Evaluation/ClosureParameter.php
  - packages/support/src/Evaluation/Invoker.php
---
# Component (support)

`Sylament\Support\Components\Component` is the abstract root every Sylament component extends.
Its Twig-rendering subclass is [`ViewComponent`](view-component.md), and the manager `Configurable` calls into is [`ComponentManager`](component-manager.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Configurable`](../../../packages/support/src/Components/Concerns/Configurable.php) | [`Concerns/Configurable.php`](../../../vendor/filament/support/src/Concerns/Configurable.php) | done | — | — |
| [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | done | — | [diverges](#closure-resolution-lives-in-a-service) · [diverges](#evaluated-flag-getters-are-not-re-cast-to-bool) |
| [`evaluationValueIsFunctionAndHasParameter()`](../../../packages/support/src/Concerns/EvaluatesClosures.php) | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | done | — | — |
| [`Invoker`](../../../packages/support/src/Evaluation/Invoker.php) and [`ClosureParameter`](../../../packages/support/src/Evaluation/ClosureParameter.php) | the reflection loop inside `EvaluatesClosures::evaluate()` | done | — | [diverges](#closure-resolution-lives-in-a-service) |
| [`newInstance()`](../../../packages/support/src/Components/Component.php) | `make()`'s `app(static::class, …)` call, inlined | done | — | [diverges](#newinstance-routes-every-make-through-a-locator) · [diverges](#make-arguments-move-from-constructors-to-setters) · [diverges](#components-declare-no-final-constructor) |
| Conditionable | [`Illuminate\Support\Traits\Conditionable`](../../../vendor/laravel/framework/src/Illuminate/Conditionable/Traits/Conditionable.php) | skipped | — | [diverges](#conditionable-and-tappable-are-not-ported) |
| Tappable | [`Illuminate\Support\Traits\Tappable`](../../../vendor/laravel/framework/src/Illuminate/Support/Traits/Tappable.php) | skipped | — | [diverges](#conditionable-and-tappable-are-not-ported) |
| Macroable | [`Concerns/Macroable.php`](../../../vendor/filament/support/src/Concerns/Macroable.php) | skipped | — | [diverges](#macroable-is-not-ported) |

## Divergences

### Closure resolution lives in a service

Filament inlines the reflection loop in `EvaluatesClosures`; the port delegates to [`Invoker`](../../../packages/support/src/Evaluation/Invoker.php), injected by a `#[Required]` setter on the trait so components need no constructor injection.
Three consequences follow:

- **`ClosureParameter` replaces the array-wrapping hack.**
  Filament wraps resolver results in an array to tell "resolved null" from "not resolved"; the port returns a [`ClosureParameter`](../../../packages/support/src/Evaluation/ClosureParameter.php) value object or `null`.
- **No container auto-wiring.**
  Filament falls back to `app()->make()` for any unbound class; Symfony's container cannot, so only types the container `has()` resolve.
  Anything else falls to the parameter's default, to `null` when it is optional or nullable, and otherwise throws instead of being silently constructed.
- **A `new`-built component resolves no services into closures.**
  `getInvoker()` falls back to an `Invoker` holding an empty container when the setter never ran, so `fn (MailerInterface $mailer) => …` resolves through `make()` on a registered component and not on one constructed with `new`.
  It extends the rule that a subclass needing DI must be a registered service, and it is what lets a Unit test evaluate closures with no kernel.
  The gap is closable by handing the resolver an `Invoker` for its `new $class()` branch, if a real case appears.

### Conditionable and Tappable are not ported

`when()` / `unless()` and `tap()` are the Laravel fluent-helper traits Filament's base composes for userland chains.
No Symfony equivalent ships, so porting means writing both, with their `HigherOrder*Proxy` helpers.
Nothing in the port or any consumer wants the fluent forms, so they are skipped, reversible the moment a component or consumer does.
Unlike Macroable, this is a deferral, not a principled rejection.

### Macroable is not ported

The port has no runtime method injection.
The extension points are subclassing and `configureUsing()`.
Reversing it would mean `__call()`-based APIs, which [principle §1](../../internals/principles.md#the-test) rejects.

### `newInstance()` routes every `make()` through a locator

`Component::newInstance()` is the single construction seam every `make()` factory calls instead of `new static()`.
It delegates to [`ComponentResolver::instantiate()`](../../../packages/support/src/DependencyInjection/ComponentResolver.php), reached through the [static-instance pattern](../../internals/php.md#static-instance-pattern), so components need no constructor injection of their own to get there.

- **`sylament.component` autoconfiguration.**
  `SylamentSupportBundle::build()` calls `registerForAutoconfiguration(Component::class)->addTag('sylament.component')`, so any `Component` subclass the application registers as a service, directly or under an `autoconfigure: true` PSR-4 resource, is tagged with no per-class opt-in.
- **`setShared(false)`.**
  [`ComponentResolverPass`](../../../packages/support/src/DependencyInjection/Compiler/ComponentResolverPass.php) marks every tagged definition non-shared before collecting it, so each locator `get()` returns a fresh, fully autowired instance: components carry per-instance configuration and must never be shared, the reasoning Symfony UX's `TwigComponentPass` applies to its components.
- **Pass registration order.**
  `build()` adds the pass with a plain `addCompilerPass()`, which lands it at `TYPE_BEFORE_OPTIMIZATION` priority 0, after the priority-100 group holding `ResolveInstanceofConditionalsPass`, which is what applies the autoconfiguration tag the pass reads.
  Registering it at a higher priority would run it before any definition carries `sylament.component`, and the locator would come out empty.
- **The class-keyed locator.**
  The pass builds a service locator keyed by each tagged definition's class and injects it into `sylament_support.component_resolver`'s `$components` argument, mirroring Symfony Form's `FormPass`.
  `ComponentResolver::instantiate()` checks `$components->has($class)` and, when true, resolves through the locator, the only path that can satisfy a subclass's own constructor dependencies, including private services.
- **The `new $class()` fallback.**
  When the container does not know the class, `instantiate()` falls back to direct construction.
  Sylament's own components do not take this path, because each bundle registers its shipped components as autowired, autoconfigured services in `loadExtension()`, through a PSR-4 load (schemas' and forms' `Components/`, tables' `Columns/`, `Filters/` and `Grouping/`) or an explicit `set()` (`Schema`, `Table`, actions' classes, `Notification`).
  What takes it is a user subclass nobody registered, and any kernel-free Unit test, where `ComponentResolver::resolve()` lazily creates an instance with no `$components` locator at all (`ComponentManager::resolve()` parity).
  A subclass that declares constructor dependencies only works through `make()` once it is registered as a service; an unregistered subclass with a constructor throws the ordinary "too few arguments" error `new $class()` produces.
- **A typed property a `make()` argument fills carries a default.**
  `new $class()` runs no constructor, so a typed property stays unassigned between construction and its first setter call, which PHP's typed-property contract rejects on read; `Html::$content` and `Text::$content` default to `null` for that reason.

### `make()` arguments move from constructors to setters

Filament passes `make()` arguments through `app(static::class, ['name' => $name])`, letting the container fill a constructor parameter by name.
Symfony's container cannot pass constructor arguments through a service-locator lookup, so `make()` resolves the instance first, through [`newInstance()`](#newinstance-routes-every-make-through-a-locator), and applies the value through the existing setter instead.
The setter still runs before `configure()`, so `setUp()` sees the value as it does in Filament.

### Components declare no `final` constructor

Filament seals `Action::__construct(?string $name)` `final`, and its siblings such as `ImportColumn`, `ExportColumn`, `NavigationItem` and `NavigationGroup` likewise.
Sylament declares no constructor on `Action` or `Component`, so a user subclass can declare its own constructor dependencies, autowired through [`ComponentResolver`](#newinstance-routes-every-make-through-a-locator).
That is the point of routing `make()` through the locator.

### Evaluated flag getters are not re-cast to bool

Filament wraps its evaluated flags in a `(bool)` cast: `hasGap()`, `isDense()`, `isCollapsed()` and `isCollapsible()` each return `(bool)` of the evaluated value.
Sylament returns the evaluated value directly against the getter's native `bool` return type, dropping the cast wherever a flag concern is ported, as in schemas' [`HasGap`](../../../packages/schemas/src/Components/Concerns/HasGap.php) and [`CanBeCollapsed`](../../../packages/schemas/src/Components/Concerns/CanBeCollapsed.php).
Under `strict_types=1` a closure returning `1` or `'yes'` therefore throws a `TypeError` where Filament silently coerces it: one policy applied to every flag getter as it lands, resting on `evaluate()` returning its value untouched.
