---
description: The data-layer validation arms `unique()` and `exists()`, their `UniqueValue` and `ExistingValue` constraints and the DQL probe their validators share.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeValidated.php
owns:
  - packages/forms/src/Validation/Concerns/ProbesRecords.php
  - packages/forms/src/Validation/ExistingValue.php
  - packages/forms/src/Validation/ExistingValueValidator.php
  - packages/forms/src/Validation/UniqueValue.php
  - packages/forms/src/Validation/UniqueValueValidator.php
---
# ProbesRecords (forms)

This file covers the `unique()` / `exists()` family of `CanBeValidated`; the rest of that trait is in [can-be-validated.md](can-be-validated.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`unique()` / `exists()` / `uniqueValidationIgnoresRecordByDefault()` / `shouldUniqueValidationIgnoreRecordByDefault()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#unique-and-exists-are-one-dql-probe-behind-a-container-service) · [semantics](#the-defaults-are-filaments-and-no-entity-class-means-no-constraint) · [diverges](#the-exclusion-is-by-key-so-a-record-of-another-class-can-exclude-a-row) · [semantics](#a-sticky-field-probes-the-database-on-every-request) |
| `scopedUnique()` / `scopedExists()` | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#scopedunique-and-scopedexists-have-nothing-to-scope) · [semantics](#a-row-a-filter-hides-reads-as-free-to-unique) |
| [`UniqueValue`](../../../packages/forms/src/Validation/UniqueValue.php) and [`ExistingValue`](../../../packages/forms/src/Validation/ExistingValue.php) with their validators | — | done | — | [diverges](#the-constraints-are-named-uniquevalue-and-existingvalue) · [diverges](#the-validators-are-container-services-and-throw-when-built-without-the-reader) · [semantics](#a-malformed-ignored-key-excludes-nothing) · [semantics](#exists-probes-a-column-never-a-key) · [semantics](#exists-over-an-array-is-one-count-over-the-distinct-values) |

## Semantics

### A sticky field probes the database on every request

The [sticky pass](../schemas/can-be-validated.md#sticky-re-validation-replaces-filaments-submit-only-model) re-runs every constraint on every request while a schema is sticky, so a `unique()` field costs one indexed probe per request in that state where Filament pays it only on submit.
Accepted and recorded; a submit-only flag is the reversal if it ever matters.

A blank value never reaches the database: `UniqueValue` and `ExistingValue` are not implicit, so the presence gate strips them from a blank string, and their validators return on `null` and `''` (and `ExistingValueValidator` on `[]`) regardless.

### The defaults are Filament's, and no entity class means no constraint

`$entity` falls back to `getRecordClass()`, the component tier's nearest-first walk, which is right for uniqueness because a relationship repeater's item field binds to the related class.
`$property` falls back to `getName()`.
The ignored record is `getRecord()` when `$ignoreRecord ?? shouldUniqueValidationIgnoreRecordByDefault()` holds and no explicit `ignorable:` was given.
When no entity class resolves, no constraint is emitted, as in Filament: a scratch-state card with no record binding and no `entity:` argument validates nothing.

### A malformed ignored key excludes nothing

The identifier to ignore comes from [`AssociationReader::findRecordKey()`](../schemas/association-reader.md#findrecordkey-answers-null-where-getrecordkey-throws), which answers null for an entity whose identifier is not complete yet instead of throwing.
That is the create page's normal state, and it covers an assigned key still blank as well as a generated one still pending.
The key may have any width, so `UniqueValue::$ignoredIdentifier` carries one string and the exclusion is [`IdentifierConstraint::notEquals()`](../schemas/record-key-contract.md#the-operations-and-their-dql), `andWhere`d only when it returns a predicate.
A key that does not decode against the probed class's identifier excludes nothing, which is the safe direction for that input: a false *taken*, never a false *unique*.

The value is bound as `sylament_probe_<n>`, where `<n>` counts the parameters already bound, the `SearchConstraint` way.
The exclusion's base is the literal `sylament_ignored_record`, because `IdentifierConstraint` refuses a parameter base ending in `_<digits>` for every class, a single identifier included.
The caller's `modifyQueryUsing` closure runs after both are bound, so it can read them by name, and a closure that binds `sylament_probe_0` itself overwrites the value.

### exists() probes a column, never a key

`createProbeQuery()` admits `getFieldNames()` only, so an identifier **association** is refused by name as the unmapped field it is, never probed by key, and a scalar identifier half is an ordinary column whose `=` and `IN` compare as any column's do, at every identifier width.
That is why `ExistingValueValidator`, `ProbesRecords` and `UniqueValueValidator`'s value arm have no key arm.
The case `refuses an identifier association as the unmapped field it is, rather than probing by key` in [`ExistingValueValidatorTest`](../../../packages/forms/tests/Unit/Validation/ExistingValueValidatorTest.php) pins it, so a sweep over the by-key sites does not turn this one into a key probe.
Only `unique(ignoreRecord:)` reads a key, and only for its exclusion.

A taken assigned identifier does not change that: the key probe it needs is schemas' own, inside the record check `getData()` ends in ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)), so no key field needs a `unique()`.
A `unique()` declared on a key half anyway probes that column, under its own *"This value is already taken."*, and answers during field validation, before the record check runs.

### exists() over an array is one COUNT over the distinct values

An array value (a multiple select) is checked as Laravel's `exists` checks it: one `IN` probe over the distinct values, violating unless the matched `COUNT(DISTINCT …)` equals the number of distinct values asked for.
It is the one probe in the repository that counts; every other "is there one?" probe, `UniqueValueValidator`'s included, is a `getOneOrNullResult()` limited to one row.

### A row a filter hides reads as free to unique()

The probe runs through the entity manager, so Doctrine's SQL filters apply to it.
A soft-deleted row still holds the column's database unique index, so under a soft-delete filter `unique()` reads its value as free and the flush meets a `UniqueConstraintViolationException`, a 500.
That is the policy rather than an oversight: `unique()` answers what the entity's own filters show, as Filament's `scopedUnique()` answers through the model's global scopes.
The record check's taken-**key** probe is the one read that suspends every filter, because a primary key is unique whatever a filter shows ([what the taken-key probe cannot see](../schemas/association-reader.md#what-the-taken-key-probe-cannot-see)).

## Divergences

### unique() and exists() are one DQL probe behind a container service

Filament's `unique()` / `exists()` take an Eloquent table and column and lean on Laravel's validation rules.
The port takes an **entity class** and a **mapped property** and runs one DQL probe through schemas' [`AssociationReader`](../schemas/association-reader.md), whose `createOptionQuery()` starts the query and whose `hasEntityManager()` guards it; the probe narrows through schemas' [`IdentifierConstraint`](../schemas/record-key-contract.md#the-operations-and-their-dql) rather than through a field name.
Forms declares `doctrine/orm` and `sylament/schemas` directly, so the probe adds no dependency.

Three parameter renames come with it, all recorded in [renames.md](../renames.md): `table` → `entity` (a class-string, not a table), `column` → `property` (a mapped field, not a column), and `modifyRuleUsing` → `modifyQueryUsing` (the closure receives a Doctrine `QueryBuilder`, not a Laravel `Unique` rule, and `modifyQueryUsing` is the name [`Select::relationship()`](select.md) uses for the same thing).
A fourth signature delta is forced by PHP rather than chosen: `$ignorable` is typed `?object` where Filament types it `Model|Closure|null`, because a `Closure` *is* an object and PHP rejects the union outright.
The closure arm still works, because `evaluate()` invokes it.

### The constraints are named UniqueValue and ExistingValue

The class names are `UniqueValue` and `ExistingValue`, not `Unique` / `Exists`.
Symfony ships its own `Constraints\Unique` (which asserts an *array* has no duplicates), and two classes of that name in one import list would let an IDE auto-import the wrong one as a `validationMessages()` key, where an unmatched key is [silently ignored by design](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built).
So the classes say what they assert.

### The validators are container services and throw when built without the reader

The validators are registered by a `src/Validation/*Validator.php` glob in [`SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php) with `autowire()` and `autoconfigure()`.
`ContainerConstraintValidatorFactory` resolves a `validatedBy()` through the container and falls back to `new $class()`, which for a validator with a required dependency is an `ArgumentCountError`, a raw PHP `Error` rather than a validation failure.
The constructor is therefore `?AssociationReader $associationReader = null`, the nullable-with-fallback shape `AbstractComparisonValidator` and `WhenValidator` use, and the null arm throws a `LogicException` naming the bundle.
That is deliberate: **a uniqueness check that silently does nothing is a security-shaped failure**, so this takes the "act loudly" side of the reader's two guard flavours.
A missing entity manager, and a property that is not a mapped field of the class, are refused the same way, the latter naming both.

### The exclusion is by key, so a record of another class can exclude a row

The exclusion decodes the ignored record's key against the **probed** class's identifier, so `unique(entity: Other::class)` on a page bound to a record of a *different* class excludes whatever row of the probed class carries the same key: a false *unique*.
A record whose key has a different width decodes to `null` and excludes nothing.
Filament does not reach this outcome: its `ignore()` passes the ignorable's `getQualifiedKeyName()`, a column qualified by the ignorable's own table, so the same declaration there is a query against a column the probed table does not have.
[`Select::applyIgnoredRecord()`](select.md) refuses a foreign-class record by name, and `unique()` has no such guard.

### scopedUnique() and scopedExists() have nothing to scope

Upstream these exist to route around Laravel's `unique` / `exists` rules bypassing Eloquent's **global scopes**: the rules query the table directly, so a soft-delete or tenancy scope declared on the model is not applied, and the `scoped*` variants re-run the check through the model's own builder.
A DQL probe runs through the entity manager, so Doctrine's SQL filters already apply and there is nothing left to scope: `unique()` and `exists()` *are* the scoped forms here.
**Skipped**, permanently.
`modifyQueryUsing:` covers the case where a caller wants a narrower probe than the entity's own filters give.
