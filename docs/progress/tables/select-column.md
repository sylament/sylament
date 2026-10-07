---
description: The editable select column, a native `<select>` whose choice writes the option key back onto the record.
ports:
  - vendor/filament/tables/src/Columns/SelectColumn.php
  - vendor/filament/tables/resources/css/columns/select.css
owns:
  - packages/tables/assets/src/styles/components/select-column.css
  - packages/tables/src/Columns/SelectColumn.php
  - packages/tables/templates/partials/SelectColumn/
---
# SelectColumn (tables)

[`Sylament\Tables\Columns\SelectColumn`](../../../packages/tables/src/Columns/SelectColumn.php) is an editable [`Column`](column.md) whose cell is a `<select>`: choosing an option writes its key back onto the record through the host's [`updateTableColumnState()`](has-columns.md) live action.
Everything [`Column`](column.md) carries is tracked there, and the write-back seam the editable columns share is tracked on [`has-columns.md`](has-columns.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()` and the default placeholder](../../../packages/tables/src/Columns/SelectColumn.php) | [`SelectColumn::setUp()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [has-empty-state.md](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) |
| [`options()` / `getOptions()`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Forms\Concerns\HasOptions`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [diverges](#the-option-comparison-reads-the-client-state-not-getoptioncomparisonstate) |
| [`rememberOptions()` / `areOptionsRemembered()` — memoise a closure option map](../../../packages/tables/src/Columns/SelectColumn.php) | [`SelectColumn::rememberOptions()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [semantics](#remembered-options-are-memoised-per-column-instance-not-per-row) |
| [`enum()` and its option/label derivation](../../../packages/forms/src/Components/Concerns/HasEnum.php) | [`Forms\Concerns\HasEnum`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | done | — | — |
| [`disableOptionWhen()` / `isOptionDisabled()` / `getEnabledOptions()`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) | [`Forms\Concerns\CanDisableOptions`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [diverges](#the-only-state-cast-a-column-carries-is-the-enum-arms) |
| [`selectablePlaceholder()` / `canSelectPlaceholder()`](../../../packages/forms/src/Components/Concerns/CanSelectPlaceholder.php) | [`Forms\Concerns\CanSelectPlaceholder`](../../../vendor/filament/forms/src/Components/Concerns/CanSelectPlaceholder.php) | done | — | — |
| [`extraInputAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Forms\Concerns\HasExtraInputAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| [`getStateCasts()` — the enum arm's `EnumStateCast`, else `[]`](../../../packages/tables/src/Columns/SelectColumn.php) | [`Select::getEnumDefaultStateCast()`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [diverges](#the-only-state-cast-a-column-carries-is-the-enum-arms) |
| [The `Choice` seed over the enabled option keys](../../../packages/tables/src/Columns/SelectColumn.php) | [`SelectColumn::getRules()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [diverges](#rulein-becomes-a-choice-over-the-posted-key-strings-rebuilt-per-validation) |
| [`$template`](../../../packages/tables/src/Columns/SelectColumn.php) + [the partial it names](../../../packages/tables/templates/partials/SelectColumn/index.html.twig) | [`SelectColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [semantics](#the-chrome-is-supports-inputwrapper-around-forms-sy-fo-select) · [diverges](#the-native-cell-renders-forms-select-option-markup-groups-and-the-disabled-blank-placeholder-included) · [diverges](#the-option-comparison-reads-the-client-state-not-getoptioncomparisonstate) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/SelectColumn/index.html.twig) | [`columns/select.js`](../../../vendor/filament/tables/resources/js/components/columns/select.js) | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) · [diverges](#the-option-comparison-reads-the-client-state-not-getoptioncomparisonstate) |
| [The refusal chrome — `aria-invalid` and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/SelectColumn/index.html.twig) | [`columns/select.js`](../../../vendor/filament/tables/resources/js/components/columns/select.js) `error` | done | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline`, `min-w-48` and the padding on the root](../../../packages/tables/assets/src/styles/components/select-column.css) | [`columns/select.css`](../../../vendor/filament/tables/resources/css/columns/select.css) | done | — | [color-column.md](color-column.md#the-stacked-on-mobile-rule-has-nothing-to-select) |
| `wire:loading.attr` / `wire:target` on the control | [`SelectColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble, `wire:ignore.self` on the root | [`SelectColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | skipped | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) · [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| `native()` / `isNative()` | [`SelectColumn::native()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion ([forms-bundle.md](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)) | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) · [diverges](#native-is-not-shipped-as-an-inert-flag) |
| `searchableOptions()` / `areOptionsSearchable()` / `getOptionsSearchResults()` / `getOptionsSearchResultsUsing()` / `optionsSearchDebounce()` / `forceOptionsSearchCaseInsensitive()` / `searchOptionLabels()` / `searchOptionValues()`, their getters, `getSearchableOptionFields()` / `getOptionsSearchColumns()` / `hasDynamicOptions()` / `hasDynamicOptionsSearchResults()` / `applyOptionsSearchConstraint()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| `preloadOptions()` / `areOptionsPreloaded()` | [`SelectColumn::preloadOptions()`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| `optionsLimit()` / `position()` / `wrapOptionLabels()` / `allowOptionsHtml()` and their getters | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| `noOptionsMessage()` / `noOptionsSearchResultsMessage()` / `optionsLoadingMessage()` / `optionsSearchingMessage()` / `optionsSearchPrompt()` and their getters | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| `getOptionLabelUsing()` / `getOptionLabel()` / `getOptionLabelFromRecordUsing()` / `hasOptionLabelFromRecordUsingCallback()` / `getOptionLabelFromRecord()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| `optionsRelationship()` and the relationship family (`getOptionsRelationship()`, `getOptionsRelationshipName()`, `getOptionsRelationshipTitleAttribute()`, `hasOptionsRelationship()`, `getOptionsFromRelationship()`, `getOptionsSearchResultsFromRelationship()`, `getOptionLabelFromRelationship()`, `getRelatedKeyNameForOptionsRelationship()`, `getQualifiedRelatedKeyNameForOptionsRelationship()`, `applyEagerLoading()`) | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| `transformOptionsForJs()` / `transformOptionsForJsUsing()` / `hasInitialNoOptionsMessage()` | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) |
| The three `#[ExposedLivewireMethod]`s (`getOptionsForJs()`, `getOptionLabel()`, `getOptionsSearchResultsForJs()`) and the `callTableColumnMethod` twin | [`SelectColumn`](../../../vendor/filament/tables/src/Columns/SelectColumn.php), [`HasColumns::callTableColumnMethod()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | blocked | the select-engine promotion | [detail](#the-js-control-arms-wait-on-the-select-engine-reaching-tables) · [has-columns.md](has-columns.md#surface) |

## Details

### The JS-control arms wait on the select engine reaching tables

The cell ships Filament's native branch only: a plain `<select>` over the whole option map, with no JS control of its own.
Every blocked row configures or feeds the searchable control Filament renders when `native(false)` is set: search and its debounce, preloading, the dropdown's limit, position, wrapping and HTML labels, the copy for dropdown states a native `<select>` never enters, the label seams that name an option the client holds but the map does not, the Eloquent relationship traversal that feeds search, and the option payload and live methods the control calls.
That control is forms' [select engine](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host), whose `utils/select.ts` ships inlined into forms' bundled entry and moves to a shared workspace when a second package consumes it, which this column's JS arms would be.
The relationship family also needs a Doctrine translation of Filament's Eloquent relation walk.

## Semantics

### Remembered options are memoised per column instance, not per row

`rememberOptions()` makes `getOptions()` cache its first answer on the column.
A table mounts one column instance and binds it to each record in turn, so the memo spans the whole page, which is the point: an enum option map would otherwise be rebuilt once per row.
A closure that means to answer differently per record must therefore **not** remember.

### The chrome is support's `InputWrapper` around forms' `sy-fo-select`

The cell renders `<twig:SylamentSupport:InputWrapper>` around `select.sy-fo-select`, minus the `id`, `name` and `data-model` a schema field carries and plus an `aria-label` taken from the column label with any markup stripped.
The selected option is the one matching the cell root's `state` value, which is the record's own state normally and the **refused input** while the host holds a refusal for the cell, so the user's choice stays on screen beside the message explaining it.
An enum-typed record value reaches that comparison as its backing string, because `getClientState()` runs the [state casts](#the-only-state-cast-a-column-carries-is-the-enum-arms) back through `set()` before the string conversion.

## Divergences

### The only state cast a column carries is the enum arm's

Forms' `CanDisableOptions::getEnabledOptionValues()` runs each option key back through the component's state casts before comparing, and `getStateCasts()` is declared by schemas' `HasState`, which a `Column` does not compose.
Rather than fork the trait or widen the column into a schema component, the write-back seam declares the method itself: [`CanUpdateState`](has-columns.md#the-write-goes-through-the-accessor-so-a-dotted-path-walks-the-to-one-chain) answers `[]`, which is the truth for every editable cell whose control speaks the record's own values, and `SelectColumn` overrides it for the one arm that does not.
An enum option map's keys are backing values, so the column seeds `[new EnumStateCast($enum)]` exactly as forms' [`Select::getEnumDefaultStateCast()`](../forms/select.md) does minus the multiple arm, because a column select is never multiple.
A **pure** enum throws, in the column's own vocabulary, because its cases have no backing value to post.

That throw duplicates forms' `Field::assertBackedEnum()`, which a `Column` cannot reach because it does not extend `Field`; the check and the message shape are forms', with "column" for "field".

The cast leaves the [`Choice` seed](#rulein-becomes-a-choice-over-the-posted-key-strings-rebuilt-per-validation) speaking the wire value, which is the point of `getEnabledOptionValues()` reversing the list through `set()`: `EnumStateCast::set()` hands a non-enum straight back, so an option key stays the string the `<select>` posts.

### `Rule::in()` becomes a `Choice` over the posted key strings, rebuilt per validation

Filament's `getRules()` appends `Rule::in([])`, an impossible rule, when the posted state has no option label or names a disabled option, which is how it refuses a disabled or unknown option.
The port seeds `new Choice(choices: $this->getEnabledOptionValues())` in `buildValidationConstraints()` instead, which says the same thing directly.
Two details are load-bearing.
The choices carry each enabled key's **string** form, which `getEnabledOptionValues()` appends beside the cast value, because a `<select>` posts strings and `Choice` compares strictly, so an integer-keyed option map would otherwise refuse every selection.
And the seed is rebuilt on every `getValidationConstraints()` call rather than in `setUp()`, so a closure option map is read at the moment the input is judged.

### The option comparison reads the client state, not `getOptionComparisonState()`

Forms' `Select` selects an option by comparing each key against [`HasOptions::getOptionComparisonState()`](../forms/has-options.md), and the column composes that trait, so the method is inherited and available.
The partial deliberately does not call it: `getOptionComparisonState()` reads `getState()`, which on a refusal is the record's **unchanged** value, so the choice the user just made would vanish from the control the moment it was refused.
The comparison therefore runs against [`getClientState()`](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph), the same value the cell root publishes for the controller, which normalises a `BackedEnum` to its value exactly as the forms method does and answers the refused input while the host holds one.
The refused option staying selected is pinned over the wire by [`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php); nothing in the port calls `getOptionComparisonState()` on a column.

### The native cell renders forms' `Select` option markup, groups and the disabled-blank placeholder included

Filament's column renders its native branch with a flat loop: every option label goes through `e()`, so a grouped option map fails there, and the placeholder `<option value="">` always prints the placeholder text.
Forms' native `Select` renders an `<optgroup>` per grouped entry and blanks the placeholder option while the field is disabled.
The cell follows forms' markup, because the column composes forms' option traits, whose maps admit groups and whose `isOptionDisabled()` and `getEnabledOptions()` already walk them.
So a grouped map renders as `<optgroup>`s here, and a disabled column shows an empty placeholder option where Filament's shows the text.

### `native()` is not shipped as an inert flag

`native()` is **blocked** rather than shipped returning `true`, because a setter no builder consults is worse than an absent one: a migrating user who calls `native(false)` and sees a native `<select>` learns nothing, where a missing method is an immediate answer.
