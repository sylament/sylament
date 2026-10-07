---
description: The trait holding an action's closure or live-action name and its click gate, with CanClose's client-side dismiss.
ports:
  - vendor/filament/actions/src/Concerns/HasAction.php
  - vendor/filament/actions/src/Concerns/CanClose.php
  - vendor/filament/actions/src/Action.php
owns:
  - packages/actions/src/Concerns/HasAction.php
  - packages/actions/src/Concerns/CanClose.php
---
# HasAction (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CanClose`](../../../packages/actions/src/Concerns/CanClose.php) (`close()` / `shouldClose()` / `hasClose()`) | [`Concerns/CanClose.php`](../../../vendor/filament/actions/src/Concerns/CanClose.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`action()`](../../../packages/actions/src/Concerns/HasAction.php) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`getAction()`](../../../packages/actions/src/Concerns/HasAction.php) (for `getActionFunction()`) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`getClientClickHandler()`](../../../packages/actions/src/Concerns/HasAction.php) (the string arm of `getLivewireClickHandler()`) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`liveComponentClickHandlerEnabled()` / `isLiveComponentClickHandlerEnabled()`](../../../packages/actions/src/Concerns/HasAction.php) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | done | — | [semantics](#the-click-gate-is-what-keeps-a-form-submit-from-mounting-a-second-action) |
| `hasAction()` | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | todo | — | [detail](#hasaction-is-not-ported) |

## Details

### `hasAction()` is not ported

Filament's `hasAction(): bool` answers `$this->action !== null`.
The port has no such method and no test of one; a caller reads `getAction() !== null` instead.

## Semantics

### The click gate is what keeps a form submit from mounting a second action

`liveComponentClickHandlerEnabled()` and `isLiveComponentClickHandlerEnabled()` are Filament's `livewireClickHandlerEnabled()` pair under the `Livewire` → `LiveComponent` [idiom translation](../translations.md#idiom), with Filament's three arms in Filament's order: an explicit override wins, then a filled `getUrl()` and `canSubmitForm()` each turn the handler off, and otherwise it is on.
The Button, Link, Badge and Grouped trigger templates read the getter for their whole live click binding; [`Action/IconButton.html.twig`](../../../packages/actions/templates/partials/Action/IconButton.html.twig) reads it for its mount binding alone.
The guard is load-bearing: [`getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) gives a form-wrapped action `->submit(name)->action(null)`, so without it the trigger falls through its click cascade to the `mountAction` default and every confirm click fires a second live request, `mountAction('submit')`, naming an action that does not exist, alongside the form's own `callMountedAction`.
The close arm is outside the gate in both codebases: Filament keeps it on a separate `x-on:click`, the port on a separate `data-action`.
The explicit override serves callers whose click lives on the markup around the action, such as [`CheckboxList`](../forms/checkbox-list.md#the-bulk-toggle-actions-render-but-never-dispatch)'s two bulk-toggle links, Filament's own `livewireClickHandlerEnabled(false)` shape.

## Divergences

### String click handlers are live-action names, and `close()` dismisses client-side

**A string `action(...)` value is a live-action method name, not a raw client expression.**
Filament's `getLivewireClickHandler()` returns a string action verbatim as the Livewire click-handler JS; the port reads it through `getClientClickHandler()` and emits it as the `action` parameter of `stimulus_action('live', 'action', …)`, so the string names a `#[LiveAction]` method on the host (`'callMountedAction'` being the canonical consumer, via the submit footer action).
There is no way to inject arbitrary JS through this seam, by design.
The same reading applies to `CanCallParentAction`'s handler, which is likewise a live-action name.

`getAction()` answers the raw value, closure or string, where Filament's `getActionFunction()` answers the closure alone and `null` for a string.
So `Action::call()` on a string action returns the string, where Filament's evaluates `null` and returns `null`; neither runs anything server-side.

All five trigger templates share one click precedence, the port of `getLivewireClickHandler()`'s arm order: a string handler first, then a [dispatch payload](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding), then a `callParent()` handler (with the action's own `arguments` attached), then `shouldClose()`, then the `mountAction` round-trip, and the whole chain is suppressed when `getUrl()` is filled, because a link navigates.
`shouldClose()` emits `data-action="sylament--support--modal#dismiss"`, a client-side close, matching Filament's `getAlpineClickHandler()` `close()` arm: the dialog closes in the browser, and the controller's `dismissed` event carries the [`cancelParentActions` round-trip](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events) back to `unmountAction`.
Dispatching `unmountAction` directly would be correct for a single slot, but it could not express "close this frame *and* cancel back to a named parent", which is per-action state the close chain has to carry.

Pinned by `ActionRenderTest`'s string, close, parent-call and precedence cases across the button and icon-button shapes.

One shape delta among the five templates is deliberate: `Action/IconButton.html.twig` suppresses click wiring for a disabled action as well as a URL-bearing one, where its four siblings emit the binding and lean on the rendered `disabled` attribute.
That template also expresses the whole precedence as guard expressions rather than the four siblings' `{% if %}` chain, so its three attribute sets (mount, close, dispatch) each restate the arms above them; they read one hoisted `clickWiringSuppressed` flag, and the close and mount sets both read the hoisted `hasEventBinding` flag, so no pair can drift apart.
