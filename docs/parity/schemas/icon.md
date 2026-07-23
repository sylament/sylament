# Icon (schemas)

Renders a standalone icon as a schema view component (a `<svg>` via the support
`generateIconHtml` helper). First-party consumer: the forms field wrapper's hint
icon, seeded into the `after_label` slot by `HasHint::setUpHint()` (see
[../forms/field.md](../forms/field.md)).

Ported: `icon` (string alias / `BackedEnum` / `Markup` / `Closure`), `size` (+ the
support `IconSize` enum), `color` (+ the shared support `HasColor` concern —
`color()` / `defaultColor()` / `getColor()` / `hasColor()`; see divergence),
`tooltip` (+ the support `HasTooltip` concern; see divergence), and `visible` /
`hidden` (inherited from the schemas `CanBeHidden`). The `.sy-sc-icon` color hook
lives in `packages/schemas/assets/src/styles/components/icon.css`.

Reference:

- [`vendor/filament/schemas/src/Components/Icon.php`](../../../vendor/filament/schemas/src/Components/Icon.php)
  — `make()`, `icon()` / `getIcon()`, `size()` / `getSize()`, `toEmbeddedHtml()`

## Missing

- [ ] Arbitrary (non-semantic) color values — Filament resolves any color through
  `->color(IconComponent::class, … ?? 'primary')` / the `ColorManager`
  ([support-managers](../infrastructure.md#support-managers)). We render the
  semantic-name palette-swap classes instead (divergence below); a custom hex /
  array palette maps to gray until the manager lands.
- [ ] The visually-hidden text alternative — Filament appends an `fi-sr-only`
  `<span>` derived from `aria-label` / tooltip so the decorative SVG is named.
  Deferred; the native `title` (divergence below) doubles as the accessible name
  for now.

## Divergences

- **`new static()` over `app()`**; two-layer Twig template
  (`@SylamentSchemas/components/Icon.html.twig` → `generateIconHtml`) over Filament
  v5's inlined `toEmbeddedHtml()` (same rationale as [section.md](section.md)).
- **`color()` renders palette-swap classes, not the `ColorManager`.** The template
  emits `sy-color` + `sy-color-<name>` for a semantic color name (the
  `IconButton`/`Section` convention, backed by the `--sy-color-*` runtime tokens),
  with a `.sy-sc-icon.sy-color` rule applying `text-color-*`; an iterable/empty
  color falls back to gray.
- **`tooltip()` renders a native `title` attribute**, not Filament's Alpine
  `x-tooltip` — the same stance the field wrapper already took for the hint icon; a
  real tooltip controller is blocked on
  [stimulus-controllers](../infrastructure.md#stimulus-controllers).
