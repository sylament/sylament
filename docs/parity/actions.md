# Actions — base + modal + mounting-lite — epic design

> **Scope:** the thin cut of the `actions` bundle the tracer needs — a
> `Sylament\Actions\Action` config object + button styling, a native-`<dialog>`
> modal system in `support`, and a **confirm-modal + minimal mount/call path**
> that runs an action closure against a record over one round-trip. This is
> **[roadmap.md](roadmap.md) Milestone 1, step 7** ("Actions — base + modal +
> mounting-lite"), the ★ **dominant-risk** step — the ⚡ Livewire→UX-LiveComponent
> action-mounting stack and the 🎨 Alpine→Stimulus modal. The deferred remainder —
> the full mounting stack, `ActionGroup`, bulk, import/export, form-in-modal, and
> the concrete record actions beyond a Delete shape — is breadth-fill
> (Milestone 2). Task briefs: [actions-tasks.md](actions-tasks.md).
>
> Discovery read Filament v5.7.1 (`vendor/filament/actions`, `vendor/filament/support`),
> Symfony UX LiveComponent v3.3 (`vendor/symfony/ux-live-component`) and current
> `main`. Every load-bearing claim is source-cited so the task briefs port
> without re-deriving. Reference roots:
> [`vendor/filament/actions/src`](../../vendor/filament/actions/src),
> [`vendor/symfony/ux-live-component/src`](../../vendor/symfony/ux-live-component/src),
> [`packages/actions`](../../packages/actions),
> [`packages/support`](../../packages/support),
> [`packages/schemas`](../../packages/schemas).

## Sequencing — spike the risk first

The roadmap flags step 7 ★ and says *spike the dominant risk first within its
epic*. The risk is a single make-or-break coupling: **can a button click reach a
PHP action closure and open a modal over the UX-LiveComponent round-trip?** So
the task plan front-loads the thinnest vertical that proves it (AC1 modal chrome
→ AC2 the mount/call spike) before the breadth of styling (AC3) and confirm
lifecycle (AC4). The prior epics de-risk this:

- **The live host substrate is shipped and proven.** `live-state` T1–T5/T7 landed
  the `#[AsLiveComponent]` + `DefaultActionTrait` + `#[LiveProp]` + `#[LiveAction]`
  host shape ([`LiveStateHost`](../../packages/schemas/tests/Fixtures/Twig/Components/LiveStateHost.php)),
  and forms fields ride it. The actions host **reuses** that shape — it writes no
  mount loop, hydrator, or action router (all ux-live-component).
- **One genuinely-new primitive:** an argument-carrying `#[LiveAction]`. Every
  shipped LiveAction so far is arg-less (`save()`,
  [`LiveStateHost.php:62`](../../packages/schemas/tests/Fixtures/Twig/Components/LiveStateHost.php)).
  `mountAction(string $name, array $arguments)` is the first to bind `#[LiveArg]`
  parameters — verified to exist in UX ([`Attribute/LiveArg.php`](../../vendor/symfony/ux-live-component/src/Attribute/LiveArg.php),
  passed through by `live_action()`) but unproven end-to-end here. **AC2 proves it.**

**Decoupling refinement (beyond a naive Filament read): the confirm-only host
needs no schemas.** A confirm modal carries no form — its body is a static
heading/description/confirm/cancel. So the mount→confirm→call→unmount flow uses
only `#[LiveAction]`/`#[LiveProp]`/re-render from `DefaultActionTrait`, **not** the
`HasSchemas` reactive loop (that loop exists for form-field `$get`/`$set`, which
confirm-only never touches). Consequently `packages/actions/composer.json` stays
`sylament/support`-only ([confirmed](../../packages/actions/composer.json)) — the
`schemas` dependency arrives only with form-in-modal (breadth-fill).

## The port shape at a glance

| Filament | Sylament (this epic) |
| --- | --- |
| `Filament\Actions\Action extends ViewComponent` ([`Action.php:51`](../../vendor/filament/actions/src/Action.php)) | `Sylament\Actions\Action extends Sylament\Support\Component\ViewComponent` — a **config object**; sibling of `Schemas\Components\Component` |
| Trigger via `toHtml()` + `CanGenerate*Html` `ob_start()` generators (v5 Blade-bypass) | a **Twig template** rendered through the existing `renderComponent()` path — the generators are **not** ported |
| Host trait `Filament\Actions\Concerns\InteractsWithActions` ([`:111`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)) | trait `Sylament\Actions\Concerns\HasActions` — the documented **name swap** ([renames.md](renames.md)) |
| Host contract `Filament\Actions\Contracts\HasActions` | contract `Sylament\Actions\Contracts\InteractsWithActions` — other half of the swap |
| `wire:click="mountAction('delete', …)"` (Livewire) | `{{ live_action('mountAction', {name:'delete', …}) }}` ([`LiveComponentRuntime.php:51`](../../vendor/symfony/ux-live-component/src/Twig/LiveComponentRuntime.php)) |
| `$this->dispatch('sync-action-modals', …)` ([`InteractsWithActions.php:789`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)) | `$this->dispatchBrowserEvent('sylament-open-modal', {id})` / `'sylament-close-modal'` ([`ComponentToolsTrait.php:49`](../../vendor/symfony/ux-live-component/src/ComponentToolsTrait.php)) — explicit id-matched events, section-controller precedent |
| `<x-filament::modal>` Alpine div-overlay in `support` (`resources/views/components/modal`, `resources/js/components/modal.js`) | `<twig:SylamentSupport:Modal>` native **`<dialog>`** + `sylament--support--modal` Stimulus controller in `support` — a **recorded divergence** |
| `Filament\Support\Colors\ColorManager`, Gate authorization, notification delivery | **deferred** (🎨 color seam; 🔒 Security; notifications bundle) |

## Filament reference — what we port

### The class + its 57 traits

`class Action extends ViewComponent implements Arrayable`
([`Action.php:51`](../../vendor/filament/actions/src/Action.php)); the chain is
exactly `Action → ViewComponent → Component` (no v3-era `StaticAction`/
`MountableAction`). Load-bearing shape: `protected string $evaluationIdentifier =
'action'` (so `fn ($action) => …` resolves the instance), `final public function
__construct(?string $name)` calling `$this->name($name)`, `make()` → construct +
`configure()`, and `setUp()` → `parent::setUp(); $this->defaultView(static::BUTTON_VIEW)`.
The five trigger "variants" (button/link/iconButton/grouped/badge) are **not
subclasses** — they are view-swaps on the one class; `ButtonAction`/`IconButtonAction`
are deprecated `->button()`/`->iconButton()` shells and there is no `LinkAction`.

Of the 57 traits, the ones this epic touches, grouped by axis:

**Reused verbatim from `support`** (present and shape-matched on `main` —
[`Component/Concerns/`](../../packages/support/src/Component/Concerns)): `HasColor`,
`HasIcon`, `HasIconPosition`, `HasIconSize`, `HasExtraAttributes`, `HasTooltip`,
plus the [`Size`](../../packages/support/src/Enum/Size.php)/`IconPosition`/`IconSize`
enums. Filament pulls these from `Filament\Support\Concerns\`; Sylament already
placed them under `Sylament\Support\Component\Concerns\`.

**New in `actions`** (MVP): `HasName` (`getName()` **throws** on blank — identity
for `make()`, unlike schemas' null-returning `HasName`), `HasLabel` (name-derived
default `str($name)->before('.')->kebab()->ucfirst()`,
[`HasLabel.php:47`](../../vendor/filament/actions/src/Concerns/HasLabel.php) — **not**
Field's `afterLast('.')`), `HasSize` (`size()`/`defaultSize()`/`getSize()`),
`CanBeDisabled`, `CanBeHidden`, `CanOpenUrl` (minimal), `CanRequireConfirmation`,
`CanOpenModal` (confirm subset), `HasAction` (the `action()` closure), `HasArguments`,
`HasLifecycleHooks` (`before`/`after`/`cancel`/`halt`), `CanNotify` (config only,
delivery stubbed), `InteractsWithRecord`, `BelongsToLiveComponent`.

**Deferred** (breadth-fill / other threads): `CanBeAuthorized` (Laravel Gate →
Symfony Security — its own 🔒 sub-epic; `disabled()`/`tooltip()` auth coupling
stubbed), `HasBadge`/`HasBadgeTooltip` + badge variant, `HasKeyBindings`,
`CanBeLabeledFrom`, `HasGroupedIcon`/`HasTableIcon`, `CanBeSorted`, `CanBeOutlined`,
`BelongsToGroup`/`BelongsToTable`, the selected-records concerns, and the
`Arrayable`/`toArray()` JS-serialization surface (Livewire snapshot transport,
also needed by notifications — plan the shape, don't build).

### The mount/call lifecycle (the ⚡ core)

Filament's runtime is a two-round-trip state machine on the host via
`InteractsWithActions`:

1. **Mount.** `mountAction(string $name, array $arguments = [], array $context = [])`
   ([`InteractsWithActions.php:111`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php))
   pushes a `['name','arguments','context']` descriptor onto the `$mountedActions`
   array, resolves the `Action`, and — via `shouldOpenModal()`
   ([`:418`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)) —
   either calls the action inline or dispatches `sync-action-modals`
   ([`:789`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php))
   to open the modal.
2. **Call.** the modal's confirm fires `callMountedAction(array $arguments = [])`
   ([`:197`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)):
   `before → $action->call() (evaluate the action() closure) → after → success/
   failure notification + redirect`, honoring `Halt`/`Cancel`, then `unmountAction()`
   ([`:732`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php))
   pops the stack.

**The Action object is never serialized** — only its `['name','arguments','context']`
descriptor lives in the `$mountedActions` LiveProp; the Action is **re-resolved
each round-trip** via a host `{name}Action()` method
([`resolveAction`, `:547`](../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)).
Record identity travels as a scalar `context['recordKey']`, re-inflated to an
entity server-side — never hydrated as an object argument.

### The modal (🎨)

Filament's modal is one Blade component + Alpine object in **support**
(`resources/views/components/modal/index.blade.php`, `resources/js/components/modal.js`);
`actions` only adds the `CanOpenModal` **config** concern that feeds it. The
Alpine object hand-implements what the browser now gives natively: focus trap
(`@alpinejs/focus`), ESC (topmost-only), click-away, refcounted body scroll-lock,
enter/leave transitions, and a stacking model. The confirm dialog is **not** a
separate class — it is `CanOpenModal` getters branching on `isConfirmationRequired`
(heading/description/icon/submit-label). We port the **config surface** and the
**interactivity contract**, not the Alpine object (see Design → Modal).

## Current graft surface (what's on `main`)

- **`packages/actions`** is a bundle class + placeholder test; `composer.json`
  requires only `sylament/support`
  ([confirmed](../../packages/actions/composer.json)). It has **no** `assets/package.json`
  (not a JS/pnpm workspace) — but `packages/actions/assets/src/styles/index.css`
  exists and is already `@import`ed by support's Tailwind build
  ([`support/…/index.css:11`](../../packages/support/assets/src/styles/index.css)),
  so `sy-ac-*` CSS needs **zero** new build setup, and the one JS file (the modal
  controller) lives in **support's** existing workspace.
- **`Sylament\Support\Component\ViewComponent`**
  ([source](../../packages/support/src/Component/ViewComponent.php)) — `template()`/
  `getTemplate()`/`templateData()`/`getExtraTemplateData()`/`render(Environment)`.
  Render context is `{component, …getExtraTemplateData(), …getTemplateData()}`.
  **No `defaultView`/`defaultSize` layer** — Filament's `defaultView(BUTTON_VIEW)`
  maps to `template(<button template>)` in `setUp()`.
- **The live host substrate** — `#[AsLiveComponent]` + `DefaultActionTrait` +
  `#[LiveProp]` + `#[LiveAction]`, exercised by
  [`LiveStateHost`](../../packages/schemas/tests/Fixtures/Twig/Components/LiveStateHost.php);
  [`BelongsToLiveComponent`](../../packages/schemas/src/Concerns/BelongsToLiveComponent.php)
  is the `getLivewire()`→`getLiveComponent()` precedent (typed against the schemas
  contract; the actions copy types against `InteractsWithActions`).
- **UX LiveComponent primitives** (verified present):
  `live_action()` → `LiveComponentRuntime::liveAction(string $actionName, array
  $parameters = [], array $modifiers = [], ?string $event = null)`, `is_safe
  html_attr` ([`:51`](../../vendor/symfony/ux-live-component/src/Twig/LiveComponentRuntime.php),
  [registered `:28`](../../vendor/symfony/ux-live-component/src/Twig/LiveComponentExtension.php));
  `#[LiveArg]`, `#[LiveAction]`, `#[LiveProp]`, `#[PreDehydrate]`/`#[PostHydrate]`,
  `DefaultActionTrait`; `ComponentToolsTrait::dispatchBrowserEvent(string, array)`
  ([`:49`](../../vendor/symfony/ux-live-component/src/ComponentToolsTrait.php)).
- **The Stimulus + Twig-component precedents** the modal/button mirror:
  [`section_controller.ts`](../../packages/support/assets/src/controllers/section_controller.ts)
  (`static values`, `connect()`, `@window`-driven methods with `#matches(event)`
  id-matching), its `symfony.controllers` registration in
  [`support/assets/package.json`](../../packages/support/assets/package.json), and
  [`IconButton/index.html.twig`](../../packages/support/templates/components/IconButton/index.html.twig)
  (`{% props %}` + `attributes.defaults({class: classes(…)})` + `generateIconHtml`).
  Support's asset deps are Stimulus-only — **native `<dialog>` needs no focus-trap
  dependency** (adding one would need approval per CLAUDE.md).

## Design

### 1. `Sylament\Actions\Action` — a config object rendered as a button

`Action extends Sylament\Support\Component\ViewComponent`, mirroring Filament and
sitting as a **sibling** of `Schemas\Components\Component` (both extend the support
`ViewComponent`; actions must not route through schemas). `evaluationIdentifier =
'action'`; `final __construct(?string $name)` → `name($name)`; `make(?string $name)`
uses **`new static($name)` + `configure()`** (the Sylament `Field::make`/`Section`
precedent — components reach services through the static `Invoker`/`ComponentManager`,
so Filament's `app(static::class, …)` buys nothing). The button template is a
**property default** — `protected $template = '@SylamentActions/components/button-action.html.twig'`
— mirroring the Icon/Text/Section/Schema precedent
([`Icon.php:21`](../../packages/schemas/src/Components/Icon.php)); `@SylamentActions`
auto-registers from the new `packages/actions/templates/` dir (standard bundle
convention, no wiring). Filament's `setUp()`→`defaultView(BUTTON_VIEW)` variant
seam is deferred with the `ActionVariant` enum (there is one variant now).
Rendered by a host via `{{ renderComponent(action) }}` — **no `toHtml()`**, one
render mechanism.

**Template-var split:** templates read the instance as `component.*` (Sylament's
uniform ViewComponent convention), while closure authors still write
`fn ($action) => …` (from `evaluationIdentifier`). Don't introduce a per-type
`$action` template var.

**Styling** reuses the support concerns above; the button template builds its
class list exactly as IconButton does — `classes('sy sy-btn sy-ac', {'sy-size-<x>':…,
'sy-disabled':…, 'sy-color':…, ('sy-color-'~name):…})` + `color()` (the shipped
helper) + `attrs(...)` — layering `sy-ac-*` action hooks. A support-level
`<twig:SylamentSupport:Button>` primitive (`sy-btn`, sibling of `IconButton`;
`sy-btn` is already forward-referenced in support CSS) provides the base chrome;
the action template composes it and adds the interactive attributes.

**MVP ships the button variant only.** The `ActionVariant` enum + `button()`/
`link()`/`iconButton()` view-swap (the single highest-leverage *deferred* design
choice) lands with the other variants in breadth-fill — grouped needs `ActionGroup`,
badge needs new support concerns + `BadgeComponent`, link/icon-button are cheap
follow-ons. A `->url()` (minimal `CanOpenUrl`) renders the trigger as `<a>` so a
record "Edit" action can link to a page without any mounting.

### 2. The host: `HasActions` trait + `InteractsWithActions` contract

Following the **documented swap** ([renames.md](renames.md), set by schemas):
Filament's host **trait** `Concerns\InteractsWithActions` → Sylament **trait**
`Sylament\Actions\Concerns\HasActions`; Filament's host **contract** `Contracts\HasActions`
→ Sylament **contract** `Sylament\Actions\Contracts\InteractsWithActions`. (Grep
trap: both names exist on both sides with opposite meanings — `Has*` is the house
trait prefix; the interface names the behavior.)

The host **composes** `HasActions` **beside** the live-component shape — it is a
flat peer trait, not an extension of `HasSchemas`:

```php
#[AsLiveComponent(name: '…', template: '…')]
class SomeHost implements InteractsWithActions   // Sylament\Actions\Contracts
{
    use DefaultActionTrait;
    use HasActions;                              // Sylament\Actions\Concerns

    #[LiveProp]
    public array $mountedActions = [];           // ['name','arguments','context'] descriptors
    // …host declares its own {name}Action() methods returning Action instances
}
```

`HasActions` provides:
- `#[LiveAction] mountAction(#[LiveArg] string $name, #[LiveArg] array $arguments = [], #[LiveArg] array $context = [])` —
  sets `$mountedActions = [[...]]` (**single slot**, not a stack, for MVP), resolves
  the action, and if it `shouldOpenModal()` dispatches `sylament-open-modal` with
  the modal id.
- `#[LiveAction] callMountedAction()` — resolves the mounted action, runs
  `before → evaluate(action closure) → after`, branches success/failure, `unmountAction()`.
- `#[LiveAction] unmountAction()` — clears `$mountedActions`, dispatches a close event.
- `getMountedAction()` (the single entry) and `getAction(string $name)` /
  `resolveAction()` — look up a host `{name}Action(): Action` method and
  **re-instantiate each round-trip** (Filament's convention, collapsed to
  single-level — no `cachedMountedActions` nesting index).

**Security — single non-writable `$mountedActions` for confirm-only.** UX
`writable: true` is whole-prop, so a *writable* array holding both action identity
and user form-data would let the client rewrite which action runs. Confirm-only
carries **no form data**, so `$mountedActions` is mutated only server-side inside
the LiveActions and needs **no `writable`** — closing the tampering hole for free.
The split into a server-controlled stack + a separate
`#[LiveProp(writable:true)] $mountedActionData` bag is the **form-in-modal seam**
(record it there, not here).

### 3. The modal — native `<dialog>` in `support` (🎨)

A new **support** UI-kit primitive (Filament's modal lives in support; roadmap
step 2 — pulled forward here as an actions prerequisite):

- **`<twig:SylamentSupport:Modal>`** — a LiveComponent-**agnostic** `<dialog>`
  (`sy sy-modal`) with heading/description/footer **slots**, rendered closed
  server-side. It takes attributes/slots for the footer; the actions layer injects
  the `live_action()` confirm/cancel wiring — the modal chrome knows nothing about
  mounting.
- **`sylament--support--modal`** Stimulus controller (in support's workspace,
  registered in `support/assets/package.json`'s `symfony.controllers`), mirroring
  `section_controller`: `static values` (`id`, `returnsFocus`), and `@window`
  methods `open`/`close` matched by `event.detail.id` (`#matches`). It calls
  `dialogTarget.showModal()` / `.close()` — **native `<dialog>` supplies focus-trap,
  ESC, top-layer stacking and `::backdrop`**, so the controller is ~30 lines vs the
  ~120 a faithful div-overlay port needs. CSS transitions ride `[open]`/state
  classes + `::backdrop`.

**Open/close flow (hybrid).** DOM presence is driven by conditional re-render
(the modal markup renders when `$mountedActions` is non-empty); on top, the server
`mountAction()` dispatches a `sylament-open-modal` browser event (id in the
payload) which the controller answers with `showModal()` after the morphdom
re-render — Filament's single `sync-action-modals` event becomes explicit
id-matched open/close events (the section-controller precedent). Because the
modal node persists while mounted, morphdom patches attributes without dropping
the `open` state; each re-render re-dispatches open, so state re-syncs. Cancel/ESC
→ `close()` + a client event; explicit `{{ live_action('unmountAction') }}` clears
server state.

**The confirm dialog is config, not a class** — `CanRequireConfirmation` +
`CanOpenModal` getters (`modalHeading`, `modalDescription`, `modalIcon`/`modalIconColor`,
`modalSubmitActionLabel`, `modalCancelActionLabel`, `modalWidth`) branch on
`isConfirmationRequired`; the actions confirm-modal template composes the support
`Modal` and injects the two `live_action()` buttons.

**Testing caveat.** No JS behavioral-test harness exists
([infrastructure.md#stimulus-controllers](infrastructure.md#stimulus-controllers)) —
the controller is covered by `tsc`/Biome + rendered-attribute assertions in PHP
render tests, not DOM interaction. Accepted for the tracer; noted as the epic's
one under-tested surface.

### 4. Record binding — a synthetic `resolveRecordUsing` resolver

Doctrine record-binding is live-state **T6** (not built). The action closure needs
a `$record`, so `InteractsWithRecord` (trimmed) exposes a host-settable
`resolveRecordUsing(fn (mixed $key) => …)`: `callMountedAction()` re-inflates
`context['recordKey']` (a scalar) to a record through that closure and injects it
as the `$record` closure parameter. The epic **proves itself on an in-memory
record** (exactly how forms fields proved on a synthetic host ahead of Doctrine),
keeping `actions` self-contained; real Doctrine resolution + the table-driven
`resolveRecordUsing` land with T6 / step 8.

### 5. Lifecycle + notifications (config, delivery stubbed)

`HasLifecycleHooks` (`before`/`after`/`cancel`/`halt`) + `HasAction` run the
closure via `evaluate()` (injecting `$record`, `$arguments`, and the host as
`$livewire`→ the live component). `Halt`/`Cancel` become
`Sylament\Support\Exception\{Halt,Cancel}` (new namespace; Filament places them in
support because multiple packages throw them). `CanNotify` ports the **config**
surface (`successNotificationTitle()`/`failureNotificationTitle()`/…) but
`sendSuccessNotification()`/`sendFailureNotification()` are **no-op seams** until
the notifications bundle lands — the success/failure branch still runs, it just
doesn't render a toast. `HasLifecycleHooks`' Laravel `Event::dispatch` calls
(`ActionCalling`/`ActionCalled`) are dropped for MVP (revisit with the Symfony
event dispatcher if a consumer needs them). Redirect (deferred with the concrete
actions) will return a `RedirectResponse` from the LiveAction, not Livewire's
`store($this)`.

## Decisions

1. **MVP = confirm-modal + no-form execute** (user-approved). Unlocks a Delete
   shape end-to-end (mount → confirm → call → closure → unmount); an "Edit" record
   action is a `->url()` link to the Create/Edit page. Defers the entire
   form-in-modal path — `getMountedActionSchema`, the nested-writable
   `mountedActions.{i}.data` LiveProp, `getState(afterValidate:)`, and the
   `ValidationException`-keeps-modal-open flow (which also needs schema validation
   that [`HasState::getState()`](../../packages/schemas/src/Concerns/HasState.php)
   does not have — `:162`, no `afterValidate`).
2. **Native `<dialog>` modal** (user-approved) — a recorded divergence from
   Filament's Alpine div-overlay; far less Stimulus, browser-native a11y.
   Re-verify slide-over/arbitrary-width against `<dialog>` before breadth-fill.
3. **Synthetic `resolveRecordUsing` resolver** (user-approved) — proves the record
   action on an in-memory record; `actions` stays `support`-only; real Doctrine
   binding is T6.
4. **Notifications: config API ported, delivery stubbed** (user-approved) —
   Filament-faithful surface, no-op send until the notifications bundle.
5. **`Action` is a config object; behavior lives on the host** — the only shape
   compatible with UX (LiveActions must be public methods on the `#[AsLiveComponent]`
   class). A real divergence from Filament's self-contained-action mental model;
   record it.
6. **`HasActions` composes beside the live shape; confirm-only needs no `HasSchemas`.**
   Actions' `composer.json` stays `support`-only. Document that form-in-modal will
   add the `schemas` dependency and the co-requirement that a form-bearing action
   host also be an `InteractsWithSchemas` host.
7. **Single, non-writable `$mountedActions` LiveProp** — descriptor array only, no
   stack, no form-data. The server-controlled/writable split is the form-in-modal
   seam.
8. **Render via `renderComponent()`, Twig template, `new static()` construction** —
   not `toHtml()`/`CanGenerate*Html`, not `app()`. Button variant only for MVP;
   the `ActionVariant` enum discriminator is the documented deferred approach.
9. **Authorization deferred** — stub `isAuthorized()` = true; simplify
   `isDisabled()` = `evaluate($disabled) || isHidden()`; reuse the plain support
   `HasTooltip`. The Gate→Security port is its own 🔒 sub-epic (panels step 10).

## Divergences (record when code lands)

New per-component files land with the code:
[`docs/parity/actions/action.md`](actions) and (support) the modal note under
[infrastructure.md#stimulus-controllers](infrastructure.md#stimulus-controllers);
the ⚡ mounting sub-thread under
[infrastructure.md#live-component-state](infrastructure.md#live-component-state).

- **Native `<dialog>`** instead of Filament's Alpine overlay + `modal.js`
  (focus-trap/scroll-lock/transitions/stacking hand-rolled). Browser-native trap,
  ESC, top-layer, `::backdrop`; slide-over/nesting re-verified before breadth-fill.
- **`Action` as a config object**, behavior on the host `HasActions` — vs
  Filament's `Action` carrying its own mount/call. Forced by UX (LiveActions =
  host methods).
- **`HasActions`/`InteractsWithActions` name swap** — trait vs contract inverted
  from Filament, per the schemas precedent.
- **Single non-writable `$mountedActions`** — vs Filament's single (Livewire-
  checksum-guarded) `$mountedActions`; the writable form-data bag is split off when
  form-in-modal lands, because UX interior-writability differs from Livewire.
- **`getLabel()` default uses `before('.')`** (actions), not `afterLast('.')`
  (Field) — the two Sylament label defaults genuinely differ; don't unify.

## Non-goals (record, don't build)

- **Form-in-modal** (Edit shape) and everything it needs: `getMountedActionSchema`,
  nested-writable LiveProp state, schema validation, `ValidationException` flow.
- **The full mounting stack** — nested/parent actions, `cachedMountedActions`,
  `ActionGroup`, bulk/selected-records actions, partial re-render
  (`forceRender`/`store($this)` — no UX analog).
- **Concrete record actions** beyond a Delete shape (`Create`/`Edit`/`View`/
  `Attach`/…) and **import/export** (🎨 queue → Messenger).
- **`CanBeAuthorized`** (Gate → Symfony Security/voters, 🔒), badge/grouped/keybinding
  variants, `toArray()`/`fromArray()` serialization (Livewire/notifications transport).
- **The PHP color system** and any modal `slideOver`/sticky/teleport/stacking beyond
  a single centered confirm dialog.
- **Notification delivery** — only the config API ships (delivery is the
  notifications bundle).

## Task plan

Cold-session briefs in [actions-tasks.md](actions-tasks.md). Four tasks,
spike-first:

1. **AC1 — Modal + Button primitives (`support`).** `<twig:SylamentSupport:Modal>`
   (native `<dialog>`) + `sylament--support--modal` Stimulus controller +
   `<twig:SylamentSupport:Button>` + `modal.css`/`button.css`. Support-only,
   LiveComponent-agnostic; render-tested from the new actions Integration kernel;
   `tsc`/Biome for the controller.
2. **AC2 — the ⚡ mount/call spike.** Skeletal `Action` (name + `action()` +
   `requiresConfirmation`), `HasActions` + `InteractsWithActions`, `$mountedActions`
   LiveProp, `mountAction`/`callMountedAction`/`unmountAction` LiveActions,
   `dispatchBrowserEvent('sylament-open-modal')`, the button trigger with
   `live_action('mountAction')` wired to the AC1 modal. A synthetic
   `#[AsLiveComponent]` Pest host proves the round-trip via `InteractsWithLiveComponents`
   (the actions Integration kernel registers the UX bundles, mirroring forms).
   **The dominant-risk spike — first argument-carrying `#[LiveAction]`.**
3. **AC3 — Action styling.** The full static surface onto `Action`: reuse the
   support concerns + `HasName`/`HasLabel`/`HasSize`/`CanBeDisabled`/`CanBeHidden`/
   `CanOpenUrl`; the polished button template (color/size/icon/disabled/`<a>`-via-url)
   + `sy-ac-*` CSS; render tests pinning output.
4. **AC4 — Confirm lifecycle + record + notify stub.** `CanRequireConfirmation` +
   `CanOpenModal` (confirm subset) + `HasLifecycleHooks` + `HasAction`/`HasArguments`
   + `CanNotify` (stub) + `InteractsWithRecord` (synthetic `resolveRecordUsing`) +
   `Halt`/`Cancel`; the confirm-modal template; an end-to-end Delete-shaped Pest
   test (click → confirm → closure runs against an in-memory record → success →
   unmount).

Dependency chain: **AC1 → AC2 → {AC3, AC4}** (AC3 styling and AC4 lifecycle
parallelize once AC2 lands the `Action` skeleton + host loop).

## Renames to record when code lands

Add two rows to [renames.md](renames.md), mirroring the schemas swap:

| Filament | Sylament | Home | Why |
| --- | --- | --- | --- |
| `Filament\Actions\Contracts\HasActions` (interface) | `InteractsWithActions` | `Sylament\Actions\Contracts\InteractsWithActions` | Deliberate swap: `Has*` reserved for traits, interface names the behavior (schemas precedent). Grep trap: both names exist on both sides with opposite meanings. |
| `Filament\Actions\Concerns\InteractsWithActions` (trait) | `HasActions` | `Sylament\Actions\Concerns\HasActions` | Other half of the swap: `Has*` matches the house trait prefix. |

`getLivewire()`→`getLiveComponent()`, `livewire()`→`liveComponent()`,
`view()`→`template()`, `dispatch()`→`dispatchBrowserEvent()`, and `Enums\`→`Enum\`/
`Exceptions\`→`Exception\` are **systematic translations**, not renames.
