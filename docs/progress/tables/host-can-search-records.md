---
description: The host trait that holds the global search term and the per-column terms, and applies both to the query lane's builder.
ports:
  - vendor/filament/tables/src/Concerns/CanSearchRecords.php
owns:
  - packages/tables/src/Concerns/CanSearchRecords.php
  - packages/tables/src/Query/SearchTermSplitter.php
---
# Concerns\CanSearchRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$tableSearch` / `updatedTableSearch()` / `getTableSearch()` / `dehydrateTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [diverges](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`applySearchToTableQuery()` / `applyGlobalSearchToTableQuery()` / `extractTableSearchWords()`](../../../packages/tables/src/Concerns/CanSearchRecords.php), the last delegating to [`Query\SearchTermSplitter`](../../../packages/tables/src/Query/SearchTermSplitter.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [semantics](#search-is-the-global-term-and-one-term-per-column) · [diverges](#search-expressions-come-from-searchconstraint-and-the-word-splitter-is-a-static-helper) · [can-sort-records.md](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column) |
| [`getTableSearchIndicator()` — the global search chip](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords::getTableSearchIndicator()`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [semantics](#search-is-the-global-term-and-one-term-per-column) · [indicator.md](indicator.md#removal-is-structured-not-a-click-handler-string) |
| `hasTableSearch()` | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | skipped | — | [can-search-records.md](can-search-records.md#hassearch-is-not-ported-because-a-null-search-indicator-answers-it) |
| [Per-column search — the `$tableColumnSearches` bag with its `hydrateTableColumnSearches()` / `dehydrateTableColumnSearches()` pair, `mountTableColumnSearches()`, `afterTableColumnSearchesHydrated()`, `getTableColumnSearch()` / `getTableColumnSearches()`, `applyColumnSearchesToTableQuery()`, `handleTableColumnSearchUpdates()` and the `resetTableColumnSearch()` / `resetTableColumnSearches()` actions](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [semantics](#search-is-the-global-term-and-one-term-per-column) · [diverges](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) · [diverges](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot) |
| [`getTableColumnSearchIndicators()` — one chip per column term](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords::getTableColumnSearchIndicators()`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [diverges](#the-column-search-chips-follow-the-bags-key-order-and-carry-the-trimmed-term) |
| `getReservedTableColumnSearchKeys()` / `fillReservedTableColumnSearchKeys()` | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | skipped | — | [diverges](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) |
| [`getTableSessionKey('search')` / `getTableSessionKey('column_search')`](../../../packages/tables/src/Concerns/CanPersistTableState.php) — `getTableSearchSessionKey()` / `getTableColumnSearchesSessionKey()` | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| `shouldPersistTableSearchInSession()` / `shouldPersistTableColumnSearchInSession()` | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | skipped | — | [deprecated: override `table()`](../README.md#statuses) |
| [`resetTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords::resetTableSearch()`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [diverges](#resettablesearch-inlines-its-reset-where-filament-delegates-to-updatedtablesearch) · [host-has-filters.md](host-has-filters.md#filter-mutations-reset-the-page-and-flush-the-record-cache) |

## Semantics

### Search is the global term, and one term per column

Both halves of Filament's trait are ported, and they are two features rather than two settings: the global term is one input over every globally searchable column, where **per-column search** is an input per column, with a UI of its own (a row under the header) and a bag of its own.
`applySearchToTableQuery()` is where they meet — it applies the column terms first and the global term second, the order Filament applies them in.

The per-column half is the [seeded bag](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) and its [first-call hydration snapshot](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot); its query pass mirrors the global one word for word — the column's own `splitIndividualSearchTerms()` overriding the table's `splitSearchTerms()` wherever it answers non-null, an `Orx` per word, `andWhere()`-d only if the column joined it — over exactly the **individually** searchable columns the developer has not hidden, so a term left on a column the developer later hid, or on one that is only globally searchable, constrains nothing.
A column the *user* toggled off still constrains, exactly as it still renders an input.
It is what the `columnSearches` injection reads.

The global half composes like this.
A blank term is a no-op — `getTableSearch()` already answers null for it, so nothing is applied and the query is returned as it came.
A table with a `searchUsing()` closure hands it the builder and the term and stops there, columns and extra columns alike unread; that closure is the whole assembly.
`callSearchUsing()` returns `void`, Filament-exact: the closure constrains the builder it was handed, and a *replacement* builder returned from it is discarded — unlike [a `defaultSort()` closure](can-sort-records.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker), whose returned builder wins.
Otherwise the term is broken into **words** — `splitSearchTerms()` is on by default, and `SearchTermSplitter::split()` normalises the Unicode blanks Filament names (`\x{3164}`, `\x{1160}`) to spaces before `str_getcsv()` splits on them, so a `"quoted phrase"` survives as one word — and each word gets **its own `Orx`**: every globally searchable column applies [itself](interacts-with-table-query.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) into it, the table's [extra searchable columns](can-search-records.md#extra-searchable-columns-take-closures-that-return-their-expression) follow, and the group is `andWhere()`-d only if something joined it.
So the composition is `OR` within a word and `AND` between words — *every* word must match *something* — and a table with nothing searchable adds no `WHERE` at all rather than an empty parenthesis or a false predicate.
The loop skips a **hidden** column before asking whether it is globally searchable, Filament's own order, [as sorting does](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column).

The global chip, `getTableSearchIndicator()`, is labelled `Search: <term>` (Filament's `filament-tables::table.fields.search.indicator` at its English value, [hardcoded like the rest of the copy](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)), answers null when no term is set, and is a [contract method](interacts-with-table.md#a-member-is-on-the-contract-when-something-outside-the-host-trait-calls-it).
[`Table::getFilterIndicators()`](indicator.md#removal-is-structured-not-a-click-handler-string) pairs it with the `resetTableSearch` live action and puts it first in [the row](has-filters.md#the-chip-is-one-badge-and-the-delete-affordance-is-supports), so the chip's X clears the term and resets the page.
`getTableColumnSearchIndicators()` fills the slot beside it, second and ahead of the filters' own chips: one `<label>: <term>` chip per individually searchable column the developer has not hidden that holds a non-blank term, keyed by column name so `getFilterIndicators()` can pair each with `resetTableColumnSearch` and the `column` argument it takes.
On the records lane none of this runs: the `search` injection hands the closure a string and the closure decides what matching means.

## Divergences

### Search expressions come from `SearchConstraint`, and the word splitter is a static helper

Filament's `generate_search_term_expression()` / `generate_search_column_expression()` pair is Eloquent-and-driver-specific assembly, replaced by [`SearchConstraint::like()`](../../../packages/tables/src/Query/SearchConstraint.php) and its [PostgreSQL text cast](../schemas/search-constraint.md#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql).
`like()` itself lives in [`Sylament\Schemas\Query\SearchConstraint`](../schemas/search-constraint.md) — tables' class delegates — so forms' relationship search reuses it without depending on `sylament/tables`.
The word splitter takes the same shape one tier down for the same reason: Filament's body sits in a **host trait**, so the only way to reach it is to be a table host, and panels' global search is not one.
Hoisting it into `Query\SearchTermSplitter` — a pure static, the tier tables' other query helpers live at — makes it callable from a panel resource, and `extractTableSearchWords()` stays on the trait at its own visibility as a one-line delegate, so no caller and no override moves.

### The column-search chips follow the bag's key order and carry the trimmed term

Filament walks the table's columns and reads each one's term out of the bag, so its chips follow declaration order and carry the term as typed.
The port walks `getTableColumnSearches()`, the flat, trimmed map keyed by column name, so the chips follow the bag's key order and carry the **trimmed** term; keying is what lets `getFilterIndicators()` pair each chip with its column.

### A full seed replaces Filament's reserved-key list, and a blank is kept

Filament seeds `$tableColumnSearches` with **26** names only — `at`, `concat`, `constructor`, `entries`, `length`, `map`, `sort`, `values` and the rest of `getReservedTableColumnSearchKeys()` — because Alpine reads the bag through a JavaScript proxy, where a column named `length` would resolve to the array's own property rather than to the entry.
Every other name is left out on the reasoning its own comment states: *other names already read back as an empty search from a JSON array*.
And on a write it **forgets** a blanked key (`Arr::forget`) unless the key is reserved, in which case it is reset to `''`.

Neither half ports.
UX's client throws `Invalid model name` for a `data-model` path the published props do not carry, so a key that is missing — or one that was forgotten on the previous keystroke — is not an empty read but a thrown error on the next one.
`mountTableColumnSearches()` therefore seeds **every** individually searchable column, `''` by default and a dotted name **nested** (`artist.name` lands as `['artist' => ['name' => '']]`), and `resetTableColumnSearch()` writes `''` where Filament would unset.
That write is the one place the divergence costs something, so it is gated: Filament's `Arr::forget` is destructive only and cannot invent a key, while an always-write takes a **client-supplied** column name and would add one permanently — a merely global column's name accumulating in the bag, the session and the wire, and a dotted one (`title.x`) replacing a real leaf with a map.
So the action resolves the name against the table first and returns silently unless the column is individually searchable, which is exactly the seed's own predicate: the bag's key set can only ever be the seed's.
The seed runs again inside [the post-hydrate hook](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot), so a column that *became* individually searchable between two requests has its key before the render that binds an input to it.

The reserved list is then unnecessary rather than merely unported: the bag holds a key for every searchable column whenever any column is searchable, so it never serialises as a JSON array in the first place, which is the collision the list exists to prevent.

Filament's own limit is inherited unchanged: a column named `author` cannot be individually searchable beside one named `author.name`, because the nesting collides — the second write turns the first's string into an array, or the other way about, depending on order.

### The per-column change is read off a first-call hydration snapshot

A `LiveProp`'s `onUpdated` hook fires for a write to the **property**, never for a write to a path inside it — [the same fact the filter bag records](host-has-filters.md#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it) — and every per-column term is exactly such a nested write.
Filament has the opposite problem and no work to do: Livewire's `updatedTableColumnSearches()` receives the key that changed.

So the change is read off the bag itself.
`hydrateTableColumnSearches()` keeps the value it was handed **on its first call only** (`??=`), which is the bag as it arrived, before any update the request carries; `afterTableColumnSearchesHydrated()` — `#[PostHydrate(priority: -5)]`, below the panel stamp every panels page runs at `20` and above schemas' state pass at `-10` — compares the hydrated bag against it and runs `handleTableColumnSearchUpdates()` only when they differ.
Both sides are seeded and cast through the same two helpers before the compare, so a column that became searchable since the last request (a blank on both sides) and a leaf that arrived as a number rather than a string (`100` against `'100'`) are both *not* an edit.
The handler is the same choreography the global term's `onUpdated` runs — deselect if the table says so, page back to `1`, flush the record cache, write the session — so a per-column keystroke and a global one are indistinguishable downstream.

### `resetTableSearch()` inlines its reset where Filament delegates to `updatedTableSearch()`

Filament's `resetTableSearch()` is `$this->tableSearch = ''; $this->updatedTableSearch();`.
The port's repeats the hook's body instead (the deselection, `tableSearch = ''`, page one, the flush and the session write), because its `updatedTableSearch()` returns early when `$previousValue` equals the new term, and delegating would work only by the accident that the defaulted `null` never equals `''`.
The cost is that a host overriding `updatedTableSearch()` does not see its override run from the reset path.
