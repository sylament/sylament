---
paths:
  - "packages/*/assets/src/styles/**"
description: How component stylesheets are written, scoped and coloured, and what support's CSS build generates.
---
# CSS rules

- **Select a component by its semantic `sy-<code>-<name>` class;** Tailwind scans no templates (`source(none)`), so the stylesheet is the only place a utility takes effect.
- **Write one file per component, `styles/components/<name>.css`, with `@apply`,** and import it into the package's aggregator (`styles/index.css`, support's `styles/components.css`) with `layer(components)`; support's `index.css` raw-imports every package's aggregator and is the only Tailwind compile.
- **Ship every rule inside a cascade layer** (`theme`, `base`, `components`, `utilities`, declared at the top of support's `index.css`), so a host's unlayered CSS overrides Sylament without `!important`.
- **Place a utility by how widely it is needed:** nest it under the component's class when one component needs it (support's `fieldset.css`), make it a package-prefixed class when several components of one package do (panels' `.sy-pa-skip-link`), and move it, code-less, to support's `utilities.css` once two packages do (`sy-sr-only`); a class is then shared exactly as widely as it is used.
- **Treat every emitted `sy-` class as public hook surface;** consumers restyle by selecting it from unlayered CSS, so a Sylament-only, dropped or re-specified hook is recorded in the component's trail entry.
- **Pass a per-instance value as an inline CSS variable, never a dynamic class;** prefix ours `--sy-*` and keep a Filament-verbatim set (the grid's `--cols-*`) unprefixed.
- **Read colours through the `--sy-*` tokens,** and in plain CSS write `var(--sy-gray-950)`, never `var(--color-gray-950)`: `--sy-*` is the runtime palette, while `--color-*` is only Tailwind's `@theme inline` alias of it, emitted wherever something happens to reference it.
- **Never hardcode a shade a contrast policy picks;** read its slot variable (`text-(--sy-text)`), since palettes do not share a lightness curve and one fixed shade fails contrast on some.
- **Hardcode a shade only where the element's policy returns `[]`** (icons do); an empty policy means the stylesheet picks the shade.
- **Read only a slot the element's policy sets;** an unset slot paints its default in silence, which `PolicyShadeGridTest` and `ToggleColorSlotsTest` catch by scanning these files.
- **Write dark styles with the `dark:` variant, never a `prefers-color-scheme` query;** the variant is class-based (`@variant dark (&:where(.dark, .dark *))`) because the page, or a panel's theme switcher, decides by putting `.dark` on `<html>`, and `base.css`'s `:root.dark { color-scheme: dark }` is the one rule that follows that class outside a component.
- **Never ship preflight page-wide;** `scope-preflight.mjs` regenerates it scoped to `.sy` markup, so a host page is never reset.
- **Keep `fonts.css` a sibling of support's `index.css`;** its relative `url('./fonts/…')` resolves only because source and built output sit at the same depth.
- **Keep `fonts.css`'s `format('woff2-variations')`,** the legacy keyword copied verbatim from `@fontsource-variable/inter`; the modern `format('woff2') tech('variations')` drops older engines the vendor still targets.
- **Keep `copy-fonts.mjs` resolving with `require.resolve`;** it throws on a missing package, where AssetMapper would only log a warning and the browser 404.
- **Never select a class a bundled JS library emits bare at the top level;** hand the library a `sy-` class through its own settings, or nest under the `sy-` class of the element it builds inside, since the stylesheet is page-wide.
- **Never import a redistributed vendor stylesheet bare;** add it to `scope-vendor-css.mjs`, which rewrites it under the component's class and renames its keyframes.
- **Never edit or commit a `*.generated.css`;** support's `build:css` regenerates all of them from the installed packages, and `.gitignore` excludes them.

Background: `docs/internals/styling.md`, `docs/internals/assets.md`, `docs/progress/support/color-policies.md`.
