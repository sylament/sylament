---
description: The `InteractsWithActions` host contract a live component implements to mount and render actions, ported from Filament's `Contracts\HasActions`.
ports:
  - vendor/filament/actions/src/Contracts/HasActions.php
  - vendor/filament/actions/src/Concerns/InteractsWithActions.php
owns:
  - packages/actions/src/Contracts/InteractsWithActions.php
---
# InteractsWithActions (actions)

`Sylament\Actions\Contracts\InteractsWithActions` is the contract a UX LiveComponent implements to host action mounting, as panels' [`Pages\Page`](../panels/custom-pages.md) and [`RelationManager`](../panels/relation-manager.md) and the test hosts do.
`Action` holds a nullable reference to one through [`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) (`liveComponent()` / `getLiveComponent()`), typing the callback target abstractly instead of against a concrete class.
It is a bare interface: no default methods, nothing composed.
The names are swapped against Filament's: this **interface** ports Filament's `Contracts\HasActions` **interface**, while Sylament's `HasActions` **trait** ([has-actions.md](has-actions.md)) ports Filament's `Concerns\InteractsWithActions` **trait**, as recorded in [renames.md](../renames.md).
This file covers the contract's method inventory and signatures; the behaviour of the mount and call pipeline belongs to the trait.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`mountAction()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | — |
| [`callMountedAction(array $arguments = [])`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`unmountAction(bool\|string\|null $cancelParentActions = null)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`getMountedAction(?int $actionNestingIndex = null)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`getMountedActions()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`mountedActionShouldOpenModal()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`getMountedModalId(?int $actionNestingIndex = null)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | — | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) · [modals.md](modals.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper) |
| [`isMountedActionModalOpen(int $actionNestingIndex)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | — | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) · [modals.md](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events) |
| [`getMountedActionData(?int $actionNestingIndex = null)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | — | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`getMountedActionSchema(?int $actionNestingIndex = null, ?Action $mountedAction = null)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (protected trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`mountedActionHasSchema(?Action $mountedAction = null)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | done | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| [`getAction()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [diverges](#getaction-drops-grouparray-lookup-and-the-ismounting-flag) |
| `replaceMountedAction()` on the contract | [`Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) (trait method) | skipped | — | [diverges](#host-methods-are-promoted-onto-the-interface-when-a-template-calls-them) |
| `mergeMountedActionArguments()` | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | todo | — | [detail](#schema-mount-plumbing-is-not-on-the-contract) |
| `getMountedActionSchemaName()` | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | skipped | — | [diverges](#getmountedactionschemaname-is-not-declared-because-the-name-is-built-inline) |
| [`getDefaultActionUrl(Action $action)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [semantics](#a-host-answering-the-url-hook-must-discriminate-by-action) |
| [`getDefaultActionSuccessRedirectUrl(Action $action)` / `getDefaultActionFailureRedirectUrl(Action $action)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [semantics](#a-host-answering-the-url-hook-must-discriminate-by-action) |
| [`getDefaultActionSchemaResolver()`, `getDefaultActionRecord()`, `getDefaultActionRecordTitle()`, `getDefaultActionRecordClass()`, `getDefaultActionRecordLabel()`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [detail](#the-panel-level-default-hooks-are-on-the-contract-the-authorization-pair-deferred) · [semantics](#a-host-answering-the-url-hook-must-discriminate-by-action) |
| [`getDefaultActionRelationship(Action $action)`](../../../packages/actions/src/Contracts/InteractsWithActions.php) | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | done | — | [detail](#the-panel-level-default-hooks-are-on-the-contract-the-authorization-pair-deferred) · [semantics](#a-host-answering-the-url-hook-must-discriminate-by-action) |
| `getDefaultActionAuthorizationResponse()` / `getDefaultActionIndividualRecordAuthorizationResponseResolver()` | [`Contracts/HasActions.php`](../../../vendor/filament/actions/src/Contracts/HasActions.php) | blocked | `CanBeAuthorized` | [detail](#the-panel-level-default-hooks-are-on-the-contract-the-authorization-pair-deferred) |

## Details

### Schema-mount plumbing is not on the contract

Filament's contract declares `mergeMountedActionArguments(array $arguments): void`, which merges caller-supplied arguments into both the raw mounted-action descriptor and the live `Action`, for a schema-backed action's form that is re-submitted.
Sylament's interface does not declare it, although the schema integration itself (`getMountedActionSchema()` / `mountedActionHasSchema()` / `getMountedActionData()`) is on the contract ([has-actions.md](has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation)).
It is a real gap rather than a naming one: `callMountedAction()` merges the caller's arguments transiently and restores them afterwards, so nothing writes them back into the persisted descriptor, which is what a form resubmit at the same frame would need.
The [keep-modal-open validation loop](has-actions.md#callmountedaction-runs-filaments-pipeline-in-filaments-order) resubmits exactly such a frame and works without it: the client re-sends its `#[LiveArg]` arguments on every submit, so the persisted copy is not what the retry reads.
**Next step:** it lands with the first caller that resubmits a frame *without* re-sending the arguments it was mounted with.

### The panel-level default hooks are on the contract, the authorization pair deferred

Filament's contract declares the `getDefaultAction*` hooks `getDefaultActionRecord`, `getDefaultActionRecordTitle`, `getDefaultActionModel`, `getDefaultActionModelLabel`, `getDefaultActionUrl`, `getDefaultActionSuccessRedirectUrl`, `getDefaultActionFailureRedirectUrl`, `getDefaultActionRelationship`, `getDefaultActionSchemaResolver`, `getDefaultActionAuthorizationResponse` and `getDefaultActionIndividualRecordAuthorizationResponseResolver`.
In Filament's own trait every one of them is a stub returning `null`: they exist as override points so a **panel resource or relation-manager page** can supply implicit record, class, URL, redirect, relationship, schema-resolver and authorization defaults for every action it mounts, without each action call site repeating them.

Each ported hook is declared on the interface and defaulted to `null` on [the trait](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred), so every host inherits it and no host needs an edit.
Each lands with the `Action`-side reader that consults it: `getDefaultActionUrl(Action $action): ?string` with [`CanOpenUrl::getUrl()`](../../../packages/actions/src/Concerns/CanOpenUrl.php), through which the [prebuilt `CreateAction` / `EditAction`](create-action.md) render as links to a resource's pages, the redirect pair with [`CanRedirect`](action.md), the record hooks with the [record chain on `Action`](action.md#the-record-chain-reaches-the-schema-the-component-and-the-host), and `getDefaultActionSchemaResolver(Action $action): ?Closure` with [`HasSchema::getSchema()`](has-schema.md#the-host-resolver-answers-only-when-the-action-declares-nothing)'s host fallback.
`getDefaultActionModel` and `getDefaultActionModelLabel` speak the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) here as `getDefaultActionRecordClass()` and `getDefaultActionRecordLabel()`; the other record hooks already spoke "record" and carry over unchanged.
`getDefaultActionRelationship(Action $action): ?Relationship` is read by [`CreateAction::getRelationship()`](create-action.md#the-created-record-joins-the-relationship-in-the-same-flush) and typed by [`Sylament\Schemas\State\Relationship`](../schemas/association-reader.md), which is where Filament's `Relation` return type lands under Doctrine.

The two authorization hooks stay out because nothing on this side reads them: they wait on `CanBeAuthorized`, parked like the rest of authorization.
**Next step:** each lands with the `Action`-side concern that reads it.

## Semantics

### A host answering the URL hook must discriminate by action

`getDefaultActionUrl()` is asked for **every** action the host renders that has no `url()` of its own, and an action that returns a URL renders as an anchor: [`CanOpenUrl::getUrl()`](../../../packages/actions/src/Concerns/CanOpenUrl.php) is what the trigger templates branch on, and only an explicit `->modal()` call suppresses it.
So a host that answers unconditionally turns its [`DeleteAction`](delete-action.md) into a link and its confirmation modal never opens.
Filament's own implementation (`Resources\Pages\Page::getDefaultActionUrl()`) is a chain of `$action instanceof CreateAction` / `instanceof EditAction` guards for exactly this reason; the port's [`DefaultActionUrlHostFixture`](../../../packages/actions/tests/Fixtures/Concerns/DefaultActionUrlHostFixture.php) mirrors the shape, answering only for an `EditAction`.
The default the trait ships returns `null`, so the trap is reachable only by a host that overrides it.

## Divergences

### Host methods are promoted onto the interface when a template calls them

Filament's `Contracts\HasActions` does **not** declare `callMountedAction()`, `unmountAction()`, `getMountedAction()`, or `getMountedActions()`: they exist only on the `Concerns\InteractsWithActions` trait, callable on the concrete Livewire component but never through the abstract contract.
Sylament's interface promotes all four, because the package's templates call back into the host by name regardless of its concrete class, and because [`BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php) types `Action`'s host reference as the interface, not a concrete class.
[`ActionModal/index.html.twig`](../../../packages/actions/templates/components/ActionModal/index.html.twig) wires `live_action('unmountAction', {cancelParentActions: …})` on its wrapper and names `callMountedAction` as the form's `submitHandler` (through `getLiveComponentCallMountedActionName()`), and the trigger partials such as [`Action/Button.html.twig`](../../../packages/actions/templates/partials/Action/Button.html.twig) pass `action: 'mountAction'` to `stimulus_action('live', 'action', …)`.
Naming the behaviour on the contract keeps that reference meaningful.

The host-side [`<twig:SylamentActions:Modals />`](../../../packages/actions/templates/components/Modals/index.html.twig) renderer reads its host through this interface, so the methods it calls are promoted by the same rule: `mountedActionShouldOpenModal(?Action $mountedAction = null): bool`, `getMountedModalId(?int $actionNestingIndex = null): string`, `isMountedActionModalOpen(int $actionNestingIndex): bool`, `getMountedActionSchema(?int, ?Action): ?Schema` and `mountedActionHasSchema(?Action): bool`.
Without them the contract would under-declare what the package's own template requires, and a host implementing only the interface would render a modal-less page with no compile- or run-time signal until the `<dialog>` failed to appear.
`getMountedModalId()` and `isMountedActionModalOpen()` have **no Filament counterpart at all**: Filament computes its modal id inline in `action-modal.blade.php` (see [modals.md](modals.md#a-frame-carries-two-identities-an-index-scoped-dialog-and-a-name-scoped-wrapper)) and decides open state on the client (see [declarative choreography](modals.md#the-stack-is-choreographed-declaratively-not-by-client-events)), so they are Sylament-only host obligations, not ported declarations.
`getMountedActionSchema()` is `protected` in Filament, so promoting it widens its visibility as well as the contract.
`getMountedActionData(?int): array` is not called from a template, but from [`HasData::getRawData()`](../../../packages/actions/src/Concerns/HasData.php) on an `Action` whose host reference is this interface; Filament reads the raw Livewire property directly there, which Sylament cannot, and the [sibling-bag divergence](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor) explains why, and why the name is singular.

The promoted methods keep Filament's full signatures, so none is a narrowing: `callMountedAction(array $arguments = [])`, `unmountAction(bool|string|null $cancelParentActions = null)`, and `getMountedAction(?int $actionNestingIndex = null)` all match Filament's trait version parameter for parameter.

The contract is **deliberately wider** than Filament's, and that is the standing rule: a method belongs here as soon as a shipped Sylament template or an `Action` concern calls it on an abstractly-typed host.
The line is drawn at exactly that: `replaceMountedAction()` is a ported trait method, but only PHP on the concrete host calls it (an action's own callback handing off to another action), so it stays trait-only, as in Filament, until a template needs it.

### `getMountedActionSchemaName()` is not declared, because the name is built inline

Filament's contract declares `getMountedActionSchemaName(): ?string`, which names the `Schema` the top mounted action's form validates against, and Filament's testing helpers read it.
The port has no such method: [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) builds the name `mountedActionSchema{$actionNestingIndex}` inline where it reads, forgets and caches a frame's schema, so the contract has nothing to declare.
It returns if a caller outside the trait needs the name.

### `getAction()` drops group/array lookup and the `isMounting` flag

Filament's `getAction(string | array $actions, bool $isMounting = true): ?Action` is a thin wrapper: it normalizes its argument to a list of descriptors and returns `Arr::last($this->resolveActions($actions, $isMounting))`, so the array form is how a caller reaches a *nested* action by path, and `$isMounting` threads through to the per-frame schema caching.
Sylament splits the two responsibilities instead: [`getAction(string $name): ?Action`](../../../packages/actions/src/Concerns/HasActions.php) is only the host-method-discovery arm (cache lookup, `"{$name}Action"` preferred over the bare `"{$name}"` method behind a return-type reflection gate, and a thrown [`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php) for a factory whose runtime value is not an `Action`; see [has-actions.md](has-actions.md#getaction-ports-the-generic-method-discovery-arm-in-full)), while the descriptor walk lives on its own [`resolveActions()`](../../../packages/actions/src/Concerns/HasActions.php).
So the nesting Filament reaches *through* `getAction()` is fully ported; what this contract signature lacks is the by-path lookup shorthand and the `$isMounting` flag, and an unknown name returns `null` silently, the mounting-flow behaviour.
It reverses by widening the parameter to `string|array` and delegating to `resolveActions()`, the moment a caller needs to fetch a nested action without mounting it.
