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
**interface**, while Sylament's `HasActions` **trait** (a separate, not-yet-audited
item) ports Filament's `Concerns\InteractsWithActions` **trait** — the exact
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
  `getMountedAction` / `getAction` implementations this contract's declared
  methods are diffed against (the trait itself is **not** on Filament's
  `Contracts\HasActions`; three of its methods are promoted onto Sylament's
  interface — see [Divergences](#divergences))

## Surface

Every method the interface declares, plus every method Filament's contract
declares that Sylament's does not. _Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`mountAction()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | — |
| `callMountedAction()` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#three-lifecycle-methods-are-promoted-onto-the-interface-with-reduced-signatures) |
| `unmountAction()` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#three-lifecycle-methods-are-promoted-onto-the-interface-with-reduced-signatures) |
| `getMountedAction()` | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method — not on the contract) | done | — | [diverges](#three-lifecycle-methods-are-promoted-onto-the-interface-with-reduced-signatures) |
| `getAction()` | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [diverges](#getaction-drops-grouparray-lookup-and-the-ismounting-flag) |
| `mergeMountedActionArguments()` (declared by Filament, dropped here) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | blocked | `HasSchema` concern / schemas-actions integration | [detail](#schema-mount-plumbing-is-not-on-the-contract) |
| `getMountedActionSchemaName()` (declared by Filament, dropped here) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | blocked | `HasSchema` concern / schemas-actions integration | [detail](#schema-mount-plumbing-is-not-on-the-contract) |
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
declares neither. There is no schema integration for actions yet — the
`HasSchema` concern is `todo` in the [actions README](README.md#features), and
nothing in the port resolves a `Schema` for a mounted action's form — so
neither method has anything to merge into or name. **Blocked** on the
`HasSchema` concern and the wider schemas-actions wiring landing.

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

### Three lifecycle methods are promoted onto the interface, with reduced signatures

Filament's `Contracts\HasActions` does **not** declare `callMountedAction()`,
`unmountAction()`, or `getMountedAction()` — they exist only on the
`Concerns\InteractsWithActions` trait, callable on the concrete Livewire
component but never through the abstract contract. Sylament's interface
promotes all three, because both the modal template and the trigger button
call back into the host by name regardless of its concrete class —
[`confirm-modal.html.twig`](../../../packages/actions/templates/components/confirm-modal.html.twig)
wires `live_action('callMountedAction')` / `live_action('unmountAction')`, and
[`button-action.html.twig`](../../../packages/actions/templates/components/button-action.html.twig)
wires `live_action('mountAction', ...)` — and because
[`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php)
types `Action`'s host reference as the interface, not a concrete class. Naming
the behavior on the contract keeps that reference meaningful.

Each promoted method also drops the nesting/argument richness Filament's trait
version has, since Sylament has no `ActionGroup` yet (`ActionGroup`,
`BelongsToGroup`, and `HasParentActions` are all `todo` per the [actions
README](README.md)):

- `callMountedAction()` has no `array $arguments = []` parameter — Filament
  merges caller-supplied arguments on top of the mounted ones at call time (a
  submitted schema does this); the port's version only reads back the
  arguments recorded at mount time.
- `unmountAction()` has no `bool|string|null $cancelParentActions` parameter —
  Filament supports a stack of nested mounted actions (a group's child mounted
  on top of its parent) with a knob controlling whether popping one also
  cancels its ancestors; the port always clears the single mounted-action slot.
- `getMountedAction()` has no `?int $actionNestingIndex` parameter — same
  nested-actions stack; the port always reads `$mountedActions[0]`.

Reversible in the same change that lands `ActionGroup` / nested actions, at
which point each parameter returns.

### `getAction()` drops group/array lookup and the `isMounting` flag

Filament's `getAction(string | array $names, bool $isMounting = true): ?Action`
accepts an array path to reach into a nested `ActionGroup`, and throws
`ActionNotResolvableException` when `$isMounting` is `false` and the action
cannot be resolved (non-mounting callers want to know resolution failed
outright; mounting flows treat "unresolvable" as "do nothing"). Sylament's
[`getAction(string $name): ?Action`](../../../packages/actions/src/Concerns/HasActions.php)
only resolves a single-level `"{$name}Action"` method on the host and always
returns `null` silently when it is missing — no group traversal (`ActionGroup`
is not ported) and no resolution exception (`ActionNotResolvableException` is
not ported either, per the [actions README](README.md#supporting-surface)).
Reversible once `ActionGroup` and the resolution exception land.

_See also: the global [rename table](../renames.md) — the `Has*` /
`InteractsWith*` **interface/trait swap** and the Livewire → UX LiveComponent
idiom translation (`live_action()`, `dispatchBrowserEvent()`) are systematic
translations recorded there, not divergences of this file._
