---
description: The palette catalog and the colour maths every contrast policy measures with.
ports:
  - vendor/filament/support/src/Colors/Color.php
owns:
  - packages/support/src/Colors/Color.php
---
# Color (support)

`Sylament\Support\Colors\Color` is pure static, with no dependencies and no state.
The registry that holds palettes is [`color-manager.md`](color-manager.md); the objects that decide which shade an element paints with are [`color-policies.md`](color-policies.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [26 palette constants](../../../packages/support/src/Colors/Color.php) | [`Color::Slate` … `Color::Rose`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [Five WCAG ratio constants](../../../packages/support/src/Colors/Color.php) | [`WCAG_AA_TEXT` … `WCAG_AAA_LARGE_TEXT`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [`convertToOklch()`](../../../packages/support/src/Colors/Color.php) | [`convertToOklch()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | [diverges](#an-unparseable-colour-throws-instead-of-yielding-black) |
| [`convertToRgb()`](../../../packages/support/src/Colors/Color.php) | [`convertToRgb()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [`calculateContrastRatio()`](../../../packages/support/src/Colors/Color.php) | [`calculateContrastRatio()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | [semantics](#the-contrast-maths-approximates-wcags-formula-rather-than-implementing-it) |
| [`findShade()`](../../../packages/support/src/Colors/Color.php) | [`findShade()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [`generatePalette()`](../../../packages/support/src/Colors/Color.php) | [`generatePalette()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [`isLight()`](../../../packages/support/src/Colors/Color.php) | [`isLight()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [`all()`](../../../packages/support/src/Colors/Color.php) | [`all()`](../../../vendor/filament/support/src/Colors/Color.php) | done | — | — |
| [`convertToHex()`, `hex()`, `rgb()`, `is*ContrastRatioAccessible()`](../../../packages/support/src/Colors/Color.php) | [same names](../../../vendor/filament/support/src/Colors/Color.php) | in progress | — | [detail](#five-public-helpers-are-ported-with-no-caller-and-no-test) |
| `generateV3Palette()` | [`generateV3Palette()`](../../../vendor/filament/support/src/Colors/Color.php) | skipped | — | [diverges](#generatev3palette-is-not-ported) |

## Details

### Five public helpers are ported with no caller and no test

`convertToHex()`, the `hex()` / `rgb()` aliases of `generatePalette()`, and the two `is*ContrastRatioAccessible()` predicates carry over verbatim so a migrating user's call still resolves, but nothing in the port calls them and no test exercises them.
Filament's own callers are few: `hex()` / `rgb()` serve userland palette registration, which here a colour string in the `sylament.colors` node covers by going through `generatePalette()` in [`ColorManager::getColors()`](../../../packages/support/src/Colors/ColorManager.php); `convertToHex()` is called only by panels' `UiAvatarsProvider`, which the port does not ship; and the two predicates have no caller in Filament.
The row needs a test case per helper and no code change.

## Semantics

### The contrast maths approximates WCAG's formula rather than implementing it

`calculateContrastRatio()` raises each raw channel to the power 2.2 (`($red / 255) ** 2.2`) where [WCAG 2.1](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html) specifies a piecewise sRGB transform — a linear segment below 0.03928 and a `((c + 0.055) / 1.055) ** 2.4` curve above it — before applying the same 0.2126 / 0.7152 / 0.0722 weights.
This is Filament's implementation carried over unchanged, and the difference is small but real: black on white is 21.000 either way, amber-700 on amber-50 measures 4.855 here against 4.851 by the WCAG formula, red-500 on white 3.851 against 3.808, and zinc-500 on zinc-50 4.593 against 4.625.

The consequence worth stating: a slot resolved right at the 4.5 boundary is resolved against *this* number, so a shade this class calls accessible can be marginally below AA by a strict reading, and vice versa.
Nothing in the port compensates for that: the policies, the regression tests and Filament all measure with the same function, so they agree with each other.
Replacing the formula would move shades in shipped components, which is a visual change rather than a port fix, so it stays.

`convertToRgb()`, which every contrast call runs first, does perform the piecewise transform in the other direction, on the oklch side; the approximation is only in the luminance step.

## Divergences

### An unparseable colour throws instead of yielding black

Filament's conversions run `sscanf()` and use the result unchecked: a value that does not match the format yields `null` channels, which coerce to `0`, so `Color::convertToOklch('#zzzzzz')` silently produces black and a mistyped palette registers as a black one.
Sylament routes every scan through `scanRgbChannels()` / `scanOklchChannels()`, which type-check the three channels and otherwise throw an `InvalidArgumentException` naming the offending value.
Failing fast beats parity here: the failure Filament produces surfaces as a black component, at render time, in a template that has no idea where the value came from.
A migrating user can hit it only with input that renders black in Filament, and that input is a typo.

### `generateV3Palette()` is not ported

Filament ships a second generator producing the shade curve its v3 palettes used, so a v3 application upgrading to v4 can keep its colours looking the same.
Sylament has no v3 to be compatible with, and the method's only reason to exist is that migration.
