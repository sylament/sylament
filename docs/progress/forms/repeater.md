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
over a Doctrine collection —
[relation-backed fields inside an item](#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map)
included, repeaters nested in repeaters with them. What is left is the one
**blocked** row,
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
**semantics** link (a permanent note in [Semantics](#semantics)) and/or a
**diverges** link (the permanent note in [Divergences](#divergences)), or `—`.
A row with no _Ports_ link is Sylament-only.

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
| Relationship family — **shipped**: `relationship()`, `hasRelationship()` / `getRelationshipName()`, `orderColumn()` / `getOrderColumn()`, the three setters `mutateRelationshipDataBeforeFillUsing()` / `mutateRelationshipDataBeforeCreateUsing()` / `mutateRelationshipDataBeforeSaveUsing()`, the fill and save arms (Filament's `fillFromRelationship()` / `getStateFromRelatedRecords()` / `saveToRelationship()`, here the two [component-tier hooks](../schemas/belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)). **Not ported**: `getRelationship()` / `getRelatedModel()` (Eloquent objects with no Doctrine counterpart — `AssociationTarget` answers both), `getCachedExistingRecords()` / `clearCachedExistingRecords()`, `afterCreate()` / `afterUpdate()` / `afterDelete()`, the three public callers `mutateRelationshipDataBeforeFill()` / `mutateRelationshipDataBeforeCreate()` / `mutateRelationshipDataBeforeSave()` (the arms call the callbacks through one private helper instead), the `getLabel()` override, item `constantState()` (its record half [ships](#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map), since a nested relationship hook cannot fire without it) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) · [diverges](#the-relationship-family-loses-its-caches-its-lifecycle-hooks-and-its-query-scope) |
| Relation-backed fields inside an item — Filament's `$item->model($record)->saveRelationships()` recursion, plus the item-schema record stamp it needs | [`Repeater::saveToRelationship()`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map) · [diverges](#a-mutate-callback-can-rewrite-what-a-nested-hook-consumes) |
| `partiallyRenderAfterActionsCalled()` / `shouldPartiallyRenderAfterActionsCalled()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | blocked | UX LiveComponent partial-render equivalent | — |
| `array_move_before()` / `array_move_after()` (`packages/forms/src/helpers.php`, the reorder bodies) | [`helpers.php`](../../../vendor/filament/forms/src/helpers.php) | done | — | — |
| Deprecated members (`createItemButtonLabel()`, `disableItemCreation()` / `disableItemDeletion()` / `disableItemMovement()`, `orderable()`, `inset()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#deprecated-filament-members-are-not-ported) |
| `nullWhenBlank()` composition — an empty repeater as `null` instead of `[]` | — | done | — | [semantics](#the-blank-test-judges-a-repeater-by-its-own-value) · [diverges](#a-repeater-owns-its-record-subtree) |
| Early object-collection refusal at hydration | — | done | — | [diverges](#object-collections-stay-refused-until-a-relationship-supplies-the-identity) |
| `itemsAs()` value-object item mode — explicit item class + optional denormalize/normalize pair over schemas' [`ObjectStateCast`](../schemas/object-state-cast.md) | — | done | — | [semantics](#a-cast-item-is-schema-complete-so-unmanaged-keys-stop-riding) · [diverges](#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam) · [diverges](#item-class-inference-is-deferred-the-declaration-stays-explicit) |

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
orderings are load-bearing, and schemas' root
[`HasState`](../schemas/schema.md#hasstate-still-lacks-constant-state-relationships-and-partial-rendering)
keeps both. **Hooks run before `fillStateWithNull()`**: an item's raw value
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

This is the **plain-array** repeater, and it is where the heading holds.
[Derived requirement](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)
reads the record's write-side type at the field's record path, and at
`tags.{uuid}.name` there is nothing to read: the walk stops at `tags` (a plain
`array` declares no element type — PHP has none to declare, and
[docblock generics are invisible by design](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point)),
`describe()` answers null, and null means
[keep the pre-types behaviour](../schemas/record-type-reader.md#a-null-recordtarget-means-no-type-answer-not-no-such-property).
So such item fields are never auto-required and never auto-suppressed — one that
must be filled says `required()` out loud, which validates per item through the
ordinary collector. The repeater itself also derives nothing, by the existing
`isCollection` skip. This is acceptance, not a gap to close: teaching
`describe()` to see through an item segment would need element types that do not
exist in the language.

Under `relationship()` it is the other way round. Each item schema
[binds the child it maps](#an-item-schema-is-stamped-with-the-child-it-maps), so
the item schema *is*
[the nearest record frame](../schemas/belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one)
and the field's path is read against the child class with the item segment
already stripped — no element type needed, because the association's target
declares one. A `TextInput::make('heading')` over a non-nullable child property
derives required; one over a nullable property does not. A `DateTimePicker`
inside such an item reads its object class from that same frame — and its
stored value too
([the item fill](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)).
Nothing above changes: a plain-array repeater has no class to frame, so it
derives nothing as it always did.

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
merge into — the identity question the
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
over [`AssociationReader::getRecordKey()`](../schemas/association-reader.md#the-record-key-is-tables-semantics-moved-onto-the-mapping),
so an item of a child keyed by two fields is `record-1~zh=20Hans` —
[the key's alphabet](../schemas/record-key.md#where-a-key-travels-and-what-each-context-forbids)
is what lets that survive the dotted state path, both `HasState` sanitisers and
the live `data-model` reserved set),
each item filled by reading the item schema's own declared leaf paths off that
child through the record accessor. So the keys stop being per-request uuids and
start being *identity*, and three places follow from it: `hydrateItems()`
returns before it re-keys (the map is already keyed, and re-keying would erase
the identity), `dehydrateItems()` returns before it positionalises (position is
not what the save reads), and an item the add action mints keeps its uuid key —
which is precisely how the save tells a new child from a loaded one.

An item leaf is read as the root fill reads one
([the rule](../schemas/belongs-to-record.md#reads-are-lenient-writes-are-strict)):
an object-valued leaf enters the item only where the item field's own state
casts flatten it, so a `DateTimePicker` or an enum-backed field fills from the
child, as does a `Uuid` under a cast that flattens it, and a fill hook receives
the object at its item path, while an association, and an object no cast
flattens, still skip. Skipping **every**
object leaf would lose data: such a field would fill empty, and the next save —
one that never touched it — would write NULL over the stored value. Pinned by
[`RepeaterRelationshipObjectLeafTest`](../../../packages/forms/tests/Integration/Components/RepeaterRelationshipObjectLeafTest.php).

`getData()` hands the whole keyed map to the save hook, which builds the
**desired list** in state order: a `record-` key resolves to the very instance
the collection holds (never a fresh reference by key — strict identity is what
makes an update an update, and it would hold on
[either of `getReference()`'s routes](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route)
anyway, since each answers the identity map's own instance), an un-prefixed key constructs the association's
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

An added item keeps its uuid key until something reads the collection back, and
the save does not.
`EditRecord` does: it calls
[`Schema::loadStateFromRelationships()`](../schemas/belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter)
after its flush — Filament's own post-save step — which runs this load hook again
and keys every item `record-<key>` by the identifier the flush gave it, so the
next save updates what this one created. Without it the next save constructed
the item again: an `EntityIdentityCollisionException` for an assigned key, and a
`DELETE` plus an `INSERT` of every added item, on every save, for a generated id.
A host that flushes itself adds that one call after its flush.

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

### A nested relation-backed field is routed by the item, not by the map

`Select::make('author')->relationship()` inside a `relationship()` repeater —
the join-entity-with-a-payload shape — maps nothing of its own: the repeater
[owns its record subtree](#a-repeater-owns-its-record-subtree), so the mapper
drops every path beneath it, and a hook on a dropped path
[is refused](../schemas/belongs-to-record.md#a-hook-the-record-path-map-does-not-carry-is-refused)
unless the owner
[speaks for it](../schemas/belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused).
`routesNestedRelationshipHookAt()` is the repeater speaking: an owner-relative
path naming an item and then something the item template declares a hook at is
one this repeater routes, and both edges then run it against the *child*, which
is the only record the hook could ever have meant.

The vouch answers from the walk that routes, so the two cannot disagree — and
the walk is [`Schema::getNestedRelationshipHooks()`](../schemas/belongs-to-record.md),
whose keys are already item-relative. Both halves of the path are checked, and
the first half is the one easy to skip: the leading segment has to name one of
the items the repeater currently holds, and only then is the remainder matched
against the template's hooks. What that leaves refused is deliberate and loud: a
hooked component in a **decoration** schema (`belowContent()` and its nine
siblings hang off the repeater itself, and neither edge walks them) names no
item, so the vouch says no and the map refuses it by name rather than letting it
never fire — and a *dotted* decoration path is refused for that same reason,
rather than vouched for by a remainder that happens to spell an item's hook.

The two edges discover from different places, and the asymmetry is forced.
`fill()` runs before a single item exists — the raw state is still empty, so
`getDefaultChildSchemas()` has no keys to build from — and reads the **template**
child schema, off a clone, the same discipline
[`getItemStatePaths()`](#every-item-schema-is-keyed-as-well-as-re-pathed) uses.
The save has the real thing, so it reads the **per-item** schema, whose
components are the very ones the map dropped. It asks for that schema only where
the template declares a **save** hook — the one edge that reads it — and an item
key the built schemas do not cover, one a caller added by hand to the array it
passed `getData()`, is built on the spot exactly as they are. So a hand-supplied
item routes the hooks its template declares, and behaves the same whether or not
the repeater carries one; the only shape left to throw is a repeater with no item
schema at all, which has nothing to route from.

Claimed paths leave the leaf walk — the hook's own key and everything under
`key.` — and the subtraction is **per edge**, mirroring the root edges exactly:
the fill routes the load-hooked keys and leaves save-only ones their ordinary
leaf read, the save routes the save-hooked keys and leaves load-only ones their
ordinary `setOnRecord()`. `getItemStatePaths()` itself is untouched: the
delegated paths stay in it, because the
[violation translator](../schemas/violation-path-translator.md) attributes item
violations through it.

Order inside one item is mutate → construct → leaves → nested hooks → order
stamp → collection write, so the
[identity pairing](#a-save-records-which-collection-key-each-item-was-written-to)
that follows the write sees children nothing is still going to touch. An added
item needs no exists-gate: the hook mutates the in-memory child and the caller's
single flush sorts the INSERTs. And recursion needs no code at all — a nested
relationship repeater is itself a hook-carrying component, so the discovery
yields it as one delegated entry, and its own body repeats the whole scheme one
level down against the child it was handed.

### An item schema is stamped with the child it maps

The routing above would still hand a nested field the *wrong* record without a
stamp, so each item schema carries one, in Filament's own two arms: a `record-`
item resolves the child off the owner's collection by
[record key](../schemas/association-reader.md), and a client-added item, having
no child yet, floors at the association's target class-string. Ours stamps the
class-string one level earlier as well — on the item **template**, the `default`
key every item schema is cloned from — because the fill discovers from that
template, before an item schema exists to have been stamped.

The stamp stops at that key, and the boundary is the same one the vouch draws.
A **decoration** schema (`belowContent()` and its nine siblings) belongs to the
repeater rather than to an item, neither edge walks one, and its components map
nothing of the child — so framing one on the child would answer every `$record`
inside it with `null` (a `Schema` holding a class-string returns no instance and
does not walk to its parent for one) and would make it the nearest record frame
for a derivation that means the owner. Decoration schemas keep the owner's
record, inherited, exactly as they did before `relationship()` was declared.

The floor is load-bearing rather than tidy. `Schema`'s record getters walk to the
parent when their own binding evaluates to `null`, so a stamp that could resolve
null would silently hand a nested `Select::make('author')` the **owner's** record
— and an owner mapping an association of the same name is exactly the shape a
join entity sits in, so the wrong answer would be a working one. A class-string
never falls through, so the only tree where a stamp resolves nothing is one with
no record class anywhere, where the parent chain answers nothing either.

Resolution is **soft** on both arms — the association is described, never
required — because the stamps are read from rendering, from `getItemStatePaths()`
and from every flat walk, and an eager `require*()` there would relocate the fill
and save edges' refusals into all of them. The cost is one accepted freeze:
`Schema` memoizes its evaluated record, so the first read of an item schema wins
and a structural read taken before the owner was bound can leave the class-string
where a later read would have produced the instance. Almost nothing depends on
which arm won — a hook receives its record explicitly, and target resolution and
the constraint frame need only the class. The exception is `unique()`'s ignore
arm, which reads the **instance** (a class-string binding answers `getRecord()`
with `null` and does not walk to the parent for one), so a frozen item schema
degrades it to no-ignore and the row being edited can be flagged as its own
duplicate. No traced lifecycle reaches that freeze — every read of an item schema
happens after the owner is bound — but it is the reason the freeze is recorded
rather than dismissed.

The stamp reaches past the routing, and that is the point rather than a side
effect: every closure inside a stamped **item** is injected the child as
`$record`, and everything derived from `getRecordClass()` follows it — a nested
field's own association resolution, and the entity a `unique()` or `exists()`
probes when it names none. Filament reads the same way (`getRecord()` walks to
the nearest `model()`), and it is what makes the field's target and the record it
is validated against agree; a component that means the *owner's* record inside an
item has to name it.

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
The collapse, expand, collapse-all and expand-all actions render the same way,
`liveComponentClickHandlerEnabled(false)` with the click bound by the repeater
controller
([forms-bundle.md](forms-bundle.md#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action)),
so their clicks never reach the host; the handle's drop is what mounts
`reorder`, through the controller, and the other six actions (`add`, `delete`,
`clone`, `moveUp`, `moveDown`, `addBetween`) mount as ordinary actions.

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
dehydrate renumbers anyway) is a known gap, left open.

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
clone or a delete. Each header cell carries an `sy-sr-only` label ("Reorder",
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
rather than inventing a state path — and the message is shown at the
schema's own path, because nothing renders an item's own
([the filing](../schemas/violation-path-translator.md#a-message-is-filed-only-under-a-component-that-shows-its-own-errors)).
Because the item fill asks each
item field whether its casts flatten an object leaf and so keys the fields by
their path, a path two item leaves share is listed once. Every consumer reads the list as a set — the save writes each path
onto the child, and the translator `array_flip()`s it — so nothing depends on
the multiplicity.

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
`saveRelationships()` phase with its own flush is ever adopted, which the port
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

### A new item whose identifier is incomplete is refused before the save returns

Filament has no counterpart: an Eloquent child is keyed by an auto-incrementing
`id`, so a repeater item cannot be missing a key half. A Doctrine child can, and
the failure it produces is worse than an exception. The repeater assigns the
owner onto each new child and hands the desired list to the accessor; the
caller's flush then **commits** the row, because an identifier component the item
schema never collected sits at its property default — `''` for a typed
`string` — which Doctrine writes without complaint. The row exists, no key names
it, and the next fill of that repeater throws `is not a printable value` reading
it back. One forgotten field in `components()` is enough.

So the save asks
[`AssociationReader::hasIncompleteAssignedIdentifier()`](../schemas/association-reader.md#an-incomplete-assigned-identifier-is-the-question-the-create-paths-ask)
of every child it **constructed** this pass, and refuses by name:

> `The [%s] repeater is saving an item of [%s] whose identifier is incomplete, so no record key can be minted for it. Put every identifier field in the repeater's components() — enabled and dehydrated for a new item — or build the child in mutateRelationshipDataBeforeCreateUsing().`

Four things about the rule, each of which the message alone gets wrong.

**It is by *assignment*, and only for a composite or association-carrying identifier.** A
class whose identifier Doctrine generates is never incomplete — the id is the
ORM's to fill at the insert — so the refusal cannot fire for the ordinary
`id`-keyed child, whatever shape it is. Neither can it fire for a **plain
single scalar** identifier the application assigns: the member answers `false`
for one at the entry, before it reads a field, because such a class has always
been keyable here and an application may fill that field from a listener no
mapping shows. What is left is a
composite key (Doctrine refuses an id generator on one) or an identifier carrying
an association — minus whatever a `#[PrePersist]` callback of the class or a mapped
entity listener fills at the flush.

**A class-level `prePersist` callback lets an unmapped blank half through.**
The mapping short-circuit exempts every class
declaring a `prePersist` callback or entity listener, whether or not it fills a
key half. `CreateAction`, the resource create page and `Select`'s `createOption`
default read the key again after `persist()`, once the callback has run; the
repeater has no such moment, since it never persists an item itself — the
owner's cascade inserts it at the application's flush. So for such a class a key
half no component maps reaches the table blank. Closing
it needs a check at the owner's flush, which the repeater does not run. Pinned as
behaviour by
[`RepeaterKeyWritesTest`](../../../packages/forms/tests/Integration/Components/RepeaterKeyWritesTest.php)'s
*writes a new item whose unmapped key half stays blank past a prePersist callback
that fills neither half*.

**A pending owner is not incomplete.** A derived-identity child under an owner
that has not been flushed yet — a repeater on a **create** form — carries an
identifier association whose target's generated id is still pending, and
Doctrine writes both in one flush, deferring the child's identifier to the
parent's insert. Reading that as "incomplete" would refuse a working create page
with advice that is false, which is exactly what the first spelling of this rule
did.

**It fires for a field that never reaches the item's state**, which is not the
same as a field absent from `components()`. A declared component left blank dies
earlier and better, as the field's own `NotBlank`, with zero statements and an
error beside the input. But a key half declared and then `hidden()` or
`disabled()` is refused here too, because `disabled()` also marks the field
unsaved and an unsaved field contributes nothing to the new child — which is why
the message says *enabled and dehydrated for a new item* rather than merely
*in `components()`*. `->disabled()->dehydrated()` saves, and an **existing** item
with a disabled key half saves either way, since nothing is being constructed.
What is left is a developer error rather than
something an end user can answer — so it throws, and it restores nothing: the
collection write has already run in memory, the item-key map is deliberately
**not** re-read, nothing has been flushed, and the request ends. The refusal is
therefore not a transaction guard: a caller that catches the `LogicException`
and then flushes anyway still commits the half-keyed row it was warned about.
Validating a
key that is *taken*, or a key half edited on an existing item, is the record
check's, which reaches
every item through the owner's loaded collection. A new item on a key a stored
row, an existing item or another new item claims is *"This value is already
used."* under every identifier field of the new item that is no association and
that the item's schema maps — a join entity's, whose identifier is associations
only, under the one that does not point back at the owner
([the rule](../schemas/can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation)) — and an existing item's key half edited is
*"This value cannot be changed."*, put back, with nothing written
([the rule](../schemas/can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine)). Two new items claiming one key are compared in
memory, so under an owner that has no identifier yet the second is refused
without a statement. A new item on the key of an item the **same** save removes
is refused too, and the message blames the key the user just removed: Doctrine
inserts before it deletes, so that save could not succeed either way — recorded
rather than closed. Pinned by
[`RepeaterKeyWritesTest`](../../../packages/forms/tests/Integration/Components/RepeaterKeyWritesTest.php), beside
[`RepeaterCompositeRelationshipTest`](../../../packages/forms/tests/Integration/Components/RepeaterCompositeRelationshipTest.php),
whose insert case now reads the new item's key once before its `INSERT`.

### A mutate callback can rewrite what a nested hook consumes

The [nested routing](#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map)
runs mutate → leaves → hooks per item, so
`mutateRelationshipDataBeforeSaveUsing()` sees the raw item array **including**
the delegated keys and may rewrite the identifier a nested `Select` submitted
before the hook reads it. Filament orders the two the other way around: its
schema tree walk runs a nested component's `saveRelationships()` children-first,
*before* `saveToRelationship()` reaches the item's own
`mutateRelationshipDataBeforeSave()` and `fill()`, so over there the callback
cannot reach what a nested hook already consumed.

The inversion is a consequence of the stance rather than a preference about
callbacks: we have no tree walk to run first, because the
[single-flush invariant](#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)
collapses the whole recursion into the two owner bodies, and once the hook runs
inside the item loop the only question left is which side of the mutate callback
it lands on. Rewritable is the more useful of the two contracts — a callback that
can normalise or substitute a nested identifier is worth more than one that
cannot — so the hook reads what the callback returned. A callback declining the
item (returning `null`) skips its nested hooks along with everything else, since
the item as a whole is declined.


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
the Doctrine relation layer did not bring one, and the
[record stamp](#an-item-schema-is-stamped-with-the-child-it-maps) that would be
its natural companion resolves the child directly rather than through a constant
— and schemas' `HasInlineLabel` is unported, so an item
schema has no inherited inline-label state to switch off. Each stamp lands with
its owner.

### Object collections stay refused until a relationship supplies the identity

The mapper's object-collection refusal
([both triggers](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused))
**does not relax** for a plain Repeater, and its message promises nothing it
cannot keep. A refusal that names future work as its owner reads as a bug report
once that work exists, so both messages state the real boundary instead —
array-backed lists are the Repeater's, and object collections need identity —
and name the answer: `relationship()`
supplies the item identity, so declare it on the component bound to the
collection; hand-mapping after `getData()` is what is left for a collection no
component declares one for. A repeater also refuses *early*:
`hydrateItems()` asks the type reader at the repeater's own record path and
throws when the declared type is an object collection — otherwise `fill()`
silently skips the object-valued read, the repeater renders empty, and the
developer meets the mapper's refusal only on first save. That path is read
against the **nearest** record frame rather than against the root container, so
the early refusal reaches one shape it used to miss whole: a plain-array repeater
sitting inside a
[stamped item schema](#an-item-schema-is-stamped-with-the-child-it-maps), whose
`record-<id>` segment used to defeat the type walk and leave the check inert.
Such a repeater over a collection-typed property of the *child* now refuses at
hydration, in the same words, with the path relative to the record it names. A
DTO list typed only by docblock (`/** @var list<Money> */ private array $items`)
remains invisible to both checks and takes the array path — the repeater will
overwrite it with value-shaped arrays, the
[recorded price of reflection-only types](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point).
`RecordTarget`'s provenance fields stay
[skipped](../schemas/record-type-reader.md#the-value-object-carries-no-provenance):
the repeater's early refusal was their named candidate, but it builds the
target it consumes in the same method, so provenance still has nothing to tell a caller.

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
property's docblock — is **deferred by decision**. The constraints are recorded so a later slice designs against
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
`Closure` from it, which the [closure-typing convention](../../../.claude/rules/php.md)
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
