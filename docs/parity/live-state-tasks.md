# Live component state — Phase 3 task plan

> Task breakdown for the epic designed in [live-state.md](live-state.md). The
> Design, Decisions, Divergences, Renames, and graft map in that file are
> **settled** (substrate = bespoke `$data` bag, per Decision §1) — these briefs
> execute it, they do not re-open it. Scope anchor:
> [infrastructure.md#live-component-state](infrastructure.md#live-component-state).
>
> Each brief is self-contained for a cold session: goal, the vendor/prototype
> source to port from, the exact current-`main` files to touch, the locked
> decisions, and acceptance criteria (including the DoD gates). Honor the
> [Shared conventions](#shared-conventions) once; briefs don't restate them.

## Sequencing

Vertical-slice-first (Decision §6): **T1 → T2** prove the whole loop
(`fill` → `data-model` bind → change → `afterStateUpdated` → `getState`) before
anything is layered on. T1 is the deterministic PHP substrate (Pest-only, no
kernel-live round-trip); T2 adds the live/reactive loop and browser proof. Only
then do the layering tasks land.

```
T1 substrate ──▶ T2 live loop ──▶ T7 renderless/poll
     │                └────────────▶ T5 $get/$set resolution
     ├──▶ T3 state casts ──▶ T6 relationship loading
     └──▶ T4 dehydration correctness + mutate
```

- **T1** blocks everything.
- **T2** needs T1's paths/state + the host bag.
- **T3 / T4** need only T1 and can proceed in parallel with T2.
- **T5** needs T1 (extends `Get`/`Set`) and reads cleanest after T2 (bubbling).
- **T6** needs T3 (casts feed relationship hydration). Doctrine-heavy; latest.
- **T7** needs T2 (`getDataModel` / re-render suppression).

**Non-goals for this epic** (record, don't build): validation (design §7 —
`ValidatableComponentTrait`/Symfony Validator is a separate epic); `$entangle`
client-local fields (design §6 — each needs a bespoke Stimulus translation,
flagged at the individual field ports); server-side per-region partial render
(Decision §3 — permanent divergence, T7 records it).

## Shared conventions

Every task must satisfy these (from [CLAUDE.md](../../CLAUDE.md)); they are not
repeated per brief.

- **DoD gates:** `composer test` green (Pest + Rector dry-run + PHPStan **level
  max**) and `pnpm test:types` + `pnpm test:lint` green. Run `composer format`
  (Rector + PHP-CS-Fixer) — never hand-format. No PHPStan baseline / ignore
  unless unavoidable (`reportUnmatchedIgnoredErrors` is on).
- **Closure typing:** setters take native `Closure` unions, direct assignment,
  no param PHPDoc, no arity specs — e.g. `afterStateUpdated(?Closure $callback)`.
  Props carry native types only; `evaluate()` supplies the generics. The
  `resolveDefaultClosureDependencyForEvaluationBy{Name,Type}` overrides return
  **`?ClosureParameter`** (Sylament shape — `ClosureParameter::make($v)` or
  `parent::…`), **not** Filament's `array` wrapper.
- **No `app()->make()`:** construct utilities with `new Get($this)` / `new
  Set($this)`. Filament's `app(Get::class, ['component' => $this])`
  (`Components/Concerns/HasState.php:823-831`) is the one line **not** to copy.
- **No service threading:** reach shared services via the static-instance
  pattern (`::getInstance()`), never via constructor/`#[Required]` injection
  into `make()`-built components. This is the prototype's headline dead end.
- **`instanceof Closure` gate only** (never `is_callable()` — main already
  narrowed this; a literal string like `'date'` is a value, never invoked).
- **Test layout:** Pest; mirror `src/` under `tests/Unit` (no kernel) and
  `tests/Integration` (kernel/container); `Fixtures/` mirrors `src/`. Inside
  `it()`/`test()` reach static helpers via `$this::`, never `self::`/`static::`.
  Don't call `bootKernel()` explicitly; `$this::getContainer()` auto-boots. Add
  fixtures by dropping classes under `tests/Fixtures/…`, never by editing the
  Kernel.
- **Parity docs in the same change:** migrate the design's
  [Divergences](live-state.md#divergences-migrate-into-schemascomponentmd-when-code-lands)
  into [schemas/component.md](schemas/component.md) /
  [schemas/schema.md](schemas/schema.md), the
  [Renames](live-state.md#renames-to-record-when-code-lands) into
  [renames.md](renames.md), and tick the relevant scope items under the
  [infrastructure anchor](infrastructure.md#live-component-state) as they land.
- **Self-review pass** (>~30 lines / >2 files): run static analysis + tests,
  re-read each modified file end-to-end, grep callers of any changed signature,
  report findings before declaring done.

---

## T1 — State substrate (deterministic)

- [x] **T1** — the `$data` bag, addressing, read/write, `Get`/`Set`, closure
  arms, and `fill()` → `getState()` round-trip, verified in Pest with **no**
  live round-trip or browser.

**Goal.** Give schema components a working state layer over a bespoke
`#[LiveProp(writable: true)] public array $data` bag: absolute/relative state
paths, raw/typed read + write through a **static Symfony PropertyAccess** helper,
`$get`/`$set` utilities, the `get/set/state/rawState/liveComponent` closure
arms, and `fill()`/`default()`/`afterStateHydrated` + a minimal `getState()`
dehydrate walk. This is the substrate every later task extends.

**Port from.**
- Filament component state: `vendor/filament/schemas/src/Components/Concerns/HasState.php`
  — `getRawState()` :957, `rawState()` :657, `getStatePath()` :719,
  `resolveRelativeStatePath()` :849, `getState()` :946, `state()` :646,
  `default()`/`getDefaultState()` :248/:680, `afterStateHydrated()` +
  `callAfterStateHydrated()` :112/:163, `formatStateUsing()` :284,
  `fillStateWithNull()` :583, `hasStatePath()` :765, `isDehydrated()`/`isSaved()`
  :775/:791, `getStateToDehydrate()` :294, `makeGetUtility/makeSetUtility` :823.
- Filament container state: `vendor/filament/schemas/src/Concerns/HasState.php`
  — `fill()` :324, `rawState()` :43, `getRawState()` :539, `getStatePath()` :562,
  `callAfterStateHydrated()` :96, `hydrateState()` :374, `fillStateWithNull()` :399,
  `dehydrateState()` :205, `getState()` :450 (port **without** `validate()`/
  relationships — a minimal prune-free assembler for now).
- Filament injection shape: `vendor/filament/schemas/src/Components/Component.php`
  :85 (by-name) / :104 (by-type) — translate to the `?ClosureParameter` return.
- **Prototype salvage** (faithful, carry forward — but see avoid-list):
  `git show origin/old:packages/schemas/src/Components/Concerns/HasState.php`
  (path grammar, `getRawState`/`rawState` via accessor, `new Get($this)`),
  `…/Components/Utilities/Get.php` + `Set.php` (simple path read/write over the
  bag), `…/Components/Component.php` (the exact `?ClosureParameter` arm block),
  `…/Concerns/HasSchemas.php` (`makeSchema`, cached schemas), `…/Schema.php::fill()`.
- **Prototype avoid-list** (design §"Must avoid"): do **not** port
  `Sylament\Support\Concerns\AccessesProperties` (the `#[Required]`
  setter-injection) or the bespoke recursive `Sylament\Support\Services\PropertyAccessor`
  — replace both with the static-instance Symfony accessor below.
- UX dot→bracket reference: `vendor/symfony/ux-live-component/src/LiveComponentHydrator.php:445`
  (`adjustPropertyPathForData`). `#[LiveProp]`-in-a-trait precedent:
  `vendor/symfony/ux-live-component/src/ComponentWithFormTrait.php:44` (`public array $formValues`).

**Touch (current `main`).**
- **New** `packages/schemas/src/State/PropertyAccessor.php` (recommended name/home):
  `implements StaticInstanceInterface` + `use HasStaticInstance`, wraps the
  injected framework `PropertyAccessorInterface` (`property_accessor` service,
  aliased at `vendor/symfony/framework-bundle/Resources/config/property_access.php:30`).
  Public `get(object $host, string $dottedPath): mixed` and
  `set(object $host, string $dottedPath, mixed $value): void`, converting the
  dotted absolute path to a Symfony property path (first segment = the `data`
  property, remaining segments = `[...]` array indices, exactly the
  `adjustPropertyPathForData` shape). Pattern precedents to mirror:
  `packages/support/src/Icons/IconManager.php`,
  `packages/support/src/Service/Invoker.php`,
  `packages/support/src/DependencyInjection/HasStaticInstance.php`.
  **Verify against the installed `symfony/property-access`** (no-guessing rule):
  default `createPropertyAccessor()` returns `null` on a missing array index
  (null-tolerant read ✓) and `setValue` auto-vivifies nested arrays (✓);
  confirm both before relying on them.
- **New** `packages/schemas/src/Components/Concerns/HasState.php` — the
  component-level trait (minimal subset above). No casts, no relationships, no
  `mutateDehydratedState`, no shared-path/`Arr::forget` dehydrate (all deferred).
- **New** `packages/schemas/src/Concerns/HasState.php` — the schema
  (container-level) trait: `statePath()`, `getStatePath(isAbsolute)` +
  cached absolute composition + `flushCachedAbsoluteStatePath()`, `getRawState()`,
  `rawState()`, `callAfterStateHydrated()`, `hydrateState()`, `fillStateWithNull()`,
  `fill()`, and a **minimal** `getState()` (assemble the dehydrated bag by
  walking `getFlatComponents()`, skipping `!isDehydrated()` components — no
  validate/prune/casts/mutate yet).
- **New** `packages/schemas/src/Components/Utilities/Get.php` + `Set.php` —
  prototype-shaped: `resolveRelativeStatePath()` then accessor read/write over
  the host bag (`new Get($this)` / `new Set($this)`). The cast-aware,
  component-resolving Filament form is **T5** — keep these simple here.
- `packages/schemas/src/Components/Component.php` — `use` both new component
  traits; add `resolveDefaultClosureDependencyForEvaluationByName()` returning
  `?ClosureParameter` for `get`/`set`/`state`/`rawState`/`liveComponent`
  (`$component` already resolves via `evaluationIdentifier`; `getLiveComponent()`
  already exists on `BelongsToContainer:41`).
- `packages/schemas/src/Schema.php` — `use` the new schema `HasState`.
- `packages/schemas/src/Concerns/HasComponents.php` — add
  `getFlatComponents(): array` (recurse `getComponents()` + each component's
  child schemas). For a flat schema it degrades to `getComponents()`.
- `packages/schemas/src/Components/Concerns/HasChildComponents.php` — add a
  small `getChildSchemas(): array` (all keys) if `getFlatComponents` needs it;
  keep minimal.
- `packages/schemas/src/Concerns/HasSchemas.php` (host) — add `fill(?array $state
  = null)` delegating to the default schema. Do **not** declare a `$data` prop or
  seed a statePath here (corrected during T1): the host declares its own
  `#[LiveProp(writable: true)]` state — a `data` bag, individual typed props, or
  one bag per form — and each schema-defining method binds via `->statePath(...)`
  (default none = top-level binding, mirroring Filament's `getFormStatePath()` →
  `null`, `forms/src/Concerns/InteractsWithForms.php:182`). Keeping the shape off
  the trait is what lets one host carry several independently-bound schemas.
- `packages/schemas/src/SylamentSchemasBundle.php` — register the accessor
  service in `loadExtension()` (so `StaticInstancePass` auto-tags it and
  support's `StaticInstanceRegistry::bootStaticInstances()` sets its instance —
  cross-package tagging works; verify it boots).
- `packages/schemas/composer.json` — add `symfony/property-access` to `require`.
  ⚠️ **Dependency-declaration checkpoint** (global "never add a dep without
  asking" rule): it is already resolved transitively via the existing
  `symfony/ux-live-component` require, so this only makes the direct use
  explicit — confirm with the user before committing the constraint. After
  editing, run `composer update sylament/schemas` (not `dump-autoload` — path
  packages read stale lock metadata otherwise).

**Locked decisions.** Substrate = `$data` bag (§1). Accessor = configured static
Symfony PropertyAccess, null-tolerant read / auto-vivify write, no bespoke
recursion, no threading (§4). Evaluator/accessor reachability via `::getInstance()`
(§5). Rename `$livewire`→`$liveComponent` (already on `main`).

**Acceptance criteria.**
- Fixture host under `packages/schemas/tests/Fixtures/` implementing
  `Contracts\InteractsWithSchemas` + `use Concerns\HasSchemas`, **declaring its own**
  `#[LiveProp(writable: true)] public array $data` and a `schema()` that binds it
  with `->statePath('data')` and returns a couple of minimal fixture leaf
  components (each `statePath = name`, under `tests/Fixtures/Components/…`). Add a
  second fixture with two schemas on distinct bags to prove multi-form isolation.
  No `#[AsLiveComponent]` needed for T1 (deterministic — a plain object exercises
  the substrate).
- **Unit** tests (`tests/Unit/Components/Concerns/HasStateTest.php`,
  `tests/Unit/Concerns/HasStateTest.php`, `tests/Unit/State/PropertyAccessorTest.php`,
  `tests/Unit/Components/Utilities/{Get,Set}Test.php`) install the accessor
  static instance in the Unit Pest hook (mirror the existing `Invoker` install
  in `packages/schemas/tests/Pest.php`), and prove:
  - `$host->fill(['title' => 'Hi'])` ⇒ `$host->data['title'] === 'Hi'`;
    unset leaves become `null` via `fillStateWithNull()`.
  - component `getRawState()`/`getState()` returns the bag value at its path;
    nested paths (`data.address.city`) read/write correctly (dot→bracket).
  - injected `$set('title', 'Bye')` then `$get('title')` ⇒ `'Bye'`;
    `$get('/data.title', isAbsolute)` and `../` relative forms resolve.
  - `default('X')` + `fill(null)` ⇒ component state `'X'`; an
    `afterStateHydrated(fn ($state, $component) => …)` closure fires during
    `fill()` and receives the injected params.
  - `Schema::getState()` returns the assembled bag; `dehydrated(false)` on a
    component drops its key.
- **Integration** test (`tests/Integration/State/PropertyAccessorBootTest.php`)
  boots the schemas kernel and asserts `PropertyAccessor::getInstance()` returns
  the booted service (proves the static-instance wiring end-to-end).
- DoD gates green. `pnpm` gates are no-op (no asset changes) but must stay green.

**Notes / pitfalls.** Keep `afterStateHydrated` (hydration hook) distinct from
the T2 `afterStateUpdated` (change hook) — the prototype collided them. PHPStan
max: the accessor takes `object`/`array`, never `$host->data` directly on an
`InteractsWithSchemas`-typed value (no such declared prop) — go through Symfony
PropertyAccess. Don't reach for Laravel `Arr::*`/`data_get` (absent); the
accessor + native array ops cover the minimal dehydrate.

---

## T2 — Live reactivity loop + `afterStateUpdated` bridge

- [x] **T2** — `data-model` binding, the `#[PreDehydrate]`/`#[PostHydrate]`
  change-detection bridge, and a fixture live host proving change →
  `afterStateUpdated` → re-render, verified with the UX live-component test
  harness **and** a browser drive.

**Goal.** Make the substrate reactive: `live()/reactive()/lazy()/debounce()` with
container inheritance, `getDataModel()` emitting the verified UX `data-model`
grammar, and the `$oldState` snapshot/diff bridge that fires each changed
component's `afterStateUpdated` closure on the round-trip.

**Port from.**
- Filament binding modifiers: `vendor/filament/schemas/src/Concerns/HasStateBindingModifiers.php`
  — `live()` :21, `reactive()` :30, `lazy()` :37, `debounce()` :44, container
  inheritance in `getStateBindingModifiers()`/`isLive()` :83/:126. (Skip
  `$entangle`/`applyStateBindingModifiers` — no UX analogue, design §6.)
- Filament change hook: `vendor/filament/schemas/src/Components/Concerns/HasState.php`
  — `afterStateUpdated()` setter :126, `callAfterStateUpdatedHook()` :222
  (injects `old`/`oldRaw`), `getOldState()`/`getOldRawState()` :685/:704.
  Filament's `callAfterStateUpdated()` :172 partial-render + `store()` dedup
  do **not** port (Livewire-only — Decision §3, §"replace-list").
- **Prototype salvage:**
  `git show origin/old:packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php`
  (`getDataModel()` — the exact `norender|…` / `on(change)|…` / `debounce(n)|…`
  / bare-path grammar) and
  `git show origin/old:packages/schemas/src/Concerns/HasSchemas.php`
  (`#[LiveProp] public array $oldState`, `#[PreDehydrate] persistOldState()`
  walking `getFlatComponents()`, `#[PostHydrate]` diff-and-fire). **Rename the
  `#[PostHydrate]` method** away from `afterStateUpdated` (the prototype's
  collision) — e.g. host `callAfterSchemaStateUpdated()`.
- **UX `data-model` grammar — verified, cite in the code comment:**
  `vendor/symfony/ux-live-component/assets/dist/live_controller.js` parses
  modifiers in `get_model_binding_default` (`norender`→`shouldRender=false`
  :1713; `on(input|change)` :1710; `debounce(ms)` :1717; `modifier|…|action`
  split). Bare path = live (input event, default debounce). This matches the
  prototype's four outputs exactly — no change needed, but keep the citation.
- Live-component test harness: `vendor/symfony/ux-live-component/src/Test/InteractsWithLiveComponents.php`
  + `TestLiveComponent` (`->set()`, `->call()`, `->render()`, `->component()`).
  **Verify `set()`'s dotted interior-path support** against that source before
  relying on `->set('data.title', …)`.

**Touch (current `main`).**
- **New** `packages/schemas/src/Components/Concerns/HasStateBindingModifiers.php`
  — `live`/`reactive`/`lazy`/`debounce` flags (native `Closure` unions, no arity
  specs) + `getDataModel(): ?string` with container inheritance when own
  `isLive` is null.
- `packages/schemas/src/Components/Concerns/HasState.php` — add the
  `afterStateUpdated(?Closure)` setter/store, `clearAfterStateUpdatedHooks()`,
  `getOldState()`/`getOldRawState()` (read the host's `$oldState` at this path),
  and the `callAfterStateUpdatedHook()` injecting `old`/`oldRaw`.
- `packages/schemas/src/Components/Component.php` — `use HasStateBindingModifiers`.
- `packages/schemas/src/Concerns/HasSchemas.php` — add `#[LiveProp] public array
  $oldState = []`, `#[PreDehydrate]` snapshot, `#[PostHydrate]` diff-and-fire
  (bubbling changed components to parents). Import the UX attributes
  (`Symfony\UX\LiveComponent\Attribute\{LiveProp,PreDehydrate,PostHydrate}` —
  all confirmed present).
- `packages/schemas/tests/Fixtures/Kernel.php` — register
  `new Symfony\UX\LiveComponent\LiveComponentBundle()` (confirmed present;
  depends on the already-registered TwigComponent + Stimulus bundles).
- **New fixtures:** an `#[AsLiveComponent]` host (mirror
  `origin/old:…/tests/Fixtures/Twig/Components/ArticleForm.php` — `use
  DefaultActionTrait, HasSchemas`, a `#[LiveAction] save()`), its Twig template
  calling `renderSchema('schema')`, and a minimal fixture **field** component
  (extends `Components\Component`, `statePath = name`, template renders
  `<input data-model="{{ component.getDataModel() }}" value="…">`). Live/render
  fixtures go under `tests/Fixtures/` per convention. (No real `Field` exists —
  the `forms` package is empty — so the fixture field is the binding surface.)

**Locked decisions.** Topology = monolithic host, whole-schema re-render +
Idiomorph (§3). `afterStateUpdated` capture = `#[PreDehydrate]`/`#[PostHydrate]`
diff, **not** `onUpdated` (§2; identity `onUpdated` doesn't fire on interior
`data.*` writes). Host `#[PostHydrate]` method and component
`afterStateUpdated()` setter kept distinctly named. `$oldState` ≈ doubles wire
state — accepted (§2).

**Acceptance criteria.**
- **Integration** test using `InteractsWithLiveComponents`
  (`tests/Integration/LiveStateTest.php`): mount the fixture host, assert the
  rendered field carries the right `data-model` for `live()` vs default vs
  `lazy()` vs `debounce()`; `->set('data.title', 'X')` (or `submitForm`) then
  `->render()` shows the new value **and** an `afterStateUpdated` closure
  observed the change (e.g. it wrote a sibling field / bumped a counter);
  confirm old value was passed. Cover the no-change case (hook does **not** fire).
- **Browser verification** (design §6 "browser-verified on a fixture host"):
  serve the fixture host (e.g. a throwaway `public/index.php` against the test
  Kernel, or `symfony server:start`), drive with Chrome
  (`mcp__claude-in-chrome__*`): type into the bound input, confirm the debounced
  round-trip re-renders and `afterStateUpdated` ran (assert a visible side
  effect). Record a GIF. If standing up a served app is blocked, the harness
  Integration test is the required gate and the browser drive is deferred with a
  prominent note — do not silently skip it.
- DoD gates green (still no asset build — `data-model` is a plain attribute, no
  Stimulus controller for the simple field).

**Notes / pitfalls.** `getDataModel()` must emit paths verbatim as the absolute
`data.*` path (binds directly; UX converts dot→bracket client- and server-side).
Non-live ⇒ `norender|…` still **syncs** the value for the next submit (the
"syncs but not reactive until submit" semantics) — that's the intended default,
not "no binding". Bubble to parents in `#[PostHydrate]` but guard against
double-firing.

---

## T3 — State casts

- [x] **T3** — `stateCast()` + the `StateCast` contract, applied on hydrate
  (`set`) and dehydrate/read (`get`).

**Goal.** Port Filament's state-cast pipeline so a component can transform its
raw bag value ↔ typed application value (the seam datetime/boolean/array casts
and future form fields hang off).

**Port from.** `vendor/filament/schemas/src/Components/Concerns/HasState.php` —
`stateCast()` :78, `getStateCasts()`/`getDefaultStateCasts()` :88/:107,
`hasCustomStateCasts()` :99, cast application in `state()` :646 (reverse `set`),
`getState()` :946 (`get`), `getStateToDehydrate()` :294 (`get`), and
`hydrateState()`'s cast-`set` pass + `appliedStateCastPaths` :446. The contract:
`vendor/filament/schemas/src/Components/StateCasts/Contracts/StateCast.php` and
the concrete casts in `vendor/filament/schemas/src/Components/StateCasts/`.

**Touch (current `main`).**
- **New** `packages/schemas/src/Components/StateCasts/Contracts/StateCast.php`
  (`get(mixed): mixed`, `set(mixed): mixed`), and any base casts actually needed
  now (keep to what a fixture field exercises — don't port the whole directory
  speculatively).
- `packages/schemas/src/Components/Concerns/HasState.php` — add the
  `stateCasts` array, `stateCast()`, `getStateCasts()`/`getDefaultStateCasts()`,
  and wire casts into `state()` (reverse-`set`), `getState()` (`get`),
  `getStateToDehydrate()` (`get`), and `hydrateState()` (`set` + the
  `appliedStateCastPaths` dedup).

**Locked decisions.** Layered after the slice (§6). Casts operate on the bespoke
bag values — no Eloquent casting analogue.

**Acceptance criteria.** Unit tests: a fixture cast round-trips (`set` on
hydrate, `get` on read/dehydrate); `getDefaultStateCasts()` compose before
custom; `appliedStateCastPaths` prevents double-application. A fixture field
using the cast fills and dehydrates to the expected typed values. DoD gates green.

---

## T4 — Dehydration correctness, conditions & mutation

- [x] **T4** — full `dehydrateState` (shared-path / descendant handling),
  `dehydrated`/`saved`/`dehydratedWhenHidden`, `beforeStateDehydrated`, and
  `mutateDehydratedState`. (Also ported a minimal `CanBeHidden` core —
  `hidden()`/`visible()`/`isHidden()`/`isVisible()` — as the first consumer of the
  hidden gate, per the approved scope decision; render-loop skipping and the rest
  remain, see [schemas/component.md](schemas/component.md).)

**Goal.** Replace T1's minimal assembler with Filament's correct dehydration:
conditional dehydration, sibling-safe key removal, and the dehydrated-state
mutation hook — the machinery nested layouts / repeaters will depend on.

**Port from.** Component: `vendor/filament/schemas/src/Components/Concerns/HasState.php`
— `dehydrateState()` :316 (shared-`statePath` + `getDescendantStatePathsToForget`
:413 + `hasComponentWithStatePath` :379), `dehydrated()`/`isDehydrated()`
:256/:775, `saved()`/`isSaved()` :270/:791, `dehydratedWhenHidden()` :263,
`beforeStateDehydrated()`/`callBeforeStateDehydrated()` :155/:233,
`mutateDehydratedState()` + `mutateDehydratedStateUsing()` :597/:632. Container:
`vendor/filament/schemas/src/Concerns/HasState.php` — `dehydrateState()` :205,
`mutateDehydratedState()` :218, `callBeforeStateDehydrated()` :143,
`buildDehydratedComponentsCache()`/`hasDehydratedComponent()` :165/:192,
`getState()` :450 (port the prune/dehydrate/mutate spine; **omit** `validate()`
until the validation epic — read from the raw bag instead).

**Touch (current `main`).** `packages/schemas/src/Components/Concerns/HasState.php`
and `packages/schemas/src/Concerns/HasState.php` (extend the T1 traits). Provide
Sylament equivalents for Filament's `Arr::set/get/has/forget` over the plain
`$state` array — extend the T1 accessor with the array-path ops needed (note:
Symfony PropertyAccess has no `forget`/unset — implement key removal explicitly).

**Locked decisions.** Layered after the slice (§6). No server-side partial
render (§3) — dehydration is request-scoped over the whole bag.

**Acceptance criteria.** Unit tests: `dehydrated(false)` drops only the owning
component's descendant paths, not a sibling sharing the `statePath`;
`beforeStateDehydrated` fires before dehydrate; `mutateDehydratedStateUsing`
transforms the dehydrated value; `saved(false)` and `dehydratedWhenHidden`
behave. A nested-fixture (Section-wrapped fields) `getState()` returns the
correct pruned array. DoD gates green.

---

## T5 — `$get`/`$set` component resolution (cast-aware)

- [x] **T5** — upgraded `Get`/`Set` to resolve the target **component** by state
  path (new `getComponentByStatePath()` on the schema `HasComponents`) and return
  its cast `getState()` / route writes through the cast `state()` setter + optional
  `afterStateUpdated`, with single-element child-container skipping and the T1
  raw-bag fallback preserved for undeclared paths. Minimal port (session decision):
  Filament's static accumulating skip-stack, the `withHidden`/`withAbsoluteStatePath`
  params, the `getComponentByStatePath` result cache, and `clearCachedChildSchemas`
  on the raw-write fallback are deferred (unreachable until computed fields /
  repeaters / hidden-filtering land) — recorded in
  [schemas/component.md](schemas/component.md).

**Goal.** Make `$get('../sibling')` return the sibling's *cast* state (not a raw
bag read), matching Filament, now that casts (T3) and nesting exist.

**Port from.** `vendor/filament/schemas/src/Components/Utilities/Get.php:23`
(resolve component via `getRootContainer()->getComponentByStatePath(...)`, return
`->getState()`, `data_get` fallback, `skipComponentsChildContainersWhileSearching`),
`Set.php:15` (resolve component, `->state()`, optional
`shouldCallUpdatedHooks`). Needs `Component::getComponentByStatePath()` /
`getRootContainer()` traversal (`getRootContainer()` already on
`BelongsToContainer:28`).

**Touch (current `main`).** `packages/schemas/src/Components/Utilities/Get.php` +
`Set.php` (extend T1's simple forms), and add `getComponentByStatePath()` to the
schema/component traversal (new method on `Concerns/HasComponents.php` or a state
trait). Keep the T1 raw-read fallback for undeclared paths.

**Locked decisions.** `new Get($this)` construction (no `app()`); §4/§5 stand.

**Acceptance criteria.** Unit tests: `$get('field')` returns cast state;
`$get('../sibling')` resolves across containers; `$set(path, v,
shouldCallUpdatedHooks: true)` fires `afterStateUpdated`; child-container
skipping prevents self-recursion. DoD gates green.

---

## T6 — Relationship state loading (Doctrine)

- [ ] **T6** — `loadStateFromRelationships` / constant-state-from-record,
  translated Eloquent → Doctrine.

**Goal.** Port Filament's relationship-backed state hydration for record-bound
schemas. Largest translation surface; lands last.

**Port from.** `vendor/filament/schemas/src/Components/Concerns/CanGetStateFromRelationships.php`
and the `getConstantState*`/`loadStateFromRelationships` calls threaded through
`HasState` (component :977-1107, container :418-445). Translate Eloquent
`Model`/relationships/`data_get($record, …)` → Doctrine entities + associations
per CLAUDE.md.

**Touch (current `main`).** New `Components/Concerns/CanGetStateFromRelationships.php`
+ the `getConstantState*` methods; integrate into `hydrateState()`/`getState()`.
Depends on a Doctrine-backed record concept on the host (`BelongsToModel` →
`BelongsToEntity` translation — coordinate with the model/record parity work).

**Locked decisions.** Latest in the sequence; needs T3 (casts feed relationship
hydration). Doctrine idiom, Filament public API shape preserved.

**Acceptance criteria.** Integration tests with a Doctrine fixture entity:
schema fills from an entity's attributes/associations; `getState()` round-trips.
DoD gates green. (Flag any Doctrine test-infra additions as their own
checkpoint.)

---

## T7 — Renderless / poll / partial-render divergence

- [ ] **T7** — renderless-after-update (`norender`/`skipRender`), `CanPoll` →
  `data-poll`, and the recorded `CanPartiallyRender` divergence.

**Goal.** Port the reactivity behaviors that *do* map to UX and formally record
the one that doesn't.

**Port from.** `vendor/filament/schemas/src/Components/Concerns/CanPoll.php`
(→ `data-poll`, default `$render`/2000ms), the renderless arms of
`callAfterStateUpdated()` (`isRenderlessAfterStateUpdated()` / `skipRender()`
:192) → suppress the UX re-render (`norender` / not-triggering render). The
`CanPartiallyRender` server-side partial (`callAfterStateUpdated()` :131-137,
`PartialsComponentHook`) has **no** UX equivalent — do not port; record it.

**Touch (current `main`).** New `Components/Concerns/CanPoll.php`; renderless
handling on the component state trait / host bridge; a template hook emitting
`data-poll`. Migrate the **No server-side partial render** divergence into
[schemas/component.md](schemas/component.md) with the design's wording.

**Locked decisions.** `CanPartiallyRender` = permanent divergence; whole-schema
re-render + Idiomorph is the model (§3). `skipRender`/renderless **does** port.

**Acceptance criteria.** Integration test (harness): a `norender`/renderless
change syncs state without a re-render side effect; a polled component triggers
the default action. The divergence is documented. DoD gates green.
