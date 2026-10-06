---
paths:
  - "packages/*/src/Colors/**"
  - "packages/support/src/Twig/Extension/ColorExtension.php"
description: "How a colour is named, how a contrast policy picks its shade, and how policies are registered."
---
# Colour rules

- **A colour is a palette name or an inline shade map, never a shade;** a contrast policy picks the shade per palette at render time, so a shade baked in elsewhere fails contrast on some palettes.
- **Keep the pieces apart:** `Color` holds static palette data and the maths (conversions, `calculateContrastRatio()`, `findShade()`), `ColorManager` is the registry (`DEFAULT_COLORS`, then `sylament.colors`, then `register()`, memoised at the first `getColors()`) and the emitter, and `ColorExtension` delivers to templates.
- **File a policy under its own package's `Colors/Components/`, never under `Twig/Components/`,** which already means a class-backed Twig component.
- **A policy implements `HasColor::getColorMap(array $color): array<string, int>` and states intent, not a number:** `ComponentColorMap::make($color)->slot('text', surface: $color[50], fallback: 900)` means "readable against this palette's shade 50".
- **Register every policy as a public service:** each `Colors/Components/` glob a bundle declares carries `->public()`, because `ColorManager` resolves a policy by class-string through `Container::get()`, which cannot reach a private service.
- **Declare one glob per directory level** the package's policies occupy (`Colors/Components/*.php`, `*/*.php`, `*/*/*.php`); a policy nested one level deeper than any glob is never registered.
- **Key a policy map only by the eight slots** `bg` and `text`, each also `hover:`, `dark:` and `dark:hover:`; `ColorManager` maps each to a shade utility class and throws on any other key for a named colour, while the inline shade-map path writes any key out as a `--sy-` declaration (colons to hyphens) without checking.
- **A policy returning `[]` is complete, not a stub:** the two base classes `sy-color` and `sy-color-<name>` are built before the map is read, so an empty map still swaps the palette and CSS fixes the shade.
- **`HasDefaultGrayColor` makes `gray` emit nothing,** so the component's CSS must paint gray in its `:not(.sy-color)` branch.
- **An unknown name emits the two base classes and no shade class;** there is no fallback palette, and it renders uncoloured on purpose.
- **Templates take colour through `colorAttributes(policyClass, color)`** spread into `attrs()`: a name yields classes, a shade map yields `sy-color` plus inline `--sy-color-*` and `--sy-<slot>` declarations.
- **`sylamentStyles()` emits only what the shipped stylesheet lacks:** `:root` values for palettes that differ from `DEFAULT_COLORS`, and a swap class for names outside them.

Background: `docs/progress/support/color.md`, `docs/progress/support/color-manager.md`, `docs/progress/support/color-policies.md`, `docs/internals/styling.md` rules 5 and 6.
