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
hook seeing an unsaved replica — and, since M7 · 6, the key a replica is asked
for: a parent kept, an incomplete key refused by name at each of its two points
— a single derived identity among them, where Doctrine's `EntityMissingAssignedId`
spoke before — a blank-default single key refused on the first replicate, a key
a `prePersist` callback or an event-manager listener fills saved, a single
assigned `int` nothing sets left to Doctrine as before, and the one shape
recorded as Doctrine's), the Integration
[`ReplicateActionTakenKeyTest`](../../../packages/actions/tests/Integration/ReplicateActionTakenKeyTest.php)
(a modal left on its original's key, a changed one, and a hook that derives the
key) and
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
| [The `action()` body — copy, map, hook, flush, `success()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#doctrine-has-no-replicate-so-the-copy-is-read-off-classmetadata) · [diverges](#a-replica-keeps-its-parent-and-one-whose-key-is-still-incomplete-is-refused-by-name) · [diverges](#a-taken-key-answers-in-the-modal-and-any-other-unique-column-is-still-the-databases) · [semantics](#the-write-body-reads-the-mounted-schema-from-the-host-not-from-a-parameter) |
| [`getSchema()` re-pointing the container at the record class](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [semantics](#the-modal-container-is-re-pointed-twice-at-the-class-then-at-the-replica) |
| [`excludeAttributes()` / `getExcludedAttributes()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#attributes-are-mapped-field-and-association-names) |
| [`beforeReplicaSaved()` / `callBeforeReplicaSaved()`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | — |
| [`getReplica()` + the `replica` closure injection](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) `resolveDefaultClosureDependencyForEvaluationByName()` | done | — | — |
| [`mutateRecordDataUsing()`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | — | [diverges](#mutaterecorddatausing-is-one-shared-trait-not-three-copies) · [semantics](#the-no-schema-arm-is-the-only-place-mutaterecorddatausing-writes-the-record-itself) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| `afterReplicaSaved()` | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | skipped | — | [diverges](#afterreplicasaved-is-not-ported) |
| [`RecordReplicator::replicate()`](../../../packages/actions/src/Replication/RecordReplicator.php) | [`Model::replicate()`](../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php) | done | — | [diverges](#doctrine-has-no-replicate-so-the-copy-is-read-off-classmetadata) · [diverges](#a-replica-keeps-its-parent-and-one-whose-key-is-still-incomplete-is-refused-by-name) |
| [`RecordReplicator::getFieldValues()` / `setFieldValues()`](../../../packages/actions/src/Replication/RecordReplicator.php) | [`Model::attributesToArray()`](../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php) + `fill()` | done | — | [diverges](#doctrine-has-no-replicate-so-the-copy-is-read-off-classmetadata) |

## Semantics

Faithful ports whose behaviour is easy to misread. Permanent.

### The write body reads the mounted schema from the host, not from a parameter

Filament's process closure takes `?Schema $schema`, but nothing hands it the
mounted container: `CanCustomizeProcess::process()` passes no injections at all,
and vendor `Action.php:575` resolves `schema` to `[$this->getSchemaContainer()]`
— the schema the *trigger* lives in, which is null for a table row action. So
the explicit map below is a repair rather than a translation. Here
[`Invoker::call()`](../../../packages/support/src/Evaluation/Invoker.php)
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

- an identifier **field** is not copied — a generated one is the database's to
  fill, and an assigned one is [asked for](#a-replica-keeps-its-parent-and-one-whose-key-is-still-incomplete-is-refused-by-name);
- the **version** field is not copied — optimistic locking counts this row's
  updates, not the original's;
- a field whose `generated` is not `GENERATED_NEVER` is not copied — the database
  produces it;
- an **embeddable leaf** (`declaredField !== null`) is not copied as a field; the
  owning embeddable is cloned instead, so a value object is never shared;
- an embeddable **nested inside another** embeddable is cloned too, one level at
  a time. Its leaves are declined as fields the same way, but `clone` is shallow,
  so a bare `clone` of the owner would hand the replica the original's nested
  instance — and a replicate form editing `address.geo.city` would then rewrite
  the **original's** row at flush, because the still-managed original's changeset
  reads the same object. Doctrine files a nested entry under a dotted key
  (`address.geo`) whose accessor `wakeupReflection()` keeps in a local
  `$parentAccessors` map rather than in the metadata's own, so
  `getPropertyAccessor('address.geo')` answers null and the root loop cannot see
  it; `cloneEmbeddable()` recurses through the *embeddable's* metadata instead,
  where its children are undotted and do have accessors. The same null answer is
  what skips the dotted keys at each level, so the recursion needs no name test
  of its own;
- a `notInsertable` field is not copied — the insert would not write it;
- an excluded name is not copied, field or association alike;
- a **to-one owning-side** association is copied as the same reference, so a
  proxy stays a proxy and stays uninitialised; an inverse side is left alone,
  since the owning row is what carries the foreign key;
- an association that is **part of the identifier** (`#[Id] #[ManyToOne]`, or a
  shared-primary-key `#[Id] #[OneToOne]`) **is** copied, as the same reference,
  so a replica keeps its parent — unless that one association is the whole
  identifier, where copying it would hand the replica its original's key
  ([why](#a-replica-keeps-its-parent-and-one-whose-key-is-still-incomplete-is-refused-by-name));
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
leaves it off the copy and keeps it out of the modal — but only at the top
level: the copy matches **mapped names**, while the fill drops **top-level keys**
of the form's nested state array. So `excludeAttributes(['address.street'])`
leaves the column off the copy and still shows the original's street in the
modal, which `Schema::getData()` then writes onto the replica. Filament never
meets this because `attributesToArray()` is flat; here, name an embeddable's
owner (`address`) when the intent is to exclude what it holds.

### A replica keeps its parent, and one whose key is still incomplete is refused by name

Eloquent's `replicate()` drops the primary key, and a Filament replica is a new
auto-incremented row. A Doctrine key can be assigned, and it can
be an association, so what the copy does with each half is a decision here —
taken with [M7 · 6](../roadmap.md#m7--composite-identifiers):

- a **field** half is never copied, and the modal asks for it. The fill
  pre-fills it from the original — the modal is a create over a copy — so a key
  left unchanged is a taken one, [answered in the modal](#a-taken-key-answers-in-the-modal-and-any-other-unique-column-is-still-the-databases);
- an **association** half is copied, so a translation replicated from its
  article's relation manager stays that article's and a `Membership` stays its
  group's. The rule is
  `count($metadata->identifier) > 1 || ! $metadata->hasAssociation($metadata->identifier[0])`:
  every identifier association is copied **unless that one association is the
  whole identifier** — a single derived identity — because there the copy would
  carry its original's key with no field left to change it. An identifier made
  of two associations keeps both — so its bare replicate carries its original's
  key, which the arm refuses by name before `persist()` (below), as it does a
  scalar half left on a declared default its original also holds.

A replica whose key is still incomplete right before it is written is refused by
name, at two points and with one message:

> `The [%s] action built a replica of [%s] whose identifier is incomplete, so it cannot be saved as a new record. Put every identifier field the copy leaves unset in its schema() — enabled and dehydrated — or set it in beforeReplicaSaved().`

The first point is before `persist()`, and it asks two questions. The first is
epic 4's
[predicate](../schemas/association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask):
is a component the application must supply still missing — asked of every
identifier but a plain single scalar, and of no class a `prePersist` callback or
mapped entity listener exempts — so Doctrine's own `EntityMissingAssignedId`
never speaks first. A **single derived identity**, whose one association the
copy leaves off, is the case that changed: its bare replicate was that Doctrine
exception and is now the named refusal, a 500 with nothing written either way.
The second question makes no exception for a plain single scalar, and it is
asked only of a class no `prePersist` hook could key — no lifecycle callback, no
mapped entity listener, no listener on the event manager: does a key whose
values are all set still mint none, because a component is blank? The reason is
Doctrine's. A persisted entity whose identity hash is `''` — a single string key
left blank — can be neither detached nor removed, so the second point could not
take it back; asked here, a single string key whose default is `''`, left blank
by every filler, is refused on the **first** replicate, where it wrote a row no
key names and left the listing failing on every render. A class such a hook
could key — and every class, once a `prePersist` listener sits on its manager's
event manager — is left to the second point, so a blank key that listener fills
still saves. A single assigned `int` nothing set holds no value at all, so it
still reaches Doctrine's `EntityMissingAssignedId`, as before.

The second point is after `persist()`, once every `prePersist` callback and
listener has run, and it exempts nothing: a replica `RecordKey::find()` still
cannot key is taken back out of the unit of work where Doctrine can take it
back — the one case it cannot is below — then refused. It is what closes a
class whose own `#[PrePersist]` exempted it from the first point and then left
a half blank — that replica inserted `('', '')` — while a key a callback does
fill from blank saves, because the second point reads it after the callback.
Both points run inside the action's transaction, so nothing is written, and
both read through the record's **own** manager — the first as
`new AssociationReader($manager)` rather than the container's reader, which
breaks for a class a non-default manager maps.

One shape Doctrine cannot take back. A class that carries a `prePersist` hook —
or any class, once a `prePersist` listener sits on its manager's event manager —
passes the first point, and a **single** string key that every hook still leaves
blank reaches the second point with an identity hash of `''`, which Doctrine can
neither detach nor remove. Since [M7 · 9](../roadmap.md#m7--composite-identifiers)
the arm catches that refusal and throws the named one all the same: nothing is
written, and the replica stays scheduled for the rest of the request, since
nothing flushes after the refusal. A composite blank key detaches, its identity
hash being `' '` rather than `''`. The Integration
[`ReplicateActionTest`](../../../packages/actions/tests/Integration/ReplicateActionTest.php)
pins both faces — the class callback and a no-op listener on the event manager.

There is no default replicate schema: no panels host answers `ReplicateAction`
with the resource form, and neither do Filament's pages. A modal's operation is
the action's **name**, so a key field declared `disabledOn('edit')` is enabled
under `replicate`, and reusing the resource form is
`->schema(fn (Schema $schema): Schema => $this->form($schema))`.

### A taken key answers in the modal, and any other unique column is still the database's

A replicate modal left on its original's key is a create on a taken key, and the
[record check](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)
answers it: *"This value is already used."* under the first identifier field the
modal lets the user type, the first that is no association — nothing written.
Before [M7 · 6](../roadmap.md#m7--composite-identifiers) it was Doctrine's
`EntityIdentityCollisionException`, the original being managed in the same
request. A changed key saves the replica.

The check reads the key the modal **mapped**, and `beforeReplicaSaved()` runs
after it. So a keyed modal pre-filled with the original's key beside a hook that
derives the copy's key is refused as unchanged where it saved before. The remedy
is Filament's own: derive the key in `fillForm()` or `mutateRecordDataUsing()`,
the way the demo's `ArticleResource` pre-fills its slug with `-copy`, or leave the
key out of the modal and let the hook set it. A taken key such a hook sets is
looked up since [M7 · 9](../roadmap.md#m7--composite-identifiers). With a
modal, the record check runs a second time after the hook, so the modal answers
it under the key field, or as a record error naming no field when the modal maps
none. Without one, `findTakenIdentifiers()` runs before `persist()` and a taken
key is refused by name — *"… whose key another record already holds …"* —
which also closes the two shapes whose copy is keyed before any hook runs: an
identifier made only of associations, and a scalar half left on a default its
original holds. The lookup costs one `find()` when the key is complete: nothing
when the identity map holds the record, one primary-key `SELECT` otherwise; with
a modal and a hook, the record's own constraints run twice, and so may the key
lookup. What stays Doctrine's is a key a `prePersist` hook or listener sets onto
a taken one: `persist()` itself raises the collision, after the listener and
before any seam here. The lookup reads the key before any `prePersist` hook
runs, so a copy such a hook would move **off** a taken key is refused by name,
as the modal's record check refuses it — derive that key in
`beforeReplicaSaved()` instead.

Eloquent's `replicate()` also drops `uniqueIds()`; the port drops nothing on
uniqueness grounds for a column that is no key, exactly as Filament's action does
not. A duplicate violating such an index still reaches the database and comes
back as an unhandled `UniqueConstraintViolationException`. That is deliberate:
the tool for it is the form-level
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
