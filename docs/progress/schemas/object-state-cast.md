---
description: The Sylament-only state cast that builds a fresh value object from an item array and deconstructs one back, behind the Repeater's value-object items.
ports: []
owns:
  - packages/schemas/src/State/Casts/ObjectStateCast.php
---
# ObjectStateCast (schemas)

[`Sylament\Schemas\State\Casts\ObjectStateCast`](../../../packages/schemas/src/State/Casts/ObjectStateCast.php) carries a value-shaped array across the object boundary: `get()` constructs a fresh instance of a declared class from an array's keys, and `set()` deconstructs an instance back into that array.
It is the mechanism behind the `Repeater`'s [value-object item mode](../forms/repeater.md#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam), where it runs once per item, but the cast itself knows nothing about items, lists or keys: it is a per-value transform over the [`StateCast`](../../../packages/schemas/src/State/Casts/Contracts/StateCast.php) contract, and its consumer owns where it fires.

Filament ships no counterpart in `Components/StateCasts/`: its answer to object-shaped repeater state is `relationship()`, identity-keyed Eloquent mapping, the surface [this cast's scope excludes](#fresh-construction-is-the-contract-because-value-objects-carry-no-identity).
The class name follows the shipped family (`BooleanStateCast`, `OptionStateCast`) and sits among the [Sylament-only names](../translations.md#names-with-no-filament-source).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get()`](../../../packages/schemas/src/State/Casts/ObjectStateCast.php): item array → fresh object | — | done | — | [semantics](#construction-is-a-hand-mapper-over-the-graphs-existing-pieces) · [semantics](#the-name-set-is-the-fidelity-contract) |
| [`set()`](../../../packages/schemas/src/State/Casts/ObjectStateCast.php): object → item array | — | done | — | [semantics](#the-name-set-is-the-fidelity-contract) |
| [`denormalizeUsing` / `normalizeUsing`](../../../packages/schemas/src/State/Casts/ObjectStateCast.php) conversion closures | — | done | — | [semantics](#the-user-can-own-either-half-of-the-conversion) |
| [Guards](../../../packages/schemas/src/State/Casts/ObjectStateCast.php) at construction, in `get()` and in `set()` | — | done | — | [semantics](#every-failure-names-its-fix) |
| [Unknown-key tolerance in `get()`](../../../packages/schemas/src/State/Casts/ObjectStateCast.php) | — | done | — | [semantics](#the-name-set-is-the-fidelity-contract) |

## Semantics

### Fresh construction is the contract, because value objects carry no identity

`get()` always builds a **new** instance: there is no populate-existing arm, no identity matching, no diffing against what the record held before.
That is the definition of the supported shape: a value object is schema-complete data with no identity, so rebuilding it from its leaves is lossless *by contract* ([the name set](#the-name-set-is-the-fidelity-contract) has the exact edge).
The [Serializer rejection](belongs-to-record.md#a-serializer-based-mapper-is-rejected-for-collection-population) measured what fresh construction does to *identity-bearing* items, duplicate INSERTs and orphan deletions on a managed Doctrine collection, and every refusal that guards that boundary ([both mapper triggers](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused), the repeater's [early hydration refusal](../forms/repeater.md#object-collections-stay-refused-until-a-relationship-supplies-the-identity)) holds with or without this cast: a `Traversable`-typed or adder-resolved property refuses either way.
The cast serves plain `?array` (or untyped) properties whose docblock says `list<SomeDto>`, the shape [the type reader cannot see](record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point) and the declaration stands in for.
Identity belongs to [`Repeater::relationship()`](../forms/repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection), which diffs record-keyed items onto a Doctrine collection through the component-tier hooks, and not to an arm of this cast.
The two modes refuse each other: `itemsAs()` throws beside `relationship()` and `relationship()` throws beside `itemsAs()`, because a freshly built identity-free object per item is precisely what a relationship must never produce.

### Construction is a hand mapper over the graph's existing pieces

The default `get()` is a hand-rolled constructor: reflect the class's constructor once, pass every parameter whose name matches an array key as a named argument, fall back to declared defaults, then write any remaining matched names through `symfony/property-access` (already in schemas' graph, the same package [`PropertyAccessor`](property-accessor.md) wraps) onto writable properties.
A declared default is the fallback for a **missing** key only: a key present with an explicit `null` is passed to a nullable parameter, so blanking a field clears a defaulted-nullable value instead of resurrecting the default; the default still wins over a present `null` only for a **non-nullable** defaulted parameter, where passing it would `TypeError`.
`symfony/serializer` is deliberately **not** adopted, against [principles §2's](../../internals/principles.md#2-prefer-the-ecosystem-over-invention) default, on named checkable grounds: the job here is *flat item array → fresh object by declared names*, the singular-denormalize slice, for which reflection plus the already-present accessor is complete, while Serializer's actual value (nested graph traversal, name converters, docblock-driven type coercion) is exactly what the identity-free flat contract excludes, and adopting it would put a production dependency into `schemas` for every consumer (the [dependency rules](../../internals/dependencies.md#require-suggest-or-root-require-dev) make that a per-consumer install, not a dev convenience).
The rejection's own closing note, that Serializer "remains a candidate for leaf-level casts… where its normalizer half fits the existing StateCast seam", stands: dates, enums and uuids are a separate catalog with different needs.
It reverses if the item contract grows nested-object graphs or name mapping, where a hand mapper would start re-implementing Serializer; the dependency is then proposed to Martin explicitly, never added in passing.

### The name set is the fidelity contract

Each direction derives its names from the item class: `set()` emits the readable name set (constructor parameters, public non-static properties, and `get`/`is`/`has`/`can` accessor names), while `get()` consumes constructor parameters by name and writes any remaining key the accessor reports writable, so a name round-trips exactly when both sides reach it.
`get()` **ignores every other key silently**, because the dehydrated item can carry [unmanaged keys a client kept or invented](../forms/repeater.md#unmanaged-item-keys-round-trip-through-the-client), so an unknown key must never be able to throw (the fail-closed posture `OptionStateCast`'s tamper guards set).
Holding that promise takes an explicit guard, because `symfony/property-access` reads its key as a property **path**, not a name: an empty or unparseable key (`inner[`) throws `InvalidPropertyPathException` before the accessor's own try/catch, and a parseable `inner.city` would *traverse* into an object the constructor built and write there, a client-controlled deep write, since item keys arrive from the browser.
So only a bare property name reaches the accessor; every other key, a non-string one included, is skipped, which is the same silent ignore the name-set contract promises.
Two consequences follow, both the value-object contract rather than gaps.
Undeclared DTO state, a property outside the name set or a value only the old instance held, does **not** survive the round trip, the deliberate opposite of the unmanaged-keys semantics for array items (the two sit side by side in [repeater.md](../forms/repeater.md#a-cast-item-is-schema-complete-so-unmanaged-keys-stop-riding)).
And a DTO is *schema-complete by definition*: if a field does not carry it and a constructor default does not supply it, it does not exist after save.
Only an identity-aware populate arm would reverse this, and that arm is [the relationship repeater](../forms/repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection), which writes each item onto the record it is keyed to rather than constructing one.

### The user can own either half of the conversion

`denormalizeUsing:` / `normalizeUsing:` closures (Symfony's vocabulary) override the default mapper per direction, so Sylament owns the plumbing points while the user owns the conversion.
The item class is declared even when both closures are supplied, so the consumer edge never receives an untyped array whose shape it cannot know, and the class still names the contract for guards and messages.
The closures are **plain callables invoked with the value** (`fn (array $item): Line`, `fn (Line $item): array`), not Invoker-evaluated: the cast stays detached from the component tree, as the other casts are, and stays constructible and testable with `new`.
It reverses if a real consumer needs component context inside the conversion; the repeater-side declaration can then evaluate before delegating, without touching this class.

### Every failure names its fix

Each guard is a `LogicException` that spells out the repair rather than letting a `TypeError` surface from inside reflection:

- **A non-instantiable class** (abstract, interface, private constructor, or no class at all) throws at cast construction, naming the class, before any request runs.
- **`get()` meeting a value that is neither an array nor the item class** throws naming the class and the actual type.
- **`set()` meeting a value that is not the item class**, such as a foreign object or a scalar where record data should hold items, throws naming the expected class and the actual type.
  A plain **array passes through unchanged**, which lets defaults authored as arrays, and any state a client bag already holds, ride the same pipeline.
- **`get()` meeting null for a non-optional, non-nullable constructor parameter** throws naming the class, the parameter, and the two fixes: `required()` on the field that feeds it (validation then rejects the submission before construction is reached) or a nullable or defaulted parameter.
  A client reaches it by blanking the field, and it surfaces only when the schema author skipped `required()` on a parameter the class demands, a misconfiguration surfaced loudly.
- **A writable property rejecting the dehydrated value** throws naming the class, the property and the value's type, with the same `required()` or nullable fix.

### The cast is general, and the Repeater is its only consumer

Nothing in the class references the `Repeater`: `get()`/`set()` transform one value, and any subtree owner, or a leaf field over a single embedded array, could apply it.
Its tests are the cast's Unit matrix plus the repeater Integration suite, and no other consumer is claimed, so a later consumer knows the seam is open and the proof is its own to add.
