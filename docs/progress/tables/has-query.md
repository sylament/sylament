---
description: 'The table''s Doctrine query lane: HasQuery, the relation-scoped table, and the relation arm''s path resolution, search, sort and aggregate shapes.'
ports:
  - vendor/filament/tables/src/Table/Concerns/HasQuery.php
  - vendor/filament/tables/src/Table/Concerns/HasRecords.php
owns:
  - packages/tables/src/Query/AggregateDeclaration.php
  - packages/tables/src/Query/AggregateQuery.php
  - packages/tables/src/Query/ColumnPathResolver.php
  - packages/tables/src/Query/Hop.php
  - packages/tables/src/Query/RelationConstraint.php
  - packages/tables/src/Query/RelationJoiner.php
  - packages/tables/src/Query/RelationPathResolver.php
  - packages/tables/src/Query/ResolvedRelationPath.php
  - packages/tables/src/Query/SearchConstraint.php
  - packages/tables/src/Table/Concerns/HasAggregateValues.php
  - packages/tables/src/Table/Concerns/HasQuery.php
---
# HasQuery (tables)

[`Sylament\Tables\Table\Concerns\HasQuery`](../../../packages/tables/src/Table/Concerns/HasQuery.php) declares how a table reaches its records through Doctrine: a `query()`, or a `relationship()` rooted at its target.
The relation arm behind it — the `Query/` helpers such as [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php), [`RelationPathResolver`](../../../packages/tables/src/Query/RelationPathResolver.php), [`RelationConstraint`](../../../packages/tables/src/Query/RelationConstraint.php), [`RelationJoiner`](../../../packages/tables/src/Query/RelationJoiner.php) and [`AggregateQuery`](../../../packages/tables/src/Query/AggregateQuery.php) — is a set of Sylament-only engines with no Filament file to diff, whose surface is the column's ([column.md](column.md)), so it has no rows here.
The entries below record the shapes it settled: the [resolution order](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused), the [search shape](#a-relation-search-is-a-correlated-exists-at-every-cardinality), the [sort shape](#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery), the [aggregate delivery](#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) and [what a composite identifier changes](#a-composite-identifier-root-correlates-and-groups-by-every-key-field).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The `query()` lane — `query()` / `getQuery()` / `modifyQueryUsing()` / `applyQueryScopes()` / `getQueryEntityManager()`](../../../packages/tables/src/Table/Concerns/HasQuery.php) | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | done | — | [semantics](#every-read-clones-a-memoised-base-query) |
| [`hasQuery()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::hasQuery()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#the-query-lane-is-on-exactly-while-no-records-closure-is-set) |
| [`getRecordClass()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getModel()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [rename](../renames.md#renames) · [semantics](#the-record-class-is-read-off-the-base-querys-root-entity) |
| [The relation-scoped table — `relationship()` / `getRelationship()` / `getRelationshipQuery()` / `isRelationshipBaseQuery()`](../../../packages/tables/src/Table/Concerns/HasQuery.php) | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | done | — | [semantics](#the-relationship-query-is-the-base-query-rooted-at-the-target) · [semantics](#the-mappings-order-is-the-relation-scoped-tables-default-sort) |
| The inverse-name derivation — `inverseRelationship()`, `getInverseRelationship()`, `getInverseRelationshipFor()` | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | skipped | — | [diverges](#the-inverse-relationship-is-read-off-the-mapping-not-derived) |

## Semantics

### The query lane is on exactly while no records closure is set

`hasQuery()` returns `$this->dataSource === null` — it never asks whether a query was declared.
A table with neither is on the query lane, and a table with **both** is on the records lane, because `records()` wins outright.
That is Filament's precedence, carried over as-is: `hasQuery()` is `! $this->dataSource` there, and the host branches on it to decide whether to run the closure or assemble a query.

The shape reads backwards until the two lanes are seen as one axis rather than two flags.
The records closure is the escape hatch — a user who supplies one has said *I will produce the rows myself*, and a query left behind from an earlier configuration pass must not silently resurface.
So the predicate asks the escape hatch, not the default.
`records(null)` puts the table back on the query lane, which is what makes the setter a true reset rather than a one-way switch.

The corollary is that "on the query lane" does not mean "has a query": a bare `Table` answers `true` and `getQuery()` answers `null`.
Anything assembling a query has to handle that null itself, and cannot read `hasQuery()` as a guarantee.

### Every read clones a memoised base query

`getQuery()` never hands out the stored builder.
It resolves the *base* query once — `getBaseQuery()` memoises into `$cachedBaseQuery`, so a `query(Closure)` declaration that yields a builder is evaluated on the first read and not again — then returns `applyQueryScopes(clone $baseQuery, …)`, a fresh clone per read.
A caller may therefore mutate what it gets back (paginate it, sort it, add a `where`) without the next reader seeing any of it, and `modifyQueryUsing()` scopes run against that private copy rather than accumulating on a shared one.

Both halves are load-bearing and neither is obvious from the signature.
Doctrine's `QueryBuilder::__clone()` deep-clones the DQL parts and the parameter collection, so the clone is genuinely independent — but the builder is mutable and every `->andWhere()` returns `$this`, so without the clone a single `getQuery()` call site would permanently narrow the table.
And without the memoisation a closure declaration would be re-run per read: the record class, the paginator's count query and the page query are three reads of the same table, and a closure doing real work — a repository call, an ACL scope — would run three times.
`query()` resets both the base cache and the record-class cache, so a re-declared query re-evaluates.

The memo holds a builder and never a `null`, so a declaration evaluating to null — no `query()` at all, or a closure that returns one — is re-evaluated on every read.
It is a branch rather than a `??=` because the read has a second job: it records which lane answered, in the flag [`isRelationshipBaseQuery()`](#the-mappings-order-is-the-relation-scoped-tables-default-sort) reports.
That is exactly the case the value-plus-flag pair [`getRecordClass()` keeps](#the-record-class-is-read-off-the-base-querys-root-entity) exists to cover on its side, and it is deliberately not kept here, because the null means *this table has no query*: every reader branches on it at once and none of them re-enters this getter, so the repeat costs one closure call rather than a getter per row.
The shape to know about is a closure doing real work before returning null — it pays that call per read.

Filament clones too (`$query->clone()`) but memoises nothing: its `getQuery()` calls `$this->evaluate($this->query)` on every read, and `getModel()` keeps a cache of its own.
The port memoises one level lower, which subsumes that cache and covers the other readers with it.

Two members carry the rest of the lane.
`applyQueryScopes()` is public and takes the builder to scope, so a caller that built its own query — a count query, a relation-scoped one — can put the table's scopes on it; scopes run in registration order, and one returning nothing (a `void` closure mutating in place) leaves the query it was handed, which is what makes both closure shapes valid.
Its only in-repo caller is `getQuery()`, and one shipped query deliberately does not become one: the [aggregate batch](#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query) builds its own builder over the record class and is *not* scoped, because it is already narrowed to the keys of a page the scopes produced — running them again would re-filter a set that has been filtered, and a scope reading the search term would filter it by a term the aggregated relation has no field for.
`getQueryEntityManager()` is Sylament-only: an Eloquent builder carries its connection and every Filament consumer reaches the model statically, where Doctrine's read side needs the `EntityManagerInterface` explicitly — so the entity manager behind the base query needs a seam, and on the `query()` lane it is read off the builder the caller handed over rather than injected.
The relationship lane is the case that stance did not foresee: there is no builder to read from until something has *built* one, and building it is a mapping read.
So `Table` composes [`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — the reader arrives the way it reaches every schemas component, through a `#[Required]` setter on the container-built table, with an entity-manager-less fallback for one built outside the container that refuses at use rather than at construction.
A `Table` therefore carries exactly one container dependency for this, and only the relationship lane consults it.

### A dotted name is resolved against the mapping before it is refused

Every query-side consumer of a column name — `Column::applySort()`, the host's `defaultSort` branch, `Column::applySearchConstraint()`, the table's extra searchable columns and `applyEagerLoading()` — resolves it the same way, in one fixed order, and the order is what keeps a plain name and a hand-declared join resolving byte-for-byte as they do without the relation arm, which sits on top of them:

1. **A dotless name** is `{rootAlias}.{name}`.
2. **A dotted name whose head is an alias the builder already declares** is returned verbatim — so a join written by hand in `query()` still wins, under whatever alias it was given, and a table that worked yesterday cannot be re-resolved out from under it.
3. Otherwise [`RelationPathResolver`](../../../packages/tables/src/Query/RelationPathResolver.php) walks the name against `ClassMetadata` from the root entity, and what it answers decides the arm.
4. Only if that resolves nothing does [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php) throw — with a message naming all three resolutions it tried.

A **dotless** name that happens to be a to-one association takes the first arm and becomes a bare `<rootAlias>.<assoc>`.
`SelectFilter::apply()`, `Group::scopeQueryByKey()` and the sort and search consumers then compare or order it with `=`, `IN`, `IS NULL` or `ORDER BY`, every one of which Doctrine refuses with `associationPathCompositeKeyNotSupported` when the association's target is keyed by several fields, while the search consumer's `LIKE` is refused on any association path at all.
Only a name that is a bare association *and* is sorted, searched, filtered or grouped by reaches it — displaying such a column is fine, as the next paragraphs show — and comparing rows by an entity is already a misconfiguration, so nothing refuses it by name.

The walk itself has one rule worth stating, because it is not the obvious one: at each step the **whole remaining path** is tested as a field name *before* the head is tested as an association.
That is what makes `address.street` resolve — a Doctrine embeddable's fields are mapped under their dotted names, so `hasField('address.street')` is true on the root and the path never becomes a hop.
It is also Filament's own precedence, arrived at differently: their `hasRelationship()` asks `$record->hasAttribute()` before `$record->isRelation()` for the same reason, an attribute that happens to share a relation's name wins.
The result is a `ResolvedRelationPath` — a list of hops and the field remainder — with **zero** hops for the embeddable case (rendered `{rootAlias}.{fieldPath}`) and one or more for a real relation path.

A path that runs out of segments standing **on** an association — `sortable(['artist'])`, where `artist` is the relation itself and no field follows — is the one resolution the two entry points answer differently, and the split is deliberate.
`analyseQueryPath()` **throws**, naming the missing field segment, because sorting or searching *by an entity* is undefined and a silent no-op there would read as a broken sort.
`tryAnalyseQueryPath()` returns null, and eager loading uses that one: a column named after an association is a perfectly good display column (the cell renders the entity, or a `formatStateUsing()` closure turns it into something), so the fetch-join arm has no business refusing what the render will happily do.
The failure contract is therefore **strict on the query seams and lenient on the display seam**, drawn at exactly one call site rather than at a `catch`.

### A relation search is a correlated `EXISTS` at every cardinality

Where a relation **sort** has to join, a relation search must not: a to-many join multiplies the root's rows, and a search that turns one matching row into three would break the count, the page arithmetic and the rendered list at once.
So `RelationConstraint::existsLike()` builds a subselect re-rooted on the *same* entity, joins it along the path, correlates it back by identifier and puts the `LIKE` on the far end:

```
EXISTS (SELECT sy_x0.id FROM Song sy_x0 JOIN sy_x0.genres sy_x0_0
        WHERE sy_x0.id = s.id AND sy_x0_0.name LIKE :sylament_search_0)
```

Filament reaches the same SQL shape from the other direction — `whereRelation` is `whereHas`, which Eloquent compiles to `exists (select …)` — so this is a faithful port rather than a divergence, and it earns a note only because three of its details look like choices and are constraints.
The subselect is re-rooted on the **root entity** rather than started at the association, because DQL's `FROM` cannot name an association path (the installed parser answers "NOT YET IMPLEMENTED").
The correlation is **identifier equality** rather than entity equality, which sidesteps comparing objects across the two scopes — and it is equality on **every** identifier field, written by [`IdentifierConstraint::correlate()`](../schemas/record-key-contract.md#the-operations-and-their-dql), so a root keyed by a host and a path correlates `sy_x0.host = s.host AND sy_x0.path = s.path` where a single-`id` one keeps `sy_x0.id = s.id` exactly as above.
And the `LIKE` parameter is registered on the **outer** builder, because one DQL query has one parameter space no matter how deeply a subselect nests — which is also why the subselect's own aliases are seeded from the outer parameter count, so two searchable relation columns in one word's `OR` group cannot collide.
The seeding survives the wider correlation for a reason worth naming: `correlate()` binds **nothing**, so the parameter count a second term reads is still the count the `LIKE`s put there.

What the subselect *projects* is one column at every identifier width: the **first** identifier field, wrapped in `IDENTITY()` when that field is an association.
`EXISTS` never reads a projection, and DQL leaves no alternative — a subselect takes no comma list, and an aliased expression inside one is a parse error reached through a path, a function or an identification variable alike — so the width lives in the correlation and nowhere else.
The `IDENTITY()` arm is not cosmetic: without it a root whose whole identifier is one association would emit `SELECT sy_x0.song` and die in Doctrine's parser with no Sylament refusal in front of it.

One routing caveat rides with all of this and bites hardest on a fetch-joined table: [a searchable dotted path may not share its head segment with a join alias](#a-searchable-dotted-path-and-a-fetch-join-alias-may-not-share-a-head-segment).

Everything the search assembly does around it is unchanged: one `Orx` per word, this predicate joining it like any other, no joins and no state left behind — so it is idempotent across the per-word loop by construction, and the count clone, which applies search but not sort, gets identical predicates and counts correctly.

### A searchable dotted path and a fetch-join alias may not share a head segment

A dotted column name reaches the correlated `EXISTS` above only when [the mapping resolves it](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused), and it does not when the path's **head names an alias the builder already declares** — there the caller's own resolution wins, deliberately, so `SearchConstraint::forColumn()` falls through and the term becomes a plain `LIKE` on that alias.
On a query that fetch-joins the collection under that same name, the `LIKE` lands on the **joined rows**, and Doctrine hydrates each record's collection out of exactly the rows that survived it: the search then filters the badge list *inside* every matching row instead of choosing which rows match, with no error anywhere and an aggregate cell beside it still counting the whole set.
Alias the join anything the searchable path does not begin with — `leftJoin('translation.blocks', 'block')` beside a searchable `blocks.heading` — and both arms behave.
The trap is not composite-specific: it bites **any** table that fetch-joins a collection under the head segment of a searchable dotted path, single-identifier roots included, because Doctrine's paginator ANDs its identifier `IN` onto the `WHERE` the `LIKE` is already in and hydrates each collection from the rows that survived both.

The case that would filter in silence is refused by name: `forColumn()` throws a `LogicException` when the head is an alias the query fetch-joins as a **collection**, naming the path, the alias and the ways out.
The message speaks of a *searchable path* and *the query* rather than a column and the table query, because panels' global search reaches it through the same `forColumn()` and has no column to name: it leads with re-aliasing the join, which both callers can do, and offers `searchable(query:)` as what a table column can also do ([global-search.md](../panels/global-search.md#the-per-attribute-constraint-hook-is-collapsed-into-one-method)).
A to-one join, a join the query does not select and an entity join keep the caller's own resolution, which is what rule 2 is for.

### The record class is read off the base query's root entity

`getRecordClass()` answers the entity class the table's rows are instances of — `array_first($this->getBaseQuery()?->getRootEntities() ?? [])`, so a query with no `from()` and a table with no query both answer `null`.
It is Filament's `getModel()` under the [record vocabulary](../renames.md#renames), and the read underneath is the one thing that changed: Eloquent's builder holds a model *instance* to take `::class` from, where Doctrine's holds the FROM clause's class-strings, plural because DQL admits several roots.
The first is the table's; a second root is a join expressed as a cartesian FROM, and it describes the query rather than the record.

The cache is a value plus a flag (`$cachedRecordClass`, `$hasCachedRecordClass`) rather than a `??=`, because `null` is a real answer and must not re-read on every call — Filament's shape, kept.
The flag is what `query()` clears alongside the base-query cache.
The null stays a null rather than becoming a throw: it means *this table is not on a Doctrine query*, which is a state every caller has to branch on anyway.

### The relationship query is the base query, rooted at the target

`relationship()` is the third data lane, and it lands in one slot: `getBaseQuery()` — the single memo every reader funnels through — falls back from the declared `query()` to [`AssociationReader::createRelationshipQuery()`](../schemas/association-reader.md).
Precedence is `records()` › `query()` › `relationship()` › the refusal, and `hasQuery()` is unmoved: it still asks only whether a `records()` closure is set, because a relationship table is a query table.

Everything downstream picks the lane up with no change of its own, which is the point of putting it there.
`getRecordClass()` reads the first root entity and the root is the **target**, always — the many-to-many arm narrows by a subselect over the owner's own association rather than by joining back through an inverse side, and the one-to-many arm narrows by the target's `mappedBy` field — so the record class is `Genre` for `Song.genres`, `getQueryEntityManager()` answers the reader's manager, columns resolve their paths from the target, and search, sort, pagination, the aggregate batch and the selection lane compose over the constraint instead of around it.
`getTableRecord($key)` is scoped for free and no ownership check exists anywhere: the constraint lives in the base query, which record resolution narrows *further* by key, so a key outside the owner's collection resolves to `null` — which is also what makes a smuggled key harmless on the two by-key bulk arms.

The relationship closure is evaluated **once** per table instance and memoised behind a `hasCachedRelationship` flag, because it usually resolves the owner and that is a database read; `relationship()` resets that memo along with the base query and the record-class memo, so re-declaring the lane re-reads it.
A closure answering `null` leaves the table with no base query at all, and that is not a quieter empty state: `hasQuery()` is still true — no `records()` closure is set — so the render reaches the three-lane refusal, `Table [...] must have [records()], [query()] or [relationship()]`.
A host whose owner may be missing therefore declares a `records()` fallback of its own (`->records(fn (): array => [])`) rather than relying on the relationship closure answering `null`.

### The mapping's order is the relation-scoped table's default sort

Doctrine applies `#[ORM\OrderBy]` when it hydrates a collection, so every other read of `Artist::$songs` comes back title-ascending.
A relation-scoped table that ignored it would be the one view of that association ordered differently from all the rest, so `getDefaultSort()` falls back — when the table declares no `defaultSort()` and the relationship's target carries an `orderBy` — to the `QueryBuilder` arm of its own contract, `addOrderBy()`ing one qualified field per mapping entry and returning the builder.

The fallback is gated on the relationship being the lane actually in force, which `isRelationshipBaseQuery()` answers off the flag `getBaseQuery()` sets as it picks a lane.
A table declaring both `query()` and `relationship()` is a query table, and appending the mapping's fields to *that* builder would qualify the target's columns with the query's own root — invalid DQL against a different entity — so the fallback answers `null` there and the relationship closure, a database read, stays unevaluated on the render path.

That arm is why nothing else had to move: the host applies the *user's* sort first and the default's parts after it ([the composition](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)), so a clicked header wins and the mapping order degrades to a tiebreaker beneath it, exactly as a hand-written `defaultSort(fn ($query) => …)` would.
The direction is read off the mapping rather than off the table, as the `SortDirection` the [reader](../schemas/association-reader.md) answers, and handed to `addOrderBy()` unchanged.
Filament has no counterpart: an Eloquent relation carries no readable default order, so this is an addition rather than a divergence.

Each mapping entry is emitted as a raw `<rootAlias>.<field>` path with no field check.
An `#[ORM\OrderBy]` entry naming an **association** therefore orders by a bare association path, which Doctrine refuses with a `QueryException` when that association's target is keyed by several fields.
It is reachable only from a mapping that orders a collection by an association, and it stays Doctrine's refusal rather than one of ours.

### A composite-identifier root correlates and groups by every key field

Relation search and the aggregate batch both reach the outer row through its identifier, and a single column cannot carry every root — `WHERE sub.id = root.id` for the search `EXISTS`, `WHERE root.id IN (:keys)` and `GROUP BY root.id` for the aggregate batch.
A record class keyed by two scalar fields, or by an association plus a scalar, has no such column: `getSingleIdentifierFieldName()` raises a `MappingException` for it.
A record class keyed by an association **alone** is not composite at all — the flag counts identifier fields and a derived identity contributes one — and a single-column clause emits the association as a bare path in a `SELECT` list, `SELECT sy_x0.<assoc>` for the search and `SELECT sy_r.<assoc> AS sy_key` for the batch, which Doctrine's parser rejects as *"Invalid PathExpression.
Must be a StateFieldPathExpression."* with no Sylament message in front of it.
So every one of those clauses is written per identifier field by [the predicate builder](../schemas/record-key-contract.md#the-operations-and-their-dql), which emits the single-column text byte for byte for a single-`id` root and the per-field form for every other one.

The batch's shapes are worth knowing, because neither is the one a reader guesses.
The batch **projects** `IDENTITY(root.venue) AS sy_key_0, root.season AS sy_key_1` — an identifier association through `IDENTITY()`, a field bare — and **groups** by the bare paths of both, `IDENTITY()` being illegal in `GROUP BY`.
The batch **re-keys** each returned row through the selection that projected it rather than by joining the raw values: `IDENTITY()` hands back the **raw** driver value under either hydrator while a field path hands back the converted object, and only the selection reconciles the two, so a type whose stored spelling differs from its printed one keys correctly instead of filling the side store under strings no cell ever asks for.
The search subselect keeps [one projected column](#a-relation-search-is-a-correlated-exists-at-every-cardinality) whatever the width.

Paging is the third mechanism that correlates by identifier, and it is not here: a query joining a collection cannot be windowed by rows at all, so a keyed root takes [a two-step read of its own](host-can-paginate-records.md#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column).
On the schemas side, `relationship()` lists a composite **target** as readily as a composite root: its many-to-many arm is a correlated `EXISTS` built on the same predicate builder, per identifier field ([relationship.md](../schemas/relationship.md#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping)).
A composite **owner** of either arm is handled by the same mechanism rather than a second one: both arms narrow to such an owner through a correlated `EXISTS` over the owner's **own** collection, so a relation-scoped table rooted at a scalar-pair, derived-identity or join-entity owner lists, keys, selects, sorts, searches, aggregates, summarises, groups and pages exactly as one rooted at a single-`id` owner does, and the builder this lane is handed still carries one root, no join and no extra alias ([the owner route](../schemas/relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
Two costs ride with it, and both are the reader's rather than this lane's: the subselect reaches the target **through** the owner's table, which [PostgreSQL does not flatten](../schemas/relationship.md#the-owner-scope-joins-through-the-owners-table-and-pays-a-self-join-postgresql-does-not-remove), and a **chained** derived-identity owner's one-to-many is Doctrine's own refusal to hydrate the child at all.
One asymmetry rides along and is deliberate: `getAllSelectableTableRecordKeys()` plucks off the **filtered** query, which carries no ordering at all, so a select-all over a relation-scoped table enumerates keys in whatever order the database returns them while the rows render in the mapping's `#[OrderBy]` order.
The selection is a set and is read as one; no assertion on it may depend on the rendered order, or on the order one platform happens to give.

## Divergences

### The inverse relationship is read off the mapping, not derived

Filament's relation-scoped table carries three members the port does not: `inverseRelationship()`, `getInverseRelationship()` and `getInverseRelationshipFor()`.
They exist because Eloquent cannot be asked what the other side of a relation is called, so Filament *guesses* it — the parent class's basename, camel-cased, singular for a has-one-or-many and plural otherwise — reached through a magic dynamic call on the record (`$record->{$this->getInverseRelationship()}()`), and `inverseRelationship()` is the setter that overrides the guess when it is wrong.

Doctrine already carries the answer: an association mapping names its counterpart (`mappedBy` on an inverse side, `inversedBy` on an owning one) and [`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php) surfaces both.
So the two jobs Filament needs the inverse name for are already done without it — the write side reaches the target's owning field through `mappedBy`, and the candidate query excludes already-related records with a negated subselect that needs no inverse mapping at all.
A setter with nothing left to override would be an API that does nothing, which is worse than an absence; the guess itself is exactly the kind of internal [principles §1](../../internals/principles.md) rejects.
[renames.md](../renames.md#renames) lists the names as `— not ported`.

Reversal is cheap but unmotivated: it needs a *reader* to want the inverse name by itself, and the setter's only Filament caller — `RelationManager::makeTable()` — reaches it from a `@deprecated` static property this port will not carry either.

### A dotted path into a JSON column is refused, where Filament's Eloquent pushes it through

Filament's `getJsonSafeColumnName()` replaces every dot in a name that is **not** a relationship with the driver's JSON arrow (`data.theme` → `data->theme`), and it does so unconditionally — no cast is consulted, no column is looked up — so a dotted name that survives the relationship check sorts and searches straight into the document, and a typo'd one becomes a JSON path that quietly matches nothing.
Here the same name is refused: the resolver asks Doctrine's mapping, which knows the column's type is `json` and nothing whatever about its contents, so no field and no association matches and `ColumnPathResolver` throws — with the JSON case named in the message, so the refusal reads as a decision rather than a missing feature.

The cell **display** side is unaffected and this is the asymmetry to keep in mind: `getStateFromRecord()` walks a decoded array by key, so a `data.theme` column renders perfectly and only refuses when someone marks it `sortable()` or `searchable()`.

What it would take to close it is the reason it is not closed: DQL has no JSON path operator, so the query would need a registered custom DQL function per platform (`JSON_EXTRACT` on MySQL/SQLite, `#>>` on PostgreSQL) plus a way for the resolver to know which prefix is a JSON column and which is a path inside it — a per-application registration, not something a port can infer from mapping metadata.
The escape hatches are the ones the message names: `sortable(query:)` and `searchable(query:)`, which hand the user the builder and let them write the platform's own expression.

### A relation sort joins, where Filament's orders by a correlated subquery

Sorting by `artist.name`, both codebases starting from a name with no join declared anywhere, reaches the same rows by opposite mechanics.
Filament hands the name to `RelationshipOrderer::buildSubquery()`, which builds the relation's own query, selects the sorted column off it, correlates it to the outer row and `limit(1)`s it, then orders by that subquery.
Sylament's [`ColumnPathResolver::resolveOrderable()`](../../../packages/tables/src/Query/ColumnPathResolver.php) `LEFT JOIN`s each hop through [`RelationJoiner`](../../../packages/tables/src/Query/RelationJoiner.php) and orders by `{alias}.{field}`.

The join is the better shape *here* for a reason that is Doctrine's rather than a preference: the same join is what [eager loading](interacts-with-table-query.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality) wants anyway, so a column that is both sorted and displayed costs exactly one `LEFT JOIN` and no extra query — where Eloquent's subquery buys nothing for the display side, its eager load being a separate query regardless.
Three consequences worth knowing:

- **LEFT, unconditionally.**
  An `INNER` join would drop every row whose relation is null the moment a user clicks a sortable header — rows vanishing on a *sort* is a data change, not an ordering change.
  Filament's correlated subquery yields `null` for the same rows, so LEFT is also what matches its outcome.
- **The join is reused, including a user's own.**
  `ensureJoined()` scans `getDQLPart('join')` for a join whose expression is exactly `{parentAlias}.{property}` and adopts its alias if one is there.
  That deliberately adopts a join the user wrote in `query()` **whatever its type** — an `INNER JOIN` or one carrying a `WITH` condition included.
  The alternative is worse in both directions: minting a second join over the same association doubles the rows the user's own join was there to shape, and refusing to reuse it would make a hand-written `innerJoin` a hard error.
  So a table that inner-joins its author in `query()` and then sorts by `author.name` keeps its own narrowing, which is what it asked for by writing the join.
- **Minted aliases are deterministic** — `sy_{property}`, nested as `sy_{parent}_{property}`, numerically suffixed only on a real collision — so the DQL a table produces is stable across renders and reads in a log.
  A collision is tested two ways, and the second is not the obvious one.
  `getAllAliases()` cannot see an alias declared inside a subselect that reached the builder as a `WHERE` **string**, while DQL resolves every identification variable of a query in one flat namespace across its subselects — so a name already standing in `getDQL()` as an identifier token of its own is taken too, and `mintAlias()` scans for it.
  Without that scan a dotted sort or summary whose first hop is a property named `owner` or `related` would die with *'sy_owner' is already defined*, against the two aliases the relation layer reserves inside its own subselect — on the many-to-many arm under **any** owner, and on every one-to-many under a composite owner, where a mapped-by field called `owner` is the commonest name there is.
  The scan reads the DQL rather than a list of the relation layer's names on purpose: there is no cross-package literal to keep in step, and it closes the `sy_x<n>` and `sy_ag<n>` cousins with it.
  A false positive — the token is a field or a parameter name rather than an alias — costs one `_2` suffix and nothing else.

A **to-many** hop anywhere in the chain is refused with a `LogicException` naming both ways out (`sortable(query:)`, or an aggregate column).
That is Filament's own behaviour, not a limitation of this port: their `RelationshipOrderer::validateRelationshipType()` throws an `InvalidArgumentException` for anything that is not `BelongsTo`, `HasOne`, `MorphOne`, `BelongsToThrough` or `HasOneThrough`.
Ours is a `LogicException` for consistency with the lane's other refusals, and it fires **before** anything is joined, so a refused sort leaves the query untouched.

### An aggregate is a batched side query and a HIDDEN subselect, never a select on the page query

Filament's aggregates ride the page query: `withCount()` and its five siblings add a correlated sub-select per declaration, Eloquent hydrates the result onto each model as an attribute, and the cell reads it from there.
That is one query for rows and values together, and it is unavailable here for a reason that is structural rather than aesthetic — a non-`HIDDEN` scalar in a Doctrine `SELECT` switches the whole result to **mixed hydration** (`[0 => Entity, 'sy_value' => 3]` per row), which breaks record keying, cell state, the row loop and `Paginator::getIterator()`, all of which expect entities.

So the value takes a second path.
After the page's records are resolved and keyed, the host runs one batched query per aggregate column — `SELECT {root}.{id} AS sy_key_0, {FN}({t}.{col}) AS sy_value … WHERE {root}.{id} IN (:sylament_agg_keys) GROUP BY {root}.{id}` — and drops the result into the table's [side store](../../../packages/tables/src/Table/Concerns/HasAggregateValues.php), where the cell reads it ahead of walking the record.
The page query stays a plain entity read, the count query is untouched, and the cost is +1 query per aggregate column per render.

Two details of that batch are contracts rather than incidentals.
Its joins are **inner**, so a key whose related set is empty produces no group and is simply absent from the result — the host fills the gap with SQL's own semantics (`0` for a count, `false` for an existence check, `null` for the rest), which keeps the defaults in one place instead of asking the database to invent them.
And the key narrowing is applied **after** the scope closure has run, because Doctrine's `where()` *replaces* where Eloquent's `where()` *adds*: a scope written with the Filament reflex would otherwise silently drop the `IN (:keys)` and aggregate the whole table into one arbitrary key's cell.
(A scope calling `groupBy()` still replaces the grouping — the same hazard, one layer along, and unfixed rather than unnoticed.
A scope calling `select()` replaces the key projection, and that one refuses loudly: the batch re-keys each row through the selection that projected it, so a missing key column throws *"A result row of […] lacks the key column [sy_key_0]"*.)

A third contract: the batch is **chunked**.
The scope closure runs once on the pristine builder, and each chunk of [`IdentifierConstraint::chunk()`](../schemas/record-key-contract.md#the-chunk-rule) narrows a **clone** of it under the one parameter base — a copy because a second predicate ANDed onto the first answers nothing, and one base because a base carrying a chunk index of its own is refused.
The per-chunk maps merge with `+` rather than `array_merge()`, which would renumber the integer array key a numeric record key becomes, handing every cell its neighbour's figure.
A single-identifier page of at most two thousand keys is one chunk, so it is one statement.

**Sorting** cannot use the store, since the database has to do the ordering, so it takes a third shape: the same aggregate as a correlated subselect added to the sorted clone `AS HIDDEN sy_agg{n}`, with `addOrderBy()` naming that alias.
`HIDDEN` is what keeps hydration pure — the scalar is projected for the SQL's benefit and dropped before the result is built — and it survives both paginator walkers (`LimitSubqueryOutputWalker` un-hides it into the inner SQL by design, `CountOutputWalker` wraps the whole statement), which is the property the whole approach rests on.
Only the sorted clone carries it; the count clone never applies sort at all.

The cost shape is worth stating plainly, because "correlated subselect" sounds worse than the alternative and is in fact the *same* alternative: Laravel's `withAggregate()` is also a correlated sub-select in the SELECT list, so both codebases pay per candidate row when ordering by an aggregate.
What differs is that Filament pays it **once** for value and order together, while this port pays the subselect on the sorted clone *and* a batch query for the values.
One extra query per aggregate column, in exchange for a page of clean entities.
An `exists()` column sorts by the raw `COUNT` rather than by the boolean it displays: the zero/non-zero boundary is identical, so the two partitions land the same way round, and only the order *within* the true group differs — by count, not by tiebreak.
With no `castToBool` in the ordering there is nothing to collapse them.
