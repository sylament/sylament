---
description: '`HasRelationship`, the Doctrine association arm `Select` and `CheckboxList` share, with `Select`''s relationship search, label and ignore-record arms'
ports:
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/src/Components/Concerns/HasPivotData.php
owns:
  - packages/forms/src/Components/Concerns/HasRelationship.php
---
# HasRelationship (forms)

[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) is composed by [`Select`](select.md) and [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged).
`Select`'s own relationship-only members (the search, label and ignore-record arms) are methods of `Select.php`, and they are written up here beside the trait they build on rather than in [select.md](select.md), which owns the class.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`relationship()`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) — to-one and to-many, over the [association reader](../schemas/association-reader.md) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#relationship-is-three-lazy-closures-over-the-association-reader) · [semantics](#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design) · [diverges](#the-save-arm-writes-a-desired-list-not-attachdetach) |
| The relationship search and label arms — `getSearchResultsFromRelationship()`, `getOptionsFromRelationship()` (searchable / preload / limit / ordering), `getOptionLabelFromRelationship()`, `getOptionLabelsFromRelationship()`, and the shared `getOptionLabelFromRecordUsing()` trio | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) · [diverges](#a-search-column-is-a-mapped-field-and-the-title-ordering-skips-what-it-cannot-order) |
| `relationship()` `ignoreRecord:` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#ignorerecord-narrows-the-two-option-arms-and-neither-label-arm) · [diverges](#a-record-of-another-class-than-the-association-target-is-refused) |
| `pivotData()` | [`Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | skipped | — | [diverges](#the-save-arm-writes-a-desired-list-not-attachdetach) |

## Semantics

### `relationship()` is three lazy closures over the association reader

[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) stores the title attribute and the optional query scope, then wires three closures and nothing else.
Each takes its component as `self`, which resolves to whichever class composed the trait, so [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) takes the same three unchanged; the option query the first of them runs is built and scoped in one place — `createScopedOptionQuery()`, the builder *before* it runs, which `getRecordsFromRelationship()` executes for the trait's own map and which `Select`'s [search, label and selected-record arms](#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) constrain, limit and order first.
Those four arms are `Select`'s, installed by its own `relationship()` override on top of the trait's three; the trait keeps what both fields use, which also includes the record-label trio (`getOptionLabelFromRecordUsing()` / `hasOptionLabelFromRecordUsingCallback()` / `getOptionLabelFromRecord()`, which Filament declares on both `Select` and `CheckboxList` because it has no shared trait) and the one record→label mapping both maps are built from, `resolveOptionLabelFromRecord()`: the callback when declared, the title attribute read through the accessor otherwise.
The association *name* and the target resolution below live in [`BelongsToRelationship`](../../../packages/forms/src/Components/Concerns/BelongsToRelationship.php), which [`Repeater::relationship()`](repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) composes too for the same three-arm refusal, over [schemas' trait of the same name](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields) that the entangled layout components share — the forms trait is only the adapter that says "The [name] field" and that a field has its own name to fall back on.
The clauses that differ per field — why an unresolvable relationship is fatal for *this* field, and what to do instead — stay with each field as small hooks, so no message loses a word to the sharing:

- **`options()`** — [`AssociationReader::describe()`](../schemas/association-reader.md) resolves the record class's mapping at that property, `createOptionQuery()` builds `SELECT record FROM <target> record`, `modifyQueryUsing` scopes it, and each row becomes its `getRecordKey()` mapped to the title attribute, read through [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md).
  The keys are the identifier strings the client speaks, so PHP canonicalizes numeric ones to `int` array keys — the same vocabulary [`getEnabledOptionValues()`](can-disable-options.md#the-choice-choices-cover-both-forms-the-raw-state-can-take) already reconciles, which is why the `Choice` guard needed no relationship arm.
  A key of two or more identifier fields always carries `~`, so it is never numeric and never coerced — [the codec's grammar](../schemas/record-key-contract.md#the-grammar) is what makes that a guarantee rather than a hope; a **single derived identity** is one field and coerces exactly as a single `id` does.
- **`loadStateFromRelationshipsUsing()`** — the association value's record key (null when unset), or the list of keys for a to-many.
  This is the [hook pair](../schemas/belongs-to-record.md) `fill()` consults instead of the ordinary record read, which would have skipped the path entirely: an object-valued leaf is skipped by `readStateFromRecord()`, and a collection is refused by `setOnRecord()`.
- **`saveRelationshipsUsing()`** — the reverse, through [`AssociationReader::getReference()`](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route), so `getData()` writes managed references without loading the target rows — except where an identifier field is itself an association, which cannot be referenced without a load and costs one `SELECT` per key.
  The to-one arm is pinned across three flavours by *writes a single composite selection back onto its record*, and that case also records what the cost is **not**: by the time a to-one write resolves its reference, `getData()`'s validation pass has already enumerated the options and hydrated the chosen row, so a forms-level write never takes the reader's cold route.
  The cold route is the reader's own, and schemas' `AssociationReaderCompositeKeyTest` is where it is pinned.

Every one of them resolves the record class and the reader **at evaluation time**, never at `relationship()` time — the same lazy shape [`Repeater::refuseObjectCollectionRecord()`](repeater.md) uses, and what lets `relationship()` be declared before the component is attached to a schema.
The to-one/to-many branch is read off the mapping on each call rather than captured: all three go through `getMultiplicityMatchedRelationshipTarget()`, which reads `isMultiple()` on the same call and refuses the two shapes where the field and the mapping disagree — a to-many association the field never declared `multiple()` for (every selection but one would be dropped on the way in, and the save would write a single reference over the collection), and a to-one association it did (a list of selections has no single record to map onto).
Both sides are still read per call rather than snapshotted, so `multiple()` may be declared on either side of `relationship()`; it is the *pairing* that is fixed, not the declaration order.

All three take the component as an injected parameter (`static function (self $component)`, where Filament types it `Select $component`) rather than closing over `$this`.
[`getClone()`](../schemas/component.md) is a shallow `clone`, so a closure bound to `$this` would keep pointing at the *original* component after the clone — every per-item schema a `Repeater` builds is a clone of the child schema, so a relationship select inside one would read the template's configuration, not the item's.
`Select`'s `ignoreRecord:` is where that is observable: it excludes the record bound to the item schema, which a closure bound to the template would never see.

`$name` defaults to the field's own name at declaration time (`$name ?? $this->getName()`, Filament-exact), which is what keeps `hasRelationship()` false for a select that never declared one — the fallback lives in the assignment, not in the getter.

The option map is memoized on the component, and reset by `relationship()` itself — again Filament-exact, and load-bearing here because a non-searchable select's map is the *whole* target table: `getOptions()` runs at least twice per request (once through `getEnabledOptionValues()` during validation, once from the template) and without the memo each is another unbounded `SELECT`.
A field declaring `modifyQueryUsing` neither reads nor writes the memo, because a scoping closure may read state that changes within the request and a cached first answer would freeze it.
The map is read exactly once per render, in [`getExtraTemplateData()`](select.md#getextratemplatedata-makes-the-one-getoptions-read-both-branches-share), for the same reason.

The concern imports `Doctrine\ORM\QueryBuilder` directly, so `forms` declares `doctrine/orm` in its own `require` rather than inheriting it through `schemas` — the split-repo rule, since each package is published standalone.

### A relationship map is full or an exception, except where search makes it partial by design

The `Choice` guard enumerates `getOptions()`, so an option map that comes back empty does not fail open — it fails *closed*, rejecting every value the user can pick, with a violation that names nothing wrong with the submission.
That is the one outcome a relationship select must never produce **by accident**, and it is exactly what a silently-degrading options closure would produce: no entity manager, no record class, or a property Doctrine maps nothing at, each yielding `[]`.
So the trait's closure throws instead.
Each arm names which of the three it hit and what would fix it, and the reader's own [`getReference()`](../schemas/association-reader.md) and `createOptionQuery()` throw on the same missing entity manager from the write side.
There is no fallback option map anywhere in the concern; a select whose options the port cannot enumerate is a configuration error, not a runtime state.

One empty-map arm is **not** an accident: `Select::getOptionsFromRelationship()` answers `[]` for a searchable select that is not preloaded (Filament returns `null` there; ours keeps the `array` type and the validation builder distinguishes the two by predicate), and a limited map for one that is.
Neither is the truth about a submitted value, and neither reaches the `Choice`: `hasFullOptionMap()` is false for every searchable relationship select, and the [label probe](select.md#the-choice-constraint-is-the-fields-only-server-side-guard) — which fetches the submitted key through the same scoped query — guards it instead.
So the invariant holds where it matters: a full map is validated as a full map, and a partial one is never validated as if it were full.
Because `isSearchable()` [defaults to `isMultiple()`](select.md#multiple-forces-the-js-control-and-is-searchable-by-default), a `multiple()` relationship select is on this arm unless it says `preload()`.

One consequence worth stating: on either arm the save hook receives an identifier that has already been checked against the target table — through the map or through the probe — by the time it runs.
Its own "not an identifier" throw is therefore a belt-and-braces guard for a state that reached `getData()` without validation, not the primary defence.

### The relationship search arm runs a `LIKE` per column over the scoped option query

Every searchable, preload, limit and label arm lives on `Select`, not in the shared trait — as in Filament, where every one of these members sits on `Select` — because [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) composes the same trait and has none of `isPreloaded()`, `getSearchColumns()`, `getOptionsLimit()` or the closures.
The trait keeps what both use: the cardinality guard, `createScopedOptionQuery(?string $search)` (create + scope, with `modifyQueryUsing` receiving the term as `$search` — `null` outside a search — beside `$query`), and the record→label mapping.
`Select::relationship()` calls the trait's, then installs four arms unconditionally on top of the three option-action defaults:

- **`getSearchResultsUsing` ⇒ `getSearchResultsFromRelationship(?string $search)`.**
  Blank `getSearchColumns()` ⇒ `[]` (an untitled relationship has nothing to search; Filament's empty nested `where` is a no-op, and an empty `orX()` would be malformed DQL).
  Otherwise the scoped query gains `andWhere(orX(SearchConstraint::like(...) per column))` — schemas' [`SearchConstraint::like()`](../schemas/search-constraint.md), the engine tables' own `SearchConstraint` delegates to, which lowers both sides on Postgres or when `forceSearchCaseInsensitive()` says so — then a limit **unconditionally**: a `setMaxResults()` the scope set wins and is written back through `optionsLimit()` (so the control's own cap agrees), else `getOptionsLimit()` (50); then the title ordering; then execute and map.
  Search columns are DQL field paths on the target (`name`, or `address.city` into an embeddable), and `getSearchColumns()` defaults to `[$titleAttribute]` when `searchable()` named none.
- **`getOptionLabelUsing` ⇒ `getOptionLabelFromRelationship()`.**
  `getSelectedRecord()` first, `null` when it answers none, else the record's label — the record-label callback when declared, the title attribute through the accessor otherwise, the same two branches Filament's install has.
- **`getSelectedRecordUsing` ⇒ the scoped query narrowed by the record key**, `null` on a miss ([above](select.md#the-option-actions-are-suffix-actions-with-doctrine-defaults)).
- **`getOptionLabelsUsing` ⇒ `getOptionLabelsFromRelationship(array $values)`.**
  The scoped query narrowed by [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under the base `sylament_values`, mapped the same two ways.
  The raw client list is filtered to its `int` and `string` entries first — it is client state, so a nested array or an object is dropped rather than failing the batch — and an empty result returns `[]` **before** the query is built, so a list with nothing scalar in it issues no statement at all.
  No ordering, as Filament's has none.

`getOptionsFromRelationship()` is overridden on `Select` too, composing gates → query → limit → ordering → execute → map so the limit and the ordering are on the query before it runs: the searchable-and-not-preloaded gate, the memo, then the limit under Filament's narrower condition (`isSearchable() && filled(getSearchColumns())`, the scope's own limit still winning and writing back), then the title ordering, then the trait's mapping.
Two Filament-parity details ride along.
Both relationship option arms **order by the title attribute** when the scoped query carries no `orderBy` — on the title-attribute path only, exactly where Filament puts it; the record-label-callback branch orders nothing, so a `CheckboxList` with a callback and no title attribute is untouched, and the trait's own map is unordered ([recorded on that field](checkbox-list.md#the-relationship-option-map-is-unordered)).
And the callback branch is what the shared `getOptionLabelFromRecordUsing()` trio makes reachable on `Select`: with it, both the map and the search results label rows through the callback, in the order the database returns them.

### `ignoreRecord:` narrows the two option arms, and neither label arm

`relationship(ignoreRecord: true)` excludes the bound record from the *pickable* set of a self-referential association, so a row cannot point at itself.
It is applied at exactly two sites — `getOptionsFromRelationship()` and `getSearchResultsFromRelationship()` — and never inside `createScopedOptionQuery()`, which four arms share.

The asymmetry is load-bearing rather than an oversight of Filament's.
The two label arms (`getSelectedRecordUsing` and `getOptionLabelsFromRelationship()`) must keep resolving a value that already names the excluded row — legacy data, or a row written before the flag was added — or the control renders no label at all *and* the [`Choice` guard](select.md#the-choice-constraint-is-the-fields-only-server-side-guard) rejects an untouched submission, so a self-parented row could not be saved at all.
Narrowing the choke point instead would be one line shorter and corrupting.

The option memo is bypassed whenever the flag is on, for the same reason a `modifyQueryUsing()` one is: the map is record-dependent, and [`getClone()`](../schemas/component.md) is a shallow clone, so a memoised map would be served to every repeater item that cloned the field.

The bypass is written twice — the read is guarded and so is the write — and each half is redundant on its own: an instance that never writes the memo has none to read, and one that never reads it does not serve a stale map either.
Only the pair is pinned by a test, because no public call sequence can populate the memo while the flag is on.
The redundancy is kept for fidelity to Filament, whose own guard is at four sites.

Note the default: `relationship(ignoreRecord:)` is `false` while [`unique(ignoreRecord:)`](can-be-validated.md) is `true`.
Both are Filament's, and the contrast is deliberate — a uniqueness probe on an edit page almost always wants to skip the row being edited, while an option map almost never needs to.

The exclusion is `andWhere`d **only when the predicate is non-null**, which is the contract's binding rule (asking for the text is what binds the parameters, so dropping the text leaves parameters bound to nothing).
That `null` arm is unreachable from this call site and is kept anyway: the key it passes was just minted by `findRecordKey()` for a record the method has already proved is an instance of the association's target, so it always decodes.
No test can pin it, and it is correct for the seam rather than for the caller — do not "simplify" it into an unconditional `andWhere`.

`HasRelationship::toRelationshipIdentifier()`'s narrowing to `?string`, with its `(string)` cast, is the same shape one step further out: the cast is an **equivalent** mutation, because its sole consumer `AssociationReader::getReference()` itself opens with `(string) $id`, so no test can observe it.
It is kept as a type-level statement — the relation layer speaks record keys, not `int|string` — and the sentence exists so that a sweep does not restore the wider union and a later reviewer does not re-report the unkillable cast as a gap.

### A composite label read runs one statement per batch, and a composite submission past `maxItems()` reads no label

`getOptionLabelsFromRelationship()` reads one statement per batch of `IdentifierConstraint::batch()`, and merges the answers into a map keyed by record key.
For a single identifier that is the whole list in one statement over one array parameter, as cheap for a long list as for a short one.
For a composite one it is a balanced OR of ANDs per batch, at most 500 keys a statement, which is the read a forged list makes expensive.
That cost is why a composite submission longer than `maxItems()` skips the label read and answers with the `Count` violation alone, while every other `multiple()` select keeps reporting both ([the divergence](#a-composite-relationship-select-past-maxitems-answers-the-count-alone)).
`hasCompositeRelationshipTarget()` asks the target's class metadata (`isIdentifierComposite`) and issues no statement.

### The relation tier reads the owner's class and never its identity

`relationship()` on this field and on [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged), and the relationship arms of [`Repeater`](repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) beside them, reach the owner record for exactly two things: the class its mapping is read off, and the collection or reference the value is written onto.
None of them builds an owner-scoped query — the option list is [`createOptionQuery()`](../schemas/association-reader.md#the-option-query-is-a-starting-point-not-a-result) over the whole target class, and the loaded state is the association value read off the record — so the width of the owner's identifier is not a question this tier asks, and a record keyed by two scalars, by an association plus a scalar or by an association alone carries these fields as any record does, though the owner half of the relationship queries is composite-aware.

That is recorded rather than left implicit because the obvious "make it composite-aware" sweep is a regression here: routing a to-many field's load through [`createRelationshipQuery()`](../schemas/relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path) would buy a statement and a correlated subselect for a collection the owner is already holding.
The one owner-facing member the tier does call is `assertRelationshipAcceptsAssociate()`, which reads the owner's **class** and the target's identifier — [the re-keying refusal](../schemas/relationship.md#no-to-many-relation-verb-moves-a-primary-key), not a scope.
An inverse-side many-to-many save calls the reader's `detach()` and `attach()` as well — [the write half](../schemas/relationship.md#a-write-lands-on-the-side-doctrine-persists), which mints no key and binds nothing, so the tier still never asks the owner's identity.
Tests pin the whole tier under a composite-owner record so the sweep has a failing test to meet rather than a silent one to pass.

## Divergences

### A search column is a mapped field, and the title ordering skips what it cannot order

Filament's `applySearchConstraint()` qualifies whatever column string it is given and lets the database reject a bad one.
Here a search column has to be a field the target entity **maps** — read off the class metadata's field names, which spell an embeddable's as `address.city` — and one that is not (an association hop such as `author.name`, or a virtual property the accessor could read but DQL cannot) is refused up front with a `LogicException` naming the column and the limitation, because a Doctrine `QueryException` from inside a search request is a 500 with no name in it.
Searching through an association is the recorded gap; a `modifyQueryUsing` that joins can still constrain by hand.
The title ordering both option arms add applies the same test the other way: a title attribute that is not a mapped field — one read through an association or a getter — leaves the database's order alone rather than throwing, since the label read still works and the ordering was never part of the shipped contract (the relation layer ordered nothing).

### A composite relationship select past `maxItems()` answers the count alone

Laravel's validator, which Filament validates through, reports every failing rule, so an oversized submission carrying an unknown value answers both the `max` and the `in` message.
A `multiple()` relationship select whose target is keyed by more than one field answers only the `Count` violation once the submission is longer than `maxItems()`.
The membership check behind it is skipped: for a searchable select it is a batched label read that a forged list would make expensive, and for one with a full option map it is a `Choice` over every option, skipped with it.
The submission is refused either way ([why](#a-composite-label-read-runs-one-statement-per-batch-and-a-composite-submission-past-maxitems-reads-no-label)).
Single-identifier relationship, static-options and enum selects report both the `Count` and the membership `Choice` violation, as Filament does.
Reversible if the composite label read ever costs what the single-identifier one does.
Pinned by [`SelectCompositeRelationshipTest`](../../../packages/forms/tests/Integration/Components/SelectCompositeRelationshipTest.php), with [`SelectMultipleTest`](../../../packages/forms/tests/Integration/Components/SelectMultipleTest.php) pinning the static-options pair.

### The save arm writes a desired list, not attach/detach

Filament's `saveStateToRelationship()` speaks Eloquent relation verbs: `associate()` for a `BelongsTo`, and for a `BelongsToMany` a `detach()` of the diff followed by `sync(detaching: false)` — plus `unsetRelation()` to drop the stale in-memory copy, and `syncWithPivotValues()` when `pivotData()` is set.
Doctrine has no equivalent verbs on the entity: the association *is* the property, and the unit of work computes the difference at flush.

The port therefore writes the whole desired list onto the property, through [`PropertyAccessor::setAssociationOnRecord()`](../schemas/property-accessor.md) — Symfony's accessor diffs it against the collection by strict identity and calls the entity's own `addX()`/`removeX()` per element, so the collection instance survives, the owning side is maintained by the adder the maker generated — or, where the field stands on the **inverse** side of a many-to-many, [by a reconcile through the association reader](#an-inverse-side-many-to-many-is-saved-through-the-owning-side-and-still-written-on-its-own) — and Doctrine emits only the join rows that actually changed ([characterized in `DoctrineCollectionWriteTest`](../../../packages/schemas/tests/Integration/State/DoctrineCollectionWriteTest.php)).
Kept elements diff as kept because a reference by key answers from the identity map with the very instances the loaded collection holds — on **both** of [`getReference()`'s routes](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route), the no-load reference and the `find()` an identifier association forces.
Two things follow from the mechanism rather than from a choice of ours: removing an element from a `ManyToMany` deletes the join row only — the target entity survives, since nothing declares `orphanRemoval` there — while a `OneToMany` that does declare it deletes the child, which is Doctrine's contract and not something the select decides.

`pivotData()` has no home in this shape at all, and is **skipped** rather than blocked — for `Select` and for [`CheckboxList`](checkbox-list.md) alike.
Filament's concern names extra columns to write onto a `BelongsToMany` pivot row.
A Doctrine many-to-many has no columns to address: a join table the mapping owns is not a row object anything can set a column on, and a join carrying columns of its own is by definition a join *entity*.
The recipe for one already ships: `Repeater::relationship()` over the join entity, with a nested relation select inside the item schema for the far side, and one `Hidden::make('role')->default(…)` for a constant column.
A `pivotData()` shim on top of that would be a second, worse spelling of the same thing, so none is added — the fatal names the missing method, which is the clearest signal available.

The single-flush shape also removes Filament's `dehydrated(false)` for the multiple arm.
Upstream it exists because a `BelongsToMany` cannot be written as a model attribute and is saved afterwards by a separate `saveRelationships()` pass; here the save hook fires *from* the dehydrated bag ([`getData()`](../schemas/belongs-to-record.md)), so switching dehydration off would silence the hook instead of protecting it — the multiple relationship select stays dehydrated, deliberately.

### An inverse-side many-to-many is saved through the owning side, and still written on its own

Doctrine persists a many-to-many from its **owning** side alone; an inverse collection is ignored at flush.
So a to-many `relationship()` field on the inverse side — a [`CheckboxList`](checkbox-list.md) over a tag's posts is the likely one — would write the desired list onto a collection nothing persists, and unless the entity's adder happened to call its counterpart back (the shape `make:entity` generates), the save would report success and write **no join row at all**, whatever the width of the record's identifier.
Filament never meets it: `BelongsToMany` writes the pivot from either end.

The to-many arm therefore asks the mapping which side persists.
On the inverse side of a many-to-many it first reconciles the desired list onto the owning side through the [association reader](../schemas/relationship.md#a-write-lands-on-the-side-doctrine-persists) — every current member missing from the desired list is `detach()`ed and every new one `attach()`ed, both of which flip to the related record's own collection — and **then** writes the record's own collection as the owning side does.
Kept members are touched by neither, so dropping one member and adding another costs one join-row `DELETE` and one `INSERT`, for a single-identifier record and a composite one alike.
The second write is not redundant: it is what the form re-fills from when it re-renders in the same request, and reconciling the owning side alone would re-render the **old** selection; it costs no statement, the reconcile's own read having loaded that collection already.
A field on the owning side never reaches the reconcile, and neither does a one-to-many — the guard names a many-to-many, since a one-to-many is always inverse and routing it through `detach()` would refuse an `orphanRemoval` mapping by name, which the composite-owner one-to-many saves of [`CompositeOwnerRelationshipTest`](../../../packages/forms/tests/Integration/Components/CompositeOwnerRelationshipTest.php) would meet at once.

One save is refused rather than written.
Reaching the owning side means `detach()`, which takes **this** record out of the target's collection; where that collection declares `orphanRemoval` Doctrine treats its members as privately owned and deletes the one taken out rather than unlinking it — here the record the form is saving, with every join row it had.
So the arm computes its removals first and, only when there is one, reads the owning side's own mapping and throws:

> `[%s::$%s] declares orphanRemoval, so a member dropped from it is deleted rather than unlinked — deselecting one in the [%s] field would delete the record this form is saving rather than its join row. Put the field on the owning side of the association, or drop orphanRemoval.`

A save that only **adds** never reaches it, which is why the refusal is computed from the removals and not from the mapping alone.
It costs one statement and writes nothing at all — not even a transaction — so the record and every join row it had survive; an owning side without the flag still deletes the dropped member's join row beside it.
Nothing that would otherwise work is refused, since without the reconcile such a save would write nothing either — the mapping is named instead of being ignored in silence.
The reader refuses the analogous **one-to-many** by name and cannot see this one ([why](../schemas/relationship.md#a-write-lands-on-the-side-doctrine-persists)), which is what puts the refusal here.

Two costs come with it.
The adder-and-remover contract reaches the **related** records' collections too, so a getter-only owning collection behind an inverse-side field throws Symfony's `NoSuchPropertyException` rather than saving nothing in silence; no test pins that arm, since every owning collection the fixtures carry has its adder.
And each added member is read before it is written: `N + 1` reads for `N` added members in a real form, whose options are already loaded, and `2N + 1` cold.
Adding to each related record's `PersistentCollection` directly would skip those reads, but it bypasses the entity's own adder, which is what this whole arm rests on, so it is not taken.

Pinned by [`InverseSideRelationshipTest`](../../../packages/forms/tests/Integration/Components/InverseSideRelationshipTest.php), over fixtures whose inverse adders deliberately heal nothing, since a healing adder would make the save pass without the reconcile too.

### A record of another class than the association target is refused

Filament's exclusion clause is `where($record->getQualifiedKeyName(), '!=', $record->getKey())` — a fully qualified column from the *record's own* table.
Pointed at a query over another table it produces `article_contributors.id != 7` inside a select over `authors`, which the database rejects.
Loudly wrong.

Our clause is [`IdentifierConstraint::notEquals()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under the base `sylament_ignored_record` — `record.<identifier> <> :sylament_ignored_record` for a single-`id` target, a negated per-field group for any other width — over the option query's own alias, so the same mistake produces perfectly valid DQL that silently excludes an unrelated row of the *target* table.
The Doctrine shape is more dangerous than the original, so it carries a guard the original does not need: a bound record that is not an instance of the association's target throws a `LogicException` naming both classes.
`ignoreRecord: true` only ever means a self-referential association, and a repeater item schema stamped with the child record is exactly the shape that would otherwise hit it.

The quiet arms stay quiet: `null`, an array and a class-string record exclude nothing — a create page has no row to exclude — and neither does an entity whose identifier is not complete yet, which [`findRecordKey()`](../schemas/association-reader.md#findrecordkey-answers-null-where-getrecordkey-throws) answers `null` for — a generated id still pending, and an **assigned** string component still sitting blank, which is a create form's normal state for a composite key.
