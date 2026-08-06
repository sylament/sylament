# Column (tables)

[`Sylament\Tables\Columns\Column`](../../../packages/tables/src/Columns/Column.php)
— the base of the column catalog: a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php)
named after the record path it reads, bound to a [`Table`](table.md), and handed
one record at a time. It ports Filament's
[`Filament\Tables\Columns\Column`](../../../vendor/filament/tables/src/Columns/Column.php)
together with the concern traits it composes — seven today
([`BelongsToTable`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php),
[`HasName`](../../../packages/tables/src/Columns/Concerns/HasName.php),
[`HasLabel`](../../../packages/tables/src/Columns/Concerns/HasLabel.php),
[`HasRecord`](../../../packages/tables/src/Columns/Concerns/HasRecord.php),
[`HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php),
[`CanBeSortable`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php),
[`CanBeSearchable`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php)),
thirty in Filament — twenty from its `Columns\Concerns\` and ten from
`Support\Concerns\`, `HasCellState` among the latter
([diverges](#hascellstate-is-relocated-into-the-column-tier)). The five ported
are folded into the surface table below rather than earning rows upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing); the
`Columns\Concerns\` traits still unported keep a per-file row in the package
[README](README.md#features), bar `CanFormatState`, which folds into
[`text-column.md`](text-column.md) because [`TextColumn`](text-column.md) is
what composes it. The rows below group those files by feature, which is what the
per-file rows upstairs point at.

The column is the read side of the record. It resolves nothing itself: the host
fetches records ([`has-table.md`](has-table.md)), the table keys the columns
([`table.md`](table.md)), and a column asked for its state reads one path off one
record through schemas'
[`PropertyAccessor`](../schemas/property-accessor.md) — the same accessor the
form fields write through, reached by composing schemas'
[`CanAccessProperties`](../../../packages/schemas/src/Components/Concerns/CanAccessProperties.php).

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
| [`getStateFromRecord()` — object graph or array, by dotted path](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`HasCellState::getStateFromRecord()`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | in progress | — | [diverges](#the-object-read-is-lenient-where-the-accessor-is-strict) · [detail](#relationship-traversal-is-the-doctrine-query-lanes) |
| [`$evaluationIdentifier = 'column'` + the `$record` / `$state` / `$table` / `$liveComponent` name injections](../../../packages/tables/src/Columns/Column.php) | [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | — | [diverges](#the-by-type-record-injection-is-not-registered) · [detail](#rowloop-waits-on-the-table-template) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | blocked | i18n ([table.md](table.md#the-empty-state-heading-hardcodes-no-records)) | — |
| `default()` / `getDefaultState()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | — |
| `separator()` / `distinctList()` — split a string into a list | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | — |
| `placeholder()` — the empty-cell copy | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | todo | — | — |
| [`clearCachedState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#the-state-cache-keys-off-the-bound-record-key) |
| [`recordKey()` / `getRecordKey()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | done | — | [diverges](#the-record-key-setter-admits-an-int) |
| `getRowLoop()` — the `$rowLoop` injection | [`Concerns\HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | blocked | the `Table` template | [detail](#rowloop-waits-on-the-table-template) |
| Relationship traversal — `hasRelationship()`, `getRelationshipResults()`, `inverseRelationship()`, the seven name caches | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | [detail](#relationship-traversal-is-the-doctrine-query-lanes) |
| Visibility — `hidden()`, `visible()`, `hiddenFrom()`, `toggleable()` | [`CanBeHidden`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeHiddenResponsively`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php), [`CanBeToggled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | todo | — | — |
| [`sortable()` / `isSortable()`](../../../packages/tables/src/Columns/Concerns/CanBeSortable.php) | [`Concerns\CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php) | done | — | [detail](#the-sort-and-search-column-lists-are-stored-and-read-by-nothing) |
| [`searchable()` / `isSearchable()` / `isGloballySearchable()` / `isIndividuallySearchable()`](../../../packages/tables/src/Columns/Concerns/CanBeSearchable.php) | [`Concerns\CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | done | — | [diverges](#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) · [detail](#the-sort-and-search-column-lists-are-stored-and-read-by-nothing) |
| `getSortColumns()` / `getSearchColumns()` and their `getDefault*` halves, `forceSearchCaseInsensitive()`, `splitIndividualSearchTerms()` | [`CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | [detail](#the-sort-and-search-column-lists-are-stored-and-read-by-nothing) |
| `applySort()`, `applySearchConstraint()` — the column's query application | [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | — |
| Actions and links — `action()`, `url()`, `openUrlInNewTab()`, `disabledClick()` | [`CanCallAction`](../../../vendor/filament/tables/src/Columns/Concerns/CanCallAction.php), [`CanOpenUrl`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) | todo | — | — |
| Editing in place — `updateStateUsing()`, `rules()`, `disabled()` | [`CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php), [`CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php), [`CanBeDisabled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | todo | — | — |
| Cell presentation — `alignment()`, `verticalAlignment()`, `width()`, `grow()`, `inline()`, `tooltip()`, the extra-attribute bags | [`HasAlignment`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) and seven siblings, [`HasTooltip`](../../../vendor/filament/tables/src/Columns/Concerns/HasTooltip.php) | todo | — | — |
| Cell rendering — `renderInLayout()`, `getCellAttributeHtml()`, `getCachedCellAttributeHtml()` | [`Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | todo | the `Table` template | [detail](#the-cell-wrapper-belongs-to-the-table-template) |
| Grouping and layout — `ColumnGroup`, `Columns\Layout\`, `BelongsToGroup`, `BelongsToLayout`, `summarize()` | [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php), [`Columns/Layout`](../../../vendor/filament/tables/src/Columns/Layout), [`CanBeSummarized`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | todo | — | — |
| The rest of the catalog — `BadgeColumn`, `BooleanColumn`, `CheckboxColumn`, `ColorColumn`, `IconColumn`, `ImageColumn`, `SelectColumn`, `TagsColumn`, `TextInputColumn`, `ToggleColumn`, `ViewColumn` | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | todo | — | — |
| _rest not audited yet_ | | | | |

The thirteen **done**-or-partial rows are covered by
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

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The sort and search column lists are stored, and read by nothing

`sortable(['title', 'subtitle'])` and `searchable(['title', 'author.name'])` both
store their list, and `sortable(query:)` / `searchable(query:)` both store their
closure — exactly as Filament does — and no shipped code reads any of the four.
That is Filament's situation too on the lane this port has: `$sortColumns` is
read only by `getSortColumns(Model $record)`, whose only caller is
`InteractsWithTableQuery::applySort()`, and the search pair is the same shape.
Both are Eloquent-typed down to the parameter (`Model $record`) and neither can
be ported before the Doctrine [`query()` lane](../roadmap.md#pulled-forward) gives
them a query to apply to.

They are stored rather than rejected because the **declaration** is the API a
migrating user writes, and it is meaningful today: `sortable(['first_name',
'last_name'])` still makes the column sortable, which is the half the closure lane
honours — the userland `records()` closure receives the column *name* through
[`sortColumn`](has-table.md#the-records-injections-stop-at-six-of-filaments-eight)
and decides for itself what ordering by it means. Refusing the array arm would
break that call site for no gain; reading it here would mean inventing a
Doctrine-side meaning for it ahead of the lane that owns one.

The two `getDefault*Columns()` halves are not stored at all, having nothing to
store: each returns `[$this->getFullAttributeName($record)]`, a relationship-aware
name resolution that is
[the query lane's whole subject](#relationship-traversal-is-the-doctrine-query-lanes).
`forceSearchCaseInsensitive()` and `splitIndividualSearchTerms()` are likewise
out — both are read by the search-expression builders, and a stored flag no
builder consults would be worse than an absent one, since it would read as
configurable behaviour.

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

### Relationship traversal is the Doctrine `query()` lane's

Two thirds of Filament's `HasCellState` is Eloquent relationship machinery:
`hasRelationship()`, `getRelationship()`, `getRelationshipResults()`,
`hasMultipleRelationship()`, `getAttributeName()`, `getFullAttributeName()`,
`getInverseRelationshipName()`, `hasNestedMorphToRelationship()` and their seven
memo properties. They exist to answer *which prefix of a dotted name is a
relationship hop and which is an attribute*, so a to-many hop can be flattened
into a list.

None of it ports as written. `artist.name` here is a **property path** walked by
[`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), which
hops objects and arrays without asking whether a segment is a relation — so the
to-one case, which is what the minimal vertical needs, already works and is
tested. The to-many case (a column over a Doctrine collection yielding a list of
values) needs the collection semantics that arrive with the Doctrine `query()`
lane ([pulled forward · 12](../roadmap.md#pulled-forward)); the same lane is what
makes `searchable()` and `sortable()` expressible at all.

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
