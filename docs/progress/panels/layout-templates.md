---
description: 'The panel shell''s Twig components (sidebar, topbar, logo, theme switcher, user menu, layout) and the panel icon aliases.'
ports:
  - vendor/filament/filament/resources/views/components/
  - vendor/filament/filament/src/View/PanelsIconAlias.php
  - vendor/filament/filament/resources/views/components/page/simple.blade.php
  - vendor/filament/filament/resources/views/components/layout/simple.blade.php
  - vendor/filament/filament/resources/views/components/header/simple.blade.php
owns:
  - packages/panels/src/Twig/PanelsIconAlias.php
  - packages/panels/templates/components/
---
# Layout templates (panels)

ten constants: nine of Filament's 32 — the sidebar group chevron, the topbar hamburger, the three theme-switcher glyphs ([Layout templates](#the-shell-components-render-the-navigation-model-and-the-chrome-as-static-twig)), the dashboard's navigation icon ([Dashboard](dashboard.md)), the global search field's magnifying glass ([global-search.md](global-search.md)), and the two the record surfaces use, the sub-navigation mobile-menu chevron ([has-sub-navigation.md](has-sub-navigation.md)) and the manage-related-records navigation item ([manage-related-records.md](manage-related-records.md#the-navigation-label-and-icon-drop-their-related-resource-arms)) — plus [the user-menu trigger, which is an invention](#user_menu_trigger-is-an-addition-not-a-rename)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Layout templates](../../../packages/panels/templates/components) | [`resources/views/components`](../../../vendor/filament/filament/resources/views/components) | in progress | — | — |
| [`Twig\PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) | [`View/PanelsIconAlias.php`](../../../vendor/filament/filament/src/View/PanelsIconAlias.php) | in progress | — | — |
| [SimplePage template](../../../packages/panels/templates/partials/SimplePage/index.html.twig) | [`components/page/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/page/simple.blade.php) | in progress | — | — |
| [SimpleLayout template](../../../packages/panels/templates/components/SimpleLayout/index.html.twig) | [`components/layout/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/simple.blade.php) | in progress | — | — |
| [SimpleHeader template](../../../packages/panels/templates/components/SimpleHeader/index.html.twig) | [`components/header/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/header/simple.blade.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The shell components render the navigation model and the chrome as static Twig

`Sidebar`, `SidebarGroup` and `SidebarItem` render the navigation model as static Twig components: the `<aside id="sy-pa-sidebar">` shell, one `<li>` per group, and one `<li>` per visible item with its icon (the active icon when the item is active), label and badge — the active item's anchor also carrying `aria-current="page"`.

A group's label element is a disclosure **only when the group is collapsible**.
`SidebarGroup` picks the tag (`{% set labelTag = collapsible ? 'button' : 'div' %}`) and nulls `type`, `aria-expanded`, `aria-controls` and the `sylament--panels--sidebar#toggleGroup` action on the non-collapsible arm, so a static group renders a plain labelled `<div>` with no disclosure semantics and no control to operate; only the collapsible arm gets the button, the `aria-expanded`/`aria-controls` pair pointing at `sy-pa-sidebar-group-items-<index>`, and the chevron icon.
Hidden items never render, and collapse state is markup only — the `sy-collapsed` class plus `data-group-label`, which the Stimulus controller reads and writes.

The chevron points **up** when the group is open and flips through `-rotate-180` when it collapses, which is Filament's pairing (`Heroicon::ChevronUp` plus `.fi-collapsed .fi-sidebar-group-collapse-btn { -rotate-180 }`).
A 180° turn is direction-symmetric, so the rule carries no `rtl:` arm — the `-rotate-90` it replaced needed one, and drawing a down chevron rotated a quarter turn pointed the closed state sideways where Filament points it down.

`Topbar` is the full-width bar above the layout — v5's DOM, where the topbar is a sibling of `.fi-layout` rather than a child of the main column, so the sidebar sits *under* it and the bar spans the viewport.
It holds the drawer toggle, a `sy-pa-topbar-start` carrying the `Logo`, and a `sy-pa-topbar-end` carrying an `end` block a caller may fill and then the `UserMenu`.
The end wrapper is unconditional: it is where the shell's own chrome goes, so it renders whether or not a caller fills the block.

`Logo` is the port of `logo.blade.php`'s three arms, rendered twice — once in the topbar start (hidden below `lg`) and once in the sidebar's own mobile header, which is where the brand appears while the drawer is open.
A `Markup` brand logo renders raw inside a `<div class="sy-pa-logo">`, a string one as an `<img>` whose alt is the brand name plus ` logo` (Filament's `layout.logo.alt` translation, hardcoded English while i18n is parked), and with neither the brand name falls back as a `<span>` — which still renders **nothing at all** when the panel has no brand name either, which is what leaves a brandless mobile header an empty strip rather than a gap with a heading in it.
The `Markup`-vs-string branch is Blade's `$logo instanceof Htmlable`, and porting it is what added the `markup` `TwigTest` to support's `MarkupExtension` (`rendition.logo is markup`) beside the existing `filled` test — the reusable Twig-side half of the `Htmlable` → `Markup` escaping contract.
The logo arms carry an inline `height:` style (`brandLogoHeight() ?? '1.5rem'`, defaulted in the template as Filament does); the text fallback carries none, matching the Blade.
When `darkModeBrandLogo()` is set both renditions render, the light one stamped `sy-pa-logo-light` (`dark:hidden`) and the dark one `sy-pa-logo-dark` (`hidden dark:flex`) — Filament's `fi-logo-light`/`fi-logo-dark` pair, CSS-toggled so no JS is involved.
The three settings thread as props down the same `Document` → `Layout` → `Topbar`/`Sidebar` chain `brandName` already rides, where Filament's blade reads the `filament()` facade in place.

`ThemeSwitcher` and `UserMenu` are the topbar-end pair.
The switcher is a `role="group"` of three buttons — light, dark, system — each carrying its `data-theme` value, an `aria-pressed` the controller keeps in sync, and its own overridable icon alias; the buttons are written out rather than looped, because a computed `constant('…THEME_SWITCHER_' ~ theme|upper ~ '_BUTTON')` would be invisible to Twig-CS-Fixer's `ValidConstantFunctionRule`, the one check in the toolchain that validates a hardcoded FQCN string, and Filament unrolls the same three too.
Each button also fires `sylament--support--dropdown#close` beside the theme action, so picking a theme dismisses the menu it sits in — Filament's `(theme = …) && close()` in one attribute.
Rendered outside a dropdown the extra action is inert, Stimulus ignoring an action whose controller is on no ancestor.

`UserMenu` is a support `Dropdown` whose trigger is an `IconButton` and whose panel today holds only the theme switcher, rendered [in place rather than teleported](#the-theme-switchers-dropdown-is-not-teleported).
It is **a stub until auth is ported**, and every part of that is visible in the markup: there is no user name, no avatar, no menu items, no `getUserMenuItems()`, and none of Filament's four gates — `auth()->check()`, `hasThemeSwitcher()`, `hasDarkModeForced()`, `getDefaultThemeMode()` — so the menu renders unconditionally for an anonymous visitor.
Its trigger icon is [an alias with no Filament counterpart](#user_menu_trigger-is-an-addition-not-a-rename).

The shell's default icons are overridable defaults, not hardcoded names: the group chevron, the topbar hamburger, the three theme-switcher glyphs and the user-menu trigger all read [`PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) constants through `constant()` the way [Modal's close button](../../../packages/support/templates/components/Modal/index.html.twig) does, so an app swaps any of them under [`sylament.icons`](../support/icon-manager.md) without touching a template.
Every value drops Filament's `panels::` prefix ([rename](../renames.md)); the constant names are Filament's, except the user-menu one, which Filament has no equivalent for.

`Layout`'s `<main>` always carries a width class beside `sy-pa-main`.
The controller resolves one value per request — a page's `getLayoutData()['maxContentWidth']`, else its `getMaxContentWidth()`, else the panel's (`Width::SevenExtraLarge` for the shell, `Width::Large` for a simple page) — and threads it through the `Document` as `maxContentWidthClass`.
A `Width` case becomes `sy-width-<token>`; a string that is no token is emitted verbatim, Filament's escape hatch for a consumer's own class.
The ladder is 26 rules in `layout.css` nested under `.sy-pa-main, .sy-pa-simple-main`, and `.sy-pa-main` carries no `max-w-*` of its own, so the class is the single source.
The `sy-width-*` name is shared with schemas' `sy-width-full` deliberately: that rule is compound-scoped under `.sy-sc-actions-ctn` and can never match a `<main>`.
All 26 tokens compile under the installed Tailwind 4.3.0 — `@apply container` expands to a base rule plus its five breakpoint rules, so the compiled sheet carries more `sy-width-` rules than there are tokens.
Which shell wraps the page is the page's own `getLayout()`, read by `PageController`: only `SylamentPanels:Layout` and `SylamentPanels:SimpleLayout` ship, and any other value is a `LogicException` naming the page — a third shell is a `Document` template override, not a `getLayout()` value.

`SimpleLayout` and `SimpleHeader` are the second shell, the one a `SimplePage` picks through `getLayout()`: a centred card on an empty ground, with no sidebar, no topbar and no navigation at all.
The layout carries the shell body classes (`sy sy-pa-body sy-pa-simple-layout`), and under `hasTopbar()` a skip link and a `sy-pa-simple-layout-header` strip pinned to the top end corner; the header renders the panel's `Logo`, an `h1` and a `p`, each only when it has something to show.
Where Filament gates that strip twice on `auth()->check()` and fills it with database notifications and a `SimpleUserMenu`, ours is gated on `hasTopbar()` alone and holds the existing `UserMenu` — with auth parked, that menu is the only theme affordance a page with no topbar would otherwise have, and `SimpleUserMenu` is not ported.
The page partial is a deliberate copy rather than a child of the base one: the two share the unsaved-changes preamble and the sentinel verbatim, but a simple page has no header band, no breadcrumbs and no sub-navigation, so there is no common parent left to extend — a `{% extends %}` would inherit a chrome whose every element the child would have to empty.

What Filament's shell has and this does not, all todo:

- Desktop collapse (the narrow icon-only rail) and the collapsed-state tooltips on items — both need panel-level sidebar settings.
  The only one a panel carries is `collapsibleNavigationGroups()`, which is not these.
- Sub-navigation: Filament renders `childItems` nested under an active item, and the nesting arm of `NavigationItem` is deferred whole (see [NavigationItem](navigation-item.md)).
- The whole account half of the user menu, and the panel toggles that gate the topbar chrome — all of it lands with auth.
- `avatar` and `tenant-menu` from `resources/views/components`, neither ported.

The Livewire-shell → static-component divergence these templates sit on is [The shell is not live](#the-shell-is-not-live) above.
Five more are theirs: [sidebar group ids are positional](#sidebar-group-ids-are-positional-not-derived-from-the-label), [the drawer state class sits on the shell root](#the-drawer-state-class-sits-on-the-shell-root-not-on-the-sidebar), [one toggle button replaces v5's shown/hidden pair](#one-toggle-button-replaces-v5s-shownhidden-pair), [`USER_MENU_TRIGGER` is an addition](#user_menu_trigger-is-an-addition-not-a-rename), and [the render tests sit under `tests/Integration/Twig/`](#the-panels-render-tests-live-under-testsintegrationtwig).

## Semantics

### The theme switcher's pressed state is client-only, and survives only because the shell is outside the live root

`ThemeSwitcher` renders all three buttons with `aria-pressed="false"` and no `sy-active`; the controller stamps the real state on `connect()` and on every pick.
The server never learns the choice — it is a `localStorage` value the document head script and the controller both read — so the markup Twig produces is deliberately *wrong* until JS runs, which is fine on a page load and would not be fine inside a re-rendering fragment: ux-live-component morphs a component's subtree from freshly rendered HTML, so a switcher inside a live root would have its `aria-pressed` and `sy-active` reset on every round trip while the actual theme, living on `<html>`, stayed put.

Panel pages are safe by construction and not by care: [the shell is not live](#the-shell-is-not-live), and the [document](routing.md) puts the page's live component *inside* `Layout`'s content block, so the topbar — switcher included — sits outside every live root on the page.
The arrangement that would break it is the mirror image: embedding `Layout` **inside** a live component, which is what the demo's `PanelShellCard` does to show the shell as a showcase card.
It works there today because the card does not re-render; a card that did would watch its switcher's aria state go stale.

## Divergences

### The shell is Turbo-indifferent and worker-safe

Two constraints the shell is built under.
Both are cheap to hold while the shell is six templates and one controller, and expensive to retrofit once pages, panels and widgets hang off them.

**Turbo-indifferent.**
Every behaviour hangs off Stimulus `connect()` — never `DOMContentLoaded`, never `turbo:load`, and never an inline `<script>`.
A handler bound to first paint is dead in a Turbo-driven app from the second navigation on, and an inline script is both a CSP liability and unreachable from a re-rendered fragment.
The landmark ids the shell emits are stable — `sy-pa-sidebar`, `sy-pa-topbar`, `sy-pa-main` — so a swapped body reconnects to the same targets and the skip link keeps working.
Sidebar collapse state is restored from `localStorage` on `connect()` rather than preserved with `data-turbo-permanent`: the markup can be replaced wholesale and the state still survives, which `data-turbo-permanent` would only manage for the exact subtree it guards.

**Worker-safe.**
No mutable static per-request state anywhere in the shell.
A long-running worker (FrankenPHP, RoadRunner, Swoole) reuses the PHP process across requests, so anything static that remembers *this* request leaks into the next one — a class of bug that never shows up under php-fpm and is close to undebuggable in production.
Request awareness reaches the navigation model through evaluated closures instead: `isActiveWhen(fn (RequestStack $requests) => …)`, where the `Invoker` resolves the parameter from the container per call — `RequestStack` is a shared service, but everything it hands back belongs to the request being served, so nothing has to be remembered.
One wiring caveat comes with it, and `NavigationItem` absorbs the part of it sylament's own closures hit: the `Invoker`'s container arm reads through `ContainerInterface::has()`/`get()`, which no **private** service answers — and FrameworkBundle's `RequestStack::class` and `UrlGeneratorInterface::class` aliases and support's `IconManager::class` alias are all private.
Those three are exactly the types sylament's shipped closures hint ([`Page::getNavigationItems()`](page.md) builds the url and active-state ones, [`Dashboard`](dashboard.md) the icon one), so a container-built item carries them itself ([the injected set](navigation-item.md#the-item-hands-its-own-services-to-evaluated-closures)) and no application publishes an alias for them.
A consumer hinting any **other** service type in a navigation closure still lands on the container arm and still has to republish that alias public.
The panels test kernel republishes `RequestStack` for precisely that path, and [`SylamentPanelsBundleTest`](../../../packages/panels/tests/Integration/SylamentPanelsBundleTest.php) pins the arm through a `NavigationGroup`, which carries no override.
The current-panel lookup had to be derived the same way, from the request and never held in a stateful singleton, and [it is](panel.md#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager).

### The shell is not live

Filament v5's sidebar and topbar are Livewire components.
Ours are anonymous Twig components rendering a plain PHP navigation model, because the shell itself needs no server round-trip: navigation is a set of links, collapse state is client-side, and the only interactive parts (the mobile drawer, per-group collapse) are markup plus one Stimulus controller.
A live shell would buy re-rendering on the server and cost a component round-trip on every page.

One thing in the shell has since needed exactly that round trip, and it is not the one this entry predicted.
[Global search](global-search.md) asks the server between navigations — a query in, matching records out — so the topbar now carries a live root.
It is a **sibling** of the static shell rather than a wrapper around it: `Topbar` stays anonymous and renders `<twig:SylamentPanels:GlobalSearch />` inside `sy-pa-topbar-end`, which is where the live boundary begins and ends.
So the round trip is paid by the one element that needs it, and everything else in the bar still renders once per page.
That component is also the toolkit's **first** `#[AsLiveComponent]` in `packages/*/src` — panels' pages are live too, but they are registered by [a compiler pass](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) rather than by the attribute.

Revisit the rest when something else in the shell must change without a navigation — the database-backed notifications trigger is the next candidate, since an unread count in the topbar is a server fact that changes between page loads.
Until then the divergence is a simplification, not a gap.

### Sidebar group ids are positional, not derived from the label

Filament's `group.blade.php` builds `fi-sidebar-group-items-<slug>-<md5 prefix>` from the group label, so the `aria-controls` target is stable per label and unique across everything on the page.
Ours is `sy-pa-sidebar-group-items-<loop index>`, assigned by `Sidebar` from the render loop.
It is stable per render and needs no label at all (a group may have none), and it rests on the shell's **single-sidebar-per-page** assumption — the same assumption the singleton ids `sy-pa-sidebar`, `sy-pa-topbar` and `sy-pa-main` already encode.
Revisit the moment two sidebars can render on one page: groups at the same index would then collide, pointing one group's button at another's item list.
The label-hash approach is the fix if that day comes — noting that collapse *persistence* is keyed the other way, on `data-group-label`, so two groups sharing a label share their stored collapse state and a renamed group loses what it had stored, which is inherent to keying on the label and a property Filament's label-slug ids carry too.

### The panels render tests live under `tests/Integration/Twig/`

The repo convention is to mirror `src/` inside each test bucket, but these templates have no `src/` counterpart — they live in `packages/panels/templates/` and are rendered by name.
The other packages settled it differently from each other: `actions` puts component render tests in `tests/Integration/Components/`, `notifications` in `tests/Integration/Twig/Components/`.
Panels uses `tests/Integration/Twig/` — the templates are the whole Twig surface of this package, and there is no `Twig/Extension/` or class-backed component to sit beside them.
Panels-local convention, settled here so the next template test does not have to re-decide; it moves under a `Components/` subdirectory if panels ever grows a class-backed component.

### The drawer state class sits on the shell root, not on the sidebar

Filament toggles `fi-sidebar-open` on the `<aside>` itself, from Alpine's `$store.sidebar`, and the closed arm is a `&:not(.fi-sidebar-open)` on the same element.
Ours toggles `sy-pa-sidebar-open` on `.sy-pa-body` — the shell root — and both the drawer and its overlay read it as a descendant selector.
The substitution is what lets **one** class drive two elements that are not nested in each other: the overlay is a sibling of the sidebar, so v5 shows and hides it with `x-show` and a transition directive on the element instead, which a static template has no equivalent for.
The cost is that the open state is only readable from the root, so anything asking "is the drawer open?" from inside the sidebar has to walk up; the Stimulus controller does exactly that by holding the root as its controller element.

### One toggle button replaces v5's shown/hidden pair

Filament's topbar renders **two** icon buttons — a bars-3 opener under `x-show="! $store.sidebar.isOpen"` and an x-mark closer under `x-show="$store.sidebar.isOpen"`, each with its own icon alias ([`livewire/topbar.blade.php:17-46`](../../../vendor/filament/filament/resources/views/livewire/topbar.blade.php)).
Ours renders one `sy-pa-topbar-open-sidebar-btn` whose `toggle` action flips the state and whose `aria-expanded` the controller keeps in sync.
Two buttons swapped by a reactive expression is an Alpine shape; a static template would have to ship both and hide one with CSS, which buys a second glyph and a second alias for a control that already announces its state to assistive technology.
The visible delta is that the open drawer's control still shows a hamburger rather than an ×, and that `TOPBAR_CLOSE_SIDEBAR_BUTTON` has no port — it returns if the pair ever does.

### The theme switcher's dropdown is not teleported

Filament's user-menu dropdown is teleported to the end of `<body>`; ours renders in place, inside `.sy-pa-topbar-end`.
That is [the same decision the dropdown primitive already carries](../support/dropdown.md), and the topbar is the one place it could plausibly clip, because `.sy-pa-topbar-ctn` sets `overflow-x-clip`.
It does not, and the distinction is exactly why that property was chosen over `hidden`: `overflow-x: hidden` beside a `visible` block axis promotes the block axis to `auto`, making the bar a scroll container that clips downward, where `clip` leaves the other axis alone.
So the menu hangs below the bar untouched and only sideways overflow is trimmed — verified on the rendered menu rather than inferred.
Revisit if the topbar ever gains a vertical clip of its own.

### `USER_MENU_TRIGGER` is an addition, not a rename

Every other value in [`PanelsIconAlias`](#the-shell-components-render-the-navigation-model-and-the-chrome-as-static-twig) is a Filament constant with its `panels::` prefix dropped ([rename](../renames.md)).
This one has no Filament counterpart at all: v5's user-menu trigger is an `<x-filament::avatar>`, an image, so there is no icon slot to alias and its `USER_MENU_TOGGLE_BUTTON` names the *sidebar* variant's chevron instead.
Ours renders an `IconButton` holding `heroicons:user-circle` as a stand-in for the unported Avatar, sized to the avatar's 32px by a `user-menu.css` rule rather than the icon-button default's 20px, and the alias exists so an application can swap that stand-in without waiting for the avatar.
It is therefore surface a migrating user cannot have been using, and it goes away — or becomes an avatar setting — when ported auth brings a real user to render.
