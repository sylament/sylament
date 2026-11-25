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
| [`getTitle()` and `getNavigationLabel()`](../../../packages/panels/src/Pages/Dashboard.php), with `Page`'s inherited `$title` as the override | `Dashboard::getTitle()` / `getNavigationLabel()` | done | — | [diverges](#the-dashboard-title-is-read-from-the-catalogue-and-a-title-override-still-wins) |
| [`getNavigationIcon()`](../../../packages/panels/src/Pages/Dashboard.php) | `Dashboard::getNavigationIcon()` | done | — | [diverges](#the-icon-fallback-has-no-top-navigation-arm) |
| `getWidgets()` / `getColumns()` / `content()` / `getFiltersFormContentComponent()` / `getWidgetsContentComponent()` | [`Pages/Dashboard.php`](../../../vendor/filament/filament/src/Pages/Dashboard.php) | blocked | a `Widget` class in `sylament/widgets` | [detail](#the-widget-half-waits-on-sylamentwidgets) |
| `getVisibleWidgets()` | `Dashboard::getVisibleWidgets()`, deprecated | skipped | — | [diverges](#the-deprecated-getvisiblewidgets-is-not-ported) |

## Details

### The widget half waits on `sylament/widgets`

`sylament/widgets` is a placeholder bundle with no `Widget` class, and every remaining member reads widgets: `getWidgets()` lists them, `getColumns()` sizes their grid, `getWidgetsContentComponent()` builds that grid and `content()` places it.
`getFiltersFormContentComponent()` only needs schemas' `EmbeddedSchema`, which exists, but it serves a filters form that exists to scope the widgets beside it, so it lands with them.

## Divergences

### The dashboard title is read from the catalogue, and a `$title` override still wins

Filament's `Dashboard` resolves its label through `__('filament-panels::pages/dashboard.title')`; ours reads Filament's key path in the catalogue's shape, `pages.dashboard.title`, from the `sylament-panels` catalogue.
The class sets no `$title`, because a static property initialiser cannot hold a message, and overrides two getters instead: the instance `getTitle()` returns the translated string, and the static `getNavigationLabel()` returns the message itself for [the navigation item to translate](page.md#a-static-getter-returns-a-message-and-the-item-that-reads-it-translates-it).
A subclass that sets `$title` wins in both and keeps a plain string.

### The icon fallback has no top-navigation arm

Filament's fallback picks `Heroicon::Home` when the panel has top navigation and `Heroicon::OutlinedHome` otherwise.
Ours resolves the single `heroicons:home` name, the outline icon, because the port has no top-navigation layout.
`getNavigationIcon()` returns a `Closure` that resolves [`PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM`](../../../packages/panels/src/Twig/PanelsIconAlias.php) through an injected `IconManager` before it falls back, so the lookup runs at render time and an alias registered after the page class loads still wins.

### The deprecated `getVisibleWidgets()` is not ported

Filament deprecates it in favour of `getWidgetsSchemaComponents($this->getWidgets())`, which also filters the widgets' visibility, so it lands as that call with the rest of the widget half.
