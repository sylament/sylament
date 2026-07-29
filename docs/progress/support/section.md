# Section (support)

The support-level Section Twig component —
`packages/support/templates/components/Section/index.html.twig`, the port of
Filament's `filament::section` Blade view. It renders the section chrome (header
with icon/heading/description/after-header, collapsible disclosure, content and
footer containers) and nothing schema-aware: it is a presentational widget that
takes literal props. The schemas `Section` component wraps it — its template
embeds `<twig:SylamentSupport:Section>` and feeds it computed values — so this
file tracks only the support widget, not the schemas component of the same name.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php)
  — the Blade view this template ports, prop for prop and branch for branch

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details), and/or
**diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Header + body structure](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | — |
| [Icon rendering (color + size)](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | — |
| [Variant + state classes](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | — |
| [Collapsible disclosure](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#the-collapsed-state-is-rendered-server-side-not-bound-client-side) |
| [Element ids + ARIA landmark](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#element-ids-and-aria-landmark-wiring-are-rendered-from-server-computed-props) |
| [Disclosure labels](../../../packages/support/templates/components/Section/index.html.twig) | [`section/index.blade.php`](../../../vendor/filament/support/resources/views/components/section/index.blade.php) | done | — | [diverges](#the-expandcollapse-labels-are-english-prop-defaults-not-translations) |
| [Live-state reset wiring](../../../packages/support/templates/components/Section/index.html.twig) | — | in progress | — | [detail](#details) · [diverges](#the-live-component-state-reset-wiring-is-a-sylament-addition) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**.

### Live-state reset wiring

The template carries `liveComponentId` and `schemaKey` props (both defaulting to
`null`) and, when a collapsible section is **not** persisting its state and both
are set, appends a `resetState` Stimulus action bound to
`reset-schema-component-state@window`:

```twig
{% if sectionStimulus and not persistCollapsed and liveComponentId and schemaKey %}
    {% set sectionStimulus = sectionStimulus|stimulus_action(sectionController, 'resetState', 'reset-schema-component-state@window') %}
{% endif %}
```

**In:** the branch and the two props exist and evaluate. **Missing:** nothing
supplies them — the schemas `Section` template passes neither
([`Section/index.html.twig`](../../../packages/schemas/templates/partials/Section/index.html.twig)
threads `collapseId`, ids and flags but no `liveComponentId`/`schemaKey`), so the
branch is never taken. `SectionRenderTest` only asserts the wiring is **absent**
(`->not->toContain('reset-schema-component-state')`, line 469); the positive path
has no render test. **Next step:** land it with the first live-component-state
consumer that populates the two props, adding a render test that sets them and
asserts the `resetState` action appears — or, if the epic reshapes the contract,
drop the forward stub. See the [divergence](#the-live-component-state-reset-wiring-is-a-sylament-addition).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### Element ids and ARIA landmark wiring are rendered from server-computed props

Filament's support section is deliberately "dumb" about ids: it mints only the
content-container id, client-side, via Alpine's `x-id` / `$id('fi-section-content')`,
and leaves `aria-labelledby` / `aria-describedby` and the heading/description ids
to its schemas-layer wrapper. Sylament has no Alpine to mint ids in the browser,
so the support template instead accepts `headingId`, `descriptionId`, `contentId`
and `labelledById` as props — computed server-side by the schemas `Section` and
threaded in — and renders the `id=…`, `aria-labelledby` and `aria-describedby`
attributes itself. The prop contract is wider than Filament's, and the ARIA
landmark wiring lives one layer lower.

_See also:_ the [rename table](../renames.md) — the Alpine → Stimulus
translation this follows from is systematic, recorded there.

### The collapsed state is rendered server-side, not bound client-side

Filament never emits `fi-collapsed` on the server: the class is applied by Alpine
(`x-bind:class="isCollapsed && 'fi-collapsed'"`), so the initial paint is always the
expanded markup and the content container is `x-cloak`ed whenever the section starts
collapsed (`@if ($collapsed || $persistCollapsed) x-cloak`) to hide the flash until
Alpine settles the state. Sylament renders `sy-collapsed` server-side
(`'sy-collapsed': collapsible and collapsed`), so an initially-collapsed section is
already correct on first paint — no flash, no cloak. The only case that can still
flash is a persisted section, whose localStorage value may override the server
default on connect, so `sy-cloak` is emitted for `collapsible and persistCollapsed`
alone — narrower than Filament's condition. Pinned by `SectionRenderTest`, which
asserts `sy-cloak` absent for a plain collapsible section (line 502) and present only
under `persistCollapsed` (line 533).

_See also:_ the [rename table](../renames.md) — the Alpine → Stimulus
translation this follows from is systematic, recorded there.

### The expand/collapse labels are English prop defaults, not translations

Filament resolves the disclosure button's labels inline with
`__('filament::components/section.actions.expand.label')` /
`…collapse.label`, pulling from its translation catalogues. Sylament has no
translation layer ported yet, so the template exposes `expandLabel` /
`collapseLabel` props defaulting to the literal English strings
`'Expand section'` / `'Collapse section'`. Callers can override them, but there
is no locale lookup. Revisit when the `support` translations
(`resources/lang/`) are ported.

### The live-component-state reset wiring is a Sylament addition

The `liveComponentId` / `schemaKey` props and the
`reset-schema-component-state@window → resetState` Stimulus action have no
counterpart in Filament's section view — they are forward wiring for the
live-component-state epic. The branch is currently unreachable (no caller sets
the props) and untested on its positive path, which is why the row is **in
progress** rather than done; see its [detail](#details).
