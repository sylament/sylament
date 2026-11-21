---
description: The concern that gives a component the content it renders instead of its state when that state is blank.
ports:
  - vendor/filament/support/src/Concerns/HasPlaceholder.php
owns:
  - packages/support/src/Components/Concerns/HasPlaceholder.php
---
# HasPlaceholder (support)

`Sylament\Support\Components\Concerns\HasPlaceholder` ports Filament's support-tier trait of the same name, typed `string | Markup | TranslatableInterface | Closure | null`.
Tables' [`Column`](../tables/column.md) composes it, and its partials render `<p class="sy-ta-placeholder">` in place of an empty cell.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`placeholder()` setter + property](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`HasPlaceholder::placeholder()`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [semantics](#a-message-is-translated-by-the-getter-so-a-reader-still-gets-a-string-or-markup) |
| [`getPlaceholder()`](../../../packages/support/src/Components/Concerns/HasPlaceholder.php) | [`HasPlaceholder::getPlaceholder()`](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php) | done | — | [semantics](#three-copies-of-this-trait-ship-and-that-is-filaments-shape-too) |

## Semantics

### Three copies of this trait ship, and that is Filament's shape too

`Sylament\Forms\Components\Concerns\HasPlaceholder` and `Sylament\Tables\Filters\Concerns\HasPlaceholder` are separate traits, and neither imports this one.
Filament carries the same three traits under the same three namespaces ([support](../../../vendor/filament/support/src/Concerns/HasPlaceholder.php), [forms](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php), [tables filters](../../../vendor/filament/tables/src/Filters/Concerns/HasPlaceholder.php)), so pointing forms at the support one would be a divergence rather than a cleanup.
The two lower copies are also narrower, taking no `Markup`, because an input's `placeholder` attribute is a plain string where a cell's placeholder is rendered content; repointing them would widen a published signature.

### A message is translated by the getter, so a reader still gets a string or `Markup`

`placeholder()` accepts a `TranslatableInterface` beside a string, a `Markup` and a closure, and `getPlaceholder()` translates it, a closure's message included, as [every text getter does](../../internals/i18n.md#a-setter-takes-a-message-and-its-getter-translates-it).
