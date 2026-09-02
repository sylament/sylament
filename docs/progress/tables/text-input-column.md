# TextInputColumn (tables)

[`Sylament\Tables\Columns\TextInputColumn`](../../../packages/tables/src/Columns/TextInputColumn.php)
— an editable [`Column`](column.md) whose cell is a text input: committing it on
`change` writes the typed value back onto the record through the host's
[`updateTableColumnState()`](has-columns.md) live action. It ports Filament's
[`Filament\Tables\Columns\TextInputColumn`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php).
Everything [`Column`](column.md) carries is tracked there, and the write-back
seam the four editable columns share is tracked on
[`has-columns.md`](has-columns.md), not repeated here.

**Status:** done — see the
[status vocabulary](../README.md#statuses). One row is **skipped**
(`mask()`), and it is the only point of the class not built.

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/TextInputColumn.php`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php)
  — the class and its `toEmbeddedHtml()`
- [`tables/resources/css/columns/text-input.css`](../../../vendor/filament/tables/resources/css/columns/text-input.css)
  — the stylesheet [`text-input-column.css`](../../../packages/tables/assets/src/styles/components/text-input-column.css)
  is the prefix-renamed port of

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in Details,
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn::setUp()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | — |
| [`type()` / `getType()` — defaulting to `text`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn::type()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | — |
| [`inputMode()` / `getInputMode()`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`Forms\Concerns\HasInputMode`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#inputmode-is-a-member-of-the-class-because-forms-has-no-hasinputmode-yet) |
| [`step()` / `getStep()`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Forms\Concerns\HasStep`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | — |
| [`extraInputAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Forms\Concerns\HasExtraInputAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| [`prefix()` / `suffix()` / `inlinePrefix()` / `inlineSuffix()` + the four getters](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [semantics](#the-affix-surface-is-the-columns-own-not-forms-hasaffixes) |
| [`prefixIcon()` / `suffixIcon()` / `prefixIconColor()` / `suffixIconColor()` + their getters](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [semantics](#the-affix-surface-is-the-columns-own-not-forms-hasaffixes) |
| [`placeholder()` — the empty-input copy](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [semantics](#the-placeholder-is-the-inputs-not-the-empty-cell-copy) |
| [`$template`](../../../packages/tables/src/Columns/TextInputColumn.php) + [the partial it names](../../../packages/tables/templates/partials/TextInputColumn/index.html.twig) | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [semantics](#the-chrome-is-supports-inputwrapper-around-forms-sy-fo-input) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/TextInputColumn/index.html.twig) | `textInputTableColumn()` Alpine data | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [The refusal chrome — `aria-invalid`, the echoed input and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/TextInputColumn/index.html.twig) | the Alpine `error` binding | done | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline`, `min-w-48` and the padding on the root](../../../packages/tables/assets/src/styles/components/text-input-column.css) | [`columns/text-input.css`](../../../vendor/filament/tables/resources/css/columns/text-input.css) | done | — | [toggle-column.md](toggle-column.md#the-stacked-on-mobile-arm-is-not-ported) |
| `wire:loading.attr` / `wire:target` on the control | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble, `wire:ignore.self` on the root | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | skipped | — | Same two reasons as [`ToggleColumn`](toggle-column.md#surface): the refusal is server-rendered, and the root must morph to carry the `state` value |
| `mask()` / `getMask()` | [`TextInputColumn::mask()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | skipped | — | The setter takes a `RawJs` and configures Alpine Mask, an Alpine plugin the toolkit does not ship; a setter no builder consults is worse than an absent one ([column.md](column.md#surface)) |

Every built row is covered by the Unit
[`TextInputColumnTest`](../../../packages/tables/tests/Unit/Columns/TextInputColumnTest.php)
and the Integration
[`TextInputColumnTest`](../../../packages/tables/tests/Integration/Columns/TextInputColumnTest.php);
the write itself is
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php)'s.

## Divergences

### `inputMode()` is a member of the class because forms has no `HasInputMode` yet

Filament composes `Forms\Components\Concerns\HasInputMode` here. Forms has not
ported that trait, and this epic
[touches no forms file](checkbox-column.md#the-skin-is-supports-sy-checkbox-the-one-selection-already-uses),
so `inputMode()` / `getInputMode()` are declared on the column itself — the same
two members, the same names, the same evaluation. When forms ports the trait the
column composes it and the pair goes.

## Semantics

### The affix surface is the column's own, not forms' `HasAffixes`

Filament declares the eight affix members on the column rather than composing
forms' `HasAffixes`, and the port follows: forms' trait carries prefix and suffix
**actions**, which a table cell has nowhere to put. So `prefix()` and `suffix()`
take an `isInline` second argument that forwards to `inlinePrefix()` /
`inlineSuffix()`, and `prefixIcon()` / `suffixIcon()` do the same — one setter
call decides both the affix and its placement.

### The placeholder is the input's, not the empty-cell copy

`HasPlaceholder` is a `Column`-level trait, and on a read-only column it is the
copy shown when the cell state is blank. Here it is the `<input placeholder>`
instead: an editable cell always renders its control, so there is no blank branch
to fill. The setter, the getter and the closure evaluation are the same trait.

### The chrome is support's `InputWrapper` around forms' `sy-fo-input`

The cell renders `<twig:SylamentSupport:InputWrapper>` — the input chrome every
text-like forms control renders inside — around `input.sy-fo-input`, minus the
`id`, `name` and `data-model` a schema field carries and plus an `aria-label`
taken from the column label with any markup stripped. The wrapper's `valid` prop
is what paints the refusal ring, and the affix props are what render the
prefix/suffix labels and icons.
