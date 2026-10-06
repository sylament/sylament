# Field (forms)

`Sylament\Forms\Components\Field` — the abstract base every user-instantiable
form field extends. It adds field identity (name + state-path seeding), the
humanized-label fallback, the ten field-wrapper slots, the field-wrapper
rendering, the error-message read and the enum state cast on top of the schemas
`Component`, and
composes seven field concerns. Five of them are **folded here**: each is a row in
the Surface table below (its detail and any divergence spliced in from its own
audit) and no longer carries a row in the [package README](README.md) Features
table. The sixth, [`CanBeValidated`](can-be-validated.md), keeps its own file —
its Filament surface is some eighty methods against the handful each folded
concern carries, and it is the field half of a vertical spanning three
packages. The seventh, [`HasEnum`](has-enum.md), keeps one too: it is composed
here but consumed across the option fields, and its own file carries the
`Field`-tier cast plumbing (`getDefaultStateCasts()` /
`getEnumDefaultStateCast()`) that the Surface table below rows for.
The schemas-level `HasName` / `HasLabel` / `HasState` it inherits stay
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
what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics) or [Divergences](#divergences), and/or a note carried by
a concern's own file.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; `make()` / `getDefaultName()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#the-name-moves-from-the-constructor-to-a-setter) |
| [`HasName`](../../../packages/schemas/src/Components/Concerns/HasName.php) (schemas) | [`Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | — |
| [`HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) (schemas) | [`Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| State-path seeding ([`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php), schemas) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | done | — | — |
| `getLabel()` / `getDefaultLabel()` humanization | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#getdefaultlabel-drops-label-translation) |
| Field-wrapper slots (10 constants + setters, `makeChildSchema` / `configureChildSchema`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | — |
| Field-wrapper rendering (`FieldWrapper` template ⟵ `wrapEmbeddedHtml`), including the `labelPrefix` / `labelSuffix` label slots a boolean field fills with its control. `inlineLabelVerticalAlignment` is omitted pending schemas' `HasInlineLabel`, the only thing that varies it; `labelTag` ships under a different shape — see the row below. | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#the-label-carries-an-inline-control-modifier-class), [semantics](../schemas/can-be-marked-as-required.md#the-native-required-attribute-is-load-bearing-inside-a-modal-form) |
| Plain-wrapper branch of the same template — root plus a screen-reader label, chosen when the container carries `FusedGroup::PLAIN_FIELD_WRAPPER_VIEW` | [`components/plain-field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/plain-field-wrapper.blade.php) | done | — | [fused-group.md](fused-group.md) |
| `labelTag` (`label` vs `div`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#labeltag-is-a-boolean-labelasheading) |
| [`CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | in progress | — | [detail](can-be-validated.md) |
| Error display (`getErrorMessages()` ⟵ `hasErrorForPath` / `ViewErrorBag`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#error-display-shows-every-message-for-the-path) |
| Input affix wrapper (`wrapInputHtml`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [semantics](#the-wrapper-owns-the-chrome-and-the-control-is-transparent) |
| State casts &amp; enum defaults (`getDefaultStateCasts` / `getEnumDefaultStateCast`), and the [`HasEnum`](../../../packages/forms/src/Components/Concerns/HasEnum.php) concern behind them | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) · [`Concerns/HasEnum.php`](../../../vendor/filament/forms/src/Components/Concerns/HasEnum.php) | done | — | [detail](has-enum.md) |
| [`hasNullableBooleanState()`](../../../packages/forms/src/Components/Field.php) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [semantics](#hasnullablebooleanstate-has-exactly-one-consumer) |
| [`HasExtraFieldWrapperAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | [`Components/Concerns/HasExtraFieldWrapperAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | done | — | [diverges](#extra-attribute-precedence-is-fixed-by-source-order) |
| [`CanBeAutofocused`](../../../packages/forms/src/Components/Concerns/CanBeAutofocused.php) | [`Components/Concerns/CanBeAutofocused.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php) | done | — | — |
| [`HasHelperText`](../../../packages/forms/src/Components/Concerns/HasHelperText.php) | [`Components/Concerns/HasHelperText.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php) | done | — | — |
| [`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) | [`Components/Concerns/HasHint.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php) | done | — | [diverges](#setuphint-narrows-its-parameter-to-field), [diverges](#gethintactions-returns-a-list-not-a-gapped-array) |
| [`HasPlaceholder`](../../../packages/forms/src/Components/Concerns/HasPlaceholder.php) | [`Components/Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [diverges](#hasplaceholder-is-composed-on-the-base-field) |
| [`ResolvesRepeaterSiblingContext`](../../../packages/forms/src/Components/Concerns/ResolvesRepeaterSiblingContext.php) — no Filament concern of its own; the sibling arithmetic `distinct()` and the two repeater-sibling concerns share, split off `CanBeValidated` | [`Components/Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [diverges](can-fix-indistinct-state.md#the-five-sibling-facts-are-one-value-object-not-string-surgery) |
| [`data-field-wrapper` on the wrapper root](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) — the scroll target schemas' root controller reveals a validation error at | [`field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/field-wrapper.blade.php) · [`plain-field-wrapper.blade.php`](../../../vendor/filament/forms/resources/views/components/plain-field-wrapper.blade.php) | done | — | — |

## Semantics

Behaviour worth stating that is not a divergence — the port and Filament agree,
but the reason is not obvious from the code.

### The wrapper owns the chrome and the control is transparent

`wrapInputHtml` is support's
[`InputWrapper`](../support/input-wrapper.md), and every text-like control
renders inside it: [`TextInput`](text-input.md), [`Textarea`](textarea.md),
[`Select`](select.md), the [`DateTimePicker`](date-time-picker.md) family and
[`CheckboxList`](checkbox-list.md)'s search box. The ring, background, shadow,
hover and `focus-within` state moved onto `.sy-input-wrp` with it, leaving
`.sy-fo-input` and `.sy-fo-select` `appearance-none border-none bg-transparent
focus:ring-0` — Filament's own split, which the port had collapsed onto the
control until the wrapper existed to hold it.

The collapse is also what had ruled on the two type-specific `input[type=…]`
rules, and both were re-decided against the transparent control:

- **`input[type='date'|'datetime-local'|'time'].sy-fo-input { bg-white/[1%] }`
  ships.** The 1 % white exists because Safari colours a fully transparent
  date field's "placeholder" too dark
  ([#7087](https://github.com/filamentphp/filament/issues/7087),
  [#18570](https://github.com/filamentphp/filament/issues/18570)) — a bug whose
  trigger is the transparent background the flip introduced, and a hair of
  opacity fixes it without disturbing the opaque wrapper now behind it. Refused
  while `.sy-fo-input` was the painted surface, where the same selector would
  have beaten the base rule, the dark arm and `:disabled` alike.
- **`input[type='range'].sy-fo-input`'s `appearance-auto` is live**, not
  vestigial. It undoes the base rule's `appearance-none`, which the port did not
  set before the flip and does now; the rule's `mx-auto` and
  `width: calc(100% - 1.5rem)` were always load-bearing.

### `hasNullableBooleanState()` has exactly one consumer

The predicate declares whether a boolean-valued state has an "unset" resting
value beside `true` and `false`. `Field` answers `false`; exactly three fields
override it to `true` — [`Select`](select.md), `Radio` and `ToggleButtons`, each
because its placeholder or unselected state is a real third value. Not `Checkbox`
or `Toggle`, which is the natural guess and is wrong: both cast through a
non-nullable `BooleanStateCast`, where `false` genuinely is the resting value.
It drives no state cast, which is the other natural guess.

Its **only** consumer, here as in Filament, is the filter reset in tables'
[`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php):
`is_bool($state) => $field->hasNullableBooleanState() ? null : false`. The port
carried that call site with the branch flattened to a hardcoded `false` for as
long as the predicate was unported; the branch is
[restored](../tables/has-table.md#filter-mutations-reset-the-page-and-flush-the-record-cache),
so a custom
`Filter::make(…)->schema([Select::make(…)->boolean()])` that declares no
`resetState()` of its own now clears to the placeholder rather than flipping to
its false arm. Both tiers of the predicate are pinned by
[`HasEnumTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasEnumTest.php),
and both arms of the branch it drives by tables'
[`HasFiltersTest`](../../../packages/tables/tests/Unit/Concerns/HasFiltersTest.php).

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

This is a Sylament-only class, and [`docs/internals/styling.md`](../../internals/styling.md) makes
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
[`Radio`](radio.md), [`CheckboxList`](checkbox-list.md) and
[`ToggleButtons`](toggle-buttons.md) — both its variants — opted in the same way
when they landed, and the rest will.
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
except the required one skips a `null` value, so a single field rarely produces two.
A blank *string* used to be a different matter — `Length(min:)`,
`Type('numeric')` and `GreaterThanOrEqual` each violate on `''` where they skip
`null` — until the [presence gate](../schemas/presence-gate.md) stopped them
reaching the validator, which makes a `required()` field's `''` report one
message rather than several. The template still loops rather than picking a
first message, because a filled value legitimately fails several arms at once
(a too-short non-numeric string under `numeric()->minLength()`), and because
picking one would make *which* one a template concern.
`showAllValidationMessages()` / `allowHtmlValidationMessages()` are
[skipped](can-be-validated.md) accordingly — the flags exist to opt *into* what
this port always does, and into raw HTML the port has no shape for. Reverses only
if a first-message-only default is wanted, which would make the flag meaningful
again.

The same list also drives the control's invalid state: `TextInput` /
`Textarea` render `aria-invalid` from `component.getErrorMessages is not empty`,
and the same expression inverted reaches the input wrapper's `valid` prop, whose
`sy-invalid` class swaps the ring to the danger token. The attribute is left on
the control for accessibility rather than for styling — the ring moved off it
with [the rest of the chrome](#the-wrapper-owns-the-chrome-and-the-control-is-transparent).
Filament emits no `aria-invalid` anywhere — it toggles an `fi-invalid`
class, statically or through an Alpine binding
(`vendor/filament/forms/src/Components/Field.php:573`) — so the attribute is an
accessibility addition of this port, not a translation, and it renders with a
*value* rather than as a bare attribute because
[`ComponentAttributeBag`](../support/component-attribute-bag.md) maps a boolean
on an `aria-*` name to the strings `"true"` / `"false"` instead of dropping a
false one.
[`TagsInput`](tags-input.md#the-text-box-is-scratch-state-and-carries-no-data-model)
and [`KeyValue`](key-value.md#every-cell-binds-by-row-index-and-a-disabled-cell-keeps-its-binding)
are the two text-like controls that render none, for one reason: neither has a
control that *is* the field's value. The tags input's box holds the tag being
typed rather than the state, and the key-value field has no single control at all
— each cell binds one half of one row — so in both the invalid state rides the
input wrapper's `sy-invalid` and the field wrapper's messages alone, which is
also where Filament leaves it.

The two classes the list renders under drop Filament's `-wrp-` segment —
`.sy-fo-field-error-list` / `.sy-fo-field-error-message` for
`fi-fo-field-wrp-error-list` / `fi-fo-field-wrp-error-message` — which is a
[recorded rename](../renames.md), not the systematic `fi-` → `sy-` swap: the
segment is vestigial on Filament's side too, `fi-fo-field-wrp` existing in v5.7.6
only inside these two names. The rule bodies are Filament's
(`list-inside list-disc space-y-0.5` on the `<ul>`,
`text-sm text-danger-600 dark:text-danger-400` on each `<li>`).

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
field. This follows the same uniform-fluent-composition choice `.claude/rules/php.md` records
for `HasColumns` (a trait on the base even where only some subclasses use it):
fields where a placeholder is meaningless (e.g. `Checkbox`, `Toggle`) inherit a
harmless unused setter. Reversible by dropping `use HasPlaceholder` from `Field`
and composing it per-component if a field ever needs a conflicting `placeholder()`
meaning.

### The name moves from the constructor to a setter

Filament's `final __construct(string $name)` is dropped, as everywhere else in
the port — the resolver path takes no constructor arguments. `make()` resolves
the name (argument, then `static::getDefaultName()`), refuses a blank one, then
`newInstance()` → `name()` → `statePath()` → `configure()`, matching
[`Column::make()`](../tables/column.md) and
[`BaseFilter::make()`](../tables/base-filter.md#the-name-moves-from-the-constructor-to-a-setter).
Construction goes through
[`Component::newInstance()`](../../../packages/support/src/Components/Component.php)
(the container when the app registered the class, `new $class()` otherwise), so a
`Field` subclass can declare constructor-injected dependencies where Filament's
`final` constructor would have to be worked around. Both name assignments still
land before `configure()`, which is where the constructor put them, so a
`configureUsing()` closure reads the same field either way.

The guard is one notch stricter than the class it ports. Filament's `Field::make()`
tests `$name === null`; ours tests `blank($name)`, so `make('')` is refused
too — an empty name seeds an empty state path, which leaves the same silently
inert field a null name would. Filament guards its own `Column` and `BaseFilter`
with `blank()`, so this is its newer shape rather than a new one. The exception
class stays faithful per class rather than uniform across the three:
`InvalidArgumentException` here, `LogicException` in the two tables ports, because
that is what each original throws — and the first extends the second, so a single
`catch (LogicException)` still covers all three.

A name is therefore never null coming out of `make()`. It can still be nulled
afterwards: the inherited
[`HasName::name()`](../../../packages/schemas/src/Components/Concerns/HasName.php)
and [`HasState::statePath()`](../../../packages/schemas/src/Components/Concerns/HasState.php)
setters are both nullable, and the label and validation-dehydration paths that
read them keep their null branches.

### `getDefaultLabel` drops label translation

Filament's `getDefaultLabel` runs the humanized string through `__()` when
`shouldTranslateLabel` is set and always returns a non-null `string`. The port's
[`getDefaultLabel`](../../../packages/forms/src/Components/Field.php) returns
`?string` (null when the name is null — which
[`make()` no longer produces](#the-name-moves-from-the-constructor-to-a-setter),
though the nullable `name()` setter still admits it) and has
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
Two of the five landed with M3 · 9 and took **no** hook with them:
[`TagsInput`](tags-input.md) and [`KeyValue`](key-value.md) each render inside the
shared field wrapper with no extra element, because the one rule vendor's hook
carries is inline-label padding, which waits on schemas' `HasInlineLabel` exactly
as [`field.css` and `select.css`](styles.md#the-input-and-select-stylesheets-stop-at-the-alignment-arms)
already do. The hook arrives with the rule, not before it.

The port puts neither setter on an inner element: the
[input wrapper now renders](#the-wrapper-owns-the-chrome-and-the-control-is-transparent)
but takes no `extraAttributes()`, so the field wrapper `<div>` is still the only
element either setter reaches and one has to win. The port spells that as
`attrs()` source order, which is later-source-wins:
`attrs({class: [...]}, field.getExtraFieldWrapperAttributes, field.getExtraAttributes)`.
`extraAttributes()` wins, for two reasons. It already rendered on that `<div>`
before this trait existed, so composing the trait cannot silently override an
attribute a consumer had already set. And it keeps the per-component setter
authoritative over the wrapper one, which is the relationship Filament expresses
by putting it on the inner element.

Revisit by routing `extraAttributes()` onto the input wrapper, where Filament has
it: the element exists now, so the two setters can stop sharing one and the
ordering stops mattering.

_See also: the global [rename table](../renames.md) — the field-wrapper
rendering leans on the systematic `toEmbeddedHtml()`/Blade → Twig template,
`Htmlable` → `Markup`, and `wire:model` → `data-model` (Stimulus) translations
recorded there, none of which is a divergence._
