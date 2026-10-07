---
description: The trait that binds an action to a record and resolves its record, class, label and title chains, plus the record key it emits.
ports:
  - vendor/filament/actions/src/Concerns/InteractsWithRecord.php
owns:
  - packages/actions/src/Concerns/InteractsWithRecord.php
---
# InteractsWithRecord (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`InteractsWithRecord`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) incl. `resolveRecordKey()` | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | in progress | `CanBeLabeledFrom`, the authorization arm | The record, record-class, record-label, plural-label and record-title chains all land — the plural trio (`pluralRecordLabel()` / `getCustomPluralRecordLabel()` / `getPluralRecordLabel()` / `getTitleCasePluralRecordLabel()`) with them, and `Action` hops each chain through its bound table — plus `ensureCorrectRecordType()` and Filament's `bool $withDefault` gate on `getRecord()`/`getRecordClass()` — [semantics](#the-record-chain-reaches-the-schema-the-component-and-the-host) · [semantics](#a-table-bound-action-titles-and-labels-through-its-table) · [rename](../renames.md#the-model-vocabulary-collapses-onto-record) · [diverges](#record-key-resolution-is-hoisted-to-hasactions) |

## Semantics

### The record chain reaches the schema, the component and the host

[`Action::getRecord(bool $withDefault = true)`](../../../packages/actions/src/Action.php) walks Filament's full chain: its **own** record (evaluated, then run through `ensureCorrectRecordType()`), its **group**, its [**schema container**](belongs-to-schema-component.md), its **schema component**, and finally the host's `getDefaultActionRecord()`.
The last hop is the only one `$withDefault: false` switches off, which is what makes it usable as a question about *bindings* rather than about what the action would resolve at run time.
`getRecordClass()` carries the same gate over its own chain — custom class → [`getTable()?->getRecordClass()`](../tables/has-query.md#the-record-class-is-read-off-the-base-querys-root-entity) → the resolved record's class → group → schema container → schema component → host `getDefaultActionRecordClass()` — so a create action inside a table resolves the class it will create into without a record existing yet.
The table hop sits **above** the record hop in Filament's order and stays there: a `records()`-lane table answers `null` (it has no root entity), so only the query lane contributes.

Three classes carry three different bodies, and that is Filament's shape rather than duplication.
Filament writes one trait method gated on `$this instanceof Action`, so a **group** never reaches past its own group, and neither does a class that composes the trait alone; here the trait keeps the group-only chain, [`ActionGroup`](action-group.md#getrecord-is-duplicated-onto-action-and-actiongroup-to-satisfy-phpstan) adds the group hop to `getRecordClass()`, and `Action` carries the whole walk.

**`ensureCorrectRecordType()` drops what does not match.**
A record reached through *any* hop is discarded when the action declares a `recordClass()` the record is not an instance of — Filament's guard, and what lets a `CreateAction::make()->recordClass(Other::class)` sit inside a table or a form bound to something else without silently inheriting the wrong record.
It is a pass-through for a non-object (an array record, a bare key), because a class guard has nothing to say about either.

A **set** record that fails the guard **stops the chain** rather than falling through it, which is Filament's shape and is easy to get backwards: its `if ($record) { return $this->ensureCorrectRecordType($record); }` returns `null` without ever reaching the group.
So `->record($a)->recordClass(B::class)` inside a group holding a real `B` answers `null`, not the group's record — an explicit binding that does not fit is an error to surface, not a reason to inherit something else.
Only an **absent** own record hands the walk on.
Pinned by `ActionTest`'s "stops at its own record when the record class does not match".

**The title is guarded on the record existing.**
`getCustomRecordTitle()` returns `null` the moment there is no record, *before* evaluating the `recordTitle()` closure or reading `recordTitleAttribute()` — Filament's `if ($record)` gate.
Without it a `fn (Article $record) => $record->title` heading would throw during [`shouldOpenModal()`](can-open-modal.md#the-action-modal-template-only-renders-part-of-the-full-action-modal), which runs while the trigger renders and long before a record is bound.
The closure receives the record both by name (`$record`) and by its own class as a type hint; the attribute is read through schemas' [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), so a dotted path traverses.
Eloquent's `'->'` JSON-path rewrite is not ported — the accessor already speaks dots.
`getRecordTitle()` then falls to the host's `getDefaultActionRecordTitle()` and finally to the record **label**, so a titleless action still renders a heading.

**The label chain adds one arm Filament does not have.**
Custom label → otherwise derive from the class through [`get_record_label()`](../support/helpers.md).
Where the action's class *is* the host's own `getDefaultActionRecordClass()`, the host's label wins — that is how a resource page names its records once instead of at every action.
Filament returns the host's answer flatly there, so a host that owns the class but answers no label yields `null`; ours falls through to the derived label instead.
A silent host is the common case (every host that has not overridden the hook), and `null` would leave a heading with nothing in it where the class name was right there.

**A host default never rides the wire.**
`getContext()` stamps `recordKey` from `getRecord(withDefault: false)`, so the key a trigger sends back names a record something actually *bound* — the host answers its own default again on the way back, and stamping it would only re-send the host its own state.
Nothing renders differently today (no shipped host answered the hook before this commit); the gate exists so that adding one does not start widening every trigger's context.

**`shouldClearRecordAfter()` / `clearRecordAfter()`** are the pair a create-shaped action flips: [`CreateAction::shouldClearRecordAfter()`](create-action.md) returns `true` and nothing else overrides either — the base answers `false` and `clearRecordAfter()` returns without touching anything, so an action that never opts in cannot lose the record it was mounted with.

**The table hops and the plural trio both landed**, once tables grew the [label and title surface](../tables/table.md) they call.
`getRecordLabel()`, `getPluralRecordLabel()`, `getRecordTitle()` and `getRecordTitleAttribute()` each hop through `getTable()?->…` before falling back, beside `getRecordClass()`'s older hop — Filament's chains verbatim, and what they mean for the host hook is its own [semantics note](#a-table-bound-action-titles-and-labels-through-its-table).
The **plural trio** — `pluralRecordLabel()`, `getCustomPluralRecordLabel()`, `getPluralRecordLabel()` and `getTitleCasePluralRecordLabel()` — is table-free and lives on the trait, pluralising through [`Sylament\Support\plural()`](../support/helpers.md); Filament's `getPluralModelLabel()` additionally branches on `locale_has_pluralization()`, which [i18n being parked](../translations.md#idiom) leaves nothing to ask, so that guard is the one piece not ported.
What still keeps the row **in progress** is the `CanBeLabeledFrom` deferral, not these.

**Three guards read `!== null` where Filament reads `filled()`**, and one arm is `??` where Filament tests `filled()`.
`getRecordClass()`'s custom-class check, `ensureCorrectRecordType()`'s, and `hasCustomRecordTitle()` all use `!== null` (`hasCustomRecordTitleAttribute()` is not one of the three — it is `!== null` upstream too, at `InteractsWithRecord.php:249`, so the pair is now consistent rather than split); `getRecordTitle()`'s **host** arm is `$host?->getDefaultActionRecordTitle($this) ?? $this->getRecordLabel()` where Filament wraps it in `filled()` — its custom and table arms are `filled()` here too, so the host arm is the only one left.
The difference is only ever an **empty string**: `->recordClass('')` is not a class, `->recordTitle('')` counts as custom here and does not upstream, and a host answering `''` stops the title chain here where Filament would fall through to the label.
One more delta, in the same family: Filament's `getRecordTitle()` nulls a `$record` **argument** that is not of the custom record class before using it; ours passes the caller's argument straight to `getCustomRecordTitle()`, on the reading that an explicitly-passed record is the caller's assertion, not an ambient one to be second-guessed — `ensureCorrectRecordType()` still guards every record the chain *resolves*.

The own-record gate is the one place that split had to be **repaired rather than recorded**.
Filament's gate is bare truthiness (`if ($record)`), which is `true` for *every* object; a plain `filled()` is not — it counts a `Countable`-empty or whitespace-only `Stringable` object as blank, so an empty collection bound as a record would have fallen through to the group instead of stopping.
The gate is therefore `is_object($record) || filled($record)`: an object always stops, exactly as upstream, and a scalar key stops when it is filled.
The residual delta is scalars only — `->record(0)` stops here and falls through upstream — which is the same empty-string family as the rest of this paragraph.
Pinned by `ActionTest`'s "stops at an own record object even when it is empty".

The class-string is injectable now: `resolveDefaultClosureDependencyForEvaluationByName()` resolves **`recordClass`**, which is the surface [`renames.md`](../renames.md#renames) was waiting for before deciding what Filament's `$model` closure parameter becomes.
The rename rows this landed — `model()`/`getModel()`/`getCustomModel()`, `modelLabel()`/`getModelLabel()`/`getTitleCaseModelLabel()`/`getCustomModelLabel()`, `get_model_label()`, `getDefaultActionModel`, `getDefaultActionModelLabel`, `getMountedActionSchemaModel`, and `$model` → `$recordClass` — are all in that table, under the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record).

Covered by [`InteractsWithRecordTest`](../../../packages/actions/tests/Unit/Concerns/InteractsWithRecordTest.php) (the trait's own chains, the guarded title and the type guard), [`ActionTest`](../../../packages/actions/tests/Unit/ActionTest.php) (both schema hops — container and component separately, since a root container's `getParentComponent()` is null and so the container case never reaches the component arm — the own-record stop, the `recordClass` injection, the context gate, the hostless label arm, the fall-to-label title and the clear-after pair) and [`HasActionsRecordTest`](../../../packages/actions/tests/Unit/Concerns/HasActionsRecordTest.php) (all four host hooks answering at once, `withDefault: false` refusing both the record and the class, the derive-anyway fallback for a host that owns the class but answers no label, and the host label being ignored for a class that is not the host's).

### A table-bound action titles and labels through its table

`Action`'s four record chains each hop through `getTable()?->…` before their older fallbacks, Filament's order verbatim: `getRecordLabel()` is custom → **table** → record class → host hook; `getPluralRecordLabel()` is custom → **table** → `plural(getRecordLabel())`; `getRecordTitle()` is custom → **table** → host hook → record label; `getRecordTitleAttribute()` is custom → **table**.

Two of those hops are terminal for a table-bound action, and that is the part a reader is likely to misread as a bug.
`Table::getRecordLabel()` and `Table::getRecordTitle()` both return `string`, never null or blank — each falls back to the table's own record label ([table.md](../tables/table.md#the-record-title-reads-the-attribute-through-the-accessor-and-falls-back-to-the-label)).
So once a table is bound, `getRecordLabel()` never reaches its class or host branch and `getRecordTitle()` never reaches [`getDefaultActionRecordTitle()`](interacts-with-actions.md): those branches serve **table-less** hosts — a page header action, a schema-hosted action — and are unreachable, not dead.
Filament behaves identically, which is why its `Resource::configureTable()` pushes the resource's labels and title attribute *onto the table*; panels' `ListRecords::table()` does the same for the same reason, and a resource row action reads its record title through the table rather than through the page hook.

The reads are all null-safe (`getTable()?->…`) because `sylament/tables` is a `suggest` of this package, not a `require` — the [guarded soft dependency](belongs-to-table.md).
An action body may still call methods on the table `getTable()` returns, since such a body only runs mounted from a table.

## Divergences

### Record-key resolution is hoisted to HasActions

Filament's `getRecord()` detects a scalar "record key" (anything that isn't a `Model` or array) and resolves it through a `resolveRecordUsing` closure stored *on the trait itself*, memoizing the resolved record back onto `$this->record`.
Sylament drops that branch from `InteractsWithRecord` entirely; a same-named `resolveRecordUsing()` setter and the key-resolution logic instead live on [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) (the live-component host trait — Filament's `InteractsWithActions`, per the [Has*/InteractsWith* swap](../renames.md#interface-and-trait-names-are-swapped)), scoped to turning a mounted action's `context.recordKey` into a record before calling `$action->record(...)` — covered by [`HasActionsRecordTest`](../../../packages/actions/tests/Unit/Concerns/HasActionsRecordTest.php).
The two `resolveRecordUsing` methods share a name but not a mechanism: Filament's is a per-action fallback inside `getRecord()`; Sylament's is a per-host callback used once, at mount time, by the live-component runtime.
Reversible if a future action needs to accept a bare record key directly (not just via live-component mount) — at that point `InteractsWithRecord::getRecord()` would need its own key-detection branch back.

The opposite direction — record → key — is *not* hoisted: [`resolveRecordKey()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) lives on the trait exactly as Filament has it, and is what [`getContext()`](action.md#getcontext-drops-the-record-model-gate) emits.
Its object arm is the one that had to be translated, since there is no `Model::getKey()` to call: it reads `id` through schemas' [`PropertyAccessor::getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php) — hence `Action` and `ActionGroup` now compose `CanAccessProperties` — and treats an `AccessException` (no readable `id`) as "no identity", returning null.
That makes it **lenient where the host's twin is strict**: the same unreadable record throws from [`HasTable::getTableRecordKey()`](../tables/has-table.md), because a table building its row index cannot proceed without a key, while an action emitting a mount context can simply omit one.
It is also the **fallback** rather than the whole outbound answer — a table-bound action over one of the table's own records is handed the table's key first ([above](action.md#getcontext-drops-the-record-model-gate)), and this literal `id` read is what answers for everything else — and, since the literal reading is now gated on one scalar field being the record's whole identity ([above](action.md#getcontext-drops-the-record-model-gate)), for a narrower "everything else" than before.
The gate is the **caller's**: `resolveRecordKey()` itself is byte-identical, because `ActionGroup` composes this trait without the manager registry the question needs.
Keep the two in step if the read ever changes: they are two implementations of one convention, not one method and its wrapper.
The array arm keeps Filament's `LogicException` for a *missing* [`ArrayRecord`](../../../packages/support/src/ArrayRecord.php) key entry — an array is unambiguously a record, so the absent entry is a caller mistake rather than an absent identity — but an entry that is present and blank (or not stringable) returns null where Filament's `strval()` would have emitted `''` or fatalled.
Covered by [`InteractsWithRecordTest`](../../../packages/actions/tests/Unit/Concerns/InteractsWithRecordTest.php).
