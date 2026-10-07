---
description: The `length()` / `maxLength()` / `minLength()` concern of TextInput and Textarea, its `Length` constraint builder and its contract.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php
  - vendor/filament/forms/src/Components/Contracts/CanBeLengthConstrained.php
  - vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php
owns:
  - packages/forms/src/Components/Concerns/CanBeLengthConstrained.php
  - packages/forms/src/Components/Contracts/CanBeLengthConstrained.php
---
# CanBeLengthConstrained (forms)

`Sylament\Forms\Components\Concerns\CanBeLengthConstrained` is the shared character-length concern composed on both `TextInput` and `Textarea`, together with the [`Contracts\CanBeLengthConstrained`](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php) interface those two fields implement.
It carries the `length` / `maxLength` / `minLength` setters and getters, and turns them into Symfony `Length` constraints for [`CanBeValidated`](can-be-validated.md) to fold into the field's constraint list.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`minLength()` / `maxLength()` (+ getters)](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | — |
| [`length()` / `getLength()`](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | — |
| [`getLengthValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php) | [`getLengthValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [diverges](#numeric-fields-get-plain-length-constraints-not-digit-counts) · [diverges](#a-zero-length-bound-builds-no-constraint) · [diverges](#length-messages-key-one-class-with-an-option-map) |
| [`Contracts\CanBeLengthConstrained`](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php) | [`Components/Contracts/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Contracts/CanBeLengthConstrained.php) | done | — | [diverges](#the-contract-declares-only-the-constraint-builder) |
| `Contracts\CanHaveNumericState` | [`Components/Contracts/CanHaveNumericState.php`](../../../vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php) | skipped | — | [diverges](#numeric-fields-get-plain-length-constraints-not-digit-counts) |

## Divergences

### Numeric fields get plain Length constraints, not digit counts

Filament's `getLengthValidationRules()` branches on `$this instanceof CanHaveNumericState && $this->isNumeric()` and swaps the whole rule family: `size:` → `digits:`, `max:` → `max_digits:`, `min:` → `min_digits:` — Laravel rules that count *digits* in a numeric value rather than characters in a string.
Symfony ships no digit-count constraint, so a numeric field gets the same `Length` every other field gets: the value is cast to string and every character counts, sign and decimal point included.
`-1.5` is therefore length 4 here, where Laravel's digit rules fail it outright for carrying a non-digit character.
The [`CanHaveNumericState`](../../../vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php) contract stays unported for the same reason — it exists to drive this branch.
Reverses if a digit-count constraint is written (a `Regex` bounding the digit run would be the port's shape); until then `maxLength()` on a numeric input is a server-side character cap only, because a numeric `TextInput` renders `type="number"` and browsers ignore `maxlength` on that type.

### A zero-length bound builds no constraint

Filament guards each bound with `filled()`, and `filled(0)` is true, so `maxLength(0)` emits `max:0`.
The port guards with `> 0`, so a zero bound is dropped and no `Length` is built — `Length(max: 0)` constructs fine in Symfony, so this is the guard's choice, not a library limit.
The three zero bounds are degenerate in different ways: `minLength(0)` is a constraint that can never fail, while `maxLength(0)` / `length(0)` accept only the empty string, which the absence of `required()` already expresses.
Provisional: the reversal is the one-token `!== null`, worth taking the moment a caller has a real reason to pin an empty value.

### Length messages key one class with an option map

Filament gives each bound its own message key — `size`, `max`, `min`.
All three `Length` builds here are the same class, so they share one key and separate by **option**: `validationMessages([Length::class => ['exactMessage' => …, 'maxMessage' => …, 'minMessage' => …]])`, applied by the [post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built).
The builder itself passes no message argument.

`Length` is also the constraint that makes the string shorthand throw rather than guess: it declares `maxMessage` / `minMessage` / `exactMessage` / `charsetMessage` and **no plain `message`**, so `[Length::class => 'Too long.']` — which would mean the `message` option — raises `LogicException` listing the four real names.

A field carrying both a max and a min emits **two** `Length` instances, and the post-pass matches on class, not on bound: every surviving option is written to every instance, so `maxMessage` lands on the min-only `Length` too.
That extra write is inert rather than merely tolerated — a `Length(min: 3)` can only ever violate its minimum, so the `maxMessage` it carries is never read.
The upshot for a caller is that one option map configures the whole family, and each message still surfaces only where its own bound can fail.

### The contract declares only the constraint builder

Filament's `Contracts\CanBeLengthConstrained` declares `getLength()`, `getMaxLength()`, `getMinLength()` **and** `getLengthValidationRules()`.
The port's declares only [`getLengthValidationConstraints(): list<Constraint>`](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php), because that is the only method anything type-checks against: the `instanceof` test in [`CanBeValidated::buildValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) exists to know whether a field *has* length constraints to fold in, and the three getters are read by the templates through the concrete field.
Narrowing keeps the contract honest about its one consumer; widening it later is additive.
The `getLengthValidationRules()` → `getLengthValidationConstraints()` rename is the [rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary), not a shape change.
