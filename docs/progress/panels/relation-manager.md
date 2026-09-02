# RelationManager (panels)

[`Sylament\Panels\Resources\RelationManagers\RelationManager`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php)
— one owner record's association as a live component of its own: a table scoped
to that association, the actions that write it, and the modals those actions
open. It ports
[`Resources/RelationManagers/RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php);
the parts of
[`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php)
that are not authorization or deprecations shipped on this class until M6 · 3 and
now sit on the trait both hosts of the tier compose,
[`InteractsWithRelationshipTable`](interacts-with-relationship-table.md) — the
manager is one of the two hosts, [`ManageRelatedRecords`](manage-related-records.md)
the other. It renders through
[`partials/RelationManager/index.html.twig`](../../../packages/panels/templates/partials/RelationManager/index.html.twig)
and is registered as a live component by
[`RelationManagerComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/RelationManagerComponentPass.php).

A manager is never routed to: the page that owns the record embeds it through
schemas' [`LiveComponent`](../schemas/live-component.md) embed, which is what
keeps its sort, page and selection across the page's own re-renders.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Resources/RelationManagers/RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php)
- [`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php)
  — `$relationship`, `getRelationshipName()`, `getRelationshipTitle()`,
  `makeTable()`, all of them now on
  [the shared trait](interacts-with-relationship-table.md)
- [`Resources/RelationManagers/Concerns/CanAuthorizeAccess.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/Concerns/CanAuthorizeAccess.php)
  — the authorization surface, inventoried below and unported

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| The host union — `InteractsWithActions` + `InteractsWithSchemas` + `InteractsWithTable`, `DefaultActionTrait` | `Component implements HasActions, HasSchemas, HasTable` | done | — | [diverges](#the-manager-stands-beside-the-resource-page-base-rather-than-under-it) |
| [`$ownerRecord` / `getOwnerRecord()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `#[Locked] public Model $ownerRecord` | done | — | [diverges](#the-owner-rides-the-wire-as-a-route-key-behind-a-dehydration-bridge) |
| [`$pageClass` / `getPageClass()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `#[Locked] public ?string $pageClass` | done | — | [diverges](#the-owner-rides-the-wire-as-a-route-key-behind-a-dehydration-bridge) |
| [`$relationship` / `getRelationshipName()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — on the shared trait since M6 · 3 | [`InteractsWithRelationshipTable`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | done | — | [diverges](#a-manager-naming-no-relationship-throws-rather-than-reaching-for-a-related-resource) |
| [`getTitle()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) + `$title` | `getTitle()` / `getRelationshipTitle()` | done | — | [semantics](#the-title-fallback-humanizes-the-relationship-name-without-pluralizing-it) |
| [`getIcon()` / `getIconPosition()` / `getBadge()` / `getBadgeColor()` / `getBadgeTooltip()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) + their statics | same five statics | done | — | — |
| [`getTabComponent()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `getTabComponent()` | done | — | [diverges](#deferred-tab-badges-do-not-port) |
| [`make()` → `RelationManagerConfiguration`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `make()` | done | — | [RelationGroup](relation-group.md) |
| [`getDefaultProperties()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `getDefaultProperties()` | done | — | [diverges](#lazy-loading-does-not-port-so-the-default-properties-are-empty) |
| [`getComponentName()` / `getTemplate()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `$view` + Livewire's component-name registration | done | — | [diverges](#the-manager-stands-beside-the-resource-page-base-rather-than-under-it) |
| [`makeTable()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) — the relationship seed, aliased here as `makeBaseRelationshipTable()` under the heading wrapper | [`InteractsWithRelationshipTable::makeTable()`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | done | — | [semantics](#the-relationship-is-seeded-in-maketable-and-table-is-never-redeclared) · [diverges](interacts-with-relationship-table.md#the-composition-clauses-are-ungrouped-and-asymmetric-between-the-hosts) |
| [`form()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) (on the trait) + [`getDefaultActionSchemaResolver()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) (stays here — [the resolver is the host's](manage-related-records.md#the-schema-resolver-is-the-hosts-not-the-tiers)) | same two | done | — | [semantics](#the-view-arm-shares-the-form-builder-because-viewaction-disables-its-own-schema), [diverges](#the-defaultform-two-column-wrapper-does-not-port) |
| [`getDefaultActionRelationship()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | [`Concerns/InteractsWithActions::getDefaultActionRelationship()`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) — Filament's manager inherits the `null` default | done | — | [diverges](#the-manager-answers-the-hosts-relationship-hook-where-filaments-leaves-it-null) |
| [`schemas()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | no counterpart — Livewire has no schema-discovery list | done | — | — |
| [`canViewForRecord()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | `canViewForRecord()` | in progress | the authorization epic | [answers `true` unconditionally](#every-registered-manager-is-reachable-by-anyone-until-the-authorization-epic) |
| `isReadOnly()` / `canReorder()`'s read-only arm | `isReadOnly()`, `canReorder()` | todo | the authorization epic | [inventory](#every-registered-manager-is-reachable-by-anyone-until-the-authorization-epic) |
| `getDefaultActionAuthorizationResponse()` / `getDefaultActionIndividualRecordAuthorizationResponseResolver()` / `CanAuthorizeAccess` / `checkPolicyExistence()` / `skipAuthorization()` | [`RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php), [`Concerns/CanAuthorizeAccess.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/Concerns/CanAuthorizeAccess.php) | todo | the authorization epic | [inventory](#every-registered-manager-is-reachable-by-anyone-until-the-authorization-epic) |
| `content()` — the manager's own tabs + `EmbeddedTable` | `content()` | todo | `EmbeddedTable`, record-filter tabs | [diverges](#the-manager-renders-a-template-not-a-content-schema) |
| `$isBadgeDeferred` / `isBadgeDeferred()` | `RelationManager.php` | skipped | — | [diverges](#deferred-tab-badges-do-not-port) |
| `CanBeLazy` — `lazy()` / `isLazy()` | [`support/src/Concerns/CanBeLazy.php`](../../../vendor/filament/support/src/Concerns/CanBeLazy.php) | skipped | — | [diverges](#lazy-loading-does-not-port-so-the-default-properties-are-empty) |
| The six `@deprecated` label statics + `$inverseRelationship` and their `makeTable()` `when()` arms | `RelationManager.php` | skipped | — | [diverges](#the-deprecated-label-statics-do-not-port) |
| [`$relatedResource` / `getRelatedResource()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) and [`getDefaultActionUrl()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php)'s related-resource arms | `RelationManager.php`, byte-identical to the copy on `ManageRelatedRecords` | done | — | [interacts-with-relationship-table.md](interacts-with-relationship-table.md#the-url-arms-are-hoisted-once-and-carry-the-owner-explicitly) |
| `getRenderHookScopes()` / `HasRenderHookScopes`, `render()` / `getViewData()` | `RelationManager.php` | todo | M6 · 6 (render hooks) | — |
| [`getTableQueryStringIdentifier()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) and the manager's own record-filter [`HasTabs`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`InteractsWithRelationshipTable`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | done | — | the manager maps no state onto the URL, so its tabs render unlinkable — Filament's own manager behaviour |
| [`mount()`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) — `loadDefaultActiveTab()` | `mount()` | done | — | — |
| [`recordAction()` / `recordUrl()` row-click seeding in `makeTable()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | [`InteractsWithRelationshipTable::makeTable()`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | done | — | — |
| [`$relationshipTitle` / `getRelationshipTitle()`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | [`InteractsWithRelationshipTable`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | done | — | [diverges](interacts-with-relationship-table.md#the-title-is-an-instance-method-and-the-static-title-chain-never-reaches-the-related-resource) — the getter is an instance method, so the **static** `getTitle()` below never reaches its related arm |
| _rest not audited yet_ | | | | |

[`Unit/Resources/RelationManagers/RelationManagerTest`](../../../packages/panels/tests/Unit/Resources/RelationManagers/RelationManagerTest.php)
covers the statics, the title fallback and its override, the tab component in
both its badged and bare arms, `make()`'s configuration, the component name, and
the two refusals (no relationship, no page class) and the `CreateAction`-only
gate on the host relationship hook — plus the load-bearing pin that `getTable()`
applies the subclass `table()` hook **once**.
[`Integration/Resources/RelationManagers/RelationManagerTest`](../../../packages/panels/tests/Integration/Resources/RelationManagers/RelationManagerTest.php)
mounts real managers over the fixture `Project`/`Task` pair: the heading and the
association-scoped rows, the live root, an action modal rendering inside the
manager's own outlet, a paginated manager's second page and its extreme links,
a `CreateAction` landing attached to the owner with its toast, the
[delete that redirects nowhere](#a-delete-on-a-manager-redirects-nowhere), and
an owner key the resource cannot answer for.
[`Unit/DependencyInjection/Compiler/RelationManagerComponentPassTest`](../../../packages/panels/tests/Unit/DependencyInjection/Compiler/RelationManagerComponentPassTest.php)
pins the pass.

## Details

### Every registered manager is reachable by anyone until the authorization epic

`canViewForRecord()` answers `true` unconditionally and nothing else in the
authorization surface is ported. Stated plainly: **a registered relation manager
is a POST-reachable live component that mutates another record's associations,
gated by nothing.** That is the same exposure the resource pages already carry,
doubled — a manager reaches records the page itself never names. `isReadOnly()`
(and the `canReorder()` arm that consults it) parks with the rest: it exists in
Filament to make managers read-only on view pages, which is a panel-level
authorization default, not a separate feature.

### Every URL a manager generates resolves the default panel

A manager has no GET route of its own — it lives entirely inside live requests —
so it carries the toolkit's **highest** exposure to
[URL generation inside a live action resolving the default panel](README.md#url-generation-inside-a-live-action-resolves-the-default-panel):
`CurrentPanel::get()` is null on every request a manager will ever serve, so
`PanelRegistry::getDefault()` answers all of them. Right for a single-panel
application and for any resource mounted in the default panel; wrong for a
manager whose resource lives **only** in a non-default one, where the generated
route name does not exist and `UrlGenerator` throws `RouteNotFoundException`.
Inventoried here, fixed with the rest of that defect (M6 · 6) — the shape is the
same either way: carry the panel id on the live component and pass `panel:`
explicitly.

M6 · 3 built exactly that shape one tier up, and it reaches a manager only
sideways: the [resource-page base](README.md#resource-pages) now carries a
`panelId` `LiveProp` and stamps `_sylament_panel` back onto the request when it
hydrates, so a manager rendered **inside** a request that hydrates its host page
reads the right panel off that request attribute like anything else in the
request. A manager's own live request — a sort, a page change, an action —
hydrates the manager alone, stamps nothing, and still resolves the default panel.
So the exposure is narrowed, not closed, and it closes when the manager carries
its own prop.

## Semantics

### The title fallback humanizes the relationship name without pluralizing it

`getTitle()` falls back to the relationship name kebab-cased, dashes spaced,
then title-cased word by word — `blogPosts` → `Blog Posts`, verbatim Filament
([`InteractsWithRelationshipTable::getRelationshipTitle()`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php)).
There is **no** pluralization step: the association name is already plural in
every idiomatic mapping, so a manager over `tasks` is titled `Tasks` because
that is what the property is called, not because anything inflected it. A
singular association name titles singular, and that is Filament's behaviour too.

The idiom is the same one [`Pages\Page::getHumanizedClassBasename()`](README.md#page)
uses on a class basename, duplicated here rather than shared: the manager does
not extend that class, and the two humanize different things.

### The relationship is seeded in `makeTable()` and `table()` is never redeclared

`HasTable::getTable()` is `$this->table($this->makeTable())` — the subclass hook
is applied by the *caller*, once. So the relationship seed goes in
`makeTable()`, which is what the base overrides; redeclaring `table()` on the
base to wrap `makeTable()` would run a subclass's hook twice and duplicate every
query scope, column and action it adds. Subclasses override `table()` exactly as
on every other table host, and the Unit suite pins the single application with a
counting fixture.

### The view arm shares the form builder because `ViewAction` disables its own schema

`getDefaultActionSchemaResolver()` answers `form(...)` for `CreateAction`,
`EditAction` **and** `ViewAction`. Filament's view arm wraps the form in
`defaultInfolist()`/`infolist()`; ours has no infolist tier to wrap it in yet
(infolists is a placeholder package), and the shipped
[`ViewAction`](../actions/view-action.md) already calls `disabledSchema()` in its
`setUp()`, so the read-only rendering is the action's doing rather than the
resolver's. When infolists land, the arm splits.

### A delete on a manager redirects nowhere

`HasActions::getDefaultActionSuccessRedirectUrl()` already answers `null`, and
the manager does **not** extend the resource-page base — so there is no
delete-to-index redirect to inherit and no override to write. A deleted row just
leaves the manager's table. The Integration suite pins it, because the obvious
reading (a `DeleteAction` on a resource-shaped host sends you to the index) is
what the resource pages do and what a reader would expect here.

### There is no refresh-after-save eventing, and that is parity

Filament v5.7.6 defines no relation-manager listener, no emitted event and no
parent-saved refresh signal — a preserved child deliberately does not re-render
on the parent page's save, and this port matches it. A consumer who wants one
wires it themselves with `emitUp()` + a `#[LiveListener]` on the manager — never
a bare `emit()`, which is a page-wide broadcast costing one HTTP request per
receiving listener.

## Divergences

### The manager stands beside the resource page base rather than under it

Filament's `RelationManager` extends `Livewire\Component`; ours implements the
three host contracts directly and composes `DefaultActionTrait` +
`HasActions` + `HasSchemas` + `HasTable`. It deliberately does **not** extend
[`Resources\Pages\Page`](README.md#resource-pages): that base answers five
route and navigation statics for a route a manager does not have, and inherits
the delete-to-index redirect a manager must not have. The cost is roughly twenty
duplicated lines — `getComponentName()`, `getTemplate()`, the registry setter —
and it is the same trade Filament makes.

### The owner rides the wire as a route key behind a dehydration bridge

Filament's `ownerRecord` is a `#[Locked] Model` — Livewire serialises an
Eloquent model by class and key. UX LiveComponent cannot: a generic base cannot
declare the concrete entity type, and a `?object` prop hands the hydrator the
useless class-string `object`. So the prop is the owner's **route key**
(`?string`) plus a `?string $pageClass`, and `getOwnerRecord()` resolves it
through `pageClass::getResource()` → the resource registry →
`resolveRecordRouteBinding()`, memoised for the request, `NotFoundHttpException`
on a miss. `is_subclass_of($pageClass, Resources\Pages\Page::class)` gates the
client-supplied class-string against the **resource**-page base, so the shell
`Pages\Page` (and `Dashboard` with it) cannot get past it.

Both props carry `dehydrateWith:`, and that is mandatory rather than defensive.
`LiveComponentHydrator::dehydrate()` reads every prop through the
`PropertyAccessor`, which prefers a same-named getter — so `getOwnerRecord()`
would hand the resolved **entity** to a `?string` wire prop and every render
would throw. The bridges return the raw props. This is the shipped
[`EditRecord::dehydrateRecord()`](README.md#editrecord) pattern, and it is the
same trap the root `CLAUDE.md` records for tables' writable props.

`#[Locked]` needs no port: a non-writable `LiveProp` is checksummed by default.

That resolution is a **bare** `resolveRecordRouteBinding($key)` — no parent
scope, where a nested resource page passes one
([why every key on such a page is scoped](nested-resources.md#one-scope-serves-the-route-key-and-the-unchecksummed-action-key)).
It is safe as it stands because the key is a checksummed `LiveProp` the host page
stamped from its own already-scoped record, so a tampered value fails the
checksum before any query runs. It stops being safe the day a manager is mounted
with a key from anywhere else — a `#[LiveArg]`, a URL — which is the thing to
check before widening how a manager is mounted.

### A manager naming no relationship throws rather than reaching for a related resource

Filament's `getRelationshipName()` falls back to
`getRelatedResource()::getParentResourceRegistration()->getRelationshipName()`.
A host that declares no `$relationship` throws a `LogicException` naming the
class and the property instead — the alternative, PHP's "typed static property
must not be accessed before initialization" `Error`, is the same failure with
none of the diagnosis.

**M6 · 3 widened this rather than reversing it: `$relationship` stays mandatory
even with a `$relatedResource` set.** The middle arm reads the related resource's
parent registration **statically**, which our instance-side registration cannot
answer, and the method is itself static because the static title chain calls it —
so instance-izing it would ripple through `getTitle()` and the whole tab read.
The affordance is narrow: Filament's own generator writes `$relationship` into
every manager and page it emits, related resource or not, so the fallback serves
a hand-written host that declares one and not the other. The message is
host-neutral ("The relationship table [%s] names no relationship") now that
[the trait](interacts-with-relationship-table.md#getrelationshipname-stays-two-arm-a-related-resource-is-not-a-fallback)
serves the routed page too, and the Unit suite pins the refusal **with** a related
resource set.

### Deferred tab badges do not port

`$isBadgeDeferred` and `isBadgeDeferred()` are not ported, so
`getTabComponent()` reads the badge eagerly. Our
[`Tabs`](../schemas/tabs.md) computes `isDeferred` as
`not isServerDriven and …`, and a manager's tab bar is server-driven by
construction — a deferred badge is structurally unreachable there. The
consequence is an invariant on the consumer: the badge statics run on **every**
live request of the owning page, so keep them O(1).

### Lazy loading does not port, so the default properties are empty

Filament's `getDefaultProperties()` carries exactly one entry, `lazy`, from
`CanBeLazy`. UX ships `loading: 'lazy'|'defer'`, but it defers the *render*, not
the mount: the component is still created, mounted, dehydrated and checksummed
on the first paint, and a not-yet-arrived child is absent from the client
registry, so a dispatch aimed at it is dropped. The server-driven tab bar
already mounts exactly one manager at a time, so the arm buys nothing. The
method ships answering `[]` because it is the seam a subclass overrides.
Revisit for single-manager pages with heavy tables — adopt UX's `loading:
'lazy'`, don't rebuild Livewire's semantics. Corollary invariant: **no expensive
work in a manager's `mount()`**.

### The manager answers the host's relationship hook where Filament's leaves it null

The hook is not ours: it is declared on Filament's host contract
([`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php)),
defaulted to `null` in
[`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php),
and read by
[`CreateAction::getRelationship()`](../../../vendor/filament/actions/src/CreateAction.php)
as the third fallback — own closure, then the table's relationship, then the
host's — which the shipped [`CreateAction`](../actions/create-action.md) mirrors
exactly. What is ours is the **override**: Filament's `RelationManager` inherits
the null default, and only
[`Resources/Pages/Page`](../../../vendor/filament/filament/src/Resources/Pages/Page.php)
and
[`Resources/Pages/ManageRelatedRecords`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php)
answer it.

We answer it because `Action::getTable()` is null for an action the table never
configured, so a `CreateAction` mounted straight on the manager — through a
schema, or a group outside the table — would otherwise reach no relationship at
all and create a record attached to nothing. The gate is Filament's own — both of
its implementations answer for a `CreateAction` and `null` for everything else —
and it is also the house rule for the whole hook cluster: a host answering one of
them [must discriminate by action](../actions/interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action).
A manager's table always carries the relationship, so this arm is unreachable
from the shipped table path and the Unit suite pins it directly.

### The `defaultForm()` two-column wrapper does not port

Filament's `defaultForm()` gives an unconfigured action schema `columns(2)`, so
relation-manager modals are two-column by default. Ours does not: modal schemas
stay single-column, matching every other action modal in the toolkit. A manager
that wants two columns says `->columns(2)` in its own `form()`.

### The `@deprecated` label statics do not port

`$recordTitleAttribute`, `$label`, `$pluralLabel`, `$modelLabel`,
`$pluralLabel`/`$pluralModelLabel` and `$inverseRelationship` are all
`@deprecated` in Filament, with "override the `table()` method" as the
replacement — and the four `when()` arms of `makeTable()` exist only to apply
them. Porting them would port a deprecation on the day the class ships. Only
Filament's heading arm of `makeTable()` survives verbatim — the seed is
`->heading(fn () => static::getTitle(...))`, rendered by the table's own header
band.

### The manager renders a template, not a content schema

Filament's `content()` returns a schema holding the manager's own record-filter
tabs, two render hooks and an `EmbeddedTable`. All four are deferred —
`EmbeddedTable` and render hooks are unported, and the record-filter tabs ride
with list-page tabs — which would leave a content schema wrapping one table and
nothing else. So the manager renders a partial instead: the table and its own
`<twig:SylamentActions:Modals />`, nothing more. The heading needs no chrome of
the manager's — `makeTable()` seeds `Table::heading()` with `static::getTitle()`
exactly as Filament's does, and the table's `sy-ta-header` band renders it
inside the container, so the tab label and the table heading stay one string by
construction.

The `Modals` outlet is not optional decoration: without it every action a
manager mounts renders **nothing at all**, with no error anywhere. The
Integration suite pins it inside the root for that reason.

### A manager must carry `#[AsLiveComponent]`, and the pass enforces it

Concrete manager classes carry a bare `#[AsLiveComponent]`, exactly as concrete
pages do — UX recognises a live component by reflecting for the attribute, and
without it a manager nested in a page is silently re-created on every parent
re-render, losing its sort, page and selection with no error anywhere.
[`RelationManagerComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/RelationManagerComponentPass.php)
turns that into a compile-time `LogicException`: it walks the
`sylament.panels.relation_manager` tag, skips abstract definitions, refuses a
class without the attribute, then clears the keyless `twig.component` tag the
attribute autoconfiguration materialised and re-adds the full eight-key live one
keyed by `getComponentName()`. It is
[`PageComponentPass`](README.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute)
minus the `PageController` locator — a manager has no route and no controller.
The authoring-surface cost (one attribute line Filament does not ask for) is
the deliberate trade recorded there.
