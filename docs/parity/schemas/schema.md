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
  given an empty `Schema` instance). Related emptiness nuance to revisit with
  [live-component-state](../infrastructure.md#live-component-state): Filament's
  `is_slot_empty()` strips HTML comments (`<!-- -->` counts as empty), while
  our templates' `|trim is not empty` checks count them as content — it starts
  mattering once live-component markers render into slots.
- [ ] `HasInlineLabels` (`inlineLabel()`, cascading to fields).
- [ ] `HasComponents` traversal remainder: `getComponents(withHidden:)` (pairs
  with `CanBeHidden`) and `getComponent()`. `getFlatComponents()` is **ported**
  (depth-first flatten through each component's child schemas, added for the state
  walk; degrades to `getComponents()` for a flat schema);
  `getFlatFields()` / `getComponentByStatePath()` revisit with forms/state (T5).

Blocked on infrastructure:

- [ ] `components()` / the alignment factories accepting a single
  `Action | ActionGroup` value (with `getComponents()` seeding it via
  `modifyAction()` / `modifyActionGroup()`) — blocked on
  [actions-package](../infrastructure.md#actions-package). The
  `Component | string | Markup` coercion (single value or array) is **ported**:
  `components()` / `start()` / `end()` / `center()` / `between()` take that
  union, and `getComponents()` normalizes a string to [`Text`](text.md) and any
  other non-component to [`Html`](html.md).
- [ ] `CanModifyActions` (`modifyActionsUsing()` / `modifyActionGroupsUsing()`,
  applied in `getComponents()`) — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] `HasState` **partially ported** (T1 container substrate): `statePath()` /
  absolute `getStatePath()` composition, `rawState()`, `fill()`, `hydrateState()`,
  `fillStateWithNull()`, `callAfterStateHydrated()`, and a minimal flat `getState()`
  walking `getFlatComponents()` and skipping `!isDehydrated()` components. Still
  missing: the `#[PreDehydrate]`/`#[PostHydrate]` `afterStateUpdated` bridge + the
  framework `$oldState` LiveProp's reactive round-trip (T2), full `dehydrateState()`
  with shared/descendant paths + `mutateDehydratedState` (T4), and relationship
  loading (T6). `CanBeValidated`, `HasOperation`, `HasStateBindingModifiers`,
  `getLivewireKey()`, and the state-path key fallbacks remain blocked on
  [live-component-state](../infrastructure.md#live-component-state)

Revisit with later packages:

- [ ] `BelongsToModel` — Doctrine equivalent of Eloquent record binding;
  revisit when forms/infolists exist.
- [ ] `HasDefaultDataFormattingSettings` (eight defaultDateDisplayFormat-style
  setter/getter pairs) — consumers are the formatting components; revisit with
  infolists/tables.
- [ ] `HasEntryWrapper` — revisit with infolists.
- [ ] `HasFieldWrapper` — revisit with forms.

## Divergences

- **`getComponents()` preserves caller array keys** (Filament reindexes the
  list). Revisit when the `CanBeHidden` filter ports — filtering must not leave
  key gaps that Filament's reindexed output wouldn't have.
- **The `sy-grid-cols` marker class is emitted unconditionally**; Filament
  guards its equivalent behind `columns > 1`. Inert either way — the class has
  no CSS of its own on either side.
- **No schema-level `schema()` alias.** Filament aliases `schema($components)`
  → `components()` on the container as well; deliberately not ported — one
  canonical name on the container. (The component-level `components()` alias
  over `childComponents()` is ported.)
- **Schema-side fallbacks are `Schema` overrides.** Filament's schemas-level
  `HasGap::hasGap()` / `isDense()` and `HasColumns::getAllColumns()` resolve
  their Schema flavor behind `$this instanceof Schema` checks inside the shared
  trait; here the traits carry the component flavor (container delegation) and
  `Schema` overrides the getters (aliasing the trait's `getAllColumns` as its
  own-columns fallback). PHPStan cannot type the cross-hierarchy `instanceof`
  branches in trait context; the public API is identical.
- **`getState()` is a minimal flat assembler (T1).** It keys each dehydrated
  component by its **own** (relative) state path over one `getFlatComponents()`
  walk, and drives `hydrateState()` / `fillStateWithNull()` /
  `callAfterStateHydrated()` the same flattened way — where Filament recurses per
  component into child schemas. Same visitation set for flat and layout-nested
  (Section) schemas; correct nested-path assembly, prune, casts, conditions, and
  `mutateDehydratedState` land in T3/T4, and dynamic per-item repeater schemas
  (which the flatten can't enumerate) in T6.
