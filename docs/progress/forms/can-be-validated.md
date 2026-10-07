---
description: The field-side half of the validation vertical, composed on `Field`. (draft)
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeValidated.php
  - vendor/filament/forms/src/Components/Contracts/HasValidationRules.php
  - vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php
owns:
  - packages/forms/src/Components/Concerns/CanBeValidated.php
  - packages/forms/src/Validation/ActiveUrl.php
  - packages/forms/src/Validation/Alpha.php
  - packages/forms/src/Validation/AlphaDash.php
  - packages/forms/src/Validation/AlphaNum.php
  - packages/forms/src/Validation/Ascii.php
  - packages/forms/src/Validation/DateComparison.php
  - packages/forms/src/Validation/Distinct.php
  - packages/forms/src/Validation/EndsWith.php
  - packages/forms/src/Validation/ExistingValue.php
  - packages/forms/src/Validation/MatchesField.php
  - packages/forms/src/Validation/MinFileSize.php
  - packages/forms/src/Validation/Prohibited.php
  - packages/forms/src/Validation/RequiredValue.php
  - packages/forms/src/Validation/StartsWith.php
  - packages/forms/src/Validation/UniqueValue.php
  - packages/forms/src/Validation/ValidDate.php
---
# CanBeValidated (forms)

[`Sylament\Forms\Components\Concerns\CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — the field-side half of the validation vertical, composed on [`Field`](field.md).
It declares what a field requires of its state and turns that into `Symfony\Component\Validator\Constraint` objects: the schema collects them keyed by absolute state path and the host runs `ValidatorInterface` over them, all tracked in [`schemas/can-be-validated.md`](../schemas/can-be-validated.md).
`Field` implements the schemas-side component contract [`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) through this trait.

Filament's trait is one of the largest in the port — some eighty methods, almost all of them Laravel rule-string sugar.
**Every one of them is now settled**: `required()` / `nullable()`, the length constraints via [`CanBeLengthConstrained`](can-be-length-constrained.md), `TextInput`'s `email()` / `numeric()` arms in [`text-input.md`](text-input.md) and `validationMessages()`; the date comparison family; and the rest — the value-comparison and conditional-required arms, the data-layer pair, the format sugar, `regex()`/`notRegex()`, `in()`/`notIn()`, the affix family, `filled()`/`multipleOf()`, `distinct()`, the nested-constraint concern and the `mutateStateForValidation` hooks.
What is left is three message-display members and the `scoped*` pair, all **skipped** with a divergence each.
The `constraint()` / `constraints()` escape hatch still takes any Symfony `Constraint`, so a rule with no sugar is reachable anyway.

**One mechanism carries every cross-field arm** and it is the date family's, generalised: a field reads any other path's live raw state at *constraint-build* time through the root container's property accessor — the identical read the runner performs — and emits a value-only constraint ([the reader](#every-cross-field-arm-resolves-its-sibling-at-build-time)).
No "cross-path validation context" was built, and the rows that named one as their blocker resolve to "none".

The package's own `Validation/` directory grew with it: fourteen constraint classes now, each with a validator, mirroring [schemas' `Validation/`](../schemas/presence-gate.md) one package down.

The rule → constraint vocabulary is a rename, recorded in the [rename table](../renames.md): `rule()` / `rules()` → `constraint()` / `constraints()`, `getValidationRules()` → `getValidationConstraints()`, `dehydrateValidationRules()` → `dehydrateValidationConstraints()`.
The payload changes ecosystem (Laravel rule strings → Symfony `Constraint` objects), so the rename makes the migration break loud and searchable instead of type-erroring on a grep hit.

## Surface

Every point of Filament's trait, grouped where a family shares one fate.
Every row is settled, so the _Blocked by_ column is empty throughout and the **Details** section is gone — its four subsections were deleted with the rows they described.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`required()` / `isRequired()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::required()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) |
| [`required(null)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — undeclare, falling back to derivation | — | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) |
| [`isRequiredByRecord()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — the derivation seam, overridden to `false` on [`Checkbox`](checkbox.md) and [`Toggle`](toggle.md) | — | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) · [semantics](#a-boolean-field-opts-out-of-derivation-but-selectboolean-does-not) |
| [`getRequiredConstraint()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`getRequiredValidationRule()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-required-constraint-is-swappable-per-field) |
| [`nullable()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::nullable()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#nullable-removes-the-required-constraint-and-nothing-else) |
| [`constraint()` / `constraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`rule()` / `rules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#rule-and-rules-become-constraint-and-constraints-over-symfony-constraint-objects) |
| [`getValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`getValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-condition-pair-walk-drops-filaments-numeric-key-rule-shorthand) |
| [`dehydrateValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`dehydrateValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | — |
| [`validationMessages()` / `getValidationMessages()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#validation-messages-apply-after-the-constraints-are-built) |
| Length constraints ([`CanBeLengthConstrained`](can-be-length-constrained.md)) | [`getLengthValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| `email()` / `numeric()` arms ([`TextInput`](text-input.md)) | [`TextInput::email()` / `numeric()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [detail](text-input.md) |
| `dehydrateValidationMessages()` / `dehydrateValidationAttributes()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#validation-messages-apply-after-the-constraints-are-built) |
| `validationAttribute()` / `getValidationAttribute()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#validationattribute-is-not-ported) |
| `showAllValidationMessages()` / `shouldShowAllValidationMessages()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| `allowHtmlValidationMessages()` / `areHtmlValidationMessagesAllowed()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#html-validation-messages-are-not-ported) |
| [`dateComparisonRule()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) |
| `fieldComparisonRule()` / `multiFieldComparisonRule()` / `multiFieldValueComparisonRule()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#the-remaining-comparison-rule-string-builders-have-no-port) |
| [`ValidDate`](../../../packages/forms/src/Validation/ValidDate.php) + its validator — no Filament source | [`DateTimePicker::setUp()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) — `rule('date')` | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) · [diverges](date-time-picker.md#every-variant-validates-as-a-date-including-the-time-only-one) |
| [`DateComparison`](../../../packages/forms/src/Validation/DateComparison.php) + its validator — no Filament source | [`CanBeValidated::dateComparisonRule()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) |
| [`MinFileSize`](../../../packages/forms/src/Validation/MinFileSize.php) + its validator — no Filament source | [`BaseFileUpload::minSize()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) — `rule("min:{n}")` | done | — | [semantics](#a-file-field-validates-in-two-places-and-only-one-of-them-is-this-trait) |
| [Format sugar (`alpha`, `alphaDash`, `alphaNum`, `ascii`, `string`, `hexColor`, `json`, `macAddress`, `ip` / `ipv4` / `ipv6`, `uuid`, `ulid`, `activeUrl`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does) · [semantics](#uuid-is-loose-so-the-nil-uuid-passes) |
| [`regex()` / `notRegex()` / `getRegexPattern()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](text-input.md#tel-writes-the-shared-regex-slot) |
| [`in()` / `notIn()` / `getInValidationConstraint()` / `getInValidationConstraintValues()` / `hasInValidationOnMultipleValues()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| [`startsWith()` / `endsWith()` / `doesntStartWith()` / `doesntEndWith()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does) |
| [`filled()` / `multipleOf()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#filled-is-required-without-the-asterisk) · [diverges](#multipleofs-zero-and-precision-arms-differ-from-laravels) |
| [Date comparison family (`after` / `afterOrEqual` / `before` / `beforeOrEqual`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) · [diverges](#a-blank-sibling-skips-the-comparison-where-laravel-fails-the-field) |
| [Value comparison family (`gt` / `gte` / `lt` / `lte`, `same` / `different`, `confirmed`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#every-cross-field-arm-resolves-its-sibling-at-build-time) · [diverges](#the-ordering-arms-compare-with-php-not-with-laravels-size-table) · [diverges](#same-different-and-confirmed-need-a-first-party-constraint) |
| [Conditional-required family (`requiredIf*` / `requiredUnless` / `requiredWith*` / `requiredWithout*`, `prohibited` / `prohibitedIf` / `prohibitedUnless` / `prohibits`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#a-conditional-requirement-shows-no-asterisk) · [semantics](#the-sibling-decides-the-match-strictness) · [diverges](#prohibited-is-first-party-because-blank-violates-on-an-empty-array) |
| [Data-layer family (`unique` / `exists` / `uniqueValidationIgnoresRecordByDefault`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#unique-and-exists-are-one-dql-probe-behind-a-container-service) · [semantics](#a-sticky-field-probes-the-database-on-every-request) |
| `scopedUnique()` / `scopedExists()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#scopedunique-and-scopedexists-have-nothing-to-scope) |
| [`distinct()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule) |
| [`mutateStateForValidation()` / `mutatesStateForValidation()`](../../../packages/schemas/src/Components/Concerns/HasState.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [detail](../schemas/component.md#the-mutatestateforvalidation-members-mutate-the-runners-copy-of-one-path) · [semantics](#field-does-not-force-mutatesstateforvalidation-true) |
| [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) + its contract | [`HasNestedRecursiveValidationRules`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [diverges](#nested-constraints-land-as-one-all-where-laravel-keys-a-path) |
| [`Prohibited`](../../../packages/forms/src/Validation/Prohibited.php), [`Distinct`](../../../packages/forms/src/Validation/Distinct.php), [`MatchesField`](../../../packages/forms/src/Validation/MatchesField.php), [`StartsWith`](../../../packages/forms/src/Validation/StartsWith.php), [`EndsWith`](../../../packages/forms/src/Validation/EndsWith.php), [`Alpha`](../../../packages/forms/src/Validation/Alpha.php), [`AlphaDash`](../../../packages/forms/src/Validation/AlphaDash.php), [`AlphaNum`](../../../packages/forms/src/Validation/AlphaNum.php), [`Ascii`](../../../packages/forms/src/Validation/Ascii.php), [`ActiveUrl`](../../../packages/forms/src/Validation/ActiveUrl.php) + their validators — no Filament source | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-format-sugar-is-stock-where-a-constraint-fits-and-first-party-where-none-does) |
| [`UniqueValue`](../../../packages/forms/src/Validation/UniqueValue.php), [`ExistingValue`](../../../packages/forms/src/Validation/ExistingValue.php) + their validators — no Filament source | [`CanBeValidated::unique()` / `exists()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#unique-and-exists-are-one-dql-probe-behind-a-container-service) |
| [`RequiredValue`](../../../packages/forms/src/Validation/RequiredValue.php) + its validator — no Filament source | [`CanBeValidated::required()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#required-carries-laravels-blank-set-not-symfonys) |

Coverage: the trait end to end (required default, `RequiredValue` build, the `nullable()` inversion, closure conditions, the derivation set — required from a non-nullable property, through a nested path, `RequiredValue` built from a derived requirement, not-required from a nullable one, from a collection one, from a path the record does not declare, from no bound record and from a field no schema contains, plus explicit `required(false)` and `nullable()` each beating the derived value, and the blank set the constraint carries — `false` accepted on a field required by a non-nullable `bool` column and on an explicitly required one, `null` and `''` still refused — the escape hatch behind true and false conditions, closure constraints returning a single constraint or an array, the message post-pass — string and option-map forms, closure leaves, blank fallback, exact-class matching, clone-on-write, the ignored-key arm and all three throw arms — the length hand-off, every `dehydrateValidationConstraints()` branch, and the date comparison family — each of the four operators, a literal boundary, a closure one, a relative one (`'tomorrow'`), the skip arms for a blank declaration, an unwired field, a blank sibling and an unparseable sibling, the sibling read in its relative and absolute spellings, and the re-read that lets a changed sibling move the boundary) by [`CanBeValidatedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeValidatedTest.php); the `getRequiredConstraint()` override and the boolean derivation opt-out by [`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php) and [`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php), with [`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php) pinning that `boolean()` still derives; the wiring and branch propagation — the container reader reaching a field built by `make()`, requirement derived from the record bound on the root schema, suppressed under a blank nullable branch, resumed once the branch is filled, applied when the branch is not declared nullable, unmoved by a path-less `nullWhenBlank()` container (asserted against two values of an unrelated sibling field, so a walk reading the whole bag fails it), an explicit `required()` staying strict inside a blank branch, and a mid-tree `record()` binding deriving for the fields beneath it both under a bound root and without one — by [`Integration/CanBeValidatedTest`](../../../packages/forms/tests/Integration/Components/Concerns/CanBeValidatedTest.php); the record frame itself — the class taken from the nearest binding, the root no longer reaching inside a subtree that re-roots, a field outside the frame still reading the root, and a memoized path that escapes the frame deriving nothing — by [`CanBeValidatedRecordFrameTest`](../../../packages/forms/tests/Integration/Components/Concerns/CanBeValidatedRecordFrameTest.php); the `isMarkedAsRequired()` fallback by [`FieldTest`](../../../packages/forms/tests/Unit/Components/FieldTest.php); the `TextInput` arms and their ordering by [`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php); the two date constraints against a real Symfony validator — every operator's pass, fail and equal case, the interpolated boundary, the per-operator default messages, the blank/unparseable/non-string skips, and the unknown-operator throw — by [`DateComparisonValidatorTest`](../../../packages/forms/tests/Unit/Validation/DateComparisonValidatorTest.php) and [`ValidDateValidatorTest`](../../../packages/forms/tests/Unit/Validation/ValidDateValidatorTest.php); the end-to-end path — a live host, a real validator, error messages read back per field and rendered into the shipped templates — by [`FieldErrorsTest`](../../../packages/forms/tests/Integration/Components/FieldErrorsTest.php).

The remaining arms extend that in place.
In the Unit [`CanBeValidatedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeValidatedTest.php): the sibling reader (the value baked in, the blank/null/unresolvable skips, the no-host silence across four different arms, the relative and both absolute spellings, and the re-read on a changed sibling); each ordering arm's class, and two numeric strings compared through a live validator; `same()`/`different()`/ `confirmed()` — the label, the polarity, a `Markup` label stripped, a blank and a null partner both compared, and the humanised fallback when no component answers to the partner path; the conditional family — the loose/strict matrix in **both** directions, the accepted and declined lists, `requiredUnless` on an absent sibling, all four `*With*` arms, an enum unwrapped, a boolean field keeping `NotNull`, no second `RequiredValue` on an already-required field, `isRequired()` and `isMarkedAsRequired()` both staying false, the four `prohibited*` arms and `prohibits()`'s pre-rendered label; `filled()` and `multipleOf()` including the closure that resolves to nothing; every format arm's class **and its options**; the regex slot (last call wins, `null` clears, `notRegex` inverts, both together, and `tel()` sharing the slot in both orders); the affix family's comma split and inversions; and `in()`/`notIn()` — the doubling, the enum unwrap, the comma string, `multiple`, `strict`, the three ways to clear the slot and the negation messages.

In the Integration [`CanBeValidatedTest`](../../../packages/forms/tests/Integration/Components/Concerns/CanBeValidatedTest.php): `distinct()`'s three arms over a real `Repeater` with positional item keys — the scalar sibling list from both ends, the boolean `isSiblingSelected` and `mustBeSelected`, the array union, a layout component between item and field, and the four silences (lone item, blank own value, no repeater, no host); the `in()` guard replacing the derived check on `Select`, surviving on an option-less `Radio` where the derived check would throw, and going `multiple` on all three multi-value fields; the contract on all four consumers; and the data layer — the entity class and property resolved from the schema's record, the identifier ignored and not ignored across four arms, an explicit `ignorable:` winning, no constraint without an entity class, and `modifyQueryUsing` reaching the Invoker.
The required constraint's blank set runs there too, through the real validator against a record: `false` clearing on a `Hidden` derived-required by `Article::$featured` and on an explicitly required one, `null` and `''` still reported on a required `TextInput` — the last of which is what bounds the widening, the gate having to keep an implicit constraint for it to fire at all.

The constraints themselves are driven against a real Symfony validator, one file per class under [`tests/Unit/Validation/`](../../../packages/forms/tests/Unit/Validation) — including `ActiveUrl` through a DNS-seam subclass so the suite never touches the network, and `UniqueValue`/`ExistingValue` over the SQLite rig with their three `LogicException` arms; the container route for the two service validators by [`ConstraintValidatorServicesTest`](../../../packages/forms/tests/Integration/Validation/ConstraintValidatorServicesTest.php); the nested concern by [`HasNestedRecursiveValidationConstraintsTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasNestedRecursiveValidationConstraintsTest.php), message recursion included; and `trim()`/`stripCharacters()`'s three-way asymmetry by [`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php) and [`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php).

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
`distinct()` resolves nothing without this, and the date comparison closures had the same latent defect.
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
The precedent is [`getEnabledOptionValues()`](has-options.md), which produces every accepted value in both spellings for the same reason.

`hasInValidationOnMultipleValues()` — `false` on the trait, `true` on [`CheckboxList`](checkbox-list.md), `isMultiple()` on [`Select`](select.md) and [`ToggleButtons`](toggle-buttons.md) — means "per element", expressed as `Choice(multiple: true)` on the field's own path rather than Laravel's `.*` key.

On the four option fields, a user list **replaces** the derived membership check rather than joining it, which is what Filament's overrides do by returning the parent's list first.
The guard sits after each field's blank early return and **before** its no-options `LogicException`, so `in()` on an option-less field is valid — placing it after the throw would make that call fail.

Filament's `getInValidationRule()` also falls back to `Rule::enum($enum)` when no user list is set.
That is not re-emitted: the option builders already derive a `Choice` from the enum's cases.

### uuid() is loose, so the nil UUID passes

`Uuid(strict: false)`, because strict rejects the nil UUID that Laravel's regex accepts and real data contains.
Loose also drops the version and variant checks — neither of which Laravel makes either.
The cost is the other direction: braced and hyphen-less forms pass here and (probably) not there.

### A sticky field probes the database on every request

The [sticky pass](../schemas/can-be-validated.md#sticky-re-validation-replaces-filaments-submit-only-model) re-runs every constraint on every request while a schema is sticky, so a `unique()` field costs one indexed probe per request in that state where Filament pays it only on submit.
Accepted and recorded; a submit-only flag is the reversal if it ever matters.

A blank value never reaches the database at all: `UniqueValue` and `ExistingValue` are not implicit, so the gate strips them from a blank string, and their validators return on `null` regardless.

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
A "skip every `bool` target" rule inside `isRequiredByRecord()` was proposed first and is actively wrong: it cannot see which of three fields bound to the same column is asking.
`Select`'s behaviour is pinned by a regression test so a future overgeneralisation of the boolean suppression fails loudly.

The same predicate has a branch-side counterpart, added for the same reason and placed the same way.
[The built-in blank test asks each declared leaf](../schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves) whether its value is blank, and `Checkbox`/`Toggle` override `isBlankStateValue()` so their resting `false` answers yes.
Without it `filled(false)` held every branch containing a boolean field permanently non-blank, so the suppression above never engaged there and the branch could never null.
`Select::boolean()` needs no override on this side either, and for the same reason as the last: its placeholder already dehydrates null, which the default test reads as blank.

### nullable() removes the required constraint and nothing else

`nullable()` is Filament's inversion verbatim — it calls `required(fn () => ! evaluate($condition))`, so it is a *setter for the same flag*, not an independent one, and a later `required()` overwrites it.
There is deliberately no Symfony counterpart constraint to add: every constraint in the ported subset returns without a violation for `null`, so "optional" is the absence of the required constraint — matching Laravel's implicit-rule skip, which is what made Filament's `nullable()` a pure inverse of `required()` in the first place.

That parity holds for the empty string a cleared input submits as well, but not by the same mechanism: `Length(min:)`, `Type('numeric')` and `GreaterThanOrEqual` each violate on `''` where they return silently on `null`, so the promise is kept one layer up by the [presence gate](../schemas/presence-gate.md), which filters them out before the runner sees them exactly as Laravel's own `presentOrRuleIsImplicit()` does.
`nullable()` is faithful either way; what the gate supplies is the presence check in front of it.

### The blank-string presence check lives in schemas, not on the field

Laravel checks presence **once, centrally, in front of every rule**, so a blank string bypasses `min`, `max`, `email`, `numeric`, `regex` and `url` alike.
This port does the same, and the reason it is recorded here at all is that the obvious place to look for it is the wrong one: it is not an arm of `buildValidationConstraints()`, and no field's builder guards on blankness for this reason.
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

A blank, null or unresolvable sibling drops the constraint rather than failing the field — the same ruling the [date family](#a-blank-sibling-skips-the-comparison-where-laravel-fails-the-field) made, and Laravel's own answer for a missing sibling is direction-dependent (passes `after`, fails `before`, fails `gt`), so there is nothing coherent to be faithful to.
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

Two things this buys that the stock classes could not: its own `validationMessages()` class key, so `same()` no longer shares `IdenticalTo::class` with a user's escape-hatch constraint; and messages that **name** the partner instead of printing it — `'This value must match {{ other }}.'` rather than a confirmed password landing in the DOM.

**The `===` hazard is stated, not solved.**
Raw state is a string on the wire but keeps the record's PHP type after `fill()` until the browser edits it, so `same()` between a wire string and a filled `int` fails until both are typed into.
Filament's `same` has the identical property against Livewire's bag.

`confirmed()` keeps Filament's argument-less signature and Laravel's partner rule: the sibling at `<own absolute path>_confirmation`, which is a **real component** in the same container, not an extra request key.
The suffix stays snake_case because that is what the rule means and what a migrating `confirmed()` call expects — Filament's own auth pages never call it, using `same('passwordConfirmation')` instead.
Laravel 13's `confirmed:other` custom partner is not exposed; Filament's signature has no slot for it and `same()` covers the case.

### Prohibited is first-party because Blank violates on an empty array

Stock `Blank` was measured and rejected: it violates on `[]`, so a [`CheckboxList`](checkbox-list.md) or multiple [`Select`](select.md) with nothing ticked would read as "not blank" and a `prohibited()` on it would fail for being empty.
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

Filament's `distinct()` is **not** Laravel's `distinct` rule, and this tree predicted the wrong landing for it (`Assert\Unique(fields:)` over the repeater's path).
It is a per-item closure over the parent repeater's sibling raw state that dispatches on the *own value's PHP type*, and the port reproduces exactly that: [`Distinct`](../../../packages/forms/src/Validation/Distinct.php) `(mode:, siblingValues:, isSiblingSelected:, mustBeSelected:)` with Filament's three message keys as three message options.

- **scalar** — violate when a sibling holds the same value.
  Comparison is loose, Filament's `Collection::contains`, because a filled item may hold a record `int` beside a wire string.
- **array** — violate on any intersection with a sibling's array.
- **boolean** — violate when this item is ticked and a sibling is too (`onlyOneMustBeSelectedMessage`), and when nothing is ticked anywhere while the field `isRequired()` (`mustBeSelectedMessage`).

The facts are resolved at build time and handed over as plain values, so the constraint itself never walks the schema.
The field's path *within* its item is taken by stripping the item schema's own prefix, where Filament does string surgery on the first dot — which assumes an item key contains none, and a uuid-keyed repeater is one rename away from breaking that.

Item-level error placement comes free, because the constraint sits on the item field's own path.
`fixIndistinctState()` and `disableOptionsWhenSelectedInSiblingRepeaterItems()` read the same arithmetic: the five values this builder derived inline moved to [`RepeaterSiblingContext`](can-fix-indistinct-state.md#the-five-sibling-facts-are-one-value-object-not-string-surgery) behind `ResolvesRepeaterSiblingContext`, which `Field` composes and all three consumers call.
Semantics are unchanged — the extraction preserved every `null` return, and these tests are what characterized it.

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
A subclass costs eleven lines and buys a readable default message plus **its own class key**, so `[Alpha::class => …]` does not also rewrite `tel()`'s and `regex()`'s messages the way a `Regex::class` key would.

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

### unique() and exists() are one DQL probe behind a container service

Filament's `unique()`/`exists()` take an Eloquent table and column and lean on Laravel's query builder.
The port takes an **entity class** and a **mapped property** and runs one DQL probe through schemas' [`AssociationReader`](../schemas/association-reader.md), which already ships `createOptionQuery()`, `findRecordKey()` and `hasEntityManager()` — and which narrows the probe through schemas' own [`IdentifierConstraint`](../schemas/record-key.md#the-operations-and-their-dql) rather than through a field name.
No new dependency either way: forms declares `doctrine/orm ^3.7`, which guarantees every `Doctrine\ORM\*` symbol transitively, and `sylament/schemas`, which it has always required.

Three parameter renames come with it, all recorded in [renames.md](../renames.md): `table` → `entity` (a class-string, not a table), `column` → `property` (a mapped field, not a column), and `modifyRuleUsing` → `modifyQueryUsing` (the closure receives a Doctrine `QueryBuilder`, not a Laravel `Unique` rule — and `modifyQueryUsing` is the name [`Select::relationship()`](select.md) already uses for the same thing).
A fourth signature delta is forced by PHP rather than chosen: `$ignorable` is typed `?object` where Filament types it `Model|Closure|null`, because a `Closure` *is* an object and PHP rejects the union outright.
The closure arm still works — `evaluate()` invokes it.

The class names are `UniqueValue` and `ExistingValue`, not `Unique`/`Exists`.
Symfony ships its own `Constraints\Unique` (which asserts an *array* has no duplicates), and two classes of that name in one import list would let an IDE auto-import the wrong one as a `validationMessages()` key — where an unmatched key is [silently ignored by design](#validation-messages-apply-after-the-constraints-are-built).
So the classes say what they assert.

**The validators are container services**, registered by a `src/Validation/*Validator.php` glob in [`SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php) with `autowire()` + `autoconfigure()`.
`ContainerConstraintValidatorFactory` resolves a `validatedBy()` through a locator and falls back to `new $class()`, which for a validator with a required dependency is an `ArgumentCountError` — a raw PHP `Error`, not a validation failure.
The constructor is therefore `?AssociationReader $associationReader = null`, the nullable-with-fallback shape `AbstractComparisonValidator` and `WhenValidator` use, and the null arm throws a `LogicException` naming the bundle.
That is deliberate: **a uniqueness check that silently does nothing is a security-shaped failure**, so this takes the "act loudly" side of the reader's two guard flavours.
A property that is not a mapped field of the class is refused the same way, naming both.

Defaults are Filament's with the Laravel injection translated.
`$entity` falls back to `getRecordClass()` — the component tier's nearest-first walk, which is right for uniqueness because a relationship repeater's item field binds to the related class.
`$property` falls back to `getName()`.
The ignorable is `getRecord()` when `$ignoreRecord ?? shouldUniqueValidationIgnoreRecordByDefault()` holds and no explicit one was given.
**No entity class resolves → no constraint**, Filament-exact: a scratch-state card with no record binding and no `entity:` argument validates nothing.

The identifier to ignore comes from [`AssociationReader::findRecordKey()`](../schemas/association-reader.md#findrecordkey-answers-null-where-getrecordkey-throws), the sibling of `getRecordKey()` that answers null for an entity whose identifier is not complete yet instead of throwing — which is the create page's normal state, not an edge, and which covers an **assigned** key still sitting blank as well as a generated one still pending.
It is a record **key** of any width, so `UniqueValue::$ignoredIdentifier` carries one string and the exclusion is [`IdentifierConstraint::notEquals()`](../schemas/record-key.md#the-operations-and-their-dql) under the base `sylament_ignored_record`, `andWhere`d only when the predicate is non-null — a **malformed** key excludes nothing, which for that input is the safe direction: a false *taken*, never a false *unique*.
That base is a literal where the value arm still counts `sylament_probe_<n>`, because a parameter base ending in `_<digits>` is refused for **every** class, a single identifier included.
For a single-identifier entity the exclusion returns the same rows the value arm's `:sylament_probe_<n>` exclusion returns, and only the bound parameter's **name** differs, `:sylament_probe_1` → `:sylament_ignored_record` — which is observable to a `modifyQueryUsing` closure that reads or rebinds parameters by name.

The exclusion is by **key**, not by class, so `unique(entity: Other::class)` on a page bound to a record of a *different* class excludes whatever row of the probed class happens to carry that key — a false *unique*, pre-existing and Filament's parity behaviour, where `Select::applyIgnoredRecord()` refuses a foreign-class record by name.
Widening the key made that strictly safer rather than worse: a record whose key is a different **width** now decodes to `null` and excludes nothing at all, where before it was a raw bind.

`exists()` probes a **column**, never a key, and that classification is why `ExistingValueValidator`, `ProbesRecords` and `UniqueValue`'s own probe builder have no key arm, on purpose.
`createProbeQuery()` admits `getFieldNames()` only, so an identifier **association** is refused by name as the unmapped field it is — never probed by key — and a scalar identifier half is an ordinary column whose `=` and `IN` compare as any column's do, at every identifier width.
The case `refuses an identifier association as the unmapped field it is, rather than probing by key` in [`ExistingValueValidatorTest`](../../../packages/forms/tests/Unit/Validation/ExistingValueValidatorTest.php) is what pins it, so a later sweep over the by-key sites does not turn this one into a key probe.
Only `unique(ignoreRecord:)` reads a key, and only for its exclusion.
A taken assigned identifier does not change that: the key probe it needs is schemas' own, inside the record check `getData()` ends in ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)), so no key field needs a `unique()` and no validator here has a key arm.
A `unique()` declared on a key half anyway still probes that one column, under its own *"This value is already taken."*, and answers during field validation — before the record check runs.

`ExistingValue` is the one place a `COUNT` reads better than a limited fetch: an array value (a multiple select) is checked as Laravel's `exists` checks it, one `IN` probe over the distinct values, violating unless the matched count equals the count asked for.
Every other "is there one?" probe in the repo is a limited `getOneOrNullResult()`, and `UniqueValue` stays that way.
Query parameters are counted (`sylament_probe_<n>`) the `SearchConstraint` way, since a caller's `modifyQueryUsing` may already have bound some.

### scopedUnique() and scopedExists() have nothing to scope

Upstream these exist to route around Laravel's `unique`/`exists` rules bypassing Eloquent's **global scopes**: the rules query the table directly, so a soft-delete or tenancy scope declared on the model is not applied, and the `scoped*` variants re-run the check through the model's own builder.
A DQL probe runs through the entity manager, so Doctrine's SQL filters already apply and there is nothing left to scope — `unique()` and `exists()` *are* the scoped forms here.
**Skipped**, permanently.
`modifyQueryUsing:` covers the case where a caller wants a narrower probe than the entity's own filters give.

The other side of running through the filters is a row a filter hides.
A soft-deleted row still holds the column's database unique index, so under a soft-delete filter `unique()` reads its value as free and the flush meets a `UniqueConstraintViolationException` — a 500.
That is the policy rather than an oversight: `unique()` answers what the entity's own filters show, as Filament's scoped forms do.
The record check's taken-**key** probe is the one read that suspends every filter, because a primary key is unique whatever a filter shows ([what the taken-key probe cannot see](../schemas/association-reader.md#what-the-taken-key-probe-cannot-see)).

### Nested constraints land as one All where Laravel keys a path

`HasNestedRecursiveValidationRules` ports as [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) — trait and contract — under the same `rule` → `constraint` vocabulary [the rename table](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) sets: `nestedRecursiveConstraint()`, `nestedRecursiveConstraints()`, `getNestedRecursiveValidationConstraints()`.
Laravel's `"{$statePath}.*"` key has no per-path counterpart here, so the list is emitted as one `All` on the field's **own** path, which is where the wrapper puts a `.*` message upstream anyway; the index each violation carries is discarded by the runner.
The string pipe-syntax arm goes with the rest of Laravel's string rules, and so does the numeric-key shorthand.

The `All` is appended **only when the raw state is an array**.
Over anything else `AllValidator` throws into the runner's hardcoded "should be of type iterable" message, and the option fields' `Type('array')`-then-return arm already speaks for a non-array.

Consumers: [`Select`](select.md), [`CheckboxList`](checkbox-list.md), [`ToggleButtons`](toggle-buttons.md), [`TagsInput`](tags-input.md) and [`BaseFileUpload`](file-upload.md).
`ToggleButtons` gets the **contract** as well as the trait, which fixes an upstream defect where its nested rules are silently dropped.
[`FusedGroup`](fused-group.md) stays skipped — it is not a field.
For a file field the `All` is a non-`File` constraint and so stays on the collection set at save.

Messages reach the nested members, which is a divergence in our favour: the post-pass now recurses through `Composite::getNestedConstraints()` and writes the rebuilt list back, cloning at each level.
Filament has no working per-element message customisation.
Two lists the recursion does not reach are recorded rather than handled — `When::$otherwise` and `Collection::$fields`; no arm emits either.
The four composite classes are named explicitly rather than matched on `Composite`, which declares no property of its own: the name of the one holding the members is a `protected` hook each subclass answers.

`All`, `When`, `Callback` and `Sequentially` carry **no `message` option** of their own, so the string shorthand keyed on any of them still throws the documented `LogicException` — the same arm `Length` (which has `minMessage`/`maxMessage` but no `message`) already hits.

### Requirement is derived from the record's write-side types

Filament's `$isRequired` starts at `false`; here it starts at **`null`**, meaning *never declared*, and `isRequired()` falls through to `isRequiredByRecord()` when nothing was declared.
A field bound to a record path whose write-side type is non-nullable is required; one bound to a nullable target is not.
Explicit declarations win in both directions and are unchanged — `required()`, `required(false)` and `nullable()` all beat the record.

**No Filament precedent exists, and the reason is structural rather than a gap in their API**: Eloquent attributes are untyped, so "may this column accept null?" is not a question a Filament schema can ask.
Their equivalent is three manual declarations that must be kept in sync with the schema by hand — fields left un-`required()`, `requiredWith()` on the siblings, `condition:` on the container.

**Class and path are both read in the nearest record frame** — `getNearestRecordClass()` and `getNearestRecordStatePath()`, the first node above the field that binds a record of its own, else the root container.
The two have to name the same frame, and [the pair comes off one walk](../schemas/belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) **so the two agree**.
What the trait shipped with instead was the root frame for both halves, chosen because reading only the class nearest-first mixed them: `Section::make('author')->statePath('author')->record(Author::class)` asked `describe(Author::class, 'author.name')`, which no type answers, and the walk derived **not required** rather than failing.
Framing both halves is the fix — that section now asks `describe(Author::class, 'name')`, so **a field inside a class-stamped frame over a non-nullable property derives required**.
It is also the half a stamped subtree needs: with the frame in place, a [relationship repeater](repeater.md) item or an [entangled subtree](../schemas/entangles-state-with-singular-relationship.md) binding its own record derives against that record instead of asking the root about a `record-<id>` segment no property answers.
Where the field's path does not nest under the frame's, the path half is null and derivation gives up rather than indexing the frame's record with a path that is not its own.
[`getRecordStatePath()`](../schemas/belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) is untouched and the map stays root-framed; only this derivation moved, and a component-tier `record()` binding now feeds it as well as [closure injection and mapping-independent reads](../schemas/belongs-to-record.md#the-component-tier-gains-record).

The signature widens to carry it: `required(bool|Closure|null $condition = true)`, where Filament's is `bool|Closure`.
**`required(null)` is a new public arm** and it is not the same as `required(false)` — it *undeclares*, handing the decision back to the record, which is the only way to get derivation back after a conditional call has set the flag.
`required(false)` remains a declaration and still suppresses.
Its coverage is the property's own `null` default rather than an explicit `required(null)` call — the same single assignment and the same `$declared !== null` branch, exercised by every derivation test above.

Two behaviours make this safe to have on by default, and both are load-bearing:

- **It suppresses itself under a blank nullable branch.**
  `hasBlankNullableBranchAncestor()` walks the container chain, and derivation gives up as soon as an ancestor declaring [`nullWhenBlank()`](../schemas/belongs-to-record.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) is currently blank.
  Without it the feature deletes the feature that motivated it: `Article::$author` nullable with `Author::$name` non-nullable would make the name unconditionally required, so a blank author could never be submitted, so the branch could never be nulled.
  With it, the pair states exactly *"name is required if there is an author"* — Filament's `condition:` + `requiredWith()` shape, derived instead of hand-written.

  An ancestor is only a branch if it carries a **record state path**, and that gate is not cosmetic symmetry with the mapper's own walk.
  `getRawState()` on a path-less component falls through to its container's path, so before the gate a path-less `nullWhenBlank()` container handed `shouldNullBranch()` the whole form's raw bag — and one field's requirement moved with unrelated fields elsewhere on the page.
  [The mapper now refuses that declaration outright](../schemas/belongs-to-record.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored); the two walks agree on what a branch is.
- **It answers `false` wherever it cannot be sure.**
  No container, no record on the frame, no frame-relative path — a field carrying none of its own, or one whose path escapes the frame — a path the record does not declare, or a collection-typed target: each returns false rather than guessing.
  The collection skip uses the wide `isCollection` flag, since a blank value says nothing about whether an `array` target accepts it.

**Accepted cost, stated because it is visible on every existing form:** asterisks and native `required` attributes now appear wherever a bound target is non-nullable, without anyone writing `required()`.
That is the intended layering fix — [a blank non-nullable leaf becomes a field error instead of a mapper crash](../schemas/can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) — but it does change existing forms, and a field that should stay optional against a non-nullable target now has to say `nullable()` out loud.

One rough edge rides along, pre-existing but with a larger population now: a `dehydrated(false)` leaf still validates, so it can derive a constraint for a write that never happens.
Harmless where the field is genuinely page-only and filled; combined with a nullable branch it is the sharp end of [the two-bag hazard](../schemas/belongs-to-record.md#a-nullwhenblank-closure-sees-two-different-bags).

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
`validationMessages()` instead takes `array<class-string<Constraint>, string|Closure|array<string, string|Closure>>` — a string value sets the constraint's `message` option, an option map sets named ones for the constraints carrying several (`Length` has `maxMessage` / `minMessage` / `exactMessage` / `charsetMessage` and no plain `message`; `Choice` has four).
The key is IDE-completable and the option names are the constraint's own properties, so nothing has to be memorised.
Recorded in the [rename table](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary).

**Messages apply in one post-pass, not at construction.**
`getValidationConstraints()` builds the list through [`buildValidationConstraints()`](#the-subclass-seam-is-a-protected-builder-not-the-public-getter) and then hands it to `applyValidationMessages()`, which matches each constraint on its **exact** class (never `instanceof` — a key for a parent should not blanket its subclasses), clones it if a message applies, and writes the named options.
This is why the old cost of this divergence is gone: an escape-hatch `->constraint(new Regex('/…/'))` is now messageable, where threading messages through constructors reached only the sugared arms that looked a key up.
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
With `Constraint` objects there is no shorthand to disambiguate: the walk is condition-then-evaluate-then-append, and the composition order is otherwise Filament's — the required constraint first, then the [length constraints](can-be-length-constrained.md) when the field implements `Contracts\CanBeLengthConstrained`, then the escape-hatch pairs in declaration order, then any subclass arms appended by a `buildValidationConstraints()` override ([`TextInput`](text-input.md)).
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
Symfony's `NotBlank` has a wider blank set: `false === $value` is the *first* clause of [`NotBlankValidator`](../../../vendor/symfony/validator/Constraints/NotBlankValidator.php).
Translating `required` to the stock constraint therefore said something Filament never says — that a boolean field cannot submit while off — and it says it invisibly, because the field a consumer hits it on is usually one with no visible control to correct.

The port emits [`RequiredValue`](../../../packages/forms/src/Validation/RequiredValue.php) instead, whose validator is one call to the house [`filled()`](../support/helpers.md) — which is Laravel's test, and which makes this constraint the exact negation of the `Prohibited` beside it, as Laravel's own `validateProhibited()` is of `validateRequired()`.
Four things follow.

- **`false` is present.**
  `Hidden::make('isApproved')->default(false)` over a non-nullable `bool` column — the shape a consumer reported on 2026-09-01 — derives a requirement it can now satisfy.
  Under `NotBlank` that field 422'd on every submit with nothing on screen to fix.
- **`0` and `'0'` stay present**, as they already were: `NotBlank` carves them out with its `'0' != $value` clause and `filled()` never counted them blank, so the `boolean()` arms of [`Radio`](radio.md) and [`Select`](select.md) are unchanged.
- **A whitespace-only string is now absent.**
  `blank()` trims with `mb_trim` where `NotBlank` tested truthiness, so `'   '` violates `required()` where it used to pass.
  That is Laravel's behaviour and the only widening beyond the boolean case.
- **It is implicit.**
  `RequiredValue` is the first implementor of schemas' [`ImplicitConstraint`](../schemas/presence-gate.md) marker, without which the presence gate would drop it for exactly the blank string it exists to reject.

The name is `RequiredValue` rather than `Required` for the reason [`UniqueValue` is not `Unique`](../translations.md#names-with-no-filament-source): Symfony ships `Symfony\Contracts\Service\Attribute\Required`, the DI attribute this trait itself imports for `setRecordTypeReader()`, so the two collide in one import list — and a `validationMessages()` key auto-imported from the wrong one is silently ignored by design.

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

That is also where class-keyed messages are less forgiving than Filament's `required` key was — a `Toggle` message keys `NotNull::class` and a `Checkbox` one `NotBlank::class`, neither of them the `RequiredValue::class` every other field emits.
Under class keying that is the rule with no exception rather than a special case: key the constraint your field emits.

### The date comparison family builds constraints where Filament builds rule strings

`after()` / `afterOrEqual()` / `before()` / `beforeOrEqual()` keep Filament's signatures verbatim, including the `bool $isStatePathAbsolute = false` tail, and still delegate to a shared [`dateComparisonRule(string $rule, …)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) whose `$rule` argument carries Filament's own rule spellings — `'after'`, `'after_or_equal'`, `'before'`, `'before_or_equal'`, which are the four [`DateComparison`](../../../packages/forms/src/Validation/DateComparison.php) operator constants.
A migrating `->dateComparisonRule('after', …)` call therefore still compiles and still means what it meant.

What changes is what the method *produces*.
Filament returns the string `"after:{$date}"` for Laravel to parse; here the method registers a closure through the existing [`constraint()`](#rule-and-rules-become-constraint-and-constraints-over-symfony-constraint-objects) pair mechanism, and the closure returns a `DateComparison` object — or `null`, which the builder's walk already skips.
Two constraints ship with it, because neither has a stock counterpart:

- **`DateComparison`** carries an operator, a resolved `DateTimeImmutable` boundary and a per-operator default message.
  Symfony's own comparison validators cannot do this job: `AbstractComparisonValidator` converts a string `comparedValue` only when the **validated value** is already a `DateTimeInterface`, and the runner validates raw wire strings, so `GreaterThan(value: $date)` would compare a string against an object.
- **`ValidDate`** is the port of the `rule('date')` that [`DateTimePicker::setUp()`](date-time-picker.md#every-variant-validates-as-a-date-including-the-time-only-one) registers, with no stock equivalent either — `Type('string')` says nothing about parseability and `DateTime` (the constraint) demands an exact format, where the field's wire format is deliberately [loose about the `T` and the seconds](date-time-picker.md#the-wire-format-uses-a-space-and-the-browser-answers-with-a-t).

Both are listed in the [rename table](../translations.md#names-with-no-filament-source) as names with no Filament source.
The boundary is resolved **per validation run**, not at declaration: `after('2026-01-01')` re-parses the literal each time, and `after('startsAt')` re-reads the sibling each time, which is what lets a comparison against another field track that field as the user types.

The literal-versus-field-name discrimination is Filament's, `strtotime()` and all: a value `strtotime()` reads is the boundary itself, anything else is a state path resolved through [`resolveRelativeStatePath()`](../schemas/component.md), the same resolver [`Get`](../schemas/get.md) uses.
It carries Filament's wrinkle with it — a field actually named `may` or `tomorrow` parses as a date and is never treated as a path — and the escape is Filament's too: the leading-slash spelling, `'/data.startsAt'`, which `strtotime()` rejects, so the name reaches the state-path arm and `resolveRelativeStatePath()` strips the slash and reads the rest as absolute.
`isStatePathAbsolute: true` is **not** a second escape.
The `strtotime()` test runs first whatever the flag says, and the flag is only read inside the arm that test already routed to — it chooses absolute over container-relative resolution for a name that failed `strtotime()`, and it suppresses nothing.

### A blank sibling skips the comparison where Laravel fails the field

`after('startsAt')` with `startsAt` empty builds **no constraint at all** here, so the dependent field validates clean.
Laravel's `after:start` fails the dependent field instead, because an unresolvable comparison value is a failed comparison over there.

The port's reading is that presence is the *other* field's business: `startsAt` declares its own `required()` (or derives one from the record), and duplicating that decision onto every field that compares against it produces two errors for one empty input, on the field the user did not touch.
The same rule covers a sibling whose value cannot be parsed and a sibling with no live component to read at all — an unresolvable boundary is no comparison, never a violation.

The cost is stated because it is a real hole: a form whose `startsAt` is optional accepts any `endsAt`, and nothing warns.
A caller who wants the pair enforced says so on the sibling — `required()` on `startsAt` — or writes the dependency as a closure constraint of its own.

### The remaining comparison rule-string builders have no port

`fieldComparisonRule()`, `multiFieldComparisonRule()` and `multiFieldValueComparisonRule()` exist to *assemble Laravel rule strings* — `"gt:{$statePath}"`, `"required_with:{$paths}"`.
They are string plumbing for an engine this port does not use; the behaviour they enable is tracked on the value-comparison and conditional-required rows above, which will build `Constraint` objects directly.
**Skipped** as API surface, permanently.
Their sibling `dateComparisonRule()` is the exception and is [**ported**](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) — not as a string builder, but as the shared registration point its four public callers already routed through, which is worth keeping because a user's own `dateComparisonRule()` call is part of the migrating surface.

### A file field validates in two places, and only one of them is this trait

[`BaseFileUpload`](file-upload.md) is the first field whose constraints do not all run at the same moment, and it splits them by **constraint class**:

- every `instanceof File` entry — `File(mimeTypes:)` from `acceptedFileTypes()`, `File(maxSize:)` from `maxSize()`, `Image` ratio bounds from `imageAspectRatio()`, and any the user added through `constraint()` — plus the first-party [`MinFileSize`](../../../packages/forms/src/Validation/MinFileSize.php) leave through `getUploadedFileConstraints()` and are validated against the real `UploadedFile` at **upload** time;
- everything else — the `RequiredValue` this trait emits, `Count` for the file counts, the path-tampering `Callback`, and every non-`File` user constraint — stays on `buildValidationConstraints()` and validates the stored **collection** at save.

Symfony's `File` and `Image` validators need a local readable path, which a stored flysystem key is not, so the first group could not run at save at all.
`MinFileSize` is filed here rather than with the field because it is a constraint like the two date ones beside it: Symfony's `File` caps a size and never floors it, so Laravel's `min:` rule for files — kibibytes, which is what `minSize()` means — needed a counterpart.
Both halves go through [`applyValidationMessages()`](#validation-messages-apply-after-the-constraints-are-built), so a field's own message overrides reach an upload refusal too.

The full write-up, including why the override sits on the builder rather than the getter, is [file-upload.md](file-upload.md#per-file-constraints-run-at-upload-time-collection-constraints-at-save).

### The subclass seam is a protected builder, not the public getter

Filament's `getValidationRules()` is both the public read and the point a subclass overrides to append its own arms.
Here those split: `getValidationConstraints()` is the public read the [`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php) contract declares, and `buildValidationConstraints()` is the protected seam [`TextInput`](text-input.md), [`Select`](select.md) and [`BaseFileUpload`](file-upload.md) override.

The split exists because messages apply in a [post-pass](#validation-messages-apply-after-the-constraints-are-built) over the finished list.
A subclass that overrode the public getter would call `parent::`, append its own constraints, and miss that work.
Routing every subclass through the builder makes that unrepresentable.
The getter is deliberately not `final` — a `final` method in a trait is an odd thing to hand a consumer, and `Field` subclasses are a public extension point — so the seam is a documented convention plus a better alternative, not enforcement.
