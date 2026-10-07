---
description: The prebuilt per-record edit action, a link to an edit page or a modal that maps a form back onto the bound record and flushes it.
ports:
  - vendor/filament/actions/src/EditAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
  - vendor/filament/actions/resources/lang/en/edit.php
owns:
  - packages/actions/src/EditAction.php
---
# EditAction (actions)

[`Sylament\Actions\EditAction`](../../../packages/actions/src/EditAction.php) is the prebuilt per-record edit action a table row or a record page carries.
Like [`CreateAction`](create-action.md) it works both of Filament's ways off one class: with an edit page to link to it renders an anchor, and without one it opens a modal, fills a form from the bound record, maps the validated state back onto that record and flushes it.
[A page beats a modal](create-action.md#a-page-beats-a-modal-for-every-record-action) here as on every record action.

The unit [`EditActionTest`](../../../packages/actions/tests/Unit/EditActionTest.php) covers the chrome and the URL fallthrough, the integration [`EditActionTest`](../../../packages/actions/tests/Integration/EditActionTest.php) the modal arm end to end, and the integration [`CanOpenUrlTest`](../../../packages/actions/tests/Integration/Concerns/CanOpenUrlTest.php) the rendered anchor; the tests that pin each rule are named in its entry below.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/EditAction.php) (`'edit'`) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | — |
| [`setUp()` label, success-notification title, `defaultColor('primary')`](../../../packages/actions/src/EditAction.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:pencil-square-20-solid')`](../../../packages/actions/src/EditAction.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#one-plain-icon-replaces-filaments-table-and-grouped-pair) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [URL mode](../../../packages/actions/src/Concerns/CanOpenUrl.php) (the host-hook fallthrough) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) `getUrl()` | done | — | [can-open-url.md](can-open-url.md#geturl-falls-back-to-the-hosts-default-url-through-the-nullable-accessor) |
| [Modal mode](../../../packages/actions/src/EditAction.php) (`fillForm()` from the record and the `action()` body that updates it) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | [diverges](#the-modal-arm-maps-the-validated-state-onto-the-bound-record) |
| [`modalHeading()` / `modalSubmitActionLabel()`](../../../packages/actions/src/EditAction.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`mutateRecordDataUsing()`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | — | [diverges](#mutaterecorddatausing-is-one-shared-trait-not-three-copies) |
| The `BelongsToMany` pivot-column split in the fill and the write | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | skipped | — | [diverges](#the-pivot-column-split-has-no-doctrine-counterpart) |

## Divergences

### The modal arm maps the validated state onto the bound record

Filament's body is `$record->update($data)`; Doctrine has no `update()`, so the port maps through the schema instead.
The fill and the write are a pair:

- `fillForm(fn (Schema $schema): array => $this->getRecordDataForFill($schema, $this->getRecordForFill()))` runs at mount, and the `Schema` argument arrives because [`CanBeMounted::fillForm()`](../../../packages/actions/src/Concerns/CanBeMounted.php) forwards the mounted schema into the closure it wraps;
- the `action()` body re-points that same container at the record and calls [`Schema::getData()`](../schemas/belongs-to-record.md) with the validated (and, where `mutateDataUsing()` asked, mutated) array, then persists and flushes through [`getEntityManagerForRecord()`](can-use-database-transactions.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade).

**The record is bound before the fill runs, and by the host.**
`fillForm()` fires inside `mountAction()`, so the record has to be on the action by then, which is why a root frame's `recordKey` is resolved at **resolve** time rather than at call time ([`HasActions`](has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation)).
Mounting `edit` with `['recordKey' => '7']` on a host that registered a `resolveRecordUsing()` callback is the whole of what an edit modal needs; a table row and a record page bind it their own ways.

**The two record guards are deliberately different widths.**
`getRecordForFill()` accepts an object **or an array** record, because filling a form from a `records()`-lane row is ordinary, while `getRecordOrFail()` accepts an object only, because the default persistence body needs an entity.
An array record therefore fills the modal and is refused at the write, and `using()` is the escape for a caller whose record is something else.
The throw on the write side is load-bearing: the URL hook turns a record-less `EditAction` into a modal, and a modal that mapped onto a fresh instance would create instead of update.

**The schema comes off the host, never off a parameter.**
The body reads [`getMountedSchema()`](has-schema.md#the-host-resolver-answers-only-when-the-action-declares-nothing) and throws when nothing resolved.
A `Schema $schema` parameter on the `process()` closure would look equivalent and is not: the [`Invoker`](../support/component.md) resolves `schema` by **name** through `Action::resolveDefaultClosureDependencyForEvaluationByName()`, which answers the *containing* schema, the one the action sits inside, not the one the modal mounted.
The named injections `process()` does pass (`schema`, `form`, `record`) are what a `using()` override reads instead.

**`saveRelationships()` is not ported, and nothing replaces it.**
Filament's `$record->update($data)` reaches it through its own `getState()`; here `getData()` writes the associations as part of the mapping and Doctrine orders the writes itself at flush, so there is nothing left to do after `persist()`.
`persist()` on an already-managed entity is a no-op that still cascade-persists.
Filament's own page has no counterpart to compare against, because its `EditRecord::handleRecordUpdate()` calls `$record->update($data)`; the persist-and-flush pair is the port's, and [`Panels\Resources\Pages\EditRecord::handleRecordUpdate()`](../panels/edit-record.md) is where it also lives page-side.

**A changed key is refused, not written.**
`getData()` ends in the record check, whose first pass puts back every identifier field the write moved and answers *"This value cannot be changed."* ([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)).
A modal that declares the key field shows it under that field; one whose `before()` hook moved a key half the modal does not declare gets it on the mounted schema's own path, which the [modal's record-error list](can-open-modal.md#the-action-modal-renders-the-record-errors-that-name-no-field) renders above the form.
Either way the key is put back and nothing is written.
The UI half is `disabledOn('edit')`, and it is bound to the operation's **name** (a modal's operation is the action's name), so an `EditAction::make('editRenamed')` leaves such a field enabled and the refusal is what holds there.
Pinned by [`EditActionChangedKeyTest`](../../../packages/actions/tests/Integration/EditActionChangedKeyTest.php).

**The record check reads the schema's own reader, rebound.**
The default write binds the mounted schema's reader to the manager the flush writes through with [`withEntityManager()`](../schemas/association-reader.md), a clone, rather than building a `new AssociationReader($manager)`, for the reason [create-action.md](create-action.md#the-record-check-reads-the-containers-reader-rebound-the-relationship-arm-keeps-the-actions-own) records.
So a reader service an application subclassed or decorated is the one the changed-key, blank-key and taken-key passes ask, and the table query's manager is the one they read.

**A key `mutateDataUsing()` adds that no component maps is refused by name**, not dropped in silence.
The first statement of the default write is [`CreateAction`'s guard](create-action.md#a-key-mutatedatausing-adds-that-no-component-maps-is-refused-by-name), which throws a `LogicException` naming the action, the hook and each added path before the record is touched; an overwritten key, a key a client typed into the data bag, a key a hidden component maps and a `using()` override all pass.

**The default persistence step is a typed private method, not an inline closure**, for the reason [`CreateAction`](create-action.md#the-modal-arm-persists-through-the-schemas-record-class) records: PHPStan reads no `@param` docblock on a closure, so a closure's `array $data` stays `array<mixed, mixed>` and cannot be handed to `getData(array<string, mixed> $state)` at level max.
`private updateRecord(array $data, Schema $schema, object $record): object` carries the types on a real signature, and the `Invoker` resolves a first-class callable's parameters exactly as it resolves a closure's, so `using()`'s injection contract is unchanged.

### `mutateRecordDataUsing()` is one shared trait, not three copies

Filament declares the property and the setter three times over, once each on `EditAction`, `ViewAction` and `ReplicateAction`, and inlines the evaluation into each class's fill closure.
The port composes one [`CanMutateRecordData`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) onto the same three classes, because the port's fill side has more to share: Filament reads the record with `$record->attributesToArray()`, while the port reads it through [`Schema::getStateFromRecord()`](../schemas/belongs-to-record.md), which needs the mounted schema.
So `getRecordDataForFill(Schema $schema, object|array $record, array $except = [])` carries the read, the `$except` drop (the excluded attributes `ReplicateAction` drops) and the mutator in one place, beside the two record guards above.
`mutateRecordData()` re-checks the closure's return through the same [`HasData::assertFieldKeyedData()`](../../../packages/actions/src/Concerns/HasData.php) that `data()` runs `mutateDataUsing()`'s through, throwing a `TypeError` that names the setter.
`getRecordForFill()` answers an array record through `array_filter(..., ARRAY_FILTER_USE_KEY)` on string keys, the normalisation [`HasActions`](has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation) applies to the same value: `getRecord()` is `mixed`, so an `is_array()` narrowing alone cannot prove the `array<string, mixed>` the seam declares.
No member changed name, so it needs no `renames.md` row.
Covered by [`CanMutateRecordDataTest`](../../../packages/actions/tests/Unit/Concerns/CanMutateRecordDataTest.php).

### One plain icon replaces Filament's table-and-grouped pair

Filament sets `tableIcon()` and `groupedIcon()`, never a plain `icon()`, so an edit action shows a pencil in a table row and in a dropdown, but not as a page header button.
The port has no `HasTableIcon`, so it sets `icon()` instead, which the grouped trigger picks up as its fallback (`component.getIcon(component.getGroupedIcon)`) and every other trigger renders directly.
The visible delta is a pencil on a header-mounted edit action where Filament shows none, accepted because the port's `Action` has no table/non-table rendering split to key a table icon off; porting `HasTableIcon` with that split reverses it.
[`ViewAction`](view-action.md) carries the same compromise with the same visible delta.
The name is the literal `heroicons:pencil-square-20-solid` rather than Filament's `ActionsIconAlias::EDIT_ACTION` indirection, for the reason [create-action.md](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) records.

### The pivot-column split has no Doctrine counterpart

Filament's fill merges the pivot's attributes into the form data and its write splits them back out onto the pivot, when the table's relationship is a `BelongsToMany`.
A Doctrine association table carrying payload is an entity of its own, so a relation-scoped table's row has no pivot attributes to fill from or write back ([table.md](../tables/table.md#surface)), the same call [`CreateAction`'s relationship arm](create-action.md#filaments-three-way-relationship-branch-collapses-to-two) got.

### The strings are literals, not translation keys

`label('Edit')`, the `Edit :label` modal heading, `modalSubmitActionLabel('Save changes')` and `successNotificationTitle('Saved')` are the English literals read from [`resources/lang/en/edit.php`](../../../vendor/filament/actions/resources/lang/en/edit.php), where Filament reads `__('filament-actions::edit.single…')`, the same call [create-action.md](create-action.md#the-strings-are-literals-not-translation-keys) records for the create side.
The success title is `Saved`, not `Edited`: that is Filament's own wording, kept.

The heading's `:label` interpolation becomes string interpolation over [`Action::getRecordTitle()`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host), which is why it is a closure rather than a literal: no title is known until the action is hosted and a record is bound.
It carries a fallback Filament does not have: when the whole title chain answers `null`, Filament would render a trailing-space `Edit `, so the port falls back to the bare verb `Edit`.
