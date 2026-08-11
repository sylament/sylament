# Indicator (tables)

[`Sylament\Tables\Filters\Indicator`](../../../packages/tables/src/Filters/Indicator.php)
— one chip in the active-filters row: a label, a colour, whether it can be
removed, and which field it removes. It ports Filament's
[`Filters\Indicator`](../../../vendor/filament/tables/src/Filters/Indicator.php).
[`FilterIndicator`](../../../packages/tables/src/Filters/FilterIndicator.php),
the readonly carrier pairing an indicator with its removal target, ships beside
it and folds into this file.

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `label()` / `getLabel()`](../../../packages/tables/src/Filters/Indicator.php) | [`Indicator`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | [diverges](#make-runs-configure) |
| [`removable()` / `isRemovable()`](../../../packages/tables/src/Filters/Indicator.php) | [`Indicator::removable()`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | — |
| [`removeField()` / `getRemoveField()`](../../../packages/tables/src/Filters/Indicator.php) | [`Indicator::removeField()`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | — |
| [`color()` — support's `HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) | [`Support\Concerns\HasColor`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`FilterIndicator` — the removal target carrier](../../../packages/tables/src/Filters/FilterIndicator.php) | [`removeLivewireClickHandler()`](../../../vendor/filament/tables/src/Filters/Indicator.php) | done | — | [diverges](#removal-is-structured-not-a-click-handler-string) |

Covered by
[`IndicatorTest`](../../../packages/tables/tests/Unit/Filters/IndicatorTest.php)
and the keying cases in
[`HasIndicatorsTest`](../../../packages/tables/tests/Unit/Filters/Concerns/HasIndicatorsTest.php).

## Divergences

### `make()` runs `configure()`

Filament's `Indicator::make()` is the one factory in its component tier that
calls `app(static::class, …)` and returns — no `configure()`, so an
`Indicator::configureUsing()` registration would never fire. Almost certainly an
oversight rather than a decision: every sibling factory configures. Ours goes
through the house `newInstance()` → seed → `configure()` idiom like everything
else, pinned by a test. [renames.md row](../renames.md#divergences).

### Removal is structured, not a click-handler string

`removeLivewireClickHandler(string)` is not ported. Filament stores a rendered
Livewire expression — `"removeTableFilter('status', 'value')"` — on the indicator
and echoes it into a `wire:click`. Here the indicator stays dumb and
`Table\Concerns\HasFilterIndicators::getFilterIndicators()` returns a
[`FilterIndicator`](../../../packages/tables/src/Filters/FilterIndicator.php)
pairing it with an action name and an argument map, which the
[table partial](../../../packages/tables/templates/partials/Table/index.html.twig)
hands straight to `stimulus_action('live', 'action', parameters: …)` — the house
form, and the only one that composes here, since `live_action()` returns a
*rendered attribute string* rather than the map a `<twig:>` tag spreads. No
string interpolation reaches markup, and the search chip reuses the same carrier
with a different action, `resetTableSearch`, and an empty map.
[renames.md row](../renames.md#renames).

A null `field` is filtered out of the map before it reaches `stimulus_action()`,
because `#[LiveArg] ?string $field = null` can only tell "no field" from "the
field is null" by the argument being **absent**, and
`StimulusAttributes::getFormattedValue()` would stringify the null into a present
parameter.

### The carrier has no constructor injection, so the service glob excludes it

`Filters/*.php` is registered as a service glob, and `FilterIndicator`'s `string`
and `array` constructor arguments are unautowirable — leaving it in would fail
the container compile, not merely register something useless. It is excluded by
path, the same shape as support's `Components/*.php` exclude for
`ComponentManager`.
