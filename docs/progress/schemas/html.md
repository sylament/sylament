---
description: The Html schema component, which prints its content as raw HTML with no wrapper element.
ports:
  - vendor/filament/schemas/src/Components/Html.php
owns:
  - packages/schemas/src/Components/Html.php
  - packages/schemas/templates/partials/Html/
---
# Html (schemas)

[`Sylament\Schemas\Components\Html`](../../../packages/schemas/src/Components/Html.php) extends the schemas [`Component`](component.md).
Its template, [`Html/index.html.twig`](../../../packages/schemas/templates/partials/Html/index.html.twig), prints a string or a `Twig\Markup` unescaped and `null` as nothing, as Filament's `toEmbeddedHtml()` does.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(string\|Markup\|Closure\|null $content)`](../../../packages/schemas/src/Components/Html.php) | [`Html::make()`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — | — |
| [`content()` / `getContent()`](../../../packages/schemas/src/Components/Html.php) | [`Components/Html.php`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — | — |
| [`Html/index.html.twig`](../../../packages/schemas/templates/partials/Html/index.html.twig) | [`Html::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Html.php) | done | — | [component.md](component.md#each-template-ports-a-php-toembeddedhtml-not-a-blade-view) |
| `HasEmbeddedView` contract / `toEmbeddedHtml()` | [`Html::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Html.php) | skipped | — | [diverges](../support/view-component.md#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
