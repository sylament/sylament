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
| Required attribute | [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php) | done | — | [diverges](#the-toggle-emits-no-required-attribute) |
| `HasToggleColors` (`onColor()` / `offColor()`) | [`Concerns/HasToggleColors.php`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleColors.php) | blocked | the colour registry | [detail](#hastogglecolors-awaits-the-colour-registry) |
| `CanFixIndistinctState` (`fixIndistinctState()`) | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | blocked | `Repeater` + the distinct/live surface | [detail](#fixindistinctstate-awaits-the-repeater) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — | [detail](#the-alpine-attribute-concern-is-not-ported) |

Coverage:
[`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php)
renders the field through a real `Schema` and asserts the switch markup, the
input's identity and binding attributes, the absence of a `value` attribute, the
inline/not-inline placement (in both directions, and that the control renders
exactly *once* — the guard against the hoisted-`{% block %}` trap described
below), the hydrated `false` default and its `bool` round trip, the `checked`
attribute in both states, the icon spans present and absent, the `bool` `Type`
constraint, `disabled` / `autofocus` / `aria-invalid`, and the deliberately
missing `required` attribute.

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**.

### HasToggleColors awaits the colour registry

Filament's `onColor()` / `offColor()` resolve through
`get_component_color_classes(ToggleComponent::class, …)`, a facade-backed
registry mapping a colour name onto a class list. There is no colour registry in
the port yet, so the toggle hardcodes primary-on / gray-off in
[`toggle.css`](../../../packages/forms/assets/src/styles/components/toggle.css),
exactly as `.sy-fo-input` hardcodes `ring-primary-600`. **Blocked**, not skipped:
the trait is two setters and two getters and lands with the colour work, at which
point the track and thumb rules take their colour from the resolved tokens
instead of the literals.

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
(`:checked ~`, `:focus-visible ~`, `:disabled ~`).

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

One implementation constraint the template must respect: Twig hoists
`{% block %}` definitions at parse time, so a `<twig:block>` nested inside an
`{% if %}` is still registered and the wrapper's `block('labelPrefix')` returns
its content regardless of the condition. The template therefore declares **both**
the `labelPrefix` and `content` blocks unconditionally and puts the
`component.isInline` test *inside* each; the "renders exactly once" tests pin it.

### The boolean rule becomes a Type constraint

Filament writes `rule('boolean')` in `setUp()`. The port registers
`new Type('bool')` — the
[rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary),
not a new mechanism. Laravel's `boolean` rule also accepts `0`, `1`, `"0"` and
`"1"`; Symfony's `Type('bool')` accepts only a real `bool`. The narrowing cannot
bite, because `getDefaultStateCasts()` puts a
`BooleanStateCast(isNullable: false)` in front of validation, so the validated
value is always a real `bool`. The constraint is registered as a **closure** so a
`validationMessages(['boolean' => …])` call made after construction still lands.

### The toggle emits no required attribute

`TextInput` and `Textarea` emit `required` from `isMarkedAsRequired()`. The
toggle does not. A native `required` on a checkbox means "must be checked" —
which is `accepted()`'s meaning, not `required()`'s: a boolean field that is
required is satisfied by an explicit `false` just as much as by `true`. Filament
faces the same question and answers it the same way, emitting neither attribute
on its button. Consumers wanting "must be on" call `accepted()`, which builds the
constraint that actually says so.

### HasToggleIcons tests for null, not truthiness

Filament's `hasOnIcon()` / `hasOffIcon()` return `(bool) $this->getOnIcon()`. The
port returns `$this->getOnIcon() !== null`. The difference only shows for a
falsy-but-set icon — an empty string, or a `Markup` wrapping `''` — which
Filament treats as unset and the port treats as set. `null` is the trait's own
unset sentinel (there is no `false` sentinel here, unlike support's `HasIcon`),
so asking for `null` is asking the question the property's type actually poses.
An empty icon name is a caller bug either way; hiding it behind a truthiness test
would only make it silent.
