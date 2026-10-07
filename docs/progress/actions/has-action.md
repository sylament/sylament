---
description: The trait holding an action's closure or live-action name and its click gate, with CanClose's client-side dismiss.
ports:
  - vendor/filament/actions/src/Concerns/HasAction.php
  - vendor/filament/actions/src/Concerns/CanClose.php
owns:
  - packages/actions/src/Concerns/HasAction.php
  - packages/actions/src/Concerns/CanClose.php
---
# HasAction (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CanClose`](../../../packages/actions/src/Concerns/CanClose.php) | [`Concerns/CanClose.php`](../../../vendor/filament/actions/src/Concerns/CanClose.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`HasAction`](../../../packages/actions/src/Concerns/HasAction.php) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | in progress | — | [detail](#hasaction-carries-the-click-gate-hasaction-is-the-one-gap-left), [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |

## Details

### HasAction carries the click gate; `hasAction()` is the one gap left

Ported: both value shapes.
`action(Closure|string|null $action): static` / `getAction(): Closure|string|null` round-trip a closure or a string, and `Action::call()` evaluates the value, injecting `record`/`arguments`/ `liveComponent` for closures and returning a string as-is (Filament-identical no-op semantics) — covered by `ActionTest`.
The string branch renders through `getClientClickHandler(): ?string` — the Sylament port of `getLivewireClickHandler()`'s string arm — which every trigger template reads first in its click precedence (string handler, then a [dispatch payload](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding), then a `callParent()` handler, then `shouldClose()` → a client-side dismiss, then the `mountAction` round-trip); the reinterpretation of the string as a live-action name rather than a raw client expression is recorded as a [divergence](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side).

Missing vs Filament's `HasAction`:
- **`hasAction(): bool`.**
  Helper predicate not ported; trivially derivable from `getAction() !== null` at call sites today, but no equivalent method or test exists.

The click gate itself is now **ported**, under the `Livewire` → `LiveComponent` [idiom translation](../translations.md#idiom): `liveComponentClickHandlerEnabled(bool|Closure|null $condition = true)` and `isLiveComponentClickHandlerEnabled(): bool` on the trait, with Filament's three arms in Filament's order — an explicit override wins, then `filled(getUrl())` and `canSubmitForm()` each turn it off, otherwise it is on.
The two default arms once lived in the templates (`hasLiveClickBinding = not component.getUrl and not component.canSubmitForm` in [`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) and its `link`/`badge`/`grouped` siblings, and an extra term in [`Action/IconButton.html.twig`](../../../packages/actions/templates/partials/Action/IconButton.html.twig)'s `mountAttributes`); all five now read the getter instead, which moves the logic without changing a single rendered attribute.
That guard is load-bearing rather than tidiness: [`getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) gives a form-wrapped action `->submit(name)->action(null)`, so without it the trigger falls through its click cascade to the `mountAction` default and every confirm click fires a **second**, unrelated live request — `mountAction('submit')`, naming an action that does not exist — alongside the form's own `callMountedAction`.
The close arm is unaffected in both codebases: Filament keeps it on a separate `x-on:click`, the port on a separate `data-action`.

What promoted it was the first caller needing the explicit override: [`CheckboxList`](../forms/checkbox-list.md#the-bulk-toggle-actions-render-but-never-dispatch) builds two bulk-toggle actions that must render as links and dispatch nothing, the click living on the `<span>` around each — Filament's own `livewireClickHandlerEnabled(false)` shape.

## Divergences

### String click handlers are live-action names, and `close()` dismisses client-side

**A string `action(...)` value is a live-action method name, not a raw client expression.**
Filament's `getLivewireClickHandler()` returns a string action verbatim as the Livewire click-handler JS; Sylament emits it through `live_action(component.getClientClickHandler)`, so the string names a `#[LiveAction]` method on the host (`'callMountedAction'` being the canonical consumer, via the submit footer action).
There is no way to inject arbitrary JS through this seam — deliberate, and permanent until an arbitrary-JS surface exists (none is planned).
The same reading applies to `CanCallParentAction`'s handler, which is likewise a live-action name.

All five trigger templates share one click precedence, the port of `getLivewireClickHandler()`'s arm order: a string handler first, then a [dispatch payload](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding), then a `callParent()` handler (with the action's own `arguments` attached), then `shouldClose()`, then the `mountAction` round-trip — and the whole chain is suppressed when `getUrl()` is filled, because a link navigates.
`shouldClose()` now emits `data-action="sylament--support--modal#dismiss"`, a **client-side** close, matching Filament's `getJsClickHandler()`: the dialog closes in the browser, and the controller's `dismissed` event carries the [`cancelParentActions` round-trip](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events) back to `unmountAction`.
Dispatching `live_action('unmountAction')` directly would be correct for a single slot, but it could not express "close this frame *and* cancel back to a named parent", which is per-action state the close chain has to carry.

Pinned by `ActionRenderTest`'s string/close/parent-call/precedence cases across the button and icon-button shapes.

One shape delta among the five templates, deliberate and now commented in place: `Action/IconButton.html.twig` suppresses click wiring for a **disabled** action as well as a URL-bearing one, where its four siblings emit the binding and lean on the rendered `disabled` attribute.
That template also expresses the whole precedence as guard expressions rather than the four siblings' `{% if %}` chain, so its three attribute sets (mount, close, dispatch) each restate the arms above them — they read one hoisted `clickWiringSuppressed` flag, and the close and mount sets both read the hoisted `hasEventBinding` flag, so no pair can drift apart.
