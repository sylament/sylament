# HasExtraInputAttributes (forms)

`Sylament\Forms\Components\Concerns\HasExtraInputAttributes` — the escape hatch
for attributes on a field's **control element**, the sibling of `Field`'s
[`HasExtraFieldWrapperAttributes`](field.md), which reaches the wrapper `<div>`
instead. Every field that renders a control composes it — one control or one per
option: `TextInput`, `Textarea`, `Select`, `Checkbox`, `Radio` and
`ToggleButtons` — Filament's own set among the ported fields — plus `Toggle`,
which Filament pointedly leaves out and the port composes anyway
([divergence](toggle.md#the-toggle-carries-extra-input-attributes-filaments-does-not)).
[`Radio`](radio.md) is the first composer whose control is not singular, and it
reaches **every** radio in the group rather than picking one; the trait itself is
unchanged by that, since where the bag lands is each template's business, and
[`ToggleButtons`](toggle-buttons.md) is the second, across both of its templates.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php)
  — the trait
- [`Components/TextInput.php`](../../../vendor/filament/forms/src/Components/TextInput.php),
  [`Textarea.php`](../../../vendor/filament/forms/src/Components/Textarea.php),
  [`Select.php`](../../../vendor/filament/forms/src/Components/Select.php),
  [`Checkbox.php`](../../../vendor/filament/forms/src/Components/Checkbox.php),
  [`Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php),
  [`ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php)
  — the six composers the port shares with Filament, whose embedded views carry
  the merge order
- [`Components/Toggle.php`](../../../vendor/filament/forms/src/Components/Toggle.php)
  — the field Filament withholds the trait from, against the port's sixth composer
  ([divergence](toggle.md#the-toggle-carries-extra-input-attributes-filaments-does-not))

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `extraInputAttributes()` — the replace and `merge:` arms | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| `getExtraInputAttributes()` / `getExtraInputAttributeBag()` | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| Merged onto the control by each field's template | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#the-extras-beat-the-attributes-the-field-computes) |

Coverage: the empty default, the replace and merge arms, closure resolution
through `evaluate()`, and the bag getter, by
[`HasExtraInputAttributesTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasExtraInputAttributesTest.php),
which also pins that all seven fields compose it. That each template merges the
extras onto the right element, and that they outrank what the field computes, is
pinned per field by the `merges extra input attributes` case in
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php),
[`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php),
[`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php),
[`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php),
[`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php)
and
[`RadioTest`](../../../packages/forms/tests/Integration/Components/RadioTest.php)
and
[`ToggleButtonsTest`](../../../packages/forms/tests/Integration/Components/ToggleButtonsTest.php)
— `CheckboxTest` and `ToggleTest` also asserting the extras land on the `<input>`
rather than on the element wrapping it, `SelectTest` that no `<option>` picks them
up, and `RadioTest` and `ToggleButtonsTest` that **every** control gets them while
the group element gets none.

## Semantics

### The extras beat the attributes the field computes

`extraInputAttributes(['inputmode' => 'email'])` wins over the `inputmode` a
`numeric()` text input derives, and `['disabled' => false]` un-disables a
`disabled()` control. That is Filament's precedence rather than an addition:
`TextInput`, `Textarea`, `Select`, `Radio` and `ToggleButtons` each build the
control's attributes
as `$this->getExtraInputAttributeBag()->merge([…the field's own…])`, and a bag's
`merge()` treats its argument as **defaults** — so the extras are what survives.
([`Checkbox`](checkbox.md#the-checkboxs-extra-input-attributes-win-where-filaments-lose)
is the one exception among the five Filament also composes, and the port does not
follow it there;
[`Toggle`](toggle.md#the-toggle-carries-extra-input-attributes-filaments-does-not)
has no Filament order to inherit at all, since Filament composes no such trait on
it.)

The port spells the same thing as `attrs()` source order, which is
later-source-wins, so the extras are the **last** argument:
`attrs({class: 'sy-fo-input', …}, component.getExtraInputAttributes)`. It reads
backwards next to
[the field wrapper](field.md#extra-attribute-precedence-is-fixed-by-source-order),
where the extras source is *not* last — but the two settle different questions.
There, two user-facing setters contend for one element and the per-component one
wins; here, a user-facing setter contends with the field's own computed markup,
and the user wins. Reverse this one and the hatch can override nothing, which is
most of what an escape hatch is for.

Two attributes are exempt, and that is the bag's doing rather than a rule of this
trait: `class` and `style` are token lists
([`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php)
appends them where it replaces everything else), so an extra `class` adds to the
field's `sy-fo-*` hook rather than removing it.
