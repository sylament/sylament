---
description: 'The host-side trait a panel page or UX Live Component mixes in to own, build, cache and fill its named `Schema` instances across a request round-trip'
ports:
  - vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php
owns:
  - packages/schemas/src/Concerns/HasSchemas.php
---
# HasSchemas (schemas)

[`Sylament\Schemas\Concerns\HasSchemas`](../../../packages/schemas/src/Concerns/HasSchemas.php) is the host-side trait a component mixes in to own one or more [`Schema`](schema.md) instances: it enumerates their names, builds and caches each from a same-named method, fills their state, and fires their `afterStateUpdated` hooks across a request round-trip.
Panels' [`Page`](../../../packages/panels/src/Pages/Page.php) and [`RelationManager`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) compose it.

The host owns the state shape, not the trait.
It declares its own state as `#[LiveProp(writable: true)]` properties — a `data` array bag, individual typed props, or one bag per form — and each schema-defining method binds to them with `->statePath(...)`, mirroring Filament's `->statePath($this->getFormStatePath())` (which likewise defaults to no path, i.e. top-level binding).
Because the trait presumes no state shape, one host can carry several independently-bound schemas without them colliding.

The label is swapped as part of the interface⇄trait switch: Filament's *trait* `InteractsWithSchemas` becomes Sylament's *trait* `HasSchemas`, while Filament's *interface* `HasSchemas` becomes Sylament's *interface* [`InteractsWithSchemas`](interacts-with-schemas.md) — recorded in the [rename table](../renames.md).
The trait's validation members (`validateSchema()`, the error bag, `clearSchemaValidation()`, the `$validatedSchemas` sticky loop, `validateSchemaRecord()` and the `form-validation-error` event) are tracked in [`can-be-validated.md`](can-be-validated.md) with the rest of the validation subsystem.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schemas()` default `['schema']`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`getSchema()` — build from matching method, cache, throw, key by name](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| [`getCachedSchemas()` — build every declared name, reuse cached](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getCachedSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`schemasHostId` `#[LiveProp]` + `getSchemasHostId()`](../../../packages/schemas/src/Concerns/HasSchemas.php) — the key prefix | [`Components/Concerns/HasKey::getLivewireKey()`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) | done | — | [diverges](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) |
| [`makeSchema()` — `Schema::make($this)`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::makeSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`fill(object\|array\|null $state = null)` default schema](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares) |
| [`oldState` `#[LiveProp]` + `persistOldState()` `#[PreDehydrate]`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::updatingInteractsWithSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#livewires-updatingupdated-state-hooks-become-a-predehydrateposthydrate-snapshot-and-diff) |
| [`callAfterSchemaStateUpdated()` `#[PostHydrate]` — diff snapshot, fire hooks](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::updatedInteractsWithSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-level-afterstateupdated-hook-is-renamed-to-callafterschemastateupdated) |
| [`getOldSchemaState(?string)` — path-keyed pre-change lookup](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getOldSchemaState()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`<name>Schema` method-name fallback — bare name first, suffix second](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`cacheSchema()` — register/unset a schema instance by name](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#cacheschema-has-no-discovery-arm-and-requires-its-second-argument) |
| [`hasCachedSchema()` — raw-cache membership test](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::hasCachedSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#hascachedschema-checks-the-raw-cache-not-getcachedschemas) |
| Reflection schema discovery (`default<Name>` hooks, return-type detection, the `isCachingSchemas()` guard) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| `discoveredSchemaNames` `#[Locked]` name memo | [`InteractsWithSchemas::getCachedSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| [`resolveSchemaByName()` — the shared reflection gate](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source) |
| [`getSchemaComponent()` absolute-key addressing](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getSchemaComponent()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#getschemacomponent-drops-the-child-container-skip-list) |
| [`callSchemaComponentMethod()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::callSchemaComponentMethod()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#callschemacomponentmethod-answers-through-a-browser-event-with-no-partial-render) |
| [`uploadSchemaComponentFile()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
| `partiallyRenderSchemaComponent()` | [`InteractsWithSchemas::partiallyRenderSchemaComponent()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#partiallyrenderschemacomponent-has-no-livecomponent-twin) |
| Deferred schema loading (`loadDeferredSchema()`, `isDeferredSchemaLoaded()`, `renderingInteractsWithSchemas()`) | [`InteractsWithSchemas::loadDeferredSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | — |
| [`ComponentToolsTrait` composition (`dispatchBrowserEvent()` and siblings)](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](#hasschemas-composes-componenttoolstrait-alongside-hasactions) |
| Livewire file-upload integration (`use WithFileUploads`, `componentFileAttachments`, `isFileUploadForSchemaComponent()`, `getSchemaComponentForFileUpload()`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [interacts-with-schemas.md](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
| `ResolvesDynamicLivewireProperties` (used trait) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#dynamic-livewire-properties-are-not-ported-because-they-hide-from-phpstan) |
| Translatable content driver (`getFilamentTranslatableContentDriver()`, `makeFilamentTranslatableContentDriver()`, `getActiveSchemaLocale()`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#the-translatable-content-driver-is-not-ported) |
| Testing helpers (`fillFormDataForTesting()`, `disableSchemaStateUpdateHooksForTesting()`, `enableSchemaStateUpdateHooksForTesting()`, `unsetMissingNumericArrayKeys()`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `TestsSchemas` | [detail](#the-testing-helpers-arrive-with-testsschemas) |

## Details

### The testing helpers arrive with `TestsSchemas`

Filament folds the testing helpers into `InteractsWithSchemas` as inline methods beside the `#[Locked]` `$areSchemaStateUpdateHooksDisabledForTesting` flag; here they are **blocked** on the `TestsSchemas` testing helper that owns them, so the trait stays a thin schema host.
[`getDefaultTestingSchemaName()`](interacts-with-schemas.md#the-testing-contract-method-is-not-declared) waits on the same helper.

### The translatable content driver is not ported

`getFilamentTranslatableContentDriver()` / `makeFilamentTranslatableContentDriver()` / `getActiveSchemaLocale()` — the per-locale content driver Filament threads through schema state — has no consumer and nothing blocking it, so it is plain **todo**.

## Divergences

### Livewire's updating/updated state hooks become a PreDehydrate/PostHydrate snapshot-and-diff

Filament's trait relies on Livewire's path-keyed `updating*`/`updated*` lifecycle: `updatingInteractsWithSchemas` snapshots into a protected `$oldSchemaState` via `data_get`, and `updatedInteractsWithSchemas` fires `callAfterStateUpdated` on every cached schema for each changed path.
UX Live Components have no per-property update hook, so the port snapshots every bound component's raw state keyed by absolute state path at `#[PreDehydrate]` (`persistOldState`) and, after re-hydration, diffs each component's raw state against that snapshot at `#[PostHydrate]` (`callAfterSchemaStateUpdated`) to fire the `afterStateUpdated` hooks.
The raw-state reads route through Symfony PropertyAccess, the state backend owned by [`property-accessor.md`](property-accessor.md).
Because UX must reconstruct the object across the wire, the snapshot lives in a public `#[LiveProp]` array `$oldState` rather than Filament's protected property, roughly doubling the wire state.
Reverses only if UX gains a native per-property `updated` hook.

`callAfterSchemaStateUpdated()` carries an explicit `#[PostHydrate(priority: -10)]`, and that number is a **cross-package contract**, not a tuning knob.
It walks `getCachedSchemas()`, so any schema no `schemas()` name can rebuild has to be back in the cache before it runs.
Each such schema is re-registered at `#[PostHydrate(priority: 10)]` — higher runs earlier (`vendor/symfony/ux-live-component/src/Attribute/PostHydrate.php`): a mounted action's by [`HasActions::afterMountedActionsHydrated()`](../actions/has-actions.md#the-mounted-action-schema-re-cache-runs-above-callafterschemastateupdated-by-explicit-posthydrate-priorities), and a table's filter form by tables' `HasFilters::afterTableFiltersFormHydrated()`.
Reverse the order and every field inside such a schema is skipped: `live()` and `afterStateUpdated()` silently never fire there, and nothing reports an error.
Equal priorities would leave the order to reverse declaration order across traits composed onto one host, which is why both sides state a number rather than relying on the default `0`.
Neither side can be changed without the other.

### Schema discovery is explicit via `schemas()`, not reflection-based

Filament's `cacheSchema()` auto-discovers schema-defining methods by reflection: it accepts a bare name or an `<name>Schema` suffix, inspects the method's first-parameter type or return type to confirm it is a `Schema`, records the name in `discoveredSchemaNames`, and runs a `default<Name>()` hook if present.
Sylament's `getSchema()` ports the **method-name half** — it tries the bare name, then `"{$name}Schema"`, bare winning when both exist, and keys the schema by the declared name either way — but requires `schemas()` to enumerate the names, and has no reflection guard, no return-type detection, no `default<Name>` hook, and no `discoveredSchemaNames`.
A method that resolves but does not return a `Schema` still fails on the post-call `instanceof` check rather than being skipped by reflection.

`discoveredSchemaNames` in particular is skipped, not deferred.
It is a `#[Locked]` Livewire property memoising names the *view* resolved, and it works only because Livewire dehydrates **after** render.
UX LiveComponent dehydrates in [`AddLiveAttributesSubscriber::onPreRender()`](../../../vendor/symfony/ux-live-component/src/EventListener/AddLiveAttributesSubscriber.php), **before** the template body runs, so a name first resolved by `{{ renderSchema('article') }}` would be appended after the props are already frozen and would never reach the next request.
That is a correctness hole, not an inefficiency: `callAfterSchemaStateUpdated()` is `#[PostHydrate]` and iterates `getCachedSchemas()`, so a template-only schema would silently miss its `afterStateUpdated` hooks.
Names resolved before dehydration — in `mount()`, in `#[PostHydrate]`, or in a `#[LiveAction]` — would persist, but the partial guarantee is worse than none.
`schemas()` therefore stays the declaration that works in every case.
The reflection loop itself is todo: it arrives when a consumer needs the `default<Name>` seeding hook or the no-parameter `articleSchema(): Schema` shape.

### cacheSchema() has no discovery arm and requires its second argument

Filament's `cacheSchema(string $name, Schema | Closure | null $schema = null)` overloads a single call three ways via `func_num_args()`: pass a built schema to register it, pass nothing to run method-name discovery (mirroring `getSchema()`'s own resolution), or explicitly pass `null` to unset the cache entry — the last two are told apart only by argument count.
Sylament's `cacheSchema(string $name, ?Schema $schema)` drops the discovery arm — that logic already lives in `getSchema()`, so duplicating it here would be a second copy to keep in sync — and makes `$schema` required, so there is no argument-count ambiguity: a non-null `Schema` registers, `null` always unsets.
A schema registered this way — the mounted-action modal caches the action's schema under a synthetic name — is returned by `getCachedSchemas()` like any other, so it joins the `persistOldState()` / `callAfterSchemaStateUpdated()` loop for free; that is what makes reactive fields work inside an open modal.
Reverses only if a caller needs single-argument discovery through this method specifically rather than through `getSchema()`.

### hasCachedSchema() checks the raw cache, not getCachedSchemas()

Filament's `hasCachedSchema()` is `array_key_exists($name, $this->getCachedSchemas())`, which builds every schema declared in `schemas()` before answering a membership question.
Sylament's checks `array_key_exists($name, $this->cachedSchemas)` — the raw property — instead, so a lookup for a name that was never built (or was explicitly unset via `cacheSchema($name, null)`) never triggers a build.
Every caller wants a pure membership test — the mounted-action schema lookup (`HasActions::getMountedActionSchema()`), the schema-component action resolver, tables' filter form and `resolveSchemaByName()` — and none should force-build unrelated declared schemas just to check whether one specific name is cached.

### `partiallyRenderSchemaComponent()` has no LiveComponent twin

When the called method carries no `#[Renderless]` attribute, Filament's `callSchemaComponentMethod()` restricts Livewire's response to a re-render of the outermost enclosing schema that opted into partial rendering, and `partiallyRenderSchemaComponent()` asks one component to render itself partially — cheaper than re-rendering the whole component.
UX LiveComponent has no partial-render mechanism at all: an action response is always a full re-render of the host.
`partiallyRenderSchemaComponent()` is therefore **skipped**, not deferred — there is nothing to port it onto.
See [interacts-with-schemas.md](interacts-with-schemas.md#callschemacomponentmethod-answers-through-a-browser-event-with-no-partial-render) for the cost accounting (every call re-renders the host; bounded by the caller's debounce, same as any other `live()` field).

### `HasSchemas` composes `ComponentToolsTrait` alongside `HasActions`

`Symfony\UX\LiveComponent\ComponentToolsTrait` supplies `dispatchBrowserEvent()` (and its `emit*()` siblings), which both `HasActions` and `HasSchemas` need, so both compose it.
PHP's trait flattening dedupes a trait pulled in through two different composing traits without an `insteadof` — [`BothContractsHostFixture`](../../../packages/actions/tests/Fixtures/Concerns/BothContractsHostFixture.php), composing both `HasActions` and `HasSchemas` on one host, is exercised by actions' own suite.
No conflict resolution is needed.

### Dynamic Livewire properties are not ported because they hide from PHPStan

Filament's trait uses `ResolvesDynamicLivewireProperties`, whose `__get()` answers an undeclared property with the schema `getSchema($property)` builds, or with the action `getAction()` finds, so a Blade view can write `$this->form`.
A magic `__get()` is an API PHPStan cannot see, which the port's Symfony-idiom principle rules out whatever its source.
Twig reaches a schema through `getSchema()` or `renderSchema()` instead, so the trait is **skipped**.

### The host-level afterStateUpdated hook is renamed to `callAfterSchemaStateUpdated`

Filament names the host method `updatedInteractsWithSchemas` (mirroring the Livewire `updated<Trait>` convention).
Sylament names it `callAfterSchemaStateUpdated` to keep it distinct from the component-level `afterStateUpdated()` setter that lives on the state concern (see [`component.md`](component.md)), with which a host-level `afterStateUpdated` would collide.
The rename is deliberate and permanent.
