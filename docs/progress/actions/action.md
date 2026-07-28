# Action (actions)

`Sylament\Actions\Action` — the base class every actions-package action
extends. It composes `Sylament\Support\Components\ViewComponent` (Twig-backed
rendering) and folds in 17 actions-package concerns — `BelongsToLiveComponent`,
`CanBeDisabled`, `CanBeHidden`, `CanBeOutlined`, `CanNotify`, `CanOpenModal`,
`CanOpenUrl`, `CanRequireConfirmation`, `HasAction`, `HasArguments`,
`HasGroupedIcon`, `HasLabel`, `HasLifecycleHooks`, `HasMountableArguments`,
`HasName`, `HasSize`,
`InteractsWithRecord` — each a
Surface row below with its own detail/divergence spliced in from its own
audit. It also composes two multi-consumer actions-package concerns tracked
outside this file — `BelongsToGroup` (shared with `ActionGroup`, written up in
[`action-group.md`](action-group.md)) and `BelongsToSchemaComponent` (shared
with `ActionGroup`, a `done` row with no detail file in the
[package README](README.md#features), since its whole surface is a two-method
back-reference pair) — plus 6 support-level traits (`HasColor`,
`HasExtraAttributes`, `HasIcon`, `HasIconPosition`, `HasIconSize`,
`HasTooltip`) that stay tracked in the [`support`](../support/README.md)
package and appear here only as reference rows. The port is a deliberate MVP
subset of Filament's `Action` — a single-purpose "confirm and run a closure"
action wired to a live component, not the mounting/modal/form/grouping/wizard
platform Filament's class has grown into.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/Action.php`](../../../vendor/filament/actions/src/Action.php)
  — the base class and the ~50 traits/interfaces it composes
- [`actions/src/Concerns/`](../../../vendor/filament/actions/src/Concerns)
  — the folded concerns audited below

## Surface

Every point of this component and its 14 folded concerns. _Blocked by_ names
what has to land first. _Detail_ links the write-up in [Details](#details)
(deleted once the row is **done**) and/or the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction & `make()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#make-drops-getdefaultname-and-di-construction) |
| `call()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | blocked | bulk actions (`InteractsWithSelectedRecords`), visibility cache | [detail](#call-drops-the-post-call-cleanup) |
| `cancel()` / `halt()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | Doctrine transaction integration | [detail](#cancel-and-halt-drop-the-rollback-flag) |
| `success()` / `failure()` / `getStatus()` + `$status` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#getstatus-drops-the-bulk-record-tally) |
| `resolveDefaultClosureDependencyForEvaluationByName()` + `$evaluationIdentifier` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | tables/HasData/InteractsWithSelectedRecords ports, operation concept | [detail](#resolvedefaultclosuredependencyforevaluationbyname-covers-9-of-16-names) |
| `resolveDefaultClosureDependencyForEvaluationByType()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | todo | schemas/tables/InteractsWithSelectedRecords ports | [detail](#resolvedefaultclosuredependencyforevaluationbytype-is-not-overridden) |
| `getContext()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | tables (`table`/`bulk` flags), modal nesting (`getParentAction()` short-circuit) | [diverges](#getcontext-emits-only-the-schemacomponent-key) |
| Trigger rendering ([`button-action.html.twig`](../../../packages/actions/templates/components/button-action.html.twig) + the [`link-action`](../../../packages/actions/templates/components/link-action.html.twig) / [`badge-action`](../../../packages/actions/templates/components/badge-action.html.twig) / [`icon-button-action`](../../../packages/actions/templates/components/icon-button-action.html.twig) siblings) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`toButtonHtml`/`toLinkHtml`/`toBadgeHtml`/`toIconButtonHtml`) | in progress | — | [detail](#tooltip-icon-position-and-extra-attribute-rendering-are-untested), [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
| Modal rendering ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)) | [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) | in progress | CanOpenModal (footer actions, content, slideover, sticky) | [detail](#the-action-modal-template-only-renders-part-of-the-full-action-modal), [diverges](#the-action-modal-is-an-anonymous-twig-component-not-an-included-view), [diverges](#the-footer-buttons-are-hardcoded-markup-not-rendered-action-instances) |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | — |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) (support) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | — |
| [`HasIconPosition`](../../../packages/support/src/Components/Concerns/HasIconPosition.php) (support) | [`Concerns/HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | done | — | — |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) (support) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | — |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) (support) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |
| [`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) | [`Concerns/BelongsToLivewire.php`](../../../vendor/filament/actions/src/Concerns/BelongsToLivewire.php) | done | — | [diverges](#the-live-component-chain-narrows-inline-and-drops-the-table-and-group-hops) |
| [`CanBeDisabled`](../../../packages/actions/src/Concerns/CanBeDisabled.php) | [`Concerns/CanBeDisabled.php`](../../../vendor/filament/actions/src/Concerns/CanBeDisabled.php) | done | — | [diverges](#canbedisabled-drops-the-authorization-short-circuits) |
| [`CanBeHidden`](../../../packages/actions/src/Concerns/CanBeHidden.php) | [`Concerns/CanBeHidden.php`](../../../vendor/filament/actions/src/Concerns/CanBeHidden.php) | in progress | CanBeAuthorized, ActionGroup, BelongsToTable | [detail](#canbehidden-omits-authorization-group-and-table-cache-integration) |
| [`CanBeOutlined`](../../../packages/actions/src/Concerns/CanBeOutlined.php) | [`Concerns/CanBeOutlined.php`](../../../vendor/filament/actions/src/Concerns/CanBeOutlined.php) | done | — | [diverges](#outlined-styling-is-a-state-class-not-a-color-map) |
| [`CanNotify`](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | in progress | notifications package | [detail](#cannotify-only-ports-notification-titles-not-sending) |
| [`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php) | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | in progress | — | [detail](#only-the-modal-content-config-is-ported-the-behavior-and-footer-action-surface-is-not), [diverges](#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution) |
| [`CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | in progress | — | [detail](#geturl-drops-the-default-action-url-fallback) |
| POST-to-url support | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | skipped | — | [diverges](#post-to-url-support-is-not-ported) |
| [`CanRequireConfirmation`](../../../packages/actions/src/Concerns/CanRequireConfirmation.php) | [`Concerns/CanRequireConfirmation.php`](../../../vendor/filament/actions/src/Concerns/CanRequireConfirmation.php) | done | — | [diverges](#confirmation-is-a-flag-not-a-modal-config-layer) |
| [`HasAction`](../../../packages/actions/src/Concerns/HasAction.php) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | in progress | — | [detail](#hasaction-drops-the-string-click-handler-and-livewire-click-gating) |
| [`HasArguments`](../../../packages/actions/src/Concerns/HasArguments.php) | [`Concerns/HasArguments.php`](../../../vendor/filament/actions/src/Concerns/HasArguments.php) | done | — | — |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | [diverges](#hasgroupedicon-narrows-its-icon-type-to-string) |
| [`label()` / `getLabel()`](../../../packages/actions/src/Concerns/HasLabel.php) | [`label()` / `getLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | — |
| `hiddenLabel()` / `isLabelHidden()` | [`hiddenLabel()` / `isLabelHidden()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | — |
| `translateLabel()` / `disableLabel()` (deprecated) | [`translateLabel()` / `disableLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported) |
| [`HasLifecycleHooks`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) | [`Concerns/HasLifecycleHooks.php`](../../../vendor/filament/actions/src/Concerns/HasLifecycleHooks.php) | in progress | HasSchema / CanSubmitForm (form-mounting concerns) | [detail](#the-form-lifecycle-hooks-and-actioncallingactioncalled-events-are-not-ported) |
| [`HasMountableArguments`](../../../packages/actions/src/Concerns/HasMountableArguments.php) | [`Concerns/HasMountableArguments.php`](../../../vendor/filament/actions/src/Concerns/HasMountableArguments.php) | done | — | [diverges](#action-invocation-is-a-php-side-seam-only) |
| [`HasName`](../../../packages/actions/src/Concerns/HasName.php) | [`Concerns/HasName.php`](../../../vendor/filament/actions/src/Concerns/HasName.php) | done | — | [diverges](#getname-tightens-its-return-type-to-non-nullable-string) |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | [diverges](#hassize-drops-the-optimized-render-fast-path-helper) |
| [`InteractsWithRecord`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | in progress | — | [detail](#the-record-title-and-model-metadata-surface-is-not-ported), [diverges](#record-key-resolution-is-hoisted-to-hasactions) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### `call()` drops the post-call cleanup

Filament's `call()` wraps `$this->evaluate($this->getActionFunction(), $parameters)`
in a `try`/`finally` that, on the way out, deselects table records
(`shouldDeselectRecordsAfterCompletion()` → `getLivewire()->deselectAllTableRecords()`)
and clears the visibility cache (`clearVisibilityCache()`). The port's
[`call()`](../../../packages/actions/src/Action.php) is the bare
`return $this->evaluate($this->getAction(), $parameters);` — no `finally`
block at all. Both branches are unreachable today: bulk-record selection
(`InteractsWithSelectedRecords`, `bulk()`) is not ported, and the port's
[`CanBeHidden`](../../../packages/actions/src/Concerns/CanBeHidden.php) has no
cache to clear (its own docblock records the cache as dropped). **Blocked** on
those two concerns landing; add the `finally` block back in the same change.

### `cancel()` and `halt()` drop the rollback flag

Filament's `cancel(bool $shouldRollBackDatabaseTransaction = false)` and
`halt(bool $shouldRollBackDatabaseTransaction = false)` construct their
exception and call `->rollBackDatabaseTransaction($condition)` on it before
throwing; both
[`Sylament\Support\Exceptions\Cancel`](../../../packages/support/src/Exceptions/Cancel.php)
and [`Halt`](../../../packages/support/src/Exceptions/Halt.php) are plain
`Exception` subclasses with no such flag or method. The port's
[`cancel()`/`halt()`](../../../packages/actions/src/Action.php) take no
parameter and just `throw new Cancel()` / `throw new Halt()`. Covered by
`ActionTest`'s "throws Cancel from cancel() and Halt from halt()" for the
throw itself, but the rollback-control surface has no test because it does
not exist. **Blocked** on Doctrine transaction wiring (there is no database
transaction to roll back yet); restore the parameter and the flag on both
exception classes in the same change that lands it.

### `resolveDefaultClosureDependencyForEvaluationByName` covers 9 of 16 names

Filament's override resolves 16 dependency-name groups: `arguments`, `data`,
`livewire`, `model`, `mountedActions`, `record`, `selectedRecords`/`records`,
`selectedRecordsQuery`/`recordsQuery`, `schema`, `schemaComponent`/`component`,
`schemaOperation`/`context`/`operation`, `schemaGet`/`get`, `schemaSet`/`set`,
`schemaComponentState`/`state`, `schemaState`, `table`. The port's
[`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/Action.php)
resolves 9 of them: `record`, `arguments`, `liveComponent` (the systematic
`livewire` → `liveComponent` rename tracked in
[`../renames.md`](../renames.md) — not a divergence), and — since the
schemas-actions integration — `schema`, `schemaComponent`/`component`,
`schemaGet`/`get`, `schemaSet`/`set`, `schemaComponentState`/`state`, and
`schemaState`, backed by `BelongsToSchemaComponent` plus two state helpers
ported onto `Action` itself: `getSchemaComponentState()` (the upward walk to
the nearest state-path-bearing parent component, vendor L602–621) and
`getSchemaState()` (the first state-path-bearing container; Filament's
`getStateSnapshot()` call maps to Sylament's `Schema::getState()`, which
[schemas' HasState](../../../packages/schemas/src/Concerns/HasState.php)
documents as its `getStateSnapshot` equivalent). `getSchemaState()` drops
Filament's leading `HasExtraItemActions` branch — that contract is unported.
The `get`/`set` arms keep Filament's **non-null-safe**
`getSchemaComponent()` deref (behind an inline PHPStan ignore): a
schema-less action evaluating a `$get`/`$set` closure fatals, exactly as in
Filament, pinned by `ActionTest`. Both arms also chain
`->skipComponentsChildContainersWhileSearching(false)` onto the returned
[`Get`](../schemas/get.md) / [`Set`](../schemas/set.md) utility, Filament-exact:
without it, the utility's default self-skip blocks it from descending into the
bound schema component's *own* child schema, so a container-hosted action's
`$get`/`$set` (e.g. a `Section` with a registered action reading a field
nested inside that same `Section`) would silently fall back to a raw,
uncast `PropertyAccessor` read/write instead of going through the owning
field's `getState()`/`state()` — the call is not redundant, it disables a
self-skip that would otherwise misfire here. `ActionGroup` mirrors Filament's
own group subset (`schema`, `schemaComponent`/`component`, `schemaGet`/`get`,
`schemaComponentState`/`state`, `schemaState` and the two duplicated state
helpers — vendor's `ActionGroup` carries no `set` arm, and its `get` arm does
not chain the skip-toggle call either, unlike `Action`'s; both asymmetries are
vendor-verified and kept). Still missing, each blocked on its backing concern: `data`/`model`
(`HasData`/model metadata), `mountedActions` (mounted-actions introspection
beyond today's `HasActions`), `selectedRecords`/`records` +
`selectedRecordsQuery`/`recordsQuery` (`InteractsWithSelectedRecords` +
`CanFetchSelectedRecords`), `schemaOperation`/`context`/`operation`
(no operation concept — Sylament's `Schema` has no `getOperation()`), and
`table` (`BelongsToTable`). Coverage: `record`/`arguments` via `ActionTest`'s
"calls the action closure…", `liveComponent` via
[`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php),
and the schema names via `ActionTest`'s injection cases (plus
`ActionGroupTest` for the group subset).

### `resolveDefaultClosureDependencyForEvaluationByType` is not overridden

Filament's `Action` also overrides `resolveDefaultClosureDependencyForEvaluationByType()`,
resolving `Builder::class` to the selected-records query, `EloquentCollection::class`/
`Collection::class` to the individually-authorized selected records, and
`Model::class` (or the record's own class) to the current record — so a
closure parameter typed `fn (Model $record)` or `fn (Builder $query)` resolves
without naming the parameter. The port has no equivalent override at all: a
type-hinted `Model`/`Doctrine` parameter falls through to
`Component::resolveDefaultClosureDependencyForEvaluationByType()` (support)
and is not resolved. **Blocked** on the same
schemas/tables/`InteractsWithSelectedRecords` prerequisites as the by-name
resolver above; no test exists because no code exists.

### CanBeHidden omits authorization, group, and table-cache integration

**In:** `hidden(bool|Closure $condition = true)`, `visible(bool|Closure $condition = true)`,
`isHidden()`, `isVisible()` — method-for-method identical to Filament's base
resolution (`evaluate($isHidden)` true wins, else `! evaluate($isVisible)`), and
covered by
[`CanBeHiddenTest`](../../../packages/actions/tests/Unit/Concerns/CanBeHiddenTest.php)
(default visibility, `hidden()`, `visible(false)`, and closure evaluation for
both).

**Missing vs Filament:** Filament's `isHidden()` is not the two-branch resolver
above — it is a caching front door (`cachedIsHidden`, `hasVisibilityCache`,
`prepareVisibilityCache()` / `clearVisibilityCache()`, keyed by
`getCurrentVisibilityRecordKey()` off `getRecord()`/`spl_object_id`) around
`resolveIsHiddenInGroup()`, which itself: (1) short-circuits true when the
action's `ActionGroup` reports `baseIsHidden()`; (2) for an `ActionGroup`
instance, iterates `getActions()` and is hidden only when every child reports
`isHiddenInGroup()`; (3) otherwise falls back to
`! isAuthorizedOrNotHiddenWhenUnauthorized()` — i.e. an action with no explicit
`hidden()`/`visible()` override is hidden by default when the current user is
unauthorized. None of the caching, the `ActionGroup` child-iteration branch, or
the authorization fallback is ported; the port's `isHidden()` is exactly
Filament's `resolveIsHiddenInGroup()` with the `instanceof ActionGroup` branch
and the trailing `isAuthorizedOrNotHiddenWhenUnauthorized()` call removed
entirely (per the trait's own docblock, a deliberate confirm-only MVP cut).
Concretely: a Sylament action with no `hidden()`/`visible()` call is always
visible today, even where Filament would hide it because the user fails
`CanBeAuthorized`'s policy check, or because its `ActionGroup` is empty/hidden.

**Next step:** restore the authorization fallback once `CanBeAuthorized` lands
(it supplies `isAuthorizedOrNotHiddenWhenUnauthorized()`), restore the
group-iteration branch once `ActionGroup` exists, and restore the
record-keyed visibility cache once `BelongsToTable`/`BelongsToGroup` give
`hasTable()`/`getGroup()` a real implementation — `getRecord()` (via
`InteractsWithRecord`) already exists and can back
`getCurrentVisibilityRecordKey()` unchanged.

### CanNotify only ports notification titles, not sending

**In:** `successNotificationTitle()` / `failureNotificationTitle()` setters and
their `getSuccessNotificationTitle()` / `getFailureNotificationTitle()`
evaluated getters — method-for-method identical to Filament's non-bulk path and
covered by
[`CanNotifyTest`](../../../packages/actions/tests/Unit/Concerns/CanNotifyTest.php)
via the
[`CanNotifyFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanNotifyFixture.php).
`sendSuccessNotification()` / `sendFailureNotification()` exist and are called
from
[`HasActions::callMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php)
exactly where Filament's `callMountedAction()` calls them, and the test confirms
they are safe, chainable no-ops.

**Missing vs Filament:** the send methods are pure stubs — neither builds nor
sends a `Notification`; the port has no notifications bundle yet, so there is no
`Notification::make()` to call. Everything downstream of that construction is
absent too: the `successNotification()` / `failureNotification()` closure
overrides (the `Notification|Closure|null` setters that let a user swap the
whole notification object), `failureNotificationBody()`, and the
`isSuccessNotificationDisabled` / `isFailureNotificationDisabled` toggle that
Filament derives from passing `null` to those setters. `unauthorizedNotification()`
/ `unauthorizedNotificationTitle()` / `sendUnauthorizedNotification()` (keyed off
Laravel's `Auth\Access\Response`) and `rateLimitedNotification()` /
`rateLimitedNotificationTitle()` / `sendRateLimitedNotification()` (keyed off
`DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException`) are also
absent — there is no authorization-response or rate-limiting concern in the
actions port yet for either to key off of. The bulk-only surface
(`getFailureNotificationNamedInjections()`, `missingBulkAuthorizationFailureNotificationMessage()`,
`missingBulkProcessingFailureNotificationMessage()`, and the bulk message
formatting inside `getFailureNotificationBody()`) has no consumer either, since
there is no `BulkAction` in the port yet. The deprecated
`successNotificationMessage()` / `failureNotificationMessage()` aliases are
dropped outright — Filament itself deprecates them in favor of the `*Title()`
setters this trait already carries, so nothing is lost.

**Next step:** once the `notifications` package ships a `Notification` type,
rewire `sendSuccessNotification()` / `sendFailureNotification()` to build one
(`success()`/`when(...)->warning()/danger()`, `title()`, `persistent()`) and
`send()` it when titled, exactly as Filament does for the non-bulk path; add the
`successNotification()` / `failureNotification()` override setters and
`failureNotificationBody()` alongside. `unauthorizedNotification*` and
`rateLimitedNotification*` wait on an authorization-response equivalent and a
rate-limiting concern respectively; the bulk injections wait on `BulkAction`.

### Only the modal-content config is ported; the behavior and footer-action surface is not

**Ported and test-covered** (`tests/Unit/Concerns/CanOpenModalTest.php`): the
seven content-config properties and their getters — `modalHeading` (falls back
to `getLabel()`), `modalDescription` (falls back to Filament's confirmation
string, `'Are you sure you would like to do this?'`, when
`isConfirmationRequired()` — the fallback that makes confirmation imply a
modal through the general predicate), `modalIcon` (falls back to
`heroicons:exclamation-triangle` only when `isConfirmationRequired()`),
`modalIconColor` (falls back to `getColor() ?? 'primary'`),
`modalSubmitActionLabel` (`'Confirm'`/`'Submit'` by confirmation state),
`modalCancelActionLabel` (`'Cancel'`), and `modalWidth` (`'md'`). These are the
exact fields [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)
reads to drive `<twig:SylamentSupport:Modal>`, and `ActionModalRenderTest`
exercises the full mount → render path through `RecordActionHost`. The
**presence/visibility surface** is ported too, Filament-exact: `modal()` /
`modalHidden()` / `hasModal()` / `hasCustomModalPresence()` /
`shouldOpenModal(?Closure $checkForSchemaUsing = null)` plus the
`getCustomModalHeading()` / `hasCustomModalHeading()` / `hasModalDescription()`
predicates it reads —
[`HasActions::mountedActionShouldOpenModal()`](has-actions.md#the-modal-open-gate-is-null-safe)
now delegates to `shouldOpenModal()`, so an action carrying only
`modalHeading()` mounts and opens its modal instead of auto-running.
`shouldOpenModal()`'s content arms (`hasModalContent()` /
`hasModalContentFooter()`) are still absent along with the content surface
itself (below); the `checkForSchemaUsing` slot exists but no caller passes it
until the action-schema integration.

**Missing vs Filament** — Filament's trait is ~800 lines; the port keeps ~130.
Absent entirely, with no property, setter, or getter:

- **Footer action composition**: `modalFooterActions()` / `extraModalFooterActions()`
  / `registerModalActions()` / `modalActions()` (deprecated alias) / `getModalFooterActions()`
  / `getModalActions()` / `getModalAction()` / `getVisibleModalFooterActions()` /
  `prepareModalAction()` / `prepareModalActionGroup()` / `makeModalAction()` /
  `makeModalSubmitAction()`. The port's confirm/cancel buttons are two `<button>`
  elements hardcoded in the template (`live_action('callMountedAction')` /
  `live_action('unmountAction')`), not `Action` objects built by
  `getModalSubmitAction()` / `getModalCancelAction()` — so `modalSubmitAction()`
  and `modalCancelAction()` (the `Action|bool|Closure` customization hooks) don't
  exist either. This whole surface composes `ActionGroup` (`todo`, no file yet)
  and calls `getSchemaContainer()` / `getTable()` / `isWizard()` on the host
  action, none of which exist on Sylament's `Action` yet — real parity here
  waits on `ActionGroup` and the schema/table wiring, not just more trait code.
- **Slide-over and layout**: `slideOver()` / `slideOverPosition()` /
  `isModalSlideOver()` / `getModalSlideOverPosition()`, `modalAlignment()` /
  `getModalAlignment()`, `modalFooterActionsAlignment()` /
  `getModalFooterActionsAlignment()`, `stickyModalFooter()` / `stickyModalHeader()`
  / `isModalFooterSticky()` / `isModalHeaderSticky()`.
- **Custom body content**: `modalContent()` / `modalContentFooter()` (+ the
  deprecated `modalFooter()` alias) / `getModalContent()` / `getModalContentFooter()`
  / `hasModalContent()` / `hasModalContentFooter()`. A Sylament action can only
  show the fixed heading/description/icon layout — no arbitrary Twig/Markup body.
- **Interaction chrome toggles**: `modalClickThrough()` / `isModalClickThrough()`,
  `closeModalByClickingAway()` / `isModalClosedByClickingAway()`,
  `closeModalByEscaping()` / `isModalClosedByEscaping()`, `modalCloseButton()` /
  `hasModalCloseButton()`, `modalAutofocus()` / `isModalAutofocused()`. Filament
  defaults these from static `ModalComponent` properties; Sylament's
  `<twig:SylamentSupport:Modal>` primitive has its own fixed Stimulus-driven
  open/close/escape/focus-restore behavior instead (see
  `ModalRenderTest`), so these toggles have no host to bind to yet.
- **Deprecated aliases**: `centerModal()`, `modalButton()`, `modalSubheading()`,
  `extraModalActions()`, `makeExtraModalAction()` are not carried over — expected;
  Sylament has no deprecation-shim policy and these all have live equivalents
  above (themselves unported).
- `getLivewireCallMountedActionName()` (returns `null` in Filament's trait,
  overridden elsewhere) has no Sylament counterpart — the port's template calls
  the live-component action name directly (`callMountedAction`) rather than
  through this indirection.

**Type-shape narrowing** (not a functional gap, just less general): `modalIcon`
is `string|Closure|null` here vs Filament's `string|BackedEnum|Htmlable|Closure|null`
(no `Heroicon` enum or `Markup`-typed icon exists in Sylament yet); `modalWidth`
is a bare string (`'md'`) vs Filament's `Width` enum (no `Width` enum ported to
Sylament yet — every width value here is just whatever string a template's CSS
happens to key on, e.g. `sy-modal-md`).

**Next step**: land `ActionGroup` and the action-to-schema/table wiring first —
they gate the entire footer-actions surface (`getModalFooterActions()` and
everything it calls). The presence/visibility, slide-over/layout, and body-content
groups can each be picked up independently once someone decides they're wanted;
none of them are blocked on anything else in this repo today.

### getUrl() drops the default-action-url fallback

Ported and test-covered: `url()`, `openUrlInNewTab()`, `getUrl()`'s closure
evaluation, `hasUrl()`, `shouldOpenUrlInNewTab()`, and the modal-precedence
guard — `getUrl()` returns `null` outright when `hasModal()` is true, with
Filament's exact precision: the guard reads `hasModal()` (an explicit
`->modal()` call), **not** `shouldOpenModal()`, so `->url(…)` +
`->modalHeading(…)` still renders a link while `->url(…)` + `->modal()`
suppresses the URL. Exercised in
[`CanOpenUrlTest`](../../../packages/actions/tests/Unit/Concerns/CanOpenUrlTest.php)
against the
[`CanOpenUrlFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanOpenUrlFixture.php)
(which stubs `hasModal()`, since the trait expects the composing class to
supply it — `Action` does via `CanOpenModal`) plus `Action`-level precedence
cases.

Missing vs Filament: when no explicit `url` closure resolves a value,
Filament's `getUrl()` falls back to
`$this->getHasActionsLivewire()?->getDefaultActionUrl($this)`; that hook stays
with the deferred
[`getDefaultAction*` host-hook cluster](has-actions.md#the-getdefaultaction-host-override-hooks-are-not-ported).
Filament's `Action::render()` also consults a plain introspection helper,
`hasShouldOpenUrlInNewTab()`, when deciding button vs. link markup; Sylament
has no equivalent (only the evaluated `shouldOpenUrlInNewTab()` exists).

Next step: restore the `getDefaultActionUrl()` fallback alongside the host-hook
cluster; add `hasShouldOpenUrlInNewTab()` with the optimized-render pipeline
that consumes it.

### HasAction drops the string click-handler and Livewire-click gating

Ported: the closure branch only. `action(?Closure $action): static` / `getAction(): ?Closure` round-trip a single closure and `Action::call()` evaluates it, injecting `record`/`arguments`/`liveComponent` — covered by `ActionTest` (`round-trips the action closure`, `calls the action closure, injecting the record and arguments`).

Missing vs Filament's `HasAction`:
- **String action values.** Filament's `$action` is `Closure|string|null`; a string is used verbatim as the client-side click-handler expression (`getLivewireClickHandler()` returns it as-is, bypassing the `mountAction`/`callMountedAction` round-trip). Sylament's `action` is `?Closure` only, so there is no way to hand the button a raw click-handler string — `button-action.html.twig` always emits `live_action('mountAction', …)` when there's no URL. This isn't a pure Alpine→Stimulus idiom swap: no Stimulus-side passthrough mechanism exists yet to receive an arbitrary string handler.
- **`livewireClickHandlerEnabled()` / `isLivewireClickHandlerEnabled()`.** Entirely absent — no way to force-disable the live dispatch. Filament's default logic also stands down when `getUrl()` is filled (ported, hardcoded in the template) or when `canSubmitForm()` is true; the latter has no Sylament equivalent because no `CanSubmitForm` concern exists yet (forms/actions form-submission integration is unbuilt), so this half of the gap is blocked on that future work rather than a simple omission.
- **`hasAction(): bool`.** Helper predicate not ported; trivially derivable from `getAction() !== null` at call sites today, but no equivalent method or test exists.

Next step: decide whether the string-click-handler passthrough is worth porting before any Stimulus-side generic handler exists; land `CanSubmitForm` (or its Sylament equivalent) before restoring the full `isLivewireClickHandlerEnabled()` gating logic.

### The form-lifecycle hooks and ActionCalling/ActionCalled events are not ported

**In:** `before(?Closure)` / `after(?Closure)` and their runners `callBefore()` /
`callAfter()`, each evaluating the stored closure through `EvaluatesClosures`.
Covered by
[`HasLifecycleHooksTest`](../../../packages/actions/tests/Unit/Concerns/HasLifecycleHooksTest.php)
via the
[`HasLifecycleHooksFixture`](../../../packages/actions/tests/Fixtures/Concerns/HasLifecycleHooksFixture.php):
both hooks fire in order when set, and both `call*` methods are a safe no-op
(return `null`) when unset. `Action` (`../../../packages/actions/src/Action.php`)
is the sole consumer.

**Missing vs Filament:** four hooks and their runners —
`beforeFormFilled()` / `callBeforeFormFilled()`,
`afterFormFilled()` / `callAfterFormFilled()`,
`beforeFormValidated()` / `callBeforeFormValidated()`,
`afterFormValidated()` / `callAfterFormValidated()` — are entirely absent, along
with the `beforeFormFilled` / `afterFormFilled` / `beforeFormValidated` /
`afterFormValidated` closure properties that back them. These fire around an
action's mounted-form fill/validate cycle, which has no port yet: the actions
package composes no `HasSchema`-equivalent or `CanSubmitForm`
(`vendor/filament/actions/src/Concerns/HasSchema.php` and
`CanSubmitForm.php` have no Sylament counterpart).
Additionally, Filament's `callBefore()` / `callAfter()` dispatch
`ActionCalling` / `ActionCalled` events (`Event::dispatch(...)`,
the latter from a `finally` block so it always fires); the port has no
`Sylament\Actions\Events` namespace and dispatches nothing. **Next step:** the
four form hooks are **blocked** on the form-mounting concerns (`HasSchema` /
`CanSubmitForm`) landing in `actions`; the event dispatch has no named
prerequisite yet and should be scoped (Symfony `EventDispatcher` port of
`ActionCalling`/`ActionCalled`) when the mount/call pipeline is designed.

### The record title and model metadata surface is not ported

**In:** `record(mixed $record): static` and `getRecord(): mixed`, the latter
running the stored value through `evaluate()` — round-tripping a literal and a
closure is covered by
[`InteractsWithRecordTest`](../../../packages/actions/tests/Unit/Concerns/InteractsWithRecordTest.php).

**Missing vs Filament:** everything else the trait carries is absent —
`model()` / `getModel()` / `getCustomModel()`, `modelLabel()` /
`pluralModelLabel()` and their Title-Case variants, `recordTitle()` /
`recordTitleAttribute()` and their `getRecordTitle()` / `getCustomRecordTitle()`
/ `hasCustomRecordTitle()` family, `resolveRecordKey()`, `hasRecord()`, and the
`ensureCorrectRecordType()` custom-model guard. Of the fallback chain Filament
walks when no record is set directly — `getGroup()?->getRecord()`,
`getSchemaContainer()?->getRecord()`, `getSchemaComponent()?->getRecord()`,
and the `getHasActionsLivewire()?->getDefaultActionRecord()` tail — only the
group hop is ported: the schema accessors exist on `Action` now
(`BelongsToSchemaComponent`), but schemas' `Schema`/`Component` have no
`getRecord()` to hop to, and there is no default-action-record host hook. The
`$record` property is typed `mixed`
instead of Filament's `Model | string | array | Closure | null`, since there is
no Doctrine-entity-aware resolution to type it against.

**Next step:** land `getModel()` / `getModelLabel()` (needs a Table or
schema-container equivalent to fall back to) and `getRecordTitle()` (needs the
same, plus a `getCustomRecordTitle()` port) once the `tables`/`schemas`
integration points they read from exist; tighten `$record`'s type once a
Doctrine entity boundary is decided.

### The action-modal template only renders part of the full action modal

**In:** a ~14-line `<twig:SylamentSupport:Modal>` include driven entirely by
[`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php)'s
current (minimal) surface — `heading`, `description`, `icon`, `iconColor`, and
`width` pass straight through the evaluated getters, and the footer renders two
fixed `<button>` elements wired to `live_action('callMountedAction')` /
`live_action('unmountAction')`, labeled from `getModalSubmitActionLabel()` /
`getModalCancelActionLabel()`. Rendered by the host's
`<twig:SylamentActions:Modals />` tag (see the `RecordActionHost` /
`ActionHost` Twig fixtures) once the mounted action's `shouldOpenModal()`
holds — Filament's own gate. Covered by
[`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php),
which asserts the modal class, heading, submit/cancel labels, the confirmation
icon `<svg>`, and both `live_action` data attributes render through a full
`mountAction` → render cycle.

**Missing vs Filament:** `action-modal.blade.php` is a general-purpose action
modal, not a confirmation-only one, and most of that generality has no
Sylament counterpart yet:

- **No dynamic content or schema/form rendering.** Filament interpolates
  `$action->getModalContent()`, the mounted action's schema
  (`$this->getMountedActionSchema()`), and `$action->getModalContentFooter()`
  between four pairs of render hooks
  (`MODAL_CUSTOM_CONTENT_{BEFORE,AFTER}`, `MODAL_SCHEMA_{BEFORE,AFTER}`,
  `MODAL_CUSTOM_CONTENT_FOOTER_{BEFORE,AFTER}`). `CanOpenModal` has no
  `modalContent` / `modalContentFooter` properties and there is no schema slot
  at all — the template has no `content` block to fill, so a form-collecting
  action cannot render its fields inside this modal today.
- **Only two fixed footer buttons, not `getVisibleModalFooterActions()`.**
  Filament resolves an ordered `array<Action|ActionGroup>` (custom submit/cancel
  actions, `extraModalFooterActions()`, alignment-driven reversal) and renders
  each as a real `Action`. Sylament's `CanOpenModal` exposes no
  `getModalSubmitAction()` / `getModalCancelAction()` / `getModalFooterActions()`
  — the template hardcodes exactly submit + cancel as plain `<button>` markup,
  so `extraModalFooterActions()`, custom `modalSubmitAction()` /
  `modalCancelAction()` overrides, and footer alignment don't exist to be
  rendered.
- **No modal-chrome configuration reaches the `Modal` component.** Filament's
  `<x-filament::modal>` call also threads `alert`, `alignment`, `autofocus`,
  `click-through`, `close-button`, `close-by-clicking-away`,
  `close-by-escaping`, `slide-over` (+ position), `sticky-footer`,
  `sticky-header`, and the extra window/overlay attribute bags.
  [`Modal/index.html.twig`](../../../packages/support/templates/components/Modal/index.html.twig)
  accepts none of these props, and `CanOpenModal` tracks none of the
  corresponding state (no `isModalSlideOver`, `isModalFooterSticky`,
  `isModalHeaderSticky`, `getModalAlignment`, `hasModalCloseButton`,
  `isModalClosedByClickingAway/Escaping`, `isModalAutofocused`,
  `isModalClickThrough`, extra attribute bags) — a confirmation modal always
  renders as a plain, non-slide-over, click-to-dismiss dialog.
- **No form-wrapper wiring.** Filament conditionally sets `wire:submit.prevent`
  to `getLivewireCallMountedActionName()` when `hasFormWrapper()` is true, and
  actually submits the form via that binding; Sylament's footer submit button
  always calls `callMountedAction` directly via `live_action`, and
  `hasFormWrapper()` / `getLivewireCallMountedActionName()` don't exist on the
  port (there is no forms integration in `actions` yet).
- **No nested-modal-close event chain.** Filament computes a
  `x-on:modal-closed` handler that also unmounts every parent action named in
  `getParentActionsToCancelOnClose()`, for stacked/nested actions. Sylament's
  `HasActions::unmountAction()` always dispatches a single
  `sylament-close-modal` event for the current modal id — nested action
  stacking (`getNestingIndex()`, per-parent cancellation) is not modeled.
- **No render hooks.** Filament brackets custom content and the schema in four
  `FilamentView::renderHook()` pairs; Sylament has no render-hook facility, so
  none of that extensibility point exists (a broader gap than this template
  alone, noted here because it is one of the things the Filament view relies
  on).

**Next step:** this row tracks the *rendering* gap; the state to drive it lives
in `CanOpenModal`, which needs to grow `modalContent` / `modalContentFooter`, a
`getModalFooterActions()`-style API, and the close/slideover/sticky/alert flags
before this template (or a fuller `action-modal.html.twig` alongside it) can
stop being confirmation-only. The support `Modal` component needs the matching
props added in lockstep.

### Tooltip, icon position, and extra-attribute rendering are untested

**In:** the template wires `component.getLabel ?? component.getName`, the
`sy-size-*` / `sy-color-*` classes off `getSize()` / `getColor()` (falling back
to an arbitrary custom size token, matching the support
[`Button`](../../../packages/support/templates/components/Button/index.html.twig)
primitive's own fallback), `isDisabled` + `aria-disabled`, the `<a>` vs
`<button>` tag and `target="_blank"` off `getUrl()` / `shouldOpenUrlInNewTab()`,
and the `mountAction` `live_action()` wiring for the non-URL case — all four
covered by
[`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php).

**Missing test coverage:** three branches the template exercises have no
assertion anywhere in the `actions` suite:

- `title: component.getTooltip ?: false` — no test renders an `Action` with
  `->tooltip(...)` and checks for the `title` attribute.
- `{%- if iconHtml and component.getIconPosition.value == 'after' %}` — the
  existing icon test (`toContain('<svg')`) only proves an icon renders, not
  that `iconPosition('after')` actually moves it past the label.
- `component.getExtraAttributes` merged as the attrs() second source — no test
  renders an `Action` with `->extraAttributes([...])` and checks it lands on
  the root element.

**Next step:** add the three cases to `ActionRenderTest`; no code change is
expected, this is coverage-only.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

### `make()` drops `getDefaultName()` and DI construction

Filament's `make()` resolves `$name ?? static::getDefaultName()` and
constructs through `app(static::class, ['name' => $name])`. The port's
[`make()`](../../../packages/actions/src/Action.php) is
`new static($name); $action->configure();` — no `getDefaultName()` hook
(Filament's own base `Action::getDefaultName()` always returns `null`; no
shipped subclass overrides it either, so nothing observable is lost yet), and
plain instantiation instead of container construction (the systematic
`app()->make()` → Symfony DI translation collapses to plain instantiation
here, per CLAUDE.md, because the action needs no injected services). The
exception behavior itself is unchanged from Filament: both throw a
`LogicException` from `getName()` — not `make()` — when the name is still
blank, per `HasName` (audited on its own row; not re-litigated here).
Reversible by adding a `getDefaultName()` hook and switching to
`Invoker`/container construction the moment a subclass needs an injected
dependency.

### `getStatus()` drops the bulk-record tally

Filament's `getStatus()` falls through, when `$status` is unset, to
`canAccessSelectedRecords()` and a `successfulSelectedRecordsCount ===
totalSelectedRecordsCount` tally kept by `InteractsWithSelectedRecords` for
bulk actions. The port's
[`getStatus()`](../../../packages/actions/src/Action.php) is
`return $this->status ?? ActionStatus::Success;` — bulk actions do not exist
in the port (no `bulk()`, no `InteractsWithSelectedRecords`), so there is no
tally to fall through to; every non-bulk action correctly defaults to
`Success` until `success()`/`failure()` is called, which is what
`ActionTest`'s "defaults the status to Success and flips to Failure on
failure()" covers. Reversible in the same change that ports bulk actions.

### The view switch renders through Twig templates, not toHtml() fast paths

Filament's five-way view switch is ported: `badge()`/`button()`/`grouped()`/
`iconButton()`/`link()` each point `template()` at one of the five template
constants (`BADGE_TEMPLATE`/`BUTTON_TEMPLATE`/`GROUPED_TEMPLATE`/
`ICON_BUTTON_TEMPLATE`/`LINK_TEMPLATE` — the systematic `*_VIEW` → `*_TEMPLATE`
translation), with the `isBadge()`/`isButton()`/`isIconButton()`/`isLink()`
predicates comparing `getTemplate()` against them (no `isGrouped()` —
Filament has none either). The default stays `BUTTON_TEMPLATE` via
`$defaultTemplate`, so `ActionGroup` can push `GROUPED_TEMPLATE` as a default
while an explicit switcher call survives the push — Filament-identical
precedence, pinned by `ActionTest`. Each shape has its own template beside
[`button-action.html.twig`](../../../packages/actions/templates/components/button-action.html.twig):
[`link-action.html.twig`](../../../packages/actions/templates/components/link-action.html.twig)
and [`badge-action.html.twig`](../../../packages/actions/templates/components/badge-action.html.twig)
mirror the button skeleton on `sy-ac-link`/`sy-ac-badge` classes (CSS in
`packages/actions/assets/src/styles/components/`, riding support's single
Tailwind compile), and
[`icon-button-action.html.twig`](../../../packages/actions/templates/components/icon-button-action.html.twig)
composes the support
[`IconButton`](../../../packages/support/templates/components/IconButton/index.html.twig)
primitive through the `{% component %}` tag (the only syntax that can spread a
computed attribute map), building its mount wiring with
`stimulus_action('live', 'action', …).toArray()` — byte-equivalent to what
`live_action()` emits. All five are exercised by
[`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php)
/ [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php)
/ [`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php).

What stays divergent: **no string fast paths** — Filament's `toHtml()`
dispatch, the `toBadgeHtml()`/`toButtonHtml()`/… renderers and their
"optimized" hand-built-HTML variants, and the `toEmbeddedHtml()` fallback have
no counterpart; every shape renders through Twig via `ViewComponent::render()`.
**`badge()` is argless** — Filament's `badge(string|Closure|null $badge)`
doubles as the badge-*text* setter via `func_num_args()`; `HasBadge` is
deferred, so the overload widens when it lands. **No `toArray()`/`fromArray()`**
serialization, and no `renderModal()`/`toModalHtmlable()` — the confirmation
modal ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig))
is rendered host-side, driven by `CanOpenModal`'s getters, not by an `Action`
method.

### The rest of Filament's `Action` surface is unported

Filament's `Action` composes roughly 50 traits/interfaces; 14 are folded into
this file's Surface table (ported, in some form) and 6 more are the
support-level traits referenced above. Everything else has no Sylament
equivalent at all today — captured here as one list rather than one row each,
since none of it has started:

- **Mounting & modal chrome beyond `CanOpenModal`'s data**: `CanBeMounted`,
  `CanClose`, `HasKeyBindings`, `CanBeBooted`, `renderModal()`/
  `toModalHtmlable()`, `HasExtraModalOverlayAttributes`/
  `HasExtraModalWindowAttributes`.
- **Forms/schemas/infolists integration**: `HasSchema`, `HasInfolist`,
  `HasData`, `HasWizard`, `CanSubmitForm` (`BelongsToSchemaComponent` and the
  `schema*` closure-dependency names landed with the schemas-actions
  integration — see the Details section above).
- **Tables/bulk-action integration**: `BelongsToTable`, `HasTableIcon`,
  `InteractsWithSelectedRecords`, `CanFetchSelectedRecords`,
  `CanDeselectRecordsAfterCompletion`, `bulk()`/`isBulk()`.
- **Grouping & nesting**: `CanBeSorted`,
  `CanCallParentAction`, `HasParentActions`, `parentAction()`/
  `getParentAction()`, `nestingIndex()`/`getNestingIndex()`, `getClone()`
  (`BelongsToGroup` itself is ported — see [action-group.md](action-group.md)).
- **Authorization & policy**: `CanBeAuthorized`, `hasAuthorization()`.
- **Rate limiting & transactions**: `CanBeRateLimited`,
  `CanUseDatabaseTransactions` (see the `cancel()`/`halt()` rollback-flag
  detail above).
- **Redirect/dispatch/mark conveniences**: `CanRedirect`, `CanDispatchEvent`,
  `markAsRead()`/`markAsUnread()`, `livewireTarget()`, custom
  `alpineClickHandler()`/`actionJs()` (the port's click wiring is the single
  `live_action('mountAction', …)` call baked into the template).
- **Rendering variants**: `HasBadge`, `HasBadgeTooltip`,
  `CanBeLabeledFrom`, and the `toArray()`/`fromArray()` serialization pair
  (folded into the view-switch divergence above).

None of these is a per-row **blocked**/**todo** entry because none has any
ported surface yet to anchor a row to — they will earn Surface rows (and
likely their own detail files, per the
[one-row-per-file rule](../README.md#one-row-per-file-one-file-per-thing))
the moment work starts on each.

### The live-component chain narrows inline and drops the table and group hops

Filament's `getLivewire()` is a multi-step resolver — explicit `$livewire`,
then `getSchemaContainer()?->getLivewire()`,
`getSchemaComponent()?->getLivewire()`, `getTable()?->getLivewire()`,
`getGroup()?->getLivewire()` — plus a second method,
`getHasActionsLivewire()`, narrowing the generic result to
`Contracts\HasActions`. Sylament's
[`getLiveComponent()`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php)
now walks the two schema hops lazily: explicit `$liveComponent`, then the
bound schema container's host, then the bound schema component's host — each
read through the schemas side's Sylament-only `getLiveComponentOrNull()`
peeks (schemas' primary getters throw, see
[`schemas/belongs-to-live-component.md`](../schemas/belongs-to-live-component.md))
and narrowed inline by a private `asActionsHost()` guard, so a host
implementing only schemas' `InteractsWithSchemas` never satisfies the chain.
Three deltas stay deliberate: (1) the getter keeps its non-nullable
`InteractsWithActions` return and throws when nothing resolves — the
fail-fast stand-in for Filament's nullable return + separate
`getHasActionsLivewire()` narrowing step; (2) no table hop —
`BelongsToTable` is unported, restore it with the `tables` package; (3) no
group hop — Sylament's
[`ActionGroup::liveComponent()`](../../../packages/actions/src/ActionGroup.php)
eagerly propagates the host to child actions at set time, so the lazy group
hop has nothing left to resolve (reverses if group binding ever becomes
lazy). Covered by
[`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php):
explicit-set precedence, both schema hops, the schemas-only-host refusal, and
the unbound throw.

### `getContext()` emits only the schemaComponent key

Filament's `getContext()` assembles up to four entries: `recordKey` (via
`resolveRecordKey()`, guarded by table-model type checks), an early return
carrying only `recordKey` for child modal actions (`getParentAction()`),
`table`/`bulk` flags, and `schemaComponent`. The port's
[`getContext()`](../../../packages/actions/src/Action.php) emits only
`['schemaComponent' => $key]` — the key of the bound schema container,
falling back to the bound schema component, exactly Filament's precedence —
and `[]` otherwise. The `recordKey` half is a consequence of the
[record-key-resolution hoist to HasActions](#record-key-resolution-is-hoisted-to-hasactions):
the action side has no `resolveRecordKey()` to emit it, while the host still
consumes an inbound `context.recordKey` at mount time
(`getMountedActionRecord()`), so the consuming half of the contract is
intact; the emitting half returns when record-key resolution lands on the
action side with the `tables` integration. The `table`/`bulk` flags wait on
`BelongsToTable`/bulk actions, and the `getParentAction()` short-circuit on
modal nesting — none has a Sylament backing surface yet. Covered by
`ActionTest`'s context cases (empty without a binding, container key,
component-key fallback, keyless container).

### CanBeDisabled drops the authorization short-circuits

Filament's `isDisabled()` falls through `isHidden()` into three more branches —
`hasAuthorizationNotification()`, `hasAuthorizationTooltip()`, and
`isAuthorized()` — that let an unauthorized-but-visible action stay enabled
(so it can show a tooltip or fire a notification on click instead of vanishing
or graying out). Sylament's port stops at `isHidden()`: a component is disabled
when `$isDisabled` is truthy or the component is hidden, full stop. This is
a deliberate MVP simplification — the toolkit has no authorization-tooltip or
authorization-notification concept yet. Reverses once those two concerns
(and the `isAuthorized()` contract they lean on) are ported and wired onto
`Action`.

### Outlined styling is a state class, not a color map

Filament's outlined button resolves through `ButtonSize`/color-map machinery in
`button.blade.php` (per-color class maps composed in PHP). Sylament's
`CanBeOutlined` port is API-identical (`outlined()` / `isOutlined()` /
`hasOutlined()`), but the rendering side is a single code-less state class:
[`button-action.html.twig`](../../../packages/actions/templates/components/button-action.html.twig)
adds `sy-outlined` to the root class list and
[`button.css`](../../../packages/support/assets/src/styles/components/button.css)
carries one `.sy-btn.sy-outlined` variant rule driven by the same
`--sy-color-*` runtime tokens the filled button uses — per the repo styling
contract (semantic classes + runtime tokens, no per-color class maps). The
button template is the only consumer, matching Filament (its link/badge/
icon-button/grouped views ignore `outlined`).

### HasGroupedIcon narrows its icon type to string

Filament types the grouped icon `string|BackedEnum|Htmlable|Closure|null`;
the port narrows it to `string|Closure|null` — no enum or `Markup` icon
values exist in the actions package yet, the same narrowing `CanOpenModal`'s
`modalIcon` already applies (support's `HasIcon` keeps the wide type, so
`getIcon(default:)` accepts the narrowed value unchanged). Consumed exactly
where Filament consumes it:
[`grouped-action.html.twig`](../../../packages/actions/templates/components/grouped-action.html.twig)
resolves its icon as `component.getIcon(component.getGroupedIcon)` — Filament's
`icon: $this->getIcon(default: $this->getGroupedIcon())` — so the grouped icon
only surfaces inside a dropdown item, never on the button shape. Widen the
type in lockstep with `HasIcon`'s enum/Markup support if that ever lands here.

### `getModalIcon()`'s confirmation fallback bypasses icon-alias resolution

Filament resolves the confirmation-modal icon through
`FilamentIcon::resolve(ActionsIconAlias::MODAL_CONFIRMATION) ?? Heroicon::OutlinedExclamationTriangle`
— a registry lookup with a typed-enum fallback. The port hardcodes the literal
string `'heroicons:exclamation-triangle'` because Sylament's `ActionsIconAlias`
equivalent doesn't exist yet (`actions/README.md` lists `ActionsIconAlias` as
`todo`); the icon-string format itself (`heroicons:name`) matches the
convention `IconManager` and other components already use (e.g.
`support/templates/components/Section/index.html.twig`'s `heroicons:chevron-up`),
so only the alias indirection is missing, not the icon convention. Reversed once
an `ActionsIconAlias`-equivalent registry lands in `actions` and `getModalIcon()`
is rewired to resolve through it with the literal as its ultimate fallback.

### POST-to-url support is not ported

Filament's `CanOpenUrl` also carries `postToUrl()` / `shouldPostToUrl()` /
`hasShouldPostToUrl()`, and `Action::render()` branches heavily on
`shouldPostToUrl()` to emit a `<form method="post">` instead of an `<a>` (CSRF
token, `action`/`method` attributes, disabled-state handling all differ from
the plain-link path). None of this exists in
[`Sylament\Actions\Concerns\CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php)
— its own docblock records the omission as deliberate: "no POST actions in the
MVP." `Action`'s current template
(`../../../packages/actions/templates/components/button-action.html.twig`)
only ever emits `<a>` or `<button>`, never a `<form>`. This is a scope decision
for the current action surface, not a missing dependency, so it is **skipped**
rather than **blocked** — reversible the moment an action needs a
same-origin state-changing link (Filament's own use case: actions that must
not be triggered by a GET, e.g. destructive links without JS).

The state-based `url` closure parameter Filament's **schemas** / **tables**
`CanOpenUrl` siblings support (the reason support's
[arity probe](../support/component.md#the-arity-probe-is-deferred) stays
deferred) never applied here: Filament's own **actions** `CanOpenUrl` — the
source this row ports — never had a `$state` argument to begin with, so nothing
was dropped on that front.

### Confirmation is a flag, not a modal-config layer

`CanRequireConfirmation` itself is a verbatim, line-for-line port — same
`$isConfirmationRequired` prop, same `requiresConfirmation()` setter, same
`isConfirmationRequired()` evaluate-and-cache getter — and is fully covered by
`CanRequireConfirmationTest`. The divergence sits one layer up, in
`CanOpenModal`: in Filament "a confirmation IS a modal", so `CanOpenModal`
carries the full modal surface (sticky header/footer, slide-over + position,
click-through, close button, closed-by-clicking-away/escaping, autofocus,
custom width/alignment, arbitrary modal/footer actions with an
`ActionGroup`-aware action list, `modalContent`/`modalContentFooter`,
`hasModal()`/`shouldOpenModal()` presence logic, wizard suppression, icon
alias resolution) and `isConfirmationRequired()` only tweaks defaults inside
that machinery (alignment, width, icon, submit label, description).
Sylament's `CanOpenModal` now carries the presence layer (`modal()` /
`modalHidden()` / `shouldOpenModal()`), and confirmation flows through it
exactly as in Filament: `isConfirmationRequired()` no longer gates the modal
directly — it feeds `getModalDescription()`'s confirmation-string fallback,
and a filled description is what `shouldOpenModal()` reads
([`HasActions::mountedActionShouldOpenModal()`](has-actions.md#the-modal-open-gate-is-null-safe)
delegates to it). What remains divergent is the still-unported modal surface
confirmation only *tweaks* in Filament (footer action composition, slide-over,
sticky panes, click-through/escape/autofocus toggles,
`modalContent`/`modalContentFooter`, alignment/width defaults keyed off
confirmation) — `CanOpenModal`'s own gap, recorded on the
[CanOpenModal detail row](#only-the-modal-content-config-is-ported-the-behavior-and-footer-action-surface-is-not),
not this trait's.

### Label translation and the deprecated `disableLabel()` alias are not ported

Filament's `HasLabel` carries `$shouldTranslateLabel` + `translateLabel()` and
runs a string `getLabel()` result through Laravel's `__()` translator, plus a
`@deprecated disableLabel()` alias for `hiddenLabel()`. Sylament drops all
three — no framework-wide translation helper is wired into the repo yet
(same call made for schemas' `HasLabel`, see
[`schemas/has-label.md`](../schemas/has-label.md#label-translation-and-the-deprecated-disablelabel-are-not-ported)),
and a fresh codebase has no reason to ship the deprecated alias. Reverses for
translation if/when a translation layer lands; the deprecated alias stays out
regardless.

### Action invocation is a PHP-side seam only

`HasMountableArguments` is a verbatim port — `$action(['id' => 5])` clones the
action, binds the arguments through `arguments()`, and records them as
`invokedArguments`, so a per-render binding never mutates a host-cached
instance. All five trigger templates serialize **only**
`getInvokedArguments()` into the mount emission
(`live_action('mountAction', { name, arguments })` — Filament-exact:
`getLivewireClickHandler()` serializes the invoked arguments, never
`getArguments()`), and emit no `arguments` param when the action was never
invoked. The divergence is the call site: Blade is PHP, so Filament templates
can invoke `($action)(['id' => 5])` inline; Twig has no call syntax for
invokable objects, so the invocation seams are PHP-side configuration and the
`renderAction()` Twig helper's argument binding (`renderAction(name,
arguments)`, the Twig-facing wrapper for `__invoke`). Permanent — a Twig-side
call syntax would require a helper function anyway, which `renderAction()`
already is.

### `getName()` tightens its return type to non-nullable `string`

Filament types `getName(): ?string` even though the method always throws
`LogicException` before returning when the name is `blank()` — the nullable
signature never actually surfaces a `null`. The port's
[`getName()`](../../../packages/actions/src/Concerns/HasName.php) declares
`: string`, matching the trait's real behavior rather than Filament's vestigial
nullability. Purely a type-signature tightening with no behavioral change (both
throw on a blank name); reverse by widening back to `?string` if some future
caller needs to distinguish "no name" from "threw" without a try/catch.

### HasSize drops the optimized-render fast-path helper

Filament's `HasSize` carries `hasSmallSizeDefault(): bool`
(`$this->size === null && $this->defaultSize === Size::Small`), consumed only by
`Action::canRenderOptimizedLink()` / `canRenderOptimizedGrouped()` — a
Blade-bypass fast path that hand-builds link/grouped-button HTML instead of
going through the full component render when the action carries no non-default
styling. Sylament's `Action` has not yet ported `render()`/`toHtml()`/the
`toButtonHtml()`/`toOptimizedLinkHtml()` family at all, so the method has no
consumer today. Reversible: port `hasSmallSizeDefault()` alongside whichever
future item ports Action's HTML-rendering/optimized-path pipeline; until then
the omission tracks that unstarted surface, not this trait's own
`size()`/`defaultSize()`/`getSize()` contract, which is fully ported and tested.

### Record-key resolution is hoisted to HasActions

Filament's `getRecord()` detects a scalar "record key" (anything that isn't a
`Model` or array) and resolves it through a `resolveRecordUsing` closure stored
*on the trait itself*, memoizing the resolved record back onto `$this->record`.
Sylament drops that branch from `InteractsWithRecord` entirely; a
same-named `resolveRecordUsing()` setter and the key-resolution logic instead
live on
[`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) (the
live-component host trait — Filament's `InteractsWithActions`, per the
[Has*/InteractsWith* swap](../renames.md#interface-and-trait-names-are-swapped)),
scoped to turning a mounted action's `context.recordKey` into a record before
calling `$action->record(...)` — covered by
[`HasActionsRecordTest`](../../../packages/actions/tests/Unit/Concerns/HasActionsRecordTest.php).
The two `resolveRecordUsing` methods share a name but not a mechanism:
Filament's is a per-action fallback inside `getRecord()`; Sylament's is a
per-host callback used once, at mount time, by the live-component runtime.
Reversible if a future action needs to accept a bare record key directly (not
just via live-component mount) — at that point `InteractsWithRecord::getRecord()`
would need its own key-detection branch back.

### The action modal is an anonymous Twig component, not an included view

Filament's `action-modal.blade.php` is a plain view: `Action::renderModal()` calls
`view('filament-actions::action-modal', ['action' => $this])`, and
`components/modals.blade.php` renders the result. The port started the same way — an
`{% include … with { action, id } only %}` from
[`Modals`](../../../packages/actions/templates/components/Modals.html.twig) — and is
now [`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig),
invoked as `<twig:SylamentActions:ActionModal :action="action" :id="…" />`. Every
other rendered surface in the repo that a template invokes is a `<twig:>` component;
an include was the odd one out, and `{% props action, id %}` states the contract the
include's `with {}` could only imply — a missing prop now fails by name.

One thing is genuinely lost: `only`. ux-twig-component's `{% component %}` tag
accepts it, but the `<twig:>` HTML syntax has no equivalent (its pre-lexer never
emits the flag), so the component inherits the caller's render context where the
include was strictly isolated. Inert here — the template declares both props and
references nothing else — but it is a real reduction in isolation, and the reason to
keep the props list explicit rather than leaning on inherited variables.

### The footer buttons are hardcoded markup, not rendered Action instances

Filament's `getModalSubmitAction()` / `getModalCancelAction()` build real
`Action` objects (colored, labeled, wired to `submit`/`action`/`close`) that
the modal view renders through the normal action-rendering pipeline. Sylament's
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)
instead writes two literal `<button class="sy sy-btn …">` elements
and inlines the submit color computation itself:
`action.getColor is iterable or action.getColor is empty or action.getColor == 'gray' ? 'primary' : action.getColor`
mirrors Filament's `match ($color) { 'gray' => 'primary', default => $color }`
in `getModalSubmitAction()`, but adds `is iterable` / `is empty` guards
Filament's version doesn't need — because the template interpolates the color
directly into a `sy-color-{{ submitColor }}` CSS class name rather than
delegating to a color-aware button component, an array-of-shades color (a
value `getColor(): string|array|null` explicitly allows) cannot be represented
in a class name and is silently coerced to `primary` instead of being passed
through. This is a consequence of there being no footer-actions API yet (see
the [modal-content detail](#the-action-modal-template-only-renders-part-of-the-full-action-modal)
above), not an independent design choice — reversible once the footer buttons
render through a real color-aware Action/button component instead of literal
class interpolation.

_See also:_ the global [rename table](../renames.md) — the
`Filament\Actions\Concerns\InteractsWithActions` ⇄ `HasActions` /
`Filament\Actions\Contracts\HasActions` ⇄ `InteractsWithActions` swap, and the
systematic `Htmlable` → `Markup`, Livewire → UX LiveComponent
(`getLivewire()` → `getLiveComponent()`), and Blade → Twig translations this
file leans on throughout, are recorded there rather than re-derived here.
