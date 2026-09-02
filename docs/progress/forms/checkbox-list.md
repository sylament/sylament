# CheckboxList (forms)

`Sylament\Forms\Components\CheckboxList` — the multi-choice option field,
rendering one native `<input type="checkbox">` per option inside an
`aria-labelledby`-pointed `role="group"` container. Extends
[`Field`](../../../packages/forms/src/Components/Field.php), casts state through
schemas'
[`OptionsArrayStateCast`](../../../packages/schemas/src/State/Casts/OptionsArrayStateCast.php),
and composes eight concerns: the four option-field ones
[`Radio`](radio.md) already carries ([`HasOptions`](has-options.md),
[`CanDisableOptions`](can-disable-options.md),
[`HasDescriptions`](has-descriptions.md),
[`HasGridDirection`](has-grid-direction.md)) plus
[`HasExtraInputAttributes`](has-extra-input-attributes.md), and four that reach
this field first or exclusively —
[`CanAllowHtml`](can-allow-html.md),
[`CanBeSearchable`](can-be-searchable.md),
[`CanLimitItemsLength`](can-limit-items-length.md) and
[`HasRelationship`](#the-to-many-relationship-arm-serves-this-field-unchanged) —
the first two shared with [`Select`](select.md) since the searchable-select epic,
each with a file of its own.

It is the field where three port-wide seams meet for the first time. It is the
only option field that is **inherently multiple**, so it is the second consumer
of the to-many relationship arm `Select::multiple()` built and the first to take
it without declaring anything. It is the first field to
[register component-level actions](#the-bulk-toggle-actions-render-but-never-dispatch)
that are deliberately inert — the click lives on the markup around them. And it
is the first field to ship a Stimulus controller, which is what
[`@sylament/forms`](#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
joined the JS workspace to carry: the template renders every option and every
target server side, and
[`checkbox_list_controller`](../../../packages/forms/assets/src/controllers/checkbox_list_controller.ts)
supplies the search filtering and the bulk toggle on top of it.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php)
  — the field class and its embedded view
- [`forms/resources/js/components/checkbox-list.js`](../../../vendor/filament/forms/resources/js/components/checkbox-list.js)
  — the Alpine component the search and bulk toggle run on
- [`forms/resources/css/components/checkbox-list.css`](../../../vendor/filament/forms/resources/css/components/checkbox-list.css)
  — the option-set chrome
- [`support/resources/css/components/input/checkbox.css`](../../../vendor/filament/support/resources/css/components/input/checkbox.css)
  — the control chrome, which vendor files a package down and the port already
  ships as `.sy-fo-checkbox`

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details) or a permanent note in
[Semantics](#semantics) / [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering — the group, the implicit option labels, the description line | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-checked-attribute-is-rendered-server-side) · [diverges](#every-checkbox-carries-the-same-data-model) · [diverges](#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair) · [diverges](#the-control-class-is-the-existing-sy-fo-checkbox) |
| The grid, and its `Column` default — applied unconditionally, no `inline()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](has-grid-direction.md#the-column-default-belongs-to-the-field-not-to-this-getter) |
| `getEnumDefaultStateCast()` — `EnumArrayStateCast` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-list-rests-at-an-array-and-that-is-load-bearing) |
| `getDefaultStateCasts()` — `[OptionsArrayStateCast]` unless custom casts or an enum | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-list-rests-at-an-array-and-that-is-load-bearing) |
| `getInValidationConstraintValues()` / `hasInValidationOnMultipleValues()` → `buildValidationConstraints()`, a `Choice(multiple: true)` over the enabled options — replaced by a user `in()` list | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-three-server-side-guards-are-ordered-so-they-never-collide) · [semantics](can-be-validated.md#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| `required()` — the standard `RequiredValue` | [`Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#required-rejects-the-empty-list-with-the-required-constraint-alone) |
| `bulkToggleable()` / `isBulkToggleable()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-bulk-toggle-actions-render-but-never-dispatch) |
| `getSelectAllAction()` / `selectAllAction()` / `getSelectAllActionName()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-bulk-toggle-actions-render-but-never-dispatch) · [diverges](#the-bulk-toggle-labels-are-untranslated-literals) |
| `getDeselectAllAction()` / `deselectAllAction()` / `getDeselectAllActionName()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-bulk-toggle-actions-render-but-never-dispatch) · [diverges](#the-bulk-toggle-labels-are-untranslated-literals) |
| `relationship()` / `getRelationshipName()` / `getRelationshipTitleAttribute()` — via [`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php) | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [detail](#the-to-many-relationship-arm-serves-this-field-unchanged) · [diverges](#the-relationship-state-is-not-pruned-from-the-dehydrated-bag) |
| `getRelationship()` — the association the mapping declares | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [detail](#the-to-many-relationship-arm-serves-this-field-unchanged) |
| `fillStateFromRelationship()` / `saveStateToRelationship()` — the load and save hooks | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](select.md#the-save-arm-writes-a-desired-list-not-attachdetach) |
| `getOptionLabelFromRecordUsing()` / `hasOptionLabelFromRecordUsingCallback()` / `getOptionLabelFromRecord()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [detail](#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query) |
| `getOptionDescriptionFromRecordUsing()` / `hasOptionDescriptionFromRecordUsingCallback()` / `getOptionDescriptionFromRecord()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [detail](#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query) |
| `getLabel()` — derived from the relationship name when unset | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [diverges](#the-relationship-label-is-a-getdefaultlabel-override) |
| `isMultiple()` — port plumbing, no Filament source | — | done | — | [diverges](#ismultiple-is-port-plumbing-with-no-filament-counterpart) · [renames](../renames.md#names-with-no-filament-source) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`HasDescriptions`](../../../packages/forms/src/Components/Concerns/HasDescriptions.php) | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [detail](has-descriptions.md) |
| [`HasGridDirection`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php) | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | [detail](has-grid-direction.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| [`CanAllowHtml`](can-allow-html.md) | [`Concerns/CanAllowHtml.php`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php) | done | — | [detail](can-allow-html.md#the-switch-opens-exactly-one-hole-per-field) |
| [`CanBeSearchable`](can-be-searchable.md) — the markup, the debounce override and the client-side filter | [`Concerns/CanBeSearchable.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeSearchable.php) | done | — | [detail](can-be-searchable.md#the-checkbox-list-search-is-client-side-only-and-mostly-select-only) · [diverges](#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) |
| [`CanLimitItemsLength`](../../../packages/forms/src/Components/Concerns/CanLimitItemsLength.php) — `minItems()` / `maxItems()` as a `Count` | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | [detail](can-limit-items-length.md) · [semantics](#the-three-server-side-guards-are-ordered-so-they-never-collide) |
| The search field's magnifying-glass icon and its [input wrapper](../support/input-wrapper.md) prefix slot | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | done | — | [semantics](#the-search-box-is-the-one-wrapper-a-template-opens-by-hand) |
| `HasPivotData` — `pivotData()` on the save arm | [`Concerns/HasPivotData.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPivotData.php) | skipped | — | [diverges](select.md#the-save-arm-writes-a-desired-list-not-attachdetach) |
| [`HasNestedRecursiveValidationConstraints` + its contract](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) — the per-element constraints | [`Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [diverges](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path) |
| `CanFixIndistinctState` | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [detail](can-fix-indistinct-state.md) |
| `CanDisableOptionsWhenSelectedInSiblingRepeaterItems` | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [detail](can-disable-options-when-selected-in-sibling-repeater-items.md) |
| `getPublishedViewOverrideCheckPath()` | [`Components/CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php) | skipped | — | [semantics](toggle-buttons.md#there-is-nothing-to-publish-a-view-override-against) |

Coverage: one checkbox per option with per-option ids and values and no shared
`name`; the `data-model` every input shares; `checked` on every member of the
array state and on no other, including the loose `'1'`-against-`1` case and an
unset state against a `0`-keyed option; the enum arms (labels from `HasLabel`,
descriptions from `HasDescription`); explicit descriptions; `allowHtml()`
rendering a `<b>` label raw while the same markup in a description stays
escaped, and both escaped without it; per-option and whole-field `disabled`; the
default column grid and `columns(2)`; `aria-invalid` in both arms; extra input
attributes reaching every input and outranking the field's own; the searchable
branch (controller, debounce value, search input with no `name`/`data-model`,
per-option targets, the hidden `role="status"` node, custom prompt and message)
and its absence by default and when disabled; the bulk-toggle branch (both
actions rendered as small links inside targeted spans carrying the click, the
`hidden` deselect span, **no** `data-action="live#action"` and no live-action
parameters on either link, the empty-options and disabled short-circuits, and
the two `…Action()` modifiers); `getSearchDebounce()` forced to `0` and still
overridable; the cast chain in both arms and the resting `[]`; the
`Choice(multiple: true)` including the disabled filter, the non-array `Type`
arm, the blank short-circuit, the
[filled gate](select.md#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out)
rejecting an element the enum cast drops (driven end to end through a host, so
the tampered element is the field's *own* raw state) and the no-options
`LogicException`; the `Count`
from `minItems()`/`maxItems()` including the empty-selection case; and
`required()` rejecting `[]` — by
[`CheckboxListTest`](../../../packages/forms/tests/Integration/Components/CheckboxListTest.php).
The relationship arm runs against the real Doctrine rig — option enumeration,
`fill()` from the association, the desired-list write through `getData()`, the
empty submission, `modifyQueryUsing`, the to-one refusal, the missing
`titleAttribute` refusal, the derived label, the two record-shaped closures, a
`Markup` record label reaching the markup unflattened, and the un-pruned
dehydration — in
[`CheckboxListRelationshipTest`](../../../packages/forms/tests/Integration/Components/CheckboxListRelationshipTest.php).
The binding mechanism itself needs a live host, so it is driven through a real
round-trip by
[`LiveCheckboxListTest`](../../../packages/forms/tests/Integration/LiveCheckboxListTest.php)
against the
[`CheckboxListForm`](../../../packages/forms/tests/Fixtures/Twig/Components/CheckboxListForm.php)
fixture, which asserts the resting `[]` on the wire before it asserts anything
about checking.

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### The search box is the one wrapper a template opens by hand

Every other control gets its [input wrapper](../support/input-wrapper.md) from
its own field template, driven by [`HasAffixes`](has-affixes.md). This one is not
a field: it is a search box **inside** a field, so the template opens
`<twig:SylamentSupport:InputWrapper>` itself with a literal
`heroicons:magnifying-glass-20-solid` prefix icon and `inlinePrefix` — vendor
hand-builds exactly the same thing, and the class
`sy-fo-checkbox-list-search-input-wrp` (which carries the `mb-4` and nothing
else) rides through as a passthrough attribute the wrapper's root appends.

That is what brings
[`FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD`](../../../packages/forms/src/Twig/FormsIconAlias.php)
to life — the constant shipped before its call site did, so an application could
already override an icon nothing rendered. Tables' search field is the same
shape one package over.

### The search box and the HTML switch are shared concerns now

What this field does with `CanBeSearchable` — a client-side filter over the
options it already rendered, `searchDebounce(0)` forced in `setUp()`, the
label/value switches and two of the four messages left unread — moved to
[can-be-searchable.md](can-be-searchable.md#the-checkbox-list-search-is-client-side-only-and-mostly-select-only)
when `Select` became the trait's second consumer, and what `allowHtml()` opens
here — the option label and nothing else, the description beside it still
escaped — to
[can-allow-html.md](can-allow-html.md#the-switch-opens-exactly-one-hole-per-field).
Both files keep this field's clauses word for word; only the fold moved.

### The to-many relationship arm serves this field unchanged

`relationship()` is not reimplemented: this field composes the same
[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php)
`Select` does, and gets the whole three-closure arm
([how it works](select.md#relationship-is-three-lazy-closures-over-the-association-reader))
unchanged — the option query, the memo, the query scope, the `fill()` read and
the desired-list write.

Two things made that possible, and both are recorded rather than assumed:

- The trait's three injected-component closures were typed `Select`. They are
  now typed **`self`**, which inside a trait resolves to the composing class, so
  a `CheckboxList` closure receives a `CheckboxList`. The
  [`Invoker`](../support/component.md#closure-resolution-lives-in-a-service)
  normalizes a `self` hint against the declaring class when it resolves the
  injection, so nothing else changed —
  `Select`'s whole relationship suite is untouched and green, which is what pins
  the widening.
- The multiplicity match the trait runs on every read asks the field
  [`isMultiple()`](#ismultiple-is-port-plumbing-with-no-filament-counterpart).
  A `CheckboxList` answers a constant `true`, so pointing it at a to-one
  association fails with the trait's existing refusal ("holds a single …") and
  the to-many arm is the only one it can take.

`getOptionsFromRelationship()` is the one method this field overrides, and only
for [the record-shaped closures](#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query);
with neither declared it delegates straight back to the trait's title-attribute
build, aliased in as `getTitleAttributeOptionsFromRelationship()`. The query
itself is shared: the trait grew a `getRecordsFromRelationship()` seam that both
paths read, so there is one place the option query is built and scoped.

### The two record-shaped closures build labels and descriptions off one query

`getOptionLabelFromRecordUsing()` replaces the title attribute as the source of
each option's label, and `getOptionDescriptionFromRecordUsing()` adds a
description per option. Declaring **either** switches the option build onto the
record path, and the descriptions are installed as a **side effect** of it — the
component calls its own `descriptions()` while building the map, exactly as
vendor does (`CheckboxList.php:169-177`), because both maps come off the one
query and running it twice to keep them separate would be worse.

The label trio — `getOptionLabelFromRecordUsing()`,
`hasOptionLabelFromRecordUsingCallback()`, `getOptionLabelFromRecord()` — no
longer lives on this class: it moved into the shared
[`HasRelationship`](../../../packages/forms/src/Components/Concerns/HasRelationship.php)
trait unchanged when [`Select`](select.md#the-relationship-search-arm-runs-a-like-per-column-over-the-scoped-option-query)
grew the same callback for its search and label arms. Filament duplicates the
three on both fields because it has no shared trait; the port has one, so both
fields carry one copy, and the trait's own option build reads the callback too
(`resolveOptionLabelFromRecord()`, callback first, title attribute otherwise).
This field's override is untouched — it still branches ahead of the query for
the description side effect. The description trio stays here: `Select` has no
descriptions.

Both closures receive the record as `$record` by name and by its concrete class,
so `fn (Tag $record) => …` and `fn (object $record) => …` both work.  Filament
types the parameter `Model` and injects `Model::class` too; the port has no such
base type — a Doctrine entity is a plain object — so the
[record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record)
applies and the signatures take `object`.

Two small deltas from vendor, both in the direction of less surprise: a
description closure returning `null` (or `''`) leaves that option **without** a
description rather than with an empty `<p>`, matching how
[`HasDescriptions`](has-descriptions.md) already treats an enum case that
describes itself as null; and a label closure returning nothing yields `''`, the
same coercion the title-attribute arm makes for an unreadable title. Declaring
either closure clears the option memo, so the two setters compose in any order.

### The list rests at an array, and that is load-bearing

`getDefaultStateCasts()` answers `[OptionsArrayStateCast()]` — the constructor
default, `isNullable: false` — unless the field has custom casts or an
[enum](has-enum.md), in which case `Field`'s chain contributes
`EnumArrayStateCast` instead. `Select::multiple()` spells the same argument out
explicitly and `ToggleButtons`' multiple arm leaves it defaulted; this field
follows `ToggleButtons`, and the value is pinned by a test rather than by the
call site.

It is worth pinning because the resting shape is what makes the binding work at
all. `live_controller`'s `getValueFromElement()` reads the model's **current**
value first and only treats a checkbox as add-or-remove-against-a-list when it
finds an array there; a path resting at `null` would take the clicked option's
bare `value` and collapse the whole list to a scalar. `isNullable: false` writes
`[]` rather than null, so the first case in
[`LiveCheckboxListTest`](../../../packages/forms/tests/Integration/LiveCheckboxListTest.php)
asserts that resting shape on the wire before it asserts anything about
checking — the same discipline `LiveToggleButtonsTest` pins for its multiple
arm.

### The three server-side guards are ordered so they never collide

`buildValidationConstraints()` appends three things in one order, and the order
is the whole content of the method:

1. **A non-array raw state is failed by `Type('array')` alone**, and the method
   returns. `Choice(multiple: true)` is undefined over anything else — its
   validator throws rather than failing — and
   [`CountValidator`](../../../vendor/symfony/validator/Constraints/CountValidator.php)
   throws an `UnexpectedValueException` on a non-countable value, so neither may
   be allowed to meet a tampered scalar. A `null` raw state is not "not an
   array" for this purpose: it is an unset field, which both validators skip on
   their own.
2. **The `Count` from `minItems()` / `maxItems()`** — before the blank
   short-circuit, deliberately, because `minItems(2)` has to fail an empty
   selection and `blank([])` is true. `Select` and `ToggleButtons` have no such
   constraint, so this is the one place the port's shared multiple-arm shape
   grows a step.
3. **The `Choice(multiple: true)`** over
   [`getEnabledOptionValues()`](can-disable-options.md), reached only once the
   payload left something there — which is a question about the
   [raw state](select.md#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out),
   since `EnumArrayStateCast` drops every element matching no case and a cast
   reading would hand a wholly tampered list back as an empty selection and skip
   the check that rejects it. The same
   [membership guard](select.md#the-choice-constraint-is-the-fields-only-server-side-guard)
   every option field builds, and the same
   [no-options `LogicException`](select.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design)
   when the field can enumerate nothing.

Filament reaches the same three answers through a different mechanism —
`hasInValidationOnMultipleValues(): true` moves its `In` rule onto
`"{$statePath}.*"`, and `minItems`/`maxItems` become `array|min:|max:` rules —
which the port has no wildcard path for
([why](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path));
`Choice(multiple: true)` validates element by element natively, so the
per-element check lands on the one path without one.

### required() rejects the empty list with the required constraint alone

`required()` builds the default
[`RequiredValue`](can-be-validated.md#required-carries-laravels-blank-set-not-symfonys)
and this field overrides `getRequiredConstraint()` nowhere. That is enough: the
constraint is one call to [`filled()`](../support/helpers.md), which counts an
empty array blank, so `[]` fails and any non-empty selection passes.

`Count(min: 1)` would say the same thing with a worse message and a second way
to spell one rule, so it is not what `required()` builds — the split
[`Checkbox`](checkbox.md#required-on-a-checkbox-means-must-be-checked) and
[`Toggle`](toggle.md#required-means-presence-not-truth) argued over does not
reach this field, because a list has no truthiness question: "blank" and
"nothing selected" are already the same value. `minItems()` remains the way to
require *more than one*, and it composes with `required()` rather than replacing
it.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### The `checked` attribute is rendered server-side

Filament emits only `wire:model` on each input and lets Livewire set the checked
property client-side once it boots. The port renders `checked` from
[`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template),
so a server-rendered edit page shows the stored selection before any JavaScript
runs. Same addition [`Radio`](radio.md#the-checked-attribute-is-rendered-server-side),
[`ToggleButtons`](toggle-buttons.md) and
[`Select`](select.md#the-template-renders-the-selected-state-itself) make.

The comparison is `state is iterable and value in state` — Twig's `in` is loose
for scalars, deliberately, because an option key may be an `int` where the state
holds the `'1'` a browser submitted. The `is iterable` guard is what a
non-array state falls into: a tampered scalar checks nothing rather than
matching an option by accident, and the strict
[`Choice`](#the-three-server-side-guards-are-ordered-so-they-never-collide) is
what admits or rejects it.

It also means the bulk-toggle's "are they all checked?" question is answerable
from the DOM on the controller's first frame. Vendor defers that read to
`$nextTick` precisely because Livewire has not hydrated the checkboxes yet when
the Alpine component initialises; here they are already correct in the HTML.
The template spends that immediacy: it hardcodes the two action spans' initial
visibility (select-all shown, deselect-all `hidden`), so a list rendered fully
selected paints the wrong link for one frame, and the controller's
`connect()`-time refresh — the same pass that runs on every search keystroke and
every option change — is what reconciles it against the real state.

### Every checkbox carries the same `data-model`

One `data-model` per input, all naming the field's one state path — the
translation of Filament's one `wire:model` per input, and the same shape
[`Radio`](radio.md#every-radio-carries-the-same-data-model) and
`ToggleButtons`' multiple arm use. It reads like a multi-binding and is not:
`live_controller`'s `getValueFromElement()` has a checkbox branch that
adds-or-removes the clicked input's `value` against the list already at that
path, so the shared path receives the whole selection and the client sends a
list rather than a delta.

Unlike `Radio`, the inputs carry **no `name`** — a checkbox group is not grouped
by `name` in the browser, and a shared one would submit a scalar on a native
form post. They do carry per-option `id`s, as `Radio`'s do, which is what
idiomorph matches on when a re-render replaces the list.

### The option inputs carry `aria-invalid` instead of a `fi-valid` / `fi-invalid` class pair

Filament puts `fi-valid` or `fi-invalid` on every checkbox input. The port emits
neither and writes `aria-invalid` on each input instead, with
[`checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css)
keying its danger arm off `[aria-invalid='true']`.

This is not a CheckboxList decision — it is the translation
[`Radio`](radio.md#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair)
already records, applied to the field that shares `Checkbox`'s control, and it
holds for the same reasons.

### The control class is the existing `.sy-fo-checkbox`

Vendor splits this field's chrome across two packages: `fi-fo-checkbox-list*`
for the option set in `forms/resources/css/components/checkbox-list.css`, and a
package-code-less `fi-checkbox-input` for the control itself in
`support/resources/css/components/input/checkbox.css`. The port already ships
that control as **`.sy-fo-checkbox`** — [`Checkbox`](checkbox.md) emits it and
[`checkbox.css`](../../../packages/support/assets/src/styles/components/checkbox.css)
styles it — so this field reuses it rather than minting a second name for the
identical control, and
[`checkbox-list.css`](../../../packages/forms/assets/src/styles/components/checkbox-list.css)
ports only vendor's own `fi-fo-checkbox-list*` rules, spacing included.

That is the rule
[`styles.md`](styles.md#the-control-stylesheets-carry-forms-coded-names-for-support-tier-classes)
records for the five controls that already carry forms-coded names, and the case
[`Radio`](radio.md#the-control-class-is-sy-fo-radio-input-one-tier-up-from-vendors)
anticipated one task earlier. It reverses the same way: the moment a second
package renders a checkbox, the rule moves down to support and loses the code.

### The bulk-toggle actions render but never dispatch

Filament builds both actions with `->livewireClickHandlerEnabled(false)` and
wraps each in `<span x-on:click="toggleAllCheckboxes()">`, so the rendered
`<a class="fi-link">` carries no handler of its own and the span supplies the
only behaviour. The `Action` objects exist so `label()`, `size()`, `icon()`,
`color()` and the user's `selectAllAction(fn (Action $action) => …)` all work.

The port reproduces that mode exactly, and reproducing it needed the switch
itself. Our action partials computed the equivalent guard inline
(`{% set hasLiveClickBinding = not component.getUrl and not component.canSubmitForm %}`),
which is precisely the fallback chain of Filament's
`isLivewireClickHandlerEnabled()` — with no way to override it, a plain `Action`
always reached the partial's final branch and emitted
`stimulus_action('live', 'action', parameters: {action: 'mountAction', …})`, a
server round-trip per click. So the guard moved into PHP where Filament keeps
it: [`HasAction`](../../../packages/actions/src/Concerns/HasAction.php) grew
`liveComponentClickHandlerEnabled()` / `isLiveComponentClickHandlerEnabled()` — the `Livewire` →
`LiveComponent` [idiom translation](../renames.md#idiom), not a rename — with Filament's
own precedence (an explicit value wins; otherwise a URL or a form submission
turns it off; otherwise on), and the four partials that had the inline
expression now read the getter. Behaviour is identical for every existing
action, which the actions, tables and panels suites pin.

The click then lives on the markup, as vendor's does: each action sits inside a
`<span>` carrying `data-sylament--forms--checkbox-list-target="selectAllWrap"`
(or `deselectAllWrap`, which starts `hidden`) and
`data-action="click->sylament--forms--checkbox-list#toggleAll"`. The assertion
that guards this is the **absence** of `data-action="live#action"` and of the
parameters it would carry on the rendered links — `data-live-action-param`
(`mountAction`) and `data-live-name-param` (the action's name), the spellings
StimulusBundle actually emits (`data-{controller}-{param}-param`) and the ones
[tables](../../../packages/tables/tests/Integration/TableRenderingTest.php) and
[panels](../../../packages/panels/tests/Integration/Resources/Pages/ListRecordsTest.php)
assert against real live wiring. The dispatch attributes are **not** the thing to
assert here: they live on a different branch of the partial and would be absent
whatever the click wiring did.

`IconButton`'s partial has a differently shaped guard and never had the inline
expression; its live-click gate takes the same getter so the new setter is not a
silent no-op there, but its event-dispatch and close branches are untouched.

### The controller is the only behaviour, and no test in this repo runs it

[`checkbox_list_controller`](../../../packages/forms/assets/src/controllers/checkbox_list_controller.ts)
ports vendor's Alpine component, which
[`@sylament/forms`](../../../packages/forms/assets/package.json) joined the JS
workspace to ship. Its filter is vendor's in shape — an empty query shows
everything, a non-empty one is a case-insensitive substring of the option's
rendered label **or** description `innerText`, never its value — and so is the
all-satisfied predicate that flips the two action spans, disabled boxes counting
as satisfied. One filter detail is ours rather than vendor's: the query is
`trim()`ed before it is lowered, so a whitespace-only search shows everything
here where vendor — whose only empty test is `['', null, undefined].includes()` —
substring-matches those spaces and hides nearly every option. Worth knowing
either way is that both sides re-read `innerText` on options the previous pass
already hid, ours behind the `hidden` attribute and vendor's behind `x-show`'s
`display: none`; an unrendered element's `innerText` falls back to `textContent`,
which is what lets a widened query find an option it had just filtered out.
Four wiring points had no direct translation:

1. **Livewire's `interceptMessage` hook becomes a `MutationObserver`.** Vendor
   re-collects the options after every round-trip belonging to its own component
   id; we have no per-component success hook, so the controller observes its own
   element for `childList` changes and re-runs the same pass. `childList` **only**
   is load-bearing: the pass writes `hidden` attributes on the options, so
   observing attributes would feed the observer its own output. The residual gap
   is a morph that rewrites `checked` on the *same* option elements and touches
   no child list — the label would keep the state it had. It is narrow because a
   server round-trip on this field is a response to a client change, which the
   delegated listener below already refreshed on, and it is the reason to reach
   for a render event rather than a widened observer if it ever bites.
2. **The per-option `x-on:change` hook becomes one delegated listener.** Vendor
   puts a change handler on every input so an individual click re-flips the
   select/deselect label; the template emits no action on the inputs, so the
   controller attaches a single `change` listener to its root in `connect()` and
   filters to checkbox targets — the same imperative shape the search input's
   `input` listener takes, and for the same reason (the template supplies the
   target, not the binding). It also fixes vendor's O(N) re-check during a bulk
   toggle: the write sets a flag the listener skips on, and the toggle ends with
   one refresh.
3. **The synthetic `change` event has to bubble.** Vendor's `new Event('change')`
   does not, and Livewire's model listener sits on the element itself.
   `live_controller` registers its `change` listener on the **component root**
   and reads `event.target` (`elementEventListeners` →
   `handleChangeEvent()` → `updateModelFromElementEvent()`), so a non-bubbling
   event would flip the box and never reach the state. Ours passes
   `{ bubbles: true }`.
4. **Vendor's `$nextTick` on the first read is dropped**, because
   [the `checked` attribute is rendered server-side](#the-checked-attribute-is-rendered-server-side) —
   the DOM is already correct when `connect()` runs, which is also what lets that
   first pass reconcile the template's hardcoded span visibility.

One mechanism the filter rests on is not the controller's at all: **its `hidden`
attributes survive a morph**. Every option the filter hides is an attribute write
the server never made, so a re-render's HTML carries none of them and a plain
morph would unhide the lot on the next round trip. ux-live-component prevents
exactly that. Its `ExternalMutationTracker`
([`live_controller.js:1064`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js))
runs a `MutationObserver` with `attributes: true` over the component root the
whole time no render is in flight, filing every outside attribute write against
the element that took it; `executeMorphdom` then reads that file back in
idiomorph's `beforeNodeMorphed` callback — `:917` looks the element up and calls
`applyToElement()` on the **incoming** node, which at `:1043` replays the recorded
`setAttribute` / `removeAttribute` pairs — so the new markup arrives already
carrying the filter's decisions. Nothing in the port asks for this and nothing
would report it missing: without it a search would silently reset itself on every
`live()` update, which is why it is recorded here rather than left as the reason
a bug never happened. It is also the division of labour behind point 1 above — the
observer is for the options a morph **adds or removes**, not for the attributes it
would otherwise erase.

One behavioural divergence, in the empty-filtered-set corner. Vendor compares
`visible.length === satisfied.length`, which is vacuously true for an empty set,
so a search matching nothing shows **"Deselect all"** over an empty list. Ours
requires a non-empty set, resting on "Select all" instead. Both links are inert
there — there is nothing visible to write — so this is a label choice, and the
one that does not offer to deselect nothing wins.

**What no test in this repo can reach.** There is no JS test rig — no jsdom, no
browser driver, nothing that instantiates a Stimulus controller — so the
[render tests](../../../packages/forms/tests/Integration/Components/CheckboxListTest.php)
assert the controller's *contract* (identifier, targets, the debounce value, the
click actions) and nothing asserts its *behaviour*. The filter, the bulk toggle,
the observer and the delegated listener are verified by reasoning against the
installed vendor sources and by driving the demo in a browser, never by
execution here. The
[bundle test](../../../packages/forms/tests/Integration/SylamentFormsBundleTest.php)
covers the other half of the shipping path: that the built controller resolves
through AssetMapper under `@sylament/forms`, which is what StimulusBundle needs
to wire the `symfony.controllers` entry at all.

### The bulk-toggle labels are untranslated literals

`'Select all'` and `'Deselect all'` are literals where Filament reads
`filament-forms::components.checkbox_list.actions.select_all.label` and
`…deselect_all.label`. Same call as
[`Radio`'s boolean labels](radio.md#the-boolean-labels-are-untranslated-literals);
the keys are recorded so the strings can be found when a translation layer
lands, and Filament's own English values are these two. These are the only keys
in this field's own `checkbox_list` family — everything the search box says
comes from
[`select.*`](can-be-searchable.md#the-four-message-literals-are-untranslated).

### `isMultiple()` is port plumbing with no Filament counterpart

Filament reads this field's multiplicity off a type: `getRelationship(): ?BelongsToMany`
says "to-many" in the signature, and nothing has to ask. The port's shared
relationship arm asks the field instead — one trait serving a `Select` that may
be either — so `CheckboxList` answers a constant `true` from an `isMultiple()`
of its own.

It is **internal**: no `multiple()` setter exists, because there is nothing to
choose. Listed in
[renames.md](../renames.md#names-with-no-filament-source) so a migrating user who
greps Filament for it and finds nothing knows the absence is real.

### The relationship label is a `getDefaultLabel()` override

Vendor overrides `getLabel()` itself, branching on `$this->label === null` and
falling through to `parent::getLabel()`. The port's
[`Field`](field.md) already splits that decision in two — `getLabel()` takes an
explicit label when one is `filled()` and otherwise calls `getDefaultLabel()` —
so the relationship derivation belongs in the second half, and the field
overrides `getDefaultLabel()` rather than the getter above it.

Same layering, same precedence (`label('…')` wins, then the relationship name,
then the field name), one fewer place that has to remember to call its parent.
The derivation itself is vendor's, spelled in Symfony's `u()`:
`->before('.')->kebab()->replace('-', ' ')->title()`, which is why
`CheckboxList::make('tagIds')->relationship('tags')` reads as "Tags" while the
same field without a relationship reads as "Tag ids".

### The relationship state is not pruned from the dehydrated bag

Filament's `relationship()` ends with `dehydrated(false)`. This field's does
not, and neither does `Select`'s or `Repeater`'s: the port's dehydration walk is
what **routes** a value to its save hook, so pruning the path would switch the
feature off rather than protect it. The state stays in the bag and the hook
takes it — the port-wide reading already recorded in
[renames.md](../renames.md#renames) and
[repeater.md](repeater.md#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope),
verified for this field rather than assumed and pinned by a case in
[`CheckboxListRelationshipTest`](../../../packages/forms/tests/Integration/Components/CheckboxListRelationshipTest.php).

### The options grid is always applied, and the option containers are unkeyed

Vendor grids the options unconditionally — `CheckboxList` has no `inline()`
where [`Radio`](radio.md#inline-is-radios-own-property-not-the-canbeinline-trait)
and `ToggleButtons` do — and the port keeps that: the `Column` default and
`columns()` work, `inline()` does not exist to be called.

Vendor also keys every option container with
`wire:key="{livewireKey}.options.{value}"` and the empty branch with a
`.empty` anchor. The port emits neither. Livewire's `wire:key` is a morph hint
for a list whose *members can move*; this list is regenerated wholesale from one
options map, in map order, on every render — nothing reorders — and idiomorph
matches on the per-option input `id`s the template already emits, which is the
same anchor `Radio` relies on. The empty-branch anchor has nothing to anchor
once the keys are gone, so an empty list renders an empty grid.
