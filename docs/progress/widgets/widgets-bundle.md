---
description: The widgets bundle class and the package's stylesheet entry point.
ports:
  - vendor/filament/widgets/src/WidgetsServiceProvider.php
  - vendor/filament/widgets/src/Commands/MakeWidgetCommand.php
  - vendor/filament/widgets/resources/css/index.css
owns:
  - packages/widgets/assets/src/styles/index.css
  - packages/widgets/src/SylamentWidgetsBundle.php
---
# SylamentWidgetsBundle (widgets)

[`Sylament\Widgets\SylamentWidgetsBundle`](../../../packages/widgets/src/SylamentWidgetsBundle.php) ports Filament's [`WidgetsServiceProvider`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) as a subclass of support's [`SylamentBundle`](../support/support-bundle.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle class](../../../packages/widgets/src/SylamentWidgetsBundle.php) | [`WidgetsServiceProvider.php`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | done | — | — |
| [Stylesheet entry point](../../../packages/widgets/assets/src/styles/index.css) | [`resources/css/index.css`](../../../vendor/filament/widgets/resources/css/index.css) | in progress | — | [detail](#the-stylesheet-entry-point-imports-no-widget-sheet) |
| `@SylamentWidgets` template namespace | [`->hasViews()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | [detail](#there-is-no-template-namespace-because-the-package-has-no-templates-directory) |
| Asset registration (the `chart`, `js-widget` and `stats-overview/stat/chart` Alpine components) | [`packageBooted()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | [detail](#the-chart-component-gives-way-to-symfonyux-chartjs) |
| `MakeWidgetCommand` + stub publishing (`packageBooted()`) | [`Commands/MakeWidgetCommand.php`](../../../vendor/filament/widgets/src/Commands/MakeWidgetCommand.php) | todo | — | — |
| Translations (`->hasTranslations()`) | [`configurePackage()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | [detail](#the-catalogue-arrives-with-the-first-component-that-renders-text) |
| The `Livewire::listen('hydrate', …)` listener that boots a lazy widget's authorization | [`packageBooted()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | skipped | — | [diverges](../actions/actions-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### The stylesheet entry point imports no widget sheet

Filament's `index.css` imports the chart, stats-overview and base widget sheets under `layer(components)`.
The port's file holds only a comment, because no widget exists to style.
Support's `index.css` imports it, so a sheet imported here reaches the built stylesheet with no further wiring.

### There is no template namespace, because the package has no `templates/` directory

Filament registers its view namespace with `->hasViews()`.
The port relies on TwigBundle's convention instead ([schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call)), and that convention maps a bundle's `templates/` directory to its namespace only when the directory exists.
`packages/widgets/` has no `templates/` directory, so `@SylamentWidgets` resolves nothing.

### The chart component gives way to `symfony/ux-chartjs`

Filament registers three Alpine components from its prebuilt `dist/`: `chart`, which `ChartWidget` mounts, `js-widget`, which a widget rendered through `HasJsRenderer` mounts, and `stats-overview/stat/chart`, a stat's sparkline.
The `chart` component has no port of its own to register, because a ported `ChartWidget` would render through `symfony/ux-chartjs`, a pre-approved Symfony package, rather than through a port of Filament's Chart.js wiring.
The sparkline component follows the stats-overview widget, and `js-widget` waits on a JS-rendered widget.
No manifest requires `symfony/ux-chartjs`, because no chart widget exists.

### The catalogue arrives with the first component that renders text

Filament's `resources/lang` for the package is unported, because no widget component renders a text yet, and a catalogue [holds only the keys its package's code uses](../../internals/i18n.md#every-package-ships-its-catalogue-in-translations-which-loads-with-no-wiring).
The first component that renders one brings `translations/sylament-widgets+intl-icu.en.yaml` under Filament's key paths, with the four requires [a catalogue brings](../../../.claude/rules/manifests.md).
