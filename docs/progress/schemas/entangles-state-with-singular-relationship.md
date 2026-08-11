# EntanglesStateWithSingularRelationship (schemas)

[`Sylament\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php)
binds a layout component's whole subtree to a **to-one** Doctrine association.
`Section::make()->relationship('profile')->components([TextInput::make('bio')])`
makes `profile` the component's state path, so its fields nest under the relation
name, and makes the component own that path in the record walks: `fill()` reads
the related record's leaves through the load hook, and `getData()` writes them
back onto it — mutating the record already there, constructing one when there is
none, and deleting it when the declared condition turns the relationship off.

Four components compose it — [`Section`](section.md), [`Grid`](grid.md),
[`Group`](group.md) and [`Fieldset`](fieldset.md) — each implementing the
[`CanEntangleWithSingularRelationships`](../../../packages/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php)
contract, which is what the wired hook closures type-hint.

The mechanics it stands on are elsewhere: the hook pair and the record path map
in [`belongs-to-record.md`](belongs-to-record.md), the mapping reads in
[`association-reader.md`](association-reader.md), the graph reads and writes in
[`property-accessor.md`](property-accessor.md).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php)
  — the trait, its two wired closures, and the cached-existing-record surface
- [`schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php)
  — the contract the closures type-hint

## Surface

Every member of the Filament trait and its contract. _Blocked by_ names what has
to land first. _Detail_ carries either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`relationship(name, condition)`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`relationship()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-relatedmodel-argument-is-dropped-the-mapping-names-the-target) · [diverges](#the-name-is-resolved-once-at-declaration) |
| [State path + subtree ownership](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`relationship()`'s `statePath()` + `dehydrated(false)`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [semantics](#subtree-ownership-not-dehydratedfalse-is-what-makes-the-hooks-fire) |
| [Fill from the related record](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`fillFromRelationship()` / `getStateFromRelatedRecord()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-subtree-is-read-and-written-per-declared-leaf-not-as-an-attribute-bag) |
| [Save onto the existing related record](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`saveRelationshipsBeforeChildrenUsing()`'s `$record` arm](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | — |
| [Construct the related record when there is none](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`saveRelationshipsBeforeChildrenUsing()`'s `new $relatedModel` arm](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [semantics](#a-constructed-related-record-is-written-through-the-property-so-the-mapping-persists-it) |
| [Delete when the condition is false](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`$record?->delete()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#a-switched-off-relationship-nulls-the-property-and-removes-the-record-explicitly) |
| [`hasRelationship()` / `getRelationshipName()`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`hasRelationship()` / `getRelationshipName()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | — |
| [Association resolution + refusals](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`getRelationship()` / `getRelatedModel()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-relation-object-becomes-an-associationtarget-and-the-morphone-arm-is-cut) |
| [`CanEntangleWithSingularRelationships`](../../../packages/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) | [`Contracts/CanEntangleWithSingularRelationships.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) | done | — | [diverges](#the-contract-carries-the-two-hook-bodies-not-the-cache-surface) |
| Sibling merge across components sharing one relationship | [both closures' `findComponentsWithThisRelationship`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | skipped | — | [diverges](#two-components-entangling-one-relationship-throw-rather-than-merge) |
| `cachedExistingRecord()` / `getCachedExistingRecord()` / `clearCachedExistingRecord()` | [the cache trio](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | skipped | — | [diverges](#the-existing-record-is-read-off-the-graph-rather-than-cached-on-the-component) |
| Child schema re-rooted onto the related record | [`getChildSchema()` override](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | todo | — | [detail](#the-child-schema-is-not-re-rooted-onto-the-related-record) |
| `mutateRelationshipDataBeforeFill/Create/Save` | [the three mutation hooks](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | todo | — | [detail](#the-three-mutation-hooks-are-not-ported-here-yet) |
| Hidden-component skip (`shouldSaveRelationshipsWhenHidden()`) | [the save closure's `isHidden()` guard](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | todo | `shouldSaveRelationshipsWhenHidden()` | [detail](#the-hidden-component-skip-waits-on-its-flag) |
| Translatable content driver | [`makeFilamentTranslatableContentDriver()` branches](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | skipped | — | [diverges](#the-translatable-content-driver-has-no-port) |

Covered by
[`EntanglesStateWithSingularRelationshipTest`](../../../packages/schemas/tests/Integration/Components/Concerns/EntanglesStateWithSingularRelationshipTest.php),
which drives a `Section` over `Post::$profile` against the real SQLite rig: fill
from an existing profile, fill to nulls without one, an edit that holds the
profile's identity across a flush, a create-when-missing that the cascade
persists, a `condition: false` save that is gone after the flush, and the four
refusals (duplicate siblings, a property with no association, a to-many
association, a `relationship()` naming nothing).

## Details

Specifics for rows that are not finished — what is already in, what is left, and
the next step. Delete a subsection when its row flips to **done**; the table row
and any divergence it earned stay behind.

### The child schema is not re-rooted onto the related record

Filament overrides `getChildSchema()` to `->model($cachedExistingRecord ??
$relatedModel)`, so the fields inside the entangled component see the *related*
record rather than the schema's. The port leaves the child schema bound to the
outer record, and nothing inside it reads the wrong one today: state lives in the
live bag, and the model-sourced validation constraints read
`getRootContainer()->getRecordClass()` — the root schema — so re-rooting an
intermediate child schema would not reach them anyway. What it would buy is a
nested component asking `getRecord()` for the related object. Next step: give the
trait a `configureChildSchema()` override once a consumer needs it, and move the
constraint lookup off the root container in the same change, so the two agree.

### The three mutation hooks are not ported here yet

`mutateRelationshipDataBeforeFillUsing()` / `…BeforeCreateUsing()` /
`…BeforeSaveUsing()` rewrite the state array on its way to and from the related
record. Forms' [`Repeater`](../forms/repeater.md) ports all three for its items,
so the shape and the "return the array you were handed" refusal already exist to
copy. Left out here only because nothing in the epic needed them on a layout
component. Next step: lift the trio out of `Repeater` into a shared concern when
the second consumer arrives, rather than writing a third copy.

### The hidden-component skip waits on its flag

Filament's save closure skips a component that `isHidden()` and does not
`shouldSaveRelationshipsWhenHidden()`. The port has `isHidden()` but no
`shouldSaveRelationshipsWhenHidden()` surface at all
([`can-be-hidden.md`](can-be-hidden.md)), and the walks it would guard prune
hidden components' state before the save hook ever runs. Next step: port the flag
with the rest of the hidden-state surface, then decide whether this guard is
still doing anything the pruning does not.

## Semantics

Faithful-but-subtle behaviour — permanent notes that are not deviations.

### Subtree ownership, not `dehydrated(false)`, is what makes the hooks fire

Filament ends `relationship()` with `$this->dehydrated(false)`, so the layout
component contributes no state of its own and its children dehydrate normally.
The port instead makes the component
[own its record subtree](belongs-to-record.md): `ownsRecordSubtree()` answers
true whenever a relationship is declared, which is what makes the record path map
carry the component **at the association's path** and drop every path collected
beneath it. That is the load-bearing half — the hooks are only ever consulted for
a path the map carries, so without the ownership the map would take
`profile.bio` from the child field and the hooks would be refused at map time as
unreachable. Ownership is keyed on the relationship being *declared*, not on its
condition: a `condition: false` component still owns its subtree, which is how
its save hook gets the chance to delete.

### A constructed related record is written through the property, so the mapping persists it

When the record holds no related object, the save builds the association's target
(refusing a constructor with required arguments, in
[`PropertyAccessor::constructIntermediate()`](property-accessor.md)'s words),
maps the submitted leaves onto it, and writes it onto the record through
`setOnRecord()` — the record's own setter. In the test rig that setter is
`Post::setProfile()`, which maintains the owning side, and the association's
`cascade: ['persist']` inserts the new row at flush. Nothing is persisted by the
component: a related record whose association declares no cascade needs the
caller's own `persist()`, exactly as an unrelated entity would.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The `relatedModel` argument is dropped; the mapping names the target

Filament's third argument, `string | Closure | null $relatedModel`, exists
because an Eloquent relation object is the only place the related class can be
read from and a morph relation cannot answer it. Doctrine's `ClassMetadata`
answers it directly —
[`AssociationReader::describe()`](association-reader.md) returns the target class
with the rest of the mapping — so the argument would only ever let a caller
contradict the mapping. Dropped: `relationship()` takes the name and the
condition, and the target comes off `ClassMetadata`.

### The name is resolved once, at declaration

Filament types `relationship(string $name)`, a plain string. The port widens it to
`string|Closure|null` for consistency with every other setter, but evaluates the
closure *there and then* rather than per read, because the name is also the
component's state path and the whole child tree is built from it. A name that
would differ between two evaluations was never expressible in Filament either. A
blank name is refused on the spot: a layout component carries no name of its own
to fall back on, unlike a field.

### The subtree is read and written per declared leaf, not as an attribute bag

Filament fills from `$record->attributesToArray()` and saves with
`$record->fill($data)->save()` — the model's whole attribute bag, filtered by
its `$fillable`. There is no Doctrine equivalent, and reflecting an entity's
properties would map columns no field asked for. The port iterates the **declared
leaf paths** of the component's child schemas instead — the same discipline the
[relationship repeater](../forms/repeater.md) uses per item — so a property no
field declares is never read and never written. Unreadable and object-valued
leaves are skipped on the way in, and a leaf the dehydrated state does not carry
is skipped on the way out.

### A switched-off relationship nulls the property and removes the record explicitly

Filament's `condition: false` arm is `$record?->delete()`. The port nulls the
association property and calls
[`AssociationReader::remove()`](association-reader.md#remove-is-here-so-orphan-deletion-does-not-grow-a-second-seam),
and does **not** require the association to declare `orphanRemoval`. Two reasons
the repeater's opposite stance does not transfer. Deleting is what the developer
declared here — `relationship(condition: false)` states that this record must not
exist — where a repeater item disappearing is an end user editing a list, whose
ownership only the mapping can settle. And nulling alone would not be enough on
an inverse side: the test rig's `Post::$profile` is the inverse of
`Profile::$post`, so writing null there changes no foreign key and, without
`orphanRemoval`, would leave the row behind pointing at its parent. The property
is still nulled so the in-memory graph agrees with the database, and it is nulled
through the accessor, so a property that rejects null is refused with a message
rather than a bare type error.

### The relation object becomes an `AssociationTarget`, and the MorphOne arm is cut

Filament returns `BelongsTo | HasOne | MorphOne | null` from `getRelationship()`
and branches on the class — `BelongsTo` saves the related record first and
associates it, the others save through the relation. The port has no relation
objects: it asks the reader for an
[`AssociationTarget`](association-reader.md) and writes through the record's own
setter, which is the same write on either side of a Doctrine to-one. `MorphOne`
has no port at all — morphs are out of the epic's scope, and Doctrine's
inheritance mapping is a different shape rather than a missing feature. A
to-**many** association is refused with a message pointing at the Repeater, since
one set of fields cannot map a collection.

### Two components entangling one relationship throw rather than merge

Filament lets several layout components share one relationship: each closure
walks the model-root container collecting every component that names the same
relationship, the first one fills, and the first one saves the merged state of
all of them. The port refuses instead — two components claiming the same record
path, one of them owning its subtree, is a `LogicException` from the record path
map naming both classes. The merge is real ergonomics (a wide form splitting one
related record across two boxes), but it needs a cross-component identity rule
the walks do not have: our map is keyed by record path, so the second component
would silently replace the first. **Provisional:** reversed by teaching the map
to fold several subtree owners at one path into one entry, at which point the
save can concatenate their subtree states the way Filament's does.

### The existing record is read off the graph rather than cached on the component

Filament caches the related model on the component (`cachedExistingRecord()`,
read back by both closures and by `getChildSchema()`), because reaching it means
a query through the relation. Doctrine's identity map already answers the same
read from memory, and the property read is the association itself, so the cache
would only add a second source of truth that a `clearCachedExistingRecord()` has
to keep honest. Both hooks read `getFromRecord($record, $name)` instead.

### The contract carries the two hook bodies, not the cache surface

Filament's `CanEntangleWithSingularRelationships` publishes the cache trio,
`fillFromRelationship()`, `getRelatedModel()`, `getRelationship()` and the three
mutation hooks. Ours publishes `getRelationshipName()`, `hasRelationship()` and
the two methods the wired closures call — `getStateFromRelationship()` and
`saveStateToRelationship()` — because that is what the contract is *for* here: a
closure wired inside a trait has no concrete class to type-hint, and the members
it invokes have to be visible on the type it does hint. The dropped members are
either not ported (the cache, the mutation hooks) or not public API here (the
association lookup). `relationship()` itself stays off the contract, as in
Filament.

### The translatable content driver has no port

Both Filament closures branch on
`$livewire->makeFilamentTranslatableContentDriver()`, which routes reads and
writes through a translation package when one is installed. The port has no
translatable-content surface anywhere, so there is no branch to write; it would
arrive with that surface, not with this trait.
