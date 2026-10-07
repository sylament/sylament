---
description: 'The root object of the tables package, the counterpart to schemas'' `Schema`: a `ViewComponent` a host builds through `HasTable`, carrying the record… (draft)'
ports:
  - vendor/filament/tables/src/Table.php
  - vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php
  - vendor/filament/tables/src/Table/Concerns/HasRecords.php
  - vendor/filament/tables/src/Table/Concerns/HasColumns.php
owns:
  - packages/tables/assets/src/controllers/table_controller.ts
  - packages/tables/assets/src/styles/components/table.css
  - packages/tables/src/Table.php
  - packages/tables/src/Table/Concerns/BelongsToLiveComponent.php
  - packages/tables/src/Table/Concerns/CanBeStriped.php
  - packages/tables/src/Table/Concerns/CanGroupRecords.php
  - packages/tables/src/Table/Concerns/CanReorderRecords.php
  - packages/tables/src/Table/Concerns/HasColumnManager.php
  - packages/tables/src/Table/Concerns/HasColumns.php
  - packages/tables/src/Table/Concerns/HasHeader.php
  - packages/tables/src/Table/Concerns/HasHeadings.php
  - packages/tables/src/Table/Concerns/HasQueryStringIdentifier.php
  - packages/tables/src/Table/Concerns/HasRecordAction.php
  - packages/tables/src/Table/Concerns/HasRecordClasses.php
  - packages/tables/src/Table/Concerns/HasRecordUrl.php
  - packages/tables/src/Table/Concerns/HasRecords.php
  - packages/tables/templates/partials/Table/
---
# Table (tables)

[`Sylament\Tables\Table`](../../../packages/tables/src/Table.php) — the root object of the tables package, the counterpart to schemas' [`Schema`](../schemas/schema.md): a [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) a host builds through [`HasTable`](has-table.md), carrying the record data source and the empty-state copy.
It ports Filament's [`Filament\Tables\Table`](../../../vendor/filament/tables/src/Table.php) together with the `Table\Concerns\` traits it composes — sixteen today ([`BelongsToLiveComponent`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php), [`HasRecords`](../../../packages/tables/src/Table/Concerns/HasRecords.php), [`HasQuery`](../../../packages/tables/src/Table/Concerns/HasQuery.php), [`HasEmptyState`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php), [`HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php), [`HasActions`](../../../packages/tables/src/Table/Concerns/HasActions.php), [`HasAggregateValues`](../../../packages/tables/src/Table/Concerns/HasAggregateValues.php), [`HasRecordActions`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php), [`HasToolbarActions`](../../../packages/tables/src/Table/Concerns/HasToolbarActions.php), [`HasHeaderActions`](../../../packages/tables/src/Table/Concerns/HasHeaderActions.php), [`HasBulkActions`](../../../packages/tables/src/Table/Concerns/HasBulkActions.php), [`CanPaginateRecords`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php), [`CanSortRecords`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php), [`CanSearchRecords`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php), [`HasFilters`](../../../packages/tables/src/Table/Concerns/HasFilters.php), [`HasFilterIndicators`](../../../packages/tables/src/Table/Concerns/HasFilterIndicators.php)), thirty-one in Filament, which has no counterpart for `HasAggregateValues` — the side store the batched aggregate read fills is the port's own.
Eleven of the sixteen are folded into the surface table below rather than earning rows upstairs, per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature); the sixteen still unported keep a per-file row in the package [README](README.md#files), the way [`actions`](../actions/README.md#files) tracks the concerns `Action` has not reached yet — as do the three action sets, keeping theirs because the two selection ones still owe the settings [listed below](has-actions.md#the-selection-getters-answer-from-more-than-their-setters) and `HasToolbarActions` is simply **done** where a reader looks for it, and the two filter concerns, keeping theirs for the form seam and the chrome actions they do not carry yet.
The rows below group those files by feature, which is what the per-file rows upstairs point at.

The `Table` fetches nothing itself.
It declares *how* records are reached — a `records()` closure, or a Doctrine `query()` — and the host resolves and caches them; see [`has-table.md`](has-table.md) for that half of the axis.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(InteractsWithTable $liveComponent)`](../../../packages/tables/src/Table.php) | [`Table::make()`](../../../vendor/filament/tables/src/Table.php) | done | — | [diverges](../support/component.md#make-arguments-move-from-constructors-to-setters) |
| [`liveComponent()` / `getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php) | [`BelongsToLivewire`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php) | done | — | [semantics](#the-host-binding-is-required-where-the-schemas-one-is-optional) |
| [`records()` / `getDataSource()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::records()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [has-table.md](has-table.md#the-records-closure-gets-filaments-eight-injections-three-of-them-typed-differently) |
| [`getRecords()` — always the current page's keyed array](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecords()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`getPaginator(): ?Paginator`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | — (Sylament-only; Filament's `getRecords()` **is** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`$evaluationIdentifier = 'table'` + the `$liveComponent` name injection](../../../packages/tables/src/Table.php) | [`Table::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Table.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Table.php) + [the partial it names](../../../packages/tables/templates/partials/Table/index.html.twig) | [`$view = 'filament-tables::index'`](../../../vendor/filament/tables/src/Table.php), [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) · [diverges](#every-rule-in-tablecss-is-scoped-under-the-sy-ta-root-one-class-above-vendors) · [semantics](#a-markup-header-label-swaps-the-sort-control-for-a-span-that-names-its-three-events) · [semantics](#aria-sort-is-dropped-with-null-because-false-would-render-as-a-string) · [diverges](has-actions.md#the-selection-cells-carry-no-header-cell-class-and-their-checkbox-skin-is-supports) |
| [Record labels and titles — `recordLabel()` / `pluralRecordLabel()` / `recordTitle()` / `recordTitleAttribute()` / `getRecordLabel()` / `getPluralRecordLabel()` / `getRecordTitle()` / `getRecordTitleAttribute()` / `hasCustomRecordTitle()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [rename](../renames.md#renames) · [semantics](#the-record-title-reads-the-attribute-through-the-accessor-and-falls-back-to-the-label) · [semantics](has-empty-state.md#the-empty-state-texts-read-the-record-labels) |
| [Record identity — `getRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecordKey()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) |
| [Morph-stable row and cell keys](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `wire:key` stamps](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-morph-key-is-an-id-attribute-not-wirekey) · [semantics](#the-row-and-cell-morph-keys-disagree-on-record-vs-records) |
| The pivot surface — `hasPivotRecordKeys()`, `allowDuplicates()`, `selectPivotDataInQuery()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php), [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | skipped | — | The `BelongsToMany` pivot arm: a Doctrine association table carrying payload is an entity of its own rather than a pivot with casts, so there is no pivot model to key rows by, to allow duplicates of, or to alias columns out of |
| [`columns()` / `getColumns()` / `getColumn()` / `getVisibleColumns()` / `flushCachedVisibleColumns()`](../../../packages/tables/src/Table/Concerns/HasColumns.php) | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | in progress | — | [column.md](column.md) · [detail](#columns-takes-columns-only-and-does-not-push) · [semantics](#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again) |
| Column grouping and layouts — `pushColumns()`, `getColumnsLayout()`, `hasColumnGroups()` | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | todo | — | [detail](#columns-takes-columns-only-and-does-not-push) |
| [The column manager — `columnManager()` / `deferColumnManager()` / `reorderableColumns()` / `persistColumnsInSession()` / `columnManagerLayout()` / `columnManagerResetActionPosition()` / `columnManagerColumns()` / `columnManagerMaxHeight()` / `columnManagerWidth()` and the trigger and apply actions](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | in progress | — | [column-manager.md](column-manager.md) · [host-has-column-manager.md](host-has-column-manager.md#the-column-state-is-re-derived-against-the-definition-on-every-read) · [detail](#the-column-managers-table-tier-ships-whole-and-the-chrome-around-it-now-does-too) |
| [The query-string identifier — `queryStringIdentifier()` / `getQueryStringIdentifier()`](../../../packages/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | [`HasQueryStringIdentifier`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | done | — | [has-table.md](has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) |
| [`recordUrl()` / `openRecordUrlInNewTab()` / `hasCustomRecordUrl()` / `getRecordUrl()` / `shouldOpenRecordUrlInNewTab()` / `extraRecordLinkAttributes()` / `getExtraRecordLinkAttributes()`](../../../packages/tables/src/Table/Concerns/HasRecordUrl.php) | [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | done | — | [semantics](#the-record-anchor-spreads-its-extra-attributes-first-so-the-derived-pair-wins) |
| `getExtraRecordLinkAttributeBag()` | [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | skipped | — | The bag wrapper has no consumer here: the row anchor spreads `getExtraRecordLinkAttributes()` through `attrs()`, which builds the bag itself — [and under the derived pair](#the-record-anchor-spreads-its-extra-attributes-first-so-the-derived-pair-wins) |
| [`recordAction()` / `getRecordAction()`](../../../packages/tables/src/Table/Concerns/HasRecordAction.php) | [`HasRecordAction`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php) | done | — | — |
| [The row chrome — the clickable marker, the stripe and the selected tint](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css), [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`index.blade.php`'s `<tr>` class list](../../../vendor/filament/tables/resources/views/index.blade.php), [`row.css`](../../../vendor/filament/tables/resources/css/row.css) | done | — | — |
| [The cell wrapper — `<a>` / `<button>` / `<div>` chosen per record and per column](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `$columnWrapperTag` match](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [semantics](#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) |
| [The reorder chrome — the toolbar trigger, the drag handle column, the sortable mount, the row markers and the indicator](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css), [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`index.blade.php`'s `fi-ta-reorder-*`](../../../vendor/filament/tables/resources/views/index.blade.php), [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) | done | — | [diverges](#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) · [tables-bundle.md](#the-table-controller-absorbs-four-jobs-livewire-does-with-attributes) |
| [Grouping — `groups()` / `getGroups()` / `getGroup()` / `defaultGroup()` / `getDefaultGroup()` / `isDefaultGroupSelectable()` / `getGrouping()` / `groupsOnly()` / `isGroupsOnly()` / the four chrome flags / `groupRecordsTriggerAction()` / `getGroupRecordsTriggerAction()`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php), and the summarizers | [`CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | in progress | — | [grouping.md](grouping.md) · [summarizer.md](summarizer.md) — the grouping settings ship whole apart from the deprecated `groupsInDropdownOnDesktop()` alias, and `summaries()` / `hasPageSummary()` / `hasAllTableSummary()` / `hasSummary()` beside them; what holds the row is [`Count::icons()`](summarizer.md#counticons-ships-as-api-and-refuses-to-summarize) |
| [Reordering — `reorderable()` / `isReorderable()` / `isReordering()` / `getReorderColumn()` / `getReorderDirection()` / `authorizeReorder()` / `isReorderAuthorized()` / `reorderRecordsTriggerAction()` / `getReorderRecordsTriggerAction()` / `beforeReordering()` / `afterReordering()` / `callBeforeReordering()` / `callAfterReordering()`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) + [`HasReorderAnimationDuration`](../../../packages/support/src/Components/Concerns/HasReorderAnimationDuration.php) | [`CanReorderRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php), [`Support\Concerns\HasReorderAnimationDuration`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | done | — | [diverges](#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) · [host-can-reorder-records.md](host-can-reorder-records.md#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) · [sortable.md](../support/sortable.md) |
| [`heading()` / `description()`](../../../packages/tables/src/Table/Concerns/HasHeader.php) + [`rootHeadingLevel()` / `getHeadingLevel()` / `getHeadingTag()`](../../../packages/tables/src/Table/Concerns/HasHeadings.php) | [`Table\Concerns\HasHeader`](../../../vendor/filament/tables/src/Table/Concerns/HasHeader.php), [`Table\Concerns\HasHeadings`](../../../vendor/filament/tables/src/Table/Concerns/HasHeadings.php) | in progress | `header()` — the custom-header arm takes a Blade `View`, and its Twig analogue waits for a consumer | [detail](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) |
| [`striped()` / `isStriped()`](../../../packages/tables/src/Table/Concerns/CanBeStriped.php) | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) | done | — | the host-side [`Concerns\CanBeStriped`](has-table.md#surface) it fronts is a deprecated shim and is **skipped** |
| [`recordClasses()` / `getRecordClasses()`](../../../packages/tables/src/Table/Concerns/HasRecordClasses.php) | [`HasRecordClasses`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordClasses.php) | done | — | — |
| Presentation — `stackedOnMobile()` / `isStackedOnMobile()`, `content()` / `getContent()` / `contentGrid()` / `getContentGrid()` / `contentFooter()` / `getContentFooter()` | [`Table\Concerns\CanBeStackedOnMobile`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStackedOnMobile.php), [`Table\Concerns\HasContent`](../../../vendor/filament/tables/src/Table/Concerns/HasContent.php) | blocked | the layout columns (`Columns\Layout`) | Both configure the **card** rendering rather than the table one — `content()` replaces the `<table>` with a caller-supplied list and `stackedOnMobile()` is the responsive half of that list, whose whole point is the label-above-value stack a `<td>` cannot make. Neither has anything to switch on until `Columns\Layout`'s `Split`/`Stack`/`Panel`/`Grid` and `contentGrid()` land, and [`AfterContent` renders nothing](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) for the same reason. They have a row apart from `striped()` and `recordClasses()`, which ship |
| [`HasDefaultDataFormattingSettings` — the eight formatting defaults, composed here](../../../packages/tables/src/Table.php) | [`Table.php`'s composed `Support\Concerns\HasDefaultDataFormattingSettings`](../../../vendor/filament/tables/src/Table.php) | done | — | [has-default-data-formatting-settings.md](../support/has-default-data-formatting-settings.md) — the trait is support's, and `TextColumn`'s date, money and numeric families are what read it off the table |
| `HasArguments`, `HasExtraAttributes` | [`Table.php`'s remaining composed traits](../../../vendor/filament/tables/src/Table.php) | todo | — | — |

Fifty-six rows are **done**, eleven **in progress**, seven **skipped**, seven **todo** and one **blocked**.
Rather than name all fifty-six, here is what the done set covers, feature by feature.
The **object itself**: `make()`, the host binding, the closure-injection wiring, and the partial that renders everything below.
The **data lanes**: the `records()` declaration, the whole `query()` lane with the two members that read off it (`hasQuery()`, `getRecordClass()`), the relation-scoped lane, and the host-delegating `getRecords()`, `getPaginator()` and `getRecordKey()` with the morph-stable row and cell keys built on the last of those.
The **browsing state**: the pagination surface, `paginatedWhileReordering()`, the sort-resolution surface with the default-sort family beside it, the eight search rows — the declaration, the chrome, the query half, the global indicator, the two session flags, and the per-column pair with the search row it renders — and the query-string identifier, beside the session-persistence quartet (`persistSortInSession()`, `persistSearchInSession()`, `persistColumnSearchesInSession()`, `persistFiltersInSession()`).
The **filters chrome**: the indicators row, the panel component, the dropdown, modal, above- and below-content layouts, and the three chrome actions.
The **column manager**, everything but its two deferrals: the nine settings, the trigger and apply actions, both layouts and both reset positions ([column-manager.md](column-manager.md)).
The **rows**: `recordUrl()` with its new-tab and extra-link members, `recordAction()`, `recordClasses()`, `striped()`, the record labels and titles the empty state reads, and the whole reorder surface with its chrome.
The **cell wrapper**, which the column catalog finished: the four-way tag choice is now decided from both tiers at once, and the row flipped with the `Column`-level arms it was waiting on.
The **record actions**: the per-record visible-action map and the toolbar action set.
The **formatting defaults**: support's `HasDefaultDataFormattingSettings`, composed here and read by `TextColumn`'s date, money and numeric families.
And the **empty state**, whole — heading, description, icon, the replacement block and its actions — beside `deferLoading()` and `poll()`, which share a row.

What is still **in progress** is where a named gap sits inside a shipped member: the column set (the layouts, and the manager against `ColumnGroup` and the `Width` enum), the grouping-and-summaries row against [`Count::icons()`](summarizer.md#counticons-ships-as-api-and-refuses-to-summarize), the record-action position rows, the big `HasFilters` row against the four refused layouts, and the header row against `header()`'s Blade-`View` arm.
The three chrome rows grouping touches — the header/toolbar rows, the selection rows and the row chrome — are **done**, with the selector, the group header row, its checkbox and the stripe's per-group restart.

The PHP surface is covered by [`TableTest`](../../../packages/tables/tests/Unit/TableTest.php) — `make()` binding the host, `records()` storing and returning the closure identity, the host-delegating `getRecordKey()`, the empty-state defaults, `columns()` keying by name and binding the table on each member while a second call resets the set, and both injection paths at once (`fn ($liveComponent)` resolved by the class's own [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/tables/src/Table.php), `fn (Table $table)` by the `EvaluatesClosures` fallback beneath it — by *name*, not by type: the parameter matches `$evaluationIdentifier`, the disjunct that short-circuits ahead of the `is_a($this, $parameterType)` arm beside it, which a differently named parameter would be needed to reach) — and, for `getRecords()`, by the host-side [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) it delegates into.

The browsing configuration is covered in the same file, built through a real `TableHost` so every assertion runs against a configured `Table` rather than a bare trait object.
Pagination: the untouched defaults as one case (paginated, `[5, 10, 25, 50]`, default `10`, no extreme links), `paginated(false)`, `paginated([12, 24])` routing its array into the page options *and* moving the default to `12`, the ten-preference against a list where ten is not first, the first-option fallback where there is no ten, and an explicit `defaultPaginationPageOption()` winning over both.
Sorting: one case gives the table a sortable and a plain column and asserts `getSortableVisibleColumn()` returns a `Column` for the first and null for both the second and a name that does not exist — the three arms of the resolver the header cell and [`sortTable()`](host-can-sort-records.md#sorttable-ignores-a-column-the-table-did-not-declare-sortable) both read.
Search: `isSearchable()` derived from the columns (globally searchable → true, individually-only → false, plain → false), then `searchable()` overriding it in both directions with `searchable(['author.name'])` also filling the extra columns; the chrome getters as one case; and the debounce translation as four values — the `null` default, `1s`, a bare `250`, and unparseable garbage falling back to 500.
The three query-half members are asserted where they are consumed, in the host's [`CanSearchRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanSearchRecordsTest.php): `shouldSplitSearchTerms()` on all three arms (the default `true`, an explicit `false`, a closure), `hasSearchUsingCallback()` beside the `searchUsing()` case it gates, and `applyExtraSearchConstraints()` through a table whose columns are **unsearchable**, so a match can only have come from the extra list — one term reaching a record through the string member and another only through the closure member, a third proving the null return adds nothing, and a fourth proving a returned builder is skipped while the constraint it applied itself still lands.

The rendered chrome is covered by the Integration [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php), against a fixture host that is sortable, searchable and paginated **at once** — one render pass with all three on, because the failure mode worth catching is a toolbar or footer that only renders when nothing else does.
Its assertions are Crawler selections, per the DOM-contract precedent above: a sortable `<th>` carrying `aria-sort="none"` around a `button.sy-ta-header-cell-sort-btn` whose `data-live-action-param` is `sortTable` and `data-live-column-param` the column name; an unsortable `<th>` with **no** button and **no** `aria-sort` attribute at all (the [null-not-false](#aria-sort-is-dropped-with-null-because-false-would-render-as-a-string) arm); the search input bound `debounce(500)|tableSearch`; and the footer's overview text, its single gap, its active item's `aria-current`, the page-2 button's live-action parameters and the per-page `<select>` bound to `tableRecordsPerPage` with the right option selected.
Two negative cases carry the rest of that host: a plain table renders neither `sy-pagination` nor `sy-ta-header-toolbar` anywhere in its markup, and a page **past the end** renders the empty state *and* the footer together — which is the only way back for a user who paged past the data, and the case a naive `{% if records %}` wrapper around the footer would break.
Three more cases drive a second host whose `records()` closure returns a carrier with **no total**, which is where the null arm of the footer condition is pinned: a middle page keeping prev/next while the window and the total drop out of the [simple footer](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both), an empty first page rendering no footer at all, and an empty page past the data keeping one.

The partial is covered by the Integration [`TableTest`](../../../packages/tables/tests/Integration/TableTest.php), which renders a real table through the booted kernel's Twig: the header labels and cell values for object records, one `sy-ta-row` per record (the per-cell `{% do column.record(record) %}` rebinding), the default empty state displacing the `<table>` entirely with its `heroicons:x-mark` fallback rendered as an `<svg>` and no description paragraph, a fully configured empty state, a `Markup` icon reaching the raw-passthrough arm of [`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php), and a `<script>` in a record value coming back escaped.

Two of its cases pin the **structure** rather than the strings, because this is the DOM contract the tables stylesheet and the demo's browser tests are written against.
They read the render back through a `DomCrawler` with the child combinator — `div.sy.sy-ta > div.sy-ta-ctn > table.sy-ta-table`, `tr.sy-ta-row > td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-text`, and `div.sy-ta-empty-state[role="status"] > div.sy-ta-empty-state-content` holding the icon background's `<svg>`, the `<h3>` heading and the `<p>` description — so a flattened wrapper or a renamed class fails here rather than silently downstream.
A substring assertion passes through both of those changes, which is why the counts are selected and not grepped.

Five more read the same way, for the row identity and the actions column.
The morph keys are asserted by *reading the host's id back* and selecting `tr[id="{hostId}.table.records.7"]` and `td[id="{hostId}.table.record.7.column.title"]`, so the two spellings and the record key that fills them are pinned as one string rather than as a substring.
The trailing default asserts the header `<th>` by all three of its class/`aria-label` markers *and* a header count of exactly two, which is what catches a duplicated or otherwise extra header cell — the three-marker selector already fails on a column rendered in the body but not the head, but it counts a second stray `<th>` just as happily as one.
It then pins the **side** on both halves, `th:last-child` and `td:last-child`, because every other selector in that case is side-blind: presence, the child nesting, the href and the header count all hold just as well for a column rendered leading, so without the two `:last-child` reads a regression that moved the default to the wrong side would pass a test whose name promises trailing.
The `BeforeColumns` case is the mirror image, selecting `td:first-child` and `th:first-child`.
The all-hidden case asserts the absence of `sy-ta-actions` from the whole render, which is the only assertion that can prove the column was skipped in the header too.
And the clone case gives two records a record-dependent `url()` and demands both hrefs, which fails if the actions are bound once and reused — the single bug the per-record clone exists to prevent.

## Details

### The column manager's table tier ships whole, and the chrome around it now does too

The feature as a whole — both tiers, the two enums, the components, the controller and every divergence — is [`column-manager.md`](column-manager.md); what follows is the table tier's own share of it.

Every setting Filament's `Table\Concerns\HasColumnManager` carries is here: `columnManager()` (an explicit answer, else derived — the manager appears for whatever it would have to edit, `hasReorderableColumns() || hasToggleableColumns()`), `deferColumnManager()` and `persistColumnsInSession()` (both defaulting **on**, and the session one is the only persistence flag in the package that does), `reorderableColumns()`, `columnManagerLayout()`, `columnManagerResetActionPosition()`, `columnManagerColumns()`, `columnManagerMaxHeight()`, `columnManagerWidth()` and the two action-modifying closures.
The state the settings govern lives one tier up, on [`Concerns\HasColumnManager`](host-has-column-manager.md#the-column-state-is-re-derived-against-the-definition-on-every-read).

Three narrowings from vendor.
The width is a raw CSS length rather than a `Width` enum, which this package does not carry, so `getColumnManagerWidth()` is genuinely nullable.
The eight deprecated `toggleColumns*` / `columnToggleForm*` aliases are skipped, as every deprecated Filament surface is.
And the trigger carries none of vendor's modal-footer machinery — the manager's apply and reset render in the partial in both layouts, exactly as the filters pair does.

The two actions mirror that filters pair, which the same file already holds.
`getColumnManagerTriggerAction()` opens client-side and never reaches the server: `liveComponentClickHandlerEnabled(false)`, and under the modal layout an invoker `command` / `commandfor` pair stamped **after** the customization closure has run, so a closure setting extra attributes of its own cannot leave the modal with nothing that opens it.
`getColumnManagerApplyAction()`'s visibility *is* the defer flag.

`resolveColumnManagerIcon()` reads the alias off an **injected** `IconManager`, the way [`CanReorderRecords`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) and [`IconColumn`](icon-column.md) do, falling back to a bare manager — which resolves nothing — for a table built outside the container.
It is deliberately *not* `resolveFiltersIcon()`'s twin, which returns a closure taking `?IconManager $icons = null` and lets the evaluator fill it: the `Invoker` holds `service_container` and `IconManager` is a **private** service, so `has()` is false, the parameter takes its `null` default and the alias is never resolved at all.
The injection is named `setColumnManagerIconManager()` rather than `setIconManager()` because `Table` flattens `CanReorderRecords` into the same class and a second setter under that name is a trait collision, not a second injection.

**Adjacent, pre-existing, and deliberately not fixed here:** [`HasFilters::resolveFiltersIcon()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) still carries the closure shape, so `ACTIONS_FILTER` and `FILTERS_REMOVE_ALL_BUTTON` never resolve an application override — verified by registering an alias and reading the trigger's icon back unchanged.
It is the only other site in `packages/*/src` with that shape.

The chrome those two actions are rendered into ships with them — the `ColumnManager` and `ColumnManagerContent` Twig components, the `sylament--tables--column-manager` controller that writes the draft, `column-manager.css`, and the partial's two branches — so what keeps this row **in progress** is neither tier's plumbing but two deferrals recorded there: [`ColumnGroup`](column-manager.md#the-columngroup-half-is-the-whole-of-what-is-left) and the [`Width` enum](column-manager.md#the-width-is-a-css-length-and-the-columns-to-width-match-is-not-ported) `columnManagerWidth()` shares with `filtersFormWidth()` above.

### `columns()` takes columns only, and does not push

Filament's `columns()` accepts three kinds of member — `Column`, `ColumnGroup` and a `Columns\Layout\Component` — resets six properties, and delegates to `pushColumns()`, which sorts each member into the flat `$columns` map, the `$columnsLayout` list and the `$columnGroups` map, then walks the result to cache each column's action.
Sylament's takes `array<Column>`, resets one property, and keys the map by [`getName()`](column.md).

Everything trimmed is a tier that does not exist yet: groups and layouts are unported ([column.md](column.md) tracks both), and so are column actions — the record-action lane put actions on the *row*, not in a cell — so the action-caching walk has nothing to cache.
What is kept is the shape that matters — `columns()` **resets** rather than appends, and binds itself to each member as it goes, so a second call replaces the set and every column can reach the table and, through it, the host.
Both are pinned by [`TableTest`](../../../packages/tables/tests/Unit/TableTest.php).
`pushColumns()` returns as the public append seam when the column groups and layouts land — the tier the sentence above names, not record grouping, which appends no column at all.

## Semantics

### The visible set asks each column twice, and is memoised until `columns()` is called again

`getVisibleColumns()` filters on `isVisible() && !isToggledHidden()`, and the second half reaches `isToggleable()`, which asks `isHidden()` again — so one walk evaluates a column's `visible()` closure **twice**, Filament-exactly.
A closure with a side effect (a counter, a log line, a query) therefore fires twice per walk, which is the reason to keep such closures pure.

The walk itself is memoised on `$cachedVisibleColumns`, because the header, the row loop and the eager-loading pass each read it and the toggle half hops Column → Table → host → the applied column state on every column.
Two things clear it: `flushCachedVisibleColumns()`, the seam a host takes after writing new column state within one request, and `columns()` itself, since re-declaring the columns mid-request is how a reorder lands.
Nothing else invalidates it — a column mutated in place after the first read keeps its old verdict for the rest of the request.

### A `Markup` header label swaps the sort control for a `<span>` that names its three events

A column label is `string|Markup|Closure|null`, and a `Markup` label may contain interactive markup, which is invalid inside a `<button>`.
The header cell follows vendor and swaps the sortable trigger to a `<span role="button" tabindex="0">` when the label [is `Markup`](../../../packages/support/src/Twig/Extension/MarkupExtension.php); a plain string keeps the `<button>`.

The swap costs three bindings rather than one.
A `<button>` gets Stimulus's default event for free, so `data-action="live#action"` is enough; a `<span>` has no default event, and Stimulus **skips a bare action on it silently** — no error, no console warning, a header that simply does not sort.
So the span names `click`, `keydown.enter` and `keydown.space` explicitly, chained through the `|stimulus_action` filter rather than merged as three maps, because [`attrs()`](../../../packages/support/src/Twig/Extension/AttributeExtension.php) keeps only the last `data-action` it is handed and would drop the first two without complaint.

### The cell wrapper interleaves the column arms with the record ones

Every cell renders inside a wrapper element chosen from **both** tiers at once: an `<a>`, a `<button>` or a `<div>`, decided by one match in which the record and column conditions are interleaved rather than layered — vendor's, ported verbatim:

- the `'a'` arm is `(columnUrl or (recordUrl is not null and columnAction is null)) and not columnHasStateBasedUrls and not isColumnClickDisabled`,
- the `'button'` arm is `(columnAction or recordAction is not null) and not columnHasStateBasedUrls and not isColumnClickDisabled`,
- everything else is a `'div'`, including every cell while [reordering](#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render), which is vendor's `$isColumnClickDisabled` reached through its `|| $isReordering` half.

Four consequences a layered reading gets wrong.
The column arms **override** the record ones rather than sitting above them: a column URL wins the `href` (`columnUrl ?: recordUrl`), and a column *action* suppresses the record URL's anchor entirely — that is what `recordUrl is not null and columnAction is null` says.
The **new-tab** read is per arm with it — `columnUrl ? column.shouldOpenUrlInNewTab : openRecordUrlInNewTab` — so `shouldOpenRecordUrlInNewTab()` is not the only source.
`getExtraRecordLinkAttributes()` spreads only under `columnUrl is null and recordUrl is not null`, because the record's link attributes belong to the record's link; the guard sits at the spread site rather than at the `{% set %}` that computes them.
It spreads **first** in the `'a'` arm's map, ahead of `href` and `target`, so the derived pair wins a collision — a caller's `extraRecordLinkAttributes(['href' => …])` cannot displace the URL the table just computed, because a later key wins in a Twig hash literal.
That is the same ordering the [row anchor](#the-record-anchor-spreads-its-extra-attributes-first-so-the-derived-pair-wins) uses, for the same reason.
And a column whose URL is a function of its own state ([`hasStateBasedUrls()`](column.md#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom)) renders a `div` and lets its own partial draw one anchor per item — that negation has no record-level twin at all.

The `<button>` arm's live wiring branches three ways, and Twig cannot type-sniff a closure against an `Action`, so the choice is made in PHP by [`Column::getActionKind()`](column.md): `action` mounts through the record-action wiring under the **action's** own name (`columnAction.getName`, not the column's — the two coincide only when the column happens to name its action after itself), `closure` calls the host's [`callTableColumnAction()`](has-columns.md), and `null` falls through to the record action unchanged.
Each arm builds its whole attribute map **before** the single `attrs()` call, because `attrs()` keeps only the last `data-action` it is handed and two wiring sources meeting there would silently drop one.

The `action` arm only resolves because the table-tier `columns()` caches the instance: after binding each column it walks the set again and `cacheAction()`s every `action()` that is an `Action` rather than a closure, so `Table::getAction()` — the map actions' `resolveTableAction()` reads — knows the name the wrapper mounts.
Without it the mount unmounts silently, since `cacheMountedActions()` swallows the `ActionNotResolvableException`.
Vendor's loop is ported as it stands, so none of the `defaultSize`/`defaultTemplate` the record arm applies is applied here: a column action is mounted from the cell, never rendered as a trigger.

A second `columns()` call rebuilds the column map but **not** the action cache, so the replaced set's action stays reachable by name — Filament does not evict either, and the record-action arm behaves the same way here (the two setters that do evict are `toolbarActions()` and `headerActions()`, both through `removeCachedActions()`).
Worth knowing wherever column sets are swapped at runtime: a stale column action stays mountable by name, and that is inherited behaviour rather than a decision this port took.

### `getRecords()` delegates to the host and fetches nothing

`Table::getRecords()` is one line — `$this->getLiveComponent()->getTableRecords()` — and that is Filament's shape too, not a stub.
The `Table` owns the *declaration* (`records()` stores a closure) while the host owns the *resolution*: it evaluates the closure, normalises the result, and caches it for the request.
Reading `getRecords()` as the fetch point and adding caching or evaluation here would duplicate — and diverge from — the host's copy in [`HasTable::getTableRecords()`](has-table.md).
The direction of the call is the thing to keep: table → host, never the reverse.

`getRecordKey($record)` is the same one line against [`getTableRecordKey()`](has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key), and reads the same way: the *convention* for identifying a record is the host's for **rendering** — the key a row renders, is ticked and mounts an action under — and the table is the seam a template or a column reaches it through.
Reading a key back is not the host's on the query lane, where every read decodes through `RecordKey`, which is why an override answering another key is refused there ([has-table.md](has-table.md#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)).
That is why the contract grew `getTableRecordKey()` rather than the table growing a resolver of its own — a second implementation of the `id` read would be one more place to keep in step with [`InteractsWithRecord::resolveRecordKey()`](../actions/action.md).

### `getRecords()` unwraps the paginator, and `getPaginator()` is the only seam to it

`Table::getRecords()` returns `array` — never a `Paginator` — even when the host's `getTableRecords()` returned one: it unwraps with `$records instanceof Paginator ? $records->getRecords() : $records`.
So the row loop is written once and reads the same shape on a paginated and an unpaginated table, and `records is empty` in the partial means *this page is empty*, which is what the empty state should say.

Filament has no such method because its `getRecords()` **is** the paginator — Laravel's implements `IteratorAggregate`, so `@foreach ($records as $record)` walks the page while `$records->links()` renders the footer off the same object.
Sylament's [`Paginator`](../support/pagination.md) is iterable too, so that shape was available; splitting it is what keeps the template's two readers honest about what they need.
The counts a footer wants are reached through `getPaginator()`, which returns null on an unpaginated table — so the partial's `footerVisible` is the carrier's presence plus a gate on what it counts, and it needs no second `isPaginated()` read.
With a known total the gate is `getTotalRecordCount > 0`, suppressing the chrome for a table with no records at all, and the total is what it tests rather than the page: a page **past the end** is empty and still renders the footer, which is the only way back for a user who paged past the data.
A [simple carrier](../support/pagination.md#one-carrier-expresses-both-shapes-rather-than-a-length-aware-and-a-simple-sibling) has no total to gate on, so the null arm asks what it does know — `count > 0 or not onFirstPage or hasMorePages` — which suppresses exactly one shape, the first page of an empty simple table, and keeps the past-the-end footer for the same reason the total arm does.
Treating a null total as visible on its own would put an empty footer under every empty simple table.

The trap to avoid when extending either: `getRecords()` must not start returning the carrier "when there is one", and `getPaginator()` must not start building one when there is not.
Both would put the same information behind two shapes.

### `aria-sort` is dropped with null, because `false` would render as a string

The header cell's `aria-sort` is computed as `isColumnSortable ? (… 'ascending' : 'descending' : 'none') : null` — the unsortable arm is `null` and not `false`, which reads like an arbitrary choice between two falsy values and is not.
`false` drops an attribute in [`ComponentAttributeBag::toScalarAttributes()`](../support/component-attribute-bag.md) *except* for an `aria-` name, which the bag converts to the **string** `"false"` first — ARIA states being string-valued, where `aria-hidden="false"` genuinely differs from an absent attribute.
So the `false` arm would emit `aria-sort="false"` on every unsortable column: an invalid token for that particular attribute, whose vocabulary is `ascending`/`descending`/`other`/`none`.

The general rule the rest of the partial relies on is unchanged — `null` and `false` both drop — and this is the one family where they differ.
Reach for `null` whenever the intent is *no attribute*, and keep `false` for a genuine boolean attribute (`disabled`) outside the `aria-` namespace.

### The row and cell morph keys disagree on `record` vs `records`

A row's key is `{hostId}.table.**records**.{recordKey}` and a cell's is `{hostId}.table.**record**.{recordKey}.column.{columnName}` — plural on the row, singular on the cell, for the same record and the same host.
It reads like a typo in one of the two.

It is Filament's, copied deliberately: [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) writes `.table.records.` on the `<tr>` (twice) and `.table.record.` on the `<td>`.
The strings are opaque — nothing parses them, they only have to be *stable* and *distinct* — so the asymmetry costs nothing, and "correcting" it would silently break any consumer that matched Filament's shape, which is the one thing porting the value verbatim buys.
Keep both spellings if this template is ever restructured; they are not two spellings of one constant.

### The host binding is required, where the schemas one is optional

`Table::make()` takes a non-optional `InteractsWithTable`, and `BelongsToLiveComponent::$liveComponent` here is a plain non-nullable property with no `getLiveComponentOrNull()` peek — deliberately unlike the same-named schemas trait ([`belongs-to-live-component.md`](../schemas/belongs-to-live-component.md)), whose property is nullable because a `Schema` can be built standalone for a preview render.
A table cannot: it reaches its records only through the host, so an unbound table has nothing to render and the binding is a constructor-time requirement rather than a later optional attachment.
Both packages ship a trait called `BelongsToLiveComponent` with different nullability — check the namespace before assuming which one you have.
This matches Filament, whose `Table\Concerns\BelongsToLivewire` is likewise non-nullable against schemas' nullable one.

### The record anchor spreads its extra attributes first, so the derived pair wins

`extraRecordLinkAttributes()` lets a caller put arbitrary attributes on the row's `<a>`.
The anchor builds its hash as `{...recordLinkAttributes, href: recordUrl, ...(openRecordUrlInNewTab ? {target: '_blank'} : {})}` — the caller's bag **first**, the derived pair after it.
A Twig hash literal is last-wins, so that ordering is what makes `href` and the new-tab `target` unoverridable from the bag.

The order reads backwards and is vendor's outcome exactly.
Blade emits `generate_href_html($recordUrl, …)` *before* `{{ $getExtraRecordLinkAttributeBag($record) }}`, and duplicate attributes in HTML are **first**-wins — so vendor's first-emitted `href` is the one the browser keeps.
Ours reaches the same place through the opposite spelling, because `attrs()` merges a hash where the last writer wins.
Get it the other way round and it is not a cosmetic difference: `getRecordUrl()` pushes its answer through [`sanitize_url()`](../support/helpers.md), and a bag-supplied `href` would be a documented way around that sanitizer.

`target` is the one key that behaves differently from `href`, and deliberately: it is only in the literal when `shouldOpenRecordUrlInNewTab()` is true, so a caller *can* set a `target` of their own on a table that opens no new tab, and cannot contradict one that does.
Everything else in the bag lands untouched, escaped like any other attribute — the values carry no markup risk, which is why the concern the setter's docblock names is the URL rather than the escaping.

### The record title reads the attribute through the accessor and falls back to the label

`getRecordTitle()` tries the `recordTitle()` closure first — the record injected by name and by type — then `recordTitleAttribute()` read through schemas' [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), then the record label.
The attribute arm swallows an `AccessException` and treats a blank or non-scalar value as absent, so a missing property, a null column and an object column all answer the label rather than throwing or rendering an empty title — the body panels' `Resource::getRecordTitle()` already carries, now shared by the table.

The return type is therefore `string`, never null: the label always answers.
That is what makes actions' unconditional table hop ([action.md](../actions/action.md#a-table-bound-action-titles-and-labels-through-its-table)) terminal — a table-bound action never reaches its host's title hook, because the table's answer is always filled.

## Divergences

### The partial emits no header scaffolding and no content wrapper

Filament's `index.blade.php` nests the table five deep — `fi-ta` > `fi-ta-ctn` > `fi-ta-main` > `fi-ta-content-ctn` > `table.fi-ta-table` — and the two header containers inside `fi-ta-main` (`fi-ta-header-ctn`, `fi-ta-header-toolbar`) are emitted **unconditionally**, hidden with `x-cloak` + `x-show` when the table has no heading, description or toolbar action.
Sylament's partial is three deep — `sy sy-ta` > `sy-ta-ctn` > `table.sy-ta-table` or `div.sy-ta-empty-state` — with no `sy-ta-main`, no `sy-ta-content-ctn`, no `sy-ta-header-ctn`, and no `aria-label` on the `<table>`; the two bands that do render — `sy-ta-header` (heading, description and the header actions, landed with the heading rows) and `sy-ta-header-toolbar` (toolbar actions, search, the filters trigger) — render only when something would go in them, where Filament emits both unconditionally behind `x-cloak` + `x-show`.

The wrappers are not markup for their own sake in Filament: they exist so Livewire's DOM morph finds the same nodes before and after a toolbar's visibility flips, which is why they render empty rather than being wrapped in an `@if`.
Live morphs survive the conditional rendering here because the whole partial re-renders server-side.
The `sy-ta-header` band landed with the heading and description rows, `header.css` behind it, and `HeaderActionsPosition::Adaptive` now has its CSS — the adaptive class rides the band when header actions are present.
The `aria-label` half of the old prediction dissolved on inspection: vendor 5.7.6 puts no `aria-label` on its `<table>` at all — the `{{ $pluralModelLabel }}` label sits on the `role="list"` wrapper of the unported grid-content layout, so it waits there, not here.

Provisional, and the reversal is mechanical: each container comes back in the change that lands the first feature it holds, at which point its emptiness is transient rather than permanent.
The **class names** are already reserved by the Filament originals, so nothing about the eventual DOM depends on inventing them later.

The `ms-auto` stopgap on `.sy-ta-search-field` left with the toolbar's second group, and the prediction that replaced it has since been paid off in full.
It expected filters to land in **slot 1** beside the search field, and they did not: Filament's toolbar has exactly two children — a start-aligned actions group (reordering, toolbar actions, the grouping selector) and an end group holding the search field, the filters trigger and the column manager — so filters joined the search field in the *same* child, and the positional `& > :nth-child(2)` gave way to the named `& > .sy-ta-header-toolbar-end { ms-auto }`.
Naming the group rather than counting it is what made [the start group](has-actions.md#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content) a one-rule addition when it landed, with no second reversal anywhere.
The toolbar is pinned in every state — search only, filters only, actions only, all of them, none at all — by [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php).

### The drag control mounts per mode, where vendor's instance outlives every render

Filament stamps `x-sortable` on the `<tbody>` whenever the table `isReorderable()` — the *capability* — and gates only the per-row `x-sortable-item` / `x-sortable-handle` on `isReordering()`, the *mode*.
So its Sortable instance exists on every render of a reorderable table and is simply childless while the mode is off.
Ours stamps [`sylament--support--sortable`](../support/sortable.md) on the `<tbody>` only while `isReordering` is true, so the instance is created when the mode opens and gone when it closes.

The reason is what a Stimulus controller costs across a morph.
UX re-renders the table into the same DOM, so an always-mounted control would have to survive idiomorph rewriting the rows underneath it — and SortableJS binds listeners to the child elements it was given.
It would then need either a `data-live-ignore` island (which would freeze the rows the server is trying to update) or a re-initialisation on every morph, and the reward for either is an instance that can do nothing, because there are no `[data-sortable-item]` children to drag until the mode is on.
Mounting per mode makes `connect()` and `disconnect()` line up exactly with the window in which dragging is legal, and it is the same reason the poll attribute is dropped rather than ignored while reordering ([the timer is ours](can-defer-loading.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers)).

Two things stay Filament's.
The animation duration is [support's shared `HasReorderAnimationDuration`](../../../packages/support/src/Components/Concerns/HasReorderAnimationDuration.php), composed by the table's `CanReorderRecords` exactly as Filament's composes its own `Support\Concerns\HasReorderAnimationDuration` — the same trait in the same base package, reused rather than re-declared, and `Repeater` was its first consumer.
And the handle is a per-row `[data-sortable-handle]` element rather than a whole draggable row, so a click on a cell still follows the row's link while the mode is open… except that it does not: the [cell wrapper](#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) falls back to a `div` while reordering, which is vendor's `$isColumnClickDisabled` carrying `|| $isReordering`.

### Every rule in `table.css` is scoped under the `.sy-ta` root, one class above vendor's

Filament ships no `.fi-ta` rule at all — `.fi-ta-ctn`, `.fi-ta-table`, `.fi-ta-header-cell`, `.fi-ta-cell`, `.fi-ta-row`, `.fi-ta-actions` and `.fi-ta-empty-state` are each a top-level selector in a file of its own.
[`table.css`](../../../packages/tables/assets/src/styles/components/table.css) wraps all seven in one `.sy-ta { … }` block.
That is what lets the seven collapse into a single stylesheet — six of them absorbed into the file whose basename already matched — and it is what matches the `div.sy.sy-ta` root the partial always emits: deliberate scoping, not a nesting habit.

It costs exactly one class of specificity on every rule the file ships, and the shift is uniform: `.fi-ta-cell` at (0,1,0) becomes `.sy-ta .sy-ta-cell` at (0,2,0), `.fi-ta-ctn` becomes `.sy-ta > .sy-ta-ctn`, and each nested arm moves with its parent — `.fi-ta-cell .fi-ta-col` at (0,2,0) becomes `.sy-ta .sy-ta-cell .sy-ta-col` at (0,3,0).
Nothing *inside* the file changes, because every rule gains the same class; what changes is what an outside rule has to beat.

So a consuming app overriding a table rule with a single class — `.sy-ta-cell { … }`, (0,1,0) — loses to the shipped rule wherever its stylesheet sits in source order, where the same override against Filament would win on order alone.
It has to match or beat (0,2,0): re-scope it under `.sy-ta`, or double the class (`.sy-ta-cell.sy-ta-cell`).

This is `table.css` alone.
The package's other three stylesheets — `header-toolbar.css`, `filters.css` and `text-column.css` — declare every one of their roots at top level exactly as vendor does, so a consumer overriding a toolbar, a filter or a column rule pays nothing extra and only the table chrome carries the surcharge.
The other failure mode the extra class could introduce — a rule that silently never applies because the scope is absent — is closed at the render: the partial emits `sy sy-ta` unconditionally, and the Integration [`TableTest`](../../../packages/tables/tests/Integration/TableTest.php) selects `div.sy.sy-ta > div.sy-ta-ctn > table.sy-ta-table` with the child combinator, so a flattened or renamed root fails there rather than as an unstyled table.

### The morph key is an `id` attribute, not `wire:key`

Filament stamps `wire:key="{{ $this->getId() }}.table.records.{{ $recordKey }}"` on every `<tr>` and `wire:key="....table.record.{{ $recordKey }}.column.{{ $column->getName() }}"` on every `<td>`, so Livewire's morph pairs a row with the same record across a re-render instead of matching by position.
Sylament stamps the *same two strings* as plain `id` attributes.

The value is identical down to the separators, and the [`{{ $this->getId() }}` prefix](has-table.md#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) has a port of its own; only the attribute differs, because the two morph libraries key on different things.
Livewire's morphdom reads `wire:key`; UX LiveComponent uses **idiomorph**, which pairs nodes by `id` and has no per-framework key attribute to read.
Emitting `wire:key` here would be a string no library looks at, and idiomorph would fall back to positional matching — the exact failure the key exists to prevent, where deleting row 1 morphs row 2's DOM state onto row 3's record.

Permanent, and it is the same substitution any Livewire → UX template port makes.
The `id` is a real document id, so it is also addressable from CSS and tests, which `wire:key` never was — the Integration `TableTest` reads the rows back by `tr[id="…"]` for exactly that reason.
