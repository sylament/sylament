# Textarea (forms)

`Textarea` — the multi-line text field. Extends the abstract [`Field`](field.md)
directly (not `TextInput`), so the label / required-mark / hint / helper / error
chrome and the `data-model` live binding are inherited unchanged; this file tracks
the textarea-specific surface (`rows()` / `cols()`) plus the length-constraint
setters shared with [`TextInput`](text-input.md). `Textarea.html.twig` embeds the
exact same `field-wrapper.html.twig` as `TextInput.html.twig`, unmodified — the
reuse proof that the wrapper is shared chrome, not `TextInput`-specific.

Reference:

- [`vendor/filament/forms/src/Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php)
  — `rows()` / `getRows()`, `cols()` / `getCols()`
- [`vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php)
  — `minLength()` / `maxLength()` (HTML-attr side only), shared with `TextInput`
- [`vendor/filament/forms/resources/views/components/field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/field-wrapper.blade.php)
  — the wrapper chrome skeleton, translated to `field-wrapper.html.twig` (F2;
  reused here unchanged)

## Missing

- [ ] `autosize()` and the Alpine autosize behavior (`shouldAutosize()`, the
  `fi-autosizable` class, the initial-height inline style) — deferred with the
  wider field surface.
- [ ] `Concerns\HasExtraInputAttributes` (`extraInputAttributes()` and the merged
  input attribute bag) — deferred.
- [ ] `CanDisableGrammarly` (`disableGrammarly()`, the `data-gramm*` attributes) —
  deferred.
- [ ] `Concerns\CanBeAutocompleted`, `Concerns\CanBeReadOnly`,
  `CanStripCharactersFromState`, `CanTrimState` — deferred with the wider field
  surface.
- [ ] Validation (`CanBeValidated`, the length validation rules) — the same seam
  as [`Field`](field.md)'s `getErrorMessages()`.

## Divergences

- **Rendered from a Twig `$template` via `ViewComponent`, not `toEmbeddedHtml()`.**
  Same stance as [text-input.md](text-input.md#divergences). [#2]
- **Field-wrapper chrome rendered from direct getters (no slot schema, no hint
  actions).** `Textarea.html.twig` embeds the exact same
  `field-wrapper.html.twig` as `TextInput.html.twig`, unmodified. Concern-side
  detail lives in [field.md](field.md). [#1]
- **The textarea's `required` attribute is driven by `isMarkedAsRequired()`.**
  Filament emits `required` from `isRequired()` (validation); with no validation
  surface yet, the attribute follows the visual required flag. The validation
  epic switches it back to `isRequired()`. [#5]
