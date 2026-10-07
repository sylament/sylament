---
description: The abstract base every user-instantiable form field extends, with its field wrapper and the field concerns it composes.
ports:
  - vendor/filament/forms/src/Components/Field.php
  - vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php
  - vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php
  - vendor/filament/forms/src/Components/Concerns/HasHelperText.php
  - vendor/filament/forms/src/Components/Concerns/HasHint.php
  - vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php
  - vendor/filament/forms/resources/views/components/field-wrapper.blade.php
owns:
  - packages/forms/src/Components/Concerns/CanBeAutofocused.php
  - packages/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php
  - packages/forms/src/Components/Concerns/HasHelperText.php
  - packages/forms/src/Components/Concerns/HasHint.php
  - packages/forms/src/Components/Concerns/HasPlaceholder.php
  - packages/forms/src/Components/Concerns/ResolvesRepeaterSiblingContext.php
  - packages/forms/src/Components/Field.php
  - packages/forms/src/State/RepeaterSiblingContext.php
  - packages/forms/templates/components/FieldWrapper/
---
# Field (forms)

`Sylament\Forms\Components\Field` is the abstract base every user-instantiable form field extends, on top of the schemas [`Component`](../schemas/component.md).
It adds field identity (name and state-path seeding), the humanized-label fallback, the field-wrapper slots and rendering, the error-message read and the enum state cast, and composes the field concerns its Surface rows list.
[`CanBeValidated`](can-be-validated.md) and [`HasEnum`](has-enum.md) keep files of their own: the first is the field half of a vertical spanning three packages, and the second is consumed across the option fields.
The schemas-level `HasName`, `HasLabel` and `HasState` it inherits are tracked in schemas, and so is [`CanBeMarkedAsRequired`](../schemas/can-be-marked-as-required.md), which the port keeps in schemas so `Fieldset` can reach it.
The Surface table carries no [unaudited row](../../internals/trail.md#an-unaudited-table-says-so) because every member of the vendor class and of the concerns it folds has a row.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `getDefaultName()`](../../../packages/forms/src/Components/Field.php) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#the-name-moves-from-the-constructor-to-a-setter) |
| [`HasName`](../../../packages/schemas/src/Components/Concerns/HasName.php) (schemas) | [`Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | [has-name.md](../schemas/has-name.md) |
| [`HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) (schemas) | [`Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | [has-label.md](../schemas/has-label.md) |
| State-path seeding through [`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php) (schemas) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | done | — | [component.md](../schemas/component.md) |
| [`getLabel()` / `getDefaultLabel()`](../../../packages/forms/src/Components/Field.php) humanization | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#getdefaultlabel-drops-label-translation) |
| [Field-wrapper slots](../../../packages/forms/src/Components/Field.php): the ten constants and setters, `makeChildSchema()` / `configureChildSchema()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | — |
| [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig), with the `labelPrefix` / `labelSuffix` slots | [`Field::wrapEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#the-label-carries-an-inline-control-modifier-class) · [diverges](#the-native-required-attribute-follows-ismarkedasrequired-not-filaments-isrequired) |
| `inlineLabelVerticalAlignment` | [`Field::wrapEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Field.php) | blocked | schemas' `HasInlineLabel` | [detail](#inlinelabelverticalalignment-waits-on-schemas-hasinlinelabel) |
| [The plain-wrapper branch](../../../packages/forms/templates/components/FieldWrapper/index.html.twig), chosen by `FusedGroup::PLAIN_FIELD_WRAPPER_VIEW` | [`plain-field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/plain-field-wrapper.blade.php) | done | — | [fused-group.md](fused-group.md) |
| `labelTag` as `labelAsHeading` | [`Field::wrapEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#labeltag-is-a-boolean-labelasheading) |
| [`CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | in progress | — | [detail](can-be-validated.md) |
| [`getErrorMessages()`](../../../packages/forms/src/Components/Field.php), for `hasErrorForPath()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#error-display-shows-every-message-for-the-path) |
| Input affix wrapper (`wrapInputHtml()`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [semantics](#the-wrapper-owns-the-chrome-and-the-control-is-transparent) |
| [`getDefaultStateCasts()` / `getEnumDefaultStateCast()`](../../../packages/forms/src/Components/Field.php) and [`HasEnum`](../../../packages/forms/src/Components/Concerns/HasEnum.php) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) · [`Concerns/HasEnum.php`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | done | — | [detail](has-enum.md) |
| [`hasNullableBooleanState()`](../../../packages/forms/src/Components/Field.php) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [semantics](#hasnullablebooleanstate-has-exactly-one-consumer) |
| [`HasExtraFieldWrapperAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | [`Concerns/HasExtraFieldWrapperAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | done | — | [diverges](#extra-attribute-precedence-is-fixed-by-source-order) |
| [`CanBeAutofocused`](../../../packages/forms/src/Components/Concerns/CanBeAutofocused.php) | [`Concerns/CanBeAutofocused.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php) | done | — | — |
| [`HasHelperText`](../../../packages/forms/src/Components/Concerns/HasHelperText.php) | [`Concerns/HasHelperText.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php) | done | — | — |
| [`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) | [`Concerns/HasHint.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php) | done | — | [diverges](#setuphint-narrows-its-parameter-to-field) · [diverges](#gethintactions-returns-a-list-not-a-gapped-array) |
| [`HasPlaceholder`](../../../packages/forms/src/Components/Concerns/HasPlaceholder.php) | [`Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [diverges](#hasplaceholder-is-composed-on-the-base-field) |
| [`ResolvesRepeaterSiblingContext`](../../../packages/forms/src/Components/Concerns/ResolvesRepeaterSiblingContext.php), no Filament concern of its own | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [diverges](#the-five-sibling-facts-are-one-value-object-not-string-surgery) |
| [`data-field-wrapper` on the wrapper root](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) | [`field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/field-wrapper.blade.php) · [`plain-field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/plain-field-wrapper.blade.php) | done | — | [semantics](#data-field-wrapper-is-the-scroll-target-of-a-validation-error) |

## Details

### `inlineLabelVerticalAlignment` waits on schemas' HasInlineLabel

Filament's `wrapEmbeddedHtml()` takes an `inlineLabelVerticalAlignment` parameter, which `TextInput`, `DateTimePicker`, `ColorPicker`, `Checkbox`, `Toggle` and `Slider` pass as `VerticalAlignment::Center`, and renders it as a `fi-vertical-align-*` class on the label column only when the field has an inline label.
The port's [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) takes no such prop, because nothing renders an inline label: schemas' `HasInlineLabel` is unported.
What exists is the label column and its base rules; what is missing is the prop, the class and the `field.css` arms [styles.md records](styles.md#the-inline-label-arms-wait-on-schemas-hasinlinelabel).

## Semantics

### The wrapper owns the chrome and the control is transparent

`wrapInputHtml()` is support's [`InputWrapper`](../support/input-wrapper.md), and the text-like controls render inside it: [`TextInput`](text-input.md), [`Textarea`](textarea.md), [`Select`](select.md), the [`DateTimePicker`](date-time-picker.md) family, [`TagsInput`](tags-input.md), [`KeyValue`](key-value.md) and [`CheckboxList`](checkbox-list.md)'s search box.
The ring, background, shadow, hover and `focus-within` state sit on `.sy-input-wrp`, and `.sy-fo-input` is `appearance-none border-none bg-transparent focus:ring-0`, which is Filament's split between `.fi-input-wrp` and `input.fi-input`.

Two type-specific rules in [`input.css`](../../../packages/forms/assets/src/styles/components/input.css) are Filament's verbatim, and both are live because the control is transparent:

- **`input[type='date'|'datetime-local'|'time'].sy-fo-input { bg-white/[1%] }`** exists because Safari colours a fully transparent date field's "placeholder" too dark ([#7087](https://github.com/filamentphp/filament/issues/7087), [#18570](https://github.com/filamentphp/filament/issues/18570)).
  A hair of opacity fixes it without disturbing the opaque wrapper behind it.
- **`input[type='range'].sy-fo-input`'s `appearance-auto`** undoes the base rule's `appearance-none`, and its `mx-auto` and `width: calc(100% - 1.5rem)` fit the slider inside the wrapper.

### `hasNullableBooleanState()` has exactly one consumer

The predicate declares whether a boolean-valued state has an "unset" resting value beside `true` and `false`.
`Field` answers `false`; three fields override it to `true`, [`Select`](select.md), `Radio` and `ToggleButtons`, each because its placeholder or unselected state is a real third value.
`Checkbox` and `Toggle` do not, which is the natural guess and is wrong: both cast through a non-nullable `BooleanStateCast`, where `false` is the resting value.
It drives no state cast, which is the other natural guess.

Its **only** consumer, here as in Filament, is the filter reset in tables' [`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php): `is_bool($state) => $fieldComponent->hasNullableBooleanState() ? null : false`.
So a custom `Filter::make(…)->schema([Select::make(…)->boolean()])` that declares no `resetState()` of its own [clears to the placeholder](../tables/host-has-filters.md#filter-mutations-reset-the-page-and-flush-the-record-cache) rather than flipping to its false arm.

### `data-field-wrapper` is the scroll target of a validation error

Both wrapper branches carry `data-field-wrapper` on their root, as Filament's two wrapper views do.
Schemas' root controller scrolls the nearest `[data-field-wrapper]` of the first rendered validation error into view ([`schema_controller.ts`](../../../packages/schemas/assets/src/controllers/schema_controller.ts)), so the attribute is load-bearing although nothing in forms reads it.

## Divergences

### The label carries an inline-control modifier class

Filament's field-wrapper label element is hardcoded `class="fi-fo-field-label"` ([`Field.php`](../../../vendor/filament/forms/src/Components/Field.php)): there is no modifier for a label containing a control, and Filament reaches the centring it needs through per-field CSS instead.

The port's [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) adds `sy-fo-field-label-inline-control` to the label whenever `labelPrefix` or `labelSuffix` is filled.
The base `.sy-fo-field-label` rule is `items-start gap-x-3`, which suits a label stacked over multi-line hint text; a checkbox beside its label text wants `items-center` and a tighter gap, and one modifier on the wrapper covers every boolean field rather than each repeating the same override.

This is a Sylament-only class, and [`docs/internals/styling.md`](../../internals/styling.md#why-are-the-emitted-classes-public) makes every emitted `sy-` class part of the public hook surface consumers may target, so it is recorded here rather than left to be discovered in a diff.

### `labelTag` is a boolean `labelAsHeading`

Filament's `wrapEmbeddedHtml()` takes `string $labelTag = 'label'` and interpolates it as the wrapper's label element, swapping `<label for="…">` for `<div id="…-label">`.
It has nothing to do with inline labels: it is chosen **per field type**, by every field that renders more than one control and so cannot point a single `for` at one of them.
[`Radio`](../../../vendor/filament/forms/src/Components/Radio.php), [`CheckboxList`](../../../vendor/filament/forms/src/Components/CheckboxList.php), [`Repeater`](../../../vendor/filament/forms/src/Components/Repeater.php), `Builder`, `FileUpload`, `KeyValue`, `MarkdownEditor`, `RichEditor`, `CodeEditor`, `Slider`, `ToggleButtons`, `TableSelect` and `ModalTableSelect` all pass `labelTag: 'div'`, and `LivewireField` picks between the two from `filled($id)`.
Those two values are the whole surface, and Filament's own template branches on `$labelTag === 'label'` to decide `for="…"` against `id="…-label"`, so the string carries one bit.

The port's [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) takes that bit directly: a `labelAsHeading` boolean prop (default `false`) picking between two written-out arms, in both the sr-only and the visible label branch.
A tag name would otherwise be interpolated into markup, a sink the template would have to sanitize for no gain, because no field wants a third tag.
Every ported field Filament passes `labelTag: 'div'` for passes `:labelAsHeading="true"`: [`Repeater`](repeater.md)'s partials, [`Radio`](radio.md), [`CheckboxList`](checkbox-list.md), both [`ToggleButtons`](toggle-buttons.md) variants, [`FileUpload`](file-upload.md) and [`KeyValue`](key-value.md), and so does [`FusedGroup`](fused-group.md).
The rename is [recorded in the rename table](../renames.md#a-tag-name-template-prop-narrows-to-what-it-selects).
It reverses into a tag-name prop if a field ever needs an element these two arms cannot spell.

### Error display shows every message for the path

Filament's `wrapEmbeddedHtml()` reads Laravel's shared `ViewErrorBag` and renders **one** message per field unless `showAllValidationMessages()` opts in, with `areHtmlValidationMessagesAllowed()` choosing escaped or raw output; `hasErrorForPath()` answers the invalid state for `wrapInputHtml()` and for the `Checkbox`, `Radio` and `CheckboxList` controls.
The port's [`getErrorMessages()`](../../../packages/forms/src/Components/Field.php) returns `list<string>`, every message the host bag holds for this field's absolute state path, read through [`getErrorsForSchemaStatePath()`](../schemas/can-be-validated.md) on the bound live component (`[]` when the field has no host), and the `FieldWrapper` template loops the whole list.
That is the Symfony violation-list idiom: a `ConstraintViolationList` is naturally plural, and the count is small in practice because every constraint except the required one skips a `null` value, and the [presence gate](../schemas/presence-gate.md) keeps a blank string from reaching the validator, so a `required()` field's `''` reports one message.
The template still loops rather than picking a first message, because a filled value can fail several arms at once (a too-short non-numeric string under `numeric()->minLength()`), and because picking one would make *which* one a template concern.
`showAllValidationMessages()` / `allowHtmlValidationMessages()` are [skipped](can-be-validated.md) accordingly: the flags exist to opt *into* what this port always does, and into raw HTML the port has no shape for.
It reverses only if a first-message-only default is wanted, which would make the flag meaningful again.

The same list drives the control's invalid state: the controls render `aria-invalid` from `component.getErrorMessages is not empty`, and the text-like ones pass the inverse to the input wrapper's `valid` prop, whose `sy-invalid` class swaps the ring to the danger token.
The attribute is on the control for accessibility rather than for styling, since the ring sits on [the input wrapper](#the-wrapper-owns-the-chrome-and-the-control-is-transparent).
Filament's fields emit no `aria-invalid`; they toggle an `fi-invalid` class, statically or through an Alpine binding (`vendor/filament/forms/src/Components/Field.php:573`), and only `FusedGroup`'s inner group carries the attribute (`vendor/filament/schemas/src/Components/FusedGroup.php:293`).
So on a field the attribute is an accessibility addition of this port, not a translation, and it renders with a *value* rather than as a bare attribute because [`ComponentAttributeBag`](../support/component-attribute-bag.md) maps a boolean on an `aria-*` name to the strings `"true"` / `"false"` instead of dropping a false one.
[`TagsInput`](tags-input.md#the-text-box-is-scratch-state-and-carries-no-data-model) and [`KeyValue`](key-value.md#every-cell-binds-by-row-index-and-a-disabled-cell-keeps-its-binding) are the text-like controls that render none, for one reason: neither has a control that *is* the field's value.
The tags input's box holds the tag being typed rather than the state, and the key-value field has no single control at all, each cell binding one half of one row, so in both the invalid state rides the input wrapper's `sy-invalid` and the field wrapper's messages alone, which is also where Filament leaves it.

The list's two classes drop Filament's `-wrp-` segment, `.sy-fo-field-error-list` / `.sy-fo-field-error-message` for `fi-fo-field-wrp-error-list` / `fi-fo-field-wrp-error-message`, which is a [recorded rename](../renames.md#the-error-list-classes-drop-the-wrapper-segment-filament-renders-nowhere) rather than the systematic `fi-` to `sy-` swap.

### `setUpHint()` narrows its parameter to Field

Filament's forms [`HasHint`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php) types the `afterLabel()` closure it registers `Field | Placeholder`.
The port types it `Field` alone, because [`Placeholder`](../../../vendor/filament/forms/src/Components/Placeholder.php) is not ported and the second arm would name a class that does not exist.

The arm is vestigial upstream as well, so it does not widen back when `Placeholder` lands: Filament deprecates `Placeholder` and parents it on [`TextEntry`](../../../vendor/filament/infolists/src/Components/TextEntry.php), whose [`Entry`](../../../vendor/filament/infolists/src/Components/Entry.php) base composes the *infolists* [`HasHint`](../../../vendor/filament/infolists/src/Components/Concerns/HasHint.php), a near-identical duplicate whose own closure is typed `Entry`.
Nothing that reaches the forms `setUpHint()` can be a `Placeholder`.
The union comes back only if the two traits are folded into one, which is the infolists port's decision, not this file's.

### `getHintActions()` returns a list, not a gapped array

Filament's `getHintActions()` is `array_filter(array_map(…))`, which preserves the original keys: a hint action whose closure resolves to null leaves the survivors gapped, so two actions minus the first return `[1 => Action]`.
The port wraps that in `array_values()` and types the return `list<Action>`.
Only the keys differ: the one shipped consumer spreads the result into the after-label slot, which renumbers regardless, while a positional read is the shape a caller writes and the gap makes it silently miss.

### HasPlaceholder is composed on the base Field

Filament does **not** put `HasPlaceholder` on `Field`; it composes the trait onto each component the input-placeholder attribute makes sense for: [`TextInput`](../../../vendor/filament/forms/src/Components/TextInput.php), [`Textarea`](../../../vendor/filament/forms/src/Components/Textarea.php), [`Select`](../../../vendor/filament/forms/src/Components/Select.php), [`DateTimePicker`](../../../vendor/filament/forms/src/Components/DateTimePicker.php), [`ColorPicker`](../../../vendor/filament/forms/src/Components/ColorPicker.php), [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php), [`TagsInput`](../../../vendor/filament/forms/src/Components/TagsInput.php), [`MarkdownEditor`](../../../vendor/filament/forms/src/Components/MarkdownEditor.php), [`RichEditor`](../../../vendor/filament/forms/src/Components/RichEditor.php), [`OneTimeCodeInput`](../../../vendor/filament/forms/src/Components/OneTimeCodeInput.php) and [`ModalTableSelect`](../../../vendor/filament/forms/src/Components/ModalTableSelect.php).
Sylament's [`Field`](../../../packages/forms/src/Components/Field.php) uses the trait directly, so `placeholder()` / `getPlaceholder()` are available on every field.
`.claude/rules/php.md` keeps Filament's trait placement unless a divergence is argued, and the argument here is uniform fluent composition: fields where a placeholder is meaningless (`Checkbox`, `Toggle`) inherit a harmless unused setter.
It reverses by dropping `use HasPlaceholder` from `Field` and composing it per component if a field ever needs a conflicting `placeholder()` meaning.

### The name moves from the constructor to a setter

Filament's `final __construct(string $name)` is dropped, as everywhere else in the port, because the resolver path takes no constructor arguments.
`make()` resolves the name (argument, then `static::getDefaultName()`), refuses a blank one, then runs `newInstance()`, `name()`, `statePath()` and `configure()`, matching [`Column::make()`](../tables/column.md) and [`BaseFilter::make()`](../tables/base-filter.md#the-name-moves-from-the-constructor-to-a-setter).
Construction goes through [`Component::newInstance()`](../../../packages/support/src/Components/Component.php) (the container when the app registered the class, `new $class()` otherwise), so a `Field` subclass can declare constructor-injected dependencies where Filament's `final` constructor would have to be worked around.
Both name assignments land before `configure()`, which is where the constructor put them, so a `configureUsing()` closure reads the same field either way.

The guard is one notch stricter than the class it ports.
Filament's `Field::make()` tests `$name === null`; the port tests `blank($name)`, so `make('')` is refused too, because an empty name seeds an empty state path and leaves the same inert field a null name would.
Filament guards its own `Column` and `BaseFilter` with `blank()`, so this is its newer shape rather than a new one.
The exception class stays faithful per class rather than uniform across the three: `InvalidArgumentException` here, `LogicException` in the two tables ports, because that is what each original throws, and the first extends the second, so a single `catch (LogicException)` covers all three.

A name is therefore never null coming out of `make()`.
It can still be nulled afterwards: the inherited [`HasName::name()`](../../../packages/schemas/src/Components/Concerns/HasName.php) and [`HasState::statePath()`](../../../packages/schemas/src/Components/Concerns/HasState.php) setters are both nullable, and the label and validation-dehydration paths that read them keep their null branches.

### `getDefaultLabel` drops label translation

Filament's `getDefaultLabel` runs the humanized string through `__()` when `shouldTranslateLabel` is set and always returns a non-null `string`.
The port's [`getDefaultLabel`](../../../packages/forms/src/Components/Field.php) returns `?string` (null when the name is null, which [`make()` never produces](#the-name-moves-from-the-constructor-to-a-setter) but the nullable `name()` setter admits) and has no translation step, because the label-translation surface (`translateLabel()`) is not ported.
Humanization itself is faithful: `u($name)->afterLast('.')->kebab()->replace('-', ' ')->title()` matches Filament's `str(...)->afterLast('.')->kebab()->replace(['-','_'],' ')->ucfirst()` on the tested cases.

### Extra-attribute precedence is fixed by source order

In Filament the two setters never meet: `extraFieldWrapperAttributes()` is the only one that reaches the field wrapper, and the schemas-level `extraAttributes()` lands on the field's **own inner element**: [`TextInput`](../../../vendor/filament/forms/src/Components/TextInput.php) merges it into the `fi-fo-text-input` element it hands to `wrapInputHtml()`, `Textarea`, `Select`, `KeyValue` and `CodeEditor` onto their equivalent inner element, and [`Checkbox`](../../../vendor/filament/forms/src/Components/Checkbox.php) and `Toggle` straight onto the control.
So there is no precedence to inherit.

The `merge()` chain in [`wrapEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Field.php), `(new Bag)->merge($extraWrapperAttributes)->merge($this->getExtraFieldWrapperAttributes())`, is not that contest either.
`$extraWrapperAttributes` is the method's own parameter, and its five callers pass one shape, a hardcoded per-field wrapper class: [`ToggleButtons`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) `fi-fo-toggle-buttons-wrp` (from two call sites, and the only one to add anything further, a `tabindex` of `-1`), [`TagsInput`](../../../vendor/filament/forms/src/Components/TagsInput.php) `fi-fo-tags-input-wrp`, [`Select`](../../../vendor/filament/forms/src/Components/Select.php) `fi-fo-select-wrp`, [`Textarea`](../../../vendor/filament/forms/src/Components/Textarea.php) `fi-fo-textarea-wrp` and [`KeyValue`](../../../vendor/filament/forms/src/Components/KeyValue.php) `fi-fo-key-value-wrp`.
It is the seam through which a field names its **own** wrapper, never a route for a user-supplied value.
All five fields are ported and none carries a `sy-fo-*-wrp` hook: each renders inside the shared field wrapper with no extra class, because the rule each hook carries in vendor is an inline-label arm that [waits on schemas' `HasInlineLabel`](styles.md#the-inline-label-arms-wait-on-schemas-hasinlinelabel), except `fi-fo-toggle-buttons-wrp`, which vendor's `repeater.css` also targets for its table layout.
A hook arrives with the rule that needs it.

The port puts neither setter on an inner element: the input wrapper takes no `extraAttributes()`, so the field wrapper `<div>` is the only element either setter reaches and one has to win.
The port spells that as `attrs()` source order, which is later-source-wins: `attrs({class: [...]}, field.getExtraFieldWrapperAttributes, field.getExtraAttributes)`.
`extraAttributes()` wins, because it keeps the per-component setter authoritative over the wrapper one, which is the relationship Filament expresses by putting it on the inner element.

It is revisited by routing `extraAttributes()` onto the input wrapper, where Filament has it; the two setters then stop sharing one element and the ordering stops mattering.

### The native `required` attribute follows `isMarkedAsRequired()`, not Filament's `isRequired()`

Filament emits a control's native `required` attribute from `isRequired()` in `TextInput`, `Textarea`, `Checkbox`, `Select` and `DateTimePicker`, and only the field wrapper's asterisk reads `isMarkedAsRequired()`.
The port's `TextInput`, `Textarea`, `Checkbox`, `DateTimePicker` and native `Select` templates emit `required` from `component.isMarkedAsRequired and not component.isConcealed`, so one getter drives both the asterisk and the attribute.
The two agree unless `markAsRequired()` is called explicitly: `markAsRequired()` on a field with no required constraint blocks submission in the browser while the server validates nothing, and `markAsRequired(false)` on a required field drops the attribute Filament keeps.
The attribute does work inside a mounted action's modal, which renders as a real `<form>` ([support/modal.md](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit)): the browser fires `submit` only after native constraint validation passes, so an empty field carrying `required` stops the request on the client.
No reason for following the marker is recorded; emitting the attribute from `isRequired()` would restore Filament's split.

### The five sibling facts are one value object, not string surgery

Filament derives the item key and the field's path within an item by cutting the component's state path at the first dot after the repeater's (`->after("{$repeaterStatePath}.")->after('.')`), and `CanFixIndistinctState` and `CanDisableOptionsWhenSelectedInSiblingRepeaterItems` each recompute that inline.
The port resolves the same five facts (the repeater, the item key, the field path, the items prefix and the sibling keys) once, through [`ResolvesRepeaterSiblingContext`](../../../packages/forms/src/Components/Concerns/ResolvesRepeaterSiblingContext.php) on `Field`, which answers a readonly [`RepeaterSiblingContext`](../../../packages/forms/src/State/RepeaterSiblingContext.php) or `null`.
The field path is taken by stripping the item schema's own prefix rather than by cutting at a dot, which survives an item key holding one and a layout component sitting between the item schema and the field.
`distinct()`, [`fixIndistinctState()`](can-fix-indistinct-state.md) and [`disableOptionsWhenSelectedInSiblingRepeaterItems()`](can-disable-options-when-selected-in-sibling-repeater-items.md) all read this one block.
`null` means "outside a repeater item, or the only item", and each consumer then leaves the siblings alone.
