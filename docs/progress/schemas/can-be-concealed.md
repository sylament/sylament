---
description: The ancestor walk that tells a schema component whether a collapsible or tabbed container above it can hide it, and the contract containers answer.
ports:
  - vendor/filament/schemas/src/Components/Contracts/CanConcealComponents.php
owns:
  - packages/schemas/src/Components/Concerns/CanBeConcealed.php
  - packages/schemas/src/Components/Contracts/CanConcealComponents.php
---
# CanBeConcealed (schemas)

[`Sylament\Schemas\Components\Concerns\CanBeConcealed`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) on the base [`Component`](component.md) answers "can something above me hide this component from view?".
The installed Filament ships no such trait: its [`Component::isConcealed()`](../../../vendor/filament/schemas/src/Components/Component.php) is deprecated and answers `false`.

Its consumers are the controls that render **native** constraint attributes: a `required` input inside an inactive Tab or a collapsed Section is `display`-hidden, and the browser refuses to submit the form around it with no visible error ("An invalid form control … is not focusable").
Forms' gated templates read `isConcealed()` and drop those attributes; see [text-input.md](../forms/text-input.md), [textarea.md](../forms/textarea.md), [checkbox.md](../forms/checkbox.md), [date-time-picker.md](../forms/date-time-picker.md) and [select.md](../forms/select.md).
Server-side validation is untouched.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getConcealingComponent()`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | — (no Filament 5 counterpart) | done | — | [diverges](#the-port-keeps-the-concealing-contract-that-filament-deprecates) |
| [`isConcealed()`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | [`isConcealed()`](../../../vendor/filament/schemas/src/Components/Component.php) (deprecated) | done | — | [diverges](#the-port-keeps-the-concealing-contract-that-filament-deprecates) |
| [`$cachedConcealingComponent` memoisation](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | — (no Filament 5 counterpart) | done | — | [semantics](#the-answer-is-memoised-for-the-life-of-the-component) |
| [`CanConcealComponents::canConcealComponents()`](../../../packages/schemas/src/Components/Contracts/CanConcealComponents.php) | [`Contracts/CanConcealComponents.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanConcealComponents.php) | done | — | [diverges](#the-port-keeps-the-concealing-contract-that-filament-deprecates) |

## Semantics

### The answer is memoised for the life of the component

The first `getConcealingComponent()` call caches its result, the ancestor or `false` for "walked it, found none", and every later call returns the cache.
Reconfiguring an ancestor after that first call (making a Section collapsible, or un-collapsible) does not change the answer.
That is what makes the walk cheap enough for every control on a page to ask, and a component's ancestry does not change mid-render.
When the walk recurses, a parent that finds no concealer caches `false` too, so a miss is memoised at every level it passed through.

## Divergences

### The port keeps the concealing contract that Filament deprecates

Filament's `CanConcealComponents` is an empty interface marked `@deprecated`, because its fields do not strip native validation attributes when concealed.
Its `Component::isConcealed()` is deprecated for the same reason: concealing containers reveal themselves around an invalid control through the `invalid` event, and a component hidden on the client is wrapped in a `<fieldset>` that is `disabled` while hidden.
The port still strips those attributes, so it keeps the contract and gives it one method, `canConcealComponents()`, which `getConcealingComponent()` reads: `Section` and `Repeater` answer `isCollapsible()`, and `Tab` and `Step` answer `true`.
That overrides the rule that a deprecated Filament member is skipped, because the walk the contract serves is what stops a hidden `required` control from blocking its form's submission.
The walk reports ancestors only: the port has no `hiddenJs()` or `visibleJs()` ([can-be-hidden.md](can-be-hidden.md#the-js-visibility-variants-wait-on-the-live-component-state-runtime)), so no component conceals itself on the client.
Porting Filament's `invalid`-event reveal would make the contract and the walk removable.
