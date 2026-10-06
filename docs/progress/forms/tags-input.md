# TagsInput (forms)

`Sylament\Forms\Components\TagsInput` — the free-text tag list: one unbound text
box for the tag being typed, and a chip per tag already in the list, each chip a
[`<twig:SylamentSupport:Badge>`](../support/badge.md) with a delete button. It is
the first field whose control writes its state through
**`component.set()` alone** — nothing in it carries a `data-model`, because the
value is an array and a live model read of a non-checkbox element is a scalar —
and the first whose visible list is a client-owned subtree
(`data-live-ignore`) that the [Stimulus controller](#the-chip-list-is-client-owned-after-the-first-paint)
repaints from the host's value store on every render. Extends
[`Field`](field.md), composes [`HasAffixes`](has-affixes.md),
[`HasExtraInputAttributes`](has-extra-input-attributes.md),
[`HasNestedRecursiveValidationConstraints`](can-be-validated.md), schemas'
`CanTrimState` / `CanStripCharactersFromState` — [per element](#the-trim-and-strip-hooks-map-over-the-list)
— and support's `HasColor` and `HasReorderAnimationDuration`; reorder rides the
shared [`sylament--support--sortable`](../support/sortable.md) control.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php)
  — the field class and its embedded view
- [`forms/resources/js/components/tags-input.js`](../../../vendor/filament/forms/resources/js/components/tags-input.js)
  — the Alpine data object the Stimulus controller ports
- [`forms/resources/css/components/tags-input.css`](../../../vendor/filament/forms/resources/css/components/tags-input.css)
  — the container and chip chrome

## Surface

Every point this field adds over `Field`. `Field`'s own surface is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links a permanent note in [Semantics](#semantics) /
[Divergences](#divergences), or the write-up in another file.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction &amp; rendering — the unbound text box, the chip list, the delete button and the live region | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#the-text-box-is-scratch-state-and-carries-no-data-model) · [semantics](#the-chip-list-is-client-owned-after-the-first-paint) · [semantics](#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it) · [diverges](#the-alpine-data-object-becomes-a-stimulus-controller-over-a-cloned-twig-chip) · [diverges](#an-empty-list-keeps-the-container-and-drops-its-chrome) · [diverges](#a-non-string-tag-is-read-and-rewritten-as-a-string) |
| `setUp()` — `default([])`, the hydration hook, the dehydration closure, the placeholder and the 100 ms reorder animation | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#an-emptied-separator-field-dehydrates-to-an-empty-string) |
| `hydrateTags()` — the non-array state, the `explode()` and the single-blank collapse | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#hydratetags-runs-off-afterstatehydrated-so-only-a-fill-explodes-the-string) · [diverges](#three-lines-are-retyped-for-strict-types) |
| `separator()` / `getSeparator()` | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#an-emptied-separator-field-dehydrates-to-an-empty-string) |
| `splitKeys()` / `getSplitKeys()` — the keys that end a tag, and the paste split | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [diverges](#getsplitkeys-keeps-a-strict-array-return) |
| `suggestions()` / `getSuggestions()` and the `<datalist>` | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#the-datalist-lives-inside-the-ignored-container-and-the-controller-owns-its-options) · [diverges](#suggestions-drops-the-arrayable-arm) |
| `tagPrefix()` / `getTagPrefix()` and `tagSuffix()` / `getTagSuffix()` | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | — |
| `reorderable()` / `isReorderable()` and the drag-and-drop reorder | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#a-duplicate-tag-is-refused-on-entry-but-a-seeded-one-renders-twice) · [diverges](#the-alpine-data-object-becomes-a-stimulus-controller-over-a-cloned-twig-chip) |
| `getDefaultStateCasts()` — the array-aware `StripCharactersStateCast` | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#the-trim-and-strip-hooks-map-over-the-list) |
| `mutateDehydratedState()` / `mutatesDehydratedState()` — the per-element trim | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#the-trim-and-strip-hooks-map-over-the-list) |
| `mutateStateForValidation()` / `mutatesStateForValidation()` — the per-element strip then trim | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | done | — | [semantics](#the-trim-and-strip-hooks-map-over-the-list) · [semantics](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag) |
| Colour — `color()` through the badge policy | [`support/Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | [detail](../support/color-policies.md) |
| Affixes — the twelve prefix/suffix props through `InputWrapper` | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [detail](has-affixes.md) |
| Nested recursive constraints — one `All` over the list | [`Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | done | — | [detail](can-be-validated.md) |
| `required()` on an empty list | [`Concerns/CanBeValidated.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeValidated.php) | done | — | — |
| Extra input attributes | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| [`HasPlaceholder`](field.md) — already on `Field` here, and `setUp()`'s `New tag` default is the one caller | [`Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [diverges](field.md#hasplaceholder-is-composed-on-the-base-field) |
| The `sy-fo-tags-input-wrp` inline-label padding rule | [`components/tags-input.css`](../../../vendor/filament/forms/resources/css/components/tags-input.css) | blocked | schemas' `HasInlineLabel` | [detail](styles.md#the-input-and-select-stylesheets-stop-at-the-alignment-arms) |
| `HasExtraAlpineAttributes` | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| `$publishedViewOverrideCheckPath` | [`Components/TagsInput.php`](../../../vendor/filament/forms/src/Components/TagsInput.php) | skipped | — | [diverges](#there-is-no-published-view-to-probe-for) |

This table carries no [unaudited row](../README.md#an-unaudited-table-says-so):
every member of the vendor class has a row above.

## Semantics

### The text box is scratch state and carries no `data-model`

The `<input type="text">` holds the tag being typed — Filament's `newTag` — not
the field's value, and it is the only text-like control the toolkit ships with
no model binding at all. Four things make a bound element impossible here and
each one alone is decisive: `live_controller` strips a `[]` suffix off a model
name, so `data-model="data.tags[]"` binds to `data.tags`; `getValueFromElement()`
reads a non-checkbox input as a **scalar**, so any write would collapse the list
to one string; a hidden input fires no model event, so a mirror element would
never write at all; and `SetValueOntoModelFieldsPlugin` walks `[data-model]`
after every render and would stringify the array back onto whatever element
carried the name. So the controller owns the write — one
`component.set(statePath, [...tags], render, debounce)` per change — and the box
carries only the field `id` (the `<label for>` target), `list`,
`autocomplete="off"`, the placeholder, `disabled` and the extra input
attributes. It carries no `name` and no `required` either: it is not the field.

It carries no `aria-invalid` either, which every other text-like control here does.
The box holds the tag being typed, so a validation failure is never *its* state
that is invalid — the field's list is. The invalid state rides
[`InputWrapper`](../support/input-wrapper.md)'s `sy-invalid` on the chrome around
the box and [`FieldWrapper`](field.md)'s messages under it, which is
Filament's shape too: its `$inputAttributes` bag carries no `aria-invalid`
either. The departure is from our own `TextInput` and `Select`, not from vendor.

### The binding modifiers reach the controller as a string, and only two of them are read

`modelBinding` is `getDataModel()` verbatim, and the controller's `parseBinding()`
— the [select controller's](select.md) function, copied — reads exactly
`norender` and `debounce(N)` off it. `on(change)` is not read, so a field
configured `live(onBlur: true)` writes **eagerly**, as the searchable select
already does. That is deliberate rather than an oversight: the write is not a
DOM event on a bound element, so there is no change event to defer to, and the
alternative — deferring to the text box's `blur` — would drop a tag created by
Enter until the box lost focus.

### The chip list is client-owned after the first paint

`div.sy-fo-tags-input-tags-ctn` carries `data-live-ignore`, so the morph never
descends into it, and the controller is what keeps it in step: on `connect()` and
on every `render:finished` it re-reads `valueStore.get(statePath)` and reconciles
the chips against that list whenever it differs from the `data-value`s on screen.
Reconciles, not rebuilds: a chip whose value is still in the list keeps its node
and moves only if its position changed, and only a value with no node left to
claim is cloned from the `<template>`. That reuse is load-bearing rather than an
optimisation — the delete button lives on the chip, and a repaint that removed it
between `mousedown` and `mouseup` dispatches the `click` at the container instead,
losing the delete silently. A chip clicked while half-typed text sits in the box
sets exactly that up, since the blur fires `createTag()` first. Alpine's keyed
`x-for` gives Filament the same reuse. Its `id` is
`{fieldId}-tags` — a **constant**, and it has to be: idiomorph runs its
id-mismatch branch *before* the ignore branch, so an id that moved with the state
(a hash of the tags, say) would blow the client-owned subtree away on the first
render after any change. Nothing is ever appended outside that container either;
a chip beside the server-rendered ones would survive every morph and duplicate.
The server still renders the initial chips into it, so with JavaScript off the
tags are visible and read-only.

### The chips are cloned from a server-rendered `<template>`, and the tag parameter is not called `tag`

Both the initial chips and the blueprint the controller clones come from one Twig
`{% macro chip(value, color, prefix, suffix, reorderable, delete_attributes) %}`,
so the delete icon stays on `SupportIconAlias::BADGE_DELETE_BUTTON` and the chip
colour stays on the badge policy rather than being retyped as markup in
JavaScript. The macro lives in its own file
([`TagsInput/Chip.html.twig`](../../../packages/forms/templates/partials/TagsInput/Chip.html.twig))
because `_self` inside a `<twig:>` block resolves to the *embedded* component
template, which carries none of the outer template's macros. Its first parameter
is `value`, not `tag`: the chip is a `<twig:SylamentSupport:Badge>` in block
form, whose content block inherits the component's context, and the Badge
declares a `tag` prop of its own (defaulting to `'span'`) — so a macro parameter
named `tag` renders the string `span` inside the label and nothing warns.

### The datalist lives inside the ignored container and the controller owns its options

The server renders `<option>` for each suggestion **minus** the tags already
chosen — Filament's `x-if` — and the controller rebuilds the set on every sync
and whenever the `suggestions` value is morphed. It sits inside the ignored
container with the chips, which costs nothing: an `<input list>` resolves its
datalist by id document-wide, and Stimulus connects targets inside an ignored
subtree like any other.

### `hydrateTags()` runs off `afterStateHydrated`, so only a fill explodes the string

A `separator()` field stores a joined string, and the list only appears when the
bag is **filled** — `hydrateTags()` is registered as an `afterStateHydrated`
hook. A bare live write of a string to the path (which no client of this field
makes) leaves the string sitting there and renders no chips. The record path is
the one that matters and it hydrates: `LiveTagsInputTest` seeds `'a,b'` through
`mount()` and reads two chips back.

### An emptied separator field dehydrates to an empty string

With `separator()` set, clearing every tag saves `''`, not `null`. The `''` → null
normalisation runs *before* the dehydration closure, so the closure receives `[]`
and `implode()` returns `''` after the normalisation has already passed. This is
Filament's exact behaviour and it is kept; a column that must go null needs a
`dehydrateStateUsing()` of its own on top.

### A duplicate tag is refused on entry, but a seeded one renders twice

`createTag()` refuses a tag the list already holds — clearing the box, silently —
and `deleteTag()` removes **every** occurrence of the deleted value, both
Filament's. Neither guards the state itself: a record that already holds
`['a', 'a']` renders two chips, and because the sortable control keys children by
`data-sortable-item`, the two drag as two identical keys. Filament has the same
shape and no de-duplication anywhere.

### The trim and strip hooks map over the list

`mutateDehydratedState()` and `mutateStateForValidation()` are the same asymmetry
every trim/strip consumer carries
([textarea.md](textarea.md#trim-reaches-the-saved-value-strip-reaches-it-through-a-cast-neither-reaches-the-raw-bag)),
applied one level down: this is the first field whose hooks map over each
**element** rather than acting on a scalar, and the `StripCharactersStateCast`
`getDefaultStateCasts()` appends is array-aware for the same reason. A scalar
state — which only a mis-seeded bag produces — still takes the plain path.

### Every controller action no-ops while the input is disabled

`disabled()` renders `sy-disabled` on the wrapper, and the stylesheet hides the
delete buttons under it. The controller does not rely on that: `createTag()`,
`keydown()`, `paste()`, `deleteTag()` and `reorder()` each return early while
`inputTarget.disabled`, so a scripted click or a drag started before the field
was disabled writes nothing.

### The controller is the only behaviour, and no test in this repo runs it

Creating a tag, splitting a paste, deleting, reordering, rebuilding the datalist,
announcing into the live region and every repaint of the chip list live in
[`tags_input_controller`](../../../packages/forms/assets/src/controllers/tags_input_controller.ts)
alone. The three PHP suites cover the server's first paint, the state layer and
the live round trip; none of them runs a line of the controller, the same gap
[`CheckboxList`](checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
records. Every row above is `done` on the strength of a browser pass for
that half — read the surface table with that in mind.

## Divergences

### The Alpine data object becomes a Stimulus controller over a cloned Twig chip

Filament renders the chips with `x-for` over an `$entangle`d array inside a
`wire:ignore`, and hardcodes the delete button's SVG into the Alpine template.
The port keeps the same division of labour — server-rendered first paint,
client-owned list thereafter — with the two mechanisms the toolkit already has:
one `sylament--forms--tags-input` Stimulus controller writing through the live
component's value store, and one Twig macro emitting the chip for both the
initial render and the `<template>` the controller clones. Nothing else about
the field differs. Two consequences worth naming: the reorder is support's
shared `sylament--support--sortable` control rather than a `x-sortable`
directive, and the vendor stylesheet's `& > template { hidden }` rule is dropped
because the blueprint sits outside the chip container (a `<template>` is
`display: none` by the UA stylesheet anyway).

### An empty list keeps the container and drops its chrome

Filament wraps the chip container in `<template x-cloak x-if="state?.length">`, so
an empty field has no container element at all. Ours has to keep it: it is the
controller's `chips` target, the morph finds it by the id its `data-live-ignore`
hangs off, and the live region and the `<datalist>` sit inside it. So the element
stays and the stylesheet takes the chrome away instead —
`&:not(:has(> .sy-badge)) { @apply border-t-0 p-0; }` — leaving a flex box of zero
height where Filament leaves nothing. Without that rule an empty field paints a
full-width rule and 16 px of padding under the input, which vendor never shows;
hiding the container outright is not the alternative, because a `display: none`
live region announces nothing.

### A non-string tag is read and rewritten as a string

The state is whatever the record holds, and a JSON column of `[1, 2]` is a legal
tag list: Twig renders `data-value="1"` for it, so the controller reads the store's
members as the strings the server already rendered rather than filtering the list
to strings. Filtering would find none, wipe both chips on the first sync, and let
the next write persist a list missing them. A member that is neither a string nor
a number carries no chip and is dropped. The cost is that the first write after any
edit saves the whole list as strings, where Filament's `x-for` leaves the untouched
numbers numeric; a column that must stay numeric needs a `dehydrateStateUsing()` of
its own.

### `nullWhenBlank()` is inert on this field, as on every array-valued leaf

`nullWhenBlank()` is composed onto the base `Component`, so it is offered here and
silently does nothing: the record mapper reaches a nullable branch only through
`ownsRecordSubtree()` — which `Repeater` declares and a field does not — or through
`hasDehydratedBranchLeaf()`, which scans the leaves *beneath* a branch path. A
`TagsInput` is a leaf with neither, so `shouldNullBranch()` never runs and an
emptied field writes `[]` into its column rather than `null`. Schemas records the
same refusal from the mapper's side
([belongs-to-record.md](../schemas/belongs-to-record.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored)
sits next to the pinned case, "a `nullWhenBlank()` leaf writing nothing because a
leaf declares no branch"), where it is a tested refusal rather than an oversight; it reads as a gap only from
the field, which is why it is restated here. A column that must go `null` when the
list empties takes a `dehydrateStateUsing()` on the field — the demo's admin arm
does exactly that — and the same holds for [`KeyValue`](key-value.md) and any other
field whose state is an array it owns whole.

### `getSplitKeys()` keeps a strict array return

Filament writes `return $this->evaluate($this->splitKeys) ?? [];` over a
**non-nullable** `array | Closure` property, so that coalesce can only ever answer
for a closure returning `null`. Ours cannot carry it: PHPStan at level max infers
a non-nullable `array<string>` out of `evaluate()` and reports the `??` as
`nullCoalesce.expr`, and widening the property to nullable to earn the guard back
would be weakening a type to route around the analyser. So `splitKeys(fn () => null)`
is a `TypeError` here where Filament yields `[]`. `getSuggestions()` keeps its
`?? []` because its own property is nullable in Filament as well
(`array | Arrayable | Closure | null`), and that is the whole of the asymmetry
between the pair.

### `suggestions()` drops the `Arrayable` arm

Filament types it `array<string> | Arrayable | Closure | null` and unwraps an
`Illuminate\Contracts\Support\Arrayable` in the getter. That interface is
Laravel's, has no Symfony counterpart and is not a dependency here, so the union
is `array<string>|Closure` — the same call
[`HasDatalistOptions`](has-datalist-options.md#the-datalist-drops-the-arrayable-arm)
and [`acceptedFileTypes()`](file-upload.md#the-arrayable-arm-of-acceptedfiletypes-becomes-iterable)
already made. A closure returning an array covers every case that arm did.

### Three lines are retyped for strict types

Filament's loose-typed lines do not compile under `declare(strict_types=1)` at
PHPStan level max, and each is retyped without changing the behaviour of any
state that reaches it. `hydrateTags()` normalises a non-string state to `null`
before `explode()` — Filament's `explode($separator, $state ?? '')` coerces an
`int` silently and `TypeError`s on an object, so a non-string, non-array raw
state hydrates to `[]` here. The single-blank collapse reads the pre-`explode()`
value (`count($tags) === 1 && blank($state)`) rather than `blank($state[0])` —
provably the same value, since one element means the text held no separator —
and it is written that way because support's `blank()` carries a
`@phpstan-assert-if-false !null`, which level max reads bidirectionally and
reports as `function.impossibleType` against the statically non-null element of
an `explode()` result. And `dehydrateStateUsing()` guards `is_array($state)` and
casts each element (`is_scalar($tag) || $tag instanceof Stringable`) before
`implode()`, the shape [`Select`](select.md) and `HasRelationship` already use;
Filament's bare `implode($separator, $state)` would fatal on a null state.

### There is no published view to probe for

`$publishedViewOverrideCheckPath` lets Filament warn when a user has published
and edited the packaged Blade view for a component. Nothing here publishes
templates — a consumer overrides a Twig template through Symfony's own
`templates/bundles/` path or a `template()` call — so there is no path to probe
and nothing to warn about, the same reading
[`DateTimePicker`](date-time-picker.md#the-alpine-and-published-view-surface-has-no-counterpart)
and [`FileUpload`](file-upload.md) already record.
