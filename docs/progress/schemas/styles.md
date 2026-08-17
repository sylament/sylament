# Styles (schemas)

The schemas bundle's component CSS —
[`packages/schemas/assets/src/styles/`](../../../packages/schemas/assets/src/styles/) —
ports Filament's [`schemas/resources/css/`](../../../vendor/filament/schemas/resources/css/).
Each stylesheet is `@apply`-authored into `components/<name>.css` and `@import`ed
into [`index.css`](../../../packages/schemas/assets/src/styles/index.css) under
`layer(components)`, mirroring Filament's `index.css` structure. The `fi-*` class
prefix is translated to the schemas component code `sy-sc-*` (see the
[CSS contract](../../styling.md)). Four of Filament's stylesheets are ported so
far (schema, section, icon, actions) — `icon.css` rule-for-rule, the other three
dropping variants from their source; the rest wait on the components they style.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/resources/css/schema.css`](../../../vendor/filament/schemas/resources/css/schema.css)
  — the grid/gap/alignment stylesheet ported into `components/schema.css`
- [`schemas/resources/css/components/`](../../../vendor/filament/schemas/resources/css/components/)
  — the eleven per-component stylesheets (section, icon, text, and the eight unported)

## Surface

One row per stylesheet. _Blocked by_ names the component whose port unblocks the
CSS. _Detail_ carries a **diverges** link to a permanent note in
[Divergences](#divergences), a **detail** link to the write-up in
[Details](#details), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schema.css` — `.sy-sc` grid/inline/gap/dense/alignment/action](../../../packages/schemas/assets/src/styles/components/schema.css) | [`schema.css`](../../../vendor/filament/schemas/resources/css/schema.css) | in progress | — | [diverges](#schema-css-drops-the-fi-width-max-width-variants-and-the-hidden-rule) |
| [`section.css` — `.sy-sc-section` label container](../../../packages/schemas/assets/src/styles/components/section.css) | [`components/section.css`](../../../vendor/filament/schemas/resources/css/components/section.css) | done | — | [diverges](#section-css-adds-the-sr-only-hidden-label-rule) · [diverges](#section-css-adds-a-dark-mode-text-color-to-the-content-container) |
| [`icon.css` — `.sy-sc-icon` gray base + color override](../../../packages/schemas/assets/src/styles/components/icon.css) | [`components/icon.css`](../../../vendor/filament/schemas/resources/css/components/icon.css) | done | — | — |
| [`actions.css` — `.sy-sc-actions` column + `.sy-sc-actions-ctn` row](../../../packages/schemas/assets/src/styles/components/actions.css) | [`components/actions.css`](../../../vendor/filament/schemas/resources/css/components/actions.css) | in progress | — | [diverges](#actions-css-folds-in-the-actions-package-row-stylesheet-and-drops-the-sticky-and-vertical-alignment-variants) |
| [`text.css` — `.sy-sc-text` (lives in the support package)](../../../packages/support/assets/src/styles/components/text.css) | [`components/text.css`](../../../vendor/filament/schemas/resources/css/components/text.css) | done | — | [diverges](#text-css-was-ported-into-the-support-package) |
| `flex.css` / `form.css` / `fused-group.css` / `image.css` / `tabs.css` / `unordered-list.css` / `wizard.css` | [`components/`](../../../vendor/filament/schemas/resources/css/components/) | blocked | Flex, Form, FusedGroup, Image, Tabs, UnorderedList, Wizard | [detail](#the-seven-remaining-stylesheets-are-unported) |

No test exercises CSS. The render tests assert that the class hooks these rules
attach to (`sy-sc`, `sy-sc-section`, `sy-sc-section-label-hidden`, `sy-sc-icon`,
`sy-sc-actions-ctn`, `sy-color`, `sy-grid-*`) are emitted into the HTML, but the styling itself never
runs — CSS cannot reach **done** through a behavior test, so its status rests on
faithfulness of the port. The `pnpm build` (clean → tsc → esbuild → tailwind, with
a `.sy`-scoped preflight) compiles them; the only guard is the CI check that the
committed `assets/dist` matches a fresh build.

## Details

### The seven remaining stylesheets are unported

`flex.css`, `form.css`, `fused-group.css`, `image.css`, `tabs.css`,
`unordered-list.css`, and `wizard.css` have no `sy-sc-*` counterpart yet. Each is
**blocked** on its component (Flex, Form, FusedGroup, Image, Tabs,
UnorderedList, Wizard) — the styling lands in the same change as the Twig template
it targets, so there is nothing to author against until then. Next step for each:
port when its component does.

## Divergences

### Actions CSS folds in the actions-package row stylesheet and drops the sticky and vertical-alignment variants

Filament splits the Actions component's styling across two packages: schemas'
`components/actions.css` (`.fi-sc-actions` column, the label container, the
`.fi-sticky` bottom-pinning variant, `fi-vertical-align-*`) and the **actions**
package's `actions.css` (the `.fi-ac` row: base gap, wrap + centering when not
full-width, the `fi-align-end`/`fi-align-right` `flex-row-reverse` order flip,
the full-width auto-fit grid). Sylament merges both into schemas'
[`actions.css`](../../../packages/schemas/assets/src/styles/components/actions.css)
next to the only component emitting the classes — the same
tracks-its-emitter reasoning as text.css, in the other direction. The row
container is classed `sy-sc-actions-ctn`, not a `fi-ac` translation, because
bare `sy-ac` is already the action trigger marker (see the
[actions.md divergence](actions.md#the-actions-row-container-class-is-sy-sc-actions-ctn-not-a-fi-ac-translation));
the `flex-row-reverse` end/right flip is copied verbatim, not "fixed" to
`justify-end`. Dropped variants: `.fi-sticky` (no Stimulus sticky controller
yet) and the `fi-vertical-align-*` set (support `HasVerticalAlignment`
unported) — both return with their component rows in `actions.md`.

### Schema CSS drops the fi-width max-width variants and the hidden rule

Filament's `schema.css` defines fifteen `.fi-grid-col.fi-width-*` max-width variants
(`3xs`…`7xl`, `none`, `container`) and a `& > .fi-hidden { hidden }` rule. Sylament's
`schema.css` keeps `text-sm`, inline/growable, gap/dense, the alignment set, the
action-wrapper rule (`.sy-inline > .sy-sc-action { contents }` — without Filament's
`:not(.fi-hidden)` guard, because hidden children are never emitted into the DOM),
and keeps `& > .sy-grid-col > .sy-sc-component { h-full }` (vendor's
`schema.css:108`, inside the same `& > .fi-grid-col`) — but omits all width
variants (`HasMaxWidth` on grid columns is unported) and the `fi-hidden` rule
(no hidden placeholders are rendered — see the
[schema.md divergence](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders)).
Reverses as `HasMaxWidth` lands and if hidden placeholders arrive with the
live-component morphing strategy.

### Section CSS adds the sr-only hidden-label rule

Sylament's `section.css` adds `.sy-sc-section-label-hidden { @apply sr-only }`,
which Filament's `section.css` has no equivalent for. It is the styling side of
the deliberate divergence where a hidden section label stays in the DOM as
screen-reader-only text (Filament drops it entirely); documented and asserted in
`SectionRenderTest`.

### Section CSS adds a dark-mode text color to the content container

Sylament's support `section.css` applies
`.sy-section-content-ctn { @apply dark:text-white }`, which Filament's
`section.css` has no counterpart for — Filament colors only the label container
(`.fi-sc-section-label-ctn`) and lets the content inherit its base text color from
the panels layout. With no layout layer ported yet, the section CSS supplies it
locally on the content container, dark mode only; light mode still inherits the
host app. Reverses when a panels/layout layer owns base typography.

### Text CSS was ported into the support package

Filament ships `components/text.css` under schemas, but Sylament's copy lives at
[`packages/support/assets/src/styles/components/text.css`](../../../packages/support/assets/src/styles/components/text.css)
(`.sy-sc-text` with `sy-font-*`/`sy-size-*` rules), not under
`packages/schemas/assets/src/styles`. It tracks where the Text render styling is
shared with support-level Twig components — note it when auditing schemas styles so
the file is not reported missing. The port also drops the badge branch and the
copyable focus-outline rule, both unported on the Text template.

_See also: the global [rename table](../renames.md) — the `fi-*` → `sy-sc-*` class
prefix is a systematic translation recorded there, not a per-stylesheet divergence._
