---
description: 'The host trait `CanPersistTableState` that `HasTable` composes: the session key, the mount-time read and the per-prop session writers. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/InteractsWithTable.php
owns:
  - packages/tables/src/Concerns/CanPersistTableState.php
---
# CanPersistTableState (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Table state in the session — `CanPersistTableState`: the `RequestStack` setter, `getTableSessionKey()`, `persistTableStateToSession()`, `readTableSessionState()`, `forgetTableSessionState()`, the five per-prop writers and the `#[PostMount(priority: 10)]` read hook](../../../packages/tables/src/Concerns/CanPersistTableState.php) | [`Concerns\InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |

## Divergences

### The session key is the host class, so two instances of one page share it

Filament persists five pieces of table state in the Laravel session, each keyed by `md5(static::class)` plus a suffix: the sort (`persistsSortInSession()`, opt-in), the search and the column searches (`persistSearchInSession()` / `persistColumnSearchesInSession()`, opt-in), the filters (`persistsFiltersInSession()`, opt-in), the [column manager's state](column-manager.md#the-session-is-written-at-the-end-of-every-mutation-rather-than-once-per-request) (`persistColumnsInSession()`, opt-**out** — the only *flag* of the five that defaults on, and the only flag that gates its write without gating its read), and the per-page option — **unconditionally**, with no setter to turn it off.
The column searches and the column state are [the per-column search bag](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) and [the bag the manager edits](host-has-column-manager.md#the-column-state-is-re-derived-against-the-definition-on-every-read).
All six suffixes go through [`CanPersistTableState`](../../../packages/tables/src/Concerns/CanPersistTableState.php)'s two helpers, but only five are read by its `#[PostMount(priority: 10)]` hook: the sixth, `columns`, is read in the manager's own mount hook at priority 4 instead, because the state it seeds has to settle before anything asks a column whether it is visible.
`getTableSessionKey()` is `sprintf('sylament.tables.%s_%s', md5(static::class), $suffix)`, Filament's shape verbatim, and per-page persists whether or not anything opted in, so every **paginated** table host with a session starts remembering per-page choices — the `isPaginated()` gate around that arm is Filament's too.

There is a seventh suffix that does not exist, and its absence is a port of an absence: **grouping** is persisted by neither codebase.
A grep of the vendor tree for `persists*InSession` returns filters, search, column searches, sort and columns and nothing else, so the port adds no writer, no suffix and no setter — [the URL is where a grouping is kept](grouping.md#nothing-persists-a-grouping-in-the-session-and-filament-does-not-either), and a case in `TableSessionPersistenceTest` fails the moment a `…_grouping` key appears.

The key is the divergence, and it is inherited rather than chosen: `md5(static::class)` names the **class**, so two instances of one component on one page share one remembered sort, and two different pages never do.
The [host id](has-table.md#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) cannot serve instead — it is regenerated per component instance, so it would remember nothing across a visit, which is the opposite failure.
Keeping Filament's collision is deliberate: the alternative is a table identity vocabulary that does not exist here either, and a collision that reproduces upstream's behaviour is easier to reason about than a novel one.

The mechanism is a `#[PostMount(priority: 10)]` hook, and the priority *is* the precedence rule.
UX's own query-string seeding is a `PostMountEvent` listener at priority 256 and every listener runs before the first `#[PostMount]` method, so a bound prop already carries the URL's value when the hook reads: the session fills only what the URL left blank (`blank($this->tableSearch)`, `blank($this->tableSort)`, `$this->tableFilters === []`, `$this->tableColumnSearches === []`), which is Filament's own precedence proved from the other end.
The per-column bag is read above [its own seed](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) at priority 3 for the same reason the sort is read above its heal: a stored bag is filled out by the seed rather than replaced by it.
The read runs above the filters mount hook and below the filters' [query-string seed](host-has-filters.md#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults), so a session-restored bag reaches the same normalizer a URL-seeded one does — and above [the sort heal](host-can-sort-records.md#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query) at priority 5, so a remembered sort naming a column the table has since dropped is validated by the same hook a URL-seeded one is, and forgotten in the same pass.

Three narrower divergences ride on it.
Every write is a **no-op without a session** — `getTableStateSession()` returns null for a request with no session at all (a console command, a sub-request, a hand-mounted test component), where Laravel's `session()` helper always resolves — so the trait degrades rather than throwing.
A remembered per-page option that is no longer in `getPaginationPageOptions()` is forgotten at mount rather than left to fail validation on every later visit; Filament removes it too, but returns `$pageOptions[0]` where ours returns null and lets [the getter](host-can-paginate-records.md#per-page-is-validated-in-the-getter-on-every-read) answer.
And the resolved default is **not written back**: Filament's mount assigns the resolved option onto `$tableRecordsPerPage`, ours leaves the prop null when the session holds nothing usable, so the session entry is only created by the user's first pick.
`persistTableRecordsPerPageInSession()` early-returns on a null prop, which is what makes that true on the mount path as well as the update one.
