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
| [`setOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write into a record's object graph; a null singular segment is constructed from its declared type, the remainder past the first segment that is neither an object nor constructible writes as a nested array; a leaf no setter can change is left alone when it already holds the value and refused by name otherwise | [`data_set()`](../../../vendor/laravel/framework/src/Illuminate/Collections/helpers.php) | done | — | [diverges](#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs) · [diverges](#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused) · [diverges](#eloquent-has-no-property-a-setter-cannot-change-so-the-leaf-rule-has-no-counterpart) · [semantics](#a-throwing-setonrecord-can-leave-a-constructed-intermediate-behind) · [semantics](#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value) |
| [`setAssociationOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — write an association value onto a record property, past `setOnRecord()`'s object-collection refusal; a single related record or null on a property no setter can change meets the same leaf rule, by identity | — (Sylament-invented) | done | — | [semantics](#the-association-write-is-the-collection-refusals-counterpart) · [semantics](#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value) |
| [`hasInArray()`](../../../packages/schemas/src/State/PropertyAccessor.php) — whether a dotted path exists in a detached array (absent vs present-null) | [`Arr::has()`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | — |
| [`acceptsNullOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — whether the record's declared write-side type at a dotted path takes null (true when the type cannot be read) | — (Sylament-invented) | done | — | [semantics](#the-nullability-probe-lives-here-because-the-type-reader-does) |
| [`getBuiltinTypeOnRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — the native type the record declares at a dotted path (null for an object, a collection, a union or a type that cannot be read) | — (Sylament-invented) | done | — | [semantics](#the-nullability-probe-lives-here-because-the-type-reader-does) |
| [`constructRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — build a record the mapper has to create for itself, refusing a constructor that requires arguments with the caller's own subject and advice | — (Sylament-invented) | done | — | [semantics](#the-mappers-construct-or-refuse-is-one-method-three-callers-borrow) |
| [Service wiring](../../../packages/schemas/src/State/PropertyAccessor.php) — registration and class alias, injected through `CanAccessProperties` | — (Sylament-invented) | done | — | [diverges](#the-accessor-is-injected-not-reached-for) |

Every row is **done** — the unit suite
([`PropertyAccessorTest`](../../../packages/schemas/tests/Unit/State/PropertyAccessorTest.php))
exercises the database-free public methods across their branches (nested reads returning
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
rather than the root record; the leaf rule for a property no setter can change —
a writable leaf still written through its setter when it holds the value, an
equal value left alone in eight shapes (a public readonly string, a private one
behind a getter, an `int` written as its numeric string, an identical bool, an
equal date, an equal `Uid`, an equal value object, an array of `int`s written as
their numeric strings), an empty string and a `null`
taken for each other both ways round, a held `0` left alone under `0.0` and
`'0'`, the four pairs PHP's `==` would match refused (`null` over a held `0`,
`null` over a held `false`, `'x'` over a held `true`, `false` over a held `0`),
another value refused naming the two types and neither value in nine — among them
`'7'` over a held `'007'`, which PHP's `<=>` would call equal, `['007']` over a
held `['7']`, and `[0]` over a held `[false]` — a property holding
no value yet refused in both readable shapes, a record compared by identity —
another instance refused though every field matches, a lazy one refused without
being loaded, a proxy subclass read as the entity it extends — and a property it
cannot read surfacing `NoSuchPropertyException` even when it holds the value;
`setAssociationOnRecord()`'s single-record arm — the held record skipped, another
one and `null` refused, a property holding none refused, a writable property
written as before — and its list arm written raw; `hasInArray()`'s present, present-null, absent and
past-scalar answers; and
`acceptsNullOnRecord()`'s four — a nullable property, a non-nullable one, a path
whose type cannot be read, and one read through an object hop rather than off the
root; `getBuiltinTypeOnRecord()`'s builtin leaf, its leaf read through an object
hop, and its two null answers — an object leaf and an unreadable path; and
`constructRecord()` through the intermediate construction and the
refusal that reach it), a
wiring test
([`PropertyAccessorBootTest`](../../../packages/schemas/tests/Integration/State/PropertyAccessorBootTest.php))
pins both paths — a component from `make()` holds the container's accessor, one
from `new` holds a locally built one — and
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)
drives it end-to-end through a real UX LiveComponent hydrate/dehydrate round-trip
via `HasState`. The ninth method's list arm needs a database, so it is pinned
beside the vendor behaviour it delegates to, in
[`DoctrineCollectionWriteTest`](../../../packages/schemas/tests/Integration/State/DoctrineCollectionWriteTest.php)
— the same case asserting that `setOnRecord()` refuses the write it accepts.

## Semantics

Behaviour a reader would plausibly misread from the source. Permanent — these
outlive the detail sections above.

### The mapper's construct-or-refuse is one method three callers borrow

`constructRecord()` is public for callers outside this class, but the rule it
enforces is the accessor's own: the mapper builds a record only when it can build
it with no arguments, because it has nothing to pass a constructor. Three places
need exactly that — `setOnRecord()`'s null singular intermediate, the
[entangled layout component](entangles-state-with-singular-relationship.md)
building the related record a to-one association is missing, and the
[relationship repeater](../forms/repeater.md) building an added item — and each
wrote its own reflection-plus-refusal before this method existed. The two clauses
that genuinely differ come in as arguments: `$subject` (what is being mapped, in
the "Cannot map …" position) and `$advice` (what to do instead). Everything
between them — the required-arguments test, the class name, "so the mapper cannot
build it. Give the constructor defaults, or" — is stated once, so the three
refusals cannot drift apart.

### The association write is the collection refusal's counterpart

`setOnRecord()` refuses to write an object collection because a plain state array
carries no identity for its items: nothing says which item is which entity, so a
write could only replace the collection wholesale and lose every association
Doctrine tracks. `setAssociationOnRecord()` is the same write with that premise
removed — its caller has already resolved entities, and the identity is the
mapping's own — so a list goes straight to Symfony's accessor.

The bypass is narrow on purpose. It takes a single property rather than a dotted
path (an association is a property, and a path through one is a different
question), and it performs no type check at all — Symfony's accessor picks the
adder/remover pair for a collection and the setter for a single object, which is
exactly the difference the caller already encoded in what it passes. The one
distinction it draws is that encoding: a **list** is written raw, while a single
related record or `null` — what a one-to-many child's mapped-by property takes —
meets [the leaf rule](#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value)
when the property has no setter, comparing records by identity alone. What it
must never become is a general "write without checks" escape hatch: everything
else on a record still goes through `setOnRecord()`, and the refusal it raises
stays the default answer for a state-array write.

Its callers write both shapes. Forms'
[`HasRelationship`](../forms/select.md#the-save-arm-writes-a-desired-list-not-attachdetach)
and the [relationship repeater](../forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
write a desired list, whose semantics (identity diffing, surviving collection
instance, adders maintaining the owning side) are the vendor behaviour
characterized in the same test file; the [association reader](association-reader.md)'s
`attach()` and `detach()` write a list onto a many-to-many and the owner, or
`null`, onto a one-to-many child.

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

### A property no setter can change is left alone when it already holds the value

Until [M7 · 10](../roadmap.md#m7--composite-identifiers) every leaf write went
straight to Symfony's accessor, so a property no setter can change — a `public
readonly` one, or a private one behind a getter alone — was a
`NoSuchPropertyException` on every mapped write, whatever it held. That is the
shape a record whose constructor takes its key has: the create page's
`makeRecord()` hands the constructor the key the form maps, and `getData()` then
writes the same key again. The leaf write now asks whether the property is
writable first, and only one that is not takes another path:

- **writable** — written, as before, through its setter and its side effects,
  even when it already holds the value;
- **not writable but readable** — read. Holding **the same value**, it is left
  alone. Holding **another**, the write is refused with a `LogicException` naming
  the state path, the record class and the two value *types* — never the values,
  which can be personal data — and the ways out: hand the constructor the form's
  value as it is, make the field's state match what the constructor keeps with
  `dehydrateStateUsing()`, or leave the field out of the form. Holding **no value
  yet** (`UninitializedPropertyException`), it is refused saying only the
  record's constructor can give it one;
- **neither** — Symfony's `NoSuchPropertyException`, unchanged, which is what a
  misspelled path must surface. A `private readonly` property with no getter is
  in this arm too, whatever it holds: the rule only sees what it can read, so
  give such a property a getter or make it public. This arm relies on Symfony's
  default `throw_exception_on_invalid_property_path: true`; an application that
  turns it off reads an unreadable property as `null`, so a misspelled path there
  meets the "another value" refusal where it used to be dropped in silence.

"Readable" is read by calling the getter, not by Symfony's `isReadable()`, which
answers `false` for an uninitialized property and would send the third case into
the fourth.

"The same value" is, in order: identical; `null` and `''`, either way round — the
`null` a nullable key holds is what a blank field writes; two scalars, neither a
bool and at least one an `int` or a `float`, that compare equal — so `'5'` is the
`5` an integer key holds and `0.0` the `0`; two arrays with the same keys whose
elements are each the same value by this same rule; two objects of one class that
is no record and compare equal — an equal date, `Uid` or value object; anything else is another
value. That is narrower than PHP's `==` on purpose: `==` would also take `null`
for the `0` an integer holds and the `false` a boolean holds, and `'x'` for a held
`true`, so a blank field over a held `0` or `false` would be left alone where it is
now refused. A bool compares strictly, and `null` matches nothing but `''`. Two
strings compare strictly too: PHP compares two numeric strings as numbers, so
`'007'` would meet `'7'`, `'1.50'` `'1.5'` and `'1e3'` `'1000'`, and a changed
zero-padded key written over a readonly one would be left alone — the save
reporting success, writing nothing, and the changed-key pass seeing no change.
The number tier exists to bridge a type a form's state and the record disagree on,
never two spellings of one. Two arrays are compared element by element under the
same rule rather than by PHP's array `<=>`, which compares its elements loosely —
`['007']` would meet `['7']` and `[false]` `[0]` — and compares an entity element by
its fields. A **record** compares by identity alone, because comparing an entity reads its
fields — which loads a lazy one and recurses without end through a bidirectional
association, a PHP `Error` (*Nesting level too deep*). An identity is enough where it matters: the
mapper's `getReference()` and a constructor's `find()` both answer the identity
map's one instance, so a derived identity's readonly association half, set by the
constructor and mapped by a `Select::relationship()`, is left alone.

The number and object tiers are written `($current <=> $value) === 0`, which answers
exactly what `==` answers for every pair they compare. `==` itself cannot be
written: the repository's PHP-CS-Fixer configuration enables the risky
`strict_comparison` rule, so a `==` here would become `===` on the next
`composer format`, and those tiers would go strict without a test noticing first.

What counts as a record is read off the object, since the accessor holds no
entity manager: its class, or a class it extends, carries Doctrine's `#[Entity]`
attribute. The parent walk is what lets a proxy subclass — the class Doctrine
generates when native lazy objects are off — answer as the entity it stands for;
a native lazy object is of the entity's own class, and is compared without being
loaded. An entity mapped in XML or PHP rather than by attribute — held directly,
or as an element of an array — takes the object tier's `<=>`: right in outcome, since two distinct entities differ in their identifier,
but it loads a lazy one and can recurse. `setAssociationOnRecord()` needs no such
reading — its caller writes nothing but records and `null` there — so its single
value compares by identity whatever the mapping.

**Who meets it.** Every caller of the two methods: `getData()`'s leaf loop and its
nullable-branch pass (whose `null` over a branch a property no setter can change
still holds is refused), the to-one `Select::relationship()` write, a singular
relationship's record and fields, a relationship repeater item's fields, tables'
editable cell and reorder write, and the association reader's one-to-many
`attach()` and `detach()` — so a nested create page whose child's constructor
already set its parent keeps it through both attaches rather than throwing. Every
non-writable write was a 500 before, so nothing that saved now fails:

- **create** — a key the constructor sets and the form maps saves; a constructor
  that normalises it (`strtolower()`) is the named refusal, with
  `dehydrateStateUsing()` as the way out;
- **edit** — an unchanged readonly key a dehydrated field maps no longer fails
  every save, and a changed one is the named refusal. That leaves an asymmetry: a
  **writable** changed key is the record check's field error *This value cannot be
  changed.*
  ([can-be-validated.md](can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)),
  a 422 with the key put back, because the write lands and the check then sees it;
  a **readonly** one is this `LogicException`, a 500, because the write is refused
  before the check runs. `disabledOn('edit')` is the remedy for both;
- **replicate** — Doctrine builds a replica without its constructor and copies no
  identifier field, so a readonly scalar key a
  [`ReplicateAction`](../actions/replicate-action.md) modal maps holds no value yet
  and is the uninitialized refusal: a key no setter can set cannot be given to a
  copy.

**Not covered.** The to-many arm: a list `setAssociationOnRecord()` writes still
goes straight to Symfony's accessor, which diffs it through the adder and remover.
And an intermediate segment: a dotted path whose holder is a readonly object the
walk would have to construct and assign is still Symfony's refusal, since the rule
runs at the leaf alone.

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

`getBuiltinTypeOnRecord()` is the same probe one step further out, and lives here
for the same reason: it answers the record's declared *native* type at a path —
`int`, `string`, `bool` — or null where the leaf is an object, a collection, a
union or unreadable, which is the shape a caller deriving a check from it needs
(null means "derive nothing", never "no such property"). Its consumer is tables'
[editable-column validation](../tables/has-columns.md#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest),
which turns the answer into a Symfony `Type` constraint so a value the record
cannot hold is refused before `setOnRecord()` is reached at all — the same
"refuse first with a message of its own" trade the nullability probe makes.

## Divergences

### Eloquent has no property a setter cannot change, so the leaf rule has no counterpart

Filament writes form data through `Model::fill()`, and an Eloquent attribute is
always writable: `setAttribute()` stores into the model's attribute array, guarded
by `$fillable` rather than by the class. A record whose constructor sets a
property no setter can change is a Doctrine shape with no Eloquent equivalent, so
Filament has nothing to skip or refuse, and
[the leaf rule](#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value)
is the mapper's own answer to it. It is monotonic over what came before — every
such write was Symfony's `NoSuchPropertyException` — and it refuses rather than
writes around a property the record closed: nothing sets one through reflection.

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
