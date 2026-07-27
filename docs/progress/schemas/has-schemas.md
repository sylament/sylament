# HasSchemas (schemas)

[`Sylament\Schemas\Concerns\HasSchemas`](../../../packages/schemas/src/Concerns/HasSchemas.php)
— the host-side trait a component (a panel page, a UX Live Component) mixes in to
own one or more [`Schema`](schema.md) instances: it enumerates their names,
builds-and-caches each from a same-named method, fills their state, and fires
their `afterStateUpdated` hooks across a request round-trip. It ports Filament's
[`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php).

The label is swapped as part of the documented interface⇄trait switch: Filament's
*trait* `InteractsWithSchemas` becomes Sylament's *trait* `HasSchemas`, while
Filament's *interface* `HasSchemas` becomes Sylament's *interface*
[`InteractsWithSchemas`](interacts-with-schemas.md) — recorded in the
[rename table](../renames.md). No production schemas class uses this trait yet; it
is ported ahead of its real consumers (panel pages / UX live hosts), and today
only test fixtures mix it in.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/InteractsWithSchemas.php`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php)
  — the large host trait this reduces to its schema-hosting and state-hook core

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries either **detail** → the write-up in [Details](#details), deleted once the
row is **done**, or **diverges** → the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schemas()` default `['schema']`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`getSchema()` — build from matching method, cache, throw, key by name](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| [`getCachedSchemas()` — build every declared name, reuse cached](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getCachedSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`makeSchema()` — `Schema::make($this)`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::makeSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`fill()` default schema](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`oldState` `#[LiveProp]` + `persistOldState()` `#[PreDehydrate]`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::updatingInteractsWithSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#livewires-updatingupdated-state-hooks-become-a-predehydrateposthydrate-snapshot-and-diff) |
| [`callAfterSchemaStateUpdated()` `#[PostHydrate]` — diff snapshot, fire hooks](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::updatedInteractsWithSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-level-afterstateupdated-hook-is-renamed-to-callafterschemastateupdated) |
| [`getOldSchemaState(?string)` — path-keyed pre-change lookup](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getOldSchemaState()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`<name>Schema` method-name fallback — bare name first, suffix second](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| Reflection schema auto-discovery (`default<Name>` hooks, `discoveredSchemaNames`, return-type detection) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| `getSchemaComponent()` absolute-key addressing | [`InteractsWithSchemas::getSchemaComponent()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#component-addressing-and-the-exposedlivewiremethod-dispatch-are-not-ported) |
| `callSchemaComponentMethod()` / `partiallyRenderSchemaComponent()` | [`InteractsWithSchemas::callSchemaComponentMethod()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#component-addressing-and-the-exposedlivewiremethod-dispatch-are-not-ported) |
| Validation integration (`validate`/`validateOnly`/`getRules`/`currentlyValidatingSchema`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `CanBeValidated` | [detail](#the-validation-file-upload-dynamic-property-and-testing-integrations-arrive-with-their-own-concerns) |
| File-upload integration (`componentFileAttachments`, `isFileUploadForSchemaComponent`, …) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `RestrictsFileUploadsToSchemaComponents` + forms `BaseFileUpload` | [detail](#the-validation-file-upload-dynamic-property-and-testing-integrations-arrive-with-their-own-concerns) |
| `ResolvesDynamicLivewireProperties` (used trait) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `ResolvesDynamicLivewireProperties` port | [detail](#the-validation-file-upload-dynamic-property-and-testing-integrations-arrive-with-their-own-concerns) |
| Translatable content driver (`getFilamentTranslatableContentDriver`, `getActiveSchemaLocale`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#the-translatable-content-driver-is-not-ported) |
| Testing helpers (`fillFormDataForTesting`, `disable/enableSchemaStateUpdateHooksForTesting`, `getDefaultTestingSchemaName`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `TestsSchemas` | [detail](#the-validation-file-upload-dynamic-property-and-testing-integrations-arrive-with-their-own-concerns) |

The nine **done** rows are the schema-hosting core (`schemas()`, `getSchema()`
with its `<name>Schema` method-name fallback, `getCachedSchemas()`,
`makeSchema()`, `fill()`) and the full PreDehydrate/PostHydrate state-hook loop
(`oldState`, `persistOldState`, `callAfterSchemaStateUpdated`,
`getOldSchemaState`). The core is covered by
[`HasSchemasTest`](../../../packages/schemas/tests/Unit/Concerns/HasSchemasTest.php)
(build-from-method, default name, caching, throw-on-missing/non-`Schema`,
key-by-name, `getCachedSchemas`/`makeSchema` fresh-vs-bound, and — via the
[`SuffixedSchemaHostFixture`](../../../packages/schemas/tests/Fixtures/Concerns/SuffixedSchemaHostFixture.php)
— suffix resolution, bare-wins precedence, and keying by the declared name);
`fill()` by
[`HasStateTest`](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php)
(single + multi-schema); and the snapshot-and-diff loop end-to-end by
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)
via the `LiveStateHost` fixture — it asserts `afterStateUpdated` fires with the
old value on a changed field and does not fire when unchanged.

## Details

Specifics for the rows that are not finished — what is left and what the next
step is. Delete a subsection when its row flips to **done**; the row and any
divergence stay behind.

### Component addressing and the ExposedLivewireMethod dispatch are not ported

`getSchemaComponent()` (resolve a component by its absolute state-path key),
`callSchemaComponentMethod()` (dispatch an `ExposedLivewireMethod` at a keyed
component), and `partiallyRenderSchemaComponent()` (re-render a subtree) are all
absent — the trait today can build, fill, and diff schemas but cannot reach an
individual component by key. This is the foundational next step: port
`getSchemaComponent()` first, since both the method dispatch and the partial
render build on component addressing.

### The validation, file-upload, dynamic-property, and testing integrations arrive with their own concerns

Filament folds these into `InteractsWithSchemas` via `use`d traits and inline
methods; here each is **blocked** on the concern that owns it — validation on
`CanBeValidated`, file uploads on `RestrictsFileUploadsToSchemaComponents` (plus
forms' `BaseFileUpload`), dynamic properties on a `ResolvesDynamicLivewireProperties`
port, and the testing helpers on `TestsSchemas`. The port wires them in through
those concerns rather than re-inlining them here, so the trait stays a thin
schema host. Nothing consumes them until a panel/live host lands.

### The translatable content driver is not ported

`getFilamentTranslatableContentDriver()` /
`makeFilamentTranslatableContentDriver()` / `getActiveSchemaLocale()` — the
per-locale content driver Filament threads through schema state — has no
consumer and no prerequisite blocking it, so it is plain **todo**.

## Divergences

### Livewire's updating/updated state hooks become a PreDehydrate/PostHydrate snapshot-and-diff

Filament's trait relies on Livewire's path-keyed `updating*`/`updated*`
lifecycle: `updatingInteractsWithSchemas` snapshots into a protected
`$oldSchemaState` via `data_get`, and `updatedInteractsWithSchemas` fires
`callAfterStateUpdated` for each changed path. UX Live Components have no
per-property update hook, so the port snapshots every bound component's raw state
keyed by absolute state path at `#[PreDehydrate]` (`persistOldState`) and, after
re-hydration, diffs each component's raw state against that snapshot at
`#[PostHydrate]` (`callAfterSchemaStateUpdated`) to fire the `afterStateUpdated`
hooks. The raw-state reads route through Symfony PropertyAccess, the state
backend owned by [`property-accessor.md`](property-accessor.md). Because UX must
reconstruct the object across the wire, the snapshot lives in a public
`#[LiveProp]` array `$oldState` rather than Filament's protected property —
roughly doubling the wire state (accepted, design §2). Reverses only if UX gains
a native per-property `updated` hook.

### Schema discovery is explicit via `schemas()`, not reflection-based

Filament's `cacheSchema()` auto-discovers schema-defining methods by reflection:
it accepts a bare name or an `<name>Schema` suffix, inspects the method's
first-parameter type or return type to confirm it is a `Schema`, records the name
in `discoveredSchemaNames`, and runs a `default<Name>()` hook if present.
Sylament's `getSchema()` ports the **method-name half** — it tries the bare name,
then `"{$name}Schema"`, bare winning when both exist, and keys the schema by the
declared name either way — but requires `schemas()` to enumerate the names, and
has no reflection guard, no return-type detection, no `default<Name>` hook, and
no `discoveredSchemaNames`. A method that resolves but does not return a `Schema`
still fails on the post-call `instanceof` check rather than being skipped by
reflection.

`discoveredSchemaNames` in particular is not merely deferred. It is a `#[Locked]`
Livewire property memoising names the *view* resolved, and it works only because
Livewire dehydrates **after** render. UX LiveComponent dehydrates in
[`AddLiveAttributesSubscriber::onPreRender()`](../../../vendor/symfony/ux-live-component/src/EventListener/AddLiveAttributesSubscriber.php),
**before** the template body runs, so a name first resolved by
`{{ renderSchema('article') }}` would be appended after the props are already
frozen and would never reach the next request. That is a correctness hole, not an
inefficiency: `callAfterSchemaStateUpdated()` is `#[PostHydrate]` and iterates
`getCachedSchemas()`, so a template-only schema would silently miss its
`afterStateUpdated` hooks. Names resolved before dehydration — in `mount()`, in
`#[PostHydrate]`, or in a `#[LiveAction]` — would persist, but the partial
guarantee is worse than none. `schemas()` therefore stays the declaration that
works in every case. Reversing the rest means porting the reflection loop when a
consumer needs the `default<Name>` seeding hook or the no-parameter
`articleSchema(): Schema` shape.

### The host-level afterStateUpdated hook is renamed to `callAfterSchemaStateUpdated`

Filament names the host method `updatedInteractsWithSchemas` (mirroring the
Livewire `updated<Trait>` convention). Sylament names it
`callAfterSchemaStateUpdated` to keep it distinct from the component-level
`afterStateUpdated()` setter that lives on the state concern (see
[`component.md`](component.md)) — the state-layer prototype collided the two
names. The rename is deliberate and permanent (docblock-noted in the trait).

_See also: the global [rename table](../renames.md) — the interface⇄trait label
swap (`InteractsWithSchemas` trait → `HasSchemas` trait) and the Livewire → UX
LiveComponent hook translation are recorded there._
