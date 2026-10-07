---
description: The `disableOptionWhen()` concern and its contract, which filter an option field's map and build the choices its `Choice` constraint validates against.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php
  - vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php
owns:
  - packages/forms/src/Components/Concerns/CanDisableOptions.php
  - packages/forms/src/Components/Contracts/CanDisableOptions.php
---
# CanDisableOptions (forms)

`Sylament\Forms\Components\Concerns\CanDisableOptions` and its [`Contracts\CanDisableOptions`](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) interface give per-option disabling over the map [`HasOptions`](has-options.md) supplies: `disableOptionWhen()` registers the predicates, `isOptionDisabled()` answers per option, and `getEnabledOptions()` flattens one optgroup level and filters.
It carries one member Filament has no counterpart for, `getEnabledOptionValues()`, which builds the choice list a field's `Choice` constraint validates against.
[`Select`](select.md), [`Radio`](radio.md), [`ToggleButtons`](toggle-buttons.md) and [`CheckboxList`](checkbox-list.md) compose the trait and implement the contract; the tables `SelectColumn` composes the trait alone.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`disableOptionWhen()` / `hasDisabledOptions()` / `hasDynamicDisabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [diverges](#a-null-callback-merges-to-nothing) |
| [`getEnabledOptions()` / `isOptionDisabled()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [semantics](has-options.md#an-enum-label-stays-markup-rather-than-being-stringified) |
| [`Contracts\CanDisableOptions`](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Components/Contracts/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php) | done | — | [diverges](#the-contract-declares-the-read-surface-not-the-setter) |
| [`getEnabledOptionValues()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php), no Filament source | — | done | — | [semantics](#the-choice-choice-list-lives-here-not-on-the-field) · [semantics](#the-choice-choices-cover-both-forms-the-raw-state-can-take) |

## Semantics

### The `Choice` choice list lives here, not on the field

Every option field builds its `Choice` the same way, over the enabled options, in both the cast-stored form and the raw submitted string, so `getEnabledOptionValues()` lives on this trait rather than being copied per field.
It reads only `getEnabledOptions()` and the schemas-level `getStateCasts()`, both of which any component composing this trait already has.

### The `Choice` choices cover both forms the raw state can take

`getEnabledOptions()` returns PHP array keys, while validation runs against the **raw** state a payload wrote: [`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) reads the host property, not `getState()`.
Two different values can legitimately sit there, and a strict `Choice` has to admit both:

- **The cast form** is what `state()` and `Schema::fill()` write, each key run through the reversed state-cast chain.
  With `options([1 => 'One'])` the key is `int 1` while `OptionStateCast::set()` stores the string `'1'`; a `Choice` over the bare keys would reject the field's own filled value.
- **The submitted form** is the option's `value` attribute, which [`Select`](select.md)'s template renders from the array key, so it is always `(string) $key`.
  A single `<select>` binds through `live_controller`'s `getValueFromElement()`, which writes `element.value`, a string, straight into the host's bag.
  Nothing re-normalizes it before validation, because `hydrateState()` runs only when a schema is filled or reloaded from its record, never on a client update.

[`getEnabledOptionValues()`](#the-choice-choice-list-lives-here-not-on-the-field) therefore emits the cast value and, when it differs, the submitted string.
For string options the two coincide and the list is the plain key list; for `boolean()`, whose `BooleanStateCast(isStoredAsInt: true)` stores `int 1`, the choices are `[1, '1', 0, '0']`, and without the string arm a `boolean()` select rejects its own value the moment it round-trips through the browser.
An enum'd field is the coinciding case again, since [`EnumStateCast::set()`](../schemas/enum-state-cast.md) stores `(string) $case->value`, which is the option key the template rendered.
Nothing is loosened by the extra arm: `Choice` stays strict and a tampered or disabled value is still absent from the list.
A multi-value cast turns one key into a one-element list, which the method spreads rather than pushes, because `Choice(multiple: true)` compares elements and a value wrapped in an array would match nothing.

## Divergences

### A null callback merges to nothing

`disableOptionWhen(null)` clears the callback list, in both Filament and the port: `Arr::wrap(null)` and [`wrap(null)`](../support/helpers.md) are both `[]`.
Filament's *merge* arm appends the argument unconditionally, so `disableOptionWhen(null, merge: true)` pushes a `null` that `hasDisabledOptions()` then counts as disabling (`null !== false`), even though `evaluate(null)` disables no option: the field claims to have disabled options and filters none.
The port skips a null on merge instead, which is also what keeps the `array<bool|Closure>` property type honest at level max.

### The contract declares the read surface, not the setter

Filament's `Contracts\CanDisableOptions` declares exactly one member, `disableOptionWhen(bool | Closure): static`, the *write* side.
Its only consumer is [`CanDisableOptionsWhenSelectedInSiblingRepeaterItems`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php), which intersects `Component & CanDisableOptions` so a closure can call the setter on whatever field it is handed.
The [port of that concern](can-disable-options-when-selected-in-sibling-repeater-items.md) calls no setter on the component it is handed: its predicate reads `resolveRepeaterSiblingContext()` and [`normalizeOptionComparisonValue()`](has-options.md), both public, so the intersection hint Filament needs the write side for has no counterpart here.

The port's [interface](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) declares the *read* side instead: `isOptionDisabled()`, which each implementor's template calls per option to render `disabled`, and `getEnabledOptions()`, which [`getEnabledOptionValues()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) walks to build the `Choice` choices.
Neither is reached through an `instanceof`: the implementors call the two methods directly, so the interface names what an implementor has to honour rather than what Filament's consumer needed.
`disableOptionWhen()` ships on the [trait](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php), so the public API is unchanged, and widening the interface is additive.
Same narrowing and same reasoning as [`CanBeLengthConstrained`](can-be-length-constrained.md#the-contract-declares-only-the-constraint-builder).
