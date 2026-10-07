---
description: The `ViewColumn` escape hatch, whose entire cell body is a Twig template the user writes.
ports:
  - vendor/filament/tables/src/Columns/ViewColumn.php
owns:
  - packages/tables/src/Columns/ViewColumn.php
---
# ViewColumn (tables)

[`Sylament\Tables\Columns\ViewColumn`](../../../packages/tables/src/Columns/ViewColumn.php) is the escape hatch: a column whose entire cell body is a Twig template the user writes.
It adds no state handling, no chrome and no options of its own; what a consumer calls on it comes from [`Column`](column.md) and, above it, support's [`ViewComponent`](../support/view-component.md): `template()`, `templateData()` and `defaultTemplate()`.
Filament's class is `class ViewColumn extends Column {}`; the port adds the fail-fast `getTemplate()` override [`ViewField`](../forms/view-field.md) carries, for the reason recorded there ([why the throw is not on the base](../forms/view-field.md#the-missing-template-throw-lives-on-viewfield-rather-than-the-base)).
`view()` → `template()` and `viewData()` → `templateData()` are the Blade → Twig translation the [idiom table](../translations.md#idiom) records, not renames.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction (`ViewColumn::make('orbit')->template(…)`) | [`Columns/ViewColumn.php`](../../../vendor/filament/tables/src/Columns/ViewColumn.php) | done | — | — |
| `template()` / `templateData()` ⟵ `view()` / `viewData()` | [`support/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [view-component.md](../support/view-component.md) |
| The template receives the column as `component` | [`$viewIdentifier = 'column'`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](../support/view-component.md#the-instance-is-always-exposed-as-component) |
| [Fail-fast on a missing template](../../../packages/tables/src/Columns/ViewColumn.php) | [`ViewComponent::getView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](../forms/view-field.md#the-missing-template-throw-lives-on-viewfield-rather-than-the-base) |
| The cell wrapper is the table partial's, not the template's | [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) `fi-ta-col` | done | — | [semantics](#the-cell-wrapper-is-served-around-the-template-not-by-it) |
| `$record` in template scope | [`support/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#no-record-variable-is-exposed--the-getter-is-the-api) |

## Semantics

### The cell wrapper is served around the template, not by it

[`ViewField`](../forms/view-field.md#the-wrapper-is-the-templates-to-open)'s template has to open its own field wrapper, because a form field's chrome is the field's.
A column's chrome is the **table's**: the `<td class="sy-ta-cell">` and the `<div class="sy-ta-col">` inside it are assembled by the [table partial](../../../packages/tables/templates/partials/Table/index.html.twig) for every column alike, as Filament's table view assembles `fi-ta-col`, and a `ViewColumn` template renders inside them without doing anything.
So the two escape hatches read the same and behave differently at their edges: a `ViewField` template that opens nothing renders naked on the page, while a `ViewColumn` template that opens nothing still lands in an aligned, padded, responsive cell.
What the template does *not* get for free is the per-column presentation the other columns' partials each apply themselves: `getAlignmentClass`, `isInline`, `getExtraAttributes`, the placeholder branch and the tooltips are all readable off `component`, and all the template's to honour or ignore.

## Divergences

### No `record` variable is exposed — the getter is the API

Filament's `ViewComponent::extractPublicMethods()` sprays every public method of the component into the view's scope as a closure, which is how a Blade custom column reaches `$record` and friends without asking for them.
That mechanism is [not ported](../support/view-component.md#extractpublicmethods-is-not-ported), so a `ViewColumn` template gets `component`, whatever `templateData()` supplied, and nothing else.
The record is one getter away, `component.getRecord`, and `getExtraTemplateData()` carries only what a single getter cannot answer, so a `record` variable would be a second name for the same value.
The state is the same story: `component.getState`, not `state`.
