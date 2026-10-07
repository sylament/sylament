---
description: The string-backed enum naming the vertical alignments `start`, `center` and `end` that vertically positioned components resolve against.
ports:
  - vendor/filament/support/src/Enums/VerticalAlignment.php
owns:
  - packages/support/src/Enums/VerticalAlignment.php
---
# VerticalAlignment (support)

`Sylament\Support\Enums\VerticalAlignment` carries the cases and backing values of `Filament\Support\Enums\VerticalAlignment` unchanged, beside the horizontal [`Alignment`](../../../packages/support/src/Enums/Alignment.php).
[`HasVerticalAlignment`](has-vertical-alignment.md) resolves a set string against it with `tryFrom()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`VerticalAlignment`](../../../packages/support/src/Enums/VerticalAlignment.php) string-backed enum | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
| `Start = 'start'` | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
| `Center = 'center'` | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
| `End = 'end'` | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
