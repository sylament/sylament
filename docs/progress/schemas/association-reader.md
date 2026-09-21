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
I reference a related entity by its key, what key does a record answer to, what query
lists the options, and how do I delete an orphan — instead of each growing its
own `EntityManager` dependency and its own metadata reads. The relationship arm
adds four more to the list — which records does this owner's collection hold,
which ones does it not, may an existing record join this collection at all, and
how do I put one in or take one out — asked through
the same collaborator and answered from the same mapping. A tenth question rides
beside them for the create paths: is this record still missing an identifier
component the **application** has to supply. The seam is also what
keeps Doctrine optional at runtime: an application with no `EntityManager` gets a
reader that reports [no association anywhere](#a-null-target-means-no-doctrine-answer-not-no-such-property)
rather than a container that cannot compile.

A record key of any width travels through all of it. The reader mints, finds and
decodes one through [`RecordKey`](record-key.md) and narrows every query through
`IdentifierConstraint`, so an entity keyed by two scalar fields, by an
association plus a scalar, by two associations or by an association alone is a
relation **target** wherever a single-`id` entity is — and
[the reader owns no copy of the codec's rules](#a-key-of-any-width-is-the-codecs-and-the-reader-keeps-only-the-route).
The **owner** of a relationship query is the one axis still narrower than that,
and it is [refused by name](#a-composite-owner-is-refused-by-name-on-the-query-path-only).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — this is Sylament-invented, so it is diffed against the sources it
wraps rather than a Filament port target:

- [`orm/src/Mapping/ClassMetadata.php`](../../../vendor/doctrine/orm/src/Mapping/ClassMetadata.php)
  — every metadata read this class makes
- [`orm/src/Mapping/AssociationMapping.php`](../../../vendor/doctrine/orm/src/Mapping/AssociationMapping.php)
  — the per-association mapping object behind `getAssociationMapping()`
- [`orm/src/EntityManager.php`](../../../vendor/doctrine/orm/src/EntityManager.php)
  — `getReference()` and `find()`, the two routes a key takes; `getReference()`'s
  ORM 3 signature is [nullable](#getreference-throws-where-doctrine-returns-null)
- [`schemas' RecordKey and IdentifierConstraint`](record-key.md) — the key codec
  and the predicate builder this class delegates to, consumed as they landed
- [`tables' HasTable::resolveQueryRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php)
  — the record-key semantics this class mirrors, trailed in
  [has-table.md](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key)

## Surface

Every point of the reader and its value object. _Blocked by_ names what has to
land first. _Detail_ carries a **semantics** link (a permanent note in
[Semantics](#semantics)), a **diverges** link (the permanent note in
[Divergences](#divergences)) and/or a **detail** link (the write-up of an
unfinished row), or `—`. Every row is Sylament-only, so no row has
a _Ports_ link.

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`__construct(?EntityManagerInterface, ?PropertyAccessor)`](../../../packages/schemas/src/State/AssociationReader.php) + [`hasEntityManager()`](../../../packages/schemas/src/State/AssociationReader.php) — the optional-Doctrine seam, and the accessor the writes go through | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`describe(string $class, string $property)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at one property, or null | done | — | [semantics](#a-null-target-means-no-doctrine-answer-not-no-such-property) |
| Transient / anonymous-class guarding inside `describe()` | done | — | [semantics](#istransient-answers-false-for-an-anonymous-class-so-it-is-not-the-whole-guard) |
| [`getReference(string $class, int\|string $id)`](../../../packages/schemas/src/State/AssociationReader.php) — the record a key names, decoded before anything is looked up | done | — | [semantics](#getreference-decodes-first-and-then-picks-its-route) · [diverges](#getreference-throws-where-doctrine-returns-null) |
| [`getRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the string key an entity is spoken by, of any width | done | — | [semantics](#the-record-key-is-tables-semantics-moved-onto-the-mapping) |
| [`findRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the same key, or null for an entity whose identifier is not complete yet | done | — | [semantics](#findrecordkey-answers-null-where-getrecordkey-throws) |
| [`hasIncompleteAssignedIdentifier(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — whether a component the **application** must assign is missing | done | — | [semantics](#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) |
| [`createOptionQuery(string $targetClass, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the base query an option list narrows | done | — | [semantics](#the-option-query-is-a-starting-point-not-a-result) |
| [`Relationship`](../../../packages/schemas/src/State/Relationship.php) — one owner record's association, by name | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`describeRelationship(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at a relationship, refused rather than null | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`createRelationshipQuery(Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the owner's related records, rooted at the target | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`createRelationshipCandidateQuery(Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — every target record NOT related to this owner | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`attach(Relationship, object $related)`](../../../packages/schemas/src/State/AssociationReader.php) — make a record one of the owner's, in memory | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) · [diverges](#no-to-many-relation-verb-moves-a-primary-key) |
| [`detach(Relationship, object $related)`](../../../packages/schemas/src/State/AssociationReader.php) — the mirror removal, after the dissociate refusals | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) |
| [`assertRelationshipAcceptsAssociate(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — whether an existing record may join this collection at all | done | — | [diverges](#no-to-many-relation-verb-moves-a-primary-key) |
| [`assertRelationshipAcceptsDetach(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — the four dissociate refusals alone, for a bulk arm that writes by key | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) · [diverges](#no-to-many-relation-verb-moves-a-primary-key) |
| [`remove(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — delete an orphan through the unit of work | done | — | [semantics](#remove-is-here-so-orphan-deletion-does-not-grow-a-second-seam) |
| [`persist(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) + [`flush()`](../../../packages/schemas/src/State/AssociationReader.php) — write a newly created related record | done | — | [semantics](#persist-and-flush-are-the-write-half-of-the-same-seam) |
| [`AssociationTarget::$targetClass` / `$isToMany`](../../../packages/schemas/src/State/AssociationTarget.php) — what to load and in what shape | done | — | — |
| [`AssociationTarget::$isOwningSide`](../../../packages/schemas/src/State/AssociationTarget.php) — whether writing this side writes the database | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [`AssociationTarget::$isOrphanRemoval`](../../../packages/schemas/src/State/AssociationTarget.php) — whether detaching an item deletes it | done | — | [diverges](#orphanremoval-is-a-plain-bool-on-the-orm-3-mapping-object) |
| [`AssociationTarget::$isManyToMany` / `$mappedBy` / `$inversedBy` / `$isNullable` / `$orderBy`](../../../packages/schemas/src/State/AssociationTarget.php) — the cardinality, the two bidirectional field names, the join column's nullability and the collection's declared order | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [Service wiring](../../../packages/schemas/src/SylamentSchemasBundle.php) — `sylament_schemas.association_reader` plus a class alias, with an ignore-on-invalid `EntityManagerInterface` argument | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — the `#[Required]` setter every consumer tier reaches the reader through | done | — | [semantics](#the-accessor-trait-is-shared-with-the-table-and-action-tiers) |
| Composite records as relation **targets** — keyed, referenced, listed, offered and written at any identifier width | done | — | [semantics](#a-key-of-any-width-is-the-codecs-and-the-reader-keeps-only-the-route) |
| Composite records as relationship-query **owners** | blocked | [M7 · 5](../roadmap.md#m7--composite-identifiers) | [detail](#a-composite-owner-is-refused-by-name-on-the-query-path-only) |
| Through-relations, morphs, pivot data | skipped | — | [diverges](#only-the-shapes-doctrine-states-in-one-mapping-are-read) |

One row is **blocked** and every other is **done** or **skipped**; the file keeps
its `done` status on the same terms
[`record-key.md`](record-key.md#owner-scoping-is-reserved-not-missing) does, whose
owner-scoping row waits on the same epic.

The unit suite
([`AssociationReaderTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderTest.php),
with M7 · 4's
[`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php),
[`AssociationReaderCompositeQueryTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeQueryTest.php)
and
[`AssociationReaderIdentifierRelationTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderIdentifierRelationTest.php)
beside it)
covers all four association shapes the fixture entities declare (many-to-one,
the inverse one-to-many with orphan removal, the owning many-to-many, and the
**inverse** one-to-one), the five mapping-shape fields across seven shapes (both
sides of a bidirectional many-to-many, a unidirectional one, an ascending and a
**descending** ordered one-to-many — the descending one being what tells the
pass-through apart from a normalised direction — and a nullable and a not-null
to-one), all three null answers (a scalar property, a non-entity
class, an anonymous class), the four operations refusing without an entity
manager, the record key against a persisted entity at every identifier flavour,
and the two refusals that remain — an unpersisted entity, and a component that is
set but not printable. The relationship seam is covered beside them:
`describeRelationship()`'s four refusals and its uninitialised-owner arm, both
query shapes asserted as DQL character for character and as the SQL SQLite
renders, the candidate negation with its mandatory `IS NULL` arm and a composed
`andWhere()` proving the two arms stay bracketed, the owner-without-an-identifier
refusal, the composite-owner refusal on both builders and both arms, and — over
the Integration rig, which persists and flushes by hand
because [`EntityManagerFactory::seed()`](../../../packages/schemas/tests/Fixtures/Doctrine/EntityManagerFactory.php)
clears the identity map — attach and detach on a unidirectional many-to-many,
on both directions of the bidirectional `Post::$categories` ↔ `Category::$posts`
pair (the owning direction re-read across a `clear()`, the inverse one written
through the owning collection, and both named in one case that reads the
untouched inverse collections as proof of where each write landed —
[`Category`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/Category.php)'s
adders deliberately heal nothing, so a mis-routed write really does lose the row),
the one-to-many associate/dissociate through
`Author::$posts`, a detach handed a reference rather than a hydrated record, the no-op attach of an
already-related record, and the four dissociate refusals one fixture each
([`Post::$sections`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/Post.php)
for orphan removal, the identifier half for the re-keying refusal, `Post::$comments`
for the NOT NULL join column, `Post::$replies`
for the setter that rejects null).

Three properties of a composite target are pinned by their own cases, because a
green suite would otherwise hide each of them: a malformed key is refused
**before any statement**, read off a recording middleware rather than off a
platform's leniency; a reference by key is taken against a **cleared** identity
map, which is the only state in which the loading route's absence is a
`TypeError`; and every expected key in an assertion is a string literal, never
one minted through the codec in the expectation.

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
- **Every operation throws a `LogicException`**, because
  `getReference()`, `getRecordKey()`, `createOptionQuery()`, `remove()` and the
  rest have
  no meaningful negative answer: a caller reaching them has already decided it is
  handling a relationship, so a silent null would corrupt state instead of
  failing. One message covers them all, and it names both causes (a reader built
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

### A key of any width is the codec's, and the reader keeps only the route

`getRecordKey()` and `findRecordKey()` are one line each — `RecordKey::of()` and
`RecordKey::find()`, with the entity manager passed per call — and
`getReference()` decodes through `RecordKey::toIdentifier()` before it looks
anything up. The reader holds **no** identifier narrowing of its own: the
`Stringable`/`is_scalar` coercion it used to carry was the fourth hand-written
copy of one rule, and
[the codec owns that rule now](record-key.md#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may).
Minting a key and finding one branch on nothing — neither `getRecordKey()` nor
`findRecordKey()` asks the identifier's width or flavour — which is what makes
"a composite record is a relation target wherever a single-`id` record is" a
property of the code rather than a claim about it.

What the delegation moved, exactly — two answers changed, and both are the
codec's stated contract rather than a decision taken here:

- An identifier that is boolean **`false`** now keys to `'0'` where the reader
  threw. `printable()` normalises a `bool` to `'1'`/`'0'` deliberately, because a
  plain `(string)` cast prints `false` as the empty string, which is an illegal
  empty component; the reader's old order called that value blank and refused it.
- A **blank or whitespace-only assigned string** identifier makes
  `findRecordKey()` answer `null` where it threw — `getRecordKey()` still throws
  `is not a printable value`. That is what lets `unique(ignoreRecord:)` and
  `Select::relationship(ignoreRecord:)` run on a create form over an
  assigned-key class: there is nothing to exclude yet, which is an answer rather
  than an error.

Two things the delegation did **not** move, and both are worth stating because a
reader assumes the opposite. Refusing a key is still cheap, and it never binds a
fragment of the submitted text — but the two shapes of by-key read differ, and
the difference is a statement. A by-key **lookup** executes **none**:
`getReference()` refuses on the decode above, and `Select`'s selected-record
lookup asks `IdentifierConstraint::equals()`, which answers `null` for a key that
decodes to nothing — `createScopedOptionQuery()` has built a `QueryBuilder` by
then, so what is never built or run is the statement. A by-key **batch** read is one
statement narrowed to `1 = 0`: `Select`'s label list hands its scalar entries to
`IdentifierConstraint::in()`, and a list whose every scalar entry is malformed
leaves nothing to compare, so the query runs and returns no rows (a list with no
scalar entry at all issues none). And the single-identifier DQL every consumer
emits is today's text, byte for byte — `IdentifierConstraint` emits
`alias.id = :base` and `alias.id IN (:base)` unchanged for a one-field class, so
nothing that pinned those strings moved.

### `getReference()` decodes first, and then picks its route

The signature is `getReference(string $class, int|string $id): object`, and it
kept the `int|string` on purpose: a raw `int` prints to the very key a
single-identifier class speaks, and a raw `int` handed to a two-field class
decodes to nothing and is refused loudly, so no consumer had to move. The body is
two steps and the order is the whole point.

**Decode, then look up.** `RecordKey::toIdentifier()` runs the class-aware grammar
check, the per-type shape check and the print-back compare; `null` out of it is
today's `LogicException` (`No [%s] carries the identifier [%s], so it cannot be
referenced.`) raised **before** any query. So `05`, ` 5`, `5abc` and `1~2` against
a one-field class each name no record at zero statements, where an untyped bind
would have aliased a row on PostgreSQL under emulated prepares or raised `22P02`
under native ones —
[the hole the codec exists to close](record-key.md#a-malformed-key-never-reaches-the-database).

One **single-identifier** shape pays for that strictness, and it is a delta beyond
the ones this epic set out to make. Where a DBAL type's `convertToPHPValue()`
reads only the database form —
[a type that cannot read its own printed form](record-key.md#a-type-that-cannot-read-its-own-printed-form-mints-keys-that-never-decode) —
the decode refuses the very key `getRecordKey()` minted, while the base handed
that printed key straight to Doctrine and the foreign-key write landed wherever
the id property admits a string. So a relation-backed `Select` or `CheckboxList`
now throws on save and a by-key `DetachBulkAction` answers the danger toast where
the base wrote. The by-key **predicates** are no worse than the base, which never
matched such a column either; the remedy is the codec's, and the roadmap's ledger
carries it.

**Then `find()` where any identifier field is an association, and
`getReference()` everywhere else.** The predicate is
`array_any($metadata->identifier, $metadata->hasAssociation(...))` — never
`isIdentifierComposite`, which is false for a **single derived identity** and so
misses one of the three flavours that need the loading route. Doctrine's
`ProxyFactory` assigns a scalar identifier component straight into a typed
association property, so `getReference()` against a **cold** identity map is a
`TypeError` for a derived identity, a join entity and a single derived identity
alike, while succeeding for all three once the record is already managed — which
is precisely the defect a seeded test hides and a first cold request finds.
`find()` works in every state, at one statement.

The scalar route keeps its **zero** statements, which is why "always `find()`" was
rejected: it would charge every single-identifier relationship write a `SELECT`
it does not pay today. One class of target pays anyway, and it is Doctrine's
doing rather than this predicate's: a class that declares `subClasses` is routed
through `find()` by `EntityManager::getReference()` itself, so an inheritance
**root** costs one statement and refuses a missing row by name. And both routes
hand back the very instance an owner's
loaded collection holds — the identity-map instance, loaded before or after the
call — which is the property `detach()`'s `!==` filter and `attach()`'s strict
`in_array()` are built on.

Three consequences recorded rather than closed. A **well-formed** key naming no row
**of that class** is a lazy phantom proxy on the reference route, exactly as it was
before, and a `LogicException` on the `find()` route — the two routes disagree, and
the divergence [below](#getreference-throws-where-doctrine-returns-null) says why
neither answer is a null. *Of that class* is load-bearing under inheritance: a
**sibling** subclass's key is well-formed for the columns the root declares, so a
reference asked for on the wrong subclass is a proxy of the class that was asked
for, and attaching it writes a join row the relationship query then never lists,
because the discriminator excludes it. A key-forging client is not what produces
that; a consumer passing the wrong `targetClass` is.

Next, one decode the codec cannot police: a scalar identifier typed as a
`BackedEnum` answers a forged key with PHP's own `ValueError` out of
`getReference()` rather than the `LogicException` this member's docblock promises.
`RecordKey` cannot see `enumType` and is frozen, and the `find()` route answers the
same key cleanly. It costs zero statements either way, and both real callers
already stand in front of it — a relationship `Select`'s `Choice` rule runs before
the forms write, and `DetachBulkAction`'s `try` toasts it — so it is recorded
rather than caught.

And a to-many write of *N* options onto a
foreign-identifier target costs *N* primary-key `SELECT`s; one
`IdentifierConstraint::in()` pre-fetch would make those `find()`s free, and is the
known remedy if it ever matters. No batched member is added for one consumer and a
rare shape.

### An incomplete assigned identifier is the question the create paths ask

`hasIncompleteAssignedIdentifier(object $entity): bool` answers one thing: is the
record missing an identifier component **the application** has to supply. It is
not "is this record keyable yet", and the difference is the reason the member
exists rather than a `findRecordKey() === null` test at the call sites.

`RecordKey::find()` answers `null` for two unrelated reasons. One is the defect
the create paths must refuse: a form that never collected a key half, so a row
would commit with an empty component and no option could ever name it. The other
is ordinary and correct: an identifier **association** whose target's generated id
is still pending — every derived-identity child of an owner that has not been
flushed yet, which is what a relationship `Repeater` on a *create* form holds.
Doctrine writes that child in one flush, deferring the child's identifier to the
parent's insert, so refusing it would refuse a working form with advice that is
false. One shape is Doctrine's own limit rather than ours: a **JOINED-inheritance**
child cannot be inserted in its owner's flush at all — `JoinedSubclassPersister`
throws *Unable to find … entity identifier associated with the UnitOfWork* — and
this predicate passes it, correctly, leaving the failure where it belongs.

So the member asks the mapping instead, and it asks only of the identifiers this
epic made keyable. A **plain single scalar** identifier answers `false` at the
entry, before anything else is read: the relation layer has always keyed such a
class, an application may fill that field from anywhere — including a listener no
class-level mapping shows — and a new refusal there would be the
single-identifier regression this milestone forbids. So an assigned single scalar
identifier left blank stays HEAD's behaviour, unrefused; that is
[M7 · 6](../roadmap.md#m7--composite-identifiers)'s to close.

Everything else — a composite identifier, or one carrying an association — goes to
the private, recursive `isMissingAssignedIdentifierComponent()`. Three mapping
facts short-circuit it to `false`, and all three say the same thing: something
other than the form fills this identifier at the flush. `usesIdGenerator()` is
Doctrine's own; `hasLifecycleCallbacks(Events::prePersist)` is a `#[PrePersist]`
callback of the class; `isset($metadata->entityListeners[Events::prePersist])` is
a mapped entity listener. Otherwise every identifier field is read: an
**association** component must be an object and must itself answer `false` to the
same question — recursing into the **private** member rather than the public one,
so that behind an identifier association a single assigned scalar still counts —
and a **scalar** component must not be
[`blank()`](../support/helpers.md). A pending generated id behind an association
therefore passes, and a missing locale does not.

One residual, recorded rather than closed: a **global** `prePersist` listener on
the event manager can assign a composite half too, and no class-level mapping
shows it, so a record relying on one is refused here. The three short-circuits
cover everything the class itself declares; covering the global case would need an
opt-out nothing has asked for.

Two callers, both refusing **before anything is flushed**: `Select`'s
`createOptionUsing` default, which refuses before `persist()`, and `Repeater`'s
relationship save, which refuses after the collection write and before the item-key
map is re-read. Both name the field list that would fix it. Neither is validation —
a taken key, a changed key half and a duplicate inside one unit of work are
[M7 · 6](../roadmap.md#m7--composite-identifiers)'s — and neither restores the
in-memory graph on the way out: a refusal here is a developer error that ends the
request, nothing has been flushed, and the two write paths that could have
persisted something have not run.

### The record key is tables' semantics, moved onto the mapping

`getRecordKey()` is the same answer
[`HasTable::resolveQueryRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php)
gives, and both reach it through the same codec: the identifier comes
from `ClassMetadata::getIdentifierValues()` — never from an `id` property
convention — a `Stringable` component is unwrapped, a `BackedEnum` becomes its
backing value, a `bool` becomes `'1'`/`'0'`, and a component that is unset,
non-scalar, blank or carrying a NUL byte is a `LogicException` rather than a key
no lookup could resolve. An entity mapped on `code` therefore keys by `code` with
nothing configured; an entity mapped on `host` and `path` keys by both, joined
and escaped by [the grammar](record-key.md#the-grammar); and an entity that was
never given its identifier fails at the point of binding instead of producing a
row the client cannot address.

The one refusal a reader would not predict belongs to the codec rather than to
either consumer: completeness is **counted**, never tested for emptiness, because
`getIdentifierValues()` *drops* a null component on the composite branch. A
half-filled pair would otherwise read as complete — and the count is compared at
every level of a chained derived identity, not only at the top.

### findRecordKey() answers null where getRecordKey() throws

`getRecordKey()` throws where `findRecordKey()` answers `null`, and the split
exists because two callers want opposite things from a record whose identifier is
not complete. A relation-backed field speaking an option list by key has a bug if
the record it is rendering cannot be keyed — that is the throw. A `unique()`
constraint asking "which row should I exclude from the probe?" has no bug at all
— a create form's record is unkeyed by definition, and the honest answer is
"nothing to exclude", which is exactly what `null` means to
[`UniqueValue`](../forms/can-be-validated.md). `Select::relationship(…,
ignoreRecord:)` is the third caller on the same terms: it reads this pair to drop
one record from an option list, and an unkeyed one drops nothing
([select.md](../forms/select.md)).

The line between them is the codec's, and it moved once: a component that is
`null` **or still blank** counts as not assigned yet, so an assigned string key
sitting at `''` until a form fills it answers `null` here rather than throwing.
Only that arm is soft. A component that exists and cannot be spoken — non-scalar,
not valid UTF-8, carrying a NUL — is `is not a printable value` from **both**
members, because an identifier that exists and cannot be printed is an error
either way, not "nothing to ignore".

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

Where the identifier is **assigned** rather than generated, that order has one
more step in front of it:
[`hasIncompleteAssignedIdentifier()`](#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask)
runs before `persist()`, because a record with half a key flushes successfully
and only fails when something tries to name it.

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
reports it transient and the owner is refused as not a managed entity.

Both queries select the **target** entity as their single root under a
caller-chosen alias, defaulting to `record`. That is load-bearing rather than
stylistic: every consumer downstream reads `getRootAliases()[0]` and the first
root entity to learn what the table lists, so a query rooted anywhere else would
answer the wrong class. The narrowing is then per shape:

- a many-to-many, either side, uni- or bidirectional, is
  `EXISTS (SELECT 1 FROM <Owner> sy_owner JOIN sy_owner.<name> sy_related WHERE sy_owner.<id> = :sylament_relationship_owner AND <correlation>)`
  — a subselect over the **owner's own** association, which is why no inverse
  mapping is needed and why the outer query gains no join;
- a one-to-many is `record.<mappedBy> = :sylament_relationship_owner`, straight
  onto the target's own to-one field.

The correlation is
[`IdentifierConstraint::correlate()`](record-key.md#the-operations-and-their-dql)
and nothing else — `sy_related.id = record.id` for a single-`id` target, one
`AND`ed equality per identifier field for every other one. **The obvious
spellings are both silently wrong.** `sy_related = record`, comparing two
identification variables, compiles to the **first identifier column alone** and
returns rows that are not related at all; `record MEMBER OF sy_owner.<name>`
compares every inverse join column against the target's *first* identifier column
and returns nothing. Both are Doctrine's own, neither errors, and the trap is
[recorded on the codec](record-key.md#comparing-two-identification-variables-compares-the-first-identifier-column-only)
because it constrains every correlation in the toolkit rather than this one.
`MEMBER OF` is therefore not a later optimisation for bidirectional mappings; it
is a shape that cannot be used at all.

`SELECT 1` is legal DQL in a subselect — it parses to `SELECT 1 AS sclr_N` — so
the projection needs no per-flavour branch, and the builder still carries exactly
**one** parameter, which is what keeps every consumer that mints an alias off
`count($query->getParameters())` where it was. The single-identifier rows are the
rows the old `record.<id> IN (SELECT sy_related.<id> …)` returned, and the
conversion is plan-neutral for the related query and an improvement for the
candidate one on PostgreSQL, where `NOT EXISTS` plans as a Hash Anti Join against
`NOT IN`'s hashed sub-plan filter.

There is **no flavour branch**, and the cheap test would have been wrong: a
**single derived identity** reports `isIdentifierComposite === false`, and it is
the one flavour the old subselect did not even refuse — it emitted the
association as a bare path in a `SELECT` list, which Doctrine rejects as
*"Invalid PathExpression"* at execution with no message of ours in front of it.

The candidate query is the negation. The many-to-many arm is the same string
prefixed `NOT `; the one-to-many arm is where it stops being symmetrical, because
`record.<mappedBy> <> :owner` alone drops every record whose field is null — SQL's
`<>` is unknown against null — so the arm is
`record.<mappedBy> IS NULL OR record.<mappedBy> <> :owner`, built as one `orX`
composite so a caller's later `andWhere()` (a search clause, a by-key `IN`)
brackets the pair instead of flattening the OR into the AND. That arm also runs
[`assertRelationshipAcceptsAssociate()`](#no-to-many-relation-verb-moves-a-primary-key)
first, so a relationship whose every candidate would be a theft refuses at the
first options read rather than offering them.

Three names are reserved inside these builders and a caller must not reuse them:
the aliases `sy_owner` and `sy_related`, and the parameter
`sylament_relationship_owner`. Doctrine refuses either alias as a caller's own
(`'sy_related' is already defined`), so the reservation is enforced rather than
merely stated. The owner is bound as the **object** — Doctrine
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

That redirection is only witnessable against an entity that does **not** heal it.
A bidirectional adder that calls its counterpart back (`Category::addPost()`
calling `Post::addCategory()`, the shape `make:entity` generates) makes a
wrong-side write land on the owning collection anyway, so the branch selector
above passes every assertion no matter which arm it picks. The fixture pair drops
those callbacks for exactly that reason: the inverse collection stays untouched
after a write, and the case reads it as the evidence of which side was written.

`attach()` runs one guard on every one-to-many —
[the re-keying refusal](#no-to-many-relation-verb-moves-a-primary-key) — and
`detach()` runs `assertRelationshipAcceptsDetach()`, whose four refusals are
ordered so that each of them is reachable:

1. the owner's association declares `orphanRemoval` — removing from such a
   collection *deletes* the record, so dissociating is not something the mapping
   permits, and `DeleteAction` is the action that means what the caller meant;
2. the target's mapped-by field is part of the target's own **identifier** —
   nulling it would erase half the record's identity, so `DeleteAction` is again
   the action that was meant;
3. the target's join column is `nullable: false` — read as `isNullable` off
   `describe($targetClass, $mappedBy)`, the owning side of the same association;
4. the target's setter or property rejects null —
   `PropertyAccessor::acceptsNullOnRecord()`, which is the PHP-level twin of (3)
   and fires where the column is nullable but the type is not.

Only the first of them fires on any one mapping, which is why the rig carries a
separate fixture per refusal rather than one entity tripping all four. The second
is ordered **before** the third deliberately: an `#[Id]` to-one's join column is
`nullable: false` in `ClassMetadata`, so such a mapping was already refused —
by (3), advising the developer to make the column nullable, which for an
identifier column is impossible. The refusal did not move; the advice did.
A many-to-many always passes all four: nothing is deleted and no column goes
null, only a join row disappears. The method is public and separate from
`detach()` because a bulk arm that dissociates **by key** — a DQL `UPDATE` rather
than a per-record write — has to run the same refusals before its statement, with
no record in hand to run them against.

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
write reaches the side Doctrine persists, how a one-to-many query finds the
field to filter on, and what the re-keying guard tests against the target's
identifier. `isNullable` is a to-one owning side's join column, and
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

## Details

### A composite owner is refused by name, on the query path only

The two relationship **query** builders refuse an owner whose own identifier is
composite, in `getRelationshipOwner()`, before any statement:

> `Records of [%s] carry a composite identifier, which cannot own a relationship query yet: Doctrine refuses to bind such a record as a query parameter. Scope the query from a single-identifier owner.`

The refusal is Doctrine's rather than ours, and it existed before the guard did —
as a side effect of `getRecordKey($owner)` throwing. With the key delegated to the
codec that side effect is gone, and what replaced it is not one failure but two,
both at **run** time and both from a different layer: the one-to-many arm dies in
the parser (`QueryException`, *"A single-valued association path expression to an
entity with a composite primary key is not supported"*), and the many-to-many arm
in parameter processing (`ORMInvalidArgumentException`, *"Binding an entity with a
composite primary key…"*). The explicit guard puts one named message in front of
both.

Three things about its shape.

The predicate is `ClassMetadata::$isIdentifierComposite` **alone**, which admits
a **single derived identity** owner — `#[Id]` on one association, no scalar half
— and that is deliberate: such an owner binds as an entity parameter and scopes
correctly on both arms of both queries, across owners with disjoint sets. The
narrower predicate panels uses for its own interim guards would refuse a shape
that works, which costs extra code to produce a false "not built yet".

The phrase *composite identifier* stays in the message on purpose, where the rest
of the milestone avoids it. It is **true** under this predicate, and the
milestone's `Done when` clause greps `packages/*/src` for exactly that phrase — so
this block is the tripwire that proves
[M7 · 5](../roadmap.md#m7--composite-identifiers) deleted it.

The guard stays on the **query** path and is not hoisted into
`describeRelationship()`. `attach()`, `detach()`, `describeRelationship()` and
both assertion members work for a composite owner today and go on working; a
composite owner can be written to and read from, it simply cannot be *bound* as a
query parameter. Hoisting the guard would break four working members to tidy one.
`getRecordKey($owner)` still runs after it, so an owner with no identifier at all
keeps its own `has none set` refusal, and the owner's identifier field in the
subselect is a private `$metadata->identifier[0]` read — safe **because** the
guard exists, not because of where it runs: the field is read while the DQL is
composed and `getRelationshipOwner()` is the last call of both builders, as it has
always been.

## Divergences

### No to-many relation verb moves a primary key

Doctrine will happily re-point a to-one association that is part of the target's
own identifier, and the `UPDATE` it emits moves the row's **primary key**. Where
that association is what a relationship is declared over, every **to-many**
relation verb inherited the behaviour, and each one is a silent data defect rather
than an error:
associating an existing record **steals it from its other owner**; where the new
owner already holds that key it is a `UniqueConstraintViolationException` that
closes the `EntityManager`; and every candidate such a relationship could offer is
by definition another owner's record, because the `IS NULL` arm can never match a
primary-key half. The join-entity-with-payload shape — `Article 1—* ArticleTranslation(article, locale)`
— is the commonest composite mapping there is, so this is not an exotic corner.

One predicate answers it —
`! $target->isManyToMany && in_array($target->mappedBy, $targetMetadata->identifier, true)`
— at four points under two messages: three of them share the associate refusal
through one private builder, and the detach point carries its own advice, because
it is one rule:

1. `assertRelationshipAcceptsAssociate()`, called inside the one-to-many arm of
   `createRelationshipCandidateQuery()`, so an `AssociateAction` refuses at the
   first options read — the moment every other reader refusal fires — and a
   consumer's `recordSelectOptionsQuery()` modifier never sees a query that offers
   only thefts.
2. `attach()`'s one-to-many arm, refusing exactly the state that re-keys: an
   **existing** record whose mapped-by value is an object **other than** this
   owner. A record the `EntityManager` does not manage yet has no primary key to
   move, so it passes whatever its mapped-by holds — being written under this
   owner is precisely what a create form building its child does, including the
   arm whose enabled relationship `Select` already picked a different owner — and
   so does a record pointing at nobody or at this owner. `CreateAction`'s
   attach-before-persist and panels' `attachRecordToParent()` are therefore
   untouched. The comparison is instance identity, which is what Doctrine's
   identity map guarantees for a managed owner.
3. `assertRelationshipAcceptsDetach()`, [above](#a-write-lands-on-the-side-doctrine-persists),
   where the shape was already refused for the wrong reason and only the advice
   changed.
4. forms' to-many relation-backed write — the `Select::multiple()` and
   `CheckboxList` arm of `HasRelationship::saveStateToRelationship()` — which calls
   (1) as the **first** statement of its to-many arm, before the per-key
   `getReference()` loop, so a write about to be refused costs no `SELECT` at all.
   Selecting another owner's child there re-keys it through the owner's adder,
   which is the forms twin of the associate defect.

The predicate is deliberately narrow: re-pointing an **ordinary** child at a new
owner stays the pinned feature it has always been, and a many-to-many never
refuses, because a join row is not the target's identity. What the refusal costs
the caller is named in its own message: create the record under this owner, or
move the association out of the identifier — and for join-entity children with a
payload, `Repeater::relationship()` is the field that creates and deletes them
and never re-points one.

The heading says **to-many** for a reason, and it is the one thing to read off
this section before relying on it. A **to-one** relation-backed write still moves
a primary key and nothing here refuses it: `Select::make('post')->relationship()`
on an *edit* form of a derived-identity record — and the same field inside an
existing join-entity repeater item, which is the field this trail recommends for
join entities — writes the picked reference onto the identifier half, and
Doctrine emits M1's `UPDATE` verbatim, closing the `EntityManager` when the new
key is taken. The guard cannot be the one above, because a **create** form has to
keep writing that field; it is a key-**change** guard, which is
[M7 · 6](../roadmap.md#m7--composite-identifiers)'s, and the roadmap's site ledger
carries the row.

Recorded rather than closed: `DetachBulkAction` refuses no one-to-many the way
its twin does, so a bulk detach over a mapping the reader refuses fails on its
**first** record — `detach()` runs `assertRelationshipAcceptsDetach()` as the
first statement of its one-to-many arm — and it fails **inside** the arm's `try`,
which turns a developer error into the danger toast a stale key produces. The
twin raises the same refusal up front, outside both `try` blocks, where it
propagates.

### `getReference()` throws where Doctrine returns null

`EntityManagerInterface::getReference()` is typed `object|null` in ORM 3, and
[`EntityManager::getReference()`](../../../vendor/doctrine/orm/src/EntityManager.php)
reaches null on two paths: the identity map holds an object of a different class
under that identifier, or the entity has subclasses and the `find()` it delegates
to misses. Our signature is non-nullable `object`, and the null answer becomes a
`LogicException` naming the class and the key — as does a key the class's own
mapping cannot decode, and as does a `find()` on
[the loading route](#getreference-decodes-first-and-then-picks-its-route) that
names no row. The reason is what the
callers do with it: a reference is fetched to *bind* a chosen option onto a
record, so a null would be written straight into the association and silently
unset the relationship the user just chose. Failing at the seam names the
identifier that could not be referenced; the alternative pushes a null check onto
every caller and gets it wrong once.

The two routes therefore disagree about one input, and it is recorded rather than
reconciled: a **well-formed** key naming no row **of that class** is a lazy phantom
proxy on the reference route — ORM behaviour, byte for byte what this class did
before — and a `LogicException` on the `find()` route. An inheritance root has no
phantom at all, since Doctrine routes a class with `subClasses` through `find()`
itself. Making the scalar route agree would cost
every single-identifier relationship write a `SELECT` it does not pay; making the
loading route agree would mean minting a reference the ORM cannot build.

### `orphanRemoval` is a plain bool on the ORM 3 mapping object

`AssociationMapping::$orphanRemoval` is declared `public bool $orphanRemoval =
false` — not an array key, not nullable — so it is read directly, with no
null-coalescing fallback. This is recorded because ORM 2 exposed association
mappings as **arrays** (`$mapping['orphanRemoval']`, absent on many mappings),
which is the shape most ported code and most training data assume; the array
access still exists in ORM 3 only through `ArrayAccess` on the mapping object.
Level max rejects a `?? false` against a non-nullable bool, so the truthful form
is the only one that compiles.

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
