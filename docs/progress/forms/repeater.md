# Repeater (forms)

`Sylament\Forms\Components\Repeater` — the array-backed repeatable field: one
child schema cloned per item, items added, removed and reordered through the
mounted-action pipeline, the whole list written into a plain `?array` record
property (the JSON-column shape [`setOnRecord()`](../schemas/property-accessor.md)
already round-trips). It extends [`Field`](field.md) and is the first component
that is **both a leaf and a container**: it carries a state path of its own *and*
path-bearing children, which is the case the record walks were
[explicitly flagged as unready for](../schemas/belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths).
This file records the design decisions before code, per the epic brief; the rows
flip as the code lands.

**Scope boundary, stated up front:** this epic maps **value-shaped array items
only**. Object collections — a `Traversable`-typed property, a Doctrine
`Collection` behind an adder — [stay refused by the mapper](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused),
because mapping collection *items* needs identity — which state row is which
entity, and what happens to the rows that disappeared — and identity is the
Doctrine relationship-state epic's surface, not this one's. The measured failure
that draws the line is recorded in
[the Serializer rejection](../schemas/belongs-to-record.md#a-serializer-based-mapper-stays-rejected-on-amended-grounds):
identity-less collection population produced duplicate INSERTs and orphan
deletions on a real two-tag `Post`. Filament's `relationship()` and `table()`
modes are out with it.

**Status:** todo — see the [status vocabulary](../README.md#statuses).

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

## Surface

Every point of Filament's class and its concerns, grouped where a family shares
one fate. _Blocked by_ names what has to land first. _Detail_ carries a
**detail** link (the write-up in [Details](#details), deleted once the row is
**done**), a **semantics** link (a permanent note in [Semantics](#semantics)),
and/or a **diverges** link (the permanent note in [Divergences](#divergences)),
or `—`. A row with no _Ports_ link is Sylament-only.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `Repeater::make()` + `components()` item schema definition (the `schema()` alias is [not ported](../renames.md)) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#item-schemas-ride-a-ported-child-schema-cache-and-clone-layer) · [semantics](#an-item-field-derives-no-requirement-from-the-record) |
| `hydrateItems()` / `dehydrateItems()` + `CanGenerateUuids` (`generateUuidUsing()` / `generateUuid()` / `fake()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) · [`CanGenerateUuids.php`](../../../vendor/filament/forms/src/Components/Concerns/CanGenerateUuids.php) | todo | — | [semantics](#items-are-uuid-keyed-in-the-live-bag-and-positional-on-the-record) · [semantics](#unmanaged-item-keys-round-trip-through-the-client) · [diverges](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid) |
| `getItems()` / `getDefaultChildSchemas()` / `areCachedDefaultChildSchemasFresh()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#item-schemas-ride-a-ported-child-schema-cache-and-clone-layer) |
| `defaultItems()` / `default()` + the hydrated-default merge | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-item-pipeline-lands-filament-exact) |
| `simple()` / `isSimple()` / `getSimpleField()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-item-pipeline-lands-filament-exact) |
| Add action (`getAddAction()` / `addAction()` / `addActionLabel()` / `addActionAlignment()` / `addable()` / `isAddable()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-structural-actions-reuse-the-mounted-action-pipeline-end-to-end) |
| Delete action (`getDeleteAction()` / `deleteAction()` / `deletable()` / `isDeletable()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-structural-actions-reuse-the-mounted-action-pipeline-end-to-end) |
| Clone action (`getCloneAction()` / `cloneAction()`) + `CanBeCloned` (`cloneable()` / `isCloneable()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) · [`CanBeCloned.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeCloned.php) | todo | — | [detail](#the-structural-actions-reuse-the-mounted-action-pipeline-end-to-end) |
| Move actions + button reorder (`getMoveUpAction()` / `getMoveDownAction()` / `moveUpAction()` / `moveDownAction()` / `reorderable()` / `reorderableWithButtons()` / `isReorderable()` / `isReorderableWithButtons()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-structural-actions-reuse-the-mounted-action-pipeline-end-to-end) |
| Drag-and-drop reorder (`getReorderAction()` / `reorderAction()` / `reorderableWithDragAndDrop()` / `isReorderableWithDragAndDrop()`) + `HasReorderAnimationDuration` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | blocked | drag-and-drop JS vertical | [diverges](#drag-and-drop-reorder-is-deferred-and-filaments-defaults-are-kept) |
| `CanLimitItemsLength` (`minItems()` / `maxItems()` / `getItemsCount()`) | [`CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | todo | — | [detail](#items-validate-through-the-existing-path-keyed-collector) |
| Item chrome (`itemLabel()` / `getItemLabel()` / `hasItemLabels()` / `itemNumbers()` / `itemHeaders()` / `truncateItemLabel()` / `getHeadingsCount()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-template-is-two-php-rendered-partials) |
| `getItemState()` / `getRawItemState()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | — |
| Template + stylesheet (`toEmbeddedHtml()` / `toSimpleEmbeddedHtml()` → `partials/Repeater/`, `repeater.css`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#the-template-is-two-php-rendered-partials) |
| Collapse chrome (`getCollapseAction()` / `getExpandAction()` / `getCollapseAllAction()` / `getExpandAllAction()` + their modifiers, `CanBeCollapsed` / `CanBeCompact` composition, `canConcealComponents()`, `shouldPersistCollapsed()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#four-chrome-clusters-are-deferred-not-dropped) |
| `addBetween` pair (`getAddBetweenAction()` / `addBetweenAction()` / `addBetweenActionLabel()`) + `labelBetweenItems()` / `getLabelBetweenItems()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#four-chrome-clusters-are-deferred-not-dropped) |
| `extraItemActions()` (`HasExtraItemActions`) | [`HasExtraItemActions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php) | todo | — | [detail](#four-chrome-clusters-are-deferred-not-dropped) |
| Items grid (`HasContainerGridLayout`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#four-chrome-clusters-are-deferred-not-dropped) |
| `table()` mode (`table()` / `getTableColumns()` / `isTable()` / `toTableEmbeddedHtml()`; the `TableColumn` class keeps [its own README row](README.md#components)) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | todo | — | [detail](#four-chrome-clusters-are-deferred-not-dropped) |
| Relationship family (`relationship()`, `orderColumn()` / `getOrderColumn()`, `saveToRelationship()`, `fillFromRelationship()`, `getStateFromRelatedRecords()`, `getCachedExistingRecords()` / `clearCachedExistingRecords()`, `getRelationship()` / `getRelationshipName()` / `getRelatedModel()` / `hasRelationship()`, `mutateRelationshipDataBeforeCreate/Fill/Save()` + `*Using()`, `afterCreate()` / `afterUpdate()` / `afterDelete()`, the `getLabel()` override, item `constantState()` / record stamping) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | blocked | Doctrine relationship-state epic | [diverges](#object-collections-stay-refused--item-identity-belongs-to-the-relationship-epic) |
| `partiallyRenderAfterActionsCalled()` / `shouldPartiallyRenderAfterActionsCalled()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | blocked | UX LiveComponent partial-render equivalent | — |
| Deprecated members (`createItemButtonLabel()`, `disableItemCreation()` / `disableItemDeletion()` / `disableItemMovement()`, `orderable()`, `inset()`) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#deprecated-filament-members-are-not-ported) |
| `nullWhenBlank()` composition — an empty repeater as `null` instead of `[]` | — | todo | — | [semantics](#the-blank-test-judges-a-repeater-by-its-own-value) · [diverges](#a-repeater-owns-its-record-subtree) |
| Early object-collection refusal at hydration | — | todo | — | [diverges](#object-collections-stay-refused--item-identity-belongs-to-the-relationship-epic) |

## Details

Specifics for rows that are not finished — the design decisions this epic starts
from. Delete a subsection when its rows flip to **done**; the permanent halves
live under [Semantics](#semantics) and [Divergences](#divergences), and the
notes that constrain *other* components move to their own files with the code
(the record-walk changes to
[belongs-to-record.md](../schemas/belongs-to-record.md), the refusal-message
rewording to [property-accessor.md](../schemas/property-accessor.md), the
`distinct()` re-point to [can-be-validated.md](can-be-validated.md)).

### Item schemas ride a ported child-schema cache and clone layer

Filament resolves `getChildSchema($itemKey)` for an unknown key by falling back
into `getCachedDefaultChildSchemas()` — a cached map the Repeater overrides to
hold **one child `Schema` per item**, each a `getClone()` of the `components()`
template schema with `->statePath($itemKey)`, invalidated by a structural
fingerprint (`array_map(is_array(...), $rawState)`: the keys, their order, and
whether each holds an array — value edits never rebuild, add/remove/reorder
always do). Sylament's `HasChildComponents` has none of that today — no caching,
no keyed fallback, no clone layer — so the epic lands the Filament-shaped slice
in **schemas** first: `getDefaultChildSchemas()` (default
`['default' => $this->getChildSchema()]`), `getCachedDefaultChildSchemas()` +
`areCachedDefaultChildSchemasFresh()` + `clearCachedChildSchemas()`, the
unknown-key fallback in `getChildSchema()`, the cached-default-map spread in
`getChildSchemas()` — gated on `array_key_exists('default', $childComponents)`,
preserving the documented no-phantom-schemas invariant — plus cache
invalidation in `childComponents()`, and the clone layer: both `Cloneable`
traits (`getClone()` flushing the absolute-path and key caches),
`HasChildComponents::cloneChildComponents()` and
`HasComponents::cloneComponents()`. One deliberate correction while porting:
Filament's `cloneComponents()` stamps `container($this)` onto the *original*
component before cloning it, mutating the shared instance the source schema
still holds; ours stamps the clone only, pinned by a test that the original's
container survives. `getFlatComponents()` already descends through
`getChildSchemas()`, so the moment item schemas appear there, validation,
dehydration, hydration and the record walks all reach item fields with no
further wiring. Item schemas are defined through `components()` alone — the
`schema()` alias every Filament repeater example uses is
[deliberately not ported](../schemas/component.md#the-schema-alias-is-not-ported),
so `simple()`'s internal wiring goes through `components()` too.

Item schemas are built without Filament's `->constantState(null)` and
`->inlineLabel(false)` stamps: constant state is
[blocked on the relationship epic](../schemas/schema.md) and schemas'
`HasInlineLabel` is unported (deferred in M1 · 3), so both arrive with their
owners.

### The item pipeline lands Filament-exact

`setUp()` registers `defaultItems(1)`, an `afterStateHydrated` hook running
`hydrateItems()` — re-keying whatever positional list `fill()` read off the
record into fresh uuid keys — and a `mutateDehydratedStateUsing` running
`dehydrateItems()`, which is `array_values()` back to a positional list (the
`simple()` arm wraps a lone scalar per item as `[$fieldName => $value]` on the
way in and plucks it back out on the way out). The root walk applies a
component's dehydrated-state mutation *after* descending its children, so a
nested repeater positionalises inner-first and the shapes compose. `default()`
keeps Filament's hydrated-default merge dance (`$hydratedDefaultState` + the
merge flag) so a default declared on the repeater survives the per-field
hydration pass; `defaultItems(N)` builds N empty uuid-keyed items.
`simple()` needs the wrapper to suppress the inner field's label — verify
`Field` has a `hiddenLabel()` arm before building on it; if it is missing it
lands as a minimal flag in the same change.

### The structural actions reuse the mounted-action pipeline end to end

Nothing new is invented host-side: the repeater registers its actions through
the component-tier `HasActions` (`registerActions()` in `setUp()`, so
`prepareAction()` stamps `schemaComponent($this)`), the trigger templates emit
the existing `stimulus_action('live', 'action', …)` parameters with
`context: {schemaComponent: key}`, and per-item triggers bind their item with
the already-ported `HasMountableArguments` — `$deleteAction(['item' => $key])`
— surfaced to Twig through `getExtraTemplateData()`, the documented home for
exactly this per-item preamble. Action bodies are Filament's: add appends an
empty uuid-keyed item and runs `getChildSchema($newUuid)->fill()`; delete
unsets; clone copies the item payload under a fresh key; moveUp/moveDown swap
whole items (ports of `array_move_before`/`array_move_after`). Each structural
action ends in an explicit `callAfterStateUpdatedHooks()` on the repeater —
the host's `#[PostHydrate]` diff pass runs *before* the action executes, so a
mutation the action makes is invisible to it until the next round-trip, and
Filament's explicit call is load-bearing here, not Livewire residue. Morph
stability comes from a per-item DOM id derived from the repeater's key plus the
item key; inputs inside items already survive morphing per the `value ?? ''`
contract on `TextInput`.

### Items validate through the existing path-keyed collector

No validation machinery changes. Item fields are real `Field` clones with
absolute state paths (`data.tags.{uuid}.name`), so the schema-tier collection
walk picks their constraints up by recursion, the host validates raw live state
per path — uuid-keyed, since validation runs before dehydration re-keys — and
the wholesale bag-section replace clears a removed item's stale errors on the
next run, with sticky re-validation composing unchanged. The repeater's own
constraints ride its `Field` surface: `minItems()` / `maxItems()` emit
`Type('array')` + `Count(min:)` / `Count(max:)` at the repeater's path (the
[constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary);
the method names keep Filament's spelling), `maxItems()` also gates
`isAddable()`, and an explicit `required()` fails on an empty item map exactly
as Filament's `required` does. `distinct()` stays out: it compares one item's
value against its siblings', which is the
[cross-path validation context](can-be-validated.md#cross-field-rules-need-a-validation-context-the-per-path-runner-does-not-build)
the per-path runner cannot express — its _Blocked by_ re-points from `Repeater`
to that context in this epic (`Assert\Unique(fields:)` over the repeater's own
path is the likely constraint when it lands, at the cost of item-level error
placement).

### The template is two PHP-rendered partials

`toEmbeddedHtml()` / `toSimpleEmbeddedHtml()` translate to
`partials/Repeater/index.html.twig` and `partials/Repeater/Simple.html.twig`
(the `<Name>/<Variant>` layout), rendered through `ViewComponent::$template`
like `TextInput`; the stylesheet ports `fi-fo-repeater` →
`sy-fo-repeater` classes into
`packages/forms/assets/src/styles/components/repeater.css`, imported by forms'
`index.css` and compiled by support's Tailwind build (one lane owns the
`assets/dist` rebuild). Item headers render the reorder/clone/delete triggers
and the `itemLabel()` heading; the repeater has no single labellable input, so
the wrapper renders its heading as Filament does — a non-`<label>` tag wired by
`aria-labelledby` — which may need a small `FieldWrapper` extension in the same
lane. Shipped chrome: `itemLabel()` (+ numbers, headers, truncation),
`addActionLabel()` / `addActionAlignment()`, and the empty state.

### Four chrome clusters are deferred, not dropped

Deferred to keep this epic JS-free and the template one honest size, each still
**todo** because nothing prerequisite is missing — they are work, not waiting:
the **collapse family** (per-item collapse/expand, collapse-all/expand-all,
`CanBeCollapsed` / `CanBeCompact`, the `CanConcealComponents` contract tied to
collapsibility, `persistCollapsed`) is client-side behaviour wanting a
repeater-item Stimulus controller; **`addBetween` + `labelBetweenItems`** are
between-item template branches Filament ships hidden by default;
**`extraItemActions()`** wants the per-item action descent exercised beyond the
built-ins (an action *inside* an item schema — modal resolution through a
uuid-keyed child schema — is untested surface until then); the **items grid**
waits on the container-grid concerns deferred in M1 · 4. `table()` mode is out
with them — it is a whole second rendering surface (`TableColumn`, header
plumbing) with no consumer in sight.

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
[scope boundary](#object-collections-stay-refused--item-identity-belongs-to-the-relationship-epic)
defers. Filament behaves identically.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional
ones say what would reverse them.

### Object collections stay refused — item identity belongs to the relationship epic

The mapper's object-collection refusal
([both triggers](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused))
**does not relax** for the Repeater; this epic narrows what the message
promises. Today `refuseCollection()` names "the Repeater epic" as the future
owner; once the Repeater exists that would read as a bug report, so the message
is reworded to state the real boundary: array-backed lists are the Repeater's,
object collections wait for the Doctrine relationship-state epic, and the
interim escape is mapping the collection by hand after `getData()`. A repeater
also refuses *early*: `hydrateItems()` asks the type reader at the repeater's
own record path and throws when the declared type is an object collection —
otherwise `fill()` silently skips the object-valued read, the repeater renders
empty, and the developer meets the mapper's refusal only on first save. A
DTO list typed only by docblock (`/** @var list<Money> */ private array $items`)
remains invisible to both checks and takes the array path — the repeater will
overwrite it with value-shaped arrays, the
[recorded price of reflection-only types](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point).
`RecordTarget`'s provenance fields stay
[skipped](../schemas/record-type-reader.md#the-value-object-carries-no-provenance):
this epic was their named candidate, but its refusal builds the target it
consumes in the same method, so provenance still has nothing to tell a caller.
Reverses only if the relationship epic lands identity-keyed item state.

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

### Drag-and-drop reorder is deferred and Filament's defaults are kept

Filament reorders by drag (SortableJS via its `x-sortable` Alpine plugin, on by
default) and by buttons (off by default). The drag surface is a JS vertical —
a sortable dependency, a Stimulus controller, morph interplay — deliberately
out of this epic, which ships **no JavaScript** (the `Toggle` precedent). The
defaults do not shift to compensate: `reorderableWithButtons()` stays opt-in
false, so nothing renders differently from Filament by silent default — a
migrating user simply finds drag reorder missing (a blocked row, loud in this
table) and buttons one call away. Flipping the buttons default on was
considered and rejected: a surprise control Filament users never asked for is
worse than a documented gap. Reverses when the drag-and-drop JS vertical lands.

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

### Deprecated Filament members are not ported

`createItemButtonLabel()`, `disableItemCreation()` / `disableItemDeletion()` /
`disableItemMovement()`, `orderable()` and `inset()` are all tagged
`@deprecated` upstream (or, for `inset()`, an explicit no-op "no longer part of
the design system"). They exist to keep Filament's own historical call sites
compiling; a fresh port has none, and shipping them would hand every new
Sylament user a deprecated spelling beside the real one. A migrating user's
call breaks loudly — missing method, current name one grep away in Filament's
own deprecation notice.

_See also: the global [rename table](../renames.md) — `ownsRecordSubtree()`
joins the Sylament-only names recorded there once it lands, and the unported
`schema()` alias has its row there; everything else in this file keeps
Filament's spelling._
