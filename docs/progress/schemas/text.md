# Text (schemas)

`Sylament\Schemas\Components\Text` — the inline text content component, a `Component` subclass whose value is a piece of styled text (span-rendered by default).
It ports Filament's schema [`Text`](../../../vendor/filament/schemas/src/Components/Text.php).
The content core is ported and tested — `content()`/`getContent()`, `size()`/`getSize()` with its `getSizeClass()` template helper, and the shared [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) / [`HasWeight`](../../../packages/support/src/Components/Concerns/HasWeight.php) / [`HasFontFamily`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) styling concerns — all rendered through the [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) template on the `ViewComponent` path (the house replacement for Filament's `HasEmbeddedView::toEmbeddedHtml()`, so that contract is not ported — a systematic idiom translation recorded in the [rename table](../renames.md)).
The larger half of Filament's `Text` surface — badge mode, copyable, icon, tooltip, and `js()` — is not yet ported.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Text.php`](../../../vendor/filament/schemas/src/Components/Text.php) — the component, its content/size/badge/js surface, and the embedded view
- [`support/src/Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php), [`HasWeight.php`](../../../vendor/filament/support/src/Concerns/HasWeight.php), [`HasFontFamily.php`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) — the styling concerns composed here
- [`support/src/Concerns/CanBeCopied.php`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php), [`HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php), [`HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php), [`HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php), [`HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) — the not-yet-ported feature concerns
- [`schemas/src/JsContent.php`](../../../vendor/filament/schemas/src/JsContent.php) — the JS-expression content wrapper behind `js()`

## Surface

Every point of this component.
Rows for the shared styling concerns (`HasColor`/`HasWeight`/`HasFontFamily`) point at their Filament source; they are also tracked in the support tracker.
_Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details) (gone once the row is **done**), **diverges** → the permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(content)`](../../../packages/schemas/src/Components/Text.php) | [`Text::make()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`content()` / `getContent()`](../../../packages/schemas/src/Components/Text.php) | [`Text::content()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`size()` / `getSize()` normalization](../../../packages/schemas/src/Components/Text.php) | [`Text::size()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`getSizeClass()` (Sylament template helper)](../../../packages/schemas/src/Components/Text.php) | — (no Filament equivalent) | done | — | — |
| [`HasColor` color/getColor/hasColor + default gray](../../../packages/support/src/Components/Concerns/HasColor.php) | [`HasColor`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | [diverges](#the-default-gray-color-lives-in-the-color-policy-not-setup) |
| [`HasWeight` weight/getWeight/getWeightClass](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | — |
| [`HasFontFamily` fontFamily/getFontFamily/getFontFamilyClass](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | — |
| [Twig span render: `sy`/`sy-sc-text` classes, escaping, raw `Markup`, extra attrs](../../../packages/schemas/templates/partials/Text/index.html.twig) | [`Text::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| `badge()` / `isBadge()` badge mode | [`Text::badge()`](../../../vendor/filament/schemas/src/Components/Text.php) | todo | — | [detail](#badge-mode) |
| `CanBeCopied` (copyable + copy JS) | [`CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | todo | — | [detail](#copyable) |
| `HasIcon` (`icon()`/`getIcon()`) | [`HasIcon`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | todo | — | [detail](#icon-support) |
| `HasIconPosition` (`IconPosition` Before/After) | [`HasIconPosition`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | todo | — | [detail](#icon-support) |
| `HasIconSize` (`IconSize`) | [`HasIconSize`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | todo | — | [detail](#icon-support) |
| `HasTooltip` (`tooltip()`/`getTooltip()`) | [`HasTooltip`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | todo | — | [detail](#tooltip) |
| `js()` / `JsContent` | [`JsContent`](../../../vendor/filament/schemas/src/JsContent.php) | todo | — | [detail](#js-and-jscontent) |
| `HasEmbeddedView` contract / `toEmbeddedHtml()` | [`HasEmbeddedView`](../../../vendor/filament/support/src/Components/Contracts/HasEmbeddedView.php) | skipped | — | — |
| `publishedViewOverrideCheckPath` (Blade view-override detection) | [`Text`](../../../vendor/filament/schemas/src/Components/Text.php) | skipped | — | — |

The two **skipped** rows fall out of the Twig-rendering translation: markup is built in `Text/index.html.twig` on the `ViewComponent` render path, not in a PHP `toEmbeddedHtml()`, so neither the `HasEmbeddedView` contract nor Filament's Blade view-override probe has a role here.
That translation is the house idiom documented in the [rename table](../renames.md), not a per-component divergence.

## Details

Specifics for the rows that are not finished — what is in, what is left, and the next step.
The ported subset (content, size + `getSizeClass()`, and the three styling concerns) is exercised by [`TextTest`](../../../packages/schemas/tests/Unit/Components/TextTest.php) (content literal/closure/`$component` injection, `Markup` passthrough, size normalization, weight/font-family, and the `get*Class()` fragments) and [`TextRenderTest`](../../../packages/schemas/tests/Integration/Components/TextRenderTest.php) (span render, string escaping, raw `Markup`, size/weight/font-family classes, extra attributes, color palette classes and gray omission); the features below are untested only because they are unported.

### Badge mode

`badge()` / `isBadge()` — Filament's flag that switches the root element from a `<span>` to a badge (a button when copyable), colored via `BadgeComponent` and wrapping the label in badge containers, and coerces the default `Size` to `Medium` in badge mode.
None of it is ported.
Next: add an `isBadge` flag (a small concern or inline) and a badge branch to `Text/index.html.twig` (`fi-badge` → `sy-badge` markup), including the badge-only `Size` default.

### Copyable

Filament's [`CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) (`copyable()` / `getCopyableState()` / `getCopyMessage()` / `getCopyMessageDuration()` / `isCopyable()`) plus the click-to-copy wiring on the span/button is absent.
Next: port `CanBeCopied` (into support or schemas) and add the copy interaction to the template — this needs a Stimulus clipboard controller standing in for Filament's Alpine `x-on:click` + copy-confirmation tooltip.

### Icon support

The three icon concerns — [`HasIcon`](../../../vendor/filament/support/src/Concerns/HasIcon.php), [`HasIconPosition`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) (`IconPosition` Before/After), and [`HasIconSize`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) — and the icon markup around the label are not ported.
The `IconPosition` / `IconSize` enums already exist under `Sylament\Support\Enums`, and the [`Icon` component](icon.md) already renders icons.
Next: port the three concerns and emit the icon before/after the label via `renderComponent` in `Text/index.html.twig`.

### Tooltip

[`HasTooltip`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) (`tooltip()` / `getTooltip()`) and the tooltip attribute wiring (with `allowHTML` derived from `Markup`-ness) are absent.
Next: port `HasTooltip` into support and render a tooltip attribute — requires the Sylament runtime equivalent of Filament's Alpine `x-tooltip`.

### `js()` and JsContent

Filament's `js()` wraps content in [`JsContent`](../../../vendor/filament/schemas/src/JsContent.php) so it renders as an evaluated JS expression rather than literal text.
Neither `Text::js()` nor `JsContent` is ported (the supporting-surface `JsContent` row in the [README](README.md) is still todo).
Next: port `JsContent` and add `Text::js()` — this depends on the live-component / JS runtime layer.

## Divergences

### The default gray color lives in the color policy, not `setUp()`

Filament's `Text::setUp()` calls `defaultColor('gray')`, so `getColor()` returns `'gray'` when no color is set.
Sylament's `Text` has no `setUp()` override: `getColor()` returns `null` by default, and [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) computes nothing — it hands `getColor()` straight to `colorAttributes()`, which returns no classes for a blank color.
Gray asked for **by name** is suppressed a step further in, by [`TextComponent`](../../../packages/schemas/src/Colors/Components/TextComponent.php) implementing `HasDefaultGrayColor`: `ColorManager::getComponentClasses()` returns an empty list for that pair, so both spellings fall through to the base `text-gray-600 dark:text-gray-400` on `.sy-sc-text` (the two `TextRenderTest` cases "omits color classes for the default gray" and "omits color classes for gray asked for by name" pin them).
Rendered output matches Filament — gray is the visual default — but the `getColor()` return value diverges (`null` vs `'gray'`).
Reverses if a consumer needs `getColor()` to report the effective default programmatically.
