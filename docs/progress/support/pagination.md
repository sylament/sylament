# Pagination (support)

Two pieces, one feature: the carrier
[`Sylament\Support\Pagination\Paginator`](../../../packages/support/src/Pagination/Paginator.php)
— one page of records plus the counts a footer needs — and the
[`Pagination`](../../../packages/support/templates/components/Pagination/index.html.twig)
Twig component that renders it. Tables is the only consumer today: a
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
[Semantics](#semantics). A **skipped** row states its reason inline, or links the
divergence carrying it.

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
| [The four `SupportIconAlias` pagination constants](../../../packages/support/src/Twig/SupportIconAlias.php) | [`SupportIconAlias`](../../../vendor/filament/support/src/View/SupportIconAlias.php) | done | — | [support-icon-alias.md](support-icon-alias.md#pagination-rtl-aliases) |
| The four `*_RTL` alias constants and the `$isRtl` branch | [`pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | todo | — | [diverges](#the-footer-has-no-rtl-branch) |
| A `Pagination\Item` component of its own | [`pagination/item.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/item.blade.php) | skipped | — | Filament's item exists to be reused by two call sites in one file; here the item markup is six lines inlined at its three uses, and a component whose only job is to pick between an icon and a label would be indirection without a second consumer |
| [Simple paging — the null-total branch: an overview with no total, prev/next with no window](../../../packages/support/templates/components/Pagination/index.html.twig) | the `$isSimple` arms of [`pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | done | — | [diverges](#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both) |
| Cursor paging — the `CursorPaginator` arm, whose clicks carry an encoded cursor rather than a page number | [`pagination/index.blade.php`](../../../vendor/filament/support/resources/views/components/pagination/index.blade.php) | blocked | a keyset carrier ([table.md](../tables/table.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features)) | — |
| Number formatting and translated copy | `Number::format()`, `trans_choice()` in the same view | blocked | i18n ([table.md](../tables/table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)) | — |
| [Styles — the footer, the overview, the item list and its items](../../../packages/support/assets/src/styles/components/pagination.css) | [`.fi-pagination`, `.fi-pagination-overview`, `.fi-pagination-items`, `.fi-pagination-item*`](../../../vendor/filament/support/resources/css/components/pagination.css) | done | — | [diverges](#the-item-styles-collapse-filaments-three-elements-onto-one) |
| [Styles — the per-page select and its label](../../../packages/support/assets/src/styles/components/pagination.css) | [`.fi-pagination-records-per-page-select*`](../../../vendor/filament/support/resources/css/components/pagination.css) with [`input/wrapper.css`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) and [`input/select.css`](../../../vendor/filament/support/resources/css/components/input/select.css) | in progress | — | [detail](#the-selects-compact-twin-is-the-last-unported-arm) · [diverges](#the-per-page-select-carries-the-input-wrappers-chrome-because-it-has-no-wrapper) |
| Vendor's responsive layout arms — the `hidden` bases on the overview and the item list, their `@4xl:`/`md:` swaps, the `@container` declaration and both `@supports` blocks | [`pagination.css`](../../../vendor/filament/support/resources/css/components/pagination.css) | skipped | — | [diverges](#the-responsive-arms-are-dropped-with-the-mobile-layout-they-serve) |

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
unknown-total shape adds six: a null total reporting a null last page while
`hasMoreRecords` answers `hasMorePages()` and the record numbers still count off
the page, the flag ignored beside a real total, an empty simple first page
reporting no pages where a later page reports some, a **first** page carrying the
flag reporting pages off it alone — the only case where `hasMoreRecords` reaches
`hasPages()`, since every later page already answers that from its number —
`getPageWindow()` refusing a carrier with no total, and the flag surviving
`withRecords()`.

The component is covered from the tables side rather than here, because support's
own fixture Kernel registers no TwigComponentBundle — the same split
[badge.md](badge.md) and [button.md](button.md) record, with the suite living
wherever the component's first consumer does. The
Integration
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php)
asserts the rendered footer: the overview text, the single gap, the active item's
`aria-current`, a numbered button's `gotoTablePage` live-action parameters, and
the per-page `<select>` bound to `tableRecordsPerPage` with the right option
marked selected. Three further cases render the null-total shape from a host whose
`records()` closure returns a carrier with no total: page 2 of a simple table
reports `Showing 3 to 4 results` with no ` of ` in it, no gap, and an item list
holding exactly a `rel="prev"` and a `rel="next"` button aimed at pages 1 and 3
beside the same per-page select; an empty simple first page renders the empty
state with no footer at all; and an empty simple page *past* the data keeps the
footer at `Showing 0 results` with a prev button and nothing else. The last two
are the arms of the
[footer condition](../tables/table.md#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it)
a total-gate cannot express, and they are what separates the null arm from a
plain "a carrier with no total is always visible".

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
[`fi-compact`](../../internals/styling.md#why-do-templates-carry-only-semantic-classes) responsive variant, and it needs the
`input.wrapper` / `input.select` support components the port has not built (both
are **todo** rows in [README](README.md#components)), so today the select is a
bare `<select class="sy-pagination-per-page-select">`. The standalone
previous/next buttons were the **simple paginator's** whole UI, and that shape
has landed without them — the footer branches on a null `totalRecordCount` and
serves simple mode from the item list's own prev/next
([diverges](#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both))
— so what is left of the pair is the mobile layout, a stylesheet question the
`sy-pagination-items` list can answer on its own.

### The select's compact twin is the last unported arm

Filament renders the per-page control **twice** inside its container — a
`fi-compact` label and a prefixed one — and `pagination.css` swaps them at a
breakpoint: `:not(.fi-compact)` is `hidden` at the top level and `@md:inline` /
`sm:inline` inside the two `@supports` blocks, against `.fi-compact`'s
`@md:hidden` / `sm:hidden`. This port renders one labelled `<select>`, so all
five arms are unported.

What is missing is the markup, not the rules. The prefixed label is an
`input.wrapper` carrying a `prefix` around an `input.select`, and neither
component is built ([both **todo**](README.md#components)) — so this row flips
with [the component's own layout row](#the-component-renders-one-of-filaments-two-layouts),
which owns that second label, and not before. Everything else in
`pagination.css` that reaches an element this footer renders is ported, with the
collapses and the drops accounted for in the divergences below.

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

[Principle §2](../../internals/principles.md#2-prefer-the-ecosystem-over-invention) makes
adoption the default and building the case that has to be argued, and the
Symfony ecosystem has a pagination package — Pagerfanta — so this is the
argument.

What a pagination package sells is its **adapters**: Pagerfanta's value is
`DoctrineORMAdapter`, `DoctrineDbalAdapter`, `ElasticaAdapter` and friends, each
of which knows how to count a source and fetch one slice of it. Sylament needs
none of them, because in both of its lanes the slicing already has an owner. On
the **closure lane** the userland `records()` closure slices — it is handed
`page` and `recordsPerPage` and returns what it fetched, and an adapter would sit
between the closure and its own data doing nothing. On the **Doctrine `query()`
lane** the slicing is
`setFirstResult()`/`setMaxResults()` plus a count query, which the lane owns
anyway to apply filters, sorts and searches to the same builder — and it is built
that way, over Doctrine's own `Tools\Pagination\Paginator` rather than an
adapter. Strip the adapters and what is left of Pagerfanta is arithmetic
over four integers, which is what this class is — 180 lines, no dependency, and
no `Countable`/`IteratorAggregate` mismatch with the record shapes the toolkit
already carries.

That is the [named, checkable gap](../../internals/principles.md#we-can-do-it-better-means-wrap)
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
configure: a `records()` closure that fetches one record more than the page needs
and returns a carrier built with `totalRecordCount: null` and `hasMoreRecords` is
simple-paginated, because the footer
[branches on what the carrier answers](#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both)
rather than on a table setting. The Doctrine `query()` lane needs a setting for
it, because there the toolkit owns the fetching and has to be told which shape to
fetch — and it has one:
[`paginationMode()` and two of `PaginationMode`'s three cases](../tables/table.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features),
`Default` paying a `COUNT` for a true total and `Simple` probing one record past
the page for the same `hasMoreRecords` flag a closure sets by hand.

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

### The simple footer keeps the overview and the item list, where Filament drops both

Filament's view computes
`$isSimple = ! $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator`
and, in the simple arm, suppresses the overview (`@if (! $isSimple)`) and the
whole `<ol class="fi-pagination-items">`
(`@if ((! $isSimple) && $paginator->hasPages())`), leaving the standalone
previous/next buttons and the per-page select. The port asks the same question of
its one carrier — `totalRecordCount is null` — and keeps both elements: the
overview renders its range without a total (`Showing 3 to 4 results`), and the
item list renders with the page window skipped, so only its `rel="prev"` and
`rel="next"` chevrons remain.

The **overview** stays because the numbers are there and the copy is ours.
`getFirstRecordNumber()` / `getLastRecordNumber()` need no total, and neither do
Laravel's `firstItem()` / `lastItem()` — they sit on `AbstractPaginator`, so the
simple paginator answers them too. What forces Filament's hand is the
translation: `'{1} Showing 1 result|[2,*] Showing :first to :last of :total results'`
is one key with no totals-free arm, so a simple footer there can only say
nothing about where the user is. When
[i18n](../tables/table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) lands,
the second arm is a second key rather than a re-decision.

The **item list** stays because the port renders
[one of Filament's two layouts](#the-component-renders-one-of-filaments-two-layouts):
the standalone buttons simple mode falls back to are the layout we do not emit,
so dropping the `<ol>` would leave a simple footer with no way to change page at
all. Its prev/next items already key off `onFirstPage()` / `hasMorePages()`, the
two predicates a carrier answers without a total, so the branch skips the window
and nothing else and both shapes share one markup and one stylesheet path.

The cost is that a simple footer here is heavier than Filament's — an overview
and a list where Filament has two buttons — and that its controls are chevrons
carrying `aria-label`s where Filament's are labelled "Previous" and "Next". It
reverses if the standalone pair is ever built for the mobile breakpoint: simple
mode should then render *that*, and this branch becomes its desktop half.

### The footer has no RTL branch

Filament's view computes `$isRtl` from the panels translation and mirrors every
chevron, reaching four `*_RTL` alias constants for the purpose. Sylament's
renders the LTR chevrons unconditionally and ships none of the four constants.

The branch is unreachable rather than dropped: `$isRtl` reads
`__('filament-panels::layout.direction')`, a translated string from the panel
layer, and neither [i18n](../tables/table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)
nor panels exists in the port — there is nothing to ask which direction the page
runs. The four constants follow the
[alias growth order](support-icon-alias.md#pagination-rtl-aliases)
and land with the branch that reads them, not before. Provisional and mechanical:
whichever of the two prerequisites lands second brings this with it.

### The item styles collapse Filament's three elements onto one

Filament's item is three nested elements — `<li class="fi-pagination-item">`
wrapping `<button class="fi-pagination-item-btn">` wrapping
`<span class="fi-pagination-item-label">` — and `pagination.css` styles each of
them. Ours is a bare `<li>` around a single `<button class="sy-pagination-item">`
(a `<span>` for the gap) holding the page number as its own text, so three
selectors' declarations land on one: the `li`'s `border-x-[0.5px]
border-gray-200 dark:border-white/10`, the button's `relative flex
overflow-hidden p-2 transition duration-75 outline-none` with its `:enabled` and
`:hover` arms, and the label's `text-sm font-semibold text-gray-700
dark:text-gray-200`. `items-center` is the one declaration added rather than
carried. The active pair collapses the same way — Filament's `fi-active` puts a
background on the button and a colour on the label, ours puts both on
`.sy-pagination-item-active` — and so does `fi-disabled`, which Filament sets for
exactly one item, the ellipsis, so its `text-gray-500 dark:text-gray-400` arm is
`.sy-pagination-gap`. One behavioural delta rides in the markup rather than the
stylesheet: the active page button is emitted `disabled`, which drops it out of
the `:enabled` hover and focus arms Filament's active item stays in.

The edge arms cannot collapse with the rest, because they are the one thing the
`li` is still needed for. They reach through the list —
`.sy-pagination-items > li:first-of-type .sy-pagination-item` and its
`:last-of-type` twin — since `.sy-pagination-item` is its `<li>`'s only child and
`:first-of-type` on it would therefore match **every** item. Filament can write
`&:first-of-type` because its class is on the `li`. Both of its edge rules meet
on that one selector: the border removal (`first:border-s-0` / `last:border-e-0`)
and the rounding (`:first-of-type .fi-pagination-item-btn { rounded-s-lg }`).

The label's `px-1.5` cannot ride along either. On Filament's button it is padding
*inside* `p-2`, so a numbered item ends up with `0.875rem` of inline padding
where an icon item keeps `0.5rem`. With one element that difference has to be
asked for: `&:not(:has(.sy-icon)) { px-3.5 }` restores the same `0.875rem` to
anything without an icon — the gap included — and leaves `p-2` square for the
chevrons.

One declaration comes from a selector we do not port at all.
`.sy-pagination-items` carries `col-start-3`, which vendor's item list does not
have (it declares `justify-self-end` and no column); the column belongs to
`.fi-pagination-next-btn`, the standalone button that occupied the grid's third
track. The button is not rendered
([the component emits one of the two layouts](#the-component-renders-one-of-filaments-two-layouts)),
so the list took the column with it rather than sitting unplaced.

### The per-page select carries the input wrapper's chrome because it has no wrapper

Filament's select is an `input.select` inside an `input.wrapper`, and the two
split the work: the wrapper paints the surface — `rounded-lg bg-white shadow-sm
ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20` — and owns the focus
ring through `focus-within:ring-2`, while `select.fi-select-input` is
`border-none bg-transparent w-full` with `focus:ring-0 focus:outline-none` so it
defers to it. Support ships the wrapper now
([input-wrapper.md](input-wrapper.md)), but the select itself is still
[**todo**](README.md#components) and this footer is not migrated onto
the wrapper yet, so the footer renders a
bare `<select class="sy-pagination-per-page-select">` and that one element carries
both halves: the wrapper's surface, the select's own `block appearance-none
py-1.5 ps-3 pe-8 text-sm leading-6 text-gray-950 dark:text-white` with the
chevron data URI and the `[&_option]` colours, and the wrapper's focus ring
re-expressed as `focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500`
— `focus-within` on an ancestor becomes plain `focus` once the styled element is
itself the focus target.

What does not come across is what only made sense inside a wrapper: `w-full`,
`border-none bg-transparent`, the `focus:ring-0` that existed to leave the ring
to the wrapper, and `fi-select-input-has-inline-prefix`'s `ps-0`, which zeroes
the start padding only when a wrapper prefix already supplies it. Two more arms
are dropped because the footer's select never
reaches them — it is never disabled and renders no groups — so the `disabled:`
colours and `[&_optgroup]` go with them, and the `@variant rtl`
background-position goes with [the rest of the RTL work](#the-footer-has-no-rtl-branch).

The `<label>` around it and its text have no Filament counterpart to port,
because Filament's inline label is the wrapper's `prefix` — rendered as
`.fi-input-wrp-prefix` with `.fi-input-wrp-label` inside it — rather than an
element of the pagination footer. `.sy-pagination-per-page` is that label and
takes the two positioning declarations of `.fi-pagination-records-per-page-select-ctn`
(`col-start-2` and `justify-self-center`, which is all that container has, so the
element is gone but nothing it did is) plus `flex items-center gap-x-2`, standing
in for the wrapper's flex row and its prefix's spacing rather than reproducing
them declaration for declaration. `.sy-pagination-per-page-label` is
`.fi-input-wrp-label`'s four — `text-sm whitespace-nowrap text-gray-500
dark:text-gray-400` — plus `font-medium`, which that label does not carry and the
overview beside it does.

### The responsive arms are dropped with the mobile layout they serve

Filament's footer hides the overview and the item list outright (`hidden` on
both) and shows them again at `@4xl:`/`md:`, hiding the standalone previous and
next buttons at the same breakpoint: below it the footer is two buttons, above it
a range and a page list. Both `@supports` blocks exist for that swap — the
`@container` version and the media-query fallback — as does the `@container`
declaration on the root.

None of it is ported, because the elements the small end trades *to* are not
rendered ([the component emits one of the two layouts](#the-component-renders-one-of-filaments-two-layouts)).
Keeping the `hidden` bases without the arms that undo them would leave the
overview and the list hidden at every width, so the bases go too, and the `flex`
the item list only ever gets from `@4xl:flex`/`md:flex` is applied
unconditionally instead: both elements are always shown. The compact select's
arms sit inside the same two blocks and are dropped for
[a different reason](#the-selects-compact-twin-is-the-last-unported-arm), which
empties the blocks entirely — the one `@supports` block our stylesheet does carry,
`(-webkit-touch-callout: none)` around the select's `text-base`, comes from
[`input/select.css`](../../../vendor/filament/support/resources/css/components/input/select.css)
rather than from here.

It reverses with the standalone pair, and only with it. A mobile breakpoint that
wants a different treatment of the list we *do* render is a rule of our own on
`.sy-pagination-items`, not Filament's trade — the same reversal the
[simple footer](#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both)
names, arriving with it.

_See also: the global [rename table](../renames.md) —
`Sylament\Support\Pagination\Paginator` and its count getters are listed there as
names with no Filament source, alongside `gotoTablePage()`, the
Livewire-mechanism translation the footer's buttons call._
