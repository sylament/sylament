---
description: The boolean fields' layout concern, whose `inline()` / `isInline()` pair decides whether the control sits beside its label text or above it.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeInline.php
owns:
  - packages/forms/src/Components/Concerns/CanBeInline.php
---
# CanBeInline (forms)

`Sylament\Forms\Components\Concerns\CanBeInline` is composed by [`Checkbox`](checkbox.md) and [`Toggle`](toggle.md).
Schemas' root-tier `Sylament\Schemas\Concerns\CanBeInline` and tables' `Sylament\Tables\Columns\Concerns\CanBeInline` share its name and its `inline()` / `isInline()` pair but default to `false`, as Filament's twins do, so read a `use CanBeInline;` by its import ([the by-import trap](../../internals/php.md#the-by-import-trap)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`inline()` / `isInline()`](../../../packages/forms/src/Components/Concerns/CanBeInline.php) | [`Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | — | [diverges](#isinline-drops-the-hasinlinelabel-guard) |

## Divergences

### isInline() drops the hasInlineLabel() guard

Filament's `isInline()` opens with `if ($this->hasInlineLabel()) { return false; }`, deferring to schemas' `HasInlineLabel`, the left-column label layout, where a label sits in its own column beside the control and an inline boolean control would fight it.
The port has no `HasInlineLabel`, so the guard has nothing to ask and is omitted; `isInline()` evaluates `$this->isInline` directly.

This is a deferral, not a design disagreement: the guard is right and the port wants it.
It reverses when `HasInlineLabel` is ported, and restoring the guard is the whole change to this trait.
