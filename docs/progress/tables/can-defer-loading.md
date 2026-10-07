---
description: 'The table''s deferred first load and its polling interval: `CanDeferLoading` and `CanPollRecords` (draft)'
ports:
  - vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php
  - vendor/filament/tables/src/Table/Concerns/CanPollRecords.php
owns:
  - packages/tables/src/Table/Concerns/CanDeferLoading.php
  - packages/tables/src/Table/Concerns/CanPollRecords.php
---
# CanDeferLoading (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`deferLoading()` / `isLoadingDeferred()` / `isLoaded()`](../../../packages/tables/src/Table/Concerns/CanDeferLoading.php) + [`poll()` / `getPollingInterval()` / `getPollingIntervalMilliseconds()`](../../../packages/tables/src/Table/Concerns/CanPollRecords.php) | [`CanDeferLoading`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php), [`CanPollRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | done | — | [detail](#the-deferred-window-is-server-gated-and-client-closed) · [diverges](#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers) · [interacts-with-table.md](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) |

## Details

### Loading affordances are deferred with the JavaScript that would drive them

`deferLoading()` and `poll()` ship over support's loading affordance: the deferred window renders support's `LoadingIndicator` at `2xl` inside a `sy-ta-table-loading-ctn`, and the poll interval is the table controller's `setInterval` over `component.render()`.
The mechanics are the two sections below.

What is still deferred is the **in-flight** affordance — the indicator Filament shows *during* a request, driven by Alpine's `wire:loading`/`x-show` over a `LoadingIndicator` contract and a `loading-indicator` view.
Nothing here renders one: a polling table repaints on arrival with no interim state, the search field's prefix holds its icon alone, and the selection banner has no spinner beside its count.
That one waits on the in-flight mechanics rather than on the component, which support now ships.

### The deferred window is server-gated and client-closed

Filament's `wire:init="loadTable"` has no UX LiveComponent counterpart (R6 §2.2), so the window is ours end-to-end.
The host carries `$isTableLoaded`, a non-writable `LiveProp` mutated only by the `loadTable()` action, and `isTableLoaded()` short-circuits to true on a table that does not defer — so `Table::isLoaded()` answers for every table and the flag is inert unless `deferLoading()` is on.
The partial reads it once and gates **four** reads on the result: the preamble `getRecords`, the `getPaginator` read, the `getVisibleRecordActionsByRecordKey` map and the selectable-count query.
Every one of them reaches the database, and each is pinned by [`TableDeferredLoadingTest`](../../../packages/tables/tests/Integration/TableDeferredLoadingTest.php)'s zero-read case: the fixture's data source counts its calls, so deleting any one gate fails there rather than only under a profiler.

The body branch splits three ways where vendor's splits two.
`records is null` is the loading block; `records is empty` is the empty state; anything else is the table.
Before this, one `records is empty` carried both null and the empty list — Twig's `is empty` is true for `null` — which is exactly the conflation vendor avoids with `$records === null`.
The selection banner gains the same gate, which is vendor's own `$isLoaded` guard on it.

The controller closes the window: on connect it reads the flag back off the live store and, when the table defers and the flag is false, fires `loadTable` once.
A reconnect after the load reads true and fires nothing, so the once-guard is the store rather than a controller flag.
What it reads there is the **method's** answer, not the raw property — the prop and the getter share a name, so `PropertyAccessor` prefers `isTableLoaded()` when the hydrator dehydrates ([the general mechanic](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary), which `$isTableReordering` carries too).
That is the value the client wants: a table that does not defer publishes `true` however its flag stands, so the controller never fires an action into a window that was never open.

### The poll interval parses strictly and carries no Livewire modifiers

`wire:poll` reads a Livewire root, and the table partial does not own one — stamping the *page* would re-render everything on the page rather than the table (R6 §2.1/2.3).
So `poll()` is server-side configuration and the interval is client-side machinery: `getPollingIntervalMilliseconds()` hands the controller a number and the controller owns the `setInterval`.
The attribute is emitted only while the table is not being reordered, matching vendor's `(! $isReordering)` guard, and regardless of the deferred state, because vendor polls through the deferred window too.

Owning the timer is what makes that guard cost something Livewire's does not.
`wire:poll` is re-read whenever the attribute is morphed, so removing it stops the loop; the table partial's root carries no id, key or `data-live-ignore`, so a live re-render morphs it **in place** and Stimulus never re-runs `connect()` — a timer started there would keep firing `render()` through a drag, morphing the rows out from under SortableJS, which is exactly what the suppression exists to prevent.
So the loop is (re)started from `pollIntervalValueChanged()` rather than only at connect: Stimulus fires that callback when the attribute is added, changed, and removed (with the value's default), which stops the timer on the way into reorder mode, restarts it on the way out, and picks up a new interval from a `poll()` closure whose answer changed.
`#startPolling()` is idempotent (it stops first) and returns unless the host is resolved, so the callback Stimulus fires *before* `connect()` — the value observer starts first — leaves the start to `connect()`.

Two divergences ride on that.
The parse is **strict**: `10s` and `500ms` parse, and anything else — a bare number, a unit Filament never had, an empty string — throws `InvalidArgumentException` where Livewire silently falls back to 2000 ms.
A table quietly polling at the wrong rate is invisible until it costs somebody a database, which is the fail-fast house rule's whole case.
One consequence of the reorder guard sitting *above* that parse in the template — `isReordering ? null : component.getPollingIntervalMilliseconds` — is that a malformed interval does not throw while a table is in reorder mode: the getter is never reached, so the page renders, and the exception arrives on the render that leaves the mode.
And Livewire's `.visible` / `.keep-alive` modifiers are **not** ported: both are properties of `wire:poll` itself, and the interval this port hands the browser is a number with nowhere to carry them.
