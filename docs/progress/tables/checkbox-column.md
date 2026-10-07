---
description: 'An editable `Column` whose cell is a plain checkbox: ticking it writes a boolean back onto the record through the host''s `updateTableColumnState()`… (draft)'
ports:
  - vendor/filament/tables/src/Columns/CheckboxColumn.php
  - vendor/filament/tables/resources/css/columns/checkbox.css
owns:
  - packages/tables/assets/src/styles/components/checkbox-column.css
  - packages/tables/src/Columns/CheckboxColumn.php
  - packages/tables/templates/partials/CheckboxColumn/
---
# CheckboxColumn (tables)

[`Sylament\Tables\Columns\CheckboxColumn`](../../../packages/tables/src/Columns/CheckboxColumn.php) — an editable [`Column`](column.md) whose cell is a plain checkbox: ticking it writes a boolean back onto the record through the host's [`updateTableColumnState()`](has-columns.md) live action.
It ports Filament's [`Filament\Tables\Columns\CheckboxColumn`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php).
Everything [`Column`](column.md) carries is tracked there, and the write-back seam the four editable columns share is tracked on [`has-columns.md`](has-columns.md), not repeated here.

## Surface

This component has no divergences of its own; the write-back's are on [`has-columns.md`](has-columns.md#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Editable` — `validate()` + `updateState()`](../../../packages/tables/src/Columns/Contracts/Editable.php) | [`Contracts\Editable`](../../../vendor/filament/tables/src/Columns/Contracts/Editable.php) | done | — | [has-columns.md](has-columns.md#surface) |
| [`setUp()` — `disabledClick()` and the boolean seed](../../../packages/tables/src/Columns/CheckboxColumn.php) | [`CheckboxColumn::setUp()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-boolean-rule-becomes-a-type-constraint) |
| [`extraInputAttributes()`](../../../packages/forms/src/Components/Concerns/HasExtraInputAttributes.php) | [`Forms\Concerns\HasExtraInputAttributes`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Columns/CheckboxColumn.php) + [the partial it names](../../../packages/tables/templates/partials/CheckboxColumn/index.html.twig) | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | — | [semantics](#the-skin-is-supports-sy-checkbox-the-one-selection-already-uses) |
| [`getClientState()` on the root + the `sylament--tables--editable-cell` controller](../../../packages/tables/templates/partials/CheckboxColumn/index.html.twig) | `checkboxTableColumn()` Alpine data | done | — | [has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph) |
| [The refusal chrome — `aria-invalid` and `<p class="sy-ta-cell-error">`](../../../packages/tables/templates/partials/CheckboxColumn/index.html.twig) | the Alpine `error` binding | done | — | [has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) |
| [`sy-inline` and the alignment arms on the root](../../../packages/tables/assets/src/styles/components/checkbox-column.css) | [`columns/checkbox.css`](../../../vendor/filament/tables/resources/css/columns/checkbox.css) | done | — | [toggle-column.md](toggle-column.md#the-stacked-on-mobile-arm-is-not-ported) |
| `wire:loading.attr` / `wire:target` on the control | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | done | — | [toggle-column.md](toggle-column.md#the-in-flight-affordance-is-the-controllers-not-a-loading-attribute) |
| `x-tooltip` for the error bubble, `wire:ignore.self` on the root | [`CheckboxColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/CheckboxColumn.php) | skipped | — | Same two reasons as [`ToggleColumn`](toggle-column.md#surface): the refusal is server-rendered, and the root must morph to carry the `state` value |

Every row is covered by the Unit [`CheckboxColumnTest`](../../../packages/tables/tests/Unit/Columns/CheckboxColumnTest.php) and the Integration [`CheckboxColumnTest`](../../../packages/tables/tests/Integration/Columns/CheckboxColumnTest.php); the write itself is [`HasColumnsTest`](../../../packages/tables/tests/Integration/Concerns/HasColumnsTest.php)'s.

## Semantics

### The skin is support's `sy-checkbox`, the one selection already uses

Filament's cell renders `input.fi-checkbox`, the same skin as the row-selection boxes.
The port does the same with `sy-checkbox`, which [support already ships](../support/README.md) and record selection already renders — so the column adds no checkbox styling of its own, only the cell padding and alignment.
Every editable cell reuses a shipped skin this way — support's `sy-checkbox` here, forms' `sy-fo-toggle*`, `sy-fo-input` and `sy-fo-select` in the other three — rather than hoisting them into support; the hoist is a follow-up, so this column touches no forms file.
