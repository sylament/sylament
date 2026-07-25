# PropertyAccessor (schemas)

[`Sylament\Schemas\State\PropertyAccessor`](../../../packages/schemas/src/State/PropertyAccessor.php)
is the port's state backend — a Sylament invention with **no Filament
counterpart** (`vendor/filament/schemas/src/State/` does not exist). It is the
seam Filament's Livewire-entanglement state binding lands on once there is no
Livewire: it wraps Symfony's
[`PropertyAccessorInterface`](../../../vendor/symfony/property-access/PropertyAccessor.php)
to read and write a host UX LiveComponent's `data` bag by absolute dotted path,
and carries a detached-array trio (`getFromArray()` / `setInArray()` /
`forgetFromArray()`) that ports Laravel's `Arr::get` / `Arr::set` / `Arr::forget`
for the dehydration walk, which assembles state in a plain array before any host
exists. It is reached through the static-instance pattern
(`StaticInstanceInterface` + `HasStaticInstance`), so state concerns call
`PropertyAccessor::getInstance()` with no constructor injection — exactly as
`EvaluatesClosures` reaches the `Invoker`.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — this is Sylament-invented, so it is diffed against the sources it
deliberately mirrors rather than a Filament port target:

- [`ux-live-component/src/LiveComponentHydrator.php`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php)
  — `adjustPropertyPathForData`, which `toPropertyPath()` mirrors so the bag path
  shape matches what the hydrator round-trips
- [`laravel/framework/src/Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php)
  — `Arr::get` / `Arr::set` / `Arr::forget`, ported as the detached-array trio

## Surface

Every point of this class. `Ports` is `—` where the method has no upstream at all
(Sylament-invented mechanics); it links the mirrored vendor source where one
exists. _Blocked by_ names what has to land first. _Detail_ carries a
**diverges** link to the permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a host's state bag by absolute dotted path (null-tolerant on missing index) | — (Sylament-invented) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`set()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write a host's state bag by absolute dotted path (auto-vivifies nested arrays) | — (Sylament-invented) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`toPropertyPath()`](../../../packages/schemas/src/State/PropertyAccessor.php) — dotted path → `data[a][b]` bracket conversion | [`LiveComponentHydrator::adjustPropertyPathForData()`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`getFromArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a detached nested array by dotted path (null past a missing/scalar segment) | [`Arr::get()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`setInArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write into a detached nested array, auto-vivifying/clobbering intermediates | [`Arr::set()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`forgetFromArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — unset leaf path(s) from a detached array, leaving emptied parents | [`Arr::forget()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [Static-instance wiring](../../../packages/schemas/src/State/PropertyAccessor.php) — service registration, class alias, boot as the reachable singleton | — (Sylament-invented) | done | — | — |

Every row is **done** — the unit suite
([`PropertyAccessorTest`](../../../packages/schemas/tests/Unit/State/PropertyAccessorTest.php))
exercises all five public methods across their branches (dot-to-bracket nested
read and null-on-missing; single-segment and nested auto-vivify writes; the
detached trio's present/missing/past-scalar reads, deep auto-vivify and non-array
clobber, and leaf-only unset over a path list with a missing-path no-op), a boot
test
([`PropertyAccessorBootTest`](../../../packages/schemas/tests/Integration/State/PropertyAccessorBootTest.php))
proves the booted DI service is the same object `getInstance()` returns, and
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)
drives it end-to-end through a real UX LiveComponent hydrate/dehydrate round-trip
via `HasState`. No row has a write-up here.

## Divergences

### State binds through Symfony PropertyAccess, not Livewire entanglement

This file owns the divergence every other schemas state file points at. Filament
routes component state through Livewire's property system: `wire:model` entangles
a field to a nested Livewire property, and Livewire's own binding reads and writes
it. Sylament has no Livewire, so `PropertyAccessor` is the whole replacement
mechanism — it takes the UX LiveComponent host and an absolute dotted state path
(e.g. `data.address.city`), converts it via `toPropertyPath()` into a Symfony
property path whose first segment is the `data` bag property and whose remaining
segments are array indices (`data[address][city]`), and delegates to Symfony's
configured `PropertyAccessorInterface`. That conversion deliberately mirrors UX
`LiveComponentHydrator::adjustPropertyPathForData`, so the bag path shape matches
what the hydrator round-trips. The framework accessor is null-tolerant on missing
indices (read) and auto-vivifies nested arrays (write), so state concerns never
guard for absent keys — the ergonomics Livewire gave Filament for free. This is
structural and permanent: it reverses only if the port ever adopts a
Livewire-equivalent entanglement layer, which is not on the roadmap.

### The detached-array trio ports Laravel's Arr helpers because PropertyAccess is host-only and has no key removal

Alongside the host-bound `get()`/`set()`, the class carries
`getFromArray()`/`setInArray()`/`forgetFromArray()` — faithful ports of Laravel's
`Arr::get` / `Arr::set` / `Arr::forget` — that operate on a plain nested array by
dotted path. They exist because the dehydration walk in `HasState` assembles state
into a detached array before it reaches any host, so there is no object for the
Symfony `PropertyAccessorInterface` to bind against. `forgetFromArray` is
hand-written (a recursive leaf unset that leaves emptied parents in place, matching
`Arr::forget`, on which the dehydration walk's own explicit parent cleanup relies)
specifically because Symfony PropertyAccess offers no key-removal operation.
Keeping both families on one class concentrates all dotted-path state mechanics in
a single seam; splitting them would only be worth it if the detached-array logic
grew consumers outside the state loop.

_See also: the global [rename table](../renames.md) — the Livewire → UX
LiveComponent binding translation and the `Arr::*` → `getFromArray/setInArray/
forgetFromArray` idiom translation are systematic house translations recorded
there, not per-component divergences; the headings above are kept only for the
specific behavioral consequences (host-only accessor, no key removal) that
survive the translation._
