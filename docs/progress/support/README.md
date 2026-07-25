# Support — port progress

Ports [`filament/support`](../../../vendor/filament/support) into
[`packages/support`](../../../packages/support). Foundation for every other bundle:
the base component model, shared UI components, colors, icons, assets, helpers.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `support/src` surface end to end. A linked name in the
first column is code that already exists in the port; its **Status** is `—` — built
but not yet audited against Filament, which happens in a later per-component pass
([`Component`](component.md) is the one done so far). A plain-text name is not built
yet and carries **Status** `todo`. A row named for a directory (`Commands`, `Facades`,
`Translations`, `ColorMaps`, …) collapses a whole subtree into one navigational entry —
a cluster we expect to port, if at all, as a unit rather than file by file.

## Components

The shared UI kit — the component model in
[`src/Components`](../../../vendor/filament/support/src/Components) and the rendered
widgets in [`src/View/Components`](../../../vendor/filament/support/src/View/Components)
+ [`resources/views/components`](../../../vendor/filament/support/resources/views/components).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Component`](../../../packages/support/src/Component/Component.php) | [`Components/Component.php`](../../../vendor/filament/support/src/Components/Component.php) | done | [component.md](component.md) |
| [`ViewComponent`](../../../packages/support/src/Component/ViewComponent.php) | [`Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | — | — |
| [`ComponentManager`](../../../packages/support/src/Component/ComponentManager.php) | [`Components/ComponentManager.php`](../../../vendor/filament/support/src/Components/ComponentManager.php) | — | — |
| Actions | [`views/components/actions.blade.php`](../../../vendor/filament/support/resources/views/components/actions.blade.php) | todo | — |
| Avatar | [`views/components/avatar.blade.php`](../../../vendor/filament/support/resources/views/components/avatar.blade.php) | todo | — |
| Badge | [`View/Components/BadgeComponent.php`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | todo | — |
| Breadcrumbs | [`views/components/breadcrumbs.blade.php`](../../../vendor/filament/support/resources/views/components/breadcrumbs.blade.php) | todo | — |
| [`Button`](../../../packages/support/templates/components/Button/index.html.twig) | [`View/Components/ButtonComponent.php`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php) | — | — |
| Callout | [`View/Components/CalloutComponent.php`](../../../vendor/filament/support/src/View/Components/CalloutComponent.php) | todo | — |
| Card | [`views/components/card.blade.php`](../../../vendor/filament/support/resources/views/components/card.blade.php) | todo | — |
| Checkbox | [`views/components/input/checkbox.blade.php`](../../../vendor/filament/support/resources/views/components/input/checkbox.blade.php) | todo | — |
| Dropdown | [`views/components/dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) | todo | — |
| Empty state | [`views/components/empty-state.blade.php`](../../../vendor/filament/support/resources/views/components/empty-state.blade.php) | todo | — |
| Fieldset | [`views/components/fieldset.blade.php`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | todo | — |
| [`Icon`](../../../packages/support/templates/components/Icon/index.html.twig) | [`views/components/icon.blade.php`](../../../vendor/filament/support/resources/views/components/icon.blade.php) | — | — |
| [`Icon button`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`View/Components/IconButtonComponent.php`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php) | — | — |
| Input | [`views/components/input/index.blade.php`](../../../vendor/filament/support/resources/views/components/input/index.blade.php) | todo | — |
| Link | [`View/Components/LinkComponent.php`](../../../vendor/filament/support/src/View/Components/LinkComponent.php) | todo | — |
| Loading indicator | [`views/components/loading-indicator.blade.php`](../../../vendor/filament/support/resources/views/components/loading-indicator.blade.php) | todo | — |
| Loading section | [`views/components/loading-section.blade.php`](../../../vendor/filament/support/resources/views/components/loading-section.blade.php) | todo | — |
| [`Modal`](../../../packages/support/templates/components/Modal/index.html.twig) | [`View/Components/ModalComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent.php) | — | — |
| One-time code | [`views/components/input/one-time-code.blade.php`](../../../vendor/filament/support/resources/views/components/input/one-time-code.blade.php) | todo | — |
| Pagination | [`views/components/pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | todo | — |
| Radio | [`views/components/input/radio.blade.php`](../../../vendor/filament/support/resources/views/components/input/radio.blade.php) | todo | — |
| [`Section`](../../../packages/support/templates/components/Section/index.html.twig) | [`views/components/section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | — | — |
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
| [`CanBeContained`](../../../packages/support/src/Component/Concerns/CanBeContained.php) | [`Concerns/CanBeContained.php`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | — | — |
| CanBeCopied | [`Concerns/CanBeCopied.php`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | todo | — |
| CanBeLazy | [`Concerns/CanBeLazy.php`](../../../vendor/filament/support/src/Concerns/CanBeLazy.php) | todo | — |
| CanConfigureCommonMark | [`Concerns/CanConfigureCommonMark.php`](../../../vendor/filament/support/src/Concerns/CanConfigureCommonMark.php) | todo | — |
| [`CanGrow`](../../../packages/support/src/Component/Concerns/CanGrow.php) | [`Concerns/CanGrow.php`](../../../vendor/filament/support/src/Concerns/CanGrow.php) | — | — |
| [`CanOrderColumns`](../../../packages/support/src/Component/Concerns/CanOrderColumns.php) | [`Concerns/CanOrderColumns.php`](../../../vendor/filament/support/src/Concerns/CanOrderColumns.php) | — | — |
| [`CanSpanColumns`](../../../packages/support/src/Component/Concerns/CanSpanColumns.php) | [`Concerns/CanSpanColumns.php`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | — | — |
| CanWrap | [`Concerns/CanWrap.php`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | todo | — |
| CanWrapHeader | [`Concerns/CanWrapHeader.php`](../../../vendor/filament/support/src/Concerns/CanWrapHeader.php) | todo | — |
| [`HasAlignment`](../../../packages/support/src/Component/Concerns/HasAlignment.php) | [`Concerns/HasAlignment.php`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | — | — |
| HasBadge | [`Concerns/HasBadge.php`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | todo | — |
| HasBadgeTooltip | [`Concerns/HasBadgeTooltip.php`](../../../vendor/filament/support/src/Concerns/HasBadgeTooltip.php) | todo | — |
| HasCellState | [`Concerns/HasCellState.php`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — |
| [`HasColor`](../../../packages/support/src/Component/Concerns/HasColor.php) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | — | — |
| HasDefaultDataFormattingSettings | [`Concerns/HasDefaultDataFormattingSettings.php`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | todo | — |
| HasExtraAlpineAttributes | [`Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — |
| [`HasExtraAttributes`](../../../packages/support/src/Component/Concerns/HasExtraAttributes.php) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | — | — |
| [`HasFontFamily`](../../../packages/support/src/Component/Concerns/HasFontFamily.php) | [`Concerns/HasFontFamily.php`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | — | — |
| HasFromBreakpoint | [`Concerns/HasFromBreakpoint.php`](../../../vendor/filament/support/src/Concerns/HasFromBreakpoint.php) | todo | — |
| [`HasIcon`](../../../packages/support/src/Component/Concerns/HasIcon.php) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | — | — |
| [`HasIconColor`](../../../packages/support/src/Component/Concerns/HasIconColor.php) | [`Concerns/HasIconColor.php`](../../../vendor/filament/support/src/Concerns/HasIconColor.php) | — | — |
| [`HasIconPosition`](../../../packages/support/src/Component/Concerns/HasIconPosition.php) | [`Concerns/HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | — | — |
| [`HasIconSize`](../../../packages/support/src/Component/Concerns/HasIconSize.php) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | — | — |
| HasLineClamp | [`Concerns/HasLineClamp.php`](../../../vendor/filament/support/src/Concerns/HasLineClamp.php) | todo | — |
| HasPlaceholder | [`Concerns/HasPlaceholder.php`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | todo | — |
| HasReorderAnimationDuration | [`Concerns/HasReorderAnimationDuration.php`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | todo | — |
| [`HasTooltip`](../../../packages/support/src/Component/Concerns/HasTooltip.php) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | — | — |
| HasVerticalAlignment | [`Concerns/HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | todo | — |
| [`HasWeight`](../../../packages/support/src/Component/Concerns/HasWeight.php) | [`Concerns/HasWeight.php`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | — | — |
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
| [`Alignment`](../../../packages/support/src/Enum/Alignment.php) | [`Enums/Alignment.php`](../../../vendor/filament/support/src/Enums/Alignment.php) | — | — |
| ArgumentValue | [`Enums/ArgumentValue.php`](../../../vendor/filament/support/src/Enums/ArgumentValue.php) | todo | — |
| [`FontFamily`](../../../packages/support/src/Enum/FontFamily.php) | [`Enums/FontFamily.php`](../../../vendor/filament/support/src/Enums/FontFamily.php) | — | — |
| [`FontWeight`](../../../packages/support/src/Enum/FontWeight.php) | [`Enums/FontWeight.php`](../../../vendor/filament/support/src/Enums/FontWeight.php) | — | — |
| [`GridDirection`](../../../packages/support/src/Enum/GridDirection.php) | [`Enums/GridDirection.php`](../../../vendor/filament/support/src/Enums/GridDirection.php) | — | — |
| [`IconPosition`](../../../packages/support/src/Enum/IconPosition.php) | [`Enums/IconPosition.php`](../../../vendor/filament/support/src/Enums/IconPosition.php) | — | — |
| [`IconSize`](../../../packages/support/src/Enum/IconSize.php) | [`Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php) | — | — |
| Operation | [`Enums/Operation.php`](../../../vendor/filament/support/src/Enums/Operation.php) | todo | — |
| Platform | [`Enums/Platform.php`](../../../vendor/filament/support/src/Enums/Platform.php) | todo | — |
| [`Size`](../../../packages/support/src/Enum/Size.php) | [`Enums/Size.php`](../../../vendor/filament/support/src/Enums/Size.php) | — | — |
| SlideOverPosition | [`Enums/SlideOverPosition.php`](../../../vendor/filament/support/src/Enums/SlideOverPosition.php) | todo | — |
| [`TextSize`](../../../packages/support/src/Enum/TextSize.php) | [`Enums/TextSize.php`](../../../vendor/filament/support/src/Enums/TextSize.php) | — | — |
| VerticalAlignment | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | todo | — |
| Width | [`Enums/Width.php`](../../../vendor/filament/support/src/Enums/Width.php) | todo | — |
| Collapsible | [`Contracts/Collapsible.php`](../../../vendor/filament/support/src/Contracts/Collapsible.php) | todo | — |
| HasColor (contract) | [`Contracts/HasColor.php`](../../../vendor/filament/support/src/Contracts/HasColor.php) | todo | — |
| HasDescription | [`Contracts/HasDescription.php`](../../../vendor/filament/support/src/Contracts/HasDescription.php) | todo | — |
| HasIcon (contract) | [`Contracts/HasIcon.php`](../../../vendor/filament/support/src/Contracts/HasIcon.php) | todo | — |
| HasLabel | [`Contracts/HasLabel.php`](../../../vendor/filament/support/src/Contracts/HasLabel.php) | todo | — |
| LoadingIndicator | [`Contracts/LoadingIndicator.php`](../../../vendor/filament/support/src/Contracts/LoadingIndicator.php) | todo | — |
| [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) | [`Contracts/ScalableIcon.php`](../../../vendor/filament/support/src/Contracts/ScalableIcon.php) | — | — |
| TranslatableContentDriver | [`Contracts/TranslatableContentDriver.php`](../../../vendor/filament/support/src/Contracts/TranslatableContentDriver.php) | todo | — |
| HasEmbeddedView | [`Components/Contracts/HasEmbeddedView.php`](../../../vendor/filament/support/src/Components/Contracts/HasEmbeddedView.php) | todo | — |
| ScopedComponentManager | [`Components/Contracts/ScopedComponentManager.php`](../../../vendor/filament/support/src/Components/Contracts/ScopedComponentManager.php) | todo | — |
| ExposedLivewireMethod | [`Components/Attributes/ExposedLivewireMethod.php`](../../../vendor/filament/support/src/Components/Attributes/ExposedLivewireMethod.php) | todo | — |
| Color | [`Colors/Color.php`](../../../vendor/filament/support/src/Colors/Color.php) | todo | — |
| ColorManager | [`Colors/ColorManager.php`](../../../vendor/filament/support/src/Colors/ColorManager.php) | todo | — |
| Heroicon | [`Icons/Heroicon.php`](../../../vendor/filament/support/src/Icons/Heroicon.php) | todo | — |
| [`IconManager`](../../../packages/support/src/Icons/IconManager.php) | [`Icons/IconManager.php`](../../../vendor/filament/support/src/Icons/IconManager.php) | — | — |
| Asset | [`Assets/Asset.php`](../../../vendor/filament/support/src/Assets/Asset.php) | todo | — |
| AlpineComponent | [`Assets/AlpineComponent.php`](../../../vendor/filament/support/src/Assets/AlpineComponent.php) | todo | — |
| Css | [`Assets/Css.php`](../../../vendor/filament/support/src/Assets/Css.php) | todo | — |
| Font | [`Assets/Font.php`](../../../vendor/filament/support/src/Assets/Font.php) | todo | — |
| Js | [`Assets/Js.php`](../../../vendor/filament/support/src/Assets/Js.php) | todo | — |
| Theme | [`Assets/Theme.php`](../../../vendor/filament/support/src/Assets/Theme.php) | todo | — |
| AssetManager | [`Assets/AssetManager.php`](../../../vendor/filament/support/src/Assets/AssetManager.php) | todo | — |
| [`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php) | [`View/ComponentAttributeBag.php`](../../../vendor/filament/support/src/View/ComponentAttributeBag.php) | — | — |
| ViewManager | [`View/ViewManager.php`](../../../vendor/filament/support/src/View/ViewManager.php) | todo | — |
| [`SupportIconAlias`](../../../packages/support/src/Twig/SupportIconAlias.php) | [`View/SupportIconAlias.php`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | — | — |
| DefaultLoadingIndicator | [`View/DefaultLoadingIndicator.php`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php) | todo | — |
| ColorMaps | [`View/Components/ColorMaps/`](../../../vendor/filament/support/src/View/Components/ColorMaps) | todo | — |
| Color contracts | [`View/Components/Contracts/`](../../../vendor/filament/support/src/View/Components/Contracts) | todo | — |
| HTML generators | [`View/Concerns/`](../../../vendor/filament/support/src/View/Concerns) | todo | — |
| [`Cancel`](../../../packages/support/src/Exception/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | — | — |
| [`Halt`](../../../packages/support/src/Exception/Halt.php) | [`Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) | — | — |
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
| [`SupportServiceProvider`](../../../packages/support/src/SylamentSupportBundle.php) | [`SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) | — | — |
| [`helpers.php`](../../../packages/support/src/helpers.php) | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | — | — |
| Translations | [`resources/lang/`](../../../vendor/filament/support/resources/lang) | todo | — |
