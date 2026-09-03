# CanDisableOptionsWhenSelectedInSiblingRepeaterItems (forms)

`Sylament\Forms\Components\Concerns\CanDisableOptionsWhenSelectedInSiblingRepeaterItems`
— the read half of
[`distinct()`](can-be-validated.md#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule).
`disableOptionsWhenSelectedInSiblingRepeaterItems()` installs `distinct()`,
`live()` and a [`disableOptionWhen()`](can-disable-options.md) predicate that
greys out an option another item of the parent repeater already holds, so the
refusal never has to fire. Composed by the four fields that implement
[`Contracts\CanDisableOptions`](can-disable-options.md): [`Radio`](radio.md),
[`Select`](select.md), [`CheckboxList`](checkbox-list.md) and
[`ToggleButtons`](toggle-buttons.md).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php)
  — the trait

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `disableOptionsWhenSelectedInSiblingRepeaterItems()` | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | — |
| The unconditional `distinct()` + `live()` installs | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | — |
| The predicate — the whole item bag plucked, this item's own selection subtracted | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [semantics](#a-value-this-item-also-holds-stays-clickable) |
| Enum and boolean sibling values compared by their option vocabulary | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [semantics](#both-sides-are-normalised-and-then-compared-as-strings) |
| The sibling path arithmetic | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [diverges](can-fix-indistinct-state.md#the-five-sibling-facts-are-one-value-object-not-string-surgery) |

Coverage: the `distinct()` and `live()` installs, the predicate's presence and
that all four fields compose it, by
[`CanDisableOptionsWhenSelectedInSiblingRepeaterItemsTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItemsTest.php);
the predicate itself — a sibling's value disabled and the others not, a value
this item also holds left enabled, blank siblings disabling nothing, an enum
sibling matched by its backing value, a `CheckboxList`'s array-valued state, an
int-keyed option map against a sibling holding the submitted string, and a field
outside any repeater — over the repeater-item rig by
[its Integration namesake](../../../packages/forms/tests/Integration/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItemsTest.php).

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### A value this item also holds stays clickable

The predicate plucks the field out of **every** item of the parent repeater,
including this one, and then subtracts this item's own selection from what it
found — Filament's `->diff(Arr::wrap($state))`. So a value two items share is
*not* disabled here, which is what lets the user release it. Only a value held
exclusively by another item is greyed out.

### Both sides are normalised and then compared as strings

Every value on both sides runs through
[`HasOptions::normalizeOptionComparisonValue()`](has-options.md) — the same
mapping the option templates compare with, so a `BackedEnum` becomes its backing
value and a `bool` becomes `1`/`0` — and both are then cast to `string` before a
strict comparison.

The cast is not cosmetic. An option's value is a **PHP array key**, so an
option map written `[1 => 'One']` has the *int* `1` as its value, while the same
value coming back off the wire in a sibling item is the *string* `'1'`. A strict
comparison after normalisation would leave `1 !== '1'` and silently disable
nothing at all on every int-keyed option map. Filament reaches the same place
through `Collection::contains()`, which compares loosely by default, and the
option templates already compare loosely for exactly this reason. An int-keyed
map with a string sibling value is a test of its own, precisely because it is
the case that separates the two.

That promotion is why `normalizeOptionComparisonValue()` is public rather than
protected: the predicate is a `static` closure reading a sibling's raw value
from outside the field's own scope.
