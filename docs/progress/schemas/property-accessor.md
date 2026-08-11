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
exists. The record pair additionally takes a
[`RecordTypeReader`](record-type-reader.md), which is what lets a write know what
the record declares where it is writing. State concerns reach it through
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
**diverges** link (the permanent note in [Divergences](#divergences)) and/or a
**semantics** link (a permanent note in [Semantics](#semantics)), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a host's state bag by absolute dotted path (null past an absent, null or non-array segment) | — (Sylament-invented) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`set()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write a host's state bag by absolute dotted path (auto-vivifies/clobbers intermediates) | — (Sylament-invented) | done | — | [diverges](#state-binds-through-symfony-propertyaccess-not-livewire-entanglement) |
| [`getFromArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a detached nested array by dotted path (null past a missing/scalar segment) | [`Arr::get()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`setInArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write into a detached nested array, auto-vivifying/clobbering intermediates | [`Arr::set()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`forgetFromArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — unset leaf path(s) from a detached array, leaving emptied parents | [`Arr::forget()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) |
| [`getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — read a record's object graph by dotted path (objects hop by property, arrays by key, null past a null/scalar intermediate) | [`data_get()`](../../../vendor/laravel/framework/src/Illuminate/Collections/helpers.php) | done | — | [diverges](#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs) |
| [`setOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write into a record's object graph; a null singular segment is constructed from its declared type, the remainder past the first segment that is neither an object nor constructible writes as a nested array | [`data_set()`](../../../vendor/laravel/framework/src/Illuminate/Collections/helpers.php) | done | — | [diverges](#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs) · [diverges](#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused) · [semantics](#a-throwing-setonrecord-can-leave-a-constructed-intermediate-behind) |
| [`setAssociationOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write an association value onto a record property, past `setOnRecord()`'s object-collection refusal | — (Sylament-invented) | done | — | [semantics](#the-association-write-is-the-collection-refusals-counterpart) |
| [`hasInArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — whether a dotted path exists in a detached array (absent vs present-null) | [`Arr::has()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | — |
| [`acceptsNullOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — whether the record's declared write-side type at a dotted path takes null (true when the type cannot be read) | — (Sylament-invented) | done | — | [semantics](#the-nullability-probe-lives-here-because-the-type-reader-does) |
| [Service wiring](../../../packages/schemas/src/State/PropertyAccessor.php) — registration and class alias, injected through `CanAccessProperties` | — (Sylament-invented) | done | — | [diverges](#the-accessor-is-injected-not-reached-for) |

Every row is **done** — the unit suite
([`PropertyAccessorTest`](../../../packages/schemas/tests/Unit/State/PropertyAccessorTest.php))
exercises the eight database-free public methods across their branches (nested reads returning
null past an absent, null or non-array segment, on a bag and on a record;
single-segment, auto-vivifying and clobbering writes; the detached trio's
present/missing/past-scalar reads, deep auto-vivify and non-array
clobber, and leaf-only unset over a path list with a missing-path no-op; the
record pair's object hops, object-to-array boundary crossing, leaf object read,
propagated `AccessException`s, in-place nested mutation, boundary vivify
preserving unmanaged siblings, and the write side's construction set —
constructing a null intermediate, constructing one declared non-nullable and
never assigned, mutating an existing intermediate in place rather than replacing
it, the plain-array branch still taking the array-remainder path, the
required-constructor-arguments refusal, the object-collection refusal at all four
of its sites — a declared segment, a declared leaf, an adder-shaped leaf, and an
initialized collection met on the walk — the scalar-intermediate
`LogicException`, and the pin that it names the object that rejected the array
rather than the root record; `hasInArray()`'s present, present-null, absent and
past-scalar answers; and
`acceptsNullOnRecord()`'s four — a nullable property, a non-nullable one, a path
whose type cannot be read, and one read through an object hop rather than off the
root), a
wiring test
([`PropertyAccessorBootTest`](../../../packages/schemas/tests/Integration/State/PropertyAccessorBootTest.php))
pins both paths — a component from `make()` holds the container's accessor, one
from `new` holds a locally built one — and
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)
drives it end-to-end through a real UX LiveComponent hydrate/dehydrate round-trip
via `HasState`. The ninth method needs a database, so it is pinned beside the
vendor behaviour it delegates to, in
[`DoctrineCollectionWriteTest`](../../../packages/schemas/tests/Integration/State/DoctrineCollectionWriteTest.php)
— the same case asserting that `setOnRecord()` refuses the write it accepts.

## Semantics

Behaviour a reader would plausibly misread from the source. Permanent — these
outlive the detail sections above.

### The association write is the collection refusal's counterpart

`setOnRecord()` refuses to write an object collection because a plain state array
carries no identity for its items: nothing says which item is which entity, so a
write could only replace the collection wholesale and lose every association
Doctrine tracks. `setAssociationOnRecord()` is the same write with that premise
removed — its caller has already resolved entities, and the identity is the
mapping's own — so it hands the value straight to Symfony's accessor.

The bypass is narrow on purpose. It takes a single property rather than a dotted
path (an association is a property, and a path through one is a different
question), it makes no attempt to distinguish a to-one from a to-many (the caller
read that off the Doctrine mapping to build the value in the first place), and it
performs no type check at all — Symfony's accessor picks the adder/remover pair
for a collection and the setter for a single object, which is exactly the
difference the caller already encoded in what it passes. What it must never
become is a general "write without checks" escape hatch: everything else on a
record still goes through `setOnRecord()`, and the refusal it raises stays the
default answer for a state-array write.

Its only caller today is forms'
[`HasRelationship`](../forms/select.md#the-save-arm-writes-a-desired-list-not-attachdetach),
whose desired-list semantics (identity diffing, surviving collection instance,
adders maintaining the owning side) are the vendor behaviour characterized in the
same test file.

### A throwing `setOnRecord()` can leave a constructed intermediate behind

Before construction landed, a throw from this method never mutated the record:
every failure was detected before anything was written. That is no longer true.
The walk assigns each constructed intermediate as it descends, so a path that
throws three segments deep leaves the objects it built on the way there attached
to the record — an empty `Author` on a `Post` whose `author.name.deep` write was
rejected.

It is left as-is rather than made transactional, on two grounds. Every throw on
this path is a `LogicException` — a programmer error in the schema or the record,
never user input — so the record is being discarded with the request anyway; and
[`getData()`](belongs-to-record.md) validates the whole state before it maps, so
a failing *submit* never reaches the mapper at all. Buffering the writes to make
the method atomic would cost a second traversal to serve only the case where
someone catches a `LogicException` and keeps using the record, which nothing does.
Worth knowing before writing a test that asserts an untouched record after a
caught throw.

### The nullability probe lives here because the type reader does

`acceptsNullOnRecord()` writes nothing. It exists so a caller about to write null
into the record graph can refuse *first*, with a message of its own, instead of
letting Symfony raise `InvalidTypeException` — the bare type error this class
exists to translate everywhere else. Its one consumer today is
[`getData()`'s branch pass](belongs-to-record.md#a-branch-the-record-cannot-null-is-refused-loudly),
which needs to name `nullWhenBlank()` in the failure, and `setOnRecord()` cannot
know that is why null arrived.

It sits on this class rather than on the caller because this class already holds
the [`RecordTypeReader`](record-type-reader.md) — the alternative was a second
`#[Required]` setter on `Schema`, duplicating the one
[`CanBeValidated`](../forms/can-be-validated.md) already carries, so that the
schema could reach a reader its own accessor was constructed with.

Note what it does **not** do: it never asserts. A path whose type cannot be read
answers `true`, so an unreadable target keeps the behaviour it had before types
were consulted, and every "should this throw" decision stays with the caller.

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

The same `->autowire()` is what keeps `RecordTypeReader`'s definition alive: it is
registered with a class alias and nothing else references it, so before the record
pair took it as a constructor argument Symfony's `RemoveUnusedDefinitionsPass`
deleted it at compile time.

What it does **not** do is require the container. `getPropertyAccessor()` builds one
locally — `new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader())`,
the construction the test bootstraps already used — when the setter never ran,
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
root segment; no host uses one, and nested plain objects are not the host pair's
job — the record pair below owns object-graph traversal.

### The record pair completes the data_get/data_set port for object graphs

`getFromRecord()`/`setOnRecord()` are the object-graph half of Laravel's
`data_get`/`data_set`, which is what Filament's record mapping actually leans
on. They are segment-wise on purpose: Symfony's dotted syntax always means
*property* — `getValue($post, 'meta.foo')` with `$meta` holding an array throws
`NoSuchPropertyException` ("Cannot read property from an array") — so
whole-path delegation would trade broken object intermediates for broken array
intermediates. The walk hops objects one property at a time through the
injected accessor and switches permanently to array traversal at the first
segment that is neither an object nor a constructible one, giving mixed graphs
(an entity holding a JSON-shaped array column) one contract. Reads are lenient
like `getFromArray()` (null past a null/scalar intermediate, accessor failures
propagate for the caller to judge); a write vivifies an *object* segment where
the record declares a constructible class there
([below](#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused))
and otherwise falls through to that array remainder, whose write-back onto a
typed property that rejects it throws `LogicException` naming the path and the
fix, because "PropertyAccessor requires a graph of objects or arrays" explains
nothing to someone who just nested a Section. Objects *inside* the array
remainder are out of scope until repeaters need them — `setInArray()`'s
documented clobber contract applies past the boundary.

That `LogicException` rides on Symfony's own classification of the rejected
write: `PropertyAccessor::setValue()` converts the setter's `TypeError` into an
`InvalidTypeException` (an `InvalidArgumentException`) only when it can
`preg_match` the message, and PHP renders an anonymous class's method as
`class@anonymous()` with no `::method` segment, so that one record shape
rethrows the raw `TypeError` instead. Catching `TypeError` alongside is not an
option — `PropertyAccessorInterface::setValue()` does not declare it, so PHPStan
calls the catch dead — which scopes the friendly message to named records, every
shape a schema actually binds to, and is why the fixtures under
[`tests/Fixtures/State/`](../../../packages/schemas/tests/Fixtures/State) are named
classes rather than the anonymous ones the rest of the suite reaches for.

### A missing singular intermediate is constructed; an object collection is refused

`setOnRecord()` used to write blind, which made both halves of a nested write
wrong in opposite directions: a null intermediate could only be reported as an
error, and a leaf whose target rejected the value produced whatever Symfony's
accessor happened to throw. It now asks the
[`RecordTypeReader`](record-type-reader.md) what the record declares before it
acts, and the two answers split the behaviour:

- **A null (or uninitialized) singular object segment whose declared type names a
  constructible class is built with `new` and assigned**, then the walk continues
  into it. The reasoning that overturns the previous "never constructed" stance is
  recorded once, in
  [belongs-to-record.md](belongs-to-record.md#a-missing-singular-intermediate-is-constructed-from-its-declared-type).
  Nothing about the *other* arms changed: an existing object is still mutated in
  place rather than replaced (pinned by an identity assertion), and a segment the
  record declares as anything but a constructible object still falls through to
  the array remainder, which is what keeps the JSON-column shape working.
- **A segment or leaf that is an object collection throws**, naming the path and
  `relationship()` as what supplies item identity. The message named
  the `Repeater` epic until the [`Repeater`](../forms/repeater.md#object-collections-stay-refused-until-a-relationship-supplies-the-identity)
  shipped, at which point pointing at it would have read as a bug report: the
  repeater maps plain-array items, and an object collection needs to know which
  state item is which entity, which is the relationship epic's problem. That
  epic then answered it: a repeater declaring
  [`relationship()`](../forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
  writes its collection through
  [`setAssociationOnRecord()`](#the-association-write-is-the-collection-refusals-counterpart)
  from its own save hook, so this refusal is never on that path — it still
  governs every collection nobody declared a relationship for. The same
  rewording precedent fired a second time for the *advice*: telling the reader
  identity "arrives with the Doctrine relationship layer — until then, map this
  property by hand" is stale the moment the layer lands, so the message now
  names `relationship()` first and keeps hand-mapping after `getData()` as the
  fallback. Two triggers raise the one message `refuseCollection()`
  holds. The **declared type** is the designed one: `assertNotCollection()` asks
  the [`RecordTypeReader`](record-type-reader.md) at both the intermediate and the
  leaf site. The **runtime instance** is the one that has to exist anyway — a
  `Traversable` met mid-walk, checked immediately before the `is_object($current)`
  hop, because that hop wins before any type is consulted and so walked an
  *initialized* collection (which is every constructor-initialized Doctrine
  entity) straight past the designed refusal into a bare `NoSuchPropertyException`
  about reading property `"0"`. It covers the same shape from the other side too:
  a collection the reader cannot see through, behind an untyped or getter-only
  surface. Both triggers are deliberately narrower than "any collection": a
  plain `array` property reports `isCollection` and must keep taking the
  array-remainder path
  ([the two flags](record-type-reader.md#two-collection-flags-because-a-plain-array-is-a-collection-and-is-still-mappable)),
  so only the object-item arms are refused — the ones whose items have no identity
  to map onto.

A third arm reports the case the reader can describe but the mapper still cannot
build: a declared class whose constructor takes required arguments throws a
`LogicException` naming the class and telling the user to give the constructor
defaults or initialize the property. All three are `LogicException` because each
is a programmer error in the schema or the record, not user input — and
`getData()` validates before it maps, so user input cannot reach them.

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
