---
description: The option field whose controls are buttons, a hidden input under a `sy-btn` label per option, and the colour, icon and tooltip maps only it composes.
ports:
  - vendor/filament/forms/src/Components/ToggleButtons.php
  - vendor/filament/forms/src/Components/Concerns/HasColors.php
  - vendor/filament/forms/src/Components/Concerns/HasIcons.php
  - vendor/filament/forms/src/Components/Concerns/HasTooltips.php
owns:
  - packages/forms/src/Components/Concerns/HasColors.php
  - packages/forms/src/Components/Concerns/HasIcons.php
  - packages/forms/src/Components/Concerns/HasTooltips.php
  - packages/forms/src/Components/ToggleButtons.php
  - packages/forms/templates/partials/ToggleButtons/
---
# ToggleButtons (forms)

`Sylament\Forms\Components\ToggleButtons` extends [`Field`](field.md) and casts state through schemas' [`OptionStateCast`](../../../packages/schemas/src/State/Casts/OptionStateCast.php), or [`OptionsArrayStateCast`](../../../packages/schemas/src/State/Casts/OptionsArrayStateCast.php) under [`multiple()`](#the-multiple-arm-needs-a-model-that-rests-at-an-array).
It renders in two shapes from one class — a grid of separate buttons by default, a joined `sy-btn-group` row under [`grouped()`](#the-grouped-variant-is-a-second-template-picked-by-gettemplate) — and picks between them at render.
It ships no JavaScript: the controls are real radios and checkboxes bound with `data-model`, and `ux-live-component` reads and repaints them natively.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering — the hidden input, the button label, the icon and the option colour | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](#the-selected-state-is-painted-from-the-sibling-input-in-both-variants) · [diverges](#the-checked-attribute-is-rendered-server-side) · [diverges](#the-dead-disabled-on-a-label-is-not-ported) · [diverges](#the-option-inputs-carry-aria-invalid-which-filaments-do-not) · [diverges](#extraattributes-stays-on-the-field-wrapper) |
| `grouped()` / `isGrouped()` and the grouped tree | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](#the-grouped-variant-is-a-second-template-picked-by-gettemplate) · [semantics](#the-grouped-labels-must-stay-direct-children-of-the-group) · [diverges](#the-variants-keep-vendors-autofocus-and-inline-asymmetries) |
| `wire:loading.attr="disabled"` on the grouped inputs | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | todo | — | [detail](#the-grouped-inputs-do-not-disable-themselves-while-a-request-is-in-flight) |
| `multiple()` / `isMultiple()` — `role`, `type` and the dropped `name` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](#the-multiple-arm-needs-a-model-that-rests-at-an-array) |
| `boolean()` — the `1`/`0` pair, its colours, its icons and the int-backed cast | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [diverges](#the-boolean-icons-resolve-their-alias-at-render-not-at-call-time) · [semantics](radio.md#the-cast-chain-collapses-to-whatever-boolean-installs) · [diverges](#the-boolean-labels-are-untranslated-literals) |
| `inline()` / `isInline()` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](#inline-is-this-fields-own-property-not-the-canbeinline-trait) |
| `size()` / `getSize()` — the button size and the icon size it implies | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | todo | — | [detail](#size-and-fullwidth-are-not-ported) |
| `fullWidth()` / `isFullWidth()` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | todo | — | [detail](#size-and-fullwidth-are-not-ported) |
| `hiddenButtonLabels()` / `areButtonLabelsHidden()` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](#a-hidden-button-label-becomes-a-tag-stripped-aria-label) |
| `getDefaultState()` — a `bool` default coerced to `1`/`0` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | — |
| `getDefaultStateCasts()` — the option cast, or its array form when multiple | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](radio.md#the-cast-chain-collapses-to-whatever-boolean-installs) |
| `getEnumDefaultStateCast()` — `EnumStateCast`, or `EnumArrayStateCast` when multiple | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [detail](has-enum.md) |
| `getIconAlias()` — no Filament source; which of the `boolean()` icons is an overridable default | — | done | — | [diverges](#the-boolean-icons-resolve-their-alias-at-render-not-at-call-time) |
| `hasNullableBooleanState()` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](field.md#hasnullablebooleanstate-has-exactly-one-consumer) |
| `getInValidationConstraintValues()` / `hasInValidationOnMultipleValues()` → `buildValidationConstraints()`, replaced by a user `in()` list | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](select.md#the-choice-constraint-is-the-fields-only-server-side-guard) · [semantics](can-disable-options.md#the-choice-choices-cover-both-forms-the-raw-state-can-take) · [semantics](can-be-validated.md#a-user-in-list-is-emitted-in-both-spellings-and-replaces-the-derived-check) |
| `required()` — the standard `RequiredValue`, in both multiplicities | [`Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | [semantics](#required-means-at-least-one-answer-in-both-multiplicities) |
| Tooltips — the per-option hover text | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [diverges](#a-tooltip-renders-as-a-native-title) |
| The grid, and its `Column` default | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | done | — | [semantics](has-grid-direction.md#the-column-default-belongs-to-the-field-not-to-this-getter) |
| [`HasOptions`](../../../packages/forms/src/Components/Concerns/HasOptions.php) | [`Concerns/HasOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasOptions.php) | done | — | [detail](has-options.md) |
| [`CanDisableOptions`](../../../packages/forms/src/Components/Concerns/CanDisableOptions.php) + [contract](../../../packages/forms/src/Components/Contracts/CanDisableOptions.php) | [`Concerns/CanDisableOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptions.php) | done | — | [detail](can-disable-options.md) |
| [`HasGridDirection`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php) | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | [detail](has-grid-direction.md) |
| [`HasExtraInputAttributes`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) · [diverges](#the-extra-input-attributes-win-where-filaments-lose-by-going-last) · [diverges](#extraattributes-stays-on-the-field-wrapper) |
| [`HasColors`](../../../packages/forms/src/Components/Concerns/HasColors.php) | [`Concerns/HasColors.php`](../../../vendor/filament/forms/src/Components/Concerns/HasColors.php) | done | — | [semantics](#hascolors-hasicons-and-hastooltips-are-this-fields-alone) · [diverges](#the-arrayable-arm-is-dropped-from-the-colour-and-icon-maps) |
| [`HasIcons`](../../../packages/forms/src/Components/Concerns/HasIcons.php) | [`Concerns/HasIcons.php`](../../../vendor/filament/forms/src/Components/Concerns/HasIcons.php) | done | — | [semantics](#hascolors-hasicons-and-hastooltips-are-this-fields-alone) · [diverges](#the-arrayable-arm-is-dropped-from-the-colour-and-icon-maps) |
| [`HasTooltips`](../../../packages/forms/src/Components/Concerns/HasTooltips.php) | [`Concerns/HasTooltips.php`](../../../vendor/filament/forms/src/Components/Concerns/HasTooltips.php) | done | — | [semantics](#hascolors-hasicons-and-hastooltips-are-this-fields-alone) · [diverges](#gettooltips-return-is-narrowed) |
| [`HasNestedRecursiveValidationConstraints`](../../../packages/forms/src/Components/Concerns/HasNestedRecursiveValidationConstraints.php) | [`Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [diverges](#the-nested-recursive-rules-trait-is-dead-api-in-filaments-own-class) |
| `getPublishedViewOverrideCheckPath()` | [`Components/ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) | skipped | — | [diverges](#there-is-nothing-to-publish-a-view-override-against) |
| [`CanFixIndistinctState`](../../../packages/forms/src/Components/Concerns/CanFixIndistinctState.php) | [`Concerns/CanFixIndistinctState.php`](../../../vendor/filament/forms/src/Components/Concerns/CanFixIndistinctState.php) | done | — | [detail](can-fix-indistinct-state.md) |
| [`CanDisableOptionsWhenSelectedInSiblingRepeaterItems`](../../../packages/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | [`Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php`](../../../vendor/filament/forms/src/Components/Concerns/CanDisableOptionsWhenSelectedInSiblingRepeaterItems.php) | done | — | [detail](can-disable-options-when-selected-in-sibling-repeater-items.md) |

## Details

### `size()` and `fullWidth()` are not ported

Filament's `size()` takes a `Size` or a string, and `getSize()` answers `Size::Medium` when none is set; both templates write it as the button's `fi-size-*` class and shrink each option's icon to `IconSize::Small` for `ExtraSmall` and `Small`.
`fullWidth()` adds `fi-width-full` to the container, which vendor's `toggle-buttons.css` uses to stretch the option containers and their buttons.
The port has neither pair: both templates hardcode `sy-size-md` on the label, pass no size to the `Icon` tag, and emit no width class, and [`toggle-buttons.css`](../../../packages/forms/assets/src/styles/components/toggle-buttons.css) carries no `sy-width-full` arm.
Porting them needs the two setters, the class in both templates, the icon-size mapping and the stylesheet arms, which [`styles.md`](styles.md) owns.

### The grouped inputs do not disable themselves while a request is in flight

Filament's grouped variant writes `wire:loading.attr="disabled"` on every input, so a second click cannot land while Livewire is still answering the first; the default variant writes nothing of the kind.
The port's `Grouped.html.twig` emits no loading directive.
`ux-live-component`'s equivalent is `data-loading="addAttribute(disabled)"`, which [`Tabs`](../../../packages/schemas/templates/partials/Tabs/index.html.twig) and [`Table`](../../../packages/tables/templates/partials/Table/index.html.twig) already emit, so the grouped inputs can take it with no new mechanism.

## Semantics

### `HasColors`, `HasIcons` and `HasTooltips` are this field's alone

All three are per-option maps keyed the way `getOptions()` is, all three memoise (`$cachedX` + `$hasCachedX`, reset by the setter), and all three are read once per option by the two templates.
`ToggleButtons` is the only forms field in Filament that composes any of them, which is why this file documents them rather than three files of their own.
They split on one axis:

| Trait | Getter | Enum fallback |
| --- | --- | --- |
| [`HasColors`](../../../packages/forms/src/Components/Concerns/HasColors.php) | `getColor(value): string\|array\|null` | [`HasColor`](../../../packages/support/src/Contracts/HasColor.php) cases, keyed `value ?? name` |
| [`HasIcons`](../../../packages/forms/src/Components/Concerns/HasIcons.php) | `getIcon(value): string\|BackedEnum\|Markup\|null` | [`HasIcon`](../../../packages/support/src/Contracts/HasIcon.php) cases, same keying |
| [`HasTooltips`](../../../packages/forms/src/Components/Concerns/HasTooltips.php) | `getTooltip(value): string\|Markup\|null` | **none** |

The enum fallback fires only when the explicit map is blank *and* the field's [`enum()`](has-enum.md) implements the matching contract, so `colors([…])` on an enum-backed field replaces the derived map wholesale rather than merging into it — vendor-exact, and the same all-or-nothing gate [`HasDescriptions`](has-descriptions.md) has.

Two asymmetries inside that family look like port slips and are not.
First, `HasTooltips` has **no** enum fallback in Filament either — there is no `HasTooltip` contract to derive from — so a tooltip is always explicit.
Second, colours and icons keep **every** case as a key, including one whose contract method answers `null`, where [`HasDescriptions`](has-descriptions.md#only-a-described-case-reaches-the-map) filters those out; the difference is vendor's and it is harmless, because `getColor()` / `getIcon()` return `null` for a missing key and for a key held against `null` alike.

`getColors()` and `getIcons()` return `array`, `getTooltips()` returns `?array` — also vendor's split, and the reason nothing reads `getTooltips()` directly: the templates ask per value.

One name is overloaded across two packages.
The enum contract above is [`Sylament\Support\Contracts\HasColor`](../../../packages/support/src/Contracts/HasColor.php), whose method answers *"what colour is this case"*; the class-string the templates hand `colorAttributes()` is a **policy** implementing [`Sylament\Support\Colors\Contracts\HasColor`](../../../packages/support/src/Colors/Contracts/HasColor.php), whose method answers *"which shades of that colour does a button use"*.
Both are called `HasColor`, both are read on the same line of the same template, and neither has anything to do with the other.
Filament has the same collision.

### The grouped variant is a second template picked by `getTemplate()`

Filament dispatches inside one render method — `toEmbeddedHtml()` opens with `if ($this->isGrouped()) { return $this->toGroupedEmbeddedHtml(); }` — so one class carries two complete DOM trees in PHP.
The port splits them into two Twig files, [`ToggleButtons/index.html.twig`](../../../packages/forms/templates/partials/ToggleButtons/index.html.twig) and [`ToggleButtons/Grouped.html.twig`](../../../packages/forms/templates/partials/ToggleButtons/Grouped.html.twig), and moves the dispatch onto the template getter:

```php
public function getTemplate(): ?string
{
    return $this->evaluate($this->template)
        ?? $this->evaluate($this->defaultTemplate)
        ?? ($this->isGrouped() ? static::GROUPED_TEMPLATE : static::TEMPLATE);
}
```

That is the shape [`ActionGroup::getTriggerTemplate()`](../../../packages/actions/src/ActionGroup.php) uses — explicit setting, then default setting, then the built-in — and it keeps the two consumer hooks working in the order a caller expects: `template('…')` wins outright, `defaultTemplate('…')` replaces the built-in without beating an explicit one, and only then does `isGrouped()` choose.
Setting `$template` to a literal the way [`Radio`](radio.md) and most other fields do would make the grouped choice unreachable, because `template()` is the same property.

[`Repeater`](repeater.md) swaps its simple template **eagerly**, `simple()` calling `$this->defaultTemplate(…)` at configuration time.
That works because `simple()` takes a field, not a condition.
`grouped()` takes `bool|Closure`, so a caller writing `grouped(fn () => $user->prefersCompact())` expects the answer to be read each time the field renders.
Eager assignment would have to evaluate that closure in the setter and freeze its first answer, which is what every other `bool|Closure` setter on the class exists not to do.

The two files repeat the option loop rather than sharing an `{% include %}`: the trees differ in the container class, the wrapping `div`, the button class list and the autofocus branch, which is most of the markup, and vendor splits them at exactly this seam.
`TEMPLATE` has no vendor constant behind it — Filament inlines `'filament-forms::components.toggle-buttons.index'` and names only `GROUPED_VIEW` — but a lone constant would read as an accident beside its partner, and both [`Repeater`](repeater.md) and [`Action`](../actions/action.md) declare one per variant.

### The grouped labels must stay direct children of the group

The joined look is support's rule, not this field's.
This template emits the tree vendor emits — `div.sy-fo-toggle-buttons.sy-btn-group` with `label.sy-btn.sy-btn-group-btn` children — and everything that makes it read as one control (the `grid grid-flow-col`, the shared ring, the first/last corner rounding and the `me-px` seam) comes from [`.sy-btn-group` in support's `button.css`](../support/button.md), which this field's [`toggle-buttons.css`](../../../packages/forms/assets/src/styles/components/toggle-buttons.css) only adds `w-max` to.

That rule keys off `:nth-child(1 of .sy-btn)` / `:nth-last-child(1 of .sy-btn)` for the end caps and the seams, so **both** halves of the tree are load-bearing: the labels carry `sy-btn`, and they are direct children of the container with only the option `<input>`s between them (which the `of .sy-btn` argument skips).
Wrapping each option in a `div` the way the default variant does would leave every corner square and every seam missing, with nothing failing anywhere — so `ToggleButtonsTest` asserts the direct-child relation, not just the classes.
The unstyled `sy-btn-group-btn` is vendor's own hook and carries no rule in either codebase.

### The `multiple()` arm needs a model that rests at an array

Every input carries the same `data-model`, one per option, exactly as [Radio does](radio.md#every-radio-carries-the-same-data-model).
The single arm behaves identically — only the clicked radio fires, and `live_controller`'s `getValueFromElement()` has no radio branch, so it falls through to the element's value.

The checkbox arm is the one to know.
That function's checkbox branch reads the model's **current** value first and only calls `getMultipleCheckboxValue()` — the add-or-remove against the existing list — when it finds an array or an object there; otherwise it falls through to `element.checked ? value : null`, which is the boolean-checkbox contract.
So a multiple field whose state path rests at `null` would collapse its list to a single scalar on the first click and never recover.

It cannot, because `getDefaultStateCasts()` answers `OptionsArrayStateCast()` for a multiple field and that cast's `set()` writes `[]` rather than `null` for blank input (`isNullable` defaults to `false`, which is also why the call needs no argument where [`Select::multiple()`](select.md) passes one explicitly).
[`LiveToggleButtonsTest`](../../../packages/forms/tests/Integration/LiveToggleButtonsTest.php) asserts that resting shape on the freshly mounted host before it asserts anything about checking.
The repaint half agrees for free: `setValueOnElement()`'s checkbox arm is `value.some(val => val == element.value)` for an array, the same membership test the template renders `checked` from.

### `inline()` is this field's own property, not the `CanBeInline` trait

Same shape [`Radio`](radio.md#inline-is-radios-own-property-not-the-canbeinline-trait) records, and for the same reason: `ToggleButtons` declares `protected bool|Closure $isInline = false;` with its own `inline()` / `isInline()` pair and composes no trait, which is vendor-exact, while the port *does* ship a [`CanBeInline`](can-be-inline.md) trait of the same method names that `Checkbox` and `Toggle` compose.

The two are not interchangeable: the trait defaults to `true` and answers to Filament's `hasInlineLabel()` guard, which the port's trait [drops](can-be-inline.md#isinline-drops-the-hasinlinelabel-guard); this property defaults to `false` and has no guard, and the word means different things on each — on a boolean field it places the control beside the field label, here it swaps the option grid for a wrapping flex row.
Radio's note carries the full argument; it applies verbatim.

### A hidden button label becomes a tag-stripped `aria-label`

`hiddenButtonLabels()` drops the label text from the button and puts it on the label element as `aria-label`, with `striptags` and `trim` applied — vendor's `e(trim(strip_tags((string) $label)))`.
The stripping is what makes the attribute usable: an option label is `string|Markup`, and a `Markup` label carrying `<b>` or a nested span would otherwise be announced with its markup, because an attribute value is text and nothing renders inside it.
The `trim` matters for the same reason it does in vendor — stripping tags routinely leaves the whitespace that surrounded them.

Nothing else changes: the icon still renders, the `for`→`id` wiring is untouched, and the control keeps its accessible name.
A field with neither an icon nor a visible label is a legal but useless configuration, and neither codebase guards it.

### `required()` means at least one answer, in both multiplicities

`required()` builds the default [`RequiredValue`](can-be-validated.md#required-carries-laravels-blank-set-not-symfonys) and the field overrides `getRequiredConstraint()` nowhere, exactly as [`Radio`](radio.md#required-means-presence-and-needs-no-override) does.
Both multiplicities land where they should:

- **Single.**
  The resting state is `null` and the false option of a `boolean()` field is `int 0`.
  `filled()` counts `null` blank and both `0` and `'0'` present, so a required `boolean()` field rejects only *no answer*, which is the whole argument radio.md makes.
- **Multiple.**
  The resting state is `[]`, which `filled()` counts blank, so the constraint fails it: required means "pick at least one".
  No `Count(min: 1)` is needed and none is added.

The interaction with `buildValidationConstraints()` is what makes both work without a special case: the method short-circuits on a [blank *raw* state](select.md#the-filled-gate-reads-the-raw-state-because-a-cast-can-null-a-tampered-value-out) before it adds any `Choice`, so an empty required field is failed by the required constraint alone rather than by a `Choice` that would have had to admit emptiness.
Reading the raw state is what keeps that carve-out to genuinely empty fields: a payload that wrote something the enum cast cannot resolve is not empty, and meets the `Choice`.

### The selected state is painted from the sibling input, in both variants

Nothing on the `<label>` says it is selected: the `checked` attribute is on the `<input>` the label covers, and the field emits the same classes either way.
The *only* thing that can tell a chosen option from an unchosen one is CSS reading across the sibling — support's [`input:checked + label.sy-btn` arms](../support/button.md#the-label-buttons-paint-their-colour-only-while-checked).

Both templates emit the tree that needs — `<input>` immediately followed by `<label class="sy-btn">`, inside a `div.sy-fo-toggle-buttons-btn-ctn` by default and directly under the group when [`grouped()`](#the-grouped-labels-must-stay-direct-children-of-the-group).
The **adjacency** is what is load-bearing and it is invisible: putting anything between the two, or moving the input after the label the way a plain checkbox row does, leaves every option painted identically with nothing failing anywhere.
`ToggleButtonsTest` pins `input.sy-fo-toggle-buttons-input + label.sy-btn` in both variants for exactly that reason, beside the direct-child assertion the group arm needs.

## Divergences

### The `checked` attribute is rendered server-side

Filament emits only `wire:model` and lets Livewire set the checked property once it boots.
The port renders `checked` from [`getOptionComparisonState()`](has-options.md#the-option-vocabulary-is-normalized-once-rather-than-per-template), so a server-rendered edit page shows the stored selection before any JavaScript runs — the same addition [`Radio`](radio.md#the-checked-attribute-is-rendered-server-side), [`Checkbox`](checkbox.md#the-checked-attribute-is-rendered-server-side) and [`Select`](select.md#the-template-renders-the-selected-state-itself) make.

The comparison branches on the state's own shape rather than on `isMultiple()`: `state is iterable ? value in state : (state is not null and value == state)`.
The scalar arm is deliberately loose (an option key is an `int` where the state may hold the `'1'` a browser submitted) and deliberately null-guarded (PHP's `0 == null` is `true`, so a `boolean()` field would otherwise render **No** checked for a field nobody has answered).
Reading the shape rather than the flag also keeps a `multiple()` field whose custom state cast yields a scalar rendering sanely instead of comparing a string against a list.

### The dead `disabled` on a `<label>` is not ported

Filament writes `disabled` onto the option's `<label>`, conditional on `blank($tooltip)`.
`disabled` is not a valid attribute on a `<label>` and does nothing in any browser — the functional disable comes from the `disabled` on the `<input>`, which both codebases emit, and the greying from the `fi-disabled` class beside it.
The port emits the input's `disabled`, `aria-disabled="true"` and `sy-disabled` on the label, and drops the inert attribute.

Dropping it also drops the tooltip carve-out, which only keeps a disabled button's tooltip working around an attribute that does nothing — the port's [native `title`](#a-tooltip-renders-as-a-native-title) shows on a disabled label regardless.
Nothing observable changes; the markup is one attribute shorter.

### A tooltip renders as a native `title`

Filament renders `tooltips()` through its global Tippy directive — `x-tooltip="{ content: …, theme: $store.theme, allowHTML: … }"` — which gives a styled, themed, HTML-capable popup.
The port has no tooltip primitive anywhere — support ships none, and actions' [`Action::tooltip()`](../actions/action.md) renders the same native attribute from its trigger partials — so a tooltip renders as `title="…"`.

The API is unchanged — `tooltips([…])` takes the same map and the same closures — and the behaviour is the browser's: no theming, no HTML, a delay the user's OS picks.
A `Markup` tooltip is the visible cost: an attribute value is text, so its tags reach the reader as literal `<b>…</b>` characters where Filament's `allowHTML` arm would render them.
Nothing strips or rewrites them — silently discarding the caller's content would hide the gap rather than show it — and `ToggleButtonsTest` characterizes the literal output so a tooltip primitive has something to change against.
**Reversible** as a whole: when that primitive lands, this field and actions' button trigger switch together, and neither public surface moves.

### The variants keep vendor's autofocus and inline asymmetries

Filament's two trees differ in three ways beyond their shape, none of them commented:

| Vendor asymmetry | Port |
| --- | --- |
| `autofocus` on the first option — default only, absent from grouped | preserved |
| `fi-inline` and the grid — default only | preserved |
| `wire:loading.attr="disabled"` on the inputs — grouped only, absent from default | [not emitted yet](#the-grouped-inputs-do-not-disable-themselves-while-a-request-is-in-flight) |

The autofocus and inline asymmetries are preserved because they are observable API: a caller writing `->grouped()->autofocus()` gets Filament's behaviour, odd as it is, and a caller who wants the other one has a one-word change.
They are recorded here so a reader diffing the two templates finds the missing branch and reaches for this table before "fixing" it.

### The option inputs carry `aria-invalid`, which Filament's do not

Filament's `ToggleButtons` writes no validity marker on its inputs at all: neither tree emits a `fi-valid` / `fi-invalid` class or an `aria-invalid`, and vendor's `toggle-buttons.css` has no invalid arm (the buttons carry a colour, not a ring).
The port writes `aria-invalid` on every input, the attribute the port's other option fields emit in place of their vendor class pair ([`Radio`](radio.md#the-option-inputs-carry-aria-invalid-instead-of-a-fi-valid--fi-invalid-class-pair)).
Here it is for assistive technology only, and no stylesheet keys off it: a radiogroup is invalid as a whole, and a screen reader should say so on every control in it.

### The extra input attributes win where Filament's lose by going last

Vendor's `ToggleButtons` is the one field composing this trait that never merges the two attribute sets.
It prints its own attributes as literal markup and appends `$extraInputAttributeBag->toHtml()` after them ([`:161-171`](../../../vendor/filament/forms/src/Components/ToggleButtons.php), and again in the grouped tree), and HTML duplicate-attribute parsing is **first**-wins — so in Filament the field's own `id`, `type` and `value` beat anything `extraInputAttributes()` sets, and an author trying to override one is silently ignored.
Its `merge()`-based siblings (`TextInput`, `Textarea`, `Select`, `Radio`) give the extras the win instead.

The port's templates put `component.getExtraInputAttributes` as the final `attrs()` source, which is later-source-wins, so the **extras win** here as they do on every other field.
That is the same override [`Checkbox`](checkbox.md#the-checkboxs-extra-input-attributes-win-where-filaments-lose) makes, for the same reason: `extraInputAttributes()` is an escape hatch, and an escape hatch a field can silently outrank is not one.
The order is the port-wide contract recorded in [`has-extra-input-attributes.md`](has-extra-input-attributes.md#the-extras-beat-the-attributes-the-field-computes), pinned here by `ToggleButtonsTest`'s extra-attributes case, whose `['disabled' => false]` un-disables a `disabled()` field — the assertion that would fail against vendor's order.

Nothing about the *rendered* markup differs otherwise: an extra attribute the field does not compute lands identically either way.
Only a **collision** on an attribute an input computes for itself parts the two.
`class` cannot collide at all: `attrs()` merges `class` as a token list rather than letting a later source replace it, so an extra class joins the control's own instead of displacing them.

### `extraAttributes()` stays on the field wrapper

Vendor starts the container's attribute bag from `getExtraAttributeBag()`, so `extraAttributes()` lands on the group element.
The port's [`FieldWrapper`](../../../packages/forms/templates/components/FieldWrapper/index.html.twig) spreads it on the wrapper `<div>` for every field and the group does not repeat it — the port-wide placement, reasoned out in [`field.md`](field.md#extra-attribute-precedence-is-fixed-by-source-order) and resolved the same way by [`Radio`](radio.md#extraattributes-stays-on-the-field-wrapper) and [`Repeater`](repeater.md).
`extraInputAttributes()` is the hatch that reaches the controls, and here it reaches **every** one of them.

Vendor also hands the wrapper `class="fi-fo-toggle-buttons-wrp"` and `tabindex="-1"`, and the port emits neither.
The class exists to feed one rule — `&.fi-fo-field-has-inline-label { & .fi-fo-field-label-col { @apply sm:pt-1.5 } }` — whose other half, the inline-label split, is unported and whose sibling arms [`field.css` omits](styles.md#the-field-stylesheet-omits-the-leading-schema-un-grow) pending schemas' `HasInlineLabel`.
Shipping a class with no rule and a rule with no reachable selector would be dead markup twice over, so both wait for that concern, which is when the rule can be verified rather than guessed.
The `tabindex="-1"` goes with them: nothing in the port calls `focus()` on a field wrapper.

### The `boolean()` icons resolve their alias at render, not at call time

Filament resolves the two overridable icons eagerly: `FilamentIcon::resolve(FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE) ?? Heroicon::Check` runs inside `boolean()`, so the alias is consulted once and the result is stored as the icon.
The port resolves aliases at **render**, through the [`Icon`](../support/icon-manager.md) component's `alias` prop — the house pattern every other alias consumer follows ([`Pagination`](../support/pagination.md), the panels shell, tables' search field).

`boolean()` stores the plain fallback names — `heroicons:check-20-solid` and `heroicons:x-mark-20-solid`, the **mini 20px** set that vendor's unprefixed `Heroicon::Check` / `Heroicon::XMark` resolve to at `generate_icon_html()`'s default size ([why the suffix is required](../support/icon-manager.md#an-unprefixed-heroicon-case-is-the-mini-20px-set-and-our-literals-must-spell-it)) — and the field records *which* alias each of those two icons answers to, in a `$iconAliases` map the templates read through a `getIconAlias(value)` getter.
That getter is the port's own addition: render-time resolution needs the field to say which of its icons is an overridable default, where eager resolution does not.
Only `boolean()` fills the map, so `getIconAlias()` is `null` for every option of every other configuration and the `Icon` tag falls back to the mapped icon.

The one behavioural delta is the ordering: calling `icons([…])` *after* `boolean()` replaces the icons but not the alias map, so a registered override for `components.toggle-buttons.boolean.true` would still win over the caller's own icon on the `1` option, where Filament's eager resolution lets the later `icons()` win.
It needs both an application-level alias registration and a `boolean()`-then-`icons()` sequence to observe, and reversing it would mean clearing the map from the `HasIcons` setter; it is recorded rather than fixed.

### The boolean labels are untranslated literals

`boolean()` sets the literal `'Yes'` and `'No'` where Filament reads `__('filament-forms::components.toggle_buttons.boolean.true')` and `…false`.
The translation surface is unported throughout, matching [`Radio`](radio.md#the-boolean-labels-are-untranslated-literals), [`Select`'s placeholder](select.md#the-default-placeholder-is-an-untranslated-literal) and [`getDefaultLabel`](field.md#getdefaultlabel-drops-label-translation).
The two keys are recorded here so the strings can be found when a translation layer lands; Filament's own English values are the same two words.
The key segment is `toggle_buttons` with an underscore, where the field's icon aliases use `toggle-buttons` with a dash — that inconsistency is Filament's.

### The `Arrayable` arm is dropped from the colour and icon maps

Filament types `colors()` and `icons()` as `array<…> | Arrayable | Closure | null` and unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()` before use; `tooltips()` takes no `Arrayable` in Filament either.
The port types both `array|Closure|null`.

`Arrayable` is a Laravel contract with no Symfony equivalent, and the closure arm already covers the case it exists for — `colors(fn () => $collection->toArray())` is one call longer and needs no interface.
Identical to the decision recorded for [`HasDescriptions`](has-descriptions.md#the-arrayable-arm-is-dropped), and it returns the same way: if a Doctrine-flavoured collection contract ever earns a port, every such setter widens together.

### `getTooltip()`'s return is narrowed

Filament declares `getTooltip(): string | Htmlable | array | null`.
The `array` arm is unreachable: the value is read out of a map whose own declared value type is `array<string | Htmlable>`, so no element can be an array unless a caller defeats the type.
The port declares `string|Markup|null` — the `Htmlable` → `Markup` translation plus the dead arm removed.

Narrowing a return is safe for callers (every value the port can produce satisfies the vendor signature) and it is what lets the templates hand the result straight to an attribute without a type check.
Widening back would only be right if Filament ever means the array arm as a per-option attribute bag, which nothing reads it as.

### The nested-recursive rules trait is dead API in Filament's own class

`ToggleButtons` `use`s `Concerns\HasNestedRecursiveValidationRules` but does **not** implement `Contracts\HasNestedRecursiveValidationRules`, and both readers of that trait's output sit behind an `instanceof Contracts\HasNestedRecursiveValidationRules` check: [`CanBeValidated::dehydrateValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) (`:921`) before it adds the `{$statePath}.*` rules, and [`Field::wrapEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/Field.php) (`:299`) before it reads their errors.
Both checks are false for this class in Filament, so even in `multiple()` mode `nestedRecursiveRules()` configures nothing that is ever read.
It looks like a vendor bug rather than a design.

The port **fixes the omission**: `ToggleButtons` takes both the [concern and the contract](can-be-validated.md#nested-constraints-land-as-one-all-where-laravel-keys-a-path), so `nestedRecursiveConstraints()` configures something the builder reads.
Reproducing an inert API faithfully would be the worse call — there is no consumer relying on the members doing nothing, and the divergence is one a migrating user only ever notices as the feature working.

### There is nothing to publish a view override against

`getPublishedViewOverrideCheckPath()` returns the Blade view name Filament checks for a *published* override — the mechanism by which an application copies a package view into its own `resources/views` and Filament notices.
`ViewComponent` declares it over a `$publishedViewOverrideCheckPath` property most fields set, and `ToggleButtons` and `Repeater` override it to answer per variant.

The port has no equivalent, and does not need one: overriding a Twig template is a first-class Symfony feature (a template of the same name under `templates/bundles/SylamentFormsBundle/` wins with no registration), and the field's own [`template()`](#the-grouped-variant-is-a-second-template-picked-by-gettemplate) setter overrides per instance.
Nothing would read the returned path, so it is skipped rather than ported to a getter with no consumer.
