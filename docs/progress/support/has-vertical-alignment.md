---
description: The shared concern that gives a component a configurable vertical alignment. (draft)
ports:
  - vendor/filament/support/src/Concerns/HasVerticalAlignment.php
  - vendor/filament/support/src/Enums/VerticalAlignment.php
owns:
  - packages/support/src/Components/Concerns/HasVerticalAlignment.php
---
# HasVerticalAlignment (support)

`Sylament\Support\Components\Concerns\HasVerticalAlignment` — the shared concern that gives a component a configurable vertical alignment.
It ports Filament's `HasVerticalAlignment`: a `VerticalAlignment | string | Closure | null` property, a fluent `verticalAlignment()` setter, three `verticallyAlign*()` shortcut helpers each taking a `bool | Closure` condition, a `getVerticalAlignment()` reader that normalizes a known string to its [`VerticalAlignment`](../../../packages/support/src/Enums/VerticalAlignment.php) case, and a `hasDynamicVerticalAlignment()` probe.
Sylament adds one method with no Filament counterpart — `getVerticalAlignmentClass()` — so this port carries a divergence, exactly as its horizontal sibling [`HasAlignment`](has-alignment.md) does.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`verticalAlignment()` setter + property](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::verticalAlignment()`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`verticallyAlign*()` helpers](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::verticallyAlignStart()` …](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`getVerticalAlignment()` normalization](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::getVerticalAlignment()`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`hasDynamicVerticalAlignment()`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | [`HasVerticalAlignment::hasDynamicVerticalAlignment()`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`getVerticalAlignmentClass()`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) | — (no Filament equivalent) | done | — | [diverges](#getverticalalignmentclass-centralizes-the-alignment-to-class-mapping) |

## Details

Every row above is **done** — the port is faithful and every method, including the added `getVerticalAlignmentClass()`, is exercised by [`HasVerticalAlignmentTest`](../../../packages/support/tests/Unit/Components/Concerns/HasVerticalAlignmentTest.php).

## Divergences

### `getVerticalAlignmentClass()` centralizes the alignment-to-class mapping

The same decision, and the same reasoning, as [`getAlignmentClass()`](has-alignment.md#getalignmentclass-centralizes-the-alignment-to-class-mapping): Filament's trait stops at `getVerticalAlignment()` and each Blade view maps the case to a class inline, while Sylament folds the mapping into the trait — `sy-vertical-align-<value>` for an enum case, a custom string as-is, `null` when unaligned — so a Twig template emits `component.getVerticalAlignmentClass` without type-sniffing the enum-vs-string union.
The class name is the one three shipped stylesheets already select (forms' `repeater.css`, notifications' `notification.css`, schemas' `flex.css`), each of which reaches it from a template that spells the mapping out by hand; the getter is what a new consumer uses instead, tables' `<td>` being the first.
