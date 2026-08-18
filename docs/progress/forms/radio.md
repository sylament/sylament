# Radio (forms)

`Sylament\Forms\Components\Radio` — the single-choice option field, rendering one
native `<input type="radio">` per option inside an
`aria-labelledby`-pointed `role="radiogroup"` container. Extends
[`Field`](../../../packages/forms/src/Components/Field.php), casts state through
schemas'
[`OptionStateCast`](../../../packages/schemas/src/State/Casts/OptionStateCast.php),
and composes the five option-field concerns
([`HasOptions`](has-options.md), [`CanDisableOptions`](can-disable-options.md),
[`HasDescriptions`](has-descriptions.md),
[`HasGridDirection`](has-grid-direction.md),
[`HasExtraInputAttributes`](has-extra-input-attributes.md)). It is the first
field to render an option **set** rather than one control, so it is also the
first consumer of support's
[`grid()`](../../../packages/support/src/Twig/Extension/GridExtension.php) helper
from a field template and the first to ask the wrapper for a
[heading label](field.md#labeltag-is-a-boolean-labelasheading) instead of a
`<label for>`. It ships no JavaScript: the controls are real radios bound with
`data-model`, and `ux-live-component` reads and repaints them natively.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php)
  — the field class and its embedded view
- [`forms/resources/css/components/radio.css`](../../../vendor/filament/forms/resources/css/components/radio.css)
  — the option-set chrome
- [`support/resources/css/components/input/radio.css`](../../../vendor/filament/support/resources/css/components/input/radio.css)
  — the control chrome, which vendor files a package down

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details) or a permanent note in
[Semantics](#semantics) / [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering — the radiogroup, the implicit option labels, the description line | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [diverges](#the-checked-attribute-is-rendered-server-side) · [diverges](#every-radio-carries-the-same-data-model) · [diverges](#the-group-name-is-the-field-id-not-the-field-name) · [diverges](#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) · [diverges](#the-control-class-is-sy-fo-radio-input-one-tier-up-from-vendors) |
| `boolean()` — the `1`/`0` option pair and the int-backed cast | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [semantics](#the-cast-chain-collapses-to-whatever-boolean-installs) · [diverges](#the-boolean-labels-are-untranslated-literals) |
| `inline()` / `isInline()` | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [semantics](#inline-is-radios-own-property-not-the-canbeinline-trait) |
| `getDefaultState()` — a `bool` default coerced to `1`/`0` | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | — |
| `getDefaultStateCasts()` — `OptionStateCast(isNullable: true)` unless custom casts or an enum | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [semantics](#the-cast-chain-collapses-to-whatever-boolean-installs) |
| `hasNullableBooleanState()` | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [semantics](field.md#hasnullablebooleanstate-has-exactly-one-consumer) |
| `getInValidationRuleValues()` → `buildValidationConstraints()`, a `Choice` over the enabled options | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [semantics](select.md#the-choice-constraint-is-the-fields-only-server-side-guard) · [semantics](select.md#the-choice-choices-cover-both-forms-the-raw-state-can-take) |
| `required()` — the standard `NotBlank` | [`Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#required-means-presence-and-needs-no-override) |
| The grid, and its `Column` default | [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php) | done | — | [semantics](has-grid-direction.md#the-column-default-belongs-to-the-field-not-to-this-getter) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`HasDescriptions`](../../../packages/forms/src/Components/Concerns/HasDescriptions.php) | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [detail](has-descriptions.md) |
| [`HasGridDirection`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php) | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | [detail](has-grid-direction.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) · [diverges](#extraattributes-stays-on-the-field-wrapper) |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | blocked | `distinct()` (M3 · 8) · repeater-item context (M3 · 6) | [detail](#the-two-repeater-sibling-concerns-have-nothing-to-build-on) |
| `CanDisableOptionsWhenSelectedInSiblingRepeaterItems` | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | blocked | repeater-item context (M3 · 6) | [detail](#the-two-repeater-sibling-concerns-have-nothing-to-build-on) |

Coverage: one input per option with the shared group name and per-option ids and
values; the `data-model` every input shares; `checked` on the matching option and
on no other, including a state that is unset against a `0`-keyed option; the enum
arms (labels from `HasLabel`, an enum-valued state matching, descriptions from
`HasDescription` with the null-describing case emitting nothing); explicit
descriptions; per-option and whole-field `disabled`; `autofocus` on the first
option only; the default column grid, `columns(2)`, `gridDirection('row')` and
`inline()` dropping the grid entirely; `aria-labelledby` asserted at both ends in
the visible and hidden-label branches; `aria-invalid`; extra input attributes
reaching every input and outranking the field's own; `extraAttributes()` landing
on the wrapper rather than the group; the `OptionStateCast` round-trip; the
`boolean()` collapse to a single `BooleanStateCast`, its rendered `1`/`0` options
and its coerced `bool` default; the `Choice` constraint including the disabled
filter, both stored forms of a `boolean()` option, the enum-only arm, the blank
short-circuit, the
[filled gate](select.md#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out)
rejecting a value the enum cast nulls out (driven end to end through a host, so
the tampered string is the field's *own* raw state) and the no-options
`LogicException`; and a required radio failing
with nothing selected, passing once selected, and passing on the `0`-keyed false
option in both stored forms — by
[`RadioTest`](../../../packages/forms/tests/Integration/Components/RadioTest.php).
The binding mechanism itself needs a live host, so it is driven through a real
round-trip by
[`LiveRadioTest`](../../../packages/forms/tests/Integration/LiveRadioTest.php)
against the
[`RadioForm`](../../../packages/forms/tests/Fixtures/Twig/Components/RadioForm.php)
fixture.

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

### The two repeater-sibling concerns have nothing to build on

Neither is partly ported; each is **blocked** rather than skipped, because both
are wanted for parity.

`CanFixIndistinctState` composes `distinct()`, `live()` and an
`afterStateUpdated()` hook that walks up to the parent `Repeater` and clears the
same option from every sibling item. `distinct()` is
[deferred to M3 · 8](can-be-validated.md) with the rest of the validation
families, and the parent-repeater item lookup the hook needs is M3 · 6's — the
same reading [`Checkbox`](checkbox.md) and
[`Toggle`](toggle.md#fixindistinctstate-awaits-the-repeater) give it, one
prerequisite further along now that `Repeater` itself exists.

`CanDisableOptionsWhenSelectedInSiblingRepeaterItems` is the same lookup wearing
the other hat: it registers a `disableOptionWhen()` predicate that asks the
sibling items which values they already hold. Its write-side dependency is the
one Filament's
[`Contracts\CanDisableOptions`](can-disable-options.md#the-contract-declares-the-read-surface-not-the-setter)
exists for, which is why that interface's port declares the read surface instead.

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### `inline()` is Radio's own property, not the `CanBeInline` trait

Radio declares `protected bool|Closure $isInline = false;` with its own
`inline()` / `isInline()` pair, and composes **no** trait for it. That is
vendor-exact — Filament's `Radio` hand-rolls the same two methods rather than
using `Concerns\CanBeInline` — and it matters here because the port *does* ship a
[`CanBeInline`](can-be-inline.md) trait with the same two method names, which
`Checkbox` and `Toggle` compose.

The two are not interchangeable and share no code. The trait defaults to
**`true`** (a checkbox sits beside its label unless told otherwise) and its
getter carries a deferred
[`hasInlineLabel()` guard](can-be-inline.md#isinline-drops-the-hasinlinelabel-guard);
Radio's property defaults to **`false`** (options stack in a grid unless told
otherwise) and has no guard to defer. The word also means different things on
each: on a boolean field it places the control relative to the field label, on a
radio it swaps the option grid for a wrapping flex row. Composing the trait here
would silently flip the default and hand the field a guard written for the other
question.

### A grouped option map is not a radio shape, and fails loudly

[`HasOptions`](has-options.md) admits one optgroup level, because `Select`
renders it. A radio has no optgroup, so the template iterates the map flat and a
grouped array reaches `isOptionDisabled(value, label)` as its `label` — a
`TypeError` naming that method, wrapped by Twig with the template and line.
Filament's radio is in the same position, `e($label)` being no happier with an
array. The failure is immediate and names the cause, so nothing guards it; it is
recorded because the *setter* accepts the shape the renderer will not.

### The cast chain collapses to whatever `boolean()` installs

`getDefaultStateCasts()` answers `[OptionStateCast(isNullable: true)]` only while
the field has neither custom casts nor an
[enum](has-enum.md); otherwise it defers to `Field`'s, which contributes the enum
cast and nothing else. `boolean()` calls `stateCast()`, so from that point
`hasCustomStateCasts()` is true and the whole chain is the one
`BooleanStateCast(isStoredAsInt: true)` it installed — the option cast is not
stacked in front of it. Same shape as
[`Select::boolean()`](select.md), and the reason a `boolean()` radio round-trips
`true` to the stored `int 1` rather than to the string `'1'` an option cast would
write.

### required() means presence, and needs no override

`required()` builds the default `NotBlank`
([`CanBeValidated`](can-be-validated.md)) and the field overrides
`getRequiredConstraint()` nowhere, so a required radio with nothing selected
fails and any selected option passes. Nothing in the option vocabulary needs the
carve-outs the two boolean fields argued over: `Checkbox` keeps `NotBlank`
because [it means "must be ticked"](checkbox.md#required-on-a-checkbox-means-must-be-checked)
against the native `required` attribute it emits, and `Toggle` swaps to `NotNull`
because [it means presence, not truth](toggle.md#required-means-presence-not-truth).

A radio sits outside that argument because its resting state is `null`, not
`false` — even in the `boolean()` arm, whose cast is nullable — so "blank" and
"nothing selected" are already the same value, and the template emits no
`required` attribute for a browser to disagree with.

The case worth checking rather than assuming is the `boolean()` arm's **No**
option, whose key is `0`.
[`NotBlankValidator`](../../../vendor/symfony/validator/Constraints/NotBlankValidator.php)
reads `false === $value || (! $value && '0' != $value)`, so it rejects `null`,
`''` and `false` but **accepts** `int 0` and the string `'0'` a browser submits —
the `'0' != $value` clause exists for exactly this. A required `boolean()` radio
therefore accepts either answer and rejects only *no answer*, which is what
`required()` should mean and what the constraint would not have given a field
whose false value were `false` itself. That is the whole reason
[`Toggle`](toggle.md#required-means-presence-not-truth) needed an override and
this field does not; `RadioTest` pins the **No** case so a future change to the
cast cannot quietly turn `required()` into
[`accepted()`](can-be-accepted.md).

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### The `checked` attribute is rendered server-side

Filament emits only `wire:model` on each input and lets Livewire set the checked
property client-side once it boots — no `checked` attribute is ever written. The
port renders `checked` from
[`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template),
so a server-rendered edit page shows the stored selection before any JavaScript
runs. Same addition [`Checkbox`](checkbox.md#the-checked-attribute-is-rendered-server-side)
and [`Select`](select.md#the-template-renders-the-selected-state-itself) make,
for the same reason.

The comparison is `state is not null and value == state` — deliberately loose,
because an option key is an `int` where the state may hold the `'1'` a browser
submitted, and deliberately null-guarded, because PHP's `0 == null` is `true` and
a `boolean()` radio would otherwise render its **No** option checked for a field
nobody has answered. The strict [`Choice`](select.md#the-choice-constraint-is-the-fields-only-server-side-guard)
is what admits or rejects a value; `checked` is presentation.

The client agrees with the server rather than fighting it. `live_controller`'s
`SetValueOntoModelFieldsPlugin` walks every `[data-model]` element on connect and
after each render and calls `setValueOnElement()`, whose radio arm is
`element.checked = element.value == value` — the same comparison, over the same
model value. So the two halves converge on one option and a re-render moves the
attribute rather than adding a second one.

### Every radio carries the same `data-model`

One `data-model` per input, all naming the field's one state path — the
translation of Filament's one `wire:model` per input. It reads like a
double-binding and is not, because a radio group only ever fires events from the
input the user just checked: `live_controller`'s `getValueFromElement()` has no
radio branch, so it falls through to `element.value` and the shared path receives
the clicked option's `value` attribute. The unchecked siblings fire nothing.

The alternative shapes — one binding on the group, or a `change`-only listener —
were considered and rejected: the group element is a `<div>`, which the
controller reads through `element.value` / `data-value` and would find empty, and
a bespoke listener would give up the repaint the plugin above performs for free.
Pinned by a real round-trip in
[`LiveRadioTest`](../../../packages/forms/tests/Integration/LiveRadioTest.php),
which drives a selection through the live host and asserts both the prop and
which input the re-render checked.

### The group name is the field id, not the field name

The `name` attribute every radio shares is
[`getId()`](../../../packages/schemas/src/Components/Concerns/HasId.php), where
the port's other four controls emit `getName()`. That is vendor-exact and it is
the point: `name` is what groups radios in the browser, so it has to be unique
per *rendered group*, and `getName()` is the bare field name — identical across
every item of a repeater, which would fuse their radio groups into one. The id is
the absolute key, so it stays distinct.

### The boolean labels are untranslated literals

`boolean()` sets the literal `'Yes'` and `'No'` where Filament reads
`__('filament-forms::components.radio.boolean.true')` and `…false`. The
translation surface is unported, matching
[`Select`'s placeholder](select.md#the-default-placeholder-is-an-untranslated-literal)
and [`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation). The
two keys are recorded here so the strings can be found when a translation layer
lands; Filament's own English values are the same two words.

### The option inputs carry `aria-invalid` instead of a `fi-valid` / `fi-invalid` class pair

Filament puts `fi-valid` or `fi-invalid` on every radio input and keys the danger
ring off the latter. The port emits neither class and writes `aria-invalid` on
each input instead, with
[`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css)
keying its danger arm off `[aria-invalid='true']`.

This is not a Radio decision — it is the translation already made for
[`checkbox.css`](../../../packages/forms/assets/src/styles/components/checkbox.css)
and [`select.css`](../../../packages/forms/assets/src/styles/components/select.css),
whose vendor sources key off `.fi-invalid` in exactly the same way, and it holds
for the same two reasons: the attribute is the part a screen reader reads, and one
emission serves both jobs where the vendor pair serves only styling. The `valid`
half has nothing to key at all — the base rule *is* the valid state — so it is
dropped rather than emitted as a class no stylesheet mentions. Reversible by
adding the pair back if a consumer's CSS ever needs a class hook the attribute
selector cannot give it; nothing in the toolkit styles either name today.

### The control class is `sy-fo-radio-input`, one tier up from vendor's

Vendor splits Radio's chrome across two packages: `fi-fo-radio*` for the option
set in `forms/resources/css/components/radio.css`, and a package-code-less
`fi-radio-input` for the control itself in
`support/resources/css/components/input/radio.css`. The port names the control
`.sy-fo-radio-input` and files its rules in forms'
[`radio.css`](../../../packages/forms/assets/src/styles/components/radio.css)
beside the option set.

That is the rule
[`styles.md`](styles.md#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes)
already records for `.sy-fo-checkbox`, `.sy-fo-input`, `.sy-fo-select` and
`.sy-fo-toggle`, applied to the fifth control of the same kind: forms is the only
package rendering it, and filing a rule beside the one template that emits it
beats filing it beside the vendor that happened to author it. It reverses the
same way — the moment a second package renders a radio, the rule moves down to
support and loses the code. Worth noting because the research trail and the plan
for this epic both wrote `sy-radio-input`, the straight `fi` → `sy` rename, which
would have left this field emitting a support-tier name while
[`CheckboxList`](README.md#components) reuses the forms-coded `.sy-fo-checkbox`
for the identical category one task later.

### `extraAttributes()` stays on the field wrapper

Vendor's Radio starts the radiogroup's attribute bag from `getExtraAttributeBag()`,
so `extraAttributes()` lands on the group. The port's
[`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig)
already spreads it on the wrapper `<div>` for every field, and the radiogroup does
not repeat it — a second emission would duplicate whatever the consumer set,
including an `id`.

This is the existing port-wide placement, not a Radio-specific choice: the reasons
are in
[`field.md`](field.md#extra-attribute-precedence-is-fixed-by-source-order), and
[`Repeater`](repeater.md) — the other field with a real inner element — resolves
it the same way. `extraInputAttributes()` is the hatch that reaches the controls,
and on a radio it reaches **every** one of them.
