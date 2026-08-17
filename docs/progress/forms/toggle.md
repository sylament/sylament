# Toggle (forms)

`Sylament\Forms\Components\Toggle` — the boolean switch field. Extends
[`Field`](../../../packages/forms/src/Components/Field.php), defaults its state
to `false`, casts it to a non-nullable `bool`, and renders a real
`<input type="checkbox" role="switch">` that CSS replaces with a track and a
thumb. It composes the two shared boolean concerns
([`CanBeInline`](can-be-inline.md), [`CanBeAccepted`](can-be-accepted.md)) and
adds its own on/off icon pair.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php)
  — the field class, its `setUp()` and its embedded view
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the concerns it composes

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details) or a permanent note in
[Divergences](#divergences); a **done** row without a permanent note reads `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Switch rendering | [`Toggle::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#the-toggle-is-a-styled-checkbox-not-a-button) |
| `false` default + `BooleanStateCast(isNullable: false)` | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | — |
| `boolean` validation | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#the-boolean-rule-becomes-a-type-constraint) |
| Inline layout (`CanBeInline`) | [`Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | — | [detail](can-be-inline.md) |
| Acceptance (`CanBeAccepted`) | [`Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | — | [detail](can-be-accepted.md) |
| [`HasToggleIcons`](../../../packages/forms/src/Components/Concerns/HasToggleIcons.php) | [`Concerns/HasToggleIcons.php`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleIcons.php) | done | — | [diverges](#hastoggleicons-tests-for-null-not-truthiness) |
| Error display (`aria-invalid` on the input) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| `required()` — no attribute, and a `NotNull` constraint | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#required-means-presence-not-truth) |
| [`isBlankStateValue()`](../../../packages/forms/src/Components/Toggle.php) — an untouched switch reads blank | — | done | — | [semantics](../schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves) |
| [`HasToggleColors`](../../../packages/forms/src/Components/Concerns/HasToggleColors.php) (`onColor()` / `offColor()`) | [`Concerns/HasToggleColors.php`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleColors.php) | done | — | [diverges](#the-two-colours-ride-on-two-elements) |
| `CanFixIndistinctState` (`fixIndistinctState()`) | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | blocked | `Repeater` + the distinct/live surface | [detail](#fixindistinctstate-awaits-the-repeater) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) · [diverges](#the-toggle-carries-extra-input-attributes-filaments-does-not) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — | [detail](#the-alpine-attribute-concern-is-not-ported) |

Coverage:
[`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php)
renders the field through a real `Schema` and asserts the switch markup, the
input's identity and binding attributes, the absence of a `value` attribute, the
inline/not-inline placement (in both directions, that the control renders exactly
*once* — the guard against the hoisted-`{% block %}` trap described below — and
that its root element is the `<span>` or the `<label for>` the layout calls for),
the hydrated `false` default and its `bool` round trip, the `checked` attribute in
both states, the icon spans present and absent and each one alone (so the on and
off branches cannot pass for each other), the `bool` `Type` constraint and its
post-hoc message, `required()` building `NotNull` and validating an off switch,
`disabled` / `autofocus`, `aria-invalid` in both directions (the `true` case
against a real validator run, which the `.sy-fo-toggle-input[aria-invalid='true']`
ring depends on), the deliberately missing `required` attribute, the two
colours landing on the elements that carry them — the resolved class list for a
named palette, the inline `--sy-color-*` declarations for an array one, primary
on the track and nothing on the root when neither is set, and nothing at all for
gray — the extra input attributes reaching the input rather than the root and
outranking what the field computes, and `isBlankStateValue()` answering `true`
for `false` and `null` but not
`true`, the override that keeps a resting switch from holding a
[`nullWhenBlank()` branch](../schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves)
alive.

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**.

### fixIndistinctState awaits the repeater

Filament's `fixIndistinctState()` composes `distinct()`, `live()` and an
`afterStateUpdated()` hook that walks up to the parent `Repeater` and switches
every sibling item off when this one is switched on. None of `Repeater`,
`distinct()` or the parent-repeater lookup exists here, so the trait has nothing
to build on. **Blocked** until the repeater lands.

### The Alpine attribute concern is not ported

`HasExtraAlpineAttributes` lets a consumer hang arbitrary `x-*` bindings on the
rendered element. It is a shared field concern with its own README row, not
toggle-specific, and it is doubly moot here: the port renders no Alpine at all
(see the divergence below). **Next step:** decide whether the concern gets a
Stimulus-shaped equivalent at all, and record that decision on its own row rather
than here.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### The toggle is a styled checkbox, not a button

Filament renders `<button role="switch" type="button">` with an Alpine
`x-data="{ state: … }"` holding the on/off value, `x-on:click` flipping it,
`x-bind:class` swapping the colour classes, and an extra `x-cloak` placeholder
element to avoid a flash before Alpine boots. The port renders a real
`<input type="checkbox" role="switch">` that CSS visually replaces with a track
and a thumb; every state transition is a sibling selector
(`:checked ~`, `:focus-visible ~`, `:disabled ~`). The on/off icon cross-fade is
Filament's own CSS unchanged: Alpine only toggles the `fi-toggle-on` /
`fi-toggle-off` class there, and `:checked ~` replaces that class one for one, so
the stacked `absolute inset-0` icons and their opacity/duration pairs port
verbatim.

Three things this buys, and they are why it holds:

- **The live binding comes for free.** `ux-live-component`'s `live_controller`
  reads `element.checked` for a checkbox that carries no `value` attribute, so
  `data-model` binds the boolean with no adapter. The button form would need a
  controller to write the value back.
- **Native semantics.** Keyboard activation, the tab order, the checked state
  exposed to assistive technology, and label association through the wrapping
  `<label>` are all the platform's, not ours to re-implement.
- **Native form participation.** The input is a real form control, so it
  submits inside the modal `<form>` an action renders.

The consequence worth stating plainly: **this field ships no JavaScript.** There
is no Stimulus controller and no `assets/src` entry for it.

The input is `sr-only`, **not** `display: none`. The latter would remove it from
the tab order and from form submission, which is precisely the platform
behaviour the divergence exists to keep.

The public API — `inline()`, `accepted()`, `declined()`, `onIcon()`,
`offIcon()`, the `false` default, the boolean cast — is unchanged, so this is a
rendering divergence only.

Because the visible switch is CSS on a `sr-only` input, the track has to sit
inside a `<label>` or it is unclickable — Filament's `<button>` needs no such
help. The control's root element therefore changes with the layout: a `<span>`
in the inline path, where it renders into `FieldWrapper`'s `labelPrefix` slot and
a nested `<label>` would be invalid HTML, and a `<label for="…">` in the
non-inline path, where it stands alone in the content column. Without that swap a
non-inline toggle is keyboard-only, and a non-inline toggle with no visible label
has nothing associated with it at all.

One implementation constraint the template must respect: Twig hoists
`{% block %}` definitions at parse time, so a `<twig:block>` nested inside an
`{% if %}` is still registered and the wrapper's `block('labelPrefix')` returns
its content regardless of the condition. The template therefore declares **both**
the `labelPrefix` and `content` blocks unconditionally and puts the
`component.isInline` test *inside* each; the "renders exactly once" tests pin it.

### The toggle carries extra input attributes, Filament's does not

Filament composes `HasExtraInputAttributes` on thirteen form components and
pointedly not on
[`Toggle`](../../../vendor/filament/forms/src/Components/Toggle.php), which has
no input element for it to address: the control is an Alpine `<button>`, and
`extraAttributes()` is merged straight onto that button, so a Filament consumer
already reaches the control by the setter every component has.

Neither half of that holds here. The port's toggle is
[a real `<input type="checkbox" role="switch">`](#the-toggle-is-a-styled-checkbox-not-a-button),
so there *is* an input; and `extraAttributes()` does not reach it, because on a
field it renders on the wrapper `<div>` rather than on the control
([why](field.md#extra-attribute-precedence-is-fixed-by-source-order)). Withholding
the concern would therefore leave the toggle the one field whose control no
setter can reach — the opposite of what Filament's omission achieves.

The extras merge onto `.sy-fo-toggle-input`, not the `.sy-fo-toggle` root that
CSS paints the track from. The input is the element the other four fields' extras
land on, so `extraInputAttributes()` means the same thing on every field: it
addresses the form control. `ToggleTest` pins both halves — the attribute on the
input, and no attribute on the root.

### The two colours ride on two elements

Filament resolves both colours in PHP and hands Alpine the two class lists,
which `x-bind:class="state ? onClasses : offClasses"` swaps on the single
`<button>`. This field ships no JavaScript, so both lists have to be in the
markup at once — and they cannot share an element, because
[`ColorManager::getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php)
names the same slot variables (`--sy-bg`, `--sy-dark-bg`, `--sy-text`) whichever
colour it resolved, so the second set would overwrite the first.

The off colour therefore lands on the control root (`.sy-fo-toggle`) and the on
colour on the track inside it. Every rule in
[`toggle.css`](../../../packages/forms/assets/src/styles/components/toggle.css)
that paints a *configured* colour is gated on the `.sy-color` marker of the
element carrying its own — what the ungated rules paint (the base track, the
thumb, the focus and invalid rings, the disabled state, the icon swap) neither
setter can reach.

Gating on the marker is necessary but not sufficient, because slot *variables*
inherit where classes do not, and a colour name the registry does not know still
gets the marker:
[`ColorManager::getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php)
returns `.sy-color` plus the swap class and stops there, so the element degrades
to inheriting rather than silently rendering primary. Two declarations close that
gap. The root's slots are aliased once into `--sy-off-*`, which nothing below
redeclares, so the unchecked rules can read them off the track. And the track
clears the three slots it reads, so one marked `.sy-color` by an unknown name
resolves them to the guaranteed-invalid value rather than to whatever the off
colour set on the root — without the clear,
`->offColor('danger')->onColor('nonsense')` paints the *off* colour on the
checked track. The clear cannot swallow a real on colour, because
[`forms/index.css`](../../../packages/forms/assets/src/styles/index.css) imports
this stylesheet into the `components` layer and support's
[`index.css`](../../../packages/support/assets/src/styles/index.css) orders that
below the `utilities` layer holding `sy-bg-color-*`; an array palette arrives as
an inline style and outranks both.

Both getters return `null` when unset, as Filament's do; the template supplies
Filament's `'gray'` off and `'primary'` on fallbacks at the point Filament's
`toEmbeddedHtml()` does. Gray resolves to no classes at all
([`ToggleComponent`](../../../packages/support/src/Colors/Components/ToggleComponent.php)
implements `HasDefaultGrayColor`), so the unchecked track keeps the stylesheet's
`bg-gray-200`, and the checked track is painted by primary's resolved slots
instead of a hardcoded `bg-primary-600`. That hardcoding is what the fallback
replaces: it fired on *any* uncoloured track, so an explicit `onColor('gray')`
rendered an amber switch where Filament renders a gray one. Its dark half was
hardcoded too, so a default checked toggle is now `primary-600` in dark mode —
the shade the contrast map picks against `gray-700` — rather than `primary-500`.

### The boolean rule becomes a Type constraint

Filament writes `rule('boolean')` in `setUp()`. The port registers
`new Type('bool')` — the
[rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary),
not a new mechanism. Laravel's `boolean` rule also accepts `0`, `1`, `"0"` and
`"1"`; Symfony's `Type('bool')` accepts only a real `bool`.

The narrowing is tolerable because of what reaches validation, and it is worth
being precise about the mechanism: the host validates the **raw** state property
([`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php)
reads `getPropertyAccessor()->get($this, $path)`), so the state casts are *not*
in front of validation — `get()` runs on read and `set()` only in
`hydrateState()`, which a live round trip never reaches. What guarantees a real
`bool` is the client: `live_controller` sends `element.checked` for a checkbox
carrying no `value` attribute, so the value written into the state bag is a JSON
boolean, and the mount-time `hydrateState()` pass seeds the same shape from the
`false` default. A payload that says otherwise is a tampered one, and the `Type`
constraint failing closed on it is the behaviour we want.

The constraint is a plain instance, and a message keys it as
`validationMessages([Type::class => …])`. Call order does not matter — the
[post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)
applies messages over the finished list, so a call made after construction still
lands. It was registered as a closure until that post-pass existed, purely to
defer the message read.

### required() means presence, not truth

On a boolean field `required()` asks for a state, not for a *true* state: an
explicit `false` satisfies it just as much as `true`. Laravel agrees — its
`required` rule fails only on `null`, `''`, an empty array or an empty
`Countable`, so `Toggle::make('x')->required()` in Filament passes with the
switch off. Two consequences, both divergences from the port's own defaults:

- **The constraint is `NotNull`, not `NotBlank`.** Symfony's `NotBlank` violates
  on `false === $value`
  ([`NotBlankValidator`](../../../vendor/symfony/validator/Constraints/NotBlankValidator.php)),
  so the shared `CanBeValidated` constraint would make a required toggle
  unsubmittable while off — `required()` would silently mean `accepted()`. The
  toggle overrides `getRequiredConstraint()`, the
  [subclass seam](can-be-validated.md#the-required-constraint-is-swappable-per-field)
  that ports Filament's `getRequiredValidationRule()` hook, and returns
  `NotNull`. A message for it therefore keys `NotNull::class`, not
  `NotBlank::class` — the constraint the field actually emits, which is the whole
  rule under
  [class keying](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built).
- **No `required` attribute on the input.** `TextInput` and `Textarea` emit it
  from `isMarkedAsRequired()`; the toggle does not, because a native `required`
  on a checkbox means "must be checked" — the browser would block the submit the
  server constraint accepts. Filament faces the same question and answers it the
  same way, emitting neither attribute on its button.

Consumers wanting "must be on" call `accepted()`, which builds the constraint
that actually says so. The label asterisk is unaffected: it follows
`isMarkedAsRequired()`, which is still true.

**[`Checkbox`](checkbox.md#required-on-a-checkbox-means-must-be-checked) keeps
the shared `NotBlank` on purpose**, so `required()` there does mean "must be
checked". That is not an inconsistency waiting to be tidied away: the checkbox
emits the native `required` attribute and the toggle does not, so each field's
server constraint says exactly what its own markup already enforces in the
browser. A review of this epic read the checkbox as carrying the same defect the
toggle had — it does not, and this paragraph exists because that reading is the
natural one.

### HasToggleIcons tests for null, not truthiness

Filament's `hasOnIcon()` / `hasOffIcon()` return `(bool) $this->getOnIcon()`. The
port returns `$this->getOnIcon() !== null`. The difference only shows for a
falsy-but-set icon — an empty string, or a `Markup` wrapping `''` — which
Filament treats as unset and the port treats as set. `null` is the trait's own
unset sentinel (there is no `false` sentinel here, unlike support's `HasIcon`),
so asking for `null` is asking the question the property's type actually poses.
An empty icon name is a caller bug either way; hiding it behind a truthiness test
would only make it silent.
