# ViewColumn (tables)

[`Sylament\Tables\Columns\ViewColumn`](../../../packages/tables/src/Columns/ViewColumn.php)
— the escape hatch: a column whose entire cell body is a Twig template the user
writes. It adds no state handling, no chrome and no options of its own;
everything a consumer calls on it comes from [`Column`](column.md) and, above it,
support's [`ViewComponent`](../support/view-component.md) — `template()`,
`templateData()` and `defaultTemplate()`.

It is [`ViewField`](../forms/view-field.md)'s twin one package over, down to the
one method each carries. Filament's class is literally
`class ViewColumn extends Column {}`; ours adds the same fail-fast
`getTemplate()` override, for the reason recorded on that file
([why the throw is not on the base](../forms/view-field.md#the-missing-template-throw-lives-on-viewfield-rather-than-the-base)) —
so this file is mostly pointers, and the reasoning is not duplicated here.

`view()` → `template()` and `viewData()` → `templateData()` are the systematic
Blade → Twig translation, not renames — the
[idiom table](../renames.md#idiom) owns that row.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/ViewColumn.php`](../../../vendor/filament/tables/src/Columns/ViewColumn.php)
  — the whole class, three lines
- [`support/src/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)
  — `view()` / `viewData()` / `getView()`, where the surface actually lives
- [`forms/src/Components/ViewField.php`](../../../vendor/filament/forms/src/Components/ViewField.php)
  — the twin, and the shape this port copies

## Surface

Every point this column adds over `Column`. `Column`'s own surface is tracked in
[`column.md`](column.md), the inherited template surface in
[`support/view-component.md`](../support/view-component.md). _Blocked by_ names
what has to land first. _Detail_ links a permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction (`ViewColumn::make('orbit')->template(…)`) | [`Columns/ViewColumn.php`](../../../vendor/filament/tables/src/Columns/ViewColumn.php) | done | — | — |
| `template()` / `templateData()` ⟵ `view()` / `viewData()` | [`support/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [detail](../support/view-component.md) |
| The template receives the column as `component` | [`$viewIdentifier = 'column'`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](../support/view-component.md#the-instance-is-always-exposed-as-component) |
| [Fail-fast on a missing template](../../../packages/tables/src/Columns/ViewColumn.php) | [`ViewComponent::getView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](../forms/view-field.md#the-missing-template-throw-lives-on-viewfield-rather-than-the-base) |
| The cell wrapper is the table partial's, not the template's | [`Column::renderInLayout()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | [diverges](#the-cell-wrapper-is-served-around-the-template-not-by-it) |
| `$record` in template scope | [`support/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#no-record-variable-is-exposed--the-getter-is-the-api) |

Coverage: a fixture template rendering with the column in scope as `component`
(reading `getName()`, `getState()` and `getRecord()` off it), `templateData`
merged into that scope from `template()`'s second argument, the absence of a
`record` variable, and the throw on both `getTemplate()` and the render path —
by the Unit
[`ViewColumnTest`](../../../packages/tables/tests/Unit/Columns/ViewColumnTest.php)
and the Integration
[`ViewColumnTest`](../../../packages/tables/tests/Integration/Columns/ViewColumnTest.php),
against the fixture template under
[`tests/Fixtures/Twig/templates/partials/ViewColumnFixture/`](../../../packages/tables/tests/Fixtures/Twig/templates/partials/ViewColumnFixture).

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

Every row above is settled — **done** or **skipped** — so none has a write-up
here now. A row moving to **in progress** or **blocked** grows one.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### No `record` variable is exposed — the getter is the API

Filament's `ViewComponent::extractPublicMethods()` sprays every public method of
the component into the view's scope as a closure, which is how a Blade custom
column reaches `$record` and friends without asking for them. That mechanism is
[not ported](../support/view-component.md#extractpublicmethods-is-not-ported),
and this column is where its absence is most visible, so it is worth stating
plainly: a `ViewColumn` template gets `component`, whatever `templateData()`
supplied, and nothing else.

The record is one getter away — `component.getRecord` — and the house rule for
`getExtraTemplateData()` is that it carries only what a single getter cannot
answer. Adding `record` would be a second name for the same value and would
shadow a `record` key a user's own `templateData()` passed. The state is the
same story: `component.getState`, not `state`.

This is a strict superset in reach and a strict subset in magic — every value
Filament's extraction would have provided is reachable, spelled as the method
that provides it.

### The cell wrapper is served around the template, not by it

[`ViewField`](../forms/view-field.md#the-wrapper-is-the-templates-to-open)'s
template has to open its own field wrapper, because a form field's chrome is the
field's. A column's chrome is the **table's**: the `<td class="sy-ta-cell">` and
the `<div class="sy-ta-col">` around it are assembled by the
[table partial](../../../packages/tables/templates/partials/Table/index.html.twig)
for every column alike, and a `ViewColumn` template renders inside them without
doing anything.

So the two escape hatches read the same and behave differently at their edges: a
`ViewField` template that opens nothing renders naked on the page, while a
`ViewColumn` template that opens nothing still lands in a properly aligned,
padded, responsive cell. What the template does *not* get for free is the
per-column presentation the other columns' partials each apply themselves —
`getAlignmentClass`, `isInline`, `canWrap`, `getExtraAttributes`, the
placeholder branch and the per-item tooltips are all readable off `component`
and all the template's to honour or ignore.
