# Styles (schemas)

The schemas bundle's component CSS —
[`packages/schemas/assets/src/styles/`](../../../packages/schemas/assets/src/styles/) —
ports Filament's [`schemas/resources/css/`](../../../vendor/filament/schemas/resources/css/).
Each stylesheet is `@apply`-authored into `components/<name>.css` and `@import`ed
into [`index.css`](../../../packages/schemas/assets/src/styles/index.css) under
`layer(components)`, mirroring Filament's `index.css` structure. The `fi-*` class
prefix is translated to the schemas component code `sy-sc-*` (see the
[CSS contract](../../internals/styling.md)). Seven of Filament's stylesheets are ported so
far (schema, section, icon, actions, flex, tabs, wizard) — `icon.css`, `flex.css` and
`tabs.css` rule-for-rule, `schema.css` and `actions.css` dropping variants from
their source, and `section.css` and `wizard.css` adding rules; the rest wait on
the components they style.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/resources/css/schema.css`](../../../vendor/filament/schemas/resources/css/schema.css)
  — the grid/gap/alignment stylesheet ported into `components/schema.css`
- [`schemas/resources/css/components/`](../../../vendor/filament/schemas/resources/css/components/)
  — the eleven per-component stylesheets: six ported here (actions, flex, icon,
  section, tabs, wizard), two ported into the package that emits their classes
  (text into support, fused-group into forms), and three unported (form, image,
  unordered-list)

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
| [`fused-group.css` — `.sy-fo-fused-group` ring, dividers and corner rules (lives in the forms package)](../../../packages/forms/assets/src/styles/components/fused-group.css) | [`components/fused-group.css`](../../../vendor/filament/schemas/resources/css/components/fused-group.css) | done | — | [diverges](#fused-group-css-was-ported-into-the-forms-package) |
| [`flex.css` — `.sy-sc-flex` row, breakpoint, alignment, dense and growable variants](../../../packages/schemas/assets/src/styles/components/flex.css) | [`components/flex.css`](../../../vendor/filament/schemas/resources/css/components/flex.css) | done | — | [diverges](#flex-css-keeps-the-hidden-child-rule-the-port-never-emits) |
| [`tabs.css` — `.sy-sc-tabs` panel show/hide, contained and vertical variants](../../../packages/schemas/assets/src/styles/components/tabs.css) | [`components/tabs.css`](../../../vendor/filament/schemas/resources/css/components/tabs.css) | done | — | — |
| [`wizard.css` — `.sy-sc-wizard` header stepper, panels and footer](../../../packages/schemas/assets/src/styles/components/wizard.css) | [`components/wizard.css`](../../../vendor/filament/schemas/resources/css/components/wizard.css) | done | — | [diverges](#wizard-css-writes-the-x-show-pairs-and-the-mis-nested-label-rule-as-real-rules) |
| `form.css` / `image.css` / `unordered-list.css` | [`components/`](../../../vendor/filament/schemas/resources/css/components/) | blocked | Form, Image, UnorderedList | [detail](#the-remaining-component-stylesheets-are-unported) |

No test exercises CSS. The render tests assert that the class hooks these rules
attach to (`sy-sc`, `sy-sc-section`, `sy-sc-section-label-hidden`, `sy-sc-icon`,
`sy-sc-actions-ctn`, `sy-sc-tabs`, `sy-sc-tabs-tab`, `sy-color`, `sy-grid-*`) are emitted into the HTML, but the styling itself never
runs — CSS cannot reach **done** through a behavior test, so its status rests on
faithfulness of the port. The `pnpm build` (clean → tsc → esbuild → tailwind, with
a `.sy`-scoped preflight) compiles them; the only guard is the CI check that the
committed `assets/dist` matches a fresh build.

## Details

### The remaining component stylesheets are unported

`form.css`, `image.css` and `unordered-list.css` have no `sy-sc-*` counterpart yet. Each is
**blocked** on its component (Form, Image,
UnorderedList) — the styling lands in the same change as the Twig template
it targets, so there is nothing to author against until then. Next step for each:
port when its component does.

## Divergences

### Flex CSS keeps the hidden-child rule the port never emits

`flex.css` is Filament's file with `fi-` → `sy-`, including
`& > .sy-hidden { @apply hidden; }`. Nothing in this port stamps that class on a
Flex child: [`Flex`](flex.md) filters hidden children server-side
(`getComponents(withHidden: false)`), and Filament only adds it from the Alpine
`x-bind:class` of the [unported JS-visibility arm](flex.md#the-per-child-alpine-visibility-and-state-wiring-is-not-ported).
The rule is kept rather than deleted so the stylesheet stays a straight prefix
swap of the vendor file — it costs one dead selector and returns to life the
moment that arm lands.

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

### Fused-group CSS was ported into the forms package

The same shape one file over, for a stronger reason: the component it styles
[ships in forms](../forms/fused-group.md#fusedgroup-lives-in-forms-not-schemas),
so its stylesheet went with it —
[`packages/forms/assets/src/styles/components/fused-group.css`](../../../packages/forms/assets/src/styles/components/fused-group.css),
imported by forms'
[`index.css`](../../../packages/forms/assets/src/styles/index.css). The rules are
vendor's whole, including every breakpoint arm, and the class code follows the
package: `.fi-sc-fused-group` becomes `.sy-fo-fused-group`, while every class the
rules **reach into** keeps the code of the package that emits it (`.sy-sc`,
`.sy-sc-component`, `.sy-sc-actions` from schemas, `.sy-input-wrp` from support,
`.sy-fo-field` / `.sy-fo-input` from forms). Note it when auditing schemas styles
so the file is not reported missing.

_See also: the global [rename table](../renames.md) — the `fi-*` → `sy-sc-*` class
prefix is a systematic translation recorded there, not a per-stylesheet divergence._

### Wizard CSS writes the x-show pairs and the mis-nested label rule as real rules

`wizard.css` is vendor's rule-for-rule with the `fi-` → `sy-` prefix swap and
three deliberate edits, all consequences of the
[server-rendered start step](wizard.md#the-server-renders-the-start-step-alpines-x-show-pairs-become-state-classes-and-css).
Filament shows and hides the completed tick against the number/icon with a pair of
`x-show` expressions in the markup; here both live in every header row and the
stylesheet does the switching —
`&:not(.sy-completed) .sy-sc-wizard-header-step-completed-icon { hidden }` and
`&.sy-completed .sy-sc-wizard-header-step-icon, &.sy-completed
.sy-sc-wizard-header-step-number { hidden }`. The two screen-reader-only classes
the markup emits get `@apply sr-only` inline
(`.sy-sc-wizard-header-step-label-hidden`, `.sy-sc-wizard-header-step-status`)
rather than a `sy-sr-only` utility, matching `section.css`. And vendor's
inactive-label rule (`wizard.css:115-119`) nests
`.fi-sc-wizard-header-step-text` **inside** `.fi-sc-wizard-header-step-icon-ctn`,
where the text is that element's sibling, so it matches nothing; it is written at
the `-text` level as intended rather than copied dead. Reviving it costs one
guard vendor never needed: a live rule at that level also matches a **completed**
step (completed implies not active) and, emitted after the completed block at
equal specificity, would win and fade a completed label to the upcoming-step
gray. The selector is therefore `&:not(.sy-active):not(.sy-completed)`, which
makes the three label states — active, completed, upcoming — mutually exclusive
by construction rather than by source order.

