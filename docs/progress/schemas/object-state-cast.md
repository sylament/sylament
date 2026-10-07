# ObjectStateCast (schemas)

`Sylament\Schemas\State\Casts\ObjectStateCast` — the state cast that carries a value-shaped array across the object boundary: `get()` constructs a fresh instance of a declared class from an array's keys, `set()` deconstructs an instance back into that array.
It is the mechanism behind the `Repeater`'s [value-object item mode](../forms/repeater.md#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam), where it runs once per item — but the cast itself knows nothing about items, lists or keys: it is a per-value transform over the existing [`StateCast`](../../../packages/schemas/src/State/Casts/Contracts/StateCast.php) contract, and its consumer owns where it fires.

**Sylament-only.**
Filament ships no counterpart anywhere in `Components/StateCasts/` — its answer to object-shaped repeater state is `relationship()`, which is identity-keyed Eloquent mapping, the exact surface [this cast's scope excludes](#fresh-construction-is-the-contract-because-value-objects-carry-no-identity).
The class name follows the shipped family (`BooleanStateCast`, `OptionStateCast`) and sits among [renames.md's Sylament-only names](../renames.md#names-with-no-filament-source).

**Status:** done — see the [status vocabulary](../README.md#statuses).

## Surface

_Detail_ links a decision section below, or `—`.
No _Ports_ column — there is no Filament source to diff against.

| Feature | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| `get()` — item array → fresh object (constructor params by name, then writable properties) | done | — | [decision](#construction-is-a-hand-mapper-over-the-graphs-existing-pieces) · [decision](#the-name-set-is-the-fidelity-contract) |
| `set()` — object → item array (readable properties over the same name set) | done | — | [decision](#the-name-set-is-the-fidelity-contract) |
| User-supplied conversion pair (`denormalizeUsing` / `normalizeUsing` closures overriding either half) | done | — | [decision](#the-user-can-own-either-half-of-the-conversion) |
| Guards — non-instantiable class at construction; wrong-class or scalar value in `set()`; a non-optional constructor param meeting null in `get()` | done | — | [decision](#every-failure-names-its-fix) |
| Unknown-key tolerance in `get()` (fail-open on extra keys, matching the fail-closed posture of `OptionStateCast` toward tampered payloads) | done | — | [decision](#the-name-set-is-the-fidelity-contract) |

Coverage: the full edge matrix — both directions over promoted-constructor, setter-based and `can`-prefixed DTOs (the accessor honours `get`/`is`/`has`/`can` and so does the name set, so a `canPublish()`/`setPublish()` pair round-trips rather than dropping its value), the null and existing-instance passthroughs, the constructor-default arms (absent key, explicit null against a nullable and a non-nullable default), the unknown-key arms including property-path-shaped keys, both closure overrides, and every guard — by [`ObjectStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/ObjectStateCastTest.php), plus the repeater-side Integration suite recorded in [repeater.md](../forms/repeater.md).

## Design decisions

Recorded before code, per the trail-first convention.
Each says what would reverse it.

### Fresh construction is the contract, because value objects carry no identity

`get()` always builds a **new** instance — there is no populate-existing arm, no identity matching, no diffing against what the record held before.
That is not a simplification but the definition of the supported shape: a value object is schema-complete data with no identity, so rebuilding it from its leaves is lossless *by contract* (see [the name set](#the-name-set-is-the-fidelity-contract) for the exact edge).
The [Serializer rejection](belongs-to-record.md#a-serializer-based-mapper-stays-rejected-on-amended-grounds) measured what fresh construction does to *identity-bearing* items — duplicate INSERTs and orphan deletions on a managed Doctrine collection — and every refusal that guards that boundary ([both mapper triggers](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused), the repeater's [early hydration refusal](../forms/repeater.md#object-collections-stay-refused-until-a-relationship-supplies-the-identity)) stays exactly as shipped: a `Traversable`-typed or adder-resolved property refuses with or without this cast declared.
The cast serves plain `?array` (or untyped) properties whose docblock says `list<SomeDto>` — the shape [the type reader cannot see](record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point) and the declaration now stands in for.
Reverses never, and the prediction held: identity arrived as [`Repeater::relationship()`](../forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)'s own machinery — record-keyed items diffed onto a Doctrine collection through the component-tier hooks — and **not** as an arm of this cast.
The two modes refuse each other loudly: `itemsAs()` builds a fresh identity-free object per item, which is precisely what a relationship must never do.

### Construction is a hand mapper over the graph's existing pieces

The class-string arm's default `get()` is a hand-rolled constructor: reflect the class's constructor once, pass every parameter whose name matches an array key as a named argument, fall back to declared defaults, then write any remaining matched names through `symfony/property-access` (already in schemas' graph — the same package [`PropertyAccessor`](property-accessor.md) wraps) onto writable properties.
A declared default is the fallback for a **missing** key only: a key present with an explicit `null` is passed to a nullable parameter, so blanking a field clears a defaulted-nullable value instead of resurrecting the default — the one case where the default still wins over a present `null` is a **non-nullable** defaulted parameter, where passing it would `TypeError`.
`symfony/serializer` is deliberately **not** adopted, against [principles §2's](../../internals/principles.md#2-prefer-the-ecosystem-over-invention) default, on named checkable grounds: the job here is *flat item array → fresh object by declared names* — the singular-denormalize slice — for which reflection plus the already-present accessor is complete, while Serializer's actual value (nested graph traversal, name converters, docblock-driven type coercion) is exactly what the identity-free flat contract excludes, and adopting it would put a package-wide production dependency into `schemas` for every consumer (the [dependency rules](../../internals/dependencies.md#require-suggest-or-root-require-dev) make that a per-consumer install, not a dev convenience).
The rejection's own closing note — Serializer "remains a candidate for leaf-level casts… where its normalizer half fits the existing StateCast seam" — is untouched: dates, enums and uuids are a separate catalog with different needs.
Reverses if the item contract ever grows nested-object graphs or name mapping, where a hand mapper would start re-implementing Serializer — at that point the dependency is proposed to Martin explicitly, never added in passing.

### The name set is the fidelity contract

Each direction derives its names from the item class — `set()` emits the readable name set (constructor parameters, public properties, and `get`/`is`/`has`/`can` accessor names), while `get()` consumes constructor parameters by name and writes any remaining key the accessor reports writable — so a name round-trips exactly when both sides reach it.
`get()` **ignores every other key silently** — the dehydrated item can carry [unmanaged keys a client kept or invented](../forms/repeater.md#unmanaged-item-keys-round-trip-through-the-client), so an unknown key must never be able to throw (the fail-closed posture `OptionStateCast`'s tamper guards set).
Holding that promise takes an explicit guard, because `symfony/property-access` reads its key as a property **path**, not a name: an empty or unparseable key (`inner[`) throws `InvalidPropertyPathException` before the accessor's own try/catch, and a parseable `inner.city` would *traverse* into an object the constructor built and write there — a client-controlled deep write, since item keys arrive from the browser.
So only a bare property name reaches the accessor; every other key is skipped, which is the same silent-ignore the name-set contract already promised.
`set()` emits exactly the readable half of the same set.
Two consequences, both the value-object contract rather than gaps: undeclared DTO state — a property outside the name set, a value only the old instance held — does **not** survive the round trip, the deliberate opposite of the shipped unmanaged-keys semantics for array items (the two stories sit side by side in [repeater.md](../forms/repeater.md#a-cast-item-is-schema-complete-so-unmanaged-keys-stop-riding)); and a DTO is *schema-complete by definition* — if a field doesn't carry it and a constructor default doesn't supply it, it does not exist after save.
Reverses only with an identity-aware populate arm, and that arm landed elsewhere as predicted — [the relationship repeater](../forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) writes each item onto the record it is keyed to rather than constructing one, so this cast keeps the identity-free contract intact.

### The user can own either half of the conversion

`denormalizeUsing:` / `normalizeUsing:` closures (Symfony's vocabulary) override the default mapper per direction, so Sylament owns the plumbing points while the user owns the conversion — the consumer edge never receives an untyped array whose shape it cannot know, because the item class is declared even when both closures are supplied (it still names the contract for guards and messages).
The closures are **plain callables invoked with the value** (`fn (array $item): Line`, `fn (Line $item): array`), not Invoker-evaluated: the cast stays detached from the component tree, exactly as the shipped casts are, and stays constructible and testable with `new`.
Reverses if a real consumer needs component context inside the conversion — then the repeater-side declaration can evaluate before delegating, without touching this class.

### Every failure names its fix

Three guards, each a `LogicException` that spells out the repair rather than letting a `TypeError` surface from inside reflection:

- **A non-instantiable class** (abstract, interface, private constructor) throws at cast construction, naming the class — before any request runs.
- **`set()` meeting a value that is not the item class** — a foreign object, a scalar where record data should hold items — throws naming the expected class and the actual type.
  A plain **array passes through unchanged**: that arm is what lets defaults authored as arrays, and any state a client bag already holds, ride the same pipeline.
- **`get()` meeting null for a non-optional, non-nullable constructor parameter** throws naming the class, the parameter, and the two fixes — `required()` on the field that feeds it (validation then 422s before construction is ever reached) or a nullable/defaulted parameter.
  This is the one guard a client can reach (by blanking the field), and it reaches a designed message only when the schema author skipped `required()` on a parameter the class demands — a misconfiguration surfaced loudly, not papered over.

### General by construction, repeater-proven by coverage

Nothing in the class references the `Repeater` — `get()`/`set()` transform one value, and any subtree owner (or, untested, a leaf field over a single embedded array) could apply it.
Its test coverage is deliberately repeater-shaped: the cast's Unit matrix plus the repeater Integration suite, and no other consumer is claimed until one exists.
The trail says "general by construction, repeater-only by test coverage" so a later consumer knows the seam is open and the proof is theirs to add.
