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
component waiting on it — an escape M3 · 8 emptied for the moment, `distinct()`
having landed, so `CanFixIndistinctState` now reads **todo** in both places. That escape covers **blocked** only: a concern we have
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
packages — folding it would swamp [`field.md`](field.md). `HasEnum` keeps one for the
opposite reason: `Field` composes it, but its consumers are the option fields and
`Select`'s multiplicity-aware cast override, so it is filed
[cross-component](has-enum.md) rather than under any one of them. The same fold
applies
to every other concern only one built component uses:
`CanSelectPlaceholder`, `CanBePreloaded` and `HasLoadingMessage` are `Select`'s
alone in the port and live in [`select.md`](select.md), `HasToggleIcons` and `HasToggleColors` are
`Toggle`'s alone and live in [`toggle.md`](toggle.md), `CanBeAutocapitalized`
is `TextInput`'s alone and lives
in [`text-input.md`](text-input.md), `HasColors`, `HasIcons` and `HasTooltips`
are `ToggleButtons`' alone and live in
[`toggle-buttons.md`](toggle-buttons.md#hascolors-hasicons-and-hastooltips-are-this-fields-alone),
and `CanBeCloned` and `CanGenerateUuids` are
`Repeater`'s alone and live in
[`repeater.md`](repeater.md) — which also carries `helpers.php`, whose two
reorder functions no other component calls — while `CanBeInline` and
`CanBeAccepted` are shared by `Checkbox` and `Toggle`,
`CanLimitItemsLength` by `Repeater`, `CheckboxList` and `Select`,
`HasRelationship` by `Select` and `CheckboxList` — which ports no Filament
concern at all, being the relationship half of `Select` itself, kept as a trait
here because a fourteen-method relation surface on a field class is what
`Concerns/` exists to prevent, and whose association-resolution core split off
again as `BelongsToRelationship` once `Repeater` grew a `relationship()` of its
own, then
[moved down to schemas](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields)
once the entangled layout components needed the same lookup, leaving forms'
same-named trait as the field-flavoured adapter over it —
`HasOptions` and `CanDisableOptions` by every option field, and
`HasExtraInputAttributes` by every field that renders a control, so those keep
rows of their own. The fold reads the port, not Filament — Filament composes
`HasStep` onto `DateTimePicker` and `Slider` (and
tables' `TextInputColumn`), and `HasDatalistOptions` onto `DateTimePicker`, so
each is promoted back to a row of its own the moment the second consumer lands.
Both are that promotion now: the [date pickers](date-time-picker.md) are the
second consumer of each, so `HasStep` and `HasDatalistOptions` left
[`text-input.md`](text-input.md)'s fold and carry
[`has-step.md`](has-step.md) and
[`has-datalist-options.md`](has-datalist-options.md).
`HasOptions` and `CanDisableOptions` are the same move one milestone earlier:
`Select` folded
them until the option fields arrived, and they now carry
[`has-options.md`](has-options.md) and
[`can-disable-options.md`](can-disable-options.md).
`CanLimitItemsLength` is the same move one epic later — `Repeater`'s alone until
`CheckboxList` composed the identical trait — now carrying
[`can-limit-items-length.md`](can-limit-items-length.md) and a row below.
`CanBeAutocapitalized` and the three
`ToggleButtons` concerns are the four folds Filament agrees with — it composes
`CanBeAutocapitalized` on `TextInput` and `HasColors` / `HasIcons` /
`HasTooltips` on `ToggleButtons`, each on that class and nothing else, so no
unported consumer is queued to promote any of them.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

## Components

The `Field` base and every field a user instantiates —
[`src/Components`](../../../vendor/filament/forms/src/Components). The nested rows
(`Builder / Block`, `MorphToSelect / Type`, `Repeater / TableColumn`) are the
user-instantiable building blocks a parent field composes; `Field` heads the
table, the rest follow alphabetically. One row ports a class Filament files in
**schemas**: `FusedGroup` composes three forms concerns and renders forms'
field-wrapper chrome, so it lands here instead —
[why](../renames.md#namespace-segments).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Field`](../../../packages/forms/src/Components/Field.php) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | in progress | [field.md](field.md) |
| [`BaseFileUpload`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`Components/BaseFileUpload.php`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | in progress | [file-upload.md](file-upload.md) |
| Builder | [`Components/Builder.php`](../../../vendor/filament/forms/src/Components/Builder.php) | todo | — |
| Builder / Block | [`Components/Builder/Block.php`](../../../vendor/filament/forms/src/Components/Builder/Block.php) | todo | — |
| [`Checkbox`](../../../packages/forms/src/Components/Checkbox.php) | [`Components/Checkbox.php`](../../../vendor/filament/forms/src/Components/Checkbox.php) | in progress | [checkbox.md](checkbox.md) |
| [`CheckboxList`](../../../packages/forms/src/Components/CheckboxList.php) | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | in progress | [checkbox-list.md](checkbox-list.md) |
| CodeEditor | [`Components/CodeEditor.php`](../../../vendor/filament/forms/src/Components/CodeEditor.php) | todo | — |
| ColorPicker | [`Components/ColorPicker.php`](../../../vendor/filament/forms/src/Components/ColorPicker.php) | todo | — |
| [`DatePicker`](../../../packages/forms/src/Components/DatePicker.php) | [`Components/DatePicker.php`](../../../vendor/filament/forms/src/Components/DatePicker.php) | in progress | [date-picker.md](date-picker.md) |
| [`DateTimePicker`](../../../packages/forms/src/Components/DateTimePicker.php) | [`Components/DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | in progress | [date-time-picker.md](date-time-picker.md) |
| [`FileUpload`](../../../packages/forms/src/Components/FileUpload.php) | [`Components/FileUpload.php`](../../../vendor/filament/forms/src/Components/FileUpload.php) | in progress | [file-upload.md](file-upload.md) |
| [`FusedGroup`](../../../packages/forms/src/Components/FusedGroup.php) (Filament ships it in **schemas**) | [`schemas/…/Components/FusedGroup.php`](../../../vendor/filament/schemas/src/Components/FusedGroup.php) | in progress | [fused-group.md](fused-group.md) |
| [`Hidden`](../../../packages/forms/src/Components/Hidden.php) | [`Components/Hidden.php`](../../../vendor/filament/forms/src/Components/Hidden.php) | done | [hidden.md](hidden.md) |
| KeyValue | [`Components/KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | todo | — |
| LivewireField | [`Components/LivewireField.php`](../../../vendor/filament/forms/src/Components/LivewireField.php) | todo | — |
| MarkdownEditor | [`Components/MarkdownEditor.php`](../../../vendor/filament/forms/src/Components/MarkdownEditor.php) | todo | — |
| ModalTableSelect | [`Components/ModalTableSelect.php`](../../../vendor/filament/forms/src/Components/ModalTableSelect.php) | todo | — |
| MorphToSelect | [`Components/MorphToSelect.php`](../../../vendor/filament/forms/src/Components/MorphToSelect.php) | todo | — |
| MorphToSelect / Type | [`Components/MorphToSelect/Type.php`](../../../vendor/filament/forms/src/Components/MorphToSelect/Type.php) | todo | — |
| MultiSelect | [`Components/MultiSelect.php`](../../../vendor/filament/forms/src/Components/MultiSelect.php) | todo | — |
| OneTimeCodeInput | [`Components/OneTimeCodeInput.php`](../../../vendor/filament/forms/src/Components/OneTimeCodeInput.php) | todo | — |
| Placeholder | [`Components/Placeholder.php`](../../../vendor/filament/forms/src/Components/Placeholder.php) | todo | — |
| [`Radio`](../../../packages/forms/src/Components/Radio.php) | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | in progress | [radio.md](radio.md) |
| RelationshipRepeater | [`Components/RelationshipRepeater.php`](../../../vendor/filament/forms/src/Components/RelationshipRepeater.php) | todo | — |
| [`Repeater`](../../../packages/forms/src/Components/Repeater.php) | [`Components/Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | in progress | [repeater.md](repeater.md) |
| [`Repeater / TableColumn`](../../../packages/forms/src/Components/Repeater/TableColumn.php) | [`Components/Repeater/TableColumn.php`](../../../vendor/filament/forms/src/Components/Repeater/TableColumn.php) | done | [repeater.md](repeater.md#table-mode-pairs-columns-by-position) |
| RichEditor | [`Components/RichEditor.php`](../../../vendor/filament/forms/src/Components/RichEditor.php) | todo | — |
| [`Select`](../../../packages/forms/src/Components/Select.php) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | in progress | [select.md](select.md) |
| Slider | [`Components/Slider.php`](../../../vendor/filament/forms/src/Components/Slider.php) | todo | — |
| TableSelect | [`Components/TableSelect.php`](../../../vendor/filament/forms/src/Components/TableSelect.php) | todo | — |
| TagsInput | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | todo | — |
| [`TextInput`](../../../packages/forms/src/Components/TextInput.php) | [`Components/TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | in progress | [text-input.md](text-input.md) — gained `trim()` / `stripCharacters()` with M3 · 8 ([semantics](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag)) |
| [`Textarea`](../../../packages/forms/src/Components/Textarea.php) | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | in progress | [textarea.md](textarea.md) — gained `trim()` / `stripCharacters()` with M3 · 8 ([semantics](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag)) |
| [`TimePicker`](../../../packages/forms/src/Components/TimePicker.php) | [`Components/TimePicker.php`](../../../vendor/filament/forms/src/Components/TimePicker.php) | in progress | [time-picker.md](time-picker.md) |
| [`Toggle`](../../../packages/forms/src/Components/Toggle.php) | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | in progress | [toggle.md](toggle.md) |
| [`ToggleButtons`](../../../packages/forms/src/Components/ToggleButtons.php) | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | in progress | [toggle-buttons.md](toggle-buttons.md) |
| [`ViewField`](../../../packages/forms/src/Components/ViewField.php) | [`Components/ViewField.php`](../../../vendor/filament/forms/src/Components/ViewField.php) | done | [view-field.md](view-field.md) |

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
— `Select`'s three single-use concerns (`CanSelectPlaceholder`,
`CanBePreloaded`, `HasLoadingMessage`) into
[`select.md`](select.md), `Toggle`'s
`HasToggleIcons` and `HasToggleColors` into [`toggle.md`](toggle.md),
`TextInput`'s `CanBeAutocapitalized` into
[`text-input.md`](text-input.md), `ToggleButtons`' `HasColors`, `HasIcons` and
`HasTooltips` into [`toggle-buttons.md`](toggle-buttons.md), and
`Repeater`'s two (`CanBeCloned`, `CanGenerateUuids`)
into [`repeater.md`](repeater.md), per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). The shared
ones stay below — `CanBeLengthConstrained`, `CanBeReadOnly` and
`CanBeAutocompleted` are used by `TextInput` and `Textarea`, `CanBeInline` and
`CanBeAccepted` by `Checkbox` and `Toggle`, `BelongsToRelationship` by `Select`
and `Repeater`, `HasRelationship` by `Select` and `CheckboxList`,
`CanLimitItemsLength` by `Repeater`, `CheckboxList` and `Select`,
`CanAllowHtml` and `CanBeSearchable` by `CheckboxList` and `Select`,
`CanBeNative` by the [date pickers](date-time-picker.md) and `Select`,
`HasOptions` and `CanDisableOptions` by every
option field, and `HasExtraInputAttributes` by `TextInput`, `Textarea`,
`Select`, `Checkbox`, `Toggle`, `Radio`, `ToggleButtons` and `CheckboxList`,
and `HasStep` and `HasDatalistOptions` by `TextInput` and the
[date pickers](date-time-picker.md).
`HasEnum` stays below too, on the other test: `Field` composes it, so every
field has it.

Every concern that was listed here **ahead of** the field composing it now has
that field, and each then went one of two ways.
[`HasDescriptions`](has-descriptions.md) is `Radio`'s and `CheckboxList`'s and
[`HasGridDirection`](has-grid-direction.md) theirs and `ToggleButtons`', both
clearing the shared bar so each keeps a row and a file of its own, while
`HasColors`, `HasIcons` and `HasTooltips` landed on
[`ToggleButtons`](toggle-buttons.md#hascolors-hasicons-and-hastooltips-are-this-fields-alone)
as their sole consumer and folded out of the table entirely, exactly as the
single-use concerns above did. `CanAllowHtml` and `CanBeSearchable` kept a row
while folding their detail into [`checkbox-list.md`](checkbox-list.md) for one
epic, because their second consumer was one epic away rather than hypothetical;
the [searchable-select epic](../roadmap.md#m3--forms-widening) gave `Select`
both — `searchable()` with the server-side arm a checkbox list has no use for,
and `allowHtml()` — and each is now a shared concern with a file of its own
([can-be-searchable.md](can-be-searchable.md),
[can-allow-html.md](can-allow-html.md)), the row repointed. `CanBeNative` went
the same way one epic later than its file was written: the date pickers held it
in [`date-time-picker.md`](date-time-picker.md) until `Select` composed it, and
[can-be-native.md](can-be-native.md) now carries both the pickers' throw and the
select's branch. [`CanLimitItemsLength`](can-limit-items-length.md)
was never one of either group: `Repeater` composes it too, and `CheckboxList`
being its second consumer is what promoted it out of
[`repeater.md`](repeater.md) ahead of that field landing; `Select` is its
third.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`BelongsToRelationship`](../../../packages/forms/src/Components/Concerns/BelongsToRelationship.php) — no Filament concern of its own; the field-flavoured adapter over [schemas' association-resolution core](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php), which `Select` and `Repeater` share with the entangled layout components | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) · [`Components/Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | [select.md](select.md#relationship-is-three-lazy-closures-over-the-association-reader) · [schemas](../schemas/entangles-state-with-singular-relationship.md#the-association-core-is-shared-with-forms-relation-backed-fields) |
| [`CanAllowHtml`](../../../packages/forms/src/Components/Concerns/CanAllowHtml.php) | [`Components/Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | [can-allow-html.md](can-allow-html.md) |
| [`CanBeAccepted`](../../../packages/forms/src/Components/Concerns/CanBeAccepted.php) | [`Components/Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | [can-be-accepted.md](can-be-accepted.md) |
| [`CanBeAutocompleted`](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Components/Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | [can-be-autocompleted.md](can-be-autocompleted.md) |
| [`CanBeInline`](../../../packages/forms/src/Components/Concerns/CanBeInline.php) | [`Components/Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | [can-be-inline.md](can-be-inline.md) |
| [`CanBeLengthConstrained`](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php) | [`Components/Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | [can-be-length-constrained.md](can-be-length-constrained.md) |
| [`CanBeNative`](../../../packages/forms/src/Components/Concerns/CanBeNative.php) | [`Components/Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | done | [can-be-native.md](can-be-native.md) |
| [`CanBePreloaded`](../../../packages/forms/src/Components/Concerns/CanBePreloaded.php) | [`Components/Concerns/CanBePreloaded.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBePreloaded.php) | done | [select.md](select.md#preload-enumerates-the-limited-map-up-front) |
| [`CanBeReadOnly`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Components/Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | [can-be-read-only.md](can-be-read-only.md) |
| [`CanBeSearchable`](../../../packages/forms/src/Components/Concerns/CanBeSearchable.php) | [`Components/Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | [can-be-searchable.md](can-be-searchable.md) |
| [`CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | [can-be-validated.md](can-be-validated.md) |
| CanDisableGrammarly | [`Components/Concerns/CanDisableGrammarly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableGrammarly.php) | todo | — |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) | [`Components/Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | [can-disable-options.md](can-disable-options.md) |
| CanDisableOptionsWhenSelectedInSiblingRepeaterItems | [`Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | todo | — |
| CanFixIndistinctState | [`Components/Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | todo | — |
| [`CanLimitItemsLength`](../../../packages/forms/src/Components/Concerns/CanLimitItemsLength.php) | [`Components/Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | [can-limit-items-length.md](can-limit-items-length.md) |
| [`HasAffixes`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Components/Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | [has-affixes.md](has-affixes.md) |
| [`HasDatalistOptions`](../../../packages/forms/src/Components/Concerns/HasDatalistOptions.php) | [`Components/Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | [has-datalist-options.md](has-datalist-options.md) |
| [`HasDescriptions`](../../../packages/forms/src/Components/Concerns/HasDescriptions.php) | [`Components/Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | [has-descriptions.md](has-descriptions.md) |
| [`HasEnum`](../../../packages/forms/src/Components/Concerns/HasEnum.php) | [`Components/Concerns/HasEnum.php`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | done | [has-enum.md](has-enum.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Components/Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | [has-extra-input-attributes.md](has-extra-input-attributes.md) |
| [`HasExtraItemActions`](../../../packages/forms/src/Components/Concerns/HasExtraItemActions.php) | [`Components/Concerns/HasExtraItemActions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php) | done | [repeater.md](repeater.md#an-extra-item-action-reads-its-items-state) |
| HasFileAttachments | [`Components/Concerns/HasFileAttachments.php`](../../../vendor/filament/forms/src/Components/Concerns/HasFileAttachments.php) | todo | — |
| [`HasGridDirection`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php) | [`Components/Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | [has-grid-direction.md](has-grid-direction.md) |
| HasInputMode | [`Components/Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | [text-input.md](text-input.md#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| [`HasLoadingMessage`](../../../packages/forms/src/Components/Concerns/HasLoadingMessage.php) | [`Components/Concerns/HasLoadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasLoadingMessage.php) | done | [select.md](select.md#loadingmessage-has-no-render-site) |
| HasMaxHeight | [`Components/Concerns/HasMaxHeight.php`](../../../vendor/filament/forms/src/Components/Concerns/HasMaxHeight.php) | todo | — |
| HasMinHeight | [`Components/Concerns/HasMinHeight.php`](../../../vendor/filament/forms/src/Components/Concerns/HasMinHeight.php) | todo | — |
| [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) | [`Components/Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | [can-be-validated.md](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path) — one `All` on the field's own path where Laravel keys `<path>.*`; composed by `Select`, `CheckboxList`, `ToggleButtons` and `BaseFileUpload`, for which it lands on the collection side of the [File/collection split](file-upload.md#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Components/Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | [has-options.md](has-options.md) |
| HasPivotData | [`Components/Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | blocked | [select.md](select.md#the-save-arm-writes-a-desired-list-not-attachdetach) |
| HasPreview | [`Components/Concerns/HasPreview.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPreview.php) | todo | — |
| [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) — no Filament concern of its own; the relation surface `Select` and `CheckboxList` share, split off the class | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) · [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | [select.md](select.md#relationship-is-three-lazy-closures-over-the-association-reader) · [checkbox-list.md](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) |
| [`HasStep`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Components/Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | [has-step.md](has-step.md) |
| HasUploadingMessage | [`Components/Concerns/HasUploadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasUploadingMessage.php) | blocked | [file-upload.md](file-upload.md#uploadingmessage-has-no-render-site) |
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
| [`CanDisableOptions` (contract)](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Components/Contracts/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php) | done | [can-disable-options.md](can-disable-options.md) |
| CanHaveNumericState (contract) | [`Components/Contracts/CanHaveNumericState.php`](../../../vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php) | todo | — |
| [`HasAffixes` (contract)](../../../packages/forms/src/Components/Contracts/HasAffixes.php) | [`Components/Contracts/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Contracts/HasAffixes.php) | done | [has-affixes.md](has-affixes.md) |
| [`HasNestedRecursiveValidationConstraints` (contract)](../../../packages/forms/src/Components/Contracts/HasNestedRecursiveValidationConstraints.php) | [`Components/Contracts/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Contracts/HasNestedRecursiveValidationRules.php) | done | [can-be-validated.md](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path) |
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
| [`FormsIconAlias`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`View/FormsIconAlias.php`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | in progress | [forms-icon-alias.md](forms-icon-alias.md) |
| TestsForms | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — |
| TestsFormComponentActions | [`Testing/TestsFormComponentActions.php`](../../../vendor/filament/forms/src/Testing/TestsFormComponentActions.php) | todo | — |
| Commands / Make* | [`Commands/`](../../../vendor/filament/forms/src/Commands) | todo | — |
| Commands / FileGenerators | [`Commands/FileGenerators/`](../../../vendor/filament/forms/src/Commands/FileGenerators) | todo | — |
| FormsComponent | [`FormsComponent.php`](../../../vendor/filament/forms/src/FormsComponent.php) | todo | — |
| [`SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php) | [`FormsServiceProvider.php`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | in progress | [forms-bundle.md](forms-bundle.md) |
| [`helpers.php`](../../../packages/forms/src/helpers.php) | [`helpers.php`](../../../vendor/filament/forms/src/helpers.php) | done | [repeater.md](repeater.md) |
| [`Uploads/`](../../../packages/forms/src/Uploads) — `TemporaryUploads` and `TemporaryUploadedFile`; Sylament-only, since Filament leans on Livewire's feature rather than shipping one | [`livewire/…/SupportFileUploads/`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads) | done | [temporary-uploads.md](temporary-uploads.md) |
| [`Validation/`](../../../packages/forms/src/Validation) — the fourteen first-party constraints Symfony has no counterpart for, each with its validator; Sylament-only, since Filament builds rule strings: `ValidDate`, `DateComparison`, `MinFileSize`, `Prohibited`, `Distinct`, `MatchesField`, `StartsWith`, `EndsWith`, `Alpha`, `AlphaDash`, `AlphaNum`, `Ascii`, `ActiveUrl`, `UniqueValue`, `ExistingValue` — plus `Concerns/ProbesRecords`, the DQL probe the last two share. Every `*Validator.php` here is a **container service**, registered by one glob in the bundle | — | done | [can-be-validated.md](can-be-validated.md#unique-and-exists-are-one-dql-probe-behind-a-container-service) |
| [Styles](../../../packages/forms/assets/src/styles) | [`resources/css/`](../../../vendor/filament/forms/resources/css) | in progress | [styles.md](styles.md) — one sheet fewer since M4 · 1: the checkbox skin moved down to support as the code-less `.sy-checkbox` once tables' selection column became its second consumer, and `Checkbox` / `CheckboxList` now emit that class beside their own `sy-fo-checkbox` hook ([diverges](styles.md#the-checkbox-skin-moved-to-support-when-tables-became-its-second-consumer)) |
| [Scripts](../../../packages/forms/assets/src/controllers) | [`resources/js/`](../../../vendor/filament/forms/resources/js) | in progress | the `@sylament/forms` workspace ships three Stimulus controllers — two of vendor's twelve registered AlpineComponents, `checkbox-list` ([checkbox-list.md](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)) and `select`, a port of Filament's select utility (`utils/select.ts`, inlined into the controller bundle) bound to the host by hand ([forms-bundle.md](forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)), plus [`repeater`](../../../packages/forms/assets/src/controllers/repeater_controller.ts), which ports the Alpine object Filament embeds in the repeater's own view rather than registering as an asset: per-item collapse state as a `data-collapsed` attribute the live morph replays, collapse-all/expand-all, the validation `expand` reveal and the bridge from support's drag control to the host's `reorder` action ([forms-bundle.md](forms-bundle.md#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action)) — and it imports the `callSchemaComponentMethod` helper the select searches through from `@sylament/schemas`, which owns it since M3 · 5 ([schemas-bundle.md](../schemas/schemas-bundle.md#the-schema-component-method-helper-lives-here-now)) |
| [`utils/cloak.ts`](../../../packages/forms/assets/src/utils/cloak.ts) — the `sy-cloak` reveal the `repeater` controller calls from `connect()` | Alpine's `x-cloak` (no vendor file) | done | a byte-for-byte copy of [support's](../../../packages/support/assets/src/utils/cloak.ts), as [schemas'](../../../packages/schemas/assets/src/utils/cloak.ts) is: the per-file build ships specifiers verbatim, so a cross-package import would need an importmap entry in every consuming app ([forms-bundle.md](forms-bundle.md#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action)) |
