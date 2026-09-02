# HasColumns (tables host)

[`Sylament\Tables\Concerns\HasColumns`](../../../packages/tables/src/Concerns/HasColumns.php)
— the **host**-tier column concern
[`HasTable`](has-table.md) composes: the live actions a cell's own controls
fire back into. It ports Filament's
[`Tables\Concerns\HasColumns`](../../../vendor/filament/tables/src/Concerns/HasColumns.php).

Do not confuse it with
[`Sylament\Tables\Table\Concerns\HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php),
the **table**-tier trait of the same name that owns `columns()` / `getColumns()` /
`getColumn()` and folds into [`table.md`](table.md). Filament ships both names in
both tiers and the port keeps them; read the import, never the bare `use`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Concerns/HasColumns.php)
  — `callTableColumnAction()`, `updateTableColumnState()`, `callTableColumnMethod()`
  and the deprecated `getTableColumns()`

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries up to three links: **detail** → the write-up in [Details](#details),
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`callTableColumnAction(string $name, string $recordKey)` — run a column's `action()` closure for one record](../../../packages/tables/src/Concerns/HasColumns.php) | [`HasColumns::callTableColumnAction()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | done | — | [diverges](#a-live-action-answers-nothing-so-the-return-value-is-dropped) · [detail](#the-hidden-column-gate-waits-on-column-visibility) |
| `updateTableColumnState(string $column, string $record, mixed $input)` — the editable columns' write-back | [`HasColumns::updateTableColumnState()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | todo | the editable columns (M4 · 4) | — |
| `callTableColumnMethod()` — dispatch to an `#[ExposedLivewireMethod]` on a column | [`HasColumns::callTableColumnMethod()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | blocked | the select-engine promotion ([forms-bundle.md](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host)) | Its only Filament consumer is `SelectColumn`'s searchable arm, which ships native-only |
| `getTableColumns()` | [`HasColumns::getTableColumns()`](../../../vendor/filament/tables/src/Concerns/HasColumns.php) | skipped | — | The whole method carries `@deprecated Override the \`table()\` method`, and the port has never landed a deprecated Filament surface — [renames.md](../renames.md) |
| _rest not audited yet_ | | | | |

`callTableColumnAction()`'s five arms are covered by
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php),
which calls the live action on a real live host over the wire and reads what the
bound closure recorded off a `LiveProp`: the record and its key reaching the
closure, an unknown record key, an unknown column name, a column carrying no
action at all, and the closure a `string` action name was wrapped into calling
its host method. The wiring that fires it — the cell wrapper's `button` arm
choosing `callTableColumnAction` over `mountAction` by
[`Column::getActionKind()`](column.md) — is pinned in
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php).

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The hidden-column gate waits on column visibility

Filament refuses the call when `$column->isHidden()` — a column the user cannot
see must not be reachable from the wire, whatever the client posts. `hidden()`,
`visible()` and `toggleable()` are unported (their rows are on
[`column.md`](column.md)), so there is no predicate to ask and the guard is
absent rather than stubbed true. It lands with `CanBeHidden`, in the same change
that gives `updateTableColumnState()` its copy of the same gate.

Nothing else is missing from the resolution order, and the order itself is
vendor's: the record resolves **first**, through
[`getTableRecord()`](has-table.md), so the table's filters are re-applied and a
record the user cannot reach is simply absent; then the column by name off the
table; then the action, which must be a `Closure` — an `Action` instance mounts
through the modal lane instead and a `null` action does nothing. Each refusal
returns without effect, exactly as vendor's returns `null`.

## Divergences

### A live action answers nothing, so the return value is dropped

Filament's `callTableColumnAction()` returns whatever the column's closure
returned, and Livewire hands that back to the browser. UX LiveComponent has no
such channel: `component.action()` resolves to a `BackendResponse`, never the
PHP return value, so a returned value would be unreachable by construction. The
port therefore types the live action `void` and the closure's own return is
discarded — the re-render is the whole answer, which is the same rule the rest of
the toolkit's live actions follow.

A closure that wants to change what the user sees writes host state (a
`LiveProp`, the record through Doctrine) and lets the re-render show it, rather
than returning something.
