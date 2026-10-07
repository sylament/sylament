---
description: 'The record-key codec and identifier predicate builder: `RecordKey`, `IdentifierConstraint` and `KeySelection` over the internal `IdentifierFields`.'
ports: []
owns:
  - packages/schemas/src/Identity/
---
# RecordKey (schemas)

[`Sylament\Schemas\Identity\RecordKey`](../../../packages/schemas/src/Identity/RecordKey.php), [`IdentifierConstraint`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) and [`KeySelection`](../../../packages/schemas/src/Identity/KeySelection.php), over the `@internal` per-class descriptor [`IdentifierFields`](../../../packages/schemas/src/Identity/IdentifierFields.php), are a Sylament invention with **no Filament source**.
Eloquent has no composite-key vocabulary at all: `Model::getKeyName()` is one string, `getKey()` reads one attribute, and `Builder::whereKey(array)` means *many keys*, never one tuple — so Filament's `getRecordKey(Model | array $record): string` inherits a single-column world and there is nothing over there to diff against.

**A record key is one string everywhere it travels.**
That is the whole stance, and it is what these three classes exist to make true: the codec turns a record — or a raw identifier tuple read back off a result row — into one string and back again, and the predicate builder replaces every single-column comparison with its per-field form so a query can be narrowed, correlated, projected, grouped and ordered by an identifier of any width.
Both are **static, with the entity manager passed per call**, in [`SearchConstraint`](search-constraint.md)'s shape: no service, no trait, no DI, so a component, a table host, a resource and a bulk action all reach them the same way.

**Both flavours, every role, parity by capability.**
A composite identifier is either several scalar `#[Id]` fields or a **derived identity** — `#[Id]` on one or more to-one associations, alone or beside scalar fields — and a record keyed either way works wherever a single-`id` entity does: a table row with its selection, reorder and bulk delete; a resource record, its `{record}` URL and global search; a relation target and a relation owner; and the create and edit paths.
The bar is capability, not a feature list: every feature that works for a single-identifier entity, and is possible for a composite one, works for a composite one, and what Doctrine itself makes impossible is recorded as a divergence in the file of the thing it constrains — [below](#divergences) for the key itself — never worked around and never left as a silent gap.
There is no Filament surface to mirror and none to break, so every public signature that speaks a record key — `getTableRecordKey(object|array $record): string`, the `{record}` route parameter, an option value — keeps its single-identifier shape, and the composite support lives behind it.

The interface consumers build against is [the contract](record-key-contract.md#contract), and each row below links its part.
Panels' custom `$recordRouteKeyName` arm needs a typed bind with a print-back compare against a *named column*, which no operation here exposes ([why it is open](../panels/resource.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`RecordKey::of()`](../../../packages/schemas/src/Identity/RecordKey.php) — a record to its key, throwing | — | done | — | [contract](record-key-contract.md#the-api) · [semantics](#find-answers-identity-not-persistence) · [semantics](#a-key-is-minted-from-the-value-its-row-would-read-back) |
| [`RecordKey::find()`](../../../packages/schemas/src/Identity/RecordKey.php) — `null` while incomplete | — | done | — | [semantics](#find-answers-identity-not-persistence) · [semantics](#a-key-is-minted-from-the-value-its-row-would-read-back) |
| [`RecordKey::fromValues()`](../../../packages/schemas/src/Identity/RecordKey.php) — PHP-form values to a key | — | done | — | [semantics](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may) |
| [`RecordKey::toIdentifier()`](../../../packages/schemas/src/Identity/RecordKey.php) — a key to `find()`'s map | — | done | — | [contract](record-key-contract.md#the-grammar) · [semantics](#a-malformed-key-never-reaches-the-database) |
| [`RecordKey::values()`](../../../packages/schemas/src/Identity/RecordKey.php) (`@internal`) | — | done | — | [contract](record-key-contract.md#the-api) |
| [`IdentifierConstraint::equals()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) / `notEquals()` | — | done | — | [contract](record-key-contract.md#the-operations-and-their-dql) |
| [`IdentifierConstraint::in()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) / `notIn()` | — | done | — | [contract](record-key-contract.md#the-operations-and-their-dql) · [semantics](#the-batch-is-a-balanced-or-tree-because-sqlite-counts-expression-depth-not-terms) |
| [`IdentifierConstraint::inColumns()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — `in()` in SQL | — | done | — | [contract](record-key-contract.md#incolumns-is-in-in-sql-for-a-statement-that-names-one-table) |
| [`IdentifierConstraint::correlate()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) | — | done | — | [contract](record-key-contract.md#the-operations-and-their-dql) · [diverges](#comparing-two-identification-variables-compares-the-first-identifier-column-only) |
| [`IdentifierConstraint::select()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) + [`KeySelection`](../../../packages/schemas/src/Identity/KeySelection.php) `keyOf()` / `keyOfHydrated()` | — | done | — | [contract](record-key-contract.md#the-operations-and-their-dql) · [semantics](#the-row-side-and-the-record-side-mint-one-string-and-only-the-codec-may) · [semantics](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time) |
| [`IdentifierConstraint::groupBy()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — bare paths | — | done | — | [diverges](#bare-identitypath-on-a-multi-column-association-answers-only-the-first-column) |
| [`IdentifierConstraint::orderBy()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) — the identifier tiebreak | — | done | — | [contract](record-key-contract.md#the-operations-and-their-dql) |
| [`IdentifierConstraint::chunk()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) | — | done | — | [contract](record-key-contract.md#the-chunk-rule) |
| [`IdentifierConstraint::batch()`](../../../packages/schemas/src/Identity/IdentifierConstraint.php) | — | done | — | [contract](record-key-contract.md#the-chunk-rule) |
| Owner scoping — `scopeToOwner()` / the owner tuple | — | skipped | — | [diverges](#there-is-no-owner-scoping-operation-because-one-equals-on-the-owners-own-collection-scopes-an-owner-of-any-width) |
| [`IdentifierFields`](../../../packages/schemas/src/Identity/IdentifierFields.php) (`@internal`) | — | done | — | [diverges](#identifier-order-is-declaration-order-so-reordering-two-id-properties-invalidates-every-minted-key) |

## Semantics

### `find()` answers identity, not persistence

`RecordKey::find()` asks "does this record carry a **complete** identifier", and that is not the same question as "is it persisted".
With a generated id the two coincide; with an **assigned** one — which is every composite key, since Doctrine refuses an id generator on a composite identifier — they part company: a record whose fields the user has filled in is keyable before the first `flush()`, and `find()` answers its key.
A caller that needs persistence asks the `UnitOfWork`.

A component that is `null` **or still blank** — a whitespace-only string, or a `Stringable` that prints as one, as support's `blank()` decides — counts as not assigned yet, and `find()` answers `null` for it: an assigned string key sits at `''` until a form fills it, and a create form's `unique(ignoreRecord:)` must be able to ask without a throw.
`of()` draws the line differently on purpose and keeps both refusals a table has always raised — `has none set` for a missing component, `is not a printable value` for a blank or unprintable one — because a row index cannot proceed without a key.

Testing `is_string()` first would let a value object printing blank — a slug object whose constructor defaults to `''` — reach the printer and throw *"is not a printable value"* out of `find()`, where a blank string answers `null`.

Completeness is **counted**, never tested for emptiness, and this is the trap the method exists to close.
`ClassMetadata::getIdentifierValues()` **drops** a null component on the composite branch rather than returning it, so an `=== []` test is correct for one field and wrong for two — a half-filled pair would read as complete.
The count is compared against `count($metadata->identifier)` **at every level**: an association component is flattened by recursing into the target's own identifier, which counts again there, so a derived identity whose target is itself keyed by an association is complete only if the whole chain is.
That recursion is the codec's own — one private method that also subsumes the completeness count and needs no unit of work; see [the divergence](#doctrines-identifierflattener-is-not-adopted-because-it-misreads-a-chained-derived-identity) for why Doctrine's `IdentifierFlattener` is not what runs here.

The two refusals name **different classes**, which is deliberate rather than an oversight: `has none set` names `$record::class`, because the record that turned up without an identifier is what a reader has to find, while `is not a printable value` names the **declared** class the key was being minted for, since that is the mapping whose field could not be printed.
On the common path they are the same string; they part only for the unmapped-subclass case the third argument exists for.

### The row side and the record side mint one string, and only the codec may

`KeySelection`'s readers and `RecordKey::of()` must produce the same string for the same row, or a store filled under row keys is read by cells asking for record keys and every cell quietly renders its default.
They are the same function by construction: a reader normalises its row's values and hands them to `IdentifierFields::toKey()`, which is the one print step `RecordKey::fromValues()` calls too — so `of()` reaches it after flattening, and neither side owns a copy.

The normalisation is where a hand-rolled copy would go wrong, and the rule is **one rule, not a table of special cases**: every value the row hands back still raw is run through its field's own DBAL type, once, before it is printed.
Which values are raw is the hydrator's answer, and **no hydrator converts every key column and the hydrators disagree about which ones they do**.
The scalar hydrator converts *none* — an aliased select is `isScalar`, and `AbstractHydrator::gatherScalarRowData()` skips both the DBAL type and `enumType` for those under a documented `// WARNING: BC break!` decision (`vendor/doctrine/orm/src/Internal/Hydration/AbstractHydrator.php:422-424`).
The array hydrator converts a *field path* but not an `IDENTITY()` expression, which `SqlWalker` registers as a plain `'string'` (`vendor/doctrine/orm/src/Query/SqlWalker.php:1370`, against `:1315` for a field path).
So a `Handle`-keyed `Venue` reached through `IDENTITY()` arrives raw under **both** hydrations, a `BackedEnum` arrives as an *instance* under the array hydrator and as its **backing value** under the scalar one, and converting only the association arm of a scalar row mints a different string on the row side than the record side mints.
So `keyOf()`, over a scalar row, converts every value, and `keyOfHydrated()`, over an array row, converts the association halves alone ([the second reader](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)).
A value the hydrator already built into an **object** is the one thing left alone, and it needs nothing: the printable rule unwraps a `BackedEnum` to its backing value and a `Stringable` to its string, which is exactly what the record side does with the same object.
The rule that falls out: a row-to-key decode is **called**, never re-derived at a call site.

### A hydrated row is keyed without reading its fields a second time

`keyOf()` converts every non-object value — sound over a scalar row, and over an array row only while reading a value twice changes nothing.
That holds for every DBAL built-in and every object-returning type, and fails for a scalar type whose reader is not idempotent: the tests' `HexCodeType` stores an integer and reads it as its lower-case hex spelling, taking whatever it is handed as a decimal number, so the array hydrator reads the column's 255 as `ff` and a second read turns `ff` into `0` — a key no record mints, with no error anywhere — while the strict `MarkedCodeType`, which stores `m:<value>` and refuses an unprefixed one, throws a `ConversionException` on the second read.
Four consumers read such rows: actions' attach and associate option projections and tables' aggregate batch read `getArrayResult()`, and tables' two-step page reads the rows Doctrine's `LimitSubqueryOutputWalker` answers, whose result-set mapping converts a field column regardless and leaves an association column raw.
So through `keyOf()` alone a lenient type would offer options under `0`, fill aggregate cells under keys no cell asks for, and page an empty window; a strict one would make the option read and the page throw.

`keyOfHydrated()` is the reader for those rows: it takes every field value as the hydrator handed it and converts only the indexes `IdentifierFields` marks as associations, since an `IDENTITY()` half arrives raw under every hydrator.
The four consumers read through it, and tables' by-key selection pluck, which reads `getScalarResult()`, keeps `keyOf()`.
A converting association half stays covered — `ListingNote`, keyed through `IDENTITY()` onto a `Sku`, keys from an array row as its record does.
Pinned in [`KeySelectionTest`](../../../packages/schemas/tests/Unit/Identity/KeySelectionTest.php) over both types at both widths, and at the four consumers in tables' [`AggregateQueryTest`](../../../packages/tables/tests/Unit/Query/AggregateQueryTest.php), [`FetchJoinPageQueryTest`](../../../packages/tables/tests/Unit/Query/FetchJoinPageQueryTest.php) and [`HexKeyAttachAssociateActionsTest`](../../../packages/tables/tests/Integration/HexKeyAttachAssociateActionsTest.php).

### A malformed key never reaches the database

A key is client input, and the guard is a strict decode *before anything is bound* — not the typed bind, which is only the second half.
The typed bind alone does not close the hole: on PostgreSQL 18 a `'5abc'` bound `PARAM_INT` raises `22P02` under native prepares and, under emulated prepares, **matches record 5**.
And a `DriverException` is not an `HttpExceptionInterface`, so `PageController::__invoke()`'s render catch, which rethrows a Twig `RuntimeError`'s previous exception only when it is an `HttpExceptionInterface`, does not convert it — the render ends in a **500**.

That 500 is live wherever a client-supplied key is bound raw, and naming those sites here is the point.
`Resource::resolveRecordRouteBinding()` routes its default arm through this decode, so `/admin/articles/abc/edit` is a 404 that runs no statement and `/admin/articles/05/edit` does not resolve article 5 — while its `$recordRouteKeyName` arm binds one named column raw and [keeps the 500](../panels/resource.md#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it).
The relation layer decodes too: a relationship `Select`'s selected-record and option-label lookups and `AttachAction`, `AssociateAction` and `DissociateBulkAction`'s key `IN` all take client state, and all of them decode before they bind, while `Select::relationship(ignoreRecord:)` and `unique(ignoreRecord:)` bind a server-derived identifier, so those two convert for composite correctness rather than for the hole.
The **table** lane — row resolution, editable cells, selection, reorder and the by-key delete — and panels' default route arm decode too, so the one client-supplied key bound raw is `Resource`'s `$recordRouteKeyName` arm, deliberately.

So `equals()` on a malformed key returns `null` and binds nothing, and the caller's contract is to answer "no such record" **without querying**.
This is pinned by asserting the refusal — that no statement was executed, through a recording middleware — rather than by asserting a platform's leniency, which differs per driver and per prepare mode.

### The batch is a balanced OR tree because SQLite counts expression depth, not terms

`in()` over K composite keys emits K parenthesised AND groups, nested as a **balanced binary tree** rather than chained left to right.
A flat chain is an expression nested K levels deep, and SQLite's `MAX_EXPR_DEPTH` is 1 000, so it refuses at ~997 terms regardless of how many fields each key has — long before any bind-parameter limit is in sight.
Nesting makes the depth `log2(K)`, and 1 501 keys execute.
The tree is invisible to the planner on every platform measured and costs nothing; the reason it looks over-engineered is that the constraint it answers is not the one a reader assumes.

### A key is minted from the value its row would read back

`of()` and `find()` hand their flattened values to `fromValues()` through a private `asStored()`: per component, `Type::getType($name)->convertToPHPValue($type->convertToDatabaseValue($value, $platform), $platform)`, typed by `IdentifierFields::of()->types`.
Two conversions per component per mint, none for a blank component, which is handed back as it is, and no statement.

Printing the value the record holds in memory is not enough, because for a record the request has just written that is not always the value its row holds.
Under a type whose writer rewrites what was typed — the panels fixture's `Stamp` code type hands `new/1` back as `NEW/1` — a freshly created record would be keyed `1~new=2F1`, a key [`toIdentifier()`](#a-malformed-key-never-reaches-the-database) refuses, since `new/1` does not print back to itself through that type.
Two defects follow, and neither depends on the key's width — a **single** assigned identifier under such a type meets both: `CreateRecord`'s redirect lands on a 404 for a record that exists under `1~NEW=2F1`, and a relationship `Select`'s created option is named by a key its option list does not hold, so nothing is selected and the parent's save refuses *"The value you selected is not a valid choice."*.
Both remedies at the call site were measured and lost: `refresh()` registers a **second** managed instance and leaves the key where it was, and `detach()` plus `find()` costs a `SELECT` and detaches the record the page just created.

A loaded record's key does not move — it already holds `convertToPHPValue()` of its row, and the round trip is idempotent for a well-behaved type — which [`RecordKeyTest`](../../../packages/schemas/tests/Unit/Identity/RecordKeyTest.php) pins over every converting fixture type, a raw-bytes uuid, a cipher, a decimal and a boolean.
A **new** record's key is normalised through its type: an integer half typed `'0007'` mints `…~7`, the key its row will answer to, through `of()` and `find()` alike.
`fromValues()` converts nothing — it prints what it is handed, values already in their PHP form — and `toIdentifier()` decodes no spelling the type would rewrite.
And a blank component is no key at all: `find()` reads it **before** its type converts it and answers `null`, and `asStored()` hands a blank component — whitespace alone, or a `Stringable` printing that — back unconverted, so `of()` refuses it with its documented *"… is not a printable value."*.
That pass-through is load-bearing rather than tidy: a type's reader may assert what it reads — DBAL's `BigIntType` asserts a numeric value — and an unguarded round trip would turn `of()`'s refusal of a blank `bigint` half into an `AssertionError`, or a `TypeError` with assertions off.

Two faces of a new record are not pinned.
A **non-numeric** string in an integer-typed key half (which only an untyped or `int|string` property can hold) raises that type's own error from `of()` and `find()` — `bigint`'s `AssertionError` under `zend.assertions=1`, a `TypeError` with assertions off, which is why no test pins the message — or, under `integer` or `smallint`, whose readers answer `0`, mints `…0`.
The `bigint` face was executed through the mint, on the one fixture property that can hold such a string; the `integer` and `smallint` face at the type level alone.
And a value its type's **writer** refuses, on a new record, throws that type's `ConversionException` from the mint.
No fixture property can hold such a value, so no test reaches it.

### The shapes the fixtures pin

Every identifier shape the codec supports by construction has a fixture and runs through the whole battery ([`KeyShapesTest`](../../../packages/schemas/tests/Unit/Identity/KeyShapesTest.php), [`AssociationReaderKeyShapesTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderKeyShapesTest.php)) on SQLite, MySQL and PostgreSQL.
The battery covers the mint, the decode, `equals()`, `in()`, `correlate()`, the key selection with `keyOf()`, group-by, order-by, a reference — which costs no statement for a scalar-keyed shape and one load for a key with an association, [the reader's route](association-reader.md#getreference-decodes-first-and-then-picks-its-route) — the taken key on create and the changed key on edit.

- **Three scalar fields.**
  Chunked at 500, the same as two.
- **An association in the middle of the key.**
- **An int-backed enum half.**
  It prints its backing value.
- **An id declared on a mapped superclass.**
- **A JOINED hierarchy under a composite root.**
- **A single `#[Id] ManyToOne`.**
- **A smallint beside an ascii_string.**
- **Symfony's own `UuidType` and `UlidType`.**
  They are `BINARY(16)` on SQLite and MySQL and a native `uuid` on PostgreSQL, and a key prints the value's canonical string.

A NUL byte never reaches PostgreSQL as part of a key.
The decoder refuses one on every platform, and pdo_pgsql would truncate it silently in a bound string.
The two tests about a NUL-byte identifier are therefore restricted to SQLite and MySQL.

### A property test carries the fuzz harness's claims

`RecordKeyPropertyTest` runs a seeded, deterministic slice of the validation's fuzz harness on every platform the suites run on.
It checks four properties:

1. **Round trip.**
   A generated tuple's key decodes to the same printed values and re-encodes to itself, and a record holding the raw values mints the key its row binds.
2. **Only canonical spellings decode.**
   Every misspelling the harness generates is refused, or is itself the canonical key of another tuple, and `equals()` agrees with the decode.
3. **A single identifier keys verbatim.**
4. **Totality.**
   No client string makes the decode or a constraint builder throw, nor the lookup that follows a decode.

The cipher classes are left out by name: their type refuses its own printed form.
`SYLAMENT_PROPERTY_SEED` and `SYLAMENT_PROPERTY_ITERATIONS` widen a local run.

On PostgreSQL, property 1 leaves out a tuple whose `guid` component is not a UUID, because a native `uuid` column cannot hold one and [the grammar](record-key-contract.md#the-grammar) shape-checks `guid` there; a pin in the same file shows `'not-a-uuid'` decoding on SQLite and MySQL and refused on PostgreSQL.
On PostgreSQL, property 4 also skips the lookup for an integer wider than its column, which raises [the platform's range error](#an-integer-beyond-the-columns-width-still-raises-the-platforms-range-error) there and names no row on the other two, as a second pin shows.

### A mutation gate holds the codec

`composer test:mutation` runs Pest's mutation testing over every file in `packages/schemas/src/Identity`, against the schemas suite.
CI runs it on every pull request and push to `main`, and a score under the committed `--min` blocks the split.
Every mutant a test can tell from the original is killed by a named test in its file's own test.
No mutant is equivalent: the table of the ones no input can tell apart is empty.
`--min` is 98.6, not 100, because of four mutants the gate never runs a test against:

| File:line | Mutator | Why the gate cannot run a test against it |
| --- | --- | --- |
| `IdentifierConstraint.php:20` | `DecrementInteger`, `IncrementInteger` | `MAX_PARAMETERS_PER_STATEMENT` is a class constant, and pcov records no executed line for a constant's declaration, so the plugin finds no covering test and reports it uncovered. |
| `IdentifierConstraint.php:22` | `DecrementInteger`, `IncrementInteger` | The same, for `MAX_COMPOSITE_KEYS_PER_STATEMENT`. |

Those four are not equivalent, and the suite does kill them.
Run by hand, each fails `IdentifierConstraintTest`'s *chunks a key list to what one statement carries*, and the two on line 22 also fail *batches a composite key list by the chunk rule and a single one whole*.
So a changed statement limit still fails the schemas suite, just not this gate.

A fault in the plugin's own swap makes a mutant escape; it never kills one.
The plugin swaps the mutated file in through a `file` stream wrapper that stays unregistered if a wrapped file operation throws, so a test process can end up running the original file, and a mutant it should kill then survives.

## Divergences

### An identifier association always resolves to exactly one join column

That is the load-bearing fact, and it is worth stating more carefully than "a composite-key entity cannot be part of another entity's identifier", which is what a reader assumes and is not quite what Doctrine enforces.
Mapping validation refuses only a **declared** identifier association carrying two or more join columns (`ClassMetadata`'s `cannotMapCompositePrimaryKeyEntitiesAsForeignId`).
An identifier association onto a composite-key target whose join columns are **auto-derived** builds happily — the mapping ends up with one bogus join column — and then fails later and elsewhere: in `SchemaTool`, and in `getSingleIdentifierFieldName()` the moment anything asks the target for its one identifier field.
So the guarantee holds in practice through two mechanisms rather than one, and the codec adds a third of its own: flattening an association component that resolves to more than one value throws a `LogicException` naming the property, so the truncation can never happen silently whatever order the checks fire in.

The fact is why `alias.assoc = :p` is legal DQL on the record's own query, why `IDENTITY()` in a projection is unambiguous, and why the codec never needs a nested tuple.
If it ever weakened, every one of those would need revisiting.

The codec's `LogicException` above is the only refusal in shipped code that turns a record away for its identifier's width.

### Doctrine's single-identifier API refuses a composite identifier, so nothing here calls it on one

Every single-identifier accessor throws on a composite class: `ClassMetadata::getSingleIdentifierFieldName()`, `getSingleIdentifierColumnName()` and `getSingleIdPropertyAccessor()`, and `UnitOfWork::getSingleIdentifierValue()`.
Binding a composite-key **entity** as a query parameter goes through the last of those and throws too, which is why [the owner bind passes identifier values rather than the owner](relationship.md#a-single-identifier-owner-binds-its-identifier-value-typed-by-the-fields-own-dbal-type); comparing a single-valued association path to a composite-key target (`x.item = …`) throws; and the entity persister's own criteria `IN` refuses a multi-column association with `CantUseInOperatorOnCompositeKeys` — the persister's limit, beside DQL's own: the builder's `in()` [writes an OR of per-field ANDs](record-key-contract.md#the-operations-and-their-dql) because DQL has no row-value `IN`.
The one single-identifier call in `Identity/` — `IdentifierFields::of()` asking an identifier association's target for its one identifier field — relies on [an identifier association always resolving to exactly one join column](#an-identifier-association-always-resolves-to-exactly-one-join-column), and is where an auto-derived association onto a composite target throws.
Verified against ORM 3.7; re-verify on a major bump.

### Doctrine's `IdentifierFlattener` is not adopted, because it misreads a chained derived identity

[Principles §2](../../internals/principles.md) says adopt what the ecosystem ships, and `Utility\IdentifierFlattener` is public, final, recursive and enum-aware, which is the whole job.
It is not adopted on **executed evidence**, which is the bar §2 sets for not adopting — a named, checkable gap rather than a preference.

Two gaps.
Fed an **incomplete** identifier it answers `''` plus a PHP warning rather than refusing, and under Pest a warning is an exception.
And for a **chained** derived identity — an identifier association whose target's own identifier is an association — its recursion is handed `UnitOfWork::getEntityIdentifier()`'s *already flattened* array, reads it by join column name, finds no such key, and answers a blank.
That second one only appears **once the target is managed**, which a row read from the database always is: key such a record before `flush()` and it works, key it after and `of()` throws `is not a printable value` while `find()` answers `null` for a record that is plainly saved.
A suite that keys its fixtures before flushing stays green over it, which is exactly the defect class this trail exists to catch.

`RecordKey::flatten()` therefore recurses itself, in one private method that also subsumes the completeness count and needs no unit of work.
Either way **no consumer branches on the identifier flavour.**

### `getReference()` on a derived identity is state-dependent, so the codec never mints one

`EntityManager::getReference()` works on a scalar composite identifier — a reordered map included — but wherever **any** identifier field is an association it succeeds only while the record is already managed and throws a `TypeError` against a cold identity map, because `ProxyFactory` assigns the scalar identifier component directly to the typed association property.
That is **three of the five flavours**: a derived identity, a join entity, and a **single derived identity**, which reports `isIdentifierComposite === false` and which a width test therefore misses.
Both outcomes were reproduced in a single process, by `clear()`ing between them.
That is precisely the defect a green suite hides: a test that seeds and then references passes, and production fails on the first cold request.
`RecordKey` therefore offers no reference-by-key at all, and the reader's `getReference()` routes those three flavours through `find()` — which works in every state, at one statement — on the predicate `array_any($metadata->identifier, $metadata->hasAssociation(...))`, never on `isIdentifierComposite`.
The scalar route keeps the reference's zero statements.

### Bare `IDENTITY(path)` on a multi-column association answers only the first column

Silently — no exception, no warning, just the first join column's value.
The two-argument form `IDENTITY(path, 'field')` does not rescue it either: it resolves **field mappings only**, so a component that is itself an association raises `Undefined reference field mapping`.
An identifier association resolves to [exactly one join column](#an-identifier-association-always-resolves-to-exactly-one-join-column), so the codec's projection is safe by construction — and, where that guarantee is weakest, by the codec's own throw rather than by a silent truncation.
`groupBy()` emits the **bare path** rather than `IDENTITY()`, both because `IDENTITY()` is illegal in a `GROUP BY` and so that no consumer learns the wrong habit from this file.

### Comparing two identification variables compares the first identifier column only

`WHERE inner = outer` between two aliases of the same entity is legal DQL and compiles to a comparison of their **first** identifier columns alone — no exception, no warning, and rows that are not the same record come back.
Executed against a scalar pair inside a correlated subselect it returned two rows where one was right.
Doctrine's parser resolves an identification variable in a comparison to a single-column expression (`vendor/doctrine/orm/src/Query/Parser.php:736`), and what it emits is valid SQL, so nothing downstream can catch it.

`MEMBER OF` fails the same way from the other end: `outer MEMBER OF alias.<collection>` compares every inverse join column of the association against the target's **first** identifier column, and returns nothing at all.

So `correlate()` exists, and it is the only correlation the toolkit writes.
It emits one `AND`ed equality per identifier field — `i.id = o.id` for a single-`id` class — and every consumer that needs two aliases of one class compared uses it: tables' relation search ([has-query.md](../tables/has-query.md#a-composite-identifier-root-correlates-and-groups-by-every-key-field)) and the relationship query's many-to-many arm ([relationship.md](relationship.md#the-relationship-query-is-rooted-at-the-target-and-needs-no-inverse-mapping)), whose `MEMBER OF` alternative this closes for good rather than parking as an optimisation.

DQL the application writes itself is outside that guarantee.
An association path to a target keyed by several fields, or a comparison of two aliases, inside a builder the application hands over — a table's `query()`, a query modifier — is the application's to join and correlate, through the public `correlate()` if it likes; the toolkit neither rewrites nor refuses it.

### Doctrine's own identity hash is an unescaped `implode(' ', …)`

`UnitOfWork::getIdHashByIdentifier()` joins the identifier values with a single space, positionally, unwrapping a `BackedEnum` and escaping nothing — so `('a b', 'c')` and `('a', 'b c')` hash to the same string and collide inside the ORM's identity map.
Doctrine gets away with it because the hash is process-internal, scoped per class and never parsed back.
It is **not** prior art for a key that crosses a request boundary, and it is recorded here so nobody reads this file's escaping as paranoia the ORM shares: it is an ambient Doctrine-imposed ambiguity, out of our hands, and one our own format does not inherit.

### A non-`Stringable` object identifier cannot be persisted at all

An identifier field whose PHP value is an object with no `__toString()` fatals inside `UnitOfWork::addToIdentityMap()`, before anything of ours runs.
So for such an object the codec's *not a printable value* refusal can only fire on a record that was never persistable.
The same refusal fires for a blank string component, and that one the database accepts — see [the next section](#a-stored-row-with-a-blank-string-component-cannot-be-keyed).
The rule it applies is [the one printable rule](record-key-contract.md#the-api), `IdentifierFields::printable()`.

### A stored row with a blank string component cannot be keyed

A string component that is empty or only whitespace has no printed form: no component may be empty ([the grammar](record-key-contract.md#the-grammar)).
PostgreSQL, MySQL and SQLite all store it in a `NOT NULL` key column anyway.

Such a row loads, and minting its key throws, so a table or resource page that shows the row fails whole.
A page that does not show it renders.
A single string identifier `''` behaves the same way.

The refusal names the row — *The identifier of [App\Entity\ProductVariant] is not a printable value.
It holds code "SKU-1", suffix "".* — so an operator can find it.
A create cannot write such a row: a new record whose assigned key is still blank is refused before anything is written ([can-be-validated.md](can-be-validated.md#a-blank-assigned-key-is-refused-before-anything-is-written)).

A reserved spelling for an empty component (a lone `=`, which is malformed) would let such legacy rows list.
It is recorded, not built.

### A single string identifier containing `.` or `/` stays broken

[The grammar](record-key-contract.md#the-grammar) exempts a one-field identifier from escaping, so a single-identifier key is the identifier value itself — and that deliberately **leaves** a hole open rather than closing it.
A `Book` keyed on `code` with the value `sy/0.1` breaks the `{record}` route (the `/`) and re-addresses a repeater item's state path (the `.`).
This is stated so the first user with a slug-keyed entity does not read the contract as a guarantee it does not make: **only a composite key carries the alphabet guarantee.**
Closing it for single identifiers would move every URL and every pinned test in the repo, a breaking change of its own.

### An integer beyond the column's width still raises the platform's range error

Not one beyond PHP's, though, and the difference is the whole of the residual.
A key of `99999999999999999999` **names no record**: `IntegerType`'s `(int)` cast saturates it to `PHP_INT_MAX`, that value prints back as `9223372036854775807`, and the print-back compare refuses the mismatch — without it the key would resolve the `PHP_INT_MAX` row, which is the `5abc matches record 5` hole in a different costume.
`BigIntType` does not saturate, so it gets [a shape check](record-key-contract.md#parameter-naming-and-what-an-empty-or-malformed-key-means) instead.

What remains is an integer that is valid for PHP and too wide for the **column** — `5000000000` against a `smallint`, say.
That decodes, binds, and raises the platform's own out-of-range error exactly as it does for a single `id`.
Checking it would mean reading the column's declared width and reproducing each platform's limits, which is a per-platform table that would drift, so it is rejected.
The residual is recorded rather than hidden.

### A type that cannot read its own printed form mints keys that never decode

This is the type that refuses its printed form on read **and** on write — the doubly-strict shape.
A type that refuses it on read only [decodes through its own writer](#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer), the section below; one whose writer throws for that same string has no route by which a string names its record.
The fixtures' `CipherType` is one: it stores `c:<value>`, reads nothing without that prefix, and writes nothing but a `Cipher`.
`RecordKey::of()` mints the PHP value's printed form, `IdentifierFields::toPhpValues()` hands that string to the reader, which raises a `ConversionException`, then to the writer, which raises another, and the decode answers `null`.
So **every** key such a class mints is refused: a keyed read narrows to nothing and a reference is refused by name, on a record that exists and whose key nothing forged.

What that costs depends on which side of a relation the record stands.
As an **owner** it is loud: `getReference()` refuses by name at zero statements, and a composite owner with such a half throws by name at `getOwnerScope()` before anything runs, on both arms of both relationship queries ([the message](relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
A **single** owner of the type, and a single derived identity onto one, are not refused at all — their bind reads the identifier value, never the key ([the bind](relationship.md#a-single-identifier-owner-binds-its-identifier-value-typed-by-the-fields-own-dbal-type)).
As the **selected records** of a by-key bulk write it is silent: the plucked keys decode to nothing, so a by-key dissociate narrows to `1 = 0` and reports a zero count with every row left owned — executed on SQLite and PostgreSQL 18.4 — and the by-key delete narrows through the same `in()` to the same `1 = 0`.
The owner side throws and the child side does not.
Recorded rather than fixed: the type breaks the round trip every key depends on, and nothing on this side of the codec can mend that.
The decode and the reference are pinned over a single (`Strongbox`), a composite (`Vault`, one half of the type) and a single derived identity onto one (`StrongboxSeal`) in [`IdentifierFieldsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierFieldsTest.php) and [`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php), `Vault`'s owner throw in [`AssociationReaderOwnerScopeTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerScopeTest.php), and `StrongboxSeal` scoping by value in [`AssociationReaderOwnerBindTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerBindTest.php).
The child side is not pinned: it was executed as a probe.

### A type that refuses its printed form on read only decodes it through its own writer

A DBAL type whose reader accepts **only the database form** while its writer takes the printed one — the `uuid_binary` shape, where the column holds sixteen raw bytes: `ramsey/uuid-doctrine`'s, not `symfony/uid`'s, whose types [round-trip their printed form](#the-shapes-the-fixtures-pin) — would mint keys a strict decode refuses.
So `toPhpValues()` answers the reader's `ConversionException` — and a value the reader answers that does not print back to the component — by running the component through the type's own writer and reading back the database form that makes — `convertToPHPValue(convertToDatabaseValue($component))` — and hands the result to the unchanged print-back rule.
So such a record's key decodes, and everything downstream of the decode follows it: `equals()`, `notEquals()`, `in()` and `notIn()` bind the sixteen bytes under `ParameterType::BINARY` — the batch under `ArrayParameterType::BINARY` — and match rows, which an untyped string bind never does on such a column; `getReference()` returns a reference where a strict decode refuses one; and a composite owner with such a half scopes both relationship queries rather than throwing.
Every consumer is on that one call — panels' `{record}` binding, tables' row resolution, selection, reorder, aggregates and paging, forms' relationship `Select` save and `unique(ignoreRecord:)`, and actions' by-key delete, detach, attach and associate.

The print-back rule is what keeps it one key per record.
The writer takes spellings the printed form is not — uppercase, thirty-two hex digits, braces, a `urn:uuid:` prefix, base58 and base32 all name one uuid to `Uuid::fromString()` — and each reads back to a value that prints lowercase and dashed, so each is refused at zero statements, as are garbage and the sixteen raw bytes themselves.
Neither step catches more than `ConversionException`: a custom type whose writer throws anything else for a forged component breaks DBAL's contract, and that exception surfaces from the decode — a forged `{record}` against such a type is a 500, not a 404.
Catching `Throwable` would turn a genuine bug inside a type into *this key names no record*, which is a catch-and-ignore; `ramsey/uuid-doctrine` wraps every failure into a `ConversionException` on both sides, so it loses nothing.
The writer is reached for **every** non-canonical spelling of a type whose reader answers without refusing — `05` for an `integer` half, `FF` for a hex one — so a writer that raises anything but a `ConversionException`, or a PHP deprecation (`hexdec()` raises one for `+ff`), surfaces there, where a decode without the writer would answer `null`.
DBAL's own boolean writer is the one built-in that does, on PostgreSQL, with an `UnexpectedValueException` for an unrecognised literal, which is why [the decode](record-key-contract.md#parameter-naming-and-what-an-empty-or-malformed-key-means) shape-checks a boolean component as `0` or `1` before any conversion; every other built-in either passes the string through its writer or refuses it with a `ConversionException`.

The fallback touches the decode alone: `toDatabaseValues()`, the split, the shape checks and `printable()` do not reach it, and a type that reads its printed form back as itself never reaches it either, so such a type answers as a strict decode would — `SkuType`'s `not-a-sku` included, its writer taking a `Sku` and nothing else.
One shape widens further than the rest: where a type's **writer** takes any string at all — DBAL's four JSON types, `json`, `jsonb`, `json_object` and `jsonb_object`, whose writer is `json_encode()` — every well-formed component decodes, each to the string it spells, so a **forged** key of such a field costs one statement that finds nothing.
Nothing is mis-keyed by that: the print-back rule holds there too, and one key still names one record.
The fallback costs three or four conversions instead of one for a component the reader refuses or reads as another value, unmeasured, and only such a component pays — that includes a forged non-canonical spelling of any type, `05` for an `integer` half among them.
A reference to a class whose every identifier field is scalar is built at zero statements; one behind an association — a single derived identity onto such a target — is loaded, at one.

Pinned over [`BinaryUuidType`](../../../packages/schemas/tests/Fixtures/Doctrine/BinaryUuidType.php), a mimic of ramsey's `UuidBinaryType` over `symfony/uid` — ramsey deliberately not a dependency — and three owners of it, `Crate` (one such identifier), `Pallet` (such a half beside a string) and `CrateSeal` (a single derived identity onto `Crate`), in [`IdentifierFieldsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierFieldsTest.php), [`IdentifierConstraintTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierConstraintTest.php), [`AssociationReaderCompositeKeyTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderCompositeKeyTest.php) and [`AssociationReaderOwnerScopeTest`](../../../packages/schemas/tests/Unit/State/AssociationReaderOwnerScopeTest.php).
It reaches the per-field `BINARY` bind and its array twin, and it reads a **stream** where PostgreSQL's hydrator hands `bytea` back as one — a mimic without that arm passes every SQLite suite and breaks on PostgreSQL alone.

The fallback serves an **object**-returning type, the `uuid_binary` shape, and a scalar one too.
The fixtures' `HexCodeType` holds an integer column as its lower-case hex spelling and reads whatever it is handed as a decimal number, so its reader takes the printed `ff` without refusing and answers `0`; a fallback reached only on a `ConversionException` would never run for it, and every key such a class mints — the record side's `ff~north`, through the writer and back — would be refused at every decode site.
The retry on a failed print-back reaches the writer (`ff` → 255) and reads 255 back as `ff`, while `FF` (the writer refuses), `0ff` (the writer takes it, and it prints back without the zero) and `+ff` stay refused.
The retry depends on the row side's [second reader](#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time): without it, such a type's silent `0` options would be keys that decode, to the wrong record.
Pinned over `HexSwatch` and `HexTile` in [`IdentifierFieldsTest`](../../../packages/schemas/tests/Unit/Identity/IdentifierFieldsTest.php), beside the boolean shape check over `Flagged` on a PostgreSQL and a SQLite platform.

### Identifier order is declaration order, so reordering two `#[Id]` properties invalidates every minted key

`ClassMetadata::$identifier` is filled in the order the attributes are declared — not alphabetically, not by column order — and the codec joins and splits in that order.
Swapping two `#[Id]` properties in an entity therefore changes what every already-minted key means: a bookmarked URL, a stored selection, a key in flight in an open tab.
There is no version marker in the format to detect it with, and adding one would cost every key two characters forever.
Treat the order of `#[Id]` declarations as part of the schema, like a column name; it is pinned by a test over the fixtures so the fact cannot quietly stop being true.

### A type extending a shape-checked family is checked as that family

The shape check reaches `BigIntType`, `DecimalType`, `GuidType` and `BooleanType` by class, so a type registered under a name of its own is checked as the family it extends — which is the point, since such a type inherits the conversion that hands a malformed string back untouched — or, for `BooleanType`, whose PostgreSQL writer refuses an unrecognised literal with no `ConversionException`, the reason that arm exists once the decode offers a component to the writer.
The cost is a subclass whose PHP value prints **outside** its parent's shape — a `BigIntType` whose reader answers a `T-123` value object, or a `BooleanType` whose value is not a bool — whose every key the decode refuses.
No such type is known, and the way out is not to extend a family whose shape it does not print.
Pinned by `Voucher` in `IdentifierFieldsTest` and `RecordKeyTest`.

### There is no owner-scoping operation, because one `equals()` on the owner's own collection scopes an owner of any width

`IdentifierConstraint` has no `scopeToOwner()` and no owner tuple.
Such an operation would **mutate** the builder rather than only binding on it, and a derived-identity owner would need a join — inner for the related query, **left plus `IS NULL`** for the candidate one.
The relation layer needs neither: its owner scope is one `equals()` call (a plain equality for a single-identifier owner) against the alias of a correlated subselect over the owner's **own** collection, so nothing is mutated, no join reaches the outer builder, and the candidate arm is that same text under `NOT EXISTS` — which keeps a partially-`NULL` foreign key where a negated equality drops it ([the shape, and the two measured against it](relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path)).
The join is not merely unnecessary but **wrong**, executed: it returns the wrong rows on every many-to-many candidate cell, because narrowing the target by joining the owner inverts what the negation is taken over.
The other candidate, `IDENTITY(path, 'field')` per key field, cannot reach an association-valued key half at all.
