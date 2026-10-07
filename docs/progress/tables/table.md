---
description: 'The tables root object: the record data source, labels and keys, the columns, the row and header settings, and the partial that renders the table'
ports:
  - vendor/filament/tables/src/Table.php
  - vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php
  - vendor/filament/tables/src/Table/Concerns/HasRecords.php
  - vendor/filament/tables/src/Table/Concerns/HasColumns.php
  - vendor/filament/tables/src/Table/Concerns/CanBeStriped.php
  - vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php
  - vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php
  - vendor/filament/tables/src/Table/Concerns/HasRecordAction.php
  - vendor/filament/tables/src/Table/Concerns/HasRecordClasses.php
  - vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php
  - vendor/filament/tables/src/Table/Concerns/HasHeader.php
  - vendor/filament/tables/src/Table/Concerns/HasHeadings.php
  - vendor/filament/tables/resources/views/index.blade.php
  - vendor/filament/tables/resources/js/components/table.js
  - vendor/filament/tables/resources/css/container.css
  - vendor/filament/tables/resources/css/table.css
  - vendor/filament/tables/resources/css/header-cell.css
  - vendor/filament/tables/resources/css/cell.css
  - vendor/filament/tables/resources/css/row.css
  - vendor/filament/tables/resources/css/actions.css
owns:
  - packages/tables/assets/src/controllers/table_controller.ts
  - packages/tables/assets/src/styles/components/header.css
  - packages/tables/assets/src/styles/components/table.css
  - packages/tables/src/Table.php
  - packages/tables/src/Table/Concerns/BelongsToLiveComponent.php
  - packages/tables/src/Table/Concerns/CanBeStriped.php
  - packages/tables/src/Table/Concerns/CanReorderRecords.php
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

[`Sylament\Tables\Table`](../../../packages/tables/src/Table.php) is the counterpart to schemas' [`Schema`](../schemas/schema.md): a [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) a host builds through [`HasTable`](has-table.md).
Grouping, the column manager and the summaries it composes are [grouping.md](grouping.md), [column-manager.md](column-manager.md) and [summarizer.md](summarizer.md).

The `Table` fetches nothing itself.
It declares *how* records are reached — a `records()` closure, or a Doctrine `query()` — and the host resolves and caches them; see [`has-table.md`](has-table.md) for that half of the axis.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(InteractsWithTable $liveComponent)`](../../../packages/tables/src/Table.php) | [`Table::make()`](../../../vendor/filament/tables/src/Table.php) | done | — | [diverges](../support/component.md#make-arguments-move-from-constructors-to-setters) |
| [`liveComponent()` / `getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php) | [`BelongsToLivewire`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php) | done | — | [semantics](#the-host-binding-is-required-where-the-schemas-one-is-optional) |
| [`records()` / `getDataSource()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::records()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [has-table.md](has-table.md#the-records-closure-gets-filaments-eight-injections-three-of-them-typed-differently) |
| [`getRecords()` — always the current page's keyed array](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecords()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`getPaginator(): ?Paginator`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | — (Sylament-only) | done | — | [pagination.md](../support/pagination.md) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`$evaluationIdentifier = 'table'` + the `$liveComponent` name injection](../../../packages/tables/src/Table.php) | [`Table::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Table.php) | done | — | — |
| `$viewIdentifier = 'table'` | [`Table.php`](../../../vendor/filament/tables/src/Table.php) | skipped | — | [view-component.md](../support/view-component.md#the-instance-is-always-exposed-as-component) |
| [`$template`](../../../packages/tables/src/Table.php) + [the partial it names](../../../packages/tables/templates/partials/Table/index.html.twig) | [`$view = 'filament-tables::index'`](../../../vendor/filament/tables/src/Table.php), [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) · [diverges](#every-rule-in-tablecss-is-scoped-under-the-sy-ta-root-one-class-above-vendors) · [semantics](#a-markup-header-label-swaps-the-sort-control-for-a-span-that-names-its-three-events) · [semantics](#aria-sort-is-dropped-with-null-because-false-would-render-as-a-string) · [has-actions.md](has-actions.md#the-selection-cells-carry-no-header-cell-class-and-their-checkbox-skin-is-supports) |
| `LOADING_TARGETS` — the live actions a loading affordance waits on | [`Table::LOADING_TARGETS`](../../../vendor/filament/tables/src/Table.php) | todo | — | [can-defer-loading.md](can-defer-loading.md) |
| [`recordLabel()` / `pluralRecordLabel()` / `recordTitle()` / `recordTitleAttribute()` and their getters, `hasCustomRecordTitle()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) · [semantics](#the-record-title-reads-the-attribute-through-the-accessor-and-falls-back-to-the-label) · [has-empty-state.md](has-empty-state.md#the-empty-state-texts-read-the-record-labels) |
| [`getRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecordKey()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) |
| [Morph-stable row and cell keys](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `wire:key` stamps](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-morph-key-is-an-id-attribute-not-wirekey) · [semantics](#the-row-and-cell-morph-keys-disagree-on-record-vs-records) |
| The pivot surface — `hasPivotRecordKeys()`, `allowDuplicates()` / `allowsDuplicates()`, `selectPivotDataInQuery()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php), [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | skipped | — | [diverges](#the-pivot-surface-is-skipped-because-a-doctrine-association-table-with-payload-is-an-entity) |
| `resolveSelectedRecordsUsing()` / `getResolveSelectedRecordsCallback()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | todo | — | — |
| [`columns()` / `getColumns()` / `getColumn()` / `getVisibleColumns()` / `flushCachedVisibleColumns()`](../../../packages/tables/src/Table/Concerns/HasColumns.php) | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | in progress | — | [column.md](column.md) · [detail](#columns-takes-columns-only-and-does-not-push) · [semantics](#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again) |
| Column groups and layouts — `pushColumns()`, `getColumnGroups()` / `getColumnGroup()` / `hasColumnGroups()`, `getColumnsLayout()` / `getCollapsibleColumnsLayout()` / `hasColumnsLayout()` | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | todo | — | [detail](#columns-takes-columns-only-and-does-not-push) |
| [`queryStringIdentifier()` / `getQueryStringIdentifier()`](../../../packages/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | [`HasQueryStringIdentifier`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | done | — | [has-table.md](has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) |
| [`recordUrl()` / `openRecordUrlInNewTab()` / `hasCustomRecordUrl()` / `getRecordUrl()` / `shouldOpenRecordUrlInNewTab()` / `extraRecordLinkAttributes()` / `getExtraRecordLinkAttributes()`](../../../packages/tables/src/Table/Concerns/HasRecordUrl.php) | [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | done | — | [semantics](#the-record-anchor-spreads-its-extra-attributes-first-so-the-derived-pair-wins) |
| `getExtraRecordLinkAttributeBag()` | [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | skipped | — | [renames.md](../renames.md#getextrarecordlinkattributebag-has-no-consumer) · [semantics](#the-record-anchor-spreads-its-extra-attributes-first-so-the-derived-pair-wins) |
| [`recordAction()` / `getRecordAction()`](../../../packages/tables/src/Table/Concerns/HasRecordAction.php) | [`HasRecordAction`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php) | done | — | — |
| [The row chrome — the clickable marker, the stripe and the selected tint](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css), [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`index.blade.php`'s `<tr>` class list](../../../vendor/filament/tables/resources/views/index.blade.php), [`row.css`](../../../vendor/filament/tables/resources/css/row.css) | done | — | [diverges](#the-table-controller-absorbs-four-jobs-livewire-does-with-attributes) |
| [The cell wrapper — `<a>` / `<button>` / `<div>` chosen per record and per column](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `$columnWrapperTag` match](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [semantics](#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) |
| [The selection controller — record, page and select-all ticks and the selection banner](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`table.js`](../../../vendor/filament/tables/resources/js/components/table.js) | done | — | [diverges](#the-selection-controller-diverges-from-tablejs-in-four-places) · [diverges](#the-table-controller-absorbs-four-jobs-livewire-does-with-attributes) |
| [The reorder chrome — the toolbar trigger, the drag handle column, the sortable mount, the row markers and the indicator](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css), [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`index.blade.php`'s `fi-ta-reorder-*`](../../../vendor/filament/tables/resources/views/index.blade.php), [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) | done | — | [diverges](#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) · [diverges](#the-table-controller-absorbs-four-jobs-livewire-does-with-attributes) |
| [`reorderable()` / `isReorderable()` / `isReordering()` / `getReorderColumn()` / `getReorderDirection()` / `authorizeReorder()` / `isReorderAuthorized()` / the trigger action / `beforeReordering()` / `afterReordering()` and their callers](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) + [`HasReorderAnimationDuration`](../../../packages/support/src/Components/Concerns/HasReorderAnimationDuration.php) | [`CanReorderRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php), [`Support\Concerns\HasReorderAnimationDuration`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | done | — | [diverges](#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) · [host-can-reorder-records.md](host-can-reorder-records.md#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) · [sortable.md](../support/sortable.md) |
| [`heading()` / `description()` and their getters](../../../packages/tables/src/Table/Concerns/HasHeader.php) + [`rootHeadingLevel()` / `getRootHeadingLevel()` / `getHeadingLevel()` / `getHeadingTag()`](../../../packages/tables/src/Table/Concerns/HasHeadings.php) + [`header.css`](../../../packages/tables/assets/src/styles/components/header.css) | [`Table\Concerns\HasHeader`](../../../vendor/filament/tables/src/Table/Concerns/HasHeader.php), [`Table\Concerns\HasHeadings`](../../../vendor/filament/tables/src/Table/Concerns/HasHeadings.php) | done | — | [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) · [has-empty-state.md](has-empty-state.md#the-empty-states-heading-tag-demotes-itself-when-the-table-has-no-heading) |
| `header()` / `getHeader()` — the custom header block | [`Table\Concerns\HasHeader`](../../../vendor/filament/tables/src/Table/Concerns/HasHeader.php) | todo | — | [detail](#header-is-not-ported) |
| [`striped()` / `isStriped()`](../../../packages/tables/src/Table/Concerns/CanBeStriped.php) | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) | done | — | — |
| [`recordClasses()` / `getRecordClasses()`](../../../packages/tables/src/Table/Concerns/HasRecordClasses.php) | [`HasRecordClasses`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordClasses.php) | done | — | — |
| `stackedOnMobile()` / `isStackedOnMobile()`, `content()` / `getContent()` / `contentGrid()` / `getContentGrid()` / `contentFooter()` / `getContentFooter()` | [`Table\Concerns\CanBeStackedOnMobile`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStackedOnMobile.php), [`Table\Concerns\HasContent`](../../../vendor/filament/tables/src/Table/Concerns/HasContent.php) | blocked | the layout columns (`Columns\Layout`) | [detail](#the-card-layout-settings-wait-on-the-layout-columns) |
| [`HasDefaultDataFormattingSettings`, composed here](../../../packages/tables/src/Table.php) | [`Table.php`'s composed `Support\Concerns\HasDefaultDataFormattingSettings`](../../../vendor/filament/tables/src/Table.php) | done | — | [has-default-data-formatting-settings.md](../support/has-default-data-formatting-settings.md) |
| `HasExtraAttributes`, composed here | [`Table.php`'s composed `Support\Concerns\HasExtraAttributes`](../../../vendor/filament/tables/src/Table.php) | todo | — | — |

## Details

### `columns()` takes columns only, and does not push

Filament's `columns()` accepts three kinds of member — `Column`, `ColumnGroup` and a `Columns\Layout\Component` — resets the column, group and layout properties, and delegates to `pushColumns()`, which sorts each member into the flat `$columns` map, the `$columnsLayout` list and the `$columnGroups` map, then walks the result to cache each column's action.
Sylament's takes `array<Column>`, resets the column map and the visible-column memo, keys the map by [`getName()`](column.md), binds itself to each member, and then walks the set to cache every column action that is an `Action`.

What is missing is the group and layout tier: `ColumnGroup` and the layout columns are unported ([README](README.md#unported)), so there is no member for `pushColumns()` or the group and layout getters to sort.
What is kept is the shape that matters — `columns()` **resets** rather than appends, so a second call replaces the set and every column can reach the table and, through it, the host.
`pushColumns()` is the public append seam that arrives with the column groups and layouts, not with record grouping, which appends no column at all.

### `header()` is not ported

Filament's `header()` takes a `View | Htmlable | Closure | null` and its template renders it in place of the heading, description and header actions.
The port's [`HasHeader`](../../../packages/tables/src/Table/Concerns/HasHeader.php) carries `heading()` and `description()` only: there is no setter, no getter and no partial branch for a custom header block.

### The card layout settings wait on the layout columns

`content()`, `contentGrid()`, `contentFooter()` and `stackedOnMobile()` configure the **card** rendering rather than the table one: `content()` replaces the `<table>` with a caller-supplied list, and `stackedOnMobile()` is the responsive half of that list, whose point is the label-above-value stack a `<td>` cannot make.
Neither has anything to switch on until `Columns\Layout`'s `Split`, `Stack`, `Panel` and `Grid` exist, and the port has no `Columns\Layout` namespace.
[`RecordActionsPosition::AfterContent` renders nothing](has-actions.md#the-four-rendered-positions-collapse-to-two) for the same reason.

## Semantics

### The visible set asks each column twice, and is memoised until `columns()` is called again

`getVisibleColumns()` filters on `isVisible() && !isToggledHidden()`, and the second half reaches `isToggleable()`, which asks `isHidden()` again — so one walk evaluates a column's `visible()` closure **twice**, as Filament's does.
A closure with a side effect (a counter, a log line, a query) therefore fires twice per walk, which is the reason to keep such closures pure.

The walk itself is memoised on `$cachedVisibleColumns`, because the header, the row loop and the eager-loading pass each read it and the toggle half hops Column → Table → host → the applied column state on every column.
Two things clear it: `flushCachedVisibleColumns()`, the seam a host takes after writing new column state within one request, and `columns()` itself, because re-declaring the columns mid-request is how a reorder lands.
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
`getExtraRecordLinkAttributes()` spreads only under `columnUrl is null and recordUrl is not null`, because the record's link attributes belong to the record's link, and it spreads [under the derived pair](#the-record-anchor-spreads-its-extra-attributes-first-so-the-derived-pair-wins).
And a column whose URL is a function of its own state ([`hasStateBasedUrls()`](column.md#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom)) renders a `div` and lets its own partial draw one anchor per item — that negation has no record-level twin at all.

The `<button>` arm's live wiring branches three ways, and Twig cannot type-sniff a closure against an `Action`, so the choice is made in PHP by [`Column::getActionKind()`](column.md): `action` mounts through the record-action wiring under the **action's** own name (`columnAction.getName`, not the column's — the two coincide only when the column happens to name its action after itself), `closure` calls the host's [`callTableColumnAction()`](has-columns.md), and `null` falls through to the record action unchanged.
Each arm builds its whole attribute map **before** the single `attrs()` call, because `attrs()` keeps only the last `data-action` it is handed and two wiring sources meeting there would silently drop one.

The `action` arm only resolves because the table-tier `columns()` caches the instance: after binding each column it walks the set again and `cacheAction()`s every `action()` that is an `Action` rather than a closure, so `Table::getAction()` — the map actions' `resolveTableAction()` reads — knows the name the wrapper mounts.
Without it the mount unmounts silently, because `cacheMountedActions()` swallows the `ActionNotResolvableException`.
Vendor's loop is ported as it stands, so none of the `defaultSize`/`defaultTemplate` the record arm applies is applied here: a column action is mounted from the cell, never rendered as a trigger.

A second `columns()` call rebuilds the column map but **not** the action cache, so the replaced set's action stays reachable by name — Filament does not evict either, and the record-action arm behaves the same way here (the two setters that do evict are `toolbarActions()` and `headerActions()`, both through `removeCachedActions()`).
Wherever column sets are swapped at runtime, a stale column action stays mountable by name, and that is inherited behaviour rather than a decision this port took.

### `getRecords()` delegates to the host and fetches nothing

`Table::getRecords()` reads `$this->getLiveComponent()->getTableRecords()`, and that is Filament's shape too, not a stub.
The `Table` owns the *declaration* (`records()` stores a closure) while the host owns the *resolution*: it evaluates the closure, normalises the result, and caches it for the request.
Reading `getRecords()` as the fetch point and adding caching or evaluation here would duplicate — and diverge from — the host's copy in [`HasTable::getTableRecords()`](has-table.md).
The direction of the call is the thing to keep: table → host, never the reverse.

`getRecordKey($record)` is the same one line against [`getTableRecordKey()`](has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key), and reads the same way: the *convention* for identifying a record is the host's for **rendering** — the key a row renders, is ticked and mounts an action under — and the table is the seam a template or a column reaches it through.
Reading a key back is not the host's on the query lane, where every read decodes through `RecordKey`, which is why an override answering another key is refused there ([has-table.md](has-table.md#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)).
That is why the contract carries `getTableRecordKey()` rather than the table carrying a resolver of its own — a second implementation of the `id` read would be one more place to keep in step with [`InteractsWithRecord::resolveRecordKey()`](../actions/action.md).

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

The general rule the rest of the partial relies on holds — `null` and `false` both drop — and this is the one family where they differ.
Reach for `null` whenever the intent is *no attribute*, and keep `false` for a genuine boolean attribute (`disabled`) outside the `aria-` namespace.

### The row and cell morph keys disagree on `record` vs `records`

A row's key is `{hostId}.table.**records**.{recordKey}` and a cell's is `{hostId}.table.**record**.{recordKey}.column.{columnName}` — plural on the row, singular on the cell, for the same record and the same host.
It reads like a typo in one of the two.

It is Filament's, copied deliberately: [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) writes `.table.records.` on the `<tr>` and `.table.record.` on the `<td>`.
The strings are opaque — nothing parses them, they only have to be *stable* and *distinct* — so the asymmetry costs nothing, and "correcting" it would silently break any consumer that matched Filament's shape, which is the one thing porting the value verbatim buys.
Keep both spellings if this template is ever restructured; they are not two spellings of one constant.

### The host binding is required, where the schemas one is optional

`Table::make()` takes a non-optional `InteractsWithTable`, and `BelongsToLiveComponent::$liveComponent` here is a plain non-nullable property with no `getLiveComponentOrNull()` peek — deliberately unlike the same-named schemas trait ([`belongs-to-live-component.md`](../schemas/belongs-to-live-component.md)), whose property is nullable because a `Schema` can be built standalone for a preview render.
A table cannot: it reaches its records only through the host, so an unbound table has nothing to render and the binding is a constructor-time requirement rather than a later optional attachment.
Both packages ship a trait called `BelongsToLiveComponent` with different nullability — check the namespace before assuming which one you have.
This matches Filament, whose `Table\Concerns\BelongsToLivewire` is likewise non-nullable against schemas' nullable one.

### The record anchor spreads its extra attributes first, so the derived pair wins

`extraRecordLinkAttributes()` lets a caller put arbitrary attributes on the record's `<a>`, which is the [cell wrapper's](#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) `'a'` arm.
That arm builds its hash as `{...recordLinkAttributes, href: columnUrl ?: recordUrl, ...(newTab ? {target: '_blank'} : {})}` — the caller's bag **first**, the derived pair after it.
A Twig hash literal is last-wins, so that ordering is what makes `href` and the new-tab `target` unoverridable from the bag.

The order reads backwards and is vendor's outcome exactly.
Blade emits `generate_href_html($recordUrl, …)` *before* `{{ $getExtraRecordLinkAttributeBag($record) }}`, and duplicate attributes in HTML are **first**-wins — so vendor's first-emitted `href` is the one the browser keeps.
Ours reaches the same place through the opposite spelling, because `attrs()` merges a hash where the last writer wins.
Get it the other way round and it is not a cosmetic difference: `getRecordUrl()` pushes its answer through [`sanitize_url()`](../support/helpers.md), and a bag-supplied `href` would be a documented way around that sanitizer.

`target` is the one key that behaves differently from `href`, and deliberately: it is only in the literal when the new-tab flag is true, so a caller *can* set a `target` of their own on a table that opens no new tab, and cannot contradict one that does.
Everything else in the bag lands untouched, escaped like any other attribute — the values carry no markup risk, which is why the concern the setter's docblock names is the URL rather than the escaping.

### The record title reads the attribute through the accessor and falls back to the label

`getRecordTitle()` tries the `recordTitle()` closure first — the record injected by name and by type — then `recordTitleAttribute()` read through schemas' [`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), then the record label.
The attribute arm swallows an `AccessException` and treats a blank or non-scalar value as absent, so a missing property, a null column and an object column all answer the label rather than throwing or rendering an empty title — the same fallback panels' `Resource::getRecordTitle()` uses.

The return type is therefore `string`, never null: the label always answers.
That is what makes actions' unconditional table hop ([action.md](../actions/action.md#a-table-bound-action-titles-and-labels-through-its-table)) terminal — a table-bound action never reaches its host's title hook, because the table's answer is always filled.

## Divergences

### The partial emits no header scaffolding and no content wrapper

Filament's `index.blade.php` nests the table five deep — `fi-ta` > `fi-ta-ctn` > `fi-ta-main` > `fi-ta-content-ctn` > `table.fi-ta-table` — and renders the two header containers inside `fi-ta-main` (`fi-ta-header-ctn`, `fi-ta-header-toolbar`) **unconditionally**, behind `x-cloak` and an `x-show` that reads the client's selection count, so a toolbar holding only bulk actions appears the moment a record is ticked.
Sylament's partial is three deep — `sy sy-ta` > `sy-ta-ctn` > `table.sy-ta-table` or `div.sy-ta-empty-state` — with no `sy-ta-main`, no `sy-ta-content-ctn`, no `sy-ta-header-ctn`, and no `aria-label` on the `<table>`.
Its two bands, `sy-ta-header` (heading, description and the header actions, with the adaptive-position class from [`header.css`](../../../packages/tables/assets/src/styles/components/header.css)) and `sy-ta-header-toolbar` (toolbar actions, search, the filters trigger, the grouping selector and the column manager), render only when something would go in them, because the whole partial re-renders server-side.
The bulk-only case is the table controller's instead: a [bulk-only toolbar action is wrapped](has-actions.md#a-bulk-only-trigger-is-hidden-by-a-wrapper-span) in an element the controller hides while nothing is selected.

Vendor puts no `aria-label` on its `<table>` either: the `{{ $pluralModelLabel }}` label sits on the `role="list"` wrapper of the grid-content layout, which arrives with the layout columns.
The class names are reserved by the Filament originals, so a container that returns with the first feature it holds needs no new name.

The toolbar has two children, as Filament's does: a start-aligned group (reordering, toolbar actions, the grouping selector) and `.sy-ta-header-toolbar-end` (the search field, the filters trigger and the column manager), pushed to the end by `ms-auto` on the named class rather than by a positional selector.
Naming the group is what keeps [the start group](has-actions.md#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content) a rule of its own.

### The drag control mounts per mode, where vendor's instance outlives every render

Filament stamps `x-sortable` on the `<tbody>` whenever the table `isReorderable()` — the *capability* — and gates only the per-row `x-sortable-item` / `x-sortable-handle` on `isReordering()`, the *mode*.
So its Sortable instance exists on every render of a reorderable table and is simply childless while the mode is off.
Ours stamps [`sylament--support--sortable`](../support/sortable.md) on the `<tbody>` only while `isReordering` is true, so the instance is created when the mode opens and gone when it closes.

The reason is what a Stimulus controller costs across a morph.
UX re-renders the table into the same DOM, so an always-mounted control would have to survive idiomorph rewriting the rows underneath it — and SortableJS binds listeners to the child elements it was given.
It would then need either a `data-live-ignore` island (which would freeze the rows the server is trying to update) or a re-initialisation on every morph, and the reward for either is an instance that can do nothing, because there are no `[data-sortable-item]` children to drag until the mode is on.
Mounting per mode makes `connect()` and `disconnect()` line up exactly with the window in which dragging is legal, and it is the same reason the poll attribute is dropped rather than ignored while reordering ([the timer is ours](can-defer-loading.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers)).

Two things stay Filament's.
The animation duration is [support's shared `HasReorderAnimationDuration`](../../../packages/support/src/Components/Concerns/HasReorderAnimationDuration.php), composed by the table's `CanReorderRecords` exactly as Filament's composes its own `Support\Concerns\HasReorderAnimationDuration`.
And the handle is a per-row `[data-sortable-handle]` element rather than a whole draggable row, while no cell is clickable during the mode, because the [cell wrapper](#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) falls back to a `div` while reordering, which is vendor's `$isColumnClickDisabled` carrying `|| $isReordering`.

### Every rule in `table.css` is scoped under the `.sy-ta` root, one class above vendor's

Filament ships no `.fi-ta` rule at all — `.fi-ta-ctn`, `.fi-ta-table`, `.fi-ta-header-cell`, `.fi-ta-cell`, `.fi-ta-row`, `.fi-ta-actions` and `.fi-ta-empty-state` are each a top-level selector in a file of its own.
[`table.css`](../../../packages/tables/assets/src/styles/components/table.css) wraps all seven in one `.sy-ta { … }` block.
That is what lets the seven collapse into a single stylesheet, and it is what matches the `div.sy.sy-ta` root the partial always emits: deliberate scoping, not a nesting habit.

It costs exactly one class of specificity on every rule the file ships, and the shift is uniform: `.fi-ta-cell` at (0,1,0) becomes `.sy-ta .sy-ta-cell` at (0,2,0), `.fi-ta-ctn` becomes `.sy-ta > .sy-ta-ctn`, and each nested arm moves with its parent — `.fi-ta-cell .fi-ta-col` at (0,2,0) becomes `.sy-ta .sy-ta-cell .sy-ta-col` at (0,3,0).
Nothing *inside* the file changes, because every rule gains the same class; what changes is what an outside rule has to beat.

So a consuming app overriding a table rule with a single class — `.sy-ta-cell { … }`, (0,1,0) — loses to the shipped rule wherever its stylesheet sits in source order, where the same override against Filament would win on order alone.
It has to match or beat (0,2,0): re-scope it under `.sy-ta`, or double the class (`.sy-ta-cell.sy-ta-cell`).

[`summary.css`](../../../packages/tables/assets/src/styles/components/summary.css) and the row rules of [`grouping.css`](../../../packages/tables/assets/src/styles/components/grouping.css) are scoped under `.sy-ta` the same way and carry the same surcharge.
The package's other stylesheets declare their roots at top level as vendor does, so a consumer overriding a toolbar, a filter or a column rule pays nothing extra.
The other failure mode the extra class could introduce — a rule that silently never applies because the scope is absent — is closed at the render: the partial emits `sy sy-ta` unconditionally, and the Integration [`TableTest`](../../../packages/tables/tests/Integration/TableTest.php) selects `div.sy.sy-ta > div.sy-ta-ctn > table.sy-ta-table` with the child combinator, so a flattened or renamed root fails there rather than as an unstyled table.

### The morph key is an `id` attribute, not `wire:key`

Filament stamps `wire:key="{{ $this->getId() }}.table.records.{{ $recordKey }}"` on every `<tr>` and `wire:key="....table.record.{{ $recordKey }}.column.{{ $column->getName() }}"` on every `<td>`, so Livewire's morph pairs a row with the same record across a re-render instead of matching by position.
Sylament stamps the *same two strings* as plain `id` attributes.

The value is identical down to the separators, and the [`{{ $this->getId() }}` prefix](has-table.md#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) has a port of its own; only the attribute differs, because the two morph libraries key on different things.
Livewire's morphdom reads `wire:key`; UX LiveComponent uses **idiomorph**, which pairs nodes by `id` and has no per-framework key attribute to read.
Emitting `wire:key` here would be a string no library looks at, and idiomorph would fall back to positional matching — the exact failure the key exists to prevent, where deleting one row morphs the next row's DOM state onto the record after it.

Permanent, and it is the same substitution any Livewire → UX template port makes.
The `id` is a real document id, so it is also addressable from CSS and tests, which `wire:key` never was.

### The pivot surface is skipped, because a Doctrine association table with payload is an entity

Filament's `hasPivotRecordKeys()`, `allowDuplicates()` / `allowsDuplicates()` and `selectPivotDataInQuery()` serve the `BelongsToMany` pivot arm: rows keyed by the pivot, duplicate attachments allowed, and pivot columns aliased into the query.
In Doctrine an association table carrying payload is an entity of its own rather than a pivot with casts, so there is no pivot model to key rows by, to allow duplicates of, or to alias columns out of.

### The table controller absorbs four jobs Livewire does with attributes

Filament's `filamentTable` Alpine data ([`table.js`](../../../vendor/filament/tables/resources/js/components/table.js)) carries selection, group collapse and the filters-dropdown positioning.
[`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) (`sylament--tables--table`) carries selection and the grouping half ([grouping.md](grouping.md#collapse-is-keyed-by-title-and-so-is-the-group-break)), leaves the dropdown to support's `Dropdown`, and adds four behaviours that are attributes over there: `wire:poll` for the interval, `wire:init` for the deferred second request, `x-on:end` for the drag write, and an `x-bind:class` for the selected-row tint.
None of those has a UX LiveComponent counterpart that reads a **table** rather than a page, so each lands in the one controller the table already mounts.

The **selected-row tint**: `#repaint()` toggles `sy-selected` on each checkbox's closest `.sy-ta-row`, the same class Filament's Alpine binds, because the server cannot keep it in step once a tick costs no request.
The **reorder bridge** binds support's non-bubbling `sylament--support--sortable:end` event and turns the new order into the `reorderTable` action, which is the whole bridge, because [the drag control never talks to a live component](../support/sortable.md).
The **poll timer** is started and restarted from `pollIntervalValueChanged()` rather than only at connect, because a morph never re-runs `connect()` and [the attribute is dropped while reordering](can-defer-loading.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers).
The **deferred kick** binds no event at all: it fires `loadTable` once on connect, guarded by the flag it reads back out of the store rather than by a field of its own, so a reconnect after the load fires nothing.

The element gate is what keeps the cost proportional, and it is the reason the jobs share one controller.
The partial stamps `stimulus_controller('sylament--tables--table', …)` only when the table is selectable, reorderable, deferred, polling or grouped — a plain read-only table mounts nothing — and every value the jobs read (`canTrack`, `pageOnly`, `allSelectableCount`, `deferred`, `pollInterval`, `groupsCollapsedByDefault`, `countOne`, `countMany`) rides that one stamp.
A new responsibility widens the gate rather than adding a second `data-controller`.

### The selection controller diverges from `table.js` in four places

The selection half of [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) ports Filament's `filamentTable` data ([`table.js`](../../../vendor/filament/tables/resources/js/components/table.js)) line for line, minus the arms the port answers elsewhere: the filters-dropdown positioning (support's `Dropdown` owns that here), the two props no caller sets (`maxSelectableRecords` and the entangled `currentSelectionLivewireProperty`), the `mountAction` wrapper that pushed the three models just before mounting — every tick already pushes them, so there is nothing left to flush — the `scrollToTopOfTable` listener, and the `deselectAllTableRecords` browser event the host broadcasts to clear a selection, which lands as a re-render the repaint reads instead.
The controller writes selection through `component.set(model, value, false)` and reads it back through `component.getData(model)`, so a tick costs **zero** requests and the next action carries the whole selection — the reason a JS half exists at all, alongside the tri-state header checkbox, whose `indeterminate` is an IDL-only property no markup can carry.
It re-asserts that state onto the DOM on `connect()` and on the host's `render:finished` hook, because the store — not the server-rendered `checked` attribute — is what wins after the first morph.
These four divergences are behavioural, and all four are deliberate.

**The shift-click range excludes the clicked checkbox.**
Filament's `handleCheckboxClick` pushes every value between the anchor and the clicked box *inclusive*, because there the box's own toggle is an inline Alpine `click` handler that has already run by the time the range is applied.
Ours runs from a delegated `click` listener while the box's own tick is `change->sylament--tables--table#toggleRecord`, which fires **after** the click — so a range including the clicked key would set it and have `toggleRecord` immediately unset it again.
The coupling is worth stating because it lives in the template, not here: **the row checkbox must stay bound to `change`**.
Rebinding it to `click` reverses the two, and every shift-selection silently comes out one record short.

**The tracking count is clamped at zero.**
Filament returns `allSelectableRecordsCount - deselectedRecords.size` raw, which goes negative the moment the deselected list outlives the ceiling that produced it — a filter narrowing the query between morphs is enough.
The count is rendered into the selection banner, so `Math.max(…, 0)` turns a wrong number into a merely stale one.

**With no ceiling, the tracking count is the page's.**
Filament's `(ceiling ?? deselected.size) - deselected.size` evaluates to exactly `0` when the host's `getAllSelectableTableRecordsCount()` is null — the banner disappears while records are in fact selected.
We count the checked boxes on the page instead, the same floor the tri-state header checkbox already works from, so an unbounded tracking selection still reads as "n records selected" for the n on screen.

**Select-all's server arm is a void live action, and its page-only arm never leaves the browser.**
Filament `await`s `$wire.getAllSelectableTableRecordKeys()` and fills the client set from the return value, for both the no-tracking and the page-only case.
UX LiveComponent actions return no value to JS, so the no-tracking arm calls `component.action('selectAllTableRecords')` and lets the host fill its own prop and re-render — the repaint on `render:finished` is what puts the ticks on screen.
The page-only arm needs no round trip at all: the keys it would ask for are the `value`s of the checkboxes already rendered, so it selects those directly.
The tracking arm is Filament's — flag up, both lists empty, zero requests.
