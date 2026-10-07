---
description: The trait that binds an action to a record and resolves its record, class, label and title chains, plus the record key it emits.
ports:
  - vendor/filament/actions/src/Concerns/InteractsWithRecord.php
owns:
  - packages/actions/src/Concerns/InteractsWithRecord.php
---
# InteractsWithRecord (actions)

`Action` overrides the record, record-class, label, plural-label, title and title-attribute getters with Filament's full chains, which [action.md](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host) documents, and `ActionGroup` overrides `getRecord()` and `getRecordClass()` ([action-group.md](action-group.md#actiongroupgetrecord-restates-the-traits-group-fallback-to-satisfy-phpstan)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()` / `hasRecord()` / `getRecord()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [semantics](#a-bound-record-stops-the-walk-even-when-the-record-class-guard-drops-it) · [action.md](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host) |
| [`recordClass()` / `getCustomRecordClass()` / `getRecordClass()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [diverges](#three-guards-read--null-where-filament-reads-filled) · [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| [`ensureCorrectRecordType()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [semantics](#a-bound-record-stops-the-walk-even-when-the-record-class-guard-drops-it) · [diverges](#three-guards-read--null-where-filament-reads-filled) |
| [`recordLabel()` / `getCustomRecordLabel()` / `getRecordLabel()` / `getTitleCaseRecordLabel()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| [`pluralRecordLabel()` / `getCustomPluralRecordLabel()` / `getPluralRecordLabel()` / `getTitleCasePluralRecordLabel()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [diverges](#the-plural-label-pluralises-english-unconditionally) · [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| [`recordTitle()` / `getCustomRecordTitle()` / `getRecordTitle()` / `hasCustomRecordTitle()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [diverges](#getcustomrecordtitle-returns-null-without-a-record) · [diverges](#three-guards-read--null-where-filament-reads-filled) |
| [`recordTitleAttribute()` / `getCustomRecordTitleAttribute()` / `getRecordTitleAttribute()` / `hasCustomRecordTitleAttribute()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | — |
| [`resolveRecordKey()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | done | — | [diverges](#resolverecordkey-reads-id-and-answers-null-for-a-record-with-no-usable-key) |
| `resolveRecordUsing()` and the record-key branch of `getRecord()` | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | skipped | — | [diverges](#record-key-resolution-is-hoisted-to-hasactions) |

## Semantics

### A bound record stops the walk, even when the record-class guard drops it

`getRecord()` returns its own record, run through `ensureCorrectRecordType()`, whenever one is bound, and walks on to the group only when none is.
So `->record($a)->recordClass(B::class)` inside a group holding a real `B` answers `null`, not the group's record: Filament's `if ($record) { return $this->ensureCorrectRecordType($record); }` returns before the group hop, because an explicit binding that does not fit is an error to surface, not a reason to inherit something else.
`ensureCorrectRecordType()` drops a record reached through any hop when the action declares a `recordClass()` the record is not an instance of, which lets `CreateAction::make()->recordClass(Other::class)` sit inside a table or a form bound to something else without inheriting the wrong record.
It passes a non-object through (an array record, a bare key), because a class guard has nothing to say about either.

The own-record gate is `is_object($record) || filled($record)`, because Filament's is bare truthiness, which is true for every object.
A plain `filled()` would count an empty `Countable` or a whitespace-only `Stringable` as blank, so an empty collection bound as a record would fall through to the group instead of stopping.
A filled scalar stops the walk and is returned as the record, where Filament treats it as a record key to resolve ([below](#record-key-resolution-is-hoisted-to-hasactions)).

## Divergences

### Three guards read `!== null` where Filament reads `filled()`

`getRecordClass()`'s custom-class check, `ensureCorrectRecordType()`'s and `hasCustomRecordTitle()` test `!== null` where Filament tests `filled()`; `hasCustomRecordTitleAttribute()` is `!== null` in both.
The difference is only ever an **empty string**: `->recordClass('')` is answered as the record class, so the guard drops every object record, and `->recordTitle('')` counts as a custom title here and does not upstream.
`Action::getRecordTitle()`'s host arm carries the same delta ([action.md](action.md#the-label-and-title-chains-differ-from-filaments-at-the-host-arm-and-for-a-passed-record)).

### `getCustomRecordTitle()` returns null without a record

Filament guards the title on a record in `getRecordTitle()` (`if ($record)`), and its `getCustomRecordTitle()` evaluates the `recordTitle()` closure with a `null` record when nothing is bound.
The port's [`getCustomRecordTitle()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) returns `null` before evaluating the closure or reading `recordTitleAttribute()` when there is no record object, because its typed injection keys the record by its own class, which needs an object.
The trait's `getRecordTitle()` is `getCustomRecordTitle() ?? getRecordLabel()`, so this is its record guard too.
Filament also keys the typed injection by `Model::class`, which has no Doctrine counterpart.
The attribute is read through schemas' [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), so a dotted path traverses, and Eloquent's `'->'` JSON-path rewrite is not ported.

### The plural label pluralises English unconditionally

Filament's `getPluralModelLabel()` pluralises the singular only when `locale_has_pluralization()` is true, and returns the singular otherwise.
The port's `getPluralRecordLabel()` always pluralises through [`Sylament\Support\plural()`](../support/helpers.md), because the port has no i18n layer and so no locale to ask ([translations.md](../translations.md#idiom)).
The guard lands with the i18n pass, which is when `locale_has_pluralization()` is ported.

### Record-key resolution is hoisted to HasActions

Filament's `getRecord()` detects a scalar "record key" (anything that is not a `Model` or an array) and resolves it through a `resolveRecordUsing` closure stored *on the trait itself*, memoizing the resolved record back onto `$this->record`.
The port drops that branch from `InteractsWithRecord`; a same-named `resolveRecordUsing()` setter and the key resolution live on [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) (the live-component host trait, Filament's `InteractsWithActions`, per the [Has*/InteractsWith* swap](../renames.md#interface-and-trait-names-are-swapped)), scoped to turning a mounted action's `context.recordKey` into a record before calling `$action->record(...)`.
The two `resolveRecordUsing` methods share a name but not a mechanism: Filament's is a per-action fallback inside `getRecord()`, and the port's is a per-host callback the live-component runtime uses once, at mount time.
Reversible if an action needs to accept a bare record key directly rather than through a live-component mount: `InteractsWithRecord::getRecord()` would then need its own key-detection branch.

### `resolveRecordKey()` reads `id` and answers null for a record with no usable key

The opposite direction, record to key, is not hoisted: [`resolveRecordKey()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) lives on the trait as Filament has it.
Its object arm has no `Model::getKey()` to call, so it reads `id` through schemas' [`PropertyAccessor::getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php), which is why `Action` and `ActionGroup` compose `CanAccessProperties`, and it treats an `AccessException` (no readable `id`) as no identity, returning null.
That makes it **lenient where the host's twin is strict**: the same unreadable record throws from [`HasTable::getTableRecordKey()`](../tables/has-table.md), because a table building its row index cannot proceed without a key, while an action emitting a mount context can omit one.
They are two implementations of one convention, so a change to one read changes the other.
The array arm keeps Filament's `LogicException` for a missing [`ArrayRecord`](../../../packages/support/src/ArrayRecord.php) key entry, because an array is unambiguously a record and the absent entry is a caller mistake; an entry that is present and blank, or not stringable, returns null where Filament's `strval()` emits `''` or fails.
[`getContext()`](action.md#getcontext-drops-the-record-model-gate) decides when this literal reading answers: a table-bound action over one of its table's records sends the table's key, and an object whose identity is not one scalar field sends none.
That gate sits in the caller rather than here because `ActionGroup` composes this trait without the manager registry the question needs.
