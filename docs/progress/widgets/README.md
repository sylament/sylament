# Widgets — port progress

Ports [`filament/widgets`](../../../vendor/filament/widgets) into [`packages/widgets`](../../../packages/widgets).
Dashboard widgets: stats overview, charts, and table widgets.

**Status:** todo — see the [status vocabulary](../README.md#statuses) and [what goes where](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature).

The tables below are structure only; nothing has been audited yet.
[`support/component.md`](../support/component.md) is the shape each detail file follows.

One decision is taken ahead of the audit: `ChartWidget` and its typed subclasses render through `symfony/ux-chartjs`, a pre-approved Symfony package, rather than a port of Filament's own Chart.js wiring.

## Components

The `Widget` base and every widget — [`src/`](../../../vendor/filament/widgets/src).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Features

Cross-cutting concerns and traits — [`src/Concerns`](../../../vendor/filament/widgets/src/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Widget configuration, templates, assets — [`src/WidgetConfiguration.php`](../../../vendor/filament/widgets/src/WidgetConfiguration.php), [`src/View`](../../../vendor/filament/widgets/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
