# ReplicateAction (actions)

[`Sylament\Actions\ReplicateAction`](../../../packages/actions/src/ReplicateAction.php)
— the prebuilt per-record duplicate: a two-squares trigger that copies the bound
record, optionally opens a form on the copy, and saves it as a new row. Filament
duplicates through Eloquent's `Model::replicate()`; Doctrine ships no such
member, so the copy rules live in a class of this package's own,
[`Sylament\Actions\Replication\RecordReplicator`](../../../packages/actions/src/Replication/RecordReplicator.php),
which is audited [in the same surface table below](#surface) — it exists only to
serve this action, and the two are only readable together.

It is modal-only, and unlike [`CreateAction`](create-action.md) and
[`EditAction`](edit-action.md) it has no URL arm to grow: Filament routes no
page at a replication either.

The success toast is a title here, `successNotificationTitle('Replicated')`; a
caller wanting a whole notification object passes one to `successNotification()`,
which [`CanNotify`](action.md) supplies to every action alike.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php)
  — the class this ports, whole
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait it composes, ported whole (row in
  [README](README.md#features))
- [`framework/src/Illuminate/Database/Eloquent/Model.php`](../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php)
  `replicate()` — the copy the replicator stands in for
- [`actions/resources/lang/en/replicate.php`](../../../vendor/filament/actions/resources/lang/en/replicate.php)
  — the four strings it sets

Covered by
[`ReplicateActionTest`](../../../packages/actions/tests/Unit/ReplicateActionTest.php)
(the chrome, the heading interpolation, the evaluated exclusions, the class
re-point and the `replica` injection), the Integration
[`ReplicateActionTest`](../../../packages/actions/tests/Integration/ReplicateActionTest.php)
(both arms end to end against the fixture entity manager — the modal fill from
the copy, the second row, the untouched original, and the `beforeReplicaSaved()`
hook seeing an unsaved replica) and
[`RecordReplicatorTest`](../../../packages/actions/tests/Integration/Replication/RecordReplicatorTest.php)
(the copy rules against real Doctrine metadata).

## Surface

Every member Filament's class carries, plus the replicator behind it. _Blocked
by_ names what has to land first. _Detail_ carries **diverges** → the note in
[Divergences](#divergences), and/or **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/ReplicateAction.php) (`'replicate'`) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | — |
| [`setUp()` label, `modalSubmitActionLabel()`, `successNotificationTitle()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalHeading()`](../../../packages/actions/src/ReplicateAction.php) (record title interpolated) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:square-2-stack-20-solid')` + `groupedIcon(...)`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#the-icon-is-a-literal-name-and-a-plain-icon-stands-in-for-tableicon) |
| [`fillForm()` from the record, minus the excluded names](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#the-fill-reads-the-schemas-declared-paths-not-an-attribute-bag) |
| [The `action()` body — copy, map, hook, flush, `success()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#doctrine-has-no-replicate-so-the-copy-is-read-off-classmetadata) · [semantics](#the-write-body-reads-the-mounted-schema-from-the-host-not-from-a-parameter) |
| [`getSchema()` re-pointing the container at the record class](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [semantics](#the-modal-container-is-re-pointed-twice-at-the-class-then-at-the-replica) |
| [`excludeAttributes()` / `getExcludedAttributes()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#attributes-are-mapped-field-and-association-names) |
| [`beforeReplicaSaved()` / `callBeforeReplicaSaved()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | — |
| [`getReplica()` + the `replica` closure injection](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) `resolveDefaultClosureDependencyForEvaluationByName()` | done | — | — |
| [`mutateRecordDataUsing()`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#mutaterecorddatausing-is-one-shared-trait-not-three-copies) · [semantics](#the-no-schema-arm-is-the-only-place-mutaterecorddatausing-writes-the-record-itself) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| `afterReplicaSaved()` | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | skipped | — | [diverges](#afterreplicasaved-is-not-ported) |
| [`RecordReplicator::replicate()`](../../../packages/actions/src/Replication/RecordReplicator.php) | [`Model::replicate()`](../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php) | done | — | [diverges](#doctrine-has-no-replicate-so-the-copy-is-read-off-classmetadata) |
| [`RecordReplicator::getFieldValues()` / `setFieldValues()`](../../../packages/actions/src/Replication/RecordReplicator.php) | [`Model::attributesToArray()`](../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php) + `fill()` | done | — | [diverges](#doctrine-has-no-replicate-so-the-copy-is-read-off-classmetadata) |

## Semantics

Faithful ports whose behaviour is easy to misread. Permanent.

### The write body reads the mounted schema from the host, not from a parameter

Filament's process closure takes `?Schema $schema` and Livewire hands it the
mounted one. Here [`Invoker::call()`](../../../packages/support/src/Evaluation/Invoker.php)
resolves a `Schema $schema` parameter **by name** first, and
[`Action`](action.md)'s named resolver answers `schema` with
`getSchemaContainer()` — the schema the *trigger* lives in, which for a table
row action is the table's own container and not the modal's. So the body reads
the modal container once, up front, through
[`getMountedSchema()`](has-schema.md#the-host-resolver-answers-only-when-the-action-declares-nothing),
and passes it down as an explicit named injection. A `using()` closure therefore
still receives `$data`, `$record` and `$schema` exactly as Filament's does — and
`$schema` is `null` for a confirmation-shaped mount, which is what the second arm
below keys on.

### The modal container is re-pointed twice, at the class then at the replica

`getSchema()` overrides the resolved container's record with the record
**class** (Filament's comment, translated, sits on the method). That is what
makes the modal behave as a create rather than an edit: the container the host
seeded carries the *original*
([`HasActions`](has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation)),
and a component reading its container's record would otherwise lazy-load the
original's collections into a form that is about to write a different row.

The write body then re-points the **same** container a second time, at the
replica, before calling [`Schema::getData()`](../schemas/belongs-to-record.md) —
the class re-point only made the fill safe; mapping needs an instance. Both
re-points are on the container object the host owns, so nothing is copied and
nothing else has to be told.

### The no-schema arm is the only place `mutateRecordDataUsing()` writes the record itself

A bare `ReplicateAction::make()` still opens a modal — a confirmation-shaped one,
because `hasCustomModalHeading()` is true — and mounts with no schema, so the
validated state is `[]` and there is nothing to map. Filament keeps
`mutateRecordDataUsing()` honest there by mutating the raw attribute bag
instead; the port does the same over the replicator's field bag
(`getFieldValues()` → `mutateRecordData()` → `setFieldValues()`). The guard is
`$data === []` **and** a mutator being set, so an action that declares neither
does no extra work, and a modal-mode run never takes this path. `setFieldValues()`
throws for a key the copy rules decline, which is the only place a typo in a
mutator's return surfaces at all.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### Doctrine has no `replicate()`, so the copy is read off `ClassMetadata`

Eloquent's `Model::replicate()` copies the raw attribute bag minus the key, the
timestamps, the unique ids and `$except`, keeps already-loaded relations in
memory and returns a non-existing model. Doctrine has no counterpart, and PHP's
`clone` is not one: a shallow clone shares the original's `PersistentCollection`
**instances**, so adding to one adds to the other and the join rows rewrite
against whichever owner the unit of work sees; and `persist()` assumes a new
entity, so a stale identifier on an assigned-id class would be inserted verbatim.

[`RecordReplicator`](../../../packages/actions/src/Replication/RecordReplicator.php)
builds the copy from the mapping instead. It takes the record's own
`EntityManagerInterface` — resolved through
[`getEntityManagerForRecord()`](action.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade),
so a query-lane table's manager wins over the registry — and is constructed
inline by the action rather than wired as a service, because there is nothing to
configure and the manager is only known per record. `newInstance()` starts the
copy, which bypasses the constructor (a record class may require arguments), and
then, per mapping entry:

- an **identifier** is not copied — the replica is a new row, or fails loudly at
  Doctrine's own missing-identifier check when the id is assigned rather than
  generated;
- the **version** field is not copied — optimistic locking counts this row's
  updates, not the original's;
- a field whose `generated` is not `GENERATED_NEVER` is not copied — the database
  produces it;
- an **embeddable leaf** (`declaredField !== null`) is not copied as a field; the
  owning embeddable is `clone`d instead, so a value object is never shared;
- an embeddable **nested inside another** embeddable has no accessor of its own
  on the root metadata and is skipped — its leaves were already declined, and the
  owning embeddable's clone carries it;
- a `notInsertable` field is not copied — the insert would not write it;
- an excluded name is not copied, field or association alike;
- a **to-one owning-side** association is copied as the same reference, so a
  proxy stays a proxy and stays uninitialised; an inverse side is left alone,
  since the owning row is what carries the foreign key;
- every **collection-valued** association is set to a fresh empty
  `ArrayCollection` — the constructor that would have initialised it never ran,
  and sharing the original's collection is the failure mode above;
- an **uninitialised** typed property is skipped rather than read, which would
  throw.

The initialisation check goes through `ClassMetadata::getPropertyAccessor()` and
its `getUnderlyingReflector()`. The accessor **interface** is marked `@internal`
in ORM 3.6 while the metadata method handing one out is public; the documented
alternative, `getReflectionProperty()`, is deprecated, so the `@internal` type is
the supported route and this is where that trade is recorded. It is named
outright — Rector normalises the `null` check on the accessor into an
`instanceof`, so `Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessor` is a
`use` import in shipped source, and an ORM release that moves it is a compile
error here rather than a silent behaviour change. ORM's own accessors already
answer `null` for an uninitialised property, so the check itself is
belt-and-braces rather than load-bearing — if the interface ever moves, the guard
is what has to be re-derived, not the copy.

### "Attributes" are mapped field and association names

`excludeAttributes()` keeps Filament's name, but an "attribute" here is a mapped
**property** name — a field or an association — not an Eloquent column key. The
two coincide for a plain column and diverge for anything Doctrine names
differently from its column, so the rule is: pass what the entity calls it, not
what the table does. The same names filter the fill, so excluding `slug` both
leaves it off the copy and keeps it out of the modal.

### A unique column is not auto-excluded, and a violation is a 500

Eloquent's `replicate()` drops `uniqueIds()`; the port drops nothing on
uniqueness grounds, exactly as Filament's action does not. A duplicate that
violates a unique index therefore reaches the database and comes back as an
unhandled `UniqueConstraintViolationException`. That is deliberate: the tool for
it is the form-level
[`unique()`](../forms/can-be-validated.md#unique-and-exists-are-one-dql-probe-behind-a-container-service)
constraint on the field the index covers, which fails the modal with a message
instead. An action replicating a uniquely-indexed record without a form arm, and
without `excludeAttributes()`, is a caller error we let fail loudly.

### `mutateRecordDataUsing()` is one shared trait, not three copies

Filament declares the property and the setter on this class, on
[`EditAction`](edit-action.md#mutaterecorddatausing-is-one-shared-trait-not-three-copies)
and on [`ViewAction`](view-action.md); the port composes one
[`CanMutateRecordData`](../../../packages/actions/src/Concerns/CanMutateRecordData.php)
onto all three, and its row in [README](README.md#features) carries the
reasoning. No member changed name, so `renames.md` is untouched by it.

### `afterReplicaSaved()` is not ported

Filament marks it `@deprecated Use after() instead` and its whole body is
`$this->after($callback)`. Porting a deprecated alias would publish an API we
would then have to deprecate, so `after()` is the only hook — and it reads the
saved replica through the `replica` injection this class registers.

### The strings are literals, not translation keys

`label('Replicate')`, the `Replicate :label` modal heading, the submit label and
the `Replicated` toast title are the English literals read from
[`resources/lang/en/replicate.php`](../../../vendor/filament/actions/resources/lang/en/replicate.php),
where Filament reads `__('filament-actions::replicate.single…')` — the same call
[create-action.md](create-action.md#the-strings-are-literals-not-translation-keys)
records, with the `Translations` row in [README](README.md#supporting-surface)
tracking the catalogue. The `:label` interpolation becomes string interpolation
over
[`Action::getRecordTitle()`](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host),
so the heading is a closure with the bare-verb fallback its siblings carry:
`Replicate` where Filament would render a trailing-space `Replicate `.

### The icon is a literal name, and a plain `icon()` stands in for `tableIcon()`

Filament resolves both icons through
`FilamentIcon::resolve(ActionsIconAlias::REPLICATE_ACTION) ?? Heroicon::Square2Stack`;
the port sets the literal `heroicons:square-2-stack-20-solid` the alias falls
back to, for the reason
[create-action.md](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection)
records — including why the name spells the mini-solid set. `ActionsIconAlias`
(unported, tracked in [README](README.md#supporting-surface)) gains a third
action's worth of indirection with this class, and the placement diverges the way
[`EditAction`](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair)'s
does: no `HasTableIcon` here, so `icon()` carries what Filament puts on
`tableIcon()`.

### The fill reads the schema's declared paths, not an attribute bag

Filament fills from `$record->attributesToArray()` minus the excluded names — the
whole row, whether or not the form declares it. The port fills through
[`CanMutateRecordData::getRecordDataForFill()`](../../../packages/actions/src/Concerns/CanMutateRecordData.php),
which reads `Schema::getStateFromRecord()`: the container's **declared paths**,
minus the excluded names. It is the same closure
[`EditAction`](edit-action.md#the-modal-arm-maps-the-validated-state-onto-the-bound-record)
uses, with the exclusions threaded through, and the delta is invisible to a form
that declares every column and deliberate for one that does not — an undeclared
column is carried by the metadata copy rather than round-tripped through form
state.
