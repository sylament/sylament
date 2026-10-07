---
description: The forms stylesheets no component file owns, the Filament forms and support stylesheets they port, and the forms stylesheets with no port yet.
ports:
  - vendor/filament/forms/resources/css/components/builder.css
  - vendor/filament/forms/resources/css/components/checkbox-list.css
  - vendor/filament/forms/resources/css/components/code-editor.css
  - vendor/filament/forms/resources/css/components/color-picker.css
  - vendor/filament/forms/resources/css/components/date-time-picker.css
  - vendor/filament/forms/resources/css/components/field.css
  - vendor/filament/forms/resources/css/components/file-upload/filepond-plugin-media-preview.css
  - vendor/filament/forms/resources/css/components/markdown-editor.css
  - vendor/filament/forms/resources/css/components/modal-table-select.css
  - vendor/filament/forms/resources/css/components/radio.css
  - vendor/filament/forms/resources/css/components/repeater.css
  - vendor/filament/forms/resources/css/components/rich-editor.css
  - vendor/filament/forms/resources/css/components/select.css
  - vendor/filament/forms/resources/css/components/slider.css
  - vendor/filament/forms/resources/css/components/tags-input.css
  - vendor/filament/forms/resources/css/components/text-input.css
  - vendor/filament/forms/resources/css/components/textarea.css
  - vendor/filament/forms/resources/css/components/toggle-buttons.css
  - vendor/filament/support/resources/css/components/input/checkbox.css
  - vendor/filament/support/resources/css/components/input/index.css
  - vendor/filament/support/resources/css/components/input/one-time-code.css
  - vendor/filament/support/resources/css/components/input/radio.css
  - vendor/filament/support/resources/css/components/input/select.css
  - vendor/filament/support/resources/css/components/toggle.css
owns:
  - packages/forms/assets/src/styles/components/checkbox-list.css
  - packages/forms/assets/src/styles/components/field.css
  - packages/forms/assets/src/styles/components/input.css
  - packages/forms/assets/src/styles/components/radio.css
  - packages/forms/assets/src/styles/components/repeater.css
  - packages/forms/assets/src/styles/components/select.css
  - packages/forms/assets/src/styles/components/tags-input.css
  - packages/forms/assets/src/styles/components/text-input.css
  - packages/forms/assets/src/styles/components/toggle-buttons.css
  - packages/forms/assets/src/styles/components/toggle.css
  - packages/forms/assets/src/styles/index.css
  - packages/support/assets/src/styles/components/checkbox.css
---
# Styles (forms)

The stylesheets under [`packages/forms/assets/src/styles/`](../../../packages/forms/assets/src/styles/) that no component file owns; `file-upload.css`, `fused-group.css` and `key-value.css` are in [file-upload.md](file-upload.md), [fused-group.md](fused-group.md) and [key-value.md](key-value.md).
Every stylesheet in Filament's [`forms/resources/css/components/`](../../../vendor/filament/forms/resources/css/components/) has a row here or in the file that owns its port, and so does every file in support's `input/` directory but `wrapper.css`, which is [input-wrapper.md](../support/input-wrapper.md)'s.
**The control chrome ports from [`support/resources/css/`](../../../vendor/filament/support/resources/css/):** Filament files the raw controls (checkbox, input, radio, select, toggle) in its *support* package and keeps only the field-and-wrapper layer in forms, so `select.css` and `toggle.css` have no counterpart in the forms tree, `input.css` is support's chrome plus vendor's forms `textarea.css` and one `date-time-picker.css` rule, and `radio.css` draws on forms only for its option set.
Diffed against the forms tree, each of them reads as drift from beginning to end.
`toggle-buttons.css` and `checkbox-list.css` are wholly forms', because their controls borrow support's button chrome (`toggle-buttons.css`) or the shared checkbox skin (`checkbox-list.css`) by class rather than restyling either.

Each stylesheet lands in `components/<name>.css` and is `@import`ed into [`index.css`](../../../packages/forms/assets/src/styles/index.css) under `layer(components)`, mirroring Filament's `index.css`; the two generated FilePond and Cropper.js sheets sit at the top of `src/styles/` (the gitignore glob is one level deep) and are imported first, so the skin that overrides them comes later in the same layer.
Forms' `assets/dist` carries its controllers and no stylesheet: support's [`index.css`](../../../packages/support/assets/src/styles/index.css) imports this aggregator, so these rules compile into support's single built stylesheet and reach a consumer through `@sylament/support`.
The `fi-*` class prefix is translated to the forms component code `sy-fo-*` (see the [CSS contract](../../internals/styling.md#why-do-templates-carry-only-semantic-classes)), which for the support-tier ports is [more than a prefix swap](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`checkbox-list.css`](../../../packages/forms/assets/src/styles/components/checkbox-list.css): the option set, its search row and its no-results line | [`components/checkbox-list.css`](../../../vendor/filament/forms/resources/css/components/checkbox-list.css) | done | — | [semantics](#a-rows-status-reads-the-vendor-stylesheet-and-no-test-renders-css) · [diverges](#the-checkbox-skin-lives-in-support-because-tables-renders-it-too) |
| [`checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css) in support: `.sy-checkbox` | [`support/…/input/checkbox.css`](../../../vendor/filament/support/resources/css/components/input/checkbox.css) | done | — | [diverges](#the-checkbox-skin-lives-in-support-because-tables-renders-it-too) · [diverges](#the-checkbox-and-radio-pin-their-checked-glyphs-size-and-placement) |
| [`input.css`](../../../packages/forms/assets/src/styles/components/input.css): `.sy-fo-input::-webkit-datetime-edit` | [`components/date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css) | in progress | — | [detail](#the-date-time-picker-stylesheet-is-one-native-rule-and-a-calendar-panel) |
| [`field.css`](../../../packages/forms/assets/src/styles/components/field.css): the `.sy-fo-field` wrapper grid, label column, content column and error list | [`components/field.css`](../../../vendor/filament/forms/resources/css/components/field.css) | in progress | — | [detail](#the-field-stylesheet-omits-the-leading-schema-un-grow) · [detail](#the-inline-label-arms-wait-on-schemas-hasinlinelabel) · [diverges](#field-css-is-flat-where-filament-nests) · [diverges](#most-screen-reader-markup-still-inlines-the-utility-instead-of-the-shared-class) |
| [`input.css`](../../../packages/forms/assets/src/styles/components/input.css): `.sy-fo-input`, the text control for `TextInput`, `Textarea` and the three pickers | [`support/…/input/index.css`](../../../vendor/filament/support/resources/css/components/input/index.css) · [`components/textarea.css`](../../../vendor/filament/forms/resources/css/components/textarea.css) | in progress | — | [detail](#the-input-stylesheet-stops-at-the-alignment-arms) · [detail](#the-inline-label-arms-wait-on-schemas-hasinlinelabel) · [diverges](#the-textarea-rule-is-the-ports-own-and-autosize-overrides-it) |
| [`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css): the `.sy-fo-radio` option set and the `.sy-fo-radio-input` control | [`components/radio.css`](../../../vendor/filament/forms/resources/css/components/radio.css) · [`support/…/input/radio.css`](../../../vendor/filament/support/resources/css/components/input/radio.css) | done | — | [diverges](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes) · [diverges](#the-checkbox-and-radio-pin-their-checked-glyphs-size-and-placement) · [diverges](radio.md#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) |
| [`repeater.css`](../../../packages/forms/assets/src/styles/components/repeater.css): the `.sy-fo-repeater`, `.sy-fo-simple-repeater` and `.sy-fo-table-repeater` item chrome | [`components/repeater.css`](../../../vendor/filament/forms/resources/css/components/repeater.css) | in progress | — | [detail](#the-repeater-stylesheet-waits-on-infolists-for-its-entry-rules) · [semantics](repeater.md#the-items-grid-is-supports-grid) |
| [`select.css`](../../../packages/forms/assets/src/styles/components/select.css): `.sy-fo-select`, the native control and its chevron, and the skin the JS control renders into | [`support/…/input/select.css`](../../../vendor/filament/support/resources/css/components/input/select.css) | done | — | [diverges](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes) · [diverges](#the-js-select-skin-is-filaments-on-the-ported-controls-container) |
| Forms' `select.css` rules: `.fi-fo-select .fi-hidden` and the `.fi-fo-select-wrp` inline-label arm | [`components/select.css`](../../../vendor/filament/forms/resources/css/components/select.css) | blocked | schemas' `HasInlineLabel` | [detail](#two-vendor-stylesheets-are-partly-rather-than-wholly-unported) |
| [`tags-input.css`](../../../packages/forms/assets/src/styles/components/tags-input.css): the chip container and its reorderable chips | [`components/tags-input.css`](../../../vendor/filament/forms/resources/css/components/tags-input.css) | in progress | — | [detail](#the-inline-label-arms-wait-on-schemas-hasinlinelabel) · [diverges](tags-input.md#the-alpine-data-object-becomes-a-stimulus-controller-over-a-cloned-twig-chip) · [diverges](tags-input.md#an-empty-list-keeps-the-container-and-drops-its-chrome) |
| [`text-input.css`](../../../packages/forms/assets/src/styles/components/text-input.css): the wrapper, the revealable input and the copy status region | [`components/text-input.css`](../../../vendor/filament/forms/resources/css/components/text-input.css) | in progress | — | [detail](#two-vendor-stylesheets-are-partly-rather-than-wholly-unported) |
| [`toggle-buttons.css`](../../../packages/forms/assets/src/styles/components/toggle-buttons.css): the option set and its hidden inputs | [`components/toggle-buttons.css`](../../../vendor/filament/forms/resources/css/components/toggle-buttons.css) | in progress | — | [detail](#the-toggle-buttons-stylesheet-omits-the-full-width-arms-and-the-groups-max-w-full) · [detail](#the-inline-label-arms-wait-on-schemas-hasinlinelabel) · [semantics](toggle-buttons.md#the-grouped-labels-must-stay-direct-children-of-the-group) · [semantics](toggle-buttons.md#the-selected-state-is-painted-from-the-sibling-input-in-both-variants) · [diverges](toggle-buttons.md#extraattributes-stays-on-the-field-wrapper) |
| [`toggle.css`](../../../packages/forms/assets/src/styles/components/toggle.css): the `.sy-fo-toggle` root, track, thumb and icon pair | [`support/…/components/toggle.css`](../../../vendor/filament/support/resources/css/components/toggle.css) | done | — | [diverges](toggle.md#the-toggle-is-a-styled-checkbox-not-a-button) · [diverges](toggle.md#the-icon-rules-select-by-name-where-filament-selects-by-position) · [diverges](toggle.md#the-two-colours-ride-on-two-elements) |
| `builder.css` / `code-editor.css` / `color-picker.css` / `markdown-editor.css` / `modal-table-select.css` / `rich-editor.css` / `slider.css` | [`components/`](../../../vendor/filament/forms/resources/css/components/) | blocked | `Builder`, `CodeEditor`, `ColorPicker`, `MarkdownEditor`, `ModalTableSelect`, `RichEditor`, `Slider` | [detail](#the-unported-stylesheets-wait-on-what-they-style) |
| `file-upload/filepond-plugin-media-preview.css` | [`components/file-upload/filepond-plugin-media-preview.css`](../../../vendor/filament/forms/resources/css/components/file-upload/filepond-plugin-media-preview.css) | blocked | FilePond's media-preview plugin in the bundle | [detail](#the-unported-stylesheets-wait-on-what-they-style) |
| `input/one-time-code.css` | [`support/…/input/one-time-code.css`](../../../vendor/filament/support/resources/css/components/input/one-time-code.css) | blocked | `OneTimeCodeInput` | [detail](#the-unported-stylesheets-wait-on-what-they-style) |

## Details

### The repeater stylesheet waits on infolists for its entry rules

Every rule of vendor's `repeater.css` is ported except the arms keyed off infolists classes, in the table-mode compact block and its no-container-query copy: `.fi-in-entry` / `.fi-in-entry-label` (the entry's own gap and label, which the compact table hides the same way it hides a field's) and `.fi-in-entry-content` (its horizontal padding).
[`infolists`](../infolists/README.md) ships a bundle class and nothing else, so there is no `sy-in-*` name to hang them on.
Their siblings are ported (`.sy-fo-field`, `.sy-fo-field-label-content`, `.sy-fo-toggle-buttons .sy-fo-field-content-col` and `.sy-fo-radio`), so the unported rules are exactly the infolists half; the toggle-buttons arm, though, sits on `.sy-fo-toggle-buttons`, the element inside the content column, so it cannot match until the port has a wrapper hook for it ([field.md](field.md#extra-attribute-precedence-is-fixed-by-source-order)).
Add the rules in the same change that gives infolists an entry partial.

### The field stylesheet omits the leading-schema un-grow

Filament's `field.css` un-grows a schema rendered first inside the label container: `& > .fi-sc:nth-child(1) { @apply grow-0 }`.
The port has no counterpart, and the rule is reachable rather than hypothetical: [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) renders `beforeLabelSchema` as the first child of `.sy-fo-field-label-ctn`, and an inline schema carries `flex grow` from [`schema.css`](../../../packages/schemas/assets/src/styles/components/schema.css).
So `beforeLabel()` stretches across the label row here and does not in Filament.

`.fi-fo-field-label.fi-hidden { @apply hidden }` is not a gap: no hidden placeholder is rendered anywhere in the port, so the selector can never match, the same DOM fact schemas records for the [whole schema render](../schemas/schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders).

### The inline-label arms wait on schemas' HasInlineLabel

Vendor's `field.css`, `select.css`, `tags-input.css`, `textarea.css` and `toggle-buttons.css` each carry an arm under `.fi-fo-field-has-inline-label`: the `sm:grid-cols-3` label/content split and its `sm:col-span-2` content column in `field.css`, and an `sm:pt-1.5` on the label column in the others.
None is ported, because nothing emits `sy-fo-field-has-inline-label`: schemas' `HasInlineLabel` is unported, which [field.md](field.md)'s field-wrapper-rendering row records.
`field.css`'s three `.fi-fo-field-label-col.fi-vertical-align-*` arms wait on the same concern, because Filament emits that class only beside an inline label (`vendor/filament/forms/src/Components/Field.php`, the `fi-vertical-align-*` class keyed on `$hasInlineLabel`).
`key-value.css` carries the same arm, [recorded in key-value.md](key-value.md#the-inline-label-rule-waits-on-hasinlinelabel).
Each returns with the concern, not with a CSS change.

### The input stylesheet stops at the alignment arms

Both text controls are transparent, with the ring, background and `focus-within` state on support's [`.sy-input-wrp`](../support/input-wrapper.md), the shape [field.md](field.md#the-wrapper-owns-the-chrome-and-the-control-is-transparent) records and where the two type-specific `input[type=…]` rules are ruled on.
The affix hooks (`.sy-fo-input-has-inline-prefix`, its suffix twin and `.sy-fo-select-has-inline-prefix`) are ported, so what is left unported is:

- **`input.fi-input`'s six `.fi-align-*` arms** (`center`, `end`, `left`, `right`, `justify`, `between`).
  No field exposes an alignment setter and Filament's own fields do not emit the class, but Filament's `TextInputColumn` emits it on its `<input>` ([text-input-column.md](../tables/text-input-column.md#alignment-lands-on-the-cell-root-where-no-rule-aligns-the-input)); a consumer reaches those arms through `extraInputAttributes()`, which [the port also appends to the class list](has-extra-input-attributes.md).
  So the hook is reachable here and the rules behind it are not, which makes this the arm a consumer can trip over.
- **One of the three per-field wrapper rules has no element.**
  Vendor puts two of them on the input wrapper itself and the third on a `<div>` outside it.
  `.fi-fo-textarea { overflow-hidden }` is ported onto the textarea's own wrapper as [`.sy-fo-textarea`](textarea.md#the-control-keeps-the-sy-fo-input-class-filaments-textarea-carries-none).
  `.fi-fo-text-input { overflow-hidden }` has an element too, the text input's `.sy-input-wrp`, and is [held back](#two-vendor-stylesheets-are-partly-rather-than-wholly-unported) with the rest of `text-input.css`.
  Only `.fi-fo-select-wrp` has nothing to attach to: it styles a second `<div>`, outside the input wrapper, and waits on schemas' `HasInlineLabel` with the rule that uses it.

### Two vendor stylesheets are partly rather than wholly unported

`text-input.css` holds two rules.
`& input.fi-revealable { @apply [&::-ms-reveal]:hidden }` suppresses Edge's native password-reveal glyph for a field that ships its own reveal button; it is ported with [`revealable()`](text-input.md#a-revealable-input-is-rendered-as-a-password-server-side) as `& input.sy-revealable` in forms' [`text-input.css`](../../../packages/forms/assets/src/styles/components/text-input.css), beside the two `sy-revealed` visibility arms and the copy-confirmation region, neither of which vendor has a counterpart for.

The other, `.fi-fo-text-input { @apply overflow-hidden }`, is held back on purpose: the copy confirmation is an absolutely positioned `role="status"` span that sits *above* the wrapper box (`bottom-full`) and is always rendered, hidden only by `opacity` while `:empty` ([detail](text-input.md#copy-confirms-through-a-status-region-not-a-tooltip)), so `overflow: hidden` on `.sy-fo-text-input` would clip it.
Filament does not hit this because tippy portals its tooltip to the body.
Porting the rule means moving the status region out of the wrapper first.

Vendor's forms `select.css` holds two more.
`.fi-fo-select .fi-hidden { @apply hidden }` can never match, for the hidden-placeholder reason [above](#the-field-stylesheet-omits-the-leading-schema-un-grow); and `.fi-fo-select-wrp.fi-fo-field-has-inline-label .fi-fo-field-label-col { sm:pt-1.5 }` needs both the select wrapper element and schemas' `HasInlineLabel`, so it waits on the later of the two.

### The date-time picker stylesheet is one native rule and a calendar panel

Vendor's `date-time-picker.css` is a single `.fi-fo-date-time-picker` block, and everything nested inside it except one rule styles the **JS calendar**: the trigger button, the display text input, the panel, its month select and year input, the calendar grid and its day states, and the time inputs.
All of it is [blocked on the calendar vertical](date-time-picker.md#the-js-calendar-family-waits-on-a-js-vertical-of-its-own), which is a JS build before it is a stylesheet.

The exception is `& input::-webkit-datetime-edit { display: block; padding: 0 }`, which fixes an excessive height WebKit gives a **native** date control.
That one is ported into [`input.css`](../../../packages/forms/assets/src/styles/components/input.css) rather than into a `date-time-picker.css` of its own: the port's three pickers render their `<input class="sy-fo-input">` inside support's `.sy-input-wrp` like every other control and add no picker-specific class, so there is no `.sy-fo-date-time-picker` for the rule to nest under.
It is written flat as `.sy-fo-input::-webkit-datetime-edit`, which is harmless on the other controls, because the pseudo-element exists only on date and time inputs.
The file appears when the calendar does, and this rule moves into it if the calendar brings a wrapper element with it.

### The unported stylesheets wait on what they style

`builder.css`, `code-editor.css`, `color-picker.css`, `markdown-editor.css`, `modal-table-select.css`, `rich-editor.css` and `slider.css` have no `sy-fo-*` counterpart, because the styling lands in the same change as the Twig template it targets and none of those fields exists.

`file-upload/filepond-plugin-media-preview.css` is nested under a stylesheet that *is* ported, and waits on the media-preview plugin rather than on a field: `FileUpload` bundles FilePond's core with the two validators (`file-validate-size`, `file-validate-type`) and the six image plugins (`image-crop`, `image-edit`, `image-exif-orientation`, `image-preview`, `image-resize`, `image-transform`), so audio and video items render as file cards with no player.
Adding the plugin is a decision of its own.

Support's `input/one-time-code.css` waits on `OneTimeCodeInput`.
It is listed here rather than in a support file because a control's chrome arrives on the forms side, as `input/radio.css` does inside forms' [`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css).

### The toggle-buttons stylesheet omits the full-width arms and the group's `max-w-full`

Vendor's `toggle-buttons.css` caps the grouped row at `max-w-full` and, under `fi-width-full`, stretches the group, grows the inline option containers and widens every button.
The port's [`toggle-buttons.css`](../../../packages/forms/assets/src/styles/components/toggle-buttons.css) gives the group `w-max` alone and has no `sy-width-full` arm, which waits on the field's [`fullWidth()`](toggle-buttons.md#size-and-fullwidth-are-not-ported).

## Semantics

### A row's status reads the vendor stylesheet, and no test renders CSS

A status in this table reads the **vendor stylesheet**: how much of its rule set has a counterpart.
That is a different question from a component file's template-and-stylesheet row, which reads the port's built surface, so `repeater.css` is **in progress** here while [repeater.md](repeater.md) marks its template row **done**, and neither is wrong.

No test exercises CSS.
The render tests (`FieldWrapperTest`, `SelectTest`, `CheckboxTest`, `RadioTest`, `ToggleTest`, `RepeaterRenderTest` and their siblings) assert that the class hooks these rules attach to are emitted, but the styling itself never runs, so a row's **done** rests on the faithfulness of the port.
One stylesheet is under an automated check of a variable contract rather than a rendered pixel: [`ToggleColorSlotsTest`](../../../packages/forms/tests/Architecture/ToggleColorSlotsTest.php) scans `toggle.css` and fails if the slot variables its checked and unchecked rules read are not exactly the ones [`ToggleComponent`](../../../packages/support/src/Colors/Components/ToggleComponent.php) resolves.
Everything else is guarded only by the CI check that support's committed `assets/dist` matches a fresh build.

## Divergences

### The control stylesheets carry forms-coded names for support-tier classes

`input.css`, `radio.css`, `select.css` and `toggle.css` port `input.fi-input`, `input[type='radio'].fi-radio-input`, `select.fi-select-input` and `.fi-toggle`, support-package classes, which [carry no package code](../renames.md) by the port's own naming rule (`fi-icon-btn` → `sy-icon-btn`).
The port names them `.sy-fo-input`, `.sy-fo-radio-input`, `.sy-fo-select` and `.sy-fo-toggle` instead, so a support-tier class arrives under the forms code and in a forms stylesheet.
It is the same tracks-its-emitter call schemas records in the other direction for [`text.css`](../schemas/styles.md#text-css-was-ported-into-the-support-package): filing a rule beside the template that emits it beats filing it beside the vendor that authored it.
The re-coding reverses when a second package renders one of these controls, as the [checkbox skin's](#the-checkbox-skin-lives-in-support-because-tables-renders-it-too) did; tables' editable cells reuse the toggle, input and select classes, and that move is owed there.

The radio keeps its `-input` suffix where the checkbox drops one, and that is not an inconsistency to tidy: `.sy-fo-radio` is taken by the option-set container the field renders around the controls, which the checkbox, a single element with no container, has no equivalent of.
Vendor draws the same line for the same reason, `fi-fo-radio` beside `fi-radio-input`.

The radio's control rule writes the class alone (`.sy-fo-radio-input`, **(0,1,0)**) rather than keeping vendor's element-and-type qualifier (`input[type='radio'].fi-radio-input`, **(0,2,1)**), because only one element ever carries the class, so the qualifier buys nothing the template does not already guarantee.
What is given up is external: a host rule needs (0,1,0) rather than (0,2,1) to reach the control, the same ledger the [flattened field stylesheet](#field-css-is-flat-where-filament-nests) sits on.
The support checkbox takes the opposite answer, for the reason [below](#the-checkbox-skin-lives-in-support-because-tables-renders-it-too).

### Field CSS is flat where Filament nests

Vendor's [`field.css`](../../../vendor/filament/forms/resources/css/components/field.css) is one rule, `.fi-fo-field { … }`, with every descendant nested inside it under `&`.
The port's [`field.css`](../../../packages/forms/assets/src/styles/components/field.css) is top-level rules with no nesting at all.
Each selector with a nested vendor counterpart loses specificity: most drop from **(0,2,0) to (0,1,0)** (`-label-ctn`, `-label`, `-label-content`, `-label-col`, `-content-col`, `-content-ctn`, `-content`, and the two error classes), and `.sy-fo-field-label-required-mark` drops from **(0,3,0) to (0,1,0)**, vendor nesting it two deep inside `.fi-fo-field-label-content`.

The consequence is narrower than it first looks.
It does **not** change what an ordinary host override can do: everything here ships inside the `components` cascade layer, and [the cascade layers](../../internals/styling.md#why-does-everything-ship-inside-cascade-layers) make unlayered consumer CSS win over any layered rule at any specificity.
What it changes is the contest *inside* the layer: a rule at (0,1,0) that lands in a `components` layer of the same name, whether a host's or another Sylament stylesheet's, ties these rules and wins on source order, where against Filament's (0,2,0) it would have lost outright.
That is a real loosening of a hook surface [styling.md](../../internals/styling.md#why-are-the-emitted-classes-public) treats as public, which is why it is recorded rather than left to a diff.

It is **one stylesheet, not a package habit**: forms' [`repeater.css`](../../../packages/forms/assets/src/styles/components/repeater.css) keeps vendor's nesting rule for rule, as does schemas' [`section.css`](../../../packages/schemas/assets/src/styles/components/section.css).
The same-element stylesheets (`input.css`, `select.css`, and `radio.css`'s control rules, while its option-set half keeps vendor's nesting) are flat too, but there the flattening is free: every one of their rules is a compound on the control itself, and a `&:checked` nested under the base rule compiles to the same selector written out.
Support's checkbox keeps vendor's nesting, its `:indeterminate` arm having two nested arms of its own to hold.
And `toggle.css`'s shape follows from [its element model](toggle.md#the-toggle-is-a-styled-checkbox-not-a-button) rather than from this choice.
Reverses by nesting the rules back under `.sy-fo-field`, a mechanical change whenever the specificity is wanted back.

### Most screen-reader markup still inlines the utility instead of the shared class

Filament's screen-reader class is `.fi-sr-only`, defined in [`support/resources/css/utilities.css`](../../../vendor/filament/support/resources/css/utilities.css) and emitted from several of its packages.
Sylament's is [`.sy-sr-only`](../../../packages/support/assets/src/styles/utilities.css), filed the same way: a code-less utility in support, on the tier that lets any package name it, held in place by support's [`CssScreenReaderUtilityTest`](../../../packages/support/tests/Architecture/CssScreenReaderUtilityTest.php) because every emitter of it lives in another package.

The rest of the port's screen-reader markup does not reach it.
Eight component rules across four packages write `@apply sr-only` into a rule of their own: forms' `.sy-fo-toggle-input`, panels' [`.sy-pa-skip-link`](../../../packages/panels/assets/src/styles/components/layout.css) and `.sy-pa-global-search-label`, schemas' `.sy-sc-section-label-hidden`, `.sy-sc-wizard-header-step-label-hidden` and `.sy-sc-wizard-header-step-status`, and support's hidden `<legend>` under `.sy-fieldset-label-hidden` plus the [`.sy-copyable-live-region`](../../../packages/support/assets/src/styles/components/copyable.css) its clipboard controller mints.
Each is one line, and each is a place a change to the utility has to be repeated by hand.
Closing it is a deletion plus a class swap per rule, in the emitting templates; support's live region is the odd one, emitted from [`clipboard_controller.ts`](../../../packages/support/assets/src/controllers/clipboard_controller.ts) rather than from Twig and found there by a `querySelector` on its own class, so that class stays on the element *beside* `.sy-sr-only` instead of being replaced by it.
There is no behavioural change either way.

Tables' [`.sy-ta-sr-only`](../../../packages/tables/assets/src/styles/components/table.css) is a different case: a *named* package-local twin nested under `.sy-ta`, the table root rather than the `<table>` element, so the loading label outside the table is hidden too, which [the utility tiers](../../internals/styling.md#where-does-a-utility-class-live) tolerate until a second package needs it.

### The checkbox skin lives in support because tables renders it too

Filament files the checkbox skin in **support** and consumes it from both forms and tables.
Tables' record-selection column is the port's second package to render the control, so the skin is [`packages/support/assets/src/styles/components/checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css) as the code-less `.sy-checkbox`, support-level classes carrying no package code being [styling.md](../../internals/styling.md#why-do-templates-carry-only-semantic-classes)'s own wording, and forms has no `checkbox.css`.
Support ships the skin as a class, not as Filament's [`input/checkbox`](../../../vendor/filament/support/resources/views/components/input/checkbox.blade.php) Blade component: each emitter writes its own `<input>`, as Filament's own tables views and `CheckboxColumn` write `fi-checkbox-input` rather than calling that component.

`sy-fo-checkbox` stays beside it.
Both emitting templates, [`Checkbox`](../../../packages/forms/templates/partials/Checkbox/index.html.twig) and [`CheckboxList`](../../../packages/forms/templates/partials/CheckboxList/index.html.twig), write `class: 'sy-checkbox sy-fo-checkbox'`, as Filament writes `fi-checkbox-input` beside its own hooks, and [`checkbox-list.css`](../../../packages/forms/assets/src/styles/components/checkbox-list.css) reaches `.sy-fo-checkbox` for the option's own layout (`mt-1 shrink-0`) and its `:has(…:disabled)` arm.
`Toggle` and `ToggleButtons` are **excluded** on purpose even though both render `type="checkbox"` inputs: their skins are a switch and a suppressed input behind a button label respectively, and a 16px ticked square would break both visually.

Two things differ from the forms-coded controls.
The selector is element-qualified (`input[type='checkbox'].sy-checkbox`, **(0,2,1)**), because a code-less class in support is reachable by any package's markup and the qualifier is what keeps it meaning *a checkbox*.
And the whole `&:indeterminate` block is ported: the dash glyph, `bg-primary-600`, the focus arm, the disabled arm and its `[aria-invalid='true']` nesting.
`HTMLInputElement.indeterminate` is a DOM property no server-rendered attribute can set and no forms control sets; tables' tri-state page checkbox is driven by [the selection controller](../tables/table.md#the-selection-controller-diverges-from-tablejs-in-four-places), which sets exactly that property.

### The checkbox and radio pin their checked glyph's size and placement

Both control rules add three declarations vendor's do not carry, `bg-center` and `bg-no-repeat` in the `@apply` list and a literal `background-size: 1rem 1rem` beside it, in [`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css) and support's [`checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css) alike.
The glyph is a `background-image` data URI on an `appearance-none` box, and the SVG declares a `viewBox` and no intrinsic width or height, so with nothing said a browser sizes it from the background positioning area and the mark tracks the box.
Vendor leaves it there; the port states the intended result, which is the same 1rem mark at the shipped `size-4` and a mark that stays 1rem if a host resizes the control.
It reverses by deleting all three from both files, which renders identically at the shipped size.

### The textarea rule is the port's own, and autosize overrides it

`textarea.sy-fo-input { @apply min-h-20 resize-y }` ports nothing.
Vendor's counterpart is `.fi-fo-textarea.fi-autosizable textarea { @apply resize-none }`: it sets no minimum height and *disables* the resize handle, because its textarea grows with its content.
Ours keeps the handle on the default control: [autosize is opt-in](textarea.md#the-off-branchs-resize-mirror-is-not-ported), so without it a `resize-none` box would be a fixed height the user cannot enlarge; `resize-y` hands that back to the platform and `min-h-20` gives the box a floor that is not one row.
Both are inventions rather than translations, and `autosize()` overrides both rather than reversing them: `.sy-fo-textarea.sy-autosizable textarea.sy-fo-input { @apply h-full min-h-0 resize-none }` beats the shared rule on specificity in either emission order ([textarea.md](textarea.md#the-control-keeps-the-sy-fo-input-class-filaments-textarea-carries-none)).

### The JS select skin is Filament's, on the ported control's container

Vendor's JS-select rules (`support/…/input/select.css:23-120`) are nested under `.fi-select-input` and address the DOM Filament's own select utility builds.
Ours address the DOM [our port of that utility](select.md#nativefalse-switches-to-the-js-control) builds, so the port is a transcription rather than a mapping — one rule for one rule, renamed onto the container class the engine inserts beside the hidden `<select>`:

| Vendor rule | Ours | The element |
| --- | --- | --- |
| `.fi-select-input-ctn` `relative` | `.sy-fo-select-ctn` | the container, inserted `afterend` of the hidden `<select>` |
| `div[x-ref='select']` `min-h-9` | — | Alpine's ref wrapper, which the port has no counterpart for; the button carries `min-h-9` itself, as vendor's does |
| `.fi-select-input-btn` | `.sy-fo-select-btn` | the combobox button — the chevron `background-image` lives here |
| `.fi-select-input-value-ctn` | `.sy-fo-select-value-ctn` | the value row inside the button |
| `.fi-select-input-value-badges-ctn` | `.sy-fo-select-value-badges-ctn` | the multi chips row (SortableJS's root when reorderable) |
| `.fi-select-input-value-label` | `.sy-fo-select-value-label` | the single selection's label |
| `.fi-select-input-value-remove-btn` | `.sy-fo-select-value-remove-btn` | the clear button, appended to the container rather than the button |
| `.fi-select-input-ctn-clearable .fi-select-input-btn` `pe-14` | `.sy-fo-select-ctn-clearable .sy-fo-select-btn` | the button, under the container's clearable flag |
| `.fi-dropdown-panel` `max-h-60 max-w-full!` | `.sy-fo-select-dropdown` — `max-w-full` plus `--sy-dropdown-max-height: 15rem` and `--sy-dropdown-overflow: auto` | the panel |
| `.fi-select-input-options-ctn` `divide-y` | `.sy-fo-select-options-ctn` | the grouped list's root `<ul>` |
| `.fi-select-input-option-group` `divide-y` | `.sy-fo-select-option-group` | a group `<li>` |
| `.fi-select-input-option-group .fi-dropdown-header` | `.sy-fo-select-option-group-label` | the group heading |
| `.fi-select-input-search-ctn` `sticky top-0 z-10 bg-white` | `.sy-fo-select-search-ctn` | the search row at the top of the panel |
| `.fi-select-input-option` `min-w-px text-wrap` | `.sy-fo-select-option` | an option `<li>` |
| `.fi-select-input-placeholder` | `.sy-fo-select-placeholder` | the placeholder span |
| `.fi-disabled` (+ its placeholder arm) | `.sy-disabled` | the button, the clear button and the search input |
| `.fi-select-input-message` / `-max-items-message` | `.sy-fo-select-message` / `.sy-fo-select-max-items-message` | the loading/empty rows and the max-items row |
| `.fi-select-input-ctn-option-labels-not-wrapped` | `.sy-fo-select-ctn-labels-not-wrapped` | the container, per `wrapOptionLabels(false)` — both `> span` arms transcribed |

The transcription carries these rulings.

**The native rules stay element-qualified.**
`select.sy-fo-select` and `select.sy-fo-select-has-inline-prefix` keep the native chevron and padding on the `<select>` alone, which is vendor's own form (`select.fi-select-input`) and reads here as a live constraint rather than a habit: on the JS branch that `<select>` is hidden but still carries both classes, and it sits as a *sibling* of a container whose whole skin is named `sy-fo-select-*`, so the qualification is what keeps the two rule sets from ever meeting as the class lists grow.

**`max-w-full!` loses its `!important`, and the panel becomes the scroll container.**
Nothing here outranks it — the engine writes the panel's `width` inline, which beats support's `width: var(--sy-dropdown-width, …)` rule outright — so the flag would only be noise.
Vendor's `max-h-60` is expressed through the panel's own `--sy-dropdown-max-height` / `--sy-dropdown-overflow` contract instead of a local `max-height`, because that contract is what makes the panel itself the scroll container ([dropdown.css](../../../packages/support/assets/src/styles/components/dropdown.css) gates `overflow-y` on the pair).
That is what makes the search row's `sticky top-0` real.

**The group label is a local class.**
Vendor styles the heading through the shared `.fi-dropdown-header` (`flex w-full gap-2 p-3 text-sm font-medium`) and adds only `font-medium text-gray-500 dark:text-gray-400` in the select arm; support ships no `sy-dropdown-header`, so `.sy-fo-select-option-group-label` carries `p-2 text-sm` — the port's own value, a step tighter than vendor's `p-3` — alongside them.
It moves down the day that component lands.

**The button suppresses its outline because the wrapper paints the ring.**
Vendor's `focus:ring-0 focus:outline-none` on `.fi-select-input-btn` is ported as written: the visible focus ring is `.sy-input-wrp`'s `focus-within:ring-2`, and the button is a real tab stop, so without this the UA paints a second ring on top of it.

**`sy-sr-only` is reused for the status region**, rather than a second copy of the utility — the controller writes support's shared class onto the live region it mints, which is the tier that class sits on ([above](#most-screen-reader-markup-still-inlines-the-utility-instead-of-the-shared-class)).

**The selected-option arm is support's.**
Vendor paints the highlighted option through the shared `.fi-dropdown-list-item.fi-selected`; the port emits `sy-dropdown-item sy-selected` and [`dropdown-item.css`](../support/dropdown-item.md) carries all three of vendor's arms (the neutral background, the `sy-color` background, and the label's text swap).
Nothing select-specific is left here for it.

**One arm is the port's own**: `.sy-fo-select-ctn-has-inline-prefix .sy-fo-select-btn { ps-0 }`.
Filament's JS button keeps its `ps-3` beside an inline prefix, which double-pads the control; the controller flags the container when the hidden `<select>` carries `sy-fo-select-has-inline-prefix` and this arm removes the padding, matching what the native branch already does.

No bundled-library class is selected anywhere in this file: the engine builds its own DOM and SortableJS's `sortable-ghost` / `sortable-chosen` / `sortable-drag` go unstyled, as they do in Filament — so [the bundled-library rule](../../internals/styling.md#why-is-a-bundled-library-styled-under-a-sylament-class) holds vacuously here and waits on the repeater's drag control for its first live case.
The remaining two-sided contract is with the [controller](forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host) and the engine it drives: a class renamed on either side breaks the paint in silence, and no test in this repo renders the control.
