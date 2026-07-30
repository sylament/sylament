# CanBeValidated (schemas)

[`Sylament\Schemas\Concerns\CanBeValidated`](../../../packages/schemas/src/Concerns/CanBeValidated.php)
— the root-tier validation trait on [`Schema`](schema.md), tracked here together
with the host-side execution machinery it delegates to (the validation members
of [`Concerns\HasSchemas`](has-schemas.md)), the component-tier contract
[`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php),
and [`ValidationException`](../../../packages/schemas/src/Exceptions/ValidationException.php).
It ports Filament's
[`Concerns/CanBeValidated.php`](../../../vendor/filament/schemas/src/Concerns/CanBeValidated.php)
and the validation members of
[`Concerns/InteractsWithSchemas.php`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php)
onto Symfony Validator: components implementing the contract build
`Symfony\Component\Validator\Constraint` objects, the schema collects them keyed
by absolute state path, and the host runs `ValidatorInterface` per path against
the raw live state — filling a per-schema error bag, recording stickiness in a
`#[LiveProp]`, and throwing `ValidationException` so ux-live-component's
exception listener re-renders the mutated component.

The port renames Filament's rule vocabulary to Symfony's —
`getValidationRules()` → `getValidationConstraints()`,
`dehydrateValidationRules()` → `dehydrateValidationConstraints()`,
`HasValidationRules` → `HasValidationConstraints` — recorded in the
[rename table](../renames.md); the payload changes ecosystem (Laravel rule
strings/objects → Symfony `Constraint`), so the name change makes the migration
break loud and searchable. The host contract declarations these rows implement
are tracked in [`interacts-with-schemas.md`](interacts-with-schemas.md),
including the skipped `currentlyValidatingSchema()`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/CanBeValidated.php`](../../../vendor/filament/schemas/src/Concerns/CanBeValidated.php)
  — the root-tier trait (walks, messages/attributes, `validate()`)
- [`schemas/src/Concerns/InteractsWithSchemas.php`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php)
  — the host-side members (`validate`/`validateOnly`/`onValidationError`/
  `prepareForValidation`/`getRules`/`getValidationAttributes`)
- [`forms/src/Components/Contracts/HasValidationRules.php`](../../../vendor/filament/forms/src/Components/Contracts/HasValidationRules.php)
  — the component contract this relocates into schemas

## Surface

Every point of the trait, the host machinery, the contract, and the exception.
_Blocked by_ names what has to land first. _Detail_ carries a **detail** link
(the write-up in [Details](#details), deleted once the row is **done**), a
**semantics** link (a permanent note in [Semantics](#semantics)), and/or a
**diverges** link (the permanent note in [Divergences](#divergences)), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Schema::getValidationConstraints()` collection walk](../../../packages/schemas/src/Concerns/CanBeValidated.php) | [`CanBeValidated::getValidationRules()`](../../../vendor/filament/schemas/src/Concerns/CanBeValidated.php) | done | — | [diverges](#the-collection-walk-cannot-skip-directly-hidden-child-schemas) |
| [`Schema::validate()`](../../../packages/schemas/src/Concerns/CanBeValidated.php) | [`CanBeValidated::validate()`](../../../vendor/filament/schemas/src/Concerns/CanBeValidated.php) | done | — | [diverges](#validate-returns-void-and-delegates-to-the-hosts-validateschema) |
| Schema-level `getValidationMessages()` / `getValidationAttributes()` | [`CanBeValidated`](../../../vendor/filament/schemas/src/Concerns/CanBeValidated.php) | skipped | — | [diverges](#schema-level-validation-messages-and-attributes-are-not-ported) |
| [`HasValidationConstraints` contract](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) | [`HasValidationRules.php` (forms)](../../../vendor/filament/forms/src/Components/Contracts/HasValidationRules.php) | done | — | [diverges](#the-component-contract-is-relocated-from-forms-into-schemas) |
| [`ValidationException`](../../../packages/schemas/src/Exceptions/ValidationException.php) | — | done | — | [diverges](#validationexception-rides-the-http-exception-re-render-pipeline) |
| [`HasSchemas::validateSchema()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::validate()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#validateschema-runs-symfonys-validator-per-state-path-instead-of-livewires-whole-component-validate) · [semantics](#the-bag-section-is-replaced-wholesale-on-every-validation) |
| [`HasSchemas::getErrorsForSchemaStatePath()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | — |
| [`HasSchemas::clearSchemaValidation()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](interacts-with-schemas.md#clearschemavalidation-is-scoped-where-filament-resets-the-whole-livewire-error-bag) |
| [`$validatedSchemas` `#[LiveProp]` + `#[PostHydrate(priority: -20)]` sticky re-validation](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](#sticky-re-validation-replaces-filaments-submit-only-model) · [semantics](#a-clean-silent-re-validation-keeps-the-schema-sticky) |
| [`setSchemaStateValidator()` `#[Required]` injection](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | — |
| Host `validateOnly()` per-path validation | [`InteractsWithSchemas::validateOnly()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#sticky-re-validation-replaces-filaments-submit-only-model) |
| Host `onValidationError()` hook | [`InteractsWithSchemas::onValidationError()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#the-onvalidationerror-hook-is-not-ported) |
| Host `getRules()` / `getValidationAttributes()` merges + `prepareForValidation()` | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#the-livewire-whole-component-validate-plumbing-is-not-ported) |
| Record-sourced constraints (an entity's own `#[Assert]` metadata) | — | todo | — | [detail](#record-sourced-constraints-validate-the-mapped-object-direction-decided) |

Coverage: the walk (path keying, `isNeitherDehydratedNorValidated()` skip
variants, child-schema recursion) by
[`CanBeValidatedTest`](../../../packages/schemas/tests/Unit/Concerns/CanBeValidatedTest.php);
the host machinery end-to-end through a real live host + validator — submit-path
bag/stickiness/typed exception, silent path, success clearing, scoped and full
clear, sticky re-validation across live round-trips including the stale-key
drop, and the `getState()`/`getStateSnapshot()` split — by
[`HasSchemasValidationTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasValidationTest.php);
the exception by
[`ValidationExceptionTest`](../../../packages/schemas/tests/Unit/Exceptions/ValidationExceptionTest.php);
the component-tier flag trio the walk guards on by the component
[`HasStateTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasStateTest.php)
(see [`component.md`](component.md)).

## Details

### Record-sourced constraints validate the mapped object (direction decided)

Now that a schema can bind a record and map state onto it
([`belongs-to-record.md`](belongs-to-record.md)), the question of what to do
with constraints an entity already declares — `#[Assert\Length]` on a property,
`#[UniqueEntity]` on the class — has a decided answer, though no implementation
yet. **Direction: validate the mapped object, after mapping.** A later epic
extends `getData()`'s tail to run `ValidatorInterface::validate($record)` and
fold the violations back into the host error bag by translating each violation's
property path to the owning component's state path — the Symfony Form idiom
(map, validate the object, flush only when valid). Class-level constraints come
free that way, which is the only route by which `UniqueEntity` can ever work: it
needs the managed object, not a scalar path value.

The alternative — pulling `getMetadataFor($class)` property constraints into the
per-field [`buildValidationConstraints()`](../forms/can-be-validated.md#the-subclass-seam-is-a-protected-builder-not-the-public-getter) seam
before validation — is **rejected**: typed constraints (a `#[Assert\Positive]`
on an `int` property) misfire against the raw, pre-cast string state a form bag
holds, and every message would then travel two different pipelines depending on
where the constraint was declared.

Caveat to carry into that epic, stated now because it is the non-obvious cost of
this direction: mapping happens *before* the object-level validation, so a
failing submit leaves the managed entity mutated in Doctrine's identity map even
though nothing is flushed. Symfony Form has exactly the same property, and the
escape is the same — `EntityManager::refresh()` on the failure path (or simply
never flushing and discarding the request's entity manager). Nothing in the M1
demo needs the feature: the Article entity carries no `#[Assert]` constraints,
and its unique-slug rule is served today by M1 · 1's escape hatch — a `Callback`
constraint querying the repository — which doubles as the motivating showcase for
the object-validation epic.

**Next step:** land it as a `getData()` tail plus a violation-path → state-path
translator, in the epic that also wants `unique`/`exists` as first-class
constraints.

### The onValidationError hook is not ported

Filament's host trait ships an empty `onValidationError(ValidationException
$exception)` hook that `validate()`/`validateOnly()` funnel failures through —
panel pages override it to dispatch scroll-to-error browser events. No consumer
exists here yet. **Next step:** port it (as a hook on `validateSchema()`'s
throw path) when the panels layer needs failure-side effects beyond the
re-render.

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### The bag section is replaced wholesale on every validation

`validateSchema()` does not merge into the error bag: it rewrites the schema's
entire bag section (or unsets it when clean) on every run. That is what clears
stale paths whose conditional constraints disappeared between requests — a
merge would leave a hidden-then-shown field's old errors behind. Reads
(`getErrorsForSchemaStatePath()`) merge across schema sections, but in practice
a path lives under one schema.

### A clean silent re-validation keeps the schema sticky

Only the submit path (`shouldThrow: true`) removes a key from
`$validatedSchemas` on success; the silent `#[PostHydrate]` pass clears the bag
section but leaves the key sticky. The asymmetry is deliberate: sticky means
"this schema failed a submit and has not succeeded one since", so errors keep
re-appearing if the user breaks the field again mid-edit. Stickiness is
harmless while clean and is cleared by the next successful submit or a scoped
`clearSchemaValidation()`.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional
ones say what would reverse them.

### The collection walk cannot skip directly-hidden child schemas

Filament's walks pass `withHidden: true` to `getChildSchemas()` and skip
`isDirectlyHidden()` child schemas. Sylament's `getChildSchemas()` takes no
`withHidden` parameter and no schema-level direct-hidden state exists
([component.md](component.md#getchildschemas-drops-the-withhidden-parameter)),
so hidden-guarding happens at component level only — the
`isNeitherDehydratedNorValidated()` check, which covers
hidden-and-not-dehydrated-when-hidden components. Reverses when schema-level
visibility traversal lands.

### validate() returns void and delegates to the host's validateSchema()

Filament's `validate(): array` returns Livewire's validated-data array — an
artifact of `prepareForValidation()`, which Symfony has no counterpart for
(violations produce no "validated data"), so returning one would lie: the port
declares `validate(): void`. It early-returns when the collected constraint map
is empty, **before** any host access, so a standalone constraint-free schema
validates trivially; otherwise it calls
`getLiveComponent()->validateSchema($this)`. Filament additionally
early-returns when no component survives the dehydration guard and brackets the
run with `currentlyValidatingSchema()` — both artifacts of the Livewire
whole-component model
([not ported](interacts-with-schemas.md#currentlyvalidatingschema-is-not-ported)),
with the same observable outcome. Permanent while the return-value semantics
differ, i.e. as long as Symfony Validator is the engine.

### Schema-level validation messages and attributes are not ported

Filament threads `getValidationMessages()`/`getValidationAttributes()` walks
(and the contract's `dehydrateValidationMessages()`/
`dehydrateValidationAttributes()`) into Livewire's `validate()` as separate
arrays. In the Symfony model the message lives *on the constraint object* —
forms' `validationMessages()` keys by constraint class and a
[post-pass](../forms/can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)
writes the named options onto the built constraints — so there is no separate
messages/attributes pipeline to collect. Permanent; that post-pass reaches
escape-hatch constraints too, so nothing is left needing a schema-tier walk.

### The component contract is relocated from forms into schemas

Filament's schemas package `instanceof`-checks
`Filament\Forms\Components\Contracts\HasValidationRules` — a soft reverse
dependency onto forms. The split-repo rule forbids schemas referencing a forms
symbol, so the contract lives in **schemas**, as
`Components\Contracts\HasValidationConstraints` (a component implements it →
`Components/Contracts/` placement; no `Interface` suffix per house style). It
carries `getValidationConstraints(): list<Constraint>` and the by-reference
path-keyed `dehydrateValidationConstraints(array &$constraints): void`; the
messages/attributes dehydrators drop per the divergence above. The namespace
move and the Rules → Constraints vocabulary rename are recorded in the
[rename table](../renames.md).

### ValidationException rides the HTTP-exception re-render pipeline

Filament throws Laravel's `Illuminate\Validation\ValidationException` and
Livewire's runtime catches it into the error bag. Sylament has no such runtime,
so `Sylament\Schemas\Exceptions\ValidationException` extends
`UnprocessableEntityHttpException`, carrying
`array<string, list<string>>` messages keyed by absolute state path:
ux-live-component's `LiveComponentSubscriber::onKernelException` re-renders the
**mutated** component on any `HttpException`, and the client morphs the 422
response in — so the keep-modal-open loop is pure server state, with no bespoke
JS. `symfony/http-kernel` stays an ambient dependency (the documented
deliberate omission). Reverses only if ux-live-component drops the
exception-to-re-render pipeline.

### validateSchema() runs Symfony's validator per state path instead of Livewire's whole-component validate

Filament's host `validate($rules, $messages, $attributes)` delegates to
Livewire — whole-object validation over the component's data, one shared error
bag. Sylament's `validateSchema(Schema $schema, bool $shouldThrow = true)`
validates one schema: it collects `$schema->getValidationConstraints()` and,
per path, reads the raw client state through
`$schema->getPropertyAccessor()->get($host, $path)` (the same read
`getRawState()` does) and runs `ValidatorInterface::validate($value,
$constraints)`, stringifying each violation message. Results key by
`$schema->getKey()` — every cached schema is keyed by
`getSchema()`/`cacheSchema()`, and a keyless schema throws a `LogicException` —
so validation scopes per schema and nested modals cannot cross-contaminate,
which is the reason ux-live-component's whole-object
`ValidatableComponentTrait` was rejected. The validator arrives via the
`#[Required]` setter `setSchemaStateValidator()`; a host built with `new`
outside DI fails fast with a `LogicException` naming the setter. Reverses only
if a whole-component validation model returns — not planned.

### Sticky re-validation replaces Filament's submit-only model

Deliberate UX divergence (design decision, 2026-07-29): Filament validates only
on submit, so an error stays on screen until the next submit regardless of
fixes. Here a failed submit records the schema key in the `$validatedSchemas`
`#[LiveProp]`, and `#[PostHydrate(priority: -20)]
revalidateValidatedSchemas()` re-validates each sticky schema silently on every
subsequent request — errors clear field-by-field as the user fixes them (the
`ValidatableComponentTrait` idiom). A sticky key whose schema no longer
resolves (a stale mounted-action name that dodged cleanup) is dropped, the
stale-stack self-heal idiom. The priority is a **cross-package contract** like
[the −10 it sits under](has-schemas.md#livewires-updatingupdated-state-hooks-become-a-predehydrateposthydrate-snapshot-and-diff):
−20 runs below the mounted-action schema re-cache (+10, actions) so mounted
schemas are resolvable, and below `callAfterSchemaStateUpdated` (−10) so
`afterStateUpdated` mutations (live slug generation) settle before
re-validation reads state. This loop also subsumes Filament's per-field
`validateOnly()`: **skipped**, and the sticky pass is deliberately shaped so a
`validateOnBlur`-style per-field opt-in could bolt on later — which is what
would reverse the skip.

### The Livewire whole-component validate plumbing is not ported

The host-level `getRules()`/`getValidationAttributes()` merges and
`prepareForValidation()` exist to feed every schema's rules into Livewire's
component-wide `validate()` call. With per-schema `validateSchema()` they have
no job. The schema-side half of the same pipeline — the
`mutateStateForValidation`/`pruneStateToMatchKeys` mutation layer — is recorded
in [schema.md](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer).
Permanent alongside it.
