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
into the `attributes.defaults()`/`attrs()` maps with Twig's `...`
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

Status: the epic is executing per [live-state-tasks.md](live-state-tasks.md).
**T1** (deterministic `$data` substrate), **T2** (the reactive `data-model` +
`afterStateUpdated` loop, `HasStateBindingModifiers`), **T3** (the state-cast
pipeline — `stateCast()` + the `StateCast` contract, applied `set` on hydrate and
`get` on read), and **T4** (correct dehydration — `dehydrateState()` with
shared/descendant-path handling, the `dehydrated`/`saved`/`dehydratedWhenHidden`
conditions, `beforeStateDehydrated`, `mutateDehydratedState`, and a minimal
`CanBeHidden` slice for the hidden gate), and **T5** (cast-aware `$get`/`$set`
component resolution — `getComponentByStatePath()` returning cast state, with the
raw-bag fallback for undeclared paths) have landed. **T7** was resolved as a
documented divergence (renderless-after-update doesn't port — wire-identical to a
non-live field in UX — and `CanPoll` → `data-poll` is deferred until a first-party
host can emit it; see [schemas/component.md](schemas/component.md)); only **T6**
(Doctrine relationship loading) remains. Per-item state lives in
[schemas/component.md](schemas/component.md) /
[schemas/schema.md](schemas/schema.md).

Required knowledge for the port: Filament's `reset-schema-component-state`
Alpine handler defers via `$nextTick` so the morph lands before state resets;
the Stimulus controllers have no equivalent deferral yet (inert today — no
state exists to reset).

## translation

A Symfony equivalent of Laravel's `__()` for `translateLabel()`-style APIs —
translator access from non-DI components (likely another `Invoker`/static-
instance-shaped problem). When this lands, back the `Invoker`'s container
lookup with a curated `ServiceLocator` of injectable services: on a compiled
container `has()` answers only for public services, so today closure
parameters can only receive effectively none of the real app services (see the
divergence note in [schemas/component.md](schemas/component.md)).

## support-managers

Filament's `SupportServiceProvider` boots a family of support singletons none
of which is mirrored yet; each lands with its first consumer: `ViewManager`
(render hooks — `registerRenderHook()` / `renderHook()`), `ColorManager`
(custom palette registration; pairs with the custom-palette item in
[support/icons.md](support/icons.md)), `TimezoneManager`, `CliManager`, and
`AssetManager` (we ship assets through Symfony AssetMapper instead — likely a
permanent divergence to record when panels land).

## html-sanitizer

Filament registers an HTML sanitizer plus the `sanitizeHtml()` /
`sanitizeUrl()` / `sanitizeCssColor()` helpers. Security-relevant the moment a
URL- or HTML-emitting component ports (links, images, rich text);
`symfony/html-sanitizer` is the natural backend.

## actions-package

The `actions` bundle itself. Unblocks: Section `headerActions()` /
`footerActions()` (+ footer alignment) and the `setUp()` seeding of the
`after_header` / `footer` slots with those action defaults; Schema's
`CanModifyActions` (`modifyActionsUsing()` / `modifyActionGroupsUsing()`,
applied when `getComponents()` materializes the list) and Section's
`configureChildSchema()` small/link-styled action defaults for the
label/content slots; `Action` / `ActionGroup` values in the
`HasComponents::components()` and child-slot unions.
