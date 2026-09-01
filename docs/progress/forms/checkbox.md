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
| [`isBlankStateValue()`](../../../packages/forms/src/Components/Checkbox.php) — an untouched box reads blank | — | done | — | [semantics](../schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves) |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | todo | — | — |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) · [diverges](#the-checkboxs-extra-input-attributes-win-where-filaments-lose) |
| [`required` drops when concealed](../../../packages/forms/templates/partials/Checkbox/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Checkbox.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |

Coverage: the inline / not-inline placement in both directions, the extra input
attributes reaching the `<input>` and outranking what the field computes, the
absent `value` attribute, the hydrated `false` default, the non-nullable cast, the
`checked` attribute, the hidden-label path, the boolean constraint against a raw
non-`bool` state, the `accepted()` / `declined()` constraints reaching the field,
the `required` / `disabled` / `autofocus` attributes, and `isBlankStateValue()`
answering `true` for `false` and `null` but not `true` — the override that keeps a
resting box from holding a
[`nullWhenBlank()` branch](../schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves)
alive — by
[`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php).
The `aria-invalid` attribute needs a live host to read an error bag from, so it is
pinned in both directions from
[`FieldErrorsTest`](../../../packages/forms/tests/Integration/Components/FieldErrorsTest.php),
whose fixture form carries an `accepted()` checkbox beside its text fields.

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

The one unfinished row is not partly ported. `CanFixIndistinctState` exists to
disambiguate identically-pathed checkboxes inside a `Repeater`. Every dependency
ships now — the repeater, the parent-repeater lookup the hook walks, and
[`distinct()`](can-be-validated.md#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule)
as of M3 · 8 — leaving the hook itself for
[M3 · 9](../roadmap.md#m3--forms-widening)'s field sweep, the same reading
[`Toggle`](toggle.md#fixindistinctstate-awaits-the-repeater) gives it.

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

One shape of raw value is carved out of that guard, and it costs nothing:
`Type('bool')` is not implicit, so schemas'
[presence gate](../schemas/presence-gate.md) filters it out when the raw value is
a blank string, and a tampered `''` is accepted where `'yes'` is rejected.
Laravel's `boolean` rule is not implicit either, so this is the faithful
behaviour, and the value it lets through is one the cast already normalizes —
`BooleanStateCast(isNullable: false)` reads `''` as `false`, so what the mapper
writes to a `bool` target is a `bool`. `accepted()` and `declined()` are the arms
that must still bite on `''`, and they do:
[`IsTrue` and `IsFalse` are in the implicit set](../schemas/presence-gate.md#istrue-and-isfalse-are-in-the-set-because-the-runner-validates-raw-state)
precisely because this path exists.

An unset field still passes: `TypeValidator` short-circuits on `null` before it
reaches `is_bool`, so a state bag with no key for the field produces no violation
here. `required()` is what rejects that case.

### required() on a checkbox means "must be checked"

The field overrides `getRequiredConstraint()` to Symfony's `NotBlank`, which
rejects `false` outright — `false === $value` is the first clause of
[`NotBlankValidator`](../../../vendor/symfony/validator/Constraints/NotBlankValidator.php).
That agrees with the native `required` attribute the template emits, which
browsers read as "this box must be ticked", so the client and the server say the
same thing. [`accepted()`](can-be-accepted.md) is the explicit spelling of the
same rule and is what to reach for when the intent matters to a reader.

The override is what carries that meaning, and it has been explicit only since
the shared constraint stopped supplying it: `required()` builds
[`RequiredValue`](can-be-validated.md#required-carries-laravels-blank-set-not-symfonys),
which follows Laravel in reading `false` as present. Until then the checkbox
inherited "must be ticked" from a default that meant it by accident — the same
accident that made every *other* field over a non-nullable `bool` column
unsubmittable while off.

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

### The checkbox's extra input attributes win where Filament's lose

`extraInputAttributes()` outranks the attributes the field computes on **every**
field that composes
[the concern](has-extra-input-attributes.md#the-extras-beat-the-attributes-the-field-computes).
That is Filament's own order on five of the seven it composes the trait on —
`TextInput`, `Textarea`, `Select`, `Radio` and `CheckboxList` each start the
control's attributes *from* the extras bag and merge their own in as defaults —
but not on the two exceptions.
[`Checkbox`](../../../vendor/filament/forms/src/Components/Checkbox.php) is the
first: its `toEmbeddedHtml()` builds the declared attributes first and then
`->merge($this->getExtraInputAttributes())`. A Laravel bag's `merge()` takes its
**argument** as the defaults, so there the extras lose every key the field
already set: `extraInputAttributes(['disabled' => false])` cannot un-disable a
Filament checkbox, though it can un-disable a Filament text input.
[`ToggleButtons`](toggle-buttons.md#the-extra-input-attributes-win-where-filaments-lose-by-going-last)
is the second, by a different route — it never merges at all, printing its own
attributes as raw HTML and appending the extras bag after them, which a browser
resolves first-wins.

The port applies one precedence to all of them rather than reproducing either odd
one out. Nothing in Filament's checkbox reads as intent: the same chain demotes
`extraAttributes()` too, while the three fields that own a wrapper element start
that element's bag from `getExtraAttributeBag()` — so the split tracks which
`merge()` happens to come first in each embedded view, not a decision about who
should win. A hatch whose precedence flips with the field it is called on is the
shape a consumer cannot hold in their head. Reversible on a concrete report that
someone depends on the checkbox arm losing.

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
