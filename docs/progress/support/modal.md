# Modal (support)

`packages/support/templates/components/Modal/index.html.twig` — the shared
`<twig:SylamentSupport:Modal>` primitive, a native-`<dialog>` re-architecture of
Filament's Alpine-driven `<x-filament::modal>`. It is template-only: there is no
Sylament PHP `ModalComponent` class. The port covers the confirm-modal chrome the
`actions` layer drives (heading/description/icon header, body + footer slots, a
semantic width, and the `sylament--support--modal` Stimulus open/close/restore
wiring); the broad remainder of Filament's modal surface is deliberately deferred.

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
| Header — heading / description / icon / iconColor + `aria-*` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) heading block | done | — | [diverges](#the-header-icon-drops-filaments-background-wrapper) |
| Content slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `$slot` | done | — | — |
| Footer slot | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `$footer` | done | — | [diverges](#footer-actions-header-slot-and-alignment-are-not-ported) |
| Semantic width class | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `width` | done | — | [diverges](#the-default-width-is-md-not-sm) |
| `returnsFocus` (↔ `focusTrapReturnsFocus`) | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `focusTrapReturnsFocus` | done | — | — |
| Close button + `ModalComponent` static config | [`ModalComponent.php`](../../../vendor/filament/support/src/View/Components/ModalComponent.php) | todo | — | [detail](#details) |
| Slide-over, sticky header/footer, alignment, alert, clickThrough, trigger, teleport, visible, extra attribute bags | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) props | todo | — | [detail](#details) |
| Configurable / quiet-close event names | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `openEventName`/`closeEventName`/`closeQuietlyEventName` | skipped | — | [diverges](#event-names-are-fixed-and-there-is-no-quiet-close) |
| `wire:submit` form wrapper / `wire:key` | [`index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) `wireSubmitHandler` | skipped | — | [diverges](#livewire-form-in-modal-wiring-is-not-ported) |

The done rows are exercised by
[`actions/tests/Integration/Components/ModalRenderTest.php`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php)
— a support-owned primitive render-tested from the `actions` suite (the first
consumer that boots a Twig-component kernel), asserting the closed `<dialog>`, the
`sylament--support--modal` controller/action wiring, conditional header/content/
footer, the `sy-modal-<width>` class, `aria-labelledby`/`aria-describedby`, and the
gray-icon color suppression.

## Details

Write-ups for the unfinished rows only.

### Close button + `ModalComponent` static config

Filament renders a `fi-modal-close-btn` icon-button in the header (gated on
`ModalComponent::$hasCloseButton`, with tab-order/autofocus juggling tied to
`closeByEscaping`), and ships a `ModalComponent` class whose four static
properties + configurators (`autofocus()`, `closeButton()`, `closedByClickingAway()`,
`closedByEscaping()`) let an app change the modal defaults globally. Neither is
ported: the Sylament modal renders no close button, and there is no PHP config
class — the native `<dialog>` relies on ESC and the consumer-supplied footer
buttons (the confirm-modal supplies its own Cancel button) to dismiss. Next step,
when a non-confirm consumer needs it: add the close button to the header and, if a
global default toggle is still wanted, a Sylament config seam for it.

### Deferred Filament modal surface (breadth-fill)

The full Filament view carries a large prop surface the port omits: `slideOver` /
`slideOverPosition`, `stickyHeader` / `stickyFooter`, `alignment`, `alert`
(`role="alertdialog"`), `clickThrough`, `trigger`, `teleport`, `visible`, the
`header` slot, `footerActions` / `footerActionsAlignment`, the explicit
`ariaLabelledby` prop, and `extraModalWindowAttributeBag` /
`extraModalOverlayAttributeBag`. These are Milestone-2 breadth-fill, not rejected —
each returns with the first consumer that needs it. Until then the template is the
confirm-modal chrome subset only.

## Divergences

Deliberate deviations from Filament — permanent unless a row above flips.

### The modal is re-architected onto native `<dialog>`

Filament's modal is a stack of `<div>`s driven by an Alpine `filamentModal`
object that hand-implements focus-trap (`@alpinejs/focus` `x-trap`), ESC
(topmost-only), click-away, refcounted body scroll-lock, enter/leave transitions,
and a stacking model, plus a separate `fi-modal-close-overlay` element and an
`x-teleport` template. Sylament renders a single native `<dialog>` and lets the
platform supply focus-trap, ESC, top-layer stacking, and `::backdrop`; the
`sylament--support--modal`
[Stimulus controller](../../../packages/support/assets/src/controllers/modal_controller.ts)
only bridges id-matched `sylament-open-modal` / `sylament-close-modal` window
events to `showModal()` / `close()` and restores opener focus on close. This drops
the overlay div, the JS transitions, the scroll-lock, click-away close, and the
`clickThrough` mode along with it. This is more than the systematic
Alpine→Stimulus / Blade→Twig translations — it is a structural rewrite recorded
here, the headline divergence of the actions confirm-modal epic.

_See also: the [rename table](../renames.md) for the Alpine→Stimulus and
Blade→Twig (`view()`→`template()`) translations this builds on._

### The header icon drops Filament's background wrapper

Filament wraps the header icon in a `fi-modal-icon-ctn > div.fi-modal-icon-bg`
whose background color comes from `ComponentAttributeBag->color(IconComponent::class, $iconColor)`.
Sylament renders `sy-modal-icon-ctn` directly around the icon SVG and applies the
color as `sy-color sy-color-<name>` classes on the icon itself (via
`generateIconHtml`), with no colored background element. A non-`gray` / non-empty
`iconColor` yields the color classes; gray or an iterable/empty value suppresses
them.

### Footer actions, header slot, and alignment are not ported

Filament's footer accepts either a `footer` slot **or** a `footerActions`
array/slot rendered inside `fi-modal-footer-actions` and aligned by
`footerActionsAlignment`; its header can be replaced wholesale by a `header` slot.
Sylament exposes a single `footer` block slot and a fixed heading/description/icon
header with no alignment control — the consumer composes its own footer buttons
(as the confirm-modal does). Reversible when a consumer needs the action-list or
custom-header forms.

### The default width is `md`, not `sm`

Filament defaults `width` to `'sm'`; Sylament defaults it to `'md'`. The width is
emitted verbatim as a `sy-modal-<value>` class rather than resolved through the
`Width` enum's `fi-width-*` / `fi-width-screen` logic.

### Event names are fixed, and there is no quiet-close

Filament lets each modal name its `openEventName` / `closeEventName` /
`closeQuietlyEventName` and dispatches them with the modal `id` in the detail.
Sylament hard-codes `sylament-open-modal` / `sylament-close-modal` (id-matched in
the controller) and has no quiet-close variant — the quiet path exists in Filament
to suppress a Livewire re-render round-trip that the native dialog does not incur.

### Livewire form-in-modal wiring is not ported

Filament swaps the modal window element to a `<form wire:submit.prevent=…>` when a
`wire:submit.prevent` attribute is present, and stamps `wire:key` on each region.
Sylament renders a plain `<dialog>` with no form wrapper or `wire:*` keys —
form-in-modal is deferred with the rest of the schemas/form-in-modal breadth, and
the confirm-modal drives its buttons through `live_action(...)` instead.
