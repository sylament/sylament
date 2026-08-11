# SelectFilter (tables)

[`Sylament\Tables\Filters\SelectFilter`](../../../packages/tables/src/Filters/SelectFilter.php)
— the option-list filter: a [`BaseFilter`](base-filter.md) whose form is one
[`Select`](../forms/select.md) and whose query pass is a parameter-bound
equality or `IN` on a column the developer names. It ports
[`Filament\Tables\Filters\SelectFilter`](../../../vendor/filament/tables/src/Filters/SelectFilter.php)
and the two `Filters\Concerns\` traits it adds —
[`HasOptions`](../../../packages/tables/src/Filters/Concerns/HasOptions.php) and
[`HasPlaceholder`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php)
— folded in here rather than earning rows upstairs. Everything
[`BaseFilter`](base-filter.md) carries is tracked there.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Filters/SelectFilter.php`](../../../vendor/filament/tables/src/Filters/SelectFilter.php)
  — the field chain, `apply()`, the indicator `setUp()`, and the searchable /
  native / preload / relationship surface that does not come across
- [`tables/src/Filters/Concerns/HasRelationship.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasRelationship.php)
  — deferred wholesale, see the divergence below

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`options()` / `getOptions()`](../../../packages/tables/src/Filters/Concerns/HasOptions.php) | `Concerns\HasOptions` | done | — | [diverges](#options-takes-an-array-or-a-closure-and-nothing-else) |
| [`placeholder()` / `getPlaceholder()`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php) | `Concerns\HasPlaceholder` | done | — | — |
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
| `searchable()`, `native()`, `preload()`, `optionsLimit()`, `getSearchResultsUsing()`, `getOptionLabelUsing()`, `getOptionLabelsUsing()`, `getOptionLabelFromRecordUsing()`, `forceSearchCaseInsensitive()` | `SelectFilter` | blocked | a JS-select epic | [diverges](#the-js-select-surface-is-dropped-from-the-field-chain) |
| `relationship()`, `emptyRelationshipOptionLabel()`, `hasEmptyRelationshipOption()`, the `getOptions*FromRelationship()` family | `Concerns\HasRelationship` | blocked | the tables-relations epic | [diverges](#the-relationship-arm-is-deferred-with-a-documented-escape-hatch) |

Every built row is pinned by
[`SelectFilterTest`](../../../packages/tables/tests/Unit/Filters/SelectFilterTest.php).

## Semantics

### Every comparison is parameter-bound

`apply()` never interpolates a value into the DQL: `bindQueryParameter()` mints
`sylament_filter_<n>` from the builder's current parameter count and binds
there, so the equality arm emits `song.title = :sylament_filter_0` and the
multiple arm emits `song.title IN(:sylament_filter_0)` with the array bound
whole. The client controls values and the developer controls the column name, so
this is the whole of the injection surface — pinned by asserting the DQL and the
bound parameter separately.

### The bail test asks whether **any** element is filled, not whether the array is empty

The guard is `array_filter(wrap($values), filled(...)) === []` — Filament's
`blank(Arr::first(Arr::wrap($values), filled(...)))`. So `['', null]` is skipped
entirely while `['', 'Bravo']` applies **with the blank element still in the
bound array**, exactly as Filament passes it to `whereIn`. Filtering the blank
out would be a second, silent divergence; the row it can never match is
harmless.

### A dotted attribute is resolved, not joined

`getAttributePath()` runs the attribute through
[`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php),
the same resolver sorting and search use: a bare name is qualified with the root
alias, a dotted path whose head is a declared alias passes through, and a dotted
path whose head is not an alias throws by name. A filter joins nothing of its
own — that is the epic-12 rule, and it is what makes the relationship escape
hatch below work.

### A user-supplied `indicator()` object passes through unsuffixed

`getIndicator()` returns `Indicator|string|Markup`. Only the string/`Markup`
arms become the `"{label}: {values}"` prefix; an already-built `Indicator` is
returned verbatim, so a consumer who wants full control of the chip builds one.
Faithful to Filament.

### The active count is a filled test, not an indicator count

`BaseFilter::getActiveCount()` counts indicators; this override returns `0` or
`1` from `filled()` on the state value. They part company for a state naming an
option that no longer exists: no label, so no indicator, but the count still
reads `1`. Filament behaves identically, and the count is what the trigger badge
shows.

## Divergences

### `options()` takes an array or a closure, and nothing else

Filament's `HasOptions::options()` also accepts an `Arrayable` and an enum
class-string, unwrapping backed enums and their `HasLabel` contract inside
`getOptions()`. Ours accepts `array|Closure|null` only — matching
[forms' `HasOptions`](../forms/select.md), which is the trait the built field
actually consults. There is no Laravel `Arrayable` here, and enum support is a
forms-level feature: it arrives on both traits together or on neither. Keep the
two in sync when it does.

### The deprecated `column()` alias is not ported

`SelectFilter::column()` / `getColumn()` are `@deprecated` one-line delegates to
`attribute()` / `getAttribute()` upstream. The project has never ported a
deprecated Filament surface; `attribute()` is the name to use, and the
[renames table](../renames.md) carries the row.

### The reset state clears the key the state actually uses

Filament's `setUp()` calls `resetState(['value' => null])` **unconditionally**,
so a `multiple()` select filter resets a key its state never writes and leaves
`values` untouched — chip removal on a multi-select silently does nothing. Ours
sets the reset state from a closure that reads `isMultiple()` at call time, so
it is `['values' => []]` for a multiple filter and `['value' => null]`
otherwise. A fix, not a mirror; pinned by test and recorded in the
[renames table](../renames.md).

### The JS-select surface is dropped from the field chain

Filament's `getFormField()` also calls `searchable()`, `native()`, `preload()`
and `optionsLimit()`, plus the four `getOption*Using()` seams and
`forceSearchCaseInsensitive()`. None exists on our `Select`, which is a native
`<select>`; calling them would mean building an entire JS select first. They
wait on a future JS-select epic, and the filter's field chain is the six methods
that do exist.

### The multi-value label follows selection order, and a blank option key still indicates

Two small deltas in how the chip's label is derived, both in `getStateLabels()`.
Filament builds the multiple arm with `->only($state['values'])` over the option
map, which preserves **option** order and drops duplicates; ours walks the state
instead, so `options(['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie'])` with
`['c', 'a']` reads *"Charlie & Alpha"* here and *"Alpha & Charlie"* upstream, and
a value the state repeats repeats its label. The state is the order the user's
`<select multiple>` sent, so neither reading is wrong, and the walk is the
cheaper one.

The second is the blank arm: Filament short-circuits on
`blank($state['value'] ?? null)` *before* the lookup, where ours lets the lookup
decide. They part company for exactly one shape — a filter declaring
`options(['' => 'None'])` with the state `['value' => '']` — which renders a
*None* chip here and no chip upstream. The query behaves identically either way,
since [the bail test](#the-bail-test-asks-whether-any-element-is-filled-not-whether-the-array-is-empty)
skips a blank value in both, so the difference is the chip alone: ours says the
declared option is selected, Filament's says nothing is.

### The relationship arm is deferred, with a documented escape hatch

`Concerns\HasRelationship` is Eloquent through and through —
`Relation::noConstraints`, `RelationshipJoiner`, `whereHas` /
`whereDoesntHave` / `whereKey`, `pluck` — and the `apply()` and indicator
relationship branches ride on it. All of it waits on the tables-relations epic,
which is where epic 12 sent every tables relationship arm. Until then the
supported shape is `options()` plus a `query()` closure over a query that
already joins: `ColumnPathResolver` is alias-aware, so
`->attribute('artist.name')` works the moment the declared query carries
`leftJoin('song.artist', 'artist')`.
