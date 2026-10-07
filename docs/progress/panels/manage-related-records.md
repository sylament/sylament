---
description: 'ManageRelatedRecords, one owner record''s association as a routed page: the sibling of a relation manager and the listing of a nested resource.'
ports:
  - vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php
owns:
  - packages/panels/src/Resources/Pages/ManageRelatedRecords.php
---
# ManageRelatedRecords (panels)

[`ManageRelatedRecords`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) is one owner record's association as a **page** of its own: the routed sibling of a [relation manager](relation-manager.md), reached through the record's [sub-navigation](has-sub-navigation.md), and the listing a [nested resource](nested-resources.md)'s records are reached through.

It is a resource page that is also a relationship-table host: it extends [`Resources\Pages\Page`](resource-pages.md) — so it carries the actions host, the breadcrumb chain and the parent-record chain — and composes [`InteractsWithRecord`](interacts-with-record.md) for the record it is opened on and [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md) for the association it lists, with tables' `HasTable` beside them; schemas' `HasSchemas` arrives through the page base.
Its record **is** the relationship's owner, which is the whole identity of the class: `getOwnerRecord()` is `getRecord()`.

A page is three statics and nothing else — for an `Article` whose `sections` also have a resource of their own:

```php
#[AsLiveComponent]
final class ManageArticleSections extends ManageRelatedRecords
{
    protected static string $resource = ArticleResource::class;

    protected static string $relationship = 'sections';

    protected static ?string $relatedResource = SectionResource::class;
}
```

registered as `'sections' => ManageArticleSections::route('/{record}/sections')` — and that key is a contract, not a convention: [`Resource::getIndexUrl()`](nested-resources.md#the-parent-arms-carry-no-relation-parameter) looks the page up on the parent by exactly `kebab($relationship)`, so a page registered under any other key is never the listing its related resource answers with.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The class](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) — a resource page composing `HasTable`, `InteractsWithRecord`, `InteractsWithRelationshipTable` | the same composition, plus `HasRelationManagers` | done | — | [diverges](#the-class-is-abstract-and-hosts-no-relation-managers-of-its-own) |
| [`mount()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | `mount(int \| string $record)` | done | — | [diverges](nested-resources.md#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount) |
| [`getOwnerRecord()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | — |
| [`getTitle()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | [diverges](#the-title-is-a-literal-not-a-translation-key) |
| [`getBreadcrumb()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | — |
| [`getNavigationLabel()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | [diverges](#the-navigation-label-and-icon-drop-their-related-resource-arms) |
| [`getNavigationIcon()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) + [the alias](../../../packages/panels/src/Twig/PanelsIconAlias.php) | same | done | — | [diverges](#the-navigation-label-and-icon-drop-their-related-resource-arms) |
| [`getDefaultActionRecord()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | [semantics](#the-owner-is-the-page-record-so-the-hooks-split-three-ways) |
| [`getDefaultActionRecordClass()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) / [`getDefaultActionRecordLabel()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | `getDefaultActionModel()` / `getDefaultActionModelLabel()` | done | — | [renamed](../renames.md#renames) · [semantics](#the-owner-is-the-page-record-so-the-hooks-split-three-ways) |
| [`getDefaultActionRecordTitle()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | [semantics](#the-owner-is-the-page-record-so-the-hooks-split-three-ways) |
| [`getDefaultActionRelationship()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | — |
| [`getDefaultActionSchemaResolver()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | same | done | — | [semantics](#the-schema-resolver-is-the-hosts-not-the-tiers) |
| [`getDefaultActionUrl()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | the copy on this class | done | — | [diverges](interacts-with-relationship-table.md#the-url-arms-are-hoisted-once-and-carry-the-owner-explicitly) |
| `schemas()`, inherited from [`Pages\Page`](../../../packages/panels/src/Pages/Page.php) | no counterpart — Livewire has no schema-discovery list | done | — | [semantics](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf) |
| [`$template`](../../../packages/panels/templates/partials/Page/table.html.twig) | `content()` — a `Group` of tabs, render hooks and an `EmbeddedTable` | done | — | [diverges](#the-page-renders-a-template-not-a-content-schema) |
| The sub-navigation band on the partial | the band block in `page/index.blade.php` | done | — | [has-sub-navigation.md](has-sub-navigation.md) |
| `getDefaultActionSuccessRedirectUrl()`, inherited from `HasActions` | the table-context narrowing on this class | done | — | [semantics](#filaments-redirect-narrowing-answers-null-for-every-action-as-the-inherited-hook-does) |
| `getPageClass()` | same | skipped | — | [diverges](#getpageclass-has-no-reader-here) |
| [`getPageClasses()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) — the page, resource-slug and record-key root classes | same | done | — | — |
| `previousUrl` | `public ?string $previousUrl` | skipped | — | [diverges](#previousurl-is-not-ported-anywhere-in-the-port) |
| `getRelationManagers()` + `HasRelationManagers` on this page | same | skipped | — | [diverges](#the-class-is-abstract-and-hosts-no-relation-managers-of-its-own) |
| The record-filter tabs (`$activeTab`, the tab strip) | `HasTabs`, through the tier | done | — | [has-tabs.md](has-tabs.md) · [interacts-with-relationship-table.md](interacts-with-relationship-table.md) |
| [`getTableUrlMapping()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) — `page` (identifier-prefixed), `sort`, `search`, `filters`, `grouping`, `tab` | the `#[Url(as: …)]` block redeclared on the page | done | — | [has-table.md](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) · [identifier](interacts-with-relationship-table.md#the-table-side-querystringidentifier-is-deliberately-not-stamped) |
| The `reordering` URL bind | `#[Url(as: 'reordering')]` | skipped | — | [diverges](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) |
| The `grouping` URL bind | `#[Url(as: 'grouping')]` | done | — | [grouping.md](../tables/grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group) |
| `canAccess()` / `authorizeAccess()` / `hydrate()` and the authorization response hooks | the same cluster | blocked | authorization | [detail](#the-page-is-reachable-by-anyone-who-can-reach-its-url) |
| `getWidgetData()` and the widget half | same | blocked | `sylament/widgets` | — |

## Details

### The page is reachable by anyone who can reach its URL

`canAccess()`, `authorizeAccess()` and the `hydrate()` re-check wait on authorization, so **a `GET` of the page's URL renders it, and every action on it runs**, gated by nothing but the record scoping.
Filament's own `canAccess()` is the most interesting of the deferrals to write later: it forwards to the *related resource's* `canAccess()` when there is one, and otherwise asks the `viewAny` policy of the class the relationship targets.
The parking is the same one every host in this port carries; it is listed here because a page has a URL, which a relation manager does not.

## Semantics

### The owner is the page record, so the hooks split three ways

Four `getDefaultAction*` hooks discriminate, and all four use Filament's own split: a `CreateAction` is writing a **listed** record, a **table-context** frame already knows its record from the table, and anything else is a frame about the **owner**.

- `getDefaultActionRecord()` — null for the first two, the page's record otherwise.
- `getDefaultActionRecordClass()` / `getDefaultActionRecordLabel()` — the relationship table's class and label for a `CreateAction` (so a header create reads *"New task"*, not *"New mrr project"*), else the resource page base's answer, which is the owner resource's.
- `getDefaultActionRecordTitle()` — null for the first two, the owner's title otherwise, so a modal heading on a page-level action names the owner.

Reaching them at all takes a page-level action whose related resource registers no page for it: a table-mounted action short-circuits every one of them (the table answers the class, the label and the record before any host hook is consulted), and a related resource with a `create` page turns the trigger into a link before a modal exists.

### The schema resolver is the host's, not the tier's

`getDefaultActionSchemaResolver()` is declared on this class rather than hoisted onto the relationship-table trait, matching Filament: Create and Edit resolve `$this->form(...)`, View resolves `$this->infolist($this->form($schema))`, and `RelationManager` keeps its own.
The resolver is the **host's** action contract — each host decides which of its schemas an action opens — where the URL arms it sits beside are pure related-resource logic and therefore hoisted.
The two resolvers are not identical either: the manager's view arm shares the form builder, because [`ViewAction` disables its own schema](relation-manager.md#the-view-arm-shares-the-form-builder-because-viewaction-disables-its-own-schema) and no infolist tier exists yet.

### Filament's redirect narrowing answers null for every action, as the inherited hook does

Filament's `ManageRelatedRecords::getDefaultActionSuccessRedirectUrl()` returns null for a table-context action and `parent::getDefaultActionSuccessRedirectUrl()` otherwise.
The class method shadows the `DeleteAction` arm of the `InteractsWithRecord` trait it composes, and `parent::` resolves through `Resources\Pages\Page` and `BasePage` to `InteractsWithActions`, which answers null, so a header `DeleteAction` on Filament's page does not redirect either.
The port declares no override: the page inherits `HasActions::getDefaultActionSuccessRedirectUrl()`, which answers null for every action, and only [`EditRecord`](edit-record.md) overrides it, for a `DeleteAction`.

## Divergences

### The class is `abstract`, and hosts no relation managers of its own

Filament's `ManageRelatedRecords` is a concrete `class` that cannot usefully be instantiated as one: the `protected static string $relationship` it composes is an **uninitialized** typed static, so the first read of it on the base class itself is a PHP `Error`.
Ours is `abstract`, which is what every other page base here is and what the class already means.
A class modifier, not a rename: a subclass sees no difference.

Filament also composes `HasRelationManagers` on it and then overrides `getRelationManagers()` to `[]`, wiring the whole tab machinery inert.
We skip the wiring whole — a page listing one association does not also host a tab bar of other associations, and nothing would fill it.
Record-filter tabs come through [the shared tier](interacts-with-relationship-table.md) instead ([has-tabs.md](has-tabs.md)), so this page offers record filters over its own association without the manager wiring.

### The page renders a template, not a content schema

Filament's `content()` returns a `Group` of the record-filter tabs, two render hooks and an `EmbeddedTable`, sibling to the relation-manager component.
All four are deferred or placed elsewhere — `EmbeddedTable` and render hooks are unported, the managers are skipped above, and the tabs are a [schema the partial renders](has-tabs.md#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds) rather than a component a `content()` would hold — which would leave a content schema wrapping one table and nothing else.
So the page renders a template instead — the shared table variant [`partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig), which `{% extends %}` the base page chrome and fills its `content` block with the tabs strip and `{{ renderTable() }}`.
The page root, the header with its breadcrumbs and header actions, the sub-navigation band and `<twig:SylamentActions:Modals />` all come from the chrome, and the outlet is [not optional](relation-manager.md#the-manager-renders-a-template-not-a-content-schema) — without it every action the page mounts renders nothing at all.
It is the same call [`ListRecords`](list-records.md) makes, and it is literally the same **file**: the list, create and manage-related shapes collapse onto two variants of one chrome, and the per-page root class comes from [`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class).

### The navigation label and icon drop their related-resource arms

Filament's `getNavigationLabel()` is `static::$navigationLabel ?? static::getRelationshipTitle()`, and its `getNavigationIcon()` inserts `$relatedResource::getNavigationIcon()` between the static and the icon alias.
Both arms are instance-bound here — the related resource is a service reached through the registry, and both methods are static because the band's item factory reads them off the class — so ours answer `static::$navigationLabel ?? getHumanizedRelationshipName()` and `static::$navigationIcon ?? the alias ?? heroicons:rectangle-stack`.

The visible consequence is narrow and worth stating: a page declaring `$relationshipTitle = 'Checklist'` and no `$navigationLabel` shows **Tasks** in the band and **Checklist** in the breadcrumb, and a related resource's own navigation icon is never inherited.
Declaring `$navigationLabel` / `$navigationIcon` on the page is the whole of the workaround.
Both close with [the static title chain](interacts-with-relationship-table.md#the-title-is-an-instance-method-and-the-static-title-chain-never-reaches-the-related-resource), which is one decision, not three.

The icon alias itself is `PanelsIconAlias::RESOURCES_PAGES_MANAGE_RELATED_RECORDS_NAVIGATION_ITEM` = `resources.pages.manage-related-records.navigation-item` — Filament's constant name with [the `panels::` prefix dropped](../renames.md#renames), like every other alias here — falling back to `heroicons:rectangle-stack`, the outline glyph Filament's `Heroicon::OutlinedRectangleStack` names.

### The title is a literal, not a translation key

`getTitle()` is `static::$title ?? sprintf('Manage %s %s', $recordTitle, $relationshipTitle)` — *Manage Apollo Tasks* — where Filament reads `filament-panels::resources/pages/manage-related-records.title` with the same two placeholders.
The same call [the create and save notification titles](create-record.md#the-create-and-save-notification-titles-are-literals-not-translation-keys) and `EditRecord::getTitle()`'s `'Edit %s'` made: i18n is parked, and the English literal is what the key resolves to.

### `getPageClass()` has no reader here

Filament's `getPageClass(): string` answers `static::class`, and exists so a relation manager embedded on the page can be told which page it hangs under.
This page embeds none ([above](#the-class-is-abstract-and-hosts-no-relation-managers-of-its-own)), and the [relation manager](relation-manager.md) that does read a page class is handed it as a mount prop by the page that embeds it.
A method nothing calls is not ported.

### `previousUrl` is not ported anywhere in the port

Filament's page fills `public ?string $previousUrl = null` from `url()->previous()` in `mount()`, feeding a cancel-via-history action.
Nothing reads it on this page — its own cancel action is not ported either — so it is dead state on the wire.
The record pages record the same deferral ([`CreateRecord`](create-record.md)); it is repeated here because the property is the one member of Filament's class that has no consumer even in Filament's own file.
