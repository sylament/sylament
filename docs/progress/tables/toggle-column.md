---
description: The editable `ToggleColumn`, whose switch cell writes a boolean back onto the record.
ports:
  - vendor/filament/tables/src/Columns/ToggleColumn.php
  - vendor/filament/tables/resources/css/columns/toggle.css
owns:
  - packages/tables/assets/src/styles/components/toggle-column.css
  - packages/tables/src/Columns/ToggleColumn.php
  - packages/tables/templates/partials/ToggleColumn/
---
# ToggleColumn (tables)

[`Sylament\Tables\Columns\ToggleColumn`](../../../packages/tables/src/Columns/ToggleColumn.php) is an editable [`Column`](column.md) whose cell is a switch: flipping it writes a boolean back onto the record through the host's [`updateTableColumnState()`](has-columns.md) live action.
It ports Filament's [`Filament\Tables\Columns\ToggleColumn`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php).
[`column.md`](column.md) tracks what every column carries, and [`has-columns.md`](has-columns.md) tracks the write-back seam the editable columns share.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()` and the boolean seed](../../../packages/tables/src/Columns/ToggleColumn.php) | [`ToggleColumn::setUp()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | — | [diverges](#the-boolean-rule-becomes-a-type-constraint) |
| [`onColor()` / `offColor()` / `getOnColor()` / `getOffColor()`](../../../packages/forms/src/Components/Concerns/HasToggleColors.php) | [`Forms\Concerns\HasToggleColors`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleColors.php) | done | — | [semantics](#the-colour-defaults-live-in-the-partial-not-in-the-getters) |
| [`onIcon()` / `offIcon()` / `getOnIcon()` / `getOffIcon()` / `hasOnIcon()` / `hasOffIcon()`](../../../packages/forms/src/Components/Concerns/HasToggleIcons.php) | [`Forms\Concerns\HasToggleIcons`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleIcons.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Columns/ToggleColumn.php) + [the partial it names](../../../packages/tables/templates/partials/ToggleColumn/index.html.twig) | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | — | [diverges](#the-switch-is-a-native-checkbox-so-nothing-binds-a-tabindex) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/ToggleColumn/index.html.twig) | [`toggle.js`](../../../vendor/filament/tables/resources/js/components/columns/toggle.js) | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [The refusal chrome — `aria-invalid` and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/ToggleColumn/index.html.twig) | [`toggle.js`](../../../vendor/filament/tables/resources/js/components/columns/toggle.js) `error` | in progress | — | [detail](#no-test-renders-a-refused-toggle) · [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline` and the alignment arms on the root](../../../packages/tables/assets/src/styles/components/toggle-column.css) | [`columns/toggle.css`](../../../vendor/filament/tables/resources/css/columns/toggle.css) | done | — | [color-column.md](color-column.md#the-stacked-on-mobile-rule-has-nothing-to-select) |
| `wire:loading.attr` / `wire:target` on the control | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | — | [diverges](#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | skipped | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| `wire:ignore.self` on the root | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | skipped | — | [diverges](#the-cell-root-morphs-and-carries-the-state-so-filaments-alpine-plumbing-around-it-has-no-counterpart) |
| The `x-ref="serverState"` hidden input and the `x-cloak` duplicate switch | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | skipped | — | [diverges](#the-cell-root-morphs-and-carries-the-state-so-filaments-alpine-plumbing-around-it-has-no-counterpart) |

## Details

### No test renders a refused toggle

The partial renders `aria-invalid="true"` on the control and `<p class="sy-ta-cell-error">` under it whenever `getUpdateStateError()` answers.
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php) renders that branch only through a `TextInputColumn`, and the Integration [`ToggleColumnTest`](../../../packages/tables/tests/Integration/Columns/ToggleColumnTest.php) asserts only that a clean cell carries no `aria-invalid`.
A test that renders a refused write through this partial is what is missing.

## Semantics

### The colour defaults live in the partial, not in the getters

`getOnColor()` and `getOffColor()` answer `null` until they are set, as forms' trait does, and the partial supplies `primary` / `gray` at render, exactly as Filament's `toEmbeddedHtml()` does.
Reading the getters is therefore not the way to ask what colour a cell will paint.

## Divergences

### The boolean rule becomes a type constraint

Filament seeds `rules(['boolean'])`, which accepts `true`, `false`, `0`, `1`, `'0'` and `'1'`.
The port seeds `new Type('bool')` instead, and can afford to: the [cell controller](../../../packages/tables/assets/src/controllers/editable_cell_controller.ts) reads `input.checked` and posts a JSON boolean, so the strict type is exactly the shape that arrives.
Anything else is a client that did not go through the shipped control, and refusing it is the point.

### The switch is a native checkbox, so nothing binds a `tabindex`

Filament's switch is a `div[role="switch"]` with `x-bind:tabindex` applied after Alpine boots, a deliberate dodge because a server-rendered `tabindex` is invalid inside the record `<a>` a clickable row wraps its cells in.
The port renders forms' toggle skin instead, `label.sy-fo-toggle > input[type="checkbox"][role="switch"]`, and a native input is focusable without any `tabindex`.
`disabledClick()` in `setUp()` makes the table partial wrap the cell in a `div` rather than an `<a>` or a `<button>`, so the control never sits inside the record link.

### The cell root morphs and carries the state, so Filament's Alpine plumbing around it has no counterpart

Filament marks the cell root `wire:ignore.self`, so Livewire leaves the root's own attributes alone, and hands its Alpine component the server's state through a hidden `x-ref="serverState"` input.
The port's root has to morph, because it carries the authoritative `state` value the [controller repaints the control from](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph), so it takes no ignore marker and needs no hidden copy of the state.
Filament's toggle also renders an `x-cloak` duplicate of the switch in its on state, to paint the on colour before Alpine boots; the port's switch is a server-rendered checkbox whose colours are CSS from the first paint, so there is nothing to cloak.
The checkbox and text-input partials skip the same `wire:ignore.self` and `x-ref="serverState"` pair for the same reason.

### The in-flight affordance is the controller's, not a loading attribute

Filament stamps `wire:loading.attr="disabled"` and a `wire:target` list on the control and lets Livewire manage it.
The port has no such directive: the controller disables the control and puts `sy-loading` on the cell root for the duration of its own write, and restores the control's `disabled` to its pre-write value when the write settles, unconditionally because [the re-render has always run by then](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph).
Removing the attribute also undoes the change UX's `ExternalMutationTracker` recorded when the controller set it, because a tracked attribute whose original value was absent is deleted from the change map on removal, so the server's own `disabled` wins again from the next morph on.
The residual is one render wide: a write whose own result disables the cell shows an enabled control until the next re-render.
