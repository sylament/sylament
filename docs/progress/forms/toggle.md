---
description: The boolean switch field, a styled native checkbox, with the on/off icon and colour concerns it shares with ToggleColumn.
ports:
  - vendor/filament/forms/src/Components/Toggle.php
  - vendor/filament/forms/src/Components/Concerns/HasToggleColors.php
  - vendor/filament/forms/src/Components/Concerns/HasToggleIcons.php
owns:
  - packages/forms/src/Components/Concerns/HasToggleColors.php
  - packages/forms/src/Components/Concerns/HasToggleIcons.php
  - packages/forms/src/Components/Toggle.php
  - packages/forms/templates/partials/Toggle/
---
# Toggle (forms)

`Sylament\Forms\Components\Toggle` is the boolean switch field.
It extends [`Field`](field.md), whose shared surface is tracked in that file, defaults its state to `false`, casts it to a non-nullable `bool`, and renders a real `<input type="checkbox" role="switch">` that CSS replaces with a track and a thumb.
It composes the shared boolean concerns [`CanBeInline`](can-be-inline.md) and [`CanBeAccepted`](can-be-accepted.md), and the on/off icon and colour concerns it shares with tables' [`ToggleColumn`](../tables/toggle-column.md).
The Surface table carries no [unaudited row](../../internals/trail.md#an-unaudited-table-says-so) because every member of the vendor class and its toggle concerns has a row.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Switch rendering | [`Toggle::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#the-toggle-is-a-styled-checkbox-not-a-button) |
| `false` default + `BooleanStateCast(isNullable: false)` | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | — |
| `boolean` validation | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#the-boolean-rule-becomes-a-type-constraint) |
| Inline layout (`CanBeInline`) | [`Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | — | [detail](can-be-inline.md) |
| Acceptance (`CanBeAccepted`) | [`Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | — | [detail](can-be-accepted.md) |
| [`HasToggleIcons`](../../../packages/forms/src/Components/Concerns/HasToggleIcons.php) | [`Concerns/HasToggleIcons.php`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleIcons.php) | done | — | [diverges](#hastoggleicons-tests-for-null-not-truthiness) · [diverges](#the-icon-rules-select-by-name-where-filament-selects-by-position) |
| Error display (`aria-invalid` on the input) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| `required()`: no attribute, and a `NotNull` constraint | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#required-means-presence-not-truth) |
| [`isBlankStateValue()`](../../../packages/forms/src/Components/Toggle.php): an untouched switch reads blank | — | done | — | [semantics](../schemas/has-nullable-branch.md#the-built-in-blank-test-asks-the-branchs-own-leaves) |
| [`isRequiredByRecord()`](../../../packages/forms/src/Components/Toggle.php): no requirement derived from a non-nullable `bool` target | — | done | — | [semantics](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types) |
| [`HasToggleColors`](../../../packages/forms/src/Components/Concerns/HasToggleColors.php) (`onColor()` / `offColor()`) | [`Concerns/HasToggleColors.php`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleColors.php) | done | — | [diverges](#the-two-colours-ride-on-two-elements) · [diverges](#oncolor-and-offcolor-accept-an-array-palette) |
| `CanFixIndistinctState` (`fixIndistinctState()`) | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [detail](can-fix-indistinct-state.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) · [diverges](#the-toggle-carries-extra-input-attributes-filaments-does-not) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |

## Divergences

### The toggle is a styled checkbox, not a button

Filament renders `<button role="switch" type="button">` with an Alpine `x-data="{ state: … }"` holding the on/off value, `x-on:click` flipping it, `x-bind:class` swapping the colour classes, and an extra `x-cloak` placeholder element to avoid a flash before Alpine boots.
The port renders a real `<input type="checkbox" role="switch">` that CSS visually replaces with a track and a thumb; every state transition is a sibling selector (`:checked ~`, `:focus-visible ~`, `:disabled ~`).
The on/off icon cross-fade is Filament's own CSS unchanged: Alpine only toggles the `fi-toggle-on` / `fi-toggle-off` class there, and `:checked ~` replaces that class one for one, so the stacked `absolute inset-0` icons and their opacity/duration pairs port verbatim.
Only the two selectors that pick the icons out change, and [that substitution is load-bearing](#the-icon-rules-select-by-name-where-filament-selects-by-position).

Three things this buys, and they are why it holds:

- **The live binding comes for free.**
  `ux-live-component`'s `live_controller` reads `element.checked` for a checkbox that carries no `value` attribute, so `data-model` binds the boolean with no adapter.
  The button form would need a controller to write the value back.
- **Native semantics.**
  Keyboard activation, the tab order, the checked state exposed to assistive technology, and label association through the wrapping `<label>` are all the platform's, not ours to re-implement.
- **Native form participation.**
  The input is a real form control, so it submits inside the modal `<form>` an action renders.

The consequence worth stating plainly: **this field ships no JavaScript.**
There is no Stimulus controller for it.

The input is `sr-only`, **not** `display: none`.
The latter would remove it from the tab order and from form submission, which is precisely the platform behaviour the divergence exists to keep.

The public API — `inline()`, `accepted()`, `declined()`, `onIcon()`, `offIcon()`, the `false` default, the boolean cast — is unchanged, so this is a rendering divergence only.

Because the visible switch is CSS on a `sr-only` input, the track has to sit inside a `<label>` or it is unclickable — Filament's `<button>` needs no such help.
The control's root element therefore changes with the layout: a `<span>` in the inline path, where it renders into `FieldWrapper`'s `labelPrefix` slot and a nested `<label>` would be invalid HTML, and a `<label for="…">` in the non-inline path, where it stands alone in the content column.
Without that swap a non-inline toggle is keyboard-only, and a non-inline toggle with no visible label has nothing associated with it at all.

One implementation constraint the template must respect: Twig hoists `{% block %}` definitions at parse time, so a `<twig:block>` nested inside an `{% if %}` is still registered and the wrapper's `block('labelPrefix')` returns its content regardless of the condition.
The template therefore declares **both** the `labelPrefix` and `content` blocks unconditionally and puts the `component.isInline` test *inside* each, and `ToggleTest` asserts the control renders once in each layout.

### The icon rules select by name where Filament selects by position

Filament reaches the two glyphs positionally.
Inside [`toggle.css`](../../../vendor/filament/support/resources/css/components/toggle.css) the thumb is `& > :first-child`, and the `&.fi-toggle-on` / `&.fi-toggle-off` arms drive the icons under it as `& > :first-child` (off) and `& > :last-child` (on), each carrying the opacity/duration/easing pair for its state.
The port's [`toggle.css`](../../../packages/forms/assets/src/styles/components/toggle.css) names them instead — `.sy-fo-toggle-icon-off` and `.sy-fo-toggle-icon-on`, classes the template puts on the two `<span>`s.

That substitution is what keeps a **single-icon** toggle correct, not a readability preference.
Filament's [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Toggle.php) emits both `<div aria-hidden="true">` wrappers unconditionally and lets the icon HTML inside either one be empty, so its thumb always has exactly two children and the two positional selectors always pick different elements.
The port's [template](../../../packages/forms/templates/partials/Toggle/index.html.twig) gates each `<span>` on `hasOffIcon` / `hasOnIcon`, so a toggle given one icon renders one child — and a lone child is both `:first-child` and `:last-child`.
Ported positionally, both rules would match it at the same specificity and the later one, `:last-child`, would win in each state: an `offIcon()`-only switch would then hide its icon while off and reveal it while on, exactly inverted, and an `onIcon()`-only one would be right by accident.
A named class cannot pick the wrong element whichever of the two is present, and [`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php) renders each icon alone for that reason.

The other way to keep vendor's selectors would be to emit both spans always, as Filament does.
That buys nothing back: it puts an empty element in every toggle's DOM and gives up two names [`docs/internals/styling.md`](../../internals/styling.md#why-are-the-emitted-classes-public) counts as public hook surface, in exchange for a selector that has to be reasoned about each time it is read.
So this is settled rather than provisional, and a future audit against the vendor stylesheet that finds the selectors differ should not "correct" them.

### The toggle carries extra input attributes, Filament's does not

Filament composes `HasExtraInputAttributes` on thirteen form components and pointedly not on [`Toggle`](../../../vendor/filament/forms/src/Components/Toggle.php), which has no input element for it to address: the control is an Alpine `<button>`, and `extraAttributes()` is merged straight onto that button, so a Filament consumer already reaches the control by the setter every component has.

Neither half of that holds here.
The port's toggle is [a real `<input type="checkbox" role="switch">`](#the-toggle-is-a-styled-checkbox-not-a-button), so there *is* an input; and `extraAttributes()` does not reach it, because on a field it renders on the wrapper `<div>` rather than on the control ([why](field.md#extra-attribute-precedence-is-fixed-by-source-order)).
Withholding the concern would therefore leave the toggle the one field whose control no setter can reach — the opposite of what Filament's omission achieves.

The extras merge onto `.sy-fo-toggle-input`, not the `.sy-fo-toggle` root that CSS paints the track from.
The input is the element every other field's extras land on, so `extraInputAttributes()` means the same thing on every field: it addresses the form control.

### The two colours ride on two elements

Filament resolves both colours in PHP and hands Alpine the two class lists, which `x-bind:class="state ? onClasses : offClasses"` swaps on the single `<button>`.
This field ships no JavaScript, so both lists have to be in the markup at once — and they cannot share an element, because [`ColorManager::getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php) names the same slot variables (`--sy-bg`, `--sy-dark-bg`, `--sy-text`) whichever colour it resolved, so the second set would overwrite the first.

The off colour therefore lands on the control root (`.sy-fo-toggle`) and the on colour on the track inside it.
Every rule in [`toggle.css`](../../../packages/forms/assets/src/styles/components/toggle.css) that paints a *configured* colour is gated on the `.sy-color` marker of the element carrying its own — what the ungated rules paint (the base track, the thumb, the focus and invalid rings, the disabled state, the icon swap) neither setter can reach.

Gating on the marker is necessary but not sufficient, because slot *variables* inherit where classes do not, and a colour name the registry does not know still gets the marker: [`ColorManager::getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php) returns `.sy-color` plus the swap class and stops there, so the element degrades to inheriting rather than silently rendering primary.
Two declarations close that gap.
The root's slots are aliased once into `--sy-off-*`, which nothing below redeclares, so the unchecked rules can read them off the track.
And the track clears the three slots it reads, so one marked `.sy-color` by an unknown name resolves them to the guaranteed-invalid value rather than to whatever the off colour set on the root — without the clear, `->offColor('danger')->onColor('nonsense')` paints the *off* colour on the checked track.
The clear cannot swallow a real on colour, because [`forms/index.css`](../../../packages/forms/assets/src/styles/index.css) imports this stylesheet into the `components` layer and support's [`index.css`](../../../packages/support/assets/src/styles/index.css) orders that below the `utilities` layer holding `sy-bg-color-*`; an array palette arrives as an inline style and outranks both.

Both getters return `null` when unset, as Filament's do; the template supplies Filament's `'gray'` off and `'primary'` on fallbacks at the point Filament's `toEmbeddedHtml()` does.
Gray resolves to no classes at all ([`ToggleComponent`](../../../packages/support/src/Colors/Components/ToggleComponent.php) implements `HasDefaultGrayColor`), so the unchecked track keeps the stylesheet's `bg-gray-200`, the checked track is painted by primary's resolved slots, and an explicit `onColor('gray')` renders a gray switch, as Filament's does.

### The boolean rule becomes a Type constraint

Filament writes `rule('boolean')` in `setUp()`.
The port registers `new Type('bool')` — the [rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary), not a new mechanism.
Laravel's `boolean` rule also accepts `0`, `1`, `"0"` and `"1"`; Symfony's `Type('bool')` accepts only a real `bool`.

The narrowing is tolerable because of what reaches validation, and it is worth being precise about the mechanism: the host validates the **raw** state property ([`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) reads `getPropertyAccessor()->get($this, $path)`), so the state casts are *not* in front of validation — `get()` runs on read and `set()` only in `hydrateState()`, which a live round trip never reaches.
What guarantees a real `bool` is the client: `live_controller` sends `element.checked` for a checkbox carrying no `value` attribute, so the value written into the state bag is a JSON boolean, and the mount-time `hydrateState()` pass seeds the same shape from the `false` default.
A payload that says otherwise is a tampered one, and the `Type` constraint failing closed on it is the behaviour we want.

The constraint is a plain instance, and a message keys it as `validationMessages([Type::class => …])`.
Call order does not matter: the [post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built) applies messages over the finished list, so a call made after construction still lands.

### required() means presence, not truth

On a boolean field `required()` asks for a state, not for a *true* state: an explicit `false` satisfies it just as much as `true`.
Laravel agrees — its `required` rule fails only on `null`, `''`, an empty array or an empty `Countable`, so `Toggle::make('x')->required()` in Filament passes with the switch off.
Two consequences, both divergences from the port's own defaults:

- **The constraint is `NotNull`.**
  The toggle overrides `getRequiredConstraint()`, the [subclass seam](can-be-validated.md#the-required-constraint-is-swappable-per-field) that ports Filament's `getRequiredValidationRule()` hook, and returns `NotNull`.
  The shared [`RequiredValue`](can-be-validated.md#required-carries-laravels-blank-set-not-symfonys) reads `false` as present too, so the override says something narrower: presence here is *not null* and nothing more, and the blank string a tampered payload could put on a boolean path passes where `RequiredValue` would violate.
  Symfony's `NotBlank` would be wrong here, because it violates on `false` and would make `required()` mean `accepted()`.
  A message for it keys `NotNull::class`, not `RequiredValue::class` — the constraint the field actually emits, which is the whole rule under [class keying](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built).
- **No `required` attribute on the input.**
  `TextInput` and `Textarea` emit it from `isMarkedAsRequired()`; the toggle does not, because a native `required` on a checkbox means "must be checked" — the browser would block the submit the server constraint accepts.
  Filament faces the same question and answers it the same way, emitting neither attribute on its button.

Consumers wanting "must be on" call `accepted()`, which builds the constraint that actually says so.
The label asterisk is unaffected: it follows `isMarkedAsRequired()`, which is still true.

**[`Checkbox`](checkbox.md#required-on-a-checkbox-means-must-be-checked) swaps to Symfony's `NotBlank` on purpose**, so `required()` there does mean "must be checked".
That is not an inconsistency waiting to be tidied away: the checkbox emits the native `required` attribute and the toggle does not, so each field's server constraint says exactly what its own markup already enforces in the browser.
The natural reading is that the checkbox carries a defect the toggle avoids, and it does not.

### HasToggleIcons tests for null, not truthiness

Filament's `hasOnIcon()` / `hasOffIcon()` return `(bool) $this->getOnIcon()`.
The port returns `$this->getOnIcon() !== null`.
The difference only shows for a falsy-but-set icon — an empty string, or a `Markup` wrapping `''` — which Filament treats as unset and the port treats as set.
`null` is the trait's own unset sentinel (there is no `false` sentinel here, unlike support's `HasIcon`), so asking for `null` is asking the question the property's type actually poses.
An empty icon name is a caller bug either way; hiding it behind a truthiness test would only make it silent.

### `onColor()` and `offColor()` accept an array palette

Filament's `HasToggleColors` types both properties and setters `string | Closure | null` and both getters `?string`, a colour name only; the setter names, the `null` default and the getters' evaluation are otherwise identical.
The port types the properties and setters `string|array<string>|Closure|null` and the getters `string|array<string>|null`, the shape Filament's own support `HasColor` takes, so a toggle accepts the inline palette every other colour setter in the port accepts.
The template hands both getters to `colorAttributes()`, which renders an array palette as the `.sy-color` marker plus inline `--sy-color-*` declarations, and the [two-element split](#the-two-colours-ride-on-two-elements) above keeps the two palettes apart.
`ToggleColumn` composes the same trait, so the widening reaches it too.
