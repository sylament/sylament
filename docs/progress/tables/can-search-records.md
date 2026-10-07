---
description: 'The table''s search configuration: global and per-column search, the search chrome, the search field component and the per-column search row'
ports:
  - vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php
  - vendor/filament/tables/resources/views/components/search-field.blade.php
  - vendor/filament/tables/resources/views/index.blade.php
  - vendor/filament/support/resources/css/components/input/wrapper.css
  - vendor/filament/support/resources/css/components/input/index.css
owns:
  - packages/tables/assets/src/styles/components/search-field.css
  - packages/tables/src/Table/Concerns/CanSearchRecords.php
---
# CanSearchRecords (tables)

The host half of search, the terms and how they reach the query, is [host-can-search-records.md](host-can-search-records.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`searchable()` / `isSearchable()` / `getExtraSearchableColumns()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [semantics](#a-table-is-searchable-when-a-column-says-so-and-searchable-overrides-in-both-directions) |
| [`searchPlaceholder()`, `searchDebounce()` and `searchOnBlur()` with their getters, and `getSearchDebounceMilliseconds()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [diverges](#getsearchdebouncemilliseconds-is-a-sylament-only-translation-of-filaments-duration-string) · [diverges](#searchonblur-renders-onchange-because-ux-live-component-has-no-blur-event) |
| [`splitSearchTerms()` / `shouldSplitSearchTerms()`, `searchUsing()` / `hasSearchUsingCallback()` / `callSearchUsing()`, `applyExtraSearchConstraints()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [host-can-search-records.md](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) · [semantics](#extra-searchable-columns-take-closures-that-return-their-expression) · [search-constraint.md](../schemas/search-constraint.md#a-field-that-holds-no-character-data-is-cast-to-text-on-postgresql) · [search-constraint.md](../schemas/search-constraint.md#a-search-term-is-not-escaped-for-like) |
| [`getSearchIndicator()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [interacts-with-table.md](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| [`persistSearchInSession()` / `persistsSearchInSession()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`persistColumnSearchesInSession()` / `persistsColumnSearchesInSession()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`isSearchableByColumn()`, `getColumnSearchIndicators()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [host-can-search-records.md](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) |
| [The `SearchField` component](../../../packages/tables/templates/components/SearchField/index.html.twig) + [`search-field.css`](../../../packages/tables/assets/src/styles/components/search-field.css) | [`search-field.blade.php`](../../../vendor/filament/tables/resources/views/components/search-field.blade.php) | done | — | [diverges](#the-search-field-is-a-root-label-around-its-input-with-model-for-wiremodel-and-an-optional-value) · [diverges](#the-search-field-flattens-supports-input-wrapper-into-two-classes) · [diverges](#items-center-is-lifted-onto-the-search-field-because-the-prefix-that-carried-it-is-gone) · [diverges](#the-search-input-adds-an-outline-none-vendor-does-not-have) |
| The search field's Enter → refresh keyup | [`search-field.blade.php`](../../../vendor/filament/tables/resources/views/components/search-field.blade.php) | todo | — | [detail](#the-search-field-does-not-refresh-on-enter) |
| [The per-column search row: the first body row, one search field per individually searchable column and a spacer for every other cell](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `fi-ta-individual-search-row`](../../../vendor/filament/tables/resources/views/index.blade.php) | in progress | — | [detail](#the-per-column-search-row-does-not-hide-at-a-breakpoint-where-no-searchable-column-shows) · [diverges](#the-search-cells-carry-morph-keys-because-they-are-the-one-per-column-element-that-moves) · [diverges](#the-search-cell-drops-vendors-per-column-class-hook-and-the-rows-sy-ta-row) |
| `hasSearch()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | skipped | — | [diverges](#hassearch-is-not-ported-because-a-null-search-indicator-answers-it) |
| `getExtraSearchableColumnRelationship()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | skipped | — | [diverges](#getextrasearchablecolumnrelationship-is-not-ported-because-the-constraint-resolves-the-relation-itself) |

## Details

### The search field does not refresh on Enter

Filament's input carries `x-on:keyup` running `$wire.$refresh()` on Enter, so a term typed and confirmed before the debounce elapses applies at once.
[`SearchField`](../../../packages/tables/templates/components/SearchField/index.html.twig) binds no keyup, so a term applies only when the `debounce(N)` or `on(change)` binding fires.
The binding belongs on the component, which serves the toolbar field and every per-column field alike.

### The per-column search row does not hide at a breakpoint where no searchable column shows

Filament computes, per breakpoint, whether any individually searchable column is visible there, writes the breakpoints where none is as the row's `data-search-hidden-at`, and `table.css` hides the row at each of them.
The port's `<tr class="sy-ta-column-search-row">` carries no such attribute and no rule hides it, so below the breakpoint where every searchable column hides, the row stays as a row of empty cells.
The cells themselves carry each column's `hiddenFrom` / `visibleFrom` classes exactly as vendor's do, so only the whole-row arm is missing.

## Semantics

### A table is searchable when a column says so, and `searchable()` overrides in both directions

`isSearchable()` reads three sources in order: an explicit `searchable(bool)` wins outright, then any extra searchable columns, then any column that is globally searchable.
`$isSearchable` is `?bool`, so null means nobody said, which is what makes the column-derived answer reachable, and `searchable(false)` also clears `extraSearchableColumns` so the second source cannot contradict the explicit false.
A column marked `searchable(isGlobal: false, isIndividual: true)` does not make the table searchable: it asked for its own input in the [per-column row](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column), not for the global field.
All of it is Filament's.

### Extra searchable columns take closures that return their expression

`searchable(['author.name', fn () => …])` takes closures beside names, and a closure joins the word's `OR` group the way a column's `searchable(query:)` closure does, by returning an expression rather than mutating a nested builder ([interacts-with-table-query.md](interacts-with-table-query.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group)).
A closure that returns the builder, which `andWhere()` does, is skipped rather than added, because its constraint is already on the query outside the `OR` group.
That skip is explicit: `QueryBuilder` implements `Stringable`, so the `string|Stringable` test that admits raw DQL would otherwise hand it to `Orx::add()`, which refuses anything outside its `Comparison`/`Func`/`Andx`/`Orx` allow-list with an `InvalidArgumentException`.
A string member goes through `SearchConstraint::forColumn()`, so it becomes a `LIKE` or a correlated `EXISTS` depending on what the name [resolves to](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused).

The injections are Filament's three, `$query`, `$search` and its `$searchQuery` alias, and a blank member is skipped before any of it, so a list built conditionally (`['title', $extra ?? '']`) does not resolve an empty path.
Filament's `blank($column)` guard is spelled `array_filter(…, filled(…))` over the list instead, because the helper's `@phpstan-assert-if-false !null` makes a direct `blank()` call on a non-nullable `string|Closure` member an impossible check at level max; as an `array_filter` callback the same helper keeps the semantics, a whitespace-only member being blank too.

## Divergences

### `getSearchDebounceMilliseconds()` is a Sylament-only translation of Filament's duration string

Filament stores `searchDebounce('500ms')` and interpolates the string into `wire:model.live.debounce.{$debounce}`, where Livewire parses it.
ux-live-component's `data-model` modifier is `debounce(N)` with N in milliseconds only, so the string is parsed on the PHP side, and `getSearchDebounceMilliseconds()` is the value the table partial hands the field.
`searchDebounce()` and `getSearchDebounce()` keep Filament's shape, taking and returning the duration string with a `'500ms'` default, so a migrating call site is unchanged.
The new member accepts `500ms`, `1s` and a bare number read as milliseconds, and answers 500 for anything it cannot parse rather than throwing, since a debounce is a comfort setting.
It is a [name with no Filament source](../translations.md#names-with-no-filament-source).
Permanent: the string is the API, the integer is the wire format.

### `searchOnBlur()` renders `on(change)`, because ux-live-component has no blur event

Filament's `searchOnBlur()` swaps the input's binding to `wire:model.live.blur`, which commits the field on every blur.
[`SearchField`](../../../packages/tables/templates/components/SearchField/index.html.twig) renders `on(change)|<model>` instead, because ux-live-component's `on()` modifier accepts only `input` or `change` and throws on anything else (`live_controller.js`, the `modelDirective.modifiers` switch).
`change` fires when a changed value leaves the field, or on Enter, so blurring an untouched input commits nothing where Livewire would commit it.
The setter and its getter keep Filament's shape (`searchOnBlur()` / `isSearchOnBlur()`), and the intent the name states, do not search while I type, is served exactly.
Schemas makes the same translation for `lazy()` ([has-state-binding-modifiers.md](../schemas/has-state-binding-modifiers.md#livewire-modifier-array-grammar-becomes-a-single-getdatamodel-data-model-string)), for the same reason.

### The search field is a root `<label>` around its input, with `model` for `wireModel` and an optional `value`

Filament's `search-field` component renders a `div` holding a visually hidden `<label>` bound to the input through `x-id`, then the input inside support's input wrapper.
The port's [`SearchField`](../../../packages/tables/templates/components/SearchField/index.html.twig) is one `<label>` wrapping the icon and the input, which names the input without an id, and the input carries the label text as `aria-label`.
Filament's `wireModel` prop is `model`, and its `debounce` duration string is `debounceMs`, an integer, beside `onBlur` and `placeholder`.
`value` is a prop the per-column call fills with the column's current term; the toolbar's call leaves it off.
Filament's `labelHidden` prop, which shows the label on a table stacked on mobile, has no counterpart, because `stackedOnMobile()` is [blocked](table.md#surface).

### The search field flattens support's input wrapper into two classes

`fi-ta-search-field` carries no rule of its own in Filament, only the stacked-on-mobile label arm `.fi-ta-search-field > .fi-ta-cell-label`: it is a hook on a `<div>`, and everything visible comes from the `x-filament::input.wrapper` it contains, where `.fi-input-wrp` paints the chrome, an inner `.fi-input-wrp-prefix.fi-inline` holds the icon, and `input.fi-input.fi-input-has-inline-prefix` sits in a `.fi-input-wrp-content-ctn` beside it.
The field does not render through support's [input wrapper](../support/input-wrapper.md), so the four vendor elements collapse to two, `<label class="sy-ta-search-field">` holding the icon and the input directly, styled by `search-field.css`.

The metrics carry over unchanged.
`gap-x-2` between the icon and the input is the distance vendor computes, the inline prefix's `pe-2` plus the input's `ps-0`; vendor's `gap-x-3` separates several prefix children and never shows here, because the prefix holds one visible child, the icon beside a `wire:loading` indicator that is `display: none` at rest (and which the port does not render, loading affordances being [deferred](can-defer-loading.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them)).
The leading `ps-3` is the prefix's, the trailing `pe-3` is the `pe` half of the input's `px-3`, `min-w-0` is lifted off `.fi-input-wrp-content-ctn`, and the `@supports (-webkit-touch-callout: none)` Safari-zoom block is vendor-verbatim, comment included.
The input's `disabled:` / `dark:disabled:` arms, including the `[-webkit-text-fill-color:…]` pair iOS needs, are carried too, since the element is a real `<input>`.

Two vendor rules cannot come across.
`.fi-input-wrp`'s disabled chrome (`&.fi-disabled { bg-gray-50 dark:bg-transparent }`) and its focus guard (`&:not(.fi-disabled):not(:has(.fi-ac-action:focus))`, which suppresses the ring while a prefix action holds focus) both key off DOM this field does not emit: no `sy-disabled` on the field, and no action mounted inside it.
Both return if the field renders through the input-wrapper component, along with the two-layer structure.

### `items-center` is lifted onto the search field, because the prefix that carried it is gone

Vendor's `.fi-input-wrp` is a bare `flex`, so its `align-items` stays `stretch`; the `items-center` sits one level in, on [`.fi-input-wrp-prefix`](../../../vendor/filament/support/resources/css/components/input/wrapper.css) (`hidden items-center gap-x-3 ps-3`), the box holding the icon.
[`search-field.css`](../../../packages/tables/assets/src/styles/components/search-field.css)'s `.sy-ta-search-field` is `flex items-center`: the class that ports the wrapper carries it, and the prefix class has no port, because the element it styles is not emitted.

The lift keeps the icon where Filament draws it.
Vendor centres the icon inside a prefix box that stretches to the field's full height.
With the prefix flattened away the icon is a direct child of the wrapper, where `stretch` degrades to `flex-start` for an item with a definite cross size, and the icon has one, `.sy-icon`'s `size-5`, 1.25rem against a field 2.25rem tall (the input's `leading-6` plus its `py-1.5`).
Without the lift the magnifying glass renders 0.5rem above vendor's.

The input is the one element the two arrangements can place differently, and they do not while it is the tallest thing in the field, since it sets the height.
Vendor's stretches inside `.fi-input-wrp-content-ctn` and ours is centred as a direct child, so they part only if the field is given a height of its own or a taller sibling.
Nothing sets either, and the divergence closes if the field renders through the input-wrapper component.

### The search input adds an `outline-none` vendor does not have

`input.fi-input` suppresses the UA outline in the focused state only, with `focus:ring-0 focus:outline-none`.
`.sy-ta-search-input` carries both of those and a bare `outline-none`, which Tailwind v4 compiles to a resting `outline-style: none`; no vendor declaration is behind it.

It buys nothing visible: a UA draws an input's outline while the element is focused, and `:focus-visible` is a subset of `:focus`, so vendor's `focus:` arm already reaches every state the resting one could.
The suppression is paired with vendor's own replacement: focus lands on the input and `.sy-ta-search-field:focus-within` paints `ring-2 ring-primary-600` (`dark:ring-primary-500`) around the whole field, as `.fi-input-wrp`'s `focus-within:ring-2` does, so the field keeps a visible keyboard-focus indicator.

What it changes is the reach of the suppression.
Vendor leaves every non-focused state alone, so an outline an application draws there, a `:user-invalid` ring or a forced-colors affordance, lands unopposed; here it has to beat `.sy-ta-search-field .sy-ta-search-input`, (0,2,0).
Nothing in the toolkit draws one, so the arm removes no outline anyone renders.
Provisional on that: it goes in the change that first wants a resting outline on this input.

### The search cells carry morph keys, because they are the one per-column element that moves

Every other per-column element in the table is stationary within a render: a header cell and a record cell are rebuilt together and idiomorph's positional pairing is right by construction.
The search cells are not.
A reorder re-`columns()` the table in the stored order and a toggle drops a cell outright, so the row's children are where both the set and the order of siblings change under a live morph, and idiomorph pairs unkeyed siblings by position (`isIdSetMatch` → `findIdSetMatch` → `findSoftMatch`).

Two failures follow, and they need different keys.
After a reorder, a dirty input pairs with whichever column then occupies its position, so the term is applied to one column and typed into another; an `id` on the cell, `<hostId>.table.column-search.<column>`, makes id-set pairing correct.
When the leading spacer count changes, which happens the moment a search matches nothing, since the spacers render only beside records, the input node is destroyed and rebuilt, losing focus and every keystroke typed during the round trip; a second `id` on the input, the cell's id plus `.input`, keeps the node alive across the shift.

Filament keys only the input, with `wire:key` set to `<id>.table.<wireModel>.field.input`, and its cell carries no key.
The cell id is the port's addition, the host-scoped `id` this repo uses in place of `wire:key` on [the record row and cell](table.md#the-row-and-cell-morph-keys-disagree-on-record-vs-records).
The toolbar's field carries no id, where Filament keys its input too: it has one instance and no siblings to be confused with.

### The search cell drops vendor's per-column class hook, and the row's `sy-ta-row`

Vendor's search cell carries a second class beside `fi-ta-individual-search-cell`, `fi-ta-individual-search-cell-<kebab>`, built from the column's camel-then-kebab name, and the port does not emit it.
Nothing in either stylesheet selects it, since vendor's `cell.css` styles the bare `.fi-ta-individual-search-cell`, and the per-column handle a caller needs is already on the cell as its `id`, `<hostId>.table.column-search.<column>`, which is stable and unambiguous where a kebabbed class is not.
Adding it would cost one call, because [`Column::getKebabCasedName()`](column.md#the-hook-class-kebabs-each-dotted-segment-on-its-own) already derives the per-column body and header hooks `sy-ta-cell-<kebab>` / `sy-ta-header-cell-<kebab>`; the hook is absent because it has no reader.

Vendor's row carries `fi-ta-row fi-ta-row-not-reorderable fi-ta-individual-search-row`, and the port's carries `sy-ta-column-search-row` alone.
`sy-ta-row` is the class every record row carries, and the stripe, the selected tint and the clickable marker are all `sy-ta-row` rules in `table.css`, none of which has anything to say about a row of inputs, so putting it on a chrome row that holds no record would make the rows mean two different things.

### `hasSearch()` is not ported, because a null search indicator answers it

Filament's `hasSearch()` asks the host whether a global term is set, beside a `getSearchIndicator()` that always returns an `Indicator`.
The port's `getSearchIndicator()` delegates to the host's `getTableSearchIndicator()` and answers null when no term is set ([host-can-search-records.md](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column)), so the null carries what the separate gate says.
The per-column half needs no gate either, an empty `getColumnSearchIndicators()` being its own answer.

### `getExtraSearchableColumnRelationship()` is not ported, because the constraint resolves the relation itself

Filament's `getExtraSearchableColumnRelationship()` walks a dotted name to decide whether its head is an Eloquent relationship, so `applyExtraSearchConstraints()` can switch to `whereRelation`.
Here [one resolution](has-query.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) answers that inside `SearchConstraint::forColumn()` for every tier at once, so `searchable(['author.name'])` searches across the association with no join declared, and no caller is left holding the branch the member exists to decide.
