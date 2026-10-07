---
description: The string-backed enum naming the six icon sizes, `xs` to `2xl`, that icon-rendering components resolve a size against.
ports:
  - vendor/filament/support/src/Enums/IconSize.php
owns:
  - packages/support/src/Enums/IconSize.php
---
# IconSize (support)

`Sylament\Support\Enums\IconSize` carries the cases and backing values of `Filament\Support\Enums\IconSize` unchanged.
[`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) returns a set size raw, and its consumers, such as [`IconExtension`](../../../packages/support/src/Twig/Extension/IconExtension.php), run `IconSize::tryFrom()` on a string so that a size outside the enum passes through.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`IconSize`](../../../packages/support/src/Enums/IconSize.php) cases `ExtraSmall` to `TwoExtraLarge` | [`Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php) | done | — | — |
| `ExtraExtraLarge` deprecated alias | [`Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php) | skipped | — | [diverges](#the-deprecated-extraextralarge-const-alias-is-dropped) |

## Divergences

### The deprecated `ExtraExtraLarge` const alias is dropped

Filament keeps `public const ExtraExtraLarge = self::TwoExtraLarge`, marked ``@deprecated Use `TwoExtraLarge` instead.``, to spare its existing callers a breaking rename.
The port leaves the alias out rather than ship a member that is deprecated from the start, so a caller writes `TwoExtraLarge`, the case Filament points to.
Re-add it if a migration path from Filament's deprecated identifiers is ever wanted.
