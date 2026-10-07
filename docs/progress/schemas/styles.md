---
description: The schemas bundle's component stylesheets under packages/schemas/assets/src/styles/, ported from Filament's schemas resources/css/.
ports:
  - vendor/filament/schemas/resources/css/index.css
  - vendor/filament/schemas/resources/css/schema.css
  - vendor/filament/schemas/resources/css/components/actions.css
  - vendor/filament/schemas/resources/css/components/flex.css
  - vendor/filament/schemas/resources/css/components/form.css
  - vendor/filament/schemas/resources/css/components/icon.css
  - vendor/filament/schemas/resources/css/components/image.css
  - vendor/filament/schemas/resources/css/components/section.css
  - vendor/filament/schemas/resources/css/components/text.css
  - vendor/filament/schemas/resources/css/components/unordered-list.css
  - vendor/filament/actions/resources/css/actions.css
owns:
  - packages/schemas/assets/src/styles/components/actions.css
  - packages/schemas/assets/src/styles/components/flex.css
  - packages/schemas/assets/src/styles/components/icon.css
  - packages/schemas/assets/src/styles/components/schema.css
  - packages/schemas/assets/src/styles/components/section.css
  - packages/support/assets/src/styles/components/text.css
---
# Styles (schemas)

The schemas bundle's component CSS in [`packages/schemas/assets/src/styles/`](../../../packages/schemas/assets/src/styles/) ports Filament's [`schemas/resources/css/`](../../../vendor/filament/schemas/resources/css/).
Each stylesheet is `@apply`-authored into `components/<name>.css` and `@import`ed into [`index.css`](../../../packages/schemas/assets/src/styles/index.css) under `layer(components)`, mirroring Filament's `index.css`.
The `fi-*` class prefix becomes the schemas component code `sy-sc-*` (the [CSS contract](../../internals/styling.md#why-do-templates-carry-only-semantic-classes)).
The stylesheets of `Tabs`, `Wizard` and forms' `FusedGroup` are rows in [tabs.md](tabs.md), [wizard.md](wizard.md) and [fused-group.md](../forms/fused-group.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`index.css`](../../../packages/schemas/assets/src/styles/index.css), the aggregator | [`index.css`](../../../vendor/filament/schemas/resources/css/index.css) | done | — | [semantics](#a-rows-status-reads-the-vendor-stylesheet-and-no-test-renders-css) |
| [`schema.css`](../../../packages/schemas/assets/src/styles/components/schema.css): `.sy-sc` grid, inline, gap, dense, alignment, action | [`schema.css`](../../../vendor/filament/schemas/resources/css/schema.css) | in progress | — | [diverges](#schema-css-drops-the-fi-width-max-width-variants-and-the-hidden-rule) |
| [`section.css`](../../../packages/schemas/assets/src/styles/components/section.css): `.sy-sc-section` label container | [`components/section.css`](../../../vendor/filament/schemas/resources/css/components/section.css) | done | — | [diverges](#section-css-adds-the-sr-only-hidden-label-rule) |
| [`icon.css`](../../../packages/schemas/assets/src/styles/components/icon.css): `.sy-sc-icon` gray base + color override | [`components/icon.css`](../../../vendor/filament/schemas/resources/css/components/icon.css) | done | — | [semantics](#a-rows-status-reads-the-vendor-stylesheet-and-no-test-renders-css) |
| [`actions.css`](../../../packages/schemas/assets/src/styles/components/actions.css): `.sy-sc-actions` column + `.sy-sc-actions-ctn` row | [`components/actions.css`](../../../vendor/filament/schemas/resources/css/components/actions.css) · [actions' `actions.css`](../../../vendor/filament/actions/resources/css/actions.css) | in progress | — | [diverges](#actions-css-folds-in-the-actions-package-row-stylesheet-and-drops-the-sticky-and-vertical-alignment-variants) |
| [`text.css`](../../../packages/support/assets/src/styles/components/text.css): `.sy-sc-text`, in the support package | [`components/text.css`](../../../vendor/filament/schemas/resources/css/components/text.css) | done | — | [diverges](#text-css-was-ported-into-the-support-package) |
| [`flex.css`](../../../packages/schemas/assets/src/styles/components/flex.css): `.sy-sc-flex` row, breakpoint, alignment, dense and growable variants | [`components/flex.css`](../../../vendor/filament/schemas/resources/css/components/flex.css) | done | — | [semantics](#flex-css-keeps-the-hidden-child-rule-the-port-never-emits) |
| `form.css` / `image.css` / `unordered-list.css` | [`components/`](../../../vendor/filament/schemas/resources/css/components/) | blocked | Form, Image, UnorderedList | [detail](#the-remaining-component-stylesheets-are-unported) |

## Details

### The remaining component stylesheets are unported

`form.css`, `image.css` and `unordered-list.css` have no `sy-sc-*` counterpart.
Each is blocked on its component (Form, Image, UnorderedList): the styling lands in the same change as the Twig template it targets, so there is nothing to author against until then.

## Semantics

### A row's status reads the vendor stylesheet, and no test renders CSS

A status in this table reads the **vendor stylesheet**: how much of its rule set has a counterpart.
No test exercises CSS: the render tests assert that the class hooks these rules attach to are emitted, but the styling itself never runs, so a row's **done** rests on the faithfulness of the port.
The only automated guard is the CI check that support's committed `assets/dist` matches a fresh build.

### Flex CSS keeps the hidden-child rule the port never emits

`flex.css` is Filament's file with `fi-` → `sy-`, including `& > .sy-hidden { @apply hidden; }`.
Nothing in this port stamps that class on a Flex child: [`Flex`](flex.md) filters hidden children server-side (`getComponents(withHidden: false)`), and Filament only adds it from the Alpine `x-bind:class` of the [unported JS-visibility arm](flex.md#the-per-child-alpine-visibility-and-state-wiring-is-not-ported).
The rule is kept so the stylesheet stays a straight prefix swap of the vendor file; it costs one dead selector and applies once that arm lands.

## Divergences

### Actions CSS folds in the actions-package row stylesheet and drops the sticky and vertical-alignment variants

Filament splits the Actions component's styling across two packages: schemas' `components/actions.css` (`.fi-sc-actions` column, the label container, the `.fi-sticky` bottom-pinning variant, `fi-vertical-align-*`) and the **actions** package's `actions.css` (the `.fi-ac` row: base gap, wrap + centering when not full-width, the `fi-align-end`/`fi-align-right` `flex-row-reverse` order flip, the full-width auto-fit grid).
Sylament merges both into schemas' [`actions.css`](../../../packages/schemas/assets/src/styles/components/actions.css) next to the only component emitting the classes.
The row container is classed `sy-sc-actions-ctn`, not a `fi-ac` translation, because bare `sy-ac` is already the action trigger marker (the [actions.md divergence](actions.md#the-actions-row-container-class-is-sy-sc-actions-ctn-not-a-fi-ac-translation)); the `flex-row-reverse` end/right flip is copied verbatim, not "fixed" to `justify-end`.
Dropped variants: `.fi-sticky` (no Stimulus sticky controller) and the `fi-vertical-align-*` set (`Actions` does not compose support's `HasVerticalAlignment`); both return with their component rows in [actions.md](actions.md#details).

### Schema CSS drops the fi-width max-width variants and the hidden rule

Filament's `schema.css` defines the `.fi-grid-col.fi-width-*` max-width variants (`3xs`…`7xl`, `none`, `container`) and a `& > .fi-hidden { hidden }` rule.
Sylament's `schema.css` keeps `text-sm`, inline/growable, gap/dense, the alignment set, the action-wrapper rule (`.sy-inline > .sy-sc-action { contents }`, without Filament's `:not(.fi-hidden)` guard because hidden children are never emitted into the DOM), and `& > .sy-grid-col > .sy-sc-component { h-full }` (vendor's `schema.css:113`, inside the same `& > .fi-grid-col`).
It omits the width variants, because schemas has no `HasMaxWidth` on grid columns, and the `fi-hidden` rule, because no hidden placeholders are rendered (the [schema.md divergence](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders)).
It reverses as `HasMaxWidth` lands, and if hidden placeholders arrive with the live-component morphing strategy.

### Section CSS adds the sr-only hidden-label rule

Sylament's `section.css` adds `.sy-sc-section-label-hidden { @apply sr-only }`, which Filament's `section.css` has no equivalent for.
It is the styling side of [the hidden label staying in the DOM](section.md#a-hidden-label-stays-in-the-dom-as-screen-reader-only-text), where Filament's section ignores `hiddenLabel()`.

### Text CSS was ported into the support package

Filament ships `components/text.css` under schemas, but Sylament's copy lives at [`packages/support/assets/src/styles/components/text.css`](../../../packages/support/assets/src/styles/components/text.css) (`.sy-sc-text` with `sy-font-*`/`sy-size-*` rules), imported by support's `components.css`, not under `packages/schemas/assets/src/styles`.
No support template emits `.sy-sc-text`: schemas' Text partial is its only emitter, and no reason for the placement is recorded.
The port also drops the badge branch and the copyable focus-outline rule, both unported on the Text template.
