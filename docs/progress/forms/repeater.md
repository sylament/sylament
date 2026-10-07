---
description: The repeatable field over a plain array, its item pipeline, structural actions, table mode and value-object items; relationship() has its own file.
ports:
  - vendor/filament/forms/src/Components/Repeater.php
  - vendor/filament/forms/src/Components/Repeater/TableColumn.php
  - vendor/filament/forms/src/Components/Concerns/CanBeCloned.php
  - vendor/filament/forms/src/Components/Concerns/CanGenerateUuids.php
  - vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php
  - vendor/filament/forms/src/helpers.php
  - vendor/filament/schemas/src/Components/Concerns/HasContainerGridLayout.php
  - vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php
owns:
  - packages/forms/src/Components/Repeater.php
  - packages/forms/templates/partials/Repeater/
---
# Repeater (forms)

`Sylament\Forms\Components\Repeater` extends [`Field`](field.md): one child schema cloned per item, items added, removed and reordered through the mounted-action pipeline, and the whole list written into a plain `?array` record property (the JSON-column shape [`setOnRecord()`](../schemas/property-accessor.md) round-trips).
It is both a leaf and a container: it carries a state path of its own *and* path-bearing children, which the record walks [answer with an owner predicate](#a-repeater-owns-its-record-subtree).

The *plain* repeater maps **value-shaped array items only**.
Object collections, a `Traversable`-typed property or a Doctrine `Collection` behind an adder, [stay refused by the mapper](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused), because mapping collection *items* needs identity: which state row is which entity, and what happens to the rows that disappeared.
The measured failure that draws the line is recorded in [the Serializer rejection](../schemas/belongs-to-record.md#a-serializer-based-mapper-stays-rejected-on-amended-grounds): identity-less collection population produced duplicate INSERTs and orphan deletions on a real two-tag `Post`.
That identity is what [`relationship()`](repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection) supplies, so a repeater that declares one maps a Doctrine collection and the refusal steps aside for it, [and only for it](#object-collections-stay-refused-until-a-relationship-supplies-the-identity).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `components()`](../../../packages/forms/src/Components/Repeater.php) (the `schema()` alias is [not ported](../renames.md)) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#an-item-field-derives-no-requirement-from-the-record) · [diverges](#every-item-schema-is-keyed-as-well-as-re-pathed) |
| `hydrateItems()` / `dehydrateItems()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#items-are-uuid-keyed-in-the-live-bag-and-positional-on-the-record) · [semantics](#the-item-pipeline-runs-children-first-and-before-the-null-fill-pass) · [semantics](#unmanaged-item-keys-round-trip-through-the-client) |
| `generateUuidUsing()` / `generateUuid()` / `fake()` | [`CanGenerateUuids.php`](../../../vendor/filament/forms/src/Components/Concerns/CanGenerateUuids.php) | done | — | [diverges](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid) |
| `getItems()` / `getDefaultChildSchemas()` / `areCachedDefaultChildSchemasFresh()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [diverges](#every-item-schema-is-keyed-as-well-as-re-pathed) · [diverges](#an-item-schema-carries-no-constant-state-or-inline-label-stamp) |
| `defaultItems()` / `default()` and the hydrated-default merge | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [diverges](#a-defaults-fill-over-a-populated-repeater-resurrects-its-pre-fill-items) |
| `simple()` / `isSimple()` / `getSimpleField()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#the-item-pipeline-runs-children-first-and-before-the-null-fill-pass) |
| `getAddAction()` / `addAction()` / `getAddActionName()` / `addActionLabel()` / `getAddActionLabel()` / `addActionAlignment()` / `getAddActionAlignment()` / `addable()` / `isAddable()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) |
| `getDeleteAction()` / `deleteAction()` / `getDeleteActionName()` / `deletable()` / `isDeletable()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) |
| `getCloneAction()` / `cloneAction()` / `getCloneActionName()` / `cloneable()` / `isCloneable()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) · [`CanBeCloned.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeCloned.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) |
| `getMoveUpAction()` / `getMoveDownAction()` / `moveUpAction()` / `moveDownAction()` / `getMoveUpActionName()` / `getMoveDownActionName()` / `reorderable()` / `reorderableWithButtons()` / `isReorderable()` / `isReorderableWithButtons()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-structural-action-fires-the-repeaters-afterstateupdated-hooks-itself) · [diverges](#the-move-helpers-correct-two-vendor-edge-defects) |
| `getReorderAction()` / `reorderAction()` / `getReorderActionName()` / `reorderableWithDragAndDrop()` / `isReorderableWithDragAndDrop()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#the-drag-reorder-round-trip-accepts-the-clients-order) · [diverges](#the-reorder-action-is-hardened-and-answers-only-real-moves) |
| `reorderAnimationDuration()` / `getReorderAnimationDuration()` | [`HasReorderAnimationDuration.php`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | done | — | — |
| `itemLabel()` / `getItemLabel()` / `hasItemLabels()` / `itemNumbers()` / `hasItemNumbers()` / `itemHeaders()` / `hasItemHeaders()` / `truncateItemLabel()` / `isItemLabelTruncated()` / `getHeadingsCount()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | — |
| `getItemState()` / `getRawItemState()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | — |
| `getItemKeys()` / `getItemStatePaths()` (Sylament's [`HasKeyedItems`](../schemas/violation-path-translator.md)) | — | done | — | [semantics](#a-save-records-which-collection-key-each-item-was-written-to) |
| [`getTemplate()`](../../../packages/forms/src/Components/Repeater.php) and the `partials/Repeater/` templates, for `toEmbeddedHtml()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#collapse-state-is-the-clients-once-the-controller-connects) · [diverges](field.md#labeltag-is-a-boolean-labelasheading) |
| `getCollapseAction()` / `getExpandAction()` / `getCollapseAllAction()` / `getExpandAllAction()`, their `*Action()` modifiers and `get*ActionName()` names, and `CanBeCollapsed` / `CanBeCompact` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#collapse-state-is-the-clients-once-the-controller-connects) · [diverges](#the-repeater-item-owns-its-collapse-state-unlike-the-section) |
| `getAddBetweenAction()` / `addBetweenAction()` / `getAddBetweenActionName()` / `addBetweenActionLabel()` / `getAddBetweenActionLabel()` / `labelBetweenItems()` / `getLabelBetweenItems()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#addbetween-hides-by-default-and-crowds-out-the-label-between-items) |
| `extraItemActions()` / `getExtraItemActions()` / `cacheExtraItemActions()` | [`HasExtraItemActions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraItemActions.php) | done | — | [semantics](#an-extra-item-action-reads-its-items-state) |
| `grid()` / `getGridColumns()` | [`HasContainerGridLayout.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasContainerGridLayout.php) | done | — | [semantics](#the-items-grid-is-supports-grid) |
| `table()` / `getTableColumns()` / `isTable()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#table-mode-pairs-columns-by-position) · [diverges](#hidden-fields-render-in-a-trailing-hidden-cell) |
| `partiallyRenderAfterActionsCalled()` / `shouldPartiallyRenderAfterActionsCalled()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | blocked | a UX LiveComponent partial-render equivalent | [detail](../schemas/schema.md#hasstate-still-lacks-constant-state-relationships-and-partial-rendering) |
| `array_move_before()` / `array_move_after()` | [`helpers.php`](../../../vendor/filament/forms/src/helpers.php) | done | — | [diverges](#the-move-helpers-correct-two-vendor-edge-defects) |
| `getPublishedViewOverrideCheckPath()` | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](toggle-buttons.md#there-is-nothing-to-publish-a-view-override-against) |
| `createItemButtonLabel()` / `disableItemCreation()` / `disableItemDeletion()` / `disableItemMovement()` / `orderable()` / `inset()` (deprecated) | [`Repeater.php`](../../../vendor/filament/forms/src/Components/Repeater.php) | skipped | — | [diverges](#deprecated-filament-members-are-not-ported) |
| `nullWhenBlank()` on a repeater | — | done | — | [semantics](#the-blank-test-judges-a-repeater-by-its-own-value) · [diverges](#a-repeater-owns-its-record-subtree) |
| Object-collection refusal at hydration | — | done | — | [diverges](#object-collections-stay-refused-until-a-relationship-supplies-the-identity) |
| `itemsAs()` value-object items over schemas' [`ObjectStateCast`](../schemas/object-state-cast.md) | — | done | — | [semantics](#a-cast-item-is-schema-complete-so-unmanaged-keys-stop-riding) · [diverges](#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam) · [diverges](#item-class-inference-is-deferred-the-declaration-stays-explicit) |

The table lists every public member of the ported classes and traits except two families with files of their own: `relationship()` and its family are in [repeater-relationship.md](repeater-relationship.md), and `minItems()` / `maxItems()` / `getItemsCount()` in [can-limit-items-length.md](can-limit-items-length.md).

## Surface — TableColumn

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`TableColumn::make()`](../../../packages/forms/src/Components/Repeater/TableColumn.php) / `getLabel()` / `hiddenHeaderLabel()` / `isHeaderLabelHidden()` / `markAsRequired()` / `isMarkedAsRequired()` | [`Repeater/TableColumn.php`](../../../vendor/filament/forms/src/Components/Repeater/TableColumn.php) | done | — | [semantics](#table-mode-pairs-columns-by-position) |

## Semantics

### Items are uuid-keyed in the live bag and positional on the record

One list, two shapes, and the boundary between them is dehydration.
In the live bag items are keyed by per-request v4 uuids (`symfony/uid`'s `Uuid::v4()`, the `Str::uuid()` idiom translation, a dependency `forms` declares), minted at hydration (`hydrateItems()` re-keys whatever `fill()` read) and by every add/clone — so a structural mutation never renumbers a sibling's state path mid-edit, which is what keeps validation errors, morph anchors and in-flight input attached to the right item.
Everything that runs before dehydration sees uuid keys: absolute state paths, the constraint collector, the error bag, `getRawState()`.
`dehydrateItems()` then `array_values()`es the map, so everything after — `getState()`, `getData()`, the record property — sees a positional list, and the uuids never persist.
Two consequences: the same field shows a *different* absolute path on every mount (never key an assertion or a morph anchor on a literal uuid), and under `Repeater::fake()` (uuid generation disabled, the test idiom) items are positionally keyed end to end, which the record walks survive through their `strval()` normalisations.

### The item pipeline runs children-first, and before the null-fill pass

`hydrateItems()` is an `afterStateHydrated` hook, so *when* the schema fires those hooks decides whether it sees the record's shape or a wreck of it.
Two orderings are load-bearing, and schemas' root [`HasState`](../schemas/schema.md#hasstate-still-lacks-constant-state-relationships-and-partial-rendering) keeps both.
**Hooks run before `fillStateWithNull()`**: an item's raw value is a scalar in simple mode (`['x', 'y']`), and the null-fill pass writes null at every unset leaf path — `tags.0.value` — which clobbers the scalar the wrap step was about to read, so a record fill would land two empty items.
**Hooks run children-first**, each descent re-reading `getChildSchemas()`: a nested repeater's hook has to re-key its own items *before* the outer one re-keys the map they sit in, or the inner component writes at a path its parent has just renamed and vivifies phantom items beside the real ones.
Filament reaches both orderings for free — it hydrates recursively and fires each component's hooks at the end of its own descent — where the port walks flat lists, so it states the order explicitly.
Dehydration runs inner-first (a component's `mutateDehydratedState` applies after its children's), which is why a nested repeater positionalises inner-first and the shapes compose.

### A structural action fires the repeater's afterStateUpdated hooks itself

Every add/delete/clone/move body ends in `$component->callAfterStateUpdatedHooks()`, which reads like Livewire residue and is not.
The host's `#[PostHydrate]` diff pass — the thing that normally notices a changed state path and fires the hooks for it — runs *before* the mounted action executes, so a mutation the action makes is invisible to it until the next round-trip.
Without the explicit call an `afterStateUpdated()` on the repeater would fire one request late, against the previous item map.
It fires exactly once per action, including at a reorder edge where the item map comes back unchanged.

### The blank test judges a repeater by its own value

A `nullWhenBlank()` repeater — and any repeater sitting inside another nullable branch — is judged by `isBlankStateValue()` over its own slice (`blank([])`: an empty list is blank, one item is not, even an all-empty item), **never** by the leaf-enumeration arm the other branches use.
That is not a shortcut but a correctness requirement: at mapping time the slice has already been positionalised while the declared leaves still carry uuid paths, so enumerating leaves would find every one absent, judge the branch vacuously blank, and null a repeater that has items — silent data loss.
Judging by value answers the same question in both bags (raw uuid-keyed at validation, positional at mapping), so the [two-bag hazard](../schemas/has-nullable-branch.md#a-nullwhenblank-closure-sees-two-different-bags) does not widen here.
The default stays Filament's: an untouched empty repeater dehydrates `[]` and writes `[]`; `nullWhenBlank()` is the opt-in that turns that into SQL `NULL` on a nullable `?array` column, and a non-nullable target meets the [existing loud refusal](../schemas/has-nullable-branch.md#a-branch-the-record-cannot-null-is-refused-loudly).

### An item field derives no requirement from the record

This is the **plain-array** repeater, and it is where the heading holds.
[Derived requirement](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types) reads the record's write-side type at the field's record path, and at `tags.{uuid}.name` there is nothing to read: the walk stops at `tags` (a plain `array` declares no element type — PHP has none to declare, and [docblock generics are invisible by design](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point)), `describe()` answers null, and null means [keep the pre-types behaviour](../schemas/record-type-reader.md#a-null-recordtarget-means-no-type-answer-not-no-such-property).
So such item fields are never auto-required and never auto-suppressed — one that must be filled says `required()` out loud, which validates per item through the ordinary collector.
The repeater itself also derives nothing, by the `isCollection` skip.
This is acceptance, not a gap to close: teaching `describe()` to see through an item segment would need element types that do not exist in the language.

Under `relationship()` it is the other way round.
Each item schema [binds the child it maps](repeater-relationship.md#an-item-schema-is-stamped-with-the-child-it-maps), so the item schema *is* [the nearest record frame](../schemas/belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) and the field's path is read against the child class with the item segment already stripped — no element type needed, because the association's target declares one.
A `TextInput::make('heading')` over a non-nullable child property derives required; one over a nullable property does not.
A `DateTimePicker` inside such an item reads its object class from that same frame — and its stored value too ([the item fill](repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)).
A plain-array repeater has no class to frame, so it derives nothing.

### Unmanaged item keys round-trip through the client

`fill($record)` reads the repeater's property **whole** — the walk's leaf-granularity read scope stops at a component that owns its subtree — so a key inside an item that no item field declares (`['name' => 'x', 'legacy' => true]`) enters the live bag, travels to the browser, and is written back on save, surviving add/remove/reorder because structural actions move whole item payloads.
Two things follow.
Unlike a `Section` over an array branch, a repeater **exposes undeclared item keys to the client** (and a client can tamper with them) — do not keep server-only values inside repeater items.
And they survive only as payload: scoping the read to declared leaves, the way the section walk does, would need to know *which* saved item a state item is to merge into — the identity question the [scope boundary](#object-collections-stay-refused-until-a-relationship-supplies-the-identity) defers.
Filament behaves identically.

### A cast item is schema-complete, so unmanaged keys stop riding

Under a declared `itemsAs()` cast the semantics [directly above](#unmanaged-item-keys-round-trip-through-the-client) invert, and deliberately: `get()` consumes only the item class's name set (constructor parameters ∪ accessor-reachable properties) and ignores every other key fail-closed, `set()` emits only the readable half of the same set, so undeclared DTO state does not survive the round trip and a tampered extra key dies at construction instead of reaching the record.
That is the value-object contract — schema-complete by definition — not a gap; the name-set mechanics and the three guards (non-instantiable class at declaration; wrong-class or scalar item at fill; a non-optional constructor parameter meeting null, whose message points at `required()` on the feeding field) are pinned in [object-state-cast.md](../schemas/object-state-cast.md#the-name-set-is-the-fidelity-contract).

The set the item bag carries is the *class's*, not the schema's: a DTO member no item field declares still rides in the live item (`note` beside a lone `title` input) and is written straight back into the fresh instance, so the round trip is lossless for everything the class names and lossy only for keys it does not.

The same per-item reading is why `simple()` and `itemsAs()` refuse each other, loudly and in either declaration order.
The simple arm's dehydrate pluck (`$itemData[$name] ?? null` behind `is_array`) would silently null every constructed object, and the composed meaning — a one-scalar item that is also an object — belongs to a *leaf* cast on the simple field (`->simple(TextInput::make('value')->stateCast(…))`), which the child walk runs correctly.
The `LogicException` names that path.

Conversion closures beside a **prebuilt** `ObjectStateCast` refuse the same way, and in both spellings: the declaration-time guard catches the literal instance, and a second guard in `getItemCast()` catches the instance a closure declaration resolved to.
Without the second one, `itemsAs(fn () => $cast, denormalizeUsing: …)` would silently drop the closures the literal spelling throws over — a fail-fast the evaluation seam would otherwise hide.

### The drag reorder round-trip accepts the client's order

Drag reorder is on by default, exactly as in Filament, and it is the one structural action whose argument is a *whole ordering* rather than one item key.
The list element carries support's drag control ([sortable.md](../support/sortable.md)); the control answers a drop with an ordered list of the `data-sortable-item` keys, forms' repeater controller turns that into a `reorder` mount, and the action writes the items back in that order.
Nothing is sent per item, so a drag is one round-trip regardless of how far the item moved.

`isReorderableWithDragAndDrop()` is `reorderableWithDragAndDrop() && isReorderable()`, mirroring the buttons arm, so `reorderable(false)` — or a disabled field — silences both surfaces at once, and the handle, the control and its markers all drop out of the DOM together rather than rendering inert.
The handle is the reorder action's own trigger, stamped `data-sortable-handle` through `extraAttributes(merge: true)`, which is why it renders with `liveComponentClickHandlerEnabled(false)`: it is grabbed, never clicked.
The collapse, expand, collapse-all and expand-all actions render the same way, `liveComponentClickHandlerEnabled(false)` with the click bound by the repeater controller ([forms-bundle.md](forms-bundle.md#the-repeater-controller-owns-the-items-collapse-state-and-bridges-the-drag-control-to-the-reorder-action)), so their clicks never reach the host; the handle's drop is what mounts `reorder`, through the controller, and the other six actions (`add`, `delete`, `clone`, `moveUp`, `moveDown`, `addBetween`) mount as ordinary actions.

### Collapse state is the client's once the controller connects

The server renders the collapse state it knows — `sy-collapsed` on an item the component reports collapsed, and the CSS hides that item's content.
From the moment the repeater's Stimulus controller connects, the item's own `data-collapsed` attribute is the truth instead, which is why every collapse rule in the stylesheet is a **pair**: `.sy-collapsed:not([data-collapsed])` for the server's answer and `[data-collapsed="true"]` for the client's, with the matching `:not(.sy-collapsed):not([data-collapsed])` / `[data-collapsed="false"]` pair for the expanded arm.
A morph that re-renders an item therefore cannot snap it shut behind the user's back: the attribute the controller stamped outranks the class the server sent.

`persistCollapsed()` puts `sy-cloak` on the root, so the whole repeater stays invisible until the controller has replayed the stored state — the same cloak idiom `Tabs` uses.
The collapse and expand triggers are two stacked icon buttons, both always rendered, the CSS fading and rotating whichever one does not apply; that is Filament's markup and it is what makes the transition possible without a re-render.

Three structural actions re-open the repeater for the rest of the request: `add`, `clone` and `addBetween` each call `collapsed(false, shouldMakeComponentCollapsible: false)` before firing the `afterStateUpdated` hooks, so a repeater declared `collapsed()` does not swallow the row the user just asked for.
The named argument matters: without it the call would *make* a non-collapsible repeater collapsible as a side effect of adding an item.

### `addBetween` hides by default and crowds out the label between items

`getAddBetweenAction()` ships `visible(false)`, Filament-exact — the between-item add button is opt-in through `addBetweenAction(fn (Action $action) => $action->visible())`.
Two consequences worth stating, because both look like bugs from the outside.
First, `mountAction()` unmounts a hidden action (`isDisabled()` reads `isHidden()`), so a test or a demo that mounts `addBetween` without switching it on gets silence rather than an error.
Second, the two between-item surfaces are an `if`/`elseif`, not a pair: where a visible `addBetween` renders, `labelBetweenItems()` does **not**, even when both are declared.
The label is the fallback for the gap, not a companion to the button.

A third consequence, narrower and vendor's own, lives in the action body: the positional arm only appends.
`getAddBetweenAction()` walks the raw state rebuilding it key by key and, when `generateUuid()` answers `null`, inserts with `$items[] = []` — which under integer keys takes the next free index, the one the following iteration overwrites with its own item.
So a mid-list insert into positionally keyed state loses the new item and `fill()`s the neighbour that took its key; an insert after the last item is unaffected.
The collision is shared with Filament's own `getAddBetweenAction()`, line for line, and it sits off every default path — uuid keying is on unless the null-uuid mode is chosen, whether by [`Repeater::fake()`](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid) or by a consumer's own `generateUuidUsing(false)` — so it is recorded here as a vendor-parity edge rather than fixed.
Hardening it (rebuilding the list positionally, which every dehydrate renumbers anyway) is a known gap, left open.

Neither between-item element is a sortable item — no `data-sortable-item` — so the drag control skips them and an ordering never contains a divider's key.

### An extra item action reads its item's state

`extraItemActions()` appends actions the header (or the simple/table row) renders per item, each bound to that item with `['item' => $itemKey]`.
The binding is what makes the action's `$schemaState` resolve to the *item's* state rather than the repeater's: `Action::getSchemaState()` sees the host implements schemas' `HasExtraItemActions` contract and answers `getItemState($itemKey)`.
So `Action::make('inspect')->action(fn (array $schemaState) => …)` receives one item's array, which is the whole point of the family.

The concern is Filament's line for line: it keys its cache by `getName()`, because schemas' `cacheActions()` splices the map into its own with a spread and a positional list would collide with the structural actions already there.
The splice does not run `prepareAction()` over the merged map, so the concern runs it on each action itself, which stamps `schemaComponent($this)`, beside the icon-button defaults (`defaultColor('gray')`, `defaultSize(Size::Small)`, `defaultTemplate(Action::ICON_BUTTON_TEMPLATE)`) that a caller's own `color()` / `size()` / `button()` still overrides.

### The items grid is support's `grid()`

`grid()` comes from schemas' `HasContainerGridLayout` and the item list carries whatever support's `grid()` Twig function emits for it — `sy-grid`, the per-breakpoint `sy-grid-cols` classes, and the `--cols-*` custom properties.
The repeater's own stylesheet therefore declares no `display: grid` on either item list; it only adds the spacing and alignment the grid does not carry.
An ungridded repeater still gets `--cols-default: repeat(1, …)`, which is why the default rendering is a single column without a special case.

The simple view takes the same call, so `grid()` composes with `simple()`; the table view lays its items out as rows and does not call `grid()`.

### Table mode pairs columns by position

`table([TableColumn::make('Name'), …])` switches the whole rendering surface: `getTemplate()` answers `TABLE_TEMPLATE` whenever columns are declared, so `table()` outranks `simple()` when both are set, and an explicit `template()` still outranks both.
`isTable()` is `filled($this->getTableColumns())`, so `table([])` is not a table — an empty column list reverts to the default view rather than rendering a headerless table.

A `TableColumn` is a label plus header options (`hiddenHeaderLabel()`, `markAsRequired()`, `wrapHeader()`, `width()`, `alignment()`, `verticalAlignment()`); it holds **no** field.
The pairing with the item's components is positional, by Filament's counter: the first column takes the first component that consumes one, the second the second, and so on.
A [`Hidden`](hidden.md) field consumes no column, an invisible component consumes one and renders an empty `sy-hidden` cell in its place, and once the columns are exhausted every remaining component — `Hidden` included — is dropped.
That drop is **silent and order-sensitive**, because the budget guard runs *before* the `Hidden` branch, faithful to vendor's guard order: a `Hidden` survives only while some column is still unclaimed, so it has to sit ahead of the component that fills the last one.
The demo's table card is the live example, declaring `Hidden::make('id')` first for exactly this reason.
The column's `verticalAlignment()` reaches the cell, not the header; its `alignment()`, `width()` and `wrapHeader()` reach the header, not the cell.

The reorder and actions columns are conditional in their own right: the reorder header cell renders only with more than one item *and* some reorder surface on, and the actions header cell only when the row can carry an extra item action, a clone or a delete.
Each header cell carries an `sy-sr-only` label ("Reorder", "Actions") so the table is still readable to a screen reader.
An item schema that contains anything other than a schema component — an `Action`, say — is refused with a `LogicException` naming the class, because there is no cell such a thing could sit in.

### A save records which collection key each item was written to

`getItemKeys()` answers the [violation-path translator](../schemas/violation-path-translator.md#the-index-is-a-collection-key-not-a-position), which resolves `sections[2]` against the key the **record's** collection holds that element under.
State order is not that key: the association write goes through the accessor's adder/remover diffing, which leaves an unchanged membership in its old order and a thinned one keyed sparsely, so the desired list's order is not what the collection ends up keyed by.

So the save hook reads the collection back after the write and pairs each element with the item key it was written from — **by identity**, since an item the client added has no record key of its own until a flush — and `getItemKeys()` returns that pairing.
Before any save (a fill, or an array-mode repeater whose items the record holds as a positional list) there is nothing to pair and the state's own keys are returned in order, which is exactly how a plain array is written.

`getItemStatePaths()` is the same list the fill and save arms walk, published on the contract: a violation naming an in-item path it does not carry is a path no field maps, and the translator [folds it at the item](../schemas/violation-path-translator.md#a-violation-inside-an-item-folds-by-the-same-rule-as-one-at-the-root) rather than inventing a state path — and the message is shown at the schema's own path, because nothing renders an item's own ([the filing](../schemas/violation-path-translator.md#a-message-is-filed-only-under-a-component-that-shows-its-own-errors)).
Because the item fill asks each item field whether its casts flatten an object leaf and so keys the fields by their path, a path two item leaves share is listed once.
Every consumer reads the list as a set — the save writes each path onto the child, and the translator `array_flip()`s it — so nothing depends on the multiplicity.

## Divergences

### Every item schema is keyed as well as re-pathed

Each item schema is the `components()` template re-pathed onto the item's key with `statePath($itemKey)`, keyed with `->key($itemKey)` and then cloned; the key is a stamp Filament does not make, and not a redundant echo of the state path.
Filament's `Schema::getKey()` falls back to `getStatePath(isAbsolute: false)`, so an unkeyed item schema keys itself by the item's state path for free.
The port's root-tier [`HasKey`](../schemas/schema.md#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback) drops that fallback, so an unkeyed item schema returns `null` and its `getInheritanceKey()` walks up to the repeater's own key: every item's fields would then resolve the same absolute key — and the same DOM id, since `getId()` is `getCustomId() ?? getKey()` — collapsing onto `<repeaterKey>.<fieldName>` across all items, with the `<li>` ids and each field wrapper's `for` / `aria-labelledby` targets duplicated with them.
It is pinned by the render test asserting each item's input id (`<repeaterKey>.0.name` against `<repeaterKey>.1.name`), which collapses to one value the moment the stamp goes.
The stamp comes back out if the schema-tier fallback is restored.

### An item schema carries no constant-state or inline-label stamp

Filament builds each item schema with `->constantState()`, the item's own array for an item that is not an existing relationship record and `null` otherwise, and with `->inlineLabel(false)`; ours builds neither, and both are waiting rather than rejected.
Constant state is [unported for want of a consumer](../schemas/component.md#relationship-derived-and-constant-state-are-dropped-from-the-component-trait): the Doctrine relation layer has none, and the [record stamp](repeater-relationship.md#an-item-schema-is-stamped-with-the-child-it-maps) that would be its natural companion resolves the child directly rather than through a constant.
Schemas' `HasInlineLabel` is unported, so an item schema has no inherited inline-label state to switch off.
Each stamp lands with its owner.

### Object collections stay refused until a relationship supplies the identity

The mapper's object-collection refusal ([both triggers](../schemas/property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused)) **does not relax** for a plain Repeater, and its message promises nothing it cannot keep.
A refusal that names future work as its owner reads as a bug report once that work exists, so both messages state the real boundary instead — array-backed lists are the Repeater's, and object collections need identity — and name the answer: `relationship()` supplies the item identity, so declare it on the component bound to the collection; hand-mapping after `getData()` is what is left for a collection no component declares one for.
A repeater also refuses *early*: `hydrateItems()` asks the type reader at the repeater's own record path and throws when the declared type is an object collection — otherwise `fill()` silently skips the object-valued read, the repeater renders empty, and the developer meets the mapper's refusal only on first save.
That path is read against the **nearest** record frame rather than against the root container, so the early refusal also reaches a plain-array repeater sitting inside a [stamped item schema](repeater-relationship.md#an-item-schema-is-stamped-with-the-child-it-maps), whose `record-<id>` segment a root-relative type walk could not cross.
Such a repeater over a collection-typed property of the *child* refuses at hydration, in the same words, with the path relative to the record it names.
A DTO list typed only by docblock (`/** @var list<Money> */ private array $items`) remains invisible to both checks and takes the array path — the repeater will overwrite it with value-shaped arrays, the [recorded price of reflection-only types](../schemas/record-type-reader.md#docblock-generics-are-invisible-and-that-is-the-point).
`RecordTarget`'s provenance fields stay [skipped](../schemas/record-type-reader.md#the-value-object-carries-no-provenance): the repeater's early refusal builds the target it consumes in the same method, so provenance has nothing to tell a caller.

Both refusals step aside for a repeater that declares [`relationship()`](repeater-relationship.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection), which supplies the item identity: the early check returns before it reads the type, and the mapper's own refusal is never reached because the save hook, not `setOnRecord()`, owns that path.
The exception is *scoped to the declaration*: everything above holds for a repeater without `relationship()`.
The docblock-only DTO list is unaffected in both modes.

### A repeater owns its record subtree

Sylament-only, because the record path map is Sylament-only.
A component-tier predicate, `ownsRecordSubtree()` (default false, `Repeater` true), marks a component whose own dehydrated value **is** the record value for everything beneath its path.
It changes the walks in three places, written up in [belongs-to-record.md](../schemas/belongs-to-record.md): `getRecordPathMap()` treats an owner as a leaf even though it has path-bearing descendants (the [owner predicate](../schemas/belongs-to-record.md#mapping-walks-the-record-graph-by-declared-leaf-paths)) and drops every collected path beneath an owner; the branch pass's presence guard asks for the owner's own path instead of leaf paths beneath it, and after nulling an owner-branch skips the exact path as well as the prefix; the built-in blank test short-circuits to the [value arm](#the-blank-test-judges-a-repeater-by-its-own-value).
Dehydration needs no arm of its own, because its pruning forgets by own path, which is subtree-safe, and forms' requirement walk inherits these arms through `shouldNullBranch()`.
One refusal rides along: a path-bearing component declared *outside* the owner's tree whose path nests under the owner's (`TextInput::make('tags.extra')` beside `Repeater::make('tags')`) would silently neither fill nor save — its reads and writes belong to the owner's wholesale value — so the map build throws, naming both components and the two ways out (move the field inside the repeater's schema, or move its path out from under the repeater).

### The item cast fires at the repeater's two pipeline boundaries, not in the generic seam

Filament has no value-object item mode to diverge from — its answer to object-shaped repeater state is `relationship()`, identity-keyed Eloquent mapping, [the surface this excludes](#object-collections-stay-refused-until-a-relationship-supplies-the-identity) — so `itemsAs()` diverges by existing at all.
Inside the port it diverges a second time, from the generic state-cast seam: the cast object reuses the `StateCast` contract but is **not** registered into `$stateCasts`, because that seam's four firing points are each wrong for a container and two of them corrupt nested repeaters.
Get-ward at `getStateToDehydrate()` would construct the outer DTOs while a plain inner repeater's uuid map still sits inside them — the inner's positionalisation runs later, in the mutate pass, whose `setInArray()` would then vivify phantom uuid paths beside the already-built DTO list.
Set-ward anywhere in the hook phase is the mirror image: `afterStateHydrated` runs children-first, so an inner repeater would read through outer items that are still DTOs, meet a non-array mid-path, and the write-back would clobber the DTO wholesale under `setInArray()`'s documented contract.

So the repeater applies the cast itself, at the two item-pipeline boundaries where the directionality is already correct: **set-ward in its `hydrateState()` position** (the flat pass walks parents before descendants and runs before every hook, so record-read DTOs are deconstructed to arrays — keys preserved — before any inner component or the `hydrateItems()` re-key looks at them), and **get-ward at the tail of `dehydrateItems()`** (the mutate pass runs children-first, so an outer cast constructs from items whose inner repeaters are already positionalised — and already objects, when the inner declares a cast of its own, so nesting composes in both directions).
`array_values()` therefore always runs over arrays, and construction always follows it.
All four nesting quadrants are pinned by tests.

**Everything mid-edit keeps the uncast shapes.**
Because `$stateCasts` stays empty, `getState()`, `getOldState()`, the `$state` closure injection, `getItemState()` / `getRawItemState()`, every structural action body, the structural freshness fingerprint, and validation (raw, per path, unchanged) all see exactly what they see on an uncast repeater — uuid-keyed value arrays.
The `minItems()`/`maxItems()` `Count` constraint counts the raw map.
Programmatic `$set()` takes array items, as on an uncast repeater; handing it DTOs is undefined, not converted.
The DTO shape exists at exactly two places: the record read that `fill()` makes, and the dehydrated bag from the mutate pass on — which is what `getData()` maps and what a host's `getState()` returns.

`nullWhenBlank()` composes unchanged for the built-in test.
The [owner value arm](#the-blank-test-judges-a-repeater-by-its-own-value) judges `blank()` over the branch's own slice, and that answer is shape-stable: a uuid-keyed array map at validation and a positional DTO list at mapping are both non-empty arrays or both empty ones, so an empty cast repeater still nulls and a filled one still survives, over `?array`-typed and untyped properties alike (a `Collection`-typed property never gets this far — the early refusal fires first).
A **closure** condition, though, sees arrays at validation and DTOs at mapping, which widens the recorded [two-bags hazard](../schemas/has-nullable-branch.md#a-nullwhenblank-closure-sees-two-different-bags).

### Item-class inference is deferred; the declaration stays explicit

`itemsAs(Line::class)` names the item class out loud, and that is the only arm that ships.
*Implicit* derivation — a `list<Line>` docblock alone flipping the repeater into object mode — is **rejected**, permanently: it would silently change every existing docblock-typed array form the day it shipped, and a library cannot assume its consumers run PHPStan over their docblocks.
*Opt-in inference* — a bare `itemsAs()` deriving the item class from the record property's docblock — is **deferred by decision**.
The constraints are recorded so a later slice designs against them instead of re-deriving them: it reads through a separate, scoped docblock reader (`PhpStanExtractor`-backed, used for item-class resolution only), never through [`RecordTypeReader`](../schemas/record-type-reader.md), whose pinned-to-reflection mutator-first contract and collection flags must not change; an underivable type (no docblock, `array<string, mixed>`, a union, a non-instantiable class) throws loudly rather than falling back to array mode; and it costs a new production dependency (`phpstan/phpdoc-parser` behind property-info's `PhpStanExtractor`) — not taken; the flat per-item construction needs no docblock extractor at all, which is part of why the mode has no dependency of its own.
Sugar over the explicit arm, additive, breaking nothing.
Optional polish that survives the deferral: a dev-time mismatch guard when a readable docblock contradicts the explicit class.

### The reorder action is hardened and answers only real moves

Filament's reorder body is `[...array_flip($arguments['items']), ...$state]` — the client's key list flipped into a map whose *values* are the positions it came from, then spread over by the current state.
Two things fall out of that which do not survive here.
The flip is unvalidated: whatever the client sent becomes an array key, and a key naming no item enters the state carrying an integer position as its value, so an item schema is then built over an `int`.
And `array_flip()` is only a positional trick for producing keys in the sent order — the values it produces are always discarded by the spread.

Ours states that directly: the argument must be a **list** (`array_is_list()`, so a JSON object rather than an array is refused with a `LogicException` naming the argument), each sent key is kept only when `array_key_exists()` finds it in the current state, and the result is `$reordered + $items` — the array union, which keeps the reordered keys in the sent order and appends whatever the client did not mention, in the state's own order.
An unknown key is dropped rather than invented; an item the client omitted survives rather than vanishing.
Both arms are pinned by [`RepeaterActionsTest`](../../../packages/forms/tests/Integration/Components/RepeaterActionsTest.php).
The key coercion is PHP's own: a numeric string from the client finds the integer key a positionally keyed map carries, which is what keeps [`fake()`](#uuid-generation-is-a-mode-flag-and-generateuuidusingtrue-mints-a-real-uuid) repeaters reorderable.

### Uuid generation is a mode flag, and `generateUuidUsing(true)` mints a real uuid

`generateUuidUsing()` keeps Filament's `Closure|bool|null` signature, but the value behind it is stored as two properties — a `bool $generatesUuids` mode flag and a `string|Closure|null $generateUuidUsing` generator — and `true` mints an ordinary v4 uuid instead of Filament's `"1"`.
Both halves fall out of typing.
Filament's truthy arm evaluates whatever the property holds and returns it from a `?string` method, so `generateUuidUsing(true)` returns the literal `true` coerced to the string `"1"` — every item keyed identically, and a coercion `declare(strict_types=1)` forbids outright.
Here `true` means what it reads as: generation is on, with no generator override, so the default arm runs.
The two-property split is the same fact seen from the type side: `evaluate()`'s generic reads a closure's return type off the property union by subtracting `Closure` from it, which the [closure-typing convention](../../../.claude/rules/php.md) makes the *only* place a closure's return type may be stated.
Filament's single property spends those arms on modes, so the generic infers `Closure`, and every check on the result is dead code at level max.
Splitting the mode out lets the union say what it is for — a uuid, or a closure producing one.
A generator returning something else therefore raises PHP's own return `TypeError` naming the component's `generateUuid()`; no hand-thrown guard rides along, because a `?string` inference makes one unreachable.

### The move helpers correct two vendor edge defects

`Sylament\Forms\array_move_before()` / `array_move_after()` port Filament's forms helpers with two edges fixed rather than copied, both proven by probe against the vendor bodies.
An **unknown key** returns the array unchanged — Filament's after-arm runs `array_search()`'s `false` through `+ 1`, indexing a neighbour that may not exist and corrupting the array with a `key => null` entry under an "Undefined array key" warning.
And the **neighbour guard** is `=== null` rather than `blank()`, so an empty-string key is a movable neighbour here where Filament aborts the swap — `''` is a legal array key, and refusing to move past it was the helper leaking value semantics into key mechanics.
Key matching is strict over `strval()`-normalised keys, the same normalisation the record walks use, so a client-sent `'2'` matches a fake-mode integer key without the loose comparison Rector rejects.

### A defaults fill over a populated repeater resurrects its pre-fill items

`Schema::fill()` with **no argument** over a repeater that already holds items writes the declared default and then rebuilds every pre-fill item beside it as an all-null ghost.
Measured over `Repeater::make('tags')->defaultItems(1)->components([TextInput::make('name')])`, with the uuid generator pinned to a fixed key so the shape is readable — the bag holds two items, the fill declares one, and three come out:

```
before  ['tags' => [['name' => 'a'], ['name' => 'b']]]
after   ['tags' => ['fresh' => ['name' => null], 0 => ['name' => null], 1 => ['name' => null]]]
```

The mechanism is the flat walk.
Schemas' `Schema::hydrateState()` materialises `getFlatComponents()` **once**, up front, so the list it walks holds the item schemas built from the *pre-fill* map — fields at `tags.0.name` and `tags.1.name`.
The repeater itself is visited first (parent-first) and writes its default, a freshly keyed map, recording it at `tags` in the walk's shared `$hydratedDefaultState`.
The stale item fields run next, and that record does not cover them: the default's keys are new, so `tags.0.name` is not a path any default mentioned.
Each field is defaultless, so each [null-writes](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default), and the accessor vivifies the missing intermediates on the way — which is what rebuilds `tags.0` and `tags.1` as items.
Filament never reaches this: its `hydrateState()` recurses, re-reading `getChildSchemas()` at each descent, so the item schemas it visits are the ones the default just created.

A stale item schema produces state only because a defaultless component [null-writes](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default) in a defaults fill.
Nothing that ships reaches it, caller by caller: `HasFilters::mountTableFilters()` and `CanBeMounted`'s default `mountUsing` fill a freshly built schema over an empty bag; `Repeater::getAddAction()` fills the one item schema it has just created empty; `HasSchemas::fill(null)` is the host-facing entry and every host calls it from `mount()`, again over an empty bag; and `HasFilters::resetTableFiltersForm()`, the only one that fills a **populated** bag, fills a filters form, and no filter that ships puts a repeater in one.
It becomes reachable the first time a host re-fills a populated form: a "reset to defaults" button over a form holding a repeater is the exact shape.

The current output is pinned by [`it characterizes the ghost items a defaults fill leaves over a populated repeater`](../../../packages/forms/tests/Unit/Components/RepeaterTest.php), a characterization test rather than a wanted one: the assertion changes with the fix, and the diff states its cost.
The fix is on the walk, not on the repeater: either recurse the way Filament does (re-reading child schemas per descent), or flush a container's cached child schemas the moment its own default replaces the state they were derived from.

### The repeater item owns its collapse state, unlike the Section

[`Section`](../schemas/section.md) collapses through a `<details>` element — the browser owns the open/closed state and the server only seeds it.
A repeater item cannot: it needs a header that is a click target *and* holds its own buttons, a collapse-all/expand-all pair reaching every sibling, and a per-item persisted state, none of which `<details>` gives without fighting it.
So the repeater item is a plain `<li>` whose state is a class before its controller connects and a `data-collapsed` attribute after, and the content is hidden by CSS rather than by the element's own semantics.

The cost is the one `<details>` would have paid for us: the collapsed content stays in the DOM.
That is deliberate rather than incidental: the fields inside a collapsed item must keep their live bindings, so the item can be submitted and validated without ever being opened, and it is why `canConcealComponents()` returns `isCollapsible()`.
A [concealing component](../schemas/can-be-concealed.md) drops the native constraints (`required`, `maxlength`) off the controls inside it, so a browser cannot refuse to submit a form over a field the user cannot see; the server-side validation still runs.
Filament's `Repeater` implements no concealing contract, and its `isConcealed()` answers false.
`Section` reaches the same contract by the same reasoning.

### Hidden fields render in a trailing hidden cell

Filament's table view emits a `Hidden` field's markup **between** two `<td>` elements — `<?= $schemaComponent->toHtml() ?>` with no cell around it — which is invalid inside a `<tr>` and is re-parented by every browser's HTML parser, so the hidden input lands outside the table and outside the row it belongs to.
Ours collects the row's `Hidden` components instead and renders them in one trailing `<td class="sy-fo-table-repeater-hidden-cell">`, which the stylesheet hides at every breakpoint.
The markup is valid, the input stays inside its row, and the cell is invisible either way.

The counter is untouched by this: a `Hidden` field still consumes no column, and it is still dropped once the columns are exhausted, matching Filament's order (the column-budget guard wraps the Hidden branch rather than sitting inside it).
So a `Hidden` declared *after* the last column-consuming component does not render at all — a Filament quirk kept on purpose, since diverging would make the column budget mean two different things depending on the component's type.

### Deprecated Filament members are not ported

Each of these members is tagged `@deprecated` upstream, and each notice but one names its replacement: `createItemButtonLabel()` → `addActionLabel()`, `disableItemCreation()` → `addable()`, `disableItemDeletion()` → `deletable()`, `disableItemMovement()` → `reorderable()` and `orderable()` → `orderColumn()`, while `inset()` is a no-op "no longer part of the design system" with no replacement.
A fresh port has no call sites to keep compiling, and shipping them would hand every new Sylament user a deprecated spelling beside the real one.
A migrating user's call breaks loudly as a missing method, with the current name one grep away in Filament's own deprecation notice.
