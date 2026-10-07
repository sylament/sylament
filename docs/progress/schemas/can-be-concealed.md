---
description: '`Sylament\Schemas\Components\Concerns\CanBeConcealed` on the base `Component` — the walk that answers "can something above me hide this component from… (draft)'
ports:
  - vendor/filament/schemas/src/Components/Contracts/CanConcealComponents.php
owns:
  - packages/schemas/src/Components/Concerns/CanBeConcealed.php
---
# CanBeConcealed (schemas)

[`Sylament\Schemas\Components\Concerns\CanBeConcealed`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) on the base [`Component`](component.md) — the walk that answers "can something above me hide this component from view?".
It ports `schemas/src/Components/Concerns/CanBeConcealed.php`, which the installed Filament 5.9 no longer ships: its [`Component::isConcealed()`](../../../vendor/filament/schemas/src/Components/Component.php) is deprecated and answers `false`.

Its consumers are the controls that render **native** constraint attributes: a `required` input inside an inactive Tab or a collapsed Section is `display`-hidden, and the browser refuses to submit the form around it with no visible error ("An invalid form control … is not focusable").
Forms' five gated templates read `isConcealed()` and drop those attributes — see [text-input.md](../forms/text-input.md), [textarea.md](../forms/textarea.md), [checkbox.md](../forms/checkbox.md), [date-time-picker.md](../forms/date-time-picker.md) and [select.md](../forms/select.md).
Server-side validation is untouched.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getConcealingComponent()`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | `getConcealingComponent()` | done | — | [diverges](#the-js-visibility-arm-is-not-ported) |
| [`isConcealed()`](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | [`isConcealed()`](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | — |
| [`$cachedConcealingComponent` memoisation](../../../packages/schemas/src/Components/Concerns/CanBeConcealed.php) | `$cachedConcealingComponent` | done | — | [semantics](#the-answer-is-memoised-for-the-life-of-the-component) |

Both rows are covered by [`Unit/Components/Concerns/CanBeConcealedTest`](../../../packages/schemas/tests/Unit/Components/Concerns/CanBeConcealedTest.php) — no concealer at the root or under a plain `Group`, a collapsible `Section` naming itself as the concealer, a non-collapsible one naming nothing, the walk up through an intervening `Group`, and both memoisation arms.
The rendered half is asserted in forms' [`Integration/Components/ConcealmentTest`](../../../packages/forms/tests/Integration/Components/ConcealmentTest.php).

## Semantics

### The answer is memoised for the life of the component

The first `getConcealingComponent()` call caches its result — the ancestor, or `false` for "walked it, found none" — and every later call returns the cache.
Reconfiguring an ancestor after that first call (making a Section collapsible, or un-collapsible) does not change the answer.
That is Filament's behaviour and it is what makes the walk cheap enough for every control on a page to ask; a component's ancestry does not change mid-render.

The cache is typed `Component|false|null` rather than Filament's `Component|bool|null` (nothing ever stores `true`), the "unresolved" test is `!== null` rather than `filled()` (identical for these three states — `filled(false)` is true — but a plain comparison says it at level max), and the read is `instanceof Component` rather than `?: null`.
Filament's recursive branch also caches the parent's `null` directly, leaving that arm unmemoised; ours coalesces it to `false`.

## Divergences

### The JS-visibility arm is not ported

Filament's first branch returns `$this` — the component conceals *itself* — when `getHiddenJs()` or `getVisibleJs()` is filled, because an Alpine expression can hide the element on the client with the server none the wiser.
This port has no `hiddenJs`/`visibleJs` (the whole arm is unported on [`CanBeHidden`](can-be-hidden.md)), so the branch has nothing to read and is left out.
It returns the moment that arm lands.
