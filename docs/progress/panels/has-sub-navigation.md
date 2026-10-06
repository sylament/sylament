# Sub-navigation (panels)

The band of sibling pages a page draws beside its own content — Edit ↔ Manage
Sections on one article's record pages — in three positions, and the machinery
behind it:

- [`Pages\Concerns\HasSubNavigation`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php)
  on the base [`Pages\Page`](README.md#page): the `getSubNavigation()` hook, the
  `getCachedSubNavigation()` normalization every template reads,
  `generateNavigationItems()` (the item factory a resource's
  `getRecordSubNavigation()` calls), `getSubNavigationParameters()` and the
  position ladder.
- [`Pages\Enums\SubNavigationPosition`](../../../packages/panels/src/Pages/Enums/SubNavigationPosition.php)
  — `Start` / `End` / `Top`, Filament-exact.
- [`Panel\Concerns\HasSubNavigation`](../../../packages/panels/src/Panel/Concerns/HasSubNavigation.php)
  — the panel-wide `subNavigationPosition()` knob, defaulting to `Start`.
- [`Resource::getRecordSubNavigation(Page)`](../../../packages/panels/src/Resources/Resource.php)
  and `Resource::getSubNavigationPosition()`, which
  [`InteractsWithRecord`](interacts-with-record.md) bridges to, plus the
  resource-page override of the position and
  `Page::generateResourcePageNavigationItems()`, the seam that turns a sibling
  **resource** page into an item.
- Four templates — `SubNavigation` (the dispatcher),
  `SubNavigationSidebar` (`Start`/`End`), `SubNavigationTabs` (`Top`) and
  `SubNavigationMobileMenu` (every position, below `md`) — mounted by the five
  page partials (`Page`, `ListRecords`, `CreateRecord`, `EditRecord` and
  `ManageRelatedRecords`), plus `sub-navigation.css` and two `page.css` rules.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Pages/Concerns/HasSubNavigation.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasSubNavigation.php)
  — the trait, the normalization and the item factory
- [`Pages/Enums/SubNavigationPosition.php`](../../../vendor/filament/filament/src/Pages/Enums/SubNavigationPosition.php)
- [`Resources/Resource/Concerns/HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php)
  — `getRecordSubNavigation()`
- [`resources/views/components/page/sub-navigation/`](../../../vendor/filament/filament/resources/views/components/page/sub-navigation)
  — the three rendering variants
- [`resources/css/components/page.css`](../../../vendor/filament/filament/resources/css/components/page.css)
  — the `fi-page-has-sub-navigation` cascade

## Surface

Every point of this feature. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`SubNavigationPosition`](../../../packages/panels/src/Pages/Enums/SubNavigationPosition.php) | [`Pages/Enums/SubNavigationPosition.php`](../../../vendor/filament/filament/src/Pages/Enums/SubNavigationPosition.php) | done | — | — |
| [`getSubNavigation()`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php) | `HasSubNavigation::getSubNavigation()` | done | — | [diverges](#the-cluster-arms-are-not-ported-so-the-resource-page-hard-override-is-moot) |
| [`getCachedSubNavigation()`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php) | `HasSubNavigation::getCachedSubNavigation()` | done | — | [semantics](#the-normalization-answers-groups-only-and-the-ungrouped-bucket-comes-first) |
| [`generateNavigationItems()`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php) | `HasSubNavigation::generateNavigationItems()` | done | — | [diverges](#a-plain-page-item-is-built-here-where-filament-asks-the-page-for-it) |
| [`getSubNavigationParameters()`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php) | same | done | — | [diverges](interacts-with-record.md#the-sub-navigation-parameters-carry-keys-and-the-parent-chain) |
| [`Page::shouldRegisterNavigation()`](../../../packages/panels/src/Pages/Page.php) + its static | [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | done | — | — |
| [`getSubNavigationPosition()`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php) — the page tier | `HasSubNavigation::getSubNavigationPosition()` | done | — | [diverges](#the-position-is-one-instance-method-with-no-static-twin) |
| [`Panel::subNavigationPosition()`](../../../packages/panels/src/Panel/Concerns/HasSubNavigation.php) / `getSubNavigationPosition()` | [`Panel/Concerns/HasSubNavigation.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasSubNavigation.php) | done | — | — |
| [`Resource::getSubNavigationPosition()`](../../../packages/panels/src/Resources/Resource.php) + its static | `Resource\Concerns\HasNavigation::getSubNavigationPosition()` | done | — | [diverges](#the-position-is-one-instance-method-with-no-static-twin) |
| [`Resources\Pages\Page::getSubNavigationPosition()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [diverges](#a-resource-page-keeps-its-own-position-static-where-filaments-drops-it) |
| [`Resource::getRecordSubNavigation()`](../../../packages/panels/src/Resources/Resource.php) | `HasNavigation::getRecordSubNavigation()` | done | — | — |
| [`Resources\Pages\Page::generateResourcePageNavigationItems()`](../../../packages/panels/src/Resources/Pages/Page.php) | `Resources\Pages\Page::getNavigationItems(array $urlParameters)` | done | — | [diverges](#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route) |
| [`SubNavigation`](../../../packages/panels/templates/components/SubNavigation/index.html.twig) — the dispatcher | the position branch inside [`page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php) | done | — | [diverges](#three-sibling-components-not-one-with-variants) |
| [`SubNavigationSidebar`](../../../packages/panels/templates/components/SubNavigationSidebar/index.html.twig) — `Start` / `End` | [`page/sub-navigation/sidebar.blade.php`](../../../vendor/filament/filament/resources/views/components/page/sub-navigation/sidebar.blade.php) | done | — | [semantics](#the-band-reuses-the-sidebar-group-and-prefixes-its-dom-ids) |
| [`SubNavigationTabs`](../../../packages/panels/templates/components/SubNavigationTabs/index.html.twig) — `Top` | [`page/sub-navigation/tabs.blade.php`](../../../vendor/filament/filament/resources/views/components/page/sub-navigation/tabs.blade.php) | done | — | [semantics](#the-top-variant-is-the-first-pin-of-tabsitems-anchor-arm) |
| [`SubNavigationMobileMenu`](../../../packages/panels/templates/components/SubNavigationMobileMenu/index.html.twig) | [`page/sub-navigation/mobile-menu.blade.php`](../../../vendor/filament/filament/resources/views/components/page/sub-navigation/mobile-menu.blade.php) | done | — | [diverges](#the-mobile-menu-renders-no-group-headers) |
| The band on the base page chrome + the root modifiers | the same block in `page/index.blade.php` | done | — | [semantics](#an-empty-band-never-resolves-a-panel) |
| [`sub-navigation.css`](../../../packages/panels/assets/src/styles/components/sub-navigation.css) + the two `page.css` rules | [`components/page.css`](../../../vendor/filament/filament/resources/css/components/page.css) | done | — | [diverges](#the-top-modifier-is-emitted-and-styled-by-nothing) |
| The record pages' own `getNavigationLabel()` / `getNavigationIcon()` overrides — what labels an `EditRecord` in a band | [`Resources/Pages/EditRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) and its `ViewRecord` sibling — `CreateRecord` declares neither | todo | — | [detail](#an-editrecord-in-a-band-is-labelled-by-its-class-name) |
| `processParentNavigationItems()` — the `key` / `parentItem` / `childItems` nesting pass | same | todo | [`NavigationItem`'s nesting arm](README.md#navigationitem) | [detail](#three-v1-skips-item-nesting-collapsible-groups-and-badge-tooltips) |
| Collapsible sub-navigation groups | the Alpine collapse key Filament namespaces per band | skipped | — | [detail](#three-v1-skips-item-nesting-collapsible-groups-and-badge-tooltips) |
| `badgeTooltip` on a band item | `NavigationItem::badgeTooltip()` | skipped | — | [detail](#three-v1-skips-item-nesting-collapsible-groups-and-badge-tooltips) |
| The `canAccess()` filter in `generateNavigationItems()` | the `$isResourcePage ? canAccess($parameters) : canAccess()` pair | todo | the authorization epic | [detail](#three-v1-skips-item-nesting-collapsible-groups-and-badge-tooltips) |
| The cluster arms — `getSubNavigation()`'s cluster branch, the position's cluster tier | same | skipped | — | [diverges](#the-cluster-arms-are-not-ported-so-the-resource-page-hard-override-is-moot) |
| Render hooks around the band | `PanelsRenderHook::PAGE_SUB_NAVIGATION_*` | todo | M6 · 6 (render hooks) | — |

[`Unit/Pages/Concerns/HasSubNavigationTest`](../../../packages/panels/tests/Unit/Pages/Concerns/HasSubNavigationTest.php)
covers the trait with no kernel: the empty default, all four rungs of the
position ladder including a page with no services at all, the five normalization
facts (ungrouped items wrapped in one label-less group first, grouped items
folded by label, a **label-less declared group** folded into the same bucket,
per-group sorting, hidden items dropped from both), the never-collapsible groups,
the render memo, the plain-page item with its group and its active state, the
`shouldRegisterNavigation()` filter, and the two refusals — a resource page
listed by a plain page, and a URL asked for outside any panel.
[`Unit/Resources/Pages/PageTest`](../../../packages/panels/tests/Unit/Resources/Pages/PageTest.php)
carries the resource-page half: the page static winning over the resource in both
directions, one item per sibling page with exactly one active, a nested band URL
carrying the whole chain, and a page its resource registers nowhere contributing
nothing.
[`Integration/Twig/SubNavigationRenderTest`](../../../packages/panels/tests/Integration/Twig/SubNavigationRenderTest.php)
renders it: the tabs variant with its anchors, its one `[aria-current="page"]`
and its group dropdown; the sidebar variant before **and** after the content
(adjacent-sibling selectors, so document order is asserted rather than presence);
the band's groups staying expanded and off the sidebar controller; the mobile
dropdown in all three positions, labelled by the active item and triggered
through its icon alias; the root modifiers; the band on the three resource
partials it serves — `ListRecords`, `CreateRecord` and `EditRecord` — at both
sidebar edges; and a page with no band drawing nothing at all. The fifth
partial's band is rendered by
[`Integration/Resources/Pages/ManageRelatedRecordsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ManageRelatedRecordsTest.php)
instead, which asserts it on both the edit page and the manage page and pins
which of the two is active.
[`Unit/PanelTest`](../../../packages/panels/tests/Unit/PanelTest.php) and
[`Integration/Resources/ResourceTest`](../../../packages/panels/tests/Integration/Resources/ResourceTest.php)
pin the panel knob (literal and closure) and the resource tier.

## Details

### Three v1 skips: item nesting, collapsible groups and badge tooltips

- **`processParentNavigationItems()`** is deferred whole, with the
  [`NavigationItem` nesting arm](README.md#navigationitem) it needs: the pass
  groups items by `getParentItem()` and hangs them off a parent's
  `childItems()`, and none of `key()` / `parentItem()` / `childItems()` is
  ported. Filament's own tabs variant ignores child items anyway, and its sidebar
  variant is the only renderer of them, so the visible gap is one variant of one
  band. It returns with the item's nesting arm, not before.
- **Collapsible groups.** Every group `getCachedSubNavigation()` returns is
  stamped `collapsible(false)`, so a labelled band group renders as a plain
  labelled list. Filament's groups collapse, keyed by a namespaced Alpine store
  entry; ours would share
  [`SidebarGroup`'s label-keyed storage](README.md#sidebar-group-ids-are-positional-not-derived-from-the-label)
  with the **main** sidebar, so a band group and a sidebar group of the same
  label would collapse each other. Non-collapsible is the honest v1; the fix is a
  storage namespace on the sidebar controller, not on the band.
- **`badgeTooltip`** is not ported on `NavigationItem` at all, and Filament's own
  resource-page item factory drops it too — so the band matches vendor here by
  accident rather than by decision, and both close together.
- **`canAccess()`** has no port anywhere yet, so `generateNavigationItems()`
  filters on `shouldRegisterNavigation()` alone: **a band lists every sibling
  page, whether or not the viewer may open it.** Same exposure as the sidebar and
  the resource pages themselves; it closes with the authorization epic.

### An `EditRecord` in a band is labelled by its class name

Filament's `EditRecord` overrides `getNavigationLabel()` to *Edit* and
`getNavigationIcon()` to a pencil-square alias — both exist **for** the band, and
neither is ported ([EditRecord](README.md#editrecord) records the deferral). The
band therefore labels an edit page through the inherited chain on
[`Pages\Page`](README.md#page): `$navigationLabel`, then `$title`, then the
humanized class basename — so an `EditArticle` page reads *Edit Article* in the
band where Filament reads *Edit*, and carries no icon unless the page declares
one. Declaring the two statics on the page is the whole workaround, and porting
Filament's overrides (with the `view-record` pair beside them — `CreateRecord`
declares neither, and `PanelsIconAlias` ships no `create-record` navigation-item
constant to port) is what makes the Filament look the default. The gap existed before this
epic and was invisible: nothing read those methods on a resource page until the
band did.

## Semantics

### The normalization answers groups only, and the ungrouped bucket comes first

`getCachedSubNavigation()` always answers `array<NavigationGroup>` — the
templates never branch on an item-versus-group union. Ungrouped items are wrapped
in one label-less group placed **first**, each labelled group follows in
declaration order, every group's items are filtered by `isVisible()` and sorted
by `getSort()`, and the result is memoized for the render.

Two shapes are worth stating because the vendor reaches them from the other
direction. A **declared** `NavigationGroup` carrying no label folds its items
into the ungrouped bucket rather than drawing a second unlabelled group;
Filament keys its groups by label — `null` becoming the `''` key — and then folds
loose items *into* that declared group, so both codebases end with exactly one
label-less group holding both sets. And declared items come before loose ones on
a sort tie, again matching, because the fold happens before the bucket is sorted
and `usort` is stable.

### An empty band never resolves a panel

The band is emitted **once**, by
[the base page chrome](README.md#routing) every page template extends — it was
five copies across five partials until M6 · 2 collapsed them, and the copies were
byte-identical, which is why nothing about the band changed in that move. The
chrome reads `this.getCachedSubNavigation` first and only asks for the
**position** when that is non-empty:

```twig
{% set subNavigation = this.getCachedSubNavigation %}
{% set subNavigationPosition = subNavigation is not empty ? this.getSubNavigationPosition : null %}
```

The position ladder ends at the panel, so a plain page with no band would
otherwise resolve `CurrentPanel` (and, failing that, the default panel) on every
render just to pick a class it then does not emit. With the guard, a page with no
sub-navigation emits neither root modifier and touches no panel at all — which is
also what keeps the trait usable on a page built with `new`, holding no services:
it answers `Start` and nothing asks.

[`SimplePage`](simple-page.md)'s own partial is the one page template that draws
no band at all: a centred card has no room for one, so it reads neither the
navigation nor the position.

### The band reuses the sidebar group and prefixes its DOM ids

`SubNavigationSidebar` renders one `<twig:SylamentPanels:SidebarGroup>` per
group rather than a band-specific list component — the group's markup is already
the label-plus-items shape the band wants, its CSS is not sidebar-scoped, and a
non-collapsible group renders as a plain labelled `<div>` with no disclosure
semantics and no control to operate.

The reuse costs one thing, and the template pays it at the call site:
`SidebarGroup` builds `id="sy-pa-sidebar-group-items-{groupIndex}"`
unconditionally, and a `Start`/`End` band renders on the same document as the
panel sidebar, both starting at index 0. The band passes
`:groupIndex="'sub-' ~ loop.index0"`, so the two id families cannot collide;
`SubNavigationRenderTest` pins `#sy-pa-sidebar-group-items-sub-0`. Filament has
no such collision because its band is a different component with no ids at all.

### The `Top` variant is the first pin of `TabsItem`'s anchor arm

The `Top` band renders support's `Tabs` with `tag="a"` `TabsItem`s carrying
`:href` and `:active` — the anchor arm of that component, which nothing had
rendered until now, so the band's render test is also that arm's first coverage.
A labelled group becomes a dropdown-triggering tab over support's `Dropdown`,
which is Filament's shape.

Assertions on the active tab target `[aria-current="page"]` and never a bare
`[aria-current]`: an inactive `TabsItem` emits `aria-current="false"`, because
[the attribute bag stringifies aria booleans](../../internals/templates.md#how-do-the-two-attribute-bags-render-a-value) for Laravel
parity, so a bare-attribute selector matches every tab.

## Divergences

### The position is one instance method, with no static twin

Filament's `getSubNavigationPosition()` is **static** at every tier, and reaches
the panel through the `Filament` facade. Ours is an **instance** method on the
page trait, on `Resource` and on the panel — a static that needs a dependency is
exactly the signal
[principles §1](../../principles.md#the-test) names for dropping `static`, and
there is no facade to reach a panel through. Shipping both — a static answering
without the panel tier and an instance twin answering with it — would be worse
than the divergence: two methods of one name, one of them quietly wrong.

The ladder itself is Filament's, one rung shorter: the page's own
`protected static ?SubNavigationPosition $subNavigationPosition` → (on a resource
page) the resource's own static → the panel knob → `Start`. Filament's missing
rung is the cluster tier, deferred with clusters.

One authoring trap comes with the static, and it is not new: a page that
**assigns** `static::$subNavigationPosition` rather than declaring it writes the
property on `Pages\Page` itself and sets the position for every page in the
process. A concrete page that means to set its position from PHP must redeclare
the property; declaring it (`protected static ?SubNavigationPosition
$subNavigationPosition = SubNavigationPosition::Top;`) is the ordinary way and
has no such edge.

### A resource page keeps its own position static where Filament's drops it

Filament's `Resources\Pages\Page::getSubNavigationPosition()` is
`static::getResource()::getSubNavigationPosition()` — a flat override, so the
page-level static it inherits from `HasSubNavigation` is dead on every resource
page. Ours reads
`static::$subNavigationPosition ?? $this->getResourceInstance()->getSubNavigationPosition()`,
so the tier a user would reasonably expect to work does work: one page of a
resource can name a position the rest of the resource does not. Pinned in both
directions, since the difference is invisible until someone declares the static
and it is ignored.

### A plain page item is built here, where Filament asks the page for it

Filament's `generateNavigationItems()` delegates to each component's own
`getNavigationItems()`. Ours does that for a **resource** page, through
[the seam below](#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route),
but builds a plain panel page's item inline from the same statics
(`getNavigationLabel()`, `getNavigationGroup()`, `getNavigationIcon()`,
`getNavigationSort()`, `getNavigationBadge()`/`getNavigationBadgeColor()`), and
mints its URL through `$component::getRouteName($panel)` plus the injected
`UrlGeneratorInterface`.

The reason is one signature: `Pages\Page::getNavigationItems(Panel $panel)`
[takes the panel](README.md#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade)
where Filament's reaches a facade, and the band has a panel — but calling it
would also inherit that method's own URL and active-state closures, which are
built for a *sidebar* item (active on the page's route, resolved through the
`Invoker` at render time) rather than for a band item that must be active by
[page-class identity](#the-active-state-is-page-class-identity-not-a-route-match).
So the band builds what it needs. The two item shapes agree on everything a user
declares; a page listed in a band gets its `->group()` exactly as a sidebar item
does, which is what lets a labelled band group hold plain pages at all.

A page whose panel or URL generator cannot be resolved raises a `LogicException`
naming both rather than rendering an item that links nowhere.

### A resource page item is built by the band because a page cannot name its own route

Filament's `Resources\Pages\Page::getNavigationItems(array $urlParameters)` mints
its own URL through `static::getUrl($parameters)`, which reaches
`getResourcePageName()` — a static reverse-lookup of the page's own `getPages()`
key. Neither is available here:
[a resource page's inherited route statics answer for the wrong route](README.md#a-resource-page-inherits-page-route-statics-that-answer-for-the-wrong-route),
and that decision is deliberately parked.

So the band builds the item instead, in
`Resources\Pages\Page::generateResourcePageNavigationItems()` — a **protected**
override of a throwing seam on the trait, which is what keeps a plain page from
silently building a broken resource-page item. It looks the page's key up by
scanning its **own** resource's `getPages()` for the registration whose `page`
matches, then mints `$resource->getUrl($key, $parameters, isAbsolute: false,
shouldGuessMissingParameters: true)` — a path, where Filament's are absolute
(`getNavigationUrl()` leaves `getUrl()`'s `$isAbsolute = true` default alone). A
page no resource registers contributes nothing, silently — which is what
Filament's `hasPage()` gates do too.

Two consequences worth naming. The sibling's **own** resource mints the URL, so a
band may list pages of another resource. And this epic therefore routes *around*
the parked static-shape decision rather than forcing it: nothing new is public,
and `getResourcePageName()` still has no port.

### The active state is page-class identity, not a route match

Filament's item is active when `original_request()->routeIs(<the page's route>)`.
A route match is wrong here for a reason that only shows up after the first
click: our pages **are** live components, so on every live re-render the current
route is `ux_live_component` and no band item would match — the band would lose
its active tab the moment the page did anything.

`generateNavigationItems()` builds each item with
`->isActiveWhen(fn (): bool => $component === static::class)`, evaluated against
the page doing the rendering. Deterministic, live-safe, and equivalent for every
item the generator builds: an item for page X is active exactly when X is
rendering. A hand-built `NavigationItem` in `getRecordSubNavigation()` keeps
whatever `isActiveWhen()` it declares.

### Three sibling components, not one with variants

The band is four templates under `templates/components/` —
`SubNavigation/index.html.twig` dispatching to
`SubNavigationSidebar`, `SubNavigationTabs` and `SubNavigationMobileMenu` —
rather than one `SubNavigation` with `<Variant>.html.twig` files beside its
index. That is not a preference: schemas'
[`ComponentTemplateLocationTest`](../../../packages/schemas/tests/Architecture/ComponentTemplateLocationTest.php)
admits only `components/<Name>/index.html.twig`, and variants only under
`partials/` — so a variant file here would fail an architecture test in a package
this epic does not touch. The rule and its enforcement now agree in the
[root `CLAUDE.md`](../../../CLAUDE.md), whose template-layout paragraph claimed
variants were legal under both halves.

The dispatcher is where the `end` flag is read: each partial mounts the component
**twice**, once before its content and once after with `:end="true"`, and the
component draws the sidebar at exactly one of them (`Start` before, `End` after),
the tabs before, and the mobile menu on the pre-content pass only. One component
serving both sidebar edges with the DOM order decided by the caller is Filament's
own arrangement.

### The mobile menu renders no group headers

Filament's `< md` band (`page/sub-navigation/mobile-menu.blade.php`) is a
dropdown that emits a `dropdown.header` per labelled group. Support ships no dropdown-header component,
so ours flattens every group's items into one list under a trigger labelled by
the active item (falling back to the first). Groups are a v1 affordance of the
two wide variants only. It closes the day support grows the header component;
nothing else about the menu changes with it.

### The `Top` modifier is emitted and styled by nothing

`page.css` gains exactly two rules — `gap-8` on `sy-pa-page-main` under
`sy-pa-page-has-sub-navigation`, and `md:flex-row md:items-start` under the
`-start` / `-end` modifiers — while `sy-pa-page-has-sub-navigation-top` is
emitted on the root and matched by no rule at all. That is deliberate rather than
missing: the `Top` band is a `Tabs` component that styles itself and stacks above
the content the column layout already produces, so a rule would have nothing to
say. The class ships because it is the hook a consumer styles a top band through,
and because the three modifiers are one family.

The neighbouring divergence updates with this epic:
[the page's main container is a flex column](README.md#the-pages-main-container-is-a-flex-column-where-v5-gates-that-on-a-full-height-page)
where vendor gates that on `.fi-page-has-sub-navigation`. The sub-navigation half
of that gate is now live and behaves identically — the band renders beside the
content at `md` and above in both codebases — so what remains deferred there is
the `fullHeight` half alone.

### The cluster arms are not ported, so the resource-page hard override is moot

Filament's `getSubNavigation()` returns a cluster's components when the page
belongs to one, and its `Resources\Pages\Page` overrides the method back to `[]`
so a resource page never picks up its cluster's band by accident. Clusters are
Beyond, so the base hook is a plain `[]` and the resource-page override has
nothing to guard against — it is not ported either.
[`InteractsWithRecord::getSubNavigation()`](interacts-with-record.md) is what
fills the hook on a **record** page, bridging to the resource, which is Filament's
arrangement exactly once the cluster arm is gone.
