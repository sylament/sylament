---
description: 'The resource index page: a live component hosting the table the resource configures, with its table template variant.'
ports:
  - vendor/filament/filament/src/Resources/Pages/ListRecords.php
owns:
  - packages/panels/src/Resources/Pages/ListRecords.php
  - packages/panels/templates/partials/Page/table.html.twig
---
# ListRecords (panels)

`Sylament\Panels\Resources\Pages\ListRecords` extends the resource-page base [`Resources\Pages\Page`](resource-pages.md) and implements tables' `InteractsWithTable`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`use HasTable`](../../../packages/panels/src/Resources/Pages/ListRecords.php), aliasing `makeTable` as `makeBaseTable` | `use InteractsWithTable { makeTable as makeBaseTable; }` | done | — | [semantics](#the-page-composes-one-host-trait-and-inherits-the-other-two) |
| [`use HasTabs`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | the same insert | done | — | [has-tabs.md](has-tabs.md) |
| [`$template`](../../../packages/panels/src/Resources/Pages/ListRecords.php) and [`partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig) | `ListRecords::content()`'s schema, with no view of its own | done | — | [semantics](#the-template-is-required-and-extends-the-chrome) · [semantics](#rendertable-reads-this-so-the-variant-extends-the-chrome-rather-than-wrapping-it) |
| [`mount()`](../../../packages/panels/src/Resources/Pages/ListRecords.php), taking `$parentRecords` | [`ListRecords::mount()`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | done | — | [nested-resources.md](nested-resources.md) |
| `authorizeAccess()` | `ListRecords::authorizeAccess()` | blocked | authorization | — |
| [`table()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `ListRecords::table()`, `makeTable()`'s query and label stamps, `Resource::configureTable()` | done | — | [diverges](#the-list-pages-table-is-stamped-query-first-and-resource-last) |
| [`makeTable()`](../../../packages/panels/src/Resources/Pages/ListRecords.php), the row-click seeding | `makeTable()`'s `recordAction()` / `recordUrl()` closures | done | — | [diverges](#the-row-click-falls-through-to-the-resources-own-view-or-edit-page) |
| [`getTableUrlMapping()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | the page's `#[Url]` properties | done | — | [diverges](#the-query-string-binding-is-one-hook-and-reordering-stays-unbound) |
| [`getTitle()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `ListRecords::getTitle()` | done | — | [semantics](#gettitle-runs-before-the-page-mounts) |
| [`getBreadcrumb()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `ListRecords::getBreadcrumb()` | done | — | [diverges](#the-breadcrumb-is-a-literal-not-a-translation-key) |
| [`form()` / `infolist()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | the same two | done | — | [semantics](#a-page-with-only-an-index-still-gets-every-record-action-as-a-modal) |
| [`getDefaultActionSchemaResolver()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `ListRecords::getDefaultActionSchemaResolver()` | done | — | [diverges](#a-modals-schema-keeps-the-resource-forms-own-column-count) |
| [`getMountedActionSchemaRecord()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `ListRecords::getMountedActionSchemaModel()` | done | — | [renames.md](../renames.md#renames) |
| [`getDefaultActionRecordTitle()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `InteractsWithActions::getDefaultActionRecordTitle()`, which `ListRecords` leaves at `null` | in progress | — | [detail](#the-header-action-record-title-is-untested) · [diverges](#a-header-actions-record-title-comes-from-the-resource) |
| [`getPageClasses()`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | `ListRecords::getPageClasses()` | done | — | [custom-pages.md](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class) |
| `getSubNavigation()`, the cluster arm | `ListRecords::getSubNavigation()` | skipped | — | [diverges](has-sub-navigation.md#the-cluster-arms-are-not-ported-so-the-resource-page-hard-override-is-moot) |
| `content()`: the tabs, the table render hooks and the `EmbeddedTable` | `ListRecords::content()` | blocked | schemas' `EmbeddedTable` | [detail](#content-waits-on-embeddedtable) |
| `getTableQuery()` / `getPluralModelLabel()` | the two deprecated seeding hooks | skipped | — | [diverges](#the-deprecated-seeding-hooks-are-not-ported) |

## Details

### The header-action record title is untested

`getDefaultActionRecordTitle()` exists and answers [the resource's record title](#a-header-actions-record-title-comes-from-the-resource) for an action the page owns.
No test mounts a header action holding an object record on a list page, so the arm that returns `Resource::getRecordTitle($record)` is unexercised.

### `content()` waits on `EmbeddedTable`

Filament's `content()` places the tabs component, the `RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` and `_AFTER` render hooks and an `EmbeddedTable` in one schema.
Schemas has no `EmbeddedTable` yet, so the [table variant](#the-template-is-required-and-extends-the-chrome) renders the strip and the table directly.

## Semantics

### The page composes one host trait and inherits the other two

`ListRecords` composes tables' `HasTable` and its own `HasTabs`; actions' `HasActions` and schemas' `HasSchemas` sit on the plain [`Pages\Page`](page.md) base and are inherited, as is `DefaultActionTrait`.
Re-`use`ing either host trait here would not merely be redundant, because a trait on the leaf outranks an inherited method: the actions one would shadow the resource base's `getDefaultActionUrl()` with the trait's null default, and the schemas one would shadow the page base's empty `schemas()` with the trait's `['schema']` and [500 on the first dehydrate](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf).
The page is a schema host only so [`getTableFiltersForm()`](../tables/has-table.md) has somewhere to live.
The `makeTable` alias is Filament's, and it is what lets the page's `makeTable()` override call the original.

### The template is required, and extends the chrome

`$template` points at [`@SylamentPanels/partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig), which `ManageRelatedRecords` names too.
It is **required**: `Page::getTemplate()` otherwise falls back to the base chrome, whose `content` block renders `renderSchema('content')` — empty on a list page — so the page would render a heading with no table and no error anywhere.
The variant `{% extends %}` the chrome and fills only `{% block content %}`, with the tabs strip and `{{ renderTable() }}`, so the page root, the header and the actions outlet are [the chrome's](custom-pages.md#the-block-contract-is-the-page-bodys-public-api).
It [presumes a tabs host and a table host](form-actions.md#the-table-variant-presumes-two-host-traits-and-says-so-only-by-failing).

### `renderTable()` reads `this`, so the variant extends the chrome rather than wrapping it

`renderTable()` takes no arguments and reads `this` out of the Twig context, requiring it to be an `InteractsWithTable`.
A page template has `this`, but a `<twig:>` component nested inside one has its *own*, so a table wrapped in another component must be captured with `{% set table %}…{% endset %}` where `this` is still the page.
A template that `{% extends %}` the chrome is still the page's own frame, so `this` is the page inside an overridden block.
The same holds for the `Modals` outlet the chrome places, which resolves its host by walking ux-twig-component's `ComponentStack`, and is why [a child template must never add a second](../actions/modals.md#there-is-no-single-render-guard).
The template is a `partials/` file, not a `components/` one: it is rendered from PHP through `template_from_method`, and a `<twig:>` invocation would find no `this`.

### `getTitle()` runs before the page mounts

`getTitle()` is `static::$title ?? $resource->getTitleCasePluralRecordLabel()`, Filament's with the static hop replaced by the instance one.
[`PageController`](routing.md) reads it off the un-mounted locator instance for the document `<title>`, so it must not depend on mounted state; the plural label does not.

### A page with only an index still gets every record action as a modal

A resource that registers only an `index` page still gets every prebuilt record action — that *is* what Filament calls a simple resource, the absence of registered pages rather than a feature.
The URL hook answers null, the action mounts, and the schema inside the modal comes from `getDefaultActionSchemaResolver(Action)`: a `CreateAction` or `EditAction` takes `$this->form(...)`, a `ViewAction` `$this->infolist($this->form($schema))`, each over the seeded schema untouched ([the column count](#a-modals-schema-keeps-the-resource-forms-own-column-count)).
`form(Schema)` and `infolist(Schema)` are public delegations to the resource that exist **for that resolver** and are deliberately not added to `schemas()`, which stays `[]`: that list is the de/hydration walk, and a modal's schema is built per mount by [the actions host](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation), never persisted with the page.
`getMountedActionSchemaRecord()` answers the resource's record **class**, which is what a page showing no single record can seed a mounted schema with — the row actions get their record from the table frame instead.

### A list page has no Create button until it declares one

`getHeaderActions()` is empty by default, and that is parity: Filament has no framework-level Create button either — its `make:filament-resource` generator writes `CreateAction::make()` into the generated list page.
The port ships no generator, so a list page lists `CreateAction::make()` in its own `getHeaderActions()`.

## Divergences

### The list page's table is stamped query-first and resource-last

Filament's `ListRecords::makeTable()` is one chain (base table, query, parent scoping, active tab, labels, `recordAction`, `recordUrl`, then `static::getResource()::configureTable($table)`), and its own `table()` is a no-op passthrough that `makeTable()` never calls.
Ours stamps the query and the labels in `table()` and hands the table to the resource in the same statement:

```php
$resource = $this->getResourceInstance();
$parentRecord = $this->getParentRecord();

return $resource->table($table
    ->query($parentRecord === null
        ? $resource->getRecordQuery(...)
        : fn (): QueryBuilder => $resource->getParentScopedRecordQuery($parentRecord))
    ->recordLabel($resource->getRecordLabel(...))
    ->pluralRecordLabel($resource->getPluralRecordLabel(...))
    ->recordTitleAttribute($resource->getRecordTitleAttribute(...))
    ->recordTitle($resource->getRecordTitle(...)));
```

The **ordering** is what carries over: the query and the labels are stamped before the resource sees the table, so `Resource::table()` can override any of them (`->query()` again, `->modifyQueryUsing()`, `->recordLabel()`), and the resource's configuration is applied last so it wins.
A list page hanging under a parent record is seeded with [the resource's own record query narrowed to that parent](nested-resources.md) under the same root alias, so nothing else in the stamp changes.
The four label setters are Filament's `configureTable()` stamp, arriving here rather than on `Resource` because ours is an instance seam and Filament's is a static one; they are passed as first-class callables, so the table evaluates each only if it reads it.
`recordTitle()` is the one that looks redundant beside the attribute already stamped and is not: `Resource::getRecordTitle()` is overridable, and without the stamp the table would read the raw attribute through its own accessor and the override would never reach a row action.
What they buy is the whole [record-title chain](../actions/action.md#a-table-bound-action-titles-and-labels-through-its-table) for row actions — a `ViewAction`'s heading reads the record, and the empty state reads *"No articles"* rather than *"No records"*.
The rest of `configureTable()`'s stamp, `authorizeReorder()`, is resource metadata whose table-side authorization arm is unported.

The query is a `Closure` rather than the builder, so `getRecordQuery()` runs at the table's first `getBaseQuery()` — after configuration, memoised for the request — instead of at page construction.

The consequence for a subclass: a `ListRecords` that overrides `table()` **must** call `parent::table($table)`, because that statement is the only place the query stamp and the resource's own `table()` are applied.
Filament's `table()` is a passthrough its `makeTable()` ignores, so overriding it there costs nothing; overriding it here without the `parent::` call yields a table with no data source and none of the resource's columns or actions.
It fails loudly rather than subtly — tables refuses a table with neither `records()`, `query()` nor `relationship()` — so this is a note about *where* the failure comes from, not a trap that ships a broken page.

### The row click falls through to the resource's own view or edit page

The seeding sits in `makeTable()` rather than in `table()`: `getTable()` runs `table()` over what `makeTable()` built, so seeding first means the resource's own `recordUrl()` still wins, which is the same precedence as the stamp above read from the other end.
Both closures walk `['view', 'edit']` against the table's own action set: `recordAction()` returns the first action that is visible and carries **no** URL (a URL'd action is a link, not a click target), and `recordUrl()` returns the first visible action's URL.
Where neither answers — the usual case, since a resource declares its row actions as page links — `recordUrl()` falls through to `$resource->getUrl('view' | 'edit', [...$this->parentRecords, 'record' => $record])` for the first of the two pages the resource registers, which is what makes a plain list's rows clickable with nothing declared at all.
That hop is Filament's, minus its `can{View,Edit}($record)` gate, which waits on authorization.
It is the list page's alone: the [relationship-table tier](interacts-with-relationship-table.md#both-seeding-probes-clone-the-action-where-vendor-clones-in-one-of-four) shares the two action probes and stops there, because a related record's page is reached through its `getDefaultActionUrl()` instead.

`recordUrl()` is stamped only `if (! $table->hasCustomRecordUrl())`, Filament's own guard, so a resource that sets one keeps it; `recordAction()` is stamped unconditionally, because Filament has no such predicate for it either.

### The query-string binding is one hook, and `reordering` stays unbound

Filament binds seven names on this page: six `#[Url]` properties (`reordering`, `filters`, `grouping`, `search`, `sort`, `tab`) plus `page`, which rides Livewire's pagination binding rather than that attribute.
Ours overrides [`getTableUrlMapping()`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) to map six of them — `page` (prefixed by the host's query-string identifier), `sort`, `search`, `filters`, [`grouping`](../tables/grouping.md) and `tab` — in one method.
`reordering` is [deliberately unbound](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page).

### The breadcrumb is a literal, not a translation key

Filament's `getBreadcrumb()` falls back to `__('filament-panels::resources/pages/list-records.breadcrumb')`; ours falls back to the literal `'List'`, because the port has no translation layer ([the deferred i18n pass](../../internals/i18n.md#translation-is-one-deferred-pass-not-a-per-component-port)).
Filament types the return `?string`; ours is `string`, because the fallback is never null.

### A header action's record title comes from the resource

Filament's `ListRecords` leaves `getDefaultActionRecordTitle()` at `InteractsWithActions`' `null`, so an action without a table falls back to its record label.
Ours answers `Resource::getRecordTitle($record)` when the action holds an object record and the resource declares a `$recordTitleAttribute`, and `null` otherwise, handing `Action::getRecordTitle()` back its record-label fallback.
A **row** action never reaches the hook: `table()` stamps the resource's title onto the table, and `Action::getRecordTitle()` asks the table before the host, so the hook serves only the page's own header actions, which carry no table.
The record pages' overrides are the sibling shape: they read their own record.

### The deprecated seeding hooks are not ported

Filament deprecates `getTableQuery()` and `getPluralModelLabel()` on this page, pointing to an override of `table()` instead, which is the seam the port's stamp already runs through.

### A modal's schema keeps the resource form's own column count

Filament's resource pages force a two-column grid onto every modal schema they resolve: `ListRecords`, `EditRecord` and `ViewRecord` each build `$schema->hasCustomColumns() ? $schema : $schema->columns(2)` before handing it to the resource's `form()`, and the full-page `defaultForm()` does the same with an inline-labels arm (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:94`, `EditRecord.php:300`, `CreateRecord.php:315`).
The port's resolvers pass the seeded schema through untouched — the list page's, which carries both actions on one arm, reads:

```php
// ListRecords::getDefaultActionSchemaResolver()
$action instanceof CreateAction, $action instanceof EditAction => $this->form(...),
```

(the arrow function collapses to a first-class callable once the wrapper is gone — the schema still arrives by name, `HasSchema::getSchema()` injecting `$schema` along with `form` and `infolist`).
The edit page's own `EditAction` does not take this arm: it embeds [the page's own form](edit-record.md#the-edit-pages-own-edit-modal-is-the-page-form-itself), already configured, leaving the create and view arms to it.

The `hasCustomColumns()` guard reads as "the *action* may override it", but the schema it guards is the blank one the host seeds per mount — a resource form is configured **inside** `Resource::form()`, one call later, so the guard can never see what the resource itself set.
The result is that a resource whose `form()` declares a single-column layout renders one way on its own create/edit page and two-up in a modal, for no reason a reader of that form can find.
Dropping the default makes the resource form the only thing that decides, and a page or action that wants the two-column grid still says `$schema->columns(2)` — the same call Filament makes, moved to where its intent is visible.

The full-page arms need no equivalent decision: `defaultForm()` is not ported — `CreateRecord::form()` and `EditRecord::form()` stamp a state path, a record and an operation and hand the schema to the resource — so a page form already renders exactly what the resource declared, and this only aligns the modal with it.
