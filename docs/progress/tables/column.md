# Column (tables)

[`Sylament\Tables\Columns\Column`](../../../packages/tables/src/Columns/Column.php)
— the base of the column catalog: a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php)
named after the record path it reads, bound to a [`Table`](table.md), and handed
one record at a time. It ports Filament's
[`Filament\Tables\Columns\Column`](../../../vendor/filament/tables/src/Columns/Column.php)
together with the concern traits it composes — eight today
([`BelongsToTable`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php),
[`HasName`](../../../packages/tables/src/Columns/Concerns/HasName.php),
[`HasLabel`](../../../packages/tables/src/Columns/Concerns/HasLabel.php),
[`HasRecord`](../../../packages/tables/src/Columns/Concerns/HasRecord.php),
[`HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php),
[`CanBeSortable`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php),
[`CanBeSearchable`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php),
[`InteractsWithTableQuery`](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php)),
thirty in Filament — twenty from its `Columns\Concerns\` and ten from
`Support\Concerns\`, `HasCellState` among the latter
([diverges](#hascellstate-is-relocated-into-the-column-tier)). Seven of the eight
are folded into the surface table below rather than earning rows upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing);
`InteractsWithTableQuery` keeps a per-file row in the package
[README](README.md#features) because the aggregate and eager-loading half of
Filament's file is still out, and the `Columns\Concerns\` traits not ported at
all keep a row there too, bar `CanFormatState`, which folds into
[`text-column.md`](text-column.md) because [`TextColumn`](text-column.md) is
what composes it. The rows below group those files by feature, which is what the
per-file rows upstairs point at.

The column is the read side of the record, and on the Doctrine lane it is also
the piece that knows how to ask for it. It resolves nothing itself: the host
fetches records ([`has-table.md`](has-table.md)), the table keys the columns
([`table.md`](table.md)), and a column asked for its state reads one path off one
record through schemas'
[`PropertyAccessor`](../schemas/property-accessor.md) — the same accessor the
form fields write through, reached by composing schemas'
[`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php).
Given the table's [`QueryBuilder`](table.md#the-relation-arm-of-the-query-lane-waits-on-a-doctrine-relation-layer)
it also applies its own ordering and contributes its own `LIKE` arm, through
`applySort()` and `applySearchConstraint()` — the column declares what sorting
and searching *by it* mean, and the table decides when to ask.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/Column.php`](../../../vendor/filament/tables/src/Columns/Column.php)
  — the class, its `make()` blank guard and both closure-injection hooks
- [`tables/src/Columns/Concerns/HasName.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasName.php)
- [`tables/src/Columns/Concerns/HasLabel.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php)
- [`tables/src/Columns/Concerns/BelongsToTable.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php)
- [`tables/src/Columns/Concerns/HasRecord.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php)
- [`support/src/Concerns/HasCellState.php`](../../../vendor/filament/support/src/Concerns/HasCellState.php)
  — the state read, hosted in support there and in the column tier here

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). Rows never disappear — only their detail
sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(?string $name = null)` + the blank-name guard](../../../packages/tables/src/Columns/Column.php) | [`Column::make()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](../renames.md#make-arguments-move-from-constructors-to-setters) |
| [`getDefaultName()`](../../../packages/tables/src/Columns/Column.php) | [`Column::getDefaultName()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | — |
| [`name()` / `getName()`](../../../packages/tables/src/Columns/Concerns/HasName.php) | [`Concerns\HasName`](../../../vendor/filament/tables/src/Columns/Concerns/HasName.php) | done | — | — |
| [`label()` / `getLabel()` — explicit value wins, else the name is humanised](../../../packages/tables/src/Columns/Concerns/HasLabel.php) | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | done | — | [semantics](#the-default-label-reads-the-second-to-last-dotted-segment) |
| [`record()` / `getRecord()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | in progress | — | [diverges](#the-record-union-is-plain-object-where-filaments-is-model) · [detail](#getrecord-has-no-layout-fallback) |
| [`table()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php) | in progress | — | [detail](#an-unbound-column-fails-without-filaments-message) |
| [`state()` / `getStateUsing()` / `getState()` — cached per record key](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#hascellstate-is-relocated-into-the-column-tier) · [diverges](#the-state-cache-keys-off-the-bound-record-key) · [semantics](#state-is-getstateusing-under-another-name) |
| [`getStateFromRecord()` — object graph or array, by dotted path](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`HasCellState::getStateFromRecord()`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | in progress | — | [diverges](#the-object-read-is-lenient-where-the-accessor-is-strict) · [detail](#relationship-traversal-is-the-doctrine-relation-layers) |
| [`$evaluationIdentifier = 'column'` + the `$record` / `$state` / `$table` / `$liveComponent` name injections](../../../packages/tables/src/Columns/Column.php) | [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | — | [diverges](#the-by-type-record-injection-is-not-registered) · [detail](#rowloop-waits-on-the-table-template) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | blocked | i18n ([table.md](table.md#the-empty-state-heading-hardcodes-no-records)) | — |
| `default()` / `getDefaultState()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | — |
| `separator()` / `distinctList()` — split a string into a list | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | — |
| `placeholder()` — the empty-cell copy | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | todo | — | — |
| [`clearCachedState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#the-state-cache-keys-off-the-bound-record-key) |
| [`recordKey()` / `getRecordKey()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | done | — | [diverges](#the-record-key-setter-admits-an-int) |
| `getRowLoop()` — the `$rowLoop` injection | [`Concerns\HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | blocked | the `Table` template | [detail](#rowloop-waits-on-the-table-template) |
| Relationship traversal — `hasRelationship()`, `getRelationshipResults()`, `inverseRelationship()`, the seven name caches, `applyRelationshipAggregates()`, `applyEagerLoading()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php), [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | blocked | the Doctrine relation layer ([table.md](table.md#the-relation-arm-of-the-query-lane-waits-on-a-doctrine-relation-layer)) | [detail](#relationship-traversal-is-the-doctrine-relation-layers) |
| Visibility — `hidden()`, `visible()`, `hiddenFrom()`, `toggleable()` | [`CanBeHidden`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeHiddenResponsively`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php), [`CanBeToggled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | todo | — | — |
| [`sortable()` / `isSortable()`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php) | [`Concerns\CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php) | done | — | — |
| [`searchable()` / `isSearchable()` / `isGloballySearchable()` / `isIndividuallySearchable()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`Concerns\CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) |
| [`getSortColumns()` / `getSearchColumns()`, `forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#the-list-getters-take-no-record-and-there-is-no-getdefaultcolumns-pair) · [semantics](#an-undeclared-sortable-has-no-sort-columns-where-an-undeclared-searchable-has-its-name) |
| `splitIndividualSearchTerms()` / `shouldSplitIndividualSearchTerms()` | [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | todo | — | — |
| [`applySort()`, `applySearchConstraint()` — the column's query application](../../../packages/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | done | — | [diverges](#sort-columns-apply-in-declared-order-where-filament-reverses-them) · [diverges](#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group) |
| Actions and links — `action()`, `url()`, `openUrlInNewTab()`, `disabledClick()` | [`CanCallAction`](../../../vendor/filament/tables/src/Columns/Concerns/CanCallAction.php), [`CanOpenUrl`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) | todo | — | — |
| Editing in place — `updateStateUsing()`, `rules()`, `disabled()` | [`CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php), [`CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php), [`CanBeDisabled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | todo | — | — |
| Cell presentation — `alignment()`, `verticalAlignment()`, `width()`, `grow()`, `inline()`, `tooltip()`, the extra-attribute bags | [`HasAlignment`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) and seven siblings, [`HasTooltip`](../../../vendor/filament/tables/src/Columns/Concerns/HasTooltip.php) | todo | — | — |
| Cell rendering — `renderInLayout()`, `getCellAttributeHtml()`, `getCachedCellAttributeHtml()` | [`Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | todo | the `Table` template | [detail](#the-cell-wrapper-belongs-to-the-table-template) |
| Grouping and layout — `ColumnGroup`, `Columns\Layout\`, `BelongsToGroup`, `BelongsToLayout`, `summarize()` | [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php), [`Columns/Layout`](../../../vendor/filament/tables/src/Columns/Layout), [`CanBeSummarized`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | todo | — | — |
| The rest of the catalog — `BadgeColumn`, `BooleanColumn`, `CheckboxColumn`, `ColorColumn`, `IconColumn`, `ImageColumn`, `SelectColumn`, `TagsColumn`, `TextInputColumn`, `ToggleColumn`, `ViewColumn` | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | todo | — | — |
| _rest not audited yet_ | | | | |

Thirteen of the fifteen **done**-or-partial rows are covered by
[`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php) — the
blank-name refusal, the three label derivations
(`title` → *Title*, `releasedAt` → *Released at*, `artist.name` → *Artist*), an
explicit label winning and a label closure receiving the column, the object read
by property, the dotted read through a nested object, the array read, all three
unreadable arms returning null, `state()` overriding the record read while the
`$record` injection still resolves, the `$state` / `$liveComponent` / `$table`
name injections and the by-type column injection under a different parameter
name, the table binding reaching the host through `getLiveComponent()`, an
integer record key stored as a string and `null` taken back, a second read of one
key served from the cache — including a cached `null`, which is why the lookup is
`array_key_exists()` and not `isset()` — a new key re-reading, an unkeyed column
reading fresh every time, and `clearCachedState()` restoring the fresh read.

The two browsing rows are covered in the same file. `sortable()` is asserted on
all four arms — the default false, the bare call, an explicit `false`, a closure,
and the column-list form still reading sortable. `searchable()` splits into four
cases: the three unset defaults together (searchable, globally, individually all
false); the bare call defaulting to **globally** searchable and not individually;
the string and array forms staying searchable; and the `isIndividual: true,
isGlobal: false` pair inverting both. A fifth case is the one that would fail
against Filament's own implementation — a closure condition returning `false`
leaving the column unsearchable, and one returning `true` leaving it globally
searchable — which is the
[vendor defect](#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column)
this port fixes.

The remaining two rows — the list getters and the query application — are
[`InteractsWithTableQueryTest`](../../../packages/tables/tests/Unit/Columns/Concerns/InteractsWithTableQueryTest.php)'s,
asserted against DQL and bound parameters rather than fetched rows: SQLite
matches `LIKE` case-insensitively for ASCII, so an execution-level assertion
would pass whether or not the `LOWER()` wrapping the case-insensitive arm exists.
It pins the default sort column ordering in the direction it was handed, a
declared list ordering [front to back](#sort-columns-apply-in-declared-order-where-filament-reverses-them),
a `sortable(query:)` closure receiving the builder and the direction and being
the only thing applied, the default search column composing into the caller's
`Orx` with its `%term%` parameter bound (and a two-name list composing both arms
under distinct parameter names), a `searchable(query:)` closure's returned
expression *and* returned DQL string both landing in that `Orx` while a closure
returning nothing leaves it empty, `forceSearchCaseInsensitive()` lowering both
sides of one column's comparison, a dotted name resolving against a joined alias
and refusing an unjoined one, and both getters' declared-and-defaulted arms.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### An unbound column fails without Filament's message

Filament's `Column::getTable()` chains three sources — the column's own table,
its group's, its layout's — and throws
`LogicException("The column [x] is not mounted to a table.")` when all three are
absent. Sylament's
[`BelongsToTable::getTable()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php)
returns a non-nullable `Table` property, so an unbound column raises PHP's
*"must not be accessed before initialization"* `Error` instead: it still fails
loudly at the same point, with a worse message.

The chain is what the message belongs to, and neither link exists yet — column
groups and column layouts are unported (their row is above). Adding a nullable
property plus a hand-written throw now would encode a fallback with nothing to
fall back to. The row flips when `BelongsToGroup` / `BelongsToLayout` land and
the three-source chain is written in one piece, message included.

### `getRecord()` has no layout fallback

Filament's `getRecord()` falls back to `$this->getLayout()?->getRecord()` when the
column carries none of its own, so a column nested in a `Columns\Layout\`
component reads the row the layout was handed. That arm belongs to the unported
column-layout tier (its row is above), so the getter returns the column's own
record or null. It lands with `BelongsToLayout` — in one piece with the
`getTable()` chain [waiting on the same tier](#an-unbound-column-fails-without-filaments-message),
rather than as a lone `?->` bolted on ahead of the thing it falls back to.

### Relationship traversal is the Doctrine relation layer's

Two thirds of Filament's `HasCellState` is Eloquent relationship machinery:
`hasRelationship()`, `getRelationship()`, `getRelationshipResults()`,
`hasMultipleRelationship()`, `getAttributeName()`, `getFullAttributeName()`,
`getInverseRelationshipName()`, `hasNestedMorphToRelationship()` and their seven
memo properties, and its `InteractsWithTableQuery` adds two consumers —
`applyRelationshipAggregates()`, which reads six aggregate declarations onto
`withAvg`/`withCount`/`withExists`/`withMax`/`withMin`/`withSum`, and
`applyEagerLoading()`, which is `$query->with([$relationshipName])`. They exist
to answer *which prefix of a dotted name is a relationship hop and which is an
attribute*, so a to-many hop can be flattened into a list, counted, or preloaded.

None of it ports as written. `artist.name` here is a **property path** walked by
[`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), which
hops objects and arrays without asking whether a segment is a relation — so the
to-one case, which is what the minimal vertical needs, already works and is
tested. On the query side the same name is resolved against the **builder** by
[`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php),
which asks only whether the head is an alias the query already declares: a table
sorting or searching across a relation joins and aliases it in `query()`, and an
unjoined head is refused by name rather than guessed at.

What is left needs a layer that does not exist. Aggregates have no Doctrine
referent as *builder methods* — `withCount()` has no `QueryBuilder` counterpart,
only a correlated subquery or a `GROUP BY` the caller writes — and eager loading
is a fetch-mode decision (`fetch: 'EAGER'` on the mapping, or a `leftJoin` plus
`addSelect`) rather than a per-column call. The to-many read (a column over a
Doctrine collection yielding a list of values) needs the same collection
semantics. All three wait on the
[Doctrine relation layer](table.md#the-relation-arm-of-the-query-lane-waits-on-a-doctrine-relation-layer),
which is where a relation-scoped table gets defined at all.

### `$rowLoop` waits on the table template

Filament registers a fifth name injection, `rowLoop`, carrying Blade's
`$loop` object for the row being rendered. It is dropped from the `match` here
because nothing produces one: the `Table` template does not exist yet, and the
Twig equivalent is `loop`, whose shape (`loop.index0`, `loop.first`, `loop.last`,
no `remaining`/`odd` without a count) is not Blade's. The injection lands with
the table template, in this same epic, and its name and contents are decided
there rather than guessed here.

### The cell wrapper belongs to the table template

`renderInLayout()`, `getCellAttributeHtml()` and `getCachedCellAttributeHtml()`
build the `<td>`/`<div>` wrapper in PHP, with the alignment, width, growth and
responsive-visibility classes concatenated into an attribute bag. Sylament emits
attributes from Twig through
[`attrs()`](../../../packages/support/src/Twig/Extension/AttributeExtension.php),
so the wrapper is the table template's job and these three methods have no
counterpart to port one-for-one. They stay listed because their *inputs* —
alignment, width, grow, the extra-attribute bags — are real API a migrating user
will look for, and those land with the template.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### The default label reads the second-to-last dotted segment

`getDefaultLabel()` runs `beforeLast('.')` **then** `afterLast('.')`, which looks
like a pair that cancels out. It does not: for `artist.name` the first call
yields `artist` and the second leaves it alone, so the column labels itself
*"Artist"* — the **relationship** name, not the attribute. That is Filament's
recipe verbatim
([`HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php)),
and it is intentional there: a column named `artist.name` sits in a table of
songs, where *Artist* is the column heading a reader expects and *Name* would be
meaningless. A single-segment name is untouched by both calls, so `releasedAt`
still labels *Released at*.

The capitalisation is `ucfirst`, not title case — Symfony's
`title()` defaults to `$allWords = false`, matching Laravel's `ucfirst()` — so
`releasedAt` is *"Released at"* and never *"Released At"*. `kebab()` already
folds underscores into dashes (it is `snake()->replace('_', '-')`), which is why
one `replace('-', ' ')` covers what Filament's `replace(['-', '_'], ' ')` spells
out in two.

### `state()` is `getStateUsing()` under another name

`state($x)` delegates straight to `getStateUsing($x)` — one property, one
evaluation path — so the two names differ only in how they read at a call site,
not in what they do. Both accept a literal *or* a closure, and `evaluate()`
returns a non-closure untouched: `->state('—')` pins every cell to a literal, and
`->state(fn ($record) => …)` computes per record. The reason both exist is
Filament's, and it is ergonomic: `state()` reads as *this is the value* and
`getStateUsing()` as *this is how to get the value*, which is the right phrasing
depending on whether you are passing a literal or a callback.

The consequence to keep in mind is that they **overwrite each other** — the last
call wins, and a column with `getStateUsing()` set never touches its record. Once
either is set, `getStateFromRecord()` is not called at all, so the column name
stops being a read path and remains only a key and a label source.

### An undeclared `sortable()` has no sort columns, where an undeclared `searchable()` has its name

`getSortColumns()` and `getSearchColumns()` read the same way —
`$this->sortColumns ?? [$this->getName()]` — but their properties start
differently: `$sortColumns` is `[]` and `$searchColumns` is `null`. So a column
that never called `sortable()` answers `[]` and one that never called
`searchable()` answers `[$this->getName()]`, and only the second falls through to
the name. That asymmetry is Filament's, property initialiser for property
initialiser, and it is invisible in the getter that looks symmetric.

It is also harmless where a table reaches these getters, because it never reaches
them on a column that declared nothing: a sort resolves its column through
[`Table::getSortableVisibleColumn()`](table.md#getsortablevisiblecolumn-has-no-hidden-arm-to-check),
which returns nothing for a column that is not sortable, and a search reaches
only the columns that answer `isGloballySearchable()`. Where it shows is a column
asked directly — a hand-built `Column` in a test, or a caller reaching past the
table — where `applySort()` adds no `ORDER BY` at all rather than ordering by the
column's own name. Both arms are pinned in
[`InteractsWithTableQueryTest`](../../../packages/tables/tests/Unit/Columns/Concerns/InteractsWithTableQueryTest.php)
so the initialisers cannot be "tidied" into agreement without a failing test.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### A `Closure` condition on `searchable()` is evaluated, not filed as a search column

`Column::searchable()` takes `bool|array|string|Closure` as its condition, and
Filament sorts the union with `is_bool($condition)`: the bool arm sets
`$isSearchable` and clears the columns, and **everything else** — an array, a
string, *and a closure* — goes to `Arr::wrap($condition)` in `$searchColumns`
with `$isSearchable = true`. So `searchable(fn () => false)` in Filament makes the
column unconditionally searchable and files the closure as a search *column*,
where it is later interpolated into a `LIKE` expression. The condition is never
evaluated. Sylament tests `is_bool($condition) || $condition instanceof Closure`
instead, so a closure lands in `$isSearchable` and `isSearchable()` evaluates it
the way `CanBeSortable` already evaluates its own.

This is a vendor defect rather than a design difference, and the shape of the
evidence is that Filament's *sibling* trait gets it right: `CanBeSortable`
branches on `is_array($condition)`, so its closure reaches `$isSortable` and is
evaluated by `isSortable()`. The two traits are otherwise line-for-line twins.
The signature itself agrees with us — `Closure` is in the declared union, which
it would not need to be if the intent were to accept a closure as a column name.

The port therefore fixes it at the cause, the way the M1 · 6
[clone-map defect](../actions/action-group.md#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately)
was fixed rather than mirrored. The cost is that a Filament user who wrote
`searchable($someClosure)` *expecting* a search column gets different behaviour —
but that user's column was searching by a closure-shaped column name, which
cannot have worked. `ColumnTest` pins both directions, so the arm cannot regress
into Filament's shape unnoticed.

### `HasCellState` is relocated into the column tier

Filament hosts `HasCellState` in **support**
(`Filament\Support\Concerns\HasCellState`), where two classes in two packages
compose it — `Tables\Columns\Column` and `Actions\Exports\ExportColumn`.
Sylament files it at
[`Sylament\Tables\Columns\Concerns\HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php)
instead. The class name, method names and signatures are unchanged, so this is a
relocation rather than a [rename](../renames.md).

Layering forces it. The state read goes through schemas'
[`PropertyAccessor`](../schemas/property-accessor.md) — the same one the form
fields read and write through, and the only dotted-path reader in the port — and
`support` sits *below* `schemas`, so a trait in support cannot import it. The
alternatives were both worse: pushing `PropertyAccessor` down into support would
drag `symfony/property-info` and `symfony/type-info` into the base package for
one consumer, and a support-hosted trait calling an abstract hook the column
implements would spread one read across two packages to preserve a directory.

This is provisional in exactly one way. Filament's placement pays off for its
*second* consumer, `Actions\Exports\ExportColumn`, which is unported — the export
layer is not on any milestone yet. When it lands, the trait moves to the lowest
package both consumers reach, which is `schemas` (both `tables` and `actions`
require it) and still not `support`, because the accessor it reads through lives
in schemas. This section then becomes a record of where it used to be; nothing
else about it changes.

### The state cache keys off the bound record key

Filament wraps the whole of `getState()` in `cacheState()`, which works the key
out for itself: a `Column` asks the Livewire host
(`$this->getLivewire()->getTableRecordKey($record)`), anything else reads
`ArrayRecord::getKeyName()` off an array record or `getKey()` off a model. Here
[`getState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php)
caches under `getRecordKey()` — the key the table already bound to the column
alongside its record — and `resolveState()` holds the body Filament passes to
`cacheState()` as a closure. Nothing is derived at read time and the host is
never called back into — a keyed column reads a given record once however it was
reached.

The visible consequence is a column nobody keyed: `getRecordKey()` is null, so
`getState()` resolves fresh on every call instead of memoising. That is the point
of keying off the binding rather than the record — a column built by hand and
handed one record after another (what every unit test does, and what a column
used outside a table is) must see each one, where a derived key would have
memoised the first read against a record the caller has since replaced. Inside a
table the case cannot arise: the row loop binds record and key together, so every
rendered cell is keyed.

Two of Filament's guards are not ported with it. `cacheState()` returns `null`
outright when the column has no record, which would make `->state('—')` or a
record-free closure resolve to null on an unbound column; `getState()` here
consults the record only if something reads it, so a literal or a closure still
resolves. And the key test is `=== null` where Filament's is `blank()`, so an
empty-string key caches here rather than reading fresh. Only an array record
handed an explicit empty `__key` can produce one —
[`HasTable::getTableRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php)
rejects a blank key on an object record outright.

### The object read is lenient where the accessor is strict

`PropertyAccessor::getFromRecord()` lets an accessor failure on an object hop
(no public property, no getter, an uninitialized typed property) propagate as
`Symfony\Component\PropertyAccess\Exception\AccessException`, deliberately, so a
caller can judge it. `getStateFromRecord()` judges it by returning `null`.

That is the semantics Filament has, arrived at differently: its read is
`data_get($record, $name)`, which yields `null` for anything it cannot reach and
never throws. A column is display-only and is handed whatever the host's data
source produced, so a typo'd name, a heterogeneous list, or a row missing an
optional relation must render an empty cell — a table that 500s because one row
of two hundred lacks a property is the wrong failure. It matches the read-lenient
arm schemas already ships in
[`BelongsToRecord::fill()`](../schemas/belongs-to-record.md), for the same
reason.

The strictness is not lost, it is placed: the **write** side
(`setOnRecord()`) throws, because writing to a path nobody can reach is a bug
with no sensible fallback. Only the array arm is naturally lenient already —
`getFromArray()` returns null for an absent key — so only the object arm needs
the `catch`.

### The record union is plain `object` where Filament's is `Model`

`record()` accepts `object|array<string, mixed>` and `getRecord()` returns
`object|array<string, mixed>|null`, against Filament's
`Model | array<string, mixed>`. This is the port-wide
[model → record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record)
applied to the column tier: there is no base class every Doctrine entity extends,
so `object` is the widest true bound, and a plain DTO, a value object or an
entity are all equally valid rows. Nothing in the column asks the record what it
is — `getStateFromRecord()` branches on `is_array()` alone.

### The record key setter admits an `int`

`recordKey()` takes `string|int|null` where Filament's takes `?string`, and
string-casts on the way in so the property and `getRecordKey()` stay `?string` —
Filament's getter exactly. The widening is forced by where the key comes from:
the [table partial's row loop](table.md#the-morph-key-is-an-id-attribute-not-wirekey)
binds each column's record and key together out of a Twig
`{% for recordKey, record in records %}` over a PHP array, and PHP re-keys a
numeric-string array key to an `int` — so a table of records keyed `'7'` hands
the template `7`. Filament never meets that: its record map is a Laravel
`Collection`, whose keys survive as strings.

The cast lands at the setter, so `?string` is the only form anything downstream
holds or reads — the property, the getter, and the
[state cache](#the-state-cache-keys-off-the-bound-record-key) key that getter
produces, where `7` and `'7'` have to be one entry and not two.

### The by-type record injection is not registered

Filament overrides `resolveDefaultClosureDependencyForEvaluationByType()` so that
a closure parameter type-hinted `Model` or the record's own concrete class
receives the record. Sylament's `Column` does not override it at all.

The Filament version is gated on `is_a($parameterType, Model::class)`, which has
no equivalent here for the reason above — there is no base class to gate on, and
un-gating it would mean *any* class-typed parameter claims the record, ahead of
the container and ahead of `$component`. The named `$record` injection covers the
need and is tested; a typed one arrives, if at all, with the schemas-side
`instanceof` treatment already reasoned out in
[belongs-to-record.md](../schemas/belongs-to-record.md).

Note that `fn (Column $column) => …` and `fn (Table $table) => …` still resolve,
but neither of them by type: the first matches `$evaluationIdentifier`, the
disjunct `EvaluatesClosures` short-circuits on ahead of its
`is_a($this, $parameterType)` arm, and the second is the `$table` name injection
above. Reaching the `is_a()` arm takes a *differently* named parameter —
`fn (Column $boundColumn) => …`, covered in
[`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php)
alongside the three name arms. A `Table`-hinted parameter under any other name
resolves nowhere and throws, since a column is not a table.

### Sort columns apply in declared order, where Filament reverses them

`applySort()` walks `getSortColumns()` front to back, appending one
`addOrderBy()` per name, so `sortable(['plays', 'title'])` orders by *plays* and
breaks ties on *title*. Filament walks the same list through `array_reverse()`
(`foreach (array_reverse($this->getSortColumns($query->getModel())) as $sortColumn)`)
onto Eloquent's `orderBy()`, which **appends** to `$this->orders` exactly as
`addOrderBy()` appends to the DQL `orderBy` part. Reversing the input to an
appending sink inverts the priority: the same declaration sorts by *title* first
over there, and `sortable(['first_name', 'last_name'])` — the natural way to
write *sort people by name* — sorts by last name.

The reversal is the vendor's behaviour verified in its own source rather than an
inference from ours, and nothing around it reads as intent: no comment, no
compensating reversal at the call site, and the sibling `Filters` and
`Summarizers` copies of `InteractsWithTableQuery` have no such loop to compare
against. A user's list is written in the order they want it applied, so we apply
it as written. The cost is the one that matters and it is small and visible: a
migrating table with a multi-column `sortable()` gets its tie-break priority the
other way round on the first click.

### A search closure returns its expression, where Filament's mutates an `orWhere` group

Filament hands `searchable(query:)` a **nested builder**: it calls
`$query->where(...)` for the first searchable column and `$query->orWhere(...)`
for the rest, passing a closure that runs `$this->evaluate($this->searchQuery, …)`
inside. Eloquent opens a parenthesised sub-clause for that callback, so the user's
closure adds `where`s to a group that is already `OR`-ed into the outer query, and
its return value is discarded.

Doctrine has no equivalent shape. `QueryBuilder::orWhere()` takes expressions
rather than a callback, and no object exists that *is* one arm of a predicate
being assembled. So the port inverts the direction: the table assembles one
`Doctrine\ORM\Query\Expr\Orx`, hands it to `applySearchConstraint()` alongside the
builder, and the closure's **return** is what joins it — an `Expr` node or a raw
DQL string, the `string|Stringable` pair `Orx::add()` declares (Doctrine narrows
further at runtime, accepting only its own `Comparison`, `Func`, `Andx` and `Orx`
nodes, and refusing anything else outright). A closure that returns nothing is
not an error: it is also handed the real `QueryBuilder`, so `andWhere()`-ing a
constraint that must apply *outside* the `OR` group is the one thing Filament's
nested-group shape cannot express and this one can.

The three injections are Filament's, unchanged — `$query`, `$search` and its
`$searchQuery` alias — and only the first differs in what it holds: the table's
builder, not a nested group. `$isFirst`, the by-reference flag Filament threads
through the column loop to choose `where` versus `orWhere`, has nothing to track
here and is gone: an `Orx` with one part renders as that part, so the first
column is not a special case.

The return is read through the `protected evaluateSearchQuery()` seam rather than
inline because
[`evaluate()`](../../../packages/support/src/Concerns/EvaluatesClosures.php) is
`@template T @param T|Closure`: handed a value typed *exactly* `Closure`, PHPStan
binds `T` to `Closure` and calls the result the callback rather than what it
returned, so every test of the value reads as impossible at level max. A
`mixed`-returning method takes it out of the generic. The shorter fix — a
`@var mixed` on the assignment — does not survive `composer format`, whose
`no_superfluous_phpdoc_tags` deletes it.

### The list getters take no record, and there is no `getDefault*Columns()` pair

`getSortColumns()` and `getSearchColumns()` are `(): array<string>` where
Filament's are `(Model $record): array<string>`. The parameter exists there only
to feed `getDefaultSortColumns($record)` / `getDefaultSearchColumns($record)`,
each of which returns `[$this->getFullAttributeName($record)]` — the
relationship-aware resolution that is
[the relation layer's subject](#relationship-traversal-is-the-doctrine-relation-layers).

Neither half has a Doctrine referent yet. A column name is a property path on the
read side, and on the query side it is resolved against the **builder** by
[`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php),
which needs the query and never the record. So the default collapses to
`[$this->getName()]`, written into the `??` rather than split into a
`getDefault*Columns()` method with one caller and nothing left to derive. The
pair comes back if the relation layer gives it something to compute; the getters
gain a parameter only if that layer needs one, and it would be the query rather
than a record — which is the port-wide
[model → record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record)
landing on a signature that had no record to name in the first place.
