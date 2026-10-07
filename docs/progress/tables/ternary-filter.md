---
description: 'The yes / no / either filter: a `SelectFilter` with a two-option boolean field and a three-arm query dispatcher.'
ports:
  - vendor/filament/tables/src/Filters/TernaryFilter.php
owns:
  - packages/tables/src/Filters/TernaryFilter.php
---
# TernaryFilter (tables)

[`Sylament\Tables\Filters\TernaryFilter`](../../../packages/tables/src/Filters/TernaryFilter.php) — the yes / no / either filter: a [`SelectFilter`](select-filter.md) whose field is a two-option boolean select and whose query pass is a three-arm dispatcher installed as the filter's own `query()` callback.
It ports [`Filament\Tables\Filters\TernaryFilter`](../../../vendor/filament/tables/src/Filters/TernaryFilter.php).
Everything [`SelectFilter`](select-filter.md) carries — the field chain, the attribute resolution, the active count, the parameter binding — is tracked there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`trueLabel()` / `falseLabel()` + getters](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::trueLabel()` | done | — | [diverges](#the-arm-labels-are-hardcoded-english) |
| [`getFormField()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::getFormField()` | done | — | [semantics](#boolean-rebuilds-the-options-the-parent-chain-just-set) |
| [`queries()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::queries()` | done | — | [semantics](#the-dispatcher-is-installed-as-the-filters-own-query-callback) |
| [`boolean()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::boolean()` | in progress | — | [detail](#the-whererelation-arms-are-deferred) · [diverges](#the-arms-qualify-the-attribute-where-filaments-leave-it-bare) |
| [`nullable()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::nullable()` | in progress | — | [detail](#the-whererelation-arms-are-deferred) · [diverges](#the-arms-qualify-the-attribute-where-filaments-leave-it-bare) |
| [`getDefaultState()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::getDefaultState()` | done | — | [semantics](#the-state-is-1--0--null-not-true--false--null) |
| [indicator override](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::setUp()` | done | — | [semantics](#the-indicator-reads-the-arm-label-not-the-option-map) |

## Details

### The `whereRelation` arms are deferred

Each of Filament's four arms opens with a `queriesRelationships()` check and falls into `$query->whereRelation($relationshipName, $titleAttribute, …)`.
`whereRelation` has no Doctrine counterpart, and the whole relationship surface of `SelectFilter` waits on a Doctrine design for the filters' relationship arm ([select-filter.md](select-filter.md#the-relationship-arm-is-deferred-with-a-documented-escape-hatch)).
`boolean()` and `nullable()` ship the attribute half of each arm and lack the relationship half.
The same escape hatch applies: `attribute('artist.isFeatured')` over a query that already joins `artist` reaches a related boolean.

## Semantics

### `boolean()` rebuilds the options the parent chain just set

`getFormField()` is `parent::getFormField()->boolean(trueLabel:, falseLabel:, placeholder:)`, and [`Select::boolean()`](../forms/select.md) re-calls `options()` and `placeholder()` internally and installs a `BooleanStateCast(isStoredAsInt: true)`.
So the `options()` closure the parent chain attached is overwritten on purpose, and a ternary filter's `options()` is dead configuration.

### The dispatcher is installed as the filter's own `query()` callback

`queries()` does not override `apply()`.
It calls `query()`, which makes `SelectFilter::apply()` short-circuit at its `hasQueryModificationCallback()` check straight into the base seam — so the ternary arms and a user's own `query()` callback are the *same* slot, and the last one set wins.
`setUp()` calls `boolean()`, so a consumer's `queries()` or `nullable()` replaces the default arms rather than stacking with them.

The **dispatcher** is what gets evaluated; the three arms do not.
It calls each one positionally — `$true($query, $data)` — so an arm takes the builder and the state by position and gets none of the `Invoker`'s injections: no named `data` / `query` / `state`, no container services, no `$filter`.
An arm needing anything else closes over it, which is exactly what `boolean()` and `nullable()` do with `$this`.
Filament's dispatcher calls its arms the same way.

### The state is `1 | 0 | null`, not `true | false | null`

`Select::boolean()` keys its options `1` and `0` and stores them as ints, so the wire state is `['value' => 1|0|null]`.
`getDefaultState()` exists only to cast a `bool` default into that vocabulary, leaving every other value untouched — and because [`filled()`](../support/helpers.md) reports `0` as filled, a `default(false)` still reaches the field as `0`.
The blank arm is `null`, which `blank()` alone separates from `0`.

### The indicator reads the arm label, not the option map

The `setUp()` override builds `"{indicator}: {trueLabel|falseLabel}"` directly from the two label getters rather than looking the state up in the option map the way `SelectFilter` does, and returns `[]` for a blank value.
An already-built `Indicator` still passes through verbatim, same rule as the parent.

## Divergences

### The arms qualify the attribute where Filament's leave it bare

Filament's `boolean()` and `nullable()` arms call `$query->where($this->getAttribute(), …)` with the raw attribute, while `SelectFilter::apply()` in the same package runs its column through `qualifyColumn()` — an inconsistency that produces an ambiguous column the moment the query joins.
Ours routes both arm sets through `SelectFilter::getAttributePath()`, i.e. [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php), so a bare name is qualified with the root alias and a dotted path is checked against the builder's aliases.
A fix, not a mirror; recorded in the [renames table](../renames.md) and pinned by the DQL assertions in `TernaryFilterTest`.

### The arm labels are hardcoded English

`setUp()` seeds `trueLabel('Yes')` and `falseLabel('No')` where Filament reads `__('filament-forms::components.select.boolean.true')` and its `false` twin.
The values are that catalogue's English ones, so the rendered chip and the two options match Filament byte for byte — but there is no catalogue to look them up in, the same [hardcoded-copy gap](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) the empty state and the search chip carry, and it lifts when toolkit-wide i18n ships.
The `'-'` placeholder is not part of it: Filament hardcodes that one too.
