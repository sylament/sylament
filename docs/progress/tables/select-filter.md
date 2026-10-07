---
description: 'The option-list filter: a `BaseFilter` whose form is one `Select` and whose query pass is a parameter-bound equality or `IN` on a column the developer… (draft)'
ports:
  - vendor/filament/tables/src/Filters/SelectFilter.php
  - vendor/filament/tables/src/Filters/Concerns/HasRelationship.php
owns:
  - packages/tables/src/Filters/Concerns/HasOptions.php
  - packages/tables/src/Filters/Concerns/HasPlaceholder.php
  - packages/tables/src/Filters/SelectFilter.php
---
# SelectFilter (tables)

[`Sylament\Tables\Filters\SelectFilter`](../../../packages/tables/src/Filters/SelectFilter.php) — the option-list filter: a [`BaseFilter`](base-filter.md) whose form is one [`Select`](../forms/select.md) and whose query pass is a parameter-bound equality or `IN` on a column the developer names.
It ports [`Filament\Tables\Filters\SelectFilter`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) and the two `Filters\Concerns\` traits it adds — [`HasOptions`](../../../packages/tables/src/Filters/Concerns/HasOptions.php) and [`HasPlaceholder`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php) — folded in here rather than earning rows upstairs.
Everything [`BaseFilter`](base-filter.md) carries is tracked there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`options()` / `getOptions()`](../../../packages/tables/src/Filters/Concerns/HasOptions.php) | `Concerns\HasOptions` | done | — | [diverges](#options-takes-an-array-or-a-closure-and-nothing-else) |
| [`placeholder()` / `getPlaceholder()`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php) | `Concerns\HasPlaceholder` | done | — | [diverges](#the-placeholder-copy-is-hardcoded-english) |
| [`multiple()` / `isMultiple()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::multiple()` | done | — | — |
| [`attribute()` / `getAttribute()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::attribute()` | done | — | [semantics](#a-dotted-attribute-is-resolved-not-joined) |
| `column()` / `getColumn()` | `SelectFilter::column()` | skipped | — | [diverges](#the-deprecated-column-alias-is-not-ported) |
| [`static()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::static()` | done | — | — |
| [`selectablePlaceholder()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::selectablePlaceholder()` | done | — | — |
| [`getFormField()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::getFormField()` | in progress | — | [diverges](#the-js-select-surface-is-dropped-from-the-field-chain) |
| [`apply()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::apply()` | in progress | — | [semantics](#every-comparison-is-parameter-bound) · [semantics](#the-bail-test-asks-whether-any-element-is-filled-not-whether-the-array-is-empty) · [diverges](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) |
| [`getActiveCount()`](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::getActiveCount()` | done | — | [semantics](#the-active-count-is-a-filled-test-not-an-indicator-count) |
| [reset state](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::setUp()` | done | — | [diverges](#the-reset-state-clears-the-key-the-state-actually-uses) |
| [indicators](../../../packages/tables/src/Filters/SelectFilter.php) | `SelectFilter::setUp()` | in progress | — | [semantics](#a-user-supplied-indicator-object-passes-through-unsuffixed) · [diverges](#the-multi-value-label-follows-selection-order-and-a-blank-option-key-still-indicates) · [diverges](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) |
| `searchable()`, `native()`, `preload()`, `optionsLimit()`, `getSearchResultsUsing()`, `getOptionLabelUsing()`, `getOptionLabelsUsing()`, `getOptionLabelFromRecordUsing()`, `forceSearchCaseInsensitive()` | `SelectFilter` | blocked | a tables decision on filter searchability, and the relationship arm | [diverges](#the-js-select-surface-is-dropped-from-the-field-chain) |
| `relationship()`, `emptyRelationshipOptionLabel()`, `hasEmptyRelationshipOption()`, the `getOptions*FromRelationship()` family | `Concerns\HasRelationship` | blocked | a Doctrine design for the filters' relationship arm | [diverges](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) |

Every built row is pinned by [`SelectFilterTest`](../../../packages/tables/tests/Unit/Filters/SelectFilterTest.php).

## Semantics

### Every comparison is parameter-bound

`apply()` never interpolates a value into the DQL: `bindQueryParameter()` mints `sylament_filter_<n>` from the builder's current parameter count and binds there, so the equality arm emits `song.title = :sylament_filter_0` and the multiple arm emits `song.title IN(:sylament_filter_0)` with the array bound whole.
The client controls values and the developer controls the column name, so this is the whole of the injection surface — pinned by asserting the DQL and the bound parameter separately.

### The bail test asks whether **any** element is filled, not whether the array is empty

The guard is `array_filter(wrap($values), filled(...)) === []` — Filament's `blank(Arr::first(Arr::wrap($values), filled(...)))`.
So `['', null]` is skipped entirely while `['', 'Bravo']` applies **with the blank element still in the bound array**, exactly as Filament passes it to `whereIn`.
Filtering the blank out would be a second, silent divergence; the row it can never match is harmless.

### A dotted attribute is resolved, not joined

`getAttributePath()` runs the attribute through [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php), the same resolver sorting and search use: a bare name is qualified with the root alias, a dotted path whose head is a declared alias passes through, and a dotted path whose head is not an alias throws by name.
A filter joins nothing of its own — that is the query lane's rule, and it is what makes the relationship escape hatch below work.

The refusal itself is the filter's own, thrown from `getAttributePath()` ahead of the resolver rather than left to it.
The resolver's message is written for the lanes that reach it *after* the mapping walk — sorting and search go through [`RelationPathResolver`](../../../packages/tables/src/Query/RelationPathResolver.php) first, so by the time they refuse a name it genuinely is neither a mapped field path nor an association path, and the JSON-column clause is genuinely the remaining suspect.
A filter runs none of that: it hands the raw attribute straight to `resolve()`, so a **mapped** `artist.name` on `Song` was being refused with the claim that it is not an association path on `Song` — false, and false in the one direction that misleads, since the reader's next move would be to check a mapping that is already correct.
The filter's own message names the rule that actually refused the path: the head is not an alias, a filter joins nothing of its own, and the fix is to join and alias the relation where the table query is declared and name that alias here.
Both halves are pinned — the unmapped head keeps its case, and a second asserts the mapped association name gets this message and not the resolver's association claim.

### A user-supplied `indicator()` object passes through unsuffixed

`getIndicator()` returns `Indicator|string|Markup`.
Only the string/`Markup` arms become the `"{label}: {values}"` prefix; an already-built `Indicator` is returned verbatim, so a consumer who wants full control of the chip builds one.
Faithful to Filament.

### The active count is a filled test, not an indicator count

`BaseFilter::getActiveCount()` counts indicators; this override returns `0` or `1` from `filled()` on the state value.
They part company for a state naming an option that no longer exists: no label, so no indicator, but the count still reads `1`.
Filament behaves identically, and the count is what the trigger badge shows.

## Divergences

### `options()` takes an array or a closure, and nothing else

Filament's `HasOptions::options()` also accepts an `Arrayable` and an enum class-string, unwrapping backed enums and their `HasLabel` contract inside `getOptions()`.
Ours accepts `array|Closure|null` only — matching [forms' `HasOptions`](../forms/select.md), which is the trait the built field actually consults.
There is no Laravel `Arrayable` here, and enum support is a forms-level feature: it arrives on both traits together or on neither.
Keep the two in sync when it does.

### The placeholder copy is hardcoded English

`setUp()` seeds the literal `'All'` where Filament branches on `isMultiple()` to read `__('filament-tables::table.filters.multi_select.placeholder')` or `__('filament-tables::table.filters.select.placeholder')`.
Both keys carry `'All'` in that catalogue ([`lang/en/table.php`](../../../vendor/filament/tables/resources/lang/en/table.php) `:193` and `:198`), so the branch collapses to one literal and the rendered field matches Filament's copy verbatim — a filter's placeholder says *"All"*, not the generic form-select *"Select an option"* our own [`Select`](../forms/select.md) seeds.
What is left is the mechanism rather than the copy — the same [hardcoded-copy gap](table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) the empty state, the search chip and the [ternary arm labels](ternary-filter.md#the-arm-labels-are-hardcoded-english) carry, lifting when toolkit-wide i18n ships.
The branch returns with it, since the two keys stay separately overridable even though their English values agree.

### The deprecated `column()` alias is not ported

`SelectFilter::column()` / `getColumn()` are `@deprecated` one-line delegates to `attribute()` / `getAttribute()` upstream.
The project has never ported a deprecated Filament surface; `attribute()` is the name to use, and the [renames table](../renames.md) carries the row.

### The reset state clears the key the state actually uses

Filament's `setUp()` calls `resetState(['value' => null])` **unconditionally**, so a `multiple()` select filter resets a key its state never writes and leaves `values` untouched — chip removal on a multi-select silently does nothing.
Ours sets the reset state from a closure that reads `isMultiple()` at call time, so it is `['values' => []]` for a multiple filter and `['value' => null]` otherwise.
A fix, not a mirror; pinned by test and recorded in the [renames table](../renames.md).

### The JS-select surface is dropped from the field chain

Filament's `getFormField()` also calls `searchable()`, `native()`, `preload()` and `optionsLimit()`, plus the four `getOption*Using()` seams and `forceSearchCaseInsensitive()`.
All nine exist on [forms' `Select`](../forms/select.md) now — forms ships the JS control, so the premise this section was written on, *none exists on our `Select`, which is a native `<select>`*, is gone.
What has **not** happened is the filter calling them, and the reason is no longer a missing dependency.

Two things are left, and neither is the control's to settle.
The first is a tables decision about what the words mean here: `searchable()` on a filter means the *filter form's* select, not the table's own search box, and the two would sit a few pixels apart in the same toolbar — the mechanism is ready either way, because the filters form is cached on the host under the name `tableFiltersForm` ([`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php)) and a cached schema is exactly what [`resolveSchemaByName()`](../schemas/interacts-with-schemas.md#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source) accepts, so a filter select is already addressable through the host seam the control searches over.
The second is that the relationship-backed half of the list — `getOptionLabelFromRecordUsing()`, and the `forceSearchCaseInsensitive()` that only means something over a relation query — belongs with the [deferred relationship arm](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) rather than with searchability, so those two cannot land ahead of it either way.
Until both are settled, the field chain stays the six methods it calls today, and the row's _Blocked by_ names them rather than the JS control.

### The multi-value label follows selection order, and a blank option key still indicates

Two small deltas in how the chip's label is derived, both in `getStateLabels()`.
Filament builds the multiple arm with `->only($state['values'])` over the option map, which preserves **option** order and drops duplicates; ours walks the state instead, so `options(['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie'])` with `['c', 'a']` reads *"Charlie & Alpha"* here and *"Alpha & Charlie"* upstream, and a value the state repeats repeats its label.
The state is the order the user's `<select multiple>` sent, so neither reading is wrong, and the walk is the cheaper one.

The second is the blank arm: Filament short-circuits on `blank($state['value'] ?? null)` *before* the lookup, where ours lets the lookup decide.
They part company for exactly one shape — a filter declaring `options(['' => 'None'])` with the state `['value' => '']` — which renders a *None* chip here and no chip upstream.
The query behaves identically either way, since [the bail test](#the-bail-test-asks-whether-any-element-is-filled-not-whether-the-array-is-empty) skips a blank value in both, so the difference is the chip alone: ours says the declared option is selected, Filament's says nothing is.

### The relationship arm is deferred, with a documented escape hatch

`Concerns\HasRelationship` is Eloquent through and through — `Relation::noConstraints`, `RelationshipJoiner`, `whereHas` / `whereDoesntHave` / `whereKey`, `pluck` — and the `apply()` and indicator relationship branches ride on it.
All of it waits on a Doctrine design of its own: the [relation arm](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) that gives columns, sorting and search their relationship traversal deliberately leaves filters' `HasRelationship` to its own row, still unbuilt.
Until then the supported shape is `options()` plus a `query()` closure over a query that already joins: `ColumnPathResolver` is alias-aware, so `->attribute('artist.name')` works the moment the declared query carries `leftJoin('song.artist', 'artist')`.
