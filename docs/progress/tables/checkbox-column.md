---
description: The editable `CheckboxColumn`, whose checkbox cell writes a boolean back onto the record.
ports:
  - vendor/filament/tables/src/Columns/CheckboxColumn.php
  - vendor/filament/tables/resources/css/columns/checkbox.css
owns:
  - packages/tables/assets/src/styles/components/checkbox-column.css
  - packages/tables/src/Columns/CheckboxColumn.php
  - packages/tables/templates/partials/CheckboxColumn/
---
# CheckboxColumn (tables)

[`Sylament\Tables\Columns\CheckboxColumn`](../../../packages/tables/src/Columns/CheckboxColumn.php) is an editable [`Column`](column.md) whose cell is a plain checkbox: ticking it writes a boolean back onto the record through the host's [`updateTableColumnState()`](has-columns.md) live action.
It ports Filament's [`Filament\Tables\Columns\CheckboxColumn`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php).
[`column.md`](column.md) tracks what every column carries, and [`has-columns.md`](has-columns.md) tracks the write-back seam the editable columns share.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()` and the boolean seed](../../../packages/tables/src/Columns/CheckboxColumn.php) | [`CheckboxColumn::setUp()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-boolean-rule-becomes-a-type-constraint) |
| [`extraInputAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Forms\Concerns\HasExtraInputAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Columns/CheckboxColumn.php) + [the partial it names](../../../packages/tables/templates/partials/CheckboxColumn/index.html.twig) | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | — | [semantics](#the-skin-is-supports-sy-checkbox-the-one-selection-already-uses) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/CheckboxColumn/index.html.twig) | [`checkbox.js`](../../../vendor/filament/tables/resources/js/components/columns/checkbox.js) | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [The refusal chrome — `aria-invalid` and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/CheckboxColumn/index.html.twig) | [`checkbox.js`](../../../vendor/filament/tables/resources/js/components/columns/checkbox.js) `error` | in progress | — | [detail](#no-test-renders-a-refused-checkbox) · [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline` and the alignment arms on the root](../../../packages/tables/assets/src/styles/components/checkbox-column.css) | [`columns/checkbox.css`](../../../vendor/filament/tables/resources/css/columns/checkbox.css) | done | — | [color-column.md](color-column.md#the-stacked-on-mobile-rule-has-nothing-to-select) |
| `wire:loading.attr` / `wire:target` / `x-bind:disabled` on the control | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | skipped | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| `wire:ignore.self` on the root and the `x-ref="serverState"` hidden input | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | skipped | — | [toggle-column.md](toggle-column.md#the-cell-root-morphs-and-carries-the-state-so-filaments-alpine-plumbing-around-it-has-no-counterpart) |

## Details

### No test renders a refused checkbox

The partial renders `aria-invalid="true"` on the control and `<p class="sy-ta-cell-error">` under it whenever `getUpdateStateError()` answers.
[`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php) renders that branch only through a `TextInputColumn`, and the Integration [`CheckboxColumnTest`](../../../packages/tables/tests/Integration/Columns/CheckboxColumnTest.php) renders only clean cells.
A test that renders a refused write through this partial is what is missing.

## Semantics

### The skin is support's `sy-checkbox`, the one selection already uses

Filament's cell renders `input.fi-checkbox-input`, the skin of its row-selection boxes.
The port does the same with `sy-checkbox`, which [support's stylesheet](../../../packages/support/assets/src/styles/components/checkbox.css) styles and the table partial's record, group and page selection boxes already render, so the column adds no checkbox styling of its own, only the cell padding and alignment.
