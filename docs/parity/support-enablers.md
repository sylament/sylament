# Support enablers (slice subset) — epic design

> **Scope:** the thin cut of support-package primitives the tracer slice needs —
> the `Operation` / `Width` / `VerticalAlignment` enums, their paired
> `Has*` traits, and a shared `color()` attribute helper that centralizes the
> already-shipped CSS color layer's consumption pattern. This is
> **[roadmap.md](roadmap.md) Milestone 1, step 1** ("Support enablers"). The
> deferred remainder — full color math / `ColorManager` / palettes, the full enum
> batch, the full trait roster — is breadth-fill (Milestone 2). Task briefs:
> [support-enablers-tasks.md](support-enablers-tasks.md).
>
> Discovery read Filament v5 (`vendor/filament/support/`) and current `main`.
> Every load-bearing claim is source-cited so the task briefs port without
> re-deriving. Reference roots:
> [`vendor/filament/support/src`](../../vendor/filament/support/src),
> [`packages/support/src`](../../packages/support/src),
> [`packages/support/assets/src/styles`](../../packages/support/assets/src/styles).

## Sequencing — why this is small (forms/live-state landed ahead of it)

The roadmap orders the tracer 1→2→…; steps 3/5/6 (live-state, forms fields)
[landed early](forms-fields.md#sequencing--taken-out-of-strict-tracer-order-deliberate)
and, riding support's existing tokens, **absorbed most of what this epic would
have front-loaded**. The honest remaining sliver:

- **Enums** — support already ships 8 of Filament's 14
  ([`packages/support/src/Enum`](../../packages/support/src/Enum): `Alignment`,
  `FontFamily`, `FontWeight`, `GridDirection`, `IconPosition`, `IconSize`,
  `Size`, `TextSize`). Missing: `ArgumentValue`, `Operation`, `Platform`,
  `SlideOverPosition`, `VerticalAlignment`, `Width`. This epic ports the three
  the slice names; the other three stay in the deferred "full enum batch".
- **Color** — the CSS layer is *already broader* than the roadmap's "primary/gray
  CSS vars". [`index.css`](../../packages/support/assets/src/styles/index.css)
  ships all seven OKLCH palettes (`primary`, `gray`, `danger`, `info`, `success`,
  `warning`) **plus** the swappable dynamic `color` palette, bridged to Tailwind
  via `@theme inline`, dark-variant included; `.sy-color-<name>` remap classes
  live in [`utilities.css`](../../packages/support/assets/src/styles/utilities.css).
  What is missing is only the **PHP consumption seam** — a shared `color()` helper
  (each template hand-rolls it today) — not the tokens.
- **Traits** — support has 14 of Filament's ~33 support concerns; `HasColor`
  already exists as a bare value-holder. The two missing that pair with the
  in-scope enums are exactly `HasWidth` + `HasVerticalAlignment`.

Net: 3 bare enums + 2 tiny traits + one attribute helper. The enums/traits are
near-mechanical; the `color()` helper is the only real design work.

## The port shape at a glance

| Filament | Sylament (this epic) |
| --- | --- |
| `Filament\Support\Enums\{Operation,Width,VerticalAlignment}` | `Sylament\Support\Enum\{…}` — bare backed enums, systematic `Enums\`→`Enum\` singularization ([renames.md](renames.md) — not a rename) |
| `Filament\Support\Concerns\{HasWidth,HasVerticalAlignment}` | `Sylament\Support\Component\Concerns\{…}` — systematic support-component-trait placement; **defined, not wired** onto any base class |
| `ComponentAttributeBag::color($component, $color)` Blade macro → classes **+** inline custom-property styles (`FilamentColor`) | `color(...)` Twig function returning **class tokens**, nested into the class lists templates already build (`attrs()`, `classes()`, `generateIconHtml`); named-palette classes only — the inline-style/array path is the deferred `ColorManager` seam |
| `Filament\Support\Colors\{Color,ColorManager}` | **deferred** (🎨 breadth-fill) — [infrastructure.md#support-managers](infrastructure.md#support-managers) |

## Filament reference — what we port

**Enums** (`vendor/filament/support/src/Enums/`). All three are bare
`string`-backed enums — no methods, no interfaces (unlike some Filament enums
that implement `HasLabel`/`HasColor`), so they port verbatim under Sylament
conventions (`declare(strict_types=1)`, `Sylament\Support\Enum` namespace):

- `Operation.php` — `Create='create'`, `Edit='edit'`, `View='view'`. Filament
  injects the current operation as the `operation` closure parameter on the
  schemas base component (`vendor/filament/schemas/src/Components/Component.php:85`,
  by-name) and reads it off the form/panel host. **This epic ports the enum
  only** — the closure-arm + host `getOperation()` wiring lands with
  record-binding (roadmap step 5) / panels, which own the operation context.
- `VerticalAlignment.php` — `Start='start'`, `Center='center'`, `End='end'`.
- `Width.php` — 26 cases: `3xs`…`7xl`, `none`, `full`, `min`, `max`, `fit`,
  `prose`, `container`, `screen-sm`…`screen-2xl`, `screen`. An enum ports whole;
  "slice subset" means *which* enums, not which cases.

**Paired traits** (`vendor/filament/support/src/Concerns/`).

- `HasWidth.php` — `int | string | Closure | null $width`; `width(...)`;
  `getWidth(): ?string` converting an `int` to `"{n}px"`. Consumed by modals,
  dropdowns, columns (their epics wire it).
- `HasVerticalAlignment.php` — `VerticalAlignment | string | Closure | null`;
  `verticalAlignment(...)`, the `verticallyAlignStart/Center/End(bool|Closure = true)`
  sugar setters, `getVerticalAlignment(): VerticalAlignment | string | null`
  (`tryFrom`-normalizes a string to the enum, falling back to the raw string),
  and `hasDynamicVerticalAlignment(): bool` (`$verticalAlignment instanceof Closure`).
  Consumed by layout containers, table cells, actions.

Both already conform to the Sylament closure-typing rule (native `Closure`
unions, direct assignment, no arity specs) — the `verticallyAlign*` sugar uses
`fn (): ?VerticalAlignment => $this->evaluate($condition) ? … : null`, which is
kept verbatim.

**The color macro** (`vendor/filament/support/src/SupportServiceProvider.php:210`
registers it; the real body is
`vendor/filament/support/src/View/ComponentAttributeBag.php:57`). Filament's
`color(string | HasColor $component, string | array | null $color): static`:

1. `->style(FilamentColor::getComponentCustomStyles($component, $color))` (`:62`)
   — inline `--c-*` custom properties for an **arbitrary/array** palette.
2. `->class(FilamentColor::getComponentClasses($component, $color))` (`:65`) —
   the `fi-color` / `fi-color-<name>` classes for a **registered** palette.

It takes a *view-component type* (`->color(BadgeComponent::class, $color)`,
`vendor/filament/support/src/View/Concerns/CanGenerateBadgeHtml.php:102`) because
each component declares which shades it needs, and resolves colors through
`FilamentColor` / `ColorManager`
(`vendor/filament/support/src/Colors/{Color,ColorManager}.php`). Both the array
path and the component-type parameter are the machinery Sylament defers.

## Current graft surface (what's on main)

- **Enums** live flat in [`packages/support/src/Enum`](../../packages/support/src/Enum)
  (8 present, listed above), all bare backed enums (e.g.
  [`Size.php`](../../packages/support/src/Enum/Size.php),
  [`Alignment.php`](../../packages/support/src/Enum/Alignment.php) — no methods).
- **Traits** live in
  [`packages/support/src/Component/Concerns`](../../packages/support/src/Component/Concerns)
  (14 present, incl. `HasColor`, `HasAlignment`, `HasIcon`, `HasWeight`).
  [`HasColor.php`](../../packages/support/src/Component/Concerns/HasColor.php) is a
  bare holder: `string | array | Closure | null $color` + `$defaultColor`,
  `color()`/`defaultColor()`/`getColor()`/`hasColor()`; it does **not** resolve a
  color to classes — that is the template's job today.
- **Attribute system** — [`ComponentAttributeBag`](../../packages/support/src/Twig/ComponentAttributeBag.php)
  with the public static `toTokenList()` (`:243`, flattens strings / conditional
  maps / nested lists), and [`AttributeExtension`](../../packages/support/src/Twig/Extension/AttributeExtension.php)
  exposing the `attrs(...)` Twig function (`:38`, **terminal** — merges sources
  and returns an HTML string, `is_safe html`) and `classes(...)` (`:74`, joins a
  token list to a string for the ux-twig-component `attributes.defaults()` sink).
- **CSS color layer** — [`index.css`](../../packages/support/assets/src/styles/index.css)
  (7 palettes + dynamic `color`, `@theme inline`),
  [`utilities.css`](../../packages/support/assets/src/styles/utilities.css)
  (`.sy-color-<name>` remaps `--sy-color-*` → the named palette). Component CSS
  reads the dynamic palette via `@apply text-color-600` / `bg-color-500` guarded
  on a `.sy-color` marker (e.g. `text.css`, `icon-button.css`, `section.css`).
- **The four hand-rolled color call sites** — each derives
  `colorName = color is iterable or color is empty ? 'gray' : color`, then emits
  `['sy-color', 'sy-color-' ~ colorName]` for a non-gray name (gray → nothing, so
  the component's base gray CSS wins):
  - [`schemas/…/Icon.html.twig`](../../packages/schemas/templates/components/Icon.html.twig)
    — via `generateIconHtml`'s `attributes` map (`class: [..., 'sy-color', 'sy-color-'~name]`).
  - [`schemas/…/Text.html.twig`](../../packages/schemas/templates/components/Text.html.twig)
    — via `attrs()`, conditional-map form.
  - [`support/…/IconButton/index.html.twig`](../../packages/support/templates/components/IconButton/index.html.twig)
    — via `classes()` inside `attributes.defaults()`, conditional-map form.
  - [`support/…/Section/index.html.twig`](../../packages/support/templates/components/Section/index.html.twig)
    — the section icon's color, via `generateIconHtml`'s `attributes` map.

  Existing render tests pin the exact output
  ([`IconComponentRenderTest`](../../packages/schemas/tests/Integration/Components/IconComponentRenderTest.php),
  [`TextRenderTest`](../../packages/schemas/tests/Integration/Components/TextRenderTest.php),
  [`IconButtonRenderTest`](../../packages/schemas/tests/Integration/Components/IconButtonRenderTest.php),
  [`SectionRenderTest`](../../packages/schemas/tests/Integration/Components/SectionRenderTest.php)):
  `sy-color sy-color-primary`/`-danger`, and `not sy-color-` for the default —
  which makes the centralization an **output-equivalent** refactor, not a
  behavior change.

## Design

### Enums (`packages/support/src/Enum/`)

Three verbatim bare-enum ports: `Operation`, `VerticalAlignment`, `Width`
(cases exactly as Filament). No methods, no interfaces. Nothing consumes them on
`main` yet — they are enablers the slice's later epics reference.

### Traits (`packages/support/src/Component/Concerns/`)

`HasWidth` and `HasVerticalAlignment`, ported verbatim (native `Closure` unions,
`getVerticalAlignment()`'s `tryFrom` normalization, the `verticallyAlign*`
sugar). **Defined, not `use`d** onto the base `Component`/`ViewComponent` —
Filament places these on the specific components that need them (Modal→width,
layout→vertical-alignment), so consumers wire them in their own epics. Landing
them unused is deliberate and safe: PHPStan's `trait.unused` is the one globally
ignored rule (CLAUDE.md), precisely so primitives can precede their consumers.

### `color()` — the shared color-to-class helper

**The seam.** Filament's `->color()` does two things; Sylament already owns half
of each and defers the rest:

| Filament | Sylament now | This epic | Deferred (🎨) |
| --- | --- | --- | --- |
| `getComponentClasses()` → `fi-color-<name>` | `.sy-color-<name>` classes exist in CSS | **centralize** the class derivation in one helper | — |
| `getComponentCustomStyles()` → inline `--c-*` for arbitrary palettes | dynamic `--sy-color-*` vars exist | — | `ColorManager` maps an array/arbitrary color to inline `--sy-color-*` |
| component-type param (per-component shades) | class-swap is component-agnostic | not needed | not needed |

**Shape — class tokens, not a source map.** Sylament's `attrs()` is terminal
(returns a string), so there is no Blade-style chainable `$attributes->color()`.
The four sites build their class lists through **three** surfaces: `attrs()`
(Text), `generateIconHtml`'s `attributes` map (Icon, Section), and `classes()`
inside `attributes.defaults()` (IconButton) — but all three route class values
through `ComponentAttributeBag::toTokenList`, which flattens nested strings/lists.
So the value that composes with **every** site is a plain **class-token string**
(or list), nested where the site already builds its class list. (A `{class: […]}`
source map would compose with the map-based surfaces but not with `classes()` —
failing the centralization.) This makes `color()` a sibling of `classes()`, both
delegating to `ComponentAttributeBag`.

- **Pure logic** — `ComponentAttributeBag::colorClasses(string|array|null $color): list<string>`
  (mirrors the `toTokenList()` static): returns `[]` when `$color` is `null`, an
  array, `''`, or `'gray'` (the default — the component's base CSS styles gray);
  otherwise `['sy-color', "sy-color-{$color}"]`. This is exactly the four sites'
  `colorName` derivation + class pair, lifted once.
- **Twig function** — `AttributeExtension::color(string|array|null $color): string`
  returns `implode(' ', ComponentAttributeBag::colorClasses($color))` — a joined
  string exactly like `classes()` (so a stray `{{ color(x) }}` renders harmlessly:
  `''` for the default, `'sy-color sy-color-<name>'` otherwise), which re-splits
  via `toTokenList` when nested into any class list.
- **Call sites** — replace each hand-rolled block with a nested `color(...)`:
  - `Icon`: `class: ['sy-sc-icon', color(component.getColor)]`
  - `Text`: `class: ['sy', 'sy-sc-text', …, color(color)]`
  - `IconButton`: `classes('sy sy-icon-btn', { … }, color(color))`
  - `Section` (icon): `class: color(iconColor)`
  Nested lists flatten through `toTokenList` (verified in
  `ComponentAttributeBag::toTokenList`), so an empty `[]` contributes nothing and
  the gray/default output is byte-identical. (Checkpoint: confirm
  `generateIconHtml` routes its `attributes.class` through `ComponentAttributeBag`
  so a nested list flattens there too — it should; the task verifies against the
  Icon/Section render tests.)

No CSS changes (the palettes and `.sy-color-*` classes already ship), so **no
asset rebuild** — this epic is PHP + Twig only.

## Decisions

1. **Scope = tight front-load + `color()` helper** (user-approved). Port the 3
   enums + 2 paired traits + the color helper; the CSS palette layer is recorded
   as already-shipped. No PHP `Color`/`ColorManager`/math.
2. **`color()` returns a joined class-token string, nested into the class lists
   templates already build** (`attrs()`, `classes()`, `generateIconHtml`) — not a
   Blade-style method chain (Sylament's `attrs()` is terminal) and not a
   `{class: […]}` source map (would not compose with `classes()`).
   Component-agnostic (no component-type parameter): the `.sy-color-<name>`
   class-swap already generalizes what Filament does per-component with inline
   custom-property styles.
3. **Enums/traits are defined, not wired** onto any base class — consumers `use`
   them in their epics (Modal→`HasWidth`, layout→`HasVerticalAlignment`). Relies
   on the global `trait.unused` ignore.
4. **`Operation` = enum only** — the `operation` closure-arm + host
   `getOperation()` land with record-binding (step 5) / panels, which own the
   operation context.
5. **Full `Width` / `VerticalAlignment` enums ported** (an enum ports whole). The
   other three missing support enums (`ArgumentValue`, `Platform`,
   `SlideOverPosition`) stay in the deferred full-enum batch.
6. **Output-equivalent color refactor** — the four call sites' rendered classes
   are unchanged (pinned by the existing Icon/Text/IconButton/Section render
   tests); centralization is a mechanism change, not a behavior change.

## Divergences (record when code lands)

Home: **[infrastructure.md#support-managers](infrastructure.md#support-managers)**
(the 🎨 color thread already has this anchor). No new per-component parity file —
the enums and traits are faithful ports with nothing to record, and the color
helper's divergence is a cross-cutting-thread note, not a component note.

- **`color()` shape** — a composable class-token Twig function (returns a joined
  class string), not Filament's chainable Blade `ComponentAttributeBag::color()`
  macro (Sylament's `attrs()` is terminal). Component-agnostic class-swap replaces
  Filament's per-component inline custom-property styles.
- **Arbitrary/array colors are the `ColorManager` seam** — `getColor()` returning
  an array (a raw palette) yields no color this epic (falls back to the gray
  default, matching today's templates). Mapping an arbitrary palette to inline
  `--sy-color-*` custom properties needs `ColorManager` (deferred).

## Non-goals (record, don't build)

- **PHP color system** — `Color` (palette registry / OKLCH generation / WCAG
  contrast math), `ColorManager` (custom-palette registration), `ColorMaps`. The
  whole 🎨 breadth-fill; unblocks arbitrary/custom colors and every deeply-colored
  component. Milestone 2.
- **The array/arbitrary-color inline-style path** of `color()` (above).
- **Wiring enums/traits onto components** — and the `operation` closure
  injection + host `getOperation()` (→ step 5 / panels).
- **The other three missing support enums** (`ArgumentValue`, `Platform`,
  `SlideOverPosition`) and the rest of the trait roster (`HasBadge`, `CanWrap`,
  `HasExtraAlpineAttributes`→`HasExtraStimulusAttributes`, …) — each lands with
  its first consumer.

## Task plan

Cold-session briefs in [support-enablers-tasks.md](support-enablers-tasks.md).
Two independent tasks (no ordering dependency):

1. **S1 — Enums + paired traits.** Port `Operation`/`Width`/`VerticalAlignment`
   into `Sylament\Support\Enum` and `HasWidth`/`HasVerticalAlignment` into
   `Sylament\Support\Component\Concerns`. Deterministic PHP; Unit-tested (enum
   cases; trait setter/getter round-trips incl. int→px, `tryFrom` normalization,
   `verticallyAlign*` sugar, `Closure` values via `evaluate()`). No consumers.
2. **S2 — `color()` helper + centralize call sites.** Add
   `ComponentAttributeBag::colorClasses()` + the `color()` Twig function; refactor
   the four hand-rolled call sites to it; keep output byte-identical (existing
   render tests are the gate). Unit-test the helper (gray/null/array → `[]`, named
   → `['sy-color', 'sy-color-<name>']`). Record the divergences under
   `infrastructure.md#support-managers`. PHP + Twig only — no asset build.

## Renames to record when code lands

None. The `Enums\`→`Enum\` singularization and the
`Filament\Support\Concerns\` → `Sylament\Support\Component\Concerns\` placement
are systematic namespace translations already documented in
[renames.md](renames.md), not renames. Filament's public API names (`width`,
`verticalAlignment`, `verticallyAlignStart`, the enum case names) are preserved
verbatim.
