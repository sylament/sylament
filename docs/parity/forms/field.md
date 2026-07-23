# Field (forms)

The abstract base every forms field extends (`TextInput`, `Textarea`, …). Tracks
`Field` itself plus its wrapper-chrome concerns (name, humanized label default,
required mark, helper text, hint + icon, placeholder, autofocus). The schemas
`HasName` concern it mixes in is tracked here too. Concrete fields track their own
surface in their own files; the inherited schemas base surface lives in
[../schemas/component.md](../schemas/component.md).

The wrapper chrome is the ten Filament field-wrapper slots — `aboveLabel`,
`belowLabel`, `beforeLabel`, `afterLabel`, `aboveContent`, `belowContent`,
`beforeContent`, `afterContent`, `aboveErrorMessage`, `belowErrorMessage` —
child-schema regions configured through `childComponents(…, KEY)` (the schemas
`HasChildComponents` substrate) and rendered by the `FieldWrapper` Twig component.
`makeChildSchema()` end-aligns the `after_label`/`after_content` slots;
`configureChildSchema()` marks all ten inline + embedded (mirrors `Section`).
Helper text seeds a `Text` into `below_content` (`HasHelperText`); hint text + icon
seed a `Text` + the schemas `Icon` into `after_label` (`HasHint::setUpHint()`, run
from `Field::setUp()`).

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
- [ ] The `HasHint` action slots (`hintAction()` / `hintActions()` /
  `getHintActions()`) — blocked on
  [actions-package](../infrastructure.md#actions-package). `setUpHint()` seeds the
  hint text + icon into the `after_label` slot but omits the trailing
  `...getHintActions()` spread (divergence below). The ten field-wrapper slots
  themselves are now ported (see the header and divergences).
- [ ] `HasEnum` (+ the enum state cast), affix traits (`HasAffixes`), extra
  field-wrapper attributes (`HasExtraFieldWrapperAttributes`), inline-label
  layout (`HasInlineLabel`), and `getDefaultName()` — deferred with the wider
  field surface.
- [ ] `translateLabel()` on the humanized default — blocked on
  [translation](../infrastructure.md#translation).

## Divergences

- **Slot signatures drop `Action | ActionGroup`.** The ten slot setters accept
  `Schema | array | Component | string | Markup | Closure | null`; Filament also
  allows `Action` / `ActionGroup` — blocked on
  [actions-package](../infrastructure.md#actions-package). Matches the schemas
  `Section` slot signatures. (Reconciled the `[design §2]` direct-getter divergence:
  the slot system is now ported, so helper text / hint render through slots exactly
  as Filament does.)
- **Hint actions omitted; `getHelperText()` removed.** `HasHint` keeps its
  text/icon/tooltip/color getters (byte-identical public API) but `setUpHint()`
  seeds only a `Text` + the schemas `Icon` into `after_label`, dropping Filament's
  `...getHintActions()`; `hintAction()` / `hintActions()` are not ported (blocked on
  the actions package). `HasHelperText` drops the `getHelperText()` getter Filament
  also lacks — `helperText()` writes the `below_content` slot directly.
- **Hint icon tooltip is a native `title`.** The schemas `Icon` renders its tooltip
  as a `title` attribute, not Filament's Alpine `x-tooltip` (see
  [../schemas/icon.md](../schemas/icon.md)).
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
