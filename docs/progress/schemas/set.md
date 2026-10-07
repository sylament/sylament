# Set (schemas)

[`Sylament\Schemas\State\Set`](../../../packages/schemas/src/State/Set.php) is the `$set` writer handed to closures during evaluation — the write-side twin of [`Get`](get.md).
An invokable bound to one [`Component`](component.md) (only that binding is `readonly`), its `__invoke($path, $state, $isAbsolute, $shouldCallUpdatedHooks)` resolves the (relative-or-absolute) state path to a component, closure-evaluates `$state`, then either writes through the owning component's `state()` (optionally firing its after-update hooks) or, when no component owns the path, sets it on the live component's raw data bag.
It ports Filament's identically-named [`Set`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Utilities/Set.php`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) — the invokable, its skip toggle, and both write branches

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__invoke` component-branch write: resolve path → `state()` with target cast](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | — |
| [Closure-valued state evaluated before writing; evaluated value returned](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | — |
| [`shouldCallUpdatedHooks` gating of after-update hooks](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | in progress | — | [diverges](#shouldcallupdatedhooks-fires-the-non-bubbling-hook-set-dropping-filaments-parent-bubbling) |
| [Raw-bag else-branch: `PropertyAccessor::set` + `clearCachedChildSchemas`](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | in progress | — | [detail](#raw-bag-else-branch) |
| [`skipComponentsChildContainersWhileSearching(bool)` toggle](../../../packages/schemas/src/State/Set.php) | [`Set::skipComponentsChildContainersWhileSearching()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | — |

## Details

### Raw-bag else-branch

The component branch (path resolves to a real component → `state()` with the target component's cast, optional after-update hooks) is ported and covered by [`SetTest`](../../../packages/schemas/tests/Unit/State/SetTest.php) across relative, cast-on-write, and parent-sibling writes.
The else-branch — `PropertyAccessor::getInstance()->set($liveComponent, $path, $state)` for a path no component owns — is written but has **two** gaps: it is untested (every asserted `SetTest` write, and `LiveStateTest`'s `Set('slug', …)`, resolves to a component, so the raw path never runs), and it omits Filament's `getRootContainer()->clearCachedChildSchemas()` call that follows the bag write.
Next: add a `SetTest` case writing an undeclared path and asserting the bag receives it via `PropertyAccessor`; restore the cache-invalidation call when the child-schema cache lands (see the [divergence](#the-raw-write-branch-skips-clearcachedchildschemas-because-no-child-schema-cache-exists-yet)).

## Divergences

### `shouldCallUpdatedHooks` fires the non-bubbling hook set, dropping Filament's parent bubbling

Filament's `Set` invokes `$component->callAfterStateUpdated()`, whose default bubbles the after-update notification to parent components; Sylament invokes [`callAfterStateUpdatedHooks()`](../../../packages/schemas/src/State/Set.php), which runs only this component's own hooks.
`SetTest` asserts the hook fires once when `shouldCallUpdatedHooks: true` and stays silent by default, but never asserts bubbling, so the delta is currently untested.
Reverses if/when parent bubbling is ported into `HasState` and `Set` is repointed at the bubbling entry point.

_See also: the [rename table](../renames.md) — `callAfterStateUpdated()` → `callAfterStateUpdatedHooks()`._

### The raw-write branch skips `clearCachedChildSchemas` because no child-schema cache exists yet

After writing an unowned path to the bag, Filament calls `getRootContainer()->clearCachedChildSchemas()` so freshly-revealed schemas rebuild; Sylament omits it.
Not a bug today — Sylament has no cached-child-schema layer (no such method exists in `packages/schemas/src`) — but the call must be restored when that caching is ported, or newly-conditional child schemas will read stale.
