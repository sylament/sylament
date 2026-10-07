---
description: 'The host trait `CanPaginateRecords` that `HasTable` composes: the page and per-page state, `gotoTablePage()` and the query lane''s page read. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/CanPaginateRecords.php
owns:
  - packages/tables/src/Concerns/CanPaginateRecords.php
---
# Concerns\CanPaginateRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CanPaginateRecords` — `$tablePage`, `$tableRecordsPerPage`, `gotoTablePage()`, `getTablePage()`, `getTableRecordsPerPage()`, `dehydrateTableRecordsPerPage()`, `updatedTableRecordsPerPage()`, `paginateTableQuery()`](../../../packages/tables/src/Concerns/CanPaginateRecords.php) | [`Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php) | in progress | — | [detail](#the-page-state-has-its-query-lane-and-no-session-or-url-half) · [diverges](#gototablepage-replaces-livewires-paginator-trio) · [diverges](#per-page-is-validated-in-the-getter-on-every-read) · [diverges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [diverges](#the-recordsperpage-injection-is-nullable) · [diverges](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [semantics](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) · [diverges](#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) · [diverges](#a-single-scalar-identifier-fetch-joined-page-ordered-by-a-to-one-association-is-doctrines-limit-and-stays-one) |

The same file carries the pin for the *other* face of that pairing — the [wire vocabulary](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) of the two writable props.
It renders [`PaginatedSongsTable`](../../../packages/tables/tests/Fixtures/Twig/Components/PaginatedSongsTable.php), a live host that paginates and searches at once, and asserts the props blob against the two elements bound to it: `tableRecordsPerPage` is the **string** `'10'` and equals the `value` of the `<option>` the footer marked selected, and `tableSearch` is `''` against a search `<input>` that renders no `value` attribute at all.
A new fixture rather than a widened old one — the only other live host, `RecordActionsTable`, is unpaginated and belongs to the action tests, and the paginated `BrowsableSongsTable` is a plain Twig component that emits no props blob.
**A functional test could not have caught this**: UX's `TestLiveComponent::call()` posts `args` and nothing else, and the only method that sends an `updated` payload at all is `set()`, which sends the one prop the test names.
The client echoing a dirty model *of its own accord* — the round trip the mismatch actually breaks — therefore has no expression in a PHP test, in this suite or in the demo's.
Asserting the dehydrated blob against the rendered element is the whole regression pin.

The [wire bridges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) are pinned in the same file, called the way the dehydration pass calls them — with the getter's value, since that is what `PropertyAccessor` hands over.
`dehydrateTableRecordsPerPage()` answers the **string** `'10'` for the seeded default, `'all'` for the selected all-option, and `null` for a table whose page options are empty; `dehydrateTableSearch()` answers `''` where its getter answers null, and the untrimmed `'  ok computer '` where its getter answers `'ok computer'` — the two assertions that fail the moment either bridge starts mirroring its getter again.
Both `onUpdated` guards have a case apiece, each carrying both arms against a fetch counter: an echoed value leaves the page at 4 **and** the record cache warm, and the next real change resets to page 1 and flushes it.

The **query half** of pagination is the third such file, [`CanPaginateRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanPaginateRecordsTest.php), driving seven seeded songs through `->paginated([2, 5])` so the default option is `2` and four pages exist.
It asserts the true total and the sliced keys on page 1 *and* page 3 (reached through `gotoTablePage()`, which is also what proves the flush); a page past the end holding no records and the total anyway; the `'all'` option returning everything with `recordsPerPage` `'all'` and a last page of `1`; `Simple` reporting a **null** total with `hasMorePages()` true on a full first page and false on the short last one, which only a probe answers correctly without a count; `Simple` plus `'all'` degenerating to a known total; `Cursor` throwing; and `paginated(false)` handing back a plain array with `getTablePaginator()` null.
One case is the fetch join the Tools paginator exists for: three artists with three songs each, `leftJoin('artist.songs', 'songs')` with `addSelect()`, page size 2 — a raw `LIMIT 2` would answer two *rows* of one artist, so the case asserts two `Artist` records, a total of `3`, and a collection of three songs on the first of them.
A keyed twin sits beside it — a root whose identifier is an association plus a scalar, fetch-joining a collection two hops out, the gigs of the venue that is half its own identifier — which takes [the two-step read](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) instead and asserts the same shape on **both** of its pages: the sliced keys, the true total, the last page and a whole collection on a record of each, because the point of that read is that a keyed page does not differ.
Because a query-lane table is paginated by default, the sorting and search suites reach their records through `getRecords()` on the carrier rather than treating the return value as an array.

## Details

### The page state has its query lane, and no session or URL half

`Concerns\CanPaginateRecords` is the page number, the per-page option, the reset-to-page-one hook, the two getters the `records()` closure is handed, and `paginateTableQuery()`, the slice the query lane takes.
Two groups of Filament members are still not in it.

The query lane is a gate in `resolveTableRecords()`: an **unpaginated** table resolves its whole query into the keyed array it always did, and a **paginated** one returns [support's carrier](../support/pagination.md) instead, records keyed the same way, so the shape a query-lane table hands back is decided by [`Table::isPaginated()`](table.md#surface), which since record grouping landed is also false under `groupsOnly()`.
Filament calls `$query->paginate()` / `simplePaginate()` / `cursorPaginate()` on an Eloquent builder and returns Laravel's paginator.
Here the page is `setFirstResult()` / `setMaxResults()` on the builder **before** `getQuery()`, which is what transfers both onto the `Query`, and the fetch goes through Doctrine's own [`Tools\Pagination\Paginator`](../../../vendor/doctrine/orm/src/Tools/Pagination/Paginator.php) — imported as `DoctrinePaginator`, support's carrier having the same short name.
Wrapping it is why the slice is not a bare `LIMIT`: on a fetch-joined query a limit over the *joined rows* would hand back half a record's collection, where the Tools paginator limits a subquery over identifiers first and re-reads the whole rows.
**Which joins earn that treatment is decided by cardinality.**
Any join at all — `getDQLPart('join') !== []` — would be broader than needed, on the grounds that an extra subquery is cheaper than silently wrong rows, but the relation arm joins *for* the user, so "broader than needed" is not free: a to-one join added because someone clicked a sortable header would flip every such table into id-subquery mode for a join that multiplies no rows.
So the heuristic is cardinality-aware — [`JoinCardinality::joinsCollection()`](../../../packages/tables/src/Query/JoinCardinality.php) walks each join expression back to its class through the root entities and the joins before it, and answers true only for a **collection-valued** association.
The broad trade survives where it still applies: anything the mapping cannot classify — an arbitrary-class join, a join off an alias this builder never declared, a join on a property that is no association — counts as collection fetching, because there the two directions still cost differently, an extra subquery against silently truncated collections.

A **third** strategy sits beside those two, and the join heuristic is only half of what picks it.
Two shapes of identifier defeat Doctrine's paginator on that path, for two different reasons.
A record class keyed by **two or more fields** has no single identifier column at all, and `getSingleIdentifierFieldName()` answers with a `MappingException` from inside a getter mid-render.
A class keyed by a **single association** does have one, and Doctrine's walkers do serve it — right up to the identifier tiebreak's `ORDER BY <root>.<association>`, which the limit walker cannot re-emit on any platform outside its `ROW_NUMBER` branch.
Either root, on a query that joins a collection, is paged by [a two-step read tables runs itself](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column); every other table, single-**scalar**-`id` roots included, still goes through the Tools paginator.
The routing predicate reads metadata alone and never the platform, so both shapes take the two-step read on PostgreSQL too, where the second would have worked either way.
One private method makes that choice — `readTableRecordWindow()`, which [the trailing-group look-ahead](summarizer.md#the-trailing-group-is-followed-past-the-page-break-with-a-record-not-a-scalar) calls for its window of one, so the page and the probe cannot disagree about the same query.

The three [modes](can-paginate-records.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) differ in what they pay for the total, and the mode is read here, where it is consumed, rather than in the setter, because a `Closure` mode has no value at set time.
`Default` reads `DoctrinePaginator::count()` — a `COUNT` subquery that ignores the first/max already set — so the carrier carries a true total and a page past the end comes back **empty with that total intact** rather than clamped to the last page, which is what leaves the footer a way back.
`Simple` runs no count at all: it asks for one record more than the page holds, drops the extra, and reports `hasMoreRecords` from whether it arrived, support's carrier [taking a null total for exactly this](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both).
`Cursor` throws, naming the keyset carrier that would answer it.
The `'all'` option bypasses the Tools paginator on every mode — a plain `getResult()`, the total being how many records came back — which makes `Simple` **plus** `'all'` degenerate to a *known* total, the one shape in which a simple paginator has one.
A page size is an `int` in every ordinary case and a numeric **string** in the one the [strict ten-match](can-paginate-records.md#the-ten-per-page-default-is-matched-strictly) allows, so it is cast before the offset arithmetic rather than trusted.

The **session** half (`getTablePerPageSessionKey()`, the `session()->put()` in the updated hook) is a [deliberate omission](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it).
The **query-string** page name (`getTablePaginationPageName()`, which feeds Livewire's `WithPagination`) is the [URL binding](has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) that has a **todo** row of its own, and it is the one argument every Filament arm passes that `paginateTableQuery()` has nothing to pass: no page here is named in a query string.
The two `@deprecated` host getters (`getTableRecordsPerPageSelectOptions()`, `isTablePaginationEnabled()`) follow the project-wide rule and are not ported at all.

## Semantics

### A keyed root that joins a collection pages in two statements, because Doctrine's walkers key by one column

`Query\FetchJoinPageQuery::read()` is the window of a query that joins a collection under a root Doctrine's paginator cannot page: two or more identifier fields, which its `getSingleIdentifierFieldName()` sites refuse outright, or a single one that is an association, which it pages until the identifier tiebreak's order item.
It answers in **two** statements, which is the shape Doctrine's own `Paginator` uses and for the same reason — a to-many join multiplies rows, so a `LIMIT` over them slices a record in half.
A keyed root whose query joins **no** collection is not routed here at all: it keeps Doctrine's paginator, exactly as a single-scalar one does.

The first statement reads the page's **identifiers** through Doctrine's own `LimitSubqueryOutputWalker`, set as a hint on a clone of the builder: that walker projects every identifier column, where the tree walkers behind it project the one `getSingleIdentifierFieldName()` answers.
The second reads the **records** off a second clone, window cleared and [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#the-operations-and-their-dql) narrowing it to those keys, hydrated normally — so every collection comes back whole and the order is the builder's own, never the first statement's.
What comes back is a **list**: the records are `array_values()`d on the way out, because a query with `INDEX BY` on its root hydrates a string-keyed array and the callers here read `[0]`.
An empty first page answers `[]` without running the second.
Both steps are clones: the builder handed in is never windowed, narrowed or reordered, so a caller may read a second window off it.
The **total** is unchanged, a `Paginator::count()` through `CountOutputWalker`, which is composite-safe.

Three things a reader would otherwise have to re-derive.

**The walker's rows are remapped positionally and minted through `KeySelection::keyOfHydrated()`.**
The walker names its own result columns — an identifier association under its bare property name carrying the **raw** driver value, a scalar field under `<alias>_<field>` and converted — in identifier order and exactly as wide as the identifier, so the row is written onto the aliases this class asked `select()` for, in order, and handed to the selection that authored them.
It is keyed through `keyOfHydrated()`, which converts the raw association halves alone: `keyOf()` would read the already converted scalar fields through their types a second time, which mints another key for a custom type whose reader does not read its own PHP value back unchanged ([the second reader](../schemas/record-key.md#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)).
`RecordKey::fromValues()` is the wrong call here and fails **silently**: it prints without converting, so against a type whose stored form differs from its printed one it mints a key the decode refuses, `in()` renders that as `1 = 0`, and the page comes back empty with no error anywhere.
A row of any other width is a `LogicException` naming the class — a tripwire on Doctrine's row shape, not a recoverable case.

That tripwire guards **width**, and width only.
Every key here is minted server-side and then strictly decoded again by `in()`, so an identifier type outside [the decode contract](../schemas/record-key-contract.md#parameter-naming-and-what-an-empty-or-malformed-key-means) — one whose printed PHP form is a spelling its own `convertToPHPValue()` refuses and whose `convertToDatabaseValue()` refuses too, [a type refusing it on read only](../schemas/record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer) being read back through its own writer — has every key of the page dropped, and the page comes back empty the same silent way, beside a correct total.
That is the by-key seam's systemic property rather than this read's: selection, row actions and the aggregate batch mint and re-decode the same way, so a loud guard belongs where the decode contract lives, in schemas' `Identity\` seam, and not in one of its consumers.
It is written down here because the page is where it shows least.

**An order item that is exactly `<root>.<field>`, where the field is an owning-side to-one association with one join column, is swapped — in the identifier statement only — for a hidden `IDENTITY()` result variable.**
`LimitSubqueryOutputWalker` rewrites an order item's SQL from the **field** mappings alone (`generateSqlAliasReplacements()`), so an association path is re-emitted as `<table alias>.<fk>` against a derived table carrying only the aliased identifier columns — a driver error, and one [the identifier tiebreak](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) walks into on every derived-identity page **outside that walker's `ROW_NUMBER` branch**, since it orders by the association itself.
So the item becomes `addSelect('IDENTITY(root.assoc) AS HIDDEN sy_order_<n>')` with `ORDER BY sy_order_<n>` — a result variable, which is the route the walker does support and the one the hidden aggregate sort already travels.
Joining the target and ordering by its identifier field would work for a plain derived identity and not for a **chained** one, whose target's own identifier is another association; the result variable needs neither the join nor the recursion.
It is applied unconditionally rather than per platform, because the rewritten item is a plain result variable that the `ROW_NUMBER` branch orders by just as happily, and it never reaches the record statement, whose order stays the builder's.
`isAssociationWithSingleJoinColumn()` is the one condition the swap tests, and it is enough on its own: only an owning-side to-one mapping carries `joinColumns` at all, so the test already implies the to-one.

Anything else in the order list is left as written, and the four shapes are not left alone for the same reason.
Two of them — an inverse-side to-one, and a to-one whose target is itself keyed by two columns — Doctrine's own `SqlWalker` refuses as an `ORDER BY` path expression in **every** statement it walks, paged or not (`associationPathInverseSideNotSupported` and `associationPathCompositeKeyNotSupported`).
So no rewrite of them could ever ship a half-key-ordered page: the record statement keeps the caller's own order and would throw one statement later even if the identifier step had been rewritten.
What the condition buys is that the refusal arrives in front of the **first** statement, and the case that shows it is an **empty** window — there a rewrite would answer `[]` and a full page would still throw, so the empty one is what `FetchJoinPageQueryTest` pins.
The other two are genuinely the walker's own: an item reached off a **joined** alias, which fails in the driver exactly as it does today, and a comma list inside a single `orderBy()` part (`->orderBy('r.position DESC, r.venue', 'DESC')` is one part, of which only the head is read), which tables' own sort API cannot produce — it adds one item per `addOrderBy()` — but a hand-written query builder can.
Two details of the swap carry no test at all and are recorded rather than pinned: `setCacheable(false)` on the identifier step, which mirrors Doctrine's own paginator, and the `<root>.` prefix test that keeps a joined alias's item out.

**The two statements are a race, inherited rather than introduced.**
A record deleted between them shortens the page — exactly as it does under Doctrine's paginator, which reads its identifiers and its rows in two statements for the same reason.

One older line becomes load-bearing on this lane without changing: `HasBulkActions::pluckTableRecordKeys()` selects the identifier off the table's own query, fetch join and all, so the join multiplies its rows the way it multiplies the page's.
The `array_unique()` around its key map is what turns five rows back into two keys — a need as old as the fetch join, since a single-identifier root multiplies its rows the same way — and the keyed fetch-joined select-all is the round trip that exercises it.

One thing the shape makes unreachable in practice: a summary and a fetch join cannot share a table, because [summarizing a to-many join is refused](summarizer.md#a-to-many-join-on-the-summarized-query-is-refused-where-filament-inflates-in-silence) rather than silently inflated.
So the look-ahead's keyed arm is reached only under a `Summarizer::using()` summarizer, which is the escape that refusal names.

## Divergences

### `gotoTablePage()` replaces Livewire's paginator trio

Filament's host does not implement paging at all: it composes Livewire's `WithPagination` trait, which supplies `$page`, `gotoPage()`, `nextPage()`, `previousPage()` and `resetPage()`, keeps the number in the query string under a per-table page name, and re-renders on each.
UX LiveComponent ships no counterpart, so the page number is a `#[LiveProp] int $tablePage` here and `gotoTablePage(#[LiveArg] int $page)` is the one `#[LiveAction]` that moves it, clamped with `max(1, …)` and flushing the record cache.

The action is not the only writer, so the clamp is not the only guard.
[A URL-bound page](has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) is seeded straight onto the property, which the action never sees, so `getTablePage()` carries the read-side pair: `max(1, min($this->tablePage, intdiv(PHP_INT_MAX, $perPage)))`.
The **floor** is there because `?page=` coerces to `int 0` on a non-nullable prop; the **cap** is there because the same seeding accepts `PHP_INT_MAX`, and `page × perPage` then overflows to a float that Doctrine's `setFirstResult()` refuses — a bookmarked `?page=9223372036854775807` was a 500 until the cap landed.
`intdiv()` is computed against the *resolved* per-page, so it moves with the user's own choice, and the capped number reaches the wire the same way the floored one does, through [the getter the dehydration prefers](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).

One live action covers the trio because the footer already computes its targets: every button in the [Pagination component](../support/pagination.md) — first, previous, each windowed number, next, last — knows the page it means, so `nextPage()` and `previousPage()` would be the same call with arithmetic moved from the template into PHP.
`resetPage()` is likewise not a member: its three Filament callers (sort changed, per-page changed, search changed) each set `$this->tablePage = 1` inline here, which is one assignment against a member whose name would be the only thing it added.
The clamp is not in Filament — its `gotoPage()` accepts what the client sends — and it is here because the page is handed to a userland closure that will use it as an offset.

Permanent in mechanism.
If UX ever ships a pagination trait, this becomes a delegate; the `records()` closure's `page` injection does not change either way.

### Per-page is validated in the getter, on every read

Filament's `$tableRecordsPerPage` is a plain public Livewire property whose getter returns it verbatim, with `getDefaultTableRecordsPerPageSelectOption()` checking the session-restored value against the page options *once*, at seeding time.
Sylament stores the raw client string in a `#[LiveProp(writable: true)] ?string $tableRecordsPerPage` and re-derives the answer in `getTableRecordsPerPage()` on **every** call: null passes through, `'all'` passes through, a numeric string becomes an `int`, anything else becomes null — and the result must then appear in `getPaginationPageOptions()` or the table's `getDefaultPaginationPageOption()` is returned instead.

The reason is that the prop is writable, and a writable `LiveProp` is client input.
UX LiveComponent checksums the props it hydrates, but a **writable** one is exempt by design — that is what writable means — so the `onUpdated` hook is not a gate: it runs when the client *tells* it the value changed, and a forged hydration payload never calls it.
Validating in the getter makes the forgery irrelevant instead of trying to catch it: the value reaching a `records()` closure is always one the table declared, whatever the payload said.
That matters because the closure will use it as a `LIMIT`.

Two consequences, both intended.
On the **first** render the prop is `null` and the getter seeds from the table's default without writing, which is what the select renders as its selected option and what [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) asserts by checking the getter answers `10` while the property is still null.
From the second request on the property holds the option string the client sent back, because that is what [the wire bridge](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) puts on the wire and hydration writes straight onto it — a narrower claim than "until the user picks something", and one the getter is indifferent to, since it re-derives its answer either way.
And the type is `?string` rather than Filament's `int|string`, because that is what a `<select>` posts; the normalisation is the cast, and it lives in one place.

`updatedTableRecordsPerPage()` and `updatedTableSearch()` are kept as `onUpdated` hooks anyway, for the half validation cannot do: resetting to page 1 and flushing the record cache when the user changes either.
Filament's same-named methods are Livewire's magic `updated<Property>` hooks; here the binding is explicit in the attribute, which is the ordinary Livewire → UX translation.

### The writable props dehydrate in their elements' wire vocabulary

Filament needs no counterpart to this: Livewire's snapshot carries the **property**, and `getTableRecordsPerPage()` is a separate read the view performs.
Here the dehydration pass reads every `LiveProp` through Symfony's `PropertyAccessor` (`LiveComponentHydrator::dehydrate()`), which prefers the same-named **getter** — the [second face](has-table.md#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) of the preference that mints the host id, and on these two props a liability rather than a convenience.
Unbridged, the wire carried `getTableRecordsPerPage()`'s resolved option — the `int` `10` — while the `<select>` bound to the prop held the string `"10"`, and it carried `getTableSearch()`'s trimmed-or-null value — `null` — while the search `<input>` held `""`.

A model that disagrees with its element is permanently dirty.
`live_controller`'s `SetValueOntoModelFieldsPlugin` runs on attach and after every render, and for a non-multiple `<select>` it writes the element's value back into the store — where `ValueStore.set()` short-circuits on `===` and otherwise records a **dirty** prop.
String `"10"` against `int` `10` is not `===`, so every subsequent request carried `updated: {tableRecordsPerPage: "10"}` — a sort click, a page click, cancelling a row action's modal — which calls `updatedTableRecordsPerPage()` and resets the table to page 1.
That was the shipped defect: on the demo, opening a row's Delete modal on page 2 and cancelling it snapped the table back to page 1.

Both props therefore declare a `dehydrateWith:` bridge, and each puts on the wire the value its **element** speaks.
`dehydrateTableRecordsPerPage()` string-casts the resolved option the pass hands it, so the model is exactly the `value` of the `<option>` the footer rendered as selected (`5 → "5"`, `'all' → "all"`; `null` — an unpaginated table, or a paginated one with empty page options — stays null and renders no select at all).
`dehydrateTableSearch()` ignores its argument and returns the property raw, so `""` stays `""` against an `<input>` that renders with no `value` attribute.
Renaming the getters would fix the mismatch too, and is not available: `getTableRecordsPerPage()` and `getTableSearch()` are the Filament names migrating users grep for.

The raw wire the search takes is **not** available to the per-page half, which is worth stating because a reader will otherwise simplify its bridge to `return $this->tableRecordsPerPage;`.
The plugin's store → DOM write is unconditional for any model that is not `undefined`, and `setValueOnElement()` marks an `<option>` selected by string comparison — so a raw `null` model matches no option and the browser's selectedness algorithm picks the **first** one.
Measured on the demo with exactly that bridge in place: the select read `2` while the table under it showed 10 rows, and the next live action posted `perPage: "2"`, a value the user never chose.
A raw wire is stable only where the element's vocabulary contains the raw value; for the select that means the resolved option and nothing else.

Both `onUpdated` hooks also grew a no-op guard — `$previousValue ===` the property returns without resetting the page or flushing the cache.
It is belt and braces now that the wires agree, and it could not have substituted for the bridge: before it, `$previousValue` carried the wire's `10` while the property carried the echoed `"10"`, so the two were never `===`.
Its one visible cost is that a hook called with the value the prop already holds does nothing, which is what the Unit cases assert from both sides.

Permanent in mechanism: it follows from the `PropertyAccessor` read, and only a prop the client writes back needs it.

### The `recordsPerPage` injection is nullable

Filament types the injection `fn (): int | string`, but the property it reads is `public $tableRecordsPerPage = null` and nothing seeds it before the first render — `getDefaultTableRecordsPerPageSelectOption()` is read by the *view*, not by the getter — so an unseeded table hands the closure `null` against a non-nullable declaration.
Sylament types it `int|string|null` and means it.

Two paths reach null here, and only one of them is Filament's.
An **unpaginated** table returns null because there is nothing to slice by, which Filament cannot express at all (its injection is unconditional).
A **paginated** table with an empty `paginationPageOptions([])` returns null because its default page option is null too.
A paginated table with options never does: the getter falls back to the table's default, so the common case hands the closure an `int` and a closure may type-hint `int` if it only supports paginated tables — the fixture host does exactly that, with an `assert(is_int(...))`.
Declaring the null is the honest half of the same contract, and it is what lets a closure branch on "unpaginated" without asking the table.

### An output walker of the query's own is refused by name only where Doctrine's fallback cannot serve a keyed root

Doctrine falls back to tree walkers the moment a query sets `HINT_CUSTOM_OUTPUT_WALKER` itself — `LimitSubqueryWalker` for a page, `CountWalker` for a total — and both resolve the root through `getSingleIdentifierFieldName()`.
Two reads cannot survive that, and each has a predicate of its own.
A **keyed** root — two or more identifier fields, or one that is an association — whose query *also joins a collection* is read in two steps, and the first of them needs Doctrine's own limit walker: the user's would be silently dropped from exactly one of the two statements.
Turning output walkers off is not an escape either: `LimitSubqueryWalker` calls that same method and then refuses a foreign-key identifier **separately**, with a `RuntimeException` telling the caller to turn the output walkers back on.
The other read is a **composite** root's total, which is `CountWalker`, and which answers `getSingleIdentifierFieldName()` with a raw `MappingException` from inside a getter mid-render.

So those two shapes are refused, with a `LogicException` naming the table, the record class and the walker, at the three places that meet them: inside the private window read both paginator sites share, in the branch that takes the two-step route; in front of the total `paginateTableQuery()` reads for itself; and in [`getAllTableRecordsCount()`](has-table.md#the-all-records-count-answers-four-ways-where-filaments-answers-two).
The first site asks whether the root is keyed, the other two whether it is composite, and that difference is the whole of the design — the guard takes the record class its caller resolved rather than resolving one for itself, because only the caller knows which question it is asking.
The hint is read off the **built** `Query` rather than off the builder, because `Configuration::setDefaultQueryHints()` reaches only the former — an application-wide default walker is precisely the shape that would otherwise surface as a mapping error nobody could place.

What the refusal leaves alone is longer than what it refuses, and deliberately so.
A **single-scalar-identifier** table is untouched — both predicates answer null for it, so the guard returns before it can fire and the DQL is what it always was.
A keyed root that joins **no** collection reads its page through a plain windowed query, which honours the user's walker, in default and simple mode alike; only a **total** behind that window can still refuse.
A **single derived identity** — one association as the whole key — totals through `CountWalker`, which does resolve an association identifier as a path, so it is untouched wherever it is counted.
Simple mode's page asks for no total at all.
What that leaves is one shape the refusal does reach with no join in the query: a **composite** root's total — `paginateTableQuery()`'s in default mode, where the window is read first and the refusal then throws in front of the total, so no page reaches the caller, and `getAllTableRecordsCount()`'s, which asks the same question whether the table pages or not.
And the `recordsPerPage: 'all'` **page** is not refused, because it builds no Doctrine paginator: it resolves the query outright and counts what came back into support's own carrier, so there is nothing for a user's walker to collide with.
A **cold** `getAllTableRecordsCount()` over that same composite root still is — it is the count that refuses, not the page, and under `'all'` a warm one answers from the carrier the page already filled.
An earlier, wider draft of this guard fired for every keyed root and took three of those shapes down with it, which is why it is drawn exactly at the collision and nowhere wider.

### A single-scalar-identifier fetch-joined page ordered by a to-one association is Doctrine's limit, and stays one

The order rewrite the [two-step read](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) applies is not applied to the lane that read does not own.
A table whose root has one **scalar** identifier, whose query fetch-joins a collection and whose order names a to-one association path, meets the same `LimitSubqueryOutputWalker` rewrite and the same driver error, on every platform outside that walker's `ROW_NUMBER` branch — SQLite here, MySQL and MariaDB in production, since no installed DBAL platform defines `supportsRowNumberFunction()`.
Ordinary API reaches it — a column named after a **bare** to-one association and made `sortable()`, a `defaultSort('artist')` or a group on that name, all of which resolve to `<root>.<assoc>`, because only a *dotted* path ending in an association is refused and a dotless name never reaches the mapping walk at all.
What does not reach it is the identifier tiebreak, which on a single-scalar root orders by a field.
It is Doctrine's, and it stays: routing single-identifier pages through the keyed read would close it and is rejected, because a proven lane that nothing asked us to move is worth more than the closure.
A table meeting all three conditions joins the target and orders by its identifier field instead, or drops the fetch join.
