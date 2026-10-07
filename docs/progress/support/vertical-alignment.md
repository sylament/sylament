---
description: The string-backed enum naming the three vertical alignments (`start`, `center`, `end`) that stacked or vertically-positioned components resolve against. (draft)
ports:
  - vendor/filament/support/src/Enums/VerticalAlignment.php
owns:
  - packages/support/src/Enums/VerticalAlignment.php
---
# VerticalAlignment (support)

`Sylament\Support\Enums\VerticalAlignment` — the string-backed enum naming the three vertical alignments (`start`, `center`, `end`) that stacked or vertically-positioned components resolve against.
It ports Filament's `Filament\Support\Enums\VerticalAlignment` name for name and value for value.
It carries no methods or interface implementations — a pure-cases value type whose consumers `tryFrom` its string against a set value, mirroring the horizontal [`Alignment`](../../../packages/support/src/Enums/Alignment.php) enum beside it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| The enum itself (string-backed, no methods) | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
| `Start = 'start'` | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
| `Center = 'center'` | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |
| `End = 'end'` | [`Enums/VerticalAlignment.php`](../../../vendor/filament/support/src/Enums/VerticalAlignment.php) | done | — | — |

A pure-cases value type carries no test of its own — like [`IconSize`](icon-size.md), it is exercised through whichever consumer stores and reads a case back.
There is nothing to diverge over: the port is the vendor file with `declare(strict_types=1)` and the `Sylament` namespace.
