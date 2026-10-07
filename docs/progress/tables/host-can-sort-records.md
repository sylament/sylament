---
description: 'The host trait `CanSortRecords` that `HasTable` composes: the sort state, `sortTable()` and the ordering the query lane composes. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/CanSortRecords.php
owns:
  - packages/tables/src/Concerns/CanSortRecords.php
---
# Concerns\CanSortRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CanSortRecords` — `$tableSort`, `sortTable()`, `healSeededTableSort()`, `getTableSortColumn()`, `getTableSortDirection()`, `applySortingToTableQuery()`](../../../packages/tables/src/Concerns/CanSortRecords.php) | [`Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php) | in progress | — | [detail](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [diverges](#sorttable-ignores-a-column-the-table-did-not-declare-sortable) · [diverges](#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) · [diverges](#the-identifier-tiebreaker-is-suppressed-only-by-the-qualified-key) · [semantics](#a-column-name-containing-a-colon-cannot-be-cycled) · [diverges](#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query) · [diverges](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |

The **query half** of sorting is the one browsing concern with a file of its own, [`CanSortRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanSortRecordsTest.php) — the state half above stays with the rest of the host state, per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature).
Every case drives the public lane against three seeded songs whose title order, play order and identifier order all disagree, so an ordering that is silently ignored cannot pass: each asserts the composed `ORDER BY` off `getFilteredSortedTableQuery()->getDQL()` **and** the record order the database answered with.
They are the user sort with its tiebreaker; the default sort alone, in the direction it declared; a mixed-case `'DESC'` arriving lowercased, which is the only transformation the declaration side performs; the default appended under a user sort; everything after the user's column taking the *declared* direction rather than the clicked one; the same-column default applied exactly once (a count of occurrences, since the failure being caught is a duplicate rather than an absence); the raw-path arm with no matching column; a default-sort closure ordering the query it was handed; another replacing it with a builder that also filters, asserted by the two records left, and that same replacement under a user sort, where the clicked ordering goes with the builder it was applied to; a third replacing it with a builder over a **different entity**, where the tiebreaker must read `book.code` — `Book`'s identifier is not named `id`, so a tiebreaker taken from the table's own record class would compose `book.id` and the case asserts the assembled DQL *parses* as well as reading right; the closure arms of both setters, one naming a column and one switching the tiebreaker off; both tiebreaker suppressions, the qualified key and the joined `artist.id` one; `defaultKeySort(false)` leaving no key ordering at all; and a `query()`-authored `orderBy` staying in front of everything the table adds.
Three more pin the composite tiebreak.
A sorted `Shortcut` query appends `ORDER BY shortcut.label ASC, shortcut.host ASC, shortcut.path ASC` — every identifier field the user's order did not already name, in mapping order, at the sort's own direction.
A query that already orders `shortcut.path DESC` gets only `shortcut.host ASC` appended, with `shortcut.path` named exactly once, which the case asserts by counting occurrences.
And a `Residency` query appends `ORDER BY residency.venue ASC, residency.season ASC` — the **plain association path** rather than `IDENTITY(residency.venue)`, which is what keeps the dedupe working by string equality against the spelling `ColumnPathResolver::resolveOrderable()` emits — then executes, so Doctrine emitting the foreign-key column is proven rather than asserted.

## Details

### Sorting composes the user's order, the default, and the identifier tiebreaker

`applySortingToTableQuery()` is the query lane's half of the trait, and it composes three orderings onto whatever the declared query already carries — a user-authored `orderBy()` in `query()` stays in front of all of them.
First the user's: `getTableSortColumn()` is resolved through [`getSortableVisibleColumn()`](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column) and the column applies **itself** through [`Column::applySort()`](interacts-with-table-query.md#sort-columns-apply-in-declared-order-where-filament-reverses-them), so a `sortable(query:)` closure or a multi-column `sortable([…])` list decides what its own name means.
Then the [default sort](can-sort-records.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker), skipped when it names the column the user is already sorting by, applied through the same column resolution when it names a declared sortable column and through [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php) when it names a raw path instead — which is how `defaultSort('plays')` works on a table with no `plays` column.
A default-sort **closure** is handed `query` and `direction`; ordering the builder it was given is the usual arm, and returning a builder replaces the query outright, filters and all — **including the user's sort**, which was applied to the builder that just got replaced, so a table whose default sort closure returns one is ordered by that builder plus the tiebreaker and by nothing the user clicked.
Filament writes the same replacement line and throws the result away at every call site, so the arm is live here and dead there — [its own divergence](#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded).

Last is `defaultKeySort()`, on by default: the root alias's identifier field, read from the mapping metadata rather than assumed to be `id`, appended in the same direction the default sort ran in.
It is what makes a page boundary stable when the sorted column has ties.
Exactly one thing suppresses it: an existing ordering whose path **is** the qualified key.
Filament's second suppression — any ordering whose path merely *ends* in the same field name — is [not ported](#the-identifier-tiebreaker-is-suppressed-only-by-the-qualified-key), because the relation arm makes `artist.id` an ordering the table can produce by itself.
A **composite** identifier is tiebroken by all of its fields rather than by one.
[`IdentifierConstraint::orderBy()`](../schemas/record-key-contract.md#the-operations-and-their-dql) appends each identifier field the existing order does not already name, in mapping order, at the same direction — so a two-field key adds two parts, a derived identity orders by the association path itself (`residency.venue`, which is the one join column it has), and a single-field key adds exactly the one part this section describes.
It still no-ops when the builder names no root entity to read metadata from, which is now the only case where there is no key to tiebreak by.

Both halves of that qualified key are read off the builder **actually in hand** — its root entity, its root alias, and the `EntityManager` the metadata comes from, never the table's memoised [record class](has-query.md#the-record-class-is-read-off-the-base-querys-root-entity) or its declared query.
That is Filament's reading too (`$query->getModel()->getQualifiedKeyName()`), and it is what a replacement builder needs: a default-sort closure returning a builder over a different entity would otherwise get that entity's alias carrying the *declared* entity's identifier field — `book.id` for a `Book` keyed by `code` — which is not a mismatch a reader would spot, because it composes cleanly and fails only when Doctrine parses it.
The table-level read stays where it belongs: keying a row is the record class's job, ordering this builder is this builder's.

That is one rule across the lane rather than two habits.
An **identity** read — [keying a record](has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) and [resolving one back](has-table.md#gettablerecord-reads-fresh-when-the-render-cache-is-cold) — sources its `EntityManager` and record class off the **table's declaration**, because a key has to mean the same thing on both sides of a round trip and the table is the only thing that outlives the builder a single read built.
An **ordering** read — the tiebreaker here — sources both off the **builder in hand**, because an `ORDER BY` only has to be valid for the query it is appended to.
Neither direction is a convenience: swapping them gives you row keys that change with a scope, or a qualified key that does not parse.

Filament's method opens with two early returns and one of them now ships.
`isTableReordering()` replaces every ordering — the user's sort, the default sort and the identifier tiebreaker alike — with the reorder column, resolved through [`ColumnPathResolver::resolveOrderable()`](../../../packages/tables/src/Query/ColumnPathResolver.php) rather than appended raw the way Filament's `orderBy($column)` is, because DQL needs the alias a bare property name does not carry.
The other, `isGroupsOnly()`, skips sorting entirely — every ordering, the tiebreaker included — and landed with [record grouping](grouping.md#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not), above the reorder branch, which is Filament's own order.
On the records lane none of this runs at all: the `sortColumn` / `sortDirection` injections are the whole application there, and the userland closure orders its own data.

`updatedTableSort()` and `updatedTableSortDirection()` collapse into the body of `sortTable()` here, because both exist to run the two things it already does — persist to the session (a [deliberate omission](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it)) and reset the page.
Filament needs them as separate members because Livewire calls them for a *property write*; `$tableSort` is not writable here, so nothing else can change it.
The three `@deprecated` host getters are not ported.

## Semantics

### A column name containing a colon cannot be cycled

`$tableSort` encodes the pair as `"{$column}:{$direction}"` and `getTableSortColumn()` splits on the first colon, so a column literally named `a:b` round-trips as column `a`, direction `b` — and `sortTable('a:b')` writes a string whose column half no longer matches the name it was given, which means the same-column branch never fires and the column can never cycle past ascending.

This is Filament's encoding, kept verbatim, and the edge is Filament's too (`str($this->tableSort)->before(':')`).
It is recorded rather than fixed because the fix costs more than the edge: a column name is a record property path, where a colon is not legal PHP and not a Doctrine field name, so the case is reachable only through an array record with a hand-written key.
Escaping the separator would change the wire format for every table to guard a name nobody can write in the common case.
If it ever needs fixing, both halves — the writer in `sortTable()` and the two readers — move together.

## Divergences

### A seeded sort is healed at mount, where Filament's is trusted to the query

`sortTable()` refuses a column the table does not declare sortable and a direction that is neither `asc` nor `desc`.
Two writers reach `$tableSort` without going through it — the query string and the session — and Filament checks neither: its `getTableSortColumn()` is the same raw `before(':')` parse ours is, and the only thing that looks at sortability is `applySortingToTableQuery()`, which simply does not sort when the lookup misses.

That is fine on the query lane and wrong on the records one.
A `records()` closure takes a `sortColumn` injection reading `getTableSortColumn()` directly, so a `?sort=<anything>` handed the closure the raw client string and left it to userland to decide what to do with a column nobody declared.
`healSeededTableSort()` closes it: a `#[PostMount(priority: 5)]` running the same column-and-direction check `sortTable()` runs, and nulling the prop when it fails.

The **priority is the point**, and it puts the hook at the bottom of a three-rung ladder — UX's query-string seeding (a listener at 256), then [the session read](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) at 10, then this at 5.
So one check covers both untrusted writers: a URL naming a column the table never offered, and a session written months ago against a table that has since dropped it.
The cleared value is **written back** to the session (`persistTableSortInSession()`), so a stale entry is forgotten once rather than re-read and re-cleared on every later visit — which is also why the heal runs below the read rather than above it.

Filament needs none of this because its own seeding lands in the same place and it accepts the consequence; ours is the [entry-point validation](#sorttable-ignores-a-column-the-table-did-not-declare-sortable) the port already applied to the action, extended to the two writers the action does not own.

### `sortTable()` ignores a column the table did not declare sortable

Filament's `sortTable()` writes whatever column string it is given straight into `$tableSort`; the check happens much later, in `applySortingToTableQuery()`, which only applies the sort if `getSortableVisibleColumn()` finds a column — so an unknown or unsortable name leaves the table *displaying* itself as sorted by a column it is not sorted by.
Sylament checks first and returns without writing: `getSortableVisibleColumn($column) === null` is a no-op, as is a direction that is not `asc`, `desc` or null.

The reason is the closure lane.
Filament's late check is harmless because the query builder is the only consumer; here the value is handed to a **userland closure** through the `sortColumn` and `sortDirection` injections, and that closure will most likely interpolate it into a query.
Filtering at the entry point is what keeps the injections trustworthy: a name arriving at a `records()` closure has been matched against a declared-sortable column, so the closure can use it without re-validating.
The direction check is the same argument for the same reason, and it is why `sortTable()` types its `$column` parameter `string|int|null` — a `#[LiveArg]` carries whatever JSON the client sent, and an integer column name (a table sorted by a year) must survive the cast rather than be refused by the signature before the check runs.
Only `$column` carries that union — `$direction` stays `?string`, because its whole vocabulary is `asc`, `desc` and null, so there is no legitimate non-string value the widening would have let through.

Permanent, and the query lane will not loosen it: an ignored request is recoverable (the user clicks again), a trusted-but-unchecked one is not.

### A default-sort closure's replacement builder reaches the caller, where Filament's is discarded

Both codebases end `applySortingToTableQuery()` with the same two moves — a `$query = $defaultSort;` when the evaluated default sort came back as a builder, then the identifier tiebreaker — and the bodies are otherwise line for line.
The divergence is in the **call sites**.
This one returns the builder and [`getFilteredSortedTableQuery()`](../../../packages/tables/src/Concerns/HasTable.php) consumes the return (`return $this->applySortingToTableQuery($query);`), so the replacement really becomes the table's query.
All four of Filament's callers invoke the method as a bare statement and then use the builder they passed in — `Concerns\HasRecords::getFilteredSortedTableQuery()` and `getTableQueryForExport()`, and two sites in `Concerns\HasBulkActions` — so over there `$query = $defaultSort` rebinds a local and nothing more.
The replacement never leaves the method; what it does do is redirect the rest of it, so `getQualifiedKeyName()` and the tiebreaker are read off and appended to the builder that is about to be thrown away.
Filament's observable behaviour for a `defaultSort()` closure returning a builder is therefore *the original query, still carrying the user's sort, with no identifier tiebreaker* — the replacement contributes nothing at all.

The reach is narrow, and worth stating so the divergence is not read as bigger than it is.
A closure written the ordinary way orders the builder it was handed and returns `$this` or nothing, so `$defaultSort` is either that same builder or not a builder, and both codebases behave identically on every such input.
Only a closure returning a **different** builder reaches the arm — which is exactly what [`getDefaultSort()`'s widened `string|QueryBuilder|null` return](can-sort-records.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker) exists to serve, and it is invisible to a reader diffing the two method bodies, because the bodies match.

Ours is the reading the declared return states — and Filament declares the same `Builder` return, so the arm is dead over there rather than absent, which is why it is kept here rather than un-ported: a value a method computes and hands back is one the caller uses.
The cost is that the replacement is **total**: it brings its own filters and drops the search and the user's sort along with everything else applied to the builder it replaced.
Three cases in the [sorting suite](../../../packages/tables/tests/Unit/Concerns/CanSortRecordsTest.php) pin it — the replacement's `WHERE` reaching the executed query, the user's sort gone from the DQL, and the tiebreaker read off the replacement (`ORDER BY book.code ASC` for a `Book` builder returned from a `Song` table's default sort).

Permanent, and it closes on its own if Filament ever consumes the return — the two agree again with no change here.
It is the third arm of this family the port declines to carry as written, beside [`searchable()`'s closure condition](interacts-with-table-query.md#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) and [`applySort()`'s reversal](interacts-with-table-query.md#sort-columns-apply-in-declared-order-where-filament-reverses-them).

### The identifier tiebreaker is suppressed only by the qualified key

`defaultKeySort()` appends the root's identifier so a page boundary is stable under ties, and it stands down when the query is already ordered by that key.
Filament tests that with `afterLast('.')` — an ordering *ending* in the same field name suppresses it, whichever alias it hangs off — on the reasoning that a query ordering by a joined key has almost always chosen its own tiebreaker.
The port drops the loose half: the comparison is against the fully qualified `{rootAlias}.{identifierField}` (or the bare field name, pre-resolution) and nothing else.

The reason is who can produce the ordering being tested.
Without the relation arm an `artist.id` ordering could only come from a user who had written the join *and* named that column, which is the case the heuristic is charitable about.
The relation arm makes the same ordering something the **table** produces on its own: a user clicks a sortable `artist.id` column, the arm joins and orders by it, and the loose comparison would then read the port's own join as the user's chosen tiebreaker and drop the root key — leaving the page boundary unstable between rows sharing an artist, which is precisely the failure `defaultKeySort()` exists to prevent, and one that only shows up as rows repeating or vanishing across a page change.

It restores Filament's *outcome* rather than departing from it, which is why this counts as a tightening and not a behaviour change to migrate: their tiebreak scan only ever meets orderings a user wrote, since their relation sort orders by a correlated **subquery** object rather than by a dotted string — so a Filament table sorted by a relation is never suppressed either.
The narrow case that does change is a user who hand-wrote `orderBy('artist.id')` and relied on the root key dropping out; they now get one extra `ORDER BY` term, which cannot change the row order they already had.
