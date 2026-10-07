---
description: The shared `<twig:SylamentSupport:Dropdown>` primitive, a positioned-panel port of Filament's Alpine/floating-ui-driven `<x-filament::dropdown>`. (draft)
ports:
  - vendor/filament/support/resources/views/components/dropdown/index.blade.php
  - vendor/filament/support/resources/js/index.js
owns:
  - packages/support/assets/src/controllers/dropdown_controller.ts
  - packages/support/assets/src/styles/components/dropdown.css
  - packages/support/templates/components/Dropdown/
---
# Dropdown (support)

`packages/support/templates/components/Dropdown/index.html.twig` — the shared `<twig:SylamentSupport:Dropdown>` primitive, a positioned-panel port of Filament's Alpine/floating-ui-driven `<x-filament::dropdown>`.
It is template-only: there is no Sylament PHP `DropdownComponent` class, mirroring Filament itself — the Blade view has no matching static-config class either (only its `list`/`header` sub-components do, out of scope here).
The markup shell — root/trigger/panel structure, the `placement`/`offset`/`flip`/`teleport`/`width`/`maxHeight` props — and the `sylament--support--dropdown` Stimulus controller/target/action wiring are both in place, `actions`' `ActionGroup` composes the shell, and the panel now carries divider-separated `.sy-dropdown-list` sections and nests one level down as a submenu.
The rows inside those sections are a component of their own, `<twig:SylamentSupport:DropdownItem>` — tracked in [dropdown-item.md](dropdown-item.md), which also took the `.sy-dropdown-item` rules out of this shell's stylesheet.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Root/trigger/panel shell + `placement`/`offset`/`flip`/`width`/`maxHeight` props | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) props, `$trigger`, `$slot` | done | — | [diverges](#the-root-is-a-positioned-shrink-to-fit-box-where-filaments-carries-no-rule-at-all) |
| `sylament--support--dropdown` positioning controller (toggle + floating placement) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `x-data="filamentDropdown"` / `x-float` | done | — | — |
| `teleport` prop (escape a clipping ancestor) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `teleport` prop | done | — | [semantics](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase) |
| Auto-placement when no explicit placement is given | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `placement` default `null` | todo | — | [detail](#details) |
| Size middleware (`size` / `availableHeight` / `availableWidth` / `sizePadding`) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `size`, `sizePadding`, `availableHeight`, `availableWidth` | todo | — | [detail](#size-middleware) |
| Conditional panel scrolling (`fi-scrollable` equivalent) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `fi-scrollable` class, gated on `$maxHeight` | done | — | [diverges](#the-panel-scrolls-only-when-a-max-height-is-set-and-the-gate-is-a-css-variable) |
| `sy-dropdown-list` sections and their dividers | [`dropdown/list/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/index.blade.php) `fi-dropdown-list` | done | — | — |
| Submenu support (nested dropdowns for nested action groups) | — (no Filament dropdown equivalent) | done | — | [diverges](#submenus-are-consumer-composition-not-a-shell-feature) |
| Open state survives a live re-render morph | — (no Filament equivalent — Alpine's `x-data` state outlives a Livewire morph by construction) | done | — | [diverges](#the-open-state-is-a-controller-field-re-asserted-through-a-mutationobserver) |

The shell row, plus the markup half of the positioning-controller row, is exercised by [`actions/tests/Integration/Components/DropdownRenderTest.php`](../../../packages/actions/tests/Integration/Components/DropdownRenderTest.php) — a support-owned primitive render-tested from the `actions` suite, same placement as the Button/Modal render tests — asserting the `sy-dropdown` shell, the `sylament--support--dropdown` controller/target/action attributes, the rendered `placement`/`offset`/`flip`/`teleport` values, the hidden panel, and the `width`/`maxHeight` CSS custom properties.
The submenu and section rows are exercised from the consumer side, in [`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php).
The trigger's *box* is held separately by [`support/tests/Architecture/CssDropdownTriggerTest.php`](../../../packages/support/tests/Architecture/CssDropdownTriggerTest.php), which reads the stylesheet: the markup those render tests assert was never what went wrong there — `.sy-dropdown-trigger` shipped `inline-flex` where Filament applies `flex cursor-pointer`, so the wrapper reserved baseline descender space nothing filled and every ActionGroup trigger rode above the actions it was centred against (measured in the demo at 6px of slack on a 20px icon-button trigger, putting its icon 3px above its siblings').
A rule a render cannot see can only be pinned at its source.

**Seen working since.**
The conditional-scrolling change was made to stop a submenu panel being clipped by its parent, and neither the clipping nor the fix is reachable from a render test — both are layout.
It shipped on the strength of Filament's equivalent rules and of reasoning about the UA's `overflow` pair-adjustment, unverified at merge; the peer demo has since shown it working, a three-level submenu chain on `/showcase/actions` rendering every panel outside its parent's box unclipped.
`teleport` is layout in the same way and was verified the same way, in the one place that needs it: a group inside a toast on `/showcase/notifications`, its panel measured 72px past the card's own bottom edge with every corner hit-testing to the panel rather than to whatever the card would have clipped it with, and the untouched `/showcase/actions` panels still carrying no inline `position` at all.
The [modal controller](modal.md) is still unseen.

## Details

### Auto-placement when no explicit placement is given

Filament's generic dropdown view defaults `placement` to `null`; omitting the `.placement.<value>` modifier altogether lets `x-float`'s floating-ui plumbing choose a placement automatically and re-choose on scroll/resize collision.
Sylament's `placement` prop defaults to `'bottom-start'` and is always rendered as an explicit Stimulus value — there is no `null`/automatic mode.
This tracks Filament's actual primary consumer rather than diverging from it: `ActionGroup` hand-rolls its own dropdown markup (not a `<x-filament::dropdown>` composition) and defaults its placement the same way — [`x-float.placement.<?= $this->getDropdownPlacement() ?? 'bottom-start' ?>`](../../../vendor/filament/actions/src/ActionGroup.php) — so the generic view's `null`/auto-placement is a library-level nicety no shipped Filament dropdown consumer actually exercises.
Still deferred to the positioning-controller work; if a future consumer needs the hands-off default, this is where it lands.

### Size middleware

Filament's `size`, `sizePadding`, `availableHeight`, and `availableWidth` props enable `x-float`'s `.size` modifier, wrapping floating-ui's `size()` middleware to shrink the panel to fit the visible viewport.
Sylament's `maxHeight` prop is a static CSS value (`--sy-dropdown-max-height`), not a live viewport-fitted middleware: a panel taller than the viewport is not reflowed.
Deferred; re-lands with the positioning controller if a consumer needs viewport-clamped panels rather than a fixed `maxHeight`.
The **scrollbar** half of Filament's feature — the `fi-scrollable` class it applies whenever a max-height exists — is *not* part of this row any more; it ships independently and has its [own row](#surface) above.

## Divergences

### The root is a positioned, shrink-to-fit box where Filament's carries no rule at all

`.sy-dropdown` applies `relative inline-block`.
`.fi-dropdown` applies nothing: the class exists only as a hook, and grepping every package's CSS source plus the compiled `filament/dist/theme.css` finds no rule whose selector ends at it — only descendant rules and one `.fi-dropdown.sm\:fi-hidden` in tables.
Filament's root is therefore a plain block-level `div`.

Both declarations do work Filament's root does not have to do, because the positioning is the port's own — a Stimulus controller over `@floating-ui/dom`, not Alpine's `x-float`.
`relative` makes the wrapper the panel's containing block, so the `left`/`top` the controller writes for Floating UI's default `absolute` strategy resolve against the trigger's own wrapper — a 20px trigger at the default 8px offset lands at `top: 28px` — rather than against whichever distant positioned ancestor a table row or a card would otherwise supply, and that ancestor's clipping and stacking with it.
`inline-block` keeps the wrapper shrink-to-fit, so a dropdown dropped into a block formatting context wraps its trigger instead of stretching the width of its container; every consumer today sits in a flex or grid parent, which blockifies the wrapper and makes that moot, but the primitive is public.

This is a hold rather than a proof: Floating UI would compute coordinates against a different offsetParent perfectly well, so neither declaration has been *shown* to be required.
Nothing asks for parity here, and reaching it would change the layout of every dropdown to fix no observed defect, so the rule stays.
The `teleport` prop settles it for the panels that opt in and leaves it open for the rest — a fixed-strategy panel resolves against the viewport, so `relative` on the wrapper does nothing for it either way ([semantics](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase)).

### The panel scrolls only when a max-height is set, and the gate is a CSS variable

Filament gives the panel its own scrollbar by adding a `fi-scrollable` class, and never adds it unconditionally.
The port mirrors [`ActionGroup.php:525`](../../../vendor/filament/actions/src/ActionGroup.php)'s gate, `'fi-scrollable' => $maxHeight` — scrollable exactly when a max-height is set.
(Filament's *generic* dropdown view is slightly wider, `'fi-scrollable' => $maxHeight || $size` at [`dropdown/index.blade.php:57`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php), because its `size` prop enables the viewport-fitting middleware that can shrink the panel below its content height.
Sylament has no `size` prop — that is the still-`todo` [size-middleware row](#size-middleware) — so the two conditions coincide here, and the second disjunct returns with that row.)
The `overflow-y-auto` rule keys off that class.
Sylament's port once applied `overflow-y-auto` **unconditionally** — a porting fidelity bug, not a design choice, and one with a real consequence: a non-`visible` `overflow-y` makes the UA re-adjust an `overflow-x: visible` to `auto`, so *every* panel was a clipping/scrolling container in both axes, and a nested submenu panel — a laid-out descendant under Floating UI's default `absolute` strategy — was clipped by its parent.

The gate is now a CSS variable rather than a class: [`Dropdown/index.html.twig`](../../../packages/support/templates/components/Dropdown/index.html.twig) writes `--sy-dropdown-overflow: auto` inline in the same branch that writes `--sy-dropdown-max-height`, and the stylesheet consumes `overflow-y: var(--sy-dropdown-overflow, visible)`.
This follows the template asserts / `var()` consumes convention [`docs/internals/styling.md`](../../internals/styling.md#why-are-per-instance-values-inline-variables) already sets for `width` and `max-height` on the same element, and it keeps the condition stated once, in the template, rather than re-derived in CSS.
An earlier form of this fix pattern-matched the inline style with a `[style*="--sy-dropdown-max-height"]` attribute selector; that was replaced because `*=` has no token boundary and panel-level attribute forwarding would let a caller style falsely trigger or defeat it.

**Accepted limitation, matching Filament exactly:** when a max-height *is* set, the panel is a scroll container again, and a submenu nested inside it will still clip.
Filament has the same behavior for the same reason, and both offer the same way out: the submenu sets [`teleport`](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase), which positions it against the viewport instead of inside the scrolling parent.

### Submenus are consumer composition, not a shell feature

Neither `dropdown/index.blade.php` nor [`dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) exposes a submenu/nested-panel prop — Filament's generic dropdown has no first-class dropdown-in-dropdown feature to port.
This row was forward provisioning for `actions`' [`ActionGroup`](../../../packages/actions/src/ActionGroup.php), which is now the consumer that defined the interaction, and the shape it needed turned out to be smaller than this row anticipated:

- **Nested panels** need nothing from the shell.
  `ActionGroup` renders a dropdown-bearing subgroup as one item in the parent's panel whose markup is that subgroup's own complete `<twig:SylamentSupport:Dropdown>`, positioned `right-start`; Floating UI's default `absolute` strategy keeps it in flow under its own `.sy-dropdown` wrapper, so the existing shell nests as-is.
  What it *did* need was for the parent panel to stop being a clipping container — see the [conditional-scrolling divergence](#the-panel-scrolls-only-when-a-max-height-is-set-and-the-gate-is-a-css-variable).
- **Flattened sections with dividers** are a `.sy-dropdown-list` element, the port of Filament's `fi-dropdown-list`: `grid gap-px px-1`, with a `& + &` rule adding the top border and spacing, so a divider appears exactly between two adjacent sections and never above the first.
  The panel's own padding moved from `p-1` to `py-1` so a divider spans the panel's full width rather than stopping short at the inset.
- **Parent-panel focus/escape handling one level down** turned out not to be needed: each nested dropdown carries its own controller instance, and neither panel traps focus.

Nothing further is provisioned.
A shell-level submenu *prop* would still be speculative — the consumer composes the primitive instead.

### The open state is a controller field, re-asserted through a MutationObserver

Filament has nothing to port here.
Its dropdown is `x-data="filamentDropdown"`, and Livewire's morph leaves an Alpine component's own reactive state alone — the panel's visibility is an `x-show` binding over that state, so a re-render simply re-runs the binding and the panel stays open.
This port's visibility is a server-rendered `hidden` attribute on the panel, which idiomorph re-applies from the freshly rendered markup, so an open dropdown snapped shut on every live re-render.
A `deferFilters(false)` table re-renders on every field change, and that is the consumer that made this a defect rather than a footnote ([tables' filters chrome](../tables/table.md)) — but a deferred panel is not requestless either: its Apply button is a request issued from an open panel, and it now leaves the panel open too, so the earlier reading of an Apply click closing the panel as correct UX no longer holds.
Filament lands in the same place for the same reason: its panel's open state is Alpine's, which a Livewire morph leaves alone, so applying never closed it there.

The state now lives in a private instance field on the controller, which outlives the morph because the controller's root element does, and the panel is re-asserted from it.
**Two paths, because a morph can do two different things to the panel.**
When idiomorph morphs it in place, the element is the same and only the attribute changes — a `MutationObserver` with `attributeFilter: ["hidden"]` catches that and re-opens.
When it replaces the element outright, no mutation reaches the old node — Stimulus' `panelTargetConnected` fires for the new one, re-binds the observer and re-opens.
Both re-entrant paths run `autoUpdate` again, stopping the previous updater first, which also repaints `left`/`top` after a morph reset the inline `style` the previous positioning pass had written.

**`panelTargetDisconnected` ignores a panel that is no longer the target**, because the replacement path can deliver the two callbacks in either order.
Idiomorph swaps a hard-mismatched child with `replaceChild` (`live_controller.js:482`) — one mutation record carrying both nodes, and Stimulus reads removals before additions within a record (`stimulus.js:485`/`:486`) — but an *unmatched* new child is inserted before the insertion point and the leftovers swept afterwards (`live_controller.js:532`, sweep at `:536`), which is two records in added-then-removed order.
There the disconnect for the **old** panel arrives after the new one is already wired, and an unconditional teardown would disconnect the observer and stop the `autoUpdate` that `panelTargetConnected` had just installed — leaving a visibly open panel that no longer repositions and no longer survives the next morph.
Comparing the argument against the current `panelTarget` makes the callback inert in exactly that case while keeping the full teardown for the case it exists for: a panel removed and not replaced, where leaving the observer and the updater running would poll a detached node forever.

**The guard against a controller-initiated toggle looping is the state pair, not a re-entrancy flag.**
MutationObserver callbacks arrive on a microtask, so a `flag = true; panel.hidden = …; flag = false` fence is already `false` by the time the callback reads it.
Instead `close()` clears the field *before* writing the attribute and the callback ignores a closed dropdown; `open()` leaves the attribute removed and the callback ignores a visible panel.
Nothing else is needed, and a flag added back "for safety" would be inert at best.

**Why not the vendor's post-render signal.** ux-live-component has one, and it is not reachable from here.
Its post-morph notification is a JS hook on the `Component` object — [`live_controller.js:1585`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js) triggers `render:finished`, typed at `live_controller.d.ts:77` as `'render:finished': (component: Component) => MaybePromise`, subscribed through `Component#on()`/`off()` at `live_controller.js:1416`/`1419`.
It is **not** a DOM event: the only CustomEvents the live controller dispatches are `live:connect` and `live:disconnect` (`live_controller.js:2228`/`2235`, through the `prefix: "live"` dispatcher at `:2279`), plus the intersection plugin's one-shot `live:appear` (`:1802`) — there is no `live:render` to listen for on an ancestor.
A re-render *can* put one more `CustomEvent` on the wire, but it is the host's own: whatever the PHP side queued through `dispatchBrowserEvent()` is replayed as a bubbling event at `:1579`-`:1583`, immediately before the hook — so its name is the server's choice and every host would have to emit one on every render, which is an application-level signal rather than a post-morph one a support primitive can subscribe to.
Reaching the hook means either the undeclared `element.__component` expando the controller defines at `live_controller.js:2205`, or importing `getComponent` from `@symfony/ux-live-component` — which `packages/support/assets/package.json` does not depend on (its dependencies are `@floating-ui/dom` plus the Stimulus peer), and which `packages/actions/assets` is deliberately the toolkit's only npm consumer of.
The observer needs neither, and it keeps the primitive correct in a page with no live component in it at all, where the hook does not exist.

**One accepted gap.** ux-live-component's `ExternalMutationTracker` (`live_controller.js:1064`) observes the component subtree with `attributes: true` (`:1080`) and replays an externally changed attribute onto the incoming node during the morph (`elementChanges.applyToElement(toEl)` at `:917`, applied at `:1043`), so in the common case it *already* preserves the removed `hidden`.
This fix does not depend on that and does not fight it: when the tracker wins, the observer sees no mutation and does nothing.
It is the cases the tracker misses — a replaced panel element, a tracker restarted between renders — that this covers, which is why the belt is kept alongside the braces.

**Unpinned by test, on purpose.**
No JS test rig exists in this repo — no `vitest`/`test:unit` target in any asset workspace — so `tsc` and Biome are the only automatic verifiers of this file, and the behavioural verification is the demo browser pass on the filters card in live mode; the `dist` byte-stability gate is the only automatic check the change itself carries.

### A prevented Escape does not close the panel

The controller's document-level `keydown` listener closes the panel on Escape — the port of Filament's `@keydown.escape.window` close — but skips a keydown whose default was already prevented.
Filament's dropdown has no such gate because its panel never hosts a control with its own Escape; ours does once tables' filter form puts a `multiple()` or `searchable()` [select](../forms/select.md) inside it, whose engine consumes Escape to close its own listbox (`preventDefault()`, no `stopPropagation()`).
Without the gate one keypress closed the listbox and the filters panel together.
Support's [modal](modal.md) carries the same gate on its click-through Escape listener, for the same control.
