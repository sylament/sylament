# Field (forms)

`Sylament\Forms\Components\Field` — the abstract base every user-instantiable
form field extends. It adds field identity (name + state-path seeding), the
humanized-label fallback, the ten field-wrapper slots, and the field-wrapper
rendering on top of the schemas `Component`, and composes five field concerns.
Those five concerns are **folded here**: each is a row in the Surface table below
(its detail and any divergence spliced in from its own audit) and no longer
carries a row in the [package README](README.md) Features table. The
schemas-level `HasName` / `HasLabel` / `HasState` it inherits stay tracked in
schemas.

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
(deleted once the row is **done**) and/or the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; `make()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#make-drops-getdefaultname-and-the-unique-name-exception) |
| [`HasName`](../../../packages/schemas/src/Components/Concerns/HasName.php) (schemas) | [`Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | — |
| [`HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) (schemas) | [`Concerns/HasLabel.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| State-path seeding ([`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php), schemas) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | done | — | — |
| `getLabel()` / `getDefaultLabel()` humanization | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#getdefaultlabel-drops-label-translation) |
| Field-wrapper slots (10 constants + setters, `makeChildSchema` / `configureChildSchema`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | [diverges](#slot-child-schemas-drop-action--actiongroup-wiring) |
| Field-wrapper rendering (`FieldWrapper` template ⟵ `wrapEmbeddedHtml`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | done | — | — |
| Validation hooks (`getErrorMessages` stub; `hasErrorForPath`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | in progress | CanBeValidated | [detail](#validation-hooks-are-a-stub) |
| Input affix wrapper (`wrapInputHtml`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | blocked | HasAffixes | [detail](#the-input-affix-wrapper-is-not-ported) |
| State casts &amp; enum defaults (`getDefaultStateCasts` / `getEnumDefaultStateCast`) | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | blocked | HasEnum | [detail](#state-casts-and-enum-defaults-are-not-ported) |
| `hasNullableBooleanState()` | [`Components/Field.php`](../../../vendor/filament/forms/src/Components/Field.php) | todo | — | [detail](#hasnullablebooleanstate-is-not-ported) |
| `HasExtraFieldWrapperAttributes` (composed by Filament's Field) | [`Components/Concerns/HasExtraFieldWrapperAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraFieldWrapperAttributes.php) | todo | — | [detail](#hasextrafieldwrapperattributes-is-not-composed) |
| [`CanBeAutofocused`](../../../packages/forms/src/Components/Concerns/CanBeAutofocused.php) | [`Components/Concerns/CanBeAutofocused.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAutofocused.php) | done | — | — |
| [`CanBeMarkedAsRequired`](../../../packages/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`Components/Concerns/CanBeMarkedAsRequired.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | in progress | CanBeValidated | [detail](#the-required-marker-defers-its-validation-fallback) |
| [`HasHelperText`](../../../packages/forms/src/Components/Concerns/HasHelperText.php) | [`Components/Concerns/HasHelperText.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHelperText.php) | done | — | — |
| [`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) | [`Components/Concerns/HasHint.php`](../../../vendor/filament/forms/src/Components/Concerns/HasHint.php) | in progress | Actions bundle (`hintActions`) | [detail](#hashint-omits-the-hint-actions) |
| [`HasPlaceholder`](../../../packages/forms/src/Components/Concerns/HasPlaceholder.php) | [`Components/Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [diverges](#hasplaceholder-is-composed-on-the-base-field) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### Validation hooks are a stub

Filament's Field implements `Contracts\HasValidationRules`, composes
`CanBeValidated`, and computes the wrapper's error region in `wrapEmbeddedHtml`
from Laravel's `ViewErrorBag` (`hasErrorForPath`,
`shouldShowAllValidationMessages`, `areHtmlValidationMessagesAllowed`). The port
ships only a
[`getErrorMessages(): array`](../../../packages/forms/src/Components/Field.php)
that returns `[]`; the `FieldWrapper` template renders its `data-validation-error`
list solely from that array, so no field renders an error yet. `hasErrorForPath`,
the message-count / all-messages branching, and HTML-message escaping are absent.
**Next step:** land `CanBeValidated` + the `HasValidationRules` contract, then
have `getErrorMessages()` read real messages off the bound live component.

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

### The required marker defers its validation fallback

Filament's `isMarkedAsRequired()` returns
`$this->evaluate($this->isMarkedAsRequired) ?? $this->isRequired()` — with no
explicit marker set it falls through to the validation-driven required state that
`CanBeValidated` supplies. The port casts the evaluated marker to `(bool)`, so an
unset marker reads `false`; the explicit `markAsRequired()` surface is otherwise
method-for-method identical and tested. The `?? $this->isRequired()` tail has
nothing to call until `CanBeValidated` (and the `isRequired()` it provides) is
ported — **blocked** on that concern and restored in the same change, not a
deliberate omission.

**The native `required` attribute became load-bearing with forms-in-modals.**
`TextInput` / `Textarea` have always emitted `required` from
`isMarkedAsRequired()`
([`TextInput.html.twig:14`](../../../packages/forms/templates/components/TextInput.html.twig)),
but with no `<form>` anywhere in the page the attribute did nothing. Now that a
mounted action's modal window renders as a real `<form>`
([support/modal.md](../support/modal.md#the-form-wrapper-carries-live_action-not-wiresubmit)),
the browser blocks submission of an empty required field — the `submit` event
only fires after native constraint validation passes, so no request reaches the
server at all. Confirmed in a browser during the forms-in-modals epic:
`valueMissing: true`, **zero** network requests, modal stays open.

The **mechanism is parity, not a divergence**: Filament emits native `required`
too (`vendor/filament/forms/src/Components/TextInput.php:365`) and `wire:submit`
has the same event ordering, so a migrating user meets the same client-side
block. What still differs is only the *source* of the flag — Filament's
`isRequired()` against this port's display-only `isMarkedAsRequired()`, which is
the split described above. It is worth
naming, because it means "unvalidated" in the forms-in-modals epic is a statement
about the **server**, and because it makes the split above user-visible sooner
than expected: until `CanBeValidated` lands, a display-only `markAsRequired()`
call *does* block submits client-side while still validating nothing on the
server. M1 · 1 separates the display-only `isMarkedAsRequired()` from a real
`isRequired()` sourced from `CanBeValidated`.

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
no unique-name guard, so `name(?string $name = null)` accepts and keeps a null
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
