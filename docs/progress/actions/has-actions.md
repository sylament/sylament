# HasActions (actions)

`Sylament\Actions\Concerns\HasActions` — the host-side trait mixed onto the
UX LiveComponent page that mounts and calls `Action`s (the `Sylament\Actions\Contracts\InteractsWithActions`
implementor); it is the **swap** counterpart of Filament's `Concerns\InteractsWithActions`
trait (see the [rename table](../renames.md)). It composes `ComponentToolsTrait`
for `dispatchBrowserEvent()` and owns the single `#[LiveProp] $mountedActions`
slot, the `mountAction` / `callMountedAction` / `unmountAction` `#[LiveAction]`
trio, action lookup (`getAction`, `getMountedAction`, `resolveAction`) backed
by a per-name instance cache (`cacheAction`, `$cachedActions`), the
modal-open gate (`mountedActionShouldOpenModal`) and confirm-modal id helper,
and a host-supplied `resolveRecordUsing()` callback for resolving a mounted
action's record. Its rendered counterpart —
[`<twig:SylamentActions:Modals />`](../../../packages/actions/templates/components/Modals.html.twig)
plus the [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php)
component class behind it — folds in here rather than earning its own file,
because it is a pure view over three of those methods (`getMountedAction` /
`mountedActionShouldOpenModal` / `getMountedModalId`), the same relationship
`ConfirmModal` has to `Action`. Filament's `InteractsWithActions` is a
~870-line trait built around a **stack** of mounted actions (nested/parent
actions, per-action `Schema` mounting, authorization, rate limiting, database
transactions, redirects, URL-bound default actions, table/schema-scoped action
resolution); Sylament ships a lean **single-slot** MVP that covers the
confirmation-modal round-trip the `actions` epic spikes on plus the
schema-scoped resolution branch (`resolveSchemaComponentAction()`, from the
schemas↔actions integration) and defers the rest.
The single slot is why the renderer needs no loop: Filament's
`@foreach (getMountedActions()) @if (! $loop->last || mountedActionShouldOpenModal())`
runs 0-or-1 times here, and when it runs it *is* last, so the whole construct
collapses to a null check plus the gate.

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
| [`mountAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `mountAction()` | in progress | Action-schema integration, `CanBeAuthorized` | [detail](#mountaction-drops-authorization-schema-mounting-and-halt-handling) · [diverges](#the-mounted-action-slot-is-a-server-controlled-liveprop-not-a-client-writable-stack) |
| [`callMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `callMountedAction()` | in progress | `CanBeRateLimited`, `CanBeAuthorized`, `CanUseDatabaseTransactions`, `CanRedirect`, action-schema integration | [detail](#callmountedaction-drops-rate-limiting-authorization-transactions-and-redirects) |
| [`unmountAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `unmountAction()` | in progress | nested action stack (multi-slot `mountedActions`) | [detail](#unmountaction-has-no-cascade-because-there-is-no-stack) |
| [`getMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getMountedAction()` | done | — | [diverges](#lookup-methods-drop-nesting) |
| [`mountedActionShouldOpenModal()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `mountedActionShouldOpenModal()` | in progress | `CanOpenModal`'s `shouldOpenModal()`; `HasSchema` (actions) for the schema check | [detail](#the-modal-open-gate-is-confirmation-only) |
| [`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getAction()` + `resolveAction()`'s method-discovery arm | in progress | `BelongsToTable` (tables — only the table-scoped branch remains) | [detail](#getaction-ports-the-generic-method-discovery-arm-in-full) |
| [`resolveAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveAction()` + `resolveActions()`'s blank-name guard | in progress | nested action stack | [detail](#resolveaction-has-no-parent-nesting) |
| [`resolveSchemaComponentAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveSchemaComponentAction()` | done | — | [diverges](#schema-context-resolution-returns-null-instead-of-throwing) |
| Action caching ([`cacheAction()`](../../../packages/actions/src/Concerns/HasActions.php), `$cachedActions`) | `Concerns/InteractsWithActions.php` `cacheAction()` / `mergeCachedActions()` / `cacheTraitActions()` | done | — | [diverges](#only-the-per-name-action-cache-is-ported) |
| [`getMountedModalId()`](../../../packages/actions/src/Concerns/HasActions.php) | `action-modal.blade.php` inline `$actionModalId` | done | — | [diverges](#the-modal-id-is-name-only-not-instance-scoped-or-nesting-scoped) |
| Modals rendering ([`Modals.html.twig`](../../../packages/actions/templates/components/Modals.html.twig) + [`Twig\Components\Modals`](../../../packages/actions/src/Twig/Components/Modals.php)) | [`components/modals.blade.php`](../../../vendor/filament/actions/resources/views/components/modals.blade.php) | done | — | [diverges](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope) · [diverges](#the-modal-is-opened-by-rendered-state-not-by-the-dispatched-event) · [diverges](#the-modals-renderer-emits-no-wrapper-element) · [diverges](#there-is-no-single-render-guard) · [diverges](#an-unresolvable-host-throws-instead-of-rendering-nothing) |
| `replaceMountedAction()` | `Concerns/InteractsWithActions.php` `replaceMountedAction()` | todo | nested action stack | [detail](#replacemountedaction-is-not-ported) |
| Action-schema/form integration (`mountedActionHasSchema`, `getMountedActionSchema*`, deprecated `mountedActionHasForm`/`getMountedActionForm`) | `Concerns/InteractsWithActions.php` (same methods) | blocked | `HasSchema` (actions) + schemas/forms mounted-action wiring | [detail](#action-schema-integration-is-entirely-unported) |
| `#[Url]`-bound default-action state (`defaultAction`, `defaultActionArguments`, `defaultActionContext`, `defaultTableAction*`) | `Concerns/InteractsWithActions.php` public `#[Url]` properties | todo | — | [detail](#url-bound-default-action-state-is-not-ported) |
| `getDefaultAction*` host override hooks (Record/Title/Model/ModelLabel/Url/SuccessRedirectUrl/FailureRedirectUrl/Relationship/SchemaResolver/AuthorizationResponse/IndividualRecordAuthorizationResponseResolver) | `Concerns/InteractsWithActions.php` `getDefaultActionRecord()` etc. | todo | — | [detail](#the-getdefaultaction-host-override-hooks-are-not-ported) |
| `forceRender()` / `partiallyRenderActionParentSchema()` | `Concerns/InteractsWithActions.php` `forceRender()` / `partiallyRenderActionParentSchema()` | blocked | schemas partial-render infrastructure (`PartialsComponentHook`) | [detail](#partial-re-render-is-not-ported) |
| [`afterActionCalled()`](../../../packages/actions/src/Concerns/HasActions.php) hook (empty protected method; runs after `callAfter()`, before the notification branch, happy path only — Filament-exact) | `Concerns/InteractsWithActions.php` `afterActionCalled()` | done | — | — |
| `bootedInteractsWithActions()` boot wiring / `WithRateLimiting` | `Concerns/InteractsWithActions.php` `bootedInteractsWithActions()`, `DanHarrin\LivewireRateLimiting\WithRateLimiting` | skipped | — | [diverges](#there-is-no-boot-hook-or-rate-limiting-trait) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### `mountAction()` drops authorization, schema mounting, and Halt handling

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
[`mountAction()`](../../../packages/actions/src/Concerns/HasActions.php) sets
the single-slot descriptor, resolves the action (swallowing
`ActionNotResolvableException` into the unmount branch, Filament-same),
unmounts when it is `null` or `isDisabled()`, opens the modal when
[`mountedActionShouldOpenModal()`](#the-modal-open-gate-is-confirmation-only)
says so, and otherwise returns `callMountedAction()`'s result — matching
Filament's shape and `mixed` return. Still absent: the authorization check
(`hasAuthorizationNotification()` / `getAuthorizationResponseWithMessage()` and
its unauthorized notification), the schema mount/fill lifecycle
(`callBeforeFormFilled()` / `$action->mount([...])` / `callAfterFormFilled()`,
plus the `ExposesStateToActionData` child-schema validation), and the
`Halt`/`Cancel`/`ValidationException` handling that wraps them. **Next step:**
land `CanBeAuthorized` for the authorization branch; the schema-mount branch
waits on the actions/forms schema integration.

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
[`callMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php)
merges the single slot's `arguments`, resolves the record via
`getMountedActionRecord()`, checks `isDisabled()`, runs
`callBefore()` / `call()` / `callAfter()`, calls the `afterActionCalled()`
hook (Filament-exact position: after `callAfter()`, before the notification
branch, so `Halt`/`Cancel` skip it), sends a success or failure notification
by `getStatus()`, catches `Halt` (keeps the action mounted) and `Cancel`
(falls through to unmount), then always unmounts. Rate limiting,
authorization, database transactions, redirects, and schema/form data are
all absent — none of the underlying `Action` concerns they depend on
(`CanBeRateLimited`, `CanBeAuthorized`, `CanUseDatabaseTransactions`,
`CanRedirect`) are composed yet either. **Next
step:** each returns with its `Action`-level concern; the schema/form-data path
waits on the same action-schema integration as `mountAction()`.

### `unmountAction()` has no cascade because there is no stack

Filament's `unmountAction(bool|string|null $cancelParentActions = null)`
decides, per call, whether to pop one entry, clear the whole stack, or pop back
to a named parent action (`getParentActionToCancelTo()`/
`shouldCancelAllParentActions()`), trims `cachedMountedActions` to match, calls
`syncActionModals()` (a `dispatch('sync-action-modals', …)` with the new nesting
index and overlay flag), and on full-empty resets `defaultAction`/
`defaultActionArguments`/`defaultActionContext` to `[]` and calls
`resetErrorBag()`. The port's
[`unmountAction()`](../../../packages/actions/src/Concerns/HasActions.php)
takes no parameters, always clears the single slot, and dispatches one
`sylament-close-modal` event keyed by `getMountedModalId()` — there is no
parent/child cascade (nothing to cascade to with one slot), no
`cachedMountedActions` (none exists), no `defaultAction*` reset (those
properties are not ported), and no `resetErrorBag()` call. **Next step:**
returns in the same change that lands the multi-slot mounted-action stack for
nested actions.

### The modal-open gate is confirmation-only

[`mountedActionShouldOpenModal(?Action $mountedAction = null): bool`](../../../packages/actions/src/Concerns/HasActions.php)
is ported under Filament's name and signature, and is the single gate both
`mountAction()` and the [Modals renderer](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)
read, so the rule cannot drift between the two. It is on the
[`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
contract, because that renderer calls it on an abstractly-typed host.
The renderer passes the already-resolved action in
(`mountedActionShouldOpenModal(action)`) rather than calling it bare — a
habit from before the [per-name action cache](#only-the-per-name-action-cache-is-ported)
landed (the no-argument form now serves the same cached instance anyway), and
still the cheaper call since it skips a redundant lookup.
The body diverges: Filament delegates to
`$action->shouldOpenModal(checkForSchemaUsing: $this->mountedActionHasSchema(...))`,
which is `hasCustomModalHeading() || hasModalDescription() || hasModalContent()
|| hasModalContentFooter() || <has schema>` behind a `hasModal()` / `isModalHidden`
override pair — confirmation implies a modal there only because
`getModalDescription()` falls back to the confirmation string when
`isConfirmationRequired()`. The port returns `isConfirmationRequired()` outright,
because none of that chain exists: `CanOpenModal` ships no `shouldOpenModal()`,
no `hasModal()`/`isModalHidden`, no `hasCustomModalHeading()`/`hasModalDescription()`,
and no confirmation fallback in `getModalDescription()` (recorded on
[`action.md`'s CanOpenModal row](action.md#only-the-modal-content-config-is-ported-the-behavior-and-footer-action-surface-is-not)).
Observable gap: an action configured with only `modalHeading('…')` and no
`requiresConfirmation()` opens a modal in Filament and auto-runs here. It is
also null-safe where Filament's is not, so a template can call it with nothing
mounted. **Next step:** delegate to `Action::shouldOpenModal()` once `CanOpenModal`
grows it; the `checkForSchemaUsing:` argument arrives with the schema
integration below.

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
nesting/array-of-descriptors form, the `$isMounting` flag, and the
table-scoped resolution branch — the schema-scoped branch lives on
`resolveAction()`'s dispatch, below. **Next step:** the table branch returns
once `BelongsToTable` and the `tables` package land.

### `resolveAction()` has no parent nesting

Filament's `resolveAction(array $action, array $parentActions)` looks up a
parent action's `getModalAction($name)` when `$parentActions` is non-empty
(throwing `ActionNotResolvableException` when the parent has no such modal
action), otherwise checks `cachedActions` before falling back to
`{name}Action()`/`{name}`; its caller `resolveActions()` throws the same
exception for a blank descriptor name. The port's
[`resolveAction(array $descriptor)`](../../../packages/actions/src/Concerns/HasActions.php)
carries the blank-name guard itself (there is no `resolveActions()` loop over
a single slot), then dispatches a filled
`$descriptor['context']['schemaComponent']` to
`resolveSchemaComponentAction()` — preserving Filament's precedence (schema
context first, table context next once tables exist, host methods last) —
and finally delegates to `getAction()`, which owns the cache
check, the method discovery, and the
[lying-factory throw](#getaction-ports-the-generic-method-discovery-arm-in-full).
What is missing is the parent-action lookup — there is only ever one slot, so
no parent exists to look into. **Next step:** parent-action lookup returns
with the nested-action stack.


### `replaceMountedAction()` is not ported

Filament's `replaceMountedAction()` clears the stack and any cached
`mountedActionSchema*` entries, then re-mounts a new action in one call — used
when an action's own callback wants to hand off to a different action
mid-flow. Not ported; there is no equivalent on the single-slot port, and
nothing currently calls for it. **Next step:** returns with the nested-action
stack, since replacing "the" mounted action only means something once more
than one can be mounted at a time.

### Action-schema integration is entirely unported

Filament's `mountedActionHasSchema()` / `getMountedActionSchema()` /
`getMountedActionSchemaName()` / `getMountedActionSchemaModel()` (plus the
deprecated `mountedActionHasForm()` / `getMountedActionForm()` aliases) build
and cache a per-mounted-action `Schema` keyed by nesting index, seeded with the
action's record/model, state path `mountedActions.{index}.data`, and dotted
`operation` name; `mountedActionShouldOpenModal()` (ported
[confirmation-only](#the-modal-open-gate-is-confirmation-only)) consumes
`mountedActionHasSchema()` as its `checkForSchemaUsing:` argument. None of
this exists in the port — `Action` does not compose `HasSchema`, and
`HasActions` has no schema-building method at all; a mounted action's "form"
today is whatever the confirm-modal template renders (heading/description/icon
only). **Blocked** on porting `HasSchema` onto `Action` and wiring the
mounted-action `Schema` construction into `HasActions`, which in turn needs the
forms/schemas action integration point to exist.

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
reads `mountedActions[0]['context']['recordKey']` and runs it through that
callback — throwing `LogicException` if a `recordKey` is present with no
resolver registered. This is a deliberate MVP bridge, not a mistake to
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
Filament accepts and ignores it on the schema branch, and the single-slot port
has no parent frames to pass. Reverses if the nested-action stack ever needs a
typed failure signal to distinguish frames; the schema path would adopt the
now-existing exception class then.

### Lookup methods drop nesting

`getMountedAction()` in the port always reads slot `0`; Filament's version
takes an optional `$actionNestingIndex`, defaults it to the top of the stack,
and serves from a dedicated `$cachedMountedActions` array keyed by that index.
With a single slot there is nothing to key by nesting index, so no
`$cachedMountedActions` exists — resolution goes through `resolveAction()`,
whose `getAction()` call now serves repeated lookups from the per-name
[`$cachedActions`](#only-the-per-name-action-cache-is-ported) instead. This
divergence is a direct consequence of the single-slot MVP design, not an
independent decision, and resolves itself once the stack lands.

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

### The modal id is name-only, not instance-scoped or nesting-scoped

Filament computes the confirm-modal id inline in
[`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php)
as `"fi-{$this->getId()}-action-{$action->getNestingIndex()}"` — scoped to the
Livewire component's own id and the action's nesting index, so two live
components on the same page (or two nested actions) never collide. The port's
[`getMountedModalId()`](../../../packages/actions/src/Concerns/HasActions.php)
returns `"{$name}-modal"` — scoped only to the action name, which the
`HasActionsTest` render assertion covers for the single-host, single-slot case
the MVP targets. It is on the
[`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
contract despite having no Filament counterpart, because the shipped
[Modals renderer](#the-modals-renderer-resolves-its-host-from-the-render-stack-not-from-template-scope)
calls it on an abstractly-typed host.
Two `ActionHost`-style components on the same page mounting
an action of the same name would collide on the emitted DOM id. Acceptable
while every consumer mounts at most one live component with action modals per
page; reversible by folding the LiveComponent's own id into the string once a
multi-instance-per-page or nested-action scenario actually needs it.

### There is no boot hook or rate-limiting trait

Filament's `bootedInteractsWithActions()` restores
`$originallyMountedActionIndex` from the stack on every Livewire boot and, when
the host is not `HasTable`, re-derives `$cachedMountedActions` (clearing
`mountedActions` if none resolve) — a consistency check the persisted
`mountedActions` property needs across Livewire's serialize/hydrate cycle.
Sylament's `HasActions` declares no boot method at all; UX LiveComponent
re-runs `mountAction`/`callMountedAction` as fresh method calls rather than
hydrating a serialized public-property stack across requests the way Livewire
does, so there is currently nothing to re-derive on boot. Likewise,
Filament composes Laravel's `DanHarrin\LivewireRateLimiting\WithRateLimiting`
trait for `callMountedAction()`'s rate-limit check; no Symfony equivalent is
pulled in, and rate limiting is out of scope until `CanBeRateLimited` (already
`todo` on the `Action` side) lands. Both are **skipped** for now rather than
`todo`/`blocked`, since neither has an identified next consumer yet — reversible
the moment persisted cross-request mounted-action state, or an `Action`
rate-limit, actually needs one.

### The mounted-action slot is a server-controlled LiveProp, not a client-writable stack

Filament's `$mountedActions` is a client-writable Livewire property whose
integrity rides on a checksum round-tripped from the browser. Sylament's
[`$mountedActions`](../../../packages/actions/src/Concerns/HasActions.php) is a
`#[LiveProp]` with **no `writable: true`**: its descriptor
(`name`/`arguments`/`context`) is written only from inside the
`mountAction()`/`unmountAction()` LiveActions, never from the client. A confirm
action therefore carries no user-editable payload and there is no tampering
surface to guard for free. Reverses if a writable per-action data bag (Filament's
`$mountedActionData`) is added once form-in-modal input lands.

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
listening. The authoritative signal is therefore the markup: `ConfirmModal`
passes `:open="true"` to `<twig:SylamentSupport:Modal>` (it is rendered only while
[`mountedActionShouldOpenModal()`](#the-modal-open-gate-is-confirmation-only) holds),
and the controller opens itself from that Stimulus value. The dispatch stays for
imperative callers and is harmless — the controller guards on `element.open`, so
value and event are idempotent. Full reasoning in
[support/modal.md](../support/modal.md#the-open-state-is-a-stimulus-value-not-only-an-event).

### The modals renderer emits no wrapper element

Filament wraps its loop in `<div wire:partial="action-modals" x-data="filamentActionModals({ livewireId })" style="height: 0">`
— a partial-render target, an Alpine data component, and an inline height so the
always-present div costs no layout. Sylament's renderer emits the modal or nothing
at all. All three reasons for the wrapper are absent: partial re-render is
[not ported](#partial-re-render-is-not-ported), the open/close behavior lives on
the `sylament--support--modal` Stimulus controller attached to the `<dialog>`
itself rather than an ancestor, and a native `<dialog>` is `display: none` until
opened so it needs no layout-neutral host. Reverses only if a partial-render
mechanism lands and needs a stable DOM anchor.

### There is no single-render guard

Filament's `modals.blade.php` short-circuits on a `hasActionsModalRendered` flag
(a `protected bool` on the trait, set from inside the view) so that the component
renders at most once per request. It needs that because
`<x-filament-actions::modals />` is baked into shared panel layouts —
[`page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php),
`topbar.blade.php`, `sidebar.blade.php` — which can nest, so a page can include it
several times without asking. Sylament has no `panels` package and therefore no
layout that places the tag implicitly: every call site is a host author writing the
tag, and writing it twice would emit two `<dialog>`s with the same
[name-scoped id](#the-modal-id-is-name-only-not-instance-scoped-or-nesting-scoped).
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
