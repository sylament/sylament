# Field (forms)

`Sylament\Forms\Components\Field` — the abstract base every user-instantiable
form field extends. It adds field identity (name + state-path seeding), the
humanized-label fallback, the ten field-wrapper slots, the field-wrapper
rendering and the error-message read on top of the schemas `Component`, and
composes six field concerns. Five of them are **folded here**: each is a row in
the Surface table below (its detail and any divergence spliced in from its own
audit) and no longer carries a row in the [package README](README.md) Features
table. The sixth, [`CanBeValidated`](can-be-validated.md), keeps its own file —
its Filament surface is some eighty methods against the handful each folded
concern carries, and it is the field half of a vertical spanning three
packages. The schemas-level `HasName` / `HasLabel` / `HasState` it inherits stay
tracked in schemas, and so does
[`CanBeMarkedAsRequired`](../schemas/can-be-marked-as-required.md), which moved
down to schemas so its `Fieldset` could reach it.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php)
  — the base class, the traits it composes, and its `wrapEmbeddedHtml` /
  `wrapInputHtml` rendering
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the five folded field concerns audited below
- [`schemas/src/Components/Concerns/`](../../../vendor/filament/schemas/src/Components/Concerns)
  — the inherited `HasName` / `HasLabel` / `HasState` behind the identity rows

## Surface

Every point of this component and its five folded concerns. _Blocked by_ names
what has to land first. _Detail_ links the write-up in [Details](#details)
(deleted once the row is **done**), the permanent note in
[Divergences](#divergences), and/or a note carried by a concern's own file.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; `make()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#make-drops-getdefaultname-and-the-unique-name-exception) |
| [`HasName`](../../../packages/schemas/src/Components/Concerns/HasName.php) (schemas) | [`Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | — |
| [`HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) (schemas) | [`Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| State-path seeding ([`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php), schemas) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | done | — | — |
| `getLabel()` / `getDefaultLabel()` humanization | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#getdefaultlabel-drops-label-translation) |
| Field-wrapper slots (10 constants + setters, `makeChildSchema` / `configureChildSchema`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | — |
| Field-wrapper rendering (`FieldWrapper` template ⟵ `wrapEmbeddedHtml`), including the `labelPrefix` / `labelSuffix` label slots a boolean field fills with its control. `inlineLabelVerticalAlignment` is omitted pending schemas' `HasInlineLabel`, the only thing that varies it; `labelTag` ships under a different shape — see the row below. | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#the-label-carries-an-inline-control-modifier-class), [semantics](../schemas/can-be-marked-as-required.md#the-native-required-attribute-is-load-bearing-inside-a-modal-form) |
| `labelTag` (`label` vs `div`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#labeltag-is-a-boolean-labelasheading) |
| [`CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | in progress | — | [detail](can-be-validated.md) |
| Error display (`getErrorMessages()` ⟵ `hasErrorForPath` / `ViewErrorBag`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#error-display-shows-every-message-for-the-path) |
| Input affix wrapper (`wrapInputHtml`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | todo | — | [detail](#the-input-affix-wrapper-is-not-ported) |
| State casts &amp; enum defaults (`getDefaultStateCasts` / `getEnumDefaultStateCast`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | blocked | HasEnum | [detail](#state-casts-and-enum-defaults-are-not-ported) |
| `hasNullableBooleanState()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | todo | — | [detail](#hasnullablebooleanstate-is-not-ported) |
| [`HasExtraFieldWrapperAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | [`Components/Concerns/HasExtraFieldWrapperAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | done | — | [diverges](#extra-attribute-precedence-is-fixed-by-source-order) |
| [`CanBeAutofocused`](../../../packages/forms/src/Components/Concerns/CanBeAutofocused.php) | [`Components/Concerns/CanBeAutofocused.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php) | done | — | — |
| [`HasHelperText`](../../../packages/forms/src/Components/Concerns/HasHelperText.php) | [`Components/Concerns/HasHelperText.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php) | done | — | — |
| [`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) | [`Components/Concerns/HasHint.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php) | done | — | [diverges](#setuphint-narrows-its-parameter-to-field), [diverges](#gethintactions-returns-a-list-not-a-gapped-array) |
| [`HasPlaceholder`](../../../packages/forms/src/Components/Concerns/HasPlaceholder.php) | [`Components/Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [diverges](#hasplaceholder-is-composed-on-the-base-field) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### The input affix wrapper is not ported

Filament's `wrapInputHtml` renders the `fi-input-wrp` prefix/suffix chrome (affix
labels, icons, prefix/suffix actions, loading indicator, Alpine disabled/invalid
class binding). None of it is ported — the port has no `HasAffixes`, no
prefix/suffix API, and no input-wrapper template, so fields render their raw
control inside the field wrapper with no affix layer.

This row is the **root** of that surface: the wrapper is the prerequisite and
`HasAffixes` follows it, which is why
[text-input.md](text-input.md) and [select.md](select.md) both file their affix
rows as blocked *by this one*, and why this row names no blocker of its own.
Ordering it that way round is not arbitrary — a prefix setter with nowhere to
render is unusable and untestable, while the wrapper is a real improvement on its
own (it is where a field's focus ring belongs). What it waits on is markup and
styling rather than a dependency.
[`fi-input-wrp`](../../../vendor/filament/support/resources/css/components/input/wrapper.css)
is where the ring, background and `focus-within` state live
(`rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10`, plus
`focus-within:ring-2`), with a deliberately
[transparent `.fi-input`](../../../vendor/filament/support/resources/css/components/input/index.css)
inside it (`bg-transparent border-none focus:ring-0`); the port's
[`.sy-fo-input`](../../../packages/forms/assets/src/styles/components/input.css)
carries all three itself. So porting affixes is not additive — it moves that
styling onto a new `sy-fo-input-wrp` and touches every text-like field's
template.

That, and not the prebuilt affix actions, is what the affix surface waits on:
[`HasAffixes`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php)
never references `TextInput\Actions\CopyAction` / `ShowPasswordAction` /
`HidePasswordAction` — it takes any `Action` through `prefixAction()` /
`suffixAction()` and prepares it with `defaultSize()`, `defaultView()` and
schemas' `prepareAction()`, every one of which the port already has
([`defaultSize()`](../../../packages/actions/src/Concerns/HasSize.php),
`defaultView()` as
[`defaultTemplate()`](../../../packages/actions/src/Action.php),
[`prepareAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php)).
The three prebuilt actions are reached only from `TextInput::revealable()` and
`copyable()`, which is where [text-input.md](text-input.md) names them.

### State casts and enum defaults are not ported

Filament's Field overrides `getDefaultStateCasts()` to append an `EnumStateCast`
and exposes `getEnumDefaultStateCast()`, both driven by the `HasEnum` concern.
Neither is composed or ported. **Blocked** on the `HasEnum` port and the schemas
`StateCast` surface.

### `hasNullableBooleanState()` is not ported

Filament's Field declares `hasNullableBooleanState(): bool` (default `false`),
overridden by boolean fields to control tri-state casting. Not ported; no
consumer exists until the boolean fields (Checkbox, Toggle) land.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above.

### The label carries an inline-control modifier class

Filament's field-wrapper label element is hardcoded `class="fi-fo-field-label"`
([`Field.php`](../../../vendor/filament/forms/src/Components/Field.php)) — there
is no modifier for a label containing a control, and Filament reaches the
centring it needs through per-field CSS instead.

The port's
[`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig)
adds `sy-fo-field-label-inline-control` to the label whenever `labelPrefix` or
`labelSuffix` is filled. The base `.sy-fo-field-label` rule is
`items-start gap-x-3`, which suits a label stacked over multi-line hint text; a
checkbox sitting beside its label text wants `items-center` and a tighter gap,
and one modifier on the wrapper covers every boolean field rather than each
repeating the same override.

This is a Sylament-only class, and [`docs/styling.md`](../../styling.md) makes
every emitted `sy-` class part of the public hook surface consumers may target —
so it is recorded here rather than left to be discovered in a diff. Same category
as the `aria-invalid` addition below: a rendering choice the port makes and
Filament does not.

### `labelTag` is a boolean `labelAsHeading`

Filament's `wrapEmbeddedHtml()` takes `string $labelTag = 'label'` and
interpolates it as the wrapper's label element, swapping `<label for="…">` for
`<div id="…-label">`. It has nothing to do with inline labels: it is chosen
**per field type**, by every field that renders more than one control and
therefore cannot point a single `for` at one of them —
[`Radio`](../../../vendor/filament/forms/src/Components/Radio.php),
[`CheckboxList`](../../../vendor/filament/forms/src/Components/CheckboxList.php),
[`Repeater`](../../../vendor/filament/forms/src/Components/Repeater.php),
`Builder`, `FileUpload`, `KeyValue`, `MarkdownEditor`, `CodeEditor`, `Slider`,
`ToggleButtons`, `TableSelect` and `ModalTableSelect` all pass `labelTag: 'div'`,
and `LivewireField` picks between the two from `filled($id)`. Those two values
are the whole surface, and Filament's own template branches on
`$labelTag === 'label'` to decide `for="…"` against `id="…-label"` — the string
carries one bit.

The port's
[`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig)
takes that bit directly: a `labelAsHeading` boolean prop (default `false`)
picking between two written-out arms, in both the sr-only and the visible label
branch. A tag name would otherwise be interpolated into markup — a sink the
template would have to sanitize for no gain, since no field wants a third tag.
[`Repeater`](repeater.md)'s two partials are the first callers
(`:labelAsHeading="true"`, with `aria-labelledby` pointed at the `-label` id);
`Radio` / `CheckboxList` and the rest opt in the same way when they land.
Recorded in the [rename table](../renames.md). Reversible into a tag-name prop if
a field ever needs an element these two arms cannot spell.

### Error display shows every message for the path

Filament's `wrapEmbeddedHtml()` reads Laravel's `ViewErrorBag` through
`hasErrorForPath()` and renders **one** message per field unless
`showAllValidationMessages()` opts in, with `areHtmlValidationMessagesAllowed()`
choosing escaped or raw output. The port's
[`getErrorMessages()`](../../../packages/forms/src/Components/Field.php) returns
`list<string>` — every message the host bag holds for this field's absolute state
path, read through
[`getErrorsForSchemaStatePath()`](../schemas/can-be-validated.md) on the bound
live component (`[]` when the field has no host — `getLiveComponentOrNull()` is
typed `?InteractsWithSchemas`, so no `instanceof` guard is needed) — and the
`FieldWrapper` template loops the whole list.
That is the Symfony violation-list idiom: a `ConstraintViolationList` is
naturally plural, and the count is small in practice because every constraint
except `NotBlank` skips a `null` value, so a single field rarely produces two.
A blank *string* is a different matter — the port has
[no presence gate](can-be-validated.md#there-is-no-presence-gate-so-a-blank-string-reaches-every-constraint),
and `Length(min:)`, `Type('numeric')` and `GreaterThanOrEqual` each violate on
`''` — which is one reason the template loops rather than picking a first
message.
`showAllValidationMessages()` / `allowHtmlValidationMessages()` are
[skipped](can-be-validated.md) accordingly — the flags exist to opt *into* what
this port always does, and into raw HTML the port has no shape for. Reverses only
if a first-message-only default is wanted, which would make the flag meaningful
again.

The same list also drives the control's invalid state: `TextInput` /
`Textarea` render `aria-invalid` from `component.getErrorMessages is not empty`
and `.sy-fo-input[aria-invalid='true']` swaps the focus ring to the danger
token. Filament emits no `aria-invalid` anywhere — it toggles an `fi-invalid`
class, statically or through an Alpine binding
(`vendor/filament/forms/src/Components/Field.php:573`) — so the attribute is an
accessibility addition of this port, not a translation, and it renders with a
*value* rather than as a bare attribute because
[`ComponentAttributeBag`](../support/component-attribute-bag.md) maps a boolean
on an `aria-*` name to the strings `"true"` / `"false"` instead of dropping a
false one.

### `setUpHint()` narrows its parameter to Field

Filament's forms
[`HasHint`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php)
types the `afterLabel()` closure it registers `Field | Placeholder`. The port
types it `Field` alone, because
[`Placeholder`](../../../vendor/filament/forms/src/Components/Placeholder.php) is
not ported and the second arm would name a class that does not exist.

The arm is vestigial upstream as well, so this does not widen back when
`Placeholder` lands: Filament v5 deprecated `Placeholder` and re-parented it onto
[`TextEntry`](../../../vendor/filament/infolists/src/Components/TextEntry.php) →
[`Entry`](../../../vendor/filament/infolists/src/Components/Entry.php), which
composes the *infolists*
[`HasHint`](../../../vendor/filament/infolists/src/Components/Concerns/HasHint.php)
— a near-identical duplicate whose own closure is typed `Entry`. Nothing that
reaches the forms `setUpHint()` can be a `Placeholder` any more. The union comes
back only if the two traits are ever folded into one, which is the infolists
port's decision, not this file's.

### `getHintActions()` returns a list, not a gapped array

Filament's `getHintActions()` is `array_filter(array_map(…))`, which preserves the
original keys — a hint action whose closure resolves to null leaves the survivors
gapped, so two actions minus the first return `[1 => Action]`. The port wraps that
in `array_values()` and types the return `list<Action>`. Only the keys differ: the
one shipped consumer spreads the result into the after-label slot, which renumbers
regardless, while a positional read is the shape a caller actually writes and the
gap makes it silently miss.

### HasPlaceholder is composed on the base Field

Filament does **not** put `HasPlaceholder` on `Field`; it composes the trait onto
each individual component the input-placeholder attribute makes sense for —
[`TextInput`](../../../vendor/filament/forms/src/Components/TextInput.php),
[`Textarea`](../../../vendor/filament/forms/src/Components/Textarea.php),
[`Select`](../../../vendor/filament/forms/src/Components/Select.php),
[`DateTimePicker`](../../../vendor/filament/forms/src/Components/DateTimePicker.php),
[`ColorPicker`](../../../vendor/filament/forms/src/Components/ColorPicker.php),
[`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php),
[`TagsInput`](../../../vendor/filament/forms/src/Components/TagsInput.php),
[`MarkdownEditor`](../../../vendor/filament/forms/src/Components/MarkdownEditor.php),
[`RichEditor`](../../../vendor/filament/forms/src/Components/RichEditor.php),
[`OneTimeCodeInput`](../../../vendor/filament/forms/src/Components/OneTimeCodeInput.php),
and [`ModalTableSelect`](../../../vendor/filament/forms/src/Components/ModalTableSelect.php).
Sylament's [`Field`](../../../packages/forms/src/Components/Field.php) uses the
trait directly, so `placeholder()` / `getPlaceholder()` are available on every
field. This follows the same uniform-fluent-composition choice CLAUDE.md records
for `HasColumns` (a trait on the base even where only some subclasses use it):
fields where a placeholder is meaningless (e.g. `Checkbox`, `Toggle`) inherit a
harmless unused setter. Reversible by dropping `use HasPlaceholder` from `Field`
and composing it per-component if a field ever needs a conflicting `placeholder()`
meaning.

### `make()` drops getDefaultName and the unique-name exception

Filament's `make()` resolves `static::getDefaultName()` and throws
`InvalidArgumentException` when the name is still null, before constructing
through `app($fieldClass, ['name' => $name])`. The port's
[`make()`](../../../packages/forms/src/Components/Field.php) is
`static::newInstance(); $field->name($name); $field->statePath($name);
$field->configure();` — construction goes through
[`Component::newInstance()`](../../../packages/support/src/Components/Component.php)
(the container when the app registered the class, `new $class()` otherwise), the
same seam every other `make()` in the epic uses, so a `Field` subclass can
already declare constructor-injected dependencies. What is still missing is the
name handling either side of that seam: there is no `getDefaultName()` hook and
no unique-name guard, so `name(?string $name)` accepts and keeps a null
name — anonymous fields are allowed (a null name yields a null state path and a
null label, both covered by tests) where Filament's would throw.

### `getDefaultLabel` drops label translation

Filament's `getDefaultLabel` runs the humanized string through `__()` when
`shouldTranslateLabel` is set and always returns a non-null `string`. The port's
[`getDefaultLabel`](../../../packages/forms/src/Components/Field.php) returns
`?string` (null when the name is null, per the anonymous-field divergence) and has
no translation step — the label-translation surface (`translateLabel()`) is not
ported. Humanization itself is faithful:
`u($name)->afterLast('.')->kebab()->replace('-', ' ')->title()` matches Filament's
`str(...)->afterLast('.')->kebab()->replace(['-','_'],' ')->ucfirst()` on the
tested cases.

### Extra-attribute precedence is fixed by source order

In Filament the two setters never meet: `extraFieldWrapperAttributes()` is the
only one that reaches the field wrapper, and the schemas-level
`extraAttributes()` lands on the field's **own inner element** —
[`TextInput`](../../../vendor/filament/forms/src/Components/TextInput.php) merges
it into the `fi-fo-text-input` div it hands to `wrapInputHtml()`, `Textarea`,
`Select`, `KeyValue` and `CodeEditor` onto their equivalent inner div, and
[`Checkbox`](../../../vendor/filament/forms/src/Components/Checkbox.php) and
`Toggle` straight onto the control element. So there is no precedence to
inherit.

The `merge()` chain in
[`wrapEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Field.php) —
`(new Bag)->merge($extraWrapperAttributes)->merge($this->getExtraFieldWrapperAttributes())`
— is not that contest either. `$extraWrapperAttributes` is the method's own
parameter, and all five of its callers pass the same shape: a hardcoded
per-field wrapper class, and nothing else that varies —
[`ToggleButtons`](../../../vendor/filament/forms/src/Components/ToggleButtons.php)
`fi-fo-toggle-buttons-wrp` (from two call sites, and the only one to add
anything further, a `tabindex` of `-1`),
[`TagsInput`](../../../vendor/filament/forms/src/Components/TagsInput.php)
`fi-fo-tags-input-wrp`,
[`Select`](../../../vendor/filament/forms/src/Components/Select.php)
`fi-fo-select-wrp`,
[`Textarea`](../../../vendor/filament/forms/src/Components/Textarea.php)
`fi-fo-textarea-wrp`, and
[`KeyValue`](../../../vendor/filament/forms/src/Components/KeyValue.php)
`fi-fo-key-value-wrp`. It is the seam through which a field names its **own**
wrapper, never a route for a user-supplied value — so each of those five carries
a `sy-fo-*-wrp` hook of its own into the port as it lands, and reading the
parameter as a `ToggleButtons` quirk would silently cost the other four theirs.

The port has no inner element to put `extraAttributes()` on, because the
[input affix wrapper is not ported](#the-input-affix-wrapper-is-not-ported) — the
field wrapper `<div>` is the only element either setter can reach, so one has to
win. The port spells that as `attrs()` source order, which is later-source-wins:
`attrs({class: [...]}, field.getExtraFieldWrapperAttributes, field.getExtraAttributes)`.
`extraAttributes()` wins, for two reasons. It already rendered on that `<div>`
before this trait existed, so composing the trait cannot silently override an
attribute a consumer had already set. And it keeps the per-component setter
authoritative over the wrapper one, which is the relationship Filament expresses
by putting it on the inner element.

Revisit when `HasAffixes` lands: `extraAttributes()` moves onto the input wrapper
where Filament has it, the two setters stop sharing an element, and the ordering
stops mattering.

_See also: the global [rename table](../renames.md) — the field-wrapper
rendering leans on the systematic `toEmbeddedHtml()`/Blade → Twig template,
`Htmlable` → `Markup`, and `wire:model` → `data-model` (Stimulus) translations
recorded there, none of which is a divergence._
