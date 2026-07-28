# HasActions (actions)

`Sylament\Actions\Concerns\HasActions` — the host-side trait mixed onto the
UX LiveComponent page that mounts and calls `Action`s (the `Sylament\Actions\Contracts\InteractsWithActions`
implementor); it is the **swap** counterpart of Filament's `Concerns\InteractsWithActions`
trait (see the [rename table](../renames.md)). It composes `ComponentToolsTrait`
for `dispatchBrowserEvent()` and owns the `#[LiveProp] $mountedActions`
**stack**, the `mountAction` / `callMountedAction` / `unmountAction`
`#[LiveAction]` trio plus `replaceMountedAction()`, action lookup
(`getAction`, `getMountedAction`, `getMountedActions`, `resolveActions`,
`resolveAction`) backed by a per-name instance cache
(`cacheAction`, `$cachedActions`) and a positional resolved-frame cache
(`$cachedMountedActions`), the modal-open gate
(`mountedActionShouldOpenModal`), the per-frame modal id and open-state
helpers (`getActionsHostId` / `getMountedModalId` /
`isMountedActionModalOpen`), the per-mount schema pipeline (the client-writable
`$mountedActionsData` bag, `getMountedActionData`, `getMountedActionSchema`,
`mountedActionHasSchema`), a `#[PostHydrate]` stale-stack self-heal, and a
host-supplied `resolveRecordUsing()` callback for resolving a mounted
action's record. Its rendered counterpart —
[`<twig:SylamentActions:Modals />`](../../../packages/actions/templates/components/Modals.html.twig)
plus the [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php)
component class behind it — folds in here rather than earning its own file,
because it is a pure view over four of those methods (`getMountedActions` /
`mountedActionShouldOpenModal` / `getMountedModalId` /
`isMountedActionModalOpen`), the same relationship `ActionModal` has to
`Action`. Filament's `InteractsWithActions` is a ~870-line trait built around
a stack of mounted actions (nested/parent actions, per-action `Schema`
mounting, authorization, rate limiting, database transactions, redirects,
URL-bound default actions, table/schema-scoped action resolution); Sylament
now ships the stack itself — append-on-mount, parent-modal descent,
the cancel cascade, per-frame modal ids and open state, and the stacked
render loop — plus the schema-scoped resolution branch
(`resolveSchemaComponentAction()`, from the schemas↔actions integration) and the
per-mount `Schema` with its mount/fill/submit lifecycle, and defers
authorization, rate limiting, transactions, redirects, and the URL-bound
default action.

The renderer is now Filament's real loop: `Modals.html.twig` walks
`getMountedActions()` and renders one `ActionModal` per frame, gating only the
last (`{% if not loop.last or mountedActionShouldOpenModal(action) %}`), which
is the exact port of Filament's
`@if (! $loop->last || mountedActionShouldOpenModal())`. What is *not* ported
is the client-side choreography Filament layers on top of that loop — see
[declarative choreography](#the-stack-is-choreographed-declaratively-not-by-client-events).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)
  — the host trait this file ports (Filament name; see the swap note above)
- [`actions/src/Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php)
  — the interface Filament's trait implicitly targets, ported as Sylament's
  [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
- [`actions/resources/views/action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php)
  — where Filament computes the per-nesting-index modal id this file's
  `getMountedModalId()` substitutes for
- [`actions/resources/views/components/modals.blade.php`](../../../vendor/filament/actions/resources/views/components/modals.blade.php)
  — the host-side renderer ported as `<twig:SylamentActions:Modals />` (its row
  below)

## Surface

_Blocked by_ names what has to land first. _Detail_ carries **detail** → the
write-up in [Details](#details), and/or **diverges** → the note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`resolveRecordUsing()`](../../../packages/actions/src/Concerns/HasActions.php) / `getMountedActionRecord()` | — (new; replaces Filament's per-source record resolution) | done | — | [diverges](#record-resolution-is-a-single-host-callback-not-per-source) |
| [`$mountedActions`](../../../packages/actions/src/Concerns/HasActions.php) stack + `dehydrateMountedActions()` | `Concerns/InteractsWithActions.php` `$mountedActions` | done | — | [diverges](#the-mounted-action-stack-is-a-server-controlled-liveprop-not-a-client-writable-array) · [diverges](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method) |
| [`mountAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `mountAction()` | in progress | `CanBeAuthorized` | [detail](#mountaction-drops-authorization-and-the-validationexception-arm) |
| [`callMountedAction(array $arguments = [])`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `callMountedAction()` | in progress | `CanBeRateLimited`, `CanBeAuthorized`, `CanUseDatabaseTransactions`, `CanRedirect` | [detail](#callmountedaction-drops-rate-limiting-authorization-transactions-and-redirects) |
| [`unmountAction(bool\|string\|null $cancelParentActions = null)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `unmountAction()` | in progress | `#[Url]` default-action state, epic-6 validation (`resetErrorBag`) | [detail](#unmountaction-drops-the-default-action-reset-and-the-error-bag-reset) · [diverges](#the-stack-is-choreographed-declaratively-not-by-client-events) |
| [`replaceMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `replaceMountedAction()` | done | — | [diverges](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper) |
| [`getMountedAction(?int $actionNestingIndex = null)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getMountedAction()` | done | — | [diverges](#getmountedaction-never-guards-its-own-read-with-a-catch) |
| [`getMountedActions()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getMountedActions()` | done | — | [diverges](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method) |
| Resolved-frame cache ([`$cachedMountedActions`](../../../packages/actions/src/Concerns/HasActions.php), `cacheMountedActions()`) | `Concerns/InteractsWithActions.php` `$cachedMountedActions` | done | — | — |
| The `#[PostHydrate]` stale-stack self-heal ([`afterMountedActionsHydrated()`](../../../packages/actions/src/Concerns/HasActions.php)) | `Concerns/InteractsWithActions.php` `bootedInteractsWithActions()`'s stale-stack drop | done | — | [diverges](#the-stale-stack-self-heal-is-a-posthydrate-hook-not-a-boot-hook) |
| [`mountedActionShouldOpenModal()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `mountedActionShouldOpenModal()` | done | — | [diverges](#the-modal-open-gate-is-null-safe) |
| [`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getAction()` + `resolveAction()`'s method-discovery arm | in progress | `BelongsToTable` (table-scoped branch); the descriptor-array form and `$isMounting` have no blocker, only no consumer | [detail](#getaction-ports-the-generic-method-discovery-arm-in-full) |
| [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveActions()` | in progress | `BelongsToTable` (table-context branch), action-schema integration (`$isMounting` / `cacheSchema`) | [detail](#resolveactions-drops-the-table-branch-and-the-ismounting-schema-cache) · [diverges](#resolution-stops-at-the-first-null-frame-and-only-the-root-frame-may-use-schema-context) · [diverges](#no-resolved-action-is-booted) |
| [`resolveAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveAction()` | done | — | — |
| [`resolveSchemaComponentAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveSchemaComponentAction()` | done | — | [diverges](#schema-context-resolution-returns-null-instead-of-throwing) |
| Action caching ([`cacheAction()`](../../../packages/actions/src/Concerns/HasActions.php), `$cachedActions`) | `Concerns/InteractsWithActions.php` `cacheAction()` / `mergeCachedActions()` / `cacheTraitActions()` | done | — | [diverges](#only-the-per-name-action-cache-is-ported) |
| [`getActionsHostId()`](../../../packages/actions/src/Concerns/HasActions.php) + the `$actionsHostId` `#[LiveProp]` | `action-modal.blade.php`'s `$this->getId()` (Livewire component id) | done | — | [diverges](#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id) |
| [`getMountedModalId(?int $actionNestingIndex = null)`](../../../packages/actions/src/Concerns/HasActions.php) | `action-modal.blade.php` inline `$actionModalId` | done | — | [diverges](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper) |
| [`isMountedActionModalOpen(int $actionNestingIndex)`](../../../packages/actions/src/Concerns/HasActions.php) | — (Sylament-only; replaces Filament's client-side stack machine) | done | — | [diverges](#the-stack-is-choreographed-declaratively-not-by-client-events) · [diverges](#the-parent-overlay-flag-is-server-stamped-not-a-sibling-css-selector) |
| Modals rendering ([`Modals.html.twig`](../../../packages/actions/templates/components/Modals.html.twig) + [`Twig\Components\Modals`](../../../packages/actions/src/Twig/Components/Modals.php)) | [`components/modals.blade.php`](../../../vendor/filament/actions/resources/views/components/modals.blade.php) | done | — | [diverges](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope) · [diverges](#the-modal-is-opened-by-rendered-state-not-by-the-dispatched-event) · [diverges](#the-modals-renderer-emits-no-wrapper-element) · [diverges](#there-is-no-single-render-guard) · [diverges](#an-unresolvable-host-throws-instead-of-rendering-nothing) |
| [Action-schema integration](../../../packages/actions/src/Concerns/HasActions.php) (`getMountedActionSchema()`, `mountedActionHasSchema()`, deprecated `mountedActionHasForm`/`getMountedActionForm`, `getMountedActionSchemaName()`, `getMountedActionSchemaModel()`) | `Concerns/InteractsWithActions.php` (same methods) | in progress | Doctrine model binding (`->model()`), an `Operation` concept (`->operation()`) | [detail](#the-per-mount-schema-carries-no-model-or-operation) · [diverges](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor) |
| [`$mountedActionsData`](../../../packages/actions/src/Concerns/HasActions.php) + `getMountedActionData()` | `Concerns/InteractsWithActions.php` `mountedActions.{i}.data` | done | — | [diverges](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor) |
| `#[Url]`-bound default-action state (`defaultAction`, `defaultActionArguments`, `defaultActionContext`, `defaultTableAction*`) | `Concerns/InteractsWithActions.php` public `#[Url]` properties | todo | — | [detail](#url-bound-default-action-state-is-not-ported) |
| `getDefaultAction*` host override hooks (Record/Title/Model/ModelLabel/Url/SuccessRedirectUrl/FailureRedirectUrl/Relationship/SchemaResolver/AuthorizationResponse/IndividualRecordAuthorizationResponseResolver) | `Concerns/InteractsWithActions.php` `getDefaultActionRecord()` etc. | todo | — | [detail](#the-getdefaultaction-host-override-hooks-are-not-ported) |
| `forceRender()` / `partiallyRenderActionParentSchema()` | `Concerns/InteractsWithActions.php` `forceRender()` / `partiallyRenderActionParentSchema()` | blocked | schemas partial-render infrastructure (`PartialsComponentHook`) | [detail](#partial-re-render-is-not-ported) |
| [`afterActionCalled()`](../../../packages/actions/src/Concerns/HasActions.php) hook (empty protected method; runs after `callAfter()`, before the notification branch, happy path only — Filament-exact) | `Concerns/InteractsWithActions.php` `afterActionCalled()` | done | — | — |
| `bootedInteractsWithActions()` boot wiring / `WithRateLimiting` | `Concerns/InteractsWithActions.php` `bootedInteractsWithActions()`, `DanHarrin\LivewireRateLimiting\WithRateLimiting` | skipped | — | [diverges](#there-is-no-boot-hook-or-rate-limiting-trait) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### `mountAction()` drops authorization and the `ValidationException` arm

Filament's `mountAction()` pushes onto the stack, resolves the action
(swallowing `ActionNotResolvableException`), bails via `unmountAction(cancelParentActions: false)`
when the action is missing or disabled, validates any `ExposesStateToActionData`
child schemas, checks `hasAuthorizationNotification()` / `getAuthorizationResponseWithMessage()`
and sends an unauthorized notification + `Cancel`, runs `callBeforeFormFilled()` /
`action->mount([...])` / `callAfterFormFilled()` against the action's per-mount
`Schema` (catching `Halt`, `Cancel`, `ValidationException`), and only opens the
modal when `mountedActionShouldOpenModal()` says the resolved action actually
needs one (schema present, or explicit `modalHeading()`/description, etc.) —
otherwise it calls the action immediately. The port's
[`mountAction()`](../../../packages/actions/src/Concerns/HasActions.php)
**appends** a `name`/`arguments`/`context` descriptor to `$mountedActions`
(Filament's own push), seeds that frame's
[data bag slot](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor)
before anything can render, resolves the action (swallowing
`ActionNotResolvableException` into the unmount branch, Filament-same),
unmounts with `cancelParentActions: false` — popping just the frame it pushed,
leaving any parent frames intact — when it is `null` or `isDisabled()`, then runs
Filament's mount lifecycle in Filament's order: `callBeforeFormFilled()` (only
when the action has a schema) → `$action->mount(['form' => $schema, 'schema' =>
$schema])` (always) → `callAfterFormFilled()` (same gate), with `Halt` and
`Cancel` unmounting the frame and returning null. It opens the modal when
[`mountedActionShouldOpenModal()`](#the-modal-open-gate-is-null-safe)
says so — now with `checkForSchemaUsing:` supplied, so a schema is on its own
enough to warrant a modal — and otherwise returns `callMountedAction()`'s
result, matching Filament's shape and `mixed` return. Still absent: the
authorization check (`hasAuthorizationNotification()` /
`getAuthorizationResponseWithMessage()` and its unauthorized notification), the
`ExposesStateToActionData` child-schema validation (no such interface here), and
the `ValidationException` arm of the catch (nothing validates yet).
**Next step:** land `CanBeAuthorized` for the authorization branch; the
`ValidationException` arm lands with M1 · 1's validation work.

### `callMountedAction()` drops rate limiting, authorization, transactions, and redirects

Filament's `callMountedAction()` accepts an `$arguments` array to merge, checks
`isAuthorized()`, applies `getRateLimit()` via `WithRateLimiting::rateLimit()`
(sending a rate-limited notification and bailing on `TooManyRequestsException`),
opens a database transaction (`beginDatabaseTransaction()` /
`commitDatabaseTransaction()` / `rollBackDatabaseTransaction()`), merges schema
state from `ExposesStateToActionData` components and the action's own `Schema`
(`getState(afterValidate: …)`), calls `callBeforeFormValidated()` /
`callAfterFormValidated()`, dispatches `sendSuccessNotification()` +
`dispatchSuccessRedirect()` or `sendFailureNotification()` +
`dispatchFailureRedirect()` by status, calls `afterActionCalled()`, checks
`store($this)->has('redirect')`, and detects whether the action stack changed
underneath it (`replaceMountedAction()` mid-call) before deciding whether to
unmount. The port's
[`callMountedAction(array $arguments = [])`](../../../packages/actions/src/Concerns/HasActions.php)
gained two of those in this epic — the caller-supplied `$arguments` merge and
the replaced-stack guard. In full, it merges the caller's `#[LiveArg]` `$arguments`
into the **top** action via `mergeArguments()` and restores the action's
original arguments afterwards, resolves the record via
`getMountedActionRecord()`, checks `isDisabled()`, runs
`callBefore()` / `call()` / `callAfter()`, calls the `afterActionCalled()`
hook (Filament-exact position: after `callAfter()`, before the notification
branch, so `Halt`/`Cancel` skip it), sends a success or failure notification
by `getStatus()`, catches `Halt` (keeps the action mounted) and `Cancel`
(falls through to unmount), then compares the stack it started with against
the current one — names and contexts only, arguments ignored, Filament-exact —
and returns without unmounting if a `replaceMountedAction()` swapped it
mid-call. It also lifts the mounted schema's state onto the action —
`$action->data($schema->getState())`, guarded by `mountedActionHasSchema()`, at
Filament's exact pipeline position immediately before `callBefore()`, so M1 · 1
can swap in `getState(afterValidate: …)` without moving anything else. Rate
limiting, authorization, database transactions and redirects are
all absent — none of the underlying `Action` concerns they depend on
(`CanBeRateLimited`, `CanBeAuthorized`, `CanUseDatabaseTransactions`,
`CanRedirect`) are composed yet either — as are the
`callBeforeFormValidated()` / `callAfterFormValidated()` hooks and the
`ExposesStateToActionData` state merge (no such interface here, so the merge
reduces to the schema's own state). Filament's sibling
`mergeMountedActionArguments()` — which writes merged arguments back into the
persisted top descriptor as well as the live `Action`, for a schema resubmit —
also stays unported; it is tracked on the contract's own row in
[interacts-with-actions.md](interacts-with-actions.md#schema-mount-plumbing-is-not-on-the-contract),
and this method's transient merge-and-restore is deliberately *not* it. **Next
step:** each returns with its `Action`-level concern; the `*FormValidated` hooks
and the `afterValidate` wrapper land with M1 · 1.

### `unmountAction()` drops the default-action reset and the error-bag reset

Filament's `unmountAction(bool|string|null $cancelParentActions = null)`
decides, per call, whether to pop one entry, clear the whole stack, or pop back
to a named parent action (`getParentActionToCancelTo()`/
`shouldCancelAllParentActions()`), trims `cachedMountedActions` to match, calls
`syncActionModals()` (a `dispatch('sync-action-modals', …)` with the new nesting
index and overlay flag), and on full-empty resets `defaultAction`/
`defaultActionArguments`/`defaultActionContext` to `[]` and calls
`resetErrorBag()`. The port's
[`unmountAction()`](../../../packages/actions/src/Concerns/HasActions.php)
ports the whole cascade: `false` pops one frame, `true` clears the stack,
`null` consults the mounted action's `shouldCancelAllParentActions()` and then
`getParentActionToCancelTo()`, and a string pops until that named parent has
itself been popped — plus the `cachedMountedActions` truncation loop that keeps
the positional cache from serving popped frames, and the matching drop of every
popped frame's [data bag slot](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor),
has-schema memo and cached `mountedActionSchema{$i}`. A `null` action (an
unresolvable stack) takes the pop-one branch, Filament-same.
What is still absent: the `defaultAction*` reset (those `#[Url]` properties are
[not ported](#url-bound-default-action-state-is-not-ported)) and
`resetErrorBag()` (nothing validates yet — the insertion point for epic 6).
`syncActionModals()` has no port and never will: see
[declarative choreography](#the-stack-is-choreographed-declaratively-not-by-client-events).
**Next step:** the `defaultAction*` reset lands with the `#[Url]` row; the
error-bag reset lands with epic 6's validation work.

### `getAction()` ports the generic method-discovery arm in full

Filament's `getAction(string | array $actions, bool $isMounting = true)`
accepts a single name or a list of nested action descriptors and resolves them
through `resolveActions()`, which branches on `context['schemaComponent']`
(via `resolveSchemaComponentAction()`, requiring `HasSchemas`),
`context['table']` (via `resolveTableAction()`, requiring `HasTable`, with
bulk/record-key handling), or the generic `resolveAction()`. The port's
[`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php)
now carries that generic arm Filament-exact: the `$cachedActions` lookup
first, then `"{$name}Action"` preferred over the bare `"{$name}"` method
(the suffixed form is skipped when the name itself already ends in `Action`),
then a `ReflectionMethod` return-type gate that refuses to invoke any method
whose declared return type does not include `Action` — and, when a
gate-passing factory returns a non-`Action` at runtime, throws
[`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php).
An unknown name still returns `null` (Filament-same for the no-context path).
The reflection-before-invoke order is load-bearing security-wise:
`mountAction()`'s `$name` is a client-writable `#[LiveArg]`, and the
return-type gate is what keeps `getAction($clientName)` from invoking
arbitrary host methods. `resolveAction()` adds Filament's blank-name guard
(throwing the same exception), and `mountAction()` swallows the exception
into its unmount branch, Filament-same. Still absent: the
array-of-descriptors form and the `$isMounting` flag — the nesting itself now
lives on [`resolveActions()`](#resolveactions-drops-the-table-branch-and-the-ismounting-schema-cache),
whose loop calls this method once per frame, and the table-scoped branch waits
on tables. **Next step:** the table branch returns
once `BelongsToTable` and the `tables` package land.

### `resolveActions()` drops the table branch and the `$isMounting` schema cache

Filament's `resolveActions(array $actions, bool $isMounting = true)` walks the
stack descriptors root-to-leaf, passing the growing resolved list to each frame
as its parent chain, and per frame: throws on a blank name, dispatches on
`context['schemaComponent']` → `resolveSchemaComponentAction()`,
`context['table']` (when the host `instanceof HasTable`) →
`resolveTableAction()`, else `resolveAction()`; merges the descriptor's
arguments; stamps `nestingIndex()`; calls `boot()`; and, while `$isMounting`,
caches a per-frame `Schema` under the key `mountedActionSchema{$i}`.
The port's [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php)
carries the walk, the blank-name throw, the schema-component dispatch, the
argument merge, and the `nestingIndex()` stamp; the parent chain reaches
[`resolveAction(array $descriptor, array $parentActions)`](../../../packages/actions/src/Concerns/HasActions.php),
which is Filament-exact — a non-empty chain looks up
`array_last($parentActions)->getModalAction($name)` and throws
`ActionNotResolvableException` naming both actions when the parent declares no
such modal action, and an empty chain delegates to `getAction()`. Two behavioral
deltas are permanent and written up below
([null-frame stop and root-only schema context](#resolution-stops-at-the-first-null-frame-and-only-the-root-frame-may-use-schema-context),
[no `boot()`](#no-resolved-action-is-booted)).
What is genuinely absent: the `context['table']` branch (no `tables` package,
no `BelongsToTable`), and the `$isMounting` parameter together with the
`cacheSchema("mountedActionSchema{$i}", …)` call it gates. The per-frame schema
itself is ported, just not from this loop: `getMountedActionSchema()` builds and
caches it on demand, so the resolution walk stays a pure name→`Action`
resolution. **Next step:** the table branch with `tables`; `$isMounting` has no
consumer once the schema build is demand-driven.

### The per-mount schema carries no model or operation

`getMountedActionSchema()` builds
`$mountedAction->getSchema($this->makeSchema()->key("mountedActionSchema{$i}")->statePath("mountedActionsData.{$i}")->rootHeadingLevel(3))`,
caches it under that key via `cacheSchema()`, and serves later reads from the
cache. Filament additionally seeds `->model(…)` from the action's
record/model/schema-component chain and `->operation(…)` from the dotted names
of the mounted stack. Neither is ported: schemas has no model binding and no
`Operation` concept, so there is nothing for either value to reach.
`getMountedActionSchemaName()` and `getMountedActionSchemaModel()` have no port
for the same reason — the name is computed inline where it is used, and the
model hook has no consumer. One guard is Sylament-only: a schema-bearing action
mounted on a host that is not an `InteractsWithSchemas` throws a
`LogicException` naming the missing contract and trait, because the schema has
nowhere to be built; a schema-less action mounts there normally, and the two
cases are told apart by probing the action against a detached `Schema`.
**Next step:** revisit with the Doctrine binding epic.

### `#[Url]`-bound default-action state is not ported

Filament exposes `defaultAction`, `defaultActionArguments`,
`defaultActionContext`, `defaultTableAction`, `defaultTableActionRecord`, and
`defaultTableActionArguments` as `#[Url(as: …)]`-bound public properties, so an
action can be pre-mounted from the query string on page load (and
`unmountAction()` resets them to `[]` rather than `null` to avoid Livewire's
`'null'`-string URL bug). None of these properties, nor any code that reads
them to auto-mount an action, exist in the port. **Next step:** todo, no
current consumer; would need the UX LiveComponent `#[LiveProp]`/query-string
binding equivalent of Livewire's `#[Url]`.

### The `getDefaultAction*` host override hooks are not ported

Filament declares eleven `getDefaultAction*(Action $action)` methods
(`Record`, `Title`, `Model`, `ModelLabel`, `Url`, `SuccessRedirectUrl`,
`FailureRedirectUrl`, `Relationship`, `SchemaResolver`,
`AuthorizationResponse`, `IndividualRecordAuthorizationResponseResolver`), all
returning `null` by default — extension points a host page overrides to supply
context-free defaults to any action it mounts. None are declared on
`HasActions`; nothing in the port currently calls for them since the `Action`
concerns that would consume them (`CanBeAuthorized`, `CanRedirect`, relation
managers, …) are not ported either. **Next step:** todo, add alongside the
`Action`-level concerns that read them.

### Partial re-render is not ported

Filament's `forceRender()` (`app(PartialsComponentHook::class)->forceRender($this)`)
and `partiallyRenderActionParentSchema()` walk up an action's schema/component
ancestry to Livewire-partially-render just the affected schema after a
successful `callMountedAction()`, avoiding a full-page re-render. Sylament has
no `PartialsComponentHook` equivalent anywhere in the codebase — LiveComponent's
own re-render model is used unmodified, i.e. every action call re-renders
however LiveComponent normally would. **Blocked** on schemas ever building a
partial-render mechanism; low priority until render cost becomes a concern.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

### Record resolution is a single host callback, not per-source

Filament never resolves a mounted action's record generically: the table
branch of `resolveActions()` looks it up via `getTableRecord($recordKey)`
(`resolveTableAction()`), the schema-component branch delegates to the schema,
and the plain generic branch (`resolveAction()`) sets no record at all —
callers of `Action::make()` outside a table/schema context set one manually.
Sylament, having neither tables nor schemas wired into `HasActions` yet, adds
a single generic seam instead:
[`resolveRecordUsing(?Closure $callback)`](../../../packages/actions/src/Concerns/HasActions.php)
lets the host register one resolver, and
[`getMountedActionRecord()`](../../../packages/actions/src/Concerns/HasActions.php)
reads the **top** frame's `context['recordKey']` and runs it through that
callback — throwing `LogicException` if a `recordKey` is present with no
resolver registered. The top-frame read serves the root frame: a nested frame's
`Action::getContext()` emits no `recordKey`, so this seam has nothing to read for
one. A nested action carries no record of its own from this seam — it inherits
its parent's record by object propagation through
[`prepareModalAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php),
which `callMountedAction()` must leave standing when the child frame's own context
resolves to nothing, rather than stamping a `null` over it. This is a deliberate MVP bridge, not a mistake to
reverse: it is what lets a plain host page (no table, no schema) mount a
record-bound confirm action today. When table/schema action resolution lands,
each will likely supply its own record via this same seam or a dedicated one;
either way this stays as the generic fallback for hosts with neither.

### Schema-context resolution returns null instead of throwing

Filament folds every schema-context resolution failure — unknown
table/schema/action, host missing the schemas contract — into the same
swallowable `ActionNotResolvableException` its mount pipeline catches. The
port ships that exception class for the generic arm (blank descriptor names,
lying factories — see
[getAction()](#getaction-ports-the-generic-method-discovery-arm-in-full)),
but the schema-context path keeps its null-miss shape: a miss is a `null`
return that `mountAction()`'s null branch turns into an unmount — the same
observable outcome without the internal-control-flow exception. Two consequences worth pinning. First, a host that receives a
`schemaComponent` context without implementing schemas'
[`InteractsWithSchemas`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php)
is a wiring bug, not a client-data miss, so
[`resolveSchemaComponentAction()`](../../../packages/actions/src/Concerns/HasActions.php)
throws a `LogicException` naming both fixes (implement the contract, compose
the `HasSchemas` trait) where Filament folds the case into the same swallowable
exception. Second, an unknown schema *name* must stay a miss, but schemas'
`HasSchemas::getSchema()` throws where Filament's returns `null` — so the
resolver ports the discovery guard of Filament's `cacheSchema()` and returns
`null` without calling `getSchema()` unless the candidate `{$name}()` /
`{$name}Schema()` method exists **and** is schema-shaped: first parameter typed
`Schema`, or parameterless with a `Schema` return type. The shape half is
load-bearing, not pedantry — the context key is a client-writable `#[LiveArg]`
and `getSchema()` invokes whatever method discovery finds, so a bare
`method_exists` check would let a crafted context remotely invoke any host
method (`unmountAction`, `callMountedAction` — which recurses — or a consumer's
parameterless `save()`); Filament no-ops all of these, and the guard makes the
port miss exactly where Filament's `cacheSchema()` misses. A crash inside a
real schema-builder method still propagates loud instead of being swallowed by
a catch. The guard duplicates discovery knowledge actions-side because
`getSchema()` deliberately stays permissive for host-side callers (an untyped
builder parameter works when the host calls it directly); if the trait's
resolution rules ever change, both sites must move together. The dropped `$parentActions` parameter is not part of this divergence:
Filament accepts and ignores it on the schema branch, and the port's caller
[only reaches this resolver for the root frame](#resolution-stops-at-the-first-null-frame-and-only-the-root-frame-may-use-schema-context),
where the parent chain is empty by construction. Reverses if a frame ever needs a
typed failure signal the null return cannot carry; the schema path would adopt the
now-existing exception class then.

### `getMountedAction()` never guards its own read with a `catch`

Filament wraps most `getMountedAction()` call sites in
`try { … } catch (ActionNotResolvableException)`, because its resolvers throw
on every miss. The port swallows that exception in exactly one place —
[`cacheMountedActions()`](../../../packages/actions/src/Concerns/HasActions.php),
which caches an unresolvable stack as `[]` — so `getMountedAction()` itself
returns `null` rather than throwing, and the `catch` Filament writes around it
in `unmountAction()` would be provably dead code under PHPStan level max. The
guard is therefore absent, not forgotten; the note lives in the method body so
a future reader does not re-add it. Everything else about the method is
Filament's: the optional `$actionNestingIndex` defaults to the top of the
stack, a warm positional `$cachedMountedActions` entry short-circuits, and a
cold read resolves the prefix `array_slice($mountedActions, 0, $index + 1)`
so a deeper frame still resolves through its parents.

### The modal-open gate is null-safe

[`mountedActionShouldOpenModal(?Action $mountedAction = null): bool`](../../../packages/actions/src/Concerns/HasActions.php)
delegates to `Action::shouldOpenModal()` — Filament's own body — so modal
presence (`modal()` / `modalHidden()`), a custom heading, and a description
(including the confirmation-string fallback that makes `requiresConfirmation()`
imply a modal, Filament-exact) all flow through the single gate both
`mountAction()` and the
[Modals renderer](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)
read, so the rule cannot drift between the two. It is on the
[`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
contract, because that renderer calls it on an abstractly-typed host. Two
One delta from Filament: the port is null-safe — it returns `false` instead of
fataling when nothing is mounted, so a template can call it bare. The
`checkForSchemaUsing:` argument is now supplied
(`$this->mountedActionHasSchema(...)`, Filament-exact), so an action whose only
modal content is its schema opens a modal.
The renderer keeps passing the already-resolved action in
(`mountedActionShouldOpenModal(action)`), still the cheaper call since it
skips a redundant lookup.

### Only the per-name action cache is ported

Filament populates `$cachedActions` from three sources: `cacheAction()`
(called by `resolveAction()` on every method-resolved action, and directly by
external registrars), `mergeCachedActions()` (bulk registration), and
`cacheTraitActions()` (a `class_uses_recursive()` walk on Livewire boot that
lets sibling `*Actions`-suffixed traits register their own actions). The port
ships [`cacheAction()`](../../../packages/actions/src/Concerns/HasActions.php)
and the `$cachedActions` property — `getAction()` consults the cache before
calling the `{name}Action()` factory and caches what it resolves, so the
trigger button and the confirm modal now share one `Action` instance per name
per request. `mergeCachedActions()` and `cacheTraitActions()` are **skipped**:
both are bulk-registration mechanisms with no Sylament consumer (no
`*Actions` sibling traits exist), and `cacheTraitActions()` additionally rides
Livewire's boot hook, which has
[no equivalent here](#there-is-no-boot-hook-or-rate-limiting-trait). Both
return with their first bulk consumer (e.g. a table-actions trait).

### The host id is a round-tripped LiveProp, not a framework component id

Filament scopes its modal id with `$this->getId()` — the Livewire component's
own runtime id, which Livewire mints and round-trips for free. UX LiveComponent
exposes no equivalent stable per-instance id to a template, so the port mints
its own: [`getActionsHostId()`](../../../packages/actions/src/Concerns/HasActions.php)
lazily fills a `#[LiveProp] ?string $actionsHostId` with
`bin2hex(random_bytes(4))` and returns it, so the same host keeps the same id
across every re-render of a live session. This is the id-supply half of the
[modal id](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper);
it exists only because the framework does not supply one, and it is the first
thing to delete if UX LiveComponent ever exposes a stable component id.

A same-named-getter/LiveProp pair is exactly what forced the
[dehydration bridge](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method)
on `$mountedActions`, and the collision exists here too — Symfony's
`PropertyAccessor` reads `actionsHostId` through `getActionsHostId()`. It is
benign here and deliberately left alone: the getter returns the same `string`
the property holds (filling it as a side effect), so dehydrating through the
getter round-trips the identical value, verified by a hydrate/dehydrate test
rather than by inspection.

### A frame carries two identities: an index-scoped dialog and a name-scoped wrapper

Filament computes one id, inline in
[`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php):
`"fi-{$this->getId()}-action-{$action->getNestingIndex()}"` — host plus nesting
index. The port's
[`getMountedModalId(?int $actionNestingIndex = null)`](../../../packages/actions/src/Concerns/HasActions.php)
is the same shape, `"sy-{$this->getActionsHostId()}-action-{$actionNestingIndex}"`,
defaulting the index to the top of the stack; it is on the
[`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
contract despite having no Filament counterpart, because the shipped
[Modals renderer](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)
calls it on an abstractly-typed host. Two hosts on one page no longer collide,
and neither do two frames of one stack. One sharp edge: on an **empty** stack
the default index is `0`, so the bare call returns a real frame-0 id rather
than a non-colliding sentinel. That is what `unmountAction()` wants — it reads
the id *before* popping, so the close event names the frame being closed — but
a future caller that asks for "the current modal id" while nothing is mounted
gets a plausible-looking string, not a miss.

Sylament adds a **second** id that Filament has no need for.
[`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig)
wraps each dialog in a `<div id="{{ id }}-w-{{ action.getName }}">` that carries
the frame's `dismissed → unmountAction` binding, because the binding cannot sit
on the component tag itself (a caller `data-action` would replace the dialog's
own Stimulus wiring inside `attributes.defaults()`) and the event bubbles.

The **index**-scoped dialog id is the load-bearing one: an unchanged frame keeps
the same id across re-renders, so Idiomorph matches it and it morphs in place,
keeping its native `open` state instead of being replaced and re-animated. The
wrapper's **name**-scoped id is a per-frame unique id and nothing more — it is
explicitly *not* a subtree-swap trigger, because Idiomorph matches by id-set
**intersection** and the unchanged dialog id inside keeps the wrapper matched
even when the action name changes. That is harmless: a morphed node has its
attributes synced from the incoming markup, so a `replaceMountedAction()`
at the same index rewrites the wrapper's `cancelParentActions` argument for the
replacing action anyway. The comment in that template used to teach the wrong
model and now says this; do not re-derive the old one.

Filament needs neither id: its close chain is computed client-side per open
modal rather than baked into markup.

### The parent-overlay flag is server-stamped, not a sibling CSS selector

Filament suppresses the dim of a modal that has an open modal after it with a
`:has(~ .fi-modal-open)` sibling rule — legal there because its modals are
plain `<div>`s stacked in DOM order. A native `<dialog>` promoted by
`showModal()` paints in **top-layer** order, which is `showModal()` **call**
order and has no relationship to DOM order, so no sibling selector can express
"is there a dialog above me". The port computes the answer server-side instead:
`Modals.html.twig` passes `:overlaid="not loop.last"` to every non-top frame,
`ActionModal` narrows it to `overlaid and open`, and support's `Modal` stamps
`sy-modal-overlaid`, whose CSS zeroes that dialog's own background so only the
topmost frame paints a scrim. The hand-off animates for free, since
`background-color` is already in the container's transition list.

### There is no boot hook or rate-limiting trait

Filament's `bootedInteractsWithActions()` does two things: it restores
`$originallyMountedActionIndex` from the stack on every Livewire boot, and,
when the host is not `HasTable`, re-derives `$cachedMountedActions`, clearing
`mountedActions` if none resolve. Sylament's `HasActions` declares no boot
method — UX LiveComponent has no component boot lifecycle — so the half that
matters is re-homed onto a hydration hook instead (see
[the stale-stack self-heal](#the-stale-stack-self-heal-is-a-posthydrate-hook-not-a-boot-hook)),
and `$originallyMountedActionIndex` is simply
[not ported](#originallymountedactionindex-and-getoriginallymountedactionindex-are-not-ported).
Likewise,
Filament composes Laravel's `DanHarrin\LivewireRateLimiting\WithRateLimiting`
trait for `callMountedAction()`'s rate-limit check; no Symfony equivalent is
pulled in, and rate limiting is out of scope until `CanBeRateLimited` (already
`todo` on the `Action` side) lands. The boot **hook** and the rate-limiting
trait are both **skipped** rather than `todo`/`blocked` — reversible the moment
an `Action` rate-limit actually needs one.

### The stale-stack self-heal is a `#[PostHydrate]` hook, not a boot hook

The consistency check Filament runs on boot is real work here too: the
`$mountedActions` LiveProp round-trips through the browser, so a stack that no
longer resolves (a renamed action, a changed schema, a redeployed app) would
otherwise render a broken modal chain. With no boot lifecycle to hang it on,
the port uses UX LiveComponent's own
[`#[PostHydrate]`](../../../packages/actions/src/Concerns/HasActions.php)
attribute: `afterMountedActionsHydrated()` returns immediately on an empty
stack and otherwise resolves it once, clearing `$mountedActions` when nothing
resolves. Same guarantee, framework-idiomatic placement, and the resolution it
performs warms `$cachedMountedActions` for the rest of the request rather than
being thrown away. Filament's `! $this instanceof HasTable` guard has no port —
there is no tables package to exempt.

### `$originallyMountedActionIndex` and `getOriginallyMountedActionIndex()` are not ported

Filament tracks which frame was mounted first so a `replaceMountedAction()`
chain can tell "the action the user started" from "the action currently on
top", and restores that index on boot. Nothing in the port reads it: the
cancel cascade addresses parents by **name**
(`getParentActionToCancelTo()`) or by "all", never by a remembered index. Not
ported, and no consumer is in sight; it returns with whichever feature actually
needs to name the originating frame.

### The mounted-action stack is a server-controlled LiveProp, not a client-writable array

Filament's `$mountedActions` is a client-writable Livewire property whose
integrity rides on a checksum round-tripped from the browser. Sylament's
[`$mountedActions`](../../../packages/actions/src/Concerns/HasActions.php) is a
`#[LiveProp]` with **no `writable: true`**: each frame's descriptor
(`name`/`arguments`/`context`) is written only from inside the
`mountAction()` / `unmountAction()` / `replaceMountedAction()` methods, never
from the client. A confirm action therefore carries no user-editable payload
and there is no tampering surface to guard for free — the client can still name
an action, but only through `mountAction`'s `#[LiveArg]`, which the
[return-type gate](#getaction-ports-the-generic-method-discovery-arm-in-full)
and the [root-only schema-context rule](#resolution-stops-at-the-first-null-frame-and-only-the-root-frame-may-use-schema-context)
both police. Epic 6 reversed this **deliberately but narrowly**: a form-in-modal
needs client-writable per-frame form state, so a *sibling* bag got
`writable: true` while the descriptor stack itself stayed server-controlled —
see [the sibling-bag divergence](#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor).

### Per-mount form state lives in a sibling bag, not in the stack descriptor

Filament binds each mounted action's form at `mountedActions.{$i}.data`, a key
inside the stack descriptor. The port binds `mountedActionsData.{$i}` instead,
because `Sylament\Schemas\State\PropertyAccessor` translates the dotted path
into the Symfony property path `mountedActions[0][data][title]`, and
`ReflectionExtractor::getReadInfo()` resolves that first segment through
`getMountedActions()` — a getter returning resolved `Action` objects — so every
read and write raises `NoSuchIndexException`. The same getter-precedence rule
already forces [the `dehydrateWith:` bridge](#the-mountedactions-liveprop-dehydrates-through-a-bridge-method);
Livewire has no such rule, which is why Filament can nest the two. The rejected
alternative was `writable: true` on `$mountedActions` itself, which
`LiveComponentHydrator::calculateWritablePaths()` would extend to `name`,
`arguments` and `context` — a privilege escalation with no authorization port to
compensate for it.

Consequences, all deliberate: the descriptor stack stays a server-controlled
`#[LiveProp]` while the data bag is `writable: true` (the narrowest possible
reversal of [that divergence](#the-mounted-action-stack-is-a-server-controlled-liveprop-not-a-client-writable-array) —
form state only); presence of a frame in the bag is never evidence of a mount,
`$mountedActions` alone is; and the bag is hand-maintained in lockstep —
`mountAction()` seeds `[$i] = []` before the frame can render, `unmountAction()`
drops every index the stack no longer has, `replaceMountedAction()` clears it.
That last point is load-bearing rather than tidiness: `live_controller.js`'s
`getDeepData()` dereferences every `[data-model]` path segment unguarded after
every render, so a field rendered at `mountedActionsData.0.title` with no index
`0` in the props is an uncaught `TypeError` that kills live updates for the
entire component. Because the bag is client-writable, both purges key off the
**index value** against the server-controlled stack rather than off the bag's own
key order, which a client could scramble. Reading the bag goes through
`getMountedActionData(?int)` — singular, so it can never collide with the
property the way `getMountedActions()` does, and adding a plural
`getMountedActionsData()` would reintroduce the exact trap this bag exists to
route around.

### The `$mountedActions` LiveProp dehydrates through a bridge method

`#[LiveProp(dehydrateWith: 'dehydrateMountedActions')]` looks like ceremony and
is not: without it, adding `getMountedActions()` silently breaks dehydration.
Symfony's `PropertyAccessor` resolves a property read by trying a same-named
**getter first** (`ReflectionExtractor::getReadInfo()` checks `get`/`is`/`has`/
`can` prefixes before the property), and
[`LiveComponentHydrator::dehydrate()`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php)
reads every `#[LiveProp]` through that accessor — so `$mountedActions` would
dehydrate as the **resolved `Action` objects** `getMountedActions()` returns
rather than the wire-safe descriptor array. `dehydrateWith:` names a method
that returns the raw property, and the vendor's own exception message points at
that attribute as the remedy.

This has no Filament analogue: Filament has the identical property/getter name
pair, but Livewire has no getter-precedence convention, so it never collides.
It is the first occurrence of this Symfony-specific translation gotcha in the
repo, and it is load-bearing rather than decorative — **do not delete it** when
the pair looks redundant. Two consequences worth knowing: `getMountedActions()`
runs on every dehydrate and its result is discarded (cheap — the cache is
usually warm), and this prop must never gain `writable: true` while the getter
exists, because the hydration side would have the same ambiguity in reverse.
The same collision exists benignly on
[`$actionsHostId`](#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id).

### The stack is choreographed declaratively, not by client events

This is the headline divergence of the nested-actions work, and the one that
explains most of the others.

Filament coordinates a modal stack from the client. `unmountAction()`
dispatches `sync-action-modals` with the new nesting index and an overlay flag;
a JS object (`filamentActionModals`) keeps the stack state, and the modals
themselves are opened and closed through `open-modal` / `close-modal` /
`close-modal-quietly` events — the "quietly" variant existing purely to
suppress a Livewire re-render round-trip.

Sylament has no client stack state at all. Which frames exist is
`$mountedActions`; whether a given frame's dialog is open is computed on the
server by
[`isMountedActionModalOpen(int $actionNestingIndex)`](../../../packages/actions/src/Concerns/HasActions.php)
and rendered into the markup as both a Stimulus value and the native `open`
attribute. The rule it implements: the **top** frame is open when its own
`mountedActionShouldOpenModal()` holds; a **parent** frame stays open unless
some deeper frame both opens a modal of its own *and* does not declare
`overlayParentActions()`. The "opens a modal of its own" qualifier is
load-bearing — without it, an `Action::halt()` on a modal-less child would
close the parent modal that `halt()` exists to keep open.

Consequences, each of which is why a Filament mechanism has no port here:

- **No `sync-action-modals` dispatch, and no `syncActionModals()` method.**
  The state it carries is already in the render.
- **No `open-modal` / `close-modal-quietly` chain.** `unmountAction()` still
  dispatches one `sylament-close-modal` for the frame it started on, and
  `mountAction()` one `sylament-open-modal`, but neither is authoritative —
  they are kept for imperative callers, and the controller guards make value
  and event idempotent (see
  [support/modal.md](../support/modal.md#the-open-state-is-a-stimulus-value-not-only-an-event)).
- **`isMountedActionModalOpen()` is Sylament-only** and sits on the
  [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
  contract, the same way `renderAction()` and `getMountedModalId()` are
  Sylament-only host obligations.
- **Client dismissal is the one client→server edge that remains.** A close
  button, ESC, click-away, or a `close()` action closes the dialog in the
  browser; the controller's `dismissed` event round-trips `unmountAction`
  with that frame's `cancelParentActions` value baked into the markup.

Nothing reverses this. Event-driven opening cannot work for a dialog created by
the response that asks it to open, which is every freshly mounted frame.

### Resolution stops at the first null frame, and only the root frame may use schema context

Two deltas in
[`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php) that
only make sense together.

Filament `continue`s past a frame that resolves to nothing; the port `break`s.
The difference matters because the two codebases fail differently: Filament's
resolvers **throw** on a miss, so a `null` there is near-unreachable, while the
port's [schema-context resolver returns `null`](#schema-context-resolution-returns-null-instead-of-throwing)
and `getAction()` returns `null` for an unknown name. Continuing past a null
frame would hand the *next* frame an empty parent chain, and an empty parent
chain means "resolve against the host" — so a client-named child frame could
re-root itself as a top-level host action. A stack with a hole is never valid
anyway, so stopping is both safer and equivalent for every case Filament can
actually reach. The observable effect: the resolved list is either empty or the
same length as the stack it was given — deeper frames throw rather than return
`null`, so no other shortfall exists.

The schema-context branch is gated on `$resolvedActions === []`, i.e. the root
frame only. Filament branches on the context key alone and can afford to,
because its `Action::getContext()` never emits `schemaComponent` for a child
frame (the port's `getContext()` has
[the same early return](action.md#getcontext-emits-only-the-schemacomponent-key)).
But `context` is a client-supplied `#[LiveArg]`, so a hand-built descriptor
could carry both a `schemaComponent` key and a nesting position; the explicit
gate is what makes "a nested frame always descends through its parent's
declared modal actions" true by construction rather than by the good behavior
of the client. Together with the `break` above, these two lines are the whole
guarantee that a deeper frame can never resolve against the host.

### No resolved action is `boot()`ed

Filament calls `$resolvedAction->boot()` on every frame in `resolveActions()`,
part of its `CanBeBooted` concern (a per-instance `booted` flag and a queue of
boot callbacks). Neither the concern nor any component boot lifecycle exists in
Sylament — `CanBeBooted` is `todo` in the
[package README](README.md#features) — so the call has no target to make. It
returns with `CanBeBooted`, in the same loop position.

### The modals renderer resolves its host from the render stack, not from template scope

Filament's `modals.blade.php` reads `$this` because a Blade component inherits the
Livewire component's scope. A Twig component gets a fresh context: ux-twig-component
spreads the caller's context in and then **overwrites** `this` with the component
being rendered ([`ComponentRenderer::preRender()`](../../../vendor/symfony/ux-twig-component/src/ComponentRenderer.php)),
so inside `<twig:…>` `this` is that component, never the host. The two implicit
Twig equivalents — `outerScope.this`, or staying an `{% include %}` — both bind to
whatever render context encloses the tag, which is the host **only** when the tag
sits directly in the host's own template; nest it in any other Twig component and
they silently bind to that component's `AnonymousComponent`. (The
[`NestedActionHost`](../../../packages/actions/tests/Fixtures/Twig/templates/components/NestedActionHost.html.twig)
fixture keeps its trigger button outside the `Section` for exactly this reason —
`this.deleteAction()` cannot resolve in there.)

So `Modals` is the repo's first **class-based** Twig component (every other one is
an anonymous template): [`getLiveComponent()`](../../../packages/actions/src/Twig/Components/Modals.php)
walks `Symfony\UX\TwigComponent\ComponentStack` innermost-first to the nearest
`InteractsWithActions`, skipping frames that are not hosts. That keeps Filament's
zero-argument call site — `<twig:SylamentActions:Modals />` against
`<x-filament-actions::modals />` — and stays correct at any nesting depth. An
optional `:liveComponent="this"` prop overrides the walk, named for the vocabulary
`BelongsToLiveComponent` / `Action::getLiveComponent()` already use for this object.

`ComponentStack` is marked `@internal` in ux-twig-component — a real maintenance
liability, accepted deliberately because the two supported alternatives are
silently position-dependent. It is contained: the walk is one method, so a vendor
change has a single repair site, and
[`ModalsTest`](../../../packages/actions/tests/Unit/Twig/Components/ModalsTest.php)
pins the contract against a hand-built stack so a break fails loudly in Unit rather
than subtly in a render.

### The modal is opened by rendered state, not by the dispatched event

`mountAction()` dispatches `sylament-open-modal`, but that event alone cannot open
a modal the same response is rendering for the first time: UX LiveComponent's
`processRerender()` morphs the DOM and dispatches queued browser events
synchronously in one task, while Stimulus binds `data-action` listeners from a
`MutationObserver` microtask that runs after it — so the new `<dialog>` is not yet
listening. The authoritative signal is therefore the markup: the renderer passes
each frame's server-computed
[`isMountedActionModalOpen()`](#the-stack-is-choreographed-declaratively-not-by-client-events)
through `ActionModal` into `<twig:SylamentSupport:Modal>`, which renders it as
both the Stimulus `open` value and the native `open` attribute, and the
controller opens itself from that value. The dispatch stays for
imperative callers and is harmless — the controller guards on `element.open`, so
value and event are idempotent. Full reasoning in
[support/modal.md](../support/modal.md#the-open-state-is-a-stimulus-value-not-only-an-event).

Note the two-way split this creates: a **parent** frame under a non-overlaying
child renders `open = false` while staying in the DOM, so it can morph back open
in place when the child unmounts, and a modal-less top frame (an
`Action::halt()` mid-flow) renders no dialog at all while its parent stays open.

### The modals renderer emits no wrapper element

Filament wraps its loop in `<div wire:partial="action-modals" x-data="filamentActionModals({ livewireId })" style="height: 0">`
— a partial-render target, an Alpine data component, and an inline height so the
always-present div costs no layout. Sylament's renderer emits the frames' modals
or nothing at all. All three reasons for the wrapper are absent: partial
re-render is
[not ported](#partial-re-render-is-not-ported), the open/close behavior lives on
the `sylament--support--modal` Stimulus controller attached to each `<dialog>`
itself rather than an ancestor, and a native `<dialog>` is `display: none` until
opened so it needs no layout-neutral host — the last point being why a stack of
closed parent frames costs nothing to keep rendered. Reverses only if a
partial-render mechanism lands and needs a stable DOM anchor.

### There is no single-render guard

Filament's `modals.blade.php` short-circuits on a `hasActionsModalRendered` flag
(a `protected bool` on the trait, set from inside the view) so that the component
renders at most once per request. It needs that because
`<x-filament-actions::modals />` is baked into shared panel layouts —
[`page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php),
`topbar.blade.php`, `sidebar.blade.php` — which can nest, so a page can include it
several times without asking. Sylament has no `panels` package and therefore no
layout that places the tag implicitly: every call site is a host author writing the
tag, and writing it twice would emit two full stacks of `<dialog>`s sharing the
same [host- and index-scoped ids](#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper) —
the ids are unique per host and frame, not per *rendering* of the tag.
No flag is ported, and none is on `HasActions`. Reverses with `panels`, if a shared
layout ever places the tag on the author's behalf.

### An unresolvable host throws instead of rendering nothing

Filament guards with `@if ($this instanceof \Filament\Actions\Contracts\HasActions …)`,
silently rendering nothing when the surrounding Livewire component is not an action
host — again because the shared panel layouts render it for every component,
host or not. With no such layout here, every occurrence of the tag is deliberate,
so `getLiveComponent()` throws a `LogicException` naming both fixes (place it inside
a host, or pass `:liveComponent`) rather than rendering nothing. Silence is the
worse failure for the problem this component exists to solve — a modal that never
appears and says nothing about why. Reverses with `panels` if a shared layout needs
the tolerant form; at that point the guard belongs in the layout, not here.

_See also:_ the [rename table](../renames.md) for the `Has*` / `InteractsWith*`
**swap** this whole file is the Sylament side of, and the systematic
Livewire → UX LiveComponent translation (`ComponentToolsTrait`,
`#[LiveAction]`/`#[LiveArg]`/`#[LiveProp]`, `dispatchBrowserEvent()`) this trait
builds on — neither is a divergence.
