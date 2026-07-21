# Cross-cutting infrastructure gaps

Component parity files link these anchors instead of restating blockers. When an
item lands, grep the repo for `infrastructure.md#<anchor>` to find everything it
unblocks.

## icon-system

Support-level icon rendering: a `generate_icon_html` equivalent, icon aliases,
size/color enums, an icon-button component. Unblocks: Section header icons and
the collapse chevron button.

## stimulus-controllers

Ports of Filament's Alpine behaviors to Stimulus (only `core_controller.ts`
exists today). First needed: section collapse (v5 semantics) — toggle state;
id-matched window events (`collapse-section`, `expand-section`, `open-section`,
`toggle-section`) plus an element-level `expand` event; a
`reset-schema-component-state` window listener restoring the initial collapse
state (matched by live-component id + root schema key, skipped when
persisting); persistence keyed `section-<id>-isCollapsed`; disclosure ARIA on
the chevron button (translated expand/collapse `aria-label`, `aria-expanded`,
`aria-controls` pointing at the content-container id — the control carries the
state, not the region); hide-before-init. Also the `HasExtraAlpineAttributes`
equivalent (extra Stimulus attributes on components).

## live-component-state

The schema state system on UX LiveComponent — the Livewire-backed heart of
Filament's `Component` that everything stateful hangs off: `HasState` (state
paths, hydration/dehydration hooks, `afterStateUpdated()` + JS variant, state
casts, get/set utilities, `$state`/`$rawState`/`$get`/`$set` closure
parameters), `HasStateBindingModifiers` (`live()`, `reactive()`, `lazy()`,
`debounce()`), `CanPoll`, `CanPartiallyRender`, and the `toSchemaHtml()`
per-child wrapper (`wire:key`, JS visibility, partial-render targeting).
Also completes the ported keys: the state-path fallbacks inside
`HasKey::getKey()` / `getInheritanceKey()` (component and schema level),
`getLivewireKey()` on both levels (needs a live-component id concept on
`InteractsWithSchemas`), and the `::{statePath}::` middle segment of Section's
auto-key. Blocks most of the base-Component surface in
[schemas/component.md](schemas/component.md) and every future form field.

## translation

A Symfony equivalent of Laravel's `__()` for `translateLabel()`-style APIs —
translator access from non-DI components (likely another `Invoker`/static-
instance-shaped problem).

## actions-package

The `actions` bundle itself. Unblocks: Section `headerActions()` /
`footerActions()` (+ footer alignment) and the `setUp()` seeding of the
`after_header` / `footer` slots with those action defaults; Schema's
`CanModifyActions` (`modifyActionsUsing()` / `modifyActionGroupsUsing()`,
applied when `getComponents()` materializes the list) and Section's
`configureChildSchema()` small/link-styled action defaults for the
label/content slots; `Action` / `ActionGroup` values in the
`HasComponents::components()` and child-slot unions.
