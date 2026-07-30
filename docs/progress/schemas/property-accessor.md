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
exists. State concerns reach it through
[`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php),
composed onto `Schema` and the schemas `Component` base — see
[the divergence below](#the-accessor-is-injected-not-reached-for).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — this is Sylament-invented, so it is diffed against the sources it
deliberately mirrors rather than a Filament port target:

- [`ux-live-component/src/LiveComponentHydrator.php`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php)
  — `adjustPropertyPathForData`, whose root-property-then-array-indices bag model
  `get()`/`set()` follow so the paths they touch match what the hydrator
  round-trips
- [`laravel/framework/src/Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php)
  — `Arr::get` / `Arr::set` / `Arr::forget`, ported as the detached-array trio

## Surface

Every point of this class. `Ports` is `—` where the method has no upstream at all
(Sylament-invented mechanics); it links the mirrored vendor source where one
exists. _Blocked by_ names what has to land first. _Detail_ carries a
**diverges** link to the permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a host's state bag by absolute dotted path (null past an absent, null or non-array segment) | — (Sylament-invented) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`set()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write a host's state bag by absolute dotted path (auto-vivifies/clobbers intermediates) | — (Sylament-invented) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`getFromArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a detached nested array by dotted path (null past a missing/scalar segment) | [`Arr::get()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`setInArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write into a detached nested array, auto-vivifying/clobbering intermediates | [`Arr::set()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`forgetFromArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — unset leaf path(s) from a detached array, leaving emptied parents | [`Arr::forget()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [Service wiring](../../../packages/schemas/src/State/PropertyAccessor.php) — registration and class alias, injected through `CanAccessProperties` | — (Sylament-invented) | done | — | [diverges](#the-accessor-is-injected-not-reached-for) |

Every row is **done** — the unit suite
([`PropertyAccessorTest`](../../../packages/schemas/tests/Unit/State/PropertyAccessorTest.php))
exercises all five public methods across their branches (nested reads returning
null past an absent, null or non-array segment, on a bag and on a record;
single-segment, auto-vivifying and clobbering writes; the detached trio's
present/missing/past-scalar reads, deep auto-vivify and non-array
clobber, and leaf-only unset over a path list with a missing-path no-op), a wiring
test
([`PropertyAccessorBootTest`](../../../packages/schemas/tests/Integration/State/PropertyAccessorBootTest.php))
pins both paths — a component from `make()` holds the container's accessor, one
from `new` holds a locally built one — and
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)
drives it end-to-end through a real UX LiveComponent hydrate/dehydrate round-trip
via `HasState`. No row has a write-up here.

## Divergences

### The accessor is injected, not reached for

`PropertyAccessor` is a plain service — no `StaticInstance`, no
`HasStaticInstance`, nothing to boot or unset between tests.
`CanAccessProperties` takes it through a `#[Required]` setter and exposes it only
as `getPropertyAccessor()`; the property itself is `private`, so neither a
sibling concern nor a subclass can reach around the getter. Since
components are registered services, `make()` builds them through the container
and the setter runs — verified: a `Section::make()` has the property initialised
before anything reads it.

What it does **not** do is require the container. `getPropertyAccessor()` builds one
locally — `new PropertyAccessor(PropertyAccess::createPropertyAccessor())`, the
construction the test bootstraps already used — when the setter never ran,
because a component built with `new` is a supported shape, not a mistake: the Unit bucket
boots no kernel at all, `ComponentResolver` keeps a null-locator path for exactly
this, and the suites construct components directly in ~300 places. Making the
dependency mandatory turned 42 tests into *"typed property must not be accessed
before initialization"*.

There is no ambient global left in this path at all: the dependency is either
injected or constructed on the spot, and nothing consults a mutable static.
That satisfies [principle §1](../../principles.md#the-test) on both branches,
and is why `PropertyAccessor` no longer appears in the static-instance roster.
The cost is that a `new`-built component makes its own Symfony accessor rather
than sharing one — bounded to code paths with no container, which is where
sharing had nothing to share through anyway.


### State binds through Symfony PropertyAccess, not Livewire entanglement

This file owns the divergence every other schemas state file points at. Filament
routes component state through Livewire's property system: `wire:model` entangles
a field to a nested Livewire property, and Livewire's own binding reads and writes
it. Sylament has no Livewire, so `PropertyAccessor` is the whole replacement
mechanism — it takes the UX LiveComponent host and an absolute dotted state path
(e.g. `data.address.city`), reads the first segment as a property on the host
through Symfony's configured `PropertyAccessorInterface`, and walks the remaining
segments as array indices into the bag it holds. That split deliberately follows
UX `LiveComponentHydrator::adjustPropertyPathForData`, so the paths these methods
touch match what the hydrator round-trips. State concerns never guard for absent
keys — a read returns null past any absent, null or non-array segment and a write
vivifies the branch it needs — the ergonomics Livewire gave Filament for free.
This is structural and permanent: it reverses only if the port ever adopts a
Livewire-equivalent entanglement layer, which is not on the roadmap.

Walking those segments here rather than handing Symfony a whole `data[address][city]`
bracket path is what makes that contract true. Symfony's `PropertyAccessor`
vivifies a *missing* index on both read and write, but throws
`UnexpectedTypeException` the moment an intermediate segment exists holding null
or a scalar — and `fillStateWithNull()` writes exactly that null onto a
path-bearing layout component's own slot before its children are visited, so a
`Section::make('author')->statePath('author')` wrapping two fields crashed on
mount. Filament survives the identical write because `data_get`/`data_set` are
lax in both directions: the parent's null is clobbered back into an array by the
first child write. Routing the traversal through the detached trio gives the
host-bound pair the same contract the detached pair already documented, and the
resulting bag is byte-identical to Filament's. The cost is that a bag property
holding an `ArrayAccess` rather than a real array no longer traverses past the
root segment; no host uses one, and nested plain objects were never reachable
either way.

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
