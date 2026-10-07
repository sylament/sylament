---
description: The trait that makes an action dispatch a browser event on click, rendered as payload attributes on its trigger.
ports:
  - vendor/filament/actions/src/Concerns/CanDispatchEvent.php
owns:
  - packages/actions/src/Concerns/CanDispatchEvent.php
---
# CanDispatchEvent (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CanDispatchEvent`](../../../packages/actions/src/Concerns/CanDispatchEvent.php) | [`Concerns/CanDispatchEvent.php`](../../../vendor/filament/actions/src/Concerns/CanDispatchEvent.php) | done | — | Filament-exact port of the whole non-deprecated surface. `dispatch()` / `dispatchSelf()` / `dispatchTo()` store the event and its payload and stamp `$dispatchDirection` — `false` for a plain dispatch, `'self'`, `'to'` — with only `dispatchTo()` recording a target component; both directed setters route through `dispatch()` first, so a re-`dispatch()` after them resets the direction to `false` exactly as upstream. `eventData()` sets the payload alone, and `getEvent()` / `getEventData()` evaluate at read time, so a closure sees the action's state when it is read rather than when it was declared. Composed onto `Action` only — vendor's `ActionGroup` composes it nowhere either. All five trigger templates read the getters, as an arm of their shared click precedence that emits [payload attributes rather than a click binding](#a-dispatch-action-renders-payload-attributes-not-a-click-binding). Covered by [`CanDispatchEventTest`](../../../packages/actions/tests/Unit/Concerns/CanDispatchEventTest.php) and [`ActionDispatchRenderTest`](../../../packages/actions/tests/Integration/ActionDispatchRenderTest.php) |
| `emit()` / `emitSelf()` / `emitTo()` (deprecated) | [`Concerns/CanDispatchEvent.php`](../../../vendor/filament/actions/src/Concerns/CanDispatchEvent.php) | skipped | — | [diverges](#the-deprecated-emit-aliases-are-not-ported) |

## Semantics

### A dispatch action renders payload attributes, not a click binding

An action carrying `dispatch()` / `dispatchSelf()` / `dispatchTo()` renders no handler at all.
Its trigger root gets five data attributes and nothing else:

| Attribute | Source | Omitted when |
| --- | --- | --- |
| `data-sylament-action-event` | `getEvent()` | the arm never fires without it |
| `data-sylament-action-event-data` | `getEventData()`, JSON-encoded | the payload is empty |
| `data-sylament-action-dispatch-to` | `getDispatchToComponent()` | `getDispatchDirection()` is not `'to'` |
| `data-sylament-action-dispatch-self` | `getDispatchDirection()` | the direction is not `'self'` |
| `data-sylament-action-close` | `shouldClose()` | the action does not close |

The split is the point: the templates stamp the **payload**, and whatever owns the click context does the **delivery**.
A toast is rendered by the notifications component, not by the live component whose action it is, so the `data-action="live#action"` binding every other arm emits would be dead markup there — bound to a controller that dispatches against the wrong host, or against no host at all.
Handing the payload to the context owner is what lets the same `Action` render inside a modal, a table row, or a toast without the template knowing which.

Three things about the attribute set are easy to get wrong.
`data-sylament-action-dispatch-to` branches on the **direction**, never on the component being non-null: `dispatchTo('X', 'a')->dispatch('b')` leaves `$dispatchToComponent` set to `'X'` while resetting the direction to `false` (vendor-exact, since both directed setters route through `dispatch()` first), so reading the component alone would address a plain dispatch at a stale target.
`data-sylament-action-close` is a **neutral marker** — it says the action closes, not how — and it is emitted by both the dispatch arm and the bare-close arm; the bare-close arm keeps its `data-action="sylament--support--modal#dismiss"` alongside, which is inert outside a modal (Stimulus resolves an action token against ancestor controllers only, so a token naming a controller that is not one binds nothing and logs nothing).
And `data-sylament-action-dispatch-self` is a **valueless** marker rather than a target: `'self'` names no component, so the key carries the boolean `getDispatchDirection() == 'self'` and the attribute bag renders it bare on `true` and drops it on `false` — which is why the reading side tests for presence, never for a value.
Its meaning is *resolve the emitter relative to this trigger*, so it and `-dispatch-to` are mutually exclusive by construction: a direction is one or the other.

One delta against Filament, and it is the shape rather than the rank.
Filament emits `getLivewireEventClickHandler()` — a `$dispatch(...)` / `$dispatchSelf(...)` / `$dispatchTo(...)` **JavaScript expression** on `wire:click`; the port has no arbitrary-JS seam and does not want one ([same reading](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) as the string click handler), so the payload crosses as data instead of code.
The **rank** is vendor-exact: `getLivewireClickHandler()` tries the string handler, then the event, then `getParentActionCallLivewireClickHandler()`, and the five templates rank them the same way, so an action carrying both a `callParent()` handler and a `dispatch()` dispatches rather than calling the parent — in both codebases.

Pinned by [`ActionDispatchRenderTest`](../../../packages/actions/tests/Integration/ActionDispatchRenderTest.php), which covers the payload, the direction branch (including the stale-target `dispatchTo(…)->dispatch(…)` case and the self marker's presence for `dispatchSelf()` alone), the empty-payload omission, the close marker on both arms, the url arm still winning, the rank against the client and parent-call handlers across all five templates, and the icon-button shape reaching the same arms through its own guard expressions.

The click wiring is one `{% if %}` chain written out in each trigger template — the same text in `Badge`, `Button`, `Grouped` and `Link`, and the same arms as ternaries in `IconButton` — so an arm added, removed or reordered changes all five templates together.
The render test above pins the rank behaviourally across the five; nothing compares the templates' text, which is why the chain is kept identical by hand rather than factored out.

The delivery half — who reads these attributes, and how a `'self'` emitter is resolved — is [actions-bundle.md](actions-bundle.md#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).

## Divergences

### The deprecated `emit*` aliases are not ported

Filament's `CanDispatchEvent` carries three `@deprecated` aliases — `emit()`, `emitSelf()` and `emitTo()` — each a one-line forward to its `dispatch*` twin, kept for callers written against the older `emit` vocabulary.
The port carries the three `dispatch*` methods and nothing else: Sylament has no deprecation-shim policy, and a fresh codebase has no older vocabulary to keep compatible with — the same call already made for [`disableLabel()`](action.md#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported), `CanNotify`'s `*NotificationMessage()` pair, and `HasData`'s `*FormData*` pair.
Nothing is lost, since every alias's target is ported.
Stays out regardless.
