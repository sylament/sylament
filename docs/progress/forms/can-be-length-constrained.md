# CanBeLengthConstrained (forms)

`Sylament\Forms\Components\Concerns\CanBeLengthConstrained` — the shared min/max
character-length concern composed on both `TextInput` and `Textarea`. Ports the
`minLength` / `maxLength` half of Filament's trait; the `length()` shortcut and
the Laravel validation-rule generator are not yet in.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php)
  — the trait
- [`Components/Contracts/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Contracts/CanBeLengthConstrained.php)
  — the interface the trait satisfies (its own [Supporting row](README.md#supporting-surface), unported)

## Surface

_Blocked by_ names what has to land first. _Detail_ links the write-up in
[Details](#details), deleted once the row is **done**.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `minLength` / `maxLength` (+ getters) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | — |
| `length()` / `getLength()` | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | todo | — | [detail](#the-length-shortcut-is-not-ported) |
| `getLengthValidationRules()` | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | blocked | validation port (`CanBeValidated`) | [detail](#length-validation-rules-await-the-validation-port) |

## Details

### The `length()` shortcut is not ported

Filament's trait carries a third property `$length` with a `length()` setter that
fans out to all three fields (`$this->length = $this->maxLength = $this->minLength
= $length`) and a `getLength()` getter. The Sylament trait ports only `minLength`
/ `maxLength`. `length()` is a trivial convenience with no dependency on anything
unported — the **next step** is to add the `$length` property, the fan-out setter,
and `getLength()`, matching Filament exactly.

### Length validation rules await the validation port

Filament's `getLengthValidationRules()` emits Laravel validator rule strings
(`size:` / `digits:`, `max:` / `max_digits:`, `min:` / `min_digits:`), branching
on `$this instanceof CanHaveNumericState && $this->isNumeric()`. None of that
machinery exists yet: the `CanBeValidated` concern and the `CanHaveNumericState`
contract are both **todo**, and the Symfony validation strategy is undecided
(Laravel rule strings have no Symfony analogue). **Blocked** until the validation
subsystem lands, at which point it is re-expressed in whatever form that port
chooses rather than copied verbatim.

## Divergences

None. The two absences (`length()` and `getLengthValidationRules()`) are
deferrals, not deliberate API deviations — they are tracked as the Details above,
and the trait's ported half is byte-faithful to Filament apart from
PHP-CS-Fixer's union-type whitespace.
