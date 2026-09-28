# AssociateAction (actions)

[`Sylament\Actions\AssociateAction`](../../../packages/actions/src/AssociateAction.php)
— the prebuilt header action of a **one-to-many** relation-scoped table: a modal
carrying one record select over every target record that is *not* this owner's,
which on submit points each picked record at the owner and flushes. It is the
one-to-many half of the pair Filament ships; `AttachAction` is the many-to-many
half, and the two are deliberately separate classes here as they are there.

The relationship it writes is the **table's** —
[`Table::relationship()`](../tables/table.md) is the only thing that supplies
one, and the action refuses with a `LogicException` naming that when a host
mounts it without one.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php)
  — the class this ports, whole
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait it composes, ported whole (row in [README](README.md#features))
- [`actions/resources/lang/en/associate.php`](../../../vendor/filament/actions/resources/lang/en/associate.php)
  — the six strings it sets

Covered by
[`AssociateActionTest`](../../../packages/actions/tests/Unit/AssociateActionTest.php)
for the chrome, the footer action, the select's shape, the evaluated
`multiple()` and the hostless refusal, and end to end by
[`AssociateDissociateActionsTest`](../../../packages/tables/tests/Integration/AssociateDissociateActionsTest.php)
in the **tables** suite — the candidate list, the association, the re-point of
another owner's record, the toast and unmount, the "associate another" arm, the
multiple arm, the preloaded options and both refusals of a smuggled key, and
beside those the select's own arms: the title ordering (the candidate list's
unowned song is titled so that title order and key order disagree, which is what
makes the assertion an assertion on the `ORDER BY`) on the projecting branch and,
through a table-level `recordTitle()` closure whose upper-cased answers no
projection could return, on the hydrating one, the action's own `recordTitle()`
closure that no table title can stand in for, the action's own title attribute
overriding a table that titles by closure,
`recordSelectOptionsQuery()` narrowing the candidates through each of its two
documented injections — the builder by parameter name with the search beside it,
and the builder by type hint — a third arm that orders the builder itself, whose
order survives, `recordSelectSearchColumns()` searching a mapped
**non-title** field with the title no longer matching, the unmapped-column
refusal, and the `LOWER()` that `forceSearchCaseInsensitive()` puts in the
emitted SQL and its absence without the flag. It sits
there for the reason
[`PrebuiltDeleteActionTest`](delete-action.md) does: only a table host supplies a
relationship, and the actions test kernel registers neither tables nor forms.

## Surface

Every member Filament's class carries. _Blocked by_ names what has to land
first. _Detail_ carries a **semantics** link to [Semantics](#semantics), a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/AssociateAction.php) (`'associate'`) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`setUp()` chrome — label, submit label, `modalWidth('lg')`, success title, `defaultColor('gray')`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalHeading()` — `"Associate {title-case record label}"`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`extraModalFooterActions()` — the `associateAnother` submit](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`schema()` — the record select alone](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [The `action()` body — resolve, bind, `process()` per record, the "another" arm](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [semantics](#the-submitted-key-is-re-resolved-through-the-candidate-query) · [diverges](#the-vendors-raw-ismultiple-read-is-not-copied) |
| [`recordSelect()` / `recordSelectOptionsQuery()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`associateAnother()` / `canAssociateAnother()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`preloadRecordSelect()` / `isRecordSelectPreloaded()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [semantics](#preloading-is-what-decides-whether-the-select-carries-a-membership-check) |
| [`multiple()` / `isMultiple()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [diverges](#the-vendors-raw-ismultiple-read-is-not-copied) |
| [`recordSelectSearchColumns()` / `getRecordSelectSearchColumns()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`getRecordSelect()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [semantics](#the-title-fast-path-projects-the-attribute) · [diverges](#searchable-takes-a-re-keyed-list-not-the-raw-column-array) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| `getRelationCountHash()` self-join, `whereDoesntHave()`, `whereNotMorphedTo()` | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | [diverges](#the-candidate-query-needs-no-inverse-relationship) |
| `getInverseRelationshipFor()` in the write | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | [diverges](#the-candidate-query-needs-no-inverse-relationship) |
| `disableAssociateAnother()` | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | a `@deprecated` alias — [renames.md](../renames.md) |

## Semantics

Faithful ports whose obvious reading is wrong.

### The submitted key is re-resolved through the candidate query

`findRecordsToAssociate()` never calls `find()`. It re-runs
`getRecordSelectOptionsQuery()` — the candidate query plus whatever
`recordSelectOptionsQuery()` narrowed it to — with
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
under the base `sylament_selected_record_keys`, so a key outside that scope — or
one the codec cannot decode, which that predicate drops rather than binds — is
simply absent from the result and the count mismatch throws. That is what closes the
hole a hand-posted `recordId` would otherwise open, and it is Filament's own
shape (`$relationshipQuery->find($data['recordId'])`, which answers null for a
row the relationship query excludes) with a refusal instead of a silent skip.

In practice the select's own membership check refuses first: an unlabelled
submitted value fails `Select`'s derived `Choice` constraint before the action
body runs. The re-resolution is the second line, and it is what still holds when
a consumer's `recordSelect()` reshapes the labelling — which is exactly how the
Integration case reaches it.

An **empty** submission is refused first, before the count check, because that
check cannot tell zero resolved records from zero submitted keys: `0 !== 0` is
false, the loop runs over nothing and the action falls through to its success
toast, reporting a write that never happened. The select is `required()`, so the
mounted path never reaches it — the guard exists for a `using()` or direct-call
consumer, and `AttachAction` carries the same one.

The count mismatch's message moved for **every** relation, a single-identifier
one included: `The action [%s] was asked to associate a record its select never
offered` is unchanged to the character, and what follows it is new — `— the key
is malformed, outside recordSelectOptionsQuery()'s scope, or already
associated.` Anything asserting the leading sentence still passes; anything
asserting the message whole does not.

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

When the table names a `recordTitleAttribute()`, the action has no custom title
closure, and either the action set the attribute itself or the table has no
custom title, the options are read as one projection —
[`IdentifierConstraint::select()`](../schemas/record-key.md#the-operations-and-their-dql)'s
expressions plus `record.<title> AS sy_title`, one aliased expression per
identifier field — rather than hydrated, each row keyed through
`KeySelection::keyOf($row)` rather than by reading a column back, which is what
makes a value-object identifier key the same string the record itself mints
([attach-action.md](attach-action.md#the-title-fast-path-projects-the-attribute)). Otherwise every candidate is hydrated and titled
through `getRecordTitle()`. The title ordering (`orderBy record.<title>` when the
builder carries no order of its own) sits **before** that branch, because
Filament orders both paths. A `recordSelectOptionsQuery()` that orders the
builder itself keeps its order — that is the documented escape hatch, and
`AttachAction` carries the same one.

Each of the three conditions has a witness, and the third one's is not an
assertion on the labels: when the action sets its own title attribute, both arms
answer the *same* strings — the hydrating arm's `getRecordTitle()` falls through
the action's own attribute exactly as the projection reads it, so the table's
closure is never consulted either way. What the two arms do not share is the
hydration, so that case clears the identity map and asserts the candidate was
never loaded into it.

### Preloading is what decides whether the select carries a membership check

`options()` is handed a closure only when `preloadRecordSelect()` is on, and
`null` otherwise. That is not cosmetic: `Select::hasFullOptionMap()` treats *any*
non-null `options` as the truth about which values are legal, so a closure that
answers `null` on the non-preloaded path would leave the select with an empty
option map and reject every submitted key. Filament can hand `options()` a
null-answering closure because its validation reaches for `getOptionLabelUsing`
instead; the port gates the setter itself.

## Divergences

### The vendor's raw `$isMultiple` read is not copied

Filament's `action()` body loops with `$this->isMultiple ? $record : [$record]` —
the **property**, not `isMultiple()`. A `multiple(fn (): bool => false)` therefore
reads as truthy there (a `Closure` is an object) while `getRecordSelect()`, one
method away, evaluates the same property properly, so the modal renders a single
select whose one key is then iterated as if it were a list. The port calls
`isMultiple()` in both places. It is a bug fix, not an API change: for the literal
`true`/`false` arguments every consumer actually writes, the two readings agree.

### The candidate query needs no inverse relationship

Filament excludes already-related records with
`whereDoesntHave($table->getInverseRelationship(), …)` — it must name the
target's inverse `BelongsTo` to do it, and it works around a self-referencing
relationship with `getRelationCountHash()`. The port asks
[`AssociationReader::createRelationshipCandidateQuery()`](../schemas/association-reader.md)
instead, which reads the mapping's `mappedBy` off Doctrine metadata and writes
`WHERE record.<mappedBy> IS NULL OR record.<mappedBy> <> :owner` — the `IS NULL`
arm matters, since SQL's `<>` alone drops the unowned rows. So the whole inverse
family (`inverseRelationship()`, `getInverseRelationship()`,
`getInverseRelationshipFor()`) has nothing to do here and is not ported, and the
write goes through the reader's `attach()` rather than through a `BelongsTo`
object.

Note the rule this inherits from Filament: a record that belongs to **another**
owner is a candidate and gets re-pointed. Associating is a move, not only an
adoption of orphans.

### Morphs, `HasOneOrManyThrough` and the pivot arm are not ported

`MorphMany`'s `whereNotMorphedTo()` branch, `HasOneOrManyThrough` and the
relation-count-hash self-join are Eloquent mechanics with no Doctrine referent: a
polymorphic association is not something Doctrine's ORM maps, and a "through"
relation is a join the caller writes. Doctrine has no pivot model either, so the
pivot columns and accessors Filament's attach family carries have no counterpart
— a join table with attributes is an entity of its own here.

### The record select is a forms `Select`, which is why actions needs forms

`getRecordSelect()` returns `Sylament\Forms\Components\Select`, so
`sylament/actions` declares `sylament/forms` — the second deliberate cycle in the
monorepo. Filament's `tableSelect()` / `getTableSelectConfiguration()` /
`getTableRecordSelect()` trio, which swaps the select for a whole table picker,
belongs to `AttachAction` alone (`AttachAction.php:36`, `:52`, `:57`, `:362`) —
`AssociateAction` has no such member, so there is nothing to port here.

### `searchable()` takes a re-keyed list, not the raw column array

`recordSelectSearchColumns()` accepts any `array<string>`, but
[`Select::searchable()`](../forms/select.md) takes `bool|Closure|list<string>|null`
— a list, because the searchable columns are iterated positionally. Handing the
raw array through as Filament does (`->searchable($this->getRecordSelectSearchColumns() ?? true)`)
fails level max with *"Type #3 from the union: `array<int|string, string>` might
not be a list"*, so the port reads the getter once and passes
`$searchColumns === null ? true : array_values($searchColumns)`. It is the same
value for every consumer who writes a list literal, which is all of them; a
string-keyed array is simply re-keyed rather than rejected. This is the one line
of `getRecordSelect()` that is not the vendor's byte for byte.

### The strings are literals, not translation keys

Filament resolves every label through `__('filament-actions::associate.…')`. i18n
is deferred toolkit-wide, so the six `en` strings are hardcoded — `Associate`,
`Associate :label`, `Record`, `Associate`, `Associate & associate another` and
`Associated`. When translation lands they become the same keys.
