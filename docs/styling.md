# Styling architecture

Sylament styles its components the way Filament 4.x does — semantic classes compiled
from Tailwind — with one deliberate divergence: components never depend on Tailwind's
preflight, because Sylament embeds into arbitrary host apps instead of owning the page.

Tailwind is a **private build-time tool** of `@sylament/support`. The shipped artifact
(`packages/support/assets/dist/styles/index.css`) is plain CSS, delivered to consumers
through the AssetMapper `autoimport` flag in `packages/support/assets/package.json`.
Consumers never need Tailwind installed.

## The rules

1. **Templates carry only semantic `sy-` classes, named with package codes.** Never
   utility classes in Twig — template scanning is disabled (`source(none)`), so a
   utility class in a template silently does nothing. Class names follow Filament's
   hook-class convention: `sy-<package code>-<component>`, e.g. `sy-fo-input`,
   `sy-sc-grid`. Package codes: `sy-ac` (actions), `sy-fo` (forms), `sy-in` (infolists),
   `sy-no` (notifications), `sy-pa` (panels), `sy-sc` (schemas), `sy-ta` (tables),
   `sy-wi` (widgets); support-level classes have no code (`sy-color-danger`). Not every
   emitted class has its own CSS rule: templates also emit code-less state/modifier
   classes (`sy-collapsed`, `sy-inline`, `sy-growable`, `sy-size-*`, breakpoint-
   prefixed grid markers) that exist as styling hooks, selectors for variant rules,
   and JS targets — inert until a component file or controller cares. A utility a
   component needs and support does not ship globally (`sy-invisible`, `sr-only`
   twins) is nested under that component's own class in its stylesheet (support's
   `fieldset.css`, schemas' `section.css`), or published as a package-prefixed class
   of its own when more than one component needs it (forms' `.sy-fo-sr-only`, panels'
   `.sy-pa-skip-link`).
2. **Every component template's root element carries the bare `sy` marker class**
   (`<div class="sy sy-fo-field">`). It is how the scoped preflight identifies
   Sylament markup — exact-class matching, so a host app's own `sy-*`-like class names
   can never trigger the reset. Inner elements don't need it (they're descendants of a
   marked root), but a component that can render standalone must have it on its root.
   One other marker class travels with `sy`: a server-rendered `sy-cloak` (hidden only
   under `@media (scripting: enabled)`, `base.css`) marks markup a controller reveals
   once its first state is applied — the gate keeps the content reachable with JS off,
   where an unconditional hide would strand it. `Section` (`persistCollapsed`), `Tabs`
   (`persist`) and the `Repeater` (`persistCollapsed`) emit it, each only in the
   persisted case where a stored value can override the server's first paint.
3. **Component CSS lives in one file per component**:
   `packages/<pkg>/assets/src/styles/components/<name>.css`, written with `@apply` and
   nested CSS, `dark:`/`hover:` variants inline. Plain CSS only where `@apply` can't
   express it. Each package's `styles/index.css` is the aggregator that imports its
   component files with `layer(components)` (support's own live in its
   `styles/components.css`); `support`'s `styles/index.css` entry imports the
   aggregators and is the single Tailwind compile.
4. **Dynamic per-instance values are inline CSS variables, never dynamic class names.**
   The grid system is the reference implementation: the `grid()` / `gridColumn()` Twig
   functions (`GridExtension`) return class + style maps whose inline styles set
   Filament-verbatim, deliberately *un*-prefixed variables (`--cols-lg: repeat(3,
   minmax(0, 1fr))`, `--col-span-default: span 2 / span 2`) that `grid.css` consumes;
   the classes (`sy-grid`, `lg:sy-grid-cols`, …) only gate *which* breakpoint variable
   applies. Variables invented on our side (not part of a Filament-verbatim set) use
   the `--sy-*` prefix.
5. **Colors go through `--sy-*` runtime tokens, and a registry decides what they
   hold.** `@theme inline` in support's `styles/index.css` bridges Tailwind color
   tokens to runtime variables, so `@apply bg-primary-600` compiles to
   `background-color: var(--sy-primary-600)`. Semantic palettes: `primary`,
   `gray`, `danger`, `info`, `success`, `warning`. Important: `@theme inline`
   variables are **not emitted at runtime** — plain CSS must reference the
   `--sy-*` token (`var(--sy-gray-950)`), not `var(--color-gray-950)`.
   Those six are defined twice on purpose: as `oklch()` values in the `@layer
   theme { :root }` **floor** of `index.css`, and as
   [`ColorManager::DEFAULT_COLORS`](../packages/support/src/Colors/ColorManager.php)
   in PHP, which is what the contrast policies measure against. The floor is why
   a consumer who calls nothing still gets coloured components — Filament's
   compiled theme defines no palette values and requires its `@filamentStyles`
   directive — and the two copies are pinned to each other shade by shade by
   `CssPaletteFloorTest`. Registering or overriding a palette:
   [How consumers customize](#how-consumers-customize).
6. **Per-instance color uses the `color` palette** (Filament's trick): component
   CSS writes `@apply bg-color-500`; a `.sy-color-<palette>` class (see
   `support/assets/src/styles/utilities.css`) or inline `--sy-color-*` variables
   decide what that resolves to. An **unknown** color name produces a
   `.sy-color-<unknown>` class no rule matches, so nothing sets the palette and
   the declarations reading it are dropped at computed-value time — the element
   inherits rather than falling back. (It used to fall back to `primary`, from a
   `--sy-color-*` root default block; those eleven declarations were deleted with
   the colour registry, so a typo now renders visibly wrong instead of silently
   deliberate. Filament behaves the same way.)

   **Which shade of that palette an element paints with is not written in CSS.**
   A [contrast policy](progress/support/color-policies.md) resolves it per
   palette at render time — `Badge` asks for *text readable against this
   palette's shade 50*, not for shade 600 — because palettes do not share a
   lightness curve and one fixed shade cannot serve them all. The policy's answer
   arrives as **slot variables**, and component CSS reads those instead of a
   shade:

   ```css
   .sy-badge.sy-color { @apply bg-color-50 text-(--sy-text) dark:text-(--sy-dark-text); }
   ```

   Eight slots exist, `bg` and `text` each in plain, `hover:`, `dark:` and
   `dark:hover:` form: `--sy-bg`, `--sy-hover-bg`, `--sy-dark-bg`,
   `--sy-dark-hover-bg`, `--sy-text`, `--sy-hover-text`, `--sy-dark-text`,
   `--sy-dark-hover-text`. Each is set by a **shade utility class** the template
   emits alongside the swap class — 92 hand-written rules in `utilities.css`, 44
   background (four prefixes × the eleven grid shades) and 48 text (four
   prefixes × the eleven shades **plus 0**, which means white):

   ```css
   .sy-text-color-700        { --sy-text: var(--sy-color-700); }
   .hover\:sy-text-color-500 { --sy-hover-text: var(--sy-color-500); }
   .dark\:sy-bg-color-400    { --sy-dark-bg: var(--sy-color-400); }
   ```

   The escaped colon is part of the **class name only** — these carry no `:hover`
   selector and no dark variant of their own. Gating is the component CSS's job
   (`hover:text-(--sy-hover-text)`), which is what lets one utility serve every
   component. A policy declaring no slots (`return []`) is a real answer meaning
   *swap the palette, I pick shades in CSS*; it still gets the two base classes.

   Two rules follow for anyone writing component CSS. **Never hardcode a shade
   for a colour a user can set** — `@apply text-color-600` is the defect this
   layer exists to remove; take a slot variable and let the policy choose. And a
   slot you read must be one the policy actually sets, which
   `PolicyShadeGridTest` (support, schemas) and `ToggleColorSlotsTest` (forms)
   check by scanning the stylesheets: both halves of a mismatch fail in silence,
   with the element painting its unset default and nothing in the console.
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
   (unlayered host CSS still always wins). The selector rewriter itself lives in
   `assets/scripts/scope-css.mjs`, and the same machinery serves redistributed vendor
   stylesheets (rule 11) — preflight's two adaptations are options it is called with,
   not a second copy of the walker.
8. **Everything ships inside cascade layers** (`theme`, `base`, `components`,
   `utilities` — the last carrying the `.sy-color-<palette>` swap classes), so any
   unlayered consumer CSS overrides Sylament without `!important`.
9. **Dark mode is class-based**: `@variant dark (&:where(.dark, .dark *))`.
   The class lands on `<html>`, so a page opting in sets it there; panels ship a
   switcher and a head script that do it for a panel. One rule follows the
   variant rather than a component: `:root.dark { color-scheme: dark }` in
   `support`'s `base.css`, which is what makes the UA paint form-control
   internals, scrollbars and the canvas dark from the class rather than from the
   OS preference alone.
10. **One `@font-face` ships, in `support/assets/src/styles/fonts.css`.** Inter
    Variable, latin subset, imported into `index.css` at the front of the base
    group and reachable as the `--sy-font` token — which nothing outside a panel
    consumes, so a host page's typography is untouched. Three things about it are
    easy to break. The file **must stay a sibling of `index.css`**: its
    `url('./fonts/…')` survives the Tailwind build verbatim, and it resolves only
    because source and output sit at the same depth — Tailwind rebases a relative
    url against the entry, so the no-op is a coincidence of layout, not a
    guarantee. Its `format('woff2-variations')` is the **legacy** keyword and is
    copied verbatim from the installed `@fontsource-variable/inter`; the modern
    spelling is `format('woff2') tech('variations')`, and "modernising" it drops
    support in older engines the vendor still targets. And
    `dist/styles/fonts/inter-LICENSE.txt` is a **deliberately served artifact**,
    copied beside the woff2 by `assets/scripts/copy-fonts.mjs`: the SIL Open Font
    License requires the notice to travel with a redistributed font file, and the
    split `sylament/support` publishes only `dist`.
11. **A class a bundled JS library emits is styled under a `sy-` wrapper class
    supplied through the library's own class settings, or — where the library
    hardcodes the class onto an element we render — qualified by that element's
    own `sy-` class. Never a bare vendor class at the top level, and never
    re-emitted from Twig. And a redistributed vendor base stylesheet is rewritten
    under the component's class by the build, never imported bare.** Rule 1 governs
    templates, and these classes are
    JS-generated, so the question rule 1 does not answer is what a *stylesheet*
    may select. The stylesheet is page-wide and unscoped by nesting, so a
    top-level `.sortable-ghost` would silently style a host app's own widget of the
    same name. The first branch's shipped case is support's drag-and-drop control
    (M3 · 6): the `sortable` controller hands SortableJS `ghostClass:
    'sy-sortable-ghost'`, and `components/sortable.css` selects that name alone —
    a wrapper class supplied to the library rather than a vendor class selected
    bare, exactly as Filament does it (`support/resources/js/sortable.js` +
    `resources/css/sortable.css`). The second branch's is forms' **FilePond**
    skin (M3 · 7): the library hardcodes its `filepond--*` classes onto the DOM
    it builds inside the `.sy-fo-file-upload` wrapper we render, so
    [`file-upload.css`](../packages/forms/assets/src/styles/components/file-upload.css)
    nests every override under that class. FilePond's **base** stylesheet is the
    third: it selects those classes bare at top level and declares three global
    `@keyframes` (`spin` collides with support's own), so support's
    `assets/scripts/scope-vendor-css.mjs` regenerates it as the gitignored
    `packages/forms/assets/src/styles/filepond.generated.css` — every rule
    rewritten to the descendant variant, every keyframe renamed
    `sy-fo-file-upload-*` with its `animation` references, and an MIT notice
    composed from the installed packages heading the file. Forms' select engine —
    a port of Filament's own — is the other side of the rule: it builds its DOM
    itself, emits only `sy-` classes, and leaves SortableJS's `sortable-ghost` /
    `sortable-chosen` / `sortable-drag` unstyled, exactly as Filament's select
    does. The Tom Select skin that used to ground this rule left with the engine
    swap
    ([forms styles.md](progress/forms/styles.md#the-js-select-skin-is-filaments-on-the-ported-controls-container)).

## How consumers customize

- **Palettes**: register them under `sylament.colors`, keyed by name. A value is
  either a colour to generate an eleven-shade palette from, or an explicit shade
  map that must list every shade of the grid (50 … 950) — a missing or off-grid
  shade throws, naming the palette, because a shade with no utility rule paints
  nothing and reports nothing.

  ```yaml
  sylament:
      colors:
          primary: '#7c3aed'            # redefines a default
          brand:                        # a new name, usable as ->color('brand')
              50: 'oklch(0.97 0.014 254.604)'
              # … through 950
  ```

  Render `{{ sylamentStyles() }}` once in the page `<head>` to emit them: a
  `:root` override for a redefined palette, a `.sy-color-<name>` swap class for a
  new one, and an empty string when nothing is registered. It is only needed for
  what you added — the six defaults ship in the stylesheet (rule 5). The
  `Color::` catalog constants are reachable from YAML through Symfony's
  `!php/const` tag (`rose: !php/const Sylament\Support\Colors\Color::Rose`), since
  the DI YAML loader parses with `Yaml::PARSE_CONSTANT`; PHP-side registration
  stays available too, by injecting `ColorManager` and calling
  `register(['primary' => Color::Rose])`.
- **Theme tweaks**: override `--sy-*` custom properties (e.g. set `--sy-primary-600`)
  in their own CSS — no build step, no Tailwind. This changes what the token
  holds but not which shade a component chooses: the contrast policies measure
  against `DEFAULT_COLORS` in PHP, so a palette overridden only in CSS can be
  painted at a shade that no longer suits it. Register it instead when the change
  is more than a nudge.
- **Component tweaks**: target the `sy-` classes; consumer CSS is unlayered and wins.
- **Shade choice**: replace a contrast policy by redefining its service id —
  `ColorManager` resolves policies from the container by class-string, so an
  application definition wins and applies everywhere that component renders. The
  substitute must be `public` (the lookup is `Container::get()`), must return only
  on-grid shades, and keeps `HasDefaultGrayColor` only if it declares it. Pinned by
  `Integration/Colors/PolicyOverrideTest`; consumer-facing write-up in
  [configuration.md](configuration.md#changing-how-a-component-picks-its-shades).
- **Different markup**: override the bundle Twig template
  (`templates/bundles/Sylament<Pkg>Bundle/components/...`).
- **Full custom design**: disable the stylesheet autoimport and style the `sy-` classes
  from scratch.
