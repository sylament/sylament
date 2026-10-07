---
description: The shared `<twig:SylamentSupport:Modal>` primitive, Filament's Alpine-driven modal rebuilt on a native `<dialog>` and a Stimulus controller.
ports:
  - vendor/filament/support/src/View/Components/ModalComponent.php
  - vendor/filament/support/resources/views/components/modal/index.blade.php
  - vendor/filament/support/resources/css/components/modal.css
  - vendor/filament/support/resources/js/components/modal.js
owns:
  - packages/support/assets/src/controllers/modal_controller.ts
  - packages/support/assets/src/styles/components/modal.css
  - packages/support/templates/components/Modal/
---
# Modal (support)

[`Modal/index.html.twig`](../../../packages/support/templates/components/Modal/index.html.twig) renders the modal, the [`modal_controller`](../../../packages/support/assets/src/controllers/modal_controller.ts) (`sylament--support--modal`) drives it, and [`modal.css`](../../../packages/support/assets/src/styles/components/modal.css) styles it.
It is template-only: there is no Sylament PHP `ModalComponent` class.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Native `<dialog>` + Stimulus open/close/restore | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `filamentModal` | done | — | [diverges](#the-modal-is-re-architected-onto-native-dialog) |
| Dismissal: ESC (`closeByEscaping`), click-away (`closeByClickingAway`), close button | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) + [`modal.js`](../../../vendor/filament/support/resources/js/components/modal.js) `isTopmost()` | done | — | [diverges](#esc-closes-through-the-native-cancel-event) · [diverges](#click-away-closes-on-a-pointerdown-and-click-origin-pair) · [diverges](#the-client-side-close-reports-itself-with-a-dismissed-event) |
| Open/close transitions | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `x-transition` | done | — | [diverges](#transitions-are-css-native-not-js-driven) |
| `open` state value, mirrored into the native `open` attribute | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `isOpen` | done | — | [diverges](#the-open-state-is-a-stimulus-value-not-only-an-event) · [diverges](#the-open-attribute-is-rendered-too-and-the-controller-re-normalizes-a-morphed-in-dialog) |
| Stacking (a modal opened over another) | [`modal.js`](../../../vendor/filament/support/resources/js/components/modal.js) `isTopmost()` + the `fi-modal-open` sibling rules | done | — | [diverges](#stacking-is-the-native-top-layer-and-the-overlaid-scrim-is-server-stamped) |
| Header: `heading` / `description` / `icon` / `iconColor` + `aria-*` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) heading block | done | — | [diverges](#the-header-icon-drops-filaments-background-wrapper) |
| Content slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `$slot` | done | — | — |
| Footer slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `$footer` | done | — | — |
| `width` → `sy-modal-<width>` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `width` | done | — | [diverges](#the-default-width-is-md-not-sm) |
| `returnsFocus` (Filament's `restoresFocus`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `restoresFocus` | done | — | [diverges](#returnsfocus-stays-true-where-filaments-action-modal-hardcodes-false) |
| Close button (+ `closeByEscaping` tab-order juggling, `autofocus` translation) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `closeButton` | done | — | [diverges](#the-chrome-defaults-are-literal-constants-not-modalcomponent-statics) |
| `ModalComponent` static config (global defaults seam) | [`ModalComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent.php) | todo | — | [detail](#modalcomponents-global-defaults-have-no-sylament-seam) |
| `slideOver` + `slideOverPosition` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | — |
| `stickyHeader` / `stickyFooter` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | — |
| `alignment` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | [diverges](#alignment-strings-pass-through-verbatim-where-filament-drops-them) |
| `footerActions` + `footerActionsAlignment` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | [diverges](#alignment-strings-pass-through-verbatim-where-filament-drops-them) |
| `clickThrough` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | [diverges](#click-through-is-a-non-modal-dialogshow) |
| `extraWindowAttributes` / `extraOverlayAttributes` (Filament's `extraModalWindowAttributeBag` / `extraModalOverlayAttributeBag`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | done | — | [diverges](#the-modal-is-re-architected-onto-native-dialog) |
| `alert` (`role="alertdialog"`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#the-remaining-filament-props-wait-on-their-first-consumer) |
| `trigger` slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#the-remaining-filament-props-wait-on-their-first-consumer) |
| `teleport` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#the-remaining-filament-props-wait-on-their-first-consumer) |
| `visible` (`fi-hidden`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#the-remaining-filament-props-wait-on-their-first-consumer) |
| `header` replacement slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#the-remaining-filament-props-wait-on-their-first-consumer) |
| `ariaLabelledby` + the heading `aria-label` fallback for a modal with no `id` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#the-remaining-filament-props-wait-on-their-first-consumer) |
| Configurable / quiet-close event names | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `openEventName`/`closeEventName`/`closeQuietlyEventName` | skipped | — | [diverges](#event-names-are-fixed-and-there-is-no-quiet-close) |
| Form wrapper (`submitHandler` swaps the window `<div>` to `<form>` + `live_action`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `wireSubmitHandler` | done | — | [diverges](#the-form-wrapper-carries-live_action-not-wiresubmit) |
| Per-region `wire:key` equivalent | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `wire:key` on header/content/footer | blocked | schemas partial rendering ([schema.md](../schemas/schema.md#partial-rendering-waits-on-a-ux-livecomponent-partial-render-equivalent)) | [detail](#the-per-region-morph-keys-are-not-ported) |
| [`modal.css`](../../../packages/support/assets/src/styles/components/modal.css) | [`components/modal.css`](../../../vendor/filament/support/resources/css/components/modal.css) | done | — | [diverges](#transitions-are-css-native-not-js-driven) · [diverges](#alignment-strings-pass-through-verbatim-where-filament-drops-them) · [diverges](#the-modal-is-a-fixed-positioning-context) |

## Details

### `ModalComponent`'s global defaults have no Sylament seam

Filament's `ModalComponent` carries four static properties and their configurators (`autofocus()`, `closeButton()`, `closedByClickingAway()`, `closedByEscaping()`), which let an app change the modal defaults globally; the blade view reads those statics as its fallbacks.
Sylament has no such class: the four defaults are literal constants, `true` in the template's `{% props %}` list and `?? true` in `CanOpenModal`'s `hasModalCloseButton()` / `isModalClosedByClickingAway()` / `isModalClosedByEscaping()` / `isModalAutofocused()` getters, which are the insertion points a seam would replace (see the [divergence](#the-chrome-defaults-are-literal-constants-not-modalcomponent-statics)).
Everything the statics gate is ported.
When an app needs kit-wide modal defaults, the seam is a Sylament configuration service, with [`ComponentManager`](../../../packages/support/src/Configuration/ComponentManager.php) the closest precedent, read from those four getters and the matching template prop defaults.

### The remaining Filament props wait on their first consumer

`alert` (`role="alertdialog"`), `trigger`, `teleport`, `visible` and the explicit `ariaLabelledby` prop have no port, and no consumer passes them; actions' [`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig) is where `alert` would arrive from `isConfirmationRequired()`, as Filament's action modal sends it ([can-open-modal.md](../actions/can-open-modal.md#the-action-modal-template-only-renders-part-of-the-full-action-modal)).
The template derives `aria-labelledby` from `id` and the heading instead, and emits no accessible name for a modal with a heading but no `id`, where Filament falls back to an `aria-label` stripped from the heading.
Filament's header can also be replaced wholesale by a `header` slot, bypassing the heading, description and icon composition; Sylament renders only the composed header, so a consumer that wants custom header markup has no seam for it.
Each returns with the first consumer that needs it.

### The per-region morph keys are not ported

Filament stamps `wire:key` on the modal's header, content and footer regions so a Livewire morph tracks each one across a form's own re-renders.
Sylament has no per-region key equivalent.
The form-in-modal wiring itself is ported (see [the form wrapper carries `live_action()`, not `wire:submit`](#the-form-wrapper-carries-live_action-not-wiresubmit)), and idiomorph keeps a focused input's node and value across a foreign re-render inside an open modal form, so nothing breaks by the absence.
The keys wait on the `schemas` / `forms` breadth that would first exercise region-level re-render stability (partial rendering, a form whose header or footer changes independently of its body), not on the form wrapper.

## Divergences

### The modal is re-architected onto native `<dialog>`

Filament's modal is a stack of `<div>`s driven by an Alpine `filamentModal` object that hand-implements the focus trap (`x-trap`), ESC (topmost only), click-away, refcounted body scroll-lock, enter and leave transitions, and a stacking model, plus a separate `fi-modal-close-overlay` element and an `x-teleport` template.
Sylament renders a native `<dialog>` and lets the platform supply the focus trap, ESC (the `cancel` event), top-layer stacking and page inerting.

The dialog is not the visible box: it is the **overlay and centering container**.
It spans the viewport, paints the dim itself as its own `background-color` (`::backdrop` is set to `display: none`, so there is one dimmable surface rather than two), grid-centers its single child, and scrolls when no sticky pane moves the scroll inside; the visible panel is a single inner `.sy-modal-window` element carrying the width ladder, the radius, the shadow and the slide-over transform.
That element's **tag** is not fixed: it renders as a `<div>` normally and as a `<form>` when a `submitHandler` is passed ([why](#the-form-wrapper-carries-live_action-not-wiresubmit)), so every `.sy-modal-window` reference here and in `modal.css` is class-keyed, never tag-keyed.
Filament's separate root and overlay elements therefore **collapse into the dialog**: caller `attributes` and `extraOverlayAttributes` both target it (later scalars win, class and style tokens append, per `attrs()` semantics), while `extraWindowAttributes` target the window element.
That collapse is what makes click-away detection exact, since `event.target === dialog` means the backdrop area and nothing else, and it is why `clickThrough` can be a container variant rather than a JS mode.

The controller drives `showModal()` / `show()` / `close()` from an `open` Stimulus value (see [the open state is a value, not only an event](#the-open-state-is-a-stimulus-value-not-only-an-event)), bridges id-matched `sylament-open-modal` / `sylament-close-modal` window events to the same calls, gates ESC and click-away, and restores opener focus on close.
What stays dropped from Filament's object: the body **scroll-lock** (the top layer plus the container's own `overflow-y` covers the modal case, and a click-through panel must not lock scrolling anyway) and the Alpine stacking model.
It is a structural rewrite, beyond the systematic Alpine → Stimulus and Blade → Twig translations.

### The form wrapper carries `live_action()`, not `wire:submit`

Filament swaps the modal window element to `<form wire:submit.prevent="{{ $wireSubmitHandler }}">` when the caller passes `wire:submit.prevent`.
Sylament's `submitHandler` prop does the same tag swap, `.sy-modal-window` rendering as `<form>` instead of `<div>` with the same classes, so no rule in `modal.css` (all keyed off the class) changes; it wires the submit through `live_action(submitHandler ~ ':prevent', event: 'submit')` instead, emitting `data-action="submit->live#action:prevent"` and `data-live-action-param="<submitHandler>"`.
The action layer supplies that value: [`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig) derives `submitHandler` from `action.hasFormWrapper` alone, `Action::getLiveComponentCallMountedActionName()` when true and `null` otherwise, which is the same single predicate [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) branches on, so the window is a `<form>` exactly when its submit button is a bare `type="submit"` with no click handler of its own.
Enter in a text field inside the form fires exactly one `callMountedAction` request and no page navigation, and an empty `required` field blocks the submit client-side with no request (see [forms/field.md](../forms/field.md#the-native-required-attribute-follows-ismarkedasrequired-not-filaments-isrequired)).

### ESC closes through the native `cancel` event

Filament binds `x-on:keydown.window.escape` and closes only when the modal is `isTopmost()`, a hand-maintained stack check over `document.querySelectorAll`.
Sylament wires the dialog's native `cancel` event to the controller's `handleCancel()`, which calls `preventDefault()` when `closeByEscaping` is off and otherwise lets the platform close the dialog.
The browser fires `cancel` on the topmost modal dialog only, so Filament's `isTopmost()` has nothing to port.
Two consequences are worth knowing.
Some engines treat repeated ESC as a user-intent escape hatch and may force-close a dialog whose `cancel` was prevented, which is accepted: `closeByEscaping(false)` is a nudge, not a trap.
And a **click-through** modal is non-modal (`dialog.show()`), which receives no `cancel` event at all, so the controller registers a document-level `keydown` Escape listener for the lifetime of that open state, gated on the same value, torn down on `close` and `disconnect`, and skipping a keydown whose default was already prevented; a child control that consumed the key (forms' select engine closing its open dropdown) takes the first Escape and the dialog the second, the same split the platform gives a `showModal()` dialog, whose `cancel` a prevented keydown suppresses.

### Click-away closes on a pointerdown-and-click origin pair

Filament closes on `x-on:click.self` on its `fi-modal-window-ctn` wrapper, the element the window sits inside, so any click whose target resolves to that wrapper closes the modal.
With the dialog *being* the overlay, Sylament has no wrapper to bind to and closes when `closeByClickingAway` holds **and** both the `pointerdown` origin and the `click` target are the dialog element itself.
The paired origin is stricter than `.self` on purpose: a text-selection drag that starts inside the window and releases outside it fires a `click` whose target is the common ancestor, which `.self` accepts and the pointerdown pair rejects, because the press did not begin on the backdrop.
Losing a selection to an accidental close is the exact failure this avoids.

### Click-through is a non-modal `dialog.show()`

Filament's `clickThrough` renders a dim-less panel and stamps `aria-modal="false"` to undo the modal semantics it would otherwise assert.
Sylament calls `dialog.show()` instead of `showModal()`, which *is* the non-modal mode natively: no `aria-modal` stamping, no page inerting, no top-layer promotion.
Because leaving the top layer also drops the UA positioning that comes with it, the click-through variant restates `position: fixed; inset: 0` and a z-index in CSS, paints no dim, and lets pointer events pass through the container while the window takes them back.
The template also forces `closeByClickingAway` to `false` in this mode, as Filament does.

### Transitions are CSS-native, not JS-driven

Filament runs 300 ms enter and leave transitions through Alpine's `x-transition` directives.
Sylament expresses the same motion in the component stylesheet with `@starting-style` plus `transition-behavior: allow-discrete` on `display` and `overlay`: the dim fades, a centered window scales `95% → 100%` with opacity, a slide-over window translates in from its edge (RTL-mirrored), and a screen-width modal skips the movement entirely, Filament's own exclusion.
No JavaScript participates, and engines without `@starting-style` show the modal without the animation.
The **close** animation plays only for client-side closes (ESC, click-away, close button): a server-driven unmount removes the element from the DOM in the same morph that flips the open state, so there is nothing left to transition out.

The stylesheet works around these `<dialog>`-plus-Tailwind-v4 traps, none of which a render test can see:

- **Tailwind v4 compiles `scale-*` / `translate-*` to the independent transform properties**, not to `transform: scale()` / `transform: translate()`.
  A `transition-[transform,opacity]` list therefore animates neither, and the centered window would snap to full size while only its opacity faded.
  The window rule names `transform, translate, scale, rotate, opacity` explicitly, which is what Tailwind's own `transition-transform` expands to, plus opacity.
  Any motion added here must extend that list, not assume `transform` covers it.
- **An open `<dialog>` is a UA-level scroll container** (`overflow: auto` in both axes, for `show()` as well as `showModal()`; author sheets set none of it).
  A slide-over window parked off-viewport at `translate: 100%` sits inside that scrollable area, so the container would grow a horizontal scrollbar for the duration of every slide-in, indistinguishable from a page scrollbar because the dialog covers the viewport.
  `overflow-x: clip` on the container closes it while leaving the block axis free for the centered-modal container scroll.
  The same scrollbar would also steal 15px of container width, moving the inline-end resting position of a slide-over panel, so it would appear to settle short of the edge and then snap flush when the scrollbar went away.
- **A slide-over container must be `overflow: clip` in both axes, not just the inline one.**
  `overflow-x: clip` against the UA's `overflow-y: auto` computes to `hidden`, and a `hidden` box is still programmatically scrollable, so `showModal()`, which focuses the close button while the panel is still parked off-viewport at `translate: 100%`, makes the browser scroll the container by the panel's entire width to reveal it.
  That offset is clamped back toward zero on the main thread as the transition shrinks the overflow, always a step behind the compositor-driven transform, and the difference paints as the panel overshooting its resting place and drifting back.
  Clipping both axes removes the scroll port entirely; the window keeps its own `overflow-y: auto`, so nothing on the container needs to scroll.
  No DOM measurement sees this: `offsetLeft` ignores scroll, computed `translate` ignores it too, and `getBoundingClientRect()` nets the two against each other, so it is visible only in `scrollLeft` or in pixels.
- **No layout property may be gated on `[open]`.**
  The attribute drops the instant a close begins, but `display` is held for the transition's length by allow-discrete, so anything keyed to `[open]` collapses under a modal that is still fully opaque on screen.
  Gating the centered frame's `p-4` would take a `1fr auto 3fr` grid's centered row 8px up at the start of every close, and gating the container's `overflow-y: auto` would drop a scrolled modal back to its top in the same frame.
  Both apply unconditionally; they are inert while the dialog is `display: none`.
  Only paint-level state (the dim) and `display` itself belong on `[open]`.
- **A settled non-`none` individual transform is a containing block for `position: fixed` descendants.**
  The open states end at `scale-none` / `translate-none`, not at `scale-100` / `translate-x-0`, which are real transform values rather than the absence of one and would make the window the containing block of any fixed-positioned dropdown inside it, where the window's own `overflow-y: auto` then clips it.
  `scale-none` / `translate-none` interpolate from the closed `scale-95` / `-translate-x-full` frames and settle with no containing block, so the modal can be a [fixed positioning context](#the-modal-is-a-fixed-positioning-context) for what it contains.

### The client-side close reports itself with a `dismissed` event

Filament's modal dispatches `modal-closed` and the action layer listens for it to unmount the mounted action.
Sylament's controller listens to the dialog's native `close` event and dispatches `sylament--support--modal:dismissed` (bubbling) **only when the `open` Stimulus value is still `true`**: a server-driven close flips that value to `false` before calling `close()`, so a still-true value is precisely the signal that the close originated in the browser and the server does not know yet.
Actions' [`ActionModal`](../actions/action.md) binds that event to `unmountAction` on the host live component, which is the same server sync Filament gets from `modal-closed`; support itself only emits, and never assumes a listener.

### The chrome defaults are literal constants, not `ModalComponent` statics

Filament sources four modal defaults from static properties on `ModalComponent` (`$hasCloseButton`, `$isClosedByClickingAway`, `$isClosedByEscaping`, `$isAutofocused`), so an app can change them kit-wide in a service provider.
Sylament resolves to the *same values* (close button on, click-away on, ESC on, autofocus on) but as literals: `{% props %}` defaults in the template and `?? true` fallbacks in `CanOpenModal`'s getters.
The observable per-modal behaviour matches Filament's out-of-the-box behaviour; what is missing is only the global override seam, tracked as its own [todo row](#modalcomponents-global-defaults-have-no-sylament-seam).

Sylament's header-rendering condition (`hasIcon or hasHeading or hasDescription`) is a superset of Filament's (`$heading || $header`): an icon-only or description-only modal renders a header, and therefore a close button, in Sylament where Filament renders neither.

### Alignment strings pass through verbatim where Filament drops them

Filament's modal accepts `Alignment|string|null` and renders a class only for the enum cases, silently dropping any other string.
Sylament resolves `alignment` / `footerActionsAlignment` as `value ?? raw` (the `width` idiom) and then branches: a known value (`start`, `left`, `center`, `end`, `right`) emits `sy-align-<value>`, and **any other string is emitted verbatim as a class**, so a consumer can point the alignment slot at its own CSS.
This matches [`HasAlignment`](has-alignment.md)'s string-as-is contract and the `width` escape hatch in this template: where Filament renders nothing, Sylament renders the string the caller asked for.

The prop defaults differ on paper and not in effect: Filament's blade defaults both props to `Alignment::Start`, while Sylament's `{% props %}` default `footerActionsAlignment` to `null` (and `alignment` to `'start'`).
Blade's default applies only when the attribute is *omitted*, and the action modal always passes an explicit value; `getModalFooterActionsAlignment()` returns `null` for a non-confirmation action in both codebases, so both render a `<footer>` with no alignment class there, and both leave the footer-actions layout rules (all scoped under an alignment class) inactive.

Filament's own start-plus-icon indent rules are dead selectors upstream: [`modal.css`](../../../vendor/filament/support/resources/css/components/modal.css) keys `fi-align-start` off the `.fi-modal` root, while [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) stamps `fi-align-*` only on the window, so Filament renders `px-6` everywhere.
Sylament keys the rules off the window, making the indent live, following the source's intent over its rendered output.
The indent is `4.25rem` where Filament's is `5.25rem`, because the icon has no background wrapper ([below](#the-header-icon-drops-filaments-background-wrapper)) and is narrower by that wrapper's padding.

### `returnsFocus` stays `true` where Filament's action modal hardcodes `false`

Filament's modal takes `restoresFocus`, and [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) passes `:restores-focus="false"`, because a Livewire morph can replace the trigger element between open and close, leaving focus to be restored to a detached node.
Sylament's controller captures the opener element at show time and calls `focus()` on it from the dialog's `close` event, a silent no-op if that element is gone by then; actions' [`ActionModal`](../actions/action.md) leaves `returnsFocus` at its `true` default, and keyboard users land back on the trigger they came from.
Filament also skips restoration for a click-through modal (`shouldRestoreFocus: restoresFocus && ! isClickThrough`); the controller restores for both.

### The header icon drops Filament's background wrapper

Filament wraps the header icon in a `fi-modal-icon-ctn > div.fi-modal-icon-bg` whose background colour comes from `ComponentAttributeBag->color(IconComponent::class, $iconColor)`.
Sylament renders `sy-modal-icon-ctn` directly around the icon SVG and applies the colour as `sy-color sy-color-<name>` classes on the icon itself (via `generateIconHtml`), with no coloured background element.
The colour still resolves through the [`ModalComponent\IconComponent`](../../../packages/support/src/Colors/Components/ModalComponent/IconComponent.php) policy Filament names, so a palette handed over inline as an array yields `sy-color` plus the `--sy-color-*` style variables; only an empty `iconColor`, or gray by name, suppresses both, because the policy implements `HasDefaultGrayColor`.

### The default width is `md`, not `sm`

Filament defaults `width` to `'sm'`; Sylament defaults it to `'md'`.
The width is emitted verbatim as a `sy-modal-<value>` class rather than resolved through the `Width` enum's `fi-width-*` / `fi-width-screen` logic.

[`Sylament\Support\Enums\Width`](../../../packages/support/src/Enums/Width.php) exists for panels' page and panel width surfaces, but `width()` takes a plain string and the class is `sy-modal-<value>`.
Retyping it means narrowing the property's type, mapping each case to a modal class and re-deriving the `sy-modal-screen` arm, which is a modal-sized change of its own rather than a rider on a page change.
The same deferral, for the same reason, is recorded on [`Table::filtersFormWidth()`](../tables/table.md#surface).

### The open state is a Stimulus value, not only an event

Filament opens a modal purely by event: `x-on:open-modal.window="… open()"`.
That cannot work for a modal whose element is created by the very response that asks it to open, which is exactly what a mounted action does: UX LiveComponent's `processRerender()` morphs the new DOM and dispatches the queued browser events **synchronously in one task**, while Stimulus binds `data-action` listeners from a `MutationObserver` callback, a microtask that runs afterwards.
The freshly inserted `<dialog>` is not listening yet, so an event-only modal renders closed on the first click and opens only on a second, by when the element exists and is bound.

So the authoritative signal is state, not a notification: `Modal` takes an `open` prop rendered as a `data-sylament--support--modal-open-value` attribute, and the controller's `openValueChanged()` calls `showModal()` / `close()`.
Stimulus invokes that callback on connect and on every later attribute change, whenever those happen, so no ordering can defeat it, and it is the honest encoding, since "this modal is open" *is* server state (`mountedActions` being non-empty).
The `show()` path guards on `element.open`, so the value and event paths are idempotent and can coexist.
The window events are kept for imperative open and close from code that has no server state to render.
Nothing reverses this; the event-only form is unsound for server-rendered modals.

### The `open` attribute is rendered too, and the controller re-normalizes a morphed-in dialog

The Stimulus value alone is not enough once a *stack* of modals persists across live re-renders.
`open` is therefore rendered twice from one prop: as `data-sylament--support--modal-open-value` (**intent**, what the server wants) and as the bare native `open` attribute (**state**, what the DOM is).
The reason is Idiomorph, which diffs attributes: with only the value rendered, a re-render of a page containing an already-open dialog would morph `open` *off* that element, because it is not in the incoming markup, and close it behind the controller's back.
Rendering it makes the incoming markup agree with reality.

That fix creates its own problem, which the controller then absorbs.
A dialog whose `open` attribute arrived **with the markup** is open but **not modal**: no top layer, no backdrop, no focus trap, no `cancel` event, and `closeOnClickAway` is dead because there is no backdrop for its target check to hit.
Only `showModal()` produces the modal state, and the parser never calls it.
So `#normalizeModality()` runs on connect and, for the top frame, at value time: if the dialog is open, not click-through, and not already `:modal`, it calls `removeAttribute("open")` and then `showModal()`.
Removing the attribute rather than calling `close()` is deliberate: the spec's close steps fire a `close` event, which the dismissal listener would read as a user close and round-trip an unmount, while attribute removal runs the same cleanup silently, and nothing ever paints a closed frame in between.

Three follow-on cases are handled in the controller; none is observable from a Twig render test, and no browser pass has exercised them:

- **A morph can re-add `open` to a dialog the user just dismissed.**
  After a client dismissal the attribute is gone but the open *value* is still `"true"` until the round-trip completes; a re-render landing in that window re-adds `open` to the same element, with no `connect()` and no `openValueChanged()`, leaving a visible, non-modal, undismissable overlay.
  A `MutationObserver` on the `open` attribute calls `#normalizeModality()`, which is the only hook that fires for that.
  It converges rather than loops: the remove and `showModal()` pair queues one more callback, and that one finds the dialog `:modal` and returns.
- **A server-driven close on a *persisting* element loses its `close` event.**
  `openValueChanged(false)` would call `element.close()`, but the morph already removed the mirrored attribute, so `element.open` is false and `close()` bails per spec, with no event and no teardown.
  The callback therefore runs the teardown itself.
  It tears down **only** the Escape listener: a hidden frame is still rendered and may re-open when the frame above it unmounts, so `#opener` is kept as the eventual restore target and focus is left where the newly opened child put it.
  The gone-for-good case is `disconnect()`, which does restore focus.
- **`#captureOpener()` runs before `#show()`'s early return** and never overwrites an existing opener.
  With the attribute always rendered, a freshly mounted dialog is already open when the controller connects, so an opener capture placed after the early return would never run and `restoreFocus()` would silently break for every modal in the app.
  Never overwriting is what lets a frame that was hidden under a child and later re-opened still point at the trigger it was first opened from.

### Stacking is the native top layer, and the overlaid scrim is server-stamped

Filament maintains its stack in JS: `isTopmost()` scans `document.querySelectorAll` to decide which modal ESC should close, and a `:has(~ .fi-modal-open)` sibling rule suppresses the dim of any modal that has an open one after it, both legal because its modals are plain `<div>`s in DOM order.

Sylament inherits the first for free and cannot use the second.
`showModal()` promotes a dialog into the **top layer**, and the browser fires `cancel` on the topmost modal dialog only, so ESC already targets the right frame with no stack bookkeeping; see [ESC closes through the native `cancel` event](#esc-closes-through-the-native-cancel-event).
But top-layer paint order is `showModal()` **call** order, which has no relationship to DOM order, so no sibling selector can answer "is there a dialog above me".
The port answers it on the server instead: an `overlaid` prop stamps `sy-modal-overlaid`, whose rule zeroes that dialog's own background so only the topmost frame paints a scrim (stacked dims would otherwise compound).
The hand-off animates for free, because `background-color` is already in the container's transition list.
Actions' `Modals` renderer is what computes the flag; see [modals.md](../actions/modals.md#the-parent-overlay-flag-is-server-stamped-not-a-sibling-css-selector).

### Event names are fixed, and there is no quiet-close

Filament lets each modal name its `openEventName` / `closeEventName` / `closeQuietlyEventName` and dispatches them with the modal `id` in the detail.
Sylament hard-codes `sylament-open-modal` / `sylament-close-modal` (id-matched in the controller) and has no quiet-close variant: the quiet path exists in Filament to suppress a Livewire re-render round-trip that the native dialog does not incur.

### The modal is a fixed positioning context

Filament stamps `fi-absolute-positioning-context` on its modal root, so a select dropdown opened inside a modal positions with Floating UI's `absolute` strategy and is therefore laid out inside the window, where a sticky-pane modal's `overflow-y: auto` clips it.
Ours stamps `sy-fixed-positioning-context` on the `<dialog>` instead: forms' select engine reads the pair of marker classes off its button's ancestors and picks the `fixed` strategy under this one, so the dropdown is positioned against the viewport and escapes both the window's overflow and the dialog's.
It stays put because the engine installs a capture-phase `scroll` listener while its panel is open and repositions on every scroll, the window's included.
The class name is the prefix rename of Filament's marker pair, and nothing in support reads it; the markers carry no rules of their own in Filament either.
Tables' content container carries the same one when it is built, which is [`content()`](../tables/table.md#surface)'s list rather than a column: `SelectColumn` ships as a **native** `<select>`, which the browser positions itself and which reads no marker class at all ([select-column.md](../tables/select-column.md)), so the first table surface that needs the marker is the one that replaces the `<table>` with a caller-supplied list.
This works only because the window's open state settles at `scale: none` / `translate: none`; see the transform trap under [transitions](#transitions-are-css-native-not-js-driven).
