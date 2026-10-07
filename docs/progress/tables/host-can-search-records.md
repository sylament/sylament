---
description: 'The host trait `CanSearchRecords` that `HasTable` composes: the global search term, the per-column terms and the search the query lane applies. (draft)'
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
| [`CanSearchRecords` — `$tableSearch`, `updatedTableSearch()`, `getTableSearch()`, `dehydrateTableSearch()`, `applySearchToTableQuery()`, `applyGlobalSearchToTableQuery()`, `extractTableSearchWords()`](../../../packages/tables/src/Concerns/CanSearchRecords.php), the last delegating to [`Query\SearchTermSplitter`](../../../packages/tables/src/Query/SearchTermSplitter.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | in progress | — | [detail](#search-is-the-global-term-and-one-term-per-column) · [diverges](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [diverges](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [can-sort-records.md](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column) |
| [Per-column search — the `$tableColumnSearches` bag with its `hydrateTableColumnSearches()` / `dehydrateTableColumnSearches()` pair, `mountTableColumnSearches()`, `afterTableColumnSearchesHydrated()`, `getTableColumnSearch()` / `getTableColumnSearches()`, `applyColumnSearchesToTableQuery()`, `handleTableColumnSearchUpdates()`, `getTableColumnSearchIndicators()`, `persistTableColumnSearchesInSession()` and the `resetTableColumnSearch()` / `resetTableColumnSearches()` actions](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [detail](#search-is-the-global-term-and-one-term-per-column) · [diverges](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) · [diverges](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot) · [diverges](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`resetTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords::resetTableSearch()`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [detail](host-has-filters.md#filter-mutations-reset-the-page-and-flush-the-record-cache) |

The **query half** of search has a file of its own for the same reason, [`CanSearchRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanSearchRecordsTest.php), driving the public lane against three seeded songs (`Love Song`, `Silent Love`, `Noise`, with disagreeing play counts) whose titles overlap on purpose, so an `OR` that silently became an `AND` — or a group that silently became a second `OR` — changes which records come back.
Every case asserts the records **and** the composed `WHERE`, because SQLite matches `LIKE` case-insensitively for ASCII and the case arms would pass on rows either way: one word matching two records through the title column and a second word matching a third only through the `plays` column (the `OR` across columns); two words narrowing to the one record carrying both, and a two-word term matching nothing though each word matches something (the `AND` across groups); `splitSearchTerms(false)` binding `%love song%` as one phrase; a quoted `"love song"` binding the same thing while splitting stays on (the `str_getcsv` arm); a term joined by the two Hangul fillers splitting into two words, which is the whole point of the normalising regex — neither character is `\s`, so without it the pair is one word matching nothing; `searchUsing()` reaching the closure with the term, replacing the assembly and leaving no `LIKE` in the DQL at all; the extra-column arms above; a searchable table with nothing searchable on it composing **no** `WHERE`; an all-whitespace term leaving the DQL identical to an unsearched one and binding no parameters; the two case arms read as DQL and bound values (`song.title LIKE` with `%Love%` against `LOWER(song.title) LIKE` with `%love%`); and four parameters named `sylament_search_0`…`_3` across two words and two columns, with `getSQL()` called on the result so the whole assembly is proved to parse.

## Details

### Search is the global term, and one term per column

Both halves of Filament's trait are ported, and they are two features rather than two settings: the global term is one input over every globally searchable column, where **per-column search** is an input per column, with a UI of its own (a row under the header) and a bag of its own.
`applySearchToTableQuery()` is where they meet — it applies the column terms first and the global term second, the order Filament applies them in — which is what it existed as a one-line delegate to prepare for.

The per-column half is the [seeded bag](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) and its [first-call hydration snapshot](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot), both recorded below; its query pass mirrors the global one word for word — the column's own `splitIndividualSearchTerms()` overriding the table's `splitSearchTerms()` wherever it answers non-null, an `Orx` per word, `andWhere()`-d only if the column joined it — over exactly the **individually** searchable columns the developer has not hidden, so a term left on a column the developer later hid, or on one that is only globally searchable, constrains nothing.
A column the *user* toggled off still constrains, exactly as it still renders an input.
It is what the `columnSearches` injection reads.

The global half composes like this.
A blank term is a no-op — `getTableSearch()` already answers null for it, so nothing is applied and the query is returned as it came.
A table with a `searchUsing()` closure hands it the builder and the term and stops there, columns and extra columns alike unread; that closure is the whole assembly.
`callSearchUsing()` returns `void`, Filament-exact: the closure constrains the builder it was handed, and a *replacement* builder returned from it is discarded — unlike [a `defaultSort()` closure](can-sort-records.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker), whose returned builder wins.
Otherwise the term is broken into **words** — `splitSearchTerms()` is on by default, and `extractTableSearchWords()` is a one-line delegate to [`Query\SearchTermSplitter::split()`](../../../packages/tables/src/Query/SearchTermSplitter.php), which normalises the Unicode blanks Filament names (`\x{3164}`, `\x{1160}`) to spaces before `str_getcsv()` splits on them, so a `"quoted phrase"` survives as one word — and each word gets **its own `Orx`**: every globally searchable column applies [itself](interacts-with-table-query.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) into it, the table's [extra searchable columns](can-search-records.md#extra-searchable-columns-take-closures-that-return-their-expression) follow, and the group is `andWhere()`-d only if something joined it.
So the composition is `OR` within a word and `AND` between words — *every* word must match *something* — and a table with nothing searchable adds no `WHERE` at all rather than an empty parenthesis or a false predicate.
The loop skips a **hidden** column before asking whether it is globally searchable, Filament's own order — an arm that needs column visibility, and that ships [with its sorting sibling](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column).

Two members of Filament's do not port.
The `generate_search_term_expression()` / `generate_search_column_expression()` pair is Eloquent-and-driver-specific assembly, replaced by [`SearchConstraint::like()`](../../../packages/tables/src/Query/SearchConstraint.php) and its [PostgreSQL text cast](can-search-records.md#a-non-string-searchable-field-is-cast-to-text-on-postgresql).
`like()` itself lives in [`Sylament\Schemas\Query\SearchConstraint`](../schemas/search-constraint.md) — tables' class delegates — so forms' relationship search reuses it without depending on `sylament/tables`.
The word splitter took the same shape one tier down for the same reason: the body sat in a **host trait**, so the only way to reach it was to be a table host, and panels' global search is not one.
Hoisting it into `Query\SearchTermSplitter` — a pure static, the tier tables' other query helpers already live at — makes it callable from a panel resource, and leaves the trait method in place at its own visibility so no caller and no override moved.
Of the search **indicators**, the global one ships — `getTableSearchIndicator()` mints the `Filters\Indicator` chip for the current term, labelled `Search: <term>` (Filament's `filament-tables::table.fields.search.indicator` at its English value, [hardcoded like the rest of the copy](has-empty-state.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)), and is the [tenth contract method](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six).
[`Table::getFilterIndicators()`](indicator.md#removal-is-structured-not-a-click-handler-string) pairs it with the `resetTableSearch` live action and puts it first in [the row](has-filters.md#the-chip-is-one-badge-and-the-delete-affordance-is-supports), so the chip's X clears the term and resets the page.
`getTableColumnSearchIndicators()` fills the slot beside it, second and ahead of the filters' own chips: one `<label>: <term>` chip per **visible**, individually searchable column holding a non-blank term, keyed by column name so `getFilterIndicators()` can pair each with `resetTableColumnSearch` and the `column` argument it takes.
Two things differ from Filament's, both consequences of keying rather than listing: the chips follow the bag's key order rather than declaration order, and the label carries the **trimmed** term, since the map they are built from is the trimmed one.
On the records lane none of this runs: the `search` injection hands the closure a string and the closure decides what matching means.

## Divergences

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
A fixture column named `entries` — on Filament's list, and unlike `length` neither a SQL keyword nor a DQL function, so a `Song` field can carry it — pins that it round-trips like any other name.

Filament's own limit is inherited unchanged: a column named `author` cannot be individually searchable beside one named `author.name`, because the nesting collides — the second write turns the first's string into an array, or the other way about, depending on order.

### The per-column change is read off a first-call hydration snapshot

A `LiveProp`'s `onUpdated` hook fires for a write to the **property**, never for a write to a path inside it — [the same fact the filter bag records](host-has-filters.md#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it) — and every per-column term is exactly such a nested write.
Filament has the opposite problem and no work to do: Livewire's `updatedTableColumnSearches()` receives the key that changed.

So the change is read off the bag itself.
`hydrateTableColumnSearches()` keeps the value it was handed **on its first call only** (`??=`), which is the bag as it arrived, before any update the request carries; `afterTableColumnSearchesHydrated()` — `#[PostHydrate(priority: -5)]`, below the panel stamp every panels page runs at `20` and above schemas' state pass at `-10` — compares the hydrated bag against it and runs `handleTableColumnSearchUpdates()` only when they differ.
Both sides are seeded and cast through the same two helpers before the compare, so a column that became searchable since the last request (a blank on both sides) and a leaf that arrived as a number rather than a string (`100` against `'100'`) are both *not* an edit.
The handler is the same choreography the global term's `onUpdated` runs — deselect if the table says so, page back to `1`, flush the record cache, write the session — so a per-column keystroke and a global one are indistinguishable downstream.
