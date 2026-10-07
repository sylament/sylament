---
description: 'The host trait `Concerns\HasFilters`: the applied and deferred filter bags, the filters form, and the live actions that apply, remove and reset filters.'
ports:
  - vendor/filament/tables/src/Concerns/HasFilters.php
  - vendor/filament/tables/src/Concerns/InteractsWithTable.php
owns:
  - packages/tables/src/Concerns/HasFilters.php
---
# Concerns\HasFilters (tables)

[`Sylament\Tables\Concerns\HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php) is the host half of filtering, composed by [`HasTable`](has-table.md); the table-side settings and the panel chrome are [`has-filters.md`](has-filters.md)'s, and the filters themselves are [`base-filter.md`](base-filter.md)'s.
It calls `getTableStateMainRequest()`, `isPersistedTableFilterBag()` and `persistTableFiltersInSession()` on [`CanPersistTableState`](can-persist-table-state.md), so a host assembling the concerns by hand composes that trait beside it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$tableFilters` / `$tableDeferredFilters` + `modifyTableFiltersProp()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [diverges](#the-filter-bags-are-arrays-never-null-and-carry-no-getters) · [semantics](#the-two-filter-bags-round-trip-differently-and-the-difference-is-the-whole-deferredlive-split) |
| [`getTableFilterState()` / `getTableFilterFormState()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [diverges](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) |
| [`parseTableFilterName()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [diverges](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) |
| [`getTableFiltersForm()` + the `#[PostHydrate(priority: 10)]` re-cache](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters::getTableFiltersForm()`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [semantics](#the-two-filter-bags-round-trip-differently-and-the-difference-is-the-whole-deferredlive-split) · [diverges](#the-live-mode-filter-hook-is-a-per-field-afterstateupdated-not-the-props-onupdated) |
| [`mountTableFilters()`, the `#[PostMount]` fill](../../../packages/tables/src/Concerns/HasFilters.php) | [`InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [semantics](#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults) · [diverges](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`mountTableFiltersFromQueryString()`, the `#[PostMount(priority: 20)]` seed](../../../packages/tables/src/Concerns/HasFilters.php) | [`InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-filter-bag-seeds-itself-from-the-query-string-because-ux-refuses-to) |
| [`applyTableFilters()`, `removeTableFilter()`, `removeTableFilters()`, `resetTableFiltersForm()`, `updatedTableFilters()`, `handleTableFilterUpdates()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [semantics](#filter-mutations-reset-the-page-and-flush-the-record-cache) · [semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it) · [diverges](#a-reset-state-that-names-a-field-with-null-is-honoured) · [diverges](#removetablefilter-carries-no-isremovingallfilters-parameter) · [diverges](#the-live-mode-filter-hook-is-a-per-field-afterstateupdated-not-the-props-onupdated) |
| `resetTableFiltersForm()`'s disabled and authorization check | [`Concerns\HasFilters::resetTableFiltersForm()`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | blocked | `getFiltersResetAction()` ([has-filters.md](has-filters.md#the-resets-are-hand-built-because-getfiltersresetaction-is-not-ported)) | — |
| `removeTableFilter()`'s two `QueryBuilder` arms | [`Concerns\HasFilters::removeTableFilter()`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | blocked | the `QueryBuilder` filter ([README.md](README.md#unported)) | — |
| [`applyFiltersToTableQuery()`, the base pass and the apply pass](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters::applyFiltersToTableQuery()`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [semantics](#record-resolution-skips-a-filter-that-opts-out) · [base-filter.md](base-filter.md#there-is-no-closure-where-group-around-apply) · [has-filters.md](has-filters.md#withappliedfiltersformstate-waits-on-the-querybuilder-filter) |
| `getTableFiltersSessionKey()` | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | skipped | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| `getTableFilters()`, `getTableFiltersFormWidth()`, `getTableFiltersFormMaxHeight()`, `shouldPersistTableFiltersInSession()` | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | skipped | — | [deprecated for `table()`](has-table.md#maketable-seeds-nothing-from-deprecated-host-getters) |

## Semantics

### Filter mutations reset the page and flush the record cache

Every filter mutation ends in `handleTableFilterUpdates()`: it deselects every record when the table [deselects on filtering](has-filters.md#one-filter-setting-drives-three-selection-behaviours), sets `tablePage = 1`, calls `flushCachedTableRecords()` and writes the bag to the session when the table persists it.
It is the tail of `updatedTableFilters()`, of `removeTableFilter()`, `removeTableFilters()` and `resetTableFiltersForm()` in live mode, and of `applyTableFilters()` in every mode.
Filament's tail is the same session write, deselection and page reset, without the flush, because a Livewire request starts with a null record cache; the port's cache is one object across the whole request, so the flush is what keeps the page reset from rendering stale rows.

The deferred branch is the fork: `removeTableFilter()`, `removeTableFilters()` and `resetTableFiltersForm()` call `applyTableFilters()` instead of the tail when the table defers, which copies the draft bag over the applied one.
So a chip removal on a deferred table also **applies whatever else was pending in the panel**, Filament's semantics exactly, and the reason removal writes through the schema rather than the arrays: `resetTableFilterState()` resolves the filter's `Group` through `Schema::getComponentByStatePath()` (built from the form's own root path plus the filter name, since the lookup takes an absolute path), walks its child schema's flat components for `Field` instances (Filament reads the same set off a `getFlatFields()` that [schemas has not ported](../schemas/schema.md#surface)) and calls `state()` on each.
Whichever bag the form's state path names receives the write.

Per field the new value is the filter's `getResetState()` entry when it names the field, and otherwise derived from the current state's type: an array resets to `[]`, a bool to `false`, anything else to `null`.
The bool arm consults [`hasNullableBooleanState()`](../forms/field.md#hasnullablebooleanstate-has-exactly-one-consumer) first and writes `null` instead of `false` when it answers true, which `Select`, `Radio` and `ToggleButtons` do.
The arm is narrower than it looks, because the fallback runs only for a field the filter's `getResetState()` does not name, and `SelectFilter` seeds `['value' => null]` (`['values' => []]` when multiple) in `setUp()`, as Filament's does at `SelectFilter.php:148`, so every `SelectFilter` and every `TernaryFilter` takes the explicit side and clears before the `match` is reached.
`Checkbox` and `Toggle` cast through a non-nullable `BooleanStateCast`, where `false` is the resting value, and neither overrides the predicate, so both reset to `false`.
What the branch decides is a hand-built nullable boolean inside a custom filter schema, `Filter::make('x')->schema([Select::make('f')->boolean()])`, which declares no reset state and clears to `null` rather than to a `false` that a ternary-shaped query arm would read as a choice.

A named `$field` narrows the reset to that one field only when the group carries it; an unknown name resets the whole group, which is Filament's `array_key_exists` guard and keeps a stale chip from becoming a no-op.

`removeTableFilters()` removes a filter only when **every** one of its indicators is removable, so a filter with no indicators is vacuously removable and is reset.
It then clears the global term through `resetTableSearch()`, the host action the search chip's X also targets, and the per-column terms through `resetTableColumnSearches()`, as Filament does; `resetTableSearch()` lives on `CanSearchRecords` ([why it inlines its reset](host-can-search-records.md#resettablesearch-inlines-its-reset-where-filament-delegates-to-updatedtablesearch)).

`resetTableFiltersForm()` fills the form with **defaults**, not reset state: `Schema::fill()` with no argument runs `hydrateState(applyDefaults: true)`, so a `Filter::make('featured')->default()` comes back checked where a chip removal would have unchecked it.
The two are deliberately different operations.
Every other field clears to null, including one whose filter declares no `default()`, such as a `SelectFilter`'s `value`, because [a defaults fill nulls a defaultless component](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default).

### `updatedTableFilters()` fires for a whole-prop write, never for a field inside it

`$tableFilters` carries `onUpdated: 'updatedTableFilters'`, and it is easy to read that as *"any client write under this prop calls the hook"*.
It does not, and the live-mode page reset turns on the mechanism.

`LiveComponentHydrator::processOnUpdatedHook()` normalises a **string** hook to `[LiveProp::IDENTITY => $method]`, then walks the map.
The `IDENTITY` branch `continue`s unless `$dehydratedUpdatedProps->hasPropValue('tableFilters')`, and `DehydratedProps::hasPropValue()` is `array_key_exists($propName, $this->propValues)`, an exact key lookup with no prefix matching.
A client write to a field inside the bag arrives under its own key, `tableFilters.status.value`, which is not `tableFilters`, so the identity hook does not fire.
The array form of `onUpdated` is what covers nested paths, and it takes **fixed** path strings, which dynamic filter names, keyed by whatever the user called the filter, cannot supply.

So the hook covers exactly the direct whole-prop writes: a test helper's `set('tableFilters', [...])`, and the applying half's own PHP-side assignment, which is inside the port's code and calls `handleTableFilterUpdates()` outright rather than relying on a hydration hook to notice.
Live-mode per-field resets ride the schema's `afterStateUpdated` walk instead, the `-10` `PostHydrate` in [`HasSchemas`](../schemas/has-schemas.md), which is also why this trait rebuilds its cache-only form at priority `10`.

### The two filter bags round-trip differently, and the difference is the whole deferred/live split

Deferred is the default, and in that mode the filters schema is rooted at `tableDeferredFilters` while the query reads `tableFilters`.
A field write therefore lands in the draft bag and changes nothing: the rows, the page and the indicator chips all still describe the applied bag, and `applyTableFilters()` is the single copy that promotes draft to applied and runs `handleTableFilterUpdates()`.
There is no "unapplied changes" affordance, matching Filament: the trigger badge and the chips count applied indicators, and the apply button is always visible while deferring.
Filament's deferred branch also calls `Schema::partiallyRender()`, which stamps a `wire:partial` on the rendered schema so Livewire can re-render that subtree alone; there is nothing to port it to, because a ux-live-component request re-renders the whole component and idiomorph diffs the result, so the branch here is the `statePath()` and nothing more.

Live mode roots the schema at `tableFilters` itself with a root `->live()`, so a field write *is* the mutation.
Which mechanism carries the page reset and the flush depends on the write's shape, and the panel only ever sends one of them: a **nested** write at the field's own model fires no `onUpdated` hook at all (the identity hook is an exact key match, [above](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it)), so it reaches `handleTableFilterUpdates()` through the field's `afterStateUpdated` instead, which is why this trait rebuilds its cache-only form at `#[PostHydrate(priority: 10)]`, above the `-10` walk that fires those hooks.
A **whole-prop** write is the shape `updatedTableFilters()` exists for, and no rendered element produces it.

One shape is worth stating because nothing rejects it: a **whole-prop** write to either bag replaces it outright, and a filter whose key the replacement omits is handed an empty slice, which [`isActive`'s opt-out polarity](base-filter.md#isactive-is-an-opt-out-not-an-opt-in) reads as *active*, while the indicator gate's opposite polarity shows no chip for it.
The panel never produces that shape, because each field writes at its own `<bag>.<filter>.<field>` model and the `#[PostMount]` fill seeded every key before it; custom chrome that assigns the bag wholesale has to seed them itself.

The mirror image is a **nested** write straight at `tableFilters.<filter>.<field>` while deferring, equally unrejected and equally not a shape the shipped chrome emits, since the panel binds the deferred bag there; it reaches the applied bag having fired nothing at all: the identity `onUpdated` wants the exact `tableFilters` key, deferred mode attaches no `afterStateUpdated` to its fields, and the `-10` walk is watching a form rooted at `tableDeferredFilters`.
So the page never resets, the draft bag is never mirrored back from the write, and the next `applyTableFilters()` overwrites it with the stale draft.

### A seeded filter bag fills the form instead of the declared defaults

`mountTableFilters()` branches on whether the bag arrived with something in it, and the branch is Filament's exactly.
An **empty** bag reaches `fill()` as `null`, which runs `hydrateState(applyDefaults: true)` and seeds every filter's declared default.
A **non-empty** one, seeded from the query string, read back from the session, or handed in by a parent passing `tableFilters`, is normalised and then passed as `fill($seededFilters)`, which runs `hydrateState(applyDefaults: false)`, so the incoming bag is written as raw state and the schema's own defaults are skipped whole.
Filament reaches the same two arms from the other side: it nulls an empty bag first (`if (! count($this->tableFilters ?? [])) { $this->tableFilters = null; }`) and `Schema::fill(null)` is what applies its defaults.

The consequence is a **partial** seed dropping its siblings' defaults, and it is Filament-identical rather than a port artefact.
A table declaring a `featured` filter and a `plays` filter defaulted to `'100'` answers `?filters[featured][value]=null` with `plays` at null, not at `'100'`: one named filter in the URL turns the whole defaults pass off.
A user who wants both writes both into the URL, which is what the URL the table itself writes back always carries.

Normalisation is the second half.
A query string carries no types, so `normalizeTableFilterValuesFromQueryString()` walks the bag recursively and maps the three scalars that arrive as their own names, `'null'` → null, `'false'` → false, `'true'` → true, before the fill, because a `TernaryFilter` reading the **string** `'false'` would take its true arm.
Filament runs the same pass, in the same place, for the same reason (its `filamentphp/filament#7999` comment).
The pass runs on the bag whatever filled it, so a **session-restored** bag is normalised too: a text filter the session stored as the literal string `'true'` comes back typed `true`, in both codebases, and a session value round-trips differently from the value that was written.

### Record resolution skips a filter that opts out

`applyFiltersToTableQuery()` runs every filter's `applyToBaseQuery()` first and every filter's `apply()` second, over one builder.
Record resolution runs the same passes with `isResolvingRecord: true`, so a row action cannot reach a record outside the filtered set; a filter that opts out with `excludeWhenResolvingRecord()` is skipped in the apply pass of that resolution only, as in Filament.

## Divergences

### The filter bags are arrays, never null, and carry no getters

Filament types both filter props `?array` and defaults them to `null` (`public ?array $tableFilters = null;`), and its boot hook writes `null` back when the bag is empty.
The port's are `array` with an `[]` default, both of them.

The reason is the element on the other end.
The filters form binds a field to a model path like `tableFilters.status.value`, and the client resolves that path against the props payload on both reads *and* writes: `ValueStore.set()` calls `get()` first to compare, and `get()` falls through to `getDeepData()` unless a flat key of that exact dotted name is already sitting in the dirty, pending or server-sent props.
On the first render, where the server sent the bag under `tableFilters` and nothing has been written yet, none is.
`getDeepData()` guards `undefined` and only `undefined`; the walk beneath it is a bare `currentLevelData = currentLevelData[parts[i]]` loop.
So a `null` parent is not an empty object to descend into: it is `null['status']`, an uncaught `TypeError` in the browser, with nothing on the server to catch or report it.
The symptom is a field that silently stops working, not an error anyone can trace.
`[]` walks.

Filament gets away with `null` because Livewire's `data_get`/`data_set` pair tolerates it on both sides; ux-live-component has no such layer, so this is the ordinary Eloquent-to-Symfony shape of that difference rather than a judgment about which default reads better.
Filament's `null` buys no tri-state either: its boot hook collapses an empty bag to `null` before the session read ([`InteractsWithTable`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) at `:64-73`), so both codebases seed from emptiness, the port testing `=== []` where Filament tests `=== null`.

The second half is a rule rather than a type.
**There is no `getTableFilters()`, and there must never be one**, for either bag.
A `LiveProp` is de/hydrated through Symfony's `PropertyAccessor`, which prefers a same-named getter over the property, so a `getTableFilters()` would become what goes on the wire, and any value it returned that differs from the raw property leaves every element bound under that path permanently dirty, re-firing its update hook on every request.
The same mechanism is [load-bearing one way and a defect the other](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) for `$tableHostId` and the writable page props; the bags are the case where the answer is not to write the getter.
Reading a slice is what consumers want anyway, and that is what [`getTableFilterState()`](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) is for.

### One filter slice is a plain array read, not a `PropertyAccessor` walk

Filament reads a slice with `Arr::get($this->tableFilters, $this->parseTableFilterName($name))`, a dotted-path accessor.
The port's is `$this->tableFilters[$name] ?? null`, a single array read, and then checks the result is a state map before returning it.

The path lookup buys nothing here, because a filter name can never be dotted.
It is the key the table files the filter under *and* the state-path segment its `Group` binds to, so a dot in one would split the other; the accessor is for schema state paths resolved against the host object, which is a different job done by a different layer.
What the plain read costs is the `null` a missing key would have produced anyway, so the arms match.

The shape check is the part that is not just a simplification.
Both bags are **writable** `LiveProp`s, which ux-live-component exempts from the prop checksum by design: the client can put anything under any key, and a forged or merely stale payload can leave a string where a filter's state map should be.
Validating on read means the filter layer receives either a state map or `null`, never a scalar it will index into; the same posture the per-page getter takes, for the same reason.
The check is **field-keyed**, not merely `is_array()`: a slice whose keys are not all strings is a list the client put where a map belongs, which is not a state map either, so it reads as `null` too, as the declared `array<string, mixed>|null` return says.
A slice that reads as `null` reaches a filter as `[]`, through the same `?? []` a missing key takes.
`parseTableFilterName()` carries a smaller version of it: Filament's returns `$name::getDefaultName()` unguarded against a `string` return type, so a `BaseFilter` subclass that never declared a default name is a `TypeError` there, where the port falls back to the class-string with `?? $name`.

### The filter bag seeds itself from the query string, because UX refuses to

UX seeds every other URL-bound prop and refuses this one: it assigns a seeded value only when the prop's resolved type accepts it, and `property-info` reads `removeTableFilter()` as a *remover* for a `tableFilter` singular, inferring `list<string>` for the bag, which no filter state ever is.
So the bag has a query-string seed of its own, `mountTableFiltersFromQueryString()`, a `#[PostMount(priority: 20)]` above both the session hook and the fill.
That seed reads `->query->all()` off the **main** request only, so a `UrlMapping(mapPath: true)` on filters would bind nothing: a path-mapped filter bag is not a shape this port reads.

The hook branches on the parameter's **presence**, not on its value, and the difference is load-bearing.
An absent `?filters` is a no-op, so the session is free to seed the bag afterwards.
A **present but malformed** one, `?filters[]=abc`, where the bag is a list rather than filter-name-keyed, is not declined and left alone but assigned `[]`, because UX has *already* written the parameter onto the prop by the time this runs: returning would leave a list in a filter-name-keyed property, which then reads as a non-empty bag and suppresses every filter's declared default.
Emptying it puts the mount back on the `fill(null)` arm, so a hand-edited URL costs the user their filter *values* and not the table's own defaults.

### A reset state that names a field with `null` is honoured

Filament picks a field's reset value with `$filterResetState[$fieldName] ?? match (…)` ([`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) at `:103`), so a reset state that names a field **with a null value** falls through to the type-derived arm.
The port tests `array_key_exists()`, so the named `null` is written as named; that is what makes `SelectFilter`'s `['value' => null]` do what it says when the field's current state is a bool.

### `removeTableFilter()` carries no `$isRemovingAllFilters` parameter

Filament's `removeTableFilter()` takes a third parameter, `bool $isRemovingAllFilters = false`, which `removeTableFilters()` passes to borrow the body without the tail.
The port splits the body into the private `resetTableFilterState()` instead, so the public action's signature is exactly the two `#[LiveArg]`s a chip sends and no client can ask it to skip its own tail.

### The live-mode filter hook is a per-field `afterStateUpdated`, not the prop's `onUpdated`

Livewire calls `updatedTableFilters()` for a write to any path *inside* the bag: `SupportLifecycleHooks::update()` derives the hook name from the dotted path's head (`studly()->before('.')`) and hands the remainder over as a second argument, so Filament's live mode gets its page reset from that hook alone.
Ux-live-component's identity `onUpdated` fires only for a write to the whole prop ([the mechanism is in Semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it)), and its array form takes compile-time path strings where these are the user's filter names, so the same hook covers strictly less here and live mode needs a second path.

That path is the schema: the non-deferred branch of `getTableFiltersForm()` attaches `afterStateUpdated(fn () => $this->handleTableFilterUpdates())` to every `Field` in the form, and `HasSchemas::callAfterSchemaStateUpdated()`, the `#[PostHydrate(priority: -10)]` pass that diffs `$oldState` against the hydrated state, fires them for exactly the fields that changed.
The filters form is in `getCachedSchemas()` by then because [the re-cache hook runs at priority `10`](../schemas/has-schemas.md).
Deferred mode attaches nothing, since a draft edit is meant not to touch the query.

`updatedTableFilters()` is kept: it is the whole-bag write path (a server-side assignment, a test, a `data-model="tableFilters"` binding), and it is what mirrors an applied write back into the deferred bag.
It also returns early when the bag is unchanged, a `$previousValue` guard Filament's hook does not carry.
