# Textarea (forms)

`Sylament\Forms\Components\Textarea` — the multi-line text field. Extends
[`Field`](../../../packages/forms/src/Components/Field.php) and adds the `rows` /
`cols` sizing setters on top of the shared length-constraint concern.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php)
  — the field class and the traits it composes
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the field-attribute concerns it layers on

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details), deleted once the row is
**done**.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `rows()` / `getRows()` | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| `cols()` / `getCols()` | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| Length constraints (`maxLength` / `minLength`) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| Embedded view → Twig template | [`Textarea::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| `autosize()` / `shouldAutosize()` | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | todo | — | [detail](#autosize-is-not-ported) |
| Autocomplete (`CanBeAutocompleted`) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| Read-only (`CanBeReadOnly`) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| Disable Grammarly (`CanDisableGrammarly`) | [`Concerns/CanDisableGrammarly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableGrammarly.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| Extra input attributes (`HasExtraInputAttributes`) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| Trim state (`CanTrimState`) | [`schemas/Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | blocked | schemas state-mutation surface | [detail](#the-state-mutation-surface-is-blocked-on-schemas) |
| Strip characters (`CanStripCharactersFromState`) | [`schemas/Concerns/CanStripCharactersFromState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | blocked | schemas state-cast infrastructure | [detail](#the-state-mutation-surface-is-blocked-on-schemas) |
| State casts + `mutate*` / `mutates*` hooks | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | blocked | schemas state-cast infrastructure | [detail](#the-state-mutation-surface-is-blocked-on-schemas) |

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**.

### Autosize is not ported

Filament's `autosize()` / `shouldAutosize()` drive the `textareaFormComponent`
Alpine component (`x-load`, `x-intersect.once="resize()"`,
`x-on:resize.window="resize()"`, an `initialHeight` derived from `rows`, and the
`fi-autosizable` wrapper class). None of it is ported: `Textarea` has no
`shouldAutosize` property, and the Twig template renders a plain `<textarea>` with
no Alpine wiring or wrapper height style. Porting it needs a `forms` JS/Alpine
asset the bundle does not ship yet. **Next step:** add the autosize setter +
getter and the (Stimulus) controller, then wire the wrapper height and
`fi-autosizable`/`sy-fo-*` class into the template.

### The input-attribute concerns are not ported

Filament's `Textarea` composes `CanBeAutocompleted` (`autocomplete`),
`CanBeReadOnly` (`readonly`), `CanDisableGrammarly` (the `data-gramm*`
attributes), `HasExtraInputAttributes` (arbitrary `<textarea>` attributes), and
`HasExtraAlpineAttributes` (arbitrary `x-*` bindings). None is composed on the
Sylament `Textarea`, and the template emits none of these attributes. They are
shared field concerns (their own README rows) that are simply not reached yet —
each is a self-contained setter/getter plus a template attribute. **Next step:**
port the concerns and emit their attributes on the `<textarea>` element.

### The state-mutation surface is blocked on schemas

Filament's `Textarea` overrides `getDefaultStateCasts()` (appending a
`StripCharactersStateCast`), `mutateDehydratedState()` /
`mutateStateForValidation()` (via `CanTrimState` + `CanStripCharactersFromState`),
and the matching `mutatesDehydratedState()` / `mutatesStateForValidation()`
guards. None is ported. These depend on the schemas state-cast +
dehydration/validation infrastructure (`StateCast`, `CanTrimState`,
`CanStripCharactersFromState`, `CanBeValidated`), which is itself unported —
`Field::getErrorMessages()` still returns `[]` and notes the validation surface is
not built. **Blocked** until that schemas infrastructure lands; it returns in the
same change.

## Divergences

None. The ported subset (`rows`, `cols`, length constraints, the field-wrapper
chrome, and the Twig template) is a faithful port; the `wire:model` →
`data-model` binding, the `Htmlable` / `toEmbeddedHtml` → Twig template
translation, and placeholder escaping via Twig are systematic idiom translations
recorded in the [rename table](../renames.md), not divergences. The
`isMarkedAsRequired`-vs-`isRequired` required-attribute difference is a
`Field`-level concern (`CanBeMarkedAsRequired`), tracked in
[`field.md`](field.md), not here.
