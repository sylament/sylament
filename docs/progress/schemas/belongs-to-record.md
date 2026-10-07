---
description: 'The root-tier record binding on `Schema`: `record()`, the `fill()` and `getData()` mapping edges over the record path map, and its relationship-hook routing.'
ports:
  - vendor/filament/schemas/src/Concerns/BelongsToModel.php
owns:
  - packages/schemas/src/Concerns/BelongsToRecord.php
---
# BelongsToRecord (schemas)

[`Sylament\Schemas\Concerns\BelongsToRecord`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) is the root tier, composed by [`Schema`](schema.md); Filament ships the component tier under the same class name in another namespace, and so does Sylament ([component-belongs-to-record.md](component-belongs-to-record.md)).
It ports Filament's `BelongsToModel` under the record-only vocabulary ([the collapse](#the-record-vocabulary-collapses-onto-record)): `record()` binds a Doctrine entity, a plain object, raw record data, or a class-string, and the two Sylament-only mapping edges — the [`fill()`](#fillobject-reads-only-the-paths-the-schema-declares) object arm and `getData()` — carry state between that object and the schema's state bag.
The per-component relationship hooks reroute a path through its own component when a plain property read or write cannot express the association ([the collapse of Filament's third pass](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)).
The operation half lives in [`has-operation.md`](has-operation.md), the optional-branch declaration `getData()` consumes in [`has-nullable-branch.md`](has-nullable-branch.md), and the write-side type lookup in [`record-type-reader.md`](record-type-reader.md).

## Surface — Schema

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`model()` + `record()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-vocabulary-collapses-onto-record) · [semantics](#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it) |
| [`getRecordClass()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`getModel()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-vocabulary-collapses-onto-record) |
| [`getRecord(bool $withParentComponentRecord = true)`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`getRecord()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | — |
| [`getRecordInstance()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`getModelInstance()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#getrecordinstance-constructs-with-new-not-the-container) |
| [`hasOwnRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [semantics](component-belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`getData(?array $state = null)`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#reads-are-lenient-writes-are-strict) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#a-missing-singular-intermediate-is-constructed-from-its-declared-type) · [semantics](#a-caller-that-has-already-validated-hands-the-state-in) · [semantics](#a-refused-submit-on-the-query-lane-shows-in-the-row-until-the-next-request) |
| [`getData()`'s record-validation tail](../../../packages/schemas/src/Concerns/BelongsToRecord.php) + [`getRecordViolationPathTranslator()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) · [diverges](can-be-validated.md#a-refused-submit-leaves-the-record-mutated) · [diverges](can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation) · [diverges](can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine) · [violation-path-translator.md](violation-path-translator.md) |
| [`findUnmappedAdditions(array $before, array $after)`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) — the paths a write seam's hook added that no component maps (`@internal`: the default write seams refuse what it answers) | — | done | — | [semantics](#a-hook-added-path-no-component-maps-is-answered-for-the-write-seams-to-refuse) · [diverges](#a-hook-added-key-no-component-maps-is-refused-where-eloquent-writes-or-drops-it) |
| [`getNullableBranches()` + the branch pass in `getData()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](has-nullable-branch.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) · [diverges](#a-component-can-own-its-whole-record-subtree) · [semantics](has-nullable-branch.md#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) · [semantics](has-nullable-branch.md#a-branch-the-record-cannot-null-is-refused-loudly) · [semantics](has-nullable-branch.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored) · [semantics](has-nullable-branch.md#branches-are-ordered-by-path-depth-because-tree-order-is-not-path-order) |
| [`getNestedRelationshipHooks()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`fill(object …)` object arm](../../../packages/schemas/src/Concerns/HasState.php) + [`readStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#fillobject-reads-only-the-paths-the-schema-declares) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#fillobject-does-not-bind-the-record) |
| [`getStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#fillobject-reads-only-the-paths-the-schema-declares) |
| [`$record` property](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`public $model`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-root-record-property-is-protected) |
| `saveRelationships()` | [`saveRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| [`loadStateFromRelationships()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`loadStateFromRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) · [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| `fillPartially()` object arm | — | blocked | `fillPartially()` ([schema.md](schema.md)) | [detail](#fillpartiallys-object-arm-waits-on-partial-rendering) |

## Details

### `fillPartially()`'s object arm waits on partial rendering

`fillPartially()` / `hydrateStatePartially()` are not ported ([schema.md](schema.md)), because they wait on a UX LiveComponent partial-render equivalent.
The object arm, sourcing a partial fill from a record rather than an array, would make the same `readStateFromRecord()` call the `fill()` object arm makes.

## Semantics

### A hook the record path map does not carry is refused

Both hooks are consulted from inside the map walk, so a component the map excludes is never asked whether it declares one.
Three exclusions produce that shape, and `getRecordPathMap()` refuses each of them with a `LogicException` naming the component, which exclusion it met, and the way back into the map:

| the component | why the map skips it | the way out |
| --- | --- | --- |
| carries no state path (or one that resolves to no record path) | the map is keyed by record path | give it the `statePath()` of the association it maps |
| nests path-bearing components without [owning its subtree](#a-component-can-own-its-whole-record-subtree) | the map takes its leaves, never the container | declare `ownsRecordSubtree()` so it maps whole |
| sits beneath a subtree owner that [does not route it](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) | the owner speaks for every path under its own | declare `relationship()` on that owner, or move it out from under it |

Left as a silent skip, a hook on any of the three would be the worst shape this mapper can produce: no error, no write, no trace — a relationship that simply never saves.
The middle row is the one a user hits, because a container is the natural place to hang a relationship, and it is exactly the shape the forms `Repeater` needs; it maps only because `Repeater::ownsRecordSubtree()` returns true.
The refusal is [the same stance](#reads-are-lenient-writes-are-strict) the mapper takes on a foreign path nesting under an owner, and the same one [`nullWhenBlank()` without a state path](has-nullable-branch.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored) takes.

The **third** row is the one a relation-backed field hits, and it is the only one with an exit the owner can open: an owner that [routes the hooks beneath it](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) takes delegation of that path and the refusal never fires.
Where no owner does, the row's other exits stay dead for that shape — a `Select` mapping a to-one association from inside a relationship repeater has nowhere to move to, and dropping its hook only moves the failure to the owner's own write.
The two owners that route are [the repeater, by the item](../forms/repeater-relationship.md#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map) and [the entangled layout components, onto the related record](entangles-state-with-singular-relationship.md#the-subtree-is-re-rooted-and-its-nested-hooks-are-routed-onto-the-related-record).
The first two rows have no such exit at any depth: a path-less component and a path-bearing non-owner container are unroutable shapes, so they keep refusing beneath a routing owner exactly as they do anywhere else.

The guard collects hook-declaring components in the map build's own walk and tests membership after the owner pass, so it costs no extra traversal and sees the map exactly as the two edges will consult it.
It fires from `fill()` as well as `getData()` — both go through the map — so a misconfigured hook is reported on the first render, not first on submit.

### A hook a subtree owner routes is delegated rather than refused

An owner swallows the paths beneath it, so a hook down there can only fire if the owner runs it.
`routesNestedRelationshipHookAt(string $path)` — on `HasChildComponents` beside `ownsRecordSubtree()`, default `false` — is where the owner says it will: the mapper asks it, per **owner-relative** path (the owner's own record path stripped), whether its walks route a hook there, and a `true` turns the third refusal arm into a delegation.
`getNestedRelationshipHooks()` on the root tier is the walk behind both halves — the owner answers the vouch from it and routes from it — which is what keeps a vouch from outliving the routing it promises.
Why the vouch is pulled by the mapper rather than handed over when the map is built is [stated with the hooks](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk): at `fill()` a relationship repeater has no item schemas to hand over.

Three rules decide which component a delegation reaches, and each is the reason a neighbouring shape still refuses.

**The nearest owner is asked, never the outermost.**
`$owners` is walked in flat order, outer before inner, so an outer owner's pass drops an inner owner's descendants before the inner owner's pass ever sees them — [`isInsideOwnerTree()`](#a-component-can-own-its-whole-record-subtree) answers "any ancestor", which cannot tell the two apart.
Attribution walks the container chain to the *first* owner instead, so a hook under a non-routing owner nested inside a routing one is still refused, and the message names the owner that would have had to route it.

**Only the map's last claimant is delegated.**
The owner pass records the component it unsets per path, which is by construction the one that survived the map's last-wins overwrite.
Two hooked components sharing one record path under an owner therefore split: the survivor is delegated, the earlier one was never in the map, never dropped, never recorded — and keeps refusing.
That is also the component the owner's discovery would have dropped, so mapper and discovery name the same survivor.

**Only the `default` arm can be skipped.**
A hook with no record path and a hook on a path-bearing non-owner container are unroutable whatever sits above them, so they refuse beneath a routing owner exactly as they do at the root.
So do the two wholesale-claimant refusals and the foreign-path one, which the owner pass raises before any of this.

Loudness survives by construction, and that is the invariant to protect when a new owner is written: because the vouch is answered from the routing walk itself, every component the mapper stops refusing is one an owner's walk will actually run.
Concretely still refused, each naming the component: a path-less or non-owner-container hook anywhere; a hook in a child schema the owner's walks never consult; the earlier of two hooked components sharing a record path; a hook under an owner whose vouch is off, a plain-array repeater or an undeclared relationship among them; and a hook under an inner non-routing owner inside an outer routing one.
An owner that vouches for a path it does not route would break that invariant — and would break it silently, which is the one failure this mapper exists to prevent.

Discovery membership is a smaller set than the map's, and deliberately so: every flat component with a load **or** save hook whose state path nests under the schema's own, minus anything with an intermediate owner between it and the schema.
A nested owner is one entry itself when it declares a hook, and everything beneath it belongs to that owner — which is how recursion falls out with no recursive code: the outer owner runs the inner owner's hook, and the inner owner repeats the whole scheme one level down.
Keys are relative to the schema asked, not to the root, so an item schema and the root schema answer in their own terms; the derivation is [the one `getRecordStatePath()` uses](component-belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it), against the schema's own state path rather than the root's.

Filament needs none of this machinery for the nested to-one, and not because its walk is richer: its select is conventionally named for the **foreign-key column** and stays dehydrated (`Select::relationship()` sets `dehydrated(fn (Select $component): bool => (! $component->isMultiple()) && $component->isSaved())`), so `$record->fill($itemData)` writes `author_id` as an ordinary attribute and the hook is not load-bearing there.
Doctrine maps the association and no foreign-key scalar beside it, so the write has no attribute to land on and the hook is the only route — which is why the port met a wall Filament does not, and why closing it was mapper work rather than transcription.

### Two components cannot both map one record path wholesale

The map is keyed by record path, so two components claiming the same one would leave the second silently replacing the first — and where one of them [owns its subtree](#a-component-can-own-its-whole-record-subtree), what is lost is not a shared leaf but a whole branch that one of the two would stop filling and saving without a trace.
`getRecordPathMap()` refuses the pair, naming both classes and the path.
Two path-bearing components sharing a record path where **neither** owns a subtree are untouched: that is the ordinary case of two fields bound to one property, and both still write it.

The shape a user meets this in is [two layout components entangling one relationship](entangles-state-with-singular-relationship.md#two-components-entangling-one-relationship-throw-rather-than-merge), which Filament merges instead; that file carries the decision and what would reverse it.

### The root tier memoizes a closure record; the component tier re-evaluates it

Both tiers accept a closure and both run `evaluate()` on entry to every getter, but they store the result differently, and this is Filament-exact rather than an oversight: the root tier assigns back (`$this->record = $this->evaluate($this->record)`), so the closure runs **once** and the schema is bound to whatever it returned; the component tier reads into a local, so the closure runs on **every** call and a component-level record can change between two reads in the same request.
Both are pinned by a call-counting test per tier.
The consequence worth knowing: a root-tier `record(fn () => $repository->find($id))` is a lazy binding, not a live one — after the first getter it can never see a different record.

### A caller that has already validated hands the state in

`getData()` reads `getState()` — which validates first — so a caller that has *already* validated and then mutated the array it got back cannot write that array through the state bag: `rawState()` + `getData()` would validate the mutated values a second time (a hashed password against the `Length(max: 20)` that passed on the plaintext, a stamped slug against a uniqueness probe) and re-run every dehydration mutation the components declare, `Repeater`'s item walk and `FileUpload`'s store move among them.
Handing the array in as `$state` skips exactly one step, `$this->getState()`, and nothing else: the nullable branch pass, the relationship save hooks and the closing `validateSchemaRecord()` all run against the supplied array, so record-level constraints are still answered on the object the mapping produced.

The callers are the write-side seams that sit between validation and mapping — the modal actions' `CreateAction`, `EditAction` and `ReplicateAction` bodies after their data hooks, the resource page bases after `mutateFormDataBeforeCreate()`/`mutateFormDataBeforeSave()`, and `Select`'s option actions after theirs.
Every zero-argument call site keeps the validating read.
Each of those seams first refuses a path its hook added that no component maps ([below](#a-hook-added-path-no-component-maps-is-answered-for-the-write-seams-to-refuse)).

### A hook-added path no component maps is answered for the write seams to refuse

`findUnmappedAdditions(array $before, array $after): array` answers the leaf paths a write seam's hook added to the state it was handed that no component of the schema maps.
It is `@internal`; its readers are the default write seams, which refuse a non-empty answer by name before mapping: both resource pages after their `mutateFormData…()` hook, `CreateAction`, `EditAction` and `ReplicateAction`'s modal arm at the head of their default write closures — so `using()` is never guarded — and `Select`'s option actions.
Without the refusal such a value would be dropped in silence, since `getData()` writes the paths the record path map carries and nothing else; writing it instead is no option, because a host's writable `data` prop takes whatever keys a client sends, and writing unmapped state would be mass assignment.

A leaf path of `$after` — a value that is no array, or an empty array — is **added** when `$before` does not hold it, read level by level, an unchanged subtree skipped on `===`; and a path where `$before` held an array and `$after` holds something else is answered too, since a hook writing `null` over a mapped `address.street` branch writes nothing `getData()` reads — no component maps `address` itself.
A path is **covered** when a record path equals it or is its dotted ancestor, so a subtree owner speaks for everything beneath it, `null` over a relationship repeater's whole `items` included.
What follows:

- a key the state already held — a client's own, a `storeFileNamesIn()` sibling — is never answered, whatever the hook does to it, so a client can never force the refusal; it stays unwritten;
- an overwrite of a mapped key is never answered;
- a key a hidden or `dehydrated(false)` component maps is covered **and written**, since the map carries it and the hook put it back in the state;
- a key added inside a relationship repeater's item is covered by the owner and still dropped, since the owner writes the item paths its template declares — recorded;
- a key the hook spells with a dot (`'a.b' => 1`) is read as one key, where `getData()` reads it as a path — at most a missed refusal, recorded;
- a removed key, and a mapped branch emptied to `[]`, are no addition: the record keeps what it held — recorded.

Pinned by [`BelongsToRecordUnmappedAdditionsTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToRecordUnmappedAdditionsTest.php), the null-over-a-branch case by name; each seam pins its own refusal.

### A refused submit on the query lane shows in the row until the next request

Mapping happens before the object-level validation, so a refused submit leaves the record mutated ([the divergence that states it](can-be-validated.md#a-refused-submit-leaves-the-record-mutated)).
On a table's query lane that mutation is visible: the row was rendered from a managed entity, an `EditAction` beneath it resolves the record through the same entity manager and the identity map hands back the *same instance*, so the re-render after the refusal paints the rejected values into the row even though nothing was flushed.
The next request re-queries and the row is correct again.

Filament's `$record->update($data)` has the same shape — it fills before it saves — so this is faithful rather than a defect, and the escape is the one the divergence above already names: never flush on the failure path, and refresh or discard the entity manager if the request continues past it.

## Divergences

### The record vocabulary collapses onto `record()`

Filament exposes one property under two names: root-tier `model()` with a `record()` alias that accepts a narrower payload (no class-string), and a component tier that ships `model()` and no `record()` at all.
Sylament ships `record()` only, on both tiers, widened to take the full payload set — instance, raw array, class-string (declares the class without binding an instance), closure — and renames the getters to match (`getModel()` → `getRecordClass()`, `getModelInstance()` → `getRecordInstance()`; `getRecord()` keeps its name).
Three reasons, judged against the [rename criterion](../renames.md):

- **Filament is internally inconsistent here.**
  The rest of it — panels (`EditRecord`, `getRecord()`), tables, actions — speaks "record"; only these two traits speak "model".
  We collapse onto Filament's own more prevalent name.
- **"Model" names an Eloquent concept that does not exist here.**
  Entities are POPOs; the concept-name leaves with the ecosystem, like rule → constraint — where the loud, searchable break was judged better than a kept name over changed semantics.
- **It removes a real API trap.**
  With two setters over one last-write-wins property, `->record($article)->model(Article::class)` reads as additive but silently unbinds the instance — and an unbound instance on an edit form means every save inserts a new row.
  One name, one call: `record($article ?? Article::class)`.

Because any object binds, `record(SomeDto::class)` or `record($someDto)` is also the typed boundary between a schema and the object behind it, so no Sylament-only `dataClass()` sits beside it.
It also converges with the codebase: actions' `InteractsWithRecord` ships record-only, and schemas' `BelongsTo*` family (`BelongsToContainer`, `BelongsToLiveComponent`, `BelongsToParentComponent`) absorbs `BelongsToRecord` naturally.
**Migration cost, stated:** Filament code calling `->model(...)` or type-hinting `fn ($model)` breaks loudly — a missing method or an unresolvable closure parameter, never a silent behaviour change.
The rename set is recorded in the [rename table](../renames.md).

### `fill($object)` reads only the paths the schema declares

Filament's `EditRecord` fills from `$record->attributesToArray()` — every non-`$hidden` attribute of the row, pushed into the browser's state bag — and offers `mutateFormDataBeforeFill()` to strip what should not have gone.
`readStateFromRecord()` inverts that: it walks the schema's own `getFlatComponents(withActions: false)` and reads only the paths those components declare, so an attribute no field names is never read and never reaches the client.
That is a privacy improvement, not just a scoping choice — the mutate hooks exist in Filament because the default is over-broad.
It also means the object arm is *precisely* "`attributesToArray()`, scoped to the schema's fields": having built the array it runs the existing array arm unchanged, so state casts, `afterStateHydrated`, and the null-fill pass behave identically to a hand-written `fill([...])`.
The page-level `mutateFormDataBeforeFill()`/`beforeFill`/`afterFill` hooks belong to the Resource pages, as page surface rather than schema surface.
[`getStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) is the same read handed back as an array instead of written into the bag, so a fill-side mutator reads the record without filling twice; Eloquent's `attributesToArray()` has no port target, so it is a gap fill rather than a rename, scoped to the declared paths like the rest.

### Reads are lenient, writes are strict

The fill read-walk wraps each accessor read in one `catch (AccessException)` and skips the path.
Symfony's `AccessException` is the common parent of `NoSuchPropertyException` (no accessor at all) and `UninitializedPropertyException` (a typed property never assigned), so the single catch covers both without an `isReadable()` pre-check, which would only be a second copy of the same walk.
It additionally skips object-valued reads — only scalars, arrays, null and an object **the reading component's own state casts flatten to a non-object** enter the bag.
So a read has exactly two skip reasons: the object cannot supply the value, or the value is not bag-representable, and the null-fill pass then supplies the key.
A null or scalar *intermediate* supplies null for the leaf rather than skipping it — the same bag either way, since the null-fill pass would supply the key.

The state-cast exemption is gated on the cast rather than on the type, because the cast is the whole reason the value is representable at all.
A leaf declaring [`EnumStateCast`](enum-state-cast.md) — or any cast that unwraps a case, which `OptionStateCast` and `OptionsArrayStateCast` also do — passes, because the hydration pass immediately behind the read turns the instance into the scalar backing value the browser will send back.
A leaf with **no** cast, or one whose casts leave the value an object, does not: it skips and null-fills.

The predicate, `isObjectCarriedByStateCasts()`, asks no type question: what earns the exemption is running the component's own casts forward and finding a non-object at the end, so an enum leaf and a `DateTimeImmutable` leaf under [`DateTimeStateCast`](date-time-state-cast.md) pass by one rule.

That gate is not fastidiousness about types.
The state bag is a writable `array` LiveProp, and [`LiveComponentHydrator::isValueValidDehydratedValue()`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php) accepts only bool, null, numeric and string — recursing into arrays — so an object anywhere inside the bag makes `dehydrateValue()` throw a `LogicException` naming the **LiveProp**, not the field that put it there.
A type-only exemption would therefore have turned a `TextInput` bound to an enum-typed property from "renders empty" into a 500 on its first dehydration, which is why the read walk asks the component instead of asking the value.
Running the chain twice — once here to ask, once behind the read to convert — costs a pure transform, since `StateCast::set()` is side-effect-free by contract.

Every object-valued leaf no cast answers for still skips, because it has no bag spelling: an associated entity is the standing case, and an association that wants one declares [`loadStateFromRelationshipsUsing()`](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk), which is checked before this skip.
Without the exemption, `Select::options(MyEnum::class)` over an enum-typed property would fill `null` on every mount while the write side worked.

**The relationship fills ask the same question.**
The predicate is the component's own — [`Component::isObjectCarriedByStateCasts(object $value)`](../../../packages/schemas/src/Components/Concerns/HasState.php), public, `@internal` and without a Filament counterpart — because two more walks ask it: a singular relationship's fill of its related record ([`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md#the-subtree-is-read-and-written-per-declared-leaf-not-as-an-attribute-bag)) and a relationship [`Repeater`](../forms/repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)'s fill of its items.
Were both to skip **every** object leaf, a date, an enum or a `Uuid` inside a relationship repeater item or a `Group::relationship()` would fill `null`, and the next save — one that never touched the field — would write NULL over the stored value.
The component is the home because the answer is its casts, and because it is the one place all three askers reach: the root read runs in `Schema` scope, the other two in a component's.
Each walk asks the field that reads the leaf — the last declared, where two share a path, as the record path map keeps it.
The object enters the bag only where a hydration pass runs right behind the read and converts it — `fill()`, the array fill after `getStateFromRecord()`, and the host's [reload](#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter), whose child-schema hydration is load-bearing for that reason — and a fill hook (`mutateFormDataBeforeFill()`, `mutateRecordDataUsing()`) receives it at its path inside a relationship as it does at the root.
An association, a collection and an object no cast flattens still skip.
Pinned by [`EntanglesStateWithSingularRelationshipObjectLeafTest`](../../../packages/schemas/tests/Integration/Components/Concerns/EntanglesStateWithSingularRelationshipObjectLeafTest.php) and forms' [`RepeaterRelationshipObjectLeafTest`](../../../packages/forms/tests/Integration/Components/RepeaterRelationshipObjectLeafTest.php).

One hole is inherited rather than introduced, and is worth naming because the gate does not cover it: an **array** leaf is not object-valued, so a `list<MyEnum>` property enters the bag whole and relies on the component's cast (`EnumArrayStateCast`) to scalarize its elements.
A cast-less component bound to one can leave instances nested in the bag; the exemption neither widens nor narrows that.

`getData()` is the opposite.
It writes **every** leaf path of the validated state and lets `NoSuchPropertyException` surface when the target cannot take one: by calling it you have declared that this state is the object's data, and silently dropping a key would silently drop a user's edit.
Two errors are softened.
A missing intermediate *object*: `setOnRecord()` wraps it in a `LogicException` naming the path, because Symfony's own message explains nothing at this boundary.
And a property **no setter can change** — a `public readonly` one, or a private one behind a getter alone: left alone when it already holds the value the state writes, and refused by name — the path, the class, the two value types — when it holds another or none yet ([property-accessor.md](property-accessor.md#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value)).
That is what lets a key a record's constructor sets save, where every such write would otherwise be Symfony's `NoSuchPropertyException`; a property neither writable nor readable — a misspelled path — still surfaces it.
The escape hatch for a page-only field is Filament's own idiom — `dehydrated(false)` removes it from `getState()` and therefore from the mapping.
Ordering matters and is deliberate: `getData()` runs the validating `getState()` first, so a failing submit throws [`ValidationException`](can-be-validated.md) before the record is touched at all.

The **second** throw is the other way round.
After the leaf walk, the written record is validated in its own right — [record-sourced constraints](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) are answered against the object the mapping produced, which means by then the record already carries the rejected value ([the cost of that ordering](can-be-validated.md#a-refused-submit-leaves-the-record-mutated)).
So the two failure modes of one call differ: a state failure leaves the record untouched, and a record failure leaves it mutated and unflushed.

### A hook-added key no component maps is refused, where Eloquent writes or drops it

Filament's write seams hand `$data` to Eloquent — `new $model` and `fill()`, `$record->update($data)` — and `Model::fill()` decides per key: a fillable key is written (an unknown column then fails at `save()`), an unfillable one is dropped in silence, unless the application turned on `preventSilentlyDiscardingAttributes()` or `shouldBeStrict()` or left the model wholly guarded — then it is a `MassAssignmentException`, *Add fillable property [house, year] …*.
The record path map plays `$fillable` here, and the toolkit takes the strict branch, unconditionally, and only for the keys a **hook** added: `getState()` keeps a key a client typed that no component declares, where Filament's prunes to the keys its validation answered ([schema.md](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)), so refusing every unmapped key would let a client force the refusal.
The message lists the paths comma-separated, as Eloquent's does.

Permanent.

### Mapping walks the record graph by declared leaf paths

Both mapping edges iterate one walk, `getRecordPathMap()`: every path-bearing leaf component's record-relative path (the schema's root `statePath` prefix stripped), with containers — components nesting a path-bearing component, at any depth and through any child-schema key — contributing their leaves and never themselves, so a leaf decorated with a path-less child schema (`helperText()`, `belowContent()`) stays a leaf.
`fill($record)` reads each leaf through `PropertyAccessor::getFromRecord()` and `getData()` writes each through `setOnRecord()` ([property-accessor.md](property-accessor.md#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs)): object segments hop by property, array segments by key, so `Section::make('author')->statePath('author')` over a nested plain object and a section over an array-valued property (the JSON-column shape) both round-trip.
Three consequences are deliberate:

- **Leaf granularity is the contract in both directions.**
  An array-backed branch is read leaf-by-leaf — an unmanaged sibling key never enters the bag or reaches the client — and written read-modify-write, so unmanaged keys survive `getData()`.
  Filament wholesale-replaces here; we diverge toward our own scoping rule: the schema maps what it declares, nothing else.
- **A missing singular intermediate is constructed; an object collection is refused.**
  A null or uninitialized intermediate whose declared type names a constructible class is built and assigned, so a nested object path needs no hand-written vivifying getter.
  [The grounds](#a-missing-singular-intermediate-is-constructed-from-its-declared-type) are stated below.
  What still throws: a declared class needing constructor arguments, an object collection at any segment — recognised from the declared type (an adder resolves the property to a `list<Tag>`, not to a `Traversable`) and again from the collection instance met on the walk — and an array remainder a typed property rejects.
- **Scope: plain nested objects and embeddable-shaped value objects.**
  The walk will mechanically traverse a loaded Doctrine association and mutate the associated entity in place — almost never what a form edit means, and undetectable here (this walk is handed no `EntityManager`, so it reads declared PHP types and no mapping metadata, even though `schemas` ships [`AssociationReader`](association-reader.md) for the component tier).
  Associations are declared instead — `relationship()` on the owning component, routed through that reader — and this note is the warning, not a guard.

The container test is `hasPathBearingDescendants()`, and it carries a second arm for the component that is both leaf and container: [a component may own its whole record subtree](#a-component-can-own-its-whole-record-subtree), in which case it maps as a leaf and speaks for every path beneath its own.

### A component can own its whole record subtree

Sylament-only, because the record path map is Sylament-only.
`ownsRecordSubtree()` — on `HasChildComponents`, default `false` — marks a component whose own dehydrated value **is** the record value for everything beneath its path — the shape of [the forms `Repeater`](../forms/repeater.md) and of a layout component [entangling a singular relationship](entangles-state-with-singular-relationship.md).
The map build answers it in two places.
The container skip is owner-aware, so an owner is collected as a leaf despite its path-bearing descendants: `fill()` then reads its property whole and `getData()` writes the dehydrated value back wholesale, which is what carries item keys the schema never declared and what makes the leaf-granularity rule above stop at the owner.
And a second pass drops every path collected beneath an owner, so nothing re-reads or re-writes a slice of a value the owner already speaks for.

That pass is not a plain filter, because two different components can produce a path under an owner's.
A **tree** descendant — a field inside the owner's own child schema — is dropped silently; the owner represents it.
A **foreign** one — a component declared beside the owner whose path merely nests under it, `StateField::make('meta.extra')` next to an owner at `meta` — throws a `LogicException` naming both components and the two ways out.
Left alone it would neither fill nor save: the owner's wholesale read never reaches it, and the owner's wholesale write overwrites whatever it wrote.
Silence there is exactly the shape this mapper [refuses loudly](#reads-are-lenient-writes-are-strict).
Tree membership is the container chain walked to the root, never the path — a shared path prefix is precisely what cannot tell the two cases apart.

The branch pass answers the predicate too, in [the three arms recorded with it](has-nullable-branch.md#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) — presence at the owner's own path, the value blank test, and the exact-path skip after nulling — and forms' derived requirement inherits all three, since it asks the same `shouldNullBranch()`.

Dehydration needs no owner arm of its own: it prunes by own path, which is already subtree-safe.

### The relationship hooks are consulted by the two mapping edges, not by a schema-level walk

Filament runs relationships as a **third** pass.
`Schema::saveRelationships()` and `Schema::loadStateFromRelationships()` walk the component tree calling the per-component callbacks, driven from `Schema::getState()` and again by the create pages once the model row exists.
Sylament has no such walk for the **save** and does not port one; the load half is a re-read the host runs after its own flush rather than a pass inside `getState()` ([below](#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter)).
The two mapping edges already walk the record path map — `fill()` through `readStateFromRecord()`, `getData()` through its leaf loop — so a path **the map carries** is routed to its component instead of to the property accessor:

- **`fill()`** — a component answering `hasLoadStateFromRelationships()` supplies the value at its path, bypassing the record read *and* the object-leaf skip.
  That skip is why the hook is needed at all: an association read returns an entity or a collection, which the fill walk drops.
- **`getData()`** — a component answering `hasSaveRelationships()` is handed the dehydrated state at its path and owns the write, so `setOnRecord()` and its [object-collection refusal](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused) never run for it.
  An **unhooked** collection path still meets that refusal unchanged, which is the guard that keeps the hook opt-in.

Map membership is the precondition, not a detail: Filament's walk visits every component in the tree, ours visits only the components [`getRecordPathMap()`](#mapping-walks-the-record-graph-by-declared-leaf-paths) carries, which is a strictly smaller set.
A hook declared on a component outside it is refused at map time rather than left to no-op — [a hook the record path map does not carry is refused](#a-hook-the-record-path-map-does-not-carry-is-refused).

Four consequences follow from collapsing the third pass into the two edges.

**The ordering member loses its reason to exist.**
`saveRelationshipsBeforeChildrenUsing()` is the seam that lets a component write its own relationship *before* its child schemas write theirs — Eloquent needs the parent row inserted before a child row can carry the foreign key, so a nested repeater's items cannot save until the item that owns them has an id.
Doctrine has no such moment: `getData()` mutates an in-memory graph and the caller's single `flush()` sorts the INSERTs itself (`UnitOfWork`'s `TopologicalSort` over the association graph).
A split hook pair would let a user declare a write ordering that Doctrine then re-derives anyway, so the declaration could only ever be ignored or contradicted.
Skipped, not deferred — revisit only if a shape turns up where the *graph mutation* is itself order-sensitive.

**Hooks fire at map time, once.**
Filament runs its walk twice on an insert — from `getState()`, where the per-component `exists` guard suppresses every hook because the row is not there yet, and again from the create page after `handleRecordCreation()`.
Sylament's hooks run inside `getData()`, against the in-memory graph, and the caller flushes once afterwards: the whole submission — leaves and relationships alike — reaches the database as one transaction, and a validation failure throws before any of it, since `getData()` validates first.
That single-flush shape is the relation layer's central choice, and it is what makes both the second walk and the `exists` guard unnecessary.

**`castStateAfterLoadingFromRelationships()` has no seam to hang on.**
In Filament it re-casts a component's state after the relationship walk mutated it mid-request, because that walk is a separate pass that can land after hydration.
Here the load hook's value enters the ordinary state array `readStateFromRecord()` returns, so `fill()`'s existing `hydrateState()` / `callAfterStateHydrated()` / `fillStateWithNull()` sequence already casts it — the same array arm a hand-written `fill([...])` takes.
Skipped as a member with nothing left to do.

**A hook beneath a subtree owner is pulled by the owner, not pushed by the map.**
Filament's walk reaches a nested hook because it descends the component tree; ours reaches only what the map carries, and an owner's paths are dropped from it.
The seam that closes the gap is [a vouch plus a discovery walk the owner runs itself](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused), not a sub-map the owner pass hands over at map-build time — and the asymmetry that rules the handoff out is the repeater's fill side.
At `fill($record)` the raw state is still empty, so no item schema exists yet, so nothing under the owner is in the flat walk and there is no sub-map to hand anywhere; the owner discovers from its **template** child schema at that moment instead, and from the per-item schema at save.
A handoff would also park mutable state on the owner between map builds, and the map is built per edge and again by the [violation translator](violation-path-translator.md).
That translator is untouched by the delegation, deliberately — and it reaches beneath an owner by another route, the last paragraph of this section.

Filament's own recursion is **not** limited to newly created items, which is easy to misread.
`Repeater::saveToRelationship()` re-runs `$item->model($record)->saveRelationships()` only for the items it just created, which is true of that method read alone — but `Schema::saveRelationships()`, the tree walk it sits inside, descends children-first over **every** item's schema before the item's own save runs, so the behaviour recurses for updated items too.
Read the walk, not the method.

**The translator is handed what the map drops beneath an owner.**
Over the map alone, under an entangled owner a nested field's record-level violation would fold at the owner's path, because the map carries only the owner — and a `Group` renders no error of its own path, so that would be a 422 with nothing on screen.
So `getRecordPathMap()` is a wrapper over a private `collectRecordPaths()`, which answers the map beside the paths it dropped beneath a subtree owner, computed by the one walk that always computed them, and `getRecordViolationPathTranslator()` builds the translator over both.
So `plaque.title` lands as `data.plaque.title`, under its field, with no contract member added.
A class-level violation on the related record names no path inside it and still folds at the owner, which renders nothing, so the host files it at the schema's own path instead, where the page and the modal render it ([why](violation-path-translator.md#a-message-is-filed-only-under-a-component-that-shows-its-own-errors)).
Pinned by [`BelongsToRecordRelatedRecordTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordRelatedRecordTest.php) and, rendered, by panels' [`EditRecordRelationshipsTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordRelationshipsTest.php).

### `loadStateFromRelationships()` is the host's reload after its flush, and takes no parameter

Filament re-reads relationship-backed state inside `Schema::getState()`: after `saveRelationships()` it calls `loadStateFromRelationships(shouldHydrate: true)`, before dehydration, or after the `afterValidate` callback when the host hands one, as its `EditRecord::save()` does.
Here there is no save walk to hang it on — the hooks run inside `getData()`, against an in-memory graph nothing has flushed yet — so the reload is the **host's** to call once its flush has run.
[`EditRecord::save()`](../panels/edit-record.md) calls `$schema->loadStateFromRelationships()` after `handleRecordUpdate()` and before `afterSave`.
The name is Filament's; the `$shouldHydrate` parameter is dropped, because the one caller that matters passes `true` — it always hydrates.

For every component in the record path map that answers `hasLoadStateFromRelationships()`, it reads that state off the bound record through the component's own load hook, hydrates it without defaults, re-hydrates each child schema and calls its after-hydrated hooks, then the component's, and null-fills — `fill()`'s sequence, restricted to the relationship-backed paths.
Every other path is left alone, a schema bound to no record reads nothing, and it runs no statement: it reads what the flush left in memory.

It exists because a relationship [`Repeater`](../forms/repeater.md) keys an item the add action minted by a per-request uuid until something reads the collection back, and without the reload nothing does — so the next save would construct that item again.
For an assigned key the second save would be an `EntityIdentityCollisionException` 500, and under the taken-key rule a false *"This value is already used."* on the item just saved; for a generated id every added item would be deleted and re-inserted on **every** save.
With the reload the second save writes nothing and a generated id keeps its value.

Four costs, all enumerated: a relation-backed component's `afterStateHydrated` hooks run once more per save; a host that flushes itself, outside `EditRecord`, has no reload until it adds the one call after its flush — the demo's `Articles:ArticleForm` does; UI-only state inside a relationship is reset; and a relationship repeater item saved on a blank key fails the save itself.
The reload hands a relationship repeater's items and a singular relationship's group back as the record gives them, so a `dehydrated(false)` field there — state the save never wrote — reads what the record gives, `null` where the record holds no such property, after every successful save, rather than keeping what the user typed.
That third cost is Filament's own behaviour, since its reload runs inside the `getState()` its save makes, and it is accepted rather than worked around.
The fourth is the reload's mint.
A relationship repeater item keyed by a single natural string key that no `prePersist` fills, under a key field the form does not `required()`, can be saved blank: a blank component is incomplete to both key rules, so the flush inserts the `''` row.
The reload then keys the item through `RecordKey::of()`, which refuses the blank component with a `LogicException` after the commit — so the save's own response is the 500, where without the reload it would answer *Saved* and the next request's fill would be the 500, the row written either way.
`required()` on the key field is the application's guard.
`EditAction`, `CreateAction` and the option modals unmount on success or refill for create-another, so none needs the reload.
Pinned by [`BelongsToRecordReloadTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToRecordReloadTest.php) and, through the page, by panels' [`EditRecordRelationshipsTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordRelationshipsTest.php), a repeater item's and a singular relationship's reset included.

### A missing singular intermediate is constructed from its declared type

Type-directed construction is right for a value object and silently wrong for an association that should be looked up; the port constructs **singular** intermediates anyway, on three grounds:

- **The dangerous case is excluded elsewhere.**
  The shape where a wrong construction becomes duplicate INSERTs is a *collection*, and a collection is [refused outright](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused) before it can reach the mapper — the mainstream Doctrine declaration included, since the refusal reads both the adder-derived type and the initialized instance already on the entity.
  The objection guards against a case the collection refusal already removes.
- **Doctrine itself vivifies here.**
  An `#[ORM\Embedded]` write goes through `ReflectionEmbeddedProperty::setValue()`, which instantiates the embeddable through `Instantiator` when the property is null.
  Requiring a user to hand-write the vivification Doctrine performs for itself is the divergence; performing it is the parity.
- **The type is declared, not guessed.**
  Without type information the mapper would have to infer a class from the path.
  With [`RecordTypeReader`](record-type-reader.md) the class comes from the property's own write-side declaration, gated on instantiability, so "construct what the record says goes here" is a reading of the record rather than an inference about it.
  A singular association that should be *looked up* is a field concern — a `Select` over related records — not a nested branch, and that surface is [`Select::relationship()`](../forms/select.md)'s either way, which the walk never sees.

Construction needs no declaration of its own: `record(SomeClass::class)` plus nested state paths is the entire user-facing flow.
What remains loud: a declared class whose constructor takes required arguments throws rather than being half-built, and Doctrine's constructor-bypass trick is deliberately not copied (`doctrine/instantiator` is not in the graph, and a bypassed constructor leaves typed properties uninitialized — one crash traded for a later, stranger one).

### A Serializer-based mapper is rejected for collection population

One reason decides it; two others are answered.

**Answered.**
One objection — a denormalizer constructs where an edit must mutate the managed entity — is answered by Serializer's own `AbstractNormalizer::OBJECT_TO_POPULATE`.
Another — that the type graph rather than the schema would decide what maps, inverting authority at exactly the unbuilt surface (`dehydrated(false)`, multi-value fields, associations) where we want "not supported" to fail loudly — is answered by Serializer's `attributes` context option, which takes a **nested** tree: a schema can hand it the same declared leaf paths `getRecordPathMap()` already produces, and the schema stays in authority over what maps.
That was checked, not assumed.

**Decisive on its own.**
`deep_object_to_populate` updates singular children but **not arrays of objects** — it produces a fresh instance per item.
For Doctrine that is not a mapping error but a duplicate INSERT at flush, plus deletion of the originals under `orphanRemoval`; measured on a two-tag `Post`, re-saving after renaming one tag and deleting the other yielded one brand-new `Tag` row and reused neither original.
That is precisely the collection shape the `Repeater` and its relationship mode need, and it is the shape this mapper [refuses outright](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused) rather than getting wrong.
Since identity matching is a state-shape decision the repeater has to make anyway, adopting Serializer would buy the singular half we already have and leave the hard half untouched — at the cost of a second exception vocabulary.
A lesser point still holds: `normalize($record)` is over-broad against the read-scoping stance and initializes Doctrine proxies wholesale.

Serializer remains a candidate for leaf-level *casts* (dates, enums, UUIDs), where its normalizer half fits the existing `StateCast` seam.
The whole verdict reverses if `deep_object_to_populate` ever grows identity-aware collection population.

### `getRecordInstance()` constructs with `new`, not the container

Filament's `getModelInstance()` ends in `app($this->model)`, Laravel's container resolving an Eloquent model class.
Symfony's container cannot make an unbound class, and an entity is not a service, so Sylament builds the class itself, through `PropertyAccessor::constructRecord()`.

For a constructor with no required parameter that is `new`.
A constructor that requires arguments gets a `LogicException` naming the class and the way out: *Cannot map a new record onto the schema: App\Entity\Membership requires constructor arguments …*.
`CreateAction`'s relationship arm builds its record the same way.
A class-string naming a class with a non-public constructor, or no class at all, surfaces as `ReflectionException`, where `new` would throw PHP's `Error`.

The resource create page builds its record itself: `CreateRecord::makeRecord(array $data): object`, called once with the validated and mutated form data, answers the record the form is then mapped onto, so a record whose constructor takes arguments is built there; its default builds the resource's record class through the same `constructRecord()`, with advice naming `makeRecord()` ([the page](../panels/create-record.md)).
Neither the page nor `CreateAction` shows the schema tier's *"through `record()`"* advice, which a form bound to a class-string could not follow: the page names `makeRecord()`, and both arms of `CreateAction` name `using()`.

This is the same boundary as [`Component::newInstance()`](../support/component.md)'s fallback: the container builds what was registered, and a record is built without arguments or refused by name.

### The root `$record` property is protected

Filament declares the root-tier property `public Model | array | string | Closure | null $model` while the component tier's is `protected`; the asymmetry is a Livewire artifact (a public property is what Livewire's serializer can see), not an API decision.
Sylament declares both `protected`, translating the artifact away like the other Livewire idioms.
Nothing reads the property from outside; `getRecord()`/`getRecordClass()`/`getRecordInstance()` are the surface.

### `fill($object)` does not bind the record

Passing an object to `fill()` sources initial state from it and nothing more — it does not call `record()`.
Binding is a schema-definition concern (`record()` alongside `operation()` and `statePath()`, rebuilt on every request), while `fill()` is a one-shot mount-time action; conflating them would mean a host that fills on mount silently loses its binding on the next request, when `fill()` is not called again.
So an edit page writes both: `record($entity)` in the schema definition and `fill($entity)` in `mount()`.
