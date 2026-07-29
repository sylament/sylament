# InteractsWithSchemas (schemas)

[`Sylament\Schemas\Contracts\InteractsWithSchemas`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php)
is the interface a schema **host** implements — the object that owns one or more
named `Schema` instances and answers state lookups against them. It ports
Filament's [`Contracts\HasSchemas`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php),
under a swapped name: Filament's interface is `HasSchemas` and its trait is
`InteractsWithSchemas`; Sylament reverses the pair, so this interface is
`InteractsWithSchemas` and the implementing trait is
[`Concerns\HasSchemas`](has-schemas.md). Every method it declares is supplied by
that trait.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Contracts/HasSchemas.php`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php)
  — the host interface this reshapes
- [`schemas/src/Concerns/InteractsWithSchemas.php`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php)
  — Filament's trait, where `schemas()`/`makeSchema()` live (promoted onto the
  interface here)

## Surface

Every point of this contract. _Blocked by_ names what has to land first.
_Detail_ carries a **detail** link to the write-up in [Details](#details)
(deleted once the row is **done**), a **diverges** link to the permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schemas(): array<string>`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-contract-declares-schemas-and-makeschema-that-filament-keeps-only-on-the-trait) |
| [`getSchema(string $name): Schema`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Contracts\HasSchemas::getSchema()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | done | — | [diverges](#getschema-returns-a-non-null-schema-and-throws-on-a-missing-name) |
| [`makeSchema(): Schema`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-contract-declares-schemas-and-makeschema-that-filament-keeps-only-on-the-trait) |
| [`cacheSchema(string $name, ?Schema $schema): ?Schema`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-contract-declares-schemas-and-makeschema-that-filament-keeps-only-on-the-trait) |
| [`hasCachedSchema(string $name): bool`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-contract-declares-schemas-and-makeschema-that-filament-keeps-only-on-the-trait) |
| [`getOldSchemaState(?string $path): mixed`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Contracts\HasSchemas::getOldSchemaState()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | done | — | [diverges](#getoldschemastate-takes-a-nullable-path) |
| [`validateSchema(Schema $schema, bool $shouldThrow = true): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas::validate()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-contract-declares-a-schema-scoped-validation-surface-livewire-owns-in-filament) |
| [`getErrorsForSchemaStatePath(?string $path): list<string>`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](#the-contract-declares-a-schema-scoped-validation-surface-livewire-owns-in-filament) |
| [`clearSchemaValidation(?string $schemaKey = null): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](#clearschemavalidation-is-scoped-where-filament-resets-the-whole-livewire-error-bag) |
| `getSchemaComponent($key, $withHidden, $skip…)` — absolute-key component addressing | [`Contracts\HasSchemas::getSchemaComponent()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | todo | — | [detail](#getschemacomponent-absolute-key-addressing-is-not-declared) |
| `makeFilamentTranslatableContentDriver()` | [`Contracts\HasSchemas::makeFilamentTranslatableContentDriver()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | todo | — | [detail](#the-translatable-content-driver-method-is-not-declared) |
| `currentlyValidatingSchema(?Schema)` | [`Contracts\HasSchemas::currentlyValidatingSchema()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | skipped | — | [diverges](#currentlyvalidatingschema-is-not-ported) |
| `getDefaultTestingSchemaName()` | [`Contracts\HasSchemas::getDefaultTestingSchemaName()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | blocked | TestsSchemas (testing helper) | [detail](#the-testing-contract-method-is-not-declared) |

Every declared method is covered: `schemas()`, `getSchema()`, `makeSchema()`,
`cacheSchema()` and `hasCachedSchema()` directly by
[`HasSchemasTest`](../../../packages/schemas/tests/Unit/Concerns/HasSchemasTest.php);
`getOldSchemaState()` indirectly through the `afterStateUpdated` diff in
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php).
`cacheSchema()`/`hasCachedSchema()` are also exercised through the interface by
actions' [`HasActionsSchemaTest`](../../../packages/actions/tests/Integration/Concerns/HasActionsSchemaTest.php),
which is why they are declared here at all: `HasActions::getMountedActionSchema()`
calls them on a `$this` narrowed to this interface. The three validation
methods are covered end-to-end by
[`HasSchemasValidationTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasValidationTest.php);
their machinery and semantics are tracked in
[`can-be-validated.md`](can-be-validated.md).

## Details

### `getSchemaComponent()` absolute-key addressing is not declared

Filament's `getSchemaComponent(string $key, bool $withHidden = false, array
$skip = [])` walks the host's schemas by absolute component key and returns the
matched `Component | Action | ActionGroup | null`. Nothing in the port addresses
a component by key yet, so the method is not declared on the interface. **Next
step:** declare it when the first component-addressing consumer lands (action
resolution or partial render), returning the ported component/action union.

### The translatable-content-driver method is not declared

Filament's `makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver`
supplies the per-host driver for translatable field content. The port has no
translatable-content layer, so the method is absent. **Next step:** declare it
with the translatable-content driver port, renamed off the `Filament` prefix per
the systematic idiom translation.

### The testing contract method is not declared

`getDefaultTestingSchemaName()` hangs off the schema-testing helpers the port
has not reached: it is **blocked** on the `TestsSchemas` testing helper and
returns to the interface in the same change that ports it.

## Divergences

### The schema-host interface and trait swap names with Filament

Filament's **interface** is `Contracts\HasSchemas` and its **trait** is
`Concerns\InteractsWithSchemas`. Sylament swaps the two labels: the interface is
`Contracts\InteractsWithSchemas` (here) and the trait is
[`Concerns\HasSchemas`](has-schemas.md). `Has*` reads as a capability trait and
`InteractsWith*` reads as a role/contract elsewhere in the codebase; the swap
keeps that convention. It is a naming choice, not a deferral — nothing reverses
it.

### The host contract declares `schemas()` and `makeSchema()` that Filament keeps only on the trait

Filament's `Contracts\HasSchemas` declares neither `schemas()` nor
`makeSchema()` — both live only on the trait. Sylament promotes both onto the
interface, so any host must expose the schema-name list and the host-bound schema
factory even if it does not use the [`HasSchemas`](has-schemas.md) trait.
`cacheSchema()` and `hasCachedSchema()` are promoted for the same reason, with a
concrete forcing case: actions'
[`HasActions::getMountedActionSchema()`](../actions/has-actions.md#the-per-mount-schema-is-not-seeded-with-a-record-or-an-operation)
narrows `$this` to *this interface* before registering a mounted action's schema,
so under PHPStan level max the calls are only legal if the interface carries
them. Consequence: the interface is a wider shape than Filament's, not a 1:1
port of the contract's method set.

### `getSchema()` returns a non-null `Schema` and throws on a missing name

Filament types `getSchema(string): ?Schema` and returns `null` for an unknown or
uncacheable name. Sylament types it `getSchema(string): Schema`, and the trait
throws a `RuntimeException` when the named method is absent or does not return a
`Schema`. This is the fail-fast, no-silent-fallback house rule: a missing schema
is a programming error, not an absent value. Reversing to nullable would only be
needed if a caller must probe for an optional schema.

### `getOldSchemaState()` takes a nullable path

Filament declares `getOldSchemaState(string $statePath)`. Sylament declares
`getOldSchemaState(?string $path)` and returns `null` for a `null` path, because
the port's `Component::getStatePath()` can return `?string` and the caller passes
it straight through. It is a minor signature widening driven by the state-path
nullability, not a behavioral change to the snapshot itself.

### The contract declares a schema-scoped validation surface Livewire owns in Filament

Filament's contract declares no validation method beyond
`currentlyValidatingSchema()` — actual validation is the Livewire base's
component-wide `validate()`/error bag, reached as `$livewire->validate(…)` and
`$livewire->getErrorBag()`. Sylament has no Livewire base, so the host contract
itself carries the schema-scoped surface: `validateSchema()` (execution),
`getErrorsForSchemaStatePath()` (the read side forms' `Field::getErrorMessages()`
uses), and `clearSchemaValidation()` (reset). The promotion rationale is the
same as for `schemas()`/`makeSchema()` above: `Schema::validate()` and the
actions pipeline call these on a host typed as *this interface*, so under
PHPStan level max the interface must carry them. The machinery, semantics, and
per-member divergences live in [`can-be-validated.md`](can-be-validated.md).

### clearSchemaValidation() is scoped where Filament resets the whole Livewire error bag

Filament clears stale modal errors with Livewire's global `resetErrorBag()`,
which wipes every error on the component — opening one modal erases an
unrelated page-form's messages. `clearSchemaValidation(?string $schemaKey)`
drops one schema's stickiness and bag section (or all of both when the key is
`null`), so clearing is scoped to the schema being reset. Deliberate and
permanent — the global wipe was a Livewire single-bag artifact, not a feature.

### currentlyValidatingSchema() is not ported

Filament stores the currently-validating schema on the Livewire component for
two reasons: to scope the `prepareForValidation()` state mutation to one schema
instead of all, and to break infinite loops in schemas that self-validate (the
table query builder crashing while an action submits). Neither exists here —
there is no `prepareForValidation` layer
([schema.md](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer))
and no table self-validation, and `validateSchema()` is already scoped by its
`Schema` argument. Reverses only if a `prepareForValidation`-like mutation pass
returns.

_See also: the global [rename table](../renames.md) — the interface ⇄ trait name
swap and the `Filament\Schemas\Contracts` → `Sylament\Schemas\Contracts`
namespace prefix are recorded there._
