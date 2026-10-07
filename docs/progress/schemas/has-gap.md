---
description: The HasGap concern on Schema and Component, which sets whether a schema renders inter-item gap and whether it packs densely.
ports:
  - vendor/filament/schemas/src/Concerns/HasGap.php
owns:
  - packages/schemas/src/Components/Concerns/HasGap.php
---
# HasGap (schemas)

[`Sylament\Schemas\Components\Concerns\HasGap`](../../../packages/schemas/src/Components/Concerns/HasGap.php) is composed by both [`Schema`](schema.md) and [`Component`](component.md) to declare whether a schema renders inter-item gap (`hasGap`) and whether it packs densely (`isDense`).
Each value is a `bool|Closure|null` property with a fluent setter (`gap()` / `dense()`) and a getter that evaluates the closure and falls back to the container.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`gap()` setter + `$hasGap`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::gap()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | — |
| [`hasGap()`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::hasGap()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | [diverges](#the-trait-carries-only-the-component-flavor-schema-overrides-the-getters-instead-of-branching-on-instanceof-schema) |
| [`dense()` setter + `$isDense`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::dense()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | — |
| [`isDense()`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::isDense()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | [diverges](#the-trait-carries-only-the-component-flavor-schema-overrides-the-getters-instead-of-branching-on-instanceof-schema) |

## Divergences

### The trait carries only the component flavor; Schema overrides the getters instead of branching on `instanceof Schema`

Filament's `hasGap()` and `isDense()` each contain an `if ($this instanceof Schema)` branch: for gap, the parent-component fallback `getParentComponent()?->hasGap() ?? true`; for dense, `isInline() || getParentComponent()?->isDense()`.
Sylament's trait keeps only the container-delegation branch (`$this->evaluate(...) ?? $this->getContainer()->hasGap()` / `->isDense()`), and [`Schema`](../../../packages/schemas/src/Schema.php) reimplements both getters with the parent-component and inline-coupling fallbacks.
Each context stays statically typed with no runtime `instanceof` test, the same split [`has-columns.md`](has-columns.md) applies.
