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
| Record-sourced constraints (an entity's own `#[Assert]` metadata, since M7 · 6 the taken-key and changed-key rules, and since M7 · 7 the blank-key rule) — the [`getData()`](belongs-to-record.md) tail | — | done | — | [diverges](#record-sourced-constraints-are-validated-on-the-mapped-object) · [diverges](#a-refused-submit-leaves-the-record-mutated) · [diverges](#a-taken-assigned-key-is-a-field-error-not-a-unique-violation) · [diverges](#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine) · [diverges](#a-blank-assigned-key-is-refused-before-anything-is-written) |
| [`HasSchemas::validateSchemaRecord()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [semantics](#a-host-holding-no-validator-skips-the-record-run-rather-than-refusing-to-map) · [semantics](#record-errors-are-sticky-but-the-silent-pass-cannot-reproduce-them) |
| [`Schema::validateRecordKey()` / `isRecordKeyValidated()`](../../../packages/schemas/src/Concerns/CanBeValidated.php) — the switch over the record check's blank-key and taken-key passes | — | done | — | [semantics](#the-key-rules-switch-off-per-schema-never-the-changed-key-one) |
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

### The key rules switch off per schema, never the changed-key one

`Schema::validateRecordKey(bool|Closure $condition = true)` switches the two key
rules that only ever answer for a **new** record — the
[blank-key pass](#a-blank-assigned-key-is-refused-before-anything-is-written)
and the [taken-key pass](#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)
— on or off for the schema whose `getData()` runs; `isRecordKeyValidated()`
reads it through a plain `evaluate()`, so a closure receives the schema's usual
injections, `$record` included: the record the schema is bound to. That is the
record being mapped where the caller binds it before `getData()` (the resource
create page, `CreateAction`'s relationship arm, an edit), and null under a
class-string binding (`CreateAction`'s plain arm).
`HasSchemas::validateSchemaRecord()` reads it once, after
the changed-key pass and the record's own constraints raised nothing, and skips
both passes when it is false — which covers `ReplicateAction`'s second call
after `beforeReplicaSaved()` too, since that reads the same schema.
`InteractsWithSchemas` did not change.

Switched off, the application owns a new record's key. A taken one reaches the
flush, where the database's primary key refuses it and the entity manager
closes. A blank key field over a non-nullable property is still refused under
the field, since the field is required on its own; one that is not required (it
declares `required(false)`, or its property is nullable) is not, and what happens
then depends on what the blank reaches the record as. A blank that
reaches the record as a string — whitespace — then reaches the create seam's
by-name refusal, whose advice names the switch. An empty field dehydrates to
null instead: over a nullable property Doctrine refuses it inside `persist()`
with its own `EntityMissingAssignedId`, and over a non-nullable one the mapping
fails first with Symfony's `InvalidTypeException`
([why the seams cannot see a null](association-reader.md#a-blank-assigned-identifier-is-read-after-persist-where-a-record-is-created)). Its use is
an application that answers uniqueness itself — its own `#[UniqueEntity]`, which
runs with the record's own constraints and pre-empts the probe whether the switch
is on or not — or a save adding many new records whose one `SELECT` each it would
rather not pay.

What it never reaches, by design:

- the **changed-key pass** and its put-back. Off, it would bring back the
  IDENTITY generator's silent *Saved* over an edited key; it costs no statement;
  and tables' editable cell runs it with no schema at all, so a form and a cell
  over one key would disagree;
- the record's own constraints, and a host with no validator, which skips the
  whole tail as before;
- every by-name `LogicException` at the create seams, `Select`, `Repeater`, a
  singular relationship (`Group`, `Section` or `Fieldset` with `relationship()`)
  and `ReplicateAction` — developer errors, which no switch silences.

The flag is the schema's own, with no parent walk: the record check runs on the
schema whose `getData()` ran. `Schema::configureUsing()` reaches every schema as a
default — it runs inside `make()` — which a page's `form()` or an action's schema
resolver then overrides. One reach is recorded rather than closed: the edit
page's `EditAction` modal wraps the form in a fresh schema
([`EditRecord`](../../../packages/panels/src/Resources/Pages/EditRecord.php)), so a
flag set in `Resource::form()` does not reach it there — which matters only for
the new records an edit creates.

Filament has no automatic key rules, so the name is the toolkit's; it follows
Filament's `bool | Closure $condition = true` switches (`deferLoading()`,
`persistTab()`), and `renames.md` carries the row. Pinned by
[`CanBeValidatedRecordKeyTest`](../../../packages/schemas/tests/Unit/Concerns/CanBeValidatedRecordKeyTest.php)
— the default, a literal and a closure flag, and `configureUsing()`'s reach — and
[`HasSchemasRecordKeySwitchTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasRecordKeySwitchTest.php):
switched off, a taken key reaches the flush and a blank mapped one is not
refused, with no statement either way, while a changed key is still refused and
put back and the record's own constraints still answer.

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
[translator](violation-path-translator.md) — filed under the component owning it
when that component shows its own errors, at the schema's own path otherwise
([why](violation-path-translator.md#a-message-is-filed-only-under-a-component-that-shows-its-own-errors))
— the messages merged into the same
per-schema bag `validateSchema()` fills, the schema key recorded sticky, and
`ValidationException` thrown — the Symfony Form idiom (map, validate the object,
flush only when valid). Class-level constraints come free that way, which is the
only route by which `UniqueEntity` can ever work: it needs the managed object,
not a scalar path value.

Since [M7 · 6](../roadmap.md#m7--composite-identifiers) the tail is several
validator calls rather than one — four since M7 · 7 — in an order each of them
depends on. First the
[changed-key pass](#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine),
validated as a `Callback` in the record's own context, because it puts a moved
key back and has to run before anything can flush it or read it. Then the
record's own constraints, unchanged, their violations added to the same list.
Then — **only when neither raised anything** — the
[blank-key pass](#a-blank-assigned-key-is-refused-before-anything-is-written),
and, only when that raised nothing either, the
[taken-key pass](#a-taken-assigned-key-is-a-field-error-not-a-unique-violation),
each a `Callback` of its own: the taken one binds form values into a lookup, so
it runs only on a record whose own constraints passed, and an application
`UniqueEntity` answers before it. Every key message is raised through
`ExecutionContext::buildViolation()`, so it is translated through the
`validators` domain and path-translated exactly as the record's own violations
are; nothing throws a `ValidationException` by hand, which would render nothing.
The `Callback` is
[`RecordKeyGuard::constraint()`](../../../packages/schemas/src/Validation/RecordKeyGuard.php),
`@internal`: one violation per refusal, at the refusal's path. It was a private
method of the host until [M7 · 10](../roadmap.md#m7--composite-identifiers)
moved it out for its second reader, tables' editable cell, which validates its
own refusal through it so the column's validator translates the message as the
form's does ([has-columns.md](../tables/has-columns.md)).
Since M7 · 10 the blank and taken passes run only while the schema's
[`validateRecordKey()`](#the-key-rules-switch-off-per-schema-never-the-changed-key-one)
switch is on, which it is by default; the changed-key pass and the record's own
constraints run either way. The host now files a
message **once per path**, so a `ValidationException` never lists one twice under
a field. `InteractsWithSchemas` did not change. The order and the four are pinned
by [`HasSchemasIdentifierGuardTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasIdentifierGuardTest.php),
and the constraint itself by
[`RecordKeyGuardTest`](../../../packages/schemas/tests/Unit/Validation/RecordKeyGuardTest.php).

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
a field bound to a non-nullable target emits its required constraint on its own, so the blank
submit fails *field* validation — asterisk, native `required` attribute, message
under the input, sticky field-by-field clearing — and the mapper crash becomes
unreachable rather than merely loud.

That does **not** disturb the rejection above, and the distinction is the whole
reason it does not: derivation reads a **type**, not a constraint. Nullability
comes from `ReflectionExtractor` reading the property's mutator, so there is no
`#[Assert]` metadata being pulled forward, no typed constraint evaluated against
raw pre-cast state, and no second message pipeline — the field emits the same
constraint it would have emitted from an explicit `required()`. Entity-declared
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

One field is the exception, since [M7 · 6](../roadmap.md#m7--composite-identifiers):
the record's key. The changed-key pass puts a moved identifier back on the spot,
so a record refused for moving its key is left holding the key it was loaded
with — every other field the mapping wrote stays written, as above — and a flush
that runs later in the request anyway has no key to write. That is what keeps a
refused key out of the identity map's way: Doctrine keys a managed record by the
old one, and a table re-render or a second write in the same request reads it.

This is deliberate rather than a defect, and the alternative is worse: validating
a copy would need a deep clone of an object graph the toolkit does not own, and
validating before mapping would mean validating the un-updated record. The
contract a consumer must hold up is therefore stated as a rule — **never flush on
the failure path** — not as something the tail can enforce.

### A taken assigned key is a field error, not a unique violation

Filament has no counterpart: Laravel validates what the form declares, so an
Eloquent model whose key the application assigns meets the database's unique
violation unless the form declares `unique()` on that field. Here the record
check answers it. A new record whose identifier is **assigned** — a composite
key, which Doctrine gives no generator, or a single one the application sets —
and already held, by a stored row, by a record the
identity map holds, or by another new record the same save would insert, is
refused with *"This value is already used."* under every identifier field that
is no association and that a component of the schema maps — both halves of a
scalar pair the form types, never the association a derived identity's records
share — so the message sits on the fields the user can change; when the schema
maps none of them it sits under the first, which folds to the schema's own path
([where it lands](association-reader.md#a-taken-identifier-is-looked-up-once-per-new-record-after-the-records-own-constraints-pass)).
Nothing is written, and the entity manager stays open, short of a concurrent
insert of the same key ([recorded](association-reader.md#what-the-taken-key-probe-cannot-see)).
Before [M7 · 6](../roadmap.md#m7--composite-identifiers) it was a
`UniqueConstraintViolationException` that closed the manager, or Doctrine's
`EntityIdentityCollisionException` with the new record still scheduled.

It needs no declaration — no `UniqueEntity`, no `unique()` on a key field. The
message is `UniqueEntity`'s own, so an application that declares one as well sees
one message: its constraint is among the record's own, which run first, and the
probe is skipped whenever any of them fails. The cost of that order is
enumerated rather than hidden: a record failing another constraint as well
learns its key is taken one submit later, exactly as the base showed that
constraint's message alone.

Two ways change the message, both Symfony's own rather than a hook of ours. Per
entity, an `#[UniqueEntity(fields: [...], message: ..., errorPath: ...)]` runs
with the record's own constraints and so pre-empts the probe: one message, the
application's, under the field `errorPath` names. Application-wide, the
`validators` catalogue's entry for *This value is already used.* — which Symfony
already translates into 57 locales — reaches it as it reaches every record
violation. And [`validateRecordKey(false)`](#the-key-rules-switch-off-per-schema-never-the-changed-key-one)
switches the probe off. A field's `validationMessages()` cannot reach it, since
it is no constraint of the field's.

Every create seam reaches it, because every one ends in `getData()`:
[`CreateRecord`](../panels/README.md#createrecord), plain and nested;
`CreateAction` on a list, a relation manager, `ManageRelatedRecords` and a record
page's header, and its create-another run; `Select`'s create-option default; a
relationship `Repeater`'s new items, two new siblings on one key included; a
singular relationship's constructed record and a dotted field constructing one;
and a replicate modal left on its original's key. The two relation-scoped creates
join the record to its owner **before** the mapping, so a key half the
relationship fills is in place when the check runs
([the order](../actions/create-action.md#the-created-record-joins-the-relationship-in-the-same-flush)).
A generated identifier adds no statement; an assigned one adds one primary-key
`SELECT`, and none when the holder is already managed. How the lookup walks and
where the message lands:
[association-reader.md](association-reader.md#a-taken-identifier-is-looked-up-once-per-new-record-after-the-records-own-constraints-pass);
what it cannot see:
[the recorded limits](association-reader.md#what-the-taken-key-probe-cannot-see).

Permanent — reverses only if a key-uniqueness constraint that needs no
declaration becomes Symfony's own.

### A blank assigned key is refused before anything is written

Since M7 · 7 a **new** record whose assigned key still has a blank component is
refused before anything is written — the record a save maps, with the records
its identifier associations reach, the items a relationship `Repeater` adds and,
since [M7 · 11](../roadmap.md#m7--composite-identifiers), the record a singular
relationship constructs. A blank component is an empty or
whitespace-only string, or an identifier association left unset or pointing at
a record whose own assigned key is blank. Who refuses it depends on whether the
form maps the blank field.

**A blank key field the form maps is a field error.** The record check refuses it
inside `getData()` with *This value should not be blank.*, after the record's own
constraints pass and before the taken-key probe runs. It files under the first
blank key field a component maps: the key fields holding a blank string first,
then the identifier associations. An identifier association whose record's own
key field is blank lists that field before itself — `spine.code`, then `spine` —
so a form mapping the related record's key field gets the error under it, and one
mapping only the association gets it under the association. A layout component
entangling the association (`Group::relationship()`) counts as mapping it, but
renders no error of its own, so since [M7 · 11](../roadmap.md#m7--composite-identifiers)
the refusal lands at the schema's own state path instead, which the page and
the action modal render above the form
([why](violation-path-translator.md#a-message-is-filed-only-under-a-component-that-shows-its-own-errors));
until then it was a 422 with no message on screen. A key field declared
`dehydrated(false)` counts as mapped, since a component still maps it, so its
typed value never reaching the record is refused under it. A blank field the
form leaves out is skipped on the way to one it maps, and when no component maps
any of them `getData()` refuses nothing and hands the record on. With the key rules
[switched off](#the-key-rules-switch-off-per-schema-never-the-changed-key-one)
it refuses nothing under a mapped field either, and the create seam below
answers for every blank key field, naming the switch in its advice.

**A key field the form leaves out is the create seam's.** The user cannot fix
it, so it is a developer error, refused by name with an exception that says how to
fix the form:

- [`CreateAction`](../actions/create-action.md#a-key-the-modal-leaves-blank-is-refused-by-name-after-persist)
  and the resource create page's default
  [`handleRecordCreation()`](../panels/README.md#createrecord) refuse it **after
  `persist()`**, so every `prePersist` callback, entity listener and global
  event-manager listener has run, and **before `flush()`**. The record is taken
  back out of the unit of work.
- `Select`'s `createOption` default refuses it **before `persist()`** and again
  **after** it, before the flush, with the global-listener residual
  [recorded on the reader](association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask).
- The [`Repeater`](../forms/repeater.md#a-new-item-whose-identifier-is-incomplete-is-refused-before-the-save-returns)
  refuses an item before any flush and never persists it itself, so a blank half
  a class-level `prePersist` callback leaves unfilled reaches the table there —
  recorded, not closed.
- [`ReplicateAction`](../actions/replicate-action.md) refuses it after
  `beforeReplicaSaved()`.
- A singular relationship's (`Group::relationship()`) constructed record was
  read by no blank seam: a whitespace key half the group maps over a nullable
  property, or one no field inside it maps, was inserted and the row could not
  be keyed. Since [M7 · 11](../roadmap.md#m7--composite-identifiers) the save
  refuses it by name inside `getData()`, once the record is written onto its
  owner, under the
  [`Repeater`'s rule](entangles-state-with-singular-relationship.md#a-constructed-record-whose-assigned-key-is-incomplete-is-refused-by-name)
  and with its exemptions.

M7 · 7's first form of this rule refused inside the mapping for every blank key
field, filing an unmapped one under the schema's own state path. That pre-empted
every place a developer fills a key the form does not show:
`beforeReplicaSaved()`, an overridden `handleRecordCreation()` that sets a key
half before calling the parent, and a global `prePersist` listener, which had no
escape at all. It also made `Select`'s own by-name refusal unreachable, and turned
a single derived identity with its association unset into *This value should not
be blank.* where the create used to fail in Doctrine's `persist()` and the
replicate refused by name.

The seams read after `persist()` because that is the one moment nothing else can
still fill the key. They look for a blank string only, through
`AssociationReader::hasBlankAssignedIdentifier()`: a null component never reaches
them, since Doctrine's `persist()` already refuses a missing assigned id by name.

Before M7 · 7, such a record was inserted with its blank key. The redirect after
the save could not mint its key, and every page listing the row failed with it.

The rule skips a generated identifier and a single scalar one, which stays the
application's `required()` to guard.

**A `prePersist` callback does not excuse a blank key field the form maps.** A
key field the form maps is the user's to fill, whatever a callback or entity
listener of the class might do at the flush, so a blank one is the field error
above. A key a callback fills belongs out of the form, where the create seam reads
it after `persist()`. Until M7 · 7's review the in-mapping half skipped every
class declaring a `prePersist` callback or entity listener, as
`AssociationReader::hasIncompleteAssignedIdentifier()` does — and that is the
commonest entity shape there is, a `#[PrePersist]` that stamps a timestamp. A key
field the user left blank on such a class then reached the create seam, which
refused it as a developer error whose advice, *give every identifier field a
field*, was false: the field was in the form.

### A changed key is refused and put back, not handed to Doctrine

Eloquent updates a primary key like any other column, and Filament passes an
edited one through. Doctrine keys its identity map by the old key, and what it
does with an edited one depends on the **id generator**, not on the key's width:
under IDENTITY it ignores the edit and the page reports *Saved*; under every
other generator it emits an `UPDATE` of the primary key, so the page's record
prop goes stale and the next request 404s — or the new key is taken and the
unique violation closes the entity manager. Since
[M7 · 6](../roadmap.md#m7--composite-identifiers) the record check refuses it for
every generator, IDENTITY included: *"This value cannot be changed."* under the
field, the stored key put back, nothing written.

The same `getData()` carries it everywhere a managed record is written:
`EditRecord`, `EditAction` on every host, `Select`'s edit-option default and its
to-one relationship arm, an existing repeater item, the existing record a
singular relationship holds, a dotted field onto a loaded related record's key,
and a hook that moved a key on a related record the form never maps — refused
at the schema's own path, where the page's record-error list and an action
modal's [outlet](../actions/action.md) render it. Tables' editable cells run the
same pass themselves and answer it as the cell's own error
([has-columns.md](../tables/has-columns.md)). The same key in another PHP type is
no change; a converting type's other spelling of it is one
([the detector](association-reader.md#a-changed-identifier-is-put-back-and-refused-and-costs-no-statement)).

All three messages — the taken, the changed and the blank one — are private
constants on the reader, with no error code, since nothing in the toolkit reads
one. No switch reaches this pass
([why](#the-key-rules-switch-off-per-schema-never-the-changed-key-one)). *"This value cannot be changed."* is new, and
stays English unless the application's `translations/validators.*` maps it —
Symfony ships no such message. `disabledOn('edit')` stays the UI and this is the server's guarantee,
and it holds where the UI guard does not: a modal's operation is the action's
**name**, so an `EditAction::make('editRenamed')` leaves a `disabledOn('edit')`
key field enabled, and the refusal is what answers there. A key written into a
related record **nobody loaded** is not seen — Doctrine drops that write itself —
and is recorded in the roadmap's ledger.

Permanent.

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
validates one schema in three steps. It collects
`$schema->getValidationConstraints()`; reads **every** one of those paths' raw
client state through `$schema->getPropertyAccessor()->get($host, $path)` (the
same read `getRawState()` does) into one flat map, which it hands to
[`Schema::mutateStateForValidation()`](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)
so each component may rewrite its own value; then, per path, filters that path's
constraints through [`PresenceGate::apply()`](presence-gate.md) so a blank string
reaches only the implicit ones and runs `ValidatorInterface::validate($value,
$constraints)`, stringifying each violation message. Reading the whole map before
validating any of it is what lets a cross-field constraint resolve its sibling at
build time and bake the value in — the mechanism every comparison and
conditional-required arm in [forms](../forms/can-be-validated.md) uses. Results key by
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
no job. Permanent.

The schema-side half of the same pipeline parts company with them as of M3 · 8:
`mutateStateForValidation` **is** ported, as a flat-map walk `validateSchema()`
applies between reading every path and validating any of them
([schema.md](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)),
while `pruneStateToMatchKeys()` stays unported because a per-path runner mutates
a copy it discards.

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

One limit the rule carries, recorded rather than solved: a component with
**several** child schemas — a Repeater's per-item schemas, each keyed by its item
key under one parent — shares a single identity (`schema.items`), and the sticky
resolver maps that identity back to the component's *default* child schema, whose
fields read the template paths rather than an item's. Today only
`Action::getSchemaState()` inside a repeater item (`Schema::getState()` →
`validate()`) can reach it, and the next root pass clears the `schema.`-prefixed
identities; the fix, when a consumer needs it, is to prefer the child schema's own
key whenever it differs from the default child schema's.

Reverses only if child-schema validation stops being a thing — the Wizard is its
first consumer, and relation managers became the second at M6 · 1.

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

