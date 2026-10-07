---
description: The select field over a static option map, an enum or a Doctrine association, rendered as the native `<select>` or as the ported JS control.
ports:
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php
  - vendor/filament/forms/src/Components/Concerns/CanBePreloaded.php
  - vendor/filament/forms/src/Components/Concerns/HasLoadingMessage.php
  - vendor/filament/support/resources/js/utilities/select.js
  - vendor/filament/forms/resources/js/components/select.js
  - vendor/filament/forms/resources/lang/en/components.php
owns:
  - packages/forms/src/Components/Concerns/CanBePreloaded.php
  - packages/forms/src/Components/Concerns/CanSelectPlaceholder.php
  - packages/forms/src/Components/Concerns/HasLoadingMessage.php
  - packages/forms/src/Components/Select.php
  - packages/forms/templates/partials/Select/
---
# Select (forms)

`Sylament\Forms\Components\Select` extends [`Field`](field.md) and renders as the native `<select>` or, once it is searchable, multiple, renders HTML labels or declares `native(false)`, as the JS control forms' `select` Stimulus controller builds, a port of Filament's own select utility.
The option concerns it shares with other fields each have their own file, linked from the rows below, and its relationship arm is written up in [`has-relationship.md`](has-relationship.md), beside the trait [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) composes too.
It casts its state through schemas' [`OptionStateCast`](../schemas/option-state-cast.md) — or, under `multiple()`, [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md), or, under an [enum](has-enum.md), [`EnumStateCast`](../schemas/enum-state-cast.md) / [`EnumArrayStateCast`](../schemas/enum-array-state-cast.md) — and guards a submitted value server-side by a Symfony `Choice` over its enabled options where the option map is the whole truth, and by [probing the value's label](#the-choice-constraint-is-the-fields-only-server-side-guard) where it is not.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<select>` rendering | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-template-renders-the-selected-state-itself) · [diverges](#the-default-placeholder-is-an-untranslated-literal) |
| The JS control — [`CanBeNative`](can-be-native.md) and the `native(false)` / searchable / multiple / HTML branch | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) · [`support/…/select.js`](../../../vendor/filament/support/resources/js/utilities/select.js) | done | — | [semantics](#getextratemplatedata-makes-the-one-getoptions-read-both-branches-share) · [diverges](#nativefalse-switches-to-the-js-control) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`HasEnum`](../../../packages/forms/src/Components/Concerns/HasEnum.php) — `getEnumDefaultStateCast()` overridden for `multiple()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](has-enum.md) |
| [`CanSelectPlaceholder`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php) | [`Concerns/CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | done | — | — |
| `disablePlaceholderSelection()` | [`Concerns/CanSelectPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | skipped | — | [diverges](#disableplaceholderselection-is-not-ported) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`hasNullableBooleanState()`](../../../packages/forms/src/Components/Select.php) | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [field.md](field.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) | [`Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [diverges](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path) |
| `HasExtraAlpineAttributes` (concern) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| `boolean()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | — |
| `getOptionLabel(withDefault)` / `resolveOptionLabel()` / `getOptionLabelUsing()` | [`resolveOptionLabel()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing) |
| `getOptionLabels(withDefaults)` / `getOptionLabelsUsing()` | [`getOptionLabels()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing) |
| Option validation — a `Choice`, or the label probe where the map is partial | [`getInValidationRuleValues()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-choice-constraint-is-the-fields-only-server-side-guard) · [semantics](can-be-validated.md#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| [`CanBeNative`](can-be-native.md) | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | done | — | [detail](can-be-native.md) · [diverges](#nativefalse-switches-to-the-js-control) |
| `multiple()` / [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md) — the chips control | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-multiple-select-validates-element-by-element) · [semantics](#multiple-forces-the-js-control-and-is-searchable-by-default) |
| [`CanBeSearchable`](can-be-searchable.md) — with `Select`'s column-array `searchable()` | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) · [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [detail](can-be-searchable.md) |
| `getSearchResultsUsing()` / `getSearchResults()` — `query` / `search` / `searchQuery` injections | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-search-round-trip-is-a-live-action-that-re-renders-the-host) |
| [`CanBePreloaded`](../../../packages/forms/src/Components/Concerns/CanBePreloaded.php) — `preload()` / `isPreloaded()` | [`Concerns/CanBePreloaded.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBePreloaded.php) | done | — | [semantics](#preload-enumerates-the-limited-map-up-front) |
| `optionsLimit()` / `getOptionsLimit()` — 50 | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) |
| [`HasLoadingMessage`](../../../packages/forms/src/Components/Concerns/HasLoadingMessage.php) — `loadingMessage()` / `getLoadingMessage()` | [`Concerns/HasLoadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasLoadingMessage.php) | done | — | [diverges](#loadingmessage-has-no-render-site) · [diverges](#the-loading-and-max-items-strings-are-untranslated-literals) |
| [`CanLimitItemsLength`](can-limit-items-length.md) — with `maxItemsMessage()` / `getMaxItemsMessage()` | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) · [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-multiple-select-validates-element-by-element) · [diverges](#the-loading-and-max-items-strings-are-untranslated-literals) · [diverges](has-relationship.md#a-composite-relationship-select-past-maxitems-answers-the-count-alone) |
| [`CanAllowHtml`](can-allow-html.md) — `allowHtml()` | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | — | [detail](can-allow-html.md) · [semantics](#transformoptionsforjs-casts-a-markup-label-to-text) |
| `transformOptionsForJsUsing()` / `transformOptionsForJs()`, `getSearchResultsForJs()` / `getOptionsForJs()` / `getOptionLabelsForJs()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#transformoptionsforjs-casts-a-markup-label-to-text) · [semantics](#the-search-round-trip-is-a-live-action-that-re-renders-the-host) |
| `dynamicOptions()` / `hasDynamicOptions()` / `hasDynamicSearchResults()` / `hasInitialNoOptionsMessage()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-search-round-trip-is-a-live-action-that-re-renders-the-host) |
| `position()` / `getPosition()`, `wrapOptionLabels()` / `canOptionLabelsWrap()`, `forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-dropdown-flips-and-escapes-the-modal) |
| `reorderable()` / `isReorderable()` — chips dragged into order, the order written back | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#reorderable-drags-the-chips-through-sortablejs-and-writes-the-order-back) |
| `getSelectedRecordUsing()` / `getSelectedRecord()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) |
| `createOptionForm()` / `createOptionUsing()` / `createOptionAction()` / `getCreateOptionAction()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) · [diverges](#the-non-relationship-schema-record-is-read-off-the-field-not-off-a-getactionschemamodel) · [diverges](#a-key-an-option-actions-mutation-adds-that-no-component-maps-is-refused) |
| `editOptionForm()` / `updateOptionUsing()` / `fillEditOptionActionFormUsing()` / `editOptionAction()` / `getEditOptionAction()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) · [diverges](#geteditoptionactionformdata-also-answers-an-object) · [diverges](#a-key-an-option-actions-mutation-adds-that-no-component-maps-is-refused) |
| `refreshSelectedOptionLabel()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [diverges](#refreshselectedoptionlabel-dispatches-a-browser-event-the-control-answers-by-re-syncing) · [translations](../translations.md#names-with-no-filament-source) |
| `manageOptionForm()` / `manageOptionActions()` / the two modal headings | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#the-option-actions-are-suffix-actions-with-doctrine-defaults) · [diverges](#the-option-action-strings-are-untranslated-literals) |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [detail](can-fix-indistinct-state.md) |
| `CanDisableOptionsWhenSelectedInSiblingRepeaterItems` | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [detail](can-disable-options-when-selected-in-sibling-repeater-items.md) |
| Affixes ([`HasAffixes`](../../../packages/forms/src/Components/Concerns/HasAffixes.php), `wrapInputHtml`) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [detail](has-affixes.md) · [semantics](#the-inline-prefix-class-counts-visible-actions-where-the-text-inputs-counts-all-of-them) |
| [`required` drops when concealed](../../../packages/forms/templates/partials/Select/index.html.twig) — the native branch only; `aria-required` is ungated | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |
| `getLabel()` — derived from the relationship name when unset | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | todo | — | [detail](#the-relationship-name-does-not-default-the-label) |
| `getActionSchemaModel()` — the create modal's record without a relationship | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | skipped | — | [diverges](#the-non-relationship-schema-record-is-read-off-the-field-not-off-a-getactionschemamodel) |

## Details

### The relationship name does not default the label

Filament's `Select::getLabel()` answers the humanized relationship name, the part before the first dot, when no `label()` is set and a relationship is declared, so `Select::make('author_id')->relationship('author')` is labelled *Author*.
The port's `Select` overrides neither `getLabel()` nor `getDefaultLabel()`, so the same field is labelled *Author id*, humanized from the field name.
[`CheckboxList`](checkbox-list.md#the-relationship-label-is-a-getdefaultlabel-override) carries the derivation as a `getDefaultLabel()` override, which is the shape this row wants.

## Semantics

### The `Choice` constraint is the field's only server-side guard

A native `<select>` gives no server-side guarantee at all: the submitted value is an ordinary string a client can set to anything, and `disabled` on an `<option>` is a browser-side courtesy — and the JS control gives less still, since its value never touches a form control the browser validates.
[`buildValidationConstraints()`](../../../packages/forms/src/Components/Select.php) therefore guards the [filled raw state](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) one of two ways, and the split is `hasFullOptionMap()`: whether `getOptions()` enumerates every value the field accepts.

**The enumerable arm** — a static `options()` map, an enum, or a relationship select that is not searchable — appends a `Choice` over [`getEnabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php); that one constraint is what rejects both a tampered value and a value whose option is disabled.
Symfony's `Choice` is always strict (`ChoiceValidator` throws on any other setting), so the comparison is identity, which is why the choices are built in [both forms the raw state can take](can-disable-options.md#the-choice-choices-cover-both-forms-the-raw-state-can-take).
A static map is the whole truth even when a `getOptionLabelUsing()` is declared beside it — the closure customizes the *label*, the map still says which values exist — where Filament would probe the closure and never consult the map.
The stricter reading is kept because it is tamper-proof without asking anything of a user closure.

**The partial arm** — a searchable relationship select, preloaded or not, whose map is empty or limited by design; or a select with no `options()` at all but a `getOptionLabelUsing()` (single) / `getOptionLabelsUsing()` (multiple) — ports Filament's `getInValidationRuleValues()`: the submitted value is legal when it **resolves a label** and that option is not disabled.
Single ⇒ [`getOptionLabel(withDefault: false)`](#getoptionlabel-resolves-off-the-map-or-through-getoptionlabelusing), blank ⇒ invalid; multiple ⇒ `getOptionLabels(withDefaults: false)`, then Filament's three checks in order — more values than labels, a value the labels do not key, any label whose option is disabled — each ⇒ invalid.
An invalid probe appends `Choice(choices: [])` (Filament's `[]` in-rule), so the value fails closed with the ordinary "not a valid choice" violation rather than being waved through; a valid probe appends nothing.
For a relationship select the probe's record source is [`getSelectedRecordUsing()`'s query](#the-option-actions-are-suffix-actions-with-doctrine-defaults), so a key outside `modifyQueryUsing`'s scope is as invalid as a missing one — Filament's semantics.
The probe resolves state at constraint-build time, the precedent [`DateComparison`](can-be-validated.md) set.

The `LogicException` raised when a filled select has nothing to validate with exists so that "validates nothing" can never be the silent default, and it widens per arm exactly as Filament's does: single ⇒ no `options()`, no enum **and** no `getOptionLabelUsing()`; multiple ⇒ … and no `getOptionLabelsUsing()` — so a `multiple()` select carrying only the single-arm closure throws, as upstream.
`hasFullOptionMap()` is `protected` and Sylament-only; Filament's `null` map return plays that role.

One shape is guarded by a different constraint, because `Choice` cannot express it: a `multiple` select whose raw state is not an array ([below](#a-non-array-raw-state-is-failed-by-a-type-constraint)).

The constraint carries no message argument: one is written afterwards by the [post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built), keyed `validationMessages([Choice::class => …])` where Filament keyed the `in` rule.
`Choice` declares four message options (`message`, `multipleMessage`, `minMessage`, `maxMessage`), so the string shorthand sets `message` — the one a single-value select violates — and an option map reaches the others.

The *whenever state is filled* half has an upstream twin, schemas' presence gate, and the two do not stack.
`Choice` is not implicit, so the [presence gate](../schemas/presence-gate.md#the-gate-is-a-no-op-for-select-which-already-guarded-itself) would skip it against the `''` a placeholder submits — except this builder never emits one for a blank state in the first place, so the gate finds nothing to filter.
The guard here is not redundant with it either: it exists because there are no choices to enumerate for a value that is not there, which is a different question from whether a constraint should run.

### The *filled* gate reads the raw state, because a cast can null a tampered value out

All four option fields carry the same short-circuit in `buildValidationConstraints()` — there are no choices to enumerate for a value that is not there — and the state it asks about has to be the **raw** one, `blank($this->getRawState())`.
Reading `getState()` there would be a validation bypass.

The two readings differ for exactly one input, and it is the one that matters.
A state cast may find nothing it can represent in what a payload wrote: [`EnumStateCast`](../schemas/enum-state-cast.md) fails closed to `null` for a value matching no case, and [`EnumArrayStateCast`](../schemas/enum-array-state-cast.md) drops every such element.
So a tampered `'deleted-by-hand'` on an enum-backed field casts to `null`, a *cast* gate reads "nothing selected", the `Choice` is never appended, and the field is left holding the required constraint alone — which a non-blank string passes.
[`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) then validates the **raw** bag entry against that empty guard, so the tampered value clears validation and reaches the record write, where a typed setter throws a `TypeError` — a 500 where a rejection belonged.

The raw gate splits two cases a cast gate conflates:

- **Nothing was submitted** — the raw state is `null`, `''` or `[]` — and the `Choice` is still skipped, so an unanswered required field is failed by the required constraint alone and an optional one passes untouched.
  Pinned by the required and blank-state cases in all four suites.
- **Something was submitted that resolves to nothing** — the raw state is filled, so the `Choice` runs and [`getEnabledOptionValues()`](can-disable-options.md#the-choice-choices-cover-both-forms-the-raw-state-can-take), which enumerates both stored forms of every enabled option, rejects it.

Reading raw is the only reading consistent with what is validated: the constraint list guards the raw bag entry, so the gate deciding whether to build it must ask about that same value.
The non-enum arms cannot show the difference: [`OptionStateCast`](../schemas/option-state-cast.md) passes a tampered scalar straight through, so its cast state is filled and the `Choice` is built either way.

Reading raw adds rejections over a cast gate without removing any.
Every cast these fields install — the option, options-array, enum, enum-array and `boolean()` casts — maps a blank raw state to a blank cast state, so every value a cast gate would hand the `Choice` reaches it here too.
What it catches beyond a cast gate is the fail-closed arm of each cast: besides the enum case above, a raw `false` on a single field (`OptionStateCast` stringifies it to `''`) and a raw array on a single field (no scalar to read, so `null`).
The array one lands on `Choice(multiple: false)`, whose validator does an `in_array()` and fails it rather than throwing, so it is a violation and not a 500.

The gate is four one-line reads rather than a shared helper, deliberately.
`getRawState()` is already the shared accessor ([schemas' component-level `HasState`](../schemas/component.md)); each builder puts its gate at a different point in a different sequence — [`CheckboxList`](checkbox-list.md#the-three-server-side-guards-are-ordered-so-they-never-collide) runs two constraints ahead of it — and a name like `hasBlankState()` would hide the one thing a reader of this method has to see, which state is being asked about.

### A multiple select validates element by element

`multiple()` swaps the field's default cast for [`OptionsArrayStateCast`](../schemas/options-array-state-cast.md), so the state is a list and the `Choice` gains `multiple: true` — which is what makes Symfony validate each submitted element against the choices instead of the array as a whole.
Three consequences worth stating, because each looks like an omission in the code:

- **An empty selection builds no membership constraint.**
  The builder's [`blank($this->getRawState())`](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) early return already covers it — [`blank([])`](../support/helpers.md) is true — so the empty-selection case needs no branch of its own, and validation of "must this field be filled?" stays with the presence/required layer where it belongs.
  The one constraint built *ahead* of that return is the item-count `Count` from `minItems()` / `maxItems()` ([`CanLimitItemsLength`](can-limit-items-length.md)), exactly as `CheckboxList` orders it, so a `minItems(1)` select fails `[]` — the empty list is a count question, not a membership one.
- **The choices stay scalar.**
  `getEnabledOptionValues()` runs each option key through the cast chain, and a multi-value cast turns one key into a *one-element list*; the method spreads that list rather than pushing it, since `Choice(multiple: true)` compares elements and a value wrapped in an array would match nothing.
  Both stored forms of a key are emitted, [for the same reason](can-disable-options.md#the-choice-choices-cover-both-forms-the-raw-state-can-take).
- **The cast is non-nullable.**
  `OptionsArrayStateCast(isNullable: false)` writes `[]` for an empty selection, not `null`, so the host's bag keeps an array the template can iterate and the browser can write back.

### A non-array raw state is failed by a `Type` constraint

`Choice(multiple: true)` is defined over arrays only: [`ChoiceValidator::validate()`](../../../vendor/symfony/validator/Constraints/ChoiceValidator.php) throws `UnexpectedValueException` for any other value rather than building a violation.
Validation runs against the **raw** state, which a tampered payload controls, and the field's [filled gate](#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) does not stop a scalar from reaching the builder — a non-blank string is exactly what that gate lets through.
Handing it to the `Choice` would end the request in a 500 instead of a rejection.

The builder therefore branches before it appends the `Choice`: when the field is `multiple` and the raw state is not an array, it appends a `Type('array')` and returns.
The payload is failed, with a violation on the field, and every array-shaped state takes the `Choice` (or [probe](#the-choice-constraint-is-the-fields-only-server-side-guard)) path unchanged.
The `Type` check comes first, ahead of the item-count `Count` and the no-`options()` `LogicException` — the order [`CheckboxList`](checkbox-list.md#the-three-server-side-guards-are-ordered-so-they-never-collide) already had, since `Count` throws on a non-array too.

### The template renders the selected state itself

The **native** branch's template emits `selected` per option, comparing the option's key against [`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template) — `in` when the state is a list, a loose `==` otherwise.
For a single select this is redundant with the live binding on a first render and becomes load-bearing on any server-rendered non-live one.
The JS branch's hidden `<select>` renders the same option loop and, beside it, every `selectedOptions` entry the map does not carry as a `selected` option — so a non-preloaded searchable select's DOM holds its selection with a label and degrades to a readable value without JS — while the control itself paints from the `selectedOptions` value; the comparison below is the native branch's.
The comparison is deliberately loose because the two sides speak different vocabularies — an option key is an `int` where the state may hold the `'1'` a browser submitted — the same mismatch [`getEnabledOptionValues()`](can-disable-options.md#the-choice-choices-cover-both-forms-the-raw-state-can-take) answers on the validation side.
Nothing is loosened security-wise: `selected` is presentation, and the strict `Choice` is what admits or rejects a value.

What the comparison never sees is a raw `getState()`: the normalization is one method on [`HasOptions`](has-options.md), so an enum-valued or boolean state reaches the template already spelled in the option vocabulary, and `getOptionLabel()` resolves through the same method rather than repeating the bool-to-int coercion.

### `getOptionLabel()` resolves off the map or through `getOptionLabelUsing()`

`getOptionLabel(bool $withDefault = true)` is a thin `#[ExposedLiveComponentMethod]` wrapper over `resolveOptionLabel($withDefault, ?array $options)`, Filament's shape, and the two arms are Filament's too.

**The map arm** — no `getOptionLabelUsing()` — looks the selected value up in the option map (the one given, else `getOptions()`), walking both the flat and the grouped shape.
The match is an array-key lookup rather than a comparison, which is what Filament's `$options[$state]` is: PHP canonicalizes a numeric-string key to an int, so a state of `'1'` finds the `1` option however the field's casts spell it.
The state is read through [`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template), so an enum unwraps and a `bool` normalizes to `int` first — the coercion PHP would apply to an array offset anyway, and the one `boolean()` depends on to resolve `true` to its `1 => 'Yes'` option.
Without a match, `$withDefault` answers the state itself as text (Filament's `return $state`, made a string because the return type is `string|Markup|null`) and `false` answers `null` — the reading validation probes.

**The closure arm** — `getOptionLabelUsing()` declared — evaluates it with `'value'` injected as a **closure** returning `getState()`, so a `fn ($value) => …` reads the state lazily and a closure that never asks for it never triggers the read; the by-reference capture is Filament's, so the `$withDefault` fallback reuses the state the closure read rather than reading it twice.
What the closure answers is coerced the one way an option label can be: `Markup` as-is, a scalar, a `Stringable` or an enum's backing value as a string, anything else `null`.
`relationship()` installs this arm, so a relationship select resolves its label from the **record** ([the selected-record query, then the callback or the title attribute](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query)) even when its map would have held it — Filament-exact, and one extra query per render on a preloaded select that the memo on `getSelectedRecord()` bounds to one.

`getOptionLabels(bool $withDefaults = true, ?array $options = null)` is the plural twin: the map arm walks the comparison state's list and defaults an unlabelled value to itself when asked, and the closure arm evaluates `getOptionLabelsUsing()` with `'values'` injected lazily, then defaults a **blank** label to its value or drops the key.
A value the closure did not return at all is absent either way — Filament's shape, and the reason a relationship select's `selectedOptions` carries only the rows the scope admits.
`getOptionLabelsForJs()` takes no argument for the same reason Filament's does not: it reads the state itself.

### `getExtraTemplateData()` makes the one `getOptions()` read both branches share

The class hook — `docs/internals/php.md`'s Twig-side port of the `toEmbeddedHtml()` local-variable preamble — performs the render's single `getOptions()` read (a `modifyQueryUsing` relationship map is not memoized, so three reads would be three queries and three maps that could disagree) and exposes four values both template branches read as plain variables: `options` (the raw map, which the `<option>` loop of **both** branches iterates — never `component.getOptions`), `optionsForJs` (`transformOptionsForJs($options)`), `selectedOptions` (the current selection as a `{value, label}` list — Filament's `initialOptionLabel` / `initialOptionLabels`, resolved through `resolveOptionLabel(options: $options)` for a single select and `getOptionLabels(options: $options)` for a multiple one, so the JS control paints its closed state with zero round-trips), and `isNative` — `! (isSearchable() || isMultiple() || isHtmlAllowed()) && isNative()`, [Filament's `Select.php:1837` rule](can-be-native.md#a-select-is-native-only-when-nothing-forces-the-js-control).
Two of the four are computed only for the JS branch: a native select gets `optionsForJs` and `selectedOptions` as `[]`, because Filament computes both inside its JS branch too and a native relationship select would otherwise pay the selected-record query for a value nothing renders.
None of the four is a public method, so none needs a rename row.

### `transformOptionsForJs()` casts a `Markup` label to text

`setUp()` installs the default `transformOptionsForJsUsing` closure Filament's does — a flat option becomes `{label, value: (string) $value, isDisabled}` and a group `{label: <group key>, options: […]}` — and it is what the four exposed methods and `optionsForJs` map through.
One port clause: a `Markup` label is cast to a plain string here, so raw HTML travels to the control only under `allowHtml()`, whose controller-side switch decides whether to escape it.
That bends the codebase's [`Markup`-renders-raw contract](can-allow-html.md#the-switch-opens-exactly-one-hole-per-field) in one place, deliberately: the JS payload is data, not a Twig sink, and the control has exactly one switch for whether it treats a label as markup.
`transformOptionsForJsUsing(null)` un-installs the default and makes `transformOptionsForJs()` throw, naming the setter — Filament's would `TypeError` on the null return; either way there is no shape to send.

### `preload()` enumerates the limited map up front

Under the port's [server-rendered options](#nativefalse-switches-to-the-js-control), `preload()` means *the server enumerates the (limited) option map into the markup*: `getOptionsFromRelationship()` returns `[]` for a searchable select that is not preloaded and the limited, title-ordered map for one that is; a non-searchable relationship select enumerates its full map, and a static `options()` map is always in the markup regardless.
`hasDynamicOptions()` and `hasInitialNoOptionsMessage()` are ported verbatim and read `isPreloaded()` the same way Filament's do, though the first buys nothing here — see the [divergence](#the-search-round-trip-is-a-live-action-that-re-renders-the-host).

### `multiple()` forces the JS control, and is searchable by default

Filament couples `multiple()` to its JS control — chips, a remove affordance per value, `maxItems()` — and the port does the same: [`isNative`](can-be-native.md#a-select-is-native-only-when-nothing-forces-the-js-control) is false for every multiple select, and [`Select::isSearchable()`](can-be-searchable.md#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) defaults to `isMultiple()`.
The multi-value parts — the cast swap, the `multiple` attribute, the multi-value `Choice`, the selected state — are carried by the JS branch's hidden `<select>`.
Filament's `relationship()` also makes a multiple select non-dehydrated; that arm is not ported, because it exists for the relationship-saving path rather than for multi-value state, and that path works the other way round here ([has-relationship.md](has-relationship.md#the-save-arm-writes-a-desired-list-not-attachdetach)).
The one consequence worth saying out loud is the relationship one ([has-relationship.md](has-relationship.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design)): a `multiple()` relationship select that calls neither `searchable()` nor `preload()` enumerates no options until the user types.

### The option actions are suffix actions with Doctrine defaults

`setUp()` registers two closures on the field's own suffix slot — `suffixActions([fn ($c) => $c->getCreateOptionAction(), fn ($c) => $c->getEditOptionAction()])` — and each getter answers `null` until its form is declared, which [`wrap(null)`](../support/helpers.md) drops on the floor.
So every select carries the registration and none of the chrome: no form, no action; `disabled()`, no action either way; `multiple()`, no edit action, because a list has no single record to edit.
Because the suffix cache [splices into the component's action map](../schemas/has-actions.md#the-affix-contract-is-typed-as-a-name-keyed-map), a trigger reaches them by the field's own dotted key (`context.schemaComponent = 'schema.author'`) with no pipeline of its own, and the modal schema lands on `mountedActionsData.<n>` like any other mounted action's.

The chain each getter builds is Filament's, in Filament's order, with two things set explicitly that the cache does not supply: `color('gray')` and `iconButton()`.
The [cache's two defaults](has-affixes.md#the-cache-contributes-two-defaults-and-nothing-else) are the small size and the icon-button *template*, and neither is a colour — an affix action that wants gray says so.

Both getters close through `applyOptionActionModifiers()`, which runs the manage-level modifier and then the action's own, each replacing the action only when it returns one — Filament's two `?? $action` guards folded into one loop.
The modifiers go through [`evaluate()`](../../../packages/support/src/Concerns/EvaluatesClosures.php), so a closure can ask for `Select $component` alongside `Action $action` exactly as Filament's can, and a modifier returning something that is neither an `Action` nor `null` raises a named `TypeError` the way schemas' [`CanModifyActions`](../../../packages/schemas/src/Concerns/CanModifyActions.php) does, rather than surfacing as a return-type failure one frame out.
The callback parameter is typed `?Closure` and the narrowing happens on the **result**, because `evaluate()`'s generic reads T off the value union — a `Closure`-only argument would make it infer `Closure`, which is why the pair of Filament's `?? $action` guards could not simply be kept as they were written.

`relationship()` is overridden here rather than widened in the shared trait: [`CheckboxList`](checkbox-list.md#the-to-many-relationship-arm-serves-this-field-unchanged) composes the same trait and has no option actions, so the override aliases the trait body in (`use HasRelationship { relationship as traitRelationship; }`), calls it, installs the [four search and label arms](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query), then three Doctrine defaults:

- **`createOptionUsing`** — `Schema::getData($data)` builds the target entity from the modal schema's class-string record (through `PropertyAccessor::constructRecord()`, so a target whose constructor requires arguments is refused by name) and maps the option action's data onto it: the state the host validated, after the action's `mutateDataUsing()`.
  The reader `persist()`s and `flush()`es it, and the key it returns is `getRecordKey()`.
  The modal schema validates itself and nothing else: its state path scopes the map, so the outer form is untouched.
  The refusal advises handing the schema a record built through `record()`, which an option form cannot follow — the arguments are the data its modal has yet to collect — so for such a target a `createOptionUsing()` of the application's own, declared after `relationship()`, replaces this step and is the way out.
- **`fillEditOptionActionFormUsing`** — the selected record itself, as an object.
  `Schema::fill(object)` runs `readStateFromRecord()`, so the modal's fields arrive filled without a hand-built array.
- **`updateOptionUsing`** — `Schema::getData($data)` maps the option action's data back onto the bound instance and the reader `flush()`es it.

Both write defaults read that data off the option action, which the action's closure hands them by name as `action`, beside Filament's `data`, `form` and `schema`.
Both first refuse, by name, a path the action's `mutateDataUsing()` added that no component of the option form maps ([why](#a-key-an-option-actions-mutation-adds-that-no-component-maps-is-refused)).
Calling `getData()` with no state would re-read the modal's own state and discard a `mutateDataUsing()` on either option action whole; Filament's defaults write `$data`.
Pinned by [`SelectOptionDataTest`](../../../packages/forms/tests/Integration/Components/SelectOptionDataTest.php).
Because the defaults do not re-run `getState()`, the option form validates once per submit, and so does every `unique()`/`exists()` probe it declares.

Both write defaults run `getData()`, so both end in the record check and in its two key rules.
A created option on a key a record already holds is *"This value is already used."* under the option form's own field, with nothing written ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)); an edit-option save that changes the selected record's key is *"This value cannot be changed."*, the key put back and the select still naming the same record ([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)) — and the field's to-one relationship arm, writing a different reference onto an identifier association of an existing record, is refused the same way.
The key `createOptionUsing` hands back is minted after the flush as the new row would read it back ([why](../schemas/record-key.md#a-key-is-minted-from-the-value-its-row-would-read-back)); minted from what was typed, a key type that rewrites it — `new-1` read back as `NEW-1` — would answer a key the option list does not hold, so the created option would not be selected and the parent's save would refuse it.
Pinned by [`SelectKeyWritesTest`](../../../packages/forms/tests/Integration/Components/SelectKeyWritesTest.php).

All three are installed **unconditionally**, exactly as Filament does it, so a `createOptionUsing()` of your own has to be declared **after** `relationship()` to survive.
That is a real ordering trap and it is pinned in both directions rather than smoothed over — a conditional install would silently change which closure wins for anyone carrying the Filament habit.
The four search and label installs — `getSearchResultsUsing`, `getOptionLabelUsing`, `getSelectedRecordUsing`, `getOptionLabelsUsing` — follow the same rule and the same precedent, and are pinned the same way: a `getOptionLabelUsing()` declared before `relationship()` is overwritten, one declared after wins.

Both write arms end by calling `forgetCachedRelationshipOptions()`, which only clears the [memoized option map](has-relationship.md#relationship-is-three-lazy-closures-over-the-association-reader).
Both option actions' own closures call it too, the create one right after the state write and its hooks, and that call is the load-bearing one: a user-supplied `createOptionUsing` that inserts a row knows nothing about the memo, and without the bust the freshly created key is validated against the map built before the insert and rejected by the `Choice`.
The one on the relationship default is belt and braces for a `createOptionUsing` that is replaced wholesale later in the chain.

`getSelectedRecord()` is nothing but `evaluate()` over `getSelectedRecordUsing()` — Filament's shape, and the seam the port keeps — memoized on the field for as long as the selection stays the same, null for a blank selection or when no resolver is declared, so a **non-relationship** select with an `editOptionForm()` binds a record to its modal by declaring the resolver.
The default `relationship()` installs is a **query**, not a reference: the [scoped option query](has-relationship.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query) narrowed by [`IdentifierConstraint::equals()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under the base `sylament_selected` — one predicate per identifier field, and `record.id = :sylament_selected` for a single-`id` target — executed with `getOneOrNullResult()`.
A state the codec cannot decode answers `null` **without querying**, since the predicate is `null` and nothing is bound, which is what keeps a forged option value a validation error rather than a 500.
A query rather than a [`getReference()`](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route) proxy has two consequences: a key whose row was deleted concurrently — or tampered with on the wire — answers `null` where a proxy would raise `EntityNotFoundException` late, so the edit modal mounts empty as Filament's does; and a key outside `modifyQueryUsing`'s scope answers `null` too, which is what makes the same closure the [validation probe's](#the-choice-constraint-is-the-fields-only-server-side-guard) record source — a selection the scope does not admit is not a legal option.
A non-scalar state (a `multiple()` list) answers `null` from the default rather than binding an array to the identifier.
The memo is keyed on the selection because it is consulted at two points in a request where the state may differ — the render before an option action and the validation after it — and Filament's unconditional memo would answer the first selection's record for the second.

### The inline-prefix class counts visible actions where the text input's counts all of them

`sy-fo-select-has-inline-prefix` strips the `<select>`'s left padding so an inline prefix can occupy it, and the template computes it from the **visibility-filtered** prefix actions ([`Select::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Select.php) filters before deriving `$hasPrefix`).
The text input and the picker family compute their `sy-fo-input-has-inline-prefix` from the **unfiltered** arrays instead, which is Filament's own asymmetry and is kept: a select whose only prefix action is hidden keeps its padding and renders no gap, while a text input in the same state keeps `ps-0` and the wrapper's own padding carries the space.
There is no suffix twin here — the `<select>` keeps its `pe-8` for the chevron whatever the suffix side holds, so suffix actions (which is where `createOption` / `editOption` live) never touch the control's padding.

### The dropdown flips and escapes the modal

Positioning is Filament's, ported verbatim: `computePosition()` with `offset(4)`, `shift({padding: 5})` and `flip()` — so the list opens above the control when there is no room below and slides to stay inside the viewport — unless `position()` pins `'top'` or `'bottom'`, which drops `flip()` and takes the pinned placement.
The strategy is `absolute` by default and `fixed` when the button sits under a `sy-fixed-positioning-context` ancestor with no nearer `sy-absolute-positioning-context` (Filament's two opt-in classes, prefix-renamed).

One thing is faithful-plus rather than faithful: support's modal stamps `sy-fixed-positioning-context` on its `<dialog>` ([modal.md](../support/modal.md#the-modal-is-a-fixed-positioning-context)), where Filament stamps the *absolute* context on its own, so a select inside a scrolling modal window — sticky header/footer panes, slide-overs — escapes that window's `overflow-y: auto` where Filament's dropdown is clipped by it.

### `reorderable()` drags the chips through SortableJS and writes the order back

`reorderable()` rides in with the same engine: the selected chips are dragged through SortableJS inside it, and `onEnd` rebuilds the state from the badges' `[data-value]` order and reports it through the same state-change callback a click does, so the reorder is one live write like any other.
Because the order is then part of the value, the controller's re-sync compares a reorderable multiple selection against the server's as an **ordered list**, and a server render carrying the same values in another order re-syncs the chips; a non-reorderable multiple compares as a multiset, so an unrelated render does not rebuild its chips over a mere reordering.

### The hidden select lends its id to the combobox button

Filament gives the field's id to its `button[role="combobox"]`, so the field's `<label for>` focuses the control.
Ours does the same at runtime: the controller reads `element.id`, removes the attribute from the hidden `<select>` at construction — a tracked attribute change the host's `ExternalMutationTracker` re-applies after every morph — and restores it on disconnect; the engine sets it on the button.
The server-side contract keeps `id` on the `<select>`, which the render tests assert; the handoff is runtime-only and invisible to PHP.
The button also mirrors the `<select>`'s `aria-invalid` and `aria-required` — at construction and after every host render — because the hidden `<select>` is `display: none` and out of the accessibility tree, while Filament's own button carries neither; the JS branch renders `aria-required` on the `<select>` for exactly that mirror, where the native branch renders `required`.

### A created option whose identifier is incomplete is refused before it is written

The Doctrine `createOptionUsing` default mints the new record's key *after* it persists and flushes, because a generated identifier does not exist until the insert.
Where the identifier is **assigned** — which is every composite key, since Doctrine refuses an id generator on one — that order commits a row first and then fails minting its key, leaving a record no option can ever name and a create modal that has already written it.
So the default asks [`AssociationReader::hasIncompleteAssignedIdentifier()`](../schemas/association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) **before** `persist()`, and [`hasBlankAssignedIdentifier()`](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created) again **after** it and before the flush, and refuses by name:

> `The [%s] select created a [%s] whose identifier is incomplete, so no option could name it. Put every identifier field in createOptionForm().`

When the option form's schema turns the key rules off with [`validateRecordKey(false)`](../schemas/can-be-validated.md), a blank key field the form maps is not refused under that field and reaches this check instead.
The bare advice would then be false for a field already in the form, so the message gains one sentence after it when the rules are off: *The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.*
With the rules on, the message is the one above, byte for byte.
`SelectOptionDataTest` pins both forms.
The switch lifts the key pass alone: a key field over a non-nullable property is still [required by the record](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types), so it reaches this check blank only when it is not required — it declares `required(false)` (over a non-nullable property its blank must also survive dehydration as a string, as the test's whitespace does, since a null cannot be written there), or its property is nullable.
What reaches it then depends on what the blank reaches the record as.
A blank that reaches it as a **string** — whitespace — meets this check and the message above whatever the class declares, since the check after `persist()` reads it too.
An empty field over a nullable property reaches it as `null`: the check before `persist()` refuses that with the same message for a class with no `prePersist` callback or entity listener, and for one that declares either the check short-circuits, so `persist()` itself refuses the null with Doctrine's `EntityMissingAssignedId` — the post-persist read is never reached ([why](../schemas/association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created)).

It is a developer error rather than validation — the fix is a missing field in `createOptionForm()`, not something an end user can supply — so nothing is written and nothing is restored; a record refused after `persist()` is detached first.
Three shapes pass.
A **pending generated** identifier does, the record's own or one behind an identifier association: that is Doctrine's to fill at the flush two lines later.
So does an identifier a `#[PrePersist]` callback of the class or a mapped entity listener assigns — the check after `persist()` reads it filled.
A callback or listener that fills **no** key half exempts the class from the first check, so the second one is what refuses a half it left blank; without it that half would be flushed, and minting the new option's key would then fail on the row already written.
And so does a **plain single scalar** identifier, always: the member answers `false` for one before it reads anything, because the relation layer keys such a class with no check, and a create option over one needs none.
Validating a key that is *taken*, as opposed to one that is incomplete, is the record check's, which this default reaches through `getData()` ([above](#the-option-actions-are-suffix-actions-with-doctrine-defaults)).
An assigned single scalar key left blank still passes both: the taken-key rule treats a blank component as incomplete and looks nothing up, so it reaches Doctrine unchecked.
Pinned by [`SelectCompositeRelationshipTest`](../../../packages/forms/tests/Integration/Components/SelectCompositeRelationshipTest.php), the after-`persist()` arm over a `Planter` whose callback fills neither key half.

## Divergences

### `native(false)` switches to the JS control

Filament's `native(false)` switches `Select` from a real `<select>` to its own first-party JavaScript control — `support/resources/js/utilities/select.js`, some 2 400 lines on `@floating-ui/dom` + `sortablejs`.
The port switches to **that same control**, ported to TypeScript as `packages/forms/assets/src/utils/select.ts` and driven by the thin first-party Stimulus controller `sylament--forms--select`, both bundled into forms' `dist` with `@floating-ui/dom` and `sortablejs`.

A thin integration around **Tom Select** is the alternative to porting the control, rejected for the friction it carries, every item of it the cost of an engine that does not share the server's contract: an own delete button rendered beside Tom Select's, the dropdown settled by hand, a membership score written to keep the server's option order, `$order` and `$id` kept in sync per option, a duplicate `<option selected>` for the value the map did not carry, the selected label re-targeted after a refresh, and the local filter's throttle zeroed.
The port's costs (a 2 400-line hand-port with no JS test rig — the engine's verification is a method-by-method vendor diff plus the browser pass) were accepted against exact DOM/CSS parity, `reorderable()`, Floating UI positioning and one shared vocabulary with Filament.
The controller, its values and its CSS are the template's and the stylesheet's trail ([forms-bundle.md](forms-bundle.md), [styles.md](styles.md)); this file carries what the PHP side promised it and where that differs from Filament:

- **The server render is the option and selection truth.**
  Filament's control fetches `getOptionsForJs` on every open when `hasDynamicOptions()` and probes labels lazily.
  Ours renders `optionsForJs` and `selectedOptions` into the markup on every render, and every live round-trip re-renders, so a "fresh options at open" round-trip buys nothing; the JS asks the server for search results only.
  Dependent options (`options(fn (Get $get) => …)`) therefore follow the last render, so the dependency must be `live()` — which Filament's own dependent-select docs require anyway.
  The four exposed methods are ported for API parity; only `getSearchResultsForJs()` is called by our JS.
- **In the JS branch the hidden `<select>` carries no `data-model`.**
  UX Live's `SetValueOntoModelFieldsPlugin` writes a single `<select data-model>`'s DOM value back into the value store after every render, so an option the user just searched in — which the in-flight render did not know about — would reset the model to `""` silently.
  The controller owns the binding instead, calling `component.set()` itself from the `statePath` and `modelBinding` values the template hands it; the native branch keeps `data-model` unchanged.
- **The dropdown flips and escapes the modal** ([semantics](#the-dropdown-flips-and-escapes-the-modal)).
- **The JS-branch `<select>` carries no `required`.**
  Filament's JS branch renders no native constraint attribute at all — it renders no `<select>`.
  Ours keeps a hidden one as the carrier of the Stimulus values and the no-JS fallback, and `required` is the one attribute with a native effect on a hidden control: Enter in the open dropdown with no focused option submits through `form.requestSubmit()`, which runs interactive constraint validation, and a hidden `<select required>` sitting on the placeholder is invalid *and* unfocusable — the submit is blocked with a console error and no visible message, where the server-side required message is the contract.
  The template emits `required` in the native branch only; everything else the hidden `<select>` carries stays, because the engine reads it (`multiple`, `disabled`, `autofocus`, `id`) or a test pins it (`name`, `class`, `aria-invalid`).

### The engine port corrects defects the vendor engine carries

The port is method-for-method vendor's except where `support/resources/js/utilities/select.js` has a bug; each item names the vendor method with its line in the installed 5.9.0 file, then what the port does instead:

1. `destroy()` (2387) leaves a positioning frame queued by `deferPositionDropdown()` to run against the removed container.
   Ours cancels it there.
2. `handleDropdownKeydown()`'s Enter with no focused option calls `form.submit()` (1332), which fires no `submit` event; under a `live_action('save:prevent', event: 'submit')` form that navigates away instead of saving.
   Ours calls `form.requestSubmit()`.
3. `updateOptionLabelInList()`'s plain-text arm appends a bare text node (1176), dropping the `<span>` its own stylesheet's `.fi-select-input-option > span` arm selects, so a refreshed label loses the option row's truncation.
   Ours keeps the `span.sy-fo-select-option-label` in both arms.
4. `selectOption()` finds the badge to remove with `querySelector(\`[data-value="${value}"]\`)` (2129), the value interpolated raw — a value holding a double quote or a backslash throws a `SyntaxError` before the state changes, so that chip can never be deselected.
   Ours passes the value through `CSS.escape()`.
5. `handleSearch()`'s empty-query branch (1834) restores the original options but leaves `activeSearchId` alone, so a dynamic search already in flight still commits its results over the emptied box when it lands, and the stale list stays until the dropdown is closed and reopened.
   Ours bumps the id and clears the searching flag there, the way `closeDropdown()` does.

### The search round-trip is a live action that re-renders the host

Filament's control reaches `getSearchResultsForJs()` through Livewire's `callSchemaComponentMethod`, which *returns* the value and is `#[Renderless]`.
UX LiveComponent has no return channel, so the port's [`callSchemaComponentMethod()`](../schemas/interacts-with-schemas.md) is a live action that re-renders normally and hands the result back through `dispatchBrowserEvent()` — the same channel actions' `mountAction()` and notifications use.
Every search round-trip therefore re-renders the host; the search debounce (`searchDebounce()`, Filament's default 1000 ms) bounds it, and it is the same cost every `live()` field already pays per keystroke.
The three predicates — `hasDynamicOptions()`, `hasDynamicSearchResults()`, `hasInitialNoOptionsMessage()` — are ported verbatim for the control's contract; `hasDynamicSearchResults()` is the one that decides whether typing asks the server at all, and it is false for a relationship select with no search columns.

### `loadingMessage()` has no render site

The API is ported whole (`HasLoadingMessage`, `'Loading...'`), but under the server-rendered-options rule the only load the control ever performs is a search, which shows `getSearchingMessage()`; the dynamic-options open fetch that `loadingMessage()` accompanies in Filament does not exist here.
The setter and getter stand, the string reaches the controller as a value, and nothing renders it — recorded rather than smoothed over, so the gap is visible from the API.

### `refreshSelectedOptionLabel()` dispatches a browser event the control answers by re-syncing

Filament's two option-action closures end by dispatching a **Livewire** event, `filament-forms::select.refreshSelectedOptionLabel`, carrying `livewireId` + `statePath` so the one Alpine control among many can match itself and re-fetch its selected label.
The port's closures call the same method at the same two points — the create action right after the state write and its hooks, before the create-another branch (Filament's `Select.php:272`), the edit action after `updateOptionUsing` (`:417`) — and it dispatches a **browser** event on the host's own root, `sylament:select-refresh-selected-option-label`, payload `{statePath}` alone (the id is unnecessary on the host's element).
The controller matches on `event.target === component.element` and `detail.statePath === statePathValue`, and answers by re-syncing from the values the same response re-rendered — no second label round-trip.
A channel change and a name change together, so it has a ["no Filament source" row](../translations.md#names-with-no-filament-source).
The [option-map bust](#the-option-actions-are-suffix-actions-with-doctrine-defaults) runs beside it and is what makes a re-render show a new row.

### The loading and max-items strings are untranslated literals

`Loading...` (`filament-forms::components.select.loading_message`) and `Only :count can be selected.` (`…select.max_items_message`, rendered by a plain `:count` substitution — Filament's `trans_choice()` has plural forms, the literal has none) are hardcoded English read verbatim from [`en/components.php`](../../../vendor/filament/forms/resources/lang/en/components.php), literals and not translation keys, the same call as [the option-action strings](#the-option-action-strings-are-untranslated-literals) and [`CanBeSearchable`'s four](can-be-searchable.md#the-four-message-literals-are-untranslated).
The three strings Filament's JS hardcodes (`Search`, `Remove <label>`, `Clear selection`) stay JS literals in the controller.

### The non-relationship schema record is read off the field, not off a `getActionSchemaModel()`

Filament's create modal binds `$schema->model($component->getActionSchemaModel())` for a select with no relationship, where `getActionSchemaModel()` is a schemas concern method resolving `actionSchemaModel() ?? getRecord() ?? getModel()`.
Neither the setter nor the getter is ported — schemas' component [`HasActions`](../schemas/has-actions.md) has no such surface — so the arm reads what our components do expose: `$component->getRecord() ?? $component->getRecordClass()`, which is the same "the field's own record, or the class it declares" answer minus the per-action override.
A select with no relationship needs `createOptionUsing()` anyway, and that closure receives the modal schema, so a form wanting a different record binds it there.
The setter lands if schemas grows the concern.

### A key an option action's mutation adds that no component maps is refused

Filament's create-option default builds `newModelInstance()` and `fill($data)`s it, and its edit default calls `update($data)`.
Eloquent's `fill()` drops a key the target's `$fillable` lacks in silence, or throws under `preventSilentlyDiscardingAttributes()`.
The port maps through the option form's record path map instead, which plays `$fillable`'s part, so a key no component maps could only be dropped in silence.
So both defaults refuse it.
A path the option action's `mutateDataUsing()` added, present in what the action returned but absent from the validated state it was handed ([`getUnmutatedData()`](../actions/README.md)), that no record path of the option form covers is a `LogicException` before anything is mapped:

> `The [%s] select's [%s] action added [%s] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or take the write over with %s().`

The first `%s` is the select's state path and the second the option action's name.
The paths are listed comma-separated and never with their values.
The last `%s` is `createOptionUsing` or `updateOptionUsing`.
It is the same message the resource pages and the record actions print, with the select as its owner.
Only an **addition** is refused.
A key the client sent is in the state the action was handed, so it is never refused, and it is never written either, since no component maps it.
An overwrite of a mapped key is written, and so is a value set on a path a `Hidden` field maps.
A `createOptionUsing()` or `updateOptionUsing()` of your own is never guarded, because the check lives in the defaults.
It is the rule the resource pages and the record actions apply at their own default write seams ([belongs-to-record.md](../schemas/belongs-to-record.md)).
To reach the action's snapshot, the defaults are handed the option action itself, by name, as `action`.
Filament's evaluation passes `data`, `form` and `schema` only, so a closure of your own that declares `$action` receives it only here.

### `getEditOptionActionFormData()` also answers an object

Filament types it `?array`, because Eloquent fills a form from `$record->attributesToArray()`.
The Doctrine default hands back the **record** instead — `Schema::fill(object)` runs `readStateFromRecord()`, which is the only reading that goes through the same mapped-state path `getData()` writes back through, association hooks included.
The return type is therefore `object|array<string, mixed>|null`; an array still works and is still what a hand-written `fillEditOptionActionFormUsing()` will usually return.

### The option-action strings are untranslated literals

The seven strings — create label, modal heading and submit label all `Create`, create-another `Create & create another`, edit label and modal heading `Edit`, edit submit `Save` — are hardcoded English, read verbatim from [`en/components.php`](../../../vendor/filament/forms/resources/lang/en/components.php).
Same call as [the placeholder below](#the-default-placeholder-is-an-untranslated-literal) and [`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation): the translation surface is unported toolkit-wide, so a literal is the honest form rather than a half-built lookup.
The two icons are overridable, though — they resolve through [`FormsIconAlias`](forms-icon-alias.md)'s `COMPONENTS_SELECT_ACTIONS_CREATE_OPTION` and `COMPONENTS_SELECT_ACTIONS_EDIT_OPTION`, falling back to `heroicons:plus-20-solid` and `heroicons:pencil-square-20-solid`.

Resolving them needs an `IconManager`, and Filament reaches its `FilamentIcon` facade.
A component cannot: the manager is a private service the container's `has()` does not answer for, so the closure form (`fn (IconManager $icons) => …`, which [`NavigationItem`](../panels/README.md) can use because it injects the manager itself) resolves nothing here.
`Select` therefore takes the manager through a `#[Required]` setter and falls back to a bare `new IconManager()` when it was built with `new` rather than through the container — the same shape [`CanAccessProperties`](../schemas/property-accessor.md#the-accessor-is-injected-not-reached-for) uses, and the same trade: a hand-built field resolves no registered overrides and renders the default icon.

### The default placeholder is an untranslated literal

`setUp()` sets the placeholder to the literal `'Select an option'`, and `boolean()` to `'-'`.
Filament reads `__('filament-forms::components.select.placeholder')`; the translation surface is unported, matching [`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation).

### `disablePlaceholderSelection()` is not ported

Filament keeps it as a deprecated alias for `selectablePlaceholder()`.
Porting a method that is already deprecated upstream would import a migration the toolkit never had.
