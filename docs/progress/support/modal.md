---
description: The shared `<twig:SylamentSupport:Modal>` primitive, a native-`<dialog>` re-architecture of Filament's Alpine-driven `<x-filament::modal>`. (draft)
ports:
  - vendor/filament/support/src/View/Components/ModalComponent.php
  - vendor/filament/support/resources/views/components/modal/index.blade.php
owns:
  - packages/support/assets/src/controllers/modal_controller.ts
  - packages/support/assets/src/styles/components/modal.css
  - packages/support/templates/components/Modal/
---
# Modal (support)

`packages/support/templates/components/Modal/index.html.twig` — the shared `<twig:SylamentSupport:Modal>` primitive, a native-`<dialog>` re-architecture of Filament's Alpine-driven `<x-filament::modal>`.
It is template-only: there is no Sylament PHP `ModalComponent` class.
The port covers the chrome the `actions` layer drives — heading/description/icon header with a close button, body + footer slots, footer actions and their alignment, a semantic width, slide-over (with position), sticky header/footer, modal alignment, click-through, the extra window/overlay attribute bags, and the `sylament--support--modal` Stimulus open/close/escape/click-away/dismiss/restore wiring.
What is still deferred is the `ModalComponent` global-defaults seam and a short list of Filament props with no consumer yet.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<dialog>` + Stimulus open/close/restore | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) x-data `filamentModal` | done | — | [diverges](#the-modal-is-re-architected-onto-native-dialog) |
| Dismissal — ESC (`closeByEscaping`), click-away (`closeByClickingAway`), close button | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `keydown.window.escape` / `click.self` + [`modal.js`](../../../vendor/filament/support/resources/js/components/modal.js) `isTopmost()` | done | — | [diverges](#esc-closes-through-the-native-cancel-event), [diverges](#click-away-closes-on-a-pointerdown-and-click-origin-pair), [diverges](#the-client-side-close-reports-itself-with-a-dismissed-event) |
| Open/close transitions | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `x-transition` | done | — | [diverges](#transitions-are-css-native-not-js-driven) |
| `open` state value (server-rendered open/close), mirrored into the native `open` attribute | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) Alpine `isOpen` | done | — | [diverges](#the-open-state-is-a-stimulus-value-not-only-an-event), [diverges](#the-open-attribute-is-rendered-too-and-the-controller-re-normalizes-a-morphed-in-dialog) |
| Stacking (a modal opened over another) | [`modal.js`](../../../vendor/filament/support/resources/js/components/modal.js) `isTopmost()` + the `fi-modal-open` sibling rules | done | — | [diverges](#stacking-is-the-native-top-layer-and-the-overlaid-scrim-is-server-stamped) |
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
| Form wrapper (`submitHandler` swaps the window `<div>` to `<form>` + `live_action`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `wireSubmitHandler` | done | — | [diverges](#the-form-wrapper-carries-live_action-not-wiresubmit) |
| Per-region `wire:key` equivalent | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `wire:key` on header/content/footer | blocked | schemas partial rendering | [detail](#the-per-region-morph-keys-are-not-ported) |

The done rows are exercised by [`actions/tests/Integration/Components/ModalRenderTest.php`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php) — a support-owned primitive render-tested from the `actions` suite (the first consumer that boots a Twig-component kernel), asserting the closed `<dialog>`, the `sylament--support--modal` controller/action wiring (including the `cancel`/`pointerdown`/`click`/`close` chrome actions and the close button's `dismiss`), conditional header/content/footer, the `sy-modal-<width>` class on the window, `aria-labelledby`/`aria-describedby`, the gray-icon color suppression, the close button and its tab-order/window-autofocus juggling, the slide-over/sticky/ click-through/alignment variant classes, the footer-actions wrapper and its alignment class, both extra attribute bags landing on their own elements, and the `open` attribute (`open=""` since ux-twig-component 3.5, the value a browser gives it too) plus the `sy-modal-overlaid` class.

**What no test in this repo can reach.**
There is no JS test rig — no jsdom, no browser driver, nothing that instantiates a Stimulus controller — so every render test asserts *markup* and nothing asserts *behavior*.
Everything the [`modal_controller`](../../../packages/support/assets/src/controllers/modal_controller.ts) does is verified by reasoning and by tracing the installed vendor sources (Stimulus' value-callback ordering, Idiomorph's attribute diffing, the `<dialog>` spec's close steps), never by execution.
That covers, specifically: the [modality normalization](#the-open-attribute-is-rendered-too-and-the-controller-re-normalizes-a-morphed-in-dialog) and its `MutationObserver`; the Escape listener a click-through dialog that starts open needs; the teardown path when a server-driven close lands on a dialog whose `open` attribute the morph already removed; and focus restoration across the hide/re-open cycle of a stacked frame.
A manual browser smoke test of those paths has **not been performed** — treat this whole controller as unverified-at-merge until either a JS test rig lands or someone drives it in a browser.

## Details

### `ModalComponent` static config

Filament ships a `ModalComponent` class whose four static properties + configurators (`autofocus()`, `closeButton()`, `closedByClickingAway()`, `closedByEscaping()`) let an app change the modal defaults globally; the blade view and `CanOpenModal` both read those statics as their fallbacks.
Sylament has no such class: the four defaults are literal constants — `true` in the template's `{% props %}` list and `?? true` in `CanOpenModal`'s `hasModalCloseButton()` / `isModalClosedByClickingAway()` / `isModalClosedByEscaping()` / `isModalAutofocused()` getters, which are exactly the insertion points a later seam replaces (see the [divergence](#the-chrome-defaults-are-literal-constants-not-modalcomponent-statics)).
Everything the statics *gate* is ported — the close button itself is a **done** row above.
Next step, when an app needs kit-wide modal defaults: add a Sylament config seam (a `ComponentManager`-style global configurator is the closest existing precedent) and read it from those four getters and the matching template prop defaults.

### Deferred Filament modal surface (breadth-fill)

Six props of the full Filament view still have no port and no consumer: `alert` (`role="alertdialog"`), `trigger`, `teleport`, `visible`, the `header` replacement slot (see the [divergence](#the-header-replacement-slot-is-not-ported)), and the explicit `ariaLabelledby` prop (the template derives `aria-labelledby` from `id` + heading instead).
These are breadth-fill, not rejected — each returns with the first consumer that needs it.

### The per-region morph keys are not ported

Filament stamps `wire:key` on the modal's header/content/footer regions so a Livewire morph tracks each one across a form's own re-renders.
Sylament has no per-region key equivalent.
The **form-in-modal wiring itself is ported** — the window-element swap and its submit binding both ship (see [the form wrapper carries `live_action()`, not `wire:submit`](#the-form-wrapper-carries-live_action-not-wiresubmit)) — and a live browser pass confirmed idiomorph keeps a focused input's node and value across a foreign re-render inside an open modal form, so nothing is currently broken by the absence.
The keys stay **blocked** on the `schemas` / `forms` breadth that would first exercise region-level re-render stability (partial rendering, a form whose header or footer changes independently of its body), not on the form wrapper.

## Divergences

### The modal is re-architected onto native `<dialog>`

Filament's modal is a stack of `<div>`s driven by an Alpine `filamentModal` object that hand-implements focus-trap (`@alpinejs/focus` `x-trap`), ESC (topmost-only), click-away, refcounted body scroll-lock, enter/leave transitions, and a stacking model, plus a separate `fi-modal-close-overlay` element and an `x-teleport` template.
Sylament renders a native `<dialog>` and lets the platform supply focus-trap, ESC (the `cancel` event), top-layer stacking, and page inerting.

The dialog is not the visible box: it is the **overlay + centering container**.
It spans the viewport, paints the dim itself as its own `background-color` (`::backdrop` is neutralized to `display: none`, so there is one dimmable surface rather than two), grid-centers its single child, and scrolls when no sticky pane moves the scroll inside; the visible panel is a single inner `.sy-modal-window` element carrying the width ladder, the radius, the shadow, and the slide-over transform.
That element's **tag** is not fixed — it renders as a `<div>` normally and as a `<form>` when a `submitHandler` is passed ([why](#the-form-wrapper-carries-live_action-not-wiresubmit)) — so read every `.sy-modal-window` reference here and in `modal.css` as class-keyed, never tag-keyed.
Filament's separate root and overlay elements therefore **collapse into the dialog**: caller `attributes` and `extraOverlayAttributes` both target it (later scalars win, class/style tokens append, per `attrs()` semantics), while `extraWindowAttributes` target the window element.
That collapse is what makes click-away detection exact — `event.target === dialog` means the backdrop area and nothing else — and it is why `clickThrough` can be a container variant rather than a JS mode.

The [Stimulus controller](../../../packages/support/assets/src/controllers/modal_controller.ts) drives `showModal()` / `show()` / `close()` from an `open` Stimulus value (see [the open state is a value, not only an event](#the-open-state-is-a-stimulus-value-not-only-an-event)), bridges id-matched `sylament-open-modal` / `sylament-close-modal` window events to the same calls, gates ESC and click-away, and restores opener focus on close.
What stays dropped from Filament's object: the body **scroll-lock** (the top layer plus the container's own `overflow-y` covers the modal case, and a click-through panel must not lock scrolling anyway) and the Alpine stacking model.
This is more than the systematic Alpine→Stimulus / Blade→Twig translations — it is a structural rewrite recorded here, the headline divergence of the modal work.

_See also: the [rename table](../renames.md) for the Alpine→Stimulus and Blade→Twig (`view()`→`template()`) translations this builds on._

### The form wrapper carries `live_action()`, not `wire:submit`

Filament swaps the modal window element to `<form wire:submit.prevent="{{ $wireSubmitHandler }}">` when `wireSubmitHandler` is filled.
Sylament's `submitHandler` prop does the same tag swap — `.sy-modal-window` renders as `<form>` instead of `<div>`, carrying the exact same classes, so no CSS rule (all keyed off `.sy-modal-window`, never the tag — see `packages/support/assets/src/styles/components/modal.css:222,226,232,244`) needed to change — but wires the submit through `live_action(submitHandler ~ ':prevent', {}, {}, 'submit')` instead of `wire:submit.prevent`, emitting `data-action="submit->live#action:prevent"` and `data-live-action-param="<submitHandler>"`.
The action layer now supplies that value: [`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig) derives `submitHandler` from `action.hasFormWrapper` alone — `Action::getLiveComponentCallMountedActionName()` when true, `null` otherwise — which is the same single predicate [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) branches on, so the window is a `<form>` exactly when its submit button is a bare `type="submit"` with no click handler of its own.
Verified in a browser: Enter in a text field inside the form fires exactly one `callMountedAction` request and no page navigation, and an empty `required` field blocks the submit client-side with zero requests (see [schemas/can-be-marked-as-required.md](../schemas/can-be-marked-as-required.md#the-native-required-attribute-is-load-bearing-inside-a-modal-form)).

### ESC closes through the native `cancel` event

Filament binds `x-on:keydown.window.escape` and closes only when the modal is `isTopmost()` — a hand-maintained stack check over `document.querySelectorAll`.
Sylament wires the dialog's native `cancel` event to the controller's `handleCancel()`, which calls `preventDefault()` when `closeByEscaping` is off and otherwise lets the platform close the dialog.
The browser fires `cancel` on the topmost modal dialog only, so Filament's `isTopmost()` has nothing to port.
Two consequences worth knowing: some engines treat repeated ESC as a user-intent escape hatch and may force-close a dialog whose `cancel` was prevented (accepted — `closeByEscaping(false)` is a nudge, not a trap); and a **click-through** modal is non-modal (`dialog.show()`), which receives no `cancel` event at all, so the controller registers a document-level `keydown` Escape listener for the lifetime of that open state, gated on the same value, torn down on `close` and `disconnect`, and skipping a keydown whose default was already prevented — so a child control that consumed the key (forms' select engine closing its open dropdown) takes the first Escape and the dialog the second, the same split the platform gives a `showModal()` dialog, whose `cancel` a prevented keydown suppresses.

### Click-away closes on a pointerdown-and-click origin pair

Filament closes on `x-on:click.self` on its `fi-modal-window-ctn` wrapper — the element the window sits inside — so any click whose target resolves to that wrapper closes the modal.
With the dialog *being* the overlay, Sylament has no wrapper to bind to and instead closes when `closeByClickingAway` holds **and** both the `pointerdown` origin and the `click` target are the dialog element itself.
The paired origin is stricter than `.self` on purpose: a text-selection drag that starts inside the window and releases outside it fires a `click` whose target is the common ancestor — which `.self` accepts and the pointerdown pair rejects, because the press did not begin on the backdrop.
Losing a selection to an accidental close is the exact failure this avoids.

### Click-through is a non-modal `dialog.show()`

Filament's `clickThrough` renders a dim-less panel and hand-stamps `aria-modal="false"` to undo the modal semantics it would otherwise assert.
Sylament calls `dialog.show()` instead of `showModal()`, which *is* the non-modal mode natively: no `aria-modal` stamping, no page inerting, no top-layer promotion.
Because leaving the top layer also drops the UA positioning that comes with it, the click-through variant re-states `position: fixed; inset: 0` and a z-index in CSS, paints no dim, and lets pointer events pass through the container while the window takes them back.
The template also forces `closeByClickingAway` to `false` in this mode, Filament-exact.

### Transitions are CSS-native, not JS-driven

Filament runs 300 ms enter/leave transitions through Alpine's `x-transition` directives.
Sylament expresses the same motion in the component stylesheet with `@starting-style` plus `transition-behavior: allow-discrete` on `display` and `overlay`: the dim fades, a centered window scales `95% → 100%` with opacity, a slide-over window translates in from its edge (RTL-mirrored), and a screen-width modal skips the movement entirely — Filament's own exclusion.
No JavaScript participates, and engines without `@starting-style` simply show the modal without the animation.
One honest limit: the **close** animation only plays for client-side closes (ESC, click-away, close button).
A server-driven unmount removes the element from the DOM in the same morph that flips the open state, so there is nothing left to transition out.

Five `<dialog>`-plus-Tailwind-v4 traps this stylesheet has to work around, every one found by rendering the built CSS in a browser rather than by any test:

- **Tailwind v4 compiles `scale-*` / `translate-*` to the independent transform properties**, not to `transform: scale()` / `transform: translate()`.
  A `transition-[transform,opacity]` list therefore animates neither, and the centered window snapped to full size while only its opacity faded.
  The window rule names `transform, translate, scale, rotate, opacity` explicitly — what Tailwind's own `transition-transform` expands to, plus opacity.
  Any future motion added here must extend that list, not assume `transform` covers it.
- **An open `<dialog>` is a UA-level scroll container** (`overflow: auto` in both axes, for `show()` as well as `showModal()`; author sheets set none of it).
  A slide-over window parked off-viewport at `translate: 100%` sits inside that scrollable area, so the container grew a horizontal scrollbar for the duration of every slide-in — indistinguishable from a page scrollbar, since the dialog covers the viewport.
  `overflow-x: clip` on the container closes it while leaving the block axis free for the centered-modal container scroll.
  The same scrollbar also stole 15px of container width, which moved the inline-end resting position of a slide-over panel, so it appeared to settle short of the edge and then snap flush when the scrollbar went away.
- **A slide-over container must be `overflow: clip` in both axes, not just the inline one.**
  `overflow-x: clip` against the UA's `overflow-y: auto` computes to `hidden`, and a `hidden` box is still programmatically scrollable — so `showModal()`, which focuses the close button while the panel is still parked off-viewport at `translate: 100%`, made the browser scroll the container by the panel's entire width to reveal it.
  That offset is clamped back toward zero on the main thread as the transition shrinks the overflow, always a step behind the compositor-driven transform, and the difference is painted as the panel overshooting its resting place and drifting back.
  Clipping both axes removes the scroll port entirely; the window keeps its own `overflow-y: auto`, so nothing on the container needs to scroll.
  Note that no DOM measurement sees this: `offsetLeft` ignores scroll, computed `translate` ignores it too, and `getBoundingClientRect()` nets the two against each other — it is visible only in `scrollLeft` or in pixels.
- **No layout property may be gated on `[open]`.**
  The attribute drops the instant a close begins, but `display` is held for the transition's length by allow-discrete, so anything keyed to `[open]` collapses under a modal that is still fully opaque on screen.
  The centered frame's `p-4` did exactly that — a `1fr auto 3fr` grid losing 16px of block padding takes its centered row 8px up, visible as a jump at the start of every close — and the container's `overflow-y: auto` dropped a scrolled modal back to its top in the same frame.
  Both now apply unconditionally; they are inert while the dialog is `display: none`.
  Only paint-level state (the dim) and `display` itself belong on `[open]`.
- **A settled non-`none` individual transform is a containing block for `position: fixed` descendants.**
  The open state used to end at `scale-100` / `translate-x-0`, which are real transform values, not the absence of one — so the window became the containing block of any fixed-positioned dropdown inside it, and the window's own `overflow-y: auto` then clipped it.
  The open states now end at `scale-none` / `translate-none`, which interpolate from the closed `scale-95` / `-translate-x-full` frames exactly as before (Chromium-verified 2026-08-19) and settle with no containing block, so the modal can be a [fixed positioning context](#the-modal-is-a-fixed-positioning-context) for what it contains.
  The `@starting-style` and closed frames are untouched.

### The client-side close reports itself with a `dismissed` event

Filament's modal dispatches `modal-closed` and the action layer listens for it to unmount the mounted action.
Sylament's controller listens to the dialog's native `close` event and dispatches `sylament--support--modal:dismissed` (bubbling) **only when the `open` Stimulus value is still `true`** — a server-driven close flips that value to `false` before calling `close()`, so a still-true value is precisely the signal that the close originated in the browser and the server does not know yet.
Actions' [`ActionModal`](../actions/action.md) binds that event to `unmountAction` on the host live component, which is the same server sync Filament gets from `modal-closed`; support itself only emits, and never assumes a listener.

### The chrome defaults are literal constants, not `ModalComponent` statics

Filament sources four modal defaults from static properties on `ModalComponent` (`$hasCloseButton`, `$isClosedByClickingAway`, `$isClosedByEscaping`, `$isAutofocused`), so an app can change them kit-wide in a service provider.
Sylament resolves to the *same values* (close button on, click-away on, ESC on, autofocus on) but as literals — `{% props %}` defaults in the template and `?? true` fallbacks in `CanOpenModal`'s getters.
The observable per-modal behavior matches Filament's out-of-the-box behavior exactly; what is missing is only the global override seam, tracked as its own [**todo** row](#modalcomponent-static-config).

One more nuance on the close button: Sylament's header-rendering condition (`hasIcon or hasHeading or hasDescription`) is a superset of Filament's (`$heading || $header`) — an icon-only or description-only modal renders a header, and therefore a close button, in Sylament where Filament renders neither.

### Alignment strings pass through verbatim where Filament drops them

Filament's modal accepts `Alignment|string|null` and renders a class only for the enum cases, silently dropping any other string.
Sylament resolves `alignment` / `footerActionsAlignment` as `value ?? raw` (the `width` idiom) and then branches: a known value (`start`, `left`, `center`, `end`, `right`) emits `sy-align-<value>`, and **any other string is emitted verbatim as a class**, so a consumer can point the alignment slot at its own CSS.
This matches [`HasAlignment`](has-alignment.md)'s string-as-is contract and the `width` escape hatch already in this template; the delta is deliberate and tiny — where Filament renders nothing, Sylament renders the string the caller asked for.

The prop defaults differ on paper and not in effect: Filament's blade defaults both props to `Alignment::Start`, Sylament's `{% props %}` default `footerActionsAlignment` to `null` (and `alignment` to `'start'`).
Blade's default only applies when the attribute is *omitted*, and the action modal always passes an explicit value — `getModalFooterActionsAlignment()` returns `null` for a non-confirmation action in both codebases — so both render a `<footer>` with no alignment class there, and both leave the footer-actions layout rules (all scoped under an alignment class) inactive.
Parity, including the sharp edge.

One more breadcrumb: Filament v5.7.1's own start+icon indent rules are dead selectors upstream — their `fi-align-*` rules key off the `.fi-modal` root while v5.7.1's blade stamps `fi-align-*` only on the window, so real Filament renders `px-6` everywhere.
Sylament re-keyed the rules to the window, making the indent live — following the source's intent over its rendered output.

### `returnsFocus` stays `true` where Filament's action modal hardcodes `false`

Filament's `action-modal.blade.php` passes `:focus-trap-returns-focus="false"`, because a Livewire morph can replace the trigger element between open and close, leaving Alpine's focus trap to restore focus to a detached node.
Sylament's controller captures the opener element at show time and calls `focus()` on it from the dialog's `close` event — a silent no-op if that element is gone by then, never a thrown error or a focus jump; actions' [`ActionModal`](../actions/action.md) leaves `returnsFocus` at its `true` default and keyboard users land back on the trigger they came from.

### The header icon drops Filament's background wrapper

Filament wraps the header icon in a `fi-modal-icon-ctn > div.fi-modal-icon-bg` whose background color comes from `ComponentAttributeBag->color(IconComponent::class, $iconColor)`.
Sylament renders `sy-modal-icon-ctn` directly around the icon SVG and applies the color as `sy-color sy-color-<name>` classes on the icon itself (via `generateIconHtml`), with no colored background element.
The color still resolves through the `ModalComponent\IconComponent` policy Filament names, so a palette handed over inline as an array yields `sy-color` plus the `--sy-color-*` style variables; only an empty `iconColor`, or gray by name — the policy implements `HasDefaultGrayColor` — suppresses both.

### The `header` replacement slot is not ported

Filament's header can be replaced wholesale by a `header` slot, bypassing the heading/description/icon composition entirely.
Sylament renders only the composed header (now including the close button), so a consumer that wants custom header markup has no seam for it.
The rest of what this divergence used to cover has landed: the `footerActions` array renders inside `sy-modal-footer-actions` (the `footer` block slot still wins when filled, Filament-exact), `footerActionsAlignment` and `alignment` both emit alignment classes, and actions' `ActionModal` passes real `Action` instances through.
Reversible when a consumer needs the custom-header form.

### The default width is `md`, not `sm`

Filament defaults `width` to `'sm'`; Sylament defaults it to `'md'`.
The width is emitted verbatim as a `sy-modal-<value>` class rather than resolved through the `Width` enum's `fi-width-*` / `fi-width-screen` logic.

[`Sylament\Support\Enums\Width`](../../../packages/support/src/Enums/Width.php) **exists**, with all 26 cases, for panels' page and panel width surfaces, so the trigger this note was waiting on has fired and the back-fill is merely **deferred**: `width()` still takes a plain string, the class is still `sy-modal-<value>`, and nothing here was retyped.
Doing it means narrowing the property's type, mapping each case to a modal class and re-deriving the `sy-modal-screen` arm, which is a modal-sized change of its own rather than a rider on a page change.
The same deferral, for the same reason, is recorded on [`Table::filtersFormWidth()`](../tables/table.md#surface).

### The open state is a Stimulus value, not only an event

Filament opens a modal purely by event — `x-on:open-modal.window="… open()"` — and Sylament started the same way.
That cannot work for a modal whose element is created by the very response that asks it to open, which is exactly what a mounted action does: UX LiveComponent's `processRerender()` morphs the new DOM and dispatches the queued browser events **synchronously in one task**, while Stimulus binds `data-action` listeners from a `MutationObserver` callback — a microtask that runs afterwards.
The freshly-inserted `<dialog>` is not listening yet, so the first click rendered a closed dialog and only a second click (by when the element existed and was bound) opened it.

So the authoritative signal is state, not a notification: `Modal` takes an `open` prop rendered as a `data-sylament--support--modal-open-value` attribute, and the controller's `openValueChanged()` calls `showModal()` / `close()`.
Stimulus invokes that callback on connect and on every later attribute change, whenever those happen, so no ordering can defeat it — and it is the honest encoding, since "this modal is open" *is* server state (`mountedActions` being non-empty).
The `show()` path guards on `element.open`, so the value and event paths are idempotent and can coexist.
The window events are kept for imperative open/close from code that has no server state to render.
Nothing reverses this; the event-only form is unsound for server-rendered modals.

### The `open` attribute is rendered too, and the controller re-normalizes a morphed-in dialog

The Stimulus value alone is not enough once a *stack* of modals persists across live re-renders.
`open` is therefore rendered twice from one prop: as `data-sylament--support--modal-open-value` (**intent**, what the server wants) and as the bare native `open` attribute (**state**, what the DOM is).
The reason is Idiomorph, which diffs attributes: with only the value rendered, a re-render of a page containing an already-open dialog would morph `open` *off* that element — it is not in the incoming markup — and close it behind the controller's back.
Rendering it makes the incoming markup agree with reality.

That fix creates its own problem, which the controller then absorbs.
A dialog whose `open` attribute arrived **with the markup** is open but **not modal**: no top layer, no backdrop, no focus trap, no `cancel` event, and `closeOnClickAway` is dead because there is no backdrop for its target check to hit.
Only `showModal()` produces the modal state, and the parser never calls it.
So `#normalizeModality()` runs on connect and, for the top frame, at value time: if the dialog is open, not click-through, and not already `:modal`, it `removeAttribute("open")` and calls `showModal()`.
Removing the attribute rather than calling `close()` is deliberate — the spec's close steps fire a `close` event, which the dismissal listener would read as a user close and round-trip an unmount; attribute removal runs the same cleanup silently, and nothing ever paints a closed frame in between.

Three follow-on cases, all handled in the controller and none of them observable from a Twig render test:

- **A morph can re-add `open` to a dialog the user just dismissed.**
  After a client dismissal the attribute is gone but the open *value* is still `"true"` until the round-trip completes; a re-render landing in that window re-adds `open` to the same element — no `connect()`, no `openValueChanged()` — leaving a visible, non-modal, undismissable overlay.
  A `MutationObserver` on the `open` attribute calls `#normalizeModality()`, which is the only hook that fires for that.
  It converges rather than loops: the remove/`showModal()` pair queues one more callback, and that one finds the dialog `:modal` and returns.
- **A server-driven close on a *persisting* element loses its `close` event.**
  `openValueChanged(false)` would call `element.close()`, but the morph already removed the mirrored attribute, so `element.open` is false and `close()` bails per spec — no event, no teardown.
  The callback therefore runs the teardown itself.
  It tears down **only** the Escape listener: a hidden frame is still rendered and may re-open when the frame above it unmounts, so `#opener` is kept as the eventual restore target and focus is left where the newly opened child put it.
  The gone-for-good case is `disconnect()`, which does restore focus.
- **`#captureOpener()` runs before `#show()`'s early return** and never overwrites an existing opener.
  With the attribute always rendered, a freshly-mounted dialog is already open when the controller connects, so an opener-capture placed after the early return would never run and `restoreFocus()` would silently break for every modal in the app.
  Never overwriting is what lets a frame that was hidden under a child and later re-opened still point at the trigger it was originally opened from.

### Stacking is the native top layer, and the overlaid scrim is server-stamped

Filament maintains its stack in JS: `isTopmost()` scans `document.querySelectorAll` to decide which modal ESC should close, and a `:has(~ .fi-modal-open)` sibling rule suppresses the dim of any modal that has an open one after it — both legal because its modals are plain `<div>`s in DOM order.

Sylament inherits the first for free and cannot use the second.
`showModal()` promotes a dialog into the **top layer**, and the browser fires `cancel` on the topmost modal dialog only, so ESC already targets the right frame with no stack bookkeeping — see [ESC closes through the native `cancel` event](#esc-closes-through-the-native-cancel-event).
But top-layer paint order is `showModal()` **call** order, which has no relationship to DOM order, so no sibling selector can answer "is there a dialog above me".
The port answers it on the server instead: an `overlaid` prop stamps `sy-modal-overlaid`, whose rule zeroes that dialog's own background so only the topmost frame paints a scrim (N stacked dims would otherwise compound).
The hand-off animates for free — `background-color` is already in the container's transition list.
Actions' `Modals` renderer is what computes the flag; see [modals.md](../actions/modals.md#the-parent-overlay-flag-is-server-stamped-not-a-sibling-css-selector).

### Event names are fixed, and there is no quiet-close

Filament lets each modal name its `openEventName` / `closeEventName` / `closeQuietlyEventName` and dispatches them with the modal `id` in the detail.
Sylament hard-codes `sylament-open-modal` / `sylament-close-modal` (id-matched in the controller) and has no quiet-close variant — the quiet path exists in Filament to suppress a Livewire re-render round-trip that the native dialog does not incur.

### The modal is a fixed positioning context

Filament stamps `fi-absolute-positioning-context` on its modal root, so a select dropdown opened inside a modal positions with Floating UI's `absolute` strategy — and is therefore laid out inside the window, where a sticky-pane modal's `overflow-y: auto` clips it.
Ours stamps `sy-fixed-positioning-context` on the `<dialog>` instead: forms' select engine reads the pair of marker classes off its button's ancestors and picks the `fixed` strategy under this one, so the dropdown is positioned against the viewport and escapes both the window's overflow and the dialog's.
It stays put because the engine installs a capture-phase `scroll` listener while its panel is open and repositions on every scroll, the window's included.
The class name is the prefix rename of Filament's marker pair, and nothing in support reads it — the markers carry no rules of their own in Filament either.
Tables' content container will carry the same one when it lands, which is [`content()`](../tables/table.md#surface)'s list rather than a column: `SelectColumn` ships as a **native** `<select>`, which the browser positions itself and which reads no marker class at all ([select-column.md](../tables/select-column.md)), so the first table surface that will need the marker is the one that replaces the `<table>` with a caller-supplied list.
This only works because the window's open state settles at `scale: none` / `translate: none`; see the transform trap [above](#transitions-are-css-native-not-js-driven).
