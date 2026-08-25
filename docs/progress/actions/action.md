# Action (actions)

`Sylament\Actions\Action` — the base class every actions-package action
extends. It composes `Sylament\Support\Components\ViewComponent` (Twig-backed
rendering) and folds in 25 actions-package concerns — `BelongsToLiveComponent`,
`CanBeDisabled`, `CanBeHidden`, `CanBeMarkedAsRead`, `CanBeOutlined`,
`CanClose`, `CanDeselectRecordsAfterCompletion`, `CanDispatchEvent`,
`CanFetchSelectedRecords`,
`CanNotify`, `CanOpenModal`, `CanOpenUrl`, `CanRedirect`,
`CanRequireConfirmation`, `CanUseDatabaseTransactions`,
`HasAction`, `HasArguments`, `HasGroupedIcon`, `HasLabel`,
`HasLifecycleHooks`, `HasMountableArguments`, `HasName`, `HasSize`,
`InteractsWithRecord`, `InteractsWithSelectedRecords` — each a
Surface row below with its own detail/divergence spliced in from its own
audit. It also composes three multi-consumer actions-package concerns tracked
outside this file — `BelongsToGroup` (shared with `ActionGroup`, written up in
[`action-group.md`](action-group.md)), `BelongsToTable` (shared with
`ActionGroup`, written up in [`belongs-to-table.md`](belongs-to-table.md)) and
`BelongsToSchemaComponent` (shared
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
| `call()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | The `try`/`finally` ships whole bar its second branch, which has no cache to clear — [bulk-action.md](bulk-action.md#the-post-call-deselect-reaches-its-host-through-the-nullable-accessor) |
| `cancel(bool $shouldRollBackDatabaseTransaction = false)` / `halt(bool $shouldRollBackDatabaseTransaction = false)` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [semantics](#the-rollback-flags-setter-and-getter-disagree-on-casing) |
| `success()` / `failure()` / `getStatus()` + `$status` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | Filament-exact, tally included: an explicit `success()`/`failure()` wins, an action that accesses no selected records is `Success`, and a bulk one compares its two counters — [bulk-action.md](bulk-action.md) |
| `bulk()` / `isBulk()` + `$isBulk` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | The routing half of the bulk pair — [bulk-action.md](bulk-action.md) |
| `resolveDefaultClosureDependencyForEvaluationByName()` + `$evaluationIdentifier` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | mounted-actions introspection | [detail](#resolvedefaultclosuredependencyforevaluationbyname-covers-13-of-16-names) · [diverges](#the-selected-record-injections-are-gated-on-the-access-flag) |
| `resolveDefaultClosureDependencyForEvaluationByType()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | — | [detail](#resolvedefaultclosuredependencyforevaluationbytype-carries-only-the-added-getset-arms) · [diverges](#the-selected-record-injections-are-gated-on-the-access-flag) |
| `getContext()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#getcontext-drops-the-record-model-gate) |
| `getClone()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`getClone()`) | done | — | Filament-exact one-liner (`clone $this`). A shallow clone is the whole contract on this side: what a caller needs is an action whose `record()` / `table()` / `group()` bindings can be re-stamped per row without touching the declared original. `ActionGroup`'s override adds the member deep-clone ([there](action-group.md#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately)) |
| `toArray()` / `fromArray()` — the 21 ported keys | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`toArray()`/`fromArray()`) | done | — | [diverges](#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened) · [semantics](#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl) · [semantics](#fromarray-restores-only-the-five-built-in-templates) · [semantics](#fromarray-degrades-a-to-dispatch-with-no-target-component-to-a-plain-dispatch) |
| `nestingIndex()` / `getNestingIndex()` + `parentAction()` / `getParentAction()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`$nestingIndex`, `$parentAction` and their accessors) | done | — | Both stamped by the host: `nestingIndex()` per frame in [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php), `parentAction()` by [`prepareModalAction()`](#canopenmodals-unported-remainder) on every action an action declares. `getParentAction()` is what makes the [`getContext()` child-frame short-circuit](#getcontext-drops-the-record-model-gate) decidable |
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
| [`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) | [`Concerns/BelongsToLivewire.php`](../../../vendor/filament/actions/src/Concerns/BelongsToLivewire.php) | done | — | [diverges](#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter) |
| [`BelongsToTable`](../../../packages/actions/src/Concerns/BelongsToTable.php) (shared with `ActionGroup`) | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php) | done | — | [belongs-to-table](belongs-to-table.md) |
| [`CanBeDisabled`](../../../packages/actions/src/Concerns/CanBeDisabled.php) | [`Concerns/CanBeDisabled.php`](../../../vendor/filament/actions/src/Concerns/CanBeDisabled.php) | done | — | [diverges](#canbedisabled-drops-the-authorization-short-circuits) |
| [`CanBeHidden`](../../../packages/actions/src/Concerns/CanBeHidden.php) | [`Concerns/CanBeHidden.php`](../../../vendor/filament/actions/src/Concerns/CanBeHidden.php) | in progress | CanBeAuthorized, ActionGroup, BelongsToTable | [detail](#canbehidden-omits-authorization-group-and-table-cache-integration) |
| [`CanBeMarkedAsRead`](../../../packages/actions/src/Concerns/CanBeMarkedAsRead.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`markAsRead()`/`markAsUnread()`, `shouldMarkAsRead()`/`shouldMarkAsUnread()`, `hasMarkAsRead()`/`hasMarkAsUnread()`) | in progress | the database-notifications panel | [detail](#canbemarkedasread-carries-the-flags-nothing-reads-them-yet) |
| [`CanBeOutlined`](../../../packages/actions/src/Concerns/CanBeOutlined.php) | [`Concerns/CanBeOutlined.php`](../../../vendor/filament/actions/src/Concerns/CanBeOutlined.php) | done | — | [diverges](#outlined-styling-is-a-state-class-not-a-color-map) |
| [`CanClose`](../../../packages/actions/src/Concerns/CanClose.php) | [`Concerns/CanClose.php`](../../../vendor/filament/actions/src/Concerns/CanClose.php) | done | — | [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`CanDeselectRecordsAfterCompletion`](../../../packages/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | [`Concerns/CanDeselectRecordsAfterCompletion.php`](../../../vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | done | — | [bulk-action.md](bulk-action.md) |
| [`CanDispatchEvent`](../../../packages/actions/src/Concerns/CanDispatchEvent.php) | [`Concerns/CanDispatchEvent.php`](../../../vendor/filament/actions/src/Concerns/CanDispatchEvent.php) | done | — | Filament-exact port of the whole non-deprecated surface. `dispatch()` / `dispatchSelf()` / `dispatchTo()` store the event and its payload and stamp `$dispatchDirection` — `false` for a plain dispatch, `'self'`, `'to'` — with only `dispatchTo()` recording a target component; both directed setters route through `dispatch()` first, so a re-`dispatch()` after them resets the direction to `false` exactly as upstream. `eventData()` sets the payload alone, and `getEvent()` / `getEventData()` evaluate at read time, so a closure sees the action's state when it is read rather than when it was declared. Composed onto `Action` only — vendor's `ActionGroup` composes it nowhere either. All five trigger templates read the getters, as an arm of their shared click precedence that emits [payload attributes rather than a click binding](#a-dispatch-action-renders-payload-attributes-not-a-click-binding). Covered by [`CanDispatchEventTest`](../../../packages/actions/tests/Unit/Concerns/CanDispatchEventTest.php) and [`ActionDispatchRenderTest`](../../../packages/actions/tests/Integration/ActionDispatchRenderTest.php) |
| `emit()` / `emitSelf()` / `emitTo()` (deprecated) | [`Concerns/CanDispatchEvent.php`](../../../vendor/filament/actions/src/Concerns/CanDispatchEvent.php) | skipped | — | [diverges](#the-deprecated-emit-aliases-are-not-ported) |
| [`CanFetchSelectedRecords`](../../../packages/actions/src/Concerns/CanFetchSelectedRecords.php) | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | in progress | — | [bulk-action.md](bulk-action.md) |
| [`CanNotify`](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | in progress | — | [detail](#cannotifys-remainder-is-the-failure-body-and-the-two-keyed-off-families), [bulk-action.md](bulk-action.md) |
| [`CanOpenModal`](../../../packages/actions/src/Concerns/CanOpenModal.php) | [`Concerns/CanOpenModal.php`](../../../vendor/filament/actions/src/Concerns/CanOpenModal.php) | in progress | — | [detail](#canopenmodals-unported-remainder), [diverges](#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution) |
| [`CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | in progress | — | [detail](#geturl-carries-the-host-fallback-the-new-tab-introspection-helper-is-missing) |
| POST-to-url support | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | skipped | — | [diverges](#post-to-url-support-is-not-ported) |
| [`CanRedirect`](../../../packages/actions/src/Concerns/CanRedirect.php) | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | done | — | Filament's four members verbatim (`successRedirectUrl()` / `failureRedirectUrl()` setters, `dispatchSuccessRedirect()` / `dispatchFailureRedirect()` resolving the action's own URL first and the host hooks [`getDefaultActionSuccessRedirectUrl()`/`getDefaultActionFailureRedirectUrl()`](has-actions.md#the-getdefaultaction-host-override-hooks-are-not-ported) second), plus a fifth reader, `getPendingRedirectUrl()`, that is [Sylament-only by mechanism](has-actions.md#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag). `redirect()` loses Filament's `?bool $navigate` parameter with the SPA-mode facade behind it. The host read is the **nullable** accessor: a detached action dispatches nothing rather than throwing, and no `instanceof` re-check is written because `getLiveComponentOrNull()` already returns the contract that carries the two hooks — one would be provably always true and fail level max. Covered by [`CanRedirectTest`](../../../packages/actions/tests/Unit/Concerns/CanRedirectTest.php) and, end to end, by [`HasActionsRedirectTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsRedirectTest.php) |
| [`CanRequireConfirmation`](../../../packages/actions/src/Concerns/CanRequireConfirmation.php) | [`Concerns/CanRequireConfirmation.php`](../../../vendor/filament/actions/src/Concerns/CanRequireConfirmation.php) | done | — | [diverges](#confirmation-is-a-flag-not-a-modal-config-layer) |
| [`CanUseDatabaseTransactions`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php) | [`Concerns/CanUseDatabaseTransactions.php`](../../../vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php) | done | — | [diverges](#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade) |
| [`HasAction`](../../../packages/actions/src/Concerns/HasAction.php) | [`Concerns/HasAction.php`](../../../vendor/filament/actions/src/Concerns/HasAction.php) | in progress | — | [detail](#hasaction-carries-the-click-gate-hasaction-is-the-one-gap-left), [diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`HasArguments`](../../../packages/actions/src/Concerns/HasArguments.php) | [`Concerns/HasArguments.php`](../../../vendor/filament/actions/src/Concerns/HasArguments.php) | done | — | — |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | [diverges](#hasgroupedicon-narrows-its-icon-type-to-string) |
| [`label()` / `getLabel()`](../../../packages/actions/src/Concerns/HasLabel.php) | [`label()` / `getLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | The name-derived default takes the segment **before the first dot** (`u($name)->before('.')`), which is Filament's own actions-side choice and differs from `Field`'s `afterLast('.')` — matched deliberately, not a slip to "fix" |
| `hiddenLabel()` / `isLabelHidden()` | [`hiddenLabel()` / `isLabelHidden()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | A hidden label renders as an `aria-label` on the trigger root rather than as visible text — the [button](../../../packages/actions/templates/partials/Action/Button.html.twig) / [link](../../../packages/actions/templates/partials/Action/Link.html.twig) / [badge](../../../packages/actions/templates/partials/Action/Badge.html.twig) triggers read `isLabelHidden` for it, matching what Filament's `labelSrOnly` button prop does |
| `translateLabel()` / `disableLabel()` (deprecated) | [`translateLabel()` / `disableLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported) |
| [`HasLifecycleHooks`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) | [`Concerns/HasLifecycleHooks.php`](../../../vendor/filament/actions/src/Concerns/HasLifecycleHooks.php) | done | — | All six hooks and their runners (`before`/`after`, `beforeFormFilled`/`afterFormFilled`, `beforeFormValidated`/`afterFormValidated`), each evaluating its stored closure through `EvaluatesClosures` and a safe `null` no-op when unset — [`HasLifecycleHooksTest`](../../../packages/actions/tests/Unit/Concerns/HasLifecycleHooksTest.php) covers every pair. Where the `*FormValidated` pair fires is the host's business: [`callMountedAction()`](has-actions.md#callmountedaction-drops-rate-limiting-and-authorization) runs `callBeforeFormValidated()` before the mounted schema's `getState()` and `callAfterFormValidated()` immediately after it returns, so a failed validation — which throws out of `getState()` — never reaches the second. `callBefore()`/`callAfter()` also carry the two lifecycle events · [diverges](#the-lifecycle-events-are-dispatched-as-objects-where-filament-dispatches-a-class-string) |
| [`HasMountableArguments`](../../../packages/actions/src/Concerns/HasMountableArguments.php) | [`Concerns/HasMountableArguments.php`](../../../vendor/filament/actions/src/Concerns/HasMountableArguments.php) | done | — | [diverges](#action-invocation-is-a-php-side-seam-only) |
| [`HasName`](../../../packages/actions/src/Concerns/HasName.php) | [`Concerns/HasName.php`](../../../vendor/filament/actions/src/Concerns/HasName.php) | done | — | [diverges](#getname-tightens-its-return-type-to-non-nullable-string) |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | [diverges](#hassize-drops-the-optimized-render-fast-path-helper) |
| [`InteractsWithRecord`](../../../packages/actions/src/Concerns/InteractsWithRecord.php) incl. `resolveRecordKey()` | [`Concerns/InteractsWithRecord.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithRecord.php) | in progress | — | The record, record-class, record-label and record-title chains all land, `ensureCorrectRecordType()` with them, and `getRecord()`/`getRecordClass()` carry Filament's `bool $withDefault` gate — [semantics](#the-record-chain-reaches-the-schema-the-component-and-the-host), which also names the **two hops deferred with them**: the table label and title hops (`getTable()?->getModelLabel()` / `->getRecordTitle()` — tables' `Table` carries no label or title surface at all) and the plural label trio (`pluralRecordLabel()` / `getPluralRecordLabel()` / `getTitleCasePluralRecordLabel()`, which only the bulk family reads, deferred beside M4 · 1's `CanBeLabeledFrom`) · [diverges](#record-key-resolution-is-hoisted-to-hasactions) |
| [`InteractsWithSelectedRecords`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | in progress | the tables selection host (same epic), the authorization epic | [bulk-action.md](bulk-action.md) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### `resolveDefaultClosureDependencyForEvaluationByName` covers 13 of 16 names

Filament's override resolves 16 dependency-name groups: `arguments`, `data`,
`livewire`, `model`, `mountedActions`, `record`, `selectedRecords`/`records`,
`selectedRecordsQuery`/`recordsQuery`, `schema`, `schemaComponent`/`component`,
`schemaOperation`/`context`/`operation`, `schemaGet`/`get`, `schemaSet`/`set`,
`schemaComponentState`/`state`, `schemaState`, `table`. The port's
[`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/Action.php)
resolves 13 of them: `record`, `recordClass` (Filament's `model`, under the
[record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) —
it resolves [`getRecordClass()`](#the-record-chain-reaches-the-schema-the-component-and-the-host),
so a closure can name the class an action will create into before any record
exists), `arguments`, `liveComponent` (the systematic
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
documents as its `getStateSnapshot` equivalent). `getSchemaState()` carries
Filament's leading `HasExtraItemActions` branch: when the bound schema component
implements schemas'
[`HasExtraItemActions`](../../../packages/schemas/src/Components/Contracts/HasExtraItemActions.php)
and the action's arguments carry a non-empty `item`, `$schemaState` is *that
item's* state rather than the container walk's — how a repeater's extra item
actions see the row they were mounted on. The key is stringified before the call,
so a numeric item key from the wire reaches `getItemState(string $key)` intact.
The guard is a bare `!== ''` rather than [`filled()`](../support/helpers.md): the
key is already narrowed to `int|string` at that point, and `filled()`'s only
static assertion is a null check, so calling it there is an
`alreadyNarrowedType` error at level max.
`ActionGroup::getSchemaState()` gains no such branch, matching Filament: a group
composes no `HasArguments`, so it has no `item` to read.
The `get`/`set` arms keep Filament's **non-null-safe** reach for the schema
component, routed through
[`getSchemaComponentOrFail()`](belongs-to-schema-component.md#getschemacomponentorfail-throws-where-filament-fatals),
whose `?? throw` is what satisfies level max — the unguarded deref was the only
thing in this codebase that ever needed an inline ignore, and the throw is what
replaced it: a schema-less action evaluating a `$get`/`$set` closure still stops
the request, exactly where Filament's does, on a `LogicException` naming the
class rather than a fatal on null, pinned by `ActionTest`. So does an action
declared *inside* a mounted-action schema, for the same reason and equally in
Filament — that schema is a **root** schema with no parent component, so all
three arms of `getSchemaComponent()` return `null`; the browser pass that found
it is written up in
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
vendor-verified and kept). Two more landed with the bulk family:
`selectedRecords`/`records` and `selectedRecordsQuery`/`recordsQuery`, resolving
[`getSelectedRecords()` / `getSelectedRecordsQuery()`](bulk-action.md#the-two-record-readers-share-one-gate-and-one-message)
behind the [access-flag gate](#the-selected-record-injections-are-gated-on-the-access-flag)
Filament has no equivalent of, and resolving the plain selection where Filament
resolves the [individually authorized](bulk-action.md#per-record-authorization-waits-on-the-authorization-epic)
one. Still missing: `mountedActions` (mounted-actions introspection
beyond today's `HasActions`),
`schemaOperation`/`context`/`operation`
(the value exists — schemas' `Schema` has
[`getOperation()`](../schemas/has-operation.md) — and so does the schema-container
hop it would read it off, now that
[`getRecord()` walks there](#the-record-chain-reaches-the-schema-the-component-and-the-host);
nothing blocks the arm any more, it simply has no caller yet),
and `table` (`BelongsToTable`). Coverage: `record`/`arguments` via `ActionTest`'s
"calls the action closure…", `data` via `ActionTest`'s "injects $data from
getData()", `liveComponent` via
[`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php),
the schema names via `ActionTest`'s injection cases (plus
`ActionGroupTest` for the group subset), `recordClass` via `ActionTest`'s
"injects the record class into a closure as recordClass", and the two
selected-record names via `ActionTest`'s gated-off cases plus the tables suite's
[`DeleteBulkActionTest`](../../../packages/tables/tests/Integration/DeleteBulkActionTest.php),
where a real host answers them.

### `resolveDefaultClosureDependencyForEvaluationByType` carries only the added Get/Set arms

Filament's `Action` overrides `resolveDefaultClosureDependencyForEvaluationByType()`
to resolve `Builder::class` to the selected-records query,
`EloquentCollection::class`/`Collection::class` to the individually-authorized
selected records, and `Model::class` (or the record's own class) to the current
record — so `fn (Model $record)` or `fn (Builder $query)` resolves without naming
the parameter. **One of those three arms is ported**: `Builder::class` maps to
`Doctrine\ORM\QueryBuilder::class` and resolves
[`getSelectedRecordsQuery()`](bulk-action.md#the-two-record-readers-share-one-gate-and-one-message),
behind the same [access-flag gate](#the-selected-record-injections-are-gated-on-the-access-flag)
the by-name arms carry. The other two stay out: there is no collection arm to
port — a Doctrine read returns a plain `list`, so the `records` **name** carries
it — and the record arm's blocker is
answered rather than gone: [`getRecord()`](#the-record-chain-reaches-the-schema-the-component-and-the-host)
now walks to the schema container and component, so an arm landed today would
resolve rather than answer `null` for every action bound to a record-carrying
schema. It stays out only because nothing has asked for it — and because the
Doctrine port has no `Model::class` base to key the arm on, so it would have to
resolve on the record's *own* class the way
[`getCustomRecordTitle()`](#the-record-chain-reaches-the-schema-the-component-and-the-host)'s
typed injection already does. A type-hinted record parameter therefore still falls through to
`Component::resolveDefaultClosureDependencyForEvaluationByType()` (support) and is
not resolved.

What the override does carry is the `Get`/`Set` pair, which
[diverges](#getset-resolve-by-type-as-well-as-by-name) from Filament — see the
note for why.

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

### CanBeMarkedAsRead carries the flags; nothing reads them yet

**In:** [`CanBeMarkedAsRead`](../../../packages/actions/src/Concerns/CanBeMarkedAsRead.php)
is a method-for-method, body-for-body port of the six members Filament declares
inline on its `Action` class rather than in a `Concerns/` trait —
`markAsRead()` / `markAsUnread()` setters, their `shouldMarkAsRead()` /
`shouldMarkAsUnread()` evaluate-and-cast getters, and the
`hasMarkAsRead()` / `hasMarkAsUnread()` `!== false` declared-ness pair. It is a
trait here because that is where this port files a single-purpose member group
(the same call `CanClose` made, whose shape it copies exactly); the surface is
identical either way. Both flags round-trip through
[`toArray()`/`fromArray()`](#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl),
which is the reason it ships now: a notification action declares
`->markAsRead()` on the sending side and the flag has to survive the flash bag.

**Out:** the behaviour. Filament reads the pair in
`getAlpineClickHandler()`, which emits the literal `markAsRead()` /
`markAsUnread()` Alpine expressions against the database-notifications panel's
Livewire component, and in `hasNoClickHandler()`, where the two `has*()` getters
join the "this action does something on click" test. Neither exists here:
there is no database-notifications panel to mark anything read *in*, and the
port's click wiring is the single `live_action('mountAction', …)` call baked
into the trigger templates rather than a resolved handler expression
([diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)).
So `->markAsRead()` currently changes nothing a user can see.

**Next step:** wire both flags into the click cascade when the
database-notifications panel lands, alongside the notification-side surface
[`notifications/README.md`](../notifications/README.md) tracks.

### CanNotify's remainder is the failure body and the two keyed-off families

**In:** `successNotificationTitle()` / `failureNotificationTitle()` and their
`getSuccessNotificationTitle()` / `getFailureNotificationTitle()` evaluated
getters, plus — since M4 · 2 — the object arms `successNotification()` /
`failureNotification()`: the `Notification|Closure|null` setters that let a
caller replace or amend the whole notification, each deriving
`isSuccessNotificationDisabled` / `isFailureNotificationDisabled` from a `null`
argument exactly as Filament derives them. Method for method identical to
Filament's non-bulk path, and covered by
[`CanNotifyTest`](../../../packages/actions/tests/Unit/Concerns/CanNotifyTest.php)
via the
[`CanNotifyFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanNotifyFixture.php).
`sendSuccessNotification()` / `sendFailureNotification()` are called from
[`HasActions::callMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php)
exactly where Filament's `callMountedAction()` calls them, and each now runs
Filament's own order: return early when the matching disable flag is set or
`class_exists(Sylament\Notifications\Notification::class)` is false; build
`Notification::make()->success()` / `->danger()->persistent()` and title it; hand
that built object to the override, evaluated with `notification` injected, its
return replacing the built one and a `null` return keeping it; and `send()` only
when a title survives. Those are two different ways to say *not this one* —
`successNotification(null)` disables the send outright, and a closure returning
`$notification->title(null)` suppresses it — and both are Filament's. The
`class_exists()` condition is a
[guarded soft integration](#the-notification-arm-is-a-guarded-soft-integration-not-a-require),
not a `require`: the imported symbol is reached only inside these two bodies, and
naming it in a property or parameter *type* never triggers autoload. Delivery
from the flash to the browser is the notifications package's own job
([`LiveDeliverySubscriber`](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php)
turns each flashed entry into a `sylament:notification` browser event on a live
request), so nothing here dispatches. The end-to-end pin lives in the
notifications suite —
[`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php),
the only kernel that boots both bundles — with tables'
[`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php)
proving the whole confirm→delete→toast shape.

Three shape notes. The title is checked **after** the `Notification` is built,
where the port used to check it first and skip the build entirely. The object
arms are what forced the reorder: an override closure is entitled to title an
otherwise untitled notification, and a pre-check would have skipped a send
Filament makes. The cost is one throwaway `Notification` per untitled, unoverridden
action — accepted, and the reason the `class_exists()` guard now sits alone in
the early return rather than sharing it with a `blank($title)`. `persistent()` on
the failure side is Filament's (`->persistent()` on its failure notification, so
an error toast waits to be dismissed). And the **partial-bulk `warning()` arm** is
ported: an action that both accesses selected records and still has a non-zero
successful count sends `warning()` rather than `danger()`, so "deleted 2 of 5"
does not read as a total failure — written as an `if`/`else` where Filament
chains `->when()`, since there is no `when()` on the port's `Notification`.
`getFailureNotificationTitle()` evaluates its closure with the four counting
injections that go with it — see
[bulk-action.md](bulk-action.md#the-failure-notification-carries-four-injections-of-filaments-nine)
for what those are and which five of Filament's nine are deferred. Both arms are
pinned from the notifications suite's
[`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php),
the only kernel that boots both bundles.

**Missing vs Filament:** `failureNotificationBody()` and the `->body(...)` call
that reads it while building the failure notification. `unauthorizedNotification()`
/ `unauthorizedNotificationTitle()` / `sendUnauthorizedNotification()` (keyed off
Laravel's `Auth\Access\Response`) and `rateLimitedNotification()` /
`rateLimitedNotificationTitle()` / `sendRateLimitedNotification()` (keyed off
`DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException`) are also
absent — there is no authorization-response or rate-limiting concern in the
actions port yet for either to key off of, and each would otherwise be the same
build-evaluate-send body the two arms above already run. Of the bulk-only
surface, `getFailureNotificationNamedInjections()` ships in its four-injection
form while `missingBulkAuthorizationFailureNotificationMessage()`,
`missingBulkProcessingFailureNotificationMessage()` and the bulk message
formatting inside `getFailureNotificationBody()` wait on the
[keyed message buckets](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages).
The deprecated
`successNotificationMessage()` / `failureNotificationMessage()` aliases are
dropped outright — Filament itself deprecates them in favor of the `*Title()`
setters this trait already carries, so nothing is lost.

**Next step:** add `failureNotificationBody()` and read it into the failure
build. `unauthorizedNotification*` and `rateLimitedNotification*` wait on an
authorization-response equivalent and a rate-limiting concern respectively; the
remaining five bulk injections wait on the keyed message buckets.

### CanOpenModal's unported remainder

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
`getModalFooterActions()` carries Filament's `isWizard()` early return (a wizard
renders its own footer row, so the modal must not render a second one — landed
with the [wizard arm](has-schema.md#the-wizard-arm)) and its
center-alignment `array_reverse` — applied only
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
[`getContext()`](#getcontext-drops-the-record-model-gate) recognize a
child frame. The `instanceof` narrowing exists because the trait is also
composed by non-`Action` classes; Filament passes `$this` unconditionally from
a class that is always an `Action`.

**Next step**: the four
`?? true` literals become reads of the `ModalComponent`-equivalent
global-defaults seam if and when that lands. What is left beyond those is one
scoped delta from the list above — `prepareModalAction()`/
`prepareModalActionGroup()` still drop Filament's `->table($this->getTable())`
binding. No longer blocked, now that
[`BelongsToTable`](belongs-to-table.md) ships: it lands with the table's own
action rendering, which is the first thing that binds a table to an action at
all.

### getUrl() carries the host fallback; the new-tab introspection helper is missing

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

Also ported, and the reason the prebuilt
[`CreateAction`](create-action.md) / [`EditAction`](edit-action.md) work at all:
the host fallback. When no explicit `url` closure resolves a value, `getUrl()`
asks the host —
`$this->evaluate($this->url) ?? $this->getLiveComponentOrNull()?->getDefaultActionUrl($this)`,
Filament's line with its `getHasActionsLivewire()` replaced by the port's
[null-tolerant accessor](#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter)
(the throwing `getLiveComponent()` would turn "this action is not on a host" into
an exception at render time, which is exactly the case that has to answer
`null`). The hook itself is
[on the contract with a `null` default on the trait](interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action).
Precedence is Filament's: an explicit `url()` never consults the host, and the
`hasModal()` guard still wins over both.

Missing vs Filament: `Action::render()` consults a plain introspection helper,
`hasShouldOpenUrlInNewTab()`, when deciding button vs. link markup; Sylament
has no equivalent (only the evaluated `shouldOpenUrlInNewTab()` exists).

Next step: add `hasShouldOpenUrlInNewTab()` with the optimized-render pipeline
that consumes it.

### HasAction carries the click gate; `hasAction()` is the one gap left

Ported: both value shapes. `action(Closure|string|null $action): static` /
`getAction(): Closure|string|null` round-trip a closure or a string, and
`Action::call()` evaluates the value, injecting `record`/`arguments`/
`liveComponent` for closures and returning a string as-is (Filament-identical
no-op semantics) — covered by `ActionTest`. The string branch renders through
`getClientClickHandler(): ?string` — the Sylament port of
`getLivewireClickHandler()`'s string arm — which every trigger template reads
first in its click precedence (string handler, then a
[dispatch payload](#a-dispatch-action-renders-payload-attributes-not-a-click-binding),
then a `callParent()` handler,
then `shouldClose()` → a client-side dismiss, then the `mountAction`
round-trip); the reinterpretation of
the string as a live-action name rather than a raw client expression is
recorded as a
[divergence](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side).

Missing vs Filament's `HasAction`:
- **`hasAction(): bool`.** Helper predicate not ported; trivially derivable from `getAction() !== null` at call sites today, but no equivalent method or test exists.

The click gate itself is now **ported**, under the `Livewire` → `LiveComponent`
[idiom translation](../renames.md#idiom): `liveComponentClickHandlerEnabled(bool|Closure|null $condition = true)`
and `isLiveComponentClickHandlerEnabled(): bool` on the trait, with Filament's
three arms in Filament's order — an explicit override wins, then `filled(getUrl())`
and `canSubmitForm()` each turn it off, otherwise it is on. The two default arms
had lived in the templates since the forms-in-modals epic
(`hasLiveClickBinding = not component.getUrl and not component.canSubmitForm` in
[`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig)
and its `link`/`badge`/`grouped` siblings, and an extra term in
[`Action/IconButton.html.twig`](../../../packages/actions/templates/partials/Action/IconButton.html.twig)'s
`mountAttributes`); all five now read the getter instead, which moves the logic
without changing a single rendered attribute. That guard is load-bearing rather
than tidiness: [`getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php)
gives a form-wrapped action `->submit(name)->action(null)`, so without it the
trigger falls through its click cascade to the `mountAction` default and every
confirm click fires a **second**, unrelated live request — `mountAction('submit')`,
naming an action that does not exist — alongside the form's own
`callMountedAction`. The close arm is unaffected in both codebases: Filament keeps
it on a separate `x-on:click`, the port on a separate `data-action`.

What promoted it was the first caller needing the explicit override:
[`CheckboxList`](../forms/checkbox-list.md#the-bulk-toggle-actions-render-but-never-dispatch)
builds two bulk-toggle actions that must render as links and dispatch nothing,
the click living on the `<span>` around each — Filament's own
`livewireClickHandlerEnabled(false)` shape.

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

### The record chain reaches the schema, the component and the host

[`Action::getRecord(bool $withDefault = true)`](../../../packages/actions/src/Action.php)
walks Filament's full chain: its **own** record (evaluated, then run through
`ensureCorrectRecordType()`), its **group**, its
[**schema container**](belongs-to-schema-component.md), its **schema component**,
and finally the host's `getDefaultActionRecord()`. The last hop is the only one
`$withDefault: false` switches off, which is what makes it usable as a question
about *bindings* rather than about what the action would resolve at run time.
`getRecordClass()` carries the same gate over its own chain — custom class →
[`getTable()?->getRecordClass()`](../tables/table.md#the-record-class-is-read-off-the-base-querys-root-entity)
→ the resolved record's class → group → schema container → schema component →
host `getDefaultActionRecordClass()` — so a create action inside a table resolves
the class it will create into without a record existing yet. The table hop sits
**above** the record hop in Filament's order and stays there: a `records()`-lane
table answers `null` (it has no root entity), so only the query lane contributes.

Three classes carry three different bodies, and that is Filament's shape rather
than duplication. Filament writes one trait method gated on `$this instanceof
Action`, so a **group** never reaches past its own group, and neither does a
class that composes the trait alone; here the trait keeps the group-only chain,
[`ActionGroup`](action-group.md#getrecord-is-duplicated-onto-action-and-actiongroup-to-satisfy-phpstan)
adds the group hop to `getRecordClass()`, and `Action` carries the whole walk.

**`ensureCorrectRecordType()` drops what does not match.** A record reached
through *any* hop is discarded when the action declares a `recordClass()` the
record is not an instance of — Filament's guard, and what lets a
`CreateAction::make()->recordClass(Other::class)` sit inside a table or a form
bound to something else without silently inheriting the wrong record. It is a
pass-through for a non-object (an array record, a bare key), because a class
guard has nothing to say about either.

A **set** record that fails the guard **stops the chain** rather than falling
through it, which is Filament's shape and is easy to get backwards: its
`if ($record) { return $this->ensureCorrectRecordType($record); }` returns `null`
without ever reaching the group. So `->record($a)->recordClass(B::class)` inside a
group holding a real `B` answers `null`, not the group's record — an explicit
binding that does not fit is an error to surface, not a reason to inherit
something else. Only an **absent** own record hands the walk on. Pinned by
`ActionTest`'s "stops at its own record when the record class does not match".

**The title is guarded on the record existing.** `getCustomRecordTitle()` returns
`null` the moment there is no record, *before* evaluating the `recordTitle()`
closure or reading `recordTitleAttribute()` — Filament's `if ($record)` gate.
Without it a `fn (Article $record) => $record->title` heading would throw during
[`shouldOpenModal()`](#the-action-modal-template-only-renders-part-of-the-full-action-modal),
which runs while the trigger renders and long before a record is bound. The
closure receives the record both by name (`$record`) and by its own class as a
type hint; the attribute is read through schemas'
[`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), so a
dotted path traverses. Eloquent's `'->'` JSON-path rewrite is not ported — the
accessor already speaks dots. `getRecordTitle()` then falls to the host's
`getDefaultActionRecordTitle()` and finally to the record **label**, so a
titleless action still renders a heading.

**The label chain adds one arm Filament does not have.** Custom label →
otherwise derive from the class through
[`get_record_label()`](../support/helpers.md). Where the action's class *is* the
host's own `getDefaultActionRecordClass()`, the host's label wins — that is how a
resource page names its records once instead of at every action. Filament returns
the host's answer flatly there, so a host that owns the class but answers no label
yields `null`; ours falls through to the derived label instead. A silent host is
the common case (every host that has not overridden the hook), and `null` would
leave a heading with nothing in it where the class name was right there.

**A host default never rides the wire.** `getContext()` stamps `recordKey` from
`getRecord(withDefault: false)`, so the key a trigger sends back names a record
something actually *bound* — the host answers its own default again on the way
back, and stamping it would only re-send the host its own state. Nothing renders
differently today (no shipped host answered the hook before this commit); the
gate exists so that adding one does not start widening every trigger's context.

**`shouldClearRecordAfter()` / `clearRecordAfter()`** are the pair a
create-shaped action flips — [`CreateAction`](create-action.md) does, from this
epic's `B` lane, and nothing overrides them yet: the base answers `false` and
`clearRecordAfter()` returns without touching anything, so an action that never
opts in cannot lose the record it was mounted with.

**Two hops of Filament's are deferred rather than dropped.** The label and title
chains each hop through the table before falling back — `getTable()?->getModelLabel()`
in `getModelLabel()`, `getTable()?->getRecordTitle($record)` in `getRecordTitle()`,
and `getTable()?->getRecordTitleAttribute()` in `getRecordTitleAttribute()`. Tables'
[`Table`](../tables/table.md) carries no label or title surface at all, so there is
nothing for those hops to call; only `getRecordClass()`'s table hop exists here,
because `Table::getRecordClass()` does. They land when the table grows the surface.
The second is the **plural trio** — `pluralRecordLabel()`, `getPluralRecordLabel()`
and `getTitleCasePluralRecordLabel()` (plus `getCustomPluralRecordLabel()`) — which
only the bulk family reads, and which is deferred beside M4 · 1's
`CanBeLabeledFrom`; Filament's own `getPluralModelLabel()` additionally branches on
`locale_has_pluralization()`, and [i18n is parked](../roadmap.md), so the arm would
have nothing to ask.

**Three guards read `!== null` where Filament reads `filled()`**, and one arm is `??`
where Filament tests `filled()`. `getRecordClass()`'s custom-class check,
`ensureCorrectRecordType()`'s, and `hasCustomRecordTitle()` all use `!== null`
(`hasCustomRecordTitleAttribute()` is not one of the three — it is `!== null`
upstream too, at `InteractsWithRecord.php:249`, so the pair is now consistent
rather than split); `getRecordTitle()`'s host arm is
`getCustomRecordTitle($record) ?? $host?->getDefaultActionRecordTitle($this) ?? …`
where Filament wraps each in `filled()`. The difference is only ever an **empty
string**: `->recordClass('')` is not a class, `->recordTitle('')` counts as custom
here and does not upstream, and a host answering `''` stops the title chain here
where Filament would fall through to the label. One more delta, in the same family:
Filament's `getRecordTitle()` nulls a `$record` **argument** that is not of the
custom record class before using it; ours passes the caller's argument straight to
`getCustomRecordTitle()`, on the reading that an explicitly-passed record is the
caller's assertion, not an ambient one to be second-guessed —
`ensureCorrectRecordType()` still guards every record the chain *resolves*.

The own-record gate is the one place that split had to be **repaired rather than
recorded**. Filament's gate is bare truthiness (`if ($record)`), which is `true`
for *every* object; a plain `filled()` is not — it counts a `Countable`-empty or
whitespace-only `Stringable` object as blank, so an empty collection bound as a
record would have fallen through to the group instead of stopping. The gate is
therefore `is_object($record) || filled($record)`: an object always stops, exactly
as upstream, and a scalar key stops when it is filled. The residual delta is
scalars only — `->record(0)` stops here and falls through upstream — which is the
same empty-string family as the rest of this paragraph. Pinned by `ActionTest`'s
"stops at an own record object even when it is empty".

The class-string is injectable now: `resolveDefaultClosureDependencyForEvaluationByName()`
resolves **`recordClass`**, which is the surface
[`renames.md`](../renames.md#renames) was waiting for before deciding what
Filament's `$model` closure parameter becomes. The rename rows this landed —
`model()`/`getModel()`/`getCustomModel()`,
`modelLabel()`/`getModelLabel()`/`getTitleCaseModelLabel()`/`getCustomModelLabel()`,
`get_model_label()`, `getDefaultActionModel`, `getDefaultActionModelLabel`,
`getMountedActionSchemaModel`, and `$model` → `$recordClass` — are all in that
table, under the
[record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record).

Covered by
[`InteractsWithRecordTest`](../../../packages/actions/tests/Unit/Concerns/InteractsWithRecordTest.php)
(the trait's own chains, the guarded title and the type guard),
[`ActionTest`](../../../packages/actions/tests/Unit/ActionTest.php) (both schema
hops — container and component separately, since a root container's
`getParentComponent()` is null and so the container case never reaches the
component arm — the own-record stop, the `recordClass` injection, the context
gate, the hostless label arm, the fall-to-label title and the clear-after pair)
and
[`HasActionsRecordTest`](../../../packages/actions/tests/Unit/Concerns/HasActionsRecordTest.php)
(all four host hooks answering at once, `withDefault: false` refusing both the
record and the class, the derive-anyway fallback for a host that owns the class
but answers no label, and the host label being ignored for a class that is not
the host's).

### The rollback flag's setter and getter disagree on casing

[`cancel()`](../../../packages/actions/src/Action.php) and `halt()` take
`bool $shouldRollBackDatabaseTransaction = false` and stamp it onto the
exception they throw, so the [`callMountedAction()` catch
arms](has-actions.md#callmountedaction-drops-rate-limiting-and-authorization)
can commit or roll back per throw. Both defaults are `false`: a cancelled or
halted action **commits** what ran before it unless it says otherwise. The pair
on [`Cancel`](../../../packages/support/src/Exceptions/Cancel.php) and
[`Halt`](../../../packages/support/src/Exceptions/Halt.php) reads wrong and is
correct: the setter is `rollBackDatabaseTransaction()` with a capital B, the
getter `shouldRollbackDatabaseTransaction()` with a lowercase one. That is
Filament's own inconsistency, kept verbatim — normalising either half would be
an API rename of a public method with no [renames.md](../renames.md) row, for a
typo migrating users already grep for.

### The serialized key list drops `alpineClickHandler` and `shouldPostToUrl`

[`toArray()`](../../../packages/actions/src/Action.php) writes 21 keys where
Filament's writes 23, and the two missing ones read as an oversight only until
you check what they would carry. **`alpineClickHandler`** is Filament's
`getCustomAlpineClickHandler()` — an Alpine expression, a string evaluated in
the browser against the component that renders it. The port's translation of a
custom click handler is a **live action name bound to a host**
([diverges](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)),
and a live action name means nothing to whatever host rehydrates the action on
the far side of the hop, so there is no value worth writing. **`shouldPostToUrl`**
has no getter to call at all — POST-to-url is
[not ported](#post-to-url-support-is-not-ported). Every other key is present,
with vendor's `view` renamed `template` (the systematic Blade → Twig
translation from [`renames.md`](../renames.md), not a rename of its own) —
though present is not the same as identical: four of them carry a different
**value shape** than Filament writes
([diverges](#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened)).

Nothing closure-bearing is written either, and that *is* Filament-exact rather
than a port decision: `action`, the modal configuration, the lifecycle hooks and
the visibility closures are absent from both key lists, because a `Closure` does
not survive a session. An action restored from an array is the **trigger** —
label, colour, icon, url, dispatch, template — not the behaviour behind it.

### `fromArray()` restores only the five built-in templates

The `template` key crosses a trust boundary — a serialized action rides the
session flash bag, and its restored value is handed to `$twig->render()` by
[`ViewComponent::render()`](../../../packages/support/src/Components/ViewComponent.php)
— so [`fromArray()`](../../../packages/actions/src/Action.php) admits it only
when it is one of `Action`'s own five `*_TEMPLATE` constants
(`BADGE`, `BUTTON`, `GROUPED`, `ICON_BUTTON`, `LINK`). Anything else is
**dropped, not rejected**: the setter is simply never called, `getTemplate()`
falls through to `$defaultTemplate`, and the action renders as a button. The
whitelist is `static::`-qualified, so a subclass that overrides a constant
whitelists its own value.

Filament gates the same key with `isViewSafe()`, a
`Str::startsWith($view, 'filament::')` prefix test, which admits any view in the
package namespace including ones a user registered there. That arm is not
ported: with five templates and no custom-template surface yet, a prefix test
would only widen what a tampered payload can name. It returns with the custom
template surface, not before.

### `fromArray()` degrades a `to` dispatch with no target component to a plain dispatch

Filament restores the dispatch trio with
`dispatchTo($data['dispatchToComponent'] ?? null, …)` — passing a possible
`null` straight into a non-nullable `string $component` parameter, which Laravel
tolerates and `declare(strict_types=1)` does not. The port guards the arm
instead (`$direction === 'to' && is_string($to)`), so a payload claiming
direction `to` without a usable component falls through to the `default` arm and
dispatches the event **plainly** rather than throwing a `TypeError` mid-render.
The event and its data still travel; only the targeting is lost. Pinned by the
tampered-payload case in
[`ActionSerializationTest`](../../../packages/actions/tests/Unit/ActionSerializationTest.php).

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

### A dispatch action renders payload attributes, not a click binding

An action carrying `dispatch()` / `dispatchSelf()` / `dispatchTo()` renders no
handler at all. Its trigger root gets five data attributes and nothing else:

| Attribute | Source | Omitted when |
| --- | --- | --- |
| `data-sylament-action-event` | `getEvent()` | the arm never fires without it |
| `data-sylament-action-event-data` | `getEventData()`, JSON-encoded | the payload is empty |
| `data-sylament-action-dispatch-to` | `getDispatchToComponent()` | `getDispatchDirection()` is not `'to'` |
| `data-sylament-action-dispatch-self` | `getDispatchDirection()` | the direction is not `'self'` |
| `data-sylament-action-close` | `shouldClose()` | the action does not close |

The split is the point: the templates stamp the **payload**, and whatever owns
the click context does the **delivery**. A toast is rendered by the
notifications component, not by the live component whose action it is, so the
`data-action="live#action"` binding every other arm emits would be dead markup
there — bound to a controller that dispatches against the wrong host, or against
no host at all. Handing the payload to the context owner is what lets the same
`Action` render inside a modal, a table row, or a toast without the template
knowing which.

Three things about the attribute set are easy to get wrong.
`data-sylament-action-dispatch-to` branches on the **direction**, never on the
component being non-null: `dispatchTo('X', 'a')->dispatch('b')` leaves
`$dispatchToComponent` set to `'X'` while resetting the direction to `false`
(vendor-exact, since both directed setters route through `dispatch()` first), so
reading the component alone would address a plain dispatch at a stale target.
`data-sylament-action-close` is a **neutral marker** — it says the action closes,
not how — and it is emitted by both the dispatch arm and the bare-close arm;
the bare-close arm keeps its `data-action="sylament--support--modal#dismiss"`
alongside, which is inert outside a modal (Stimulus resolves an action token
against ancestor controllers only, so a token naming a controller that is not
one binds nothing and logs nothing). And `data-sylament-action-dispatch-self` is
a **valueless** marker rather than a target: `'self'` names no component, so the
key carries the boolean `getDispatchDirection() == 'self'` and the attribute bag
renders it bare on `true` and drops it on `false` — which is why the reading side
tests for presence, never for a value. Its meaning is *resolve the emitter
relative to this trigger*, so it and `-dispatch-to` are mutually exclusive by
construction: a direction is one or the other.

One delta against Filament, and it is the shape rather than the rank. Filament
emits `getLivewireEventClickHandler()` — a `$dispatch(...)` /
`$dispatchSelf(...)` / `$dispatchTo(...)` **JavaScript expression** on
`wire:click`; the port has no arbitrary-JS seam and does not want one
([same reading](#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)
as the string click handler), so the payload crosses as data instead of code.
The **rank** is vendor-exact: `getLivewireClickHandler()` tries the string
handler, then the event, then `getParentActionCallLivewireClickHandler()`, and
the five templates rank them the same way, so an action carrying both a
`callParent()` handler and a `dispatch()` dispatches rather than calling the
parent — in both codebases.

Pinned by
[`ActionDispatchRenderTest`](../../../packages/actions/tests/Integration/ActionDispatchRenderTest.php),
which covers the payload, the direction branch (including the stale-target
`dispatchTo(…)->dispatch(…)` case and the self marker's presence for
`dispatchSelf()` alone), the empty-payload omission, the close marker
on both arms, the url arm still winning, the rank against the client and
parent-call handlers across all five templates, and the icon-button shape
reaching the same arms through its own guard expressions.

The delivery half — who reads these attributes, and how a `'self'` emitter is
resolved — is
[actions-bundle.md](actions-bundle.md#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

### The lifecycle events are dispatched as objects, where Filament dispatches a class-string

[`callBefore()`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php)
dispatches `new ActionCalling($this)` before evaluating its closure, and
`callAfter()` dispatches `new ActionCalled($this)` from a `finally`, so it fires
even when the after-closure throws — position-for-position Filament's. What
crosses the wire is not. Filament calls
`Event::dispatch(ActionCalling::class, $this)`: Laravel's
`parseEventAndPayload()` takes the object branch only when the *event* is an
object, so a class-string event leaves the payload as `[$action]` and the event
class is never constructed — a listener registered on `ActionCalling::class`
receives an `Action`, and `getAction()` never runs. Symfony's
`EventDispatcherInterface::dispatch(object $event, ?string $eventName = null)`
requires a real object, so the port constructs one, and its listeners receive
what Filament's own class shape implies:
[`ActionCalling`](../../../packages/actions/src/Events/ActionCalling.php) /
[`ActionCalled`](../../../packages/actions/src/Events/ActionCalled.php), final
readonly, one promoted `Action`, one `getAction()`. Neither is stoppable or cancellable —
parity, and `halt()` / `cancel()` remain the control channel. The dispatcher is
a nullable
`Symfony\Contracts\EventDispatcher\EventDispatcherInterface` on a `#[Required]`
setter, the [`EvaluatesClosures` pattern](../support/component.md): an action
built with `new` rather than through the container dispatches nothing — and the
nullsafe call skips constructing the event at all, so nothing is built to be
thrown away. Covered by
[`HasActionsEventsTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsEventsTest.php)
(both events around one call, in order, carrying the same instance; the
`finally` arm through a throwing after-closure).

### The transaction trio resolves a Doctrine connection instead of a facade

Filament's `CanUseDatabaseTransactions` is three imperative methods over
Laravel's `DB` facade, each returning early unless `databaseTransaction()` was
called — imperative rather than a `DB::transaction($callback)` wrapper because
the pipeline decides commit-vs-rollback per exception type. The port keeps that
shape and the `false` default exactly, and answers the "which connection?"
question Laravel's facade answers globally:
[`getDatabaseTransactionConnection()`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php)
prefers the table's own query manager
(`getTable()?->getQueryEntityManager()?->getConnection()` — reached through the
same [guarded soft reference](belongs-to-table.md) the rest of the table surface
uses, so nothing is required of a tables-less consumer) and falls back to the
default manager of a `#[Required] ?ManagerRegistry`. That registry is the one
[`DeleteAction`](delete-action.md) used to declare for itself and now inherits
from here — one setter, one service, no duplicate. Both fallback failures throw
rather than silently skipping the transaction: no registry (an action built
outside the container) names the container in its message, and a non-ORM manager
names the type it got. The ORM's own `wrapInTransaction()` is deliberately not
used — it `close()`s the EntityManager on failure and cannot express the
per-exception commit/rollback split the pipeline needs; DBAL 4 nests
unconditionally through savepoints, so the wrap composes with an outer test
transaction.

M4 · 2 put a fourth member beside the trio, for the write actions rather than for
a transaction:
[`getEntityManagerForRecord(object|class-string $recordOrClass): EntityManagerInterface`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php)
answers *which manager owns this record's class*, on the same precedence the
connection resolver uses — the table's `getQueryEntityManager()` when the action
has a query-lane table, else the registry's `getManagerForClass()`. It lives here
because this is the trait that already carries the `#[Required] ?ManagerRegistry`
every action inherits, and it has no Filament counterpart at all: Eloquent
resolves a model's connection from the model. It is `protected`, and it throws
rather than degrading on each of its three misses — no registry names the
container-built instance, no manager for the class names the class, and a
non-ORM `ObjectManager` names the type it got. [`DeleteAction`](delete-action.md)
and [`DeleteBulkAction`](bulk-action.md) keep resolving their own manager this
epic, because their messages are pinned by tests; adopting the helper there is a
sweep item.

Covered by
[`CanUseDatabaseTransactionsTest`](../../../packages/actions/tests/Unit/Concerns/CanUseDatabaseTransactionsTest.php)
for the default-off and fail-fast arms, and for all four arms of the record
resolver through
[`CanUseDatabaseTransactionsFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanUseDatabaseTransactionsFixture.php),
which exists to expose the protected method — the shipped write bodies reach it
through `$this` and no public surface reveals it.

### The selected-record injections are gated on the access flag

Filament's four selected-record arms —
`selectedRecords`/`records`, `selectedRecordsQuery`/`recordsQuery` by name and
`Builder::class` by type — call the readers unconditionally, so *any* action
whose closure happens to name a `$records` parameter throws the
`accessSelectedRecords()` `LogicException` instead of resolving. That is
tolerable in Filament, where `$records` is a bulk-action idiom; it is not here,
because the same name reads naturally on a plain action that means *its own*
collection of something. So all four arms are wrapped in
`hasCanAccessSelectedRecords()` — the **presence** test, not the evaluated flag,
so a closure-valued `accessSelectedRecords(fn () => …)` still counts — and fall
through to `parent::resolveDefaultClosureDependencyForEvaluationBy*()` when it
is false. The parameter then resolves the ordinary way: an injection, the
container, its own default, or `null`.

The gate is deliberately *not* `canAccessSelectedRecords()`: evaluating the flag
to decide whether to resolve would run a user closure on every parameter of
every action, and a flag that evaluates false would silently hand the parameter
a container service rather than raising the gate the caller asked for.
Pinned by `ActionTest`'s three fall-through cases.

### `$get`/`$set` resolve by type as well as by name

Filament binds the state utilities to closure **parameter names** only:
`'schemaGet', 'get' => [...makeGetUtility()...]` and
`'schemaSet', 'set' => [...makeSetUtility()...]` in
[`Action`](../../../vendor/filament/actions/src/Action.php)'s by-name resolver,
with nothing for `Get::class`/`Set::class` in its by-type one. Its
[schema `Component`](../../../vendor/filament/schemas/src/Components/Component.php),
by contrast, resolves both by type. So upstream `fn (Get $anything)` works inside
a field closure and fails inside an action closure, purely because of the
parameter's name.

The port keeps both by-name arms verbatim and **adds** `Get::class`/`Set::class`
to the by-type resolver on `Action` (and `Get::class` on
[`ActionGroup`](action-group.md), matching that class's narrower by-name surface —
upstream gives the group `get` and no `set`, and the port does not invent one).
A closure parameter typed `Get`/`Set` now resolves under any name, on an action
exactly as on a component.

The reason is internal consistency: the port's own component tier already accepts
a typed `Get`, and having the same type-hint silently fail one tier up is a
difference users hit as a bug rather than a design. Nothing upstream depends on
the omission — this only widens what resolves, so every Filament-shaped closure
keeps working unchanged.

The cost is that `getSchemaComponentOrFail()` now fires for a `Get`/`Set`-typed
parameter on an action rendered outside a schema, where before that closure died
on an unresolved-parameter `RuntimeException` instead. Both are exceptions at the
same call; the `LogicException` names the actual problem, so the trade is an
improvement. Reversible by deleting the two arms if upstream ever gives its
own reason for the split.

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
deferred, so the overload widens when it lands.
**No `renderModal()`/`toModalHtmlable()`** — the confirmation
modal ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig))
is rendered host-side, driven by `CanOpenModal`'s getters, not by an `Action`
method. The serialization pair Filament keeps beside the view switch —
`toArray()`/`fromArray()`, whose `view` key becomes `template` here — **has**
landed, with its own Surface row and the key-list and whitelist notes under
[Semantics](#semantics).

### The serialized enum values flatten to scalars, and a `ScalableIcon` crosses unflattened

Filament's `toArray()` writes whatever its getters return, objects included:
`getIconPosition(): IconPosition` is non-nullable, so `iconPosition` is *always*
an enum instance in the payload, and `iconSize` / `size` are `IconSize` / `Size`
instances whenever the setter was handed one.
[`Action::toArray()`](../../../packages/actions/src/Action.php) writes `->value`
for all three — `iconPosition` unconditionally (no `instanceof` check, because
there is nothing else it can be), `iconSize` and `size` only when the value is
an enum, since those two getters return a union and a plain string passes
straight through. Those three keys are scalar, which is what lets them cross a
session or a JSON hop without depending on how PHP serializes enums; the
payload as a whole is not, since `color` carries a shade map, `eventData` and
`extraAttributes` carry arrays, `label` and `tooltip` carry `Markup`, and
`icon` carries a `ScalableIcon` unflattened.

Nothing is lost on the way back. `iconPosition` re-resolves exactly:
`fromArray()` hands `'before'` to `iconPosition()` and the getter runs it
through `IconPosition::tryFrom()`, returning the same case. `iconSize` and
`size` restore as the **string** rather than the enum, and every consumer takes
it — [`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php)
calls `IconSize::tryFrom()` on a string size, and the support primitives' `size`
props are strings, which is what the trigger templates already pass literally as
`component.getSize ?? 'md'`. So the divergence is one of wire format, not of
what renders.

`icon` diverges the other way, and for exactly the reason
[notifications' does](../notifications/notification.md#a-scalableicon-crosses-the-session-unflattened-and-resolves-at-render-size).
Filament resolves a scalable icon *at serialization time* — `$icon instanceof
ScalableIcon` takes `getIconForSize($this->getIconSize() ?? IconSize::Medium)`,
a size chosen before anyone knows how the action will render — and only a plain
`BackedEnum` falls through to `->value`. The port inverts those two arms: a
`BackedEnum` implementing
[`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) is
kept **as the enum** (the session PHP-serializes enums, and `fromArray()` admits
a `BackedEnum` back through `icon()`), and only a plain enum flattens to its
value. Resolution then happens where every other icon resolves — in
`generateIconHtml()`'s `ScalableIcon` → `BackedEnum` cascade, at the size the
trigger template actually passes — so a scalable icon is size-correct on the
serialized path where Filament's is pinned to whatever `iconSize()` said at
`toArray()` time. Pinned by the `ScalableFixtureIcon` case in
[`ActionSerializationTest`](../../../packages/actions/tests/Unit/ActionSerializationTest.php).

### The rest of Filament's `Action` surface is unported

Filament's `Action` composes roughly 50 traits/interfaces; the 21 the
[package README](README.md#actions--port-progress) lists are folded into
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
- **Forms/schemas/infolists integration**: `HasInfolist`
  (`HasSchema`, `HasWizard`, `HasData`, `CanSubmitForm` and
  `BelongsToSchemaComponent` are
  all composed now — the first two through [`has-schema.md`](has-schema.md), the
  other three through their [package README](README.md#features) rows — and the
  `schema*` closure-dependency names landed with the schemas-actions
  integration, see the Details section above).
- **Tables/bulk-action integration**: `HasTableIcon`,
  `InteractsWithSelectedRecords`, `CanFetchSelectedRecords`,
  `CanDeselectRecordsAfterCompletion`, `bulk()`/`isBulk()`
  (`BelongsToTable` is ported — see [belongs-to-table.md](belongs-to-table.md)).
- **Grouping & nesting**: `CanBeSorted`
  (`BelongsToGroup` is ported — see [action-group.md](action-group.md) — and
  `CanCallParentAction`, `HasParentActions`, `parentAction()`/
  `getParentAction()`, `nestingIndex()`/`getNestingIndex()` and `getClone()`
  all have rows in the Surface table above).
- **Authorization & policy**: `CanBeAuthorized`, `hasAuthorization()`.
- **Rate limiting**: `CanBeRateLimited`
  (`CanUseDatabaseTransactions` is ported — Surface row above).
- **Dispatch/mark conveniences**: `livewireTarget()`,
  custom `alpineClickHandler()`/`actionJs()` — the port's click wiring is the
  single `live_action('mountAction', …)` call baked into the template
  (`CanDispatchEvent` and `CanBeMarkedAsRead` are both ported, each with a
  Surface row above; only the mark flags' *click behaviour* is still missing).
- **Rendering variants**: `HasBadge`, `HasBadgeTooltip` and
  `CanBeLabeledFrom` (the `toArray()`/`fromArray()` pair Filament files beside
  them has landed — Surface row above).

None of these is a per-row **blocked**/**todo** entry because none has any
ported surface yet to anchor a row to — they will earn Surface rows (and
likely their own detail files, per the
[one-row-per-file rule](../README.md#one-row-per-file-one-file-per-thing))
the moment work starts on each.

### The live-component chain narrows inline instead of through a second getter

Filament's `getLivewire()` is a multi-step resolver — explicit `$livewire`,
then `getSchemaContainer()?->getLivewire()`,
`getSchemaComponent()?->getLivewire()`, `getTable()?->getLivewire()`,
`getGroup()?->getLivewire()` — plus a second method,
`getHasActionsLivewire()`, narrowing the generic result to
`Contracts\HasActions`. Sylament's
[`getLiveComponent()`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php)
walks all four hops in Filament's order, each narrowed inline by a private
`asActionsHost()` guard rather than by a second method, so a host implementing
only schemas' `InteractsWithSchemas` never satisfies the chain. The two schema
hops read through the schemas side's Sylament-only `getLiveComponentOrNull()`
peeks (schemas' primary getters throw, see
[`schemas/belongs-to-live-component.md`](../schemas/belongs-to-live-component.md));
the table hop reads [`Table::getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php),
which is non-nullable there because `Table::make()` always binds one, and gets
narrowed by the same guard — a table host that interacts with tables but not
with actions resolves nothing.

One delta stays deliberate: the getter keeps its non-nullable
`InteractsWithActions` return and throws when nothing resolves — the
fail-fast stand-in for Filament's nullable return + separate
`getHasActionsLivewire()` narrowing step — while the chain itself lives in
the Sylament-only `getLiveComponentOrNull()` peek (mirroring the schemas-side
precedent by name), which `prepareModalAction()`/`prepareModalActionGroup()`
use to bind footer actions without forcing an unbound owner to throw.

The group hop is kept even though
[`ActionGroup::liveComponent()`](../../../packages/actions/src/ActionGroup.php)
eagerly propagates the host to child actions at set time: the eager push only
fires at `liveComponent()`-call time, so an action added to an
already-bound group afterwards resolves through this lazy hop and nothing
else. Covered by
[`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php):
explicit-set precedence, both schema hops, the late-joining group hop, the
schemas-only-host refusal, and the unbound throw; the table hop by
[`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php),
which lives in the tables suite because only there can a real `Table` be built.

### `getContext()` drops the record model gate

Filament's `getContext()` assembles up to four entries: `recordKey` (via
`resolveRecordKey()`, guarded by table-model type checks), an early return
carrying only `recordKey` for child modal actions (`getParentAction()`),
`table`/`bulk` flags, and `schemaComponent`. The port's
[`getContext()`](../../../packages/actions/src/Action.php) emits three of
them in the same order — `recordKey`, then (past the child-frame return)
`table`, then `schemaComponent`, the key of the bound schema container falling
back to the bound schema component.

One entry diverges. The **model gate** around `recordKey` is dropped:
Filament only emits a key when the record has no table, is not an Eloquent
`Model`, the table declares no model, or the record's class matches it — a
guard against a table whose rows are a different model than the action's own
record. Sylament's tables carry no model class at all (records are plain
objects or arrays), so there is nothing to compare and the key is emitted
whenever [`resolveRecordKey()`](#record-key-resolution-is-hoisted-to-hasactions)
yields one. Restore the gate if `Table` ever grows a declared record class.
The record it resolves the key from is read with
[`withDefault: false`](#the-record-chain-reaches-the-schema-the-component-and-the-host),
so a record the *host* supplied as a default never becomes a `recordKey` on the
wire — the host answers it again on the way back, and sending it would only hand
the host its own state.
The **`bulk` flag** is ported verbatim, in Filament's own position and with
Filament's own double `hasTable()` test — a bulk action with no table emits
neither key, which is what routes it to the record-action registry rather than
the bulk one ([bulk-action.md](bulk-action.md)).

The **child-frame short-circuit is ported** verbatim:
`if ($this->parentAction instanceof self) { return $context; }` runs after the
`recordKey` branch and before the table and schema branches, so a modal action
declared by another action carries its record identity up and nothing else —
not the schema binding it inherited from `prepareModalAction()`, not the table
binding it inherited from its parent. Without it, a nested frame would carry a
`schemaComponent` key up to the host and — but for the host's own
[deeper-frame gate](has-actions.md#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache) —
try to resolve against the host's schema instead of its parent's declared modal
actions. Covered by `ActionTest`'s context cases (empty without a binding,
container key, component-key fallback, keyless container, an unresolvable
record key, the emitted `recordKey`, a bound-schema action whose
`parentAction` suppresses the key the binding alone would emit, and a bulk
action with no table emitting no `bulk` key) and by
[`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php)
for the arms that need a real `Table` — the `table` and `bulk` flags and their
suppression on a nested frame.

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
passes `:outlined="component.isOutlined"` to support's `Button`, which emits the
`sy-outlined` class itself and — since the colour registry — also uses the flag
to pick
[`OutlinedButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php)
over `ButtonComponent`, so an outlined action gets a genuinely different colour
map rather than only a different rule
([why](../support/button.md#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class)).
[`button.css`](../../../packages/support/assets/src/styles/components/button.css)
carries one `.sy-btn.sy-outlined` variant rule driven by the same `--sy-color-*`
runtime tokens and slot variables the filled button uses — per the repo styling
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
[dispatch payload](#a-dispatch-action-renders-payload-attributes-not-a-click-binding),
then a `callParent()` handler (with the action's own `arguments` attached),
then `shouldClose()`, then the `mountAction` round-trip — and the whole chain is
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
and lean on the rendered `disabled` attribute. That template also expresses the
whole precedence as guard expressions rather than the four siblings'
`{% if %}` chain, so its three attribute sets (mount, close, dispatch) each
restate the arms above them — they read one hoisted `clickWiringSuppressed`
flag, and the close and mount sets both read the hoisted `hasEventBinding`
flag, so no pair can drift apart.

### The deprecated `emit*` aliases are not ported

Filament's `CanDispatchEvent` carries three `@deprecated` aliases —
`emit()`, `emitSelf()` and `emitTo()` — each a one-line forward to its
`dispatch*` twin, kept for callers written against the older `emit` vocabulary.
The port carries the three `dispatch*` methods and nothing else: Sylament has no
deprecation-shim policy, and a fresh codebase has no older vocabulary to keep
compatible with — the same call already made for
[`disableLabel()`](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported),
`CanNotify`'s `*NotificationMessage()` pair, and `HasData`'s `*FormData*` pair.
Nothing is lost, since every alias's target is ported. Stays out regardless.

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
`support/templates/components/Section/index.html.twig`'s
`heroicons:chevron-up-20-solid`),
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

### The notification arm is a guarded soft integration, not a `require`

Filament's `filament/actions` **requires** `filament/notifications`, so its
`CanNotify` imports `Notification` and calls it unconditionally. The port cannot
copy that edge: `sylament/notifications` already requires `sylament/actions` (a
notification carries `Action`s in its body), so a `require` back would be a
composer cycle. So the send arm takes the shape the repo reserves for exactly
this case — the one
[`HasActions`' table branch](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference)
already uses, and the one support's `isAssetMapperAvailable()` established:

- **the guard** — `class_exists(Notification::class)` in front of every use.
  `Notification::class` on an imported name is a compile-time string and
  autoloads nothing, so in an install without the package the symbol is never
  resolved and both send methods stay the chainable no-ops they were. The object
  arms M4 · 2 added *type* two properties and two setter parameters
  `Notification|Closure|null`, which is inert for the same reason: PHP resolves a
  property or parameter type lazily, only when a value meets it — and a caller
  with a `Notification` to pass demonstrably has the package.
- **the declaration** — `sylament/notifications` is a composer `suggest` on
  `sylament/actions`, the strongest declaration an optional integration gets.
- **the pin** — a soft reference fails *silently* when it rots (a renamed or
  moved `Notification` leaves `class_exists()` false and every toast quietly
  disappears), so it is pinned by a test in the suite that has both packages:
  [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php)
  in the **notifications** Integration suite, whose kernel boots both bundles.
  Tables' [`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php)
  covers the same seam end to end.

The guard's known limit, stated because it is loud rather than silent: it tests
for the *package*, and sending also needs the *bundle* booted, since
[`Notification::send()`](../notifications/notifications.md) throws unless the
instance came from the container. An application that installs
`sylament/notifications` but never registers `SylamentNotificationsBundle`
therefore gets a `LogicException` naming that exact fix on the first successful
action, instead of a missing toast. That is the intended trade — a fallback that
swallowed it would leave a user with a silently notification-less panel and no
signal at all — and no guard can detect an unregistered bundle from inside a
trait.

The second limit is the same shape one layer down: with the package installed
*and* the bundle registered, `send()` still writes to the flash bag, and
`RequestStack::getSession()` throws `SessionNotFoundException` on a request that
carries none. So a successful action on a session-less request — a console
command, a stateless API route, an application that never enabled
`framework.session` — fails there rather than silently dropping the toast. Same
trade, same reason: a panel application needs a session anyway, and the throw
names the missing piece.

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
[CanOpenModal detail row](#canopenmodals-unported-remainder),
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

The opposite direction — record → key — is *not* hoisted:
[`resolveRecordKey()`](../../../packages/actions/src/Concerns/InteractsWithRecord.php)
lives on the trait exactly as Filament has it, and is what
[`getContext()`](#getcontext-drops-the-record-model-gate)
emits. Its object arm is the one that had to be translated, since there is no
`Model::getKey()` to call: it reads `id` through schemas'
[`PropertyAccessor::getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php)
— hence `Action` and `ActionGroup` now compose `CanAccessProperties` — and
treats an `AccessException` (no readable `id`) as "no identity", returning
null. That makes it **lenient where the host's twin is strict**: the same
unreadable record throws from
[`HasTable::getTableRecordKey()`](../tables/has-table.md), because a table
building its row index cannot proceed without a key, while an action emitting
a mount context can simply omit one. The array arm keeps Filament's
`LogicException` for a *missing*
[`ArrayRecord`](../../../packages/support/src/ArrayRecord.php) key entry — an
array is unambiguously a record, so the absent entry is a caller mistake rather
than an absent identity — but an entry that is present and blank (or not
stringable) returns null where Filament's `strval()` would have emitted `''` or
fatalled. Covered by
[`InteractsWithRecordTest`](../../../packages/actions/tests/Unit/Concerns/InteractsWithRecordTest.php).

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
([detail](#canopenmodals-unported-remainder)). One limitation moved rather than vanished: an
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
