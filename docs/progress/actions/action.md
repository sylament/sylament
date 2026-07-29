# Action (actions)

`Sylament\Actions\Action` — the base class every actions-package action
extends. It composes `Sylament\Support\Components\ViewComponent` (Twig-backed
rendering) and folds in 18 actions-package concerns — `BelongsToLiveComponent`,
`CanBeDisabled`, `CanBeHidden`, `CanBeOutlined`, `CanClose`, `CanNotify`,
`CanOpenModal`, `CanOpenUrl`, `CanRequireConfirmation`, `HasAction`,
`HasArguments`, `HasGroupedIcon`, `HasLabel`, `HasLifecycleHooks`,
`HasMountableArguments`, `HasName`, `HasSize`,
`InteractsWithRecord` — each a
Surface row below with its own detail/divergence spliced in from its own
audit. It also composes two multi-consumer actions-package concerns tracked
outside this file — `BelongsToGroup` (shared with `ActionGroup`, written up in
[`action-group.md`](action-group.md)) and `BelongsToSchemaComponent` (shared
with `ActionGroup`, a `done` row with no detail file in the
[package README](README.md#features), since its whole surface is a two-method
back-reference pair) and the two modal attribute-bag concerns
`HasExtraModalOverlayAttributes` / `HasExtraModalWindowAttributes` (`done` rows
in the same README, each a two-method pair as well) — plus 6 support-level
traits (`HasColor`,
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

Every point of this component and its folded concerns. _Blocked by_ names
what has to land first. _Detail_ links the write-up in [Details](#details)
(deleted once the row is **done**), the permanent faithful-but-subtle note in
[Semantics](#semantics), and/or the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction & `make()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | — |
| `call()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | blocked | bulk actions (`InteractsWithSelectedRecords`), visibility cache | [detail](#call-drops-the-post-call-cleanup) |
| `cancel()` / `halt()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | Doctrine transaction integration | [detail](#cancel-and-halt-drop-the-rollback-flag) |
| `success()` / `failure()` / `getStatus()` + `$status` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#getstatus-drops-the-bulk-record-tally) |
| `resolveDefaultClosureDependencyForEvaluationByName()` + `$evaluationIdentifier` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | tables/InteractsWithSelectedRecords ports, the Action→schema record/operation hop (M1 · 6) | [detail](#resolvedefaultclosuredependencyforevaluationbyname-covers-10-of-16-names) |
| `resolveDefaultClosureDependencyForEvaluationByType()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | todo | schemas/tables/InteractsWithSelectedRecords ports | [detail](#resolvedefaultclosuredependencyforevaluationbytype-is-not-overridden) |
| `getContext()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | tables (`table`/`bulk` flags, `recordKey`) | [diverges](#getcontext-emits-only-the-schemacomponent-key) |
| `nestingIndex()` / `getNestingIndex()` + `parentAction()` / `getParentAction()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`$nestingIndex`, `$parentAction` and their accessors) | done | — | Both stamped by the host: `nestingIndex()` per frame in [`resolveActions()`](has-actions.md#resolveactions-drops-the-table-branch), `parentAction()` by [`prepareModalAction()`](#canopenmodals-chrome-layout-and-wizard-surface-is-not-ported) on every action an action declares. `getParentAction()` is what makes the [`getContext()` child-frame short-circuit](#getcontext-emits-only-the-schemacomponent-key) decidable |
| [`CanCallParentAction`](../../../packages/actions/src/Concerns/CanCallParentAction.php) | [`Concerns/CanCallParentAction.php`](../../../vendor/filament/actions/src/Concerns/CanCallParentAction.php) | done | — | Filament-exact two-method port (`callParent()` + getter). The getter is `getParentActionCallLiveComponentClickHandler()` against Filament's `…Livewire…`, the systematic Livewire → UX LiveComponent naming translation from the [rename table](../renames.md), not a rename of its own. Read by all five trigger templates, ranked **above** `shouldClose()` in the click precedence |
| [`HasParentActions`](../../../packages/actions/src/Concerns/HasParentActions.php) | [`Concerns/HasParentActions.php`](../../../vendor/filament/actions/src/Concerns/HasParentActions.php) | done | — | Complete, method-for-method and body-for-body. Three of its accessors (`shouldCancelParentActionsOnClose()`, `shouldCancelAllParentActionsOnClose()`, `getParentActionToCancelToOnClose()`) have no consumer here — and none in `vendor/filament` either, so they are kept as declared API rather than trimmed |
| Trigger rendering ([`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) + the [`Link`](../../../packages/actions/templates/partials/Action/Link.html.twig) / [`Badge`](../../../packages/actions/templates/partials/Action/Badge.html.twig) / [`Grouped`](../../../packages/actions/templates/partials/Action/Grouped.html.twig) / [`IconButton`](../../../packages/actions/templates/partials/Action/IconButton.html.twig) siblings) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`toButtonHtml`/`toLinkHtml`/`toBadgeHtml`/`toGroupedHtml`/`toIconButtonHtml`) | in progress | — | [detail](#tooltip-icon-position-and-extra-attribute-rendering-are-untested), [semantics](#an-uncolored-badge-or-link-action-renders-primary-not-gray), [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
| Modal rendering ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)) | [`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php) | in progress | — | [detail](#the-action-modal-template-only-renders-part-of-the-full-action-modal), [diverges](#the-action-modal-is-an-anonymous-twig-component-not-an-included-view), [diverges](#the-modal-footer-renders-real-action-instances) |
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
| [`CanClose`](../../../packages/actions/src/Concerns/CanClose.php) | [`Concerns/CanClose.php`](../../../vendor/filament/actions/src/Concerns/CanClose.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`CanNotify`](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | in progress | notifications package | [detail](#cannotify-only-ports-notification-titles-not-sending) |
| [`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php) | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | in progress | — | [detail](#canopenmodals-chrome-layout-and-wizard-surface-is-not-ported), [diverges](#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution) |
| [`CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | in progress | — | [detail](#geturl-drops-the-default-action-url-fallback) |
| POST-to-url support | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | skipped | — | [diverges](#post-to-url-support-is-not-ported) |
| [`CanRequireConfirmation`](../../../packages/actions/src/Concerns/CanRequireConfirmation.php) | [`Concerns/CanRequireConfirmation.php`](../../../vendor/filament/actions/src/Concerns/CanRequireConfirmation.php) | done | — | [diverges](#confirmation-is-a-flag-not-a-modal-config-layer) |
| [`HasAction`](../../../packages/actions/src/Concerns/HasAction.php) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | in progress | — | [detail](#hasaction-drops-the-livewire-click-gating), [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`HasArguments`](../../../packages/actions/src/Concerns/HasArguments.php) | [`Concerns/HasArguments.php`](../../../vendor/filament/actions/src/Concerns/HasArguments.php) | done | — | — |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | [diverges](#hasgroupedicon-narrows-its-icon-type-to-string) |
| [`label()` / `getLabel()`](../../../packages/actions/src/Concerns/HasLabel.php) | [`label()` / `getLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | The name-derived default takes the segment **before the first dot** (`u($name)->before('.')`), which is Filament's own actions-side choice and differs from `Field`'s `afterLast('.')` — matched deliberately, not a slip to "fix" |
| `hiddenLabel()` / `isLabelHidden()` | [`hiddenLabel()` / `isLabelHidden()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | A hidden label renders as an `aria-label` on the trigger root rather than as visible text — the [button](../../../packages/actions/templates/partials/Action/Button.html.twig) / [link](../../../packages/actions/templates/partials/Action/Link.html.twig) / [badge](../../../packages/actions/templates/partials/Action/Badge.html.twig) triggers read `isLabelHidden` for it, matching what Filament's `labelSrOnly` button prop does |
| `translateLabel()` / `disableLabel()` (deprecated) | [`translateLabel()` / `disableLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported) |
| [`HasLifecycleHooks`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) | [`Concerns/HasLifecycleHooks.php`](../../../vendor/filament/actions/src/Concerns/HasLifecycleHooks.php) | in progress | a Symfony event dispatch for `ActionCalling`/`ActionCalled` | [detail](#the-lifecycle-hooks-are-ported-actioncallingactioncalled-events-are-not) |
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
cache to clear ([the cache is dropped](#canbehidden-omits-authorization-group-and-table-cache-integration)).
**Blocked** on
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

### `resolveDefaultClosureDependencyForEvaluationByName` covers 10 of 16 names

Filament's override resolves 16 dependency-name groups: `arguments`, `data`,
`livewire`, `model`, `mountedActions`, `record`, `selectedRecords`/`records`,
`selectedRecordsQuery`/`recordsQuery`, `schema`, `schemaComponent`/`component`,
`schemaOperation`/`context`/`operation`, `schemaGet`/`get`, `schemaSet`/`set`,
`schemaComponentState`/`state`, `schemaState`, `table`. The port's
[`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/Action.php)
resolves 10 of them: `record`, `arguments`, `liveComponent` (the systematic
`livewire` → `liveComponent` rename tracked in
[`../renames.md`](../renames.md) — not a divergence), `data` (added with the
forms-in-modals epic: `'data' => ClosureParameter::make($this->getData())`,
backed by [`HasData`](../../../packages/actions/src/Concerns/HasData.php), so a
mounted action's callback reads its submitted form state as `$data`), and — since
the schemas-actions integration — `schema`, `schemaComponent`/`component`,
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
Filament, pinned by `ActionTest`. So does an action declared *inside* a
mounted-action schema, for the same reason and equally in Filament — that schema
is a **root** schema with no parent component, so all three arms of
`getSchemaComponent()` return `null`; the browser pass that found it is written
up in
[has-actions.md](has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema).
Both arms also chain
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
vendor-verified and kept). Still missing, each blocked on its backing concern: `model`
(model metadata), `mountedActions` (mounted-actions introspection
beyond today's `HasActions`), `selectedRecords`/`records` +
`selectedRecordsQuery`/`recordsQuery` (`InteractsWithSelectedRecords` +
`CanFetchSelectedRecords`), `schemaOperation`/`context`/`operation`
(the value exists now — schemas' `Schema` has
[`getOperation()`](../schemas/has-operation.md) — but no arm here reads it off
the action's schema container; deferred to M1 · 6 with the record hop below),
and `table` (`BelongsToTable`). Coverage: `record`/`arguments` via `ActionTest`'s
"calls the action closure…", `data` via `ActionTest`'s "injects $data from
getData()", `liveComponent` via
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
entirely — a deliberate confirm-only MVP cut. The child-iteration branch does
exist now, but as an override on
[`ActionGroup::isHidden()`](../../../packages/actions/src/ActionGroup.php) rather
than in this shared trait, since only `ActionGroup` needs it —
[see there](action-group.md#no-ishiddeningroup-or-visibility-caching).
Concretely: a Sylament action with no `hidden()`/`visible()` call is always
visible today, even where Filament would hide it because the user fails
`CanBeAuthorized`'s policy check, or because its `ActionGroup` is empty/hidden.

**Next step:** restore the authorization fallback once `CanBeAuthorized` lands
(it supplies `isAuthorizedOrNotHiddenWhenUnauthorized()`), restore the
`getGroup()?->baseIsHidden()` short-circuit once `BelongsToGroup` carries more
than a setter/getter pair, and restore the
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

### CanOpenModal's chrome, layout, and wizard surface is not ported

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
The **custom body content** group is ported too: `modalContent()` /
`modalContentFooter()` / `getModalContent()` / `getModalContentFooter()` /
`hasModalContent()` / `hasModalContentFooter()` (Filament's `View|Htmlable`
typed as `Markup`, the systematic translation; the deprecated `modalFooter()`
alias is not carried, per the no-deprecation-shims policy), with both feeding
`shouldOpenModal()`'s content arms and rendering in Filament's order inside
the modal's content slot
([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)).
The `checkForSchemaUsing` slot is now supplied: the host passes
`$this->mountedActionHasSchema(...)`, Filament-exact, so an action whose only
modal content is its `schema()` opens a modal on that alone.
The **footer-action family** is ported: `modalFooterActions()` (full
override, prepared and keyed by name) / `extraModalFooterActions()` (slotted
between submit and cancel, `ActionGroup`-aware) / `registerModalActions()` /
`modalSubmitAction()` / `modalCancelAction()` (the `Action|bool|Closure`
customization hooks — a closure receives the default via the `action` named
injection; `false` removes the button) / `getModalFooterActions()` /
`getVisibleModalFooterActions()` / `getModalSubmitAction()` (string click
handler from `getLiveComponentCallMountedActionName()`, color
primary-for-gray-or-unset) / `getModalCancelAction()` (`close()` + gray) /
`getExtraModalFooterActions()` / `getModalActions()` (group-flattening) /
`getModalAction()` / `prepareModalAction()` / `prepareModalActionGroup()` /
`makeModalAction()` (`Action::make($name)->button()`), each memoized in the
`cached*` properties Filament uses. Scoped deltas, each deliberate:
`getModalFooterActions()` drops the `isWizard()` early return (wizards are
unported) but carries Filament's center-alignment `array_reverse` — applied only
in the composed submit/extras/cancel branch, matching both `Alignment::Center`
and the `'center'` string, so a confirmation modal renders cancel-first;
`getModalSubmitAction()` now carries Filament's
`->submit($hasFormWrapper ? $name : null)->action($hasFormWrapper ? null : $name)`
branch in full (landed with `HasSchema` / `CanSubmitForm`), and matches
`null` alongside `'gray'` to primary (Filament's `getColor()` never returns
null there; ours does for an unset color — observably identical);
`makeModalSubmitAction()` (the `callParent` factory) is ported, with no
constructor yet; `prepareModalAction()` now stamps `->parentAction(...)`
(narrowed by an `instanceof Action` check the composing class needs) and still
drops `->table(...)` (tables), and
translates `->livewire(...)` to
`->liveComponent($this->getLiveComponentOrNull())`; the `=== false` removal
check is `$action instanceof Action`, so a nonsense `modalSubmitAction(true)`
nulls the button instead of fataling. The footer renders through
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)'s
`getVisibleModalFooterActions()` loop as real `Action` buttons.

The **chrome group** is ported, Filament-exact in names, signatures, and
effective defaults, and covered by `CanOpenModalTest`: `slideOver()` /
`isModalSlideOver()`, `slideOverPosition()` / `getModalSlideOverPosition()`
(typed against support's new
[`SlideOverPosition`](../../../packages/support/src/Enums/SlideOverPosition.php)
enum),
`stickyModalHeader()` / `stickyModalFooter()` and their
`isModalHeaderSticky()` / `isModalFooterSticky()` getters (each falling back to
`isModalSlideOver()`, so a slide-over sticks both panes by default),
`modalAlignment()` / `getModalAlignment()` (explicit, else `Center` on
confirmation or an `xs`/`sm` width, else `Start`),
`modalFooterActionsAlignment()` / `getModalFooterActionsAlignment()` (explicit,
else `Center` on confirmation, else `null`), `modalCloseButton()` /
`hasModalCloseButton()`, `closeModalByClickingAway()` /
`isModalClosedByClickingAway()`, `closeModalByEscaping()` /
`isModalClosedByEscaping()`, `modalAutofocus()` / `isModalAutofocused()`, and
`modalClickThrough()` / `isModalClickThrough()`. Every one of them reaches the
support `Modal` primitive through
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig).
The one scoped delta: Filament's last four getters fall back to static
`ModalComponent` properties, and the port writes the same values as literal
`?? true` fallbacks because that global-defaults seam is deferred — those four
`?? true` expressions in `hasModalCloseButton()` /
`isModalClosedByClickingAway()` / `isModalClosedByEscaping()` /
`isModalAutofocused()` are the seam's insertion points when it lands (tracked on
support's [`ModalComponent` static-config row](../support/modal.md#modalcomponent-static-config)).

**Missing vs Filament** — Filament's trait is ~800 lines; the port is now ~690.
Absent entirely, with no property, setter, or getter:

- **Wizard suppression**: `getModalFooterActions()`'s `isWizard()` early return,
  deferred with `HasWizard`.
- **Deprecated aliases**: `centerModal()`, `modalButton()`, `modalSubheading()`,
  `extraModalActions()`, `makeExtraModalAction()` are not carried over — expected;
  Sylament has no deprecation-shim policy and these all have live equivalents
  above (themselves unported).
- `getLiveComponentCallMountedActionName()` (the systematic Livewire →
  LiveComponent translation of `getLivewireCallMountedActionName()`) is ported:
  the trait's base returns `null` and `Action` overrides it to
  `'callMountedAction'`, exactly as Filament does — the submit footer action's
  string click handler reads it.

**Type-shape narrowing** (not a functional gap, just less general): `modalIcon`
is `string|Closure|null` here vs Filament's `string|BackedEnum|Htmlable|Closure|null`
(no `Heroicon` enum or `Markup`-typed icon exists in Sylament yet); `modalWidth`
is a bare string (`'md'`) vs Filament's `Width` enum (no `Width` enum ported to
Sylament yet — every width value here is just whatever string a template's CSS
happens to key on, e.g. `sy-modal-md`).

`makeModalSubmitAction(string $name, ?array $arguments = null)` is **ported**,
Filament's body exactly:
`makeModalAction($name)->callParent($this->getLiveComponentCallMountedActionName())->arguments($arguments)->color('gray')`.
It builds a footer action that calls its **parent** action instead of mounting
itself, and the trigger templates already rank its `callParent` handler above
`shouldClose()`. Nothing in the port constructs one yet, and the reason is worth
naming precisely, because it is not a wizard thing: Filament's four call sites
are `CreateAction` (`createAnother`), `AttachAction` (`attachAnother`),
`AssociateAction` (`associateAnother`), and forms' `Select` (`createAnother`
again) — the documented "create another / attach another" pattern
([`actions/docs/02-modals.md`](../../../vendor/filament/actions/docs/02-modals.md)),
where an extra footer button re-runs the same action with an `['another' => true]`
argument flag instead of closing the modal. Every one of those is a **prebuilt
action**, and none is ported. So the first consumer arrives with the prebuilt
record actions (or with forms' `Select`), not with `HasWizard`. Filament's
`makeExtraModalAction()` is the deprecated alias of this same method, which is
why the deprecated-aliases bullet above lists it.

`prepareModalAction()` now also stamps `->parentAction($this instanceof Action ? $this : null)`
alongside the schema-container/schema-component/live-component bindings, so
every action an action declares — footer actions, `getModalAction()` targets —
knows its parent. That single line is what makes the host's parent-modal
descent addressable from the action side and what lets
[`getContext()`](#getcontext-emits-only-the-schemacomponent-key) recognize a
child frame. The `instanceof` narrowing exists because the trait is also
composed by non-`Action` classes; Filament passes `$this` unconditionally from
a class that is always an `Action`.

**Next step**: the wizard suppression returns with `HasWizard`; the four
`?? true` literals become reads of the `ModalComponent`-equivalent
global-defaults seam if and when that lands. What is left beyond those is one
scoped delta from the list above — `prepareModalAction()`'s still-dropped
`->table()` binding, waiting on `BelongsToTable`.

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

### HasAction drops the Livewire-click gating

Ported: both value shapes. `action(Closure|string|null $action): static` /
`getAction(): Closure|string|null` round-trip a closure or a string, and
`Action::call()` evaluates the value, injecting `record`/`arguments`/
`liveComponent` for closures and returning a string as-is (Filament-identical
no-op semantics) — covered by `ActionTest`. The string branch renders through
`getClientClickHandler(): ?string` — the Sylament port of
`getLivewireClickHandler()`'s string arm — which every trigger template reads
first in its click precedence (string handler, then a `callParent()` handler,
then `shouldClose()` → a client-side dismiss, then the `mountAction`
round-trip); the reinterpretation of
the string as a live-action name rather than a raw client expression is
recorded as a
[divergence](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side).

Missing vs Filament's `HasAction`:
- **`livewireClickHandlerEnabled()` / `isLivewireClickHandlerEnabled()` as a method.** Filament's gate has three arms: an explicit `livewireClickHandlerEnabled()` override, `filled($this->getUrl())`, and `canSubmitForm()`. Both **default** arms are now implemented — but in the five trigger templates rather than on the trait. The `getUrl()` arm always was (`{% if not component.getUrl %}` around the whole click cascade); the forms-in-modals epic added the second as a `hasLiveClickBinding = not component.getUrl and not component.canSubmitForm` guard in [`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) and its `link`/`badge`/`grouped` siblings, and as an extra term in [`Action/IconButton.html.twig`](../../../packages/actions/templates/partials/Action/IconButton.html.twig)'s `mountAttributes`. It is load-bearing, not tidiness: [`getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) gives a form-wrapped action `->submit(name)->action(null)`, so without the guard the trigger falls through its click cascade to the `mountAction` default and every confirm click fires a **second**, unrelated live request — `mountAction('submit')`, naming an action that does not exist — alongside the form's own `callMountedAction`. What is still absent is only the **explicit override**: there is no `livewireClickHandlerEnabled()` setter, so a caller cannot force-enable or force-disable the binding against the two defaults. The close arm is unaffected in both codebases — Filament keeps it on a separate `x-on:click`, the port on a separate `data-action`.
- **`hasAction(): bool`.** Helper predicate not ported; trivially derivable from `getAction() !== null` at call sites today, but no equivalent method or test exists.

Next step: promote the template-side guard into a real `isLiveComponentClickHandlerEnabled()` on `HasAction` the moment a caller needs the explicit override — the two default arms already agree with Filament's, so that change is additive and moves logic rather than adding behaviour.

### The lifecycle hooks are ported; ActionCalling/ActionCalled events are not

**In:** all six hooks Filament declares — `before(?Closure)` /
`after(?Closure)`, `beforeFormFilled(?Closure)` / `afterFormFilled(?Closure)`
and `beforeFormValidated(?Closure)` / `afterFormValidated(?Closure)` — with
their runners `callBefore()` / `callAfter()` / `callBeforeFormFilled()` /
`callAfterFormFilled()` / `callBeforeFormValidated()` /
`callAfterFormValidated()`, each evaluating the stored closure through
`EvaluatesClosures`. Covered by
[`HasLifecycleHooksTest`](../../../packages/actions/tests/Unit/Concerns/HasLifecycleHooksTest.php)
via the
[`HasLifecycleHooksFixture`](../../../packages/actions/tests/Fixtures/Concerns/HasLifecycleHooksFixture.php):
each pair fires in order when set, and every `call*` method is a safe
no-op (return `null`) when unset. `Action`
(`../../../packages/actions/src/Action.php`) is the sole consumer. Where the
`*FormValidated` pair fires is the host's business —
[`HasActions::callMountedAction()`](has-actions.md#callmountedaction-drops-rate-limiting-authorization-transactions-and-redirects)
runs `callBeforeFormValidated()` before the mounted schema's `getState()` and
`callAfterFormValidated()` inside its `afterValidate` callback, so a failed
validation never reaches the second one.

**Missing vs Filament:** Filament's `callBefore()` / `callAfter()`
dispatch `ActionCalling` / `ActionCalled` events (`Event::dispatch(...)`, the
latter from a `finally` block so it always fires); the port has no
`Sylament\Actions\Events` namespace and dispatches nothing. **Next step:** the
event dispatch has no named prerequisite yet and should be scoped (Symfony
`EventDispatcher` port of `ActionCalling`/`ActionCalled`) when the mount/call
pipeline is designed.

### The record title and model metadata surface is not ported

**In:** `record(mixed $record): static` and `getRecord(): mixed`, the latter
running the stored value through `evaluate()` — round-tripping a literal and a
closure is covered by
[`InteractsWithRecordTest`](../../../packages/actions/tests/Unit/Concerns/InteractsWithRecordTest.php).

**Missing vs Filament:** everything else the trait carries is absent —
`model()` / `getModel()` / `getCustomModel()`, `modelLabel()` /
`pluralModelLabel()` and their Title-Case variants, `recordTitle()` /
`recordTitleAttribute()` and their `getRecordTitle()` / `getCustomRecordTitle()`
/ `hasCustomRecordTitle()` family, `resolveRecordKey()`, and the
`ensureCorrectRecordType()` custom-model guard. Of the fallback chain Filament
walks when no record is set directly — `getGroup()?->getRecord()`,
`getSchemaContainer()?->getRecord()`, `getSchemaComponent()?->getRecord()`,
and the `getHasActionsLivewire()?->getDefaultActionRecord()` tail — only the
group hop is ported. Both remaining ends now exist on the schemas side —
`Schema` and `Component` carry `getRecord()`/`getRecordClass()` under the
record-only vocabulary this trait already used
([`belongs-to-record.md`](../schemas/belongs-to-record.md); the two surfaces have
converged, so the hop is a plain delegation with no name translation) — but the
hop itself is unwired: an `Action` inside a record-bound schema still resolves
`$record` from its own binding plus the group fallback only. **Deferred to
M1 · 6** (record actions), which owns both hops and the
`schemaOperation`/`operation` arm; there is still no default-action-record host
hook. The
`$record` property is typed `mixed`
instead of Filament's `Model | string | array | Closure | null`, since there is
no Doctrine-entity-aware resolution to type it against.

**Next step:** land `getModel()` / `getModelLabel()` (needs a Table or
schema-container equivalent to fall back to) and `getRecordTitle()` (needs the
same, plus a `getCustomRecordTitle()` port) once the `tables`/`schemas`
integration points they read from exist; tighten `$record`'s type once a
Doctrine entity boundary is decided.

### The action-modal template only renders part of the full action modal

**In:** a `<twig:SylamentSupport:Modal>` call driven entirely by
[`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php)'s
getters — Filament's whole binding list minus the deferred `alert` and the
`wire:*` keys. Content config (`heading`, `description`, `icon`, `iconColor`,
`width`) and the full chrome (`alignment`, `slideOver` + `slideOverPosition`,
`stickyHeader` / `stickyFooter`, `closeButton`, `closeByClickingAway`,
`closeByEscaping`, `autofocus`, `clickThrough`, both extra attribute bags)
pass straight through, the `content` block renders `getModalContent()` then
`getModalContentFooter()`, and the footer renders
`getVisibleModalFooterActions()` as real `Action` instances with
`getModalFooterActionsAlignment()` as their alignment.

The whole thing sits inside a **dismissal wrapper**: a `<div>` binding the
support controller's `sylament--support--modal:dismissed` event to
`live_action('unmountAction', { cancelParentActions: action.getParentActionsToCancelOnClose })`,
so a client-side close (ESC, click-away, close button, a `close()` action)
unmounts that frame server-side — cancelling as far up the stack as the action
asked — and the next live re-render does not
resurrect a modal the user already closed — Filament's `x-on:modal-closed`
equivalent. It is a wrapper element rather than a binding on the component tag
because a caller `data-action` would replace the dialog's own Stimulus wiring
inside `attributes.defaults()`; the event bubbles from the dialog, so the
wrapper catches it. Its id is name-scoped where the dialog's is index-scoped —
[why](has-actions.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper).

Rendered by the host's `<twig:SylamentActions:Modals />` tag (see the
`RecordActionHost` / `ActionHost` Twig fixtures), once per mounted frame, with
the top frame gated on `shouldOpenModal()` — Filament's own loop and gate. Each
instance takes its `id` and its `open` state from the host
([per-frame ids](has-actions.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper),
[server-computed open state](has-actions.md#the-stack-is-choreographed-declaratively-not-by-client-events)),
plus `overlaid` for every frame that is not the top one. Covered by
[`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php),
which asserts, through a full `mountAction` → render cycle: the modal class,
heading, submit/cancel labels and click wiring, the confirmation icon
`<svg>`, extra footer actions slotted between submit and cancel, content +
content-footer placement, the chrome pass-through, the confirmation modal's
centered cancel-first footer, and the dismissal wrapper's `unmountAction`
binding — and by
[`ModalsRenderTest`](../../../packages/actions/tests/Integration/Components/ModalsRenderTest.php)
for the stacked cases: one dialog per frame with distinct ids, per-frame open
and overlaid state, per-frame `cancelParentActions`, and the modal-less top
frame that renders nothing.

**Missing vs Filament:** `action-modal.blade.php` is a general-purpose action
modal, not a confirmation-only one, and most of that generality has no
Sylament counterpart yet:

- **No render-hook brackets around the content slots.** The template fills the
  support Modal's `content` block with `getModalContent()`, then the mounted
  action's schema, then `getModalContentFooter()` — Filament's exact order
  (`action-modal.blade.php:63-84`), with the schema arriving as a `schema` prop
  from [`Modals`](has-actions.md) rather than being resolved in-template. What
  has no counterpart is the four render-hook pairs bracketing those three
  (`MODAL_CUSTOM_CONTENT_{BEFORE,AFTER}`, `MODAL_SCHEMA_{BEFORE,AFTER}`,
  `MODAL_CUSTOM_CONTENT_FOOTER_{BEFORE,AFTER}`) — no render-hook facility
  exists.
- **No `alert` mode.** Filament threads `alert` through to the modal's
  `role="alertdialog"`; neither `CanOpenModal` nor
  [`Modal/index.html.twig`](../../../packages/support/templates/components/Modal/index.html.twig)
  carries it (tracked on support's
  [breadth-fill row](../support/modal.md#deferred-filament-modal-surface-breadth-fill)).
  Every other chrome prop Filament threads now passes through; two bindings
  differ deliberately: `returnsFocus` stays at its `true` default where Filament
  hardcodes `focus-trap-returns-focus="false"`
  ([why](../support/modal.md#returnsfocus-stays-true-where-filaments-action-modal-hardcodes-false)),
  and the modal `id` plus the per-frame `open` value are Sylament's
  server-state open signal rather than Filament's event-only open.
- **No render hooks.** Filament brackets custom content and the schema in four
  `FilamentView::renderHook()` pairs; Sylament has no render-hook facility, so
  none of that extensibility point exists (a broader gap than this template
  alone, noted here because it is one of the things the Filament view relies
  on).

The **form-wrapper wiring is ported**: the template derives
`submitHandler = action.hasFormWrapper ? action.getLiveComponentCallMountedActionName : null`
and passes it to support's `Modal`, which swaps the window element to `<form>`
and binds the submit event
([support/modal.md](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit)).
Filament couples the two the same way (`action-modal.blade.php:27`). The one
translation is the binding itself — `live_action('…:prevent', …, 'submit')`
where Filament writes `wire:submit.prevent`.

**Next step:** what is left on this row is `alert` and the render hooks. Each
lands with the concern named beside it; the support `Modal` component takes its
matching prop in the same change.

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

## Semantics

Faithful ports whose behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### An uncolored badge or link action renders primary, not gray

Every trigger template passes `:color="component.getColor"`, which is `null` for
an action nobody called `->color()` on. Reading that, the obvious conclusion is
that `null` reaches the primitive and lands in its gray branch. It does not.
`{% props %}` compiles to `isset($context['__props']['color'])`
([`PropsNode.php`](../../../vendor/symfony/ux-twig-component/src/Twig/PropsNode.php)),
and `isset()` is **false for null** — so an explicitly passed `null` is
indistinguishable from an omitted prop and the prop default wins. An uncolored
badge or link action therefore emits `sy-color sy-color-primary` and renders
through the primitive's `.sy-color` branch; `:not(.sy-color)` is unreachable
from these two triggers.

That is Filament's behaviour, not a divergence — `badge.blade.php` and
`link.blade.php` both default `'color' => 'primary'` — and `Button` and
`IconButton` have rendered this way since they were composed. The one trigger
where the gray branch *is* reachable is `Grouped`, because
[`DropdownItem`](../support/dropdown-item.md) defaults to `'gray'` instead,
matching `dropdown/list/item.blade.php`.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

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
precedence, pinned by `ActionTest`. Each shape has its own template under
[`partials/Action/`](../../../packages/actions/templates/partials/Action), and
every one of the five is a thin composition over the support primitive
Filament's corresponding `*_VIEW` constant points at —
[`Badge`](../support/badge.md), [`Button`](../support/button.md),
[`DropdownItem`](../support/dropdown-item.md),
[`IconButton`](../support/icon-button.md) and [`Link`](../support/link.md),
invoked as `<twig:>` tags. Each builds its mount wiring with
`stimulus_action('live', 'action', …)` and spreads it into the tag with
`{{ ...map }}` — byte-equivalent to what `live_action()` emits inline. `actions`
owns no trigger CSS at all: the class list on every trigger root is the
primitive's, plus the bare `sy-ac` marker the template contributes. All
five are exercised by
[`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php)
/ [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php)
/ [`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php).

**The five triggers reached Filament's post-consolidation shape in two steps.**
Filament points all five view constants at *support*-package components —
`BADGE_VIEW = 'filament::components.badge'`, `BUTTON_VIEW =
'filament::components.button.index'`, `GROUPED_VIEW =
'filament::components.dropdown.list.item'`, `LINK_VIEW =
'filament::components.link'` — and `Action::fromArray()` keeps a back-compat map
from the old `filament-actions::button-action` / `grouped-action` /
`icon-button-action` / `link-action` names to them. Those deprecated names are
exactly what this port's templates were called, so what shipped first was
Filament's *pre-consolidation* shape: five hand-rolled skeletons, three of them
carrying their own `sy-ac-badge` / `sy-ac-link` CSS in `actions`. `Button` and
`IconButton` collapsed first, because support already had those primitives;
`Badge`, `Grouped` and `Link` followed once support gained
[`Badge`](../support/badge.md), [`Link`](../support/link.md) and
[`DropdownItem`](../support/dropdown-item.md). The click cascade — the only
structural blocker — turned out to be expressible as an attribute map, so all
three collapsed the same way `Button` had.

`ActionGroup`'s own
[`GroupedTrigger`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig)
came along in the same change: it duplicated the identical dropdown-item
skeleton, and is the second consumer that justified making the item a support
primitive rather than leaving it `actions`-owned. Two gaps closed with the
move — `->color()` now reaches a grouped action and a grouped group, which
[`toGroupedHtml()`](../../../vendor/filament/actions/src/Action.php) and
[`toGroupedTriggerHtml()`](../../../vendor/filament/actions/src/ActionGroup.php)
both pass and the hand-rolled templates dropped.

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
this file's Surface table (ported, in some form), 6 more are the
support-level traits referenced above, and a handful carry their own rows in the
[package README](README.md#features) or their own detail file rather than
folding here. The names below are what is left — nothing started, captured as one
list rather than one row each, with each bullet's parenthetical naming the
siblings that *have* landed so the list cannot silently go stale:

- **Mounting & modal chrome beyond `CanOpenModal`'s data**:
  `HasKeyBindings`, `CanBeBooted`, `renderModal()`/`toModalHtmlable()`
  (`CanBeMounted`, `HasExtraModalOverlayAttributes` and
  `HasExtraModalWindowAttributes` are ported — see the
  [package README](README.md#features)).
- **Forms/schemas/infolists integration**: `HasInfolist` and `HasWizard`
  (`HasSchema`, `HasData`, `CanSubmitForm` and `BelongsToSchemaComponent` are
  all composed now — the first through [`has-schema.md`](has-schema.md), the
  other three through their [package README](README.md#features) rows — and the
  `schema*` closure-dependency names landed with the schemas-actions
  integration, see the Details section above).
- **Tables/bulk-action integration**: `BelongsToTable`, `HasTableIcon`,
  `InteractsWithSelectedRecords`, `CanFetchSelectedRecords`,
  `CanDeselectRecordsAfterCompletion`, `bulk()`/`isBulk()`.
- **Grouping & nesting**: `CanBeSorted` and `getClone()`
  (`BelongsToGroup` is ported — see [action-group.md](action-group.md) — and
  `CanCallParentAction`, `HasParentActions`, `parentAction()`/
  `getParentAction()` and `nestingIndex()`/`getNestingIndex()` all landed with
  the nested-actions epic and have rows in the Surface table above).
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
`getHasActionsLivewire()` narrowing step — while the chain itself lives in
the Sylament-only `getLiveComponentOrNull()` peek (mirroring the schemas-side
precedent by name), which `prepareModalAction()`/`prepareModalActionGroup()`
use to bind footer actions without forcing an unbound owner to throw; (2) no
table hop —
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
`BelongsToTable`/bulk actions.

The **child-frame short-circuit is ported**: `if ($this->parentAction instanceof self) { return $context; }`
runs before the schema branch, so a modal action declared by another action
emits an empty context even when it inherited a schema binding from
`prepareModalAction()`. Without it, a nested frame would carry a
`schemaComponent` key up to the host and — but for the host's own
[deeper-frame gate](has-actions.md#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache) —
try to resolve against the host's schema instead of its parent's declared modal
actions. Filament returns `$context` (already holding `recordKey`) at the same
point; ours returns it empty, because `recordKey` is not emitted here yet.
Covered by `ActionTest`'s context cases (empty without a binding, container key,
component-key fallback, keyless container, and a bound-schema action whose
`parentAction` suppresses the key the binding alone would emit).

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
[`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig)
adds `sy-outlined` to the root class list and
[`button.css`](../../../packages/support/assets/src/styles/components/button.css)
carries one `.sy-btn.sy-outlined` variant rule driven by the same
`--sy-color-*` runtime tokens the filled button uses — per the repo styling
contract (semantic classes + runtime tokens, no per-color class maps). The
button template is the only consumer, matching Filament (its link/badge/
icon-button/grouped views ignore `outlined`).

### String click handlers are live-action names, and `close()` dismisses client-side

**A string `action(...)` value is a live-action method name, not a raw client
expression.** Filament's `getLivewireClickHandler()` returns a string action
verbatim as the Livewire click-handler JS; Sylament emits it through
`live_action(component.getClientClickHandler)`, so the string names a
`#[LiveAction]` method on the host (`'callMountedAction'` being the canonical
consumer, via the submit footer action). There is no way to inject arbitrary
JS through this seam — deliberate, and permanent until an arbitrary-JS surface
exists (none is planned). The same reading applies to
`CanCallParentAction`'s handler, which is likewise a
live-action name.

All five trigger templates share one click precedence, the port of
`getLivewireClickHandler()`'s arm order: a string handler first, then a
`callParent()` handler (with the action's own `arguments` attached), then
`shouldClose()`, then the `mountAction` round-trip — and the whole chain is
suppressed when `getUrl()` is filled, because a link navigates. `shouldClose()`
now emits `data-action="sylament--support--modal#dismiss"`, a **client-side**
close, matching Filament's `getJsClickHandler()`: the dialog closes in the
browser, and the controller's `dismissed` event carries the
[`cancelParentActions` round-trip](has-actions.md#the-stack-is-choreographed-declaratively-not-by-client-events)
back to `unmountAction`. Until the nested-actions epic this branch dispatched
`live_action('unmountAction')` directly — correct for a single slot, but it
could not express "close this frame *and* cancel back to a named parent", which
is per-action state the close chain has to carry.

Pinned by `ActionRenderTest`'s string/close/parent-call/precedence cases across
the button and icon-button shapes.

One shape delta among the five templates, deliberate and now commented in
place: `Action/IconButton.html.twig` suppresses click wiring for a **disabled**
action as well as a URL-bearing one, where its four siblings emit the binding
and lean on the rendered `disabled` attribute. Both bindings on that template
read one hoisted `clickWiringSuppressed` flag so they cannot drift apart again.

### HasGroupedIcon narrows its icon type to string

Filament types the grouped icon `string|BackedEnum|Htmlable|Closure|null`;
the port narrows it to `string|Closure|null` — no enum or `Markup` icon
values exist in the actions package yet, the same narrowing `CanOpenModal`'s
`modalIcon` already applies (support's `HasIcon` keeps the wide type, so
`getIcon(default:)` accepts the narrowed value unchanged). Consumed exactly
where Filament consumes it:
[`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig)
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
— the omission is deliberate: no POST actions in the MVP. `Action`'s current template
(`../../../packages/actions/templates/partials/Action/Button.html.twig`)
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
[CanOpenModal detail row](#canopenmodals-chrome-layout-and-wizard-surface-is-not-ported),
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
[`Modals`](../../../packages/actions/templates/components/Modals/index.html.twig) — and is
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

### The modal footer renders real Action instances

Filament's `getModalSubmitAction()` / `getModalCancelAction()` build real
`Action` objects (colored, labeled, wired to `submit`/`action`/`close`) that
the modal view renders through the normal action-rendering pipeline — and so
does Sylament:
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)
loops `getVisibleModalFooterActions()` through `renderComponent()`, so every
footer button is a `makeModalAction()`-built `Action` rendering via
`Action/Button.html.twig` (submit reaches `live_action('callMountedAction')`
through its string click handler; cancel closes the dialog client-side via
`data-action="sylament--support--modal#dismiss"` from `close()`, and the
dismissal wrapper turns that into the `unmountAction` round-trip — see the
[click-precedence divergence](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)).
A third shape exists but has no constructor yet:
`makeModalSubmitAction()` builds a footer action whose `callParent()` handler
calls the **parent** action's `callMountedAction`, ranked above `close()` in
the same precedence chain — Filament's `createAnother` / `attachAnother`
pattern, which arrives here with the prebuilt actions
([detail](#canopenmodals-chrome-layout-and-wizard-surface-is-not-ported)). One limitation moved rather than vanished: an
array-of-shades color (a value `getColor(): string|array<string>|null`
explicitly allows) still cannot be represented in the rendered classes — that
is now the general button-template limitation (`Action/Button.html.twig`
coerces iterable/empty colors to gray, and `getModalSubmitAction()` maps
gray/unset to primary), not a hardcoded-footer artifact.

_See also:_ the global [rename table](../renames.md) — the
`Filament\Actions\Concerns\InteractsWithActions` ⇄ `HasActions` /
`Filament\Actions\Contracts\HasActions` ⇄ `InteractsWithActions` swap, and the
systematic `Htmlable` → `Markup`, Livewire → UX LiveComponent
(`getLivewire()` → `getLiveComponent()`), and Blade → Twig translations this
file leans on throughout, are recorded there rather than re-derived here.
