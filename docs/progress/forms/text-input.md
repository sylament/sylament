# TextInput (forms)

`Sylament\Forms\Components\TextInput` — the single-line text field, extending
[`Field`](../../../packages/forms/src/Components/Field.php). An early subset of
Filament's `TextInput`: the type sugar (`type`/`email`/`numeric`/`password`), its
`getType`/`getInputMode` derivation, length constraints via the shared
[`CanBeLengthConstrained`](can-be-length-constrained.md) concern, and the `Email`
/ numeric `Type` validation arms `email()` / `numeric()` contribute to
[`CanBeValidated`](can-be-validated.md). The
affix/mask/step/copy/reveal/state-cast surface is not ported yet.

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
| [`email()` / `isEmail()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-email-and-numeric-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`numeric()` / `isNumeric()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | in progress | `HasStep` | [detail](#numeric-does-not-set-the-step) · [diverges](#the-email-and-numeric-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`getValidationConstraints()` arms](../../../packages/forms/src/Components/TextInput.php) | [`TextInput::email()` / `numeric()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [diverges](#the-email-and-numeric-constraint-arms-live-in-an-override-not-in-the-setters) |
| [`password()` / `isPassword()`](../../../packages/forms/src/Components/TextInput.php) | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |
| [`getInputMode()`](../../../packages/forms/src/Components/TextInput.php) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `HasInputMode` (concern) | [`Concerns/HasInputMode.php`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | skipped | — | [diverges](#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern) |
| `integer()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | `HasStep` | — |
| `tel()` / `telRegex()` / `isTel()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | — | — |
| `url()` / `isUrl()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | — | — |
| `currentPassword()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | authentication layer | — |
| `maxValue()` / `minValue()` / `getMaxValue()` / `getMinValue()` | [`TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php) | todo | — | — |
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

### `numeric()` does not set the step

Filament's `numeric()` does three things beyond setting the flag: a validation
rule (ported, as the constraint arm below), `->inputMode(fn => 'decimal')`
(handled bespoke — see the divergence below), and `->step(fn => 'any')`. The
`step` half is **blocked on `HasStep`**, which the port does not compose, so a
numeric input renders no `step` attribute and the browser applies its default of
`1` — rejecting decimals in the native check even though the server-side
`Type('numeric')` accepts them. **Next step:** port `HasStep` and have
`numeric()` seed `'any'`, exactly as Filament does.

## Divergences

### The email and numeric constraint arms live in an override, not in the setters

Filament's `email()` / `numeric()` push their validation rule into the shared
rule list from inside the setter (`->rule('email', fn () => $condition)`), so the
rule is registered eagerly and the condition closure is what stays lazy. The
port's setters only flip `isEmail` / `isNumeric`, and
[`getValidationConstraints()`](../../../packages/forms/src/Components/TextInput.php)
overrides the parent to append `Email(message: …)` / `Type('numeric', message:
…)` behind those flags. Same observable API and same laziness, two reasons for
the shape: a `Constraint` object baked at setter time would freeze the
`validationMessages()` key before the user has set it (Filament's messages are a
separate array resolved later, so it does not have this problem), and the
override keeps every message-baking arm in one readable list. The cost is that a
subclass overriding `getValidationConstraints()` must call `parent::` — the
constraint order is `NotBlank` → length → escape hatch → type arms, pinned by
`TextInputTest`.

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
