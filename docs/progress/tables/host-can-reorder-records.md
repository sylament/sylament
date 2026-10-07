---
description: 'The host trait `CanReorderRecords` that `HasTable` composes: the reorder mode flag, its toggle and the `reorderTable()` write. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/CanReorderRecords.php
owns:
  - packages/tables/src/Concerns/CanReorderRecords.php
---
# Concerns\CanReorderRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Reordering — `$isTableReordering` / `toggleTableReordering()` / `isTableReordering()` / `reorderTable()`](../../../packages/tables/src/Concerns/CanReorderRecords.php) | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | done | — | [semantics](#reordertable-is-gated-by-reorderable-never-by-the-reorder-mode) · [semantics](#a-refused-key-leaves-a-gap-in-the-numbering-and-a-smuggled-one-does-not) · [diverges](#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) · [table.md](table.md#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) |
| `makeTableReorderColumnExpression()` — the mass `CASE` expression | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | skipped | — | [diverges](#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) |
| `reorderTable()`'s `BelongsToMany` pivot-column arm | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | skipped | — | It reorders a position column that lives on the **pivot** rather than on the record, keyed by the pivot class under `allowsDuplicates()`. The pivot surface is skipped whole on Doctrine grounds — an association table carrying payload is an entity of its own, so there is no pivot query to reorder and no pivot class to key by ([table.md](table.md#surface), [renames.md](../renames.md#renames)) — and a join table with a position column is reordered here as the entity it is |
| The deprecated host reorder shims — `isTablePaginationEnabledWhileReordering()`, `getTableReorderColumn()` | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | skipped | — | Both are documented "Override the `table()` method" bridges, and the project has never ported a deprecated Filament surface; what they front is [`Table\Concerns\CanPaginateRecords::isPaginatedWhileReordering()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) and [`Table\Concerns\CanReorderRecords::getReorderColumn()`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) |

The reorder half needed one rig change to be testable at all: the [`Song`](../../../packages/tables/tests/Fixtures/Doctrine/Entity/Song.php) fixture gained a **nullable** `int $position` field with its accessor pair, so the drag write has a mapped column to assign onto and a fresh row starts unnumbered — every other fixture entity is unchanged, and no existing case reads the field.

## Semantics

### `reorderTable()` is gated by `reorderable()`, never by the reorder mode

The live action's only guard is `$table->isReorderable()` — it does **not** ask `isTableReordering()`, so a client that posts an order without ever flipping the mode still writes.
That reads like a missing check and is Filament-exact: vendor's `reorderTable()` opens with the same single `if (! $this->getTable()->isReorderable())` and nothing else.
The reason it is safe is that the mode flag is chrome, not authority — `reorderable()` is where a table says reordering is allowed at all, and `authorizeReorder()` is the arm that will carry a policy once authorization lands.
Adding the mode to the gate would buy nothing an attacker could not get by calling `toggleTableReordering()` first.

The flag is re-gated from the other direction, and that half is ours: `isTableReordering()` answers `$this->getTable()->isReorderable() && $this->isTableReordering`, so a mode left standing on a table that has since lost its reorder column reports false and every branch reading it — the handle column, the drag mount, the suppressed sort headers, the emptied action sets — goes back to normal without a write.
Filament's `isTableReordering()` composes the same two, so this is parity too; what is worth saying is that the flag alone is never trusted anywhere.

### A refused key leaves a gap in the numbering, and a smuggled one does not

`reorderTable()` numbers by array position — `$index + 1` over the submitted order, reversed first when `getReorderDirection()` is `desc` — and the two ways a key can fail to produce a record land on opposite sides of that numbering.

A key the **query does not answer for** (deleted since the drag, filtered out by a `modifyQueryUsing()` scope, or simply invented) is skipped inside the loop, so its slot is consumed and the positions either side of it differ by two.
Filament's mass `CASE` update behaves identically for the same reason: its `WHEN` arms are built from `$recordIndex + 1` over the same array, and the arm for a key with no row matches nothing.
A gap is therefore parity, not a defect, and reordering is position-ordered rather than position-contiguous by design — the next drag renumbers from 1 regardless.

A key that is **not a printable scalar** never reaches the loop: the bag is filtered (`is_scalar($key) && ! is_bool($key)`) and `array_values()`-compacted *before* numbering, so a smuggled array or object shifts every later key up one and leaves no gap.
That compaction is ours — vendor hands `$order` straight to `$connection->escape()` — and it is the shape the `#[LiveArg] array $order` edge needs, since the keys are client-supplied.

## Divergences

### The reorder write is a unit-of-work flush, not one mass `CASE` update

Filament writes a whole reorder in a single statement: a `DB::transaction()` around one `UPDATE … SET position = case when key = 'a' then 1 when key = 'b' then 2 … end` over a `whereIn` of the submitted keys, assembled by `makeTableReorderColumnExpression()` with the values escaped through the connection's grammar.
Ours resolves the keys to managed entities through the table's own query, writes each position with the [property accessor](../schemas/property-accessor.md), and calls `flush()` once.

The shape is Doctrine's rather than a preference.
A raw mass `UPDATE` bypasses the unit of work, so entities the request already loaded would keep their stale positions, `@PreUpdate`/`@PostUpdate` lifecycle callbacks would not fire, and an optimistic-lock `@Version` column would not increment — three things a Doctrine application is entitled to assume happen when a mapped field changes.
One flush still sends one transaction, so the atomicity Filament's explicit `DB::transaction()` buys is not lost; what changes is that N rows cost N `UPDATE` statements rather than one.
N is not the page size: reordering is **unpaginated by default** (`CanPaginateRecords::$isPaginatedWhileReordering` is `false`, and `HasTable::resolveTableRecords()` skips the paginator while the table is reordering), so the submitted order is the whole filtered table unless `paginatedWhileReordering()` is on.
The trade is still worth it, and the read that resolves those keys back to entities is chunked for the same reason — see [the chunk rule](../schemas/record-key-contract.md#the-chunk-rule).

Two smaller members go with it.
Vendor strips the reorder column to its last dotted segment (`str($column)->afterLast('.')`) because the pivot arm below needs a bare column name; ours resolves the *whole* path through the accessor, so `reorderable('meta.position')` writes into an embeddable or a value object rather than being silently truncated to `position`.
And Filament's second parameter `$draggedRecordKey` is [not ported](../renames.md#renames) — vendor declares it and reads it nowhere in the body — so the live action takes the order alone.
