# ToggleColumn (tables)

[`Sylament\Tables\Columns\ToggleColumn`](../../../packages/tables/src/Columns/ToggleColumn.php)
— an editable [`Column`](column.md) whose cell is a switch: flipping it writes a
boolean back onto the record through the host's
[`updateTableColumnState()`](has-columns.md) live action. It ports Filament's
[`Filament\Tables\Columns\ToggleColumn`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php).
Everything [`Column`](column.md) carries — the name, the label, the record
binding, the state read, the alignment, the closure injections — is tracked
there, and the write-back seam the four editable columns share is tracked on
[`has-columns.md`](has-columns.md), not repeated here.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/ToggleColumn.php`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php)
  — the class and its `toEmbeddedHtml()`
- [`tables/resources/css/columns/toggle.css`](../../../vendor/filament/tables/resources/css/columns/toggle.css)
  — the stylesheet [`toggle-column.css`](../../../packages/tables/assets/src/styles/components/toggle-column.css)
  is the prefix-renamed port of

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in Details,
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()` and the boolean seed](../../../packages/tables/src/Columns/ToggleColumn.php) | [`ToggleColumn::setUp()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | — | [diverges](#the-boolean-rule-becomes-a-type-constraint) |
| [`onColor()` / `offColor()` / `getOnColor()` / `getOffColor()`](../../../packages/forms/src/Components/Concerns/HasToggleColors.php) | [`Forms\Concerns\HasToggleColors`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleColors.php) | done | — | [semantics](#the-colour-defaults-live-in-the-partial-not-in-the-getters) |
| [`onIcon()` / `offIcon()` / `hasOnIcon()` / `hasOffIcon()`](../../../packages/forms/src/Components/Concerns/HasToggleIcons.php) | [`Forms\Concerns\HasToggleIcons`](../../../vendor/filament/forms/src/Components/Concerns/HasToggleIcons.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Columns/ToggleColumn.php) + [the partial it names](../../../packages/tables/templates/partials/ToggleColumn/index.html.twig) | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | — | [diverges](#the-switch-is-a-native-checkbox-so-nothing-binds-a-tabindex) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/ToggleColumn/index.html.twig) | `toggleTableColumn()` Alpine data | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [The refusal chrome — `aria-invalid` and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/ToggleColumn/index.html.twig) | the Alpine `error` binding | done | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline` and the alignment arms on the root](../../../packages/tables/assets/src/styles/components/toggle-column.css) | [`columns/toggle.css`](../../../vendor/filament/tables/resources/css/columns/toggle.css) | done | — | [semantics](#the-stacked-on-mobile-arm-is-not-ported) |
| `wire:loading.attr` / `wire:target` on the control | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | done | — | [diverges](#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | skipped | — | The refusal is server-rendered under the control instead — [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| `wire:ignore.self` on the root | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | skipped | — | The root **must** morph — it is what carries the authoritative `state` value the control is repainted from ([has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph)) |
| The `x-ref="serverState"` hidden input and the `x-cloak` duplicate switch | [`ToggleColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ToggleColumn.php) | skipped | — | Both exist to hand Alpine a pre-boot copy of the state and to paint the on-colour before it boots; the root's `state` value is that copy here, and the server renders the real control coloured, so there is nothing to cloak |

The rows are covered by the Unit
[`ToggleColumnTest`](../../../packages/tables/tests/Unit/Columns/ToggleColumnTest.php)
(the contract, the click gate, the boolean seed, the colours and icons) and the
Integration
[`ToggleColumnTest`](../../../packages/tables/tests/Integration/Columns/ToggleColumnTest.php)
(the rendered cell: the controller values, forms' skin, the thumb icons, the
disabled arm, `sy-inline` and the alignment class). The write itself is
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php)'s.

## Divergences

### The boolean rule becomes a type constraint

Filament seeds `rules(['boolean'])`, which accepts `1`, `'1'`, `'on'` and
friends because that is what an HTML form posts. The port seeds
`new Type('bool')` instead, and can afford to: the
[cell controller](../../../packages/tables/assets/src/controllers/editable_cell_controller.ts)
reads `input.checked` and posts a JSON boolean, so the strict type is exactly the
shape that arrives. Anything else is a client that did not go through the shipped
control, and refusing it is the point.

### The switch is a native checkbox, so nothing binds a `tabindex`

Filament's switch is a `div[role="switch"]` with `x-bind:tabindex` applied after
Alpine boots — a deliberate dodge, because a server-rendered `tabindex` is
invalid inside the record `<a>` a clickable row wraps its cells in. The port
renders forms' toggle skin instead — `label.sy-fo-toggle > input[type="checkbox"][role="switch"]`
— and a native input is focusable and valid wherever it sits, so the trick has
nothing to solve. `disabledClick()` in `setUp()` collapses the cell wrapper to a
`div` besides, so the control is never inside an anchor to begin with.

### The in-flight affordance is the controller's, not a loading attribute

Filament stamps `wire:loading.attr="disabled"` and a `wire:target` list on the
control and lets Livewire manage it. The port has no such directive: the
controller disables the control and puts `sy-loading` on the cell root for the
duration of its own write, and **always** restores the control when the write
settles. It cannot condition that restore on whether a re-render ran in the
meantime, because one always has: UX resolves an action's promise *after*
`processRerender()`, which fires `render:finished` at its end
([`live_controller.js`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js)),
so a "no re-render ran" guard never fires and the cell would stay disabled for
the rest of the page's life. Removing the attribute is also what un-does the
change UX's `ExternalMutationTracker` recorded when we set it — a tracked
attribute whose original value was absent is *deleted* from the change map on
removal — so the server's own `disabled` wins again from the next morph on. The
residual is one render wide: a write whose own result disables the cell shows an
enabled control until the next re-render.

## Semantics

### The colour defaults live in the partial, not in the getters

`getOnColor()` and `getOffColor()` answer `null` until they are set — forms'
trait, unchanged — and the partial supplies `primary` / `gray` at render, exactly
as Filament's `toEmbeddedHtml()` does. Reading the getters is therefore not the
way to ask what colour a cell will paint.

### The stacked-on-mobile arm is not ported

Filament's stylesheet has a `.fi-ta-table-stacked-on-mobile &` branch that drops
the cell padding on small screens. It is dropped here with the rest of the card
rendering, which waits on the layout columns (`Columns\Layout`) — the same reason
`CanBeStackedOnMobile` is **blocked** on the package
[README](README.md#features).
