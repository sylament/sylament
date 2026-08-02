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
| [`RecordTarget::$class` / `$property`](../../../packages/schemas/src/State/RecordTarget.php) — where the description came from, so an error message can name the object that rejected a write rather than the root record | done | — | — |
| [`RecordTarget::$objectClass`](../../../packages/schemas/src/State/RecordTarget.php) — the class to construct for an object segment, else null | done | — | [diverges](#objectclass-is-gated-on-instantiability-so-it-is-a-promise-not-a-hint) |
| [`RecordTarget::$isNullable`](../../../packages/schemas/src/State/RecordTarget.php) — whether null may be **written** | done | — | [semantics](#nullability-is-the-mutators-even-when-the-getter-disagrees) |
| [`RecordTarget::$isCollection` / `$isObjectCollection`](../../../packages/schemas/src/State/RecordTarget.php) — any collection, and the `Traversable` arm only | done | — | [diverges](#two-collection-flags-because-a-plain-array-is-a-collection-and-is-still-mappable) |
| [Service wiring](../../../packages/schemas/src/SylamentSchemasBundle.php) — `sylament_schemas.record_type_reader` plus a class alias, registered with **no** extractor argument | done | — | [diverges](#the-extractor-is-pinned-to-reflection-and-must-not-be-autowired) |
| Generic collection element types (`array<int, Tag>`) | skipped | — | [diverges](#docblock-generics-are-invisible-and-that-is-the-point) |

Every row is **done** or **skipped** — the unit suite
([`RecordTypeReaderTest`](../../../packages/schemas/tests/Unit/State/RecordTypeReaderTest.php))
covers both nullability arms, the getter/setter asymmetry, the array and
`Traversable` collection arms and the two flags separating them, the enum arm
that `class_exists()` alone would have waved through, the dotted-path walk, and
both null answers (an unreadable property, a path traversing a non-object
segment). The consumers pin the rest from their own suites: construction and
refusal in [`PropertyAccessorTest`](../../../packages/schemas/tests/Unit/State/PropertyAccessorTest.php),
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
`setOnRecord()` falls through to the array-remainder write, `getData()` skips the
branch, forms' `isRequiredByRecord()` returns `false`. So a null is never an
error and never means "not nullable" — it means the reader declined to answer.

Three different situations produce it, and they are deliberately not
distinguished: the extractor read no type at all (`describeProperty()` on a
property with no accessor, no mutator and no declaration), a `describe()` walk hit
a segment whose `objectClass` is null and so could not continue, or the class does
not exist. Distinguishing them would only move the "do what you did before"
decision from one place into three.

## Divergences

Design decisions with no Filament source to deviate from — permanent, and the
reason each holds.

### The extractor is pinned to reflection and must not be autowired

The service is registered with its `$extractor` argument **unset**, so the
constructor's `?? new ReflectionExtractor()` decides. This looks like a missing
`->autowire()` and is not: autowiring would inject
`PropertyTypeExtractorInterface`, which `framework.property_info` aliases to the
chained `property_info` service — and in that chain `ReflectionExtractor` sits
at priority **−1002**, behind `PhpStanExtractor` (−1000), `PhpDocExtractor`
(−1001) and `ConstructorExtractor`. The chain returns the first non-null answer,
so an autowired reader would read PHPDoc and the constructor **ahead** of the
mutator, and the write-side contract above would quietly stop being true — for
records that carry docblocks, and only for those.

Two further costs ride on that: `PropertyTypeExtractorInterface` is only a
service at all once the application enables `framework.property_info`, so
autowiring would make the reader fail to compile in an app that has not; and a
PHPDoc-reading chain surfaces `GenericType`, which is neither `ObjectType` nor
`CollectionType`, so `Collection<int, Tag>` would slip past the collection check
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
of them is a fatal error, not a catchable one. Gating here means no consumer needs
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
`isObjectCollection` covers only a `Traversable`-typed object, the arm whose items
have no identity to map onto. They are not interchangeable and the split is
load-bearing in both directions:

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
`array` to this reader. A DTO list of objects therefore takes the array-remainder
path rather than being refused. **Skipped** rather than a defect, on two grounds:
it is unchanged from before this reader existed, and it is unreachable until a
`Repeater` can emit list-shaped state — which is the same epic that will decide how
list items are identified. Reversing it means reading docblocks, which is exactly
the autowiring the divergence above rejects; the identity-aware pass that epic
needs is the real fix.
