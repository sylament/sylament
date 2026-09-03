# KeyValue (forms)

`Sylament\Forms\Components\KeyValue` — the map-backed field: a two-column table
of key/value rows over a plain `?array` record property, rows added, removed and
reordered through the mounted-action pipeline. It extends
[`Field`](field.md), defaults its state to `[]`, and carries
[`KeyValueStateCast`](../../../packages/schemas/src/State/Casts/KeyValueStateCast.php)
so the **record** sees a map while the **wire** carries a list of rows — which is
what lets every cell bind by index.

Filament renders no rows at all server-side: its block is `wire:ignore`, an
Alpine object owns the row list, and the three `Action`s are inert chrome inside
its click handlers. Ours is the roadmap's shape instead — rows rendered from the
raw state, per-cell `data-model` bindings, and `add`/`delete`/`reorder` as real
mounted actions
([divergence](#the-rows-are-server-owned-and-the-three-actions-are-real)) — which
puts every flag, label and gate under PHP test and leaves ~50 lines of
JavaScript.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).
The field ships end to end; the one unfinished row is the inline-label
stylesheet rule, which waits on schemas' `HasInlineLabel` like every other
field's.

Reference — the Filament source this is diffed against, walked end to end for
this table, so it carries no [unaudited row](../README.md#an-unaudited-table-says-so):

- [`forms/src/Components/KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php)
  — the class, its three registered actions and its embedded view
- [`schemas/src/Components/StateCasts/KeyValueStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/KeyValueStateCast.php)
  — the rows ⇄ map cast
- [`forms/resources/js/components/key-value.js`](../../../vendor/filament/forms/resources/js/components/key-value.js)
  — the Alpine object that owns the rows there
- [`forms/resources/css/components/key-value.css`](../../../vendor/filament/forms/resources/css/components/key-value.css)
  — the stylesheet
- [`support/src/Concerns/HasReorderAnimationDuration.php`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php),
  [`support/src/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php)
  — the support concerns it composes

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details), a permanent note in
[Semantics](#semantics) or a deviation in [Divergences](#divergences); a **done**
row with no permanent note reads `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The table shell](../../../packages/forms/templates/partials/KeyValue/index.html.twig) — the column matrix, the two cell inputs, the chrome cells | [`KeyValue::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | [diverges](#the-rows-are-server-owned-and-the-three-actions-are-real) · [semantics](#every-cell-binds-by-row-index-and-a-disabled-cell-keeps-its-binding) |
| `default([])` + [`getDefaultStateCasts()`](../../../packages/forms/src/Components/KeyValue.php) | [`KeyValue::getDefaultStateCasts()`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | — |
| [`KeyValueStateCast`](../../../packages/schemas/src/State/Casts/KeyValueStateCast.php) — rows ⇄ map, `''` keys dropped, last duplicate wins | [`StateCasts/KeyValueStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/KeyValueStateCast.php) | done | — | [semantics](#the-cast-sniffs-the-first-element-so-a-map-of-maps-is-not-a-row-list) · [diverges](#the-cast-fails-fast-on-a-state-it-cannot-read) |
| [`getAddAction()`](../../../packages/forms/src/Components/KeyValue.php) / `addAction()` / `getAddActionName()` / `addActionLabel()` / `getAddActionLabel()` / `addable()` / `isAddable()` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | [semantics](#a-structural-action-fires-the-fields-afterstateupdated-hooks-itself) |
| [`getDeleteAction()`](../../../packages/forms/src/Components/KeyValue.php) / `deleteAction()` / `getDeleteActionName()` / `deleteActionLabel()` / `getDeleteActionLabel()` / `deletable()` / `isDeletable()` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | [diverges](#the-delete-and-reorder-labels-are-honoured-where-filament-ignores-its-own-setters) · [semantics](#a-trigger-argument-is-re-validated-against-the-rows-the-server-rendered) |
| [`getReorderAction()`](../../../packages/forms/src/Components/KeyValue.php) / `reorderAction()` / `getReorderActionName()` / `reorderActionLabel()` / `getReorderActionLabel()` / `reorderable()` / `isReorderable()` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | [diverges](#the-delete-and-reorder-labels-are-honoured-where-filament-ignores-its-own-setters) · [semantics](#the-reorder-order-is-hardened-and-the-indexes-it-names-are-sound) |
| [`editableKeys()`](../../../packages/forms/src/Components/KeyValue.php) / `canEditKeys()` / `editableValues()` / `canEditValues()` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | [semantics](#every-cell-binds-by-row-index-and-a-disabled-cell-keeps-its-binding) |
| [`keyLabel()`](../../../packages/forms/src/Components/KeyValue.php) / `getKeyLabel()` / `valueLabel()` / `getValueLabel()` / `keyPlaceholder()` / `getKeyPlaceholder()` / `valuePlaceholder()` / `getValuePlaceholder()` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | done | — | [diverges](#the-seven-literals-are-untranslated) |
| [`HasReorderAnimationDuration`](../../../packages/support/src/Components/Concerns/HasReorderAnimationDuration.php) — the drag control's duration | [`support/…/HasReorderAnimationDuration.php`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | done | — | [detail](../support/sortable.md) |
| [`hydrateRows()`](../../../packages/forms/src/Components/KeyValue.php) — the blank-row pad (a Sylament addition) | — | done | — | [semantics](#the-raw-bag-is-never-empty-and-never-sparse) |
| [`mutateStateForValidation()`](../../../packages/forms/src/Components/KeyValue.php) / `mutatesStateForValidation()` — validation reads the map (a Sylament addition) | — | done | — | [semantics](#validation-reads-the-raw-bag-so-the-field-hands-it-the-map) |
| [`key-value.css`](../../../packages/forms/assets/src/styles/components/key-value.css) — the table skin, the two chrome columns and the add row | [`components/key-value.css`](../../../vendor/filament/forms/resources/css/components/key-value.css) | done | — | [detail](styles.md) · [detail](#the-inline-label-rule-waits-on-hasinlinelabel) |
| [`sylament--forms--key-value`](../../../packages/forms/assets/src/controllers/key_value_controller.ts) — the reorder bridge and the focus guard | [`resources/js/components/key-value.js`](../../../vendor/filament/forms/resources/js/components/key-value.js) | done | — | [diverges](#the-controller-is-a-reorder-bridge-and-a-focus-guard-not-a-row-store) |
| `.fi-fo-key-value-wrp` inline-label rule | [`components/key-value.css`](../../../vendor/filament/forms/resources/css/components/key-value.css) | blocked | schemas' `HasInlineLabel` | [detail](#the-inline-label-rule-waits-on-hasinlinelabel) |
| `HasExtraAlpineAttributes` | [`support/…/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| `$publishedViewOverrideCheckPath` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | skipped | — | there is no published-view mechanism to probe for |
| `addButtonLabel()` / `deleteButtonLabel()` / `reorderButtonLabel()` / `disableAddingRows()` / `disableDeletingRows()` / `disableEditingKeys()` / `disableEditingValues()` | [`KeyValue.php`](../../../vendor/filament/forms/src/Components/KeyValue.php) | skipped | — | [diverges](#deprecated-filament-members-are-not-ported) |

Coverage:
[`KeyValueTest`](../../../packages/forms/tests/Unit/Components/KeyValueTest.php)
(Unit) covers the fluent API against a wired field — the shipped defaults, the
seven literals and each setter overriding one, every flag closure evaluated, the
label closure receiving the field, `hydrateRows()` over `[]` / `null` / a
garbage-bearing list, `getState()` answering the map, `state()` writing a map
back as rows, the mutate pair, the three action names and their built shapes, a
modifier reshaping and a modifier declining, the per-row delete clone, the
template-data flags, and the absence of the seven deprecated members.
[`KeyValueTest`](../../../packages/forms/tests/Integration/Components/KeyValueTest.php)
(Integration) renders through the real Twig stack: the table shell over the
padded blank row, one row per raw row with its labels/placeholders/values, the
reorder column and sortable chain present only when reorderable, `addable(false)`
and `deletable(false)` each dropping their chrome, `disabled()` dropping both
chrome columns and the add trigger and disabling both cells, `editableKeys(false)`
/ `editableValues(false)` disabling one cell while it keeps its binding, the
per-cell `data-model` for a plain / `live()` / `live(debounce: 300)` / `lazy()`
field, the controller present in all three modes, the `aria-labelledby` pairing
against a visible and a hidden label, each delete trigger's `row` argument,
`getState()` dropping the blank row, `required()` failing on a blank-only bag and
passing on one keyed row, a `Count` constraint counting real rows rather than the
padding, and the `Post::$meta` round trip in both directions.
[`LiveKeyValueTest`](../../../packages/forms/tests/Integration/LiveKeyValueTest.php)
drives the three actions through a real round-trip on a shipped-shape
`#[AsLiveComponent]` host: the resting blank row, `add`, `delete` (including the
re-pad, the numeric-string index, the four refused arguments and the dirty cell
write that rides the same request), `reorder` (the permutation, the dropped and
appended indexes, and the three refused `items` shapes), a client-written row
list surviving untouched, and the state hooks seeing the map.

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**.

### The inline-label rule waits on HasInlineLabel

Vendor's stylesheet ends with a second block, `.fi-fo-key-value-wrp
.fi-fo-field-has-inline-label .fi-fo-field-label-col { @apply sm:pt-1.5 }`, which
nudges the label column down so it lines up with the first row of the table when
the field's label sits beside its control. Nothing here emits
`sy-fo-field-has-inline-label`, because schemas' `HasInlineLabel` is unported —
the same block is [outstanding on `field.css`, `select.css` and
`toggle-buttons.css`](styles.md). **Next step:** the rule lands with that concern,
in one change across every field that carries one.

## Semantics

Faithful behaviour that the next reader would plausibly re-derive wrongly.

### The raw bag is never empty and never sparse

Every cell binds by row index, so a rest point with no rows would leave the first
keystroke writing `data.meta.0.key` — a path the server never rendered, which UX
Live refuses as `Invalid model name`. `afterStateHydrated` therefore runs
`hydrateRows()`, which pads an empty list with one `['key' => '', 'value' => '']`
row (the Filament client pushes the same blank row into its own state), and every
structural action ends with `array_values()` so the list never re-encodes as a
JSON object.

The invariant is kept at exactly two kinds of place, and knowing which matters:
`fill()` — the only path that runs the state casts and the hydration hooks — and
the three actions. **Neither the casts nor `afterStateHydrated` run on a live
request**, so a code path that writes the bag directly (a client `set()`, a
future action) pads it itself or the invariant is gone. `hydrateRows()` also
normalises: whatever the bag held becomes a list of `key`/`value` pairs, so an
index is always a position and a row always answers both cells.

`addable(false)` and the padded row coexist by design — the field still rests on
one editable row, it just offers no way to add a second.

### Every cell binds by row index, and a disabled cell keeps its binding

Each input's `data-model` is built by
[`getDataModel($statePath)`](../schemas/has-state-binding-modifiers.md#the-optional-state-path-is-a-sylament-addition)
over `<the field's path>.<index>.<key|value>`, so the two cells of a row inherit
the field's own `live()` / `lazy()` / `debounce()` modifiers rather than
declaring their own. A cell disabled by `editableKeys(false)`,
`editableValues(false)` or `disabled()` **keeps** that attribute: the browser
fires no event on a disabled input, so nothing is written, while UX Live's
`SetValueOntoModelFieldsPlugin` still repaints it from the store after a morph —
dropping the binding would leave a stale value on screen after every re-render.

### A trigger argument is re-validated against the rows the server rendered

`delete`'s `row` argument arrives from the client, so it is not trusted: it must
be an `int` or a numeric string (a JSON round trip makes an integer key into
one) naming an index the current raw list actually holds, or the action throws a
`LogicException`. The row list is read fresh inside the action rather than
captured when the trigger rendered, which is what makes that check meaningful —
see [the reorder note](#the-reorder-order-is-hardened-and-the-indexes-it-names-are-sound)
for why the index the client names is still the row it meant.

### The reorder order is hardened and the indexes it names are sound

`reorder`'s `items` must be a list; anything else throws. Within it, an index the
list does not hold is dropped and an index the order omitted is appended in its
old position — the [repeater's contract](repeater.md#the-drag-reorder-round-trip-accepts-the-clients-order),
so a stale drag can reorder but never lose or invent a row.

Naming rows by index across a request looks fragile and is not: UX Live applies
the request's dirty model writes during hydration, **before** the action runs, so
the cell values the user typed have already landed at their old indexes by the
time the list is permuted. That is also why deleting row 0 keeps a value typed
into row 1 — the write lands at index 1, then the splice moves it to 0.

### A structural action fires the field's afterStateUpdated hooks itself

`add`, `delete` and `reorder` write the raw bag directly, which is not a state
update the runner notices, so each one calls `callAfterStateUpdatedHooks()` at
the end. The hooks see `getState()` — the map — never the row list.

### Validation reads the raw bag, so the field hands it the map

`HasSchemas::validateSchema()` reads each validated path straight off the host
through the property accessor, which for this field is the **row list** — and
that list always holds at least the blank row, so `required()` could never fail
and a `Count` would count the padding. `KeyValue` therefore overrides
`mutateStateForValidation()` to return the cast map (and `mutatesStateForValidation()`
to `true`, the [`TextInput`](text-input.md) pair's shape), so `NotBlank` sees
`[]` for a blank-only field and the `Count` family counts real rows.

### The cast sniffs the first element, so a map of maps is not a row list

`KeyValueStateCast` tells the two shapes apart by asking whether the first
element is an array: a list of `['key' => …, 'value' => …]` rows is one, a
`['a' => 'one']` map is not. Filament's own sniff, kept verbatim. A map whose
*values* are arrays therefore reads as a row list and comes back mangled — which
is not a shape a key/value field can hold anyway, since both cells are text
inputs.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The rows are server-owned and the three actions are real

Filament renders no rows: the block carries `wire:ignore` plus a `wire:key` hash
of the disabled flag, an Alpine object holds `rows` and syncs a pruned copy into
the entangled state, and the three `Action`s render as inert markup that Alpine's
`x-on:click` handlers act on — the buttons never reach Livewire. The port renders
one `<tr>` per raw row, binds each cell by `data-model`, and registers `add`,
`delete` and `reorder` as ordinary mounted actions over the
[repeater's pipeline](repeater.md).

What that buys: every server-side flag reacts to a re-render (Filament's are
frozen after first paint, since the block is ignored and only the disabled flag
is in its re-key hash), the whole field is reachable from PHP tests, and the
client half is ~50 lines instead of ~200. What it costs: one request per add,
delete and reorder — the repeater's cost, and the reason the field is bound
`norender` by default so typing costs nothing.

Two things make the per-cell binding safe rather than a race. A structural
action's request applies the dirty cell writes during hydration, before the
action body runs, so nothing typed is lost by clicking. And the morph's
focused-element carry-over is defused twice — UX Live rewrites every
`[data-model]` input from its store after each render, and the controller blurs
the focused element before a click reaches a button
([below](#the-controller-is-a-reorder-bridge-and-a-focus-guard-not-a-row-store)).

### The controller is a reorder bridge and a focus guard, not a row store

`key-value.js` is the whole field there: it owns the row list, adds, deletes and
reorders it client-side, prunes blank keys out of the state, and re-derives its
rows from a `$watch` on the entangled value. None of that has anywhere to live
here — the server owns the rows — so
[`key_value_controller.ts`](../../../packages/forms/assets/src/controllers/key_value_controller.ts)
keeps two behaviours.

`reorder(event)` turns support's drag control's `end` event into the host's
`reorder` action, exactly as the repeater's controller does; the binding rides
the same element as the sortable controller because that event does not bubble.

The capture-phase `mousedown` listener is the subtle one. WebKit does not focus a
clicked `<button>`, so the caret stays in the cell the user was typing in; the
live morph then restores focus to the element at that position after the
response, and the browser's own value — the pre-request one — wins over the
re-rendered row now sitting at that index. Blurring the active element before the
click reaches any button inside the table removes the carry-over entirely.
Chromium and Firefox focus the button and never hit it; the listener is
free there.

### The delete and reorder labels are honoured where Filament ignores its own setters

`deleteActionLabel()` and `reorderActionLabel()` exist in Filament and are
readable through `getDeleteActionLabel()` / `getReorderActionLabel()`, but the
two actions are built with the translated literal inlined — the getters are never
consulted, so the setters silently do nothing to the rendered button. A vendor
defect rather than a design; ours label both actions through the getters, so all
three setters work.

### The cast fails fast on a state it cannot read

Filament's cast decodes a string state with a bare `json_decode()` and then calls
`array_key_first()` on the result, so malformed JSON is a `TypeError` deep inside
the cast rather than a decode failure. Ours decodes with `JSON_THROW_ON_ERROR`,
and refuses a state that is neither an array nor a JSON string of one with a
`LogicException` naming the type it met — a corrupted column should say so at the
boundary.

### The seven literals are untranslated

'Add row', 'Delete row', 'Reorder row', 'Key', 'Value', 'Reorder' and 'Actions'
ship as English literals where Filament reads them from
`filament-forms::components.key_value.*`. The standing
[i18n deferral](../roadmap.md#m3--forms-widening): no translation layer exists
yet, and every ported literal will be retrofitted in one change when it does.

### Deprecated Filament members are not ported

`addButtonLabel()`, `deleteButtonLabel()`, `reorderButtonLabel()`,
`disableAddingRows()`, `disableDeletingRows()`, `disableEditingKeys()` and
`disableEditingValues()` are `@deprecated` aliases of the seven live setters.
The standing rule — a port does not ship a surface its source already tells
users to stop calling — so they are **skipped**, and the Unit suite asserts each
one is absent rather than leaving that to a reader's memory.
