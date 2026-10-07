---
description: 'The table''s search surface: global and per-column search, the search chrome and the `LIKE` constraint it builds (draft)'
ports:
  - vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php
  - vendor/filament/tables/resources/views/components/search-field.blade.php
owns:
  - packages/tables/assets/src/styles/components/search-field.css
  - packages/tables/src/Table/Concerns/CanSearchRecords.php
---
# CanSearchRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`searchable()` / `isSearchable()` / `getExtraSearchableColumns()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [semantics](#a-table-is-searchable-when-a-column-says-so-and-searchable-overrides-in-both-directions) |
| [The search chrome — `searchPlaceholder()`, `searchDebounce()`, `searchOnBlur()` and their getters](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [diverges](#getsearchdebouncemilliseconds-is-a-sylament-only-translation-of-filaments-duration-string) · [diverges](#searchonblur-renders-onchange-because-ux-live-component-has-no-blur-event) · [diverges](#the-search-field-flattens-supports-input-wrapper-into-two-classes) · [diverges](#items-center-is-lifted-onto-the-search-field-because-the-prefix-that-carried-it-is-gone) · [diverges](#the-search-input-adds-an-outline-none-vendor-does-not-have) |
| [`splitSearchTerms()` / `shouldSplitSearchTerms()`, `searchUsing()` / `hasSearchUsingCallback()` / `callSearchUsing()`, `applyExtraSearchConstraints()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [host-can-search-records.md](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) · [semantics](#extra-searchable-columns-take-closures-that-return-their-expression) · [diverges](#a-non-string-searchable-field-is-cast-to-text-on-postgresql) · [diverges](#a-search-term-is-not-escaped-for-like) |
| [`getSearchIndicator()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [interacts-with-table.md](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) · [detail](#the-unported-search-surface-splits-three-ways) |
| [`persistSearchInSession()` / `persistsSearchInSession()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`persistColumnSearchesInSession()` / `persistsColumnSearchesInSession()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`isSearchableByColumn()`, `getColumnSearchIndicators()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [host-can-search-records.md](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) · [detail](#the-unported-search-surface-splits-three-ways) |
| [The per-column search row — the first body row, one search field per individually searchable column and a spacer for every other cell](../../../packages/tables/templates/partials/Table/index.html.twig) + [`search-field.css`](../../../packages/tables/assets/src/styles/components/search-field.css) | [`index.blade.php`'s `fi-ta-individual-search-cell` row](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [semantics](#the-search-cells-carry-morph-keys-because-they-are-the-one-per-column-element-that-moves) · [diverges](#the-search-row-carries-the-responsive-classes-vendor-puts-only-on-the-header) · [diverges](#the-search-cell-drops-vendors-per-column-class-hook-and-the-rows-sy-ta-row) |
| The search field's Enter → refresh keyup | [`search-field.blade.php`](../../../vendor/filament/tables/resources/views/components/search-field.blade.php) | todo | — | Filament binds `x-on:keyup.enter="$wire.$refresh()"` so a term typed and confirmed before the debounce elapses applies at once. It was unported while the toolbar field was inline markup and stayed so when [`SearchField`](../../../packages/tables/templates/components/SearchField/index.html.twig) was extracted, since the extraction promised the toolbar's markup unchanged; it lands on the component and serves both call sites at once |
| `hasSearch()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | skipped | — | It answers *is a global term set*, which [`getSearchIndicator()`'s nullable return says once](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column); the per-column half needs no equivalent either, an empty `getColumnSearchIndicators()` being its own gate |
| `getExtraSearchableColumnRelationship()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | skipped | — | It answers *is this dotted name's head a relationship*, so Filament's caller can switch to `whereRelation`. Here [one resolution](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) answers that inside `SearchConstraint::forColumn()` for every tier at once, so an extra searchable column crosses an association with nothing to ask first — there is no caller left to hold the branch this member exists to decide |

## Details

### The unported search surface splits three ways

Filament's `Table\Concerns\CanSearchRecords` is 300 lines; the port keeps the declaration, the chrome, the query half and the global session half.
What the surface divides into stays worth naming, because one of the three arms is out whole and a second is half in.

**Session persistence** ships whole.
Both setters ship against the host-side [`CanPersistTableState`](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it): `persistSearchInSession()` arrived with that layer, and `persistColumnSearchesInSession()` with the bag it had nothing to persist without — its own `column_search` key, read at mount between the search and sort blocks and written back beside them.

**The query lane's members** have a **done** row of their own: `splitSearchTerms()`, which governs how the term is broken up *before* being turned into constraints, `searchUsing()` with its `callSearchUsing()`/`hasSearchUsingCallback()` pair — a closure handed the builder, which bypasses column assembly entirely — and `applyExtraSearchConstraints()`, which is what finally consumes `getExtraSearchableColumns()`: a string member goes through [`SearchConstraint::forColumn()`](../../../packages/tables/src/Query/SearchConstraint.php) — the same dispatch a column's own search columns take, so it joins the word's `OR` group as a `LIKE` or as a correlated `EXISTS` depending on what the name [resolves to](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) — and a closure member [returns its expression](#extra-searchable-columns-take-closures-that-return-their-expression) the way a column's `searchable(query:)` does.
So a table declaring `searchable(['author.name'])` searches by it — across the association, with no join declared in `query()` — rather than merely reading as searchable.
The composition around them — [one group per word, `AND`-ed](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) — is the host's.

One member of the group is **skipped** rather than pending: `getExtraSearchableColumnRelationship()`, which walks a dotted name to decide whether its head is an Eloquent relationship so the constraint can become a `whereRelation`.
Here that question is answered one layer down and for every tier at once — `SearchConstraint::forColumn()` resolves the name [against the mapping](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) and returns either a `LIKE` or a correlated `EXISTS` — so `searchable(['author.name'])` searches across the association with nothing declared, and no caller is left holding the branch this member exists to decide.

**The per-column arm ships**, and only one member of it stays out.
`isSearchableByColumn()` answers whether any column wants its own input — the search row's gate — and `getColumnSearchIndicators()` delegates to the host for one `Filters\Indicator` per column-scoped term, keyed by column name so the [indicators row](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) can pair each with `resetTableColumnSearch`.
`hasSearch()` is **skipped** on the same grounds its global sibling records: `getSearchIndicator()` is a nullable delegate to the host's `getTableSearchIndicator()` — the [tenth contract method](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) — where the null return carries what Filament's separate gate says, and an empty `getColumnSearchIndicators()` says it for the per-column half, so neither needs a companion method.

## Semantics

### A table is searchable when a column says so, and `searchable()` overrides in both directions

`isSearchable()` reads three sources in order and the order is the API: an explicit `searchable(bool)` wins outright, then any extra searchable columns, then any column that is **globally** searchable.
So a table with a `->searchable()` column renders a search field without the table itself being configured at all — which is how a migrating user expects it to work — while `->searchable(false)` on the table suppresses the field even when a column asks for it.

The subtlety is that `$isSearchable` is `?bool`, not `bool`: null means *nobody said*, which is what makes the column-derived answer reachable, and it is why `searchable(false)` also clears `extraSearchableColumns` — leaving them would make the second source contradict the explicit false.
A column marked `searchable(isGlobal: false, isIndividual: true)` deliberately does **not** make the table searchable, because it asked for its own input, not for the global field; it gets one of its own from [per-column search](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) instead.
All of it is Filament's, expressed the same way.

### Extra searchable columns take closures that return their expression

`searchable(['author.name', fn () => …])` takes closures beside names, and they join the search group the same way a column's `searchable(query:)` closure does — by **returning** an expression, not by mutating a nested builder.
The contract is one and the same, argued once in [interacts-with-table-query.md](interacts-with-table-query.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group): a returned `Expr` node or raw DQL string is added to the word's `Orx`, a closure that returns nothing adds nothing, and a closure that returns the **builder** — which `andWhere()` does, so `fn (QueryBuilder $query) => $query->andWhere(…)` returns one whether or not the author meant to — is skipped rather than added, because its constraint is already on the query, outside the `OR` group.
That skip is explicit in both places: `QueryBuilder` implements `Stringable`, so the `string|Stringable` test that admits raw DQL would otherwise hand it to `Orx::add()`, which refuses anything outside its `Comparison`/`Func`/`Andx`/`Orx` allow-list with an `InvalidArgumentException`.

The injections are Filament's three — `$query`, `$search`, and the `$searchQuery` alias for it — and a blank member is skipped before any of it, so a list built conditionally (`['title', $extra ?? '']`) does not resolve an empty path.
Filament's `blank($column)` guard is spelled `array_filter(…, filled(…))` over the list instead: the helper's `@phpstan-assert-if-false !null` makes a direct `blank()` call on a non-nullable member read as an impossible check at level max, where the same helper as an `array_filter` callback narrows the list and keeps the semantics exactly (a whitespace-only member is blank too).

### The search cells carry morph keys, because they are the one per-column element that moves

Every other per-column element in the table is stationary within a render: a header cell and a record cell are rebuilt together and idiomorph's positional pairing is right by construction.
The search cells are not.
A reorder re-`columns()` the table in the stored order and a toggle drops a cell outright, so the row's children are the one place in the table where the **set** and the **order** of siblings both change under a live morph — and idiomorph pairs unkeyed siblings by position (`isIdSetMatch` → `findIdSetMatch` → `findSoftMatch`).

Two failures fall out of that, and they need different keys.
After a reorder, a *dirty* input pairs with whichever column now occupies its position, so the term is applied to one column and typed into another — fixed by an `id` on the **cell**, which is what makes id-set pairing correct.
And when the leading spacer count changes — which is exactly what happens the moment a search matches nothing, on the default configuration — the input node is destroyed and rebuilt, losing focus and every keystroke typed during the round trip; fixed by a second `id` on the **input**, which keeps the node alive across the shift.

Both are the host-scoped `id` this repo already uses in place of Livewire's `wire:key` — the same translation [the record row and cell](table.md#the-row-and-cell-morph-keys-disagree-on-record-vs-records) carry two loops away, so this is parity arriving late rather than a divergence.
The toolbar's own field is deliberately left unkeyed: it has one instance with no siblings to be confused with, and [its markup is promised unchanged](#the-search-field-flattens-supports-input-wrapper-into-two-classes).

## Divergences

### `getSearchDebounceMilliseconds()` is a Sylament-only translation of Filament's duration string

Filament stores `searchDebounce('500ms')` and interpolates the string straight into the Blade attribute `wire:model.live.debounce.{{ $searchDebounce }}`, where Livewire parses it. ux-live-component's `data-model` modifier is `debounce(N)` with **N in milliseconds and nothing else**, so the string has to be parsed on the PHP side; `getSearchDebounceMilliseconds()` does that and is the value the partial renders.

`searchDebounce()` and `getSearchDebounce()` keep Filament's exact shape — the setter takes and the getter returns the duration string, defaulting to `'500ms'` — so a migrating call site is unchanged; the new member sits beside them as a derived read.
It accepts `500ms`, `1s`, a bare number (read as milliseconds), and returns 500 for anything it cannot parse rather than throwing: a debounce is a comfort setting, and a typo'd one should not take a page down.
Recorded in the [rename table](../translations.md#names-with-no-filament-source) as a name with no Filament source.
Permanent — the string is the API, the integer is the wire format.

### `searchOnBlur()` renders `on(change)`, because ux-live-component has no blur event

The sibling translation, one modifier over.
Filament's `searchOnBlur()` swaps the input's binding to `wire:model.live.blur`, which commits the field on **every** blur.
The partial renders `on(change)|tableSearch` instead: ux-live-component's `on()` modifier accepts `input` or `change` and throws on anything else — the whitelist is literal in `vendor/symfony/ux-live-component/assets/dist/live_controller.js`, in the `modelDirective.modifiers` switch — so there is no blur event to name.
The delta is real rather than cosmetic: `change` fires when a **changed** value leaves the field, or on Enter, so blurring an untouched input commits nothing where Livewire would have committed it.

The setter and its getter keep Filament's shape (`searchOnBlur()` / `isSearchOnBlur()`), so a migrating call site is unchanged and the intent the name states — *do not search while I type* — is served exactly.
Schemas already made this translation for `lazy()` ([has-state-binding-modifiers.md](../schemas/has-state-binding-modifiers.md#livewire-modifier-array-grammar-becomes-a-single-getdatamodel-data-model-string)), and it holds here for the same reason it holds there: the two client runtimes have incompatible binding grammars, and this one has no third option.

One consequence of the binding to keep in view: the search `<input>` renders no `value` attribute at all.
That is safe today — the morph preserves a modified model field's value, and no server-side path ever *clears* `tableSearch` — but the day a "reset search" action lands, it will null the prop while the DOM input keeps its stale text, because there is no attribute for idiomorph to reset against.
The fix is the one [`TextInput`](../forms/text-input.md) already carries: render `value` explicitly and **empty rather than absent** (`?? ''`).
Do it in the same change as the writer, not before — an unconditional `value` today would only re-render what the morph already preserves.

### A non-string searchable field is cast to text on PostgreSQL

[`SearchConstraint::like()`](../../../packages/tables/src/Query/SearchConstraint.php) emits `path LIKE :param` — or `LOWER(path) LIKE :param` on the case-insensitive arm — and, on PostgreSQL, casts a non-character path to text first.
The implementation itself lives in [`Sylament\Schemas\Query\SearchConstraint`](../schemas/search-constraint.md), with tables' class delegating.
Filament's `generate_search_column_expression()` does more on Postgres: it double-quotes each segment of the name and appends `::text`, because Postgres refuses `integer LIKE text` outright where MySQL and SQLite coerce silently.
So without a cast a `searchable()` integer column, portable in Filament, fails loudly at execution against Postgres while the same table against SQLite matches.

There is no DQL counterpart to lift.
`::text` is a Postgres cast written in SQL, and a DQL string is parsed by Doctrine before any platform sees it; the portable spelling would be a DQL function, and both candidates are wrong here.
`CONCAT(path, '')` changes what an index can serve, and `CAST` is not a DQL function at all — the parser's built-in string list is `concat`, `substring`, `trim`, `lower`, `upper` and `identity`, anything else arriving through `Configuration::addCustomStringFunction()`, which the toolkit cannot assume a consumer has called.
A platform-conditional emission would also have to happen *before* the parser runs, which is exactly where `SearchConstraint` already reads the platform for its case-insensitivity default — so that is where the cast arm sits, the paragraph below saying how.

The type-aware cast is what runs: schemas' `like()` wraps a non-character field in `CONCAT(path, '')` on PostgreSQL alone ([search-constraint.md](../schemas/search-constraint.md#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql)), so a searchable integer column matches on all three platforms, and the PostgreSQL pins assert the match.
The alias walk it needed is `QueryAliases`, shared with the refusal below.

Two escape hatches exist beside it, both first-class API rather than workarounds: `searchable(query:)` on the column hands the closure the builder and takes back whatever expression it wants (a registered `CAST`, a `LOWER(CONCAT(…))`, a platform-specific literal), and `searchUsing()` on the table replaces the whole assembly.
The **column-name** half of Filament's helper — the quoting and the JSON-path spelling — has no counterpart by construction: a DQL path is `alias.field` against mapped metadata, never a quoted table name, and JSON access is not in the grammar.

### A search term is not escaped for `LIKE`

The bound parameter is `'%' . $search . '%'`, so `%` and `_` typed by the user stay wildcards: searching `a_c` matches `abc`, and searching `%` matches every row with a non-null value in any searchable column.
This is Filament's behaviour exactly — its `generate_search_term_expression()` lowercases and returns, and the call site interpolates `"%{$search}%"` — and the port keeps it rather than quietly changing what a migrating table matches.

It is a usability quirk and not an injection risk: the term is a **bound parameter** on both sides, never concatenated into DQL, so the worst a crafted term does is match too much.
Escaping it properly needs a `LIKE … ESCAPE` clause (supported by the DQL grammar) plus a per-platform decision about the escape character, and it changes matching for every existing table — so if it lands it lands as an opt-in on the same setter that already governs case (`forceSearchCaseInsensitive()`'s neighbour), not as a silent fix.

### The search field flattens support's input wrapper into two classes

`fi-ta-search-field` carries no rule in Filament at all — it is a bare hook on a `<div>`, and everything visible comes from the `x-filament::input.wrapper` component it contains: `.fi-input-wrp` paints the chrome, an inner `.fi-input-wrp-prefix.fi-inline` holds the icon, and `input.fi-input.fi-input-has-inline-prefix` sits in a `.fi-input-wrp-content-ctn` beside it.
Support ships that component now ([input-wrapper.md](../support/input-wrapper.md)), but this field predates it and is not migrated onto it — a follow-up — and the field itself is [ported inline into the partial](README.md) rather than as a component, so four vendor elements collapse to two — `<label class="sy-ta-search-field">` holding the icon and the input directly — and three vendor rules collapse into `header-toolbar.css`'s two.

The metrics carry over unchanged, which is worth spelling out because one of them reads like a drift and is not.
Ours puts `gap-x-2` between the icon and the input; vendor's `gap-x-3` separates *multiple* prefix children and never shows here, because the search field's prefix holds one visible child — the icon, with a `wire:loading` indicator beside it that is `display: none` at rest and so creates no gap (and which this port does not render at all, loading affordances being [deferred](can-defer-loading.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them)).
The icon-to-input distance vendor actually computes is the inline prefix's `pe-2` plus the input's `ps-0` — 0.5rem, which is what `gap-x-2` is.
The leading `ps-3` is the prefix's, the trailing `pe-3` is the `pe` half of the input's `px-3`, `min-w-0` is lifted off `.fi-input-wrp-content-ctn`, and the `@supports (-webkit-touch-callout: none)` Safari-zoom block is vendor-verbatim, comment included.
The input's six `disabled:` / `dark:disabled:` arms — including the `[-webkit-text-fill-color:…]` pair iOS needs — are carried too: the element is a real `<input>`, so they are expressible on the flat DOM whether or not this call site ever disables it.

Two vendor rules cannot come across, and they are the remainder rather than a choice.
`.fi-input-wrp`'s disabled chrome (`&.fi-disabled { bg-gray-50 dark:bg-transparent }`) and its focus guard (`&:not(.fi-disabled):not(:has(.fi-ac-action:focus))`, which suppresses the wrapper ring while a prefix action holds focus) both key off DOM this partial does not emit — no `sy-disabled` on the field, and no action mounted inside it.
Both return once this field migrates onto the input-wrapper component, along with the two-layer structure that migration would restore.

### `items-center` is lifted onto the search field, because the prefix that carried it is gone

Vendor's `.fi-input-wrp` is a bare `flex`, so its `align-items` stays `stretch`; the `items-center` sits one level in, on [`.fi-input-wrp-prefix`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) (`hidden items-center gap-x-3 ps-3`), the box holding the icon.
[`header-toolbar.css`](../../../packages/tables/assets/src/styles/components/header-toolbar.css)'s `.sy-ta-search-field` is `flex items-center`: the class that ports the *wrapper* gained it, and the class that declared it has no port at all, because the element it styles is not emitted.

The lift is what keeps the icon where Filament draws it, not a restyle.
Vendor centres the icon *inside* a prefix box that stretches to the field's full height.
Flatten the prefix away and the icon becomes a direct child of the wrapper, where `stretch` degrades to `flex-start` for any item with a definite cross size — and the icon has one, `.sy-icon`'s `size-5`, 1.25rem against a field 2.25rem tall (the input's `leading-6` plus its `py-1.5`).
Without the lift the magnifying glass renders 0.5rem above vendor's.

The one element the two arrangements can disagree about is the input, and today they cannot.
Vendor's stretches inside `.fi-input-wrp-content-ctn`; ours is centred as a direct child.
Both are no-ops while the input is the tallest thing in the field — it is what sets the height — so they part only if the field is ever given a height of its own or a taller sibling, at which point vendor's input would grow and ours would stay at its natural height, centred.
Nothing sets either, and the divergence closes with the same migration onto the input-wrapper component the rest of the flattening waits on.

### The search input adds an `outline-none` vendor does not have

`input.fi-input` suppresses the UA outline in the focused state only — `focus:ring-0 focus:outline-none`.
`.sy-ta-search-input` carries both of those **and** a bare `outline-none`, which Tailwind v4 compiles to a resting `outline-style: none`.
No vendor declaration is behind it; the arm is ours.

It buys nothing visible.
A UA draws an input's outline while the element is focused, and `:focus-visible` is a subset of `:focus`, so vendor's `focus:` arm already reaches every state the resting one could.

The suppression **is** paired, and the replacement is vendor's own rather than something added to compensate for the extra arm: focus lands on the input and `.sy-ta-search-field:focus-within` paints `ring-2 ring-primary-600` (`dark:ring-primary-500`) around the whole field, exactly as `.fi-input-wrp`'s `focus-within:ring-2` does over there.
The field keeps a visible keyboard-focus indicator, so this is not the accessibility regression an unpaired `outline-none` would be — in neither codebase was the indicator ever the input's own outline.

What it does change is the *reach* of the suppression.
Vendor leaves every non-focused state alone, so an outline an application draws there — a `:user-invalid` ring, a forced-colors affordance — lands unopposed; here it has to beat `.sy-ta-header-toolbar .sy-ta-search-field .sy-ta-search-input`, (0,3,0).
Nothing in the toolkit draws one, so the arm is inert in both directions today: it removes no outline anyone renders and blocks no rule anyone writes.

Recorded rather than reverted, and provisional on exactly that inertness — it is an addition with no vendor counterpart and no reader, so it goes in the change that first wants a resting outline on this input, with nothing to migrate.

### The search row carries the responsive classes vendor puts only on the header

`hiddenFrom()` / `visibleFrom()` emit a breakpoint class on **both** the header `<th>` and the record `<td>`, because a `<td>` that stays while its `<th>` goes leaves the whole row misaligned below that breakpoint.
Vendor emits them on the `<th>` and on a `ColumnGroup`'s header cell only ([`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php)), and its own search `<td>` carries `fi-ta-cell` plus the two search hooks and nothing else.

Copying that here would break the thing the classes exist to protect.
Our search row is a row of real cells in the same table, so a search cell with no breakpoint class stays visible at a width where its header and its records have both gone — one column wider than everything above and below it.
So the search-row loop hoists `getHiddenFrom()` / `getVisibleFrom()` exactly as the record-cell loop does and spreads the same two entries into the cell's `classes()` call.

It is the rare divergence where *departing* from vendor is what preserves the rendering: the difference is downstream of the record cell's own divergence, not a fresh choice, and it would have to be reversed the same day that one was.

### The search cell drops vendor's per-column class hook, and the row's `sy-ta-row`

Two class names vendor's search row carries are deliberately absent.
The per-column hook `fi-ta-individual-search-cell-<kebab>` — a second class beside `fi-ta-individual-search-cell`, carrying the column's camel-then-kebab name — is not emitted: nothing in either stylesheet selects it — vendor's `cell.css` styles the bare `.fi-ta-individual-search-cell` and stops there — and the per-column handle a caller actually needs is already on the cell as its `id`, `<hostId>.table.column-search.<column>`, which is stable, addressable from CSS and JS alike, and unambiguous where a kebabbed class is not.

The cost of adding it would be nil, and that is worth saying plainly rather than implying otherwise: the port already ships the per-column body and header hooks `sy-ta-cell-<kebab>` / `sy-ta-header-cell-<kebab>`, both derived by [`Column::getKebabCasedName()`](column.md#the-hook-class-kebabs-each-dotted-segment-on-its-own), so the transform is one call away and its dotted-name fidelity is already paid for.
The reason is that the hook has no reader, not that it would be expensive.

The row itself carries `sy-ta-column-search-row` **alone**, where vendor's carries `fi-ta-row` beside it.
That is not thrift: `sy-ta-row` is the class every record row carries and a dozen shipped assertions read `tbody > tr` classes against an exact string, so putting it on a chrome row that holds no record would make "the rows" mean two different things in the stylesheet and in every test.
The stripe, the selected tint and the clickable marker are all `sy-ta-row` rules, and none of them has anything to say about a row of inputs.
