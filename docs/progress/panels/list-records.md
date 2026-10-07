---
description: 'The resource''s index page: a live component hosting the table the resource configures.'
ports:
  - vendor/filament/filament/src/Resources/Pages/ListRecords.php
owns:
  - packages/panels/src/Resources/Pages/ListRecords.php
  - packages/panels/templates/partials/Page/table.html.twig
---
# ListRecords (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Resources\Pages\ListRecords`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | [`Resources/Pages/ListRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | in progress | — | — |
| [Page table variant](../../../packages/panels/templates/partials/Page/table.html.twig) | no Filament counterpart — v5's list and manage-related pages have no view either; [`ListRecords::content()`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) and [`ManageRelatedRecords::content()`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php) return a schema holding an [`EmbeddedTable`](../../../vendor/filament/schemas/src/Components/EmbeddedTable.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The list page is a live component hosting the table the resource configures

The resource's index page: a live component that hosts a table the resource configures.
The whole class is a composition plus nine members: `$template`, `table()`, `getTitle()` and `schemas()` below, `getBreadcrumb()` — narrowed to a plain `List` off the [base](resource-pages.md) — and the four the modal record actions add, `form()`, `infolist()`, `getDefaultActionSchemaResolver()` and `getMountedActionSchemaRecord()`.

```php
abstract class ListRecords extends Page implements InteractsWithTable
{
    use HasTable { HasTable::makeTable as makeBaseTable; }   // Sylament\Tables\Concerns
    use HasTabs;                                             // Sylament\Panels\Resources\Concerns
}
```

One host contract declared here, three composed in total: actions' `InteractsWithActions`/`HasActions` **and** schemas' `InteractsWithSchemas`/`HasSchemas` sit on the plain [`Pages\Page`](page.md) base and are inherited, and re-`use`ing either here would not merely be redundant — the actions one would shadow the resource base's `getDefaultActionUrl()` with the trait's null default, and the schemas one would shadow the page base's empty `schemas()` with the trait's `['schema']` and [500 on the first dehydrate](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf).
There is no `DefaultActionTrait` either: the panel [`Page`](page.md) base already carries it, and re-`use`ing a trait an ancestor already uses is legal but would imply the base does not.
The `makeTable` alias is the one place this hierarchy does alias, so the override can call the original.
The three host traits share no member name (every `HasTable` member is `*Table*`-prefixed, actions' are `*Action*`-prefixed, schemas' are `*Schema*`-prefixed plus `fill()`/`oldState`), so the composition needs no `insteadof` and no aliasing — the one place Filament does alias (`makeTable as makeBaseTable`) exists to let its `makeTable()` override call the original, which our resource delegation does not need.
The cost is twelve public `LiveProp`s on the wire, all inherited: `tableHostId`, `tablePage`, `tableRecordsPerPage`, `tableSort`, `tableSearch`, `tableFilters`, `tableDeferredFilters`, `mountedActions`, `mountedActionsData`, `actionsHostId`, `oldState`, `validatedSchemas`.

`ListRecords` was also the **first shipped class in the repo to use any of those three host traits** — every other host is a test fixture or a demo component.
That matters beyond bookkeeping: PHPStan skips a trait no analysed class uses (the globally-ignored `trait.unused`), so all three bodies were unanalysed until this class existed, and giving them a user turns on both their standalone and their per-using-class analysis at once.
Whatever that surfaces belongs to the trait's own package, not here.

- `$template` points at [`@SylamentPanels/partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig) and is **required**: `Page::getTemplate()` otherwise falls back to the base chrome, whose `content` block renders `renderSchema('content')` — empty on a list page — so the page would render a heading with no table and no error anywhere.
  That variant `{% extends %}` the base chrome and overrides one block rather than repeating the [three-element page skeleton](routing.md), so the rhythm rules are inherited rather than restated.
- `table(Table $table): Table` stamps the resource's query and its record labels and title attribute, then hands the table to the resource — [query first, resource last](#the-list-pages-table-is-stamped-query-first-and-resource-last).
  The query has a second arm: a list page hanging under a parent record is seeded with [the resource's own record query narrowed to that parent](nested-resources.md#the-nested-list-page-ships-unexercised-by-any-shipped-page) instead, under the same root alias, so nothing else in the stamp changes.
  `mount(array $parentRecords = [])` is declared for it — nothing here resolves at mount, but a page that scopes its own query should not depend on that staying true.
- `getTitle()` is `static::$title ?? $resource->getTitleCasePluralRecordLabel()`, Filament verbatim with the static hop replaced by the instance one.
  It runs **before** the component mounts as well: [`PageController`](routing.md) reads it off the locator instance for the document `<title>`, so it must not depend on mounted state — the plural label does not.
- `schemas()` is **no longer declared here**, and the deletion is load-bearing rather than tidy.
  It used to return `[]` on this page because `HasSchemas::schemas()` defaults to `['schema']` and `getCachedSchemas()` then demands a `schema()` / `schemaSchema()` method the list page does not have, so the first `#[PreDehydrate] persistOldState()` threw `RuntimeException` and the page 500'd on its very first render.
  The **base** now answers `[]` ([why the discovery list is empty there](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf)), so the override is redundant — and keeping the trait alongside it would have been worse than redundant, since a trait outranks an inherited class.
  The schema host is here only so [`getTableFiltersForm()`](../tables/has-table.md) has somewhere to live; the tables fixture `SchemaTableHost` and the demo's `FiltersTableCard` still carry the override for the same reason, being hosts of their own rather than pages.

**Modal record actions.**
A resource that registers only an `index` page still gets every prebuilt record action — that *is* what Filament calls a simple resource, the absence of registered pages rather than a feature.
The URL hook answers null, the action mounts, and the schema inside the modal comes from `getDefaultActionSchemaResolver(Action)`, Filament's three arms minus their grid default: a `CreateAction` or `EditAction` takes `$this->form($schema)`, a `ViewAction` `$this->infolist($this->form($schema))`, each over the seeded schema untouched — which leaves the create/edit arm a bare `$this->form(...)` — so [the resource form's own column count survives into the modal](#a-modals-schema-keeps-the-resource-forms-own-column-count).
`form(Schema)` and `infolist(Schema)` are public delegations to the resource that exist **for that resolver** and are deliberately not added to `schemas()`, which stays `[]`: that list is the de/hydration walk, and a modal's schema is built per mount by [the actions host](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation), never persisted with the page.
`getMountedActionSchemaRecord()` answers the resource's record **class**, which is what a page showing no single record can seed a mounted schema with — the row actions get their record from the table frame instead.

`getDefaultActionRecordTitle(Action)` answers a mounted action's title for the actions the page itself owns.
A **row** action no longer reaches it: `table()` pushes the resource's `recordLabel()`, `pluralRecordLabel()` and `recordTitleAttribute()` onto the table — Filament's `Resource::configureTable()` — and `Action::getRecordTitle()` hops through the table before the host hook ([semantics](../actions/interacts-with-record.md#a-table-bound-action-titles-and-labels-through-its-table)), so a `ViewAction` on a row reads *"View Alpha"* through the table's own title read.
The hook still serves the page's header actions, which carry no table: when the action holds an object record and the resource declares a `$recordTitleAttribute` it returns `Resource::getRecordTitle($record)`, otherwise `null`, handing `Action::getRecordTitle()` back its record-label fallback.
The edit page's override is the sibling shape and reads its *own* record instead, since a record page has one.

**Header actions.**
`getHeaderActions()` returns the actions the template renders into the `Header` component's `actions` block — the same contract on all three page types, since the member is the [base's](resource-pages.md).
Declare them any way that reads well: a `<name>Action()` method on the page, or an action built inline in the list.
Nothing is bound or registered by hand — the base's `getCachedHeaderActions()` is what the templates read, and it stamps and registers the list on the way through, once per request ([how, and why the walk is lazy](custom-pages.md#header-actions-are-bound-lazily-rather-than-at-boot)):

```php
public function purgeAction(): Action
{
    return Action::make('purge')->requiresConfirmation()->action(…);
}

public function getHeaderActions(): array
{
    return [CreateAction::make(), $this->purgeAction()];
}
```

Nothing puts a Create button there by default, and that is parity, not a gap: Filament has no framework-level one either — its `make:filament-resource` generator writes `CreateAction::make()` into the generated page body.
We ship no generator, so until one exists the button is something a user (or the demo) writes by hand — and the line above is what that costs.

**The template.**
`partials/Page/table.html.twig` is eleven lines: it extends the base chrome and fills `{% block content %}` with the tabs strip and `{{ renderTable() }}`.
Everything else is the chrome's — the page root, the `sy-pa-page-header-main-ctn` wrapper holding the `Header` with the header actions in its `actions` block above `sy-pa-page-main` > `sy-pa-page-content`, and `<twig:SylamentActions:Modals />` last, outside the wrapper — so the three-class [page skeleton](routing.md) is inherited rather than repeated, and the root classes come from [`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class) (`sy-pa-resource-list-records-page` plus the slug-derived `sy-pa-resource-<slug>`, Filament's `fi-resource-<slug>`).
The variant [presumes a tabs host and a table host](form-actions.md#the-table-variant-presumes-two-host-traits-and-says-so-only-by-failing).
Two placement rules survive the move, both invisible until they break.
`renderTable()` takes no arguments and reads `this` out of the Twig context, requiring it to be an `InteractsWithTable` — a page template has `this`, but a `<twig:>` component nested inside one has its *own*, so a table wrapped in another component must be captured with `{% set table %}…{% endset %}` where `this` is still the page (the demo's `TableLiveCard` carries that note) — and a template that `{% extends %}` the chrome is still the page's own frame, so `this` is the page inside an overridden block.
And the `Modals` outlet must sit inside the page's own template **chain**, because it resolves its host by walking ux-twig-component's `ComponentStack` — this is exactly why [the document does not carry one](routing.md), and why the base chrome placing it once means [a child template must never add a second](../actions/modals.md#there-is-no-single-render-guard).
Both files are `partials/`, not `components/`: they are rendered from PHP through `template_from_method`, and a `<twig:>` invocation would find no `this`.

Three of the seven features that list once held ship.
**Tabs** ship as [`HasTabs`](has-tabs.md) — `$activeTab`, `getTabs()`, `getCachedTabs()`, `getTabsSchema()` and `modifyQueryWithActiveTab()` — the strip rendered above `renderTable()` from the same `{% if tabs is not empty %}` guard all three tab-bearing partials carry.
**Query-string binding** ships as [`getTableUrlMapping()`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page), overridden here to map six of the seven names Filament binds — `page` (identifier-prefixed), `sort`, `search`, `filters`, `tab` and [`grouping`](../tables/grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group).
The one left, `reordering`, is [deliberately unbound](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page).
Filament's own seven are **six `#[Url]` attributes plus `page`**, which rides Livewire's pagination binding rather than that attribute — one hook answers for all six here, which is the whole of the divergence.
And **row-click defaulting** ships as the `recordAction()` / `recordUrl()` seeding in `makeTable()`, which [resource pages answer where no action does](#the-row-click-falls-through-to-the-resources-own-view-or-edit-page).

Still deferred, all todo: the deprecated `getTableQuery()` / `modelLabel()` seeding, `content()` and its `EmbeddedTable`, and the `ManageRecords` subclass.
The parent-record scoping arm ships, and the page partial carries [the sub-navigation band](has-sub-navigation.md) beside it.

Three divergences are the list page's: [the table is stamped query-first and resource-last](#the-list-pages-table-is-stamped-query-first-and-resource-last), [header actions are bound lazily](custom-pages.md#header-actions-are-bound-lazily-rather-than-at-boot) and [a modal's schema keeps the resource form's own column count](#a-modals-schema-keeps-the-resource-forms-own-column-count).

## Divergences

### The list page's table is stamped query-first and resource-last

Filament's `ListRecords::makeTable()` is an eight-step chain (base table, query, parent scoping, active tab, labels, `recordAction`, `recordUrl`, then `static::getResource()::configureTable($table)`), and its own `table()` is a no-op passthrough that `makeTable()` never calls.
Ours is two lines in `table()`:

```php
$resource = $this->getResourceInstance();

return $resource->table($table
    ->query(fn (): QueryBuilder => $resource->getRecordQuery())
    ->recordLabel($resource->getRecordLabel(...))
    ->pluralRecordLabel($resource->getPluralRecordLabel(...))
    ->recordTitleAttribute($resource->getRecordTitleAttribute(...))
    ->recordTitle($resource->getRecordTitle(...)));
```

The **ordering** is what carries over: the query and the labels are stamped before the resource sees the table, so `Resource::table()` can override any of them (`->query()` again, `->modifyQueryUsing()`, `->recordLabel()`), and the resource's configuration is applied last so it wins.
The four label setters are Filament's `configureTable()` stamp, arriving here rather than on `Resource` because ours is an instance seam and Filament's is a static one; they are passed as first-class callables, so the table evaluates each only if it reads it.
`recordTitle()` is the one that looks redundant beside the attribute already stamped and is not: `Resource::getRecordTitle()` is overridable, and without the stamp the table would read the raw attribute through its own accessor and the override would never reach a row action.
What they buy is the whole [record-title chain](../actions/interacts-with-record.md#a-table-bound-action-titles-and-labels-through-its-table) for row actions — a `ViewAction`'s heading reads the record, and the empty state reads *"No articles"* rather than *"No records"*.
What is still gone is the middle of the chain, each piece with its own reason — the deprecated `getTableQuery()` seeding is a deprecated delegate, and the rest of `configureTable()`'s stamp (`authorizeReorder()`) is resource metadata whose table-side authorization arm is unported.

#### The row click falls through to the resource's own view or edit page

Row-click defaulting was the one this method was predicted to miss, and it landed — in `makeTable()` rather than in `table()`, which is the placement the prediction got wrong and the reason matters.
`table()` runs the resource hop last so a resource can override anything; `makeTable()` runs **before** `table()`, so seeding there means the resource's own `recordUrl()` still wins, which is the same precedence read from the other end and it composes with `parent::table()` being mandatory.

The fallthrough is Filament's, plus one hop Filament does not have.
Both closures walk `['view', 'edit']` against the table's own action set: `recordAction()` returns the first action that is visible and carries **no** URL (a URL'd action is a link, not a click target), and `recordUrl()` returns the first visible action's URL.
Where neither answers — the usual case, since a resource declares its row actions as page links — `recordUrl()` falls through to `$resource->getUrl('view' | 'edit', [...$this->parentRecords, 'record' => $record])`, which is what makes a plain list's rows clickable with nothing declared at all.
That last hop is the list page's alone: the [relation-manager tier](interacts-with-relationship-table.md) shares the two action probes verbatim and stops there, because a related record's page is reached through [`getDefaultActionUrl()`](interacts-with-relationship-table.md) instead.

`recordUrl()` is stamped only `if (! $table->hasCustomRecordUrl())`, Filament's own guard, so a resource that sets one keeps it; `recordAction()` is stamped unconditionally, because it has no such predicate over there either.

The query closure is worth one note: `->query()` takes a `Closure` rather than the builder, so `getRecordQuery()` runs at the table's first `getBaseQuery()` — after configuration, memoised for the request — instead of at page construction.

The consequence for a subclass: a `ListRecords` that overrides `table()` **must** call `parent::table($table)`, because those two lines are the only place the query stamp and the resource's own `table()` are applied.
Filament's `table()` is a passthrough its `makeTable()` ignores, so overriding it there costs nothing; overriding it here without the `parent::` call yields a table with no data source and none of the resource's columns or actions.
It fails loudly rather than subtly — tables refuses a configured table with neither `records()` nor `query()` — so this is a note about *where* the failure comes from, not a trap that ships a broken page.

### A modal's schema keeps the resource form's own column count

Filament's resource pages force a two-column grid onto every modal schema they resolve: `ListRecords`, `EditRecord` and `ViewRecord` each build `$schema->hasCustomColumns() ? $schema : $schema->columns(2)` before handing it to the resource's `form()`, and the full-page `defaultForm()` does the same with an inline-labels arm (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:94`, `EditRecord.php:300`, `CreateRecord.php:315`).
The port's resolvers pass the seeded schema through untouched — the list page's, which still carries both actions on one arm, reads:

```php
// ListRecords::getDefaultActionSchemaResolver()
$action instanceof CreateAction, $action instanceof EditAction => $this->form(...),
```

(the arrow function collapses to a first-class callable once the wrapper is gone — the schema still arrives by name, `HasSchema::getSchema()` injecting `$schema` along with `form` and `infolist`).
The edit page's own `EditAction` no longer takes this arm at all: it embeds [the page's own form](edit-record.md#the-edit-pages-own-edit-modal-is-the-page-form-itself), already configured, leaving the create and view arms to it.

The `hasCustomColumns()` guard reads as "the *action* may override it", but the schema it guards is the blank one the host seeds per mount — a resource form is configured **inside** `Resource::form()`, one call later, so the guard can never see what the resource itself set.
The result is that a resource whose `form()` declares a single-column layout renders one way on its own create/edit page and two-up in a modal, for no reason a reader of that form can find.
Dropping the default makes the resource form the only thing that decides, and a page or action that wants the two-column grid still says `$schema->columns(2)` — the same call Filament makes, moved to where its intent is visible.

The full-page arms need no equivalent decision: `defaultForm()` is not ported — `CreateRecord::form()` and `EditRecord::form()` stamp a state path, a record and an operation and hand the schema to the resource — so a page form already renders exactly what the resource declared, and this only aligns the modal with it.
