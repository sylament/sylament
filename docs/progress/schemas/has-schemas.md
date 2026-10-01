# HasSchemas (schemas)

[`Sylament\Schemas\Concerns\HasSchemas`](../../../packages/schemas/src/Concerns/HasSchemas.php)
— the host-side trait a component (a panel page, a UX Live Component) mixes in to
own one or more [`Schema`](schema.md) instances: it enumerates their names,
builds-and-caches each from a same-named method, fills their state, and fires
their `afterStateUpdated` hooks across a request round-trip. It ports Filament's
[`Concerns\InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php).

The host owns the state shape, not the trait. It declares its own state as
`#[LiveProp(writable: true)]` properties — a `data` array bag, individual typed
props, or one bag per form — and each schema-defining method binds to them with
`->statePath(...)`, mirroring Filament's `->statePath($this->getFormStatePath())`
(which likewise defaults to no path, i.e. top-level binding). Because the trait
presumes no state shape, one host can carry several independently-bound schemas
without them colliding.

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
[Divergences](#divergences) — or, for the validation surface tracked in its own
file, a link to that file.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`schemas()` default `['schema']`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`getSchema()` — build from matching method, cache, throw, key by name](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| [`getCachedSchemas()` — build every declared name, reuse cached](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getCachedSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`schemasHostId` `#[LiveProp]` + `getSchemasHostId()` — the lazily minted key prefix](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`Components/Concerns/HasKey::getLivewireKey()`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) (`getLivewire()->getId()`) | done | — | [diverges](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) |
| [`makeSchema()` — `Schema::make($this)`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::makeSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`fill(object\|array\|null $state = null)` default schema](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares) |
| [`oldState` `#[LiveProp]` + `persistOldState()` `#[PreDehydrate]`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::updatingInteractsWithSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#livewires-updatingupdated-state-hooks-become-a-predehydrateposthydrate-snapshot-and-diff) |
| [`callAfterSchemaStateUpdated()` `#[PostHydrate]` — diff snapshot, fire hooks](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::updatedInteractsWithSchemas()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#the-host-level-afterstateupdated-hook-is-renamed-to-callafterschemastateupdated) |
| [`getOldSchemaState(?string)` — path-keyed pre-change lookup](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getOldSchemaState()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`<name>Schema` method-name fallback — bare name first, suffix second](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | — |
| [`cacheSchema()` — register/unset a schema instance by name](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#cacheschema-has-no-discovery-arm-and-requires-its-second-argument) |
| [`hasCachedSchema()` — raw-cache membership test](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::hasCachedSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [diverges](#hascachedschema-checks-the-raw-cache-not-getcachedschemas) |
| Reflection schema auto-discovery (`default<Name>` hooks, `discoveredSchemaNames`, return-type detection) | [`InteractsWithSchemas::cacheSchema()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [diverges](#schema-discovery-is-explicit-via-schemas-not-reflection-based) |
| [`resolveSchemaByName()` — the shared reflection gate](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#resolveschemabyname-is-a-sylament-only-extraction-with-no-filament-source) |
| [`getSchemaComponent()` absolute-key addressing](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::getSchemaComponent()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#getschemacomponent-drops-the-child-container-skip-list) |
| [`callSchemaComponentMethod()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::callSchemaComponentMethod()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#callschemacomponentmethod-answers-through-a-browser-event-with-no-partial-render) |
| [`uploadSchemaComponentFile()`](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [interacts-with-schemas.md](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
| `partiallyRenderSchemaComponent()` | [`InteractsWithSchemas::callSchemaComponentMethod()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [diverges](#partiallyrenderschemacomponent-has-no-livecomponent-twin) |
| [`ComponentToolsTrait` composition (`dispatchBrowserEvent()` and siblings)](../../../packages/schemas/src/Concerns/HasSchemas.php) | — | done | — | [diverges](#hasschemas-composes-componenttoolstrait-alongside-hasactions) |
| [Validation integration (`validateSchema`, error bag, sticky re-validation, and the record tail `validateSchemaRecord()` with the three key rules it runs — the changed and taken ones since M7 · 6, the blank one since M7 · 7 — and, since M7 · 10, the validateRecordKey() switch over the last two)](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | in progress | — | [can-be-validated.md](can-be-validated.md) · [the order](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) |
| [`form-validation-error` browser event on a failing submit-path validation](../../../packages/schemas/src/Concerns/HasSchemas.php) | [`InteractsWithSchemas::validate()`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | done | — | [can-be-validated.md](can-be-validated.md#the-throwing-branch-announces-form-validation-error-carrying-the-schema-identity) |
| Livewire file-upload integration (`use WithFileUploads`, `componentFileAttachments`, `isFileUploadForSchemaComponent`, `getSchemaComponentForFileUpload`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | skipped | — | [interacts-with-schemas.md](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam) |
| `ResolvesDynamicLivewireProperties` (used trait) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `ResolvesDynamicLivewireProperties` port | [detail](#the-dynamic-property-and-testing-integrations-arrive-with-their-own-concerns) |
| Translatable content driver (`getFilamentTranslatableContentDriver`, `getActiveSchemaLocale`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | todo | — | [detail](#the-translatable-content-driver-is-not-ported) |
| Testing helpers (`fillFormDataForTesting`, `disable/enableSchemaStateUpdateHooksForTesting`, `getDefaultTestingSchemaName`) | [`InteractsWithSchemas`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php) | blocked | `TestsSchemas` | [detail](#the-dynamic-property-and-testing-integrations-arrive-with-their-own-concerns) |

The sixteen **done** rows are the schema-hosting core (`schemas()`, `getSchema()`
with its `<name>Schema` method-name fallback, `getCachedSchemas()`,
`makeSchema()`, `fill()`, `cacheSchema()`, `hasCachedSchema()`), the full
PreDehydrate/PostHydrate state-hook loop (`oldState`, `persistOldState`,
`callAfterSchemaStateUpdated`, `getOldSchemaState`), and the browser-callable
seam (`resolveSchemaByName()`, `getSchemaComponent()`,
`callSchemaComponentMethod()`, `uploadSchemaComponentFile()` beside it, and the
`ComponentToolsTrait` composition they answer through — whose second consumer is
the `form-validation-error` event a failing submit-path validation dispatches).
The core is covered by
[`HasSchemasTest`](../../../packages/schemas/tests/Unit/Concerns/HasSchemasTest.php)
(build-from-method, default name, caching, throw-on-missing/non-`Schema`,
key-by-name, `getCachedSchemas`/`makeSchema` fresh-vs-bound, register/unset via
`cacheSchema`, and raw-cache membership via `hasCachedSchema` — including, via
the
[`DiscoveryThrowingSchemaHostFixture`](../../../packages/schemas/tests/Fixtures/Concerns/DiscoveryThrowingSchemaHostFixture.php),
that `hasCachedSchema` never triggers a build — and, via the
[`SuffixedSchemaHostFixture`](../../../packages/schemas/tests/Fixtures/Concerns/SuffixedSchemaHostFixture.php),
suffix resolution, bare-wins precedence, and keying by the declared name);
`fill()` by
[`HasStateTest`](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php)
(single + multi-schema) — the host method is a pure passthrough to the default
schema's `fill()`, so widening it to `object|array|null` for the
[record object arm](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares)
touched the signature and nothing else; and the snapshot-and-diff loop end-to-end by
[`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)
via the `LiveStateHost` fixture — it asserts `afterStateUpdated` fires with the
old value on a changed field and does not fire when unchanged.
`resolveSchemaByName()` and `getSchemaComponent()` are covered directly by
[`HasSchemasTest`](../../../packages/schemas/tests/Unit/Concerns/HasSchemasTest.php);
`callSchemaComponentMethod()`, its `ComponentToolsTrait`-supplied
`dispatchBrowserEvent()`, and the browser-callable seam end-to-end (the
attribute gate, the visibility gate, the argument-shape hardening, the
hidden-component and unknown-key/unknown-method arms) are covered by
[`HasSchemasComponentMethodTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasComponentMethodTest.php)
against the `LiveStateHost`/`LiveField` fixtures.
`uploadSchemaComponentFile()` — the second browser-callable action, which carries
the bytes the seam's JSON arguments cannot — is covered beside it by
[`HasSchemasUploadTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasUploadTest.php)
(the `AcceptsUploadedFiles` gate, the file-key shape check, the part-name match,
and the hidden/unknown/refusing arms), and its divergence from Filament's
Livewire-upload route is written up in
[interacts-with-schemas.md](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam).

## Details

Specifics for the rows that are not finished — what is left and what the next
step is. Delete a subsection when its row flips to **done**; the row and any
divergence stay behind.

### The dynamic-property and testing integrations arrive with their own concerns

Filament folds these into `InteractsWithSchemas` via `use`d traits and inline
methods; here each is **blocked** on the concern that owns it — dynamic
properties on a `ResolvesDynamicLivewireProperties` port, and the testing helpers
on `TestsSchemas`. The port wires them in through
those concerns rather than re-inlining them here, so the trait stays a thin
schema host. Nothing consumes them until a panel/live host lands. Filament's
file-upload members were the third of this group until M3 · 7 landed
`uploadSchemaComponentFile()`; they are now **skipped** rather than blocked,
because `use WithFileUploads` / `componentFileAttachments` /
`isFileUploadForSchemaComponent()` / `getSchemaComponentForFileUpload()` exist
only to fence the four Livewire upload RPC endpoints, and this port opens no such
endpoints —
[the write-up](interacts-with-schemas.md#uploadschemacomponentfile-carries-the-bytes-beside-the-seam)
has the reasoning, and it is the same reasoning that makes
`RestrictsFileUploadsToSchemaComponents` skipped in
[the package README](README.md) and the provider's `on('call')` abort hook
skipped in [schemas-bundle.md](schemas-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported).
The validation integration landed the same way: its members
(`validateSchema()`, the error bag, `clearSchemaValidation()`, the
`$validatedSchemas` sticky loop at `#[PostHydrate(priority: -20)]`) live on
this trait but are tracked in [`can-be-validated.md`](can-be-validated.md) with
the rest of the validation subsystem.

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

`callAfterSchemaStateUpdated()` carries an explicit `#[PostHydrate(priority: -10)]`,
and that number is a **cross-package contract**, not a tuning knob. It walks
`getCachedSchemas()`, so any schema no `schemas()` name can rebuild has to be
back in the cache before it runs. Exactly one such schema exists today: a
mounted action's, re-registered by
[`HasActions::afterMountedActionsHydrated()`](../actions/has-actions.md#the-mounted-action-schema-re-cache-runs-above-callafterschemastateupdated-by-explicit-posthydrate-priorities),
which pairs it with `#[PostHydrate(priority: 10)]` — higher runs earlier
(`vendor/symfony/ux-live-component/src/Attribute/PostHydrate.php`). Reverse the
two and every field inside an open modal is skipped: `live()` and
`afterStateUpdated()` silently never fire there, and nothing reports an error.
Equal priorities would leave the order to reverse declaration order across two
traits composed onto one host, which is why both sides state a number rather than
relying on the default `0`. Neither can be changed without the other.

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

### cacheSchema() has no discovery arm and requires its second argument

Filament's `cacheSchema(string $name, Schema | Closure | null $schema = null)`
overloads a single call three ways via `func_num_args()`: pass a built schema
to register it, pass nothing to run method-name discovery (mirroring
`getSchema()`'s own resolution), or explicitly pass `null` to unset the cache
entry — the last two are told apart only by argument count. Sylament's
`cacheSchema(string $name, ?Schema $schema)` drops the discovery arm — that
logic already lives in `getSchema()`, so duplicating it here would be a second
copy to keep in sync — and makes `$schema` required, so there is no
argument-count ambiguity: a non-null `Schema` registers, `null` always unsets.
A schema registered this way — the mounted-action modal caches the action's
schema under a synthetic name — is returned by `getCachedSchemas()` like any
other, so it joins the `persistOldState()` / `callAfterSchemaStateUpdated()` loop
for free; that is what makes reactive fields work inside an open modal. Reverses
only if a caller needs single-argument discovery through this method specifically
rather than through `getSchema()`.

### hasCachedSchema() checks the raw cache, not getCachedSchemas()

Filament's `hasCachedSchema()` is `array_key_exists($name, $this->getCachedSchemas())`,
which builds every schema declared in `schemas()` before answering a membership
question. Sylament's checks `array_key_exists($name, $this->cachedSchemas)` — the
raw property — instead, so a lookup for a name that was never built (or was
explicitly unset via `cacheSchema($name, null)`) never triggers a build. Both
Sylament call sites want a pure membership test: the mounted-action schema
lookup (`HasActions::mountedActionHasSchema()`) and the schema-component action
resolver, neither of which should force-build unrelated declared schemas just to
check whether one specific name is cached.

### `partiallyRenderSchemaComponent()` has no LiveComponent twin

Filament's `callSchemaComponentMethod()` restricts Livewire's response to a
re-render of the affected schema's own container when the called method
carries no `#[Renderless]` attribute — a partial render, cheaper than
re-rendering the whole component. UX LiveComponent has no partial-render
mechanism at all: an action response is always a full re-render of the host.
`partiallyRenderSchemaComponent()` is therefore **skipped**, not deferred —
there is nothing to port it onto. See
[interacts-with-schemas.md](interacts-with-schemas.md#callschemacomponentmethod-answers-through-a-browser-event-with-no-partial-render)
for the full cost accounting (every call re-renders the host; bounded by the
caller's debounce, same as any other `live()` field).

### `HasSchemas` composes `ComponentToolsTrait` alongside `HasActions`

`Symfony\UX\LiveComponent\ComponentToolsTrait` supplies `dispatchBrowserEvent()`
(and its `emit*()` siblings) that both `HasActions` and, from this change,
`HasSchemas` need. PHP's trait flattening dedupes a trait pulled in through two
different composing traits without an `insteadof` — verified against
[`BothContractsHostFixture`](../../../packages/actions/tests/Fixtures/Concerns/BothContractsHostFixture.php),
the existing fixture composing both `HasActions` and `HasSchemas` on one host,
which is exercised (and stays green) by actions' own suite. No conflict
resolution was added because none is needed.

### The host-level afterStateUpdated hook is renamed to `callAfterSchemaStateUpdated`

Filament names the host method `updatedInteractsWithSchemas` (mirroring the
Livewire `updated<Trait>` convention). Sylament names it
`callAfterSchemaStateUpdated` to keep it distinct from the component-level
`afterStateUpdated()` setter that lives on the state concern (see
[`component.md`](component.md)) — the state-layer prototype collided the two
names. The rename is deliberate and permanent.

_See also: the global [rename table](../renames.md) — the interface⇄trait label
swap (`InteractsWithSchemas` trait → `HasSchemas` trait), the Livewire → UX
LiveComponent hook translation, `resolveSchemaByName()`, and the
`sylament:schema-component-method-result` browser event are recorded there._
