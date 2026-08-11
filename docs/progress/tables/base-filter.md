# BaseFilter (tables)

[`Sylament\Tables\Filters\BaseFilter`](../../../packages/tables/src/Filters/BaseFilter.php)
— the root object of the filter tier: a support
[`Component`](../support/component.md) composing the eleven
`Filters\Concerns\` traits that carry a filter's identity, visibility, state,
schema and query application. It ports Filament's
[`Filters\BaseFilter`](../../../vendor/filament/tables/src/Filters/BaseFilter.php)
and the eleven concerns it composes, folded into the surface table below per
[one row per file](../README.md#one-row-per-file-one-file-per-thing).
[`Indicator`](indicator.md) is tracked separately, because it is a component of
its own rather than a concern, and so is each subclass that extends this one —
[`Filter`](filter.md), [`SelectFilter`](select-filter.md) and
[`TernaryFilter`](ternary-filter.md), each carrying its own status in its own
file.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Filters/BaseFilter.php`](../../../vendor/filament/tables/src/Filters/BaseFilter.php)
- [`tables/src/Filters/Concerns/`](../../../vendor/filament/tables/src/Filters/Concerns)
  — fourteen files, of which eleven are composed here; `HasOptions` and
  `HasPlaceholder` ship for `SelectFilter` and `HasRelationship` is deferred

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `getDefaultName()`](../../../packages/tables/src/Filters/BaseFilter.php) | [`BaseFilter::make()`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | done | — | [diverges](#the-name-moves-from-the-constructor-to-a-setter) |
| [`getActiveCount()`](../../../packages/tables/src/Filters/BaseFilter.php) | [`BaseFilter::getActiveCount()`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | done | — | — |
| [`$filter` / `$liveComponent` / `$table` closure injections](../../../packages/tables/src/Filters/BaseFilter.php) | [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | done | — | — |
| [`table()` / `getTable()` / `getLiveComponent()` / `getState()` / `getFormState()`](../../../packages/tables/src/Filters/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Filters/Concerns/BelongsToTable.php) | done | — | [diverges](#the-state-readers-are-guarded-because-the-contract-does-not-declare-them) |
| [`hidden()` / `visible()` / `hiddenOn()` / `visibleOn()`](../../../packages/tables/src/Filters/Concerns/CanBeHidden.php) | [`Concerns\CanBeHidden`](../../../vendor/filament/tables/src/Filters/Concerns/CanBeHidden.php) | done | — | — |
| [`resetState()` / `getResetState()`](../../../packages/tables/src/Filters/Concerns/CanResetState.php) | [`Concerns\CanResetState`](../../../vendor/filament/tables/src/Filters/Concerns/CanResetState.php) | done | — | — |
| [`columnSpan()` / `columnSpanFull()` / `columnStart()`](../../../packages/tables/src/Filters/Concerns/CanSpanColumns.php) | [`Concerns\CanSpanColumns`](../../../vendor/filament/tables/src/Filters/Concerns/CanSpanColumns.php) | done | — | [semantics](#the-span-getters-return-raw-closures) |
| [`columns()`](../../../packages/tables/src/Filters/Concerns/HasColumns.php) | [`Concerns\HasColumns`](../../../vendor/filament/tables/src/Filters/Concerns/HasColumns.php) | done | — | — |
| [`default()` / `getDefaultState()`](../../../packages/tables/src/Filters/Concerns/HasDefaultState.php) | [`Concerns\HasDefaultState`](../../../vendor/filament/tables/src/Filters/Concerns/HasDefaultState.php) | done | — | — |
| [`indicator()` / `indicateUsing()` / `getIndicators()` / `getIndicator()`](../../../packages/tables/src/Filters/Concerns/HasIndicators.php) | [`Concerns\HasIndicators`](../../../vendor/filament/tables/src/Filters/Concerns/HasIndicators.php) | done | — | [semantics](#a-string-array-key-becomes-the-chips-remove-field) |
| [`label()` / `getLabel()`](../../../packages/tables/src/Filters/Concerns/HasLabel.php) | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Filters/Concerns/HasLabel.php) | done | — | [diverges](#translatelabel-is-not-ported) |
| [`name()` / `getName()`](../../../packages/tables/src/Filters/Concerns/HasName.php) | [`Concerns\HasName`](../../../vendor/filament/tables/src/Filters/Concerns/HasName.php) | done | — | — |
| [`schema()` / `modifyFormFieldUsing()` / `getSchemaComponents()` / `hasSchema()` / `getFormField()`](../../../packages/tables/src/Filters/Concerns/HasSchema.php) | [`Concerns\HasSchema`](../../../vendor/filament/tables/src/Filters/Concerns/HasSchema.php) | done | — | [diverges](#the-form-alias-is-not-ported) |
| `getSchema()` — the filter's live child schema | [`Concerns\HasSchema::getSchema()`](../../../vendor/filament/tables/src/Filters/Concerns/HasSchema.php) | todo | the host filters form | [detail](#getschema-lands-with-the-host-filters-form-not-here) |
| [`apply()` / `applyToBaseQuery()` / `query()` / `baseQuery()` / `excludeWhenResolvingRecord()`](../../../packages/tables/src/Filters/Concerns/InteractsWithTableQuery.php) | [`Concerns\InteractsWithTableQuery`](../../../vendor/filament/tables/src/Filters/Concerns/InteractsWithTableQuery.php) | done | — | [diverges](#there-is-no-closure-where-group-around-apply) · [semantics](#isactive-is-an-opt-out-not-an-opt-in) |
| `HasRelationship` | [`Concerns/HasRelationship.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasRelationship.php) | blocked | a future tables-relations epic | — |

Coverage is the fifteen files under
[`tests/Unit/Filters/`](../../../packages/tables/tests/Unit/Filters), one per
source file, plus two
[`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php)
cases: a filter and an indicator built through `make()` carry the **container's**
`Invoker`, and `FilterIndicator` is absent from the container.

## Details

### `getSchema()` lands with the host filters form, not here

The filter's live child schema is read off the host's `getTableFiltersForm()`,
which [`InteractsWithTable`](has-table.md) does not declare yet — so
`getSchema()` is not written in this commit at all. Guarding it would be
scaffolding: the host-state change adds that method to the contract, and the
same change appends `getSchema()` to
[`Filters\Concerns\HasSchema`](../../../packages/tables/src/Filters/Concerns/HasSchema.php),
unguarded, typed at level max. **Next step:** it arrives with the host concern,
covered by the deferred/live round trips.

## Semantics

### The span getters return raw closures

`getColumnSpan()` and `getColumnStart()` do not evaluate. The values are
forwarded verbatim to the schema `Group` the table builds per filter, and the
`Group` evaluates them itself with its own injections; evaluating here would
resolve them against the filter instead.

### A string array key becomes the chip's remove field

`indicateUsing()` may return a keyed map, and a **string** key is taken as the
field name the chip removes — `['value' => 'Published']` produces an indicator
whose `getRemoveField()` is `value`, which the indicators row turns into
`removeTableFilter(filterName, field)`. An integer key (a plain list) leaves the
field null, so removing the chip clears the whole filter.

### `isActive` is an opt-out, not an opt-in

`apply()` and `applyToBaseQuery()` each consult `$data['isActive']` only when
the key is present: `!($data['isActive'] ?? true)`. Both passes carry the same
pair of guards, the hidden check included. A filter whose schema has no
`isActive` field — every `SelectFilter`, every custom `schema()` — therefore
applies unconditionally once it has a query callback, while a checkbox `Filter`
skips when unticked. The
indicator default runs the opposite polarity (`$state['isActive'] ?? false`), so
an unticked filter shows no chip; that asymmetry is Filament's and is preserved.

## Divergences

### The name moves from the constructor to a setter

Filament's `final __construct(string $name)` is dropped, as everywhere else in
the port — the resolver path takes no constructor arguments. `make()` resolves
the name (argument, then `static::getDefaultName()`), refuses a blank one with
Filament's message shape, then `newInstance()` → `name()` → `configure()`,
matching [`Column::make()`](column.md) exactly.

### The state readers are guarded because the contract does not declare them

`getState()` and `getFormState()` reach `getTableFilterState()` /
`getTableFilterFormState()` on the host, and
[`InteractsWithTable`](has-table.md) does not declare either: the contract grows
only with what a shipped template reads off the host, and these are read from
PHP. So both are `method_exists()`-guarded and throw a `LogicException` naming
[`HasTable`](has-table.md) when absent. That is not defensive padding —
`InteractsWithTable` is implementable by hand, and the alternative failure is a
table that silently filters nothing. PHPStan narrows the guarded call to `mixed`,
which a private normalizer turns back into `array<string, mixed>`, so shipped
source carries no inline ignore.

### There is no closure-where group around `apply()`

Filament wraps the whole apply pass in one `$query->where(function (Builder $q) …)`
so a filter's internal `orWhere` cannot leak into the search or another filter.
DQL has no closure-where. Both passes run sequentially on the one builder via
`andWhere()` composition instead — correct for the AND-shaped mainstream, and a
filter needing internal OR writes the idiomatic
`$query->andWhere($query->expr()->orX(…))`. The consumer footgun this leaves is
worth stating plainly: a bare `->orWhere()` inside a filter callback widens the
**whole** query, search included. The base/apply split survives as ordering only.

### `translateLabel()` is not ported

The label derivation is `symfony/string`'s
`u($name)->before('.')->kebab()->replace('-', ' ')->title()`, matching Filament's
`str()->before('.')->kebab()->replace(['-','_'], ' ')->ucfirst()` output
(`is_featured` → `Is featured`; `kebab()` already eats the underscore). The
`__()` pass and `translateLabel()` wait on the toolkit-wide i18n epic, like the
empty-state heading.

### The `form()` alias is not ported

Filament ships `form()` / `getFormSchema()` as deprecated aliases of `schema()` /
`getSchemaComponents()`. Neither is ported — the same call the port made for
`HasChildComponents::schema()`. [renames.md row](../renames.md#renames).
