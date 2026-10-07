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
  - packages/tables/src/Columns/Concerns/CanBeDisabled.php
  - packages/tables/src/Columns/Concerns/CanBeHidden.php
  - packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php
  - packages/tables/src/Columns/Concerns/CanBeInline.php
  - packages/tables/src/Columns/Concerns/CanBeSummarized.php
  - packages/tables/src/Columns/Concerns/CanBeToggled.php
  - packages/tables/src/Columns/Concerns/CanCallAction.php
  - packages/tables/src/Columns/Concerns/CanOpenUrl.php
  - packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php
  - packages/tables/src/Columns/Concerns/HasIcon.php
  - packages/tables/src/Columns/Concerns/HasLabel.php
  - packages/tables/src/Columns/Concerns/HasName.php
  - packages/tables/src/Columns/Concerns/HasRecord.php
  - packages/tables/src/Columns/Concerns/HasRowLoopObject.php
  - packages/tables/src/Columns/Concerns/HasTooltip.php
  - packages/tables/src/Columns/Contracts/
---
# Column (tables)

[`Sylament\Tables\Columns\Column`](../../../packages/tables/src/Columns/Column.php) — the base of the column catalog: a [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) named after the record path it reads, bound to a [`Table`](table.md), and handed one record at a time.
It ports Filament's [`Filament\Tables\Columns\Column`](../../../vendor/filament/tables/src/Columns/Column.php) together with the concern traits it composes — twenty-eight today, fifteen of them landed by the column catalog.
Twenty live in the column tier ([`BelongsToTable`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php), [`CanAggregateRelatedRecords`](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php), [`CanBeDisabled`](../../../packages/tables/src/Columns/Concerns/CanBeDisabled.php), [`CanBeHidden`](../../../packages/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeHiddenResponsively`](../../../packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php), [`CanBeInline`](../../../packages/tables/src/Columns/Concerns/CanBeInline.php), [`CanBeSearchable`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php), [`CanBeSortable`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeToggled`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php), [`CanCallAction`](../../../packages/tables/src/Columns/Concerns/CanCallAction.php), [`CanOpenUrl`](../../../packages/tables/src/Columns/Concerns/CanOpenUrl.php), [`HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php), [`HasExtraCellAttributes`](../../../packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php), [`HasExtraHeaderAttributes`](../../../packages/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php), [`HasLabel`](../../../packages/tables/src/Columns/Concerns/HasLabel.php), [`HasName`](../../../packages/tables/src/Columns/Concerns/HasName.php), [`HasRecord`](../../../packages/tables/src/Columns/Concerns/HasRecord.php), [`HasRowLoopObject`](../../../packages/tables/src/Columns/Concerns/HasRowLoopObject.php), [`HasTooltip`](../../../packages/tables/src/Columns/Concerns/HasTooltip.php), [`InteractsWithTableQuery`](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php)), and seven are support's, composed by a bare `use` line ([`CanGrow`](../../../packages/support/src/Components/Concerns/CanGrow.php), [`CanWrapHeader`](../../../packages/support/src/Components/Concerns/CanWrapHeader.php), [`HasAlignment`](../../../packages/support/src/Components/Concerns/HasAlignment.php), [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php), [`HasPlaceholder`](../../../packages/support/src/Components/Concerns/HasPlaceholder.php), [`HasVerticalAlignment`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php), [`HasWidth`](../../../packages/support/src/Components/Concerns/HasWidth.php)), beside schemas' [`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php); thirty in Filament — twenty from its `Columns\Concerns\` and ten from `Support\Concerns\`, of which `HasCellState` and `CanAggregateRelatedModels` [moved package](has-cell-state.md#hascellstate-is-relocated-into-the-column-tier) (the second also [renamed](../renames.md#renames)) while `HasAlignment` stayed and `HasTooltip` [split in two](#the-tables-tier-tooltip-trio-sits-beside-supports-one-tooltip-trait).
Both of Filament's tiers count toward that same thirty, which is why the support-hosted group belongs in the numerator here as well.
All of them are folded into the surface table below rather than earning rows upstairs, per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature) — `InteractsWithTableQuery` included, since its aggregate and eager-loading half is [built](#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) — and the `Columns\Concerns\` traits not ported at all keep a row there too, bar the five [`TextColumn`](text-column.md) composes — `CanFormatState`, `HasColor`, `HasDescription`, `HasIcon` and `HasIconColor` — which fold into [`text-column.md`](text-column.md) instead, because that is the class composing them.
The rows below group those files by feature, which is what the per-file rows upstairs point at.

The column is the read side of the record, and on the Doctrine lane it is also the piece that knows how to ask for it.
It resolves nothing itself: the host fetches records ([`has-table.md`](has-table.md)), the table keys the columns ([`table.md`](table.md)), and a column asked for its state walks its dotted name over one record through schemas' [`PropertyAccessor`](../schemas/property-accessor.md) — the same accessor the form fields write through, reached by composing schemas' [`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php) — fanning out over any collection it crosses.
Given the table's [`QueryBuilder`](has-query.md#every-read-clones-a-memoised-base-query) it also applies its own ordering, contributes its own `LIKE` arm and fetch-joins what it displays, through `applySort()`, `applySearchConstraint()` and `applyEagerLoading()` — the column declares what sorting, searching and reading *by it* mean, and the table decides when to ask.
A name crossing an association needs nothing declared in `query()`: the [relation arm](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) resolves it against Doctrine's mapping.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(?string $name = null)` + the blank-name guard](../../../packages/tables/src/Columns/Column.php) | [`Column::make()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](../renames.md#make-arguments-move-from-constructors-to-setters) |
| [`getDefaultName()`](../../../packages/tables/src/Columns/Column.php) | [`Column::getDefaultName()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | — |
| [`name()` / `getName()`](../../../packages/tables/src/Columns/Concerns/HasName.php) | [`Concerns\HasName`](../../../vendor/filament/tables/src/Columns/Concerns/HasName.php) | done | — | — |
| [`label()` / `getLabel()` — explicit value wins, else the name is humanised](../../../packages/tables/src/Columns/Concerns/HasLabel.php) | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | done | — | [semantics](#the-default-label-reads-the-second-to-last-dotted-segment) |
| [`record()` / `getRecord()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | in progress | — | [diverges](#the-record-union-is-plain-object-where-filaments-is-model) · [detail](#getrecord-has-no-layout-fallback) |
| [`table()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php) | in progress | — | [detail](#gettable-refuses-without-filaments-three-source-chain) |
| [`$evaluationIdentifier = 'column'` + the `$record` / `$rowLoop` / `$state` / `$table` / `$liveComponent` name injections](../../../packages/tables/src/Columns/Column.php) | [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | — | [diverges](#the-by-type-record-injection-is-not-registered) · [semantics](#the-row-loop-is-a-blade-shaped-object-built-from-twigs-array) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | blocked | i18n ([has-empty-state.md](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)) | — |
| [`placeholder()` / `getPlaceholder()` — the empty-cell copy](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [has-placeholder.md](../support/has-placeholder.md) |
| [`recordKey()` / `getRecordKey()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | done | — | [diverges](#the-record-key-setter-admits-an-int) |
| [`rowLoop()` / `getRowLoop()` — the `$rowLoop` injection](../../../packages/tables/src/Columns/Concerns/HasRowLoopObject.php) | [`Concerns\HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | done | — | [semantics](#the-row-loop-is-a-blade-shaped-object-built-from-twigs-array) |
| Relationship traversal — `hasRelationship()`, `getRelationshipResults()`, `inverseRelationship()`, the seven name caches, `applyRelationshipAggregates()`, `applyEagerLoading()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php), [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | in progress | — | [detail](#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) — `inverseRelationship()` is settled as **not ported** there, because the summarizer tier is its only consumer and refuses the case it exists for; what holds the row is `hasNestedMorphToRelationship()` |
| Visibility — [`hidden()` / `visible()` / `hiddenOn()` / `visibleOn()` / `isHidden()` / `isVisible()`](../../../packages/tables/src/Columns/Concerns/CanBeHidden.php) and [`toggleable()` / `toggledHiddenByDefault()` / `isToggleable()` / `isToggledHidden()`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php) | [`CanBeHidden`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeToggled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | done | — | [diverges](#istoggleables-blank-label-group-arm-is-dropped-with-columngroup) |
| [`hiddenFrom()` / `visibleFrom()` — the responsive pair](../../../packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | [`Concerns\CanBeHiddenResponsively`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | done | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) |
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

Ten more cases in that file are the collection walk, and they are written so the **shape** rules cannot pass by accident: a `tags.name` list, a *single*-element one still a list, blanks dropped, `distinctList()` deduping only once set, a path that collects nothing answering null, a to-one chain read raw with a blank leaf intact, a dotless collection name left to the plain record read, a path ending *at* the collection flattening into its items, a row whose to-one hop is null still reading as a list — the case [metadata multiplicity](has-cell-state.md#the-list-shape-is-metadata-derived-where-filaments-is-cached-off-the-first-row) exists for — and the records lane falling back to the values, with no mapping to ask.

The two browsing rows are covered in the same file.
`sortable()` is asserted on all four arms — the default false, the bare call, an explicit `false`, a closure, and the column-list form still reading sortable.
`searchable()` splits into four cases: the three unset defaults together (searchable, globally, individually all false); the bare call defaulting to **globally** searchable and not individually; the string and array forms staying searchable; and the `isIndividual: true, isGlobal: false` pair inverting both.
A fifth case is the one that would fail against Filament's own implementation — a closure condition returning `false` leaving the column unsearchable, and one returning `true` leaving it globally searchable — which is the [vendor defect](interacts-with-table-query.md#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) this port fixes.

`alignment()` is the one row covered outside this package: the setter, the string-to-enum resolution and the `sy-align-*` fragment are support's, pinned in [has-alignment.md](../support/has-alignment.md), and what tables owes is the class reaching the cell — asserted by the Integration [`TextColumnTest`](../../../packages/tables/tests/Integration/Columns/TextColumnTest.php), which is also where [how it reaches a badge row](text-column.md#alignment-reaches-a-badge-row-through-the-list-not-through-text-align) is recorded.

The remaining five rows split two ways.
Three — the list getters, the query application and the eager-loading arm — are [`InteractsWithTableQueryTest`](../../../packages/tables/tests/Unit/Columns/Concerns/InteractsWithTableQueryTest.php)'s, asserted against DQL and bound parameters rather than fetched rows: SQLite matches `LIKE` case-insensitively for ASCII, so an execution-level assertion would pass whether or not the `LOWER()` wrapping the case-insensitive arm exists.
It pins the default sort column ordering in the direction it was handed, a declared list ordering [front to back](interacts-with-table-query.md#sort-columns-apply-in-declared-order-where-filament-reverses-them), a `sortable(query:)` closure receiving the builder and the direction and being the only thing applied, the default search column composing into the caller's `Orx` with its `%term%` parameter bound (and a two-name list composing both arms under distinct parameter names), a `searchable(query:)` closure's returned expression *and* returned DQL string both landing in that `Orx` while a closure returning nothing leaves it empty and one returning the **builder** it constrained leaves it empty too — with that constraint on the query, which is the [skip](interacts-with-table-query.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) that keeps a `Stringable` builder out of `Orx::add()` — all three of Filament's injections proved by name (`$query` by identity against the builder passed in, `$search` and its `$searchQuery` alias by value), `forceSearchCaseInsensitive()` lowering both sides of one column's comparison, a dotted name resolving against a joined alias — asserted through `getSQL()`, so the alias-headed `ORDER BY` is proved to *parse* and not merely to read right — and both getters' declared-and-defaulted arms.
Five cases carry the relation arm through the same seams: an alias-headed name still winning verbatim on both the sort and the search side, an unaliased one being *joined* rather than refused while an unknown head still throws, a to-one column fetch-joining every alias it crosses, a column that already sorted by the same path reusing that join and selecting its alias once, and the five shapes `applyEagerLoading()` leaves alone — dotless, to-many, alias-headed, unmapped and association-terminal.

The last two rows — the aggregate declarations and the cell they bind to — are [`CanAggregateRelatedRecordsTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanAggregateRelatedRecordsTest.php)'s, fifteen cases over resolution rather than SQL: each kind mapping to its function (`exists` the bool-cast `COUNT`), a closure declaration evaluating through the getters, the string form binding whatever the column is called, the array form picking the entry whose derived alias *is* the column name and resolving to null when none does, a camelCase relationship deriving through snake case, an array value that is a `Closure` carried as the scope, and the four multi-claim shapes — a string form beside an array naming another cell resolving, two array forms naming no cell staying [inert](can-aggregate-related-records.md#an-aggregate-declaration-that-claims-no-cell-computes-nothing), and both genuinely ambiguous pairs refusing.

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
Doctrine answers off the class instead: the cell read simply walks values and fans out at whatever turns out to be a `Collection` (no split needed at all), and the two query seams split the name against `ClassMetadata` through [`RelationPathResolver`](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused), which needs no record and so needs no per-record cache.
So `hasRelationship()`, `getRelationshipResults()`, `hasMultipleRelationship()`, the two attribute-name getters and the seven caches have no counterpart to port: each is a step of a derivation this side does not perform.
`applyRelationshipAggregates()` goes the same way for a different reason — the aggregate value never reaches the page query here, so there is nothing to apply *to* it ([why](has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)) — while the six declaration setters it reads keep their names, their required second argument and their union types on [`CanAggregateRelatedRecords`](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php), bar the [`Expression` arm](can-aggregate-related-records.md#the-illuminate-expression-arm-is-dropped-from-the-aggregated-column).
`applyEagerLoading()` keeps its name and its position and changes its mechanism: a fetch join on the page query rather than a second `with()` query, and [to-one only](interacts-with-table-query.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality).

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

The cast lands at the setter, so `?string` is the only form anything downstream holds or reads — the property, the getter, and the [state cache](has-cell-state.md#the-state-cache-keys-off-the-bound-record-key) key that getter produces, where `7` and `'7'` have to be one entry and not two.

### The by-type record injection is not registered

Filament overrides `resolveDefaultClosureDependencyForEvaluationByType()` so that a closure parameter type-hinted `Model` or the record's own concrete class receives the record.
Sylament's `Column` does not override it at all.

The Filament version is gated on `is_a($parameterType, Model::class)`, which has no equivalent here for the reason above — there is no base class to gate on, and un-gating it would mean *any* class-typed parameter claims the record, ahead of the container and ahead of `$component`.
The named `$record` injection covers the need and is tested; a typed one arrives, if at all, with the schemas-side `instanceof` treatment already reasoned out in [belongs-to-record.md](../schemas/belongs-to-record.md).

Note that `fn (Column $column) => …` and `fn (Table $table) => …` still resolve, but neither of them by type: the first matches `$evaluationIdentifier`, the disjunct `EvaluatesClosures` short-circuits on ahead of its `is_a($this, $parameterType)` arm, and the second is the `$table` name injection above.
Reaching the `is_a()` arm takes a *differently* named parameter — `fn (Column $boundColumn) => …`, covered in [`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php) alongside the three name arms.
A `Table`-hinted parameter under any other name resolves nowhere and throws, since a column is not a table.
