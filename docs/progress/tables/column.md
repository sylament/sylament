---
description: 'The column base class: a `ViewComponent` named after the record path it reads, bound to a `Table` and handed one record at a time.'
ports:
  - vendor/filament/tables/src/Columns/Column.php
  - vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeInline.php
  - vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php
  - vendor/filament/tables/src/Columns/Concerns/CanCallAction.php
  - vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php
  - vendor/filament/tables/src/Columns/Concerns/HasExtraCellAttributes.php
  - vendor/filament/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php
  - vendor/filament/tables/src/Columns/Concerns/HasLabel.php
  - vendor/filament/tables/src/Columns/Concerns/HasName.php
  - vendor/filament/tables/src/Columns/Concerns/HasRecord.php
  - vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php
  - vendor/filament/tables/src/Columns/Concerns/HasTooltip.php
  - vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php
  - vendor/filament/tables/resources/views/index.blade.php
  - vendor/filament/support/src/Concerns/CanGrow.php
  - vendor/filament/support/src/Concerns/CanWrapHeader.php
  - vendor/filament/support/src/Concerns/HasCellState.php
  - vendor/filament/support/src/Concerns/HasWidth.php
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
  - packages/tables/src/Columns/Concerns/CanBeToggled.php
  - packages/tables/src/Columns/Concerns/CanCallAction.php
  - packages/tables/src/Columns/Concerns/CanOpenUrl.php
  - packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php
  - packages/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php
  - packages/tables/src/Columns/Concerns/HasLabel.php
  - packages/tables/src/Columns/Concerns/HasName.php
  - packages/tables/src/Columns/Concerns/HasRecord.php
  - packages/tables/src/Columns/Concerns/HasRowLoopObject.php
  - packages/tables/src/Columns/Concerns/HasTooltip.php
---
# Column (tables)

[`Sylament\Tables\Columns\Column`](../../../packages/tables/src/Columns/Column.php) is the base of the column catalog: a [`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) named after the record path it reads, bound to a [`Table`](table.md), and handed one record at a time.
It ports Filament's [`Column`](../../../vendor/filament/tables/src/Columns/Column.php) and the concern traits it composes, grouped by feature in the rows below.
A trait with a file of its own has its rows there: [`HasCellState`](has-cell-state.md), [`InteractsWithTableQuery`, `CanBeSearchable` and `CanBeSortable`](interacts-with-table-query.md), [`CanAggregateRelatedRecords`](can-aggregate-related-records.md), [`CanBeSummarized`](summarizer.md), and the editable columns' [`CanUpdateState` and `CanBeValidated`](has-columns.md).
The five traits only [`TextColumn`](text-column.md) composes (`CanFormatState`, `HasColor`, `HasDescription`, `HasIcon` and `HasIconColor`) have their rows in that file.
Support's traits are composed by a bare `use` line: [`HasPlaceholder`](../support/has-placeholder.md), [`HasAlignment`](../support/has-alignment.md), [`HasVerticalAlignment`](../support/has-vertical-alignment.md) and [`HasExtraAttributes`](../support/has-extra-attributes.md) have their rows in support, and `CanGrow`, `CanWrapHeader` and `HasWidth` have theirs here.
Filament's support-tier `HasCellState` and `CanAggregateRelatedModels` [sit in the column tier here](has-cell-state.md#hascellstate-is-relocated-into-the-column-tier), the second [renamed](../renames.md#renames), and its `HasTooltip` [stays split in two](#the-tables-tier-tooltip-trio-sits-beside-supports-one-tooltip-trait).
Each concrete column has its own file, listed in the [package index](README.md#files).

The column is the read side of the record, and on the Doctrine lane it is also the piece that knows how to ask for it.
It resolves nothing itself: the host fetches records ([`has-table.md`](has-table.md)), the table keys the columns ([`table.md`](table.md)), and a column asked for its state walks its dotted name over one record through schemas' [`PropertyAccessor`](../schemas/property-accessor.md), reached by composing schemas' [`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php), fanning out over any collection it crosses.
Given the table's [`QueryBuilder`](has-query.md#every-read-clones-a-memoised-base-query) it also applies its own ordering, contributes its own `LIKE` arm and fetch-joins what it displays, through `applySort()`, `applySearchConstraint()` and `applyEagerLoading()`: the column declares what sorting, searching and reading by it mean, and the table decides when to ask.
A name crossing an association needs nothing declared in `query()`: the [relation arm](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) resolves it against Doctrine's mapping.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(?string $name = null)` + the blank-name guard](../../../packages/tables/src/Columns/Column.php) | [`Column::make()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](../support/component.md#make-arguments-move-from-constructors-to-setters) |
| [`getDefaultName()`](../../../packages/tables/src/Columns/Column.php) | [`Column::getDefaultName()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | — |
| [`name()` / `getName()`](../../../packages/tables/src/Columns/Concerns/HasName.php) | [`Concerns\HasName`](../../../vendor/filament/tables/src/Columns/Concerns/HasName.php) | done | — | — |
| [`label()` / `getLabel()`, the name humanised when no label is set](../../../packages/tables/src/Columns/Concerns/HasLabel.php) | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | done | — | [semantics](#the-default-label-reads-the-second-to-last-dotted-segment) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | skipped | — | [translations.md](../translations.md#idiom) · [has-empty-state.md](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) |
| [`record()` / `getRecord()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | in progress | — | [diverges](#the-record-union-is-plain-object-where-filaments-is-model) · [detail](#getrecord-has-no-layout-fallback) |
| [`recordKey()` / `getRecordKey()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | done | — | [diverges](#the-record-key-setter-admits-an-int) |
| [`table()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php), [`Column::getTable()`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | — | [detail](#gettable-refuses-without-filaments-three-source-chain) |
| [`$evaluationIdentifier = 'column'` + the `$record` / `$rowLoop` / `$state` / `$table` / `$liveComponent` name injections](../../../packages/tables/src/Columns/Column.php) | [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](#the-by-type-record-injection-is-not-registered) · [semantics](#the-row-loop-is-a-blade-shaped-object-built-from-twigs-array) |
| [`rowLoop()` / `getRowLoop()`, the `$rowLoop` injection](../../../packages/tables/src/Columns/Concerns/HasRowLoopObject.php) | [`Concerns\HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | done | — | [semantics](#the-row-loop-is-a-blade-shaped-object-built-from-twigs-array) |
| Relationship traversal: `hasRelationship()`, `getRelationshipResults()`, `inverseRelationship()`, `hasNestedMorphToRelationship()`, the seven name caches, `applyRelationshipAggregates()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php), [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | skipped | — | [diverges](#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) |
| Visibility: [`hidden()` / `visible()` / `hiddenOn()` / `visibleOn()` / `isHidden()` / `isVisible()`](../../../packages/tables/src/Columns/Concerns/CanBeHidden.php) and [`toggleable()` / `toggledHiddenByDefault()` / `isToggleable()` / `isToggledHidden()`](../../../packages/tables/src/Columns/Concerns/CanBeToggled.php) | [`CanBeHidden`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeToggled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | done | — | [diverges](#istoggleables-blank-label-group-arm-is-dropped-with-columngroup) |
| [`hiddenFrom()` / `visibleFrom()` and their getters](../../../packages/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | [`Concerns\CanBeHiddenResponsively`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php) | done | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) |
| [`action()` / `getAction()` / `getActionKind()`](../../../packages/tables/src/Columns/Concerns/CanCallAction.php) | [`Concerns\CanCallAction`](../../../vendor/filament/tables/src/Columns/Concerns/CanCallAction.php) | done | — | [diverges](#the-record-union-is-plain-object-where-filaments-is-model) · [table.md](table.md#the-cell-wrapper-interleaves-the-column-arms-with-the-record-ones) · [has-columns.md](has-columns.md) |
| [`url()` / `openUrlInNewTab()` / `getUrl()` / `hasStateBasedUrls()` / `shouldOpenUrlInNewTab()`](../../../packages/tables/src/Columns/Concerns/CanOpenUrl.php) | [`Concerns\CanOpenUrl`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) | done | — | [diverges](#a-column-url-is-sanitized-where-filament-only-warns) · [semantics](#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom) |
| [`disabled()` / `isDisabled()` / `isEnabled()` / `disabledClick()` / `isClickDisabled()`](../../../packages/tables/src/Columns/Concerns/CanBeDisabled.php) | [`Concerns\CanBeDisabled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | done | — | [has-columns.md](has-columns.md#the-write-is-not-authorization-checked) |
| `disableClick()` | [`Concerns\CanBeDisabled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | skipped | — | [deprecated for `disabledClick()`](../renames.md#a-deprecated-filament-member-is-not-ported) |
| [`width()` / `getWidth()`](../../../packages/support/src/Components/Concerns/HasWidth.php) | [`Support\Concerns\HasWidth`](../../../vendor/filament/support/src/Concerns/HasWidth.php) | done | — | — |
| [`grow()` / `canGrow()`](../../../packages/support/src/Components/Concerns/CanGrow.php) | [`Support\Concerns\CanGrow`](../../../vendor/filament/support/src/Concerns/CanGrow.php) | done | — | — |
| [`wrapHeader()` / `canHeaderWrap()`](../../../packages/support/src/Components/Concerns/CanWrapHeader.php) | [`Support\Concerns\CanWrapHeader`](../../../vendor/filament/support/src/Concerns/CanWrapHeader.php) | done | — | — |
| [`inline()` / `isInline()`](../../../packages/tables/src/Columns/Concerns/CanBeInline.php) | [`Concerns\CanBeInline`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeInline.php) | done | — | — |
| [`tooltip()` / `headerTooltip()` / `emptyTooltip()` and their getters](../../../packages/tables/src/Columns/Concerns/HasTooltip.php) | [`Concerns\HasTooltip`](../../../vendor/filament/tables/src/Columns/Concerns/HasTooltip.php) | done | — | [diverges](#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) · [semantics](#the-tables-tier-tooltip-trio-sits-beside-supports-one-tooltip-trait) |
| [`extraCellAttributes()` / `extraHeaderAttributes()` and their getters](../../../packages/tables/src/Columns/Concerns/HasExtraCellAttributes.php) | [`HasExtraCellAttributes`](../../../vendor/filament/tables/src/Columns/Concerns/HasExtraCellAttributes.php), [`HasExtraHeaderAttributes`](../../../vendor/filament/tables/src/Columns/Concerns/HasExtraHeaderAttributes.php) | done | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) · [image-column.md](image-column.md#extra-img-attribute-registrations-merge-defaults-first) |
| [`getCellClassName()` / `getHeaderCellClassName()`, the per-column hook classes](../../../packages/tables/src/Columns/Column.php) | [`index.blade.php`'s `str($columnName)->camel()->kebab()`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [semantics](#the-hook-class-kebabs-each-dotted-segment-on-its-own) |
| Cell rendering: `getCellAttributeHtml()`, `getCachedCellAttributeHtml()` and the five `hasDynamic*()` cache guards | [`Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | skipped | — | [diverges](#the-cache-guards-behind-filaments-memoised-cell-bag-are-not-ported) |
| Column groups and layouts: `renderInLayout()`, `BelongsToGroup`, `BelongsToLayout` and composing `CanSpanColumns` | [`Column::renderInLayout()`](../../../vendor/filament/tables/src/Columns/Column.php) | todo | — | [unported](README.md#unported) |
| `BadgeColumn`, `BooleanColumn`, `TagsColumn` | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | skipped | — | [deprecated](../renames.md#a-deprecated-filament-member-is-not-ported) |

## Details

### `getTable()` refuses without Filament's three-source chain

Filament's `Column::getTable()` chains three sources (the column's own table, its group's, its layout's) and throws `LogicException("The column [x] is not mounted to a table.")` when all three are absent.
[`BelongsToTable::getTable()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) throws that same message, naming the same column, because a `date()` or `url()` closure reaching the table on an unmounted column is a real path and PHP's *"must not be accessed before initialization"* `Error` names nothing useful.

What is missing is the chain itself: column groups and column layouts are unported (their row is above), so there is nothing to fall back to and the getter reads one source.
The row flips when `BelongsToGroup` and `BelongsToLayout` land and the two remaining links are written.

### `getRecord()` has no layout fallback

Filament's `getRecord()` falls back to `$this->getLayout()?->getRecord()` when the column carries none of its own, so a column nested in a `Columns\Layout\` component reads the row the layout was handed.
That arm belongs to the unported column-layout tier (its row is above), so the getter returns the column's own record or null.
It lands with `BelongsToLayout`, in one piece with the `getTable()` chain [waiting on the same tier](#gettable-refuses-without-filaments-three-source-chain).

## Semantics

### The row loop is a Blade-shaped object built from Twig's array

`rowLoop()` takes what the cell loop can hand it, Twig's `loop` **array** for the record being rendered, reached as `loop.parent.loop` because the column loop is nested inside the record one, and stores the `stdClass` Filament's [`HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) declares, so a migrating `fn (stdClass $rowLoop) => $rowLoop->iteration` works unchanged.
The two vocabularies do not line up: Twig's `index` counts from 1 and its `index0` from 0, where Blade's `iteration` counts from 1 and its `index` from 0, so `index` means opposite things on either side, and reading Twig's array through Blade's names would be off by one in silence.
The translation is `iteration` ← `index`, `index` ← `index0`, `remaining` ← `revindex0`, `count` ← `length`, `first`/`last` straight across, `even` from `iteration % 2` with `odd` its inverse, `depth` fixed at `1`.

Two entries carry nothing rather than guessing.
Twig fills `length`, `revindex0` and `last` only for an array or a `Countable`, so on any other iterable they stay `null`; every table lane yields one of the two, so in practice they are filled.
And Twig's own `parent` entry holds the **whole parent template context**, not a parent loop, so it is never copied: `parent` is always `null`.
An `stdClass` handed in passes through untranslated, and `null` clears.

### `getUrl()` answers by argument count, so a closure's parameter name changes the DOM

`getUrl()` switches on `func_num_args()`: called with no argument it answers only a URL that does *not* read the state, and called with one, even `null`, only a URL that does.
The two callers are different places in the markup.
The cell wrapper calls it bare, so a state-based URL leaves the wrapper a plain `div`; the `TextColumn` and `ImageColumn` partials call it with one item's state, so the links are drawn per item inside the cell instead.

Which arm a column is on is decided by **reflection**, not by configuration: `hasStateBasedUrls()` asks whether the closure declares a parameter named `$state`.
So renaming that parameter, `fn ($state) => …` to `fn ($value) => …`, which any refactoring tool will offer, moves the whole cell from per-item anchors to one wrapping anchor, with no error anywhere.
Filament behaves identically; the trap is inherited, not introduced.

### The tables-tier tooltip trio sits beside support's one-tooltip trait

`Sylament\Support\Components\Concerns\HasTooltip` ships one tooltip with no state injection; the column tier's [`HasTooltip`](../../../packages/tables/src/Columns/Concerns/HasTooltip.php) ships three (the per-item one, the header's and the blank-state one) and injects `state` into the first.
That is Filament's own split (`Support\Concerns\HasTooltip` beside `Tables\Columns\Concerns\HasTooltip`), kept rather than collapsed, so widening support's would diverge from vendor for every non-column consumer.
`Column` composes the column-tier one only.
Read the import, never the bare `use HasTooltip;`: both traits are named `HasTooltip`.

### The hook class kebabs each dotted segment on its own

`getCellClassName()` and `getHeaderCellClassName()` split the column name on dots, camel-kebab each segment and rejoin with the dots intact, so `author.name` renders `sy-ta-cell-author.name` and `authorName` renders `sy-ta-cell-author-name`, two distinct hooks.
Running the whole name through one `u()->camel()->kebab()` would eat the dot and collapse the two onto the same class; Laravel's `Str::camel()`, which Filament calls, leaves dots alone, so segment-wise is what reproduces vendor rather than what departs from it.

The hook class is derived from a user-supplied name while the `<th>` also carries state classes in the same namespace, so the two can collide: a column literally named `sorted` emits `sy-ta-header-cell-sorted`, which is also the class the header stamps on the column the table is sorted by, so a stylesheet written against the state would paint that column unconditionally.
Filament has the identical collision on `fi-ta-header-cell-sorted` and does nothing about it, so neither does this port: prefixing or escaping the hook would break the [styling contract](../../internals/styling.md#why-are-the-emitted-classes-public) that the class *is* the column's name.

### The default label reads the second-to-last dotted segment

`getDefaultLabel()` runs `beforeLast('.')` **then** `afterLast('.')`, which looks like a pair that cancels out.
It does not: for `artist.name` the first call yields `artist` and the second leaves it alone, so the column labels itself *"Artist"*, the **relationship** name, not the attribute.
That is Filament's recipe verbatim ([`HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php)), and it is intentional there: a column named `artist.name` sits in a table of songs, where *Artist* is the column heading a reader expects and *Name* would be meaningless.
A single-segment name is untouched by both calls, so `releasedAt` labels *Released at*.

The capitalisation is `ucfirst`, not title case: Symfony's `title()` defaults to `$allWords = false`, matching Laravel's `ucfirst()`, so `releasedAt` is *"Released at"* and never *"Released At"*.
`kebab()` already folds underscores into dashes (it is `snake()->replace('_', '-')`), which is why one `replace('-', ' ')` covers what Filament's `replace(['-', '_'], ' ')` spells out in two.

## Divergences

### Relationship traversal ships as a value walk, a side store and one kept member

Most of Filament's `HasCellState` is Eloquent relationship machinery: `hasRelationship()`, `queriesRelationships()`, `getRelationship()`, `getRelationshipName()`, `getRelationshipResults()`, `hasMultipleRelationship()`, `getAttributeName()`, `getFullAttributeName()`, `getInverseRelationshipName()`, `hasNestedMorphToRelationship()`, the `inverseRelationship()` setter and seven memo properties.
Its `InteractsWithTableQuery` adds two consumers: `applyRelationshipAggregates()`, which reads six aggregate declarations onto `withAvg`/`withCount`/`withExists`/`withMax`/`withMin`/`withSum`, and `applyEagerLoading()`, which is `$query->with([$relationshipName])`.
They exist to answer *which prefix of a dotted name is a relationship hop and which is an attribute*, so a to-many hop can be flattened into a list, counted, or preloaded.

Filament derives the split by **asking an instantiated relation object** (`$record->isRelation($segment)` and the `Relation` it returns) and memoises each answer on the column, which is what the seven caches hold.
The port answers off the class instead: the cell read walks values and fans out at whatever turns out to be a `Collection`, so it needs no split at all, and the two query seams split the name against `ClassMetadata` through [`RelationPathResolver`](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused), which needs no record and so no per-record cache.
So none of those members has a counterpart: each is a step of a derivation this side does not perform.
`hasNestedMorphToRelationship()` goes with them: it exists only to stop the name caches being reused across records when a `MorphTo` hop sends each record to a different model, and the port keeps no such cache and maps no `MorphTo`.
`applyRelationshipAggregates()` goes for a different reason: the aggregate value never reaches the page query here, so there is nothing to apply it to ([why](has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)), while the six declaration setters it reads keep their names, their required second argument and their union types on [`CanAggregateRelatedRecords`](../../../packages/tables/src/Columns/Concerns/CanAggregateRelatedRecords.php), bar the [`Expression` arm](can-aggregate-related-records.md#the-illuminate-expression-arm-is-dropped-from-the-aggregated-column).
`applyEagerLoading()` is the kept member: it keeps its name and its position and changes its mechanism, and its row is in [interacts-with-table-query.md](interacts-with-table-query.md#surface).

`inverseRelationship()` names the reverse accessor a summarizer walks back through, and the [summarizer tier](summarizer.md) is its only consumer in either codebase: Filament re-roots a summary query on the related record through that accessor, so that a to-many relation's rows are weighted once.
The port [refuses the to-many case outright](summarizer.md#the-batch-keeps-a-to-one-relation-column-and-refuses-a-to-many-one) and batches the to-one one off the alias the eager load already joined, so there is no query left to re-root and nothing for the setter to name.

### `isToggleable()`'s blank-label group arm is dropped with `ColumnGroup`

Filament's [`isToggleable()`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) returns true unconditionally for a column whose label is blank and whose group is a `ColumnGroup`, so the manager can toggle a whole group through its members.
There is no group tier here (the column groups and layouts row is todo), so the arm would read a `getGroup()` that does not exist.
It returns with `ColumnGroup`; nothing else in the trait diverges.

### A column url is sanitized where Filament only warns

Filament's [`CanOpenUrl::url()`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) carries a comment telling the caller to validate a URL derived from user input against `javascript:` and validates nothing.
[`getUrl()`](../../../packages/tables/src/Columns/Concerns/CanOpenUrl.php) runs both of its arities through [`sanitize_url()`](../../../packages/support/src/helpers.php) instead, which is what the record arm on the same anchor does ([`HasRecordUrl::getRecordUrl()`](../../../packages/tables/src/Table/Concerns/HasRecordUrl.php)) and what [`ImageColumn::getDefaultImageUrl()`](../../../packages/tables/src/Columns/ImageColumn.php) does.
A refused URL answers `null`, so the cell falls back exactly as an unset one does, to a `div` wrapper or an unwrapped item, rather than emitting a live `javascript:` href.
Two arms of one `<a>` disagreeing about whether a scheme is safe would be the surprising shape, not this.

### A tooltip renders as a native `title`, and `Markup` is flattened to text

Filament drives every tooltip through Alpine and Tippy (`x-tooltip`), with an `x-tooltip.html` variant that renders an `Htmlable` tooltip as markup.
The toolkit ships no tooltip primitive, and every tooltip in it is a native `title` attribute, so `tooltip()`, `headerTooltip()` and `emptyTooltip()` land as `title`, and a `Markup` value is flattened with `striptags` first, because an attribute value cannot hold markup.
Filament's `allowHTML` therefore has no analogue: rich tooltips are text here.
A floating tooltip control in support, over the `@floating-ui/dom` it already bundles, would reverse this, and it would replace the rendering, not this PHP surface.

### The cache guards behind Filament's memoised cell bag are not ported

Filament builds the `<td>`'s attribute string in PHP, `getCellAttributeHtml()`, and memoises it through `getCachedCellAttributeHtml()`, which is only safe while nothing in the bag varies per row.
Five `hasDynamic*()` predicates exist to answer that: `hasDynamicExtraCellAttributes()`, `hasDynamicHiddenFrom()`, `hasDynamicVisibleFrom()`, and the alignment pair on support's traits.

Here the `<td>` bag is assembled in Twig through [`attrs()`](../../../packages/support/src/Twig/Extension/AttributeExtension.php), so there is no PHP string to memoise and no cache to guard.
Porting the three column-tier predicates would ship dead methods that read as configuration, so they are dropped: `HasExtraCellAttributes` and `CanBeHiddenResponsively` carry only their setters and getters.
The two on support's traits stay, because they belong to support's ports of `HasAlignment` and `HasVerticalAlignment`, not to the cell cache.
`getExtraCellAttributeBag()` and `getExtraHeaderAttributeBag()` go with the cache for the same reason: the template spreads a plain array through `attrs()` and never wants a bag.

### The record union is plain `object` where Filament's is `Model`

`record()` accepts `object|array<string, mixed>` and `getRecord()` returns `object|array<string, mixed>|null`, against Filament's `Model | array<string, mixed>`.
This is the port-wide [model → record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) applied to the column tier: there is no base class every Doctrine entity extends, so `object` is the widest true bound, and a plain DTO, a value object or an entity are all equally valid rows.
Nothing in the column asks the record what it is: `getStateFromRecord()` branches on `is_array()` alone.

The string `action()` arm carries the same union for the same reason.
Filament wraps a method name into a closure typed `?Model $record`; the port types it `object|array|null`, because the Invoker injects by name whatever `getTableRecord()` answered, and on the `records()` lane that is an array row, which a `?object` hint would meet with a `TypeError` out of the live action rather than a call.

### The record key setter admits an `int`

`recordKey()` takes `string|int|null` where Filament's takes `?string`, and string-casts on the way in so the property and `getRecordKey()` stay `?string`, Filament's getter exactly.
The widening is forced by where the key comes from: the [table partial's row loop](table.md#the-morph-key-is-an-id-attribute-not-wirekey) binds each column's record and key together out of a Twig `{% for recordKey, record in records %}` over a PHP array, and PHP re-keys a numeric-string array key to an `int`, so a table of records keyed `'7'` hands the template `7`.
Filament never meets that: its record map is a Laravel `Collection`, whose keys survive as strings.

The cast lands at the setter, so `?string` is the only form anything downstream holds or reads: the property, the getter, and the [state cache](has-cell-state.md#the-state-cache-keys-off-the-bound-record-key) key that getter produces, where `7` and `'7'` have to be one entry and not two.

### The by-type record injection is not registered

Filament overrides `resolveDefaultClosureDependencyForEvaluationByType()` so that a closure parameter type-hinted `Model` or the record's own concrete class receives the record.
Sylament's `Column` does not override it at all.

The Filament version is gated on `is_a($parameterType, Model::class)`, which has no equivalent here for the reason above: there is no base class to gate on, and un-gating it would mean *any* class-typed parameter claims the record, ahead of the container and ahead of `$component`.
The named `$record` injection covers the need; a typed one arrives, if at all, with the schemas-side `instanceof` treatment already reasoned out in [belongs-to-record.md](../schemas/belongs-to-record.md).

`fn (Column $column) => …` and `fn (Table $table) => …` still resolve, but neither of them by type: the first matches `$evaluationIdentifier`, the disjunct `EvaluatesClosures` short-circuits on ahead of its `is_a($this, $parameterType)` arm, and the second is the `$table` name injection above.
Reaching the `is_a()` arm takes a differently named parameter, `fn (Column $boundColumn) => …`.
A `Table`-hinted parameter under any other name resolves nowhere and throws, because a column is not a table.
