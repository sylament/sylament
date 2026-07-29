# HasSchema (actions)

[`Sylament\Actions\Concerns\HasSchema`](../../../packages/actions/src/Concerns/HasSchema.php)
— the per-action schema **declaration**: an `Action` names the components its
modal should contain with `schema()`, and `getSchema(Schema $schema)` turns that
declaration into a real [`Schema`](../schemas/schema.md) when a host hands it one
to populate. The other half — building that container per mounted frame, keying
it `mountedActionSchema{$i}`, binding it to the client-writable
`mountedActionsData.{$i}` bag, caching it, filling it at mount, validating it at
submit and lifting its state back onto the action once it passes — is the
host's, and lives in
[`has-actions.md`](has-actions.md). This file covers only the action-side
surface.

Its sole consumer is [`Sylament\Actions\Action`](../../../packages/actions/src/Action.php),
via its trait block. It earns a file of its own rather than folding into
[`action.md`](action.md) because its counterpart lives in a second file: the
declaration and the host pipeline are two halves of one feature, and each needs
one addressable home to link the other from.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses). Every ported row is **done** and the
two unported ones are permanent omissions, but the row set is not all-settled:
the wizard arm and the host schema-resolver fallback are **skipped** against
Filament surface that exists, so the file records deviations rather than an
audit with nothing left in it.

Reference — the Filament source this is diffed against:

- [`actions/src/Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php)
  — the trait this ports
- [`actions/src/Concerns/HasWizard.php`](../../../vendor/filament/actions/src/Concerns/HasWizard.php)
  — the concern the unported wizard arm reaches into
- [`actions/src/Concerns/InteractsWithActions.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithActions.php)
  — where Filament consumes `getSchema()`, ported as
  [`HasActions::getMountedActionSchema()`](has-actions.md)

## Surface

_Blocked by_ names what has to land first. _Detail_ carries **detail** → the
write-up in [Details](#details), and/or **diverges** → the note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schema(array\|Closure\|null $schema)`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `schema()` | done | — | Filament-exact setter. The backing property is one member wider than the parameter — [diverges](#the-schema-property-is-one-member-wider-than-its-setter) |
| [`getSchema(Schema $schema): ?Schema`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `getSchema()` | done | — | Its three named injections (`form` / `schema` / `infolist`, all the container being built) win over `Action`'s own `resolveDefaultClosureDependencyForEvaluationByName('schema')`, which resolves `getSchemaContainer()` — [`Invoker::call()`](../../../packages/support/src/Evaluation/Invoker.php) checks `$namedInjections` by `array_key_exists()` before it ever calls the `$resolveParameter` callback — so a `fn (Schema $schema) => [...]` passed to `schema()` receives *this* call's container, never the action's pre-existing one. [diverges](#getschema-returns-null-for-an-empty-schema-not-an-empty-schema) · [diverges](#getschema-has-no-host-schema-resolver-fallback) · [diverges](#the-wizard-arm-is-not-ported) |
| [`disabledSchema(bool\|Closure $condition = true)` / `isSchemaDisabled()`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `disabledSchema()` / `isSchemaDisabled()` | done | — | Filament-exact, body for body. Usable only because schemas' [`CanBeDisabled`](../schemas/can-be-disabled.md) pair landed in the same epic — `getSchema()`'s disabled arm calls `Schema::disabled()`, which did not exist before it |
| [`formWrapper(bool\|Closure\|null $condition = true)` / `hasFormWrapper()`](../../../packages/actions/src/Concerns/HasSchema.php) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `formWrapper()` / `hasFormWrapper()` | done | — | [diverges](#hasformwrapper-defaults-to-plain-true). Two consumers: [`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php)'s `->submit(…)` / `->action(…)` branch, and [`ActionModal`](../../../packages/actions/templates/components/ActionModal/index.html.twig), which derives its `submitHandler` prop from it |
| `getSchema()`'s wizard branch (the `isWizard()` arm and its `modifyWizardUsing()` hook) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `getSchema()` | skipped | — | [diverges](#the-wizard-arm-is-not-ported). The sibling `HasWizard` concern the arm reads keeps its own row in [the package README](README.md#features) |
| Host schema-resolver fallback (`getDefaultActionSchemaResolver()`) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) `getSchema()`'s `??` arm | skipped | — | [diverges](#getschema-has-no-host-schema-resolver-fallback) |
| Deprecated aliases (`form()`, `getForm()`, `disableForm()`, `disabledForm()`, `isFormDisabled()`) | [`Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php) | skipped | — | The project has never ported a deprecated Filament surface; each is a one-line delegate to the current name, all five of which are ported above |

This table has no [unaudited row](../README.md#an-unaudited-table-says-so), and
that is a claim rather than an omission:
[`vendor/filament/actions/src/Concerns/HasSchema.php`](../../../vendor/filament/actions/src/Concerns/HasSchema.php)
is 150 lines and has been walked end to end — every property, method and
deprecated alias it declares has a row above.

Covered by
[`HasSchemaTest`](../../../packages/actions/tests/Unit/Concerns/HasSchemaTest.php)
for the trait in isolation, and end to end through a real mounted frame by
[`HasActionsSchemaTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsSchemaTest.php)
(mount seeds the bag, the schema renders bound to `mountedActionsData.0.*`, a
client write round-trips into `$action->getData()`),
[`HasActionsValidationTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsValidationTest.php)
(a declared schema carrying a constrained component decides whether the submit
reaches the action at all) and
[`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php)
(the `<form>` window and the schema slot).

## Details

Nothing outstanding — every row above is **done** or **skipped**; the permanent
deviations are in [Divergences](#divergences).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The wizard arm is not ported

Filament's `getSchema()` branches on `isWizard()` and, for an array-shaped
declaration, wraps the whole thing in a `Wizard` component — `contained(false)`,
`startOnStep()`, the modal cancel/submit actions re-homed as wizard actions, an
Alpine submit handler, `skippable()`, `disabled()`, and a `modifyWizardUsing()`
hook — all of it fed by the sibling
[`HasWizard`](../../../vendor/filament/actions/src/Concerns/HasWizard.php)
concern (`steps()`, `startOnStep()`, `skippableSteps()`, `isWizard()`,
`isWizardSkippable()`, `modifyWizardUsing()`, `getWizardStartStep()`). Schemas
ships no `Wizard` component, so the branch has nothing to build and the concern
nothing to configure; neither is composed onto `Action`. `Wizard` is named an
explicit non-goal for the current milestone — see
[roadmap M1's out-of-scope list](../roadmap.md#m1--entity-crud-slice) ("advanced
layouts (`Flex`, `FusedGroup`, `Tabs`, `Wizard`)"). Both return together, in one
change: the branch is meaningless without the component, and the component is
useless to actions without the branch.

### `hasFormWrapper()` defaults to plain `true`

Filament returns `$this->evaluate($this->hasFormWrapper) ?? (! $this->isWizard())`
— the wrapper is on unless the modal *is* a wizard, which renders its own
submit chrome. With
[no wizard arm](#the-wizard-arm-is-not-ported) that fallback collapses to a
literal `true`, and the port writes it as one rather than keeping a
`! false` that reads like a live branch.

The consequence is worth stating because it is what the rest of the epic rests
on: a schema-bearing action **always** renders inside a real `<form>`, so
[`CanOpenModal::getModalSubmitAction()`](../../../packages/actions/src/Concerns/CanOpenModal.php)
always takes the `->submit($name)` arm and never the `->action($name)` one, and
the modal window is therefore always the `<form>` that button submits. The
`->action(…)` half of that branch is live code with no reachable caller until
either a `Wizard` lands or a user calls `formWrapper(false)` explicitly — it is
kept because the latter is public API, not because the default can reach it.

### `getSchema()` returns null for an empty schema, not an empty `Schema`

An action that declares `schema([])`, or whose schema closure returns `[]`, gets
`null` back rather than a populated-with-nothing `Schema`. This is Filament's own
behaviour (`is_array($modifiedSchema) && (! count($modifiedSchema))`), written
here as `$modifiedSchema === []`, and two things downstream depend on it:
[`mountedActionHasSchema()`](has-actions.md) is a cheap
`count(getComponents())` over the result rather than a second probe, and
`shouldOpenModal(checkForSchemaUsing:)` stays honest — an action whose only
declared modal content is an empty schema does not silently acquire a modal it
has nothing to put in.

### `getSchema()` has no host schema-resolver fallback

Filament resolves `$this->schema ?? $this->getHasActionsLivewire()?->getDefaultActionSchemaResolver($this)`,
so a panel page can supply an implicit schema for every action it mounts without
each action declaring one. That hook belongs to the
`getDefaultAction*` family, which is tracked unported in
[has-actions.md](has-actions.md#the-getdefaultaction-host-override-hooks-are-not-ported)
— there is no method to call, so the `??` arm is not written. `$this->schema`
alone drives resolution here; a caller wanting a dynamic default passes a
closure to `schema()` directly. It returns with that hook family, which in turn
waits on the `panels` package.

### The `$schema` property is one member wider than its setter

The backing property is typed `Schema|array<Component|Action|ActionGroup>|Closure|null`
where Filament's is `array|Closure|null`, and the public `schema()` setter stays
Filament-exact at `array|Closure|null` — a bare `Schema` still cannot be
assigned directly, only returned from a closure. The public API is therefore
unchanged; only the internal type widened.

The reason is the `is_array()` guard in `getSchema()`. Filament writes that
guard precisely so a closure may hand back an already-prepared `Schema` instead
of a component array — a capability its own type system does not describe,
because the property is narrower than what the guard admits. Sylament's
`evaluate()` carries a PHPStan generic Filament's does not
(`@template T @param T|Closure $value @return T`, subtracting `Closure` from
the property's union), so the same latent gap becomes a hard level-max error:
with the narrow type, `$modifiedSchema` is `array|null` and the non-array branch
is provably dead. Widening the property keeps the branch **live** rather than
deleting it, which is the difference between preserving a Filament capability
and quietly dropping one. See
[the closure-typing convention](../../../CLAUDE.md).

_See also:_ the state path this schema binds to is **not** Filament's
`mountedActions.{i}.data` — it is a sibling bag, for reasons that belong to the
host trait that owns it: [the sibling-bag divergence](has-actions.md#per-mount-form-state-lives-in-a-sibling-bag-not-in-the-stack-descriptor),
recorded in the [rename table](../renames.md). And an `Action` declared *inside*
one of these schemas cannot inject `Set` or `Get` —
[a limitation shared with Filament](has-actions.md#set-and-get-are-unavailable-to-an-action-inside-a-mounted-action-schema).
