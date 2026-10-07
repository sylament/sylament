---
description: 'How a column sorts, searches and fetch-joins: the sortable and searchable declarations and the query seams that apply them. (draft)'
ports:
  - vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php
  - vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php
owns:
  - packages/tables/src/Columns/Concerns/CanBeSearchable.php
  - packages/tables/src/Columns/Concerns/CanBeSortable.php
  - packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php
---
# InteractsWithTableQuery (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`applyEagerLoading()` — fetch-join the to-one chains a visible name reads through](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | [`InteractsWithTableQuery::applyEagerLoading()`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | done | — | three lines: the body is [`Query\RelationJoiner::fetchJoinPath()`](../../../packages/tables/src/Query/RelationJoiner.php), which a [`Group`](grouping.md) fetch-joins its own column through, so the two share an implementation rather than a copy. [diverges](#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality) · [has-table.md](has-table.md#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) |
| [`sortable()` / `isSortable()`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php) | [`Concerns\CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php) | done | — | — |
| [`searchable()` / `isSearchable()` / `isGloballySearchable()` / `isIndividuallySearchable()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`Concerns\CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) |
| [`getSortColumns()` / `getSearchColumns()`, `forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#the-list-getters-take-no-record-and-there-is-no-getdefaultcolumns-pair) · [semantics](#an-undeclared-sortable-has-no-sort-columns-where-an-undeclared-searchable-has-its-name) |
| [`splitIndividualSearchTerms()` / `shouldSplitIndividualSearchTerms()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | The tri-state governs how a column's **own** search input splits its term: `null` defers to the table's `splitSearchTerms()`, a bool overrides it. Read by [`applyColumnSearchesToTableQuery()`](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) alone — the global term keeps the table-level flag |
| [`applySort()`, `applySearchConstraint()` — the column's query application](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | done | — | [diverges](#sort-columns-apply-in-declared-order-where-filament-reverses-them) · [diverges](#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) · [has-query.md](has-query.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery) · [has-query.md](has-query.md#a-relation-search-is-a-correlated-exists-at-every-cardinality) |

## Semantics

### An undeclared `sortable()` has no sort columns, where an undeclared `searchable()` has its name

`getSortColumns()` and `getSearchColumns()` read the same way — `$this->sortColumns ?? [$this->getName()]` — but their properties start differently: `$sortColumns` is `[]` and `$searchColumns` is `null`.
So a column that never called `sortable()` answers `[]` and one that never called `searchable()` answers `[$this->getName()]`, and only the second falls through to the name.
That asymmetry is Filament's, property initialiser for property initialiser, and it is invisible in the getter that looks symmetric.

It is also harmless where a table reaches these getters, because it never reaches them on a column that declared nothing: a sort resolves its column through [`Table::getSortableVisibleColumn()`](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column), which returns nothing for a column that is not sortable, and a search reaches only the columns that answer `isGloballySearchable()` — or, one input at a time, `isIndividuallySearchable()`.
Where it shows is a column asked directly — a hand-built `Column` in a test, or a caller reaching past the table — where `applySort()` adds no `ORDER BY` at all rather than ordering by the column's own name.
Both arms are pinned in [`InteractsWithTableQueryTest`](../../../packages/tables/tests/Unit/Columns/Concerns/InteractsWithTableQueryTest.php) so the initialisers cannot be "tidied" into agreement without a failing test.

## Divergences

### A `Closure` condition on `searchable()` is evaluated, not filed as a search column

`Column::searchable()` takes `bool|array|string|Closure` as its condition, and Filament sorts the union with `is_bool($condition)`: the bool arm sets `$isSearchable` and clears the columns, and **everything else** — an array, a string, *and a closure* — goes to `Arr::wrap($condition)` in `$searchColumns` with `$isSearchable = true`.
So `searchable(fn () => false)` in Filament makes the column unconditionally searchable and files the closure as a search *column*, where it is later interpolated into a `LIKE` expression.
The condition is never evaluated.
Sylament tests `is_bool($condition) || $condition instanceof Closure` instead, so a closure lands in `$isSearchable` and `isSearchable()` evaluates it the way `CanBeSortable` already evaluates its own.

This is a vendor defect rather than a design difference, and the shape of the evidence is that Filament's *sibling* trait gets it right: `CanBeSortable` branches on `is_array($condition)`, so its closure reaches `$isSortable` and is evaluated by `isSortable()`.
The two traits are otherwise line-for-line twins.
The signature itself agrees with us — `Closure` is in the declared union, which it would not need to be if the intent were to accept a closure as a column name.

The port therefore fixes it at the cause, the way the [clone-map defect](../actions/action-group.md#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately) was fixed rather than mirrored.
The cost is that a Filament user who wrote `searchable($someClosure)` *expecting* a search column gets different behaviour — but that user's column was searching by a closure-shaped column name, which cannot have worked.
`ColumnTest` pins both directions, so the arm cannot regress into Filament's shape unnoticed.

### A to-many relation stays lazy, where Filament eager-loads every cardinality

Filament's `applyEagerLoading()` calls `$query->with([$relationshipName])` for any relationship prefix, to-many included: Eloquent's eager load is a **second query** matched onto the parents afterwards, so cardinality costs it nothing and two collection columns are two extra queries.
Doctrine's equivalent is a fetch join on the same query, where two collection columns are a cartesian product of their rows — so this port fetch-joins **to-one chains only** and lets a collection column initialise its `PersistentCollection` per row.

That is one query per row per collection column, and it is characterised rather than hidden: the [eager-loading test](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) counts the statements a to-many page issues and asserts the per-row number, so the cost is a pinned fact and a future change to it fails a test rather than passing unnoticed.
The alternatives were weighed and both are worse: an unconditional collection fetch join goes cartesian the moment a second to-many column is added, and a fetch-join-when-exactly-one rule makes the query shape flip on an unrelated column being declared.

The follow-up that closes it is named rather than implied — **batch priming**: one query per collection column per page, loading the related rows for the page's identifiers, then hydrating them into the roots' collections.
Doctrine has no public API that primes a `PersistentCollection` from rows the caller already holds, so it is manual hydration work and it is why the batch is not in v1.

### Sort columns apply in declared order, where Filament reverses them

`applySort()` walks `getSortColumns()` front to back, appending one `addOrderBy()` per name, so `sortable(['plays', 'title'])` orders by *plays* and breaks ties on *title*.
Filament walks the same list through `array_reverse()` (`foreach (array_reverse($this->getSortColumns($query->getModel())) as $sortColumn)`) onto Eloquent's `orderBy()`, which **appends** to `$this->orders` exactly as `addOrderBy()` appends to the DQL `orderBy` part.
Reversing the input to an appending sink inverts the priority: the same declaration sorts by *title* first over there, and `sortable(['first_name', 'last_name'])` — the natural way to write *sort people by name* — sorts by last name.

The reversal is the vendor's behaviour verified in its own source rather than an inference from ours, and nothing around it reads as intent: no comment, no compensating reversal at the call site, and Filament's own sibling `Filters` and `Summarizers` copies of `InteractsWithTableQuery` have no such loop to compare against (nor does [the port's summarizer copy](summarizer.md), which sorts nothing).
A user's list is written in the order they want it applied, so we apply it as written.
The cost is the one that matters and it is small and visible: a migrating table with a multi-column `sortable()` gets its tie-break priority the other way round on the first click.

The relation arm leaves this alone on purpose, though it is the change most likely to have quietly re-opened it.
Its work happens *inside* the loop — each name now resolves through [`resolveOrderable()`](has-query.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery) instead of `resolve()`, so a to-one path joins and orders by its alias — while the iteration stays front to back, so `sortable(['artist.name', 'title'])` orders by artist and breaks ties on title, the way the declaration reads.
An aggregate column is the one shape that never reaches the loop: it claims the sort only when `sortColumns` is `null` — nobody named a column — so a list of any length is still the user's own and is applied name by name.

### A search closure returns its expression, where Filament's mutates an `orWhere` group

Filament hands `searchable(query:)` a **nested builder**: it calls `$query->where(...)` for the first searchable column and `$query->orWhere(...)` for the rest, passing a closure that runs `$this->evaluate($this->searchQuery, …)` inside.
Eloquent opens a parenthesised sub-clause for that callback, so the user's closure adds `where`s to a group that is already `OR`-ed into the outer query, and its return value is discarded.

Doctrine has no equivalent shape.
`QueryBuilder::orWhere()` takes expressions rather than a callback, and no object exists that *is* one arm of a predicate being assembled.
So the port inverts the direction: the table assembles one `Doctrine\ORM\Query\Expr\Orx`, hands it to `applySearchConstraint()` alongside the builder, and the closure's **return** is what joins it — an `Expr` node or a raw DQL string, the `string|Stringable` pair `Orx::add()` declares (Doctrine narrows further at runtime, accepting only its own `Comparison`, `Func`, `Andx` and `Orx` nodes, and refusing anything else outright).
A closure that returns nothing is not an error: it is also handed the real `QueryBuilder`, so `andWhere()`-ing a constraint that must apply *outside* the `OR` group is the one thing Filament's nested-group shape cannot express and this one can.

Returning that builder is not an error either, and the distinction is the one sharp edge of the contract.
`QueryBuilder::andWhere()` returns `$this`, so `fn (QueryBuilder $query) => $query->andWhere(…)` — the natural arrow-function spelling of *constrain the query yourself* — hands back a builder whether or not its author thought about the return value.
That builder is **skipped**: its constraint is already on the query, adding it to the group would be a duplicate, and it is checked for by type *before* the `string|Stringable` test rather than being caught by it, because `QueryBuilder` implements `Stringable` (its `__toString()` is the DQL) and would otherwise sail into `Orx::add()` and die on the allow-list with a message about an expression type, naming nothing the author wrote.
The rule reads the same at the table's [extra searchable columns](can-search-records.md#extra-searchable-columns-take-closures-that-return-their-expression), which take the same kind of closure.

The three injections are Filament's, unchanged — `$query`, `$search` and its `$searchQuery` alias — and only the first differs in what it holds: the table's builder, not a nested group.
`$isFirst`, the by-reference flag Filament threads through the column loop to choose `where` versus `orWhere`, has nothing to track here and is gone: an `Orx` with one part renders as that part, so the first column is not a special case.

The return is read through the `protected evaluateSearchQuery()` seam rather than inline because [`evaluate()`](../../../packages/support/src/Concerns/EvaluatesClosures.php) is `@template T @param T|Closure`: handed a value typed *exactly* `Closure`, PHPStan binds `T` to `Closure` and calls the result the callback rather than what it returned, so every test of the value reads as impossible at level max.
A `mixed`-returning method takes it out of the generic.
The shorter fix — a `@var mixed` on the assignment — does not survive `composer format`, whose `no_superfluous_phpdoc_tags` deletes it.

### The list getters take no record, and there is no `getDefault*Columns()` pair

`getSortColumns()` and `getSearchColumns()` are `(): array<string>` where Filament's are `(Model $record): array<string>`.
The parameter exists there only to feed `getDefaultSortColumns($record)` / `getDefaultSearchColumns($record)`, each of which returns `[$this->getFullAttributeName($record)]` — the relationship-aware resolution [the traversal row](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) covers.

The relation layer has since landed and the pair did **not** come back, which is what makes this permanent rather than provisional.
The reason is where the resolution happens.
Filament resolves the name **into the list** — the getter hands back an already-qualified `artist.name` worked out against a model — so it needs a record to work it out from.
Here the list stays the user's own strings and each *consumer* resolves them: the sort through [`ColumnPathResolver::resolveOrderable()`](has-query.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery), which joins as it goes, and the search through [`SearchConstraint::forColumn()`](has-query.md#a-relation-search-is-a-correlated-exists-at-every-cardinality), which builds an `EXISTS` instead of a path at all.
Neither result *is* a column name — one carries a minted join alias, the other a whole predicate — so there is nothing a shared getter could return that both would want, and the record was never the missing ingredient: both resolutions read the **query**, which carries its own entity manager and root entity.

So the default stays `[$this->getName()]`, written into the `??` rather than split into a `getDefault*Columns()` method with one caller and nothing to derive.
The parameter would return only if some future consumer needed a per-record answer — a morph column, whose target class genuinely differs row by row, is the only shape that would — and it would be the query rather than a record, which is the port-wide [model → record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) landing on a signature that had no record to name in the first place.
