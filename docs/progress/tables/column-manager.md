# Column manager (tables)

The chrome that lets a **user** hide, show and reorder a table's columns, and
the state behind it. It spans both tiers and every layer of the package:
[`Sylament\Tables\Table\Concerns\HasColumnManager`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php)
carries the settings and the two chrome actions,
[`Sylament\Tables\Concerns\HasColumnManager`](../../../packages/tables/src/Concerns/HasColumnManager.php)
carries the state on the host, two `Enums\` cases pick the layout and the reset
position, two Twig components and the
[table partial](../../../packages/tables/templates/partials/Table/index.html.twig)'s
two branches render it, one Stimulus controller writes the draft, and one
stylesheet skins it. It ports Filament's pair of traits of the same names, its
two enums, its `column-manager/` view pair, its `column-manager.js` and its
`column-manager.css`.

The **developer**'s half of visibility is a different feature and is tracked
elsewhere: [`hidden()` / `visible()`](column.md#surface) and the responsive
[`hiddenFrom()` / `visibleFrom()`](column.md#surface) are the column's own, and
[`Table::getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again)
is what folds both halves into the one set the partial and the query loops read.
This file is only the manager: the state a user's choices live in, and the panel
that edits it.

Two traits of the same short name are in play here and
[two more](has-columns.md) elsewhere in the package — read the **import**, never
the bare `use HasColumnManager;`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses). What holds it back is
[`ColumnGroup`](#the-columngroup-half-is-the-whole-of-what-is-left): vendor's
manager lists column *groups* beside columns, with a tri-state group checkbox and
a nested drag list, and none of that is ported.

Reference — the Filament source this is diffed against:

- [`tables/src/Table/Concerns/HasColumnManager.php`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php)
  — the settings, the eight deprecated aliases and the two chrome actions
- [`tables/src/Concerns/HasColumnManager.php`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php)
  — the host state, its two sync directions and the session
- [`tables/src/Enums/ColumnManagerLayout.php`](../../../vendor/filament/tables/src/Enums/ColumnManagerLayout.php),
  [`tables/src/Enums/ColumnManagerResetActionPosition.php`](../../../vendor/filament/tables/src/Enums/ColumnManagerResetActionPosition.php)
- [`tables/resources/views/components/column-manager/index.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/index.blade.php)
  and [`content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php)
- [`tables/resources/js/components/column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js)
  — the Alpine data the Stimulus controller replaces
- [`tables/resources/css/column-manager.css`](../../../vendor/filament/tables/resources/css/column-manager.css)

## Surface

Every point of the feature, across both tiers. _Blocked by_ names what has to
land first. _Detail_ carries up to three links: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). A **skipped** row states its reason inline
instead.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columnManager()` / `hasColumnManager()` — an explicit answer, else derived from what there is to edit](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#the-state-exists-for-the-columns-not-for-the-chrome) |
| [`deferColumnManager()` / `hasDeferredColumnManager()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | — |
| [`reorderableColumns()` / `hasReorderableColumns()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#the-order-is-followed-only-while-columns-are-reorderable) |
| [`persistColumnsInSession()` / `persistsColumnsInSession()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [has-table.md](has-table.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [diverges](#the-session-is-written-at-the-end-of-every-mutation-rather-than-once-per-request) |
| [`columnManagerLayout()` / `getColumnManagerLayout()` / `hasColumnManagerDropdown()` / `hasColumnManagerModal()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) + [`ColumnManagerLayout`](../../../packages/tables/src/Enums/ColumnManagerLayout.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php), [`Enums/ColumnManagerLayout`](../../../vendor/filament/tables/src/Enums/ColumnManagerLayout.php) | done | — | [diverges](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`columnManagerResetActionPosition()` / `getColumnManagerResetActionPosition()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) + [`ColumnManagerResetActionPosition`](../../../packages/tables/src/Enums/ColumnManagerResetActionPosition.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php), [`Enums/ColumnManagerResetActionPosition`](../../../vendor/filament/tables/src/Enums/ColumnManagerResetActionPosition.php) | done | — | [diverges](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`columnManagerColumns()` / `getColumnManagerColumns()` / `columnManagerMaxHeight()` / `getColumnManagerMaxHeight()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#a-bare-integer-column-count-is-supports-shorthand-and-the-table-tier-hands-it-through-untouched) |
| [`columnManagerWidth()` / `getColumnManagerWidth()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | in progress | the `Width` enum ([table.md](table.md#the-filters-form-columns-follow-the-layout-the-width-does-not)) | [diverges](#the-width-is-a-css-length-and-the-columns-to-width-match-is-not-ported) |
| [`columnManagerTriggerAction()` / `getColumnManagerTriggerAction()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [diverges](#the-trigger-drops-vendors-modal-footer-machinery-because-the-chrome-is-the-partials) · [table.md](table.md#the-trigger-opens-its-chrome-from-the-client-and-the-count-stays-a-template-chip) |
| [`columnManagerApplyAction()` / `getColumnManagerApplyAction()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [diverges](#the-trigger-drops-vendors-modal-footer-machinery-because-the-chrome-is-the-partials) |
| [`hasToggleableColumns()`](../../../packages/tables/src/Table/Concerns/HasColumnManager.php) | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [semantics](#hastoggleablecolumns-walks-every-columns-closure-at-mount) |
| [`resolveColumnManagerIcon()` + `ACTIONS_COLUMN_MANAGER`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`FilamentIcon::resolve()` in `getColumnManagerTriggerAction()`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | done | — | [table.md](table.md#the-column-managers-table-tier-ships-whole-and-the-chrome-around-it-now-does-too) · [tables-bundle.md](tables-bundle.md#the-alias-set-is-thirteen-constants-of-filaments-23) |
| The eight deprecated aliases — `toggleColumnsTriggerAction()`, `getToggleColumnsTriggerAction()`, `columnToggleFormColumns()`, `getColumnToggleFormColumns()`, `columnToggleFormMaxHeight()`, `getColumnToggleFormMaxHeight()`, `columnToggleFormWidth()`, `getColumnToggleFormWidth()` | [`Table\Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | skipped | — | Each carries `@deprecated Use \`columnManager*()\` instead.` and is a one-line delegate to the name that ships; the project has never ported a deprecated Filament surface ([renames.md](../renames.md#renames)) |
| [`$tableColumns` / `$tableDeferredColumns` — the applied bag and the client-writable draft](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager::$tableColumns`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [has-table.md](has-table.md#the-column-state-is-re-derived-against-the-definition-on-every-read) · [diverges](#the-stored-item-carries-four-keys-where-filaments-carries-seven) |
| [`mountTableColumnManager()` / `afterTableColumnManagerHydrated()` — the two lifecycle hooks](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager::initTableColumnManager()`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [has-table.md](has-table.md#the-column-state-is-re-derived-against-the-definition-on-every-read) |
| [`applyTableColumnManager(?array $state = null)` / `resetTableColumnManager()` — the two live actions](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [diverges](#reorderedness-is-asked-not-remembered-so-the-second-session-key-is-gone) |
| [`getDefaultTableColumnState()` / `syncTableColumnState()` / `updateTableColumns()` / `setTableColumns()`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [has-table.md](has-table.md#the-column-state-is-re-derived-against-the-definition-on-every-read) · [semantics](#the-order-is-followed-only-while-columns-are-reorderable) |
| [`isTableColumnToggledHidden()`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager::isTableColumnToggledHidden()`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | done | — | [semantics](#an-absent-name-reads-as-hidden) · [has-table.md](has-table.md#the-contract-is-twenty-six-methods-of-filaments-forty-six) |
| [`getTableColumnManagerItems()` — the panel's rows](../../../packages/tables/src/Concerns/HasColumnManager.php) | [the `x-for` over `deferredColumns` in `content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php) | done | — | [diverges](#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for) |
| `updatedToggledTableColumns()` and the four deprecated host members beside it — `getToggledTableColumnsSessionKey()`, `getTableColumnToggleFormColumns()`, `getTableColumnToggleFormWidth()`, `getTableColumnToggleFormMaxHeight()` | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | skipped | — | The first is a deprecated delegate to `applyTableColumnManager()`; the next is `@deprecated Use \`getTableColumnManagerSessionKey()\` instead.` and fronts a key [`CanPersistTableState`](../../../packages/tables/src/Concerns/CanPersistTableState.php) mints here; the last three carry `@deprecated Override the \`table()\` method` and front settings that ship on the table object as `columnManagerColumns()` / `columnManagerWidth()` / `columnManagerMaxHeight()`. The project has never ported a deprecated Filament surface ([renames.md](../renames.md#renames)) |
| [`ColumnManager` — the panel's chrome](../../../packages/tables/templates/components/ColumnManager/index.html.twig) | [`views/components/column-manager/index.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/index.blade.php) | in progress | [`ColumnGroup`](#the-columngroup-half-is-the-whole-of-what-is-left) | [diverges](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it) |
| [`ColumnManagerContent` — the item list](../../../packages/tables/templates/components/ColumnManagerContent/index.html.twig) | [`views/components/column-manager/content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php) | in progress | [`ColumnGroup`](#the-columngroup-half-is-the-whole-of-what-is-left) | [diverges](#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for) · [semantics](#the-checkbox-renders-only-where-there-is-something-to-toggle) |
| [The partial's two branches — the toolbar trigger, the dropdown panel and the modal](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `fi-ta-col-manager` slot](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [table.md](table.md#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content) |
| [`sylament--tables--column-manager` — the draft writer](../../../packages/tables/assets/src/controllers/column_manager_controller.ts) | [`components/column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js) | done | — | [tables-bundle.md](tables-bundle.md#asset-registration) · [diverges](#a-hidden-columns-entry-survives-a-drag-where-vendors-rebuild-drops-it) |
| [`column-manager.css`](../../../packages/tables/assets/src/styles/components/column-manager.css) | [`resources/css/column-manager.css`](../../../vendor/filament/tables/resources/css/column-manager.css) | done | — | [diverges](#the-item-list-block-sits-at-top-level-where-vendors-nests-inside-the-panel) |
| Column groups — the group row, its tri-state checkbox, the nested drag list and `toggleGroup()` | [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php), [`content.blade.php`](../../../vendor/filament/tables/resources/views/components/column-manager/content.blade.php), [`column-manager.js`](../../../vendor/filament/tables/resources/js/components/column-manager.js) | todo | — | [detail](#the-columngroup-half-is-the-whole-of-what-is-left) |

Twenty rows are **done**, three **in progress**, two **skipped** and one
**todo**. The done set is the whole single-column feature end to end: a table
declaring `toggleable()` on any column, or `reorderableColumns()`, gets a
toolbar trigger, a panel in either layout, a per-column checkbox and a drag
handle, an Apply button while the manager defers and a Reset in either position,
the choice remembered in the session, and the columns themselves dropped from
the header, from every body row and from the query loops that would have joined
or aggregated for them.

Coverage splits by tier, and each tier has one file.
[`Unit/Table/Concerns/HasColumnManagerTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasColumnManagerTest.php)
covers the settings, the derivation, the two actions and the icon fallback;
[`Unit/Concerns/HasColumnManagerTest`](../../../packages/tables/tests/Unit/Concerns/HasColumnManagerTest.php)
covers the sync algebra — a new column merged in at its declared place, a
vanished one dropped, a stored toggle surviving, the definition winning
toggleability, the forced-on arm for a non-toggleable column, the null-sentinel
re-seed, the reorder order kept only under `reorderableColumns()` and the reset
restoring the declared order — plus every arm of the normalizer against a forged
draft. What neither can reach is the round trip, so
[`Integration/ColumnManagerLiveTest`](../../../packages/tables/tests/Integration/ColumnManagerLiveTest.php)
drives the two live fixtures
([`ColumnManagerTable`](../../../packages/tables/tests/Fixtures/Twig/Components/ColumnManagerTable.php),
deferred; [`LiveColumnManagerTable`](../../../packages/tables/tests/Fixtures/Twig/Components/LiveColumnManagerTable.php),
live plus `reorderableColumns()`) over the wire, and the render half sits in
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php)
beside the rest of the partial's chrome. The session half round-trips in
[`TableSessionPersistenceTest`](../../../packages/tables/tests/Integration/TableSessionPersistenceTest.php),
in both polarities of `persistColumnsInSession()`.

One live-rig trap is worth carrying, because it makes a test pass for the wrong
reason. [`isTableColumnToggledHidden()` answers `true` for a name it has never
heard of](#an-absent-name-reads-as-hidden), so a test that fetches the host as a
plain service skips `#[PostMount]`, leaves `$tableColumns` empty, and gets a
`true` that proves nothing about the user's choice. Mount through a live rig and
probe the seeded state before asserting on a toggle.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The `ColumnGroup` half is the whole of what is left

Filament's manager is a list of two kinds of thing. A plain column is one row
with one checkbox; a
[`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php) is a
row with a **tri-state** checkbox over its children, a nested drag list of its
own, and its own `calculateGroupedColumns()` / `getColumn(name, groupName)` /
`toggleGroup()` / `reorderGroupColumns()` handlers — roughly
half of `column-manager.js`'s 241 lines are that arm, and seven members of
vendor's host trait: two that exist only for it
(`mapTableColumnGroupToArray()`, `syncGroupFromDefaultTableColumnState()`) and
the group branches of five more — `getTableColumnsToggleStateByName()`,
`updateTableColumns()` and `flattenTableColumnStateItems()`, all three of which
ship here under those names, plus `syncItemFromDefaultTableColumnState()` and
`syncItemFromTableColumnState()`, the pair this port collapsed into
`syncTableColumnStateItems()`. `ColumnGroup` itself is not ported
(it is [`column.md`](column.md#surface)'s grouping-and-layout row), so there is
nothing here for the group arm to list, and the three rows it would touch — the
two components and the enum-free item list — stay **in progress** rather than
**done**: the [status vocabulary](../README.md#statuses) reserves **done** for a
whole port.

Two things were built to meet it half way rather than left to be retrofitted.
The stored item carries a `type`, which is `'column'` for everything this epic
produces and `'group'` on read only — a state written by a later version loads
rather than being refused whole by
[the normalizer](has-table.md#the-column-state-is-re-derived-against-the-definition-on-every-read).
And the drag list's sortable ids are `column::<name>`, the same `type::name`
shape vendor's `reorderColumns()` splits on, so the controller's `reorder()`
already understands a group id and matches it against a `type === 'group'` entry;
that branch is forward compatibility and nothing renders it today.

Four class names are reserved for the group arm and are deliberately not emitted
now, so the eventual port does not have to rename anything already shipped:
`sy-ta-col-manager-group`, `sy-ta-col-manager-group-header`,
`sy-ta-col-manager-group-label` and `sy-ta-col-manager-group-items`.

## Known limitation

### `columnManagerColumns()` above one spills a column off the panel

**Found by the browser pass, reproducible, and not fixed.** With
`columnManagerColumns(2)` the item list lays out in **three** column positions,
and the third sits outside the container's own width — off-screen at a normal
viewport, so the control in it cannot be seen or reached. Measured on the demo's
showcase card at both **five** and **six** items, at panel widths of 42, 48, 56
and 64rem, on clean page loads with no drag artefacts.

What it is **not**: `column-manager.css` is byte-identical to
[vendor's](../../../vendor/filament/tables/resources/css/column-manager.css) for
both the `-items` and `-item` rules; the container is built by the identical
call, `->grid($columns, GridDirection::Column)`, over a
[`grid()`](../support/README.md) whose column arm is byte-identical to
Filament's; support's grid emits the expected `column-count: 2`; and the extra
`.sy-ta-col-manager-content` wrapper this port interposes was tested by
reparenting the list out of it and is **not** the cause. No `max-height`
constrains the list anywhere in its ancestor chain. Setting
`column-fill: auto` collapses it to a single column, so the spill comes from
CSS multi-column **balancing** over `break-inside: avoid` items rather than from
anything this port chose.

So it is inherited rather than mis-ported, and a Filament table configured the
same way should show it too — but it is a real constraint on a knob we ship, and
the default (`1`) is unaffected: `/admin/articles` lists six items in one column
correctly. **Anyone reaching for more than one column should verify the panel in
a browser first.** The fix is a layout change — a CSS grid rather than CSS
multi-column, which would diverge from vendor deliberately — and belongs in its
own change, not in the epic that found it.

## Divergences

Permanent departures from Filament, each with the reason.

### The trigger drops vendor's modal-footer machinery, because the chrome is the partial's

Vendor's `getColumnManagerTriggerAction()` builds an action carrying six settings
this port does not: `modalSubmitAction(false)`, an `extraModalFooterActions()`
pair (the apply action plus a hand-made reset), `modalCancelActionLabel()`,
`authorize(true)`, an `alpineClickHandler()` on each footer action, and an
`extraAttributes(['class' => 'fi-force-enabled'], merge: true)` stamped after the
customization closure. All six exist because the *action* owns the modal
upstream: opening the manager is opening that action's modal, so the footer has
to be assembled through the action API.

Here the manager's chrome is the [table partial](../../../packages/tables/templates/partials/Table/index.html.twig)'s,
exactly as the filters panel beside it is: the dropdown is support's `Dropdown`
and the modal is support's `Modal`, both rendered by the partial, with Apply and
Reset placed in their footers directly. So there is no action-owned modal for
those settings to configure, and the trigger reduces to what actually opens
something — `liveComponentClickHandlerEnabled(false)`, and, under the modal
layout, an invoker `command` / `commandfor` pair. That pair *is* stamped after
the customization closure, and for vendor's reason rather than by imitation:
`extraAttributes()` replaces unless told to merge, so a closure setting
attributes of its own would otherwise leave the modal layout with a trigger that
opens nothing.

`authorize(true)` has no counterpart because no authorization layer ships yet,
and `fi-force-enabled` none because nothing here disables an action from the
outside.

### The stored item carries four keys where Filament's carries seven

Vendor's state item is
`{type, name, label, isHidden, isToggled, isToggleable, isToggledHiddenByDefault}`
(plus a `columns` array on a group). Ours is
`{type, name, isToggled, isToggledHiddenByDefault}`.

The three dropped keys are all **derived from the definition**, and the
definition is on the server, one `getColumn()` away, at every point that needs
them. Vendor stores them because its manager is an Alpine component reading a
client-side array: `label` is what the row prints, `isHidden` is what it filters
on, `isToggleable` is what disables its checkbox — all three are needed on the
client because that is where the list is built. Here
[the list is built on the server](#the-item-list-is-server-rendered-where-vendors-is-an-alpine-x-for),
so those three are read fresh off the column each render and never round-trip.

The gain is not brevity. A stored `label` is a stale copy the moment a label
changes; a stored `isHidden`/`isToggleable` is a **client-supplied claim about
the definition**, and the draft is writable, so honouring it would let a forged
draft say a column is toggleable when it is not. The four keys that remain are
exactly the ones the client is allowed an opinion about — which column, in what
order, toggled or not — and even those are re-derived against the definition on
every read.

`isToggledHiddenByDefault` stays despite being derivable, because it is not read
as a fact about the current definition but as a **sentinel about the stored
one**: `null` means *this column was not toggleable when this state was written*,
which is what lets a column that has since become toggleable be re-seeded from
its declared default rather than left at a stale choice.

### Reorderedness is asked, not remembered, so the second session key is gone

Filament has the same derived test this port uses —
`hasReorderedTableColumns()` flattens the stored and declared states to
`type:name` lists, narrows the declared one to the columns both know, and asks
whether they differ. What it does with the answer is where the two part company.
Vendor **latches** it per call: `persistHasReorderedTableColumns()` writes
`$wasReordered || $this->hasReorderedTableColumns()` into a **second** session
entry (`tables.<md5>_has_reordered_columns`), and every later
`applyTableColumnManager()` picks its sync direction by reading that entry back
rather than by re-asking. The latch's other half is the **client's**: it never
reads the stored value back into the write, so what keeps the entry `true` over
an order the test would call unreordered is `$wasReordered` — the Alpine
component's own `hasReordered`, set on any drag (`column-manager.js:152`/`:184`)
and cleared only after a successful apply (`:225`). An apply that carries `false`
over a restored order therefore writes `false || false` and lowers it again.

Here the same test is `isReorderedTableColumnState()` — parameterised over a
state, so it can be asked of the draft as well as of the applied bag — and it is
asked afresh at each sync. That removes three things at once: the second session
key, the write that maintains it, and the `$wasReordered` parameter, which is a
**client's claim about what the client did** rather than a value. A draft could
assert it without having reordered anything, so dropping it is a small hardening
as well as a simplification.

The behaviour difference is the latch. A user who drags a column, drags it back
and applies stays in reordered mode upstream — the client still claims a drag,
so the entry is written `true` over an order that is the declared one again, and
it stays that way until some later apply carries `false`. Here the same apply
re-derives the answer from the state, which now matches the default, so it reads
as unreordered at once. Both render the declared order that day; what the stale
claim costs upstream is the *next* definition change, because reordered mode
appends a newly declared column at the end of the stored order where the
unreordered path re-derives it into its declared position. It is a difference
and is recorded as one — and it is the reason the port needs no
`resetTableColumnManager()` arm to clear a flag.

### The session is written at the end of every mutation, rather than once per request

Vendor's `applyTableColumnManager()` and `resetTableColumnManager()` each end in
`persistTableColumns()`, and so do ours — but vendor *also* runs the whole apply
from `initTableColumnManager()` on every request, so the session is rewritten
once per render whether anything changed or not. Ours writes on the two live
actions and on the mount that seeds the state, and the hydrate hook — the
per-request path — writes nothing at all.

The trigger for the divergence is mechanical: vendor's mount and its apply are
one method, so the per-request write is a side effect of reusing it. Splitting
them into a `#[PostMount]` seed and a `#[PostHydrate]` re-derivation makes the
write a choice, and writing an unchanged value into the session on every render
of every table with a toggleable column is a cost with no reader.

The flag's polarity is Filament's and is inherited whole:
`persistColumnsInSession()` defaults **on** — the only persistence flag in the
package that does — and gates the **write** only, so the read is ungated. Both
halves of that are recorded on
[`has-table.md`](has-table.md#the-column-state-is-re-derived-against-the-definition-on-every-read).

### The item list is server-rendered, where vendor's is an Alpine `x-for`

`content.blade.php` iterates `columns` with `x-for` — the client array
`$wire.entangle('tableColumns')` seeds from the *applied* bag — while each row's
`checked` and `disabled` bindings read the draft, through
`getColumn(column.name, null)` off `deferredColumns`. So every row, its label and
its disabled state are produced in the browser, and the row set and its order
only change once Apply has written the draft back. Here
`getTableColumnManagerItems()` produces the rows on the host, from
`tableDeferredColumns` rather than the applied bag, and the
`ColumnManagerContent` component iterates a plain Twig array. The two therefore
render *different* bags, which is the load-bearing half of this divergence: a
dragged row moves in our list on the next render and in vendor's only after
Apply.

That is the same trade the rest of the package makes (the selection banner, the
editable cells): the server is canonical, the controller repaints. Concretely,
the list is the draft's order, filtered to the columns the definition still
declares and the developer has not `hidden()`, each row carrying the column's
*current* label and toggleability read off the definition — none of which the
client could be trusted with. What the controller still owns is the part that
must not wait for a round trip: the checkbox's `checked` state, repainted from
the draft on `connect()` and on every `render:finished`, because UX pins a
touched input through a morph and the server-rendered `checked` attribute stops
winning after the first one.

The list is published through the contract rather than reached off the trait, as
[the twenty-fourth member](has-table.md#the-contract-is-twenty-six-methods-of-filaments-forty-six):
the partial reads it off `getLiveComponent`, which is typed
`InteractsWithTable`.

### A hidden column's entry survives a drag, where vendor's rebuild drops it

Vendor's `reorderTopLevel()` builds a fresh array from the ids the drag control
reported and assigns it over `deferredColumns`. Anything the list did not
render — a column the developer `hidden()`, so it never had a row to drag — is
absent from those ids and therefore **falls out of the state entirely**, taking
its stored toggle with it; the next sync re-adds it from the *definition*, so
whatever the user had chosen for it is silently reset.

`reorder()` here appends the unreported entries after the reported ones instead
of dropping them, so the entry survives the drag with its stored `isToggled` and
`isToggledHiddenByDefault` intact. The **order** is not the difference: both ends
leave such an entry at the back, ours because `reorder()` appends it there and
vendor's because
[`getNewDefaultColumnStateItems()`](../../../packages/tables/src/Concerns/HasColumnManager.php)'s
counterpart re-appends the missing default at the tail. It is a fix rather than
a translation, and it is recorded here rather than in the
[bundle's controller notes](tables-bundle.md#asset-registration) because it
changes what the *state* means rather than how the controller is wired.

### The width is a CSS length, and the columns-to-width match is not ported

Vendor's `getColumnManagerWidth()` is `Width | string | null` and falls back
through a `match` on the column count — 2 → `TwoExtraLarge`, 3 →
`FourExtraLarge`, 4 → `SixExtraLarge`, else `ExtraSmall` — so it never actually
returns null. Ours is `?string`, a raw CSS length handed to support's `Dropdown`
and `Modal`, and it is genuinely nullable: with nothing set, the dropdown takes
the `20rem` its own stylesheet declares and the modal takes `md`.

Both halves of that are the same missing piece — the `Width` enum, which this
package does not carry. It is the deferral
[`filtersFormWidth()` already waits on](table.md#the-filters-form-columns-follow-the-layout-the-width-does-not),
and the two setters land together or not at all; the `match` is a one-line
follow-on once there is an enum for it to return.

### The modal layout renders one Reset, wherever the setting puts it

`ColumnManagerResetActionPosition` ships whole and is honoured whole in the
**dropdown** layout: `Header` puts a `Link` beside the heading, `Footer` puts a
`Button` in the actions row. In the **modal** layout the partial renders the
panel's items alone — `ColumnManagerContent`, not `ColumnManager` — and puts
Apply and an unconditional Reset `Button` in the modal's own footer, so the
setting has no effect there.

This is the filters modal's shape one component over, and deliberately so: a
modal already has a footer, and a heading-row Reset inside a dialog whose own
footer is empty reads as a mistake. Vendor reaches the same place by a different
route — its modal *is* the trigger action's, with the footer assembled through
`extraModalFooterActions()` — so upstream the setting is equally inert under the
modal layout. The divergence is in the mechanism, not the outcome.

### The item-list block sits at top level, where vendor's nests inside the panel

`column-manager.css` nests `.sy-ta-col-manager-header`,
`.sy-ta-col-manager-heading` and `.sy-ta-col-manager-actions-ctn` under
`.sy-ta-col-manager` as vendor does, but hoists `.sy-ta-col-manager-items` and
everything under it to top level, where vendor keeps it nested.

The reason is the [modal layout](#the-modal-layout-renders-one-reset-wherever-the-setting-puts-it):
it renders `ColumnManagerContent` **without** the `.sy-ta-col-manager` wrapper,
so a nested rule would leave every item in the modal unstyled — no
`break-inside-avoid`, no `-mt-6`, no gap. The rest of the sheet is vendor's,
prefix-renamed, plus one Sylament-only block, `.sy-ta-col-manager-dropdown`,
which carries the `20rem` default width because support's `Dropdown` has no
width of its own to default.

## Semantics

Behaviour worth knowing that is not a divergence.

### The state exists for the columns, not for the chrome

`hasTableColumnState()` — the gate on both lifecycle hooks — asks whether any
column is *reorderable or toggleable*, never whether the manager is shown. So
`columnManager(false)` over toggleable columns still seeds the state and still
honours each column's `toggledHiddenByDefault()`: the setting hides the trigger,
it does not turn the feature off. The other direction is the
[divergence recorded on `has-table.md`](has-table.md#the-column-state-is-re-derived-against-the-definition-on-every-read) —
an explicit `columnManager(true)` over columns that are neither seeds nothing,
and renders an empty panel.

### An absent name reads as hidden

`isTableColumnToggledHidden()` is `!($map[$name] ?? false)`, so a name the map
has never heard of answers **true**. That is safe by construction rather than by
luck: both sync directions leave every declared column in the state before
anything can read it, and a column only asks about itself when it is toggleable,
which is exactly when it is in the map. The invariant to preserve is therefore
*the state is complete before the first read*, not the default itself.

It is also the shape that makes an unmounted host read as "everything hidden", so
a test that skips `#[PostMount]` sees a `true` that says nothing — the trap
[the coverage note above](#surface) names.

### The order is followed only while columns are reorderable

`syncTableColumnState()` re-`columns()` the table in the stored order only when
`hasReorderableColumns()` is on. Turning reordering off therefore restores the
declared order immediately, without clearing the stored one — the order is still
in the session, and turning the setting back on brings it back.

`updateTableColumns()` reads `getDefaultTableColumnState()` **before** it
re-columns the table. The ordering is what keeps the method correct on its own —
the default state is memoised on first read, so reading it after the table has
been re-columned in the stored order would memoise the *stored* order as the
declared one and leave Reset with nothing to restore — but it is defensive
rather than load-bearing today: the sole caller is `syncTableColumnState()`,
whose preceding statement runs `syncTableColumnStateItems()`, and *that* method's
first line reads the default. The memo is therefore always warm by the time the
re-columning happens, and deleting the priming read changes no behaviour and
fails no test. It is kept so that a second caller — a direct
`updateTableColumns()` on a cold memo — cannot silently invert Reset.

### `hasToggleableColumns()` walks every column's closure at mount

It is `array_any()` over the declared columns asking each `isToggleable()`, and
`toggleable()` takes a closure, so the gate evaluates every column's closure on
every mount and every hydrate — including for a table with no manager at all,
which is what the gate exists to find out. Filament does the same and no cheaper
gate was substituted; the cost is recorded rather than optimised, beside
[the visible set's two walks](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again).

### A bare integer column count is support's shorthand, and the table tier hands it through untouched

`columnManagerColumns()` takes vendor's `int | array | Closure` and
`getColumnManagerColumns()` hands it to the items element's `grid()` call as-is.
Support's grid reads a bare integer as *N columns from `lg` up and one below*, so
`columnManagerColumns(2)` emits `--cols-lg: 2; --cols-default: 1` rather than
`--cols-default: 2`. The widening is `grid()`'s; the table tier neither performs
nor undoes it, which is Filament-exact.

The direction matters too: the items element is a **column**-direction grid
(CSS multi-column), not a row grid, which is what makes `break-inside-avoid` and
the `-mt-6` / `pt-6` rhythm in the stylesheet do anything at all.

### The checkbox renders only where there is something to toggle

`ColumnManagerContent` renders the `<input>` only when the table has toggleable
columns at all, and disables it per row where that particular column is not
toggleable. Vendor gates the same way, and the reason is UX rather than parity:
a `reorderableColumns()`-only manager would otherwise show a column of
permanently inert checkboxes beside the drag handles.

The flag is computed once in the partial's preamble
(`component.hasToggleableColumns`) and threaded through both layouts, so the
dropdown and the modal cannot disagree about it.

### The session key is the host class, so relation managers of one class share one

The manager's state persists under the `columns` suffix of
[the shared session key](has-table.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it),
which is `md5(static::class)`. Two instances of the same host class on one page
therefore share one remembered column set — which for the column manager is more
visible than for the sort or the search, because two relation managers of the
same class under one parent record are a realistic page: hiding a column in one
hides it in the other, and the two panels agree because they are reading one
entry.

That is Filament's collision, inherited deliberately rather than worked around;
the [reasoning is on `has-table.md`](has-table.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it)
and applies unchanged here.
