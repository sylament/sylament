# SearchConstraint (schemas)

[`Sylament\Schemas\Query\SearchConstraint`](../../../packages/schemas/src/Query/SearchConstraint.php)
— a Sylament invention with **no Filament source**. Filament's analogue is the
`generate_search_column_expression()` / `generate_search_term_expression()`
pair
([`vendor/filament/support/src/helpers.php:242-338`](../../../vendor/filament/support/src/helpers.php)),
Eloquent-and-driver-specific string assembly with no DQL counterpart to lift.

It shipped first in `tables`
([table.md](../tables/table.md#the-default-search-comparison-assumes-a-string-typed-field)),
which still owns `forColumn()` (the relation-crossing dispatch a column's
search resolves through) and its own `like()` that now delegates here.
`like()` itself moved down in M3·4 so `forms`' relationship search arm can
reuse it: `forms` and `tables` are sibling packages that both build on
`schemas` and neither may depend on the other, so a helper both need moves
down to their shared ancestor rather than one borrowing the other's package.

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`like(QueryBuilder $query, string $path, string $search, ?bool $forceCaseInsensitive): Comparison`](../../../packages/schemas/src/Query/SearchConstraint.php) — a `LIKE` (or case-insensitive `LOWER(...) LIKE`) comparison, parameter-bound onto `$query` | done | — | [semantics](#case-insensitivity-defaults-off-the-querys-platform-not-a-fixed-choice) |

## Semantics

### Case-insensitivity defaults off the query's platform, not a fixed choice

`$forceCaseInsensitive` is the caller's override; left `null`, the constraint
asks `$query`'s own connection whether it is Postgres
(`instanceof PostgreSQLPlatform`) and folds case only there — Postgres' `LIKE`
is case-sensitive where MySQL and SQLite's are not, so the platform read keeps
the same `searchable()` declaration matching consistently across backends
without the caller naming one. Each call also mints its own parameter name off
`count($query->getParameters())` (`sylament_search_<n>`), so repeat calls
against one builder — a multi-word search, or a search crossing several
searchable columns — never collide. Folding case is not casting: on PostgreSQL
a field whose type is not a string is a driver error either way, which
[table.md](../tables/table.md#the-default-search-comparison-assumes-a-string-typed-field)
records, together with the escape hatch and the way forward.
