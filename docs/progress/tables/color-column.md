---
description: The swatch column, one sanitized `background-color` square per state value, optionally copyable or linked.
ports:
  - vendor/filament/tables/src/Columns/ColorColumn.php
  - vendor/filament/tables/resources/css/columns/color.css
owns:
  - packages/tables/assets/src/styles/components/color-column.css
  - packages/tables/src/Columns/ColorColumn.php
  - packages/tables/templates/partials/ColorColumn/
---
# ColorColumn (tables)

[`Sylament\Tables\Columns\ColorColumn`](../../../packages/tables/src/Columns/ColorColumn.php) is a [`Column`](column.md) whose cell is swatches: one rounded square per state value, painted with the value itself as a `background-color` and named for assistive technology by the sanitized colour.
Everything [`Column`](column.md) carries, from the name and the state read to the placeholder, the tooltips and the closure injections, is tracked there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`copyable()` / `copyableState()` / `copyMessage()` / `copyMessageDuration()` and their getters](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`Support\Concerns\CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [can-be-copied.md](../support/can-be-copied.md) |
| [`wrap()` / `canWrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`Support\Concerns\CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | [can-wrap.md](../support/can-wrap.md) |
| [`getStateItems()` — the cell's swatch list](../../../packages/tables/src/Columns/ColorColumn.php) | — (Sylament-only) | done | — | [semantics](#the-copy-values-are-resolved-only-for-a-copyable-swatch) |
| [The sanitized `background-color`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [helpers.md](../support/helpers.md#sanitize_css_color-is-the-second-macro-that-lands-as-a-helper) · [diverges](#only-a-string-or-a-backed-enums-value-reaches-the-sanitizer) |
| [The `role="img"` swatch and its `aria-label`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [semantics](#an-unsanitizable-value-paints-nothing-and-a-plain-swatch-names-nothing) |
| [The copyable swatch — `role="button"`, `aria-label="Copy …"` and the clipboard controller](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [diverges](#the-copyable-swatch-is-named-where-filament-leaves-it-nameless) · [copyable.md](../support/copyable.md) |
| [`$template`](../../../packages/tables/src/Columns/ColorColumn.php) + [the partial it names](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [diverges](text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) |
| [The blank-state placeholder and the empty tooltip](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [column.md](column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) |
| [The per-item tooltip](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [column.md](column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) |
| [The per-item `<a>` for a state-based `url()`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | — (Sylament-only) | done | — | [diverges](#the-swatch-honours-the-column-wide-url-where-filaments-ignores-it) |
| [`sy-inline`, the alignment class and the wrapper `extraAttributes()`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | — |
| [`color-column.css`](../../../packages/tables/assets/src/styles/components/color-column.css) | [`resources/css/columns/color.css`](../../../vendor/filament/tables/resources/css/columns/color.css) | done | — | [diverges](#the-copyable-focus-ring-lives-in-supports-sheet-rather-than-this-one) · [diverges](#the-stacked-on-mobile-rule-has-nothing-to-select) |

## Semantics

### An unsanitizable value paints nothing, and a plain swatch names nothing

`sanitize_css_color()` answers `null` for anything that is not unambiguously a CSS colour, and on a **plain** swatch the partial keys three attributes off that one answer: the `style`, the `role` and the `aria-label`.
So a cell holding `red;position:fixed` renders `<span class="sy-ta-color-item"></span>`, an empty, unnamed, uncoloured square, rather than a swatch announcing a value the browser never painted.
Naming a colour the page does not show would be worse than staying silent, which is why the three travel together rather than the `aria-label` falling back to the raw state.

A **copyable** swatch keys only the `style` off the sanitizer.
Its `role` is `button` for every value, because the copy control exists whatever the colour is, so the name cannot be dropped with the paint: a nameless `role="button"` carrying the clipboard controller is a focusable, operable control with no accessible name.
The unsanitizable arm therefore falls back to the colour-free `Copy colour`, the verb without a value the page shows.

The swatch still renders.
It is not dropped from the list, so a row of five values always shows five squares and a bad one reads as a gap in the row rather than as a shorter row.
[`IconColumn`](icon-column.md#an-item-whose-icon-resolves-blank-contributes-nothing-its-name-included) makes the opposite choice for the opposite reason: an icon carries its own name, a swatch is a fixed-size box.

### The copy values are resolved only for a copyable swatch

`getStateItems()` evaluates `copyableState`, `copyMessage` and `copyMessageDuration` behind `isCopyable($value)`, so a non-copyable column never runs those closures and the item's three copy keys are `null` rather than the `Copied` / `2000` defaults `CanBeCopied`'s getters would answer.
The keys exist on every item because the partial reads them unconditionally; `copyable` is the flag that decides whether a swatch has a copy control.

`copyableState` falls back to the raw state stringified, so `->copyable()` alone copies the colour as written in the record, not the sanitized form.
A value the sanitizer rejects is therefore still copyable, and copies what the record holds, which is what a user clicking a broken swatch wants to see.

## Divergences

### The copyable swatch is named where Filament leaves it nameless

Upstream's own comment says the copyable swatch "is an interactive control that needs separate treatment (an accessible name and keyboard operability), so it is not named here", and then nothing names it: the `role` and `aria-label` are suppressed for exactly the copyable case, leaving a `<div>` with a click handler, no role, no name and no keyboard path.

This port does the treatment the comment asks for.
A copyable swatch renders `role="button"`, `aria-label="Copy {colour}"` and the three [clipboard](../support/copyable.md) activation bindings (`click`, `keydown.enter`, `keydown.space`); focus is stamped client-side by the controller, so no `tabindex` is served inside a cell that may sit within a record `<a>`.
A non-copyable swatch keeps `role="img"` and the bare colour as its name.

The name is the **sanitized** colour wherever there is one, so the two arms' labels differ only by the verb.
Where there is none the arms part: the plain swatch drops its `role` and its name together and goes inert, while the copyable one keeps both and names itself `Copy colour`, because the control is still there to operate.

### The swatch honours the column-wide `url()` where Filament's ignores it

`ColorColumn::toEmbeddedHtml()` renders no anchor at all, so upstream's `->url(fn ($state) => …)` on a colour column silently links nothing: the cell wrapper is forced to a `div` by the state-based arm of [`getUrl()`'s arity switch](column.md#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom), and no per-item link takes its place.

`url()` comes from `CanOpenUrl` on the `Column` base in both codebases, so it is a column-wide contract, and this partial honours it the way [`IconColumn`](icon-column.md)'s does: each swatch inside its own `<a>`, taking `target="_blank"` from `openUrlInNewTab()`.
The two arms stay mutually exclusive: a URL that does not read the state answers on the wrapper alone, so a swatch is never nested inside two anchors.

### Only a string or a backed enum's value reaches the sanitizer

Filament hands the raw state item to `Str::sanitizeCssColor`, whose closure takes `string|BackedEnum|null`, unwraps a backed enum itself, and is called without strict types, so an `int` or a `Stringable` item is coerced to a string first.
[`sanitize_css_color()`](../support/helpers.md#sanitize_css_color-is-the-second-macro-that-lands-as-a-helper) is typed `?string`, so `getSanitizedColor()` reads `->value` off a `BackedEnum` and hands the sanitizer `null` for every other non-string.
A colour held as a backed enum case paints exactly as it does upstream: `Brand = '#4f46e5'` paints, `Brand = 'brand'` does not.
A `Stringable` value object holding `#4f46e5` paints upstream and renders an unpainted, unnamed swatch here; casting a `Stringable` before the sanitizer would close that.

### The copyable focus ring lives in support's sheet rather than this one

Filament's `color.css` carries a `&.fi-copyable` branch with `cursor-pointer outline-none focus-visible:ring-2` and the primary ring colours.
The port drops it, because support's [`copyable.css`](../../../packages/support/assets/src/styles/components/copyable.css) already states exactly those declarations on the bare `.sy-copyable` class the swatch carries, the class the shared [clipboard control](../support/copyable.md) is styled by everywhere it appears.
Re-stating them here would be a second definition of one rule, and the two would drift.

### The stacked-on-mobile rule has nothing to select

Upstream's `.fi-ta-table-stacked-on-mobile & { @apply p-0 sm:px-3 sm:py-4 }` is dropped: the stacked-on-mobile table layout is [blocked](table.md#surface), so the ancestor class that rule keys off is emitted nowhere.
It returns with that layout.
[`icon-column.css`](../../../packages/tables/assets/src/styles/components/icon-column.css) and [`text-column.css`](../../../packages/tables/assets/src/styles/components/text-column.css) drop the identical rule for the identical reason.
