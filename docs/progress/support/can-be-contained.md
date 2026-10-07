---
description: The shared concern that lets a layout component drop its container chrome with `contained(false)`.
ports:
  - vendor/filament/support/src/Concerns/CanBeContained.php
owns:
  - packages/support/src/Components/Concerns/CanBeContained.php
---
# CanBeContained (support)

`Sylament\Support\Components\Concerns\CanBeContained` ports Filament's trait of the same name.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`contained()`](../../../packages/support/src/Components/Concerns/CanBeContained.php) | [`contained()`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | done | — | — |
| [`isContained()`, memoized after its first evaluation](../../../packages/support/src/Components/Concerns/CanBeContained.php) | [`isContained()`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | done | — | [diverges](component.md#evaluated-flag-getters-are-not-re-cast-to-bool) |
