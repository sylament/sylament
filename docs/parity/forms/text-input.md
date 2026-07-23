# Text input (forms)

`TextInput` — the single-line text field. Extends the abstract [`Field`](field.md),
so the label / required-mark / hint / helper / error chrome and the `data-model`
live binding are inherited; this file tracks the input-specific surface (type sugar,
length constraints, input mode) together with the shared `FieldWrapper` Twig
component, the `TextInput.html.twig` template, and the `sy-fo-*` CSS introduced
alongside it.

Reference:

- [`vendor/filament/forms/src/Components/TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php)
  — `type()` / `getType()`, `email()` / `numeric()` / `password()`, input mode
- [`vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php)
  — `minLength()` / `maxLength()` (HTML-attr side only)
- [`vendor/filament/forms/resources/views/components/field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/field-wrapper.blade.php)
  — the wrapper chrome skeleton, translated to the `FieldWrapper` Twig component

## Missing

- [ ] The rest of the type surface: `tel()` / `url()` / `integer()`, `telRegex()`,
  `step()`, `autocomplete()` / `autocapitalize()`, `inputMode()` as a public setter,
  and `datalist()` — deferred with the wider field surface.
- [ ] `length()` and the length **validation** rules (`getLengthValidationRules()`);
  only the `minlength` / `maxlength` HTML attributes are ported. Restored with the
  validation surface (the same seam as `Field`'s `getErrorMessages()`).
- [ ] Affixes (`prefix()` / `suffix()` and affix icons/actions), input masks
  (`mask()`), password `revealable()`, and numeric `minValue()` / `maxValue()` plus
  the numeric state cast — deferred (TextInput breadth); the affix/reveal action
  slots are additionally blocked on
  [actions-package](../infrastructure.md#actions-package).

## Divergences

- **Rendered from a Twig `$template` via `ViewComponent`, not `toEmbeddedHtml()`.**
  Filament v5 renders fields from an inline `HasEmbeddedView::toEmbeddedHtml()`; we
  set `$template = '@SylamentForms/components/TextInput.html.twig'` and render through
  `ViewComponent::render()` — the general schemas→Twig stance. [#2]
- **Field-wrapper chrome rendered from direct getters (no slot schema, no hint
  actions).** The `FieldWrapper` Twig component
  (`<twig:SylamentForms:FieldWrapper :field="component">` with a `content` slot) reads
  `field.getLabel` / `isMarkedAsRequired` / `getHint` / `getHelperText` /
  `getErrorMessages` directly and is reused unchanged by every field — structurally
  mirroring Filament v4's own `<x-filament-forms::field-wrapper :field>` Blade
  component + slot, and consistent with the support `<twig:SylamentSupport:…>`
  components. It is the direct-getter chrome (vs Filament's ten child-schema wrapper
  slots) that is the divergence, not the component mechanism. Concern-side detail
  lives in [field.md](field.md). [#1]
- **The input's `required` attribute is driven by `isMarkedAsRequired()`.** Filament
  emits `required` from `isRequired()` (validation); with no validation surface yet,
  the attribute follows the visual required flag. The validation epic switches it back
  to `isRequired()`. [#5]
