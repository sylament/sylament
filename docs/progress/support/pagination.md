# Pagination (support)

Two pieces, one feature: the carrier
[`Sylament\Support\Pagination\Paginator`](../../../packages/support/src/Pagination/Paginator.php)
— one page of records plus the counts a footer needs — and the
[`Pagination`](../../../packages/support/templates/components/Pagination/index.html.twig)
Twig component that renders it. Both landed with the table upgrades epic, which
is the only consumer today: a
[`records()`](../tables/has-table.md) closure receiving `page` and
`recordsPerPage` slices its own data and returns a `Paginator`, and the
[table partial](../tables/table.md) renders the component beneath the `<table>`.

The pair ports Filament's
[`views/components/pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php)
— but only the **view** half has a Filament source. Filament's carrier is
Laravel's `LengthAwarePaginator`, a framework class Sylament has no equivalent
of, so `Paginator` is a Sylament-only class whose shape is dictated by what the
ported view reads off it (`total()`, `currentPage()`, `firstItem()`,
`lastItem()`, `lastPage()`, `onFirstPage()`, `hasMorePages()`, `hasPages()`, and
the `elements` window `render()` builds through Laravel's `UrlWindow`).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament and Laravel sources this is diffed against:

- [`support/resources/views/components/pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php)
  — the rendered footer: overview, per-page select, the item list
- [`support/resources/views/components/pagination/item.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/item.blade.php)
  — the single item, a button-or-span with an icon or a label
- `Illuminate\Pagination\LengthAwarePaginator`, `Illuminate\Pagination\Paginator`
  and `Illuminate\Pagination\UrlWindow` — the counts, the unknown-total
  predicates and the elided page window `Paginator` reimplements

## Surface

Every point of both pieces. _Blocked by_ names what has to land first. _Detail_
carries up to three links: **detail** → the write-up in [Details](#details),
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics). A **skipped** row states its reason inline instead.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__construct(records, totalRecordCount, page, recordsPerPage, hasMoreRecords)` — a null total means unknown — plus the four refusals](../../../packages/support/src/Pagination/Paginator.php) | `LengthAwarePaginator::__construct()` and `Illuminate\Pagination\Paginator::__construct()` | done | — | [diverges](#the-carrier-is-first-party-rather-than-an-ecosystem-package) · [semantics](#the-constructor-validates-what-its-docblock-cannot-narrow) |
| [`getRecords()` / `count()` / `getIterator()`](../../../packages/support/src/Pagination/Paginator.php) | `LengthAwarePaginator::items()`, `count()`, `getIterator()` | done | — | — |
| [`getTotalRecordCount()` / `getPage()` / `getRecordsPerPage()` / `getLastPage()` — the first and the last are null when the total is unknown](../../../packages/support/src/Pagination/Paginator.php) | `total()`, `currentPage()`, `perPage()`, `lastPage()` | done | — | [diverges](#the-counts-are-named-in-full-not-abbreviated) |
| [`getFirstRecordNumber()` / `getLastRecordNumber()` — null on an empty page](../../../packages/support/src/Pagination/Paginator.php) | `firstItem()`, `lastItem()` | done | — | — |
| [`onFirstPage()` / `hasMorePages()` / `hasPages()` — the last two read `hasMoreRecords` when the total is unknown](../../../packages/support/src/Pagination/Paginator.php) | the same three across `LengthAwarePaginator` and `Illuminate\Pagination\Paginator` | done | — | [diverges](#one-carrier-expresses-both-shapes-rather-than-a-length-aware-and-a-simple-sibling) |
| [`getPageWindow()` + the `GAP` constant — refuses a paginator with no total](../../../packages/support/src/Pagination/Paginator.php) | `UrlWindow` at `onEachSide(0)` | done | — | [diverges](#the-page-window-is-a-flat-list-of-numbers-and-gaps-not-laravels-nested-elements-array) |
| [`withRecords()` — the re-keyed clone](../../../packages/support/src/Pagination/Paginator.php) | `setCollection()` | done | — | [semantics](#withrecords-is-how-a-readonly-carrier-gets-re-keyed) |
| [The `Pagination` component — overview, per-page select, page window, extreme links](../../../packages/support/templates/components/Pagination/index.html.twig) | [`pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | in progress | — | [detail](#the-component-renders-one-of-filaments-two-layouts) · [diverges](#the-page-buttons-are-hardcoded-to-the-tables-gototablepage-action) |
| [The four `SupportIconAlias` pagination constants](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [support-icon-alias.md](support-icon-alias.md#badge--breadcrumbs--rtl-aliases) |
| The four `*_RTL` alias constants and the `$isRtl` branch | [`pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | todo | — | [diverges](#the-footer-has-no-rtl-branch) |
| A `Pagination\Item` component of its own | [`pagination/item.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/item.blade.php) | skipped | — | Filament's item exists to be reused by two call sites in one file; here the item markup is six lines inlined at its three uses, and a component whose only job is to pick between an icon and a label would be indirection without a second consumer |
| Simple and cursor paging — the `$isSimple` branch, the standalone previous/next buttons | [`pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | todo | — | [table.md](../tables/table.md#three-pagination-setters-configure-machinery-the-closure-lane-does-not-have) |
| Number formatting and translated copy | `Number::format()`, `trans_choice()` in the same view | blocked | i18n ([table.md](../tables/table.md#the-empty-state-heading-hardcodes-no-records)) | — |
| Styles — `sy-pagination*` | [`resources/css/`](../../../vendor/filament/support/resources/css) | in progress | — | [detail](#three-corrections-to-the-stylesheets-port-accounting) |

The `Paginator` rows are covered by
[`PaginatorTest`](../../../packages/support/tests/Unit/Pagination/PaginatorTest.php),
which reads as arithmetic because that is what the class is: the counts of a
mid-list page, a `Generator` argument keeping its keys through
`iterator_to_array()`, the 1-based record numbers, the null pair on an empty
page, the three position predicates across a first, last and single page, and
the `'all'` option collapsing to one page while still reporting `1`–`12`. The
window has a case per branch — under eight pages listing every number, near the
beginning, near the end, and the middle with a gap on both sides — plus an
**overflow** page past the end, which is the case a table reaches by paging and
then narrowing its search, and which must still render a way back. `withRecords()`
is asserted to return a different instance with the counts intact and the
original untouched, and the four construction refusals run as one dataset. The
unknown-total shape adds five: a null total reporting a null last page while
`hasMoreRecords` drives both predicates and the record numbers still count off
the page, the flag ignored beside a real total, an empty simple first page
reporting no pages where a later page reports some, `getPageWindow()` refusing a
carrier with no total, and the flag surviving `withRecords()`.

The component is covered from the tables side rather than here, because support's
own fixture Kernel registers no TwigComponentBundle — the same split
[badge.md](badge.md) and [button.md](button.md) record, with the suite living
wherever the component's first consumer does. The
Integration
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php)
asserts the rendered footer: the overview text, the single gap, the active item's
`aria-current`, a numbered button's `gotoTablePage` live-action parameters, and
the per-page `<select>` bound to `tableRecordsPerPage` with the right option
marked selected.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**.

### The component renders one of Filament's two layouts

Filament's view emits **two** presentations at once and lets CSS choose: a pair
of standalone previous/next `<x-filament::button>`s (the mobile and
simple-paginator layout) *and* the numbered `<ol>`; likewise two per-page
`<label>`s, one compact and one prefixed, differing only in how the select is
wrapped. Sylament's component emits one of each — the numbered list and a single
labelled select.

Nothing is dropped in principle. The second per-page label is a
[`fi-compact`](../../styling.md) responsive variant, and it needs the
`input.wrapper` / `input.select` support components the port has not built (both
are **todo** rows in [README](README.md#components)), so today the select is a
bare `<select class="sy-pagination-per-page-select">`. The standalone
previous/next buttons are the **simple paginator's** whole UI, and the carrier
now expresses that shape — a null `totalRecordCount` means the total is unknown
([diverges](#one-carrier-expresses-both-shapes-rather-than-a-length-aware-and-a-simple-sibling))
— so what they wait on is the footer's own `$isSimple` branch. Both arrive with
the piece they wait on; the mobile layout is a stylesheet question that the
`sy-pagination-items` list can answer on its own.

### Three corrections to the stylesheet's port accounting

The stylesheet's commit (`a1680ed`) enumerates the vendor selectors it skipped,
and three lines of that accounting are wrong. A commit body cannot be corrected
after the fact, so the accurate tracing lives here — it matters because the
`:not(.fi-compact)` and responsive arms are exactly what this **in progress**
row still owes, and a reader reconciling
[`pagination.css`](../../../vendor/filament/support/resources/css/components/pagination.css)
against ours needs to know which side of the line each one is on.

**`.fi-pagination-records-per-page-select-ctn` was ported, not skipped.** Both
of its declarations — `col-start-2` and `justify-self-center` — sit on
`.sy-pagination-per-page`. Filament needs a container element because its select
is wrapped by the `input.wrapper` component; ours is a bare `<select>` inside
the `<label>`, so the container's *positioning* merged onto the label the way
the wrapper's inline-label treatment did. The element is gone; its behaviour is
not.

**The dropped select arms are the `:not(.fi-compact)` ones, and they are
responsive states.** `.fi-pagination-records-per-page-select` carries
`&:not(.fi-compact) { hidden }` at the top level and `@md:inline` / `sm:inline`
inside the two `@supports` blocks — the non-compact select is hidden until the
container is wide enough, then swapped in for the compact one. All three arms go
with the rest of the responsive trade, since we render one always-visible
select; naming only "both `.fi-compact` arms" as skipped describes half of it.

**`.sy-pagination-items`' `col-start-3` is carried over from the skipped
`.fi-pagination-next-btn`.** Vendor's `.fi-pagination-items` declares
`justify-self-end` and no column at all — `col-start-3` belongs to the
standalone next button, which we do not render. Dropping the selector without
its declaration would have left the item list unplaced in the three-column grid,
so it moved onto the element that now occupies that column.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### The constructor validates what its docblock cannot narrow

`$recordsPerPage` is a positive `int` or the literal string `'all'`, and the
property and getter say exactly that — `@var int|'all'`, `@return int|'all'`. The
**constructor parameter** deliberately does not: it is `int|string` with no
narrowing docblock, and the check is a runtime `if` in the body.

The narrowing is left off on purpose. At PHPStan level max, a parameter declared
`int|'all'` makes the guard that rejects everything else statically dead —
`$recordsPerPage !== 'all'` on a value the analyser already believes is `'all'`
is `notIdentical.alwaysFalse`, an error, and the only ways to silence it are an
inline ignore (banned in shipped source) or deleting the check. Deleting it is
the wrong trade: a docblock is not enforced at runtime, and this value arrives
from a **userland closure**, so the guard is the only thing standing between a
typo and a division by zero in `getLastPage()`. Narrow types on the way out where
they are checkable, a wide type plus a check on the way in.

### `withRecords()` is how a readonly carrier gets re-keyed

`Paginator` is `final readonly`, so the host cannot re-key the records in place
after the closure returns — and re-keying is not optional, since every record map
in the port is keyed by record key. `withRecords()` is the seam:
[`HasTable::resolveTableRecords()`](../tables/has-table.md) builds the keyed array
and gets back a **new** carrier with the same counts, which is what it caches.

Two consequences worth keeping. The paginator a `records()` closure returns is
never the one the template renders, so identity comparisons against it fail by
design. And the counts are carried through untouched — `withRecords()` does not
recompute `totalRecordCount` from the array it is handed, because the whole point
of the carrier is that the total is bigger than the page.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The carrier is first-party rather than an ecosystem package

[Principle §2](../../principles.md#2-prefer-the-ecosystem-over-invention) makes
adoption the default and building the case that has to be argued, and the
Symfony ecosystem has a pagination package — Pagerfanta — so this is the
argument.

What a pagination package sells is its **adapters**: Pagerfanta's value is
`DoctrineORMAdapter`, `DoctrineDbalAdapter`, `ElasticaAdapter` and friends, each
of which knows how to count a source and fetch one slice of it. Sylament needs
none of them, because in both of its lanes the slicing already has an owner. On
the **closure lane** the userland `records()` closure slices — it is handed
`page` and `recordsPerPage` and returns what it fetched, and an adapter would sit
between the closure and its own data doing nothing. On the coming **Doctrine
`query()` lane** the slicing is `setFirstResult()`/`setMaxResults()` plus a count
query, which the lane must own anyway to apply filters, sorts and searches to the
same builder. Strip the adapters and what is left of Pagerfanta is arithmetic
over four integers, which is what this class is — 180 lines, no dependency, and
no `Countable`/`IteratorAggregate` mismatch with the record shapes the toolkit
already carries.

That is the [named, checkable gap](../../principles.md#we-can-do-it-better-means-wrap)
the principle asks for: *the package's value is its adapters, and neither lane can
use one.* The same reasoning is why EasyAdmin — the closest Symfony-side analogue
— ships a paginator of its own rather than depending on one. Wrapping was
considered and rejected for the same reason: there is nothing to wrap, only
arithmetic to re-expose.

It reverses if a lane ever appears whose slicing is *not* already owned — a
third-party data source the toolkit paginates itself — at which point adopting
the package for its adapter is exactly right, and this class becomes the shape a
`PagerfantaAdapter` fills.

### One carrier expresses both shapes rather than a length-aware and a simple sibling

Laravel ships two classes and Filament's view type-tests between them —
`$isSimple = ! $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator`
— where the length-aware one knows its total and `Illuminate\Pagination\Paginator`
does not, answering `hasMorePages()` from a `hasMore` boolean it sets by fetching
one record more than the page needs. Here that is one class: `totalRecordCount`
is `?int` with null meaning *unknown*, and the `hasMoreRecords` constructor flag
carries what the extra record found.

A sibling `SimplePaginator` was the alternative, and what it costs is a union at
every seam the [records lane](../tables/has-table.md) already built —
`getTableRecords(): array|Paginator`, `getTablePaginator(): ?Paginator`, the
`$cachedTableRecords` property, `HasRecords`' two accessors, the `instanceof`
branch in `resolveTableRecords()` and the class name in the error it throws, and
the component's `paginator` prop — each widened, and each `instanceof` doubled,
to tell apart two classes differing in one field. The nullable total pays for
that with two nullable getters and a `getPageWindow()` that throws a
`LogicException` where a simple sibling would simply not have the method; the
seams stay single-typed.

`hasMoreRecords` is read **only** when the total is null. With a total the
arithmetic answers both predicates, so the flag is ignored rather than rejected —
a caller passing both gets the counted answer, which is the one that cannot be
wrong. The null arms are the simple paginator's own predicates: `hasMorePages()`
is the flag, and `hasPages()` is `AbstractPaginator`'s
`currentPage() != 1 || hasMorePages()`.

The closure lane gets simple pagination out of this for free — nothing to
configure, once the footer's `$isSimple` branch lands (the **todo** row above):
a `records()` closure that fetches one record more than the page needs and
returns a carrier built with `totalRecordCount: null` and `hasMoreRecords` is
simple-paginated, because the footer branches on what the carrier answers rather
than on a table setting. A mode setting belongs to the coming Doctrine `query()`
lane, where the toolkit owns the fetching and has to be told which shape to
fetch.

### The counts are named in full, not abbreviated

Laravel's paginator answers `total()`, `perPage()`, `currentPage()`,
`firstItem()`, `lastItem()`, `items()`; this one answers
`getTotalRecordCount()`, `getRecordsPerPage()`, `getPage()`,
`getFirstRecordNumber()`, `getLastRecordNumber()`, `getRecords()`. Nothing is
renamed *from Filament* — Filament never names these, it inherits them — so this
is a naming choice made once, against the [rename rule](../renames.md)'s bar.

Two reasons it goes this way. The toolkit's vocabulary is **record**, not *item*
([the model vocabulary collapses onto record](../renames.md#the-model-vocabulary-collapses-onto-record)),
and a paginator answering `items()` beside a table answering `getRecords()` would
be the only place the two words meet. And `get*` prefixes are the house form for
every getter in the port, Filament's included, so `total()` would read as a
Laravel import in a file that has no other. The names are recorded in the
[rename table](../renames.md#names-with-no-filament-source) as names with no
Filament source. A user migrating a Blade `$paginator->total()` has to rewrite it,
which is the cost; the call sites are in a footer nobody hand-writes twice.

### The page window is a flat list of numbers and gaps, not Laravel's nested `elements` array

Laravel's `render()` hands the view an `elements` array whose members are
*either* a string (the ellipsis) *or* an associative array of page → URL, so the
Blade template nests two loops and type-tests each element. `getPageWindow()`
returns a flat `list<int|string>` — page numbers with `Paginator::GAP` (`…`)
marking each elided range — and the Twig template is one loop with one
`is same as(gap)` test.

The nesting exists to carry **URLs**, which a live component does not have: every
item here is a `gotoTablePage` live action carrying a page *number*, so the
page → URL map would be a page → nothing map. Flattening also puts the gap
sentinel in one named place rather than leaving it as "whichever element happened
to be a string". The window itself is Laravel's shape at `onEachSide(0)` — the
setting Filament pins in `paginateTableQuery()` — reimplemented rather than
inherited: under 8 pages every number, otherwise first-four or last-four with two
caps at the far end, and the middle case capping both sides.

### The page buttons are hardcoded to the table's `gotoTablePage` action

Every clickable item in the component emits
`stimulus_action('live', 'action', parameters: {action: 'gotoTablePage', page: …})`,
naming a live action that lives on **tables**' host trait — from a **support**
component, which is the lower package and knows nothing about tables. The
per-page `<select>` is the same shape one level softer: its `data-model` is a
prop, `currentPageOptionProperty`, but the prop's **default** is
`'tableRecordsPerPage'`, also a tables name.

Filament has the same coupling and hides it better: its view interpolates
`gotoPage($page, $paginator->getPageName())`, a Livewire method the *paginator*
supplies the argument for, so the view names a method every `WithPagination`
component has. There is no equivalent here — the page name comes from a query
string this port does not bind, and the host method is ours to name.

It is left hardcoded rather than parameterised because a prop nothing varies is
worse than a literal: it would read as configurable while the only supported
value stayed the same, and the second consumer is who should decide the shape.
The select's prop is the exception because Filament already declared it, and it
is kept at Filament's default for the same reason Filament chose that default —
tables is the first consumer of both. Revisit when a second one appears
(pagination on a panels list page, or a widget): the action name becomes a prop
beside `currentPageOptionProperty`, and the default stays where it is for
compatibility.

### The footer has no RTL branch

Filament's view computes `$isRtl` from the panels translation and mirrors every
chevron, reaching four `*_RTL` alias constants for the purpose. Sylament's
renders the LTR chevrons unconditionally and ships none of the four constants.

The branch is unreachable rather than dropped: `$isRtl` reads
`__('filament-panels::layout.direction')`, a translated string from the panel
layer, and neither [i18n](../tables/table.md#the-empty-state-heading-hardcodes-no-records)
nor panels exists in the port — there is nothing to ask which direction the page
runs. The four constants follow the
[alias growth order](support-icon-alias.md#badge--breadcrumbs--rtl-aliases)
and land with the branch that reads them, not before. Provisional and mechanical:
whichever of the two prerequisites lands second brings this with it.

_See also: the global [rename table](../renames.md) —
`Sylament\Support\Pagination\Paginator` and its count getters are listed there as
names with no Filament source, alongside `gotoTablePage()`, the
Livewire-mechanism translation the footer's buttons call._
