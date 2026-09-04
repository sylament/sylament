# Actions — port progress

Ports [`filament/actions`](../../../vendor/filament/actions) into
[`packages/actions`](../../../packages/actions): action objects — button / link /
icon-button / badge styling, modals, and the mount/call lifecycle — plus the
bulk, relationship, and import/export action families built on the `Action` base.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `actions/src` surface end to end (plus the rendered
views, translations, and assets), so no table carries an
[unaudited row](../README.md#an-unaudited-table-says-so). The per-component audit
against Filament has now resolved every **started** row (a linked first-column
source) to a real [status](../README.md#statuses) — **done**, **in progress**,
**blocked**, or **skipped** — with a detail file wherever a divergence, an open
gap, or real sub-structure earns one. A plain-text name is not built yet and
stays **todo**.

The 26 concerns Filament composes onto the `Action` base —
`BelongsToLiveComponent`, `CanBeDisabled`, `CanBeHidden`, `CanBeOutlined`,
`CanCallParentAction`, `CanClose`, `CanDeselectRecordsAfterCompletion`,
`CanDispatchEvent`, `CanFetchSelectedRecords`, `CanNotify`,
`CanOpenModal`, `CanOpenUrl`, `CanRedirect`, `CanRequireConfirmation`,
`CanUseDatabaseTransactions`,
`HasAction`, `HasArguments`, `HasGroupedIcon`, `HasLabel`,
`HasLifecycleHooks`, `HasMountableArguments`, `HasName`, `HasParentActions`,
`HasSize`,
`InteractsWithRecord` and `InteractsWithSelectedRecords` — have folded into
[`action.md`](action.md) and left the Features table, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). Most are
single-consumer in the port; the four `ActionGroup` also composes —
`CanBeOutlined`, `HasGroupedIcon`, `HasLabel`, `HasSize` — stay written up once
in `action.md`, with reference rows in [`action-group.md`](action-group.md);
the host-side `HasActions` trait keeps its own row. `Action`'s rendering
templates fold in the same way (see the [Supporting surface](#supporting-surface)
note). [`support/component.md`](../support/component.md) is the shape each detail
file follows.

Names follow Filament except where the port deliberately renames: the first
column links the Sylament file under **its own** name, the **Ports** column names
the Filament source. The rename to know while reading these tables is the
`Has*` / `InteractsWith*` **swap** (recorded in [`renames.md`](../renames.md)) —
in Sylament, `HasActions` is the host-side **trait** (ports Filament's
[`Concerns/InteractsWithActions`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php))
and `InteractsWithActions` is the **interface** (ports Filament's
[`Contracts/HasActions`](../../../vendor/filament/actions/src/Contracts/HasActions.php)),
the exact inverse of Filament.

## Components

The `Action` base, the prebuilt record / relationship / import / export actions,
`ActionGroup`, and the bulk-action family — [`src/`](../../../vendor/filament/actions/src).
`Action` heads the table; the rest follow alphabetically. (`ActionName` and the
service provider live in [Supporting surface](#supporting-surface).) Four rows
share a detail file with the class they preset, the way a component's concerns
fold into its own file: `BulkActionGroup` into
[`action-group.md`](action-group.md), `DeleteBulkAction` into
[`delete-action.md`](delete-action.md#deletebulkaction), `DetachBulkAction` into
[`detach-action.md`](detach-action.md#detachbulkaction) and
`DissociateBulkAction` into
[`dissociate-action.md`](dissociate-action.md#dissociatebulkaction).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Action`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | [action.md](action.md) |
| [`ActionGroup`](../../../packages/actions/src/ActionGroup.php) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | in progress | [action-group.md](action-group.md) |
| [`AssociateAction`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | [associate-action.md](associate-action.md) |
| [`AttachAction`](../../../packages/actions/src/AttachAction.php) | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | in progress | [attach-action.md](attach-action.md) |
| [`BulkAction`](../../../packages/actions/src/BulkAction.php) | [`BulkAction.php`](../../../vendor/filament/actions/src/BulkAction.php) | in progress | [bulk-action.md](bulk-action.md) |
| [`BulkActionGroup`](../../../packages/actions/src/BulkActionGroup.php) | [`BulkActionGroup.php`](../../../vendor/filament/actions/src/BulkActionGroup.php) | in progress | [action-group.md](action-group.md) |
| ButtonAction | [`ButtonAction.php`](../../../vendor/filament/actions/src/ButtonAction.php) | todo | - |
| [`CreateAction`](../../../packages/actions/src/CreateAction.php) | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | done | [create-action.md](create-action.md) |
| [`DeleteAction`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | in progress | [delete-action.md](delete-action.md) |
| [`DeleteBulkAction`](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | in progress | [delete-action.md](delete-action.md#deletebulkaction) |
| [`DetachAction`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | [detach-action.md](detach-action.md) |
| [`DetachBulkAction`](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | in progress | [detach-action.md](detach-action.md#detachbulkaction) |
| [`DissociateAction`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | [dissociate-action.md](dissociate-action.md) |
| [`DissociateBulkAction`](../../../packages/actions/src/DissociateBulkAction.php) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) | in progress | [dissociate-action.md](dissociate-action.md#dissociatebulkaction) |
| [`EditAction`](../../../packages/actions/src/EditAction.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | done | [edit-action.md](edit-action.md) |
| ExportAction | [`ExportAction.php`](../../../vendor/filament/actions/src/ExportAction.php) | todo | - |
| ExportBulkAction | [`ExportBulkAction.php`](../../../vendor/filament/actions/src/ExportBulkAction.php) | todo | - |
| ForceDeleteAction | [`ForceDeleteAction.php`](../../../vendor/filament/actions/src/ForceDeleteAction.php) | todo | - |
| ForceDeleteBulkAction | [`ForceDeleteBulkAction.php`](../../../vendor/filament/actions/src/ForceDeleteBulkAction.php) | todo | - |
| IconButtonAction | [`IconButtonAction.php`](../../../vendor/filament/actions/src/IconButtonAction.php) | todo | - |
| ImportAction | [`ImportAction.php`](../../../vendor/filament/actions/src/ImportAction.php) | todo | - |
| [`ReplicateAction`](../../../packages/actions/src/ReplicateAction.php) | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | done | [replicate-action.md](replicate-action.md) |
| RestoreAction | [`RestoreAction.php`](../../../vendor/filament/actions/src/RestoreAction.php) | todo | - |
| RestoreBulkAction | [`RestoreBulkAction.php`](../../../vendor/filament/actions/src/RestoreBulkAction.php) | todo | - |
| SelectAction | [`SelectAction.php`](../../../vendor/filament/actions/src/SelectAction.php) | todo | - |
| [`ViewAction`](../../../packages/actions/src/ViewAction.php) | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | in progress | [view-action.md](view-action.md) |

## Features

Cross-cutting action concerns — [`src/Concerns`](../../../vendor/filament/actions/src/Concerns).
Most are mixed onto the `Action` base; the `InteractsWith*` traits are host-side
(mixed onto the Livewire / LiveComponent page that mounts actions). The 23
concerns `Action` composes are folded into [`action.md`](action.md) and no longer
carry rows here — the remaining rows are the not-yet-started concerns, the
host-side `HasActions` trait, and the two-method extra-modal-attribute traits
whose whole surface fits in a row. `BelongsToGroup` folds the same way into
[`action-group.md`](action-group.md) instead: `Action` and `ActionGroup` both
compose it, but its own surface (the back-reference pair) is small enough to
write up once, in the file for the class it primarily describes, rather than
spinning up a third detail file. `HasDropdown` folds into
[`action-group.md`](action-group.md) too, single-consumer (`ActionGroup`
only) and audited there, and so leaves this table the same way `Action`'s own
single-consumer concerns do. Ordered by the name in the first column: the
Sylament `HasActions` row (in the `H` block) is the port of Filament's
`Concerns/InteractsWithActions` trait — see the
[swap note above](#actions--port-progress).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) | [`Concerns/BelongsToSchemaComponent.php`](../../../vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php) | done | [belongs-to-schema-component](belongs-to-schema-component.md) |
| [`BelongsToTable`](../../../packages/actions/src/Concerns/BelongsToTable.php) | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php) | done | [belongs-to-table](belongs-to-table.md) |
| CanBeAuthorized | [`Concerns/CanBeAuthorized.php`](../../../vendor/filament/actions/src/Concerns/CanBeAuthorized.php) | todo | - |
| CanBeBooted | [`Concerns/CanBeBooted.php`](../../../vendor/filament/actions/src/Concerns/CanBeBooted.php) | todo | - |
| CanBeLabeledFrom | [`Concerns/CanBeLabeledFrom.php`](../../../vendor/filament/actions/src/Concerns/CanBeLabeledFrom.php) | todo | - |
| [`CanBeMounted`](../../../packages/actions/src/Concerns/CanBeMounted.php) | [`Concerns/CanBeMounted.php`](../../../vendor/filament/actions/src/Concerns/CanBeMounted.php) | done | Filament-exact: `mount()`, `mountUsing()`, `getMountUsing()` (defaulting to `fn (?Schema $schema = null) => $schema?->fill()`) and `fillForm()`. Filament wraps `mount()` in a `try`/`finally` calling `clearVisibilityCache()`; there is no visibility cache anywhere in the port, so the `finally` has nothing to clear and is not written. `fillForm()`'s closure is one parameter short of Filament's: Filament types it `Action $action` and calls `$action->evaluate($data)`, the port declares no action parameter and calls `$this->evaluate($data, …)`. [`Invoker::call()`](../../../packages/support/src/Evaluation/Invoker.php) invokes with `$callable(...$args)` and never rebinds a closure's `$this`, and `mount()` always evaluates `mountUsing` back through the instance that stored it, so the captured `$this` is exactly what an `$action` parameter would have received. It is also the only form that types: an untyped `$action` still resolves at runtime (by name, against `evaluationIdentifier`) but is statically `mixed`, so PHPStan cannot verify the `evaluate()` call on it once the trait is composed onto a class, where `$this` is typed by ordinary trait-in-context analysis. It is one *injection* wider than Filament's, though: the wrapper forwards the mounted schema to the user closure as `form` and `schema`, so `fillForm(fn (Schema $schema) => …)` reads the modal's own container. Filament never needed it — its fill closures read `$record->attributesToArray()`, ours read [`Schema::getStateFromRecord()`](../schemas/belongs-to-record.md), which needs the schema to read from — and without the forward a `Schema` parameter resolves by name to the *containing* schema through [`Action::resolveDefaultClosureDependencyForEvaluationByName()`](action.md), which is the page's form on a record page and `null` on a table row or a page header |
| CanBeRateLimited | [`Concerns/CanBeRateLimited.php`](../../../vendor/filament/actions/src/Concerns/CanBeRateLimited.php) | todo | - |
| CanBeSorted | [`Concerns/CanBeSorted.php`](../../../vendor/filament/actions/src/Concerns/CanBeSorted.php) | todo | - |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | Filament-exact, whole trait: `using()` stores one closure and `process(?Closure $default, array $parameters = [])` evaluates `$this->using ?? $default` — the single override point for a prebuilt action's persistence step, replacing the default **wholesale** rather than wrapping it. Composed by every prebuilt action that writes — [create](create-action.md), [edit](edit-action.md), [delete](delete-action.md), [replicate](replicate-action.md), [`DeleteBulkAction`](bulk-action.md), and the six relationship actions ([attach](attach-action.md), [detach](detach-action.md) with [`DetachBulkAction`](detach-action.md#detachbulkaction), [associate](associate-action.md), [dissociate](dissociate-action.md) with [`DissociateBulkAction`](dissociate-action.md#dissociatebulkaction)) — and by nothing else. It stays a row rather than a file because there is nothing to say past this paragraph: the trait is two members, Filament-exact, and what each composer's default body does is that composer's own file's business; `process()` returning `null` when neither closure is given is Filament's own shape, not a guard the port added. Covered by [`CanCustomizeProcessTest`](../../../packages/actions/tests/Unit/Concerns/CanCustomizeProcessTest.php) |
| CanExportRecords | [`Concerns/CanExportRecords.php`](../../../vendor/filament/actions/src/Concerns/CanExportRecords.php) | todo | - |
| [`CanMutateRecordData`](../../../packages/actions/src/Concerns/CanMutateRecordData.php) | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php), [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php), [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) (`mutateRecordDataUsing()`) | done | A **structural extraction**, not a rename: Filament declares `$mutateRecordDataUsing` and `mutateRecordDataUsing()` three times over — once each on `EditAction`, `ViewAction` and `ReplicateAction` — with no trait behind them, and inlines the evaluation into each class's fill closure. The port composes one trait onto the same three classes, because the port's fill side has more to share than Filament's: Filament reads the record with Eloquent's `$record->attributesToArray()`, while the port reads it through [`Schema::getStateFromRecord()`](../schemas/belongs-to-record.md), which needs the mounted schema — so `getRecordDataForFill(Schema $schema, object|array $record, array $except = [])` carries the read, the `$except` drop (the excluded attributes `ReplicateAction` will drop) and the mutator in one place. Two guards ride with it, and they are deliberately not the same width: `getRecordForFill()` accepts an **object or an array** record, because a `records()`-lane table row is an array and filling a form from one is ordinary, while `getRecordOrFail()` accepts an object only, because the default persistence bodies need an entity — and `using()` is the escape where the record is something else. `mutateRecordData()` re-checks the closure's return through the same [`HasData::assertFieldKeyedData()`](../../../packages/actions/src/Concerns/HasData.php) that `data()` runs `mutateDataUsing()`'s through, throwing a `TypeError` that names the setter. `renames.md` is untouched by any of it — no member changed name. One shape note the level-max gate forced on the fill guard: `getRecordForFill()` answers an **array** record through `array_filter(..., ARRAY_FILTER_USE_KEY)` on string keys, the same normalisation [`HasActions::resolveMountedActionSchemaRecord()`](has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation) already applies to the same value — `getRecord()` is `mixed`, so an `is_array()` narrowing alone cannot prove the `array<string, mixed>` the seam declares, and an [`ArrayRecord`](../../../packages/support/src/ArrayRecord.php) is string-keyed by definition. Covered by [`CanMutateRecordDataTest`](../../../packages/actions/tests/Unit/Concerns/CanMutateRecordDataTest.php), and end to end by [`EditActionTest`](../../../packages/actions/tests/Integration/EditActionTest.php) / [`ViewActionTest`](../../../packages/actions/tests/Integration/ViewActionTest.php) |
| [`CanSubmitForm`](../../../packages/actions/src/Concerns/CanSubmitForm.php) | [`Concerns/CanSubmitForm.php`](../../../vendor/filament/actions/src/Concerns/CanSubmitForm.php) | done | Full shape ported for API parity (`submit()`, `canSubmitForm()`, `getFormToSubmit()`, `formId()`, `getFormId()`). `canSubmitForm()` is the live consumer: the five trigger templates render `type="submit"` and suppress their live click binding from it, and [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) branches on it. `getFormToSubmit()` / `getFormId()` have **no** consumer yet — in Filament they feed a `wire:target` loading scope and a `form=` attribute for a button rendered outside its form, neither of which exists here; ported anyway so a migrating `submit()` / `formId()` call does not fatal. Filament's `hasCanSubmitForm()` / `hasFormId()` are not ported — trivial duplicates with no caller in Filament's own tree |
| [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) | in progress | [has-actions.md](has-actions.md) |
| [`HasData`](../../../packages/actions/src/Concerns/HasData.php) | [`Concerns/HasData.php`](../../../vendor/filament/actions/src/Concerns/HasData.php) | done | `data()` **replaces** wholesale rather than merging (unlike `HasArguments::mergeArguments()`), optionally through `mutateDataUsing()`; `resetData()` empties without mutating. `data()` adds a `TypeError` guard Filament has no need for — `mutateDataUsing` is a bare `?Closure` (no arity spec, per [the closure-typing convention](../../../CLAUDE.md)), so PHPStan cannot know the callback returned an array, and `assertFieldKeyedData()` supplies that guarantee instead of an unchecked assignment trusting a misbehaving callback — an `is_array()` check plus an all-string-keys pass behind a `@phpstan-assert array<string, mixed>`, the key half being what the level-max gate needs since PHPStan 2.2.13 stopped binding `evaluate()`'s `T` to a bare `Closure` argument, which leaves the result `mixed` and an `is_array()` narrowing alone at `array<mixed, mixed>`; same shape as the guards in [`Schemas\Concerns\CanModifyActions`](../../../packages/schemas/src/Concerns/CanModifyActions.php). `getRawData()` reads the host's per-frame bag through [`InteractsWithActions::getMountedActionData()`](interacts-with-actions.md) instead of Filament's direct property read, and there is deliberately **no** `getMountedActionsData()` — [why](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor). The deprecated `*FormData*` aliases (`formData()`, `getRawFormData()`) are not ported |
| [`HasExtraModalOverlayAttributes`](../../../packages/actions/src/Concerns/HasExtraModalOverlayAttributes.php) | [`Concerns/HasExtraModalOverlayAttributes.php`](../../../vendor/filament/actions/src/Concerns/HasExtraModalOverlayAttributes.php) | done | Filament-exact setter/getter pair (`$merge` appends, a non-merge call replaces the queue) folded through support's `ComponentAttributeBag`; renders onto the `<dialog>`, which is the overlay in the port ([why](../support/modal.md#the-modal-is-re-architected-onto-native-dialog)) |
| [`HasExtraModalWindowAttributes`](../../../packages/actions/src/Concerns/HasExtraModalWindowAttributes.php) | [`Concerns/HasExtraModalWindowAttributes.php`](../../../vendor/filament/actions/src/Concerns/HasExtraModalWindowAttributes.php) | done | The window twin of the row above, rendering onto the `.sy-modal-window` element (a `<div>`, or a `<form>` when the action has a [form wrapper](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit)) |
| [`HasId`](../../../packages/actions/src/Concerns/HasId.php) | [`Concerns/HasId.php`](../../../vendor/filament/actions/src/Concerns/HasId.php) | done | Filament-exact two-method port; not composed onto `Action` (Filament composes it only on `SelectAction`, its future consumer here) |
| HasInfolist | [`Concerns/HasInfolist.php`](../../../vendor/filament/actions/src/Concerns/HasInfolist.php) | todo | - |
| HasKeyBindings | [`Concerns/HasKeyBindings.php`](../../../vendor/filament/actions/src/Concerns/HasKeyBindings.php) | todo | - |
| [`HasSchema`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | [has-schema.md](has-schema.md) |
| HasTableIcon | [`Concerns/HasTableIcon.php`](../../../vendor/filament/actions/src/Concerns/HasTableIcon.php) | todo | - |
| HasTooltip | [`Concerns/HasTooltip.php`](../../../vendor/filament/actions/src/Concerns/HasTooltip.php) | todo | - |
| [`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php) | [`Concerns/HasWizard.php`](../../../vendor/filament/actions/src/Concerns/HasWizard.php) | done | Filament-exact member for member, declaration order aside (`steps()` / `startOnStep()` / `skippableSteps()` / `modifyWizardUsing()` / `isWizard()` / `isWizardSkippable()` / `getWizardStartStep()`), with `$modifyWizardUsing` typed one member wider than its setter for the same level-max reason `Wizard::nextAction()` is. What it configures is the schema-side branch — [has-schema.md](has-schema.md#the-wizard-arm) |

## Supporting surface

Everything with no action class of its own: the host contract, the status enum,
the call events, the resolve exception, the `ActionName` attribute, the
import/export subsystem (the user-facing `Exporter` / `Importer` / column bases
plus their Eloquent models, queue jobs, downloaders, HTTP controllers, and `make:`
commands), the test helpers, the icon-alias and render-hook registries, the
`renderAction()` Twig helper, the rendered views, the translations and assets,
and the bundle wiring —
[`src/Contracts`](../../../vendor/filament/actions/src/Contracts),
[`src/Enums`](../../../vendor/filament/actions/src/Enums),
[`src/Events`](../../../vendor/filament/actions/src/Events),
[`src/Exceptions`](../../../vendor/filament/actions/src/Exceptions),
[`src/Exports`](../../../vendor/filament/actions/src/Exports),
[`src/Imports`](../../../vendor/filament/actions/src/Imports),
[`src/Commands`](../../../vendor/filament/actions/src/Commands),
[`src/Testing`](../../../vendor/filament/actions/src/Testing),
[`src/View`](../../../vendor/filament/actions/src/View),
[`resources/views`](../../../vendor/filament/actions/resources/views). A row named
for a directory collapses that whole subtree into one navigational entry.
`Action`'s own rendering templates — the trigger family
([`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig),
[`Action/Link.html.twig`](../../../packages/actions/templates/partials/Action/Link.html.twig),
[`Action/Badge.html.twig`](../../../packages/actions/templates/partials/Action/Badge.html.twig),
[`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig),
[`Action/IconButton.html.twig`](../../../packages/actions/templates/partials/Action/IconButton.html.twig),
and `ActionGroup`'s own
[`GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig);
each a thin composition over the support-layer primitive Filament's matching
`*_VIEW` constant points at, so none carries markup of its own to track) and
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)
(porting [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php))
— are folded into [`action.md`](action.md) as its trigger- and modal-rendering
rows rather than carrying rows here. The host-side
[`Modals`](../../../packages/actions/templates/components/Modals/index.html.twig)
component (porting [`components/modals.blade.php`](../../../vendor/filament/actions/resources/views/components/modals.blade.php))
folds the same way into [`has-actions.md`](has-actions.md), being a pure view over
that trait's mounted-action state.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | in progress | [interacts-with-actions.md](interacts-with-actions.md) |
| [`ActionStatus`](../../../packages/actions/src/Enums/ActionStatus.php) | [`Enums/ActionStatus.php`](../../../vendor/filament/actions/src/Enums/ActionStatus.php) | done | - |
| [`ActionCalled`](../../../packages/actions/src/Events/ActionCalled.php) | [`Events/ActionCalled.php`](../../../vendor/filament/actions/src/Events/ActionCalled.php) | done | Final class, one promoted `Action`, one `getAction()`, dispatched from [`HasLifecycleHooks::callAfter()`](action.md)'s `finally` on Symfony's dispatcher — [as an object, where Filament dispatches a class-string](action.md#the-lifecycle-events-are-dispatched-as-objects-where-filament-dispatches-a-class-string) |
| [`ActionCalling`](../../../packages/actions/src/Events/ActionCalling.php) | [`Events/ActionCalling.php`](../../../vendor/filament/actions/src/Events/ActionCalling.php) | done | The twin, dispatched from `callBefore()` before the closure runs — [same divergence](action.md#the-lifecycle-events-are-dispatched-as-objects-where-filament-dispatches-a-class-string) |
| [`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php) | [`Exceptions/ActionNotResolvableException.php`](../../../vendor/filament/actions/src/Exceptions/ActionNotResolvableException.php) | done | - |
| ActionName | [`ActionName.php`](../../../vendor/filament/actions/src/ActionName.php) | todo | - |
| Exporter | [`Exports/Exporter.php`](../../../vendor/filament/actions/src/Exports/Exporter.php) | todo | - |
| ExportColumn | [`Exports/ExportColumn.php`](../../../vendor/filament/actions/src/Exports/ExportColumn.php) | todo | - |
| ExportFormat | [`Exports/Enums/ExportFormat.php`](../../../vendor/filament/actions/src/Exports/Enums/ExportFormat.php) | todo | - |
| ExportFormat (contract) | [`Exports/Enums/Contracts/ExportFormat.php`](../../../vendor/filament/actions/src/Exports/Enums/Contracts/ExportFormat.php) | todo | - |
| Export (model) | [`Exports/Models/Export.php`](../../../vendor/filament/actions/src/Exports/Models/Export.php) | todo | - |
| Exports / Downloaders | [`Exports/Downloaders/`](../../../vendor/filament/actions/src/Exports/Downloaders) | todo | - |
| Exports / Jobs | [`Exports/Jobs/`](../../../vendor/filament/actions/src/Exports/Jobs) | todo | - |
| Exports / Concerns | [`Exports/Concerns/`](../../../vendor/filament/actions/src/Exports/Concerns) | todo | - |
| Exports / Http | [`Exports/Http/`](../../../vendor/filament/actions/src/Exports/Http) | todo | - |
| Importer | [`Imports/Importer.php`](../../../vendor/filament/actions/src/Imports/Importer.php) | todo | - |
| ImportColumn | [`Imports/ImportColumn.php`](../../../vendor/filament/actions/src/Imports/ImportColumn.php) | todo | - |
| Import (model) | [`Imports/Models/Import.php`](../../../vendor/filament/actions/src/Imports/Models/Import.php) | todo | - |
| FailedImportRow (model) | [`Imports/Models/FailedImportRow.php`](../../../vendor/filament/actions/src/Imports/Models/FailedImportRow.php) | todo | - |
| Imports / Events | [`Imports/Events/`](../../../vendor/filament/actions/src/Imports/Events) | todo | - |
| Imports / Jobs | [`Imports/Jobs/`](../../../vendor/filament/actions/src/Imports/Jobs) | todo | - |
| Imports / Exceptions | [`Imports/Exceptions/`](../../../vendor/filament/actions/src/Imports/Exceptions) | todo | - |
| Imports / Http | [`Imports/Http/`](../../../vendor/filament/actions/src/Imports/Http) | todo | - |
| Commands | [`Commands/`](../../../vendor/filament/actions/src/Commands) | todo | - |
| Commands / FileGenerators | [`Commands/FileGenerators/`](../../../vendor/filament/actions/src/Commands/FileGenerators) | todo | - |
| TestAction | [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | todo | - |
| TestsActions | [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | - |
| [`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php) (`renderAction(name, arguments)`) | — (Sylament-only; mirrors schemas' [`SchemaExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | [actions-bundle.md](actions-bundle.md#the-bundle-registers-a-renderaction-twig-extension-which-filament-has-no-counterpart-for) |
| ActionsIconAlias | [`View/ActionsIconAlias.php`](../../../vendor/filament/actions/src/View/ActionsIconAlias.php) | todo | - |
| ActionsRenderHook | [`View/ActionsRenderHook.php`](../../../vendor/filament/actions/src/View/ActionsRenderHook.php) | todo | - |
| views/components/group | [`views/components/group.blade.php`](../../../vendor/filament/actions/resources/views/components/group.blade.php) | todo | - |
| Translations | [`resources/lang/`](../../../vendor/filament/actions/resources/lang) | todo | - |
| [Assets (JS/CSS)](../../../packages/actions/assets) | [`resources/js/`](../../../vendor/filament/actions/resources/js), [`resources/css/`](../../../vendor/filament/actions/resources/css) | in progress | the workspace ships one Sylament-only Stimulus controller (page-wide dispatch delegation) and the `sy-ac-*` rules, which compile into support's stylesheet; the built `dist` is mapped into AssetMapper as `@sylament/actions` ([actions-bundle.md](actions-bundle.md#asset-registration-is-an-assetmapper-prepend)), and Filament's Alpine modal-nesting plugin is unported — [actions-bundle.md](actions-bundle.md#the-asset-workspace-is-js-only-and-its-importmap-block-names-only-stimulus) |
| [`SylamentActionsBundle`](../../../packages/actions/src/SylamentActionsBundle.php) | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | in progress | [actions-bundle.md](actions-bundle.md) |
