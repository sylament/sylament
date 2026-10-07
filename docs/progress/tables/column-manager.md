---
description: The chrome that lets a user hide, show and reorder a table's columns, and the state behind it, across the table and host tiers.
ports:
  - vendor/filament/tables/src/Table/Concerns/HasColumnManager.php
  - vendor/filament/tables/src/Concerns/HasColumnManager.php
  - vendor/filament/tables/src/Enums/ColumnManagerLayout.php
  - vendor/filament/tables/src/Enums/ColumnManagerResetActionPosition.php
  - vendor/filament/tables/resources/views/components/column-manager/index.blade.php
  - vendor/filament/tables/resources/views/components/column-manager/content.blade.php
  - vendor/filament/tables/resources/js/components/column-manager.js
  - vendor/filament/tables/resources/css/column-manager.css
owns:
  - packages/tables/assets/src/controllers/column_manager_controller.ts
  - packages/tables/assets/src/styles/components/column-manager.css
  - packages/tables/src/Concerns/HasColumnManager.php
  - packages/tables/src/Enums/ColumnManagerLayout.php
  - packages/tables/src/Enums/ColumnManagerResetActionPosition.php
  - packages/tables/src/Table/Concerns/HasColumnManager.php
  - packages/tables/templates/components/ColumnManager/
  - packages/tables/templates/components/ColumnManagerContent/
---
# Column manager (tables)

The **developer**'s half of visibility is a different feature: [`hidden()` / `visible()`](column.md#surface) and the responsive [`hiddenFrom()` / `visibleFrom()`](column.md#surface) are the column's own, and [`Table::getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again) is what folds both halves into the one set the partial and the query loops read.
This file is only the manager: the state a user's choices live in, and the panel that edits it.

Two traits of the same short name are in play here, [`Table\Concerns\HasColumnManager`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) and [`Concerns\HasColumnManager`](../../../packages/tables/src/Concerns/HasColumnManager.php), and [two more](has-columns.md) elsewhere in the package: read the **import**, never the bare `use HasColumnManager;`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columnManager()` / `hasColumnManager()` — an explicit answer, else derived from what there is to edit](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#the-column-state-is-re-derived-against-the-definition-on-every-read) · [diverges](#an-explicit-columnmanagertrue-over-nothing-editable-lists-no-rows-where-filaments-lists-every-column) |
| [`deferColumnManager()` / `hasDeferredColumnManager()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | — |
| [`reorderableColumns()` / `hasReorderableColumns()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#the-order-is-followed-only-while-columns-are-reorderable) · [diverges](#the-reorderable-answer-is-asked-at-each-use-where-filament-memoises-it-per-host) |
| [`persistColumnsInSession()` / `persistsColumnsInSession()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [diverges](#the-session-is-written-at-the-end-of-every-mutation-rather-than-once-per-request) · [diverges](#the-session-read-ignores-persistcolumnsinsession-where-filaments-honours-it) |
| [`columnManagerLayout()` / `getColumnManagerLayout()` / `hasColumnManagerDropdown()` / `hasColumnManagerModal()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) + [`ColumnManagerLayout`](../../../packages/tables/src/Enums/ColumnManagerLayout.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php), [`Enums/ColumnManagerLayout`](../../../vendor/filament/tables/src/Enums/ColumnManagerLayout.php) | done | — | [diverges](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`columnManagerResetActionPosition()` / `getColumnManagerResetActionPosition()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) + [`ColumnManagerResetActionPosition`](../../../packages/tables/src/Enums/ColumnManagerResetActionPosition.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php), [`Enums/ColumnManagerResetActionPosition`](../../../vendor/filament/tables/src/Enums/ColumnManagerResetActionPosition.php) | done | — | [diverges](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`columnManagerColumns()` / `getColumnManagerColumns()` / `columnManagerMaxHeight()` / `getColumnManagerMaxHeight()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [diverges](#the-item-clips-its-overflow-where-vendors-overhanging-handle-can-spill-a-column) · [semantics](#a-bare-integer-column-count-is-supports-shorthand-and-the-table-tier-hands-it-through-untouched) |
| [`columnManagerWidth()` / `getColumnManagerWidth()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | in progress | — | [detail](#the-width-is-a-css-length-and-the-columns-to-width-match-is-not-ported) |
| [`columnManagerTriggerAction()` / `getColumnManagerTriggerAction()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [diverges](#the-trigger-drops-vendors-modal-footer-machinery-because-the-chrome-is-the-partials) · [has-filters.md](has-filters.md#the-trigger-opens-its-chrome-from-the-client-and-the-count-stays-a-template-chip) |
| [`columnManagerApplyAction()` / `getColumnManagerApplyAction()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [diverges](#the-trigger-drops-vendors-modal-footer-machinery-because-the-chrome-is-the-partials) |
| [`hasToggleableColumns()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#the-column-state-is-re-derived-against-the-definition-on-every-read) |
| [`resolveColumnManagerIcon()` + `ACTIONS_COLUMN_MANAGER`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`FilamentIcon::resolve()` in `getColumnManagerTriggerAction()`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [diverges](#the-table-tiers-icon-manager-arrives-through-a-setter-of-its-own-name) · [tables-bundle.md](tables-bundle.md#an-alias-lands-with-the-component-that-reads-it) |
| `toggleColumnsTriggerAction()`, `getToggleColumnsTriggerAction()`, `columnToggleFormColumns()`, `getColumnToggleFormColumns()`, `columnToggleFormMaxHeight()`, `getColumnToggleFormMaxHeight()`, `columnToggleFormWidth()`, `getColumnToggleFormWidth()` | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | skipped | — | [deprecated for the `columnManager*()` names](../README.md#statuses) |
| [`$tableColumns` / `$tableDeferredColumns` — the applied bag and the client-writable draft](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager::$tableColumns`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [semantics](#the-column-state-is-re-derived-against-the-definition-on-every-read) · [diverges](#the-stored-item-carries-four-keys-where-filaments-carries-seven) |
| [`mountTableColumnManager()` / `afterTableColumnManagerHydrated()` — the two lifecycle hooks](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager::initTableColumnManager()`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [semantics](#the-column-state-is-re-derived-against-the-definition-on-every-read) |
| [`applyTableColumnManager(?array $state = null)` / `resetTableColumnManager()` — the two live actions](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [diverges](#reorderedness-is-asked-not-remembered-so-the-second-session-key-is-gone) |
| [`getDefaultTableColumnState()` / `syncTableColumnState()` / `updateTableColumns()` / `setTableColumns()`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [semantics](#the-column-state-is-re-derived-against-the-definition-on-every-read) · [semantics](#the-order-is-followed-only-while-columns-are-reorderable) |
| [`isTableColumnToggledHidden()`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager::isTableColumnToggledHidden()`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [semantics](#an-absent-name-reads-as-hidden) · [interacts-with-table.md](interacts-with-table.md#a-member-is-on-the-contract-when-something-outside-the-host-trait-calls-it) |
| [`getTableSessionKey('columns')`](../../../packages/tables/src/Concerns/CanPersistTableState.php) — `getTableColumnsSessionKey()` | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| `getHasReorderedTableColumnsSessionKey()` / `persistHasReorderedTableColumns()` | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | skipped | — | [diverges](#reorderedness-is-asked-not-remembered-so-the-second-session-key-is-gone) |
| [`getTableColumnManagerItems()` — the panel's rows](../../../packages/tables/src/Concerns/HasColumnManager.php) | [the `x-for` over `deferredColumns` in `content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php) | done | — | [diverges](#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for) |
| `updatedToggledTableColumns()` / `getToggledTableColumnsSessionKey()` | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | skipped | — | [deprecated for `applyTableColumnManager()` and the columns session key](../README.md#statuses) |
| `getTableColumnToggleFormColumns()` / `getTableColumnToggleFormWidth()` / `getTableColumnToggleFormMaxHeight()` | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | skipped | — | [deprecated: override `table()`](../README.md#statuses) |
| [`ColumnManager` — the panel's chrome](../../../packages/tables/templates/components/ColumnManager/index.html.twig) | [`views/components/column-manager/index.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/index.blade.php) | in progress | — | [detail](#the-columngroup-half-is-the-whole-of-what-is-left) · [diverges](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`ColumnManagerContent` — the item list](../../../packages/tables/templates/components/ColumnManagerContent/index.html.twig) | [`views/components/column-manager/content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php) | in progress | — | [detail](#the-columngroup-half-is-the-whole-of-what-is-left) · [diverges](#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for) · [semantics](#the-checkbox-renders-only-where-there-is-something-to-toggle) |
| [The partial's two branches — the toolbar trigger, the dropdown panel and the modal](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `fi-ta-col-manager` slot](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [has-actions.md](has-actions.md#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content) |
| [`sylament--tables--column-manager` — the draft writer](../../../packages/tables/assets/src/controllers/column_manager_controller.ts) | [`components/column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js) | done | — | [tables-bundle.md](tables-bundle.md#the-assets-register-through-assetmapper-as-stimulus-controllers-not-as-alpine-components) · [diverges](#the-column-manager-controller-drops-vendors-error-isloading-and-watch) · [diverges](#a-hidden-columns-entry-survives-a-drag-where-vendors-rebuild-drops-it) |
| [`column-manager.css`](../../../packages/tables/assets/src/styles/components/column-manager.css) | [`resources/css/column-manager.css`](../../../vendor/filament/tables/resources/css/column-manager.css) | done | — | [diverges](#the-item-list-block-sits-at-top-level-where-vendors-nests-inside-the-panel) |
| Column groups — the group row, its tri-state checkbox, the nested drag list and `toggleGroup()` | [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php), [`content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php), [`column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js) | blocked | `ColumnGroup` ([column.md](column.md#surface)) | [detail](#the-columngroup-half-is-the-whole-of-what-is-left) |

## Details

### The `ColumnGroup` half is the whole of what is left

Filament's manager is a list of two kinds of thing.
A plain column is one row with one checkbox; a [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php) is a row with a **tri-state** checkbox over its children, a nested drag list of its own, and its own `calculateGroupedColumns()` / `getColumn(name, groupName)` / `toggleGroup()` / `reorderGroupColumns()` handlers in `column-manager.js`.
On the host it is seven members of vendor's trait: two that exist only for it (`mapTableColumnGroupToArray()`, `syncGroupFromDefaultTableColumnState()`) and the group branches of five more, `getTableColumnsToggleStateByName()`, `updateTableColumns()` and `flattenTableColumnStateItems()`, all three of which ship here under those names, plus `syncItemFromDefaultTableColumnState()` and `syncItemFromTableColumnState()`, the pair this port collapses into `syncTableColumnStateItems()`.
`ColumnGroup` itself is not ported (it is [`column.md`](column.md#surface)'s grouping-and-layout row), so there is nothing for the group arm to list, and the two components stay **in progress**: the single-column list and chrome are whole, the group row and its nested list are missing.

Two things meet it half way rather than being left to retrofit.
The stored item carries a `type`, which is `'column'` for everything the port produces and `'group'` on read only, so a state written by a later version loads rather than being refused whole by [the normalizer](#the-column-state-is-re-derived-against-the-definition-on-every-read).
And the drag list's sortable ids are `column::<name>`, the same `type::name` shape vendor's `reorderColumns()` splits on, so the controller's `reorder()` already understands a group id and matches it against a `type === 'group'` entry; that branch is forward compatibility, and nothing renders a group id.

Vendor's group arm carries two class names, `fi-ta-col-manager-group` and `fi-ta-col-manager-group-items`, with a nested rule each in `column-manager.css`; their prefix-renamed counterparts are neither emitted nor styled here, and arrive with the group row.

### The width is a CSS length, and the columns-to-width match is not ported

Vendor's `getColumnManagerWidth()` is `Width | string | null` and falls back through a `match` on the column count (2 → `TwoExtraLarge`, 3 → `FourExtraLarge`, 4 → `SixExtraLarge`, else `ExtraSmall`), so it never actually returns null.
Ours is `?string`, a raw CSS length handed to support's `Dropdown` and `Modal`, and it is genuinely nullable: with nothing set, the dropdown takes the `20rem` its own stylesheet declares and the modal takes `md`.

[`Sylament\Support\Enums\Width`](../../../packages/support/src/Enums/Width.php) exists, so nothing blocks the back-fill: it is retyping the property and the setter and porting the column-count match.
It is the same gap [`filtersFormWidth()` records](has-filters.md#the-filters-form-columns-follow-the-layout-the-width-does-not), and the two setters land together.

## Semantics

### The column state is re-derived against the definition on every read

The applied bag `$tableColumns` and the client-writable draft `$tableDeferredColumns` are both **derived** values, never sources of truth.
The definition — the columns `table()` declared — owns identity and toggleability; the stored state owns only the toggle, and the order too while [`reorderableColumns()`](#the-order-is-followed-only-while-columns-are-reorderable) is on.
`syncTableColumnState()` runs that merge at every entry point: the mount hook, the hydrate hook and both live actions.
So a column the definition dropped disappears from the state, a column it added arrives at its declared place, a column that cannot be toggled is forced on however the state reads, and a column whose stored sentinel says it was not toggleable when it was stored is re-seeded from the definition's current default rather than left at a stale choice.

Everything arriving from outside goes through `normalizeTableColumnState()` first — the session, whose contents an earlier definition of this table may have written, and the writable draft, which is a client value like any other.
It refuses a non-list or an item without a string `type` and `name` **whole**, and the caller then falls back to the definition's own state rather than to a partially-trusted one; a non-boolean toggle reads as on.
Any string `type` passes, so a stored state carrying Filament's `group` items still loads rather than being refused whole; nothing here produces one.

The state exists for any column the manager could toggle or move, whether or not the chrome that edits it is rendered: `hasColumnManager()` gates the trigger alone, so `columnManager(false)` over toggleable columns still honours their declared `toggledHiddenByDefault()`.

The mount gate walks **every** column's `isToggleable()` closure, as Filament's does, because `hasToggleableColumns()` is `array_any()` over the declared set.

The two hooks take priorities the rest of the package has to leave free: `#[PostMount(priority: 4)]`, below `CanPersistTableState`'s read at 10 and the filters mount at 20 and above the filters hook at 0, because 5 is [`CanSortRecords::healSeededTableSort()`](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)'s and UX sorts equal priorities with a stable `arsort()`, which would resolve a tie by reflection order over flattened traits; and `#[PostHydrate(priority: -4)]`, which re-derives both bags before any read of either.

### Two `TableColumnState` vocabularies meet on one host, and both are Filament's

`updateTableColumnState()` writes **one editable cell's value** — [`has-columns.md`](has-columns.md#surface) owns it, and the state it names is a record's property.
`getDefaultTableColumnState()`, `syncTableColumnState()`, `normalizeTableColumnState()`, `isReorderedTableColumnState()`, `hasTableColumnState()` and `setTableColumns()` all concern [the column manager's visibility-and-order bag](#the-column-state-is-re-derived-against-the-definition-on-every-read), which is about which columns render at all.
The two have nothing to do with each other, they flatten into the same host class, and the phrase *table column state* means a different thing in each.

The collision is **inherited, not minted**.
Vendor's [`Concerns\HasColumns`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) declares `updateTableColumnState(string $column, string $record, mixed $input)` and vendor's [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) declares `getDefaultTableColumnState()` and `setTableColumns()`; Filament ships both vocabularies on the same host, so preserving its public API means preserving the ambiguity.
Renaming the four coinages this port added (`normalizeTableColumnState`, `isReorderedTableColumnState`, `hasTableColumnState`, `syncTableColumnState` — the last collapsing Filament's `syncStaticColumnsFromTableColumnState()` and `syncReorderableColumnsFromDefaultTableColumnState()`) away from that stem would leave them inconsistent with the two Filament names they sit beside, which reads worse than the ambiguity does.

The disambiguator is the **argument**, not the name: the editable write takes a column name *and a record key*, and every manager member takes a state array or nothing.
A reader arriving at either family from the other should start there.

### An absent name reads as hidden

`isTableColumnToggledHidden()` is `!($map[$name] ?? false)`, so a name the map has never heard of answers **true**.
That is safe by construction rather than by luck: both sync directions leave every declared column in the state before anything can read it, and a column only asks about itself when it is toggleable, which is exactly when it is in the map.
The invariant to preserve is therefore *the state is complete before the first read*, not the default itself.

It is also the shape that makes an unmounted host read as "everything hidden": a test that fetches the host as a plain service skips `#[PostMount]`, leaves `$tableColumns` empty, and gets a `true` that proves nothing about the user's choice.
Mount through a live rig and probe the seeded state before asserting on a toggle.

### The order is followed only while columns are reorderable

`syncTableColumnState()` re-`columns()` the table in the stored order only when `hasReorderableColumns()` is on.
Turning reordering off therefore restores the declared order immediately, without clearing the stored one: the order is still in the session, and turning the setting back on brings it back.

`updateTableColumns()` reads `getDefaultTableColumnState()` **before** it re-columns the table.
The ordering is what keeps the method correct on its own: the default state is memoised on first read, so reading it after the table has been re-columned in the stored order would memoise the *stored* order as the declared one and leave Reset with nothing to restore.
It is defensive rather than load-bearing: the sole caller is `syncTableColumnState()`, whose preceding statement runs `syncTableColumnStateItems()`, and *that* method's first line reads the default.
The memo is therefore always warm by the time the re-columning happens, and deleting the priming read changes no behaviour and fails no test.
It is kept so that a second caller, a direct `updateTableColumns()` on a cold memo, cannot silently invert Reset.

### A bare integer column count is support's shorthand, and the table tier hands it through untouched

`columnManagerColumns()` takes vendor's `int | array | Closure` and `getColumnManagerColumns()` hands it to the items element's `grid()` call as-is.
Support's grid reads a bare integer as *N columns from `lg` up and one below*, so `columnManagerColumns(2)` emits `--cols-lg: 2; --cols-default: 1` rather than `--cols-default: 2`.
The widening is `grid()`'s; the table tier neither performs nor undoes it, which is Filament-exact.

The direction matters too: the items element is a **column**-direction grid (CSS multi-column), not a row grid, which is what makes `break-inside-avoid` and the `-mt-6` / `pt-6` rhythm in the stylesheet do anything at all.

### The checkbox renders only where there is something to toggle

`ColumnManagerContent` renders the `<input>` only when the table has toggleable columns at all, and disables it per row where that particular column is not toggleable.
Vendor gates the same way, and the reason is UX rather than parity: a `reorderableColumns()`-only manager would otherwise show a column of permanently inert checkboxes beside the drag handles.

The flag is computed once in the partial's preamble (`component.hasToggleableColumns`) and threaded through both layouts, so the dropdown and the modal cannot disagree about it.

## Divergences

### The column-manager controller drops vendor's `error`, `isLoading` and `$watch`

[`column_manager_controller.ts`](../../../packages/tables/assets/src/controllers/column_manager_controller.ts) ports vendor's [`components/column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js) over the same value-store mechanism the selection controller uses: a tick or a drag rewrites the **whole** `tableDeferredColumns` array through `component.set(model, value, false)` and repaints the checkboxes from it, so a deferred manager reaches the server only when Apply is pressed and a live one once per change.
Vendor's `error` and `isLoading` fields are dropped: vendor writes them around its `$wire` call and no column-manager template reads them, and UX's own overlay is the failure signal here.
Vendor's `init()` also `$watch`es its `columns` array to re-seed the client draft whenever the applied state changes under it; there is no counterpart because the draft is a **server** prop — `afterTableColumnManagerHydrated()` re-derives `tableDeferredColumns` on every hydrate — so a dirty draft rides the request and survives a morph the user did not ask for, and a successful apply is what replaces it.
Apply and reset stay plain live actions, because the dirty draft rides every request.

### The table tier's icon manager arrives through a setter of its own name

Filament resolves the trigger's glyph through the `FilamentIcon::resolve()` facade.
[`resolveColumnManagerIcon()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) reads the `ACTIONS_COLUMN_MANAGER` alias off an `IconManager` injected through `#[Required] setColumnManagerIconManager()`, the way [`CanReorderRecords`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) and [`CanGroupRecords`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php) take theirs, and falls back to a bare manager, which resolves nothing, for a table built outside the container.
The setter is not named `setIconManager()` because `Table` flattens `CanReorderRecords` into the same class, and a second setter under that name is a trait collision rather than a second injection.
It is not `resolveFiltersIcon()`'s shape either, a closure taking `?IconManager $icons = null` for the Invoker to fill: `IconManager` is a private service and the Invoker asks `service_container`, so `has()` answers false and the parameter takes its `null` default.

### An explicit `columnManager(true)` over nothing editable lists no rows, where Filament's lists every column

`hasTableColumnState()` asks only whether a column could be toggled or moved, so an explicit `columnManager(true)` over columns that are neither seeds no state and lists no rows — a manager over nothing editable is an empty panel here, where Filament's `initTableColumnManager()` returns early only for a table with a columns layout and would list every column with its toggle off.
Widening the gate to `hasColumnManager()` would cost every **plain** table two extra walks of `isToggleable()` over every column, on both hooks, which is the O(N) closure cost the [visible set](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again) already pays twice; the chrome guards on the row count instead.

### The reorderable answer is asked at each use, where Filament memoises it per host

Filament memoises its reorderable answer once per host (`hasReorderableTableColumns()`, a `??=` on a host property); the port asks `Table::hasReorderableColumns()` afresh at each of its four sites, one of them inside the per-column default-state build, so a `reorderableColumns(fn () => …)` closure is evaluated O(N) times per request instead of once.
The memo is droppable because it has no invalidation seam of its own and the table is built once per request; the cost is recorded here rather than paid for with a second uninvalidated memo beside the two `setTableColumns()` already drops.

### The session read ignores `persistColumnsInSession()`, where Filament's honours it

`persistTableColumns()` writes only while `persistsColumnsInSession()` is on, as Filament's does, but `mountTableColumnManager()` reads the stored `columns` entry whatever the flag says.
Filament gates both halves: its `loadTableColumnsFromSession()` returns the default state when the flag is off.
So `persistColumnsInSession(false)` here means "stop writing", not "stop reading", and a state stored before the flag was turned off keeps applying; a case in `TableSessionPersistenceTest` pins that by seeding a key for the opted-out fixture and reading it back.
Nothing chose this deviation: it rests on a reading of Filament's read as ungated, and gating the read in `mountTableColumnManager()` on `persistsColumnsInSession()` would reverse it.

### The trigger drops vendor's modal-footer machinery, because the chrome is the partial's

Vendor's `getColumnManagerTriggerAction()` builds an action carrying six settings this port does not: `modalSubmitAction(false)`, an `extraModalFooterActions()` pair (the apply action plus a hand-made reset), `modalCancelActionLabel()`, `authorize(true)`, an `alpineClickHandler()` on each footer action, and an `extraAttributes(['class' => 'fi-force-enabled'], merge: true)` stamped after the customization closure.
All six exist because the *action* owns the modal upstream: opening the manager is opening that action's modal, so the footer has to be assembled through the action API.

Here the manager's chrome is the [table partial](../../../packages/tables/templates/partials/Table/index.html.twig)'s, exactly as the filters panel beside it is: the dropdown is support's `Dropdown` and the modal is support's `Modal`, both rendered by the partial, with Apply and Reset placed in their footers directly.
So there is no action-owned modal for those settings to configure, and the trigger reduces to what actually opens something: `liveComponentClickHandlerEnabled(false)`, and, under the modal layout, an invoker `command` / `commandfor` pair.
That pair *is* stamped after the customization closure, and for vendor's reason rather than by imitation: `extraAttributes()` replaces unless told to merge, so a closure setting attributes of its own would otherwise leave the modal layout with a trigger that opens nothing.

`authorize(true)` has no counterpart because no authorization layer ships, and `fi-force-enabled` none because nothing here disables an action from the outside.

### The stored item carries four keys where Filament's carries seven

Vendor's state item is `{type, name, label, isHidden, isToggled, isToggleable, isToggledHiddenByDefault}` (plus a `columns` array on a group).
Ours is `{type, name, isToggled, isToggledHiddenByDefault}`.

The three dropped keys are all **derived from the definition**, and the definition is on the server, one `getColumn()` away, at every point that needs them.
Vendor stores them because its manager is an Alpine component reading a client-side array: `label` is what the row prints, `isHidden` is what it filters on, `isToggleable` is what disables its checkbox, and all three are needed on the client because that is where the list is built.
Here [the list is built on the server](#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for), so those three are read fresh off the column each render and never round-trip.

The gain is not brevity.
A stored `label` is a stale copy the moment a label changes; a stored `isHidden`/`isToggleable` is a **client-supplied claim about the definition**, and the draft is writable, so honouring it would let a forged draft say a column is toggleable when it is not.
The four keys that remain are exactly the ones the client is allowed an opinion about (which column, in what order, toggled or not), and even those are re-derived against the definition on every read.

`isToggledHiddenByDefault` stays despite being derivable, because it is not read as a fact about the current definition but as a **sentinel about the stored one**: `null` means *this column was not toggleable when this state was written*, which is what lets a column that has become toggleable since be re-seeded from its declared default rather than left at a stale choice.

### Reorderedness is asked, not remembered, so the second session key is gone

Filament has the same derived test this port uses: `hasReorderedTableColumns()` flattens the stored and declared states to `type:name` lists, narrows the declared one to the columns both know, and asks whether they differ.
What it does with the answer is where the two part company.
With `persistColumnsInSession()` on, its default, vendor **latches** it: `persistHasReorderedTableColumns()` writes `$wasReordered || $this->hasReorderedTableColumns()` into a **second** session entry (`tables.<md5>_has_reordered_columns`), and every later `applyTableColumnManager()` picks its sync direction by reading that entry back rather than by re-asking; with the flag off it re-asks, still OR-ing in `$wasReordered`.
The latch's other half is the **client's**: it never reads the stored value back into the write, so what keeps the entry `true` over an order the test would call unreordered is `$wasReordered`, the Alpine component's own `hasReordered`, set on any drag (`column-manager.js:152`/`:184`) and cleared after a successful apply (`:225`) or when the draft is reset to the applied columns (`:238`).
An apply that carries `false` over a restored order therefore writes `false || false` and lowers it again.

Here the same test is `isReorderedTableColumnState()`, parameterised over a state so it can be asked of the draft as well as of the applied bag, and it is asked afresh at each sync.
That removes three things at once: the second session key, the write that maintains it, and the `$wasReordered` parameter, which is a **client's claim about what the client did** rather than a value.
A draft could assert it without having reordered anything, so dropping it is a small hardening as well as a simplification.

The behaviour difference is the latch.
A user who drags a column, drags it back and applies stays in reordered mode upstream: the client still claims a drag, so the entry is written `true` over an order that is the declared one again, and it stays that way until some later apply carries `false`.
Here the same apply re-derives the answer from the state, which matches the default again, so it reads as unreordered at once.
Both render the declared order that day; what the stale claim costs upstream is the *next* definition change, because reordered mode appends a newly declared column at the end of the stored order where the unreordered path re-derives it into its declared position.
It is a difference and is recorded as one, and it is the reason the port needs no `resetTableColumnManager()` arm to clear a flag.

### The session is written at the end of every mutation, rather than once per request

Vendor's `applyTableColumnManager()` and `resetTableColumnManager()` each end in `persistTableColumns()`, and so do ours, but vendor *also* runs the whole apply from `initTableColumnManager()` on every request, so the session is rewritten once per render whether anything changed or not.
Ours writes on the two live actions and on the mount that seeds the state, and the hydrate hook, the per-request path, writes nothing at all.

The divergence is mechanical: vendor's mount and its apply are one method, so the per-request write is a side effect of reusing it.
Splitting them into a `#[PostMount]` seed and a `#[PostHydrate]` re-derivation makes the write a choice, and writing an unchanged value into the session on every render of every table with a toggleable column is a cost with no reader.

`persistColumnsInSession()` defaults **on**, as Filament's does; the read it does not gate is [a divergence of its own](#the-session-read-ignores-persistcolumnsinsession-where-filaments-honours-it).

### The item list is server-rendered, where vendor's is an Alpine `x-for`

`content.blade.php` iterates `columns` with `x-for` (the client array `$wire.entangle('tableColumns')` seeds from the *applied* bag), while each row's `checked` and `disabled` bindings read the draft, through `getColumn(column.name, null)` off `deferredColumns`.
So every row, its label and its disabled state are produced in the browser, and the row set and its order only change once Apply has written the draft back.
Here `getTableColumnManagerItems()` produces the rows on the host, from `tableDeferredColumns` rather than the applied bag, and the `ColumnManagerContent` component iterates a plain Twig array.
The two therefore render *different* bags, which is the load-bearing half of this divergence: a dragged row moves in our list on the next render and in vendor's only after Apply.

That is the same trade the rest of the package makes (the selection banner, the editable cells): the server is canonical, the controller repaints.
Concretely, the list is the draft's order, filtered to the columns the definition still declares and the developer has not `hidden()`, each row carrying the column's *current* label and toggleability read off the definition, none of which the client could be trusted with.
What the controller still owns is the part that must not wait for a round trip: the checkbox's `checked` state, repainted from the draft on `connect()` and on every `render:finished`, because UX pins a touched input through a morph and the server-rendered `checked` attribute stops winning after the first one.

The list is published through [the contract](interacts-with-table.md#a-member-is-on-the-contract-when-something-outside-the-host-trait-calls-it) rather than reached off the trait: the partial reads it off `getLiveComponent`, which is typed `InteractsWithTable`.

### A hidden column's entry survives a drag, where vendor's rebuild drops it

Vendor's `reorderTopLevel()` builds a fresh array from the ids the drag control reported and assigns it over `deferredColumns`.
Anything the list did not render (a column the developer `hidden()`, so it never had a row to drag) is absent from those ids and therefore **falls out of the state entirely**, taking its stored toggle with it; the next sync re-adds it from the *definition*, so whatever the user had chosen for it is silently reset.

`reorder()` here appends the unreported entries after the reported ones instead of dropping them, so the entry survives the drag with its stored `isToggled` and `isToggledHiddenByDefault` intact.
The **order** is not the difference: both ends leave such an entry at the back, ours because `reorder()` appends it there and vendor's because [`getNewDefaultColumnStateItems()`](../../../packages/tables/src/Concerns/HasColumnManager.php)'s counterpart re-appends the missing default at the tail.
It is a fix rather than a translation, and it is recorded here rather than in the [bundle's controller notes](tables-bundle.md#the-assets-register-through-assetmapper-as-stimulus-controllers-not-as-alpine-components) because it changes what the *state* means rather than how the controller is wired.

### The modal layout renders one Reset, wherever the setting puts it

`ColumnManagerResetActionPosition` ships whole and is honoured whole in the **dropdown** layout: `Header` puts a `Link` beside the heading, `Footer` puts a `Button` in the actions row.
In the **modal** layout the partial renders the panel's items alone (`ColumnManagerContent`, not `ColumnManager`) and puts Apply and an unconditional Reset `Button` in the modal's own footer, so the setting has no effect there.

This is the filters modal's shape one component over, and deliberately so: a modal already has a footer, and a heading-row Reset inside a dialog whose own footer is empty reads as a mistake.
Vendor reaches the same place by a different route (its modal *is* the trigger action's, with the footer assembled through `extraModalFooterActions()`), so upstream the setting is equally inert under the modal layout.
The divergence is in the mechanism, not the outcome.

### The item-list block sits at top level, where vendor's nests inside the panel

`column-manager.css` nests `.sy-ta-col-manager-header`, `.sy-ta-col-manager-heading` and `.sy-ta-col-manager-actions-ctn` under `.sy-ta-col-manager` as vendor does, but hoists `.sy-ta-col-manager-items` and everything under it to top level, where vendor keeps it nested.

The reason is the [modal layout](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it): it renders `ColumnManagerContent` **without** the `.sy-ta-col-manager` wrapper, so a nested rule would leave every item in the modal unstyled, with no `break-inside-avoid`, no `-mt-6` and no gap.
The rest of the sheet is vendor's, prefix-renamed, minus the group arm's two rules, plus two Sylament-only additions: the `.sy-ta-col-manager-dropdown` block, which carries the `20rem` default width because support's `Dropdown` has no width of its own to default, and the item's guarded `overflow: clip`, [recorded below](#the-item-clips-its-overflow-where-vendors-overhanging-handle-can-spill-a-column).

### The item clips its overflow, where vendor's overhanging handle can spill a column

With `columnManagerColumns(2)` and five items, vendor's stylesheet lays the list out in **three** column positions and puts the third outside the panel: off the screen at a normal viewport, its control unreachable.
The spill is inherited rather than mis-ported (the `-items` and `-item` rules, the `->grid($columns, GridDirection::Column)` call and the emitted `column-count: 2` are all vendor's), and the fix is one rule.

The items element is a CSS multi-column box balancing `break-inside: avoid` items 44px tall: the `pt-6` rhythm over a 20px line.
The reorder handle in each is support's `IconButton`, which carries Filament's own negative margins (`-m-1.5` at its small size), so a 32px button sits in a 20px content box and overhangs the item by 6px above, below and to the right.
Chromium's balancer counts that overhang against `break-inside: avoid`, and with it three items do not fit the column height it settles on: two go in each column and the fifth opens an overflow column past the panel.
A synthetic five-item list without the overhang balances 3 + 2 in the same browser, one whose child carries negative vertical margins reproduces the spill exactly, and `column-fill: auto` does not fix it but collapses the list to one column.

So `.sy-ta-col-manager-item` sets `overflow: clip` with `overflow-clip-margin: 10px`.
The clip takes the overhang out of the item's overflow and the balancer fits three per column again at the same 44px row; the clip margin keeps the handle's 6px overhang and its 2px focus ring painted, so nothing visible changes, where zeroing the handle's margin instead would grow every row to 56px.
The rule sits behind `@supports (overflow-clip-margin: 1px)`: an engine with `overflow: clip` but without the margin property would clip the handle's hover face and ring at the row edge, so it keeps vendor's layout instead.
Nothing in the PHP suites can pin a layout, so the rule rests on a browser check: two columns, nothing outside, in the dropdown and the modal alike, with the one-column default untouched.
