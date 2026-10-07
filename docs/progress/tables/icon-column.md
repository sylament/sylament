# IconColumn (tables)

[`Sylament\Tables\Columns\IconColumn`](../../../packages/tables/src/Columns/IconColumn.php) — a [`Column`](column.md) whose cell is icons: one per state value, coloured through a policy of its own and named for assistive technology by a screen-reader-only span, with a boolean mode that turns a `true`/`false` cell into a check or a cross.
It ports Filament's [`Filament\Tables\Columns\IconColumn`](../../../vendor/filament/tables/src/Columns/IconColumn.php).
Everything [`Column`](column.md) carries — the name, the label, the record binding, the state read, the placeholder, the tooltips, the per-item links, the closure injections — is tracked there, not repeated here.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/IconColumn.php`](../../../vendor/filament/tables/src/Columns/IconColumn.php) — the class and its `toEmbeddedHtml()`
- [`tables/src/View/Components/Columns/IconColumnComponent/IconComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/IconColumnComponent/IconComponent.php) — the colour policy [`IconComponent`](../../../packages/tables/src/Colors/Components/IconColumnComponent/IconComponent.php) ports
- [`tables/resources/css/columns/icon.css`](../../../vendor/filament/tables/resources/css/columns/icon.css) — the stylesheet [`icon-column.css`](../../../packages/tables/assets/src/styles/components/icon-column.css) is the prefix-renamed port of

## Surface

Every point of this component.
_Blocked by_ names what has to land first.
_Detail_ carries **diverges** → the permanent note in [Divergences](#divergences), and **semantics** → the permanent note in [Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`boolean()` / `isBoolean()`](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::boolean()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [diverges](#the-boolean-auto-detect-reads-doctrine-metadata-off-the-tables-record-class) |
| [`true()` / `trueIcon()` / `trueColor()` and their getters](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::true()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [semantics](#every-true-and-false-setter-turns-the-column-boolean) |
| [`false()` / `falseIcon()` / `falseColor()` and their getters](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::false()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [semantics](#every-true-and-false-setter-turns-the-column-boolean) |
| [The boolean defaults — the two icon aliases and `success` / `danger`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`IconColumn::getTrueIcon()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [semantics](#the-alias-is-resolved-in-php-and-only-the-icons-have-one) |
| [`getIcon()` / `getColor()` — the base getters with the boolean arm under them](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::getIcon()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [semantics](#a-null-state-is-not-false) |
| [`icon()` / `icons()` / `color()` / `colors()` — the per-state maps and the state contracts](../../../packages/tables/src/Columns/Concerns/HasIcon.php) | [`Concerns\HasIcon`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php), [`Concerns\HasColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php) | done | — | [text-column.md](text-column.md#the-colour-and-icon-maps-resolve-last-match-wins-and-false-suppresses-the-state-contract-fallback) · [diverges](text-column.md#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes) |
| [`size()` / `getSize()`](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::size()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [semantics](#base-is-a-size-that-means-no-size-and-the-default-is-large) |
| [`listWithLineBreaks()` / `isListWithLineBreaks()`](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::listWithLineBreaks()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | — |
| [`wrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | — |
| [`getStateItems()` — the cell's icon list](../../../packages/tables/src/Columns/IconColumn.php) | — (Sylament-only; Filament resolves each item inside `toEmbeddedHtml()`) | done | — | [semantics](#an-item-whose-icon-resolves-blank-contributes-nothing-its-name-included) |
| [The screen-reader text alternative beside each icon](../../../packages/tables/src/Columns/IconColumn.php) | [`IconColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [semantics](#the-icon-is-hidden-and-the-span-beside-it-is-the-only-name) · [diverges](table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships) |
| [`$template`](../../../packages/tables/src/Columns/IconColumn.php) + [the partial it names](../../../packages/tables/templates/partials/IconColumn/index.html.twig) | [`IconColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [diverges](text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) |
| [The blank-state placeholder and the empty tooltip](../../../packages/tables/templates/partials/IconColumn/index.html.twig) | [`IconColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [column.md](column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) |
| [The per-item tooltip and the per-item `<a>`](../../../packages/tables/templates/partials/IconColumn/index.html.twig) | [`IconColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | [column.md](column.md#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom) |
| [`sy-inline`, the alignment class and the wrapper `extraAttributes()`](../../../packages/tables/templates/partials/IconColumn/index.html.twig) | [`IconColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | done | — | — |
| [`Colors\Components\IconColumnComponent\IconComponent`](../../../packages/tables/src/Colors/Components/IconColumnComponent/IconComponent.php) | [`View/Components/Columns/IconColumnComponent/IconComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/IconColumnComponent/IconComponent.php) | done | — | [diverges](#the-icon-policy-measures-the-dark-surface-against-gray-900) · [semantics](#the-policy-carries-a-map-and-the-default-gray-contract-at-once) |
| [`icon-column.css`](../../../packages/tables/assets/src/styles/components/icon-column.css) | [`resources/css/columns/icon.css`](../../../vendor/filament/tables/resources/css/columns/icon.css) | done | — | [diverges](#the-stacked-on-mobile-rule-has-nothing-to-select) |
| `options()` — the deprecated `icons()` alias | [`IconColumn::options()`](../../../vendor/filament/tables/src/Columns/IconColumn.php) | skipped | — | The port ships no deprecated Filament surface — [renames.md](../renames.md) |

The surface rows are covered by the Unit [`IconColumnTest`](../../../packages/tables/tests/Unit/Columns/IconColumnTest.php) — the boolean defaults, every `true*`/`false*` setter turning the column boolean, the two-stage icon and colour resolution (base first, then the boolean pair, with `false` suppressing and `true` still throwing), the null-state arm, the size resolution including `'base'`, the metadata auto-detect on a `query()` lane against both a boolean and a non-boolean field and its absence on the `records()` lane, and the item list with its text-alternative chain — and by the Integration [`IconColumnTest`](../../../packages/tables/tests/Integration/Columns/IconColumnTest.php), which renders columns inside a real table and reads the cell out of `td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-icon`: the two boolean glyphs and their palettes, a state carrying the icon/colour/label contracts, the list and line-break shapes, the dropped blank-icon item, the per-item links, the placeholder, and the `title` a `Markup` tooltip flattens to.
The colour policy is pinned by [`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php), which measures its slots against the `&.sy-color` rule in `icon-column.css` over the adversarial palettes.

## Details

Specifics for rows that are not finished.
Delete a subsection when its row flips to **done**.

Every row above is settled — **done** or **skipped** — so none has a write-up here now.
A row moving to **in progress** or **blocked** grows one.

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### Every `true` and `false` setter turns the column boolean

`trueIcon()`, `trueColor()`, `falseIcon()` and `falseColor()` each open with `$this->boolean()`, so `->trueIcon('heroicons:star')` alone is enough to put the column in boolean mode — and `true()` / `false()`, which are pairs of those two setters, turn it boolean even when both arguments are `null`.
That is Filament's own shape, and dropping it in a port produces a column that configures a boolean icon and then renders nothing at all, because `getIcon()` returns `null` the moment `isBoolean()` is false.

The corollary is that `boolean()` is the only one of the six that is *not* about the icons: it writes `$isBoolean`, a different property from `icon()`'s.
`->boolean(true)` and `->icon(true)` are one keystroke apart and mean opposite things — the second [throws](text-column.md#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes).

### A `null` state is not `false`

`getIcon()` and `getColor()` return `null` for a `null` state item *before* the true/false branch, so a boolean column with a null cell renders no icon rather than the cross.
That is separate from a **blank** state, which never reaches the item loop at all — it takes the placeholder path in the partial.
A nullable boolean field therefore has three renderings, not two: the check, the cross, and nothing.

### `'base'` is a size that means no size, and the default is Large

`getSize()` maps the literal string `'base'` to `null` alongside a blank value, the same dead-string arm [`ImageColumn`](image-column.md#the-sy-size-base-css-arm-is-not-ported) carries for its remaining-text size.
The **default** is not applied there: the getter answers `null` for an unconfigured column and the partial resolves `item.size ?? 'lg'`, so an `IconColumn`'s icons are a step larger than a `TextColumn`'s, which fall back to `sm`.

### The alias is resolved in PHP, and only the icons have one

`getTrueIcon()` and `getFalseIcon()` ask [`IconManager`](../support/icon-manager.md) for `TablesIconAlias::COLUMNS_ICON_COLUMN_TRUE` / `_FALSE` before falling back to `heroicons:check-circle` / `heroicons:x-circle`, so a consumer's `sylament.icons` override is visible to a closure and to a test reading the getter — not only to the rendered markup, which is what resolving the alias through `generateIconHtml()`'s `alias` argument would have bought.
The **colours** have no alias arm at all: `getTrueColor()` and `getFalseColor()` are a literal `'success'` and `'danger'`, exactly as upstream.

The manager arrives through a `#[Required] setIconManager()` setter with a `new IconManager()` fallback, the shape [`Repeater`](../forms/repeater.md) and [`Select`](../forms/select.md) already use — so a column built with `new` outside the container answers the shipped glyph rather than failing to resolve a service.

### An item whose icon resolves blank contributes nothing, its name included

`getStateItems()` returns one entry per state value, and an entry whose icon resolves blank carries nothing but its `state`; the partial filters those out before rendering.
So a list column whose `icons()` map covers three of five values renders three icons — and, deliberately, three text alternatives: the name is built only for an item that has a glyph to name.
A five-value cell can therefore announce fewer values than it holds, which is Filament's behaviour and worth knowing before reading the markup as a bug.

Keeping the dropped entries in the list rather than filtering them in PHP is what makes `items is empty` in the partial mean exactly *the state was blank* — the placeholder branch and the empty-icon case stay distinguishable.

### The icon is hidden, and the span beside it is the only name

Every icon renders `aria-hidden="true"`, so the `<span class="sy-ta-sr-only">` that follows it is the cell's whole accessible content — and the accessible name of any `<a>` the per-item link arm wraps around it.
The name is chosen by the first arm that answers: the item's tooltip, then `Yes` / `No` on a boolean column, then the state's [`HasLabel`](../../../packages/support/src/Contracts/HasLabel.php), then a backed enum's value, then a `Markup`'s stripped text, then the scalar itself.
The result is stripped of tags and trimmed, so a `Markup` tooltip names the icon with its text rather than its markup — the same flattening the `title` attribute gets.

### The policy carries a map and the default-gray contract at once

`IconColumnComponent\IconComponent` implements **both** `HasColor` and `HasDefaultGrayColor` and returns a non-empty map — a shape neither existing tables policy has.
`HasDefaultGrayColor` means `->color('gray')` emits nothing at all, so `icon-column.css`'s own `text-gray-400 dark:text-gray-500` stands; every other palette emits the two carriers plus the shade classes that set `--sy-text` and `--sy-dark-text`, which the `&.sy-color` branch reads.
Contrast that with the text column, where [`gray` is a visible colour on the item and the absence of one on the icon](text-column.md#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon): there the both-contracts policy resolves an *empty* map, because the stylesheet picks the shade off the palette swap.
Here the shade is resolved, because the icons are the cell's only content and owe their surface a measured ratio.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The boolean auto-detect reads Doctrine metadata off the table's record class

Filament's `isBoolean()` probes the **bound record** — `$record instanceof Model && $record->hasCast($name, ['bool', 'boolean'])` — and assigns the answer back onto `$this->isBoolean`, the same property `boolean()` writes.
Eloquent's `$casts` map has no Doctrine twin at the attribute level, so the port asks the mapping instead: `ClassMetadata::getTypeOfField($name) === 'boolean'` over [`Table::getRecordClass()`](table.md) and `getQueryEntityManager()` — the pair [`HasCellState::pathTraversesToMany()`](column.md#the-list-shape-is-metadata-derived-where-filaments-is-cached-off-the-first-row) already uses for the same reason, and memoised the same way, in a private field rather than over the configuration property.

The probe reads `$this->table` rather than [`getTable()`](column.md#gettable-refuses-without-filaments-three-source-chain), and that is load-bearing rather than sloppy: the getter throws for an unmounted column, and `isBoolean()` has to *answer* for one — `IconColumn::make('flag')` built outside a table is not boolean, it is not an error.

Three consequences.
The answer is a property of the **column**, not of the first row that happened to render, so a heterogeneous record set cannot make one row decide for the rest.
An explicit `boolean(false)` still wins, because the detection runs only when `$isBoolean` is blank and `false` is not blank.
And on the `records()` lane there is no mapping to ask, so the detection answers `false` and `boolean()` becomes explicit — which is Filament's own behaviour for a record that is not a `Model`.

The probe reads a **field** name, so a dotted path (`author.isActive`) is not auto-detected; call `boolean()` for one.
Filament's `hasCast()` has the same limit for the same reason.

### The icon policy measures the dark surface against `gray-900`

Filament resolves the `dark:text` slot against `$gray[800]`; this one resolves it against `$gray[900]`, because the shipped table chrome paints `bg-white dark:bg-gray-900`.
It is the identical correction [`TextColumnComponent\ItemComponent`](text-column.md#the-item-policy-measures-the-dark-surface-against-gray-900) already made, with the same reasoning — measuring against a surface the toolkit never paints understates the contrast of every dark cell.

Both slots resolve at `Color::WCAG_AA_NON_TEXT` (3.0), which is Filament's choice and the right one: an icon is a non-text element, and it is the only content the cell has.

### The stacked-on-mobile rule has nothing to select

Upstream's `.fi-ta-table-stacked-on-mobile & { @apply p-0 sm:px-3 sm:py-4 }` is dropped: the stacked-on-mobile table layout is unported, so the ancestor class that rule keys off is emitted nowhere.
It returns with that layout, not before.
[`color-column.css`](../../../packages/tables/assets/src/styles/components/color-column.css) drops the identical rule for the identical reason.
