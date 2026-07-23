# Field (forms)

The abstract base every forms field extends (`TextInput`, `Textarea`, …). Tracks
`Field` itself plus its wrapper-chrome concerns (name, humanized label default,
required mark, helper text, hint + icon, placeholder, autofocus). The schemas
`HasName` concern it mixes in is tracked here too. Concrete fields track their own
surface in their own files; the inherited schemas base surface lives in
[../schemas/component.md](../schemas/component.md).

Reference:

- [`vendor/filament/forms/src/Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php)
  — `make()` / `__construct()`, `getLabel()` / `getDefaultLabel()` humanize
- [`vendor/filament/schemas/src/Components/Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php)
- [`vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php)
- [`vendor/filament/forms/src/Components/Concerns/HasHelperText.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php)
- [`vendor/filament/forms/src/Components/Concerns/HasHint.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php)
- [`vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php)
- [`vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php)

## Missing

- [ ] Validation (`CanBeValidated` / `HasValidationRules`): the rule engine,
  `isRequired()`, and real error messages. `getErrorMessages()` returns `[]` for
  now; the wrapper renders its error region only when it is non-empty. Restores
  the `isMarkedAsRequired()` `?? isRequired()` fallback (divergence below) when it
  lands.
- [ ] The ten field-wrapper slots (`aboveLabel`, `belowLabel`, `beforeLabel`,
  `afterLabel`, `aboveContent`, `belowContent`, `beforeContent`, `afterContent`,
  `aboveErrorMessage`, `belowErrorMessage`) and the `HasHint` action slots
  (`hintAction()` / `hintActions()` / `getHintActions()` / `setUpHint()`) — the
  wrapper chrome is rendered from direct getters instead (divergence below); the
  action slots are additionally blocked on
  [actions-package](../infrastructure.md#actions-package).
- [ ] `HasEnum` (+ the enum state cast), affix traits (`HasAffixes`), extra
  field-wrapper attributes (`HasExtraFieldWrapperAttributes`), inline-label
  layout (`HasInlineLabel`), and `getDefaultName()` — deferred with the wider
  field surface.
- [ ] `translateLabel()` on the humanized default — blocked on
  [translation](../infrastructure.md#translation).

## Divergences

- **Direct-getter wrapper chrome (no slot schema, no hint actions).** Filament
  stores helper text as a `belowContent` child-schema slot (no getter), and hint
  text/icon as `afterLabel` slots seeded in `setUpHint()`, exposing the wrapper as
  ten child-schema slot keys. We store each chrome value on its own prop with a
  direct getter — `getHelperText()`, `hasHint()` / `getHint()`, `hasHintIcon()` /
  `getHintIcon()` / `getHintIconTooltip()`, `getHintColor()` — and render the
  wrapper from those getters. `hintAction()` / `hintActions()` are dropped. [design §2]
- **`HasName` is nullable.** Filament types `HasName::$name` as non-nullable
  `string` and `Field::make()` throws (via `getDefaultName()`) when no name is
  given. We type it `?string $name = null` with `name(?string)` /
  `getName(): ?string`, and `make(?string $name = null)` seeds a null name and
  state path without throwing — a layout-shaped field needs no name. Consequently
  `getDefaultLabel()` returns `null` for a null name rather than humanizing. [#3]
- **`isMarkedAsRequired()` returns the explicit flag only.** Filament falls back
  to `?? $this->isRequired()` from the un-ported `CanBeValidated`; we return
  `(bool) $this->evaluate($this->isMarkedAsRequired)`. The validation epic restores
  the fallback. [#4]
