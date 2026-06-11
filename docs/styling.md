# Styling architecture

Sylament styles its components the way Filament 4.x does — semantic classes compiled
from Tailwind — with one deliberate divergence: components never depend on Tailwind's
preflight, because Sylament embeds into arbitrary host apps instead of owning the page.

Tailwind is a **private build-time tool** of `@sylament/support`. The shipped artifact
(`packages/support/assets/dist/styles/main.css`) is plain CSS, delivered to consumers
through the AssetMapper `autoimport` flag in `packages/support/assets/package.json`.
Consumers never need Tailwind installed.

## The rules

1. **Templates carry only semantic `sy-` classes, named with package codes.** Never
   utility classes in Twig — template scanning is disabled (`source(none)`), so a
   utility class in a template silently does nothing. Class names follow Filament's
   hook-class convention: `sy-<package code>-<component>`, e.g. `sy-fo-input`,
   `sy-sc-grid`. Package codes: `sy-ac` (actions), `sy-fo` (forms), `sy-in`
   (infolists), `sy-no` (notifications), `sy-sc` (schemas), `sy-ta` (tables), `sy-wi`
   (widgets); support-level classes have no code (`sy-color-danger`).
2. **Every component template's root element carries the bare `sy` marker class**
   (`<div class="sy sy-fo-field">`). It is how the scoped preflight identifies
   Sylament markup — exact-class matching, so a host app's own `sy-*`-like class names
   can never trigger the reset. Inner elements don't need it (they're descendants of a
   marked root), but a component that can render standalone must have it on its root.
3. **Component CSS lives in one file per component**:
   `packages/<pkg>/assets/src/styles/components/<name>.css`, written with `@apply` and
   nested CSS, `dark:`/`hover:` variants inline. Plain CSS only where `@apply` can't
   express it. Each package's `styles/main.css` is the aggregator that imports its
   component files with `layer(components)`; `support`'s entry imports the aggregators
   and is the single Tailwind compile.
4. **Dynamic per-instance values are inline CSS variables, never dynamic class names.**
   Example: `Grid.html.twig` sets `style="--sy-grid-cols: {{ ... }}"` and the CSS uses
   `repeat(var(--sy-grid-cols, 1), minmax(0, 1fr))`.
5. **Colors go through `--sy-*` runtime tokens.** `@theme inline` in support's
   `main.css` bridges Tailwind color tokens to runtime variables, so
   `@apply bg-primary-600` compiles to `background-color: var(--sy-primary-600)`.
   Semantic palettes: `primary`, `gray`, `danger`, `info`, `success`, `warning`.
   Important: `@theme inline` variables are **not emitted at runtime** — plain CSS must
   reference the `--sy-*` token (`var(--sy-gray-950)`), not `var(--color-gray-950)`.
6. **Per-instance color uses the `color` palette** (Filament's trick): component CSS
   writes `@apply bg-color-500`; a `.sy-color-<palette>` class (see
   `support/assets/src/styles/colors.css`) or inline `--sy-color-*` variables decide
   what that resolves to. Defaults to `primary`.
7. **Preflight is scoped, never page-wide.** `support`'s build generates
   `src/styles/preflight.generated.css` (gitignored) from the *installed* Tailwind's
   `preflight.css` via `assets/scripts/scope-preflight.mjs`, rewriting every rule to
   apply only to `.sy` markup (the marker-classed root and everything inside it) at
   original specificity. Components get full preflight semantics in any host app, the host page
   is never touched, and a Tailwind bump re-syncs automatically. Deliberate divergences
   (encoded in the script): the `html` rule maps onto Sylament root elements with its
   `font-*` declarations dropped — components inherit the host page's typography, so a
   page-wide font change reaches Sylament markup — and `body` is dropped. Two
   consequences: Filament component CSS can be ported essentially verbatim, and host
   content nested *inside* a Sylament container also gets preflight semantics
   (unlayered host CSS still always wins).
8. **Everything ships inside cascade layers** (`theme`, `base`, `components`), so any
   unlayered consumer CSS overrides Sylament without `!important`.
9. **Dark mode is class-based**: `@variant dark (&:where(.dark, .dark *))`.

## How consumers customize

- **Theme tweaks**: override `--sy-*` custom properties (e.g. set `--sy-primary-600`)
  in their own CSS — no build step, no Tailwind.
- **Component tweaks**: target the `sy-` classes; consumer CSS is unlayered and wins.
- **Different markup**: override the bundle Twig template
  (`templates/bundles/Sylament<Pkg>Bundle/components/...`).
- **Full custom design**: disable the stylesheet autoimport and style the `sy-` classes
  from scratch.
