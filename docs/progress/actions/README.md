# Actions — port trail

Ports [`filament/actions`](../../../vendor/filament/actions) into [`packages/actions`](../../../packages/actions): the `Action` base with its trigger styles, modals and mount/call lifecycle, and the bulk, relationship and import/export actions built on it.

## Files

<!-- trail-index:start -->
- [action-group.md](action-group.md)
- [action.md](action.md)
- [actions-bundle.md](actions-bundle.md)
- [associate-action.md](associate-action.md)
- [attach-action.md](attach-action.md)
- [belongs-to-schema-component.md](belongs-to-schema-component.md)
- [belongs-to-table.md](belongs-to-table.md)
- [bulk-action.md](bulk-action.md)
- [can-dispatch-event.md](can-dispatch-event.md)
- [can-notify.md](can-notify.md)
- [can-open-modal.md](can-open-modal.md)
- [can-open-url.md](can-open-url.md)
- [can-submit-form.md](can-submit-form.md)
- [can-use-database-transactions.md](can-use-database-transactions.md)
- [create-action.md](create-action.md)
- [delete-action.md](delete-action.md)
- [detach-action.md](detach-action.md)
- [dissociate-action.md](dissociate-action.md)
- [edit-action.md](edit-action.md)
- [enums.md](enums.md)
- [exceptions.md](exceptions.md)
- [has-action.md](has-action.md)
- [has-actions.md](has-actions.md)
- [has-id.md](has-id.md)
- [has-schema.md](has-schema.md)
- [interacts-with-actions.md](interacts-with-actions.md)
- [interacts-with-record.md](interacts-with-record.md)
- [modals.md](modals.md)
- [replicate-action.md](replicate-action.md)
- [view-action.md](view-action.md)
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
