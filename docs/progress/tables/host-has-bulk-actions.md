---
description: 'The host trait `HasBulkActions` that `HasTable` composes: the selection props, the selected-records resolution and select-all. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/HasBulkActions.php
owns:
  - packages/tables/src/Concerns/HasBulkActions.php
---
# Concerns\HasBulkActions (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`HasBulkActions` — the three selection props, `isTableRecordSelected()`, `hasSelectedTableRecords()`, `getSelectedTableRecords()`, `getSelectedTableRecordsQuery()`, `getAllSelectableTableRecordKeys()`, `getAllSelectableTableRecordsCount()`, `selectAllTableRecords()`, `deselectAllTableRecords()`](../../../packages/tables/src/Concerns/HasBulkActions.php) | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | in progress | — | [detail](#the-selection-is-server-canonical-and-re-derived-on-every-read) · [semantics](#a-selection-outlives-a-page-and-a-sort-and-not-a-filter-or-a-search) · [diverges](#deselectalltablerecords-clears-the-server-state-where-filaments-only-dispatches-an-event) · [diverges](#select-all-across-pages-is-a-state-writing-live-action-not-an-answered-call) |
| [Grouped selection — `getGroupedSelectableTableRecordKeys()`](../../../packages/tables/src/Concerns/HasBulkActions.php), the group checkbox | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | done | — | [grouping.md](grouping.md#the-whole-universe-arm-costs-one-query-per-group-on-the-page) · [diverges](grouping.md#both-selection-lanes-key-through-getstringkey-where-filaments-records-lane-reads-the-column-raw) |
| The deprecated `*TableBulkAction*` host shims — `mountTableBulkAction()`, `callMountedTableBulkAction()`, `replaceMountedTableBulkAction()`, `getMountedTableBulkAction()`, `shouldSelectCurrentPageOnly()`, `getTableBulkActions()`, … | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | skipped | — | Every one is a v4→v5 bridge delegating to the unified action API, and the project has never ported a deprecated Filament surface. What they front is built: a bulk mount is `mountAction($name, context: ['table' => true, 'bulk' => true])`, exactly what `mountTableBulkAction()` forwards to |

## Details

### The selection is server-canonical, and re-derived on every read

Three writable `LiveProp`s hold the whole selection — `$selectedTableRecordKeys` (wire name `selectedTableRecords`), `$deselectedTableRecords` and `$isTrackingDeselectedTableRecords` — and none of them is trusted.
The `updated` bag a live request carries is **not** checksummed (only the originals are), so a key in either list proves nothing about the record being on the page, extant, visible under the current filters, or selectable at all.
Every read re-derives:

- `normalizedSelectedTableRecordKeys()` runs on **every** read of either bag, dropping anything that is not a non-bool scalar, casting the rest to `string`, and re-listing with `array_values()`.
  A nested array, a `true`, a keyed map — all gone before a key is compared.
  The props are typed `list<string>` because that is what they are *meant* to hold; the normalizer is what makes the type true of client input.
- `isTableRecordSelected()` and `hasSelectedTableRecords()` are tracking-aware: while tracking, the answer inverts and reads the deselection bag, and "something is selected" is true by definition — an inverted empty exclusion set means *everything*.
- `getSelectedTableRecords()` re-applies the table's filters and search to the query it builds, and re-applies `checkIfRecordIsSelectableUsing()` to whatever comes back, in both universes.
  The [filter half is conditional](has-filters.md#one-filter-setting-drives-three-selection-behaviours); the selectability half never is.
- `tracksDeselectedTableRecords()` re-derives the **flag** itself, which is the one client value whose forgery *widens* the reach: an inverted selection with an empty exclusion bag means every row, so a `true` posted onto a table whose UI never offers tracking would resolve a bulk write to the whole filtered set — or, where `deselectAllRecordsWhenFiltered(false)` also turns the filter re-application off, to the whole unfiltered base query.
  The controller offers the inverted selection only when `canTrackDeselectedRecords()` and not `selectsCurrentPageOnly()`, and the reader is that same gate on the server: a table that cannot track at all, and a **query-lane** table that confines selection to its page, both read a forged flag as no tracking and fall back to the selected-keys bag.
  A `records()` table is page-only by construction and is deliberately left alone — its tracking arm resolves off the page collection the user was shown, so there is nothing to widen.
  Filament trusts the flag; this is a divergence, and it costs nothing, because every path our own chrome can take passes the gate.

Two universes, as everywhere else on this trait.
On the **records lane** the selection resolves off the current page collection — `array_intersect_key()` for the keys, `array_diff_key()` for the deselections, Filament's `only`/`except` spelled in PHP — so a key selected on page 1 does not resolve from page 2, the same asymmetry [`getTableRecord()`](has-table.md#gettablerecord-reads-fresh-when-the-render-cache-is-cold) already has.
On the **query lane** it is a fresh query, narrowed by [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#the-operations-and-their-dql) or, while tracking, `notIn()`, both built the way `resolveQueryTableRecord()` builds its single-key constraint.
A single-identifier class emits exactly the `IN (:keys)` / `NOT IN (:deselected)` over the qualified identifier this lane always emitted — now with the list **typed** by the identifier's own DBAL type — and a composite one emits the balanced OR of per-field ANDs that DQL's missing row-value `IN` forces.
A key that is malformed for the class is **dropped** rather than refused, so one stale entry in a selection bag cannot fail a bulk action over the rest of it, and the keys are de-duplicated on the way.
An empty bag means opposite things on the two arms, and the port has to say so, because Doctrine says neither: DBAL expands an empty list parameter to `NULL`, so **both** `IN (NULL)` and `NOT IN (NULL)` match nothing.
On the `IN` arm that is the right answer — an empty selection selects no rows — and `getSelectedTableRecords()` never reaches the query anyway, since it opens with the empty-and-not-tracking early-out and returns `[]` without touching the database.
On the `NOT IN` arm it is the exact inversion of the truth: an empty exclusion set means **every** row, and it is the commonest state there is, since the default select-all flips tracking on and empties both bags.
So the constraint is **omitted** rather than emitted while the deselection bag is empty.
Laravel gives Filament that for free — its grammar compiles `whereKeyNot([])` to `1 = 1` — and a literal port loses it: a bulk delete after select-all would delete nothing at all, silently.

`getSelectedTableRecordsQuery(false)` — the arm a bulk action takes when it means to write without hydrating — carries one extra constraint the fetching arm does not need.
Because that arm never hydrates, the selectability callback has nowhere to run afterwards, so it runs **first**: the query is cloned, walked, filtered through `isRecordSelectable()`, and the surviving keys are added as a second `IN (…)`.
Filament's `constrainQueryToSelectableTableRecords()` does the same, and without it a DQL bulk delete would happily delete rows the table refuses to let the user select — the client is the only source of keys on that path.

The resolved set is memoised in `$cachedSelectedTableRecords` for the rest of the request, and the two mutators (`selectAllTableRecords()`, `deselectAllTableRecords()`) null it, so a same-request read after a clear sees the clear.
Filament's cache is never invalidated, because Filament's clear [never touches the server state](#deselectalltablerecords-clears-the-server-state-where-filaments-only-dispatches-an-event).
The memo does **not** key on `$shouldFetchSelectedRecords`, Filament's exactly: call it once fetching and once not in the same request and the second call returns the first call's shape — records where keys were asked for, or the reverse.
No shipped caller does, since an action reads its selection once.

Two of Filament's moves are deliberately absent from the query.
It applies `applySortingToTableQuery()` to the selected-records query; ours does not, because the ordering changes no membership and the non-fetch arm's builder is handed to a bulk action that may turn it into a DQL `DELETE`, which an `ORDER BY` would make invalid.
And the `maxSelectableRecords` `limit()` is absent with the setting itself.

`getAllSelectableTableRecordsCount()` is the banner's ceiling, and the one member here typed to answer **null**.
With no selectability callback it is free — the page count where selection is page-only, the paginator's total otherwise.
With a callback set it is Filament's documented performance cliff: the whole filtered set is hydrated and filtered, once per render.
The one shape whose paginator carries no total is simple-mode pagination, and there it takes Filament's last arm and counts the filtered query, through [`getAllTableRecordsCount()`](has-table.md#the-all-records-count-answers-four-ways-where-filaments-answers-two) — which cannot throw on that path, since everything past the page-only branch has a query (`selectsCurrentPageOnly()` is true for every `records()` table, so the lane that has no total to count is also the lane that never gets there).
A simple-paginated table with selection on therefore pays one `COUNT` per render, exactly as Filament's does; the alternative was a ceiling-less banner on a table that can perfectly well be counted.

So the `?int` is contract slack rather than a case this trait reaches: it is typed nullable because [the contract](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) lets a host satisfy it without this trait, and because the chrome has to render *something* for a host that answers null — the banner falls back to counting the page, and the select-all-across-pages link, which needs a ceiling, does not appear.

Not ported, with the settings they belong to: chunked resolution (`chunkSelectedRecords()`, `lazyById()` — no shipped action asks for it and Doctrine's iteration story differs), the `maxSelectableRecords` enforcement in both universes, `resolveSelectedRecordsUsing()`, and the grouped key enumeration.
Each has a [row](#surface) or a [table-side row](has-actions.md#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed).

### A composite selection larger than one batch is read in batches

`getSelectedTableRecords()` reads a selection over a record class keyed by several fields in batches of `IdentifierConstraint::batch()`, at most 500 keys a statement — 500 for two to four identifier fields, fewer above that ([the chunk rule](../schemas/record-key-contract.md#the-chunk-rule)) — instead of one statement over the whole bag.
The bags are client input, and one statement over them cost time quadratic in their length.
A forged 10 000-key `selectedTableRecords` held a request for about 23 s, where a single-identifier table answered in about 23 ms.
On PostgreSQL the tracked arm's `NOT (… OR …)` crossed the JIT threshold and ran for minutes.
Three shapes follow:

- **The selected bag** is batched.
  Each batch runs the query `getSelectedTableRecordsQuery()` would, narrowed to its keys.
- **A tracked deselection** that fits one batch stays one `notIn()` statement.
  A larger one is subtracted in PHP from a pluck of every key the table admits (one statement binding no key), and the rest is read in batches, since an exclusion cannot be split across statements.
  The one-batch exclusion compares each deselected key decoded and bound by its field's type, so a spelling the codec accepts but no row mints — a decimal `1.0` for `1`, a case variant under a case-insensitive collation — excludes the row; the larger bag is subtracted by string, as the checkbox compares, and does not.
  Such a spelling never comes from a rendered checkbox, so only a forged bag sees the two arms disagree, and the only row it can affect is one rendered ticked, which the exclusion spares.
  The one-statement exclusion is a sequential scan on PostgreSQL 18, and from roughly 40 000 rows a 101–500-key one crosses `jit_above_cost`, where JIT adds about 130–280 ms to it ([measured](../schemas/record-key-contract.md#the-chunk-rule)).
- **`fetchSelectedRecords(false)` beside `checkIfRecordIsSelectableUsing()`** hydrates each batch once and keys the selectable records in PHP.
  `getSelectedTableRecordsQuery(false)` binds them back as a second key list instead.

The hydrated records come back in table order within a batch, and batch after batch across them; Filament's own chunked read comes back in id order, not the table's.
Each record comes back once: two spellings of one key that the database compares equal — a decimal `1` and `1.0`, or a case variant under a case-insensitive collation — can land in different batches and hydrate the same managed record twice, so the batches' records are de-duplicated by identity before the selectability filter, as the single statement returned such a row once.
`getSelectedTableRecordsQuery()` is unchanged: it returns one builder, a custom bulk action's `$selectedRecordsQuery`, so it binds every selected key.
A bulk action over a large composite selection reads `$selectedRecords` instead.
A single-identifier selection is untouched: one statement, one array parameter.

## Semantics

### A selection outlives a page and a sort, and not a filter or a search

Which state changes clear the selection is not derivable from the code that clears it, because the clearing lives in two `updated*` tails rather than anywhere a reader would look.
The rule, ported from Filament verbatim: a **filter** change and a **search** change deselect everything (when `deselectAllRecordsWhenFiltered()` is on, the default); a **sort**, a **page** change and a **per-page** change do not.
The hook points are `handleTableFilterUpdates()` — already the single tail of all five filter mutations — and `updatedTableSearch()`, in both cases immediately before the existing page reset.

A third site is Sylament's own: `resetTableSearch()`, the clear button's `#[LiveAction]`, deselects on the same terms.
Filament has no such action — its search input is a live model write, so clearing it goes through `updatedTableSearch()` like any other edit — and without the splice the port would have two channels to one state change disagreeing about the selection.

Nothing is deliberately absent from that list: `sortTable()`, `gotoTablePage()` and `updatedTableRecordsPerPage()` reset the page and flush the record cache without touching the three props, so a selection made on page 1 is still a selection on page 3.
That is the whole reason the query lane resolves by key rather than from the page collection.

Two consequences worth stating.
A selection can outlive the page that showed it — including onto an overflow page with no rows at all, since a page past the end [renders empty with the total intact](host-can-paginate-records.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) — so the chrome that clears it has to stay reachable from an empty page.
And on the records lane a surviving cross-page selection resolves to **nothing**, because that lane resolves off the current page collection; the keys are still held, still round-trip, and still count in the banner, but `getSelectedTableRecords()` returns only those on the page in front of you.

## Divergences

### `deselectAllTableRecords()` clears the server state, where Filament's only dispatches an event

Filament's body is one line — `$this->dispatch('deselectAllTableRecords')->self()` — and the PHP props are left holding the old selection.
The Alpine component clears *its* sets and the emptied selection reaches the server on the next request.
So anything reading `$selectedTableRecords` later in the same request — after the deselect-on-completion hook of a bulk action, say — sees a selection that has just been cleared.

Ours writes the three props to their empty defaults and nulls the resolution cache.
There is no browser event and no listener, because there is nothing for one to do: every call site is already inside a live request (the two `updated*` tails, and an action's deselect-after-completion), so the response's re-render carries the cleared props, the client store reinitialises from them, and the table controller repaints from that.
This is the selection's central architectural divergence stated at its smallest: Filament's selection is client-authoritative with the server reading a copy, ours is server-canonical with the client holding a working copy.

### Select-all across pages is a state-writing live action, not an answered call

Filament's client, on the fallback select-all path, does `this.selectedRecords = new Set(await $wire.getAllSelectableTableRecordKeys())` — a Livewire method call whose **return value** the client stores.
UX LiveComponent has no such channel: a `#[LiveAction]` re-renders, it does not answer.

So the port inverts the direction.
`getAllSelectableTableRecordKeys()` still exists with Filament's four-arm body, but its caller is a `#[LiveAction] selectAllTableRecords()` on the same trait, which enumerates the keys and writes them into `$selectedTableRecordKeys` itself.
The cost is identical — one round trip either way — and the props stay canonical, which is the [whole selection architecture](#the-selection-is-server-canonical-and-re-derived-on-every-read).
The action must end in an ordinary re-render for the client to see the result; short-circuiting the response would leave the checkboxes showing the old state.

The cheap select-all path does not come here at all.
With deselect-tracking available (the default) the client flips `isTrackingDeselectedTableRecords` and empties both lists through the value store — three deferred writes, **no request** — so "all 999 999 selected" costs nothing and no key list ever crosses the wire.
This action is the fallback for the two cases tracking is off in: `trackDeselectedRecords(false)` (or `deselectAllRecordsWhenFiltered(false)`, which [forces it](has-filters.md#one-filter-setting-drives-three-selection-behaviours)), and `selectCurrentPageOnly()`, where the enumeration is the page's keys anyway.

One Filament caution ports with it: that enumeration has **no limit and no chunking**.
On a filtered set of a million rows it returns a million strings and puts them all in a prop.
