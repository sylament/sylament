---
description: 'The @sylament/panels asset workspace: the shell Stimulus controllers and the sy-pa-* stylesheets.'
ports:
  - vendor/filament/filament/resources/js/
  - vendor/filament/filament/resources/css/
owns:
  - packages/panels/assets/src/
---
# Assets (panels)

the `@sylament/panels` workspace ships four Stimulus controllers (`sidebar`, `theme`, `global-search`, `unsaved-changes`) and the `sy-pa-*` rules, which compile into support's stylesheet — [Assets](#the-sylamentpanels-workspace-ships-the-stimulus-controllers-and-compiles-its-styles-into-support); `sub-navigation.css` and `page.css`'s band modifiers are [has-sub-navigation.md](has-sub-navigation.md#the-top-modifier-is-emitted-and-styled-by-nothing)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Assets (JS/CSS)](../../../packages/panels/assets) | [`resources/js/`](../../../vendor/filament/filament/resources/js), [`resources/css/`](../../../vendor/filament/filament/resources/css) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The `@sylament/panels` workspace ships the Stimulus controllers and compiles its styles into support

`packages/panels/assets` is the `@sylament/panels` pnpm workspace, JS-only in the same shape as `@sylament/notifications`: four Stimulus controllers build to its own `dist`, while `src/styles/index.css` is imported by support's stylesheet and compiles into support's single built CSS.
It carries `@symfony/ux-live-component` as a JS peer — in both `peerDependencies` and `devDependencies`, the workspace being `strictPeerDependencies: true` — which stays a bare specifier the consuming app's importmap resolves, exactly as in `@sylament/tables` and `@sylament/schemas`.
The built `dist` is mapped into AssetMapper as `@sylament/panels`.
The eleven style files carry the `sy-pa-*` rules mobile-first — off-canvas drawer below `lg`, two-column grid at `lg` and up — and the first eight are imported in **shell-nesting** order rather than alphabetically (`layout`, `logo`, `sidebar`, `topbar`, `user-menu`, `theme-switcher`, `header`, `page`), so the file reads outside-in the way the DOM nests.
`sub-navigation`, `global-search` and `relation-manager` are the exceptions: appended ninth, tenth and eleventh rather than sitting at their nesting positions.
Four of them are worth naming:

- `layout.css` owns `.sy-pa-body`, the shell root, and the two custom properties the shell reads off it: `--sy-sidebar-width` (the drawer's width, a [runtime token](../../internals/styling.md#why-are-per-instance-values-inline-variables) like every other `--sy-*` name — not one of the deliberately unprefixed emission-set variables the grid system uses, which are unprefixed only because they are Filament's verbatim) and `--font-sans`.
  It also carries the 26-rung [`sy-width-*` ladder](simple-page.md#the-ladder-shares-one-class-vocabulary-with-schemas-and-cannot-collide) nested under `.sy-pa-main, .sy-pa-simple-main`, and the four `.sy-pa-simple-*` shell rules.
- `page.css` is the [page skeleton's](routing.md) three rules — `py-8 gap-y-8` on the wrapper, a flex column on `sy-pa-page-main` ([a divergence](#the-pages-main-container-is-a-flex-column-where-v5-gates-that-on-a-full-height-page)), and `grid flex-1 auto-cols-fr gap-y-8` on `sy-pa-page-content` — plus `.sy-pa-form` (`flex flex-col gap-6`, Filament's `fi-sc-form`, which is what replaced the old actions-row `mt-6` and error-list `mb-6`), `.sy-pa-form-errors` (the record-level error list, reading the `danger` palette the way forms' per-field errors do) and `.sy-pa-simple-page-content`.
  There is no resource-form stylesheet: the record-level error rule is `.sy-pa-form-errors` here, and the form's actions row is schemas' own `sy-sc-actions` with no wrapper of its own.
- `header.css` is the page header's own rules, three groups among them: the two-level actions container ([why the alignment rides the inner row](custom-pages.md#the-alignment-class-rides-the-inner-row-and-the-container-is-what-the-breadcrumb-offset-hangs-off)), its seven `sy-align-*` arms, and the three `.sy-pa-simple-header-*` rules.
- `relation-manager.css` is the [manager's](relation-manager.md) three rules: the column that stacks its header over its table, the header row, and the heading's own type — the chrome Filament's `Table::heading()` would otherwise carry.

The active sidebar item paints from the [fixed `primary` palette](#the-active-sidebar-item-paints-a-fixed-primary-shade).

The third controller, `global-search`, arrived with [the topbar search](global-search.md), and where it binds is load-bearing: it rides the inner `.sy-pa-global-search` div rather than the live root above it, because that root's attribute bag already carries `data-controller="live"` and [`attrs()` replaces a repeated attribute rather than appending](global-search.md#the-stimulus-controller-rides-the-inner-element-not-the-live-root).
It is declared `fetch: eager` like the other two, and it shares their failure mode: a consuming app that leaves the block out of `assets/controllers.json` gets a field that renders and searches — `data-model` is what queries the server — but no ⌘K binding, no Escape, no click-away and no arrow-key walk through the results, with no error anywhere ([how a controller reaches an application](../../internals/assets.md#how-does-a-controller-reach-a-consuming-application)).

**The panel's font is Inter, and it is scoped.**
`.sy-pa-body` sets `font-(family-name:--sy-font)`, where `--sy-font` is a support-level token defaulting to `'Inter Variable', ui-sans-serif, system-ui, sans-serif` — so the face reaches every component inside the panel through inheritance, the scoped preflight having dropped `font-*` from its `html` rule for exactly that reason ([styling.md](../../internals/styling.md#why-is-preflight-scoped-to-sylament-markup)), and nothing outside a panel is touched.
The same rule also redefines Tailwind's own **`--font-sans`** to `var(--sy-font)` on that element: `sy-font-sans` compiles to `font-sans`, which reads `--font-sans`, so without the scoped override a component asking for the sans family inside a panel would opt *out* of the panel's face and back onto the system stack.
Filament closes the same gap by folding the panel font into `--font-sans` in its theme; ours is that fold, scoped to the shell root instead of the document.

The `sidebar` controller is declared `fetch: eager`, matching notifications: the shell must apply stored collapse state on the first paint rather than after lazy loading catches up.
It owns the drawer open/close pair (`toggle`, `close`, `closeOnEscape`), the per-group collapse toggle, and persistence under the localStorage key `sylament.panels.sidebar.collapsedGroups` (a JSON array of group labels read from `data-group-label`).

Opening the drawer dims the topbar along with the page.
The overlay is `z-30`, Filament's own value, and it comes after the topbar container in the DOM, so it paints over it and the hamburger underneath stops being clickable — which is also what v5 does, and what makes the close-on-click and close-on-Escape arms the only ways out.

The `theme` controller is the second, also `fetch: eager`.
It reads a stored choice of `light` / `dark` / `system` under [a namespaced key](#the-stored-theme-key-is-namespaced), toggles the `dark` class on `document.documentElement`, keeps each switcher button's `aria-pressed` and `sy-active` in sync, and — only while the stored choice is `system` — re-renders when the OS preference changes.
Two things it does not own.
The `dark` **variant** is support's (`@variant dark (&:where(.dark, .dark *))`), and so is `:root.dark { color-scheme: dark }`, filed in support's `base.css` rather than here: panels ships no base-layer stylesheet, the whole toolkit compiles into one bundle either way, and the rule is correct on a non-panel page too.
And the **first paint** is the document's, not the controller's — see the head script in [Routing](routing.md), which has to run before the stylesheet or the page flashes light before Stimulus boots.

Two behaviours worth knowing.
Every storage read and write is guarded, and an absent key, unavailable storage or an unparseable value all collapse to the same "no usable state" signal — in which case the server-rendered classes stand and the key is seeded from them, so a user who has never touched a group gets the markup's defaults instead of everything force-expanded.
And the controller keeps `aria-expanded` in sync with the class it toggles, since the markup is static and nothing else updates it — and the whole restore/persist pass, not just the aria sync, walks only groups matching `.sy-pa-sidebar-group-btn[aria-expanded]`, so a non-collapsible group (same label class, no disclosure attributes — see [Layout templates](layout-templates.md)) is deliberately skipped everywhere: a stale localStorage entry can never collapse a group that has no control to reopen it.

The fourth controller, `unsaved-changes`, belongs to the page tier and is the port of Filament's `unsaved-changes-alert.js`.
It binds the sentinel div the [page partial](routing.md) emits rather than the live root above it — the same constraint the `global-search` controller lives under, since [`attrs()` replaces a repeated attribute rather than appending](global-search.md#the-stimulus-controller-rides-the-inner-element-not-the-live-root) and a second `data-controller` there would take the live binding down.
It reads the host's state through `resolveLiveHost()` — `assets/src/utils/live_host.ts`, the third byte-identical copy of the helper schemas and tables carry, because schemas' `exports` map points at `src/`, which the split repo `export-ignore`s, and panels' `BUNDLED_ENTRIES` is empty, so the util ships per-file beside the controller and its logical path has to resolve on its own (which is what the second case in `SylamentPanelsBundleTest` pins).
It only ever **reads** — `getData()`, `valueStore.getDirtyProps()` and the `mountedActions` prop — and never `set()`s one: `savedDataHash` is not writable, so a write would surface as a `HydrationException` on the next request rather than as a client-side error.

Two of its mechanics are counter-intuitive enough to name here, both written up in [unsaved-changes-alert.md](unsaved-changes-alert.md).
The `savedHash` value is a **re-arm trigger, never the comparand** — nothing compares it to anything; a morph re-stamps the sentinel's attribute in place without reconnecting the controller, Stimulus fires `savedHashValueChanged()`, and the controller re-takes its own client-side baseline ([why the hash cannot be recomputed in the browser](unsaved-changes-alert.md#no-hash-is-ever-computed-in-the-browser-so-the-prop-is-a-marker)).
And a **bfcache restore does not reconnect the controller**, so the baseline taken before the page was frozen survives and the guard stays armed — navigating away from a restored page with unsaved edits still prompts, which is the opposite of the natural prediction and what the browser pass confirms ([the record](unsaved-changes-alert.md#a-bfcache-restore-keeps-the-guard-armed-rather-than-re-baselining)).

It is declared `fetch: eager` like the other three, and for a sharper reason than they have: a guard that arms late is absent exactly when it matters.
It shares their failure mode too — a consuming app that leaves the block out of `assets/controllers.json` navigates away from unsaved edits with no prompt and no error anywhere ([how a controller reaches an application](../../internals/assets.md#how-does-a-controller-reach-a-consuming-application)); the page itself still renders and still saves, so the omission is degraded, not broken.

What Filament's panel assets have and this does not, all todo:

- Desktop collapse (the icon-only rail) and its width/tooltip state — the controller tracks the mobile drawer only.
  The one sidebar setting a panel carries is `collapsibleNavigationGroups()`; the desktop-rail settings behind this are still unported.
- `scroll-sidebar.js` — restoring sidebar scroll position across navigations.
- `error-notifications.js` — the surface it serves exists (the [`CreateRecord`](create-record.md) / [`EditRecord`](edit-record.md) forms, error outlet included), but the script is not ported, so a validation failure below the fold is silent.
  Its sibling `unsaved-changes-alert.js` **is** ported, as the `unsaved-changes` controller above, minus Filament's SPA arm: that one hangs off `Livewire.navigate`, which has no counterpart here.
- `echo.js` and the resource/page scripts — none of the surfaces they serve exist yet.
  `dark-mode.js` is the one that now has a counterpart: ours is the `theme` controller plus the document head script, and what is still missing is the panel-level configuration around it (`hasThemeSwitcher()`, `hasDarkModeForced()`, `getDefaultThemeMode()`), so the switcher is unconditional.

One fixture trap the mobile-drawer work left behind, recorded so the error is recognisable rather than mysterious: panels' test fixture icon set carries the single `chevron-left-20-solid` / `chevron-right-20-solid` pair (added with the breadcrumb separators) but **not** the `chevron-double-*-20-solid` pair, and the `ux_icons` `ignore_not_found` default is `false`.
A panels test that ever renders a **multi-page** table therefore throws `IconNotFoundException` on [`Pagination`](../support/pagination.md)'s first/last buttons.
The fix is one `cp` from tables' fixture directory; the resource list page paginates to a single page today, which is why nothing hits it.

## Divergences

### The active sidebar item paints a fixed `primary` shade

`sidebar.css` writes `text-primary-700 dark:text-primary-400` on the active item's icon and label rather than reading a `--sy-text` slot; the button itself carries no colour at all, only the `bg-gray-100 dark:bg-white/5` highlight.
Both halves are v5's own values ([`sidebar.css:157-179`](../../../vendor/filament/filament/resources/css/components/sidebar.css)), so the divergence is not the shade but the *mechanism*.
The *never hardcode a shade for a colour a user can set* rule ([why](../../internals/styling.md#why-does-a-policy-pick-the-shade-instead-of-the-stylesheet)) governs the **per-instance** `color` palette, which is chosen by a [contrast policy](../support/color-policies.md) because the palette is not known until render.
The sidebar has no per-instance colour API — neither `NavigationItem` nor `NavigationGroup` composes `HasColor`, and there is nothing to build a colour map from — so there is no policy to ask and no slot to read; `primary` resolves through the `--sy-primary-*` runtime tokens, so a consumer retheming the palette still gets their colour.
It becomes a rule-6 case the day the sidebar takes a colour, at which point the shade moves into a policy slot and the icon/label pair reads the slot variables instead.
The known residual risk until then: a consumer registering an unusually light or dark `primary` gets a fixed 700/400 against the fixed `gray-100` / `white/5` active background, which no policy is measuring.

### The page's main container is a flex column where v5 gates that on a full-height page

`page.css` gives `.sy-pa-page-main` an unconditional `flex flex-col`.
Filament gives `.fi-page-main` **no base rule at all**: it becomes a flex column only under `.fi-page-has-sub-navigation`, and gains `h-full` only under `.fi-height-full` ([`page.css`](../../../vendor/filament/filament/resources/css/components/page.css)).
The consequence is one declaration further in, and it is the reason to record this rather than treat it as a formatting difference: `.fi-page-content` carries `flex-1`, which is **inert** in vendor's default page because its parent is not a flex container — where ours is live, so a short page's content container grows to fill the wrapper instead of hugging its rows.
Nothing visible differs today, because every page we ship fills its content anyway.

It is a deliberate simplification, not a mistake: gating a rule on a class nothing emits would be dead CSS.
**Half of that gate is live.**
[Sub-navigation](has-sub-navigation.md) ships, so `sy-pa-page-has-sub-navigation` is emitted and carries the two rules the band needs — `gap-8`, and `md:flex-row md:items-start` under the start/end modifiers — and the band behaves identically to vendor's on both edges.
What stays unconditional here is the base `flex flex-col`, which vendor gates on the same class; that half, and the `fullHeight` half beside it, revisit **at the `fullHeight` port**, where vendor's own `h-full` cascade lands on the same three elements and the difference between "always a column" and "a column when asked" starts to matter.

### The stored theme key is namespaced

Filament persists the choice under the bare `localStorage['theme']`; ours uses `sylament.theme`, the same namespacing the sidebar controller already applies to `sylament.panels.sidebar.collapsedGroups`.
A bare `theme` key is a collision waiting to happen in a host application that has its own dark mode — and unlike most collisions this one is invisible, since both sides would read a value that parses.
The cost is that a consumer migrating from Filament loses a visitor's stored preference once, at the switch.
