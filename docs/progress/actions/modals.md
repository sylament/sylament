---
description: The `<twig:SylamentActions:Modals />` component that renders one ActionModal per mounted frame, and the host's per-frame id and open-state helpers it reads.
ports:
  - vendor/filament/actions/resources/views/components/modals.blade.php
  - vendor/filament/actions/resources/views/action-modal.blade.php
  - vendor/filament/actions/src/Concerns/InteractsWithActions.php
  - vendor/filament/actions/resources/js/components/modals.js
  - vendor/filament/actions/resources/js/index.js
owns:
  - packages/actions/src/Twig/Components/Modals.php
  - packages/actions/templates/components/Modals/
---
# Modals (actions)

The renderer is Filament's loop: `Modals/index.html.twig` walks `getMountedActions()` and renders one `ActionModal` per frame, gating only the last (`{% if not loop.last or liveComponent.mountedActionShouldOpenModal(action) %}`), the port of Filament's `@if ((! $loop->last) || $this->mountedActionShouldOpenModal())`.
What is *not* ported is the client-side choreography Filament layers on top of that loop: see [declarative choreography](#the-stack-is-choreographed-declaratively-not-by-client-events).
The host-side rows below live in [`HasActions`](has-actions.md), and are documented here because they exist to feed this renderer.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`mountedActionShouldOpenModal()`](../../../packages/actions/src/Concerns/HasActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) | done | — | [diverges](#the-modal-open-gate-is-null-safe) |
| [`getActionsHostId()`](../../../packages/actions/src/Concerns/HasActions.php) and the `$actionsHostId` `#[LiveProp]` | [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) `$this->getId()` | done | — | [diverges](#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id) |
| [`getMountedModalId(?int $actionNestingIndex = null)`](../../../packages/actions/src/Concerns/HasActions.php) | [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) inline `$actionModalId` | done | — | [diverges](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper) |
| [`isMountedActionModalOpen(int $actionNestingIndex)`](../../../packages/actions/src/Concerns/HasActions.php) | — | done | — | [diverges](#the-stack-is-choreographed-declaratively-not-by-client-events) · [diverges](#the-parent-overlay-flag-is-server-stamped-not-a-sibling-css-selector) |
| [`<twig:SylamentActions:Modals />`](../../../packages/actions/src/Twig/Components/Modals.php) | [`components/modals.blade.php`](../../../vendor/filament/actions/resources/views/components/modals.blade.php) | done | — | [diverges](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope) · [diverges](#the-modal-is-opened-by-rendered-state-not-by-the-dispatched-event) · [diverges](#the-modals-renderer-emits-no-wrapper-element) · [diverges](#there-is-no-single-render-guard) · [diverges](#an-unresolvable-host-throws-instead-of-rendering-nothing) |

## Divergences

### The modal-open gate is null-safe

[`mountedActionShouldOpenModal(?Action $mountedAction = null): bool`](../../../packages/actions/src/Concerns/HasActions.php) delegates to `Action::shouldOpenModal()` — Filament's own body — so modal presence (`modal()` / `modalHidden()`), a custom heading, and a description (including the confirmation-string fallback that makes `requiresConfirmation()` imply a modal, Filament-exact) all flow through the single gate both `mountAction()` and the [Modals renderer](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope) read, so the rule cannot drift between the two.
It is on the [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) contract, because that renderer calls it on an abstractly-typed host.
One delta from Filament: the port is null-safe, returning `false` instead of fataling when nothing is mounted, so a template can call it bare.
The `checkForSchemaUsing:` argument is supplied (`$this->mountedActionHasSchema(...)`, Filament-exact), so an action whose only modal content is its schema opens a modal.
The renderer passes the already-resolved action in (`mountedActionShouldOpenModal(action)`), which skips a redundant lookup.

### The host id is a round-tripped LiveProp, not a framework component id

Filament scopes its modal id with `$this->getId()` — the Livewire component's own runtime id, which Livewire mints and round-trips for free.
UX LiveComponent exposes no equivalent stable per-instance id to a template, so the port mints its own: [`getActionsHostId()`](../../../packages/actions/src/Concerns/HasActions.php) lazily fills a `#[LiveProp] ?string $actionsHostId` with `bin2hex(random_bytes(4))` and returns it, so the same host keeps the same id across every re-render of a live session.
This is the id-supply half of the [modal id](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper); it exists only because the framework does not supply one, and it is the first thing to delete if UX LiveComponent ever exposes a stable component id.

A same-named-getter/LiveProp pair is exactly what forced the [dehydration bridge](has-actions.md#the-mountedactions-liveprop-dehydrates-through-a-bridge-method) on `$mountedActions`, and the collision exists here too — Symfony's `PropertyAccessor` reads `actionsHostId` through `getActionsHostId()`.
It is benign here and deliberately left alone: the getter returns the same `string` the property holds (filling it as a side effect), so dehydrating through the getter round-trips the identical value.

One constraint the lazy fill carries, and it is not visible from the getter: **the id must first be minted from inside a `#[LiveAction]`**, which is what `mountAction()` / `unmountAction()` do by way of `getMountedModalId()`.
`AddLiveAttributesSubscriber::onPreRender` (`vendor/symfony/ux-live-component/src/EventListener/AddLiveAttributesSubscriber.php`) dehydrates every `#[LiveProp]` — this one included — *before* the component's template body renders, so an id first minted while rendering never reaches that dehydration pass: the next request hydrates `$actionsHostId` back to `null`, mints a different value, and every dialog id changes, breaking the DOM identity [the morph relies on](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper).
Nothing enforces this.
A `mount()`-seeded stack, or a URL-driven default action, would violate it by reading the id first from the template instead of from a live action.

### A frame carries two identities: an index-scoped dialog and a name-scoped wrapper

Filament computes one id, inline in [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php): `"fi-{$this->getId()}-action-{$action->getNestingIndex()}"` — host plus nesting index.
The port's [`getMountedModalId(?int $actionNestingIndex = null)`](../../../packages/actions/src/Concerns/HasActions.php) is the same shape, `"sy-{$this->getActionsHostId()}-action-{$actionNestingIndex}"`, defaulting the index to the top of the stack; it is on the [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) contract despite having no Filament counterpart, because the shipped [Modals renderer](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope) calls it on an abstractly-typed host.
Two hosts on one page do not collide, and neither do two frames of one stack.
One sharp edge: on an **empty** stack the default index is `0`, so the bare call returns a real frame-0 id rather than a non-colliding sentinel.
That is what `unmountAction()` wants — it reads the id *before* popping, so the close event names the frame being closed — but a future caller that asks for "the current modal id" while nothing is mounted gets a plausible-looking string, not a miss.

Sylament adds a **second** id that Filament has no need for.
[`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig) wraps each dialog in a `<div id="{{ id }}-w-{{ action.getName }}">` that carries the frame's `dismissed → unmountAction` binding, because the binding cannot sit on the component tag itself (a caller `data-action` would replace the dialog's own Stimulus wiring inside `attributes.defaults()`) and the event bubbles.

The **index**-scoped dialog id is the load-bearing one: an unchanged frame keeps the same id across re-renders, so Idiomorph matches it and it morphs in place, keeping its native `open` state instead of being replaced and re-animated.
The wrapper's **name**-scoped id is a per-frame unique id and nothing more — it is explicitly *not* a subtree-swap trigger, because Idiomorph matches by id-set **intersection** and the unchanged dialog id inside keeps the wrapper matched even when the action name changes.
That is harmless: a morphed node has its attributes synced from the incoming markup, so a `replaceMountedAction()` at the same index rewrites the wrapper's `cancelParentActions` argument for the replacing action anyway.

Filament needs neither id: its close chain is computed client-side per open modal rather than baked into markup.

### The parent-overlay flag is server-stamped, not a sibling CSS selector

Filament suppresses the dim of a modal that has an open modal after it with a `:has(~ .fi-modal.fi-modal-open)` sibling rule — legal there because its modals are plain `<div>`s stacked in DOM order.
A native `<dialog>` promoted by `showModal()` paints in **top-layer** order, which is `showModal()` **call** order and has no relationship to DOM order, so no sibling selector can express "is there a dialog above me".
The port computes the answer server-side instead: `Modals/index.html.twig` passes `:overlaid="not loop.last"` to every non-top frame, `ActionModal` narrows it to `overlaid and open`, and support's `Modal` stamps `sy-modal-overlaid`, whose CSS zeroes that dialog's own background so only the topmost frame paints a scrim.
The hand-off animates for free, because `background-color` is already in the container's transition list.

### The stack is choreographed declaratively, not by client events

This divergence explains most of the others.

Filament coordinates a modal stack from the client.
`mountAction()` and `unmountAction()` dispatch `sync-action-modals` with the new nesting index and an overlay flag; a JS object (`filamentActionModals`) keeps the stack state, and the modals themselves are opened and closed through `open-modal` / `close-modal` / `close-modal-quietly` events — the "quietly" variant existing purely to suppress a Livewire re-render round-trip.

Sylament has no client stack state at all.
Which frames exist is `$mountedActions`; whether a given frame's dialog is open is computed on the server by [`isMountedActionModalOpen(int $actionNestingIndex)`](../../../packages/actions/src/Concerns/HasActions.php) and rendered into the markup as both a Stimulus value and the native `open` attribute.
The rule it implements: the **top** frame is open when its own `mountedActionShouldOpenModal()` holds; a **parent** frame stays open unless some deeper frame both opens a modal of its own *and* does not declare `overlayParentActions()`.
The "opens a modal of its own" qualifier is load-bearing — without it, an `Action::halt()` on a modal-less child would close the parent modal that `halt()` exists to keep open.

Consequences, each of which is why a Filament mechanism has no port here:

- **No `sync-action-modals` dispatch, and no `syncActionModals()` method.**
  The state it carries is already in the render.
- **No `open-modal` / `close-modal-quietly` chain.**
  `unmountAction()` dispatches one `sylament-close-modal` for the frame it started on, and `mountAction()` one `sylament-open-modal`, but neither is authoritative — they are kept for imperative callers, and the controller guards make value and event idempotent (see [support/modal.md](../support/modal.md#the-open-state-is-a-stimulus-value-not-only-an-event)).
- **`isMountedActionModalOpen()` is Sylament-only** and sits on the [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) contract, the same way `getMountedModalId()` is a Sylament-only host obligation.
- **Client dismissal is the one client→server edge that remains.**
  A close button, ESC, click-away, or a `close()` action closes the dialog in the browser; the controller's `dismissed` event round-trips `unmountAction` with that frame's `cancelParentActions` value baked into the markup.

Nothing reverses this.
Event-driven opening cannot work for a dialog created by the response that asks it to open, which is every freshly mounted frame.

### The modals renderer resolves its host from the render stack, not from template scope

Filament's `modals.blade.php` reads `$this` because a Blade component inherits the Livewire component's scope.
A Twig component gets a fresh context: ux-twig-component spreads the caller's context in and then **overwrites** `this` with the component being rendered ([`ComponentRenderer::preRender()`](../../../vendor/symfony/ux-twig-component/src/ComponentRenderer.php)), so inside `<twig:…>` `this` is that component, never the host.
The two implicit Twig equivalents — `outerScope.this`, or staying an `{% include %}` — both bind to whatever render context encloses the tag, which is the host **only** when the tag sits directly in the host's own template; nest it in any other Twig component and they silently bind to that component's `AnonymousComponent`.
(The [`NestedActionHost`](../../../packages/actions/tests/Fixtures/Twig/templates/components/NestedActionHost.html.twig) fixture keeps its trigger button outside the `Section` for exactly this reason — `this.deleteAction()` cannot resolve in there.)

So `Modals` is a **class-based** Twig component rather than an anonymous template: [`getLiveComponent()`](../../../packages/actions/src/Twig/Components/Modals.php) walks `Symfony\UX\TwigComponent\ComponentStack` innermost-first to the nearest `InteractsWithActions`, skipping frames that are not hosts.
That keeps Filament's zero-argument call site — `<twig:SylamentActions:Modals />` against `<x-filament-actions::modals />` — and stays correct at any nesting depth.
An optional `:liveComponent="this"` prop overrides the walk, named for the vocabulary `BelongsToLiveComponent` / `Action::getLiveComponent()` already use for this object.

`ComponentStack` is marked `@internal` in ux-twig-component — a real maintenance liability, accepted deliberately because the two supported alternatives are silently position-dependent.
It is contained: the walk is a single method, so a vendor change has a single repair site, and [`ModalsTest`](../../../packages/actions/tests/Unit/Twig/Components/ModalsTest.php) pins the contract against a hand-built stack so a break fails loudly in Unit rather than subtly in a render.

### The modal is opened by rendered state, not by the dispatched event

`mountAction()` dispatches `sylament-open-modal`, but that event alone cannot open a modal the same response is rendering for the first time: UX LiveComponent's `processRerender()` morphs the DOM and dispatches queued browser events synchronously in one task, while Stimulus binds `data-action` listeners from a `MutationObserver` microtask that runs after it — so the new `<dialog>` is not yet listening.
The authoritative signal is therefore the markup: the renderer passes each frame's server-computed [`isMountedActionModalOpen()`](#the-stack-is-choreographed-declaratively-not-by-client-events) through `ActionModal` into `<twig:SylamentSupport:Modal>`, which renders it as both the Stimulus `open` value and the native `open` attribute, and the controller opens itself from that value.
The dispatch stays for imperative callers and is harmless — the controller guards on `element.open`, so value and event are idempotent.
Full reasoning in [support/modal.md](../support/modal.md#the-open-state-is-a-stimulus-value-not-only-an-event).

Note the two-way split this creates: a **parent** frame under a non-overlaying child renders `open = false` while staying in the DOM, so it can morph back open in place when the child unmounts, and a modal-less top frame (an `Action::halt()` mid-flow) renders no dialog at all while its parent stays open.

### The modals renderer emits no wrapper element

Filament wraps its loop in `<div wire:partial="action-modals" x-data="filamentActionModals({ livewireId })" style="height: 0">` — a partial-render target, an Alpine data component, and an inline height so the always-present div costs no layout.
Sylament's renderer emits the frames' modals or nothing at all.
All three reasons for the wrapper are absent: partial re-render is [not ported](has-actions.md#partial-re-render-is-not-ported), the open/close behavior lives on the `sylament--support--modal` Stimulus controller attached to each `<dialog>` itself rather than an ancestor, and a native `<dialog>` is `display: none` until opened so it needs no layout-neutral host — the last point being why a stack of closed parent frames costs nothing to keep rendered.
Reverses only if a partial-render mechanism lands and needs a stable DOM anchor.

### There is no single-render guard

Filament's `modals.blade.php` short-circuits on a `hasActionsModalRendered` flag (a `protected bool` on the trait, set from inside the view) so that the component renders at most once per request.
It needs that because `<x-filament-actions::modals />` is baked into shared panel layouts — [`page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php), `topbar.blade.php`, `sidebar.blade.php` — which can nest, so a page can include it several times without asking.

Sylament's `panels` package places the tag on the author's behalf too, and the flag does not ship, because the placement is arranged so that placing it twice is not something a page can do by accident.
The outlet is emitted **once**, by [the base page chrome](../panels/routing.md) — after the content, outside the header-main container, with the [unsaved-changes sentinel](../panels/unsaved-changes-alert.md#the-sentinel-is-an-inner-element-because-a-second-data-controller-would-take-the-live-binding-down) following it when the page or its panel arms the guard — and every shipped page template reaches it by `{% extends %}`ing that chrome and overriding a block, so a page inherits exactly one outlet however many blocks it fills.
[`SimplePage`](../panels/simple-page.md)'s partial carries its own single copy for the same reason its chrome is a copy.
The rule that follows is the one a page author needs: **a child template must never add a `<twig:SylamentActions:Modals />` of its own** — writing it twice would emit two full stacks of `<dialog>`s sharing the same [host- and index-scoped ids](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper), because the ids are unique per host and frame, not per *rendering* of the tag.

A nested host is not a second rendering of the same stack: a [relation manager](../panels/relation-manager.md) mounts its own outlet inside its own live root, and each outlet resolves the nearest host by walking the `ComponentStack`, so the two stacks are different components' and neither duplicates the other.
Reverses only if a layout ever places the tag somewhere a page template cannot see it and therefore cannot avoid doubling.

### An unresolvable host throws instead of rendering nothing

Filament guards with `@if ($this instanceof \Filament\Actions\Contracts\HasActions …)`, silently rendering nothing when the surrounding Livewire component is not an action host — because its shared panel layouts render the tag for every component, host or not.
That guard has no work to do here: every panel page **is** an actions host, because [`Pages\Page`](../panels/custom-pages.md) implements `InteractsWithActions`, and outside a panel every occurrence of the tag is a host author writing it deliberately.
So `getLiveComponent()` throws a `LogicException` naming both fixes (place it inside a host, or pass `:liveComponent`) rather than rendering nothing.
Silence is the worse failure for the problem this component exists to solve — a modal that never appears and says nothing about why.
Reverses only if a shell ever renders the tag around something that is not a host, at which point the guard belongs in that shell rather than here.
