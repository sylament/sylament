---
description: 'The trait that names *what the host is doing* with a schema: `operation()` stores a free-form string (`''create''`, `''edit''`, `''view''`, or anything a… (draft)'
ports:
  - vendor/filament/schemas/src/Concerns/HasOperation.php
owns:
  - packages/schemas/src/Concerns/HasOperation.php
---
# HasOperation (schemas)

[`Sylament\Schemas\Concerns\HasOperation`](../../../packages/schemas/src/Concerns/HasOperation.php) — the trait that names *what the host is doing* with a schema: `operation()` stores a free-form string (`'create'`, `'edit'`, `'view'`, or anything a host invents) and `getOperation()` resolves it, falling back up the parent-component chain and finally to the host live component's class name.
It is composed by [`Schema`](schema.md) and consumed at the component tier by the `$operation` / `$context` closure parameters and by [`hiddenOn()`/`visibleOn()`](can-be-hidden.md) and [`disabledOn()`](can-be-disabled.md).
The typed vocabulary for the three standard values is support's [`Operation`](../support/README.md) enum; `operation()` itself takes `?string`, as Filament's does.
The record half of the host context lives in [`belongs-to-record.md`](belongs-to-record.md).

## Surface

Every point of this trait, plus the component-tier injection it feeds.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`operation()`](../../../packages/schemas/src/Concerns/HasOperation.php) | [`operation()`](../../../vendor/filament/schemas/src/Concerns/HasOperation.php) | done | — | — |
| [`getOperation()` — own value, parent-component chain, host class](../../../packages/schemas/src/Concerns/HasOperation.php) | [`getOperation()`](../../../vendor/filament/schemas/src/Concerns/HasOperation.php) | done | — | [diverges](#an-unhosted-operation-less-schema-throws-a-runtimeexception-where-filament-raises-a-typeerror) |
| [`$operation` / `$context` closure injection (component tier)](../../../packages/schemas/src/Components/Component.php) | [`Components\Component`](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | [diverges](#the-injection-arm-is-guarded-so-a-detached-component-still-evaluates) |
| `context()` setter | [`context()`](../../../vendor/filament/schemas/src/Concerns/HasOperation.php) | skipped | — | [diverges](#the-deprecated-context-setter-is-not-ported) |

Coverage: [`HasOperationTest`](../../../packages/schemas/tests/Unit/Concerns/HasOperationTest.php) — the explicit value, the host-class fallback, the unhosted throw, inheritance through a child-schema chain, and both injection names on an attached component.
The `*On()` consumers are covered from their own traits' [hidden](../../../packages/schemas/tests/Unit/Components/Concerns/CanBeHiddenTest.php) and [disabled](../../../packages/schemas/tests/Unit/Components/Concerns/CanBeDisabledTest.php) suites.

## Divergences

### An unhosted, operation-less schema throws a `RuntimeException` where Filament raises a `TypeError`

The fallback chain ends at the host live component's class name, which means a standalone schema (`Schema::make()` with no host) that was never given an operation has nothing to return.
Sylament's [`getLiveComponent()`](belongs-to-live-component.md#getlivecomponent-throws-instead-of-returning-a-possibly-null-component) throws `RuntimeException('Schema is not part of LiveComponent')` at that point.
Filament reaches the same dead end and fails there too, just less legibly: its `getLivewire()` declares a non-nullable return type over a nullable property, so the failure surfaces as a `TypeError` about the return type of an unrelated method.
Same failure class, clearer message — Filament does **not** return an FQCN for an unhosted schema, and any note claiming it does is wrong.

### The injection arm is guarded so a detached component still evaluates

`Components\Component` resolves `$operation`/`$context` through `getContainer()->getOperation()`, and `$container` is an uninitialized typed property until a schema stamps the component.
The arm is therefore `isset($this->container)`-guarded and falls through to the parent resolver when it is not — a `new Component()` keeps evaluating closures (pinned by the existing base-component tests) instead of fataling on the back-reference.
Same guard, same reason, as the record fallbacks in [belongs-to-record.md](belongs-to-record.md#the-record-type-probe-is-instanceof-not-a-model-gate).

### The deprecated `context()` setter is not ported

Filament keeps `context(?string $context)` as an `@deprecated` forwarder to `operation()`, and injects both `$context` and `$operation` as closure parameter names.
The *injection* names are both ported — a migrating user's closures keep working — but the setter is not: it was already deprecated in the source we port from, so shipping it would only reintroduce the ambiguity the deprecation is retiring.
Same call as the deprecated [`name()`/`getName()` aliases](schema.md#the-deprecated-name-and-getname-aliases-are-not-ported).
