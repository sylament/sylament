# RecordTypeReader (schemas)

[`Sylament\Schemas\State\RecordTypeReader`](../../../packages/schemas/src/State/RecordTypeReader.php)
and the [`RecordTarget`](../../../packages/schemas/src/State/RecordTarget.php)
value object it returns — a Sylament invention with **no Filament counterpart**,
and the reason there is none is structural rather than an omission: Eloquent
attributes are untyped, so "what does this record declare at this path?" is a
question Filament's mapper cannot ask and never had to answer.

It exists because [`PropertyAccessor::setOnRecord()`](property-accessor.md) used
to write blind. Given a record class and a dotted path it answers three things
from one `symfony/property-info` call — what class to construct for an object
segment, whether null may be written, and whether the property holds a
collection — which is what lets the mapper construct a missing singular
intermediate, lets [`getData()`](belongs-to-record.md) null a whole branch, lets
forms [derive a field's requirement](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)
from the record instead of a declaration, and lets an object collection be
refused loudly instead of mapped wrongly.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — this is Sylament-invented, so it is diffed against the sources it
wraps rather than a Filament port target:

- [`property-info/Extractor/ReflectionExtractor.php`](../../../vendor/symfony/property-info/Extractor/ReflectionExtractor.php)
  — the one extractor this reader is pinned to, and whose `getType()` resolution
  order the whole contract rests on
- [`framework-bundle/Resources/config/property_info.php`](../../../vendor/symfony/framework-bundle/Resources/config/property_info.php)
  — the chained `property_info` service the bundle deliberately does **not**
  autowire in

## Surface

Every point of the reader and its value object. _Blocked by_ names what has to
land first. _Detail_ carries a **semantics** link (a permanent note in
[Semantics](#semantics)) and/or a **diverges** link (the permanent note in
[Divergences](#divergences)), or `—`. Every row is Sylament-only, so no row has
a _Ports_ link.

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`describe(string $recordClass, string $path)`](../../../packages/schemas/src/State/RecordTypeReader.php) — describe a dotted path, hopping object segments by their declared class | done | — | [semantics](#a-null-recordtarget-means-no-type-answer-not-no-such-property) |
| [`describeProperty(string $class, string $property)`](../../../packages/schemas/src/State/RecordTypeReader.php) — describe one property of one class | done | — | [semantics](#nullability-is-the-mutators-even-when-the-getter-disagrees) |
| `RecordTarget::$class` / `$property` — provenance fields | skipped | — | [diverges](#the-value-object-carries-no-provenance) |
| [`RecordTarget::$objectClass`](../../../packages/schemas/src/State/RecordTarget.php) — the class to construct for an object segment, else null | done | — | [diverges](#objectclass-is-gated-on-instantiability-so-it-is-a-promise-not-a-hint) |
| [`RecordTarget::$isNullable`](../../../packages/schemas/src/State/RecordTarget.php) — whether null may be **written** | done | — | [semantics](#nullability-is-the-mutators-even-when-the-getter-disagrees) |
| [`RecordTarget::$isCollection` / `$isObjectCollection`](../../../packages/schemas/src/State/RecordTarget.php) — any collection, and the object-item arms only | done | — | [diverges](#two-collection-flags-because-a-plain-array-is-a-collection-and-is-still-mappable) |
| [Service wiring](../../../packages/schemas/src/SylamentSchemasBundle.php) — `sylament_schemas.record_type_reader` plus a class alias, registered with **no** extractor argument | done | — | [diverges](#the-extractor-is-pinned-to-reflection-and-must-not-be-autowired) |
| Generic collection element types (`array<int, Tag>`) | skipped | — | [diverges](#docblock-generics-are-invisible-and-that-is-the-point) |

Every row is **done** or **skipped** — the unit suite
([`RecordTypeReaderTest`](../../../packages/schemas/tests/Unit/State/RecordTypeReaderTest.php))
covers both nullability arms, the getter/setter asymmetry, the array,
`Traversable` and adder-derived `list<Tag>` collection arms and the two flags
separating them, the enum arm that `class_exists()` alone would have waved
through, the dotted-path walk, and both null answers (an unreadable property, a
path traversing a non-object segment). The walk is pinned through the **leaf's
own** answers rather than through provenance: `Post::$author` reports an
`objectClass` and `TypedRecord::$money` is nullable, so
`describe(…, 'author.name')` reporting no `objectClass` and
`describe(…, 'money.amount')` reporting non-nullable are each reachable only by
descending a segment — verified by disabling the walk, which fails that test and
no other. The consumers pin the rest from their own suites:
construction, refusal and the nullability probe in [`PropertyAccessorTest`](../../../packages/schemas/tests/Unit/State/PropertyAccessorTest.php),
branch nulling in [`Unit/Concerns/BelongsToRecordTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToRecordTest.php),
derivation in forms' [`CanBeValidatedTest`](../../../packages/forms/tests/Integration/Components/Concerns/CanBeValidatedTest.php).
No row has a write-up here.

## Semantics

Behaviour a reader would plausibly misread from the source. Permanent.

### Nullability is the mutator's, even when the getter disagrees

`ReflectionExtractor::getType()` resolves the **setter's first parameter** first
and only falls back to the accessor's return type, then the constructor, then the
property (`vendor/symfony/property-info/Extractor/ReflectionExtractor.php:165-197`).
That ordering is the reason this extractor was chosen rather than an incidental
detail: mapping *writes*, so the write side is the only side whose answer is
correct. Measured over four shapes:

| declaration | reported type | `isNullable` |
| --- | --- | --- |
| `getName(): string` / `setName(string)` | `string` | `false` |
| `getBio(): ?string` / `setBio(?string)` | `?string` | `true` |
| `getAuthor(): Author` / `setAuthor(?Author)` | `?Author` | **`true`** |
| `getAuthor(): ?Author` / `setAuthor(Author)` | `Author` | **`false`** |

Rows three and four are the ones a reader re-deriving the answer from the class
would get wrong, and row three is not hypothetical: it is exactly the shape the
demo's `Article` carried while it hid a missing intermediate behind a vivifying
`getAuthor(): AuthorProfile`. The asymmetry is pinned by
`TypedRecord::$vivifying`, whose getter and setter deliberately disagree.

### A null `RecordTarget` means "no type answer", not "no such property"

Both methods return `null` rather than throwing, and every consumer reads that
one way: *keep whatever behaviour you had before types were consulted.*
`setOnRecord()` falls through to the array-remainder write,
[`acceptsNullOnRecord()`](property-accessor.md#the-nullability-probe-lives-here-because-the-type-reader-does)
answers `true` so `getData()`'s branch pass raises no objection, and forms'
`isRequiredByRecord()` returns `false`. So a null is never an error and never
means "not nullable" — it means the reader declined to answer.

Three different situations produce it, and they are deliberately not
distinguished: the extractor read no type at all (`describeProperty()` on a
property with no accessor, no mutator and no declaration), a `describe()` walk hit
a segment whose `objectClass` is null and so could not continue, or the class does
not exist. Distinguishing them would only move the "do what you did before"
decision from one place into three.

## Divergences

Design decisions with no Filament source to deviate from — permanent, and the
reason each holds.

### The value object carries no provenance

`RecordTarget` was designed with `$class` and `$property` fields naming what had
been described. They are **dropped**, because provenance is something every call
site already has and none of them can be told anything new by:

- Both places that hold a target — `assertNotCollection()` and the
  required-constructor throw in [`setOnRecord()`](property-accessor.md) — receive
  one built by `describeProperty($holder::class, $segment)` a few lines above, in
  the same method. `$target->class === $holder::class` and
  `$target->property === $segment` hold **by construction**, so interpolating the
  target instead of the walk's own variables is a substitution, not an accuracy
  gain.
- The third message — the array remainder a typed property rejects — fires
  precisely when there is *no* usable target (`describeProperty()` returned null,
  or the type named no constructible class), so it has nothing to route through.

That leaves the only argument for keeping them a hypothetical: that the walk
might one day resolve a holder the reader did not describe. Nothing in the code
can reach that state, and two unused public properties on a `final readonly`
value object with two consumers, both in this repo, is not the place to park a
hypothetical. They return the day a consumer holds a target it did not itself
build — the `Repeater` epic's refusal messages being the first candidate — and
adding a promoted property back is a one-line change. That candidate came and
went without materialising them: the repeater's own early refusal builds the
target it consumes in the same method, so it too holds the class and property it
would have read back, and the fields stay dropped.

The one thing they did buy was test expressiveness: the dotted-path walk was
pinned by asserting the leaf's declaring class. That coverage is re-expressed
rather than dropped, through leaf answers only reachable after a hop (above).

### The extractor is pinned to reflection and must not be autowired

The service is registered with its `$extractor` argument **unset**, so the
constructor's `?? new ReflectionExtractor()` decides. This looks like a missing
`->autowire()` and is not: autowiring would inject
`PropertyTypeExtractorInterface`, which `framework.property_info` aliases to the
chained `property_info` service — and in that chain `ReflectionExtractor` sits
**last**, at priority −1002, under `ConstructorExtractor` (−999),
`PhpStanExtractor` (−1000) and `PhpDocExtractor` (−1001). The chain returns the
first non-null answer, so an autowired reader would read the constructor and
PHPDoc **ahead** of the mutator, and the write-side contract above would quietly
stop being true — for records that carry docblocks or a promoted constructor, and
only for those.

Two further costs ride on that. `PropertyTypeExtractorInterface` is a service only
while `framework.property_info` is enabled — normally it is, since
`enableIfStandalone('symfony/property-info', …)` turns the node on whenever the
package is installed and `schemas` requires it, so this bites only under
`symfony/symfony` or an explicit `enabled: false`, where autowiring would fail the
container build outright. And a PHPDoc-reading chain surfaces `GenericType`, which
is neither `ObjectType` nor `CollectionType`, so `Collection<int, Tag>` would slip
past the collection check
that [`setOnRecord()`](property-accessor.md) refuses on. The constructor keeps the
`?PropertyTypeExtractorInterface` seam so a consumer *can* hand in a different
extractor deliberately; nothing in the codebase does.

### `objectClass` is gated on instantiability, so it is a promise not a hint

`resolveObjectClass()` returns a class-string only when
`class_exists($className)` **and** `new ReflectionClass($className)->isInstantiable()`.
Without the second half the property would be a hint a caller had to re-check:
`class_exists()` is `true` for an enum (Symfony resolves a backed-enum property to
`BackedEnumType extends EnumType extends ObjectType`, so it reaches the gate), for
an abstract class, and for a class with a non-public constructor — and `new` on any
of them raises an `Error`, not one of the `PropertyAccess` exceptions the mapper
already catches, so it would surface as an uncaught engine error rather than as a
mapping failure. Gating here means no consumer needs
its own "can I construct this?" guard, and the docblock's promise is enforced at
the one place that can enforce it. Interfaces never reach the gate; `class_exists()`
already excludes them.

Constructible is deliberately not the same as *buildable*: a class with required
constructor arguments still reports its `objectClass`, and the mapper throws a
`LogicException` naming the class and telling the user to give the constructor
defaults. Bypassing the constructor the way Doctrine's `Instantiator` does was
rejected — `doctrine/instantiator` is not in the graph, and a bypassed constructor
leaves typed properties uninitialized, which trades one crash for a later, worse
one.

### Two collection flags, because a plain array is a collection and is still mappable

`isCollection` covers any collection (a plain `array` resolves to `CollectionType`);
`isObjectCollection` covers the two arms whose items have no identity to map onto
— a `Traversable`-typed object, and a `CollectionType` whose value type (unwrapped
if nullable) is an object. The second arm is not a refinement but the mainstream
Doctrine declaration: `getTags(): Collection` behind `addTag(Tag)`/`removeTag(Tag)`
resolves mutator-first to `list<Tag>`, a `CollectionType` and never an
`ObjectType`, so a predicate testing only the `Traversable` arm let the commonest
association shape slip through as a plain list and be mapped wrongly. A native
`array` resolves a **mixed** value type, which is what keeps it out of the object
arm and mappable. They are not interchangeable and the split is load-bearing in
both directions:

- **Refusing every collection breaks the JSON-column shape.** A `?array $meta`
  property under a `Section` is a supported, tested branch — the state is written
  as nested array data through the array-remainder path — and it reports
  `isCollection: true`. [`setOnRecord()`](property-accessor.md) therefore refuses
  on `isObjectCollection` alone.
- **Deriving requirement from an array target is meaningless.** A blank value for
  an `array` target says nothing about whether that target accepts it, so forms'
  derivation skips on `isCollection` — the wide flag.

### Docblock generics are invisible, and that is the point

`ReflectionExtractor` builds a `TypeResolver` with no string resolver, so it never
reads a docblock: `array<int, Tag>` and `array<string, mixed>` are the same native
`array` to this reader. A DTO list of objects whose item class is stated only in a
docblock therefore takes the array-remainder path rather than being refused —
where an adder/remover pair states it in native types, reflection alone reaches it
and [the object arm](#two-collection-flags-because-a-plain-array-is-a-collection-and-is-still-mappable)
does refuse. **Skipped** rather than a defect, on two grounds:
it is unchanged from before this reader existed, and it is unreachable until a
`Repeater` can emit list-shaped state — which is the same epic that will decide how
list items are identified. Reversing it means reading docblocks, which is exactly
the autowiring the divergence above rejects; the identity-aware pass that epic
needs is the real fix.
