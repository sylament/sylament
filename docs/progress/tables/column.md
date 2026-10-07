---
description: 'The base of the column catalog: a `ViewComponent` named after the record path it reads, bound to a `Table`, and handed one record at a time. (draft)'
ports:
  - vendor/filament/tables/src/Columns/Column.php
  - vendor/filament/tables/src/Columns/Concerns/HasName.php
  - vendor/filament/tables/src/Columns/Concerns/HasLabel.php
  - vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php
  - vendor/filament/tables/src/Columns/Concerns/HasRecord.php
  - vendor/filament/support/src/Concerns/HasCellState.php
owns:
  - packages/support/src/Components/Concerns/CanGrow.php
  - packages/support/src/Components/Concerns/CanWrapHeader.php
  - packages/support/src/Components/Concerns/HasWidth.php
  - packages/tables/src/Columns/Column.php
  - packages/tables/src/Columns/Concerns/BelongsToTable.php
  - packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php
  - packages/tables/src/Columns/Concerns/CanBeDisabled.php
  - packages/tables/src/Columns/Concerns/CanBeHidden.php
  - packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php
  - packages/tables/src/Columns/Concerns/CanBeInline.php
  - packages/tables/src/Columns/Concerns/CanBeSearchable.php
  - packages/tables/src/Columns/Concerns/CanBeSortable.php
  - packages/tables/src/Columns/Concerns/CanBeSummarized.php
  - packages/tables/src/Columns/Concerns/CanBeToggled.php
  - packages/tables/src/Columns/Concerns/CanCallAction.php
  - packages/tables/src/Columns/Concerns/CanOpenUrl.php
  - packages/tables/src/Columns/Concerns/HasCellState.php
  - packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php
  - packages/tables/src/Columns/Concerns/HasIcon.php
  - packages/tables/src/Columns/Concerns/HasLabel.php
  - packages/tables/src/Columns/Concerns/HasName.php
  - packages/tables/src/Columns/Concerns/HasRecord.php
  - packages/tables/src/Columns/Concerns/HasRowLoopObject.php
  - packages/tables/src/Columns/Concerns/HasTooltip.php
  - packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php
  - packages/tables/src/Columns/Contracts/
---
# Column (tables)

[`Sylament\Tables\Columns\Column`](../../../packages/tables/src/Columns/Column.php) — the base of the column catalog: a [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) named after the record path it reads, bound to a [`Table`](table.md), and handed one record at a time.
It ports Filament's [`Filament\Tables\Columns\Column`](../../../vendor/filament/tables/src/Columns/Column.php) together with the concern traits it composes — twenty-eight today, fifteen of them landed by the column catalog.
Twenty live in the column tier ([`BelongsToTable`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php), [`CanAggregateRelatedRecords`](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php), [`CanBeDisabled`](../../../packages/tables/src/Columns/Concerns/CanBeDisabled.php), [`CanBeHidden`](../../../packages/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeHiddenResponsively`](../../../packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php), [`CanBeInline`](../../../packages/tables/src/Columns/Concerns/CanBeInline.php), [`CanBeSearchable`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php), [`CanBeSortable`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeToggled`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php), [`CanCallAction`](../../../packages/tables/src/Columns/Concerns/CanCallAction.php), [`CanOpenUrl`](../../../packages/tables/src/Columns/Concerns/CanOpenUrl.php), [`HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php), [`HasExtraCellAttributes`](../../../packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php), [`HasExtraHeaderAttributes`](../../../packages/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php), [`HasLabel`](../../../packages/tables/src/Columns/Concerns/HasLabel.php), [`HasName`](../../../packages/tables/src/Columns/Concerns/HasName.php), [`HasRecord`](../../../packages/tables/src/Columns/Concerns/HasRecord.php), [`HasRowLoopObject`](../../../packages/tables/src/Columns/Concerns/HasRowLoopObject.php), [`HasTooltip`](../../../packages/tables/src/Columns/Concerns/HasTooltip.php), [`InteractsWithTableQuery`](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php)), and seven are support's, composed by a bare `use` line ([`CanGrow`](../../../packages/support/src/Components/Concerns/CanGrow.php), [`CanWrapHeader`](../../../packages/support/src/Components/Concerns/CanWrapHeader.php), [`HasAlignment`](../../../packages/support/src/Components/Concerns/HasAlignment.php), [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php), [`HasPlaceholder`](../../../packages/support/src/Components/Concerns/HasPlaceholder.php), [`HasVerticalAlignment`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php), [`HasWidth`](../../../packages/support/src/Components/Concerns/HasWidth.php)), beside schemas' [`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php); thirty in Filament — twenty from its `Columns\Concerns\` and ten from `Support\Concerns\`, of which `HasCellState` and `CanAggregateRelatedModels` [moved package](#hascellstate-is-relocated-into-the-column-tier) (the second also [renamed](../renames.md#renames)) while `HasAlignment` stayed and `HasTooltip` [split in two](#the-tables-tier-tooltip-trio-sits-beside-supports-one-tooltip-trait).
Both of Filament's tiers count toward that same thirty, which is why the support-hosted group belongs in the numerator here as well.
All of them are folded into the surface table below rather than earning rows upstairs, per [one row per file](../README.md#one-row-per-file-one-file-per-thing) — `InteractsWithTableQuery` included, since its aggregate and eager-loading half is [built](#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) — and the `Columns\Concerns\` traits not ported at all keep a row there too, bar the five [`TextColumn`](text-column.md) composes — `CanFormatState`, `HasColor`, `HasDescription`, `HasIcon` and `HasIconColor` — which fold into [`text-column.md`](text-column.md) instead, because that is the class composing them.
The rows below group those files by feature, which is what the per-file rows upstairs point at.

The column is the read side of the record, and on the Doctrine lane it is also the piece that knows how to ask for it.
It resolves nothing itself: the host fetches records ([`has-table.md`](has-table.md)), the table keys the columns ([`table.md`](table.md)), and a column asked for its state walks its dotted name over one record through schemas' [`PropertyAccessor`](../schemas/property-accessor.md) — the same accessor the form fields write through, reached by composing schemas' [`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php) — fanning out over any collection it crosses.
Given the table's [`QueryBuilder`](table.md#every-read-clones-a-memoised-base-query) it also applies its own ordering, contributes its own `LIKE` arm and fetch-joins what it displays, through `applySort()`, `applySearchConstraint()` and `applyEagerLoading()` — the column declares what sorting, searching and reading *by it* mean, and the table decides when to ask.
A name crossing an association needs nothing declared in `query()`: the [relation arm](table.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) resolves it against Doctrine's mapping.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(?string $name = null)` + the blank-name guard](../../../packages/tables/src/Columns/Column.php) | [`Column::make()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](../renames.md#make-arguments-move-from-constructors-to-setters) |
| [`getDefaultName()`](../../../packages/tables/src/Columns/Column.php) | [`Column::getDefaultName()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | — |
| [`name()` / `getName()`](../../../packages/tables/src/Columns/Concerns/HasName.php) | [`Concerns\HasName`](../../../vendor/filament/tables/src/Columns/Concerns/HasName.php) | done | — | — |
| [`label()` / `getLabel()` — explicit value wins, else the name is humanised](../../../packages/tables/src/Columns/Concerns/HasLabel.php) | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | done | — | [semantics](#the-default-label-reads-the-second-to-last-dotted-segment) |
| [`record()` / `getRecord()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | in progress | — | [diverges](#the-record-union-is-plain-object-where-filaments-is-model) · [detail](#getrecord-has-no-layout-fallback) |
| [`table()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php) | in progress | — | [detail](#gettable-refuses-without-filaments-three-source-chain) |
| [`state()` / `getStateUsing()` / `getState()` — cached per record key](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#hascellstate-is-relocated-into-the-column-tier) · [diverges](#the-state-cache-keys-off-the-bound-record-key) · [semantics](#state-is-getstateusing-under-another-name) |
| [`getStateFromRecord()` — object graph or array, by dotted path, fanning out over a collection](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`HasCellState::getStateFromRecord()`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#the-object-read-is-lenient-where-the-accessor-is-strict) · [diverges](#a-to-one-chain-keeps-the-raw-read-where-filaments-relationship-arm-drops-blanks) · [diverges](#the-list-shape-is-metadata-derived-where-filaments-is-cached-off-the-first-row) |
| [`$evaluationIdentifier = 'column'` + the `$record` / `$rowLoop` / `$state` / `$table` / `$liveComponent` name injections](../../../packages/tables/src/Columns/Column.php) | [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | — | [diverges](#the-by-type-record-injection-is-not-registered) · [semantics](#the-row-loop-is-a-blade-shaped-object-built-from-twigs-array) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | blocked | i18n ([table.md](table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)) | — |
| [`default()` / `getDefaultState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [semantics](#the-default-and-the-split-run-inside-the-per-record-cache-in-that-order) |
| [`separator()` — split a string into a list](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [semantics](#the-default-and-the-split-run-inside-the-per-record-cache-in-that-order) |
| [`distinctList()` / `isDistinctList()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [semantics](#distinctlist-dedupes-entity-values-by-value-not-by-identity) |
| [`placeholder()` / `getPlaceholder()` — the empty-cell copy](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [has-placeholder.md](../support/has-placeholder.md) |
| [`clearCachedState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#the-state-cache-keys-off-the-bound-record-key) |
| [`recordKey()` / `getRecordKey()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | done | — | [diverges](#the-record-key-setter-admits-an-int) |
| [`rowLoop()` / `getRowLoop()` — the `$rowLoop` injection](../../../packages/tables/src/Columns/Concerns/HasRowLoopObject.php) | [`Concerns\HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | done | — | [semantics](#the-row-loop-is-a-blade-shaped-object-built-from-twigs-array) |
| Relationship traversal — `hasRelationship()`, `getRelationshipResults()`, `inverseRelationship()`, the seven name caches, `applyRelationshipAggregates()`, `applyEagerLoading()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php), [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | in progress | — | [detail](#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) — `inverseRelationship()` is settled as **not ported** there, because the summarizer tier is its only consumer and refuses the case it exists for; what holds the row is `hasNestedMorphToRelationship()` |
| [`applyEagerLoading()` — fetch-join the to-one chains a visible name reads through](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | [`InteractsWithTableQuery::applyEagerLoading()`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | done | — | three lines: the body is [`Query\RelationJoiner::fetchJoinPath()`](../../../packages/tables/src/Query/RelationJoiner.php), which a [`Group`](grouping.md) fetch-joins its own column through, so the two share an implementation rather than a copy. [diverges](#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality) · [has-table.md](has-table.md#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) |
| [The aggregate declarations — `avg()`, `counts()`, `exists()`, `max()`, `min()`, `sum()` and their getters](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php) | [`Support\Concerns\CanAggregateRelatedModels`](../../../vendor/filament/support/src/Concerns/CanAggregateRelatedModels.php) | done | — | [rename](../renames.md#renames) · [diverges](#an-aggregate-column-names-itself-freely-where-filaments-must-match-laravels-alias) · [diverges](#the-illuminate-expression-arm-is-dropped-from-the-aggregated-column) · [diverges](#an-aggregate-declaration-that-claims-no-cell-computes-nothing) |
| [`getCellAggregateDeclaration()` — which declaration this cell reads](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php) | — (Sylament-only; Filament reads the value off the model as an attribute) | done | — | [diverges](#an-aggregate-column-names-itself-freely-where-filaments-must-match-laravels-alias) · [table.md](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) |
| Visibility — [`hidden()` / `visible()` / `hiddenOn()` / `visibleOn()` / `isHidden()` / `isVisible()`](../../../packages/tables/src/Columns/Concerns/CanBeHidden.php) and [`toggleable()` / `toggledHiddenByDefault()` / `isToggleable()` / `isToggledHidden()`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php) | [`CanBeHidden`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeToggled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | done | — | [diverges](#istoggleables-blank-label-group-arm-is-dropped-with-columngroup) |
| [`hiddenFrom()` / `visibleFrom()` — the responsive pair](../../../packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | [`Concerns\CanBeHiddenResponsively`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | done | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) |
| [`sortable()` / `isSortable()`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php) | [`Concerns\CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php) | done | — | — |
| [`searchable()` / `isSearchable()` / `isGloballySearchable()` / `isIndividuallySearchable()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`Concerns\CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) |
| [`getSortColumns()` / `getSearchColumns()`, `forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#the-list-getters-take-no-record-and-there-is-no-getdefaultcolumns-pair) · [semantics](#an-undeclared-sortable-has-no-sort-columns-where-an-undeclared-searchable-has-its-name) |
| [`splitIndividualSearchTerms()` / `shouldSplitIndividualSearchTerms()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | The tri-state governs how a column's **own** search input splits its term: `null` defers to the table's `splitSearchTerms()`, a bool overrides it. Read by [`applyColumnSearchesToTableQuery()`](has-table.md#search-is-the-global-term-and-one-term-per-column) alone — the global term keeps the table-level flag |
| [`applySort()`, `applySearchConstraint()` — the column's query application](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | done | — | [diverges](#sort-columns-apply-in-declared-order-where-filament-reverses-them) · [diverges](#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) · [table.md](table.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery) · [table.md](table.md#a-relation-search-is-a-correlated-exists-at-every-cardinality) |
| [`action()` / `getAction()` / `getActionKind()`](../../../packages/tables/src/Columns/Concerns/CanCallAction.php) | [`Concerns\CanCallAction`](../../../vendor/filament/tables/src/Columns/Concerns/CanCallAction.php) | done | — | [table.md](table.md#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) · [has-columns.md](has-columns.md) |
| [`url()` / `openUrlInNewTab()` / `getUrl()` / `hasStateBasedUrls()` / `shouldOpenUrlInNewTab()`](../../../packages/tables/src/Columns/Concerns/CanOpenUrl.php) | [`Concerns\CanOpenUrl`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) | done | — | [diverges](#a-column-url-is-sanitized-where-filament-only-warns) · [semantics](#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom) |
| [`disabled()` / `isDisabled()` / `isEnabled()` / `disabledClick()` / `isClickDisabled()`](../../../packages/tables/src/Columns/Concerns/CanBeDisabled.php) | [`Concerns\CanBeDisabled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | done | — | `disableClick()`, the deprecated alias, is skipped — [renames.md](../renames.md) |
| Editing in place — `updateStateUsing()` / `beforeStateUpdated()` / `afterStateUpdated()`, `constraint()` / `constraints()` / `validationMessages()` | [`CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php), [`CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php) | done | — | [diverges](#the-editable-write-is-not-authorization-checked) · [has-columns.md](has-columns.md#surface) · [diverges](has-columns.md#the-pivot-column-arm-has-nothing-to-write-through) |
| [`alignment()` / `getAlignment()` / `getAlignmentClass()`](../../../packages/tables/src/Columns/Column.php) | [`HasAlignment`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | [has-alignment.md](../support/has-alignment.md) · [diverges](text-column.md#alignment-reaches-a-badge-row-through-the-list-not-through-text-align) |
| [`verticalAlignment()` / `getVerticalAlignment()` / `getVerticalAlignmentClass()`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`Support\Concerns\HasVerticalAlignment`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | [has-vertical-alignment.md](../support/has-vertical-alignment.md) |
| [`width()` / `getWidth()`](../../../packages/support/src/Components/Concerns/HasWidth.php) | [`Support\Concerns\HasWidth`](../../../vendor/filament/support/src/Concerns/HasWidth.php) | done | — | — |
| [`grow()` / `canGrow()`](../../../packages/support/src/Components/Concerns/CanGrow.php) | [`Support\Concerns\CanGrow`](../../../vendor/filament/support/src/Concerns/CanGrow.php) | done | — | the `<th>` asks `canGrow(false)`, so an undeclared column does not grow |
| [`wrapHeader()` / `canHeaderWrap()`](../../../packages/support/src/Components/Concerns/CanWrapHeader.php) | [`Support\Concerns\CanWrapHeader`](../../../vendor/filament/support/src/Concerns/CanWrapHeader.php) | done | — | — |
| [`inline()` / `isInline()`](../../../packages/tables/src/Columns/Concerns/CanBeInline.php) | [`Concerns\CanBeInline`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeInline.php) | done | — | — |
| [`tooltip()` / `headerTooltip()` / `emptyTooltip()` and their getters](../../../packages/tables/src/Columns/Concerns/HasTooltip.php) | [`Concerns\HasTooltip`](../../../vendor/filament/tables/src/Columns/Concerns/HasTooltip.php) | done | — | [diverges](#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) · [semantics](#the-tables-tier-tooltip-trio-sits-beside-supports-one-tooltip-trait) |
| [`extraAttributes()` / `extraCellAttributes()` / `extraHeaderAttributes()` and their getters](../../../packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php) | [`Support\Concerns\HasExtraAttributes`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php), [`HasExtraCellAttributes`](../../../vendor/filament/tables/src/Columns/Concerns/HasExtraCellAttributes.php), [`HasExtraHeaderAttributes`](../../../vendor/filament/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php) | done | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) · [image-column.md](image-column.md#extra-img-attribute-registrations-merge-defaults-first) |
| [`getCellClassName()` / `getHeaderCellClassName()` — the per-column hook classes](../../../packages/tables/src/Columns/Column.php) | [`index.blade.php`'s `str($columnName)->camel()->kebab()`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [semantics](#the-hook-class-kebabs-each-dotted-segment-on-its-own) |
| Cell rendering — `getCellAttributeHtml()`, `getCachedCellAttributeHtml()` and the five `hasDynamic*()` cache guards | [`Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | skipped | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) |
| Column grouping and layout — `ColumnGroup`, `Columns\Layout\`, `renderInLayout()`, `BelongsToGroup`, `BelongsToLayout`, `CanSpanColumns`, `HasSpace` | [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php), [`Columns/Layout`](../../../vendor/filament/tables/src/Columns/Layout) | todo | — | — |
| [`summarize()` / `getSummarizer()` / `getSummarizers()` / `hasSummary()`](../../../packages/tables/src/Columns/Concerns/CanBeSummarized.php) | [`Columns\Concerns\CanBeSummarized`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | done | — | [summarizer.md](summarizer.md) — the trait ships whole, and a column reading a relationship aggregate [refuses to be summarized](summarizer.md#summarizing-an-aggregate-column-is-refused-where-filament-produces-a-wrong-number) |
| [`ColorColumn`](../../../packages/tables/src/Columns/ColorColumn.php), [`IconColumn`](../../../packages/tables/src/Columns/IconColumn.php), [`ImageColumn`](../../../packages/tables/src/Columns/ImageColumn.php), [`ViewColumn`](../../../packages/tables/src/Columns/ViewColumn.php) | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | done | — | [color-column.md](color-column.md) · [icon-column.md](icon-column.md) · [image-column.md](image-column.md) · [view-column.md](view-column.md) |
| `BadgeColumn`, `BooleanColumn`, `TagsColumn` — the deprecated one-line subclasses | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | skipped | — | The spellings their own deprecation notices name all ship; the project has never ported a deprecated Filament surface ([renames.md](../renames.md)) |
| [`CheckboxColumn`](../../../packages/tables/src/Columns/CheckboxColumn.php), [`SelectColumn`](../../../packages/tables/src/Columns/SelectColumn.php), [`TextInputColumn`](../../../packages/tables/src/Columns/TextInputColumn.php), [`ToggleColumn`](../../../packages/tables/src/Columns/ToggleColumn.php) — the four editable columns | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | done | — | [checkbox-column.md](checkbox-column.md) · [select-column.md](select-column.md) · [text-input-column.md](text-input-column.md) · [toggle-column.md](toggle-column.md) · [has-columns.md](has-columns.md) |

Sixteen of the forty-two **done**-or-partial rows are covered by [`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php) — the blank-name refusal, the three label derivations (`title` → *Title*, `releasedAt` → *Released at*, `artist.name` → *Artist*), an explicit label winning and a label closure receiving the column, the object read by property, the dotted read through a nested object, the array read, all three unreadable arms returning null, `state()` overriding the record read while the `$record` injection still resolves, the `$state` / `$liveComponent` / `$table` name injections and the by-type column injection under a different parameter name, the table binding reaching the host through `getLiveComponent()`, an integer record key stored as a string and `null` taken back, a second read of one key served from the cache — including a cached `null`, which is why the lookup is `array_key_exists()` and not `isset()` — a new key re-reading, an unkeyed column reading fresh every time, `clearCachedState()` restoring the fresh read, the unmounted column naming itself in its refusal, the hook classes over a plain name, a camelCase one and a dotted one, and a `$rowLoop` closure receiving the translated object.

The fourteen rows the column catalog landed are one Unit file per concern under [`tests/Unit/Columns/Concerns/`](../../../packages/tables/tests/Unit/Columns/Concerns) — each default, each setter's literal and closure arms, `getUrl()`'s two arities against a state-based and a plain URL and the sanitizer refusing both, the string action arm calling its host method with an object record, an array one and none at all, the attribute stacks' replace-versus-merge and their [defaults-first](image-column.md#extra-img-attribute-registrations-merge-defaults-first) collision order, and the loop translation's countable and non-countable shapes — plus, for each one's effect on the markup, the header and cell assembly cases in the Integration [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php): the whole `<th>` class list in one string, the growable marker yielding to a declared width, a `Markup` header tooltip flattened, the sort control swapping to a `span[role=button]` naming all three events, the whole `<td>` class list, and the wrapper matrix — a column URL beating the record URL and marking itself, a column action suppressing the record URL, a column URL beating a record action, `disabledClick()` and a state-based URL each collapsing to a `div`, the new-tab flag following whichever arm supplied the `href`, the record link attributes riding the record fallback alone, and the two column-action wirings.
The four refusal arms of `callTableColumnAction()` are [`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php)'s.

Ten more cases in that file are the collection walk, and they are written so the **shape** rules cannot pass by accident: a `tags.name` list, a *single*-element one still a list, blanks dropped, `distinctList()` deduping only once set, a path that collects nothing answering null, a to-one chain read raw with a blank leaf intact, a dotless collection name left to the plain record read, a path ending *at* the collection flattening into its items, a row whose to-one hop is null still reading as a list — the case [metadata multiplicity](#the-list-shape-is-metadata-derived-where-filaments-is-cached-off-the-first-row) exists for — and the records lane falling back to the values, with no mapping to ask.

The two browsing rows are covered in the same file.
`sortable()` is asserted on all four arms — the default false, the bare call, an explicit `false`, a closure, and the column-list form still reading sortable.
`searchable()` splits into four cases: the three unset defaults together (searchable, globally, individually all false); the bare call defaulting to **globally** searchable and not individually; the string and array forms staying searchable; and the `isIndividual: true, isGlobal: false` pair inverting both.
A fifth case is the one that would fail against Filament's own implementation — a closure condition returning `false` leaving the column unsearchable, and one returning `true` leaving it globally searchable — which is the [vendor defect](#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) this port fixes.

`alignment()` is the one row covered outside this package: the setter, the string-to-enum resolution and the `sy-align-*` fragment are support's, pinned in [has-alignment.md](../support/has-alignment.md), and what tables owes is the class reaching the cell — asserted by the Integration [`TextColumnTest`](../../../packages/tables/tests/Integration/Columns/TextColumnTest.php), which is also where [how it reaches a badge row](text-column.md#alignment-reaches-a-badge-row-through-the-list-not-through-text-align) is recorded.

The remaining five rows split two ways.
Three — the list getters, the query application and the eager-loading arm — are [`InteractsWithTableQueryTest`](../../../packages/tables/tests/Unit/Columns/Concerns/InteractsWithTableQueryTest.php)'s, asserted against DQL and bound parameters rather than fetched rows: SQLite matches `LIKE` case-insensitively for ASCII, so an execution-level assertion would pass whether or not the `LOWER()` wrapping the case-insensitive arm exists.
It pins the default sort column ordering in the direction it was handed, a declared list ordering [front to back](#sort-columns-apply-in-declared-order-where-filament-reverses-them), a `sortable(query:)` closure receiving the builder and the direction and being the only thing applied, the default search column composing into the caller's `Orx` with its `%term%` parameter bound (and a two-name list composing both arms under distinct parameter names), a `searchable(query:)` closure's returned expression *and* returned DQL string both landing in that `Orx` while a closure returning nothing leaves it empty and one returning the **builder** it constrained leaves it empty too — with that constraint on the query, which is the [skip](#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) that keeps a `Stringable` builder out of `Orx::add()` — all three of Filament's injections proved by name (`$query` by identity against the builder passed in, `$search` and its `$searchQuery` alias by value), `forceSearchCaseInsensitive()` lowering both sides of one column's comparison, a dotted name resolving against a joined alias — asserted through `getSQL()`, so the alias-headed `ORDER BY` is proved to *parse* and not merely to read right — and both getters' declared-and-defaulted arms.
Five cases carry the relation arm through the same seams: an alias-headed name still winning verbatim on both the sort and the search side, an unaliased one being *joined* rather than refused while an unknown head still throws, a to-one column fetch-joining every alias it crosses, a column that already sorted by the same path reusing that join and selecting its alias once, and the five shapes `applyEagerLoading()` leaves alone — dotless, to-many, alias-headed, unmapped and association-terminal.

The last two rows — the aggregate declarations and the cell they bind to — are [`CanAggregateRelatedRecordsTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanAggregateRelatedRecordsTest.php)'s, fifteen cases over resolution rather than SQL: each kind mapping to its function (`exists` the bool-cast `COUNT`), a closure declaration evaluating through the getters, the string form binding whatever the column is called, the array form picking the entry whose derived alias *is* the column name and resolving to null when none does, a camelCase relationship deriving through snake case, an array value that is a `Closure` carried as the scope, and the four multi-claim shapes — a string form beside an array naming another cell resolving, two array forms naming no cell staying [inert](#an-aggregate-declaration-that-claims-no-cell-computes-nothing), and both genuinely ambiguous pairs refusing.

## Details

### `getTable()` refuses without Filament's three-source chain

Filament's `Column::getTable()` chains three sources — the column's own table, its group's, its layout's — and throws `LogicException("The column [x] is not mounted to a table.")` when all three are absent.
[`BelongsToTable::getTable()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) throws that same message, naming the same column, because a `date()` or `url()` closure reaching the table on an unmounted column is a real path and PHP's *"must not be accessed before initialization"* `Error` names nothing useful.

What is still missing is the chain itself: column groups and column layouts are unported (their row is above), so there is nothing to fall back *to* and the getter reads one source.
The row flips when `BelongsToGroup` / `BelongsToLayout` land and the two remaining links are written.

### `getRecord()` has no layout fallback

Filament's `getRecord()` falls back to `$this->getLayout()?->getRecord()` when the column carries none of its own, so a column nested in a `Columns\Layout\` component reads the row the layout was handed.
That arm belongs to the unported column-layout tier (its row is above), so the getter returns the column's own record or null.
It lands with `BelongsToLayout` — in one piece with the `getTable()` chain [waiting on the same tier](#gettable-refuses-without-filaments-three-source-chain), rather than as a lone `?->` bolted on ahead of the thing it falls back to.

### Relationship traversal ships as a value walk, a side store and one kept member

Two thirds of Filament's `HasCellState` is Eloquent relationship machinery: `hasRelationship()`, `getRelationship()`, `getRelationshipResults()`, `hasMultipleRelationship()`, `getAttributeName()`, `getFullAttributeName()`, `getInverseRelationshipName()`, `hasNestedMorphToRelationship()` and their seven memo properties, and its `InteractsWithTableQuery` adds two consumers — `applyRelationshipAggregates()`, which reads six aggregate declarations onto `withAvg`/`withCount`/`withExists`/`withMax`/`withMin`/`withSum`, and `applyEagerLoading()`, which is `$query->with([$relationshipName])`.
They exist to answer *which prefix of a dotted name is a relationship hop and which is an attribute*, so a to-many hop can be flattened into a list, counted, or preloaded.

The relation arm answers all three questions and keeps one of the member names.
What changed is where the answer comes from.
Filament's machinery derives the split by **asking an instantiated relation object** — `$record->isRelation($segment)` and the `Relation` it returns — and memoises each answer on the column, which is what the seven caches hold.
Doctrine answers off the class instead: the cell read simply walks values and fans out at whatever turns out to be a `Collection` (no split needed at all), and the two query seams split the name against `ClassMetadata` through [`RelationPathResolver`](table.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused), which needs no record and so needs no per-record cache.
So `hasRelationship()`, `getRelationshipResults()`, `hasMultipleRelationship()`, the two attribute-name getters and the seven caches have no counterpart to port: each is a step of a derivation this side does not perform.
`applyRelationshipAggregates()` goes the same way for a different reason — the aggregate value never reaches the page query here, so there is nothing to apply *to* it ([why](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)) — while the six declaration setters it reads keep their names, their required second argument and their union types on [`CanAggregateRelatedRecords`](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php), bar the [`Expression` arm](#the-illuminate-expression-arm-is-dropped-from-the-aggregated-column).
`applyEagerLoading()` keeps its name and its position and changes its mechanism: a fetch join on the page query rather than a second `with()` query, and [to-one only](#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality).

One member is genuinely still out, which is what holds the row at **in progress**: `hasNestedMorphToRelationship()` is morph machinery, and morphs are out of the relation layer on both the schemas and the tables side, refused rather than guessed.
It blocks nothing — a column with no polymorphic association reads, sorts, searches, counts and fetch-joins today.

The other, `inverseRelationship()`, said *"it lands with that tier or not at all"* until the tier landed, and the answer is **not at all**.
It names the reverse accessor a summarizer walks back through, and the [summarizer tier](summarizer.md) is its only consumer in either codebase: Filament re-roots a summary query on the related record through that accessor, purely so a to-many relation's rows are weighted once.
The port [refuses the to-many case outright](summarizer.md#the-batch-keeps-a-to-one-relation-column-and-refuses-a-to-many-one) and batches the to-one one off the alias the eager load already joined, so there is no query left to re-root and nothing for the setter to name — and Filament's own default for it is an English-inflection guess reached through a magic dynamic call, which is the shape the port [does not carry](../renames.md#renames) one tier up either.

## Semantics

### The row loop is a Blade-shaped object built from Twig's array

`rowLoop()` takes what the cell loop can hand it — Twig's `loop` **array** for the record being rendered, reached as `loop.parent.loop` because the column loop is nested inside the record one — and stores the `stdClass` Filament's [`HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) declares, so a migrating `fn (stdClass $rowLoop) => $rowLoop->iteration` works unchanged.
The two vocabularies do not line up: Twig's `index` counts from 1 and its `index0` from 0, where Blade's `iteration` counts from 1 and its `index` from 0 — so `index` means opposite things on either side, and reading Twig's array through Blade's names would be off by one in silence.
The translation is `iteration` ← `index`, `index` ← `index0`, `remaining` ← `revindex0`, `count` ← `length`, `first`/`last` straight across, `even` from `iteration % 2` with `odd` its inverse, `depth` fixed at `1`.

Two entries carry nothing rather than guessing.
Twig fills `length`, `revindex0` and `last` only for an array or a `Countable`, so on any other iterable they stay `null` — every table lane yields one of the two, so in practice they are filled.
And Twig's own `parent` entry holds the **whole parent template context**, not a parent loop, so it is never copied: `parent` is always `null`.
An `stdClass` handed in passes through untranslated, and `null` clears.

### `getUrl()` answers by argument count, so a closure's parameter name changes the DOM

`getUrl()` switches on `func_num_args()`: called with no argument it answers only a URL that does *not* read the state, and called with one — even `null` — only a URL that does.
The two callers are different places in the markup.
The cell wrapper calls it bare, so a state-based URL leaves the wrapper a plain `div`; each column partial calls it with one item's state, so the links are drawn per item inside the cell instead.

Which arm a column is on is decided by **reflection**, not by configuration: `hasStateBasedUrls()` asks whether the closure declares a parameter named `$state`.
So renaming that parameter — `fn ($state) => …` to `fn ($value) => …`, which any refactoring tool will offer — moves the whole cell from per-item anchors to one wrapping anchor, with no error anywhere.
Filament behaves identically; the trap is inherited, not introduced.

### The tables-tier tooltip trio sits beside support's one-tooltip trait

`Sylament\Support\Components\Concerns\HasTooltip` ships one tooltip with no state injection; the column tier's [`HasTooltip`](../../../packages/tables/src/Columns/Concerns/HasTooltip.php) ships three — the per-item one, the header's and the blank-state one — and injects `state` into the first.
That is Filament's own split (`Support\Concerns\HasTooltip` beside `Tables\Columns\Concerns\HasTooltip`), kept rather than collapsed, so widening support's would diverge from vendor for every non-column consumer.
`Column` composes the column-tier one only.
Read the import, never the bare `use HasTooltip;` — the two names are one letter apart in a class body.

### The hook class kebabs each dotted segment on its own

`getCellClassName()` and `getHeaderCellClassName()` split the column name on dots, camel-kebab each segment and rejoin with the dots intact, so `author.name` renders `sy-ta-cell-author.name` and `authorName` renders `sy-ta-cell-author-name` — two distinct hooks.
Running the whole name through one `u()->camel()->kebab()` would eat the dot and collapse the two onto the same class; Laravel's `Str::camel()`, which Filament calls, leaves dots alone, so segment-wise is what reproduces vendor rather than what departs from it.

The hook class is derived from a user-supplied name while the `<th>` also carries state classes in the same namespace, so the two can collide: a column literally named `sorted` emits `sy-ta-header-cell-sorted`, which is *also* the class the header stamps on the column the table is currently sorted by, so a stylesheet written against the state would paint that column unconditionally.
Filament has the identical collision on `fi-ta-header-cell-sorted` and does nothing about it, so neither does this port — prefixing or escaping the hook would break the [styling contract](../../internals/styling.md#why-are-the-emitted-classes-public) that the class *is* the column's name — but it is worth knowing before naming a column after a table state.

### The default label reads the second-to-last dotted segment

`getDefaultLabel()` runs `beforeLast('.')` **then** `afterLast('.')`, which looks like a pair that cancels out.
It does not: for `artist.name` the first call yields `artist` and the second leaves it alone, so the column labels itself *"Artist"* — the **relationship** name, not the attribute.
That is Filament's recipe verbatim ([`HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php)), and it is intentional there: a column named `artist.name` sits in a table of songs, where *Artist* is the column heading a reader expects and *Name* would be meaningless.
A single-segment name is untouched by both calls, so `releasedAt` still labels *Released at*.

The capitalisation is `ucfirst`, not title case — Symfony's `title()` defaults to `$allWords = false`, matching Laravel's `ucfirst()` — so `releasedAt` is *"Released at"* and never *"Released At"*.
`kebab()` already folds underscores into dashes (it is `snake()->replace('_', '-')`), which is why one `replace('-', ' ')` covers what Filament's `replace(['-', '_'], ' ')` spells out in two.

### `state()` is `getStateUsing()` under another name

`state($x)` delegates straight to `getStateUsing($x)` — one property, one evaluation path — so the two names differ only in how they read at a call site, not in what they do.
Both accept a literal *or* a closure, and `evaluate()` returns a non-closure untouched: `->state('—')` pins every cell to a literal, and `->state(fn ($record) => …)` computes per record.
The reason both exist is Filament's, and it is ergonomic: `state()` reads as *this is the value* and `getStateUsing()` as *this is how to get the value*, which is the right phrasing depending on whether you are passing a literal or a callback.

The consequence to keep in mind is that they **overwrite each other** — the last call wins, and a column with `getStateUsing()` set never touches its record.
Once either is set, `getStateFromRecord()` is not called at all, so the column name stops being a read path and remains only a key and a label source.

### An undeclared `sortable()` has no sort columns, where an undeclared `searchable()` has its name

`getSortColumns()` and `getSearchColumns()` read the same way — `$this->sortColumns ?? [$this->getName()]` — but their properties start differently: `$sortColumns` is `[]` and `$searchColumns` is `null`.
So a column that never called `sortable()` answers `[]` and one that never called `searchable()` answers `[$this->getName()]`, and only the second falls through to the name.
That asymmetry is Filament's, property initialiser for property initialiser, and it is invisible in the getter that looks symmetric.

It is also harmless where a table reaches these getters, because it never reaches them on a column that declared nothing: a sort resolves its column through [`Table::getSortableVisibleColumn()`](table.md#sorting-and-global-search-both-skip-a-hidden-column), which returns nothing for a column that is not sortable, and a search reaches only the columns that answer `isGloballySearchable()` — or, one input at a time, `isIndividuallySearchable()`.
Where it shows is a column asked directly — a hand-built `Column` in a test, or a caller reaching past the table — where `applySort()` adds no `ORDER BY` at all rather than ordering by the column's own name.
Both arms are pinned in [`InteractsWithTableQueryTest`](../../../packages/tables/tests/Unit/Columns/Concerns/InteractsWithTableQueryTest.php) so the initialisers cannot be "tidied" into agreement without a failing test.

### The default and the split run inside the per-record cache, in that order

`getState()` caches one value per record key, and both `separator()` and `default()` run **inside** that cache rather than around it — so a `default()` closure is evaluated once per record, not once per read, and a template asking for the state three times gets one evaluation.
`ColumnTest`'s `'caches the defaulted state per record key'` case counts the calls, which is what a refactor moving either step outside `resolveState()` would break.

The order is Filament's and it matters both ways.
The split runs first, so a string state becomes a list before the blank test sees it — which is how a column whose whole value is `''` or `'   '` reaches `default()` at all: `explode()` yields one blank part, that single-blank case collapses to `[]`, and `[]` is blank.
Reversing the two would leave `['']` — filled, list-shaped, and un-defaultable.
The blank test runs second and reads the *post-split* value, so `default(['none'])` fills an emptied list exactly as `default('—')` fills an unset scalar.

Two edges are worth naming because a reader reaches for the wrong one.
The separator is read through `evaluate()` and then tested for **truthiness**, so `separator(null)` and `separator('')` both mean *do not split* rather than *split on nothing* — an empty separator would make `explode()` throw.
And the blank test is [`blank()`](../support/helpers.md), not falsiness: a state of `0`, `'0'` or `false` is filled and survives, where a naive `?:` would replace it with the default.

The single-blank collapse spells `mb_trim($parts[0]) === ''` rather than calling `blank()` on it, and that is a static-analysis constraint rather than a preference: `blank()` carries `@phpstan-assert-if-false !null $value`, so against an argument PHPStan already knows is a `string` the true branch is unreachable and the call is reported `function.impossibleType` at level max.
`explode()` returns `list<string>`, which is exactly that case.
Four other `src/` sites reach for the same spelling for the same reason; widening the assertion is support's to do, and until it happens a statically-known string cannot be passed to `blank()` anywhere in the toolkit.

### `distinctList()` dedupes entity values by value, not by identity

`distinctList()` on a fanned-out path runs `array_unique($values, SORT_REGULAR)`, which compares with `==`.
For the ordinary case — a list of strings pulled off a collection — that is exactly what it reads as.
For a path that ends **at** an association, where the values are entities rather than scalars, `==` on two objects of the same class compares their properties one by one, so two distinct rows that happen to carry equal field values collapse into one entry while `===` would have kept both.

It is Filament's behaviour too, reached by the same kind of comparison: `Collection::unique()` defaults to `$strict = false` and tests membership with `in_array($value, $exists, false)`, which is `==` again.
So neither codebase dedupes by identity here, and the note exists because the code says `SORT_REGULAR` — a flag that reads like "the default, nothing to see" and is in fact the whole semantics.
`SORT_STRING` (PHP's actual default for `array_unique()`) would be worse, not stricter: it would stringify the entities first and throw on any that are not `Stringable`.

## Divergences

### `isToggleable()`'s blank-label group arm is dropped with `ColumnGroup`

Filament's [`isToggleable()`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) returns true unconditionally for a column whose label is blank and whose group is a `ColumnGroup`, so the manager can toggle a whole group through its members.
There is no group tier here — [grouping and layout](#surface) is a todo row — so the arm would read a `getGroup()` that does not exist.
It returns with `ColumnGroup`; nothing else in the trait diverges.

### The editable write is not authorization-checked

The four editable columns write straight onto the record — no policy, no gate, nothing between the posted value and `persist()`/`flush()`.
Filament carries a `// Security:` comment on each of its editable column classes saying exactly this, and naming `disabled()` as the restriction; the port carries the same comment at the write site in [`CanUpdateState::updateState()`](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php), which is the one place a reader can act on it, and adds the part that is true here and not there: the toolkit ships **no** authorization layer at all, so `disabled()` is not merely the recommended gate, it is the only one there is.

The host checks it — `updateTableColumnState()` binds the record **before** asking `isDisabled()`, so a per-row closure judges the row it was handed — and nothing else is checked.
A write that must be restricted takes a `disabled()` closure, or the record is edited through a page or a modal action the application guards itself.
The full seam is on [`has-columns.md`](has-columns.md#the-write-is-not-authorization-checked).

### A column url is sanitized where Filament only warns

Filament's [`CanOpenUrl::url()`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) carries a comment telling the caller to validate a URL derived from user input against `javascript:` and validates nothing.
[`getUrl()`](../../../packages/tables/src/Columns/Concerns/CanOpenUrl.php) runs both of its arities through [`sanitize_url()`](../../../packages/support/src/helpers.php) instead, which is what the record arm on the very same anchor already does ([`HasRecordUrl::getRecordUrl()`](../../../packages/tables/src/Table/Concerns/HasRecordUrl.php)) and what [`ImageColumn::getDefaultImageUrl()`](../../../packages/tables/src/Columns/ImageColumn.php) does.
A refused URL answers `null`, so the cell falls back exactly as an unset one does — a `div` wrapper, or an unwrapped item — rather than emitting a live `javascript:` href.
Two arms of one `<a>` disagreeing about whether a scheme is safe would be the surprising shape, not this.

### A tooltip renders as a native `title`, and `Markup` is flattened to text

Filament drives every tooltip through Alpine and Tippy (`x-tooltip`), with an `x-tooltip.html` variant that renders an `Htmlable` tooltip as markup.
The toolkit ships no tooltip primitive — every tooltip in it is a native `title` attribute — so `tooltip()`, `headerTooltip()` and `emptyTooltip()` land as `title`, and a `Markup` value is flattened with `striptags` before it does, because an attribute value cannot hold markup.
Filament's `allowHTML` therefore has no analogue: rich tooltips are text here.
A floating tooltip control over support's already-bundled `@floating-ui/dom` is a support follow-up; when it arrives it replaces the rendering, not this PHP surface.

### The cache guards behind Filament's memoised cell bag are not ported

Filament builds the `<td>`'s attribute string in PHP — `getCellAttributeHtml()` — and memoises it through `getCachedCellAttributeHtml()`, which is only safe while nothing in the bag varies per row.
Five `hasDynamic*()` predicates exist to answer that: `hasDynamicExtraCellAttributes()`, `hasDynamicHiddenFrom()`, `hasDynamicVisibleFrom()`, and the alignment pair already on support's traits.

Here the `<td>` bag is assembled in Twig through [`attrs()`](../../../packages/support/src/Twig/Extension/AttributeExtension.php), so there is no PHP string to memoise and no cache to guard.
Porting the three column-tier predicates would ship dead methods that read as configuration, so they are dropped: `HasExtraCellAttributes` and `CanBeHiddenResponsively` carry only their setters and getters.
The two on support's traits stay, because they predate this and other consumers may read them.
`getExtraCellAttributeBag()` and `getExtraHeaderAttributeBag()` go with the cache for the same reason — the template spreads a plain array through `attrs()` and never wants a bag.

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

### `HasCellState` is relocated into the column tier

Filament hosts `HasCellState` in **support** (`Filament\Support\Concerns\HasCellState`), where two classes in two packages compose it — `Tables\Columns\Column` and `Actions\Exports\ExportColumn`.
Sylament files it at [`Sylament\Tables\Columns\Concerns\HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) instead.
The class name, method names and signatures are unchanged, so this is a relocation rather than a [rename](../renames.md).

Layering forces it.
The state read goes through schemas' [`PropertyAccessor`](../schemas/property-accessor.md) — the same one the form fields read and write through, and the only dotted-path reader in the port — and `support` sits *below* `schemas`, so a trait in support cannot import it.
The alternatives were both worse: pushing `PropertyAccessor` down into support would drag `symfony/property-info` and `symfony/type-info` into the base package for one consumer, and a support-hosted trait calling an abstract hook the column implements would spread one read across two packages to preserve a directory.

This is provisional in exactly one way.
Filament's placement pays off for its *second* consumer, `Actions\Exports\ExportColumn`, which is unported — the export layer is not ported.
When it is, the trait moves to the lowest package both consumers reach, which is `schemas` (both `tables` and `actions` require it) and still not `support`, because the accessor it reads through lives in schemas.
This section then becomes a record of where it used to be; nothing else about it changes.

A second support-hosted trait followed it for the same reason and is recorded in the same [table](../translations.md#namespace-segments): `CanAggregateRelatedModels` → `CanAggregateRelatedRecords`, whose declarations are read by tables' own Doctrine query builder.
Both would need the ORM in the base package to stay where Filament puts them, both are composed by `ExportColumn` over there, and both move to `schemas` together if it lands.
That one is [also renamed](../renames.md#renames), which this one is not.

### The state cache keys off the bound record key

Filament wraps the whole of `getState()` in `cacheState()`, which works the key out for itself: a `Column` asks the Livewire host (`$this->getLivewire()->getTableRecordKey($record)`), anything else reads `ArrayRecord::getKeyName()` off an array record or `getKey()` off a model.
Here [`getState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) caches under `getRecordKey()` — the key the table already bound to the column alongside its record — and `resolveState()` holds the body Filament passes to `cacheState()` as a closure.
Nothing is derived at read time and the host is never called back into — a keyed column reads a given record once however it was reached.

The visible consequence is a column nobody keyed: `getRecordKey()` is null, so `getState()` resolves fresh on every call instead of memoising.
That is the point of keying off the binding rather than the record — a column built by hand and handed one record after another (what every unit test does, and what a column used outside a table is) must see each one, where a derived key would have memoised the first read against a record the caller has since replaced.
Inside a table the case cannot arise: the row loop binds record and key together, so every rendered cell is keyed.

Two of Filament's guards are not ported with it.
`cacheState()` returns `null` outright when the column has no record, which would make `->state('—')` or a record-free closure resolve to null on an unbound column; `getState()` here consults the record only if something reads it, so a literal or a closure still resolves.
And the key test is `=== null` where Filament's is `blank()`, so an empty-string key caches here rather than reading fresh.
Only an array record handed an explicit empty `__key` can produce one — [`HasTable::getTableRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php) rejects a blank key on an object record outright.

### The object read is lenient where the accessor is strict

`PropertyAccessor::getFromRecord()` lets an accessor failure on an object hop (no public property, no getter, an uninitialized typed property) propagate as `Symfony\Component\PropertyAccess\Exception\AccessException`, deliberately, so a caller can judge it.
`getStateFromRecord()` judges it by returning `null`.

That is the semantics Filament has, arrived at differently: its read is `data_get($record, $name)`, which yields `null` for anything it cannot reach and never throws.
A column is display-only and is handed whatever the host's data source produced, so a typo'd name, a heterogeneous list, or a row missing an optional relation must render an empty cell — a table that 500s because one row of two hundred lacks a property is the wrong failure.
It matches the read-lenient arm schemas already ships in [`BelongsToRecord::fill()`](../schemas/belongs-to-record.md), for the same reason.

The strictness is not lost, it is placed: the **write** side (`setOnRecord()`) throws, because writing to a path nobody can reach is a bug with no sensible fallback.
Only the array arm is naturally lenient already — `getFromArray()` returns null for an absent key — so only the object arm needs the `catch`.
The same leniency covers every hop of a dotted walk: a segment the record cannot answer ends that branch with no value rather than raising, which is what lets one row of a fanned-out collection be unreadable without taking the other items with it.

### A to-one chain keeps the raw read, where Filament's relationship arm drops blanks

Filament routes **every** relationship-headed name through one arm: the results are collected, `blank()` entries are dropped, and a survivor count below two returns the first value.
A to-one relationship yields exactly one result, so `artist.name` over an artist whose name is `''` collects one blank, drops it, counts zero and answers `null`.
Here a chain of to-one hops is read straight — `''` stays `''` — and only a path that actually crosses a `Collection` gets the drop-blanks-then-shape treatment.

The divergence is narrow and it is deliberate: a to-one hop has no list to compact, so dropping a blank there is not list hygiene but a **value rewrite**, and the one thing it changes is which of `''` and `null` a formatter or a `default()` sees.
Filament can afford it because `getState()` immediately maps `blank($state)` onto `getDefaultState()` anyway, so the two collapse over there whenever a default is declared.

`default()` has since landed, which resolves the revisit this section reserved: `getState()` now runs the same blank-to-default step, so `''` and `null` collapse here exactly as they do upstream and the two ports answer alike at the only place a caller reads.
What still differs is one level down — `getStateFromRecord()` returns `''` where Filament's relationship arm returns `null` — which is why [`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php)'s `'reads a to-one chain unchanged, blank leaf included'` case asserts the raw read **and** the defaulted one: the pair is what pins the divergence to that level and proves it does not surface above it.

### The list shape is metadata-derived, where Filament's is cached off the first row

Whether a cell answers a scalar or a list must be **stable across rows** — a column rendering `Alice` on one row and `[Alice]` on the next breaks any formatter that type-tests, and `TextColumn` badges a list where it prints a scalar.
Filament decides it per column with `hasMultipleRelationship()`, which walks the relationship values of the record **in hand** and memoises the answer: whichever row is rendered first decides the shape for the whole table.
That is row-order-dependent by construction — a first row whose to-one hop is `null` never reaches the collection behind it, so `false` is cached and every later row is asked for a single value.

Here the question is asked of the **mapping** instead: `isCollectionValuedAssociation()` per segment, from the table's record class through `getQueryEntityManager()`, so the answer is a property of the declaration and no row can shift it.
The cache is still per column, but what it caches cannot depend on which record arrived first.
A path with no mapping to consult — the records lane, a DTO, a plain array — falls back to the **value**-derived answer (did this walk cross a collection), which is the only thing there is to ask there, and is why the fallback is not a defect: without an entity manager the shape is per row by necessity.

The cost is one metadata read per column per request, memoised in `$cachedPathTraversesToMany`; the gain is that the null-first-row case — the one a demo dataset produces the moment an optional relation exists — cannot silently change a column's type.

### A to-many relation stays lazy, where Filament eager-loads every cardinality

Filament's `applyEagerLoading()` calls `$query->with([$relationshipName])` for any relationship prefix, to-many included: Eloquent's eager load is a **second query** matched onto the parents afterwards, so cardinality costs it nothing and two collection columns are two extra queries.
Doctrine's equivalent is a fetch join on the same query, where two collection columns are a cartesian product of their rows — so this port fetch-joins **to-one chains only** and lets a collection column initialise its `PersistentCollection` per row.

That is one query per row per collection column, and it is characterised rather than hidden: the [eager-loading test](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) counts the statements a to-many page issues and asserts the per-row number, so the cost is a pinned fact and a future change to it fails a test rather than passing unnoticed.
The alternatives were weighed and both are worse: an unconditional collection fetch join goes cartesian the moment a second to-many column is added, and a fetch-join-when-exactly-one rule makes the query shape flip on an unrelated column being declared.

The follow-up that closes it is named rather than implied — **batch priming**: one query per collection column per page, loading the related rows for the page's identifiers, then hydrating them into the roots' collections.
Doctrine has no public API that primes a `PersistentCollection` from rows the caller already holds, so it is manual hydration work and it is why the batch is not in v1.

### The record union is plain `object` where Filament's is `Model`

`record()` accepts `object|array<string, mixed>` and `getRecord()` returns `object|array<string, mixed>|null`, against Filament's `Model | array<string, mixed>`.
This is the port-wide [model → record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) applied to the column tier: there is no base class every Doctrine entity extends, so `object` is the widest true bound, and a plain DTO, a value object or an entity are all equally valid rows.
Nothing in the column asks the record what it is — `getStateFromRecord()` branches on `is_array()` alone.

The string `action()` arm carries the same union for the same reason.
Filament wraps a method name into a closure typed `?Model $record`; the port types it `object|array|null`, because the Invoker injects by name whatever `getTableRecord()` answered, and on the `records()` lane that is an array row — which a `?object` hint would meet with a `TypeError` out of the live action rather than a call.

### The record key setter admits an `int`

`recordKey()` takes `string|int|null` where Filament's takes `?string`, and string-casts on the way in so the property and `getRecordKey()` stay `?string` — Filament's getter exactly.
The widening is forced by where the key comes from: the [table partial's row loop](table.md#the-morph-key-is-an-id-attribute-not-wirekey) binds each column's record and key together out of a Twig `{% for recordKey, record in records %}` over a PHP array, and PHP re-keys a numeric-string array key to an `int` — so a table of records keyed `'7'` hands the template `7`.
Filament never meets that: its record map is a Laravel `Collection`, whose keys survive as strings.

The cast lands at the setter, so `?string` is the only form anything downstream holds or reads — the property, the getter, and the [state cache](#the-state-cache-keys-off-the-bound-record-key) key that getter produces, where `7` and `'7'` have to be one entry and not two.

### The by-type record injection is not registered

Filament overrides `resolveDefaultClosureDependencyForEvaluationByType()` so that a closure parameter type-hinted `Model` or the record's own concrete class receives the record.
Sylament's `Column` does not override it at all.

The Filament version is gated on `is_a($parameterType, Model::class)`, which has no equivalent here for the reason above — there is no base class to gate on, and un-gating it would mean *any* class-typed parameter claims the record, ahead of the container and ahead of `$component`.
The named `$record` injection covers the need and is tested; a typed one arrives, if at all, with the schemas-side `instanceof` treatment already reasoned out in [belongs-to-record.md](../schemas/belongs-to-record.md).

Note that `fn (Column $column) => …` and `fn (Table $table) => …` still resolve, but neither of them by type: the first matches `$evaluationIdentifier`, the disjunct `EvaluatesClosures` short-circuits on ahead of its `is_a($this, $parameterType)` arm, and the second is the `$table` name injection above.
Reaching the `is_a()` arm takes a *differently* named parameter — `fn (Column $boundColumn) => …`, covered in [`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php) alongside the three name arms.
A `Table`-hinted parameter under any other name resolves nowhere and throws, since a column is not a table.

### Sort columns apply in declared order, where Filament reverses them

`applySort()` walks `getSortColumns()` front to back, appending one `addOrderBy()` per name, so `sortable(['plays', 'title'])` orders by *plays* and breaks ties on *title*.
Filament walks the same list through `array_reverse()` (`foreach (array_reverse($this->getSortColumns($query->getModel())) as $sortColumn)`) onto Eloquent's `orderBy()`, which **appends** to `$this->orders` exactly as `addOrderBy()` appends to the DQL `orderBy` part.
Reversing the input to an appending sink inverts the priority: the same declaration sorts by *title* first over there, and `sortable(['first_name', 'last_name'])` — the natural way to write *sort people by name* — sorts by last name.

The reversal is the vendor's behaviour verified in its own source rather than an inference from ours, and nothing around it reads as intent: no comment, no compensating reversal at the call site, and Filament's own sibling `Filters` and `Summarizers` copies of `InteractsWithTableQuery` have no such loop to compare against (nor does [the port's summarizer copy](summarizer.md), which sorts nothing).
A user's list is written in the order they want it applied, so we apply it as written.
The cost is the one that matters and it is small and visible: a migrating table with a multi-column `sortable()` gets its tie-break priority the other way round on the first click.

The relation arm leaves this alone on purpose, though it is the change most likely to have quietly re-opened it.
Its work happens *inside* the loop — each name now resolves through [`resolveOrderable()`](table.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery) instead of `resolve()`, so a to-one path joins and orders by its alias — while the iteration stays front to back, so `sortable(['artist.name', 'title'])` orders by artist and breaks ties on title, the way the declaration reads.
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
The rule reads the same at the table's [extra searchable columns](table.md#extra-searchable-columns-take-closures-that-return-their-expression), which take the same kind of closure.

The three injections are Filament's, unchanged — `$query`, `$search` and its `$searchQuery` alias — and only the first differs in what it holds: the table's builder, not a nested group.
`$isFirst`, the by-reference flag Filament threads through the column loop to choose `where` versus `orWhere`, has nothing to track here and is gone: an `Orx` with one part renders as that part, so the first column is not a special case.

The return is read through the `protected evaluateSearchQuery()` seam rather than inline because [`evaluate()`](../../../packages/support/src/Concerns/EvaluatesClosures.php) is `@template T @param T|Closure`: handed a value typed *exactly* `Closure`, PHPStan binds `T` to `Closure` and calls the result the callback rather than what it returned, so every test of the value reads as impossible at level max.
A `mixed`-returning method takes it out of the generic.
The shorter fix — a `@var mixed` on the assignment — does not survive `composer format`, whose `no_superfluous_phpdoc_tags` deletes it.

### The list getters take no record, and there is no `getDefault*Columns()` pair

`getSortColumns()` and `getSearchColumns()` are `(): array<string>` where Filament's are `(Model $record): array<string>`.
The parameter exists there only to feed `getDefaultSortColumns($record)` / `getDefaultSearchColumns($record)`, each of which returns `[$this->getFullAttributeName($record)]` — the relationship-aware resolution [the traversal row](#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) covers.

The relation layer has since landed and the pair did **not** come back, which is what makes this permanent rather than provisional.
The reason is where the resolution happens.
Filament resolves the name **into the list** — the getter hands back an already-qualified `artist.name` worked out against a model — so it needs a record to work it out from.
Here the list stays the user's own strings and each *consumer* resolves them: the sort through [`ColumnPathResolver::resolveOrderable()`](table.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery), which joins as it goes, and the search through [`SearchConstraint::forColumn()`](table.md#a-relation-search-is-a-correlated-exists-at-every-cardinality), which builds an `EXISTS` instead of a path at all.
Neither result *is* a column name — one carries a minted join alias, the other a whole predicate — so there is nothing a shared getter could return that both would want, and the record was never the missing ingredient: both resolutions read the **query**, which carries its own entity manager and root entity.

So the default stays `[$this->getName()]`, written into the `??` rather than split into a `getDefault*Columns()` method with one caller and nothing to derive.
The parameter would return only if some future consumer needed a per-record answer — a morph column, whose target class genuinely differs row by row, is the only shape that would — and it would be the query rather than a record, which is the port-wide [model → record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) landing on a signature that had no record to name in the first place.

### An aggregate column names itself freely, where Filament's must match Laravel's alias

Filament's aggregate value arrives as a **model attribute**: `withCount('songs')` selects a subquery aliased `songs_count`, Eloquent hydrates it onto the model, and the cell reads it because the column is *named* `songs_count`.
The name is therefore not a choice — it is Laravel's derived alias, and a column named `track_total` reading `counts('songs')` displays nothing at all, with no error to say why.

Here the value never touches the record: it is fetched into a [side store](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) keyed by the column's own name, so `TextColumn::make('track_total')->counts('songs')` works, and the name is free.
The derivation is still ported, because the **array** form declares several aggregates through one call and something has to say which of them fills this cell: there, an entry whose alias matches the column name wins, with `'relationship as alias'` honoured exactly as Laravel honours it.

Two deltas inside that derivation, both from `UnicodeString::snake()` standing in for `Str::snake()` over a string Laravel pre-mangles.
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
A genuinely computed aggregate — `SUM(price * quantity)` — is out of reach today and lands, if it lands, as a declared DQL expression rather than as a raw-SQL union member.
