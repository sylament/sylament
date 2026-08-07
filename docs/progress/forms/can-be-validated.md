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
| [`required()` / `isRequired()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::required()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) |
| [`required(null)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — undeclare, falling back to derivation | — | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) |
| [`isRequiredByRecord()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) — the derivation seam, overridden to `false` on [`Checkbox`](checkbox.md) and [`Toggle`](toggle.md) | — | done | — | [diverges](#requirement-is-derived-from-the-records-write-side-types) · [semantics](#a-boolean-field-opts-out-of-derivation-but-selectboolean-does-not) |
| [`getRequiredConstraint()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`getRequiredValidationRule()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-required-constraint-is-swappable-per-field) |
| [`nullable()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated::nullable()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#nullable-removes-the-notblank-and-nothing-else) |
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
| `dateComparisonRule()` / `fieldComparisonRule()` / `multiFieldComparisonRule()` / `multiFieldValueComparisonRule()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#the-comparison-rule-string-builders-have-no-port) |
| Format sugar (`alpha`, `alphaDash`, `alphaNum`, `ascii`, `string`, `hexColor`, `json`, `macAddress`, `ip` / `ipv4` / `ipv6`, `uuid`, `ulid`, `activeUrl`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `regex()` / `notRegex()` / `getRegexPattern()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `in()` / `notIn()` / `getInValidationRule()` / `getInValidationRuleValues()` / `hasInValidationOnMultipleValues()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | `HasOptions` / `HasEnum` | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `startsWith()` / `endsWith()` / `doesntStartWith()` / `doesntEndWith()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| `filled()` / `multipleOf()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | — | [detail](#the-unsugared-rule-families-are-reachable-through-constraint) |
| Comparison family (`after` / `afterOrEqual` / `before` / `beforeOrEqual`, `gt` / `gte` / `lt` / `lte`, `same` / `different`, `confirmed`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | cross-path validation context | [detail](#cross-field-rules-need-a-validation-context-the-per-path-runner-does-not-build) |
| Conditional-required family (`requiredIf*` / `requiredUnless` / `requiredWith*` / `requiredWithout*`, `prohibited` / `prohibitedIf` / `prohibitedUnless` / `prohibits`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | todo | cross-path validation context | [detail](#cross-field-rules-need-a-validation-context-the-per-path-runner-does-not-build) |
| Data-layer family (`unique` / `exists` / `scopedUnique` / `scopedExists` / `uniqueValidationIgnoresRecordByDefault`) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | blocked | Doctrine query layer | [detail](#the-data-layer-rules-wait-on-a-doctrine-query-layer) |
| `distinct()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | blocked | cross-path validation context | [detail](#cross-field-rules-need-a-validation-context-the-per-path-runner-does-not-build) |
| `mutateStateForValidation()` / `mutatesStateForValidation()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | blocked | schemas state-mutation layer | [detail](#the-validation-state-mutation-hooks-wait-on-schemas) |

Coverage: the trait end to end (required default, `NotBlank` build, the
`nullable()` inversion, closure conditions, the derivation set — required from a
non-nullable property, through a nested path, `NotBlank` built from a derived
requirement, not-required from a nullable one, from a collection one, from a path
the record does not declare, from no bound record and from a field no schema
contains, plus explicit `required(false)` and `nullable()` each beating the
derived value — the escape hatch behind true and
false conditions, closure constraints returning a single constraint or an array,
the message post-pass — string and option-map forms, closure leaves, blank
fallback, exact-class matching, clone-on-write, the ignored-key arm and all three
throw arms — the length hand-off, and every `dehydrateValidationConstraints()`
branch) by
[`CanBeValidatedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeValidatedTest.php);
the `getRequiredConstraint()` override and the boolean derivation opt-out by
[`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php)
and [`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php),
with [`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php)
pinning that `boolean()` still derives; the wiring and branch propagation — the
container reader reaching a field built by `make()`, requirement derived from the
record bound on the root schema, suppressed under a blank nullable branch,
resumed once the branch is filled, applied when the branch is not declared
nullable, unmoved by a path-less `nullWhenBlank()` container (asserted against two
values of an unrelated sibling field, so a walk reading the whole bag fails it),
and an explicit `required()` staying strict inside a blank branch — by
[`Integration/CanBeValidatedTest`](../../../packages/forms/tests/Integration/Components/Concerns/CanBeValidatedTest.php);
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
the setter and a flag, then append the constraint in `buildValidationConstraints()`
behind that flag, exactly as `TextInput`'s `email()` / `numeric()` arms do —
messages need no per-arm work, since the
[post-pass](#validation-messages-apply-after-the-constraints-are-built) reaches
any constraint by class. `regex()` carries one extra decision the others do not:
`TextInput`'s `tel()` already appends a `Regex` of its own, and a peer `regex()`
arm makes `->tel()->regex('/…/')` enforce
[both patterns](text-input.md#the-type-sugar-constraint-arms-live-in-an-override-not-in-the-setters)
where Filament's single `$regexPattern` keeps only the later call. `in()` / `notIn()` additionally
want the `HasOptions` / `HasEnum` concerns Filament reads the value list from,
which is why they name a blocker while staying **todo**.

### Cross-field rules need a validation context the per-path runner does not build

The comparison and conditional-required families all validate one path *against
another* (`gt:other`, `requiredIf:status,published`, `confirmed`). The host runs
[`ValidatorInterface::validate($value, $constraints)`](../schemas/can-be-validated.md#validateschema-runs-symfonys-validator-per-state-path-instead-of-livewires-whole-component-validate)
per path over a bare value, so Symfony's `AbstractComparison`-family constraints
— which resolve `propertyPath` against the *object under validation* — have
nothing to resolve against. The literal-bound arm of that same family is
unaffected and already ships:
[`maxValue()` / `minValue()`](text-input.md#the-value-bounds-are-comparison-constraints-not-laravels-min-and-max)
pass a `value`, which needs no context. **Next step:** validate the schema's state array as
one object (a `Collection` constraint keyed by path, or a per-path context
seeded with the root), then the comparison constraints and a
`When`-based conditional-required arm both drop in. `distinct()` waits on the
same context rather than on the [`Repeater`](repeater.md) it names: the repeater
ships, and its items already validate per path — what `distinct()` needs is to
compare one item's value against its siblings', which no per-path run can see
(`Assert\Unique(fields:)` over the repeater's own path is the likely landing,
at the cost of item-level error placement). Until then the escape hatch
covers the case with a closure constraint reading sibling state through
`$component->getContainer()`.

### The data-layer rules wait on a Doctrine query layer

Filament's `unique()` / `exists()` (and their `scoped*` query-builder variants)
take an Eloquent table/model, and `uniqueValidationIgnoresRecordByDefault()` leans on the panel's record binding.
The record binding half of that now exists — a schema binds an entity and a field
can reach it (`$record`, `getRecordClass()`, see
[belongs-to-record.md](../schemas/belongs-to-record.md)), so
`uniqueValidationIgnoresRecordByDefault()` has an id to ignore. What is still
missing is the **query** layer: a repository/`QueryBuilder` seam a field-level
constraint can run a lookup through, plus the entity-level route for Symfony's
`UniqueEntity`, which needs the managed object rather than a scalar path value
(decided direction:
[validate the mapped object](../schemas/can-be-validated.md#record-sourced-constraints-validate-the-mapped-object-direction-decided)).
**Blocked** on that Doctrine query layer. Until then the escape hatch covers the case: a closure constraint that queries
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

### A boolean field opts out of derivation, but `Select::boolean()` does not

`Checkbox` and `Toggle` override `isRequiredByRecord()` to a constant `false`,
and `Select::boolean()` — which also binds to a `bool` column — deliberately does
not. The asymmetry looks like an oversight and is the whole point: the question
the override answers is a **field** question, not a record one.

The predicate that matters is *does this field's blank state dehydrate to
something the target rejects*. Read off the state casts:

| field | cast | blank state dehydrates to |
| --- | --- | --- |
| `Checkbox` / `Toggle` | `BooleanStateCast(isNullable: false)` | `false` — never null |
| `Select::boolean()` | `BooleanStateCast(isStoredAsInt: true)`, `isNullable` defaulting **true** | the placeholder dehydrates to **null** |

So a non-nullable `bool` target always accepts what a checkbox or a toggle
produces, and deriving requirement there would silently mean *"must be ticked"*:
`NotBlank` violates on `false`, and the checkbox template emits a native
`required` attribute, so an unticked box becomes unsubmittable. Only an explicit
[`required()`](toggle.md#required-means-presence-not-truth) may say that. A
`Select::boolean()` placeholder, by contrast, really does dehydrate null onto
`setFeatured(bool)` — the exact crash this derivation exists to prevent — so
suppressing it there would remove the guard rather than a false positive.

This is why the suppression lives on the two field classes rather than on the
target type. A "skip every `bool` target" rule inside `isRequiredByRecord()` was
proposed first and is actively wrong: it cannot see which of three fields bound
to the same column is asking. `Select`'s behaviour is pinned by a regression test
so a future overgeneralisation of the boolean suppression fails loudly.

The same predicate has a branch-side counterpart, added for the same reason and
placed the same way.
[The built-in blank test asks each declared leaf](../schemas/belongs-to-record.md#the-built-in-blank-test-asks-the-branchs-own-leaves)
whether its value is blank, and `Checkbox`/`Toggle` override
`isBlankStateValue()` so their resting `false` answers yes. Without it
`filled(false)` held every branch containing a boolean field permanently
non-blank, so the suppression above never engaged there and the branch could
never null. `Select::boolean()` needs no override on this side either, and for
the same reason as the last: its placeholder already dehydrates null, which the
default test reads as blank.

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

### Requirement is derived from the record's write-side types

Filament's `$isRequired` starts at `false`; here it starts at **`null`**, meaning
*never declared*, and `isRequired()` falls through to `isRequiredByRecord()` when
nothing was declared. A field bound to a record path whose write-side type is
non-nullable is required; one bound to a nullable target is not. Explicit
declarations win in both directions and are unchanged — `required()`,
`required(false)` and `nullable()` all beat the record.

**No Filament precedent exists, and the reason is structural rather than a gap in
their API**: Eloquent attributes are untyped, so "may this column accept null?"
is not a question a Filament schema can ask. Their equivalent is three manual
declarations that must be kept in sync with the schema by hand — fields left
un-`required()`, `requiredWith()` on the siblings, `condition:` on the container.

**The class is read in the root frame**, `getRootContainer()->getRecordClass()`,
not from the nearest binding up the chain. Class and path have to name the same
frame: `getRecordStatePath()` is root-relative by
[definition](../schemas/belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it),
and it is the frame `getData()` maps in. Reading the class nearest-first mixed
the two — `Section::make('author')->statePath('author')->record(Author::class)`
asked `describe(Author::class, 'author.name')`, which no type answers, and the
walk then derived **not required** rather than failing. So a component-tier
`record()` binding feeds
[closure injection and mapping-independent reads](../schemas/belongs-to-record.md#the-component-tier-gains-record)
and never derivation; a field that wants a derived requirement is bound through
the schema its path is relative to.

The signature widens to carry it: `required(bool|Closure|null $condition = true)`,
where Filament's is `bool|Closure`. **`required(null)` is a new public arm** and
it is not the same as `required(false)` — it *undeclares*, handing the decision
back to the record, which is the only way to get derivation back after a
conditional call has set the flag. `required(false)` remains a declaration and
still suppresses. Its coverage is the property's own `null` default rather than
an explicit `required(null)` call — the same single assignment and the same
`$declared !== null` branch, exercised by every derivation test above.

Two behaviours make this safe to have on by default, and both are load-bearing:

- **It suppresses itself under a blank nullable branch.**
  `hasBlankNullableBranchAncestor()` walks the container chain, and derivation
  gives up as soon as an ancestor declaring
  [`nullWhenBlank()`](../schemas/belongs-to-record.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written)
  is currently blank. Without it the feature deletes the feature that motivated
  it: `Article::$author` nullable with `Author::$name` non-nullable would make the
  name unconditionally required, so a blank author could never be submitted, so
  the branch could never be nulled. With it, the pair states exactly *"name is
  required if there is an author"* — Filament's `condition:` + `requiredWith()`
  shape, derived instead of hand-written.

  An ancestor is only a branch if it carries a **record state path**, and that
  gate is not cosmetic symmetry with the mapper's own walk. `getRawState()` on a
  path-less component falls through to its container's path, so before the gate a
  path-less `nullWhenBlank()` container handed `shouldNullBranch()` the whole
  form's raw bag — and one field's requirement moved with unrelated fields
  elsewhere on the page.
  [The mapper now refuses that declaration outright](../schemas/belongs-to-record.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored);
  the two walks agree on what a branch is.
- **It answers `false` wherever it cannot be sure.** No container, no bound record
  class, no record-relative path, a path the record does not declare, or a
  collection-typed target — each returns false rather than guessing. The
  collection skip uses the wide `isCollection` flag, since a blank value says
  nothing about whether an `array` target accepts it.

**Accepted cost, stated because it is visible on every existing form:** asterisks
and native `required` attributes now appear wherever a bound target is
non-nullable, without anyone writing `required()`. That is the intended layering
fix — [a blank non-nullable leaf becomes a field error instead of a mapper
crash](../schemas/can-be-validated.md#record-sourced-constraints-validate-the-mapped-object-direction-decided)
— but it does change existing forms, and a field that should stay optional against
a non-nullable target now has to say `nullable()` out loud.

One rough edge rides along, pre-existing but with a larger population now: a
`dehydrated(false)` leaf still validates, so it can derive a constraint for a
write that never happens. Harmless where the field is genuinely page-only and
filled; combined with a nullable branch it is the sharp end of
[the two-bag hazard](../schemas/belongs-to-record.md#a-nullwhenblank-closure-sees-two-different-bags).

The reader itself arrives by `#[Required]` setter with a `new RecordTypeReader()`
fallback, the `EvaluatesClosures`/`CanAccessProperties` idiom, so a field built
with `new` outside DI still derives. `getRecordTypeReader()` is public rather than
private, matching both sibling precedents.

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

### Validation messages apply after the constraints are built

Filament threads three parallel arrays into Livewire's `validate()` — rules,
messages, attributes — with `dehydrateValidationMessages()` /
`dehydrateValidationAttributes()` collecting the latter two by state path.
Symfony carries the message *on the constraint*, so the two dehydrators have
nothing left to collect — **skipped**, with the schemas-side half of the same
removal recorded
[there](../schemas/can-be-validated.md#schema-level-validation-messages-and-attributes-are-not-ported).

Two shape changes ride along, and together they make the port's messages reach
*more* than Filament's keyed messages do.

**Keys are constraint class-strings, not Laravel rule names.** Filament keys by
rule name (`required`, `max`, `email`); a port of that vocabulary would be a set
of strings with nothing behind them in Symfony, discoverable only by grepping our
source. `validationMessages()` instead takes
`array<class-string<Constraint>, string|Closure|array<string, string|Closure>>` —
a string value sets the constraint's `message` option, an option map sets named
ones for the constraints carrying several (`Length` has `maxMessage` /
`minMessage` / `exactMessage` / `charsetMessage` and no plain `message`; `Choice`
has four). The key is IDE-completable and the option names are the constraint's
own properties, so nothing has to be memorised. Recorded in the
[rename table](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary).

**Messages apply in one post-pass, not at construction.** `getValidationConstraints()`
builds the list through [`buildValidationConstraints()`](#the-subclass-seam-is-a-protected-builder-not-the-public-getter)
and then hands it to `applyValidationMessages()`, which matches each constraint on
its **exact** class (never `instanceof` — a key for a parent should not blanket its
subclasses), clones it if a message applies, and writes the named options. This is
why the old cost of this divergence is gone: an escape-hatch
`->constraint(new Regex('/…/'))` is now messageable, where threading messages
through constructors reached only the sugared arms that looked a key up. Cloning
happens only on write, so a user-supplied constraint instance — which
`->constraint()` stores and reuses across every call — is never mutated, while an
untouched one keeps its identity.

Two behaviours worth stating because they are asymmetric on purpose. A key
matching no constraint the field built is **silently ignored**: `required(false)`
and a false `constraint()` condition both legitimately un-emit, so this cannot
throw. An unknown option name **throws** `LogicException` naming the constraint
and listing its real options — always a mistake, and worth catching loudly since
these are public properties and an unchecked name would rewrite `Length::$max`
rather than a message. That message names the field's state path, so the
`LogicException` is what a **wired** field raises; on a field never attached to a
schema the state-path read fails first — a direct-call and test scenario only,
since every validation path reaches the field through
`dehydrateValidationConstraints()`, whose own unguarded state-path read already
makes wiring the precondition. A key that is not a constraint class needs no
runtime arm: PHPStan at level max rejects it at the call site.

`getValidationMessages()` keeps Filament's evaluation semantics — values run
through `evaluate()`, the result is `array_filter`ed, and the string shorthand
normalises onto the `message` key. Permanent while Symfony owns the message.

One arm of that is narrower than Filament's, and it is worth stating because the
type reads like an oversight. A leaf resolving to `''` is filtered out and the
constraint keeps its own default — but a leaf closure **must return a string**.
The evaluating closure is typed `fn (string|Closure $message): string`, so under
`strict_types=1` a closure returning `null` is a `TypeError`, not a fallback to
the default.

That declaration is forced twice over, so do not "fix" it to `?string`. PHPStan
binds `evaluate()`'s `@return T` to `string` here, by subtracting `Closure` from
the property's union ([the closure-typing convention](../../../CLAUDE.md)), which
makes `?string` a level-max `return.unusedType` error; and Rector's
`typeDeclarations` set re-adds the declaration if it is dropped instead. It is
also the contract the property already states — `$validationMessages` types its
leaves `string|Closure`, so a closure handing back `null` violates its own
declaration, and the fail-fast rule prefers the `TypeError` to a silent fallback
that hides it. A conditional message meant to fall through to the constraint's
default is spelled `fn () => $condition ? 'Give it a title.' : ''`.

### The condition-pair walk drops Filament's numeric-key rule shorthand

Filament's `getValidationRules()` walks `[$rule, $condition]` pairs and has to
handle a numeric-keyed shorthand (a bare rule string arriving where a pair was
expected) plus `getInValidationRule()`'s `In`/`Enum` special case. With
`Constraint` objects there is no shorthand to disambiguate: the walk is
condition-then-evaluate-then-append, and the composition order is otherwise
Filament's — `NotBlank` first, then the
[length constraints](can-be-length-constrained.md) when the field implements
`Contracts\CanBeLengthConstrained`, then the escape-hatch pairs in declaration
order, then any subclass arms appended by a `buildValidationConstraints()` override
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

### The required constraint is swappable per field

Filament's `getRequiredValidationRule()` returns `'required'` or `'nullable'` for
a subclass to swap — `RichEditor` overrides it to reject a document that is
structurally empty. The port keeps the seam and translates the name with the rest
of the [rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary):
`getRequiredConstraint(): Constraint` returns the `NotBlank` that
`buildValidationConstraints()` prepends when `isRequired()`. It carries no
message of its own — one is written afterwards by the
[post-pass](#validation-messages-apply-after-the-constraints-are-built) if the
caller keyed the constraint it returns.

Two shape changes ride along. It is **protected**, not public: it is a subclass
seam, and nothing outside the field calls it — `getValidationConstraints()` is
the public read. And there is no `'nullable'` arm to return, because optionality
in Symfony is the *absence* of the constraint
([above](#nullable-removes-the-notblank-and-nothing-else)) — the caller asks
`isRequired()` first, so the method is only reached when a constraint is wanted.

[`Toggle`](toggle.md#required-means-presence-not-truth) is the first overrider:
`NotBlank` violates on `false`, which would turn `required()` into `accepted()`
on a boolean field, so it returns `NotNull` instead. That is also the one place
class-keyed messages are less forgiving than Filament's `required` key was — a
`Toggle` message keys `NotNull::class`, not `NotBlank::class`. Under class keying
that is the rule with no exception rather than a special case: key the constraint
your field emits.

### The comparison rule-string builders have no port

The four `*ComparisonRule()` builders exist to *assemble Laravel rule strings* —
`"gt:{$statePath}"`, `"after:{$date}"`. They are string plumbing for an engine
this port does not use; the behaviour they enable is tracked on the comparison
and conditional-required rows above, which will build `Constraint` objects
directly. **Skipped** as API surface, permanently.

### The subclass seam is a protected builder, not the public getter

Filament's `getValidationRules()` is both the public read and the point a
subclass overrides to append its own arms. Here those split:
`getValidationConstraints()` is the public read the
[`HasValidationConstraints`](../../../packages/schemas/src/Components/Contracts/HasValidationConstraints.php)
contract declares, and `buildValidationConstraints()` is the protected seam
[`TextInput`](text-input.md) and [`Select`](select.md) override.

The split exists because messages apply in a
[post-pass](#validation-messages-apply-after-the-constraints-are-built) over the
finished list. A subclass that overrode the public getter would call
`parent::`, append its own constraints, and miss that work. Routing every
subclass through the builder makes that unrepresentable. The getter is
deliberately not `final` — a `final` method in a trait is an odd thing to hand a
consumer, and `Field` subclasses are a public extension point — so the seam is a
documented convention plus a better alternative, not enforcement.
