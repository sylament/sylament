# IconSize (support)

`Sylament\Support\Enum\IconSize` — the string-backed enum naming the six icon
sizes (`xs`…`2xl`) that icon-rendering components resolve against. It ports
Filament's `Filament\Support\Enums\IconSize`; the `Enums\` → `Enum\` namespace
singularization is a [systematic translation](../renames.md), not a rename. It
carries no methods or interface implementations — a pure-cases value type whose
consumers (e.g. [`HasIconSize`](../../../packages/support/src/Component/Concerns/HasIconSize.php))
`tryFrom` its string against a set value.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php)
  — the six cases and one deprecated const alias

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Six size cases (`xs`…`2xl`) | [`Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php) | done | — | — |
| `ExtraExtraLarge` deprecated alias | [`Enums/IconSize.php`](../../../vendor/filament/support/src/Enums/IconSize.php) | skipped | — | [diverges](#the-deprecated-extraextralarge-const-alias-is-dropped) |

## Details

Both rows are settled. The six cases are ported one-to-one with identical
backing values (`ExtraSmall = 'xs'`, `Small = 'sm'`, `Medium = 'md'`,
`Large = 'lg'`, `ExtraLarge = 'xl'`, `TwoExtraLarge = '2xl'`) and exercised
through the [`HasIconSize` consumer test](../../../packages/support/tests/Unit/Component/Concerns/HasIconSizeTest.php),
which stores and reads back `IconSize::Small` and `IconSize::Large` — a
pure-cases value type has no dedicated test.

## Divergences

### The deprecated `ExtraExtraLarge` const alias is dropped

Filament keeps `public const ExtraExtraLarge = self::TwoExtraLarge`, marked
`@deprecated Use TwoExtraLarge instead.`, purely to spare existing callers a
breaking rename. A fresh port carries no such callers, so the alias is left out
rather than ported-then-deprecated. Re-add it only if a migration path from
older Filament identifiers is ever wanted.
