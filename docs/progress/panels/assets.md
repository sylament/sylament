---
description: 'The @sylament/panels asset workspace: the sidebar and theme Stimulus controllers and the sy-pa-* shell stylesheets compiled into support.'
ports:
  - vendor/filament/filament/resources/js/stores/sidebar.js
  - vendor/filament/filament/resources/js/dark-mode.js
  - vendor/filament/filament/resources/js/scroll-sidebar.js
  - vendor/filament/filament/resources/js/error-notifications.js
  - vendor/filament/filament/resources/js/echo.js
  - vendor/filament/filament/resources/js/reset-restored-resource-create-record-pages.js
  - vendor/filament/filament/resources/css/base.css
  - vendor/filament/filament/resources/css/utilities.css
  - vendor/filament/filament/resources/css/components/layout.css
  - vendor/filament/filament/resources/css/components/logo.css
  - vendor/filament/filament/resources/css/components/sidebar.css
  - vendor/filament/filament/resources/css/components/topbar.css
  - vendor/filament/filament/resources/css/components/user-menu.css
  - vendor/filament/filament/resources/css/components/theme-switcher.css
  - vendor/filament/filament/resources/css/components/header.css
  - vendor/filament/filament/resources/css/components/page.css
  - vendor/filament/filament/resources/css/components/tenant-menu.css
owns:
  - packages/panels/assets/src/
---
# Assets (panels)

`packages/panels/assets` is the `@sylament/panels` pnpm workspace, JS-only in the same shape as `@sylament/notifications`: its Stimulus controllers build to its own `dist`, mapped into AssetMapper as `@sylament/panels`, while `src/styles/index.css` is imported by support's stylesheet and compiles into support's single built CSS.
The `global-search` and `unsaved-changes` controllers are [global-search.md](global-search.md)'s and [unsaved-changes-alert.md](unsaved-changes-alert.md)'s, and `sub-navigation.css` and `relation-manager.css` are [has-sub-navigation.md](has-sub-navigation.md)'s and [relation-manager.md](relation-manager.md)'s.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`sidebar` controller](../../../packages/panels/assets/src/controllers/sidebar_controller.ts) — the mobile drawer, the group collapse and its persistence | [`stores/sidebar.js`](../../../vendor/filament/filament/resources/js/stores/sidebar.js) | done | — | [semantics](#the-sidebar-controller-restores-only-groups-that-have-a-control-to-reopen-them) |
| The desktop collapse (the icon-only rail) and its width and tooltip state | [`stores/sidebar.js`](../../../vendor/filament/filament/resources/js/stores/sidebar.js) | todo | — | — |
| [`theme` controller](../../../packages/panels/assets/src/controllers/theme_controller.ts) with the document head script | [`dark-mode.js`](../../../vendor/filament/filament/resources/js/dark-mode.js) | done | — | [semantics](#the-theme-controller-owns-neither-the-dark-variant-nor-the-first-paint) · [diverges](#the-stored-theme-key-is-namespaced) |
| Restoring the sidebar scroll position | [`scroll-sidebar.js`](../../../vendor/filament/filament/resources/js/scroll-sidebar.js) | todo | — | — |
| Scrolling a validation error below the fold into view | [`error-notifications.js`](../../../vendor/filament/filament/resources/js/error-notifications.js) | todo | — | — |
| The broadcast client | [`echo.js`](../../../vendor/filament/filament/resources/js/echo.js) | blocked | the broadcast delivery arm | [notification.md](../notifications/notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| The restored create-page reset | [`reset-restored-resource-create-record-pages.js`](../../../vendor/filament/filament/resources/js/reset-restored-resource-create-record-pages.js) | skipped | — | [diverges](#the-restored-create-page-reset-has-nothing-to-reset) |
| [`layout.css`](../../../packages/panels/assets/src/styles/components/layout.css) — the shell root, its custom properties and the panel font | [`components/layout.css`](../../../vendor/filament/filament/resources/css/components/layout.css) | done | — | [semantics](#the-panels-font-is-inter-and-it-is-scoped-to-the-shell-root) |
| The skip link (`.sy-pa-skip-link` in `layout.css`) | [`utilities.css`](../../../vendor/filament/filament/resources/css/utilities.css) | done | — | — |
| [`sidebar.css`](../../../packages/panels/assets/src/styles/components/sidebar.css) | [`components/sidebar.css`](../../../vendor/filament/filament/resources/css/components/sidebar.css) | done | — | [semantics](#opening-the-drawer-dims-the-topbar-too) · [diverges](#the-active-sidebar-item-paints-a-fixed-primary-shade) |
| [`page.css`](../../../packages/panels/assets/src/styles/components/page.css) | [`components/page.css`](../../../vendor/filament/filament/resources/css/components/page.css) | done | — | [diverges](#the-pages-main-container-is-a-flex-column-where-v5-gates-that-on-a-full-height-page) |
| [`logo.css`](../../../packages/panels/assets/src/styles/components/logo.css), [`topbar.css`](../../../packages/panels/assets/src/styles/components/topbar.css), [`user-menu.css`](../../../packages/panels/assets/src/styles/components/user-menu.css), [`theme-switcher.css`](../../../packages/panels/assets/src/styles/components/theme-switcher.css), [`header.css`](../../../packages/panels/assets/src/styles/components/header.css) | the five same-named [`components/`](../../../vendor/filament/filament/resources/css/components) stylesheets | done | — | [header alignment](custom-pages.md#the-alignment-class-rides-the-inner-row-and-the-container-is-what-the-breadcrumb-offset-hangs-off) |
| `:root.dark { color-scheme: dark }`, in support's `base.css` | [`base.css`](../../../vendor/filament/filament/resources/css/base.css) | done | — | [semantics](#the-theme-controller-owns-neither-the-dark-variant-nor-the-first-paint) |
| The validation-error scroll margin under the topbar (`[data-field-wrapper] { scroll-margin-top }`) | [`base.css`](../../../vendor/filament/filament/resources/css/base.css) | todo | — | — |
| The tenant menu | [`components/tenant-menu.css`](../../../vendor/filament/filament/resources/css/components/tenant-menu.css) | blocked | tenancy | — |

## Semantics

### A consuming app that omits a controller block degrades silently

Every panels controller is declared `fetch: eager` in `package.json`, so the shell applies stored state on the first paint rather than after lazy loading catches up.
A consuming app that leaves a block out of its `assets/controllers.json` gets no error anywhere ([how a controller reaches an application](../../internals/assets.md#how-does-a-controller-reach-a-consuming-application)): without `sidebar` the drawer never opens and collapsed groups never restore, without `theme` the switcher does nothing, and without `global-search` the field renders and searches (`data-model` is what queries the server) but has no ⌘K binding, no Escape, no click-away and no arrow-key walk through the results.
The `unsaved-changes` block's omission is [its own entry](unsaved-changes-alert.md#an-app-that-omits-the-controller-block-loses-the-guard-silently).

### The sidebar controller restores only groups that have a control to reopen them

The controller owns the drawer pair (`toggle`, `close`, `closeOnEscape`), the per-group collapse toggle, and persistence under the localStorage key `sylament.panels.sidebar.collapsedGroups`, a JSON array of group labels read from `data-group-label`.
Every storage read and write is guarded, and an absent key, unavailable storage or an unparseable value all collapse to the same "no usable state" signal, in which case the server-rendered classes stand and the key is seeded from them, so a user who has never touched a group gets the markup's defaults instead of everything force-expanded.
The controller keeps `aria-expanded` in sync with the class it toggles, because the markup is static and nothing else updates it.
The whole restore and persist pass walks only groups holding a `.sy-pa-sidebar-group-btn[aria-expanded]`, so a non-collapsible group (same label class, no disclosure attributes; see [Layout templates](layout-templates.md)) is skipped everywhere: a stale localStorage entry can never collapse a group that has no control to reopen it.

### Opening the drawer dims the topbar too

The overlay is `z-30`, Filament's own value, and it comes after the topbar in the DOM, so it paints over it and the hamburger underneath stops being clickable, as in Filament.
That is what makes the close-on-click and close-on-Escape arms the only ways out.

### The theme controller owns neither the dark variant nor the first paint

The `theme` controller reads a stored choice of `light`, `dark` or `system` under [a namespaced key](#the-stored-theme-key-is-namespaced), toggles the `dark` class on `document.documentElement`, keeps each switcher button's `aria-pressed` and `sy-active` in sync, and re-renders when the OS preference changes only while the stored choice is `system`.
The `dark` variant is support's (`@variant dark (&:where(.dark, .dark *))`), and so is `:root.dark { color-scheme: dark }`, filed in support's `base.css`: panels ships no base-layer stylesheet, the toolkit compiles into one bundle either way, and the rule is correct on a non-panel page too.
The first paint is the document's: the head script in [Routing](routing.md) runs before the stylesheet, or the page flashes light before Stimulus boots.

### The panel's font is Inter, and it is scoped to the shell root

`.sy-pa-body` sets `font-(family-name:--sy-font)`, where `--sy-font` is a support-level token defaulting to `'Inter Variable', ui-sans-serif, system-ui, sans-serif`, so the face reaches every component inside the panel through inheritance; the scoped preflight drops `font-*` from its `html` rule for that reason ([styling.md](../../internals/styling.md#why-is-preflight-scoped-to-sylament-markup)), and nothing outside a panel is touched.
The same rule redefines Tailwind's own `--font-sans` to `var(--sy-font)` on that element: `sy-font-sans` compiles to `font-sans`, which reads `--font-sans`, so without the scoped override a component asking for the sans family inside a panel would opt out of the panel's face and back onto the system stack.
Filament closes the same gap by folding the panel font into `--font-sans` in its theme; ours is that fold, scoped to the shell root instead of the document.
The root also carries `--sy-sidebar-width`, the drawer's width, a [runtime token](../../internals/styling.md#why-are-per-instance-values-inline-variables) like every other `--sy-*` name.

## Divergences

### The active sidebar item paints a fixed `primary` shade

`sidebar.css` writes `text-primary-700 dark:text-primary-400` on the active item's icon and label rather than reading a `--sy-text` slot; the button itself carries no colour at all, only the `bg-gray-100 dark:bg-white/5` highlight.
Both halves are v5's own values ([`sidebar.css:157-179`](../../../vendor/filament/filament/resources/css/components/sidebar.css)), so the divergence is not the shade but the *mechanism*.
The *never hardcode a shade for a colour a user can set* rule ([why](../../internals/styling.md#why-does-a-policy-pick-the-shade-instead-of-the-stylesheet)) governs the **per-instance** `color` palette, which is chosen by a [contrast policy](../support/color-policies.md) because the palette is not known until render.
The sidebar has no per-instance colour API — neither `NavigationItem` nor `NavigationGroup` composes `HasColor`, and there is nothing to build a colour map from — so there is no policy to ask and no slot to read; `primary` resolves through the `--sy-primary-*` runtime tokens, so a consumer retheming the palette still gets their colour.
What would reverse it is the sidebar taking a colour: the shade then moves into a policy slot and the icon/label pair reads the slot variables.
The residual risk meanwhile: a consumer registering an unusually light or dark `primary` gets a fixed 700/400 against the fixed `gray-100` / `white/5` active background, which no policy is measuring.

### The page's main container is a flex column where v5 gates that on a full-height page

`page.css` gives `.sy-pa-page-main` an unconditional `flex flex-col`.
Filament gives `.fi-page-main` **no base rule at all**: it becomes a flex column only under `.fi-page-has-sub-navigation`, and gains `h-full` only under `.fi-height-full` ([`page.css`](../../../vendor/filament/filament/resources/css/components/page.css)).
The consequence is one declaration further in, and it is the reason to record this rather than treat it as a formatting difference: `.fi-page-content` carries `flex-1`, which is **inert** in vendor's default page because its parent is not a flex container — where ours is live, so a short page's content container grows to fill the wrapper instead of hugging its rows.
Nothing visible differs on the shipped pages, because each fills its content anyway.

It is a deliberate simplification, not a mistake: gating a rule on a class nothing emits would be dead CSS.
**Half of that gate is live.**
[Sub-navigation](has-sub-navigation.md) ships, so `sy-pa-page-has-sub-navigation` is emitted and carries the two rules the band needs — `gap-8`, and `md:flex-row md:items-start` under the start/end modifiers — and the band behaves identically to vendor's on both edges.
What stays unconditional here is the base `flex flex-col`, which vendor gates on the same class; that half, and the `fullHeight` half beside it, revisit **at the `fullHeight` port**, where vendor's own `h-full` cascade lands on the same three elements and the difference between "always a column" and "a column when asked" starts to matter.

### The stored theme key is namespaced

Filament persists the choice under the bare `localStorage['theme']`; ours uses `sylament.theme`, the same namespacing the sidebar controller applies to `sylament.panels.sidebar.collapsedGroups`.
A bare `theme` key is a collision waiting to happen in a host application that has its own dark mode — and unlike most collisions this one is invisible, because both sides would read a value that parses.
The cost is that a consumer migrating from Filament loses a visitor's stored preference once, at the switch.

### The restored create-page reset has nothing to reset

Filament's script resets `isCreating` on a create page that Livewire's SPA history cache restores, because a restored `true` would block a second submit; outside SPA mode Livewire's `no-store` header keeps the page out of the back/forward cache, so the case cannot arise.
The port has no SPA navigation and its [`CreateRecord`](create-record.md) carries no `isCreating` prop, so the script has no state to correct.
