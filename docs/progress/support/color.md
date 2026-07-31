# Color (support)

`Sylament\Support\Colors\Color` — the palette catalog and the colour maths every
contrast policy measures with. Pure static, no dependencies, no state: 26 palette
constants, five WCAG ratio constants, the three format conversions, the contrast
calculation, the shade search, and the palette generator. The registry that holds
palettes is [`color-manager.md`](color-manager.md); the objects that decide which
shade an element paints with are [`color-policies.md`](color-policies.md).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Colors/Color.php`](../../../vendor/filament/support/src/Colors/Color.php)
  — the catalog, the conversions and the contrast maths

## Surface

Every point of this class. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections do.

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
| [Unconsumed helpers — `convertToHex()`, `hex()`, `rgb()`, `isTextContrastRatioAccessible()`, `isNonTextContrastRatioAccessible()`](../../../packages/support/src/Colors/Color.php) | [same names](../../../vendor/filament/support/src/Colors/Color.php) | in progress | — | [detail](#five-public-helpers-are-ported-with-no-caller-and-no-test) |
| `generateV3Palette()` | [`generateV3Palette()`](../../../vendor/filament/support/src/Colors/Color.php) | skipped | — | [diverges](#generatev3palette-is-not-ported) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### Five public helpers are ported with no caller and no test

`convertToHex()`, the `hex()` / `rgb()` aliases of `generatePalette()`, and the
two `is*ContrastRatioAccessible()` predicates carry over verbatim so a migrating
user's call still resolves, but nothing in the port calls them and no test
exercises them — so they are **in progress** rather than **done** under the
[vocabulary](../README.md#statuses), which requires a test.

They are not idle by accident. Filament reaches `hex()` / `rgb()` from userland
registration (`FilamentColor::register(['brand' => Color::hex('#7c3aed')])`),
which here is covered by the `sylament.colors` node handing a colour string
straight to `getColors()`; `convertToHex()` and the two predicates are Filament's
panel-side colour-picker and debugging surface, which has no consumer until
panels. Each flips to **done** the moment something calls it and a case pins the
result — no code change expected.

`convertToRgb()` is **done** despite having no direct test of its own: both of
its branches are exercised through `calculateContrastRatio()`, which converts
every colour it is handed, and the suite drives it with hex pairs and with oklch
palettes.

## Semantics

Faithful ports that a reader re-deriving the behaviour from the source would
plausibly get wrong. Permanent, and true once the row is **done**.

### The contrast maths approximates WCAG's formula rather than implementing it

`calculateContrastRatio()` raises each raw channel to the power 2.2
(`($red / 255) ** 2.2`) where
[WCAG 2.1](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html)
specifies a piecewise sRGB transform — a linear segment below 0.03928 and a
`((c + 0.055) / 1.055) ** 2.4` curve above it — before applying the same
0.2126 / 0.7152 / 0.0722 weights. This is Filament's implementation carried over
unchanged, and the difference is small but real: black on white is 21.000 either
way, amber-700 on amber-50 measures 4.855 here against 4.851 by the WCAG
formula, and the two largest gaps in the shipped palettes are red-500 on white
(3.851 here, 3.808 by WCAG) and zinc-500 on zinc-50 (4.593 here, 4.625 by WCAG).

The consequence worth stating: a slot resolved right at the 4.5 boundary is
resolved against *this* number, so a shade this class calls accessible can be
marginally below AA by a strict reading, and vice versa. Nothing in the port
compensates for that — the policies, the regression tests and Filament all
measure with the same function, so they agree with each other. Replacing the
formula would move shades in shipped components, which is a visual change rather
than a port fix, so it stays.

Note also that `convertToRgb()` — which every contrast call runs first —
performs the correct piecewise transform in the *other* direction, on the oklch
side. The approximation is only in the luminance step.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### An unparseable colour throws instead of yielding black

Filament's conversions run `sscanf()` and use the result unchecked: a value that
does not match the format yields `null` channels, which coerce to `0`, so
`Color::convertToOklch('#zzzzzz')` silently produces black and a mistyped
palette registers as a black one. Sylament routes every scan through
`scanRgbChannels()` / `scanOklchChannels()`, which type-check the three channels
and otherwise throw an `InvalidArgumentException` naming the offending value.

This is CLAUDE.md's fail-fast rule beating parity: the failure Filament produces
surfaces as a black component, at render time, in a template that has no idea
where the value came from. It is the one behavioural difference a migrating user
can hit with previously-"working" input, and the input in question was always a
typo. Pinned by "throws on an unparseable color rather than yielding black" in
[`ColorTest`](../../../packages/support/tests/Unit/Colors/ColorTest.php).

### `generateV3Palette()` is not ported

Filament ships a second generator producing the shade curve its v3 palettes
used, so a v3 application upgrading to v4 can keep its colours looking the same.
Sylament has no v3 to be compatible with, and the method's only reason to exist
is that migration. **skipped**, and it does not become **todo** later — there is
no future in which it acquires a consumer here.
