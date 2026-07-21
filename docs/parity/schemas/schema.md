# Schema (schemas container class)

The container class every component tree renders through. Component-level
surface lives in [component.md](component.md); this file tracks the `Schema`
class itself. Ported so far: `BelongsToLiveComponent`,
`BelongsToParentComponent`, `CanBeEmbeddedInParentComponent` (flag only — the
state-path rerouting lands with live-component-state), `CanBeInline`,
`HasAlignment` (+ the `start()` / `end()` / `center()` / `between()`
factories), `HasColumns`, `HasComponents`, `HasExtraAttributes`, `HasGap`,
`HasHeadings`, `HasKey` — modulo the items below.

Reference:

- [`vendor/filament/schemas/src/Schema.php`](../../../vendor/filament/schemas/src/Schema.php)
- [`vendor/filament/schemas/src/Concerns/`](../../../vendor/filament/schemas/src/Concerns)

## Missing

Portable now (no blocker):

- [ ] `Cloneable` (`getClone()`, `afterCloned()`; must flush the cached keys,
  like the component-level item in [component.md](component.md)).
- [ ] `CanBeHidden` / `CanBeDisabled` schema-level flags — including
  suppressing the render of an empty or all-hidden schema (Filament returns an
  empty string; ours still renders the root div, observable when a slot is
  given an empty `Schema` instance).
- [ ] `HasInlineLabels` (`inlineLabel()`, cascading to fields).
- [ ] `HasComponents` traversal remainder: `getComponents(withHidden:)` (pairs
  with `CanBeHidden`), `getFlatComponents()`, `getComponent()`;
  `getFlatFields()` / `getComponentByStatePath()` revisit with forms/state.

Blocked on infrastructure:

- [ ] `components()` / the alignment factories accepting a single
  `Component | Action | ActionGroup | string | Htmlable`, a `Schema`, or
  `null`, with `getComponents()` normalizing strings to `Text` and other
  non-components to `Html` — needs those schemas components ported;
  `Action | ActionGroup` values are blocked on
  [actions-package](../infrastructure.md#actions-package). Until then the
  unions are `array | Closure`.
- [ ] `CanModifyActions` (`modifyActionsUsing()` / `modifyActionGroupsUsing()`,
  applied in `getComponents()`) — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] `HasState`, `CanBeValidated`, `HasOperation`, `HasStateBindingModifiers`,
  `getLivewireKey()`, and the state-path key fallbacks — blocked on
  [live-component-state](../infrastructure.md#live-component-state)

Revisit with later packages:

- [ ] `BelongsToModel` — Doctrine equivalent of Eloquent record binding;
  revisit when forms/infolists exist.
- [ ] `HasEntryWrapper` — revisit with infolists.
- [ ] `HasFieldWrapper` — revisit with forms.
