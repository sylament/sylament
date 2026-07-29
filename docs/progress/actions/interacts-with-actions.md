# InteractsWithActions (actions)

`Sylament\Actions\Contracts\InteractsWithActions` — the host contract: whatever
UX LiveComponent hosts action mounting (today the test fixtures `ActionHost` /
`RecordActionHost`; eventually panel resource pages and table hosts) implements
it, and `Action` holds a nullable reference to one through
[`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php)
(`liveComponent()` / `getLiveComponent()`), typing the callback target
abstractly instead of against a concrete class. It is a bare interface — no
default methods, nothing composed. This is the **SWAP**: Sylament's
`InteractsWithActions` **interface** ports Filament's `Contracts\HasActions`
**interface**, while Sylament's `HasActions` **trait** (audited separately, in
[has-actions.md](has-actions.md)) ports Filament's
`Concerns\InteractsWithActions` **trait** — the exact
inverse naming, recorded in [renames.md](../renames.md). Deep behavioral parity
of the mount/call pipeline itself (schema state, database transactions, rate
limiting, redirects) belongs to that trait's own audit, not this one; this file
judges only the contract's method inventory and signatures.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php)
  — the interface this ports (name-swapped)
- [`actions/src/Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)
  — the trait whose `mountAction` / `callMountedAction` / `unmountAction` /
  `getMountedAction` / `mountedActionShouldOpenModal` / `getAction`
  implementations this contract's declared methods are diffed against (the trait
  itself is **not** on Filament's `Contracts\HasActions`; five of its methods,
  plus the Sylament-only `getMountedModalId` and `isMountedActionModalOpen`, are
  promoted onto Sylament's interface — see [Divergences](#divergences))

## Surface

Every method the interface declares, plus every method Filament's contract
declares that Sylament's does not. _Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`mountAction()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | — |
| `callMountedAction(array $arguments = [])` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `unmountAction(bool\|string\|null $cancelParentActions = null)` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `getMountedAction(?int $actionNestingIndex = null)` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `getMountedActions()` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `mountedActionShouldOpenModal()` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `getMountedModalId(?int $actionNestingIndex = null)` | — (no Filament counterpart; see [has-actions.md](has-actions.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper)) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `isMountedActionModalOpen(int $actionNestingIndex)` | — (no Filament counterpart; see [has-actions.md](has-actions.md#the-stack-is-choreographed-declaratively-not-by-client-events)) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `getMountedActionData(?int $actionNestingIndex = null)` | — (no Filament counterpart; the bag it reads replaces the `mountedActions.{i}.data` state path — see [has-actions.md](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor)) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `getMountedActionSchema(?int $actionNestingIndex = null, ?Action $mountedAction = null)` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (protected trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `mountedActionHasSchema(?Action $mountedAction = null)` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `getAction()` | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [diverges](#getaction-drops-grouparray-lookup-and-the-ismounting-flag) |
| `replaceMountedAction()` (trait-only here, as in Filament) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | skipped | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `mergeMountedActionArguments()` (declared by Filament, dropped here) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | todo | — | [detail](#schema-mount-plumbing-is-not-on-the-contract) |
| `getMountedActionSchemaName()` (declared by Filament, dropped here) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | skipped | — | [detail](#schema-mount-plumbing-is-not-on-the-contract) |
| The ten `getDefaultAction*` hooks (declared by Filament, dropped here) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | blocked | `panels` package | [detail](#the-panel-level-default-hooks-are-not-on-the-contract) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### Schema-mount plumbing is not on the contract

Filament's contract declares `mergeMountedActionArguments(array $arguments): void`
(merges caller-supplied arguments into both the raw mounted-action descriptor
and the live `Action`, used when a schema-backed action's form is
re-submitted) and `getMountedActionSchemaName(): ?string` (names the `Schema`
instance the mounted action's form validates against). Sylament's interface
declares neither, even though the schema integration itself now exists
(`getMountedActionSchema()` / `mountedActionHasSchema()` /
`getMountedActionData()` are all on the contract — see
[has-actions.md](has-actions.md#the-per-mount-schema-carries-no-model-or-operation)).
`getMountedActionSchemaName()` has no port at all: the name is computed inline
at the one place it is needed, so there is nothing to declare — **skipped**, and
it returns only if a second caller ever wants it. `mergeMountedActionArguments()`
is a real gap rather than a naming one: `callMountedAction()` merges the caller's
arguments transiently and restores them afterwards, so nothing writes them back
into the persisted descriptor, which is what a form resubmit at the same frame
would need. M1 · 1's
[keep-modal-open validation loop](has-actions.md#callmountedaction-drops-rate-limiting-authorization-transactions-and-redirects)
resubmits exactly such a frame and landed without it: the client re-sends its
`#[LiveArg]` arguments on every submit, so the persisted copy is not what the
retry reads. **Next step:** it lands with the first caller that resubmits a
frame *without* re-sending the arguments it was mounted with.

### The panel-level default hooks are not on the contract

Filament's contract declares ten `getDefaultAction*` hooks
(`getDefaultActionRecord`, `getDefaultActionRecordTitle`,
`getDefaultActionModel`, `getDefaultActionModelLabel`, `getDefaultActionUrl`,
`getDefaultActionSuccessRedirectUrl`, `getDefaultActionFailureRedirectUrl`,
`getDefaultActionRelationship`, `getDefaultActionSchemaResolver`,
`getDefaultActionAuthorizationResponse`, and
`getDefaultActionIndividualRecordAuthorizationResponseResolver`). In Filament's
own trait every one of them is a stub returning `null` — they exist purely as
override points so a **panel resource or relation-manager page** can supply
implicit record/model/URL/redirect/relationship/schema-resolver/authorization
defaults for every action it mounts, without each action call site repeating
them. Sylament's actions package ships
[`InteractsWithRecord`](../../../packages/actions/src/Concerns/InteractsWithRecord.php)
(`record()` / `getRecord()`), but that is an explicit per-`Action` setter, not
a host-resolved default, and the `panels` package — the layer that would
supply page-level defaults — is `todo` (per [the top-level progress
table](../README.md#packages)). Dropping the whole cluster is consistent with
that: no host exists yet that could implement it meaningfully. **Blocked** on
the `panels` package landing (and, for `getDefaultActionRelationship`
specifically, a Doctrine relation equivalent to Eloquent's `Relation`).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

### Host methods are promoted onto the interface when a template calls them

Filament's `Contracts\HasActions` does **not** declare `callMountedAction()`,
`unmountAction()`, `getMountedAction()`, or `getMountedActions()` — they exist
only on the `Concerns\InteractsWithActions` trait, callable on the concrete
Livewire component but never through the abstract contract. Sylament's interface
promotes all four, because both the modal templates and the trigger buttons
call back into the host by name regardless of its concrete class —
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig)
wires `live_action('unmountAction', { cancelParentActions: … })`,
[`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig)
wires `live_action('mountAction', …)` and, for a modal submit action,
`live_action('callMountedAction', …)` — and because
[`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php)
types `Action`'s host reference as the interface, not a concrete class. Naming
the behavior on the contract keeps that reference meaningful.

Three more follow from the same rule, driven by the host-side
[`<twig:SylamentActions:Modals />`](../../../packages/actions/templates/components/Modals/index.html.twig)
renderer, which reads its host through this interface:
`mountedActionShouldOpenModal(?Action $mountedAction = null): bool`,
`getMountedModalId(?int $actionNestingIndex = null): string`, and
`isMountedActionModalOpen(int $actionNestingIndex): bool`. Without them the
contract would under-declare what the package's own template requires, and a
host implementing only the interface would render a modal-less page with no
compile- or run-time signal until the `<dialog>` failed to appear. The last two
have **no Filament counterpart at all** — Filament computes its modal id inline
in `action-modal.blade.php` (see
[has-actions.md](has-actions.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper))
and decides open state on the client (see
[declarative choreography](has-actions.md#the-stack-is-choreographed-declaratively-not-by-client-events)) —
so they are promoted as Sylament-only host obligations, not ported declarations.

The forms-in-modals epic added three more under the same rule.
`getMountedActionSchema(?int, ?Action): ?Schema` and
`mountedActionHasSchema(?Action): bool` are what the modal renderer reads to
decide whether a mounted action has a schema slot and to render it; like
`Modals`, that renderer holds its host through this interface, so the pair has
to be declared here. The first is `protected` in Filament, so promoting it is a
visibility widening as well as a contract addition — the same trade the modal
templates already forced on `callMountedAction()`. `getMountedActionData(?int):
array` is not called from a template at all, but from
[`HasData::getRawData()`](../../../packages/actions/src/Concerns/HasData.php) on
an `Action` whose host reference is this interface; Filament reads the raw
Livewire property directly there, which Sylament cannot — the
[sibling-bag divergence](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor)
explains why, and why the name is singular.

The nesting-actions epic restored the full signatures the single-slot MVP had
reduced, so no promoted method is a narrowing any more:
`callMountedAction(array $arguments = [])`,
`unmountAction(bool|string|null $cancelParentActions = null)`, and
`getMountedAction(?int $actionNestingIndex = null)` all match Filament's trait
version parameter for parameter.

The contract stays **deliberately wider** than Filament's, and that is the
standing rule rather than an accident of this epic: a method belongs here as
soon as a shipped Sylament template calls it on an abstractly-typed host. The
line is drawn at exactly that — `replaceMountedAction()` is a real ported trait
method, but only PHP calls it (an action's own callback handing off to another
action), so it stays trait-only, as in Filament. It moves onto the contract the
day a template needs it, not before.

### `getAction()` drops group/array lookup and the `isMounting` flag

Filament's `getAction(string | array $actions, bool $isMounting = true): ?Action`
is a thin wrapper: it normalizes its argument to a list of descriptors and
returns `Arr::last($this->resolveActions($actions, $isMounting))`, so the array
form is how a caller reaches a *nested* action by path, and `$isMounting`
threads through to the per-frame schema caching. Sylament splits the two
responsibilities instead —
[`getAction(string $name): ?Action`](../../../packages/actions/src/Concerns/HasActions.php)
is only the host-method-discovery arm (cache lookup,
`"{$name}Action"` preferred over the bare `"{$name}"` method behind a
return-type reflection gate, and a thrown
[`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php)
for a factory whose runtime value is not an `Action`; see
[has-actions.md](has-actions.md#getaction-ports-the-generic-method-discovery-arm-in-full)),
while the descriptor walk lives on its own
[`resolveActions()`](has-actions.md#resolveactions-drops-the-table-branch).
So the nesting Filament reaches *through* `getAction()` is fully ported; what
this contract signature still lacks is the by-path lookup shorthand and the
`$isMounting` flag — an unknown name always returns `null` silently, the
mounting-flow behavior. Reversible by widening the parameter to
`string|array` and delegating to `resolveActions()`, the moment a caller needs
to fetch a nested action without mounting it.

_See also: the global [rename table](../renames.md) — the `Has*` /
`InteractsWith*` **interface/trait swap** and the Livewire → UX LiveComponent
idiom translation (`live_action()`, `dispatchBrowserEvent()`) are systematic
translations recorded there, not divergences of this file._
