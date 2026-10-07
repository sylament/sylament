---
description: The concern that greys out an option another item of the parent repeater already holds, the read half of `distinct()`.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php
owns:
  - packages/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php
---
# CanDisableOptionsWhenSelectedInSiblingRepeaterItems (forms)

`Sylament\Forms\Components\Concerns\CanDisableOptionsWhenSelectedInSiblingRepeaterItems` is the read half of [`distinct()`](can-be-validated.md#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule): its [`disableOptionWhen()`](can-disable-options.md) predicate greys out an option another item already holds, so the refusal never has to fire.
The four fields that implement [`Contracts\CanDisableOptions`](can-disable-options.md) compose it: [`Radio`](radio.md), [`Select`](select.md), [`CheckboxList`](checkbox-list.md) and [`ToggleButtons`](toggle-buttons.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`disableOptionsWhenSelectedInSiblingRepeaterItems()`](../../../packages/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | — |
| The unconditional `distinct()` + `live()` installs | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | — |
| The predicate: the whole item bag plucked, this item's own selection subtracted | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [semantics](#a-value-this-item-also-holds-stays-clickable) |
| Enum and boolean sibling values compared by their option vocabulary | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [semantics](#both-sides-are-normalised-and-then-compared-as-strings) |
| The sibling path arithmetic, through [`ResolvesRepeaterSiblingContext`](../../../packages/forms/src/Components/Concerns/ResolvesRepeaterSiblingContext.php) | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [diverges](field.md#the-five-sibling-facts-are-one-value-object-not-string-surgery) |

## Semantics

### A value this item also holds stays clickable

The predicate plucks the field out of **every** item of the parent repeater, including this one, and then subtracts this item's own selection from what it found, as Filament's `->diff(Arr::wrap($state))` does.
So a value two items share is *not* disabled here, which is what lets the user release it.
Only a value held exclusively by another item is greyed out.

### Both sides are normalised and then compared as strings

Every value on both sides runs through [`HasOptions::normalizeOptionComparisonValue()`](has-options.md), the same mapping the option templates compare with, so a `BackedEnum` becomes its backing value and a `bool` becomes `1`/`0`; both are then cast to `string` before a strict comparison.

The cast is not cosmetic.
An option's value is a **PHP array key**, so an option map written `[1 => 'One']` has the *int* `1` as its value, while the same value coming back off the wire in a sibling item is the *string* `'1'`.
A strict comparison after normalisation would leave `1 !== '1'` and silently disable nothing at all on every int-keyed option map.
Filament reaches the same place through `Collection::contains()`, which compares loosely when given a single value, and the option templates compare loosely too.
