---
description: The action-side schema declaration, `schema()` and `getSchema()`, with its wizard arm and the `HasWizard` concern that configures it.
ports:
  - vendor/filament/actions/src/Concerns/HasSchema.php
  - vendor/filament/actions/src/Concerns/HasWizard.php
owns:
  - packages/actions/src/Concerns/HasSchema.php
  - packages/actions/src/Concerns/HasWizard.php
---
# HasSchema (actions)

An `Action` names the components its modal should contain with `schema()`, and `getSchema(Schema $schema)` turns that declaration into a real [`Schema`](../schemas/schema.md) when a host hands it one to populate.
The other half, building that container per mounted frame, keying it `mountedActionSchema{$i}`, binding it to the client-writable `mountedActionsData.{$i}` bag, caching it, filling it at mount, validating it at submit and lifting its state back onto the action once it passes, is the host's, and lives in [`has-actions.md`](has-actions.md).
The state path that container binds to is a [sibling bag](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor), not Filament's `mountedActions.{i}.data`, and an `Action` declared inside it cannot inject `Set` or `Get`, [a limitation shared with Filament](has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schema(array\|Closure\|null $schema)`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | — | [diverges](#the-schema-property-is-one-member-wider-than-its-setter) |
| [`getSchema(Schema $schema): ?Schema`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | — | [semantics](#the-declarations-own-injections-win-over-the-actions-schema-resolver) · [diverges](#getschema-returns-null-for-an-empty-schema-not-an-empty-schema) |
| [`disabledSchema(bool\|Closure $condition = true)` / `isSchemaDisabled()`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | — | — |
| [`formWrapper(bool\|Closure\|null $condition = true)` / `hasFormWrapper()`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | — | [diverges](#hasformwrapper-stays-true-for-a-wizard) |
| `getSchema()`'s wizard arm and its `modifyWizardUsing()` hook | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | — | [semantics](#the-wizard-arm-wraps-an-array-declaration-in-an-uncontained-wizard-and-the-modal-drops-its-own-footer) · [diverges](#the-wizard-arm-drops-the-alpine-submit-handler) |
| [`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php): `steps()`, `startOnStep()`, `skippableSteps()`, `modifyWizardUsing()`, `isWizard()`, `isWizardSkippable()`, `getWizardStartStep()` | [`Concerns/HasWizard.php`](../../../vendor/filament/actions/src/Concerns/HasWizard.php) | done | — | [diverges](#modifywizardusing-is-one-member-wider-than-its-setter) |
| Host schema-resolver fallback (`getDefaultActionSchemaResolver()`) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | done | — | [semantics](#the-host-resolver-answers-only-when-the-action-declares-nothing) |
| [`getMountedSchema(): ?Schema`](../../../packages/actions/src/Concerns/HasSchema.php) | — | done | — | [semantics](#the-host-resolver-answers-only-when-the-action-declares-nothing) |
| Deprecated aliases (`form()`, `getForm()`, `disableForm()`, `disabledForm()`, `isFormDisabled()`) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | skipped | — | [diverges](#the-deprecated-form-aliases-are-not-ported) |

## Semantics

### The declaration's own injections win over the action's `schema` resolver

`getSchema()` evaluates the declaration with three named injections, `form`, `schema` and `infolist`, all bound to the container being built.
They win over `Action`'s own `resolveDefaultClosureDependencyForEvaluationByName('schema')`, which resolves `getSchemaContainer()`, because [`Invoker::call()`](../../../packages/support/src/Evaluation/Invoker.php) checks `$namedInjections` by `array_key_exists()` before it calls the `$resolveParameter` callback.
So a `fn (Schema $schema) => [...]` passed to `schema()` receives *this* call's container, never the action's pre-existing one.

### The wizard arm wraps an array declaration in an uncontained `Wizard`, and the modal drops its own footer

`getSchema()` branches on `isWizard()` and, for an array-shaped declaration, wraps the whole thing in a schemas [`Wizard`](../schemas/wizard.md) before the array reaches `$schema->components()`: `contained(false)`, `startOnStep()`, the modal cancel and submit actions re-homed as the wizard's own footer slots, `skippable()`, `disabled()`, and the `modifyWizardUsing()` hook, whose closure receives the built wizard as the `wizard` named injection and may return a replacement (returning nothing keeps the built one).
The steps come from the sibling [`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php) concern, ported member for member with its declaration order aside: `steps()` flips the wizard flag and forwards to `schema()`, and `modifyWizardUsing()` also flips it, so a hook alone makes an action a wizard.

The other half of the arm is on the modal side: [`CanOpenModal::getModalFooterActions()`](../../../packages/actions/src/Concerns/CanOpenModal.php) returns `[]` for a wizard, because the wizard renders its own back, next, cancel and submit row and the modal must not render a second one underneath it.
Both halves are Filament's.

The arm carries one PHPStan assertion of its own: the array it hands `Wizard::make()` is annotated `/** @var array<Step> $steps */`, because `getSchema()`'s array arm is typed as the general component list a schema takes while `Wizard::make()` accepts steps only.
Filament's `Wizard::make()` carries the identical `@param array<Step> | Closure` and its `HasSchema` passes the same array in with no annotation, so the mismatch exists there too and is not flagged.
The assertion is unchecked: a non-`Step` in a `steps()` declaration is a user error the wizard surfaces at render, where `Wizard::getSteps()` filters by `instanceof Step` and omits it.
It goes away if the array arm narrows its own type by the wizard flag.

### The host resolver answers only when the action declares nothing

`getSchema()` resolves `$this->schema ?? $this->getLiveComponentOrNull()?->getDefaultActionSchemaResolver($this)`, Filament's own line, so a resource page supplies one form to every action it mounts and no action has to repeat it.
Three things about the `??` are load-bearing and none is obvious.

It fires on `null` alone, so an action that declared `schema([])` never reaches the host: `[]` is not null, the resolver is not consulted, and the `=== []` guard returns `null`, so an explicit empty declaration stays a *decision*, not a gap for the host to fill.
The host read is the **nullable** accessor, the same rule [`CanRedirect`](action.md) follows: a detached action resolves nothing rather than throwing, and no `instanceof` re-check is written because `getLiveComponentOrNull()` already returns the contract carrying the hook.
And the resolver's closure is evaluated with the [three named injections](#the-declarations-own-injections-win-over-the-actions-schema-resolver) the declaration arm passes, so a page may write `fn (Schema $schema)` or `fn ($form)` and get the same object.

`getMountedSchema()` is the other half, and it has no Filament counterpart because Filament does not need one.
A write body, a `CreateAction`'s process closure or an `EditAction`'s, has to read *the modal's* schema.
A closure the pipeline evaluates **with** the explicit pair does get it: `callMountedAction()` runs `$action->call(['form' => $schema, 'schema' => $schema])`, so an `action()` closure may take `Schema $schema` (`CreateAction::setUp()` does); `process()` forwards the same pair to a `using()` closure; and `fillForm()`'s closure gets it from `CanBeMounted`'s `mountUsing()` wrapper.
What does not work is a closure the pipeline evaluates with **no** injection of its own (`before()`, `after()`, the four form hooks, `mutateRecordDataUsing()`, which is handed `data` alone), because `schema` then falls through [`Action::resolveDefaultClosureDependencyForEvaluationByName()`](action.md) to `getSchemaContainer()`, which is the **containing** schema: the page's form on a record page, and `null` on a table row or a page header.
The write bodies cannot take the pair as a parameter either, because they are what supplies it.
So the read is explicit instead: `getLiveComponentOrNull()?->getMountedActionSchema(mountedAction: $this)`, which asks the host for the frame it built for *this* action, and answers `null` for a confirmation-shaped action that mounted with no schema at all, or for an action with no host.
Filament's own bodies sidestep the problem by reading `$record->attributesToArray()` rather than a schema; the port's read [`Schema::getStateFromRecord()`](../schemas/belongs-to-record.md), which needs the schema to read it *from*.

## Divergences

### The wizard arm drops the Alpine submit handler

Filament's branch also calls `->alpineSubmitHandler("\$wire.{$this->getLivewireCallMountedActionName()}()")`, which hands the wizard's last-step submit button an Alpine expression to run.
Sylament has no Alpine and schemas' `Wizard` exposes no such setter: the submit slot holds the modal's own submit `Action`, which is a real `button[type="submit"]` inside the modal's `<form>` wrapper, so the browser submits it natively and the live component's `callMountedAction` runs off that.
Nothing is lost, and the port has no place to put the string.

### `hasFormWrapper()` stays `true` for a wizard

Filament returns `$this->evaluate($this->hasFormWrapper) ?? (! $this->isWizard())`: the wrapper is off for a wizard, because Filament's wizard renders its own submit chrome and its steps are what a submit acts on.
The port's fallback is the literal `true`, deliberately: a step renders no form of its own here, so the outer `<form>` is the only one, and dropping it would leave the last-step submit button with nothing to submit.

The rest of the modal pipeline rests on that.
A schema-bearing action, wizard or not, always renders inside a real `<form>`, so [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) always takes the `->submit($name)` arm and never the `->action($name)` one, and [`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig) derives its `submitHandler` prop from the same flag, so the modal window is always the `<form>` that button submits.
The `->action(…)` half of that branch is live code with no reachable caller unless a user calls `formWrapper(false)` explicitly; it is kept because that is public API.
Inside the wizard, Enter in a text input on a non-last step is intercepted by [the wizard controller](../schemas/wizard.md), which advances instead of submitting; on the last step it falls through to the native form submit.

### `getSchema()` returns null for an empty schema, not an empty `Schema`

An action that declares `schema([])`, or whose schema closure returns `[]`, gets `null` back rather than a populated-with-nothing `Schema`.
This is Filament's own behaviour (`is_array($modifiedSchema) && (! count($modifiedSchema))`), written here as `$modifiedSchema === []`, and two things downstream depend on it: [`mountedActionHasSchema()`](has-actions.md) is a `count(getComponents())` over the result rather than a second probe, and `shouldOpenModal(checkForSchemaUsing:)` stays honest, so an action whose only declared modal content is an empty schema does not acquire a modal it has nothing to put in.

### The `$schema` property is one member wider than its setter

The backing property is typed `Schema|array<Component|Action|ActionGroup>|Closure|null` where Filament's is `array|Closure|null`, and the public `schema()` setter stays Filament-exact at `array|Closure|null`: a bare `Schema` still cannot be assigned directly, only returned from a closure.
The public API is therefore unchanged; only the internal type is wider.

The reason is the `is_array()` guard in `getSchema()`.
Filament writes that guard so a closure may hand back an already-prepared `Schema` instead of a component array, a capability its own type system does not describe, because the property is narrower than what the guard admits.
Sylament's `evaluate()` carries a PHPStan generic Filament's does not (`@template T @param T|Closure $value @return T`, subtracting `Closure` from the property's union), so the same latent gap becomes a hard level-max error: with the narrow type, `$modifiedSchema` is `array|null` and the non-array branch is provably dead.
Widening the property keeps the branch **live** rather than deleting it, which is the difference between preserving a Filament capability and dropping one.
See [the closure-typing convention](../../internals/php.md#the-closure-typing-convention).

### `$modifyWizardUsing` is one member wider than its setter

[`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php) types `$modifyWizardUsing` as `Wizard|Closure|null` where Filament's is `?Closure`, the same widening [`Wizard::nextAction()`](../schemas/wizard.md)'s property uses.
`evaluate()`'s generic subtracts `Closure` from the property's union, so a bare `?Closure` would infer the *closure* as the evaluated type and make the `?? $wizard` fallback provably dead at level max.
The public `modifyWizardUsing()` setter stays Filament-exact at `?Closure`.

### The deprecated `form()` aliases are not ported

Filament keeps five deprecated delegates on `HasSchema`, and each docblock names its replacement: `disableForm()` and `disabledForm()` point to `disabledSchema()`, `form()` to `schema()`, `getForm()` to `getSchema()`, and `isFormDisabled()` to `isSchemaDisabled()`.
The port ships only the replacements, all of which are on this file's Surface, because it ports no deprecated Filament surface.
