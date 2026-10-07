---
description: 'The dashboard, the one concrete page the package ships, mounted at the panel root.'
ports:
  - vendor/filament/filament/src/Pages/Dashboard.php
owns:
  - packages/panels/src/Pages/Dashboard.php
---
# Dashboard (panels)

`Sylament\Panels\Pages\Dashboard` extends the panel [`Page`](page.md) and is registered as a service by the bundle itself.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$routePath`](../../../packages/panels/src/Pages/Dashboard.php) / `getRoutePath()` | [`Dashboard::getRoutePath()`](../../../vendor/filament/filament/src/Pages/Dashboard.php) | done | — | — |
| [`$navigationSort`](../../../packages/panels/src/Pages/Dashboard.php) | the same static, `-2` | done | — | — |
| [`$title`](../../../packages/panels/src/Pages/Dashboard.php), read by the inherited `getTitle()` and `getNavigationLabel()` | `Dashboard::getTitle()` / `getNavigationLabel()` | done | — | [diverges](#the-dashboard-title-is-a-literal-not-a-translation-key) |
| [`getNavigationIcon()`](../../../packages/panels/src/Pages/Dashboard.php) | `Dashboard::getNavigationIcon()` | done | — | [diverges](#the-icon-fallback-has-no-top-navigation-arm) |
| `getWidgets()` / `getColumns()` / `content()` / `getFiltersFormContentComponent()` / `getWidgetsContentComponent()` | [`Pages/Dashboard.php`](../../../vendor/filament/filament/src/Pages/Dashboard.php) | blocked | a `Widget` class in `sylament/widgets` | [detail](#the-widget-half-waits-on-sylamentwidgets) |
| `getVisibleWidgets()` | `Dashboard::getVisibleWidgets()`, deprecated | skipped | — | [diverges](#the-deprecated-getvisiblewidgets-is-not-ported) |

## Details

### The widget half waits on `sylament/widgets`

`sylament/widgets` is a placeholder bundle with no `Widget` class, and every remaining member reads widgets: `getWidgets()` lists them, `getColumns()` sizes their grid, `getWidgetsContentComponent()` builds that grid and `content()` places it.
`getFiltersFormContentComponent()` only needs schemas' `EmbeddedSchema`, which exists, but it serves a filters form that exists to scope the widgets beside it, so it lands with them.

## Divergences

### The dashboard title is a literal, not a translation key

Filament's `Dashboard` resolves its label through `__('filament-panels::pages/dashboard.title')`; ours sets `protected static ?string $title = 'Dashboard'`, and the inherited `Page::getTitle()` and `Page::getNavigationLabel()` both read the literal.
The port has no translation layer, so a key here would be the only one in the codebase and would have to invent the catalogue conventions as a side effect of shipping a dashboard ([the deferred i18n pass](../../internals/i18n.md#translation-is-one-deferred-pass-not-a-per-component-port)).
When that pass lands, the literal becomes a translated string with `'Dashboard'` as its English default, and a consumer overriding `$title` is unaffected either way.

### The icon fallback has no top-navigation arm

Filament's fallback picks `Heroicon::Home` when the panel has top navigation and `Heroicon::OutlinedHome` otherwise.
Ours resolves the single `heroicons:home` name, the outline icon, because the port has no top-navigation layout.
`getNavigationIcon()` returns a `Closure` that resolves [`PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM`](../../../packages/panels/src/Twig/PanelsIconAlias.php) through an injected `IconManager` before it falls back, so the lookup runs at render time and an alias registered after the page class loads still wins.

### The deprecated `getVisibleWidgets()` is not ported

Filament deprecates it in favour of `getWidgetsSchemaComponents($this->getWidgets())`, which also filters the widgets' visibility, so it lands as that call with the rest of the widget half.
