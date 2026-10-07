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
| [Disclosure labels](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#the-expandcollapse-labels-are-english-prop-defaults-not-translations) |
| [Live-state reset wiring](../../../packages/support/templates/components/Section/index.html.twig) | — | in progress | — | [detail](#the-live-state-reset-branch-has-no-caller) · [diverges](#the-live-component-state-reset-wiring-is-a-sylament-addition) |
| [`section.css`](../../../packages/support/assets/src/styles/components/section.css) | [`components/section.css`](../../../vendor/filament/support/resources/css/components/section.css) | in progress | — | [detail](#two-collapsed-state-rules-differ-from-filaments-with-no-recorded-reason) · [diverges](#section-css-adds-a-dark-mode-text-color-to-the-content-container) |

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
[`SectionRenderTest`](../../../packages/schemas/tests/Integration/Components/SectionRenderTest.php) asserts only that the action is absent; the positive path has no render test.
**Next step:** land it with the first live-component-state consumer that populates the two props, adding a render test that sets them and asserts the `resetState` action appears, or drop the forward stub if live-component state reshapes the contract.

### Two collapsed-state rules differ from Filament's with no recorded reason

Apart from the prefix and the [dark-mode text colour](#section-css-adds-a-dark-mode-text-color-to-the-content-container), `section.css` follows Filament's stylesheet rule for rule except in two places.
A collapsed section's content container is `invisible absolute h-0 overflow-hidden border-none`, where Filament's is `hidden`.
The non-aside section's `grid gap-y-4` on the content container applies whether or not the section is collapsed, where Filament gates it on `:not(.fi-collapsed)`.
**Next step:** record why the collapsed body stays in the layout, as a divergence, or restore Filament's rules.

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
[`SectionRenderTest`](../../../packages/schemas/tests/Integration/Components/SectionRenderTest.php) pins it, asserting `sy-cloak` absent on a plain collapsible section and present under `persistCollapsed`.

### The expand/collapse labels are English prop defaults, not translations

Filament resolves the disclosure button's labels inline with `__('filament::components/section.actions.expand.label')` / `…collapse.label`, pulling from its translation catalogues.
Sylament ships no translation layer, so the template exposes `expandLabel` / `collapseLabel` props defaulting to the literal English strings `'Expand section'` / `'Collapse section'`.
Callers can override them, but there is no locale lookup.
It is revisited when the `support` translations (`resources/lang/`) are ported.

### The live-component-state reset wiring is a Sylament addition

The `liveComponentId` / `schemaKey` props and the `reset-schema-component-state@window → resetState` Stimulus action have no counterpart in Filament's section view: they are forward wiring for a live-component-state consumer.
The branch has no caller and no positive-path test, which is why the row is in progress rather than done; see its [detail](#the-live-state-reset-branch-has-no-caller).

### Section CSS adds a dark-mode text color to the content container

Support's `section.css` applies `.sy-section-content-ctn { @apply dark:text-white }`, which Filament's support `section.css` has no counterpart for: Filament colors only the schemas label container and lets the content inherit its base text color from the panels layout.
Support's Section also renders outside panels' layout, whose root sets `dark:text-white` ([`layout.css`](../../../packages/panels/assets/src/styles/components/layout.css)), so the stylesheet supplies the color locally on the content container, dark mode only; light mode inherits the host app.
