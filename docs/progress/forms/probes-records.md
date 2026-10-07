---
description: The data-layer validation arms `unique()` and `exists()`, their DQL probe and their constraint classes. (draft)
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeValidated.php
owns:
  - packages/forms/src/Validation/ExistingValue.php
  - packages/forms/src/Validation/UniqueValue.php
---
# ProbesRecords (forms)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Data-layer family (`unique` / `exists` / `uniqueValidationIgnoresRecordByDefault`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#unique-and-exists-are-one-dql-probe-behind-a-container-service) · [semantics](#a-sticky-field-probes-the-database-on-every-request) |
| `scopedUnique()` / `scopedExists()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#scopedunique-and-scopedexists-have-nothing-to-scope) |
| [`UniqueValue`](../../../packages/forms/src/Validation/UniqueValue.php), [`ExistingValue`](../../../packages/forms/src/Validation/ExistingValue.php) + their validators — no Filament source | [`CanBeValidated::unique()` / `exists()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#unique-and-exists-are-one-dql-probe-behind-a-container-service) |

## Semantics

### A sticky field probes the database on every request

The [sticky pass](../schemas/can-be-validated.md#sticky-re-validation-replaces-filaments-submit-only-model) re-runs every constraint on every request while a schema is sticky, so a `unique()` field costs one indexed probe per request in that state where Filament pays it only on submit.
Accepted and recorded; a submit-only flag is the reversal if it ever matters.

A blank value never reaches the database at all: `UniqueValue` and `ExistingValue` are not implicit, so the gate strips them from a blank string, and their validators return on `null` regardless.

## Divergences

### unique() and exists() are one DQL probe behind a container service

Filament's `unique()`/`exists()` take an Eloquent table and column and lean on Laravel's query builder.
The port takes an **entity class** and a **mapped property** and runs one DQL probe through schemas' [`AssociationReader`](../schemas/association-reader.md), which already ships `createOptionQuery()`, `findRecordKey()` and `hasEntityManager()` — and which narrows the probe through schemas' own [`IdentifierConstraint`](../schemas/record-key-contract.md#the-operations-and-their-dql) rather than through a field name.
No new dependency either way: forms declares `doctrine/orm ^3.7`, which guarantees every `Doctrine\ORM\*` symbol transitively, and `sylament/schemas`, which it has always required.

Three parameter renames come with it, all recorded in [renames.md](../renames.md): `table` → `entity` (a class-string, not a table), `column` → `property` (a mapped field, not a column), and `modifyRuleUsing` → `modifyQueryUsing` (the closure receives a Doctrine `QueryBuilder`, not a Laravel `Unique` rule — and `modifyQueryUsing` is the name [`Select::relationship()`](select.md) already uses for the same thing).
A fourth signature delta is forced by PHP rather than chosen: `$ignorable` is typed `?object` where Filament types it `Model|Closure|null`, because a `Closure` *is* an object and PHP rejects the union outright.
The closure arm still works — `evaluate()` invokes it.

The class names are `UniqueValue` and `ExistingValue`, not `Unique`/`Exists`.
Symfony ships its own `Constraints\Unique` (which asserts an *array* has no duplicates), and two classes of that name in one import list would let an IDE auto-import the wrong one as a `validationMessages()` key — where an unmatched key is [silently ignored by design](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built).
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
It is a record **key** of any width, so `UniqueValue::$ignoredIdentifier` carries one string and the exclusion is [`IdentifierConstraint::notEquals()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under the base `sylament_ignored_record`, `andWhere`d only when the predicate is non-null — a **malformed** key excludes nothing, which for that input is the safe direction: a false *taken*, never a false *unique*.
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
