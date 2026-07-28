# Modal (support)

`packages/support/templates/components/Modal/index.html.twig` — the shared
`<twig:SylamentSupport:Modal>` primitive, a native-`<dialog>` re-architecture of
Filament's Alpine-driven `<x-filament::modal>`. It is template-only: there is no
Sylament PHP `ModalComponent` class. The port covers the chrome the `actions`
layer drives — heading/description/icon header with a close button, body +
footer slots, footer actions and their alignment, a semantic width, slide-over
(with position), sticky header/footer, modal alignment, click-through, the
extra window/overlay attribute bags, and the `sylament--support--modal` Stimulus
open/close/escape/click-away/dismiss/restore wiring. What is still deferred is
the `ModalComponent` global-defaults seam and a short list of Filament props
with no consumer yet.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/View/Components/ModalComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent.php)
  — the static-config class (`$hasCloseButton`, `$isClosedByClickingAway`, …)
- [`support/resources/views/components/modal/index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php)
  — the Blade view whose props and structure this template diffs against

## Surface

_Blocked by_ names what has to land first. _Detail_ carries **detail** → the
write-up in [Details](#details), and/or **diverges** → the note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<dialog>` + Stimulus open/close/restore | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) x-data `filamentModal` | done | — | [diverges](#the-modal-is-re-architected-onto-native-dialog) |
| Dismissal — ESC (`closeByEscaping`), click-away (`closeByClickingAway`), close button | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `keydown.window.escape` / `click.self` + [`modal.js`](../../../vendor/filament/support/resources/js/components/modal.js) `isTopmost()` | done | — | [diverges](#esc-closes-through-the-native-cancel-event), [diverges](#click-away-closes-on-a-pointerdown-and-click-origin-pair), [diverges](#the-client-side-close-reports-itself-with-a-dismissed-event) |
| Open/close transitions | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `x-transition` | done | — | [diverges](#transitions-are-css-native-not-js-driven) |
| `open` state value (server-rendered open/close) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) Alpine `isOpen` | done | — | [diverges](#the-open-state-is-a-stimulus-value-not-only-an-event) |
| Header — heading / description / icon / iconColor + `aria-*` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) heading block | done | — | [diverges](#the-header-icon-drops-filaments-background-wrapper) |
| Content slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `$slot` | done | — | — |
| Footer slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `$footer` | done | — | — |
| Semantic width class | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `width` | done | — | [diverges](#the-default-width-is-md-not-sm) |
| `returnsFocus` (↔ `focusTrapReturnsFocus`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `focusTrapReturnsFocus` | done | — | [diverges](#returnsfocus-stays-true-where-filaments-action-modal-hardcodes-false) |
| Close button (+ `closeByEscaping` tab-order juggling, `autofocus` translation) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `closeButton` | done | — | [diverges](#the-chrome-defaults-are-literal-constants-not-modalcomponent-statics) |
| `ModalComponent` static config (global defaults seam) | [`ModalComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent.php) | todo | — | [detail](#modalcomponent-static-config) |
| Slide-over (+ position), sticky header/footer, alignment, footer actions + their alignment, clickThrough, extra window/overlay attribute bags | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | [diverges](#alignment-strings-pass-through-verbatim-where-filament-drops-them), [diverges](#click-through-is-a-non-modal-dialogshow) |
| `alert`, `trigger`, `teleport`, `visible`, `header` slot, `ariaLabelledby` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#deferred-filament-modal-surface-breadth-fill) |
| Configurable / quiet-close event names | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `openEventName`/`closeEventName`/`closeQuietlyEventName` | skipped | — | [diverges](#event-names-are-fixed-and-there-is-no-quiet-close) |
| `wire:submit` form wrapper / `wire:key` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `wireSubmitHandler` | blocked | forms (LiveComponent form-in-modal) | [detail](#livewire-form-in-modal-wiring-is-not-ported) |

The done rows are exercised by
[`actions/tests/Integration/Components/ModalRenderTest.php`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php)
— a support-owned primitive render-tested from the `actions` suite (the first
consumer that boots a Twig-component kernel), asserting the closed `<dialog>`, the
`sylament--support--modal` controller/action wiring (including the
`cancel`/`pointerdown`/`click`/`close` chrome actions and the close button's
`dismiss`), conditional header/content/footer, the `sy-modal-<width>` class on the
window, `aria-labelledby`/`aria-describedby`, the gray-icon color suppression, the
close button and its tab-order/window-autofocus juggling, the slide-over/sticky/
click-through/alignment variant classes, the footer-actions wrapper and its
alignment class, and both extra attribute bags landing on their own elements.

## Details

Write-ups for the unfinished rows only.

### `ModalComponent` static config

Filament ships a `ModalComponent` class whose four static properties +
configurators (`autofocus()`, `closeButton()`, `closedByClickingAway()`,
`closedByEscaping()`) let an app change the modal defaults globally; the blade
view and `CanOpenModal` both read those statics as their fallbacks. Sylament has
no such class: the four defaults are literal constants — `true` in the
template's `{% props %}` list and `?? true` in `CanOpenModal`'s
`hasModalCloseButton()` / `isModalClosedByClickingAway()` /
`isModalClosedByEscaping()` / `isModalAutofocused()` getters, which are exactly
the insertion points a later seam replaces (see the
[divergence](#the-chrome-defaults-are-literal-constants-not-modalcomponent-statics)).
Everything the statics *gate* is ported — the close button itself is a **done**
row above. Next step, when an app needs kit-wide modal defaults: add a Sylament
config seam (a `ComponentManager`-style global configurator is the closest
existing precedent) and read it from those four getters and the matching
template prop defaults.

### Deferred Filament modal surface (breadth-fill)

Six props of the full Filament view still have no port and no consumer: `alert`
(`role="alertdialog"`), `trigger`, `teleport`, `visible`, the `header`
replacement slot (see the
[divergence](#the-header-replacement-slot-is-not-ported)), and the explicit
`ariaLabelledby` prop (the template derives `aria-labelledby` from `id` +
heading instead). These are breadth-fill, not rejected — each returns with the
first consumer that needs it.

### Livewire form-in-modal wiring is not ported

Filament swaps the modal window element to a `<form wire:submit.prevent=…>` when a
`wire:submit.prevent` attribute is present, and stamps `wire:key` on each region.
Sylament renders a plain `<dialog>` with no form wrapper or `wire:*` keys — the
action modal drives its footer actions through `live_action(...)` instead. Form-in-modal
is **blocked** on the `schemas` / `forms` form-in-modal breadth: it lands there as
the UX LiveComponent form wrapper (not `wire:*`) and the per-region keys the modal
needs, in the same change.

## Divergences

Deliberate deviations from Filament — permanent unless a row above flips.

### The modal is re-architected onto native `<dialog>`

Filament's modal is a stack of `<div>`s driven by an Alpine `filamentModal`
object that hand-implements focus-trap (`@alpinejs/focus` `x-trap`), ESC
(topmost-only), click-away, refcounted body scroll-lock, enter/leave transitions,
and a stacking model, plus a separate `fi-modal-close-overlay` element and an
`x-teleport` template. Sylament renders a native `<dialog>` and lets the platform
supply focus-trap, ESC (the `cancel` event), top-layer stacking, and page inerting.

The dialog is not the visible box: it is the **overlay + centering container**.
It spans the viewport, paints the dim itself as its own `background-color`
(`::backdrop` is neutralized to `display: none`, so there is one dimmable
surface rather than two), grid-centers its single child, and scrolls when no
sticky pane moves the scroll inside; the visible panel is an inner
`div.sy-modal-window` carrying the width ladder, the radius, the shadow, and the
slide-over transform. Filament's separate root and overlay elements therefore
**collapse into the dialog**: caller `attributes` and `extraOverlayAttributes`
both target it (later scalars win, class/style tokens append, per `attrs()`
semantics), while `extraWindowAttributes` target the window div. That collapse is
what makes click-away detection exact — `event.target === dialog` means the
backdrop area and nothing else — and it is why `clickThrough` can be a container
variant rather than a JS mode.

The [Stimulus controller](../../../packages/support/assets/src/controllers/modal_controller.ts)
drives `showModal()` / `show()` / `close()` from an `open` Stimulus value (see
[the open state is a value, not only an event](#the-open-state-is-a-stimulus-value-not-only-an-event)),
bridges id-matched `sylament-open-modal` / `sylament-close-modal` window events to
the same calls, gates ESC and click-away, and restores opener focus on close.
What stays dropped from Filament's object: the body **scroll-lock** (the top
layer plus the container's own `overflow-y` covers the modal case, and a
click-through panel must not lock scrolling anyway) and the Alpine stacking
model. This is more than the systematic Alpine→Stimulus / Blade→Twig
translations — it is a structural rewrite recorded here, the headline divergence
of the modal work.

_See also: the [rename table](../renames.md) for the Alpine→Stimulus and
Blade→Twig (`view()`→`template()`) translations this builds on._

### ESC closes through the native `cancel` event

Filament binds `x-on:keydown.window.escape` and closes only when the modal is
`isTopmost()` — a hand-maintained stack check over `document.querySelectorAll`.
Sylament wires the
dialog's native `cancel` event to the controller's `handleCancel()`, which calls
`preventDefault()` when `closeByEscaping` is off and otherwise lets the platform
close the dialog. The browser fires `cancel` on the topmost modal dialog only, so
Filament's `isTopmost()` has nothing to port. Two consequences worth knowing:
some engines treat repeated ESC as a user-intent escape hatch and may force-close
a dialog whose `cancel` was prevented (accepted — `closeByEscaping(false)` is a
nudge, not a trap); and a **click-through** modal is non-modal
(`dialog.show()`), which receives no `cancel` event at all, so the controller
registers a document-level `keydown` Escape listener for the lifetime of that
open state, gated on the same value and torn down on `close` and `disconnect`.

### Click-away closes on a pointerdown-and-click origin pair

Filament closes on `x-on:click.self` on its `fi-modal-window-ctn` wrapper — the
element the window sits inside — so any click whose target resolves to that
wrapper closes the modal. With the dialog *being* the overlay, Sylament has no
wrapper to bind to and instead closes when `closeByClickingAway` holds **and**
both the `pointerdown` origin and the `click` target are the dialog element
itself. The paired origin is stricter than `.self` on purpose: a text-selection
drag that starts inside the window and releases outside it fires a `click` whose
target is the common ancestor — which `.self` accepts and the pointerdown pair
rejects, because the press did not begin on the backdrop. Losing a selection to
an accidental close is the exact failure this avoids.

### Click-through is a non-modal `dialog.show()`

Filament's `clickThrough` renders a dim-less panel and hand-stamps
`aria-modal="false"` to undo the modal semantics it would otherwise assert.
Sylament calls `dialog.show()` instead of `showModal()`, which *is* the
non-modal mode natively: no `aria-modal` stamping, no page inerting, no
top-layer promotion. Because leaving the top layer also drops the UA positioning
that comes with it, the click-through variant re-states `position: fixed; inset:
0` and a z-index in CSS, paints no dim, and lets pointer events pass through the
container while the window takes them back. The template also forces
`closeByClickingAway` to `false` in this mode, Filament-exact.

### Transitions are CSS-native, not JS-driven

Filament runs 300 ms enter/leave transitions through Alpine's `x-transition`
directives. Sylament expresses the same motion in the component stylesheet with
`@starting-style` plus `transition-behavior: allow-discrete` on `display` and
`overlay`: the dim fades, a centered window scales `95% → 100%` with opacity, a
slide-over window translates in from its edge (RTL-mirrored), and a
screen-width modal skips the movement entirely — Filament's own exclusion. No
JavaScript participates, and engines without `@starting-style` simply show the
modal without the animation. One honest limit: the **close** animation only
plays for client-side closes (ESC, click-away, close button). A server-driven
unmount removes the element from the DOM in the same morph that flips the open
state, so there is nothing left to transition out.

### The client-side close reports itself with a `dismissed` event

Filament's modal dispatches `modal-closed` and the action layer listens for it to
unmount the mounted action. Sylament's controller listens to the dialog's native
`close` event and dispatches `sylament--support--modal:dismissed` (bubbling)
**only when the `open` Stimulus value is still `true`** — a server-driven close
flips that value to `false` before calling `close()`, so a still-true value is
precisely the signal that the close originated in the browser and the server does
not know yet. Actions' [`ActionModal`](../actions/action.md) binds that event to
`unmountAction` on the host live component, which is the same server sync
Filament gets from `modal-closed`; support itself only emits, and never assumes a
listener.

### The chrome defaults are literal constants, not `ModalComponent` statics

Filament sources four modal defaults from static properties on `ModalComponent`
(`$hasCloseButton`, `$isClosedByClickingAway`, `$isClosedByEscaping`,
`$isAutofocused`), so an app can change them kit-wide in a service provider.
Sylament resolves to the *same values* (close button on, click-away on, ESC on,
autofocus on) but as literals — `{% props %}` defaults in the template and
`?? true` fallbacks in `CanOpenModal`'s getters. The observable per-modal
behavior matches Filament's out-of-the-box behavior exactly; what is missing is
only the global override seam, tracked as its own
[**todo** row](#modalcomponent-static-config).

### Alignment strings pass through verbatim where Filament drops them

Filament's modal accepts `Alignment|string|null` and renders a class only for the
enum cases, silently dropping any other string. Sylament resolves
`alignment` / `footerActionsAlignment` as `value ?? raw` (the `width` idiom) and
then branches: a known value (`start`, `left`, `center`, `end`, `right`) emits
`sy-align-<value>`, and **any other string is emitted verbatim as a class**, so a
consumer can point the alignment slot at its own CSS. This matches
[`HasAlignment`](has-alignment.md)'s string-as-is contract and the `width`
escape hatch already in this template; the delta is deliberate and tiny — where
Filament renders nothing, Sylament renders the string the caller asked for.

The prop defaults differ on paper and not in effect: Filament's blade defaults
both props to `Alignment::Start`, Sylament's `{% props %}` default
`footerActionsAlignment` to `null` (and `alignment` to `'start'`). Blade's
default only applies when the attribute is *omitted*, and the action modal always
passes an explicit value — `getModalFooterActionsAlignment()` returns `null` for
a non-confirmation action in both codebases — so both render a `<footer>` with no
alignment class there, and both leave the footer-actions layout rules (all scoped
under an alignment class) inactive. Parity, including the sharp edge.

### `returnsFocus` stays `true` where Filament's action modal hardcodes `false`

Filament's `action-modal.blade.php` passes `:focus-trap-returns-focus="false"`,
because a Livewire morph can replace the trigger element between open and close,
leaving Alpine's focus trap to restore focus to a detached node. Sylament's
controller captures the opener element at show time and calls `focus()` on it
from the dialog's `close` event — a silent no-op if that element is gone by
then, never a thrown error or a focus jump; actions'
[`ActionModal`](../actions/action.md) leaves `returnsFocus` at its `true`
default and keyboard users land back on the trigger they came from.

### The header icon drops Filament's background wrapper

Filament wraps the header icon in a `fi-modal-icon-ctn > div.fi-modal-icon-bg`
whose background color comes from `ComponentAttributeBag->color(IconComponent::class, $iconColor)`.
Sylament renders `sy-modal-icon-ctn` directly around the icon SVG and applies the
color as `sy-color sy-color-<name>` classes on the icon itself (via
`generateIconHtml`), with no colored background element. A non-`gray` / non-empty
`iconColor` yields the color classes; gray or an iterable/empty value suppresses
them.

### The `header` replacement slot is not ported

Filament's header can be replaced wholesale by a `header` slot, bypassing the
heading/description/icon composition entirely. Sylament renders only the
composed header (now including the close button), so a consumer that wants
custom header markup has no seam for it. The rest of what this divergence used
to cover has landed: the `footerActions` array renders inside
`sy-modal-footer-actions` (the `footer` block slot still wins when filled,
Filament-exact), `footerActionsAlignment` and `alignment` both emit alignment
classes, and actions' `ActionModal` passes real `Action` instances through.
Reversible when a consumer needs the custom-header form.

### The default width is `md`, not `sm`

Filament defaults `width` to `'sm'`; Sylament defaults it to `'md'`. The width is
emitted verbatim as a `sy-modal-<value>` class rather than resolved through the
`Width` enum's `fi-width-*` / `fi-width-screen` logic.

### The open state is a Stimulus value, not only an event

Filament opens a modal purely by event — `x-on:open-modal.window="… open()"` — and
Sylament started the same way. That cannot work for a modal whose element is
created by the very response that asks it to open, which is exactly what a mounted
action does: UX LiveComponent's `processRerender()` morphs the new DOM and
dispatches the queued browser events **synchronously in one task**, while Stimulus
binds `data-action` listeners from a `MutationObserver` callback — a microtask that
runs afterwards. The freshly-inserted `<dialog>` is not listening yet, so the first
click rendered a closed dialog and only a second click (by when the element existed
and was bound) opened it.

So the authoritative signal is state, not a notification: `Modal` takes an `open`
prop rendered as a `data-sylament--support--modal-open-value` attribute, and the
controller's `openValueChanged()` calls `showModal()` / `close()`. Stimulus invokes
that callback on connect and on every later attribute change, whenever those
happen, so no ordering can defeat it — and it is the honest encoding, since "this
modal is open" *is* server state (`mountedActions` being non-empty). Both calls are
guarded on `element.open`, so the value and event paths are idempotent and can
coexist. The window events are kept for imperative open/close from code that has no
server state to render. Nothing reverses this; the event-only form is unsound for
server-rendered modals.

### Event names are fixed, and there is no quiet-close

Filament lets each modal name its `openEventName` / `closeEventName` /
`closeQuietlyEventName` and dispatches them with the modal `id` in the detail.
Sylament hard-codes `sylament-open-modal` / `sylament-close-modal` (id-matched in
the controller) and has no quiet-close variant — the quiet path exists in Filament
to suppress a Livewire re-render round-trip that the native dialog does not incur.
