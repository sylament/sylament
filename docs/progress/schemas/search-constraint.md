---
description: The Doctrine `LIKE` search predicate that forms, tables and actions share, folding case and casting non-character fields per platform.
ports: []
owns:
  - packages/schemas/src/Query/QueryAliases.php
  - packages/schemas/src/Query/SearchConstraint.php
---
# SearchConstraint (schemas)

[`Sylament\Schemas\Query\SearchConstraint`](../../../packages/schemas/src/Query/SearchConstraint.php) has **no Filament source**.
Filament's analogue is the `generate_search_column_expression()` / `generate_search_term_expression()` pair ([`vendor/filament/support/src/helpers.php:255-354`](../../../vendor/filament/support/src/helpers.php)), Eloquent-and-driver-specific string assembly with no DQL counterpart to lift.

Tables' [`Query\SearchConstraint`](../../../packages/tables/src/Query/SearchConstraint.php) keeps `forColumn()`, the relation-crossing dispatch a column's search resolves through, and a `like()` that delegates here ([can-search-records.md](../tables/can-search-records.md)).
`like()` lives in `schemas`, which `forms`, `tables` and `actions`, its three callers, all require.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`like()`](../../../packages/schemas/src/Query/SearchConstraint.php) — a parameter-bound `LIKE` or `LOWER(...) LIKE` comparison | — | done | — | [semantics](#case-insensitivity-defaults-off-the-querys-platform-not-a-fixed-choice) · [semantics](#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql) · [semantics](#a-search-term-is-not-escaped-for-like) |
| [`QueryAliases::classOf()` / `isFetchJoinedCollection()`](../../../packages/schemas/src/Query/QueryAliases.php) — the entity an alias stands for, and whether it is a fetch-joined collection | — | done | — | [semantics](#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql) |

## Semantics

### Case-insensitivity defaults off the query's platform, not a fixed choice

`$forceCaseInsensitive` is the caller's override; left `null`, the constraint asks `$query`'s own connection whether it is Postgres (`instanceof PostgreSQLPlatform`) and folds case only there — Postgres' `LIKE` is case-sensitive where MySQL and SQLite's are not, so the platform read keeps the same `searchable()` declaration matching consistently across backends without the caller naming one.
Each call also mints its own parameter name off `count($query->getParameters())` (`sylament_search_<n>`), so repeat calls against one builder — a multi-word search, or a search crossing several searchable columns — never collide.
Folding case is not casting: a field that holds no character data is cast separately, on PostgreSQL alone — [below](#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql).

### A field that holds no character data is cast to text on PostgreSQL

PostgreSQL compares a value with `LIKE` only once it is text, so on that platform alone `like()` wraps the path as `CONCAT(<path>, '')` — `(<column> || '')` in the SQL — whenever it resolves to a mapped field whose type is neither a `TextType` nor a `StringType` other than a `GuidType` (PostgreSQL's guid is a native `UUID`).
The folded arm becomes `LOWER(CONCAT(<path>, ''))`.
A character field keeps `LOWER(<path>)`, so an index on `lower(col)` still serves it, and MySQL and SQLite get the uncast path, because they compare any column as text.
Filament's analogue casts every column with `::text` on PostgreSQL, character columns included.
On PostgreSQL, `integer`, `smallint`, `bigint`, `decimal`, `float`, `boolean`, `guid`, `date`, `datetime`, `datetimetz`, `time` and `json` match through the cast; `jsonb`, `binary`/`blob` and arrays do not, because their own `||` operator claims the expression, and `searchable(query:)` is the way to search one.
A custom type that stores text without extending `StringType` is cast too and still matches, losing only the `lower(col)` index.
A boolean prints `true`/`false` on PostgreSQL where the other two print `1`/`0`.

The path resolves through its alias: a root or join alias of the builder through `QueryAliases::classOf()`, and a subselect's alias — which never reaches the builder — through the `$class` the caller hands in, as tables' `RelationConstraint` does with the last hop of its path.
A path that resolves to nothing — an expression, an alias nobody declared — is left exactly as it was.
The `''` literal is quoted through the connection when the DQL compiles, so a builder over a manager that must not connect cannot compile a cast search.
Tables' [`Query\JoinCardinality`](../../../packages/tables/src/Query/JoinCardinality.php) and [`Query\RelationJoiner`](../../../packages/tables/src/Query/RelationJoiner.php) each walk a builder's join part on their own, beside `QueryAliases`.

`CONCAT(<path>, '')` is the spelling because DQL has no cast to lift: `::text` is SQL a DQL string never reaches, and `CAST` is not a DQL function, the parser's built-in string functions being `concat`, `substring`, `trim`, `lower`, `upper` and `identity`, with anything else arriving through `Configuration::addCustomStringFunction()`, which the toolkit cannot assume a consumer has called.
Filament's helper also double-quotes each segment of the column name and spells a JSON path with `->`/`->>`; neither has a counterpart, because a DQL path is `alias.field` against mapped metadata and JSON access is not in the DQL grammar.
A search the cast cannot serve takes `searchable(query:)` on the column, which hands the closure the builder, or `searchUsing()` on the table, which replaces the whole assembly.

### A search term is not escaped for `LIKE`

The bound parameter is `'%' . $search . '%'`, so `%` and `_` typed by the user stay wildcards: searching `a_c` matches `abc`, and searching `%` matches every row with a non-null value in the searched column.
This is Filament's behaviour exactly: its `generate_search_term_expression()` lowercases and returns, and the call sites interpolate `"%{$search}%"`, so the port keeps it rather than changing what a migrating search matches.
It is a usability quirk and not an injection risk, because the term is a bound parameter on both sides, never concatenated into DQL.
Escaping it needs a `LIKE … ESCAPE` clause, which the DQL grammar supports, plus a per-platform choice of escape character, and it changes matching for every existing search, so it would land as an opt-in beside `forceSearchCaseInsensitive()`, not as a silent fix.
