# Support — port trail

Ports [`filament/support`](../../../vendor/filament/support) into [`packages/support`](../../../packages/support): the foundation every other bundle builds on, from the base component model and the shared UI components to colours, icons, assets and helpers.

## Files

<!-- trail-index:start -->
- [array-record.md](array-record.md)
- [badge.md](badge.md)
- [breadcrumbs.md](breadcrumbs.md)
- [button.md](button.md)
- [can-be-contained.md](can-be-contained.md)
- [can-be-copied.md](can-be-copied.md)
- [can-order-columns.md](can-order-columns.md)
- [can-span-columns.md](can-span-columns.md)
- [can-wrap.md](can-wrap.md)
- [cancel.md](cancel.md)
- [color-manager.md](color-manager.md)
- [color-policies.md](color-policies.md)
- [color.md](color.md)
- [component-attribute-bag.md](component-attribute-bag.md)
- [component-manager.md](component-manager.md)
- [component.md](component.md)
- [contracts.md](contracts.md)
- [copyable.md](copyable.md)
- [dropdown-item.md](dropdown-item.md)
- [dropdown.md](dropdown.md)
- [enums.md](enums.md)
- [exposed-live-component-method.md](exposed-live-component-method.md)
- [fieldset.md](fieldset.md)
- [filesystem-registry.md](filesystem-registry.md)
- [halt.md](halt.md)
- [has-alignment.md](has-alignment.md)
- [has-color.md](has-color.md)
- [has-default-data-formatting-settings.md](has-default-data-formatting-settings.md)
- [has-extra-attributes.md](has-extra-attributes.md)
- [has-font-family.md](has-font-family.md)
- [has-icon-color.md](has-icon-color.md)
- [has-icon-size.md](has-icon-size.md)
- [has-icon.md](has-icon.md)
- [has-placeholder.md](has-placeholder.md)
- [has-tooltip.md](has-tooltip.md)
- [has-vertical-alignment.md](has-vertical-alignment.md)
- [has-weight.md](has-weight.md)
- [helpers.md](helpers.md)
- [icon-button.md](icon-button.md)
- [icon-manager.md](icon-manager.md)
- [icon-size.md](icon-size.md)
- [icon.md](icon.md)
- [input-wrapper.md](input-wrapper.md)
- [link.md](link.md)
- [loading-indicator.md](loading-indicator.md)
- [modal.md](modal.md)
- [pagination.md](pagination.md)
- [section.md](section.md)
- [sortable.md](sortable.md)
- [support-bundle.md](support-bundle.md)
- [support-icon-alias.md](support-icon-alias.md)
- [tabs.md](tabs.md)
- [vertical-alignment.md](vertical-alignment.md)
- [view-component.md](view-component.md)
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`views/components/actions.blade.php`](../../../vendor/filament/support/resources/views/components/actions.blade.php) | todo | — | — |
| [`views/components/avatar.blade.php`](../../../vendor/filament/support/resources/views/components/avatar.blade.php) | todo | — | — |
| [`resources/css/components/avatar.css`](../../../vendor/filament/support/resources/css/components/avatar.css) | todo | — | — |
| [`views/components/callout.blade.php`](../../../vendor/filament/support/resources/views/components/callout.blade.php) | todo | — | — |
| [`resources/css/components/callout.css`](../../../vendor/filament/support/resources/css/components/callout.css) | todo | — | — |
| [`views/components/card.blade.php`](../../../vendor/filament/support/resources/views/components/card.blade.php) | todo | — | — |
| [`views/components/input/checkbox.blade.php`](../../../vendor/filament/support/resources/views/components/input/checkbox.blade.php) | todo | — | [checkbox skin](../forms/styles.md#the-checkbox-skin-lives-in-support-because-tables-renders-it-too) |
| [`views/components/empty-state.blade.php`](../../../vendor/filament/support/resources/views/components/empty-state.blade.php) | todo | — | — |
| [`resources/css/components/empty-state.css`](../../../vendor/filament/support/resources/css/components/empty-state.css) | todo | — | — |
| [`views/components/input/index.blade.php`](../../../vendor/filament/support/resources/views/components/input/index.blade.php) | todo | — | — |
| [`views/components/loading-section.blade.php`](../../../vendor/filament/support/resources/views/components/loading-section.blade.php) | todo | — | — |
| [`resources/css/components/loading-section.css`](../../../vendor/filament/support/resources/css/components/loading-section.css) | todo | — | — |
| [`views/components/input/one-time-code.blade.php`](../../../vendor/filament/support/resources/views/components/input/one-time-code.blade.php) | todo | — | — |
| [`resources/js/components/one-time-code.js`](../../../vendor/filament/support/resources/js/components/one-time-code.js) | todo | — | — |
| [`views/components/input/radio.blade.php`](../../../vendor/filament/support/resources/views/components/input/radio.blade.php) | todo | — | — |
| [`views/components/input/select.blade.php`](../../../vendor/filament/support/resources/views/components/input/select.blade.php) | skipped | — | [input-wrapper.md](input-wrapper.md#support-ships-no-select-input-component-because-forms-select-emits-filaments-pair-inside-this-wrapper) |
| [`views/components/toggle.blade.php`](../../../vendor/filament/support/resources/views/components/toggle.blade.php) | todo | — | — |
| [`views/components/button/group.blade.php`](../../../vendor/filament/support/resources/views/components/button/group.blade.php) | todo | — | — |
| [`resources/js/components/tooltip.js`](../../../vendor/filament/support/resources/js/components/tooltip.js) | todo | — | — |
| [`resources/css/components/tooltip.css`](../../../vendor/filament/support/resources/css/components/tooltip.css) | todo | — | — |
| [`resources/js/components/form-button.js`](../../../vendor/filament/support/resources/js/components/form-button.js) | todo | — | — |
| [`Concerns/CanBeLazy.php`](../../../vendor/filament/support/src/Concerns/CanBeLazy.php) | todo | — | — |
| [`Enums/Platform.php`](../../../vendor/filament/support/src/Enums/Platform.php) | todo | — | — |
| [`Contracts/Collapsible.php`](../../../vendor/filament/support/src/Contracts/Collapsible.php) | todo | — | — |
| [`Contracts/TranslatableContentDriver.php`](../../../vendor/filament/support/src/Contracts/TranslatableContentDriver.php) | todo | — | — |
| [`Icons/Heroicon.php`](../../../vendor/filament/support/src/Icons/Heroicon.php) | todo | — | — |
| [`Assets/Asset.php`](../../../vendor/filament/support/src/Assets/Asset.php) | todo | — | — |
| [`Assets/AlpineComponent.php`](../../../vendor/filament/support/src/Assets/AlpineComponent.php) | todo | — | — |
| [`Assets/Css.php`](../../../vendor/filament/support/src/Assets/Css.php) | todo | — | — |
| [`Assets/Font.php`](../../../vendor/filament/support/src/Assets/Font.php) | todo | — | — |
| [`Assets/Js.php`](../../../vendor/filament/support/src/Assets/Js.php) | todo | — | — |
| [`Assets/Theme.php`](../../../vendor/filament/support/src/Assets/Theme.php) | todo | — | — |
| [`views/assets.blade.php`](../../../vendor/filament/support/resources/views/assets.blade.php) | todo | — | — |
| [`View/ViewManager.php`](../../../vendor/filament/support/src/View/ViewManager.php) | todo | — | — |
| [`View/Concerns/`](../../../vendor/filament/support/src/View/Concerns) | todo | — | — |
| [`views/anonymous-partial.blade.php`](../../../vendor/filament/support/resources/views/anonymous-partial.blade.php) | todo | — | — |
| [`resources/js/partials.js`](../../../vendor/filament/support/resources/js/partials.js) | todo | — | — |
| [`resources/js/utilities/pluralize.js`](../../../vendor/filament/support/resources/js/utilities/pluralize.js) | todo | — | — |
| [`Events/FilamentUpgraded.php`](../../../vendor/filament/support/src/Events/FilamentUpgraded.php) | todo | — | — |
| [`Facades/`](../../../vendor/filament/support/src/Facades) | todo | — | — |
| [`Commands/`](../../../vendor/filament/support/src/Commands) | todo | — | — |
| [`Livewire/Partials/`](../../../vendor/filament/support/src/Livewire/Partials) | todo | — | — |
| [`Services/RelationshipJoiner.php`](../../../vendor/filament/support/src/Services/RelationshipJoiner.php) | todo | — | — |
| [`Services/RelationshipOrderer.php`](../../../vendor/filament/support/src/Services/RelationshipOrderer.php) | todo | — | — |
| [`Authorization/DenyResponse.php`](../../../vendor/filament/support/src/Authorization/DenyResponse.php) | todo | — | — |
| [`ChunkIterator.php`](../../../vendor/filament/support/src/ChunkIterator.php) | todo | — | — |
| [`CliManager.php`](../../../vendor/filament/support/src/CliManager.php) | todo | — | — |
| [`Markdown.php`](../../../vendor/filament/support/src/Markdown.php) | todo | — | — |
| [`RawJs.php`](../../../vendor/filament/support/src/RawJs.php) | todo | — | — |
| [`TimezoneManager.php`](../../../vendor/filament/support/src/TimezoneManager.php) | todo | — | — |
| [`resources/lang/`](../../../vendor/filament/support/resources/lang) | todo | — | — |
