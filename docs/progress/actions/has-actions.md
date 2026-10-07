---
description: The host-side trait a UX LiveComponent page composes to mount, resolve, call and unmount a stack of Actions.
ports:
  - vendor/filament/actions/src/Concerns/InteractsWithActions.php
owns:
  - packages/actions/src/Concerns/HasActions.php
---
# HasActions (actions)

`Sylament\Actions\Concerns\HasActions` is the trait behind the [`InteractsWithActions`](interacts-with-actions.md) contract, and the [swapped](../renames.md#interface-and-trait-names-are-swapped) counterpart of Filament's `Concerns\InteractsWithActions`.
The stack's rendering, its per-frame modal ids and its open state are in [modals.md](modals.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`resolveRecordUsing()`](../../../packages/actions/src/Concerns/HasActions.php) / `getMountedActionRecord()` / `resolveMountedActionRecord()` | — | done | — | [diverges](#record-resolution-is-a-single-host-callback-not-per-source) · [diverges](#a-root-frames-record-key-is-resolved-when-the-action-resolves-and-only-there) |
| [`$mountedActions`](../../../packages/actions/src/Concerns/HasActions.php) stack + `dehydrateMountedActions()` | `Concerns/InteractsWithActions.php` `$mountedActions` | done | — | [diverges](#the-mounted-action-stack-is-a-server-controlled-liveprop-not-a-client-writable-array) · [diverges](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method) · [semantics](#the-mounted-action-descriptor-is-typed-string-keyed-and-the-client-fills-two-thirds-of-it) |
| [`mountAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `mountAction()` | in progress | — | [detail](#mountaction-drops-authorization) · [detail](#modal-less-frames-stay-on-the-stack-where-filament-unmounts-them) · [diverges](#the-error-bag-reset-is-scoped-to-one-frame-not-a-global-reset) |
| [`callMountedAction(array $arguments = [])`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `callMountedAction()` | in progress | — | [detail](#callmountedaction-lacks-rate-limiting-authorization-and-the-exposesstatetoactiondata-merge) · [semantics](#callmountedaction-runs-filaments-pipeline-in-filaments-order) · [diverges](#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag) · [diverges](#every-mounted-action-run-flushes-a-table-hosts-records-cache) · [semantics](#clearrecordafter-runs-at-both-of-filaments-sites) |
| [`unmountAction(bool\|string\|null $cancelParentActions = null)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `unmountAction()` | in progress | — | [detail](#unmountaction-drops-the-default-action-reset) · [detail](#modal-less-frames-stay-on-the-stack-where-filament-unmounts-them) · [diverges](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events) · [diverges](#the-error-bag-reset-is-scoped-to-one-frame-not-a-global-reset) · [semantics](#clearrecordafter-runs-at-both-of-filaments-sites) |
| `unmountActionsWithoutModals()` | `Concerns/InteractsWithActions.php` `unmountActionsWithoutModals()` | todo | — | [detail](#modal-less-frames-stay-on-the-stack-where-filament-unmounts-them) |
| [`replaceMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `replaceMountedAction()` | done | — | [diverges](modals.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper) |
| [`getMountedAction(?int $actionNestingIndex = null)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getMountedAction()` | done | — | [diverges](#getmountedaction-never-guards-its-own-read-with-a-catch) |
| [`getMountedActions()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getMountedActions()` | done | — | [diverges](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method) |
| Resolved-frame cache ([`$cachedMountedActions`](../../../packages/actions/src/Concerns/HasActions.php), `cacheMountedActions()`) | `Concerns/InteractsWithActions.php` `$cachedMountedActions` / `truncateCachedMountedActions()` | done | — | — |
| `isActionMounted()` | `Concerns/InteractsWithActions.php` `isActionMounted()` | todo | — | — |
| The stale-stack self-heal ([`afterMountedActionsHydrated()`](../../../packages/actions/src/Concerns/HasActions.php)) | `Concerns/InteractsWithActions.php` `bootedInteractsWithActions()` | done | — | [diverges](#the-stale-stack-self-heal-is-a-posthydrate-hook-not-a-boot-hook) |
| Per-frame schema re-cache at hydrate | `Concerns/InteractsWithActions.php` `resolveActions()` | done | — | [diverges](#the-mounted-action-schema-re-cache-runs-above-callafterschemastateupdated-by-explicit-posthydrate-priorities) · [semantics](#a-mounted-modals-validation-errors-survive-an-unrelated-live-update) |
| [`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getAction()` | in progress | — | [detail](#getaction-ports-the-generic-method-discovery-arm-in-full) |
| [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveActions()` | done | — | [diverges](#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache) · [diverges](#no-resolved-action-is-booted) · [diverges](#resolveactions-takes-no-ismounting-flag) · [diverges](#the-table-branch-reaches-tables-through-a-guarded-soft-reference) · [diverges](#the-mounted-action-schema-re-cache-runs-above-callafterschemastateupdated-by-explicit-posthydrate-priorities) |
| [`resolveAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveAction()` | done | — | [diverges](#a-root-frames-record-key-is-resolved-when-the-action-resolves-and-only-there) |
| [`resolveTableAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveTableAction()` | done | — | [semantics](#a-client-supplied-record-key-is-type-checked-before-the-lookup) · [diverges](#the-table-branch-reaches-tables-through-a-guarded-soft-reference) · [diverges](#a-table-context-on-a-non-table-host-is-a-wiring-bug-not-a-miss) |
| [`resolveSchemaComponentAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveSchemaComponentAction()` | done | — | [diverges](#schema-context-resolution-returns-null-instead-of-throwing) |
| [`cacheAction()`](../../../packages/actions/src/Concerns/HasActions.php) + `$cachedActions` | `Concerns/InteractsWithActions.php` `cacheAction()` | done | — | — |
| `mergeCachedActions()` / `cacheTraitActions()` | `Concerns/InteractsWithActions.php` `mergeCachedActions()` / `cacheTraitActions()` | skipped | — | [diverges](#only-the-per-name-action-cache-is-ported) |
| [Action-schema integration](../../../packages/actions/src/Concerns/HasActions.php) (`getMountedActionSchema()`, `mountedActionHasSchema()`, `getMountedActionSchemaRecord()`, `forgetMountedActionSchema()`) | `Concerns/InteractsWithActions.php` (`getMountedActionSchemaModel()`, `forgetCachedMountedActionSchemas()`) | done | — | [has-schema.md](has-schema.md) · [semantics](#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation) · [diverges](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor) · [diverges](#the-has-schema-check-is-memoised-separately-because-the-schema-cache-cannot-hold-null) · [diverges](#a-schema-bearing-action-on-a-host-without-schemas-throws) · [diverges](#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema) |
| `getMountedActionSchemaName()` | `Concerns/InteractsWithActions.php` `getMountedActionSchemaName()` | skipped | — | [interacts-with-actions.md](interacts-with-actions.md#getmountedactionschemaname-is-not-declared-because-the-name-is-built-inline) |
| `mountedActionHasForm()` / `getMountedActionForm()` (deprecated) | `Concerns/InteractsWithActions.php` `mountedActionHasForm()` / `getMountedActionForm()` | skipped | — | [diverges](#the-deprecated-mountedactionhasform-and-getmountedactionform-aliases-are-not-ported) |
| [`$mountedActionsData`](../../../packages/actions/src/Concerns/HasActions.php) + `getMountedActionData()` | `Concerns/InteractsWithActions.php` `mountedActions.{i}.data` | done | — | [diverges](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor) |
| `#[Url]`-bound default-action state (`defaultAction*`, `defaultTableAction*`, `getDefaultActionUrlContext()`, `getDefaultTableActionUrlContext()`) | `Concerns/InteractsWithActions.php` public `#[Url]` properties | todo | — | [detail](#url-bound-default-action-state-is-not-ported) |
| [`getDefaultActionUrl()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getDefaultActionUrl()` | done | — | — |
| [`getDefaultActionSuccessRedirectUrl()`](../../../packages/actions/src/Concerns/HasActions.php) / [`getDefaultActionFailureRedirectUrl()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getDefaultActionSuccessRedirectUrl()` / `getDefaultActionFailureRedirectUrl()` | done | — | — |
| [`getDefaultActionSchemaResolver()`](../../../packages/actions/src/Concerns/HasActions.php) / [`getDefaultActionRecord()`](../../../packages/actions/src/Concerns/HasActions.php) / [`getDefaultActionRecordTitle()`](../../../packages/actions/src/Concerns/HasActions.php) / [`getDefaultActionRecordClass()`](../../../packages/actions/src/Concerns/HasActions.php) / [`getDefaultActionRecordLabel()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getDefaultActionSchemaResolver()` / `getDefaultActionRecord()` / `getDefaultActionRecordTitle()` / `getDefaultActionModel()` / `getDefaultActionModelLabel()` | done | — | [semantics](#getdefaultactionrecord-answers-a-record-never-a-bare-key) |
| [`getDefaultActionRelationship()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getDefaultActionRelationship()` | done | — | — |
| `getDefaultActionAuthorizationResponse()` / `getDefaultActionIndividualRecordAuthorizationResponseResolver()` | `Concerns/InteractsWithActions.php` (same names) | blocked | `CanBeAuthorized` | [detail](#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred) |
| `forceRender()` / `partiallyRenderActionParentSchema()` | `Concerns/InteractsWithActions.php` `forceRender()` / `partiallyRenderActionParentSchema()` | blocked | schemas partial-render infrastructure (`PartialsComponentHook`) | [detail](#partial-re-render-is-not-ported) |
| [`afterActionCalled()`](../../../packages/actions/src/Concerns/HasActions.php) hook | `Concerns/InteractsWithActions.php` `afterActionCalled()` | done | — | — |
| `bootedInteractsWithActions()` boot wiring / `WithRateLimiting` | `Concerns/InteractsWithActions.php` `bootedInteractsWithActions()`, `DanHarrin\LivewireRateLimiting\WithRateLimiting` | skipped | — | [diverges](#there-is-no-boot-hook-or-rate-limiting-trait) |
| `getOriginallyMountedActionIndex()` + `$originallyMountedActionIndex` | `Concerns/InteractsWithActions.php` `getOriginallyMountedActionIndex()` | skipped | — | [diverges](#originallymountedactionindex-and-getoriginallymountedactionindex-are-not-ported) |
| `syncActionModals()` | `Concerns/InteractsWithActions.php` `syncActionModals()` | skipped | — | [modals.md](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events) |

## Details

### `mountAction()` drops authorization

Filament's `mountAction()` checks `hasAuthorizationNotification()` / `getAuthorizationResponseWithMessage()` before the mount lifecycle, sending an unauthorized notification and a `Cancel` on denial, and validates the child schemas of an `ExposesStateToActionData` schema component first.
The port has neither: `CanBeAuthorized` is not composed onto `Action`, and no `ExposesStateToActionData` interface exists.
Two later arms are absent as well: Filament stores `hasUnsavedChangesAlert()` on the descriptor (the [unsaved-changes row](can-open-modal.md#surface)), and refuses to auto-run a modal-less action mounted with `mountedFromUrl` (the [default-action state](#url-bound-default-action-state-is-not-ported)).
Everything else runs in Filament's order: push, resolve, unmount the pushed frame with `cancelParentActions: false` on a miss or a disabled action, `callBeforeFormFilled()` → `mount(['form' => $schema, 'schema' => $schema])` → `callAfterFormFilled()`, and the modal-open gate.
The port also seeds the frame's [data bag slot](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor) before anything can render.

### `callMountedAction()` lacks rate limiting, authorization and the `ExposesStateToActionData` merge

Filament's `callMountedAction()` returns early when `hasAuthorizationNotification()` and `isAuthorized()` deny, applies `getRateLimit()` through `WithRateLimiting::rateLimit()` (a rate-limited notification on `TooManyRequestsException`), and merges the state of an `ExposesStateToActionData` component's child schemas into the action's data.
None of the three is in the port: neither `CanBeRateLimited` nor `CanBeAuthorized` is composed onto `Action`, and with no `ExposesStateToActionData` interface the data is the schema's own state.
Two of Filament's statements differ as well, with no recorded reason: Filament's successful run ends with `$action->resetData()` after restoring the arguments (`InteractsWithActions.php:411-412`), which the port omits, and its modal-less `ValidationException` path unmounts with `cancelParentActions: false` (`:389`), where the port's `unmountAction()` call (`HasActions.php:267`) consults the action's cancel cascade.
Filament's `mergeMountedActionArguments()`, which writes merged arguments back into the persisted descriptor, is [tracked on the contract](interacts-with-actions.md#schema-mount-plumbing-is-not-on-the-contract); this method's transient merge-and-restore is not it.

### `unmountAction()` drops the default-action reset

The cancel cascade is Filament's: `false` pops one frame, `true` clears the stack, `null` consults the mounted action's `shouldCancelAllParentActions()` and then `getParentActionToCancelTo()`, a string pops until that named parent has itself been popped, and a `null` action takes the pop-one branch.
What is absent is the full-empty reset of `defaultAction` / `defaultActionArguments` / `defaultActionContext` to `[]`, because those `#[Url]` properties are [not ported](#url-bound-default-action-state-is-not-ported); it lands with them.
`syncActionModals()` has no port by design: see [declarative choreography](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events).

### Modal-less frames stay on the stack where Filament unmounts them

Filament unmounts a frame that has no modal to be seen in, at two places.
After auto-running a modal-less action, `mountAction()` unmounts it when it is still the mounted action, which is what a `halt()` leaves behind (`InteractsWithActions.php:230-237`); the port returns `callMountedAction()`'s result and leaves the halted frame standing (`HasActions.php:171-173`).
And a top-level `unmountAction()` calls `unmountActionsWithoutModals()` (`:864-868`), popping every exposed top frame whose `mountedActionShouldOpenModal()` is false; the port has neither the method nor the `$mountActionNestingLevel` counter that gates it.
`HasActionsTest`'s `keeps a parent open when a non-modal child halts on top of it` pins the port's behaviour, a halted non-modal child staying mounted under an open parent, which Filament's `mountAction()` would unmount.

### `getAction()` ports the generic method-discovery arm in full

The port's [`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php) carries Filament's generic arm: the `$cachedActions` lookup first, then `"{$name}Action"` preferred over the bare `"{$name}"` method (the suffixed form is skipped when the name already ends in `Action`), then a `ReflectionMethod` return-type gate that refuses to invoke any method whose declared return type does not include `Action`, and an [`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php) when a gate-passing factory returns a non-`Action` at runtime.
An unknown name returns `null`.
The reflection-before-invoke order is load-bearing for security: `mountAction()`'s `$name` is a client-writable `#[LiveArg]`, and the return-type gate is what keeps `getAction($clientName)` from invoking arbitrary host methods.
`resolveActions()` carries Filament's blank-name guard, throwing the same exception.
Absent: Filament's `string | array $actions` descriptor-list form and its `$isMounting` flag; the nesting lives on [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php), whose loop calls this method once per root frame, and the table-scoped and schema-scoped branches sit there too.
The descriptor-list form lands when a caller wants it.

### `#[Url]`-bound default-action state is not ported

Filament exposes `defaultAction`, `defaultActionArguments`, `defaultActionContext`, `defaultTableAction`, `defaultTableActionRecord` and `defaultTableActionArguments` as `#[Url(as: …)]`-bound public properties, so an action can be pre-mounted from the query string on page load, with `getDefaultActionUrlContext()` / `getDefaultTableActionUrlContext()` stamping `mountedFromUrl` on the context (and `unmountAction()` resets them to `[]` rather than `null` to avoid Livewire's `'null'`-string URL bug).
None of these properties, nor any code that reads them to auto-mount an action, exists in the port.

The mechanism is not the blocker.
UX LiveComponent's `#[LiveProp(url: …)]`, reached either statically or through a [per-prop `modifier:`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) where the parameter name has to be computed, is Livewire's `#[Url]` equivalent.

What is not built is everything the binding would feed.
Filament's auto-mounting calls `mountAction()` from those properties before the first render; the port has no equivalent moment, and the design questions behind it are its own: a mounted action's schema has to be filled and validated on a **GET** rather than on a live action, the six parameters are a client-supplied action name plus arguments plus a record key that all need the untrusted-edge treatment a live mount gets, and a modal opened by a URL has to survive a refresh without re-firing its side effects.
The four `defaultTableAction*` members land with it or not at all: they exist so a *table* row action can be deep-linked, which is the same machinery one context over.

### The `getDefaultAction*` host override hooks are ported, the authorization pair deferred

Filament's `getDefaultAction*(Action $action)` methods all return `null` by default: extension points a host page overrides to supply context-free defaults to any action it mounts.
Each lands by one recipe: declare it on [the contract](interacts-with-actions.md), default it to `return null;` here, and ship it with the `Action`-side concern that reads it, so every host that composes this trait satisfies the wider interface with no edit.
The readers: [`CreateAction`](create-action.md) / [`EditAction`](edit-action.md) reach their resource page through `getDefaultActionUrl()`; [`CanRedirect`](action.md) reads the redirect pair; the [record chain on `Action`](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host) walks to `getDefaultActionRecord()`, `getDefaultActionRecordTitle()`, `getDefaultActionRecordClass()` and `getDefaultActionRecordLabel()`; [`HasSchema::getSchema()`](has-schema.md) reads `getDefaultActionSchemaResolver()`; and [`CreateAction::getRelationship()`](create-action.md#the-created-record-joins-the-relationship-in-the-same-flush) ends at `getDefaultActionRelationship()`.
Two are renames, `getDefaultActionModel` and `getDefaultActionModelLabel`, under the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record).
A host answering any of them [must discriminate by action](interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action).

The authorization pair, `getDefaultActionAuthorizationResponse()` and `getDefaultActionIndividualRecordAuthorizationResponseResolver()`, has no reader, because `CanBeAuthorized` is not ported; each lands with it.

### Partial re-render is not ported

Filament's `forceRender()` (`app(PartialsComponentHook::class)->forceRender($this)`) and `partiallyRenderActionParentSchema()` walk up an action's schema and component ancestry to partially render just the affected schema after a successful `callMountedAction()`.
Sylament has no `PartialsComponentHook` equivalent, so every action call re-renders the way a LiveComponent normally does.
It waits on schemas building a partial-render mechanism, and is low priority until render cost becomes a concern.

## Semantics

### `callMountedAction()` runs Filament's pipeline in Filament's order

The port merges the caller's `#[LiveArg]` `$arguments` into the top action and restores the originals afterwards, binds the [host-resolved record](#a-root-frames-record-key-is-resolved-when-the-action-resolves-and-only-there), bails on `isDisabled()`, then opens the database transaction and runs, inside it: the schema arm, `call()`, `callAfter()`, `afterActionCalled()`, and the success or failure notification followed by the matching redirect, so the flashed notification survives the navigation.
`Halt` keeps the action mounted, `Cancel` falls through to the unmount, and after the commit the stack it started with is compared against the current one, by names and contexts only: a `replaceMountedAction()` mid-call returns without unmounting.
The [redirect read](#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag) sits above that comparison, Filament's relative order, so an action that replaced the stack and asked for a redirect still navigates.

The schema arm, guarded by `mountedActionHasSchema()`, calls `callBeforeFormValidated()`, `$schema->getState()`, then `callAfterFormValidated()`, `data($state)` and `callBefore()`, so nothing downstream of validation runs when the state does not validate; a schema-less action calls `callBefore()` alone.
Filament runs those three inside [`getState(afterValidate: …)`](../schemas/schema.md), which is behaviour-identical because the callback is `getState()`'s last statement before its `return`.
The port writes the state into a local because PHPStan reads no PHPDoc above a closure, so a closure parameter can carry no key type, and `Action::data()` takes `array<string, mixed>`.
`call()` receives the mounted schema as both `form` and `schema`, so an `action()` closure can ask for `Schema $schema` as a `mount()` closure can; a schema-less action is [called with no arguments](#a-schema-less-action-is-called-with-no-arguments-not-with-a-pair-of-nulls).

A [`ValidationException`](../schemas/can-be-validated.md) rolls back and propagates when the action has a modal to keep open, since the stack, the data bag and the error bag are already correct and the re-render is the retry; only the modal-less case first restores the arguments, calls `resetData()` and unmounts, because no dialog is left to hold the messages.

The transaction is Filament's ordering verbatim: `beginDatabaseTransaction()` is the first statement inside the `try`, so validation, every hook, the body, the notification and the redirect resolution all run inside it.
`Halt` and `Cancel` commit or roll back per the flag their exception carries ([the pair](../support/cancel.md)), `Cancel` marking the transaction finalized so the tail does not commit twice, and `ValidationException` and any other `Throwable` roll back and rethrow.
That rollback is unguarded, as in Filament: a `beginDatabaseTransaction()` that itself threw (an unreachable connection, a registry whose manager is not an ORM one) is followed by a `rollBackDatabaseTransaction()` that throws in turn, replacing the original cause.
A `hasBegun` flag would preserve the first exception, but it would invent state Filament does not carry on a path that is already an infrastructure failure.

### The per-mount schema is seeded with the action's record and the dotted operation

`getMountedActionSchema()` hands the fresh container a `record()` and an `operation()` beside its key, state path and heading level, Filament's own seed under the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record).
Both matter to a modal that maps onto a record: the record is what [`Schema::getStateFromRecord()`](../schemas/belongs-to-record.md) fills from and what `getData()` writes back to, and the operation is what `visibleOn('create')` and its `hiddenOn()` twin match against.

The record is `resolveMountedActionSchemaRecord()`, Filament's chain: `$action->getRecord()`, then `$action->getRecordClass()` (a class-string binds the *class* without an instance, which is what a create modal wants), then the owning schema component's record or class, then the host's own `getMountedActionSchemaRecord()`.
The two action-side links take `withDefault: false` whenever the action **is** owned by a schema component, so a `Select`'s option action or a repeater item action never reaches past its own component to the host's default record; a host-level action keeps the default.
Filament's third link is the component's `getActionSchemaModel()`, which schemas' component `HasActions` does not port ([select.md](../forms/select.md#the-non-relationship-schema-record-is-read-off-the-field-not-off-a-getactionschemamodel)), so the chain reads `getRecord() ?? getRecordClass()` off the component instead.
One link is narrowed: `Action::getRecord()` is `mixed` here, because a host's [`resolveRecordUsing()`](#record-resolution-is-a-single-host-callback-not-per-source) may answer a bare key, and a scalar therefore binds nothing rather than reaching `record()`, where a string would mean a class.

It is passed as a **closure**, not a value.
The schema tier's `record()` takes a closure through its `object` arm and evaluates it once, memoising the result ([`BelongsToRecord`](../schemas/belongs-to-record.md)), which keeps the seed lazy: an eager read during `resolveActions()` would re-enter the very resolution that is building the schema.
An action's own `->schema(fn (Schema $schema) => … $schema->record(…))` runs *after* the seed and wins.

The operation is the dotted chain of mounted action **names** down to the frame (`edit`, or `edit.attachFile` one modal deeper), computed by `getMountedActionSchemaOperation()`.
Without it, [`Schema::getOperation()`](../schemas/has-operation.md)'s last fallback is the host **class name**, so `visibleOn('create')` inside a modal could never match.

### `getDefaultActionRecord()` answers a record, never a bare key

`getDefaultActionRecord()` returns `object|array<string, mixed>|null`, which is narrower than the `record(mixed)` setter it feeds.
The setter's width exists so a host can bind a bare key and let [`resolveRecordUsing()`](#record-resolution-is-a-single-host-callback-not-per-source) turn it into a record, but a default is answered by the host itself, which has no reason to hand back a key it would then have to resolve.

### A client-supplied record key is type-checked before the lookup

`resolveTableAction()` is Filament's whole body (the nested-frame `getModalAction()` hop, the bulk-then-record map read, and the record bind `$resolvedAction->getRootGroup()?->record($record) ?? $resolvedAction->record($record)` that hands a grouped action's record to the group so every dropdown member shares one) plus one check Filament has no need for.

`context` is a client-writable `#[LiveArg]`, so the value reaching `getTableRecord(string $key)` could be an array or an object.
A filled non-scalar key therefore throws `ActionNotResolvableException` naming its type, which the resolve pipeline swallows into the same graceful unmount a stale key gets.
The throw keeps the record bind all-or-nothing: skipping the bind instead would mount the action recordless and let a `fn (Song $record)` modal closure fatal further downstream.
A bulk name absent from **both** maps takes the same path.

### `clearRecordAfter()` runs at both of Filament's sites

An `Action` is cached per name for the life of the host object, so a record bound onto one survives the run that bound it.
Filament clears it at exactly two places, and so does the port: `unmountAction()`, once the stack has emptied (never while a parent frame is still open, whose child inherited that record), and `callMountedAction()`'s "the action replaced itself" early return, the one exit that leaves the stack standing without unmounting.

Only an action that asks for it is cleared: [`shouldClearRecordAfter()`](action.md) is `false` on the base and true on the create-shaped ones.
Filament's base answers `! $record->exists`, an Eloquent read of whether the bound model has been persisted, and there is no Doctrine equivalent that does not need an entity manager the action may not have, so the port's base is a flat `false` and each create-shaped subclass overrides it.

### A mounted modal's validation errors survive an unrelated live update

A modal that failed validation keeps its messages while the client goes on typing elsewhere on the page, and the mechanism is an ordering rather than any code that preserves them.
[`HasSchemas::revalidateValidatedSchemas()`](../schemas/has-schemas.md) runs at `#[PostHydrate(priority: -20)]` and re-validates every schema named in `$validatedSchemas` (a `#[LiveProp]`, so the *names* ride the wire where the messages never do), reading each by name; `resolveActions()` re-registers each mounted frame's schema at [`#[PostHydrate(priority: 10)]`](#the-mounted-action-schema-re-cache-runs-above-callafterschemastateupdated-by-explicit-posthydrate-priorities), which is **earlier**.
So by the time the revalidation walks `mountedActionSchema0`, the cache holds it and it re-validates against the hydrated data, producing the same failures again.

The obvious reading gets this wrong in both directions: the errors are not carried over the wire, and nothing preserves them either; they are re-derived every request, which is also why fixing the field clears them without a submit.
Reverse the two priorities and the modal's messages vanish on the next unrelated update.

### The mounted-action descriptor is typed string-keyed, and the client fills two thirds of it

The stack is `array<int, array{name: string, arguments: array<string, mixed>, context: array<string, mixed>}>`, and `arguments` / `context` arrive as `#[LiveArg]` values: the client controls those two completely, so their key type is a **claim**, not a guarantee, and a hand-written client can post a JSON array where a map belongs.
It is the right claim, because it is what everything downstream declares (`Action::$arguments`, `mergeArguments()` and `getArguments()` are all `array<string, mixed>`), and it needs no guard: a list merges by position and reads back as arguments no action asked for, with nothing to crash on, the same posture [`tableFilters`](../tables/host-has-filters.md#the-filter-bags-are-arrays-never-null-and-carry-no-getters) takes for its own client-writable bag.

The two are not equally cheap, though.
A filter slice *is* indexed by field name once it reaches `apply()` (`$data['isActive']`, and `$data['value']` / `$data['values']` on [`SelectFilter`](../../../packages/tables/src/Filters/SelectFilter.php)), so a posted list reads as filter state that is silently absent, and the query comes back unfiltered.
`arguments` is only ever **merged** (`mergeArguments()`) and handed whole to an evaluated closure; nothing in the port indexes it by a name the client did not supply.
So the same wrong shape costs a filter a wrong result set and costs an action nothing.

`name` is not a claim but a guarantee, which is why `resolveActions()` may index it without a `??` fallback: it is a native `string` parameter of `mountAction()`, the only other writer is `replaceMountedAction()`, and the prop is **not** writable, so a hydrated stack that reshaped it would fail ux-live-component's checksum before this trait ever saw it.
`resolveActions()` spells its two presence checks as `$descriptor['arguments'] !== []` and `mb_trim($descriptor['name']) === ''` rather than `filled()` / `blank()`, because neither value can be `null` and the helpers' `@phpstan-assert-if-*` would make the call constant at level max.

## Divergences

### A redirect leaves the pipeline as a returned response, not through a framework-side bag

Filament's `CanRedirect::redirect()` calls Livewire's own `$component->redirect($url, navigate: …)`, which writes into Livewire's request store; `callMountedAction()` then reads `store($this)->has('redirect')` to decide whether to unmount and return early.
UX LiveComponent has no such bag — a `#[LiveAction]` method **is** the controller (`LiveComponentSubscriber` sets `_controller` to it), so returning a `Symfony\Component\HttpFoundation\RedirectResponse` is the whole mechanism: the subscriber turns it into a `204` carrying `X-Live-Redirect` plus the `Location` header, and the client controller navigates.
The port therefore stores the resolved URL on the action ([`getPendingRedirectUrl()`](action.md), Sylament-only) and `callMountedAction()` reads it where Filament reads the store — after the commit, **before** the argument restore and the replaced-stack comparison, so an action that replaced the mounted stack still navigates.
`mountAction()`'s auto-run path returns this method's value verbatim, so a modal-less action redirects too.
Two consequences are load-bearing.
The component is **not** re-rendered on that path, so `unmountAction()` runs *before* the return rather than being skipped as unnecessary; and Filament's `navigate:`/SPA-mode argument has no analogue and is dropped — there is one navigation channel here, and it is the browser's.
Storing it on the action rather than in a per-request bag costs one thing Filament never pays: the action outlives the run.
`$cachedActions` holds the same instance for the life of the host object, so a URL resolved on one run would still be sitting there on the next, and the second run would navigate on the first run's answer even where the host now answers `null` — which is exactly what a create-another remount of the same cached action does.
`callMountedAction()` therefore calls `resetPendingRedirect()` the moment it has read the URL, before unmounting.

### Record resolution is a single host callback, not per-source

Filament never resolves a mounted action's record generically: the table branch of `resolveActions()` looks it up via `getTableRecord($recordKey)` (`resolveTableAction()`), the schema-component branch delegates to the schema, and the plain generic branch (`resolveAction()`) sets no record at all — callers of `Action::make()` outside a table/schema context set one manually.
Sylament adds a single generic seam instead: [`resolveRecordUsing(?Closure $callback)`](../../../packages/actions/src/Concerns/HasActions.php) lets the host register one resolver, and [`resolveMountedActionRecord()`](#a-root-frames-record-key-is-resolved-when-the-action-resolves-and-only-there) reads a frame's `context['recordKey']` and runs it through that callback — throwing `LogicException` if a `recordKey` is present with no resolver registered.
A nested action carries no record of its own from this seam — it inherits its parent's record by object propagation through [`prepareModalAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php), which the pipeline must leave standing rather than stamping a `null` over it.
This is what lets a plain host page (no table, no schema) mount a record-bound confirm action.

The table branch supplies its own record exactly as Filament does — [`resolveTableAction()`](#a-client-supplied-record-key-is-type-checked-before-the-lookup) binds it at **resolve** time, before the action is ever called — so the seam stays out of that path: `getMountedActionRecord()` returns `null` for a frame a table host resolved, the same `null` that already means "leave the action's record standing" for a nested frame.
That gate carries the same `instanceof InteractsWithTable` guard the dispatch does, so a plain host handed a stray `table` context still goes through `resolveRecordUsing()`, and a table host that registers a callback anyway keeps it for the frames the table branch does not claim.
The schema-component branch is gated out the same way, and for the same reason: an action owned by a component gets its record off the component chain.
The seam is therefore the resolution of exactly one kind of frame — a root frame the host itself owns.

### A root frame's record key is resolved when the action resolves, and only there

Filament's generic `resolveAction()` sets no record at all; only its table branch binds one, and it binds at **resolve** time.
The port's [single host callback](#record-resolution-is-a-single-host-callback-not-per-source) follows that timing for the generic branch too: `resolveAction()`'s root arm runs the frame's key through `resolveMountedActionRecord()` and calls `record()` on the resolved action before returning it.
Timing is the point.
A fill at mount, a `getRecordClass()` read a `unique()` constraint's validator makes while the host hydrates, and a modal heading built from `getRecordTitle()` all happen before `callMountedAction()` is reached, and every one of them wants the record already there.
The call-time bind stays as an idempotent second write.

Both binds gate on `is_object($record) || filled($record)`, the same gate [`Action::getRecord()`](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host) uses for an action's own record, and for the same reason: a resolved record is opaque, and `blank()` calls a `Countable` of zero or a `Stringable` casting to `''` blank.
An entity whose `__toString()` returns an empty name — an ordinary Symfony idiom — would otherwise never be bound, and the failure lands a layer away as *"fills its form from a record but none is bound"*.
Only a **scalar or array** record still has to be non-blank, which is the arm the gate keeps.

Which frames reach the resolver is the other half, and it is three guards rather than one.
A frame carrying a `schemaComponent` context answers `null` — the component chain owns that record.
A frame carrying a `table` context on an `InteractsWithTable` host answers `null` — `resolveTableAction()` bound it already.
And `getMountedActionRecord()`, the call-time read, delegates only when the stack holds exactly **one** frame, because a nested frame inherits its parent's record through `prepareModalAction()` and has no key of its own.

The first guard is what keeps the seam from firing on every schema host in the toolkit.
[`Action::getContext()`](action.md) stamps `recordKey` from `getRecord(withDefault: false)`, and that chain reaches the action's schema container — so a `Select` option action inside a record-bound form emits its form's record key, on a host that has no `resolveRecordUsing()` and no reason to grow one.
Without the guard the very first mount would be a `LogicException`.
It emits that key only where the form's record is keyed by **one scalar field**, or where no mapping describes the record at all ([the mint's own gate](action.md#getcontext-drops-the-record-model-gate)); a record whose identifier is composite or an association emits nothing and never reaches the guard at all, which narrows what the guard has to catch rather than changing it.
Filament has no equivalent guard because its generic branch never resolves a record in the first place.

The resolver is `protected` rather than a closure-only seam, so a host that already knows how to find its records can answer it outright instead of registering a callback — [panels'](../panels/README.md) resource pages answer it through the resource's own scoped binding.

### Every mounted-action run flushes a table host's records cache

`HasTable::getTableRecords()` memoises into `$cachedTableRecords`, which outlives a run on the same host object.
Without a flush, a modal create would render its new row only when nothing had populated that cache before the write; a header action reading `getAllTableRecordsCount()` first would hide it.
So `callMountedAction()` calls [`flushCachedTableRecords()`](../tables/has-table.md) on any `InteractsWithTable` host, through the same guarded soft `instanceof` the [table branch](#the-table-branch-reaches-tables-through-a-guarded-soft-reference) uses.

It runs in a **`finally`** around the whole run, so it is unconditional: success, an explicit `failure()`, a `Halt` out of a create-another, a `Cancel`, and a rethrown `ValidationException` all reach it.
Success-only would be the tempting gate and it is wrong — an action that halted or failed after its write has still written, and the stale cache would then be exactly what the user sees.
Filament has no counterpart at all: Livewire re-renders from a fresh component instance each request, so its table cache cannot outlive the call.

### Schema-context resolution returns null instead of throwing

Filament folds every schema-context resolution failure — unknown table/schema/action, host missing the schemas contract — into the same swallowable `ActionNotResolvableException` its mount pipeline catches.
The port ships that exception class for the generic arm (blank descriptor names, lying factories — see [getAction()](#getaction-ports-the-generic-method-discovery-arm-in-full)), but the schema-context path keeps its null-miss shape: a miss is a `null` return that `mountAction()`'s null branch turns into an unmount — the same observable outcome without the internal-control-flow exception.
Two consequences worth pinning.
First, a host that receives a `schemaComponent` context without implementing schemas' [`InteractsWithSchemas`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) is a wiring bug, not a client-data miss, so [`resolveSchemaComponentAction()`](../../../packages/actions/src/Concerns/HasActions.php) throws a `LogicException` naming both fixes (implement the contract, compose the `HasSchemas` trait) where Filament folds the case into the same swallowable exception.
Second, an unknown schema *name* must stay a miss, but schemas' `HasSchemas::getSchema()` throws where Filament's returns `null` — so the resolver ports the discovery guard of Filament's `cacheSchema()` and returns `null` without calling `getSchema()` unless the candidate `{$name}()` / `{$name}Schema()` method exists **and** is schema-shaped: first parameter typed `Schema`, or parameterless with a `Schema` return type.
The shape half is load-bearing, not pedantry — the context key is a client-writable `#[LiveArg]` and `getSchema()` invokes whatever method discovery finds, so a bare `method_exists` check would let a crafted context remotely invoke any host method (`unmountAction`, `callMountedAction` — which recurses — or a consumer's parameterless `save()`); Filament no-ops all of these, and the guard makes the port miss exactly where Filament's `cacheSchema()` misses.
A crash inside a real schema-builder method still propagates loud instead of being swallowed by a catch.
The guard is schemas' shared [`resolveSchemaByName()`](../schemas/interacts-with-schemas.md#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source), which `getSchemaComponent()` calls too, so the two sites cannot drift apart.
The `$parentActions` parameter is not part of this divergence either way: Filament accepts and ignores it on the schema branch, while the port's signature is `resolveSchemaComponentAction(array $descriptor, array $parentActions)` and **reads** it — it is what tells a root frame from a deeper one, and the [deeper-frame gate](#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache) is stated in terms of it.
Reverses if a frame ever needs a typed failure signal the null return cannot carry; the schema path would adopt the exception class then.

### A schema-less action is called with no arguments, not with a pair of nulls

Filament computes `$schema` once, before the try block, and passes `['form' => $schema, 'schema' => $schema]` into `Action::call()` on **every** branch ([`InteractsWithActions::callMountedAction()`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)), so an action with no modal schema still binds both names — to `null`.
The port computes `$schema` inside the schema branch and passes `[]` on the other, since there is no schema to name.

What that changes is only which mechanism answers a closure asking for one.
`form` reaches no resolver here, so a `$form` parameter is filled from its own default or `null` when it is optional and raises the Invoker's named error otherwise, where Filament binds it to `null`.
`schema` is answered anyway, by `Action`'s own [name resolver](../../../packages/actions/src/Action.php), which returns `getSchemaContainer()` — the container the action was **registered** on, not the modal schema — so a `?Schema $schema` parameter still lands and a non-nullable one still takes a `TypeError` when that container is null, exactly as it would under Filament's explicit `null`.
Reverses by passing the pair unconditionally the moment a caller wants `form` bound on the schema-less branch.

### `getMountedAction()` never guards its own read with a `catch`

Filament wraps most `getMountedAction()` call sites in `try { … } catch (ActionNotResolvableException)`, because its resolvers throw on every miss.
The port swallows that exception in exactly one place — [`cacheMountedActions()`](../../../packages/actions/src/Concerns/HasActions.php), which caches an unresolvable stack as `[]` — so `getMountedAction()` itself returns `null` rather than throwing, and the `catch` Filament writes around it in `unmountAction()` would be provably dead code under PHPStan level max.
The guard is therefore absent, not forgotten — do not re-add it.
Everything else about the method is Filament's: the optional `$actionNestingIndex` defaults to the top of the stack, a warm positional `$cachedMountedActions` entry short-circuits, and a cold read resolves the prefix `array_slice($mountedActions, 0, $index + 1)` so a deeper frame still resolves through its parents.

### Only the per-name action cache is ported

Filament populates `$cachedActions` from three sources: `cacheAction()` (called by `resolveAction()` on every method-resolved action, and directly by external registrars), `mergeCachedActions()` (bulk registration), and `cacheTraitActions()` (a `class_uses_recursive()` walk on Livewire boot that lets sibling `*Actions`-suffixed traits register their own actions).
The port ships [`cacheAction()`](../../../packages/actions/src/Concerns/HasActions.php) and the `$cachedActions` property — `getAction()` consults the cache before calling the `{name}Action()` factory and caches what it resolves, so the trigger button and the confirm modal share one `Action` instance per name per request.
`mergeCachedActions()` and `cacheTraitActions()` are **skipped**: both are bulk-registration mechanisms with no Sylament consumer (no `*Actions` sibling traits exist), and `cacheTraitActions()` additionally rides Livewire's boot hook, which has [no equivalent here](#there-is-no-boot-hook-or-rate-limiting-trait).
Both return with their first bulk consumer (e.g. a table-actions trait).

### There is no boot hook or rate-limiting trait

Filament's `bootedInteractsWithActions()` does three things: it restores `$originallyMountedActionIndex` from the stack on every Livewire boot, calls `cacheTraitActions()`, and, when the host is not `HasTable`, re-derives `$cachedMountedActions`, clearing `mountedActions` if none resolve.
Sylament's `HasActions` declares no boot method — UX LiveComponent has no component boot lifecycle — so the half that matters is re-homed onto a hydration hook instead (see [the stale-stack self-heal](#the-stale-stack-self-heal-is-a-posthydrate-hook-not-a-boot-hook)), and `$originallyMountedActionIndex` is simply [not ported](#originallymountedactionindex-and-getoriginallymountedactionindex-are-not-ported).
Likewise, Filament composes Laravel's `DanHarrin\LivewireRateLimiting\WithRateLimiting` trait for `callMountedAction()`'s rate-limit check; no Symfony equivalent is pulled in, and rate limiting is out of scope until `CanBeRateLimited` lands on `Action`.
The boot **hook** and the rate-limiting trait are both **skipped** rather than `todo`/`blocked` — reversible the moment an `Action` rate-limit actually needs one.

### The stale-stack self-heal is a `#[PostHydrate]` hook, not a boot hook

The consistency check Filament runs on boot is real work here too: the `$mountedActions` LiveProp round-trips through the browser, so a stack that no longer resolves (a renamed action, a changed schema, a redeployed app) would otherwise render a broken modal chain.
With no boot lifecycle to hang it on, the port uses UX LiveComponent's own [`#[PostHydrate]`](../../../packages/actions/src/Concerns/HasActions.php) attribute: `afterMountedActionsHydrated()` returns immediately on an empty stack and otherwise resolves it once, clearing `$mountedActions` when nothing resolves.
Same guarantee, framework-idiomatic placement, and the resolution it performs warms `$cachedMountedActions` for the rest of the request rather than being thrown away.
Filament's `! $this instanceof HasTable` guard has no port: the self-heal runs for a table host too, where the [table branch](#the-table-branch-reaches-tables-through-a-guarded-soft-reference) re-resolves the stack against the table on the same hook.

### `$originallyMountedActionIndex` and `getOriginallyMountedActionIndex()` are not ported

Filament tracks which frame was mounted first so a `replaceMountedAction()` chain can tell "the action the user started" from "the action currently on top", and restores that index on boot.
Nothing in the port reads it: the cancel cascade addresses parents by **name** (`getParentActionToCancelTo()`) or by "all", never by a remembered index.
Not ported, and no consumer is in sight; it returns with whichever feature actually needs to name the originating frame.

### The mounted-action stack is a server-controlled LiveProp, not a client-writable array

Filament's `$mountedActions` is a client-writable Livewire property whose integrity rides on a checksum round-tripped from the browser.
Sylament's [`$mountedActions`](../../../packages/actions/src/Concerns/HasActions.php) is a `#[LiveProp]` with **no `writable: true`**: each frame's descriptor (`name`/`arguments`/`context`) is written only from inside the `mountAction()` / `unmountAction()` / `replaceMountedAction()` methods, never from the client.
A confirm action therefore carries no user-editable payload and there is no tampering surface to guard for free — the client can still name an action, but only through `mountAction`'s `#[LiveArg]`, which the [return-type gate](#getaction-ports-the-generic-method-discovery-arm-in-full) and the [schema-context resolution gate](#resolution-stops-at-the-first-null-frame-and-a-deeper-frame-reaches-schema-context-only-through-the-cache) both police.
One exception is **deliberate and narrow**: a form-in-modal needs client-writable per-frame form state, so a *sibling* bag is `writable: true` while the descriptor stack itself stays server-controlled — see [the sibling-bag divergence](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor).

### Per-mount form state lives in a sibling bag, not in the stack descriptor

Filament binds each mounted action's form at `mountedActions.{$i}.data`, a key inside the stack descriptor.
The port binds `mountedActionsData.{$i}` instead, because `Sylament\Schemas\State\PropertyAccessor` translates the dotted path into the Symfony property path `mountedActions[0][data][title]`, and `ReflectionExtractor::getReadInfo()` resolves that first segment through `getMountedActions()` — a getter returning resolved `Action` objects — so every read and write raises `NoSuchIndexException`.
The same getter-precedence rule already forces [the `dehydrateWith:` bridge](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method); Livewire has no such rule, which is why Filament can nest the two.
The rejected alternative was `writable: true` on `$mountedActions` itself, which `LiveComponentHydrator::calculateWritablePaths()` would extend to `name`, `arguments` and `context` — a privilege escalation with no authorization port to compensate for it.

Consequences, all deliberate: the descriptor stack stays a server-controlled `#[LiveProp]` while the data bag is `writable: true` (the narrowest possible reversal of [that divergence](#the-mounted-action-stack-is-a-server-controlled-liveprop-not-a-client-writable-array) — form state only); presence of a frame in the bag is never evidence of a mount, `$mountedActions` alone is; and the bag is hand-maintained in lockstep — `mountAction()` seeds `[$i] = []` before the frame can render, `unmountAction()` drops every index the stack no longer has, `replaceMountedAction()` clears it.
That last point is load-bearing rather than tidiness: `live_controller.js`'s `getDeepData()` dereferences every `[data-model]` path segment unguarded after every render, so a field rendered at `mountedActionsData.0.title` with no index `0` in the props is an uncaught `TypeError` that kills live updates for the entire component.
Because the bag is client-writable, both purges key off the **index value** against the server-controlled stack rather than off the bag's own key order, which a client could scramble.
Reading the bag goes through `getMountedActionData(?int)` — singular, so it can never collide with the property the way `getMountedActions()` does, and adding a plural `getMountedActionsData()` would reintroduce the exact trap this bag exists to route around.

### The error-bag reset is scoped to one frame, not a global reset

Filament calls Livewire's `resetErrorBag()` at two points in this trait — before a modal opens in `mountAction()`, and at the end of an `unmountAction()` that leaves frames standing (`InteractsWithActions.php:892`) — each wiping every error on the whole component.
The port calls [`clearSchemaValidation()`](../schemas/interacts-with-schemas.md#clearschemavalidation-is-scoped-where-filament-resets-the-whole-livewire-error-bag) with the one key the moment concerns: `mountAction()` clears `mountedActionSchema{$i}` for the index it just pushed, and `forgetMountedActionSchema($i)` clears the frame it is forgetting, which puts the unmount-side clear on every path that drops a frame.

The behavioural difference is a nested stack, and it is the reason to prefer the narrow call: the frame's key is also what keeps it [sticky](../schemas/can-be-validated.md#sticky-re-validation-replaces-filaments-submit-only-model) — `$validatedSchemas` re-validates `mountedActionSchema{$i}` on every hydrate, so the messages a modal shows after a failed submit are recomputed, not remembered, and clearing the key is what stops that.
With a global reset, mounting a child action erases the parent modal's messages, so a user who submits an invalid parent form and then opens a child from it comes back to a form that has silently forgotten what was wrong with it.
Per-frame clearing keeps each frame's errors alive for exactly as long as the frame is.

### The has-schema check is memoised separately, because the schema cache cannot hold null

[`mountedActionHasSchema()`](../../../packages/actions/src/Concerns/HasActions.php) carries a second memo of its own — `protected array $cachedMountedActionHasSchema = []`, keyed by nesting index — alongside the schema cache.
It looks redundant and is not.

Memoising is **required, not an optimisation.**
[`isMountedActionModalOpen()`](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events) runs the modal-open gate once per frame per render ([`Modals/index.html.twig:4,8`](../../../packages/actions/templates/components/Modals/index.html.twig)), and that gate passes `checkForSchemaUsing: $this->mountedActionHasSchema(...)`.
Unmemoised, a stack of _n_ frames costs O(_n_²) schema constructions per render.

The schema cache cannot do the job, because the answer that needs caching most is the negative one.
`cacheSchema(string $name, ?Schema $schema)` treats `null` as **unset** ([`HasSchemas`](../schemas/has-schemas.md#cacheschema-has-no-discovery-arm-and-requires-its-second-argument), mirroring `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:349-366`), and `$cachedSchemas` is typed `array<string, Schema>`.
So it can remember "this frame **has** a schema" and can never remember "this frame has **none**" — the negative answer is exactly the `null` the cache spends as its absence sentinel, so a schema-less action would rebuild and re-probe on every single call.
A boolean-only sidecar is the smallest thing that closes that.

The maintenance rule is the whole cost, and it is worth stating plainly: **the two caches must be invalidated together.**
`unmountAction()` and `replaceMountedAction()` each drop `$cachedMountedActionHasSchema[$i]` alongside forgetting `mountedActionSchema{$i}`.
Let them drift and a replaced frame reports the previous frame's answer — a modal that opens with nothing in it, or one that never opens at all.

No Filament analogue.
Livewire's `$cachedSchemas` has the same null-is-absent shape, but Filament calls `mountedActionHasSchema()` once per modal render rather than once per frame per render, because its stack is choreographed client-side (see [declarative choreography](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events)).
The extra memo is a direct consequence of computing open state on the server.

### The mounted-action schema re-cache runs above `callAfterSchemaStateUpdated()`, by explicit `#[PostHydrate]` priorities

A mounted-action schema is **cache-only**: no `schemas()` name can rebuild it, and `HasSchemas::$cachedSchemas` is a plain protected property, not a `#[LiveProp]`, so it arrives empty on every request.
Two hooks therefore have to run in a fixed order, and both say so in their attribute rather than relying on declaration order:

| Hook | Attribute | Job |
| --- | --- | --- |
| [`HasActions::afterMountedActionsHydrated()`](../../../packages/actions/src/Concerns/HasActions.php) | `#[PostHydrate(priority: 10)]` | Resolves the hydrated stack, which is what puts every frame's schema back under `mountedActionSchema{$i}` (via [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php)) |
| [`HasSchemas::callAfterSchemaStateUpdated()`](../schemas/has-schemas.md) | `#[PostHydrate(priority: -10)]` | Walks `getCachedSchemas()` and fires `afterStateUpdated` on every component whose state changed |

Higher priority runs earlier — `vendor/symfony/ux-live-component/src/Attribute/PostHydrate.php` documents `$priority` as "higher called earlier".

This is load-bearing, and its failure mode is silent.
On any request that is not itself a `mountAction()` call — a field update inside an open modal is exactly that — nothing else puts the mounted-action schema back before the state-hook walk.
Get the order wrong and `callAfterSchemaStateUpdated()` walks a cache that does not yet contain the modal's schema: every field inside the open modal is skipped, so `live()` and `afterStateUpdated()` **never fire there** and nothing reports an error.

The explicit numbers matter as much as their sign.
Equal priorities would sort by reverse declaration order — an artifact of trait composition on the host class, not a contract worth depending on — so both hooks state a priority even though only their relative order is meaningful.
Filament needs none of this: Livewire re-runs Filament's `resolveActions()` schema caching as part of its own request lifecycle, so the ordering is implicit there.

### `Set` and `Get` are unavailable to an action inside a mounted-action schema

An `Action` declared as a direct child of a **mounted-action** schema cannot inject `$set` / `$get` (or `$schemaSet` / `$schemaGet`).
[`BelongsToSchemaComponent::getSchemaComponent()`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) resolves `$this->schemaComponent ?? $this->getSchemaContainer()?->getParentComponent() ?? $this->getGroup()?->getSchemaComponent()`.
A mounted-action schema is a **root** schema (`getMountedActionSchema()` builds it from `makeSchema()`, so it has no parent component), and an action declared inside it belongs to no group and is bound to no component of its own, so all three arms are `null`.
`Action`'s `get` / `set` arms reach the component through `getSchemaComponentOrFail()` (`packages/actions/src/Action.php:664-665`), which throws a `LogicException` naming the class ([why](action.md#get-and-set-throw-on-an-action-with-no-schema-component-where-filament-fatals)).

Filament fails on the same case: `vendor/filament/actions/src/Concerns/BelongsToSchemaComponent.php:28-31` is the port's resolution chain verbatim, and `vendor/filament/actions/src/Action.php:581-582` calls `->makeGetUtility()` / `->makeSetUtility()` on it unguarded, a fatal on `null`.
The same action declared in a **top-level** host schema works, because its container has a parent component (a `Section`, say) for the second arm to find, so the limitation is specific to root schemas, of which the mounted-action schema is the only one users meet.

Such an action can still inject `$liveComponent`, `$arguments`, `$record` and `$data`, and reach the form state through those.
A fix is upstream-shaped (bind the mounted-action schema to a component, or make the arms null-safe on both sides), so the port keeps Filament's reach.

### The `$mountedActions` LiveProp dehydrates through a bridge method

`#[LiveProp(dehydrateWith: 'dehydrateMountedActions')]` looks like ceremony and is not: without it, adding `getMountedActions()` silently breaks dehydration.
Symfony's `PropertyAccessor` resolves a property read by trying a same-named **getter first** (`ReflectionExtractor::getReadInfo()` checks `get`/`is`/`has`/ `can` prefixes before the property), and [`LiveComponentHydrator::dehydrate()`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php) reads every `#[LiveProp]` through that accessor — so `$mountedActions` would dehydrate as the **resolved `Action` objects** `getMountedActions()` returns rather than the wire-safe descriptor array.
`dehydrateWith:` names a method that returns the raw property, and the vendor's own exception message points at that attribute as the remedy.

This has no Filament analogue: Filament has the identical property/getter name pair, but Livewire has no getter-precedence convention, so it never collides.
It is load-bearing rather than decorative — **do not delete it** when the pair looks redundant.
Two consequences worth knowing: `getMountedActions()` runs on every dehydrate and its result is discarded (cheap — the cache is usually warm), and this prop must never gain `writable: true` while the getter exists, because the hydration side would have the same ambiguity in reverse.
The same collision exists benignly on [`$actionsHostId`](modals.md#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id).

### Resolution stops at the first null frame, and a deeper frame reaches schema context only through the cache

Two deltas in [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php) that only make sense together.

Filament `continue`s past a frame that resolves to nothing; the port `break`s.
The difference matters because the two codebases fail differently: Filament's resolvers **throw** on a miss, so a `null` there is near-unreachable, while the port's [schema-context resolver returns `null`](#schema-context-resolution-returns-null-instead-of-throwing) and `getAction()` returns `null` for an unknown name.
Continuing past a null frame would hand the *next* frame an empty parent chain, and an empty parent chain means "resolve against the host" — so a client-named child frame could re-root itself as a top-level host action.
A stack with a hole is never valid anyway, so stopping is both safer and equivalent for every case Filament can actually reach.
The resolved list is therefore always a prefix of the stack it was given.

The schema-context branch is gated, but not on frame position alone.
The loop takes it for **any** frame carrying the key, and [`resolveSchemaComponentAction()`](../../../packages/actions/src/Concerns/HasActions.php) owns the policy one call down, where the cache lookup it depends on lives: `if (($parentActions !== []) && (! $isCachedSchema)) { return null; }`.
A deeper frame is admitted **only** when the container it names is already registered in the schema cache; a deeper frame naming a *method-discovered* schema is still refused.

Filament branches on the context key alone and can afford to, because its `Action::getContext()` never emits `schemaComponent` for a child frame (the port's `getContext()` has [the same early return](action.md#getcontext-drops-the-record-model-gate)).
But `context` is a client-supplied `#[LiveArg]`, so a hand-built descriptor could carry both a `schemaComponent` key and a nesting position — which is what the gate exists to answer.

What puts that entry in the cache in time is the loop position: the port registers each frame's schema **inside** the walk, at Filament's own position, rather than after it — the next frame may *be* one of that schema's actions, and a descriptor carrying `context['schemaComponent'] = 'mountedActionSchema0'` is resolved by reading exactly the entry the previous iteration wrote.
A host that is not an `InteractsWithSchemas` skips the registration rather than throwing: an action that has a schema can never have been mounted on such a host, because [`mountAction()`](#mountaction-drops-authorization) throws first.

The cache concession does not weaken it, because the two halves of "who can mint a name" are not the same as "who can say a name".
Only [`getMountedActionSchema()`](../../../packages/actions/src/Concerns/HasActions.php) registers a cache-only schema, always under a server-minted `mountedActionSchema{$i}`; a client can *name* one but can never *create* one, and nothing method-discovered ever enters that branch.
So the property the gate guarantees is unchanged — a nested frame can never re-root itself against a host-**declared** schema — while it admits the case a position-only gate would refuse: an `Action` declared *inside* a mounted-action schema, whose container has no declaring method and which necessarily sits at frame 1 or deeper.
Filament reaches that same case through a bare `getSchema($name)` (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:742`).
Together with the `break` above, this is the whole guarantee that a deeper frame cannot resolve against the host.

### No resolved action is `boot()`ed

Filament calls `$resolvedAction->boot()` on every frame in `resolveActions()`, part of its `CanBeBooted` concern (a per-instance `booted` flag and a queue of boot callbacks).
Neither the concern nor any component boot lifecycle exists in Sylament, so the call has no target to make.
It returns with `CanBeBooted`, in the same loop position.

### `resolveActions()` takes no `$isMounting` flag

Filament's `resolveActions(array $actions, bool $isMounting = true)` gates exactly one thing on that parameter: the per-frame `cacheSchema("mountedActionSchema{$i}", …)` registration.
The port drops the parameter, and this one is deliberate rather than pending — `resolveActions()` has a single caller here, `cacheMountedActions()`, which only ever passes a prefix of `$mountedActions`, so there is no non-mounting path to gate.
It returns only if a second, non-mounting caller ever appears.

### The table branch reaches tables through a guarded soft reference

[`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php) imports tables' [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) and dispatches on `$this instanceof InteractsWithTable && filled($descriptor['context']['table'] ?? null)`, yet `sylament/actions` does **not** require `sylament/tables` — it only suggests it.
The guard is Filament's own (`$this instanceof HasTable`, with `filament/actions` requiring `filament/tables` nowhere either); the `suggest` entry is ours, since Filament declares the optional link nowhere at all.
Two things make the soft reference safe: the interface is only ever named in an `instanceof` and a `::class` constant, neither of which loads the class — an `instanceof` against a missing interface is `false`, not an error — and the host check comes **first** in the `elseif`, so a host without the contract falls through to generic resolution even when a client hands it a `table` context.

It is the second instance of a pattern [`docs/internals/dependencies.md`](../../internals/dependencies.md#the-guarded-optionals-and-their-pins) names explicitly — the first is support's `isAssetMapperAvailable()` guard around each bundle's `prependExtension()` — and its cost is that composer checks nothing: rename or move the contract and the branch dead-ends in silence.
That is what [`packages/tables/tests/Integration/RecordActionsResolutionTest.php`](../../../packages/tables/tests/Integration/RecordActionsResolutionTest.php) is for; it fails loudly the moment the guard stops matching.
The same file's `src`-mirror position is deliberately relaxed for the same reason: the branch lives in actions, but only a tables host can exercise it.

### A table context on a non-table host is a wiring bug, not a miss

Filament's `resolveTableAction()` opens by throwing `ActionNotResolvableException` when the host is not a `HasTable` — the same swallowable exception its mount pipeline catches, so the frame silently unmounts.
The port throws a `LogicException` naming the contract to implement instead, matching what [`resolveSchemaComponentAction()`](#schema-context-resolution-returns-null-instead-of-throwing) already does for a host missing `InteractsWithSchemas`.
The case is unreachable through the dispatch — `resolveActions()` only takes the branch for a host that *is* an `InteractsWithTable` — so the throw guards a direct call, and the re-check doubles as the narrowing PHPStan needs for the `getTable()` / `getTableRecord()` calls that follow.

### A schema-bearing action on a host without schemas throws

Filament's `getMountedActionSchema()` calls `hasCachedSchema()` and `makeSchema()` unconditionally (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:792-797`), so a host that does not also compose schemas' `InteractsWithSchemas` fails on an undefined method.
Here [`getMountedActionSchema()`](../../../packages/actions/src/Concerns/HasActions.php) on a host that is not an `InteractsWithSchemas` probes the action against a detached `Schema::make()`: a schema-bearing action throws a `LogicException` naming the missing contract and trait, and a schema-less action mounts normally.

### The deprecated `mountedActionHasForm()` and `getMountedActionForm()` aliases are not ported

Filament marks both `@deprecated`, pointing to `mountedActionHasSchema()` and `getMountedActionSchema()` (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:519-521`, `:816-818`).
The port ships only those replacements, as it ships no deprecated surface.
