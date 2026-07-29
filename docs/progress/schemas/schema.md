# Schema (schemas)

[`Sylament\Schemas\Schema`](../../../packages/schemas/src/Schema.php) — the root
container of the component model: the object a live component holds, the state
root every field binds through, and the grid/flex layout that renders the child
tree. It extends `support`'s [`ViewComponent`](../support/component.md) and ports
[`schemas/src/Schema.php`](../../../vendor/filament/schemas/src/Schema.php),
folding the single-use container concerns Filament splits across
`Concerns/` — each is a surface row below rather than a file of its own.

The **shared** concerns Schema composes keep their own detail files and are named
in prose here without a status row:
[`HasColumns`](has-columns.md) (Schema aliases `getAllColumns` →
`getOwnAllColumns` and wraps it for parent inheritance),
[`HasGap`](has-gap.md) (Schema overrides `hasGap()`),
[`HasStateBindingModifiers`](has-state-binding-modifiers.md) (Schema overrides
`isLive*()`/`getLiveDebounce()`), the host contract
[`InteractsWithSchemas`](interacts-with-schemas.md), the bound-host trait
[`BelongsToLiveComponent`](belongs-to-live-component.md), the record binding
[`BelongsToRecord`](belongs-to-record.md) (both tiers in one file, and the home
of the `fill()`/`getData()` mapping edges), the operation context
[`HasOperation`](has-operation.md), and the PropertyAccess
state backend [`property-accessor.md`](property-accessor.md) — the one place the
`data_get`/`data_set` → Symfony `PropertyAccess` translation is derived, referenced
by every state read/write here. From `support` it composes
[`HasAlignment`](../support/has-alignment.md) (the four alignment factories build
on it) and `HasExtraAttributes`. The single-use validation concern
[`CanBeValidated`](can-be-validated.md) also keeps its own file rather than
folding in here — it spans the root trait and the host-side machinery in
[`HasSchemas`](has-schemas.md).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Schema.php`](../../../vendor/filament/schemas/src/Schema.php)
  — the container class and its inline overrides
- [`schemas/src/Concerns/`](../../../vendor/filament/schemas/src/Concerns)
  — the folded concern implementations behind the rows below

## Surface

Every point of this container, plus one row per folded concern. _Blocked by_
names what has to land first. _Detail_ carries either or both: **detail** → the
write-up in [Details](#details), deleted once the row is **done**; **diverges** →
the permanent note in [Divergences](#divergences). Rows never disappear — only
their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Construction + `make()`](../../../packages/schemas/src/Schema.php) | [`Schema::make()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`resolveDefaultClosureDependencyForEvaluationByName` (`$container`/`$liveComponent`/`$record`)](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-model-closure-parameter-and-the-model-type-gate-are-dropped) |
| [`resolveDefaultClosureDependencyForEvaluationByType` (record probe)](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-model-closure-parameter-and-the-model-type-gate-are-dropped) |
| [alignment factories `start`/`end`/`center`/`between`](../../../packages/schemas/src/Schema.php) | [`Schema::start()` …](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`isLive`/`isLiveOnBlur`/`isLiveDebounced`/`getLiveDebounce` parent-walk overrides](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | in progress | — | [detail](#the-parent-walk-inheritance-overrides-are-untested) |
| [`hasGap`/`isDense` parent-walk overrides](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`getAllColumns` parent inheritance override](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | in progress | — | [detail](#the-parent-walk-inheritance-overrides-are-untested) |
| [Twig-template rendering (`Schema/index.html.twig`)](../../../packages/schemas/templates/partials/Schema/index.html.twig) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-schema-renders-visible-children-only-without-hidden-placeholders) |
| Root Livewire/Alpine render wiring (`wire:partial`, `x-data filamentSchema`, client-side state reset) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | skipped | — | [diverges](#the-root-schema-drops-livewire-partial-render-and-alpine-wiring) |
| [`BelongsToParentComponent`](../../../packages/schemas/src/Concerns/BelongsToParentComponent.php) | [`Concerns/BelongsToParentComponent.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToParentComponent.php) | done | — | [diverges](#parentcomponent-accepts-null-to-detach) |
| [`CanBeEmbeddedInParentComponent`](../../../packages/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php) | [`Concerns/CanBeEmbeddedInParentComponent.php`](../../../vendor/filament/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php) | done | — | — |
| [`CanBeInline`](../../../packages/schemas/src/Concerns/CanBeInline.php) | [`Concerns/CanBeInline.php`](../../../vendor/filament/schemas/src/Concerns/CanBeInline.php) | done | — | — |
| [`HasComponents`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | in progress | — | [detail](#hascomponents-still-lacks-the-lookup-and-clone-layer) · [diverges](#getcomponents-preserves-the-callers-array-keys-instead-of-reindexing) |
| [`HasState`](../../../packages/schemas/src/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | in progress | — | [detail](#hasstate-still-lacks-constant-state-relationships-and-partial-rendering) · [diverges](#getstate-validates-without-a-prepareforvalidation-mutation-layer) |
| [`fill(object\|array\|null $state = null)` — the object arm](../../../packages/schemas/src/Concerns/HasState.php) | — | done | — | [belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares) |
| [`readStateFromRecord()`](../../../packages/schemas/src/Concerns/HasState.php) | — | done | — | [belongs-to-record.md](belongs-to-record.md#reads-are-lenient-writes-are-strict) |
| [`HasKey`](../../../packages/schemas/src/Concerns/HasKey.php) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Concerns/HasKey.php) | in progress | live-component id | [detail](#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback) |
| [`HasHeadings`](../../../packages/schemas/src/Concerns/HasHeadings.php) | [`Concerns/HasHeadings.php`](../../../vendor/filament/schemas/src/Concerns/HasHeadings.php) | done | — | — |
| [`CanModifyActions`](../../../packages/schemas/src/Concerns/CanModifyActions.php) | [`Concerns/CanModifyActions.php`](../../../vendor/filament/schemas/src/Concerns/CanModifyActions.php) | done | — | [diverges](#modifyaction-and-modifyactiongroup-guard-a-callbacks-return-type-at-runtime) |

Beyond the folded concerns, six of Filament's composed traits are not yet
ported at all — `CanBeHidden`, `Cloneable`, `HasEntryWrapper`,
`HasFieldWrapper`, `HasInlineLabels`, and support's
`HasDefaultDataFormattingSettings` — and earn their own README rows as they
land. (`CanBeDisabled`, `CanBeValidated`, `BelongsToRecord`, and `HasOperation`
are composed and tracked in their own files:
[can-be-disabled.md](can-be-disabled.md),
[can-be-validated.md](can-be-validated.md),
[belongs-to-record.md](belongs-to-record.md),
[has-operation.md](has-operation.md).)

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### The parent-walk inheritance overrides are untested

`isLive()`/`isLiveOnBlur()`/`isLiveDebounced()`/`getLiveDebounce()` and
`getAllColumns()` each fall through to `getParentComponent()?->…()` when the
schema declares no own value — the container's inheritance semantics. The
own-value branches are exercised (state-binding-modifier reads, `SchemaRenderTest`
column counts), but no test builds a parent→child schema pair and asserts the
child inherits the parent's live mode or column count. **In:** the override
bodies, ported faithfully. **Left:** direct coverage of the fall-through.
**Next:** add a unit case that parents one schema under another and asserts the
inherited `isLive`/`getAllColumns` result.

### `HasComponents` still lacks the lookup and clone layer

`components()`/`getComponents()`/`getFlatComponents()`/`getComponentByStatePath()`/
`getAction()` are ported and covered (35 unit cases plus the `SchemaRenderTest`
coercion and hidden-filtering paths). **In:** lazy closure resolution, container
assignment, `string`→`Text` and other→`Html` child coercion, visibility
filtering, top-level/nested/skip-container state-path lookup, and — matching
Filament's unions — `Action`/`ActionGroup` children: each is passed through the
container's `modifyAction()`/`modifyActionGroup()` hook, stamped
`schemaContainer($this)`, and left uncoerced;
`getComponents(bool $withActions = true, bool $withHidden = true)`
and `getFlatComponents(bool $withActions = true)` drop them when `$withActions`
is false (the state walks in `HasState`/`HasSchemas` and
`getComponentByStatePath()` all pass `withActions: false`; `getFlatComponents()`
appends actions but never descends into them). Both accessors declare PHPStan
conditional return types, so `withActions: false` call sites keep
`array<Component>` inference. The container-wide
`getAction(string $actionName, ?string $nestedContainerKey = null)` walks the
visible components: a direct `Action` child matches by name, a direct
`ActionGroup` child by its flat-actions map, a component-registered action only
when `$nestedContainerKey` exactly equals the owning component's relative key,
and every component's child schemas recursively — keyed child schemas are
entered only when the remaining key matches or prefixes them, unkeyed ones pass
it through, and a keyed component's inheritance-key prefix is stripped before
descending (`str_starts_with`/`mb_substr`, no Laravel `str()`). Depth-first,
first match wins; hidden components are skipped. **Left:** `getComponent()`,
`getFlatFields()` (depend on Fields),
`cloneComponents()`/`clearCachedChildSchemas()` (depend on `Cloneable`
and child-schema caching), and the deprecated `schema()` alias. **Next:** port
the rest as Fields/`Cloneable` land. The structural
difference it keeps — a single `$cachedComponents` slot instead of Filament's
flag-keyed memoization — is permanent and written up in
[Divergences](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization).

### `HasState` still lacks constant state, relationships, and partial rendering

Schema is the state root; this trait assembles the dehydrated bag. **In** and
tested (11 unit + 4 integration cases through a real UX LiveComponent, plus the
validation-path cases in
[`HasSchemasValidationTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasValidationTest.php)):
`fill()` → `hydrateState()` → `fillStateWithNull()` → `callAfterStateHydrated()`,
including the Sylament-only object arm (`fill($record)` →
`readStateFromRecord()` → the unchanged array arm — see
[belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares));
`getRawState()`/`rawState()` and `statePath()`/`getStatePath()`/`hasStatePath()`
with the absolute-path cache; and both dehydrated reads — the validating
`getState(bool $shouldCallHooksBefore = true, ?Closure $afterValidate = null)`
(`validate()` on entry, before any dehydration; `$afterValidate` invoked with
the final state) and the non-validating `getStateSnapshot()`, sharing one
assembly (`callBeforeStateDehydrated` — guarded by `$shouldCallHooksBefore` —
→ `dehydrateState` → `mutateDehydratedState` → unwrap) including
`hasDehydratedComponent()`/`buildDehydratedComponentsCache()`
and the shared-state-path sibling handling. **Left**, all unported:

- **Constant state** (`state()`/`constantState()`/`getConstantState()`) — the
  infolist static-state path; the record fallback chain it reads now exists
  ([`BelongsToRecord`](belongs-to-record.md)), so it is **blocked** on the
  infolists epic alone — plus the relationship state paths for its
  `getStatePathForRelationship()` arm.
- **The relationship layer** (`saveRelationships()`/
  `loadStateFromRelationships()`) — **blocked** on the Doctrine
  relationship-state epic.
- **Partial rendering** (`partiallyRender()`/`shouldPartiallyRender()`/
  `fillPartially()`/`hydrateStatePartially()`) — **blocked** on a UX
  LiveComponent partial-render equivalent; decide whether targeted re-render is
  a goal before porting.
- `getStateOnly()`/`getStateExcept()` — a trivial `array_intersect_key`/
  `array_diff_key` over `getState()`; add when a consumer needs them.
- The recursive public `flushCachedAbsoluteStatePaths()` — only the single
  protected `flushCachedAbsoluteStatePath()` exists; add the tree-walking flush
  when post-cache mutation (dynamic repeaters) becomes reachable.

**Next:** port each with the epic named in its bullet. Two small additions over
Filament's container trait already ship: `hasStatePath()` is defined here (Filament
inlines `filled($this->statePath)`), and the shared assembly resets the dehydrated
cache on entry so each run rebuilds it.

### `HasKey` ports the schema key chain, minus the state-path fallback

The schema-scope key model — `key()` setter, absolute prefix from the parent
component's inheritance key, `getInheritanceKey()` fallback, attribute/JS-char
sanitization, and absolute/inheritance-key caching with flush hooks — is ported
and covered. **Left:** two pieces. `getLiveComponentKey()` (Filament's
`getLivewireKey()`, the DOM key from the live-component id) is **blocked** on the
live-component id being reachable. More importantly, `getKey()` silently omits
Filament's `?? getStatePath(isAbsolute: false)` fallback even though
`getStatePath()` is live on the schema via `HasState` and the sibling
component-level `HasKey` keeps it — an unflagged, untested deviation, not a
recorded decision (commit `7aad138` gives no rationale). **Next:** reconcile it —
either restore `$key = $this->evaluate($this->key) ?? $this->getStatePath(isAbsolute: false)`
with a covering test (a keyless schema carrying a state path), or, if the drop is
intentional, promote it to a deliberate divergence explaining why a schema key
never falls back to its state path while a component key does. The deprecated
`name()`/`getName()` aliases are a separate, deliberate
[skip](#the-deprecated-name-and-getname-aliases-are-not-ported).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Each
says what would reverse it when the decision is provisional.

### Schema is the state root; Filament splits state across the Livewire base and the schema

Filament divides container state between its `Livewire\Component` base (which
holds the `$data` array, the updated-lifecycle hooks, and partial rendering) and
`Schema`. Sylament has no Livewire base to inherit: a schema binds to a plain UX
LiveComponent — the [`InteractsWithSchemas`](interacts-with-schemas.md) host —
reached through
[`BelongsToLiveComponent`](belongs-to-live-component.md),
and `HasState` reads and writes the host's bound properties through
[PropertyAccess](property-accessor.md). Everything Filament kept on the Livewire
base that the schema depends on (the state bag, the updated dispatch) is
relocated: state lives on the host's typed properties, and the updated-lifecycle
dispatch moves to the host's
[`#[PostHydrate]` pass](#schema-level-afterstateupdated-dispatch-moves-to-the-hosts-posthydrate-pass).
The schema becomes the single state root, and the render-time Livewire coupling
below drops out with the base class. Reverses only if a Livewire-shaped base is
reintroduced — not planned.

### The root schema drops Livewire partial-render and Alpine wiring

Filament's `renderEmbeddedHtml()` emits `wire:partial` (`schema.{key}`),
`x-data="filamentSchema({ livewireId, schemaKey })"`, and
`x-on:form-validation-error`/`reset-schema-component-state` on the root div, and
exposes `dispatchClientSideStateReset()`. Sylament's `Schema/index.html.twig` emits none
of these. Live binding runs field-side through UX LiveComponent `data-model`
attributes plus a full component re-render (proven by `LiveStateTest`), so
per-schema partial rendering, the client-side Alpine state object, and client-side
state reset have no port. Reverses if per-schema partial rendering or client-side
state reset become goals.

### The `$model` closure parameter and the `Model` type gate are dropped

By-name resolution now covers `$container` (the schema itself), `$liveComponent`
(the bound host, read from the raw nullable property so a standalone schema
injects `null`), and `$record` (`getRecord()`), and the `…ByType` override is
wired: a parameter whose type the bound record satisfies receives the record.
Two pieces of Filament's version stay dropped. `$model` — the class-string
parameter — is not injected, part of the
[record-only rename](belongs-to-record.md#the-model-closure-parameter-is-not-ported).
And where Filament gates its by-type probe on `is_a($type, Model::class)`, this
override probes `$record instanceof $parameterType` because there is no `Model`
base class to gate on
([why that is safe](belongs-to-record.md#the-record-type-probe-is-instanceof-not-a-model-gate)).
The self/static-type identity is still handled by the base `EvaluatesClosures`
own-class mechanism rather than a `self` arm here.

### `parentComponent()` accepts null to detach

Filament types `parentComponent(Component $component)` non-nullable. Sylament
widens it to `?Component`, so a schema can be reset to root (`isRoot()` true
again). Harmless superset of Filament's contract; reversible if a caller ever
needs the non-null guarantee.

### The component collection keeps a single cache and drops the flag-keyed memoization

Filament memoizes `getComponents` (with/without hidden), `getFlatComponents`, and
`getComponentByStatePath` under multi-dimensional caches keyed by
`withActions`/`withHidden`/`withOriginalKeys`/`withAbsoluteKeys`/`containerKey`/
`spl_object_id`. Sylament keeps only `$cachedComponents` (the raw resolved list)
and recomputes flat/by-state-path each call. `getComponents(bool $withActions = true,
bool $withHidden = true)` matches Filament's `withActions` default but keeps the
flipped `withHidden = true` default (Filament `false`; the render pipeline —
`Schema::render()`'s empty guard and `getExtraTemplateData()` — passes
`withHidden: false` explicitly), drops `withOriginalKeys` (see the
key-preservation divergence below), and filters on `isVisible()` rather than
`isHidden()`. `getFlatComponents(bool $withActions = true)` keeps only that one
flag — Filament's `withHidden`/`withAbsoluteKeys`/`containerKey` parameters and
its key-indexed carry are dropped: the flat list stays hidden-inclusive, ordered,
and unkeyed. Correct but not memoized; revisit if flat/state-path lookups become
hot.

### `getState()` validates without a prepareForValidation mutation layer

Filament's two container reads are now both ported —
`getState(bool $shouldCallHooksBefore = true, ?Closure $afterValidate = null)`
runs `validate()` on entry (throwing
[`ValidationException`](can-be-validated.md) before any dehydration) and
`getStateSnapshot()` is the validation-free variant — but the machinery
Filament wraps around its validate call stays permanently unported:
`prepareForValidation()` / per-component `mutateStateForValidation()` (which
rewrite a copy of the live state before Laravel's validator sees it — Symfony
violations never mutate state and there is no rule-object enum→scalar
conversion to prepare), `pruneStateToMatchKeys()` (which re-syncs clean
Livewire values), and the `Component::withVisibilityCache(...)` wrapper (no
visibility cache exists). The validator therefore sees the raw live state
exactly as the host reads it. `$afterValidate` is invoked plainly with the
final dehydrated state (Filament's `value($afterValidate, $state)` position).
The child-schema `isHiddenAndNotDehydratedWhenHidden()` guards remain
un-mirrored, consistent with hidden-guarding living at component level
([can-be-validated.md](can-be-validated.md#the-collection-walk-cannot-skip-directly-hidden-child-schemas)).

### `getRawState()` seeds an empty array on a blank or top-level path

Filament's container `getRawState()` returns `data_get($livewire, $statePath) ?? []`
typed `array|Arrayable`. Sylament returns `array` only and, when the schema has no
state path (top-level binding, no single bag to read), returns `[]` rather than
reading the host — the intended seed for the dehydration prune. `Arrayable`
support is dropped (Doctrine collections are not `Arrayable`-shaped here).

### The absolute-path cache is a nullable field plus a boolean flag, not an unset typed property

Filament declares `protected string $cachedAbsoluteStatePath;` and detects a hit
with `isset()`, flushing via `unset()` (with a phpstan-ignore for
`unset.possiblyHookedProperty`). Sylament declares
`?string $cachedAbsoluteStatePath = null` plus `bool $hasCachedAbsoluteStatePath`,
caching through a try/finally that sets value then flag and flushing both — to
avoid `unset()` of a typed property and keep PHPStan level max clean without an
ignore. Behaviourally identical; not provisional.

### Schema-level afterStateUpdated dispatch moves to the host's PostHydrate pass

Filament's `Schema::callAfterStateUpdated(string $path)` walks components matching
the updated path inside a partial-render `finally` block. Sylament omits it from
the trait and dispatches from `InteractsWithSchemas::callAfterSchemaStateUpdated()`
under UX LiveComponent's `#[PostHydrate]`: after re-hydration it iterates
`getFlatComponents` and fires `callAfterStateUpdatedHooks()` on every component
whose `getRawState` differs from the snapshot `getOldSchemaState`. This is the
Symfony-idiom translation of Livewire's updated lifecycle; the path-matching-plus-
bubbling shape and the `partiallyRender` `finally` block are not reproduced.
Reverses only if per-path targeted dispatch is needed.

### The deprecated `name()` and `getName()` aliases are not ported

Filament's schema-level `HasKey` carries `name()`/`getName()` as `@deprecated`
forwarders to `key()`/`getKey()`. Sylament omits them: they were dead on arrival
in the source we port from, so shipping them only reintroduces the ambiguity the
deprecation was retiring. The recommended `key()`/`getKey()` are ported.
Reversible only if a migrating consumer proves it still calls the old names.

### `getComponents()` preserves the caller's array keys instead of reindexing

Filament's `filterComponents()` calls `array_values()` unless `withOriginalKeys`
is set, so `getComponents()` returns a list reindexed from zero. Sylament keeps
each entry under its source index, and the visibility `array_filter` preserves
keys too, so a filtered result can carry the numeric gaps Filament's reindexed
output never has. Reversible by wrapping the return in `array_values()` if a
consumer ever needs a dense list.

### The schema renders visible children only, without hidden placeholders

Filament's `renderEmbeddedHtml()` walks `getComponents(withHidden: true)` and
keeps every hidden child in the DOM as an empty placeholder (`div.fi-sc-action
fi-hidden` for actions, a `toSchemaHtml(isVisible: false)` wrapper for
components) so Livewire's DOM diffing stays aligned across re-renders. Sylament
renders visible children only: `Schema::render()` returns `null` when
`getComponents(withHidden: false)` is empty — matching Filament's `''`-on-empty
contract, which is what lets `Section` suppress the chrome around an empty
slot — and `getExtraTemplateData()` hands the template the visible set as
`visibleChildren` pairs, each carrying the child and a precomputed `isAction`
flag (the Twig-side stand-in for the template's `instanceof` branch). No
placeholder element is ever emitted for a hidden child. Revisit together with
the live-component morphing strategy: if UX LiveComponent morphing needs stable
placeholders the way Livewire's does, the loop switches to with-hidden
visibility pairs like Filament's `$componentsWithVisibility`.

### `modifyAction()` and `modifyActionGroup()` guard a callback's return type at runtime

Filament's `($this->modifyActionsUsing)($action) ?? $action` relies on PHP's native
return-type coercion: a callback that returns neither an `Action` nor `null` trips
the method's own `: Action` return type, which throws a `TypeError` implicitly.
PHPStan cannot infer the return type of invoking a bare `?Closure` property, so it
flags the expression itself as returning `mixed` — and this codebase writes no
`Closure(Action): ?Action` signature spec anywhere, even for a callback invoked
plainly with fixed arguments. Sylament instead narrows explicitly: it holds the
coalesced result in a local variable, `instanceof`-checks it, and throws the same
`TypeError` itself on a mismatch. Observable behavior is unchanged — a conforming
callback or a `null` return works exactly as Filament's; a non-conforming one still
throws `TypeError`, just raised explicitly rather than by implicit return-type
coercion. `modifyActionGroup()` carries the identical guard. Reverses only if
PHPStan gains inference for arbitrary closure invocations.

_See also: the global [rename table](../renames.md) — the Livewire → UX
LiveComponent renames (`BelongsToLivewire`→`BelongsToLiveComponent`,
`livewire()`→`liveComponent()`, `getLivewire()`→`getLiveComponent()`,
`getLivewireKey()`→`getLiveComponentKey()`) and the `viewIdentifier`→`template`
idiom translation are recorded there, not as per-component divergences._
