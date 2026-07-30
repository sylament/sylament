# Field (forms)

`Sylament\Forms\Components\Field` — the abstract base every user-instantiable
form field extends. It adds field identity (name + state-path seeding), the
humanized-label fallback, the ten field-wrapper slots, the field-wrapper
rendering and the error-message read on top of the schemas `Component`, and
composes five field concerns. Four of them are **folded here**: each is a row in
the Surface table below (its detail and any divergence spliced in from its own
audit) and no longer carries a row in the [package README](README.md) Features
table. The fifth, [`CanBeValidated`](can-be-validated.md), keeps its own file —
its Filament surface is some eighty methods against this file's five-method
concerns, and it is the field half of a vertical spanning three packages. The
schemas-level `HasName` / `HasLabel` / `HasState` it inherits stay tracked in
schemas, and so does
[`CanBeMarkedAsRequired`](../schemas/can-be-marked-as-required.md), which moved
down to schemas so its `Fieldset` could reach it.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php)
  — the base class, the traits it composes, and its `wrapEmbeddedHtml` /
  `wrapInputHtml` rendering
- [`forms/src/Components/Concerns/`](../../../vendor/filament/forms/src/Components/Concerns)
  — the four folded field concerns audited below
- [`schemas/src/Components/Concerns/`](../../../vendor/filament/schemas/src/Components/Concerns)
  — the inherited `HasName` / `HasLabel` / `HasState` behind the identity rows

## Surface

Every point of this component and its four folded concerns. _Blocked by_ names
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
| Field-wrapper slots (10 constants + setters, `makeChildSchema` / `configureChildSchema`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#slot-child-schemas-drop-action--actiongroup-wiring) |
| Field-wrapper rendering (`FieldWrapper` template ⟵ `wrapEmbeddedHtml`), including the `labelPrefix` / `labelSuffix` label slots a boolean field fills with its control. `inlineLabelVerticalAlignment` is omitted pending schemas' `HasInlineLabel`, the only thing that varies it; `labelTag` is omitted for an unrelated reason — see the row below. | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#the-label-carries-an-inline-control-modifier-class), [semantics](../schemas/can-be-marked-as-required.md#the-native-required-attribute-is-load-bearing-inside-a-modal-form) |
| `labelTag` (`label` vs `div`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | todo | the first multi-control field (`Radio` / `CheckboxList`) | [detail](#labeltag-waits-for-the-first-multi-control-field) |
| [`CanBeValidated`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php) | [`Components/Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | in progress | — | [detail](can-be-validated.md) |
| Error display (`getErrorMessages()` ⟵ `hasErrorForPath` / `ViewErrorBag`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#error-display-shows-every-message-for-the-path) |
| Input affix wrapper (`wrapInputHtml`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | blocked | HasAffixes | [detail](#the-input-affix-wrapper-is-not-ported) |
| State casts &amp; enum defaults (`getDefaultStateCasts` / `getEnumDefaultStateCast`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | blocked | HasEnum | [detail](#state-casts-and-enum-defaults-are-not-ported) |
| `hasNullableBooleanState()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | todo | — | [detail](#hasnullablebooleanstate-is-not-ported) |
| `HasExtraFieldWrapperAttributes` (composed by Filament's Field) | [`Components/Concerns/HasExtraFieldWrapperAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | todo | — | [detail](#hasextrafieldwrapperattributes-is-not-composed) |
| [`CanBeAutofocused`](../../../packages/forms/src/Components/Concerns/CanBeAutofocused.php) | [`Components/Concerns/CanBeAutofocused.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php) | done | — | — |
| [`HasHelperText`](../../../packages/forms/src/Components/Concerns/HasHelperText.php) | [`Components/Concerns/HasHelperText.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php) | done | — | — |
| [`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) | [`Components/Concerns/HasHint.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php) | in progress | Actions bundle (`hintActions`) | [detail](#hashint-omits-the-hint-actions) |
| [`HasPlaceholder`](../../../packages/forms/src/Components/Concerns/HasPlaceholder.php) | [`Components/Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [diverges](#hasplaceholder-is-composed-on-the-base-field) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### `labelTag` waits for the first multi-control field

Filament's `wrapEmbeddedHtml()` takes a `$labelTag` that swaps the wrapper's
`<label for="…">` for a `<div id="…-label">`. It has nothing to do with inline
labels: it is chosen **per field type**, by every field that renders more than
one control and therefore cannot point a single `for` at one of them —
[`Radio`](../../../vendor/filament/forms/src/Components/Radio.php),
[`CheckboxList`](../../../vendor/filament/forms/src/Components/CheckboxList.php),
[`Repeater`](../../../vendor/filament/forms/src/Components/Repeater.php),
`Builder`, `FileUpload`, `KeyValue`, `MarkdownEditor`, `CodeEditor` and
`ModalTableSelect` all pass `labelTag: 'div'`, and `LivewireField` picks it from
`filled($id)`.

Every field ported so far renders exactly one control, so all of them want the
`<label for>` the template hardcodes. **Next step:** add the parameter with the
first multi-control field. Note this is *not* blocked on schemas'
`HasInlineLabel` — only `inlineLabelVerticalAlignment` is.

### The input affix wrapper is not ported

Filament's `wrapInputHtml` renders the `fi-input-wrp` prefix/suffix chrome (affix
labels, icons, prefix/suffix actions, loading indicator, Alpine disabled/invalid
class binding). None of it is ported — the port has no `HasAffixes`, no
prefix/suffix API, and no input-wrapper template. **Blocked** on the `HasAffixes`
concern port; until then fields render their raw control inside the field wrapper
with no affix layer.

### State casts and enum defaults are not ported

Filament's Field overrides `getDefaultStateCasts()` to append an `EnumStateCast`
and exposes `getEnumDefaultStateCast()`, both driven by the `HasEnum` concern.
Neither is composed or ported. **Blocked** on the `HasEnum` port and the schemas
`StateCast` surface.

### `hasNullableBooleanState()` is not ported

Filament's Field declares `hasNullableBooleanState(): bool` (default `false`),
overridden by boolean fields to control tri-state casting. Not ported; no
consumer exists until the boolean fields (Checkbox, Toggle) land.

### `HasExtraFieldWrapperAttributes` is not composed

Filament's Field composes `HasExtraFieldWrapperAttributes`
(`extraFieldWrapperAttributes()` merged onto the wrapper `<div>`). The port omits
the trait; the `FieldWrapper` template merges only `field.getExtraAttributes`
(the schemas-level `HasExtraAttributes`). **Next step:** port the concern and
merge it in the wrapper template.

### `HasHint` omits the hint actions

**In:** `hint()`, `hintColor()`, `hintIcon($icon, $tooltip = null)` — with the
`func_num_args() >= 2` guard so a one-argument call never clobbers a prior
tooltip — and `hintIconTooltip()`, plus every accessor (`hasHint()`, `getHint()`,
`getHintColor()`, `hasHintIcon()`, `getHintIcon()`, `getHintIconTooltip()`).
`setUpHint()` registers an `afterLabel()` closure that seeds a `Text` (colored,
visible-when-filled) and an `Icon` (tooltipped, colored, visible-when-filled)
into the after-label slot, each reading back through
`$icon/$text->getContainer()->getParentComponent()`. Covered by `FieldTest`
(defaults, round-trip, closure evaluation, the `func_num_args` tooltip guard, the
after-label slot seeding) and by the `TextInput` / `Textarea` integration suites.

**Missing vs Filament:** `hintAction(Action | Closure)`, `hintActions(array)`,
and `getHintActions()` — the hint-adjacent action buttons — are not ported
(`Filament\Actions\Action` has no Sylament equivalent yet), so `setUpHint()` drops
the `...$component->getHintActions()` spread Filament appends to the after-label
components. `setUpHint()`'s closure also narrows its parameter from Filament's
`Field | Placeholder` to `Field`, because `Placeholder` is not ported.
**Blocked** on the Actions bundle (and, for the parameter, the `Placeholder`
component); add the trio and re-add the spread when they land.

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
except `NotBlank` skips blank input, so a single field rarely produces two.
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

### Slot child-schemas drop Action / ActionGroup wiring

Filament's slot setters accept `Action | ActionGroup` in their union and
`configureChildSchema` calls `modifyActionsUsing(...)` /
`modifyActionGroupsUsing(...)` to default embedded actions to `Size::Small` /
`Action::LINK_VIEW`. The port's setters accept only
`Schema|array|Component|string|Markup|Closure|null` and its
[`configureChildSchema`](../../../packages/forms/src/Components/Field.php) sets
`inline()` + `embeddedInParentComponent()` with no action defaults — the actions
integration is not ported. Restore when `actions` is wired into forms.

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

_See also: the global [rename table](../renames.md) — the field-wrapper
rendering leans on the systematic `toEmbeddedHtml()`/Blade → Twig template,
`Htmlable` → `Markup`, and `wire:model` → `data-model` (Stimulus) translations
recorded there, none of which is a divergence._
