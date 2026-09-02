# ManageRelatedRecords (panels)

[`Sylament\Panels\Resources\Pages\ManageRelatedRecords`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php)
— one owner record's association as a **page** of its own: the routed sibling of
a [relation manager](relation-manager.md), reached through the record's
[sub-navigation](has-sub-navigation.md), and the listing a
[nested resource](nested-resources.md)'s records are reached through. It ports
[`Resources/Pages/ManageRelatedRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php).

It is a resource page that is also a relationship-table host: it extends
[`Resources\Pages\Page`](README.md#resource-pages) — so it carries the actions
host, the breadcrumb chain and the parent-record chain — and composes
[`InteractsWithRecord`](interacts-with-record.md) for the record it is opened on
and [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md) for
the association it lists, with tables' `HasTable` and schemas' `HasSchemas`
beside them. Its record **is** the relationship's owner, which is the whole
identity of the class: `getOwnerRecord()` is `getRecord()`.

A page is three statics and nothing else — for an `Article` whose `sections`
also have a resource of their own:

```php
#[AsLiveComponent]
final class ManageArticleSections extends ManageRelatedRecords
{
    protected static string $resource = ArticleResource::class;

    protected static string $relationship = 'sections';

    protected static ?string $relatedResource = SectionResource::class;
}
```

registered as `'sections' => ManageArticleSections::route('/{record}/sections')`
— and that key is a contract, not a convention:
[`Resource::getIndexUrl()`](nested-resources.md#the-parent-arms-carry-no-relation-parameter)
looks the page up on the parent by exactly `kebab($relationship)`, so a page
registered under any other key is never the listing its related resource answers
with.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Resources/Pages/ManageRelatedRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php)
  — the whole class
- [`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php)
  — the tier it composes, [ported here](interacts-with-relationship-table.md)
- [`Resources/Pages/ListRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php)
  — the page shape its template follows

## Surface

Every point of this page. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| The class — a resource page composing `HasSchemas`, `HasTable`, `InteractsWithRecord`, `InteractsWithRelationshipTable` | the same composition, plus `HasRelationManagers` | done | — | [diverges](#the-class-is-abstract-and-hosts-no-relation-managers-of-its-own) |
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
| [`schemas()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | no counterpart — Livewire has no schema-discovery list | done | — | — |
| [`$template`](../../../packages/panels/templates/partials/ManageRelatedRecords/index.html.twig) | `content()` — a `Group` of tabs, render hooks and an `EmbeddedTable` | done | — | [diverges](#the-page-renders-a-template-not-a-content-schema) |
| The sub-navigation band on the partial | the band block in `page/index.blade.php` | done | — | [has-sub-navigation.md](has-sub-navigation.md) |
| `getDefaultActionSuccessRedirectUrl()`'s table-context narrowing | same | todo | — | [detail](#the-table-context-redirect-narrowing-is-not-ported) |
| `getPageClass()` | same | skipped | — | [diverges](#getpageclass-has-no-reader-here) |
| `getPageClasses()` — the record-keyed root classes | same | skipped | — | [diverges](#the-root-carries-one-page-class-not-three) |
| `previousUrl` | `public ?string $previousUrl` | skipped | — | [diverges](#previousurl-is-not-ported-anywhere-in-the-port) |
| `getRelationManagers()` + `HasRelationManagers` on this page | same | skipped | — | [diverges](#the-class-is-abstract-and-hosts-no-relation-managers-of-its-own) |
| The record-filter tabs (`$activeTab`, the tab strip) | `HasTabs`, through the tier | done | — | [has-tabs.md](has-tabs.md) — composed through [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md), so this page and a relation manager offer the same filters; the strip is built at render time rather than declared in a `content()` schema |
| [`getTableUrlMapping()`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) — `page` (identifier-prefixed), `sort`, `search`, `filters`, `tab` | the `#[Url(as: …)]` block redeclared on the page | done | — | [has-table.md](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) · the prefix comes from [the host hook](interacts-with-relationship-table.md#the-table-side-querystringidentifier-is-deliberately-not-stamped), never from the `Table` setter |
| The `reordering` URL bind | `#[Url(as: 'reordering')]` | skipped | — | Reordering shipped with M4 · 3 and the bind [deliberately did not](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) — a transient UI mode rather than browsing state, and binding it would leave a permanent `reordering=0` in every URL |
| The `grouping` URL bind | `#[Url(as: 'grouping')]` | todo | grouping (M4 · 6) | There is no `$tableGrouping` prop to attach a mapping to until grouping lands; the [hook it would go through](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) already exists |
| `canAccess()` / `authorizeAccess()` / `hydrate()` and the authorization response hooks | the same cluster | todo | the authorization epic | [detail](#the-page-is-reachable-by-anyone-who-can-reach-its-url) |
| `getWidgetData()` and the widget half | same | todo | `sylament/widgets` | — |

[`Integration/Resources/Pages/ManageRelatedRecordsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ManageRelatedRecordsTest.php)
mounts real pages over a fixture `Project`/`Task` pair on a panel of their own:
the page rendering with the **related resource's** table (its columns, its record
actions, only the owner's rows), the trail ending in the relationship title, the
header create linking at the related create page and the rows linking at its edit
page **under the same owner**, a delete through a table-context action, the two
security pins (a table action naming another owner's record never mounts, and the
scoped `getTableRecord()` underneath it), the record band on both sibling pages
with the right item active on each, and both `getIndexUrl()` arms — the manage
page answering when the key matches the relationship, and the parent's edit page
answering when it does not.
A second fixture page, whose related resource registers **no create page**, is
what reaches the five host hooks at all: the header create mounts a modal
labelled from the relationship table, creating through it joins the owner, the
frame titles resolve three ways, and a table frame carries no owner record. A
third, declaring no `$relatedResource`, pins that the modal opens on the page's
own schema rather than erroring.
The tier's own coverage is
[interacts-with-relationship-table.md](interacts-with-relationship-table.md).

## Details

### The page is reachable by anyone who can reach its URL

`canAccess()`, `authorizeAccess()` and the `hydrate()` re-check are not ported,
with the rest of the authorization epic — so **a `GET` of the page's URL renders
it, and every action on it runs**, gated by nothing but the record scoping.
Filament's own `canAccess()` is the most interesting of the deferrals to write
later: it forwards to the *related resource's* `canAccess()` when there is one,
and otherwise asks the `viewAny` policy of the class the relationship targets.
The parking is the same one every host in this port carries; it is listed here
because a page has a URL, which a relation manager does not.

### The table-context redirect narrowing is not ported

Filament's `getDefaultActionSuccessRedirectUrl()` narrows the record page's:
a **table-context** action returns null (a row delete must not redirect to the
resource index), everything else falls through to `parent::`. Ours inherits the
resource-page base's hook unchanged, and that base answers null for everything
except a `DeleteAction` on [`EditRecord`](README.md#editrecord) — a class this
page is not — so the observable behaviour today is identical: nothing this page
mounts redirects. The override lands when the base's hook grows an arm this page
would have to narrow; recording it keeps the divergence from being discovered as
a bug later.

## Semantics

### The owner is the page record, so the hooks split three ways

Four `getDefaultAction*` hooks discriminate, and all four use Filament's own
split: a `CreateAction` is writing a **listed** record, a **table-context** frame
already knows its record from the table, and anything else is a frame about the
**owner**.

- `getDefaultActionRecord()` — null for the first two, the page's record
  otherwise.
- `getDefaultActionRecordClass()` / `getDefaultActionRecordLabel()` — the
  relationship table's class and label for a `CreateAction` (so a header create
  reads *"New task"*, not *"New mrr project"*), else the resource page base's
  answer, which is the owner resource's.
- `getDefaultActionRecordTitle()` — null for the first two, the owner's title
  otherwise, so a modal heading on a page-level action names the owner.

Reaching them at all takes a page-level action whose related resource registers
no page for it: a table-mounted action short-circuits every one of them (the
table answers the class, the label and the record before any host hook is
consulted), and a related resource with a `create` page turns the trigger into a
link before a modal exists. That is why the fixture set carries a second manage
page built precisely that way.

### The schema resolver is the host's, not the tier's

`getDefaultActionSchemaResolver()` is declared on this class rather than hoisted
onto the relationship-table trait, matching Filament: Create and Edit resolve
`$this->form(...)`, View resolves `$this->infolist($this->form($schema))`, and
`RelationManager` keeps its own. The resolver is the **host's** action contract —
each host decides which of its schemas an action opens — where the URL arms it
sits beside are pure related-resource logic and therefore hoisted. The two
resolvers are not identical either: the manager's view arm shares the form
builder, because [`ViewAction` disables its own schema](relation-manager.md#the-view-arm-shares-the-form-builder-because-viewaction-disables-its-own-schema)
and no infolist tier exists yet.

## Divergences

### The class is `abstract`, and hosts no relation managers of its own

Filament's `ManageRelatedRecords` is a concrete `class` that cannot usefully be
instantiated as one: the `protected static string $relationship` it composes is
an **uninitialized** typed static, so the first read of it on the base class
itself is a PHP `Error`. Ours is `abstract`, which is what every other page base
here is and what the class already means. A class modifier, not a rename: a
subclass sees no difference.

Filament also composes `HasRelationManagers` on it and then overrides
`getRelationManagers()` to `[]`, wiring the whole tab machinery inert. We skip
the wiring whole — a page listing one association does not also host a tab bar of
other associations, and nothing would fill it. The demo carries `sections` on
both surfaces on purpose — `SectionsRelationManager` as a tab on the article edit
page ([M6 · 1](relation-manager.md), the modal flow) beside `ManageArticleSections`
as a routed page (this epic, the link flow) — so the one association shows what
each flow looks like rather than either standing in for the other. Record-filter
tabs are the exception that did arrive: `HasTabs` landed with
[M4 · 3](../roadmap.md#m4--tables-widening) on
[the shared tier](interacts-with-relationship-table.md) rather than on
`ListRecords` alone, so this page offers record filters over its own association
without the manager wiring above coming with them — [has-tabs.md](has-tabs.md).

### The page renders a template, not a content schema

Filament's `content()` returns a `Group` of the record-filter tabs, two render
hooks and an `EmbeddedTable`, sibling to the relation-manager component. All four
are deferred or placed elsewhere — `EmbeddedTable` and render hooks are unported,
the managers are skipped above, and the tabs landed with M4 · 3 as a
[schema the partial renders](has-tabs.md#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds)
rather than a component a `content()` would hold — which would leave a content
schema wrapping one table and nothing else. So the page renders
[`partials/ManageRelatedRecords/index.html.twig`](../../../packages/panels/templates/partials/ManageRelatedRecords/index.html.twig):
the `ListRecords` skeleton (the page root, the header with its breadcrumbs and
header actions, `{{ renderTable() }}` inside `sy-pa-page-content`, the
sub-navigation band around it) plus `<twig:SylamentActions:Modals />`, which is
[not optional](relation-manager.md#the-manager-renders-a-template-not-a-content-schema)
— without it every action the page mounts renders nothing at all. It is the same
call [`ListRecords`](README.md#listrecords) already made, one page over.

### The navigation label and icon drop their related-resource arms

Filament's `getNavigationLabel()` is `static::$navigationLabel ??
static::getRelationshipTitle()`, and its `getNavigationIcon()` inserts
`$relatedResource::getNavigationIcon()` between the static and the icon alias.
Both arms are instance-bound here — the related resource is a service reached
through the registry, and both methods are static because the band's item factory
reads them off the class — so ours answer `static::$navigationLabel ??
getHumanizedRelationshipName()` and `static::$navigationIcon ?? the alias ??
heroicons:rectangle-stack`.

The visible consequence is narrow and worth stating: a page declaring
`$relationshipTitle = 'Checklist'` and no `$navigationLabel` shows **Tasks** in
the band and **Checklist** in the breadcrumb, and a related resource's own
navigation icon is never inherited. Declaring `$navigationLabel` /
`$navigationIcon` on the page is the whole of the workaround. Both close with
[the static title chain](interacts-with-relationship-table.md#the-title-is-an-instance-method-and-the-static-title-chain-never-reaches-the-related-resource),
which is one decision, not three.

The icon alias itself is
`PanelsIconAlias::RESOURCES_PAGES_MANAGE_RELATED_RECORDS_NAVIGATION_ITEM` =
`resources.pages.manage-related-records.navigation-item` — Filament's constant
name with [the `panels::` prefix dropped](../renames.md#renames), like every other
alias here — falling back to `heroicons:rectangle-stack`, the outline glyph
Filament's `Heroicon::OutlinedRectangleStack` names.

### The title is a literal, not a translation key

`getTitle()` is `static::$title ?? sprintf('Manage %s %s', $recordTitle,
$relationshipTitle)` — *Manage Apollo Tasks* — where Filament reads
`filament-panels::resources/pages/manage-related-records.title` with the same two
placeholders. The same call
[the create and save notification titles](README.md#the-create-and-save-notification-titles-are-literals-not-translation-keys)
and `EditRecord::getTitle()`'s `'Edit %s'` made: i18n is parked, and the English
literal is what the key resolves to.

### `getPageClass()` has no reader here

Filament's `getPageClass(): string` answers `static::class`, and exists so a
relation manager embedded on the page can be told which page it hangs under.
This page embeds none ([above](#the-class-is-abstract-and-hosts-no-relation-managers-of-its-own)),
and the [relation manager](relation-manager.md) that does read a page class is
handed it as a mount prop by the page that embeds it. A method nothing calls is
not ported.

### The root carries one page class, not three

Filament's `getPageClasses()` returns
`['fi-resource-manage-related-records-page', 'fi-resource-<slug>',
'fi-resource-record-<key>']`. The template writes
`sy-pa-resource-manage-related-records-page` alone, which is the same call the
[list page](README.md#listrecords) made: the slug-derived and record-derived
halves wait for a reason to style one resource's — or one record's — page
differently from another's. Nothing in the stylesheet selects any of the three.

### `previousUrl` is not ported anywhere in the port

Filament's page fills `public ?string $previousUrl = null` from
`url()->previous()` in `mount()`, feeding a cancel-via-history action. Nothing
reads it on this page — its own cancel action is not ported either — so it is
dead state on the wire. The record pages already record the same deferral
([`CreateRecord`](README.md#createrecord)); it is repeated here because the
property is the one member of Filament's class that has no consumer even in
Filament's own file.
