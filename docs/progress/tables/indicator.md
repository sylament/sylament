---
description: One chip in the active-filters row — its label, colour, removability and removal field — and the carrier pairing it with its removal action.
ports:
  - vendor/filament/tables/src/Filters/Indicator.php
owns:
  - packages/tables/src/Filters/FilterIndicator.php
  - packages/tables/src/Filters/Indicator.php
---
# Indicator (tables)

[`FilterIndicator`](../../../packages/tables/src/Filters/FilterIndicator.php), the readonly carrier pairing an [`Indicator`](../../../packages/tables/src/Filters/Indicator.php) with its removal target, has no Filament counterpart and is documented here.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `label()` / `getLabel()`](../../../packages/tables/src/Filters/Indicator.php) | [`Indicator`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | [diverges](#make-runs-configure) |
| [`removable()` / `isRemovable()`](../../../packages/tables/src/Filters/Indicator.php) | [`Indicator::removable()`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | — |
| [`removeField()` / `getRemoveField()`](../../../packages/tables/src/Filters/Indicator.php) | [`Indicator::removeField()`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | — |
| [`color()` — support's `HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) | [`Support\Concerns\HasColor`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| `removeLivewireClickHandler()` / `getRemoveLivewireClickHandler()` | [`Indicator`](../../../vendor/filament/tables/src/Filters/Indicator.php) | skipped | — | [diverges](#removal-is-structured-not-a-click-handler-string) |
| [`FilterIndicator` — the removal target carrier](../../../packages/tables/src/Filters/FilterIndicator.php) | — (Sylament-only) | done | — | [diverges](#removal-is-structured-not-a-click-handler-string) |

## Divergences

### `make()` runs `configure()`

Filament's `Indicator::make()` is the one factory in its component tier that calls `app(static::class, …)` and returns — no `configure()`, so an `Indicator::configureUsing()` registration would never fire.
Almost certainly an oversight rather than a decision: every sibling factory configures.
Ours goes through the house `newInstance()` → seed → `configure()` idiom like everything else.

### Removal is structured, not a click-handler string

`removeLivewireClickHandler(string)` is not ported.
Filament stores a rendered Livewire expression — `"removeTableFilter('status', 'value')"` — on the indicator and echoes it into a `wire:click`.
Here the indicator stays dumb and `Table\Concerns\HasFilterIndicators::getFilterIndicators()` returns a [`FilterIndicator`](../../../packages/tables/src/Filters/FilterIndicator.php) pairing it with an action name and an argument map, which the [table partial](../../../packages/tables/templates/partials/Table/index.html.twig) hands straight to `stimulus_action('live', 'action', parameters: …)` — the house form, and the only one that composes here, since `live_action()` returns a *rendered attribute string* rather than the map a `<twig:>` tag spreads.
No string interpolation reaches markup, and the search chips reuse the same carrier with a different action: `resetTableSearch` with an empty map for the global term, and `resetTableColumnSearch` with the column name for each per-column term.

The partial filters a null `field` out of the map before it reaches `stimulus_action()`, because `#[LiveArg] ?string $field = null` can only tell "no field" from "the field is null" by the argument being **absent**, and `StimulusAttributes::getFormattedValue()` would stringify the null into a present parameter.
