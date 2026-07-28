# Modal chrome — design spec

Roadmap [epic 4 (pulled forward)](../../progress/roadmap.md): the deferred modal
surface, wired end to end from `CanOpenModal` through support `Modal`
props/CSS/Stimulus. Reference: Filament v5.7.1 (`vendor/filament/`), extracted
verbatim from `support/resources/views/components/modal/index.blade.php`,
`support/resources/css/components/modal.css`, `support/resources/js/components/modal.js`,
`actions/src/Concerns/CanOpenModal.php`, and `actions/resources/views/action-modal.blade.php`.

## Decisions (approved 2026-07-28)

1. **Dialog-as-container restructure.** The `<dialog>` becomes the
   full-viewport overlay + centering container (it paints the dim itself;
   `::backdrop` is neutralized); a new inner `div.sy-modal-window` is the
   visible box. Native wins retained (top layer, focus trap, `cancel`/ESC,
   inert page). This gives `extraModalOverlayAttributes` an honest render
   target, makes click-away detection exact (`event.target === dialog`), lets
   Filament's slide-over/alignment/sticky/click-through CSS port ~1:1, and
   sets up epic 5's stacked-modal overlay behavior.
2. **Full Filament defaults parity.** Close button defaults **on**; footer
   actions default **start-aligned** (submit leftmost), replacing the current
   row-reverse (end-style) footer. Confirmation modals auto-center and reverse
   to cancel-first. Two recorded divergences disappear.
3. **CSS-only transitions.** Filament's 300 ms Alpine transitions become pure
   CSS via `@starting-style` + `transition-behavior: allow-discrete`:
   fade+scale for centered modals, translate-x for slide-overs, fade for the
   dim. No JS; older browsers skip the animation.

## Scope

In: `slideOver()` + `slideOverPosition()`, `stickyModalHeader()` /
`stickyModalFooter()`, `modalAlignment()` + `modalFooterActionsAlignment()`
(with the center-reverse restored in `getModalFooterActions()`),
`modalCloseButton()` + the support header close button,
`closeModalByClickingAway()` / `closeModalByEscaping()`, `modalAutofocus()`,
`modalClickThrough()`, `HasExtraModalOverlayAttributes` /
`HasExtraModalWindowAttributes`, the dismissed→unmount server sync, and the
three decisions above.

Out (still deferred, unchanged): the `ModalComponent` global-defaults seam
(defaults become plain constants), a `Width` enum (widths stay strings), the
icon-alias resolution registry, `alert` (`role="alertdialog"`), `trigger`,
`teleport`, `visible`, the `header` replacement slot, `ariaLabelledby` prop,
configurable/quiet-close event names, scroll-lock (remains a recorded
divergence of the dialog re-architecture; click-through must not lock scroll
anyway, so behavior is at least self-consistent).

## 1 · PHP surface (`packages/actions`)

Extend `Sylament\Actions\Concerns\CanOpenModal` with Filament's exact names,
signatures, and defaults (all setters take `Closure`-able values, evaluated in
the getters):

| Setter | Property (default) | Getter → effective default |
| --- | --- | --- |
| `slideOver(bool\|Closure $condition = true)` | `bool\|Closure $isModalSlideOver = false` | `isModalSlideOver(): bool` → `false` |
| `slideOverPosition(SlideOverPosition\|Closure\|null $position = null)` | `SlideOverPosition\|Closure\|null = null` | `getModalSlideOverPosition(): ?SlideOverPosition` → `null` (template defaults to End) |
| `stickyModalHeader(bool\|Closure $condition = true)` | `bool\|Closure\|null = null` | `isModalHeaderSticky(): bool` → `?? $this->isModalSlideOver()` |
| `stickyModalFooter(bool\|Closure $condition = true)` | `bool\|Closure\|null = null` | `isModalFooterSticky(): bool` → `?? $this->isModalSlideOver()` |
| `modalAlignment(Alignment\|string\|Closure\|null $alignment = null)` | `Alignment\|string\|Closure\|null = null` | `getModalAlignment(): Alignment\|string` → explicit; else `Center` when `isConfirmationRequired()` or `getModalWidth()` ∈ `['xs','sm']`; else `Start` |
| `modalFooterActionsAlignment(Alignment\|string\|Closure\|null $alignment = null)` | `Alignment\|string\|Closure\|null = null` | `getModalFooterActionsAlignment(): Alignment\|string\|null` → explicit; else `Center` when confirmation required; else `null` |
| `modalCloseButton(bool\|Closure\|null $condition = true)` | `bool\|Closure\|null = null` | `hasModalCloseButton(): bool` → `?? true` |
| `closeModalByClickingAway(bool\|Closure\|null $condition = true)` | `bool\|Closure\|null = null` | `isModalClosedByClickingAway(): bool` → `?? true` |
| `closeModalByEscaping(bool\|Closure\|null $condition = true)` | `bool\|Closure\|null = null` | `isModalClosedByEscaping(): bool` → `?? true` |
| `modalAutofocus(bool\|Closure\|null $condition = true)` | `bool\|Closure\|null = null` | `isModalAutofocused(): bool` → `?? true` |
| `modalClickThrough(bool\|Closure\|null $condition = true)` | `bool\|Closure\|null = null` | `isModalClickThrough(): bool` → `false` |

The four `?? true` fallbacks are where Filament reads `ModalComponent::$…`
statics — the seam is deferred, so they are literal constants here (noted in
the progress docs so the seam's later arrival knows its insertion points).

`getModalFooterActions()` gains Filament's reversal **only in the composed
submit/extra/cancel branch** (the `modalFooterActions()` override branch
returns early un-reversed):

```php
if (in_array($this->getModalFooterActionsAlignment(), [Alignment::Center, 'center'])) {
    $actions = array_reverse($actions);
}
```

`getModalWidth()` is untouched: our unconditional `'md'` default is an
already-recorded divergence (Filament: `md` on confirmation, else `4xl`);
`getModalAlignment()`'s outcomes match Filament's for both defaults, so no
behavior knock-on.

Two new traits on `Action`, mirroring Filament's
`Filament\Actions\Concerns\HasExtraModal{Window,Overlay}Attributes`:
`extraModalWindowAttributes(array|Closure $attributes, bool $merge = false)` /
`getExtraModalWindowAttributes(): array` and the Overlay twin. Non-merge calls
replace the queued list with `[$attributes]`; merge calls append; the getter
folds all queued entries (each closure-evaluated) through support's
`ComponentAttributeBag` merge semantics. Filament's `escape: false` merge and
`get…AttributeBag()` accessors translate to whatever our bag's existing API
supports — follow the `extraAttributes` precedent already in the codebase.

New enum `Sylament\Support\Enums\SlideOverPosition: string { Start = 'start';
End = 'end'; }` (support, beside `Alignment`).

## 2 · Support `Modal` template

New DOM (`packages/support/templates/components/Modal/index.html.twig`):

```
<dialog class="sy sy-modal [sy-modal-slide-over] [sy-modal-slide-over-from-start|-from-end]
               [sy-modal-has-sticky-header] [sy-modal-has-sticky-footer]
               [sy-modal-click-through] [sy-modal-screen]"
        {caller attributes} {extraOverlayAttributes} {stimulus wiring, id, aria-*}>
  <div class="sy-modal-window sy-modal-<width> [sy-align-<value>]
              [sy-modal-window-has-close-btn] [sy-modal-window-has-icon]
              [sy-modal-window-has-content] [sy-modal-window-has-footer]"
       {extraWindowAttributes} [autofocus tabindex="-1"]>
    <header class="sy-modal-header [sy-vertical-align-center]">
      [IconButton close]  [icon-ctn]  [heading/description]
    </header>
    <div class="sy-modal-content">…</div>
    <footer class="sy-modal-footer [sy-align-<value>]">
      {footer slot} | <div class="sy-modal-footer-actions">{footerActions}</div>
    </footer>
  </div>
</dialog>
```

Caller `attributes` and `extraOverlayAttributes` both target the dialog
(Filament's separate root and overlay elements collapse into it — divergence
note); `extraWindowAttributes` target the window div. Both bags spread with
`attrs()` semantics: later scalars win, class/style tokens append.

New props (defaults = Filament blade defaults, adjusted only where decided):

| Prop | Default | Notes |
| --- | --- | --- |
| `slideOver` | `false` | root variant class |
| `slideOverPosition` | `'end'` | enum-or-string, `value ?? raw` like `width` |
| `stickyHeader` / `stickyFooter` | `false` | root variant classes; the slide-over auto-stick lives in the PHP getters, not here (Filament-exact) |
| `alignment` | `null` | enum-or-string like `width`: `value ?? raw`; a known value (`start`/`left`/`center`/`end`/`right`) emits `sy-align-<value>` on the window, any other string passes through verbatim as a custom class (the `width` escape-hatch idiom, consistent with `HasAlignment`'s string-as-is contract; Filament silently drops strings — minor deliberate delta, docs-noted). Drives `sy-vertical-align-center` on the header when icon ∧ heading ∧ ¬description ∧ alignment ∈ {start, left}. Same emission rule for `footerActionsAlignment` on the `<footer>` |
| `closeButton` | `true` | decision 2 |
| `closeByClickingAway` | `true` | forced `false` when `clickThrough` (Filament-exact) |
| `closeByEscaping` | `true` | |
| `autofocus` | `true` | |
| `clickThrough` | `false` | |
| `footerActions` | `[]` | iterable of renderables; the `footer` slot wins when filled (Filament-exact) |
| `footerActionsAlignment` | `null` | class on the `<footer>` element |
| `extraWindowAttributes` / `extraOverlayAttributes` | `{}` | hashes (our idiom for Filament's attribute bags) |

Existing props (`content`, `description`, `footer`, `heading`, `icon`,
`iconAlias`, `iconColor`, `id`, `open`, `returnsFocus`, `width`) are
unchanged; `sy-modal-<width>` moves from the dialog to the window div.

**Close button** — first in the header, composing support `IconButton`:
`color="gray"`, `icon="heroicons:x-mark"`, `iconSize="lg"`, label `'Close'`
(hardcoded English per the existing Cancel/Confirm precedent), class
`sy-modal-close-btn`, click → controller `dismiss`. Filament's icon alias
(`MODAL_CLOSE_BUTTON`) passes through the existing `iconAlias` convention —
match however support templates currently carry alias strings (registry
resolution stays deferred).

**Focus rules** (Filament's juggling, translated to the native focus
delegate):

- Close button `tabindex="-1"` when `closeByEscaping` (ESC exists, so the
  button leaves the tab order); natively tabbable otherwise (sole keyboard
  dismissal).
- The **window div** gets `autofocus` + `tabindex="-1"` when
  (`closeButton` ∧ ¬`closeByEscaping` ∧ heading present) — Filament's rule so
  the trap doesn't land on the close button — **or** when ¬`autofocus` — our
  translation of `x-trap.noautofocus`, since `showModal()` always focuses
  something; the neutral outline-free window is the "nothing". One attribute
  covers both. (Verify at implementation that the dialog focus delegate
  honors `autofocus` on a `tabindex="-1"` div; adjust to a controller-side
  `element.focus()` fallback if a browser doesn't.)

**Click-through** forces `closeByClickingAway = false` in the template and
renders no dim (the dialog gets a transparent-background variant class). No
`aria-modal` stamping needed: `show()` yields non-modal dialog semantics
natively (Filament stamps `aria-modal="false"` by hand).

## 3 · Stimulus controller (`modal_controller.ts`)

New values: `closeByEscaping` (default `true`), `closeByClickingAway`
(default `true`), `clickThrough` (default `false`). Existing `id`, `open`,
`returnsFocus` unchanged.

Behaviors:

- **Show path**: `#show()` branches on `clickThrough` — `show()` (non-modal)
  vs `showModal()`. `openValueChanged` logic otherwise unchanged.
- **ESC**: template wires `cancel->…#cancel`; the handler calls
  `preventDefault()` when `closeByEscaping` is off. The native `cancel` event
  fires only on the topmost modal dialog, so Filament's `isTopmost()` needs
  no port. (Known platform caveat, recorded as a note: some browsers may
  force-close on repeated ESC as a user-intent escape hatch.) For
  click-through (non-modal — no `cancel` event), the controller registers a
  document `keydown` Escape listener while open, gated on the same value.
- **Click-away**: `pointerdown->…#…` + `click->…#…` pair; close only when
  `closeByClickingAway` and **both** the pointerdown origin and the click
  target are the dialog element itself (the backdrop area in the container
  structure). The pointerdown-origin requirement reproduces Filament's
  text-selection-drag guard (their PR #18022) by construction: a drag that
  starts inside the window and releases outside targets the common ancestor
  on `click` but not on `pointerdown`.
- **Dismiss**: `dismiss()` action for the close button → `element.close()`.
- **Dismissed event (server sync)**: on the dialog `close` event, when
  `openValue` is still `true` (i.e. the close originated client-side — ESC,
  click-away, close button — not from a server morph flipping the value), the
  controller `dispatch`es a `dismissed` event
  (`sylament--support--modal:dismissed`, bubbling). Server-driven closes
  (`openValueChanged(false)`) must not emit it. Focus restore on `close`
  stays as is.

## 4 · CSS (`packages/support/assets/src/styles/components/modal.css`)

Rewritten to the container/window structure; Filament's rules port `fi-`→`sy-`
with these mappings:

- **Container** (`.sy-modal[open]`): full-viewport in the top layer
  (`width/height: 100%; max-width/max-height: none; margin: 0; padding: 0`
  overriding dialog UA styles), the dim as its own background
  (`bg-gray-950/50 dark:bg-gray-950/75`), `::backdrop { background: none }`,
  grid centering `grid-rows-[1fr_auto_1fr] sm:grid-rows-[1fr_auto_3fr]
  justify-items-center`, `p-4` + window `mx-auto rounded-xl` only when
  neither slide-over nor screen-width, a `sy-clickable` class on the dialog
  when click-away is active (`cursor-pointer`, Filament's `fi-clickable`),
  `overflow-y: auto` when neither sticky nor slide-over nor screen (Filament
  scrolls the window *container*; the dialog is that container now — exact
  parity). Width `'screen'` emits `sy-modal-screen` on **both** the root
  (gating padding/rounding, the shared `h-dvh` rule, and transition
  suppression) and the window (via `sy-modal-<width>`, styled `fixed
  inset-0`) — selectors disambiguate, mirroring Filament's dual
  `fi-width-screen` placement.
- **Window** (`.sy-modal-window`): current dialog visuals move here
  (`bg-white shadow-xl ring-1 ring-gray-950/5 dark:…`, `outline-none`,
  `row-start-2`, `flex flex-col`, `w-full`, `pointer-events-auto`,
  `cursor-default`), width ladder `sy-modal-3xs … sy-modal-7xl` (+ existing
  six) as `max-w-*`, `sy-modal-screen` → `fixed inset-0` + the shared
  slide-over/screen `h-dvh` + content `flex-1` rule.
- **Slide-over**: window `h-dvh overflow-y-auto`, `ms-auto` (end) /
  `me-auto` (start), no rounding/container-padding, header `gap-x-3 pt-5`,
  footer `mt-auto`, close button `end-6 top-5` (vs `end-4 top-4` default).
- **Sticky**: header `sticky top-0 z-10 border-b bg-white pb-6 dark:…` +
  `rounded-t-xl` (non-slide-over); footer `sticky bottom-0 border-t bg-white
  py-5` + `rounded-b-xl`; sticky variants move the scroll to the window:
  `max-h-[calc(100dvh-2rem)] overflow-y-auto` (non-slide-over, non-screen).
- **Alignment** (first consumer of `sy-align-*`, scoped under `.sy-modal` per
  the styling contract): start/left header `gap-x-5`; center header
  `flex-col text-center` + icon-ctn `mb-5 flex items-center justify-center`;
  `sy-vertical-align-center` header `items-center`; start+icon content/footer
  indent — Filament's `ps-[5.25rem]` recomputed for our header metrics (no
  icon-bg wrapper; px-6 + our lg icon width + gap-x-5), applied under the
  same guards (non-sticky-header, window-has-icon, footer not center-aligned).
- **Footer actions**: footer `w-full`; `.sy-modal-footer-actions` base
  `gap-3`; start/left `flex flex-wrap items-center`; center `px-6` on footer
  + `flex flex-col-reverse` + the container-query `@sm` grid enhancement
  (both `@supports` branches, Filament-verbatim); end/right `flex
  flex-row-reverse flex-wrap items-center`. The current `flex-row-reverse`
  base on `.sy-modal-footer` is removed (decision 2); existing padding rules
  redistribute per Filament (`pb-6`, the no-content `mt-6` fallbacks, sticky
  `py-5`).
- **Click-through**: `.sy-modal-click-through { pointer-events: none;
  background: transparent }` with the window's `pointer-events-auto`
  re-enabling interaction — plus, since `show()` leaves the top layer,
  explicit `position: fixed; inset: 0` and a z-index (reuse the project's
  existing overlay z convention — check the dropdown's — so the panel floats
  above page content).
- **Transitions** (decision 3): 300 ms on open and close via
  `@starting-style` + `transition-behavior: allow-discrete` (Tailwind
  `starting:` variant / plain CSS where `@apply` can't express it): centered
  window `scale-95 opacity-0 → scale-100 opacity-100`, slide-over window
  `translate-x-full` (end; mirrored for start, `rtl:` flipped), container dim
  `opacity`; screen-width modals skip the scale/translate movement
  (Filament-exact — they gate those transition classes on width ≠ screen).
  Close animation is honest only for client-side closes — an unmount morph
  removes the element outright; accepted, noted.

Dark-mode variants inline, `components` cascade layer, semantic classes only,
`--sy-*` tokens for any color — per `docs/styling.md`. `pnpm build` dist
committed in the same change (CI gate).

## 5 · Actions wiring

**`ActionModal/index.html.twig`** grows the full pass-through (Filament's
binding list, minus deferred `alert`/wire-keys):
`:slideOver="action.isModalSlideOver"`,
`:slideOverPosition="action.getModalSlideOverPosition"`,
`:stickyHeader="action.isModalHeaderSticky"`,
`:stickyFooter="action.isModalFooterSticky"`,
`:alignment="action.getModalAlignment"`,
`:closeButton="action.hasModalCloseButton"`,
`:closeByClickingAway="action.isModalClosedByClickingAway"`,
`:closeByEscaping="action.isModalClosedByEscaping"`,
`:autofocus="action.isModalAutofocused"`,
`:clickThrough="action.isModalClickThrough"`,
`:extraWindowAttributes="action.getExtraModalWindowAttributes"`,
`:extraOverlayAttributes="action.getExtraModalOverlayAttributes"`,
`:footerActions="action.getVisibleModalFooterActions"` +
`:footerActionsAlignment="action.getModalFooterActionsAlignment"` — replacing
the current footer block+loop. `returnsFocus` stays `true` (our restore
works; Filament's hardcoded `false` is a Livewire artifact — docs note).

**Dismissed → unmount**: `ActionModal` binds the controller's `dismissed`
event to the live component's `unmountAction` (the same live-action mechanism
the Cancel footer action uses), so ESC/click-away/close-button keep server
`mountedActions` in sync and the next morph doesn't resurrect the modal.
Filament parity: their `x-on:modal-closed` handler.

**Modal template `footerActions` rendering**: entries are `Action` view
components → `renderComponent()` in the loop (support renders any
`ViewComponent`; follow the existing child-rendering precedent for any
Markup-vs-component handling).

## 6 · Tests

- **Unit (`actions/tests/Unit/Concerns/`)**: extend `CanOpenModalTest` —
  round-trips + closure evaluation for every new member; defaults matrix
  (sticky ← slideOver fallback; alignment: explicit / confirmation / xs–sm
  width / Start; footer-actions alignment: confirmation → Center);
  center-reverse in `getModalFooterActions()` incl. the `'center'` string and
  the override branch *not* reversing. New `HasExtraModalWindowAttributesTest`
  + `HasExtraModalOverlayAttributesTest` (file-per-SUT): replace vs merge,
  closure entries, bag fold.
- **Integration (`actions/tests/Integration/Components/`)**:
  `ModalRenderTest` — every new prop's markup: container/window split
  (assertions retargeted), variant classes, close-button presence + tabindex
  juggling + window `autofocus` cases, footer-actions wrapper + alignment
  classes, both extra-attribute targets, controller values +
  `cancel`/`pointerdown`/`click`/`dismiss` action wiring, click-through
  forcing click-away off. `ActionModalRenderTest` — action→prop flow:
  `slideOver()` ⇒ slide-over class; `requiresConfirmation()` ⇒ centered +
  cancel-first DOM order (`mb_strpos` idiom); default ⇒ close button present,
  submit-first order; `dismissed`→`unmountAction` binding present.
  `ModalsRenderTest` — untouched unless assertions graze moved classes.
- **TS**: `pnpm test:types` + Biome; no JS test rig exists — controller
  behavior is asserted through rendered wiring, not simulated events.

## 7 · Docs trail (same commit as the code)

- `docs/progress/support/modal.md`: flip the in-scope surface rows (close
  button; slide-over/sticky/alignment/click-through/extra-bags subset of the
  breadth row — `alert`/`trigger`/`teleport`/`visible`/`header` slot stay
  open); rewrite the headline `<dialog>` divergence for the container/window
  structure; add translation notes (cancel-event ESC, pointerdown-pair
  click-away, `show()` click-through, `@starting-style` transitions,
  `dismissed` event); record the default flips (close button on, start
  footer) and the collapsed root/overlay attribute targets.
- `docs/progress/actions/action.md`: rewrite the `CanOpenModal` detail
  (chrome group lands; wizard suppression + deprecated aliases remain out),
  update the modal-rendering row detail + blocked-by, drop
  `HasExtraModal*Attributes` from the unported-surface divergence.
- README rollups (`actions/README.md`, `support/README.md`, top-level) per
  the same-commit rule; `roadmap.md`: flip epic 4 → done, advance Now to 5.
- `renames.md`: nothing — every Filament name is preserved.

## 8 · Delivery

Three lanes (worktree-parallelizable; detail in the implementation plan):

1. **support** — `SlideOverPosition` enum, Modal template restructure,
   controller, CSS, transitions.
2. **actions PHP** — `CanOpenModal` additions, two traits, Unit tests.
3. **integration** (after 1+2) — ActionModal pass-through, dismissed→unmount,
   Integration tests, docs trail, dist rebuild.

Gates: `composer test` (Pest + Rector + PHPStan max), `pnpm build` with dist
committed, `pnpm test:types`, `pnpm test:lint`, `composer format`.

## Verify-at-implementation list

Small facts to confirm against vendor/installed code while building (none
block the design): the dialog focus-delegate honoring `autofocus` on a
`tabindex="-1"` div across engines; the exact icon-alias string convention
(`SupportIconAlias` equivalent) used by existing support templates; the
`ComponentAttributeBag` API surface for the trait fold (`merge` semantics vs
Filament's `escape: false`); the recomputed start+icon content indent from our
real header metrics; the z-index convention for the non-top-layer
click-through panel (dropdown precedent); Tailwind `starting:` variant
availability in the installed version (else plain CSS in the component file).
