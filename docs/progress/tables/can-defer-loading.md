---
description: The table's deferred first load and polling interval, and the host flag and action that close the deferred window.
ports:
  - vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php
  - vendor/filament/tables/src/Table/Concerns/CanPollRecords.php
  - vendor/filament/tables/src/Concerns/CanDeferLoading.php
owns:
  - packages/tables/src/Table/Concerns/CanDeferLoading.php
  - packages/tables/src/Table/Concerns/CanPollRecords.php
  - packages/tables/src/Concerns/CanDeferLoading.php
---
# CanDeferLoading (tables)

The table's `CanDeferLoading` and `CanPollRecords` configure the deferred first load and the poll; the host's `Concerns\CanDeferLoading` holds the flag the deferred window closes on.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`deferLoading()` / `isLoadingDeferred()` / `isLoaded()`](../../../packages/tables/src/Table/Concerns/CanDeferLoading.php) | [`Table\Concerns\CanDeferLoading`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php) | done | — | [semantics](#the-deferred-window-is-server-gated-and-client-closed) |
| [`poll()` / `getPollingInterval()` / `getPollingIntervalMilliseconds()`](../../../packages/tables/src/Table/Concerns/CanPollRecords.php) | [`Table\Concerns\CanPollRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | done | — | [diverges](#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers) |
| [The host half: `$isTableLoaded`, `loadTable()`, `isTableLoaded()`](../../../packages/tables/src/Concerns/CanDeferLoading.php) | [`Concerns\CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | done | — | [semantics](#the-deferred-window-is-server-gated-and-client-closed) · [interacts-with-table.md](interacts-with-table.md#a-member-is-on-the-contract-when-something-outside-the-host-trait-calls-it) |
| `isTableLoadingDeferred()` | [`Concerns\CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | skipped | — | [has-table.md](has-table.md#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) |

## Semantics

### The deferred window is server-gated and client-closed

Filament closes the window with `wire:init="loadTable"`, which UX LiveComponent has no counterpart for, so the table controller closes it instead.
The host carries `$isTableLoaded`, a `LiveProp` that is not writable and changes only through the `loadTable()` action, and `isTableLoaded()` answers true on a table that does not defer, so `Table::isLoaded()` answers for every table and the flag is inert unless `deferLoading()` is on.
The partial reads it once and gates four reads on the result, each of which reaches the database: the `getRecords` read, the `getPaginator` read, the `getVisibleRecordActionsByRecordKey` map and the selectable-count query.

The body branch splits three ways where vendor's splits two.
`records is null` is the loading block; `records is empty` is the empty state; anything else is the table.
The null test comes first because Twig's `is empty` is also true for `null`, which is the conflation vendor avoids with `$records === null`.
The selection banner carries the same gate, which is vendor's own `$isLoaded` guard on it.

The controller closes the window: on connect it reads the flag back off the live store and, when the table defers and the flag is false, fires `loadTable` once.
A reconnect after the load reads true and fires nothing, so the once-guard is the store rather than a controller flag.
What it reads there is the **method's** answer, not the raw property — the prop and the getter share a name, so `PropertyAccessor` prefers `isTableLoaded()` when the hydrator dehydrates ([the general mechanic](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary), which `$isTableReordering` carries too).
That is the value the client wants: a table that does not defer publishes `true` however its flag stands, so the controller never fires an action into a window that was never open.

## Divergences

### The poll interval parses strictly and carries no Livewire modifiers

`wire:poll` reads a Livewire root, and the table partial does not own one — stamping the *page* would re-render everything on the page rather than the table.
So `poll()` is server-side configuration and the interval is client-side machinery: `getPollingIntervalMilliseconds()` hands the controller a number and the controller owns the `setInterval`.
The attribute is emitted only while the table is not being reordered, matching vendor's `(! $isReordering)` guard, and regardless of the deferred state, because vendor polls through the deferred window too.

Owning the timer is what makes that guard cost something Livewire's does not.
`wire:poll` is re-read whenever the attribute is morphed, so removing it stops the loop; the table partial's root carries no id, key or `data-live-ignore`, so a live re-render morphs it **in place** and Stimulus never re-runs `connect()` — a timer started there would keep firing `render()` through a drag, morphing the rows out from under SortableJS, which is exactly what the suppression exists to prevent.
So the loop is (re)started from `pollIntervalValueChanged()` rather than only at connect: Stimulus fires that callback when the attribute is added, changed, and removed (with the value's default), which stops the timer on the way into reorder mode, restarts it on the way out, and picks up a new interval from a `poll()` closure whose answer changed.
`#startPolling()` is idempotent (it stops first) and returns unless the host is resolved, so the callback Stimulus fires *before* `connect()` — the value observer starts first — leaves the start to `connect()`.

Two divergences ride on that.
The parse is **strict**: a number of milliseconds or seconds such as `500ms`, `10s` or `1.5s` parses, and anything else — a bare number, Livewire's minute unit `1m`, an empty string — throws `InvalidArgumentException`, where Livewire reads `m` as minutes and silently falls back to 2000 ms on the rest.
A table quietly polling at the wrong rate is invisible until it costs somebody a database, which is the fail-fast house rule's whole case.
One consequence of the reorder guard sitting *above* that parse in the template — `isReordering ? null : component.getPollingIntervalMilliseconds` — is that a malformed interval does not throw while a table is in reorder mode: the getter is never reached, so the page renders, and the exception arrives on the render that leaves the mode.
And Livewire's `.visible` / `.keep-alive` modifiers are **not** ported: both are properties of `wire:poll` itself, and the interval this port hands the browser is a number with nowhere to carry them.
