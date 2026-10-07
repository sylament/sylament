---
description: The shared layout concern the boolean fields compose, carrying the `inline()` setter and the `isInline()` getter that decide whether the control sits… (draft)
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeInline.php
  - vendor/filament/schemas/src/Components/Concerns/HasInlineLabel.php
owns: []
---
# CanBeInline (forms)

`Sylament\Forms\Components\Concerns\CanBeInline` — the shared layout concern the boolean fields compose, carrying the `inline()` setter and the `isInline()` getter that decide whether the control sits beside its label text or above it.
The name collides with `Sylament\Schemas\Concerns\CanBeInline`, the root-object trait a child `Schema` uses; that is the inherited two-tier cost [`docs/internals/php.md`](../../internals/php.md#the-by-import-trap) records — read the trait by its **import**, never by the bare `use CanBeInline;` in a class body.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `inline()` / `isInline()` | [`Concerns/CanBeInline.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeInline.php) | done | — | [diverges](#isinline-drops-the-hasinlinelabel-guard) |

Coverage: the `true` default, the literal-`false` setter and closure evaluation by [`CanBeInlineTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeInlineTest.php) against the [`FixtureBooleanField`](../../../packages/forms/tests/Fixtures/Components/FixtureBooleanField.php) fixture.

## Divergences

### isInline() drops the hasInlineLabel() guard

Filament's `isInline()` opens with `if ($this->hasInlineLabel()) { return false; }`, deferring to schemas' `HasInlineLabel` — the left-column label layout, where a label sits in its own column beside the control and an inline boolean control would fight it.
That concern is unported and stays **todo**, so the guard has nothing to ask and is omitted; the port evaluates `$this->isInline` directly.

This is a deferral, not a design disagreement: the guard is right and we want it.
It reverses the moment `HasInlineLabel` lands — restoring the two lines is the whole change.
