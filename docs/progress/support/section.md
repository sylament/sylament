---
description: The presentational `<twig:SylamentSupport:Section>` Twig component, its collapse controller and stylesheet, the port of Filament's `filament::section` view.
ports:
  - vendor/filament/support/resources/views/components/section/index.blade.php
  - vendor/filament/support/resources/views/components/section/heading.blade.php
  - vendor/filament/support/resources/views/components/section/description.blade.php
  - vendor/filament/support/resources/css/components/section.css
owns:
  - packages/support/assets/src/controllers/section_controller.ts
  - packages/support/assets/src/styles/components/section.css
  - packages/support/templates/components/Section/
---
# Section (support)

[`Section/index.html.twig`](../../../packages/support/templates/components/Section/index.html.twig) is the support-level Section Twig component, the port of Filament's `filament::section` Blade view.
It renders the section chrome (header with icon, heading, description and after-header, collapsible disclosure, content and footer containers) and nothing schema-aware: it is a presentational widget that takes literal props.
The schemas [`Section`](../schemas/section.md) component wraps it, embedding `<twig:SylamentSupport:Section>` and feeding it computed values, so this file tracks only the support widget.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Header + body structure](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php), with [`section/heading`](../../../vendor/filament/support/resources/views/components/section/heading.blade.php) and [`section/description`](../../../vendor/filament/support/resources/views/components/section/description.blade.php) rendered inline | done | — | — |
| [Icon rendering (color + size)](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | — |
| [Variant + state classes](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | — |
| [Collapsible disclosure](../../../packages/support/assets/src/controllers/section_controller.ts) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#the-collapsed-state-is-rendered-server-side-not-bound-client-side) |
| [Element ids + ARIA landmark](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#element-ids-and-aria-landmark-wiring-are-rendered-from-server-computed-props) |
| [Disclosure labels](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#the-expand-and-collapse-labels-are-props-defaulting-to-the-catalogue-and-the-controller-holds-no-text) |
| [Live-state reset wiring](../../../packages/support/templates/components/Section/index.html.twig) | — | in progress | — | [detail](#the-live-state-reset-branch-has-no-caller) · [diverges](#the-live-component-state-reset-wiring-is-a-sylament-addition) |
| [`section.css`](../../../packages/support/assets/src/styles/components/section.css) | [`components/section.css`](../../../vendor/filament/support/resources/css/components/section.css) | done | — | [diverges](#section-css-adds-a-dark-mode-text-color-to-the-content-container) · [diverges](#the-after-header-text-leading-rule-is-written-in-supports-stylesheet) |

## Details

### The live-state reset branch has no caller

The template carries `liveComponentId` and `schemaKey` props, both defaulting to `null`, and, when a collapsible section is not persisting its state and both are set, appends a `resetState` Stimulus action bound to `reset-schema-component-state@window`:

```twig
{% if sectionStimulus and not persistCollapsed and liveComponentId and schemaKey %}
    {% set sectionStimulus = sectionStimulus|stimulus_action(sectionController, 'resetState', 'reset-schema-component-state@window') %}
{% endif %}
```

**In:** the branch, the two props and the controller's `resetState()` exist.
**Missing:** nothing supplies the props: the schemas `Section` template ([`Section/index.html.twig`](../../../packages/schemas/templates/partials/Section/index.html.twig)) threads `collapseId`, ids and flags but no `liveComponentId` or `schemaKey`, so the branch is never taken.
Until then a section renders no reset action.
**Next step:** land it with the first live-component-state consumer that populates the two props, or drop the forward stub if live-component state reshapes the contract.

## Divergences

### Element ids and ARIA landmark wiring are rendered from server-computed props

Filament's support section mints only the content-container id, client-side, through Alpine's `x-id` / `$id('fi-section-content')`, and leaves `aria-labelledby` / `aria-describedby` and the heading and description ids to its schemas-layer wrapper.
Sylament has no Alpine to mint ids in the browser, so the support template accepts `headingId`, `descriptionId`, `contentId` and `labelledById` as props, computed server-side by the schemas `Section` and threaded in, and renders the `id=…`, `aria-labelledby` and `aria-describedby` attributes itself.
The prop contract is wider than Filament's, and the ARIA landmark wiring lives one layer lower.
It follows from the systematic Alpine → Stimulus [translation](../translations.md#idiom).

### The collapsed state is rendered server-side, not bound client-side

Filament never emits `fi-collapsed` on the server: Alpine applies the class (`x-bind:class="isCollapsed && 'fi-collapsed'"`), so the initial paint is always the expanded markup, and the content container is `x-cloak`ed whenever the section starts collapsed (`@if ($collapsed || $persistCollapsed) x-cloak`) to hide the flash until Alpine settles the state.
Sylament renders `sy-collapsed` server-side (`'sy-collapsed': collapsible and collapsed`), so an initially collapsed section is correct on first paint, with no flash and no cloak.
The only case that can still flash is a persisted section, whose localStorage value may override the server default on connect, so `sy-cloak` is emitted for `collapsible and persistCollapsed` alone, a narrower condition than Filament's.
It is pinned: `sy-cloak` is absent on a collapsible section that persists nothing and present under `persistCollapsed`.

### The expand and collapse labels are props defaulting to the catalogue, and the controller holds no text

Filament resolves the disclosure button's labels inline with `__('filament::components/section.actions.expand.label')` / `…collapse.label`.
The template exposes `expandLabel` / `collapseLabel` props whose defaults translate Filament's key paths, `components/section.actions.expand.label` and `components/section.actions.collapse.label`, from the `sylament` catalogue, in Filament's English.
A caller can pass either prop to replace the catalogue's text.
Filament keys the pair twice, under `filament-schemas::components.section.actions.*` and `filament::components/section.actions.*`, and the port reads only the `sylament` pair, so a Filament locale's `filament-schemas` pair is imported onto the `sylament` key, and an override in `sylament-schemas` never reaches the widget.
The button's `aria-label` and the controller's `expandLabel` / `collapseLabel` Stimulus values are rendered from the same two props, so the controller, which rewrites the `aria-label` on every toggle, reads both texts from them ([i18n.md](../../internals/i18n.md#a-count-the-browser-updates-reaches-it-as-a-map-of-plural-forms)): the server's locale at render time fixes both texts, and a client-side locale change needs a re-render.

### The live-component-state reset wiring is a Sylament addition

The `liveComponentId` / `schemaKey` props and the `reset-schema-component-state@window → resetState` Stimulus action have no counterpart in Filament's section view: they are forward wiring for a live-component-state consumer.
The branch has no caller, which is why the row is in progress rather than done; see its [detail](#the-live-state-reset-branch-has-no-caller).

### Section CSS adds a dark-mode text color to the content container

Support's `section.css` applies `.sy-section-content-ctn { @apply dark:text-white }`, which Filament's support `section.css` has no counterpart for: Filament colors only the schemas label container and lets the content inherit its base text color from the panels layout.
Support's Section also renders outside panels' layout, whose root sets `dark:text-white` ([`layout.css`](../../../packages/panels/assets/src/styles/components/layout.css)), so the stylesheet supplies the color locally on the content container, dark mode only; light mode inherits the host app.

### The after-header text leading rule is written in support's stylesheet

Filament's `leading-6` for a `.fi-sc-text` inside the after-header container is a rule of the schemas package's `section.css`, nested under `.fi-section > .fi-section-header > .fi-section-header-content > .fi-section-header-after-ctn`, and its support `section.css` gives that container's `leading-6` to `.fi-link` alone.
Support's `section.css` writes both selectors in the one rule, `.sy-sc-text` beside `.sy-link`, under the same `.sy-section-header-content > .sy-section-header-after-ctn` path, so the rendered result matches Filament's.
Schemas' [Text](../schemas/text.md) partial emits `.sy-sc-text`, which is why a class of a schemas component is named in support; its stylesheet lives in support too ([styles](../schemas/styles.md#text-css-was-ported-into-the-support-package)).
