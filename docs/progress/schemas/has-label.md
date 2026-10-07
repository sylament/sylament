---
description: 'The human-facing label surface: `label()` / `getLabel()`, the `hiddenLabel()` / `isLabelHidden()` screen-reader toggle, and `hasCustomLabel()`. (draft)'
ports:
  - vendor/filament/schemas/src/Components/Concerns/HasLabel.php
owns:
  - packages/schemas/src/Components/Concerns/HasLabel.php
---
# HasLabel (schemas)

[`Sylament\Schemas\Components\Concerns\HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) — the human-facing label surface: `label()` / `getLabel()`, the `hiddenLabel()` / `isLabelHidden()` screen-reader toggle, and `hasCustomLabel()`.
It ports Filament's identically-named base concern and is shared — the [`Section`](section.md) box, the [`Fieldset`](fieldset.md) legend, the forms [`Field`](../../../packages/forms/src/Components/Field.php), and the actions [`Action`](../../../packages/actions/src/Action.php) all `use` it — so it keeps its own row rather than folding into any one of them.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`label()` / `getLabel()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`label()` / `getLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| [`hiddenLabel()` / `isLabelHidden()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`hiddenLabel()` / `isLabelHidden()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| [`hasCustomLabel()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`hasCustomLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| `translateLabel()` / `shouldTranslateLabel` | [`translateLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-are-not-ported) |
| `disableLabel()` (deprecated alias) | [`disableLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-are-not-ported) |

The three ported rows are **done** — the label, hidden-label toggle, and `hasCustomLabel()` are covered through [`SectionRenderTest`](../../../packages/schemas/tests/Integration/Components/SectionRenderTest.php) (label rendered above the section, hidden label kept as screen-reader-only, plain-string escaping, and the label-container omission for a blank label), so none has a write-up here.

## Divergences

### Label translation and the deprecated `disableLabel()` are not ported

Filament's `HasLabel` carries `shouldTranslateLabel` + `translateLabel()` and runs `getLabel()` through Laravel's `__()` translator, plus a `@deprecated` `disableLabel()` alias for `hiddenLabel()`.
Sylament drops all three: no framework-wide translation helper is wired in yet, and a fresh codebase has no reason to ship the deprecated alias.
Reverses for translation if/when a translation layer lands; the deprecated alias stays out.

_See also: the global [rename table](../renames.md) — the label prop's `string|Htmlable|Closure|null` → `string|Markup|Closure|null` retyping is the systematic `Htmlable` → `Twig\Markup` translation recorded there, not a divergence._
