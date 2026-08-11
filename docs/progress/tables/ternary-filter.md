# TernaryFilter (tables)

[`Sylament\Tables\Filters\TernaryFilter`](../../../packages/tables/src/Filters/TernaryFilter.php)
— the yes / no / either filter: a [`SelectFilter`](select-filter.md) whose field
is a two-option boolean select and whose query pass is a three-arm dispatcher
installed as the filter's own `query()` callback. It ports
[`Filament\Tables\Filters\TernaryFilter`](../../../vendor/filament/tables/src/Filters/TernaryFilter.php).
Everything [`SelectFilter`](select-filter.md) carries — the field chain, the
attribute resolution, the active count, the parameter binding — is tracked
there.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Filters/TernaryFilter.php`](../../../vendor/filament/tables/src/Filters/TernaryFilter.php)
  — the labels, `getFormField()`, `queries()`, `boolean()`, `nullable()`, the
  `getDefaultState()` int cast and the indicator override

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`trueLabel()` / `falseLabel()` + getters](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::trueLabel()` | done | — | — |
| [`getFormField()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::getFormField()` | done | — | [semantics](#boolean-rebuilds-the-options-the-parent-chain-just-set) |
| [`queries()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::queries()` | done | — | [semantics](#the-dispatcher-is-installed-as-the-filters-own-query-callback) |
| [`boolean()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::boolean()` | in progress | — | [diverges](#the-arms-qualify-the-attribute-where-filaments-leave-it-bare) · [diverges](#the-whererelation-arms-are-deferred) |
| [`nullable()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::nullable()` | in progress | — | [diverges](#the-arms-qualify-the-attribute-where-filaments-leave-it-bare) · [diverges](#the-whererelation-arms-are-deferred) |
| [`getDefaultState()`](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::getDefaultState()` | done | — | [semantics](#the-state-is-1--0--null-not-true--false--null) |
| [indicator override](../../../packages/tables/src/Filters/TernaryFilter.php) | `TernaryFilter::setUp()` | done | — | [semantics](#the-indicator-reads-the-arm-label-not-the-option-map) |

Every built row is pinned by
[`TernaryFilterTest`](../../../packages/tables/tests/Unit/Filters/TernaryFilterTest.php),
which uses the `isFeatured` column added to the
[`Song` fixture](../../../packages/tables/tests/Fixtures/Doctrine/Entity/Song.php)
for this epic — nullable, so the three-state arms and the `nullable()` arms are
both reachable on one entity.

## Semantics

### `boolean()` rebuilds the options the parent chain just set

`getFormField()` is `parent::getFormField()->boolean(trueLabel:, falseLabel:,
placeholder:)`, and [`Select::boolean()`](../forms/select.md) re-calls
`options()` and `placeholder()` internally and installs a
`BooleanStateCast(isStoredAsInt: true)`. So the `options()` closure the parent
chain attached is overwritten on purpose, and a ternary filter's `options()` is
dead configuration. Named arguments are used at that call because the three
labels are not a positional run — a padded call would break silently the moment
`boolean()` grew a parameter.

### The dispatcher is installed as the filter's own `query()` callback

`queries()` does not override `apply()`. It calls `query()`, which makes
`SelectFilter::apply()` short-circuit at its `hasQueryModificationCallback()`
check straight into the base seam — so the ternary arms and a user's own
`query()` callback are the *same* slot, and the last one set wins. `setUp()`
calls `boolean()`, so a consumer's `queries()` or `nullable()` replaces the
default arms rather than stacking with them.

### The state is `1 | 0 | null`, not `true | false | null`

`Select::boolean()` keys its options `1` and `0` and stores them as ints, so the
wire state is `['value' => 1|0|null]`. `getDefaultState()` exists only to cast a
`bool` default into that vocabulary, leaving every other value untouched — and
because [`filled()`](../support/helpers.md) reports `0` as filled, a
`default(false)` still reaches the field as `0`. The blank arm is `null`, which
`blank()` alone separates from `0`.

### The indicator reads the arm label, not the option map

The `setUp()` override builds `"{indicator}: {trueLabel|falseLabel}"` directly
from the two label getters rather than looking the state up in the option map
the way `SelectFilter` does, and returns `[]` for a blank value. An already-built
`Indicator` still passes through verbatim, same rule as the parent.

## Divergences

### The arms qualify the attribute where Filament's leave it bare

Filament's `boolean()` and `nullable()` arms call
`$query->where($this->getAttribute(), …)` with the raw attribute, while
`SelectFilter::apply()` in the same package runs its column through
`qualifyColumn()` — an inconsistency that produces an ambiguous column the
moment the query joins. Ours routes both arm sets through
`SelectFilter::getAttributePath()`, i.e.
[`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php),
so a bare name is qualified with the root alias and a dotted path is checked
against the builder's aliases. A fix, not a mirror; recorded in the
[renames table](../renames.md) and pinned by the DQL assertions in
`TernaryFilterTest`.

### The `whereRelation` arms are deferred

Each of Filament's four arms opens with a `queriesRelationships()` check and
falls into `$query->whereRelation($relationshipName, $titleAttribute, …)`.
`whereRelation` has no Doctrine counterpart, and the whole relationship surface
of `SelectFilter` waits on the tables-relations epic
([select-filter.md](select-filter.md#the-relationship-arm-is-deferred-with-a-documented-escape-hatch)).
The same escape hatch applies: `attribute('artist.isFeatured')` over a query
that already joins `artist` reaches a related boolean today.
