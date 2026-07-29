# CanBeValidated (forms)

[`Sylament\Forms\Components\Concerns\CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php)
— the field-side half of the validation vertical, composed on
[`Field`](field.md). It declares what a field requires of its state and turns
that into `Symfony\Component\Validator\Constraint` objects: the schema collects
them keyed by absolute state path and the host runs `ValidatorInterface` over
them, all tracked in
[`schemas/can-be-validated.md`](../schemas/can-be-validated.md). `Field`
implements the schemas-side component contract
[`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php)
through this trait.

Filament's trait is one of the largest in the port — some eighty methods, almost
all of them Laravel rule-string sugar. This port lands the subset the M1 · 1
validation vertical needs (`required()` / `nullable()`, the length constraints
via [`CanBeLengthConstrained`](can-be-length-constrained.md), `TextInput`'s
`email()` / `numeric()` arms in [`text-input.md`](text-input.md),
`validationMessages()`) plus a `constraint()` / `constraints()` escape hatch that
takes any Symfony `Constraint`, so nothing in the remainder is *unreachable* — it
is only unsugared. That is why the rows below are mostly **todo** rather than
**skipped**: each is a constraint arm someone can add without new machinery.

The rule → constraint vocabulary is a rename, recorded in the
[rename table](../renames.md): `rule()` / `rules()` → `constraint()` /
`constraints()`, `getValidationRules()` → `getValidationConstraints()`,
`dehydrateValidationRules()` → `dehydrateValidationConstraints()`. The payload
changes ecosystem (Laravel rule strings → Symfony `Constraint` objects), so the
rename makes the migration break loud and searchable instead of type-erroring on
a grep hit.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php)
  — the trait
- [`forms/src/Components/Contracts/HasValidationRules.php`](../../../vendor/filament/forms/src/Components/Contracts/HasValidationRules.php)
  — the contract it satisfies, [relocated into schemas](../schemas/can-be-validated.md#the-component-contract-is-relocated-from-forms-into-schemas)
- [`forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php)
  — the sibling concern for nested-field rules (its own [Features row](README.md#features), unported)

## Surface

Every point of Filament's trait, grouped where a family shares one fate.
_Blocked by_ names what has to land first. _Detail_ carries a **detail** link
(the write-up in [Details](#details), deleted once the row is **done**), a
**semantics** link (a permanent note in [Semantics](#semantics)), and/or a
**diverges** link (the permanent note in [Divergences](#divergences)), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`required()` / `isRequired()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::required()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | — |
| [`nullable()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::nullable()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#nullable-removes-the-notblank-and-nothing-else) |
| [`constraint()` / `constraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`rule()` / `rules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#rule-and-rules-become-constraint-and-constraints-over-symfony-constraint-objects) |
| [`getValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`getValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-condition-pair-walk-drops-filaments-numeric-key-rule-shorthand) |
| [`dehydrateValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`dehydrateValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | — |
| [`validationMessages()` / `getValidationMessages()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#validation-messages-bake-into-the-constraints-at-construction) |
| Length constraints ([`CanBeLengthConstrained`](can-be-length-constrained.md)) | [`getLengthValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| `email()` / `numeric()` arms ([`TextInput`](text-input.md)) | [`TextInput::email()` / `numeric()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [detail](text-input.md) |
| `dehydrateValidationMessages()` / `dehydrateValidationAttributes()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#validation-messages-bake-into-the-constraints-at-construction) |
| `validationAttribute()` / `getValidationAttribute()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#validationattribute-is-not-ported) |
| `showAllValidationMessages()` / `shouldShowAllValidationMessages()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| `allowHtmlValidationMessages()` / `areHtmlValidationMessagesAllowed()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#html-validation-messages-are-not-ported) |
| `getRequiredValidationRule()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#the-rule-string-builder-helpers-have-no-port) |
| `dateComparisonRule()` / `fieldComparisonRule()` / `multiFieldComparisonRule()` / `multiFieldValueComparisonRule()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#the-rule-string-builder-helpers-have-no-port) |
| Format sugar (`alpha`, `alphaDash`, `alphaNum`, `ascii`, `string`, `hexColor`, `json`, `macAddress`, `ip` / `ipv4` / `ipv6`, `uuid`, `ulid`, `activeUrl`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `regex()` / `notRegex()` / `getRegexPattern()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `in()` / `notIn()` / `getInValidationRule()` / `getInValidationRuleValues()` / `hasInValidationOnMultipleValues()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | `HasOptions` / `HasEnum` | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `startsWith()` / `endsWith()` / `doesntStartWith()` / `doesntEndWith()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `filled()` / `multipleOf()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| Comparison family (`after` / `afterOrEqual` / `before` / `beforeOrEqual`, `gt` / `gte` / `lt` / `lte`, `same` / `different`, `confirmed`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | cross-path validation context | [detail](#cross-field-rules-need-a-validation-context-the-per-path-runner-does-not-build) |
| Conditional-required family (`requiredIf*` / `requiredUnless` / `requiredWith*` / `requiredWithout*`, `prohibited` / `prohibitedIf` / `prohibitedUnless` / `prohibits`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | cross-path validation context | [detail](#cross-field-rules-need-a-validation-context-the-per-path-runner-does-not-build) |
| Data-layer family (`unique` / `exists` / `scopedUnique` / `scopedExists` / `uniqueValidationIgnoresRecordByDefault`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | blocked | Doctrine query layer | [detail](#the-data-layer-rules-wait-on-a-doctrine-query-layer) |
| `distinct()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | blocked | `Repeater` | [detail](#the-data-layer-rules-wait-on-a-doctrine-query-layer) |
| `mutateStateForValidation()` / `mutatesStateForValidation()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | blocked | schemas state-mutation layer | [detail](#the-validation-state-mutation-hooks-wait-on-schemas) |

Coverage: the trait end to end (required default, `NotBlank` build, the
`nullable()` inversion, closure conditions, the escape hatch behind true and
false conditions, closure constraints returning a single constraint or an array,
message baking, the length hand-off, and every `dehydrateValidationConstraints()`
branch) by
[`CanBeValidatedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeValidatedTest.php);
the `isMarkedAsRequired()` fallback by
[`FieldTest`](../../../packages/forms/tests/Unit/Components/FieldTest.php); the
`TextInput` arms and their ordering by
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php);
the end-to-end path — a live host, a real validator, error messages read back per
field and rendered into the shipped templates — by
[`FieldErrorsTest`](../../../packages/forms/tests/Integration/Components/FieldErrorsTest.php).

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**.

### The unsugared rule families are reachable through constraint()

The format, regex, `in`/`notIn`, `startsWith`/`endsWith`, `filled` and
`multipleOf` families are all single-constraint sugar: each is one Symfony
constraint (`Regex`, `Choice`, `Ip`, `Uuid`, `Ulid`, `Json`, `Type`,
`DivisibleBy`, …) that a user reaches today with
`->constraint(new Regex('/…/'))`. None needs machinery that is missing, so none
is **blocked** — they are simply not sugared yet. **Next step (per family):** add
the setter and a flag, append the constraint in `getValidationConstraints()`
behind that flag, and bake the matching `validationMessages()` key, exactly as
`TextInput`'s `email()` / `numeric()` arms do. `in()` / `notIn()` additionally
want the `HasOptions` / `HasEnum` concerns Filament reads the value list from,
which is why they name a blocker while staying **todo**.

### Cross-field rules need a validation context the per-path runner does not build

The comparison and conditional-required families all validate one path *against
another* (`gt:other`, `requiredIf:status,published`, `confirmed`). The host runs
[`ValidatorInterface::validate($value, $constraints)`](../schemas/can-be-validated.md#validateschema-runs-symfonys-validator-per-state-path-instead-of-livewires-whole-component-validate)
per path over a bare value, so Symfony's `AbstractComparison`-family constraints
— which resolve `propertyPath` against the *object under validation* — have
nothing to resolve against. **Next step:** validate the schema's state array as
one object (a `Collection` constraint keyed by path, or a per-path context
seeded with the root), then the comparison constraints and a
`When`-based conditional-required arm both drop in. Until then the escape hatch
covers the case with a closure constraint reading sibling state through
`$component->getContainer()`.

### The data-layer rules wait on a Doctrine query layer

Filament's `unique()` / `exists()` (and their `scoped*` query-builder variants)
take an Eloquent table/model, and `uniqueValidationIgnoresRecordByDefault()` /
`distinct()` lean on the panel's record binding and `Repeater` item context.
The record binding half of that now exists — a schema binds an entity and a field
can reach it (`$record`, `getRecordClass()`, see
[belongs-to-record.md](../schemas/belongs-to-record.md)), so
`uniqueValidationIgnoresRecordByDefault()` has an id to ignore. What is still
missing is the **query** layer: a repository/`QueryBuilder` seam a field-level
constraint can run a lookup through, plus the entity-level route for Symfony's
`UniqueEntity`, which needs the managed object rather than a scalar path value
(decided direction:
[validate the mapped object](../schemas/can-be-validated.md#record-sourced-constraints-validate-the-mapped-object-direction-decided)).
**Blocked** on that Doctrine query layer (and, for `distinct()`, on `Repeater`).
Until then the escape hatch covers the case: a closure constraint that queries
the repository itself.

### The validation-state mutation hooks wait on schemas

`mutateStateForValidation()` / `mutatesStateForValidation()` are the field half of
the schemas `mutateStateForValidation` / `pruneStateToMatchKeys` pipeline, which
is [not ported](../schemas/schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)
— the host validates raw client state. **Blocked** on that schemas layer;
`Textarea`'s trim/strip state casts land in the same change
([textarea.md](textarea.md#the-state-mutation-surface-is-blocked-on-schemas)).

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### nullable() removes the NotBlank and nothing else

`nullable()` is Filament's inversion verbatim — it calls
`required(fn () => ! evaluate($condition))`, so it is a *setter for the same
flag*, not an independent one, and a later `required()` overwrites it. There is
deliberately no Symfony counterpart constraint to add: every constraint in the
ported subset (`Length`, `Email`, `Type`) returns without a violation for `null`
and `''` by Symfony's own validator semantics, so "optional" is the absence of
`NotBlank` — matching Laravel's implicit-rule skip, which is what made Filament's
`nullable()` a pure inverse of `required()` in the first place.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### rule() and rules() become constraint() and constraints() over Symfony Constraint objects

`rule(mixed $rule, bool | Closure $condition)` / `rules(string | array | Closure
$rules, …)` become
[`constraint(Constraint|Closure $constraint, bool|Closure $condition)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php)
/ `constraints(array|Closure $constraints, …)`. Two changes ride together. The
**payload** is a Symfony `Constraint` instance (or a closure returning one or an
array of them), never a rule string — so Laravel's pipe syntax (`'required|max:255'`)
and its string-keyed rule names have no form here, and `constraints()` drops
Filament's `string` branch entirely. The **name** follows the payload, per the
[rename criterion](../renames.md): a preserved `rule()` would keep every
migrating call site compiling while failing at runtime on the argument type,
where the rename turns the same migration into a findable grep. The pair
semantics are otherwise Filament-exact — each entry is `[$constraint,
$condition]`, the condition is evaluated first, and a closure payload may return
either a single constraint or an array that flattens in.

### Validation messages bake into the constraints at construction

Filament threads three parallel arrays into Livewire's `validate()` — rules,
messages, attributes — with `dehydrateValidationMessages()` /
`dehydrateValidationAttributes()` collecting the latter two by state path.
Symfony carries the message *on the constraint*, so `validationMessages()` maps
Filament's simple keys onto constraint message options at construction time
(`required` → `NotBlank::$message`, `max` → `Length::$maxMessage`, `min` →
`Length::$minMessage`, `size` → `Length::$exactMessage`, `email` →
`Email::$message`, `numeric` → `Type::$message`) and the two dehydrators have
nothing left to collect — **skipped**, with the schemas-side half of the same
removal recorded
[there](../schemas/can-be-validated.md#schema-level-validation-messages-and-attributes-are-not-ported).
`getValidationMessages()` itself is Filament-exact: values evaluate through
`evaluate()` and the result is `array_filter`ed, so a message resolving to `null`
or `''` falls back to the constraint's own default. The visible cost: a key set
through `validationMessages()` reaches only the *sugared* arms that look it up —
an escape-hatch constraint carries its own `message:` option instead, where
Filament's keyed messages apply to any rule. Permanent while Symfony owns the
message.

### The condition-pair walk drops Filament's numeric-key rule shorthand

Filament's `getValidationRules()` walks `[$rule, $condition]` pairs and has to
handle a numeric-keyed shorthand (a bare rule string arriving where a pair was
expected) plus `getInValidationRule()`'s `In`/`Enum` special case. With
`Constraint` objects there is no shorthand to disambiguate: the walk is
condition-then-evaluate-then-append, and the composition order is otherwise
Filament's — `NotBlank` first, then the
[length constraints](can-be-length-constrained.md) when the field implements
`Contracts\CanBeLengthConstrained`, then the escape-hatch pairs in declaration
order, then any subclass arms appended by a `getValidationConstraints()` override
([`TextInput`](text-input.md)). Permanent.

### validationAttribute() is not ported

Filament's `validationAttribute()` / `getValidationAttribute()` supply the field
name Laravel interpolates into `:attribute` in a rule message, defaulting to the
field's label. Symfony's violation messages carry no field-name placeholder —
`NotBlank`'s default is "This value should not be blank." with no slot to fill —
and the message that reaches the user is rendered *next to* its field by the
`FieldWrapper`, so the name is redundant. **Skipped**; a user who wants the name
in the text writes it into `validationMessages()`. Reverses only if a
constraint-message translation layer with an attribute placeholder is added.

### HTML validation messages are not ported

`allowHtmlValidationMessages()` opts a field's messages out of Blade escaping.
The port's messages are plain strings escaped by Twig, and
[`getErrorMessages()`](field.md) returns `list<string>`; opting in would mean
typing the list `list<string|Markup>` and threading a per-field flag into the
wrapper template for no consumer. **Skipped** — a `Markup` message would be the
port's shape if it is ever wanted, not a boolean flag.

### The rule-string builder helpers have no port

`getRequiredValidationRule()` (returns the literal `'required'` for subclasses to
swap) and the four `*ComparisonRule()` builders exist to *assemble Laravel rule
strings* — `"gt:{$statePath}"`, `"after:{$date}"`. They are string plumbing for
an engine this port does not use; the behaviour they enable is tracked on the
comparison and conditional-required rows above, which will build `Constraint`
objects directly. **Skipped** as API surface, permanently.
