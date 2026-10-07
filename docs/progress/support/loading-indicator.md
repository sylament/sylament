---
description: The anonymous Twig component (`<twig:SylamentSupport:LoadingIndicator>`) rendering the spinner Filament's `<x-filament::loading-indicator>` renders… (draft)
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

[`LoadingIndicator/index.html.twig`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) — the anonymous Twig component (`<twig:SylamentSupport:LoadingIndicator>`) rendering the spinner Filament's `<x-filament::loading-indicator>` renders, with [`loading-indicator.css`](../../../packages/support/assets/src/styles/components/loading-indicator.css) supplying the spin and the size scale.
Its first consumer is schemas' [`Tabs`](../schemas/tabs.md) partial, which shows one inside a deferred tab badge's placeholder until the controller fills it.

Filament's view is a one-liner delegating to `generate_loading_indicator_html()`, which resolves a container-bound `LoadingIndicator` contract — by default [`DefaultLoadingIndicator`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php), a class emitting the two-path SVG.
The **markup** is ported here; the contract/override seam is not (rows below), so the SVG is the template's own body rather than something a consumer can swap.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The two-path SVG + `aria-hidden`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) | [`DefaultLoadingIndicator::toHtml()`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php) | done | — | — |
| [`size` → `sy-size-*`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) | [`generate_loading_indicator_html(size:)`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#the-root-carries-sy-loading-indicator-without-sy-icon-so-the-size-scale-is-its-own) |
| [`loading-indicator.css`](../../../packages/support/assets/src/styles/components/loading-indicator.css) | [`components/loading-indicator.css`](../../../vendor/filament/support/resources/css/components/loading-indicator.css) | done | — | [diverges](#the-stylesheet-carries-the-size-scale-vendor-inherits-from-fi-icon) |
| `LoadingIndicator` contract | [`Contracts/LoadingIndicator.php`](../../../vendor/filament/support/src/Contracts/LoadingIndicator.php) | todo | — | [detail](#the-override-seam-is-unported) |
| `DefaultLoadingIndicator` | [`View/DefaultLoadingIndicator.php`](../../../vendor/filament/support/src/View/DefaultLoadingIndicator.php) | todo | — | [detail](#the-override-seam-is-unported) |

Exercised from **schemas**' Integration suite ([`TabsRenderTest`](../../../packages/schemas/tests/Integration/Components/TabsRenderTest.php)), which asserts the `svg.sy-loading-indicator.sy-size-sm` inside a deferred tab's placeholder — support's own fixture Kernel registers no TwigComponentBundle, the split [breadcrumbs.md](breadcrumbs.md) records.

## Details

### The override seam is unported

Filament lets an application rebind the `LoadingIndicator` contract to swap every spinner in the UI at once; the helper resolves it from the container once and memoises.
Ours has no seam: the SVG is the template body, and overriding it means overriding the template.
Nothing in the toolkit needs the indirection yet — one consumer, one shape — and a Twig-tier seam would want a different design from Laravel's container binding anyway (a template override path or a config key).
Next step: revisit when a second consumer wants a different spinner, and decide the seam then rather than porting the container binding shape.

## Divergences

### The root carries `sy-loading-indicator` without `sy-icon`, so the size scale is its own

`generate_loading_indicator_html()` classes the SVG `fi-icon fi-loading-indicator fi-size-{size}`, so it inherits `icon.css`'s size scale and Filament's own `loading-indicator.css` only has to add the spin.
Ours emits `sy sy-loading-indicator sy-size-{size}` — the bare `sy` marker is mandatory ([styling.md](../../internals/styling.md#why-does-every-component-root-carry-the-bare-marker-class)) and `sy-icon` is deliberately absent, because `icon.css` also gives `.sy-icon > svg` an inherited box for the wrapped `<span>` shape the icon extension emits and the indicator *is* the `<svg>`, not a wrapper around one.
The size scale is therefore repeated in `loading-indicator.css`, copied value-for-value from `icon.css` — including its `size-5` base.

### The stylesheet carries the size scale vendor inherits from `fi-icon`

Vendor's stylesheet is a single `@apply motion-safe:animate-spin`; the box comes from the `fi-icon` class it also carries.
Ours keeps that declaration and adds the box beside it (`@apply size-5 motion-safe:animate-spin`) plus the six `&.sy-size-*` rules the missing `fi-icon` class would otherwise have supplied (above) — a `size` outside the template's `knownSizes` list emits no size class at all, so without the base the SVG would render dimensionless.
The `motion-safe:` guard is vendor's and is kept: a spinner is the graphic a `prefers-reduced-motion` user asked to have frozen, and dropping it would make this stylesheet the one place in the toolkit that overrides that preference.

_See also: the global [rename table](../renames.md) — the Blade → Twig render and the `fi-*` → `sy-*` class prefix are systematic translations recorded there._
