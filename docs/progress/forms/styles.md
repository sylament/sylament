# Styles (forms)

The forms bundle's component CSS —
[`packages/forms/assets/src/styles/`](../../../packages/forms/assets/src/styles/)
— is nine stylesheets, and only six of them draw on
[`forms/resources/css/`](../../../vendor/filament/forms/resources/css/) at all.
**The rest port from
[`support/resources/css/`](../../../vendor/filament/support/resources/css/)**:
Filament files the raw control chrome — checkbox, input, radio, select, toggle —
in its *support* package and keeps only the field-and-wrapper layer in forms, so
`checkbox.css`, `select.css` and `toggle.css` have no counterpart in the forms
tree at all, `input.css` is support-chrome plus two forms-tree pulls (vendor's
`textarea.css` folded in whole, `date-time-picker.css` for its one native rule),
`radio.css` has one for its option set only, and
`toggle-buttons.css` and `checkbox-list.css` are the exceptions that prove the
split — both are wholly forms', because their controls borrow support's button
chrome (`toggle-buttons.css`) or forms' own already-ported checkbox
(`checkbox-list.css`) by class rather than restyling either. Diffed
against the wrong directory each of them reads as drift from beginning to end,
which is the single most useful thing to know before reading this file. Every row
below names the vendor file it was actually written against.

Each stylesheet is `@apply`-authored into `components/<name>.css` and `@import`ed
into [`index.css`](../../../packages/forms/assets/src/styles/index.css) under
`layer(components)`, mirroring Filament's `index.css` structure. Forms ships no
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
  `radio.css`, `repeater.css` and `toggle-buttons.css` ported directly,
  `textarea.css` folded into `input.css`, `text-input.css`, `select.css` and
  `date-time-picker.css` partly, the other eleven unported
- [`support/resources/css/components/input/`](../../../vendor/filament/support/resources/css/components/input/)
  — `checkbox.css`, `index.css`, `radio.css` and `select.css`, the four control
  sources, plus the `wrapper.css` chrome the port collapses into its controls
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
| [`checkbox-list.css` — the `.sy-fo-checkbox-list` option set, its search row and its no-results line](../../../packages/forms/assets/src/styles/components/checkbox-list.css) | [`components/checkbox-list.css`](../../../vendor/filament/forms/resources/css/components/checkbox-list.css) | in progress | [the input affix wrapper](field.md#the-input-affix-wrapper-is-not-ported) | [detail](checkbox-list.md#the-search-box-borrows-sy-fo-input-instead-of-the-unbuilt-wrapper) · [diverges](checkbox-list.md#the-control-class-is-the-existing-sy-fo-checkbox) |
| [`checkbox.css` — `.sy-fo-checkbox` box, tick and invalid arms](../../../packages/forms/assets/src/styles/components/checkbox.css) | [`support/…/input/checkbox.css`](../../../vendor/filament/support/resources/css/components/input/checkbox.css) | done | — | [diverges](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes) · [diverges](#the-indeterminate-checkbox-arm-has-no-port) |
| [`date-time-picker.css` — its one native-control rule, ported into `input.css` as `.sy-fo-input::-webkit-datetime-edit`](../../../packages/forms/assets/src/styles/components/input.css) | [`components/date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css) | in progress | the JS calendar vertical ([date-time-picker.md](date-time-picker.md#the-js-calendar-family-waits-on-a-js-vertical-of-its-own)) | [detail](#the-date-time-picker-stylesheet-is-one-native-rule-and-a-calendar-panel) |
| [`field.css` — the `.sy-fo-field` wrapper grid, label column, content column and error list](../../../packages/forms/assets/src/styles/components/field.css) | [`components/field.css`](../../../vendor/filament/forms/resources/css/components/field.css) | in progress | schemas' `HasInlineLabel` · support's `HasVerticalAlignment` | [detail](#the-field-stylesheet-omits-the-leading-schema-un-grow) · [diverges](#field-css-is-flat-where-filament-nests) · [diverges](#sy-fo-sr-only-is-a-support-utility-authored-as-a-forms-component-class) |
| [`input.css` — `.sy-fo-input`, the text control for `TextInput`, `Textarea` and the three pickers](../../../packages/forms/assets/src/styles/components/input.css) | [`support/…/input/index.css`](../../../vendor/filament/support/resources/css/components/input/index.css) + [`support/…/input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) + [`components/textarea.css`](../../../vendor/filament/forms/resources/css/components/textarea.css) + [`components/date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css) | in progress | [the input affix wrapper](field.md#the-input-affix-wrapper-is-not-ported) | [detail](#the-input-and-select-stylesheets-wait-on-the-affix-wrapper) · [diverges](#the-two-text-controls-invent-a-hover-ring) · [diverges](#the-textarea-rule-is-the-ports-own-because-nothing-autosizes) |
| [`radio.css` — the `.sy-fo-radio` option set and the `.sy-fo-radio-input` control](../../../packages/forms/assets/src/styles/components/radio.css) | [`components/radio.css`](../../../vendor/filament/forms/resources/css/components/radio.css) + [`support/…/input/radio.css`](../../../vendor/filament/support/resources/css/components/input/radio.css) | done | — | [diverges](#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes) · [diverges](radio.md#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) |
| [`repeater.css` — `.sy-fo-repeater` and `.sy-fo-simple-repeater` item chrome](../../../packages/forms/assets/src/styles/components/repeater.css) | [`components/repeater.css`](../../../vendor/filament/forms/resources/css/components/repeater.css) | in progress | the four deferred chrome clusters | [detail](repeater.md#four-chrome-clusters-are-deferred-not-dropped) · [diverges](#the-repeater-item-lists-declare-their-own-grid) |
| [`select.css` — `.sy-fo-select`, the native control and its chevron](../../../packages/forms/assets/src/styles/components/select.css) | [`support/…/input/select.css`](../../../vendor/filament/support/resources/css/components/input/select.css) + [`support/…/input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) | in progress | [`CanBeNative`](select.md#canbenative-is-not-ported) · [the input affix wrapper](field.md#the-input-affix-wrapper-is-not-ported) | [detail](#the-input-and-select-stylesheets-wait-on-the-affix-wrapper) · [diverges](#the-two-text-controls-invent-a-hover-ring) |
| [`toggle-buttons.css` — the `.sy-fo-toggle-buttons` option set and its hidden inputs](../../../packages/forms/assets/src/styles/components/toggle-buttons.css) | [`components/toggle-buttons.css`](../../../vendor/filament/forms/resources/css/components/toggle-buttons.css) | in progress | schemas' `HasInlineLabel` | [semantics](toggle-buttons.md#the-grouped-labels-must-stay-direct-children-of-the-group) · [semantics](toggle-buttons.md#the-selected-state-is-painted-from-the-sibling-input-in-both-variants) · [diverges](toggle-buttons.md#extraattributes-stays-on-the-field-wrapper) |
| [`toggle.css` — the `.sy-fo-toggle` root, track, thumb and icon pair](../../../packages/forms/assets/src/styles/components/toggle.css) | [`support/…/components/toggle.css`](../../../vendor/filament/support/resources/css/components/toggle.css) | done | — | [diverges](toggle.md#the-toggle-is-a-styled-checkbox-not-a-button) · [diverges](toggle.md#the-icon-rules-select-by-name-where-filament-selects-by-position) · [diverges](toggle.md#the-two-colours-ride-on-two-elements) |
| `text-input.css` / `select.css` — the rules no port carries | [`components/`](../../../vendor/filament/forms/resources/css/components/) | blocked | `revealable()` · schemas' `HasInlineLabel` | [detail](#two-vendor-stylesheets-are-partly-rather-than-wholly-unported) |
| `builder.css` / `code-editor.css` / `color-picker.css` / `file-upload.css` (+ `file-upload/filepond-plugin-media-preview.css`) / `key-value.css` / `markdown-editor.css` / `modal-table-select.css` / `rich-editor.css` / `slider.css` / `tags-input.css` | [`components/`](../../../vendor/filament/forms/resources/css/components/) | blocked | Builder, CodeEditor, ColorPicker, FileUpload, KeyValue, MarkdownEditor, ModalTableSelect, RichEditor, Slider, TagsInput | [detail](#the-eleven-unported-forms-stylesheets) |

This table carries no [unaudited row](../README.md#an-unaudited-table-says-so):
every one of the twenty stylesheets in
[`forms/resources/css/components/`](../../../vendor/filament/forms/resources/css/components/)
has a row above. The claim is scoped to that directory on purpose. The
support-tier sources the five cross-package ports draw from are named per row
rather than enumerated, and support's own `resources/css/` is not this table's
subject — `input/one-time-code.css` sits beside the four control sources with no
forms-side port, its component unported, and the rest of that directory belongs
to support's audit.

No test exercises CSS. The render tests assert that the class hooks these rules
attach to (`sy-fo-field` and its label/content/error descendants,
`sy-fo-field-label-inline-control`, `sy-fo-sr-only`, `sy-fo-input`,
`sy-fo-select`, `sy-fo-checkbox`, the six `sy-fo-toggle-*` classes, the
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

### The input and select stylesheets wait on the affix wrapper

Both controls collapse vendor's `.fi-input-wrp` chrome onto the control element,
which [field.md](field.md#the-input-affix-wrapper-is-not-ported) records as the
port's shape and which is also where the two type-specific `input[type=…]` rules
are ruled on. What remains unported follows from that collapse and from the
missing per-field wrapper element:

- **The affix hooks have no element.** `&.fi-input-has-inline-prefix { ps-0 }`,
  its suffix twin and `&.fi-select-input-has-inline-prefix { ps-0 }` address the
  prefix/suffix layer nothing emits.
- **The per-field wrapper rules have no element either.**
  `.fi-fo-text-input { overflow-hidden }`, `.fi-fo-select-wrp` and
  `.fi-fo-textarea-wrp` all style a `<div>` between the field wrapper and the
  control; the port's templates render the `<input>`, `<select>` and `<textarea>`
  as direct children of `.sy-fo-field-content`. Those wrappers arrive with the
  `sy-fo-*-wrp` hooks [field.md](field.md#extra-attribute-precedence-is-fixed-by-source-order)
  already anticipates for all five affected fields.
- **`input.fi-input`'s six `.fi-align-*` arms** (`center`, `end`, `left`,
  `right`, `justify`, `between`) have no port. No field exposes an alignment
  setter, so nothing emits the class — but Filament's own fields do not emit it
  either; a consumer reaches those arms through `extraInputAttributes()`, which
  [the port also appends to the class list](has-extra-input-attributes.md). So the
  hook is reachable here and the rules behind it are not, which is the one arm of
  this list a consumer can trip over today.

`select.css`'s second vendor block — the ninety-odd rules styling the
Choices.js/Alpine custom select, `div[x-ref='select']` included — is not part of
this: it is the styling half of
[`CanBeNative`](select.md#canbenative-is-not-ported), which the port skips in
favour of the native renderer.

### Two vendor stylesheets are partly rather than wholly unported

`text-input.css` holds two rules. `.fi-fo-text-input { @apply overflow-hidden }`
styles the wrapper `<div>` named above, and
`& input.fi-revealable { @apply [&::-ms-reveal]:hidden }` suppresses Edge's
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
render a bare `<input class="sy-fo-input">` with no per-field wrapper element
around it, so there is no `.sy-fo-date-time-picker` for the rule to nest under
and no second rule that would share a file with it. It is written flat as
`.sy-fo-input::-webkit-datetime-edit`, which is harmless on the other controls —
the pseudo-element exists only on date and time inputs. Next step: the file
appears when the calendar does, and this rule moves into it if the calendar
brings a wrapper element with it.

### The eleven unported forms stylesheets

`builder.css`, `code-editor.css`, `color-picker.css`, `file-upload.css` (with
its nested `file-upload/filepond-plugin-media-preview.css`), `key-value.css`,
`markdown-editor.css`, `modal-table-select.css`, `rich-editor.css`,
`slider.css` and `tags-input.css` have no `sy-fo-*` counterpart. Each is
**blocked** on its field — every one of those fields is **todo** in the
[package README](README.md#components) — because the styling lands in the same
change as the Twig template it targets and there is nothing to author against
until then. Next step for each: port when its field does.

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

`checkbox.css`, `input.css`, `radio.css`, `select.css` and `toggle.css` port
`input[type='checkbox'].fi-checkbox-input`, `input.fi-input`,
`input[type='radio'].fi-radio-input`, `select.fi-select-input` and `.fi-toggle` —
support-package classes, which [carry no package code](../renames.md) by the
port's own naming rule (`fi-icon-btn` → `sy-icon-btn`). The port names them
`.sy-fo-checkbox`, `.sy-fo-input`, `.sy-fo-radio-input`, `.sy-fo-select` and
`.sy-fo-toggle` instead, so a support-tier class arrives under the forms code and
in a forms stylesheet. It is the same
tracks-its-emitter call schemas records in the other direction for
[`text.css`](../schemas/styles.md#text-css-was-ported-into-the-support-package):
forms is the only consumer of all five today, and filing a rule beside the one
template that emits it beats filing it beside the vendor that happened to author
it. The re-coding reverses the moment a second package renders one of these
controls — an infolists or tables cell reusing the input chrome would have to
either import a forms-coded class or duplicate the rule, and moving the file down
to support is the cheaper answer at that point.

The radio keeps its `-input` suffix where the checkbox drops one, and that is not
an inconsistency to tidy: `.sy-fo-radio` is already taken by the option-set
container the field renders around the controls, which the checkbox — a single
element with no container — has no equivalent of. Vendor draws the same line for
the same reason, `fi-fo-radio` beside `fi-radio-input`.

The checkbox changes shape as well as tier: vendor qualifies its base rule with
the element and the type attribute (`input[type='checkbox'].fi-checkbox-input`,
**(0,2,1)**) where the port writes the class alone (`.sy-fo-checkbox`,
**(0,1,0)**). Only one element ever carries that class, so the qualifier bought
vendor nothing the template does not already guarantee, and the stylesheet's
internal order survives — our `[aria-invalid='true']` arm outranks our base by
the same one point vendor's `.fi-invalid` outranks its own. What is given up is
external: a host rule now needs (0,1,0) rather than (0,2,1) to reach the box,
the same ledger the
[flattened field stylesheet](#field-css-is-flat-where-filament-nests) sits on.

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
The same-element stylesheets (`checkbox.css`, `input.css`, `select.css`, and
`radio.css`'s three control rules — its option-set half keeps vendor's nesting)
are flat too, but there the flattening is free: every one of their rules is a
compound on the control itself, and a `&:checked` nested under the base rule
compiles to the same selector as the `.sy-fo-checkbox:checked` written out. And
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

Nothing breaks today — `FieldWrapper` is its only emitter — and the reason it is
flagged rather than fixed is that relocating it is cross-package churn for no
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

### The indeterminate checkbox arm has no port

Vendor's `input[type='checkbox'].fi-checkbox-input` nests a whole `&:indeterminate`
block — the dash glyph, `bg-primary-600`, a focus arm, a disabled arm and an
`.fi-invalid` nesting of its own. None of it is ported, and none of it ever will
be under the current field: `HTMLInputElement.indeterminate` is a DOM property
that no server-rendered attribute can set, and the
[`Checkbox` template](../../../packages/forms/templates/partials/Checkbox/index.html.twig)
ships no JavaScript to set it. The selector cannot match, so the rules would be
dead weight in the built stylesheet. They come back only if a tri-state checkbox
ever lands with a controller behind it.

### The checkbox and radio pin their checked glyph's size and placement

Both control rules add three declarations vendor's do not carry — `bg-center`
and `bg-no-repeat` in the `@apply` list, and a literal
`background-size: 1rem 1rem` beside it — in
[`checkbox.css`](../../../packages/forms/assets/src/styles/components/checkbox.css)
and
[`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css)
alike. The glyph is a `background-image` data URI on an `appearance-none` box,
and the SVG declares a `viewBox` and no intrinsic width or height, so with
nothing said a browser sizes it from the background positioning area and the
mark tracks the box. Vendor leaves it there; the port states the intended
result, which is the same 1rem mark at the shipped `size-4` and a mark that
stays 1rem if a host ever resizes the control. Recorded because it is an
addition to a rule otherwise ported line for line — the checkbox's landed first
and went unrecorded, and the radio copied it — and it reverses by deleting all
three from both files, which renders identically today.

### The two text controls invent a hover ring

`.sy-fo-input` and `.sy-fo-select` both carry
`hover:ring-gray-950/20 dark:hover:ring-white/20`. Vendor's `.fi-input-wrp` has no
`:hover` rule anywhere — it goes straight from the resting ring to
`focus-within`. The affordance is the port's own, added on both controls together
so a text field and a select behave alike, and it is cheap to keep: it paints only
the ring colour, so it cannot collide with the invalid arm, which restates
`hover:ring-danger-*` for exactly that reason. Reverses by deleting four
declarations if Filament parity on hover is ever wanted.

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

### The repeater item lists declare their own grid

```css
/* ours */    & .sy-fo-repeater-items { @apply grid items-start gap-4; }
/* vendor */  & .fi-fo-repeater-items { @apply items-start gap-4; }
```

Vendor takes `display: grid` from the container-grid classes
`HasContainerGridLayout` emits onto that element; the port's items grid is
deferred with the rest of that concern
([repeater.md](repeater.md#four-chrome-clusters-are-deferred-not-dropped)), so the
stylesheet supplies the declaration itself on both item lists, the simple
repeater's included. Correct today and wrong the moment the concern lands — an
emitted `sy-grid-cols-*` would then be fighting a hardcoded single-column `grid`
from the same layer. The deferral is recorded on the component; this compensating
declaration is recorded here so the removal is not left to be rediscovered.

_See also: the global [rename table](../renames.md) — the `fi-*` → `sy-fo-*` class
prefix, the `--x` → `--sy-x` custom-property prefix, and the
`fi-fo-field-wrp-error-*` → `sy-fo-field-error-*` rename are recorded there, not
as per-stylesheet divergences._
