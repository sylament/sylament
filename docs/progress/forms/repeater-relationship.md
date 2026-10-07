---
description: '`Repeater::relationship()`: a Doctrine collection mapped item by item, its fill, save, order and mutate arms, and relation-backed fields inside an item'
ports:
  - vendor/filament/forms/src/Components/Repeater.php
owns:
  - packages/forms/src/Components/Concerns/BelongsToRelationship.php
---
# Repeater relationship (forms)

The relationship arm of [`Repeater`](repeater.md), whose class, templates and remaining surface that file owns; every member below is a method of [`Repeater.php`](../../../packages/forms/src/Components/Repeater.php).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`relationship()`](../../../packages/forms/src/Components/Repeater.php) — over a Doctrine collection | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) · [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `relationship()`'s `$modifyQueryUsing` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `relationship()`'s `$modifyRecordsUsing` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | — |
| [`hasRelationship()` / `getRelationshipName()`](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | — |
| [`orderColumn()` / `getOrderColumn()`](../../../packages/forms/src/Components/Repeater.php) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) · [diverges](#the-order-stamp-is-0-based-written-onto-the-child-and-out-of-the-callbacks-reach) |
| [`mutateRelationshipDataBeforeFillUsing()` / `mutateRelationshipDataBeforeCreateUsing()` / `mutateRelationshipDataBeforeSaveUsing()`](../../../packages/forms/src/Components/Repeater.php) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [diverges](#the-fill-callback-may-not-decline-an-item) · [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| The fill and save arms — Filament's `fillFromRelationship()` / `getStateFromRelatedRecords()` / `saveToRelationship()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) · [diverges](#a-new-item-whose-identifier-is-incomplete-is-refused-before-the-save-returns) |
| Relation-backed fields inside an item — Filament's `$item->model($record)->saveRelationships()` | [`Repeater::saveToRelationship()`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map) · [semantics](#an-item-schema-is-stamped-with-the-child-it-maps) · [diverges](#a-mutate-callback-can-rewrite-what-a-nested-hook-consumes) |
| `getRelationship()` / `getRelatedModel()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `getCachedExistingRecords()` / `clearCachedExistingRecords()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `afterCreate()` / `afterUpdate()` / `afterDelete()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `mutateRelationshipDataBeforeFill()` / `mutateRelationshipDataBeforeCreate()` / `mutateRelationshipDataBeforeSave()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `getLabel()`'s relationship-name fallback | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | — |

## Semantics

### A relationship repeater keys its items by identity and diffs the collection

`relationship()` swaps the item pipeline's two ends and leaves everything between them alone.
`fill()` does not read the property (an object collection would be skipped as an object leaf anyway) — the [component-tier load hook](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) reads the collection and returns one item per child, keyed `record-<recordKey>` (Filament's shape, over [`AssociationReader::getRecordKey()`](../schemas/association-reader.md#the-record-key-is-tables-semantics-moved-onto-the-mapping), so an item of a child keyed by two fields is `record-1~zh=20Hans` — [the key's alphabet](../schemas/record-key-contract.md#where-a-key-travels-and-what-each-context-forbids) is what lets that survive the dotted state path, both `HasState` sanitisers and the live `data-model` reserved set), each item filled by reading the item schema's own declared leaf paths off that child through the record accessor.
So the keys are *identity* rather than per-request uuids, and three places follow from it: `hydrateItems()` returns before it re-keys (the map is already keyed, and re-keying would erase the identity), `dehydrateItems()` returns before it positionalises (position is not what the save reads), and an item the add action mints keeps its uuid key — which is precisely how the save tells a new child from a loaded one.

An item leaf is read as the root fill reads one ([the rule](../schemas/belongs-to-record.md#reads-are-lenient-writes-are-strict)): an object-valued leaf enters the item only where the item field's own state casts flatten it, so a `DateTimePicker` or an enum-backed field fills from the child, as does a `Uuid` under a cast that flattens it, and a fill hook receives the object at its item path, while an association, and an object no cast flattens, still skip.
Skipping **every** object leaf would lose data: such a field would fill empty, and the next save — one that never touched it — would write NULL over the stored value.
Pinned by [`RepeaterRelationshipObjectLeafTest`](../../../packages/forms/tests/Integration/Components/RepeaterRelationshipObjectLeafTest.php).

`getData()` hands the whole keyed map to the save hook, which builds the **desired list** in state order: a `record-` key resolves to the very instance the collection holds (never a fresh reference by key — strict identity is what makes an update an update, and it would hold on [either of `getReference()`'s routes](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route) anyway, since each answers the identity map's own instance), an un-prefixed key constructs the association's target class through [`PropertyAccessor::constructRecord()`](../schemas/property-accessor.md#the-mappers-construct-or-refuse-is-one-method-its-callers-borrow) — the mapper's shared construct-or-refuse, so an item class needing constructor arguments is refused in the same words everywhere — and a loaded child no item names is simply left out.
The list goes to [`setAssociationOnRecord()`](../schemas/property-accessor.md#the-association-write-is-the-collection-refusals-counterpart), whose adder/remover diffing keeps the collection instance, calls `addSection()` for the new children (so the owning side is set by the record's own code) and `removeSection()` for the departed — which `orphanRemoval` turns into a DELETE at flush.
Nothing is deleted by the repeater itself, which is why an association not mapped `orphanRemoval` is refused rather than silently leaving rows behind — and refused at **fill**, like the array mode's own early refusal, so the mapping is corrected before anyone types into the form rather than at the save that would have lost the deletion.
A to-one association is refused there too: a repeater over one has nothing to repeat.
Item fields are written per declared leaf path with the *child* as the root, so a dotted item path (`address.city`) reaches through the child's own graph on [`setOnRecord()`](../schemas/property-accessor.md#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs)'s terms.

Several consequences follow.
A `record-` key naming a child the collection does not hold **throws** — it means the record changed under a filled form, and resurrecting it as a new row would be worse than a refusal.
The clone action mints a uuid key, so cloning a loaded item creates a *new* record rather than double-writing the old one.
`orderColumn()` stamps `0..n-1` in state order onto each child before the write, and the read side is Doctrine's — the association needs its own `#[ORM\OrderBy]` on that property, since nothing here orders the query.
A mutation callback returning **null** declines the item, Filament-exact: before-create skips constructing it (nothing is written, nothing joins the desired list), before-save skips writing it but the child stays in the desired list, so declining a save is not a deletion.
And the whole diff runs inside the single `getData()` call, with no flush of its own: the caller flushes, once, exactly as with every other mapped field.

An added item keeps its uuid key until something reads the collection back, and the save does not.
`EditRecord` does: it calls [`Schema::loadStateFromRelationships()`](../schemas/belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) after its flush — Filament's own post-save step — which runs this load hook again and keys every item `record-<key>` by the identifier the flush gave it, so the next save updates what this one created.
Without it the next save constructs the item again: an `EntityIdentityCollisionException` for an assigned key, and a `DELETE` plus an `INSERT` of every added item, on every save, for a generated id.
A host that flushes itself adds that one call after its flush.

Reorderability is coupled to the order column, and the coupling is call-order-sensitive exactly as in Filament.
`relationship()` ends with `reorderable(false)`, because a collection whose order nothing persists would let the user arrange rows that come back in Doctrine's order on the next load.
`orderColumn()` ends with `reorderable(fn (Repeater $component): bool => $component->getOrderColumn() !== null)`, which switches it back on — so `relationship()->orderColumn('position')` is reorderable and `orderColumn('position')->relationship()` is **not**, the later call winning either way.
Ours reads the column through a closure rather than passing it as Filament's truthy `reorderable($column)` does, so a closure-valued column is resolved at evaluation time rather than counted as truthy while unresolved, and `orderColumn(null)` reads as not reorderable rather than as the literal `null` Filament would hand `reorderable()`.

### A nested relation-backed field is routed by the item, not by the map

`Select::make('author')->relationship()` inside a `relationship()` repeater — the join-entity-with-a-payload shape — maps nothing of its own: the repeater [owns its record subtree](repeater.md#a-repeater-owns-its-record-subtree), so the mapper drops every path beneath it, and a hook on a dropped path [is refused](../schemas/belongs-to-record.md#a-hook-the-record-path-map-does-not-carry-is-refused) unless the owner [speaks for it](../schemas/belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused).
`routesNestedRelationshipHookAt()` is the repeater speaking: an owner-relative path naming an item and then something the item template declares a hook at is one this repeater routes, and both edges then run it against the *child*, which is the only record the hook could ever have meant.

The vouch answers from the walk that routes, so the two cannot disagree — and the walk is [`Schema::getNestedRelationshipHooks()`](../schemas/belongs-to-record.md), whose keys are already item-relative.
Both halves of the path are checked, and the first half is the one easy to skip: the leading segment has to name one of the items the repeater currently holds, and only then is the remainder matched against the template's hooks.
What that leaves refused is deliberate and loud: a hooked component in a **decoration** schema (`belowContent()` and its nine siblings hang off the repeater itself, and neither edge walks them) names no item, so the vouch says no and the map refuses it by name rather than letting it never fire — and a *dotted* decoration path is refused for that same reason, rather than vouched for by a remainder that happens to spell an item's hook.

The two edges discover from different places, and the asymmetry is forced.
`fill()` runs before a single item exists — the raw state is still empty, so `getDefaultChildSchemas()` has no keys to build from — and reads the **template** child schema, off a clone, the same discipline [`getItemStatePaths()`](repeater.md#every-item-schema-is-keyed-as-well-as-re-pathed) uses.
The save has the real thing, so it reads the **per-item** schema, whose components are the very ones the map dropped.
It asks for that schema only where the template declares a **save** hook — the one edge that reads it — and an item key the built schemas do not cover, one a caller added by hand to the array it passed `getData()`, is built on the spot exactly as they are.
So a hand-supplied item routes the hooks its template declares, and behaves the same whether or not the repeater carries one; the only shape left to throw is a repeater with no item schema at all, which has nothing to route from.

Claimed paths leave the leaf walk — the hook's own key and everything under `key.` — and the subtraction is **per edge**, mirroring the root edges exactly: the fill routes the load-hooked keys and leaves save-only ones their ordinary leaf read, the save routes the save-hooked keys and leaves load-only ones their ordinary `setOnRecord()`.
`getItemStatePaths()` itself is untouched: the delegated paths stay in it, because the [violation translator](../schemas/violation-path-translator.md) attributes item violations through it.

Order inside one item is mutate → construct → leaves → nested hooks → order stamp → collection write, so the [identity pairing](repeater.md#a-save-records-which-collection-key-each-item-was-written-to) that follows the write sees children nothing is still going to touch.
An added item needs no exists-gate: the hook mutates the in-memory child and the caller's single flush sorts the INSERTs.
And recursion needs no code at all — a nested relationship repeater is itself a hook-carrying component, so the discovery yields it as one delegated entry, and its own body repeats the whole scheme one level down against the child it was handed.

### An item schema is stamped with the child it maps

The routing above would still hand a nested field the *wrong* record without a stamp, so each item schema carries one, in Filament's own two arms: a `record-` item resolves the child off the owner's collection by [record key](../schemas/association-reader.md), and a client-added item, having no child yet, floors at the association's target class-string.
Ours stamps the class-string one level earlier as well — on the item **template**, the `default` key every item schema is cloned from — because the fill discovers from that template, before an item schema exists to have been stamped.

The stamp stops at that key, and the boundary is the same one the vouch draws.
A **decoration** schema (`belowContent()` and its nine siblings) belongs to the repeater rather than to an item, neither edge walks one, and its components map nothing of the child — so framing one on the child would answer every `$record` inside it with `null` (a `Schema` holding a class-string returns no instance and does not walk to its parent for one) and would make it the nearest record frame for a derivation that means the owner.
Decoration schemas keep the owner's record, inherited, exactly as they did before `relationship()` was declared.

The floor is load-bearing rather than tidy.
`Schema`'s record getters walk to the parent when their own binding evaluates to `null`, so a stamp that could resolve null would silently hand a nested `Select::make('author')` the **owner's** record — and an owner mapping an association of the same name is exactly the shape a join entity sits in, so the wrong answer would be a working one.
A class-string never falls through, so the only tree where a stamp resolves nothing is one with no record class anywhere, where the parent chain answers nothing either.

Resolution is **soft** on both arms — the association is described, never required — because the stamps are read from rendering, from `getItemStatePaths()` and from every flat walk, and an eager `require*()` there would relocate the fill and save edges' refusals into all of them.
The cost is one accepted freeze: `Schema` memoizes its evaluated record, so the first read of an item schema wins and a structural read taken before the owner was bound can leave the class-string where a later read would have produced the instance.
Almost nothing depends on which arm won — a hook receives its record explicitly, and target resolution and the constraint frame need only the class.
The exception is `unique()`'s ignore arm, which reads the **instance** (a class-string binding answers `getRecord()` with `null` and does not walk to the parent for one), so a frozen item schema degrades it to no-ignore and the row being edited can be flagged as its own duplicate.
No traced lifecycle reaches that freeze — every read of an item schema happens after the owner is bound — but it is the reason the freeze is recorded rather than dismissed.

The stamp reaches past the routing, and that is the point rather than a side effect: every closure inside a stamped **item** is injected the child as `$record`, and everything derived from `getRecordClass()` follows it — a nested field's own association resolution, and the entity a `unique()` or `exists()` probes when it names none.
Filament reads the same way (`getRecord()` walks to the nearest `model()`), and it is what makes the field's target and the record it is validated against agree; a component that means the *owner's* record inside an item has to name it.

## Divergences

### The relationship family loses its caches, its lifecycle hooks and its query scope

Several members of Filament's relationship surface have no counterpart here, each for a stated reason rather than as deferral.

`getRelationship()` and `getRelatedModel()` **are not ported**: the first returns an Eloquent relation object, which Doctrine has no counterpart for, and the second's class-string is what the protected `getRelationshipTarget()` resolves off the mapping as an `AssociationTarget`, which every arm here reads instead.

`getCachedExistingRecords()` / `clearCachedExistingRecords()` **are not ported**: Filament caches the related rows because its fill and its save each run their own Eloquent query.
Ours reads the collection off the record through the accessor both times, and the collection *is* the cache — Doctrine's identity map already guarantees the same instances come back, which is the very property the strict-identity update depends on.
A memo on top would only add a second source of truth.

`afterCreate()` / `afterUpdate()` / `afterDelete()` **are not ported**, and this one is a real capability gap: Filament fires them around its per-item `save()`/`delete()` calls, and in the [single-flush shape](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) no such moment exists — `getData()` mutates an object graph and returns, so at the time the repeater is done nothing has touched the database and no row has an identifier yet.
Firing them at map time would be a lie about when they run.
Consumers that need a post-write moment use Doctrine's own lifecycle events (`postPersist`, `postUpdate`, `postRemove`, or an `onFlush` subscriber) — the same seam the ORM already gives every other write in the application.
Reverses only if a `saveRelationships()` phase with its own flush is ever adopted, which the port [decided against](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk).

The three public callers `mutateRelationshipDataBeforeFill()`, `mutateRelationshipDataBeforeCreate()` and `mutateRelationshipDataBeforeSave()` **are not ported**: Filament's own fill and save arms are their callers, and ours run the three callbacks through one private helper that injects the item array and, on a save, the child.
The setters that register the callbacks ship.

`dehydrated(false)` **is not applied**.
Filament prunes the repeater's state from the dehydrated bag because its record save would otherwise try to write an array into a relation.
Our dehydration walk is what *routes* the value: `getData()` consults the save hook only for a path the bag still carries, so pruning would switch the whole feature off.
The state stays in the bag and the hook takes it.

`relationship()`'s second parameter, `$modifyQueryUsing`, is **accepted and refused**.
The signature stays Filament's so the call site ports unchanged, but the items come from the collection the record already holds rather than from a query of our own, so a scope would silently never run — and a silent no-op is exactly what this codebase refuses.
Passing one throws, naming Doctrine's `#[ORM\OrderBy]` and a filtered collection as the two places that scoping actually belongs.
Reverses if the fill ever grows a query of its own (a paginated or filtered relationship repeater), at which point the parameter gets its Filament meaning back.

Two smaller shapes go with them.
The mutation callbacks are evaluated with the item array injected as **`state`**, not Filament's `$data` — every other mutation seam in the port names it `state` (`mutateDehydratedStateUsing`, `dehydrateStateUsing`), and the evaluation vocabulary is one word per meaning; `mutateRelationshipDataBeforeSaveUsing` additionally receives the child as `record` **and** typed by its own class, so `fn (PostSection $section)` resolves the way the [component-tier hooks](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) already inject the record.
And `simple()`, `itemsAs()` and `relationship()` are a **three-way** mode exclusion, each pair refusing in either declaration order for the reason that pair fails: the simple pluck would reduce a related record to one scalar, and a freshly constructed value object would throw away the identity the relationship exists to preserve.

### A new item whose identifier is incomplete is refused before the save returns

Filament has no counterpart: an Eloquent child is keyed by an auto-incrementing `id`, so a repeater item cannot be missing a key half.
A Doctrine child can, and the failure it produces is worse than an exception.
The repeater assigns the owner onto each new child and hands the desired list to the accessor; the caller's flush then **commits** the row, because an identifier component the item schema never collected sits at its property default — `''` for a typed `string` — which Doctrine writes without complaint.
The row exists, no key names it, and the next fill of that repeater throws `is not a printable value` reading it back.
One forgotten field in `components()` is enough.

So the save asks [`AssociationReader::hasIncompleteAssignedIdentifier()`](../schemas/association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) of every child it **constructed** this pass, and refuses by name:

> `The [%s] repeater is saving an item of [%s] whose identifier is incomplete, so no record key can be minted for it. Put every identifier field in the repeater's components() — enabled and dehydrated for a new item — or build the child in mutateRelationshipDataBeforeCreateUsing().`

Four things about the rule, each of which the message alone gets wrong.

**It is by *assignment*, and only for a composite or association-carrying identifier.**
A class whose identifier Doctrine generates is never incomplete — the id is the ORM's to fill at the insert — so the refusal cannot fire for the ordinary `id`-keyed child, whatever shape it is.
Neither can it fire for a **plain single scalar** identifier the application assigns: the member answers `false` for one at the entry, before it reads a field, because such a class has always been keyable here and an application may fill that field from a listener no mapping shows.
What is left is a composite key (Doctrine refuses an id generator on one) or an identifier carrying an association — minus whatever a `#[PrePersist]` callback of the class or a mapped entity listener fills at the flush.

**A class-level `prePersist` callback lets an unmapped blank half through.**
The mapping short-circuit exempts every class declaring a `prePersist` callback or entity listener, whether or not it fills a key half.
`CreateAction`, the resource create page and `Select`'s `createOption` default read the key again after `persist()`, once the callback has run; the repeater has no such moment, since it never persists an item itself — the owner's cascade inserts it at the application's flush.
So for such a class a key half no component maps reaches the table blank.
Closing it needs a check at the owner's flush, which the repeater does not run.
Pinned as behaviour by [`RepeaterKeyWritesTest`](../../../packages/forms/tests/Integration/Components/RepeaterKeyWritesTest.php)'s *writes a new item whose unmapped key half stays blank past a prePersist callback that fills neither half*.

**A pending owner is not incomplete.**
A derived-identity child under an owner that has not been flushed yet — a repeater on a **create** form — carries an identifier association whose target's generated id is still pending, and Doctrine writes both in one flush, deferring the child's identifier to the parent's insert.
Reading that as "incomplete" would refuse a working create page with advice that is false.

**It fires for a field that never reaches the item's state**, which is not the same as a field absent from `components()`.
A declared component left blank dies earlier and better, as the field's own `NotBlank`, with zero statements and an error beside the input.
But a key half declared and then `hidden()` or `disabled()` is refused here too, because `disabled()` also marks the field unsaved and an unsaved field contributes nothing to the new child — which is why the message says *enabled and dehydrated for a new item* rather than merely *in `components()`*.
`->disabled()->dehydrated()` saves, and an **existing** item with a disabled key half saves either way, since nothing is being constructed.
What is left is a developer error rather than something an end user can answer — so it throws, and it restores nothing: the collection write has already run in memory, the item-key map is deliberately **not** re-read, nothing has been flushed, and the request ends.
The refusal is therefore not a transaction guard: a caller that catches the `LogicException` and then flushes anyway still commits the half-keyed row it was warned about.
Validating a key that is *taken*, or a key half edited on an existing item, is the record check's, which reaches every item through the owner's loaded collection.
A new item on a key a stored row, an existing item or another new item claims is *"This value is already used."* under every identifier field of the new item that is no association and that the item's schema maps — a join entity's, whose identifier is associations only, under the one that does not point back at the owner ([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)) — and an existing item's key half edited is *"This value cannot be changed."*, put back, with nothing written ([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)).
Two new items claiming one key are compared in memory, so under an owner that has no identifier yet the second is refused without a statement.
A new item on the key of an item the **same** save removes is refused too, and the message blames the key the user just removed: Doctrine inserts before it deletes, so that save could not succeed either way — recorded rather than closed.
Pinned by [`RepeaterKeyWritesTest`](../../../packages/forms/tests/Integration/Components/RepeaterKeyWritesTest.php), beside [`RepeaterCompositeRelationshipTest`](../../../packages/forms/tests/Integration/Components/RepeaterCompositeRelationshipTest.php).

### A mutate callback can rewrite what a nested hook consumes

The [nested routing](#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map) runs mutate → leaves → hooks per item, so `mutateRelationshipDataBeforeSaveUsing()` sees the raw item array **including** the delegated keys and may rewrite the identifier a nested `Select` submitted before the hook reads it.
Filament orders the two the other way around: its schema tree walk runs a nested component's `saveRelationships()` children-first, *before* `saveToRelationship()` reaches the item's own `mutateRelationshipDataBeforeSave()` and `fill()`, so over there the callback cannot reach what a nested hook already consumed.

The inversion is a consequence of the stance rather than a preference about callbacks: we have no tree walk to run first, because the [single-flush invariant](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) collapses the whole recursion into the two owner bodies, and once the hook runs inside the item loop the only question left is which side of the mutate callback it lands on.
Rewritable is the more useful of the two contracts — a callback that can normalise or substitute a nested identifier is worth more than one that cannot — so the hook reads what the callback returned.
A callback declining the item (returning `null`) skips its nested hooks along with everything else, since the item as a whole is declined.

### The order stamp is 0-based, written onto the child, and out of the callbacks' reach

Filament stamps `$itemData[$orderColumn] = $itemOrder` with `$itemOrder` starting at **1**, *before* either mutation callback sees the item — so a hook reads the position it is about to be given and may overwrite it, and the value reaches the record through the same `fill()` that writes every other key (`vendor/filament/forms/src/Components/Repeater.php:1035-1046`).
Here the stamp is `0..n-1`, written straight onto the child with [`setOnRecord()`](../schemas/property-accessor.md#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs) *after* the callbacks have run, and never present in the item array at all.

Two reasons, one per half.
**0-based** because the number is the element's index in the collection the save is building (`count($desiredRecords)`), not a human-facing rank — nothing here renders it, and the read side is Doctrine's `#[ORM\OrderBy]`, which sorts either numbering identically.
**Outside the callbacks** because the port writes the item array through the item schema's *declared* leaf paths, and an order column is precisely the property no field declares: a key a callback added under that name would be dropped on the way to the record, so offering it there would be a seam that silently does nothing.

The cost is real and bounded: a callback cannot read or rewrite the position, which Filament's can.
Reverses if a case turns up for reordering from a hook — the stamp would move into the item array before the callbacks and be written from there, alongside the declared paths rather than through them.

One consequence of the [null-skip](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) arm follows from the ordering half.
A child whose before-save callback declined it is kept in the desired list but never written, so it keeps whatever position it already had while the items around it are restamped — the same outcome as Filament, where a `continue` skips the `fill()` that would have carried the stamp.
A declined **create** costs no number here (the stamps stay contiguous), where Filament's `$itemOrder` has already been incremented and leaves a gap.

### The fill callback may not decline an item

`mutateRelationshipDataBeforeCreateUsing` and `mutateRelationshipDataBeforeSaveUsing` return `?array` because Filament's do — null declines the item.
`mutateRelationshipDataBeforeFillUsing` returns `array` in Filament too, and here a null from it **throws** rather than being read as a skip: the fill is showing the children the record already holds, so declining one would hide a row the user is editing, and the state the form is built from would disagree with the collection the save diffs against.
The refusal names the callback and says which two may decline.
