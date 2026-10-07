---
description: The concern that gives a component an icon, a blank-value sentinel that suppresses the default, and the getter's fallback.
ports:
  - vendor/filament/support/src/Concerns/HasIcon.php
owns:
  - packages/support/src/Components/Concerns/HasIcon.php
---
# HasIcon (support)

`Sylament\Support\Components\Concerns\HasIcon` ports Filament's trait of the same name: `icon()` stores a blank value as a `false` sentinel, and `getIcon()` falls back to its `$default` only when the icon is unset, never when it is `false`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`icon()` / `getIcon()` / `hasIcon()`](../../../packages/support/src/Components/Concerns/HasIcon.php) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | [diverges](#the-renderable-normalization-branch-is-dropped) |

## Divergences

### The Renderable normalization branch is dropped

Filament's `getIcon()` carries a first branch (`if ($icon instanceof Renderable) { return new HtmlString($icon->render()); }`, from [PR #13512](https://github.com/filamentphp/filament/pull/13512)) that turns a Laravel `Illuminate\Contracts\Support\Renderable` view object, a contract distinct from `Htmlable`, into an `HtmlString`.
The port omits it: `Renderable` has no Twig or Symfony counterpart (only `Htmlable` maps, to `Twig\Markup`), and neither the icon property's union (`string|BackedEnum|Markup|Closure|false|null`) nor any consumer produces such a value.
Re-add it alongside the first component that hands `getIcon()` a renderable.
The rest of the trait matches Filament's line for line, with `Markup` for `Htmlable` ([translations](../translations.md#idiom)).
