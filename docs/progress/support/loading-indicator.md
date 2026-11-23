---
description: The LoadingIndicator Twig component, the spinner SVG Filament's loading-indicator view renders, and its stylesheet.
ports:
  - vendor/filament/support/resources/views/components/loading-indicator.blade.php
  - vendor/filament/support/src/View/DefaultLoadingIndicator.php
  - vendor/filament/support/src/Contracts/LoadingIndicator.php
  - vendor/filament/support/resources/css/components/loading-indicator.css
owns:
  - packages/support/assets/src/styles/components/loading-indicator.css
  - packages/support/templates/components/LoadingIndicator/
---
# Loading indicator (support)

[`LoadingIndicator/index.html.twig`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) is the anonymous Twig component `<twig:SylamentSupport:LoadingIndicator>`, and [`loading-indicator.css`](../../../packages/support/assets/src/styles/components/loading-indicator.css) supplies its spin and size scale.
Schemas' [`Tabs`](../schemas/tabs.md) partial shows one in a deferred tab badge's placeholder, and tables' [`Table`](../tables/table.md) partial shows one while deferred records load.

Filament's view is a one-liner delegating to `generate_loading_indicator_html()`, which resolves a container-bound `LoadingIndicator` contract, by default [`DefaultLoadingIndicator`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php), a class emitting an empty `<svg>` classed `fi-loading-indicator-default`, which `loading-indicator.css` paints through a CSS mask.
The port's SVG is the template's own body rather than something a consumer can swap, and it draws the spinner with inline paths ([detail](#the-spinner-is-drawn-by-inline-paths-where-filament-masks-an-empty-svg)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The masked empty SVG + `aria-hidden`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) | [`DefaultLoadingIndicator::toHtml()`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php) | in progress | — | [detail](#the-spinner-is-drawn-by-inline-paths-where-filament-masks-an-empty-svg) |
| [`size` → `sy-size-*`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) | [`generate_loading_indicator_html(size:)`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#the-root-carries-sy-loading-indicator-without-sy-icon-so-the-size-scale-is-its-own) |
| [`loading-indicator.css`](../../../packages/support/assets/src/styles/components/loading-indicator.css) | [`components/loading-indicator.css`](../../../vendor/filament/support/resources/css/components/loading-indicator.css) | in progress | — | [detail](#the-spinner-is-drawn-by-inline-paths-where-filament-masks-an-empty-svg) · [diverges](#the-root-carries-sy-loading-indicator-without-sy-icon-so-the-size-scale-is-its-own) |
| `LoadingIndicator` contract | [`Contracts/LoadingIndicator.php`](../../../vendor/filament/support/src/Contracts/LoadingIndicator.php) | todo | — | [detail](#the-override-seam-is-unported) |
| `DefaultLoadingIndicator` | [`View/DefaultLoadingIndicator.php`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php) | todo | — | [detail](#the-override-seam-is-unported) |

## Details

### The spinner is drawn by inline paths, where Filament masks an empty SVG

Filament's `DefaultLoadingIndicator::toHtml()` emits an empty `<svg viewBox="0 0 24 24">` classed `fi-loading-indicator-default` ([`DefaultLoadingIndicator.php:15-19`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php)).
`loading-indicator.css` paints that class `inline-block shrink-0 bg-current` under a `mask` holding the two-path spinner as a data URI, and under `forced-colors: active` sets `forced-color-adjust: none` with `CanvasText`, or `ButtonText` inside a `<button>` ([`loading-indicator.css:5-18`](../../../vendor/filament/support/resources/css/components/loading-indicator.css)).
The port's template emits both `<path>` elements inline with `fill="currentColor"` and no `sy-loading-indicator-default` class ([`index.html.twig:8-30`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig)), and its stylesheet has no forced-colors arm.
**Next step:** render an empty `<svg>` classed `sy-loading-indicator-default`, port the mask and forced-colors rule into `loading-indicator.css`, and pin the class and the absence of `<path>` in a render case.

### The override seam is unported

Filament lets an application rebind the `LoadingIndicator` contract to swap every spinner in the UI at once; the helper resolves it from the container once and memoises it.
The port has no seam: the SVG is the template body, and overriding it means overriding the template.
Nothing in the toolkit asks for a different spinner, and a Twig-tier seam wants a different design from Laravel's container binding (a template override path or a config key), so the seam is decided when a consumer needs it rather than ported in the container's shape.

## Divergences

### The root carries `sy-loading-indicator` without `sy-icon`, so the size scale is its own

`generate_loading_indicator_html()` classes the SVG `fi-icon fi-loading-indicator fi-size-{size}`, so it inherits `icon.css`'s size scale, and Filament's own `loading-indicator.css` gives `.fi-loading-indicator` only `motion-safe:animate-spin` and `.fi-loading-indicator-default` only its mask and forced-colors paint.
The port emits `sy sy-loading-indicator sy-size-{size}`: the bare `sy` marker is mandatory ([styling.md](../../internals/styling.md#why-does-every-component-root-carry-the-bare-marker-class)), and `sy-icon` is absent because `icon.css` also gives `.sy-icon > svg` an inherited box for the wrapped `<span>` shape the icon extension emits, while the indicator is the `<svg>` itself.
The stylesheet therefore repeats `icon.css`'s scale value for value: `@apply size-5 motion-safe:animate-spin` plus the six `&.sy-size-*` rules.
The `size-5` base matters because a `size` outside the template's `knownSizes` list emits no size class at all, and the SVG would otherwise render dimensionless.
The `motion-safe:` guard is Filament's and stays: a spinner is the graphic a `prefers-reduced-motion` user asked to have frozen.
