# Repeater (forms)

`Sylament\Forms\Components\Repeater` — the array-backed repeatable field: one
child schema cloned per item, items added, removed and reordered through the
mounted-action pipeline, the whole list written into a plain `?array` record
property (the JSON-column shape [`setOnRecord()`](../schemas/property-accessor.md)
already round-trips). It extends [`Field`](field.md) and is the first component
that is **both a leaf and a container**: it carries a state path of its own *and*
path-bearing children, which is the case the record walks were
[explicitly flagged as unready for](../schemas/belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths)
and now [answer with an owner predicate](#a-repeater-owns-its-record-subtree).

**Scope boundary, stated up front:** the *plain* repeater maps **value-shaped
array items only**. Object collections — a `Traversable`-typed property, a
Doctrine `Collection` behind an adder — [stay refused by the mapper](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused),
because mapping collection *items* needs identity — which state row is which
entity, and what happens to the rows that disappeared. The measured failure
that draws the line is recorded in
[the Serializer rejection](../schemas/belongs-to-record.md#a-serializer-based-mapper-stays-rejected-on-amended-grounds):
identity-less collection population produced duplicate INSERTs and orphan
deletions on a real two-tag `Post`. That identity is exactly what
[`relationship()`](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
supplies, so a repeater that declares one maps a Doctrine collection and the
refusal steps aside for it — [and only for it](#object-collections-stay-refused-until-a-relationship-supplies-the-identity).
`table()` mode ships, over that same item pipeline.

**Status:** in progress — see the [status vocabulary](../README.md#statuses). The
array-backed field ships end to end: item pipeline, per-item child schemas, the
eleven structural actions, item chrome, the collapse family, `addBetween` /
`labelBetweenItems`, `extraItemActions()`, the items grid, `table()` mode, the
three partials, the record round-trip, the
[value-object item mode](#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam)
and the
[relationship mode](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
over a Doctrine collection. What is left is the one **blocked** row,
`partiallyRenderAfterActionsCalled()`, which wants a UX LiveComponent
partial-render equivalent.

Reference — the Filament source this is diffed against, walked end to end for
this table, so it carries no [unaudited row](../README.md#an-unaudited-table-says-so):

- [`forms/src/Components/Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php)
  — the class, its eleven registered actions, the item pipeline, the three
  embedded views
- [`forms/src/Components/Concerns/CanGenerateUuids.php`](../../../vendor/filament/forms/src/Components/Concerns/CanGenerateUuids.php),
  [`CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php),
  [`CanBeCloned.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeCloned.php),
  [`HasExtraItemActions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php)
  — the forms-tier concerns it composes
- [`schemas/src/Components/Concerns/HasChildComponents.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php)
  and [`Cloneable.php`](../../../vendor/filament/schemas/src/Components/Concerns/Cloneable.php)
  — the keyed-child-schema cache and clone layer the item schemas ride on
- [`forms/src/Components/Repeater/TableColumn.php`](../../../vendor/filament/forms/src/Components/Repeater/TableColumn.php)
  — the table-mode column object
- [`schemas/src/Components/Concerns/HasContainerGridLayout.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasContainerGridLayout.php),
  [`CanBeRepeated.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeRepeated.php)
  and [`schemas/src/Components/Contracts/HasExtraItemActions.php`](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php)
  — the schemas-tier seams the chrome sits on
- [`support/src/Concerns/HasReorderAnimationDuration.php`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php),
  [`HasWidth.php`](../../../vendor/filament/support/src/Concerns/HasWidth.php)
  and [`CanWrapHeader.php`](../../../vendor/filament/support/src/Concerns/CanWrapHeader.php)
  — the support-tier concerns the drag control and the table columns compose
- [`forms/resources/css/components/repeater.css`](../../../vendor/filament/forms/resources/css/components/repeater.css)
  — the stylesheet all three views are painted by
- [`forms/resources/lang/en/components.php`](../../../vendor/filament/forms/resources/lang/en/components.php)
  — the `repeater` block, which is where every label and column heading string
  here comes from

## Surface

Every point of Filament's class and its concerns, grouped where a family shares
one fate. _Blocked by_ names what has to land first. _Detail_ carries a
**detail** link (the write-up in [Details](#details), deleted once the row is
**done**), a **semantics** link (a permanent note in [Semantics](#semantics)),
and/or a **diverges** link (the permanent note in [Divergences](#divergences)),
or `—`. A row with no _Ports_ link is Sylament-only.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `Repeater::make()` + `components()` item schema definition (the `schema()` alias is [not ported](../renames.md)) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#an-item-field-derives-no-requirement-from-the-record) · [diverges](#every-item-schema-is-keyed-as-well-as-re-pathed) |
| `hydrateItems()` / `dehydrateItems()` + `CanGenerateUuids` (`generateUuidUsing()` / `generateUuid()` / `fake()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) · [`CanGenerateUuids.php`](../../../vendor/filament/forms/src/Components/Concerns/CanGenerateUuids.php) | done | — | [semantics](#items-are-uuid-keyed-in-the-live-bag-and-positional-on-the-record) · [semantics](#the-item-pipeline-runs-children-first-and-before-the-null-fill-pass) · [semantics](#unmanaged-item-keys-round-trip-through-the-client) · [diverges](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid) |
| `getItems()` / `getDefaultChildSchemas()` / `areCachedDefaultChildSchemasFresh()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [diverges](#every-item-schema-is-keyed-as-well-as-re-pathed) · [diverges](#an-item-schema-carries-no-constant-state-or-inline-label-stamp) |
| `defaultItems()` / `default()` + the hydrated-default merge | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [diverges](#a-defaults-fill-over-a-populated-repeater-resurrects-its-pre-fill-items) |
| `simple()` / `isSimple()` / `getSimpleField()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#the-item-pipeline-runs-children-first-and-before-the-null-fill-pass) |
| Add action (`getAddAction()` / `addAction()` / `addActionLabel()` / `addActionAlignment()` / `addable()` / `isAddable()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) |
| Delete action (`getDeleteAction()` / `deleteAction()` / `deletable()` / `isDeletable()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) |
| Clone action (`getCloneAction()` / `cloneAction()`) + `CanBeCloned` (`cloneable()` / `isCloneable()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) · [`CanBeCloned.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeCloned.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) |
| Move actions + button reorder (`getMoveUpAction()` / `getMoveDownAction()` / `moveUpAction()` / `moveDownAction()` / `reorderable()` / `reorderableWithButtons()` / `isReorderable()` / `isReorderableWithButtons()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) · [diverges](#the-move-helpers-correct-two-vendor-edge-defects) |
| Drag-and-drop reorder (`getReorderAction()` / `reorderAction()` / `reorderableWithDragAndDrop()` / `isReorderableWithDragAndDrop()`) + `HasReorderAnimationDuration` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#the-drag-reorder-round-trip-accepts-the-clients-order) · [diverges](#the-reorder-action-is-hardened-and-answers-only-real-moves) |
| [`CanLimitItemsLength`](../../../packages/forms/src/Components/Concerns/CanLimitItemsLength.php) (`minItems()` / `maxItems()` / `getItemsCount()`) — shared with `CheckboxList`, so it keeps [its own file](can-limit-items-length.md) | [`CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | [detail](can-limit-items-length.md) · [semantics](#an-item-field-derives-no-requirement-from-the-record) |
| Item chrome (`itemLabel()` / `getItemLabel()` / `hasItemLabels()` / `itemNumbers()` / `itemHeaders()` / `truncateItemLabel()` / `getHeadingsCount()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | — |
| `getItemState()` / `getRawItemState()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | — |
| `getItemKeys()` / `getItemStatePaths()` ([`HasKeyedItems`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php)) — the collection-keyed item keys and the item's declared paths the [violation-path translator](../schemas/violation-path-translator.md) resolves a record-side violation against | — | done | — | [semantics](#a-save-records-which-collection-key-each-item-was-written-to) |
| Template + stylesheet (`toEmbeddedHtml()` / `toSimpleEmbeddedHtml()` / `toTableEmbeddedHtml()` → `partials/Repeater/{index,Simple,Table}.html.twig`, `repeater.css`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#collapse-state-is-the-clients-once-the-controller-connects) · [diverges](field.md#labeltag-is-a-boolean-labelasheading) |
| Collapse chrome (`getCollapseAction()` / `getExpandAction()` / `getCollapseAllAction()` / `getExpandAllAction()` + their modifiers, `CanBeCollapsed` / `CanBeCompact` composition, `canConcealComponents()`, `shouldPersistCollapsed()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#collapse-state-is-the-clients-once-the-controller-connects) · [diverges](#the-repeater-item-owns-its-collapse-state-unlike-the-section) |
| `addBetween` pair (`getAddBetweenAction()` / `addBetweenAction()` / `addBetweenActionLabel()`) + `labelBetweenItems()` / `getLabelBetweenItems()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#addbetween-hides-by-default-and-crowds-out-the-label-between-items) |
| `extraItemActions()` (`HasExtraItemActions`) | [`HasExtraItemActions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php) | done | — | [semantics](#an-extra-item-action-reads-its-items-state) |
| Items grid (`HasContainerGridLayout`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#the-items-grid-is-supports-grid) |
| `table()` mode (`table()` / `getTableColumns()` / `isTable()` / `toTableEmbeddedHtml()`; the `TableColumn` class keeps [its own README row](README.md#components)) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#table-mode-pairs-columns-by-position) · [diverges](#hidden-fields-render-in-a-trailing-hidden-cell) |
| Relationship family — **shipped**: `relationship()`, `hasRelationship()` / `getRelationshipName()`, `orderColumn()` / `getOrderColumn()`, the three setters `mutateRelationshipDataBeforeFillUsing()` / `mutateRelationshipDataBeforeCreateUsing()` / `mutateRelationshipDataBeforeSaveUsing()`, the fill and save arms (Filament's `fillFromRelationship()` / `getStateFromRelatedRecords()` / `saveToRelationship()`, here the two [component-tier hooks](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)). **Not ported**: `getRelationship()` / `getRelatedModel()` (Eloquent objects with no Doctrine counterpart — `AssociationTarget` answers both), `getCachedExistingRecords()` / `clearCachedExistingRecords()`, `afterCreate()` / `afterUpdate()` / `afterDelete()`, the three public callers `mutateRelationshipDataBeforeFill()` / `mutateRelationshipDataBeforeCreate()` / `mutateRelationshipDataBeforeSave()` (the arms call the callbacks through one private helper instead), the `getLabel()` override, item `constantState()` / record stamping | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) · [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| `partiallyRenderAfterActionsCalled()` / `shouldPartiallyRenderAfterActionsCalled()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | blocked | UX LiveComponent partial-render equivalent | — |
| `array_move_before()` / `array_move_after()` (`packages/forms/src/helpers.php`, the reorder bodies) | [`helpers.php`](../../../vendor/filament/forms/src/helpers.php) | done | — | — |
| Deprecated members (`createItemButtonLabel()`, `disableItemCreation()` / `disableItemDeletion()` / `disableItemMovement()`, `orderable()`, `inset()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#deprecated-filament-members-are-not-ported) |
| `nullWhenBlank()` composition — an empty repeater as `null` instead of `[]` | — | done | — | [semantics](#the-blank-test-judges-a-repeater-by-its-own-value) · [diverges](#a-repeater-owns-its-record-subtree) |
| Early object-collection refusal at hydration | — | done | — | [diverges](#object-collections-stay-refused-until-a-relationship-supplies-the-identity) |
| `itemsAs()` value-object item mode — explicit item class + optional denormalize/normalize pair over schemas' [`ObjectStateCast`](../schemas/object-state-cast.md) | — | done | — | [semantics](#a-cast-item-is-schema-complete-so-unmanaged-keys-stop-riding) · [diverges](#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam) · [diverges](#item-class-inference-is-deferred-the-declaration-stays-explicit) |

## Details

Specifics for rows that are not finished. Delete a subsection when its rows flip
to **done**; the permanent halves live under [Semantics](#semantics) and
[Divergences](#divergences), and the notes that constrain *other* components
live in their own files — the record-walk changes in
[belongs-to-record.md](../schemas/belongs-to-record.md), the hydration order in
[schema.md](../schemas/schema.md#hasstate-still-lacks-constant-state-relationships-and-partial-rendering),
the wrapper heading arm in [field.md](field.md#labeltag-is-a-boolean-labelasheading),
and `distinct()`'s three arms in
[can-be-validated.md](can-be-validated.md#distinct-is-three-arms-of-one-first-party-constraint-not-laravels-rule).

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### Items are uuid-keyed in the live bag and positional on the record

One list, two shapes, and the boundary between them is dehydration.
In the live bag items are keyed by per-request v4 uuids (`symfony/uid`'s
`Uuid::v4()`, the `Str::uuid()` idiom translation; the dependency is declared
by `forms`, approved 2026-08-04) — minted at hydration
(`hydrateItems()` re-keys whatever `fill()` read) and by every add/clone — so a
structural mutation never renumbers a sibling's state path mid-edit, which is
what keeps validation errors, morph anchors and in-flight input attached to the
right item. Everything that runs before dehydration sees uuid keys: absolute
state paths, the constraint collector, the error bag, `getRawState()`.
`dehydrateItems()` then `array_values()`es the map, so everything after —
`getState()`, `getData()`, the record property — sees a positional list, and
the uuids never persist. Two consequences: the same field shows a *different*
absolute path on every mount (never key an assertion or a morph anchor on a
literal uuid), and under `Repeater::fake()` (uuid generation disabled, the test
idiom) items are positionally keyed end to end, which the record walks already
survive — their `strval()` normalisations were landed for exactly this key
shape.

### The item pipeline runs children-first, and before the null-fill pass

`hydrateItems()` is an `afterStateHydrated` hook, so *when* the schema fires
those hooks decides whether it sees the record's shape or a wreck of it. Two
orderings are load-bearing, and both were corrected in schemas' root
[`HasState`](../schemas/schema.md#hasstate-still-lacks-constant-state-relationships-and-partial-rendering)
with this epic. **Hooks run before `fillStateWithNull()`**: an item's raw value
is a scalar in simple mode (`['x', 'y']`), and the null-fill pass writes null at
every unset leaf path — `tags.0.value` — which clobbers the scalar the wrap
step was about to read, so a record fill would land two empty items. **Hooks run
children-first**, each descent re-reading `getChildSchemas()`: a nested
repeater's hook has to re-key its own items *before* the outer one re-keys the
map they sit in, or the inner component writes at a path its parent has just
renamed and vivifies phantom items beside the real ones. Filament reaches both
orderings for free — it hydrates recursively and fires each component's hooks at
the end of its own descent — where the port walks flat lists, so it states the
order explicitly. Dehydration already ran inner-first (a component's
`mutateDehydratedState` applies after its children's), which is why a nested
repeater positionalises inner-first and the shapes compose.

### A structural action fires the repeater's afterStateUpdated hooks itself

Every add/delete/clone/move body ends in
`$component->callAfterStateUpdatedHooks()`, which reads like Livewire residue
and is not. The host's `#[PostHydrate]` diff pass — the thing that normally
notices a changed state path and fires the hooks for it — runs *before* the
mounted action executes, so a mutation the action makes is invisible to it until
the next round-trip. Without the explicit call an `afterStateUpdated()` on the
repeater would fire one request late, against the previous item map. It fires
exactly once per action, including at a reorder edge where the item map comes
back unchanged.

### The blank test judges a repeater by its own value

A `nullWhenBlank()` repeater — and any repeater sitting inside another nullable
branch — is judged by `isBlankStateValue()` over its own slice (`blank([])`:
an empty list is blank, one item is not, even an all-empty item), **never** by
the leaf-enumeration arm the other branches use. That is not a shortcut but a
correctness requirement: at mapping time the slice has already been
positionalised while the declared leaves still carry uuid paths, so enumerating
leaves would find every one absent, judge the branch vacuously blank, and null
a repeater that has items — silent data loss. Judging by value answers the same
question in both bags (raw uuid-keyed at validation, positional at mapping), so
the [two-bag hazard](../schemas/belongs-to-record.md#a-nullwhenblank-closure-sees-two-different-bags)
does not widen here. The default stays Filament's: an untouched empty repeater
dehydrates `[]` and writes `[]`; `nullWhenBlank()` is the opt-in that turns
that into SQL `NULL` on a nullable `?array` column, and a non-nullable target
meets the [existing loud refusal](../schemas/belongs-to-record.md#a-branch-the-record-cannot-null-is-refused-loudly).

### An item field derives no requirement from the record

[Derived requirement](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)
reads the record's write-side type at the field's record path, and at
`tags.{uuid}.name` there is nothing to read: the walk stops at `tags` (a plain
`array` declares no element type — PHP has none to declare, and
[docblock generics are invisible by design](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point)),
`describe()` answers null, and null means
[keep the pre-types behaviour](../schemas/record-type-reader.md#a-null-recordtarget-means-no-type-answer-not-no-such-property).
So item fields are never auto-required and never auto-suppressed — an item
field that must be filled says `required()` out loud, which validates per item
through the ordinary collector. The repeater itself also derives nothing, by
the existing `isCollection` skip. This is acceptance, not a gap to close:
teaching `describe()` to see through an item segment would need element types
that do not exist in the language.

### Unmanaged item keys round-trip through the client

`fill($record)` reads the repeater's property **whole** — the walk's
leaf-granularity read scope stops at a component that owns its subtree — so a
key inside an item that no item field declares (`['name' => 'x', 'legacy' =>
true]`) enters the live bag, travels to the browser, and is written back on
save, surviving add/remove/reorder because structural actions move whole item
payloads. Two things follow. Unlike a `Section` over an array branch, a
repeater **exposes undeclared item keys to the client** (and a client can
tamper with them) — do not keep server-only values inside repeater items.
And they survive only as payload: scoping the read to declared leaves, the way
the section walk does, would need to know *which* saved item a state item is to
merge into — the identity question this epic's
[scope boundary](#object-collections-stay-refused-until-a-relationship-supplies-the-identity)
defers. Filament behaves identically.

### A cast item is schema-complete, so unmanaged keys stop riding

Under a declared `itemsAs()` cast the semantics
[directly above](#unmanaged-item-keys-round-trip-through-the-client) invert, and
deliberately: `get()` consumes only the item class's name set (constructor
parameters ∪ accessor-reachable properties) and ignores every other key
fail-closed, `set()` emits only the readable half of the same set, so undeclared
DTO state does not survive the round trip and a tampered extra key dies at
construction instead of reaching the record. That is the value-object contract —
schema-complete by definition — not a gap; the name-set mechanics and the three
guards (non-instantiable class at declaration; wrong-class or scalar item at
fill; a non-optional constructor parameter meeting null, whose message points at
`required()` on the feeding field) are pinned in
[object-state-cast.md](../schemas/object-state-cast.md#the-name-set-is-the-fidelity-contract).

The set the item bag carries is the *class's*, not the schema's: a DTO member no
item field declares still rides in the live item (`note` beside a lone `title`
input) and is written straight back into the fresh instance, so the round trip
is lossless for everything the class names and lossy only for keys it does not.

The same per-item reading is why `simple()` and `itemsAs()` refuse each other,
loudly and in either declaration order. The simple arm's dehydrate pluck
(`$itemData[$name] ?? null` behind `is_array`) would silently null every
constructed object, and the composed meaning — a one-scalar item that is also an
object — belongs to a *leaf* cast on the simple field
(`->simple(TextInput::make('value')->stateCast(…))`), which the shipped child
walk already runs correctly. The `LogicException` names that path.

Conversion closures beside a **prebuilt** `ObjectStateCast` refuse the same way,
and in both spellings: the declaration-time guard catches the literal instance,
and a second guard in `getItemCast()` catches the instance a closure declaration
resolved to. Without the second one, `itemsAs(fn () => $cast, denormalizeUsing:
…)` would silently drop the closures the literal spelling throws over — a
fail-fast the evaluation seam would otherwise hide.

### A relationship repeater keys its items by identity and diffs the collection

`relationship()` swaps the item pipeline's two ends and leaves everything
between them alone. `fill()` no longer reads the property (an object collection
would be skipped as an object leaf anyway) — the
[component-tier load hook](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) reads the collection
and returns one item per child, keyed `record-<recordKey>` (Filament's shape,
over [`AssociationReader::getRecordKey()`](../schemas/association-reader.md)),
each item filled by reading the item schema's own declared leaf paths off that
child through the record accessor. So the keys stop being per-request uuids and
start being *identity*, and three places follow from it: `hydrateItems()`
returns before it re-keys (the map is already keyed, and re-keying would erase
the identity), `dehydrateItems()` returns before it positionalises (position is
not what the save reads), and an item the add action mints keeps its uuid key —
which is precisely how the save tells a new child from a loaded one.

`getData()` hands the whole keyed map to the save hook, which builds the
**desired list** in state order: a `record-` key resolves to the very instance
the collection holds (never a fresh `getReference()` — strict identity is what
makes an update an update), an un-prefixed key constructs the association's
target class through
[`PropertyAccessor::constructRecord()`](../schemas/property-accessor.md#the-mappers-construct-or-refuse-is-one-method-three-callers-borrow)
— the mapper's shared construct-or-refuse, so an item class needing constructor
arguments is refused in the same words everywhere — and a loaded child no item
names is simply left out. The list goes
to [`setAssociationOnRecord()`](../schemas/property-accessor.md#the-association-write-is-the-collection-refusals-counterpart), whose
adder/remover diffing keeps the collection instance, calls `addSection()` for
the new children (so the owning side is set by the record's own code) and
`removeSection()` for the departed — which `orphanRemoval` turns into a DELETE
at flush. Nothing is deleted by the repeater itself, which is why an association
not mapped `orphanRemoval` is refused rather than silently leaving rows
behind — and refused at **fill**, like the array mode's own early refusal, so the
mapping is corrected before anyone types into the form rather than at the save
that would have lost the deletion. A to-one association is refused there too: a
repeater over one has nothing to repeat. Item fields are written per declared leaf path with the *child* as
the root, so a dotted item path (`address.city`) reaches through the child's own
graph on [`setOnRecord()`](../schemas/property-accessor.md#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs)'s terms.

Five consequences worth stating. A `record-` key naming a child the collection
no longer holds **throws** — it means the record changed under a filled form, and
resurrecting it as a new row would be worse than a refusal. The clone action
mints a uuid key, so cloning a loaded item creates a *new* record rather than
double-writing the old one. `orderColumn()` stamps `0..n-1` in state order onto
each child before the write, and the read side is Doctrine's — the association
needs its own `#[ORM\OrderBy]` on that property, since nothing here orders the
query. A mutation callback returning **null** declines the item, Filament-exact:
before-create skips constructing it (nothing is written, nothing joins the
desired list), before-save skips writing it but the child stays in the desired
list, so declining a save is not a deletion. And the whole diff runs inside the
single `getData()` call, with no flush of its own: the caller flushes, once,
exactly as with every other mapped field.

Reorderability is coupled to the order column, and the coupling is
call-order-sensitive exactly as in Filament. `relationship()` ends with
`reorderable(false)`, because a collection whose order nothing persists would
let the user arrange rows that come back in Doctrine's order on the next load.
`orderColumn()` ends with `reorderable(fn (Repeater $component): bool =>
$component->getOrderColumn() !== null)`, which switches it back on — so
`relationship()->orderColumn('position')` is reorderable and
`orderColumn('position')->relationship()` is **not**, the later call winning
either way. Ours reads the column through a closure rather than passing it as
Filament's truthy `reorderable($column)` does, so a closure-valued column is
resolved at evaluation time rather than counted as truthy while unresolved, and
`orderColumn(null)` reads as not reorderable rather than as the literal `null`
Filament would hand `reorderable()`.

### The drag reorder round-trip accepts the client's order

Drag reorder is on by default, exactly as in Filament, and it is the one
structural action whose argument is a *whole ordering* rather than one item key.
The list element carries support's drag control
([sortable.md](../support/sortable.md)); the control answers a drop with an
ordered list of the `data-sortable-item` keys, forms' repeater controller turns
that into a `reorder` mount, and the action writes the items back in that order.
Nothing is sent per item, so a drag is one round-trip regardless of how far the
item moved.

`isReorderableWithDragAndDrop()` is `reorderableWithDragAndDrop() &&
isReorderable()`, mirroring the buttons arm, so `reorderable(false)` — or a
disabled field — silences both surfaces at once, and the handle, the control and
its markers all drop out of the DOM together rather than rendering inert. The
handle is the reorder action's own trigger, stamped `data-sortable-handle`
through `extraAttributes(merge: true)`, which is why it renders with
`liveComponentClickHandlerEnabled(false)`: it is grabbed, never clicked.

### Collapse state is the client's once the controller connects

The server renders the collapse state it knows — `sy-collapsed` on an item the
component reports collapsed, and the CSS hides that item's content. From the
moment the repeater's Stimulus controller connects, the item's own
`data-collapsed` attribute is the truth instead, which is why every collapse
rule in the stylesheet is a **pair**: `.sy-collapsed:not([data-collapsed])` for
the server's answer and `[data-collapsed="true"]` for the client's, with the
matching `:not(.sy-collapsed):not([data-collapsed])` / `[data-collapsed="false"]`
pair for the expanded arm. A morph that re-renders an item therefore cannot
snap it shut behind the user's back: the attribute the controller stamped
outranks the class the server sent.

`persistCollapsed()` puts `sy-cloak` on the root, so the whole repeater stays
invisible until the controller has replayed the stored state — the same cloak
idiom `Tabs` uses. The collapse and expand triggers are two stacked icon
buttons, both always rendered, the CSS fading and rotating whichever one does
not apply; that is Filament's markup and it is what makes the transition
possible without a re-render.

Three structural actions re-open the repeater for the rest of the request:
`add`, `clone` and `addBetween` each call
`collapsed(false, shouldMakeComponentCollapsible: false)` before firing the
`afterStateUpdated` hooks, so a repeater declared `collapsed()` does not swallow
the row the user just asked for. The named argument matters: without it the
call would *make* a non-collapsible repeater collapsible as a side effect of
adding an item.

### `addBetween` hides by default and crowds out the label between items

`getAddBetweenAction()` ships `visible(false)`, Filament-exact — the between-item
add button is opt-in through `addBetweenAction(fn (Action $action) =>
$action->visible())`. Two consequences worth stating, because both look like
bugs from the outside. First, `mountAction()` unmounts a hidden action
(`isDisabled()` reads `isHidden()`), so a test or a demo that mounts
`addBetween` without switching it on gets silence rather than an error. Second,
the two between-item surfaces are an `if`/`elseif`, not a pair: where a visible
`addBetween` renders, `labelBetweenItems()` does **not**, even when both are
declared. The label is the fallback for the gap, not a companion to the button.

A third consequence, narrower and vendor's own, lives in the action body: the
positional arm only appends. `getAddBetweenAction()` walks the raw state
rebuilding it key by key and, when `generateUuid()` answers `null`, inserts with
`$items[] = []` — which under integer keys takes the next free index, the one the
following iteration overwrites with its own item. So a mid-list insert into
positionally keyed state loses the new item and `fill()`s the neighbour that took
its key; an insert after the last item is unaffected. The collision is shared
with Filament's own `getAddBetweenAction()`, line for line, and it sits off every
default path — uuid keying is on unless the null-uuid mode is chosen, whether by
[`Repeater::fake()`](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid)
or by a consumer's own `generateUuidUsing(false)` — so it is recorded here as a
vendor-parity edge rather than fixed. Hardening it (rebuilding the list positionally, which every
dehydrate renumbers anyway) is the sweep's to take.

Neither between-item element is a sortable item — no `data-sortable-item` — so
the drag control skips them and an ordering never contains a divider's key.

### An extra item action reads its item's state

`extraItemActions()` appends actions the header (or the simple/table row) renders
per item, each bound to that item with `['item' => $itemKey]`. The binding is
what makes the action's `$schemaState` resolve to the *item's* state rather than
the repeater's: `Action::getSchemaState()` sees the host implements schemas'
`HasExtraItemActions` contract and answers `getItemState($itemKey)`. So
`Action::make('inspect')->action(fn (array $schemaState) => …)` receives one
item's array, which is the whole point of the family.

The forms concern keys its cache by `getName()` rather than by position, because
schemas' `cacheActions()` splices the map into its own with a spread and PHP
renumbers integer keys through a spread — a positional list would collide with
the structural actions already in that cache. The splice does not run
`prepareAction()` over the merged map, so the concern stamps
`schemaComponent($this)` itself, along with the icon-button defaults
(`defaultColor('gray')`, `defaultSize(Size::Small)`,
`defaultTemplate(Action::ICON_BUTTON_TEMPLATE)`) that a caller's own
`color()`/`size()`/`button()` still overrides.

### The items grid is support's `grid()`

`grid()` comes from schemas' `HasContainerGridLayout` and the item list carries
whatever support's `grid()` Twig function emits for it — `sy-grid`, the
per-breakpoint `sy-grid-cols` classes, and the `--cols-*` custom properties. The
repeater's own stylesheet therefore declares no `display: grid` on either item
list; it only adds the spacing and alignment the grid does not carry. An
ungridded repeater still gets `--cols-default: repeat(1, …)`, which is why the
default rendering is a single column without a special case.

The simple and table views take the same call, so `grid()` composes with
`simple()`; `table()` lays its items out as rows and the grid on the (unused)
list element never renders there.

### Table mode pairs columns by position

`table([TableColumn::make('Name'), …])` switches the whole rendering surface:
`getTemplate()` answers `TABLE_TEMPLATE` whenever columns are declared, so
`table()` outranks `simple()` when both are set, and an explicit `template()`
still outranks both. `isTable()` is `filled($this->getTableColumns())`, so
`table([])` is not a table — an empty column list reverts to the default view
rather than rendering a headerless table.

A `TableColumn` is a label plus header options (`hiddenHeaderLabel()`,
`markAsRequired()`, `wrapHeader()`, `width()`, `alignment()`,
`verticalAlignment()`); it holds **no** field. The pairing with the item's
components is positional, by Filament's counter: the first column takes the
first component that consumes one, the second the second, and so on. A
[`Hidden`](hidden.md) field consumes no column, an invisible component consumes
one and renders an empty `sy-hidden` cell in its place, and once the columns are
exhausted every remaining component — `Hidden` included — is dropped. That drop
is **silent and order-sensitive**, because the budget guard runs *before* the
`Hidden` branch, faithful to vendor's guard order: a `Hidden` survives only while
some column is still unclaimed, so it has to sit ahead of the component that
fills the last one. The demo's table card is the live example, declaring
`Hidden::make('id')` first for exactly this reason. The column's
`verticalAlignment()` reaches the cell, not the header; its
`alignment()`, `width()` and `wrapHeader()` reach the header, not the cell.

The reorder and actions columns are conditional in their own right: the reorder
header cell renders only with more than one item *and* some reorder surface on,
and the actions header cell only when the row can carry an extra item action, a
clone or a delete. Each header cell carries an `sy-fo-sr-only` label ("Reorder",
"Actions") so the table is still readable to a screen reader. An item schema
that contains anything other than a schema component — an `Action`, say — is
refused with a `LogicException` naming the class, because there is no cell such
a thing could sit in.

### A save records which collection key each item was written to

`getItemKeys()` answers the
[violation-path translator](../schemas/violation-path-translator.md#the-index-is-a-collection-key-not-a-position),
which resolves `sections[2]` against the key the **record's** collection holds
that element under. State order is not that key: the association write goes
through the accessor's adder/remover diffing, which leaves an unchanged
membership in its old order and a thinned one keyed sparsely, so the desired
list's order is not what the collection ends up keyed by.

So the save hook reads the collection back after the write and pairs each
element with the item key it was written from — **by identity**, since an item
the client added has no record key of its own until a flush — and
`getItemKeys()` returns that pairing. Before any save (a fill, or an array-mode
repeater whose items the record holds as a positional list) there is nothing to
pair and the state's own keys are returned in order, which is exactly how a
plain array is written.

`getItemStatePaths()` is the same list the fill and save arms already walk,
published on the contract: a violation naming an in-item path it does not carry
is a path no field maps, and the translator
[folds it at the item](../schemas/violation-path-translator.md#a-violation-inside-an-item-folds-by-the-same-rule-as-one-at-the-root)
rather than inventing a state path.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional
ones say what would reverse them.

### The relationship family loses its caches, its lifecycle hooks and its query scope

Four members of Filament's relationship surface have no counterpart here, each
for a stated reason rather than as deferral.

`getCachedExistingRecords()` / `clearCachedExistingRecords()` **are not ported**:
Filament caches the related rows because its fill and its save each run their own
Eloquent query. Ours reads the collection off the record through the accessor
both times, and the collection *is* the cache — Doctrine's identity map already
guarantees the same instances come back, which is the very property the
strict-identity update depends on. A memo on top would only add a second source
of truth.

`afterCreate()` / `afterUpdate()` / `afterDelete()` **are not ported**, and this
one is a real capability gap: Filament fires them around its per-item
`save()`/`delete()` calls, and in the
[single-flush shape](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) no such moment exists —
`getData()` mutates an object graph and returns, so at the time the repeater is
done nothing has touched the database and no row has an identifier yet. Firing
them at map time would be a lie about when they run. Consumers that need a
post-write moment use Doctrine's own lifecycle events (`postPersist`,
`postUpdate`, `postRemove`, or an `onFlush` subscriber) — the same seam the ORM
already gives every other write in the application. Reverses only if a
`saveRelationships()` phase with its own flush is ever adopted, which the epic
[decided against](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk).

`dehydrated(false)` **is not applied**. Filament prunes the repeater's state from
the dehydrated bag because its record save would otherwise try to write an array
into a relation. Our dehydration walk is what *routes* the value: `getData()`
consults the save hook only for a path the bag still carries, so pruning would
switch the whole feature off. The state stays in the bag and the hook takes it.

`relationship()`'s second parameter, `$modifyQueryUsing`, is **accepted and
refused**. The signature stays Filament's so the call site ports unchanged, but
the items come from the collection the record already holds rather than from a
query of our own, so a scope would silently never run — and a silent no-op is
exactly what this codebase refuses. Passing one throws, naming Doctrine's
`#[ORM\OrderBy]` and a filtered collection as the two places that scoping
actually belongs. Reverses if the fill ever grows a query of its own (a paginated
or filtered relationship repeater), at which point the parameter gets its
Filament meaning back.

Two smaller shapes go with them. The mutation callbacks are evaluated with the
item array injected as **`state`**, not Filament's `$data` — every other
mutation seam in the port names it `state` (`mutateDehydratedStateUsing`,
`dehydrateStateUsing`), and the evaluation vocabulary is one word per meaning;
`mutateRelationshipDataBeforeSaveUsing` additionally receives the child as
`record` **and** typed by its own class, so `fn (PostSection $section)` resolves
the way the [component-tier hooks](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)
already inject the record. And `simple()`, `itemsAs()` and `relationship()` are now a **three-way**
mode exclusion, each pair refusing in either declaration order for the reason
that pair fails: the simple pluck would reduce a related record to one scalar,
and a freshly constructed value object would throw away the identity the
relationship exists to preserve.

### The order stamp is 0-based, written onto the child, and out of the callbacks' reach

Filament stamps `$itemData[$orderColumn] = $itemOrder` with `$itemOrder`
starting at **1**, *before* either mutation callback sees the item — so a hook
reads the position it is about to be given and may overwrite it, and the value
reaches the record through the same `fill()` that writes every other key
(`vendor/filament/forms/src/Components/Repeater.php:1024-1030`). Here the stamp
is `0..n-1`, written straight onto the child with
[`setOnRecord()`](../schemas/property-accessor.md#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs)
*after* the callbacks have run, and never present in the item array at all.

Two reasons, one per half. **0-based** because the number is the element's index
in the collection the save is building (`count($desiredRecords)`), not a
human-facing rank — nothing here renders it, and the read side is Doctrine's
`#[ORM\OrderBy]`, which sorts either numbering identically. **Outside the
callbacks** because the port writes the item array through the item schema's
*declared* leaf paths, and an order column is precisely the property no field
declares: a key a callback added under that name would be dropped on the way to
the record, so offering it there would be a seam that silently does nothing.

The cost is real and bounded: a callback cannot read or rewrite the position,
which Filament's can. Reverses if a case turns up for reordering from a hook —
the stamp would move into the item array before the callbacks and be written
from there, alongside the declared paths rather than through them.

One consequence of the [null-skip](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
arm follows from the ordering half. A child whose before-save callback declined
it is kept in the desired list but never written, so it keeps whatever position
it already had while the items around it are restamped — the same outcome as
Filament, where a `continue` skips the `fill()` that would have carried the
stamp. A declined **create** costs no number here (the stamps stay contiguous),
where Filament's `$itemOrder` has already been incremented and leaves a gap.

### The fill callback may not decline an item

`mutateRelationshipDataBeforeCreateUsing` and
`mutateRelationshipDataBeforeSaveUsing` return `?array` because Filament's do —
null declines the item. `mutateRelationshipDataBeforeFillUsing` returns `array`
in Filament too, and here a null from it **throws** rather than being read as a
skip: the fill is showing the children the record already holds, so declining one
would hide a row the user is editing, and the state the form is built from would
disagree with the collection the save diffs against. The refusal names the
callback and says which two may decline.

### Every item schema is keyed as well as re-pathed

Each item schema is a `getClone()` of the `components()` template re-pathed onto
the item's key, and takes `->key((string) $itemKey)` on top — a stamp Filament
does not make, and not a redundant echo of the state path. Filament's
`Schema::getKey()` ends in `?? getStatePath(isAbsolute: false)`, so an unkeyed
item schema keys itself by the item's state path for free. The port's root-tier
[`HasKey`](../schemas/schema.md#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback)
drops that fallback, so an unkeyed item schema returns `null` and its
`getInheritanceKey()` walks up to the repeater's own key: every item's fields
would then resolve the same absolute key — and the same DOM id, since `getId()`
is `getCustomId() ?? getKey()` — collapsing onto `<repeaterKey>.<fieldName>`
across all items, with the `<li>` ids and each field wrapper's `for` /
`aria-labelledby` targets duplicated with them. It is pinned by the render test
asserting each item's input id (`<repeaterKey>.0.name` against
`<repeaterKey>.1.name`), which collapses to one value the moment the stamp goes.
The stamp is the first call site compensating for that gap (the schemas trail's
open **Next** item on it points here); it comes back out if the schema-tier
fallback is restored.

### An item schema carries no constant-state or inline-label stamp

Filament builds each item schema with `->constantState(null)` and
`->inlineLabel(false)`; ours builds neither, and both are waiting rather than
rejected. Constant state is
[unported for want of a consumer](../schemas/component.md#relationship-derived-and-constant-state-are-dropped-from-the-component-trait) —
the Doctrine relation layer did not bring one, and the only thing that would set
it on an item is the record stamping this epic does not do — and schemas'
`HasInlineLabel` is unported (deferred in M1 · 3), so an item schema has no
inherited inline-label state to switch off. Each stamp lands with its owner.

### Object collections stay refused until a relationship supplies the identity

The mapper's object-collection refusal
([both triggers](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused))
**does not relax** for a plain Repeater; the array-backed epic narrowed what the
message promises. `refuseCollection()` named "the Repeater epic" as the future
owner; once the Repeater existed that read as a bug report, so the message was
reworded to state the real boundary: array-backed lists are the Repeater's, and
object collections need identity. That precedent applied a second time here —
"which arrives with the Doctrine relationship layer … until then, map this
property by hand" is a wait the reader has already finished, on the one path
this epic fixes. Both messages now name the answer instead: `relationship()`
supplies the item identity, so declare it on the component bound to the
collection; hand-mapping after `getData()` is what is left for a collection no
component declares one for. A repeater also refuses *early*:
`hydrateItems()` asks the type reader at the repeater's own record path and
throws when the declared type is an object collection — otherwise `fill()`
silently skips the object-valued read, the repeater renders empty, and the
developer meets the mapper's refusal only on first save. A
DTO list typed only by docblock (`/** @var list<Money> */ private array $items`)
remains invisible to both checks and takes the array path — the repeater will
overwrite it with value-shaped arrays, the
[recorded price of reflection-only types](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point).
`RecordTarget`'s provenance fields stay
[skipped](../schemas/record-type-reader.md#the-value-object-carries-no-provenance):
that epic was their named candidate, but its refusal builds the target it
consumes in the same method, so provenance still has nothing to tell a caller.

**The reversal fired**, exactly as written — identity-keyed item state landed
with [`relationship()`](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection),
so both refusals now step aside for a repeater that declares one: the early
check returns before it reads the type, and the mapper's own refusal is never
reached because the save hook, not `setOnRecord()`, owns that path. The reversal
is *scoped to the declaration* — everything above still holds verbatim for a
repeater without `relationship()`, so the early refusal itself is unchanged and
still fires; only its *advice* moved on, reworded in the same commit that made
the old advice false. The docblock-only DTO list is unaffected in both modes.

### A repeater owns its record subtree

Sylament-only, because the record path map is Sylament-only. A new
component-tier predicate, `ownsRecordSubtree()` (default false, `Repeater`
true), marks a component whose own dehydrated value **is** the record value for
everything beneath its path. The walks change in three places, and the
permanent write-ups land in
[belongs-to-record.md](../schemas/belongs-to-record.md) with the code:
`getRecordPathMap()` treats an owner as a leaf even though it has path-bearing
descendants — resolving the
[flagged predicate](../schemas/belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths)
— and drops every collected path beneath an owner; the branch pass's presence
guard asks for the owner's own path instead of leaf paths beneath it, and after
nulling an owner-branch skips the exact path as well as the prefix; the
built-in blank test short-circuits to the
[value arm](#the-blank-test-judges-a-repeater-by-its-own-value). Dehydration
needs no change — its pruning already forgets by own-path, which is
subtree-safe — and forms' requirement walk inherits the new arms through
`shouldNullBranch()`. One refusal rides along: a path-bearing component
declared *outside* the owner's tree whose path nests under the owner's
(`TextInput::make('tags.extra')` beside `Repeater::make('tags')`) would
silently neither fill nor save — its reads and writes belong to the owner's
wholesale value — so the map build throws, naming both components and the two
ways out (move the field inside the repeater's schema, or move its path out
from under the repeater).

### The item cast fires at the repeater's two pipeline boundaries, not in the generic seam

Filament has no value-object item mode to diverge from — its answer to
object-shaped repeater state is `relationship()`, identity-keyed Eloquent
mapping, [the surface this excludes](#object-collections-stay-refused-until-a-relationship-supplies-the-identity)
— so `itemsAs()` diverges by existing at all. Inside the port it diverges a
second time, from the generic state-cast seam: the cast object reuses the
`StateCast` contract but is **not** registered into `$stateCasts`, because that
seam's four firing points are each wrong for a container and two of them corrupt
nested repeaters. Get-ward at `getStateToDehydrate()` would construct the outer
DTOs while a plain inner repeater's uuid map still sits inside them — the
inner's positionalisation runs later, in the mutate pass, whose `setInArray()`
would then vivify phantom uuid paths beside the already-built DTO list. Set-ward
anywhere in the hook phase is the mirror image: `afterStateHydrated` runs
children-first, so an inner repeater would read through outer items that are
still DTOs, meet a non-array mid-path, and the write-back would clobber the DTO
wholesale under `setInArray()`'s documented contract.

So the repeater applies the cast itself, at the two item-pipeline boundaries
where the directionality is already correct: **set-ward in its `hydrateState()`
position** (the flat pass walks parents before descendants and runs before every
hook, so record-read DTOs are deconstructed to arrays — keys preserved — before
any inner component or the `hydrateItems()` re-key looks at them), and
**get-ward at the tail of `dehydrateItems()`** (the mutate pass runs
children-first, so an outer cast constructs from items whose inner repeaters are
already positionalised — and already objects, when the inner declares a cast of
its own, so nesting composes in both directions). `array_values()` therefore
always runs over arrays, and construction always follows it. All four nesting
quadrants are pinned by tests.

**Everything mid-edit keeps today's shapes.** Because `$stateCasts` stays empty,
`getState()`, `getOldState()`, the `$state` closure injection, `getItemState()` /
`getRawItemState()`, every structural action body, the structural freshness
fingerprint, and validation (raw, per path, unchanged) all see exactly what they
see on an uncast repeater — uuid-keyed value arrays. The
`minItems()`/`maxItems()` `Count` constraint counts the raw map. Programmatic
`$set()` takes array items, as it does today; handing it DTOs is undefined, not
converted. The DTO shape exists at exactly two places: the record read that
`fill()` makes, and the dehydrated bag from the mutate pass on — which is what
`getData()` maps and what a host's `getState()` returns.

`nullWhenBlank()` composes unchanged for the built-in test. The
[owner value arm](#the-blank-test-judges-a-repeater-by-its-own-value) judges
`blank()` over the branch's own slice, and that answer is shape-stable: a
uuid-keyed array map at validation and a positional DTO list at mapping are both
non-empty arrays or both empty ones, so an empty cast repeater still nulls and a
filled one still survives, over `?array`-typed and untyped properties alike (a
`Collection`-typed property never gets this far — the early refusal fires
first). A **closure** condition, though, now sees arrays at validation and DTOs
at mapping — a widening of the recorded
[two-bags hazard](../schemas/belongs-to-record.md#a-nullwhenblank-closure-sees-two-different-bags)
that landed in that note's text with this code.

### Item-class inference is deferred; the declaration stays explicit

`itemsAs(Line::class)` names the item class out loud, and that is the only arm
that ships. *Implicit* derivation — a `list<Line>` docblock alone flipping the
repeater into object mode — is **rejected**, permanently: it would silently
change every existing docblock-typed array form the day it shipped, and a
library cannot assume its consumers run PHPStan over their docblocks.
*Opt-in inference* — a bare `itemsAs()` deriving the item class from the record
property's docblock — is **deferred out of the epic by decision (Martin,
2026-08-05)**. The constraints are recorded so a later slice designs against
them instead of re-deriving them: it reads through a separate, scoped docblock
reader (`PhpStanExtractor`-backed, used for item-class resolution only), never
through [`RecordTypeReader`](../schemas/record-type-reader.md), whose
pinned-to-reflection mutator-first contract and collection flags must not
change; an underivable type (no docblock, `array<string, mixed>`, a union, a
non-instantiable class) throws loudly rather than falling back to array mode;
and it costs a new production dependency (`phpstan/phpdoc-parser` behind
property-info's `PhpStanExtractor`) — not taken now, and note that the flat
per-item construction needs no docblock extractor at all, which is part of why
the mode ships dependency-free. Sugar over the explicit arm, additive, breaking
nothing. Optional polish that survives the deferral: a dev-time mismatch guard
when a readable docblock contradicts the explicit class.

### The reorder action is hardened and answers only real moves

Filament's reorder body is `[...array_flip($arguments['items']), ...$state]` —
the client's key list flipped into a map whose *values* are the positions it
came from, then spread over by the current state. Two things fall out of that
which do not survive here. The flip is unvalidated: whatever the client sent
becomes an array key, and a key naming no item enters the state carrying an
integer position as its value, so an item schema is then built over an `int`.
And `array_flip()` is only a positional trick for producing keys in the sent
order — the values it produces are always discarded by the spread.

Ours states that directly: the argument must be a **list** (`array_is_list()`,
so a JSON object rather than an array is refused with a `LogicException` naming
the argument), each sent key is kept only when `array_key_exists()` finds it in
the current state, and the result is `$reordered + $items` — the array union,
which keeps the reordered keys in the sent order and appends whatever the client
did not mention, in the state's own order. An unknown key is dropped rather than
invented; an item the client omitted survives rather than vanishing. Both arms
are pinned by
[`RepeaterActionsTest`](../../../packages/forms/tests/Integration/Components/RepeaterActionsTest.php).
The key coercion is PHP's own: a numeric string from the client finds the
integer key a positionally keyed map carries, which is what keeps
[`fake()`](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid)
repeaters reorderable.

### Uuid generation is a mode flag, and `generateUuidUsing(true)` mints a real uuid

`generateUuidUsing()` keeps Filament's `Closure|bool|null` signature, but the
value behind it is stored as two properties — a `bool $generatesUuids` mode flag
and a `string|Closure|null $generateUuidUsing` generator — and `true` mints an
ordinary v4 uuid instead of Filament's `"1"`. Both halves fall out of typing.
Filament's truthy arm evaluates whatever the property holds and returns it from
a `?string` method, so `generateUuidUsing(true)` returns the literal `true`
coerced to the string `"1"` — every item keyed identically, and a coercion
`declare(strict_types=1)` forbids outright. Here `true` means what it reads as:
generation is on, with no generator override, so the default arm runs. The
two-property split is the same fact seen from the type side: `evaluate()`'s
generic reads a closure's return type off the property union by subtracting
`Closure` from it, which the [closure-typing convention](../../../CLAUDE.md)
makes the *only* place a closure's return type may be stated. Filament's single
property spends those arms on modes, so the generic infers `Closure`, and every
check on the result is dead code at level max. Splitting the mode out lets the
union say what it is for — a uuid, or a closure producing one. A generator
returning something else therefore raises PHP's own return `TypeError` naming
the component's `generateUuid()`; no hand-thrown guard rides along, because a
`?string` inference makes one unreachable.

### The move helpers correct two vendor edge defects

`Sylament\Forms\array_move_before()` / `array_move_after()` port Filament's
forms helpers with two edges fixed rather than copied, both proven by probe
against the vendor bodies. An **unknown key** returns the array unchanged —
Filament's after-arm runs `array_search()`'s `false` through `+ 1`, indexing a
neighbour that may not exist and corrupting the array with a `key => null`
entry under an "Undefined array key" warning. And the **neighbour guard** is
`=== null` rather than `blank()`, so an empty-string key is a movable neighbour
here where Filament aborts the swap — `''` is a legal array key, and refusing
to move past it was the helper leaking value semantics into key mechanics. Key
matching is strict over `strval()`-normalised keys, the same normalisation the
record walks use, so a client-sent `'2'` matches a fake-mode integer key
without the loose comparison Rector rejects.

### A defaults fill over a populated repeater resurrects its pre-fill items

`Schema::fill()` with **no argument** over a repeater that already holds items
writes the declared default and then rebuilds every pre-fill item beside it as an
all-null ghost. Measured over
`Repeater::make('tags')->defaultItems(1)->components([TextInput::make('name')])`,
with the uuid generator pinned to a fixed key so the shape is readable — the bag
holds two items, the fill declares one, and three come out:

```
before  ['tags' => [['name' => 'a'], ['name' => 'b']]]
after   ['tags' => ['fresh' => ['name' => null], 0 => ['name' => null], 1 => ['name' => null]]]
```

The mechanism is the flat walk. Schemas' `Schema::hydrateState()` materialises
`getFlatComponents()` **once**, up front, so the list it walks holds the item
schemas built from the *pre-fill* map — fields at `tags.0.name` and
`tags.1.name`. The repeater itself is visited first (parent-first) and writes its
default, a freshly keyed map, recording it at `tags` in the walk's shared
`$hydratedDefaultState`. The stale item fields run next, and that record does not
cover them: the default's keys are new, so `tags.0.name` is not a path any
default mentioned. Each field is defaultless, so each
[null-writes](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default),
and the accessor vivifies the missing intermediates on the way — which is what
rebuilds `tags.0` and `tags.1` as items. Filament never reaches this: its
`hydrateState()` recurses, re-reading `getChildSchemas()` at each descent, so the
item schemas it visits are the ones the default just created.

**Pre-existing shape, escalated by the defaults-fill fix.** Materialise-once is
older than this; what changed is that a defaultless component now *writes* null
where it used to be skipped, so a stale item schema went from inert to
state-producing. Nothing that ships reaches it, caller by caller:
`HasFilters::mountTableFilters()` and `CanBeMounted`'s default `mountUsing` fill
a freshly built schema over an empty bag; `Repeater::getAddAction()` fills the
one item schema it has just created empty; `HasSchemas::fill(null)` is the
host-facing entry and every host calls it from `mount()`, again over an empty
bag; and `HasFilters::resetTableFiltersForm()` — the only one that fills a
**populated** bag, which is how the null-write's absence was caught at all —
fills a filters form, and no filter that ships puts a repeater in one. It becomes
reachable the first time a host re-fills a populated form: a "reset to defaults"
button over a form holding a repeater is the exact shape.

Today's output is pinned as-is by
[`it characterizes the ghost items a defaults fill leaves over a populated repeater`](../../../packages/forms/tests/Unit/Components/RepeaterTest.php),
a characterization test rather than a wanted one — the assertion changes when the
fix lands, and the diff is what states its cost. The fix is on the walk, not on
the repeater: either recurse the way Filament does (re-reading child schemas per
descent), or flush a container's cached child schemas the moment its own default
replaces the state they were derived from.

### The repeater item owns its collapse state, unlike the Section

[`Section`](../schemas/section.md) collapses through a `<details>` element — the
browser owns the open/closed state and the server only seeds it. A repeater item
cannot: it needs a header that is a click target *and* holds its own buttons, a
collapse-all/expand-all pair reaching every sibling, and a per-item persisted
state, none of which `<details>` gives without fighting it. So the repeater item
is a plain `<li>` whose state is a class before its controller connects and a
`data-collapsed` attribute after, and the content is hidden by CSS rather than
by the element's own semantics.

The cost is the one `<details>` would have paid for us: the collapsed content
stays in the DOM. That is deliberate rather than incidental — the fields inside
a collapsed item must keep their live bindings, so the item can be submitted and
validated without ever being opened — and it is why `canConcealComponents()`
returns `isCollapsible()`. A concealing component drops the native constraints
(`required`, `maxlength`) off the controls inside it, so a browser cannot refuse
to submit a form over a field the user cannot see; the server-side validation
still runs. `Section` reaches the same contract by the same reasoning.

### Hidden fields render in a trailing hidden cell

Filament's table view emits a `Hidden` field's markup **between** two `<td>`
elements — `<?= $schemaComponent->toHtml() ?>` with no cell around it — which is
invalid inside a `<tr>` and is re-parented by every browser's HTML parser, so
the hidden input lands outside the table and outside the row it belongs to. Ours
collects the row's `Hidden` components instead and renders them in one trailing
`<td class="sy-fo-table-repeater-hidden-cell">`, which the stylesheet hides at
every breakpoint. The markup is valid, the input stays inside its row, and the
cell is invisible either way.

The counter is untouched by this: a `Hidden` field still consumes no column, and
it is still dropped once the columns are exhausted, matching Filament's order
(the column-budget guard wraps the Hidden branch rather than sitting inside it).
So a `Hidden` declared *after* the last column-consuming component does not
render at all — a Filament quirk kept on purpose, since diverging would make the
column budget mean two different things depending on the component's type.

### Deprecated Filament members are not ported

`createItemButtonLabel()`, `disableItemCreation()` / `disableItemDeletion()` /
`disableItemMovement()`, `orderable()` and `inset()` are all tagged
`@deprecated` upstream (or, for `inset()`, an explicit no-op "no longer part of
the design system"). They exist to keep Filament's own historical call sites
compiling; a fresh port has none, and shipping them would hand every new
Sylament user a deprecated spelling beside the real one. A migrating user's
call breaks loudly — missing method, current name one grep away in Filament's
own deprecation notice.

_See also: the global [rename table](../renames.md) — `ownsRecordSubtree()`,
`itemsAs()` and `ObjectStateCast` all sit among the Sylament-only names recorded
there, and the unported `schema()` alias has its row there; everything else in
this file keeps Filament's spelling._
