# Textarea (forms)

`Sylament\Forms\Components\Textarea` — the multi-line text field. Extends
[`Field`](../../../packages/forms/src/Components/Field.php) and adds the `rows` /
`cols` sizing setters on top of the shared length-constraint concern.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php)
  — the field class and the traits it composes
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the field-attribute concerns it layers on

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links a permanent note in [Divergences](#divergences) or
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `rows()` / `getRows()` | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| `cols()` / `getCols()` | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| Length constraints (`length` / `maxLength` / `minLength` + `Contracts\CanBeLengthConstrained`) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [detail](can-be-length-constrained.md) |
| Error display (`aria-invalid` on the `<textarea>`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](field.md#error-display-shows-every-message-for-the-path) |
| Embedded view → Twig template | [`Textarea::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | — |
| Input wrapper (`wrapInputHtml`) — no affix API, Filament-exact | [`Textarea::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [semantics](field.md#the-wrapper-owns-the-chrome-and-the-control-is-transparent) · [diverges](#the-control-keeps-the-sy-fo-input-class-filaments-textarea-carries-none) |
| [`autosize()` / `shouldAutosize()`](../../../packages/forms/src/Components/Textarea.php) | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [semantics](#the-sizer-grows-on-input-floors-at-the-initial-height-and-never-measures-a-hidden-control) · [diverges](#the-off-branchs-resize-mirror-is-not-ported) |
| [Autocomplete (`CanBeAutocompleted`)](../../../packages/forms/src/Components/Concerns/CanBeAutocompleted.php) | [`Concerns/CanBeAutocompleted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutocompleted.php) | done | — | [detail](can-be-autocompleted.md) |
| [Read-only (`CanBeReadOnly`)](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [detail](can-be-read-only.md) |
| [Disable Grammarly (`CanDisableGrammarly`)](../../../packages/forms/src/Components/Concerns/CanDisableGrammarly.php) | [`Concerns/CanDisableGrammarly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableGrammarly.php) | done | — | — |
| [Extra input attributes (`HasExtraInputAttributes`)](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| Extra Alpine attributes (`HasExtraAlpineAttributes`) | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| [Trim state (`CanTrimState`)](../../../packages/schemas/src/Components/Concerns/CanTrimState.php) | [`schemas/Concerns/CanTrimState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanTrimState.php) | done | — | [semantics](#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [Strip characters (`CanStripCharactersFromState`)](../../../packages/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | [`schemas/Concerns/CanStripCharactersFromState.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanStripCharactersFromState.php) | done | — | [semantics](#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [State casts + `mutate*` / `mutates*` hooks](../../../packages/forms/src/Components/Textarea.php) | [`Components/Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [semantics](#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| [Native constraint attributes drop when concealed](../../../packages/forms/templates/partials/Textarea/index.html.twig) — `required`, `minlength`, `maxlength` | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Textarea.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |

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
is schemas'. `TagsInput`, the third upstream consumer, landed with M3 · 9.

### The sizer grows on `input`, floors at the initial height, and never measures a hidden control

`autosize()` wraps the control in `div.sy-fo-textarea-sizer` inside the input
wrapper's content — never around the support-owned `.sy-input-wrp-content-ctn`,
which belongs to [`InputWrapper`](../support/input-wrapper.md) and is shared with
every other text-like control. The sizer carries the
[`sylament--forms--textarea`](../../../packages/forms/assets/src/controllers/textarea_controller.ts)
controller and an inline `height` of `((rows ?? 2) * 1.5) + 0.75` rem, so the box
is the right size in the server-rendered HTML before any JavaScript runs. That
height is derived from `rows`, not the `rows` attribute: an unset `rows` still
renders `3.75rem` and still emits no `rows` attribute.

The controller measures the control and writes the **sizer**: the control is
`height: 100%` of it, so writing the control's own height would fight the rule
that lets it fill. A measurement zeroes the control's height, reads
`scrollHeight`, restores it, and takes `max(contentHeight, initialHeight)` — the
initial height is a floor, so the box never shrinks below the row count the field
asked for.

Filament re-measures from an Alpine `$watch` on the entangled state; the state is
the browser's own here, so the trigger is the control's DOM `input` event, plus a
`window` resize listener and one `IntersectionObserver` firing once when the field
first reaches the screen. Both members short-circuit on `scrollHeight <= 0`: a
control inside an inactive [`Tab`](../schemas/tabs.md) measures zero, and writing
that measurement would collapse the box the moment the tab is opened. The height
the controller writes is an inline style, which survives a live re-render through
the mutation tracker's `style` replay.

## Divergences

### The off branch's resize mirror is not ported

Filament renders the sizer unconditionally and mounts its Alpine component either
way: with `autosize()` the component grows the sizer, and **without** it a
`ResizeObserver` mirrors whatever height the user dragged the control to onto the
sizer, because the sizer is the element the wrapper's `overflow-hidden` clips.
Neither half of that branch is ported. Without `autosize()` there is no sizer, no
controller and no `sy-autosizable` class — the rendered markup is byte-identical
to what shipped before autosize landed, and `textarea.sy-fo-input`'s own
`resize-y` hands the grip back to the platform with nothing to mirror it onto.
[`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php)
pins that off arm as an exact string, so the branch cannot grow an element by
accident.

It reverses by rendering the sizer unconditionally and giving the controller the
mirroring arm, which is worth doing only if the sizer ever carries chrome of its
own — today it carries only a height.

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
the same box either way. The two rules now do disagree, and the class still
stays: `.sy-fo-textarea.sy-autosizable textarea.sy-fo-input` beats the shared
`textarea.sy-fo-input` on specificity in either emission order, so autosize
overrides the `min-h-20 resize-y` pair with `h-full min-h-0 resize-none` without
moving a single declaration off the shared rule.

The rest of the ported subset (`rows`, `cols`, length constraints, the
field-wrapper chrome, and the Twig template) is a faithful port; the `wire:model`
→ `data-model` binding, the `Htmlable` / `toEmbeddedHtml` → Twig template
translation, and placeholder escaping via Twig are systematic idiom translations
recorded in the [rename table](../renames.md), not divergences. The `required`
and `aria-invalid` attributes the template emits are `Field`-level concerns
(`CanBeMarkedAsRequired` and the error display), tracked in
[`field.md`](field.md), not here.
