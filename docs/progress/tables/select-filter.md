---
description: 'The option-list filter: a `BaseFilter` whose form is one `Select` and whose query pass is a bound equality or `IN` on a column the developer names.'
ports:
  - vendor/filament/tables/src/Filters/SelectFilter.php
  - vendor/filament/tables/src/Filters/Concerns/HasOptions.php
  - vendor/filament/tables/src/Filters/Concerns/HasPlaceholder.php
  - vendor/filament/tables/src/Filters/Concerns/HasRelationship.php
owns:
  - packages/tables/src/Filters/Concerns/HasOptions.php
  - packages/tables/src/Filters/Concerns/HasPlaceholder.php
  - packages/tables/src/Filters/SelectFilter.php
---
# SelectFilter (tables)

[`Sylament\Tables\Filters\SelectFilter`](../../../packages/tables/src/Filters/SelectFilter.php) is the option-list filter: a [`BaseFilter`](base-filter.md) whose form is one [`Select`](../forms/select.md) and whose query pass is a parameter-bound equality or `IN` on a column the developer names.
It ports [`Filament\Tables\Filters\SelectFilter`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) and the `Filters\Concerns\` traits only it composes: [`HasOptions`](../../../packages/tables/src/Filters/Concerns/HasOptions.php), [`HasPlaceholder`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php) and Filament's `HasRelationship`.
Everything [`BaseFilter`](base-filter.md) carries is tracked there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`options()` / `getOptions()`](../../../packages/tables/src/Filters/Concerns/HasOptions.php) | [`Concerns\HasOptions`](../../../vendor/filament/tables/src/Filters/Concerns/HasOptions.php) | done | — | [diverges](#options-takes-an-array-or-a-closure-and-nothing-else) |
| `options()` given an enum class-string | [`Concerns\HasOptions`](../../../vendor/filament/tables/src/Filters/Concerns/HasOptions.php) | todo | — | [detail](#the-filters-options-reads-no-enum-class-string-where-forms-options-does) |
| [`placeholder()` / `getPlaceholder()`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php) | [`Concerns\HasPlaceholder`](../../../vendor/filament/tables/src/Filters/Concerns/HasPlaceholder.php) | done | — | [diverges](#the-placeholder-copy-is-hardcoded-english) |
| [`multiple()` / `isMultiple()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::multiple()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | done | — | — |
| [`attribute()` / `getAttribute()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::attribute()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | done | — | [semantics](#a-dotted-attribute-is-resolved-not-joined) |
| `column()` / `getColumn()` (deprecated; `attribute()` replaces them) | [`SelectFilter::column()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | skipped | — | [diverges](#the-deprecated-column-alias-is-not-ported) |
| [`static()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::static()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | done | — | — |
| [`selectablePlaceholder()` / `canSelectPlaceholder()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::selectablePlaceholder()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | done | — | — |
| [`getFormField()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::getFormField()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | in progress | — | [detail](#the-js-select-surface-is-dropped-from-the-field-chain) · [detail](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) |
| [`apply()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::apply()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | in progress | — | [detail](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) · [semantics](#every-comparison-is-parameter-bound) · [semantics](#the-bail-test-asks-whether-any-element-is-filled-not-whether-the-array-is-empty) |
| [`getActiveCount()`](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::getActiveCount()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | done | — | [semantics](#the-active-count-is-a-filled-test-not-an-indicator-count) |
| [reset state](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::setUp()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | done | — | [diverges](#the-reset-state-clears-the-key-the-state-actually-uses) |
| [indicators](../../../packages/tables/src/Filters/SelectFilter.php) | [`SelectFilter::setUp()`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | in progress | — | [detail](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) · [semantics](#a-user-supplied-indicator-object-passes-through-unsuffixed) · [diverges](#the-multi-value-label-follows-selection-order-and-a-blank-option-key-still-indicates) |
| `searchable()` / `native()` / `optionsLimit()` with their getters, and the `getOptionLabelUsing()` / `getOptionLabelsUsing()` / `getSearchResultsUsing()` seams | [`SelectFilter.php`](../../../vendor/filament/tables/src/Filters/SelectFilter.php), [`Concerns/HasOptions.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasOptions.php) | blocked | a tables decision on filter searchability | [detail](#the-js-select-surface-is-dropped-from-the-field-chain) |
| `getOptionLabelFromRecordUsing()` / `forceSearchCaseInsensitive()` | [`SelectFilter.php`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | blocked | the filters' relationship arm | [detail](#the-js-select-surface-is-dropped-from-the-field-chain) |
| `relationship()` / `preload()` / `emptyRelationshipOptionLabel()`, the rest of `HasRelationship` and the `get*FromRelationship()` family | [`Concerns/HasRelationship.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasRelationship.php) | blocked | a Doctrine design for the filters' relationship arm | [detail](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) |

## Details

### The JS-select surface is dropped from the field chain

Filament's `getFormField()` also calls `searchable()`, `native()`, `preload()` and `optionsLimit()`, plus the four `getOption*Using()` seams and `forceSearchCaseInsensitive()`.
All nine exist on [forms' `Select`](../forms/select.md), which ships the JS control, so the filter not calling them is not a missing dependency.
The port's chain is `label()`, `multiple()`, `placeholder()`, `selectablePlaceholder()`, `options()` and `default()` ([`SelectFilter.php:101`](../../../packages/tables/src/Filters/SelectFilter.php)).

Two things hold the rest, and neither is the control's to settle.
The first is a tables decision about what the words mean here: `searchable()` on a filter means the *filter form's* select, not the table's own search box, and the two would sit a few pixels apart in the same toolbar.
The mechanism is ready either way, because the filters form is cached on the host under the name `tableFiltersForm` ([`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php)) and a cached schema is exactly what [`resolveSchemaByName()`](../schemas/interacts-with-schemas.md#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source) accepts, so a filter select is already addressable through the host seam the control searches over.
The second is that the relationship-backed half of the list — `getOptionLabelFromRecordUsing()`, and the `forceSearchCaseInsensitive()` that only means something over a relation query — belongs with the [deferred relationship arm](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) rather than with searchability, so those two cannot land ahead of it either way.

### The relationship arm is deferred, with a documented escape hatch

`Concerns\HasRelationship` is Eloquent through and through — `Relation::noConstraints`, `RelationshipJoiner`, `whereHas` / `whereDoesntHave` / `whereKey`, `pluck` — and the `getFormField()`, `apply()` and indicator relationship branches ride on it.
The port ships each of those three without its relationship branch.
All of it waits on a Doctrine design of its own: the [relation arm](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) that gives columns, sorting and search their relationship traversal leaves filters' `HasRelationship` to its own row.
Until then the supported shape is `options()` plus a `query()` closure over a query that already joins: `ColumnPathResolver` is alias-aware, so `->attribute('artist.name')` works the moment the declared query carries `leftJoin('song.artist', 'artist')`.

### The filter's `options()` reads no enum class-string, where forms' `options()` does

Filament's `HasOptions::options()` accepts an enum class-string and `getOptions()` unwraps its cases, through the enum's `HasLabel` contract when it has one.
[Forms' `HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) ports that arm (`options(array|string|Closure|null)` at `:23`, the enum branch in `getOptions()` at `:40`), and the filter's trait does not: it takes `array|Closure|null` ([`Filters/Concerns/HasOptions.php:19`](../../../packages/tables/src/Filters/Concerns/HasOptions.php)).
Because the filter hands its options to the field as a closure over `getOptions()`, the field's own enum support never sees the class-string, so a filter declared `options(Status::class)` fails at the type check.

## Semantics

### Every comparison is parameter-bound

`apply()` never interpolates a value into the DQL: `bindQueryParameter()` mints `sylament_filter_<n>` from the builder's current parameter count and binds there, so the equality arm emits `song.title = :sylament_filter_0` and the multiple arm emits `song.title IN(:sylament_filter_0)` with the array bound whole.
The client controls values and the developer controls the column name, so this is the whole of the injection surface.

### The bail test asks whether **any** element is filled, not whether the array is empty

The guard is `array_filter(wrap($values), filled(...)) === []` — Filament's `blank(Arr::first(Arr::wrap($values), filled(...)))`.
So `['', null]` is skipped entirely while `['', 'Bravo']` applies **with the blank element still in the bound array**, exactly as Filament passes it to `whereIn`.
Filtering the blank out would be a second, silent divergence; the row it can never match is harmless.

### A dotted attribute is resolved, not joined

`getAttributePath()` runs the attribute through [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php), the same resolver sorting and search use: a bare name is qualified with the root alias, a dotted path whose head is a declared alias passes through, and a dotted path whose head is not an alias throws by name.
A filter joins nothing of its own — that is the query lane's rule, and it is what makes the relationship escape hatch above work.

The refusal itself is the filter's own, thrown from `getAttributePath()` ahead of the resolver rather than left to it.
The resolver's message is written for the lanes that reach it *after* the mapping walk — sorting and search go through [`RelationPathResolver`](../../../packages/tables/src/Query/RelationPathResolver.php) first, so by the time they refuse a name it genuinely is neither a mapped field path nor an association path, and the JSON-column clause is genuinely the remaining suspect.
A filter runs none of that: it hands the raw attribute straight to `resolve()`, so the resolver would refuse a **mapped** `artist.name` on `Song` with the claim that it is not an association path on `Song` — false, and false in the one direction that misleads, since the reader's next move would be to check a mapping that is already correct.
The filter's own message names the rule that actually refused the path: the head is not an alias, a filter joins nothing of its own, and the fix is to join and alias the relation where the table query is declared and name that alias here.

### A user-supplied `indicator()` object passes through unsuffixed

`getIndicator()` returns `Indicator|string|Markup`.
Only the string/`Markup` arms become the `"{label}: {values}"` prefix; an already-built `Indicator` is returned verbatim, so a consumer who wants full control of the chip builds one.
Faithful to Filament.

### The active count is a filled test, not an indicator count

`BaseFilter::getActiveCount()` counts indicators; this override returns `0` or `1` from `filled()` on the state value.
They part company for a state naming a value the options do not list: no label, so no indicator, but the count still reads `1`.
Filament behaves identically, and the count is what the trigger badge shows.

## Divergences

### `options()` takes an array or a closure, and nothing else

Filament's `HasOptions::options()` also accepts an `Arrayable`, calling `toArray()` on it inside `getOptions()`.
There is no Laravel `Arrayable` here, so the filter's trait takes `array|Closure|null`; forms' `HasOptions` drops the `Arrayable` arm too.
The enum class-string arm is a [gap](#the-filters-options-reads-no-enum-class-string-where-forms-options-does) rather than part of this divergence.

### The placeholder copy is hardcoded English

`setUp()` seeds the literal `'All'` where Filament branches on `isMultiple()` to read `__('filament-tables::table.filters.multi_select.placeholder')` or `__('filament-tables::table.filters.select.placeholder')`.
Both keys carry `'All'` in that catalogue ([`lang/en/table.php`](../../../vendor/filament/tables/resources/lang/en/table.php) `:193` and `:198`), so the branch collapses to one literal and the rendered field matches Filament's copy verbatim — a filter's placeholder says *"All"*, not the generic form-select *"Select an option"* our own [`Select`](../forms/select.md) seeds.
What is left is the mechanism rather than the copy — the same [hardcoded-copy gap](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) the empty state, the search chip and the [ternary arm labels](ternary-filter.md#the-arm-labels-are-hardcoded-english) carry, lifting when toolkit-wide i18n ships.
The branch returns with it, since the two keys stay separately overridable even though their English values agree.

### The deprecated `column()` alias is not ported

`SelectFilter::column()` / `getColumn()` are `@deprecated` one-line delegates to `attribute()` / `getAttribute()` upstream.
A deprecated Filament member [is not ported](../renames.md#a-deprecated-filament-member-is-not-ported); `attribute()` is the name to use, and the [renames table](../renames.md#renames) carries the row.

### The reset state clears the key the state actually uses

Filament's `setUp()` calls `resetState(['value' => null])` **unconditionally**, so a `multiple()` select filter resets a key its state never writes and leaves `values` untouched — chip removal on a multi-select silently does nothing.
Ours sets the reset state from a closure that reads `isMultiple()` at call time, so it is `['values' => []]` for a multiple filter and `['value' => null]` otherwise.
A fix, not a mirror; recorded in the [renames table](../renames.md#renames).

### The multi-value label follows selection order, and a blank option key still indicates

Two small deltas in how the chip's label is derived, both in `getStateLabels()`.
Filament builds the multiple arm with `->only($state['values'])` over the option map, which preserves **option** order and drops duplicates; ours walks the state instead, so `options(['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie'])` with `['c', 'a']` reads *"Charlie & Alpha"* here and *"Alpha & Charlie"* upstream, and a value the state repeats repeats its label.
The state is the order the user's `<select multiple>` sent, so neither reading is wrong, and the walk is the cheaper one.

The second is the blank arm: Filament short-circuits on `blank($state['value'] ?? null)` *before* the lookup, where ours lets the lookup decide.
They part company for exactly one shape — a filter declaring `options(['' => 'None'])` with the state `['value' => '']` — which renders a *None* chip here and no chip upstream.
The query behaves identically either way, since [the bail test](#the-bail-test-asks-whether-any-element-is-filled-not-whether-the-array-is-empty) skips a blank value in both, so the difference is the chip alone: ours says the declared option is selected, Filament's says nothing is.
