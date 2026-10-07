---
description: 'The host trait `HasFilters` that `HasTable` composes: the two filter bags, the filters form and the filter live actions. (draft)'
ports:
  - vendor/filament/tables/src/Concerns/HasFilters.php
owns:
  - packages/tables/src/Concerns/HasFilters.php
---
# Concerns\HasFilters (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Concerns\HasFilters` — the `$tableFilters` / `$tableDeferredFilters` bags, `getTableFilterState()`, `getTableFilterFormState()`, `parseTableFilterName()`, `getTableFiltersForm()`, the `#[PostMount]` fill and the `#[PostHydrate(priority: 10)]` re-cache](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | in progress | — | [diverges](#the-filter-bags-are-arrays-never-null-and-carry-no-getters) · [diverges](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) · [diverges](#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults) · [semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it) · [diverges](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [semantics](#the-two-filter-bags-round-trip-differently-and-the-difference-is-the-whole-deferredlive-split) |
| [`applyTableFilters()`, `removeTableFilter()`, `removeTableFilters()`, `resetTableFiltersForm()`, `updatedTableFilters()`, `handleTableFilterUpdates()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [detail](#filter-mutations-reset-the-page-and-flush-the-record-cache) · [diverges](#the-live-mode-filter-hook-is-a-per-field-afterstateupdated-not-the-props-onupdated) |

## Details

### Filter mutations reset the page and flush the record cache

Every filter mutation ends in the same two lines — `tablePage = 1` and `flushCachedTableRecords()` — because a narrowed result set makes the current page number meaningless and the render cache stale.
That is `handleTableFilterUpdates()`, and it is the tail of `updatedTableFilters()`, of `removeTableFilter()`, `removeTableFilters()` and `resetTableFiltersForm()` in live mode, and of `applyTableFilters()` in every mode.
Filament's tail is the same reset plus two things that do not exist here — the session write and `deselectAllTableRecords()` — and it omits the flush because a Livewire request starts with a null cache; ours is one object across the whole request, so the flush is what the page reset would otherwise lie about.

The deferred branch is the fork: `removeTableFilter()`, `removeTableFilters()` and `resetTableFiltersForm()` call `applyTableFilters()` instead of the tail when the table defers, which copies the draft bag over the applied one.
So a chip removal on a deferred table also **applies whatever else was pending in the panel** — Filament's semantics exactly, and the reason removal writes through the schema rather than the arrays: `resetTableFilterState()` resolves the filter's `Group` through `Schema::getComponentByStatePath()` (built from the form's own root path plus the filter name, since our lookup takes an absolute path), walks its child schema's flat components for `Field` instances — Filament reads the same set off a `getFlatFields()` that [schemas has not ported](../schemas/schema.md#the-schema-level-clearcachedchildschemas-has-every-prerequisite) — and calls `state()` on each.
Whichever bag the form's state path names receives the write.

Per field the new value is `getResetState()[$fieldName]` when the filter names one, and otherwise derived from the current state's type: an array resets to `[]`, a bool to `false`, anything else to `null`.
"Names one" is `array_key_exists()` here and `$filterResetState[$fieldName] ?? …` upstream, so a reset state that names a field **with a null value** is honoured here and falls through to the type-derived arm there — a fix rather than a mirror, and the one that makes `SelectFilter`'s `['value' => null]` do what it says.

The bool arm consults [`hasNullableBooleanState()`](../forms/field.md#hasnullablebooleanstate-has-exactly-one-consumer) first and writes `null` instead of `false` when it answers true — which `Select`, `Radio` and `ToggleButtons` all do.
This is the predicate's only consumer in either tree, so restoring the branch is what porting it was *for*.

The arm is narrower than it looks either way, because the fallback runs only for a field the filter's `getResetState()` does not name, and `SelectFilter` seeds `['value' => null]` in `setUp()` — as Filament's does, at `SelectFilter.php:148` — so every `SelectFilter` and every `TernaryFilter` takes the explicit-null left side and clears before the `match` is reached.
`Checkbox`/`Toggle` are the other side of it: both cast through a non-nullable `BooleanStateCast`, where `false` genuinely is the resting value, and neither overrides the predicate, so both still reset to `false`.

What the branch actually decides is a hand-built nullable boolean inside a custom filter schema — `Filter::make('x')->schema([Select::make('f')->boolean()])` — which declares no reset state.
That now clears to `null` rather than to `0`; the `0` it used to write was a *false* filter to any ternary-shaped query arm, not a cleared one, so the flattening had been a real behaviour difference and not only a typing one.
Both arms are pinned in [`HasFiltersTest`](../../../packages/tables/tests/Unit/Concerns/HasFiltersTest.php) — the nullable field clearing to `null`, the `Checkbox` beside it still clearing to `false`.

A named `$field` narrows the reset to that one field only when the group actually carries it — an unknown name resets the whole group, which is Filament's `array_key_exists` guard and keeps a stale chip from becoming a no-op.

`removeTableFilters()` removes a filter only when **every** one of its indicators is removable; a filter with no indicators is vacuously removable and is reset.
It then clears the global search through `resetTableSearch()`, the host action the search chip's X also targets — `tableSearch = ''`, page one, flush — which is why that method lives on `CanSearchRecords` beside `updatedTableSearch()` rather than in the filters concern that calls it.

Two signature-level deltas fall out of that pair.
Filament's `removeTableFilter()` carries a third parameter, `bool $isRemovingAllFilters = false`, which `removeTableFilters()` passes to borrow the body without the tail; ours splits the body into the private `resetTableFilterState()` instead, so the public action's signature is exactly the two `#[LiveArg]`s a chip sends and no caller can ask it to skip its own tail.
And Filament's `resetTableSearch()` delegates (`$this->tableSearch = ''; $this->updatedTableSearch();`) where ours inlines the same three lines, because our `updatedTableSearch()` carries a `$previousValue` no-op guard Filament's hook does not — delegating would work only by the accident that the defaulted `null` never equals `''`.
Behaviour is identical today; the cost is that a host overriding `updatedTableSearch()` does not see its override run from the reset path.

`resetTableFiltersForm()` fills the form with **defaults**, not reset state: `Schema::fill()` with no argument runs `hydrateState(applyDefaults: true)`, so a `Filter::make('featured')->default()` comes back checked where a chip removal would have unchecked it.
The two are deliberately different operations.
Every other field clears to null, including one whose filter declares no `default()` — a `SelectFilter`'s `value` is the live case, and the browser pass that found it standing after a reset is what [restored the defaults-fill null-write](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default) in the schemas state layer.

## Semantics

### `updatedTableFilters()` fires for a whole-prop write, never for a field inside it

`$tableFilters` carries `onUpdated: 'updatedTableFilters'`, and it is easy to read that as *"any client write under this prop calls the hook"*.
It does not, and the mechanism is worth stating because the live-mode page reset turns on it.

`LiveComponentHydrator::processOnUpdatedHook()` normalises a **string** hook to `[LiveProp::IDENTITY => $method]`, then walks the map.
The `IDENTITY` branch `continue`s unless `$dehydratedUpdatedProps->hasPropValue('tableFilters')`, and `DehydratedProps::hasPropValue()` is `array_key_exists($propName, $this->propValues)` — an exact key lookup, no prefix matching.
A client write to a field inside the bag arrives under its own key, `tableFilters.status.value`, which is not `tableFilters`, so the identity hook does not fire.
The array form of `onUpdated` is what covers nested paths, and it takes **fixed** path strings — which dynamic filter names, keyed by whatever the user called the filter, cannot supply.

So the hook covers exactly the direct whole-prop writes: a test helper's `set('tableFilters', [...])`, and the applying half's own PHP-side assignment, which is inside our code and calls `handleTableFilterUpdates()` outright rather than relying on a hydration hook to notice.
Live-mode per-field resets ride the schema's `afterStateUpdated` walk instead — the `-10` `PostHydrate` in [`HasSchemas`](../schemas/has-schemas.md), which is also why this trait rebuilds its cache-only form at priority `10`.

### The two filter bags round-trip differently, and the difference is the whole deferred/live split

Deferred is the default, and in that mode the filters schema is rooted at `tableDeferredFilters` while the query reads `tableFilters`.
A field write therefore lands in the draft bag and changes nothing: the rows, the page and the indicator chips all still describe the applied bag, and `applyTableFilters()` is the single copy that promotes draft to applied and runs `handleTableFilterUpdates()` — page to 1, record cache flushed.
There is no "unapplied changes" affordance, matching Filament: the trigger badge and the chips count applied indicators, and the apply button is simply always visible while deferring.
Filament's deferred branch also calls `Schema::partiallyRender()`, which stamps a `wire:partial` on the rendered schema so Livewire can re-render that subtree alone; there is no port and nothing to port it to — a ux-live-component request re-renders the whole component and idiomorph diffs the result — so the branch here is the `statePath()` and nothing more.

Live mode roots the schema at `tableFilters` itself with a root `->live()`, so a field write *is* the mutation.
Which mechanism carries the page reset and the flush depends on the write's shape, and the panel only ever sends one of them: a **nested** write at the field's own model fires no `onUpdated` hook at all (the identity hook is an exact key match — see the note above), so it reaches `handleTableFilterUpdates()` through the field's `afterStateUpdated` instead, which is why this trait rebuilds its cache-only form at `#[PostHydrate(priority: 10)]` — above the `-10` walk that fires those hooks.
A **whole-prop** write is the shape `updatedTableFilters()` exists for, and no rendered element produces it.

The observable contract is the same at the end of either path, which is why one suite covers all three: `FiltersLiveTest` drives the deferred path through the panel's field models and the apply action, the live path through the panel's field models after paging off page 1 — so the reset is observable, and a broken link anywhere in that chain renders the empty state — and the hook through the one whole-prop write it keeps for that purpose.

Three consequences of removal being a **schema-state** mutation rather than an array edit are pinned there too.
A chip's X resets the field its indicator was keyed to and leaves the sibling field of the same filter standing, because `removeTableFilter()` narrows the group's `Field` walk to that one name.
In deferred mode a chip removal also applies any pending draft edits, because its tail *is* `applyTableFilters()` — Filament's semantics, inherited deliberately.
`resetTableFiltersForm()` re-fills from the field **defaults**, not from `getResetState()`, so a filter carrying `default()` comes back set rather than empty.

Record resolution runs the same filters with `isResolvingRecord: true`, so a row action cannot reach a record outside the filtered set; a filter that opts out with `excludeWhenResolvingRecord()` is skipped in that pass only, and both arms are pinned by mounting the same action against the same filtered-out key.

One shape is worth stating because nothing rejects it: a **whole-prop** write to either bag replaces it outright, and a filter whose key the replacement omits is handed an empty slice — which [`isActive`'s opt-out polarity](base-filter.md#isactive-is-an-opt-out-not-an-opt-in) reads as *active*, while the indicator gate's opposite polarity shows no chip for it.
The panel never produces that shape, because each field writes at its own `<bag>.<filter>.<field>` model and the `#[PostMount]` fill seeded every key before it; custom chrome that assigns the bag wholesale has to seed them itself.
`FiltersLiveTest` drives the field models in both modes for exactly this reason, and keeps a single whole-prop write, on the live host, purely as the `updatedTableFilters()` pin.

The mirror image is a **nested** write straight at `tableFilters.<filter>.<field>` while deferring — equally unrejected, and equally not a shape the shipped chrome emits, since the panel binds the deferred bag there — which reaches the applied bag having fired nothing at all: the identity `onUpdated` wants the exact `tableFilters` key, deferred mode attaches no `afterStateUpdated` to its fields, and the `-10` walk is watching a form rooted at `tableDeferredFilters`.
So the page never resets, the draft bag is never mirrored back from the write, and the next `applyTableFilters()` overwrites it with the stale draft.

## Divergences

### The filter bags are arrays, never null, and carry no getters

Filament types both filter props `?array` and defaults them to `null` (`public ?array $tableFilters = null;`), and its boot hook deliberately writes `null` back when the bag is empty.
Ours are `array` with an `[]` default, both of them.

The reason is the element on the other end.
The filters form binds a field to a model path like `tableFilters.status.value`, and the client resolves that path against the props payload on both reads *and* writes — `ValueStore.set()` calls `get()` first to compare, and `get()` falls through to `getDeepData()` unless a flat key of that exact dotted name is already sitting in the dirty, pending or server-sent props.
On the render that matters — the first one, where the server sent the bag under `tableFilters` and nothing has been written yet — nothing is.
`getDeepData()` guards `undefined` and only `undefined`; the walk beneath it is a bare `currentLevelData = currentLevelData[parts[i]]` loop.
So a `null` parent is not an empty object to descend into — it is `null['status']`, an uncaught `TypeError` in the browser, with nothing on the server to catch or report it.
The symptom is a field that silently stops working, not an error anyone can trace.
`[]` walks.

Filament gets away with `null` because Livewire's `data_get`/`data_set` pair tolerates it on both sides; ux-live-component has no such layer, so this is the ordinary Eloquent-to-Symfony shape of that difference rather than a judgment about which default reads better.
The one thing Filament's `null` bought — a tri-state where *"never set"* differs from *"set to nothing"* — is only load-bearing for the session and query-string lanes deciding whether to seed from a stored bag, and [neither is ported](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it).

The second half is a rule rather than a type.
**There is no `getTableFilters()`, and there must never be one**, for either bag.
A `LiveProp` is de/hydrated through Symfony's `PropertyAccessor`, which prefers a same-named getter over the property — so a `getTableFilters()` would become what goes on the wire, and any value it returned that differs from the raw property leaves every element bound under that path permanently dirty, re-firing its update hook on every request.
The same mechanism is [load-bearing one way and a defect the other](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) for `$tableHostId` and the two writable props above; the bags are the case where the answer is simply not to write the getter.
Reading a slice is what consumers want anyway, and that is what [`getTableFilterState()`](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) is for.

### One filter slice is a plain array read, not a `PropertyAccessor` walk

Filament reads a slice with `Arr::get($this->tableFilters, $this->parseTableFilterName($name))`, a dotted-path accessor.
Ours is `$this->tableFilters[$name] ?? null` — a single array read — and then checks the result is an array before returning it.

The path lookup buys nothing here, because a filter name can never be dotted.
It is the key the table files the filter under *and* the state-path segment its `Group` binds to, so a dot in one would split the other; the accessor is for schema state paths resolved against the host object, which is a different job done by a different layer.
What the plain read costs is the `null` a missing key would have produced anyway, so the arms match.

The shape check is the part that is not just a simplification.
Both bags are **writable** `LiveProp`s, which ux-live-component exempts from the prop checksum by design — the client can put anything under any key, and a forged or merely stale payload can leave a string where a filter's state map should be.
Validating on read means the filter layer receives either a state map or `null`, never a scalar it will index into; the same posture the per-page getter takes, for the same reason.
The check is **field-keyed**, not merely `is_array()`: a slice whose keys are not all strings is a list the client put where a map belongs, which is not a state map either, so it reads as `null` too.
That is what the declared `array<string, mixed>|null` return has always claimed and what the prose above it has always said; the `is_array()` half of the check went unchallenged for as long as [nothing composed the trait](has-table.md#a-host-trait-is-analysed-only-through-a-class-that-composes-it).
A slice that reads as `null` reaches a filter as `[]`, through the same `?? []` a missing key already took.
`parseTableFilterName()` carries a smaller version of it: Filament's returns `$name::getDefaultName()` unguarded against a `string` return type, so a `BaseFilter` subclass that never declared a default name is a `TypeError` there, where ours falls back to the class-string with `?? $name`.

### A seeded filter bag fills the form instead of the declared defaults

`mountTableFilters()` branches on whether the bag arrived with something in it, and the branch is Filament's exactly.
An **empty** bag reaches `fill()` as `null`, which runs `hydrateState(applyDefaults: true)` and seeds every filter's declared default.
A **non-empty** one — seeded from the query string, read back from the session, or handed in by a parent passing `tableFilters` — is normalised and then passed as `fill($seededFilters)`, which runs `hydrateState(applyDefaults: false)`, so the incoming bag is written as raw state and the schema's own defaults are skipped whole.
Filament reaches the same two arms from the other side: it nulls an empty bag first (`if (! count($this->tableFilters ?? [])) { $this->tableFilters = null; }`) and `Schema::fill(null)` is what applies its defaults.

The consequence is a **partial** seed dropping its siblings' defaults, and it is Filament-identical rather than a port artefact.
A table declaring a `featured` filter and a `plays` filter defaulted to `'100'` answers `?filters[featured][value]=null` with `plays` at null, not at `'100'`: one named filter in the URL turns the whole defaults pass off.
A user who wants both writes both into the URL, which is what the URL the table itself writes back always carries.

Normalisation is the second half.
A query string carries no types, so `normalizeTableFilterValuesFromQueryString()` walks the bag recursively and maps the three scalars that arrive as their own names — `'null'` → null, `'false'` → false, `'true'` → true — before the fill, because a `TernaryFilter` reading the **string** `'false'` would take its true arm.
Filament runs the same pass, in the same place, for the same reason (its `filamentphp/filament#7999` comment).
One thing follows that is easy to miss: the pass runs on the bag whatever filled it, so a **session-restored** bag is normalised too — a text filter the session stored as the literal string `'true'` comes back typed `true`.
That is Filament's behaviour as well, and it is the reason a session value round-trips differently from the value that was written.

Two smaller notes on the same hook.
The filter bag has a query-string seed of its own, a `#[PostMount(priority: 20)]` above both the session hook and the fill, because UX refuses to seed this one prop: it assigns a seeded value only when the prop's resolved type accepts it, and `property-info` reads `removeTableFilter()` as a *remover* for a `tableFilter` singular, inferring `list<string>` for the bag — which no filter state ever is.
That seed reads `->query->all()` off the **main** request only, so a `UrlMapping(mapPath: true)` on filters would bind nothing: a path-mapped filter bag is not a shape this port reads.

That hook branches on the parameter's **presence**, not on its value, and the difference is load-bearing.
An absent `?filters` is a no-op, so the session is free to seed the bag afterwards.
A **present but malformed** one — `?filters[]=abc`, where the bag is a list rather than filter-name-keyed — is not declined and left alone but assigned `[]`, because UX has *already* written the parameter onto the prop by the time this runs: returning would leave a list in a filter-name-keyed property, which then reads as a non-empty bag and suppresses every filter's declared default.
Emptying it puts the mount back on the `fill(null)` arm above, so a hand-edited URL costs the user their filter *values* and not the table's own defaults.
And `Concerns\HasFilters` now requires `CanPersistTableState` to be composed beside it — it calls `getTableStateMainRequest()`, `isPersistedTableFilterBag()` and `persistTableFiltersInSession()` — which `HasTable` does, so the coupling only matters to a host assembling the concerns by hand.

### The live-mode filter hook is a per-field `afterStateUpdated`, not the prop's `onUpdated`

Livewire calls `updatedTableFilters()` for a write to any path *inside* the bag — `SupportLifecycleHooks::update()` derives the hook name from the dotted path's head (`studly()->before('.')`) and hands the remainder over as a second argument — so Filament's live mode gets its page reset from that hook alone.
Ux-live-component's identity `onUpdated` fires only for a write to the whole prop — [the mechanism is in Semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it), and its array form takes compile-time path strings where ours are the user's filter names — so the same hook covers strictly less here and live mode needs a second path.

That path is the schema: the non-deferred branch of `getTableFiltersForm()` attaches `afterStateUpdated(fn () => $this->handleTableFilterUpdates())` to every `Field` in the form, and `HasSchemas::callAfterSchemaStateUpdated()` — the `#[PostHydrate(priority: -10)]` pass that diffs `$oldState` against the hydrated state — fires them for exactly the fields that changed.
The filters form is in `getCachedSchemas()` by then because [the re-cache hook runs at priority `10`](../schemas/has-schemas.md).
Deferred mode attaches nothing, since a draft edit is meant not to touch the query; the pinning cases assert both halves.

`updatedTableFilters()` is kept rather than deleted: it is the whole-bag write path — a server-side assignment, a test, a `data-model="tableFilters"` binding — and it is what mirrors an applied write back into the deferred bag.
