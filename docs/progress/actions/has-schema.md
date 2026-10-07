# HasSchema (actions)

[`Sylament\Actions\Concerns\HasSchema`](../../../packages/actions/src/Concerns/HasSchema.php) — the per-action schema **declaration**: an `Action` names the components its modal should contain with `schema()`, and `getSchema(Schema $schema)` turns that declaration into a real [`Schema`](../schemas/schema.md) when a host hands it one to populate.
The other half — building that container per mounted frame, keying it `mountedActionSchema{$i}`, binding it to the client-writable `mountedActionsData.{$i}` bag, caching it, filling it at mount, validating it at submit and lifting its state back onto the action once it passes — is the host's, and lives in [`has-actions.md`](has-actions.md).
This file covers only the action-side surface.

Its sole consumer is [`Sylament\Actions\Action`](../../../packages/actions/src/Action.php), via its trait block.
It earns a file of its own rather than folding into [`action.md`](action.md) because its counterpart lives in a second file: the declaration and the host pipeline are two halves of one feature, and each needs one addressable home to link the other from.

**Status:** done — see the [status vocabulary](../README.md#statuses).
Every ported row is **done**; the one unported row (the deprecated aliases) is a permanent omission with its reason recorded below.
The host schema-resolver fallback was the last outstanding row, waiting until the hook family it calls existed to be called.

Reference — the Filament source this is diffed against:

- [`actions/src/Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) — the trait this ports
- [`actions/src/Concerns/HasWizard.php`](../../../vendor/filament/actions/src/Concerns/HasWizard.php) — the sibling concern the wizard arm reads
- [`actions/src/Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php) — where Filament consumes `getSchema()`, ported as [`HasActions::getMountedActionSchema()`](has-actions.md)

## Surface

_Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details), and/or **diverges** → the note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schema(array\|Closure\|null $schema)`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `schema()` | done | — | Filament-exact setter. The backing property is one member wider than the parameter — [diverges](#the-schema-property-is-one-member-wider-than-its-setter) |
| [`getSchema(Schema $schema): ?Schema`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `getSchema()` | done | — | Its three named injections (`form` / `schema` / `infolist`, all the container being built) win over `Action`'s own `resolveDefaultClosureDependencyForEvaluationByName('schema')`, which resolves `getSchemaContainer()` — [`Invoker::call()`](../../../packages/support/src/Evaluation/Invoker.php) checks `$namedInjections` by `array_key_exists()` before it ever calls the `$resolveParameter` callback — so a `fn (Schema $schema) => [...]` passed to `schema()` receives *this* call's container, never the action's pre-existing one. [diverges](#getschema-returns-null-for-an-empty-schema-not-an-empty-schema) · [detail](#the-host-resolver-answers-only-when-the-action-declares-nothing) · [detail](#the-wizard-arm) |
| [`disabledSchema(bool\|Closure $condition = true)` / `isSchemaDisabled()`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `disabledSchema()` / `isSchemaDisabled()` | done | — | Filament-exact, body for body. Usable only because schemas ports the [`CanBeDisabled`](../schemas/can-be-disabled.md) pair — `getSchema()`'s disabled arm calls `Schema::disabled()` |
| [`formWrapper(bool\|Closure\|null $condition = true)` / `hasFormWrapper()`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `formWrapper()` / `hasFormWrapper()` | done | — | [diverges](#hasformwrapper-stays-true-for-a-wizard). Two consumers: [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php)'s `->submit(…)` / `->action(…)` branch, and [`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig), which derives its `submitHandler` prop from it |
| `getSchema()`'s wizard branch (the `isWizard()` arm and its `modifyWizardUsing()` hook) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `getSchema()` | done | — | [detail](#the-wizard-arm) · [diverges](#the-wizard-arm-drops-the-alpine-submit-handler). The sibling [`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php) concern the arm reads keeps its own row in [the package README](README.md#features) |
| Host schema-resolver fallback (`getDefaultActionSchemaResolver()`) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `getSchema()`'s `??` arm | done | — | A status reversal: the row read **skipped** for as long as the [`getDefaultAction*` family](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred) had no method to call, and flipped when that family landed — [detail](#the-host-resolver-answers-only-when-the-action-declares-nothing) |
| [`getMountedSchema(): ?Schema`](../../../packages/actions/src/Concerns/HasSchema.php) | — (Sylament-only; Filament's write bodies never need the modal schema, because they read the record with Eloquent's `attributesToArray()`) | done | — | The unambiguous read a write body uses — asking for `schema` by closure parameter resolves to the *containing* schema instead. [detail](#the-host-resolver-answers-only-when-the-action-declares-nothing) |
| Deprecated aliases (`form()`, `getForm()`, `disableForm()`, `disabledForm()`, `isFormDisabled()`) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | skipped | — | The project has never ported a deprecated Filament surface; each is a one-line delegate to the current name, all five of which are ported above |

This table has no [unaudited row](../README.md#an-unaudited-table-says-so), and that is a claim rather than an omission: [`vendor/filament/actions/src/Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) is 150 lines and has been walked end to end — every property, method and deprecated alias it declares has a row above.

Covered by [`HasSchemaTest`](../../../packages/actions/tests/Unit/Concerns/HasSchemaTest.php) for the trait in isolation, and end to end through a real mounted frame by [`HasActionsSchemaTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsSchemaTest.php) (mount seeds the bag, the schema renders bound to `mountedActionsData.0.*`, a client write round-trips into `$action->getData()`), [`HasActionsValidationTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsValidationTest.php) (a declared schema carrying a constrained component decides whether the submit reaches the action at all), [`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php) (the `<form>` window and the schema slot) and, for the wizard arm, [`HasWizardTest`](../../../packages/actions/tests/Unit/Concerns/HasWizardTest.php) plus [`HasSchemaWizardTest`](../../../packages/actions/tests/Integration/Concerns/HasSchemaWizardTest.php) (the uncontained wizard in the modal, its cancel/submit footer slots, the suppressed modal footer, and the non-wizard control that keeps its own).

## Details

### The wizard arm

`getSchema()` branches on `isWizard()` and, for an array-shaped declaration, wraps the whole thing in a schemas [`Wizard`](../schemas/wizard.md) before the array reaches `$schema->components()` — `contained(false)`, `startOnStep()`, the modal cancel and submit actions re-homed as the wizard's own footer slots, `skippable()`, `disabled()`, and the `modifyWizardUsing()` hook, whose closure receives the built wizard as the `wizard` named injection and may return a replacement (returning nothing keeps the built one).
The steps themselves come from the sibling [`HasWizard`](../../../packages/actions/src/Concerns/HasWizard.php) concern, ported member for member (the declaration order differs from vendor's; the set does not): `steps()` (which flips the wizard flag and forwards to `schema()`), `startOnStep()`, `skippableSteps()`, `modifyWizardUsing()` (which also flips the flag, so a hook alone makes an action a wizard), `isWizard()`, `isWizardSkippable()` and `getWizardStartStep()`.

The other half of the arm is the suppression on the modal side: [`CanOpenModal::getModalFooterActions()`](../../../packages/actions/src/Concerns/CanOpenModal.php) returns `[]` for a wizard, because the wizard renders its own back/next/cancel/ submit row and the modal must not render a second one underneath it.
Both halves are Filament's, and both are covered — the trait in isolation by [`HasWizardTest`](../../../packages/actions/tests/Unit/Concerns/HasWizardTest.php), and end to end through a real mounted modal by [`HasSchemaWizardTest`](../../../packages/actions/tests/Integration/Concerns/HasSchemaWizardTest.php).

One member of Filament's `HasWizard` is typed differently here: `$modifyWizardUsing` is `Wizard|Closure|null` where Filament's is `?Closure`, the same widening [`Wizard::nextAction()`](../schemas/wizard.md) already uses — `evaluate()`'s generic subtracts `Closure` from the property's union, so a bare `?Closure` would infer the *closure* as the evaluated type and make the `?? $wizard` fallback provably dead at level max.
The public setter stays Filament-exact at `?Closure`.

The arm carries one PHPStan assertion of its own: the array it hands `Wizard::make()` is annotated `/** @var array<Step> $steps */`, because `getSchema()`'s array arm is typed as the general component list a schema takes while `Wizard::make()` accepts steps only.
Filament's `Wizard::make()` carries the identical `@param array<Step> | Closure` and its `HasSchema` passes the same array in with no annotation, so the mismatch exists over there too and is simply not flagged.
The assertion is unchecked: a non-`Step` in a `steps()` declaration is a user error the wizard surfaces at render, where `Wizard::getSteps()` filters by `instanceof Step` and simply omits it.
Reverses if the array arm ever narrows its own type by the wizard flag.

### The host resolver answers only when the action declares nothing

`getSchema()` resolves `$this->schema ?? $this->getLiveComponentOrNull()?->getDefaultActionSchemaResolver($this)`, Filament's own line — so a resource page supplies one form to every action it mounts and no action has to repeat it.
Three things about the `??` are worth stating, because each is load-bearing and none is obvious.

It fires on `null` alone, so an action that declared `schema([])` never reaches the host: `[]` is not null, the resolver is not consulted, and the `=== []` guard below returns `null` — an explicit empty declaration stays a *decision*, not a gap for the host to fill.
The host read is the **nullable** accessor, the same rule [`CanRedirect`](action.md) follows: a detached action resolves nothing rather than throwing, and no `instanceof` re-check is written because `getLiveComponentOrNull()` already returns the contract carrying the hook.
And the resolver's closure is evaluated with the three named injections the declaration arm already passes — `form`, `schema` and `infolist`, all bound to the container being built — so a page may write `fn (Schema $schema)` or `fn ($form)` and get the same object.

`getMountedSchema()` is the other half, and it has no Filament counterpart because Filament does not need one.
A write body — a `CreateAction`'s process closure, an `EditAction`'s — has to read *the modal's* schema.
A closure the pipeline evaluates **with** the explicit pair does get it: `callMountedAction()` runs `$action->call(['form' => $schema, 'schema' => $schema])`, so an `action()` closure may take `Schema $schema` (`CreateAction::setUp()` does); `process()` forwards the same pair to a `using()` closure; and `fillForm()`'s closure gets it from `CanBeMounted`'s `mountUsing()` wrapper.
What does not work is a closure the pipeline evaluates with **no** injection of its own — `before()`, `after()`, the four form hooks, `mutateRecordDataUsing()` (which is handed `data` alone) — because `schema` then falls through [`Action::resolveDefaultClosureDependencyForEvaluationByName()`](action.md) to `getSchemaContainer()`, which is the **containing** schema: the page's form on a record page, and `null` on a table row or a page header.
The write bodies cannot take the pair as a parameter either, since they are what supplies it.
So the read is explicit instead: `getLiveComponentOrNull()?->getMountedActionSchema(mountedAction: $this)`, which asks the host for the frame it built for *this* action, and answers `null` for a confirmation-shaped action that mounted with no schema at all, or for an action with no host.
Filament's own bodies sidestep the problem by reading `$record->attributesToArray()` rather than a schema; ours read [`Schema::getStateFromRecord()`](../schemas/belongs-to-record.md), which needs the schema to read it *from*.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The wizard arm drops the Alpine submit handler

Filament's branch also calls `->alpineSubmitHandler("\$wire.{$this->getLivewireCallMountedActionName()}()")`, which hands the wizard's last-step submit button an Alpine expression to run.
Sylament has no Alpine and schemas' `Wizard` exposes no such setter: the submit slot holds the modal's own submit `Action`, which is a real `button[type="submit"]` inside the modal's `<form>` wrapper, so the browser submits it natively and the live component's `callMountedAction` runs off that.
Nothing is lost, and the port would have no place to put the string.

### `hasFormWrapper()` stays `true` for a wizard

Filament returns `$this->evaluate($this->hasFormWrapper) ?? (! $this->isWizard())` — the wrapper is off for a wizard, because Filament's wizard renders its own submit chrome and its steps are what a submit would act on.
The port keeps the literal `true` it shipped before the wizard arm landed, deliberately: a step renders no form of its own here, so the outer `<form>` is the only one, and dropping it would leave the last-step submit button with nothing to submit.

The consequences are the ones the rest of the modal pipeline rests on.
A schema-bearing action — wizard or not — always renders inside a real `<form>`, so [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php) always takes the `->submit($name)` arm and never the `->action($name)` one, and the modal window is therefore always the `<form>` that button submits.
The `->action(…)` half of that branch is live code with no reachable caller unless a user calls `formWrapper(false)` explicitly — it is kept because that is public API, not because the default can reach it.
Inside the wizard, Enter in a text input on a non-last step is intercepted by [the wizard controller](../schemas/wizard.md), which advances instead of submitting; on the last step it falls through to the native form submit.

### `getSchema()` returns null for an empty schema, not an empty `Schema`

An action that declares `schema([])`, or whose schema closure returns `[]`, gets `null` back rather than a populated-with-nothing `Schema`.
This is Filament's own behaviour (`is_array($modifiedSchema) && (! count($modifiedSchema))`), written here as `$modifiedSchema === []`, and two things downstream depend on it: [`mountedActionHasSchema()`](has-actions.md) is a cheap `count(getComponents())` over the result rather than a second probe, and `shouldOpenModal(checkForSchemaUsing:)` stays honest — an action whose only declared modal content is an empty schema does not silently acquire a modal it has nothing to put in.

### The `$schema` property is one member wider than its setter

The backing property is typed `Schema|array<Component|Action|ActionGroup>|Closure|null` where Filament's is `array|Closure|null`, and the public `schema()` setter stays Filament-exact at `array|Closure|null` — a bare `Schema` still cannot be assigned directly, only returned from a closure.
The public API is therefore unchanged; only the internal type widened.

The reason is the `is_array()` guard in `getSchema()`.
Filament writes that guard precisely so a closure may hand back an already-prepared `Schema` instead of a component array — a capability its own type system does not describe, because the property is narrower than what the guard admits.
Sylament's `evaluate()` carries a PHPStan generic Filament's does not (`@template T @param T|Closure $value @return T`, subtracting `Closure` from the property's union), so the same latent gap becomes a hard level-max error: with the narrow type, `$modifiedSchema` is `array|null` and the non-array branch is provably dead.
Widening the property keeps the branch **live** rather than deleting it, which is the difference between preserving a Filament capability and quietly dropping one.
See [the closure-typing convention](../../internals/php.md#the-closure-typing-convention).

_See also:_ the state path this schema binds to is **not** Filament's `mountedActions.{i}.data` — it is a sibling bag, for reasons that belong to the host trait that owns it: [the sibling-bag divergence](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor), recorded in the [rename table](../renames.md).
And an `Action` declared *inside* one of these schemas cannot inject `Set` or `Get` — [a limitation shared with Filament](has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema).
