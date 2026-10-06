# Component (support)

`Sylament\Support\Components\Component` — the abstract root every Sylament
component extends. It composes two of the five traits Filament's base composes;
all five are rows below, tracked here rather than in files of their own. The
Twig-rendering subclass `ViewComponent` and the `ComponentManager` that
`Configurable` calls into have their own rows in the [package README](README.md)
and earn detail files of their own when they are audited.

**Status:** done — see the
[status vocabulary](../README.md#statuses). Every row below is settled: four
**done** and the three Laravel traits **skipped**, the last of them — the arity
probe — flipped by [`Column::hasStateBasedUrls()`](../tables/column.md),
the reader that earned it. The table carries no unaudited row, so *done* here
is the whole base component, not the two subclasses: `ViewComponent` and
`ComponentManager` keep their own rows in the [package README](README.md).

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
| [`Configurable`](../../../packages/support/src/Components/Concerns/Configurable.php) | [`Concerns/Configurable.php`](../../../vendor/filament/support/src/Concerns/Configurable.php) | done | — | — |
| [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | done | — | [diverges](#closure-resolution-lives-in-a-service) · [diverges](#evaluated-flag-getters-are-not-re-cast-to-bool) |
| [`newInstance()`](../../../packages/support/src/Components/Component.php) | `make()`'s `app(static::class, …)` call, inlined | done | — | [diverges](#newinstance-routes-every-make-through-a-locator) |
| [Arity probe](../../../packages/support/src/Concerns/EvaluatesClosures.php) | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | done | — | — |
| Conditionable | [`Illuminate\Support\Traits\Conditionable`](../../../vendor/laravel/framework/src/Illuminate/Conditionable/Traits/Conditionable.php) | skipped | — | [diverges](#conditionable-and-tappable-are-not-ported) |
| Tappable | [`Illuminate\Support\Traits\Tappable`](../../../vendor/laravel/framework/src/Illuminate/Support/Traits/Tappable.php) | skipped | — | [diverges](#conditionable-and-tappable-are-not-ported) |
| Macroable | [`Concerns/Macroable.php`](../../../vendor/filament/support/src/Concerns/Macroable.php) | skipped | — | [diverges](#macroable-is-not-ported) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

No row has a write-up here.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above: a row stays **done** or **skipped** and keeps
pointing here. Say what would reverse the decision when it is provisional.

### Closure resolution lives in a service

Filament inlines the reflection loop in `EvaluatesClosures`; we delegate to
[`Invoker`](../../../packages/support/src/Evaluation/Invoker.php), injected by a
`#[Required]` setter on the trait so components need no constructor injection.
Three consequences worth their own note:

- **`ClosureParameter` replaces the array-wrapping hack.** Filament wraps
  resolver results in an array to tell "resolved null" from "not resolved"; we
  return a [`ClosureParameter`](../../../packages/support/src/Evaluation/ClosureParameter.php)
  value object or `null`.
- **No container auto-wiring.** Filament falls back to `app()->make()` for any
  unbound class; Symfony's container cannot, so only types the container `has()`
  resolve. Anything else throws instead of being silently constructed.
- **A `new`-built component resolves no services into closures.** `getInvoker()`
  falls back to an `Invoker` holding an empty container when the setter never
  ran, so `fn (MailerInterface $mailer) => …` resolves through `make()` on a
  registered component and not on one constructed with `new`. Accepted rather
  than overlooked: it extends the rule that a subclass needing DI must be a
  registered service, and it is what lets the Unit bucket — which already ran
  every test against an empty-container `Invoker` — evaluate closures with no
  kernel. The gap is closable by handing the resolver an `Invoker` for its
  `new $class()` branch, if a real case ever appears.

### Conditionable and Tappable are not ported

`when()` / `unless()` and `tap()` — the Laravel fluent-helper traits Filament's
base composes for userland chains. No Symfony equivalent ships, so porting means
writing both (and their `HigherOrder*Proxy` helpers). Nothing in the port, or
any consumer, currently wants the fluent forms, so they are **skipped** —
reversible the moment a component or consumer does. Unlike Macroable, this is a
deferral, not a principled rejection.

### Macroable is not ported

No runtime method injection. The extension points are subclassing and
`configureUsing()`. Reversing it would mean `__call()`-based APIs, which
[principle §1](../../internals/principles.md#the-test) rejects.

### `newInstance()` routes every `make()` through a locator

`Component::newInstance()` is the single construction seam every `make()`
factory calls instead of `new static()`. It delegates to
[`ComponentResolver::instantiate()`](../../../packages/support/src/DependencyInjection/ComponentResolver.php),
reached through the static-instance pattern (see "Static instance pattern" in
[`docs/internals/php.md`](../../internals/php.md#static-instance-pattern)) so components need no constructor injection
of their own to get there.

- **`sylament.component` autoconfiguration.** `SylamentSupportBundle::build()`
  calls `registerForAutoconfiguration(Component::class)->addTag('sylament.component')`,
  so any `Component` subclass the application registers as a service — directly,
  or by falling under an `autoconfigure: true` PSR-4 resource — is tagged
  automatically, with no per-class opt-in.
- **`setShared(false)`.** [`ComponentResolverPass`](../../../packages/support/src/DependencyInjection/Compiler/ComponentResolverPass.php)
  marks every tagged definition non-shared before collecting it, so each
  locator `get()` returns a fresh, fully autowired instance — components carry
  per-instance configuration and must never be shared, the same reasoning
  Symfony UX's `TwigComponentPass` applies to live components.
- **Pass registration order.** `build()` adds the pass with a plain
  `addCompilerPass()`, which lands it at `TYPE_BEFORE_OPTIMIZATION` priority 0 —
  *after* the priority-100 group holding `ResolveInstanceofConditionalsPass`,
  which is what actually applies the autoconfiguration tag the pass reads.
  Registering it at a higher priority would run it before any definition
  carries `sylament.component`, and the locator would come out empty.
- **The class-keyed locator.** The pass builds a `ServiceLocator` keyed by each
  tagged definition's class and injects it into
  `sylament_support.component_resolver`'s `$components` argument, mirroring
  Symfony Form's `FormPass`. `ComponentResolver::instantiate()` checks
  `$components->has($class)` and, when true, resolves through the locator —
  this is the only path that can satisfy a subclass's own constructor
  dependencies, including **private** services.
- **The `new $class()` fallback.** When the container doesn't know the class,
  `instantiate()` falls back to direct construction. Sylament's own components
  no longer take this path — each bundle PSR-4-loads its `Components/`
  directory in `loadExtension()`, so `Section`, `TextInput`, `Action` and the
  rest are container-built and autowired like any app component. What remains
  is a user subclass nobody registered, and any kernel-free Unit test, where
  `ComponentResolver::resolve()` lazily creates an instance with no
  `$components` locator at all (`ComponentManager::resolve()` parity). A
  subclass that declares constructor dependencies only works through `make()`
  once it is registered as a service; an unregistered subclass with a
  constructor throws the ordinary "too few arguments" error `new $class()`
  produces.

One consequence worth flagging for future porting: **a class whose
constructor is removed must give any previously-constructor-assigned typed
property a default.** `Html::$content` and `Text::$content` used to be
assigned by a now-deleted constructor; without a default, a typed property
left unassigned between `new $class()` and its first setter call violates
PHP's typed-property contract. Both now default to `null`.

### Evaluated flag getters are not re-cast to bool

Filament wraps every evaluated flag in a `(bool)` cast — `hasGap()`, `isDense()`,
`isCollapsed()`, `isCollapsible()` each `return (bool) $this->evaluate(...)`.
Sylament returns the evaluated value directly against the getter's native `bool`
return type, dropping the cast wherever a flag concern is ported (schemas'
[`HasGap`](../../../packages/schemas/src/Components/Concerns/HasGap.php) and
[`CanBeCollapsed`](../../../packages/schemas/src/Components/Concerns/CanBeCollapsed.php)
today). Under `strict_types=1` a closure returning `1` or `'yes'` therefore
`TypeError`s here where Filament silently coerces it — one policy applied to every
flag getter as it lands, resting on `evaluate()` returning its value untouched.
