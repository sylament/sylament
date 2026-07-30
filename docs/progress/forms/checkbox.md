# Checkbox (forms)

`Sylament\Forms\Components\Checkbox` — the boolean field rendering a native
`<input type="checkbox">`. Extends
[`Field`](../../../packages/forms/src/Components/Field.php), defaults its state
to `false`, casts state through schemas'
[`BooleanStateCast`](../../../packages/schemas/src/State/Casts/BooleanStateCast.php),
and composes the two shared boolean concerns
([`CanBeInline`](can-be-inline.md), [`CanBeAccepted`](can-be-accepted.md)). It
ships no JavaScript: the control is a real checkbox bound with `data-model`, and
`ux-live-component` reads `element.checked` natively.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Checkbox.php`](../../../vendor/filament/forms/src/Components/Checkbox.php)
  — the field class, its `setUp()` and its embedded view
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the field concerns it composes

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details) or a permanent note in
[Semantics](#semantics) / [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering | [`Components/Checkbox.php`](../../../vendor/filament/forms/src/Components/Checkbox.php) | done | — | — |
| `default(false)` + `BooleanStateCast` | [`Components/Checkbox.php`](../../../vendor/filament/forms/src/Components/Checkbox.php) | done | — | — |
| Boolean validation (`Type('bool')`) | [`rule('boolean')`](../../../vendor/filament/forms/src/Components/Checkbox.php) | done | — | [semantics](#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live) |
| [`CanBeInline`](../../../packages/forms/src/Components/Concerns/CanBeInline.php) | [`Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | — | [detail](can-be-inline.md) |
| [`CanBeAccepted`](../../../packages/forms/src/Components/Concerns/CanBeAccepted.php) | [`Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | — | [detail](can-be-accepted.md) |
| Checked state rendered server-side | [`Checkbox::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Checkbox.php) | done | — | [diverges](#the-checked-attribute-is-rendered-server-side) |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | blocked | `Repeater` | — |
| `HasExtraInputAttributes` | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | todo | — | — |

Coverage: the inline / not-inline placement in both directions, the absent
`value` attribute, the hydrated `false` default, the non-nullable cast, the
`checked` attribute, the hidden-label path, the boolean constraint against a raw
non-`bool` state, the `accepted()` / `declined()` constraints reaching the field,
and the `required` / `disabled` / `autofocus` attributes, by
[`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php).
The `aria-invalid` attribute needs a live host to read an error bag from, so it is
pinned in both directions from
[`FieldErrorsTest`](../../../packages/forms/tests/Integration/Components/FieldErrorsTest.php),
whose fixture form carries an `accepted()` checkbox beside its text fields.

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

Neither unfinished row is partly ported. `CanFixIndistinctState` exists to
disambiguate identically-pathed checkboxes inside a `Repeater`, which is itself
unported, so it has nothing to fix yet — **blocked** on that, the same reading
[`Toggle`](toggle.md#fixindistinctstate-awaits-the-repeater) gives it.
`HasExtraInputAttributes` is a shared field concern (its own README row) that no
field reaches yet, and waits on nothing — a setter/getter plus one `attrs()`
source on the template.

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### Validation reads the raw state, so the boolean Type constraint is live

`setUp()` registers `Type('bool')`, the port of Filament's `rule('boolean')` — a
message for it keys `validationMessages([Type::class => …])`, applied by the
[post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built) —
and `getDefaultStateCasts()` appends `BooleanStateCast(isNullable: false)`. The cast
does **not** stand between the host property and the validator: it normalizes
only what [`state()`](../../../packages/schemas/src/Components/Concerns/HasState.php)
writes and what `getState()` reads back, while
[`validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) validates
`$schema->getPropertyAccessor()->get($this, $path)` — the raw, uncast host value,
the same thing `getRawState()` returns. No state cast runs on that path.

So the constraint is a live guard, not a formality. Anything that writes the
state bag without going through `state()` — a tampered live payload, or server
code assigning a string into `$data` — reaches the validator as it was written,
and `Type('bool')` is what rejects it. Deleting it would remove real protection;
`CheckboxTest`'s _validates the raw state rather than the cast one_ case pins
exactly that, asserting a raw `'yes'` violates the constraint even though
`getState()` reports `true`.

An unset field still passes: `TypeValidator` short-circuits on `null` before it
reaches `is_bool`, so a state bag with no key for the field produces no violation
here. `required()` is what rejects that case.

### required() on a checkbox means "must be checked"

`required()` produces a `NotBlank` constraint
([`CanBeValidated`](can-be-validated.md)), and Symfony's `NotBlank` rejects
`false` outright — `false === $value` is the first clause of
[`NotBlankValidator`](../../../vendor/symfony/validator/Constraints/NotBlankValidator.php).
That agrees with the native `required` attribute the template emits, which
browsers read as "this box must be ticked", so the client and the server say the
same thing. [`accepted()`](can-be-accepted.md) is the explicit spelling of the
same rule and is what to reach for when the intent matters to a reader.

**[`Toggle`](toggle.md#required-means-presence-not-truth) deliberately does the
opposite** — it overrides `getRequiredConstraint()` to `NotNull`, so a required
toggle validates while off. The asymmetry is not an oversight to reconcile: each
field's constraint matches the attribute its own template emits. The checkbox
emits native `required`, which the browser enforces as "must be ticked", so a
server rule that accepted `false` would contradict the client and let a submit
through that the browser had already blocked. The toggle emits no such attribute,
so it is free to mean presence. Change one and you must change its template too.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The checked attribute is rendered server-side

Filament emits only `wire:model` on the input and lets Livewire set the checked
property client-side once it boots. The port renders `checked` from
`getState()`, so a server-rendered edit page shows the stored value before any
JavaScript runs. This is the same class of addition as the `aria-invalid`
attribute every field emits — server-side truth for a control whose Filament
original leaves it to the client.

The input carries **no `value` attribute**, and that absence is load-bearing
rather than an omission: `live_controller` binds a valueless checkbox as
`element.checked`, a real boolean, and binds one carrying `value` as that string.

### Both label slots are declared unconditionally

The template hands the input to the wrapper's `labelPrefix` slot when
[`isInline()`](can-be-inline.md) and to the default `content` slot otherwise, and
it declares **both** `<twig:block>` tags unconditionally with the `{% if %}`
inside each. The obvious shape — one `{% if %}` choosing which block to declare —
does not compile: `<twig:block>` becomes a Twig `{% block %}`, whose own parser
subparse swallows the `{% endif %}` and fails with
`Unexpected "endblock" tag (expecting closing tag for the "if" tag)`. The failure
is loud, so this is a note for whoever tries to "simplify" the template, not a
silent trap.
