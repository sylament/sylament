# Support — port trail

Ports [`filament/support`](../../../vendor/filament/support) into [`packages/support`](../../../packages/support): the foundation every other bundle builds on, from the base component model and the shared UI components to colours, icons, assets and helpers.

## Files

<!-- trail-index:start -->
| File | Status | Open | Ports | Description |
| --- | --- | --- | --- | --- |
| [array-record.md](array-record.md) | done | — | `ArrayRecord.php` | The global name of the entry that identifies an array record, `__key` unless an application overrides it. |
| [badge.md](badge.md) | in progress | 3 blocked · 1 todo | `resources/views/components/badge.blade.php`, `View/Components/BadgeComponent.php`, `resources/css/components/badge.css` | The `<twig:SylamentSupport:Badge>` anonymous component, Filament's status pill with its icon, colour map and delete button. |
| [breadcrumbs.md](breadcrumbs.md) | done | — | `resources/views/components/breadcrumbs.blade.php`, `resources/css/components/breadcrumbs.css` | The anonymous `<twig:SylamentSupport:Breadcrumbs>` Twig component and its stylesheet, the port of Filament's `<x-filament::breadcrumbs>`. |
| [button.md](button.md) | in progress | 2 in progress · 1 blocked · 6 todo | `resources/views/components/button/index.blade.php`, `resources/css/components/button.css` | The anonymous `<twig:SylamentSupport:Button>` text-button template and the `button.css` chrome beside it, including the button group and label-button arms. |
| [can-be-contained.md](can-be-contained.md) | done | — | `Concerns/CanBeContained.php` | The shared concern that lets a layout component drop its container chrome with `contained(false)`. |
| [can-be-copied.md](can-be-copied.md) | done | — | `Concerns/CanBeCopied.php` | The shared concern that offers a component's state to the clipboard, with the copied text, the confirmation message and how long it stays. |
| [can-order-columns.md](can-order-columns.md) | done | — | `Concerns/CanOrderColumns.php` | The shared concern that sets a component's visual order in its parent grid per breakpoint, through `columnOrder()`. |
| [can-span-columns.md](can-span-columns.md) | done | — | `Concerns/CanSpanColumns.php` | The trait that sets how many grid columns a component spans and which column it starts on, per responsive breakpoint. |
| [can-wrap.md](can-wrap.md) | done | — | `Concerns/CanWrap.php` | The shared concern that lets a component's content wrap onto several lines with `wrap()`. |
| [cancel.md](cancel.md) | done | — | `Exceptions/Cancel.php` | The control-flow exception an action closure throws to abort the lifecycle and unmount the action without a failure notification. |
| [color-manager.md](color-manager.md) | in progress | 1 todo | `Colors/ColorManager.php`, `Assets/AssetManager.php`, `Facades/FilamentColor.php` | The palette registry, the class and inline-style emitters that apply a contrast policy, and the colour CSS the `sylamentStyles()` function adds on top. |
| [color-policies.md](color-policies.md) | in progress | 3 blocked | `View/Components/BadgeComponent.php`, `View/Components/ButtonComponent.php`, `View/Components/CalloutComponent.php`, `View/Components/CalloutComponent/IconComponent.php`, `View/Components/ColorMaps/ButtonComponentColorMap.php`, `View/Components/ColorMaps/ComponentColorMap.php`, `View/Components/ColorMaps/IconButtonComponentColorMap.php`, `View/Components/Contracts/HasColor.php`, `View/Components/Contracts/HasDefaultGrayColor.php`, `View/Components/DropdownComponent/HeaderComponent.php`, `View/Components/DropdownComponent/ItemComponent.php`, `View/Components/DropdownComponent/ItemComponent/IconComponent.php`, `View/Components/IconButtonComponent.php`, `View/Components/InputComponent/WrapperComponent/IconComponent.php`, `View/Components/LinkComponent.php`, `View/Components/ModalComponent/IconComponent.php`, `View/Components/SectionComponent/IconComponent.php`, `View/Components/ToggleComponent.php` | The per-element contrast policies that pick a palette's shades, the two colour contracts and the three shade resolvers behind them. |
| [color.md](color.md) | in progress | 1 in progress | `Colors/Color.php` | The palette catalog and the colour maths every contrast policy measures with. |
| [component-attribute-bag.md](component-attribute-bag.md) | done | — | `View/ComponentAttributeBag.php`, `resources/css/components/grid.css` | The `ComponentAttributeBag` HTML attribute container and the `attrs()`, `classes()`, `styles()`, `grid()` and `gridColumn()` Twig functions built on it. |
| [component-manager.md](component-manager.md) | in progress | 1 blocked | `Components/ComponentManager.php`, `Components/Contracts/ScopedComponentManager.php` | The registry behind configureUsing(), which runs each component's setUp() and its registered closures in class-hierarchy order. |
| [component.md](component.md) | done | — | `Components/Component.php`, `Concerns/Configurable.php`, `Concerns/EvaluatesClosures.php`, `Concerns/Macroable.php` | The abstract root every Sylament component extends, with its closure evaluation and the construction seam every `make()` goes through. |
| [contracts.md](contracts.md) | done | — | `Contracts/HasColor.php`, `Contracts/HasDescription.php`, `Contracts/HasIcon.php`, `Contracts/HasLabel.php`, `Contracts/ScalableIcon.php` | The state contracts a user's enum implements to give its cases a colour, description, icon or label, and the ScalableIcon contract for size-aware icon sets. |
| [copyable.md](copyable.md) | done | — | `vendor/filament/tables/src/Columns/TextColumn.php`, `vendor/filament/tables/resources/css/columns/color.css` | The shared copy-to-clipboard Stimulus controller and its stylesheet, the port of the inline Alpine click handler Filament writes in every copyable component. |
| [dropdown-item.md](dropdown-item.md) | in progress | 2 blocked · 7 todo | `resources/views/components/dropdown/list/item.blade.php`, `View/Concerns/CanGenerateDropdownItemHtml.php`, `resources/css/components/dropdown/list/item.css` | The anonymous `<twig:SylamentSupport:DropdownItem>` row primitive inside a dropdown panel, and its stylesheet. |
| [dropdown.md](dropdown.md) | in progress | 9 todo | `resources/css/components/dropdown/header.css`, `resources/css/components/dropdown/index.css`, `resources/css/components/dropdown/list/index.css`, `resources/js/components/dropdown.js`, `resources/views/components/dropdown/header.blade.php`, `resources/views/components/dropdown/index.blade.php`, `resources/views/components/dropdown/list/index.blade.php` | The shared `<twig:SylamentSupport:Dropdown>` positioned-panel primitive and its `sylament--support--dropdown` Stimulus controller over Floating UI. |
| [enums.md](enums.md) | done | — | `Enums/Alignment.php`, `Enums/ArgumentValue.php`, `Enums/FontFamily.php`, `Enums/FontWeight.php`, `Enums/GridDirection.php`, `Enums/IconPosition.php`, `Enums/Operation.php`, `Enums/Size.php`, `Enums/SlideOverPosition.php`, `Enums/TextSize.php` | The support enums with no file of their own, from alignment, size and font cases to icon position, grid direction, operation and the not-passed sentinel. |
| [exposed-live-component-method.md](exposed-live-component-method.md) | done | — | `Components/Attributes/ExposedLivewireMethod.php` | The method attribute that marks a schema-component method as callable from the browser through the host's dispatcher. |
| [fieldset.md](fieldset.md) | done | — | `resources/views/components/fieldset.blade.php`, `resources/css/components/fieldset.css` | The anonymous Twig component `<twig:SylamentSupport:Fieldset>` that ports Filament's `<x-filament::fieldset>`. |
| [filesystem-registry.md](filesystem-registry.md) | done | — | `vendor/filament/forms/src/Components/BaseFileUpload.php`, `vendor/filament/tables/src/Columns/ImageColumn.php` | The `FilesystemRegistry` storage seam every file-handling component reaches through, the port's translation of Laravel's `Storage::disk()`. |
| [halt.md](halt.md) | done | — | `Exceptions/Halt.php` | The control-flow exception a closure throws to stop the lifecycle cleanly while its action modal stays mounted, without a failure notification. |
| [has-alignment.md](has-alignment.md) | done | — | `Concerns/HasAlignment.php` | The concern that gives a component a horizontal alignment, its align*() shortcuts and the class a template emits for it. |
| [has-color.md](has-color.md) | done | — | `Concerns/HasColor.php` | The shared concern that gives a component a palette colour, with a fallback default, through `color()` and `defaultColor()`. |
| [has-default-data-formatting-settings.md](has-default-data-formatting-settings.md) | in progress | 1 blocked | `Concerns/HasDefaultDataFormattingSettings.php` | The eight toolkit-wide formatting defaults a date, money or number formatter falls back to when the component that renders it names none. |
| [has-extra-attributes.md](has-extra-attributes.md) | in progress | 1 in progress | `Concerns/HasExtraAttributes.php` | The shared concern that lets a component carry extra HTML attributes for its root element, replaced or merged with `extraAttributes()`. |
| [has-font-family.md](has-font-family.md) | done | — | `Concerns/HasFontFamily.php` | The concern that gives a component a font family and the class a template emits for it. |
| [has-icon-color.md](has-icon-color.md) | done | — | `Concerns/HasIconColor.php` | The shared concern that gives a component an icon colour separate from its own colour. |
| [has-icon-size.md](has-icon-size.md) | done | — | `Concerns/HasIconSize.php` | The shared concern that gives a component a configurable icon size. |
| [has-icon.md](has-icon.md) | done | — | `Concerns/HasIcon.php` | The concern that gives a component an icon, a blank-value sentinel that suppresses the default, and the getter's fallback. |
| [has-placeholder.md](has-placeholder.md) | done | — | `Concerns/HasPlaceholder.php` | The concern that gives a component the content it renders instead of its state when that state is blank. |
| [has-tooltip.md](has-tooltip.md) | done | — | `Concerns/HasTooltip.php` | The shared concern that gives a component a tooltip, as a string or as `Twig\Markup`. |
| [has-vertical-alignment.md](has-vertical-alignment.md) | done | — | `Concerns/HasVerticalAlignment.php` | The concern that gives a component a vertical alignment, its verticallyAlign*() shortcuts and the class a template emits for it. |
| [has-weight.md](has-weight.md) | done | — | `Concerns/HasWeight.php` | The concern that gives a component a font weight and the class a template emits for it. |
| [helpers.md](helpers.md) | in progress | 1 blocked · 9 todo | `helpers.php`, `SupportServiceProvider.php` | The `Sylament\Support` namespaced global functions, porting Filament's helpers and macros and the Laravel helpers Filament's code calls. |
| [icon-button.md](icon-button.md) | in progress | 3 blocked · 3 todo | `resources/views/components/icon-button.blade.php`, `View/Components/IconButtonComponent.php`, `resources/css/components/icon-button.css` | The `<twig:SylamentSupport:IconButton>` anonymous component, Filament's icon-only button with its size-derived icon, accessible name and colour map. |
| [icon-manager.md](icon-manager.md) | done | — | `Icons/IconManager.php`, `Facades/FilamentIcon.php` | The `IconManager` registry mapping icon aliases to icon references, and its `renderIcon()` bridge to Symfony UX Icons. |
| [icon-size.md](icon-size.md) | done | — | `Enums/IconSize.php` | The string-backed enum naming the six icon sizes, `xs` to `2xl`, that icon-rendering components resolve a size against. |
| [icon.md](icon.md) | done | — | `resources/views/components/icon.blade.php`, `resources/css/components/icon.css` | The anonymous Twig component `<twig:SylamentSupport:Icon>` that ports Filament's `<x-filament::icon>`. |
| [input-wrapper.md](input-wrapper.md) | in progress | 2 todo | `resources/css/components/input/wrapper.css`, `resources/views/components/input/wrapper.blade.php` | The `<twig:SylamentSupport:InputWrapper>` component that carries a text-like control's chrome, state classes and prefix and suffix affixes. |
| [link.md](link.md) | in progress | 1 in progress · 3 blocked · 2 todo | `resources/views/components/link.blade.php`, `View/Components/LinkComponent.php`, `resources/css/components/link.css` | The `<twig:SylamentSupport:Link>` anonymous component, Filament's inline text link or button with its icon, size, weight and colour map. |
| [loading-indicator.md](loading-indicator.md) | in progress | 2 todo | `resources/views/components/loading-indicator.blade.php`, `View/DefaultLoadingIndicator.php`, `Contracts/LoadingIndicator.php`, `resources/css/components/loading-indicator.css` | The LoadingIndicator Twig component, the spinner SVG Filament's loading-indicator view renders, and its stylesheet. |
| [modal.md](modal.md) | in progress | 1 blocked · 7 todo | `View/Components/ModalComponent.php`, `resources/views/components/modal/index.blade.php`, `resources/css/components/modal.css`, `resources/js/components/modal.js` | The shared `<twig:SylamentSupport:Modal>` primitive, Filament's Alpine-driven modal rebuilt on a native `<dialog>` and a Stimulus controller. |
| [pagination.md](pagination.md) | in progress | 2 in progress · 2 blocked · 1 todo | `resources/views/components/pagination/index.blade.php`, `resources/views/components/pagination/item.blade.php`, `resources/css/components/pagination.css` | The `Paginator` carrier, one page of records plus its counts, and the `<twig:SylamentSupport:Pagination>` footer that renders it. |
| [section.md](section.md) | in progress | 2 in progress | `resources/views/components/section/index.blade.php`, `resources/views/components/section/heading.blade.php`, `resources/views/components/section/description.blade.php`, `resources/css/components/section.css` | The presentational `<twig:SylamentSupport:Section>` Twig component, its collapse controller and stylesheet, the port of Filament's `filament::section` view. |
| [sortable.md](sortable.md) | done | — | `resources/js/sortable.js`, `resources/css/sortable.css` | The shared drag-and-drop Stimulus controller over SortableJS, the port of Filament's `x-sortable` directive, and its ghost-class rule. |
| [support-bundle.md](support-bundle.md) | in progress | 2 in progress · 1 blocked | `SupportServiceProvider.php`, `resources/css/index.css`, `resources/css/base.css` | The `SylamentSupportBundle` service wiring, config, static-instance boot and asset registration, and the package's stylesheet entry and core controller. |
| [support-icon-alias.md](support-icon-alias.md) | in progress | 1 blocked | `View/SupportIconAlias.php` | The icon alias names an app registers overrides against through IconManager, so a component's default icon can be swapped without touching its template. |
| [tabs.md](tabs.md) | done | — | `resources/views/components/tabs/index.blade.php`, `resources/views/components/tabs/item.blade.php`, `resources/css/components/tabs.css` | The tab chrome, the `<twig:SylamentSupport:Tabs>` nav shell and the `<twig:SylamentSupport:TabsItem>` trigger, with their shared stylesheet. |
| [vertical-alignment.md](vertical-alignment.md) | done | — | `Enums/VerticalAlignment.php` | The string-backed enum naming the vertical alignments `start`, `center` and `end` that vertically positioned components resolve against. |
| [view-component.md](view-component.md) | in progress | 1 todo | `Components/ViewComponent.php`, `Components/Contracts/HasEmbeddedView.php` | The abstract `Component` subclass that adds Twig-backed rendering, and the `renderComponent()` Twig function that drives it. |
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
| [`Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
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
