# Widgets — port trail

Ports [`filament/widgets`](../../../vendor/filament/widgets) into [`packages/widgets`](../../../packages/widgets): the dashboard widgets, from stats overviews to charts and table widgets.

## Files

<!-- trail-index:start -->
| File | Status | Open | Ports | Description |
| --- | --- | --- | --- | --- |
| [widgets-bundle.md](widgets-bundle.md) | in progress | 2 in progress · 4 todo | `WidgetsServiceProvider.php`, `Commands/MakeWidgetCommand.php`, `resources/css/index.css` | The widgets bundle class and the package's stylesheet entry point. |
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`ChartWidget.php`](../../../vendor/filament/widgets/src/ChartWidget.php) | todo | — | [detail](widgets-bundle.md#the-chart-component-gives-way-to-symfonyux-chartjs) |
| _rest not audited yet_ | — | — | — |
