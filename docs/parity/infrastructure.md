# Cross-cutting infrastructure gaps

Component parity files link these anchors instead of restating blockers. When an
item lands, grep the repo for `infrastructure.md#<anchor>` to find everything it
unblocks.

## global-configuration

Filament's `Configurable` routes `configure()` through a `ComponentManager`
(`vendor/filament/support/src/Components/ComponentManager.php`):
`configureUsing()` registers global closures applied to every new instance of a
class (with scoped `during()` runs and `isImportant` ordering), and
`configure()` wraps `setUp()` with them. Ours
(`packages/support/src/Concerns/Configurable.php`) is a bare
`configure() → setUp()`. Affects `make()` semantics on every component; the
manager is a singleton reachable from non-DI contexts — same static-instance
shape as `Invoker`.

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

## schema-slot-apis

Schema-side features that keyed slot schemas need: `inline()`,
`embeddedInParentComponent()`, alignment factories
(`Schema::start/center/end/between`), `modifyActionsUsing()` /
`modifyActionGroupsUsing()`. Unblocks: Section's `afterHeader()`, `footer()`,
`beforeLabel()`/`afterLabel()`, `aboveContent()`/`belowContent()`; Fieldset and
Wizard will need the same.

## heading-cascade

Filament's `HasHeadings`: heading tags derived from nesting depth
(`getHeadingLevel()`/`getHeadingTag()` — h2, h3, … capped to `p`), with the
Schema tracking a root heading level and components overriding
`getHeadingsCount()`.

## column-span

Grid participation for components sized inside a parent grid: `columnSpan()` /
`columnSpanFull()` / `columnStart()` (`CanSpanColumns`) and `columnOrder()`
(`CanOrderColumns`), emitted as grid-column attributes by the schema render
loop's per-child wrapper. Schema-wide gap, affects every component used as a
schema child.

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
`footerActions()` (+ alignment), and action support inside slot schemas.
