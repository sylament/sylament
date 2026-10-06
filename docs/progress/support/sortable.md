# Sortable (support)

The shared drag-and-drop control —
[`packages/support/assets/src/controllers/sortable_controller.ts`](../../../packages/support/assets/src/controllers/sortable_controller.ts),
the port of Filament's `x-sortable` Alpine directive, plus the one rule its ghost
class needs
([`assets/src/styles/components/sortable.css`](../../../packages/support/assets/src/styles/components/sortable.css)).
It creates one SortableJS instance on the element carrying
`sylament--support--sortable`, drags the children marked `data-sortable-item`
from their `[data-sortable-handle]`, and announces every real move as a
non-bubbling `sylament--support--sortable:end` event on that same element.

It is deliberately **live-host-agnostic**: it never calls a live action, never
imports `@symfony/ux-live-component`, and holds no opinion about what an order
means. `support` is the base workspace — it cannot reach schemas'
`resolveLiveHost`, and growing a live-component peer for one controller would
push that dependency onto every consumer of the base package — so the write
lives in the consumer, which binds the event and decides what to do with the
keys. Four consumers ship. Forms' `sylament--forms--repeater` was the first;
tables' [`reorderable()`](../tables/table.md#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render)
is the second, and it binds the control differently — the `<tbody>` mounts it only
while the table is in reorder mode, where a repeater's item list carries it
always — which is the first proof that the control's live-host-agnostic contract
survives being mounted and unmounted rather than only connected once. M3 · 9
added two more, both the repeater's way — the container carries the control
whenever the field can be reordered at all, rather than per mode:
[`KeyValue`](../forms/key-value.md) on its row `<tbody>`, and
[`TagsInput`](../forms/tags-input.md) on its chip container, which is the first
mount inside a `data-live-ignore` subtree the consumer's own controller repaints.
`FileUpload` is **not** one — FilePond owns its own list and reorders through its
`allowReorder` option.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/js/sortable.js`](../../../vendor/filament/support/resources/js/sortable.js)
  — the whole `x-sortable` directive (47 lines), option for option
- [`support/resources/css/sortable.css`](../../../vendor/filament/support/resources/css/sortable.css)
  — the single ghost rule

## Surface

Every point of this control. _Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Semantics](#semantics), and/or
**diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The directive → a Stimulus controller](../../../packages/support/assets/src/controllers/sortable_controller.ts) | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) `Alpine.directive('sortable', …)` | done | — | [diverges](#the-control-attaches-only-where-a-consumer-asks-for-drag) |
| [Item marker `x-sortable-item` → `data-sortable-item`](../../../packages/support/assets/src/controllers/sortable_controller.ts) | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) `draggable` + `dataIdAttr` | done | — | — |
| [Handle marker `x-sortable-handle` → `data-sortable-handle`](../../../packages/support/assets/src/controllers/sortable_controller.ts) | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) `handle` | done | — | — |
| [`x-sortable-group` → the `group` value](../../../packages/support/assets/src/controllers/sortable_controller.ts) | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) `group: el.getAttribute('x-sortable-group')` | done | — | [detail](#an-empty-group-value-means-no-group) |
| [`data-sortable-animation-duration` → the `animationDuration` value](../../../packages/support/assets/src/controllers/sortable_controller.ts) | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) `parseInt(el.dataset?.sortableAnimationDuration)` | done | — | [detail](#the-animation-duration-is-a-stimulus-number-value) |
| [Ghost class `fi-sortable-ghost` → `sy-sortable-ghost`](../../../packages/support/assets/src/styles/components/sortable.css) | [`sortable.css`](../../../vendor/filament/support/resources/css/sortable.css) | done | — | [diverges](#only-the-ghost-class-is-ours-the-other-three-stay-vendor-defaults) |
| [The #17402 `onEnd` re-insert workaround](../../../packages/support/assets/src/controllers/sortable_controller.ts) | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) `onEnd` | done | — | [detail](#the-17402-workaround-and-why-a-chrome-carrying-list-needs-it) |
| `window.Sortable` | [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) line 3 | skipped | — | [diverges](#the-instance-is-private-and-nothing-is-assigned-to-window) |
| [The `end` answer](../../../packages/support/assets/src/controllers/sortable_controller.ts) | — (Filament's consumers bind SortableJS' own bubbling `end` with `.stop`) | done | — | [diverges](#the-answer-is-a-namespaced-non-bubbling-event-dispatched-only-on-a-real-move) |

## Contract

What a consumer writes, and what it gets back. This table is the interface —
forms' repeater partials emit it, forms' repeater controller binds it, and the
three later consumers build against it unchanged.

| Piece | Value |
| --- | --- |
| Identifier | `sylament--support--sortable`, on the **list element** (the repeater's items `<ul>`, the simple list `<ul>`, the table `<tbody>`, the key-value rows' `<tbody>`, the tags input's chip container `<div>`) |
| Values | `animationDuration` (Number, default `300`); `group` (String, default `""`) |
| Item marker | `data-sortable-item="<key>"` on each draggable child — it is Sortable's `draggable` selector **and** its `dataIdAttr`, so a child without it is neither draggable nor enumerated, which is exactly how a list may interleave chrome (the repeater's between-items `<li>`s) that stays put |
| Handle marker | `data-sortable-handle` on the element a drag starts from; a list whose items carry no handle is undraggable |
| Options | `{ group, draggable: "[data-sortable-item]", handle: "[data-sortable-handle]", dataIdAttr: "data-sortable-item", animation, ghostClass: "sy-sortable-ghost", onEnd }` — nothing else |
| Event | `sylament--support--sortable:end`, dispatched on the list element with `bubbles: false`, so nested sortables never hear each other and a consumer binds it on the same element it put the controller on |
| Event detail | `{ items: string[], item: string \| null, oldDraggableIndex: number, newDraggableIndex: number }` — `items` is `toArray()` (every draggable child's key in the new DOM order), `item` the dragged child's key, and the index pair counts **draggable children only** (a chrome-free list's draggable index is its absolute index; `KeyValue` splices by it, tables passes `item` as its second argument) |
| Lifecycle | `connect()` calls `Sortable.create(this.element, …)`; `disconnect()` destroys the instance and nulls the field. The instance is a private field — never `Sortable.get()`, which cannot see across two bundled copies (each carries its own expando key) |
| Touch | native HTML5 drag-and-drop where the browser has it; iOS and Android Chrome take SortableJS' fallback, whose cloned ghost is appended inside the list (`fallbackOnBody` left at `false`) |

Boolean-shaped values do not appear here — the control has none — but the two it
has are read once, at `connect()`. A consumer that changes `animationDuration`
after connect gets the old value until the controller reconnects.

## Semantics

### An empty `group` value means no group

Stimulus' String value defaults to `""` when the attribute is absent, and
SortableJS treats `group: ""` as a *named* group rather than an absent one, so
the controller maps `""` back to `undefined` before handing the options over.
Filament reads `el.getAttribute('x-sortable-group')`, which is `null` when
absent, and `null` is what SortableJS' own default is — the mapping is what keeps
the two equivalent. No shipped consumer sets a group today; it exists because
Filament's directive exposes it and cross-list drag is the obvious next use.

### The animation duration is a Stimulus Number value

Filament parses `el.dataset.sortableAnimationDuration` with `parseInt` and falls
back to `300` when the result is `NaN` — `if (animation !== 0 && !animation)` —
which deliberately keeps an explicit `0` (drag with no animation). A Stimulus
Number value with `default: 300` behaves the same way: the attribute absent gives
`300`, `data-…-animation-duration-value="0"` gives `0`. The attribute name
differs, because a Stimulus value attribute is namespaced by the controller
identifier (`data-sylament--support--sortable-animation-duration-value`), and
that is the systematic Alpine → Stimulus translation the
[rename table](../renames.md) records, not a rename of the feature.

### The #17402 workaround, and why a chrome-carrying list needs it

`filamentphp/filament#17402`: when a list interleaves **non-draggable** siblings
between its draggable children — precisely the repeater's `addBetween` blocks —
SortableJS' insertion point is computed against the absolute child list while the
index pair it reports counts draggables only, so the node lands next to the wrong
sibling. Filament re-seats it inside `onEnd`, and the port carries that verbatim
in meaning: when the draggable index actually changed, it looks up
`to.querySelectorAll(":scope > [data-sortable-item]")[newDraggableIndex - 1]` and
`insertBefore`s the dragged node after it. The `> 0` guard is what makes a move to
the head of the list a no-op here — there is no preceding draggable to seat
after — and the `!== undefined` guard is real despite the DOM lib typing the
index access as `Element`: an out-of-range `NodeList` index is `undefined` at
runtime.

Two orderings to keep straight. SortableJS dispatches its own bare, bubbling DOM
`end` event **before** it runs the `onEnd` option callback, so a listener on the
bare name sees the DOM *before* the re-seat; ours is dispatched from inside
`onEnd`, i.e. after it. And `toArray()` enumerates only children matching the
`draggable` selector, so the key list is the same either way — the re-seat fixes
the DOM, not the answer.

The matching consumer rule: never drive such a list with `Sortable.sort()`. It
appends past trailing non-draggables, which is the same defect from the other
side.

### The morph round trip, and why the consumer must accept the client's order

The control moves a DOM node the server also renders, so what survives a UX Live
re-render decides whether inputs keep their identity. All of the following is in
`vendor/symfony/ux-live-component/assets/dist/live_controller.js`.

`ExternalMutationTracker`'s `handleChildListMutation` **nets a same-parent move
out**: a node removed and re-added within one batch is spliced from whichever list
it landed in first, in either arrival order, so Sortable's `insertBefore` leaves
no added/removed record at all. `ElementChanges`' `addClass`/`removeClass` cancel
in the same way, so the transient `sortable-chosen` / `sortable-ghost` /
`sortable-drag` classes net to nothing; `ChangingItemsTracker.removeItem` deletes
a style property that was set from absent and then cleared, so `transform` and
`will-change` net out too. In fallback mode the cloned ghost is added and removed
inside the list and nets out with them.

**One mutation survives.** `handleGenericAttributeMutation` records
`draggable: null → "true"` at drag start and `"true" → "false"` at drop, and the
attribute still exists at the end, so the tracker keeps a permanent
`draggable="false"` change on every item that has ever been dragged and replays it
onto the incoming node at `beforeNodeMorphed` for the rest of that item's life.
It is behaviourally harmless — SortableJS re-sets `draggable` at the next drag —
but it defeats the `isEqualNode` short-circuit for that item, so the item is
always fully morphed rather than skipped.

The morph itself matches by `id`: idiomorph's `isIdSetMatch` hits its direct-id
arm for our items, because a repeater `<li>` already renders
`id="<key>.<itemKey>"` and the item key is a uuid, not a position. So when the
server re-renders the list **in the order the client sent**, `morphChildren`
walks `[B, A]` against `[B, A]`, morphs each item in place, and no input is
removed or re-created — focus and uncommitted values survive.

When the server's order **disagrees** with the DOM, that guarantee is gone.
`morphChildren` fails the id-set match at the insertion point, falls through to
`findIdSetMatch` and then `findSoftMatch`, and either path removes a live item
node and re-inserts it from fresh markup — its inputs destroyed and rebuilt,
focus and in-flight typed values lost. Which item pays depends on the relative id
weights, so it is not even predictable.

**The rule that falls out: a consumer's reorder handler must accept the ordering
the client sent.** Filament neither undoes the client move nor guards against
this — it simply always accepts — and so must we. A handler that silently no-ops
(disabled, a failed validation, a lost race) costs a rebuilt item, which is why
the control has no "rejected" path and why the write lives in the consumer that
can promise this rather than in the control.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The answer is a namespaced, non-bubbling event dispatched only on a real move

Filament's directive dispatches nothing of its own: its consumers bind SortableJS'
own `end` event, which is bare-named and bubbling, and therefore have to write
`x-on:end.stop` so a nested list's drop is not also heard by its ancestor. The
port answers on `this.dispatch("end", { detail, bubbles: false })` instead, i.e.
`sylament--support--sortable:end` on the list element only. Three things follow.
The name cannot collide with another library's `end`. Nested sortables are
isolated by construction rather than by every consumer remembering `.stop` — which
matters more here than in Filament, because Stimulus 3 has no `:stop` action
option and a consumer would have to call `event.stopPropagation()` in a method
body. And the event carries a typed `detail` (the key list, the dragged key and
the draggable-index pair), where SortableJS sets its payload as own properties on
the event object and leaves `detail` unused.

The port also **only** dispatches on a real move — `from !== to` or a changed
draggable index — where SortableJS' `end` fires on every drop, including one that
put the item back where it started. A consumer therefore never pays a live
round-trip for a no-op drag.

### The control attaches only where a consumer asks for drag

Filament's `x-sortable` is written onto the list unconditionally by the views that
have a sortable list at all, and the directive instantiates SortableJS every time.
The port is a Stimulus controller, so attaching it is a decision the emitting
template makes per render — forms' repeater emits it only when the repeater is
actually drag-reorderable. That follows the `CheckboxList` precedent of attaching
a controller only when there is behaviour, and it keeps a ~120 KB library from
initialising on lists nobody can drag.

### Only the ghost class is ours; the other three stay vendor defaults

`ghostClass` is handed `sy-sortable-ghost` and
[`sortable.css`](../../../packages/support/assets/src/styles/components/sortable.css)
styles it (`@apply opacity-30`, Filament's rule for `fi-sortable-ghost`).
`chosenClass`, `dragClass` and `fallbackClass` are left at SortableJS' defaults
(`sortable-chosen`, `sortable-drag`, `sortable-fallback`) and are **not** styled.
This is [the bundled-library rule](../../internals/styling.md#why-is-a-bundled-library-styled-under-a-sylament-class)'s first live case: the toolkit's
stylesheet is page-wide, so selecting `.sortable-ghost` bare would style a host
app's own widget of the same name — a class a bundled library emits is reachable
only under a `sy-` name we hand the library ourselves, which is what `ghostClass`
lets us do and what leaving the other three unstyled avoids needing.

`fallbackOnBody` is likewise left at `false` deliberately. Setting it `true` would
append the touch-mode ghost to `<body>`, outside the `.sy` scope the toolkit's
preflight and the `.dark` theme class both live under, and the ghost would render
unstyled.

### The instance is private, and nothing is assigned to `window`

Filament's `sortable.js` does `window.Sortable = Sortable` so Blade expressions can
reach `$event.target.sortable`. The house assigns no globals anywhere, so the
instance is a private class field and the only way out of the controller is the
event. `Sortable.get(element)` is not an escape hatch either: it reads an expando
key minted per bundle, and a page carrying both this control and forms' select
control has two independently bundled copies of SortableJS whose expandos differ,
so a lookup from the wrong copy silently returns `undefined`.

### SortableJS is bundled, twice, pinned to one version

The controller is a `BUNDLED_ENTRIES` build in
[`packages/support/assets/scripts/build-js.mjs`](../../../packages/support/assets/scripts/build-js.mjs)
(`external: ['@hotwired/stimulus']`, `license: 'sortablejs'`), so `sortablejs` is
inlined into `dist/controllers/sortable_controller.js` behind the banner the build
composes from the installed package, and consumers need no importmap entry for it.
Forms' `select` controller bundles its own copy the same way, so a page using both
carries the library twice — sanctioned by the carried decision that each dist
inlines its own copy, as Filament's own builds do. The two copies are kept from
drifting into different **versions** by a `sortablejs: "^1.15.7"` override in
[`pnpm-workspace.yaml`](../../../pnpm-workspace.yaml); nothing keeps them from
being two objects at runtime, which is the point of the private-instance rule
above.
