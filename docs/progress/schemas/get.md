# Get (schemas)

[`Sylament\Schemas\Components\Utilities\Get`](../../../packages/schemas/src/Components/Utilities/Get.php)
— the `$get` callable a component hands its evaluated closures so they can read
sibling and cross-container state by path. `__invoke($path, $isAbsolute)`
resolves the path against the component's own state, a sibling, or another
container, falling back to a raw read off the live component. It ports Filament's
[`Get`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php), whose
`__invoke` is faithfully mirrored while the typed-accessor layer above it is not
yet ported.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Utilities/Get.php`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php)
  — `__invoke`, the nine typed accessors, and the skip-child-containers toggle

## Surface

Every point of this utility. _Blocked by_ names what has to land first. _Detail_
carries a **detail** link to the write-up in [Details](#details) (deleted once
the row is **done**), a **diverges** link to the permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__invoke()` — own-state / sibling / cross-container resolution + raw-bag fallback](../../../packages/schemas/src/Components/Utilities/Get.php) | [`__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | done | — | — |
| Typed accessors: [`string()` / `integer()` / `float()` / `boolean()` / `array()` / `date()` / `enum()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | [`string()` …](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | todo | — | [detail](#typed-accessors-are-not-ported) |
| [`filled()` / `blank()` convenience predicates](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | [`filled()` / `blank()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | todo | — | [detail](#typed-accessors-are-not-ported) |
| [`skipComponentsChildContainersWhileSearching(bool)` toggle + search stack](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | [`skipComponentsChildContainersWhileSearching()`](../../../vendor/filament/schemas/src/Components/Utilities/Get.php) | todo | — | [diverges](#the-skip-child-containers-search-state-collapses-from-a-static-stack-to-a-per-call-array) |

`__invoke`'s raw fallback reads through
[`PropertyAccessor`](property-accessor.md) rather than Laravel's `data_get` —
the Symfony PropertyAccess state backend that owns that translation. The path
resolution itself (`resolveRelativeStatePath`, `getComponentByStatePath`) lives
on the [`Component`](component.md) it wraps.

## Details

### Typed accessors are not ported

Filament layers eleven thin readers over `__invoke` — seven casting accessors
(`string`, `integer`, `float`, `boolean`, `array`, `date`, `enum`) and two
predicates (`filled`, `blank`) — each taking the same `$key` / `$isAbsolute`
pair, most also an `$isNullable` flag that short-circuits to `null` on a blank
read before casting. Sylament ships only `__invoke`; none of these exist yet.

What is in: the resolution core they all call. What is left: the readers
themselves. Next step: port each as a wrapper over `__invoke`, mirroring
Filament's `blank()`-guard-then-cast shape; translate `date()`'s
`Carbon::parse` to a plain PHP/Symfony `DateTimeImmutable` and carry the
`(TNullable is true ? ?T : T)` conditional return in prose, since PHPStan cannot
express it on a native signature. Add a `GetTest` case per accessor.

## Divergences

### The skip-child-containers search state collapses from a static stack to a per-call array

Filament keeps a static `array<Component> $skipComponentsChildContainersWhileSearching`,
pushing the current component onto it and popping in a `finally` around the
`getRootContainer()->getComponentByStatePath()` call, and threads
`withHidden: true`, `withAbsoluteStatePath: true`, plus that stack (or `[]` when
the `shouldSkipComponentsChildContainersWhileSearching` flag is off) as named
arguments. Sylament passes a flat `[$this->component]` to a two-argument
`getComponentByStatePath(string, array)` — no static stack, no `withHidden` /
`withAbsoluteStatePath`, no mutable flag. This tracks Sylament's simpler
[`HasComponents::getComponentByStatePath(string, array)`](../../../packages/schemas/src/Concerns/HasComponents.php)
signature, and is why
the class is `final readonly` here while Filament's is mutable (the dropped flag
is the only mutable state). It reverses if nested-repeater search — where
re-entrancy makes the stack matter — is ported, at which point the toggle and
its state return together.
