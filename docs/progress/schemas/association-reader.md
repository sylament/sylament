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
own `EntityManager` dependency and its own metadata reads. The relationship arm
adds three more to the list — which records does this owner's collection hold,
which ones does it not, and how do I put one in or take one out — asked through
the same collaborator and answered from the same mapping. The seam is also what
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
| [`__construct(?EntityManagerInterface, ?PropertyAccessor)`](../../../packages/schemas/src/State/AssociationReader.php) + [`hasEntityManager()`](../../../packages/schemas/src/State/AssociationReader.php) — the optional-Doctrine seam, and the accessor the writes go through | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`describe(string $class, string $property)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at one property, or null | done | — | [semantics](#a-null-target-means-no-doctrine-answer-not-no-such-property) |
| Transient / anonymous-class guarding inside `describe()` | done | — | [semantics](#istransient-answers-false-for-an-anonymous-class-so-it-is-not-the-whole-guard) |
| [`getReference(string $class, int\|string $id)`](../../../packages/schemas/src/State/AssociationReader.php) — a managed reference without a load | done | — | [diverges](#getreference-throws-where-doctrine-returns-null) |
| [`getRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the string key an entity is spoken by | done | — | [semantics](#the-record-key-is-tables-semantics-moved-onto-the-mapping) |
| [`findRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the same key, or null for an entity that has none yet | done | — | [semantics](#findrecordkey-answers-null-where-getrecordkey-throws) |
| [`getIdentifierFieldName(string $class)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapped identifier's field name, for a caller that needs the property rather than the value | done | — | [diverges](#a-composite-identifier-is-refused-not-encoded) |
| [`createOptionQuery(string $targetClass, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the base query an option list narrows | done | — | [semantics](#the-option-query-is-a-starting-point-not-a-result) |
| [`Relationship`](../../../packages/schemas/src/State/Relationship.php) — one owner record's association, by name | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`describeRelationship(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at a relationship, refused rather than null | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`createRelationshipQuery(Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the owner's related records, rooted at the target | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`createRelationshipCandidateQuery(Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — every target record NOT related to this owner | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`attach(Relationship, object $related)`](../../../packages/schemas/src/State/AssociationReader.php) — make a record one of the owner's, in memory | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) |
| [`detach(Relationship, object $related)`](../../../packages/schemas/src/State/AssociationReader.php) — the mirror removal, after the dissociate refusals | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) |
| [`assertRelationshipAcceptsDetach(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — the three dissociate refusals alone, for a bulk arm that writes by key | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) |
| [`remove(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — delete an orphan through the unit of work | done | — | [semantics](#remove-is-here-so-orphan-deletion-does-not-grow-a-second-seam) |
| [`persist(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) + [`flush()`](../../../packages/schemas/src/State/AssociationReader.php) — write a newly created related record | done | — | [semantics](#persist-and-flush-are-the-write-half-of-the-same-seam) |
| [`AssociationTarget::$targetClass` / `$isToMany`](../../../packages/schemas/src/State/AssociationTarget.php) — what to load and in what shape | done | — | — |
| [`AssociationTarget::$isOwningSide`](../../../packages/schemas/src/State/AssociationTarget.php) — whether writing this side writes the database | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [`AssociationTarget::$isOrphanRemoval`](../../../packages/schemas/src/State/AssociationTarget.php) — whether detaching an item deletes it | done | — | [diverges](#orphanremoval-is-a-plain-bool-on-the-orm-3-mapping-object) |
| [`AssociationTarget::$isManyToMany` / `$mappedBy` / `$inversedBy` / `$isNullable` / `$orderBy`](../../../packages/schemas/src/State/AssociationTarget.php) — the cardinality, the two bidirectional field names, the join column's nullability and the collection's declared order | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [Service wiring](../../../packages/schemas/src/SylamentSchemasBundle.php) — `sylament_schemas.association_reader` plus a class alias, with an ignore-on-invalid `EntityManagerInterface` argument | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — the `#[Required]` setter every consumer tier reaches the reader through | done | — | [semantics](#the-accessor-trait-is-shared-with-the-table-and-action-tiers) |
| Composite identifiers | skipped | — | [diverges](#a-composite-identifier-is-refused-not-encoded) |
| Through-relations, morphs, pivot data | skipped | — | [diverges](#only-the-shapes-doctrine-states-in-one-mapping-are-read) |

Every row is **done** or **skipped** — the unit suite
([`AssociationReaderTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderTest.php))
covers all four association shapes the fixture entities declare (many-to-one,
the inverse one-to-many with orphan removal, the owning many-to-many, and the
**inverse** one-to-one), the five mapping-shape fields across six shapes (both
sides of a bidirectional many-to-many, a unidirectional one, an ordered
one-to-many, and a nullable and a not-null to-one), all three null answers (a scalar property, a non-entity
class, an anonymous class), the four operations refusing without an entity
manager, the record key against a persisted entity, and both key refusals — an
unpersisted entity and the composite-identifier
[`CompositePair`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/CompositePair.php)
fixture carried over from tables. The relationship seam is covered beside them:
`describeRelationship()`'s four refusals and its uninitialised-owner arm, both
query shapes asserted as DQL character for character and as the SQL SQLite
renders, the candidate negation with its mandatory `IS NULL` arm and a composed
`andWhere()` proving the two arms stay bracketed, the owner-without-an-identifier
refusal, and — over the Integration rig, which persists and flushes by hand
because [`EntityManagerFactory::seed()`](../../../packages/schemas/tests/Fixtures/Doctrine/EntityManagerFactory.php)
clears the identity map — attach and detach on a unidirectional many-to-many,
on both directions of the bidirectional `Post::$categories` ↔ `Category::$posts`
pair, the one-to-many associate/dissociate through `Author::$posts`, a detach
handed a reference rather than a hydrated record, the no-op attach of an
already-related record, and the three dissociate refusals one fixture each
([`Post::$sections`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/Post.php)
for orphan removal, `Post::$comments` for the NOT NULL join column, `Post::$replies`
for the setter that rejects null).

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

### findRecordKey() answers null where getRecordKey() throws

`getRecordKey()` is now a one-line wrapper: it calls `findRecordKey()` and throws
the "has none set" `LogicException` when the answer is null. The split exists
because two callers want opposite things from an entity that has never been
flushed. A relation-backed field speaking an option list by key has a bug if the
record it is rendering has no identifier — that is the throw. A `unique()`
constraint asking "which row should I exclude from the probe?" has no bug at all
— a create form's record is unflushed by definition, and the honest answer is
"nothing to exclude", which is exactly what `null` means to
[`UniqueValue`](../forms/can-be-validated.md).

Only the empty-identifier arm moved. The composite-identifier refusal stays on
`findRecordKey()`, so both callers still get it, and so does the "not a printable
value" throw inside the coercion: an identifier that exists but cannot be spoken
is an error either way, not "nothing to ignore".

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
covers deletion for free. [`attach()` and `detach()`](#a-write-lands-on-the-side-doctrine-persists)
are the third arm of the same argument, one tier up: they write an association
rather than a record, and they route through the reader for the same reason.

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

### `persist()` and `flush()` are the write half of the same seam

`remove()` alone covers the case where a component deletes a record it did not
create. The mirror case is a component that *creates* one: a relation-backed
field offered a value the option list does not carry has to make the related
entity before it can key it, and keying it needs an identifier, which only exists
after the insert. So the pair is `persist()` then `flush()` then
`getRecordKey()`, and `persist()` deliberately does not flush — the caller
decides where the write boundary sits, and a component creating several records
in one pass should pay for one flush rather than one per record.

`flush()` takes no entity because ORM 3's `EntityManager::flush(): void` takes
none either — the single-entity flush ORM 2 offered is gone, so the reader has no
narrower boundary to expose even if a caller wanted one.

### The relationship query is rooted at the target and needs no inverse mapping

A [`Relationship`](../../../packages/schemas/src/State/Relationship.php) is an
owner object and a property name, and nothing else — Filament's counterpart is an
Eloquent `Relation`, which is a query, a pair of models and a set of write
primitives at once, and Doctrine has no such object to port. Every fact about it
is read on demand, so the value object stays free of an `EntityManager` and the
reader stays the only seam that holds one.

`describeRelationship()` is `describe()` with the null answers turned into
refusals, because a relation-scoped table has nothing to fall back to: no entity
manager, an owner whose class is not mapped, a property Doctrine maps no
association at, and a **single-valued** association each get a message naming the
fix. The last is the mirror image of
[`requireSingularRelationshipTarget()`](entangles-state-with-singular-relationship.md)
one component over — that one refuses a collection where it wanted a record, this
one refuses a record where it wanted a collection. The owner's class is read off
the metadata rather than off `$owner::class`, so what is reported and embedded in
the DQL is the canonical mapped name; with native lazy objects (both rigs enable
them) that is `$owner::class` already. The owner itself must be a hydrated entity
or a PHP native lazy object: `getEntityClass()` runs `isTransient($owner::class)`
*before* any metadata read, and a classic `Proxies\__CG__\…` subclass carries no
entity attribute of its own — PHP does not inherit attributes — so the driver
reports it transient and the owner is refused as not a managed entity. Nothing in
this epic passes one.

Both queries select the **target** entity as their single root under a
caller-chosen alias, defaulting to `record`. That is load-bearing rather than
stylistic: every consumer downstream reads `getRootAliases()[0]` and the first
root entity to learn what the table lists, so a query rooted anywhere else would
answer the wrong class. The narrowing is then per shape:

- a many-to-many, either side, uni- or bidirectional, is
  `record.<id> IN (SELECT sy_related.<id> FROM <Owner> sy_owner JOIN sy_owner.<name> sy_related WHERE sy_owner.<id> = :sylament_relationship_owner)`
  — a subselect over the **owner's own** association, which is why no inverse
  mapping is needed and why the outer query gains no join;
- a one-to-many is `record.<mappedBy> = :sylament_relationship_owner`, straight
  onto the target's own to-one field.

The rejected shape was `SELECT record FROM Target record JOIN record.<inverse> …`:
it needs the mapping to be bidirectional, multiplies rows, and puts the join
cardinality in the way of the aggregate lane. `MEMBER OF` is the same objection —
it needs an inverse mapping — and is kept only as a possible later optimisation
for bidirectional mappings.

The candidate query is the negation, and the one-to-many arm is where it stops
being symmetrical: `record.<mappedBy> <> :owner` alone drops every record whose
field is null, because SQL's `<>` is unknown against null — so the arm is
`record.<mappedBy> IS NULL OR record.<mappedBy> <> :owner`, built as one `orX`
composite so a caller's later `andWhere()` (a search clause, a by-key `IN`)
brackets the pair instead of flattening the OR into the AND.

Three names are reserved inside these builders and a caller must not reuse them:
the aliases `sy_owner` and `sy_related`, and the parameter
`sylament_relationship_owner`. The owner is bound as the **object** — Doctrine
binds a managed entity by its identifier, typed as the column is — after
`getRecordKey()` refuses an owner that has none yet, which is the difference
between a clear refusal and a query that silently matches nothing. Neither query
orders anything: the mapping's `#[ORM\OrderBy]` is the *table's* default sort, not
the query's, so a clicked sort can replace it.

### A write lands on the side Doctrine persists

`attach()` and `detach()` write in memory and leave the flush to the caller, the
same boundary [`persist()`](#persist-and-flush-are-the-write-half-of-the-same-seam)
already draws. Both go through
[`PropertyAccessor::setAssociationOnRecord()`](property-accessor.md) — Symfony's
accessor diffs the whole desired list against what the property holds and calls
the record's own adder and remover per element — so a domain adder with logic in
it still runs, and the collection instance survives for Doctrine to diff. The
contract that buys is the one forms already states: a collection-valued
association needs an adder and a remover, a to-one a setter, or Symfony names
what is missing.

Which record is written is decided by the mapping, not by which side the caller
named. An inverse-side collection write emits **zero SQL** at flush, so a
relationship declared from the inverse side of a many-to-many is redirected: the
write lands on the *related* record's `mappedBy` collection with the owner as the
member. A one-to-many is always inverse, so it never writes a collection at all —
it points the target's `mappedBy` field at the owner, or at null. Attaching a
record the owner already holds is a no-op, caught by an identity check before the
accessor is asked to diff.

`detach()` runs `assertRelationshipAcceptsDetach()` first on every one-to-many,
and the order of its three refusals is what makes each of them reachable:

1. the owner's association declares `orphanRemoval` — removing from such a
   collection *deletes* the record, so dissociating is not something the mapping
   permits, and `DeleteAction` is the action that means what the caller meant;
2. the target's join column is `nullable: false` — read as `isNullable` off
   `describe($targetClass, $mappedBy)`, the owning side of the same association;
3. the target's setter or property rejects null —
   `PropertyAccessor::acceptsNullOnRecord()`, which is the PHP-level twin of (2)
   and fires where the column is nullable but the type is not.

Only the first of them fires on any one mapping, which is why the rig carries a
separate fixture per refusal rather than one entity tripping all three. A
many-to-many always passes: nothing is deleted and no column goes null, only a
join row disappears. The method is public and separate from `detach()` because a
bulk arm that dissociates **by key** — a DQL `UPDATE` rather than a per-record
write — has to run the same refusals before its statement, with no record in hand
to run them against.

### The owning-side and orphan-removal flags are what a writer needs, not trivia

`AssociationTarget` carries nine fields because a writer cannot act correctly on
fewer. `targetClass` and `isToMany` say what to load and in what shape.
`isOwningSide` says whether writing this side changes the database at all — an
inverse side is a read-through, so a component that writes one without also
maintaining the owning side saves nothing, which is exactly the trap the fixture
entities pin (`Post::$profile` is the **inverse** one-to-one; `Profile::$post`
owns it). `isOrphanRemoval` says whether dropping an item deletes the entity or
merely detaches it — the difference between a cleanup and data loss, and not
something a caller may guess.

The five the relationship seam added answer the questions a *relationship* writer
asks on top of those. `isManyToMany` picks between the two query shapes and the
two write shapes — a many-to-many is a collection on both sides, everything else
has a to-one field somewhere. `mappedBy` names the owning field on the target and
is read on inverse sides only (an owning side has none, and Doctrine's
`getAssociationMappedByTargetField()` throws rather than answering null there);
`inversedBy` is its mirror, the inverse field on the target, read on owning sides
only and null where the mapping is unidirectional. Between them they are how a
write reaches the side Doctrine persists, and how a one-to-many query finds the
field to filter on. `isNullable` is a to-one owning side's join column, and
deliberately `true` for every other shape — a collection and an inverse side have
no column of their own to be null, so the flag reads as "nothing here forbids a
null" rather than as a claim about a column that does not exist. `orderBy` is the
mapping's own collection order, exactly as declared: Doctrine's docblock types it
`array<string, 'asc'|'desc'>` but hands `#[ORM\OrderBy(['position' => 'ASC'])]`
back as `'ASC'`, so the field is typed `array<string, string>` and a consumer that
needs a canonical direction lower-cases it.

### The accessor trait is shared with the table and action tiers

The reader is injected onto a consumer by a `#[Required]` setter with a
`new AssociationReader()` fallback, the same idiom
[`CanBeValidated`](../forms/can-be-validated.md) uses for `RecordTypeReader`: a
container-built consumer gets the container's reader, one built with `new` gets
an entity-manager-less one that answers `hasEntityManager()` false and throws on
everything needing an EM.

The pair lived on the component-tier
[`BelongsToRecord`](belongs-to-record.md) until the relationship seam gave two
non-components the same need — `Table` builds its base query through the reader
and `Action` writes an association through it — so it is
[`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php)
now, composed by `BelongsToRecord` unchanged. It keeps the `Components/Concerns/`
namespace under the repo's placement rule: a trait a component uses at all lives
there, whether or not something else uses it too.

The blast radius is worth stating because it is wider than it looks.
`BelongsToRecord` sits on the base `Component`, so **every** schemas component —
and every component of every package built on schemas — consumes the reader
service, where before nothing did and the container pruned it. The service's
`EntityManagerInterface` argument is `nullOnInvalid()` for exactly that reason:
the schemas bundle boots inside forms', actions', notifications' and tables' test
kernels, none of which registers Doctrine, and the setter has to autowire cleanly
there. A container-compile failure in one of those suites is the signal that the
nullability wiring broke.

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
