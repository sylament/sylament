---
description: '`HasNullableBranch` on the base `Component`: `nullWhenBlank()` and the branch pass in `getData()` that nulls a blank optional branch of the record graph (draft)'
ports: []
owns:
  - packages/schemas/src/Components/Concerns/HasNullableBranch.php
---
# HasNullableBranch (schemas)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`nullWhenBlank()` / `isNullableBranch()` / `shouldNullBranch()` / `isBlankStateValue()`](../../../packages/schemas/src/Components/Concerns/HasNullableBranch.php) | — | done | — | [diverges](#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) · [semantics](#the-built-in-blank-test-asks-the-branchs-own-leaves) · [semantics](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) · [semantics](#a-branch-the-record-cannot-null-is-refused-loudly) · [diverges](#a-nullwhenblank-closure-sees-two-different-bags) · [diverges](#a-doctrine-embeddable-cannot-be-nulled) |

## Semantics

### `nullWhenBlank()` cannot compose with a save hook

A component declaring both `nullWhenBlank()` and `saveRelationshipsUsing()` throws a `LogicException` from `getNullableBranches()`, naming the component class and the two ways out.
This is a v1 refusal rather than a rule about what is possible, and the grounds are that the two declarations answer the same question differently.
The branch pass writes `null` at the branch's own record path through the property accessor and then skips every path beneath it — but a save hook exists precisely because the accessor cannot write that path, so the "null the branch" half would either fail or write past the relationship the hook owns.
Emptiness for a relationship is the hook's own business: a to-many hook clearing its collection and a to-one hook writing `null` both express it inside the callback, with the entity manager in reach.

Placed before the state-path check in the same loop, so the conflict is reported even when the component also carries no state path — the pair of declarations is wrong independently of where it sits.

### The built-in blank test asks the branch's own leaves

`shouldNullBranch()` without a closure does **not** scan the values in the branch's slice.
It walks the root schema's flat components and keeps every declared leaf whose absolute path sits under the branch's own — path-bearing, and not itself a container — then reads each leaf's value out of the slice and asks the leaf whether it is blank, through `Component::isBlankStateValue()`.
Three consequences, all deliberate:

1. **Unmanaged keys in the slice no longer influence the verdict.**
   An array-backed branch keeping a sibling key the schema never declared ([leaf granularity](belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths)) is judged on the declared leaves alone — the same "the schema maps what it declares" rule the rest of both walks follows.
2. **A leaf absent from the slice is blank, and membership is the path, not the tree.**
   Beneath-ness is the same absolute-path prefix the branch pass skips by, so a filled leaf three levels down keeps the branch alive — and so does one declared by a *sibling* component whose path nests under the branch, [the independence the ordering sort exists for](#branches-are-ordered-by-path-depth-because-tree-order-is-not-path-order).
   Judging tree children instead let a branch null while a filled sibling leaf beneath its path was then skipped as nulled — silent data loss, caught in review before merge.
   A leaf whose key the slice does not carry simply contributes nothing.
3. **A detached or path-less component falls back to the old value-recursive test**, so `shouldNullBranch()` stays total.
   Neither real walk can reach that arm — the mapper [refuses a path-less branch outright](#a-nullable-branch-with-no-state-path-is-refused-not-ignored) and forms' requirement walk is gated the same way — it exists so a component built with `new` in a unit test, with no container to enumerate leaves through, still answers.
   A wired component without a path of its own takes the same arm: its absolute path would be its container's, and enumerating leaves under that would judge the wrong branch.

The trap that forced the change is a boolean.
`filled(false)` is `true`, and `Checkbox`/`Toggle` cast with `BooleanStateCast(isNullable: false)`, so an untouched box dehydrates `false` rather than null.
Under the old value scan that made **any** boolean field inside a `nullWhenBlank()` branch permanently non-blank: derived requirement never suppressed, so the branch's non-nullable leaves stayed required and a blank branch could not be submitted, and had it been submitted the branch could never have nulled.
Asking the leaf instead puts the answer where the state casts are — [`Checkbox`](../forms/checkbox.md) and [`Toggle`](../forms/toggle.md) override `isBlankStateValue()` to read their resting `false` as blank, the same field-side placement that already carries [their derivation opt-out](../forms/can-be-validated.md#a-boolean-field-opts-out-of-derivation-but-selectboolean-does-not).

A branch that [owns its record subtree](belongs-to-record.md#a-component-can-own-its-whole-record-subtree) asks none of that: it judges its own whole slice through `isBlankStateValue()` and never enumerates a leaf, and the enumeration skips every leaf sitting under an owner for the same reason.
That is a correctness requirement, not a shortcut.
At mapping time an owner's slice has already been rewritten by its own dehydration — [the `Repeater` positionalises](../forms/repeater.md#items-are-uuid-keyed-in-the-live-bag-and-positional-on-the-record) its uuid-keyed items — while the leaves it declares still carry the uuid paths, so enumeration would find every one of them absent, judge a branch that holds items vacuously blank, and null it.
One all-blank item is the exact case: by value it is an item, by leaves it is nothing, and the two answers differ by a whole list.
An owner nested inside an ordinary branch is answered the same way — the outer branch reads the owner's value, not the owner's leaves.

The leaf-enumerating predicate the walk needs, `hasPathBearingDescendants()`, moved from a private helper on the root tier onto `Component` (via `HasChildComponents`) so both walks share one definition, the same convergence [`getRecordStatePath()`](component-belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) argues for.

### A branch the bag no longer carries is skipped, not nulled

The branch pass judges a branch only when the dehydrated bag still holds **at least one of the leaf paths the schema declares beneath it**.
If it holds none, the pass writes nothing at all.

This is the guard that stops branch-nulling from being a data-loss bug, and the reason is that "hidden" and "blank" arrive at the pass looking identical.
[The dehydration walk](component.md) *forgets* a non-dehydrated component's whole subtree — `isDehydrated()` ends in `! isHiddenAndNotDehydratedWhenHidden()` — so a hidden branch leaves no trace in the bag.
Read that absence with `getFromArray()`, coerce the non-array to `[]`, and `shouldNullBranch([])` answers `true`, correctly, because an empty bag holds nothing filled.
The pass had asked the wrong question.
Measured, with a `Post` holding a real `Author('Ada')` and a *filled* author in state:

| schema | before | now |
| --- | --- | --- |
| `->statePath('author')->hidden()`, undeclared | survives | survives |
| `->statePath('author')->nullWhenBlank()->hidden()` | **NULL — data loss** | survives |
| `->statePath('author')->nullWhenBlank()` with a `->hidden()` leaf | **NULL — data loss** | survives |

The realistic trigger is not exotic: `Section::make('Author')->statePath('author')->nullWhenBlank()->hiddenOn(Operation::Edit)` deleted an article's author every time someone edited its title, with no exception and no validation error.

**A `hasInArray()` check on the branch path itself is not enough**, and this file proposed exactly that before it was tested.
[`forgetFromArray()`](property-accessor.md#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal) unsets only the leaf key and deliberately leaves emptied parents behind, so pruning a branch's only leaf leaves `author => []` in the bag and `hasInArray($state, 'author')` still answers `true`.
Swapping the leaf-path predicate for that one-liner fails the hidden-leaf case and nothing else — run as an experiment, not assumed.

The same predicate settles two cases that were filed separately.
A component with no declared leaf beneath it is a **leaf, not a branch** — `nullWhenBlank()` on a plain field now writes nothing, where before the pass nulled it every time and was saved only by the leaf walk overwriting the null a moment later.
That rescue did not apply to a `dehydrated(false)` leaf, where the null reached the record and contradicted `getData()`'s own docblock.
Both are gone.

An [owner branch](belongs-to-record.md#a-component-can-own-its-whole-record-subtree) is asked at its **own** path instead, and that is not the one-liner rejected above.
It has no mapped leaf beneath it at all — the owner speaks for them — so the leaf-path predicate would answer "absent" for every owner and no owner could ever null.
Reading its own path is safe for the reason the leaf predicate is not: an owner's key is not an emptied parent left behind by pruning, it is the one key the dehydration walk writes for it, so a hidden owner's key is gone outright and a present one is evidence of a real submission.

The residual, stated so it is not mistaken for an oversight: a branch with *some* leaves pruned and some present is still judged on the leaves that remain.
That is [the two-bag hazard](#a-nullwhenblank-closure-sees-two-different-bags), not this guard's business.

### A branch the record cannot null is refused loudly

`nullWhenBlank()` on a property the record declares non-nullable — `private Author $author` rather than `?Author` — is a declaration that can never be honoured.
It used to reach `setOnRecord($record, 'author', null)` unguarded and surface as Symfony's `InvalidTypeException`: a 500 whose message names neither `nullWhenBlank()` nor the fix, from a mapper whose [documented contract](belongs-to-record.md#reads-are-lenient-writes-are-strict) is that its failures spell the fix out.
The pass now asks [`acceptsNullOnRecord()`](property-accessor.md#the-nullability-probe-lives-here-because-the-type-reader-does) and throws a `LogicException` naming the path, the record and the declaration.

Two placement decisions inside that, both deliberate:

- **Before the branch condition, not after.**
  A non-nullable branch throws whether or not the user left it blank, so the failure is deterministic and surfaces on the first save in development rather than on the first *empty* save in production.
- **After the presence guard, not before.**
  A branch the bag does not carry is not part of this submission at all, so a `hiddenOn(Operation::Edit)` branch over a non-nullable property does not throw on every edit.

The alternative considered and rejected was to keep the mapper quiet and instead refuse the [derived-requirement suppression](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types) for a non-nullable branch, so the user meets a field-level 422 instead.
It does not actually close the hole: suppression only produces a 422 when some leaf beneath the branch derives a requirement, and a branch whose leaves are all themselves nullable stays blank, stays valid, and reaches the same crash.
It also aims a message at the *end user* for a mistake only the *developer* can fix, leaving `nullWhenBlank()` a silent no-op on that property — the same failure mode as the path-less case below.

### A nullable branch with no state path is refused, not ignored

`getNullableBranches()` keys branches by [`getRecordStatePath()`](component-belongs-to-record.md#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it), which is null for a component carrying no `statePath()` of its own.
Such a component names no property, so it cannot be a branch — but dropping it silently meant that

```php
Section::make('Author')->nullWhenBlank()->components([
    TextInput::make('author.name'),   // the path is on the field, not the section
])
```

— a natural way to write "this author block is optional" — did nothing at all, with no feedback.
It now throws, naming the component class and the two ways out (give it the `statePath()` of the branch it wraps, or drop the declaration).

Forms' validation walk had no such filter, which is how one field's requirement came to depend on unrelated fields elsewhere: `getRawState()` on a path-less component [falls through to the container's path](component.md), so `shouldNullBranch()` was handed the **whole form's** raw bag and suppressed requirement whenever *any* of it was blank.
That walk is now gated the same way, so both agree on what a branch is — [the forms side](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types).

### Branches are ordered by path depth because tree order is not path order

`getNullableBranches()` ends in a `uksort()` by dot count, so the branch pass runs outermost-first and an outer branch that nulls can short-circuit every inner one beneath it.
**This sort is load-bearing.
Do not delete it as redundant.**

The tempting argument for deleting it is that `getFlatComponents()` already walks the tree depth-first, so a parent is always visited before its children and ancestors already come first.
That argument is false, and it was made once — the sort's own author offered it, and it did not survive review.
Tree nesting and path nesting are independent: a component's record path comes from `statePath()`, not from where it sits in the component tree, so two **siblings** can nest by path.

```php
Section::make()->statePath('meta.seo')->nullWhenBlank(),   // declared first
Section::make()->statePath('meta')->nullWhenBlank(),       // its path-ancestor
```

Pre-order traversal reaches `meta.seo` first.
Without the sort the pass evaluates the descendant before the ancestor, so nulling `meta` afterwards writes over a decision already taken about a path underneath it, and the `$nulledPrefixes` short-circuit — which only skips branches it has *already* seen a prefix for — cannot save it.
Sorting by depth restores the invariant the short-circuit assumes.

That exact sibling-by-path shape is now pinned through the refusal, so deleting the sort fails a test rather than a code review: the inner branch sits over a **non-nullable** property, which only survives the pass by being short-circuited under the nulled outer one, so an unsorted pass reaches it first and throws [the non-nullable refusal](#a-branch-the-record-cannot-null-is-refused-loudly) naming `meta.seo`.

One latent type detail rode along with it, now closed: PHP coerces a numeric-string array key to `int`, so a schema using `statePath('0')` would have `TypeError`d under `strict_types=1` — in the sort, whose callback hinted `(string $a, string $b)`, and equally at `setOnRecord()`, which takes a `string` path.
The hints are `int|string`, and `getData()` normalises both walks' keys with `strval()` once rather than casting at each of the six use sites.
The claim that this covered "both walks" was true only of the two inside `getData()`: the **fill** walk, `readStateFromRecord()`, iterated the same map's keys raw and `TypeError`d on `getFromRecord()`'s `string $path`.
It normalises the same way now, so all three walks agree.

## Divergences

### A declared branch is nulled whole before its leaves are written

`nullWhenBlank()` marks a container as an optional branch of the record graph.
`getData()` collects every declared branch before the leaf walk, evaluates each against its own slice of the dehydrated state, and for one that should not exist writes `null` at the **branch** path and then skips every leaf beneath it — so a discarded branch is never rebuilt by one of its own leaves.
`true` uses the built-in test — every leaf the schema declares beneath the branch reports its own value blank, at any depth, so a filled leaf three levels down keeps the whole branch alive ([the leaves answer, not the values](#the-built-in-blank-test-asks-the-branchs-own-leaves)); a closure receives that slice as `$state` and decides for itself.

A branch that [owns its record subtree](belongs-to-record.md#a-component-can-own-its-whole-record-subtree) takes three narrower arms of the same pass, all following from its having no mapped leaf beneath it.
Presence is read at the branch's **own** path rather than through a leaf it does not have; the blank test short-circuits to the value arm; and nulling records the branch path exactly as well as by prefix, because an owner *is* a mapped path where an ordinary branch never is — without the exact skip the leaf walk would immediately write the owner's wholesale value back over the `null` the pass just wrote, and the declaration would silently do nothing.

There is no Filament counterpart and the near neighbour has opposite polarity — `relationship(condition:)` says *save when true* and ships no blank test, so a Filament user writes the emptiness check by hand every time.
The name is recorded as a Sylament invention rather than a rename in the [rename table](../translations.md#names-with-no-filament-source).

Two consequences worth stating.
**An undeclared branch is not nulled**, it is constructed: leave `nullWhenBlank()` off and a blank nested object still gets built, then fails validation on its first non-nullable leaf — which is the behaviour that makes the declaration opt-in rather than a default.
And **nulling does not relax validation**: an explicitly `required()` leaf inside a blank nullable branch still fails, because branch-nulling runs after `getState()`.
That is not an oversight — the seam that lets a blank optional branch submit is [derived requirement](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types), which suppresses itself under a blank nullable branch; an explicit declaration staying strict is the correct half of that pair.

### A `nullWhenBlank()` closure sees two different bags

The blank test runs at two different moments against two differently-shaped bags, and a closure written for one can be wrong about the other.

- **At mapping**, `getData()` passes the branch's slice of the **dehydrated** state — state casts applied, `dehydrated(false)` leaves pruned out.
- **At validation**, forms' derived requirement asks the same question through `shouldNullBranch()` to decide whether to suppress a leaf's requirement, and reads the **raw** live state off the host instead, because requirement is decided before any dehydrated bag is assembled.

So `nullWhenBlank(fn (array $state) => ...)` is invoked twice per submit with two shapes, and the observable consequence is sharper than the shape difference: a branch holding a filled leaf the dehydration walk prunes reads **non-blank** at validation — so the branch's other leaves are required and the user is made to fill them — and then reads **blank** at mapping, so the whole branch, including the value the user was forced to supply, is discarded.

It does not widen for an [owner branch](belongs-to-record.md#a-component-can-own-its-whole-record-subtree), which is the one shape where the two bags could differ most: the value arm reads the branch's own slice, so the raw uuid-keyed bag at validation and the positional one at mapping answer the same question with the same verdict.

It does widen, by one shape, for a repeater declaring [value-object items](../forms/repeater.md#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam): the built-in test stays verdict-stable there for the same reason (both bags are arrays, both empty or both not), but a **closure** condition on such a repeater reads uuid-keyed value *arrays* at validation and a positional list of *constructed objects* at mapping — so a closure reaching into an item (`$state[0]['title']`) is right about one bag and fatal on the other.

The [presence guard](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) narrowed that to a **mixed** branch.
A branch whose leaves are *all* pruned is now skipped outright rather than nulled, so the discard needs at least one surviving leaf beside the pruned one.
It did not remove the hazard, and the guard is not the fix — the two walks still read two bags.

Not fixed, and the reason is cost rather than doubt: making validation read dehydrated state would mean dehydrating a branch per field per request, on the hot path, to serve a combination (a pruned leaf inside a `nullWhenBlank()` branch) that no shipped schema uses.
The practical rule until then — **do not put a pruned leaf inside a nullable branch**, where pruned covers a hidden leaf (`hidden()`, `hiddenOn(...)`, `visible(false)`) as much as a `dehydrated(false)` one, and the hidden shape is the likelier of the two to be written by accident — and write closure conditions against key *presence* and blankness rather than against an exact bag shape.
Reverses if the cost model changes or a real schema needs the combination.

### A Doctrine embeddable cannot be nulled

Branch-nulling round-trips for a plain property, a DTO, and a real `OneToOne` association.
It does **not** round-trip for an `#[ORM\Embedded]`, and this is Doctrine's constraint rather than the mapper's: an embeddable write goes through `ReflectionEmbeddedProperty::setValue()`, which vivifies, and a read returns null only when the whole parent property is null.
Set the branch to null, flush, reload — and Doctrine hydrates the columns straight back into an empty object.
The mapper's `null` is written and accepted; it simply does not survive the round trip.

Documented rather than worked around.
Detecting it would mean reading Doctrine mapping metadata, which this walk is deliberately not handed: `schemas` does own [`AssociationReader`](association-reader.md), which holds an `EntityManager` and reads `ClassMetadata`, but only the component tier reaches it — the record walk in [`Concerns/BelongsToRecord`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) takes no reader and works entirely off the declared PHP types [`RecordTypeReader`](record-type-reader.md) supplies.
Faking it — writing a fresh empty embeddable instead of null — would only rename the same outcome.
A branch that genuinely has to be absent wants a nullable `OneToOne` or a plain nullable property, not an embeddable.
