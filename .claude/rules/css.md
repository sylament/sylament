---
paths:
  - "packages/*/assets/src/styles/**"
description: How component stylesheets are written, scoped and coloured, and what support's CSS build generates.
---
# CSS rules

- **Select a component by its semantic `sy-<code>-<name>` class;** Tailwind scans no templates (`source(none)`), so the stylesheet is the only place a utility takes effect.
- **Write one file per component, `styles/components/<name>.css`, with `@apply`,** and import it into the package's aggregator (`styles/index.css`, support's `styles/components.css`) with `layer(components)`; support's `index.css` raw-imports every package's aggregator and is the only Tailwind compile.
- **Pass a per-instance value as an inline CSS variable, never a dynamic class;** prefix ours `--sy-*` and keep a Filament-verbatim set (the grid's `--cols-*`) unprefixed.
- **Read colours through the `--sy-*` tokens,** and in plain CSS write `var(--sy-gray-950)`, never `var(--color-gray-950)`: `--sy-*` is the runtime palette, while `--color-*` is only Tailwind's `@theme inline` alias of it, emitted wherever something happens to reference it.
- **Never hardcode a shade a contrast policy picks;** read its slot variable (`text-(--sy-text)`), since palettes do not share a lightness curve and one fixed shade fails contrast on some.
- **Hardcode a shade only where the element's policy returns `[]`** (icons do); an empty policy means the stylesheet picks the shade.
- **Read only a slot the element's policy sets;** an unset slot paints its default in silence, which `PolicyShadeGridTest` and `ToggleColorSlotsTest` catch by scanning these files.
- **Never ship preflight page-wide;** `scope-preflight.mjs` regenerates it scoped to `.sy` markup, so a host page is never reset.
- **Keep `fonts.css` a sibling of support's `index.css`;** its relative `url('./fonts/…')` resolves only because source and built output sit at the same depth.
- **Keep `copy-fonts.mjs` resolving with `require.resolve`;** it throws on a missing package, where AssetMapper would only log a warning and the browser 404.
- **Never select a class a bundled JS library emits bare at the top level;** hand the library a `sy-` class through its own settings, or nest under the `sy-` class of the element it builds inside, since the stylesheet is page-wide.
- **Never import a redistributed vendor stylesheet bare;** add it to `scope-vendor-css.mjs`, which rewrites it under the component's class and renames its keyframes.
- **Never edit or commit a `*.generated.css`;** support's `build:css` regenerates all of them from the installed packages, and `.gitignore` excludes them.

Background: `docs/styling.md` (the full contract), `docs/internals/assets.md`, `docs/progress/support/color-policies.md`.
