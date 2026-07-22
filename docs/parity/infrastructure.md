# Cross-cutting infrastructure gaps

Component parity files link these anchors instead of restating blockers. When an
item lands, grep the repo for `infrastructure.md#<anchor>` to find everything it
unblocks.

## stimulus-controllers

Ports of Filament's Alpine behaviors to Stimulus. Section collapse has landed
and establishes the pattern: controllers live in the support assets workspace
(`packages/support/assets/src/controllers/<name>_controller.ts`), are
registered in the workspace `package.json` `symfony.controllers` block
(identifiers derive as `sylament--support--<name>`), receive server config via
Stimulus values, listen for window events via `@window` action descriptors,
and render the initial state server-side — hide-before-init only where the
truth is client-side (persisted state), via the generic `sy-cloak` class
(`base.css`, gated on `@media (scripting: enabled)` so JS-off keeps content
reachable), revealed by the shared `uncloak()` helper
(`assets/src/utils/cloak.ts`) that the owning controller calls from
`connect()` once its initial state is applied. Templates author the wiring
with StimulusBundle's Twig helpers —
`stimulus_controller()`/`stimulus_action()`/`stimulus_target()` chains spread
into the `attributes.defaults()`/`html_attr()` maps with Twig's `...`
operator (`StimulusAttributes` is iterable; null controller values are
skipped, so conditional attributes need no ternaries) — which makes
`symfony/stimulus-bundle` a hard production dependency of `support` (declared
there; the helper functions resolve at Twig compile time, so templates fail
without the bundle even when nothing is collapsible). Remaining under this
anchor: the `HasExtraAlpineAttributes`
equivalent (extra Stimulus attributes on components); future behavior ports
(tabs, wizard, dropdown, tooltip, key bindings, …); and a JS unit-test harness
decision — controllers are currently covered by `tsc --noEmit` strict + Biome
plus rendered-attribute assertions in the Twig render tests, not by JS-side
behavioral tests.

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
