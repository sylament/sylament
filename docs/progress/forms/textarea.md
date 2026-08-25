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
| Length constraints (`length` / `maxLength` / `minLength` + `Contracts\CanBeLengthConstrained`) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| Error display (`aria-invalid` on the `<textarea>`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| Embedded view → Twig template | [`Textarea::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| Input wrapper (`wrapInputHtml`) — no affix API, Filament-exact | [`Textarea::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [semantics](field.md#the-wrapper-owns-the-chrome-and-the-control-is-transparent) · [diverges](#the-control-keeps-the-sy-fo-input-class-filaments-textarea-carries-none) |
| `autosize()` / `shouldAutosize()` | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | todo | — | [detail](#autosize-is-not-ported) |
| [Autocomplete (`CanBeAutocompleted`)](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | [detail](can-be-autocompleted.md) |
| [Read-only (`CanBeReadOnly`)](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [detail](can-be-read-only.md) |
| Disable Grammarly (`CanDisableGrammarly`) | [`Concerns/CanDisableGrammarly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableGrammarly.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| [Extra input attributes (`HasExtraInputAttributes`)](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | todo | — | [detail](#the-input-attribute-concerns-are-not-ported) |
| [Trim state (`CanTrimState`)](../../../packages/schemas/src/Components/Concerns/CanTrimState.php) | [`schemas/Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | done | — | [semantics](#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [Strip characters (`CanStripCharactersFromState`)](../../../packages/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | [`schemas/Concerns/CanStripCharactersFromState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | done | — | [semantics](#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [State casts + `mutate*` / `mutates*` hooks](../../../packages/forms/src/Components/Textarea.php) | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [semantics](#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [Native constraint attributes drop when concealed](../../../packages/forms/templates/partials/Textarea/index.html.twig) — `required`, `minlength`, `maxlength` | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |

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

Filament's `Textarea` composes `CanDisableGrammarly` (the `data-gramm*`
attributes) and `HasExtraAlpineAttributes` (arbitrary `x-*` bindings). Neither is
composed on the Sylament `Textarea`, and the template emits neither attribute.
They are shared field concerns (their own README rows) that are simply not
reached yet — each is a self-contained setter/getter plus a template attribute.
**Next step:** port the concerns and emit their attributes on the `<textarea>`
element. Filament composes three more alongside them,
[`HasExtraInputAttributes`](has-extra-input-attributes.md),
[`CanBeReadOnly`](can-be-read-only.md) and
[`CanBeAutocompleted`](can-be-autocompleted.md); all three are absent from this
list because they have landed.

## Semantics

### trim() reaches the saved value, strip reaches it through a cast, neither reaches the raw bag

`Textarea` composes schemas' `CanTrimState` and `CanStripCharactersFromState` and
overrides the four mutation members plus `getDefaultStateCasts()`, exactly as
[`TextInput`](text-input.md) does — the two surfaces are identical upstream, and
`Textarea::stripCharacters()` is undocumented there but fully wired, so it ships.

The asymmetry is the feature, and it is Filament's:

- `trim()` reaches the **validated** value through `mutateStateForValidation()`
  and the **saved** value through `mutateDehydratedState()`.
- `stripCharacters()` reaches the validated value the same way, but reaches the
  saved value through a `StripCharactersStateCast` appended to the default casts —
  not through the dehydration hook.
- **Neither reaches the raw bag** the browser wrote, so what the user typed stays
  on screen while what is validated and stored is cleaned.

`mutatesStateForValidation()` is `parent || hasStripCharacters() || isTrimmed()`,
so a `Textarea` with neither set costs the mutation walk nothing.
[The walk itself](../schemas/schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)
is schemas'. `TagsInput`, the third upstream consumer, is M3 · 9's.

## Divergences

### The control keeps the `sy-fo-input` class, Filament's textarea carries none

Filament's `<textarea>` is the one text control with **no** `fi-input` class on
it: its typography, padding and placeholder colours come from a
`.fi-fo-textarea textarea` descendant rule in
[`textarea.css`](../../../vendor/filament/forms/resources/css/components/textarea.css),
which restates most of `input.fi-input` a second time. Ours keeps
`class="sy-fo-input"` on the control and lets the shared rule paint it, so the
declarations exist once. What the wrapper class carries here is only what is
genuinely the wrapper's: `.sy-fo-textarea { overflow-hidden }`, vendor's own
first declaration, without which the control's square corners spill past the
wrapper's `rounded-lg`.

The placement is what diverges, not the result — a shipped `<textarea>` renders
the same box either way. It reverses by moving the declarations into a
`.sy-fo-textarea textarea` rule and dropping the class from the template, which
is worth doing only if the two rules ever need to disagree; autosize is the
candidate, and it is [not ported](#autosize-is-not-ported).

The rest of the ported subset (`rows`, `cols`, length constraints, the
field-wrapper chrome, and the Twig template) is a faithful port; the `wire:model`
→ `data-model` binding, the `Htmlable` / `toEmbeddedHtml` → Twig template
translation, and placeholder escaping via Twig are systematic idiom translations
recorded in the [rename table](../renames.md), not divergences. The `required`
and `aria-invalid` attributes the template emits are `Field`-level concerns
(`CanBeMarkedAsRequired` and the error display), tracked in
[`field.md`](field.md), not here.
