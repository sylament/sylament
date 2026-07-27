# Dropdown (support)

`packages/support/templates/components/Dropdown/index.html.twig` — the shared
`<twig:SylamentSupport:Dropdown>` primitive, a positioned-panel port of Filament's
Alpine/floating-ui-driven `<x-filament::dropdown>`. It is template-only: there is
no Sylament PHP `DropdownComponent` class, mirroring Filament itself — the Blade
view has no matching static-config class either (only its `list`/`header`
sub-components do, out of scope here). This task ships the markup shell that
`actions`' `ActionGroup` will compose in a later task: the root/trigger/panel
structure, the `placement`/`offset`/`flip`/`width`/`maxHeight` props, and the
`sylament--support--dropdown` Stimulus controller/target/action wiring — the
controller class itself lands in the next task.

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
write-up in [Details](#details).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Root/trigger/panel shell + `placement`/`offset`/`flip`/`width`/`maxHeight` props | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) props, `$trigger`, `$slot` | done | — | — |
| `sylament--support--dropdown` positioning controller (toggle + floating placement) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `x-data="filamentDropdown"` / `x-float` | in progress | — | [detail](#details) |
| Teleport (render panel outside the stacking context) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `teleport` prop | todo | — | [detail](#details) |
| Auto-placement when no explicit placement is given | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `placement` default `null` | todo | — | [detail](#details) |
| Size middleware (`size` / `availableHeight` / `availableWidth` / `sizePadding`) | [`dropdown/index.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/index.blade.php) `size`, `sizePadding`, `availableHeight`, `availableWidth` | todo | — | [detail](#details) |
| Submenu support (nested dropdowns for nested action groups) | — (no Filament dropdown equivalent) | todo | — | [detail](#details) |

The shell row, plus the markup half of the positioning-controller row, is
exercised by
[`actions/tests/Integration/Components/DropdownRenderTest.php`](../../../packages/actions/tests/Integration/Components/DropdownRenderTest.php)
— a support-owned primitive render-tested from the `actions` suite, same
placement as the Button/Modal render tests — asserting the `sy-dropdown` shell,
the `sylament--support--dropdown` controller/target/action attributes, the
rendered `placement`/`offset`/`flip` values, the hidden panel, and the
`width`/`maxHeight` CSS custom properties. The controller's actual behavior
(toggling, positioning) has no test yet — there is no controller to run.

## Details

### `sylament--support--dropdown` positioning controller

This task renders only the markup: the `.sy-dropdown` root carries
`data-controller="sylament--support--dropdown"` plus the `placement`/`offset`/
`flip` Stimulus values; the `.sy-dropdown-trigger` wrapper carries the `trigger`
target and a `click→toggle` action; the `.sy-dropdown-panel` carries the `panel`
target and starts `hidden`. No `sylament--support--dropdown` controller class
exists yet, so `toggle` currently has nothing listening — the next task adds it:
showing/hiding the panel on `toggle()` and positioning it against the trigger
using the `placement`/`offset`/`flip` values already rendered here. Filament's
equivalent is the `x-data="filamentDropdown"` object plus the `x-float`
directive (registered by the `@awcodes/alpine-floating-ui` plugin imported in
[`resources/js/index.js`](../../../vendor/filament/support/resources/js/index.js)),
which also binds `x-on:keyup.enter`, `x-on:keyup.space`, and
`x-on:mousedown` on the trigger rather than a plain `click` — the controller
task should decide whether Stimulus needs the same keyboard/pointer surface or
a plain `click`/`keydown` pair suffices.

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
to shrink the panel — and give it its own scrollbar (`fi-scrollable`) — to fit
the visible viewport. Sylament's `maxHeight` prop is a static CSS value
(`--sy-dropdown-max-height`), not a live viewport-fitted middleware: a panel
taller than the viewport is not reflowed. Deferred; re-lands with the
positioning controller if a consumer needs viewport-clamped panels rather than a
fixed `maxHeight`.

### Submenu support

Neither `dropdown/index.blade.php` nor
[`dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php)
exposes a submenu/nested-panel prop — Filament's generic dropdown has no
first-class dropdown-in-dropdown feature to port. This row is forward
provisioning for `actions`' [`ActionGroup`](../../../vendor/filament/actions/src/ActionGroup.php),
whose `make()` accepts `array<Action | ActionGroup>`: a group can nest another
group, and `ActionGroup` (which hand-rolls its own dropdown markup rather than
composing `<x-filament::dropdown>`) renders a nested group that itself
`hasDropdown()` as one list item whose HTML is that nested group's own dropdown
— a submenu, structurally — while a nested group *without* its own dropdown is
flattened into a second `fi-dropdown-list` in the parent panel instead. Sylament
has no shell support for either shape yet: nesting a second `.sy-dropdown-panel`
inside an item, with parent-panel focus/escape handling that stays correct one
level down, and the flattened-list-with-divider case. Todo until a nested-group
consumer (Task 9's `ActionGroup` or later) defines the exact interaction.
