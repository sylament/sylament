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
| [`PresenceGate::apply()`](../../../packages/schemas/src/Validation/PresenceGate.php) in front of each path's run | [`Validator::presentOrRuleIsImplicit()`](../../../vendor/laravel/framework/src/Illuminate/Validation/Validator.php) (Laravel) | done | — | [detail](presence-gate.md) |
| [`HasSchemas::getErrorsForSchemaStatePath()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [semantics](#the-read-across-identities-de-duplicates) |
| [The validation identity (`getSchemaValidationKey()` + `clearNestedSchemaValidation()`)](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](#a-components-child-schema-is-keyed-by-that-component-not-by-the-root) |
| [`form-validation-error` dispatch on the throwing branch](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::validate()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-throwing-branch-announces-form-validation-error-carrying-the-schema-identity) |
| [`HasSchemas::clearSchemaValidation()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](interacts-with-schemas.md#clearschemavalidation-is-scoped-where-filament-resets-the-whole-livewire-error-bag) |
| [`$validatedSchemas` `#[LiveProp]` + `#[PostHydrate(priority: -20)]` sticky re-validation](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](#sticky-re-validation-replaces-filaments-submit-only-model) · [semantics](#a-clean-silent-re-validation-keeps-the-schema-sticky) |
| [`setSchemaStateValidator()` `#[Required]` injection](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | — |
| Host `validateOnly()` per-path validation | [`InteractsWithSchemas::validateOnly()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#sticky-re-validation-replaces-filaments-submit-only-model) |
| Host `onValidationError()` hook | [`InteractsWithSchemas::onValidationError()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#the-onvalidationerror-hook-is-not-ported) |
| Host `getRules()` / `getValidationAttributes()` merges + `prepareForValidation()` | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#the-livewire-whole-component-validate-plumbing-is-not-ported) |
| Record-sourced constraints (an entity's own `#[Assert]` metadata) — the [`getData()`](belongs-to-record.md) tail | — | done | — | [diverges](#record-sourced-constraints-are-validated-on-the-mapped-object) · [diverges](#a-refused-submit-leaves-the-record-mutated) |
| [`HasSchemas::validateSchemaRecord()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [semantics](#a-host-holding-no-validator-skips-the-record-run-rather-than-refusing-to-map) · [semantics](#record-errors-are-sticky-but-the-silent-pass-cannot-reproduce-them) |
| [`Schema::getRecordViolationPathTranslator()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) + the translator it builds | — | done | — | [detail](violation-path-translator.md) |

Coverage: the walk (path keying, `isNeitherDehydratedNorValidated()` skip
variants, child-schema recursion) by
[`CanBeValidatedTest`](../../../packages/schemas/tests/Unit/Concerns/CanBeValidatedTest.php);
the host machinery end-to-end through a real live host + validator — submit-path
bag/stickiness/typed exception, silent path, success clearing, scoped and full
clear, sticky re-validation across live round-trips including the stale-key
drop, and the `getState()`/`getStateSnapshot()` split — by
[`HasSchemasValidationTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasValidationTest.php),
which also carries the identity cases over a `Section`-nested fixture host
(`ValidationNestedHost`): the root identity, the component's child-schema
identity, its sticky round trip, the unresolvable-identity drop, the
nested-clear on a root pass, the de-duplicated read, and the
`form-validation-error` payload on each identity shape plus its absence on the
silent path;
the exception by
[`ValidationExceptionTest`](../../../packages/schemas/tests/Unit/Exceptions/ValidationExceptionTest.php);
the component-tier flag trio the walk guards on by the component
[`HasStateTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasStateTest.php)
(see [`component.md`](component.md)); the record tail — the four fold
destinations (mapped leaf, nested cascade, class-level, unmapped property), the
[empty root key of a pathless schema](#a-pathless-schema-folds-its-untranslatable-violations-at-the-empty-key), the
clean pass, the mutated-but-unflushed reality, and the no-validator skip — by
[`BelongsToRecordValidationTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordValidationTest.php)
over real Doctrine entities, and over a real `Repeater::relationship()` by forms'
[`RepeaterRecordValidationTest`](../../../packages/forms/tests/Integration/Components/RepeaterRecordValidationTest.php).

## Details

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

### A host holding no validator skips the record run rather than refusing to map

`validateSchema()` throws a `LogicException` naming the `#[Required]` setter when
no validator was injected; `validateSchemaRecord()` returns instead. The
asymmetry follows from who calls them: `validateSchema()` is called on purpose,
so a missing validator is the caller's bug, while the record run is a **tail of
`getData()`** that every mapping call reaches. Throwing there would make a host
built with `new` — a unit test, a standalone script — unable to map at all,
which is a much larger break than skipping a check nothing asked for. A
container-built host always holds the validator, so production never takes this
arm.

### Record errors are sticky but the silent pass cannot reproduce them

A record failure records the schema key in `$validatedSchemas` exactly as a state
failure does, and that is what eventually clears the messages: the sticky
`#[PostHydrate]` pass
[replaces the bag section wholesale](#the-bag-section-is-replaced-wholesale-on-every-validation)
on the next request, and it re-runs only the **state** constraints — it has no
record to validate, `getData()` being a submit-time call. So a record error is
shown on the 422 re-render it caused and is gone after the user's next
interaction, rather than clearing field-by-field the way a state error does.
That is the intended shape (a `UniqueEntity` verdict is stale the moment
anything changes), not an oversight; what it costs is that the two error
families age differently in the same bag.

### A pathless schema folds its untranslatable violations at the empty key

The fold destination is the schema's **own** state path, and a schema declared
without one has `''` for it — so a class-level or otherwise untranslatable
violation is filed under the empty-string key rather than being dropped.
Consistent rather than degenerate: `getErrorsForSchemaStatePath('')` reads that
key back, and the message rides the `ValidationException` payload either way. It
is worth stating because the empty key belongs to no input, so a host wanting
those messages on screen has to ask for `''` explicitly — the same requirement a
`data`-rooted schema has for `data`, only easier to mistake for a missing value.
Pinned by a case in `BelongsToRecordValidationTest`, so the shape cannot drift
into a silent drop.

### A clean silent re-validation keeps the schema sticky

Only the submit path (`shouldThrow: true`) removes a key from
`$validatedSchemas` on success; the silent `#[PostHydrate]` pass clears the bag
section but leaves the key sticky. The asymmetry is deliberate: sticky means
"this schema failed a submit and has not succeeded one since", so errors keep
re-appearing if the user breaks the field again mid-edit. Stickiness is
harmless while clean and is cleared by the next successful submit or a scoped
`clearSchemaValidation()`.

### The read across identities de-duplicates

`getErrorsForSchemaStatePath()` merges every identity's bag section and returns
`array_values(array_unique($messages))`. Two identities can legitimately hold the
same path's message at once — a root submit fails and stays sticky, then a Wizard
step's Next re-validates that step's child schema — and both re-validate on every
request, so the raw merge would render one message twice under one field. The
de-duplication is on read rather than on write because each bag section is still
owned wholesale by the pass that filled it.

### A host built outside the container announces nothing

The `form-validation-error` dispatch is guarded on the `LiveResponder` that UX
LiveComponent's `ComponentToolsTrait` injects through a `#[Required]` setter: a
host built with `new` (every schemas/forms render fixture that never boots a live
request) never received one, and there is no browser to announce to. The bag, the
stickiness and the throw are unchanged for such a host — only the announcement is
skipped. The three Integration cases asserting the event on a container-built host
are what keep the guard from silently becoming always-false if the vendor property
is ever renamed.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional
ones say what would reverse them.

### Record-sourced constraints are validated on the mapped object

An entity's own metadata — `#[Assert\Length]` on a property, `#[UniqueEntity]`
on the class — is answered by validating the object
[`getData()`](belongs-to-record.md) has just written, not by pulling that
metadata into the per-field constraint build. The tail is
`HasSchemas::validateSchemaRecord()`: `ValidatorInterface::validate($record)`,
each violation's property path through the
[translator](violation-path-translator.md), the messages merged into the same
per-schema bag `validateSchema()` fills, the schema key recorded sticky, and
`ValidationException` thrown — the Symfony Form idiom (map, validate the object,
flush only when valid). Class-level constraints come free that way, which is the
only route by which `UniqueEntity` can ever work: it needs the managed object,
not a scalar path value.

The alternative — pulling `getMetadataFor($class)` property constraints into the
per-field [`buildValidationConstraints()`](../forms/can-be-validated.md#the-subclass-seam-is-a-protected-builder-not-the-public-getter)
seam before validation — is **rejected**: typed constraints (a
`#[Assert\Positive]` on an `int` property) misfire against the raw, pre-cast
string state a form bag holds, and every message would then travel two different
pipelines depending on where the constraint was declared.

**One class of failure this direction structurally cannot report, and how it is
covered instead.** Validating the mapped object happens *after* mapping, so it
only ever sees objects that mapping successfully produced — and a blank value for
a non-nullable target is exactly the case where mapping does not get that far. A
blank `data.author.name` against `Author::setName(string)` throws
`InvalidTypeException` from the accessor (an empty string
[dehydrates to `null`](component.md) before the write), so `getData()` dies
before any object exists to validate. No ordering of the tail fixes that: moving
the object validation earlier would mean validating an object that has not been
populated yet.

The hole is closed from the other side, by
[deriving requirement from the record's write-side types](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types):
a field bound to a non-nullable target emits `NotBlank` on its own, so the blank
submit fails *field* validation — asterisk, native `required` attribute, message
under the input, sticky field-by-field clearing — and the mapper crash becomes
unreachable rather than merely loud.

That does **not** disturb the rejection above, and the distinction is the whole
reason it does not: derivation reads a **type**, not a constraint. Nullability
comes from `ReflectionExtractor` reading the property's mutator, so there is no
`#[Assert]` metadata being pulled forward, no typed constraint evaluated against
raw pre-cast state, and no second message pipeline — the field emits the same
`NotBlank` it would have emitted from an explicit `required()`. Entity-declared
constraints all travel the one post-mapping route.

Permanent — reverses only if Symfony Validator stops being the engine. Filament
has no counterpart at all: Laravel rules are declared on the form, and an
Eloquent model carries no constraint metadata to consult.

### A refused submit leaves the record mutated

The non-obvious cost of validating what the mapping produced: mapping happens
*before* the object-level validation, so a failing submit leaves the managed
entity mutated in Doctrine's identity map even though nothing is flushed. Symfony
Form has exactly the same property, and the escape is the same —
`EntityManager::refresh()` on the failure path, or simply never flushing and
discarding the request's entity manager, which is what a live component doing
`getData()` then `flush()` already does. Both halves are pinned by test: the
record carries the rejected value after the exception, and a re-read from the
database still returns the old one.

This is deliberate rather than a defect, and the alternative is worse: validating
a copy would need a deep clone of an object graph the toolkit does not own, and
validating before mapping would mean validating the un-updated record. The
contract a consumer must hold up is therefore stated as a rule — **never flush on
the failure path** — not as something the tail can enforce.

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
`getRawState()` does), filters the path's constraints through
[`PresenceGate::apply()`](presence-gate.md) so a blank string reaches only the
implicit ones, and runs `ValidatorInterface::validate($value,
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
`ValidatableComponentTrait` idiom). The pass resolves an identity three ways: a
cached or declared name through `getSchema()`, a
[component's absolute key](#a-components-child-schema-is-keyed-by-that-component-not-by-the-root)
through `getSchemaComponent($key)?->getChildSchema()`, and — when neither
answers — nothing, so a sticky identity that no longer resolves (a stale
mounted-action name that dodged cleanup, a Step removed from a Wizard) is
dropped, the stale-stack self-heal idiom. The priority is a **cross-package contract** like
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

### A component's child schema is keyed by that component, not by the root

A schema's validation identity is `$schema->getParentComponent()?->getKey() ??
$schema->getKey()`. A root schema keeps the name the host registered it under
(`schema`, `mountedActionSchema0`); a **component's** child schema takes the
owning component's absolute key (`schema.basics::wizard-step`). Filament has no
such rule because it never validates a child schema in isolation — its Wizard
does (`Step::getChildSchema()->validate()`), and there the child schema inherits
the root's key, so a per-step pass would overwrite the whole form's bag section
and make the sticky `#[PostHydrate(-20)]` pass re-validate the entire form on the
next request. Three consequences, all deliberate:

- `revalidateValidatedSchemas()` gains a branch that resolves such an identity
  back through `getSchemaComponent($key)?->getChildSchema()` before falling
  through to `clearSchemaValidation()`, so a step's errors survive the round trip
  exactly as a root schema's do — and a step that has since disappeared from the
  schema self-heals the same way a stale mounted-action name does.
- Validating a **root** schema clears every sticky identity and bag section
  prefixed `<rootKey>.`: a root pass supersedes the passes over the components
  inside it, so the same message is never carried by two identities that both
  re-validate. A scoped `clearSchemaValidation('<rootKey>')` clears the same
  prefix, for the same reason — a reset that left the step identities behind
  would resurrect the cleared messages on the next sticky pass.
- The child schema itself is **not** re-keyed. Descendant keys stay
  Filament-exact, so a field keeps `schema.author` when it moves under a Tab or a
  Step and everything addressing components by key is untouched.

Reverses only if child-schema validation stops being a thing — the Wizard is its
first consumer and relation managers will be the second.

### The throwing branch announces form-validation-error, carrying the schema identity

Filament dispatches `form-validation-error` with `livewireId: $this->getId()` from
its Livewire `validate()`/`validateOnly()` overrides, and its client-side schema
handler uses that id to decide whether the event belongs to it. Sylament dispatches
the same event name from `validateSchema()`'s throwing branch with
`['schemaKey' => $identity]` instead: there is no live-component id producer on
this side, and the schema root the JS scopes to is identified by its key, so the
payload carries what the listener actually needs. The silent path
(`shouldThrow: false`) dispatches nothing — the sticky pass runs it on every
request and an announcement per request would re-scroll the page. Recorded as a
payload translation in [renames.md](../renames.md), not a behavioural divergence.

