# Component (support)

`Sylament\Support\Component\Component` — the abstract root every Sylament
component extends. It composes two of the five traits Filament's base composes;
all five are rows below, tracked here rather than in files of their own. The
Twig-rendering subclass `ViewComponent` and the `ComponentManager` that
`Configurable` calls into have their own rows in the [package README](README.md)
and earn detail files of their own when they are audited.

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
| [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | done | — | [diverges](#closure-resolution-lives-in-a-service) · [diverges](#evaluated-flag-getters-are-not-re-cast-to-bool) |
| Arity probe | [`Concerns/EvaluatesClosures.php`](../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) | blocked | state-based-URL consumer | [detail](#the-arity-probe-is-deferred) |
| Conditionable | [`Illuminate\Support\Traits\Conditionable`](../../../vendor/laravel/framework/src/Illuminate/Conditionable/Traits/Conditionable.php) | skipped | — | [diverges](#conditionable-and-tappable-are-not-ported) |
| Tappable | [`Illuminate\Support\Traits\Tappable`](../../../vendor/laravel/framework/src/Illuminate/Support/Traits/Tappable.php) | skipped | — | [diverges](#conditionable-and-tappable-are-not-ported) |
| Macroable | [`Concerns/Macroable.php`](../../../vendor/filament/support/src/Concerns/Macroable.php) | skipped | — | [diverges](#macroable-is-not-ported) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The arity probe is deferred

`evaluationValueIsFunctionAndHasParameter()` — reports whether a closure declares
a given parameter — is the one method of Filament's `EvaluatesClosures` we leave
out. Its only callers are `hasStateBasedUrls()` on the **schemas** and **tables**
`CanOpenUrl` concerns
([schemas](../../../vendor/filament/schemas/src/Components/Concerns/CanOpenUrl.php),
[tables](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php)),
which ask whether a `url` closure wants a `state` argument. Neither is ported yet,
and the `actions` `CanOpenUrl` we did port drops state-based URLs for now — so
nothing consumes the probe today. State-based URLs are on the 1.0 roadmap, so the
probe is **blocked** on their first consumer, not a non-goal: it returns in the
same change as the `schemas` / `tables` `CanOpenUrl` port, where it finally has
behavior to test against.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above: a row stays **done** or **skipped** and keeps
pointing here. Say what would reverse the decision when it is provisional.

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

### Conditionable and Tappable are not ported

`when()` / `unless()` and `tap()` — the Laravel fluent-helper traits Filament's
base composes for userland chains. No Symfony equivalent ships, so porting means
writing both (and their `HigherOrder*Proxy` helpers). Nothing in the port, or
any consumer, currently wants the fluent forms, so they are **skipped** —
reversible the moment a component or consumer does. Unlike Macroable, this is a
deferral, not a principled rejection.

### Macroable is not ported

No runtime method injection. The extension points are subclassing and
`configureUsing()`. Reversing this means accepting `__call()`-based APIs that
neither PHPStan nor an IDE can see.

### Evaluated flag getters are not re-cast to bool

Filament wraps every evaluated flag in a `(bool)` cast — `hasGap()`, `isDense()`,
`isCollapsed()`, `isCollapsible()` each `return (bool) $this->evaluate(...)`.
Sylament returns the evaluated value directly against the getter's native `bool`
return type, dropping the cast wherever a flag concern is ported (schemas'
[`HasGap`](../../../packages/schemas/src/Concerns/HasGap.php) and
[`CanBeCollapsed`](../../../packages/schemas/src/Components/Concerns/CanBeCollapsed.php)
today). Under `strict_types=1` a closure returning `1` or `'yes'` therefore
`TypeError`s here where Filament silently coerces it — one policy applied to every
flag getter as it lands, resting on `evaluate()` returning its value untouched.

_See also: the global [rename table](../renames.md) — namespace
singularization (`Support\Components\` → `Support\Component\`) is a systematic
translation recorded there, not a per-component divergence._
