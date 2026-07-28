# Dropdown (support)

`packages/support/templates/components/Dropdown/index.html.twig` — the shared
`<twig:SylamentSupport:Dropdown>` primitive, a positioned-panel port of Filament's
Alpine/floating-ui-driven `<x-filament::dropdown>`. It is template-only: there is
no Sylament PHP `DropdownComponent` class, mirroring Filament itself — the Blade
view has no matching static-config class either (only its `list`/`header`
sub-components do, out of scope here). The markup shell — root/trigger/panel
structure, the `placement`/`offset`/`flip`/`width`/`maxHeight` props — and the
`sylament--support--dropdown` Stimulus controller/target/action wiring are both
in place, `actions`' `ActionGroup` composes the shell, and the panel now carries
divider-separated `.sy-dropdown-list` sections and nests one level down as a
submenu.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php)
  — the Blade view whose props and structure this template diffs against
- [`support/resources/js/index.js`](../../../vendor/filament/support/resources/js/index.js)
  — registers the `@awcodes/alpine-floating-ui` plugin backing the `x-float`
  directive the deferred rows below diff against

## Surface

_Blocked by_ names what has to land first. _Detail_ carries **detail** → the
write-up in [Details](#details), and/or **diverges** → the note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Root/trigger/panel shell + `placement`/`offset`/`flip`/`width`/`maxHeight` props | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) props, `$trigger`, `$slot` | done | — | — |
| `sylament--support--dropdown` positioning controller (toggle + floating placement) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `x-data="filamentDropdown"` / `x-float` | done | — | — |
| Teleport (render panel outside the stacking context) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `teleport` prop | todo | — | [detail](#details) |
| Auto-placement when no explicit placement is given | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `placement` default `null` | todo | — | [detail](#details) |
| Size middleware (`size` / `availableHeight` / `availableWidth` / `sizePadding`) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `size`, `sizePadding`, `availableHeight`, `availableWidth` | todo | — | [detail](#size-middleware) |
| Conditional panel scrolling (`fi-scrollable` equivalent) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `fi-scrollable` class, gated on `$maxHeight` | done | — | [diverges](#the-panel-scrolls-only-when-a-max-height-is-set-and-the-gate-is-a-css-variable) |
| `sy-dropdown-list` sections and their dividers | [`dropdown/list/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/index.blade.php) `fi-dropdown-list` | done | — | — |
| Submenu support (nested dropdowns for nested action groups) | — (no Filament dropdown equivalent) | done | — | [diverges](#submenus-are-consumer-composition-not-a-shell-feature) |

The shell row, plus the markup half of the positioning-controller row, is
exercised by
[`actions/tests/Integration/Components/DropdownRenderTest.php`](../../../packages/actions/tests/Integration/Components/DropdownRenderTest.php)
— a support-owned primitive render-tested from the `actions` suite, same
placement as the Button/Modal render tests — asserting the `sy-dropdown` shell,
the `sylament--support--dropdown` controller/target/action attributes, the
rendered `placement`/`offset`/`flip` values, the hidden panel, and the
`width`/`maxHeight` CSS custom properties. The submenu and section rows are
exercised from the consumer side, in
[`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php).

**Known unverified at merge.** The conditional-scrolling change was made to
stop a submenu panel being clipped by its parent, and neither the clipping nor
the fix is reachable from a render test — both are layout, and this repo has no
browser driver. The CSS is verified by reading Filament's equivalent rules and
by reasoning about the UA's `overflow` pair-adjustment; it has not been seen
working. Same status as the [modal controller](modal.md).

## Details

### Teleport

Filament's dropdown accepts a `teleport` prop (a `.teleport` modifier on
`x-float`) that renders the panel through Alpine's `x-teleport`, moving it out
of the trigger's DOM position — useful inside an `overflow: hidden` / `overflow:
auto` ancestor (a table cell, a scrolling card) that would otherwise clip the
panel. Sylament's shell always renders `.sy-dropdown-panel` as a normal child of
`.sy-dropdown`; nothing teleports it yet. Deferred until a consumer is clipped by
an ancestor — the tables row-action dropdown is the likely first one.

### Auto-placement when no explicit placement is given

Filament's generic dropdown view defaults `placement` to `null`; omitting the
`.placement.<value>` modifier altogether lets `x-float`'s floating-ui plumbing
choose a placement automatically and re-choose on scroll/resize collision.
Sylament's `placement` prop defaults to `'bottom-start'` and is always rendered
as an explicit Stimulus value — there is no `null`/automatic mode. This tracks
Filament's actual primary consumer rather than diverging from it: `ActionGroup`
hand-rolls its own dropdown markup (not a `<x-filament::dropdown>` composition)
and defaults its placement the same way —
[`x-float.placement.<?= $this->getDropdownPlacement() ?? 'bottom-start' ?>`](../../../vendor/filament/actions/src/ActionGroup.php)
— so the generic view's `null`/auto-placement is a library-level nicety no
shipped Filament dropdown consumer actually exercises. Still deferred to the
positioning-controller work; if a future consumer needs the hands-off default,
this is where it lands.

### Size middleware

Filament's `size`, `sizePadding`, `availableHeight`, and `availableWidth` props
enable `x-float`'s `.size` modifier, wrapping floating-ui's `size()` middleware
to shrink the panel to fit the visible viewport. Sylament's `maxHeight` prop is
a static CSS value (`--sy-dropdown-max-height`), not a live viewport-fitted
middleware: a panel taller than the viewport is not reflowed. Deferred;
re-lands with the positioning controller if a consumer needs viewport-clamped
panels rather than a fixed `maxHeight`. The **scrollbar** half of Filament's
feature — the `fi-scrollable` class it applies whenever a max-height exists —
is *not* part of this row any more; it ships independently and has its
[own row](#surface) above.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The panel scrolls only when a max-height is set, and the gate is a CSS variable

Filament gives the panel its own scrollbar by adding a `fi-scrollable` class,
and never adds it unconditionally. The port mirrors
[`ActionGroup.php:525`](../../../vendor/filament/actions/src/ActionGroup.php)'s
gate, `'fi-scrollable' => $maxHeight` — scrollable exactly when a max-height is
set. (Filament's *generic* dropdown view is slightly wider,
`'fi-scrollable' => $maxHeight || $size` at
[`dropdown/index.blade.php:57`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php),
because its `size` prop enables the viewport-fitting middleware that can shrink
the panel below its content height. Sylament has no `size` prop — that is the
still-`todo` [size-middleware row](#size-middleware) — so the two conditions
coincide here, and the second disjunct returns with that row.)
The `overflow-y-auto` rule keys off that class. Sylament's port applied
`overflow-y-auto` **unconditionally** for its first two epics — a porting
fidelity bug, not a design choice, and one with a real consequence: a
non-`visible` `overflow-y` makes the UA re-adjust an `overflow-x: visible` to
`auto`, so *every* panel was a clipping/scrolling container in both axes, and a
nested submenu panel — a laid-out descendant under Floating UI's default
`absolute` strategy — was clipped by its parent.

The gate is now a CSS variable rather than a class:
[`Dropdown/index.html.twig`](../../../packages/support/templates/components/Dropdown/index.html.twig)
writes `--sy-dropdown-overflow: auto` inline in the same branch that writes
`--sy-dropdown-max-height`, and the stylesheet consumes
`overflow-y: var(--sy-dropdown-overflow, visible)`. This follows the template
asserts / `var()` consumes convention [rule 4 of `docs/styling.md`](../../styling.md)
already sets for `width` and `max-height` on the same element, and it keeps the
condition stated once, in the template, rather than re-derived in CSS. An
earlier form of this fix pattern-matched the inline style with a
`[style*="--sy-dropdown-max-height"]` attribute selector; that was replaced
because `*=` has no token boundary and panel-level attribute forwarding would
let a caller style falsely trigger or defeat it.

**Accepted limitation, matching Filament exactly:** when a max-height *is* set,
the panel is a scroll container again, and a submenu nested inside it will
still clip. Filament has the same behavior for the same reason; solving it
needs the [teleport](#teleport) row or a `fixed` positioning strategy.

### Submenus are consumer composition, not a shell feature

Neither `dropdown/index.blade.php` nor
[`dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php)
exposes a submenu/nested-panel prop — Filament's generic dropdown has no
first-class dropdown-in-dropdown feature to port. This row was forward
provisioning for `actions`' [`ActionGroup`](../../../packages/actions/src/ActionGroup.php),
which is now the consumer that defined the interaction, and the shape it needed
turned out to be smaller than this row anticipated:

- **Nested panels** need nothing from the shell. `ActionGroup` renders a
  dropdown-bearing subgroup as one item in the parent's panel whose markup is
  that subgroup's own complete `<twig:SylamentSupport:Dropdown>`, positioned
  `right-start`; Floating UI's default `absolute` strategy keeps it in flow
  under its own `.sy-dropdown` wrapper, so the existing shell nests as-is. What
  it *did* need was for the parent panel to stop being a clipping container —
  see the [conditional-scrolling divergence](#the-panel-scrolls-only-when-a-max-height-is-set-and-the-gate-is-a-css-variable).
- **Flattened sections with dividers** are a `.sy-dropdown-list` element, the
  port of Filament's `fi-dropdown-list`: `grid gap-px px-1`, with a
  `& + &` rule adding the top border and spacing, so a divider appears exactly
  between two adjacent sections and never above the first. The panel's own
  padding moved from `p-1` to `py-1` so a divider spans the panel's full width
  rather than stopping short at the inset.
- **Parent-panel focus/escape handling one level down** turned out not to be
  needed: each nested dropdown carries its own controller instance, and neither
  panel traps focus.

Nothing further is provisioned. A shell-level submenu *prop* would still be
speculative — the consumer composes the primitive instead.
