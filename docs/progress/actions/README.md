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

The 16 concerns Filament composes onto the `Action` base —
`BelongsToLiveComponent`, `CanBeDisabled`, `CanBeHidden`, `CanBeOutlined`,
`CanNotify`, `CanOpenModal`, `CanOpenUrl`, `CanRequireConfirmation`,
`HasAction`, `HasArguments`, `HasGroupedIcon`, `HasLabel`,
`HasLifecycleHooks`, `HasName`, `HasSize`, and
`InteractsWithRecord` — are single-consumer in the port (only `Action` exists
today), so they have folded into [`action.md`](action.md) and left the Features
table, per [one row per file](../README.md#one-row-per-file-one-file-per-thing);
the host-side `HasActions` trait keeps its own row. `Action`'s two rendering
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
service provider live in [Supporting surface](#supporting-surface).)

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Action`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | [action.md](action.md) |
| [`ActionGroup`](../../../packages/actions/src/ActionGroup.php) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | in progress | [action-group.md](action-group.md) |
| AssociateAction | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | todo | - |
| AttachAction | [`AttachAction.php`](../../../vendor/filament/actions/src/AttachAction.php) | todo | - |
| BulkAction | [`BulkAction.php`](../../../vendor/filament/actions/src/BulkAction.php) | todo | - |
| BulkActionGroup | [`BulkActionGroup.php`](../../../vendor/filament/actions/src/BulkActionGroup.php) | todo | - |
| ButtonAction | [`ButtonAction.php`](../../../vendor/filament/actions/src/ButtonAction.php) | todo | - |
| CreateAction | [`CreateAction.php`](../../../vendor/filament/actions/src/CreateAction.php) | todo | - |
| DeleteAction | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | todo | - |
| DeleteBulkAction | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | todo | - |
| DetachAction | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | todo | - |
| DetachBulkAction | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | todo | - |
| DissociateAction | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | todo | - |
| DissociateBulkAction | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) | todo | - |
| EditAction | [`EditAction.php`](../../../vendor/filament/actions/src/EditAction.php) | todo | - |
| ExportAction | [`ExportAction.php`](../../../vendor/filament/actions/src/ExportAction.php) | todo | - |
| ExportBulkAction | [`ExportBulkAction.php`](../../../vendor/filament/actions/src/ExportBulkAction.php) | todo | - |
| ForceDeleteAction | [`ForceDeleteAction.php`](../../../vendor/filament/actions/src/ForceDeleteAction.php) | todo | - |
| ForceDeleteBulkAction | [`ForceDeleteBulkAction.php`](../../../vendor/filament/actions/src/ForceDeleteBulkAction.php) | todo | - |
| IconButtonAction | [`IconButtonAction.php`](../../../vendor/filament/actions/src/IconButtonAction.php) | todo | - |
| ImportAction | [`ImportAction.php`](../../../vendor/filament/actions/src/ImportAction.php) | todo | - |
| ReplicateAction | [`ReplicateAction.php`](../../../vendor/filament/actions/src/ReplicateAction.php) | todo | - |
| RestoreAction | [`RestoreAction.php`](../../../vendor/filament/actions/src/RestoreAction.php) | todo | - |
| RestoreBulkAction | [`RestoreBulkAction.php`](../../../vendor/filament/actions/src/RestoreBulkAction.php) | todo | - |
| SelectAction | [`SelectAction.php`](../../../vendor/filament/actions/src/SelectAction.php) | todo | - |
| ViewAction | [`ViewAction.php`](../../../vendor/filament/actions/src/ViewAction.php) | todo | - |

## Features

Cross-cutting action concerns — [`src/Concerns`](../../../vendor/filament/actions/src/Concerns).
Most are mixed onto the `Action` base; the `InteractsWith*` traits are host-side
(mixed onto the Livewire / LiveComponent page that mounts actions). The 16
concerns `Action` composes are folded into [`action.md`](action.md) and no longer
carry rows here — the remaining rows are the not-yet-started concerns plus the
host-side `HasActions` trait. `BelongsToGroup` folds the same way into
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
| [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) | [`Concerns/BelongsToSchemaComponent.php`](../../../vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php) | done | - |
| BelongsToTable | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php) | todo | - |
| CanBeAuthorized | [`Concerns/CanBeAuthorized.php`](../../../vendor/filament/actions/src/Concerns/CanBeAuthorized.php) | todo | - |
| CanBeBooted | [`Concerns/CanBeBooted.php`](../../../vendor/filament/actions/src/Concerns/CanBeBooted.php) | todo | - |
| CanBeLabeledFrom | [`Concerns/CanBeLabeledFrom.php`](../../../vendor/filament/actions/src/Concerns/CanBeLabeledFrom.php) | todo | - |
| CanBeMounted | [`Concerns/CanBeMounted.php`](../../../vendor/filament/actions/src/Concerns/CanBeMounted.php) | todo | - |
| CanBeRateLimited | [`Concerns/CanBeRateLimited.php`](../../../vendor/filament/actions/src/Concerns/CanBeRateLimited.php) | todo | - |
| CanBeSorted | [`Concerns/CanBeSorted.php`](../../../vendor/filament/actions/src/Concerns/CanBeSorted.php) | todo | - |
| CanCallParentAction | [`Concerns/CanCallParentAction.php`](../../../vendor/filament/actions/src/Concerns/CanCallParentAction.php) | todo | - |
| CanClose | [`Concerns/CanClose.php`](../../../vendor/filament/actions/src/Concerns/CanClose.php) | todo | - |
| CanCustomizeProcess | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | todo | - |
| CanDeselectRecordsAfterCompletion | [`Concerns/CanDeselectRecordsAfterCompletion.php`](../../../vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | todo | - |
| CanDispatchEvent | [`Concerns/CanDispatchEvent.php`](../../../vendor/filament/actions/src/Concerns/CanDispatchEvent.php) | todo | - |
| CanExportRecords | [`Concerns/CanExportRecords.php`](../../../vendor/filament/actions/src/Concerns/CanExportRecords.php) | todo | - |
| CanFetchSelectedRecords | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | todo | - |
| CanRedirect | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | todo | - |
| CanSubmitForm | [`Concerns/CanSubmitForm.php`](../../../vendor/filament/actions/src/Concerns/CanSubmitForm.php) | todo | - |
| CanUseDatabaseTransactions | [`Concerns/CanUseDatabaseTransactions.php`](../../../vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php) | todo | - |
| [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) | in progress | [has-actions.md](has-actions.md) |
| HasData | [`Concerns/HasData.php`](../../../vendor/filament/actions/src/Concerns/HasData.php) | todo | - |
| HasExtraModalOverlayAttributes | [`Concerns/HasExtraModalOverlayAttributes.php`](../../../vendor/filament/actions/src/Concerns/HasExtraModalOverlayAttributes.php) | todo | - |
| HasExtraModalWindowAttributes | [`Concerns/HasExtraModalWindowAttributes.php`](../../../vendor/filament/actions/src/Concerns/HasExtraModalWindowAttributes.php) | todo | - |
| [`HasId`](../../../packages/actions/src/Concerns/HasId.php) | [`Concerns/HasId.php`](../../../vendor/filament/actions/src/Concerns/HasId.php) | done | Filament-exact two-method port; not composed onto `Action` (Filament composes it only on `SelectAction`, its future consumer here) |
| HasInfolist | [`Concerns/HasInfolist.php`](../../../vendor/filament/actions/src/Concerns/HasInfolist.php) | todo | - |
| HasKeyBindings | [`Concerns/HasKeyBindings.php`](../../../vendor/filament/actions/src/Concerns/HasKeyBindings.php) | todo | - |
| HasMountableArguments | [`Concerns/HasMountableArguments.php`](../../../vendor/filament/actions/src/Concerns/HasMountableArguments.php) | todo | - |
| HasParentActions | [`Concerns/HasParentActions.php`](../../../vendor/filament/actions/src/Concerns/HasParentActions.php) | todo | - |
| HasSchema | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | todo | - |
| HasTableIcon | [`Concerns/HasTableIcon.php`](../../../vendor/filament/actions/src/Concerns/HasTableIcon.php) | todo | - |
| HasTooltip | [`Concerns/HasTooltip.php`](../../../vendor/filament/actions/src/Concerns/HasTooltip.php) | todo | - |
| HasWizard | [`Concerns/HasWizard.php`](../../../vendor/filament/actions/src/Concerns/HasWizard.php) | todo | - |
| InteractsWithSelectedRecords | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | todo | - |

## Supporting surface

Everything with no action class of its own: the host contract, the status enum,
the call events, the resolve exception, the `ActionName` attribute, the
import/export subsystem (the user-facing `Exporter` / `Importer` / column bases
plus their Eloquent models, queue jobs, downloaders, HTTP controllers, and `make:`
commands), the test helpers, the icon-alias and render-hook registries, the
rendered views, the translations and assets, and the bundle wiring —
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
`Action`'s own rendering templates —
[`button-action.html.twig`](../../../packages/actions/templates/components/button-action.html.twig)
(a Sylament composition with **no** single Filament source; Filament renders
action buttons through the support-layer button/link component views) and
[`ConfirmModal/index.html.twig`](../../../packages/actions/templates/components/ConfirmModal/index.html.twig)
(porting [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php))
— are folded into [`action.md`](action.md) as its button- and modal-rendering
rows rather than carrying rows here. The host-side
[`Modals`](../../../packages/actions/templates/components/Modals.html.twig)
component (porting [`components/modals.blade.php`](../../../vendor/filament/actions/resources/views/components/modals.blade.php))
folds the same way into [`has-actions.md`](has-actions.md), being a pure view over
that trait's mounted-action state.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | in progress | [interacts-with-actions.md](interacts-with-actions.md) |
| [`ActionStatus`](../../../packages/actions/src/Enums/ActionStatus.php) | [`Enums/ActionStatus.php`](../../../vendor/filament/actions/src/Enums/ActionStatus.php) | done | - |
| ActionCalled | [`Events/ActionCalled.php`](../../../vendor/filament/actions/src/Events/ActionCalled.php) | todo | - |
| ActionCalling | [`Events/ActionCalling.php`](../../../vendor/filament/actions/src/Events/ActionCalling.php) | todo | - |
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
| ActionsIconAlias | [`View/ActionsIconAlias.php`](../../../vendor/filament/actions/src/View/ActionsIconAlias.php) | todo | - |
| ActionsRenderHook | [`View/ActionsRenderHook.php`](../../../vendor/filament/actions/src/View/ActionsRenderHook.php) | todo | - |
| views/components/group | [`views/components/group.blade.php`](../../../vendor/filament/actions/resources/views/components/group.blade.php) | todo | - |
| Translations | [`resources/lang/`](../../../vendor/filament/actions/resources/lang) | todo | - |
| Assets (JS/CSS) | [`resources/js/`](../../../vendor/filament/actions/resources/js) | todo | - |
| [`SylamentActionsBundle`](../../../packages/actions/src/SylamentActionsBundle.php) | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | in progress | [actions-bundle.md](actions-bundle.md) |
