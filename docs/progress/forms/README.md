# Forms — port progress

Ports [`filament/forms`](../../../vendor/filament/forms) into
[`packages/forms`](../../../packages/forms): the field catalog — the `Field`
base, every field built on it, their shared concerns, and validation.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `forms/src` surface end to end, so no table carries an
[unaudited row](../README.md#an-unaudited-table-says-so). The per-component audit
against Filament has now resolved every **started** row (a linked first-column
source) to a real [status](../README.md#statuses) — **done** or **in progress** —
with a detail file wherever a divergence, an open gap, or real sub-structure earns
one. A plain-text name is not built yet and stays **todo** here even where a
component's detail file marks that concern **blocked** for itself — these tables
carry no _Blocked by_ column, and a prerequisite only means anything against the
component waiting on it, so `CanBeNative` is **todo** below and **blocked** in
[`select.md`](select.md). That escape covers **blocked** only: a concern we have
decided against is **skipped** in both places, since a non-goal is not relative to
the component that reached the decision — so `HasInputMode` is **skipped** below
and in
[`text-input.md`](text-input.md#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern).

The five single-use concerns Filament composes onto the base `Field` —
`CanBeAutofocused`, `HasExtraFieldWrapperAttributes`, `HasHelperText`, `HasHint`,
and `HasPlaceholder` — have folded into [`field.md`](field.md) and left the
Features table, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). The sixth,
`CanBeMarkedAsRequired`, is no longer tracked here at all: it relocated to schemas
so its `Fieldset` could reach it, and is tracked in
[`../schemas/can-be-marked-as-required.md`](../schemas/can-be-marked-as-required.md).
`CanBeLengthConstrained`, `CanBeReadOnly` and `CanBeAutocompleted` are shared by
`TextInput` and `Textarea`, so they keep their own rows, and `CanBeValidated`
keeps one despite sitting on `Field` alone, because its Filament surface is some
eighty methods and it is the field half of a validation vertical spanning three
packages — folding it would swamp [`field.md`](field.md). The same fold applies
to every other concern only one built component uses: `HasOptions`,
`CanSelectPlaceholder`, `CanDisableOptions` and `HasRelationship` are `Select`'s
alone in the port and live in [`select.md`](select.md) — the last of those ports
no Filament concern at all, being the relationship half of `Select` itself, kept
as a trait here because a fourteen-method relation surface on a field class is
what `Concerns/` exists to prevent, and its association-resolution core split off
again as `BelongsToRelationship` once `Repeater` grew a `relationship()` of its
own — and moved down to
[schemas](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields)
once the entangled layout components needed the same lookup, leaving forms'
same-named trait as the field-flavoured adapter over it, which still keeps a
row — `HasToggleIcons` and `HasToggleColors` are
`Toggle`'s alone and live in [`toggle.md`](toggle.md), `HasStep`,
`HasDatalistOptions` and `CanBeAutocapitalized` are `TextInput`'s alone and live
in [`text-input.md`](text-input.md), and `CanBeCloned`, `CanGenerateUuids` and
`CanLimitItemsLength` are `Repeater`'s alone and live in
[`repeater.md`](repeater.md) — which also carries `helpers.php`, whose two
reorder functions no other component calls — while `CanBeInline` and
`CanBeAccepted` are shared by `Checkbox` and `Toggle`, and
`HasExtraInputAttributes` by all five fields that render a control, so those keep
rows of their own. The fold reads the port, not Filament — Filament composes
`HasOptions` and `CanDisableOptions` onto `Radio`, `CheckboxList` and
`ToggleButtons` as well, `HasStep` onto `DateTimePicker` and `Slider` (and
tables' `TextInputColumn`), and `HasDatalistOptions` onto `DateTimePicker`, so
each is promoted back to a row of its own the moment the second consumer lands.
`CanBeAutocapitalized` is the one fold Filament agrees with — it composes that
trait on `TextInput` and nothing else, so no unported consumer is queued to
promote it. [`support/component.md`](../support/component.md) is the shape each
detail file follows.

## Components

The `Field` base and every field a user instantiates —
[`src/Components`](../../../vendor/filament/forms/src/Components). The nested rows
(`Builder / Block`, `MorphToSelect / Type`, `Repeater / TableColumn`) are the
user-instantiable building blocks a parent field composes; `Field` heads the
table, the rest follow alphabetically.

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Field`](../../../packages/forms/src/Components/Field.php) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | in progress | [field.md](field.md) |
| BaseFileUpload | [`Components/BaseFileUpload.php`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | todo | — |
| Builder | [`Components/Builder.php`](../../../vendor/filament/forms/src/Components/Builder.php) | todo | — |
| Builder / Block | [`Components/Builder/Block.php`](../../../vendor/filament/forms/src/Components/Builder/Block.php) | todo | — |
| [`Checkbox`](../../../packages/forms/src/Components/Checkbox.php) | [`Components/Checkbox.php`](../../../vendor/filament/forms/src/Components/Checkbox.php) | in progress | [checkbox.md](checkbox.md) |
| CheckboxList | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | todo | — |
| CodeEditor | [`Components/CodeEditor.php`](../../../vendor/filament/forms/src/Components/CodeEditor.php) | todo | — |
| ColorPicker | [`Components/ColorPicker.php`](../../../vendor/filament/forms/src/Components/ColorPicker.php) | todo | — |
| DatePicker | [`Components/DatePicker.php`](../../../vendor/filament/forms/src/Components/DatePicker.php) | todo | — |
| DateTimePicker | [`Components/DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | todo | — |
| FileUpload | [`Components/FileUpload.php`](../../../vendor/filament/forms/src/Components/FileUpload.php) | todo | — |
| Hidden | [`Components/Hidden.php`](../../../vendor/filament/forms/src/Components/Hidden.php) | todo | — |
| KeyValue | [`Components/KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | todo | — |
| LivewireField | [`Components/LivewireField.php`](../../../vendor/filament/forms/src/Components/LivewireField.php) | todo | — |
| MarkdownEditor | [`Components/MarkdownEditor.php`](../../../vendor/filament/forms/src/Components/MarkdownEditor.php) | todo | — |
| ModalTableSelect | [`Components/ModalTableSelect.php`](../../../vendor/filament/forms/src/Components/ModalTableSelect.php) | todo | — |
| MorphToSelect | [`Components/MorphToSelect.php`](../../../vendor/filament/forms/src/Components/MorphToSelect.php) | todo | — |
| MorphToSelect / Type | [`Components/MorphToSelect/Type.php`](../../../vendor/filament/forms/src/Components/MorphToSelect/Type.php) | todo | — |
| MultiSelect | [`Components/MultiSelect.php`](../../../vendor/filament/forms/src/Components/MultiSelect.php) | todo | — |
| OneTimeCodeInput | [`Components/OneTimeCodeInput.php`](../../../vendor/filament/forms/src/Components/OneTimeCodeInput.php) | todo | — |
| Placeholder | [`Components/Placeholder.php`](../../../vendor/filament/forms/src/Components/Placeholder.php) | todo | — |
| Radio | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | todo | — |
| RelationshipRepeater | [`Components/RelationshipRepeater.php`](../../../vendor/filament/forms/src/Components/RelationshipRepeater.php) | todo | — |
| [`Repeater`](../../../packages/forms/src/Components/Repeater.php) | [`Components/Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | in progress | [repeater.md](repeater.md) |
| Repeater / TableColumn | [`Components/Repeater/TableColumn.php`](../../../vendor/filament/forms/src/Components/Repeater/TableColumn.php) | todo | — |
| RichEditor | [`Components/RichEditor.php`](../../../vendor/filament/forms/src/Components/RichEditor.php) | todo | — |
| [`Select`](../../../packages/forms/src/Components/Select.php) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | in progress | [select.md](select.md) |
| Slider | [`Components/Slider.php`](../../../vendor/filament/forms/src/Components/Slider.php) | todo | — |
| TableSelect | [`Components/TableSelect.php`](../../../vendor/filament/forms/src/Components/TableSelect.php) | todo | — |
| TagsInput | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | todo | — |
| [`TextInput`](../../../packages/forms/src/Components/TextInput.php) | [`Components/TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | in progress | [text-input.md](text-input.md) |
| [`Textarea`](../../../packages/forms/src/Components/Textarea.php) | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | in progress | [textarea.md](textarea.md) |
| TimePicker | [`Components/TimePicker.php`](../../../vendor/filament/forms/src/Components/TimePicker.php) | todo | — |
| [`Toggle`](../../../packages/forms/src/Components/Toggle.php) | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | in progress | [toggle.md](toggle.md) |
| ToggleButtons | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | todo | — |
| ViewField | [`Components/ViewField.php`](../../../vendor/filament/forms/src/Components/ViewField.php) | todo | — |

## Features

Cross-cutting field concerns —
[`src/Components/Concerns`](../../../vendor/filament/forms/src/Components/Concerns)
(field-level) — plus the host-level
[`src/Concerns`](../../../vendor/filament/forms/src/Concerns) trait a
Livewire/LiveComponent host uses to resolve form schemas. A concern used by a
single component is tracked in that component's detail file, not here: the base
`Field`'s five started concerns (`CanBeAutofocused`,
`HasExtraFieldWrapperAttributes`, `HasHelperText`, `HasHint`,
`HasPlaceholder`) have folded into
[`field.md`](field.md) — a sixth, `CanBeMarkedAsRequired`, relocated to schemas
and is tracked in
[`../schemas/can-be-marked-as-required.md`](../schemas/can-be-marked-as-required.md)
— `Select`'s four (`HasOptions`, `CanSelectPlaceholder`,
`CanDisableOptions`, `HasRelationship`) into [`select.md`](select.md), `Toggle`'s
`HasToggleIcons` and `HasToggleColors` into [`toggle.md`](toggle.md),
`TextInput`'s `HasStep`, `HasDatalistOptions` and `CanBeAutocapitalized` into
[`text-input.md`](text-input.md), and
`Repeater`'s three (`CanBeCloned`, `CanGenerateUuids`, `CanLimitItemsLength`)
into [`repeater.md`](repeater.md), per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). The shared
ones stay below — `CanBeLengthConstrained`, `CanBeReadOnly` and
`CanBeAutocompleted` are used by `TextInput` and `Textarea`, `CanBeInline` and
`CanBeAccepted` by `Checkbox` and `Toggle`, `BelongsToRelationship` by `Select`
and `Repeater`, and `HasExtraInputAttributes` by
`TextInput`, `Textarea`, `Select`, `Checkbox` and `Toggle`.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`BelongsToRelationship`](../../../packages/forms/src/Components/Concerns/BelongsToRelationship.php) — no Filament concern of its own; the field-flavoured adapter over [schemas' association-resolution core](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php), which `Select` and `Repeater` share with the entangled layout components | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) · [`Components/Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | [select.md](select.md#relationship-is-three-lazy-closures-over-the-association-reader) · [schemas](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields) |
| CanAllowHtml | [`Components/Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | todo | — |
| [`CanBeAccepted`](../../../packages/forms/src/Components/Concerns/CanBeAccepted.php) | [`Components/Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | [can-be-accepted.md](can-be-accepted.md) |
| [`CanBeAutocompleted`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Components/Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | [can-be-autocompleted.md](can-be-autocompleted.md) |
| [`CanBeInline`](../../../packages/forms/src/Components/Concerns/CanBeInline.php) | [`Components/Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | [can-be-inline.md](can-be-inline.md) |
| [`CanBeLengthConstrained`](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php) | [`Components/Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | [can-be-length-constrained.md](can-be-length-constrained.md) |
| CanBeNative | [`Components/Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | todo | — |
| CanBePreloaded | [`Components/Concerns/CanBePreloaded.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBePreloaded.php) | todo | — |
| [`CanBeReadOnly`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Components/Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | [can-be-read-only.md](can-be-read-only.md) |
| CanBeSearchable | [`Components/Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | todo | — |
| [`CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | in progress | [can-be-validated.md](can-be-validated.md) |
| CanDisableGrammarly | [`Components/Concerns/CanDisableGrammarly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableGrammarly.php) | todo | — |
| CanDisableOptionsWhenSelectedInSiblingRepeaterItems | [`Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | todo | — |
| CanFixIndistinctState | [`Components/Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | todo | — |
| HasAffixes | [`Components/Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | todo | — |
| HasColors | [`Components/Concerns/HasColors.php`](../../../vendor/filament/forms/src/Components/Concerns/HasColors.php) | todo | — |
| HasDescriptions | [`Components/Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | todo | — |
| HasEnum | [`Components/Concerns/HasEnum.php`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | todo | — |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Components/Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | [has-extra-input-attributes.md](has-extra-input-attributes.md) |
| HasExtraItemActions | [`Components/Concerns/HasExtraItemActions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php) | todo | — |
| HasFileAttachments | [`Components/Concerns/HasFileAttachments.php`](../../../vendor/filament/forms/src/Components/Concerns/HasFileAttachments.php) | todo | — |
| HasGridDirection | [`Components/Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | todo | — |
| HasIcons | [`Components/Concerns/HasIcons.php`](../../../vendor/filament/forms/src/Components/Concerns/HasIcons.php) | todo | — |
| HasInputMode | [`Components/Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | [text-input.md](text-input.md#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| HasLoadingMessage | [`Components/Concerns/HasLoadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasLoadingMessage.php) | todo | — |
| HasMaxHeight | [`Components/Concerns/HasMaxHeight.php`](../../../vendor/filament/forms/src/Components/Concerns/HasMaxHeight.php) | todo | — |
| HasMinHeight | [`Components/Concerns/HasMinHeight.php`](../../../vendor/filament/forms/src/Components/Concerns/HasMinHeight.php) | todo | — |
| HasNestedRecursiveValidationRules | [`Components/Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | todo | — |
| HasPivotData | [`Components/Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | blocked | [select.md](select.md#the-save-arm-writes-a-desired-list-not-attachdetach) |
| HasPreview | [`Components/Concerns/HasPreview.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPreview.php) | todo | — |
| [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) — no Filament concern of its own; `Select`'s relation surface, split off the class | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | [select.md](select.md#relationship-is-three-lazy-closures-over-the-association-reader) |
| HasTooltips | [`Components/Concerns/HasTooltips.php`](../../../vendor/filament/forms/src/Components/Concerns/HasTooltips.php) | todo | — |
| HasUploadingMessage | [`Components/Concerns/HasUploadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasUploadingMessage.php) | todo | — |
| InteractsWithForms | [`Concerns/InteractsWithForms.php`](../../../vendor/filament/forms/src/Concerns/InteractsWithForms.php) | todo | — |
| InteractsWithToolbarButtons | [`Components/Concerns/InteractsWithToolbarButtons.php`](../../../vendor/filament/forms/src/Components/Concerns/InteractsWithToolbarButtons.php) | todo | — |

## Supporting surface

Everything with no field class of its own: the field contracts, the nested
enums, the RichEditor infrastructure subtree, the prebuilt affix/toolbar
actions, the Livewire table-select component, the icon-alias registry, the test
helpers, the `make:` commands and their file generators, the component
stylesheets, and the bundle wiring —
[`src/Contracts`](../../../vendor/filament/forms/src/Contracts),
[`src/Components/Contracts`](../../../vendor/filament/forms/src/Components/Contracts),
[`src/Testing`](../../../vendor/filament/forms/src/Testing),
[`src/View`](../../../vendor/filament/forms/src/View),
[`src/Commands`](../../../vendor/filament/forms/src/Commands). A row named for a
directory collapses that whole subtree into one navigational entry.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| HasForms | [`Contracts/HasForms.php`](../../../vendor/filament/forms/src/Contracts/HasForms.php) | todo | — |
| [`CanBeLengthConstrained` (contract)](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php) | [`Components/Contracts/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Contracts/CanBeLengthConstrained.php) | done | [can-be-length-constrained.md](can-be-length-constrained.md) |
| [`CanDisableOptions` (contract)](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Components/Contracts/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php) | done | [select.md](select.md) |
| CanHaveNumericState (contract) | [`Components/Contracts/CanHaveNumericState.php`](../../../vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php) | todo | — |
| HasAffixes (contract) | [`Components/Contracts/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Contracts/HasAffixes.php) | todo | — |
| HasNestedRecursiveValidationRules (contract) | [`Components/Contracts/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Contracts/HasNestedRecursiveValidationRules.php) | todo | — |
| HasValidationRules (contract) → [`Schemas\Components\Contracts\HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) | [`Components/Contracts/HasValidationRules.php`](../../../vendor/filament/forms/src/Components/Contracts/HasValidationRules.php) | done | [schemas/can-be-validated.md](../schemas/can-be-validated.md#the-component-contract-is-relocated-from-forms-into-schemas) |
| Language | [`Components/CodeEditor/Enums/Language.php`](../../../vendor/filament/forms/src/Components/CodeEditor/Enums/Language.php) | todo | — |
| Behavior | [`Components/Slider/Enums/Behavior.php`](../../../vendor/filament/forms/src/Components/Slider/Enums/Behavior.php) | todo | — |
| PipsMode | [`Components/Slider/Enums/PipsMode.php`](../../../vendor/filament/forms/src/Components/Slider/Enums/PipsMode.php) | todo | — |
| TextInput / Actions | [`Components/TextInput/Actions/`](../../../vendor/filament/forms/src/Components/TextInput/Actions) | todo | — |
| RichEditor / Actions | [`Components/RichEditor/Actions/`](../../../vendor/filament/forms/src/Components/RichEditor/Actions) | todo | — |
| RichEditor / TipTapExtensions | [`Components/RichEditor/TipTapExtensions/`](../../../vendor/filament/forms/src/Components/RichEditor/TipTapExtensions) | todo | — |
| RichEditor / Plugins | [`Components/RichEditor/Plugins/`](../../../vendor/filament/forms/src/Components/RichEditor/Plugins) | todo | — |
| RichEditor / Models | [`Components/RichEditor/Models/`](../../../vendor/filament/forms/src/Components/RichEditor/Models) | todo | — |
| RichEditor / rich content services | [`Components/RichEditor/`](../../../vendor/filament/forms/src/Components/RichEditor) | todo | — |
| TableSelectLivewireComponent | [`Components/TableSelect/Livewire/TableSelectLivewireComponent.php`](../../../vendor/filament/forms/src/Components/TableSelect/Livewire/TableSelectLivewireComponent.php) | todo | — |
| FormsIconAlias | [`View/FormsIconAlias.php`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | todo | — |
| TestsForms | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — |
| TestsFormComponentActions | [`Testing/TestsFormComponentActions.php`](../../../vendor/filament/forms/src/Testing/TestsFormComponentActions.php) | todo | — |
| Commands / Make* | [`Commands/`](../../../vendor/filament/forms/src/Commands) | todo | — |
| Commands / FileGenerators | [`Commands/FileGenerators/`](../../../vendor/filament/forms/src/Commands/FileGenerators) | todo | — |
| FormsComponent | [`FormsComponent.php`](../../../vendor/filament/forms/src/FormsComponent.php) | todo | — |
| [`SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php) | [`FormsServiceProvider.php`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | in progress | [forms-bundle.md](forms-bundle.md) |
| [`helpers.php`](../../../packages/forms/src/helpers.php) | [`helpers.php`](../../../vendor/filament/forms/src/helpers.php) | done | [repeater.md](repeater.md) |
| [Styles](../../../packages/forms/assets/src/styles) | [`resources/css/`](../../../vendor/filament/forms/resources/css) | in progress | [styles.md](styles.md) |
