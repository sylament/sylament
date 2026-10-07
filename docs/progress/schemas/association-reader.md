---
description: '`Sylament\Schemas\State\AssociationReader` and the `AssociationTarget` value object it returns — a Sylament invention with no Filament counterpart… (draft)'
ports: []
owns:
  - packages/schemas/src/State/AssociationReader.php
  - packages/schemas/src/State/AssociationTarget.php
---
# AssociationReader (schemas)

[`Sylament\Schemas\State\AssociationReader`](../../../packages/schemas/src/State/AssociationReader.php) and the [`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php) value object it returns — a Sylament invention with **no Filament counterpart**, and the reason there is none is structural.
Filament asks an Eloquent model for a relationship by calling it: `$record->{$name}()` hands back a `Relation` object that already knows its related model, its cardinality, and how to save itself, so a component reads and writes a relationship through the model instance it was given.
Doctrine has no such object — an association is *mapping metadata*, read from the `EntityManager`, and writing it is the unit of work's job at flush time.
This class is that translation, and it is the **only** seam through which `schemas` touches an `EntityManager`.

It exists so that every later relation-aware component asks one collaborator the five questions the mapping can answer — what does this property relate to, how do I reference a related entity by its key, what key does a record answer to, what query lists the options, and how do I delete an orphan — instead of each growing its own `EntityManager` dependency and its own metadata reads.
The relationship arm adds four more to the list — which records does this owner's collection hold, which ones does it not, may an existing record join this collection at all, and how do I put one in or take one out — asked through the same collaborator and answered from the same mapping.
A tenth question rides beside them for the create paths: is this record still missing an identifier component the **application** has to supply.
Two more ride beside it, and they are the record check's rather than a component's: which identifier did a managed record's write move, and which new record's assigned key is already held.
The seam is also what keeps Doctrine optional at runtime: an application with no `EntityManager` gets a reader that reports [no association anywhere](#a-null-target-means-no-doctrine-answer-not-no-such-property) rather than a container that cannot compile.

A record key of any width travels through all of it.
The reader mints, finds and decodes one through [`RecordKey`](record-key.md) and narrows every query through `IdentifierConstraint`, so an entity keyed by two scalar fields, by an association plus a scalar, by two associations or by an association alone is a relation **target** wherever a single-`id` entity is — and [the reader owns no copy of the codec's rules](#a-key-of-any-width-is-the-codecs-and-the-reader-keeps-only-the-route).
It is a relation **owner** there too: both relationship queries narrow to an owner of any width [through the owner's own collection](relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path), adding no join to the outer builder and no operation to the codec.

## Surface

Every point of the reader and its value object.
_Blocked by_ names what has to land first, and answers `—` for every row: the last one that waited has closed.
_Detail_ carries a **semantics** link (a permanent note in [Semantics](#semantics)) and/or a **diverges** link (the permanent note in [Divergences](#divergences)), or `—`; there is no **detail** link and no `## Details` section any more, both having gone with that row, which is what the trail's own rule asks of a section whose row flips.
Every row is Sylament-only, so no row has a _Ports_ link.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__construct(?EntityManagerInterface, ?PropertyAccessor)`](../../../packages/schemas/src/State/AssociationReader.php) + [`hasEntityManager()`](../../../packages/schemas/src/State/AssociationReader.php) — the optional-Doctrine seam, and the accessor the writes go through | — | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`withEntityManager(EntityManagerInterface $entityManager)`](../../../packages/schemas/src/State/AssociationReader.php) — this reader bound to another entity manager, as a clone that keeps its class, its accessor and what a subclass carries | — | done | — | [semantics](#withentitymanager-is-a-clone-so-a-subclassed-reader-survives-the-rebind) |
| [`describe(string $class, string $property)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at one property, or null | — | done | — | [semantics](#a-null-target-means-no-doctrine-answer-not-no-such-property) |
| Transient / anonymous-class guarding inside `describe()` | — | done | — | [semantics](#istransient-answers-false-for-an-anonymous-class-so-it-is-not-the-whole-guard) |
| [`getReference(string $class, int\|string $id)`](../../../packages/schemas/src/State/AssociationReader.php) — the record a key names, decoded before anything is looked up | — | done | — | [semantics](#getreference-decodes-first-and-then-picks-its-route) · [diverges](#getreference-throws-where-doctrine-returns-null) |
| [`getRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the string key an entity is spoken by, of any width | — | done | — | [semantics](#the-record-key-is-tables-semantics-moved-onto-the-mapping) |
| [`findRecordKey(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — the same key, or null for an entity whose identifier is not complete yet | — | done | — | [semantics](#findrecordkey-answers-null-where-getrecordkey-throws) |
| [`hasIncompleteAssignedIdentifier(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — whether a component the **application** must assign is missing | — | done | — | [semantics](#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) |
| [`hasBlankAssignedIdentifier(object $record)`](../../../packages/schemas/src/State/AssociationReader.php) — whether a record's assigned key, as `persist()` left it, still holds a blank string component (a null one Doctrine refuses inside `persist()`) | — | done | — | [semantics](#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) |
| [`revertIdentifierChanges(object $record)`](../../../packages/schemas/src/State/AssociationReader.php) — put back every identifier field a managed record the loaded associations reach no longer prints, and answer each one by its property path (`@internal`: the record check and an editable cell's write call it) | — | done | — | [semantics](#a-changed-identifier-is-put-back-and-refused-and-costs-no-statement) |
| [`findTakenIdentifiers(object $record)`](../../../packages/schemas/src/State/AssociationReader.php) — every new record a flush would insert whose assigned key is held already, answered under every field the form could let the user type (`@internal`: the record check calls it and keeps the fields its schema maps, and `ReplicateAction`'s taken-key check with no schema asks whether it answers anything) | — | done | — | [semantics](#a-taken-identifier-is-looked-up-once-per-new-record-after-the-records-own-constraints-pass) · [semantics](#what-the-taken-key-probe-cannot-see) |
| [`findBlankAssignedIdentifiers(object $record)`](../../../packages/schemas/src/State/AssociationReader.php) — every field a new record's blank assigned key could be refused under, in the order a refusal picks from (`@internal`: the record check calls it and refuses under the first one the schema maps) | — | done | — | [semantics](#a-new-records-blank-key-fields-are-the-record-checks-candidates) |
| [`createOptionQuery(string $targetClass, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the base query an option list narrows | — | done | — | [semantics](#the-option-query-is-a-starting-point-not-a-result) |
| [`remove(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) — delete an orphan through the unit of work | — | done | — | [semantics](#remove-is-here-so-orphan-deletion-does-not-grow-a-second-seam) |
| [`persist(object $entity)`](../../../packages/schemas/src/State/AssociationReader.php) + [`flush()`](../../../packages/schemas/src/State/AssociationReader.php) — write a newly created related record | — | done | — | [semantics](#persist-and-flush-are-the-write-half-of-the-same-seam) |
| [`AssociationTarget::$targetClass` / `$isToMany`](../../../packages/schemas/src/State/AssociationTarget.php) — what to load and in what shape | — | done | — | — |
| [`AssociationTarget::$isOwningSide`](../../../packages/schemas/src/State/AssociationTarget.php) — whether writing this side writes the database | — | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [`AssociationTarget::$isOrphanRemoval`](../../../packages/schemas/src/State/AssociationTarget.php) — whether detaching an item deletes it | — | done | — | [diverges](#orphanremoval-is-a-plain-bool-on-the-orm-3-mapping-object) |
| [`AssociationTarget::$isManyToMany` / `$mappedBy` / `$inversedBy` / `$isNullable` / `$orderBy`](../../../packages/schemas/src/State/AssociationTarget.php) — the cardinality, the two bidirectional field names, the join column's nullability and the collection's declared order | — | done | — | [semantics](#the-owning-side-and-orphan-removal-flags-are-what-a-writer-needs-not-trivia) |
| [Service wiring](../../../packages/schemas/src/SylamentSchemasBundle.php) — `sylament_schemas.association_reader` plus a class alias, with an ignore-on-invalid `EntityManagerInterface` argument | — | done | — | [semantics](#the-entity-manager-is-optional-and-the-two-halves-answer-differently) |
| [`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — the `#[Required]` setter every consumer tier reaches the reader through | — | done | — | [semantics](#the-accessor-trait-is-shared-with-the-table-and-action-tiers) |
| Composite records as relation **targets** — keyed, referenced, listed, offered and written at any identifier width | — | done | — | [semantics](#a-key-of-any-width-is-the-codecs-and-the-reader-keeps-only-the-route) |
| Through-relations, morphs, pivot data | — | skipped | — | [diverges](#only-the-shapes-doctrine-states-in-one-mapping-are-read) |

Every row is **done** or **skipped**.
When the last **blocked** one closed, [`record-key-contract.md`](record-key-contract.md#owner-scoping-was-reserved-and-turned-out-not-to-be-needed)'s reserved owner-scoping row went with it — not by being built, but because the owner scope turned out to need no operation of the codec's at all.

The unit suite ([`AssociationReaderTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderTest.php), with [`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php), [`AssociationReaderCompositeQueryTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeQueryTest.php) and [`AssociationReaderIdentifierRelationTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderIdentifierRelationTest.php) beside it) covers all four association shapes the fixture entities declare (many-to-one, the inverse one-to-many with orphan removal, the owning many-to-many, and the **inverse** one-to-one), the five mapping-shape fields across seven shapes (both sides of a bidirectional many-to-many, a unidirectional one, an ascending and a **descending** ordered one-to-many — the descending one being what tells the pass-through apart from a normalised direction — and a nullable and a not-null to-one), all three null answers (a scalar property, a non-entity class, an anonymous class), the four operations refusing without an entity manager, the record key against a persisted entity at every identifier flavour, and the two refusals that remain — an unpersisted entity, and a component that is set but not printable.
The relationship seam is covered beside them: `describeRelationship()`'s four refusals and its uninitialised-owner arm, both query shapes asserted as DQL character for character and as the SQL SQLite renders, the candidate negation with its mandatory `IS NULL` arm and a composed `andWhere()` proving the two arms stay bracketed, the owner-without-an-identifier refusal, the composite-owner refusal on both builders and both arms, and — over the Integration rig, which persists and flushes by hand because [`EntityManagerFactory::seed()`](../../../packages/schemas/tests/Fixtures/Doctrine/EntityManagerFactory.php) clears the identity map — attach and detach on a unidirectional many-to-many, on both directions of the bidirectional `Post::$categories` ↔ `Category::$posts` pair (the owning direction re-read across a `clear()`, the inverse one written through the owning collection, and both named in one case that reads the untouched inverse collections as proof of where each write landed — [`Category`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/Category.php)'s adders deliberately heal nothing, so a mis-routed write really does lose the row), the one-to-many associate/dissociate through `Author::$posts`, a detach handed a reference rather than a hydrated record, the no-op attach of an already-related record, and the four dissociate refusals one fixture each ([`Post::$sections`](../../../packages/schemas/tests/Fixtures/Doctrine/Entity/Post.php) for orphan removal, the identifier half for the re-keying refusal, `Post::$comments` for the NOT NULL join column, `Post::$replies` for the setter that rejects null).

Three properties of a composite target are pinned by their own cases, because a green suite would otherwise hide each of them: a malformed key is refused **before any statement**, read off a recording middleware rather than off a platform's leniency; a reference by key is taken against a **cleared** identity map, which is the only state in which the loading route's absence is a `TypeError`; and every expected key in an assertion is a string literal, never one minted through the codec in the expectation.

## Semantics

### The entity manager is optional, and the two halves answer differently

The constructor takes `?EntityManagerInterface` and the bundle wires it with `service(EntityManagerInterface::class)->nullOnInvalid()`, so `schemas` compiles in an application with no Doctrine at all — the same shape as support's [AssetMapper guard](../support/support-bundle.md), and the reason `doctrine/orm` being a hard `require` of the package is still not a hard requirement of the *runtime*.
Without a manager the class splits in two:

- **`describe()` answers null**, because "is this property an association?" has a meaningful negative answer — no Doctrine means no associations, and a caller keeps whatever non-association behaviour it already had.
- **Every operation throws a `LogicException`**, because `getReference()`, `getRecordKey()`, `createOptionQuery()`, `remove()` and the rest have no meaningful negative answer: a caller reaching them has already decided it is handling a relationship, so a silent null would corrupt state instead of failing.
  One message covers them all, and it names both causes (a reader built outside the container, or no `EntityManagerInterface` service) and the two ways out — register `doctrine/doctrine-bundle`, or bind the record by hand after `getData()`.

`hasEntityManager()` exists so a caller can branch **before** it commits to the relationship path, rather than catching the exception the operations throw.

The two record-check passes take the `describe()` side rather than the operations': without a manager, `revertIdentifierChanges()` and `findTakenIdentifiers()` walk nothing and answer no refusal.
They run inside every `getData()` a validator reaches, and a host with no Doctrine has no identity map for a key to move in or be held by — so a reader built with `new`, which is what a schema built outside the container holds, guards nothing.
Both test files pin it, *guards nothing without an entity manager*.

### `withEntityManager()` is a clone, so a subclassed reader survives the rebind

`withEntityManager(EntityManagerInterface $entityManager): static` answers this reader bound to another manager.
It is a clone — `clone($this, ['entityManager' => $entityManager])`, PHP 8.5's clone-with, which reassigns the `private readonly` manager from inside the class, on a subclass's instance too — so the copy keeps the reader's class, its property accessor by identity and anything a subclass carries, and the reader it was called on keeps the manager it had.
It answers a new instance every time, the house's `with*` shape ([`Paginator::withRecords()`](../support/pagination.md)).
`new static(...)` was the alternative and is wrong: it breaks a subclass whose constructor takes arguments of its own, and drops whatever state one holds.

It is not final, on purpose.
A subclass's `__clone()` runs **before** the clone-with assignment, so one holding per-manager state resets it there.
And a reader that **delegates** to another — the decorator shape, since the reader has no interface — keeps delegating to the old manager after a plain clone, because the clone copies its reference to the inner reader; it overrides the method to rebind that one too:

```php
public function withEntityManager(EntityManagerInterface $entityManager): static
{
    return clone(parent::withEntityManager($entityManager), ['inner' => $this->inner->withEntityManager($entityManager)]);
}
```

It exists for the write seams that read through the manager they flush through, which rebind the reader they already hold rather than constructing one: `CreateAction`, `EditAction` and `ReplicateAction` their mounted schema's reader — `ReplicateAction`'s arm without a schema the action's own — tables' editable cell its table's, and the resource create and edit pages their form's.
Built with `new`, each of those dropped a subclassed `sylament_schemas.association_reader` service — the service an application may decorate — and the container's property accessor with it.
[`AssociationReaderConstructionTest`](../../../packages/schemas/tests/Architecture/AssociationReaderConstructionTest.php) keeps it so: in `packages/*/src` only `CanAccessAssociations`' manager-less fallback constructs a reader.

A reader taking its manager per call instead — or resolving one from a `ManagerRegistry` per class — is deferred indefinitely.
The record check runs inside `getData()`, through `InteractsWithSchemas::validateSchemaRecord()`, and only the caller knows which manager it will flush through, so a per-call manager would have to change that host contract; and a registry answers the first manager whose mapping knows a class, so it cannot pick a second manager over the same mapping — the shape a table whose `query()` runs on another manager has ([below](#what-the-taken-key-probe-cannot-see)).

Pinned by [`AssociationReaderWithEntityManagerTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderWithEntityManagerTest.php): a clone bound to the manager with the original untouched, the accessor kept, a subclass and its own constructor argument kept, a bound reader rebound, and the manager used for real — a key moved on a record a second manager keeps is refused only by the reader rebound to that manager.

### A null target means "no Doctrine answer", not "no such property"

`describe()` returns null on three different facts — there is no entity manager, the class is not a managed entity, the property is not an association — and deliberately does not distinguish them, exactly as [`RecordTypeReader`](record-type-reader.md#a-null-recordtarget-means-no-type-answer-not-no-such-property) does.
All three mean the same thing to a caller: *keep doing what you did before associations existed*.
A field whose property is a plain column, a field on a DTO record, and every field in a Doctrine-less application take one code path, not three.

### `isTransient()` answers false for an anonymous class, so it is not the whole guard

The transient check is `getMetadataFactory()->isTransient($class)` rather than a `getClassMetadata()` call in a `try`, following the reasoning tables already recorded for [its record-key arm](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key): the metadata read is the expensive, throwing operation and the factory answers the cheap question directly.
But `isTransient()` has one documented hole, verified in [`AbstractClassMetadataFactory`](../../../vendor/doctrine/persistence/src/Mapping/AbstractClassMetadataFactory.php): it returns **false** for an anonymous class — the plainest transient object there is — and the metadata read that follows then throws `Doctrine\Persistence\Mapping\MappingException: Class "class@anonymous …" is anonymous`.
So `describe()` adds a `ReflectionClass::isAnonymous()` check and answers null for it.
A test double built as `new class { … }` is an ordinary thing to hand a schema, and it takes the non-association path rather than an uncatchable mapping error.

### A key of any width is the codec's, and the reader keeps only the route

`getRecordKey()` and `findRecordKey()` are one line each — `RecordKey::of()` and `RecordKey::find()`, with the entity manager passed per call — and `getReference()` decodes through `RecordKey::toIdentifier()` before it looks anything up.
The reader holds **no** identifier narrowing of its own: the `Stringable`/`is_scalar` coercion it used to carry was the fourth hand-written copy of one rule, and [the codec owns that rule now](record-key.md#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may).
Minting a key and finding one branch on nothing — neither `getRecordKey()` nor `findRecordKey()` asks the identifier's width or flavour — which is what makes "a composite record is a relation target wherever a single-`id` record is" a property of the code rather than a claim about it.

What the delegation moved, exactly — two answers changed, and both are the codec's stated contract rather than a decision taken here:

- An identifier that is boolean **`false`** now keys to `'0'` where the reader threw.
  `printable()` normalises a `bool` to `'1'`/`'0'` deliberately, because a plain `(string)` cast prints `false` as the empty string, which is an illegal empty component; the reader's old order called that value blank and refused it.
- A **blank or whitespace-only assigned string** identifier makes `findRecordKey()` answer `null` where it threw — `getRecordKey()` still throws `is not a printable value`.
  That is what lets `unique(ignoreRecord:)` and `Select::relationship(ignoreRecord:)` run on a create form over an assigned-key class: there is nothing to exclude yet, which is an answer rather than an error.

Two things the delegation did **not** move, and both are worth stating because a reader assumes the opposite.
Refusing a key is still cheap, and it never binds a fragment of the submitted text — but the two shapes of by-key read differ, and the difference is a statement.
A by-key **lookup** executes **none**: `getReference()` refuses on the decode above, and `Select`'s selected-record lookup asks `IdentifierConstraint::equals()`, which answers `null` for a key that decodes to nothing — `createScopedOptionQuery()` has built a `QueryBuilder` by then, so what is never built or run is the statement.
A by-key **batch** read is one statement narrowed to `1 = 0`: `Select`'s label list hands its scalar entries to `IdentifierConstraint::in()`, and a list whose every scalar entry is malformed leaves nothing to compare, so the query runs and returns no rows (a list with no scalar entry at all issues none).
And the single-identifier DQL every consumer emits is today's text, byte for byte — `IdentifierConstraint` emits `alias.id = :base` and `alias.id IN (:base)` unchanged for a one-field class, so nothing that pinned those strings moved.

### `getReference()` decodes first, and then picks its route

The signature is `getReference(string $class, int|string $id): object`, and it kept the `int|string` on purpose: a raw `int` prints to the very key a single-identifier class speaks, and a raw `int` handed to a two-field class decodes to nothing and is refused loudly, so no consumer had to move.
The body is two steps and the order is the whole point.

**Decode, then look up.**
`RecordKey::toIdentifier()` runs the class-aware grammar check, the per-type shape check and the print-back compare; `null` out of it is today's `LogicException` (`No [%s] carries the identifier [%s], so it cannot be referenced.`) raised **before** any query.
So `05`, ` 5`, `5abc` and `1~2` against a one-field class each name no record at zero statements, where an untyped bind would have aliased a row on PostgreSQL under emulated prepares or raised `22P02` under native ones — [the hole the codec exists to close](record-key.md#a-malformed-key-never-reaches-the-database).

One **single-identifier** shape paid for that strictness until the codec learned to read it back.
Where a DBAL type's `convertToPHPValue()` reads only the database form, the decode refused the very key `getRecordKey()` minted, while the base had handed that printed key straight to Doctrine and the foreign-key write landed wherever the id property admits a string — so a relation-backed `Select` or `CheckboxList` threw on save and a by-key `DetachBulkAction` answered the danger toast where the base wrote.
The remedy was the codec's: a component the reader refuses is read back [through the type's own writer](record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer), so such a key decodes and the reference is taken again — built at zero statements where every identifier field is scalar, loaded at one behind a single derived identity.
What stays refused is the type that refuses its printed form on **write** too — [the doubly-strict shape](record-key.md#a-type-that-cannot-read-its-own-printed-form-mints-keys-that-never-decode) — by construction rather than by omission, at zero statements ([`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php) pins both).

**Then `find()` where any identifier field is an association, and `getReference()` everywhere else.**
The predicate is `array_any($metadata->identifier, $metadata->hasAssociation(...))` — never `isIdentifierComposite`, which is false for a **single derived identity** and so misses one of the three flavours that need the loading route.
Doctrine's `ProxyFactory` assigns a scalar identifier component straight into a typed association property, so `getReference()` against a **cold** identity map is a `TypeError` for a derived identity, a join entity and a single derived identity alike, while succeeding for all three once the record is already managed — which is precisely the defect a seeded test hides and a first cold request finds.
`find()` works in every state, at one statement.

The scalar route keeps its **zero** statements, which is why "always `find()`" was rejected: it would charge every single-identifier relationship write a `SELECT` it does not pay today.
One class of target pays anyway, and it is Doctrine's doing rather than this predicate's: a class that declares `subClasses` is routed through `find()` by `EntityManager::getReference()` itself, so an inheritance **root** costs one statement and refuses a missing row by name.
And both routes hand back the very instance an owner's loaded collection holds — the identity-map instance, loaded before or after the call — which is the property `detach()`'s `!==` filter and `attach()`'s strict `in_array()` are built on.

Three consequences recorded rather than closed.
A **well-formed** key naming no row **of that class** is a lazy phantom proxy on the reference route, exactly as it was before, and a `LogicException` on the `find()` route — the two routes disagree, and the divergence [below](#getreference-throws-where-doctrine-returns-null) says why neither answer is a null.
*Of that class* is load-bearing under inheritance: a **sibling** subclass's key is well-formed for the columns the root declares, so a reference asked for on the wrong subclass is a proxy of the class that was asked for, and attaching it writes a join row the relationship query then never lists, because the discriminator excludes it.
A key-forging client is not what produces that; a consumer passing the wrong `targetClass` is.

Next, one decode the codec cannot police: a scalar identifier typed as a `BackedEnum` answers a forged key with PHP's own `ValueError` out of `getReference()` rather than the `LogicException` this member's docblock promises.
`RecordKey` cannot see `enumType` and is frozen, and the `find()` route answers the same key cleanly.
It costs zero statements either way, and both real callers already stand in front of it — a relationship `Select`'s `Choice` rule runs before the forms write, and `DetachBulkAction`'s `try` toasts it — so it is recorded rather than caught.

And a to-many write of *N* options onto a foreign-identifier target costs *N* primary-key `SELECT`s; one `IdentifierConstraint::in()` pre-fetch would make those `find()`s free, and is the known remedy if it ever matters.
No batched member is added for one consumer and a rare shape.

### An incomplete assigned identifier is the question the create paths ask

`hasIncompleteAssignedIdentifier(object $entity): bool` answers one thing: is the record missing an identifier component **the application** has to supply.
It is not "is this record keyable yet", and the difference is the reason the member exists rather than a `findRecordKey() === null` test at the call sites.

`RecordKey::find()` answers `null` for two unrelated reasons.
One is the defect the create paths must refuse: a form that never collected a key half, so a row would commit with an empty component and no option could ever name it.
The other is ordinary and correct: an identifier **association** whose target's generated id is still pending — every derived-identity child of an owner that has not been flushed yet, which is what a relationship `Repeater` on a *create* form holds.
Doctrine writes that child in one flush, deferring the child's identifier to the parent's insert, so refusing it would refuse a working form with advice that is false.
One shape is Doctrine's own limit rather than ours: a **JOINED-inheritance** child cannot be inserted in its owner's flush at all — `JoinedSubclassPersister` throws *Unable to find … entity identifier associated with the UnitOfWork* — and this predicate passes it, correctly, leaving the failure where it belongs.

So the member asks the mapping instead, and it asks only of a composite identifier or one carrying an association.
A **plain single scalar** identifier answers `false` at the entry, before anything else is read: the relation layer has always keyed such a class, an application may fill that field from anywhere — including a listener no class-level mapping shows — and a new refusal there would be a single-identifier regression, which a wider key must never cause.
So an assigned single scalar identifier left blank stays unrefused here, as it always was.
The record check leaves this predicate as it is: its taken-key pass treats a blank component as incomplete and looks nothing up — a `Stringable` component that prints blank included, since it reads blankness through support's `blank()` there as everywhere else in the reader, and an identifier association only while it is `null`, as everywhere else too: its target is an entity, never a printed key component, so one whose `__toString()` prints blank or which counts no members is still looked up — so a blank single scalar key on a create path still reaches Doctrine as it did — which is what keeps two new children whose `#[PrePersist]` fills that key from blank saving — and only [`ReplicateAction`](../actions/replicate-action.md) refuses one by name, because it asks once every filler has run.

Everything else — a composite identifier, or one carrying an association — goes to the private, recursive `isMissingAssignedIdentifierComponent()`.
Three mapping facts short-circuit it to `false`, and all three say the same thing: something other than the form fills this identifier at the flush.
`usesIdGenerator()` is Doctrine's own; `hasLifecycleCallbacks(Events::prePersist)` is a `#[PrePersist]` callback of the class; `isset($metadata->entityListeners[Events::prePersist])` is a mapped entity listener.
Otherwise every identifier field is read: an **association** component must be an object and must itself answer `false` to the same question — recursing into the **private** member rather than the public one, so that behind an identifier association a single assigned scalar still counts — and a **scalar** component must not be [`blank()`](../support/helpers.md).
A pending generated id behind an association therefore passes, and a missing locale does not.

One residual, recorded rather than closed: a **global** `prePersist` listener on the event manager can assign a composite half too, and no class-level mapping shows it, so a record relying on one is refused here.
The three short-circuits cover everything the class itself declares; covering the global case would need an opt-out nothing has asked for.
The residual reaches `Select`'s `createOption` default and the `Repeater`, and `ReplicateAction` asks the same predicate before `persist()`; the `Select` arm is executed, and a created option whose key a global listener would fill is refused by name.
Reading `EventManager::hasListeners(Events::prePersist)` as a fourth short-circuit is not the way out: it answers for every listener on every class, so one timestamping listener anywhere in an application would switch the refusal off for every record, and the `Repeater`, which has no post-`persist()` read behind it, would then write a blank key half no component maps.
The record check does not ask this predicate: its blank-key pass reads [the record's blank key fields](#a-new-records-blank-key-fields-are-the-record-checks-candidates) and refuses only one the form maps, which is the user's to fill.
The two create seams, [`CreateAction`](../actions/create-action.md#a-key-the-modal-leaves-blank-is-refused-by-name-after-persist) and the resource create page, ask [`hasBlankAssignedIdentifier()`](#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) after `persist()`, once the listener has run.

The short-circuits cut the other way too.
A class declaring a `prePersist` callback or entity listener that fills **no** key half is still exempted, so a blank half passes this predicate.
`Select`'s `createOption` default therefore reads the key again after `persist()`, through `hasBlankAssignedIdentifier()`, and refuses such a record by the same message, detached before anything is flushed.
The `Repeater` has no such moment — it never persists an item itself; the owner's cascade inserts it at the application's flush — so for such a class the blank half no component maps reaches the table, recorded in [repeater-relationship.md](../forms/repeater-relationship.md#a-new-item-whose-identifier-is-incomplete-is-refused-before-the-save-returns).

Four callers, all refusing **before anything is flushed**: `Select`'s `createOptionUsing` default, which asks before `persist()`, `Repeater`'s relationship save, which refuses after the collection write and before the item-key map is re-read, `ReplicateAction::replicateRecord()`, which asks after `beforeReplicaSaved()` and before `persist()`, and a singular relationship's `saveStateToRelationship()`, which asks of a related record it constructed once that record is written onto its owner ([entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md)).
All four name the fields that would fix it.
None is validation: a taken key, a changed key half and two new records claiming one key are the record check's ([changed](#a-changed-identifier-is-put-back-and-refused-and-costs-no-statement), [taken](#a-taken-identifier-is-looked-up-once-per-new-record-after-the-records-own-constraints-pass)) — and none of the four restores the in-memory graph on the way out: a refusal here is a developer error that ends the request, nothing has been flushed, and the two write paths that could have persisted something have not run.

### A blank assigned identifier is read after persist(), where a record is created

`hasBlankAssignedIdentifier(object $record): bool` asks whether a record's assigned key, as `persist()` left it, still holds a component that is a blank string, through an identifier association's own key too.
Its callers are the three places that persist a record a form mapped: [`CreateAction::createRecord()`](../actions/create-action.md#a-key-the-modal-leaves-blank-is-refused-by-name-after-persist), [`CreateRecord::handleRecordCreation()`](../panels/create-record.md) and `Select`'s [`createOption` default](../forms/select.md#a-created-option-whose-identifier-is-incomplete-is-refused-before-it-is-written).
Each asks it after `persist()` and before `flush()`, and refuses a `true` by name.
They refuse a key field the form leaves out, which the record check [leaves to them](can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written).
The two create seams rebind the reader they hold to the manager their flush writes through, with [`withEntityManager()`](#withentitymanager-is-a-clone-so-a-subclassed-reader-survives-the-rebind), never the container's default; `Select` asks the reader it persists and flushes through.

Where [`hasIncompleteAssignedIdentifier()`](#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) reads the mapping to guess what will fill the key, this one reads the record after the fillers ran.
So it has no `prePersist` short-circuits: every callback, entity listener and global event-manager listener has already had its turn.
The test is `RecordKey::find() === null`.
A null component never reaches it, because Doctrine's `AssignedGenerator` refuses a missing assigned id by name inside `persist()`.
What is left is a blank string, possibly inside an associated record's key.

Three shapes answer `false`: a generated identifier, a single scalar identifier (the application's `required()` to guard) and an identifier association onto a record whose generated identifier is still pending.
The last is a derived-identity child of an owner created in the same flush.
`RecordKey::find()` reads it as null too, but Doctrine defers the child's key to the owner's insert, so refusing it would refuse a working create.
That exemption holds only while no scalar identifier field is blank: a blank string half beside a pending owner answers `true`, since the flush would write it as `''`.

Pinned by [`AssociationReaderBlankIdentifierTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderBlankIdentifierTest.php).

### A new record's blank key fields are the record check's candidates

`findBlankAssignedIdentifiers(object $record)` answers every field a new record's blank assigned key could be refused under, keyed by property path, in the order the record check picks from: each identifier field holding a blank string — null, empty or whitespace-only — then each identifier association left unset, or onto a record whose own assigned key is blank.
For the last, the target's blank key fields come before the association itself, so `spine.code` precedes `spine`.
The record check refuses under the first candidate a component of the schema maps, and hands the rest to [the create seams](#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) ([the rule](can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written)).

It answers nothing for a record the unit of work already holds, for a generated identifier and for a single scalar one, and a target whose identifier is generated is never blank here.
It reads the record's fields rather than [`hasIncompleteAssignedIdentifier()`](#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask), so a `prePersist` callback or entity listener the class declares changes nothing: a key field the form maps is the user's to fill.
It does not ask that predicate first, whose short-circuits would exempt such a class, and it falls back to no other field when no candidate matches, since an empty list means nothing is blank.
Pinned by [`HasSchemasIdentifierGuardTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasIdentifierGuardTest.php).

### A changed identifier is put back and refused, and costs no statement

`revertIdentifierChanges(object $record): array` is the changed-key rule — `@internal`, and called from two places: schemas' record check, first of the [four validator calls](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) `getData()` ends in, and tables' editable-cell write, which runs it alone ([the cell](../tables/has-columns.md)).
It answers `array<string, string>`: the message *"This value cannot be changed."*, keyed by the refused field's property path from the record.

It walks the record and every record its **loaded** associations reach, over every edge, breadth-first and each record once, carrying the property path from the record — `translations[1]` for a collection member, `plaque.serial` behind a to-one.
It never loads: an uninitialized proxy and an uninitialized `PersistentCollection` are skipped, and `ClassMetadata::getFieldValue()` reads the raw property, so the walk initializes nothing.
An anonymous class and a class the manager deems transient are skipped too — a schema bound to an anonymous-class object keeps mapping.
A record not in the identity map, or one scheduled for insert or delete, is skipped: a new record has no stored key to move.

The detector compares, per identifier field, the value `UnitOfWork::getEntityIdentifier()` keyed the record by with the one it holds now — `===` first, then `IdentifierFields::printable()` of both, an association reduced to its target's identifier value through `RecordKey::values()`.
So the same key in another PHP type is **no** change: `'5'` over `5`, an equal `Uuid`, the same enum case set again.
A converting type's other spelling of the same key **is** one — `ab-1` typed over a stored `AB-1` is refused — because accepting it would need a detector that runs every value through its type's writer, and only a hand-typed value reaches it, a key field being meant to sit `disabledOn('edit')`.

A changed field is put back on the spot — a scalar from that identifier, an association from `getOriginalEntityData()`, and an association the unit of work holds no original value for throws a `LogicException` by name.
The restore is load-bearing rather than tidy: without it a flush later in the same request writes the new key after all, and a table's re-render loses the cell's error or collapses two rows onto one DOM id.
It runs no statement; the walk costs about 3 µs a loaded managed record per save, measured when the rule was designed — an unchanged save of a record holding 5 000 loaded children went from 0.02 ms to 16 ms.
Its limit is the walk's: a key written into a related record **nobody loaded** is not seen, and is Doctrine's to drop — it writes the raw identifier, and a later load overwrites it.

Pinned by [`AssociationReaderChangedIdentifierTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderChangedIdentifierTest.php) — every shape the rigs seed untouched, the same key in another type, the skipped states, the paths through a collection, a to-one and a cycle, the unloaded proxy and collection, and the named `LogicException`.

### A taken identifier is looked up once per new record, after the record's own constraints pass

`findTakenIdentifiers(object $record): array` is the taken-key rule, `@internal` — the record check's, and `ReplicateAction`'s on its arm without a schema, which asks only whether it answers anything — and of the same shape, answering *"This value is already used."* — `UniqueEntity`'s default text, from symfony/validator's own `validators` catalogue, so it is translated with no bridge installed.
It walks the record and every record its loaded **cascade-persist** associations reach, which is exactly what a flush would insert beside it, and asks only of a **new** record whose class has a natural identifier — `isIdentifierNatural()`, so a generated id is never looked up — whether its key is held already, in two ways:

- **by a sibling**: another new record met earlier in the same walk claims the same key.
  A scalar half is compared in its database form (`convertToDatabaseValue()`), an association half by the very object, which is the only identity a new target has — so two new items on one key under an owner that has no identifier yet are refused without a statement;
- **by a holder**: `EntityManager::find($metadata->rootEntityName, $identifier)` answers an object that is not the record — the identity map first, then one primary-key `SELECT`.
  A replica's managed original counts and the record itself does not, and the **root** entity name is what finds a sibling subclass's row in a single-table or joined hierarchy.

A component that is `null` or a blank string is incomplete, and nothing is claimed or looked up for it — `RecordKey::find()`'s own rule — which is what keeps two new children whose key a `#[PrePersist]` fills from `''` saving.
And `RecordKey::values()` gates the lookup, so an association half that is a new record with no identifier yet is never bound.

The message lands on every field a form could let the user type: each identifier field that is no association, in identifier order.
The reader answers them all, and the record check keeps the ones a component of its schema maps — a taken record's fields grouped by the property path they share.
When the schema maps none of them, or the identifier has none, it lands where it always did: the first identifier field that is no association; failing that, the first that is not the back-reference the walk arrived through — the inverse collection's `mappedBy`; failing that, the first — and an unmapped one folds to the schema's own path.
So a `(host, path)` key typed in both halves is refused under both, a form typing only the host under the host, an `ArticleTranslation(article, locale)` under **Locale** on a create page and in a relation manager's modal alike, and a join entity created from its festival's repeater under the band rather than the festival.
Answering the first such field alone would leave the second half of a scalar pair — the one a user may well have typed wrong — with no mark.
The message is still Symfony's own, translated in its 57 catalogues, so a copy under each half adds no string; a field label in it would have needed one.

Where it runs is half of what it is.
The record check calls it **only when** the changed-key pass and the record's own constraints raised nothing, because it binds form values into `find()`: a malformed `guid` on an entity that declares `#[Assert\Uuid]` then answers that constraint's *"This value is not a valid UUID."* rather than PostgreSQL's `22P02` — without one, the lookup meets PostgreSQL's own `22P02`, as the base's `INSERT` did — and an application `UniqueEntity` answers first, so the toolkit never adds a second message; and it does not run at all on a schema that [switches the key rules off](can-be-validated.md#the-key-rules-switch-off-per-schema-never-the-changed-key-one).
The cost is one primary-key `SELECT` per new assigned-key record, none when the holder is managed, none for a generated id — so a save adding 200 assigned-key repeater items runs 200.
Batching it per class through [`IdentifierConstraint::in()`](record-key-contract.md#the-operations-and-their-dql) is the known remedy, and is not built for a cost nobody has met.

Pinned by [`AssociationReaderTakenIdentifierTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderTakenIdentifierTest.php): a stored, a managed and a persisted-unflushed holder, a free key after one `SELECT`, the sibling claim under a stored and a new owner, the cascade-only walk, the generated id, blank and `null` components, the unflushed association half, the record itself, both inheritance hierarchies through their root, and the placement over every shape, every field of a scalar pair or triple included ([`AssociationReaderKeyShapesTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderKeyShapesTest.php) beside it).
The host's half — keeping the mapped fields, else the first — by [`HasSchemasTakenKeyPlacementTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasTakenKeyPlacementTest.php) and forms' [`TakenKeyPlacementTest`](../../../packages/forms/tests/Integration/Components/TakenKeyPlacementTest.php): both halves, one, neither, a derived identity's scalar half, two of three, a hidden half that carries the message while only the visible one shows it, and a relationship repeater's items each under their own halves.

### What the taken-key probe cannot see

Six limits, recorded rather than closed:

- **A detached record reads as new.**
  A reader reads its own entity manager's unit of work and database, and `UnitOfWork::getEntityState()` cannot tell a detached record from a new one on a taken key without the database — so a record that manager does not keep, a detached one or one another manager keeps over the same mapping, reads as new, and an unchanged write of it answers *already used*.
  Pinned as behaviour: *reads a detached record, or one another manager keeps, as a new one — so its own unchanged key answers taken*.
  The hosts keep the second-manager half out of reach.
  An action rebinds its schema's reader to the manager it writes through, with [`withEntityManager()`](#withentitymanager-is-a-clone-so-a-subclassed-reader-survives-the-rebind) — `getEntityManagerForRecord()`, the table query's manager, else the registry's for the class — so on a table whose `query()` runs on a second manager both rules read that manager's unit of work and database, where the flush runs; panels' create and edit pages bind their form's reader to the manager they flush through, the container's unless a page overrides `setRecordPersistence()`; and a custom host that writes through another manager binds its schema's reader the same way, `$schema->setAssociationReader($schema->getAssociationReader()->withEntityManager($manager))`.
  The detached record is what remains, and the base failed its write anyway: `persist()` takes a detached record for a new one, so its flush met the stored key.
  The hosts' half is pinned by actions' [`TableQueryManagerKeyTest`](../../../packages/actions/tests/Integration/TableQueryManagerKeyTest.php): on a table over a second manager, an unchanged edit saves, a changed key is refused, and a create or a replica on a key only that manager's database holds is *already used*.
- **A record whose class only another entity manager maps is skipped by both passes** of a reader over a manager that does not map it: the walk asks the reader's own manager's metadata factory, to which that class is transient, so neither this probe nor the changed-key pass sees it — no regression, since the base guarded nothing either.
  An action writing such a record binds its schema's reader to the manager that maps it, so both passes run there; what is left is a custom host that writes through that manager without binding its schema's reader.
  Unexecuted.
- **Two requests creating one key at once** both pass the lookup, and the second meets the database's constraint: the probe is a read, not a lock.
  Executed on PostgreSQL 18 and MySQL 8, the second flush is a `UniqueConstraintViolationException` that closes that request's entity manager.
  Reads still work, and DoctrineBundle's reset replaces the closed manager before the next request.
  This is recorded, not closed, as `UniqueEntity` and Filament leave it too.
- **Two new records whose keys a case- or accent-insensitive collation equates** — `Example.com` and `example.com` under MySQL's default `utf8mb4_0900_ai_ci` — are not caught in one save: the walk claims a sibling's key by `serialize()` of what its type's `convertToDatabaseValue()` writes, an exact comparison in PHP, and the `find()` probe's identity-map hit is exact as well while its SELECT sees only stored rows.
  The flush meets the unique index, and the second is the same `UniqueConstraintViolationException` as two requests at once, closing the manager.
  A type whose writer folds the spellings to one value is caught, since the claim compares what reaches the column: the fixture `CaselessType` lower-cases on write, so *refuses the second of two new siblings whose keys differ only in a spelling their column stores alike* pins that, not the collation.
- **The lookup compares keys as the database does**, so a `guid` key spelled in the other letter case is taken on PostgreSQL, whose `uuid` type compares case-insensitively, and free on SQLite, which stores the guid as `CHAR(36)` — where the insert then writes a second row differing from the first only in case.
  No code path of ours differs: the probe binds the value and takes the database's equality, which on PostgreSQL is the one its primary key enforces anyway.
  Executed on both platforms, and unpinned.
- **MariaDB** is unexecuted.
  The suites run the rules on SQLite, MySQL 8 and PostgreSQL 18.

**A Doctrine SQL filter does not hide the holder.**
A primary key is unique whatever a filter shows, so the probe's `find()` runs with every enabled filter suspended, and restores them in a `finally`.

Without the suspension, a soft-deleted or other-tenant row holding the key reads as free, and the flush meets the primary key: a `UniqueConstraintViolationException` and a closed entity manager.
That was executed on PostgreSQL 18 and MySQL 8, for single and composite keys alike.

The price is that a holder the filters hid is loaded into the identity map, so a by-key `find()` or `getReference()` of that identifier — `AssociationReader::getReference()` included — answers it past the restored filter for the rest of that request, and its load listeners run; for a class under the second-level cache the unfiltered row is also written to its entity region, where a later request's `find()` hits it until eviction or expiry.
DQL stays filtered either way.
A create it refuses ends in a validation error before anything is flushed.
With no filter enabled nothing changes, statement for statement.

A lookup that loads nothing was also considered: an identity-map check, then a `SELECT 1` through `inColumns()`.
It was rejected because it must decode the minted key again, so a type that cannot read its own printed form would read as free.
It would also change the single-key statement.

### The record key is tables' semantics, moved onto the mapping

`getRecordKey()` is the same answer [`HasTable::resolveQueryRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php) gives, and both reach it through the same codec: the identifier comes from `ClassMetadata::getIdentifierValues()` — never from an `id` property convention — a `Stringable` component is unwrapped, a `BackedEnum` becomes its backing value, a `bool` becomes `'1'`/`'0'`, and a component that is unset, non-scalar, blank or carrying a NUL byte is a `LogicException` rather than a key no lookup could resolve.
An entity mapped on `code` therefore keys by `code` with nothing configured; an entity mapped on `host` and `path` keys by both, joined and escaped by [the grammar](record-key-contract.md#the-grammar); and an entity that was never given its identifier fails at the point of binding instead of producing a row the client cannot address.

The one refusal a reader would not predict belongs to the codec rather than to either consumer: completeness is **counted**, never tested for emptiness, because `getIdentifierValues()` *drops* a null component on the composite branch.
A half-filled pair would otherwise read as complete — and the count is compared at every level of a chained derived identity, not only at the top.

### findRecordKey() answers null where getRecordKey() throws

`getRecordKey()` throws where `findRecordKey()` answers `null`, and the split exists because two callers want opposite things from a record whose identifier is not complete.
A relation-backed field speaking an option list by key has a bug if the record it is rendering cannot be keyed — that is the throw.
A `unique()` constraint asking "which row should I exclude from the probe?" has no bug at all — a create form's record is unkeyed by definition, and the honest answer is "nothing to exclude", which is exactly what `null` means to [`UniqueValue`](../forms/can-be-validated.md).
`Select::relationship(…, ignoreRecord:)` is the third caller on the same terms: it reads this pair to drop one record from an option list, and an unkeyed one drops nothing ([select.md](../forms/select.md)).

The line between them is the codec's, and it moved once: a component that is `null` **or still blank** counts as not assigned yet, so an assigned string key sitting at `''` until a form fills it answers `null` here rather than throwing.
Only that arm is soft.
A component that exists and cannot be spoken — non-scalar, not valid UTF-8, carrying a NUL — is `is not a printable value` from **both** members, because an identifier that exists and cannot be printed is an error either way, not "nothing to ignore".

### The option query is a starting point, not a result

`createOptionQuery()` returns a `QueryBuilder` selecting the target entity under a fixed default alias (`record`), and nothing else — no ordering, no limit, no search.
Everything a relationship option list needs to add (a user's `modifyQueryUsing`, a search constraint, a page limit) is a mutation on that builder, so the reader never has to grow a parameter for it, and a caller can always fall back to the manager's own query API for a shape this cannot express.
The alias is a parameter rather than a constant because a caller composing a subquery needs to control it.

### `remove()` is here so orphan deletion does not grow a second seam

Detaching an item from an association whose mapping declares `orphanRemoval` is Doctrine's job at flush time, but a component that *replaces* a collection has to delete what it dropped, and that is an `EntityManager::remove()` call.
Routing it through the reader keeps the "only seam" property true: no relation-aware component holds an `EntityManager` of its own, and the null-manager refusal above covers deletion for free.
[`attach()` and `detach()`](relationship.md#a-write-lands-on-the-side-doctrine-persists) are the third arm of the same argument, one tier up: they write an association rather than a record, and they route through the reader for the same reason.

The first component to replace a collection did **not** end up calling it.
The [relationship repeater](../forms/repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) leaves a dropped child out of the desired list and lets the mapping's `orphanRemoval` delete it, refusing an association that declares none rather than deleting on its own authority — because the mapping is where ownership is declared, and a form layer issuing DELETEs against a mapping that says "merely detach" is the data-loss shape `isOrphanRemoval` exists to warn about.

Its caller is the one anticipated above: the record no mapping owns.
A layout component [entangled with a to-one association](entangles-state-with-singular-relationship.md#a-switched-off-relationship-nulls-the-property-and-removes-the-record-explicitly) whose `relationship()` condition evaluates false nulls the property and calls `remove()` on what was there, `orphanRemoval` or not — the developer declared that this related record must not exist, which is a different statement from an end user dropping an item out of a list.
Nulling alone would not even reach the database on an inverse side, so the explicit call is what makes the outcome independent of which side of the association the component is declared on.

### `persist()` and `flush()` are the write half of the same seam

`remove()` alone covers the case where a component deletes a record it did not create.
The mirror case is a component that *creates* one: a relation-backed field offered a value the option list does not carry has to make the related entity before it can key it, and keying it needs an identifier, which only exists after the insert.
So the pair is `persist()` then `flush()` then `getRecordKey()`, and `persist()` deliberately does not flush — the caller decides where the write boundary sits, and a component creating several records in one pass should pay for one flush rather than one per record.

Where the identifier is **assigned** rather than generated, that order has one more step in front of it: [`hasIncompleteAssignedIdentifier()`](#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) runs before `persist()`, because a record with half a key flushes successfully and only fails when something tries to name it.

`flush()` takes no entity because ORM 3's `EntityManager::flush(): void` takes none either — the single-entity flush ORM 2 offered is gone, so the reader has no narrower boundary to expose even if a caller wanted one.

### The owning-side and orphan-removal flags are what a writer needs, not trivia

`AssociationTarget` carries nine fields because a writer cannot act correctly on fewer.
`targetClass` and `isToMany` say what to load and in what shape.
`isOwningSide` says whether writing this side changes the database at all — an inverse side is a read-through, so a component that writes one without also maintaining the owning side saves nothing, which is exactly the trap the fixture entities pin (`Post::$profile` is the **inverse** one-to-one; `Profile::$post` owns it).
`isOrphanRemoval` says whether dropping an item deletes the entity or merely detaches it — the difference between a cleanup and data loss, and not something a caller may guess.

The five the relationship seam added answer the questions a *relationship* writer asks on top of those.
`isManyToMany` picks between the two query shapes and the two write shapes — a many-to-many is a collection on both sides, everything else has a to-one field somewhere.
`mappedBy` names the owning field on the target and is read on inverse sides only (an owning side has none, and Doctrine's `getAssociationMappedByTargetField()` throws rather than answering null there); `inversedBy` is its mirror, the inverse field on the target, read on owning sides only and null where the mapping is unidirectional.
Between them they are how a write reaches the side Doctrine persists, how a one-to-many query finds the field to filter on, and what the re-keying guard tests against the target's identifier.
`isNullable` is a to-one owning side's join columns — **all** of them, `array_all(…nullable ?? true)`, so the flag is true only where every one of them admits a null — and deliberately `true` for every other shape, since a collection and an inverse side have no column of their own to be null and the flag reads as "nothing here forbids a null" rather than as a claim about a column that does not exist.
Only a to-one onto a composite class has more than one such column, so reading them all is no single-identifier change.
Reading only `joinColumns[0]` would let a foreign key whose **second** column is `nullable: false` pass `assertRelationshipAcceptsDetach()` and then die at flush — a `NotNullConstraintViolationException` that reaches the user two different ways, neither of them the named refusal the mapping deserved: [`DissociateAction`](#dissociating-is-refused-where-the-mapping-forbids-it) wraps nothing, so it is a 500 with a **closed** `EntityManager`, while `DissociateBulkAction`'s fetching arm catches and answers a danger toast over that same closed manager.
`orderBy` is the mapping's own collection order, typed `array<string, SortDirection>`: from ORM 3.7 the metadata normalises `#[ORM\OrderBy(['position' => 'ASC'])]` to PHP 8.6's `SortDirection` enum (a string being deprecated there), while `ToManyAssociationMapping::orderBy()` still declares `SortDirection|'asc'|'desc'`, so the reader converts any string it is handed through schemas' `Query\SortDirections::from()` — the one place a table direction string becomes the enum Doctrine's builder takes — and a consumer passes the value straight to `addOrderBy()`.
Until the floor rose to ORM 3.7 the field was the mapping's raw string, and tables lower-cased it with `mb_strtolower()`, which the enum turned into a `TypeError` on every relation-scoped table whose association declares an order.

### The accessor trait is shared with the table and action tiers

The reader is injected onto a consumer by a `#[Required]` setter with a `new AssociationReader()` fallback, the same idiom [`CanBeValidated`](../forms/can-be-validated.md) uses for `RecordTypeReader`: a container-built consumer gets the container's reader, one built with `new` gets an entity-manager-less one that answers `hasEntityManager()` false and throws on everything needing an EM.

The pair lived on the component-tier [`BelongsToRecord`](belongs-to-record.md) until the relationship seam gave two non-components the same need — `Table` builds its base query through the reader and `Action` writes an association through it — so it is [`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) now, composed by `BelongsToRecord` unchanged.
It keeps the `Components/Concerns/` namespace under the repo's placement rule: a trait a component uses at all lives there, whether or not something else uses it too.

The root [`Schema`](schema.md) composes it as well, beside `CanAccessProperties`, so the host's record check reaches the reader through the schema it validates — `$schema->getAssociationReader()` — and no host contract changed for it.
A schema built with `new` holds the manager-less fallback and so guards nothing, the standing `#[Required]` trade; every host builds its schemas through the container, and [`HasSchemasIdentifierGuardTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasIdentifierGuardTest.php) pins both faces.

The blast radius is worth stating because it is wider than it looks.
`BelongsToRecord` sits on the base `Component`, so **every** schemas component — and every component of every package built on schemas — consumes the reader service, where before nothing did and the container pruned it.
The service's `EntityManagerInterface` argument is `nullOnInvalid()` for exactly that reason: the schemas bundle boots inside forms', actions', notifications' and tables' test kernels, none of which registers Doctrine, and the setter has to autowire cleanly there.
A container-compile failure in one of those suites is the signal that the nullability wiring broke.

## Divergences

### `getReference()` throws where Doctrine returns null

`EntityManagerInterface::getReference()` is typed `object|null` in ORM 3, and [`EntityManager::getReference()`](../../../vendor/doctrine/orm/src/EntityManager.php) reaches null on two paths: the identity map holds an object of a different class under that identifier, or the entity has subclasses and the `find()` it delegates to misses.
Our signature is non-nullable `object`, and the null answer becomes a `LogicException` naming the class and the key — as does a key the class's own mapping cannot decode, and as does a `find()` on [the loading route](#getreference-decodes-first-and-then-picks-its-route) that names no row.
The reason is what the callers do with it: a reference is fetched to *bind* a chosen option onto a record, so a null would be written straight into the association and silently unset the relationship the user just chose.
Failing at the seam names the identifier that could not be referenced; the alternative pushes a null check onto every caller and gets it wrong once.

The two routes therefore disagree about one input, and it is recorded rather than reconciled: a **well-formed** key naming no row **of that class** is a lazy phantom proxy on the reference route — ORM behaviour, byte for byte what this class did before — and a `LogicException` on the `find()` route.
An inheritance root has no phantom at all, since Doctrine routes a class with `subClasses` through `find()` itself.
Making the scalar route agree would cost every single-identifier relationship write a `SELECT` it does not pay; making the loading route agree would mean minting a reference the ORM cannot build.

### `orphanRemoval` is a plain bool on the ORM 3 mapping object

`AssociationMapping::$orphanRemoval` is declared `public bool $orphanRemoval = false` — not an array key, not nullable — so it is read directly, with no null-coalescing fallback.
This is recorded because ORM 2 exposed association mappings as **arrays** (`$mapping['orphanRemoval']`, absent on many mappings), which is the shape most ported code and most training data assume; the array access still exists in ORM 3 only through `ArrayAccess` on the mapping object.
Level max rejects a `?? false` against a non-nullable bool, so the truthful form is the only one that compiles.

### Only the shapes Doctrine states in one mapping are read

`describe()` answers from a single `ClassMetadata` association mapping, so it covers the four shapes Doctrine declares there — many-to-one, one-to-many, one-to-one, many-to-many, in either direction.
Filament additionally reflects polymorphic (`morphTo`, `morphMany`), through- (`hasManyThrough`) and pivot-data relationships, all of which are Eloquent constructs with no single-mapping Doctrine equivalent: a Doctrine join entity is an ordinary entity with two associations, so the "pivot data" case is a nested schema over that entity rather than a mapping flag.
Skipped here on those grounds, not deferred — reading them would mean inventing a vocabulary Doctrine does not have.
