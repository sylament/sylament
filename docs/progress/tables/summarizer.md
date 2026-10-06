# Summarizers (tables)

The figures under a table: one row for the page on screen, one for the whole
filtered set, and one under each group. The tier is
[`Sylament\Tables\Columns\Summarizers\Summarizer`](../../../packages/tables/src/Columns/Summarizers/Summarizer.php)
plus the five concrete classes
([`Sum`](../../../packages/tables/src/Columns/Summarizers/Sum.php),
[`Average`](../../../packages/tables/src/Columns/Summarizers/Average.php),
[`Count`](../../../packages/tables/src/Columns/Summarizers/Count.php),
[`Range`](../../../packages/tables/src/Columns/Summarizers/Range.php),
[`Values`](../../../packages/tables/src/Columns/Summarizers/Values.php)), the six
concerns and one contract folded in with them, and everything that feeds them:
[`Columns\Concerns\CanBeSummarized`](../../../packages/tables/src/Columns/Concerns/CanBeSummarized.php)
on the column,
[`Table\Concerns\CanSummarizeRecords`](../../../packages/tables/src/Table/Concerns/CanSummarizeRecords.php)
on the table,
[`Concerns\CanSummarizeRecords`](../../../packages/tables/src/Concerns/CanSummarizeRecords.php)
on the host,
[`Query\SummaryQuery`](../../../packages/tables/src/Query/SummaryQuery.php) as
the read primitive, the
[`Summary`](../../../packages/tables/templates/components/Summary/index.html.twig)
and
[`SummaryRow`](../../../packages/tables/templates/components/SummaryRow/index.html.twig)
components with the three
[`partials/Summarizer/`](../../../packages/tables/templates/partials/Summarizer)
bodies under them, and
[`summary.css`](../../../packages/tables/assets/src/styles/components/summary.css).

Grouping — which record starts a group, which one ends
it, and the collapse the band renders under — is
[`grouping.md`](grouping.md). This file is the figures.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses). Three things are named and not built:
[`Count::icons()`](#counticons-ships-as-api-and-refuses-to-summarize), a
[summary over a `records()` table](#the-records-lane-is-refused-loudly-where-filaments-fatals-deep-in-summarize)
and the [batched `GROUP BY`](#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/Summarizers/Summarizer.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php)
  and its five subclasses
- [`tables/src/Columns/Summarizers/Concerns/`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns)
  — `BelongsToColumn`, `CanBeHidden`, `CanFormatState`, `HasLabel`,
  `InteractsWithTableQuery`
- [`tables/src/Columns/Concerns/CanBeSummarized.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php)
- [`tables/src/Table/Concerns/CanSummarizeRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php),
  [`tables/src/Concerns/CanSummarizeRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php)
- [`tables/resources/views/components/summary/index.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/index.blade.php)
  and [`row.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/row.blade.php)
- [`tables/resources/views/index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php)
  — the band's placement inside `<tbody>`, the grouped summary batch and the
  trailing-group probe
- [`tables/resources/css/columns/summaries/`](../../../vendor/filament/tables/resources/css/columns)

## Surface

Every point of the tier, from the summarizer up to the band. _Blocked by_ names
what has to land first. _Detail_ carries up to three links: **detail** → the
write-up in [Details](#details), deleted once the row is **done**; **diverges** →
the permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). A **skipped** row states its reason inline
instead.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Summarizer::make()` / `id()` / `getId()` / `using()`](../../../packages/tables/src/Columns/Summarizers/Summarizer.php) | [`Summarizers\Summarizer`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) | done | — | the class is concrete, as vendor's is — `Summarizer::make()->using(…)` is documented API — and a `using()` closure answers [ahead of both refusals its own message names](#summarizing-an-aggregate-column-is-refused-where-filament-produces-a-wrong-number) |
| [`getState()` / `getSelectedState()` / `hasSelectedState()` / `selectedState()`](../../../packages/tables/src/Columns/Summarizers/Summarizer.php) | [`Summarizers\Summarizer`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) | done | — | [semantics](#the-batch-short-circuit-asks-whether-the-alias-was-there-not-whether-the-value-is-filled) |
| [`summarize()` / `getSelectStatements()`](../../../packages/tables/src/Columns/Summarizers/Summarizer.php) | [`Summarizers\Summarizer`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) | done | — | [semantics](#emitting-no-select-statement-is-how-a-summarizer-opts-out-of-the-batch) |
| [`getExtraTemplateData()` — the formatted state, read once](../../../packages/tables/src/Columns/Summarizers/Summarizer.php) | [`Summarizer::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php)'s local | done | — | [semantics](#the-list-summary-formats-per-entry-where-the-range-formats-its-pair-whole) |
| [`Sum`](../../../packages/tables/src/Columns/Summarizers/Sum.php) / [`Average`](../../../packages/tables/src/Columns/Summarizers/Average.php) | [`Sum`](../../../vendor/filament/tables/src/Columns/Summarizers/Sum.php), [`Average`](../../../vendor/filament/tables/src/Columns/Summarizers/Average.php) | done | — | [semantics](#every-numeric-figure-is-normalised-once-at-the-summarizer-boundary) |
| [`Count`](../../../packages/tables/src/Columns/Summarizers/Count.php) | [`Count`](../../../vendor/filament/tables/src/Columns/Summarizers/Count.php) | in progress | — | [detail](#counticons-ships-as-api-and-refuses-to-summarize) · [diverges](#count-counts-the-column-on-both-paths-where-vendors-single-query-counts-rows) |
| [`Range` — `summarize()`, `excludeNull()`, `minimalDateTimeDifference()`, `minimalTextualDifference()`](../../../packages/tables/src/Columns/Summarizers/Range.php) | [`Range`](../../../vendor/filament/tables/src/Columns/Summarizers/Range.php) | done | — | [semantics](#the-list-summary-formats-per-entry-where-the-range-formats-its-pair-whole) · [has-default-data-formatting-settings.md](../support/has-default-data-formatting-settings.md) |
| [`Values` — `summarize()`, `bulleted()` / `isBulleted()`](../../../packages/tables/src/Columns/Summarizers/Values.php) | [`Values`](../../../vendor/filament/tables/src/Columns/Summarizers/Values.php) | done | — | [semantics](#a-list-summary-shows-the-drivers-own-values) |
| [`Concerns\BelongsToColumn` — `column()` / `getColumn()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Columns/Summarizers/Concerns/BelongsToColumn.php) | [`Summarizers\Concerns\BelongsToColumn`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/BelongsToColumn.php) | done | — | — |
| [`Concerns\CanBeHidden` — `hidden()` / `visible()` / `hiddenOn()` / `visibleOn()` / `isHidden()` / `isVisible()`](../../../packages/tables/src/Columns/Summarizers/Concerns/CanBeHidden.php) | [`Summarizers\Concerns\CanBeHidden`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/CanBeHidden.php) | done | — | [semantics](#the-visibility-cache-is-keyed-by-the-query-with-a-doctrine-key) |
| [`Concerns\CanFormatState` — `formatStateUsing()`, `limit()`, `prefix()` / `suffix()`, `placeholder()`, `money()`, `numeric()`, `html()`, `markdown()`, `formatState()` and their getters](../../../packages/tables/src/Columns/Summarizers/Concerns/CanFormatState.php) | [`Summarizers\Concerns\CanFormatState`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/CanFormatState.php) | done | — | [diverges](#the-formatting-trait-is-a-copy-of-the-columns-and-the-hoist-is-owed) · [text-column.md](text-column.md#markdown-throws-for-the-package-it-suggests-rather-than-falling-through) |
| [`Concerns\HasLabel` — `label()` / `getLabel()` / `getDefaultLabel()` / `hiddenLabel()` / `isLabelHidden()`](../../../packages/tables/src/Columns/Summarizers/Concerns/HasLabel.php) | [`Summarizers\Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/HasLabel.php) | done | — | [diverges](#hiddenlabel-is-honoured-in-all-three-markups-where-vendor-honours-it-in-one) |
| `translateLabel()` | [`Summarizers\Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/HasLabel.php) | skipped | — | It runs the label through Laravel's translator; i18n is parked whole for the toolkit, so there is nothing behind the flag to switch on ([renames.md](../renames.md#renames)) |
| [`Concerns\InteractsWithTableQuery` — `query()` / `getQuery()` / `hasQueryModification()`](../../../packages/tables/src/Columns/Summarizers/Concerns/InteractsWithTableQuery.php) | [`Summarizers\Concerns\InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/InteractsWithTableQuery.php) | done | — | [semantics](#one-query-setter-carries-two-slots-told-apart-by-type) |
| [`Concerns\HasSelectAlias` + `Contracts\HasSelectAlias` — `selectAlias()` / `getSelectAlias()` / `getSelectedState()` / `hasSelectedState()`](../../../packages/tables/src/Columns/Summarizers/Concerns/HasSelectAlias.php) | the same trio copied into [`Sum`](../../../vendor/filament/tables/src/Columns/Summarizers/Sum.php), [`Average`](../../../vendor/filament/tables/src/Columns/Summarizers/Average.php) and [`Count`](../../../vendor/filament/tables/src/Columns/Summarizers/Count.php) | done | — | [diverges](#the-alias-trio-is-one-trait-behind-a-marker-interface-where-filament-copies-it-three-times) |
| [`Column::summarize()` / `getSummarizer()` / `getSummarizers()` / `hasSummary()`](../../../packages/tables/src/Columns/Concerns/CanBeSummarized.php) | [`Columns\Concerns\CanBeSummarized`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | done | — | [semantics](#getsummarizers-re-points-every-summarizer-and-that-mutation-is-the-mechanism) · [diverges](#summarizing-an-aggregate-column-is-refused-where-filament-produces-a-wrong-number) |
| [`Table::summaries()` / `hasPageSummary()` / `hasAllTableSummary()` / `hasSummary()`](../../../packages/tables/src/Table/Concerns/CanSummarizeRecords.php) | [`Table\Concerns\CanSummarizeRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | done | — | [semantics](#hassummary-walks-every-column-while-the-batch-walks-the-visible-ones) |
| [`getAllTableSummaryQuery()` / `getPageTableSummaryQuery()`](../../../packages/tables/src/Concerns/CanSummarizeRecords.php) | [`Concerns\CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | done | — | [semantics](#a-page-summary-narrows-by-keys-never-by-a-limit) · [has-table.md](has-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| [`getGroupedTableSummaryQuery()`](../../../packages/tables/src/Concerns/CanSummarizeRecords.php) | — (Sylament-only; Filament batches one `GROUP BY` instead) | done | — | [diverges](#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by) · [renames.md](../renames.md#names-with-no-filament-source) |
| [`getTableSummarySelectedState()` — the one batched SELECT](../../../packages/tables/src/Concerns/CanSummarizeRecords.php) | [`Concerns\CanSummarizeRecords::getTableSummarySelectedState()`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | done | — | [semantics](#the-alias-is-pushed-before-the-statements-are-read-and-the-ordinal-is-one-per-declared-summarizer) · [diverges](#the-batch-keeps-a-to-one-relation-column-and-refuses-a-to-many-one) |
| `getTableSummarySelectedState()`'s `$modifyQueryUsing` parameter | [`Concerns\CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | skipped | — | [diverges](#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by) — its only caller upstream is the grouped `GROUP BY` batch the per-group query replaces |
| [`shouldRenderTrailingGroupedTableSummary()`](../../../packages/tables/src/Concerns/CanSummarizeRecords.php) | [`Concerns\CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | done | — | [semantics](#the-trailing-group-is-followed-past-the-page-break-with-a-record-not-a-scalar) · [grouping.md](grouping.md#the-group-boundaries-are-one-php-map-rather-than-loop-carried-template-state) |
| [`Query\SummaryQuery::readRow()` / `readDistinct()` / `qualifyAttribute()`](../../../packages/tables/src/Query/SummaryQuery.php) | [the `->select()` / `->get()` calls inside](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) each summarizer | done | — | [semantics](#every-summary-read-is-one-clone-with-the-order-reset-and-the-paging-cleared) · [diverges](#a-to-many-join-on-the-summarized-query-is-refused-where-filament-inflates-in-silence) · [renames.md](../renames.md#namespaces-with-no-filament-source) |
| [`Summary` — the band's header row and the two rows under it](../../../packages/tables/templates/components/Summary/index.html.twig) | [`views/components/summary/index.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/index.blade.php) | done | — | [diverges](#the-band-is-handed-the-real-all-table-query-even-when-the-all-table-row-is-off) · [semantics](#the-placeholder-cells-follow-the-record-rows-order-not-vendors) |
| [`SummaryRow` — one row: a band row or a group boundary's](../../../packages/tables/templates/components/SummaryRow/index.html.twig) | [`views/components/summary/row.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/row.blade.php) | done | — | [diverges](#every-summary-cell-carries-its-columns-responsive-pair-where-vendors-carries-none) · [semantics](#the-heading-cell-spans-to-the-first-summarized-column-and-drops-a-summarizer-on-the-first-one) |
| [The three summarizer bodies — `index`, `range`, `values`](../../../packages/tables/templates/partials/Summarizer) + [`summary.css`](../../../packages/tables/assets/src/styles/components/summary.css) | [`Summarizer::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) and its two overrides, [`columns/summaries/`](../../../vendor/filament/tables/resources/css/columns) | done | — | [diverges](#hiddenlabel-is-honoured-in-all-three-markups-where-vendor-honours-it-in-one) |
| [The band's placement — inside `<tbody>`, after the record loop, gated on the summary and on the mode](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `<x-filament-tables::summary>`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [semantics](#the-page-row-is-what-the-query-proves-not-what-the-paginator-claims) · [semantics](#a-group-with-no-heading-closes-with-no-summary-row-except-under-groupsonly) · [diverges](#every-row-of-the-band-carries-a-host-scoped-id) |
| `Count::icons()`'s per-icon counts, its template and its colour policy | [`Count::summarize()`](../../../vendor/filament/tables/src/Columns/Summarizers/Count.php)'s icon arm | todo | — | [detail](#counticons-ships-as-api-and-refuses-to-summarize) |
| A summary over a `records()` table | [`Summarizer::getState()`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) (which fatals there) | todo | — | [diverges](#the-records-lane-is-refused-loudly-where-filaments-fatals-deep-in-summarize) |
| The batched `GROUP BY` for per-group figures | [`index.blade.php`'s grouped summary batch](../../../vendor/filament/tables/resources/views/index.blade.php) | todo | — | [diverges](#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by) |
| The summary rows' stacked-on-mobile variants | [`columns/summaries/`](../../../vendor/filament/tables/resources/css/columns) | blocked | the layout columns (`Columns\Layout`) | They are the responsive half of the card rendering, with nothing to stack until `content()` has a list ([table.md](table.md#surface)) |

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

### `Count::icons()` ships as API and refuses to summarize

`icons()` and `hasIcons()` are on `Count` with Filament's signatures, and a
counting `Count` behaves exactly as upstream. What an icon count *does* is not
built: vendor runs one `COUNT` per distinct icon an
[`IconColumn`](icon-column.md) would render, renders them through an
`icon-count` view with a colour policy of its own, and pays `1 + N` queries for
it. `summarize()` therefore throws a `LogicException` naming `IconColumn` and
`Summarizer::using()`, and `getSelectStatements()` returns `[]` so the batch
skips it rather than aliasing a figure nothing will read.

The column is not what holds it back: `IconColumn` and its contrast policy
ship. What is left is scope — the template, a second policy under
`Colors/Components/` and the per-icon query fan-out are a feature rather than a
translation, and no consumer has asked for one.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### `hasSummary()` walks every column while the batch walks the visible ones

`Table::hasSummary()` reads `getColumns()` — every declared column, whether or
not the user toggled it off — so a hidden summarized column keeps the band on
screen. `getTableSummarySelectedState()` reads
[`getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again),
so the hidden column contributes no aggregate and gets no cell. Both are
vendor's own sets, at vendor's own call sites, and the asymmetry is deliberate
rather than an oversight to reconcile: the band's *existence* is a property of
the table, and its *cells* are a property of what is on screen. The band derives
its columns from the partial's visible list, so a column hidden by the
[column manager](column-manager.md) disappears from the summary rows exactly as
it does from the record rows.

### The batch short-circuit asks whether the alias was there, not whether the value is filled

Filament gates its short-circuit on `filled($this->getSelectedState())`, so a
`SUM` or `AVG` that legitimately came back `NULL` over an empty set fails the
test and re-runs its own query to arrive at the same `NULL`. The base's
`getState()` gates on `hasSelectedState()` instead — an `array_key_exists()` on
the alias, which is the distinction the three batchable classes were already
drawing — so *absent* and *null* stop meaning the same thing and an empty set
costs one query rather than two. The public getters keep their names and their
`null`.

`hasSelectedState()` reads the **raw** alias property rather than
`getSelectAlias()`, which mints a per-instance fallback: a summarizer the batch
never pushed an alias onto has no figure in the row, and minting one to look up
would ask for a key that cannot be there.

### Emitting no select statement is how a summarizer opts out of the batch

`getSelectStatements()` returns `[]` on the base, and `Range` and `Values` never
override it. That is the opt-out, not an omission: a range carries two figures
where the batch keys one alias to one value, and a distinct list is not an
aggregate at all. Both therefore run one query each per summary row — the page
row, the all-table row and every group row — which is upstream's cost too. A
summarizer that [scopes the query itself](#one-query-setter-carries-two-slots-told-apart-by-type)
is excluded for a different reason: the batch's figure is over the unscoped set,
so it would be the wrong number.

### The alias is pushed before the statements are read, and the ordinal is one per declared summarizer

`getTableSummarySelectedState()` calls
`$summarizer->selectAlias('sy_summary_' . $ordinal)` **before**
`getSelectStatements()`, so the alias the SELECT carries is the one the summarizer
looks its own figure up under. Filament mints its alias lazily inside
`getSelectAlias()` with `Str::random()`, which would leave every lookup here
missing the row it is reading. The names are deterministic for two further
reasons: a DQL result variable cannot start with a digit, which a random string
can, and a reproducible select list is what a test can assert.

The counter advances once per summarizer **pushed**, not once per statement
emitted — because a summarizer can pass the gate and emit nothing (`Count` under
`icons()` does), and an ordinal that advanced per statement would hand the next
summarizer the same alias and let one read the other's figure.

What it counts is the **declared** summarizers of every visible column, not the
ones this particular query batches — the two gates that skip an ordinal
(`HasSelectAlias`, `hasQueryModification()`) are properties of the summarizer,
and the one that does not (`isVisible()`) is a property of the query. That
distinction is the whole of it, because one render fires several batches: the
partial computes the page and all-table states in its preamble, the record loop
then fires one batch per group on the page, and only afterwards does the band
draw the page and all-table rows from the states it computed first. A
`hidden(fn ($query) => …)` summarizer — a supported, documented predicate — is
visible in one of those batches and not in another, so an ordinal counted over
the visible set alone would re-mint a later batch's aliases onto the names an
earlier batch's row is about to read, and a labelled cell would publish its
neighbour's figure. Filament cannot hit this because its alias is
`$this->selectAlias ??= Str::random()`, minted once per instance; the port's
answer is the same identity by construction rather than by chance. The
[all-table row's figures](../../../packages/tables/tests/Integration/SummaryRenderingTest.php)
under exactly that configuration are what pin it.

### `getSummarizers()` re-points every summarizer, and that mutation is the mechanism

`Column::getSummarizers($query)` calls `$summarizer->query($query)` on each one
and filters out the hidden ones — it mutates rather than copies, deliberately and
as upstream. That re-point is *how* a row of the band tells its summarizers which
dataset this row is over: the same summarizer instance answers for the page
query, then the all-table query, then one query per group, in a single render. A
reader who assumes the getter is pure will misread the band.

One consequence rides along: the grouped boundary map calls
`Table::hasSummary($allQuery)` while it walks, so by the time the partial starts
drawing, every summarizer is already pointed at the all-table query. Nothing
depends on that — the band re-points them per row before reading a figure — but
nothing may assume a summarizer arrives un-pointed either.

### One `query()` setter carries two slots, told apart by type

`query(QueryBuilder|Closure|null)` is Filament's one setter for two jobs: a
builder **points** the summarizer at a dataset, a closure **scopes** it, and
`null` clears both. `hasQueryModification()` asks only about the closure, which
is what keeps a scoped summarizer out of the batch while a re-pointed one stays
in it.

### Every summary read is one clone, with the order reset and the paging cleared

`SummaryQuery::readRow()` and `readDistinct()` both run on a clone of the table
query with `resetDQLPart('orderBy')`, `setFirstResult(0)` and
`setMaxResults(null)`, and with the select replaced — the joins stay, so an
aggregate can name a joined alias, while the fetch-join selects go with the old
list. Each of the three is load-bearing. A leftover `ORDER BY` over an aggregate
is a PostgreSQL error. A leftover `LIMIT` bounds the aggregate's own **result
row** rather than the aggregated set, so a page summary written that way silently
reports the all-table figure. And the clone is what keeps the stored builder
untouched, so the row that asks first does not narrow the row that asks next.

`readRow()` reads a single result rather than a guarded list because an ungrouped
aggregate returns exactly one row over any set, the empty one included.

### A page summary narrows by keys, never by a limit

`getPageTableSummaryQuery()` adds `root.<id> IN (:sylament_summary_keys)` over the
keys the paginator already resolved — the shape the
[aggregate batch](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)
uses. It answers `null` in three shapes: off the query lane, on an unpaginated
table, and on a page holding no records, whose empty key list would narrow to
`1 = 0` and publish a figure over nothing in place of no figure at all. The page
must be resolved before the query can be built, which is Filament's dependency
too.

A composite identifier narrows the same way, through
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
under `sylament_summary_keys`: one balanced OR of per-field ANDs where a
single-identifier table emits `root.<id> IN (…)`. An **unguarded**
`getSingleIdentifierFieldName()` here would throw a raw Doctrine
`MappingException` out of a getter mid-render for every summarized composite
table, because `HasTable` keys the page rather than refusing it. The all-table row still reads its figure there — off
`getAllTableSummaryQuery()`, which is `getFilteredTableQuery()` and nothing
else, so *All &lt;records&gt;* means the whole **filtered and searched** set and
not the whole table. A search narrowing five rows to three moves both figures.

The bind has that shape even for a **single-identifier** table: the DQL text
is the plain `IN`, but the keys are converted to the identifier's own PHP type
and bound under an `ArrayParameterType`, so anyone reading the parameter back
sees `[3, 4]`, not `['3', '4']`. The
[aggregate batch](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)
stands on that same footing under its own base, `sylament_agg_keys`, and is
chunked besides, which makes it the third site in the toolkit that chunks and the only one that is not part of
a **write**; the by-key delete and the reorder's re-resolution are the other
two.

Doctrine's **fetch-join** paginator pages such a root through a **single
identifier column** and leaks the fact two different ways: a scalar-pair root
reaches `RootTypeWalker`'s `getSingleIdentifierFieldName()` and surfaces a raw
`MappingException` mid-render, while a derived identity fails earlier still,
inside `LimitSubqueryOutputWalker`, which re-emits the identifier tiebreak's
`ORDER BY root.<association>` as a column its own derived table does not carry
and ends the render in a driver error instead — on every platform outside that
walker's `ROW_NUMBER` branch, which is SQLite here and MySQL and MariaDB in
production. (The scalar-pair half is platform-independent; only the derived
identity's carries that qualifier.) So this
look-ahead and
[`paginateTableQuery()`](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half)
read their window through **one** private method on `CanPaginateRecords`, and
that method routes a keyed root whose query joins a collection into
[a two-step read tables runs itself](has-table.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column)
while every other table keeps Doctrine's paginator byte for byte. Writing the
decision once is what the look-ahead earns: a page is built before its
successor is probed, over the same query, and the page's joins are always a
superset of the probe's, so two copies of the test would be free to disagree
about the same builder. The window of one is taken on a **clone**, because that
read windows the builder it is handed.

"Byte for byte" is the page's claim, not this probe's: over a query that joins
**no** collection the look-ahead is Doctrine's paginator with
`fetchJoinCollection` false rather than a plain `getResult()` — the same single
statement with the same binds on a single-identifier root, but through a
paginator.

An **unpaged** table never reaches any of it: `paginated(false)` and a
`recordsPerPage` of `'all'` both return before a Doctrine paginator is built —
`'all'` does build support's own carrier over the rows it read.

One caveat that reads worse than it is: the page query narrows by every key the
page holds, and under `recordsPerPage: 'all'` that would be every key on the
table, against a DQL parse time superlinear in key count — see
[the chunk rule](../schemas/record-key.md#the-chunk-rule). Nothing reaches it.
`hasPages()` is false whenever one page holds the whole filtered set — under
`'all'`, and on any single-page table — the partial asks for the page query
only when it is true, and the method itself answers the
**un-narrowed** filtered query in exactly that shape. One page means the page
*is* the filtered set, so the predicate only ever reproduced what it started
from, at a cost of one bound parameter per key per identifier field. The guard
sits **below** the empty-page guard and not above it, and the ordering is the
whole of it: an empty table reports one page too, and it owes `null` rather
than a figure over everything.

### The page row is what the query proves, not what the paginator claims

The partial asks for the page query only when a page row is wanted at all, and
then treats *the query being null* as the answer to whether the row renders. The
two can disagree: on a page past the last one the paginator still reports pages
while the page itself holds no records, so the query narrows to nothing and
answers `null`. Reading the paginator alone there would render a page row whose
every summarizer runs its own query over a null dataset — which on a
`records()`-shaped column is the [loud refusal](#the-records-lane-is-refused-loudly-where-filaments-fatals-deep-in-summarize)
firing on a page that merely overflowed.

The band as a whole is gated `not isReordering` in exactly one place — the
`hasSummary` flag every read and every row derives from — so a drag renders no
row and, more to the point, asks for no figure.

### Every numeric figure is normalised once, at the summarizer boundary

DQL types `SUM`/`AVG`/`MIN`/`MAX` as `'string'` and converts none of them —
`COUNT` is the only aggregate it casts — so PostgreSQL hands back
`"82.5000000000000000"` where SQLite hands back `82.5`. `Sum`, `Average`,
`Count` and `Range`'s numeric ends run one normalisation: an integral numeric
string becomes an `int`, anything else numeric a `float`. A `Range` over dates
keeps the driver's own string and parses it in `minimalDateTimeDifference()`.

The caveat that comes with `float` is worth stating: a `decimal` sum beyond
about fifteen significant digits loses precision at that cast. The decimal-string
path belongs to [`money()`](text-column.md), which formats the string it is given.

### The list summary formats per entry where the range formats its pair whole

`Summarizer::getExtraTemplateData()` hands the template
`formatState(getState())`, and two of the three bodies read exactly that: the
text one prints the formatted scalar, and the range one prints `state[0]` and
`state[1]` — the pair after `formatState()` ran over it **whole**, because
`minimalDateTimeDifference()` and `minimalTextualDifference()` are themselves
`formatStateUsing()` closures typed `array $state`. `Values` overrides the
template data to the **raw** list and the values body calls
`component.formatState(value)` per entry.

That split is vendor's, verified in both views, and it is what keeps a user's
`formatStateUsing(fn (string $state) => …)` — the demo's status card — looking at
one value rather than at a list. The other half of the same fact: a scalar-typed
closure on a `Range` is a `TypeError` here exactly as it is upstream, because a
range's closure is handed the pair.

### A list summary shows the driver's own values

`Values::summarize()` is one `SELECT DISTINCT` read through
`getSingleColumnResult()`, so an enum column lists backing values and a date
column lists the driver's own strings. That is parity rather than a shortcut:
Filament plucks through the base query builder, where no Eloquent cast runs, and
its view `e()`s each item, which unwraps a `BackedEnum` to its value. Typed
hydration was measured and rejected for this row — it would hand the template
`bool`s, `DateTimeImmutable`s and enum cases with no formatter that renders an
object. `formatStateUsing()` is the hook, and it sees one entry at a time.

The distinct result carries no order of its own, which is upstream's behaviour
too; a test comparing lists sorts them first.

### The visibility cache is keyed by the query, with a Doctrine key

`hidden(fn ($query) => …)` is a documented predicate that hits the database, and
one summarizer is re-pointed at up to `2 + G` queries in a single render — so the
answer is cached **per query** rather than per summarizer, as upstream. Filament
hashes `toRawSql()`; the Doctrine key is `md5()` over the DQL, the bound
parameters sorted by name, and the first/max results. Object parameter values are
keyed by `spl_object_id()`, since a record bound as a parameter is what a
relation-scoped query carries and serializing one would walk its whole graph —
so two equal-but-distinct instances hash differently and the predicate runs
twice. Conservative in the only direction that is safe: the cache can never
answer a stale figure.

### The trailing group is followed past the page break, with a record, not a scalar

`shouldRenderTrailingGroupedTableSummary()` decides whether the page's last
record closes its group or the group continues onto the next page — where a
summary row would put a partial figure under a heading naming the whole group.
Four shapes answer without touching the database, in Filament's own order: no
record; no next page (which covers an unpaginated table, the last page, and a
page holding everything); nothing grouped; or no query to look ahead in. Only
what is left pays, and it pays once — the boundary map that calls it is
[memoised](grouping.md#the-group-boundaries-are-one-php-map-rather-than-loop-carried-template-state),
and on an unsummarized table it is never called at all.

The look-ahead hydrates the **record** at `page × perPage` and compares
`getStringKey()` on both sides. A scalar key probe was considered and not built:
the key may be a `getKeyFromRecordUsing()` closure's answer, and that closure
needs a record. A window of one is a window over *rows*, and a to-many fetch
join makes rows and records different things, so the window is taken the way a
whole page is: through the one private method on `CanPaginateRecords` that
`paginateTableQuery()` reads its own page with, on a **clone**, because that
read windows the builder it is handed. Which of the two routes it takes —
Doctrine's own paginator, or
[the two-step read tables runs itself](has-table.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column)
— is that method's decision and not this one's, which is the point of writing
it once.

Its exactness inherits the sort's: with the identifier tiebreaker on, "the record
at offset N" is deterministic and agrees with the page it looks past. Under
`defaultKeySort(false)` there is no total order and the probe can disagree —
inherited from the sort lane, and still strictly better than Filament, which has
no tiebreaker at all. One arm is unreachable rather than untested: the `'all'`
per-page conjunct, because a query-lane `'all'` paginator carries a total, so
`hasMorePages()` answers false two guards earlier. It is kept because the
signature it guards is real. Vendor's fifth arm, the `CursorPaginator` branch at
[`CanSummarizeRecords:46-55`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php),
has no counterpart here at all: `PaginationMode::Cursor` throws in
[`paginateTableQuery()`](../../../packages/tables/src/Concerns/CanPaginateRecords.php), so
the mode the arm exists for cannot reach this method — it is skipped on the
pagination row's own grounds.

### A group with no heading closes with no summary row, except under `groupsOnly()`

Vendor gates every per-group summary row — the in-loop one and the trailing one,
in both of its layouts — on `filled($previousRecordGroupTitle)`, so the blank
group gets a header row and no figure under it. The port gates the same way, and
for the same reason: the heading a per-group row carries is
`<title> summary`, which for the blank group would read as a bare `" summary"`
naming nothing.

The one arm that is the port's own is `groupsOnly()`. There the per-group row
**is** the group — it carries the title in place of a header row that is not
rendered at all — so it is emitted whether or not the title is filled, and the
blank group keeps its row. Gating it there would take a whole group off the page
rather than take a heading off a figure. Vendor's groups-only branch is a
different rendering (a distinct-key group query, [still a row here](#surface)),
so this is a new arm rather than a delta against one of theirs.

### The placeholder cells follow the record row's order, not vendor's

A summary row emits its leading and trailing placeholder cells in the **record
row's** order — `actionsBeforeCells`, `checkboxLeading`, `actionsBeforeColumns`,
then the columns, then `actionsAfterColumns`, `checkboxTrailing`,
`actionsAfterCells`. Vendor emits *actions then checkbox* on both sides, which
lands one column off under `BeforeColumns` + `BeforeCells` and under
`AfterCells` + `AfterCells`. The eight combinations of the two action positions
and the two checkbox positions are asserted cell-for-cell against the `thead`.

The six booleans replace vendor's two position enums for the same reason the
record row's own gates are booleans: the partial has already resolved which cells
exist, and passing the resolved answer keeps one arithmetic in one place.

### The heading cell spans to the first summarized column, and drops a summarizer on the first one

The row's leading `<th scope="row">` spans from the first column up to the first
one that has a summarizer for this query — so a summarizer declared on the
**first** column is absorbed by the heading cell and never renders. That is
vendor's arithmetic exactly, and it is worth knowing before writing a fixture: a
`Range` on column one shows nothing until the row has a heading cell of its own
(which `groupsOnly()` and the content layout's `extraHeadingColumn` give it).

`<th scope="row">` is on the heading cell and `<th scope="col">` only on
*labelled* header cells; an empty placeholder in the header row stays a `<td>` so
a screen reader announces no blank column header. Both are vendor's own a11y
notes, ported with their reasons.

## Divergences

Deliberate deviations from Filament, each phrased as a statement and addressable
by anchor.

### The per-group figure is one scoped query per group, not one `GROUP BY`

Filament computes every group's figures in a single `GROUP BY` batch, re-rooted
through [`Group::groupQuery()`](grouping.md#groupquery-is-superseded-by-the-per-group-scoped-query)
and narrowed through `getTableSummarySelectedState()`'s `$modifyQueryUsing`
parameter. The port runs `getGroupedTableSummaryQuery($record)` per group instead
— a fresh clone of the all-table query, scoped by `Group::scopeQuery()` — which
costs `G − 1` more statements for a page of `G` groups (and `G` is bounded by the
page size).

The batch was measured and refused on three counts, not on taste: the SQL group
key would have to string-equal `Group::getStringKey()` for five different kinds
of group and does not, a `date()` group needs the `DATE()` DQL has not got, and a
relationship group needs join-splicing the batch has no place for. The per-group
query has none of those problems and honours `scopeQueryUsing()` on the same path
the [group header](grouping.md) resolved its own key with, so the figure and the
heading over it cannot disagree. The batched shape returns if a consuming
application ever configures a `DATE()` DQL function; `$modifyQueryUsing`
disappears with the batch, since that was its only caller.

### A to-many join on the summarized query is refused where Filament inflates in silence

A to-many join multiplies rows, and every aggregate over them reads the multiplied
set — `COUNT` answers 4 for three artists once `a.songs` is joined. The port's own
machinery never adds such a join (eager loading refuses to-many, search uses
`EXISTS`, the relation lane uses `IN`), but a user's `query()` closure or a joining
filter can, so `SummaryQuery` refuses it with a `LogicException` naming
`Summarizer::using()` as the way out. Filament produces the inflated number
instead. A wrong figure under a heading that says *All records* is worse than an
error that names its own escape hatch.

### Summarizing an aggregate column is refused where Filament produces a wrong number

A column carrying a relationship aggregate (`counts()`, `sum()`, …) reads its
value from a
[batched side store](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)
filled for the page's keys only, and DQL cannot aggregate a subselect
(`SUM((SELECT …))` is a parse error). So the alternative to refusing is a page
row that is right sitting beside an all-table row that is wrong.
`Summarizer::getState()` throws a `LogicException` naming the column and
`Summarizer::using()`, and the batch skips such a column entirely.

`using()` is the escape hatch in fact and not only in the message: the closure is
evaluated **before** both this refusal and the qualifier's to-many one, and it is
handed the column's own name beside the query, so a caller who knows what the
figure should be can compute it.

### The records lane is refused, loudly, where Filament's fatals deep in `summarize()`

A `records()` table's rows were never queried, so there is no dataset to
aggregate. Filament's summarizer reaches a `TypeError` several frames down;
`getState()` throws a `LogicException` here that names the lane, the column and
the two ways out (`query()` / `relationship()`, or dropping the summarizer).

A PHP fold over the loaded rows is the obvious follow-up and is not built: it
doubles the tier — every summarizer would need a second implementation — and it
cannot honour `query(Closure)` or `using()`, both of which are defined in terms
of a query builder.

### `Count` counts the column on both paths where vendor's single query counts rows

Vendor's `Count::summarize()` is `count(*)` while its batched statement is
`count(column)`, so on a nullable column its page row and its all-table row can
disagree about nulls depending on which path answered. Both paths here are
`COUNT({$attribute})`. Consistency between the rows of one band is the whole
point of running the two reads the way this tier does, and a user who wants row
counts has `Count::make()` on a non-nullable column or `using()`.

### The batch keeps a to-one relation column and refuses a to-many one

Filament skips a relationship column in its batch outright and lets each such
summarizer run its own query. `SummaryQuery::qualifyAttribute()` resolves the
column's name instead: a root field off the root alias, a to-one path off the
alias its hops are already joined under (so `MIN(sy_artist.name)` batches with
everything else), an embeddable field off the root. A to-many path throws, worded
on [the sort arm's model](table.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery)
and naming `Summarizer::using()`.

The resolution is deliberately **lazy** — it runs only once a summarizer on that
column has passed the batch's three gates — so a `Values` on `genres.name`, which
never batches anyway, does not blow up the statement every other column
contributes to.

Filament's third answer to the same question, `Column::inverseRelationship()`,
re-roots the query on the related record to weight to-many rows once. It is
[not ported](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member),
and refusing the to-many case is why: the summarizer is its only consumer in
either codebase.

### The alias trio is one trait behind a marker interface where Filament copies it three times

`selectAlias()` / `getSelectAlias()` / `getSelectedState()` are copied verbatim
into `Sum`, `Average` and `Count` upstream. Here they are
`Concerns\HasSelectAlias`, and a summarizer that takes an alias declares
`Contracts\HasSelectAlias` — the `Contracts\` beside `Concerns\` idiom
`Support\Contracts\HasLabel` already follows. The interface is what the batch
tests before pushing an alias: `selectAlias()` is a trait member on three of six
classes, which PHPStan cannot ask about off the base at level max. The bundle's
component glob leaves `Contracts/` unregistered.

### The formatting trait is a copy of the column's, and the hoist is owed

`Summarizers\Concerns\CanFormatState` ports vendor's whole member set —
`formatStateUsing`, `limit`, `money`, `numeric`, `placeholder`, `markdown`,
`prefix`, `suffix`, `html`, `formatState`, and the getters — by **copying** the
[column trait's](text-column.md) bodies wherever the two share a member, which is
Filament's own shape: it files four copies of this trait (tables, infolists,
summarizers, exports) and none of them in support. So `money()` and `numeric()`
format through `NumberFormatter` here exactly as a column's do, `html()` sanitizes
through the app's own `HtmlSanitizerInterface` arriving by `#[Required]` setter,
and `markdown()`
[throws for `league/commonmark` rather than falling through](text-column.md#markdown-throws-for-the-package-it-suggests-rather-than-falling-through).

**What the copy actually shares**, since the debt below is only worth stating
precisely: `money()`, `numeric()`, `html()`, `markdown()`, the CommonMark pair,
`prefix()`/`suffix()`, `limit()` and the private helpers `sanitizeState()`,
`affixState()`, `escape()`, `formatterResult()` and `resolveNumberLocale()` are
the column trait's bodies with two edits — the closure receiver retyped
(`TextColumn $column` → `Summarizer $summarizer`, every call site inside `money()`
and `numeric()` renamed with it), and four `LogicException` messages resubjected
from the column and `$this->getName()` to the summarizer and `static::class`
(`formatState()`'s stringable throw, `formatterResult()`, `sanitizeState()`'s
commonmark throw and `getHtmlSanitizer()`'s). The two files are 393 lines against
599, and the shared bodies differ in `!` spacing throughout, so a mechanical
`diff` of the pair is noisier than the substance is.

**Where the two deliberately part**, and where a copy-back in either direction
would be a defect:

- `formatState()` is structurally different. The column `json_encode`s an array
  state and returns `null` for a null one; the summarizer returns an array
  **whole** — vendor's affix and placeholder tail would fatal on a `Range`'s
  pair, and the pass-through is what makes
  [the per-entry / whole-pair split](#the-list-summary-formats-per-entry-where-the-range-formats-its-pair-whole)
  work — throws only for a non-null non-stringable, and ends on a placeholder
  tail (`mb_trim((string) $affixed) === '' ? placeholder : affixed`) the column
  trait has not got.
- `placeholder()` is declared inside this trait and nowhere on the column side.
  A column's is support's
  [`Components\Concerns\HasPlaceholder`](../support/README.md#features),
  composed on the `Column` base and read by the cell; a summarizer's is a
  `string|Closure|null` prop of the formatter itself, because `formatState()` is
  the only thing that reads it.
- The date family (`date()` / `dateTime()` / `time()` / `since()` and their four
  tooltip siblings, `timezone()`, `resolveTimeZone()`, `toDateTimeImmutable()`)
  and the word family (`words()`, `getWordLimit()` / `getWordLimitEnd()`) are
  absent, as they are on vendor's summarizer trait — and with no `words()` the
  word half of `truncateState()` is dropped from the copy rather than left
  unfed.

One thing differs from vendor inside the copy: `limit()` is the column's
**property pair** (`getCharacterLimit()` / `getCharacterLimitEnd()`) rather than
vendor's formatter install, which comes with the bodies.

The debt this creates is Sylament's own rather than Filament's: the toolkit now
ships **two** copies of these mechanics — the same bodies under two receivers —
so a fix to one of the shared members is owed to the other, while the three
partings above are meant to stay apart. It is recorded on the
[`CanFormatState` hoist row](../support/README.md#features) and stays **todo**
until a third reader is wanted.

### `hiddenLabel()` is honoured in all three markups where vendor honours it in one

Vendor's `hiddenLabel()` lives on the shared `HasLabel` trait and is read only by
the text summarizer's view — a range or a list renders its label whatever the flag
says. All three bodies here wrap the label in `sy-sr-only` when the flag is set,
which is what a shared API implies. The class is support's
[`.sy-sr-only`](../support/README.md#supporting-surface), not a tables-tier
spelling of the same idea.

### Every row of the band carries a host-scoped id

The band's three rows are `<hostId>.table.summary.{header,page,all}` and a group
boundary's is `<hostId>.table.group-summary.<key>`, the key percent-encoded the
way [the group header row's is](grouping.md#the-key-segment-of-every-id-this-row-mints-is-percent-encoded).
Vendor gives the band no ids at all. They exist because the rows are morph
identity: whether a page row renders changes with the paginator, so an
unidentified band pairs positionally and idiomorph can replace one row's whole
`innerHTML` with another's — the defect class
[the per-column search cells](table.md#the-search-cells-carry-morph-keys-because-they-are-the-one-per-column-element-that-moves)
guard against too.

### The band is handed the real all-table query even when the all-table row is off

Under `summaries(allTableCondition: false)` vendor passes `null` as the band's
all-table query, so `getSummarizers(null)` returns every summarizer unfiltered —
hidden ones included — and the header row labels a column whose cell will never
render. The port passes the real query either way and lets the visibility filter
run, so a `hidden()` summarizer stops labelling its column. It is the consistent
answer, and the only place the two behaviours are visible is that header row.

### Every summary cell carries its column's responsive pair, where vendor's carries none

`Column` emits `<bp>:sy-ta-hidden` / `<bp>:sy-ta-visible` on every
`<th>` and `<td>` it owns, and the per-column search cells replicate the
pair. Vendor's `summary/row.blade.php` emits neither, so at parity a
`hiddenFrom('md')` column's summary cell would render below `md` while its header
and its cells are hidden — one cell wider than the row it belongs to.
`SummaryRow` stamps the pair from `getHiddenFrom()` / `getVisibleFrom()` on every
per-column cell.

One edge is unfixable rather than unfixed, and vendor does not try either: the
heading cell spans several columns, and it inherits the **first** one's pair. Where
it spans a single column that is exactly right; where it spans a responsive column
and a static one, no colspan can be — the row is one cell off with the pair and
`span − 1` off without it. The band's own cells are right in every case that does
not span.

Two smaller shape notes ride along. A placeholder cell carries `sy-ta-cell` where
vendor emits a bare `<td>`, matching the per-column search row's placeholders and
buying the cell its own edge padding. And `Summary` / `SummaryRow` are **sibling**
components rather than `components/Summary/Row/`, because the architecture test
admits only `components/<Name>/index.html.twig` — and a group boundary renders
`SummaryRow` alone, so the pair is right either way. `getExtraHeaderAttributeBag()`
and `canHeaderWrap()` have no counterpart on this port's `Column` and are dropped
with vendor's `fi-wrapped` split, which is a no-op upstream.

Both components publish an `extraHeadingColumn` and a `placeholderColumns` prop
that no call site sets today: the table layout never needs either, and the
content layout that does waits on the layout columns.
