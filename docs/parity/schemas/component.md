# Component (schemas base class)

The abstract base every schemas component extends. Component files (e.g.
[section.md](section.md)) track only their own surface; the inherited surface
lives here. Ported so far: `BelongsToContainer`, `CanGrow`, `CanOrderColumns`,
`CanSpanColumns`, `HasChildComponents`, `HasColumns`, `HasExtraAttributes`,
`HasGap`, `HasHeadings`, `HasId`, `HasKey`, `HasStateBindingModifiers` — modulo
the items below.

Reference:

- [`vendor/filament/schemas/src/Components/Component.php`](../../../vendor/filament/schemas/src/Components/Component.php)
- [`vendor/filament/schemas/src/Components/Concerns/`](../../../vendor/filament/schemas/src/Components/Concerns)

## Missing

Portable now (no blocker):

- [ ] `CanBeHidden` **partially ported** (T4, dehydration is the first consumer):
  `hidden()` / `visible()` closures + `isHidden()` / `isVisible()` (the non-cached
  branch) landed so `dehydratedWhenHidden` can gate `isDehydrated()`. Still
  missing: `hiddenWhenAllChildComponentsHidden()`, the schema render loop skipping
  hidden children (and the paired `getComponents(withHidden:)` /
  `getChildSchemas(withHidden:)` filtering — a hidden component still renders
  today), the static visibility cache (`withVisibilityCache()`), `hiddenOn()` /
  `visibleOn()` (need the schema operation concept), and `whenTruthy()` /
  `whenFalsy()` + JS visibility (blocked on
  [live-component-state](../infrastructure.md#live-component-state)).
- [ ] `HasMeta` (`meta()`, `getMeta()`, `hasMeta()`).
- [ ] `Cloneable` (`getClone()`, `afterCloned()`; must flush the cached keys —
  `flushCachedAbsoluteKey()` / `flushCachedAbsoluteInheritanceKey()` — as
  Filament's does).
- [ ] `CanBeDisabled` (`disabled()`, `isDisabled()`, `isEnabled()`;
  `disabledOn()` has the same operation caveat as `hiddenOn()`).
- [ ] `HasMaxWidth` (`maxWidth()`; needs a support `Width` enum port).
- [ ] `HasInlineLabel` (`inlineLabel()`, cascading from the schema).
- [ ] `CanBeGridContainer` (`gridContainer()`).
- [ ] `CanBeLiberatedFromContainerGrid` (`liberatedFromContainerGrid()`).
- [ ] `HasChildComponents` gaps: `schema()` alias, `childComponents()`
  accepting single `Action | ActionGroup` values (the `Component | string |
  Markup` single-or-array coercion is **ported** — the string→`Text` /
  other→`Html` normalization runs in the child schema's `getComponents()`, see
  [schema.md](schema.md)), `getChildComponents()`,
  `getChildSchemas(withHidden:)`, the container-named aliases, and the
  keyed-slot `cachedChildSchemas` memoization (Filament caches non-closure
  slots; ours rebuilds per `getChildSchema()` call) together with the
  schema-level `clearCachedChildSchemas()` flush that pairs with it.
- [ ] `EvaluatesClosures::evaluationValueIsFunctionAndHasParameter()` — used
  by Filament's `CanOpenUrl` twins; port with the first of them.
Blocked on infrastructure:

- [ ] `HasActions` (`registerActions()`, `getAction()`, …) — blocked on
  [actions-package](../infrastructure.md#actions-package)
- [ ] `HasState` **partially ported** (T1 substrate + T2 reactive loop + T3 casts +
  T4 dehydration + T5 cast-aware `$get`/`$set` resolution):
  state paths (`statePath()`, absolute `getStatePath()`, `resolveRelativeStatePath()`),
  `getRawState()`/`rawState()`/`state()`/`getState()`, `default()`,
  `afterStateHydrated()`/`formatStateUsing()`, `hydrateState()`/`fillStateWithNull()`,
  the dehydration surface (`dehydrateState()` with shared-path / descendant-forget
  handling via `hasComponentWithStatePath()` / `getDescendantStatePathsToForget()`,
  `getStateToDehydrate()` + `dehydrateStateUsing()`, the `dehydrated()` /
  `saved()` / `dehydratedWhenHidden()` conditions feeding the full `isDehydrated()`
  chain, `beforeStateDehydrated()` / `callBeforeStateDehydrated()`, and
  `mutateDehydratedStateUsing()` / `mutateDehydratedState()` — T4),
  `makeGet/SetUtility()`,
  `afterStateUpdated()` + `clearAfterStateUpdatedHooks()` /
  `callAfterStateUpdatedHooks()` + `getOldState()`/`getOldRawState()` (the change
  bridge, fired from the host's `#[PostHydrate]` diff — see [schema.md](schema.md)),
  the state-cast pipeline (`stateCast()`, `getStateCasts()`/`getDefaultStateCasts()`,
  `hasCustomStateCasts()`, applied reverse-`set` in `state()`, forward-`get` in
  `getState()`/`getOldState()`, and a single forward-`set` pass in `hydrateState()`
  deduped by absolute path via `appliedStateCastPaths`; `BooleanStateCast` is the one
  concrete cast ported so far), and the
  `$state`/`$rawState`/`$get`/`$set`/`$liveComponent`/`$old`/`$oldRaw` closure
  parameters — `$state`/`$old` are now the **cast** values (the container half is in
  [schema.md](schema.md)); `$get`/`$set` resolve the owning **component** by state
  path (new `getComponentByStatePath()` on the schema `HasComponents`) and
  return/route its **cast** state, falling back to a raw bag read/write for a path
  owned by no component (T5). Still missing:
  relationship loading (T6). `CanPartiallyRender` and renderless-after-update do
  **not** port, and `CanPoll` → `data-poll` is **deferred** to a first-party host
  (T7 — see the divergences below); the per-child wrapper state features
  (`wire:key`, JS visibility, partial-render targeting — the grid-column wrapper
  itself is ported), `$operation`, and the state-dependent remainder of `HasKey`
  (state-path fallbacks, `getLivewireKey()`) remain blocked on
  [live-component-state](../infrastructure.md#live-component-state)
- [ ] `CanBeConcealed` (`isConcealed()`, `getConcealingComponent()`) — the
  `CanConcealComponents` contract it pairs with exists (Section implements it);
  the trait itself needs `getHiddenJs()`/`getVisibleJs()` from
  [live-component-state](../infrastructure.md#live-component-state).

Revisit with later packages:

- [ ] `BelongsToModel` (+ `BelongsToContainer::getModelRootContainer()`,
  `$record` / `$model` closure parameters) — Doctrine equivalent of Eloquent
  record binding; revisit when forms/infolists exist.
- [ ] `CanBeRepeated` — revisit with the forms `Repeater` / `Builder`.
- [ ] `HasEntryWrapper` — revisit with infolists.
- [ ] `HasFieldWrapper` — revisit with forms.
- [ ] Support-level `VerticalAlignment` enum + `HasVerticalAlignment` — first
  consumers are tables/`Flex`; revisit when those land.

## Divergences

- **Component-level `components()` union is widened** to
  `Schema|Component|string|Markup|array|Closure|null` — Filament omits `Schema`
  from `components()` (reserving it, with `null`, for `childComponents()`).
  Passing a `Schema` straight to `components()` is accepted convenience here; the
  `Component|string|Markup` single/array values are the ported coercion (Filament
  accepts those too, minus `Schema`).
- **Minimal attribute bag.** `HasExtraAttributes` folds entries through
  `Sylament\Support\Twig\ComponentAttributeBag` — a small value object with
  Laravel's merge-as-defaults semantics (class/style token lists append,
  defaults first; other existing keys win) instead of Filament's
  `Filament\Support\View\ComponentAttributeBag` extending Laravel's full bag.
  It normalizes `class`/`style` to token lists so templates can pass
  `getExtraAttributes()` straight into `attrs()` (the bag-backed `html_attr()`
  replacement), which merges them with other sources and escapes at render —
  hence no `escape:` parameter on `extraAttributes()` (Filament merges raw with
  `escape: false`). `View` → `Twig` follows the Laravel → Symfony translation
  conventions, not a rename.
- **No container-`make()` fallback in closure evaluation.** Filament's last
  resort for a class-typed closure parameter is Laravel's `app()->make()`,
  which auto-wires unbound classes; Symfony's container cannot instantiate ad
  hoc. After the resolver hooks and the identifier/self-type checks, we consult
  the DI container for **public services only** — on a compiled container
  `has()` answers `false` for private services, i.e. nearly every real app
  service (Twig, the entity manager, the translator) — then fall back to the
  declared default, then `null` for optional/nullable parameters, and otherwise
  throw. The planned sharpening (a curated `ServiceLocator` of injectable
  services) is recorded on the
  [translation](../infrastructure.md#translation) anchor.
- **`$get`/`$set` built with `new`, not the container.** Following the divergence
  above, `makeGetUtility()`/`makeSetUtility()` return `new Get($this)` /
  `new Set($this)`, where Filament resolves `app(Get::class, ['component' => $this])`.
  `Get`/`Set` now resolve the owning component via
  `getRootContainer()->getComponentByStatePath()` and return its cast `getState()`
  / route writes through cast `state()` + optional `afterStateUpdated`
  (`shouldCallUpdatedHooks`), keeping the T1 raw bag read/write as the fallback for a
  path owned by no component (T5). Minimal port — deferred until the components that
  exercise them exist: Filament's static *accumulating* skip-stack in `Get`
  (a single-element `[$this->component]` skip suffices absent computed-getter chains,
  and keeps both utilities `final readonly`), `getComponentByStatePath`'s `withHidden`
  / `withAbsoluteStatePath` params and result cache (our `getComponents()` /
  `getChildSchemas()` never prune hidden, callers always resolve to an absolute path
  first, and the O(components) walk needs no cache yet), and `clearCachedChildSchemas()`
  on the raw-write fallback (no repeaters/builders whose child schemas depend on
  state). `shouldCallUpdatedHooks` fires `callAfterStateUpdatedHooks()` only —
  Filament's fuller `callAfterStateUpdated()` (partial render, renderless,
  parent-bubble) has no `Set`-side equivalent: partial render and parent-bubble are
  the partial-render divergence, and renderless-after-update doesn't port (T7 —
  see below).
- **Closure results are type-checked, not coerced.** Filament wraps evaluated
  flags in `(bool)` casts (`CanBeCollapsed`, `HasGap`, `CanBeContained`, …);
  under `strict_types=1` we return the evaluated value against the declared
  native return type, so a closure returning `1` / `'yes'` TypeErrors here
  where Filament silently coerces. One policy across every trait — new ports
  drop Filament's casts rather than reintroducing coercion.
- **`getDataModel()` replaces Filament's `wire:model` emitter (T2).** Filament's
  `HasStateBindingModifiers` returns a Livewire modifier array
  (`getStateBindingModifiers()`) that `applyStateBindingModifiers()` joins onto
  `wire:model`; here `getDataModel()` emits the UX `data-model` string against the
  **absolute** state path — `norender|<path>` (not live: syncs, no re-render),
  `<path>` (live), `on(change)|<path>` (`lazy()`), `debounce(<n>)|<path>` — the
  grammar `live_controller.js` parses. `$entangle` fields have no UX analogue and
  are not ported (each complex field needs a bespoke Stimulus translation, flagged
  at the field ports). Container inheritance follows the `HasGap` shape (trait =
  component flavor, `Schema` overrides the getters), so both levels stay statically
  typed.
- **`afterStateUpdated` fires from a host `#[PostHydrate]` whole-bag diff (T2).**
  UX has no path-keyed `updated*` hook, so the host snapshots each bound
  component's raw state in `#[PreDehydrate]` (`$oldState` LiveProp) and diffs it
  after the next hydrate, firing each changed component's hooks with `$old`/`$oldRaw`
  injected. Consequences vs Filament's Livewire `callAfterStateUpdated()`: the
  single-pass walk visits each component once, so there is **no `store()`-backed
  dedup** and **no per-write parent-bubbling** — a field→sibling cascade fires only
  when the target is visited after the source in flat order (true bubbling pairs
  with nested layouts, T4); `$oldState` roughly doubles wire state (accepted).
- **No server-side partial render — and the reactivity features that fall out of it (T7).**
  `CanPartiallyRender::partiallyRenderComponentsAfterStateUpdated` has no UX
  equivalent: every round-trip re-renders the whole schema and client Idiomorph
  diffs (permanent divergence, design §3). Two adjacent Filament reactivity features
  collapse for the same reason (UX always re-renders the whole component per request)
  and were **deliberately not shipped** in T7:
  - **Renderless-after-update** (`skipRenderAfterStateUpdated` /
    `isRenderlessAfterStateUpdated`) means "run the update round-trip, skip the
    repaint." UX has no such mode; the only lever is the client `norender` model
    modifier, which suppresses the round-trip *entirely* (the hook then fires
    batched on the next request via the `#[PostHydrate]` diff). That is
    wire-identical to a plain non-live field, so the setter would be a redundant,
    misleading alias — a field that should sync without re-rendering simply omits
    `live()` (the default binding is already `norender|<path>`). Not ported.
  - **Polling** (`CanPoll` → `data-poll`) is **deferred**, not rejected. UX honors
    `data-poll` only on the live-controller root — the host's `#[AsLiveComponent]`
    template — and only as a whole-component `$render`, never Filament's
    per-component `wire:poll` + `partiallyRenderSchemaComponent(...)`. The schemas
    package renders *inside* that root and doesn't own it, so `poll()` cannot
    auto-emit until a first-party host/form base (forms/panels) owns the live
    template. A bring-your-own host meanwhile sets
    `data-poll="delay(<ms>)|$render"` on its own root directly (the native UX way).
    Revisit when that base lands.
- **State casts operate on the bespoke `$data` bag (T3).** The `StateCast`
  `set`/`get` pipeline transforms raw bag values ↔ typed application values with no
  Eloquent-casting analogue (design locked decision). Casts apply on **both** fill
  paths: `Schema::fill()` always runs `hydrateState()`, gated by an `applyDefaults`
  flag (`fill()` = defaults mode; `fill($state)` = provided-state mode, defaults
  skipped) — where T1 skipped `hydrateState()` entirely for provided state, so a
  provided-state fill now applies the `set` pass too. Default values are written
  **raw** (`rawState()`, not `state()`), so the single deduped `set` pass casts them
  exactly once.
- **Dehydration array ops are native `Arr` ports (T4).** Filament's `dehydrateState`
  addresses the assembled `$state` bag with Laravel `Arr::get`/`Arr::set`/`Arr::forget`
  (dot notation over a detached nested array); Laravel is banned in shipped code and
  Symfony PropertyAccess has no key removal, so `Schemas\State\PropertyAccessor`
  grows `getFromArray()` / `setInArray()` / `forgetFromArray()` — faithful ports
  (`set` clobbers a non-array intermediate to `[]`; `forget` unsets only the leaf and
  **leaves an emptied parent behind**, so the walk cleans up shared parents
  explicitly, exactly as Filament does). The dedicated `getStateToDehydrate()` seam
  applies casts `.get` + `dehydrateStateUsing`, where T1–T3 had dehydration read
  values through the component's cast-aware `getState()`.
