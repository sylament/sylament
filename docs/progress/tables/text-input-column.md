---
description: The editable `TextInputColumn`, whose text-input cell writes the typed value back onto the record on `change`.
ports:
  - vendor/filament/tables/src/Columns/TextInputColumn.php
  - vendor/filament/tables/resources/css/columns/text-input.css
owns:
  - packages/tables/assets/src/styles/components/text-input-column.css
  - packages/tables/src/Columns/TextInputColumn.php
  - packages/tables/templates/partials/TextInputColumn/
---
# TextInputColumn (tables)

[`Sylament\Tables\Columns\TextInputColumn`](../../../packages/tables/src/Columns/TextInputColumn.php) is an editable [`Column`](column.md) whose cell is a text input: committing it on `change` writes the typed value back onto the record through the host's [`updateTableColumnState()`](has-columns.md) live action.
It ports Filament's [`Filament\Tables\Columns\TextInputColumn`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php).
[`column.md`](column.md) tracks what every column carries, and [`has-columns.md`](has-columns.md) tracks the write-back seam the editable columns share.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) · [has-columns.md](has-columns.md#a-record-type-the-input-cannot-hold-seeds-a-type-constraint-and-the-write-refuses-the-rest) |
| [`setUp()` — `disabledClick()`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn::setUp()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | — |
| [`type()` / `getType()` — defaulting to `text`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn::type()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | — |
| [`inputMode()` / `getInputMode()`](../../../packages/tables/src/Columns/TextInputColumn.php) | [`Forms\Concerns\HasInputMode`](../../../vendor/filament/forms/src/Components/Concerns/HasInputMode.php) | done | — | [diverges](#inputmode-is-declared-on-the-column-because-forms-ships-no-hasinputmode) |
| [`step()` / `getStep()`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Forms\Concerns\HasStep`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | — |
| [`extraInputAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Forms\Concerns\HasExtraInputAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| [`prefix()` / `suffix()` / `inlinePrefix()` / `inlineSuffix()` + the four getters](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [semantics](#the-affix-surface-is-the-columns-own-not-forms-hasaffixes) |
| [`prefixIcon()` / `suffixIcon()` / `prefixIconColor()` / `suffixIconColor()` + their getters](../../../packages/tables/src/Columns/TextInputColumn.php) | [`TextInputColumn`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [semantics](#the-affix-surface-is-the-columns-own-not-forms-hasaffixes) |
| [`placeholder()` — the empty-input copy](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`Support\Concerns\HasPlaceholder`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [semantics](#the-placeholder-is-the-inputs-not-the-empty-cell-copy) |
| [`$template`](../../../packages/tables/src/Columns/TextInputColumn.php) + [the partial it names](../../../packages/tables/templates/partials/TextInputColumn/index.html.twig) | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [semantics](#the-chrome-is-supports-inputwrapper-around-forms-sy-fo-input) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/TextInputColumn/index.html.twig) | [`text-input.js`](../../../vendor/filament/tables/resources/js/components/columns/text-input.js) | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [The refusal chrome — `aria-invalid`, the echoed input and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/TextInputColumn/index.html.twig) | [`text-input.js`](../../../vendor/filament/tables/resources/js/components/columns/text-input.js) `error` | done | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline`, `min-w-48` and the padding on the root](../../../packages/tables/assets/src/styles/components/text-input-column.css) | [`columns/text-input.css`](../../../vendor/filament/tables/resources/css/columns/text-input.css) | done | — | [color-column.md](color-column.md#the-stacked-on-mobile-rule-has-nothing-to-select) |
| `wire:loading.attr` / `wire:target` / `x-bind:disabled` on the control | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | skipped | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| `wire:ignore.self` on the root and the `x-ref="serverState"` hidden input | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | skipped | — | [toggle-column.md](toggle-column.md#the-cell-root-morphs-and-carries-the-state-so-filaments-alpine-plumbing-around-it-has-no-counterpart) |
| `mask()` / `getMask()` | [`TextInputColumn::mask()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | blocked | a masking-engine decision | [text-input.md](../forms/text-input.md#input-masking-waits-on-an-engine-decision-and-rawjs-is-not-it) |
| `alignment()` aligning the typed text | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | todo | — | [detail](#alignment-lands-on-the-cell-root-where-no-rule-aligns-the-input) |
| `type('color')` opening the native picker on click | [`TextInputColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextInputColumn.php) | todo | — | — |

## Details

### `alignment()` lands on the cell root, where no rule aligns the input

Filament puts `fi-align-*` on the `<input>` itself, defaulting to `start`, and support's `input/index.css` turns each arm into a `text-align`.
The port's partial puts `getAlignmentClass()` on the cell root instead, where [`text-input-column.css`](../../../packages/tables/assets/src/styles/components/text-input-column.css) has no alignment rule, and the table's `.sy-ta-col` wrapper resets the text to `text-start` besides.
Moving the class onto the input also needs the `.sy-align-*` arms that forms' input stylesheet lacks ([styles.md](../forms/styles.md#the-input-stylesheet-stops-at-the-alignment-arms)).

## Semantics

### The affix surface is the column's own, not forms' `HasAffixes`

Filament declares the affix setters and getters on the column rather than composing forms' `HasAffixes`, whose prefix and suffix actions a table cell has nowhere to put, and the port follows.
So `prefix()` and `suffix()` take an `isInline` second argument that forwards to `inlinePrefix()` / `inlineSuffix()`, and `prefixIcon()` / `suffixIcon()` do the same: one setter call decides both the affix and its placement.

### The placeholder is the input's, not the empty-cell copy

`HasPlaceholder` is a `Column`-level trait, and on a read-only column it is the copy shown when the cell state is blank.
Here it is the `<input placeholder>` instead, as in Filament: an editable cell always renders its control, so there is no blank branch to fill.
The setter, the getter and the closure evaluation are the same trait.

### The chrome is support's `InputWrapper` around forms' `sy-fo-input`

The cell renders `<twig:SylamentSupport:InputWrapper>`, the input chrome every text-like forms control renders inside, around `input.sy-fo-input`, without the `id`, `name` and `data-model` a schema field carries and with an `aria-label` taken from the column label with any markup stripped.
The wrapper's `valid` prop is what paints the refusal ring, and its affix props render the prefix and suffix labels and icons.

## Divergences

### `inputMode()` is declared on the column because forms ships no `HasInputMode`

Filament composes `Forms\Components\Concerns\HasInputMode` here.
Forms ships no such trait, because its `TextInput` answers `getInputMode()` from `numeric()` and `integer()` with no setter ([text-input.md](../forms/text-input.md#input-mode-is-a-bespoke-getter-not-the-hasinputmode-concern)), so the column declares `inputMode()` / `getInputMode()` itself, with Filament's names and closure evaluation.
A forms port of the trait would replace the pair.
