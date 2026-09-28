# AttachAction (actions)

[`Sylament\Actions\AttachAction`](../../../packages/actions/src/AttachAction.php)
— the prebuilt many-to-many attach: a gray header trigger that opens a modal
carrying one field, a record select over every target record the owner does
**not** already have, and on submit makes the picked record(s) one of the
owner's. It is the first action that only makes sense on a
[relation-scoped table](../tables/table.md) — `Table::relationship()` is what
names the owner and the association, and without one the action refuses.

Its undo, [`DetachAction`](detach-action.md), is a file of its own, with
[`DetachBulkAction`](detach-action.md#detachbulkaction) riding along in it.

The success toast is a title here, `successNotificationTitle('Attached')`; a
caller wanting a whole notification object passes one to `successNotification()`,
which [`CanNotify`](action.md) supplies to every action alike.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).
Every ported member is done; what keeps the row short of complete is the
`tableSelect()` trio, blocked on forms' unported `TableSelect`.

Reference — the Filament source this is diffed against:

- [`actions/src/AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php)
  — the class this ports, whole
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait it composes, ported whole (row in [README](README.md#features))
- [`actions/resources/lang/en/attach.php`](../../../vendor/filament/actions/resources/lang/en/attach.php)
  — the strings it sets
- [`support/src/Services/RelationshipJoiner.php`](../../../vendor/filament/support/src/Services/RelationshipJoiner.php)
  — the Eloquent machinery the candidate query stands in for

Covered by
[`AttachActionTest`](../../../packages/actions/tests/Unit/AttachActionTest.php)
(the chrome, the footer arm, the record select's shape and the hostless refusal)
and end to end by
[`AttachDetachActionsTest`](../../../packages/tables/tests/Integration/AttachDetachActionsTest.php)
in the **tables** suite — the candidate list, the search, the closure-titled
arm (the table's closure, the action's own closure, and the action's own title
attribute overriding a table that titles by closure), the preloaded arm, the single and multiple submits, the attach-another arm,
both smuggled-key refusals and the empty-submission one, and beside those the
select's own arms: the title ordering (over song 2, whose two candidates sort by
title in the opposite order to their keys, so the assertion is on the `ORDER BY`
rather than on the insertion order) on both the plucking and the hydrating
branch, `recordSelectOptionsQuery()` narrowing the candidates through each of
its two documented injections — the builder by parameter name with the search
beside it, and the builder by type hint — a third arm that orders the builder
itself, whose order survives (the escape hatch below), the unmapped-search-column
refusal,
and the `LOWER()` that `forceSearchCaseInsensitive()` puts in the emitted SQL and
its absence without the flag. It sits there for the
reason
[`PrebuiltDeleteActionTest`](delete-action.md) does, one step further: only a
table host supplies a relationship, and the actions test kernel registers
neither the tables nor the forms bundle, so the record select could not even be
built there.

## Surface

Every member Filament's class carries. _Blocked by_ names what has to land
first. _Detail_ carries **diverges** → the note in [Divergences](#divergences),
and/or **semantics** → the permanent note in [Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/AttachAction.php) (`'attach'`) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading (`"Attach {title-case record label}"`), submit label, `modalWidth('lg')`, success title, `defaultColor('gray')`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`schema()` — the record select as the modal's only field](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#the-record-select-is-a-forms-select-which-is-why-actions-requires-forms) |
| [`extraModalFooterActions()` — the `attachAnother` submit](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-another-arm-refills-the-schema-and-halts) |
| [The `action()` body — resolve, bind, `process()`, another-or-success](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-submitted-key-is-re-resolved-through-the-candidate-query) |
| [`getRecordSelectOptionsQuery()` — the candidate query](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) (`RelationshipJoiner::prepareQueryForNoConstraints()` + `whereDoesntHave()`) | done | — | [diverges](#the-candidate-query-needs-no-inverse-relationship) |
| [`getRecordSelectOptions()` — search, limit, order, pluck-or-hydrate](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) (`$getOptions`) | done | — | [semantics](#the-title-fast-path-projects-the-attribute), [diverges](#the-title-ordering-applies-to-both-arms-not-only-the-projected-one) |
| [`getRecordSelect()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#options-is-installed-only-on-the-preloaded-arm) |
| [`recordSelect()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`recordSelectOptionsQuery()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#recordselectoptionsquery-is-evaluated-with-query-and-search) |
| [`attachAnother()` / `canAttachAnother()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`preloadRecordSelect()` / `isRecordSelectPreloaded()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [diverges](#options-is-installed-only-on-the-preloaded-arm) |
| [`multiple()` / `isMultiple()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | — |
| [`recordSelectSearchColumns()` / `getRecordSelectSearchColumns()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | [semantics](#the-search-columns-are-validated-against-the-mapping) |
| [`forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | done | — | The flag rides [`SearchConstraint::like()`](../schemas/README.md), which already defaults to case-insensitive on PostgreSQL alone. |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | The default body is [`attachRecords()`](../../../packages/actions/src/AttachAction.php), forwarded `relationship` and `records`. |
| `tableSelect()` / `getTableSelectConfiguration()` / `getTableRecordSelect()` | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | blocked | forms' `TableSelect` (unscheduled) | `getRecordSelect()` returns a `Select`, never the `Field` union Filament needs for the two-shape return. |
| `disableAttachAnother()` | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | skipped | — | A `@deprecated` alias of `attachAnother()`; the port ships no deprecated Filament surface — [renames.md](../renames.md#renames). |
| The `allowsDuplicates()` guard (`whereDoesntHave()` made conditional, the pivot-keyed detach) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | skipped | — | [diverges](#pivot-data-duplicates-morphs-and-relationshipjoiner-are-not-ported) |
| `getRelationCountHash()` self-join hash, `whereNotMorphedTo()`, `HasOneOrManyThrough`, `RelationshipJoiner` | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | skipped | — | [diverges](#pivot-data-duplicates-morphs-and-relationshipjoiner-are-not-ported) |

## Semantics

Faithful ports whose behaviour is easy to misread. Permanent.

### The submitted key is re-resolved through the candidate query

The modal posts a record key, and a record key is client input. The submit
never calls `find()` on it: it runs the **same** candidate query the select
offered options from, narrowed by
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
under the base `sylament_selected_record_keys`, and refuses when fewer records
come back than keys went in. A key the codec cannot decode is **dropped** by that
predicate rather than bound, so it resolves nothing and falls into the same count
check — which is why the refusal names three causes rather than two. A key that
is malformed, one outside `recordSelectOptionsQuery()`'s scope and one naming a
record the owner already has are all simply absent from the result, so the count
differs and the attach throws rather than writing. The check still means what it
says at any identifier width, because distinct records map to distinct keys by
[the codec's injectivity](../schemas/record-key.md#invariants). Filament resolves
the same way — its `whereIn(...)->get()` on the relationship query is the same
idea — and the port makes the count check explicit because a silently-dropped
key would otherwise report success for a write that never happened.

The message moved for **every** relation, a single-identifier one included:
`The action [%s] was asked to attach a record its select never offered` is
unchanged to the character, and what follows it is new — `— the key is
malformed, outside recordSelectOptionsQuery()'s scope, or already attached.`
Anything asserting the leading sentence still passes; anything asserting the
message whole does not.

A submission carrying **no** key at all is refused before that check rather than
by it, since zero resolved records equals zero submitted keys and the count check
would pass. Filament has no equivalent guard because it does not need one: its
`->first()` hands `null` to `$relationship->attach(null)`, which throws. The
select's `required()` gates the ordinary browser path either way; this is the
schema-bypassed one.

There are two gates in front of that one, and the end-to-end test asserts both.
The select's own validation runs first: an unlabelled value fails
`Select`'s label probe, so the ordinary browser path answers with a validation
error rather than an exception. The re-resolution behind it is what refuses a key
that reaches the action body with the schema bypassed.

Since M7 · 7 the fetch runs one statement per batch of
`IdentifierConstraint::batch()`: the whole submission for a single identifier,
and 500 keys a statement for a composite one. A forged `multiple()`
submission therefore costs time linear in its length. The batches' records merge
in PHP, each record once: two spellings of one key that the database compares
equal — a decimal `1` and `1.0`, or a case variant under a case-insensitive
collation — can land in different batches and resolve the same managed record
twice. The count check then compares them against every submitted key, so a
duplicated, doubly spelled or unknown key is refused as one statement refused it.
Pinned by
[`CompositeAttachAssociateActionsTest`](../../../packages/tables/tests/Integration/CompositeAttachAssociateActionsTest.php).

### The title fast path projects the attribute

When the record title is a plain mapped attribute — a title attribute is set, the
action declares no `recordTitle()` closure, and either the action set the
attribute itself or the table declares no `recordTitle()` closure either — the
option list is read as one projection —
[`IdentifierConstraint::select()`](../schemas/record-key.md#the-operations-and-their-dql)'s
expressions plus `record.<title> AS sy_title`, so
`record.id AS sy_key_0, record.<title> AS sy_title` for a single-`id` target and
one aliased expression per identifier field for any other — rather than by
hydrating every candidate. Otherwise the candidates are hydrated
and titled one by one through `getRecordTitle($record)`, which is what lets a
table's `recordTitle()` closure decide the option labels. This is Filament's
`pluck()`-versus-`mapWithKeys()` branch, condition for condition. What is *not*
condition for condition is where the ordering sits —
[a divergence](#the-title-ordering-applies-to-both-arms-not-only-the-projected-one).

Each of the three conditions has a witness, and the third one's is not an
assertion on the labels: when the action sets its own title attribute, both arms
answer the *same* strings — the hydrating arm's `getRecordTitle()` falls through
the action's own attribute exactly as the projection reads it, so the table's
closure is never consulted either way. What the two arms do not share is the
hydration, so that case clears the identity map and asserts the candidate was
never loaded into it.

Each row is keyed by `KeySelection::keyOf($row)`, never by reading a projected
column back, and that closed a defect the single-identifier code carried: the
array hydrator converts a **field path**, so a target whose identifier is a value
object hydrated `record.<id> AS sy_key` into an *object* and
`$options[$row['sy_key']]` was a `TypeError` in the attach modal. `keyOf()` runs
every value the hydrator did not already build into an object through that
field's own DBAL type before printing it, so the projected key is the key the
record itself mints. The result alias moved from `sy_key` to `sy_key_0` with it
— one query-cache miss, no behaviour
([record-key.md](../schemas/record-key.md#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may)).

### `recordSelectOptionsQuery()` is evaluated with `query` and `search`

The closure receives the candidate `QueryBuilder` as `$query` — by name and by
type — and the current search term as `$search`, which is `null` everywhere the
query is built for something other than a search (the preload, the option-label
lookups, the submit's re-resolution). Returning null keeps the builder the action
made; a builder returned replaces it.

Note the trap this shares with forms' `Select`: in Filament the **search string**
also arrives as `$query` on `getSearchResultsUsing()`, so the two names mean
opposite things one method apart. The port's search closures take `$search`
throughout.

### The search columns are validated against the mapping

`recordSelectSearchColumns()` names fields on the *target* entity, and each is
checked against `ClassMetadata::hasField()` before it reaches the DQL — an
unmapped name is a `LogicException` naming the action, the column and the class,
not a Doctrine parse error further down. With no columns declared, the title
attribute is the one searched column; with neither, a search matches everything
the candidate query already admits.

The pair keeps Filament's `array<string>`, and the one line of `getRecordSelect()`
that is not the vendor's byte for byte is what pays for it:
[`Select::searchable()`](../forms/select.md) takes `bool|Closure|list<string>|null`
— a list, because the searchable columns are iterated positionally — so handing
the getter through as Filament does fails level max with *"Type #3 from the union:
`array<int|string, string>` might not be a list"*. The port reads the getter once
and passes `$searchColumns === null ? true : array_values($searchColumns)`, which
is the same value for every consumer who writes a list literal, and re-keys rather
than rejects a string-keyed array. `AssociateAction` pays it identically.

### The "another" arm refills the schema and halts

`attachAnother` is a second submit action in the modal footer carrying
`['another' => true]`. The arm is `CreateAction`'s five statements —
`callAfter()`, `sendSuccessNotification()`, `record(null)`, `$schema->fill()`,
`halt()` — and deliberately *not* its full create-another block: there is no
`preserveFormDataWhenCreatingAnother()` counterpart to honour and no client-side
state reset to dispatch, because the modal holds exactly one field and refilling
it is the whole reset.

## Divergences

Where the port deliberately differs from Filament.

### The candidate query needs no inverse relationship

Filament excludes the already-related records with
`whereDoesntHave($table->getInverseRelationship(), …)`, which needs the *name* of
the inverse relation and, when parent and related sit on the same table, the
`getRelationCountHash()` alias to disambiguate the self-join. Doctrine needs
neither: [`AssociationReader::createRelationshipCandidateQuery()`](../schemas/association-reader.md)
roots the query at the target entity and negates a subselect built from the
**owner's own** association —
`NOT EXISTS (SELECT 1 FROM <Owner> sy_owner JOIN sy_owner.<relation> sy_related WHERE sy_owner.<id> = :sylament_relationship_owner AND <the correlation>)`,
where the correlation compares `sy_related` to the outer alias field by field —
so nothing is joined into the outer query and no inverse mapping has to exist.
`inverseRelationship()` / `getInverseRelationship()` are
[not ported](../renames.md#renames) for the same reason.

### Pivot data, duplicates, morphs and `RelationshipJoiner` are not ported

Four Eloquent mechanics have no Doctrine referent and are skipped whole:

- **Pivot columns and `allowDuplicates()`.** Doctrine has no pivot model; a join
  table carrying attributes is an entity of its own, and its primary key is the
  pair, which is exactly what makes a duplicate row unrepresentable. Dropping
  duplicates makes the candidate exclusion unconditional (Filament's
  `->when(! $table->allowsDuplicates(), …)` loses its condition) and collapses
  the detach to one branch.
- **`RelationshipJoiner` / `Relation::noConstraints()`.** Filament has to strip
  the parent constraint off a relation object and re-add the join by hand; the
  port never had one — the reader builds a fresh query from the mapping.
- **`getRelationCountHash()`.** A self-join alias generator for the
  `whereDoesntHave` subquery the port does not emit.
- **`whereNotMorphedTo()` / `HasOneOrManyThrough`.** Doctrine maps no polymorphic
  association and no through-relation, so there is no shape to branch on.

### The title ordering applies to both arms, not only the projected one

Filament's `if (empty($relationshipQuery->getQuery()->orders)) { $relationshipQuery->orderBy($titleAttribute); }`
sits **inside** the pluck branch
([`AttachAction.php:289-300`](../../../vendor/filament/actions/src/AttachAction.php)),
so the hydrate-and-title arm below it emits no `ORDER BY` at all and lists in
whatever order the driver returns. The port applies the order **before** the
branch: both arms order by the title attribute when the query carries no order of
its own, so the option list reads the same whichever arm built it and a
`recordTitle()` closure does not silently cost the sort. A caller wanting the
unordered arm back orders the builder itself in `recordSelectOptionsQuery()` —
any order of its own suppresses this one.

### The record select is a forms `Select`, which is why actions requires forms

Filament's `getRecordSelect()` returns a `Field`, either a `Select` or a
`TableSelect`. The port has no `TableSelect`, so the return type is the concrete
`Sylament\Forms\Components\Select`. That import is the reason `sylament/actions`
**requires** `sylament/forms` rather than suggesting it: the class is
unconditionally constructed on the modal's schema path, so no guard could make it
optional. Filament's own manifest carries the same edge.

### `options()` is installed only on the preloaded arm

Filament installs `options()` unconditionally, its closure answering `null` when
the select is not preloaded — which in Laravel leaves the field with no option
map. The port installs it only for the preloaded arm, because the port's
[`Select::hasFullOptionMap()`](../forms/select.md#the-choice-constraint-is-the-fields-only-server-side-guard) treats *any* `options()`
configuration as the whole truth about which values are legal, and would then
validate a searched pick against an empty map and reject it. The observable
behaviour is Filament's on both arms: preloaded, the map is the candidate list
and it is also what validates a pick; searchable, the label probe through
`getOptionLabelUsing()` is. `dynamicOptions()` is installed either way, exactly
as Filament sets it.

### The strings are literals, not translation keys

Filament reads every string through `__('filament-actions::attach…')`. i18n is
deferred for the whole toolkit, so the port hardcodes Filament's `en` values —
`Attach`, `Attach {label}`, `Record`, `Attach & attach another`, `Attached`. The
same call every other prebuilt action's strings got.
