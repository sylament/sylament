---
description: The prebuilt header action that makes picked records members of a many-to-many relation-scoped table's owner through a record select.
ports:
  - vendor/filament/actions/src/AttachAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
  - vendor/filament/actions/resources/lang/en/attach.php
owns:
  - packages/actions/src/AttachAction.php
---
# AttachAction (actions)

[`Sylament\Actions\AttachAction`](../../../packages/actions/src/AttachAction.php) is the prebuilt many-to-many attach: a gray header trigger that opens a modal carrying one field, a record select over every target record the owner does **not** already have, and on submit makes the picked record(s) one of the owner's.
It only makes sense on a [relation-scoped table](../tables/table.md): `Table::relationship()` is what names the owner and the association, and without one the action refuses.
[`AssociateAction`](associate-action.md) carries the same record select, options and re-resolution code, so the entries below hold for it too.

Its undo, [`DetachAction`](detach-action.md), is a file of its own, with [`DetachBulkAction`](detach-action.md#surface--detachbulkaction) riding along in it.

[`AttachActionTest`](../../../packages/actions/tests/Unit/AttachActionTest.php) covers the class alone; the end-to-end cases live in the tables suite's [`AttachDetachActionsTest`](../../../packages/tables/tests/Integration/AttachDetachActionsTest.php), because only a table host supplies a relationship and the actions test kernel registers neither the tables nor the forms bundle, so the record select could not even be built there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/AttachAction.php) (`'attach'`) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`setUp()` chrome](../../../packages/actions/src/AttachAction.php) (label, heading, submit label, `modalWidth('lg')`, success title, `defaultColor('gray')`) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`schema()`](../../../packages/actions/src/AttachAction.php) (the record select as the modal's only field) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#the-record-select-is-a-forms-select-which-is-why-actions-requires-forms) |
| [`extraModalFooterActions()`](../../../packages/actions/src/AttachAction.php) (the `attachAnother` submit) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-another-arm-refills-the-schema-and-halts) |
| [The `action()` body](../../../packages/actions/src/AttachAction.php) (resolve, bind, `process()`, another-or-success) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-submitted-key-is-re-resolved-through-the-candidate-query) |
| [`getRecordSelectOptionsQuery()`](../../../packages/actions/src/AttachAction.php) (the candidate query) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#the-candidate-query-needs-no-inverse-relationship) |
| [`getRecordSelectOptions()`](../../../packages/actions/src/AttachAction.php) (search, limit, order, project or hydrate) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) (`$getOptions`) | done | — | [semantics](#the-title-fast-path-projects-the-attribute) · [diverges](#the-title-ordering-applies-to-both-arms-not-only-the-projected-one) |
| [`getRecordSelect()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#options-is-installed-only-on-the-preloaded-arm) · [diverges](#searchable-takes-a-re-keyed-list-not-the-raw-column-array) |
| [`recordSelect()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`recordSelectOptionsQuery()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#recordselectoptionsquery-is-evaluated-with-query-and-search) |
| [`attachAnother()` / `canAttachAnother()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`preloadRecordSelect()` / `isRecordSelectPreloaded()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#options-is-installed-only-on-the-preloaded-arm) |
| [`multiple()` / `isMultiple()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`recordSelectSearchColumns()` / `getRecordSelectSearchColumns()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-search-columns-are-validated-against-the-mapping) |
| [`forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-case-insensitive-flag-rides-searchconstraintlike) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | [diverges](#process-forwards-records-beside-relationship) |
| `tableSelect()` / `getTableSelectConfiguration()` / `getTableRecordSelect()` | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | blocked | forms' `TableSelect` | [detail](#the-table-picker-waits-on-forms-tableselect) |
| `disableAttachAnother()` (`@deprecated` for `attachAnother(false)`) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | skipped | — | [renames.md](../renames.md#renames) |
| The `allowsDuplicates()` guard (`whereDoesntHave()` made conditional) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | skipped | — | [diverges](#pivot-data-duplicates-morphs-and-relationshipjoiner-are-not-ported) |
| `getRelationCountHash()` self-join hash, `whereNotMorphedTo()`, `HasOneOrManyThrough`, `RelationshipJoiner` | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | skipped | — | [diverges](#pivot-data-duplicates-morphs-and-relationshipjoiner-are-not-ported) |

## Details

### The table picker waits on forms' `TableSelect`

Filament's `getRecordSelect()` returns a `Field`: a `Select`, or a `TableSelect` when `tableSelect()` names a table configuration.
The port has no `TableSelect` component, so `getRecordSelect()` returns a `Select` and the trio is unported until forms ships one.

## Semantics

### The submitted key is re-resolved through the candidate query

The modal posts a record key, and a record key is client input.
The submit never calls `find()` on it: it runs the **same** candidate query the select offered options from, narrowed by [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under the base `sylament_selected_record_keys`, and refuses when fewer records come back than keys went in.
A key the codec cannot decode is **dropped** by that predicate rather than bound, so it resolves nothing and falls into the same count check, which is why the refusal names three causes: a key that is malformed, one outside `recordSelectOptionsQuery()`'s scope, and one naming a record the owner already has are all simply absent from the result, so the count differs and the action throws rather than writing.
The check means what it says at any identifier width, because distinct records map to distinct keys by [the codec's injectivity](../schemas/record-key-contract.md#invariants).
Filament resolves through the relationship query the same way (`AttachAction`'s `whereIn(...)->get()`, `AssociateAction`'s `find()`), and skips silently what it does not find; the port makes the count check explicit because a silently dropped key would report success for a write that never happened.

A submission carrying **no** key at all is refused, with a `LogicException` naming the action, before that check rather than by it, because zero resolved records equals zero submitted keys and the count check would pass.
Filament's attach has no equivalent guard: its `->first()` hands `null` to `$relationship->attach(null)`, which parses to no ids and inserts nothing, so the action reports success.
The select's `required()` gates the ordinary browser path either way; the guard is for a `using()` or direct-call consumer that bypasses the schema.

In practice the select's own validation refuses first: an unlabelled value fails `Select`'s label probe, so the ordinary browser path answers with a validation error rather than an exception.
The re-resolution is what refuses a key that reaches the action body with the schema bypassed, or through a `recordSelect()` that reshapes the labelling.

The fetch runs one statement per batch of `IdentifierConstraint::batch()`: the whole submission for a single identifier, and at most 500 keys a statement for a composite one.
A forged `multiple()` submission therefore costs time linear in its length.
The batches' records merge in PHP, each record once: two spellings of one key that the database compares equal (a decimal `1` and `1.0`, or a case variant under a case-insensitive collation) can land in different batches and resolve the same managed record twice.
The count check then compares them against every submitted key, so a duplicated, doubly spelled or unknown key is refused as one statement refused it.
Pinned by [`CompositeAttachAssociateActionsTest`](../../../packages/tables/tests/Integration/CompositeAttachAssociateActionsTest.php), which also attaches and associates a 501-key submission whole.

### The title fast path projects the attribute

When the record title is a plain mapped attribute (a title attribute is set, the action declares no `recordTitle()` closure, and either the action set the attribute itself or the table declares no `recordTitle()` closure either), the option list is read as one projection: [`IdentifierConstraint::select()`](../schemas/record-key-contract.md#the-operations-and-their-dql)'s expressions plus `record.<title> AS sy_title`, one aliased expression per identifier field (`sy_key_0`, …), rather than hydrating every candidate.
Otherwise the candidates are hydrated and titled one by one through `getRecordTitle($record)`, which is what lets a table's `recordTitle()` closure decide the option labels.
This is Filament's `pluck()`-versus-`mapWithKeys()` branch, condition for condition; where the ordering sits is [a divergence](#the-title-ordering-applies-to-both-arms-not-only-the-projected-one).

Each of the three conditions has a witness, and the third one's is not an assertion on the labels: when the action sets its own title attribute, both arms answer the *same* strings, because the hydrating arm's `getRecordTitle()` falls through the action's own attribute exactly as the projection reads it, so the table's closure is never consulted either way.
What the two arms do not share is the hydration, so that case clears the identity map and asserts the candidate was never loaded into it.

Each projected row is keyed through the selection that projected it, never by reading a projected column back: the array hydrator converts a **field path**, so a target whose identifier is a value object would hydrate a key column into an *object*, which cannot key an array.
So each row is keyed by `KeySelection::keyOfHydrated($row)`, which takes every field as the hydrator handed it and converts only an `IDENTITY()` association half, raw under every hydrator, through its field's own DBAL type before printing, so the projected key is the key the record itself mints.
`keyOf()` would read the already converted fields through their types a second time, which mints another key, with no error, for a custom type whose reader does not read its own PHP value back unchanged ([the second reader](../schemas/record-key.md#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)).
The result alias is `sy_key_0`, not `sy_key` ([record-key.md](../schemas/record-key.md#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may)).

### `recordSelectOptionsQuery()` is evaluated with `query` and `search`

The closure receives the candidate `QueryBuilder` as `$query`, by name and by type, and the current search term as `$search`, which is `null` everywhere the query is built for something other than a search (the preload, the option-label lookups, the submit's re-resolution).
Returning null keeps the builder the action made; a builder returned replaces it.

Note the trap this shares with forms' `Select`: in Filament the **search string** also arrives as `$query` on `getSearchResultsUsing()`, so the two names mean opposite things one method apart.
The port's search closures take `$search` throughout.

### The search columns are validated against the mapping

`recordSelectSearchColumns()` names fields on the *target* entity, and each is checked against `ClassMetadata::hasField()` before it reaches the DQL: an unmapped name is a `LogicException` naming the action, the column and the class, not a Doctrine parse error further down.
With no columns declared, the title attribute is the searched column; with neither, a search matches everything the candidate query already admits.

### The case-insensitive flag rides `SearchConstraint::like()`

`forceSearchCaseInsensitive()` is handed to [`SearchConstraint::like()`](../schemas/search-constraint.md), whose `null` default is case-insensitive on PostgreSQL alone; `true` wraps the column in `LOWER()` and lower-cases the term on every platform.

### The "another" arm refills the schema and halts

`attachAnother` is a second submit action in the modal footer carrying `['another' => true]`.
The arm is five statements, `callAfter()`, `sendSuccessNotification()`, `record(null)`, `$schema->fill()`, `halt()`, and deliberately *not* `CreateAction`'s full create-another block: there is no `preserveFormDataWhenCreatingAnother()` counterpart to honour and no client-side state reset to dispatch, because the modal holds exactly one field and refilling it is the whole reset.

## Divergences

### The candidate query needs no inverse relationship

Filament excludes the already-related records with `whereDoesntHave($table->getInverseRelationship(), …)`, which needs the *name* of the inverse relation and, when parent and related sit on the same table, the `getRelationCountHash()` alias to disambiguate the self-join.
Doctrine needs neither: [`AssociationReader::createRelationshipCandidateQuery()`](../schemas/association-reader.md) roots the query at the target entity and negates a subselect built from the **owner's own** association, `NOT EXISTS (SELECT 1 FROM <Owner> sy_owner JOIN sy_owner.<relation> sy_related WHERE sy_owner.<id> = :sylament_relationship_owner AND <the correlation>)`, where the correlation compares `sy_related` to the outer alias field by field, so nothing is joined into the outer query and no inverse mapping has to exist.
`inverseRelationship()` / `getInverseRelationship()` are [not ported](../renames.md#renames) for the same reason.

### Pivot data, duplicates, morphs and `RelationshipJoiner` are not ported

Four Eloquent mechanics have no Doctrine referent and are skipped whole.

**Pivot columns and `allowDuplicates()`.**
Doctrine has no pivot model; a join table carrying attributes is an entity of its own, and its primary key is the pair, which is exactly what makes a duplicate row unrepresentable.
Dropping duplicates makes the candidate exclusion unconditional (Filament's `->when(! $table->allowsDuplicates(), …)` loses its condition) and collapses the detach to one branch.

**`RelationshipJoiner` / `Relation::noConstraints()`.**
Filament has to strip the parent constraint off a relation object and re-add the join by hand; the port has no relation object, because the reader builds a fresh query from the mapping.

**`getRelationCountHash()`.**
A self-join alias generator for the `whereDoesntHave` subquery the port does not emit.

**`whereNotMorphedTo()` / `HasOneOrManyThrough`.**
Doctrine maps no polymorphic association and no through-relation, so there is no shape to branch on.

### The title ordering applies to both arms, not only the projected one

Filament's `if (empty($relationshipQuery->getQuery()->orders)) { $relationshipQuery->orderBy($titleAttribute); }` sits **inside** the pluck branch ([`AttachAction.php:289-300`](../../../vendor/filament/actions/src/AttachAction.php), and [`AssociateAction.php:269-280`](../../../vendor/filament/actions/src/AssociateAction.php) alike), so the hydrate-and-title arm below it emits no `ORDER BY` at all and lists in whatever order the driver returns.
The port applies the order **before** the branch, in both classes: both arms order by the title attribute when the query carries no order of its own, so the option list reads the same whichever arm built it and a `recordTitle()` closure does not silently cost the sort.
A caller wanting the unordered arm back orders the builder itself in `recordSelectOptionsQuery()`; any order of its own suppresses this one.

### The record select is a forms `Select`, which is why actions requires forms

Filament's `getRecordSelect()` returns a `Field`, either a `Select` or a `TableSelect`.
The port has no `TableSelect`, so the return type is the concrete `Sylament\Forms\Components\Select`, in `AssociateAction` too.
That import is the reason `sylament/actions` **requires** `sylament/forms` rather than suggesting it: the class is unconditionally constructed on the modal's schema path, so no guard could make it optional.
Filament's own manifest carries the same edge.

### `options()` is installed only on the preloaded arm

Filament installs `options()` unconditionally, its closure answering `null` when the select is not preloaded, and validates every pick through the label probe, `getOptionLabelUsing()`.
The port installs it only for the preloaded arm, because the port's [`Select::hasFullOptionMap()`](../forms/select.md#the-choice-constraint-is-the-fields-only-server-side-guard) treats *any* `options()` configuration on a select with no relationship as the whole truth about which values are legal, and would then validate a searched pick against an empty map and reject it.
Not preloaded, the label probe through `getOptionLabelUsing()` validates a pick, as in Filament.
Preloaded, the map validates it, and the map is the first `optionsLimit` candidates: a pick found by searching past them is refused where Filament's label probe would accept it.
`dynamicOptions()` is installed either way, exactly as Filament sets it.

### `searchable()` takes a re-keyed list, not the raw column array

`recordSelectSearchColumns()` keeps Filament's `array<string>`, but [`Select::searchable()`](../forms/select.md) takes `bool|list<string>|Closure|null`, a list, because the searchable columns are iterated positionally.
Handing the getter through as Filament does (`->searchable($this->getRecordSelectSearchColumns() ?? true)`) fails level max with *"Type #3 from the union: `array<int|string, string>` might not be a list"*, so the port reads the getter once and passes `$searchColumns === null ? true : array_values($searchColumns)`, in `AssociateAction` too.
It is the same value for every consumer who writes a list literal, and a string-keyed array is re-keyed rather than rejected.
This is the one line of `getRecordSelect()` that is not the vendor's.

### `process()` forwards `records` beside `relationship`

Filament's default step receives `relationship` and resolves the record itself; the port's default step, [`attachRecords()`](../../../packages/actions/src/AttachAction.php), is handed `relationship` and `records`, the resolved list, so a `using()` override reads the picked records whatever the multiplicity.
The action binds `record()` only when exactly one record was picked.

### The strings are literals, not translation keys

Filament reads every string through `__('filament-actions::attach…')`.
The port hardcodes Filament's `en` values, `Attach`, `Attach :label`, `Record`, `Attach & attach another` and `Attached`, the same call every other prebuilt action's strings got.
