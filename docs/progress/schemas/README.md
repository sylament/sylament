# Schemas — port progress

Ports [`filament/schemas`](../../../vendor/filament/schemas) into
[`packages/schemas`](../../../packages/schemas). The component model: the base
`Component`, the `Schema` container, the layout and content components, the
shared concerns, and the state loop (`$get`/`$set`, state casts).

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `schemas` surface — every class, trait, contract,
template, asset, and translation set. The per-component audit against Filament
has now resolved every **started** row (a linked first-column source) to a real
[status](../README.md#statuses) — **done** or **in progress** — with a detail
file wherever a divergence, an open gap, or real sub-structure earns one. A
plain-text name is not built yet and stays **todo**. Single-use concerns have
folded into the detail file of the component that uses them (Schema's into
[`schema.md`](schema.md), the base Component's into [`component.md`](component.md),
Section's into [`section.md`](section.md), Tabs' into [`tabs.md`](tabs.md)) and left the Features tables, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing).
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

A first-column link whose target file is named differently from the row is a
rename or relocation the port already made — the idiom translations (Livewire →
UX LiveComponent, Blade view → Twig template, `Htmlable` → `Markup`), the
`BelongsToLivewire` → `BelongsToLiveComponent`, `BelongsToModel` →
`BelongsToRecord` (both tiers, with `model()` absorbed into `record()`), and
`SchemasServiceProvider` → `SylamentSchemasBundle` renames, and the schema-host `HasSchemas` (interface) ⇄
`InteractsWithSchemas` (trait) name swap. These are recorded in
[`renames.md`](../renames.md).

## Components

The classes a user instantiates — the `Schema` root, the base `Component`, and
the layout/content components in
[`src/Components`](../../../vendor/filament/schemas/src/Components). The
`Components/Concerns` traits are [Features](#features); `Components/StateCasts`,
`Components/Utilities`, and `Components/Contracts` are the
[Supporting surface](#supporting-surface). One row's code lives one package up:
`FusedGroup` renders forms' field-wrapper chrome and composes three forms
concerns, so it ships in `sylament/forms` and its row here points there —
[why](../renames.md#namespace-segments).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Schema`](../../../packages/schemas/src/Schema.php) | [`Schema.php`](../../../vendor/filament/schemas/src/Schema.php) | in progress | [schema.md](schema.md) |
| [`Component`](../../../packages/schemas/src/Components/Component.php) | [`Components/Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | in progress | [component.md](component.md) |
| [`Grid`](../../../packages/schemas/src/Components/Grid.php) | [`Components/Grid.php`](../../../vendor/filament/schemas/src/Components/Grid.php) | done | [grid.md](grid.md) |
| [`Group`](../../../packages/schemas/src/Components/Group.php) | [`Components/Group.php`](../../../vendor/filament/schemas/src/Components/Group.php) | done | [group.md](group.md) |
| [`Section`](../../../packages/schemas/src/Components/Section.php) | [`Components/Section.php`](../../../vendor/filament/schemas/src/Components/Section.php) | done | [section.md](section.md) |
| [`Fieldset`](../../../packages/schemas/src/Components/Fieldset.php) | [`Components/Fieldset.php`](../../../vendor/filament/schemas/src/Components/Fieldset.php) | done | [fieldset.md](fieldset.md) |
| [`Flex`](../../../packages/schemas/src/Components/Flex.php) | [`Components/Flex.php`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | [flex.md](flex.md) |
| [`FusedGroup`](../../../packages/forms/src/Components/FusedGroup.php) (ships in **forms**) | [`Components/FusedGroup.php`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | in progress | [../forms/fused-group.md](../forms/fused-group.md) |
| [`Tabs`](../../../packages/schemas/src/Components/Tabs.php) | [`Components/Tabs.php`](../../../vendor/filament/schemas/src/Components/Tabs.php) | in progress | [tabs.md](tabs.md) |
| [`Tabs\Tab`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [`Components/Tabs/Tab.php`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | [tab.md](tab.md) |
| [`Wizard`](../../../packages/schemas/src/Components/Wizard.php) | [`Components/Wizard.php`](../../../vendor/filament/schemas/src/Components/Wizard.php) | in progress | [wizard.md](wizard.md) |
| [`Wizard\Step`](../../../packages/schemas/src/Components/Wizard/Step.php) | [`Components/Wizard/Step.php`](../../../vendor/filament/schemas/src/Components/Wizard/Step.php) | in progress | [step.md](step.md) |
| [`Actions`](../../../packages/schemas/src/Components/Actions.php) | [`Components/Actions.php`](../../../vendor/filament/schemas/src/Components/Actions.php) | in progress | [actions.md](actions.md) |
| `Callout` | [`Components/Callout.php`](../../../vendor/filament/schemas/src/Components/Callout.php) | todo | — |
| `EmbeddedSchema` | [`Components/EmbeddedSchema.php`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php) | todo | — |
| `EmbeddedTable` | [`Components/EmbeddedTable.php`](../../../vendor/filament/schemas/src/Components/EmbeddedTable.php) | todo | — |
| `EmptyState` | [`Components/EmptyState.php`](../../../vendor/filament/schemas/src/Components/EmptyState.php) | todo | — |
| `Form` | [`Components/Form.php`](../../../vendor/filament/schemas/src/Components/Form.php) | todo | — |
| [`Html`](../../../packages/schemas/src/Components/Html.php) | [`Components/Html.php`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — |
| [`Icon`](../../../packages/schemas/src/Components/Icon.php) | [`Components/Icon.php`](../../../vendor/filament/schemas/src/Components/Icon.php) | in progress | [icon.md](icon.md) |
| `Image` | [`Components/Image.php`](../../../vendor/filament/schemas/src/Components/Image.php) | todo | — |
| `Livewire` | [`Components/Livewire.php`](../../../vendor/filament/schemas/src/Components/Livewire.php) | todo | — |
| `RenderHook` | [`Components/RenderHook.php`](../../../vendor/filament/schemas/src/Components/RenderHook.php) | todo | — |
| [`Text`](../../../packages/schemas/src/Components/Text.php) | [`Components/Text.php`](../../../vendor/filament/schemas/src/Components/Text.php) | in progress | [text.md](text.md) |
| `UnorderedList` | [`Components/UnorderedList.php`](../../../vendor/filament/schemas/src/Components/UnorderedList.php) | todo | — |
| `View` | [`Components/View.php`](../../../vendor/filament/schemas/src/Components/View.php) | todo | — |

## Features

Cross-cutting concerns and traits shared across components. A concern used by a
single component is tracked in that component's detail file, not here:

- the base **Component**'s own concerns (`BelongsToContainer`,
  `HasChildComponents`, `HasId`, and the component-level `HasState` / `HasKey` /
  `HasHeadings`) → [`component.md`](component.md);
- **Section**'s (`CanBeCollapsed`, `CanBeCompact`, `CanBeDivided`,
  `CanBeSecondary`, `HasDescription`, `HasHeading`, `HasHeaderActions`,
  `HasFooterActions`, `HasFooterActionsAlignment`, `CanBeAside`,
  `HasContentBefore`) → [`section.md`](section.md);
- **Schema**'s (`BelongsToParentComponent`, `CanBeEmbeddedInParentComponent`,
  `CanBeInline`, `CanModifyActions`, `HasComponents`, and the schema-level
  `HasState` / `HasKey` / `HasHeadings`) → [`schema.md`](schema.md).

`HasLabel`, `CanBeHidden`, and `BelongsToLiveComponent` are **not** folded — each
is shared with `actions`/`forms` components, so it keeps its own row below.

Names collide across the two directories (`HasKey`, `HasState`, `HasHeadings`,
…) because they solve the same problem at component vs container scope; the Ports
path disambiguates.

### Component-level concerns

Traits on the base `Component` —
[`src/Components/Concerns`](../../../vendor/filament/schemas/src/Components/Concerns).
The rows below are the ones still shared or not yet started; the base Component's
own single-use concerns folded into [`component.md`](component.md).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`BelongsToModel`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | in progress | [belongs-to-record.md](belongs-to-record.md) |
| [`BelongsToRelationship`](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php) — no Filament concern of its own; the association name, target resolution and three-arm refusal shared by the entangled layout components and forms' relation-backed fields | [`Components/Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) · [`forms/Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | [entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields) |
| [`CanBeConcealed`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | [`Components/Concerns/CanBeConcealed.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeConcealed.php) | done | [can-be-concealed.md](can-be-concealed.md) |
| [`CanBeDisabled`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php) | [`Components/Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php) | done | [can-be-disabled.md](can-be-disabled.md) |
| `CanBeGridContainer` | [`Components/Concerns/CanBeGridContainer.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeGridContainer.php) | todo | — |
| [`CanBeHidden`](../../../packages/schemas/src/Components/Concerns/CanBeHidden.php) | [`Components/Concerns/CanBeHidden.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | in progress | [can-be-hidden.md](can-be-hidden.md) |
| `CanBeLiberatedFromContainerGrid` | [`Components/Concerns/CanBeLiberatedFromContainerGrid.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeLiberatedFromContainerGrid.php) | todo | — |
| [`CanBeMarkedAsRequired`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`forms/Components/Concerns/CanBeMarkedAsRequired.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | done | [can-be-marked-as-required.md](can-be-marked-as-required.md) |
| [`CanBeRepeated`](../../../packages/schemas/src/Components/Concerns/CanBeRepeated.php) | [`Components/Concerns/CanBeRepeated.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeRepeated.php) | done | [component.md](component.md) |
| `CanGetStateFromRelationships` | [`Components/Concerns/CanGetStateFromRelationships.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanGetStateFromRelationships.php) | todo | — |
| `CanOpenUrl` | [`Components/Concerns/CanOpenUrl.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanOpenUrl.php) | todo | — |
| `CanPartiallyRender` | [`Components/Concerns/CanPartiallyRender.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanPartiallyRender.php) | todo | — |
| [`CanPersistTab`](../../../packages/schemas/src/Components/Concerns/CanPersistTab.php) | [`Components/Concerns/CanPersistTab.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanPersistTab.php) | done | [tabs.md](tabs.md#canpersisttabs-property-is-protected-and-the-storage-key-drops-alpines-prefix) |
| `CanPoll` | [`Components/Concerns/CanPoll.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanPoll.php) | todo | — |
| `CanStripCharactersFromState` | [`Components/Concerns/CanStripCharactersFromState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | todo | — |
| `CanTrimState` | [`Components/Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | todo | — |
| [`Cloneable`](../../../packages/schemas/src/Components/Concerns/Cloneable.php) | [`Components/Concerns/Cloneable.php`](../../../vendor/filament/schemas/src/Components/Concerns/Cloneable.php) | done | [component.md](component.md#haschildcomponents-only-the-materialization-core-is-ported) |
| [`EntanglesStateWithSingularRelationship`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`Components/Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | in progress | [entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md) |
| [`HasActions`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`Components/Concerns/HasActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | in progress | [has-actions.md](has-actions.md) |
| [`HasContainerGridLayout`](../../../packages/schemas/src/Components/Concerns/HasContainerGridLayout.php) | [`Components/Concerns/HasContainerGridLayout.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasContainerGridLayout.php) | done | Two level-max deltas from vendor, both behaviour-pinned by [`HasContainerGridLayoutTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasContainerGridLayoutTest.php). `getGridColumns()` builds its map fresh, in [`HasColumns::getAllColumns()`](../../../packages/schemas/src/Components/Concerns/HasColumns.php)' shape — the six-breakpoint default map only when `grid()` was never called, an empty array the walk fills otherwise — where vendor seeds `$columns` from the raw `$gridColumns` property and `unset()`s each key as the walk consumes it; that property carries closures and integer keys, so seeding from it cannot satisfy the declared `array<string, ?int>` return at level max. And the empty-breakpoint guard is `$gridColumnBreakpoint === ''` rather than vendor's `blank()`, which PHPStan rejects as `function.impossibleType` over an already-narrowed `int\|string` key — the same family as [actions' `!== ''` item-key guard](../actions/action.md); the two spellings part only over a whitespace-only breakpoint name, which no real breakpoint map contains |
| `HasEntryWrapper` | [`Components/Concerns/HasEntryWrapper.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasEntryWrapper.php) | todo | — |
| `HasFieldWrapper` | [`Components/Concerns/HasFieldWrapper.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasFieldWrapper.php) | todo | [../forms/fused-group.md](../forms/fused-group.md#the-component-tier-hasfieldwrapper-twin-is-not-ported) |
| `HasInlineLabel` | [`Components/Concerns/HasInlineLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasInlineLabel.php) | todo | — |
| [`HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`Components/Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | [has-label.md](has-label.md) |
| `HasMaxWidth` | [`Components/Concerns/HasMaxWidth.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasMaxWidth.php) | todo | — |
| `HasMeta` | [`Components/Concerns/HasMeta.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasMeta.php) | todo | — |
| [`HasName`](../../../packages/schemas/src/Components/Concerns/HasName.php) | [`Components/Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | [has-name.md](has-name.md) |

### Schema-level concerns

Traits on the `Schema` container and its wiring —
[`src/Concerns`](../../../vendor/filament/schemas/src/Concerns). Schema's own
single-use concerns folded into [`schema.md`](schema.md); the rows below are
shared with the base `Component` or not yet started.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`BelongsToLivewire`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) | [`Concerns/BelongsToLivewire.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToLivewire.php) | done | [belongs-to-live-component.md](belongs-to-live-component.md) |
| [`BelongsToModel`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | in progress | [belongs-to-record.md](belongs-to-record.md) |
| [`CanBeDisabled`](../../../packages/schemas/src/Concerns/CanBeDisabled.php) | [`Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Concerns/CanBeDisabled.php) | done | [can-be-disabled.md](can-be-disabled.md) |
| `CanBeHidden` | [`Concerns/CanBeHidden.php`](../../../vendor/filament/schemas/src/Concerns/CanBeHidden.php) | todo | — |
| [`CanBeValidated`](../../../packages/schemas/src/Concerns/CanBeValidated.php) | [`Concerns/CanBeValidated.php`](../../../vendor/filament/schemas/src/Concerns/CanBeValidated.php) | in progress | [can-be-validated.md](can-be-validated.md) |
| [`Cloneable`](../../../packages/schemas/src/Concerns/Cloneable.php) | [`Concerns/Cloneable.php`](../../../vendor/filament/schemas/src/Concerns/Cloneable.php) | done | [schema.md](schema.md#clonecomponents-never-stamps-the-original-and-always-resets-the-cache) |
| [`HasColumns`](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`Concerns/HasColumns.php`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | [has-columns.md](has-columns.md) |
| [`HasGap`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`Concerns/HasGap.php`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | [has-gap.md](has-gap.md) |
| `HasEntryWrapper` | [`Concerns/HasEntryWrapper.php`](../../../vendor/filament/schemas/src/Concerns/HasEntryWrapper.php) | todo | — |
| [`HasFieldWrapper`](../../../packages/schemas/src/Concerns/HasFieldWrapper.php) | [`Concerns/HasFieldWrapper.php`](../../../vendor/filament/schemas/src/Concerns/HasFieldWrapper.php) | done | [schema.md](schema.md#the-field-wrapper-view-is-opaque-data-with-no-closure-arm-and-no-parent-walk) |
| `HasInlineLabels` | [`Concerns/HasInlineLabels.php`](../../../vendor/filament/schemas/src/Concerns/HasInlineLabels.php) | todo | — |
| [`HasOperation`](../../../packages/schemas/src/Concerns/HasOperation.php) | [`Concerns/HasOperation.php`](../../../vendor/filament/schemas/src/Concerns/HasOperation.php) | done | [has-operation.md](has-operation.md) |
| [`HasStateBindingModifiers`](../../../packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php) | [`Concerns/HasStateBindingModifiers.php`](../../../vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php) | in progress | [has-state-binding-modifiers.md](has-state-binding-modifiers.md) |
| [`InteractsWithSchemas`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`Concerns/InteractsWithSchemas.php`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | in progress | [has-schemas.md](has-schemas.md) |
| `ResolvesDynamicLivewireProperties` | [`Concerns/ResolvesDynamicLivewireProperties.php`](../../../vendor/filament/schemas/src/Concerns/ResolvesDynamicLivewireProperties.php) | todo | — |
| `RestrictsFileUploadsToSchemaComponents` | [`Concerns/RestrictsFileUploadsToSchemaComponents.php`](../../../vendor/filament/schemas/src/Concerns/RestrictsFileUploadsToSchemaComponents.php) | skipped | [diverges](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |

## Supporting surface

Everything with no user-instantiated component of its own: the state loop
(`$get`/`$set`, the `PropertyAccessor` backend, the `RecordTypeReader` behind
record mapping, the `AssociationReader` seam onto Doctrine's mapping, and the
state casts), the validation presence gate, contracts,
the view layer, the test helper, value objects, CLI commands, templates, assets,
and translations —
[`src/Components/Utilities`](../../../vendor/filament/schemas/src/Components/Utilities),
[`src/Components/StateCasts`](../../../vendor/filament/schemas/src/Components/StateCasts),
[`src/Components/Contracts`](../../../vendor/filament/schemas/src/Components/Contracts),
[`src/Contracts`](../../../vendor/filament/schemas/src/Contracts),
[`src/View`](../../../vendor/filament/schemas/src/View),
[`src/Testing`](../../../vendor/filament/schemas/src/Testing),
[`src/Commands`](../../../vendor/filament/schemas/src/Commands), and
[`resources`](../../../vendor/filament/schemas/resources). A row named for a
directory collapses a subtree we expect to port, if at all, as a unit. A row
with no Ports link is Sylament-only — no Filament source to diff against.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Get`](../../../packages/schemas/src/State/Get.php) | [`Components/Utilities/Get.php`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | in progress | [get.md](get.md) |
| [`Set`](../../../packages/schemas/src/State/Set.php) | [`Components/Utilities/Set.php`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | in progress | [set.md](set.md) |
| [`PropertyAccessor`](../../../packages/schemas/src/State/PropertyAccessor.php) | — | done | [property-accessor.md](property-accessor.md) |
| [`RecordTypeReader`](../../../packages/schemas/src/State/RecordTypeReader.php) | — | done | [record-type-reader.md](record-type-reader.md) |
| [`AssociationReader`](../../../packages/schemas/src/State/AssociationReader.php) + [`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php) | — | done | [association-reader.md](association-reader.md) |
| [`SearchConstraint`](../../../packages/schemas/src/Query/SearchConstraint.php) | — | done | [search-constraint.md](search-constraint.md) |
| [`StateCast`](../../../packages/schemas/src/State/Casts/Contracts/StateCast.php) | [`Components/StateCasts/Contracts/StateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/Contracts/StateCast.php) | done | — |
| [`BooleanStateCast`](../../../packages/schemas/src/State/Casts/BooleanStateCast.php) | [`Components/StateCasts/BooleanStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/BooleanStateCast.php) | done | — |
| [`DateTimeStateCast`](../../../packages/schemas/src/State/Casts/DateTimeStateCast.php) | [`Components/StateCasts/DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | [date-time-state-cast.md](date-time-state-cast.md) |
| [`EnumArrayStateCast`](../../../packages/schemas/src/State/Casts/EnumArrayStateCast.php) | [`Components/StateCasts/EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | [enum-array-state-cast.md](enum-array-state-cast.md) |
| [`EnumStateCast`](../../../packages/schemas/src/State/Casts/EnumStateCast.php) | [`Components/StateCasts/EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | [enum-state-cast.md](enum-state-cast.md) |
| [`FileUploadStateCast`](../../../packages/schemas/src/State/Casts/FileUploadStateCast.php) | [`Components/StateCasts/FileUploadStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/FileUploadStateCast.php) | done | — |
| `KeyValueStateCast` | [`Components/StateCasts/KeyValueStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/KeyValueStateCast.php) | todo | — |
| `NumberStateCast` | [`Components/StateCasts/NumberStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/NumberStateCast.php) | todo | — |
| [`ObjectStateCast`](../../../packages/schemas/src/State/Casts/ObjectStateCast.php) | — | done | [object-state-cast.md](object-state-cast.md) |
| [`OptionStateCast`](../../../packages/schemas/src/State/Casts/OptionStateCast.php) | [`Components/StateCasts/OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | [option-state-cast.md](option-state-cast.md) |
| [`OptionsArrayStateCast`](../../../packages/schemas/src/State/Casts/OptionsArrayStateCast.php) | [`Components/StateCasts/OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php) | done | [options-array-state-cast.md](options-array-state-cast.md) |
| `SliderStateCast` | [`Components/StateCasts/SliderStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/SliderStateCast.php) | todo | — |
| `StripCharactersStateCast` | [`Components/StateCasts/StripCharactersStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/StripCharactersStateCast.php) | todo | — |
| [`AcceptsUploadedFiles`](../../../packages/schemas/src/Components/Contracts/AcceptsUploadedFiles.php) | — | done | [diverges](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
| [`CanConcealComponents`](../../../packages/schemas/src/Components/Contracts/CanConcealComponents.php) | [`Components/Contracts/CanConcealComponents.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanConcealComponents.php) | done | — |
| [`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) | [`HasValidationRules.php` (forms)](../../../vendor/filament/forms/src/Components/Contracts/HasValidationRules.php) | done | [can-be-validated.md](can-be-validated.md) |
| [`ValidationException`](../../../packages/schemas/src/Exceptions/ValidationException.php) | — | done | [can-be-validated.md](can-be-validated.md) |
| [`PresenceGate`](../../../packages/schemas/src/Validation/PresenceGate.php) + [`ImplicitConstraint`](../../../packages/schemas/src/Validation/Contracts/ImplicitConstraint.php) | — | done | [presence-gate.md](presence-gate.md) |
| [`ViolationPathTranslator`](../../../packages/schemas/src/State/ViolationPathTranslator.php) + [`HasKeyedItems`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php) | — | done | [violation-path-translator.md](violation-path-translator.md) |
| [`CanEntangleWithSingularRelationships`](../../../packages/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) | [`Components/Contracts/CanEntangleWithSingularRelationships.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) | done | [entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md#the-contract-carries-the-two-hook-bodies-not-the-cache-surface) |
| `ExposesStateToActionData` | [`Components/Contracts/ExposesStateToActionData.php`](../../../vendor/filament/schemas/src/Components/Contracts/ExposesStateToActionData.php) | todo | — |
| [`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php) | [`Components/Contracts/HasAffixActions.php`](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php) | done | [has-actions.md](has-actions.md#the-affix-contract-is-typed-as-a-name-keyed-map) |
| [`HasExtraItemActions`](../../../packages/schemas/src/Components/Contracts/HasExtraItemActions.php) | [`Components/Contracts/HasExtraItemActions.php`](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php) | done | [has-actions.md](has-actions.md) |
| `HasRenderHookScopes` | [`Contracts/HasRenderHookScopes.php`](../../../vendor/filament/schemas/src/Contracts/HasRenderHookScopes.php) | todo | — |
| [`HasSchemas`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Contracts/HasSchemas.php`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | in progress | [interacts-with-schemas.md](interacts-with-schemas.md) |
| [`SchemaExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) | — | done | [schema-extension.md](schema-extension.md) |
| [`Colors\Components\IconComponent`](../../../packages/schemas/src/Colors/Components/IconComponent.php) | [`View/Components/IconComponent.php`](../../../vendor/filament/schemas/src/View/Components/IconComponent.php) | done | [icon.md](icon.md#the-default-icon-color-is-gray-with-no-color-class-not-filaments-primary) |
| [`Colors\Components\TextComponent`](../../../packages/schemas/src/Colors/Components/TextComponent.php) | [`View/Components/TextComponent.php`](../../../vendor/filament/schemas/src/View/Components/TextComponent.php) | done | [text.md](text.md#the-default-gray-color-lives-in-the-color-policy-not-setup) |
| [`SchemaIconAlias`](../../../packages/schemas/src/Twig/SchemaIconAlias.php) | [`View/SchemaIconAlias.php`](../../../vendor/filament/schemas/src/View/SchemaIconAlias.php) | in progress | [tabs.md](tabs.md) |
| `TestsSchemas` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — |
| `JsContent` | [`JsContent.php`](../../../vendor/filament/schemas/src/JsContent.php) | todo | — |
| [`SchemasServiceProvider`](../../../packages/schemas/src/SylamentSchemasBundle.php) | [`SchemasServiceProvider.php`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | in progress | [schemas-bundle.md](schemas-bundle.md) |
| Commands | [`Commands/`](../../../vendor/filament/schemas/src/Commands) | todo | — |
| Commands / FileGenerators | [`Commands/FileGenerators/`](../../../vendor/filament/schemas/src/Commands/FileGenerators) | todo | — |
| Command stubs | [`stubs/`](../../../vendor/filament/schemas/stubs) | todo | — |
| [Component views](../../../packages/schemas/templates/partials) | [`resources/views/components/`](../../../vendor/filament/schemas/resources/views/components) | in progress | [component-views.md](component-views.md) |
| [Styles](../../../packages/schemas/assets/src/styles) | [`resources/css/`](../../../vendor/filament/schemas/resources/css) | in progress | [styles.md](styles.md) |
| [Scripts](../../../packages/schemas/assets/src) | [`resources/js/`](../../../vendor/filament/schemas/resources/js) | in progress | [schemas-bundle.md](schemas-bundle.md#the-schemas-package-grew-a-js-workspace-of-its-own) · [diverges](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
| Translations | [`resources/lang/`](../../../vendor/filament/schemas/resources/lang) | todo | — |
