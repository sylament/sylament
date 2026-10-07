---
description: The relation manager, one owner record's association as an embedded live component with its scoped table, its actions and their modals.
ports:
  - vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php
  - vendor/filament/filament/src/Resources/RelationManagers/Concerns/CanAuthorizeAccess.php
owns:
  - packages/panels/assets/src/styles/components/relation-manager.css
  - packages/panels/src/DependencyInjection/Compiler/RelationManagerComponentPass.php
  - packages/panels/src/Resources/RelationManagers/RelationManager.php
  - packages/panels/templates/partials/RelationManager/
---
# RelationManager (panels)

[`Sylament\Panels\Resources\RelationManagers\RelationManager`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) is one owner record's association as a live component of its own: a table scoped to that association, the actions that write it, and the modals those actions open.
The members it shares with [`ManageRelatedRecords`](manage-related-records.md) sit on [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md), which both compose.
A manager is never routed to: the page that owns the record embeds it through schemas' [`LiveComponent`](../schemas/live-component.md) embed, which is what keeps its sort, page and selection across the page's own re-renders.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| The host union — `InteractsWithActions` + `InteractsWithSchemas` + `InteractsWithTable`, `DefaultActionTrait` | `Component implements HasActions, HasSchemas, HasTable` | done | — | [diverges](#the-manager-stands-beside-the-resource-page-base-rather-than-under-it) |
| [`$ownerRecord` / `getOwnerRecord()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `#[Locked] public Model $ownerRecord` | done | — | [diverges](#the-owner-rides-the-wire-as-a-route-key-behind-a-dehydration-bridge) |
| [`$pageClass` / `getPageClass()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `#[Locked] public ?string $pageClass` | done | — | [diverges](#the-owner-rides-the-wire-as-a-route-key-behind-a-dehydration-bridge) |
| [`getTitle()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) + `$title` | `getTitle()` | done | — | [semantics](#the-title-fallback-humanizes-the-relationship-name-without-pluralizing-it) · [diverges](interacts-with-relationship-table.md#the-title-is-an-instance-method-and-the-static-title-chain-never-reaches-the-related-resource) |
| [`getIcon()` / `getIconPosition()` / `getBadge()` / `getBadgeColor()` / `getBadgeTooltip()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) + their statics | the same five and their statics | done | — | — |
| [`getTabComponent()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `getTabComponent()` | done | — | [diverges](#deferred-tab-badges-do-not-port) |
| [`make()` → `RelationManagerConfiguration`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `make()` | done | — | [relation-group.md](relation-group.md) |
| [`getDefaultProperties()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `getDefaultProperties()` | done | — | [diverges](#lazy-loading-does-not-port-so-the-default-properties-are-empty) |
| [`getComponentName()` / `getTemplate()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `$view` + Livewire's component-name registration | done | — | [diverges](#the-manager-stands-beside-the-resource-page-base-rather-than-under-it) |
| [`DependencyInjection\Compiler\RelationManagerComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/RelationManagerComponentPass.php) | [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php)'s Livewire registration | done | — | [diverges](#a-manager-must-carry-aslivecomponent-and-the-pass-enforces-it) |
| [`makeTable()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) — the heading over the aliased `makeBaseRelationshipTable()` | `makeTable()` | done | — | [semantics](#the-relationship-is-seeded-in-maketable-and-table-is-never-redeclared) · [diverges](#the-deprecated-label-statics-do-not-port) |
| [`getDefaultActionSchemaResolver()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | the same | done | — | [semantics](#the-view-arm-shares-the-form-builder-because-viewaction-disables-its-own-schema) · [diverges](#the-defaultform-two-column-wrapper-does-not-port) · [manage-related-records.md](manage-related-records.md#the-schema-resolver-is-the-hosts-not-the-tiers) |
| [`getDefaultActionRelationship()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | [`Concerns/InteractsWithActions::getDefaultActionRelationship()`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php), inherited as `null` | done | — | [diverges](#the-manager-answers-the-hosts-relationship-hook-where-filaments-leaves-it-null) |
| [`schemas()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | no counterpart — Livewire has no schema-discovery list | done | — | — |
| `mount()` — `loadDefaultActiveTab()` | `mount()` | done | — | [diverges](#the-default-active-tab-loads-at-post-mount-and-the-manager-keeps-no-mount-of-its-own) |
| The panel id a manager's own live request resolves URLs against | `original_request()` inside Filament's URL arms | todo | — | [detail](#every-url-a-manager-generates-on-its-own-live-request-resolves-the-default-panel) |
| [`canViewForRecord()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `canViewForRecord()` | in progress | — | [detail](#every-registered-manager-is-reachable-by-anyone-until-authorization-lands) |
| `isReadOnly()` / `canReorder()`'s read-only arm | `isReadOnly()`, `canReorder()` | blocked | authorization | [detail](#every-registered-manager-is-reachable-by-anyone-until-authorization-lands) |
| `getDefaultActionAuthorizationResponse()` / `getDefaultActionIndividualRecordAuthorizationResponseResolver()` / `CanAuthorizeAccess::hydrateCanAuthorizeAccess()` | [`RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php), [`Concerns/CanAuthorizeAccess.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/Concerns/CanAuthorizeAccess.php) | blocked | authorization | [detail](#every-registered-manager-is-reachable-by-anyone-until-authorization-lands) |
| `content()` — the manager's own tabs + `EmbeddedTable` | `content()` | blocked | `EmbeddedTable` | [diverges](#the-manager-renders-a-template-not-a-content-schema) |
| `getRenderHookScopes()` / `HasRenderHookScopes`, `render()` / `getViewData()` | [`RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php) | blocked | render hooks | — |
| `$isBadgeDeferred` / `isBadgeDeferred()` | [`RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php) | skipped | — | [diverges](#deferred-tab-badges-do-not-port) |
| `CanBeLazy` — `lazy()` / `isLazy()` | [`support/src/Concerns/CanBeLazy.php`](../../../vendor/filament/support/src/Concerns/CanBeLazy.php) | skipped | — | [diverges](#lazy-loading-does-not-port-so-the-default-properties-are-empty) |
| The `@deprecated` label statics and getters, `$inverseRelationship` / `getInverseRelationshipName()`, and their `makeTable()` `when()` arms | [`RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php) | skipped | — | [diverges](#the-deprecated-label-statics-do-not-port) |

## Details

### Every registered manager is reachable by anyone until authorization lands

`canViewForRecord()` answers `true` unconditionally and nothing else in the authorization surface is ported; the method exists, and Filament's `skipAuthorization()`, related-resource and `viewAny` policy arms are what is missing.
Stated plainly: **a registered relation manager is a POST-reachable live component that mutates another record's associations, gated by nothing.**
That is the same exposure the resource pages carry, doubled: a manager reaches records the page itself never names.
`isReadOnly()` (and the `canReorder()` arm that consults it) parks with the rest: it exists in Filament to make managers read-only on view pages, which is a panel-level authorization default, not a separate feature.

### Every URL a manager generates on its own live request resolves the default panel

A manager has no GET route of its own, so it carries the toolkit's highest exposure to [URL generation inside a live action resolving the default panel](resource.md#url-generation-inside-a-live-action-resolves-the-panel-the-page-was-opened-in).
A manager rendered **inside** a request that carries the panel (the page's GET, or a live request that hydrates its host page, whose [panel stamp](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism) writes `_sylament_panel` back onto the request) reads the right panel off that request attribute like anything else in the request.
A manager's own live request (a sort, a page change, an action) hydrates the manager alone, stamps nothing, and `CurrentPanel::get()` answers null, so `PanelRegistry::getDefault()` answers instead.
That is right for a single-panel application and for any resource mounted in the default panel, and wrong for a manager whose resource lives **only** in a non-default one: a manager declaring `$relatedResource` there mints, through `getDefaultActionUrl()`, a route name that exists only under the other panel, and `UrlGenerator` throws `RouteNotFoundException` on that request; a manager that declares no `$relatedResource` mints none and is unaffected.
What is missing is the page's own fix on the manager: carry the panel id in a `LiveProp` and stamp it back onto the request at post-hydrate.
No test drives a manager on a non-default panel.

## Semantics

### The title fallback humanizes the relationship name without pluralizing it

`getTitle()` answers `$title`, else the relationship name kebab-cased, dashes spaced, then title-cased word by word (`blogPosts` → `Blog Posts`), the same steps as Filament's [`InteractsWithRelationshipTable::getRelationshipTitle()`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) last arm.
There is **no** pluralization step: the association name is already plural in every idiomatic mapping, so a manager over `tasks` is titled `Tasks` because that is what the property is called, not because anything inflected it.
A singular association name titles singular, and that is Filament's behaviour too.
Filament's `getTitle()` reaches that fallback through `getRelationshipTitle()`, so a `$relationshipTitle` and a related resource's plural label come first there; ours skips both ([why the related arm](interacts-with-relationship-table.md#the-title-is-an-instance-method-and-the-static-title-chain-never-reaches-the-related-resource)).

The idiom is the one [`Pages\Page::getHumanizedClassBasename()`](page.md) uses on a class basename, duplicated rather than shared: the manager does not extend that class, and the two humanize different things.

### The relationship is seeded in `makeTable()` and `table()` is never redeclared

`HasTable::getTable()` is `$this->table($this->makeTable())`: the subclass hook is applied by the *caller*, once.
So the relationship seed goes in `makeTable()`, which is what the base overrides; redeclaring `table()` on the base to wrap `makeTable()` would run a subclass's hook twice and duplicate every query scope, column and action it adds.
Subclasses override `table()` exactly as on every other table host, and the Unit suite pins the single application with a counting fixture.

### The view arm shares the form builder because `ViewAction` disables its own schema

`getDefaultActionSchemaResolver()` answers `form(...)` for `CreateAction`, `EditAction` **and** `ViewAction`.
Filament's view arm wraps the form in `defaultInfolist()`/`infolist()`; ours has no infolist tier to wrap it in (infolists is a placeholder package), and the shipped [`ViewAction`](../actions/view-action.md) calls `disabledSchema()` in its `setUp()`, so the read-only rendering is the action's doing rather than the resolver's.
When infolists land, the arm splits.

### A delete on a manager redirects nowhere

`HasActions::getDefaultActionSuccessRedirectUrl()` answers `null`, and the delete-to-index redirect is `EditRecord`'s own override of it, which the manager never inherits, so there is no override to write.
A deleted row just leaves the manager's table.
The Integration suite pins it, because the obvious reading (a `DeleteAction` on a resource-shaped host sends you to the index) is what the edit page does and what a reader would expect here.

### There is no refresh-after-save eventing, and that is parity

Filament defines no relation-manager listener, no emitted event and no parent-saved refresh signal: a preserved child does not re-render on the parent page's save, and this port matches it.
A consumer who wants one wires it themselves with `emitUp()` + a `#[LiveListener]` on the manager, never a bare `emit()`, which is a page-wide broadcast costing one HTTP request per receiving listener.

## Divergences

### The manager stands beside the resource page base rather than under it

Filament's `RelationManager` extends `Livewire\Component`; ours implements the three host contracts directly and composes `DefaultActionTrait` + `HasActions` + `HasSchemas` + `HasTable` beside the shared trait.
It deliberately does **not** extend [`Resources\Pages\Page`](resource-pages.md): that base answers route and navigation statics for a route a manager does not have.
The cost is a few duplicated members (`getComponentName()`, `getTemplate()`, the registry setter), and it is the same trade Filament makes.

### The owner rides the wire as a route key behind a dehydration bridge

Filament's `ownerRecord` is a `#[Locked] Model`: Livewire serialises an Eloquent model by class and key.
UX LiveComponent cannot: a generic base cannot declare the concrete entity type, and a `?object` prop hands the hydrator the useless class-string `object`.
So the prop is the owner's **route key** (`?string`) plus a `?string $pageClass`, and `getOwnerRecord()` resolves it through `pageClass::getResource()` → the resource registry → `resolveRecordRouteBinding()`, memoised for the request, `NotFoundHttpException` on a miss.
The key is the resource's own route key at any identifier width, so the prop, its checksum and the bridge carry `1~A=2FB=20C` exactly as they carry `7`; and the query underneath carries such an owner too ([how the scope reaches an owner of any width](../schemas/relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
`is_subclass_of($pageClass, Resources\Pages\Page::class)` gates the client-supplied class-string against the **resource**-page base, so the shell `Pages\Page` (and `Dashboard` with it) cannot get past it.

Both props carry `dehydrateWith:`, and that is mandatory rather than defensive.
`LiveComponentHydrator::dehydrate()` reads every prop through the `PropertyAccessor`, which prefers a same-named getter, so `getOwnerRecord()` would hand the resolved **entity** to a `?string` wire prop and every render would throw.
The bridges return the raw props.
This is the [`EditRecord::dehydrateRecord()`](edit-record.md) pattern, and the same trap [the PHP notes](../../internals/php.md) record for writable props.

`#[Locked]` needs no port: a non-writable `LiveProp` is checksummed by default.

That resolution is a **bare** `resolveRecordRouteBinding($key)`, with no parent scope where a nested resource page passes one ([why every key on such a page is scoped](nested-resources.md#one-scope-serves-the-route-key-and-the-unchecksummed-action-key)).
It is safe as it stands because the key is a checksummed `LiveProp` the host page stamped from its own already-scoped record, so a tampered value fails the checksum before any query runs.
It stops being safe the day a manager is mounted with a key from anywhere else (a `#[LiveArg]`, a URL), which is the thing to check before widening how a manager is mounted.
It is also Filament's own shape: `#[Locked] Model $ownerRecord` carries no scope either, and closing it here would mean a second wire prop holding the parent chain.

### Deferred tab badges do not port

`$isBadgeDeferred` and `isBadgeDeferred()` are not ported, so `getTabComponent()` reads the badge eagerly.
Our [`Tabs`](../schemas/tabs.md) computes `isDeferred` as `not isServerDriven and …`, and a manager's tab bar is server-driven by construction, so a deferred badge is structurally unreachable there.
The consequence is an invariant on the consumer: the badge statics run on **every** live request of the owning page, so keep them O(1).

### The default active tab loads at post-mount, and the manager keeps no `mount()` of its own

Filament loads it from `mount()` (`RelationManager.php:120-123`), which is safe there because Livewire populates a nested component's matching properties *before* `mount()` runs.
ux-twig-component is the other way round: [`ComponentFactory::mountFromObject()`](../../../vendor/symfony/ux-twig-component/src/ComponentFactory.php) invokes `mount()` first and only then assigns the mount data `mount()` did not consume, so anything the hook reads is still at its declared default.
That matters for a manager: loading the default active tab **memoises the tab set**, so a `getTabs()` reading the owner key, or a registration property like `TaggedTasksRelationManager::$tag`, would build its tabs (and, through `modifyQueryWithActiveTab()`, scope the table) against values nobody set yet.

So the hook is a `#[PostMount(priority: 30)]` on [`HasTabs`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) shared by all three tabbed hosts, and no host calls it from `mount()`.
Post-mount runs after every property is assigned, and after UX's own query-string seeding (`RequestInitializeSubscriber` listens on `PostMountEvent` at priority 256, and every listener runs before the first `#[PostMount]` method), which buys a second thing: a blank `?tab=` is healed while the props can still be dehydrated with the healed value.
The manager is therefore left with no `mount()` at all: its two keys are assigned by the property loop like any other prop.

### Lazy loading does not port, so the default properties are empty

Filament's `getDefaultProperties()` carries exactly one entry, `lazy`, from `CanBeLazy`.
UX ships `loading: 'lazy'|'defer'`, but it defers the *render*, not the mount: the component is still created, mounted, dehydrated and checksummed on the first paint, and a not-yet-arrived child is absent from the client registry, so a dispatch aimed at it is dropped.
The server-driven tab bar mounts exactly one manager at a time, so the arm buys nothing.
The method ships answering `[]` because it is the seam a subclass overrides.
Revisit for single-manager pages with heavy tables: adopt UX's `loading: 'lazy'`, don't rebuild Livewire's semantics.
Corollary invariant: **no expensive work on a manager's mount path**, which, with no `mount()` of its own, means the `#[PostMount]` hooks its traits carry.

### The manager answers the host's relationship hook where Filament's leaves it null

The hook is not ours: it is declared on Filament's host contract ([`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php)), defaulted to `null` in [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php), and read by [`CreateAction::getRelationship()`](../../../vendor/filament/actions/src/CreateAction.php) as the third fallback (own closure, then the table's relationship, then the host's), which the shipped [`CreateAction`](../actions/create-action.md) mirrors exactly.
What is ours is the **override**: Filament's `RelationManager` inherits the null default, and only [`Resources/Pages/Page`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) and [`Resources/Pages/ManageRelatedRecords`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php) answer it.

We answer it because `Action::getTable()` is null for an action the table never configured, so a `CreateAction` mounted straight on the manager (through a schema, or a group outside the table) would otherwise reach no relationship at all and create a record attached to nothing.
The gate is Filament's own (both of its implementations answer for a `CreateAction` and `null` for everything else), and it is the house rule for the whole hook cluster: a host answering one of them [must discriminate by action](../actions/interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action).
A manager's table always carries the relationship, so this arm is unreachable from the shipped table path and the Unit suite pins it directly.

### The `defaultForm()` two-column wrapper does not port

Filament's `defaultForm()` gives an unconfigured action schema `columns(2)`, so relation-manager modals are two-column by default.
Ours does not: modal schemas stay single-column, matching every other action modal in the toolkit.
A manager that wants two columns says `->columns(2)` in its own `form()`.

### The `@deprecated` label statics do not port

`$recordTitleAttribute`, `$inverseRelationship`, `$label`, `$pluralLabel`, `$modelLabel` and `$pluralModelLabel`, with their getters, are all `@deprecated` in Filament, with "override the `table()` method" as the replacement, and the four `when()` arms of `makeTable()` exist only to apply them.
The port ships no deprecated surface, so only the heading arm of Filament's `makeTable()` survives, minus Filament's `@deprecated` `getTableHeading()` override: the seed is `->heading(fn () => static::getTitle(...))`, rendered by the table's own header band.

### The manager renders a template, not a content schema

Filament's `content()` returns a schema holding the manager's own record-filter tabs, two render hooks and an `EmbeddedTable`.
`EmbeddedTable` and the render hooks are unported, which would leave a content schema wrapping one table and nothing else.
So the manager renders a partial instead: the tab strip when it has one, the table, and its own `<twig:SylamentActions:Modals />`, nothing more.

The tabs do **not** move the decision: [`HasTabs`](has-tabs.md) ships on [the shared tier](interacts-with-relationship-table.md), so a manager declaring `getTabs()` renders a strip above its table, but as a [schema the partial renders](has-tabs.md#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds), by the same guard `ListRecords` uses, rather than as a component placed in a `content()` that does not exist.
The heading needs no chrome of the manager's: `makeTable()` seeds `Table::heading()` with `static::getTitle()` as Filament's does, and the table's `sy-ta-header` band renders it inside the container, so the tab label and the table heading stay one string by construction.

The `Modals` outlet is not optional decoration: without it every action a manager mounts renders **nothing at all**, with no error anywhere.
The Integration suite pins it inside the root for that reason.

### A manager must carry `#[AsLiveComponent]`, and the pass enforces it

Concrete manager classes carry a bare `#[AsLiveComponent]`, exactly as concrete pages do: UX recognises a live component by reflecting for the attribute, and without it a manager nested in a page is silently re-created on every parent re-render, losing its sort, page and selection with no error anywhere.
[`RelationManagerComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/RelationManagerComponentPass.php) turns that into a compile-time `LogicException`: it walks the `sylament.panels.relation_manager` tag, skips abstract definitions, refuses a class without the attribute and two managers deriving one component name, then clears the keyless `twig.component` tag the attribute autoconfiguration materialised and re-adds the full eight-key live one keyed by `getComponentName()`.
It is [`PageComponentPass`](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) minus the `PageController` locator: a manager has no route and no controller.
The authoring-surface cost (one attribute line Filament does not ask for) is the deliberate trade recorded there.
