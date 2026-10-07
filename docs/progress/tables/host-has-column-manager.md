---
description: 'The host trait `HasColumnManager` that `HasTable` composes: the applied and draft column bags and how they are re-derived. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/HasColumnManager.php
owns:
  - packages/tables/src/Concerns/HasColumnManager.php
---
# Concerns\HasColumnManager (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The column manager — the two column bags, `mountTableColumnManager()`, `afterTableColumnManagerHydrated()`, `applyTableColumnManager()`, `resetTableColumnManager()`, `getDefaultTableColumnState()`, `isTableColumnToggledHidden()`, `getTableColumnManagerItems()`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | in progress | — | [column-manager.md](column-manager.md) · [detail](#the-column-state-is-re-derived-against-the-definition-on-every-read) |

## Details

### The column state is re-derived against the definition on every read

The applied bag `$tableColumns` and the client-writable draft `$tableDeferredColumns` are both **derived** values, never sources of truth.
The definition — the columns `table()` declared — owns identity and toggleability; the stored state owns only the toggle, and the order too while [`reorderableColumns()`](table.md#surface) is on.
`syncTableColumnState()` runs that merge at every entry point: the mount hook, the hydrate hook and both live actions.
So a column the definition dropped disappears from the state, a column it added arrives at its declared place, a column that cannot be toggled is forced on however the state reads, and a column whose stored sentinel says it was not toggleable when it was stored is re-seeded from the definition's current default rather than left at a stale choice.

Everything arriving from outside goes through `normalizeTableColumnState()` first — the session, whose contents an earlier version of this table wrote, and the writable draft, which is a client value like any other.
It refuses a non-list or an item without a string `type` and `name` **whole**, and the caller then falls back to the definition's own state rather than to a partially-trusted one; a non-boolean toggle reads as on.
The stored shape carries a `type` at all because Filament's manager lists column *groups* beside columns — `group` is honoured on read so a state a later version writes still loads, and is never produced here.

The state exists for any column the manager could toggle or move, whether or not the chrome that edits it is rendered: `hasColumnManager()` gates the trigger alone, so `columnManager(false)` over toggleable columns still honours their declared `toggledHiddenByDefault()`.
The chrome that edits it — the manager's two Twig components, its Stimulus controller, its stylesheet and the partial's two branches — is tracked with the rest of the feature on [`column-manager.md`](column-manager.md).

The other direction of that gate is a **divergence**, and a deliberate one.
`hasTableColumnState()` asks only whether a column could be toggled or moved, so an explicit `columnManager(true)` over columns that are neither seeds no state and lists no rows — a manager over nothing editable is an empty panel here, where Filament's `initTableColumnManager()` gates on `hasColumnsLayout()` alone and would list every column with its toggle off.
Widening the gate to `hasColumnManager()` would cost every **plain** table two extra walks of `isToggleable()` over every column, on both hooks, which is the O(N) closure cost the [visible set](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again) already pays twice; the chrome guards on the row count instead.

Two costs are carried rather than optimised away, both Filament-exact.
The mount gate walks **every** column's `isToggleable()` closure, since `hasToggleableColumns()` is `array_any()` over the declared set and no cheaper gate was substituted for it.
And Filament memoises its reorderable answer once per host (`hasReorderableTableColumns()`, a `??=` on a host property); the port asks `Table::hasReorderableColumns()` afresh at each of its four sites, one of them inside the per-column default-state build, so a `reorderableColumns(fn () => …)` closure is evaluated O(N) times per request instead of once.
The memo is droppable because it has no invalidation seam of its own and the table is built once per request; the cost is recorded here rather than paid for with a second uninvalidated memo beside the two `setTableColumns()` already drops.

The session **read** is ungated while the **write** is gated, unlike the three sibling flags on [`CanPersistTableState`](can-persist-table-state.md#surface), which gate each read.
This is Filament's own shape — vendor's `loadTableColumnsFromSession()` is called unconditionally and only `persistTableColumns()` consults the flag — so `persistColumnsInSession(false)` means "stop writing", not "stop reading", and a state stored before the flag was turned off keeps applying.
Kept for parity and pinned by a case that seeds a key for the opted-out fixture and reads it back; gating the read is a behaviour change rather than a bug fix, and belongs with whoever wants it.

The two hooks take priorities the rest of the package has to leave free: `#[PostMount(priority: 4)]`, below `CanPersistTableState`'s read at 10 and above the filters mount at 0, because 5 is [`CanSortRecords::healSeededTableSort()`](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)'s and UX sorts equal priorities with a stable `arsort()`, which would resolve a tie by reflection order over flattened traits; and `#[PostHydrate(priority: -4)]`, which re-derives both bags before any read of either.

## Semantics

### Two `TableColumnState` vocabularies meet on one host, and both are Filament's

`updateTableColumnState()` writes **one editable cell's value** — [`has-columns.md`](has-columns.md#surface) owns it, and the state it names is a record's property.
`getDefaultTableColumnState()`, `syncTableColumnState()`, `normalizeTableColumnState()`, `isReorderedTableColumnState()`, `hasTableColumnState()` and `setTableColumns()` all concern [the column manager's visibility-and-order bag](#the-column-state-is-re-derived-against-the-definition-on-every-read), which is about which columns render at all.
The two have nothing to do with each other, they flatten into the same host class, and the phrase *table column state* means a different thing in each.

The collision is **inherited, not minted**.
Vendor's [`Concerns\HasColumns`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) declares `updateTableColumnState(string $column, string $record, mixed $input)` and vendor's [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) declares `getDefaultTableColumnState()` and `setTableColumns()`; Filament ships both vocabularies on the same host, so preserving its public API means preserving the ambiguity.
Renaming the four coinages this port added (`normalizeTableColumnState`, `isReorderedTableColumnState`, `hasTableColumnState`, `syncTableColumnState` — the last collapsing Filament's `syncStaticColumnsFromTableColumnState()` and `syncReorderableColumnsFromDefaultTableColumnState()`) away from that stem would leave them inconsistent with the two Filament names they sit beside, which reads worse than the ambiguity does.

The disambiguator is the **argument**, not the name: the editable write takes a column name *and a record key*, and every manager member takes a state array or nothing.
A reader arriving at either family from the other should start there.
