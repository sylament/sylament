# ActionGroup base + Dropdown primitive — design

Date: 2026-07-27
Status: approved (design); implementation plan to follow

## Why now

The Section header/footer-actions epic (the three blocked rows in
`docs/progress/schemas/section.md`) writes `Action | ActionGroup` unions across
the schemas surface. Building an ActionGroup **base** first lets those
signatures land Filament-final on first write, and forces the Action
view-switching machinery (`defaultTemplate()`, grouped item template) that the
Section epic's `configureChildSchema()` polish branch needs anyway. Sequencing
decided in conversation: this epic, then the schemas↔actions integration epic.

"Base" scope: full model surface plus a real Floating-UI dropdown rendering,
with the trigger-view catalog, submenus, badges, and table/bulk machinery
deferred. Deferred ≠ dropped — every deferred surface gets a row in the
progress tables.

## Decisions taken

- **Rendering scope:** model + full dropdown with Floating UI positioning
  (not model-only, not a naive CSS dropdown).
- **JS dependency shipping:** `@floating-ui/dom` is bundled into
  `@sylament/support`'s dist (single `--bundle` esbuild entry for the dropdown
  controller, `@hotwired/stimulus` external). No consumer importmap step.
- **Layering:** the dropdown is a generic support primitive (Filament parity —
  their dropdown views live in filament/support); ActionGroup composes it.
- **Mount resolution:** unchanged in this epic. Grouped items emit the same
  `live_action('mountAction', { name })`; hosts resolve via the existing
  `{name}Action()` method convention. Flat-actions-based resolution arrives
  with the schemas integration epic.

## Support package

### Dropdown Twig component

`packages/support/templates/components/Dropdown/index.html.twig`, used as
`<twig:SylamentSupport:Dropdown>`. Anonymous component with `{% props %}`,
following the Modal precedent.

- Props: `placement` (default `bottom-start`), `offset`, `width`, `maxHeight`,
  `flip` (default `true`). Defaults not stated here mirror Filament's dropdown
  defaults, read from their JS/blade at implementation time.
- Blocks: `trigger` (caller slots button-ish markup, e.g.
  `<twig:SylamentSupport:IconButton>`), default block = panel content.
- Markup: root `sy sy-dropdown`; panel `sy-dropdown-panel`, hidden until open.
  Per-instance width/max-height via inline `--sy-*` CSS variables. Item
  styling class `sy-dropdown-item` lives in support's CSS (generic, like
  Filament's `fi-dropdown-list-item`), consumed by actions' templates.
- ARIA: `aria-expanded` + `aria-haspopup` on the trigger; no ARIA `menu`
  role pattern — items are plain buttons/links, matching Filament.

### Dropdown Stimulus controller

`packages/support/assets/src/controllers/dropdown_controller.ts`
(`sylament--support--dropdown`), following the Modal controller precedent.

- Targets: trigger, panel. Values mirror the component props.
- Open: `computePosition` with `offset` / `flip` (when enabled) / `shift`
  middleware; `autoUpdate` keeps position fresh while open and is torn down on
  close and `disconnect()` — no leaked observers.
- Close: Escape (focus returns to trigger) and click-outside.
- Deferred: teleport (moving the panel to `<body>`), `auto-placement`.

### Build change (bundled entry)

`@floating-ui/dom` becomes a `dependencies` entry of `@sylament/support`
(pinned by the committed `pnpm-lock.yaml`). `build:js` (and `watch:js`) gain a
bundled pass for the dropdown controller only: `--bundle` with
`--external:@hotwired/stimulus`; every other file keeps the per-file,
no-bundle build. The CI dist-reproducibility check must stay green — the
bundled output rebuilds byte-for-byte from the lockfile.

### ViewComponent::defaultTemplate()

Port of Filament's `ViewComponent::defaultView()` precedence, template-named
per the systematic view→template translation (no rename entry needed):

- New `protected string|Closure|null $defaultTemplate` + `defaultTemplate()`
  setter on `Sylament\Support\Components\ViewComponent`.
- `getTemplate()` resolves explicit `template()` first, then the default.
  Neither set → existing null-template behavior, unchanged.

## Actions package

### ActionGroup model

`Sylament\Actions\ActionGroup extends ViewComponent`. Filament-exact
construction: `make(array $actions)` / final constructor take the actions
array (no name parameter).

- `actions(array $actions)` is **eager**, as in Filament: each child gets a
  `group($this)` back-reference, and a name-keyed `$flatActions` map is built,
  recursing through nested groups. `getFlatActions()` returns it — this
  surface is final for the schemas epic.
- `getActions()` returns children with `defaultTemplate()` pushed onto
  `Action` children: the grouped item template when the dropdown renders,
  their normal look under `dropdown(false)`.
- Visibility composes: group hidden when its own flag says so **or** every
  child is hidden (children checked via plain `isHidden()`).
- Trigger surface reuses existing concerns: `HasLabel` (plain-string default
  `'Actions'` — no translator dependency), `HasIcon` (default
  ellipsis-vertical), color/size/tooltip/extra-attributes.
- `HasDropdown` port: `dropdown()`, `dropdownPlacement()` +
  `defaultDropdownPlacement()`, `dropdownOffset()`, `dropdownWidth()`,
  `dropdownMaxHeight()`, `dropdownFlip()` — closure-evaluable, flowing into
  the Dropdown component props. Deferred: `dropdownTeleport()`,
  `dropdownAutoPlacement()`.
- New `BelongsToGroup` concern on both `Action` and `ActionGroup`
  (`group()` / `getGroup()`).
- Propagation: `liveComponent()` and `record()` on the group forward to
  children; `Action::getRecord()` gains a group fallback.

### Rendering

- Group template (flat file, `ViewComponent::$template` convention):
  composes `<twig:SylamentSupport:Dropdown>`; `dropdown(false)` renders
  children inline in their normal button look instead.
- Trigger goes through `$triggerTemplate` / `defaultTriggerTemplate`
  machinery (the extension point for badge/link/button-group triggers later).
  Two trigger templates now: **icon-button** (default) and **button**, with
  `iconButton()` / `button()` / `isIconButton()` / `isButton()`.
- Grouped item template `grouped-action.html.twig`: full-width row — icon,
  label, URL-or-`mountAction`, disabled state — `sy-dropdown-item` classes.
  Items stay real `Action`s rendered through `renderComponent()`, so
  mounting, confirm modals, and the `Modals` renderer work unchanged.
- A nested `ActionGroup` child **throws `LogicException` at render time**
  (submenus deferred; model-level nesting stays valid so `getFlatActions()`
  is complete).

## Testing

Canonical buckets, mirroring `src/`:

- support Unit: `defaultTemplate()` precedence on `ViewComponent`.
- actions Unit: `ActionGroupTest` (make, eager flat actions incl. nested
  recursion, visibility composition, propagation forwarding, trigger-template
  switching) + per-concern tests with fixtures (`BelongsToGroupTest`,
  `HasDropdownTest`) per the existing `Fixtures/Concerns` pattern.
- actions Integration: `DropdownRenderTest` (support component render-tested
  from actions, per the Modal/Button precedent — actions' kernel registers
  TwigComponentBundle) and `ActionGroupRenderTest` via a fixture host:
  trigger + hidden panel + items render, item emits `mountAction`, URL item
  renders an anchor, `dropdown(false)` inline row, nested group throws.
- JS: `tsc --noEmit` only; no JS test runner exists in the workspace and none
  is added (same status as the existing three controllers).
- Gates: `composer test`, `pnpm test:types` + `pnpm test:lint`,
  `composer format`, CI dist-reproducibility.

## Bookkeeping (same commit as the code it describes)

- New `docs/progress/actions/action-group.md`: surface table with deferred
  rows — badge/button-group/link/grouped trigger views, submenu rendering,
  teleport, auto-placement, `toArray`/`Arrayable`, table/bulk concerns.
- New support progress file for Dropdown; `defaultTemplate()` as a row in
  support's component ledger.
- CLAUDE.md: support build-script description (bundled entry), first runtime
  JS dependency, Dropdown in the anonymous-components list.
- Roadmap: name the pulled-forward epic explicitly — Dropdown primitive +
  ActionGroup base — slotted ahead of the Section header/footer-actions epic.
- No rename-table entries: view→template is the recorded systematic
  translation.

## Explicitly deferred (tracked, not built)

Trigger views: badge, button-group, grouped, link. Submenu rendering.
Dropdown teleport and auto-placement. `toArray()`/`Arrayable`. Table/bulk
concerns (`BelongsToTable`, `hasNonBulkAction()`, visibility caching).
Translator-backed default label. Schemas-side integration (`prepareAction`/
`prepareActionGroup`, slot unions, `Schema::getAction()`) — that is the next
epic.
