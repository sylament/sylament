# Tables — port progress

Ports [`filament/tables`](../../../vendor/filament/tables) into
[`packages/tables`](../../../packages/tables). The table builder: query, columns,
filters, actions, grouping, summaries.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `tables/src` surface end to end (plus the rendered
views, translations and assets), so no table carries an
[unaudited row](../README.md#an-unaudited-table-says-so). The minimal tables
vertical (M1 · 5), the record-action lane (M1 · 6) and five pulled-forward epics
— table upgrades ([· 11](../roadmap.md#pulled-forward)), the Doctrine `query()`
lane ([· 12](../roadmap.md#pulled-forward)), the relation arm
([· 20](../roadmap.md#pulled-forward)) and table filters
([· 21](../roadmap.md#pulled-forward)) and [`ImageColumn`](../roadmap.md#pulled-forward)
([· 25](../roadmap.md#pulled-forward)) — plus, from
[M4](../roadmap.md#m4--tables-widening), the header/toolbar/bulk-action epic
(· 1), modal-mode record actions (· 2), row behaviour and query-string state
(· 3), the column catalog (· 4) and relation-scoped tables (· 8), are what has
landed so far
— a linked first-column name is built and carries a real
[status](../README.md#statuses), with a detail file wherever a divergence, an
open gap or real sub-structure earns one; a plain-text name is not built yet and
is **todo**, **blocked** or **skipped**. Five of the host rows are **skipped**,
all for the same reason: each of those five Filament traits is deprecated whole.
`HasActions` and `HasRecordAction` are `*TableAction` delegates (both added by
M1 · 6), `CanPollRecords` is a single deprecated getter (added by the table
upgrades epic), and `CanBeStriped` and `HasEmptyState` are v3 host hooks the
table object superseded (both settled by M4 · 3, which shipped what they front) —
the project has never ported a deprecated Filament surface, and the machinery
each fronts is built or tracked elsewhere, it just does not answer to those names
here. A sixth **skipped** row, `Filters / MultiSelectFilter`, is the same rule one
tier down.

**todo** and **blocked** are distinguished here rather than collapsed, because
the [vocabulary](../README.md#statuses) defines **todo** as having nothing to
wait on — so a row whose detail file already names a prerequisite would be
false as **todo**. These tables carry the same four columns as every package
README, so a **blocked** row names its prerequisite at the head of _Detail_,
ahead of the detail-file link that carries the full write-up. Two rows are
**blocked** today, both on the same thing — `CanBeStackedOnMobile` and
`HasContent` configure the **card** rendering rather than the table one, so they
wait on the layout columns (M4 · 7) that give them something to switch on.
_Relation managers_ — what most rows here once waited on
— was the last prerequisite to go before them: the
[relation-scoped table](table.md#the-relationship-query-is-the-base-query-rooted-at-the-target)
turned out not to need it, because `Table::relationship()` is a **data lane**, a
third case beside `records()` and `query()`, and it shipped with M4 · 8 *ahead*
of relation managers rather than inside them — a relation manager is one
consumer of that lane and the actions that write through it are another.
Four more stood here until their epics landed. _Table upgrades
([pulled forward · 11](../roadmap.md#pulled-forward))_ went first: its rows are
now **done**, **in progress** or — where what was left is Eloquent query
assembly — moved onto the query lane, and the two that were waiting on nothing
at all (deferred loading, polling) stayed plain **todo** until M4 · 3 took them.
The _Doctrine `query()`
lane ([pulled forward · 12](../roadmap.md#pulled-forward))_ then took those and
flipped them in turn, leaving `HasQuery`, `InteractsWithTableQuery` and
`PaginationMode` **in progress** against relation-aware resolution and a keyset
carrier rather than against an epic. The _relation arm
([pulled forward · 20](../roadmap.md#pulled-forward))_ closed the first of those:
[relationship traversal](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member),
the aggregates and eager loading are in, so `InteractsWithTableQuery` folded into
[`column.md`](column.md) and lost its row here, and what it did **not** take on —
the relation-scoped table — was left with a destination rather than an open
question, which [M4 · 8](../roadmap.md#m4--tables-widening) then shipped,
folding `HasQuery` into [`table.md`](table.md) and taking its row with it.
_Table bulk actions_ was the fourth, closed by
[M4 · 1](../roadmap.md#m4--tables-widening): the `BulkAction` family landed in
[`actions`](../actions/README.md), and the selection machinery it needed — the
three writable props on the host, the checkbox settings on the table object, and
the toolbar and header action sets that decide whether the checkboxes render at
all — landed here with it, leaving the two selection rows **in progress**
against the deferred settings each names rather than **blocked**. (The
[`forms`](../forms/README.md) README records the opposite
precedence — **todo** upstairs, **blocked** in the component's file — so the two
packages disagree until that one is revisited.)

Fifty concerns the port has landed are **folded** into the detail file of
the class that composes them and carry no row here, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing) — twelve
`Table\Concerns\` traits (`BelongsToLivewire`, `CanPaginateRecords`,
`CanSearchRecords`, `CanSortRecords`, `HasActions`, `HasAggregateValues`,
`HasColumns`, `HasEmptyState`, `HasFilterIndicators`, `HasQuery`,
`HasRecordActions`, `HasRecords`) into
[`table.md`](table.md), twenty `Columns\Concerns\` traits
(`BelongsToTable`, `CanAggregateRelatedRecords`, `CanBeDisabled`, `CanBeHidden`,
`CanBeHiddenResponsively`, `CanBeInline`,
`CanBeSearchable`, `CanBeSortable`, `CanBeToggled`, `CanCallAction`,
`CanOpenUrl`,
`HasCellState`, `HasExtraCellAttributes`, `HasExtraHeaderAttributes`, `HasLabel`,
`HasName`, `HasRecord`, `HasRowLoopObject`, `HasTooltip`,
`InteractsWithTableQuery`) into
[`column.md`](column.md), two more
`Columns\Concerns\` traits (`CanBeValidated`, `CanUpdateState`) into
[`has-columns.md`](has-columns.md) — the write-back seam that calls both, which
all four editable columns link to rather than any one of them owning it — five
`Columns\Concerns\` traits
(`CanFormatState`, `HasColor`, `HasDescription`, `HasIcon`, `HasIconColor`)
into
[`text-column.md`](text-column.md), and eleven `Filters\Concerns\` traits
(`BelongsToTable`, `CanBeHidden`, `CanResetState`, `CanSpanColumns`,
`HasColumns`, `HasDefaultState`, `HasIndicators`, `HasLabel`, `HasName`,
`HasSchema`, `InteractsWithTableQuery`) into
[`base-filter.md`](base-filter.md). Filament's `resources/views/index.blade.php`
folds the same way, into `table.md`'s `$template` row.
`Table\Concerns\HasFilters` is a landed concern that keeps a row anyway, for a
reason of its own: it is built and folded into [`table.md`](table.md) while the
`filtersFormSchema()` override and the four before/after-content layouts are not
— so the row is where the deferred half stays findable.
`Filters\Concerns\HasOptions` and
`Filters\Concerns\HasPlaceholder` keep rows for a different reason: they ship for
`SelectFilter` alone, so they fold into
[`select-filter.md`](select-filter.md) rather than into `base-filter.md` with the
eleven above. `Columns\Concerns\InteractsWithTableQuery` kept a row on the same
grounds until [· 20](../roadmap.md#pulled-forward) landed its aggregate and
eager-loading half, and folded when it did; the eleven the column catalog landed
folded on arrival — nine into `column.md`, and `CanUpdateState` / `CanBeValidated`
into `has-columns.md` rather than into any one of the four editable columns that
compose them. `HasCellState` and `CanAggregateRelatedRecords` are counted here
too, and were not before: both have been folded into `column.md` since · 20, and
were missing from this list rather than from that file. Everything
Filament ships that the port has **not** reached keeps its
own per-file row below, the way
[`actions`](../actions/README.md#features) tracks the concerns `Action` has not
reached yet — so a deferred feature is findable here rather than only inside a
detail file. [`support/component.md`](../support/component.md) is the shape each
detail file follows.

Two consequences of that split are worth stating, because they are where a
reader would otherwise look and find nothing. The **record-label machinery**
(`recordLabel()`, `pluralRecordLabel()`, `recordTitle()`,
`recordTitleAttribute()`) and **record identity** (`getRecordKey()`) live in
`Table\Concerns\HasRecords`, which is landed and folded — their status is
[`table.md`](table.md)'s _Record labels and titles_ and _Record identity_ rows. The **`TextColumn` formatting catalog** (`limit()`, `words()`,
`prefix()`, `date()`, `money()`, `badge()`, …) lives in
`Columns\Concerns\CanFormatState`, likewise landed and folded — its status is
[`text-column.md`](text-column.md)'s formatting rows.

## Components

`Table` plus the column, filter and grouping catalogs —
[`src/Table.php`](../../../vendor/filament/tables/src/Table.php),
[`src/Columns`](../../../vendor/filament/tables/src/Columns),
[`src/Filters`](../../../vendor/filament/tables/src/Filters),
[`src/Grouping`](../../../vendor/filament/tables/src/Grouping). `Table` and the
`Column` base head the table; the rest follow alphabetically, with a
`Parent / Child` name for anything nested below a directory. Filament's action
classes are not a tables catalog — table actions are `Filament\Actions` classes
reached through `Table\Concerns\HasActions`, whose row is in
[Features](#features).

The `Filters / QueryBuilder / Constraints` row collapses that whole 30-file
subtree into one navigational entry, as the
[`forms`](../forms/README.md#supporting-surface) audit does for `RichEditor`:
every file under it is a one-line class or trait aliasing
`Filament\QueryBuilder\Constraints\*` from the separate `filament/query-builder`
package, so there is nothing to diff per file until that package is on a
milestone.

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Table`](../../../packages/tables/src/Table.php) | [`src/Table.php`](../../../vendor/filament/tables/src/Table.php) | in progress | [table.md](table.md) |
| [`Column`](../../../packages/tables/src/Columns/Column.php) | [`src/Columns/Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | [column.md](column.md) |
| BadgeColumn | [`Columns/BadgeColumn.php`](../../../vendor/filament/tables/src/Columns/BadgeColumn.php) | skipped | The whole class is a deprecated one-line override of [`TextColumn`](text-column.md) — its own deprecation notice names the spelling that ships, `TextColumn` plus `badge()`, and the project has never ported a deprecated Filament surface ([renames.md](../renames.md)) |
| BooleanColumn | [`Columns/BooleanColumn.php`](../../../vendor/filament/tables/src/Columns/BooleanColumn.php) | skipped | The whole class is a deprecated one-line override of [`IconColumn`](icon-column.md) — its own deprecation notice names the spelling that ships, `IconColumn` plus `boolean()`, and the project has never ported a deprecated Filament surface ([renames.md](../renames.md)) |
| [`CheckboxColumn`](../../../packages/tables/src/Columns/CheckboxColumn.php) | [`Columns/CheckboxColumn.php`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | [checkbox-column.md](checkbox-column.md) |
| [`ColorColumn`](../../../packages/tables/src/Columns/ColorColumn.php) | [`Columns/ColorColumn.php`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | [color-column.md](color-column.md) |
| ColumnGroup | [`Columns/ColumnGroup.php`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php) | todo | — |
| Columns / Layout / Component | [`Columns/Layout/Component.php`](../../../vendor/filament/tables/src/Columns/Layout/Component.php) | todo | — |
| Columns / Layout / Grid | [`Columns/Layout/Grid.php`](../../../vendor/filament/tables/src/Columns/Layout/Grid.php) | todo | — |
| Columns / Layout / Panel | [`Columns/Layout/Panel.php`](../../../vendor/filament/tables/src/Columns/Layout/Panel.php) | todo | — |
| Columns / Layout / Split | [`Columns/Layout/Split.php`](../../../vendor/filament/tables/src/Columns/Layout/Split.php) | todo | — |
| Columns / Layout / Stack | [`Columns/Layout/Stack.php`](../../../vendor/filament/tables/src/Columns/Layout/Stack.php) | todo | — |
| Columns / Layout / View | [`Columns/Layout/View.php`](../../../vendor/filament/tables/src/Columns/Layout/View.php) | todo | — |
| Columns / Summarizers / Average | [`Columns/Summarizers/Average.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Average.php) | todo | — |
| Columns / Summarizers / Count | [`Columns/Summarizers/Count.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Count.php) | todo | — |
| Columns / Summarizers / Range | [`Columns/Summarizers/Range.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Range.php) | todo | — |
| Columns / Summarizers / Sum | [`Columns/Summarizers/Sum.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Sum.php) | todo | — |
| Columns / Summarizers / Summarizer | [`Columns/Summarizers/Summarizer.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Summarizer.php) | todo | — |
| Columns / Summarizers / Values | [`Columns/Summarizers/Values.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Values.php) | todo | — |
| [`Filters / BaseFilter`](../../../packages/tables/src/Filters/BaseFilter.php) | [`Filters/BaseFilter.php`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | in progress | [base-filter.md](base-filter.md) |
| [`Filters / Filter`](../../../packages/tables/src/Filters/Filter.php) | [`Filters/Filter.php`](../../../vendor/filament/tables/src/Filters/Filter.php) | done | [filter.md](filter.md) |
| [`Filters / Indicator`](../../../packages/tables/src/Filters/Indicator.php) | [`Filters/Indicator.php`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | [indicator.md](indicator.md) |
| Filters / MultiSelectFilter | [`Filters/MultiSelectFilter.php`](../../../vendor/filament/tables/src/Filters/MultiSelectFilter.php) | skipped | The whole class is a deprecated `isMultiple(): true` override of `SelectFilter`, and the spelling its own deprecation notice names — `SelectFilter` plus `multiple()` — ships; the project has never ported a deprecated Filament surface ([renames.md](../renames.md)) |
| Filters / QueryBuilder | [`Filters/QueryBuilder.php`](../../../vendor/filament/tables/src/Filters/QueryBuilder.php) | todo | — |
| Filters / QueryBuilder / Constraints | [`Filters/QueryBuilder/Constraints/`](../../../vendor/filament/tables/src/Filters/QueryBuilder/Constraints) | todo | — |
| [Filters / SelectFilter](../../../packages/tables/src/Filters/SelectFilter.php) | [`Filters/SelectFilter.php`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | in progress | [select-filter.md](select-filter.md) |
| [Filters / TernaryFilter](../../../packages/tables/src/Filters/TernaryFilter.php) | [`Filters/TernaryFilter.php`](../../../vendor/filament/tables/src/Filters/TernaryFilter.php) | in progress | [ternary-filter.md](ternary-filter.md) |
| Filters / TrashedFilter | [`Filters/TrashedFilter.php`](../../../vendor/filament/tables/src/Filters/TrashedFilter.php) | todo | — |
| Grouping / Group | [`Grouping/Group.php`](../../../vendor/filament/tables/src/Grouping/Group.php) | todo | — |
| [`IconColumn`](../../../packages/tables/src/Columns/IconColumn.php) | [`Columns/IconColumn.php`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | [icon-column.md](icon-column.md) |
| [`ImageColumn`](../../../packages/tables/src/Columns/ImageColumn.php) | [`Columns/ImageColumn.php`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | [image-column.md](image-column.md) |
| [`SelectColumn`](../../../packages/tables/src/Columns/SelectColumn.php) | [`Columns/SelectColumn.php`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | in progress | [select-column.md](select-column.md) |
| TagsColumn | [`Columns/TagsColumn.php`](../../../vendor/filament/tables/src/Columns/TagsColumn.php) | skipped | The whole class is a deprecated `badge()` subclass of [`TextColumn`](text-column.md) whose own nested `limit()` is deprecated in favour of `limitList()`, and both spellings ship; the project has never ported a deprecated Filament surface ([renames.md](../renames.md)) |
| [`TextColumn`](../../../packages/tables/src/Columns/TextColumn.php) | [`src/Columns/TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | in progress | [text-column.md](text-column.md) |
| [`TextInputColumn`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`Columns/TextInputColumn.php`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | [text-input-column.md](text-input-column.md) |
| [`ToggleColumn`](../../../packages/tables/src/Columns/ToggleColumn.php) | [`Columns/ToggleColumn.php`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | [toggle-column.md](toggle-column.md) |
| [`ViewColumn`](../../../packages/tables/src/Columns/ViewColumn.php) | [`Columns/ViewColumn.php`](../../../vendor/filament/tables/src/Columns/ViewColumn.php) | done | [view-column.md](view-column.md) |

## Features

Cross-cutting concerns and traits, one row per file, grouped by the tier that
composes them and named for it:

- **`Host / …`** — [`src/Concerns`](../../../vendor/filament/tables/src/Concerns),
  the traits a Livewire / UX Live Component host composes through
  `InteractsWithTable`. The port's counterpart is
  [`HasTable`](../../../packages/tables/src/Concerns/HasTable.php), whose row
  heads the table.
- **`Table / …`** — [`src/Table/Concerns`](../../../vendor/filament/tables/src/Table/Concerns),
  the traits [`Table`](table.md) composes. Every host feature has a table-level
  twin here — `Host / CanPaginateRecords` reads the host's page state,
  `Table / CanPaginateRecords` declares the table's pagination options — so the
  two lists overlap by name on purpose.
- **`Column / …`** — [`src/Columns/Concerns`](../../../vendor/filament/tables/src/Columns/Concerns),
  shared by [`Column`](column.md), `ColumnGroup`, the `Columns\Layout\`
  components and the concrete column types.
- **`Summarizer / …`**, **`Filter / …`**, **`Group / …`** —
  [`Columns/Summarizers/Concerns`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns),
  [`Filters/Concerns`](../../../vendor/filament/tables/src/Filters/Concerns) and
  [`Grouping/Concerns`](../../../vendor/filament/tables/src/Grouping/Concerns).

The prefix is load-bearing rather than decorative: twenty trait names occur in
two of these tiers and five (`BelongsToTable`, `CanBeHidden`, `HasColumns`,
`HasLabel`, `InteractsWithTableQuery`) in three, which is the same
[read-by-import, never by the bare `use`](../../../CLAUDE.md) trap the schemas
tiers carry. The fifty landed concerns folded into their component's detail
file are named in the [preamble](#tables--port-progress) and carry no row here.

`Contracts/HasTable.php` is not listed in
[Supporting surface](#supporting-surface): it is inseparable from the trait it
pairs with, so it shares the first row below.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`HasTable`](../../../packages/tables/src/Concerns/HasTable.php) / [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`src/Concerns/InteractsWithTable.php`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php), [`src/Contracts/HasTable.php`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | [has-table.md](has-table.md) |
| Host / CanBeStriped | [`Concerns/CanBeStriped.php`](../../../vendor/filament/tables/src/Concerns/CanBeStriped.php) | skipped | The trait and its single member `isTableStriped()` both carry `@deprecated Override the \`table()\` method`, and the project has never ported a deprecated Filament surface — what it fronts is `Table::striped()`, the **done** `Table / CanBeStriped` row below ([has-table.md](has-table.md#surface)) |
| [`Host / CanDeferLoading`](../../../packages/tables/src/Concerns/CanDeferLoading.php) | [`Concerns/CanDeferLoading.php`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | done | [table.md](table.md#the-deferred-window-is-server-gated-and-client-closed) |
| Host / CanGroupRecords | [`Concerns/CanGroupRecords.php`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | todo | — |
| Host / CanPaginateRecords | [`Concerns/CanPaginateRecords.php`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php) | in progress | [has-table.md](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) |
| Host / CanPollRecords | [`Concerns/CanPollRecords.php`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | The whole trait is a deprecated host getter (`getTablePollingInterval()`), and the project has never ported a deprecated Filament surface — the interval it fronts is `Table::poll()`, the shipped `Table / CanPollRecords` row below ([has-table.md](has-table.md#surface), [table.md](table.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers)) |
| [`Host / CanReorderRecords`](../../../packages/tables/src/Concerns/CanReorderRecords.php) | [`Concerns/CanReorderRecords.php`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | done | [has-table.md](has-table.md#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) |
| Host / CanSearchRecords | [`Concerns/CanSearchRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | in progress | [has-table.md](has-table.md#search-is-the-global-term-and-one-term-per-column) |
| Host / CanSortRecords | [`Concerns/CanSortRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php) | in progress | [has-table.md](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) |
| Host / CanSummarizeRecords | [`Concerns/CanSummarizeRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | todo | — |
| Host / HasActions | [`Concerns/HasActions.php`](../../../vendor/filament/tables/src/Concerns/HasActions.php) | skipped | Every method in the file is a deprecated `*TableAction` delegate to the actions-package equivalent, and the project has never ported a deprecated Filament surface. What it fronts is built: mounting, resolution — the bulk map included — and record binding go through actions' [`HasActions`](../actions/has-actions.md#a-client-supplied-record-key-is-type-checked-before-the-lookup) and the [`InteractsWithTable`](has-table.md#the-contract-is-twenty-six-methods-of-filaments-forty-six) contract |
| [`Host / HasBulkActions`](../../../packages/tables/src/Concerns/HasBulkActions.php) | [`Concerns/HasBulkActions.php`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | in progress | [has-table.md](has-table.md#the-selection-is-server-canonical-and-re-derived-on-every-read) |
| [`Host / HasColumnManager`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns/HasColumnManager.php`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | in progress | [column-manager.md](column-manager.md) |
| [`Host / HasColumns`](../../../packages/tables/src/Concerns/HasColumns.php) | [`Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | in progress | [has-columns.md](has-columns.md) |
| Host / HasContent | [`Concerns/HasContent.php`](../../../vendor/filament/tables/src/Concerns/HasContent.php) | todo | — |
| Host / HasEmptyState | [`Concerns/HasEmptyState.php`](../../../vendor/filament/tables/src/Concerns/HasEmptyState.php) | skipped | The trait and all five members (`getTableEmptyState()`, `getTableEmptyStateActions()`, `getTableEmptyStateDescription()`, `getTableEmptyStateHeading()`, `getTableEmptyStateIcon()`) carry `@deprecated Override the \`table()\` method`, and the project has never ported a deprecated Filament surface — what they front is the table-side empty-state family, [**done** on `table.md`](table.md#surface) |
| Host / HasFilters | [`Concerns/HasFilters.php`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | in progress | [has-table.md](has-table.md) |
| Host / HasHeader | [`Concerns/HasHeader.php`](../../../vendor/filament/tables/src/Concerns/HasHeader.php) | todo | — |
| Host / HasRecordAction | [`Concerns/HasRecordAction.php`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | The file is one deprecated `getTableRecordAction()` host hook, superseded upstream by `Table::recordAction()` — the `Table / HasRecordAction` row below tracks that one |
| Host / HasRecords | [`Concerns/HasRecords.php`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | [has-table.md](has-table.md) |
| Table / CanBeStackedOnMobile | [`Table/Concerns/CanBeStackedOnMobile.php`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStackedOnMobile.php) | blocked | the layout columns (M4 · 7) — it is the responsive half of the card rendering, with nothing to stack until `content()` has a list ([table.md](table.md#surface)) |
| [`Table / CanBeStriped`](../../../packages/tables/src/Table/Concerns/CanBeStriped.php) | [`Table/Concerns/CanBeStriped.php`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) | done | [table.md](table.md#surface) |
| [`Table / CanDeferLoading`](../../../packages/tables/src/Table/Concerns/CanDeferLoading.php) | [`Table/Concerns/CanDeferLoading.php`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php) | done | [table.md](table.md#the-deferred-window-is-server-gated-and-client-closed) |
| Table / CanGroupRecords | [`Table/Concerns/CanGroupRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | todo | — |
| [`Table / CanPollRecords`](../../../packages/tables/src/Table/Concerns/CanPollRecords.php) | [`Table/Concerns/CanPollRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | done | [table.md](table.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers) |
| [`Table / CanReorderRecords`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) | [`Table/Concerns/CanReorderRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php) | done | [table.md](table.md#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) |
| Table / CanSummarizeRecords | [`Table/Concerns/CanSummarizeRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | todo | — |
| Table / HasArguments | [`Table/Concerns/HasArguments.php`](../../../vendor/filament/tables/src/Table/Concerns/HasArguments.php) | todo | — |
| [`Table / HasBulkActions`](../../../packages/tables/src/Table/Concerns/HasBulkActions.php) | [`Table/Concerns/HasBulkActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php) | in progress | [table.md](table.md#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) |
| [`Table / HasColumnManager`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table/Concerns/HasColumnManager.php`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | in progress | [column-manager.md](column-manager.md) |
| Table / HasContent | [`Table/Concerns/HasContent.php`](../../../vendor/filament/tables/src/Table/Concerns/HasContent.php) | blocked | the layout columns (M4 · 7) — `content()` replaces the `<table>` with a caller-supplied list, and there is no list until `Columns\Layout` ships ([table.md](table.md#surface)) |
| [`Table / HasFilters`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table/Concerns/HasFilters.php`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | in progress | [table.md](table.md) |
| Table / HasHeader | [`Table/Concerns/HasHeader.php`](../../../vendor/filament/tables/src/Table/Concerns/HasHeader.php) | todo | — |
| [`Table / HasHeaderActions`](../../../packages/tables/src/Table/Concerns/HasHeaderActions.php) | [`Table/Concerns/HasHeaderActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php) | in progress | [table.md](table.md#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) |
| Table / HasHeadings | [`Table/Concerns/HasHeadings.php`](../../../vendor/filament/tables/src/Table/Concerns/HasHeadings.php) | todo | — |
| [`Table / HasQueryStringIdentifier`](../../../packages/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | [`Table/Concerns/HasQueryStringIdentifier.php`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | done | [has-table.md](has-table.md#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers) |
| [`Table / HasRecordAction`](../../../packages/tables/src/Table/Concerns/HasRecordAction.php) | [`Table/Concerns/HasRecordAction.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php) | done | [table.md](table.md#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) |
| [`Table / HasRecordClasses`](../../../packages/tables/src/Table/Concerns/HasRecordClasses.php) | [`Table/Concerns/HasRecordClasses.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordClasses.php) | done | [table.md](table.md#surface) |
| [`Table / HasRecordUrl`](../../../packages/tables/src/Table/Concerns/HasRecordUrl.php) | [`Table/Concerns/HasRecordUrl.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | in progress | `getExtraRecordLinkAttributeBag()` alone is skipped — [table.md](table.md#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) |
| [`Table / HasToolbarActions`](../../../packages/tables/src/Table/Concerns/HasToolbarActions.php) | [`Table/Concerns/HasToolbarActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php) | done | [table.md](table.md#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) |
| Column / BelongsToGroup | [`Columns/Concerns/BelongsToGroup.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToGroup.php) | todo | — |
| Column / BelongsToLayout | [`Columns/Concerns/BelongsToLayout.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToLayout.php) | todo | — |
| Column / CanBeCopied | [`Columns/Concerns/CanBeCopied.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeCopied.php) | skipped | The whole trait is a `@deprecated` shim over `Support\Concerns\CanBeCopied`, and the port has never landed a deprecated Filament surface — the support trait it fronts ships as [`can-be-copied.md`](../support/can-be-copied.md) ([renames.md](../renames.md)) |
| Column / CanBeSummarized | [`Columns/Concerns/CanBeSummarized.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | todo | — |
| Column / HasSpace | [`Columns/Concerns/HasSpace.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasSpace.php) | todo | — |
| Summarizer / BelongsToColumn | [`Columns/Summarizers/Concerns/BelongsToColumn.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/BelongsToColumn.php) | todo | — |
| Summarizer / CanBeHidden | [`Columns/Summarizers/Concerns/CanBeHidden.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/CanBeHidden.php) | todo | — |
| Summarizer / CanFormatState | [`Columns/Summarizers/Concerns/CanFormatState.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/CanFormatState.php) | todo | — |
| Summarizer / HasLabel | [`Columns/Summarizers/Concerns/HasLabel.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/HasLabel.php) | todo | — |
| Summarizer / InteractsWithTableQuery | [`Columns/Summarizers/Concerns/InteractsWithTableQuery.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/InteractsWithTableQuery.php) | todo | — |
| [`Filter / HasOptions`](../../../packages/tables/src/Filters/Concerns/HasOptions.php) | [`Filters/Concerns/HasOptions.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasOptions.php) | in progress | the `options()` / `getOptions()` pair ships for `SelectFilter`; the enum and `Arrayable` arms and the three `getOption*Using()` seams wait on a tables filters epic — [select-filter.md](select-filter.md) |
| [`Filter / HasPlaceholder`](../../../packages/tables/src/Filters/Concerns/HasPlaceholder.php) | [`Filters/Concerns/HasPlaceholder.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasPlaceholder.php) | done | ships for `SelectFilter`, folded into [select-filter.md](select-filter.md). The column catalog (M4 · 4) landed a third copy — [`Support\Components\Concerns\HasPlaceholder`](../support/has-placeholder.md), which `Column` composes — and did **not** repoint this one: three copies under Filament's own three namespaces, the two lower ones narrower (`string\|Closure\|null` against the support trait's `Markup`-carrying union), so a repoint would widen a published signature. The dedupe is a follow-up to revisit only if a fourth copy is wanted, and it waits on M3 · 9, whose lanes own forms' copy ([has-placeholder.md](../support/has-placeholder.md#three-copies-of-this-trait-ship-and-that-is-filaments-shape-too)) |
| Filter / HasRelationship | [`Filters/Concerns/HasRelationship.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasRelationship.php) | todo | — |
| Group / BelongsToTable | [`Grouping/Concerns/BelongsToTable.php`](../../../vendor/filament/tables/src/Grouping/Concerns/BelongsToTable.php) | todo | — |

## Supporting surface

Everything with no table, column or filter class of its own: the enums, the
editable-column contract, the Livewire table component, the test helpers, the
`make:` commands and their file generators, the icon-alias and render-hook
registries, the view components, the `renderTable()` Twig helper, the rendered
views, the translations and assets, and the bundle wiring —
[`src/Enums`](../../../vendor/filament/tables/src/Enums),
[`src/Columns/Contracts`](../../../vendor/filament/tables/src/Columns/Contracts),
[`src/Commands`](../../../vendor/filament/tables/src/Commands),
[`src/Testing`](../../../vendor/filament/tables/src/Testing),
[`src/View`](../../../vendor/filament/tables/src/View),
[`resources`](../../../vendor/filament/tables/resources). A row named for a
directory collapses that whole subtree into one navigational entry. A row whose
Ports cell names no Filament source is Sylament-only — nothing to diff against.

`Actions/HeaderActionsPosition.php` sits here rather than in
[Components](#components) despite its directory: it is an enum, not an action
class, and it is the only file Filament's tables package keeps under `Actions/`.
The port files it under `Enums/` beside its five siblings, which is where its
_Feature_ cell links — [table.md](table.md#headeractionsposition-files-under-enums-where-filament-keeps-it-under-actions)
carries that one-line divergence.

The bundle owns the two Twig-side rows as well as its own, so all three share
[tables-bundle.md](tables-bundle.md): what `loadExtension()` wires and why
Filament's provider has no counterpart for it, the `renderTable()` helper, and
the alias set's growth order. Filament's
[`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php)
carries no row — it is ported by
[`Table/index.html.twig`](../../../packages/tables/templates/partials/Table/index.html.twig)
and folded into [`table.md`](table.md)'s `$template` row; `TextColumn`'s partial
folds into [`text-column.md`](text-column.md) the same way, against
`TextColumn::toEmbeddedHtml()` rather than a Blade file of its own.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`TableExtension`](../../../packages/tables/src/Twig/Extension/TableExtension.php) (`renderTable()`) | — (Sylament-only; mirrors schemas' [`SchemaExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | [tables-bundle.md](tables-bundle.md#the-bundle-registers-a-rendertable-twig-extension-which-filament-has-no-counterpart-for) |
| [`TablesIconAlias`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`View/TablesIconAlias.php`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | in progress | [tables-bundle.md](tables-bundle.md#the-alias-set-is-thirteen-constants-of-filaments-23) |
| [`SylamentTablesBundle`](../../../packages/tables/src/SylamentTablesBundle.php) | [`TablesServiceProvider.php`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | in progress | [tables-bundle.md](tables-bundle.md) |
| [Styles](../../../packages/tables/assets/src/styles) | [`resources/css/`](../../../vendor/filament/tables/resources/css) | in progress | Sixteen sheets ship, each imported into [`index.css`](../../../packages/tables/assets/src/styles/index.css) alphabetically under `layer(components)`. The chrome: `table.css` (the container, rows, `<th>`/`<td>`, the cell wrapper, the empty state and the reorder handle), `header.css`, `header-toolbar.css`, `filters.css` (the panel, its trigger and the indicator row), `column-manager.css` (the panel and its item list, [whose block sits at top level rather than nested in the panel's](column-manager.md#the-item-list-block-sits-at-top-level-where-vendors-nests-inside-the-panel) because the modal layout renders the items without that wrapper), `search-field.css` (the field the toolbar and every per-column search cell mount, extracted from `header-toolbar.css` when the second call site arrived) and `selection.css`. The columns, one sheet each: `text-column.css` — split along Filament's own line into the cell's layout, the item's type and the description ([text-column.md](text-column.md#the-item-element-is-never-merged-into-the-outer-div)), and carrying vendor's `<ul>` rules since the list arms ([text-column.md](text-column.md#the-list-wrapper-appeared-with-the-list-arms-and-closed-a-css-divergence-with-it)) — `image-column.css`, and the column catalog's `icon-column.css`, `color-column.css`, `toggle-column.css`, `checkbox-column.css`, `select-column.css` and `text-input-column.css`, plus `editable-cell.css`, which is Sylament-only: the two rules every editable cell shares (`.sy-ta-cell-error` and the in-flight `.sy-loading`) belong to none of the four columns that render them ([has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client)). The four editable sheets are thin because the controls reuse forms' skins rather than restating them ([tables-bundle.md](tables-bundle.md#the-manifest-declares-sylamentforms)). Vendor's `content.css`, `columns/layout/` and `columns/summaries/` follow their features |
| [Scripts](../../../packages/tables/assets/src) | [`resources/js/`](../../../vendor/filament/tables/resources/js) | in progress | the `@sylament/tables` workspace — the seventh, JS-only, added with record selection — ships four Stimulus controllers, all `fetch: eager`. [`table`](../../../packages/tables/assets/src/controllers/table_controller.ts) ports the **selection half** of vendor's [`components/table.js`](../../../vendor/filament/tables/resources/js/components/table.js) — the filters-dropdown positioning (support's `Dropdown` owns that), the group-collapse arm (M4 · 6) and five smaller arms are dropped, and four behaviours diverge, all listed in [tables-bundle.md](tables-bundle.md#asset-registration). [`limited-list`](../../../packages/tables/assets/src/controllers/limited_list_controller.ts) replaces the inline `x-data="{ isLimited: true }"` a `TextColumn` with `expandableLimitedList()` carries — the state moves into a Stimulus value a live morph replays, which Alpine's per-render scope loses ([text-column.md](text-column.md#the-expand-toggles-render-only-where-there-is-something-to-expand)). [`editable-cell`](../../../packages/tables/assets/src/controllers/editable_cell_controller.ts) is one controller where Filament ships four Alpine components (`columns/checkbox`, `columns/select`, `columns/text-input`, `columns/toggle`): it commits the control's value through the host's write action and repaints the control from the cell root's authoritative `state` value, which is what makes the seam server-canonical ([has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph)). [`column-manager`](../../../packages/tables/assets/src/controllers/column_manager_controller.ts) ports vendor's [`components/column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js): every tick and drag writes the whole draft into the host's `tableDeferredColumns` through the ValueStore, so a deferred manager costs zero requests until Apply and a live one costs exactly one per change ([tables-bundle.md](tables-bundle.md#asset-registration)). The workspace's one import outside Stimulus, [`utils/live_host.ts`](../../../packages/tables/assets/src/utils/live_host.ts), which `table`, `editable-cell` and `column-manager` share, is the sanctioned copy of schemas' — [tables-bundle.md](tables-bundle.md#asset-registration) |
| [`Colors\Components\TextColumnComponent\ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) | [`View/Components/Columns/TextColumnComponent/ItemComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent.php) | done | [text-column.md](text-column.md#the-item-policy-measures-the-dark-surface-against-gray-900) |
| [`Colors\Components\TextColumnComponent\ItemComponent\IconComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent/IconComponent.php) | [`View/Components/Columns/TextColumnComponent/ItemComponent/IconComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent/IconComponent.php) | done | [text-column.md](text-column.md#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) |
| [`Colors\Components\IconColumnComponent\IconComponent`](../../../packages/tables/src/Colors/Components/IconColumnComponent/IconComponent.php) | [`View/Components/Columns/IconColumnComponent/IconComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/IconColumnComponent/IconComponent.php) | done | [icon-column.md](icon-column.md#the-policy-carries-a-map-and-the-default-gray-contract-at-once) |
| [`HeaderActionsPosition`](../../../packages/tables/src/Enums/HeaderActionsPosition.php) | [`Actions/HeaderActionsPosition.php`](../../../vendor/filament/tables/src/Actions/HeaderActionsPosition.php) | done | The enum ships whole; it files under `Enums/` rather than Filament's `Actions/` — [table.md](table.md#headeractionsposition-files-under-enums-where-filament-keeps-it-under-actions) |
| [`Editable` (contract)](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Columns/Contracts/Editable.php`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | folded into [has-columns.md](has-columns.md#surface), the seam its two members are called through |
| [`ColumnManagerLayout`](../../../packages/tables/src/Enums/ColumnManagerLayout.php) | [`Enums/ColumnManagerLayout.php`](../../../vendor/filament/tables/src/Enums/ColumnManagerLayout.php) | done | The enum ships whole; both cases — `Dropdown` and `Modal` — are read by [`hasColumnManagerDropdown()`/`hasColumnManagerModal()`](column-manager.md#surface), and only the dropdown honours [the reset position](column-manager.md#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`ColumnManagerResetActionPosition`](../../../packages/tables/src/Enums/ColumnManagerResetActionPosition.php) | [`Enums/ColumnManagerResetActionPosition.php`](../../../vendor/filament/tables/src/Enums/ColumnManagerResetActionPosition.php) | done | The enum ships whole; both cases — `Header` and `Footer` — are answered by [`getColumnManagerResetActionPosition()`](column-manager.md#surface), and are [inert under the modal layout](column-manager.md#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`FiltersLayout`](../../../packages/tables/src/Enums/FiltersLayout.php) | [`Enums/FiltersLayout.php`](../../../vendor/filament/tables/src/Enums/FiltersLayout.php) | in progress | The enum ships whole; six cases are honored — `Dropdown`, `Modal`, `AboveContent`, `AboveContentCollapsible`, `BelowContent` and `Hidden` — the four before/after-content ones refused where [the chrome reads them](table.md#an-unshipped-filters-layout-is-refused-where-the-chrome-reads-it) |
| [`FiltersResetActionPosition`](../../../packages/tables/src/Enums/FiltersResetActionPosition.php) | [`Enums/FiltersResetActionPosition.php`](../../../vendor/filament/tables/src/Enums/FiltersResetActionPosition.php) | done | [table.md](table.md) |
| [`PaginationMode`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Enums/PaginationMode.php`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | in progress | The enum ships whole; `Default` and `Simple` are read by [`paginateTableQuery()`](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half), `Cursor` is refused until a keyset carrier exists — [table.md](table.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) |
| [`RecordActionsPosition`](../../../packages/tables/src/Enums/RecordActionsPosition.php) | [`Enums/RecordActionsPosition.php`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php) | done | [table.md](table.md#the-position-default-has-no-content-arm-to-choose-between-yet) |
| [`RecordCheckboxPosition`](../../../packages/tables/src/Enums/RecordCheckboxPosition.php) | [`Enums/RecordCheckboxPosition.php`](../../../vendor/filament/tables/src/Enums/RecordCheckboxPosition.php) | done | [table.md](table.md#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) |
| TableComponent | [`TableComponent.php`](../../../vendor/filament/tables/src/TableComponent.php) | todo | — |
| Commands | [`Commands/`](../../../vendor/filament/tables/src/Commands) | todo | — |
| Commands / FileGenerators | [`Commands/FileGenerators/`](../../../vendor/filament/tables/src/Commands/FileGenerators) | todo | — |
| TestsActions | [`Testing/TestsActions.php`](../../../vendor/filament/tables/src/Testing/TestsActions.php) | todo | — |
| TestsBulkActions | [`Testing/TestsBulkActions.php`](../../../vendor/filament/tables/src/Testing/TestsBulkActions.php) | todo | — |
| TestsColumns | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — |
| TestsFilters | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | todo | — |
| TestsRecords | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | todo | — |
| TestsSummaries | [`Testing/TestsSummaries.php`](../../../vendor/filament/tables/src/Testing/TestsSummaries.php) | todo | — |
| TablesRenderHook | [`View/TablesRenderHook.php`](../../../vendor/filament/tables/src/View/TablesRenderHook.php) | todo | — |
| View / Components | [`Columns/Summarizers/CountComponent/`](../../../vendor/filament/tables/src/View/Components/Columns/Summarizers/CountComponent) | todo | the two `TextColumnComponent` policies and the [icon column's](icon-column.md) have rows of their own above; what is left of the directory is the count summarizer's, waiting on its component |
| [views/components/column-manager](../../../packages/tables/templates/components/ColumnManager/index.html.twig) | [`views/components/column-manager/index.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/index.blade.php) | in progress | the panel's chrome — the heading, the reset action in either position and the apply action's footer — around the items component; the `ColumnGroup` half is unported ([column-manager.md](column-manager.md#the-columngroup-half-is-the-whole-of-what-is-left)) |
| [views/components/column-manager/content](../../../packages/tables/templates/components/ColumnManagerContent/index.html.twig) | [`views/components/column-manager/content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php) | in progress | the items alone, server-rendered from the host's draft rather than Alpine's `x-for`, and the element the manager's controller mounts on in both layouts; the nested group list is unported with `ColumnGroup` ([column-manager.md](column-manager.md#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for)) |
| views/components/columns/layout | [`views/components/columns/layout.blade.php`](../../../vendor/filament/tables/resources/views/components/columns/layout.blade.php) | todo | — |
| views/components/filters | [`views/components/filters.blade.php`](../../../vendor/filament/tables/resources/views/components/filters.blade.php) | todo | — |
| [views/components/search-field](../../../packages/tables/templates/components/SearchField/index.html.twig) | [`views/components/search-field.blade.php`](../../../vendor/filament/tables/resources/views/components/search-field.blade.php) | in progress | the component both call sites mount — the [table partial](../../../packages/tables/templates/partials/Table/index.html.twig)'s toolbar and each searchable cell of its [per-column search row](has-table.md#search-is-the-global-term-and-one-term-per-column) — with `wireModel` translated to `model` and the debounce, on-blur and placeholder props beside it; two shapes are ours rather than vendor's, and both are cheaper: the root `<label>` wraps the input instead of vendor's `x-id`-bound visually-hidden sibling, and the `value` attribute is a prop the per-column call fills and the toolbar leaves off. Filament's Enter → `$wire.$refresh()` keyup is unported, as it was while the field was inline — [its own **todo** row](table.md#surface) now carries the deferral |
| views/components/summary | [`views/components/summary/index.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/index.blade.php) | todo | — |
| views/components/summary/row | [`views/components/summary/row.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/row.blade.php) | todo | — |
| Translations | [`resources/lang/`](../../../vendor/filament/tables/resources/lang) | todo | — |
