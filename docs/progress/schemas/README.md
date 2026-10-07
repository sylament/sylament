# Schemas — port trail

Ports [`filament/schemas`](../../../vendor/filament/schemas) into [`packages/schemas`](../../../packages/schemas): the component model, from the base `Component` and the `Schema` container through the layout and content components and their shared concerns to the state loop (`$get`/`$set` and the state casts).

## Files

<!-- trail-index:start -->
- [actions.md](actions.md)
- [association-reader.md](association-reader.md)
- [belongs-to-live-component.md](belongs-to-live-component.md)
- [belongs-to-record.md](belongs-to-record.md)
- [boolean-state-cast.md](boolean-state-cast.md)
- [can-be-concealed.md](can-be-concealed.md)
- [can-be-disabled.md](can-be-disabled.md)
- [can-be-hidden.md](can-be-hidden.md)
- [can-be-marked-as-required.md](can-be-marked-as-required.md)
- [can-be-validated.md](can-be-validated.md)
- [can-trim-state.md](can-trim-state.md)
- [component-belongs-to-record.md](component-belongs-to-record.md)
- [component.md](component.md)
- [date-time-state-cast.md](date-time-state-cast.md)
- [embedded-schema.md](embedded-schema.md)
- [entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md)
- [enum-array-state-cast.md](enum-array-state-cast.md)
- [enum-state-cast.md](enum-state-cast.md)
- [fieldset.md](fieldset.md)
- [file-upload-state-cast.md](file-upload-state-cast.md)
- [flex.md](flex.md)
- [get.md](get.md)
- [grid.md](grid.md)
- [group.md](group.md)
- [has-actions.md](has-actions.md)
- [has-columns.md](has-columns.md)
- [has-gap.md](has-gap.md)
- [has-label.md](has-label.md)
- [has-name.md](has-name.md)
- [has-nullable-branch.md](has-nullable-branch.md)
- [has-operation.md](has-operation.md)
- [has-schemas.md](has-schemas.md)
- [has-state-binding-modifiers.md](has-state-binding-modifiers.md)
- [html.md](html.md)
- [icon.md](icon.md)
- [interacts-with-schemas.md](interacts-with-schemas.md)
- [live-component.md](live-component.md)
- [object-state-cast.md](object-state-cast.md)
- [option-state-cast.md](option-state-cast.md)
- [options-array-state-cast.md](options-array-state-cast.md)
- [presence-gate.md](presence-gate.md)
- [property-accessor.md](property-accessor.md)
- [record-key-contract.md](record-key-contract.md)
- [record-key.md](record-key.md)
- [record-type-reader.md](record-type-reader.md)
- [relationship.md](relationship.md)
- [schema-extension.md](schema-extension.md)
- [schema.md](schema.md)
- [schemas-bundle.md](schemas-bundle.md)
- [search-constraint.md](search-constraint.md)
- [section.md](section.md)
- [set.md](set.md)
- [sort-directions.md](sort-directions.md)
- [state-cast.md](state-cast.md)
- [step.md](step.md)
- [strip-characters-state-cast.md](strip-characters-state-cast.md)
- [styles.md](styles.md)
- [tab.md](tab.md)
- [tabs.md](tabs.md)
- [text.md](text.md)
- [violation-path-translator.md](violation-path-translator.md)
- [wizard.md](wizard.md)
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`Components/Callout.php`](../../../vendor/filament/schemas/src/Components/Callout.php) | todo | — | — |
| [`Components/EmbeddedTable.php`](../../../vendor/filament/schemas/src/Components/EmbeddedTable.php) | todo | — | — |
| [`Components/EmptyState.php`](../../../vendor/filament/schemas/src/Components/EmptyState.php) | todo | — | — |
| [`Components/Form.php`](../../../vendor/filament/schemas/src/Components/Form.php) | todo | — | — |
| [`Components/Image.php`](../../../vendor/filament/schemas/src/Components/Image.php) | todo | — | — |
| [`Components/RenderHook.php`](../../../vendor/filament/schemas/src/Components/RenderHook.php) | todo | — | — |
| [`Components/UnorderedList.php`](../../../vendor/filament/schemas/src/Components/UnorderedList.php) | todo | — | — |
| [`Components/View.php`](../../../vendor/filament/schemas/src/Components/View.php) | todo | — | — |
| [`Components/Concerns/CanBeGridContainer.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeGridContainer.php) | todo | — | — |
| [`Components/Concerns/CanBeLiberatedFromContainerGrid.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeLiberatedFromContainerGrid.php) | todo | — | — |
| [`Components/Concerns/CanGetStateFromRelationships.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanGetStateFromRelationships.php) | todo | — | — |
| [`Components/Concerns/CanOpenUrl.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanOpenUrl.php) | todo | — | — |
| [`Components/Concerns/CanPartiallyRender.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanPartiallyRender.php) | todo | — | — |
| [`Components/Concerns/CanPoll.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanPoll.php) | todo | — | — |
| [`Components/Concerns/HasEntryWrapper.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasEntryWrapper.php) | todo | — | — |
| [`Components/Concerns/HasFieldWrapper.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasFieldWrapper.php) | todo | — | [forms twin](../forms/fused-group.md#the-component-tier-hasfieldwrapper-twin-is-not-ported) |
| [`Components/Concerns/HasInlineLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasInlineLabel.php) | todo | — | [forms' isInline() guard](../forms/can-be-inline.md#isinline-drops-the-hasinlinelabel-guard) |
| [`Components/Concerns/HasMaxWidth.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasMaxWidth.php) | todo | — | [panels' twin](../panels/simple-page.md) |
| [`Components/Concerns/HasMeta.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasMeta.php) | todo | — | — |
| [`Concerns/CanBeHidden.php`](../../../vendor/filament/schemas/src/Concerns/CanBeHidden.php) | todo | — | — |
| [`Concerns/HasEntryWrapper.php`](../../../vendor/filament/schemas/src/Concerns/HasEntryWrapper.php) | todo | — | — |
| [`Concerns/HasInlineLabels.php`](../../../vendor/filament/schemas/src/Concerns/HasInlineLabels.php) | todo | — | — |
| [`Concerns/ResolvesDynamicLivewireProperties.php`](../../../vendor/filament/schemas/src/Concerns/ResolvesDynamicLivewireProperties.php) | skipped | — | [diverges](has-schemas.md#dynamic-livewire-properties-are-not-ported-because-they-hide-from-phpstan) |
| [`Components/StateCasts/NumberStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/NumberStateCast.php) | todo | — | — |
| [`Components/StateCasts/SliderStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/SliderStateCast.php) | todo | — | — |
| [`Components/Contracts/ExposesStateToActionData.php`](../../../vendor/filament/schemas/src/Components/Contracts/ExposesStateToActionData.php) | todo | — | — |
| [`Contracts/HasRenderHookScopes.php`](../../../vendor/filament/schemas/src/Contracts/HasRenderHookScopes.php) | todo | — | — |
| [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |
| [`Commands/`](../../../vendor/filament/schemas/src/Commands) | todo | — | — |
| [`stubs/`](../../../vendor/filament/schemas/stubs) | todo | — | — |
| [`resources/lang/`](../../../vendor/filament/schemas/resources/lang) | todo | — | — |
