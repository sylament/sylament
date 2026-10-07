---
description: The host trait that remembers a table's browsing state in the session, and the table's `persistInSession()` shortcut over its flags.
ports:
  - vendor/filament/tables/src/Concerns/InteractsWithTable.php
  - vendor/filament/tables/src/Table/Concerns/CanPersistInSession.php
owns:
  - packages/tables/src/Concerns/CanPersistTableState.php
---
# CanPersistTableState (tables)

Filament has no trait of this name: its session reads and writes are `Concerns\InteractsWithTable::bootedInteractsWithTable()` and the `updated*()` hooks of each host concern, which `HasTable` composes here as one trait.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The session store: the `RequestStack` setter, `getTableSessionKey()`, `persistTableStateToSession()`, `readTableSessionState()`, `forgetTableSessionState()`](../../../packages/tables/src/Concerns/CanPersistTableState.php) | [`Concerns\InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [semantics](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [diverges](#every-session-read-and-write-is-a-no-op-without-a-session) |
| [The mount-time read: `mountTablePersistedState()`](../../../packages/tables/src/Concerns/CanPersistTableState.php) | [`Concerns\InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [semantics](#the-session-read-is-a-post-mount-hook-and-its-priority-is-the-precedence-rule) · [diverges](#a-stale-per-page-choice-is-forgotten-without-a-substitute-and-the-default-is-not-written-back) |
| [The per-prop writers: `persistTableSortInSession()`, `persistTableSearchInSession()`, `persistTableColumnSearchesInSession()`, `persistTableFiltersInSession()`, `persistTableRecordsPerPageInSession()`](../../../packages/tables/src/Concerns/CanPersistTableState.php) | [`Concerns\InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [semantics](#the-session-read-is-a-post-mount-hook-and-its-priority-is-the-precedence-rule) |
| `persistInSession()` | [`Table\Concerns\CanPersistInSession`](../../../vendor/filament/tables/src/Table/Concerns/CanPersistInSession.php) | blocked | `persistGroupInSession()` and `persistRecordsPerPageInSession()` | [detail](#persistinsession-waits-on-the-grouping-and-per-page-session-flags) |

## Details

### `persistInSession()` waits on the grouping and per-page session flags

Filament's `persistInSession()` turns every session flag on at once: `persistFiltersInSession()`, `persistSearchInSession()`, `persistColumnSearchesInSession()`, `persistSortInSession()`, `persistGroupInSession()`, `persistColumnsInSession()` and `persistRecordsPerPageInSession()`.
The port's `Table` has five of those setters; `persistGroupInSession()` ([grouping.md](grouping.md)) and `persistRecordsPerPageInSession()` ([can-paginate-records.md](can-paginate-records.md#the-per-page-choice-persists-with-no-opt-out)) do not exist, so the shortcut has nothing to forward two of its calls to.

## Semantics

### The session key is the host class, so two instances of one page share it

Filament keys each piece of remembered table state by `md5(static::class)` plus a suffix, and `getTableSessionKey()` keeps that shape under its own prefix: `sprintf('sylament.tables.%s_%s', md5(static::class), $suffix)`, where Filament's is `tables.{md5}_{suffix}`.
`md5(static::class)` names the **class**, so two instances of one component on one page share one remembered sort, and two different pages never do.
For the column manager the collision is the most visible one, because two relation managers of the same class under one parent record are a realistic page: hiding a column in one hides it in the other, and the two panels agree because they read one `columns` entry.
The [host id](has-table.md#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) cannot serve instead — it is regenerated per component instance, so it would remember nothing across a visit, which is the opposite failure.
Keeping Filament's collision is deliberate: the alternative is a table identity vocabulary that does not exist here either, and a collision that reproduces upstream's behaviour is easier to reason about than a novel one.

### The session read is a post-mount hook, and its priority is the precedence rule

Filament remembers seven pieces of table state, each behind a flag on the table: the sort, the search, the column searches, the filters and the grouping (all opt-in), and the column manager's state and the per-page option (both opt-**out**).
The port remembers six: grouping is not persisted ([grouping.md](grouping.md)), and the per-page option has [no flag](can-paginate-records.md#the-per-page-choice-persists-with-no-opt-out), so every paginated host with a session remembers it.
The column searches and the column state are [the per-column search bag](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) and [the bag the manager edits](column-manager.md#the-column-state-is-re-derived-against-the-definition-on-every-read).
All six suffixes go through the store's helpers, but only five are read by `mountTablePersistedState()`, a `#[PostMount(priority: 10)]` hook: the sixth, `columns`, is read in the manager's own mount hook at priority 4 instead, because the state it seeds has to settle before anything asks a column whether it is visible.
The per-page arm sits behind `isPaginated()`, as Filament's does.

UX's own query-string seeding is a `PostMountEvent` listener at priority 256 and every listener runs before the first `#[PostMount]` method, so a bound prop already carries the URL's value when the hook reads: the session fills only what the URL left blank (`blank($this->tableSearch)`, `blank($this->tableSort)`, `$this->tableFilters === []`, `$this->tableColumnSearches === []`), which is Filament's own precedence proved from the other end.
The per-column bag is read above [its own seed](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) at priority 3 for the same reason the sort is read above its heal: a stored bag is filled out by the seed rather than replaced by it.
The read runs below the filters' [query-string seed](host-has-filters.md#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults) at priority 20 and above the filters mount hook at 0, so a session-restored bag reaches the same normalizer a URL-seeded one does — and above [the sort heal](host-can-sort-records.md#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query) at priority 5, so a remembered sort naming a column the table has since dropped is validated by the same hook a URL-seeded one is, and forgotten in the same pass.

## Divergences

### Every session read and write is a no-op without a session

`getTableStateSession()` returns null for a request with no session at all (a console command, a sub-request, a hand-mounted test component), where Laravel's `session()` helper always resolves, so the trait degrades rather than throwing.

### A stale per-page choice is forgotten without a substitute, and the default is not written back

A remembered per-page option that is no longer in `getPaginationPageOptions()` is forgotten at mount rather than left to fail validation on every later visit; Filament removes it too, but returns `$pageOptions[0]` where ours returns null and lets [the getter](host-can-paginate-records.md#per-page-is-validated-in-the-getter-on-every-read) answer.
And the resolved default is **not written back**: Filament's mount assigns the resolved option onto `$tableRecordsPerPage`, ours leaves the prop null when the session holds nothing usable, so the session entry is only created by the user's first pick.
`persistTableRecordsPerPageInSession()` early-returns on a null prop, which is what makes that true on the mount path as well as the update one.
