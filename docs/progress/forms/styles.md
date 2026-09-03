# Styles (forms)

The forms bundle's component CSS —
[`packages/forms/assets/src/styles/`](../../../packages/forms/assets/src/styles/)
— is ten authored stylesheets plus two the build generates, and only seven of
them draw on
[`forms/resources/css/`](../../../vendor/filament/forms/resources/css/) at all.
**The rest port from
[`support/resources/css/`](../../../vendor/filament/support/resources/css/)**:
Filament files the raw control chrome — checkbox, input, radio, select, toggle —
in its *support* package and keeps only the field-and-wrapper layer in forms, so
`select.css` and `toggle.css` have no counterpart in the forms
tree at all, `input.css` is support-chrome plus two forms-tree pulls (vendor's
`textarea.css` folded in whole, `date-time-picker.css` for its one native rule),
`radio.css` has one for its option set only, and
`toggle-buttons.css` and `checkbox-list.css` are the exceptions that prove the
split — both are wholly forms', because their controls borrow support's button
chrome (`toggle-buttons.css`) or the shared checkbox skin
(`checkbox-list.css`) by class rather than restyling either. Diffed
against the wrong directory each of them reads as drift from beginning to end,
which is the single most useful thing to know before reading this file. The
tenth reads from neither package: `fused-group.css` ports **schemas**'
`components/fused-group.css`, because
[the component it styles ships here rather than there](fused-group.md#fusedgroup-lives-in-forms-not-schemas).
And the two generated ones read from **npm** rather than from Filament at all:
`filepond.generated.css` is FilePond's own base stylesheet and
`cropper.generated.css` Cropper.js's, each rewritten under a file-upload class by
support's build
([diverges](#the-filepond-base-stylesheet-is-redistributed-scoped-by-the-build)).
One authored stylesheet **left** this tree rather than being written into it:
the checkbox skin moved to support the moment tables became its second consumer
([diverges](#the-checkbox-skin-moved-to-support-when-tables-became-its-second-consumer)).
Every row below names the vendor file it was actually written against.

Each authored stylesheet lands in `components/<name>.css` and is `@import`ed
into [`index.css`](../../../packages/forms/assets/src/styles/index.css) under
`layer(components)`, mirroring Filament's `index.css` structure; the two
generated sheets sit at the top of `src/styles/` (the gitignore glob is one level
deep) and are imported first, so the skin that overrides them comes later in the
same layer. Forms ships no
`assets/dist` and joins no pnpm workspace: support's
[`index.css`](../../../packages/support/assets/src/styles/index.css) imports this
aggregator, so these rules compile into support's single built stylesheet and
reach a consumer through `@sylament/support`. The `fi-*` class prefix is
translated to the forms component code `sy-fo-*` (see the
[CSS contract](../../styling.md)) — which for the support-tier ports is
[more than a prefix swap](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/resources/css/components/`](../../../vendor/filament/forms/resources/css/components/)
  — the twenty per-component stylesheets: `checkbox-list.css`, `field.css`,
  `file-upload.css`, `radio.css`, `repeater.css` and `toggle-buttons.css` ported
  directly, `textarea.css` folded into `input.css`, `text-input.css`, `select.css`
  and `date-time-picker.css` partly, the other ten unported
- [`support/resources/css/components/input/`](../../../vendor/filament/support/resources/css/components/input/)
  — `index.css`, `radio.css` and `select.css`, the three control sources still
  ported into this tree, plus the `wrapper.css` chrome the port collapses into
  its controls; the fourth, `checkbox.css`, is
  [support's](../support/README.md#supporting-surface) now
- [`schemas/resources/css/components/fused-group.css`](../../../vendor/filament/schemas/resources/css/components/fused-group.css)
  — the fused box, ported whole with the component that emits its class
- [`support/resources/css/components/toggle.css`](../../../vendor/filament/support/resources/css/components/toggle.css)
  — the switch chrome `toggle.css` re-architects onto a checkbox
- [`support/resources/css/utilities.css`](../../../vendor/filament/support/resources/css/utilities.css)
  — where `fi-sr-only` lives, the utility `field.css` mis-tiers

## Surface

One row per stylesheet, plus rows for the vendor stylesheets with no port. A
status here reads the **vendor stylesheet**: how much of its rule set has a
counterpart. That is a different question from a component file's own
template-and-stylesheet row, which reads the port's *built* surface — so
`repeater.css` is **in progress** here while
[repeater.md](repeater.md) marks its template-and-stylesheet row **done**, and
neither is wrong. Per-feature reasoning stays in the component files; this table
is the cross-index over the stylesheets, the way
[component-views.md](../schemas/component-views.md) is over schemas' templates.
_Blocked by_ names what unblocks the missing rules. _Detail_ carries a
**diverges** link to a permanent note in [Divergences](#divergences), a
**detail** link to the write-up in [Details](#details) or in a component file, a
**semantics** link to a permanent note in a component file, or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`checkbox-list.css` — the `.sy-fo-checkbox-list` option set, its search row and its no-results line](../../../packages/forms/assets/src/styles/components/checkbox-list.css) | [`components/checkbox-list.css`](../../../vendor/filament/forms/resources/css/components/checkbox-list.css) | done | — | [diverges](checkbox-list.md#the-control-class-is-the-existing-sy-fo-checkbox) |
| `checkbox.css` — gone from this tree; the box, tick, dash and invalid arms are [support's `.sy-checkbox`](../../../packages/support/assets/src/styles/components/checkbox.css), which the two emitting templates carry beside their own `sy-fo-checkbox` hook | [`support/…/input/checkbox.css`](../../../vendor/filament/support/resources/css/components/input/checkbox.css) | done | — | [diverges](#the-checkbox-skin-moved-to-support-when-tables-became-its-second-consumer) |
| [`date-time-picker.css` — its one native-control rule, ported into `input.css` as `.sy-fo-input::-webkit-datetime-edit`](../../../packages/forms/assets/src/styles/components/input.css) | [`components/date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css) | in progress | the JS calendar vertical ([date-time-picker.md](date-time-picker.md#the-js-calendar-family-waits-on-a-js-vertical-of-its-own)) | [detail](#the-date-time-picker-stylesheet-is-one-native-rule-and-a-calendar-panel) |
| [`field.css` — the `.sy-fo-field` wrapper grid, label column, content column and error list](../../../packages/forms/assets/src/styles/components/field.css) | [`components/field.css`](../../../vendor/filament/forms/resources/css/components/field.css) | in progress | schemas' `HasInlineLabel` · support's `HasVerticalAlignment` | [detail](#the-field-stylesheet-omits-the-leading-schema-un-grow) · [diverges](#field-css-is-flat-where-filament-nests) · [diverges](#sy-fo-sr-only-is-a-support-utility-authored-as-a-forms-component-class) |
| [`file-upload.css` — vendor's root layout (the `flex flex-col gap-y-2` root, the `sy-align-*` arms, the avatar input container's width and the error message), the `.sy-fo-file-upload` wrapper's FilePond overrides, the two JS-built action anchors and the editor dialog's skin](../../../packages/forms/assets/src/styles/components/file-upload.css) | [`components/file-upload.css`](../../../vendor/filament/forms/resources/css/components/file-upload.css) | done | — | [diverges](#the-filepond-base-stylesheet-is-redistributed-scoped-by-the-build) · [diverges](#filepond--label-action-takes-the-link-colour-policy-not-vendors-hardcoded-shade) · [diverges](#the-download-and-open-action-anchors-are-the-ports-own) · [diverges](#the-dark-backdrop-arm-sits-in-an-variant-dark-block) |
| `filepond.generated.css` — FilePond's own base stylesheet, rewritten under `.sy-fo-file-upload` by support's `scope-vendor-css.mjs` (generated, gitignored) | `filepond/dist/filepond.css` + `filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css` + `filepond-plugin-image-edit/dist/filepond-plugin-image-edit.css` (npm, not Filament) | done | — | [diverges](#the-filepond-base-stylesheet-is-redistributed-scoped-by-the-build) |
| `cropper.generated.css` — Cropper.js's own stylesheet, rewritten under `.sy-fo-file-upload-editor` by support's `scope-vendor-css.mjs` (generated, gitignored) | `cropperjs/dist/cropper.css` (npm, not Filament) | done | — | [diverges](#the-filepond-base-stylesheet-is-redistributed-scoped-by-the-build) |
| [`fused-group.css` — the `.sy-fo-fused-group` ring, dividers and corner rules](../../../packages/forms/assets/src/styles/components/fused-group.css) | [`schemas/…/components/fused-group.css`](../../../vendor/filament/schemas/resources/css/components/fused-group.css) | done | — | [diverges](../schemas/styles.md#fused-group-css-was-ported-into-the-forms-package) · [semantics](fused-group.md#the-corner-rules-reach-through-two-elements-the-schema-partial-nests) |
| [`input.css` — `.sy-fo-input`, the text control for `TextInput`, `Textarea` and the three pickers](../../../packages/forms/assets/src/styles/components/input.css) | [`support/…/input/index.css`](../../../vendor/filament/support/resources/css/components/input/index.css) + [`support/…/input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) + [`components/textarea.css`](../../../vendor/filament/forms/resources/css/components/textarea.css) + [`components/date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css) | in progress | an alignment setter | [detail](#the-input-and-select-stylesheets-stop-at-the-alignment-arms) · [diverges](#the-input-wrapper-invents-a-hover-ring) · [diverges](#the-textarea-rule-is-the-ports-own-because-nothing-autosizes) |
| [`radio.css` — the `.sy-fo-radio` option set and the `.sy-fo-radio-input` control](../../../packages/forms/assets/src/styles/components/radio.css) | [`components/radio.css`](../../../vendor/filament/forms/resources/css/components/radio.css) + [`support/…/input/radio.css`](../../../vendor/filament/support/resources/css/components/input/radio.css) | done | — | [diverges](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes) · [diverges](radio.md#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) |
| [`repeater.css` — `.sy-fo-repeater`, `.sy-fo-simple-repeater` and `.sy-fo-table-repeater` item chrome](../../../packages/forms/assets/src/styles/components/repeater.css) | [`components/repeater.css`](../../../vendor/filament/forms/resources/css/components/repeater.css) | in progress | infolists | [detail](#the-repeater-stylesheet-waits-on-infolists-for-two-rules) · [diverges](#the-repeater-item-lists-take-supports-grid) |
| [`select.css` — `.sy-fo-select`, the native control and its chevron, and the `.sy-fo-select-*` skin the JS control renders into](../../../packages/forms/assets/src/styles/components/select.css) | [`support/…/input/select.css`](../../../vendor/filament/support/resources/css/components/input/select.css) + [`support/…/input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) | in progress | schemas' `HasInlineLabel` | [detail](#the-input-and-select-stylesheets-stop-at-the-alignment-arms) · [diverges](#the-input-wrapper-invents-a-hover-ring) · [diverges](#the-js-select-skin-is-filaments-on-the-ported-controls-container) |
| [`toggle-buttons.css` — the `.sy-fo-toggle-buttons` option set and its hidden inputs](../../../packages/forms/assets/src/styles/components/toggle-buttons.css) | [`components/toggle-buttons.css`](../../../vendor/filament/forms/resources/css/components/toggle-buttons.css) | in progress | schemas' `HasInlineLabel` | [semantics](toggle-buttons.md#the-grouped-labels-must-stay-direct-children-of-the-group) · [semantics](toggle-buttons.md#the-selected-state-is-painted-from-the-sibling-input-in-both-variants) · [diverges](toggle-buttons.md#extraattributes-stays-on-the-field-wrapper) |
| [`toggle.css` — the `.sy-fo-toggle` root, track, thumb and icon pair](../../../packages/forms/assets/src/styles/components/toggle.css) | [`support/…/components/toggle.css`](../../../vendor/filament/support/resources/css/components/toggle.css) | done | — | [diverges](toggle.md#the-toggle-is-a-styled-checkbox-not-a-button) · [diverges](toggle.md#the-icon-rules-select-by-name-where-filament-selects-by-position) · [diverges](toggle.md#the-two-colours-ride-on-two-elements) |
| `text-input.css` / `select.css` — the rules no port carries | [`components/`](../../../vendor/filament/forms/resources/css/components/) | blocked | `revealable()` · schemas' `HasInlineLabel` | [detail](#two-vendor-stylesheets-are-partly-rather-than-wholly-unported) |
| `builder.css` / `code-editor.css` / `color-picker.css` / `file-upload/filepond-plugin-media-preview.css` / `key-value.css` / `markdown-editor.css` / `modal-table-select.css` / `rich-editor.css` / `slider.css` / `tags-input.css` | [`components/`](../../../vendor/filament/forms/resources/css/components/) | blocked | Builder, CodeEditor, ColorPicker, the media-preview plugin, KeyValue, MarkdownEditor, ModalTableSelect, RichEditor, Slider, TagsInput | [detail](#the-ten-unported-forms-stylesheets) |

This table carries no [unaudited row](../README.md#an-unaudited-table-says-so):
every one of the twenty stylesheets in
[`forms/resources/css/components/`](../../../vendor/filament/forms/resources/css/components/)
has a row above — plus two rows for the generated sheets, which have no Filament
counterpart at all. The claim is scoped to that directory on purpose. The
support-tier sources the five cross-package ports draw from are named per row
rather than enumerated, and support's own `resources/css/` is not this table's
subject — `input/one-time-code.css` sits beside the four control sources with no
forms-side port, its component unported, and the rest of that directory belongs
to support's audit.

No test exercises CSS. The render tests assert that the class hooks these rules
attach to (`sy-fo-field` and its label/content/error descendants,
`sy-fo-field-label-inline-control`, `sy-fo-sr-only`, `sy-fo-input`,
`sy-fo-select`, `sy-fo-checkbox` beside the shared `sy-checkbox`, the six
`sy-fo-toggle-*` classes, the
`sy-fo-radio*` family, the `sy-fo-toggle-buttons*` trio with the `sy-btn-group`
arm beside it, and the
`sy-fo-repeater` / `sy-fo-simple-repeater` families) are emitted into the HTML —
`FieldWrapperTest`, `FieldErrorsTest`, `TextInputTest`, `TextareaTest`,
`SelectTest`, `CheckboxTest`, `ToggleTest`, `RadioTest`, `ToggleButtonsTest` and
`RepeaterRenderTest` — but the
styling itself never runs, so CSS cannot reach **done** through a behaviour test
and a row's status rests on faithfulness of the port. One stylesheet is under an
automated check, and it checks a variable contract rather than a rendered pixel:
[`ToggleColorSlotsTest`](../../../packages/forms/tests/Architecture/ToggleColorSlotsTest.php)
scans `toggle.css` and fails if the slot variables its checked and unchecked
rules read are not exactly the ones
[`ToggleComponent`](../../../packages/support/src/Colors/Components/ToggleComponent.php)
resolves — the only edit to a forms stylesheet that can break a suite. Everything
else is guarded only by the CI check that support's committed `assets/dist`
matches a fresh build.

## Details

Specifics for rows that are not finished — what is in, what is left, and the next
step. Delete a subsection when its row flips to **done**; the table row and any
divergence it earned stay behind.

### The repeater stylesheet waits on infolists for two rules

Every rule of vendor's 401-line `repeater.css` is ported except two, both in the
table-mode compact block and both keyed off infolists classes that do not exist
yet: `.fi-in-entry` / `.fi-in-entry-label` (the entry's own gap and label, which
the compact table hides the same way it hides a field's) and
`.fi-in-entry-content` (its horizontal padding). [`infolists`](../infolists/README.md)
ships a bundle class and nothing else, so there is no `sy-in-*` name to hang
them on. Their siblings *are* ported — `.sy-fo-field`, `.sy-fo-field-label-content`,
`.sy-fo-toggle-buttons .sy-fo-field-content-col` and `.sy-fo-radio` all carry
their vendor rule — so the gap is exactly the infolists half. Add the two rules
in the same change that gives infolists an entry partial.

### The field stylesheet omits the leading-schema un-grow

Filament's `field.css` un-grows a schema rendered first inside the label
container: `& > .fi-sc:nth-child(1) { @apply grow-0 }`. The port has no
counterpart, and the rule is reachable rather than hypothetical —
[`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig)
renders `beforeLabelSchema` as the first child of `.sy-fo-field-label-ctn`, and
an inline schema carries `flex grow` from
[`schema.css`](../../../packages/schemas/assets/src/styles/components/schema.css).
So `beforeLabel()` stretches across the label row here and does not in Filament.
Next step: port the rule with the row.

The other two gaps are already tracked as component surface rather than as CSS.
`&.fi-fo-field-has-inline-label` (the `sm:grid-cols-3` label/content split and
its `sm:col-span-2` content column) waits on schemas' `HasInlineLabel`, which
[field.md](field.md)'s field-wrapper-rendering row records as omitted for exactly
that reason; the three `.fi-fo-field-label-col.fi-vertical-align-*` arms wait on
support's `HasVerticalAlignment`, unported for the same reason schemas'
[`actions.css`](../schemas/styles.md#actions-css-folds-in-the-actions-package-row-stylesheet-and-drops-the-sticky-and-vertical-alignment-variants)
drops its own copy of that set. Both return with their concern, not with a CSS
change.

`.fi-fo-field-label.fi-hidden { @apply hidden }` is not a gap and never becomes
one: no hidden placeholder is rendered anywhere in the port, so the selector can
never match — the same DOM fact schemas records for the
[whole schema render](../schemas/schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders).

### The input and select stylesheets stop at the alignment arms

Both controls are now transparent, with the ring, background and `focus-within`
state on support's
[`.sy-input-wrp`](../support/input-wrapper.md) — the shape
[field.md](field.md#the-wrapper-owns-the-chrome-and-the-control-is-transparent)
records, and where the two type-specific `input[type=…]` rules are ruled on. The
affix hooks landed with it (`.sy-fo-input-has-inline-prefix`, its suffix twin and
`.sy-fo-select-has-inline-prefix`), so what is left unported is:

- **`input.fi-input`'s six `.fi-align-*` arms** (`center`, `end`, `left`,
  `right`, `justify`, `between`) have no port. No field exposes an alignment
  setter, so nothing emits the class — but Filament's own fields do not emit it
  either; a consumer reaches those arms through `extraInputAttributes()`, which
  [the port also appends to the class list](has-extra-input-attributes.md). So the
  hook is reachable here and the rules behind it are not, which is the one arm of
  this list a consumer can trip over today.
- **One of the three per-field wrapper rules still has no element.** Vendor puts
  two of them on the input wrapper itself and the third on a `<div>` outside it.
  `.fi-fo-textarea { overflow-hidden }` ported with the flip, onto the textarea's
  own wrapper as
  [`.sy-fo-textarea`](textarea.md#the-control-keeps-the-sy-fo-input-class-filaments-textarea-carries-none).
  `.fi-fo-text-input { overflow-hidden }` is the same rule one control over and
  its element exists too — the text input's `.sy-input-wrp` — so it is a one-line
  class away rather than blocked; it stays with the rest of `text-input.css`
  below. Only `.fi-fo-select-wrp` has nothing to attach to: it styles a second
  `<div>`, outside the input wrapper rather than inside it, and waits on schemas'
  `HasInlineLabel` with the rules that use it.

The ninety-odd rules that style the **JS** select are not part of this, and they
are not in vendor's forms `select.css` either — that file is thirteen lines (both
of its rules are ruled on [below](#two-vendor-stylesheets-are-partly-rather-than-wholly-unported)).
They live in `support/…/input/select.css:23-120`, the same file this row's native
arm ports — keyed off the bare *class* `.fi-select-input` where the native arm is
keyed off `select.fi-select-input`. The port keeps that element qualification and
hangs the JS half off the container class the engine builds instead
([element-qualification](#the-js-select-skin-is-filaments-on-the-ported-controls-container)).
They land with
[`native(false)`](select.md#nativefalse-switches-to-the-js-control), transcribed
onto the DOM a port of Filament's own select utility builds:
[the JS select skin](#the-js-select-skin-is-filaments-on-the-ported-controls-container).

### Two vendor stylesheets are partly rather than wholly unported

`text-input.css` holds two rules. `.fi-fo-text-input { @apply overflow-hidden }`
is the text input's half of the pair named above — the element exists now, so it
is a one-line class away rather than blocked, and it is unported only because
nothing has clipped past the wrapper's corners here yet. The other,
`& input.fi-revealable { @apply [&::-ms-reveal]:hidden }`, suppresses Edge's
native password-reveal glyph for a field that ships its own reveal button —
[`revealable()`](text-input.md) is **todo** there, blocked on the prebuilt
`TextInput/Actions`, and the rule lands with it.

Vendor's `select.css` holds two more. `.fi-fo-select .fi-hidden { @apply hidden }`
can never match, for the hidden-placeholder reason above; and
`.fi-fo-select-wrp.fi-fo-field-has-inline-label .fi-fo-field-label-col { sm:pt-1.5 }`
needs both the select wrapper element and schemas' `HasInlineLabel`, so it waits
on the later of the two.

### The date-time picker stylesheet is one native rule and a calendar panel

Vendor's `date-time-picker.css` is a single `.fi-fo-date-time-picker` block, and
everything nested inside it except one rule styles the **JS calendar** — the
trigger button, the display text input, the panel, its month select and year
input, the calendar grid and its day states, and the time inputs. All of it is
[blocked on the calendar vertical](date-time-picker.md#the-js-calendar-family-waits-on-a-js-vertical-of-its-own),
which is a JS build before it is a stylesheet.

The exception is `& input::-webkit-datetime-edit { display: block; padding: 0 }`,
which fixes an excessive height WebKit gives a **native** date control. That one
is ported, and it is ported into
[`input.css`](../../../packages/forms/assets/src/styles/components/input.css)
rather than into a `date-time-picker.css` of its own: the port's three pickers
render their `<input class="sy-fo-input">` inside support's `.sy-input-wrp` like
every other control and add no picker-specific class of their own, so there is no
`.sy-fo-date-time-picker` for the rule to nest under and no second rule that
would share a file with it. It is written flat as
`.sy-fo-input::-webkit-datetime-edit`, which is harmless on the other controls —
the pseudo-element exists only on date and time inputs. Next step: the file
appears when the calendar does, and this rule moves into it if the calendar
brings a wrapper element with it.

### The ten unported forms stylesheets

`builder.css`, `code-editor.css`, `color-picker.css`, `key-value.css`,
`markdown-editor.css`, `modal-table-select.css`, `rich-editor.css`,
`slider.css` and `tags-input.css` have no `sy-fo-*` counterpart. Each is
**blocked** on its field — every one of those fields is **todo** in the
[package README](README.md#components) — because the styling lands in the same
change as the Twig template it targets and there is nothing to author against
until then. Next step for each: port when its field does.

The tenth is the odd one out: `file-upload/filepond-plugin-media-preview.css` is
nested under a stylesheet that *is* ported, and is blocked on the media-preview
plugin rather than on a field — `FileUpload` ships with FilePond's core plus
every plugin but this one: the two validators (`file-validate-size`,
`file-validate-type`) and the six image plugins (`image-crop`, `image-edit`,
`image-exif-orientation`, `image-preview`, `image-resize`, `image-transform`), so
audio and video items render as file cards with no player. Next step: port when the plugin joins
the bundle, a decision of its own — the editor shipped without it.

One support-tier stylesheet sits in the same position one package over:
`input/one-time-code.css`, blocked on `OneTimeCodeInput`. It is listed here
rather than in a support trail because the cross-package ports above establish
that a control's chrome arrives on the forms side — `input/radio.css` was the
most recent of them, landing inside forms'
[`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css)
with [`Radio`](radio.md).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above.

### The control stylesheets carry forms-coded names for support-tier classes

`input.css`, `radio.css`, `select.css` and `toggle.css` port
`input.fi-input`, `input[type='radio'].fi-radio-input`, `select.fi-select-input`
and `.fi-toggle` —
support-package classes, which [carry no package code](../renames.md) by the
port's own naming rule (`fi-icon-btn` → `sy-icon-btn`). The port names them
`.sy-fo-input`, `.sy-fo-radio-input`, `.sy-fo-select` and
`.sy-fo-toggle` instead, so a support-tier class arrives under the forms code and
in a forms stylesheet. It is the same
tracks-its-emitter call schemas records in the other direction for
[`text.css`](../schemas/styles.md#text-css-was-ported-into-the-support-package):
forms is the only consumer of all four today, and filing a rule beside the one
template that emits it beats filing it beside the vendor that happened to author
it. The re-coding reverses the moment a second package renders one of these
controls — an infolists or tables cell reusing the input chrome would have to
either import a forms-coded class or duplicate the rule, and moving the file down
to support is the cheaper answer at that point.

There were five, and the fifth is the reversal happening exactly as written:
`checkbox.css` ported `input[type='checkbox'].fi-checkbox-input` as
`.sy-fo-checkbox` until tables' selection column became its second consumer, at
which point the file moved down to support and the class went back to being
code-less ([below](#the-checkbox-skin-moved-to-support-when-tables-became-its-second-consumer)).
Read this section as four stylesheets waiting on the same trigger.

The radio keeps its `-input` suffix where the checkbox drops one, and that is not
an inconsistency to tidy: `.sy-fo-radio` is already taken by the option-set
container the field renders around the controls, which the checkbox — a single
element with no container — has no equivalent of. Vendor draws the same line for
the same reason, `fi-fo-radio` beside `fi-radio-input`.

The radio's own shape question — whether to keep vendor's element-and-type
qualifier (`input[type='radio'].fi-radio-input`, **(0,2,1)**) or write the class
alone (`.sy-fo-radio-input`, **(0,1,0)**) — is answered the second way, on the
grounds that only one element ever carries the class, so the qualifier buys
nothing the template does not already guarantee, and the stylesheet's internal
order survives either way. What is given up is external: a host rule now needs
(0,1,0) rather than (0,2,1) to reach the control, the same ledger the
[flattened field stylesheet](#field-css-is-flat-where-filament-nests) sits on.
The checkbox took the opposite answer when it moved to support, and deliberately
— see below.

### Field CSS is flat where Filament nests

Vendor's [`field.css`](../../../vendor/filament/forms/resources/css/components/field.css)
is one rule — `.fi-fo-field { … }` — with every descendant nested inside it under
`&`. The port's
[`field.css`](../../../packages/forms/assets/src/styles/components/field.css) is
twelve top-level rules with no nesting at all. Ten selectors across nine of those
rules have a nested vendor counterpart, and each loses specificity: nine drop from
**(0,2,0) to (0,1,0)** (`-label-ctn`, `-label`, `-label-content`, `-label-col`,
`-content-col`, `-content-ctn`, `-content`, and the two error classes), and
`.sy-fo-field-label-required-mark` drops from **(0,3,0) to (0,1,0)**, vendor
nesting it two deep inside `.fi-fo-field-label-content`. The compiled output
confirms both shapes: `.sy-fo-field-label-content` emits as a single class where
`.sy-fo-repeater .sy-fo-repeater-item-header-label` emits as a descendant pair.

The consequence is narrower than it first looks, and worth stating so nobody
"fixes" the wrong thing. It does **not** change what an ordinary host override can
do: everything here ships inside the `components` cascade layer, and
[styling.md rule 8](../../styling.md) makes unlayered consumer CSS win over any
layered rule at any specificity. What it changes is the contest *inside* the
layer — a rule at (0,1,0) that lands in a `components` layer of the same name,
whether a host's or another Sylament stylesheet's, now ties these rules and wins
on source order, where against Filament's (0,2,0) it would have lost outright.
That is a real loosening of a hook surface [styling.md](../../styling.md) treats
as public, which is why it is recorded rather than left to a diff.

It is also **one stylesheet, not a package habit**: forms'
[`repeater.css`](../../../packages/forms/assets/src/styles/components/repeater.css)
keeps vendor's nesting rule for rule, as does schemas'
[`section.css`](../../../packages/schemas/assets/src/styles/components/section.css).
The same-element stylesheets (`input.css`, `select.css`, and
`radio.css`'s three control rules — its option-set half keeps vendor's nesting)
are flat too, but there the flattening is free: every one of their rules is a
compound on the control itself, and a `&:checked` nested under the base rule
compiles to the same selector as the `.sy-fo-input:focus` written out. Support's
checkbox keeps vendor's nesting instead, its `:indeterminate` arm having two
nested arms of its own to hold. And
`toggle.css`'s shape follows from
[its element model](toggle.md#the-toggle-is-a-styled-checkbox-not-a-button)
rather than from this choice. Reverses by nesting the nine rules back under
`.sy-fo-field`, which is a mechanical change whenever the specificity is wanted
back.

### `sy-fo-sr-only` is a support utility authored as a forms component class

Filament's screen-reader class is `.fi-sr-only`, defined in
[`support/resources/css/utilities.css`](../../../vendor/filament/support/resources/css/utilities.css)
at line 453 and emitted from eight of its packages — forms' field wrapper,
`Repeater`, `KeyValue` and `TagsInput`, tables' search field, icon column and
summarizers, infolists' entry wrapper, and on through schemas, actions, support,
notifications and the panel builder. It is a *utility* with no package code, so
its port belongs in
[`packages/support/assets/src/styles/utilities.css`](../../../packages/support/assets/src/styles/utilities.css)
as `sy-sr-only`. The port instead defines `.sy-fo-sr-only` at the bottom of
forms' `field.css`: wrong tier, wrong package code, and wrong kind of file.

Nothing breaks today — its two emitters are both forms', the field wrapper and
the `sylament--forms--select` controller's `role="status"` live region, so the
class is always reachable where it is written — and the reason it is flagged
rather than fixed is that relocating it is cross-package churn for no
behavioural gain. What the misplacement actually costs shows up on the *second*
consumer, and that consumer already exists: every other place in the port that
needs the same effect writes `@apply sr-only` into a component rule of its own
rather than reaching a shared class — panels'
[`.sy-pa-skip-link`](../../../packages/panels/assets/src/styles/components/layout.css),
schemas' `.sy-sc-section-label-hidden`, support's hidden `<legend>` and forms'
own `.sy-fo-toggle-input`. So the port has four hand-inlined copies of a utility
and one class that could have been it, filed where only forms can name it. Closing
it is a move plus a rename, and the four inlined copies collapse onto it in the
same change.

### The checkbox skin moved to support when tables became its second consumer

Filament files the checkbox skin in **support** and consumes it from both forms
and tables. The port started it in forms as `.sy-fo-checkbox`, on the
tracks-its-emitter rule [above](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes),
with that section's own escape clause attached: *the re-coding reverses the
moment a second package renders one of these controls*. M4 · 1's record-selection
column is that second package, so
[`packages/forms/assets/src/styles/components/checkbox.css`](../../../packages/forms/assets/src/styles/components/)
is gone with its import line and
[`packages/support/assets/src/styles/components/checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css)
holds the skin as the code-less `.sy-checkbox` — support-level classes carrying
no package code being [styling.md rule 1](../../styling.md)'s own wording. The
same escalation `InputWrapper` made one component earlier, and Filament's own
layering restored.

Three sibling skins have **not** made that move, and their second consumer has
now arrived. The column catalog (M4 · 4) renders tables' editable cells inside
forms' own classes — `sy-fo-toggle*` on
[`ToggleColumn`](../tables/toggle-column.md), `sy-fo-input` on
[`TextInputColumn`](../tables/text-input-column.md) and `sy-fo-select` on
[`SelectColumn`](../tables/select-column.md), each inside support's
`InputWrapper` — which is a class reuse across a hard dependency (tables
requires forms) rather than a package-boundary break, and deliberately not the
escalation above: the hoist would touch three forms stylesheets and every
template that emits them, which are M3 · 9's files. So the escape clause is
**owed** on all three and not yet taken, and the note stands here rather than in
tables so the next reader of *this* tree finds it. Taking it means moving
`toggle.css`, `input.css` and `select.css`'s control halves into support under
code-less names, the way `checkbox.css` went.

`sy-fo-checkbox` did **not** go with it. Both emitting templates —
[`Checkbox`](../../../packages/forms/templates/partials/Checkbox/index.html.twig)
and
[`CheckboxList`](../../../packages/forms/templates/partials/CheckboxList/index.html.twig)
— now write `class: 'sy-checkbox sy-fo-checkbox'`, exactly as Filament writes
`fi-checkbox-input` beside its own hooks, and
[`checkbox-list.css`](../../../packages/forms/assets/src/styles/components/checkbox-list.css)
still reaches `.sy-fo-checkbox` for the option's own layout (`mt-1 shrink-0`) and
its `:has(…:disabled)` arm. Every existing render assertion on `sy-fo-checkbox`
therefore survives untouched. `Toggle` and `ToggleButtons` are **excluded** on
purpose even though both render `type="checkbox"` inputs: their skins are a
switch and a suppressed-input-behind-a-button-label respectively, and a
16px ticked square would break both visually.

The move restores two things the forms copy had dropped. The selector is
element-qualified again (`input[type='checkbox'].sy-checkbox`, **(0,2,1)**),
because a code-less class in support is reachable by any package's markup and the
qualifier is what keeps it meaning *a checkbox*. And the whole `&:indeterminate`
block is ported for the first time — the dash glyph, `bg-primary-600`, the focus
arm, the disabled arm and its `[aria-invalid='true']` nesting. Forms never needed
it and recorded so: `HTMLInputElement.indeterminate` is a DOM property no
server-rendered attribute can set, and no forms control ships JavaScript that
sets it. Tables' tri-state page checkbox is driven by
[the selection controller](../tables/tables-bundle.md#the-selection-controller-diverges-from-tablejs-in-four-places),
which sets exactly that property, so the rules now have a selector that matches.

### The checkbox and radio pin their checked glyph's size and placement

Both control rules add three declarations vendor's do not carry — `bg-center`
and `bg-no-repeat` in the `@apply` list, and a literal
`background-size: 1rem 1rem` beside it — in
[`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css)
and, since it
[moved to support](#the-checkbox-skin-moved-to-support-when-tables-became-its-second-consumer),
[`checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css)
alike. The glyph is a `background-image` data URI on an `appearance-none` box,
and the SVG declares a `viewBox` and no intrinsic width or height, so with
nothing said a browser sizes it from the background positioning area and the
mark tracks the box. Vendor leaves it there; the port states the intended
result, which is the same 1rem mark at the shipped `size-4` and a mark that
stays 1rem if a host ever resizes the control. Recorded because it is an
addition to a rule otherwise ported line for line — the checkbox's landed first
and went unrecorded, and the radio copied it — and it reverses by deleting all
three from both files, which renders identically today. The three travelled with
the checkbox into support unchanged, so the note now spans two packages.

### The input wrapper invents a hover ring

[`.sy-input-wrp`](../support/input-wrapper.md) carries
`hover:ring-gray-950/20 dark:hover:ring-white/20`. Vendor's `.fi-input-wrp` has no
`:hover` rule anywhere — it goes straight from the resting ring to
`focus-within`. The affordance is the port's own. It was invented on
`.sy-fo-input` and `.sy-fo-select` together, back when each control painted its
own ring, and it moved onto the wrapper unchanged when the ring did, so a text
field and a select still behave alike and every other wrapped control now joins
them. It is cheap to keep: it paints only the ring colour, so it cannot collide
with the invalid arm, which restates the danger ring for exactly that reason.
Reverses by deleting two declarations if Filament parity on hover is ever
wanted.

Only the light half does anything. `dark:hover:ring-white/20` is the same value
as the resting `dark:ring-white/20`, so the dark hover is inert — it was carried
over verbatim from the two controls that owned the ring before the wrapper did,
and it is kept rather than deleted so both halves of the pair stay visible at the
one place a reviewer reads them. Deepening the shade is not a fix; it would
invent a dark hover Filament has no counterpart for on top of a light one it
already has none for.

### The textarea rule is the port's own, because nothing autosizes

`textarea.sy-fo-input { @apply min-h-20 resize-y }` ports nothing. Vendor's
counterpart is `.fi-fo-textarea.fi-autosizable textarea { @apply resize-none }` —
it sets no minimum height and *disables* the resize handle, because its textarea
grows with its content under an Alpine autosize binding.
[Autosize is not ported](textarea.md#autosize-is-not-ported), so disabling the
handle would leave a fixed-height box the user cannot enlarge; `resize-y` hands
that back to the platform and `min-h-20` gives the box a floor that is not one
row. Both are inventions rather than translations, and both reverse when autosize
lands: the rule becomes vendor's, gated on the same `sy-fo-*` class the autosize
controller keys off.

### The repeater item lists take support's grid

The item lists take support's `grid()` (`sy-grid` plus the per-breakpoint
`--cols-*` custom properties), so the repeater owns no grid CSS: both item lists
declare only the spacing and alignment the grid does not carry, matching vendor
rule for rule. The compensating `@apply grid` these two rules carried while
`HasContainerGridLayout` was deferred is gone with the deferral
([repeater.md](repeater.md#the-items-grid-is-supports-grid)).

### `.filepond--label-action` takes the link colour policy, not vendor's hardcoded shade

`.filepond--label-action` — FilePond's "Browse" link — is `text-(--sy-text)
hover:opacity-80 dark:text-(--sy-dark-text)` where vendor hardcodes
`text-primary-700 hover:text-primary-600 …`; the link colour policy sits on the
pond container and supplies the slots, per [styling.md rule 5](../../styling.md).

### The download and open action anchors are the port's own

The two action anchors are ours (`sy-fo-file-upload-download-action` /
`-open-action` with an inner `-icon` span) rather than vendor's
`filepond--download-icon` / `filepond--open-icon`: our JS builds those anchors, so
there is no vendor class to select at all — [rule 11](../../styling.md)'s second
branch has nothing to reach here. Their mask-image payloads are copied verbatim
from vendor (`file-upload.css:82-83, 92-93`), which is why the download glyph is a
Feather arrow and the open glyph a Heroicons external-link rather than a matched
pair; both are MIT and the layout/mask split onto two elements is the port's own,
so the anchor carries the box and the span carries the mask.

### The FilePond base stylesheet is redistributed scoped by the build

Filament imports `filepond/dist/filepond.min.css` and the image-preview plugin's
CSS through a JS entry, so its compiled `dist/index.css` carries ~60 bare
top-level `.filepond--*` selectors and three global `@keyframes` (`spin`, `shake`,
`fall`) — only a cascade layer scopes them. Sylament's stylesheet is page-wide in a
host application, so shipping that verbatim would style a host app's own
`filepond--*` markup and — worse — steal `@keyframes spin`, which support's
loading indicator already declares.
[styling.md rule 11](../../styling.md) forbids it, and FilePond exposes no
class-prefix option (its `className` only adds a class to the root), so the base
sheet is **regenerated** instead of imported.

`packages/support/assets/scripts/scope-vendor-css.mjs` runs in support's
`build:css` chain — between `scope-preflight.mjs` and Tailwind — and writes
`packages/forms/assets/src/styles/filepond.generated.css`, which forms'
[`index.css`](../../../packages/forms/assets/src/styles/index.css) imports
**first** so our skin, in the same layer, wins on order. Four things about it:

- It resolves its sources through a `createRequire` rooted at **forms'**
  `package.json`, because `hoistPattern: []` keeps `filepond` in forms'
  workspace while `postcss` lives in support's — and pnpm builds forms *after*
  support, so a forms-side generator would not exist when support's Tailwind runs.
- Every rule takes the **descendant** variant only (`:where(.sy-fo-file-upload)
  .filepond--root`), not preflight's self+descendant pair: FilePond builds inside
  the wrapper we render, so the wrapper is always an ancestor and the self variant
  would double the output for nothing.
- The six `@keyframes` (three names, prefixed and unprefixed) are renamed
  `sy-fo-file-upload-*`, and the six shorthand `animation:` / `-webkit-animation:`
  declarations that reference them are rewritten to match. Rules *inside* a
  `@keyframes` are left alone — a percentage selector is not an element selector,
  and scoping one produces invalid CSS the browser drops silently.
- The file is gitignored by the existing `packages/*/assets/src/styles/*.generated.css`
  glob (one level deep — hence the location) and carries a generated-file banner
  plus one MIT notice per source package, composed from the installed
  `package.json` the way `build-js.mjs`'s `licenseBanner()` composes its. The
  compiled result lands in support's committed `dist/styles/index.css`, which is
  what CI's byte-stable build gate checks.

The selector walker itself moved out of `scope-preflight.mjs` into a shared
`scope-css.mjs` that both callers use — preflight's two adaptations (map the
`html, :host` rule onto the scope root with its `font-*` declarations dropped,
drop `body`) became options rather than a second copy. The refactor's proof is
byte-identity: the generated preflight and support's built `dist/styles/index.css`
are unchanged by it.

Cropper.js's stylesheet is the second generated vendor sheet:
`scope-vendor-css.mjs` rewrites `cropperjs/dist/cropper.css` as forms' gitignored
`cropper.generated.css`, every rule scoped under `.sy-fo-file-upload-editor` — the
dialog's class rather than the field's, since Cropper's DOM only ever lives inside
it.

### The JS select skin is Filament's, on the ported control's container

Vendor's JS-select rules (`support/…/input/select.css:23-120`) are nested under
`.fi-select-input` and address the DOM Filament's own select utility builds. Ours
address the DOM [our port of that utility](select.md#nativefalse-switches-to-the-js-control)
builds, so the port is a transcription rather than a mapping — one rule for one
rule, renamed onto the container class the engine inserts beside the hidden
`<select>`:

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

Seven rulings carried through the transcription.

**The native rules stay element-qualified.** `select.sy-fo-select` and
`select.sy-fo-select-has-inline-prefix` keep the native chevron and padding on the
`<select>` alone, which is vendor's own form (`select.fi-select-input`) and reads
here as a live constraint rather than a habit: on the JS branch that `<select>` is
hidden but still carries both classes, and it now sits as a *sibling* of a
container whose whole skin is named `sy-fo-select-*`, so the qualification is what
keeps the two rule sets from ever meeting as the class lists grow.

**`max-w-full!` loses its `!important`, and the panel becomes the scroll
container.** Nothing here outranks it — the engine writes the panel's `width`
inline, which beats support's `width: var(--sy-dropdown-width, …)` rule outright —
so the flag would only be noise. Vendor's `max-h-60` is expressed through the
panel's own `--sy-dropdown-max-height` / `--sy-dropdown-overflow` contract instead
of a local `max-height`, because that contract is what makes the panel itself the
scroll container ([dropdown.css](../../../packages/support/assets/src/styles/components/dropdown.css)
gates `overflow-y` on the pair). That is what makes the search row's
`sticky top-0` real: with the previous engine the scroll container was an element
*below* the search row, so the row had nothing to stick to and took a dividing
border instead.

**The group label is a local class.** Vendor styles the heading through the shared
`.fi-dropdown-header` (`flex w-full gap-2 p-3 text-sm font-medium`) and adds only
`font-medium text-gray-500 dark:text-gray-400` in the select arm; support ships no
`sy-dropdown-header`, so `.sy-fo-select-option-group-label` carries `p-2 text-sm`
— the port's own M3 · 4 value, a step tighter than vendor's `p-3` — alongside
them. It moves down the day that component lands.

**The button suppresses its outline because the wrapper paints the ring.**
Vendor's `focus:ring-0 focus:outline-none` on `.fi-select-input-btn` is ported as
written: the visible focus ring is `.sy-input-wrp`'s `focus-within:ring-2`, and
the button is a real tab stop, so without this the UA paints a second ring on top
of it.

**`sy-fo-sr-only` is reused for the status region**, rather than a second copy of
the utility — the same support-tier-class-under-a-forms-name case recorded
[above](#sy-fo-sr-only-is-a-support-utility-authored-as-a-forms-component-class).

**The selected-option arm moved down to support.** Vendor paints the highlighted
option through the shared `.fi-dropdown-list-item.fi-selected`; the port emits
`sy-dropdown-item sy-selected` and
[`dropdown-item.css`](../support/dropdown-item.md) now carries all three of
vendor's arms (the neutral background, the `sy-color` background, and the label's
text swap). Nothing select-specific is left here for it.

**One arm is the port's own**: `.sy-fo-select-ctn-has-inline-prefix
.sy-fo-select-btn { ps-0 }`. Filament's JS button keeps its `ps-3` beside an
inline prefix, which double-pads the control; the controller flags the container
when the hidden `<select>` carries `sy-fo-select-has-inline-prefix` and this arm
removes the padding, matching what the native branch already does.

No bundled-library class is selected anywhere in this file any more — the engine
builds its own DOM and SortableJS's `sortable-ghost` / `sortable-chosen` /
`sortable-drag` go unstyled, as they do in Filament — so
[styling.md rule 11](../../styling.md) holds vacuously here and waits on the
repeater's drag control for its first live case. The remaining two-sided contract
is with the [controller](forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host) and the engine it drives: a class
renamed on either side breaks the paint in silence, and no test in this repo
renders the control.

### The dark backdrop arm sits in an `@variant dark` block

The editor dialog's `::backdrop` takes its light shade inline
(`bg-gray-950/50`) and its dark one from an `@variant dark` block one nesting
level up, rather than from a `dark:` prefix beside it. Written inline, the dark
variant compiles to a `:where()` compound sitting next to the pseudo-element, and
the optimiser Tailwind runs over the compiled sheet mangles that pairing into
`::backdrop:where()` — a selector that matches nothing, with no error anywhere.
Hoisting the arm one level up keeps the two rules apart through the build, and
support's committed `dist/styles/index.css` carries both (50 % light, 75 % under
`.dark`). It is the only `@variant dark` block in the forms tree, and it is
there for the build rather than for the design.

_See also: the global [rename table](../renames.md) — the `fi-*` → `sy-fo-*` class
prefix, the `--x` → `--sy-x` custom-property prefix, and the
`fi-fo-field-wrp-error-*` → `sy-fo-field-error-*` rename are recorded there, not
as per-stylesheet divergences._
