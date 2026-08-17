# CanDisableOptions (forms)

`Sylament\Forms\Components\Concerns\CanDisableOptions` and its
[`Contracts\CanDisableOptions`](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php)
interface — per-option disabling over the map
[`HasOptions`](has-options.md) supplies: `disableOptionWhen()` registers the
predicates, `isOptionDisabled()` answers per option, and `getEnabledOptions()`
flattens one optgroup level and filters. It carries one member Filament has no
counterpart for, `getEnabledOptionValues()`, which builds the choice list a
field's `Choice` constraint validates against.

Promoted out of [`select.md`](select.md) ahead of the option fields that share
it, per [one row per file](../README.md#one-row-per-file-one-file-per-thing).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php)
  — the trait
- [`Components/Contracts/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php)
  — the interface

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics) or [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `disableOptionWhen()` / `hasDisabledOptions()` / `hasDynamicDisabledOptions()` | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [diverges](#a-null-callback-merges-to-nothing) |
| `getEnabledOptions()` / `isOptionDisabled()` | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [semantics](has-options.md#an-enum-label-stays-markup-rather-than-being-stringified) |
| [`Contracts\CanDisableOptions`](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Components/Contracts/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Contracts/CanDisableOptions.php) | done | — | [diverges](#the-contract-declares-the-read-surface-not-the-setter) |
| `getEnabledOptionValues()` — no Filament source | — | done | — | [semantics](#the-choice-choice-list-lives-here-not-on-the-field) |

Coverage: the concern surface — flattening, per-option disabling, the label
injected into the callback, merge/replace, and a null callback in both arms — by
[`CanDisableOptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanDisableOptionsTest.php);
`getEnabledOptionValues()` through the constraint it feeds — the tampered-value,
disabled-option and int-keyed cases plus both stored forms of a `boolean()`
option, all run through the container's real validator — by
[`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php)
and its
[multi-value twin](../../../packages/forms/tests/Integration/Components/SelectMultipleTest.php),
with the tampered-value, disabled-option and both-stored-forms cases re-run over
an option **set** — plus the per-option `disabled` attribute its template renders
— by
[`RadioTest`](../../../packages/forms/tests/Integration/Components/RadioTest.php),
and the disabled option re-run once more against a control that carries the state
on a hidden input and the disabled styling on the label beside it, by
[`ToggleButtonsTest`](../../../packages/forms/tests/Integration/Components/ToggleButtonsTest.php).

## Semantics

### The `Choice` choice list lives here, not on the field

`getEnabledOptionValues()` shipped as a protected method on `Select`, since
`Select` was the only field building a `Choice`. Every option field builds one
the same way — over the enabled options, in both the cast-stored form and the
raw submitted string — so the method moved onto this trait unchanged rather than
being copied per field. It reads only `getEnabledOptions()` and the schemas-level
`getStateCasts()`, both of which any field composing this trait already has, so
the move needed no seam.

Why the list carries both forms, and why a multi-value cast's list is spread
rather than pushed, is
[`select.md`](select.md#the-choice-choices-cover-both-forms-the-raw-state-can-take)
— the reasoning belongs beside the constraint builder that consumes it, and the
`Select` suite is what pins the move.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

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
[`getEnabledOptionValues()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php)
walks to build the `Choice` choices. Neither is reached through an `instanceof`
yet — [`Select`](select.md), [`Radio`](radio.md) and
[`ToggleButtons`](toggle-buttons.md) are the implementors, and all three call the
two methods directly — so the interface names what an implementor has to honour
rather than what Filament's absent consumer needed.
`disableOptionWhen()` still ships on the
[trait](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php),
so the public API is unchanged, and widening the interface once the repeater arm
lands is additive. Same narrowing and same reasoning as
[`CanBeLengthConstrained`](can-be-length-constrained.md#the-contract-declares-only-the-constraint-builder).
