# EntanglesStateWithSingularRelationship (schemas)

[`Sylament\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) binds a layout component's whole subtree to a **to-one** Doctrine association.
`Section::make()->relationship('profile')->components([TextInput::make('bio')])` makes `profile` the component's state path, so its fields nest under the relation name, and makes the component own that path in the record walks: `fill()` reads the related record's leaves through the load hook, and `getData()` writes them back onto it — mutating the record already there, constructing one when there is none, and deleting it when the declared condition turns the relationship off.
The subtree is re-rooted onto that related record, and the relationship hooks declared inside it are [routed there](#the-subtree-is-re-rooted-and-its-nested-hooks-are-routed-onto-the-related-record) too — so a relation-backed field maps the *related* record's own associations.

Five components compose it — [`Section`](section.md), [`Grid`](grid.md), [`Group`](group.md), [`Fieldset`](fieldset.md) and [`Flex`](flex.md) — each implementing the [`CanEntangleWithSingularRelationships`](../../../packages/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) contract, which is what the wired hook closures type-hint.

The mechanics it stands on are elsewhere: the hook pair and the record path map in [`belongs-to-record.md`](belongs-to-record.md), the mapping reads in [`association-reader.md`](association-reader.md), the graph reads and writes in [`property-accessor.md`](property-accessor.md).
The association itself — the name, the target and the refusals — comes from [`BelongsToRelationship`](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php), [shared with forms' relation-backed fields](#the-association-core-is-shared-with-forms-relation-backed-fields).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) — the trait, its two wired closures, and the cached-existing-record surface
- [`schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) — the contract the closures type-hint

## Surface

Every member of the Filament trait and its contract.
_Blocked by_ names what has to land first.
_Detail_ carries either or both: **detail** → the write-up in [Details](#details), deleted once the row is **done**; **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`relationship(name, condition)`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`relationship()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-relatedmodel-argument-is-dropped-the-mapping-names-the-target) · [diverges](#the-name-is-resolved-once-at-declaration) |
| [State path + subtree ownership](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`relationship()`'s `statePath()` + `dehydrated(false)`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [semantics](#subtree-ownership-not-dehydratedfalse-is-what-makes-the-hooks-fire) |
| [Fill from the related record](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`fillFromRelationship()` / `getStateFromRelatedRecord()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-subtree-is-read-and-written-per-declared-leaf-not-as-an-attribute-bag) |
| [Save onto the existing related record](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`saveRelationshipsBeforeChildrenUsing()`'s `$record` arm](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | — |
| [Construct the related record when there is none](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`saveRelationshipsBeforeChildrenUsing()`'s `new $relatedModel` arm](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [semantics](#a-constructed-related-record-is-written-through-the-property-so-the-mapping-persists-it) |
| [A constructed record whose assigned key is incomplete is refused by name](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | — (Laravel has no assigned composite key to leave incomplete) | done | — | [semantics](#a-constructed-record-whose-assigned-key-is-incomplete-is-refused-by-name) |
| [Delete when the condition is false](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`$record?->delete()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#a-switched-off-relationship-nulls-the-property-and-removes-the-record-explicitly) |
| [`hasRelationship()` / `getRelationshipName()`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`hasRelationship()` / `getRelationshipName()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-association-core-is-shared-with-forms-relation-backed-fields) |
| [Association resolution + refusals](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php) | [`getRelationship()` / `getRelatedModel()`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [diverges](#the-relation-object-becomes-an-associationtarget-and-the-morphone-arm-is-cut) · [diverges](#the-association-core-is-shared-with-forms-relation-backed-fields) |
| [`CanEntangleWithSingularRelationships`](../../../packages/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) | [`Contracts/CanEntangleWithSingularRelationships.php`](../../../vendor/filament/schemas/src/Components/Contracts/CanEntangleWithSingularRelationships.php) | done | — | [diverges](#the-contract-carries-the-two-hook-bodies-not-the-cache-surface) |
| Sibling merge across components sharing one relationship | [both closures' `findComponentsWithThisRelationship`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | skipped | — | [diverges](#two-components-entangling-one-relationship-throw-rather-than-merge) |
| `cachedExistingRecord()` / `getCachedExistingRecord()` / `clearCachedExistingRecord()` | [the cache trio](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | skipped | — | [diverges](#the-existing-record-is-read-off-the-graph-rather-than-cached-on-the-component) |
| [Child schema re-rooted onto the related record](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`getChildSchema()` override](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [semantics](#the-subtree-is-re-rooted-and-its-nested-hooks-are-routed-onto-the-related-record) · [diverges](#the-stamp-is-a-closure-floored-at-the-target-class) |
| [Relation-backed fields inside the entangled subtree](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`Schema::saveRelationships()`'s tree walk](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [semantics](#the-subtree-is-re-rooted-and-its-nested-hooks-are-routed-onto-the-related-record) |
| `mutateRelationshipDataBeforeFill/Create/Save` | [the three mutation hooks](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | todo | — | [detail](#the-three-mutation-hooks-are-not-ported-here-yet) |
| Hidden-component skip (`shouldSaveRelationshipsWhenHidden()`) | [the save closure's `isHidden()` guard](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | todo | `shouldSaveRelationshipsWhenHidden()` | [detail](#the-hidden-component-skip-waits-on-its-flag) |
| Translatable content driver | [`makeFilamentTranslatableContentDriver()` branches](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | skipped | — | [diverges](#the-translatable-content-driver-has-no-port) |

Covered by [`EntanglesStateWithSingularRelationshipTest`](../../../packages/schemas/tests/Integration/Components/Concerns/EntanglesStateWithSingularRelationshipTest.php), which drives a `Section` over `Post::$profile` against the real SQLite rig: fill from an existing profile, fill to nulls without one, an edit that holds the profile's identity across a flush, a create-when-missing that the cascade persists, a `condition: false` save that is gone after the flush, a nested container whose *leaves* are written rather than its own path, and the four refusals (duplicate siblings, a property with no association, a to-many association, a `relationship()` naming nothing).
The re-rooting and the nested hooks are pinned there too: a nested component resolving the related record — and, with no related record bound, the target class rather than the outer record — every child-schema key stamped including a decoration one, `Section`'s own child-key configuration surviving the `getChildSchema()` override, a hooked leaf filling from and saving onto the related record while the ordinary leaves keep their walk, a hook declared in a decoration schema routed like any other, and a `condition: false` save that removes the branch without running a nested hook.
The end-to-end shape — a `Select` mapping the *related* record's own association — lives in forms, where a relation-backed field can actually be built: [`EntangledNestedRelationshipTest`](../../../packages/forms/tests/Integration/Components/EntangledNestedRelationshipTest.php) drives `Select::make('handle')` inside `Section::make()->relationship('author')` through fill, a changed selection and a cleared one.
One case runs over a second association, `Post::$byline`, mapped deliberately **without** `orphanRemoval`: switching that relationship off still deletes the row, which is the property the [explicit removal](#a-switched-off-relationship-nulls-the-property-and-removes-the-record-explicitly) exists for and the one an `orphanRemoval` association could never distinguish.

## Details

Specifics for rows that are not finished — what is already in, what is left, and the next step.
Delete a subsection when its row flips to **done**; the table row and any divergence it earned stay behind.

### The three mutation hooks are not ported here yet

`mutateRelationshipDataBeforeFillUsing()` / `…BeforeCreateUsing()` / `…BeforeSaveUsing()` rewrite the state array on its way to and from the related record.
Forms' [`Repeater`](../forms/repeater.md) ports all three for its items, so the shape and the "return the array you were handed" refusal already exist to copy.
Left out here only because nothing has needed them on a layout component yet.
Next step: lift the trio out of `Repeater` into a shared concern when the second consumer arrives, rather than writing a third copy.

### The hidden-component skip waits on its flag

Filament's save closure skips a component that `isHidden()` and does not `shouldSaveRelationshipsWhenHidden()`.
The port has `isHidden()` but no `shouldSaveRelationshipsWhenHidden()` surface at all ([`can-be-hidden.md`](can-be-hidden.md)), and the walks it would guard prune hidden components' state before the save hook ever runs.
Next step: port the flag with the rest of the hidden-state surface, then decide whether this guard is still doing anything the pruning does not.

## Semantics

Faithful-but-subtle behaviour — permanent notes that are not deviations.

### The subtree is re-rooted, and its nested hooks are routed, onto the related record

Two halves of one move.
The **re-root** is a trait-level `getChildSchema()` override — Filament's own seam — that binds every child schema to the record this component maps, so a component nested inside asks `getRecord()` and gets the *related* object.
Every child-schema key is stamped, decoration keys included: Filament's override does not discriminate, and `getRelatedStatePaths()` already walks them all, so stamping a subset would leave the two disagreeing about which record a path belongs to.
It is deliberately **not** `configureChildSchema()`, the seam this trait would otherwise reach for: [`Section`](../../../packages/schemas/src/Components/Section.php) — the trait's primary consumer — declares a class-level `configureChildSchema()`, and a class method silently shadows a same-named trait method, so a trait-side one would never run for a `Section` and would break its own key configuration if it did.
`getChildSchema()` shadows only the one inherited from the base `Component`, and `parent::` reaches it, the same shape the trait's own `ownsRecordSubtree()` already uses.
A test pins that `Section`'s child-key configuration still applies underneath.

The **routing** is what makes a relation-backed field inside the subtree work at all.
This component [owns its record subtree](belongs-to-record.md#a-component-can-own-its-whole-record-subtree), so the mapper drops every path beneath it and would [refuse](belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) a nested relationship hook as unreachable.
It vouches instead — `routesNestedRelationshipHookAt()` answers from the same walk the two hook bodies route from, `Schema::getNestedRelationshipHooks()` merged across `getChildSchemas()` — and then runs those hooks itself against the related record: the fill sets each **load**-hooked path from its hook, the save hands each **save**-hooked path to its hook, presence-gated exactly as the root edge gates its own, after the leaf writes and before the attach-if-new tail.

Subtraction is **per edge**, mirroring the root: a load-hooked path leaves the leaf read and a save-hooked one leaves the leaf write, so a hook on one edge keeps its ordinary walk on the other.
A claim covers the hook's own path and everything nested under it, which is what lets a nested owner — itself one hooked entry — carry the leaves `getRelatedStatePaths()` still collects beneath it.
A `condition: false` component vouches and still fills, but its save nulls and removes the branch without running a nested hook: the branch is being deleted, not written.

The merge walks `getChildSchemas()` in its natural order and lets the last assignment win, which is the order the root's flat walk visits an owner's child schemas in — so the component the discovery keeps for a shared path is the one the record path map kept, and [the mapper's last-claimant rule](belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) and this walk cannot name different survivors.

### Every closure inside the subtree now sees the related record

Accepted consequence of the re-root, and Filament's semantics: a closure on a component in a stamped subtree receives the related record through `$record` injection, and `getRecordClass()` answers the related class.
That is what makes a nested field's own target resolution correct — a relation-backed field reads `getRecordClass()` to find the association it maps — and it re-points the `unique()` / `exists()` default probe class with it.
The constraint half of that agreement is not this trait's to make: the derivations that used to frame on the root container — `isRequiredByRecord()`, `DateTimePicker`'s object class — read the component-tier [nearest-record frame](belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) instead, whose class-and-path pair resolves to the very record this stamp binds.

### Subtree ownership, not `dehydrated(false)`, is what makes the hooks fire

Filament ends `relationship()` with `$this->dehydrated(false)`, so the layout component contributes no state of its own and its children dehydrate normally.
The port instead makes the component [own its record subtree](belongs-to-record.md): `ownsRecordSubtree()` answers true whenever a relationship is declared, which is what makes the record path map carry the component **at the association's path** and drop every path collected beneath it.
That is the load-bearing half — the hooks are only ever consulted for a path the map carries, so without the ownership the map would take `profile.bio` from the child field and the hooks would be refused at map time as unreachable.
Ownership is keyed on the relationship being *declared*, not on its condition: a `condition: false` component still owns its subtree, which is how its save hook gets the chance to delete.

### A constructed related record is written through the property, so the mapping persists it

When the record holds no related object, the save builds the association's target through [`PropertyAccessor::constructRecord()`](property-accessor.md#the-mappers-construct-or-refuse-is-one-method-three-callers-borrow) — the same construct-or-refuse the accessor uses for a null intermediate, refusing a constructor with required arguments — maps the submitted leaves onto it, and writes it onto the record through `setOnRecord()` — the record's own setter.
In the test rig that setter is `Post::setProfile()`, which maintains the owning side, and the association's `cascade: ['persist']` inserts the new row at flush.
Nothing is persisted by the component: a related record whose association declares no cascade needs the caller's own `persist()`, exactly as an unrelated entity would.

### A constructed record whose assigned key is incomplete is refused by name

Once the constructed record is written onto the owner, the save asks [`AssociationReader::hasIncompleteAssignedIdentifier()`](association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask) of it, and throws a `LogicException` when an assigned key component is still null or blank:

> The [pair] relationship is saving a new [App\Entity\Pair] whose identifier is
> incomplete, so no record key can be minted for it. Give every identifier field
> a component — enabled, dehydrated and required — inside the relationship, or
> on the record it hangs off when its identifier is that record, or drop
> relationship() and create the related record by hand after getData().

Without this read no blank-key seam reads this record: the record check's blank pass descends identifier associations only, and the create seams read the root record alone.
So a whitespace key half typed into a field over a nullable property — or a key half no field inside the group maps — would pass `getData()`, the cascade would insert the row, and nothing could key it afterwards: the redirect and every listing would fail on it.
A field over a non-nullable key property never gets that far, since it is required on its own.

It is read **after** the owner's setter runs, not before: a shared-key one-to-one held from its inverse side, whose setter syncs the owning side as Symfony's maker writes it, gets its whole key from that write.
Read before it, the cap of a new base would be refused although the flush keys it.
The refusal inherits the reader's exemptions — a single scalar key stays the application's `required()` to guard, and a class with a `prePersist` callback or entity listener is trusted to fill its key — and with them the [`Repeater`](../forms/repeater.md#a-new-item-whose-identifier-is-incomplete-is-refused-before-the-save-returns)'s recorded residual: a half such a callback leaves unfilled still reaches the table.
As in the repeater, the record stays on the owner when the save throws, so only a flush the host never reaches could write it.
An existing related record is never asked; its key is the changed-key pass's to guard.

One reach is recorded rather than closed.
The reader follows an identifier association into the record it points at, so a shared-key child whose identifier **is** the owner is refused when the owner's own single scalar key is blank — an owner whose key field is not required (`required(false)`, or a nullable property) and holds whitespace.
Without the refusal that `getData()` would pass, the single scalar root key being exempt; the [`Repeater`](../forms/repeater.md#a-new-item-whose-identifier-is-incomplete-is-refused-before-the-save-returns) refuses its items under such an owner the same way, and the refusal stays: the child row could not be keyed after the flush either.
A required key field — which forms derives for a non-nullable property — never reaches it.
Telling the two causes apart needs the reader's private walk behind [`hasIncompleteAssignedIdentifier()`](association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask), which answers one boolean, so the message names both instead: its advice points at the record the relationship hangs off as well as at the components inside it, since naming the group alone would send the developer past the owner's key field.

Pinned by [`EntanglesStateWithSingularRelationshipKeyTest`](../../../packages/schemas/tests/Integration/Components/Concerns/EntanglesStateWithSingularRelationshipKeyTest.php) — a whitespace and an empty half, an unmapped half, a shared-key child refused over its owner's blank optional key, a filled pair that saves, a blank single scalar key left alone, an existing record left alone and the syncing setter that saves — and, through real `TextInput`s, by forms' [`EntangledIncompleteKeyTest`](../../../packages/forms/tests/Integration/Components/EntangledIncompleteKeyTest.php).

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
Say what would reverse the decision when it is provisional.

### The `relatedModel` argument is dropped; the mapping names the target

Filament's third argument, `string | Closure | null $relatedModel`, exists because an Eloquent relation object is the only place the related class can be read from and a morph relation cannot answer it.
Doctrine's `ClassMetadata` answers it directly — [`AssociationReader::describe()`](association-reader.md) returns the target class with the rest of the mapping — so the argument would only ever let a caller contradict the mapping.
Dropped: `relationship()` takes the name and the condition, and the target comes off `ClassMetadata`.

### The name is resolved once, at declaration

Filament types `relationship(string $name)`, a plain string.
The port widens it to `string|Closure|null` for consistency with every other setter, but evaluates the closure *there and then* rather than per read, because the name is also the component's state path and the whole child tree is built from it.
A name that would differ between two evaluations was never expressible in Filament either.
A blank name is refused on the spot: a layout component carries no name of its own to fall back on, unlike a field.

### The subtree is read and written per declared leaf, not as an attribute bag

Filament fills from `$record->attributesToArray()` and saves with `$record->fill($data)->save()` — the model's whole attribute bag, filtered by its `$fillable`.
There is no Doctrine equivalent, and reflecting an entity's properties would map columns no field asked for.
The port iterates the **declared leaf paths** of the component's child schemas instead — the same discipline the [relationship repeater](../forms/repeater.md) uses per item — so a property no field declares is never read and never written.
An unreadable leaf is skipped on the way in, and so is an object-valued one unless the field's own state casts flatten it — the root fill's rule, asked of the same component ([the predicate](belongs-to-record.md#reads-are-lenient-writes-are-strict)) — so a date or an enum fills from the related record, as does a `Uuid` under a cast that flattens it, and a fill hook receives the object at its path, while an association, and an object no cast flattens, still skip.
A leaf the dehydrated state does not carry is skipped on the way out.
Skipping every object-valued leaf here would fill such a field empty, and the next save — one that never touched it — would write NULL over the stored value.
Pinned by [`EntanglesStateWithSingularRelationshipObjectLeafTest`](../../../packages/schemas/tests/Integration/Components/Concerns/EntanglesStateWithSingularRelationshipObjectLeafTest.php) and, through a real `DateTimePicker` inside a `Group::relationship()`, by forms' [`RepeaterRelationshipObjectLeafTest`](../../../packages/forms/tests/Integration/Components/RepeaterRelationshipObjectLeafTest.php).

*Leaf* is meant in the record walk's sense, so the collection filters exactly as [`getRecordPathMap()`](belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths) does: a nested container carrying path-bearing components contributes its leaves, never its own path, unless it [owns its subtree](belongs-to-record.md#a-component-can-own-its-whole-record-subtree).
Without that filter a plain `Section` at `post` around a field at `post.title` would yield both, and the save's first write would hand the whole subtree array to the object property — the write [the mapper refuses](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused).

### The stamp is a closure, floored at the target class

Filament binds the child schema to a value it has already resolved — `$this->getCachedExistingRecord() ?? $this->getRelatedModel()`, an instance or a class-string.
Ours is a closure instead, re-applied on every `getChildSchema()` call, and its resolution is **soft**: the association object when the record holds one, else the target class the mapping names, else null.
Two facts force the shape.
A record that evaluates to `null` falls through to the parent, so a stamp resolving null in a bound tree would hand the subtree the *outer* record — the one record it must never see — and the class-string floor makes that fallthrough unreachable wherever the mapping answers.
And the stamp is evaluated from structural reads (rendering, flat walks, the map build), so target resolution goes through [`AssociationReader::describe()`](association-reader.md) rather than the refusing `getRelationshipTarget()`: a misconfigured relationship stays reported by the fill and save edges, where the message can say what to do, instead of throwing out of every tree walk.

The root-tier getters memoize the evaluated record back onto the schema, so a cached default child schema freezes whatever the first read produced — the class-string, where the related object did not exist yet.
Harmless: the hooks receive the record explicitly, and target resolution and the constraint frame need only the class.
It holds per schema object within one request, which is the only lifetime a schema tree has.

### A switched-off relationship nulls the property and removes the record explicitly

Filament's `condition: false` arm is `$record?->delete()`.
The port nulls the association property and calls [`AssociationReader::remove()`](association-reader.md#remove-is-here-so-orphan-deletion-does-not-grow-a-second-seam), and does **not** require the association to declare `orphanRemoval`.
Two reasons the repeater's opposite stance does not transfer.
Deleting is what the developer declared here — `relationship(condition: false)` states that this record must not exist — where a repeater item disappearing is an end user editing a list, whose ownership only the mapping can settle.
And nulling alone would not be enough on an inverse side: the test rig's `Post::$profile` is the inverse of `Profile::$post`, so writing null there changes no foreign key and, without `orphanRemoval`, would leave the row behind pointing at its parent.
The property is still nulled so the in-memory graph agrees with the database, and it is nulled through the accessor, so a property that rejects null is refused with a message rather than a bare type error.

### The association core is shared with forms' relation-backed fields

Filament writes the association lookup three times — once in this trait, once in `Select`, once in `Repeater` — because each reaches a different Eloquent relation API.
Ours is one Doctrine lookup, so the port keeps one copy: [`BelongsToRelationship`](../../../packages/schemas/src/Components/Concerns/BelongsToRelationship.php) holds `$relationship`, `getRelationshipName()`, `getRelationshipNameOrFail()`, `getRelationshipTarget()` and its three-arm refusal (no entity manager, no record class, no association at that property), and this trait, forms' [`Select`](../forms/select.md) and forms' [`Repeater`](../forms/repeater.md) all compose it.
It sits in **schemas** rather than forms because that is the direction the dependency already runs — forms requires schemas, not the reverse — and forms' same-named trait is now a three-line adapter filling in the clauses a *field* says differently.

Three seams carry the per-consumer prose, so no message lost a word to the sharing: `getRelationshipSubject()` (how a refusal names the thing that declared the relationship — `The [bio] field` against a field, the component class against a layout component), `getMissingEntityManagerConsequence()` (why a missing entity manager is fatal for *this* consumer), and `getMissingRelationshipNameNote()` (whether there was a name to fall back on).
The to-many refusal stays here, since it is this trait's alone — one set of fields cannot map a collection, where a `Repeater` wants exactly that.

### The relation object becomes an `AssociationTarget`, and the MorphOne arm is cut

Filament returns `BelongsTo | HasOne | MorphOne | null` from `getRelationship()` and branches on the class — `BelongsTo` saves the related record first and associates it, the others save through the relation.
The port has no relation objects: it asks the reader for an [`AssociationTarget`](association-reader.md) and writes through the record's own setter, which is the same write on either side of a Doctrine to-one.
`MorphOne` has no port at all — morphs are out of scope, and Doctrine's inheritance mapping is a different shape rather than a missing feature.
A to-**many** association is refused with a message pointing at the Repeater, since one set of fields cannot map a collection.

### Two components entangling one relationship throw rather than merge

Filament lets several layout components share one relationship: each closure walks the model-root container collecting every component that names the same relationship, the first one fills, and the first one saves the merged state of all of them.
The port refuses instead — two components claiming the same record path, one of them owning its subtree, is a `LogicException` from the record path map naming both classes.
The merge is real ergonomics (a wide form splitting one related record across two boxes), but it needs a cross-component identity rule the walks do not have: our map is keyed by record path, so the second component would silently replace the first.
**Provisional:** reversed by teaching the map to fold several subtree owners at one path into one entry, at which point the save can concatenate their subtree states the way Filament's does.

### The existing record is read off the graph rather than cached on the component

Filament caches the related model on the component (`cachedExistingRecord()`, read back by both closures and by `getChildSchema()`), because reaching it means a query through the relation.
Doctrine's identity map already answers the same read from memory, and the property read is the association itself, so the cache would only add a second source of truth that a `clearCachedExistingRecord()` has to keep honest.
Both hooks read `getFromRecord($record, $name)` instead.

### The contract carries the two hook bodies, not the cache surface

Filament's `CanEntangleWithSingularRelationships` publishes the cache trio, `fillFromRelationship()`, `getRelatedModel()`, `getRelationship()` and the three mutation hooks.
Ours publishes `getRelationshipName()`, `hasRelationship()` and the two methods the wired closures call — `getStateFromRelationship()` and `saveStateToRelationship()` — because that is what the contract is *for* here: a closure wired inside a trait has no concrete class to type-hint, and the members it invokes have to be visible on the type it does hint.
The dropped members are either not ported (the cache, the mutation hooks) or not public API here (the association lookup).
`relationship()` itself stays off the contract, as in Filament.

### The translatable content driver has no port

Both Filament closures branch on `$livewire->makeFilamentTranslatableContentDriver()`, which routes reads and writes through a translation package when one is installed.
The port has no translatable-content surface anywhere, so there is no branch to write; it would arrive with that surface, not with this trait.
