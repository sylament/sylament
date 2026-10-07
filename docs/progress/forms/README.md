# Forms — port trail

Ports [`filament/forms`](../../../vendor/filament/forms) into [`packages/forms`](../../../packages/forms): the `Field` base, every field built on it, their shared concerns, and validation.

## Files

<!-- trail-index:start -->
- [can-allow-html.md](can-allow-html.md)
- [can-be-accepted.md](can-be-accepted.md)
- [can-be-autocompleted.md](can-be-autocompleted.md)
- [can-be-inline.md](can-be-inline.md)
- [can-be-length-constrained.md](can-be-length-constrained.md)
- [can-be-native.md](can-be-native.md)
- [can-be-read-only.md](can-be-read-only.md)
- [can-be-searchable.md](can-be-searchable.md)
- [can-be-validated.md](can-be-validated.md)
- [can-disable-options-when-selected-in-sibling-repeater-items.md](can-disable-options-when-selected-in-sibling-repeater-items.md)
- [can-disable-options.md](can-disable-options.md)
- [can-fix-indistinct-state.md](can-fix-indistinct-state.md)
- [can-limit-items-length.md](can-limit-items-length.md)
- [checkbox-list.md](checkbox-list.md)
- [checkbox.md](checkbox.md)
- [date-comparison.md](date-comparison.md)
- [date-picker.md](date-picker.md)
- [date-time-picker.md](date-time-picker.md)
- [field.md](field.md)
- [file-upload.md](file-upload.md)
- [forms-bundle.md](forms-bundle.md)
- [forms-icon-alias.md](forms-icon-alias.md)
- [fused-group.md](fused-group.md)
- [has-affixes.md](has-affixes.md)
- [has-datalist-options.md](has-datalist-options.md)
- [has-descriptions.md](has-descriptions.md)
- [has-enum.md](has-enum.md)
- [has-extra-input-attributes.md](has-extra-input-attributes.md)
- [has-grid-direction.md](has-grid-direction.md)
- [has-options.md](has-options.md)
- [has-relationship.md](has-relationship.md)
- [has-step.md](has-step.md)
- [hidden.md](hidden.md)
- [key-value.md](key-value.md)
- [probes-records.md](probes-records.md)
- [radio.md](radio.md)
- [repeater-relationship.md](repeater-relationship.md)
- [repeater.md](repeater.md)
- [select.md](select.md)
- [styles.md](styles.md)
- [tags-input.md](tags-input.md)
- [temporary-uploads.md](temporary-uploads.md)
- [text-input.md](text-input.md)
- [textarea.md](textarea.md)
- [time-picker.md](time-picker.md)
- [toggle-buttons.md](toggle-buttons.md)
- [toggle.md](toggle.md)
- [view-field.md](view-field.md)
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`Components/Builder.php`](../../../vendor/filament/forms/src/Components/Builder.php) | todo | — | — |
| [`Components/Builder/Block.php`](../../../vendor/filament/forms/src/Components/Builder/Block.php) | todo | — | — |
| [`Components/CodeEditor.php`](../../../vendor/filament/forms/src/Components/CodeEditor.php) | todo | — | — |
| [`Components/CodeEditor/Enums/Language.php`](../../../vendor/filament/forms/src/Components/CodeEditor/Enums/Language.php) | todo | — | — |
| [`Components/ColorPicker.php`](../../../vendor/filament/forms/src/Components/ColorPicker.php) | todo | — | — |
| [`Components/LivewireField.php`](../../../vendor/filament/forms/src/Components/LivewireField.php) | todo | — | — |
| [`Components/MarkdownEditor.php`](../../../vendor/filament/forms/src/Components/MarkdownEditor.php) | todo | — | — |
| [`Components/MarkdownEditor/MarkdownFaker.php`](../../../vendor/filament/forms/src/Components/MarkdownEditor/MarkdownFaker.php) | todo | — | — |
| [`Components/ModalTableSelect.php`](../../../vendor/filament/forms/src/Components/ModalTableSelect.php) | todo | — | — |
| [`Components/MorphToSelect.php`](../../../vendor/filament/forms/src/Components/MorphToSelect.php) | skipped | — | [diverges](../schemas/association-reader.md#only-the-shapes-doctrine-states-in-one-mapping-are-read) |
| [`Components/MorphToSelect/Type.php`](../../../vendor/filament/forms/src/Components/MorphToSelect/Type.php) | skipped | — | [diverges](../schemas/association-reader.md#only-the-shapes-doctrine-states-in-one-mapping-are-read) |
| [`Components/MultiSelect.php`](../../../vendor/filament/forms/src/Components/MultiSelect.php) | skipped | — | [deprecated for `Select::multiple()`](../README.md#statuses) |
| [`Components/OneTimeCodeInput.php`](../../../vendor/filament/forms/src/Components/OneTimeCodeInput.php) | todo | — | — |
| [`Components/Placeholder.php`](../../../vendor/filament/forms/src/Components/Placeholder.php) | skipped | — | [deprecated for `TextEntry::state()`](../README.md#statuses) |
| [`Components/RelationshipRepeater.php`](../../../vendor/filament/forms/src/Components/RelationshipRepeater.php) | skipped | — | [deprecated for `Repeater::relationship()`](../README.md#statuses) |
| [`Components/RichEditor.php`](../../../vendor/filament/forms/src/Components/RichEditor.php) | todo | — | — |
| [`Components/RichEditor/`](../../../vendor/filament/forms/src/Components/RichEditor) | todo | — | — |
| [`Components/Slider.php`](../../../vendor/filament/forms/src/Components/Slider.php) | todo | — | — |
| [`Components/Slider/Enums/Behavior.php`](../../../vendor/filament/forms/src/Components/Slider/Enums/Behavior.php) | todo | — | — |
| [`Components/Slider/Enums/PipsMode.php`](../../../vendor/filament/forms/src/Components/Slider/Enums/PipsMode.php) | todo | — | — |
| [`Components/TableSelect.php`](../../../vendor/filament/forms/src/Components/TableSelect.php) | todo | — | — |
| [`Components/TableSelect/Livewire/TableSelectLivewireComponent.php`](../../../vendor/filament/forms/src/Components/TableSelect/Livewire/TableSelectLivewireComponent.php) | todo | — | — |
| [`Components/ContentFaker.php`](../../../vendor/filament/forms/src/Components/ContentFaker.php) | todo | — | — |
| [`FakerProvider.php`](../../../vendor/filament/forms/src/FakerProvider.php) | todo | — | — |
| [`Components/Concerns/HasFileAttachments.php`](../../../vendor/filament/forms/src/Components/Concerns/HasFileAttachments.php) | todo | — | — |
| [`Components/Concerns/HasMaxHeight.php`](../../../vendor/filament/forms/src/Components/Concerns/HasMaxHeight.php) | todo | — | — |
| [`Components/Concerns/HasMinHeight.php`](../../../vendor/filament/forms/src/Components/Concerns/HasMinHeight.php) | todo | — | — |
| [`Components/Concerns/HasPreview.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPreview.php) | todo | — | — |
| [`Components/Concerns/HasUploadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasUploadingMessage.php) | blocked | a form-processing channel | [detail](file-upload.md#uploadingmessage-has-no-render-site) |
| [`Components/Concerns/InteractsWithToolbarButtons.php`](../../../vendor/filament/forms/src/Components/Concerns/InteractsWithToolbarButtons.php) | todo | — | — |
| [`Concerns/InteractsWithForms.php`](../../../vendor/filament/forms/src/Concerns/InteractsWithForms.php) | todo | — | — |
| [`Contracts/HasForms.php`](../../../vendor/filament/forms/src/Contracts/HasForms.php) | todo | — | — |
| [`FormsComponent.php`](../../../vendor/filament/forms/src/FormsComponent.php) | todo | — | — |
| [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| [`Testing/TestsFormComponentActions.php`](../../../vendor/filament/forms/src/Testing/TestsFormComponentActions.php) | todo | — | — |
| [`Commands/`](../../../vendor/filament/forms/src/Commands) | todo | — | — |
| [`views/components/builder/`](../../../vendor/filament/forms/resources/views/components/builder) | todo | — | — |
| [`resources/js/components/builder.js`](../../../vendor/filament/forms/resources/js/components/builder.js) | todo | — | — |
| [`resources/js/components/code-editor.js`](../../../vendor/filament/forms/resources/js/components/code-editor.js) | todo | — | — |
| [`resources/js/components/color-picker.js`](../../../vendor/filament/forms/resources/js/components/color-picker.js) | todo | — | — |
| [`resources/js/components/markdown-editor.js`](../../../vendor/filament/forms/resources/js/components/markdown-editor.js) | todo | — | — |
| [`resources/js/components/markdown-editor/`](../../../vendor/filament/forms/resources/js/components/markdown-editor) | todo | — | — |
| [`resources/js/components/rich-editor.js`](../../../vendor/filament/forms/resources/js/components/rich-editor.js) | todo | — | — |
| [`resources/js/components/rich-editor/`](../../../vendor/filament/forms/resources/js/components/rich-editor) | todo | — | — |
| [`resources/js/components/slider.js`](../../../vendor/filament/forms/resources/js/components/slider.js) | todo | — | — |
| [`resources/lang/`](../../../vendor/filament/forms/resources/lang) | todo | — | — |
