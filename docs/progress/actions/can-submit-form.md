---
description: The CanSubmitForm concern on Action, which turns an action's trigger into a submit button for a form.
ports:
  - vendor/filament/actions/src/Concerns/CanSubmitForm.php
owns:
  - packages/actions/src/Concerns/CanSubmitForm.php
---
# CanSubmitForm (actions)

[`Sylament\Actions\Concerns\CanSubmitForm`](../../../packages/actions/src/Concerns/CanSubmitForm.php) is composed by [`Action`](action.md).
Its readers live elsewhere: the `Button`, `Link`, `Badge`, `IconButton` and `Grouped` trigger partials render `type="submit"` from `canSubmitForm()` and pass `getFormId()` to the support component's `form` attribute, [`HasAction::isLiveComponentClickHandlerEnabled()`](has-action.md) answers `false` for a submitting action, and [`CanOpenModal::getModalSubmitAction()`](can-open-modal.md) calls `submit()` when the modal wraps a form.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`submit()` / `canSubmitForm()`](../../../packages/actions/src/Concerns/CanSubmitForm.php) | [`Concerns/CanSubmitForm.php`](../../../vendor/filament/actions/src/Concerns/CanSubmitForm.php) | done | — | — |
| [`getFormToSubmit()`](../../../packages/actions/src/Concerns/CanSubmitForm.php) | [`Concerns/CanSubmitForm.php`](../../../vendor/filament/actions/src/Concerns/CanSubmitForm.php) | done | — | [diverges](#getformtosubmit-has-no-reader-because-the-trigger-renders-no-loading-indicator) |
| [`formId()` / `getFormId()`](../../../packages/actions/src/Concerns/CanSubmitForm.php) | [`Concerns/CanSubmitForm.php`](../../../vendor/filament/actions/src/Concerns/CanSubmitForm.php) | done | — | — |
| `hasCanSubmitForm()` / `hasFormId()` | [`Concerns/CanSubmitForm.php`](../../../vendor/filament/actions/src/Concerns/CanSubmitForm.php) | skipped | — | [diverges](#hascansubmitform-and-hasformid-are-not-ported-because-only-filaments-optimized-render-paths-call-them) |

## Divergences

### `getFormToSubmit()` has no reader, because the trigger renders no loading indicator

Filament reads the form name in two places: `getLivewireTarget()` returns it for an action that accesses no selected records, and the button generator falls back to it as the `wire:target` of the button's loading indicator.
The port has neither: no trigger partial passes a loading target, and the support button's loading indicator is a todo row in [button.md](../support/button.md).
The getter is ported so that a migrating `submit()` call keeps its value readable, and it gains a reader when the button's loading indicator lands.

### `hasCanSubmitForm()` and `hasFormId()` are not ported, because only Filament's optimized render paths call them

Filament calls the two predicates only from `canRenderOptimizedLink()` and `canRenderOptimizedGrouped()`, which decide whether an action may bypass its Blade component for hand-built HTML.
The port renders every trigger through Twig and has no such fast path ([action.md](action.md#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths)), so the predicates would have no caller.
`HasSize::hasSmallSizeDefault()` is left out for the same reason ([action.md](action.md#hassize-drops-the-optimized-render-fast-path-helper)), and porting the fast path would bring all three back.
