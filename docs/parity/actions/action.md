# Action (actions)

`Sylament\Actions\Action` — Filament's action config object (`Action extends
ViewComponent`), rendered as a button — plus the host substrate that mounts and
runs it: the `Sylament\Actions\Concerns\HasActions` trait and the
`Sylament\Actions\Contracts\InteractsWithActions` contract (Filament's
`Concerns\InteractsWithActions` / `Contracts\HasActions`, name-swapped — see
[renames.md](../renames.md)). This file tracks all three together, the way
[field.md](../forms/field.md) tracks `Field` plus the schemas `HasName`
concern it mixes in: `Action`, `HasActions`, and `InteractsWithActions` landed
as one epic surface ([actions.md](../actions.md), AC1–AC4) and stay one file
until they grow enough to split. The inherited `ViewComponent` surface
(`template()`/`render()`/…) lives in
[../support/view-component.md](../support/view-component.md); the
`sylament--support--modal` Stimulus controller and the ⚡ action-mounting loop
are cross-cutting and tracked under
[infrastructure.md#stimulus-controllers](../infrastructure.md#stimulus-controllers)
and
[infrastructure.md#live-component-state](../infrastructure.md#live-component-state)
instead of restated here. MVP scope (button variant, confirm-modal execute, no
form) is [actions.md](../actions.md) Decision 1 — that design doc is the fuller
narrative; this file is the living checklist.

**Verification status.** The mount → confirm → call → unmount lifecycle and
the confirm-modal render are proven server-side by the Integration suite
(`HasActionsLifecycleTest`, `HasActionsTest`, `ConfirmModalRenderTest` — all via
`InteractsWithLiveComponents`, which invokes LiveActions directly). The client
round-trip — button click → top-layer `<dialog>` opens → confirm → closure
runs — has no automated JS/DOM harness exercising it
([infrastructure.md#stimulus-controllers](../infrastructure.md#stimulus-controllers)
tracks the repo-wide absence of one); this remains the epic's one noted
under-tested surface, unchanged since AC1.

Reference:

- [`vendor/filament/actions/src/Action.php`](../../../vendor/filament/actions/src/Action.php)
- [`vendor/filament/actions/src/Concerns/`](../../../vendor/filament/actions/src/Concerns) —
  `HasName`, `HasLabel`, `HasSize`, `CanBeDisabled`, `CanBeHidden`, `CanOpenUrl`,
  `CanRequireConfirmation`, `CanOpenModal`, `HasAction`, `HasArguments`,
  `HasLifecycleHooks`, `CanNotify`, `InteractsWithRecord`, `BelongsToLivewire`
- [`vendor/filament/actions/src/Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) —
  the host trait Sylament's `HasActions` ports
- [`vendor/filament/actions/src/Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) —
  the host contract Sylament's `InteractsWithActions` ports
- [`vendor/filament/support/resources/views/components/modal/index.blade.php`](../../../vendor/filament/support/resources/views/components/modal/index.blade.php) /
  [`vendor/filament/support/resources/js/components/modal.js`](../../../vendor/filament/support/resources/js/components/modal.js) —
  the Alpine div-overlay modal `<twig:SylamentSupport:Modal>` replaces
- [`vendor/filament/support/src/Exceptions/Halt.php`](../../../vendor/filament/support/src/Exceptions/Halt.php) /
  [`Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php)

## Missing

Breadth-fill (Milestone 2 — [roadmap.md](../roadmap.md) Actions row; no
infrastructure blocker):

- [ ] `ActionVariant` enum + `button()`/`link()`/`iconButton()` view-swap — MVP
  ships the button variant only (`->url()` renders a plain link today).
- [ ] `ActionGroup`, nested/parent actions, the `cachedMountedActions` nesting
  index (collapsed to one slot for MVP), bulk/selected-records actions,
  partial re-render (`forceRender`/`store($this)` — no UX analog).
- [ ] The writable `$mountedActionData` bag split off `$mountedActions` — the
  form-in-modal seam (Decision 7).
- [ ] Concrete record actions beyond the Delete shape (`Create`/`Edit`/`View`/
  `Attach`/…) and import/export (🎨 queue → Messenger).
- [ ] `HasBadge`/`HasBadgeTooltip` + badge variant, `HasKeyBindings`,
  `CanBeLabeledFrom`, `HasGroupedIcon`/`HasTableIcon`, `CanBeSorted`,
  `CanBeOutlined`, `BelongsToGroup`/`BelongsToTable`, selected-records concerns.
- [ ] `Arrayable`/`toArray()`/`fromArray()` JS-serialization surface (Livewire
  snapshot transport; also needed by notifications).
- [ ] Redirect after a successful call — will return a `RedirectResponse` from
  the LiveAction, not Livewire's `store($this)`.
- [ ] `HasLifecycleHooks`' Laravel `Event::dispatch` calls
  (`ActionCalling`/`ActionCalled`) — dropped for MVP; revisit with the Symfony
  event dispatcher if a consumer needs them.

Blocked on infrastructure / other threads:

- [ ] Form-in-modal (Edit shape): `getMountedActionSchema`, the nested-writable
  `mountedActions.{i}.data` LiveProp, schema validation, and the
  `ValidationException`-keeps-modal-open flow — needs
  [`Schemas\Concerns\HasState::getState()`](../../../packages/schemas/src/Concerns/HasState.php)
  to grow an `afterValidate` mode first (Decision 1).
- [ ] `CanBeAuthorized` (Laravel Gate → Symfony Security/voters) — its own 🔒
  sub-epic (panels step 10, [roadmap.md](../roadmap.md); no infrastructure.md
  anchor yet); `disabled()`/`tooltip()` auth coupling stays stubbed until it
  lands.
- [ ] Real Doctrine record resolution — the host `resolveRecordUsing` wired to
  `BelongsToEntity`, plus a table-driven variant — blocked on 🔶 Doctrine
  record-binding (roadmap step 5 / live-state T6; no infrastructure.md anchor
  yet).
- [ ] Full color math for `modalIconColor()` / custom-palette arrays — blocked
  on [support-managers](../infrastructure.md#support-managers) (`ColorManager`);
  today an array color degrades to gray in the modal icon, same gap as
  [icon-button.md](../support/icon-button.md).
- [ ] Notification delivery — only the `CanNotify` config API ships;
  `sendSuccessNotification()`/`sendFailureNotification()` stay no-op seams
  until the notifications bundle lands.

## Divergences

- **Native `<dialog>` modal instead of Filament's Alpine overlay.**
  `<twig:SylamentSupport:Modal>` (`support`) is a LiveComponent-agnostic
  `<dialog>` driven by the `sylament--support--modal` Stimulus controller — the
  browser supplies focus-trap, ESC, top-layer stacking, and `::backdrop`,
  replacing Filament's hand-rolled Alpine object (focus-trap plugin,
  click-away, refcounted scroll-lock, transitions, a stacking model). Filament's
  single `sync-action-modals` dispatch becomes two explicit id-matched browser
  events, `sylament-open-modal` / `sylament-close-modal` (the section-controller
  precedent — see
  [infrastructure.md#stimulus-controllers](../infrastructure.md#stimulus-controllers)).
  Slide-over/arbitrary-width/nesting need re-verifying against `<dialog>` before
  breadth-fill (Decision 2).
- **`Action` is a config object; the host `HasActions` trait carries behavior
  (Decision 5).** Filament's `Action` invokes its own mount/call machinery
  inline (via `InteractsWithActions` methods on `$this->getLivewire()`); UX
  LiveActions must be public methods declared directly on the
  `#[AsLiveComponent]` class, so the mount/call/unmount loop lives on
  `Sylament\Actions\Concerns\HasActions` and `Action` is reduced to a
  resolved-and-discarded config value, re-resolved every round-trip via a host
  `{name}Action()` method — the one shape compatible with UX, and a real
  divergence from Filament's self-contained-action mental model.
- **`HasActions` / `InteractsWithActions` name swap.** Trait vs. contract names
  are inverted from Filament (`Has*` reserved for traits, the interface names
  the behavior) — recorded in [renames.md](../renames.md), including the "grep
  trap" note that both names exist on both sides with opposite meanings.
- **Single, non-writable `$mountedActions` (Decision 7).** Filament's
  `$mountedActions` is a Livewire-checksum-guarded stack; ours is a
  `#[LiveProp]` with no `writable: true` at all, because confirm-only mounting
  carries no user-editable form data — the descriptor (`name`/`arguments`/
  `context`) is mutated only from inside the LiveActions, closing the tampering
  hole for free. It is also collapsed to one slot instead of Filament's stack
  (no nested/parent actions yet). The split into a server-controlled stack plus
  a separate writable `$mountedActionData` bag is the form-in-modal seam,
  deliberately not built yet (see Missing).
- **`getLabel()` default uses `before('.')`, not `Field`'s `afterLast('.')`.**
  Ported byte-for-byte from Filament's own `Actions\Concerns\HasLabel`; the two
  Sylament label defaults (`Action`'s and the forms `Field`'s) genuinely
  differ because Filament's own do, and are not reconciled.
- **`resolveRecordUsing` lives on the host, not the action (Decision 3).**
  Doctrine record-binding (`BelongsToModel`/`BelongsToEntity`) is roadmap step 5
  / live-state T6, not built yet. `InteractsWithRecord` on `Action` is trimmed
  to a plain `record()`/`getRecord()` pair; the host's
  `HasActions::resolveRecordUsing(Closure $callback)` supplies a synthetic
  in-memory resolver that `callMountedAction()` calls with
  `context['recordKey']` (a scalar) to re-inflate the record and inject it via
  `$action->record(...)` before `call()`. Proven on `RecordActionHost`'s
  in-memory `$rows` array — the same tracer-bullet move forms fields used ahead
  of Doctrine. Real Doctrine resolution replaces this closure wholesale when T6
  lands; the call-site shape is not expected to change.
- **`Halt`/`Cancel` ported bare into `Sylament\Support\Exception`.** Both are
  plain `Exception` subclasses with no DB-transaction-rollback behavior.
  Filament's `Halt`/`Cancel` (also homed in its support package) exist partly to
  unwind a Laravel DB transaction cleanly; Doctrine's unit-of-work defers
  flush/commit until an explicit `flush()`, so there is no open transaction to
  roll back at throw time — revisit alongside Doctrine record-binding if a
  consumer needs transactional semantics. `callMountedAction()` still honors
  both faithfully: `Halt` keeps the action mounted (modal stays open, no
  notification), `Cancel` unmounts silently.
- **Closure injection name `liveComponent`, not Filament's `livewire`.**
  `Action::resolveDefaultClosureDependencyForEvaluationByName()` resolves a
  `$liveComponent` parameter to `BelongsToLiveComponent::getLiveComponent()` —
  mirrors the schemas `$liveComponent` precedent
  (`getLivewire()` → `getLiveComponent()` is a systematic translation per
  [renames.md](../renames.md), not a rename) rather than reintroducing
  Filament's Livewire-specific parameter name.
- **Notifications: config-only, `sendSuccess/FailureNotification` no-op seams
  (Decision 4).** `CanNotify` ports the full getter surface
  (`successNotificationTitle()`/`failureNotificationTitle()`/…) faithfully, but
  `sendSuccessNotification()`/`sendFailureNotification()` are no-op stubs — the
  success/failure branch in `callMountedAction()` still runs (`getStatus()`
  still gates which one fires), it just renders no toast until the
  notifications bundle lands.
- **`CanBeDisabled` drops the authorization branches (Decision 9).** Filament's
  `isDisabled()` also consults `hasAuthorizationNotification()` /
  `hasAuthorizationTooltip()` / `isAuthorized()`; ours is
  `evaluate($isDisabled) || isHidden()` only, restored when the 🔒 Security
  sub-epic lands (see Missing).
- **`CanBeHidden` drops the table/group cache and `ActionGroup` iteration.**
  Filament's version also caches visibility per table row and recurses into
  `ActionGroup` children; the MVP `isHidden()`/`isVisible()` pair mirrors the
  schemas `CanBeHidden` shape (evaluate-and-return, no cache) since there is no
  table or group yet.
- **`CanOpenUrl` is minimal.** Drops Filament's `hasModal()` guard and the
  Livewire `getDefaultActionUrl()` fallback (neither concept exists yet) and
  `postToUrl()`/`shouldPostToUrl()` (no POST actions in the MVP); a component
  with a URL renders as a plain `<a>` and skips the mount wiring entirely.

## Consumer notes / MVP limitations

- **Record injection is call-time only.** `$record`/`$arguments` are bound
  onto the action inside `HasActions::callMountedAction()`; the instance
  returned by `getMountedAction()` — used for the mount-time
  `isConfirmationRequired()` gate and for render-time modal config — is a
  separately-resolved, unbound instance (see the "config object, re-resolved
  every round-trip" divergence above), so modal-config closures —
  `modalHeading(fn ($record) => …)`, `requiresConfirmation(fn ($record) => …)`
  — evaluate with `$record = null`. Use static modal config in the
  confirm-only MVP; record-aware modal config lands with the full mounting
  stack (breadth-fill).
- **Register `resolveRecordUsing` in the host constructor, not `mount()`.** It
  is a plain, non-serialized `?Closure` property, not a `#[LiveProp]`, and
  `mount()` runs only on the component's initial render — a resolver assigned
  there is gone by the `callMountedAction` round-trip (`RecordActionHost`'s
  fixture constructor is the pattern to copy). The host now throws
  `LogicException` (fail-fast, Filament-faithful —
  `InteractsWithRecord::getRecord()` throws the same way) when an action
  context carries a `recordKey` but no resolver is registered, instead of
  silently resolving to no record.
- **The confirm modal's default icon requires heroicons.** `getModalIcon()`
  defaults to `heroicons:exclamation-triangle` for confirmation-required
  actions — the same shipped-icon-set assumption as the `Section` collapse
  chevron precedent (`heroicons:chevron-up`) and the `symfony/ux-icons`
  dependency both ride on. Consumers must have that icon resolvable via their
  ux-icons config, or set `modalIcon()` explicitly; an unresolvable icon
  throws `Symfony\UX\Icons\Exception\IconNotFoundException` at render.
