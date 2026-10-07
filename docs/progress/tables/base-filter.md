---
description: 'The root object of the filter tier: `BaseFilter` and the concerns carrying a filter''s identity, visibility, state, schema and query application.'
ports:
  - vendor/filament/tables/src/Filters/BaseFilter.php
  - vendor/filament/tables/src/Filters/Concerns/BelongsToTable.php
  - vendor/filament/tables/src/Filters/Concerns/CanBeHidden.php
  - vendor/filament/tables/src/Filters/Concerns/CanResetState.php
  - vendor/filament/tables/src/Filters/Concerns/CanSpanColumns.php
  - vendor/filament/tables/src/Filters/Concerns/HasColumns.php
  - vendor/filament/tables/src/Filters/Concerns/HasDefaultState.php
  - vendor/filament/tables/src/Filters/Concerns/HasIndicators.php
  - vendor/filament/tables/src/Filters/Concerns/HasLabel.php
  - vendor/filament/tables/src/Filters/Concerns/HasName.php
  - vendor/filament/tables/src/Filters/Concerns/HasSchema.php
  - vendor/filament/tables/src/Filters/Concerns/InteractsWithTableQuery.php
owns:
  - packages/tables/src/Filters/BaseFilter.php
  - packages/tables/src/Filters/Concerns/BelongsToTable.php
  - packages/tables/src/Filters/Concerns/CanBeHidden.php
  - packages/tables/src/Filters/Concerns/CanResetState.php
  - packages/tables/src/Filters/Concerns/CanSpanColumns.php
  - packages/tables/src/Filters/Concerns/HasColumns.php
  - packages/tables/src/Filters/Concerns/HasDefaultState.php
  - packages/tables/src/Filters/Concerns/HasIndicators.php
  - packages/tables/src/Filters/Concerns/HasLabel.php
  - packages/tables/src/Filters/Concerns/HasName.php
  - packages/tables/src/Filters/Concerns/HasSchema.php
  - packages/tables/src/Filters/Concerns/InteractsWithTableQuery.php
---
# BaseFilter (tables)

[`Sylament\Tables\Filters\BaseFilter`](../../../packages/tables/src/Filters/BaseFilter.php) is the root object of the filter tier: a support [`Component`](../support/component.md) composing the `Filters\Concerns\` traits that carry a filter's identity, visibility, state, schema and query application.
It ports Filament's [`Filters\BaseFilter`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) and the concerns it composes.
[`Indicator`](indicator.md) is tracked separately, because it is a component of its own rather than a concern, and so is each subclass — [`Filter`](filter.md), [`SelectFilter`](select-filter.md) and [`TernaryFilter`](ternary-filter.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `getDefaultName()`](../../../packages/tables/src/Filters/BaseFilter.php) | [`BaseFilter::make()`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | done | — | [diverges](#the-name-moves-from-the-constructor-to-a-setter) |
| [`getActiveCount()`](../../../packages/tables/src/Filters/BaseFilter.php) | [`BaseFilter::getActiveCount()`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | done | — | — |
| [`$filter` / `$liveComponent` / `$table` closure injections](../../../packages/tables/src/Filters/BaseFilter.php) | [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | done | — | — |
| [`table()` / `getTable()` / `getLiveComponent()` / `getState()` / `getFormState()`](../../../packages/tables/src/Filters/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Filters/Concerns/BelongsToTable.php) | done | — | [diverges](#the-state-readers-are-guarded-because-the-contract-does-not-declare-them) |
| [`hidden()` / `visible()` / `hiddenOn()` / `visibleOn()` / `isHidden()` / `isVisible()`](../../../packages/tables/src/Filters/Concerns/CanBeHidden.php) | [`Concerns\CanBeHidden`](../../../vendor/filament/tables/src/Filters/Concerns/CanBeHidden.php) | done | — | — |
| [`resetState()` / `getResetState()`](../../../packages/tables/src/Filters/Concerns/CanResetState.php) | [`Concerns\CanResetState`](../../../vendor/filament/tables/src/Filters/Concerns/CanResetState.php) | done | — | — |
| [`columnSpan()` / `columnSpanFull()` / `columnStart()` and their getters](../../../packages/tables/src/Filters/Concerns/CanSpanColumns.php) | [`Concerns\CanSpanColumns`](../../../vendor/filament/tables/src/Filters/Concerns/CanSpanColumns.php) | done | — | [semantics](#the-span-getters-return-raw-closures) |
| [`columns()` / `getColumns()`](../../../packages/tables/src/Filters/Concerns/HasColumns.php) | [`Concerns\HasColumns`](../../../vendor/filament/tables/src/Filters/Concerns/HasColumns.php) | done | — | — |
| [`default()` / `getDefaultState()`](../../../packages/tables/src/Filters/Concerns/HasDefaultState.php) | [`Concerns\HasDefaultState`](../../../vendor/filament/tables/src/Filters/Concerns/HasDefaultState.php) | done | — | — |
| [`indicator()` / `indicateUsing()` / `getIndicators()` / `getIndicator()`](../../../packages/tables/src/Filters/Concerns/HasIndicators.php) | [`Concerns\HasIndicators`](../../../vendor/filament/tables/src/Filters/Concerns/HasIndicators.php) | done | — | [semantics](#a-string-array-key-becomes-the-chips-remove-field) |
| [`label()` / `getLabel()`](../../../packages/tables/src/Filters/Concerns/HasLabel.php) | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Filters/Concerns/HasLabel.php) | done | — | [diverges](#translatelabel-is-not-ported) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Filters/Concerns/HasLabel.php) | skipped | — | [diverges](#translatelabel-is-not-ported) · [translations.md](../translations.md#idiom) |
| [`name()` / `getName()`](../../../packages/tables/src/Filters/Concerns/HasName.php) | [`Concerns\HasName`](../../../vendor/filament/tables/src/Filters/Concerns/HasName.php) | done | — | — |
| [`schema()` / `modifyFormFieldUsing()` / `getSchemaComponents()` / `hasSchema()` / `getFormField()`](../../../packages/tables/src/Filters/Concerns/HasSchema.php) | [`Concerns\HasSchema`](../../../vendor/filament/tables/src/Filters/Concerns/HasSchema.php) | done | — | — |
| `form()` / `getFormSchema()` (deprecated; `schema()` / `getSchemaComponents()` replace them) | [`Concerns\HasSchema`](../../../vendor/filament/tables/src/Filters/Concerns/HasSchema.php) | skipped | — | [diverges](#the-form-alias-is-not-ported) |
| [`getSchema()` — the filter's live child schema](../../../packages/tables/src/Filters/Concerns/HasSchema.php) | [`Concerns\HasSchema::getSchema()`](../../../vendor/filament/tables/src/Filters/Concerns/HasSchema.php) | done | — | [semantics](#getschema-is-unguarded-where-the-state-readers-beside-it-are-not) |
| [`apply()` / `applyToBaseQuery()` / `query()` / `baseQuery()` / `modifyQueryUsing()` / `modifyBaseQueryUsing()` / `excludeWhenResolvingRecord()`](../../../packages/tables/src/Filters/Concerns/InteractsWithTableQuery.php) | [`Concerns\InteractsWithTableQuery`](../../../vendor/filament/tables/src/Filters/Concerns/InteractsWithTableQuery.php) | done | — | [diverges](#there-is-no-closure-where-group-around-apply) · [semantics](#isactive-is-an-opt-out-not-an-opt-in) |

## Semantics

### `getSchema()` is unguarded where the state readers beside it are not

`getSchema()` reads the filter's live child schema off `$this->getLiveComponent()->getTableFiltersForm()` with no `method_exists()` check, two files away from `BelongsToTable`'s `getState()` / `getFormState()`, which [carry one and throw when it fails](#the-state-readers-are-guarded-because-the-contract-does-not-declare-them).
The asymmetry is not an oversight and not a style drift: `getTableFiltersForm()` is [on the contract](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) and the two state readers are not, so `getLiveComponent()` — typed `InteractsWithTable` — answers the first call at level max and cannot answer the other two.
A guard here would be unreachable code guarding a declared method; a guard there is the only thing standing between a contract-satisfying host and a fatal.
Both disappear the same way, by the contract growing, and the rule for adding a guard is the same either way: check what the interface declares, not what the trait happens to supply.

### The span getters return raw closures

`getColumnSpan()` and `getColumnStart()` do not evaluate.
The values are forwarded verbatim to the schema `Group` the table builds per filter, and the `Group` evaluates them itself with its own injections; evaluating here would resolve them against the filter instead.

### A string array key becomes the chip's remove field

`indicateUsing()` may return a keyed map, and a **string** key is taken as the field name the chip removes — `['value' => 'Published']` produces an indicator whose `getRemoveField()` is `value`, which the indicators row turns into `removeTableFilter(filterName, field)`.
An integer key (a plain list) leaves the field null, so removing the chip clears the whole filter.

### `isActive` is an opt-out, not an opt-in

`apply()` and `applyToBaseQuery()` each consult `$data['isActive']` only when the key is present: `!(bool) ($data['isActive'] ?? true)`.
Both passes carry the same pair of guards, the hidden check included.
A filter whose schema has no `isActive` field — every `SelectFilter`, every custom `schema()` — therefore applies unconditionally once it has a query callback, while a checkbox `Filter` skips when unticked.
The indicator default runs the opposite polarity (`$state['isActive'] ?? false`), so an unticked filter shows no chip; that asymmetry is Filament's and is preserved.

The three guards run in a different **order** than Filament's, which checks `isHidden()` first and the callback second.
Ours probes for the callback first, so a filter that declares none never evaluates its visibility closure.
The outcome is identical either way — all three arms return the untouched builder — so the order is a cheapness choice, not a behavioural one.

## Divergences

### The name moves from the constructor to a setter

Filament's `final __construct(string $name)` is dropped, as everywhere else in the port — the resolver path takes no constructor arguments.
`make()` resolves the name (argument, then `static::getDefaultName()`), refuses a blank one with Filament's message shape, then `newInstance()` → `name()` → `configure()`, matching [`Column::make()`](column.md) exactly.

### The state readers are guarded because the contract does not declare them

`getState()` and `getFormState()` reach `getTableFilterState()` / `getTableFilterFormState()` on the host, and [`InteractsWithTable`](has-table.md) does not declare either: the contract grows only with what a shipped template reads off the host, and these are read from PHP.
So both are `method_exists()`-guarded and throw a `LogicException` naming [`HasTable`](has-table.md) when absent.
That is not defensive padding — `InteractsWithTable` is implementable by hand, and the alternative failure is a table that silently filters nothing.
PHPStan narrows the guarded call to `mixed`, which a private normalizer turns back into `array<string, mixed>`, so shipped source carries no inline ignore.
That last part is a **static** claim rather than a runtime guarantee: the normalizer casts each key with `(string)`, and PHP canonicalises a numeric string key straight back to an integer, so a bag that arrives keyed `0` stays keyed `0`.
The shipped host never produces that case — [`getTableFilterState()`](host-has-filters.md#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) reads a slice that is not field-keyed as `null`, so the normalizer sees `[]` — leaving the cast live only for a host that answers these methods by hand.
Nothing downstream reads a filter slice positionally — every key is a field name a schema wrote — so the declared shape is the one every consumer needs and the residue is unreachable in practice.

### There is no closure-where group around `apply()`

Filament wraps the whole apply pass in one `$query->where(function (Builder $q) …)` so a filter's internal `orWhere` cannot leak into the search or another filter.
DQL has no closure-where.
Both passes run sequentially on the one builder via `andWhere()` composition instead — correct for the AND-shaped mainstream, and a filter needing internal OR writes the idiomatic `$query->andWhere($query->expr()->orX(…))`.
The consumer footgun this leaves: a bare `->orWhere()` inside a filter callback widens the **whole** query, search included.
The base/apply split survives as ordering only.

### `translateLabel()` is not ported

The label derivation is `symfony/string`'s `u($name)->before('.')->kebab()->replace('-', ' ')->title()`, matching Filament's `str()->before('.')->kebab()->replace(['-','_'], ' ')->ucfirst()` output (`is_featured` → `Is featured`; `kebab()` already eats the underscore).
The `__()` pass and `translateLabel()` wait on toolkit-wide i18n, like the empty-state heading.

### The `form()` alias is not ported

Filament ships `form()` / `getFormSchema()` as deprecated aliases of `schema()` / `getSchemaComponents()`.
A deprecated Filament member [is not ported](../renames.md#a-deprecated-filament-member-is-not-ported), and the [renames table](../renames.md#renames) carries the row.
