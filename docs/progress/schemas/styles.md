# Styles (schemas)

The schemas bundle's component CSS —
[`packages/schemas/assets/src/styles/`](../../../packages/schemas/assets/src/styles/) —
ports Filament's [`schemas/resources/css/`](../../../vendor/filament/schemas/resources/css/).
Each stylesheet is `@apply`-authored into `components/<name>.css` and `@import`ed
into [`index.css`](../../../packages/schemas/assets/src/styles/index.css) under
`layer(components)`, mirroring Filament's `index.css` structure. The `fi-*` class
prefix is translated to the schemas component code `sy-sc-*` (see the
[CSS contract](../../styling.md)). Three of Filament's stylesheets are ported so
far (schema, section, icon), each dropping variants from its source; the rest wait
on the components they style.

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
| [`schema.css` — `.sy-sc` grid/inline/gap/dense/alignment](../../../packages/schemas/assets/src/styles/components/schema.css) | [`schema.css`](../../../vendor/filament/schemas/resources/css/schema.css) | in progress | — | [diverges](#schema-css-drops-the-fi-width-max-width-variants-and-the-hidden-and-action-rules) |
| [`section.css` — `.sy-sc-section` label container](../../../packages/schemas/assets/src/styles/components/section.css) | [`components/section.css`](../../../vendor/filament/schemas/resources/css/components/section.css) | done | — | [diverges](#section-css-adds-the-sr-only-hidden-label-rule) |
| [`icon.css` — `.sy-sc-icon` color override](../../../packages/schemas/assets/src/styles/components/icon.css) | [`components/icon.css`](../../../vendor/filament/schemas/resources/css/components/icon.css) | in progress | — | [diverges](#icon-css-drops-the-default-gray-base-color) |
| [`text.css` — `.sy-sc-text` (lives in the support package)](../../../packages/support/assets/src/styles/components/text.css) | [`components/text.css`](../../../vendor/filament/schemas/resources/css/components/text.css) | done | — | [diverges](#text-css-was-ported-into-the-support-package) |
| `actions.css` / `flex.css` / `form.css` / `fused-group.css` / `image.css` / `tabs.css` / `unordered-list.css` / `wizard.css` | [`components/`](../../../vendor/filament/schemas/resources/css/components/) | blocked | Actions, Flex, Form, FusedGroup, Image, Tabs, UnorderedList, Wizard | [detail](#the-eight-remaining-stylesheets-are-unported) |

No test exercises CSS. The render tests assert that the class hooks these rules
attach to (`sy-sc`, `sy-sc-section`, `sy-sc-section-label-hidden`, `sy-sc-icon`,
`sy-color`, `sy-grid-*`) are emitted into the HTML, but the styling itself never
runs — CSS cannot reach **done** through a behavior test, so its status rests on
faithfulness of the port. The `pnpm build` (clean → tsc → esbuild → tailwind, with
a `.sy`-scoped preflight) compiles them; the only guard is the CI check that the
committed `assets/dist` matches a fresh build.

## Details

### The eight remaining stylesheets are unported

`actions.css`, `flex.css`, `form.css`, `fused-group.css`, `image.css`, `tabs.css`,
`unordered-list.css`, and `wizard.css` have no `sy-sc-*` counterpart yet. Each is
**blocked** on its component (Actions, Flex, Form, FusedGroup, Image, Tabs,
UnorderedList, Wizard) — the styling lands in the same change as the Twig template
it targets, so there is nothing to author against until then. Next step for each:
port when its component does.

## Divergences

### Schema CSS drops the fi-width max-width variants and the hidden and action rules

Filament's `schema.css` defines fifteen `.fi-grid-col.fi-width-*` max-width variants
(`3xs`…`7xl`, `none`, `container`), a `& > .fi-hidden { hidden }` rule, and
`.fi-inline & > .fi-sc-action:not(.fi-hidden) { contents }`. Sylament's
`schema.css` keeps `text-sm`, inline/growable, gap/dense, the alignment set, and
adds `& > .sy-grid-col > .sy-sc-component { h-full }` — but omits all width variants
(`HasMaxWidth` on grid columns is unported), the `fi-hidden` rule (Sylament never
emits hidden children into the DOM), and the action rule (Actions unported).
Reverses as `HasMaxWidth` and Actions land.

### Icon CSS drops the default gray base color

Filament's `components/icon.css` sets `.fi-sc-icon { text-gray-400 dark:text-gray-500 }`
as the default and lets `.fi-color` override it. Sylament's `icon.css` keeps only
the `&.sy-color { text-color-500 dark:text-color-400 }` override and omits the
default-gray base, so a gray schema Icon inherits color rather than getting the
explicit gray token. Small fidelity gap to close.

### Section CSS adds the sr-only hidden-label rule

Sylament's `section.css` adds `.sy-sc-section-label-hidden { @apply sr-only }`,
which Filament's `section.css` has no equivalent for. It is the styling side of
the deliberate divergence where a hidden section label stays in the DOM as
screen-reader-only text (Filament drops it entirely); documented and asserted in
`SectionRenderTest`.

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
