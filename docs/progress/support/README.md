# Support — port progress

Ports [`filament/support`](../../../vendor/filament/support) into
[`packages/support`](../../../packages/support). Foundation for every other bundle:
the base component model, shared UI components, colors, icons, assets, helpers.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `support/src` surface end to end. A linked name in the
first column is code that already exists in the port; the per-component audit
against Filament has now resolved every one to a real
[status](../README.md#statuses) — **done** or **in progress** — linking a detail
file wherever a divergence, an open gap, or real sub-structure earns one. A
plain-text name is not built yet and carries **Status** `todo`. A row named for
a directory (`Commands`, `Facades`, `Translations`, `ColorMaps`, …) collapses a
whole subtree into one navigational entry — a cluster we expect to port, if at
all, as a unit rather than file by file.

## Components

The shared UI kit — the component model in
[`src/Components`](../../../vendor/filament/support/src/Components) and the rendered
widgets in [`src/View/Components`](../../../vendor/filament/support/src/View/Components)
+ [`resources/views/components`](../../../vendor/filament/support/resources/views/components).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Component`](../../../packages/support/src/Components/Component.php) | [`Components/Component.php`](../../../vendor/filament/support/src/Components/Component.php) | in progress | [component.md](component.md) |
| [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) | [`Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | [view-component.md](view-component.md) |
| [`ComponentManager`](../../../packages/support/src/Configuration/ComponentManager.php) | [`Components/ComponentManager.php`](../../../vendor/filament/support/src/Components/ComponentManager.php) | in progress | [component-manager.md](component-manager.md) |
| Actions | [`views/components/actions.blade.php`](../../../vendor/filament/support/resources/views/components/actions.blade.php) | todo | — |
| Avatar | [`views/components/avatar.blade.php`](../../../vendor/filament/support/resources/views/components/avatar.blade.php) | todo | — |
| [`Badge`](../../../packages/support/templates/components/Badge/index.html.twig) | [`View/Components/BadgeComponent.php`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | in progress | [badge.md](badge.md) |
| Breadcrumbs | [`views/components/breadcrumbs.blade.php`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | todo | — |
| [`Button`](../../../packages/support/templates/components/Button/index.html.twig) | [`View/Components/ButtonComponent.php`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php) | in progress | [button.md](button.md) |
| Callout | [`View/Components/CalloutComponent.php`](../../../vendor/filament/support/src/View/Components/CalloutComponent.php) | todo | — |
| Card | [`views/components/card.blade.php`](../../../vendor/filament/support/resources/views/components/card.blade.php) | todo | — |
| Checkbox | [`views/components/input/checkbox.blade.php`](../../../vendor/filament/support/resources/views/components/input/checkbox.blade.php) | todo | — |
| [`Dropdown`](../../../packages/support/templates/components/Dropdown/index.html.twig) | [`views/components/dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) | in progress | [dropdown.md](dropdown.md) |
| [`Dropdown item`](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`views/components/dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | in progress | [dropdown-item.md](dropdown-item.md) |
| Empty state | [`views/components/empty-state.blade.php`](../../../vendor/filament/support/resources/views/components/empty-state.blade.php) | todo | — |
| [`Fieldset`](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`views/components/fieldset.blade.php`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — |
| [`Icon`](../../../packages/support/templates/components/Icon/index.html.twig) | [`views/components/icon.blade.php`](../../../vendor/filament/support/resources/views/components/icon.blade.php) | done | — |
| [`Icon button`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`View/Components/IconButtonComponent.php`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php) | in progress | [icon-button.md](icon-button.md) |
| Input | [`views/components/input/index.blade.php`](../../../vendor/filament/support/resources/views/components/input/index.blade.php) | todo | — |
| [`Link`](../../../packages/support/templates/components/Link/index.html.twig) | [`View/Components/LinkComponent.php`](../../../vendor/filament/support/src/View/Components/LinkComponent.php) | in progress | [link.md](link.md) |
| Loading indicator | [`views/components/loading-indicator.blade.php`](../../../vendor/filament/support/resources/views/components/loading-indicator.blade.php) | todo | — |
| Loading section | [`views/components/loading-section.blade.php`](../../../vendor/filament/support/resources/views/components/loading-section.blade.php) | todo | — |
| [`Modal`](../../../packages/support/templates/components/Modal/index.html.twig) | [`View/Components/ModalComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent.php) | in progress | [modal.md](modal.md) |
| One-time code | [`views/components/input/one-time-code.blade.php`](../../../vendor/filament/support/resources/views/components/input/one-time-code.blade.php) | todo | — |
| Pagination | [`views/components/pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | todo | — |
| Radio | [`views/components/input/radio.blade.php`](../../../vendor/filament/support/resources/views/components/input/radio.blade.php) | todo | — |
| [`Section`](../../../packages/support/templates/components/Section/index.html.twig) | [`views/components/section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | in progress | [section.md](section.md) |
| Select | [`views/components/input/select.blade.php`](../../../vendor/filament/support/resources/views/components/input/select.blade.php) | todo | — |
| Tabs | [`views/components/tabs/index.blade.php`](../../../vendor/filament/support/resources/views/components/tabs/index.blade.php) | todo | — |
| Toggle | [`View/Components/ToggleComponent.php`](../../../vendor/filament/support/src/View/Components/ToggleComponent.php) | todo | — |

## Features

Cross-cutting concerns and traits —
[`src/Concerns`](../../../vendor/filament/support/src/Concerns). The base
`Component`'s own traits (`Configurable`, `EvaluatesClosures`, `Macroable`, and the
Laravel `Conditionable`/`Tappable`) are tracked in [`component.md`](component.md),
not here.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| CanAggregateRelatedModels | [`Concerns/CanAggregateRelatedModels.php`](../../../vendor/filament/support/src/Concerns/CanAggregateRelatedModels.php) | todo | — |
| [`CanBeContained`](../../../packages/support/src/Components/Concerns/CanBeContained.php) | [`Concerns/CanBeContained.php`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | done | — |
| CanBeCopied | [`Concerns/CanBeCopied.php`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | todo | — |
| CanBeLazy | [`Concerns/CanBeLazy.php`](../../../vendor/filament/support/src/Concerns/CanBeLazy.php) | todo | — |
| CanConfigureCommonMark | [`Concerns/CanConfigureCommonMark.php`](../../../vendor/filament/support/src/Concerns/CanConfigureCommonMark.php) | todo | — |
| [`CanGrow`](../../../packages/support/src/Components/Concerns/CanGrow.php) | [`Concerns/CanGrow.php`](../../../vendor/filament/support/src/Concerns/CanGrow.php) | done | — |
| [`CanOrderColumns`](../../../packages/support/src/Components/Concerns/CanOrderColumns.php) | [`Concerns/CanOrderColumns.php`](../../../vendor/filament/support/src/Concerns/CanOrderColumns.php) | done | — |
| [`CanSpanColumns`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`Concerns/CanSpanColumns.php`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | [can-span-columns.md](can-span-columns.md) |
| CanWrap | [`Concerns/CanWrap.php`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | todo | — |
| CanWrapHeader | [`Concerns/CanWrapHeader.php`](../../../vendor/filament/support/src/Concerns/CanWrapHeader.php) | todo | — |
| [`HasAlignment`](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`Concerns/HasAlignment.php`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | [has-alignment.md](has-alignment.md) |
| HasBadge | [`Concerns/HasBadge.php`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | todo | — |
| HasBadgeTooltip | [`Concerns/HasBadgeTooltip.php`](../../../vendor/filament/support/src/Concerns/HasBadgeTooltip.php) | todo | — |
| HasCellState | [`Concerns/HasCellState.php`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — |
| HasDefaultDataFormattingSettings | [`Concerns/HasDefaultDataFormattingSettings.php`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | todo | — |
| HasExtraAlpineAttributes | [`Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — |
| [`HasFontFamily`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`Concerns/HasFontFamily.php`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | [has-font-family.md](has-font-family.md) |
| HasFromBreakpoint | [`Concerns/HasFromBreakpoint.php`](../../../vendor/filament/support/src/Concerns/HasFromBreakpoint.php) | todo | — |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | [has-icon.md](has-icon.md) |
| [`HasIconColor`](../../../packages/support/src/Components/Concerns/HasIconColor.php) | [`Concerns/HasIconColor.php`](../../../vendor/filament/support/src/Concerns/HasIconColor.php) | done | — |
| [`HasIconPosition`](../../../packages/support/src/Components/Concerns/HasIconPosition.php) | [`Concerns/HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | done | — |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — |
| HasLineClamp | [`Concerns/HasLineClamp.php`](../../../vendor/filament/support/src/Concerns/HasLineClamp.php) | todo | — |
| HasPlaceholder | [`Concerns/HasPlaceholder.php`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | todo | — |
| HasReorderAnimationDuration | [`Concerns/HasReorderAnimationDuration.php`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | todo | — |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — |
| HasVerticalAlignment | [`Concerns/HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | todo | — |
| [`HasWeight`](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`Concerns/HasWeight.php`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | [has-weight.md](has-weight.md) |
| HasWidth | [`Concerns/HasWidth.php`](../../../vendor/filament/support/src/Concerns/HasWidth.php) | todo | — |

## Supporting surface

Everything with no user-facing UI class of its own: enums, contracts, colors, icons,
assets, facades, the view layer, exceptions, events, CLI commands, Eloquent query
services, root value classes, translations, and the bundle wiring —
[`src/Enums`](../../../vendor/filament/support/src/Enums),
[`src/Contracts`](../../../vendor/filament/support/src/Contracts),
[`src/Colors`](../../../vendor/filament/support/src/Colors),
[`src/Icons`](../../../vendor/filament/support/src/Icons),
[`src/Assets`](../../../vendor/filament/support/src/Assets),
[`src/View`](../../../vendor/filament/support/src/View),
[`src/Commands`](../../../vendor/filament/support/src/Commands).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Alignment`](../../../packages/support/src/Enums/Alignment.php) | [`Enums/Alignment.php`](../../../vendor/filament/support/src/Enums/Alignment.php) | done | — |
| ArgumentValue | [`Enums/ArgumentValue.php`](../../../vendor/filament/support/src/Enums/ArgumentValue.php) | todo | — |
| [`FontFamily`](../../../packages/support/src/Enums/FontFamily.php) | [`Enums/FontFamily.php`](../../../vendor/filament/support/src/Enums/FontFamily.php) | done | — |
| [`FontWeight`](../../../packages/support/src/Enums/FontWeight.php) | [`Enums/FontWeight.php`](../../../vendor/filament/support/src/Enums/FontWeight.php) | done | — |
| [`GridDirection`](../../../packages/support/src/Enums/GridDirection.php) | [`Enums/GridDirection.php`](../../../vendor/filament/support/src/Enums/GridDirection.php) | done | — |
| [`IconPosition`](../../../packages/support/src/Enums/IconPosition.php) | [`Enums/IconPosition.php`](../../../vendor/filament/support/src/Enums/IconPosition.php) | done | — |
| [`IconSize`](../../../packages/support/src/Enums/IconSize.php) | [`Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php) | done | [icon-size.md](icon-size.md) |
| [`Operation`](../../../packages/support/src/Enums/Operation.php) | [`Enums/Operation.php`](../../../vendor/filament/support/src/Enums/Operation.php) | done | — |
| Platform | [`Enums/Platform.php`](../../../vendor/filament/support/src/Enums/Platform.php) | todo | — |
| [`Size`](../../../packages/support/src/Enums/Size.php) | [`Enums/Size.php`](../../../vendor/filament/support/src/Enums/Size.php) | done | — |
| [`SlideOverPosition`](../../../packages/support/src/Enums/SlideOverPosition.php) | [`Enums/SlideOverPosition.php`](../../../vendor/filament/support/src/Enums/SlideOverPosition.php) | done | — |
| [`TextSize`](../../../packages/support/src/Enums/TextSize.php) | [`Enums/TextSize.php`](../../../vendor/filament/support/src/Enums/TextSize.php) | done | — |
| VerticalAlignment | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | todo | — |
| Width | [`Enums/Width.php`](../../../vendor/filament/support/src/Enums/Width.php) | todo | — |
| Collapsible | [`Contracts/Collapsible.php`](../../../vendor/filament/support/src/Contracts/Collapsible.php) | todo | — |
| HasColor (contract) | [`Contracts/HasColor.php`](../../../vendor/filament/support/src/Contracts/HasColor.php) | todo | — |
| HasDescription | [`Contracts/HasDescription.php`](../../../vendor/filament/support/src/Contracts/HasDescription.php) | todo | — |
| HasIcon (contract) | [`Contracts/HasIcon.php`](../../../vendor/filament/support/src/Contracts/HasIcon.php) | todo | — |
| HasLabel | [`Contracts/HasLabel.php`](../../../vendor/filament/support/src/Contracts/HasLabel.php) | todo | — |
| LoadingIndicator | [`Contracts/LoadingIndicator.php`](../../../vendor/filament/support/src/Contracts/LoadingIndicator.php) | todo | — |
| [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) | [`Contracts/ScalableIcon.php`](../../../vendor/filament/support/src/Contracts/ScalableIcon.php) | done | — |
| TranslatableContentDriver | [`Contracts/TranslatableContentDriver.php`](../../../vendor/filament/support/src/Contracts/TranslatableContentDriver.php) | todo | — |
| HasEmbeddedView | [`Components/Contracts/HasEmbeddedView.php`](../../../vendor/filament/support/src/Components/Contracts/HasEmbeddedView.php) | todo | — |
| ScopedComponentManager | [`Components/Contracts/ScopedComponentManager.php`](../../../vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php) | todo | — |
| ExposedLivewireMethod | [`Components/Attributes/ExposedLivewireMethod.php`](../../../vendor/filament/support/src/Components/Attributes/ExposedLivewireMethod.php) | todo | — |
| Color | [`Colors/Color.php`](../../../vendor/filament/support/src/Colors/Color.php) | todo | — |
| ColorManager | [`Colors/ColorManager.php`](../../../vendor/filament/support/src/Colors/ColorManager.php) | todo | — |
| Heroicon | [`Icons/Heroicon.php`](../../../vendor/filament/support/src/Icons/Heroicon.php) | todo | — |
| [`IconManager`](../../../packages/support/src/Icons/IconManager.php) | [`Icons/IconManager.php`](../../../vendor/filament/support/src/Icons/IconManager.php) | done | [icon-manager.md](icon-manager.md) |
| Asset | [`Assets/Asset.php`](../../../vendor/filament/support/src/Assets/Asset.php) | todo | — |
| AlpineComponent | [`Assets/AlpineComponent.php`](../../../vendor/filament/support/src/Assets/AlpineComponent.php) | todo | — |
| Css | [`Assets/Css.php`](../../../vendor/filament/support/src/Assets/Css.php) | todo | — |
| Font | [`Assets/Font.php`](../../../vendor/filament/support/src/Assets/Font.php) | todo | — |
| Js | [`Assets/Js.php`](../../../vendor/filament/support/src/Assets/Js.php) | todo | — |
| Theme | [`Assets/Theme.php`](../../../vendor/filament/support/src/Assets/Theme.php) | todo | — |
| AssetManager | [`Assets/AssetManager.php`](../../../vendor/filament/support/src/Assets/AssetManager.php) | todo | — |
| [`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php) | [`View/ComponentAttributeBag.php`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | in progress | [component-attribute-bag.md](component-attribute-bag.md) |
| ViewManager | [`View/ViewManager.php`](../../../vendor/filament/support/src/View/ViewManager.php) | todo | — |
| [`SupportIconAlias`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`View/SupportIconAlias.php`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | in progress | [support-icon-alias.md](support-icon-alias.md) |
| DefaultLoadingIndicator | [`View/DefaultLoadingIndicator.php`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php) | todo | — |
| ColorMaps | [`View/Components/ColorMaps/`](../../../vendor/filament/support/src/View/Components/ColorMaps) | todo | — |
| Color contracts | [`View/Components/Contracts/`](../../../vendor/filament/support/src/View/Components/Contracts) | todo | — |
| HTML generators | [`View/Concerns/`](../../../vendor/filament/support/src/View/Concerns) | todo | — |
| [`Cancel`](../../../packages/support/src/Exceptions/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | in progress | [cancel.md](cancel.md) |
| [`Halt`](../../../packages/support/src/Exceptions/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | in progress | [halt.md](halt.md) |
| FilamentUpgraded | [`Events/FilamentUpgraded.php`](../../../vendor/filament/support/src/Events/FilamentUpgraded.php) | todo | — |
| Facades | [`Facades/`](../../../vendor/filament/support/src/Facades) | todo | — |
| Commands | [`Commands/`](../../../vendor/filament/support/src/Commands) | todo | — |
| Commands / FileGenerators | [`Commands/FileGenerators/`](../../../vendor/filament/support/src/Commands/FileGenerators) | todo | — |
| Commands / Concerns | [`Commands/Concerns/`](../../../vendor/filament/support/src/Commands/Concerns) | todo | — |
| Livewire partials | [`Livewire/Partials/`](../../../vendor/filament/support/src/Livewire/Partials) | todo | — |
| RelationshipJoiner | [`Services/RelationshipJoiner.php`](../../../vendor/filament/support/src/Services/RelationshipJoiner.php) | todo | — |
| RelationshipOrderer | [`Services/RelationshipOrderer.php`](../../../vendor/filament/support/src/Services/RelationshipOrderer.php) | todo | — |
| DenyResponse | [`Authorization/DenyResponse.php`](../../../vendor/filament/support/src/Authorization/DenyResponse.php) | todo | — |
| ArrayRecord | [`ArrayRecord.php`](../../../vendor/filament/support/src/ArrayRecord.php) | todo | — |
| ChunkIterator | [`ChunkIterator.php`](../../../vendor/filament/support/src/ChunkIterator.php) | todo | — |
| CliManager | [`CliManager.php`](../../../vendor/filament/support/src/CliManager.php) | todo | — |
| Markdown | [`Markdown.php`](../../../vendor/filament/support/src/Markdown.php) | todo | — |
| RawJs | [`RawJs.php`](../../../vendor/filament/support/src/RawJs.php) | todo | — |
| TimezoneManager | [`TimezoneManager.php`](../../../vendor/filament/support/src/TimezoneManager.php) | todo | — |
| [`SupportServiceProvider`](../../../packages/support/src/SylamentSupportBundle.php) | [`SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) | in progress | [support-bundle.md](support-bundle.md) |
| [`helpers.php`](../../../packages/support/src/helpers.php) | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | in progress | [helpers.md](helpers.md) |
| Translations | [`resources/lang/`](../../../vendor/filament/support/resources/lang) | todo | — |
