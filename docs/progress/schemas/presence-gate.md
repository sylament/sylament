---
description: '`PresenceGate` and the `ImplicitConstraint` marker: Laravel''s presence check, which runs only the implicit constraints against a blank string'
ports: []
owns:
  - packages/schemas/src/Validation/Contracts/ImplicitConstraint.php
  - packages/schemas/src/Validation/PresenceGate.php
---
# PresenceGate (schemas)

[`Sylament\Schemas\Validation\PresenceGate`](../../../packages/schemas/src/Validation/PresenceGate.php) and the [`ImplicitConstraint`](../../../packages/schemas/src/Validation/Contracts/ImplicitConstraint.php) marker interface it consults — a port of **Laravel's** presence check, not of anything in Filament.
Filament has no counterpart because it never needed one: its rules run through Laravel's validator, which gates them centrally, so the behaviour arrives for free and is invisible in Filament's own source.

It exists because Symfony's validators disagree about the empty string: handed `''`, some return before comparing anything and some compare it.
Without this gate, an **optional** field a user typed into and then cleared would report *"This value should be greater than or equal to 1"* from `minValue(1)` while `maxValue(10)` beside it stayed quiet, and `numeric()` would say the value was not numeric — one field, three arms, no agreement.
Laravel runs none of those rules on a blank string.
`PresenceGate::apply()` filters a path's constraints down to the implicit ones before [`validateSchema()`](can-be-validated.md#validateschema-runs-symfonys-validator-per-state-path-instead-of-livewires-whole-component-validate) hands them to the validator, which restores that.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`apply(mixed $value, array $constraints)`](../../../packages/schemas/src/Validation/PresenceGate.php) — the filter | [`Validator::presentOrRuleIsImplicit()`](../../../vendor/laravel/framework/src/Illuminate/Validation/Validator.php) | done | — | [diverges](#the-gate-sits-in-front-of-the-runner-not-inside-the-validator) |
| The blank test — `is_string($value) && mb_trim($value) === ''` | [`presentOrRuleIsImplicit()`'s first branch](../../../vendor/laravel/framework/src/Illuminate/Validation/Validator.php) | done | — | [diverges](#the-blank-test-trims-multibyte-where-laravel-trims-bytes) |
| The implicit set — `NotBlank`, `NotNull`, `IsTrue`, `IsFalse` | [`Validator::$implicitRules`](../../../vendor/laravel/framework/src/Illuminate/Validation/Validator.php) | done | — | [semantics](#the-implicit-set-is-matched-by-instanceof-where-laravels-is-matched-by-name) |
| [`ImplicitConstraint`](../../../packages/schemas/src/Validation/Contracts/ImplicitConstraint.php) — the consumer opt-in | [`ImplicitRule`](../../../vendor/laravel/framework/src/Illuminate/Contracts/Validation/ImplicitRule.php) | done | — | [semantics](#the-marker-interface-mirrors-the-mechanism-laravel-deprecated-not-the-one-it-replaced-it-with) |
| A present-but-`null` value running every constraint | [`presentOrRuleIsImplicit()`'s second branch](../../../vendor/laravel/framework/src/Illuminate/Validation/Validator.php) | done | — | [semantics](#null-is-deliberately-not-gated-and-neither-is-anything-blank-that-is-not-a-string) |
| `passesOptionalCheck()` / `isNotNullIfMarkedAsNullable()` / `hasNotFailedPreviousRuleIfPresenceRule()` — `isValidatable()`'s other three gates | [`Validator::isValidatable()`](../../../vendor/laravel/framework/src/Illuminate/Validation/Validator.php) | skipped | — | [diverges](#only-the-presence-gate-of-laravels-four-validatable-checks-is-ported) |

## Semantics

### The implicit set is matched by instanceof, where Laravel's is matched by name

Laravel's `isImplicit()` is `$rule instanceof ImplicitRule || in_array($rule, $this->implicitRules)` — an interface test for object rules, an **exact string match** for the twenty-four built-in rule names it cannot modify.
The port's `isImplicit()` is `instanceof` for both halves, so a consumer's `final class RequiredHandle extends NotBlank {}` stays implicit.

That is a translation, not a loosening.
Laravel's rules are *strings*: exact matching is the only test available to it, and the type hierarchy that would make `instanceof` meaningful does not exist there.
Here it does, and a subclass of `NotBlank` genuinely is a member of the family the list names — losing that silently is the same failure mode the [`IsTrue` / `IsFalse` inclusion](#istrue-and-isfalse-are-in-the-set-because-the-runner-validates-raw-state) guards against.

It is deliberately **not** the rule [`applyValidationMessages()` uses](../forms/can-be-validated.md#validation-messages-apply-after-the-constraints-are-built), which matches on the exact class and says so — and the two are not in tension.
There, the class is a **user-supplied key**, so blanketing subclasses would apply a message the caller never asked for.
Here, membership is a fixed taxonomy the consumer never keys into, and Symfony subclasses none of the four (checked: nothing in `vendor/symfony/validator` extends `NotBlank`, `NotNull`, `IsTrue` or `IsFalse`, and none of the four extends another), so `instanceof` can only ever admit a class someone wrote on purpose.

### The marker interface mirrors the mechanism Laravel deprecated, not the one it replaced it with

Its first implementor is forms' [`RequiredValue`](../forms/can-be-validated.md#required-carries-laravels-blank-set-not-symfonys), the constraint `required()` builds: it is not a `NotBlank` subclass, so nothing in the list above would have matched it and the gate would have dropped the one constraint whose whole job is to reject the blank string.

`ImplicitConstraint` is a bare marker, and Laravel's user-facing opt-in is not: a `ValidationRule` consumer writes `public $implicit = true;` on the rule, and [`InvokableValidationRule::make()`](../../../vendor/laravel/framework/src/Illuminate/Validation/InvokableValidationRule.php) reads that property and wraps the rule in an anonymous class implementing the **deprecated** `ImplicitRule`.
So the interface is still what `isImplicit()` tests; only the way a consumer reaches it differs.

The port mirrors the interface rather than the property, because a Symfony `Constraint` is a public-property data object whose properties *are* its options: a `public bool $implicit` would join the option namespace the [message post-pass](../forms/can-be-validated.md#validation-messages-apply-after-the-constraints-are-built) reflects over, and would be settable per instance, where implicitness is a fact about the constraint class.
A marker interface is also the codebase's shape for exactly this (`Contracts\StaticInstance`, `Contracts\ScalableIcon`).

### IsTrue and IsFalse are in the set because the runner validates raw state

`Checkbox` and `Toggle` cast to a real `bool`, which makes their `accepted()` / `declined()` constraints look unreachable from a blank string — so the two look like list-completeness for its own sake.
They are not.
[Validation reads the raw host value](../forms/checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live), not `getState()`, and no state cast stands on that path; `IsTrueValidator` and `IsFalseValidator` return early only on `null` and on their own accepted values, never on `''`, so a bag holding `''` for a checkbox path — a tampered live payload, or server code assigning into `$data` — reaches them as `''` and violates.

Leaving them out would make `accepted()` quietly pass on exactly the input it exists to reject, which is why `CheckboxTest`'s `keeps accepted() biting on a raw blank state the presence gate would otherwise skip` pins it.
The general rule stands on its own regardless — matching Laravel's `$implicitRules` is the criterion, and per-constraint reachability is not — but here the two agree.

### null is deliberately not gated, and neither is anything blank that is not a string

Laravel's second branch treats a present-but-`null` value as present and runs the rules; the port does the same, and the test is `is_string()` first for exactly that reason.
Nothing is lost by it — every constraint in the ported subset already returns without a violation on `null`, which is what [`nullable()`'s promise](../forms/can-be-validated.md#nullable-removes-the-required-constraint-and-nothing-else) rests on — and gating `null` would change behaviour nobody asked to change: `NotNull` is in the implicit set, so it would survive, but a consumer's own non-implicit constraint that *wants* to see `null` would stop being run.

The house helper [`blank()`](../support/helpers.md) is deliberately **not** used here, despite being the codebase's shape for this test.
It is broader: it counts an empty array and an empty `Countable` as blank, so a `Count(min: 1)` on an empty collection — a constraint about presence in every sense that matters, and one Laravel runs — would be filtered out.
The gate needs the narrow string test, not the general one, and that is pinned by a runner test rather than left to reading.

### The gate is a no-op for Select, which already guarded itself

A `Select` with a placeholder submits `''`, and `Choice` is not implicit, so the obvious reading is that the gate either closes a latent bug (an optional select failing its own placeholder) or silently loosens select validation.
It does **neither**, and the reason is upstream: [`Select::buildValidationConstraints()`](../../../packages/forms/src/Components/Select.php) returns before building any `Choice` when `blank($this->getRawState())`, so a blank select never emits a `Choice` for the gate to filter.
The guard reads the raw state, so no state cast changes the answer: `''` is blank whatever the select casts it to.
If that guard is ever relaxed, the gate is what keeps an optional select from failing its own placeholder.
Pinned by `SelectTest`'s `builds no Choice constraint for the empty string a placeholder submits, so the presence gate finds nothing to skip`, which asserts the empty constraint list rather than just the absent violation — the two are indistinguishable from the error bag alone.

### What each Symfony validator does with the empty string, and why the gate is not defensive

The table is what each validator does with `''` **if reached**; the gate means a blank string reaches only the first row.
It is the evidence for *which* constraints the gate is load-bearing for — without it, a reader cannot tell whether the filter does real work or is a belt-and-braces guard, and the answer is not guessable from the constraint names.

*Not run* means the validator returned before comparing anything; *runs, passes* means it compared `''` and found nothing wrong.

| Constraint | `null` | `''` |
| --- | --- | --- |
| `NotBlank` (implicit — still reached) | violates | violates |
| `Email`, `Regex`, `Url` | not run | not run |
| `Ip`, `Uuid`, `Ulid`, `Json`, `CssColor`, `MacAddress` | not run | not run |
| `Type('numeric')`, `Length(min:)`, `GreaterThanOrEqual`, `GreaterThan` | not run | **violates** |
| `Choice` | not run | **violates** ("not a valid choice") |
| `DivisibleBy`, `Count`, `All` | not run | **violates** (a *type* message) |
| `Type('string')`, `Length(max:)`, `LessThanOrEqual`, `LessThan` | not run | runs, passes |

`EmailValidator`, `RegexValidator` and `UrlValidator` open with `if (null === $value || '' === $value) { return; }`, and so do the six format validators on the third row — which is why the whole [format sugar](../forms/can-be-validated.md) needed no gating of its own.
`TypeValidator`, `LengthValidator`, `ChoiceValidator` and every `AbstractComparisonValidator` subclass return on `null` only, so `''` reached their comparison.

The `DivisibleBy`/`Count`/`All` row is a different mechanism again and worth naming, because "violates" understates it: their validators **throw** `UnexpectedValueException` on a value of the wrong shape, and the runner turns that into a hardcoded type violation — "This value should be of type numeric.", "…of type array|\Countable.", "…of type iterable." respectively.
So without the gate, an untouched optional field would report a type error naming an internal PHP type at the user.
That is the sharpest case for the filter in this table.

The comparison rows line up by direction — a **minimum** violates on `''` and a **maximum** does not.
The `Email` / `Regex` / `Url` row lands on the no-violation side for an unrelated reason, having opted out of blank input altogether rather than compared it, and reading the two as one mechanism is what makes the last row look like a coincidence.
It is not: `mb_strlen('')` is `0`, which clears any `max`, and `''` compared against a stringified bound sorts before every non-empty one, so `LessThanOrEqual` passes for **any** bound — `10`, `0`, `-5`, `1.5` alike — while `GreaterThanOrEqual` violates for all four.

Two things the table is **not**.
It is not the `"abc"` behaviour of a comparison arm, which is a separate consequence of comparing an unconverted string ([there](../forms/text-input.md#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max)) and is untouched by the gate — `"abc"` is present, so every constraint still runs.
And it is not something a state cast could fix: Laravel does not reach a converted value here, it declines to run the rule at all, which is what the gate does too.

## Divergences

### The gate sits in front of the runner, not inside the validator

Laravel's check lives *inside* the validator, on the rule-by-rule loop (`isValidatable()` is consulted per attribute-rule pair).
Symfony's `ValidatorInterface` has no equivalent seam — the decision to skip is each `ConstraintValidator`'s own, which is precisely why they disagree — and wrapping or decorating the container's validator would change behaviour for every Symfony consumer in the application, not just for schema state.

So the port filters the **constraint list** instead, in the one place that owns the per-path run.
`getValidationConstraints()` returns `array<string, list<Constraint>>` and the component is already out of scope by then, which is not a loss: Laravel's gate works from the same three things (`$rule`, `$attribute`, `$value`), so nothing the check needs is missing.

The alternative — a blank guard repeated inside each field's [`buildValidationConstraints()`](../forms/can-be-validated.md#the-subclass-seam-is-a-protected-builder-not-the-public-getter) — is **rejected**.
It is the shape `Select` happens to have (for its own reason: it cannot enumerate choices for a value that is not there), and generalising it would mean every current and future arm re-deriving the same rule, with the failure mode of forgetting being a wrong violation on a user's screen.
One filter in front of the runner is also what makes a consumer's escape-hatch `->constraint(new Regex('/…/'))` gate without the consumer doing anything.

Permanent while validation runs per state path.
The [record-sourced pass](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) gets **no** equivalent, by decision.
It validates a *mapped* object: Symfony walks the entity's own metadata property by property, and a `string` property written the `''` it was given does reach an entity-level `#[Assert\Length(min: 3)]`.
But that is the entity's own contract about its own persisted value, not a field's contract about what a user typed — an empty column failing `Length(min: 3)` is the correct answer there, where an untouched optional input failing it is not.
The two passes want opposite defaults, and a gate in front of the record pass would silently disable half of every entity's constraints.
Reverses only if a consumer shows a case where the entity pass should skip a blank the field pass already let through.

### The blank test trims multibyte where Laravel trims bytes

Laravel's branch is `is_string($value) && trim($value) === ''`; the port's is `mb_trim($value) === ''`.
So a field holding only a non-breaking space — what a paste from a rendered page routinely leaves behind — gates here and does not in Laravel.

[`blank()`](../support/helpers.md) trims with `mb_trim` for the same reason and records it as a divergence, and a gate that disagreed with the codebase's own blank test would be the surprising thing.
Permanent.

### Only the presence gate of Laravel's four validatable checks is ported

`isValidatable()` is four checks anded together, and the other three are **skipped** rather than deferred:

- `passesOptionalCheck()` implements the `sometimes` rule — a rule vocabulary with no port and no planned one, since a field that should not validate is spelled with a condition here.
- `isNotNullIfMarkedAsNullable()` short-circuits the remaining rules once `nullable` is present and the value is null.
  It has no work to do: the port's [`nullable()`](../forms/can-be-validated.md#nullable-removes-the-required-constraint-and-nothing-else) is the *absence* of the required constraint, not a rule in the list, and every ported constraint already returns on `null`.
- `hasNotFailedPreviousRuleIfPresenceRule()` — despite the name, not a presence check at all: its body is `in_array($rule, ['Unique', 'Exists']) ? ! $this->messages->has($attribute) : true`, so it holds back the two **database** rules once the attribute already has any message, "to avoid possible database type comparison errors" in its own words.
  Against the [data-layer family](../forms/can-be-validated.md) the answer is that nothing equivalent is needed: `UniqueValue` and `ExistingValue` are not implicit, so the gate strips them from a blank string before the runner sees them, and their validators return on `null` — a blank required field never reaches the database.
  What Laravel's check additionally buys is suppressing the probe once an *earlier* rule on the same attribute failed; here every constraint on a path runs, and a probe of a value that will be rejected anyway costs one indexed read.
  Skipped.
