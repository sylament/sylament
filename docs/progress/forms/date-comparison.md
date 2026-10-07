---
description: The date comparison validation arms and their `DateComparison` and `ValidDate` constraint classes. (draft)
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeValidated.php
  - vendor/filament/forms/src/Components/DateTimePicker.php
owns:
  - packages/forms/src/Validation/DateComparison.php
  - packages/forms/src/Validation/ValidDate.php
---
# DateComparison (forms)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`dateComparisonRule()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) |
| [`ValidDate`](../../../packages/forms/src/Validation/ValidDate.php) + its validator — no Filament source | [`DateTimePicker::setUp()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) — `rule('date')` | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) · [diverges](date-time-picker.md#every-variant-validates-as-a-date-including-the-time-only-one) |
| [`DateComparison`](../../../packages/forms/src/Validation/DateComparison.php) + its validator — no Filament source | [`CanBeValidated::dateComparisonRule()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) |
| [Date comparison family (`after` / `afterOrEqual` / `before` / `beforeOrEqual`)](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`CanBeValidated`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [diverges](#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings) · [diverges](#a-blank-sibling-skips-the-comparison-where-laravel-fails-the-field) |

## Divergences

### The date comparison family builds constraints where Filament builds rule strings

`after()` / `afterOrEqual()` / `before()` / `beforeOrEqual()` keep Filament's signatures verbatim, including the `bool $isStatePathAbsolute = false` tail, and still delegate to a shared [`dateComparisonRule(string $rule, …)`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) whose `$rule` argument carries Filament's own rule spellings — `'after'`, `'after_or_equal'`, `'before'`, `'before_or_equal'`, which are the four [`DateComparison`](../../../packages/forms/src/Validation/DateComparison.php) operator constants.
A migrating `->dateComparisonRule('after', …)` call therefore still compiles and still means what it meant.

What changes is what the method *produces*.
Filament returns the string `"after:{$date}"` for Laravel to parse; here the method registers a closure through the existing [`constraint()`](can-be-validated.md#rule-and-rules-become-constraint-and-constraints-over-symfony-constraint-objects) pair mechanism, and the closure returns a `DateComparison` object — or `null`, which the builder's walk already skips.
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
