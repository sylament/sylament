# Tables — port progress

Ports [`filament/tables`](../../../vendor/filament/tables) into
[`packages/tables`](../../../packages/tables). The table builder: query, columns,
filters, actions, grouping, summaries.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `tables/src` surface end to end (plus the rendered
views, translations and assets), so no table carries an
[unaudited row](../README.md#an-unaudited-table-says-so). The minimal tables
vertical (M1 · 5), the record-action lane (M1 · 6) and the table upgrades epic
([pulled forward · 11](../roadmap.md#pulled-forward)) are what has landed so far
— a linked first-column name is built and carries a real
[status](../README.md#statuses), with a detail file wherever a divergence, an
open gap or real sub-structure earns one; a plain-text name is not built yet and
is **todo**, **blocked** or **skipped**. Three rows are **skipped**, all for the
same reason: Filament's host `HasActions` and `HasRecordAction` are wholly
deprecated `*TableAction` delegates (both added by M1 · 6), and its host
`CanPollRecords` is a single deprecated getter (added by the table upgrades
epic) — the project has never ported a deprecated Filament surface, and the
machinery each fronts is built or tracked elsewhere, it just does not answer to
those names here.

**todo** and **blocked** are distinguished here rather than collapsed, because
the [vocabulary](../README.md#statuses) defines **todo** as having nothing to
wait on — so a row whose detail file already names a prerequisite would be
false as **todo**. These tables carry the same four columns as every package
README, so a **blocked** row names its prerequisite at the head of _Detail_,
ahead of the detail-file link that carries the full write-up: _table bulk
actions_ is the one the rows here wait on today, with _a future
tables-relations epic_ a second that only the detail files name so far — the
[Doctrine relation layer](../roadmap.md#pulled-forward) shipped for schemas and
forms and deliberately left tables out, so what is waited on is that epic rather
than the association machinery. It is what the
[relation arm of the query lane](table.md#the-relation-arm-of-the-query-lane-waits-on-a-future-tables-relations-epic)
and
[relationship traversal](column.md#relationship-traversal-is-a-future-tables-relations-epics)
are held by.
Two more stood here until their epics landed. _Table upgrades
([pulled forward · 11](../roadmap.md#pulled-forward))_ went first: its rows are
now **done**, **in progress** or — where what was left is Eloquent query
assembly — moved onto the query lane, and the two that were waiting on nothing
at all (deferred loading, polling) are plain **todo**. The _Doctrine `query()`
lane ([pulled forward · 12](../roadmap.md#pulled-forward))_ then took those and
flipped them in turn, leaving `HasQuery`, `InteractsWithTableQuery` and
`PaginationMode` **in progress** against the relation layer and a keyset carrier
rather than against an epic. Neither _table bulk actions_ nor the relation layer
has an epic carrying it — bulk actions need a `BulkAction` class and the
row-selection machinery, the relation layer is unordered under
[Beyond](../roadmap.md#beyond), and the roadmap schedules neither. (The
[`forms`](../forms/README.md) README records the opposite
precedence — **todo** upstairs, **blocked** in the component's file — so the two
packages disagree until that one is revisited.)

Twenty concerns the port has landed are **folded** into the detail file of the
class that composes them and carry no row here, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing) — nine
`Table\Concerns\` traits (`BelongsToLivewire`, `CanPaginateRecords`,
`CanSearchRecords`, `CanSortRecords`, `HasActions`, `HasColumns`,
`HasEmptyState`, `HasRecordActions`, `HasRecords`) into
[`table.md`](table.md), six `Columns\Concerns\` traits
(`BelongsToTable`, `CanBeSearchable`, `CanBeSortable`, `HasLabel`, `HasName`,
`HasRecord`) into [`column.md`](column.md), and five `Columns\Concerns\` traits
(`CanFormatState`, `HasColor`, `HasDescription`, `HasIcon`, `HasIconColor`)
into
[`text-column.md`](text-column.md). Filament's `resources/views/index.blade.php`
folds the same way, into `table.md`'s `$template` row.
`Table\Concerns\HasQuery` and `Columns\Concerns\InteractsWithTableQuery` are the
two landed concerns that keep a row anyway, for the same reason: `HasQuery`'s
`query()` arm is built and folded into [`table.md`](table.md) while its relation
arm is deferred whole, and `InteractsWithTableQuery`'s `applySort()` /
`applySearchConstraint()` are built and folded into [`column.md`](column.md)
while its relationship aggregates and eager loading are deferred with the same
layer — so each row is where the deferred half stays findable. Everything
Filament ships that the port has **not** reached keeps its
own per-file row below, the way
[`actions`](../actions/README.md#features) tracks the concerns `Action` has not
reached yet — so a deferred feature is findable here rather than only inside a
detail file. [`support/component.md`](../support/component.md) is the shape each
detail file follows.

Two consequences of that split are worth stating, because they are where a
reader would otherwise look and find nothing. The **record-label machinery**
(`modelLabel()`, `pluralModelLabel()`, `recordTitle()`) and **record identity**
(`getRecordKey()`) live in `Table\Concerns\HasRecords`, which is landed and
folded — their status is [`table.md`](table.md)'s _Record labels_ and _Record
identity_ rows. The **`TextColumn` formatting catalog** (`limit()`, `words()`,
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
| BadgeColumn | [`Columns/BadgeColumn.php`](../../../vendor/filament/tables/src/Columns/BadgeColumn.php) | todo | — |
| BooleanColumn | [`Columns/BooleanColumn.php`](../../../vendor/filament/tables/src/Columns/BooleanColumn.php) | todo | — |
| CheckboxColumn | [`Columns/CheckboxColumn.php`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | todo | — |
| ColorColumn | [`Columns/ColorColumn.php`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | todo | — |
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
| Filters / BaseFilter | [`Filters/BaseFilter.php`](../../../vendor/filament/tables/src/Filters/BaseFilter.php) | todo | — |
| Filters / Filter | [`Filters/Filter.php`](../../../vendor/filament/tables/src/Filters/Filter.php) | todo | — |
| Filters / Indicator | [`Filters/Indicator.php`](../../../vendor/filament/tables/src/Filters/Indicator.php) | todo | — |
| Filters / MultiSelectFilter | [`Filters/MultiSelectFilter.php`](../../../vendor/filament/tables/src/Filters/MultiSelectFilter.php) | todo | — |
| Filters / QueryBuilder | [`Filters/QueryBuilder.php`](../../../vendor/filament/tables/src/Filters/QueryBuilder.php) | todo | — |
| Filters / QueryBuilder / Constraints | [`Filters/QueryBuilder/Constraints/`](../../../vendor/filament/tables/src/Filters/QueryBuilder/Constraints) | todo | — |
| Filters / SelectFilter | [`Filters/SelectFilter.php`](../../../vendor/filament/tables/src/Filters/SelectFilter.php) | todo | — |
| Filters / TernaryFilter | [`Filters/TernaryFilter.php`](../../../vendor/filament/tables/src/Filters/TernaryFilter.php) | todo | — |
| Filters / TrashedFilter | [`Filters/TrashedFilter.php`](../../../vendor/filament/tables/src/Filters/TrashedFilter.php) | todo | — |
| Grouping / Group | [`Grouping/Group.php`](../../../vendor/filament/tables/src/Grouping/Group.php) | todo | — |
| IconColumn | [`Columns/IconColumn.php`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | todo | — |
| ImageColumn | [`Columns/ImageColumn.php`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | todo | — |
| SelectColumn | [`Columns/SelectColumn.php`](../../../vendor/filament/tables/src/Columns/SelectColumn.php) | todo | — |
| TagsColumn | [`Columns/TagsColumn.php`](../../../vendor/filament/tables/src/Columns/TagsColumn.php) | todo | — |
| [`TextColumn`](../../../packages/tables/src/Columns/TextColumn.php) | [`src/Columns/TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | in progress | [text-column.md](text-column.md) |
| TextInputColumn | [`Columns/TextInputColumn.php`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | todo | — |
| ToggleColumn | [`Columns/ToggleColumn.php`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | todo | — |
| ViewColumn | [`Columns/ViewColumn.php`](../../../vendor/filament/tables/src/Columns/ViewColumn.php) | todo | — |

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
tiers carry. The twenty landed concerns folded into their component's detail file
are named in the [preamble](#tables--port-progress) and carry no row here.

`Contracts/HasTable.php` is not listed in
[Supporting surface](#supporting-surface): it is inseparable from the trait it
pairs with, so it shares the first row below.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`HasTable`](../../../packages/tables/src/Concerns/HasTable.php) / [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`src/Concerns/InteractsWithTable.php`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php), [`src/Contracts/HasTable.php`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | [has-table.md](has-table.md) |
| Host / CanBeStriped | [`Concerns/CanBeStriped.php`](../../../vendor/filament/tables/src/Concerns/CanBeStriped.php) | todo | — |
| Host / CanDeferLoading | [`Concerns/CanDeferLoading.php`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | todo | [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| Host / CanGroupRecords | [`Concerns/CanGroupRecords.php`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | todo | — |
| Host / CanPaginateRecords | [`Concerns/CanPaginateRecords.php`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php) | in progress | [has-table.md](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) |
| Host / CanPollRecords | [`Concerns/CanPollRecords.php`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | The whole trait is a deprecated host getter (`getTablePollingInterval()`), and the project has never ported a deprecated Filament surface — the interval it fronts is `Table::poll()`, the `Table / CanPollRecords` row below ([has-table.md](has-table.md)) |
| Host / CanReorderRecords | [`Concerns/CanReorderRecords.php`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | todo | — |
| Host / CanSearchRecords | [`Concerns/CanSearchRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | in progress | [has-table.md](has-table.md#search-is-the-global-term-only) |
| Host / CanSortRecords | [`Concerns/CanSortRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php) | in progress | [has-table.md](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) |
| Host / CanSummarizeRecords | [`Concerns/CanSummarizeRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | todo | — |
| Host / HasActions | [`Concerns/HasActions.php`](../../../vendor/filament/tables/src/Concerns/HasActions.php) | skipped | Every method in the file is a deprecated `*TableAction` delegate to the actions-package equivalent, and the project has never ported a deprecated Filament surface. What it fronts is built: mounting, resolution and record binding go through actions' [`HasActions`](../actions/has-actions.md#resolvetableaction-drops-the-bulk-arm) and the [`InteractsWithTable`](has-table.md#the-contract-is-nine-methods-of-filaments-forty-six) contract |
| Host / HasBulkActions | [`Concerns/HasBulkActions.php`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | blocked | table bulk actions — [has-table.md](has-table.md) |
| Host / HasColumnManager | [`Concerns/HasColumnManager.php`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | todo | — |
| Host / HasColumns | [`Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | todo | — |
| Host / HasContent | [`Concerns/HasContent.php`](../../../vendor/filament/tables/src/Concerns/HasContent.php) | todo | — |
| Host / HasEmptyState | [`Concerns/HasEmptyState.php`](../../../vendor/filament/tables/src/Concerns/HasEmptyState.php) | todo | — |
| Host / HasFilters | [`Concerns/HasFilters.php`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | todo | — |
| Host / HasHeader | [`Concerns/HasHeader.php`](../../../vendor/filament/tables/src/Concerns/HasHeader.php) | todo | — |
| Host / HasRecordAction | [`Concerns/HasRecordAction.php`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | The file is one deprecated `getTableRecordAction()` host hook, superseded upstream by `Table::recordAction()` — the `Table / HasRecordAction` row below tracks that one |
| Host / HasRecords | [`Concerns/HasRecords.php`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | [has-table.md](has-table.md) |
| Table / CanBeStackedOnMobile | [`Table/Concerns/CanBeStackedOnMobile.php`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStackedOnMobile.php) | todo | — |
| Table / CanBeStriped | [`Table/Concerns/CanBeStriped.php`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) | todo | — |
| Table / CanDeferLoading | [`Table/Concerns/CanDeferLoading.php`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php) | todo | [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| Table / CanGroupRecords | [`Table/Concerns/CanGroupRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | todo | — |
| Table / CanPollRecords | [`Table/Concerns/CanPollRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | todo | [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| Table / CanReorderRecords | [`Table/Concerns/CanReorderRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php) | todo | — |
| Table / CanSummarizeRecords | [`Table/Concerns/CanSummarizeRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | todo | — |
| Table / HasArguments | [`Table/Concerns/HasArguments.php`](../../../vendor/filament/tables/src/Table/Concerns/HasArguments.php) | todo | — |
| Table / HasBulkActions | [`Table/Concerns/HasBulkActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php) | blocked | table bulk actions — [table.md](table.md) |
| Table / HasColumnManager | [`Table/Concerns/HasColumnManager.php`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | todo | — |
| Table / HasContent | [`Table/Concerns/HasContent.php`](../../../vendor/filament/tables/src/Table/Concerns/HasContent.php) | todo | — |
| Table / HasFilterIndicators | [`Table/Concerns/HasFilterIndicators.php`](../../../vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php) | todo | — |
| Table / HasFilters | [`Table/Concerns/HasFilters.php`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | todo | — |
| Table / HasHeader | [`Table/Concerns/HasHeader.php`](../../../vendor/filament/tables/src/Table/Concerns/HasHeader.php) | todo | — |
| Table / HasHeaderActions | [`Table/Concerns/HasHeaderActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php) | todo | — |
| Table / HasHeadings | [`Table/Concerns/HasHeadings.php`](../../../vendor/filament/tables/src/Table/Concerns/HasHeadings.php) | todo | — |
| [`Table / HasQuery`](../../../packages/tables/src/Table/Concerns/HasQuery.php) | [`Table/Concerns/HasQuery.php`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | in progress | [table.md](table.md#the-relation-arm-of-the-query-lane-waits-on-a-future-tables-relations-epic) |
| Table / HasQueryStringIdentifier | [`Table/Concerns/HasQueryStringIdentifier.php`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | todo | — |
| Table / HasRecordAction | [`Table/Concerns/HasRecordAction.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php) | todo | — |
| Table / HasRecordClasses | [`Table/Concerns/HasRecordClasses.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordClasses.php) | todo | — |
| Table / HasRecordUrl | [`Table/Concerns/HasRecordUrl.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | todo | — |
| Table / HasToolbarActions | [`Table/Concerns/HasToolbarActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php) | todo | — |
| Column / BelongsToGroup | [`Columns/Concerns/BelongsToGroup.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToGroup.php) | todo | — |
| Column / BelongsToLayout | [`Columns/Concerns/BelongsToLayout.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToLayout.php) | todo | — |
| Column / CanBeCopied | [`Columns/Concerns/CanBeCopied.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeCopied.php) | todo | — |
| Column / CanBeDisabled | [`Columns/Concerns/CanBeDisabled.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | todo | — |
| Column / CanBeHidden | [`Columns/Concerns/CanBeHidden.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php) | todo | — |
| Column / CanBeHiddenResponsively | [`Columns/Concerns/CanBeHiddenResponsively.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | todo | — |
| Column / CanBeInline | [`Columns/Concerns/CanBeInline.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeInline.php) | todo | — |
| Column / CanBeSummarized | [`Columns/Concerns/CanBeSummarized.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | todo | — |
| Column / CanBeToggled | [`Columns/Concerns/CanBeToggled.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | todo | — |
| Column / CanBeValidated | [`Columns/Concerns/CanBeValidated.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php) | todo | — |
| Column / CanCallAction | [`Columns/Concerns/CanCallAction.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanCallAction.php) | todo | — |
| Column / CanOpenUrl | [`Columns/Concerns/CanOpenUrl.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) | todo | — |
| Column / CanUpdateState | [`Columns/Concerns/CanUpdateState.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php) | todo | — |
| Column / HasExtraCellAttributes | [`Columns/Concerns/HasExtraCellAttributes.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasExtraCellAttributes.php) | todo | — |
| Column / HasExtraHeaderAttributes | [`Columns/Concerns/HasExtraHeaderAttributes.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php) | todo | — |
| Column / HasRowLoopObject | [`Columns/Concerns/HasRowLoopObject.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | todo | [column.md](column.md#rowloop-waits-on-a-decision-about-twigs-loop-not-on-the-table-template) |
| Column / HasSpace | [`Columns/Concerns/HasSpace.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasSpace.php) | todo | — |
| Column / HasTooltip | [`Columns/Concerns/HasTooltip.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasTooltip.php) | todo | — |
| [`Column / InteractsWithTableQuery`](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | [`Columns/Concerns/InteractsWithTableQuery.php`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | in progress | `applySort()` and `applySearchConstraint()` are in; the aggregate and eager-loading arms wait on a future tables-relations epic — [column.md](column.md#relationship-traversal-is-a-future-tables-relations-epics) |
| Summarizer / BelongsToColumn | [`Columns/Summarizers/Concerns/BelongsToColumn.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/BelongsToColumn.php) | todo | — |
| Summarizer / CanBeHidden | [`Columns/Summarizers/Concerns/CanBeHidden.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/CanBeHidden.php) | todo | — |
| Summarizer / CanFormatState | [`Columns/Summarizers/Concerns/CanFormatState.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/CanFormatState.php) | todo | — |
| Summarizer / HasLabel | [`Columns/Summarizers/Concerns/HasLabel.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/HasLabel.php) | todo | — |
| Summarizer / InteractsWithTableQuery | [`Columns/Summarizers/Concerns/InteractsWithTableQuery.php`](../../../vendor/filament/tables/src/Columns/Summarizers/Concerns/InteractsWithTableQuery.php) | todo | — |
| Filter / BelongsToTable | [`Filters/Concerns/BelongsToTable.php`](../../../vendor/filament/tables/src/Filters/Concerns/BelongsToTable.php) | todo | — |
| Filter / CanBeHidden | [`Filters/Concerns/CanBeHidden.php`](../../../vendor/filament/tables/src/Filters/Concerns/CanBeHidden.php) | todo | — |
| Filter / CanResetState | [`Filters/Concerns/CanResetState.php`](../../../vendor/filament/tables/src/Filters/Concerns/CanResetState.php) | todo | — |
| Filter / CanSpanColumns | [`Filters/Concerns/CanSpanColumns.php`](../../../vendor/filament/tables/src/Filters/Concerns/CanSpanColumns.php) | todo | — |
| Filter / HasColumns | [`Filters/Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasColumns.php) | todo | — |
| Filter / HasDefaultState | [`Filters/Concerns/HasDefaultState.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasDefaultState.php) | todo | — |
| Filter / HasIndicators | [`Filters/Concerns/HasIndicators.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasIndicators.php) | todo | — |
| Filter / HasLabel | [`Filters/Concerns/HasLabel.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasLabel.php) | todo | — |
| Filter / HasName | [`Filters/Concerns/HasName.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasName.php) | todo | — |
| Filter / HasOptions | [`Filters/Concerns/HasOptions.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasOptions.php) | todo | — |
| Filter / HasPlaceholder | [`Filters/Concerns/HasPlaceholder.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasPlaceholder.php) | todo | — |
| Filter / HasRelationship | [`Filters/Concerns/HasRelationship.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasRelationship.php) | todo | — |
| Filter / HasSchema | [`Filters/Concerns/HasSchema.php`](../../../vendor/filament/tables/src/Filters/Concerns/HasSchema.php) | todo | — |
| Filter / InteractsWithTableQuery | [`Filters/Concerns/InteractsWithTableQuery.php`](../../../vendor/filament/tables/src/Filters/Concerns/InteractsWithTableQuery.php) | todo | — |
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
| [`TablesIconAlias`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`View/TablesIconAlias.php`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | in progress | [tables-bundle.md](tables-bundle.md#the-alias-set-is-five-constants-of-filaments-23) |
| [`SylamentTablesBundle`](../../../packages/tables/src/SylamentTablesBundle.php) | [`TablesServiceProvider.php`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | in progress | [tables-bundle.md](tables-bundle.md) |
| [Styles](../../../packages/tables/assets/src/styles) | [`resources/css/`](../../../vendor/filament/tables/resources/css) | in progress | `table.css`, `header-toolbar.css` and `text-column.css` ship — the last split along Filament's own line into the cell's layout, the item's type and the description ([text-column.md](text-column.md#the-item-element-is-never-merged-into-the-outer-div)); the remaining column, filter, summary and column-manager sheets follow their features |
| [`Colors\Components\TextColumnComponent\ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) | [`View/Components/Columns/TextColumnComponent/ItemComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent.php) | done | [text-column.md](text-column.md#the-item-policy-measures-the-dark-surface-against-gray-900) |
| [`Colors\Components\TextColumnComponent\ItemComponent\IconComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent/IconComponent.php) | [`View/Components/Columns/TextColumnComponent/ItemComponent/IconComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent/IconComponent.php) | done | [text-column.md](text-column.md#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) |
| HeaderActionsPosition | [`Actions/HeaderActionsPosition.php`](../../../vendor/filament/tables/src/Actions/HeaderActionsPosition.php) | todo | — |
| Editable (contract) | [`Columns/Contracts/Editable.php`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | todo | — |
| ColumnManagerLayout | [`Enums/ColumnManagerLayout.php`](../../../vendor/filament/tables/src/Enums/ColumnManagerLayout.php) | todo | — |
| ColumnManagerResetActionPosition | [`Enums/ColumnManagerResetActionPosition.php`](../../../vendor/filament/tables/src/Enums/ColumnManagerResetActionPosition.php) | todo | — |
| FiltersLayout | [`Enums/FiltersLayout.php`](../../../vendor/filament/tables/src/Enums/FiltersLayout.php) | todo | — |
| FiltersResetActionPosition | [`Enums/FiltersResetActionPosition.php`](../../../vendor/filament/tables/src/Enums/FiltersResetActionPosition.php) | todo | — |
| [`PaginationMode`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Enums/PaginationMode.php`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | in progress | The enum ships whole; `Default` and `Simple` are read by [`paginateTableQuery()`](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half), `Cursor` is refused until a keyset carrier exists — [table.md](table.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) |
| [`RecordActionsPosition`](../../../packages/tables/src/Enums/RecordActionsPosition.php) | [`Enums/RecordActionsPosition.php`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php) | done | [table.md](table.md#the-position-default-has-no-content-arm-to-choose-between-yet) |
| RecordCheckboxPosition | [`Enums/RecordCheckboxPosition.php`](../../../vendor/filament/tables/src/Enums/RecordCheckboxPosition.php) | todo | — |
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
| View / Components | [`View/Components/Columns/IconColumnComponent/`](../../../vendor/filament/tables/src/View/Components/Columns/IconColumnComponent), [`Columns/Summarizers/CountComponent/`](../../../vendor/filament/tables/src/View/Components/Columns/Summarizers/CountComponent) | todo | the two `TextColumnComponent` policies have rows of their own above; what is left of the directory is the icon column's and the count summarizer's, each waiting on its component |
| views/components/column-manager | [`views/components/column-manager/index.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/index.blade.php) | todo | — |
| views/components/column-manager/content | [`views/components/column-manager/content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php) | todo | — |
| views/components/columns/layout | [`views/components/columns/layout.blade.php`](../../../vendor/filament/tables/resources/views/components/columns/layout.blade.php) | todo | — |
| views/components/filters | [`views/components/filters.blade.php`](../../../vendor/filament/tables/resources/views/components/filters.blade.php) | todo | — |
| views/components/search-field | [`views/components/search-field.blade.php`](../../../vendor/filament/tables/resources/views/components/search-field.blade.php) | in progress | ported **inline** into the [table partial](../../../packages/tables/templates/partials/Table/index.html.twig)'s toolbar rather than as a component of its own — its `wireModel` prop exists to serve the per-column search field, the second call site, which is unported ([has-table.md](has-table.md#search-is-the-global-term-only)); it becomes a component in the change that lands one |
| views/components/summary | [`views/components/summary/index.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/index.blade.php) | todo | — |
| views/components/summary/row | [`views/components/summary/row.blade.php`](../../../vendor/filament/tables/resources/views/components/summary/row.blade.php) | todo | — |
| Translations | [`resources/lang/`](../../../vendor/filament/tables/resources/lang) | todo | — |
| Scripts | [`resources/js/`](../../../vendor/filament/tables/resources/js) | todo | — |
