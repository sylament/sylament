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
[`index.css`](../../../packages/widgets/assets/src/styles/index.css) is the package's stylesheet entry point, which support's [`index.css`](../../../packages/support/assets/src/styles/index.css) imports into its single built stylesheet.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle class](../../../packages/widgets/src/SylamentWidgetsBundle.php) | [`WidgetsServiceProvider.php`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | in progress | — | [detail](#the-bundle-class-is-an-empty-subclass-that-no-test-boots) |
| [Stylesheet entry point](../../../packages/widgets/assets/src/styles/index.css) | [`resources/css/index.css`](../../../vendor/filament/widgets/resources/css/index.css) | in progress | — | [detail](#the-stylesheet-entry-point-imports-no-widget-sheet) |
| `@SylamentWidgets` template namespace | [`->hasViews()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | [detail](#there-is-no-template-namespace-because-the-package-has-no-templates-directory) |
| Asset registration (the `chart` and `stats-overview/stat/chart` Alpine components) | [`packageBooted()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | [detail](#the-chart-component-gives-way-to-symfonyux-chartjs) |
| `MakeWidgetCommand` + stub publishing | [`Commands/MakeWidgetCommand.php`](../../../vendor/filament/widgets/src/Commands/MakeWidgetCommand.php), [`packageBooted()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | — |
| Translations (`->hasTranslations()`) | [`configurePackage()`](../../../vendor/filament/widgets/src/WidgetsServiceProvider.php) | todo | — | [i18n](../../internals/i18n.md#translation-is-one-deferred-pass-not-a-per-component-port) |

## Details

### The bundle class is an empty subclass that no test boots

`SylamentWidgetsBundle` declares no member, so it carries only what `AbstractBundle` and `SylamentBundle` give every bundle.
The package's test kernel registers it, but the suite's one test asserts `true` without booting that kernel, so nothing shows the bundle loads.
What is missing is a test that boots the kernel with the bundle registered.

### The stylesheet entry point imports no widget sheet

Filament's `index.css` imports the chart, stats-overview and base widget sheets under `layer(components)`.
The port's file holds only a comment, because no widget exists to style.
Support's `index.css` already imports it, so a sheet imported here reaches the built stylesheet with no further wiring.

### There is no template namespace, because the package has no `templates/` directory

Filament registers its view namespace with `->hasViews()`.
The port relies on TwigBundle's convention instead ([schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call)), and that convention maps a bundle's `templates/` directory to its namespace only when the directory exists.
`packages/widgets/` has no `templates/` directory, so `@SylamentWidgets` resolves nothing.

### The chart component gives way to `symfony/ux-chartjs`

Filament registers two Alpine components from its prebuilt `dist/`: `chart`, which `ChartWidget` mounts, and `stats-overview/stat/chart`, a stat's sparkline.
`ChartWidget` and its typed subclasses render through `symfony/ux-chartjs`, a pre-approved Symfony package, rather than through a port of Filament's Chart.js wiring, so the `chart` component has no port of its own to register.
The sparkline component follows the stats-overview widget.
No manifest requires `symfony/ux-chartjs`, because no chart widget exists.
