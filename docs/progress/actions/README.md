# Actions — port trail

Ports [`filament/actions`](../../../vendor/filament/actions) into [`packages/actions`](../../../packages/actions): the `Action` base with its trigger styles, modals and mount/call lifecycle, and the bulk, relationship and import/export actions built on it.

## Files

<!-- trail-index:start -->
| File | Status | Open | Ports | Description |
| --- | --- | --- | --- | --- |
| [action-group.md](action-group.md) | in progress | 3 in progress · 1 blocked · 7 todo | `ActionGroup.php`, `BulkActionGroup.php`, `Concerns/BelongsToGroup.php`, `Concerns/HasDropdown.php` | `ActionGroup`, which collapses actions behind one dropdown trigger or an inline row, its `BulkActionGroup` preset, and `BelongsToGroup` and `HasDropdown`. |
| [action.md](action.md) | in progress | 7 in progress · 4 todo | `Action.php`, `Concerns/BelongsToLivewire.php`, `Concerns/CanBeMounted.php`, `Concerns/CanBeDisabled.php`, `Concerns/CanBeHidden.php`, `Concerns/CanBeOutlined.php`, `Concerns/CanCallParentAction.php`, `Concerns/CanRedirect.php`, `Concerns/HasArguments.php`, `Concerns/HasData.php`, `Concerns/HasExtraModalOverlayAttributes.php`, `Concerns/HasExtraModalWindowAttributes.php`, `Concerns/HasGroupedIcon.php`, `Concerns/HasLabel.php`, `Concerns/HasLifecycleHooks.php`, `Concerns/HasMountableArguments.php`, `Concerns/HasName.php`, `Concerns/HasParentActions.php`, `Concerns/HasSize.php`, `Events/ActionCalled.php`, `Events/ActionCalling.php`, `vendor/filament/support/src/Concerns/HasIconPosition.php` | The base class every actions-package action extends, with the members it declares itself and the concerns no other trail file documents. |
| [actions-bundle.md](actions-bundle.md) | in progress | 1 blocked · 1 todo | `ActionsServiceProvider.php`, `routes/web.php`, `database/migrations/` | The actions bundle class, its service, Twig and AssetMapper wiring, the page-wide dispatch controller, and what the package manifest declares. |
| [associate-action.md](associate-action.md) | done | — | `AssociateAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/associate.php` | The prebuilt header action that points picked records at a one-to-many relation-scoped table's owner through a record select. |
| [attach-action.md](attach-action.md) | in progress | 1 blocked | `AttachAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/attach.php` | The prebuilt header action that makes picked records members of a many-to-many relation-scoped table's owner through a record select. |
| [belongs-to-schema-component.md](belongs-to-schema-component.md) | done | — | `Concerns/BelongsToSchemaComponent.php` | The trait binding an action or group to the schema component and container it renders inside, with the fallback chain the `$get` / `$set` utilities read. |
| [belongs-to-table.md](belongs-to-table.md) | done | — | `Concerns/BelongsToTable.php` | The trait binding an action or action group to the table that renders it, and why its Table reference needs no Composer require. |
| [bulk-action.md](bulk-action.md) | in progress | 1 blocked · 1 todo | `BulkAction.php`, `Concerns/InteractsWithSelectedRecords.php`, `Concerns/CanFetchSelectedRecords.php`, `Concerns/CanDeselectRecordsAfterCompletion.php` | The preset that runs an action over a table's selected records, and the three selected-records concerns it turns on. |
| [can-dispatch-event.md](can-dispatch-event.md) | done | — | `Concerns/CanDispatchEvent.php` | The trait that makes an action dispatch a browser event on click, rendered as payload attributes on its trigger. |
| [can-notify.md](can-notify.md) | in progress | 4 blocked · 1 todo | `Concerns/CanNotify.php` | The trait that builds and sends an action's success and failure notifications through the optional notifications package. |
| [can-open-modal.md](can-open-modal.md) | in progress | 1 in progress · 2 todo | `Concerns/CanOpenModal.php`, `Concerns/CanRequireConfirmation.php`, `resources/views/action-modal.blade.php` | The trait that configures an action's modal, the confirmation flag that tunes its defaults, and the ActionModal template that renders it. |
| [can-open-url.md](can-open-url.md) | done | — | `Concerns/CanOpenUrl.php` | The trait that gives an action a URL, opened in the same or a new tab, with the host's default URL as fallback. |
| [can-submit-form.md](can-submit-form.md) | done | — | `Concerns/CanSubmitForm.php` | The CanSubmitForm concern on Action, which turns an action's trigger into a submit button for a form. |
| [can-use-database-transactions.md](can-use-database-transactions.md) | done | — | `Concerns/CanUseDatabaseTransactions.php` | The trait that wraps an action's run in a Doctrine transaction and resolves the entity manager a record's class belongs to. |
| [create-action.md](create-action.md) | done | — | `CreateAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/create.php` | The prebuilt create action, a link to a create page or a modal that maps a form onto a new record and persists it through Doctrine. |
| [delete-action.md](delete-action.md) | in progress | 3 blocked | `DeleteAction.php`, `DeleteBulkAction.php`, `Concerns/CanCustomizeProcess.php` | The prebuilt record delete and its bulk sibling DeleteBulkAction, both removing records through Doctrine behind a confirmation modal. |
| [detach-action.md](detach-action.md) | in progress | 1 in progress · 1 blocked | `DetachAction.php`, `DetachBulkAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/detach.php` | The prebuilt many-to-many detach and its bulk sibling DetachBulkAction, undoing records' membership of a relation-scoped table's relationship. |
| [dissociate-action.md](dissociate-action.md) | in progress | 1 blocked | `DissociateAction.php`, `DissociateBulkAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/dissociate.php` | The prebuilt one-to-many dissociate and its bulk sibling DissociateBulkAction, nulling records' owner on a relation-scoped table. |
| [edit-action.md](edit-action.md) | done | — | `EditAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/edit.php` | The prebuilt per-record edit action, a link to an edit page or a modal that maps a form back onto the bound record and flushes it. |
| [enums.md](enums.md) | done | — | `Enums/ActionStatus.php` | The actions package's enums, which hold ActionStatus, the outcome an action reports after it runs. |
| [exceptions.md](exceptions.md) | done | — | `Exceptions/ActionNotResolvableException.php` | The actions package's exceptions, which hold ActionNotResolvableException, the signal that a mounted action cannot be resolved. |
| [has-action.md](has-action.md) | in progress | 1 todo | `Concerns/HasAction.php`, `Concerns/CanClose.php`, `Action.php` | The trait holding an action's closure or live-action name and its click gate, with CanClose's client-side dismiss. |
| [has-actions.md](has-actions.md) | in progress | 4 in progress · 2 blocked · 3 todo | `Concerns/InteractsWithActions.php` | The host-side trait a UX LiveComponent page composes to mount, resolve, call and unmount a stack of Actions. |
| [has-id.md](has-id.md) | done | — | `Concerns/HasId.php` | The actions package's HasId concern, which gives an action an id that falls back to its name. |
| [has-schema.md](has-schema.md) | done | — | `Concerns/HasSchema.php`, `Concerns/HasWizard.php` | The action-side schema declaration, `schema()` and `getSchema()`, with its wizard arm and the `HasWizard` concern that configures it. |
| [interacts-with-actions.md](interacts-with-actions.md) | in progress | 1 blocked · 1 todo | `Contracts/HasActions.php`, `Concerns/InteractsWithActions.php` | The `InteractsWithActions` host contract a live component implements to mount and render actions, ported from Filament's `Contracts\HasActions`. |
| [interacts-with-record.md](interacts-with-record.md) | done | — | `Concerns/InteractsWithRecord.php` | The trait that binds an action to a record and resolves its record, class, label and title chains, plus the record key it emits. |
| [modals.md](modals.md) | done | — | `resources/views/components/modals.blade.php`, `resources/views/action-modal.blade.php`, `Concerns/InteractsWithActions.php`, `resources/js/components/modals.js`, `resources/js/index.js` | The `<twig:SylamentActions:Modals />` component that renders one ActionModal per mounted frame, and the host's per-frame id and open-state helpers it reads. |
| [replicate-action.md](replicate-action.md) | done | — | `ReplicateAction.php`, `Concerns/CanCustomizeProcess.php`, `resources/lang/en/replicate.php` | The prebuilt per-record duplicate that copies the bound record through Doctrine metadata, optionally opens a form on the copy, and saves it. |
| [view-action.md](view-action.md) | in progress | 1 in progress | `ViewAction.php`, `resources/lang/en/view.php` | The prebuilt read-only record action, a modal that fills the record's schema, disables it and offers only a Close button. |
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`ButtonAction.php`](../../../vendor/filament/actions/src/ButtonAction.php) | skipped | — | [deprecated for `Action::button()`](../README.md#statuses) |
| [`ExportAction.php`](../../../vendor/filament/actions/src/ExportAction.php) | todo | — | — |
| [`ExportBulkAction.php`](../../../vendor/filament/actions/src/ExportBulkAction.php) | todo | — | — |
| [`ForceDeleteAction.php`](../../../vendor/filament/actions/src/ForceDeleteAction.php) | skipped | — | [diverges](delete-action.md#there-is-no-soft-delete-arm-to-hide-behind) |
| [`ForceDeleteBulkAction.php`](../../../vendor/filament/actions/src/ForceDeleteBulkAction.php) | skipped | — | [diverges](delete-action.md#there-is-no-soft-delete-arm-to-hide-behind) |
| [`IconButtonAction.php`](../../../vendor/filament/actions/src/IconButtonAction.php) | skipped | — | [deprecated for `Action::iconButton()`](../README.md#statuses) |
| [`ImportAction.php`](../../../vendor/filament/actions/src/ImportAction.php) | todo | — | — |
| [`RestoreAction.php`](../../../vendor/filament/actions/src/RestoreAction.php) | skipped | — | [diverges](delete-action.md#there-is-no-soft-delete-arm-to-hide-behind) |
| [`RestoreBulkAction.php`](../../../vendor/filament/actions/src/RestoreBulkAction.php) | skipped | — | [diverges](delete-action.md#there-is-no-soft-delete-arm-to-hide-behind) |
| [`SelectAction.php`](../../../vendor/filament/actions/src/SelectAction.php) | todo | — | — |
| [`ActionName.php`](../../../vendor/filament/actions/src/ActionName.php) | todo | — | — |
| [`Concerns/CanBeAuthorized.php`](../../../vendor/filament/actions/src/Concerns/CanBeAuthorized.php) | todo | — | — |
| [`Concerns/CanBeBooted.php`](../../../vendor/filament/actions/src/Concerns/CanBeBooted.php) | todo | — | — |
| [`Concerns/CanBeLabeledFrom.php`](../../../vendor/filament/actions/src/Concerns/CanBeLabeledFrom.php) | todo | — | — |
| [`Concerns/CanBeRateLimited.php`](../../../vendor/filament/actions/src/Concerns/CanBeRateLimited.php) | todo | — | — |
| [`Concerns/CanBeSorted.php`](../../../vendor/filament/actions/src/Concerns/CanBeSorted.php) | todo | — | — |
| [`Concerns/CanExportRecords.php`](../../../vendor/filament/actions/src/Concerns/CanExportRecords.php) | todo | — | — |
| [`Concerns/HasInfolist.php`](../../../vendor/filament/actions/src/Concerns/HasInfolist.php) | skipped | — | [deprecated for `schema()`](../README.md#statuses) |
| [`Concerns/HasKeyBindings.php`](../../../vendor/filament/actions/src/Concerns/HasKeyBindings.php) | todo | — | — |
| [`Concerns/HasTableIcon.php`](../../../vendor/filament/actions/src/Concerns/HasTableIcon.php) | todo | — | — |
| [`Concerns/HasTooltip.php`](../../../vendor/filament/actions/src/Concerns/HasTooltip.php) | todo | — | — |
| [`Exports/Exporter.php`](../../../vendor/filament/actions/src/Exports/Exporter.php) | todo | — | — |
| [`Exports/ExportColumn.php`](../../../vendor/filament/actions/src/Exports/ExportColumn.php) | todo | — | — |
| [`Exports/ExportDispatcher.php`](../../../vendor/filament/actions/src/Exports/ExportDispatcher.php) | todo | — | — |
| [`Exports/Enums/ExportFormat.php`](../../../vendor/filament/actions/src/Exports/Enums/ExportFormat.php) | todo | — | — |
| [`Exports/Enums/Contracts/ExportFormat.php`](../../../vendor/filament/actions/src/Exports/Enums/Contracts/ExportFormat.php) | todo | — | — |
| [`Exports/Models/Export.php`](../../../vendor/filament/actions/src/Exports/Models/Export.php) | todo | — | — |
| [`Exports/Concerns/`](../../../vendor/filament/actions/src/Exports/Concerns) | todo | — | — |
| [`Exports/ContentGenerators/`](../../../vendor/filament/actions/src/Exports/ContentGenerators) | todo | — | — |
| [`Exports/Downloaders/`](../../../vendor/filament/actions/src/Exports/Downloaders) | todo | — | — |
| [`Exports/Http/`](../../../vendor/filament/actions/src/Exports/Http) | todo | — | — |
| [`Exports/Jobs/`](../../../vendor/filament/actions/src/Exports/Jobs) | todo | — | — |
| [`Imports/Importer.php`](../../../vendor/filament/actions/src/Imports/Importer.php) | todo | — | — |
| [`Imports/ImportColumn.php`](../../../vendor/filament/actions/src/Imports/ImportColumn.php) | todo | — | — |
| [`Imports/ImportDispatcher.php`](../../../vendor/filament/actions/src/Imports/ImportDispatcher.php) | todo | — | — |
| [`Imports/Models/Import.php`](../../../vendor/filament/actions/src/Imports/Models/Import.php) | todo | — | — |
| [`Imports/Models/FailedImportRow.php`](../../../vendor/filament/actions/src/Imports/Models/FailedImportRow.php) | todo | — | — |
| [`Imports/ContentGenerators/`](../../../vendor/filament/actions/src/Imports/ContentGenerators) | todo | — | — |
| [`Imports/Downloaders/`](../../../vendor/filament/actions/src/Imports/Downloaders) | todo | — | — |
| [`Imports/Events/`](../../../vendor/filament/actions/src/Imports/Events) | todo | — | — |
| [`Imports/Exceptions/`](../../../vendor/filament/actions/src/Imports/Exceptions) | todo | — | — |
| [`Imports/Http/`](../../../vendor/filament/actions/src/Imports/Http) | todo | — | — |
| [`Imports/Jobs/`](../../../vendor/filament/actions/src/Imports/Jobs) | todo | — | — |
| [`Commands/`](../../../vendor/filament/actions/src/Commands) | todo | — | — |
| [`Testing/TestAction.php`](../../../vendor/filament/actions/src/Testing/TestAction.php) | todo | — | — |
| [`Testing/TestsActions.php`](../../../vendor/filament/actions/src/Testing/TestsActions.php) | todo | — | — |
| [`Testing/ExportFake.php`](../../../vendor/filament/actions/src/Testing/ExportFake.php) | todo | — | — |
| [`Testing/ImportFake.php`](../../../vendor/filament/actions/src/Testing/ImportFake.php) | todo | — | — |
| [`Testing/TestableExport.php`](../../../vendor/filament/actions/src/Testing/TestableExport.php) | todo | — | — |
| [`Testing/TestableImport.php`](../../../vendor/filament/actions/src/Testing/TestableImport.php) | todo | — | — |
| [`View/ActionsIconAlias.php`](../../../vendor/filament/actions/src/View/ActionsIconAlias.php) | todo | — | — |
| [`View/ActionsRenderHook.php`](../../../vendor/filament/actions/src/View/ActionsRenderHook.php) | todo | — | — |
| [`views/components/group.blade.php`](../../../vendor/filament/actions/resources/views/components/group.blade.php) | todo | — | — |
| [`resources/lang/`](../../../vendor/filament/actions/resources/lang) | todo | — | — |
