---
description: 'The one concrete page the package ships, mounted at the panel root.'
ports:
  - vendor/filament/filament/src/Pages/Dashboard.php
owns:
  - packages/panels/src/Pages/Dashboard.php
---
# Dashboard (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Pages\Dashboard`](../../../packages/panels/src/Pages/Dashboard.php) | [`Pages/Dashboard.php`](../../../vendor/filament/filament/src/Pages/Dashboard.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The dashboard is the one concrete page the package ships

The one concrete page the package ships: `$routePath = '/'` (overriding `getRoutePath()`, so the page owns the panel root rather than `/dashboard`), `$navigationSort = -2` to keep it first, and `$title = 'Dashboard'`, which `Page::getTitle()` and `getNavigationLabel()` both read — [a literal where Filament translates](#the-dashboard-title-is-a-literal-not-a-translation-key).
`getNavigationIcon()` returns a `Closure` resolving [`PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM`](../../../packages/panels/src/Twig/PanelsIconAlias.php) through the injected `IconManager`, falling back to `heroicons:home`; the closure defers the lookup to render time, which is what lets an override registered after the page class loads still win.

Deferred, all todo: the widgets half (`getWidgets()`, `getColumns()`, `content()`, the widget schema components) — none of it can land before `sylament/widgets` exists.
Filament's icon fallback also picks between two heroicon cases on `hasTopNavigation()` — `Heroicon::Home` for top navigation and `Heroicon::OutlinedHome` otherwise; ours resolves the single `heroicons:home` name, which *is* the outline arm, since the port has no top-navigation layout to pick the mini one for.

## Divergences

### The dashboard title is a literal, not a translation key

Filament's `Dashboard` resolves its label through `__('filament-panels::pages/dashboard.title')`; ours sets `protected static ?string $title = 'Dashboard'` and both `getTitle()` and `getNavigationLabel()` read the literal.
There is no translation layer anywhere in the port yet — no bundle ships a message catalogue, no template calls `trans`, and no user-facing string carries a message id — so a key here would be the only one in the codebase and would have to invent the catalogue conventions (domain naming, where catalogues live, how a consumer overrides one) as a side effect of shipping a dashboard.
The literal is a placeholder with a known replacement: when i18n lands it becomes a translated string with `'Dashboard'` as its English default, and a consumer overriding `$title` today is unaffected either way.
