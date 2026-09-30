# SearchConstraint (schemas)

[`Sylament\Schemas\Query\SearchConstraint`](../../../packages/schemas/src/Query/SearchConstraint.php)
— a Sylament invention with **no Filament source**. Filament's analogue is the
`generate_search_column_expression()` / `generate_search_term_expression()`
pair
([`vendor/filament/support/src/helpers.php:242-338`](../../../vendor/filament/support/src/helpers.php)),
Eloquent-and-driver-specific string assembly with no DQL counterpart to lift.

It shipped first in `tables`
([table.md](../tables/table.md#a-non-string-searchable-field-is-cast-to-text-on-postgresql)),
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
| [`like(QueryBuilder $query, string $path, string $search, ?bool $forceCaseInsensitive, ?string $class = null): Comparison`](../../../packages/schemas/src/Query/SearchConstraint.php) — a `LIKE` (or case-insensitive `LOWER(...) LIKE`) comparison, parameter-bound onto `$query` | done | — | [semantics](#case-insensitivity-defaults-off-the-querys-platform-not-a-fixed-choice), [casts](#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql) |
| [`QueryAliases::classOf()` / `isFetchJoinedCollection()`](../../../packages/schemas/src/Query/QueryAliases.php) — the entity a builder alias stands for, and whether it is a fetch-joined collection | done | — | [casts](#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql) |

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
searchable columns — never collide. Folding case is not casting: a field that holds no character
data is cast separately, on PostgreSQL alone —
[below](#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql),
with the table-side story in
[table.md](../tables/table.md#a-non-string-searchable-field-is-cast-to-text-on-postgresql).

### A field that holds no character data is cast to text on PostgreSQL

PostgreSQL compares a value with `LIKE` only once it is text, so on that platform
alone `like()` wraps the path as `CONCAT(<path>, '')` — `(<column> || '')` in
the SQL — whenever it resolves to a mapped field whose type is neither a
`TextType` nor a `StringType` other than a `GuidType` (PostgreSQL's guid is a
native `UUID`). The folded arm becomes `LOWER(CONCAT(<path>, ''))`. A character
field keeps `LOWER(<path>)`, so an index on `lower(col)` still serves it, and
MySQL and SQLite emit exactly what they did, since they compare any column as
text. Executed on PostgreSQL 18 (M7 · 9): `integer`, `smallint`, `bigint`,
`decimal`, `float`, `boolean`, `guid`, `date`, `datetime`, `datetimetz`, `time`
and `json` all match through the cast; `jsonb`, `binary`/`blob` and arrays still
fail, as they did with no cast, because their own `||` operator claims the
expression — `searchable(query:)` is the way to search one. A custom type that
stores text without extending `StringType` is cast too and still matches, losing
only the `lower(col)` index. A boolean prints `true`/`false` on PostgreSQL where
the other two print `1`/`0`.

The path resolves through its alias: a root or join alias of the builder
through `QueryAliases::classOf()`, and a subselect's alias — which never reaches
the builder — through the `$class` the caller hands in, as tables'
`RelationConstraint` does with the last hop of its path. A path that resolves to
nothing — an expression, an alias nobody declared — is left exactly as it was.
The `''` literal is quoted through the connection when the DQL compiles, so a
builder over a manager that must not connect cannot compile a cast search.
`QueryAliases` is the third walk of a builder's join part, beside tables'
[`Query\JoinCardinality`](../../../packages/tables/src/Query/JoinCardinality.php)
and [`Query\RelationJoiner`](../../../packages/tables/src/Query/RelationJoiner.php),
which M7 · 9 left as they were; folding those two onto `QueryAliases` is a
follow-up.
