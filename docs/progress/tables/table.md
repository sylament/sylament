# Table (tables)

[`Sylament\Tables\Table`](../../../packages/tables/src/Table.php) — the root
object of the tables package, the counterpart to schemas'
[`Schema`](../schemas/schema.md): a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) a
host builds through [`HasTable`](has-table.md), carrying the record data source
and the empty-state copy. It ports Filament's
[`Filament\Tables\Table`](../../../vendor/filament/tables/src/Table.php) together
with the `Table\Concerns\` traits it composes — three today
([`BelongsToLiveComponent`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php),
[`HasRecords`](../../../packages/tables/src/Table/Concerns/HasRecords.php),
[`HasEmptyState`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php)),
thirty-one in Filament. Those concerns are folded into the surface table below
rather than earning rows upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing).

The `Table` fetches nothing itself. It declares *how* records are reached — a
`records()` closure — and the host resolves and caches them; see
[`has-table.md`](has-table.md) for that half of the axis.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Table.php`](../../../vendor/filament/tables/src/Table.php)
  — the class, its `make()`, `setUp()` and closure-injection hook
- [`tables/src/Table/Concerns/BelongsToLivewire.php`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php)
- [`tables/src/Table/Concerns/HasRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php)
- [`tables/src/Table/Concerns/HasEmptyState.php`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php)

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). Rows never disappear — only their detail
sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(InteractsWithTable $liveComponent)`](../../../packages/tables/src/Table.php) | [`Table::make()`](../../../vendor/filament/tables/src/Table.php) | done | — | [diverges](../renames.md#make-arguments-move-from-constructors-to-setters) |
| [`liveComponent()` / `getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php) | [`BelongsToLivewire`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php) | done | — | [semantics](#the-host-binding-is-required-where-the-schemas-one-is-optional) |
| [`records()` / `getDataSource()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::records()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [has-table.md](has-table.md#the-records-closure-receives-no-named-injections-yet) |
| [`getRecords()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecords()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) |
| [`emptyStateHeading()` / `getEmptyStateHeading()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | in progress | — | [diverges](#the-empty-state-heading-hardcodes-no-records) |
| [`emptyStateDescription()` / `getEmptyStateDescription()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | in progress | — | [detail](#the-create-action-default-description-waits-on-table-actions) |
| [`emptyStateIcon()` / `getEmptyStateIcon()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | in progress | — | [diverges](#the-empty-state-icon-getter-returns-null-and-the-fallback-lives-in-the-template) |
| [`$evaluationIdentifier = 'table'` + the `$liveComponent` name injection](../../../packages/tables/src/Table.php) | [`Table::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Table.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Table.php) | [`$view = 'filament-tables::index'`](../../../vendor/filament/tables/src/Table.php) | in progress | the `Table` template | [detail](#the-template-path-is-declared-ahead-of-the-file) |
| `emptyState()` (a whole replacement view) | [`HasEmptyState::emptyState()`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | todo | — | — |
| `emptyStateActions()` / `pushEmptyStateActions()` | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | blocked | table actions (M1 · 6) | — |
| Record labels — `modelLabel()`, `pluralModelLabel()`, `recordTitle()`, `recordTitleAttribute()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | todo | — | [diverges](#the-empty-state-heading-hardcodes-no-records) |
| Record identity — `getRecordKey()`, `hasPivotRecordKeys()`, `allowDuplicates()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | blocked | row identity (M1 · 6) | — |
| Columns — `columns()`, the column manager | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php), [`HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | todo | — | [detail](#the-column-set-is-the-next-step-of-this-epic) |
| The `query()` lane — `query()`, `hasQuery()`, `getModel()`, `relationship()` | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | — |
| Pagination and polling — `paginated()`, `deferLoading()`, `poll()` | [`CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php), [`CanDeferLoading`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php), [`CanPollRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Sorting and searching — `defaultSort()`, `searchable()`, the session persistence | [`CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php), [`CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Filters — `filters()`, the indicators, the query-string identifier | [`HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php), [`HasFilterIndicators`](../../../vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php), [`HasQueryStringIdentifier`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | todo | — | — |
| Actions — record, header, toolbar and bulk actions, `recordUrl()` | [`Table\Concerns\HasActions`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php) and its five siblings | blocked | table actions (M1 · 6) | — |
| Grouping and summaries — `groups()`, `defaultGroup()`, the summarizers | [`CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | todo | — | — |
| Reordering — `reorderable()` | [`CanReorderRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php) | todo | — | — |
| Presentation — `striped()`, `stackedOnMobile()`, `content()`, `header()`, the headings, `recordClasses()` | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) and five siblings | todo | — | — |
| `HasArguments`, `HasExtraAttributes`, `HasDefaultDataFormattingSettings` | [`Table.php`'s remaining composed traits](../../../vendor/filament/tables/src/Table.php) | todo | — | — |

The four **done** rows are the host binding, the data-source declaration, the
host-delegating `getRecords()`, and the closure-injection wiring. They are
covered by
[`TableTest`](../../../packages/tables/tests/Unit/TableTest.php) — `make()`
binding the host, `records()` storing and returning the closure identity, the
empty-state defaults, and both injection paths at once (`fn ($liveComponent)`
resolved by name through
[`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/tables/src/Table.php),
`fn (Table $table)` resolved by type through `EvaluatesClosures`' own
`is_a($this, $parameterType)` arm) — and, for `getRecords()`, by the host-side
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
it delegates into.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The create-action default description waits on table actions

Filament's `Table::setUp()` registers a default `emptyStateDescription()` closure
that returns *"Create a `<model label>` to get started"* — but only when the
table has an action named `create`, and `null` otherwise. Both halves are
missing here: table actions arrive with **M1 · 6**, and the model label with the
[label machinery](#the-empty-state-heading-hardcodes-no-records). Sylament's
`Table` therefore does not override `setUp()` at all, and
`getEmptyStateDescription()` returns `null` until a user sets one. The default
returns in the same change as `Table\Concerns\HasActions`, where
`hasAction('create')` finally has an answer.

### The template path is declared ahead of the file

`$template` names `@SylamentTables/partials/Table/index.html.twig` before that
file exists — the table template lands later in this same epic (M1 · 5), with
the columns and the empty-state markup it renders. `getTemplate()` returns the
path today, so `render()` throws a Twig loader error rather than returning
`null`; nothing calls it yet. The row flips when the template ships.

### The column set is the next step of this epic

`use Table\Concerns\HasColumns;` joins the trait block in the next step of
M1 · 5, together with the `TextColumn` catalog. `HasColumnManager` — the
user-toggleable column visibility panel — is a separate, later concern and is
not part of the minimal vertical.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### `getRecords()` delegates to the host and fetches nothing

`Table::getRecords()` is one line — `$this->getLiveComponent()->getTableRecords()`
— and that is Filament's shape too, not a stub. The `Table` owns the
*declaration* (`records()` stores a closure) while the host owns the
*resolution*: it evaluates the closure, normalises the result, and caches it for
the request. Reading `getRecords()` as the fetch point and adding caching or
evaluation here would duplicate — and diverge from — the host's copy in
[`HasTable::getTableRecords()`](has-table.md). The direction of the call is the
thing to keep: table → host, never the reverse.

### The host binding is required, where the schemas one is optional

`Table::make()` takes a non-optional `InteractsWithTable`, and
`BelongsToLiveComponent::$liveComponent` here is a plain non-nullable property
with no `getLiveComponentOrNull()` peek — deliberately unlike the same-named
schemas trait
([`belongs-to-live-component.md`](../schemas/belongs-to-live-component.md)),
whose property is nullable because a `Schema` can be built standalone for a
preview render. A table cannot: it reaches its records only through the host, so
an unbound table has nothing to render and the binding is a constructor-time
requirement rather than a later optional attachment. Both packages ship a trait
called `BelongsToLiveComponent` with different nullability — check the namespace
before assuming which one you have. This matches Filament, whose
`Table\Concerns\BelongsToLivewire` is likewise non-nullable against schemas'
nullable one.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The empty state heading hardcodes "No records"

Filament's `getEmptyStateHeading()` falls back to
`__('filament-tables::table.empty.heading', ['model' => $this->getPluralModelLabel()])`
— a translated string interpolating the table's plural model label, which is
itself derived from the Eloquent model class when not set explicitly. Sylament
returns the literal `'No records'`.

Two prerequisites are missing, and neither is a table concern. The **label
machinery** (`modelLabel()` / `pluralModelLabel()` / `getRecordTitle()`) needs a
record-class → human-label derivation that the record vocabulary has not grown
yet, plus a pluralizer — Filament reaches `Str::plural()` behind a
`locale_has_pluralization()` guard, and the Symfony equivalent
(`symfony/string`'s inflector) is a decision of its own. **i18n** is not wired
anywhere in the port: no `translation` component is a dependency of any bundle
and no message catalogue ships, so there is nothing for a translation key to
resolve against.

The literal is therefore a placeholder that reads correctly in English rather
than an API claim, and it is the only user-visible string the tables package
hardcodes. It reverses in two steps: the label machinery makes the heading say
*"No articles"*, and the i18n pass turns it into a catalogue lookup. Until then a
consumer wanting either writes `->emptyStateHeading(...)` explicitly, which is
fully ported.

### The empty state icon getter returns null, and the fallback lives in the template

Filament types `getEmptyStateIcon(): string | BackedEnum` — never null — because
the getter itself chains two fallbacks: `FilamentIcon::resolve(TablesIconAlias::EMPTY_STATE)`
for the registered alias override, then `Heroicon::OutlinedXMark`. Sylament types
it `string|BackedEnum|Markup|null` and returns only what the user set.

Only the **nullability** differs. The settable union is Filament's
`string | BackedEnum | Htmlable | Closure | null` under the standard
`Htmlable` → [`Twig\Markup`](../renames.md#idiom) translation, matching support's
own [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php)
— so a raw `<svg>` handed to `emptyStateIcon()` survives the getter untouched
rather than being escaped or rejected. Filament's own getter return type
(`string | BackedEnum`) is narrower than its property union and could not
actually carry the `Htmlable` arm it accepts; the getter here admits `Markup`
so the two agree.

The chain is not dropped, it moves: the alias hook and the `heroicons:x-mark`
default sit next to each other in the table template, which is where the rest of
the toolkit already puts them — the same shape support's `Modal` close button
uses. Keeping them in PHP would split one two-step fallback across a getter and
a template, and would make the getter lie about whether the user configured
anything. A caller asking *"did the user set an icon?"* gets a straight answer
here; a caller wanting the rendered icon reads the template's resolution. This is
permanent, and it is the general policy for icon getters in this port rather than
a table-specific choice.

_See also: the global [rename table](../renames.md) — `BelongsToLivewire` →
`BelongsToLiveComponent`, `livewire()` → `liveComponent()` and `getLivewire()` →
`getLiveComponent()` are the Livewire → UX LiveComponent idiom translation
recorded there, not divergences of this component._
