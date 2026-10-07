---
description: The column's cell state — the record read by dotted path, the per-record cache, the default, the split and the distinct list.
ports:
  - vendor/filament/support/src/Concerns/HasCellState.php
owns:
  - packages/tables/src/Columns/Concerns/HasCellState.php
---
# HasCellState (tables)

The relationship members of Filament's trait are [column.md](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member)'s row.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`state()` / `getStateUsing()` / `getState()` — cached per record key](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#hascellstate-is-relocated-into-the-column-tier) · [diverges](#the-state-cache-keys-off-the-bound-record-key) · [semantics](#state-is-getstateusing-under-another-name) |
| [`getStateFromRecord()` — object graph or array, by dotted path, fanning out over a collection](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`HasCellState::getStateFromRecord()`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#the-object-read-is-lenient-where-the-accessor-is-strict) · [diverges](#a-to-one-chain-keeps-the-raw-read-where-filaments-relationship-arm-drops-blanks) · [diverges](#the-list-shape-is-metadata-derived-where-filaments-is-cached-off-the-first-row) |
| [`default()` / `getDefaultState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [semantics](#the-default-and-the-split-run-inside-the-per-record-cache-in-that-order) |
| [`separator()` / `getSeparator()` — split a string into a list](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [semantics](#the-default-and-the-split-run-inside-the-per-record-cache-in-that-order) |
| [`distinctList()` / `isDistinctList()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [semantics](#distinctlist-dedupes-entity-values-by-value-not-by-identity) |
| [`clearCachedState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | done | — | [diverges](#the-state-cache-keys-off-the-bound-record-key) |
| `getGetStateUsingCallback()` | [`Support\Concerns\HasCellState`](../../../vendor/filament/support/src/Concerns/HasCellState.php) | skipped | — | [diverges](#the-raw-state-callback-has-no-getter-because-validation-reads-the-input-directly) |

## Semantics

### `state()` is `getStateUsing()` under another name

`state($x)` delegates straight to `getStateUsing($x)` — one property, one evaluation path — so the two names differ only in how they read at a call site, not in what they do.
Both accept a literal *or* a closure, and `evaluate()` returns a non-closure untouched: `->state('—')` pins every cell to a literal, and `->state(fn ($record) => …)` computes per record.
The reason both exist is Filament's, and it is ergonomic: `state()` reads as *this is the value* and `getStateUsing()` as *this is how to get the value*, which is the right phrasing depending on whether you are passing a literal or a callback.

The consequence to keep in mind is that they **overwrite each other** — the last call wins, and a column with `getStateUsing()` set never touches its record.
Once either is set, `getStateFromRecord()` is not called at all, so the column name stops being a read path and remains only a key and a label source.

### The default and the split run inside the per-record cache, in that order

`getState()` caches one value per record key, and both `separator()` and `default()` run **inside** that cache rather than around it — so a `default()` closure is evaluated once per record, not once per read, and a template asking for the state three times gets one evaluation.

The order is Filament's and it matters both ways.
The split runs first, so a string state becomes a list before the blank test sees it — which is how a column whose whole value is `''` or `'   '` reaches `default()` at all: `explode()` yields one blank part, that single-blank case collapses to `[]`, and `[]` is blank.
Reversing the two would leave `['']` — filled, list-shaped, and un-defaultable.
The blank test runs second and reads the *post-split* value, so `default(['none'])` fills an emptied list exactly as `default('—')` fills an unset scalar.

Two edges are worth naming because a reader reaches for the wrong one.
The separator is read through `evaluate()` and then tested for **truthiness**, so `separator(null)` and `separator('')` both mean *do not split* rather than *split on nothing* — an empty separator would make `explode()` throw.
And the blank test is [`blank()`](../support/helpers.md), not falsiness: a state of `0`, `'0'` or `false` is filled and survives, where a naive `?:` would replace it with the default.

The single-blank collapse spells `mb_trim($parts[0]) === ''` rather than calling `blank()` on it, and that is a static-analysis constraint rather than a preference: `blank()` carries `@phpstan-assert-if-false !null $value`, so against an argument PHPStan already knows is a `string` the true branch is unreachable and the call is reported `function.impossibleType` at level max.
`explode()` returns `list<string>`, which is exactly that case.
Other `src/` sites use the same spelling for the same reason; widening the assertion is support's to do, and while it stands a statically-known string cannot be passed to `blank()` anywhere in the toolkit.

### `distinctList()` dedupes entity values by value, not by identity

`distinctList()` on a fanned-out path runs `array_unique($values, SORT_REGULAR)`, which compares with `==`.
For the ordinary case — a list of strings pulled off a collection — that is exactly what it reads as.
For a path that ends **at** an association, where the values are entities rather than scalars, `==` on two objects of the same class compares their properties one by one, so two distinct rows that happen to carry equal field values collapse into one entry while `===` would have kept both.

It is Filament's behaviour too, by the very same call: `Collection::unique()` with no key and its default `$strict = false` is `array_unique($this->items, SORT_REGULAR)`.
So neither codebase dedupes by identity here, and the note exists because the code says `SORT_REGULAR` — a flag that reads like "the default, nothing to see" and is in fact the whole semantics.
`SORT_STRING` (PHP's actual default for `array_unique()`) would be worse, not stricter: it would stringify the entities first and throw on any that are not `Stringable`.

## Divergences

### The raw state callback has no getter, because validation reads the input directly

Filament's one reader of `getGetStateUsingCallback()` is `Columns\Concerns\CanBeValidated::validate()`, which swaps the column's state for the input while the validator runs and restores the callback afterwards.
The port's [`CanBeValidated::validate()`](../../../packages/tables/src/Columns/Concerns/CanBeValidated.php) hands the input straight to the validator without that swap, so nothing would read the getter and it is not ported.
A consumer that needs the raw callback would reverse this.

### `HasCellState` is relocated into the column tier

Filament hosts `HasCellState` in **support** (`Filament\Support\Concerns\HasCellState`), where two classes in two packages compose it — `Tables\Columns\Column` and `Actions\Exports\ExportColumn`.
Sylament files it at [`Sylament\Tables\Columns\Concerns\HasCellState`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) instead.
The class name, method names and signatures are unchanged, so this is a relocation rather than a [rename](../renames.md).

Layering forces it.
The state read goes through schemas' [`PropertyAccessor`](../schemas/property-accessor.md) — the same one the form fields read and write through, and the only dotted-path reader in the port — and `support` sits *below* `schemas`, so a trait in support cannot import it.
The alternatives were both worse: pushing `PropertyAccessor` down into support would drag `symfony/property-info` and `symfony/type-info` into the base package for one consumer, and a support-hosted trait calling an abstract hook the column implements would spread one read across two packages to preserve a directory.

This is provisional in exactly one way.
Filament's placement pays off for its *second* consumer, `Actions\Exports\ExportColumn`, which is unported.
Porting it moves the trait to the lowest package both consumers reach, which is `schemas` (both `tables` and `actions` require it) and still not `support`, because the accessor it reads through lives in schemas.
[`CanAggregateRelatedRecords`](can-aggregate-related-records.md) is relocated for the same reason and moves with it ([translations.md](../translations.md#hascellstate-and-canaggregaterelatedrecords-move-up-into-tables)).

### The state cache keys off the bound record key

Filament wraps the whole of `getState()` in `cacheState()`, which works the key out for itself: a `Column` asks the Livewire host (`$this->getLivewire()->getTableRecordKey($record)`), anything else reads `ArrayRecord::getKeyName()` off an array record or `getKey()` off a model.
Here [`getState()`](../../../packages/tables/src/Columns/Concerns/HasCellState.php) caches under `getRecordKey()` — the key the table already bound to the column alongside its record — and `resolveState()` holds the body Filament passes to `cacheState()` as a closure.
Nothing is derived at read time and the host is never called back into — a keyed column reads a given record once however it was reached.

The visible consequence is a column nobody keyed: `getRecordKey()` is null, so `getState()` resolves fresh on every call instead of memoising.
That is the point of keying off the binding rather than the record — a column built by hand and handed one record after another (what every unit test does, and what a column used outside a table is) must see each one, where a derived key would have memoised the first read against a record the caller has since replaced.
Inside a table the case cannot arise: the row loop binds record and key together, so every rendered cell is keyed.

Two of Filament's guards are not ported with it.
`cacheState()` returns `null` outright when the column has no record, which would make `->state('—')` or a record-free closure resolve to null on an unbound column; `getState()` here consults the record only if something reads it, so a literal or a closure still resolves.
And the key test is `=== null` where Filament's is `blank()`, so an empty-string key caches here rather than reading fresh.
Only an array record handed an explicit empty `__key` can produce one — [`HasTable::getTableRecordKey()`](../../../packages/tables/src/Concerns/HasTable.php) rejects a blank key on an object record outright.

### The object read is lenient where the accessor is strict

`PropertyAccessor::getFromRecord()` lets an accessor failure on an object hop (no public property, no getter, an uninitialized typed property) propagate as `Symfony\Component\PropertyAccess\Exception\AccessException`, deliberately, so a caller can judge it.
`getStateFromRecord()` judges it by returning `null`.

That is the semantics Filament has, arrived at differently: its read is `data_get($record, $name)`, which yields `null` for anything it cannot reach and never throws.
A column is display-only and is handed whatever the host's data source produced, so a typo'd name, a heterogeneous list, or a row missing an optional relation must render an empty cell — a table that 500s because one row of two hundred lacks a property is the wrong failure.
It matches the read-lenient arm schemas already ships in [`BelongsToRecord::fill()`](../schemas/belongs-to-record.md), for the same reason.

The strictness is not lost, it is placed: the **write** side (`setOnRecord()`) throws, because writing to a path nobody can reach is a bug with no sensible fallback.
Only the array arm is naturally lenient already — `getFromArray()` returns null for an absent key — so only the object arm needs the `catch`.
The same leniency covers every hop of a dotted walk: a segment the record cannot answer ends that branch with no value rather than raising, which is what lets one row of a fanned-out collection be unreadable without taking the other items with it.

### A to-one chain keeps the raw read, where Filament's relationship arm drops blanks

Filament routes **every** relationship-headed name through one arm: the results are collected, `blank()` entries are dropped, and a survivor count below two returns the first value.
A to-one relationship yields exactly one result, so `artist.name` over an artist whose name is `''` collects one blank, drops it, counts zero and answers `null`.
Here a chain of to-one hops is read straight — `''` stays `''` — and only a path that actually crosses a `Collection` gets the drop-blanks-then-shape treatment.

The divergence is narrow and it is deliberate: a to-one hop has no list to compact, so dropping a blank there is not list hygiene but a **value rewrite**, and the one thing it changes is which of `''` and `null` a formatter or a `default()` sees.
Filament can afford it because `getState()` immediately maps `blank($state)` onto `getDefaultState()` anyway, so the two collapse over there whenever a default is declared.

`getState()` here runs the same blank-to-default step, so `''` and `null` collapse in the state exactly as they do upstream, and the two ports answer alike at the place a caller reads.
What differs is one level down: `getStateFromRecord()` returns `''` where Filament's relationship arm returns `null`.

### The list shape is metadata-derived, where Filament's is cached off the first row

Whether a cell answers a scalar or a list must be **stable across rows** — a column rendering `Alice` on one row and `[Alice]` on the next breaks any formatter that type-tests, and `TextColumn` badges a list where it prints a scalar.
Filament decides it per column with `hasMultipleRelationship()`, which walks the relationship values of the record **in hand** and memoises the answer: whichever row is rendered first decides the shape for the whole table.
That is row-order-dependent by construction — a first row whose to-one hop is `null` never reaches the collection behind it, so `false` is cached and every later row is asked for a single value.

Here the question is asked of the **mapping** instead: `isCollectionValuedAssociation()` per segment, from the table's record class through `getQueryEntityManager()`, so the answer is a property of the declaration and no row can shift it.
The cache is still per column, but what it caches cannot depend on which record arrived first.
A path with no mapping to consult — the records lane, a DTO, a plain array — falls back to the **value**-derived answer (did this walk cross a collection), which is the only thing there is to ask there, and is why the fallback is not a defect: without an entity manager the shape is per row by necessity.

The cost is one metadata read per column per request, memoised in `$cachedPathTraversesToMany`; the gain is that the null-first-row case — the one a demo dataset produces the moment an optional relation exists — cannot silently change a column's type.
