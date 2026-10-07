---
description: 'The table''s action sets: the flat action maps, record, header, toolbar and bulk actions, and the selection settings and chrome (draft)'
ports:
  - vendor/filament/tables/src/Table/Concerns/HasActions.php
  - vendor/filament/tables/src/Table/Concerns/HasRecordActions.php
  - vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php
  - vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php
  - vendor/filament/tables/src/Table/Concerns/HasBulkActions.php
  - vendor/filament/tables/src/Enums/RecordActionsPosition.php
  - vendor/filament/tables/src/Actions/HeaderActionsPosition.php
  - vendor/filament/tables/src/Enums/RecordCheckboxPosition.php
owns:
  - packages/tables/assets/src/styles/components/header-toolbar.css
  - packages/tables/assets/src/styles/components/selection.css
  - packages/tables/src/Enums/HeaderActionsPosition.php
  - packages/tables/src/Enums/RecordActionsPosition.php
  - packages/tables/src/Enums/RecordCheckboxPosition.php
  - packages/tables/src/Table/Concerns/HasActions.php
  - packages/tables/src/Table/Concerns/HasBulkActions.php
  - packages/tables/src/Table/Concerns/HasHeaderActions.php
  - packages/tables/src/Table/Concerns/HasRecordActions.php
  - packages/tables/src/Table/Concerns/HasToolbarActions.php
---
# HasActions (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`recordActions()` / `pushRecordActions()` / `getRecordActions()` / `getFlatRecordActions()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`Table\Concerns\HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | in progress | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| [`recordActionsPosition()` / `getRecordActionsPosition()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) + [`RecordActionsPosition`](../../../packages/tables/src/Enums/RecordActionsPosition.php) | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php), [`Enums/RecordActionsPosition`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php) | in progress | — | [detail](#the-position-default-has-no-content-arm-to-choose-between-yet) · [diverges](#the-position-setter-drops-filaments-string-arm) · [diverges](#the-four-rendered-positions-collapse-to-two) |
| [`getVisibleRecordActionsByRecordKey()` — per-record clones, record-bound, hidden dropped](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`index.blade.php`'s `$reduceVisibleRecordActions` / `$recordActionsByRecordKey`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-visible-action-map-is-a-table-method-and-is-not-short-circuited) |
| [The two flat action maps — `getAction()` / `getBulkAction()` / `hasAction()` / `hasBulkAction()` / `getFlatActions()` / `getFlatBulkActions()` / `cacheAction()` / `mergeCachedFlatActions()` / `removeCachedActions()`](../../../packages/tables/src/Table/Concerns/HasActions.php) | [`Table\Concerns\HasActions`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php) | in progress | — | [detail](#the-flat-action-map-keeps-one-trim-and-its-caller) · [semantics](#the-bulk-split-is-by-class-and-eviction-is-by-identity) |
| `modifyUngroupedRecordActionsUsing()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| Record-action presentation — `recordActionsColumnLabel()`, `recordActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#the-actions-header-cell-is-always-the-unlabelled-arm) |
| The deprecated v3 aliases — `actions()`, `pushActions()`, `actionsPosition()`, `actionsColumnLabel()`, `actionsAlignment()`, `getActions()`, `getActionsPosition()`, `getActionsColumnLabel()`, `getActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | skipped | — | The project has never ported a deprecated Filament surface; each is a one-line delegate to the `record*` name above |
| [`toolbarActions()` / `pushToolbarActions()` / `getToolbarActions()`](../../../packages/tables/src/Table/Concerns/HasToolbarActions.php) | [`Table\Concerns\HasToolbarActions`](../../../vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php) | done | — | [detail](#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) |
| [`headerActions()` / `pushHeaderActions()` / `getHeaderActions()` / `headerActionsPosition()` / `getHeaderActionsPosition()`](../../../packages/tables/src/Table/Concerns/HasHeaderActions.php) + [`HeaderActionsPosition`](../../../packages/tables/src/Enums/HeaderActionsPosition.php) | [`Table\Concerns\HasHeaderActions`](../../../vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php), [`Actions/HeaderActionsPosition`](../../../vendor/filament/tables/src/Actions/HeaderActionsPosition.php) | in progress | — | [detail](#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) · [diverges](#headeractionsposition-files-under-enums-where-filament-keeps-it-under-actions) |
| [The two-group toolbar — the start actions group, its three reserved slots and the widened render gate](../../../packages/tables/templates/partials/Table/index.html.twig) + [`header-toolbar.css`](../../../packages/tables/assets/src/styles/components/header-toolbar.css) | [`index.blade.php`'s `fi-ta-header-toolbar`](../../../vendor/filament/tables/resources/views/index.blade.php), [`container.css`](../../../vendor/filament/tables/resources/css/container.css) | done | — | [diverges](#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content) · [diverges](#a-bulk-only-trigger-is-hidden-by-a-wrapper-span) · [diverges](table.md#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) |
| [The selection chrome — the checkbox cells, the tri-state page checkbox and the selection banner](../../../packages/tables/templates/partials/Table/index.html.twig) + [`selection.css`](../../../packages/tables/assets/src/styles/components/selection.css) | [`index.blade.php`'s selection cells and `fi-ta-selection-indicator`](../../../vendor/filament/tables/resources/views/index.blade.php), [`support/…/input/checkbox.css`](../../../vendor/filament/support/resources/css/components/input/checkbox.css) | done | — | [diverges](#the-selection-banner-flattens-vendors-second-wrapper-and-renders-supports-link) · [diverges](#the-selection-cells-carry-no-header-cell-class-and-their-checkbox-skin-is-supports) · [tables-bundle.md](tables-bundle.md#the-selection-controller-diverges-from-tablejs-in-four-places) |
| [The selection settings — `bulkActions()` / `pushBulkActions()` / `groupedBulkActions()` / `selectable()` / `isSelectionEnabled()` / `selectCurrentPageOnly()` / `selectsCurrentPageOnly()` / `checkIfRecordIsSelectableUsing()` / `checksIfRecordIsSelectable()` / `isRecordSelectable()` / `trackDeselectedRecords()` / `canTrackDeselectedRecords()` / `recordCheckboxPosition()` / `getRecordCheckboxPosition()` / `getAllSelectableRecordsCount()` / `selectGroupsOnly()` / `selectsGroupsOnly()`](../../../packages/tables/src/Table/Concerns/HasBulkActions.php) + [`RecordCheckboxPosition`](../../../packages/tables/src/Enums/RecordCheckboxPosition.php) | [`Table\Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php), [`Enums/RecordCheckboxPosition`](../../../vendor/filament/tables/src/Enums/RecordCheckboxPosition.php) | in progress | — | [detail](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) · [semantics](has-filters.md#one-filter-setting-drives-three-selection-behaviours) · [host-has-bulk-actions.md](host-has-bulk-actions.md#the-selection-is-server-canonical-and-re-derived-on-every-read) |

The action rows split their coverage by tier.
[`HasRecordActionsTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasRecordActionsTest.php) drives everything through a real `Table`: one registration flattening a group into the same name-keyed map as an ungrouped action, a second `recordActions()` call resetting the set where `pushRecordActions()` appends to it, the `Size::Small` + link-template defaults losing to an explicit `size()`/`button()`, the table bound onto both an ungrouped action and a group member, the `AfterColumns` default with the two dropdown placements it picks by side, a closure position evaluated with the table injected, and the `InvalidArgumentException` a non-action member earns.
`getVisibleRecordActionsByRecordKey()` is covered from the render side instead, in the Integration file below — the map's whole contract is what the partial does with it, and a unit assertion on a list of clones would restate the method body.
[`HasActionsTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasActionsTest.php) covers the map on its own, through an object composing the trait alone — the `shouldOverwriteExistingAction: false` arm of `cacheAction()` has no public caller yet, so nothing on `Table` reaches it.

## Details

### Record actions register without the per-action modifier hook

`recordActions()`, `pushRecordActions()`, `getRecordActions()` and `getFlatRecordActions()` are ported whole: the reset-then-push shape, the `Size::Small` + `LINK_TEMPLATE` defaults stamped through [`defaultSize()`/`defaultTemplate()`](../actions/action.md) so an explicit `size()`/`button()` still wins, the group flattening into the shared flat map, the table bound onto every member, and the by-side dropdown placement `getRecordActions()` re-stamps on each read.

Two things Filament does here are not in.
`modifyUngroupedRecordActionsUsing()` — a closure `pushRecordActions()` evaluates against every ungrouped action with `['action' => $action]` injected, which Filament's docs reach through a [`Table::configureUsing()`](../support/component-manager.md) registration to restyle every table's ungrouped actions at once — has no consumer in the port yet and is **todo** on a row of its own.
And the `->defaultDropdownTeleport(true)` Filament chains onto the placement stamp is still not made here, though it is now *callable*: the teleport family landed on the action side with the toast that needed it ([semantics](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase)), so the stamp is one call away rather than blocked.
It is a **live gap**, not a deferral of convenience — `.sy-ta-ctn` applies `overflow-x-auto`, and a non-`visible` overflow on one axis makes the UA adjust the other to `auto` as well, so the table container clips a row-action panel exactly the way the toast card did.
Whoever next touches record actions should add the call and verify it in the demo; this file is not that lane.

### The actions header cell is always the unlabelled arm

Filament's header renders one of two `<th>`s for the actions column: a normal `fi-ta-header-cell` carrying `$recordActionsColumnLabel` when the table set one, and otherwise an empty `fi-ta-actions-header-cell fi-ta-empty-header-cell` whose `aria-label` is the pluralised *"Actions"* translation.
Sylament's partial renders only the second, with the English literal `aria-label="Actions"`.

Both halves of the first arm are missing rather than declined: `recordActionsColumnLabel()` is the **todo** row above, and there is no label to read; the pluralisation `trans_choice(..., $flatRecordActionsCount)` needs the [i18n layer](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) nothing in the port has, the same reason every fixed string is a literal.
The setter and the `{% if %}` that reads it land together, at which point the literal becomes the fallback of the second arm rather than the only arm.

`recordActionsAlignment()` is the other half of that row and it is a CSS question, not a template one: Filament expresses it as `fi-align-*` modifier classes on `fi-ta-actions`, and the [ported stylesheet block](../../../packages/tables/assets/src/styles/components/table.css) deliberately stops at the base rule — the alignment and wrapping arms come back with the setter that can emit them.

### The position default has no content arm to choose between yet

Filament's `getRecordActionsPosition()` falls back through *two* defaults: with no content grid and no columns layout it answers `AfterColumns`, and otherwise `AfterContent` — the actions move below the card when the table renders records as a grid instead of as rows.
Sylament answers `AfterColumns` unconditionally.

Nothing is dropped in principle.
`getContentGrid()` and `hasColumnsLayout()` are both unported (the `Presentation` and `Column grouping and layouts` rows above), so there is no state either branch could read — a two-way fallback would be a constant with extra steps.
The `AfterContent` case of the enum ships anyway, because the enum is a verbatim port and a partial one would be a worse lie.
The second arm returns in the same change as the content grid.

### The flat action map keeps one trim, and its caller

The map is Filament's whole surface now — `getAction()`, `getBulkAction()`, `hasAction()`, `hasBulkAction()`, `getFlatActions()`, `getFlatBulkActions()`, `cacheAction()` with its `instanceof BulkAction` split, `mergeCachedFlatActions()` and `removeCachedActions()`.
The bulk half and the eviction landed together with [`BulkAction`](../actions/bulk-action.md) and the two action-set traits that call them, which is what the earlier version of this section was waiting for.

One trim is deliberate rather than incidental.
`mergeCachedFlatActions()` drops Filament's `$shouldOverwriteExistingActions` passthrough, while `cacheAction()` keeps its own `$shouldOverwriteExistingAction`.
That looks lopsided until you find who passes `false`: only `pushEmptyStateActions()`, whose whole point is that an empty-state action must **not** displace a same-named record or header action already in the map.
Both members exist to serve that one caller, which is [blocked](#surface) — so the parameter stays where a caller will reach it and the passthrough returns with the caller that needs it.
Every other caller overwrites.

### The header and toolbar setters are destructive, because the flat bulk map is the checkbox switch

`toolbarActions()` and `headerActions()` both walk the set they are about to replace, hand each member (a group flattened first) to `removeCachedActions()`, empty their own ordered list, and only then push the new set.
Filament's comment explains the shape and it is worth keeping as a statement rather than a narration: the flat **bulk** map is what [`isSelectionEnabled()`](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) reads, so an appending setter would leave a replaced bulk action switching the checkbox column on forever, and clearing the whole map instead would take the record actions down with it.
Only identity eviction hits exactly the members the set being replaced owns.

Two consequences a reader would otherwise have to derive.
Re-registering under a name another set already cached is **last writer wins** in the flat map, and evicting the winner then leaves the map without the loser — the maps are keyed by name, the eviction is by identity, and nothing restores a shadowed entry.
And `pushToolbarActions()` / `pushHeaderActions()` do **not** evict, by design: they are the append half the destructive setters delegate into.

Two spelling differences from vendor, both no-ops.
`pushHeaderActions()` loops `getFlatActions()` calling `cacheAction()` per member where `pushToolbarActions()` calls `mergeCachedFlatActions()`; the port uses the merge helper in both, since that helper *is* the loop.
And `$action->table($this)` is stamped inside each `instanceof` branch rather than ahead of them, matching [`pushRecordActions()`](#record-actions-register-without-the-per-action-modifier-hook) and turning a bad member into the `InvalidArgumentException` the branch already throws instead of a fatal on an undefined method.

### The selection settings ship as a subset, and the rest are listed

Ported whole from `Table\Concerns\HasBulkActions`: the two deprecated aliases `bulkActions()` / `pushBulkActions()` (five lines each, forwarding to the toolbar setters, annotation included — they carry the API's household name and a migrating user greps for it in both directions), `groupedBulkActions()`, `selectable()` / `isSelectionEnabled()`, `selectCurrentPageOnly()` / `selectsCurrentPageOnly()`, `checkIfRecordIsSelectableUsing()` / `checksIfRecordIsSelectable()` / `isRecordSelectable()`, `trackDeselectedRecords()` / `canTrackDeselectedRecords()`, `recordCheckboxPosition()` / `getRecordCheckboxPosition()`, `getAllSelectableRecordsCount()`, which delegates to the host, and — since record grouping landed — `selectGroupsOnly()` / `selectsGroupsOnly()`, which removes the affordances that select a whole *page* (the header checkbox and the banner's select-all link) and leaves the checkbox column, its header cell and every per-record checkbox exactly as they were, so the column count does not move.

Three of those getters carry behaviour a signature cannot: `isSelectionEnabled()` returns an explicit `selectable()` bool when one was set and otherwise answers *is any visible bulk action registered*, so a table turns its own checkboxes on; `selectsCurrentPageOnly()` is `evaluate(…) || ! hasQuery()`, so a `records()` table is page-only whatever the setter says; and `canTrackDeselectedRecords()` is force-false under `deselectAllRecordsWhenFiltered(false)` ([why](has-filters.md#one-filter-setting-drives-three-selection-behaviours)).
`checksIfRecordIsSelectable()` is a **presence** test and never evaluates the callback — every reader uses it to decide whether the per-record walk is worth running at all, so evaluating it there would cost the very thing it guards.

`isRecordSelectable()` hands the callback the record twice: as the `record` named injection and as a typed injection keyed by `$record::class`.
The typed half is **exact-class** matching, because support's `Invoker` compares class-strings rather than walking `is_a()`, so a Doctrine **proxy** would key under its proxy class and miss a `fn (Song $record)` hint.
Nothing reaches that today — a root query's results are hydrated entities, never proxies — and the shape is shared with every other `::class`-keyed typed injection in the toolkit, so it is recorded here rather than worked around at this one call site: it would be fixed once, in the `Invoker`, if a proxy ever arrives.

Not ported, each for a stated reason rather than by omission:

- `maxSelectableRecords()` / `getMaxSelectableRecords()` — and with them Filament's per-universe asymmetry, a `LogicException` on the records lane against a silent SQL `limit()` on the query lane.
  A cap wants one meaning before it ships.
- `disabledSelection()` / `isSelectionDisabled()` — it suppresses the banner's two links only, which is chrome the banner has to render first.
- `resolveSelectedRecordsUsing()` — the records-lane override for how a selection resolves; the built-in `only`/`except` over the page collection is the only resolution here so far.
- `currentSelectionLivewireProperty()` — **skipped**, not deferred: it entangles the Alpine selection into a host property, which is exactly what the port's [writable props](host-has-bulk-actions.md#the-selection-is-server-canonical-and-re-derived-on-every-read) already are.

## Semantics

### The bulk split is by class, and eviction is by identity

`cacheAction()` sorts on `instanceof BulkAction`, not on `isBulk()`.
So an `Action::make('x')->bulk()` — bulk on the wire, bulk in [`getContext()`](../actions/action.md) — lands in the **record** map, and a bulk mount reaches it only through the resolver's fall-through from the bulk map to the record one.
It works; it just does not take the direct path, and it does not enable the checkbox column, since [`isSelectionEnabled()`](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) walks `getFlatBulkActions()`.
Filament behaves identically; use `BulkAction` when the class matters.

`removeCachedActions()` filters both maps with `in_array(…, strict: true)` — identity, never name.
That is what lets the [destructive setters](#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) evict their own members without touching a same-named action some other registration owns.

## Divergences

### `HeaderActionsPosition` files under `Enums/`, where Filament keeps it under `Actions/`

Filament's `HeaderActionsPosition` is the only file its tables package keeps in `src/Actions/`, and it is an enum rather than an action class.
The port files it in `Enums/` beside `RecordActionsPosition`, `RecordCheckboxPosition`, `FiltersLayout`, `FiltersResetActionPosition` and `PaginationMode`, so the six enums are in one place.
Cases, name and defaults are Filament's; only the namespace segment moves, which is the [namespace translation](../translations.md#namespace-segments) rule rather than a rename.
`RecordCheckboxPosition` is **not** a divergence of any kind — Filament already keeps that one in `Enums/`.

### The toolbar's start group carries its own class and renders only when it has content

Filament's start-aligned toolbar group *is* `fi-ta-actions`, the same class its record-action cell wrapper carries, and it renders unconditionally so the positional `& > :nth-child(1)` / `:nth-child(2)` rules always find two children.
Neither half survives the port.
The class is `sy-ta-header-toolbar-actions`, because `.sy-ta-actions` already means *the record-actions cell wrapper* here and its rule is `justify-end gap-3` — a start-aligned toolbar group under that name would be right-aligned and differently gapped — and because [`TableTest`](../../../packages/tables/tests/Integration/TableTest.php)'s all-hidden-actions case asserts the *absence* of the string `sy-ta-actions` from the rendered table, which a toolbar group of that name would falsify.
The group is then gated on holding a header or toolbar action, which costs the end group nothing precisely because the two groups are named rather than counted ([above](table.md#the-partial-emits-no-header-scaffolding-and-no-content-wrapper)): an absent first child leaves `& > .sy-ta-header-toolbar-end { ms-auto }` doing the same work.
The stylesheet addition is one rule, `max-w-full shrink-0` — vendor's `fi-ta-actions` pair minus everything the toolbar's generic `& > * { flex min-h-9 items-center gap-x-4 }` already supplies.

The group's contents keep Filament's order exactly — [reorder trigger] [toolbar actions] [grouping selector] — all three filled, with the end group's third slot taken by the column manager.
Getting that order wrong forces a restructure when a later trigger arrives, so the slots are load-bearing rather than decorative — each trigger drops into its reserved position with no restructure.
Header actions rendered here as a stopgap while no `sy-ta-header` band existed; they moved into the band (`sy-ta-header-actions` — named, not `fi-ta-actions`, for the same record-actions-collision reason as the start group) when the heading rows landed, which is Filament's own placement, and each list wraps its bulk-only members the same way wherever it renders.

### A bulk-only trigger is hidden by a wrapper span

Filament reveals a bulk trigger with `x-cloak` + `x-show="getSelectedRecordsCount()"` written straight onto the trigger element.
The port cannot: the trigger is rendered by the action's own template, and the only thing the table partial reaches into it is `getExtraAttributes()` — an unconditional map, which is where [`BulkAction`](../actions/bulk-action.md#bulk-only-visibility-is-a-data-marker-the-table-controller-flips-not-an-alpine-expression) stamps `data-sy-table-bulk-only`, `BulkActionGroup` stamping the same marker on its dropdown root.
That marker is what the selection controller flips `hidden` on, so the client half needs nothing further; the **server** half — `hidden` on the first paint, before the controller connects — has nowhere to go on an element the partial does not write.
So each bulk-only item is wrapped in a `<span>` carrying both the marker and `hidden` while nothing is selected, the shape `CheckboxList`'s two swapped bulk-toggle spans already use.
Wrapper and trigger carry the same marker and flip together.

Which items get the wrapper is read off the item itself — `isBulk` for an `Action`, and for an `ActionGroup` the marker's presence in `getExtraDropdownAttributes()`, which is the same declaration the controller selects on rather than a second predicate free to drift from it.
The gap that leaves is Filament's `ActionGroup::hasNonBulkAction()`, deliberately unwritten here: a **plain** `ActionGroup` holding only bulk actions is unmarked, so it renders and stays visible with nothing selected.
`BulkActionGroup` — what `groupedBulkActions()` builds, and what the demo uses — is marked and behaves.
The gap closes with that method.

### The selection banner flattens vendor's second wrapper and renders support's `Link`

Filament's banner is one `fi-ta-selection-indicator` holding two children, so its `& > :nth-child(1)` / `:nth-child(2)` rules can address them: the first a `<div>` around the loading indicator and the count text, the second a `<div>` wrapping `fi-ta-selection-indicator-actions-ctn` — a wrapper that exists only because two render hooks bracket it.
There are no render hooks here, so the second wrapper is dropped and `.sy-ta-selection-indicator-actions-ctn` **is** the second child.
That makes vendor's own `& .fi-ta-selection-indicator-actions-ctn { flex gap-x-3 }` redundant with the `& > *` rule above it, so it is not ported either and the class survives as the DOM hook the tests and the controller select on.
The loading indicator inside the first child goes with [the loading affordances](can-defer-loading.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) the toolkit has not ported at all.

Both links are `<twig:SylamentSupport:Link tag="button">`, which is exactly what `<x-filament::link tag="button">` is on the other side — so `primary` and `danger` come from the colour policy rather than a banner-local rule, and [`selection.css`](../../../packages/tables/assets/src/styles/components/selection.css) carries no link styling at all.
"Select all" ships a server `hidden` and is revealed by the controller only where a ceiling is known and not everything is selected; "Deselect all" is visible whenever the banner is.
The count is a controller-filled empty `<span>` seeded from two literal value strings (`1 record selected`, `:count records selected`) — Filament's `|`-separated Laravel plural string run through its own `window.pluralize` global, which the port does not ship; i18n takes both literals when it lands.
The controller formats the number it interpolates with `Intl.NumberFormat`, so it reads _"1,200 records selected"_, while the ceiling in the server-rendered _"Select all N"_ label is the bare integer.
The two are deliberately not aligned: `Intl.NumberFormat()` groups by the **browser's** locale and Twig's `number_format` by the template's, so matching them would trade a visible inconsistency for a locale-dependent one.
It resolves with i18n, on the same pass that takes the two count literals.

The banner is server-gated on `isSelectionEnabled()` and client-gated on the count, as vendor's is, and it renders on an overflow (empty) page too: a selection outlives a page that lost its rows.
Of vendor's two extra server gates one is ported — the banner does not render while a deferred table's window is still open — while `maxSelectableRecords(1)` stays unported.
The `fi-selected` row tint is not among them: the controller runs a `#repaint()` pass over each checkbox's closest `.sy-ta-row`, so `sy-selected` is stamped from the client exactly as Filament's Alpine binds it, and the row chrome rather than the ticked checkbox carries the state now — the tint and its leading primary bar are `row.css`'s two rules ported verbatim, the bar as a `::before` on the row's first child.
Vendor's *second* copy of that bar, in its `table.css`, is not ported: it paints the same stripe for the mobile-stacked block layout, which [has no chrome here](#surface) until the layout columns land.

### The selection cells carry no header-cell class, and their checkbox skin is support's

The `<th>` and `<td>` wrapping a checkbox carry `sy-ta-selection-cell` — never `sy-ta-header-cell`.
That is Filament's own split, and it is load-bearing downstream: every header-label assertion in this repo and in the demo reads `th.sy-ta-header-cell` as *the column headers*, and a selection column would otherwise push an unlabelled cell into that list.
The one deliberate trim is `sy-ta-cell` on the `<th>`, which vendor does stamp there: our `.sy-ta-cell` rule is written for a body cell (`p-0`, plus a `:has(.sy-ta-actions)` arm), so the header would import padding the selection rule then has to override.
The body cell keeps both classes, and `.sy-ta-selection-cell` is authored *after* `.sy-ta-cell` in [`table.css`](../../../packages/tables/assets/src/styles/components/table.css) so its `px-3 py-4` outranks the base `p-0` on source order at equal specificity.

A record the table refuses renders the cell **empty** rather than a disabled input — Filament's shape, and behavioural rather than cosmetic: the controller's page-key walk reads `.sy-ta-record-checkbox` values off the DOM, so a record with no input is excluded from "select page" by construction.

The `<input>` is inline in the template with no support Twig component behind it, again Filament's shape, and its skin is the code-less `.sy-checkbox` class support gained in the same change, beside the `sy-ta-page-checkbox` / `sy-ta-record-checkbox` hooks.
Moving the skin there is what gave the page checkbox its `:indeterminate` arm, which forms' copy never carried because nothing in forms can set that property ([forms' styles.md](../forms/styles.md#the-checkbox-skin-lives-in-support-because-tables-renders-it-too)).

The two ARIA labels are Filament's own strings with one word translated — "Select/deselect all records on this page for bulk actions." and "Select/deselect record :key for bulk actions." against vendor's "Select/deselect all items for bulk actions." and "Select/deselect item :key for bulk actions."
*Record* is this toolkit's word for what vendor's translation calls an item, and the page checkbox says which rows it covers because our header cell is the only place that label appears.

### The visible action map is a `Table` method, and is not short-circuited

Filament builds its per-record action map **in the Blade template**: a `$reduceVisibleRecordActions` closure clones each registered action, binds the record, drops the hidden ones, and a preamble loop calls it per record filling `$recordActionsByRecordKey`.
Sylament's [`getVisibleRecordActionsByRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) is the same computation on the `Table`, and the partial only reads the result.

The move is the ordinary Blade → Twig translation — Twig has no `@php` block and a closure-building preamble is not idiom — but it changes one thing on purpose.
Filament's preamble **breaks** out of the record loop the moment one record yields a visible action, because all it needs from that loop is the boolean `$hasRecordActionsForAnyRecord`; the rows it never reached compute their actions lazily, inside the row loop, through the same closure.
Sylament computes every record's entry up front and returns the whole map.

That is the cheaper shape here, not the more expensive one.
The row loop needs each record's list anyway, so Filament's early break saves work only for the records between the first visible action and the end of the page — and it pays for the saving with a second call site and a per-record cache to keep the two in step.
Computing all of it once, in one place, is the same total work in the common case (every record has the same actions) and one code path instead of two.
The template's own `hasRecordActions` is then a `filter(...)|length > 0` over the finished map.

One consequence to keep: a record whose actions are *all* hidden keeps an **empty list** in the map rather than being absent, so the template can tell "this record has no visible actions" from "this record is not in the map" — the first still renders its (empty) actions cell so the row keeps its column count.
The whole map is empty only when nothing was registered at all, which is the early return at the top of the method.

### The four rendered positions collapse to two

`RecordActionsPosition` has five cases, and the partial now branches on four of them separately: `BeforeCells` and `BeforeColumns` are two distinct insertion points around the selection cell, as are `AfterColumns` and `AfterCells`, while `AfterContent` renders nothing at all.
What still collapses is the *output*, and only on a table with no selection — with the checkbox column absent there is nothing between the two leading points, or between the two trailing ones, so each pair lands the same cell in the same place.

In Filament the pairs are distinct because there is a third thing in the row.
`*Cells` sits outside the columns *and* outside the selection checkbox and the reorder handle; `*Columns` sits between them and the data columns — so `BeforeCells` puts the actions before the checkbox and `BeforeColumns` after it.
The port splits the branches apart at exactly those two points, around the checkbox and the handle — but only the checkbox separates the pairs, because the two modes are mutually exclusive: reordering drops the checkbox cells *and* empties the action sets, so a row being dragged carries a handle, its columns, and no action cell at any of the four positions.
The distinctness a handle would add is therefore never observable, and the sentence above stands as written: with selection off, each pair lands the same cell in the same place.

`AfterContent` renders nothing on purpose rather than by omission: it is the columns-*layout* position, where records render as cards rather than rows and the actions go below the card body.
That layout is unported ([column grouping and layouts](#surface)), so a table asking for `AfterContent` gets no actions column at all — matching Filament, whose row-mode markup has no `AfterContent` arm either.

Half resolved, half still provisional: the branch split is permanent, the coinciding output is not — it ends for any given table the moment selection is on — and `AfterContent` gains its arm with the columns layout.

### The position setter drops Filament's `string` arm

Filament types both `recordActions()`'s `$position` and `recordActionsPosition()` as `RecordActionsPosition | string | Closure | null`, but `getRecordActionsPosition(): RecordActionsPosition` returns whatever `evaluate()` produced without converting — so a `string` accepted by the setter is a `TypeError` at the getter, every time.
The `string` arm is a v3 leftover from when positions were string constants, and nothing in Filament converts it.
Sylament types both `RecordActionsPosition|Closure|null` and rejects the string at the call site instead.

The consequence is the reverse of the usual compatibility argument: keeping the arm would preserve an API that cannot be used successfully, while dropping it turns a runtime crash deep in the getter into a type error at the setter the caller wrote.
A migrating user passing `'after-columns'` gets told immediately, at the line that is wrong.
Permanent — it reverses only if Filament ever adds the conversion the arm implies, at which point this becomes a real feature rather than a dead union member.
