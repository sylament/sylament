---
description: 'The panel page base: title, heading, navigation statics, routing and the live-component half every panel page shares.'
ports:
  - vendor/filament/filament/src/Pages/Page.php
  - vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php
  - vendor/filament/filament/src/Pages/Concerns/InteractsWithFormActions.php
  - vendor/filament/filament/src/Pages/Concerns/HasMaxWidth.php
  - vendor/filament/filament/src/Pages/Concerns/HasTopbar.php
  - vendor/filament/filament/src/Pages/Concerns/HasUnsavedDataChangesAlert.php
owns:
  - packages/panels/src/Pages/Page.php
---
# Page (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Pages\Page`](../../../packages/panels/src/Pages/Page.php) | [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php), [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php), [`InteractsWithFormActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithFormActions.php), [`HasMaxWidth.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasMaxWidth.php), [`HasTopbar.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasTopbar.php), [`HasUnsavedDataChangesAlert.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The panel page carries the declarative surface plus the routing and live-component half

The declarative surface plus the routing and live-component half.
The `title` static with `getTitle()` (falling back to the kebab-cased, title-cased class basename), the `heading`/`subheading` pair with `getHeading()` falling back to the title, the navigation statics (`navigationIcon`, `activeNavigationIcon`, `navigationLabel`, `navigationSort`, `navigationGroup`) with their static getters, and the `navigationBadge`/`navigationBadgeColor` hooks a subclass overrides.
`getNavigationLabel()` carries Filament's full fallback chain — `$navigationLabel`, then `$title`, then the humanized class basename — so a page that sets neither static still names itself in the sidebar.

Routing and rendering: `getSlug()` (the `$slug` static, else the kebab-cased class basename), `getRoutePath()`, `getRouteName(Panel $panel)` — the `sylament.<panel id>.pages.<slug>` convention — `getComponentName()` (the FQCN with `\` replaced by `:`, the ux-twig-component tag name), and `getTemplate()` (the `$template` property, else `@SylamentPanels/partials/Page/index.html.twig`) as the `template_from_method` target.
`DefaultActionTrait` makes the page a live component.
`getNavigationItems(Panel $panel)` builds the one `NavigationItem` a page contributes, its `url()` and `isActiveWhen()` closures resolving `UrlGeneratorInterface` and `RequestStack` through the `Invoker` at render time rather than capturing them.

`getBreadcrumbs(): array` is here too, returning `[]`, and its contract is the map [support's `Breadcrumbs`](../support/breadcrumbs.md) consumes: a **string** key is a URL and its crumb links, an **int** key is plain text.
The base returns nothing, so a plain panel page — the [`Dashboard`](dashboard.md) included — heads no trail, and [`Page/index.html.twig`](routing.md) renders no `Header` at all unless something else fills it.

Two more members gate navigation.
`shouldRegisterNavigation()` and its `protected static bool $shouldRegisterNavigation = true` are Filament's, and the flag gates both surfaces: the [sub-navigation band](has-sub-navigation.md) filters on it, and `Panel::getNavigation()`'s page loop `continue`s on it in the same shape the resource half of that loop already used — which is the position Filament's `Pages\Page::registerNavigationItems()` early-returns from.
So a page opting out is absent from a band and from the **sidebar** alike, pinned by an `OptedOutNavigationPage` fixture registered and routed like its siblings, which leaves the gate the only thing keeping it out.
The other two arms of that method stay unported on the terms the [resource loop](panel.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource) already states: `getCluster()` guards an unported subsystem, and `canAccess()` waits on authorization.

The base also composes [`Pages\Concerns\HasSubNavigation`](has-sub-navigation.md), so **every** panel page can draw a band: `getSubNavigation()` returns `[]` by default, `getCachedSubNavigation()` normalizes whatever a page answers with, and `generateNavigationItems()` builds one item per sibling page.
The page's own `$subNavigationPosition` static is the first rung of the position ladder.

**The base is a page a user writes**, and the whole of that tier is [custom-pages.md](custom-pages.md).
In one paragraph: the class now `implements InteractsWithActions` and `InteractsWithSchemas` and composes `HasActions` (aliased) and `HasSchemas`, so **every** panel page hosts actions and schemas — with `schemas()` narrowed to `[]` and an identity `content(Schema): Schema` a page overrides for its body; five `Pages\Concerns` traits arrive beside it ([`InteractsWithHeaderActions`](custom-pages.md), [`InteractsWithFormActions`](form-actions.md), [`HasMaxWidth` and `HasTopbar`](simple-page.md), [`HasUnsavedDataChangesAlert`](unsaved-changes-alert.md)); the header-action walk, `callHook()`, `halt()`, the `panelId` prop and its [priority-20 stamp](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism) moved up from the [resource base](resource-pages.md); and `getPanel()`, `getPageClasses()`, `getExtraBodyAttributes()`, `getLayout()`/`getLayoutData()`, `getMaxContentWidth()`, `hasUnsavedDataChangesAlert(): false` and a `#[LiveListener('refresh-page')] refresh()` are new.
The partial it renders through is no longer a page with an empty body but [the chrome every other page template extends](routing.md), with `header`, `content` and `footer` blocks.

[`Pages\SimplePage`](simple-page.md) is the one subclass the package ships beside [`Dashboard`](dashboard.md): an abstract page that picks the centred-card shell through `getLayout()`, composes `HasMaxWidth` and `HasTopbar`, renders its own partial, and defaults `$shouldRegisterNavigation` to `false` so it stays out of a sidebar it does not draw.
Filament's cannot be routed through `pages()` at all — [a strictly larger surface here](simple-page.md#simplepage-extends-page-so-it-is-routable-and-hides-itself-from-navigation).

Deferred, all todo:

- The `BasePage` split — Filament separates `BasePage` (the render/lifecycle half) from `Page` (the panel-aware half); it waits on porting auth, which is what first needs a page that knows no panel.
- The panel-level breadcrumb toggle.
  Filament's `Panel::breadcrumbs(bool)` / `hasBreadcrumbs()` switches the trail off for a whole panel and its `hasResourceBreadcrumbs()` does the same for resource pages alone; neither is ported, so a page that returns crumbs always renders them.
- A writer for the `heading`/`subheading` pair.
  Filament ships none either — its `BasePage` declares both as protected properties a subclass assigns or a subclass getter overrides — so today the pair stays subclass-only.

Three deltas against Filament's page: [`$navigationGroup` is string-only](#navigationgroup-is-string-only-on-page-too), [`getNavigationItems()` and `getRouteName()` take the panel](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade), and [a concrete page carries a bare `#[AsLiveComponent]` that a compiler pass registers over](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute).

## Semantics

### A page lifecycle hook is a method name, not a registration

`callHook(string $hook)` is `method_exists($this, $hook)` and then a dynamic call, which is the whole implementation and is Filament's.
Nothing registers a hook and nothing declares one: a page opts into `beforeSave` by *writing a method called* `beforeSave`, and a page that writes none pays one `method_exists()` per site.

Two consequences are worth stating because the shape hides them.
A **typo is silent** — `beforeSve()` is simply never called, with no error at load or at run, which is the cost Filament accepts for a zero-ceremony extension point and which we accept with it.
And the hooks are not an interface, so nothing on the base declares them; the list of names is the contract, and it lives in the two page sections above rather than in code.
The sites and their order are Filament's exactly: `beforeFill` / `afterFill` around a fill, `beforeValidate` / `afterValidate` around the state read, `beforeCreate` / `afterCreate` around the create write, and `beforeSave` / `afterSave` around the save — with `beforeSave` firing *inside* the state read, before the mutator, which [the edit page's lifecycle table](edit-record.md) flags because normalising it would be a divergence nobody would notice.

## Divergences

### `$navigationGroup` is string-only on `Page` too

Filament types the static `string | UnitEnum | null` so a page can name its group with an enum case, matching `NavigationItem::group()`.
Ours is `?string`, for the same reason and on the same schedule as [the item's missing arm](navigation-item.md#group-and-getgroup-carry-no-unitenum-arm) — the two have to agree about what a group key is, so they move together.

It stays deferred, deliberately.
The panel's navigation assembly buckets items by `getGroup()` and matches a registered group by key or label, all of it string-keyed; widening the page static alone would produce a group key nothing matches, and widening the whole path means answering how an enum case *names* a group and where its label and icon come from — which is `NavigationGroup::fromEnum()` and the `HasLabel` / `HasIcon` / `Collapsible` contracts behind it, none of them ported.
So the three move as one piece: `fromEnum()`, `NavigationItem::group()`'s enum arm, and this static.

### Pages register as live components through a compiler pass over a per-class attribute

Filament registers a panel's pages as Livewire components imperatively, from `Panel::register()` through `registerLivewireComponents()` and `queueLivewireComponentForRegistration()`.
The Symfony translation is split in two, and both halves are load-bearing: a **bare `#[AsLiveComponent]`** on every concrete page class, and [`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) writing the registration over the top of it.

The pass alone was the original shape, and it was wrong.
UX does not recognise a live component by its container tag — it **reflects for the attribute**: `LiveComponentStack::isLiveComponent()` is `[] !== new \ReflectionClass($classname)->getAttributes(AsLiveComponent::class)` (`vendor/symfony/ux-live-component/src/Util/LiveComponentStack.php:46-49`), and that class is `final` with the method `private`, so there is no seam to teach it about a tag.
A tag-only page is therefore not a live *parent*: every live component nested inside it is silently re-created on each re-render, with no error anywhere.
Relation managers are exactly that nesting, which is what forced the attribute in.
It is also the idiomatic answer (principles §2) — the ecosystem already ships the marker for "this class is a live component", and tagging without it was the deviation.

Class attributes do not inherit, and neither does the reflection check, so the attribute cannot be hoisted onto the abstract `Page` base: every concrete page a consumer writes repeats one line.
Forgetting it used to fail badly and silently.
Now the pass throws at compile time, naming the class and the fix — the missing marker is a container-build error rather than a live child that quietly loses its state.

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
Accepted, then, but it fixes the shape of the auth work: **the authorization gate belongs on the component mount, not on the panel routes**.
A check written against the route defaults (`_sylament_panel`, `_sylament_page`) secures exactly the half of the surface the pass did not create, and leaves the POST endpoint open under a name derived from the page's own FQCN.

The cost is an authoring-surface addition Filament does not have: one `#[AsLiveComponent]` line per concrete page class, which a consumer coming from Filament has no reason to expect.
It buys the only live-parent recognition UX offers, and the throw means it is never *silently* missing.

### `getNavigationItems()` takes the panel rather than reaching a facade

Filament's `Page::getNavigationItems()` is parameterless and reaches the current panel through the `Filament` facade — `getRouteName(?Panel $panel = null)` defaults to `Filament::getCurrentOrDefaultPanel()` when the argument is omitted.
A **static** method cannot reach a service in Symfony, which is the same constraint the [`Resource` carried decision](resource.md#resource-is-a-service-not-a-static-class-string-registry) opens with, so the panel travels as a required argument: `getNavigationItems(Panel $panel)` and `getRouteName(Panel $panel)`.
Both callers already hold one — [`Panel::getNavigation()`](panel.md) passes `$this`, [`PanelRouteLoader`](routing.md) passes the panel it is mounting — so nothing has to find a panel it did not already have.

The argument moves the other way where nothing reads it.
Filament's `getRoutePath(Panel $panel)` and `getSlug(?Panel $panel = null)` take a panel solely to look up a per-panel `PageConfiguration` (the same page registered twice in one panel under different keys); that surface is unported, so both are parameterless here and gain the argument if and when configurations land.
The policy is the same in both directions: the panel is an argument exactly where the method reads it, never as a uniform prefix.

### The actions host sits on the base because a trait outranks an inherited method

[`Pages\Page`](#the-panel-page-carries-the-declarative-surface-plus-the-routing-and-live-component-half) — not `Resources\Pages\Page`, and not `ListRecords`, `CreateRecord` or `EditRecord` — `implements InteractsWithActions` and `use`s `HasActions` (through the ungrouped alias `use HasActions { getAction as private getCachedOrDeclaredAction; }`), while `Resources\Pages\Page` writes `getDefaultActionUrl()` and the rest of the `getDefault*` family in its class body.
That placement is forced, not stylistic.
PHP's precedence is *current class > inserted trait > inherited class*, so a subclass that re-`use`d `HasActions` would insert the trait's `getDefaultActionUrl(): ?string { return null; }` **over** the resource base's override, and every `CreateAction` on that page would render with no href — no error, no warning, just a dead button.
A `ListRecords` with its own `use HasActions` is exactly the arrangement that would swallow the hook, so the trait sits above the resource base, on the plain page base.

That placement is also the closer parity: Filament composes `InteractsWithActions` on `BasePage`, so *every* page is an actions host there too — and here it is what lets a hand-written page declare header actions without extending a resource.
The **same** rule and the same hazard apply one tier down to `HasSchemas`, which sits on the page base too, and there the failure is not a dead button but a 500 on the first dehydrate ([why every leaf had to drop it](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf)).
So the rule for this hierarchy is simply: **a page never re-`use`s a trait its base already carries.**
It reads like tidiness and is not; `packages/panels/tests/Integration/Resources/Pages/ListRecordsTest.php` fails on the rendered `href` the moment the actions half is broken, and [`Unit/Resources/Pages/PageTest`](../../../packages/panels/tests/Unit/Resources/Pages/PageTest.php) pins which class each shadowed member is declared on.
