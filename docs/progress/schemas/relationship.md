---
description: '`Sylament\Schemas\State\Relationship` and the relationship arm of `AssociationReader` that scopes, lists and writes one owner''s association (draft)'
ports: []
owns:
  - packages/schemas/src/State/Relationship.php
---
# Relationship (schemas)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Relationship`](../../../packages/schemas/src/State/Relationship.php) — one owner record's association, by name | — | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`describeRelationship(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — the mapping at a relationship, refused rather than null | — | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`createRelationshipQuery(Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the owner's related records, rooted at the target | — | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`scopeToRelationship(QueryBuilder, Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — the same narrowing onto a caller's own builder rooted at the target, which `createRelationshipQuery()` is over a bare select | — | done | — | [semantics](#scopetorelationship-narrows-a-callers-query-rather-than-building-one) |
| [`createRelationshipCandidateQuery(Relationship, string $alias = 'record')`](../../../packages/schemas/src/State/AssociationReader.php) — every target record NOT related to this owner | — | done | — | [semantics](#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping) |
| [`attach(Relationship, object $related)`](../../../packages/schemas/src/State/AssociationReader.php) — make a record one of the owner's, in memory | — | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) · [diverges](#no-to-many-relation-verb-moves-a-primary-key) |
| [`detach(Relationship, object $related)`](../../../packages/schemas/src/State/AssociationReader.php) — the mirror removal, after the dissociate refusals | — | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) |
| [`assertRelationshipAcceptsAssociate(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — whether an existing record may join this collection at all | — | done | — | [diverges](#no-to-many-relation-verb-moves-a-primary-key) |
| [`assertRelationshipAcceptsDetach(Relationship)`](../../../packages/schemas/src/State/AssociationReader.php) — the four dissociate refusals alone, for a bulk arm that writes by key | — | done | — | [semantics](#a-write-lands-on-the-side-doctrine-persists) · [diverges](#no-to-many-relation-verb-moves-a-primary-key) |
| Composite records as relationship-query **owners** — both queries, both arms, both sides | — | done | — | [semantics](#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path) · [diverges](#the-owner-scope-joins-through-the-owners-table-and-pays-a-self-join-postgresql-does-not-remove) |

## Semantics

### The relationship query is rooted at the target and needs no inverse mapping

A [`Relationship`](../../../packages/schemas/src/State/Relationship.php) is an owner object and a property name, and nothing else — Filament's counterpart is an Eloquent `Relation`, which is a query, a pair of models and a set of write primitives at once, and Doctrine has no such object to port.
Every fact about it is read on demand, so the value object stays free of an `EntityManager` and the reader stays the only seam that holds one.

`describeRelationship()` is `describe()` with the null answers turned into refusals, because a relation-scoped table has nothing to fall back to: no entity manager, an owner whose class is not mapped, a property Doctrine maps no association at, and a **single-valued** association each get a message naming the fix.
The last is the mirror image of [`requireSingularRelationshipTarget()`](entangles-state-with-singular-relationship.md) one component over — that one refuses a collection where it wanted a record, this one refuses a record where it wanted a collection.
The owner's class is read off the metadata rather than off `$owner::class`, so what is reported and embedded in the DQL is the canonical mapped name; with native lazy objects (both rigs enable them) that is `$owner::class` already.
The owner itself must be a hydrated entity or a PHP native lazy object: `getEntityClass()` runs `isTransient($owner::class)` *before* any metadata read, and a classic `Proxies\__CG__\…` subclass carries no entity attribute of its own — PHP does not inherit attributes — so the driver reports it transient and the owner is refused as not a managed entity.

Both queries select the **target** entity as their single root under a caller-chosen alias, defaulting to `record`.
That is load-bearing rather than stylistic: every consumer downstream reads `getRootAliases()[0]` and the first root entity to learn what the table lists, so a query rooted anywhere else would answer the wrong class.
The narrowing is then per shape:

- a many-to-many, either side, uni- or bidirectional, is `EXISTS (SELECT 1 FROM <Owner> sy_owner JOIN sy_owner.<name> sy_related WHERE sy_owner.<id> = :sylament_relationship_owner AND <correlation>)` — a subselect over the **owner's own** association, which is why no inverse mapping is needed and why the outer query gains no join;
- a one-to-many is `record.<mappedBy> = :sylament_relationship_owner`, straight onto the target's own to-one field;
- **either** arm, where the owner is keyed by more than one field, is the many-to-many shape above with the owner's own key spread across it — the one-to-many included, because DQL cannot compare a mapped-by path to such a record at all ([the whole of that route](#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).

The correlation is [`IdentifierConstraint::correlate()`](record-key-contract.md#the-operations-and-their-dql) and nothing else — `sy_related.id = record.id` for a single-`id` target, one `AND`ed equality per identifier field for every other one.
**The obvious spellings are both silently wrong.**
`sy_related = record`, comparing two identification variables, compiles to the **first identifier column alone** and returns rows that are not related at all; `record MEMBER OF sy_owner.<name>` compares every inverse join column against the target's *first* identifier column and returns nothing.
Both are Doctrine's own, neither errors, and the trap is [recorded on the codec](record-key.md#comparing-two-identification-variables-compares-the-first-identifier-column-only) because it constrains every correlation in the toolkit rather than this one.
`MEMBER OF` is therefore not a later optimisation for bidirectional mappings; it is a shape that cannot be used at all.

`SELECT 1` is legal DQL in a subselect — it parses to `SELECT 1 AS sclr_N` — so the projection needs no per-flavour branch, and the conversion itself adds no parameter: under a **single-identifier owner** the builder still carries exactly **one**, which is what kept every consumer that mints an alias off `count($query->getParameters())` where it was.
An owner keyed by several fields binds one parameter **per identifier field** (`sylament_relationship_owner_0`, `_1`, …), so those counted stems sit above the owner's width and shift by it — a relation-scoped table's per-column search mints `sy_x3` / `:sylament_search_3` under a scalar-pair owner where a single-`id` owner mints `sy_x2`, which tables' `CompositeOwnerRelationshipTableTest` pins as a literal.
It is the **owner's** width that moves them and not the key's in general: the target's width binds nothing at all, since `IdentifierConstraint::correlate()` emits equalities between two aliases.
The single-identifier rows are the rows the old `record.<id> IN (SELECT sy_related.<id> …)` returned, and the conversion is plan-neutral for the related query and an improvement for the candidate one on PostgreSQL, where `NOT EXISTS` plans as a Hash Anti Join against `NOT IN`'s hashed sub-plan filter.

There is **no flavour branch on the target**, and the cheap test would have been wrong: a **single derived identity** reports `isIdentifierComposite === false`, and it is the one flavour the old subselect did not even refuse — it emitted the association as a bare path in a `SELECT` list, which Doctrine rejects as *"Invalid PathExpression"* at execution with no message of ours in front of it.
The **owner** is the one axis where that flag is read, and deliberately: it is the whole of the route test, and the same single derived identity is on the narrow side of it ([why the flavour is the right question there and the wrong one here](#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).

The candidate query is the negation.
The many-to-many arm is the same string prefixed `NOT `; the one-to-many arm is where it stops being symmetrical, because `record.<mappedBy> <> :owner` alone drops every record whose field is null — SQL's `<>` is unknown against null — so the arm is `record.<mappedBy> IS NULL OR record.<mappedBy> <> :owner`, built as one `orX` composite so a caller's later `andWhere()` (a search clause, a by-key `IN`) brackets the pair instead of flattening the OR into the AND.
Every one-to-many candidate query runs [`assertRelationshipAcceptsAssociate()`](#no-to-many-relation-verb-moves-a-primary-key) first, so a relationship whose every candidate would be a theft refuses at the first options read rather than offering them.
The call sits **above** the route branch rather than inside the mapped-by arm, so the refusal keeps its place in the order for an owner of any width — although it cannot fire for a composite one: such an owner can never be half of a child's identifier, Doctrine's own mapping validation refusing the declaration before any of this runs.

Three names are reserved inside these builders and a caller must not reuse them: the aliases `sy_owner` and `sy_related`, and the parameter base `sylament_relationship_owner`, which for an owner of several fields mints `_0`, `_1`, … per field and is one plain parameter otherwise.
Doctrine refuses either alias as a caller's own (`'sy_related' is already defined`), so that half of the reservation is enforced rather than merely stated; the base is not, and it is [recorded on the codec](record-key-contract.md#parameter-naming-and-what-an-empty-or-malformed-key-means) beside the other ten.
`getRecordKey()` refuses an owner that has no identifier yet on every route, which is the difference between a clear refusal and a query that silently matches nothing; [what is bound after it](#a-single-identifier-owner-binds-its-identifier-value-typed-by-the-fields-own-dbal-type) is the one thing about the owner that is not the same for every width.
Neither query orders anything: the mapping's `#[ORM\OrderBy]` is the *table's* default sort, not the query's, so a clicked sort can replace it.

### `scopeToRelationship()` narrows a caller's query rather than building one

`createRelationshipQuery()` builds its own select of the target and narrows it; `scopeToRelationship()` is that narrowing alone, applied **in place** to a builder the caller hands it, which must be rooted at the relationship's target under `$alias` (default `record`) — and `createRelationshipQuery()` is now exactly `scopeToRelationship()` over `createOptionQuery()`, so the two cannot drift.
It appends one `andWhere()` — the mapped-by equality or the correlated `EXISTS`, by the same route test — and binds the owner, so every condition, join and parameter the caller's builder already carries holds beside the owner's; Doctrine brackets the appended predicate whenever the builder already had a `WHERE` and the predicate carries its own `AND`.
A builder with no root under `$alias` is refused before anything is appended, naming the relationship, the owner's class and the alias: `Cannot scope a query to the relationship [<name>] of [<Owner>]: it has no root under the alias [<alias>], which the scope narrows.` — without it, a mapped-by arm would compile against an alias nothing declares, and an `EXISTS` arm would correlate to one.

It exists for panels' nested resources: `BelongsToParent::getParentScopedRecordQuery()` hands it a clone of the resource's own `getRecordQuery()` under that builder's first root alias, so an override that hides rows holds on every nested page ([the composition](../panels/nested-resources.md#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write)).
The three reserved names above are the caller's to avoid in the builder it hands over, and only half of that is enforced: Doctrine refuses a caller's own `sy_owner` or `sy_related` alias, while a caller parameter named `sylament_relationship_owner` would be overwritten by the owner's.
A caller builder that fetch-joins a collection keeps the join, so a single-result read over it meets every joined row — Filament's composition carries the same exposure.
[`AssociationReaderScopeToRelationshipTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderScopeToRelationshipTest.php) pins the caller's condition kept on the mapped-by arm, on a single-identifier many-to-many and on both arms of a composite owner, the builder returned being the one handed in, `createRelationshipQuery()` printing the same DQL as the scope over a bare select, and the refusal leaving the builder untouched.

### An owner of any width is scoped through its own collection rather than through a mapped-by path

An owner keyed by several fields takes the shape the many-to-many arm takes for **every** owner — a correlated `EXISTS` over the owner's own association rather than an `IN (SELECT …)` subselect — on both arms of both queries:

```
SELECT record FROM <Target> record WHERE EXISTS (
  SELECT 1 FROM <Owner> sy_owner JOIN sy_owner.<relationship> sy_related
  WHERE sy_owner.<f0> = :sylament_relationship_owner_0
    AND sy_owner.<f1> = :sylament_relationship_owner_1
    AND <IdentifierConstraint::correlate('sy_related', <Target>, 'record')>)
```

and the candidate query is that string under `NOT EXISTS`.
`JOIN sy_owner.<rel>` walks a one-to-many's inverse collection exactly as it walks a many-to-many, from either side, so the outer builder keeps its single root, gains no join and no second alias, and answers `getAllAliases()` with the root alone — for every owner flavour, which is what lets every consumer downstream go on reading `getRootAliases()[0]` and minting its own aliases off the builder.

**The route test is `isIdentifierComposite` on the owner, and only that** (`$target->isManyToMany || $this->hasCompositeOwner(…)`).
A single identifier and a **single derived identity** owner therefore keep all three of the texts the paragraphs above this section describe, byte for byte — the one-to-many `record.<mappedBy> = :owner`, its `IS NULL OR <> :owner` negation, and the many-to-many subselect scoped by `sy_owner.<identifier[0]> = :owner`.
Taking every owner through the subselect was executed and rejected: it moves four pinned assertions and puts `sy_owner`/`sy_related` on every one-to-many query in the toolkit, for rows that do not change.

**The owner scope is one `equals()` on the subselect's alias, and that is the whole surface a composite owner adds.**
`IdentifierConstraint::equals($query, 'sy_owner', $ownerClass, $this->getRecordKey($owner), 'sylament_relationship_owner')` — the owner handed over as the **key** it mints rather than as values, so [the codec](record-key-contract.md#owner-scoping-was-reserved-and-turned-out-not-to-be-needed) gained nothing at all.
A `null` out of `equals()` **throws**:

> `The owner [%s] mints the key [%s], which does not decode against its own mapping, so the relationship [%s] cannot be scoped to it — an identifier type that cannot read its own printed form.`

Dropping the scope instead would list every record that has *any* owner, which is the one failure this seam must not have; and the shape that reaches it is the one the codec records for a DBAL type that can neither read back nor take back what it prints, so the message names the cause rather than the width.
A type that refuses its printed form on read only is not that shape: its key decodes [through the type's writer](record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer), and an owner with such a half — `Pallet` — scopes both queries on both arms, while `Vault`, whose half refuses it both ways, still throws before anything is executed.
The same refusal fires for an owner whose PHP value is spelled other than its DBAL type prints it — a `bigint` held as `'007'`, a decimal as `'.5'` — which no loaded record carries.

**The negation is `NOT EXISTS`, and that is not cosmetic.**
A candidate arm written as `NOT (<the equality text>)` drops a target whose composite foreign key is **partially** `NULL` — half-null rows are exactly what an unenforced schema leaves behind — where `NOT EXISTS` keeps them, together with the records that belong to nobody.
The one-to-many's `IS NULL OR <>` pair exists for the same reason one route down.

**Neither builder ends in a trailing `setParameter()` any more on this route.**
The composite arm binds itself while its scope is composed, so the single `setParameter('sylament_relationship_owner', …)` both builders used to close with returns early for it — leaving it would be *"Too many parameters: the query defines 2 parameters and you bound 3"*.
What the guard that used to stand here refused is gone with it: a composite owner was refused by name because Doctrine will not bind such a record as a query parameter, and nothing binds one now.

The scope stays inside the `WHERE` for a further reason, and it binds any later re-shaping.
A custom bulk action's `$selectedRecordsQuery` is the table's query, and a closure calling `->delete()` or `->update()` on it is supported: Doctrine's `QueryBuilder` builds a bulk `DELETE` or `UPDATE` from the `FROM`, `SET` and `WHERE` parts alone and drops every join, so the correlated `EXISTS` survives the conversion unchanged.
A scope that narrowed by **joining** the owner would not survive it: a predicate carried in the join's `WITH` is dropped silently, and one in the `WHERE` over the joined alias is a parse error, on relation-scoped tables alone.

### A single-identifier owner binds its identifier value, typed by the field's own DBAL type

The three single-identifier DQL texts did not move, and neither did any driver statement's text or parameter value for a built-in type without SQL-side conversion; the binding type now follows the field's DBAL type — `bigint` binds as a string, as Doctrine's own `BigIntType` does.
One thing did: the parameter is the owner's identifier **value** under that value's DBAL type, rather than the owner entity, for a plain **field** mapping and for a single **derived** identity alike, in one arm for both: the value is `RecordKey::values($entityManager, $owner, $ownerClass)[0]` — the codec's own walk, `@internal`, which reduces an identifier association to its target's value at any depth — and the type is `IdentifierFields::types[0]`, the **terminal** field's, resolved through the association exactly as the decode resolves it.
So an owner keyed on a `Post` binds the integer `1` under `integer` where it bound the entity, one keyed on a `Listing` that listing's `Sku` under `sku`, and one keyed on a raw-bytes `Crate` its `Uuid` under `binary_uuid`.
`getRecordKey()` still runs first, as the gate it was: it carries the codec's refusals, so an owner with no identifier yet, or one whose target's identifier is blank or unprintable, is refused before anything is bound — and the `?? throw` behind it is there for the type system alone.
The bind is also what makes this class the first reader of the `@internal` `IdentifierFields` outside `Identity\`, in the same package.

No enum unwrap stands in front of that bind, and none is needed — an identifier mapped with `enumType`, declared or inferred, is read through `ReflectionEnumProperty`, which already answers the backing value (executed); the only property shape that would hand the bind an enum cannot be persisted or hydrated on ORM 3.6.8; the prototype carried such an unwrap for a day and removed it as dead code.

It is a fix rather than a tidy-up, and the defect it closes is single-identifier.
Doctrine binds a managed entity by *casting* its identifier and never consults the field's DBAL type (`Query::resolveParameterValue()`), so an owner whose type really converts — a database form that is not the printed form — was scoped **wrong in silence**: its related list answered nothing and its candidate list answered everything.
No test could see it, because the DQL is identical either way and no fixture carried such an owner.
`symfony/uid`'s `UuidType` on MySQL, MariaDB or SQLite (`BINARY(16)`) is that shape, which is every relation manager of a Uuid-keyed owner there — **executed** against a bridge-shaped copy of that type: the entity bind answered nothing on either arm of either query, and the typed bind answers the right rows.
PostgreSQL's native `uuid` is unaffected.

The derived arm was the same defect one hop deeper.
Converting there means reaching into another class's identifier, which is exactly what the codec already does for every identifier association, so the bind asks the codec rather than doing it itself.
Executed on SQLite and PostgreSQL 18.4 before the change: onto a converting target the entity bind scoped by the printed form, wrong rows in silence, and onto a raw-bytes target it related nothing and offered every record as a candidate.
One owner failed loudly instead: an **unflushed** derived owner whose target's identifier is assigned was handed to PDO as an entity, a PHP `Error` (*could not be converted to string*); it binds its target's value now and answers nothing related.
The route not taken is the **key**: scoping a derived owner through the key it mints would refuse one onto a doubly-strict target, whose key never decodes, although a single owner of that same type already scopes by value — a refusal the step would have invented rather than inherited.
Pinned over `ListingSeal`, `CrateSeal` and `StrongboxSeal`, one onto each shape, on both arms of both queries, in [`AssociationReaderOwnerBindTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerBindTest.php), where the case that bound `PostSeal` as the record itself became one binding its target's `1` under `integer`.

### A write lands on the side Doctrine persists

`attach()` and `detach()` write in memory and leave the flush to the caller, the same boundary [`persist()`](association-reader.md#persist-and-flush-are-the-write-half-of-the-same-seam) already draws.
Both go through [`PropertyAccessor::setAssociationOnRecord()`](property-accessor.md) — Symfony's accessor diffs the whole desired list against what the property holds and calls the record's own adder and remover per element — so a domain adder with logic in it still runs, and the collection instance survives for Doctrine to diff.
The contract that buys is the one forms already states: a collection-valued association needs an adder and a remover, a to-one a setter, or Symfony names what is missing.
A to-one with no setter follows the accessor's leaf rule instead: a readable property that already holds the record written (by identity) is left alone, and one holding another record, or none yet, is refused by name with a `LogicException`.
Symfony's own refusal remains only for a property that is neither writable nor readable ([the leaf rule](property-accessor.md#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value)).

Which record is written is decided by the mapping, not by which side the caller named.
An inverse-side collection write emits **zero SQL** at flush, so a relationship declared from the inverse side of a many-to-many is redirected: the write lands on the *related* record's `mappedBy` collection with the owner as the member.
A one-to-many is always inverse, so it never writes a collection at all — it points the target's `mappedBy` field at the owner, or at null.
Attaching a record the owner already holds is a no-op, caught by an identity check before the accessor is asked to diff.

That redirection is only witnessable against an entity that does **not** heal it.
A bidirectional adder that calls its counterpart back (`Category::addPost()` calling `Post::addCategory()`, the shape `make:entity` generates) makes a wrong-side write land on the owning collection anyway, so the branch selector above passes every assertion no matter which arm it picks.
The fixture pair drops those callbacks for exactly that reason: the inverse collection stays untouched after a write, and the case reads it as the evidence of which side was written.

The redirect writes the related record's collection and never the owner's, so after `attach()` or `detach()` on an inverse side the owner's own in-memory collection is stale until it is reloaded.
`AttachAction`, `DetachAction` and their siblings redirect and re-read, so nothing shows it; forms' inverse-side save, which re-renders in the same request, reconciles through these two and then writes the owner's collection itself ([has-relationship.md](../forms/has-relationship.md#an-inverse-side-many-to-many-is-saved-through-the-owning-side-and-still-written-on-its-own)).
It is left that way on purpose: the one caller that re-renders mends it itself, and every other reads the owner afresh.

Where the owning collection declares `orphanRemoval`, that same redirect is a deletion, and the reader does not refuse it.
`detach()` takes the owner **out** of the target's collection, and Doctrine treats such a collection's members as privately owned, so the one taken out is deleted rather than unlinked — the record the caller was writing, with every join row it had.
`assertRelationshipAcceptsDetach()` returns for a many-to-many before it reads the flag at all, and the flag it would read is the one on the side the caller named, which is not the side that deletes; its first refusal is the analogous **one-to-many**, refused by name.
So the refusal sits on the one route into it: forms' [inverse-side save](../forms/has-relationship.md#an-inverse-side-many-to-many-is-saved-through-the-owning-side-and-still-written-on-its-own) computes its removals first and throws when the owning side deletes orphans.
Neither verb moved for it.

None of this half reads the owner's identity, and that is a property of the code rather than a claim about it: `attach()`, `detach()`, `describeRelationship()` and both assertion members mint no key, bind no parameter and build no query, so they round-trip under every owner flavour, on both arms and from either side, and did so before the query half could.
They are pinned rather than edited — the tests are the record that a later sweep must not route these four through the reader's query to "make them composite-aware".

`attach()` runs one guard on every one-to-many — [the re-keying refusal](#no-to-many-relation-verb-moves-a-primary-key) — and `detach()` runs `assertRelationshipAcceptsDetach()`, whose four refusals are ordered so that each of them is reachable:

1. the owner's association declares `orphanRemoval` — removing from such a collection *deletes* the record, so dissociating is not something the mapping permits, and `DeleteAction` is the action that means what the caller meant;
2. the target's mapped-by field is part of the target's own **identifier** — nulling it would erase half the record's identity, so `DeleteAction` is again the action that was meant;
3. the target's join column is `nullable: false` — read as `isNullable` off `describe($targetClass, $mappedBy)`, the owning side of the same association;
4. the target's setter or property rejects null — `PropertyAccessor::acceptsNullOnRecord()`, which is the PHP-level twin of (3) and fires where the column is nullable but the type is not.

Only the first of them fires on any one mapping, which is why the rig carries a separate fixture per refusal rather than one entity tripping all four.
The second is ordered **before** the third deliberately: an `#[Id]` to-one's join column is `nullable: false` in `ClassMetadata`, so such a mapping was already refused — by (3), advising the developer to make the column nullable, which for an identifier column is impossible.
The refusal did not move; the advice did.
A many-to-many always passes all four: nothing is deleted and no column goes null, only a join row disappears.
The method is public and separate from `detach()` because a bulk arm that dissociates **by key** — one SQL `UPDATE` rather than a per-record write — has to run the same refusals before its statement, with no record in hand to run them against.

## Divergences

### The owner scope joins through the owner's table, and pays a self-join PostgreSQL does not remove

The correlated `EXISTS` reaches the target **through** the owner's own table, where a mapped-by comparison reaches it through the foreign key alone, and two consequences follow that no amount of indexing removes.

The first is cost, and it was measured rather than guessed.
On an ordinary owner — of the order of ten children — the related query costs ≈0.05–0.09 ms against ≈0.02–0.03 ms for a bare `IDENTITY()` comparison, which is noise; PostgreSQL 18.4 plans **that** read as `Nested Loop → HashAggregate → Index Scan on <target>_pkey`, so the foreign-key index finds each child and the primary key then re-fetches it.
On a **pathological** one — 50 000 children of a single owner, out of 200 000 — a page of 25 costs 19.8 ms against 4.0 ms, an unbounded read 21 ms against 2.9 ms, and the paginator's `COUNT` 17 ms, the unbounded read planning as a `Parallel Hash Semi Join` over a sequential scan instead.
That is an admin panel's page, not a hot path, and it is accepted on those terms.

The faster shape is recorded so a later change can take it against a real profile rather than rediscover it, and so is what it cannot do.
**A′** is `IDENTITY(record.<mappedBy>, '<f0>') = :p0 AND IDENTITY(…, '<f1>') = :p1` — no join, no subselect, no alias — and two things a first sketch of it missed are what keep it out.
Its candidate arm cannot be written the way today's one-to-many arm is: `record.<mappedBy> IS NULL OR …` is *"A single-valued association path expression to an entity with a composite primary key is not supported"*, so the null arm has to be spelled per field as well.
And it cannot reach an **association**-valued key half at all — `IDENTITY(record.<mappedBy>, '<assocHalf>')` is *"Undefined reference field mapping"* — so it covers an all-scalar owner only, which would mean a second candidate form, a flavour branch [no consumer ever takes](record-key.md#doctrines-identifierflattener-is-not-adopted-because-it-misreads-a-chained-derived-identity), and a new codec operation, for 0.03 ms on the owner anybody actually has.
**B**, a join from the target to the owner, is not slower but **wrong**: it returns the wrong rows on every many-to-many candidate cell and flips the cardinality the paginator reads.

The second consequence is a shape rather than a number, and it is **executed** rather than inferred: because the subselect names the owner's own table, a Doctrine SQL filter on the owner class narrows it, and an owner row that is missing behind an unenforced foreign key makes the `EXISTS` false — so such an owner lists nothing where a direct foreign-key comparison would have listed its children, and its candidate list offers its own attached records.
The missing-owner row exists only where the foreign key is unenforced — PostgreSQL refuses it.
Neither is reachable from anything the toolkit ships.

Two platforms were never executed.
PostgreSQL 18.4 and SQLite were; MySQL and MariaDB are historically weak on correlated `EXISTS`, and while the many-to-many arm has this shape for every owner — so the toolkit already depends on the platform's semi-join handling — the one-to-many arm under a composite owner joins that dependency.

### A child of a chained derived identity cannot be hydrated against a cold identity map

An owner whose identifier association targets a class whose **own** identifier is an association — a derived identity chained one level deeper — scopes correctly here and its many-to-many arm works whole.
Its one-to-many arm is unreachable for a reason outside this seam: Doctrine fatals hydrating any **child** of such an owner against a cold identity map, `UnitOfWork::getIdHashByIdentifier()` running `implode()` over an object, and it does so on a plain `find()` of the child, with no query of ours involved.
So there is no refusal of ours — the failure is loud, immediate and Doctrine's own — and no fixture anywhere carries such a back-reference, because one poisons every cold hydration of the child class rather than only the case that declares it.

### A JOINED leaf whose root declares the mapped-by cannot be scoped to an owner keyed by several fields

Under an owner keyed by several fields the scope is the correlated `EXISTS`, and its `JOIN sy_owner.<collection>` over a **JOINED leaf** whose mapped-by the **root** declares puts the root's foreign-key columns on the leaf's alias — `INNER JOIN Toaster t3_ ON d2_.region = t3_.depot_region`, a column the leaf's table does not have — so the query fails on read before anything else runs.
Plain DQL reproduces it with no code of ours involved, so it is Doctrine's and recorded rather than refused.
A single-identifier owner is unaffected, since its scope is the mapped-by equality and joins nothing, which is why tables' `Depot`, the owner of the `Toaster` leaf, is keyed by one field.

### A quoted single join column breaks the mapped-by scope of a single-identifier owner

`SqlWalker::walkPathExpression()` writes a single-column to-one's join column unquoted even where the mapping quotes it, so a reserved-word column is a syntax error (`… WHERE o0_.select = ?`) and a relation-scoped query under a **single-identifier** owner with such a mapped-by fails on read.
An owner keyed by several fields scopes through the correlated `EXISTS`, whose join the walker does quote.
Doctrine's, recorded rather than worked around, and the reason tables' `Purchase`, whose join columns are quoted reserved words, hangs off `Bazaar`, an owner keyed by two fields.

### No to-many relation verb moves a primary key

Doctrine will happily re-point a to-one association that is part of the target's own identifier, and the `UPDATE` it emits moves the row's **primary key**.
Where that association is what a relationship is declared over, every **to-many** relation verb inherited the behaviour, and each one is a silent data defect rather than an error: associating an existing record **steals it from its other owner**; where the new owner already holds that key it is a `UniqueConstraintViolationException` that closes the `EntityManager`; and every candidate such a relationship could offer is by definition another owner's record, because the `IS NULL` arm can never match a primary-key half.
The join-entity-with-payload shape — `Article 1—* ArticleTranslation(article, locale)` — is the commonest composite mapping there is, so this is not an exotic corner.

One predicate answers it — `! $target->isManyToMany && in_array($target->mappedBy, $targetMetadata->identifier, true)` — at four points under two messages: three of them share the associate refusal through one private builder, and the detach point carries its own advice, because it is one rule:

1. `assertRelationshipAcceptsAssociate()`, called inside the one-to-many arm of `createRelationshipCandidateQuery()`, so an `AssociateAction` refuses at the first options read — the moment every other reader refusal fires — and a consumer's `recordSelectOptionsQuery()` modifier never sees a query that offers only thefts.
2. `attach()`'s one-to-many arm, refusing exactly the state that re-keys: an **existing** record whose mapped-by value is an object **other than** this owner.
   A record the `EntityManager` does not manage yet has no primary key to move, so it passes whatever its mapped-by holds — being written under this owner is precisely what a create form building its child does, including the arm whose enabled relationship `Select` already picked a different owner — and so does a record pointing at nobody or at this owner.
   `CreateAction`'s attach-before-persist and panels' `attachRecordToParent()` are therefore untouched.
   The comparison is instance identity, which is what Doctrine's identity map guarantees for a managed owner.
3. `assertRelationshipAcceptsDetach()`, [above](#a-write-lands-on-the-side-doctrine-persists), where the shape was already refused for the wrong reason and only the advice changed.
4. forms' to-many relation-backed write — the `Select::multiple()` and `CheckboxList` arm of `HasRelationship::saveStateToRelationship()` — which calls (1) as the **first** statement of its to-many arm, before the per-key `getReference()` loop, so a write about to be refused costs no `SELECT` at all.
   Selecting another owner's child there re-keys it through the owner's adder, which is the forms twin of the associate defect.

The predicate is deliberately narrow: re-pointing an **ordinary** child at a new owner stays the pinned feature it has always been, and a many-to-many never refuses, because a join row is not the target's identity.
What the refusal costs the caller is named in its own message: create the record under this owner, or move the association out of the identifier — and for join-entity children with a payload, `Repeater::relationship()` is the field that creates and deletes them and never re-points one.

The heading says **to-many** for a reason, and it is the one thing to read off this section before relying on it.
A **to-one** relation-backed write is not refused here: `Select::make('post')->relationship()` on an *edit* form of a derived-identity record — and the same field inside an existing join-entity repeater item, which is the field this trail recommends for join entities — writes the picked reference onto the identifier half, and unguarded, Doctrine would emit the key-moving `UPDATE` above verbatim, closing the `EntityManager` when the new key is taken.
The guard cannot be the one above, because a **create** form has to keep writing that field; it is a key-**change** guard, [a pass of the record check's own](association-reader.md#a-changed-identifier-is-put-back-and-refused-and-costs-no-statement), which skips a record the unit of work has not keyed — what a create form writes — and puts a moved reference back under the field.

Recorded rather than closed: `DetachBulkAction` refuses no one-to-many the way its twin does, so a bulk detach over a mapping the reader refuses fails on its **first** record — `detach()` runs `assertRelationshipAcceptsDetach()` as the first statement of its one-to-many arm — and it fails **inside** the arm's `try`, which turns a developer error into the danger toast a stale key produces.
The twin raises the same refusal up front, outside both `try` blocks, where it propagates.
