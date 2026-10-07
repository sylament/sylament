# Schemas — port trail

Ports [`filament/schemas`](../../../vendor/filament/schemas) into [`packages/schemas`](../../../packages/schemas): the component model, from the base `Component` and the `Schema` container through the layout and content components and their shared concerns to the state loop (`$get`/`$set` and the state casts).

## Files

<!-- trail-index:start -->
| File | Status | Open | Ports | Description |
| --- | --- | --- | --- | --- |
| [actions.md](actions.md) | in progress | 1 blocked · 2 todo | `Components/Actions.php`, `vendor/filament/support/src/Concerns/HasVerticalAlignment.php` | The schemas layout component that renders a row of action buttons anywhere a schema child can go, with an optional label above the row. |
| [association-reader.md](association-reader.md) | done | — | — | The one Doctrine seam `schemas` reads associations, record keys and identifier rules through, and the `AssociationTarget` it answers. |
| [belongs-to-live-component.md](belongs-to-live-component.md) | done | — | `Concerns/BelongsToLivewire.php` | The trait that binds a Schema to its UX LiveComponent host, with a throwing accessor and a nullable peek. |
| [belongs-to-record.md](belongs-to-record.md) | in progress | 1 blocked | `Concerns/BelongsToModel.php` | The root-tier record binding on `Schema`: `record()`, the `fill()` and `getData()` mapping edges over the record path map, and its relationship-hook routing. |
| [boolean-state-cast.md](boolean-state-cast.md) | done | — | `Components/StateCasts/BooleanStateCast.php` | The BooleanStateCast, which reads state as a bool and writes it as a bool, an int or null. |
| [can-be-concealed.md](can-be-concealed.md) | done | — | `Components/Contracts/CanConcealComponents.php` | The ancestor walk that tells a schema component whether a collapsible or tabbed container above it can hide it, and the contract containers answer. |
| [can-be-disabled.md](can-be-disabled.md) | done | — | `Concerns/CanBeDisabled.php`, `Components/Concerns/CanBeDisabled.php` | The two CanBeDisabled traits, one on Schema and one on the base Component, and how a disabled component leaves the saved state. |
| [can-be-hidden.md](can-be-hidden.md) | in progress | 1 blocked · 1 todo | `Components/Concerns/CanBeHidden.php` | The schemas visibility trait: `hidden()` / `visible()` and their readers, the `*On()` operation forms and `hiddenWhenAllChildComponentsHidden()`. |
| [can-be-marked-as-required.md](can-be-marked-as-required.md) | done | — | `vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php` | The required-marker toggle shared by forms' Field and FusedGroup and schemas' Fieldset, falling back to the using class's isRequired(). |
| [can-be-validated.md](can-be-validated.md) | in progress | 1 todo | `Concerns/CanBeValidated.php`, `Concerns/InteractsWithSchemas.php`, `vendor/filament/forms/src/Components/Contracts/HasValidationRules.php` | Schema validation on Symfony Validator — the trait on `Schema`, the host's per-schema run and sticky errors, the record check and `ValidationException`. |
| [can-trim-state.md](can-trim-state.md) | done | — | `Components/Concerns/CanTrimState.php` | The CanTrimState concern that TextInput, Textarea and TagsInput compose to trim string state before validation and saving. |
| [component-belongs-to-record.md](component-belongs-to-record.md) | done | — | `Components/Concerns/BelongsToModel.php` | The component tier of BelongsToRecord on the base Component, with its record binding, record-relative paths and per-component relationship hooks. |
| [component.md](component.md) | in progress | 5 in progress · 1 blocked · 1 todo | `Components/Component.php`, `Components/Concerns/BelongsToContainer.php`, `Components/Concerns/CanBeRepeated.php`, `Components/Concerns/Cloneable.php`, `Components/Concerns/HasChildComponents.php`, `Components/Concerns/HasHeadings.php`, `Components/Concerns/HasId.php`, `Components/Concerns/HasKey.php`, `Components/Concerns/HasState.php` | The base class every schema component extends — container membership, child schemas, identity, headings, closure injection and state. |
| [date-time-state-cast.md](date-time-state-cast.md) | done | — | `Components/StateCasts/DateTimeStateCast.php` | The state cast that converts a date, time or datetime field between the wire string the input speaks and the record's date object or string. |
| [embedded-schema.md](embedded-schema.md) | done | — | `Components/EmbeddedSchema.php` | The EmbeddedSchema component, which renders one of its host's own named schemas in place. |
| [entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md) | in progress | 1 todo | `Components/Concerns/EntanglesStateWithSingularRelationship.php`, `Components/Contracts/CanEntangleWithSingularRelationships.php` | EntanglesStateWithSingularRelationship, its contract and BelongsToRelationship, binding a layout component's subtree to a to-one Doctrine association. |
| [enum-array-state-cast.md](enum-array-state-cast.md) | done | — | `Components/StateCasts/EnumArrayStateCast.php` | The state cast that converts a multi-value field between a list of backed enum cases and the list of strings its inputs speak. |
| [enum-state-cast.md](enum-state-cast.md) | done | — | `Components/StateCasts/EnumStateCast.php` | The state cast that turns a field's raw value into a single backed enum case and back to its string spelling. |
| [fieldset.md](fieldset.md) | done | — | `Components/Fieldset.php` | The labelled, bordered schemas layout component that lays its children out in two columns under a `<legend>`. |
| [file-upload-state-cast.md](file-upload-state-cast.md) | done | — | `Components/StateCasts/FileUploadStateCast.php` | The FileUploadStateCast, which keys a file upload's state by uuid on write and reads it back as one file or a list. |
| [flex.md](flex.md) | done | — | `Components/Flex.php`, `vendor/filament/support/src/Concerns/HasFromBreakpoint.php` | The schemas layout row whose children sit side by side outside the schema grid, each growing to fill the space unless it opts out. |
| [get.md](get.md) | in progress | 2 todo | `Components/Utilities/Get.php` | The `$get` callable a component hands its evaluated closures so they can read sibling and cross-container state by path. |
| [grid.md](grid.md) | done | — | `Components/Grid.php` | The basic schemas layout component, holding a column count that its child schema lays out and a host element. |
| [group.md](group.md) | done | — | `Components/Group.php` | The schemas layout component that takes its children in the factory and claims one cell of the outer grid, stacking them inside it. |
| [has-actions.md](has-actions.md) | done | — | `Components/Concerns/HasActions.php`, `Components/Contracts/HasAffixActions.php`, `Components/Contracts/HasExtraItemActions.php` | The schemas component trait that registers actions, caches them by name with the affix and extra-item contracts' actions, and stamps each with its component. |
| [has-columns.md](has-columns.md) | in progress | 1 in progress | `Concerns/HasColumns.php` | The column-count concern that `Schema` and the schemas `Component` share, folding `columns()` calls into a breakpoint map for the grid. |
| [has-gap.md](has-gap.md) | done | — | `Concerns/HasGap.php` | The HasGap concern on Schema and Component, which sets whether a schema renders inter-item gap and whether it packs densely. |
| [has-label.md](has-label.md) | done | — | `Components/Concerns/HasLabel.php` | The HasLabel concern that schema components and forms fields share for label(), hiddenLabel() and hasCustomLabel(). |
| [has-name.md](has-name.md) | done | — | `Components/Concerns/HasName.php` | The schemas-level HasName trait holding a field's machine name behind name() and getName(). |
| [has-nullable-branch.md](has-nullable-branch.md) | done | — | — | `HasNullableBranch` on the base `Component`: `nullWhenBlank()` and the branch pass in `getData()` that nulls a blank optional branch of the record graph |
| [has-operation.md](has-operation.md) | done | — | `Concerns/HasOperation.php` | The schema trait that names what the host is doing with a schema, resolving the operation up the parent chain to the host's class. |
| [has-schemas.md](has-schemas.md) | in progress | 1 blocked · 3 todo | `Concerns/InteractsWithSchemas.php` | The host-side trait a panel page or UX Live Component mixes in to own, build, cache and fill its named `Schema` instances across a request round-trip |
| [has-state-binding-modifiers.md](has-state-binding-modifiers.md) | in progress | 3 in progress · 2 todo | `Concerns/HasStateBindingModifiers.php` | The concern that sets when a schema or component syncs its state to the server, translated into a UX LiveComponent data-model directive. |
| [html.md](html.md) | done | — | `Components/Html.php` | The Html schema component, which prints its content as raw HTML with no wrapper element. |
| [icon.md](icon.md) | in progress | 2 todo | `Components/Icon.php`, `View/Components/IconComponent.php` | The schemas content component that renders a single icon with a size, a colour and a tooltip. |
| [interacts-with-schemas.md](interacts-with-schemas.md) | in progress | 1 blocked · 1 todo | `Contracts/HasSchemas.php`, `Concerns/InteractsWithSchemas.php` | The InteractsWithSchemas host contract, its browser-callable seams, and the AcceptsUploadedFiles component contract the upload seam gates on. |
| [live-component.md](live-component.md) | done | — | `Components/Livewire.php`, `Components/Concerns/HasKey.php` | The schema component that embeds another live component in place, by registered name or class-string, with the props the caller names. |
| [object-state-cast.md](object-state-cast.md) | done | — | — | The Sylament-only state cast that builds a fresh value object from an item array and deconstructs one back, behind the Repeater's value-object items. |
| [option-state-cast.md](option-state-cast.md) | done | — | `Components/StateCasts/OptionStateCast.php` | The state cast behind single-value option fields, which narrows a digit-string to an int key and fails closed on a non-scalar payload. |
| [options-array-state-cast.md](options-array-state-cast.md) | done | — | `Components/StateCasts/OptionsArrayStateCast.php` | The state cast that converts a multi-value option field between a list of option keys and the list of strings its inputs speak. |
| [presence-gate.md](presence-gate.md) | done | — | — | `PresenceGate` and the `ImplicitConstraint` marker: Laravel's presence check, which runs only the implicit constraints against a blank string |
| [property-accessor.md](property-accessor.md) | done | — | — | The state backend that reads and writes a live host's state bag, a detached array and a record's object graph by dotted path. |
| [record-key-contract.md](record-key-contract.md) | done | — | — | The key contract of `RecordKey`, `IdentifierConstraint` and `KeySelection`: the grammar, where a key travels, the API, the operations and the chunk rule. |
| [record-key.md](record-key.md) | done | — | — | The record-key codec and identifier predicate builder: `RecordKey`, `IdentifierConstraint` and `KeySelection` over the internal `IdentifierFields`. |
| [record-type-reader.md](record-type-reader.md) | done | — | — | `RecordTypeReader` and its `RecordTarget`: what a record class declares at a dotted path, read from the write side through `symfony/property-info` |
| [relationship.md](relationship.md) | done | — | — | The Relationship value object and the AssociationReader arm that scopes, lists and writes one owner record's association. |
| [schema-extension.md](schema-extension.md) | done | — | — | The Twig extension behind renderSchema(), which renders a host LiveComponent's named schema from a template. |
| [schema.md](schema.md) | in progress | 3 in progress · 3 blocked · 7 todo | `Schema.php`, `Concerns/BelongsToParentComponent.php`, `Concerns/CanBeEmbeddedInParentComponent.php`, `Concerns/CanBeInline.php`, `Concerns/CanModifyActions.php`, `Concerns/Cloneable.php`, `Concerns/HasComponents.php`, `Concerns/HasFieldWrapper.php`, `Concerns/HasHeadings.php`, `Concerns/HasKey.php`, `Concerns/HasState.php` | The root container of the component model: the state root a live component holds, its folded container concerns, closure injection and grid template. |
| [schemas-bundle.md](schemas-bundle.md) | in progress | 1 in progress · 1 blocked · 3 todo | `SchemasServiceProvider.php`, `View/SchemaIconAlias.php`, `resources/js/index.js`, `resources/js/components/actions.js`, `resources/js/components/tabs.js`, `resources/js/components/wizard.js` | The schemas bundle class, its asset workspace and utilities, the icon aliases, and the Stimulus controllers that replace Filament's schemas Alpine scripts. |
| [search-constraint.md](search-constraint.md) | done | — | — | The Doctrine `LIKE` search predicate that forms, tables and actions share, folding case and casting non-character fields per platform. |
| [section.md](section.md) | in progress | 1 in progress | `Components/Section.php`, `Components/Concerns/CanBeCollapsed.php`, `Components/Concerns/CanBeCompact.php`, `Components/Concerns/CanBeDivided.php`, `Components/Concerns/CanBeSecondary.php`, `Components/Concerns/HasDescription.php`, `Components/Concerns/HasFooterActions.php`, `Components/Concerns/HasFooterActionsAlignment.php`, `Components/Concerns/HasHeaderActions.php`, `Components/Concerns/HasHeading.php` | The boxed schemas layout component with a heading header, collapse, compact, divided, secondary and aside variants, and six auxiliary slots. |
| [set.md](set.md) | in progress | 1 in progress | `Components/Utilities/Set.php` | The $set state writer handed to closures, the write-side twin of Get. |
| [sort-directions.md](sort-directions.md) | done | — | — | The helper that turns the asc or desc string of the sort API into the SortDirection enum Doctrine's query builder takes. |
| [state-cast.md](state-cast.md) | done | — | `Components/StateCasts/Contracts/StateCast.php` | The StateCast contract, the get() and set() pair that every schemas state cast implements. |
| [step.md](step.md) | done | — | `Components/Wizard/Step.php`, `Components/Concerns/HasLabel.php` | One panel of a Wizard — its label, header description and icons, validation callbacks and the child schema holding the step's components. |
| [strip-characters-state-cast.md](strip-characters-state-cast.md) | done | — | `Components/StateCasts/StripCharactersStateCast.php` | The StripCharactersStateCast, which removes configured characters from string state, element by element for arrays. |
| [styles.md](styles.md) | in progress | 2 in progress · 1 blocked | `resources/css/index.css`, `resources/css/schema.css`, `resources/css/components/actions.css`, `resources/css/components/flex.css`, `resources/css/components/form.css`, `resources/css/components/icon.css`, `resources/css/components/image.css`, `resources/css/components/section.css`, `resources/css/components/text.css`, `resources/css/components/unordered-list.css`, `vendor/filament/actions/resources/css/actions.css` | The schemas bundle's component stylesheets under packages/schemas/assets/src/styles/, ported from Filament's schemas resources/css/. |
| [tab.md](tab.md) | done | — | `Components/Tabs/Tab.php`, `vendor/filament/support/src/Concerns/HasBadge.php`, `vendor/filament/support/src/Concerns/HasBadgeTooltip.php` | One tab of a Tabs component, with its label, icon and badge on the nav side and a child schema rendered as the panel, plus support's badge concerns. |
| [tabs.md](tabs.md) | in progress | 1 blocked | `Components/Tabs.php`, `Components/Concerns/CanPersistTab.php`, `resources/css/components/tabs.css` | The Tabs layout component and its CanPersistTab concern, in client mode (a Stimulus class swap) and server mode (a host live property). |
| [text.md](text.md) | in progress | 7 todo | `Components/Text.php`, `View/Components/TextComponent.php`, `JsContent.php` | The schemas content component that renders a piece of styled text in a span, with a size, a weight, a font family and a colour. |
| [violation-path-translator.md](violation-path-translator.md) | done | — | — | `ViolationPathTranslator` and the `HasKeyedItems` contract: a record-graph violation path spoken back as the state path that shows it |
| [wizard.md](wizard.md) | done | — | `Components/Wizard.php`, `resources/js/components/wizard.js`, `resources/css/components/wizard.css` | The multi-step layout component: a header stepper, one panel per `Step`, a footer with Back/Next and cancel/submit slots, and per-step validation |
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
