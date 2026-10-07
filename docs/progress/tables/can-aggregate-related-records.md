---
description: The column's relationship aggregate declarations and the declaration its cell reads.
ports:
  - vendor/filament/support/src/Concerns/CanAggregateRelatedModels.php
owns:
  - packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php
---
# CanAggregateRelatedRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The aggregate declarations — `avg()`, `counts()`, `exists()`, `max()`, `min()`, `sum()` and their getters](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php) | [`Support\Concerns\CanAggregateRelatedModels`](../../../vendor/filament/support/src/Concerns/CanAggregateRelatedModels.php) | done | — | [rename](../renames.md#renames) · [diverges](#an-aggregate-column-names-itself-freely-where-filaments-must-match-laravels-alias) · [diverges](#the-illuminate-expression-arm-is-dropped-from-the-aggregated-column) · [diverges](#an-aggregate-declaration-that-claims-no-cell-computes-nothing) |
| [`getCellAggregateDeclaration()` / `hasRelationshipAggregates()`](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php) | — (Sylament-only) | done | — | [diverges](#an-aggregate-column-names-itself-freely-where-filaments-must-match-laravels-alias) · [has-query.md](has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) |

## Divergences

### An aggregate column names itself freely, where Filament's must match Laravel's alias

Filament's aggregate value arrives as a **model attribute**: `withCount('songs')` selects a subquery aliased `songs_count`, Eloquent hydrates it onto the model, and the cell reads it because the column is *named* `songs_count`.
The name is therefore not a choice — it is Laravel's derived alias, and a column named `track_total` reading `counts('songs')` displays nothing at all, with no error to say why.

Here the value never touches the record: it is fetched into a [side store](has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) keyed by the column's own name, so `TextColumn::make('track_total')->counts('songs')` works, and the name is free.
The derivation is still ported, because the **array** form declares several aggregates through one call and something has to say which of them fills this cell: there, an entry whose alias matches the column name wins, with `'relationship as alias'` honoured exactly as Laravel honours it.

The derivation differs from Laravel's in two ways, both because `UnicodeString::snake()` stands in for `Str::snake()` over a string Laravel pre-mangles.
Laravel strips every character that is not alphanumeric, space or underscore *before* snaking, so a dotted path loses its dot: `artist.label` derives `artistlabel_count` there and `artist_label_count` here.
And Laravel lowercases the aggregated column wholesale rather than snaking it, so `avg('songs', 'totalDuration')` derives `songs_avg_totalduration` there and `songs_avg_total_duration` here.
Both are strictly more readable, both only ever matter to the array form, and neither can silently mis-bind: a name that matches nothing resolves to no declaration rather than to the wrong one.

One asymmetry worth stating, because the string form accepts syntax it then ignores: `counts('songs as mySongs')` parses the alias and does nothing with it, since a single declaration binds to the column whatever the column is called.
The `as` clause is only meaningful in the array form, where it is how one of several entries claims a cell.

### An aggregate declaration that claims no cell computes nothing

`counts(['songs', 'awards as trophies'])` on one column declares two aggregates and the cell can display one.
Filament runs both regardless — `withCount()` puts both subqueries on the page query and hydrates both attributes onto every model, where the unread one sits available to any other column, a `formatStateUsing()` closure reaching for `$record->trophies`, or a row-scoped action.

Here an unread declaration is not merely unread, it is **never executed**: the host asks each column for the single declaration its cell binds to and batches that one only, so `trophies` above costs no query and exists nowhere.
That falls out of the side store rather than being a policy on top of it — a store is keyed by column name and a column has one cell, so there is no place for a second value to live.

Both halves of the trade are real.
What is lost is Filament's incidental side-channel, where declaring an aggregate on any column made it readable from everywhere; what is gained is that a declaration nobody displays costs nothing, where Filament pays a correlated subquery per page render for it.
The declaration is not an error either — it does not throw, because the array form is *designed* to be split across sibling columns and the ones aliased elsewhere simply belong to other cells.
Declaring an aggregate you then never display is the shape that gets silently ignored, and it is worth knowing before wondering why a query never appears in the log.

### The Illuminate `Expression` arm is dropped from the aggregated column

Filament types the aggregated column `string | Expression | Closure | null` on all four of `avg()`, `max()`, `min()` and `sum()`, so a caller can pass `DB::raw('price * quantity')` and aggregate an expression rather than a field.
Ours takes `string|Closure|null`.

There is no Doctrine referent to keep.
`Illuminate\Contracts\Database\Query\Expression` is a driver-aware value the grammar interpolates into SQL, and Doctrine's equivalent layer is DQL, where an arbitrary SQL fragment is not admissible at all — the parser would reject it, and the escape hatch for real SQL is a native query or a registered DQL function, neither of which is a value you hand to a setter.
Typing the parameter to accept one would promise a payload the aggregate builder could only interpolate blindly into a DQL string it then fails to parse.

What replaces it is the scope closure the array form already carries: an aggregate that needs more than a field name narrows its set with `counts(['songs' => fn (QueryBuilder $query, string $alias) => $query->andWhere(…)])`, which is DQL the parser accepts.
A genuinely computed aggregate — `SUM(price * quantity)` — is out of reach, and would arrive as a declared DQL expression rather than as a raw-SQL union member.
