---
description: The field-side validation trait on `Field`, its rule-string sugar as Symfony constraints, and the first-party constraints that sugar needs.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeValidated.php
  - vendor/filament/forms/src/Components/Contracts/HasValidationRules.php
  - vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php
  - vendor/filament/forms/src/Components/Contracts/HasNestedRecursiveValidationRules.php
owns:
  - packages/forms/src/Components/Concerns/CanBeValidated.php
  - packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php
  - packages/forms/src/Components/Contracts/HasNestedRecursiveValidationConstraints.php
  - packages/forms/src/Validation/ActiveUrl.php
  - packages/forms/src/Validation/ActiveUrlValidator.php
  - packages/forms/src/Validation/Alpha.php
  - packages/forms/src/Validation/AlphaDash.php
  - packages/forms/src/Validation/AlphaNum.php
  - packages/forms/src/Validation/Ascii.php
  - packages/forms/src/Validation/Distinct.php
  - packages/forms/src/Validation/DistinctValidator.php
  - packages/forms/src/Validation/EndsWith.php
  - packages/forms/src/Validation/EndsWithValidator.php
  - packages/forms/src/Validation/MatchesField.php
  - packages/forms/src/Validation/MatchesFieldValidator.php
  - packages/forms/src/Validation/MinFileSize.php
  - packages/forms/src/Validation/MinFileSizeValidator.php
  - packages/forms/src/Validation/Prohibited.php
  - packages/forms/src/Validation/ProhibitedValidator.php
  - packages/forms/src/Validation/RequiredValue.php
  - packages/forms/src/Validation/RequiredValueValidator.php
  - packages/forms/src/Validation/StartsWith.php
  - packages/forms/src/Validation/StartsWithValidator.php
---
# CanBeValidated (forms)

[`Sylament\Forms\Components\Concerns\CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) is the field-side half of the validation vertical, composed on [`Field`](field.md).
It declares what a field requires of its state and turns that into `Symfony\Component\Validator\Constraint` objects: the schema collects them keyed by absolute state path and the host runs `ValidatorInterface` over them, all tracked in [`schemas/can-be-validated.md`](../schemas/can-be-validated.md).
`Field` implements the schemas-side component contract [`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) through this trait; the contract is [relocated into schemas](../schemas/can-be-validated.md#the-component-contract-is-relocated-from-forms-into-schemas).
The date comparison family is in [date-comparison.md](date-comparison.md) and the data-layer pair `unique()` / `exists()` in [probes-records.md](probes-records.md); the `constraint()` / `constraints()` escape hatch takes any Symfony `Constraint`, so a rule with no sugar is reachable anyway.
Every cross-field arm reads another path's live raw state at constraint-build time through the root container's property accessor and emits a value-only constraint ([the reader](#every-cross-field-arm-resolves-its-sibling-at-build-time)).
The first-party constraints live in the package's own `Validation/` directory, each with its validator, mirroring [schemas' `Validation/`](../schemas/presence-gate.md) one package down.

The rule → constraint vocabulary is a rename, recorded in the [rename table](../renames.md): `rule()` / `rules()` → `constraint()` / `constraints()`, `getValidationRules()` → `getValidationConstraints()`, `dehydrateValidationRules()` → `dehydrateValidationConstraints()`.
The payload changes ecosystem (Laravel rule strings → Symfony `Constraint` objects), so the rename makes the migration break loud and searchable instead of type-erroring on a grep hit.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`required()` / `isRequired()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::required()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) |
| [`required(null)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — undeclare, falling back to derivation | — | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) |
| [`isRequiredByRecord()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — the derivation seam, overridden to `false` on [`Checkbox`](checkbox.md) and [`Toggle`](toggle.md) | — | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) · [semantics](#a-boolean-field-opts-out-of-derivation-but-selectboolean-does-not) |
| [`getRequiredConstraint()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`getRequiredValidationRule()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-required-constraint-is-swappable-per-field) |
| [`nullable()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::nullable()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#nullable-removes-the-required-constraint-and-nothing-else) · [semantics](#the-blank-string-presence-check-lives-in-schemas-not-on-the-field) |
| [`constraint()` / `constraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`rule()` / `rules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#rule-and-rules-become-constraint-and-constraints-over-symfony-constraint-objects) · [semantics](#a-closure-the-field-registers-takes-the-field-as-a-parameter-never-this) |
| [`getValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`getValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-condition-pair-walk-drops-filaments-numeric-key-rule-shorthand) · [diverges](#the-subclass-seam-is-a-protected-builder-not-the-public-getter) |
| [`dehydrateValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`dehydrateValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | — |
| [`validationMessages()` / `getValidationMessages()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#validation-messages-apply-after-the-constraints-are-built) |
| Length constraints ([`CanBeLengthConstrained`](can-be-length-constrained.md)) | [`getLengthValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| `email()` / `numeric()` arms ([`TextInput`](text-input.md)) | [`TextInput::email()` / `numeric()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [detail](text-input.md) |
| `dehydrateValidationMessages()` / `dehydrateValidationAttributes()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#validation-messages-apply-after-the-constraints-are-built) |
| `validationAttribute()` / `getValidationAttribute()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#validationattribute-is-not-ported) |
| `showAllValidationMessages()` / `shouldShowAllValidationMessages()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| `allowHtmlValidationMessages()` / `areHtmlValidationMessagesAllowed()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#html-validation-messages-are-not-ported) |
| `fieldComparisonRule()` / `multiFieldComparisonRule()` / `multiFieldValueComparisonRule()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#the-remaining-comparison-rule-string-builders-have-no-port) |
| [`MinFileSize`](../../../packages/forms/src/Validation/MinFileSize.php) + its validator — no Filament source | [`BaseFileUpload::minSize()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) — `rule("min:{n}")` | done | — | [semantics](#a-file-field-validates-in-two-places-and-only-one-of-them-is-this-trait) |
| [Format sugar (`alpha`, `alphaDash`, `alphaNum`, `ascii`, `string`, `hexColor`, `json`, `macAddress`, `ip` / `ipv4` / `ipv6`, `uuid`, `ulid`, `activeUrl`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does) · [semantics](#uuid-is-loose-so-the-nil-uuid-passes) |
| [`regex()` / `notRegex()` / `getRegexPattern()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](text-input.md#tel-writes-the-shared-regex-slot) |
| [`in()` / `notIn()` / `getInValidationConstraint()` / `getInValidationConstraintValues()` / `hasInValidationOnMultipleValues()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| [`startsWith()` / `endsWith()` / `doesntStartWith()` / `doesntEndWith()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does) |
| [`filled()` / `multipleOf()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#filled-is-required-without-the-asterisk) · [diverges](#multipleofs-zero-and-precision-arms-differ-from-laravels) |
| [Value comparison family (`gt` / `gte` / `lt` / `lte`, `same` / `different`, `confirmed`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#every-cross-field-arm-resolves-its-sibling-at-build-time) · [diverges](#the-ordering-arms-compare-with-php-not-with-laravels-size-table) · [diverges](#same-different-and-confirmed-need-a-first-party-constraint) |
| [Conditional-required family (`requiredIf*` / `requiredUnless` / `requiredWith*` / `requiredWithout*`, `prohibited` / `prohibitedIf` / `prohibitedUnless` / `prohibits`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#a-conditional-requirement-shows-no-asterisk) · [semantics](#the-sibling-decides-the-match-strictness) · [diverges](#prohibited-is-first-party-because-blank-violates-on-an-empty-array) |
| [`distinct()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule) |
| [`mutateStateForValidation()` / `mutatesStateForValidation()`](../../../packages/schemas/src/Components/Concerns/HasState.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [detail](../schemas/component.md#the-mutatestateforvalidation-members-mutate-the-runners-copy-of-one-path) · [semantics](#field-does-not-force-mutatesstateforvalidation-true) |
| [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) + its contract | [`HasNestedRecursiveValidationRules`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [diverges](#nested-constraints-land-as-one-all-where-laravel-keys-a-path) |
| [`Prohibited`](../../../packages/forms/src/Validation/Prohibited.php), [`Distinct`](../../../packages/forms/src/Validation/Distinct.php), [`MatchesField`](../../../packages/forms/src/Validation/MatchesField.php), [`StartsWith`](../../../packages/forms/src/Validation/StartsWith.php), [`EndsWith`](../../../packages/forms/src/Validation/EndsWith.php), [`Alpha`](../../../packages/forms/src/Validation/Alpha.php), [`AlphaDash`](../../../packages/forms/src/Validation/AlphaDash.php), [`AlphaNum`](../../../packages/forms/src/Validation/AlphaNum.php), [`Ascii`](../../../packages/forms/src/Validation/Ascii.php), [`ActiveUrl`](../../../packages/forms/src/Validation/ActiveUrl.php) + their validators — no Filament source | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does) |
| [`RequiredValue`](../../../packages/forms/src/Validation/RequiredValue.php) + its validator — no Filament source | [`CanBeValidated::required()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#required-carries-laravels-blank-set-not-symfonys) |

## Semantics

### Every cross-field arm resolves its sibling at build time

`gt()`, `same()`, `requiredIf()`, `prohibits()`, `distinct()` and the rest all go through one protected reader, `resolveSiblingRawState($statePath, $isStatePathAbsolute = false)`, which asks the root container's property accessor for another path's **live raw state** — the identical read [`validateSchema()`](../schemas/can-be-validated.md#validateschema-runs-symfonys-validator-per-state-path-instead-of-livewires-whole-component-validate) performs.
The runner collects the whole constraint map before it reads any value, so a constraint built this way can bake its partner's value in and stay a value-only constraint.

Symfony's own cross-field mechanisms cannot stand in for this, because the runner validates each path's value alone, with no object around it.
A comparison constraint's `propertyPath` reads its partner off `ExecutionContext::getObject()`, and `AbstractComparisonValidator` returns without a violation when that is `null`, so it is a silent pass rather than an error.
`When`'s closure is called with the same `null` object, so it cannot read a sibling either.

Three consequences, all inherited from the date family and all deliberate.

**A field with no live host emits no cross-field constraint.**
A unit-built field, or a schema rendered without a host, has nothing to read, so the arm returns nothing rather than guessing.
Only a direct `resolveSiblingRawState()` call throws, and its message names the field by `getName()` — the absolute path would walk an uninitialised container and fatal first.

**The sibling is re-read on every build.**
The sticky `#[PostHydrate(-20)]` pass rebuilds the constraint list per request, so an arm tracks a partner the user is still typing into.

**Sibling reads see raw state, never mutated state.**
The build happens before the runner's [mutation pass](../schemas/schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer), and the reader goes to the host, so `same('password')` with `trim()` on both fields compares a trimmed own value against an untrimmed partner.
Filament's siblings see the mutated attributes array.
Reversible by handing the reader the runner's mutated map, if a consumer ever hits it.

### A closure the field registers takes the field as a parameter, never `$this`

Every closure `CanBeValidated` hands to `constraint()`, `required()` or a condition is `static fn (Field $component) => …`, Filament's own shape, and reads the field through that parameter.
A captured `$this` would be wrong inside a repeater: each item's components are clones built by `getClone()`, the closure travels with the clone unchanged, and the Invoker never rebinds `$this`, so the closure would read the template component's state path and siblings instead of the item's.
`distinct()` resolves nothing without this.
A new closure registered from inside a field follows the same shape.

### The sibling decides the match strictness

`requiredIf`, `requiredUnless`, `prohibitedIf` and `prohibitedUnless` port Laravel's rule exactly — `in_array($sibling, $values, is_bool($sibling) || is_null($sibling))` — and it is the **sibling's** own type that picks strict or loose, not the declared value.
So `requiredIf('count', 1)` still matches the `'1'` a text input submits, `requiredIf('flag', true)` is *not* matched by a `'1'` sibling, and `requiredIf('flag', true)` **is** matched by a raw `true` one.
Reading the strictness off the declared value instead is the natural misreading, and it inverts two of those three.

One delta is forced by the live bag: a null sibling is "present and null" here, because `PropertyAccessor::get()` answers null for an absent key too.
Laravel's `Arr::has` guard on `required_if` therefore collapses into the strict-null match.
`requiredIf('other', null)` matches a null sibling, and — unlike everywhere else in this trait — the values list is **not** built with [`wrap()`](../support/helpers.md): its `null` → `[]` arm would read that null as absence and erase the very thing the caller asked to match.

### A conditional requirement shows no asterisk

No member of the conditional-required family writes `$isRequired`, upstream or here, so `requiredIf()` renders no asterisk and no native `required` attribute.
A conditionally-required field that should be starred says [`markAsRequired()`](../schemas/can-be-marked-as-required.md).
What the family emits is the field's **own** `getRequiredConstraint()` — `RequiredValue`, [`Checkbox`](checkbox.md)'s `NotBlank` or [`Toggle`](toggle.md)'s `NotNull` — so a boolean field keeps the presence semantics its own markup states.
All three are implicit, so [the presence gate](../schemas/presence-gate.md) keeps them on the blank input the rule exists to catch; `Prohibited` is not implicit, and the gate dropping it on a blank string is exactly right.

A field that is **already** required emits nothing from this family: the base builder has put that constraint in the list once, and a second copy reports the same message twice.

Derived requirement is the caller's to reconcile.
A field bound to a non-nullable record property already derives `RequiredValue` ([derivation](#requirement-is-derived-from-the-records-write-side-types)), which makes a `requiredUnless()` on it dead.
The family does not suppress derivation the way `nullable()` does — it never writes the flag Filament never wrote either — so a conditionally-required field against a non-nullable column says `nullable()` first, exactly as an explicitly optional one does.

### filled() is required() without the asterisk

Laravel's `filled` is `required` behind an `Arr::has` guard, and a live bag always has the key, so the two collapse.
The arm emits the field's own `getRequiredConstraint()` through a pair, which keeps `Toggle`'s `NotNull` (a `false` toggle is filled).
There is no second mechanism to look for.

The trait imports the house function `filled()` **and** declares a `filled()` method; they are different symbol kinds, so `filled($x)` is the function and `$this->filled()` the method.
Neither shadows the other, and the import is not a mistake to "fix".

### A user in() list is emitted in both spellings, and replaces the derived check

`Choice` is strict here — `strict: false` throws at validation time — while the raw bag speaks strings and a filled record may speak ints.
So a user list is emitted as **each value and its string form**, de-duplicated by *strict* identity: `in([1, 2])` becomes `['1' and 1, '2' and 2]`.
`array_unique()` is never used for this, at any sort flag, because it collapses `1` and `'1'` and would silently erase the doubling.
Enums unwrap to `->value` first.
The precedent is [`getEnabledOptionValues()`](can-disable-options.md), which produces every accepted value in both spellings for the same reason.

`hasInValidationOnMultipleValues()` — `false` on the trait, `true` on [`CheckboxList`](checkbox-list.md), `isMultiple()` on [`Select`](select.md) and [`ToggleButtons`](toggle-buttons.md) — means "per element", expressed as `Choice(multiple: true)` on the field's own path rather than Laravel's `.*` key.

On the four option fields, a user list **replaces** the derived membership check rather than joining it, which is what Filament's overrides do by returning the parent's list first.
The guard sits after each field's blank early return and **before** its no-options `LogicException`, so `in()` on an option-less field is valid — placing it after the throw would make that call fail.

Filament's `getInValidationRule()` also falls back to `Rule::enum($enum)` when no user list is set.
That is not re-emitted: the option builders already derive a `Choice` from the enum's cases.

### uuid() is loose, so the nil UUID passes

`Uuid(strict: false)`, because strict rejects the nil UUID that Laravel's regex accepts and real data contains.
Loose also drops the version and variant checks — neither of which Laravel makes either.
The cost is the other direction: braced and hyphen-less forms pass here and (probably) not there.

### Field does not force mutatesStateForValidation() true

Filament's forms trait overrides `mutatesStateForValidation()` to a constant `true`, because its only first-party job upstream is unwrapping a `BackedEnum` for Laravel's `in` rule.
The raw bag here never holds an enum — [`EnumStateCast::set()`](../schemas/enum-state-cast.md) stringifies on hydration — so the override has nothing to do and is not ported.
A field mutates for validation when, and only when, something asked it to.

### A boolean field opts out of derivation, but `Select::boolean()` does not

`Checkbox` and `Toggle` override `isRequiredByRecord()` to a constant `false`, and `Select::boolean()` — which also binds to a `bool` column — deliberately does not.
The asymmetry looks like an oversight and is the whole point: the question the override answers is a **field** question, not a record one.

The predicate that matters is *does this field's blank state dehydrate to something the target rejects*.
Read off the state casts:

| field | cast | blank state dehydrates to |
| --- | --- | --- |
| `Checkbox` / `Toggle` | `BooleanStateCast(isNullable: false)` | `false` — never null |
| `Select::boolean()` | `BooleanStateCast(isStoredAsInt: true)`, `isNullable` defaulting **true** | the placeholder dehydrates to **null** |

So a non-nullable `bool` target always accepts what a checkbox or a toggle produces, and deriving requirement there would silently mean *"must be ticked"* on the checkbox: [its own required constraint](checkbox.md#required-on-a-checkbox-means-must-be-checked) violates on `false`, and its template emits a native `required` attribute, so an unticked box becomes unsubmittable.
Only an explicit [`required()`](toggle.md#required-means-presence-not-truth) may say that.
A `Select::boolean()` placeholder, by contrast, really does dehydrate null onto `setFeatured(bool)` — the exact crash this derivation exists to prevent — so suppressing it there would remove the guard rather than a false positive.

This is why the suppression lives on the two field classes rather than on the target type.
A "skip every `bool` target" rule inside `isRequiredByRecord()` is the tempting alternative and is wrong: it cannot see which of three fields bound to the same column is asking.

The same predicate has a branch-side counterpart, placed the same way.
[The built-in blank test asks each declared leaf](../schemas/has-nullable-branch.md#the-built-in-blank-test-asks-the-branchs-own-leaves) whether its value is blank, and `Checkbox`/`Toggle` override `isBlankStateValue()` so their resting `false` answers yes.
Without it `filled(false)` would hold every branch containing a boolean field permanently non-blank, so the suppression above would never engage there and the branch could never null.
`Select::boolean()` needs no override on this side either, and for the same reason as the last: its placeholder already dehydrates null, which the default test reads as blank.

### nullable() removes the required constraint and nothing else

`nullable()` is Filament's inversion verbatim — it calls `required(fn () => ! evaluate($condition))`, so it is a *setter for the same flag*, not an independent one, and a later `required()` overwrites it.
There is deliberately no Symfony counterpart constraint to add: every constraint in the ported subset returns without a violation for `null`, so "optional" is the absence of the required constraint — matching Laravel's implicit-rule skip, which is what made Filament's `nullable()` a pure inverse of `required()` in the first place.

That parity holds for the empty string a cleared input submits as well, but not by the same mechanism: `Length(min:)`, `Type('numeric')` and `GreaterThanOrEqual` each violate on `''` where they return silently on `null`, so the promise is kept one layer up by the [presence gate](../schemas/presence-gate.md), which filters them out before the runner sees them exactly as Laravel's own `presentOrRuleIsImplicit()` does.
`nullable()` is faithful either way; what the gate supplies is the presence check in front of it.

### The blank-string presence check lives in schemas, not on the field

Laravel checks presence **once, centrally, in front of every rule**, so a blank string bypasses `min`, `max`, `email`, `numeric`, `regex` and `url` alike.
This port does the same, and the reason it is recorded here is that the obvious place to look for it is the wrong one: it is not an arm of `buildValidationConstraints()`, and no field's builder guards on blankness for this reason.
The check is [`PresenceGate`](../schemas/presence-gate.md), a constraint filter schemas applies in front of `validateSchema()`'s per-path run — which is why an escape-hatch `->constraint(new Regex('/…/'))` gates without the caller doing anything, and why adding a new sugared arm here needs no blank guard of its own.

Two consequences land on this trait's surface.
`required()` is unaffected — `RequiredValue` implements the gate's `ImplicitConstraint` marker, so it still runs and still violates on `''` — and so is [`nullable()`](#nullable-removes-the-required-constraint-and-nothing-else), whose promise the gate is what completes.
What each Symfony validator would have done with `''`, and the `mb_trim` / `null` / `blank()` decisions the gate makes, are recorded [there](../schemas/presence-gate.md#what-each-symfony-validator-does-with-the-empty-string-and-why-the-gate-is-not-defensive) rather than repeated here.

## Divergences

### The ordering arms compare with PHP, not with Laravel's size table

`gt` / `gte` / `lt` / `lte` emit the stock `GreaterThan`, `GreaterThanOrEqual`, `LessThan` and `LessThanOrEqual` with the sibling's raw value baked in.
Symfony's validators compare with PHP's `>`/`>=`/`<`/`<=`, and PHP 8 compares two numeric strings numerically (`'10' > '9'` is true), which is the only case Filament documents.

Laravel does not.
Its `getSize()` compares **strings by length** and **arrays by count**, and fails outright on a type mismatch.
Here two non-numeric strings compare lexicographically and mixed types coerce.
A consumer wanting Laravel's length semantics writes it with `Length` or a `Callback`.
Messages are Symfony's stock ones, which interpolate `{{ compared_value }}` where Laravel's interpolate `:value`.

A blank, null or unresolvable sibling drops the constraint rather than failing the field — the same ruling the [date family](date-comparison.md#a-blank-sibling-skips-the-comparison-where-laravels-verdict-depends-on-the-operator) made, and Laravel's own answer for a missing sibling is direction-dependent (passes `after`, fails `before`, fails `gt`), so there is nothing coherent to be faithful to.
Filament's `gt('5')` — a numeric literal rather than a field name — always fails upstream and resolves to a missing sibling here, i.e. no constraint; literal bounds are [`minValue()` / `maxValue()`](text-input.md#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max).

**One class-key cost is real.**
`gte()`/`lte()` emit the very classes `minValue()`/`maxValue()` emit, and the message post-pass keys on exact class, so a `validationMessages([GreaterThanOrEqual::class => …])` on a field carrying both arms rewrites both.
That is the same cost `->tel()->integer()` already pays on `Regex::class`.

### same(), different() and confirmed() need a first-party constraint

Symfony's `IdenticalTo` and `NotIdenticalTo` cannot serve, and the reason is a throw rather than a preference: `AbstractComparison::__construct()` raises `ConstraintDefinitionException` when both `value` and `propertyPath` are null — and a **null partner is the normal state of a confirmation field** until someone types into it.
`same('passwordConfirmation')` with a filled own value and an empty partner must *fail*, not crash the builder.

So the three arms emit [`MatchesField`](../../../packages/forms/src/Validation/MatchesField.php) `(value:, other:, match:)`, holding the resolved sibling value, the sibling's plain-text label and the polarity.
It compares with `===`/`!==` (Laravel's `same`/`different`), returns on a blank **own** value, and compares a blank or null **partner** rather than skipping it — the one place the cross-field arms part company with the ordering ones.
`other` is the partner component's label, read through `getComponentByStatePath()` and `strip_tags()`-ed because a label may be `Markup`, falling back to the path's last segment humanised.

Two things this buys that the stock classes could not: its own `validationMessages()` class key, so `same()` does not share `IdenticalTo::class` with a user's escape-hatch constraint; and messages that **name** the partner instead of printing it — `'This value must match {{ other }}.'` rather than a confirmed password landing in the DOM.

**The `===` hazard is stated, not solved.**
Raw state is a string on the wire but keeps the record's PHP type after `fill()` until the browser edits it, so `same()` between a wire string and a filled `int` fails until both are typed into.
Filament's `same` has the identical property against Livewire's bag.

`confirmed()` keeps Filament's argument-less signature and Laravel's partner rule: the sibling at `<own absolute path>_confirmation`, which is a **real component** in the same container, not an extra request key.
The suffix stays snake_case because that is what the rule means and what a migrating `confirmed()` call expects — Filament's own auth pages never call it, using `same('passwordConfirmation')` instead.
Laravel 13's `confirmed:other` custom partner is not exposed; Filament's signature has no slot for it and `same()` covers the case.

### Prohibited is first-party because Blank violates on an empty array

Stock `Blank` does not fit: it violates on `[]`, so a [`CheckboxList`](checkbox-list.md) or multiple [`Select`](select.md) with nothing ticked would read as "not blank" and a `prohibited()` on it would fail for being empty.
Laravel's `prohibited` is `! validateRequired()`, which the house [`filled()`](../support/helpers.md) mirrors — `false` and `0` count as present in both.
So the four arms emit [`Prohibited`](../../../packages/forms/src/Validation/Prohibited.php), whose validator violates when `filled($value)`.

`prohibits($paths)` puts the violation on **this** field, Laravel-exact ("if I am present, they must be empty"): at build time, any named sibling that is `filled()` contributes its label to a pre-rendered message, and the constraint stays a plain presence test.

### multipleOf()'s zero and precision arms differ from Laravel's

`DivisibleBy(value: …)` under Filament's own pair condition, which is `filled()` on the evaluated value — there is no `$condition` tail upstream and none is added.
Two deltas are recorded rather than patched.
**Zero:** Laravel's `0 % 0` fails, `0 % n` passes and `n % 0` fails, where `DivisibleBy` violates for any zero divisor.
**Precision:** Laravel uses `BigDecimal` where Symfony uses a 12-significant-digit `fmod` tolerance, which rescues `0.3 % 0.1`.
A non-numeric value lands on the runner's hardcoded "This value should be of type numeric." violation — the same text a non-numeric `numeric()` field already gets.

### distinct() is three arms of one first-party constraint, not Laravel's rule

Filament's `distinct()` is **not** Laravel's `distinct` rule, so `Assert\Unique(fields:)` over the repeater's path is not its counterpart.
It is a per-item closure over the parent repeater's sibling raw state that dispatches on the *own value's PHP type*, and the port reproduces exactly that: [`Distinct`](../../../packages/forms/src/Validation/Distinct.php) `(mode:, siblingValues:, isSiblingSelected:, mustBeSelected:)` with Filament's three message keys as three message options.

- **scalar** — violate when a sibling holds the same value.
  Comparison is loose, Filament's `Collection::contains`, because a filled item may hold a record `int` beside a wire string.
- **array** — violate on any intersection with a sibling's array.
- **boolean** — violate when this item is ticked and a sibling is too (`onlyOneMustBeSelectedMessage`), and when nothing is ticked anywhere while the field `isRequired()` (`mustBeSelectedMessage`).

The facts are resolved at build time and handed over as plain values, so the constraint itself never walks the schema.
The sibling paths come from [`RepeaterSiblingContext`](field.md#the-five-sibling-facts-are-one-value-object-not-string-surgery) behind `ResolvesRepeaterSiblingContext`, which `Field` composes and which `fixIndistinctState()` and `disableOptionsWhenSelectedInSiblingRepeaterItems()` read too.
Item-level error placement comes free, because the constraint sits on the item field's own path.

### The format sugar is stock where a constraint fits, and first-party where none does

Stock, each constructed with named arguments — the first positional of `Choice`, `Ip`, `Uuid`, `Ulid`, `Json` and `Url` is a poison-pill `$options` array that throws, and `MacAddress`'s first positional is the message, not the type:

| arm | constraint | why not the obvious alternative |
| --- | --- | --- |
| `string()` | `Type(type: 'string')` | exact |
| `json()` | `Json()` | exact |
| `macAddress()` | `MacAddress(type: MacAddress::ALL)` | both sides are `FILTER_VALIDATE_MAC` |
| `ip()` / `ipv4()` / `ipv6()` | `Ip(version: ALL / V4 / V6)` | **`Ip()`'s default is V4, not ALL** — `ip()` must say so |
| `uuid()` | `Uuid(strict: false)` | [loose on purpose](#uuid-is-loose-so-the-nil-uuid-passes) |
| `ulid()` | `Ulid()` | exact |
| `hexColor()` | `CssColor(formats: [HEX_LONG, HEX_LONG_WITH_ALPHA, HEX_SHORT, HEX_SHORT_WITH_ALPHA])` | the default twelve formats also accept `red`, `rgb(…)` and `Canvas`; Laravel's `hex_color` is exactly the four hex shapes |

First-party where nothing fits.
`alpha()`, `alphaDash()`, `alphaNum()` and `ascii()` are four `Regex` **subclasses** carrying Laravel's own patterns and validated by Symfony's `RegexValidator` — [principles §2](../../internals/principles.md)'s *wrap* rung, their validator and our class.
`Type('alpha')` is byte-only `ctype_alpha` where Laravel's is `\pL\pM`, and `Charset('ASCII')` passes control characters, must be spelled upper-case or it self-refutes, and is not an early-return constraint.
A subclass buys a readable default message plus **its own class key**, so `[Alpha::class => …]` does not also rewrite `tel()`'s and `regex()`'s messages the way a `Regex::class` key would.

Two pattern details are load-bearing.
Laravel anchors with `\A…\z`, not `^…$`, so a trailing newline is refused; copying the `^…$` spelling would let one through.
And `ascii` is the **positive complement** of the class `Str::isAscii()` → `ASCII::is_ascii()` negates: NUL is refused while the control characters `\x10` and `\x13` are accepted.
That is an upstream slip, and it ships as-is, because the rule is "what Laravel accepts".
Filament's `alpha:ascii` parameter is a Laravel rule parameter, not a Filament argument, and is not exposed.

`activeUrl()` is the one arm that is more than unsugared syntax: [`ActiveUrl`](../../../packages/forms/src/Validation/ActiveUrl.php)'s validator does the DNS lookup Laravel's `active_url` does, one `dns_get_record($host . '.', DNS_A | DNS_AAAA)` inside Laravel's own try/catch shape.
An unresolvable host answers `[]` with no warning and no throw, so the catch only guards a resolver error.
The lookup sits behind a `protected hasDnsRecord()` seam and `ActiveUrlValidator` is deliberately **not** `final`: a test subclass overrides the seam so the suite never touches the network.
The network cost is Laravel's own; a consumer wanting syntax only uses `url()`.

`startsWith()`/`endsWith()` and their negations emit [`StartsWith`](../../../packages/forms/src/Validation/StartsWith.php) and [`EndsWith`](../../../packages/forms/src/Validation/EndsWith.php) rather than a `Regex` of `preg_quote`d alternatives, which would share `Regex::class` with three other arms for messaging.
Semantics are Laravel's: a match on **any** value passes the positive form, the negative form needs **none** to match, and a value that is neither string nor numeric violates both.
A comma string splits without trimming — Filament passes it through unsplit so that Laravel splits it, and the artifact is kept so a caller's spelling means the same thing on both sides.

### Nested constraints land as one All where Laravel keys a path

`HasNestedRecursiveValidationRules` ports as [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) — trait and contract — under the same `rule` → `constraint` vocabulary [the rename table](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) sets: `nestedRecursiveConstraint()`, `nestedRecursiveConstraints()`, `getNestedRecursiveValidationConstraints()`.
Laravel's `"{$statePath}.*"` key has no per-path counterpart here, so the list is emitted as one `All` on the field's **own** path, which is where the wrapper puts a `.*` message upstream anyway; the index each violation carries is discarded by the runner.
The string pipe-syntax arm goes with the rest of Laravel's string rules, and so does the numeric-key shorthand.

The `All` is appended **only when the raw state is an array**.
Over anything else `AllValidator` throws into the runner's hardcoded "should be of type iterable" message, and the option fields' `Type('array')`-then-return arm already speaks for a non-array.

Consumers: [`Select`](select.md), [`CheckboxList`](checkbox-list.md), [`ToggleButtons`](toggle-buttons.md), [`TagsInput`](tags-input.md) and [`BaseFileUpload`](file-upload.md).
`ToggleButtons` gets the **contract** as well as the trait, which fixes an upstream defect: Filament's composes the trait without the contract, so its nested rules are silently dropped.
[`FusedGroup`](fused-group.md)'s `.*` error-collection arm is [skipped there](fused-group.md#the-error-collection-keeps-the-first-failing-field-and-nothing-else).
For a file field the `All` is a non-`File` constraint and so stays on the collection set at save.

Messages reach the nested members, which is a divergence in our favour: the post-pass recurses through `Composite::getNestedConstraints()` and writes the rebuilt list back, cloning at each level.
Filament has no working per-element message customisation.
Two lists the recursion does not reach are recorded rather than handled — `When::$otherwise` and `Collection::$fields`; no arm emits either.
The four composite classes it recurses into (`All`, `AtLeastOneOf`, `Sequentially` and `When`) are named explicitly rather than matched on `Composite`, which declares no property of its own: the name of the one holding the members is a `protected` hook each subclass answers.

`All`, `When`, `Callback` and `Sequentially` carry **no `message` option** of their own, so the string shorthand keyed on any of them still throws the documented `LogicException` — the same arm `Length` (which has `minMessage`/`maxMessage` but no `message`) already hits.

### Requirement is derived from the record's write-side types

Filament's `$isRequired` starts at `false`; here it starts at **`null`**, meaning *never declared*, and `isRequired()` falls through to `isRequiredByRecord()` when nothing was declared.
A field bound to a record path whose write-side type is non-nullable is required; one bound to a nullable target is not.
Explicit declarations win in both directions and are unchanged — `required()`, `required(false)` and `nullable()` all beat the record.

**No Filament precedent exists, and the reason is structural rather than a gap in their API**: Eloquent attributes are untyped, so "may this column accept null?" is not a question a Filament schema can ask.
Their equivalent is three manual declarations that must be kept in sync with the schema by hand — fields left un-`required()`, `requiredWith()` on the siblings, `condition:` on the container.

**Class and path are both read in the nearest record frame** — `getNearestRecordClass()` and `getNearestRecordStatePath()`, the first node above the field that binds a record of its own, else the root container.
The two have to name the same frame, and [the pair comes off one walk](../schemas/component-belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) **so the two agree**.
Reading only the class nearest-first would mix them: `Section::make('author')->statePath('author')->record(Author::class)` would ask `describe(Author::class, 'author.name')`, which no type answers, and the walk would derive **not required** rather than failing.
Framed on both halves, that section asks `describe(Author::class, 'name')`, so **a field inside a class-stamped frame over a non-nullable property derives required**.
A stamped subtree needs the same: a [relationship repeater](repeater.md) item or an [entangled subtree](../schemas/entangles-state-with-singular-relationship.md) binding its own record derives against that record instead of asking the root about a `record-<id>` segment no property answers.
Where the field's path does not nest under the frame's, the path half is null and derivation gives up rather than indexing the frame's record with a path that is not its own.
[`getRecordStatePath()`](../schemas/component-belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) stays root-framed for the map; only this derivation reads the nearest frame, and a component-tier `record()` binding feeds it as well as [closure injection and mapping-independent reads](../schemas/component-belongs-to-record.md#the-component-tier-gains-record).

The signature widens to carry it: `required(bool|Closure|null $condition = true)`, where Filament's is `bool|Closure`.
**`required(null)` is a new public arm** and it is not the same as `required(false)` — it *undeclares*, handing the decision back to the record, which is the only way to get derivation back after a conditional call has set the flag.
`required(false)` remains a declaration and still suppresses.

Two behaviours make this safe to have on by default, and both are load-bearing:

- **It suppresses itself under a blank nullable branch.**
  `hasBlankNullableBranchAncestor()` walks the container chain, and derivation gives up as soon as an ancestor declaring [`nullWhenBlank()`](../schemas/has-nullable-branch.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) is currently blank.
  Without it the feature deletes the feature that motivated it: `Article::$author` nullable with `Author::$name` non-nullable would make the name unconditionally required, so a blank author could never be submitted, so the branch could never be nulled.
  With it, the pair states exactly *"name is required if there is an author"* — Filament's `condition:` + `requiredWith()` shape, derived instead of hand-written.

  An ancestor is only a branch if it carries a **record state path**, and that gate is not cosmetic symmetry with the mapper's own walk.
  `getRawState()` on a path-less component falls through to its container's path, so without the gate a path-less `nullWhenBlank()` container would hand `shouldNullBranch()` the whole form's raw bag, and one field's requirement would move with unrelated fields elsewhere on the page.
  [The mapper refuses that declaration outright](../schemas/has-nullable-branch.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored); the two walks agree on what a branch is.
- **It answers `false` wherever it cannot be sure.**
  No container, no record on the frame, no frame-relative path — a field carrying none of its own, or one whose path escapes the frame — a path the record does not declare, or a collection-typed target: each returns false rather than guessing.
  The collection skip uses the wide `isCollection` flag, since a blank value says nothing about whether an `array` target accepts it.

**Accepted cost, stated because it is visible on every form:** asterisks and native `required` attributes appear wherever a bound target is non-nullable, without anyone writing `required()`.
That is the intended layering fix — [a blank non-nullable leaf becomes a field error instead of a mapper crash](../schemas/can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) — and a field that should stay optional against a non-nullable target has to say `nullable()` out loud.

One rough edge rides along: a `dehydrated(false)` leaf still validates, so it can derive a constraint for a write that never happens.
Harmless where the field is genuinely page-only and filled; combined with a nullable branch it is the sharp end of [the two-bag hazard](../schemas/has-nullable-branch.md#a-nullwhenblank-closure-sees-two-different-bags).

The reader itself arrives by `#[Required]` setter with a `new RecordTypeReader()` fallback, the `EvaluatesClosures`/`CanAccessProperties` idiom, so a field built with `new` outside DI still derives.
`getRecordTypeReader()` is public rather than private, matching both sibling precedents.

### rule() and rules() become constraint() and constraints() over Symfony Constraint objects

`rule(mixed $rule, bool | Closure $condition)` / `rules(string | array | Closure $rules, …)` become [`constraint(Constraint|Closure $constraint, bool|Closure $condition)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) / `constraints(array|Closure $constraints, …)`.
Two changes ride together.
The **payload** is a Symfony `Constraint` instance (or a closure returning one or an array of them), never a rule string — so Laravel's pipe syntax (`'required|max:255'`) and its string-keyed rule names have no form here, and `constraints()` drops Filament's `string` branch entirely.
The **name** follows the payload, per the [rename criterion](../renames.md): a preserved `rule()` would keep every migrating call site compiling while failing at runtime on the argument type, where the rename turns the same migration into a findable grep.
The pair semantics are otherwise Filament-exact — each entry is `[$constraint, $condition]`, the condition is evaluated first, and a closure payload may return either a single constraint or an array that flattens in.

### Validation messages apply after the constraints are built

Filament threads three parallel arrays into Livewire's `validate()` — rules, messages, attributes — with `dehydrateValidationMessages()` / `dehydrateValidationAttributes()` collecting the latter two by state path.
Symfony carries the message *on the constraint*, so the two dehydrators have nothing left to collect — **skipped**, with the schemas-side half of the same removal recorded [there](../schemas/can-be-validated.md#schema-level-validation-messages-and-attributes-are-not-ported).

Two shape changes ride along, and together they make the port's messages reach *more* than Filament's keyed messages do.

**Keys are constraint class-strings, not Laravel rule names.**
Filament keys by rule name (`required`, `max`, `email`); a port of that vocabulary would be a set of strings with nothing behind them in Symfony, discoverable only by grepping our source.
`validationMessages()` instead takes `array<class-string<Constraint>, string|Closure|array<string, string|Closure>>`: a string value sets the constraint's `message` option, and an option map sets named ones (any public property named `message` or ending in `Message`) for the constraints carrying several (`Length` has `maxMessage` / `minMessage` / `exactMessage` / `charsetMessage` and no plain `message`; `Choice` has four).
The key is IDE-completable and the option names are the constraint's own properties, so nothing has to be memorised.
Recorded in the [rename table](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary).

**Messages apply in one post-pass, not at construction.**
`getValidationConstraints()` builds the list through [`buildValidationConstraints()`](#the-subclass-seam-is-a-protected-builder-not-the-public-getter) and then hands it to `applyValidationMessages()`, which matches each constraint on its **exact** class (never `instanceof` — a key for a parent should not blanket its subclasses), clones it if a message applies, and writes the named options.
This is why an escape-hatch `->constraint(new Regex('/…/'))` is messageable, where threading messages through constructors would reach only the sugared arms that looked a key up.
Cloning happens only on write, so a user-supplied constraint instance — which `->constraint()` stores and reuses across every call — is never mutated, while an untouched one keeps its identity.

Two behaviours worth stating because they are asymmetric on purpose.
A key matching no constraint the field built is **silently ignored**: `required(false)` and a false `constraint()` condition both legitimately un-emit, so this cannot throw.
An unknown option name **throws** `LogicException` naming the constraint and listing its real options — always a mistake, and worth catching loudly since these are public properties and an unchecked name would rewrite `Length::$max` rather than a message.
That message names the field's state path, so the `LogicException` is what a **wired** field raises; on a field never attached to a schema the state-path read fails first — a direct-call and test scenario only, since every validation path reaches the field through `dehydrateValidationConstraints()`, whose own unguarded state-path read already makes wiring the precondition.
A key that is not a constraint class needs no runtime arm: PHPStan at level max rejects it at the call site.

`getValidationMessages()` keeps Filament's evaluation semantics — values run through `evaluate()`, the result is `array_filter`ed, and the string shorthand normalises onto the `message` key.
Permanent while Symfony owns the message.

One arm of that is narrower than Filament's, and it is worth stating because the type reads like an oversight.
A leaf resolving to `''` is filtered out and the constraint keeps its own default — but a leaf closure **must return a string**.
The evaluating closure is typed `fn (string|Closure $message): string`, so under `strict_types=1` a closure returning `null` is a `TypeError`, not a fallback to the default.

That declaration is forced twice over, so do not "fix" it to `?string`.
PHPStan binds `evaluate()`'s `@return T` to `string` here, by subtracting `Closure` from the property's union ([the closure-typing convention](../../internals/php.md#the-closure-typing-convention)), which makes `?string` a level-max `return.unusedType` error; and Rector's `typeDeclarations` set re-adds the declaration if it is dropped instead.
It is also the contract the property already states — `$validationMessages` types its leaves `string|Closure`, so a closure handing back `null` violates its own declaration, and the fail-fast rule prefers the `TypeError` to a silent fallback that hides it.
A conditional message meant to fall through to the constraint's default is spelled `fn () => $condition ? 'Give it a title.' : ''`.

### The condition-pair walk drops Filament's numeric-key rule shorthand

Filament's `getValidationRules()` walks `[$rule, $condition]` pairs and has to handle a numeric-keyed shorthand (a bare rule string arriving where a pair was expected) plus `getInValidationRule()`'s `In`/`Enum` special case.
With `Constraint` objects there is no shorthand to disambiguate: the walk is condition-then-evaluate-then-append, and the composition order is otherwise Filament's: the required constraint first, then the [length constraints](can-be-length-constrained.md) when the field implements `Contracts\CanBeLengthConstrained`, then the `in()` slot, then the regex slot, then the escape-hatch pairs in declaration order.
The port then appends the [nested `All`](#nested-constraints-land-as-one-all-where-laravel-keys-a-path), and a `buildValidationConstraints()` override such as [`TextInput`](text-input.md)'s appends its own arms after that.
Permanent.

### validationAttribute() is not ported

Filament's `validationAttribute()` / `getValidationAttribute()` supply the field name Laravel interpolates into `:attribute` in a rule message, defaulting to the field's label.
Symfony's violation messages carry no field-name placeholder — `RequiredValue`'s default is "This value should not be blank." with no slot to fill — and the message that reaches the user is rendered *next to* its field by the `FieldWrapper`, so the name is redundant.
**Skipped**; a user who wants the name in the text writes it into `validationMessages()`.
Reverses only if a constraint-message translation layer with an attribute placeholder is added.

### HTML validation messages are not ported

`allowHtmlValidationMessages()` opts a field's messages out of Blade escaping.
The port's messages are plain strings escaped by Twig, and [`getErrorMessages()`](field.md) returns `list<string>`; opting in would mean typing the list `list<string|Markup>` and threading a per-field flag into the wrapper template for no consumer.
**Skipped** — a `Markup` message would be the port's shape if it is ever wanted, not a boolean flag.

### required() carries Laravel's blank set, not Symfony's

Filament's `required()` is Laravel's `required` rule, which [fails only on `null`, a string that trims to `''`, an empty `Countable` and a pathless file](../../../vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php).
Symfony's `NotBlank` has a wider blank set: `false === $value` is the *first* clause of [`NotBlankValidator`](../../../vendor/symfony/validator/Constraints/NotBlankValidator.php)'s blank test.
Translating `required` to the stock constraint would therefore say something Filament never says, that a boolean field cannot submit while off, and it would say it invisibly, because the field a consumer hits it on is usually one with no visible control to correct.

The port emits [`RequiredValue`](../../../packages/forms/src/Validation/RequiredValue.php) instead, whose validator is one call to the house [`filled()`](../support/helpers.md) — which is Laravel's test, and which makes this constraint the exact negation of the `Prohibited` beside it, as Laravel's own `validateProhibited()` is of `validateRequired()`.
Four things follow.

- **`false` is present.**
  `Hidden::make('isApproved')->default(false)` over a non-nullable `bool` column derives a requirement it can satisfy.
  Under `NotBlank` that field would 422 on every submit with nothing on screen to fix.
- **`0` and `'0'` stay present**, as under `NotBlank`, which carves them out with its `'0' != $value` clause and `filled()` never counted them blank, so the `boolean()` arms of [`Radio`](radio.md) and [`Select`](select.md) behave the same under either.
- **A whitespace-only string is absent.**
  `blank()` trims with `mb_trim` where `NotBlank` tests truthiness, so `'   '` violates `required()` where `NotBlank` lets it pass.
  That is Laravel's behaviour and the only widening beyond the boolean case.
- **It is implicit.**
  `RequiredValue` implements schemas' [`ImplicitConstraint`](../schemas/presence-gate.md) marker, without which the presence gate would drop it for exactly the blank string it exists to reject.

The name is `RequiredValue` rather than `Required` to avoid an import collision ([translations.md](../translations.md#uniquevalue-existingvalue-and-requiredvalue-are-named-to-avoid-an-import-collision)): this trait itself imports Symfony's `Required` DI attribute for `setRecordTypeReader()`.

### The required constraint is swappable per field

Filament's `getRequiredValidationRule()` returns `'required'` or `'nullable'` for a subclass to swap — `RichEditor` overrides it to reject a document that is structurally empty.
The port keeps the seam and translates the name with the rest of the [rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary): `getRequiredConstraint(): Constraint` returns the [`RequiredValue`](#required-carries-laravels-blank-set-not-symfonys) that `buildValidationConstraints()` prepends when `isRequired()`.
It carries no message of its own — one is written afterwards by the [post-pass](#validation-messages-apply-after-the-constraints-are-built) if the caller keyed the constraint it returns.

Two shape changes ride along.
It is **protected**, not public: it is a subclass seam, and nothing outside the field calls it — `getValidationConstraints()` is the public read.
And there is no `'nullable'` arm to return, because optionality in Symfony is the *absence* of the constraint ([above](#nullable-removes-the-required-constraint-and-nothing-else)) — the caller asks `isRequired()` first, so the method is only reached when a constraint is wanted.

Two fields override it, in opposite directions.
[`Toggle`](toggle.md#required-means-presence-not-truth) returns `NotNull`, which is presence and nothing else — it tolerates the blank string a tampered payload could put on a boolean path, where the shared constraint would violate.
[`Checkbox`](checkbox.md#required-on-a-checkbox-means-must-be-checked) returns Symfony's `NotBlank`, whose blank set *does* hold `false`, because the checkbox emits the native `required` attribute and a browser reads that as "must be ticked": the server would otherwise accept a submit the client had blocked.

That is also where class-keyed messages are less forgiving than Filament's `required` key — a `Toggle` message keys `NotNull::class` and a `Checkbox` one `NotBlank::class`, neither of them the `RequiredValue::class` every other field emits.
Under class keying that is the rule with no exception rather than a special case: key the constraint your field emits.

### The remaining comparison rule-string builders have no port

`fieldComparisonRule()`, `multiFieldComparisonRule()` and `multiFieldValueComparisonRule()` exist to *assemble Laravel rule strings* — `"gt:{$statePath}"`, `"required_with:{$paths}"`.
They are string plumbing for an engine this port does not use; the behaviour they enable is on the value-comparison and conditional-required rows above, which build `Constraint` objects directly.
**Skipped** as API surface, permanently.
Their sibling `dateComparisonRule()` is the exception and is [**ported**](date-comparison.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) — not as a string builder, but as the shared registration point its four public callers route through, which is worth keeping because a user's own `dateComparisonRule()` call is part of the migrating surface.

### A file field validates in two places, and only one of them is this trait

[`BaseFileUpload`](file-upload.md) splits its constraints by **constraint class**: every `instanceof File` entry plus the first-party [`MinFileSize`](../../../packages/forms/src/Validation/MinFileSize.php) is validated against the real `UploadedFile` at **upload** time through `getUploadedFileConstraints()`, and everything else, including the `RequiredValue` this trait emits, validates the stored **collection** at save ([file-upload.md](file-upload.md#per-file-constraints-run-at-upload-time-collection-constraints-at-save)).
`MinFileSize` is filed here rather than with the field because it is a constraint like the date ones: Symfony's `File` caps a size and never floors it, so Laravel's `min:` rule for files (kibibytes, which is what `minSize()` means) needs a counterpart.
Both halves go through [`applyValidationMessages()`](#validation-messages-apply-after-the-constraints-are-built), so a field's own message overrides reach an upload refusal too.

### The subclass seam is a protected builder, not the public getter

Filament's `getValidationRules()` is both the public read and the point a subclass overrides to append its own arms.
Here those split: `getValidationConstraints()` is the public read the [`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) contract declares, and `buildValidationConstraints()` is the protected seam that [`TextInput`](text-input.md), [`Select`](select.md), [`Radio`](radio.md), [`CheckboxList`](checkbox-list.md), [`ToggleButtons`](toggle-buttons.md), [`Repeater`](repeater.md) and [`BaseFileUpload`](file-upload.md) override.

The split exists because messages apply in a [post-pass](#validation-messages-apply-after-the-constraints-are-built) over the finished list.
A subclass that overrode the public getter would call `parent::`, append its own constraints, and miss that work.
Routing every subclass through the builder makes that unrepresentable.
The getter is deliberately not `final` — a `final` method in a trait is an odd thing to hand a consumer, and `Field` subclasses are a public extension point — so the seam is a documented convention plus a better alternative, not enforcement.
