---
description: The shared trait that gives a component a configurable icon. (draft)
ports:
  - vendor/filament/support/src/Concerns/HasIcon.php
owns:
  - packages/support/src/Components/Concerns/HasIcon.php
  - packages/support/src/Contracts/HasIcon.php
---
# HasIcon (support)

`Sylament\Support\Components\Concerns\HasIcon` — the shared trait that gives a component a configurable icon.
It ports Filament's `HasIcon` concern: an `icon()` setter that stores a literal, enum, `Markup`, or closure (collapsing a blank value to a `false` sentinel), a `getIcon()` that evaluates the stored value and falls back to a default only when the icon is unset, and a `hasIcon()` predicate.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`icon()` / `getIcon()` / `hasIcon()`](../../../packages/support/src/Components/Concerns/HasIcon.php) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | [diverges](#the-renderable-normalization-branch-is-dropped) |

## Divergences

### The Renderable normalization branch is dropped

Filament's `getIcon()` carries a first branch (`if ($icon instanceof Renderable) { return new HtmlString($icon->render()); }`, from [PR #13512](https://github.com/filamentphp/filament/pull/13512)) that normalizes a `Illuminate\Contracts\Support\Renderable` — a Laravel view-object contract distinct from `Htmlable` — into an `HtmlString`.
The Sylament port omits it: `Renderable` has no ported Twig/Symfony equivalent (only `Htmlable` maps, to `Twig\Markup`), and neither the icon property's union (`string|BackedEnum|Markup|Closure|false|null`) nor any consumer produces such a value.
A closure could still return one at runtime, but with nothing in the port constructing renderable view-objects the branch would be dead code; re-add it alongside the first component that hands `getIcon()` a renderable.
The rest of the trait is byte-faithful modulo the `Htmlable` → `Markup` translation.

_See also: the global [rename table](../renames.md) — `Htmlable` → `Twig\Markup` is a systematic idiom translation recorded there, not a per-component divergence._
