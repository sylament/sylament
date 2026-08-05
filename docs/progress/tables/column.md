# Column (tables)

[`Sylament\Tables\Columns\Column`](../../../packages/tables/src/Columns/Column.php)
— the base of the column catalog: a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php)
named after the record path it reads, bound to a [`Table`](table.md), and handed
one record at a time. It ports Filament's
[`Filament\Tables\Columns\Column`](../../../vendor/filament/tables/src/Columns/Column.php)
together with the concern traits it composes — five today
([`BelongsToTable`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php),
[`HasName`](../../../packages/tables/src/Columns/Concerns/HasName.php),
[`HasLabel`](../../../packages/tables/src/Columns/Concerns/HasLabel.php),
[`HasRecord`](../../../packages/tables/src/Columns/Concerns/HasRecord.php),
[`HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php)),
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
| [`record()` / `getRecord()`](../../../packages/tables/src/Columns/Concerns/HasRecord.php) | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | in progress | — | [diverges](#the-record-union-is-plain-object-where-filaments-is-model) · [detail](#recordkey-waits-on-row-identity) |
| [`table()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Columns/Concerns/BelongsToTable.php) | [`Concerns\BelongsToTable`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToTable.php) | in progress | — | [detail](#an-unbound-column-fails-without-filaments-message) |
| [`state()` / `getStateUsing()` / `getState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | in progress | — | [diverges](#hascellstate-is-relocated-into-the-column-tier) · [semantics](#state-is-getstateusing-under-another-name) · [detail](#the-state-read-is-not-cached-per-record) |
| [`getStateFromRecord()` — object graph or array, by dotted path](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`HasCellState::getStateFromRecord()`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | in progress | — | [diverges](#the-object-read-is-lenient-where-the-accessor-is-strict) · [detail](#relationship-traversal-is-the-doctrine-query-lanes) |
| [`$evaluationIdentifier = 'column'` + the `$record` / `$state` / `$table` / `$liveComponent` name injections](../../../packages/tables/src/Columns/Column.php) | [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | — | [diverges](#the-by-type-record-injection-is-not-registered) · [detail](#rowloop-waits-on-the-table-template) |
| `translateLabel()` | [`Concerns\HasLabel`](../../../vendor/filament/tables/src/Columns/Concerns/HasLabel.php) | blocked | i18n ([table.md](table.md#the-empty-state-heading-hardcodes-no-records)) | — |
| `default()` / `getDefaultState()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | — |
| `separator()` / `distinctList()` — split a string into a list | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | — |
| `placeholder()` — the empty-cell copy | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | todo | — | — |
| `clearCachedState()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | todo | — | [detail](#the-state-read-is-not-cached-per-record) |
| `recordKey()` / `getRecordKey()` | [`Concerns\HasRecord`](../../../vendor/filament/tables/src/Columns/Concerns/HasRecord.php) | blocked | row identity (M1 · 6) | [detail](#recordkey-waits-on-row-identity) |
| `getRowLoop()` — the `$rowLoop` injection | [`Concerns\HasRowLoopObject`](../../../vendor/filament/tables/src/Columns/Concerns/HasRowLoopObject.php) | blocked | the `Table` template | [detail](#rowloop-waits-on-the-table-template) |
| Relationship traversal — `hasRelationship()`, `getRelationshipResults()`, `inverseRelationship()`, the seven name caches | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | [detail](#relationship-traversal-is-the-doctrine-query-lanes) |
| Visibility — `hidden()`, `visible()`, `hiddenFrom()`, `toggleable()` | [`CanBeHidden`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHidden.php), [`CanBeHiddenResponsively`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeHiddenResponsively.php), [`CanBeToggled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeToggled.php) | todo | — | — |
| Sorting and searching — `sortable()`, `searchable()`, the query application | [`CanBeSortable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSortable.php), [`CanBeSearchable`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSearchable.php), [`InteractsWithTableQuery`](../../../vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Actions and links — `action()`, `url()`, `openUrlInNewTab()`, `disabledClick()` | [`CanCallAction`](../../../vendor/filament/tables/src/Columns/Concerns/CanCallAction.php), [`CanOpenUrl`](../../../vendor/filament/tables/src/Columns/Concerns/CanOpenUrl.php) | blocked | table actions (M1 · 6) | — |
| Editing in place — `updateStateUsing()`, `rules()`, `disabled()` | [`CanUpdateState`](../../../vendor/filament/tables/src/Columns/Concerns/CanUpdateState.php), [`CanBeValidated`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeValidated.php), [`CanBeDisabled`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php) | todo | — | — |
| Cell presentation — `alignment()`, `verticalAlignment()`, `width()`, `grow()`, `inline()`, `tooltip()`, the extra-attribute bags | [`HasAlignment`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) and seven siblings, [`HasTooltip`](../../../vendor/filament/tables/src/Columns/Concerns/HasTooltip.php) | todo | — | — |
| Cell rendering — `renderInLayout()`, `getCellAttributeHtml()`, `getCachedCellAttributeHtml()` | [`Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | todo | the `Table` template | [detail](#the-cell-wrapper-belongs-to-the-table-template) |
| Grouping and layout — `ColumnGroup`, `Columns\Layout\`, `BelongsToGroup`, `BelongsToLayout`, `summarize()` | [`ColumnGroup`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php), [`Columns/Layout`](../../../vendor/filament/tables/src/Columns/Layout), [`CanBeSummarized`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeSummarized.php) | todo | — | — |
| The rest of the catalog — `BadgeColumn`, `BooleanColumn`, `CheckboxColumn`, `ColorColumn`, `IconColumn`, `ImageColumn`, `SelectColumn`, `TagsColumn`, `TextInputColumn`, `ToggleColumn`, `ViewColumn` | [`src/Columns`](../../../vendor/filament/tables/src/Columns) | todo | — | — |
| _rest not audited yet_ | | | | |

The nine **done**-or-partial rows are covered by
[`ColumnTest`](../../../packages/tables/tests/Unit/Columns/ColumnTest.php) — the
blank-name refusal, the three label derivations
(`title` → *Title*, `releasedAt` → *Released at*, `artist.name` → *Artist*), an
explicit label winning and a label closure receiving the column, the object read
by property, the dotted read through a nested object, the array read, all three
unreadable arms returning null, `state()` overriding the record read while the
`$record` injection still resolves, and the table binding reaching the host
through `getLiveComponent()`.

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

### `recordKey()` waits on row identity

`HasRecord` ports the record binding but not `recordKey()` / `getRecordKey()`.
The key is what a table row is identified by across a morph — the same thing
[`has-table.md`](has-table.md) defers with `getTableRecord()` — and it arrives
with **M1 · 6**, which puts the table host on the live-component axis. Two things
here wait on it: the per-record state cache below, and the record-scoped actions
whose row is above.

Filament's `getRecord()` also falls back to `$this->getLayout()?->getRecord()`;
that arm is part of the unported column-layout tier, so the getter returns the
column's own record or null.

### The state read is not cached per record

Filament wraps `getState()` in `cacheState()`, keyed by the record key, so a
column asked for its state twice while rendering one row reads the record once.
Sylament's `getState()` reads every time, and `clearCachedState()` has nothing to
clear so it is not ported.

That is a deliberate ordering, not an oversight: the cache key *is*
`getRecordKey()`, which [waits on row identity](#recordkey-waits-on-row-identity).
Caching before the key exists would mean either a second identity scheme to throw
away, or a cache keyed on the record object — which memoises across rows when two
rows are equal-but-distinct. Both rows flip together in **M1 · 6**. Until then a
column re-reads, and the read is a property access rather than a query, so the
cost is not user-visible.

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
