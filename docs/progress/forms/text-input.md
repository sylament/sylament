# TextInput (forms)

`Sylament\Forms\Components\TextInput` — the single-line text field, extending
[`Field`](../../../packages/forms/src/Components/Field.php). An early subset of
Filament's `TextInput`: the type sugar (`type`/`email`/`numeric`/`password`), its
`getType`/`getInputMode` derivation, and length constraints via the shared
[`CanBeLengthConstrained`](../../../packages/forms/src/Components/Concerns/CanBeLengthConstrained.php)
concern. The affix/mask/step/copy/reveal/state-cast surface and the validation
`rule()` side-effects Filament attaches are not ported yet.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php)
  — the field and every trait it composes
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the concerns behind the unported rows below

## Surface

Every point this field adds over `Field`. `Field`'s own surface (label, hint,
helper text, placeholder, required, slots, rendering chrome) is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`type()` / `getType()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | in progress | tel/url sugar | [detail](#gettype-drops-the-tel-and-url-branches) |
| [`email()` / `isEmail()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | in progress | `CanBeValidated` | [detail](#email-and-numeric-drop-their-validation-rule-side-effects) |
| [`numeric()` / `isNumeric()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | in progress | `CanBeValidated`, `HasStep` | [detail](#email-and-numeric-drop-their-validation-rule-side-effects) |
| [`password()` / `isPassword()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `HasInputMode` (concern) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `integer()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `CanBeValidated`, `HasStep` | — |
| `tel()` / `telRegex()` / `isTel()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | regex validation | — |
| `url()` / `isUrl()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `CanBeValidated` | — |
| `currentPassword()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `CanBeValidated` | — |
| `maxValue()` / `minValue()` / `getMaxValue()` / `getMinValue()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `CanBeValidated` | — |
| `mask()` / `getMask()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `RawJs` | — |
| `revealable()` / `isPasswordRevealable()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `TextInput/Actions` | — |
| `copyable()` / `isCopyable()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `HasAffixes`, `TextInput/Actions` | — |
| `HasStep` (concern) | [`Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | todo | — | — |
| `HasAffixes` (concern) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | todo | prebuilt affix actions | — |
| `HasDatalistOptions` (concern) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | todo | — | — |
| `CanBeAutocapitalized` (concern) | [`Concerns/CanBeAutocapitalized.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocapitalized.php) | todo | — | — |
| `CanBeAutocompleted` (concern) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | todo | — | — |
| `CanBeReadOnly` (concern) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | todo | — | — |
| `HasExtraInputAttributes` / `HasExtraAlpineAttributes` | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | todo | — | — |
| State casts + trim/strip (`getDefaultStateCasts`, `mutate*State`) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | schemas state-cast surface | — |

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

### `getType()` drops the tel and url branches

The port's [`getType()`](../../../packages/forms/src/Components/TextInput.php)
resolves an explicit `type()` first, then falls through `email → number →
password → text`. Filament's has two more branches, `isTel()` → `'tel'` and
`isUrl()` → `'url'`, before the `text` default. They return with the `tel()` /
`url()` rows above; until then the sugar chain is faithful for the four types it
covers. Covered by TextInputTest's `derives the input type from the type sugar`
and `honors an explicit type over the sugar`.

### `email()` and `numeric()` drop their validation `rule()` side-effects

The port sets the `isEmail` / `isNumeric` flags and nothing else. Filament's
`email()` also calls `->rule('email', …)`; its `numeric()` calls
`->rule('numeric', …)`, `->inputMode(fn => 'decimal')`, and `->step(fn =>
'any')`. The flag-setting half is faithful and drives `getType()` /
`getInputMode()`; the `rule()` half is **blocked on `CanBeValidated`** (no
validation surface is ported), and the `step()` half is blocked on `HasStep`. The
input-mode half is handled bespoke — see the divergence below. Covered by `emits
inputmode=decimal for numeric inputs only`.

## Divergences

### Input mode is a bespoke getter, not the `HasInputMode` concern

Filament composes
[`HasInputMode`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php)
(an `inputMode()` setter + `getInputMode()`), and `numeric()` / `integer()` push
closures into it (`'decimal'` / `'numeric'`). The port composes no such concern:
[`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) is a fixed
method returning `'decimal'` when `isNumeric()` and `null` otherwise. The rendered
result matches Filament for the ported `numeric()` path, but there is no
user-facing `inputMode()` setter and no `'numeric'` mode (that arrives with
`integer()`). Reversible when `HasInputMode` is ported — at which point the
bespoke getter is replaced by the concern and the row above flips to a plain
compose.
