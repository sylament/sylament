# Table (tables)

[`Sylament\Tables\Table`](../../../packages/tables/src/Table.php) — the root
object of the tables package, the counterpart to schemas'
[`Schema`](../schemas/schema.md): a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) a
host builds through [`HasTable`](has-table.md), carrying the record data source
and the empty-state copy. It ports Filament's
[`Filament\Tables\Table`](../../../vendor/filament/tables/src/Table.php) together
with the `Table\Concerns\` traits it composes — six today
([`BelongsToLiveComponent`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php),
[`HasRecords`](../../../packages/tables/src/Table/Concerns/HasRecords.php),
[`HasEmptyState`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php),
[`HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php),
[`HasActions`](../../../packages/tables/src/Table/Concerns/HasActions.php),
[`HasRecordActions`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php)),
thirty-one in Filament. The six ported are folded into the surface table below
rather than earning rows upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing); the
twenty-five still unported keep a per-file row in the package
[README](README.md#features), the way
[`actions`](../actions/README.md#features) tracks the concerns `Action` has not
reached yet. The rows below group those files by feature, which is what the
per-file rows upstairs point at.

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
- [`tables/src/Table/Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php)
- [`tables/src/Table/Concerns/HasActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php)
- [`tables/src/Table/Concerns/HasRecordActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php)
- [`tables/src/Enums/RecordActionsPosition.php`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php)

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). A **skipped** row states its reason inline
instead, where the decision is one line and applies a policy recorded elsewhere.
Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(InteractsWithTable $liveComponent)`](../../../packages/tables/src/Table.php) | [`Table::make()`](../../../vendor/filament/tables/src/Table.php) | done | — | [diverges](../renames.md#make-arguments-move-from-constructors-to-setters) |
| [`liveComponent()` / `getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php) | [`BelongsToLivewire`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php) | done | — | [semantics](#the-host-binding-is-required-where-the-schemas-one-is-optional) |
| [`records()` / `getDataSource()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::records()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [has-table.md](has-table.md#the-records-closure-receives-no-named-injections-yet) |
| [`getRecords()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecords()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) |
| [`emptyStateHeading()` / `getEmptyStateHeading()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-empty-state-heading-hardcodes-no-records) |
| [`emptyStateDescription()` / `getEmptyStateDescription()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | in progress | — | [detail](#the-create-action-default-description-waits-on-the-model-label) |
| [`emptyStateIcon()` / `getEmptyStateIcon()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-empty-state-icon-getter-returns-null-and-the-fallback-lives-in-the-template) |
| [`$evaluationIdentifier = 'table'` + the `$liveComponent` name injection](../../../packages/tables/src/Table.php) | [`Table::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Table.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Table.php) + [the partial it names](../../../packages/tables/templates/partials/Table/index.html.twig) | [`$view = 'filament-tables::index'`](../../../vendor/filament/tables/src/Table.php), [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) |
| `emptyState()` (a whole replacement view) | [`HasEmptyState::emptyState()`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | todo | — | — |
| `emptyStateActions()` / `pushEmptyStateActions()` | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | blocked | table actions (M1 · 6) | — |
| Record labels — `modelLabel()`, `pluralModelLabel()`, `recordTitle()`, `recordTitleAttribute()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | todo | — | [diverges](#the-empty-state-heading-hardcodes-no-records) |
| [Record identity — `getRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecordKey()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) · [diverges](#rows-and-cells-carry-no-morph-stable-key) |
| Pivot record identity — `hasPivotRecordKeys()`, `allowDuplicates()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | skipped | — | The `BelongsToMany` pivot arm — it stays unported with the rest of the Eloquent relation surface |
| [`columns()` / `getColumns()` / `getColumn()`](../../../packages/tables/src/Table/Concerns/HasColumns.php) | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | in progress | — | [column.md](column.md) · [detail](#columns-takes-columns-only-and-does-not-push) |
| Column grouping and layouts — `pushColumns()`, `getColumnsLayout()`, `hasColumnGroups()` | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | todo | — | [detail](#columns-takes-columns-only-and-does-not-push) |
| The column manager — user-toggleable column visibility | [`HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | todo | — | — |
| The `query()` lane — `query()`, `hasQuery()`, `getModel()`, `relationship()` | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | — |
| Pagination and polling — `paginated()`, `deferLoading()`, `poll()` | [`CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php), [`CanDeferLoading`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php), [`CanPollRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Sorting and searching — `defaultSort()`, `searchable()`, the session persistence | [`CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php), [`CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Filters — `filters()`, the indicators, the query-string identifier | [`HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php), [`HasFilterIndicators`](../../../vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php), [`HasQueryStringIdentifier`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | todo | — | — |
| [`recordActions()` / `pushRecordActions()` / `getRecordActions()` / `getFlatRecordActions()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`Table\Concerns\HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | in progress | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| [`recordActionsPosition()` / `getRecordActionsPosition()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) + [`RecordActionsPosition`](../../../packages/tables/src/Enums/RecordActionsPosition.php) | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php), [`Enums/RecordActionsPosition`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php) | in progress | — | [detail](#the-position-default-has-no-content-arm-to-choose-between-yet) · [diverges](#the-position-setter-drops-filaments-string-arm) |
| [The flat action map — `getAction()` / `hasAction()` / `getFlatActions()` / `cacheAction()` / `mergeCachedFlatActions()`](../../../packages/tables/src/Table/Concerns/HasActions.php) | [`Table\Concerns\HasActions`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php) | in progress | — | [detail](#the-flat-action-map-has-no-bulk-half-and-no-removal) |
| `modifyUngroupedRecordActionsUsing()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| Record-action presentation — `recordActionsColumnLabel()`, `recordActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | blocked | the `Table` template | — |
| The deprecated v3 aliases — `actions()`, `pushActions()`, `actionsPosition()`, `actionsColumnLabel()`, `actionsAlignment()`, `getActions()`, `getActionsPosition()`, `getActionsColumnLabel()`, `getActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | skipped | — | The project has never ported a deprecated Filament surface; each is a one-line delegate to the `record*` name above |
| Header, toolbar and bulk actions, `recordAction()`, `recordUrl()` | [`HasHeaderActions`](../../../vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php), [`HasToolbarActions`](../../../vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php), [`HasBulkActions`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php), [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | blocked | table actions (M1 · 6) | — |
| Grouping and summaries — `groups()`, `defaultGroup()`, the summarizers | [`CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | todo | — | — |
| Reordering — `reorderable()` | [`CanReorderRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php) | todo | — | — |
| Presentation — `striped()`, `stackedOnMobile()`, `content()`, `header()`, the headings, `recordClasses()` | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) and five siblings | todo | — | — |
| `HasArguments`, `HasExtraAttributes`, `HasDefaultDataFormattingSettings` | [`Table.php`'s remaining composed traits](../../../vendor/filament/tables/src/Table.php) | todo | — | — |

The nine **done** rows are `make()`, the host binding, the data-source
declaration, the host-delegating `getRecords()` and `getRecordKey()`, the
empty-state heading and icon, the closure-injection wiring, and the partial that
renders all of it; the column set, the empty-state description and the three
action rows are partly in, one step behind them.

The PHP surface is covered by
[`TableTest`](../../../packages/tables/tests/Unit/TableTest.php) — `make()`
binding the host, `records()` storing and returning the closure identity, the
host-delegating `getRecordKey()`, the empty-state defaults, `columns()` keying by
name and binding the table on each member while a second call resets the set, and
both injection paths at once
(`fn ($liveComponent)` resolved by the class's own
[`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/tables/src/Table.php),
`fn (Table $table)` by the `EvaluatesClosures` fallback beneath it — by *name*,
not by type: the parameter matches `$evaluationIdentifier`, the disjunct that
short-circuits ahead of the `is_a($this, $parameterType)` arm beside it, which a
differently named parameter would be needed to reach) — and, for `getRecords()`,
by the host-side
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
it delegates into.

The action rows split their coverage by tier.
[`HasRecordActionsTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasRecordActionsTest.php)
drives everything through a real `Table`: one registration flattening a group
into the same name-keyed map as an ungrouped action, a second `recordActions()`
call resetting the set where `pushRecordActions()` appends to it, the
`Size::Small` + link-template defaults losing to an explicit `size()`/`button()`,
the table bound onto both an ungrouped action and a group member, the
`AfterColumns` default with the two dropdown placements it picks by side, a
closure position evaluated with the table injected, and the `InvalidArgumentException`
a non-action member earns.
[`HasActionsTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasActionsTest.php)
covers the map on its own, through an object composing the trait alone — the
`shouldOverwriteExistingAction: false` arm of `cacheAction()` has no public
caller yet, so nothing on `Table` reaches it.

The partial is covered by the Integration
[`TableTest`](../../../packages/tables/tests/Integration/TableTest.php), which
renders a real table through the booted kernel's Twig: the header labels and
cell values for object records, one `sy-ta-row` per record (the per-cell
`{% do column.record(record) %}` rebinding), the default empty state displacing
the `<table>` entirely with its `heroicons:x-mark` fallback rendered as an
`<svg>` and no description paragraph, a fully configured empty state, a `Markup`
icon reaching the raw-passthrough arm of
[`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php),
and a `<script>` in a record value coming back escaped.

Two of its cases pin the **structure** rather than the strings, because this is
the DOM contract the tables stylesheet and the demo's browser tests are written
against. They read the render back through a `DomCrawler` with the child
combinator — `div.sy.sy-ta > div.sy-ta-ctn > table.sy-ta-table`,
`tr.sy-ta-row > td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-text`, and
`div.sy-ta-empty-state[role="status"] > div.sy-ta-empty-state-content` holding
the icon background's `<svg>`, the `<h3>` heading and the `<p>` description — so
a flattened wrapper or a renamed class fails here rather than silently
downstream. A substring assertion passes through both of those changes, which is
why the counts are selected and not grepped.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The create-action default description waits on the model label

Filament's `Table::setUp()` registers a default `emptyStateDescription()` closure
that returns *"Create a `<model label>` to get started"* — but only when the
table has an action named `create`, and `null` otherwise. The action half is now
answerable: [`hasAction('create')`](#the-flat-action-map-has-no-bulk-half-and-no-removal)
reads the flat map a `recordActions()` call fills. The **model label** is still
missing, with the rest of the
[label machinery](#the-empty-state-heading-hardcodes-no-records), so there is no
string to interpolate. Sylament's `Table` therefore does not override `setUp()`
at all, and `getEmptyStateDescription()` returns `null` until a user sets one.
The default returns in the same change as the label machinery. Note what
`hasAction('create')` will read by then: the flat map is shared across every
registration lane, so a `create` action declared as a header action counts too —
that lane is [still blocked](#surface), and today only `recordActions()` fills
the map.

### Record actions register without the per-action modifier hook

`recordActions()`, `pushRecordActions()`, `getRecordActions()` and
`getFlatRecordActions()` are ported whole: the reset-then-push shape, the
`Size::Small` + `LINK_TEMPLATE` defaults stamped through
[`defaultSize()`/`defaultTemplate()`](../actions/action.md) so an explicit
`size()`/`button()` still wins, the group flattening into the shared flat map,
the table bound onto every member, and the by-side dropdown placement
`getRecordActions()` re-stamps on each read.

Two things Filament does here are not in. `modifyUngroupedRecordActionsUsing()`
— a closure `pushRecordActions()` evaluates against every ungrouped action with
`['action' => $action]` injected, which Filament's docs reach through a
[`Table::configureUsing()`](../support/component-manager.md) registration to
restyle every table's ungrouped actions at once — has no consumer in the port
yet and is **todo** on a row of its own. And the
`->defaultDropdownTeleport(true)` Filament chains onto the placement stamp is
simply not callable: the whole teleport family is deferred on the action side,
tracked by
[`action-group.md`](../actions/action-group.md#dropdownwidth-is-a-css-string-dropdownteleport-and-dropdownautoplacement-are-deferred),
so the stamp here is the placement half alone and gains the second call if that
row ever lands.

### The position default has no content arm to choose between yet

Filament's `getRecordActionsPosition()` falls back through *two* defaults: with
no content grid and no columns layout it answers `AfterColumns`, and otherwise
`AfterContent` — the actions move below the card when the table renders records
as a grid instead of as rows. Sylament answers `AfterColumns` unconditionally.

Nothing is dropped in principle. `getContentGrid()` and `hasColumnsLayout()` are
both unported (the `Presentation` and `Column grouping and layouts` rows above),
so there is no state either branch could read — a two-way fallback would be a
constant with extra steps. The `AfterContent` case of the enum ships anyway,
because the enum is a verbatim port and a partial one would be a worse lie. The
second arm returns in the same change as the content grid.

### The flat action map has no bulk half and no removal

`getAction()`, `hasAction()`, `getFlatActions()`, `cacheAction()` and
`mergeCachedFlatActions()` are Filament's, minus everything that mentions a bulk
action: `getBulkAction()`, `getFlatBulkActions()`, `hasBulkAction()`, the
`$flatBulkActions` property, and the `instanceof BulkAction` branch that sorts a
cached action into one map or the other. There is no `BulkAction` class in the
port — [`action.md`](../actions/action.md) tracks it — so the branch has nothing
to test and the second map nothing to hold. The bulk half returns with that
class.

`removeCachedActions()` is likewise out: its only Filament callers are
`HasHeaderActions` and `HasToolbarActions`, which re-register a named action and
evict the one they replace — both unported, so nothing can evict anything yet.

One smaller trim is deliberate rather than incidental.
`mergeCachedFlatActions()` drops Filament's `$shouldOverwriteExistingActions`
passthrough, while `cacheAction()` keeps its own
`$shouldOverwriteExistingAction`. That looks lopsided until you find who passes
`false`: only `pushEmptyStateActions()`, whose whole point is that an empty-state
action must **not** displace a same-named record or header action already in the
map. Both members exist to serve that one caller, which is
[blocked](#surface) — so the parameter stays where a caller will reach it and
the passthrough returns with the caller that needs it.

### `columns()` takes columns only, and does not push

Filament's `columns()` accepts three kinds of member —
`Column`, `ColumnGroup` and a `Columns\Layout\Component` — resets six
properties, and delegates to `pushColumns()`, which sorts each member into the
flat `$columns` map, the `$columnsLayout` list and the `$columnGroups` map,
then walks the result to cache each column's action. Sylament's takes
`array<Column>`, resets one property, and keys the map by
[`getName()`](column.md).

Everything trimmed is a tier that does not exist yet: groups and layouts are
unported ([column.md](column.md) tracks both), and column actions arrive with
table actions in **M1 · 6**, so the action-caching walk has nothing to cache.
What is kept is the shape that matters — `columns()` **resets** rather than
appends, and binds itself to each member as it goes, so a second call replaces
the set and every column can reach the table and, through it, the host. Both are
pinned by [`TableTest`](../../../packages/tables/tests/Unit/TableTest.php).
`pushColumns()` returns as the public append seam when the grouping tier lands.

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

`getRecordKey($record)` is the same one line against
[`getTableRecordKey()`](has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key),
and reads the same way: the *convention* for identifying a record is the host's,
and the table is the seam a template or a column reaches it through. That is why
the contract grew `getTableRecordKey()` rather than the table growing a resolver
of its own — a second implementation of the `id` read would be one more place to
keep in step with
[`InteractsWithRecord::resolveRecordKey()`](../actions/action.md).

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

### The partial emits no header scaffolding and no content wrapper

Filament's `index.blade.php` nests the table five deep — `fi-ta` > `fi-ta-ctn` >
`fi-ta-main` > `fi-ta-content-ctn` > `table.fi-ta-table` — and the two header
containers inside `fi-ta-main` (`fi-ta-header-ctn`, `fi-ta-header-toolbar`) are
emitted **unconditionally**, hidden with `x-cloak` + `x-show` when the table has
no heading, description or toolbar action. Sylament's partial is three deep —
`sy sy-ta` > `sy-ta-ctn` > `table.sy-ta-table` or `div.sy-ta-empty-state` — with
no `sy-ta-main`, no `sy-ta-content-ctn`, neither header container, and no
`aria-label` on the `<table>`.

The wrappers are not markup for their own sake in Filament: they exist so Livewire's
DOM morph finds the same nodes before and after a toolbar's visibility flips,
which is why they render empty rather than being wrapped in an `@if`. Nothing
here can flip yet — the heading, description, toolbar actions, filters and
pagination they host all sit in **todo** or **blocked** rows above, and the
`<table>`'s `aria-label` needs the [record labels](#the-empty-state-heading-hardcodes-no-records)
that the same rows wait on. A wrapper emitted now would be a permanently empty
node with a class no stylesheet targets.

Provisional, and the reversal is mechanical: each container comes back in the
change that lands the first feature it holds, at which point its emptiness is
transient rather than permanent. The **class names** are already reserved by the
Filament originals, so nothing about the eventual DOM depends on inventing them
later.

### Rows and cells carry no morph-stable key

Filament stamps `wire:key="{{ $this->getId() }}.table.records.{{ $recordKey }}"`
on every `<tr>` and `wire:key="....table.record.{{ $recordKey }}.column.{{ $column->getName() }}"`
on every `<td>`, so Livewire's morph pairs a row with the same record across a
re-render instead of matching by position. Sylament's rows and cells carry no key
at all.

Only the template half is left now. [`HasTable::getTableRecords()`](has-table.md)
keys its map by record key exactly as Filament does, and
[`getRecordKey()`](#getrecords-delegates-to-the-host-and-fetches-nothing) — the
seam `$recordKey` comes from — is **done** on the row above. What has not
happened is the partial reading either: its loop still iterates values rather
than keys, and stamps nothing onto the `<tr>` or `<td>`. Emitting a positional
index instead would be worse than emitting nothing — it morphs row 2 onto row 2
after a delete, silently carrying the wrong record's DOM state across.

Provisional, and now purely a template change: the keys go on when the partial
grows its record-action column, still in **M1 · 6**. Until then every table
render replaces its rows wholesale, which is correct — just not incremental.

### The position setter drops Filament's `string` arm

Filament types both `recordActions()`'s `$position` and `recordActionsPosition()`
as `RecordActionsPosition | string | Closure | null`, but
`getRecordActionsPosition(): RecordActionsPosition` returns whatever
`evaluate()` produced without converting — so a `string` accepted by the setter
is a `TypeError` at the getter, every time. The `string` arm is a v3 leftover
from when positions were string constants, and nothing in Filament converts it.
Sylament types both `RecordActionsPosition|Closure|null` and rejects the string
at the call site instead.

The consequence is the reverse of the usual compatibility argument: keeping the
arm would preserve an API that cannot be used successfully, while dropping it
turns a runtime crash deep in the getter into a type error at the setter the
caller wrote. A migrating user passing `'after-columns'` gets told immediately,
at the line that is wrong. Permanent — it reverses only if Filament ever adds
the conversion the arm implies, at which point this becomes a real feature
rather than a dead union member.

_See also: the global [rename table](../renames.md) — `BelongsToLivewire` →
`BelongsToLiveComponent`, `livewire()` → `liveComponent()` and `getLivewire()` →
`getLiveComponent()` are the Livewire → UX LiveComponent idiom translation
recorded there, not divergences of this component._
