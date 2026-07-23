# Live component state — epic discovery

> **Scope** is enumerated in [infrastructure.md#live-component-state](infrastructure.md#live-component-state);
> this file is the design workspace for that anchor and does not restate the port
> checklist. **Phase 1 (Discovery)**, then **Design + Decisions** (Phase 2), are
> below; the **Phase 3 task breakdown** lives in
> [live-state-tasks.md](live-state-tasks.md) (summarized under
> [Task plan](#task-plan-phase-3)).
>
> Discovery read Filament v5.7.1 (`vendor/filament/`), Symfony UX Live Components
> 3.3.0 (`vendor/symfony/ux-live-component/`), and the throwaway `origin/old`
> prototype. Every load-bearing claim is source-cited so Phase 2 can design
> without re-deriving. Reference roots:
> [`vendor/filament/schemas`](../../vendor/filament/schemas/src),
> [`vendor/symfony/ux-live-component`](../../vendor/symfony/ux-live-component/src),
> [`packages/schemas/src`](../../packages/schemas/src).

## The two models at a glance

| Dimension | Filament (Livewire) | UX Live Components 3.3 |
| --- | --- | --- |
| State store | the Livewire host object itself; `data_get/data_set($livewire, $path)` | a declared `#[LiveProp(writable: true)] public array $data` bag on the LiveComponent |
| Addressing | dotted **state paths** composed up the container chain | dotted `data-model` paths → PropertyAccess (dot→bracket) into the array prop |
| Arbitrary paths | `ResolvesDynamicLivewireProperties` resolves undeclared paths on the host | any interior key of a `writable: true` array is writable — no declaration needed |
| "field changed" hook | Livewire `updating*/updated*` convention, keyed by path, same request | no path-keyed hook; reconstruct by diffing old vs new bag |
| Reactivity trigger | `wire:model.live` → Livewire round-trip | `data-model` → debounced (~150ms) round-trip |
| Re-render grain | **server-side partial** (`PartialsComponentHook`, per-component key) | **whole component** re-rendered server-side; client Idiomorph diffs; only whole-*child* skipping |
| Complex fields | `$wire.$entangle('path')` for Alpine client-local reactivity | no `$entangle` analogue — a Stimulus controller or `norender` |
| Reach the evaluator | Laravel global `app()` | Sylament static-instance `Invoker::getInstance()` (already shipped) |

## Filament v5 — what we port

State lives in `vendor/filament/schemas/`, in **two** `HasState` traits that stay
distinct: component-level `schemas/src/Components/Concerns/HasState.php` (a leaf
field) and container-level `schemas/src/Concerns/HasState.php` (the `Schema`).

**Storage & addressing.** No store object — `getRawState()` is
`data_get($this->getLivewire(), $statePath)` (`Components/Concerns/HasState.php:957-965`)
and `rawState()` is `data_set(...)` (`:657-661`). `getLivewire()` resolves
component→container→host (`BelongsToContainer.php:63-66`, `BelongsToLivewire.php:25`).
Absolute path is memoized, composed as `container.statePath` + `.own` (`:719-740`);
relative grammar (`/` absolute, `../` parent-walk) in `resolveRelativeStatePath()`
(`:849`). A `Field` defaults its statePath to its name
(`forms/src/Components/Field.php:69-70`). Layout components (Section/Fieldset) set
**no** statePath → flat state; Repeater/Builder are fields → nested
`repeater.<item>.child`; item keys are sanitized (client-influenced).

**Lifecycle order.**
- Mount (`fill()`): `default()` → relationships → state-casts.set → `afterStateHydrated` (`formatStateUsing` is sugar over this hook, `:284`).
- Live update: property set → `updatingInteractsWithSchemas` (snapshot old) → mutate → `updatedInteractsWithSchemas` → `callAfterStateUpdated($path)` → hooks → partial render → bubble to parent (`:172-228`).
- Save (`getState()`): validate → mutate-for-validation → prune → `callBeforeStateDehydrated` → `dehydrateState` (casts.get + `dehydrateStateUsing`; `dehydrated(false)` drops the key) → `mutateDehydratedState` (`Concerns/HasState.php:450-491`).

**Reactivity.** `HasStateBindingModifiers`: `live(onBlur, debounce)`, `reactive()`
(alias), `lazy()` (=`live(onBlur: true)`), `debounce()` (`:21-44`), with container
inheritance when own `isLive` is null (`:126`). Emits the Livewire suffix
`wire:model.live[.blur][.debounce.Nms]` or flips `$entangle(...)` args (`:61-83`).
Server partial render is `CanPartiallyRender` → `PartialsComponentHook::renderPartial()`
keyed `schema-component::{key}` (`CanPartiallyRender.php:62`), `skipRender()` for renderless.

**`$get`/`$set` + injection.** `Get`/`Set` are `__invoke`-ables closing over the
component, resolving a path and reading `getState()` (cast-applied) or `data_get`
fallback (`Utilities/Get.php:23`, `Set.php:15`; `$set` does **not** fire updated-hooks
by default). Injected in `schemas/src/Components/Component.php`: by name
`get/set/state/rawState/livewire/record/model/operation` (`:85`), by type
`Get::class/Set::class/Model::class` (`:104`). Structurally identical to our
`resolveDefaultClosureDependencyForEvaluationBy{Name,Type}`.

**Livewire coupling (the replace-list).** Host implements `HasSchemas`, uses
`InteractsWithSchemas`. Must-replace touch-points: the `updating*/updated*` path
hooks (there is **no** `respondToBinding` symbol — this convention *is* the
mechanism, `:146-153`); `ResolvesDynamicLivewireProperties` (undeclared state paths
resolve on the host); `wire:model`/`$entangle` client bindings printed by PHP field
components; the `$wire.*` JS surface (`mountAction`, `callSchemaComponentMethod`,
`upload`); `PartialsComponentHook` morphing; validation piggybacking Livewire's
`validate()`; `store()` per-request dedup of after-update callbacks.

## UX Live Components 3.3 — what we build on

**Dependency.** Already a committed `require` of `packages/schemas`
(`symfony/ux-live-component: ^3.1`, `packages/schemas/composer.json:21`) — absent
from root and from `support`. The reactive layer therefore belongs in `schemas`
(or above); `support` stays free of it. The Phase-2 "add a dep" question is really
"confirm schemas is the home."

**The flat bag is the framework's own idiom.** `ComponentWithFormTrait::$formValues`
is literally `#[LiveProp(writable: true, fieldName: '...')] public array`
(`ComponentWithFormTrait.php:44-45`). Round-trip: dehydrate emits flat dotted keys
(`Util/DehydratedProps.php`); the frontend sends a 1-D dotted `updated` map
(`data.a.b => v`); hydrate accepts **every** interior path of an identity-writable
array with no enumeration (`LiveComponentHydrator.php:707-722`) and writes via
PropertyAccess with dot→bracket conversion (`:445-505`). Leaves must be JSON
scalars/arrays-of-scalars (`isValueValidDehydratedValue`).

**Constraints that bound the design.**
- **Whole-component re-render only.** Every request re-renders the entire component template server-side (`LiveComponentSubscriber.php:331-342`); the only diffing is client Idiomorph + coarse whole-*child*-component fingerprint skipping (`InterceptChildComponentRenderSubscriber.php`). No server-side per-field partial render.
- **Checksum freezes the top-level prop set** (HMAC over the read-only props, bound to component name + slot, `LiveComponentHydrator.php:410-443`). The frontend can only send `updated` for props that already exist and are writable — it cannot introduce a new top-level prop. Interior bag keys are free.
- **Reactivity = model change → debounce ~150ms → HTTP round-trip → full re-render** (`live_controller.js:1377`). No client reactive graph; `onUpdated` fires *during* the round-trip. Cross-component only via explicit `emit`/`emitUp`/`#[LiveListener]` (client-mediated).
- **Type rigidity:** union/intersection LiveProps throw (`LiveComponentMetadataFactory.php:83-85`); interface-typed values can't hydrate; non-scalar leaves need a hydration extension.

**Extension seams (build on these, in order).**
1. `Hydration/HydrationExtensionInterface` (tagged, auto-configured) — teach the hydrator a custom domain object ↔ scalar/array. The clean public seam (hydrator is `final @internal`; `DoctrineEntityHydrationExtension` is the reference impl).
2. Per-prop `hydrateWith`/`dehydrateWith`, `useSerializerForHydration`, `format`.
3. Lifecycle hooks `#[PreDehydrate]` / `#[PostHydrate]` / `#[PreReRender]`.
4. `modifier` — per-request mutation of a prop's `LiveProp` config (e.g. compute writable paths).
5. Decorate/replace the `ux.live_component.component_hydrator` service for anything deeper.

**Poll / renderless map cleanly:** `data-poll` (default action `$render`, 2000ms) ≈
`CanPoll`; the `norender` model modifier + not-triggering-render ≈
`skipRenderAfterStateUpdated`.

**`ComponentWithFormTrait` (the alternative substrate).** Rides Symfony's Form
component: `instantiateForm()`, the `formValues` writable bag, `extractFormValues()`
mirroring a native submit, auto-submit via `#[PreReRender]`, `getDataModelValue()`
default `on(change)|*`, validation via `ValidatableComponentTrait` + Symfony
Validator. Filament uses neither Symfony Forms nor per-field props — so leaning on
this trait buys validation/binding for free at the cost of adopting Symfony's Form
model instead of Filament's schema-owns-state model. **This is the core Phase-2 fork.**

## `origin/old` prototype — prior art

Throwaway branch, tip `556f55f`, **no shared history with main** — mine for shape,
merge nothing.

**Salvage (Filament-faithful, carry forward):** the closure param set + names
`$get/$set/$state/$rawState/$component/$liveComponent`; the state-path grammar
(`/` absolute, `../` parent-walk, memoized absolute composition); `Get`/`Set`
invokables + `makeGet/SetUtility()`; `HasStateBindingModifiers` with `getDataModel()`
→ UX `data-model` strings (`norender|…`, `on(change)|…`, `debounce(n)|…` — verify
grammar against 3.3 before trusting); the `#[LiveProp] $oldState` + `#[PreDehydrate]`
snapshot + `#[PostHydrate]` diff-and-fire bridge for `afterStateUpdated`.

**Must avoid (the dead ends):**
- **`#[Required]` setter-injecting the `Invoker`/`PropertyAccessor`** into components — fires only for container-built objects, but components are `make()` → `new static()`, so it stayed uninitialized and forced hand-threading `->setInvoker()->setPropertyAccessor()` through every `makeSchema()`/`getComponents()` site. **Main already solved this** with the static-instance pull (`Invoker::getInstance()`) and deleted the threading. If the state layer needs a `PropertyAccessor` service, give *it* the `StaticInstanceInterface` treatment — never thread services through construction.
- `is_callable()` gate (invokes plain strings like `'date'`) — main narrowed to `instanceof Closure`; keep that.
- Naming a `#[PostHydrate]` hook and a fluent setter both `afterStateUpdated()` — collided confusingly; keep distinct names.
- The bespoke recursive `PropertyAccessor` — re-evaluate vs configuring Symfony's; never ship the setter-injected form.

## Current graft surface (what's on main)

Main carries the LiveComponent *plumbing*, none of the *state*:
- `Schemas\Schema` (`packages/schemas/src/Schema.php`) — `use BelongsToLiveComponent`, ctor `__construct(?InteractsWithSchemas $liveComponent)`, `make(?InteractsWithSchemas)`, `evaluationIdentifier = 'schema'`, and **already** resolves `container`/`liveComponent` closure params (`:59-66`). Schema-level state additions extend this match.
- `Schemas\Components\Component` (`packages/schemas/src/Components/Component.php`) — `use BelongsToContainer` (reaches the host via `getContainer()`→Schema→`getLiveComponent()`), `HasKey`, `evaluationIdentifier = 'component'`. It has **no** `resolveDefaultClosureDependencyForEvaluationByName` override yet — the `get/set/state/rawState` arms are added here as a new override (`$component` already comes from `evaluationIdentifier`).
- `Contracts\InteractsWithSchemas` is the host contract (the `HasSchemas`/Livewire-host analogue), injected at `Schema::make($host)`.
- A grep for `HasState|statePath|getDataModel|oldState|PreDehydrate` on main's schemas src returns nothing — the epic is **additive** onto these classes, not a rewrite.

Key-system tie-ins (tracked under the infra anchor, not here): state-path fallbacks
in `HasKey::getKey()`/`getInheritanceKey()`, `getLivewireKey()` needing a
live-component id, Section's `::{statePath}::` auto-key segment.

## Gap analysis (Filament ↔ UX 3.3)

1. **The state bag.** Filament addresses the *host object* by path (`data_get/data_set($livewire, $path)`) and leans on `ResolvesDynamicLivewireProperties` so undeclared paths resolve. → UX requires a declared `#[LiveProp(writable: true)] public array $data`; any interior key is writable, no dynamic-property shim. **Implication:** the port *simplifies* here — one writable array prop replaces host-object-as-bag + dynamic-property machinery. Reads/writes become PropertyAccess on that array, not `data_get` on the host.

2. **"Field changed" detection ("respondToBinding").** Filament gets Livewire's path-keyed `updating*/updated*` (old captured pre-write, hooks fire post-write, same request). → UX has no path-keyed hook. **Implication:** two options — (a) the bag's `onUpdated` LiveProp hook, which supports a keyed-by-path form (`['@identity' => 'm', 'sub' => 'm2']`, old value passed, same request) but only for **statically-declared** keys; or (b) the prototype's `#[PreDehydrate]` snapshot + `#[PostHydrate]` whole-bag diff, which covers **dynamic** nested paths (repeater items) that `onUpdated` keys can't name (old captured end of request N, diffed start of N+1). Dynamic paths likely force (b) or a hybrid. The single most intricate mechanic to port.

3. **Partial rendering — the significant divergence.** Filament server-renders just the changed component (`PartialsComponentHook`, keyed by component key). → UX has no server-side partial; every round-trip re-renders the whole component, client Idiomorph minimizes DOM churn, only whole *child components* are skipped by fingerprint. **Implication:** `CanPartiallyRender::partiallyRenderComponentsAfterStateUpdated` cannot port 1:1. Default port = whole-component re-render + Idiomorph. Per-region partial render, if truly needed, means promoting the region to a child live component (coarse, changes architecture). Record as a permanent divergence. `skipRender`/renderless **does** port (`norender` / suppress re-render).

4. **Closure injection — near-zero gap.** Filament's by-name/by-type match is structurally identical to ours; Schema already resolves `container`/`liveComponent`. **Implication:** add `get/set/state/rawState` arms to schemas `Component` (and `liveComponent` where the component itself needs it), construct `Get`/`Set` via `new Get($this)` — **not** `app()->make()`; Sylament deliberately omits container auto-make, so Filament's `app(Get::class, [...])` is the one line not to copy. Rename `$livewire`→`$liveComponent`.

5. **State-path grammar — near-zero gap.** Sylament's own grammar; only the resolution target changes (address the `$data` array prop via PropertyAccess, not the host via `data_get`). The prototype already ported it faithfully.

6. **`wire:model` vs `$entangle` fields.** Filament splits fields: simple ones use `wire:model[.live]` (round-trip); complex ones (Toggle, Slider, RichEditor, FileUpload…) use `$wire.$entangle('path')` for Alpine client-local reactivity without a round-trip. → UX `data-model` is the `wire:model` analogue; there is **no** `$entangle` equivalent — client-local two-way state is a Stimulus controller reading/writing the model, or `norender`. **Implication:** `wire:model` fields port straightforwardly via `getDataModel()`; each `$entangle` field needs a bespoke Stimulus translation. Downstream (individual field ports), flagged now.

7. **Validation.** Filament piggybacks Livewire's `validate()`, `InteractsWithSchemas` merging schema rules. → UX offers `ValidatableComponentTrait` + Symfony Validator, or `ComponentWithFormTrait` → Symfony Form validation. **Implication:** ties into the substrate fork below. Out of early scope (Filament's `CanBeValidated` is separate and no form fields are ported yet) — note and defer.

8. **Reach-the-evaluator / `make()` construction.** Already solved on main (static-instance) vs the prototype's dead end. Not a gap — a settled constraint the port must honor.

## Design

**Substrate — decided at the Phase 2 gate: the bespoke `$data` bag.** We reuse UX's
LiveProp *primitive* (`#[LiveProp(writable: true)] public array $data`) but not
`ComponentWithFormTrait` — the schema owns state, there is no Symfony Form.
`ComponentWithFormTrait` was rejected because it is Form-first: it requires
`instantiateForm(): FormInterface` and auto-submits the bag into a `Form` every
render (`ComponentWithFormTrait.php:70,109-115,150-191`), which has no place in
Filament's model. Validation is ported later, onto Symfony Validator directly.

### Topology — one live component per schema (monolithic host)

The host is an `#[AsLiveComponent]` implementing `Contracts\InteractsWithSchemas`,
using `Concerns\HasSchemas`. The host **declares its own** state as
`#[LiveProp(writable: true)]` properties — a `data` array bag, individual typed
props, or one bag per form — and each schema-defining method binds to them via
`->statePath(...)`. The trait declares no state prop, so one host can carry several
independently-bound schemas without collision; it *does* own the framework
`$oldState` snapshot in T2 (a `#[LiveProp]` declared in a trait is picked up —
`ComponentWithFormTrait` proves it). Every round-trip re-renders the whole schema;
client Idiomorph diffs the result. Filament forms are likewise one Livewire
component — only Filament's server-side *sub-region* partial render is lost
(Decisions §3).

### Two hydration layers — never conflate

1. **UX LiveProp (de)hydration** of `$data` — automatic per-request wire serialization. We do not touch it.
2. **Filament schema hydrate/dehydrate** — our port, layered on the bag:
   - `fill($record?)` at mount: `default()` → (relationships, later) → state-casts.set → `afterStateHydrated` (`formatStateUsing` is sugar over this).
   - `getState()` at submit: (validate, later) → prune → (`beforeStateDehydrated`, later) → `dehydrateState` (state-casts.get + `dehydrateStateUsing`; `dehydrated(false)` drops the key) → `mutateDehydratedState`.

### State addressing

`Component::getRawState()`/`rawState()` walk `getContainer()` (Schema) →
`getLiveComponent()` (host) → **configured static Symfony PropertyAccess** into the
host's state property (e.g. `$host->data`) at the field's absolute state path
(dot→bracket, null-tolerant read, auto-vivifying write — the same conversion UX does
in `adjustPropertyPathForData`). Add `Component::getLiveComponent()` =
`getContainer()->getLiveComponent()` (mirrors Filament
`BelongsToContainer::getLivewire()`). The state-path grammar ports as-is (`/`
absolute, `../` parent-walk, memoized absolute composition). Each schema-defining
method on the host sets its own **statePath** (`->statePath('data')` for a bag, or
none for top-level binding — Filament's `getFormStatePath()` defaults to `null`), so
absolute paths compose from whatever the host chose and bind verbatim as
`data-model`.

### Reactivity → `data-model`

`Components\Concerns\HasStateBindingModifiers`: `live(onBlur, debounce)` / `reactive()`
/ `lazy()` / `debounce()` set flags with container inheritance when own `isLive` is
null. `getDataModel()` emits the field's `data-model`: `norender|<path>` when not live
(sync bag, no re-render — Filament's "syncs but not reactive until submit"), `<path>`
when live, `on(change)|<path>` on blur, `debounce(n)|<path>` when debounced. **Verify
the exact modifier grammar against ux-live-component 3.3 at build** — `live_controller.js`
parses these; discovery cited them but confirm before shipping `getDataModel()`.

### `afterStateUpdated` bridge

Host: `#[LiveProp] public array $oldState = []`; `#[PreDehydrate]` snapshots each flat
component's raw state at end of render N; `#[PostHydrate]` diffs against current state
at start of N+1 and evaluates changed components' `afterStateUpdated` closures with
`$old`/`$oldRaw` injected, bubbling to parents. Forced by the finding that identity
`onUpdated` does not fire on interior `data.*` writes (Decisions §2). Keep the host
hook and the fluent setter **distinctly named** (the prototype collided both on
`afterStateUpdated`) — e.g. host `#[PostHydrate] callAfterSchemaStateUpdated()` vs
component `afterStateUpdated(?Closure)`.

### Closure parameters

New `Component::resolveDefaultClosureDependencyForEvaluationByName` override adds
`get`/`set`/`state`/`rawState` (and `liveComponent`, reached via the container);
`Get`/`Set` are `__invoke`-ables built with `new Get($this)` — **not** `app()->make()`
(Sylament's deliberate no-auto-make divergence; Filament's `app(Get::class, [...])`
is the one line not to copy). `$component` already resolves via `evaluationIdentifier`;
`Schema` already resolves `container`/`liveComponent`.

### Graft map (all additive on main)

| Add to | What |
| --- | --- |
| `Concerns\HasSchemas` (host) | `fill()`; `$oldState` + `#[PreDehydrate]`/`#[PostHydrate]` hooks (framework state, T2). **No** `$data`/statePath baked in — the host declares its own state props and each schema method binds via `->statePath(...)` |
| `Components\Concerns\HasState` (new) | state paths, get/set raw+cast state, `default`/`afterStateHydrated`/`afterStateUpdated`, `dehydrateState`/`mutateDehydratedState`, state casts |
| `Components\Concerns\HasStateBindingModifiers` (new) | `live`/`reactive`/`lazy`/`debounce`, `getDataModel()` |
| `Components\Component` | compose the two new traits; `getLiveComponent()`; `resolve…ByName` state arms |
| `Concerns\HasState` (schema, new) | container-level `fill`/`getState`/hydrate/dehydrate walk over `getFlatComponents()` |
| `Components\Utilities\Get` / `Set` (new) | invokable path read/write over the bag |

## Decisions

1. **Substrate = `#[LiveProp(writable: true)]` state the host declares** (user-approved at the Phase 2 gate; the framework does **not** bake a `$data` prop or a fixed statePath into `HasSchemas` — corrected during T1 to match Filament, whose `getFormStatePath()` defaults to `null`). Reuse the LiveProp primitive, not `ComponentWithFormTrait`; the host owns the state shape (one `data` bag, individual typed props, or one bag per form) and binds each schema via `->statePath(...)`; the schema owns hydrate/dehydrate; no Symfony Form.
2. **`afterStateUpdated` capture = `#[PreDehydrate]`/`#[PostHydrate]` diff**, not `onUpdated`. Verified: identity `onUpdated` fires only on whole-array replacement (`LiveComponentHydrator.php:774-777`) and keyed `onUpdated` needs static paths (`:790-797`) — neither covers a dynamic bag. Accepted cost: `$oldState` roughly doubles wire state.
3. **Topology = monolithic host + whole-schema re-render + Idiomorph.** `CanPartiallyRender` becomes a permanent divergence (UX has no server-side sub-region render). Nested per-component live components stay a *future* opt-in for heavy components (repeaters) — they would fracture cross-component `$get('../x')` addressing, so not free to retrofit, but not worth base-layer complexity now.
4. **PropertyAccessor = configured static Symfony PropertyAccess** (null-tolerant read, auto-vivify write). No bespoke recursive accessor, no service threading.
5. **Evaluator reachability = `Invoker::getInstance()`** (main's static-instance pattern). The prototype's `#[Required]` setter injection stays rejected.
6. **v1 scope = a thin vertical slice first** — fill → `data-model` bind → change → `afterStateUpdated` → `getState`, browser-verified on a fixture host — then layer state casts, relationship loading, and `mutateDehydrated`.

## Divergences (migrate into schemas/component.md when code lands)

- **No server-side partial render.** `CanPartiallyRender::partiallyRenderComponentsAfterStateUpdated` has no UX equivalent; the port re-renders the whole schema and lets Idiomorph diff. `skipRender`/renderless does port (suppress re-render / `norender`).
- **No `app()->make()` auto-wiring** of `Get`/`Set` (already a recorded Sylament divergence for closure evaluation).

## Task plan (Phase 3)

The full, cold-session-executable briefs live in
[live-state-tasks.md](live-state-tasks.md) (checkbox per task). Vertical-slice
first (Decision §6): **T1 → T2** prove the whole loop before anything is layered.

1. **T1 — State substrate (deterministic).** The `$data` bag, absolute/relative
   state paths, static Symfony PropertyAccess read/write, `Get`/`Set`, the
   `get/set/state/rawState/liveComponent` closure arms, `fill()`/`default()`/
   `afterStateHydrated`, minimal `getState()`. Pest-only, no live round-trip.
2. **T2 — Live loop + `afterStateUpdated` bridge.** `live/reactive/lazy/debounce`
   + `getDataModel()`, `$oldState` `#[PreDehydrate]`/`#[PostHydrate]` diff-fire,
   fixture live host + field. Verified with the UX live-component harness + a
   browser drive.
3. **T3 — State casts.** `stateCast()` + `StateCast` contract, applied on
   hydrate/dehydrate.
4. **T4 — Dehydration correctness, conditions & mutation.** Full `dehydrateState`
   (shared/descendant paths), `dehydrated`/`saved`/`dehydratedWhenHidden`,
   `beforeStateDehydrated`, `mutateDehydratedState`.
5. **T5 — `$get`/`$set` component resolution.** Cast-aware, component-by-path,
   child-container skipping.
6. **T6 — Relationship state loading (Doctrine).** `CanGetStateFromRelationships`
   translated Eloquent → Doctrine.
7. **T7 — Renderless / poll / partial-render divergence.** `norender`/`skipRender`,
   `CanPoll` → `data-poll`; record the permanent `CanPartiallyRender` divergence.

Non-goals (record, don't build): validation, `$entangle` client-local fields,
server-side per-region partial render.

## Renames to record when code lands

- Filament `$livewire` closure param → Sylament `$liveComponent` (host is `InteractsWithSchemas`, not a Livewire component).
- Filament `getLivewire()` / `BelongsToLivewire` → Sylament `getLiveComponent()` / `BelongsToLiveComponent` (already on main).
- Filament interface `HasSchemas` → Sylament `Contracts\InteractsWithSchemas`; Filament trait `InteractsWithSchemas` → Sylament `Concerns\HasSchemas` (names inverted vs Filament; already on main).
