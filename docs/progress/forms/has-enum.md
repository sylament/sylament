---
description: 'The enum seam on every field: `enum()` / `getEnum()` and the field-tier enum state cast they drive, swapped for the array cast under `multiple()`.'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasEnum.php
  - vendor/filament/forms/src/Components/Field.php
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/src/Components/ToggleButtons.php
  - vendor/filament/forms/src/Components/Concerns/CanBeValidated.php
owns:
  - packages/forms/src/Components/Concerns/HasEnum.php
---
# HasEnum (forms)

`Sylament\Forms\Components\Concerns\HasEnum` — three lines of storage (`enum()` / `getEnum()`) that are the whole enum seam, plus the `Field`-tier plumbing they drive: `getEnumDefaultStateCast()` and the `getDefaultStateCasts()` override that appends it.
Composed on the **base `Field`**, so `enum()` exists on every field rather than on the option-shaped ones only, exactly as Filament composes it.

One declaration therefore does two jobs: it is the option source [`HasOptions`](has-options.md) falls back to, and it is the state cast that turns a submitted backing value into a case on the way in and back into a string on the way out.
[`HasDescriptions`](has-descriptions.md), `HasColors` and `HasIcons` read `getEnum()` too, each deriving its own per-case map through support's `HasDescription` / `HasColor` / `HasIcon` contracts; each fallback is written up with its concern, the colours and icons in [toggle-buttons.md](toggle-buttons.md#hascolors-hasicons-and-hastooltips-are-this-fields-alone).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `enum()` / `getEnum()` on every `Field` | [`Concerns/HasEnum.php`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | done | — | — |
| [`Field::getDefaultStateCasts()`](../../../packages/forms/src/Components/Field.php) appending the enum cast | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [semantics](#a-custom-cast-suppresses-the-option-casts-not-the-enum-one) |
| [`Field::getEnumDefaultStateCast()`](../../../packages/forms/src/Components/Field.php) → [`EnumStateCast`](../schemas/enum-state-cast.md) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#a-pure-unitenum-is-refused-at-the-field-not-deep-in-the-cast) |
| [`Select::getEnumDefaultStateCast()`](../../../packages/forms/src/Components/Select.php) → `EnumArrayStateCast` under `multiple()` | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | — |
| [`ToggleButtons::getEnumDefaultStateCast()`](../../../packages/forms/src/Components/ToggleButtons.php) → the same swap under its own `multiple()` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | — |
| Enum-derived options (`options(MyEnum::class)`, `enum()` alone) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| `Rule::enum()` as the implicit validation rule ([`CanBeValidated::getInValidationRule()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php)) | [`Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | skipped | — | [diverges](#the-enum-validates-through-the-option-choice-not-a-rule-of-its-own) |

## Semantics

### One declaration, two type contracts

The options half accepts **any** `UnitEnum` — a pure enum keys its options by case name — while the state casts need a **`BackedEnum`**, because a form's raw state is a scalar and only a backing value can spell a case.
Filament carries the same asymmetry: its `HasOptions` types nothing beyond `class-string`, while its `EnumStateCast` is documented `class-string<BackedEnum>` and calls `tryFrom()`.
Ours is documented the same and [reaches for `$case->value` instead of `tryFrom()`](../schemas/enum-state-cast.md), which is a different line to fatal on and the same requirement.

The port keeps the asymmetry and makes the boundary explicit rather than implicit — see [the divergence below](#a-pure-unitenum-is-refused-at-the-field-not-deep-in-the-cast).
Two consequences worth stating:

- **The enum-case key idiom is `$case instanceof BackedEnum ? $case->value : $case->name`.**
  Filament writes `$case->value ?? $case->name`, which reads as a null-coalesce but is really a "property may not exist" fallback — PHPStan at level max rejects `->value` on a `UnitEnum` outright, and `??` does not save it.
  Every enum fallback in the port spells the `instanceof` out.
- **A pure `UnitEnum` is a legitimate option source** as long as nothing asks for its cast: `options(MySize::class)` on a field whose state never round-trips through the enum renders fine.
  It is `getEnumDefaultStateCast()` that refuses, and it only runs when a cast is actually assembled.

### A custom cast suppresses the option casts, not the enum one

Each option field's `getDefaultStateCasts()` opens with the shared guard `if ($this->hasCustomStateCasts() || filled($this->getEnum())) { return parent::getDefaultStateCasts(); }`, and `parent::` is `Field`, which appends the enum cast whenever an enum is set.
So the two escapes are not symmetrical, and the asymmetry is Filament's:

- A **custom** `stateCast()` disables the field's own option cast (`OptionStateCast` / `OptionsArrayStateCast`) — the user has declared how this state is stored — but an enum cast still precedes it if an enum is set.
- An **enum** disables the option cast too, because the enum cast already speaks the option vocabulary: it stores `(string) $case->value`, which is the option key the template rendered.

`Select::boolean()` is the case where both fire at once and the result reads as a collapse: it sets an option map *and* appends a `BooleanStateCast(isStoredAsInt: true)`, so `hasCustomStateCasts()` flips true, the default set falls back to `Field`'s (empty, since `boolean()` sets no enum), and the effective chain is the boolean cast alone.
That is the intended shape, not an accident of ordering.

## Divergences

### The enum validates through the option `Choice`, not a rule of its own

Filament's `CanBeValidated::getInValidationRule()` falls back to `Rule::enum($enum)` when a field carries an enum and no explicit `in` values.
The port ships no such fallback and needs none: an enum'd field's `getOptions()` already enumerates every case, and the `Choice` constraint each option field builds runs over exactly that list — so the enum is validated, strictly, by the same constraint that guards a literal option map, with one code path instead of two.

Sharing that path has one precondition: the gate deciding whether to build the `Choice` must read the [raw state, not the cast one](select.md#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out).
[`EnumStateCast`](../schemas/enum-state-cast.md) fails closed to `null` for a value matching no case, so an enum'd field is the one place where a filled payload has a *blank* cast state — and a cast reading of the gate would skip the `Choice` for precisely the values it exists to reject.
A literal option map cannot show it, because [`OptionStateCast`](../schemas/option-state-cast.md) hands a tampered scalar back unchanged.

`Select`'s no-options `LogicException` is where the missing rule would otherwise surface: Filament throws when `$this->options === null`, which an enum-only select is.
The port asks `$this->options === null && $this->getEnum() === null`, so an enum-only field validates rather than throwing — the guard exists to make "validates nothing" impossible, and an enum is a full enumeration.

### A pure `UnitEnum` is refused at the field, not deep in the cast

Filament hands `getEnum()`'s result to `app(EnumStateCast::class, ['enum' => $enum])` with nothing checking that the enum is backed; a pure `UnitEnum` reaches `$this->enum::tryFrom($state)` and fatals there, on a method the class does not have, with a stack that names the cast rather than the field that misconfigured it.
Unguarded, the port would fatal one line over — on `$case->value`, a property a pure case does not have — which is no better a message.

`Field::assertBackedEnum()` refuses at the seam instead, with a `LogicException` naming the field and the enum and saying what to change.
It is not just a nicer message: the throw carries `@phpstan-assert class-string<BackedEnum> $enum`, so every caller downstream of it — `Field`'s cast and `Select`'s multiplicity-aware override — narrows to the type the cast constructors document, with no cast, no inline suppression, and no second runtime check.
The [no-inline-`@phpstan-ignore` rule](../../../CLAUDE.md) is what makes that shape the cheap one: an unguarded nullable or an under-typed class-string wants a throw, and here the throw pays for itself twice.
