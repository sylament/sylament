# HasPlaceholder (support)

`Sylament\Support\Components\Concerns\HasPlaceholder` — the shared concern that gives a component the text it renders **instead of** its state when that state is blank.
It ports Filament's support-tier `HasPlaceholder` whole: a `string | Markup | Closure | null` property, a fluent `placeholder()` setter and a `getPlaceholder()` reader that evaluates it.
Its first consumer is tables' `Column`, whose partials render `<p class="sy-ta-placeholder">` in place of an empty cell.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Concerns/HasPlaceholder.php`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) — the trait ported here, method for method

## Surface

Every point of this concern.
_Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details), deleted once the row is **done**; **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`placeholder()` setter + property](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`HasPlaceholder::placeholder()`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | — |
| [`getPlaceholder()`](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`HasPlaceholder::getPlaceholder()`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [diverges](#three-copies-of-this-trait-ship-and-that-is-filaments-shape-too) |

## Details

Both rows are **done**, exercised by [`HasPlaceholderTest`](../../../packages/support/tests/Unit/Components/Concerns/HasPlaceholderTest.php).
No row has a write-up here.

## Divergences

### Three copies of this trait ship, and that is Filament's shape too

`Sylament\Forms\Components\Concerns\HasPlaceholder` and `Sylament\Tables\Filters\Concerns\HasPlaceholder` already existed when this one landed, and neither was repointed at it.
That is not an oversight: Filament carries the same three traits under the same three namespaces ([support](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php), [forms](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php), [tables filters](../../../vendor/filament/tables/src/Filters/Concerns/HasPlaceholder.php)), so importing the support one from forms would be a divergence rather than a cleanup, and the two lower copies are narrower — `string | Closure | null`, because an input's `placeholder` attribute is a plain string where a cell's placeholder is rendered content and takes `Markup`.
Repointing them would widen a published signature.

The dedupe is therefore a deliberate non-goal for now rather than debt: it is revisited only if a fourth copy is wanted.
