---
description: The base class every actions-package action extends, with the members it declares itself and the concerns no other trail file documents.
ports:
  - vendor/filament/actions/src/Action.php
  - vendor/filament/actions/src/Concerns/BelongsToLivewire.php
  - vendor/filament/actions/src/Concerns/CanBeDisabled.php
  - vendor/filament/actions/src/Concerns/CanBeHidden.php
  - vendor/filament/actions/src/Concerns/CanBeOutlined.php
  - vendor/filament/actions/src/Concerns/CanCallParentAction.php
  - vendor/filament/actions/src/Concerns/CanRedirect.php
  - vendor/filament/actions/src/Concerns/HasArguments.php
  - vendor/filament/actions/src/Concerns/HasGroupedIcon.php
  - vendor/filament/actions/src/Concerns/HasLabel.php
  - vendor/filament/actions/src/Concerns/HasLifecycleHooks.php
  - vendor/filament/actions/src/Concerns/HasMountableArguments.php
  - vendor/filament/actions/src/Concerns/HasName.php
  - vendor/filament/actions/src/Concerns/HasParentActions.php
  - vendor/filament/actions/src/Concerns/HasSize.php
owns:
  - packages/actions/src/Action.php
  - packages/actions/src/Concerns/BelongsToLiveComponent.php
  - packages/actions/src/Concerns/CanBeDisabled.php
  - packages/actions/src/Concerns/CanBeHidden.php
  - packages/actions/src/Concerns/CanBeMarkedAsRead.php
  - packages/actions/src/Concerns/CanCallParentAction.php
  - packages/actions/src/Concerns/CanRedirect.php
  - packages/actions/src/Concerns/HasArguments.php
  - packages/actions/src/Concerns/HasLifecycleHooks.php
  - packages/actions/src/Concerns/HasMountableArguments.php
  - packages/actions/src/Concerns/HasName.php
  - packages/actions/src/Concerns/HasParentActions.php
  - packages/actions/templates/partials/Action/
---
# Action (actions)

`Sylament\Actions\Action` extends support's [`ViewComponent`](../support/view-component.md).
Most concerns it composes have a file of their own, named after the trait or after the class that shares it, and the selected-records trio lives in [`bulk-action.md`](bulk-action.md); this file holds the members `Action` declares itself and the concerns no other file documents.
The Livewire → UX LiveComponent, Blade → Twig and `Htmlable` → `Markup` translations it leans on are recorded in [`translations.md`](../translations.md), and the `HasActions` ⇄ `InteractsWithActions` swap in [`renames.md`](../renames.md#interface-and-trait-names-are-swapped).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `getDefaultName()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | — |
| [`call()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [semantics](bulk-action.md#the-post-call-deselect-reaches-its-host-through-the-nullable-accessor) · [detail](#canbehidden-omits-authorization-group-and-table-cache-integration) |
| [`cancel()` / `halt()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [cancel.md](../support/cancel.md) · [halt.md](../support/halt.md) |
| `hold()` (deprecated) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | skipped | — | [diverges](#the-deprecated-hold-and-withattributes-aliases-are-not-ported) |
| [`success()` / `failure()` / `getStatus()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [bulk-action.md](bulk-action.md) |
| [`bulk()` / `isBulk()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [bulk-action.md](bulk-action.md) |
| [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | — | [detail](#the-by-name-resolver-lacks-mountedactions-parentaction-and-schemaoperation) · [diverges](#the-selected-record-injections-are-gated-on-the-access-flag) |
| `mountedParentAction()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | todo | — | [detail](#the-by-name-resolver-lacks-mountedactions-parentaction-and-schemaoperation) |
| [`resolveDefaultClosureDependencyForEvaluationByType()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | in progress | — | [detail](#the-by-type-resolver-has-no-record-arm) · [diverges](#the-selected-record-injections-are-gated-on-the-access-flag) · [diverges](#getset-resolve-by-type-as-well-as-by-name) |
| [The `table` injection, by name and by `Table::class`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (the name arm) | done | — | [diverges](#tableclass-resolves-by-type-as-well-as-by-name) · [semantics](#a-table-bound-action-titles-and-labels-through-its-table) |
| [`getSchemaComponentState()` / `getSchemaState()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [semantics](#schemastate-is-the-mounted-items-state-for-an-extra-item-action) · [semantics](#get-and-set-throw-on-an-action-with-no-schema-component-where-filament-fatals) |
| [`badge()` / `button()` / `grouped()` / `iconButton()` / `link()` and the `is*()` predicates](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
| [`shouldClearRecordAfter()` / `clearRecordAfter()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [semantics](#the-record-chain-reaches-the-schema-the-component-and-the-host) |
| [`getContext()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#getcontext-drops-the-record-model-gate) |
| [`getClone()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [action-group.md](action-group.md#getclone-rebuilds-the-flat-map-from-the-cloned-members-rather-than-cloning-it-separately) |
| [`toArray()` / `fromArray()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened) · [semantics](#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl) · [semantics](#fromarray-restores-only-the-five-built-in-templates) · [semantics](#fromarray-degrades-a-to-dispatch-with-no-target-component-to-a-plain-dispatch) |
| [`nestingIndex()` / `getNestingIndex()` / `parentAction()` / `getParentAction()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | [diverges](#getcontext-drops-the-record-model-gate) |
| [`getLiveComponentCallMountedActionName()`](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`getLivewireCallMountedActionName()`) | done | — | [can-open-modal.md](can-open-modal.md) |
| [Click-handler resolution](../../../packages/actions/templates/partials/Action/) (the trigger templates' precedence) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`getLivewireClickHandler()`, `getLivewireEventClickHandler()`, `getJsClickHandler()`) | done | — | [diverges](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| `alpineClickHandler()` / `actionJs()` / `getAlpineClickHandler()` / `getCustomAlpineClickHandler()` / `hasAlpineClickHandler()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | skipped | — | [diverges](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| `livewireTarget()` / `getLivewireTarget()` / `hasLivewireTarget()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | todo | — | — |
| `withAttributes()` (deprecated) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | skipped | — | [diverges](#the-deprecated-hold-and-withattributes-aliases-are-not-ported) |
| [Trigger rendering](../../../packages/actions/templates/partials/Action/) (five templates) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`CanGenerate*Html`) | in progress | — | [detail](#tooltip-and-icon-position-rendering-on-an-action-are-untested) · [semantics](#an-uncolored-badge-or-link-action-renders-primary-not-gray) · [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
| `toHtml()` / `toOptimized*Html()` / `renderModal()` / `toModalHtmlable()` / `hasCustomLivewireClickHandler()` | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | skipped | — | [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
| `HasBadge` / `HasBadgeTooltip` on `Action` | [`Concerns/HasBadge.php`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | todo | — | [diverges](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths) |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | [component.md](../support/component.md) |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | [view-component.md](../support/view-component.md) |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) (support) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | [has-icon.md](../support/has-icon.md) |
| [`HasIconPosition`](../../../packages/support/src/Components/Concerns/HasIconPosition.php) (support) | [`Concerns/HasIconPosition.php`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | done | — | — |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) (support) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | [view-component.md](../support/view-component.md) |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) (support) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | [view-component.md](../support/view-component.md) |
| [`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) | [`Concerns/BelongsToLivewire.php`](../../../vendor/filament/actions/src/Concerns/BelongsToLivewire.php) | done | — | [diverges](#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter) |
| [`CanBeDisabled`](../../../packages/actions/src/Concerns/CanBeDisabled.php) | [`Concerns/CanBeDisabled.php`](../../../vendor/filament/actions/src/Concerns/CanBeDisabled.php) | done | — | [diverges](#canbedisabled-drops-the-authorization-short-circuits) |
| [`CanBeHidden`](../../../packages/actions/src/Concerns/CanBeHidden.php) | [`Concerns/CanBeHidden.php`](../../../vendor/filament/actions/src/Concerns/CanBeHidden.php) | in progress | — | [detail](#canbehidden-omits-authorization-group-and-table-cache-integration) |
| [`CanBeMarkedAsRead`](../../../packages/actions/src/Concerns/CanBeMarkedAsRead.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`markAsRead()` and its five siblings) | in progress | — | [detail](#canbemarkedasread-carries-the-flags-nothing-reads-them-yet) |
| [`CanBeOutlined`](../../../packages/actions/src/Concerns/CanBeOutlined.php) | [`Concerns/CanBeOutlined.php`](../../../vendor/filament/actions/src/Concerns/CanBeOutlined.php) | done | — | [button.md](../support/button.md#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class) |
| [`CanCallParentAction`](../../../packages/actions/src/Concerns/CanCallParentAction.php) | [`Concerns/CanCallParentAction.php`](../../../vendor/filament/actions/src/Concerns/CanCallParentAction.php) | done | — | [semantics](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side) |
| [`CanRedirect`](../../../packages/actions/src/Concerns/CanRedirect.php) | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | done | — | [diverges](#redirect-records-a-pending-url-instead-of-redirecting-the-host) |
| [`HasArguments`](../../../packages/actions/src/Concerns/HasArguments.php) | [`Concerns/HasArguments.php`](../../../vendor/filament/actions/src/Concerns/HasArguments.php) | done | — | — |
| [`HasGroupedIcon`](../../../packages/actions/src/Concerns/HasGroupedIcon.php) | [`Concerns/HasGroupedIcon.php`](../../../vendor/filament/actions/src/Concerns/HasGroupedIcon.php) | done | — | [diverges](#hasgroupedicon-narrows-its-icon-type-to-string) |
| [`label()` / `getLabel()`](../../../packages/actions/src/Concerns/HasLabel.php) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | [semantics](#the-name-derived-label-is-the-names-segment-before-the-first-dot) |
| [`hiddenLabel()` / `isLabelHidden()` / `hasLabelHidden()`](../../../packages/actions/src/Concerns/HasLabel.php) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | — |
| `translateLabel()` | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported) |
| `disableLabel()` (deprecated) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported) |
| [`HasLifecycleHooks`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) | [`Concerns/HasLifecycleHooks.php`](../../../vendor/filament/actions/src/Concerns/HasLifecycleHooks.php) | done | — | [diverges](#the-lifecycle-events-are-dispatched-as-objects-where-filament-dispatches-a-class-string) · [has-actions.md](has-actions.md#callmountedaction-runs-filaments-pipeline-in-filaments-order) |
| [`HasMountableArguments`](../../../packages/actions/src/Concerns/HasMountableArguments.php) | [`Concerns/HasMountableArguments.php`](../../../vendor/filament/actions/src/Concerns/HasMountableArguments.php) | done | — | [diverges](#action-invocation-is-a-php-side-seam-only) |
| [`HasName`](../../../packages/actions/src/Concerns/HasName.php) | [`Concerns/HasName.php`](../../../vendor/filament/actions/src/Concerns/HasName.php) | done | — | [diverges](#getname-tightens-its-return-type-to-non-nullable-string) |
| [`HasParentActions`](../../../packages/actions/src/Concerns/HasParentActions.php) | [`Concerns/HasParentActions.php`](../../../vendor/filament/actions/src/Concerns/HasParentActions.php) | done | — | [semantics](#three-of-hasparentactions-accessors-have-no-reader-in-either-codebase) |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | — |
| `hasSmallSizeDefault()` | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | skipped | — | [diverges](#hassize-drops-the-optimized-render-fast-path-helper) |

## Details

### The by-name resolver lacks `mountedActions`, `parentAction` and `schemaOperation`

Filament's by-name resolver answers `arguments`, `data`, `livewire`, `model`, `mountedActions`, `parentAction`, `record`, `selectedRecords`/`records`, `selectedRecordsQuery`/`recordsQuery`, `schema`, `schemaComponent`/`component`, `schemaOperation`/`context`/`operation`, `schemaGet`/`get`, `schemaSet`/`set`, `schemaComponentState`/`state`, `schemaState` and `table`.
The port's [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/actions/src/Action.php) answers all of them but three, with `livewire` as `liveComponent` and `model` as `recordClass` under the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record).
`recordClass` resolves [`getRecordClass()`](#the-record-chain-reaches-the-schema-the-component-and-the-host), so a closure can name the class an action creates into before any record exists.
`data` resolves [`HasData`](../../../packages/actions/src/Concerns/HasData.php)'s `getData()`, so a mounted action's callback reads its submitted form state as `$data`.
The two selected-record names sit behind the [access-flag gate](#the-selected-record-injections-are-gated-on-the-access-flag), and they resolve the plain selection where Filament resolves the [individually authorized](bulk-action.md#per-record-authorization-waits-until-authorization-lands) one.

The three missing names:

- `mountedActions` has nothing in its way: the host contract declares [`getMountedActions()`](../../../packages/actions/src/Contracts/InteractsWithActions.php).
- `parentAction` resolves `$this->mountedParentAction ?? $this->getParentAction()` in Filament, where the host stamps `mountedParentAction()` on each nested action it resolves and clears it on unmount (`InteractsWithActions.php:620` and `:898`).
  The port has `getParentAction()` but neither the setter nor the host stamp.
- `schemaOperation`/`context`/`operation` would read [`getOperation()`](../schemas/has-operation.md) off the bound schema container, falling back to the component's container; both exist, and the arm has no caller.

Pinned by `ActionTest`'s injection cases, by [`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php) for `liveComponent`, and by the tables suite's [`DeleteBulkActionTest`](../../../packages/tables/tests/Integration/DeleteBulkActionTest.php), where a real host answers the two selected-record names.

### The by-type resolver has no record arm

Filament's by-type resolver maps `Builder::class` to the selected-records query, `EloquentCollection::class` and `Collection::class` to the individually authorized selected records, and `Model::class` or the record's own class to the current record.
The port maps `Doctrine\ORM\QueryBuilder::class` to [`getSelectedRecordsQuery()`](bulk-action.md#the-two-record-readers-share-one-gate-and-one-message), behind the same [access-flag gate](#the-selected-record-injections-are-gated-on-the-access-flag) the by-name arms carry.
It has no collection arm, because a Doctrine read returns a plain `list` and the `records` name carries it.
The record arm is missing.
With no `Model::class` base to key it on, it would resolve on the record's own class, the way [`getCustomRecordTitle()`](#the-record-chain-reaches-the-schema-the-component-and-the-host)'s typed injection does, and the [`getRecord()`](#the-record-chain-reaches-the-schema-the-component-and-the-host) walk it would read already reaches the schema container and component.
A type-hinted record parameter therefore falls through to support's `EvaluatesClosures::resolveDefaultClosureDependencyForEvaluationByType()` and is not resolved.

The override also carries [`Table::class`](#tableclass-resolves-by-type-as-well-as-by-name) and the [`Get`/`Set` pair](#getset-resolve-by-type-as-well-as-by-name), which Filament's does not.

### CanBeHidden omits authorization, group, and table-cache integration

**In:** `hidden()`, `visible()`, `isHidden()` and `isVisible()`, resolved as Filament's base resolution is: an evaluated `$isHidden` that is true wins, otherwise `! evaluate($isVisible)`.
Covered by [`CanBeHiddenTest`](../../../packages/actions/tests/Unit/Concerns/CanBeHiddenTest.php).

**Missing vs Filament:** Filament's `isHidden()` short-circuits true when the action's group reports `baseIsHidden()`, then defers to `isHiddenInGroup()`, and both cache their answer per record when the action has a table (`prepareVisibilityCache()`, `clearVisibilityCache()`, keyed by `getCurrentVisibilityRecordKey()`).
Under the cache, `resolveIsHiddenInGroup()` hides an `ActionGroup` whose children all report `isHiddenInGroup()`, and otherwise falls back to `! isAuthorizedOrNotHiddenWhenUnauthorized()`, so an action with no `hidden()` or `visible()` call is hidden when the current user is unauthorized.
The port's `isHidden()` is `resolveIsHiddenInGroup()` without the `ActionGroup` branch and without the authorization fallback; the child walk lives on [`ActionGroup::isHidden()`](../../../packages/actions/src/ActionGroup.php) as an override ([see there](action-group.md#no-ishiddeningroup-or-visibility-caching)).
So an action with no `hidden()` or `visible()` call is visible even where Filament would hide it because the user fails `CanBeAuthorized`'s policy check or because its group is hidden.
With nothing cached, `call()`'s `finally` carries no `clearVisibilityCache()` call, which is the second branch of Filament's.

**Next step:** the authorization fallback waits for `CanBeAuthorized`, which supplies `isAuthorizedOrNotHiddenWhenUnauthorized()`.
The group short-circuit needs the port's `ActionGroup` to expose its own base flag, which Filament's group does by aliasing the trait's `isHidden` as `baseIsHidden` (`ActionGroup.php:50`).
The record-keyed cache can key on `getRecord()` and `hasTable()` as they stand.

### CanBeMarkedAsRead carries the flags; nothing reads them yet

**In:** [`CanBeMarkedAsRead`](../../../packages/actions/src/Concerns/CanBeMarkedAsRead.php) ports, body for body, the six members Filament declares inline on its `Action` class: the `markAsRead()` / `markAsUnread()` setters, their `shouldMarkAsRead()` / `shouldMarkAsUnread()` evaluate-and-cast getters, and the `hasMarkAsRead()` / `hasMarkAsUnread()` `!== false` pair.
It is a trait here because that is where this port files a single-purpose member group, as it does for `CanClose`; the surface is identical either way.
Both flags round-trip through [`toArray()`/`fromArray()`](#the-serialized-key-list-drops-alpineclickhandler-and-shouldposttourl), which a notification action needs: it declares `->markAsRead()` on the sending side, and the flag has to survive the flash bag.

**Out:** the behaviour.
Filament reads the pair in `getAlpineClickHandler()`, which emits the literal `markAsRead()` / `markAsUnread()` Alpine expressions against the database-notifications panel's Livewire component, and its `has*()` getters join the no-customisation test in `canRenderOptimizedLink()` and `canRenderOptimizedGrouped()`.
Neither exists here: there is no database-notifications panel to mark anything read in, and the port's click wiring is the trigger templates' precedence rather than a resolved handler expression ([diverges](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)).
So `->markAsRead()` changes nothing a user can see.

**Next step:** wire both flags into the click precedence when the database-notifications panel exists, alongside the notification-side surface [`notifications/README.md`](../notifications/README.md) tracks.

### Tooltip and icon-position rendering on an action are untested

**In:** every trigger template passes the action's label, icon, icon size and position, colour, size, disabled state, URL and new-tab target, tooltip and extra attributes to the support primitive it composes.
[`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php) covers the label, colour, size, icon, disabled state, link-versus-button choice and mount wiring, and [`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php) covers extra attributes merging onto the icon-button root.

**Missing test coverage:** no test renders an `Action` with `->tooltip(…)` and checks the `title` attribute, and none renders `->iconPosition('after')` and checks that the icon follows the label.
The primitives' own tests ([`BadgeRenderTest`](../../../packages/actions/tests/Integration/Components/BadgeRenderTest.php), [`LinkRenderTest`](../../../packages/actions/tests/Integration/Components/LinkRenderTest.php)) cover icon position on the primitive, not through an action.

**Next step:** add both cases to `ActionRenderTest`; no code change is expected.

## Semantics

### `$schemaState` is the mounted item's state for an extra item action

[`getSchemaState()`](../../../packages/actions/src/Action.php) carries Filament's leading branch: when the bound schema component implements schemas' [`HasExtraItemActions`](../../../packages/schemas/src/Components/Contracts/HasExtraItemActions.php) and the action's arguments carry a non-empty `item`, `$schemaState` is that item's state rather than the container walk's, which is how a repeater's extra item actions see the row they were mounted on.
The key is stringified before the call, so a numeric item key from the wire reaches `getItemState(string $key)` intact.
The guard is a bare `!== ''` rather than [`filled()`](../support/helpers.md): the key is already narrowed to `int|string` there, and `filled()`'s only static assertion is a null check, so calling it is an `alreadyNarrowedType` error at level max.
Otherwise the walk returns the first state-path-bearing container's `getState()`, which is the port's equivalent of Filament's `getStateSnapshot()` ([`HasState`](../../../packages/schemas/src/Concerns/HasState.php)).
`getSchemaComponentState()` walks the other way, up to the nearest parent component with a state path, and falls back to the bound component's own state.
Pinned by `ActionTest`'s `$state` and `$schemaState` cases.

### `$get` and `$set` throw on an action with no schema component, where Filament fatals

The `get` and `set` arms keep Filament's non-null-safe reach for the schema component, routed through [`getSchemaComponentOrFail()`](belongs-to-schema-component.md#getschemacomponentorfail-throws-where-filament-fatals), whose `?? throw` is what satisfies level max.
A schema-less action evaluating a `$get` or `$set` closure therefore stops the request where Filament's does, on a `LogicException` naming the class rather than a fatal on null, pinned by `ActionTest`.
An action declared inside a mounted-action schema throws the same way, as it fails in Filament, because that schema is a root schema with no parent component ([has-actions.md](has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema)).

Both arms chain `->skipComponentsChildContainersWhileSearching(false)` onto the returned [`Get`](../schemas/get.md) or [`Set`](../schemas/set.md), as Filament's do.
Without it, the utility's default self-skip keeps it out of the bound component's own child schema, so a container-hosted action's `$get` or `$set` (a `Section` action reading a field nested inside that same `Section`) would fall back to a raw, uncast `PropertyAccessor` read or write instead of going through the owning field's `getState()` or `state()`.

### The serialized key list drops `alpineClickHandler` and `shouldPostToUrl`

[`toArray()`](../../../packages/actions/src/Action.php) writes 21 keys where Filament's writes 23, and the two missing ones read as an oversight only until you check what they would carry.
**`alpineClickHandler`** is Filament's `getCustomAlpineClickHandler()`, an Alpine expression evaluated in the browser against the component that renders it.
The port's translation of a custom click handler is a **live action name bound to a host** ([diverges](has-action.md#string-click-handlers-are-live-action-names-and-close-dismisses-client-side)), and a live action name means nothing to whatever host rehydrates the action on the far side of the hop, so there is no value worth writing.
**`shouldPostToUrl`** has no getter to call at all, because POST-to-url is [not ported](can-open-url.md#post-to-url-support-is-not-ported).
Every other key is present, with vendor's `view` named `template` (the Blade → Twig translation in [`translations.md`](../translations.md)), though present is not the same as identical: four of them carry a different **value shape** than Filament writes ([diverges](#the-serialized-enum-values-flatten-to-scalars-and-a-scalableicon-crosses-unflattened)).

Nothing closure-bearing is written either, and that *is* Filament-exact rather than a port decision: `action`, the modal configuration, the lifecycle hooks and the visibility closures are absent from both key lists, because a `Closure` does not survive a session.
An action restored from an array is the **trigger** (label, colour, icon, url, dispatch, template), not the behaviour behind it.

### `fromArray()` restores only the five built-in templates

The `template` key crosses a trust boundary: a serialized action rides the session flash bag, and its restored value is handed to `$twig->render()` by [`ViewComponent::render()`](../../../packages/support/src/Components/ViewComponent.php).
So [`fromArray()`](../../../packages/actions/src/Action.php) admits it only when it is one of `Action`'s own five `*_TEMPLATE` constants (`BADGE_TEMPLATE`, `BUTTON_TEMPLATE`, `GROUPED_TEMPLATE`, `ICON_BUTTON_TEMPLATE`, `LINK_TEMPLATE`).
Anything else is **dropped, not rejected**: the setter is never called, `getTemplate()` falls through to `$defaultTemplate`, and the action renders as a button.
The whitelist is `static::`-qualified, so a subclass that overrides a constant whitelists its own value.

Filament gates the same key with `isViewSafe()`, a `Str::startsWith($view, 'filament::')` prefix test, which admits any view in the package namespace including ones a user registered there.
That arm is not ported: with five templates and no custom-template surface, a prefix test would only widen what a tampered payload can name.
It returns with a custom template surface.

### `fromArray()` degrades a `to` dispatch with no target component to a plain dispatch

Filament restores the dispatch trio with `dispatchTo($data['dispatchToComponent'] ?? null, …)`, passing a possible `null` straight into a non-nullable `string $component` parameter, which Laravel tolerates and `declare(strict_types=1)` does not.
The port guards the arm instead (`$direction === 'to' && is_string($to)`), so a payload claiming direction `to` without a usable component falls through to the `default` arm and dispatches the event **plainly** rather than throwing a `TypeError` mid-render.
The event and its data still travel; only the targeting is lost.
Pinned by the tampered-payload case in [`ActionSerializationTest`](../../../packages/actions/tests/Unit/ActionSerializationTest.php).

### An uncolored badge or link action renders primary, not gray

`getColor()` is `null` for an action nobody called `->color()` on, and the `Badge`, `Button`, `IconButton` and `Link` triggers pass it as `:color="component.getColor ?? 'primary'"`.
The fallback is load-bearing: `{% props %}` compiles to `array_key_exists('color', $context['__props'])` ([`PropsNode.php`](../../../vendor/symfony/ux-twig-component/src/Twig/PropsNode.php)), so an explicitly passed `null` **replaces** the primitive's `'primary'` default rather than falling back to it, and a bare getter would render every uncolored action with no colour classes at all.
An uncolored badge or link action therefore emits `sy-color sy-color-primary` and renders through the primitive's `.sy-color` branch; `:not(.sy-color)` is unreachable from these triggers.

That is Filament's behaviour, not a divergence: `badge.blade.php` and `link.blade.php` both default `'color' => 'primary'`.
The one trigger where the gray branch *is* reachable is `Grouped`, which passes the bare getter on purpose: [`DropdownItem`](../support/dropdown-item.md) defaults to `'gray'`, matching `dropdown/list/item.blade.php`, and its colour policy emits nothing for either `gray` or `null`, so the two render the same.

### The name-derived label is the name's segment before the first dot

`getLabel()` falls back to `u($name)->before('.')->kebab()->replace('-', ' ')->title()`, the port of Filament's actions-side chain, which takes the segment **before the first dot**.
`Field`'s label takes the segment after the last one (`afterLast('.')`), also as its Filament source does, so the two differ on purpose.

### Three of `HasParentActions`' accessors have no reader in either codebase

`shouldCancelParentActionsOnClose()`, `shouldCancelAllParentActionsOnClose()` and `getParentActionToCancelToOnClose()` are read nowhere in `packages/` or in `vendor/filament/`.
They are kept as Filament's declared API rather than trimmed, and the rest of the trait is ported member for member.

## Divergences

### The lifecycle events are dispatched as objects, where Filament dispatches a class-string

[`callBefore()`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php) dispatches `new ActionCalling($this)` before evaluating its closure, and `callAfter()` dispatches `new ActionCalled($this)` from a `finally`, so it fires even when the after-closure throws, in Filament's positions.
What crosses the wire is not Filament's.
Filament calls `Event::dispatch(ActionCalling::class, $this)`: Laravel's `parseEventAndPayload()` takes the object branch only when the *event* is an object, so a class-string event leaves the payload as `[$action]` and the event class is never constructed, and a listener registered on `ActionCalling::class` receives an `Action`.
Symfony's `EventDispatcherInterface::dispatch(object $event, ?string $eventName = null)` requires a real object, so the port constructs one, and its listeners receive what Filament's own class shape implies: [`ActionCalling`](../../../packages/actions/src/Events/ActionCalling.php) / [`ActionCalled`](../../../packages/actions/src/Events/ActionCalled.php), final readonly, one promoted `Action`, one `getAction()`.
Neither is stoppable or cancellable, as in Filament, and `halt()` / `cancel()` remain the control channel.
The dispatcher is a nullable `Symfony\Contracts\EventDispatcher\EventDispatcherInterface` on a `#[Required]` setter, the [`EvaluatesClosures` pattern](../support/component.md): an action built with `new` rather than through the container dispatches nothing, and the nullsafe call skips constructing the event at all.
Covered by [`HasActionsEventsTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsEventsTest.php).

The other four hooks (`beforeFormFilled`/`afterFormFilled`, `beforeFormValidated`/`afterFormValidated`) are Filament's, each a stored closure evaluated through `EvaluatesClosures` and a `null` no-op when unset; where the host fires them is [`callMountedAction()`](has-actions.md#callmountedaction-runs-filaments-pipeline-in-filaments-order)'s business.

### The selected-record injections are gated on the access flag

Filament's selected-record arms (`selectedRecords`/`records` and `selectedRecordsQuery`/`recordsQuery` by name, `Builder::class` by type) call the readers unconditionally, so *any* action whose closure names a `$records` parameter throws the `accessSelectedRecords()` `LogicException` instead of resolving.
That is tolerable in Filament, where `$records` is a bulk-action idiom; it is not here, because the same name reads naturally on a plain action that means *its own* collection of something.
So all of these arms are wrapped in `hasCanAccessSelectedRecords()` (the **presence** test, not the evaluated flag, so a closure-valued `accessSelectedRecords(fn () => …)` still counts) and fall through to `parent::resolveDefaultClosureDependencyForEvaluationBy*()` when it is false.
The parameter then resolves the ordinary way: an injection, the container, its own default, or `null`.

The gate is deliberately *not* `canAccessSelectedRecords()`: evaluating the flag to decide whether to resolve would run a user closure on every parameter of every action, and a flag that evaluates false would silently hand the parameter a container service rather than raising the gate the caller asked for.
Pinned by `ActionTest`'s fall-through cases.

### `$get`/`$set` resolve by type as well as by name

Filament binds the state utilities to closure **parameter names** only: `'schemaGet', 'get'` and `'schemaSet', 'set'` in [`Action`](../../../vendor/filament/actions/src/Action.php)'s by-name resolver, with nothing for `Get::class`/`Set::class` in its by-type one.
Its [schema `Component`](../../../vendor/filament/schemas/src/Components/Component.php), by contrast, resolves both by type.
So upstream `fn (Get $anything)` works inside a field closure and fails inside an action closure, purely because of the parameter's name.

The port keeps both by-name arms and **adds** `Get::class`/`Set::class` to the by-type resolver on `Action` (and `Get::class` on [`ActionGroup`](action-group.md), matching that class's narrower by-name surface: upstream gives the group `get` and no `set`, and the port does not invent one).
A closure parameter typed `Get` or `Set` resolves under any name, on an action exactly as on a component.

The reason is internal consistency: the port's own component tier accepts a typed `Get`, and having the same type-hint silently fail one tier up is a difference users hit as a bug rather than a design.
Nothing upstream depends on the omission, since this only widens what resolves, so every Filament-shaped closure keeps working unchanged.

The cost is that `getSchemaComponentOrFail()` fires for a `Get`/`Set`-typed parameter on an action rendered outside a schema, where without the arms that closure would die on an unresolved-parameter `RuntimeException`.
Both are exceptions at the same call, and the `LogicException` names the actual problem.
Reversible by deleting the two arms if upstream ever gives its own reason for the split.

### `Table::class` resolves by type as well as by name

Filament resolves the bound table only by the parameter name `table` (`Action.php:585`).
The port keeps that arm and adds `Table::class` to the by-type resolver, resolving [`getTable()`](belongs-to-table.md) the same way.
The reason is the port's [`Invoker`](../../../packages/support/src/Evaluation/Invoker.php), which falls back to the DI container for an unresolved class-typed parameter where Laravel's does not: a `fn (Table $table)` closure would otherwise be handed a **fresh, unconfigured** `Table` service instead of the action's own.
Explicit injections resolve before the container, so with the arm a table-less action answers `null`, pinned by `ActionTest`.
It costs nothing at the seam: a `match` over a `::class` constant compares compile-time strings and never autoloads, so the [guarded soft dependency](belongs-to-table.md#the-table-reference-is-a-guarded-soft-dependency-not-a-declared-one) on `sylament/tables` holds.

### The view switch renders through Twig templates, not toHtml() fast paths

Filament's five-way view switch is ported: `badge()`/`button()`/`grouped()`/`iconButton()`/`link()` each point `template()` at one of the five template constants (`BADGE_TEMPLATE`/`BUTTON_TEMPLATE`/`GROUPED_TEMPLATE`/`ICON_BUTTON_TEMPLATE`/`LINK_TEMPLATE`, the `*_VIEW` → `*_TEMPLATE` translation), with the `isBadge()`/`isButton()`/`isIconButton()`/`isLink()` predicates comparing `getTemplate()` against them (no `isGrouped()`, as Filament has none).
The default is `BUTTON_TEMPLATE` via `$defaultTemplate`, so `ActionGroup` can push `GROUPED_TEMPLATE` as a default while an explicit switcher call survives the push, Filament's precedence, pinned by `ActionTest`.
Each shape has its own template under [`partials/Action/`](../../../packages/actions/templates/partials/Action), and each is a thin composition over the support primitive Filament's corresponding `*_VIEW` constant points at: [`Badge`](../support/badge.md), [`Button`](../support/button.md), [`DropdownItem`](../support/dropdown-item.md), [`IconButton`](../support/icon-button.md) and [`Link`](../support/link.md), invoked as `<twig:>` tags.
Each builds its mount wiring with `stimulus_action('live', 'action', …)` and spreads it into the tag with `{{ ...map }}`, which is byte-equivalent to what `live_action()` emits inline.
`actions` owns no trigger CSS: the class list on every trigger root is the primitive's, plus the bare `sy-ac` marker the template contributes.
All five are exercised by [`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php), [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php) and [`ActionGroupRenderTest`](../../../packages/actions/tests/Integration/ActionGroupRenderTest.php).
`ActionGroup`'s [`GroupedTrigger`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig) composes the same `DropdownItem` primitive, and `->color()` reaches a grouped action and a grouped group, as [`toGroupedHtml()`](../../../vendor/filament/actions/src/Action.php) and [`toGroupedTriggerHtml()`](../../../vendor/filament/actions/src/ActionGroup.php) pass it.

What stays divergent:

- **No string fast paths:** Filament's `toHtml()` dispatch, the `toBadgeHtml()`/`toButtonHtml()`/… renderers of the `CanGenerate*Html` traits, their `toOptimizedLinkHtml()`/`toOptimizedGroupedHtml()` hand-built variants with the `has*()` probes that guard them, and the `toEmbeddedHtml()` fallback have no counterpart; every shape renders through Twig via `ViewComponent::render()`.
- **`badge()` is argless:** Filament's `badge(string|Closure|null $badge = null)` doubles as the badge-*text* setter via `func_num_args()`.
  `Action` does not compose support's [`HasBadge`](../../../packages/support/src/Components/Concerns/HasBadge.php) and `HasBadgeTooltip`, so the overload widens when it does.
- **No `renderModal()`/`toModalHtmlable()`:** the confirmation modal ([`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)) is rendered host-side, driven by [`CanOpenModal`](can-open-modal.md)'s getters, not by an `Action` method.

### The serialized enum values flatten to scalars, and a `ScalableIcon` crosses unflattened

Filament's `toArray()` writes whatever its getters return, objects included: `getIconPosition(): IconPosition` is non-nullable, so `iconPosition` is *always* an enum instance in the payload, and `iconSize` / `size` are `IconSize` / `Size` instances whenever the setter was handed one.
[`Action::toArray()`](../../../packages/actions/src/Action.php) writes `->value` for all three: `iconPosition` unconditionally (no `instanceof` check, because there is nothing else it can be), `iconSize` and `size` only when the value is an enum, because those two getters return a union and a plain string passes straight through.
Those three keys are scalar, which is what lets them cross a session or a JSON hop without depending on how PHP serializes enums; the payload as a whole is not, because `color` carries a shade map, `eventData` and `extraAttributes` carry arrays, `label` and `tooltip` carry `Markup`, and `icon` carries a `ScalableIcon` unflattened.

Nothing is lost on the way back.
`iconPosition` re-resolves exactly: `fromArray()` hands `'before'` to `iconPosition()` and the getter runs it through `IconPosition::tryFrom()`, returning the same case.
`iconSize` and `size` restore as the **string** rather than the enum, and every consumer takes it: [`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php) calls `IconSize::tryFrom()` on a string size, and the support primitives' `size` props are strings, which is what the trigger templates pass literally as `component.getSize ?? 'md'`.
So the divergence is one of wire format, not of what renders.

`icon` diverges the other way, and for exactly the reason [notifications' does](../notifications/notification.md#a-scalableicon-crosses-the-session-unflattened-and-resolves-at-render-size).
Filament resolves a scalable icon *at serialization time*: `$icon instanceof ScalableIcon` takes `getIconForSize($this->getIconSize() ?? IconSize::Medium)`, a size chosen before anyone knows how the action will render, and only a plain `BackedEnum` falls through to `->value`.
The port inverts those two arms: a `BackedEnum` implementing [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) is kept **as the enum** (the session PHP-serializes enums, and `fromArray()` admits a `BackedEnum` back through `icon()`), and only a plain enum flattens to its value.
Resolution then happens where every other icon resolves, in `generateIconHtml()`'s `ScalableIcon` → `BackedEnum` cascade, at the size the trigger template passes, so a scalable icon is size-correct on the serialized path where Filament's is pinned to whatever `iconSize()` said at `toArray()` time.
Pinned by the `ScalableFixtureIcon` case in [`ActionSerializationTest`](../../../packages/actions/tests/Unit/ActionSerializationTest.php).

### The live-component chain narrows inline instead of through a second getter

Filament's `getLivewire()` is a multi-step resolver (explicit `$livewire`, then `getSchemaContainer()?->getLivewire()`, `getSchemaComponent()?->getLivewire()`, `getTable()?->getLivewire()`, `getGroup()?->getLivewire()`), plus a second method, `getHasActionsLivewire()`, narrowing the generic result to `Contracts\HasActions`.
Sylament's [`getLiveComponentOrNull()`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) walks the same hops in Filament's order, each narrowed inline by a private `asActionsHost()` guard rather than by a second method, so a host implementing only schemas' `InteractsWithSchemas` never satisfies the chain.
The two schema hops read through the schemas side's Sylament-only `getLiveComponentOrNull()` peeks (schemas' primary getters throw, see [`schemas/belongs-to-live-component.md`](../schemas/belongs-to-live-component.md)); the table hop reads [`Table::getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php), which is non-nullable there because `Table::make()` always binds one, and is narrowed by the same guard, so a table host that interacts with tables but not with actions resolves nothing.

`getLiveComponent()` keeps a non-nullable `InteractsWithActions` return and throws when nothing resolves, the fail-fast stand-in for Filament's nullable return plus separate narrowing step.
The chain itself lives in the Sylament-only `getLiveComponentOrNull()` peek (named after the schemas-side precedent), which `prepareModalAction()`/`prepareModalActionGroup()` use to bind footer actions without forcing an unbound owner to throw.

The group hop is kept even though [`ActionGroup::liveComponent()`](../../../packages/actions/src/ActionGroup.php) eagerly propagates the host to child actions at set time: the eager push only fires at `liveComponent()`-call time, so an action added to an already-bound group afterwards resolves through this lazy hop and nothing else.
Covered by [`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php), and the table hop by [`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php), which lives in the tables suite because only there can a real `Table` be built.

### `getContext()` drops the record model gate

Filament's `getContext()` assembles up to four entries: `recordKey` (via `resolveRecordKey()`, guarded by table-model type checks), an early return carrying only `recordKey` for child modal actions (`getParentAction()`), `table`/`bulk` flags, and `schemaComponent`.
The port's [`getContext()`](../../../packages/actions/src/Action.php) emits them in the same order: `recordKey`, then (past the child-frame return) `table` and `bulk`, then `schemaComponent`, the key of the bound schema container falling back to the bound schema component.

One entry diverges.
The **model gate** around `recordKey` is dropped: Filament only emits a key when the record has no table, is not an Eloquent `Model`, the table declares no model, or the record's class matches it, a guard against a table whose rows are a different model than the action's own record.
Sylament has no model class to compare, so the key is emitted whenever *something* yields one, but **which** something depends on the table.

When the action has a table and the record is an instance of the table's [record class](../tables/has-query.md#the-record-class-is-read-off-the-base-querys-root-entity), `getContext()` asks the table: `Table::getRecordKey()`, the host's own read, which goes through [`RecordKey::of()`](../schemas/record-key.md) on the query lane.
Only otherwise does it fall back to [`resolveRecordKey()`](interacts-with-record.md#record-key-resolution-is-hoisted-to-hasactions)'s literal `id`.
That is the gap [has-table.md](../tables/has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) names, closed the way it proposes: the actions layer is handed the table's key rather than taught Doctrine, so a row action on a `Book`, a `Shortcut` or a `Residency` mounts **with** its record.
An action with no table, or over a record that is not the table's entity (a `SELECT NEW` projection, a DTO), reads `id`, and only where **one scalar field** is the record's whole identity, which is the shape a single property read can name.
A private `isRecordKeyedByOneScalarField()` asks `ClassMetadata` for the identifier and answers true where it holds one field and that field is not an association, whatever the field is called, so a record keyed by a `sku` or a slug mints its key.
For a **composite** identifier and for an **association** one, `getContext()` mints no `recordKey` at all, so the action's declared record stands.
What the gate prevents is a key that names no record: the partial `"5"` of an `id`-plus-`locale` identifier, which a host that decodes strictly refuses as a 404 on click, and the stray non-identifier `id` of a composite entity, which is a 500.
It lives on `Action` rather than in the trait because `ActionGroup` composes `InteractsWithRecord` **without** the manager registry the question needs, and it keeps the literal reading wherever no mapping answers: an anonymous class, which Doctrine refuses to describe at all, and a class no manager maps, which is also the arm an action built outside the container takes, holding no registry to ask.
In each of those, the literal reading is the only identity there is; an **array** record never reaches the gate at all, the `is_object()` test in front of it leaving `ArrayRecord`'s `__key` entry to the literal read.

One shape the gate deliberately does not catch, because the mapping cannot tell it apart from the ordinary one: a record keyed by one scalar field whose readable `id` is something else, such as a legacy serial beside a natural key, mints that `id`.
On a panels page the resource then resolves it by the identifier and answers a 404.
That is the [`$recordRouteKeyName`](../panels/resource.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it) family's mismatch, closed only by a host hook that lets a page mint the key itself, which nothing provides.

The table read can **throw** where `resolveRecordKey()` would omit `recordKey`: a record of the table's own class whose identifier is incomplete (an explicitly set, not yet persisted entity) makes `RecordKey::of()` refuse.
That is deliberate, and it is the same record the table could never resolve on the way back; `getRecord(withDefault: false)` keeps a header `CreateAction` out of that path, and a row is always keyable, so no shipped path reaches that throw.

The record it resolves the key from is read with [`withDefault: false`](#the-record-chain-reaches-the-schema-the-component-and-the-host), so a record the *host* supplied as a default never becomes a `recordKey` on the wire: the host answers it again on the way back, and sending it would only hand the host its own state.
The **`bulk` flag** is Filament's, in Filament's position and with Filament's double table test: a bulk action with no table emits neither key, which routes it to the record-action registry rather than the bulk one ([bulk-action.md](bulk-action.md)).

The **child-frame short-circuit** is Filament's: `if ($this->parentAction instanceof self) { return $context; }` runs after the `recordKey` branch and before the table and schema branches, so a modal action declared by another action carries its record identity up and nothing else, neither the schema binding it inherited from `prepareModalAction()` nor the table binding it inherited from its parent.
Without it, a nested frame would carry a `schemaComponent` key up to the host and, but for the host's own [deeper-frame gate](has-actions.md#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache), try to resolve against the host's schema instead of its parent's declared modal actions.
`parentAction()` is stamped by [`prepareModalAction()`](can-open-modal.md) on every action an action declares, and `nestingIndex()` by `resolveActions()` per frame.
Covered by `ActionTest`'s context cases and by [`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php) for the arms that need a real `Table`: the composite key, the `table` and `bulk` flags and their suppression on a nested frame.

### CanBeDisabled drops the authorization short-circuits

Filament's `isDisabled()` falls through `isHidden()` into three more branches, `hasAuthorizationNotification()`, `hasAuthorizationTooltip()` and `isAuthorized()`, which let an unauthorized-but-visible action stay enabled so it can show a tooltip or fire a notification on click instead of vanishing or graying out.
Sylament's port stops at `isHidden()`: a component is disabled when `$isDisabled` is truthy or the component is hidden.
The toolkit has no authorization-tooltip or authorization-notification concept.
Reverses once those two concerns, and the `isAuthorized()` contract they lean on, are ported and wired onto `Action`.

### HasGroupedIcon narrows its icon type to string

Filament types the grouped icon `string|BackedEnum|Htmlable|Closure|null`; the port narrows it to `string|Closure|null`, the same narrowing `CanOpenModal`'s `modalIcon` applies (support's `HasIcon` keeps the wide type, so `getIcon(default:)` accepts the narrowed value unchanged).
Consumed where Filament consumes it: [`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig) resolves its icon as `component.getIcon(component.getGroupedIcon)`, Filament's `icon: $this->getIcon(default: $this->getGroupedIcon())`, so the grouped icon only surfaces inside a dropdown item, never on the button shape.
Widen the type in lockstep with an enum or `Markup` grouped icon if the actions package needs one.

### Label translation and the deprecated `disableLabel()` alias are not ported

Filament's `HasLabel` carries `$shouldTranslateLabel` + `translateLabel()` and runs a string `getLabel()` result through Laravel's `__()` translator, plus a `@deprecated disableLabel()` alias whose replacement Filament names as `hiddenLabel()`.
Sylament drops all three: no translation layer is wired into labels (the same call schemas' `HasLabel` makes, see [`schemas/has-label.md`](../schemas/has-label.md#label-translation-and-the-deprecated-disablelabel-are-not-ported)), and a fresh codebase has no reason to ship the deprecated alias when `hiddenLabel()` exists.
Reverses for translation if a translation layer lands; the deprecated alias stays out regardless.

### Action invocation is a PHP-side seam only

`HasMountableArguments` is Filament's: `$action(['id' => 5])` clones the action, binds the arguments through `arguments()`, and records them as `invokedArguments`, so a per-render binding never mutates a host-cached instance.
All five trigger templates serialize **only** `getInvokedArguments()` into the mount emission, as Filament's `getJsClickHandler()` does, and emit no `arguments` param when the action was never invoked.
The divergence is the call site: Blade is PHP, so Filament templates can invoke `($action)(['id' => 5])` inline; Twig has no call syntax for invokable objects, so the invocation seams are PHP-side configuration and the `renderAction()` Twig helper's argument binding (`renderAction(name, arguments)`, the Twig-facing wrapper for `__invoke`).
Permanent: a Twig-side call syntax would require a helper function anyway, which `renderAction()` already is.

### `getName()` tightens its return type to non-nullable `string`

Filament types `getName(): ?string` even though the method throws `LogicException` before returning when the name is `blank()`, so the nullable signature never surfaces a `null`.
The port's [`getName()`](../../../packages/actions/src/Concerns/HasName.php) declares `: string`, matching the trait's real behaviour rather than Filament's vestigial nullability.
A type-signature tightening with no behavioural change (both throw on a blank name); reverse by widening back to `?string` if a caller needs to distinguish "no name" from "threw" without a try/catch.

### HasSize drops the optimized-render fast-path helper

Filament's `HasSize` carries `hasSmallSizeDefault(): bool` (`$this->size === null && $this->defaultSize === Size::Small`), read only by `Action::canRenderOptimizedLink()` and `canRenderOptimizedGrouped()`, the guards of a Blade-bypass fast path that hand-builds link and grouped-item HTML when the action carries no non-default styling.
The port has [no string fast paths](#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths), so the helper would have no reader; the rest of the trait (`size()`, `defaultSize()`, `getSize()`, `getDefaultSize()`, `hasSize()`) is Filament's and covered by [`HasSizeTest`](../../../packages/actions/tests/Unit/Concerns/HasSizeTest.php).

### `redirect()` records a pending URL instead of redirecting the host

Filament's `redirect(string|Closure $url, ?bool $navigate = null)` calls the Livewire component's `redirect()`, with SPA-mode navigation read from the `FilamentView` facade.
The port's [`redirect(string|Closure $url)`](../../../packages/actions/src/Concerns/CanRedirect.php) evaluates the URL into a pending URL that `getPendingRedirectUrl()` exposes and `resetPendingRedirect()` clears, because [a redirect leaves the pipeline as a returned response](has-actions.md#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag); `$navigate` goes with the SPA facade.
`dispatchSuccessRedirect()` and `dispatchFailureRedirect()` resolve the action's own URL first and the host's [default-URL hooks](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred) second, through the nullable `getLiveComponentOrNull()` where Filament dereferences `getHasActionsLivewire()` unguarded, so a detached action dispatches nothing rather than failing.
No `instanceof` re-check is written, because the accessor already returns the contract that carries both hooks, and one would be provably always true and fail level max.
Covered by [`CanRedirectTest`](../../../packages/actions/tests/Unit/Concerns/CanRedirectTest.php) and, end to end, by [`HasActionsRedirectTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsRedirectTest.php).

### The deprecated `hold()` and `withAttributes()` aliases are not ported

Filament marks `hold()` `@deprecated` in favour of `halt()`, and `withAttributes()` in favour of `extraAttributes()`.
The port ships both replacements and neither alias, as it does for [`disableLabel()`](#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported).
