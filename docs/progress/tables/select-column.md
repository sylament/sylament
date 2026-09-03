# SelectColumn (tables)

[`Sylament\Tables\Columns\SelectColumn`](../../../packages/tables/src/Columns/SelectColumn.php)
— an editable [`Column`](column.md) whose cell is a `<select>`: choosing an
option writes its key back onto the record through the host's
[`updateTableColumnState()`](has-columns.md) live action. It ports Filament's
[`Filament\Tables\Columns\SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php).
Everything [`Column`](column.md) carries is tracked there, and the write-back
seam the four editable columns share is tracked on
[`has-columns.md`](has-columns.md), not repeated here.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses). The native cell ships whole; the
searchable, preloaded and relationship arms are **blocked** on the same thing —
the select-engine promotion described on
[`forms-bundle.md`](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)
— which this epic deliberately does not trigger.

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/SelectColumn.php`](../../../vendor/filament/tables/src/Columns/SelectColumn.php)
  — the class and its `toEmbeddedHtml()`
- [`tables/resources/css/columns/select.css`](../../../vendor/filament/tables/resources/css/columns/select.css)
  — the stylesheet [`select-column.css`](../../../packages/tables/assets/src/styles/components/select-column.css)
  is the prefix-renamed port of

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in Details,
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()` and the default placeholder](../../../packages/tables/src/Columns/SelectColumn.php) | [`SelectColumn::setUp()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [semantics](#the-placeholder-copy-is-english-and-hardcoded) |
| [`options()` / `getOptions()`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Forms\Concerns\HasOptions`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [diverges](#the-option-comparison-reads-the-client-state-not-getoptioncomparisonstate) |
| [`rememberOptions()` / `areOptionsRemembered()` — memoise a closure option map](../../../packages/tables/src/Columns/SelectColumn.php) | [`SelectColumn::rememberOptions()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [semantics](#remembered-options-are-memoised-per-column-instance-not-per-row) |
| [`enum()` and its option/label derivation](../../../packages/forms/src/Components/Concerns/HasEnum.php) | [`Forms\Concerns\HasEnum`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | done | — | — |
| [`disableOptionWhen()` / `isOptionDisabled()` / `getEnabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) | [`Forms\Concerns\CanDisableOptions`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [diverges](#the-only-state-cast-a-column-carries-is-the-enum-arms) |
| [`selectablePlaceholder()` / `canSelectPlaceholder()`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php) | [`Forms\Concerns\CanSelectPlaceholder`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | done | — | — |
| [`extraInputAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Forms\Concerns\HasExtraInputAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| [`getStateCasts()` — the enum arm's `EnumStateCast`, else `[]`](../../../packages/tables/src/Columns/SelectColumn.php) | [`Select::getEnumDefaultStateCast()`](../../../vendor/filament/forms/src/Components/Select.php) (Eloquent casts in Filament's column) | done | — | [diverges](#the-only-state-cast-a-column-carries-is-the-enum-arms) |
| [The `Choice` seed over the enabled option keys](../../../packages/tables/src/Columns/SelectColumn.php) | [`SelectColumn::getRules()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [diverges](#rulein-becomes-a-choice-over-string-cast-keys-rebuilt-per-validation) |
| [`$template`](../../../packages/tables/src/Columns/SelectColumn.php) + [the partial it names](../../../packages/tables/templates/partials/SelectColumn/index.html.twig) | [`SelectColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [semantics](#the-chrome-is-supports-inputwrapper-around-forms-sy-fo-select) · [diverges](#the-option-comparison-reads-the-client-state-not-getoptioncomparisonstate) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/SelectColumn/index.html.twig) | `selectTableColumn()` Alpine data | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) · [diverges](#the-option-comparison-reads-the-client-state-not-getoptioncomparisonstate) |
| [The refusal chrome — `aria-invalid` and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/SelectColumn/index.html.twig) | the Alpine `error` binding | done | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline`, `min-w-48` and the padding on the root](../../../packages/tables/assets/src/styles/components/select-column.css) | [`columns/select.css`](../../../vendor/filament/tables/resources/css/columns/select.css) | done | — | [toggle-column.md](toggle-column.md#the-stacked-on-mobile-arm-is-not-ported) |
| `wire:loading.attr` / `wire:target` on the control | [`SelectColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble, `wire:ignore.self` on the root | [`SelectColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | skipped | — | Same two reasons as [`ToggleColumn`](toggle-column.md#surface): the refusal is server-rendered, and the root must morph to carry the `state` value |
| `native()` / `isNative()` | [`SelectColumn::native()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion ([forms-bundle.md](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)) | [diverges](#the-cell-is-native-only-and-native-is-not-shipped-as-an-inert-flag) |
| `searchableOptions()` / `getOptionsSearchResults()` / `getOptionsSearchResultsUsing()` / `optionsSearchDebounce()` / `forceOptionsSearchCaseInsensitive()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | The searchable arm is the JS control's, and the control is forms' — its promotion out of forms is what unblocks the whole group |
| `preloadOptions()` / `areOptionsPreloaded()` | [`SelectColumn::preloadOptions()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | Preloading only means something to a JS control that would otherwise fetch |
| `optionsLimit()` / `position()` / `wrapOptionLabels()` / `allowOptionsHtml()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | All four configure the JS control's dropdown; a native `<select>` has none of them |
| `noOptionsMessage()` / `noOptionsSearchResultsMessage()` / `optionsLoadingMessage()` / `optionsSearchingMessage()` / `optionsSearchPrompt()` and their getters | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | Five copy setters for dropdown states a native `<select>` never enters |
| `getOptionLabelUsing()` / `getOptionLabel()` / `getOptionLabelFromRecordUsing()` / `getOptionLabelFromRecord()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | The label seams exist to name an option the client holds but the option map does not; the native cell always renders the whole map |
| `optionsRelationship()` and the relationship family (`getRelatedKeyNameForOptionsRelationship()`, `getQualifiedRelatedKeyNameForOptionsRelationship()`, `getOptionLabelFromRelationship()`, the modifier closures) | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | Eloquent relation traversal for options, which needs the searchable arm to be worth having |
| `transformOptionsForJs()` / `transformOptionsForJsUsing()` / `hasInitialNoOptionsMessage()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | The JS control's option payload |
| The three `#[ExposedLivewireMethod]`s and the `callTableColumnMethod` twin | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php), [`HasColumns::callTableColumnMethod()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | blocked | the select-engine promotion | The host half is tracked on [has-columns.md](has-columns.md#surface) |

Every built row is covered by the Unit
[`SelectColumnTest`](../../../packages/tables/tests/Unit/Columns/SelectColumnTest.php)
and the Integration
[`SelectColumnTest`](../../../packages/tables/tests/Integration/Columns/SelectColumnTest.php);
the write, the disabled-option refusal and the enum arm's round trip over a
real enum-typed column are
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php)'s.

## Divergences

### The only state cast a column carries is the enum arm's

Forms' `CanDisableOptions::getEnabledOptionValues()` runs each option key back
through the component's state casts before comparing — a schemas-tier idea, and
`getStateCasts()` is declared only by schemas' `HasState`, which a `Column` does
not compose. Rather than fork the trait or widen the column into a schema
component, the write-back seam declares the method itself:
[`CanUpdateState`](has-columns.md#the-write-goes-through-the-accessor-so-a-dotted-path-walks-the-to-one-chain)
answers `[]`, which is the truth for every editable cell whose control speaks
the record's own values, and `SelectColumn` overrides it for the one arm that
does not. An enum option map's keys are backing values, so the column seeds
`[new EnumStateCast($enum)]` exactly as forms'
[`Select::getEnumDefaultStateCast()`](../forms/select.md) does minus the
multiple arm — a column select is never multiple. A **pure** enum throws, in the
column's own vocabulary, because its cases have no backing value to post.

That throw is a **second copy** of forms' `assertBackedEnum()`: forms declares it
on `Field`, which a `Column` does not extend, so the four lines and their message
are duplicated here rather than reached. Neither support nor forms is the obvious
home — support has no enum-option concept and forms' copy sits on the field
base — so the dedupe waits for a third caller to say where it belongs. Recorded
as an adjacent issue, not as a divergence: nothing about the behaviour differs
from forms', only the file it is written in.

The cast leaves the [`Choice` seed](#rulein-becomes-a-choice-over-string-cast-keys-rebuilt-per-validation)
alone, which is the point of `getEnabledOptionValues()` reversing the list
through `set()`: `EnumStateCast::set()` hands a non-enum straight back, so an
option key stays the string the `<select>` posts and validation still runs on
the wire value.

### `Rule::in()` becomes a `Choice` over string-cast keys, rebuilt per validation

Filament's `getRules()` appends `Rule::in([])` — an impossible rule — when the
posted state has no option label, which is how it refuses a disabled or unknown
option. The port seeds `new Choice(choices: $this->getEnabledOptionValues())`
inside `getValidationConstraints()` instead, which says the same thing directly.
Two details are load-bearing. The keys are **string-cast** (that is what
`getEnabledOptionValues()` produces) because a `<select>` posts strings and
`Choice` compares strictly — an integer-keyed option map would otherwise refuse
every selection. And the seed is rebuilt on every `getValidationConstraints()`
call rather than in `setUp()`, so a closure option map is read at the moment the
input is judged.

### The option comparison reads the client state, not `getOptionComparisonState()`

Forms' `Select` selects an option by comparing each key against
[`HasOptions::getOptionComparisonState()`](../forms/has-options.md), and the
column composes that trait, so the method is inherited and available. The
partial deliberately does not call it: `getOptionComparisonState()` reads
`getState()`, which on a refusal is the record's **unchanged** value, so the
choice the user just made would vanish from the control the moment it was
refused. The comparison therefore runs against
[`getClientState()`](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph)
— the same value the cell root publishes for the controller — which normalises a
`BackedEnum` to its value exactly as the forms method does and answers the
refused input while the host holds one. The refused option staying selected is
pinned over the wire by
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php);
nothing in the port calls `getOptionComparisonState()` on a column.

### The cell is native-only, and `native()` is not shipped as an inert flag

The whole searchable/preloaded/relationship group above is one decision: the
searchable select is forms' JS control, and promoting it out of forms is a
separate piece of work this epic does not trigger. `native()` is **blocked**
rather than shipped returning `true`, because a setter no builder consults is
worse than an absent one — a migrating user who calls `native(false)` and sees a
native `<select>` learns nothing, where a missing method is an immediate answer.

## Semantics

### The placeholder copy is English and hardcoded

`setUp()` sets `placeholder('Select an option')`, Filament's own
`filament-tables::table.columns.select.placeholder`. There is no i18n layer, so
the string is inlined here as it is everywhere else in the port
([table.md](table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)).
The placeholder renders as an `<option value="">`, selected while the record
holds nothing, and blank while the column is disabled — Filament's own arm.

### Remembered options are memoised per column instance, not per row

`rememberOptions()` makes `getOptions()` cache its first answer on the column.
A table mounts one column instance and binds it to each record in turn, so the
memo spans the whole page — which is the point: an enum option map would
otherwise be rebuilt once per row. A closure that means to answer differently per
record must therefore **not** remember.

### The chrome is support's `InputWrapper` around forms' `sy-fo-select`

The cell renders `<twig:SylamentSupport:InputWrapper>` around
`select.sy-fo-select`, minus the `id`, `name` and `data-model` a schema field
carries and plus an `aria-label` taken from the column label with any markup
stripped. Grouped options render as `<optgroup>`, a disabled option carries
`disabled` from `isOptionDisabled()`, and the selected option is the one matching
the cell root's `state` value — which is the record's own state normally and the
**refused input** while the host holds a refusal for the cell, so the user's
choice stays on screen beside the message explaining it. An enum-typed record
value reaches that comparison as its backing string, because `getClientState()`
runs the [state casts](#the-only-state-cast-a-column-carries-is-the-enum-arms)
back through `set()` before the string conversion.
