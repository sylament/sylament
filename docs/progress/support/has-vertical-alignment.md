---
description: The concern that gives a component a vertical alignment, its verticallyAlign*() shortcuts and the class a template emits for it.
ports:
  - vendor/filament/support/src/Concerns/HasVerticalAlignment.php
owns:
  - packages/support/src/Components/Concerns/HasVerticalAlignment.php
---
# HasVerticalAlignment (support)

`Sylament\Support\Components\Concerns\HasVerticalAlignment` ports Filament's trait of the same name; `getVerticalAlignment()` normalizes a known string to its [`VerticalAlignment`](../../../packages/support/src/Enums/VerticalAlignment.php) case and passes any other string through.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`verticalAlignment()` setter + property](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::verticalAlignment()`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`verticallyAlign*()` helpers](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::verticallyAlignStart()` …](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`getVerticalAlignment()` normalization](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::getVerticalAlignment()`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`hasDynamicVerticalAlignment()`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::hasDynamicVerticalAlignment()`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`getVerticalAlignmentClass()`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | — (no Filament equivalent) | done | — | [diverges](#getverticalalignmentclass-centralizes-the-alignment-to-class-mapping) |

## Divergences

### `getVerticalAlignmentClass()` centralizes the alignment-to-class mapping

The same decision as its horizontal sibling's [`getAlignmentClass()`](has-alignment.md#getalignmentclass-centralizes-the-alignment-to-class-mapping): Filament's trait stops at `getVerticalAlignment()` and each Blade view maps the case to a class inline, while Sylament's getter returns `sy-vertical-align-<value>` for an enum case, a custom string as-is, and `null` when unaligned.
Tables' cell template reads the getter ([`Table/index.html.twig`](../../../packages/tables/templates/partials/Table/index.html.twig)); [`Flex`](../../../packages/schemas/src/Components/Flex.php) and [`Repeater`](../../../packages/forms/src/Components/Repeater.php) build the same `sy-vertical-align-<value>` class in PHP by hand.
