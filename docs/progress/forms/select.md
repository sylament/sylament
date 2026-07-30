# Select (forms)

`Sylament\Forms\Components\Select` — the native `<select>` field, over a static
option map. It composes three single-use concerns
([`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php),
[`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php),
[`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php))
plus the
[`Contracts\CanDisableOptions`](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php)
interface, casts its state through schemas'
[`OptionStateCast`](../schemas/README.md), and appends a Symfony `Choice`
constraint over its enabled options so a tampered payload is rejected
server-side.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php)
  — the field
- [`Components/Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php),
  [`CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php),
  [`CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php)
  — the concerns
- [`Components/Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php)
  — the native/JS renderer switch (unported)

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Details](#details), [Semantics](#semantics) or [Divergences](#divergences), or
`—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<select>` rendering | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [diverges](#canbenative-is-not-ported) · [diverges](#the-default-placeholder-is-an-untranslated-literal) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | in progress | HasEnum | [diverges](#hasoptions-drops-the-enum-class-string-arm) |
| [`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php) | [`Concerns/CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | done | — | [diverges](#disableplaceholderselection-is-not-ported) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [diverges](#a-null-callback-merges-to-nothing) |
| [`Contracts\CanDisableOptions`](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Components/Contracts/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php) | done | — | [diverges](#the-contract-declares-the-read-surface-not-the-setter) |
| `boolean()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | — |
| `getOptionLabel()` | [`resolveOptionLabel()`](../../../vendor/filament/forms/src/Components/Select.php) | in progress | relationship layer | [detail](#getoptionlabel-omits-the-getoptionlabelusing-arm) |
| Option validation (`Choice`) | [`getInValidationRuleValues()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-choice-constraint-is-the-fields-only-server-side-guard) |
| `CanBeNative` | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | blocked | the JS select control | [diverges](#canbenative-is-not-ported) |
| `multiple()` / `OptionsArrayStateCast` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | todo | array state mapping in `getData()` | — |
| `searchable()` / `preload()` / `HasLoadingMessage` | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | todo | the JS select control | — |
| `relationship()` / create + edit option actions | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | todo | the Doctrine relation layer | — |
| `allowHtml()` | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | todo | — | — |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | blocked | `Repeater` | — |
| Affixes (`HasAffixes`, `wrapInputHtml`) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | blocked | HasAffixes | — |

Coverage: the concern surface — flattening, per-option disabling, merge/replace,
placeholder selectability and dynamic options — by
[`CanDisableOptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanDisableOptionsTest.php);
the rendered markup (including a disabled option in both the flat and the grouped
shape), the state round-trip, `boolean()`, `getOptionLabel()` over flat, grouped
and boolean options, the no-options `LogicException` and the `Choice` guard — the
tampered-value, disabled-option and int-keyed cases plus both stored forms of a
`boolean()` option, all run through the container's real validator — by
[`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php).

## Details

### `getOptionLabel()` omits the `getOptionLabelUsing` arm

The port resolves the label from the static option map only, walking both the
flat and the grouped shape. The match is an array-key lookup rather than a
comparison, which is what Filament's `$options[$state]` is: PHP canonicalizes a
numeric-string key to an int, so a state of `'1'` finds the `1` option however
the field's casts spell it. A `bool` state is normalized to `int` first — the
coercion PHP would apply to an array offset anyway, and the one `boolean()`
depends on to resolve `true` to its `1 => 'Yes'` option.

Filament's `getOptionLabelUsing` closure arm exists
for relationship-backed options, where the label is fetched rather than known, so
it lands with that layer. Filament's `$withDefault` parameter — falling back to
the raw state when no option matches — is also unported: the port returns `null`,
which is the honest answer for a value that is not an option.

## Semantics

### The `Choice` constraint is the field's only server-side guard

A native `<select>` gives no server-side guarantee at all: the submitted value is
an ordinary string a client can set to anything, and `disabled` on an `<option>`
is a browser-side courtesy.
[`getValidationConstraints()`](../../../packages/forms/src/Components/Select.php)
therefore appends a `Choice` over
[`getEnabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php)
whenever state is filled — that one constraint is what rejects both a tampered
value and a value whose option is disabled. Symfony's `Choice` is always strict
(`ChoiceValidator` throws on any other setting), so the comparison is identity,
which is why the choices are built in the field's *stored* form (see below).

The `LogicException` raised when a filled select has no `options()` exists so
that "validates nothing" can never be the silent default: a select whose options
come from somewhere the port cannot see must say so loudly rather than wave every
payload through.

### The `Choice` choices cover both forms the raw state can take

`getEnabledOptions()` returns PHP array keys, while validation runs against the
**raw** state a payload wrote —
[`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php)
reads the host property, not `getState()`. Two different values can legitimately
sit there, and a strict `Choice` has to admit both:

- **The cast form** — what `state()` and `Schema::fill()` write, each key run
  through the reversed state-cast chain. With `options([1 => 'One'])` the key is
  `int 1` while `OptionStateCast::set()` stores the string `'1'`; a `Choice` over
  the bare keys would reject the field's own filled value.
- **The submitted form** — the option's `value` attribute, which the template
  renders from the array key, so it is always `(string) $key`. A single
  `<select>` binds through `live_controller`'s `getValueFromElement()`, which
  writes `element.value` — a string — straight into the host's bag, and nothing
  re-normalizes it afterwards: `hydrateState()` is reached from `Schema::fill()`
  only, i.e. at mount.

`getEnabledOptionValues()` therefore emits the cast value and, when it differs,
the submitted string. For string options the two coincide and the list is the
plain key list; for `boolean()`, whose `BooleanStateCast(isStoredAsInt: true)`
stores `int 1`, the choices are `[1, '1', 0, '0']` — without the string arm a
`boolean()` select rejects its own value the moment it round-trips through the
browser. Nothing is loosened by the extra arm: `Choice` stays strict and a
tampered or disabled value is still absent from the list.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### `CanBeNative` is not ported

Filament's `native(false)` switches `Select` from a real `<select>` to its
JavaScript Choices.js control, and every searchable/multiple affordance hangs off
that renderer. The port ships the native renderer only, so `native()` has no
second branch to switch to: it could only lie (accept the call and render
natively anyway) or throw at runtime. A method that does not exist is the signal
PHPStan and the IDE can both see, so the row is **blocked** rather than skipped —
it lands with the JS control.

### `HasOptions` drops the enum class-string arm

Filament's `options()` calls `enum()` when handed an enum class-string, and
`getOptions()` expands the cases through `Support\Contracts\HasLabel`. `HasEnum`,
`EnumStateCast` and that contract are all unported, so the setter union narrows
from `array|Arrayable|string|Closure|null` to `array|Closure|null` — the
`Arrayable` arm goes the same way, being a Laravel contract with no port.
Reverses with the `HasEnum` port.

### The default placeholder is an untranslated literal

`setUp()` sets the placeholder to the literal `'Select an option'`, and
`boolean()` to `'-'`. Filament reads
`__('filament-forms::components.select.placeholder')`; the translation surface is
unported, matching
[`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation).

### A null callback merges to nothing

`disableOptionWhen(null)` clears the callback list, in both Filament and the port
— `Arr::wrap(null)` and [`wrap(null)`](../support/helpers.md) are both `[]`.
Filament's *merge* arm appends the argument unconditionally, so
`disableOptionWhen(null, merge: true)` pushes a `null` that
`hasDisabledOptions()` then counts as disabling (`null !== false`), even though
`evaluate(null)` disables no option — the field claims to have disabled options
and filters none. The port skips a null on merge instead, which is also what
keeps the `array<bool|Closure>` property type honest at level max.

### The contract declares the read surface, not the setter

Filament's `Contracts\CanDisableOptions` declares exactly one member,
`disableOptionWhen(bool | Closure): static` — the *write* side. Its only consumer
is
[`CanDisableOptionsWhenSelectedInSiblingRepeaterItems`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php),
which intersects `Component & CanDisableOptions` so a closure can call the setter
on whatever field it is handed. That concern is unported — it needs the
`Repeater` — so the setter has nothing here to type-check it.

The port's
[interface](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php)
declares the *read* side instead: `isOptionDisabled()`, which
[the template](../../../packages/forms/templates/partials/Select/index.html.twig)
calls per option to render `disabled`, and `getEnabledOptions()`, which
[`getEnabledOptionValues()`](../../../packages/forms/src/Components/Select.php)
walks to build the `Choice` choices. Neither is reached through an `instanceof`
yet — `Select` is the only implementor — so the interface names what a second
implementor would have to honour rather than what Filament's absent consumer
needed. `disableOptionWhen()` still ships on the
[trait](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php),
so the public API is unchanged, and widening the interface once the repeater arm
lands is additive. Same narrowing and same reasoning as
[`CanBeLengthConstrained`](can-be-length-constrained.md#the-contract-declares-only-the-constraint-builder).

### `disablePlaceholderSelection()` is not ported

Filament keeps it as a deprecated alias for `selectablePlaceholder()`. Porting a
method that is already deprecated upstream would import a migration the toolkit
never had.
