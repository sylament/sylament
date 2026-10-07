---
description: The multi-choice option field, one native checkbox per option inside an `aria-labelledby`-pointed group, with its search filter and bulk-toggle controller.
ports:
  - vendor/filament/forms/src/Components/CheckboxList.php
  - vendor/filament/forms/resources/js/components/checkbox-list.js
owns:
  - packages/forms/assets/src/controllers/checkbox_list_controller.ts
  - packages/forms/src/Components/CheckboxList.php
  - packages/forms/templates/partials/CheckboxList/
---
# CheckboxList (forms)

`Sylament\Forms\Components\CheckboxList` extends [`Field`](field.md) and casts state through schemas' [`OptionsArrayStateCast`](../../../packages/schemas/src/State/Casts/OptionsArrayStateCast.php).
It is the option field that is **inherently multiple**, so it takes the to-many arm of the [relationship trait](#the-to-many-relationship-arm-serves-this-field-unchanged) `Select::multiple()` also uses without declaring anything.
Its two bulk-toggle actions are [registered but inert](#the-bulk-toggle-actions-render-but-never-dispatch): the click lives on the markup around them.
The template renders every option and every target server side, and [`checkbox_list_controller`](#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) supplies the search filtering and the bulk toggle on top of it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering — the group, the implicit option labels, the description line | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-checked-attribute-is-rendered-server-side) · [diverges](#every-checkbox-carries-the-same-data-model) · [diverges](#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) · [diverges](styles.md#the-checkbox-skin-lives-in-support-because-tables-renders-it-too) |
| The grid, and its `Column` default — applied unconditionally, no `inline()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](has-grid-direction.md#the-column-default-belongs-to-the-field-not-to-this-getter) · [diverges](#the-options-grid-is-always-applied-and-the-option-containers-are-unkeyed) |
| `getEnumDefaultStateCast()` — `EnumArrayStateCast` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-list-rests-at-an-array-and-that-is-load-bearing) |
| `getDefaultStateCasts()` — `[OptionsArrayStateCast]` unless custom casts or an enum | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-list-rests-at-an-array-and-that-is-load-bearing) |
| `getInValidationConstraintValues()` / `hasInValidationOnMultipleValues()` → `buildValidationConstraints()`, a `Choice(multiple: true)` over the enabled options, replaced by a user `in()` list | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-three-server-side-guards-are-ordered-so-they-never-collide) · [semantics](can-be-validated.md#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| `required()` — the standard `RequiredValue` | [`Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#required-rejects-the-empty-list-with-the-required-constraint-alone) |
| `bulkToggleable()` / `isBulkToggleable()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-bulk-toggle-actions-render-but-never-dispatch) |
| `getSelectAllAction()` / `selectAllAction()` / `getSelectAllActionName()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-bulk-toggle-actions-render-but-never-dispatch) · [diverges](#the-bulk-toggle-labels-are-untranslated-literals) |
| `getDeselectAllAction()` / `deselectAllAction()` / `getDeselectAllActionName()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-bulk-toggle-actions-render-but-never-dispatch) · [diverges](#the-bulk-toggle-labels-are-untranslated-literals) |
| `relationship()` / `getRelationshipName()` / `getRelationshipTitleAttribute()` — via [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-to-many-relationship-arm-serves-this-field-unchanged) · [diverges](#the-relationship-state-is-not-pruned-from-the-dehydrated-bag) · [diverges](#the-relationship-option-map-is-unordered) |
| `getRelationship()` — the association target, resolved by the protected [`getRelationshipTarget()`](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php) | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#filaments-public-relationship-accessors-are-the-traits-private-closures-here) |
| `fillStateFromRelationship()` / `saveStateToRelationship()` — the trait's load and save closures | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#filaments-public-relationship-accessors-are-the-traits-private-closures-here) · [semantics](has-relationship.md#the-save-arm-writes-a-desired-list-not-attachdetach) |
| `getOptionLabelFromRecordUsing()` / `hasOptionLabelFromRecordUsingCallback()` / `getOptionLabelFromRecord()` — via `HasRelationship` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query) |
| `getOptionDescriptionFromRecordUsing()` / `hasOptionDescriptionFromRecordUsingCallback()` / `getOptionDescriptionFromRecord()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query) |
| `getLabel()` — derived from the relationship name when unset | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-relationship-label-is-a-getdefaultlabel-override) |
| `isMultiple()` — port plumbing, no Filament source | — | done | — | [diverges](#ismultiple-is-port-plumbing-with-no-filament-counterpart) · [translations](../translations.md#names-with-no-filament-source) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`HasDescriptions`](../../../packages/forms/src/Components/Concerns/HasDescriptions.php) | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [detail](has-descriptions.md) |
| [`HasGridDirection`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php) | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | [detail](has-grid-direction.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| [`CanAllowHtml`](../../../packages/forms/src/Components/Concerns/CanAllowHtml.php) | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | — | [detail](can-allow-html.md#the-switch-opens-exactly-one-hole-per-field) |
| [`CanBeSearchable`](../../../packages/forms/src/Components/Concerns/CanBeSearchable.php) — the markup, the debounce override and the client-side filter | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [detail](can-be-searchable.md#the-checkbox-list-search-is-client-side-only-and-mostly-select-only) · [diverges](#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) |
| [`CanLimitItemsLength`](../../../packages/forms/src/Components/Concerns/CanLimitItemsLength.php) — `minItems()` / `maxItems()` as a `Count` | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | [detail](can-limit-items-length.md) · [semantics](#the-three-server-side-guards-are-ordered-so-they-never-collide) |
| The search field's magnifying-glass icon and its [input wrapper](../support/input-wrapper.md) prefix slot | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-search-box-opens-its-own-input-wrapper-as-vendors-does) |
| `HasPivotData` — `pivotData()` on the save arm | [`Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | skipped | — | [diverges](has-relationship.md#the-save-arm-writes-a-desired-list-not-attachdetach) |
| `HasExtraAlpineAttributes` (concern) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| [`HasNestedRecursiveValidationConstraints` + its contract](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) — the per-element constraints | [`Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [diverges](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path) |
| [`CanFixIndistinctState`](../../../packages/forms/src/Components/Concerns/CanFixIndistinctState.php) | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [detail](can-fix-indistinct-state.md) |
| [`CanDisableOptionsWhenSelectedInSiblingRepeaterItems`](../../../packages/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [detail](can-disable-options-when-selected-in-sibling-repeater-items.md) |
| `getPublishedViewOverrideCheckPath()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | skipped | — | [diverges](toggle-buttons.md#there-is-nothing-to-publish-a-view-override-against) |

## Semantics

### The search box opens its own input wrapper, as vendor's does

A field template usually gets its [input wrapper](../support/input-wrapper.md) for the field's own control, driven by [`HasAffixes`](has-affixes.md).
The search box is not the field's control: it is a box **inside** the field, so the template opens `<twig:SylamentSupport:InputWrapper>` itself with a literal `heroicons:magnifying-glass-20-solid` prefix icon and `inlinePrefix` — vendor hand-builds exactly the same thing, and the class `sy-fo-checkbox-list-search-input-wrp` (which carries the `mb-4` and nothing else) rides through as a passthrough attribute the wrapper's root appends.
The wrapper's `prefixIconAlias` prop takes [`FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD`](../../../packages/forms/src/Twig/FormsIconAlias.php), so an override registered under `sylament.icons` replaces the magnifying glass.

### The to-many relationship arm serves this field unchanged

`relationship()` is not reimplemented: this field composes the same [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) `Select` does, and gets the whole three-closure arm ([how it works](has-relationship.md#relationship-is-three-lazy-closures-over-the-association-reader)) unchanged — the option query, the memo, the query scope, the `fill()` read and the desired-list write.

The trait's three injected-component closures are typed **`self`**, which inside a trait resolves to the composing class, so a `CheckboxList` closure receives a `CheckboxList`; the [`Invoker`](../support/component.md#closure-resolution-lives-in-a-service) normalizes a `self` hint against the declaring class when it resolves the injection.
The multiplicity match the trait runs on every read asks the field [`isMultiple()`](#ismultiple-is-port-plumbing-with-no-filament-counterpart).
A `CheckboxList` answers a constant `true`, so pointing it at a to-one association fails with the trait's refusal ("holds a single …") and the to-many arm is the only one it can take.
Composite keys ride the same inheritance: the option keys, the state read and the desired-list write are all the trait's, so the [key spelling](has-relationship.md#relationship-is-three-lazy-closures-over-the-association-reader) the trait guarantees holds here unchanged.

`getOptionsFromRelationship()` is overridden here only for [the record-shaped closures](#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query); with neither declared it delegates straight back to the trait's build, aliased in as `getTitleAttributeOptionsFromRelationship()`.
Both paths read the trait's `getRecordsFromRelationship()`, so the option query is built and scoped in one place.

### The two record-shaped closures build labels and descriptions off one query

`getOptionLabelFromRecordUsing()` replaces the title attribute as the source of each option's label, and `getOptionDescriptionFromRecordUsing()` adds a description per option.
Declaring **either** switches the option build onto the record path, and the descriptions are installed as a **side effect** of it — the component calls its own `descriptions()` while building the map, exactly as vendor does (`CheckboxList.php:222-230`), because both maps come off the one query and running it twice to keep them separate would be worse.

The label trio — `getOptionLabelFromRecordUsing()`, `hasOptionLabelFromRecordUsingCallback()`, `getOptionLabelFromRecord()` — lives in the shared [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) trait, because [`Select`](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) reads the same callback for its search and label arms.
Filament duplicates the three on both fields because it has no shared trait; the port has one, so both fields carry one copy, and the trait's own option build reads the callback too (`resolveOptionLabelFromRecord()`, callback first, title attribute otherwise).
This field's override branches ahead of the query for the description side effect.
The description trio stays here: `Select` has no descriptions.

Both closures receive the record as `$record` by name and by its concrete class, so `fn (Tag $record) => …` and `fn (object $record) => …` both work.
Filament types the parameter `Model` and injects `Model::class` too; the port has no such base type — a Doctrine entity is a plain object — so the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) applies and the signatures take `object`.

Two small deltas from vendor, both in the direction of less surprise: a description closure returning `null` (or `''`) leaves that option **without** a description rather than with an empty `<p>`, matching how [`HasDescriptions`](has-descriptions.md) treats an enum case that describes itself as null; and a label closure returning nothing yields `''`, the same coercion the title-attribute arm makes for an unreadable title.
Declaring either closure clears the option memo, so the two setters compose in any order.

### The list rests at an array, and that is load-bearing

`getDefaultStateCasts()` answers `[OptionsArrayStateCast()]` — the constructor default, `isNullable: false` — unless the field has custom casts or an [enum](has-enum.md), in which case `Field`'s chain contributes `EnumArrayStateCast` instead.
`Select::multiple()` spells the same argument out explicitly and `ToggleButtons`' multiple arm leaves it defaulted; this field follows `ToggleButtons`, and the value is pinned by a test rather than by the call site.

It is worth pinning because the resting shape is what makes the binding work at all.
`live_controller`'s `getValueFromElement()` reads the model's **current** value first and only treats a checkbox as add-or-remove-against-a-list when it finds an array there; a path resting at `null` would take the clicked option's bare `value` and collapse the whole list to a scalar.
`isNullable: false` writes `[]` rather than null, which the first case in [`LiveCheckboxListTest`](../../../packages/forms/tests/Integration/LiveCheckboxListTest.php) asserts on the wire before it asserts anything about checking.

### The three server-side guards are ordered so they never collide

`buildValidationConstraints()` appends three things in one order, and the order is the whole content of the method:

1. **A non-array raw state is failed by `Type('array')` alone**, and the method returns.
   `Choice(multiple: true)` is undefined over anything else — its validator throws rather than failing — and [`CountValidator`](../../../vendor/symfony/validator/Constraints/CountValidator.php) throws an `UnexpectedValueException` on a non-countable value, so neither may be allowed to meet a tampered scalar.
   A `null` raw state is not "not an array" for this purpose: it is an unset field, which both validators skip on their own.
2. **The `Count` from `minItems()` / `maxItems()`** — before the blank short-circuit, deliberately, because `minItems(2)` has to fail an empty selection and `blank([])` is true.
3. **The `Choice(multiple: true)`** over [`getEnabledOptionValues()`](can-disable-options.md), reached only once the payload left something there — which is a question about the [raw state](select.md#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out), because `EnumArrayStateCast` drops every element matching no case and a cast reading would hand a wholly tampered list back as an empty selection and skip the check that rejects it.
   It is the same [membership guard](select.md#the-choice-constraint-is-the-fields-only-server-side-guard) every option field builds, and the same [no-options `LogicException`](has-relationship.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design) when the field can enumerate nothing.

Filament reaches the same three answers through a different mechanism — `hasInValidationOnMultipleValues(): true` moves its `In` rule onto `"{$statePath}.*"`, and `minItems`/`maxItems` become `array|min:|max:` rules — which the port has no wildcard path for ([why](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path)); `Choice(multiple: true)` validates element by element natively, so the per-element check lands on the one path without one.

### required() rejects the empty list with the required constraint alone

`required()` builds the default [`RequiredValue`](can-be-validated.md#required-carries-laravels-blank-set-not-symfonys) and this field overrides `getRequiredConstraint()` nowhere.
That is enough: the constraint is one call to [`filled()`](../support/helpers.md), which counts an empty array blank, so `[]` fails and any non-empty selection passes.

`Count(min: 1)` would say the same thing with a worse message and a second way to spell one rule, so it is not what `required()` builds — the split [`Checkbox`](checkbox.md#required-on-a-checkbox-means-must-be-checked) and [`Toggle`](toggle.md#required-means-presence-not-truth) make does not reach this field, because a list has no truthiness question: "blank" and "nothing selected" are already the same value.
`minItems()` remains the way to require *more than one*, and it composes with `required()` rather than replacing it.

## Divergences

### The `checked` attribute is rendered server-side

Filament emits only `wire:model` on each input and lets Livewire set the checked property client-side once it boots.
The port renders `checked` from [`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template), so a server-rendered edit page shows the stored selection before any JavaScript runs.
Same addition [`Radio`](radio.md#the-checked-attribute-is-rendered-server-side), [`ToggleButtons`](toggle-buttons.md) and [`Select`](select.md#the-template-renders-the-selected-state-itself) make.

The comparison is `state is iterable and value in state` — Twig's `in` is loose for scalars, deliberately, because an option key may be an `int` where the state holds the `'1'` a browser submitted.
The `is iterable` guard is what a non-array state falls into: a tampered scalar checks nothing rather than matching an option by accident, and the strict [`Choice`](#the-three-server-side-guards-are-ordered-so-they-never-collide) is what admits or rejects it.

It also means the bulk-toggle's "are they all checked?" question is answerable from the DOM on the controller's first frame.
Vendor defers that read to `$nextTick` because Livewire has not hydrated the checkboxes yet when the Alpine component initialises; here they are already correct in the HTML.
The template spends that immediacy: it hardcodes the two action spans' initial visibility (select-all shown, deselect-all `hidden`), so a list rendered fully selected paints the wrong link for one frame, and the controller's `connect()`-time refresh — the same pass that runs on every search keystroke and every option change — is what reconciles it against the real state.

### Every checkbox carries the same `data-model`

One `data-model` per input, all naming the field's one state path — the translation of Filament's one `wire:model` per input, and the same shape [`Radio`](radio.md#every-radio-carries-the-same-data-model) and `ToggleButtons`' multiple arm use.
It reads like a multi-binding and is not: `live_controller`'s `getValueFromElement()` has a checkbox branch that adds-or-removes the clicked input's `value` against the list already at that path, so the shared path receives the whole selection and the client sends a list rather than a delta.

Unlike `Radio`, the inputs carry **no `name`** — a checkbox group is not grouped by `name` in the browser, and a shared one would submit a scalar on a native form post.
They do carry per-option `id`s, as `Radio`'s do, which is what idiomorph matches on when a re-render replaces the list.

### The option inputs carry `aria-invalid` instead of a `fi-valid` / `fi-invalid` class pair

Filament puts `fi-valid` or `fi-invalid` on every checkbox input.
The port emits neither and writes `aria-invalid` on each input instead, with [`checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css) keying its danger arm off `[aria-invalid='true']`.

This is not a CheckboxList decision — it is the translation [`Radio`](radio.md#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) records, applied to the field that shares `Checkbox`'s control, and it holds for the same reasons.

### The bulk-toggle actions render but never dispatch

Filament builds both actions with `->livewireClickHandlerEnabled(false)` and wraps each in `<span x-on:click="toggleAllCheckboxes()">`, so the rendered `<a class="fi-link">` carries no handler of its own and the span supplies the only behaviour.
The `Action` objects exist so `label()`, `size()`, `icon()`, `color()` and the user's `selectAllAction(fn (Action $action) => …)` all work.

The port reproduces that mode with `liveComponentClickHandlerEnabled(false)`, the [click gate](../actions/has-action.md#the-click-gate-is-what-keeps-a-form-submit-from-mounting-a-second-action) under the `Livewire` → `LiveComponent` [idiom translation](../translations.md#idiom), so the action partial emits no `stimulus_action('live', 'action', …)` mount binding and a click costs no server round-trip.
The click then lives on the markup, as vendor's does: each action sits inside a `<span>` carrying `data-sylament--forms--checkbox-list-target="selectAllWrap"` (or `deselectAllWrap`, which starts `hidden`) and `data-action="click->sylament--forms--checkbox-list#toggleAll"`.

### The controller is the only behaviour, and no test in this repo runs it

[`checkbox_list_controller`](../../../packages/forms/assets/src/controllers/checkbox_list_controller.ts) ports vendor's Alpine component and ships in the [`@sylament/forms`](../../../packages/forms/assets/package.json) workspace.
Its filter is vendor's in shape — an empty query shows everything, a non-empty one is a case-insensitive substring of the option's rendered label **or** description `innerText`, never its value — and so is the all-satisfied predicate that flips the two action spans, disabled boxes counting as satisfied.
One filter detail is ours rather than vendor's: the query is `trim()`ed before it is lowered, so a whitespace-only search shows everything here where vendor — whose only empty test is `['', null, undefined].includes()` — substring-matches those spaces and hides nearly every option.
Both sides re-read `innerText` on options the previous pass already hid, ours behind the `hidden` attribute and vendor's behind `x-show`'s `display: none`; an unrendered element's `innerText` falls back to `textContent`, which is what lets a widened query find an option it had just filtered out.
Four wiring points have no direct translation:

1. **Livewire's `interceptMessage` hook becomes a `MutationObserver`.**
   Vendor re-collects the options after every round-trip belonging to its own component id; the port has no per-component success hook, so the controller observes its own element for `childList` changes and re-runs the same pass.
   `childList` **only** is load-bearing: the pass writes `hidden` attributes on the options, so observing attributes would feed the observer its own output.
   The residual gap is a morph that rewrites `checked` on the *same* option elements and touches no child list — the label would keep the state it had.
   It is narrow because a server round-trip on this field is a response to a client change, which the delegated listener below already refreshed on, and it is the reason to reach for a render event rather than a widened observer if it ever bites.
2. **The per-option `x-on:change` hook becomes one delegated listener.**
   Vendor puts a change handler on every input so an individual click re-flips the select/deselect label; the template emits no action on the inputs, so the controller attaches a single `change` listener to its root in `connect()` and filters to checkbox inputs — the same imperative shape the search input's `input` listener takes, and for the same reason (the template supplies the target, not the binding).
   It also avoids vendor's O(N) re-check during a bulk toggle: the write sets a flag the listener skips on, and the toggle ends with one refresh.
3. **The synthetic `change` event has to bubble.**
   Vendor's `new Event('change')` does not, and Livewire's model listener sits on the element itself.
   `live_controller` registers its `change` listener on the **component root** and reads `event.target` (`elementEventListeners` → `handleChangeEvent()` → `updateModelFromElementEvent()`), so a non-bubbling event would flip the box and never reach the state.
   Ours passes `{ bubbles: true }`.
4. **Vendor's `$nextTick` on the first read is dropped**, because [the `checked` attribute is rendered server-side](#the-checked-attribute-is-rendered-server-side) — the DOM is already correct when `connect()` runs, which is also what lets that first pass reconcile the template's hardcoded span visibility.

One mechanism the filter rests on is not the controller's at all: **its `hidden` attributes survive a morph**.
Every option the filter hides is an attribute write the server never made, so a re-render's HTML carries none of them and a plain morph would unhide the lot on the next round trip.
ux-live-component prevents exactly that.
Its `ExternalMutationTracker` ([`live_controller.js:1197`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js)) runs a `MutationObserver` with `attributes: true` over the component root the whole time no render is in flight, filing every outside attribute write against the element that took it; `executeMorphdom` then reads that file back in idiomorph's `beforeNodeMorphed` callback — `:1046` looks the element up and `:1049` calls `applyToElement()` on the **incoming** node, which replays the recorded attribute changes — so the new markup arrives already carrying the filter's decisions.
Nothing in the port asks for this and nothing would report it missing: without it a search would silently reset itself on every `live()` update.
It is also the division of labour behind point 1 above — the observer is for the options a morph **adds or removes**, not for the attributes it would otherwise erase.

One behavioural divergence sits in the empty-filtered-set corner.
Vendor compares `visible.length === satisfied.length`, which is vacuously true for an empty set, so a search matching nothing shows **"Deselect all"** over an empty list.
Ours requires a non-empty set, resting on "Select all" instead.
Both links are inert there — there is nothing visible to write — so this is a label choice, and the one that does not offer to deselect nothing wins.

There is no JS test rig — no jsdom, no browser driver, nothing that instantiates a Stimulus controller — so the [render tests](../../../packages/forms/tests/Integration/Components/CheckboxListTest.php) assert the controller's *contract* (identifier, targets, the debounce value, the click actions) and nothing asserts its *behaviour*.
The filter, the bulk toggle, the observer and the delegated listener are verified by reasoning against the installed vendor sources and by driving the demo in a browser, never by execution here.

### The bulk-toggle labels are untranslated literals

`'Select all'` and `'Deselect all'` are literals where Filament reads `filament-forms::components.checkbox_list.actions.select_all.label` and `…deselect_all.label`.
Same call as [`Radio`'s boolean labels](radio.md#the-boolean-labels-are-untranslated-literals); the keys are recorded so the strings can be found when a translation layer lands, and Filament's own English values are these two.
These are the only keys in this field's own `checkbox_list` family — everything the search box says comes from [`select.*`](can-be-searchable.md#the-four-message-literals-are-untranslated).

### `isMultiple()` is port plumbing with no Filament counterpart

Filament reads this field's multiplicity off a type: `getRelationship(): ?BelongsToMany` says "to-many" in the signature, and nothing has to ask.
The port's shared relationship arm asks the field instead, because one trait serves a `Select` that may be either, so `CheckboxList` answers a constant `true` from an `isMultiple()` of its own.

It is **internal**: no `multiple()` setter exists, because there is nothing to choose.
Listed in [translations.md](../translations.md#names-with-no-filament-source) so a migrating user who greps Filament for it and finds nothing knows the absence is real.

### Filament's public relationship accessors are the trait's private closures here

Filament's `CheckboxList` publishes `getRelationship()`, which returns the `BelongsToMany`, and the `fillStateFromRelationship()` / `saveStateToRelationship()` pair its `relationship()` closures call.
The port publishes none of the three.
[`HasRelationship::relationship()`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) wires a load closure and a save closure that call the trait's private `getStateFromRelationship()` and `saveStateToRelationship()`, and the association target comes from the protected `getRelationshipTarget()` of schemas' [`BelongsToRelationship`](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php), read through the trait's multiplicity match.
Those closures are the only callers, and a caller that needs to know the association reads its name from `getRelationshipName()`.

### The relationship label is a `getDefaultLabel()` override

Vendor overrides `getLabel()` itself, branching on `$this->label === null` and falling through to `parent::getLabel()`.
The port's [`Field`](field.md) splits that decision in two — `getLabel()` takes an explicit label when one is `filled()` and otherwise calls `getDefaultLabel()` — so the relationship derivation belongs in the second half, and the field overrides `getDefaultLabel()` rather than the getter above it.

Same layering, same precedence (`label('…')` wins, then the relationship name, then the field name), one fewer place that has to remember to call its parent.
The derivation itself is vendor's, spelled in Symfony's `u()`: `->before('.')->kebab()->replace('-', ' ')->title()`, whose `title()` uppercases only the first letter as vendor's `ucfirst()` does, which is why `CheckboxList::make('tagIds')->relationship('tags')` reads as "Tags" while the same field without a relationship reads as "Tag ids".

### The relationship state is not pruned from the dehydrated bag

Filament's `relationship()` ends with `dehydrated(false)`.
This field's does not, and neither does `Select`'s or `Repeater`'s: the port's dehydration walk is what **routes** a value to its save hook, so pruning the path would switch the feature off rather than protect it.
The state stays in the bag and the hook takes it — the port-wide reading recorded in [renames.md](../renames.md#renames) and [repeater-relationship.md](repeater-relationship.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope), pinned for this field by [`CheckboxListRelationshipTest`](../../../packages/forms/tests/Integration/Components/CheckboxListRelationshipTest.php).

### The relationship option map is unordered

Filament's `CheckboxList` orders its title-attribute options by that attribute when the scoped query carries no order; the port's reads [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php)'s map as the database returns it.
`Select` orders its own two arms ([has-relationship.md](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query)); this field does not, so a consumer that wants an order sets it in the `relationship(modifyQueryUsing:)` closure.

### The options grid is always applied, and the option containers are unkeyed

Vendor grids the options unconditionally — `CheckboxList` has no `inline()` where [`Radio`](radio.md#inline-is-radios-own-property-not-the-canbeinline-trait) and `ToggleButtons` do — and the port keeps that: the `Column` default and `columns()` work, `inline()` does not exist to be called.

Vendor also keys every option container with `wire:key="{livewireKey}.options.{value}"` and the empty branch with a `.empty` anchor.
The port emits neither.
Livewire's `wire:key` is a morph hint for a list whose *members can move*; this list is regenerated wholesale from one options map, in map order, on every render — nothing reorders — and idiomorph matches on the per-option input `id`s the template emits, which is the same anchor `Radio` relies on.
The empty-branch anchor has nothing to anchor once the keys are gone, so an empty list renders an empty grid.
