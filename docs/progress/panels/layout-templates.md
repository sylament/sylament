---
description: 'The panel shell''s Twig components (layout, topbar, sidebar, logo, theme switcher, user menu) and the panel icon aliases.'
ports:
  - vendor/filament/filament/resources/views/components/layout/index.blade.php
  - vendor/filament/filament/resources/views/components/logo.blade.php
  - vendor/filament/filament/resources/views/components/sidebar/group.blade.php
  - vendor/filament/filament/resources/views/components/sidebar/item.blade.php
  - vendor/filament/filament/resources/views/components/theme-switcher/
  - vendor/filament/filament/resources/views/components/user-menu.blade.php
  - vendor/filament/filament/resources/views/livewire/sidebar.blade.php
  - vendor/filament/filament/resources/views/livewire/topbar.blade.php
  - vendor/filament/filament/src/View/PanelsIconAlias.php
owns:
  - packages/panels/src/Twig/PanelsIconAlias.php
  - packages/panels/templates/components/
---
# Layout templates (panels)

The shell components render the navigation model and the chrome as static Twig; the page's own live component renders inside `Layout`'s content block.
The simple shell (`SimpleLayout`, `SimpleHeader`) is [simple-page.md](simple-page.md)'s, the page header is [custom-pages.md](custom-pages.md)'s, and the band is [has-sub-navigation.md](has-sub-navigation.md)'s.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Layout`](../../../packages/panels/templates/components/Layout/index.html.twig) — the shell root, skip link, overlay and `<main>` | [`layout/index.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/index.blade.php) | done | — | [simple-page.md](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller) · [custom-pages.md](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) |
| [`Sidebar`](../../../packages/panels/templates/components/Sidebar/index.html.twig) | [`livewire/sidebar.blade.php`](../../../vendor/filament/filament/resources/views/livewire/sidebar.blade.php) | done | — | [diverges](#the-shell-is-not-live) |
| [`SidebarGroup`](../../../packages/panels/templates/components/SidebarGroup/index.html.twig) | [`sidebar/group.blade.php`](../../../vendor/filament/filament/resources/views/components/sidebar/group.blade.php) | done | — | [semantics](#a-sidebar-groups-label-is-a-disclosure-only-when-the-group-is-collapsible) · [diverges](#sidebar-group-ids-are-positional-not-derived-from-the-label) |
| [`SidebarItem`](../../../packages/panels/templates/components/SidebarItem/index.html.twig) | [`sidebar/item.blade.php`](../../../vendor/filament/filament/resources/views/components/sidebar/item.blade.php) | done | — | — |
| [`Topbar`](../../../packages/panels/templates/components/Topbar/index.html.twig) | [`livewire/topbar.blade.php`](../../../vendor/filament/filament/resources/views/livewire/topbar.blade.php) | done | — | [diverges](#the-shell-is-not-live) · [diverges](#one-toggle-button-replaces-v5s-shownhidden-pair) |
| The mobile drawer's open state | `$store.sidebar` and `fi-sidebar-open` | done | — | [diverges](#the-drawer-state-class-sits-on-the-shell-root-not-on-the-sidebar) |
| [`Logo`](../../../packages/panels/templates/components/Logo/index.html.twig) | [`logo.blade.php`](../../../vendor/filament/filament/resources/views/components/logo.blade.php) | done | — | [diverges](#the-brand-settings-thread-down-as-props-where-filaments-blades-read-the-facade) |
| [`ThemeSwitcher`](../../../packages/panels/templates/components/ThemeSwitcher/index.html.twig) | [`theme-switcher/`](../../../vendor/filament/filament/resources/views/components/theme-switcher/index.blade.php) | done | — | [semantics](#the-theme-switchers-pressed-state-is-client-only-and-survives-only-because-the-shell-is-outside-the-live-root) · [semantics](#the-theme-switchers-three-buttons-are-written-out-not-looped) |
| [`UserMenu`](../../../packages/panels/templates/components/UserMenu/index.html.twig) — the dropdown holding the theme switcher | [`user-menu.blade.php`](../../../vendor/filament/filament/resources/views/components/user-menu.blade.php) | done | — | [diverges](#the-theme-switchers-dropdown-is-not-teleported) |
| The user menu's account half — the user name, avatar, `getUserMenuItems()` and the topbar's `auth()->check()` gate | same | blocked | auth | [detail](#the-user-menu-is-a-stub-until-auth-is-ported) |
| The panel's theme toggles — `hasDarkMode()`, `hasDarkModeForced()`, `hasThemeSwitcher()`, `getDefaultThemeMode()` | the gates in `user-menu.blade.php` and `theme-switcher/index.blade.php` | todo | — | [detail](#the-user-menu-is-a-stub-until-auth-is-ported) |
| [`PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) — the constants the shell, the dashboard, global search, the band and the related-records page read | [`View/PanelsIconAlias.php`](../../../vendor/filament/filament/src/View/PanelsIconAlias.php) | done | — | [semantics](#the-shells-icons-are-overridable-aliases) |
| `PanelsIconAlias::USER_MENU_TRIGGER` | no counterpart | done | — | [diverges](#user_menu_trigger-is-an-addition-not-a-rename) |
| `TOPBAR_CLOSE_SIDEBAR_BUTTON` | the same | skipped | — | [diverges](#one-toggle-button-replaces-v5s-shownhidden-pair) |
| Desktop collapse — the icon-only rail and its item tooltips | `sidebarCollapsibleOnDesktop()` / `sidebarFullyCollapsibleOnDesktop()` and their templates | todo | — | — |
| Child items under an active sidebar item | `sidebar/item.blade.php`'s `childItems` arm | blocked | [`NavigationItem`'s nesting arm](navigation-item.md#the-nesting-arm-waits-on-key) | — |
| The database-notifications triggers | `sidebar/` and `topbar/database-notifications-trigger.blade.php` | blocked | database notifications | — |
| The user and tenant avatars | `avatar/user.blade.php` / `avatar/tenant.blade.php` | blocked | auth | — |
| The tenant menu | `tenant-menu.blade.php` | blocked | tenancy | — |
| Top navigation | `topbar/item.blade.php` and the panel's `topNavigation()` | todo | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The user menu is a stub until auth is ported

`UserMenu` is a support `Dropdown` whose trigger is an `IconButton` and whose panel holds only the theme switcher.
There is no user name, no avatar, no menu items and no `getUserMenuItems()`, and none of Filament's gates around it: the topbar's `auth()->check()`, and the `hasDarkMode() && ! hasDarkModeForced() && hasThemeSwitcher()` test in front of the switcher — so the menu and its switcher render unconditionally, for an anonymous visitor too.
The theme switcher starts from no panel default either: Filament seeds it with `getDefaultThemeMode()`, which the port has no panel setting for.
The account half lands with auth; the theme toggles are panel settings that need nothing else.

## Semantics

### A sidebar group's label is a disclosure only when the group is collapsible

`SidebarGroup` picks the tag (`{% set labelTag = collapsible ? 'button' : 'div' %}`) and nulls `type`, `aria-expanded`, `aria-controls` and the `sylament--panels--sidebar#toggleGroup` action on the non-collapsible arm, so a static group renders a plain labelled `<div>` with no disclosure semantics and no control to operate; only the collapsible arm gets the button, the `aria-expanded`/`aria-controls` pair pointing at `sy-pa-sidebar-group-items-<index>`, and the chevron icon.
Collapse state is markup only — the `sy-collapsed` class plus `data-group-label`, which the Stimulus controller reads and writes.

The chevron points **up** when the group is open and flips through `-rotate-180` when it collapses, which is Filament's pairing (`Heroicon::ChevronUp` plus `.fi-collapsed .fi-sidebar-group-collapse-btn { -rotate-180 }`).
A 180° turn is direction-symmetric, so the rule carries no `rtl:` arm.

### The shell's icons are overridable aliases

The group chevron, the topbar hamburger, the three theme-switcher glyphs and the user-menu trigger all read [`PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) constants through `constant()` the way [Modal's close button](../../../packages/support/templates/components/Modal/index.html.twig) does, so an app swaps any of them under [`sylament.icons`](../support/icon-manager.md) without touching a template.
Every value drops Filament's `panels::` prefix ([rename](../renames.md)); the constant names are Filament's, except `USER_MENU_TRIGGER`, which Filament has no equivalent for.

### The theme switcher's three buttons are written out, not looped

The switcher is a `role="group"` of three buttons — light, dark, system — each carrying its `data-theme` value, an `aria-pressed` the controller keeps in sync, and its own overridable icon alias.
The buttons are written out rather than looped, because a computed `constant('…THEME_SWITCHER_' ~ theme|upper ~ '_BUTTON')` would be invisible to Twig-CS-Fixer's `ValidConstantFunctionRule`, the one check in the toolchain that validates a hardcoded FQCN string, and Filament unrolls the same three too.
Each button also fires `sylament--support--dropdown#close` beside the theme action, so picking a theme dismisses the menu it sits in — Filament's `(theme = …) && close()` in one attribute.
Rendered outside a dropdown the extra action is inert, Stimulus ignoring an action whose controller is on no ancestor.

### The theme switcher's pressed state is client-only, and survives only because the shell is outside the live root

`ThemeSwitcher` renders all three buttons with `aria-pressed="false"` and no `sy-active`; the controller stamps the real state on `connect()` and on every pick.
The server never learns the choice — it is a `localStorage` value the document head script and the controller both read — so the markup Twig produces is deliberately *wrong* until JS runs, which is fine on a page load and would not be fine inside a re-rendering fragment: ux-live-component morphs a component's subtree from freshly rendered HTML, so a switcher inside a live root would have its `aria-pressed` and `sy-active` reset on every round trip while the actual theme, living on `<html>`, stayed put.

Panel pages are safe by construction and not by care: [the shell is not live](#the-shell-is-not-live), and the [document](routing.md) puts the page's live component *inside* `Layout`'s content block, so the topbar — switcher included — sits outside every live root on the page.
The arrangement that would break it is the mirror image: embedding `Layout` **inside** a live component, which is what the demo's `PanelShellCard` does to show the shell as a showcase card.
It works there because the card does not re-render; a card that did would watch its switcher's aria state go stale.

### The shell templates' render tests live under `tests/Integration/Twig/`

The repo convention is to mirror `src/` inside each test bucket, but the anonymous shell templates have no `src/` counterpart — they live in `packages/panels/templates/` and are rendered by name.
The other packages settled it differently from each other: `actions` puts component render tests in `tests/Integration/Components/`, `notifications` in `tests/Integration/Twig/Components/`.
Panels renders its templates from `tests/Integration/Twig/`, and the one class-backed component, `Twig\Components\GlobalSearch`, is tested from `tests/Integration/Twig/Components/`, which mirrors its `src/` path.

## Divergences

### The shell is Turbo-indifferent and worker-safe

Two constraints the shell is built under, cheap to hold while the shell is small and expensive to retrofit once pages, panels and widgets hang off them.

**Turbo-indifferent.**
Every behaviour hangs off Stimulus `connect()` — never `DOMContentLoaded`, never `turbo:load`, and never an inline `<script>`.
A handler bound to first paint is dead in a Turbo-driven app from the second navigation on, and an inline script is both a CSP liability and unreachable from a re-rendered fragment.
The landmark ids the shell emits are stable — `sy-pa-sidebar`, `sy-pa-topbar`, `sy-pa-main` — so a swapped body reconnects to the same targets and the skip link keeps working.
Sidebar collapse state is restored from `localStorage` on `connect()` rather than preserved with `data-turbo-permanent`: the markup can be replaced wholesale and the state still survives, which `data-turbo-permanent` would only manage for the exact subtree it guards.

**Worker-safe.**
No mutable static per-request state anywhere in the shell.
A long-running worker (FrankenPHP, RoadRunner, Swoole) reuses the PHP process across requests, so anything static that remembers *this* request leaks into the next one — a class of bug that never shows up under php-fpm and is close to undebuggable in production.
Request awareness reaches the navigation model through evaluated closures instead — `isActiveWhen(fn (RequestStack $requests) => …)` — whose services [the item hands in itself](navigation-item.md#the-item-hands-its-own-services-to-evaluated-closures), and the current-panel lookup is derived from the request the same way, never held in a stateful singleton ([the lookup](panel.md#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager)).

### The shell is not live

Filament v5's sidebar and topbar are Livewire components.
Ours are anonymous Twig components rendering a plain PHP navigation model, because the shell itself needs no server round-trip: navigation is a set of links, collapse state is client-side, and the interactive parts (the mobile drawer, per-group collapse, the theme switcher) are markup plus the sidebar and theme Stimulus controllers.
A live shell would buy re-rendering on the server and cost a component round-trip on every page.

[Global search](global-search.md) is the one part of the topbar that asks the server between navigations — a query in, matching records out — so the topbar carries one live root.
It is a **sibling** of the static shell rather than a wrapper around it: `Topbar` stays anonymous and renders `<twig:SylamentPanels:GlobalSearch />` inside `sy-pa-topbar-end`, which is where the live boundary begins and ends.
So the round trip is paid by the one element that needs it, and everything else in the bar renders once per page.
That component registers through its own `#[AsLiveComponent(name:, template:)]` — panels' pages are live too, but each carries a bare attribute and is registered by [a compiler pass](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute).

Revisit the rest when something else in the shell must change without a navigation — the database-backed notifications trigger is the next candidate, because an unread count in the topbar is a server fact that changes between page loads.
Until then the divergence is a simplification, not a gap.

### Sidebar group ids are positional, not derived from the label

Filament's `group.blade.php` builds `fi-sidebar-group-items-<slug>-<md5 prefix>` from the group label, so the `aria-controls` target is stable per label and unique across everything on the page.
Ours is `sy-pa-sidebar-group-items-<loop index>`, assigned by `Sidebar` from the render loop.
It is stable per render and needs no label at all (a group may have none), and it rests on the shell's **single-sidebar-per-page** assumption — the same assumption the singleton ids `sy-pa-sidebar`, `sy-pa-topbar` and `sy-pa-main` already encode.
Revisit the moment two sidebars can render on one page: groups at the same index would then collide, pointing one group's button at another's item list.
The label-hash approach is the fix if that day comes — noting that collapse *persistence* is keyed the other way, on `data-group-label`, so two groups sharing a label share their stored collapse state and a renamed group loses what it had stored, which is inherent to keying on the label and a property Filament's label-slug ids carry too.

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
So the menu hangs below the bar untouched and only sideways overflow is trimmed.
Revisit if the topbar ever gains a vertical clip of its own.

### The brand settings thread down as props where Filament's blades read the facade

`brandName`, `brandLogo`, `darkModeBrandLogo` and `brandLogoHeight` ride as props down the `Document` → `Layout` → `Topbar`/`Sidebar` → `Logo` chain, where Filament's `logo.blade.php` reads the `filament()` facade in place.
`Logo` is rendered twice — in the topbar start and in the sidebar's own mobile header — and keeps `logo.blade.php`'s three arms: a `Markup` logo renders raw inside a `<div class="sy-pa-logo">` (Blade's `$logo instanceof Htmlable`, through support's `markup` Twig test), a string one as an `<img>`, and with neither the brand name falls back as a `<span>`, rendering nothing at all when the panel has no brand name either.
The `<img>` alt is the brand name plus ` logo`, a literal where Filament reads the `layout.logo.alt` translation, while i18n is parked.
When `darkModeBrandLogo()` is set both renditions render, `sy-pa-logo-light` and `sy-pa-logo-dark` — Filament's `fi-logo-light`/`fi-logo-dark` pair, CSS-toggled.

### `USER_MENU_TRIGGER` is an addition, not a rename

Every other value in [`PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) is a Filament constant with its `panels::` prefix dropped ([rename](../renames.md)).
This one has no Filament counterpart at all: v5's user-menu trigger is an `<x-filament::avatar>`, an image, so there is no icon slot to alias and its `USER_MENU_TOGGLE_BUTTON` names the *sidebar* variant's chevron instead.
Ours renders an `IconButton` holding `heroicons:user-circle` as a stand-in for the unported Avatar, sized to the avatar's 32px by a `user-menu.css` rule rather than the icon-button default's 20px, and the alias exists so an application can swap that stand-in without waiting for the avatar.
It is therefore surface a migrating user cannot have been using, and it goes away — or becomes an avatar setting — when ported auth brings a real user to render.
