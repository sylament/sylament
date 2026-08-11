# AssociationReader (schemas)

[`Sylament\Schemas\State\AssociationReader`](../../../packages/schemas/src/State/AssociationReader.php)
and the [`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php)
value object it returns — a Sylament invention with **no Filament counterpart**,
and the reason there is none is structural. Filament asks an Eloquent model for
a relationship by calling it: `$record->{$name}()` hands back a `Relation`
object that already knows its related model, its cardinality, and how to save
itself, so a component reads and writes a relationship through the model
instance it was given. Doctrine has no such object — an association is
*mapping metadata*, read from the `EntityManager`, and writing it is the unit of
work's job at flush time. This class is that translation, and it is the **only**
seam through which `schemas` touches an `EntityManager`.

It exists so that every later relation-aware component asks one collaborator the
five questions the mapping can answer — what does this property relate to, how do
I reference a related entity by id, what key does a record answer to, what query
lists the options, and how do I delete an orphan — instead of each growing its
own `EntityManager` dependency and its own metadata reads. The seam is also what
keeps Doctrine optional at runtime: an application with no `EntityManager` gets a
reader that reports [no association anywhere](#a-null-target-means-no-doctrine-answer-not-no-such-property)
rather than a container that cannot compile.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — this is Sylament-invented, so it is diffed against the sources it
wraps rather than a Filament port target:

- [`orm/src/Mapping/ClassMetadata.php`](../../../vendor/doctrine/orm/src/Mapping/ClassMetadata.php)
  — every metadata read this class makes
- [`orm/src/Mapping/AssociationMapping.php`](../../../vendor/doctrine/orm/src/Mapping/AssociationMapping.php)
  — the per-association mapping object behind `getAssociationMapping()`
- [`orm/src/EntityManager.php`](../../../vendor/doctrine/orm/src/EntityManager.php)
  — `getReference()`, whose ORM 3 signature is [nullable](#getreference-throws-where-doctrine-returns-null)
- [`tables' HasTable::resolveQueryRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php)
  — the record-key semantics this class mirrors, trailed in
  [has-table.md](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key)

## Surface

Every point of the reader and its value object. _Blocked by_ names what has to
land first. _Detail_ carries a **semantics** link (a permanent note in
[Semantics](#semantics)) and/or a **diverges** link (the permanent note in
[Divergences](#divergences)), or `—`. Every row is Sylament-only, so no row has
a _Ports_ link.

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`__construct(?EntityManagerInterface)`](../../../packages/schemas/src/State/AssociationReader.php) + [`hasEntityManager()`](../../../packages/schemas/src/State/AssociationReader.php) — the optional-Doctrine seam | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`describe(string $class, string $property)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at one property, or null | done | — | [semantics](#a-null-target-means-no-doctrine-answer-not-no-such-property) |
| Transient / anonymous-class guarding inside `describe()` | done | — | [semantics](#istransient-answers-false-for-an-anonymous-class-so-it-is-not-the-whole-guard) |
| [`getReference(string $class, int\|string $id)`](../../../packages/schemas/src/State/AssociationReader.php) — a managed reference without a load | done | — | [diverges](#getreference-throws-where-doctrine-returns-null) |
| [`getRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the string key an entity is spoken by | done | — | [semantics](#the-record-key-is-tables-semantics-moved-onto-the-mapping) |
| [`createOptionQuery(string $targetClass, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the base query an option list narrows | done | — | [semantics](#the-option-query-is-a-starting-point-not-a-result) |
| [`remove(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — delete an orphan through the unit of work | done | — | [semantics](#remove-is-here-so-orphan-deletion-does-not-grow-a-second-seam) |
| [`AssociationTarget::$targetClass` / `$isToMany`](../../../packages/schemas/src/State/AssociationTarget.php) — what to load and in what shape | done | — | — |
| [`AssociationTarget::$isOwningSide`](../../../packages/schemas/src/State/AssociationTarget.php) — whether writing this side writes the database | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [`AssociationTarget::$isOrphanRemoval`](../../../packages/schemas/src/State/AssociationTarget.php) — whether detaching an item deletes it | done | — | [diverges](#orphanremoval-is-a-plain-bool-on-the-orm-3-mapping-object) |
| [Service wiring](../../../packages/schemas/src/SylamentSchemasBundle.php) — `sylament_schemas.association_reader` plus a class alias, with an ignore-on-invalid `EntityManagerInterface` argument | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| Composite identifiers | skipped | — | [diverges](#a-composite-identifier-is-refused-not-encoded) |
| Through-relations, morphs, pivot data | skipped | — | [diverges](#only-the-shapes-doctrine-states-in-one-mapping-are-read) |

Every row is **done** or **skipped** — the unit suite
([`AssociationReaderTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderTest.php))
covers all four association shapes the fixture entities declare (many-to-one,
the inverse one-to-many with orphan removal, the owning many-to-many, and the
**inverse** one-to-one), all three null answers (a scalar property, a non-entity
class, an anonymous class), the four operations refusing without an entity
manager, the record key against a persisted entity, and both key refusals — an
unpersisted entity and the composite-identifier
[`CompositePair`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/CompositePair.php)
fixture carried over from tables.

## Semantics

### The entity manager is optional, and the two halves answer differently

The constructor takes `?EntityManagerInterface` and the bundle wires it with
`service(EntityManagerInterface::class)->nullOnInvalid()`, so `schemas` compiles
in an application with no Doctrine at all — the same shape as support's
[AssetMapper guard](../support/support-bundle.md), and the reason
`doctrine/orm` being a hard `require` of the package is still not a hard
requirement of the *runtime*. Without a manager the class splits in two:

- **`describe()` answers null**, because "is this property an association?" has a
  meaningful negative answer — no Doctrine means no associations, and a caller
  keeps whatever non-association behaviour it already had.
- **The four operations throw a `LogicException`**, because
  `getReference()`, `getRecordKey()`, `createOptionQuery()` and `remove()` have
  no meaningful negative answer: a caller reaching them has already decided it is
  handling a relationship, so a silent null would corrupt state instead of
  failing. One message covers all four, and it names both causes (a reader built
  outside the container, or no `EntityManagerInterface` service) and the two ways
  out — register `doctrine/doctrine-bundle`, or bind the record by hand after
  `getData()`.

`hasEntityManager()` exists so a caller can branch **before** it commits to the
relationship path, rather than catching the exception the operations throw.

### A null target means "no Doctrine answer", not "no such property"

`describe()` returns null on three different facts — there is no entity manager,
the class is not a managed entity, the property is not an association — and
deliberately does not distinguish them, exactly as
[`RecordTypeReader`](record-type-reader.md#a-null-recordtarget-means-no-type-answer-not-no-such-property)
does. All three mean the same thing to a caller: *keep doing what you did before
associations existed*. A field whose property is a plain column, a field on a DTO
record, and every field in a Doctrine-less application take one code path, not
three.

### `isTransient()` answers false for an anonymous class, so it is not the whole guard

The transient check is `getMetadataFactory()->isTransient($class)` rather than a
`getClassMetadata()` call in a `try`, following the reasoning tables already
recorded for [its record-key arm](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key):
the metadata read is the expensive, throwing operation and the factory answers the
cheap question directly. But `isTransient()` has one documented hole, verified in
[`AbstractClassMetadataFactory`](../../../vendor/doctrine/persistence/src/Mapping/AbstractClassMetadataFactory.php):
it returns **false** for an anonymous class — the plainest transient object there
is — and the metadata read that follows then throws
`Doctrine\Persistence\Mapping\MappingException: Class "class@anonymous …" is
anonymous`. So `describe()` adds a `ReflectionClass::isAnonymous()` check and
answers null for it. A test double built as `new class { … }` is an ordinary thing
to hand a schema, and it takes the non-association path rather than an
uncatchable mapping error.

### The record key is tables' semantics, moved onto the mapping

`getRecordKey()` is the same answer
[`HasTable::resolveQueryRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php)
gives, with the same refusals and the same message shapes: the identifier comes
from `ClassMetadata::getIdentifierValues()` — never from an `id` property
convention — a `Stringable` identifier is unwrapped, and a value that is unset,
non-scalar or blank is a `LogicException` rather than a key no lookup could
resolve. An entity mapped on `code` therefore keys by `code` with nothing
configured, and an entity that was never persisted fails at the point of binding
instead of producing a row the client cannot address.

One shape difference from tables: the scalar value is normalised to `?string`
*before* the blank check, so the single [`blank()`](../support/helpers.md) call
sees `string|null` and the throw covers both the non-printable and the blank
arms. Written the other way round — `! is_scalar($key) || blank($key)`, tables'
order — the helper is called with a type PHPStan has already proven non-null, and
its `@phpstan-assert-if-false !null` docblock makes that call a level-max
`function.impossibleType` error in a class context. One call, no suppression.

The order is **not** behaviour-neutral, and the one value it parts ways on is
`false`. [`blank()`](../support/helpers.md) answers `false` for a boolean —
bools are never blank, mirroring Laravel — while `(string) false` is `''`, which
is blank. So an entity whose identifier is boolean `false` throws here and yields
the empty string in tables' order. The reader's answer is the one worth keeping:
its return feeds an item key and a `getReference()` lookup, and `''` is not a key
anything can resolve. Nothing in either package maps a boolean identifier today,
so this is a recorded delta rather than a bug filed against tables — but it is a
delta, not a rephrasing.

### The option query is a starting point, not a result

`createOptionQuery()` returns a `QueryBuilder` selecting the target entity under a
fixed default alias (`record`), and nothing else — no ordering, no limit, no
search. Everything a relationship option list needs to add (a user's
`modifyQueryUsing`, a search constraint, a page limit) is a mutation on that
builder, so the reader never has to grow a parameter for it, and a caller can
always fall back to the manager's own query API for a shape this cannot express.
The alias is a parameter rather than a constant because a caller composing a
subquery needs to control it.

### `remove()` is here so orphan deletion does not grow a second seam

Detaching an item from an association whose mapping declares `orphanRemoval` is
Doctrine's job at flush time, but a component that *replaces* a collection has to
delete what it dropped, and that is an `EntityManager::remove()` call. Routing it
through the reader keeps the "only seam" property true: no relation-aware
component holds an `EntityManager` of its own, and the null-manager refusal above
covers deletion for free.

The first component to replace a collection did **not** end up calling it. The
[relationship repeater](../forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
leaves a dropped child out of the desired list and lets the mapping's
`orphanRemoval` delete it, refusing an association that declares none rather
than deleting on its own authority — because the mapping is where ownership is
declared, and a form layer issuing DELETEs against a mapping that says "merely
detach" is the data-loss shape `isOrphanRemoval` exists to warn about.

Its caller is the one anticipated above: the record no mapping owns. A layout
component
[entangled with a to-one association](entangles-state-with-singular-relationship.md#a-switched-off-relationship-nulls-the-property-and-removes-the-record-explicitly)
whose `relationship()` condition evaluates false nulls the property and calls
`remove()` on what was there, `orphanRemoval` or not — the developer declared
that this related record must not exist, which is a different statement from an
end user dropping an item out of a list. Nulling alone would not even reach the
database on an inverse side, so the explicit call is what makes the outcome
independent of which side of the association the component is declared on.

### The owning-side and orphan-removal flags are what a writer needs, not trivia

`AssociationTarget` carries four fields because a writer cannot act correctly on
fewer. `targetClass` and `isToMany` say what to load and in what shape.
`isOwningSide` says whether writing this side changes the database at all — an
inverse side is a read-through, so a component that writes one without also
maintaining the owning side saves nothing, which is exactly the trap the fixture
entities pin (`Post::$profile` is the **inverse** one-to-one; `Profile::$post`
owns it). `isOrphanRemoval` says whether dropping an item deletes the entity or
merely detaches it — the difference between a cleanup and data loss, and not
something a caller may guess.

## Divergences

### `getReference()` throws where Doctrine returns null

`EntityManagerInterface::getReference()` is typed `object|null` in ORM 3, and
[`EntityManager::getReference()`](../../../vendor/doctrine/orm/src/EntityManager.php)
reaches null on two paths: the identity map holds an object of a different class
under that identifier, or the entity has subclasses and the `find()` it delegates
to misses. Our signature is non-nullable `object`, and the null answer becomes a
`LogicException` naming the class and the identifier. The reason is what the
callers do with it: a reference is fetched to *bind* a chosen option onto a
record, so a null would be written straight into the association and silently
unset the relationship the user just chose. Failing at the seam names the
identifier that could not be referenced; the alternative pushes a null check onto
every caller and gets it wrong once.

### `orphanRemoval` is a plain bool on the ORM 3 mapping object

`AssociationMapping::$orphanRemoval` is declared `public bool $orphanRemoval =
false` — not an array key, not nullable — so it is read directly, with no
null-coalescing fallback. This is recorded because ORM 2 exposed association
mappings as **arrays** (`$mapping['orphanRemoval']`, absent on many mappings),
which is the shape most ported code and most training data assume; the array
access still exists in ORM 3 only through `ArrayAccess` on the mapping object.
Level max rejects a `?? false` against a non-nullable bool, so the truthful form
is the only one that compiles.

### A composite identifier is refused, not encoded

`getRecordKey()` throws on `ClassMetadata::$isIdentifierComposite`, mirroring
[tables' refusal](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key)
and its message shape. A relation-backed field speaks a **single string** to the
client — an option value, a selected key — and any encoding of two columns into
one string (a delimiter, JSON, a hash) has to be decoded on the way back, which
turns a mapping question into a serialisation format nobody asked for. Refusing
is consistent across the two packages that key records, and it is
[out of scope for this epic](../roadmap.md) by decision rather than by omission.

### Only the shapes Doctrine states in one mapping are read

`describe()` answers from a single `ClassMetadata` association mapping, so it
covers the four shapes Doctrine declares there — many-to-one, one-to-many,
one-to-one, many-to-many, in either direction. Filament additionally reflects
polymorphic (`morphTo`, `morphMany`), through- (`hasManyThrough`) and pivot-data
relationships, all of which are Eloquent constructs with no single-mapping
Doctrine equivalent: a Doctrine join entity is an ordinary entity with two
associations, so the "pivot data" case is a nested schema over that entity rather
than a mapping flag. Skipped here on those grounds, not deferred — reading them
would mean inventing a vocabulary Doctrine does not have.
