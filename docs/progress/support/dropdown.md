---
description: The shared `<twig:SylamentSupport:Dropdown>` positioned-panel primitive and its `sylament--support--dropdown` Stimulus controller over Floating UI.
ports:
  - vendor/filament/support/resources/css/components/dropdown/header.css
  - vendor/filament/support/resources/css/components/dropdown/index.css
  - vendor/filament/support/resources/css/components/dropdown/list/index.css
  - vendor/filament/support/resources/js/components/dropdown.js
  - vendor/filament/support/resources/views/components/dropdown/header.blade.php
  - vendor/filament/support/resources/views/components/dropdown/index.blade.php
  - vendor/filament/support/resources/views/components/dropdown/list/index.blade.php
owns:
  - packages/support/assets/src/controllers/dropdown_controller.ts
  - packages/support/assets/src/styles/components/dropdown.css
  - packages/support/templates/components/Dropdown/
---
# Dropdown (support)

[`Dropdown/index.html.twig`](../../../packages/support/templates/components/Dropdown/index.html.twig) is the shared `<twig:SylamentSupport:Dropdown>` primitive, the port of Filament's Alpine and Floating UI driven `<x-filament::dropdown>`.
It is template-only: there is no Sylament PHP class behind it, mirroring Filament, whose Blade view has no static-config class either.
`actions`' `ActionGroup` composes it, and its panel carries divider-separated `.sy-dropdown-list` sections and nests one level down as a submenu.
The rows inside those sections are `<twig:SylamentSupport:DropdownItem>`, tracked in [dropdown-item.md](dropdown-item.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Root/trigger/panel shell + `placement`/`offset`/`flip`/`width`/`maxHeight` props](../../../packages/support/templates/components/Dropdown/index.html.twig) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) props, `$trigger`, `$slot` | done | — | [diverges](#the-root-is-a-positioned-shrink-to-fit-box-where-filaments-carries-no-rule-at-all) · [width](../actions/action-group.md#dropdownwidth-takes-a-css-length-not-a-width-enum-or-a-class) |
| [Positioning controller: toggle, floating placement, `aria-haspopup`/`aria-expanded`, Escape](../../../packages/support/assets/src/controllers/dropdown_controller.ts) | [`dropdown.js`](../../../vendor/filament/support/resources/js/components/dropdown.js) + `x-float` | done | — | [diverges](#a-prevented-escape-does-not-close-the-panel) |
| [`teleport` prop (escape a clipping ancestor)](../../../packages/support/assets/src/controllers/dropdown_controller.ts) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `teleport` prop | done | — | [semantics](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase) · [semantics](#the-inline-position-fixed-write-precedes-the-compute-and-the-strategy-option-does-not-substitute-for-it) |
| Auto-placement when no explicit placement is given | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `placement` default `null` | todo | — | [detail](#the-placement-is-always-explicit-so-there-is-no-automatic-mode) |
| Size middleware (`size` / `availableHeight` / `availableWidth` / `sizePadding`) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `size`, `sizePadding`, `availableHeight`, `availableWidth` | todo | — | [detail](#maxheight-is-a-static-value-and-no-middleware-fits-the-panel-to-the-viewport) |
| [Conditional panel scrolling (`fi-scrollable`)](../../../packages/support/assets/src/styles/components/dropdown.css) | [`dropdown/index.css`](../../../vendor/filament/support/resources/css/components/dropdown/index.css) `fi-scrollable`, gated on `$maxHeight` | done | — | [diverges](#the-panel-scrolls-only-when-a-max-height-is-set-and-the-gate-is-a-css-variable) |
| [`sy-dropdown-list` sections and their dividers](../../../packages/support/assets/src/styles/components/dropdown.css) | [`dropdown/list/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/index.blade.php) `fi-dropdown-list` | done | — | [diverges](#submenus-are-consumer-composition-not-a-shell-feature) |
| Submenus (nested dropdowns for nested action groups) | — (no Filament dropdown equivalent) | done | — | [diverges](#submenus-are-consumer-composition-not-a-shell-feature) |
| [Open state survives a live re-render morph](../../../packages/support/assets/src/controllers/dropdown_controller.ts) | — (Alpine's `x-data` state outlives a Livewire morph by construction) | done | — | [diverges](#the-open-state-is-a-controller-field-re-asserted-through-a-mutationobserver) |
| `aria-controls` and a generated panel `id` | [`dropdown.js`](../../../vendor/filament/support/resources/js/components/dropdown.js) `setUpAria()` / `syncAria()` | todo | — | — |
| Focus on open (`data-dropdown-autofocus`) | [`dropdown.js`](../../../vendor/filament/support/resources/js/components/dropdown.js) `syncAria()` | todo | — | — |
| Staged Escape (`data-dropdown-escape`, cancelable `dropdown-escape` event) | [`dropdown.js`](../../../vendor/filament/support/resources/js/components/dropdown.js) `handleEscape()` | todo | — | [diverges](#a-prevented-escape-does-not-close-the-panel) |
| Close on SPA navigation | [`dropdown.js`](../../../vendor/filament/support/resources/js/components/dropdown.js) `livewire:navigate` listener | todo | — | — |
| Dropdown header | [`dropdown/header.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/header.blade.php) + `header.css` | todo | — | — |
| `shift` prop | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `shift` prop, default `false` | todo | — | [detail](#the-controller-always-shifts-where-filaments-shift-is-opt-in) |
| Panel enter/leave fade | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `x-transition:enter-start` / `x-transition:leave-end` + `fi-opacity-0` | todo | — | — |

## Details

### The placement is always explicit, so there is no automatic mode

Filament's generic dropdown view defaults `placement` to `null`; omitting the `.placement.<value>` modifier lets `x-float`'s Floating UI plumbing choose a placement and re-choose it on scroll or resize collision.
Sylament's `placement` prop defaults to `'bottom-start'` and is always rendered as an explicit Stimulus value, so there is no `null` or automatic mode.
That matches Filament's own primary consumer: `ActionGroup` writes its own dropdown markup rather than composing `<x-filament::dropdown>`, and defaults its placement the same way ([`x-float.placement.<?= $this->getDropdownPlacement() ?? 'bottom-start' ?>`](../../../vendor/filament/actions/src/ActionGroup.php)), so no shipped Filament dropdown consumer exercises the automatic mode.

### `maxHeight` is a static value, and no middleware fits the panel to the viewport

Filament's `size`, `sizePadding`, `availableHeight` and `availableWidth` props enable `x-float`'s `.size` modifier, which wraps Floating UI's `size()` middleware to shrink the panel to fit the visible viewport.
Sylament's `maxHeight` prop is a static CSS value (`--sy-dropdown-max-height`), so a panel taller than the viewport is not reflowed.
The scrollbar half of Filament's feature, the `fi-scrollable` class, is the conditional-scrolling row, which is done.

### The controller always shifts, where Filament's `shift` is opt-in

Filament's `shift` prop defaults to `false` and adds `x-float`'s `.shift` modifier only when set, and `ActionGroup`'s own dropdown markup never sets it.
[`dropdown_controller.ts`](../../../packages/support/assets/src/controllers/dropdown_controller.ts) passes `shift({ padding: 8 })` to every `computePosition()` call, and the template has no `shift` prop to turn it off.
What is missing is the prop and the conditional around the middleware.

## Semantics

### The inline `position: fixed` write precedes the compute, and the `strategy` option does not substitute for it

Floating UI decides both of the things that matter for a teleported panel by reading the panel's **computed** `position`, never the `strategy` it was handed: `getTrueOffsetParent()` returns `null` only for `getComputedStyle(element).position === 'fixed'`, and `getClippingElementAncestors()` opens with the same test to decide whether to start its walk at the panel's parent (`@floating-ui/dom@1.8.0`, `dist/floating-ui.dom.mjs:435` and `:323`).
So a pass computed while the panel is still stylesheet-`absolute` resolves `.sy-dropdown` as the offsetParent and keeps a clipping ancestor (such as the notification toast) in the clipping set: offsetParent-relative coordinates written out as fixed ones, with `flip` / `shift` having clamped to the box the panel was supposed to escape.
`#position()` in [`dropdown_controller.ts`](../../../packages/support/assets/src/controllers/dropdown_controller.ts) therefore assigns `panel.style.position` **before** `await computePosition(...)`, not beside the `left` / `top` writes after it.
A tidy-up that groups the three style writes would undo it, and the bug is invisible whenever a second positioning pass follows in the same frame, which `autoUpdate`'s observers usually arrange, so that cannot be relied on.
Filament reaches the same invariant structurally, setting `n.style.position = "fixed"` at `x-float`'s directive setup, before any compute can run (`support/dist/index.js`, `c.float.strategy=="fixed"&&(n.style.position="fixed")`).
The flag itself is [`HasDropdown`'s teleport pair](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase).

## Divergences

### The root is a positioned, shrink-to-fit box where Filament's carries no rule at all

`.sy-dropdown` applies `relative inline-block`.
`.fi-dropdown` applies nothing: no rule in any package's CSS source or in the compiled `filament/dist/theme.css` styles it on its own, only descendant rules and one `.fi-dropdown.sm\:fi-hidden` in tables.
Filament's root is therefore a plain block-level `div`.

Both declarations do work Filament's root does not have to do, because the positioning is the port's own, a Stimulus controller over `@floating-ui/dom` rather than Alpine's `x-float`.
`relative` makes the wrapper the panel's containing block, so the `left`/`top` the controller writes for Floating UI's default `absolute` strategy resolve against the trigger's own wrapper (a 20px trigger at the default 8px offset lands at `top: 28px`) rather than against whichever distant positioned ancestor a table row or a card would otherwise supply, and that ancestor's clipping and stacking with it.
`inline-block` keeps the wrapper shrink-to-fit, so a dropdown dropped into a block formatting context wraps its trigger instead of stretching the width of its container; every shipped consumer sits in a flex or grid parent, which blockifies the wrapper, but the primitive is public.

This is a hold rather than a proof: Floating UI would compute coordinates against a different offsetParent perfectly well, so neither declaration is *shown* to be required.
Nothing asks for parity here, and reaching it would change the layout of every dropdown to fix no observed defect, so the rule stays.
A `teleport` panel uses the fixed strategy and resolves against the viewport, so `relative` on the wrapper does nothing for it either way ([semantics](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase)).

### The panel scrolls only when a max-height is set, and the gate is a CSS variable

Filament gives the panel its own scrollbar by adding a `fi-scrollable` class, and never adds it unconditionally.
The port mirrors [`ActionGroup.php:525`](../../../vendor/filament/actions/src/ActionGroup.php)'s gate, `'fi-scrollable' => $maxHeight`: scrollable exactly when a max-height is set.
Filament's *generic* dropdown view is wider, `'fi-scrollable' => $maxHeight || $size` at [`dropdown/index.blade.php:57`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php), because its `size` prop enables the viewport-fitting middleware that can shrink the panel below its content height.
Sylament has no `size` prop (the [size-middleware row](#maxheight-is-a-static-value-and-no-middleware-fits-the-panel-to-the-viewport)), so the two conditions coincide here, and the second disjunct returns with that row.
The scrolling must stay conditional: a non-`visible` `overflow-y` makes the UA re-adjust an `overflow-x: visible` to `auto`, so an always-scrolling panel is a clipping container in both axes, and a nested submenu panel, a laid-out descendant under Floating UI's default `absolute` strategy, would be clipped by its parent.

The gate is a CSS variable rather than a class: [`Dropdown/index.html.twig`](../../../packages/support/templates/components/Dropdown/index.html.twig) writes `--sy-dropdown-overflow: auto` inline in the same branch that writes `--sy-dropdown-max-height`, and the stylesheet consumes `overflow-y: var(--sy-dropdown-overflow, visible)`.
This follows the template-asserts, `var()`-consumes convention [`docs/internals/styling.md`](../../internals/styling.md#why-are-per-instance-values-inline-variables) sets for `width` and `max-height` on the same element, and it keeps the condition stated once, in the template, rather than re-derived in CSS.
An attribute selector over the inline style (`[style*="--sy-dropdown-max-height"]`) is not used, because `*=` has no token boundary and panel-level attribute forwarding would let a caller's style falsely trigger or defeat it.

When a max-height *is* set, the panel is a scroll container again, and a submenu nested inside it still clips.
Filament behaves the same for the same reason, and both offer the same way out: the submenu sets [`teleport`](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase), which positions it against the viewport instead of inside the scrolling parent.

### Submenus are consumer composition, not a shell feature

Neither `dropdown/index.blade.php` nor [`dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) exposes a submenu or nested-panel prop: Filament's generic dropdown has no dropdown-in-dropdown feature to port.
`actions`' [`ActionGroup`](../../../packages/actions/src/ActionGroup.php) defines the interaction, and it needs nothing from the shell beyond the shell itself.

`ActionGroup` renders a dropdown-bearing subgroup as one item in the parent's panel whose markup is that subgroup's own complete `<twig:SylamentSupport:Dropdown>`, positioned `right-start`; Floating UI's default `absolute` strategy keeps it in flow under its own `.sy-dropdown` wrapper, so the shell nests as-is.
What it relies on is the parent panel not being a clipping container (see the [conditional-scrolling divergence](#the-panel-scrolls-only-when-a-max-height-is-set-and-the-gate-is-a-css-variable)).

Flattened sections with dividers are a `.sy-dropdown-list` element, the port of Filament's `fi-dropdown-list`: `grid gap-px px-1`, with a `& + &` rule adding the top border and spacing, so a divider appears exactly between two adjacent sections and never above the first.
The panel pads only vertically (`py-1`), so a divider spans the panel's full width rather than stopping short at an inset.
Filament draws the same picture with `p-1` on every list and `divide-y` on the panel.

Each nested dropdown carries its own controller instance and neither panel traps focus, so the parent needs no focus or Escape handling one level down.
A shell-level submenu prop would be speculative: the consumer composes the primitive instead.

### The open state is a controller field, re-asserted through a MutationObserver

Filament has nothing to port here.
Its dropdown is `x-data="filamentDropdown"`, and Livewire's morph leaves an Alpine component's own reactive state alone, so a re-render simply re-runs the binding and the panel stays open.
This port's visibility is a server-rendered `hidden` attribute on the panel, which idiomorph re-applies from the freshly rendered markup, so without the controller field an open dropdown would snap shut on every live re-render.
A `deferFilters(false)` table re-renders on every field change ([tables' filters chrome](../tables/has-filters.md)), and a deferred panel's Apply button issues a request from an open panel; both keep the panel open, as Filament's do.

The state lives in a private instance field on the controller, which outlives the morph because the controller's root element does, and the panel is re-asserted from it.
**Two paths, because a morph can do two different things to the panel.**
When idiomorph morphs it in place, the element is the same and only the attribute changes: a `MutationObserver` with `attributeFilter: ["hidden"]` catches that and re-opens.
When it replaces the element outright, no mutation reaches the old node: Stimulus' `panelTargetConnected` fires for the new one, re-binds the observer and re-opens.
Both re-entrant paths run `autoUpdate` again, stopping the previous updater first, which also repaints `left`/`top` after a morph reset the inline `style` the previous positioning pass had written.

**`panelTargetDisconnected` ignores a panel that is not the current target**, because the replacement path can deliver the two callbacks in either order.
Idiomorph removes the old siblings it skips past before morphing a later match (`removeNodesBetween()`), but inserts an unmatched new child before the insertion point and sweeps the leftovers only when `morphChildren()` finishes (`live_controller.js:510-541`), so the old panel's removal can arrive before or after the new panel's insertion.
When it arrives after, the disconnect for the **old** panel lands once the new one is wired, and an unconditional teardown would disconnect the observer and stop the `autoUpdate` that `panelTargetConnected` had just installed, leaving a visibly open panel that stops repositioning and does not survive the next morph.
Comparing the argument against the current `panelTarget` makes the callback inert in exactly that case while keeping the full teardown for the case it exists for: a panel removed and not replaced, where leaving the observer and the updater running would poll a detached node forever.

**The guard against a controller-initiated toggle looping is the state pair, not a re-entrancy flag.**
MutationObserver callbacks arrive on a microtask, so a `flag = true; panel.hidden = …; flag = false` fence is already `false` by the time the callback reads it.
Instead `close()` clears the field *before* writing the attribute and the callback ignores a closed dropdown; `open()` leaves the attribute removed and the callback ignores a visible panel.
Nothing else is needed, and a flag added "for safety" would be inert at best.

**Why not the vendor's post-render signal.** ux-live-component has one, and it is not reachable from here.
Its post-morph notification is a JS hook on the `Component` object: [`live_controller.js:1782`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js) triggers `render:finished`, typed at `live_controller.d.ts:87` as `'render:finished': (component: Component) => MaybePromise`, subscribed through `Component#on()`/`off()` at `live_controller.js:1583`/`1586`.
It is **not** a DOM event: the only CustomEvents the live controller dispatches are `live:connect` and `live:disconnect` (`live_controller.js:2484`/`2491`, through the `prefix: "live"` dispatcher at `:2535`), plus the intersection plugin's one-shot `live:appear` (`:2015`), so there is no `live:render` to listen for on an ancestor.
A re-render *can* put one more `CustomEvent` on the wire, but it is the host's own: whatever the PHP side queued through `dispatchBrowserEvent()` is replayed as a bubbling event at `:1768`, immediately before the hook, so its name is the server's choice and every host would have to emit one on every render, which is an application-level signal rather than a post-morph one a support primitive can subscribe to.
Reaching the hook means either the undeclared `element.__component` property the controller defines at `live_controller.js:2461`, or importing `getComponent` from `@symfony/ux-live-component`, which support's `package.json` does not depend on, while the packages above it declare it.
The observer needs neither, and it keeps the primitive correct in a page with no live component in it at all, where the hook does not exist.

**One accepted overlap.** ux-live-component's `ExternalMutationTracker` (`live_controller.js:1197`) observes the component subtree with `attributes: true` (`:1217`) and replays an externally changed attribute onto the incoming node during the morph (`elementChanges.applyToElement(toEl)` at `:1049`), so in the common case it already preserves the removed `hidden`.
The controller does not depend on that and does not fight it: when the tracker wins, the observer sees no mutation and does nothing.
It covers the cases the tracker misses, a replaced panel element or a tracker restarted between renders.

No automatic test runs the controller, because no asset workspace has a JS test runner; the behaviour is checked in the demo's live filters card.

### A prevented Escape does not close the panel

The controller's document-level `keydown` listener closes the panel on Escape and refocuses the trigger, but skips a keydown whose default was already prevented.
Filament routes Escape differently: `handleEscape()` in `dropdown.js` intercepts only for panel content marked `[data-dropdown-escape]`, which may cancel a `dropdown-escape` event to keep the panel open, and lets every other keydown, such as a searchable select's, reach its own listeners before the floating plugin closes the panel.
The port has no opt-in marker; tables' filter form puts a `multiple()` or `searchable()` [select](../forms/select.md) inside the panel, whose engine consumes Escape to close its own listbox (`preventDefault()`, no `stopPropagation()`), and the gate keeps one keypress from closing the listbox and the filters panel together.
Support's [modal](modal.md) carries the same gate on its click-through Escape listener, for the same control.
