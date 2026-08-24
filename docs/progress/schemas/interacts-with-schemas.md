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
| [`validateSchema(Schema $schema, bool $shouldThrow = true): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas::validate()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-contract-declares-a-schema-scoped-validation-surface-livewire-owns-in-filament) · [diverges](can-be-validated.md#a-components-child-schema-is-keyed-by-that-component-not-by-the-root) |
| [`validateSchemaRecord(Schema $schema, object $record): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) |
| [`getErrorsForSchemaStatePath(?string $path): list<string>`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](#the-contract-declares-a-schema-scoped-validation-surface-livewire-owns-in-filament) |
| [`clearSchemaValidation(?string $schemaKey = null): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](#clearschemavalidation-is-scoped-where-filament-resets-the-whole-livewire-error-bag) |
| [`resolveSchemaByName(string $name): ?Schema`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source) |
| [`getSchemaComponent(string $key, bool $withHidden = false): Component\|Action\|ActionGroup\|null`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Contracts\HasSchemas::getSchemaComponent()`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php) | done | — | [diverges](#getschemacomponent-drops-the-child-container-skip-list) |
| [`dispatchBrowserEvent(string $eventName, array $payload = []): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | — | done | — | [diverges](#dispatchbrowserevent-is-a-sylament-only-contract-member) |
| [`callSchemaComponentMethod(string $componentKey, string $method, array $arguments = []): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas::callSchemaComponentMethod()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#callschemacomponentmethod-answers-through-a-browser-event-with-no-partial-render) |
| [`uploadSchemaComponentFile(string $componentKey, string $fileKey, Request $request): void`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) | [`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) (`use WithFileUploads`) | done | — | [diverges](#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
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
[`can-be-validated.md`](can-be-validated.md). `resolveSchemaByName()` and
`getSchemaComponent()` are covered directly by
[`HasSchemasTest`](../../../packages/schemas/tests/Unit/Concerns/HasSchemasTest.php);
`callSchemaComponentMethod()` (and, through it, `dispatchBrowserEvent()`) is
covered end-to-end by
[`HasSchemasComponentMethodTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasComponentMethodTest.php),
and `uploadSchemaComponentFile()` by
[`HasSchemasUploadTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasUploadTest.php)
beside it, which drives real `UploadedFile`s through
`TestLiveComponent::call()`'s third argument.
`resolveSchemaByName()` is also exercised through actions'
[`resolveSchemaComponentAction()`](../actions/has-actions.md), which now calls
it instead of carrying its own copy of the same reflection gate.

## Details

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
`validateSchemaRecord()` (the [record tail](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)
`Schema::getData()` calls on the host it belongs to),
`getErrorsForSchemaStatePath()` (the read side forms' `Field::getErrorMessages()`
uses), and `clearSchemaValidation()` (reset). The promotion rationale is the
same as for `schemas()`/`makeSchema()` above: `Schema::validate()` and the
actions pipeline call these on a host typed as *this interface*, so under
PHPStan level max the interface must carry them. Composing
[`HasSchemas`](../../../packages/schemas/src/Concerns/HasSchemas.php) is the
supported way to satisfy all four — `validateSchemaRecord()` included, since a
host implementing it by hand has to reproduce the translator, the bag section and
the sticky-key bookkeeping that
[`getErrorsForSchemaStatePath()`](#the-contract-declares-a-schema-scoped-validation-surface-livewire-owns-in-filament)
reads back. The machinery, semantics, and per-member divergences live in
[`can-be-validated.md`](can-be-validated.md).

### clearSchemaValidation() is scoped where Filament resets the whole Livewire error bag

Filament clears stale modal errors with Livewire's global `resetErrorBag()`,
which wipes every error on the component — opening one modal erases an
unrelated page-form's messages. `clearSchemaValidation(?string $schemaKey)`
drops one schema's stickiness and bag section — plus every identity prefixed
`<key>.`, the child schemas inside it, mirroring what validating a root does —
or all of both when the key is `null`, so clearing is scoped to the schema being
reset. Deliberate and
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

### `resolveSchemaByName()` is a Sylament-only extraction with no Filament source

Filament's `getSchemaComponent()` resolves the schema name by calling
`$this->getSchema($schemaName)` directly, and that is safe there because
Filament's `getSchema()` **does** gate: it delegates to `cacheSchema()`
(`vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:202-310`),
which tries the bare name then the `<name>Schema` suffix and accepts the
method only when its first parameter — or, with none, its return type — is
declared `Schema`, answering `null` for everything else. Sylament's
`getSchema()` throws instead of gating (the fail-fast house rule for a
missing schema, [above](#getschema-returns-a-non-null-schema-and-throws-on-a-missing-name)),
so it would invoke *any* same-named single-argument host method if called
with an unvalidated name — from a `#[LiveArg]`-supplied `$componentKey`, that
is arbitrary method invocation. `resolveSchemaByName(string $name): ?Schema`
is where Filament's `cacheSchema()` gate is hoisted to, standing between a
browser-supplied name and `getSchema()`: a cached schema wins outright;
otherwise a `<name>()`/`<name>Schema()` method is accepted only when its
first parameter (or, with none, its return type) is declared `Schema` — a
name it refuses answers `null` rather than throwing. What is genuinely
Sylament-only is the **name**: Filament has no member to rename from,
because its gate is a private step inside `cacheSchema()` rather than a
callable host method. Two hardenings sit on top of Filament's gate — Filament's own trait
carries `Schema`-shaped members its arms admit as well (`makeSchema(): Schema`,
`currentlyValidatingSchema(?Schema $schema)`), so this is a hardening past
Filament rather than a gap only ours has:
the candidate may not be a member `InteractsWithSchemas` itself declares
(`validateSchema(Schema $schema, bool $shouldThrow = true)`,
`validateSchemaRecord(Schema $schema, object $record)`, `makeSchema(): Schema`
and, through the suffix arm, `make`), and a first-parameter match may not
have more than one **required** parameter, since `getSchema()` passes exactly
one argument. Without the first, `validateSchemaRecord.x` from a hostile
client was an unhandled `ArgumentCountError` — a 500 on every live host —
and `makeSchema`/`make` minted an empty schema keyed under a bogus name. The
Unit cases in
[`HasSchemasTest`](../../../packages/schemas/tests/Unit/Concerns/HasSchemasTest.php)
pin both refusals — the four contract names, and a host's own schema-shaped
method needing a second argument — and the Integration case in
[`HasSchemasComponentMethodTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasComponentMethodTest.php)
pins the browser-facing half. What the gate still admits, exactly as Filament's does: a
host's **own** parameterless `Schema`-returning getter — tables'
`getTableFiltersForm(): Schema` is the live example — is invoked and, by
`getSchema()`, keyed under the browser's name (`Schema::key()` on the schema
the host already holds) for that request. Nothing is written beyond that
request-scoped key, and narrowing it further would mean refusing a shape
Filament accepts, so it stays. It is also an extraction, not a new
mechanism: `HasActions::resolveSchemaComponentAction()`
(`packages/actions/src/Concerns/HasActions.php:688-745` before this change)
carried the same gate inline; it now calls this method instead, so the gate
exists once. See the "Names with no Filament source" table in
[renames.md](../renames.md).

### `getSchemaComponent()` drops the child-container skip list

Filament declares `getSchemaComponent(string $key, bool $withHidden = false,
array $skipComponentsChildContainersWhileSearching = [])`. Sylament's
[`HasComponents::getComponent()`](schema.md) does not port that third
parameter (schema.md records why — the arm exists for the repeater sibling
context, which `getComponentByStatePath()` already covers here), so
`getSchemaComponent()` carries only `$key` and `$withHidden`. The schema name
is resolved through [`resolveSchemaByName()`](#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source)
rather than Filament's direct `getSchema()` call, because ours throws where
Filament's gates. Beyond
that, the body is Filament-exact: split on the first `.` (no `.` answers
`null`), then hand the **whole, unsplit** `$key` to `Schema::getComponent()`
with `isAbsoluteKey: true` — a component's own absolute key already carries
its owning schema's name as the leading path segment, so passing the
dot-stripped remainder instead would never match.

### `dispatchBrowserEvent()` is a Sylament-only contract member

Filament's Livewire component type carries `dispatch()` implicitly — every
Livewire component has it, so nothing declares it on `Contracts\HasSchemas`.
UX LiveComponent has no such ambient method; `Symfony\UX\LiveComponent\ComponentToolsTrait::dispatchBrowserEvent(string $eventName, array $payload = []): void`
is the closest equivalent, and [`HasSchemas`](has-schemas.md) composes that
trait so the contract can declare the method schemas' own
`callSchemaComponentMethod()`, the `form-validation-error` announcement
`validateSchema()`'s throwing branch makes (payload `{schemaKey}`, where Filament
sends `livewireId` —
[why](can-be-validated.md#the-throwing-branch-announces-form-validation-error-carrying-the-schema-identity)),
and forms' `Select` (through `getLiveComponent()`) all call. A host built with
`new` rather than resolved from the container has no injected `LiveResponder` and
throws (an uninitialized-typed-property error) the moment it calls this — every
real, container-built host has one via the trait's `#[Required]` setter, which is
why the validation announcement is the one call site guarded on it
([why](can-be-validated.md#a-host-built-outside-the-container-announces-nothing)).

### `callSchemaComponentMethod()` answers through a browser event, with no partial render

Filament's `callSchemaComponentMethod()` *returns* the exposed method's
result to Livewire, which either restricts the response to a partial render
of the affected schema container or, for a `#[Renderless]` method, skips the
render entirely and ships the return value alone. UX LiveComponent's
`#[LiveAction]` has no return channel and no partial-render mechanism, so
neither branch ports: the action always re-renders the whole host normally
and hands the result back as the browser event
`sylament:schema-component-method-result` with payload `{componentKey,
method, fileKey, result}` — exactly the channel `HasActions::mountAction()` and
notifications already dispatch through. (`fileKey` is `null` for every call
through this seam; it exists for
[`uploadSchemaComponentFile()`](#uploadschemacomponentfile-carries-the-bytes-beside-the-seam),
which shares the event and the private dispatcher behind it.) That dispatcher
runs one guard both arms pay for: the result is `json_encode`d with
`JSON_THROW_ON_ERROR` first, and a `JsonException` becomes a `LogicException`
naming the method and the component key. A browser-callable method returning
something JSON cannot carry — raw bytes that are not UTF-8, an `INF` — is a
programmer error, and the seam is the one place a reader can be shown it;
without the guard the value reaches `dispatchBrowserEvent()` and dies far
downstream as a broken response header. Note what the guard does **not**
catch: a closure and a property-less object both encode to `{}` quite happily,
so they arrive as an empty result rather than a throw. Cost, paid on every call: a search
round-trip through this seam re-renders the host where Filament's method
would not (bounded by the caller's own debounce, and the same cost every
`live()` field already pays per keystroke). A second, Sylament-only cost sits
beside it: the browser-supplied `$arguments` are checked against the target
method's signature (`argumentsMatchSignature()` — every key names a real
parameter, every parameter without a default is present) before the call, and
the call itself is wrapped in `catch (TypeError)`, both because a bare
`$component->{$method}(...$arguments)` spread from an unvalidated payload
would otherwise 500 the request — the price is that a `TypeError` genuinely
raised *inside* the called method's own body (not from the argument bind) is
indistinguishable from a mistyped argument and is masked the same way, as a
plain `null` result with no way for the caller to tell the two apart. The gate
also asks the reflected method whether it `isPublic()`, because `method_exists()`
admits non-public ones: a `protected`/`private` method carrying
`#[ExposedLiveComponentMethod]` would reach the spread and raise an `Error` —
which `catch (TypeError)` does not hold — so it answers `null` exactly like an
unexposed method instead. One shape stays a 500, accepted: a payload whose
`arguments` is not a JSON object fails the action's own `array $arguments`
binding as a `TypeError` inside the argument resolver, before the body this gate
lives in ever runs — unreachable from our own JS, which always sends an object.

### `uploadSchemaComponentFile()` carries the bytes beside the seam

A `#[LiveAction]`'s arguments are JSON, so a file cannot travel through
`callSchemaComponentMethod()` at all. Filament has no counterpart to point at
either: Livewire's `WithFileUploads` opens four RPC endpoints
(`_startUpload`/`_finishUpload`/`_uploadErrored`/`_removeUpload`) on every
component, and `RestrictsFileUploadsToSchemaComponents` exists purely to shut
them again for anything that is not a schema component. Ours is a **second
`#[LiveAction]` beside the seam**, closed by construction rather than opened and
then fenced:

- **The bytes ride the same live request.** UX Live's transport already does
  multipart — `Component.files(key, input)` stashes a real `<input type="file">`
  and `performRequest()` appends one `FormData` part per file beside the props
  blob — so the action needs no route, no signer and no second security
  boundary. The part is named `sylament_upload_<fileKey>` and is read straight
  off `$request->files`; **nothing about the file is taken from browser-supplied
  arguments**, which is the whole reason this is not a file arm on the seam's
  own `$arguments` spread.
- **Two gates, both narrow.** The component is resolved through the same
  `getSchemaComponent()` path the seam uses (so a hidden component, an unknown
  key and a key naming a host-contract member all answer `null`), and only a
  component implementing
  [`Components\Contracts\AcceptsUploadedFiles`](../../../packages/schemas/src/Components/Contracts/AcceptsUploadedFiles.php)
  is handed the file. An implementor may still refuse — `null` for "not
  mounted, disabled", `['ok' => false, 'message' => …]` for a constraint that
  turned the file away without writing anything.
- **The file key is shape-checked before it names anything.** `$fileKey` must
  match `^[A-Za-z0-9-]{1,64}$`, checked *before* it is concatenated into a
  multipart part name and before an implementor may use it as a state key. A
  refused key answers `null` with no part read.
- **The result is FIFO per `(componentKey, method, fileKey)`.** The action
  answers on the seam's own event, so the browser helper's matcher is reused
  unchanged — but the payload carries `fileKey`, because several uploads to
  **one** component are legitimately in flight at once. Action order alone would
  usually match them correctly; keying makes it true by construction, so a mixed
  outcome (one file accepted, one refused) cannot paint the refusal on the wrong
  item. Within one key the queue is still FIFO, in action order — the browser
  helper
  ([`utils/schema_component_method.ts`](../../../packages/schemas/assets/src/utils/schema_component_method.ts))
  keys it `(componentKey, method, fileKey)`, so a `callSchemaComponentMethod()`
  call, which passes no file key, lands in exactly the queue it used before.
- **A `_batch` request hands the same `FileBag` to every action in it.** Written
  down rather than prevented, because it is inherent to UX Live's batching:
  `[upload(A), upload(B)]` in one batch sees the same files, and a batch mixing
  an upload with an unrelated action runs both with a multipart body present.
  The file is still gated per component and per part name, so B only ever
  receives the part B's own `fileKey` names.
- **What it costs, stated up front.** Every upload re-renders the host with the
  whole props blob in the same body (eight dropped files are eight sequential
  round trips); `fetch()` reports no upload progress, so a client shows an
  indeterminate indicator; and there is no abort — a cancelled upload becomes
  "remove the file once the request settles". A dedicated signed route would buy
  progress and abort back at the cost of a route, a signer and a second security
  boundary; it is the recorded follow-up if progress turns out to be
  non-negotiable.

`RestrictsFileUploadsToSchemaComponents` is therefore **skipped**, not blocked:
it fences an RPC surface this port never opens.

_See also: the global [rename table](../renames.md) — the interface ⇄ trait name
swap, the `Filament\Schemas\Contracts` → `Sylament\Schemas\Contracts`
namespace prefix, `resolveSchemaByName()` and the
`sylament:schema-component-method-result` browser event are recorded there._
