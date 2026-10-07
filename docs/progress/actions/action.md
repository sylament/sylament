---
description: The base class every actions-package action extends. (draft)
ports:
  - vendor/filament/actions/src/Action.php
  - vendor/filament/actions/src/Concerns/
owns:
  - packages/actions/src/Action.php
  - packages/actions/src/Concerns/BelongsToLiveComponent.php
  - packages/actions/src/Concerns/CanBeDisabled.php
  - packages/actions/src/Concerns/CanBeHidden.php
  - packages/actions/src/Concerns/CanBeMarkedAsRead.php
  - packages/actions/src/Concerns/CanCallParentAction.php
  - packages/actions/src/Concerns/CanCustomizeProcess.php
  - packages/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php
  - packages/actions/src/Concerns/CanFetchSelectedRecords.php
  - packages/actions/src/Concerns/CanMutateRecordData.php
  - packages/actions/src/Concerns/CanRedirect.php
  - packages/actions/src/Concerns/HasArguments.php
  - packages/actions/src/Concerns/HasLifecycleHooks.php
  - packages/actions/src/Concerns/HasMountableArguments.php
  - packages/actions/src/Concerns/HasName.php
  - packages/actions/src/Concerns/HasParentActions.php
  - packages/actions/src/Concerns/InteractsWithSelectedRecords.php
  - packages/actions/templates/partials/Action/
  - packages/support/src/Components/Concerns/HasIconPosition.php
---
# Action (actions)

`Sylament\Actions\Action` — the base class every actions-package action extends.
It composes `Sylament\Support\Components\ViewComponent` (Twig-backed rendering) and folds in 25 actions-package concerns — `BelongsToLiveComponent`, `CanBeDisabled`, `CanBeHidden`, `CanBeMarkedAsRead`, `CanBeOutlined`, `CanClose`, `CanDeselectRecordsAfterCompletion`, `CanDispatchEvent`, `CanFetchSelectedRecords`, `CanNotify`, `CanOpenModal`, `CanOpenUrl`, `CanRedirect`, `CanRequireConfirmation`, `CanUseDatabaseTransactions`, `HasAction`, `HasArguments`, `HasGroupedIcon`, `HasLabel`, `HasLifecycleHooks`, `HasMountableArguments`, `HasName`, `HasSize`, `InteractsWithRecord`, `InteractsWithSelectedRecords` — each a Surface row below with its own detail/divergence spliced in from its own audit.
It also composes three multi-consumer actions-package concerns tracked outside this file — `BelongsToGroup` (shared with `ActionGroup`, written up in [`action-group.md`](action-group.md)), `BelongsToTable` (shared with `ActionGroup`, written up in [`belongs-to-table.md`](belongs-to-table.md)) and `BelongsToSchemaComponent` (shared with `ActionGroup`, a `done` row with no detail file in the [package README](README.md#features), since its whole surface is a two-method back-reference pair) and the two modal attribute-bag concerns `HasExtraModalOverlayAttributes` / `HasExtraModalWindowAttributes` (`done` rows in the same README, each a two-method pair as well) — plus 6 support-level traits (`HasColor`, `HasExtraAttributes`, `HasIcon`, `HasIconPosition`, `HasIconSize`, `HasTooltip`) that stay tracked in the [`support`](../support/README.md) package and appear here only as reference rows.
The port is a deliberate MVP subset of Filament's `Action` — a single-purpose "confirm and run a closure" action wired to a live component, not the mounting/modal/form/grouping/wizard platform Filament's class has grown into.

## Surface

Every point of this component and its folded concerns.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction & `make()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | — |
| `call()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | The `try`/`finally` ships whole bar its second branch, which has no cache to clear — [bulk-action.md](bulk-action.md#the-post-call-deselect-reaches-its-host-through-the-nullable-accessor) |
| `cancel(bool $shouldRollBackDatabaseTransaction = false)` / `halt(bool $shouldRollBackDatabaseTransaction = false)` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [semantics](#the-rollback-flags-setter-and-getter-disagree-on-casing) |
| `success()` / `failure()` / `getStatus()` + `$status` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | Filament-exact, tally included: an explicit `success()`/`failure()` wins, an action that accesses no selected records is `Success`, and a bulk one compares its two counters — [bulk-action.md](bulk-action.md) |
| `bulk()` / `isBulk()` + `$isBulk` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | The routing half of the bulk pair — [bulk-action.md](bulk-action.md) |
| `resolveDefaultClosureDependencyForEvaluationByName()` + `$evaluationIdentifier` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | mounted-actions introspection | [detail](#resolvedefaultclosuredependencyforevaluationbyname-covers-14-of-16-names) · [diverges](#the-selected-record-injections-are-gated-on-the-access-flag) |
| `resolveDefaultClosureDependencyForEvaluationByType()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | — | [detail](#resolvedefaultclosuredependencyforevaluationbytype-carries-the-table-arm-and-the-added-getset-pair) · [diverges](#the-selected-record-injections-are-gated-on-the-access-flag) |
| [The `table` injection — `'table'` by name, `Table::class` by type](../../../packages/actions/src/Action.php) | [`Action::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/actions/src/Action.php) (the name arm; the type arm is Sylament-only) | done | — | [detail](#resolvedefaultclosuredependencyforevaluationbytype-carries-the-table-arm-and-the-added-getset-pair) · [semantics](interacts-with-record.md#a-table-bound-action-titles-and-labels-through-its-table) |
| `getContext()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#getcontext-drops-the-record-model-gate) |
| `getClone()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`getClone()`) | done | — | Filament-exact one-liner (`clone $this`). A shallow clone is the whole contract on this side: what a caller needs is an action whose `record()` / `table()` / `group()` bindings can be re-stamped per row without touching the declared original. `ActionGroup`'s override adds the member deep-clone ([there](action-group.md#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately)) |
| `toArray()` / `fromArray()` — the 21 ported keys | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`toArray()`/`fromArray()`) | done | — | [diverges](#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened) · [semantics](#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl) · [semantics](#fromarray-restores-only-the-five-built-in-templates) · [semantics](#fromarray-degrades-a-to-dispatch-with-no-target-component-to-a-plain-dispatch) |
| `nestingIndex()` / `getNestingIndex()` + `parentAction()` / `getParentAction()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`$nestingIndex`, `$parentAction` and their accessors) | done | — | Both stamped by the host: `nestingIndex()` per frame in [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php), `parentAction()` by [`prepareModalAction()`](can-open-modal.md#canopenmodals-unported-remainder) on every action an action declares. `getParentAction()` is what makes the [`getContext()` child-frame short-circuit](#getcontext-drops-the-record-model-gate) decidable |
| [`CanCallParentAction`](../../../packages/actions/src/Concerns/CanCallParentAction.php) | [`Concerns/CanCallParentAction.php`](../../../vendor/filament/actions/src/Concerns/CanCallParentAction.php) | done | — | Filament-exact two-method port (`callParent()` + getter). The getter is `getParentActionCallLiveComponentClickHandler()` against Filament's `…Livewire…`, the systematic Livewire → UX LiveComponent naming translation from the [rename table](../renames.md), not a rename of its own. Read by all five trigger templates, ranked **above** `shouldClose()` in the click precedence |
| [`HasParentActions`](../../../packages/actions/src/Concerns/HasParentActions.php) | [`Concerns/HasParentActions.php`](../../../vendor/filament/actions/src/Concerns/HasParentActions.php) | done | — | Complete, method-for-method and body-for-body. Three of its accessors (`shouldCancelParentActionsOnClose()`, `shouldCancelAllParentActionsOnClose()`, `getParentActionToCancelToOnClose()`) have no consumer here — and none in `vendor/filament` either, so they are kept as declared API rather than trimmed |
| Trigger rendering ([`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) + the [`Link`](../../../packages/actions/templates/partials/Action/Link.html.twig) / [`Badge`](../../../packages/actions/templates/partials/Action/Badge.html.twig) / [`Grouped`](../../../packages/actions/templates/partials/Action/Grouped.html.twig) / [`IconButton`](../../../packages/actions/templates/partials/Action/IconButton.html.twig) siblings) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`toButtonHtml`/`toLinkHtml`/`toBadgeHtml`/`toGroupedHtml`/`toIconButtonHtml`) | in progress | — | [detail](#tooltip-icon-position-and-extra-attribute-rendering-are-untested), [semantics](#an-uncolored-badge-or-link-action-renders-primary-not-gray), [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
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
| [`CanDeselectRecordsAfterCompletion`](../../../packages/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | [`Concerns/CanDeselectRecordsAfterCompletion.php`](../../../vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | done | — | [bulk-action.md](bulk-action.md) |
| [`CanFetchSelectedRecords`](../../../packages/actions/src/Concerns/CanFetchSelectedRecords.php) | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | in progress | — | [bulk-action.md](bulk-action.md) |
| [`CanRedirect`](../../../packages/actions/src/Concerns/CanRedirect.php) | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | done | — | Filament's four members verbatim (`successRedirectUrl()` / `failureRedirectUrl()` setters, `dispatchSuccessRedirect()` / `dispatchFailureRedirect()` resolving the action's own URL first and the host hooks [`getDefaultActionSuccessRedirectUrl()`/`getDefaultActionFailureRedirectUrl()`](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred) second), plus a fifth reader, `getPendingRedirectUrl()`, that is [Sylament-only by mechanism](has-actions.md#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag). `redirect()` loses Filament's `?bool $navigate` parameter with the SPA-mode facade behind it. The host read is the **nullable** accessor: a detached action dispatches nothing rather than throwing, and no `instanceof` re-check is written because `getLiveComponentOrNull()` already returns the contract that carries the two hooks — one would be provably always true and fail level max. Covered by [`CanRedirectTest`](../../../packages/actions/tests/Unit/Concerns/CanRedirectTest.php) and, end to end, by [`HasActionsRedirectTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsRedirectTest.php) |
| [`HasArguments`](../../../packages/actions/src/Concerns/HasArguments.php) | [`Concerns/HasArguments.php`](../../../vendor/filament/actions/src/Concerns/HasArguments.php) | done | — | — |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | [diverges](#hasgroupedicon-narrows-its-icon-type-to-string) |
| [`label()` / `getLabel()`](../../../packages/actions/src/Concerns/HasLabel.php) | [`label()` / `getLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | The name-derived default takes the segment **before the first dot** (`u($name)->before('.')`), which is Filament's own actions-side choice and differs from `Field`'s `afterLast('.')` — matched deliberately, not a slip to "fix" |
| `hiddenLabel()` / `isLabelHidden()` | [`hiddenLabel()` / `isLabelHidden()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | A hidden label renders as an `aria-label` on the trigger root rather than as visible text — the [button](../../../packages/actions/templates/partials/Action/Button.html.twig) / [link](../../../packages/actions/templates/partials/Action/Link.html.twig) / [badge](../../../packages/actions/templates/partials/Action/Badge.html.twig) triggers read `isLabelHidden` for it, matching what Filament's `labelSrOnly` button prop does |
| `translateLabel()` / `disableLabel()` (deprecated) | [`translateLabel()` / `disableLabel()`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported) |
| [`HasLifecycleHooks`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) | [`Concerns/HasLifecycleHooks.php`](../../../vendor/filament/actions/src/Concerns/HasLifecycleHooks.php) | done | — | All six hooks and their runners (`before`/`after`, `beforeFormFilled`/`afterFormFilled`, `beforeFormValidated`/`afterFormValidated`), each evaluating its stored closure through `EvaluatesClosures` and a safe `null` no-op when unset — [`HasLifecycleHooksTest`](../../../packages/actions/tests/Unit/Concerns/HasLifecycleHooksTest.php) covers every pair. Where the `*FormValidated` pair fires is the host's business: [`callMountedAction()`](has-actions.md#callmountedaction-drops-rate-limiting-and-authorization) runs `callBeforeFormValidated()` before the mounted schema's `getState()` and `callAfterFormValidated()` immediately after it returns, so a failed validation — which throws out of `getState()` — never reaches the second. `callBefore()`/`callAfter()` also carry the two lifecycle events · [diverges](#the-lifecycle-events-are-dispatched-as-objects-where-filament-dispatches-a-class-string) |
| [`HasMountableArguments`](../../../packages/actions/src/Concerns/HasMountableArguments.php) | [`Concerns/HasMountableArguments.php`](../../../vendor/filament/actions/src/Concerns/HasMountableArguments.php) | done | — | [diverges](#action-invocation-is-a-php-side-seam-only) |
| [`HasName`](../../../packages/actions/src/Concerns/HasName.php) | [`Concerns/HasName.php`](../../../vendor/filament/actions/src/Concerns/HasName.php) | done | — | [diverges](#getname-tightens-its-return-type-to-non-nullable-string) |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | [diverges](#hassize-drops-the-optimized-render-fast-path-helper) |
| [`InteractsWithSelectedRecords`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | in progress | `CanBeAuthorized` | [bulk-action.md](bulk-action.md) |

## Details

### `resolveDefaultClosureDependencyForEvaluationByName` covers 14 of 16 names

Filament's override resolves 16 dependency-name groups: `arguments`, `data`, `livewire`, `model`, `mountedActions`, `record`, `selectedRecords`/`records`, `selectedRecordsQuery`/`recordsQuery`, `schema`, `schemaComponent`/`component`, `schemaOperation`/`context`/`operation`, `schemaGet`/`get`, `schemaSet`/`set`, `schemaComponentState`/`state`, `schemaState`, `table`.
The port's [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/Action.php) resolves 14 of them: `record`, `recordClass` (Filament's `model`, under the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) — it resolves [`getRecordClass()`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host), so a closure can name the class an action will create into before any record exists), `arguments`, `liveComponent` (the systematic `livewire` → `liveComponent` rename tracked in [`../renames.md`](../renames.md) — not a divergence), `data` (`'data' => ClosureParameter::make($this->getData())`, backed by [`HasData`](../../../packages/actions/src/Concerns/HasData.php), so a mounted action's callback reads its submitted form state as `$data`), `table` (the [bound table](belongs-to-table.md), added with the label and title hops — `Table::class` resolves the same value by type, which is [Sylament-only](#resolvedefaultclosuredependencyforevaluationbytype-carries-the-table-arm-and-the-added-getset-pair)), and — since the schemas-actions integration — `schema`, `schemaComponent`/`component`, `schemaGet`/`get`, `schemaSet`/`set`, `schemaComponentState`/`state`, and `schemaState`, backed by `BelongsToSchemaComponent` plus two state helpers ported onto `Action` itself: `getSchemaComponentState()` (the upward walk to the nearest state-path-bearing parent component, vendor L602–621) and `getSchemaState()` (the first state-path-bearing container; Filament's `getStateSnapshot()` call maps to Sylament's `Schema::getState()`, which [schemas' HasState](../../../packages/schemas/src/Concerns/HasState.php) documents as its `getStateSnapshot` equivalent).
`getSchemaState()` carries Filament's leading `HasExtraItemActions` branch: when the bound schema component implements schemas' [`HasExtraItemActions`](../../../packages/schemas/src/Components/Contracts/HasExtraItemActions.php) and the action's arguments carry a non-empty `item`, `$schemaState` is *that item's* state rather than the container walk's — how a repeater's extra item actions see the row they were mounted on.
The key is stringified before the call, so a numeric item key from the wire reaches `getItemState(string $key)` intact.
The guard is a bare `!== ''` rather than [`filled()`](../support/helpers.md): the key is already narrowed to `int|string` at that point, and `filled()`'s only static assertion is a null check, so calling it there is an `alreadyNarrowedType` error at level max.
`ActionGroup::getSchemaState()` gains no such branch, matching Filament: a group composes no `HasArguments`, so it has no `item` to read.
The `get`/`set` arms keep Filament's **non-null-safe** reach for the schema component, routed through [`getSchemaComponentOrFail()`](belongs-to-schema-component.md#getschemacomponentorfail-throws-where-filament-fatals), whose `?? throw` is what satisfies level max — the unguarded deref was the only thing in this codebase that ever needed an inline ignore, and the throw is what replaced it: a schema-less action evaluating a `$get`/`$set` closure still stops the request, exactly where Filament's does, on a `LogicException` naming the class rather than a fatal on null, pinned by `ActionTest`.
So does an action declared *inside* a mounted-action schema, for the same reason and equally in Filament — that schema is a **root** schema with no parent component, so all three arms of `getSchemaComponent()` return `null`; the browser pass that found it is written up in [has-actions.md](has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema).
Both arms also chain `->skipComponentsChildContainersWhileSearching(false)` onto the returned [`Get`](../schemas/get.md) / [`Set`](../schemas/set.md) utility, Filament-exact: without it, the utility's default self-skip blocks it from descending into the bound schema component's *own* child schema, so a container-hosted action's `$get`/`$set` (e.g. a `Section` with a registered action reading a field nested inside that same `Section`) would silently fall back to a raw, uncast `PropertyAccessor` read/write instead of going through the owning field's `getState()`/`state()` — the call is not redundant, it disables a self-skip that would otherwise misfire here.
`ActionGroup` mirrors Filament's own group subset (`schema`, `schemaComponent`/`component`, `schemaGet`/`get`, `schemaComponentState`/`state`, `schemaState` and the two duplicated state helpers — vendor's `ActionGroup` carries no `set` arm, and its `get` arm does not chain the skip-toggle call either, unlike `Action`'s; both asymmetries are vendor-verified and kept).
Two more landed with the bulk family: `selectedRecords`/`records` and `selectedRecordsQuery`/`recordsQuery`, resolving [`getSelectedRecords()` / `getSelectedRecordsQuery()`](bulk-action.md#the-two-record-readers-share-one-gate-and-one-message) behind the [access-flag gate](#the-selected-record-injections-are-gated-on-the-access-flag) Filament has no equivalent of, and resolving the plain selection where Filament resolves the [individually authorized](bulk-action.md#per-record-authorization-waits-until-authorization-lands) one.
Still missing: `mountedActions` (mounted-actions introspection beyond today's `HasActions`), `schemaOperation`/`context`/`operation` (the value exists — schemas' `Schema` has [`getOperation()`](../schemas/has-operation.md) — and so does the schema-container hop it would read it off, now that [`getRecord()` walks there](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host); nothing blocks the arm any more, it simply has no caller yet), and `table` (`BelongsToTable`).
Coverage: `record`/`arguments` via `ActionTest`'s "calls the action closure…", `data` via `ActionTest`'s "injects $data from getData()", `liveComponent` via [`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php), the schema names via `ActionTest`'s injection cases (plus `ActionGroupTest` for the group subset), `recordClass` via `ActionTest`'s "injects the record class into a closure as recordClass", and the two selected-record names via `ActionTest`'s gated-off cases plus the tables suite's [`DeleteBulkActionTest`](../../../packages/tables/tests/Integration/DeleteBulkActionTest.php), where a real host answers them.

### `resolveDefaultClosureDependencyForEvaluationByType` carries the `Table` arm and the added Get/Set pair

Filament's `Action` overrides `resolveDefaultClosureDependencyForEvaluationByType()` to resolve `Builder::class` to the selected-records query, `EloquentCollection::class`/`Collection::class` to the individually-authorized selected records, and `Model::class` (or the record's own class) to the current record — so `fn (Model $record)` or `fn (Builder $query)` resolves without naming the parameter.
**One of those three arms is ported**: `Builder::class` maps to `Doctrine\ORM\QueryBuilder::class` and resolves [`getSelectedRecordsQuery()`](bulk-action.md#the-two-record-readers-share-one-gate-and-one-message), behind the same [access-flag gate](#the-selected-record-injections-are-gated-on-the-access-flag) the by-name arms carry.
The other two stay out: there is no collection arm to port — a Doctrine read returns a plain `list`, so the `records` **name** carries it — and the record arm's blocker is answered rather than gone: [`getRecord()`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host) now walks to the schema container and component, so an arm landed today would resolve rather than answer `null` for every action bound to a record-carrying schema.
It stays out only because nothing has asked for it — and because the Doctrine port has no `Model::class` base to key the arm on, so it would have to resolve on the record's *own* class the way [`getCustomRecordTitle()`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host)'s typed injection already does.
A type-hinted record parameter therefore still falls through to `Component::resolveDefaultClosureDependencyForEvaluationByType()` (support) and is not resolved.

What the override carries beyond that is `Table::class`, resolving [`getTable()`](belongs-to-table.md) exactly as the `table` **name** arm does.
That name arm is Filament's (`Action.php:582`); the **type** arm is Sylament-only, and it exists because this port's `Invoker` falls back to the DI container for an unresolved class-typed parameter where Laravel's does not: a `fn (Table $table)` closure would otherwise be handed a **fresh, unconfigured** `Table` service instead of the action's own.
Explicit injections resolve before the container, so with the arm a table-less action answers `null` — pinned by `ActionTest`.
It costs nothing at the seam: a `match` over a `::class` constant compares compile-time strings and never autoloads, so the [guarded soft dependency](belongs-to-table.md) on `sylament/tables` holds.

The last thing it carries is the `Get`/`Set` pair, which [diverges](#getset-resolve-by-type-as-well-as-by-name) from Filament — see the note for why.

### CanBeHidden omits authorization, group, and table-cache integration

**In:** `hidden(bool|Closure $condition = true)`, `visible(bool|Closure $condition = true)`, `isHidden()`, `isVisible()` — method-for-method identical to Filament's base resolution (`evaluate($isHidden)` true wins, else `! evaluate($isVisible)`), and covered by [`CanBeHiddenTest`](../../../packages/actions/tests/Unit/Concerns/CanBeHiddenTest.php) (default visibility, `hidden()`, `visible(false)`, and closure evaluation for both).

**Missing vs Filament:** Filament's `isHidden()` is not the two-branch resolver above — it is a caching front door (`cachedIsHidden`, `hasVisibilityCache`, `prepareVisibilityCache()` / `clearVisibilityCache()`, keyed by `getCurrentVisibilityRecordKey()` off `getRecord()`/`spl_object_id`) around `resolveIsHiddenInGroup()`, which itself: (1) short-circuits true when the action's `ActionGroup` reports `baseIsHidden()`; (2) for an `ActionGroup` instance, iterates `getActions()` and is hidden only when every child reports `isHiddenInGroup()`; (3) otherwise falls back to `! isAuthorizedOrNotHiddenWhenUnauthorized()` — i.e. an action with no explicit `hidden()`/`visible()` override is hidden by default when the current user is unauthorized.
None of the caching, the `ActionGroup` child-iteration branch, or the authorization fallback is ported; the port's `isHidden()` is exactly Filament's `resolveIsHiddenInGroup()` with the `instanceof ActionGroup` branch and the trailing `isAuthorizedOrNotHiddenWhenUnauthorized()` call removed entirely — a deliberate confirm-only MVP cut.
The child-iteration branch does exist now, but as an override on [`ActionGroup::isHidden()`](../../../packages/actions/src/ActionGroup.php) rather than in this shared trait, since only `ActionGroup` needs it — [see there](action-group.md#no-ishiddeningroup-or-visibility-caching).
Concretely: a Sylament action with no `hidden()`/`visible()` call is always visible today, even where Filament would hide it because the user fails `CanBeAuthorized`'s policy check, or because its `ActionGroup` is empty/hidden.

**Next step:** restore the authorization fallback once `CanBeAuthorized` lands (it supplies `isAuthorizedOrNotHiddenWhenUnauthorized()`), restore the `getGroup()?->baseIsHidden()` short-circuit once `BelongsToGroup` carries more than a setter/getter pair, and restore the record-keyed visibility cache once `BelongsToTable`/`BelongsToGroup` give `hasTable()`/`getGroup()` a real implementation — `getRecord()` (via `InteractsWithRecord`) already exists and can back `getCurrentVisibilityRecordKey()` unchanged.

### CanBeMarkedAsRead carries the flags; nothing reads them yet

**In:** [`CanBeMarkedAsRead`](../../../packages/actions/src/Concerns/CanBeMarkedAsRead.php) is a method-for-method, body-for-body port of the six members Filament declares inline on its `Action` class rather than in a `Concerns/` trait — `markAsRead()` / `markAsUnread()` setters, their `shouldMarkAsRead()` / `shouldMarkAsUnread()` evaluate-and-cast getters, and the `hasMarkAsRead()` / `hasMarkAsUnread()` `!== false` declared-ness pair.
It is a trait here because that is where this port files a single-purpose member group (the same call `CanClose` made, whose shape it copies exactly); the surface is identical either way.
Both flags round-trip through [`toArray()`/`fromArray()`](#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl), which is the reason it ships now: a notification action declares `->markAsRead()` on the sending side and the flag has to survive the flash bag.

**Out:** the behaviour.
Filament reads the pair in `getAlpineClickHandler()`, which emits the literal `markAsRead()` / `markAsUnread()` Alpine expressions against the database-notifications panel's Livewire component, and in `hasNoClickHandler()`, where the two `has*()` getters join the "this action does something on click" test.
Neither exists here: there is no database-notifications panel to mark anything read *in*, and the port's click wiring is the single `live_action('mountAction', …)` call baked into the trigger templates rather than a resolved handler expression ([diverges](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)).
So `->markAsRead()` currently changes nothing a user can see.

**Next step:** wire both flags into the click cascade when the database-notifications panel lands, alongside the notification-side surface [`notifications/README.md`](../notifications/README.md) tracks.

### Tooltip, icon position, and extra-attribute rendering are untested

**In:** the template wires `component.getLabel ?? component.getName`, the `sy-size-*` / `sy-color-*` classes off `getSize()` / `getColor()` (falling back to an arbitrary custom size token, matching the support [`Button`](../../../packages/support/templates/components/Button/index.html.twig) primitive's own fallback), `isDisabled` + `aria-disabled`, the `<a>` vs `<button>` tag and `target="_blank"` off `getUrl()` / `shouldOpenUrlInNewTab()`, and the `mountAction` `live_action()` wiring for the non-URL case — all four covered by [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php).

**Missing test coverage:** three branches the template exercises have no assertion anywhere in the `actions` suite:

- `title: component.getTooltip ?: false` — no test renders an `Action` with `->tooltip(...)` and checks for the `title` attribute.
- `{%- if iconHtml and component.getIconPosition.value == 'after' %}` — the existing icon test (`toContain('<svg')`) only proves an icon renders, not that `iconPosition('after')` actually moves it past the label.
- `component.getExtraAttributes` merged as the attrs() second source — no test renders an `Action` with `->extraAttributes([...])` and checks it lands on the root element.

**Next step:** add the three cases to `ActionRenderTest`; no code change is expected, this is coverage-only.

## Semantics

### The rollback flag's setter and getter disagree on casing

[`cancel()`](../../../packages/actions/src/Action.php) and `halt()` take `bool $shouldRollBackDatabaseTransaction = false` and stamp it onto the exception they throw, so the [`callMountedAction()` catch arms](has-actions.md#callmountedaction-drops-rate-limiting-and-authorization) can commit or roll back per throw.
Both defaults are `false`: a cancelled or halted action **commits** what ran before it unless it says otherwise.
The pair on [`Cancel`](../../../packages/support/src/Exceptions/Cancel.php) and [`Halt`](../../../packages/support/src/Exceptions/Halt.php) reads wrong and is correct: the setter is `rollBackDatabaseTransaction()` with a capital B, the getter `shouldRollbackDatabaseTransaction()` with a lowercase one.
That is Filament's own inconsistency, kept verbatim — normalising either half would be an API rename of a public method with no [renames.md](../renames.md) row, for a typo migrating users already grep for.

### The serialized key list drops `alpineClickHandler` and `shouldPostToUrl`

[`toArray()`](../../../packages/actions/src/Action.php) writes 21 keys where Filament's writes 23, and the two missing ones read as an oversight only until you check what they would carry.
**`alpineClickHandler`** is Filament's `getCustomAlpineClickHandler()` — an Alpine expression, a string evaluated in the browser against the component that renders it.
The port's translation of a custom click handler is a **live action name bound to a host** ([diverges](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)), and a live action name means nothing to whatever host rehydrates the action on the far side of the hop, so there is no value worth writing.
**`shouldPostToUrl`** has no getter to call at all — POST-to-url is [not ported](can-open-url.md#post-to-url-support-is-not-ported).
Every other key is present, with vendor's `view` renamed `template` (the systematic Blade → Twig translation from [`renames.md`](../renames.md), not a rename of its own) — though present is not the same as identical: four of them carry a different **value shape** than Filament writes ([diverges](#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened)).

Nothing closure-bearing is written either, and that *is* Filament-exact rather than a port decision: `action`, the modal configuration, the lifecycle hooks and the visibility closures are absent from both key lists, because a `Closure` does not survive a session.
An action restored from an array is the **trigger** — label, colour, icon, url, dispatch, template — not the behaviour behind it.

### `fromArray()` restores only the five built-in templates

The `template` key crosses a trust boundary — a serialized action rides the session flash bag, and its restored value is handed to `$twig->render()` by [`ViewComponent::render()`](../../../packages/support/src/Components/ViewComponent.php) — so [`fromArray()`](../../../packages/actions/src/Action.php) admits it only when it is one of `Action`'s own five `*_TEMPLATE` constants (`BADGE`, `BUTTON`, `GROUPED`, `ICON_BUTTON`, `LINK`).
Anything else is **dropped, not rejected**: the setter is simply never called, `getTemplate()` falls through to `$defaultTemplate`, and the action renders as a button.
The whitelist is `static::`-qualified, so a subclass that overrides a constant whitelists its own value.

Filament gates the same key with `isViewSafe()`, a `Str::startsWith($view, 'filament::')` prefix test, which admits any view in the package namespace including ones a user registered there.
That arm is not ported: with five templates and no custom-template surface yet, a prefix test would only widen what a tampered payload can name.
It returns with the custom template surface, not before.

### `fromArray()` degrades a `to` dispatch with no target component to a plain dispatch

Filament restores the dispatch trio with `dispatchTo($data['dispatchToComponent'] ?? null, …)` — passing a possible `null` straight into a non-nullable `string $component` parameter, which Laravel tolerates and `declare(strict_types=1)` does not.
The port guards the arm instead (`$direction === 'to' && is_string($to)`), so a payload claiming direction `to` without a usable component falls through to the `default` arm and dispatches the event **plainly** rather than throwing a `TypeError` mid-render.
The event and its data still travel; only the targeting is lost.
Pinned by the tampered-payload case in [`ActionSerializationTest`](../../../packages/actions/tests/Unit/ActionSerializationTest.php).

### An uncolored badge or link action renders primary, not gray

`getColor()` is `null` for an action nobody called `->color()` on, and the `Badge`, `Button`, `IconButton` and `Link` triggers pass it as `:color="component.getColor ?? 'primary'"`.
The fallback is load-bearing: since ux-twig-component 3.5, `{% props %}` compiles to `array_key_exists('color', $context['__props'])` ([`PropsNode.php`](../../../vendor/symfony/ux-twig-component/src/Twig/PropsNode.php)), so an explicitly passed `null` **replaces** the primitive's `'primary'` default rather than falling back to it.
Until 3.5 the check was `isset()`, false for null, and the bare getter was enough; after it, the bare getter rendered every uncolored action with no colour classes at all.
An uncolored badge or link action therefore still emits `sy-color sy-color-primary` and renders through the primitive's `.sy-color` branch; `:not(.sy-color)` is unreachable from these triggers.

That is Filament's behaviour, not a divergence — `badge.blade.php` and `link.blade.php` both default `'color' => 'primary'`.
The one trigger where the gray branch *is* reachable is `Grouped`, which passes the bare getter on purpose: [`DropdownItem`](../support/dropdown-item.md) defaults to `'gray'`, matching `dropdown/list/item.blade.php`, and its colour policy emits nothing for either `gray` or `null`, so the two render the same.

## Divergences

### The lifecycle events are dispatched as objects, where Filament dispatches a class-string

[`callBefore()`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) dispatches `new ActionCalling($this)` before evaluating its closure, and `callAfter()` dispatches `new ActionCalled($this)` from a `finally`, so it fires even when the after-closure throws — position-for-position Filament's.
What crosses the wire is not.
Filament calls `Event::dispatch(ActionCalling::class, $this)`: Laravel's `parseEventAndPayload()` takes the object branch only when the *event* is an object, so a class-string event leaves the payload as `[$action]` and the event class is never constructed — a listener registered on `ActionCalling::class` receives an `Action`, and `getAction()` never runs.
Symfony's `EventDispatcherInterface::dispatch(object $event, ?string $eventName = null)` requires a real object, so the port constructs one, and its listeners receive what Filament's own class shape implies: [`ActionCalling`](../../../packages/actions/src/Events/ActionCalling.php) / [`ActionCalled`](../../../packages/actions/src/Events/ActionCalled.php), final readonly, one promoted `Action`, one `getAction()`.
Neither is stoppable or cancellable — parity, and `halt()` / `cancel()` remain the control channel.
The dispatcher is a nullable `Symfony\Contracts\EventDispatcher\EventDispatcherInterface` on a `#[Required]` setter, the [`EvaluatesClosures` pattern](../support/component.md): an action built with `new` rather than through the container dispatches nothing — and the nullsafe call skips constructing the event at all, so nothing is built to be thrown away.
Covered by [`HasActionsEventsTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsEventsTest.php) (both events around one call, in order, carrying the same instance; the `finally` arm through a throwing after-closure).

### The selected-record injections are gated on the access flag

Filament's four selected-record arms — `selectedRecords`/`records`, `selectedRecordsQuery`/`recordsQuery` by name and `Builder::class` by type — call the readers unconditionally, so *any* action whose closure happens to name a `$records` parameter throws the `accessSelectedRecords()` `LogicException` instead of resolving.
That is tolerable in Filament, where `$records` is a bulk-action idiom; it is not here, because the same name reads naturally on a plain action that means *its own* collection of something.
So all four arms are wrapped in `hasCanAccessSelectedRecords()` — the **presence** test, not the evaluated flag, so a closure-valued `accessSelectedRecords(fn () => …)` still counts — and fall through to `parent::resolveDefaultClosureDependencyForEvaluationBy*()` when it is false.
The parameter then resolves the ordinary way: an injection, the container, its own default, or `null`.

The gate is deliberately *not* `canAccessSelectedRecords()`: evaluating the flag to decide whether to resolve would run a user closure on every parameter of every action, and a flag that evaluates false would silently hand the parameter a container service rather than raising the gate the caller asked for.
Pinned by `ActionTest`'s three fall-through cases.

### `$get`/`$set` resolve by type as well as by name

Filament binds the state utilities to closure **parameter names** only: `'schemaGet', 'get' => [...makeGetUtility()...]` and `'schemaSet', 'set' => [...makeSetUtility()...]` in [`Action`](../../../vendor/filament/actions/src/Action.php)'s by-name resolver, with nothing for `Get::class`/`Set::class` in its by-type one.
Its [schema `Component`](../../../vendor/filament/schemas/src/Components/Component.php), by contrast, resolves both by type.
So upstream `fn (Get $anything)` works inside a field closure and fails inside an action closure, purely because of the parameter's name.

The port keeps both by-name arms verbatim and **adds** `Get::class`/`Set::class` to the by-type resolver on `Action` (and `Get::class` on [`ActionGroup`](action-group.md), matching that class's narrower by-name surface — upstream gives the group `get` and no `set`, and the port does not invent one).
A closure parameter typed `Get`/`Set` now resolves under any name, on an action exactly as on a component.

The reason is internal consistency: the port's own component tier already accepts a typed `Get`, and having the same type-hint silently fail one tier up is a difference users hit as a bug rather than a design.
Nothing upstream depends on the omission — this only widens what resolves, so every Filament-shaped closure keeps working unchanged.

The cost is that `getSchemaComponentOrFail()` now fires for a `Get`/`Set`-typed parameter on an action rendered outside a schema, where before that closure died on an unresolved-parameter `RuntimeException` instead.
Both are exceptions at the same call; the `LogicException` names the actual problem, so the trade is an improvement.
Reversible by deleting the two arms if upstream ever gives its own reason for the split.

### The view switch renders through Twig templates, not toHtml() fast paths

Filament's five-way view switch is ported: `badge()`/`button()`/`grouped()`/ `iconButton()`/`link()` each point `template()` at one of the five template constants (`BADGE_TEMPLATE`/`BUTTON_TEMPLATE`/`GROUPED_TEMPLATE`/ `ICON_BUTTON_TEMPLATE`/`LINK_TEMPLATE` — the systematic `*_VIEW` → `*_TEMPLATE` translation), with the `isBadge()`/`isButton()`/`isIconButton()`/`isLink()` predicates comparing `getTemplate()` against them (no `isGrouped()` — Filament has none either).
The default stays `BUTTON_TEMPLATE` via `$defaultTemplate`, so `ActionGroup` can push `GROUPED_TEMPLATE` as a default while an explicit switcher call survives the push — Filament-identical precedence, pinned by `ActionTest`.
Each shape has its own template under [`partials/Action/`](../../../packages/actions/templates/partials/Action), and every one of the five is a thin composition over the support primitive Filament's corresponding `*_VIEW` constant points at — [`Badge`](../support/badge.md), [`Button`](../support/button.md), [`DropdownItem`](../support/dropdown-item.md), [`IconButton`](../support/icon-button.md) and [`Link`](../support/link.md), invoked as `<twig:>` tags.
Each builds its mount wiring with `stimulus_action('live', 'action', …)` and spreads it into the tag with `{{ ...map }}` — byte-equivalent to what `live_action()` emits inline.
`actions` owns no trigger CSS at all: the class list on every trigger root is the primitive's, plus the bare `sy-ac` marker the template contributes.
All five are exercised by [`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php) / [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php) / [`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php).

**The five triggers reached Filament's post-consolidation shape in two steps.**
Filament points all five view constants at *support*-package components — `BADGE_VIEW = 'filament::components.badge'`, `BUTTON_VIEW = 'filament::components.button.index'`, `GROUPED_VIEW = 'filament::components.dropdown.list.item'`, `LINK_VIEW = 'filament::components.link'` — and `Action::fromArray()` keeps a back-compat map from the old `filament-actions::button-action` / `grouped-action` / `icon-button-action` / `link-action` names to them.
Those deprecated names are exactly what this port's templates were called, so what shipped first was Filament's *pre-consolidation* shape: five hand-rolled skeletons, three of them carrying their own `sy-ac-badge` / `sy-ac-link` CSS in `actions`.
`Button` and `IconButton` collapsed first, because support already had those primitives; `Badge`, `Grouped` and `Link` followed once support gained [`Badge`](../support/badge.md), [`Link`](../support/link.md) and [`DropdownItem`](../support/dropdown-item.md).
The click cascade — the only structural blocker — turned out to be expressible as an attribute map, so all three collapsed the same way `Button` had.

`ActionGroup`'s own [`GroupedTrigger`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig) came along in the same change: it duplicated the identical dropdown-item skeleton, and is the second consumer that justified making the item a support primitive rather than leaving it `actions`-owned.
Two gaps closed with the move — `->color()` now reaches a grouped action and a grouped group, which [`toGroupedHtml()`](../../../vendor/filament/actions/src/Action.php) and [`toGroupedTriggerHtml()`](../../../vendor/filament/actions/src/ActionGroup.php) both pass and the hand-rolled templates dropped.

What stays divergent: **no string fast paths** — Filament's `toHtml()` dispatch, the `toBadgeHtml()`/`toButtonHtml()`/… renderers and their "optimized" hand-built-HTML variants, and the `toEmbeddedHtml()` fallback have no counterpart; every shape renders through Twig via `ViewComponent::render()`.
**`badge()` is argless** — Filament's `badge(string|Closure|null $badge)` doubles as the badge-*text* setter via `func_num_args()`; `HasBadge` is deferred, so the overload widens when it lands.
**No `renderModal()`/`toModalHtmlable()`** — the confirmation modal ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)) is rendered host-side, driven by `CanOpenModal`'s getters, not by an `Action` method.
The serialization pair Filament keeps beside the view switch — `toArray()`/`fromArray()`, whose `view` key becomes `template` here — **has** landed, with its own Surface row and the key-list and whitelist notes under [Semantics](#semantics).

### The serialized enum values flatten to scalars, and a `ScalableIcon` crosses unflattened

Filament's `toArray()` writes whatever its getters return, objects included: `getIconPosition(): IconPosition` is non-nullable, so `iconPosition` is *always* an enum instance in the payload, and `iconSize` / `size` are `IconSize` / `Size` instances whenever the setter was handed one.
[`Action::toArray()`](../../../packages/actions/src/Action.php) writes `->value` for all three — `iconPosition` unconditionally (no `instanceof` check, because there is nothing else it can be), `iconSize` and `size` only when the value is an enum, since those two getters return a union and a plain string passes straight through.
Those three keys are scalar, which is what lets them cross a session or a JSON hop without depending on how PHP serializes enums; the payload as a whole is not, since `color` carries a shade map, `eventData` and `extraAttributes` carry arrays, `label` and `tooltip` carry `Markup`, and `icon` carries a `ScalableIcon` unflattened.

Nothing is lost on the way back.
`iconPosition` re-resolves exactly: `fromArray()` hands `'before'` to `iconPosition()` and the getter runs it through `IconPosition::tryFrom()`, returning the same case.
`iconSize` and `size` restore as the **string** rather than the enum, and every consumer takes it — [`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php) calls `IconSize::tryFrom()` on a string size, and the support primitives' `size` props are strings, which is what the trigger templates already pass literally as `component.getSize ?? 'md'`.
So the divergence is one of wire format, not of what renders.

`icon` diverges the other way, and for exactly the reason [notifications' does](../notifications/notification.md#a-scalableicon-crosses-the-session-unflattened-and-resolves-at-render-size).
Filament resolves a scalable icon *at serialization time* — `$icon instanceof ScalableIcon` takes `getIconForSize($this->getIconSize() ?? IconSize::Medium)`, a size chosen before anyone knows how the action will render — and only a plain `BackedEnum` falls through to `->value`.
The port inverts those two arms: a `BackedEnum` implementing [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) is kept **as the enum** (the session PHP-serializes enums, and `fromArray()` admits a `BackedEnum` back through `icon()`), and only a plain enum flattens to its value.
Resolution then happens where every other icon resolves — in `generateIconHtml()`'s `ScalableIcon` → `BackedEnum` cascade, at the size the trigger template actually passes — so a scalable icon is size-correct on the serialized path where Filament's is pinned to whatever `iconSize()` said at `toArray()` time.
Pinned by the `ScalableFixtureIcon` case in [`ActionSerializationTest`](../../../packages/actions/tests/Unit/ActionSerializationTest.php).

### The rest of Filament's `Action` surface is unported

Filament's `Action` composes roughly 50 traits/interfaces; the 21 the [package README](README.md#actions--port-progress) lists are folded into this file's Surface table (ported, in some form), 6 more are the support-level traits referenced above, and a handful carry their own rows in the [package README](README.md#features) or their own detail file rather than folding here.
The names below are what is left — nothing started, captured as one list rather than one row each, with each bullet's parenthetical naming the siblings that *have* landed so the list cannot silently go stale:

- **Mounting & modal chrome beyond `CanOpenModal`'s data**: `HasKeyBindings`, `CanBeBooted`, `renderModal()`/`toModalHtmlable()` (`CanBeMounted`, `HasExtraModalOverlayAttributes` and `HasExtraModalWindowAttributes` are ported — see the [package README](README.md#features)).
- **Forms/schemas/infolists integration**: `HasInfolist` (`HasSchema`, `HasWizard`, `HasData`, `CanSubmitForm` and `BelongsToSchemaComponent` are all composed now — the first two through [`has-schema.md`](has-schema.md), the other three through their [package README](README.md#features) rows — and the `schema*` closure-dependency names landed with the schemas-actions integration, see the Details section above).
- **Tables/bulk-action integration**: `HasTableIcon`, `InteractsWithSelectedRecords`, `CanFetchSelectedRecords`, `CanDeselectRecordsAfterCompletion`, `bulk()`/`isBulk()` (`BelongsToTable` is ported — see [belongs-to-table.md](belongs-to-table.md)).
- **Grouping & nesting**: `CanBeSorted` (`BelongsToGroup` is ported — see [action-group.md](action-group.md) — and `CanCallParentAction`, `HasParentActions`, `parentAction()`/ `getParentAction()`, `nestingIndex()`/`getNestingIndex()` and `getClone()` all have rows in the Surface table above).
- **Authorization & policy**: `CanBeAuthorized`, `hasAuthorization()`.
- **Rate limiting**: `CanBeRateLimited` (`CanUseDatabaseTransactions` is ported — Surface row above).
- **Dispatch/mark conveniences**: `livewireTarget()`, custom `alpineClickHandler()`/`actionJs()` — the port's click wiring is the single `live_action('mountAction', …)` call baked into the template (`CanDispatchEvent` and `CanBeMarkedAsRead` are both ported, each with a Surface row above; only the mark flags' *click behaviour* is still missing).
- **Rendering variants**: `HasBadge`, `HasBadgeTooltip` and `CanBeLabeledFrom` (the `toArray()`/`fromArray()` pair Filament files beside them has landed — Surface row above).

None of these is a per-row **blocked**/**todo** entry because none has any ported surface yet to anchor a row to — they will earn Surface rows (and likely their own detail files, per the [one-row-per-file rule](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature)) the moment work starts on each.

### The live-component chain narrows inline instead of through a second getter

Filament's `getLivewire()` is a multi-step resolver — explicit `$livewire`, then `getSchemaContainer()?->getLivewire()`, `getSchemaComponent()?->getLivewire()`, `getTable()?->getLivewire()`, `getGroup()?->getLivewire()` — plus a second method, `getHasActionsLivewire()`, narrowing the generic result to `Contracts\HasActions`.
Sylament's [`getLiveComponent()`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) walks all four hops in Filament's order, each narrowed inline by a private `asActionsHost()` guard rather than by a second method, so a host implementing only schemas' `InteractsWithSchemas` never satisfies the chain.
The two schema hops read through the schemas side's Sylament-only `getLiveComponentOrNull()` peeks (schemas' primary getters throw, see [`schemas/belongs-to-live-component.md`](../schemas/belongs-to-live-component.md)); the table hop reads [`Table::getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php), which is non-nullable there because `Table::make()` always binds one, and gets narrowed by the same guard — a table host that interacts with tables but not with actions resolves nothing.

One delta stays deliberate: the getter keeps its non-nullable `InteractsWithActions` return and throws when nothing resolves — the fail-fast stand-in for Filament's nullable return + separate `getHasActionsLivewire()` narrowing step — while the chain itself lives in the Sylament-only `getLiveComponentOrNull()` peek (mirroring the schemas-side precedent by name), which `prepareModalAction()`/`prepareModalActionGroup()` use to bind footer actions without forcing an unbound owner to throw.

The group hop is kept even though [`ActionGroup::liveComponent()`](../../../packages/actions/src/ActionGroup.php) eagerly propagates the host to child actions at set time: the eager push only fires at `liveComponent()`-call time, so an action added to an already-bound group afterwards resolves through this lazy hop and nothing else.
Covered by [`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php): explicit-set precedence, both schema hops, the late-joining group hop, the schemas-only-host refusal, and the unbound throw; the table hop by [`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php), which lives in the tables suite because only there can a real `Table` be built.

### `getContext()` drops the record model gate

Filament's `getContext()` assembles up to four entries: `recordKey` (via `resolveRecordKey()`, guarded by table-model type checks), an early return carrying only `recordKey` for child modal actions (`getParentAction()`), `table`/`bulk` flags, and `schemaComponent`.
The port's [`getContext()`](../../../packages/actions/src/Action.php) emits three of them in the same order — `recordKey`, then (past the child-frame return) `table`, then `schemaComponent`, the key of the bound schema container falling back to the bound schema component.

One entry diverges.
The **model gate** around `recordKey` is dropped: Filament only emits a key when the record has no table, is not an Eloquent `Model`, the table declares no model, or the record's class matches it — a guard against a table whose rows are a different model than the action's own record.
Sylament has no model class to compare, so the key is emitted whenever *something* yields one — but **which** something now depends on the table.

That branch has a table arm.
When the action has a table and the record is an instance of the table's [record class](../tables/has-query.md#the-record-class-is-read-off-the-base-querys-root-entity), `getContext()` asks the table — `Table::getRecordKey()`, the host's own read, which goes through [`RecordKey::of()`](../schemas/record-key.md) on the query lane — and only otherwise falls back to [`resolveRecordKey()`](interacts-with-record.md#record-key-resolution-is-hoisted-to-hasactions)'s literal `id`.
That is not a gate restored but the gap [has-table.md named and left open](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) being closed the way it proposed: the actions layer is handed the table's key rather than taught Doctrine, so a row action on a `Book`, a `Shortcut` or a `Residency` mounts **with** its record instead of silently without one.
An action with no table, or over a record that is not the table's entity — a `SELECT NEW` projection, a DTO — reads `id`, and only where **one scalar field** is the record's whole identity, which is the shape a single property read can name.
A private `isRecordKeyedByOneScalarField()` asks `ClassMetadata` for the identifier and answers true where it holds one field and that field is not an association — whatever the field is called, so a record keyed by a `sku` or a slug mints exactly the key it always minted.
For a **composite** identifier and for an **association** one, `getContext()` mints no `recordKey` at all, so the action's declared record stands — which is exactly what a composite record with no `id` property has always done.
What the gate removes is a key that named no record: the partial `"5"` of an `id`-plus-`locale` identifier, which a host that decodes strictly then refuses as a 404 on click, and the stray non-identifier `id` of a composite entity, which was a 500.
It lives on `Action` rather than in the trait because `ActionGroup` composes `InteractsWithRecord` **without** the manager registry the question needs, and it keeps the literal reading wherever no mapping answers — an anonymous class, which Doctrine refuses to describe at all; and a class no manager maps, which is the arm an action built outside the container takes too, holding no registry to ask.
In each of those, the literal reading is the only identity there is; an **array** record never reaches the gate at all, the `is_object()` test in front of it leaving `ArrayRecord`'s `__key` entry to the literal read.

One shape the gate deliberately does not catch, because the mapping cannot tell it apart from the ordinary one: a record keyed by one scalar field whose readable `id` is something else — a legacy serial beside a natural key — mints that `id`, exactly as before the gate existed.
On a panels page the resource then resolves it by the identifier and answers a 404.
That is the [`$recordRouteKeyName`](../panels/resource.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it) family's pre-existing mismatch, closed only by a host hook that lets a page mint the key itself, which nothing provides yet.

It also means the read can now **throw** where it used to stay quiet: a record of the table's own class whose identifier is incomplete — an explicitly-set, not-yet-persisted entity — makes `RecordKey::of()` refuse, where `resolveRecordKey()` would simply have omitted `recordKey`.
That is deliberate, and it is the same record the table could never have resolved on the way back; `getRecord(withDefault: false)` is what keeps a header `CreateAction` out of that path, and a row is always keyable, so a row action never sees it — no shipped path reaches that throw.

The record it resolves the key from is read with [`withDefault: false`](interacts-with-record.md#the-record-chain-reaches-the-schema-the-component-and-the-host), so a record the *host* supplied as a default never becomes a `recordKey` on the wire — the host answers it again on the way back, and sending it would only hand the host its own state.
The **`bulk` flag** is ported verbatim, in Filament's own position and with Filament's own double `hasTable()` test — a bulk action with no table emits neither key, which is what routes it to the record-action registry rather than the bulk one ([bulk-action.md](bulk-action.md)).

The **child-frame short-circuit is ported** verbatim: `if ($this->parentAction instanceof self) { return $context; }` runs after the `recordKey` branch and before the table and schema branches, so a modal action declared by another action carries its record identity up and nothing else — not the schema binding it inherited from `prepareModalAction()`, not the table binding it inherited from its parent.
Without it, a nested frame would carry a `schemaComponent` key up to the host and — but for the host's own [deeper-frame gate](has-actions.md#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache) — try to resolve against the host's schema instead of its parent's declared modal actions.
Covered by `ActionTest`'s context cases (empty without a binding, container key, component-key fallback, keyless container, an unresolvable record key, the emitted `recordKey`, a bound-schema action whose `parentAction` suppresses the key the binding alone would emit, and a bulk action with no table emitting no `bulk` key) and by [`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php) for the arms that need a real `Table` — the `table` and `bulk` flags and their suppression on a nested frame.

### CanBeDisabled drops the authorization short-circuits

Filament's `isDisabled()` falls through `isHidden()` into three more branches — `hasAuthorizationNotification()`, `hasAuthorizationTooltip()`, and `isAuthorized()` — that let an unauthorized-but-visible action stay enabled (so it can show a tooltip or fire a notification on click instead of vanishing or graying out).
Sylament's port stops at `isHidden()`: a component is disabled when `$isDisabled` is truthy or the component is hidden, full stop.
This is a deliberate MVP simplification — the toolkit has no authorization-tooltip or authorization-notification concept yet.
Reverses once those two concerns (and the `isAuthorized()` contract they lean on) are ported and wired onto `Action`.

### Outlined styling is a state class, not a color map

Filament's outlined button resolves through `ButtonSize`/color-map machinery in `button.blade.php` (per-color class maps composed in PHP).
Sylament's `CanBeOutlined` port is API-identical (`outlined()` / `isOutlined()` / `hasOutlined()`), but the rendering side is a single code-less state class: [`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) passes `:outlined="component.isOutlined"` to support's `Button`, which emits the `sy-outlined` class itself and — since the colour registry — also uses the flag to pick [`OutlinedButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php) over `ButtonComponent`, so an outlined action gets a genuinely different colour map rather than only a different rule ([why](../support/button.md#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class)).
[`button.css`](../../../packages/support/assets/src/styles/components/button.css) carries one `.sy-btn.sy-outlined` variant rule driven by the same `--sy-color-*` runtime tokens and slot variables the filled button uses — per the repo styling contract (semantic classes + runtime tokens, no per-color class maps).
The button template is the only consumer, matching Filament (its link/badge/ icon-button/grouped views ignore `outlined`).

### HasGroupedIcon narrows its icon type to string

Filament types the grouped icon `string|BackedEnum|Htmlable|Closure|null`; the port narrows it to `string|Closure|null` — no enum or `Markup` icon values exist in the actions package yet, the same narrowing `CanOpenModal`'s `modalIcon` already applies (support's `HasIcon` keeps the wide type, so `getIcon(default:)` accepts the narrowed value unchanged).
Consumed exactly where Filament consumes it: [`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig) resolves its icon as `component.getIcon(component.getGroupedIcon)` — Filament's `icon: $this->getIcon(default: $this->getGroupedIcon())` — so the grouped icon only surfaces inside a dropdown item, never on the button shape.
Widen the type in lockstep with `HasIcon`'s enum/Markup support if that ever lands here.

### Label translation and the deprecated `disableLabel()` alias are not ported

Filament's `HasLabel` carries `$shouldTranslateLabel` + `translateLabel()` and runs a string `getLabel()` result through Laravel's `__()` translator, plus a `@deprecated disableLabel()` alias for `hiddenLabel()`.
Sylament drops all three — no framework-wide translation helper is wired into the repo yet (same call made for schemas' `HasLabel`, see [`schemas/has-label.md`](../schemas/has-label.md#label-translation-and-the-deprecated-disablelabel-are-not-ported)), and a fresh codebase has no reason to ship the deprecated alias.
Reverses for translation if/when a translation layer lands; the deprecated alias stays out regardless.

### Action invocation is a PHP-side seam only

`HasMountableArguments` is a verbatim port — `$action(['id' => 5])` clones the action, binds the arguments through `arguments()`, and records them as `invokedArguments`, so a per-render binding never mutates a host-cached instance.
All five trigger templates serialize **only** `getInvokedArguments()` into the mount emission (`live_action('mountAction', { name, arguments })` — Filament-exact: `getLivewireClickHandler()` serializes the invoked arguments, never `getArguments()`), and emit no `arguments` param when the action was never invoked.
The divergence is the call site: Blade is PHP, so Filament templates can invoke `($action)(['id' => 5])` inline; Twig has no call syntax for invokable objects, so the invocation seams are PHP-side configuration and the `renderAction()` Twig helper's argument binding (`renderAction(name, arguments)`, the Twig-facing wrapper for `__invoke`).
Permanent — a Twig-side call syntax would require a helper function anyway, which `renderAction()` already is.

### `getName()` tightens its return type to non-nullable `string`

Filament types `getName(): ?string` even though the method always throws `LogicException` before returning when the name is `blank()` — the nullable signature never actually surfaces a `null`.
The port's [`getName()`](../../../packages/actions/src/Concerns/HasName.php) declares `: string`, matching the trait's real behavior rather than Filament's vestigial nullability.
Purely a type-signature tightening with no behavioral change (both throw on a blank name); reverse by widening back to `?string` if some future caller needs to distinguish "no name" from "threw" without a try/catch.

### HasSize drops the optimized-render fast-path helper

Filament's `HasSize` carries `hasSmallSizeDefault(): bool` (`$this->size === null && $this->defaultSize === Size::Small`), consumed only by `Action::canRenderOptimizedLink()` / `canRenderOptimizedGrouped()` — a Blade-bypass fast path that hand-builds link/grouped-button HTML instead of going through the full component render when the action carries no non-default styling.
Sylament's `Action` has not yet ported `render()`/`toHtml()`/the `toButtonHtml()`/`toOptimizedLinkHtml()` family at all, so the method has no consumer today.
Reversible: port `hasSmallSizeDefault()` alongside whichever future item ports Action's HTML-rendering/optimized-path pipeline; until then the omission tracks that unstarted surface, not this trait's own `size()`/`defaultSize()`/`getSize()` contract, which is fully ported and tested.

_See also:_ the global [rename table](../renames.md) — the `Filament\Actions\Concerns\InteractsWithActions` ⇄ `HasActions` / `Filament\Actions\Contracts\HasActions` ⇄ `InteractsWithActions` swap, and the systematic `Htmlable` → `Markup`, Livewire → UX LiveComponent (`getLivewire()` → `getLiveComponent()`), and Blade → Twig translations this file leans on throughout, are recorded there rather than re-derived here.
