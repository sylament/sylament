---
description: 'The panel page base Pages\Page: title, heading, navigation statics, routes, breadcrumbs and its registration as a live component.'
ports:
  - vendor/filament/filament/src/Pages/Page.php
  - vendor/filament/filament/src/Pages/BasePage.php
  - vendor/filament/filament/src/Pages/Concerns/HasRoutes.php
  - vendor/filament/filament/src/Panel/Concerns/HasComponents.php
  - vendor/filament/support/src/Concerns/CanCallHooks.php
owns:
  - packages/panels/src/Pages/Page.php
---
# Page (panels)

[`Pages\Page`](../../../packages/panels/src/Pages/Page.php) is the base every panel page extends; the members a user writes a page against (the actions and schemas hosts, `content()`, the partial's blocks, the layout and width seams) are in [custom-pages.md](custom-pages.md), and the sub-navigation band it composes is in [has-sub-navigation.md](has-sub-navigation.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$title` / `getTitle()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | — |
| [`$heading` / `$subheading` / `getHeading()` / `getSubheading()`](../../../packages/panels/src/Pages/Page.php) | [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | done | — | — |
| [`$navigationIcon` / `$activeNavigationIcon` / `$navigationLabel` / `$navigationSort`](../../../packages/panels/src/Pages/Page.php) and their static getters, `getNavigationBadge()` / `getNavigationBadgeColor()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | — |
| [`$navigationGroup` / `getNavigationGroup()`](../../../packages/panels/src/Pages/Page.php) | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [diverges](#navigationgroup-is-string-only-on-page-too) |
| [`$shouldRegisterNavigation` / `shouldRegisterNavigation()`](../../../packages/panels/src/Pages/Page.php) | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [semantics](#a-page-that-opts-out-of-navigation-is-absent-from-the-sidebar-and-the-band-alike) |
| [`getNavigationItems(Panel $panel)`](../../../packages/panels/src/Pages/Page.php) | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [diverges](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade) |
| [`$slug` / `getSlug()` / `getRoutePath()`](../../../packages/panels/src/Pages/Page.php) | [`Pages/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasRoutes.php) | done | — | [diverges](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade) |
| [`getRouteName(Panel $panel)`](../../../packages/panels/src/Pages/Page.php) | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [diverges](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade) |
| [`getComponentName()` / `$template` / `getTemplate()`](../../../packages/panels/src/Pages/Page.php), with `DefaultActionTrait` | no counterpart — Livewire names a component itself | done | — | — |
| [`DependencyInjection\Compiler\PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) | [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) (`registerLivewireComponents()` / `queueLivewireComponentForRegistration()`) | done | — | [diverges](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) |
| [`getBreadcrumbs()`](../../../packages/panels/src/Pages/Page.php) | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | [breadcrumbs.md](../support/breadcrumbs.md) |
| [`callHook()`](../../../packages/panels/src/Pages/Page.php) — the page's own hook method | [`support/Concerns/CanCallHooks.php`](../../../vendor/filament/support/src/Concerns/CanCallHooks.php) | done | — | [semantics](#a-page-lifecycle-hook-is-a-method-name-not-a-registration) |
| The per-trait hook arm (`<hook><TraitBasename>()`) | [`support/Concerns/CanCallHooks.php`](../../../vendor/filament/support/src/Concerns/CanCallHooks.php) | todo | — | [detail](#the-per-trait-hook-arm-is-not-ported) |
| `$breadcrumb` / `getBreadcrumb()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | todo | — | — |
| The navigation-hierarchy breadcrumbs (`getNavigationHierarchyBreadcrumbs()` and its helpers) | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | todo | — | [detail](#the-navigation-hierarchy-breadcrumbs-wait-on-the-panels-breadcrumb-settings) |
| `$navigationBadgeTooltip` / `getNavigationBadgeTooltip()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | blocked | `NavigationItem::badgeTooltip()` | — |
| `$navigationParentItem` / `getNavigationParentItem()` and the item `key()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | blocked | the `NavigationItem` nesting arm | [navigation-item.md](navigation-item.md) |
| `getUrl()` / `getNavigationUrl()` / `getNavigationItemActiveRoutePattern()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | todo | — | [detail](#a-page-has-no-url-method-of-its-own) |
| The `BasePage` split | [`BasePage.php`](../../../vendor/filament/filament/src/Pages/BasePage.php) | todo | — | [detail](#the-basepage-split-waits-on-auth) |
| `getDefaultTestingSchemaName()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | todo | — | — |
| The static setters `navigationGroup()` / `navigationIcon()` / `navigationLabel()` / `navigationSort()` / `navigationParentItem()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | skipped | — | [diverges](resource.md#nothing-is-memoised-in-a-static) |
| `$isDiscovered` / `isDiscovered()` | [`Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | skipped | — | [diverges](resource.md#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem) |
| _rest not audited yet_ | — | — | — | — |

## Details

### The navigation-hierarchy breadcrumbs wait on the panel's breadcrumb settings

Filament's `getBreadcrumbs()` prepends the page's place in the navigation (its group and parent item) when the panel's `hasNavigationHierarchyInBreadcrumbs()` is on, then appends `$breadcrumb` or the title.
The port's `getBreadcrumbs()` returns `[]`, which is what Filament's answers for a page with no `$breadcrumb`, no cluster and the hierarchy setting off, its default.
The hierarchy arm needs the `$hasNavigationHierarchy` flag of `Panel::breadcrumbs()`, which is unported with the rest of that panel setting, so a page that returns crumbs always renders them.

### A page has no URL method of its own

Filament's `Page::getUrl()` generates the page's route URL for the current panel, and `getNavigationUrl()` and `getNavigationItemActiveRoutePattern()` feed the navigation item from it.
The port's navigation item resolves its URL and active state from `getRouteName($panel)` through closures typed `UrlGeneratorInterface` and `RequestStack`, so nothing on the page generates its URL directly.

### The per-trait hook arm is not ported

After the page's own hook method, Filament's `CanCallHooks::callHook()` walks `class_uses_recursive($this)` and calls `<hook><TraitBasename>()` once for every trait that declares one, so a trait composed into a page can join a lifecycle site without the page forwarding to it.
The port's `callHook()` stops after the page's own method, so a trait that declares such a method is never called.
No trait in the port relies on the arm.

### The `BasePage` split waits on auth

Filament separates `BasePage` (the render and lifecycle half) from `Page` (the panel-aware half).
The port has one `Pages\Page`, because the first consumer of a page that knows no panel is the auth pages, which are unported.

## Semantics

### A page lifecycle hook is a method name, not a registration

`callHook(string $hook)` is `method_exists($this, $hook)` and then a dynamic call, which is the first half of Filament's `CanCallHooks::callHook()`; the per-trait half is [not ported](#the-per-trait-hook-arm-is-not-ported).
Nothing registers a hook and nothing declares one: a page opts into `beforeSave` by *writing a method called* `beforeSave`, and a page that writes none pays one `method_exists()` per site.

Two consequences are worth stating because the shape hides them.
A **typo is silent** — `beforeSve()` is simply never called, with no error at load or at run, which is the cost Filament accepts for a zero-ceremony extension point and which we accept with it.
And the hooks are not an interface, so nothing on the base declares them; the list of names is the contract, and it lives in [create-record.md](create-record.md) and [edit-record.md](edit-record.md) rather than in code.
The sites and their order are Filament's exactly: `beforeFill` / `afterFill` around a fill, `beforeValidate` / `afterValidate` around the state read, `beforeCreate` / `afterCreate` around the create write, and `beforeSave` / `afterSave` around the save — with `beforeSave` firing *inside* the state read, before the mutator, which [the edit page](edit-record.md) keeps because normalising it would be a divergence nobody would notice.

### A page that opts out of navigation is absent from the sidebar and the band alike

`shouldRegisterNavigation()` gates both surfaces: the [sub-navigation band](has-sub-navigation.md) filters on it, and `Panel::getNavigation()`'s page loop skips the page, which is the position Filament's `Page::registerNavigationItems()` early-returns from.
The other two arms of that method stay unported on the terms the [resource loop](panel.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource) states: `getCluster()` guards an unported subsystem, and `canAccess()` waits on authorization.

### The actions host sits on the base because a trait outranks an inherited method

`Pages\Page` — not `Resources\Pages\Page`, and not `ListRecords`, `CreateRecord` or `EditRecord` — `implements InteractsWithActions` and `use`s `HasActions` (through the ungrouped alias `use HasActions { getAction as private getCachedOrDeclaredAction; }`), while `Resources\Pages\Page` writes `getDefaultActionUrl()` and the rest of the `getDefault*` family in its class body.
That placement is forced, not stylistic.
PHP's precedence is *current class > inserted trait > inherited class*, so a subclass that re-`use`d `HasActions` would insert the trait's `getDefaultActionUrl(): ?string { return null; }` **over** the resource base's override, and every `CreateAction` on that page would render with no href — no error, no warning, just a dead button.
A `ListRecords` with its own `use HasActions` is exactly the arrangement that would swallow the hook, so the trait sits above the resource base, on the plain page base.

That placement is also the closer parity: Filament composes `InteractsWithActions` on `BasePage`, so *every* page is an actions host there too — and here it is what lets a hand-written page declare header actions without extending a resource.
The **same** rule and the same hazard apply one tier down to `HasSchemas`, which sits on the page base too, and there the failure is not a dead button but a 500 on the first dehydrate ([why every leaf had to drop it](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf)).
So the rule for this hierarchy is simply: **a page never re-`use`s a trait its base already carries.**
It reads like tidiness and is not.

## Divergences

### `$navigationGroup` is string-only on `Page` too

Filament types the static `string | UnitEnum | null` so a page can name its group with an enum case, matching `NavigationItem::group()`.
Ours is `?string`, for the same reason and on the same schedule as [the item's missing arm](navigation-item.md#group-and-getgroup-carry-no-unitenum-arm) — the two have to agree about what a group key is, so they move together.

The arm is deferred deliberately.
The panel's navigation assembly buckets items by `getGroup()` and matches a registered group by key or label, all of it string-keyed; widening the page static alone would produce a group key nothing matches, and widening the whole path means answering how an enum case *names* a group and where its label and icon come from — which is [`NavigationGroup::fromEnum()`](navigation-group.md#fromenum-waits-on-the-collapsible-enum-contract) and the `Collapsible` enum contract it waits on.
So the three move as one piece: `fromEnum()`, `NavigationItem::group()`'s enum arm, and this static.

### Pages register as live components through a compiler pass over a per-class attribute

Filament registers a panel's pages as Livewire components imperatively, from `Panel::register()` through `registerLivewireComponents()` and `queueLivewireComponentForRegistration()`.
The Symfony translation is split in two, and both halves are load-bearing: a **bare `#[AsLiveComponent]`** on every concrete page class, and [`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) writing the registration over the top of it.

A pass alone is not enough.
UX does not recognise a live component by its container tag — it **reflects for the attribute**: `LiveComponentStack::isLiveComponent()` is `[] !== new \ReflectionClass($classname)->getAttributes(AsLiveComponent::class)` (`vendor/symfony/ux-live-component/src/Util/LiveComponentStack.php:46-49`), and that class is `final` with the method `private`, so there is no seam to teach it about a tag.
A tag-only page is therefore not a live *parent*: every live component nested inside it is silently re-created on each re-render, with no error anywhere.
Relation managers are exactly that nesting.
The attribute is also the idiomatic answer (principles §2): the ecosystem ships the marker for "this class is a live component", and tagging without it would be the deviation.

Class attributes do not inherit, and neither does the reflection check, so the attribute cannot be hoisted onto the abstract `Page` base: every concrete page a consumer writes repeats one line.
The pass throws at compile time when a page lacks it, naming the class and the fix, so the missing marker is a container-build error rather than a live child that quietly loses its state.

What the attribute does *not* do is register the component usefully.
Attribute autoconfiguration (`LiveComponentExtension.php:94-102`) adds `twig.component` carrying `AsLiveComponent::serviceConfig()` with the null and empty entries filtered — a tag with **no `key`** and no template, which would leave `TwigComponentPass` deriving a name from the FQCN against the *application's* `twig_component.defaults` and throwing for a class outside them.
So the pass `clearTag('twig.component')` and re-adds the full eight-key payload (`key`, `live`, `route`, `method`, `url_reference_type`, `expose_public_props`, `attributes_var` and `template_from_method: 'getTemplate'`), plus `controller.service_arguments`, which is harmlessly duplicated.
The ordering that makes the clear work is Symfony's own: `AttributeAutoconfigurationPass` and `ResolveInstanceofConditionalsPass` run at beforeOptimization **priority 100**, so by the time this pass runs at priority 10 the attribute-derived tag is already materialized, and `TwigComponentPass` at priority 0 sees exactly one tag with an explicit key.
`template_from_method` is the payload the attribute's `new FromMethod('getTemplate')` form produces, and it is the arm that matters: a literal `template:` string is fixed at compile time, where `getTemplate()` lets the base class supply the default and a subclass override `$template`.

Owning the tag rather than accepting the attribute's is what buys the two things an attribute could not do.
Two pages deriving the same component name throw at compile time naming both services, where a duplicate attribute key would silently leave one registration standing (`TwigComponentPass` has no duplicate guard on the explicit-key path).
And the pass builds the class-keyed `ServiceLocator` `PageController` resolves pages through, so the same tag that makes a page renderable makes it reachable.

The pass' shape — *clear the attribute-derived tag, re-add ours, throw without the attribute* — is the contract every live-component pass in this package follows.

What the pass tags is every `Page` service the application registers, not every page a panel lists — `pages()` is read by [`PanelRouteLoader`](routing.md), never by the pass.
So a page class that no panel attaches still becomes a live component mounted at `ux_live_component` over POST and still becomes a public controller service through `controller.service_arguments`; it has no panel GET route and is reachable anyway.
It could not be otherwise: the pass runs at container compile time and a panel is assembled at runtime, by `PanelRegistry` calling each provider's `panel()`, so no `pages()` list exists to filter by when the tags are written.
This is accepted, and it fixes the shape of the auth work: **the authorization gate belongs on the component mount, not on the panel routes**.
A check written against the route defaults (`_sylament_panel`, `_sylament_page`) secures exactly the half of the surface the pass did not create, and leaves the POST endpoint open under a name derived from the page's own FQCN.

The cost is an authoring-surface addition Filament does not have: one `#[AsLiveComponent]` line per concrete page class, which a consumer coming from Filament has no reason to expect.
It buys the only live-parent recognition UX offers, and the throw means it is never *silently* missing.

### `getNavigationItems()` takes the panel rather than reaching a facade

Filament's `Page::getNavigationItems()` is parameterless and reaches the current panel through the `Filament` facade — `getRouteName(?Panel $panel = null)` defaults to `Filament::getCurrentOrDefaultPanel()` when the argument is omitted.
A **static** method cannot reach a service in Symfony, which is the same constraint the [`Resource` carried decision](resource.md#resource-is-a-service-not-a-static-class-string-registry) opens with, so the panel travels as a required argument: `getNavigationItems(Panel $panel)` and `getRouteName(Panel $panel)`.
Both callers already hold one — [`Panel::getNavigation()`](panel.md) passes `$this`, [`PanelRouteLoader`](routing.md) passes the panel it is mounting — so nothing has to find a panel it did not already have.

The argument moves the other way where nothing reads it.
Filament's `getRoutePath(Panel $panel)` and `getSlug(?Panel $panel = null)` take a panel solely to look up a per-panel `PageConfiguration` (the same page registered twice in one panel under different keys); that surface is unported, so both are parameterless here and gain the argument when configurations land.
The policy is the same in both directions: the panel is an argument exactly where the method reads it, never as a uniform prefix.
