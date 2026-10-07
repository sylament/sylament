---
description: The shared concern used by both `Schema` and `Component` to declare whether a schema renders inter-item gap (`hasGap`) and whether it packs densely… (draft)
ports:
  - vendor/filament/schemas/src/Concerns/HasGap.php
owns:
  - packages/schemas/src/Components/Concerns/HasGap.php
---
# HasGap (schemas)

`Sylament\Schemas\Components\Concerns\HasGap` — the shared concern used by both `Schema` and `Component` to declare whether a schema renders inter-item gap (`hasGap`) and whether it packs densely (`isDense`).
Each value is a `bool|Closure|null` property with a fluent setter (`gap()` / `dense()`) and a getter that evaluates the closure and falls back to the container.
It ports Filament's identically-named base trait.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`gap()` setter + `$hasGap`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::gap()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | — |
| [`hasGap()`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::hasGap()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | [diverges](#the-trait-carries-only-the-component-flavor-schema-overrides-the-getters-instead-of-branching-on-instanceof-schema) |
| [`dense()` setter + `$isDense`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::dense()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | — |
| [`isDense()`](../../../packages/schemas/src/Components/Concerns/HasGap.php) | [`HasGap::isDense()`](../../../vendor/filament/schemas/src/Concerns/HasGap.php) | done | — | [diverges](#the-trait-carries-only-the-component-flavor-schema-overrides-the-getters-instead-of-branching-on-instanceof-schema) |

## Details

The setters, and `hasGap()`/`isDense()` in their component (container-delegation) flavor, are ported faithfully.
Direct coverage lives in [`HasGapTest`](../../../packages/schemas/tests/Unit/Concerns/HasGapTest.php) (the Schema-side `isDense` override — default `false`, inline coupling forces `true`, explicit `dense(false)` beats the inline coupling) and in [`SchemaRenderTest`](../../../packages/schemas/tests/Integration/Components/SchemaRenderTest.php) (`gap()`/`dense()` setters, the default gap class dropping when gap is disabled, a gap closure evaluated at render, the dense class).
The trait's own component-flavor getter bodies run indirectly where a child `Schema` delegates up to its parent `Section` (a `Component`) — no direct Component-level gap/dense test exists, but the delegation path is exercised.

## Divergences

### The trait carries only the component flavor; Schema overrides the getters instead of branching on `instanceof Schema`

Filament's `hasGap()`/`isDense()` each contain an `if ($this instanceof Schema)` branch — for gap, the parent-component fallback `getParentComponent()?->hasGap() ?? true`; for dense, `isInline() || getParentComponent()?->isDense()`.
Sylament's trait keeps only the container-delegation branch (`$this->evaluate(...) ?? $this->getContainer()->hasGap()` / `->isDense()`), and [`Schema`](../../../packages/schemas/src/Schema.php) reimplements both getters with the parent-component and inline-coupling fallbacks.
Deliberate and permanent — each context stays statically typed with no runtime `instanceof` test, the same split applied by [`has-columns.md`](has-columns.md).
(Schema's `isDense` override also normalizes Filament's nullable `isInline() || parent?->isDense()` to a strict `bool` — that normalization belongs to the Schema detail file, but it is the behavior `HasGapTest` asserts.)

_See also: the global [rename table](../renames.md) — the `Filament\Schemas\` → `Sylament\Schemas\` namespace swap is a systematic translation recorded there, not a per-component divergence._
