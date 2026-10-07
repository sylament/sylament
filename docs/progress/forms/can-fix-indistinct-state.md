---
description: The repair half of `distinct()`. (draft)
ports:
  - vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php
owns:
  - packages/forms/src/Components/Concerns/CanFixIndistinctState.php
---
# CanFixIndistinctState (forms)

`Sylament\Forms\Components\Concerns\CanFixIndistinctState` — the repair half of [`distinct()`](can-be-validated.md#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule).
`fixIndistinctState()` installs `distinct($condition)`, `live(condition: $condition)` and an `afterStateUpdated()` hook that takes the value the user just picked away from every sibling repeater item holding it, so two items never claim the same one.
Composed by the six option-bearing fields: [`Checkbox`](checkbox.md), [`Toggle`](toggle.md), [`Radio`](radio.md), [`Select`](select.md), [`CheckboxList`](checkbox-list.md) and [`ToggleButtons`](toggle-buttons.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `fixIndistinctState(bool\|Closure $condition = true)` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | — |
| The `distinct()` + `live(condition:)` installs | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | — |
| The array arm — the picked values subtracted from a sibling selection, reindexed | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [semantics](#the-array-arm-cascades-because-our-hooks-fire-for-every-changed-path), [diverges](#the-comparison-reads-the-raw-state-not-the-cast-one) |
| The scalar / bool arm — a matching sibling becomes `false` if it was `true`, else `null` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [semantics](#the-false-skip-comes-before-the-blank-test-and-nothing-can-observe-it), [diverges](#the-comparison-reads-the-raw-state-not-the-cast-one) |
| The sibling path arithmetic | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [diverges](#the-five-sibling-facts-are-one-value-object-not-string-surgery) |

Coverage: the installs and the condition arm (literal and closure), and that all six fields compose it, by [`CanFixIndistinctStateTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanFixIndistinctStateTest.php).
The hook itself only fires from schemas' `#[PostHydrate]` walk, so it is driven through a real round trip by [`LiveIndistinctStateTest`](../../../packages/forms/tests/Integration/LiveIndistinctStateTest.php) against the [`IndistinctRepeaterForm`](../../../packages/forms/tests/Fixtures/Twig/Components/IndistinctRepeaterForm.php) fixture — one field per arm: the scalar clear and the untouched different-valued sibling, the ticked sibling flipping to `false` and the two unticked ones left alone, the array subtraction reindexed from zero, the three-item cascade pinned in both directions, an enum-backed field repaired by its backing value, a cleared value leaving its former twin alone, a `false` condition and a field outside any repeater.
One case renders: the corrected sibling's `<select>` comes back with no `selected` option and its checkbox with no `checked` attribute, which is what the user actually sees.
Item keys are read back off the mounted bag, never written into a test — a mount re-keys every item by uuid.

## Semantics

### The array arm cascades because our hooks fire for every changed path

Filament runs the hook only for the path Livewire reported as updated; ours runs for **every** component whose raw state differs from the pre-dehydrate snapshot (`callAfterSchemaStateUpdated()` in schemas' `HasSchemas`).
So a repair that edits a sibling makes that sibling's *own* hook run in the same pass, and it may edit a third item in turn.
The cascade is monotone — values only ever leave a selection — and terminates, so no re-entrancy guard is added.

It is **iteration-order dependent**, and that is the part to remember: the walk considers each component once, in flat-component order, so a repair that dirties an item the walk has already passed does not re-fire it.
Picking in the *first* item cascades forward through the third; picking in the *third* clears the first and stops there.
Both directions are pinned as tests, so the behaviour is a recorded fact rather than an accident.
The scalar and bool arms cannot cascade at all: a sibling written to `null` is blank and returns at the guard, and one written to `false` is skipped by the filter below.

### The false skip comes before the blank test, and nothing can observe it

Vendor filters a sibling in three steps, in this order: skip `=== false`, skip `blank()`, keep `=== $state`.
The order is load-bearing upstream because `blank(false)` is `false` here too, so without the first step an unticked sibling would reach the equality test against an unticked subject and be written over.

Our port keeps the order, and no test separates the two: the only fields whose state can be `false` are `Checkbox` and `Toggle`, and their non-nullable `BooleanStateCast` normalises a written `null` straight back to `false`, so both orderings leave the same bag.
The step is kept for vendor fidelity and for the day a boolean-valued field ships without that cast.

The hook's opening `blank()` return is unobservable in exactly the same way, and for a plainer reason: every sibling that reaches the equality test is filled, so a blank subject matches none of them, and the array arm intersects against an empty picked list.
It is a short-circuit, not a behaviour — the test named after it asserts what a reader can actually see, that clearing a value leaves the sibling which held it alone.

### An option value that is not a scalar takes no part in the subtraction

The array arm compares through `array_diff()` / `array_intersect()`, which compare as strings, so both sides are filtered to scalars first.
No option map can produce a non-scalar value — only a malformed client payload can — and such an entry does not survive a repair of the sibling holding it.

## Divergences

### The comparison reads the raw state, not the cast one

Filament hands the hook `$state` — the state a component's casts have already been applied to — and compares it against sibling values plucked straight out of the repeater's **raw** item bag.
The two vocabularies agree only while a field has no state cast.
Give one an enum and they part: `$state` is a `BackedEnum` instance while a sibling holds its backing string, so `'draft' !== Status::Draft` skips every sibling and the array arm's scalar filter drops the picked values altogether.
The repair silently does nothing, and `distinct()` — which compares raw to raw — still refuses the submission the concern exists to prevent.

Ours takes `$rawState` instead, so both sides of every comparison and every value written back speak the item bag's vocabulary.
Nothing else moves: an uncast field sees the identical values it saw before, and the three arms are unchanged.
An enum-backed field repaired by its backing value is the test that separates the two.

### The five sibling facts are one value object, not string surgery

Filament derives the item key and the field's path within an item by cutting the component's state path at the first dot after the repeater's (`->after("{$repeaterStatePath}.")->after('.')`), and each of its two sibling concerns recomputes that inline.
Ours resolves the same five facts — the repeater, the item key, the field path, the items prefix and the sibling keys — once, through [`ResolvesRepeaterSiblingContext`](../../../packages/forms/src/Components/Concerns/ResolvesRepeaterSiblingContext.php) on [`Field`](field.md), which answers a readonly [`RepeaterSiblingContext`](../../../packages/forms/src/State/RepeaterSiblingContext.php) or `null`.
The field path is taken by stripping the item schema's own prefix rather than by cutting at a dot, which survives an item key holding one and a layout component sitting between the item schema and the field.

The arithmetic is not new: `distinct()` used it first, and the extraction is what the two concerns here build on, so all three read the same block.
`null` means "outside a repeater item, or the only item" — the predicate then answers `false` and the hook returns.
