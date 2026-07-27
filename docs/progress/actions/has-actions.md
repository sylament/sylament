# HasActions (actions)

`Sylament\Actions\Concerns\HasActions` — the host-side trait mixed onto the
UX LiveComponent page that mounts and calls `Action`s (the `Sylament\Actions\Contracts\InteractsWithActions`
implementor); it is the **swap** counterpart of Filament's `Concerns\InteractsWithActions`
trait (see the [rename table](../renames.md)). It composes `ComponentToolsTrait`
for `dispatchBrowserEvent()` and owns the single `#[LiveProp] $mountedActions`
slot, the `mountAction` / `callMountedAction` / `unmountAction` `#[LiveAction]`
trio, action lookup (`getAction`, `getMountedAction`, `resolveAction`), the
confirm-modal id helper, and a host-supplied `resolveRecordUsing()` callback for
resolving a mounted action's record. Filament's `InteractsWithActions` is a
~870-line trait built around a **stack** of mounted actions (nested/parent
actions, per-action `Schema` mounting, authorization, rate limiting, database
transactions, redirects, URL-bound default actions, table/schema-scoped action
resolution); Sylament ships a lean **single-slot** MVP that covers the
confirmation-modal round-trip the `actions` epic spikes on and defers the rest.

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
| [`getMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getMountedAction()` | done | — | [diverges](#lookup-methods-drop-caching-and-nesting) |
| [`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php) | `Concerns/InteractsWithActions.php` `getAction()` | in progress | `BelongsToSchemaComponent`, `BelongsToTable` | [detail](#getaction-only-resolves-plain-host-methods) |
| [`resolveAction()`](../../../packages/actions/src/Concerns/HasActions.php) (protected) | `Concerns/InteractsWithActions.php` `resolveAction()` | in progress | nested action stack; action caching | [detail](#resolveaction-has-no-parent-nesting-or-failure-signal) |
| Action caching (`cacheAction`, `mergeCachedActions`, `cacheTraitActions`) | `Concerns/InteractsWithActions.php` `cacheAction()` / `mergeCachedActions()` / `cacheTraitActions()` | todo | — | [detail](#action-caching-is-not-ported) |
| [`getMountedModalId()`](../../../packages/actions/src/Concerns/HasActions.php) | `action-modal.blade.php` inline `$actionModalId` | done | — | [diverges](#the-modal-id-is-name-only-not-instance-scoped-or-nesting-scoped) |
| `replaceMountedAction()` | `Concerns/InteractsWithActions.php` `replaceMountedAction()` | todo | nested action stack | [detail](#replacemountedaction-is-not-ported) |
| Action-schema/form integration (`mountedActionHasSchema`/`ShouldOpenModal`, `getMountedActionSchema*`, deprecated `mountedActionHasForm`/`getMountedActionForm`) | `Concerns/InteractsWithActions.php` (same methods) | blocked | `HasSchema` (actions) + schemas/forms mounted-action wiring | [detail](#action-schema-integration-is-entirely-unported) |
| `#[Url]`-bound default-action state (`defaultAction`, `defaultActionArguments`, `defaultActionContext`, `defaultTableAction*`) | `Concerns/InteractsWithActions.php` public `#[Url]` properties | todo | — | [detail](#url-bound-default-action-state-is-not-ported) |
| `getDefaultAction*` host override hooks (Record/Title/Model/ModelLabel/Url/SuccessRedirectUrl/FailureRedirectUrl/Relationship/SchemaResolver/AuthorizationResponse/IndividualRecordAuthorizationResponseResolver) | `Concerns/InteractsWithActions.php` `getDefaultActionRecord()` etc. | todo | — | [detail](#the-getdefaultaction-host-override-hooks-are-not-ported) |
| `forceRender()` / `partiallyRenderActionParentSchema()` | `Concerns/InteractsWithActions.php` `forceRender()` / `partiallyRenderActionParentSchema()` | blocked | schemas partial-render infrastructure (`PartialsComponentHook`) | [detail](#partial-re-render-is-not-ported) |
| `afterActionCalled()` hook | `Concerns/InteractsWithActions.php` `afterActionCalled()` | todo | — | [detail](#afteractioncalled-is-not-ported) |
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
the single-slot descriptor, resolves the action, unmounts silently if it is
`null`, and opens the modal **only** when `isConfirmationRequired()` is true —
there is no disabled check before mounting, no authorization check, no schema
mount/fill lifecycle, no `Halt`/`Cancel`/`ValidationException` handling during
mount, and no immediate-call path for schema-less, non-confirmation actions
mounted without a modal. **Next step:** land `CanBeAuthorized` for the
authorization branch; the schema-mount branch and the general
`mountedActionShouldOpenModal()` check wait on the actions/forms schema
integration.

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
`callBefore()` / `call()` / `callAfter()`, sends a success or failure
notification by `getStatus()`, catches `Halt` (keeps the action mounted) and
`Cancel` (falls through to unmount), then always unmounts. Rate limiting,
authorization, database transactions, redirects, schema/form data, and the
`afterActionCalled()` hook are all absent — none of the underlying `Action`
concerns they depend on (`CanBeRateLimited`, `CanBeAuthorized`,
`CanUseDatabaseTransactions`, `CanRedirect`) are composed yet either. **Next
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

### `getAction()` only resolves plain host methods

Filament's `getAction(string | array $actions, bool $isMounting = true)`
accepts a single name or a list of nested action descriptors and resolves them
through `resolveActions()`, which branches on `context['schemaComponent']`
(via `resolveSchemaComponentAction()`, requiring `HasSchemas`),
`context['table']` (via `resolveTableAction()`, requiring `HasTable`, with
bulk/record-key handling), or the generic `resolveAction()` (parent-modal
lookup, `cachedActions` lookup, `{name}Action()` **or** bare `{name}` method
with a reflection check that the return type includes `Action`). The port's
[`getAction(string $name)`](../../../packages/actions/src/Concerns/HasActions.php)
only tries `"{$name}Action"` — no bare-method fallback, no return-type
reflection, no `cachedActions` lookup, no table- or schema-scoped resolution,
and no nesting/array-of-descriptors form. **Next step:** the table- and
schema-scoped branches return once `BelongsToSchemaComponent` /
`BelongsToTable` (currently `todo` in the [package README](README.md)) land;
the bare-method fallback and its reflection guard are unblocked already and
could be added independently.

### `resolveAction()` has no parent nesting or failure signal

Filament's `resolveAction(array $action, array $parentActions)` looks up a
parent action's `getModalAction($name)` when `$parentActions` is non-empty,
otherwise checks `cachedActions` before falling back to
`{name}Action()`/`{name}`, and throws `ActionNotResolvableException` when
nothing resolves. The port's
[`resolveAction(array $descriptor)`](../../../packages/actions/src/Concerns/HasActions.php)
is `return $this->getAction($descriptor['name']);` — no parent-action lookup
(there is only ever one slot), no cache check, and a `null` return instead of
a thrown exception on failure (silently swallowed by `mountAction()`'s
`$action === null` branch, which mirrors Filament's own catch-and-null-out
behavior for that exception, so the externally observable outcome matches).
**Next step:** parent-action lookup returns with the nested-action stack;
the cache check returns with action caching (below).

### Action caching is not ported

Filament caches every resolved `Action` in `$cachedActions` (keyed by name,
populated by `cacheAction()` and `mergeCachedActions()`) and every mounted
action in `$cachedMountedActions` (keyed by nesting index), and
`cacheTraitActions()` walks `class_uses_recursive()` at boot to let sibling
`*Actions`-suffixed traits (e.g. a future table-actions trait) register their
own actions into the cache. None of this exists in the port: `getAction()`
calls `{name}Action()` fresh on every invocation. Concretely, the `ActionHost`
fixture's template calls `this.deleteAction()` directly to render the trigger
button, while `getMountedAction()` independently resolves a **second**,
distinct `Action` instance for the confirm modal — the two never share state
(e.g. a `->record()` set on one is invisible to the other). This is
currently harmless because no code mutates an action after construction other
than the mount/call cycle itself, but it is a real behavioral gap, not just a
missing optimization. **Next step:** add `$cachedActions` + `cacheAction()`
and have `getAction()` consult it before calling the factory method.

### `replaceMountedAction()` is not ported

Filament's `replaceMountedAction()` clears the stack and any cached
`mountedActionSchema*` entries, then re-mounts a new action in one call — used
when an action's own callback wants to hand off to a different action
mid-flow. Not ported; there is no equivalent on the single-slot port, and
nothing currently calls for it. **Next step:** returns with the nested-action
stack, since replacing "the" mounted action only means something once more
than one can be mounted at a time.

### Action-schema integration is entirely unported

Filament's `mountedActionHasSchema()` / `mountedActionShouldOpenModal()` /
`getMountedActionSchema()` / `getMountedActionSchemaName()` /
`getMountedActionSchemaModel()` (plus the deprecated `mountedActionHasForm()`
/ `getMountedActionForm()` aliases) build and cache a per-mounted-action
`Schema` keyed by nesting index, seeded with the action's record/model,
state path `mountedActions.{index}.data`, and dotted `operation` name. None of
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

### `afterActionCalled()` is not ported

Filament's `callMountedAction()` calls a protected `afterActionCalled(Action
$action): void {}` hook (empty by default) after `callAfter()` succeeds, for
subclasses/traits that want to react to every successful action call
uniformly. Not declared on the port's `HasActions`; no current consumer.
**Next step:** todo, trivial to add whenever a first caller needs it — add it
to `callMountedAction()` in the same change.

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

### Lookup methods drop caching and nesting

`getMountedAction()` in the port always reads slot `0` and calls
`resolveAction()` fresh; Filament's version takes an optional
`$actionNestingIndex`, defaults it to the top of the stack, and serves from
`$cachedMountedActions` when populated. With a single slot and no action
cache (see [Action caching is not ported](#action-caching-is-not-ported)),
there is nothing to key by nesting index and nothing to cache against — this
divergence is a direct consequence of the single-slot MVP design, not an
independent decision, and resolves itself once the stack and the cache both
land.

### The modal id is name-only, not instance-scoped or nesting-scoped

Filament computes the confirm-modal id inline in
[`action-modal.blade.php`](../../../vendor/filament/actions/resources/views/action-modal.blade.php)
as `"fi-{$this->getId()}-action-{$action->getNestingIndex()}"` — scoped to the
Livewire component's own id and the action's nesting index, so two live
components on the same page (or two nested actions) never collide. The port's
[`getMountedModalId()`](../../../packages/actions/src/Concerns/HasActions.php)
returns `"{$name}-modal"` — scoped only to the action name, which the
`HasActionsTest` render assertion covers for the single-host, single-slot case
the MVP targets. Two `ActionHost`-style components on the same page mounting
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

_See also:_ the [rename table](../renames.md) for the `Has*` / `InteractsWith*`
**swap** this whole file is the Sylament side of, and the systematic
Livewire → UX LiveComponent translation (`ComponentToolsTrait`,
`#[LiveAction]`/`#[LiveArg]`/`#[LiveProp]`, `dispatchBrowserEvent()`) this trait
builds on — neither is a divergence.
