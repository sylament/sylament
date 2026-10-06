# Styling

Why the bundles' stylesheets, and the classes their templates emit, are what they are. The rules are in `.claude/rules/css.md` (stylesheets), `.claude/rules/twig.md` (the classes a template emits) and `.claude/rules/colors.md` (colour policies); how the CSS is built and reaches an application is in `docs/internals/assets.md`.

Sylament styles its components the way Filament does, with semantic classes compiled from Tailwind, and diverges in one place: no component depends on a page-wide reset, because Sylament renders inside an application's pages instead of owning them. Tailwind is a private build-time tool of `@sylament/support`; the shipped `dist/styles/index.css` is plain CSS, and an application never installs Tailwind.

## Why do templates carry only semantic classes?

Tailwind scans no template (`source(none)`), so a utility class written in Twig compiles to nothing and fails without a sign; the stylesheet is the only place a utility takes effect. The names follow Filament's hook-class convention with `sy-` for `fi-`: `sy-<package code>-<component>`, where the code names the package that ships the component rather than the vendor directory its rules were copied from (`fi-sc-fused-group` became `sy-fo-fused-group`), and support's classes carry no code (`sy-icon-btn`, `sy-color-danger`). A template also emits code-less state and modifier classes (`sy-collapsed`, `sy-inline`, `sy-growable`, `sy-size-*`, the breakpoint-prefixed grid markers). They are hooks, selectors for variant rules and JavaScript targets, inert until a stylesheet or a controller reads them, so not every emitted class has a rule of its own.

## Why are the emitted classes public?

An application restyles Sylament by selecting its classes from unlayered CSS, which [outranks every layered rule](#why-does-everything-ship-inside-cascade-layers), so every emitted `sy-` class is a hook some application's stylesheet may target, as Filament's `fi-` classes are. Renaming, dropping or re-specifying one changes what that stylesheet matches without any error, which is why such a change is recorded in the component's trail entry rather than left to a diff.

## Why does every component root carry the bare marker class?

The scoped preflight has to tell Sylament markup from the host's, and it matches the exact class `sy` (`<div class="sy sy-fo-field">`), so a host's own class names that merely start with `sy-` never trigger the reset. Inner elements need no marker, being descendants of a marked root; a component that can render standalone carries it on its own root.

## Why is the cloak gated on scripting?

`sy-cloak` marks server-rendered markup that a controller reveals once it has applied its first state, because a value the browser stored can override the server's first paint: a `persistCollapsed()` repeater or section, and persisted tabs, each cloaked only in that persisted case. Support's `base.css` hides it only under `@media (scripting: enabled)`, so with JavaScript off the content stays reachable, where an unconditional hide such as Alpine's `x-cloak` would strand it. With JavaScript on and the controller left out of the application's `controllers.json`, the markup stays hidden ([assets.md](assets.md#how-does-a-controller-reach-a-consuming-application)).

## Where does a utility class live?

The stylesheet is page-wide, so a utility sits at the narrowest tier that serves every component using it. One a single component needs nests under that component's class (support's `fieldset.css`, schemas' `section.css`). One several components of one package need becomes a package-prefixed class: panels' `.sy-pa-skip-link`, emitted by both `Layout` and `SimpleLayout`, is the only class in that tier today. One two packages need is support's, code-less, in `utilities.css` (`sy-sr-only`). A package-local twin is tolerated until a second package needs it: forms' `.sy-fo-sr-only` held that tier until one did and then moved down to support, and tables' `.sy-ta-sr-only`, nested under the table root, is such a twin today.

## Why are per-instance values inline variables?

A class name built at render time has no rule to match, since nothing compiles from templates, so a value that varies per instance travels as an inline custom property that the stylesheet consumes. The grid is the reference: `GridExtension`'s `grid()` and `gridColumn()` return class and style maps whose inline styles set Filament's own variables (`--cols-lg: repeat(3, minmax(0, 1fr))`, `--col-span-default: span 2 / span 2`) for `grid.css` to read, and the classes (`sy-grid`, `lg:sy-grid-cols`) only gate which breakpoint's variable applies. A variable set copied from Filament stays unprefixed so Filament's rules port unchanged. A variable Sylament invents takes the `--sy-` prefix, because custom properties inherit and Sylament renders inside a page it does not own: `--text` and `--primary-600` are among the most common names in the wild, and one `:root { --text: … }` in a host application would otherwise repaint every component.

## How do colours reach the stylesheet?

Every colour resolves through a `--sy-*` runtime token. `@theme inline` in support's `index.css` bridges Tailwind's colour tokens to those variables, so `@apply bg-primary-600` compiles to `background-color: var(--sy-primary-600)` and component CSS keeps Tailwind's vocabulary. The `--color-*` names `@theme inline` defines are compile-time aliases, emitted only where something references them, which is why plain CSS reads the `--sy-*` token itself.

## Why are the default palettes defined twice?

The six default palettes exist as `oklch()` values in the `@layer theme { :root }` floor of support's `index.css`, and as [`ColorManager::DEFAULT_COLORS`](../../packages/support/src/Colors/ColorManager.php) in PHP, which is what the contrast policies measure. The floor is why an application that calls nothing still gets coloured components, where Filament's compiled theme defines no palette and requires its `@filamentStyles` directive; `sylamentStyles()` then emits only what an application adds on top ([color-manager.md](../progress/support/color-manager.md)). `CssPaletteFloorTest` pins the two copies to each other shade by shade.

## How does a component take a colour per instance?

Through Filament's palette named `color`: component CSS writes `@apply bg-color-500`, and a `.sy-color-<palette>` swap class in support's `utilities.css`, or inline `--sy-color-*` declarations, decide what that resolves to. An unknown colour name produces a `.sy-color-<name>` class no rule matches, so nothing sets the palette, the declarations reading it are dropped at computed-value time and the element inherits. It used to fall back to `primary` through a block of `--sy-color-*` root defaults; those were deleted with the colour registry, so a typo now renders visibly wrong instead of silently deliberate, as it does in Filament.

## Why does a policy pick the shade instead of the stylesheet?

Palettes do not share a lightness curve, so no fixed shade is readable against all of them: `@apply text-color-600` is the defect this layer exists to remove. A [contrast policy](../progress/support/color-policies.md) resolves the shade per palette at render time (`Badge` asks for text readable against the palette's shade 50, not for shade 600), and its answer arrives as slot variables the component CSS reads instead of a shade:

```css
.sy-badge.sy-color { @apply bg-color-50 text-(--sy-text) dark:text-(--sy-dark-text); }
```

Each of the eight slots (`bg` and `text`, plain and under `hover:`, `dark:` and `dark:hover:`) is set by a shade utility class the template emits beside the swap class: 92 hand-written rules in `utilities.css`, 44 for backgrounds (four prefixes × eleven shades) and 48 for text (the same plus `0`, white). The escaped colon belongs to the class name only (`.hover\:sy-text-color-500 { --sy-hover-text: var(--sy-color-500); }`): these rules carry no `:hover` selector and no dark variant of their own, because gating is the component CSS's job (`hover:text-(--sy-hover-text)`), which is what lets one utility serve every component. A policy that returns no slots is a real answer, meaning the palette swaps and the stylesheet picks the shade. Both halves of a slot mismatch fail in silence, the element painting the slot's unset default with nothing in the console, so `PolicyShadeGridTest` (support, schemas) and `ToggleColorSlotsTest` (forms) scan the stylesheets for it.

## Why is preflight scoped to Sylament markup?

A page-wide reset would restyle the host application, and Sylament does not own the page. Support's build therefore regenerates `preflight.generated.css` from the installed Tailwind's `preflight.css` through `scope-preflight.mjs`, rewriting every rule to apply only to `.sy` markup, the marked root and everything inside it, at its original specificity: components get full preflight semantics in any host, the host is never touched, and a Tailwind upgrade re-syncs by itself. Two adaptations are deliberate. The `html` rule maps onto Sylament's root elements with its `font-*` declarations dropped, so a page-wide font change reaches Sylament markup, and `body` is dropped. Two consequences follow: Filament's component CSS ports essentially verbatim, and host content nested inside a Sylament container gets preflight semantics too, though unlayered host CSS still wins. The selector rewriter is `scope-css.mjs`, which also scopes [redistributed vendor stylesheets](#why-is-a-bundled-library-styled-under-a-sylament-class); preflight's two adaptations are options it is called with.

## Why does everything ship inside cascade layers?

So that any unlayered CSS an application writes overrides Sylament at any specificity, with no `!important`. Support's `index.css` declares `theme`, `base`, `components` and `utilities` once, at its top, and imports everything into one of them; the `utilities` layer carries the `.sy-color-<palette>` swap classes.

## Why is dark mode class-based?

The page decides when it is dark, not the toolkit. The `dark:` variant is `@variant dark (&:where(.dark, .dark *))`, the class lands on `<html>`, and a page opting in sets it there; panels ship a switcher and a head script that do it for a panel. One rule follows the class rather than a component: `:root.dark { color-scheme: dark }` in support's `base.css`, which makes the browser paint form-control internals, scrollbars and the canvas dark from the class instead of from the operating system's preference alone.

## Why does the font ship the way it does?

One `@font-face` ships, in support's `fonts.css`: Inter Variable, latin subset, imported at the front of the base group and reachable as the `--sy-font` token, which nothing outside a panel reads, so a host page's typography is untouched. Three details are easy to break. `fonts.css` sits beside `index.css` because its `url('./fonts/…')` survives the Tailwind build verbatim only while source and output share a depth: Tailwind rebases a relative URL against the entry, so the no-op is a coincidence of layout, not a guarantee. Its `format('woff2-variations')` is the legacy keyword, copied verbatim from the installed `@fontsource-variable/inter`, and the modern `format('woff2') tech('variations')` would drop older engines the vendor still targets. And the font's licence file is served on purpose ([assets.md](assets.md#how-is-supports-css-built)).

## Why is a bundled library styled under a Sylament class?

The stylesheet is page-wide and nothing scopes it by nesting, so a top-level `.sortable-ghost` would silently style a host application's own widget of that name, and a class a JavaScript library adds at runtime is one no template rule covers. Two answers apply, both Filament's own. Where the library takes its class names as settings, Sylament hands it a `sy-` class: the `sortable` controller passes SortableJS `ghostClass: 'sy-sortable-ghost'`, and `components/sortable.css` selects that name alone, as Filament's `sortable.js` and `sortable.css` do. Where the library hardcodes its classes onto DOM it builds inside an element Sylament renders, the overrides nest under that element's `sy-` class: forms' FilePond skin under `.sy-fo-file-upload` ([`file-upload.css`](../../packages/forms/assets/src/styles/components/file-upload.css)), and the image editor's Cropper.js overrides under `.sy-fo-file-upload-editor`, the dialog its DOM lives in.

A vendor's own base stylesheet is never imported bare either. FilePond's selects its classes at the top level and declares three global `@keyframes`, one of them a `spin` that collides with support's, so support's `scope-vendor-css.mjs` regenerates it as forms' gitignored `filepond.generated.css`, every rule rewritten under the wrapper and every keyframe renamed `sy-fo-file-upload-*` along with its references; Cropper.js's sheet is rewritten the same way under the dialog's class. Forms' select engine, a port of Filament's, is the other side: it builds its own DOM, emits only `sy-` classes and leaves SortableJS's `sortable-ghost`, `sortable-chosen` and `sortable-drag` unstyled, as Filament's select does. The Tom Select skin that first grounded this left with the engine swap ([forms' styles.md](../progress/forms/styles.md#the-js-select-skin-is-filaments-on-the-ported-controls-container)).
