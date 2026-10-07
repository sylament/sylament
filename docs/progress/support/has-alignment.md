---
description: The concern that gives a component a horizontal alignment, its align*() shortcuts and the class a template emits for it.
ports:
  - vendor/filament/support/src/Concerns/HasAlignment.php
owns:
  - packages/support/src/Components/Concerns/HasAlignment.php
---
# HasAlignment (support)

`Sylament\Support\Components\Concerns\HasAlignment` ports Filament's trait of the same name; `getAlignment()` normalizes a known string to its [`Alignment`](../../../packages/support/src/Enums/Alignment.php) case and passes any other string through.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`alignment()` setter + property](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::alignment()`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`align*()` helpers](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::alignStart()` …](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`getAlignment()` normalization](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::getAlignment()`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`hasDynamicAlignment()`](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::hasDynamicAlignment()`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`getAlignmentClass()`](../../../packages/support/src/Components/Concerns/HasAlignment.php) | — (no Filament equivalent) | done | — | [diverges](#getalignmentclass-centralizes-the-alignment-to-class-mapping) |

## Divergences

### `getAlignmentClass()` centralizes the alignment-to-class mapping

Filament's `HasAlignment` stops at `getAlignment()`; each Blade template that renders an aligned component maps the `Alignment` case to a CSS class itself, with an inline `match` or ternary.
Sylament folds that mapping into the trait: `getAlignmentClass()` returns `sy-align-<value>` for an enum case, a custom string as-is, and `null` when unaligned, so a Twig template emits `component.getAlignmentClass` without type-sniffing the enum-or-string union `getAlignment()` returns.
It is additive, and it is one member of the [`get*Class()` family](../translations.md#the-getclass-family-is-one-pattern-and-filaments-getclass-means-a-class-string).
