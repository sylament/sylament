---
description: The text column and its state pipeline, from date, number, money and HTML formatting to badges, icons, colours, descriptions and lists.
ports:
  - vendor/filament/tables/src/Columns/TextColumn.php
  - vendor/filament/tables/src/Columns/Concerns/CanFormatState.php
  - vendor/filament/tables/src/Columns/Concerns/HasColor.php
  - vendor/filament/tables/src/Columns/Concerns/HasIcon.php
  - vendor/filament/tables/src/Columns/Concerns/HasIconColor.php
  - vendor/filament/tables/src/Columns/Concerns/HasDescription.php
  - vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent.php
  - vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent/IconComponent.php
  - vendor/filament/tables/resources/css/columns/text.css
  - vendor/filament/support/src/Concerns/CanConfigureCommonMark.php
  - vendor/filament/support/src/Concerns/HasLineClamp.php
owns:
  - packages/support/src/Components/Concerns/HasLineClamp.php
  - packages/tables/assets/src/controllers/limited_list_controller.ts
  - packages/tables/assets/src/styles/components/text-column.css
  - packages/tables/src/Colors/Components/TextColumnComponent/
  - packages/tables/src/Columns/Concerns/CanFormatState.php
  - packages/tables/src/Columns/Concerns/HasColor.php
  - packages/tables/src/Columns/Concerns/HasDescription.php
  - packages/tables/src/Columns/Concerns/HasIcon.php
  - packages/tables/src/Columns/Concerns/HasIconColor.php
  - packages/tables/src/Columns/TextColumn.php
  - packages/tables/templates/partials/TextColumn/
---
# TextColumn (tables)

[`Sylament\Tables\Columns\TextColumn`](../../../packages/tables/src/Columns/TextColumn.php) is a [`Column`](column.md) that renders its cell state as text, through the formatting pipeline that sits between the raw state and the template.
The five `Columns\Concerns\` traits it composes, [`CanFormatState`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php), [`HasColor`](../../../packages/tables/src/Columns/Concerns/HasColor.php), [`HasDescription`](../../../packages/tables/src/Columns/Concerns/HasDescription.php), [`HasIcon`](../../../packages/tables/src/Columns/Concerns/HasIcon.php) and [`HasIconColor`](../../../packages/tables/src/Columns/Concerns/HasIconColor.php), have their rows here, per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature).
Everything [`Column`](column.md) carries, from the name and the record binding to the state read and the closure injections, is tracked there.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`formatStateUsing()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState::formatStateUsing()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#formatstateusing-accepts-a-literal-where-filament-accepts-only-a-closure) |
| [`formatState(mixed $state)` — the `state` injection](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState::formatState()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#formatstate-throws-for-a-state-it-cannot-stringify) · [diverges](#formatstate-always-returns-a-string-where-filament-passes-a-scalar-through) |
| [`getFormattedState()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | — (Sylament-only) | done | — | [diverges](#getformattedstate-replaces-the-embedded-views-local-preamble) |
| [`getStateItems()` — the cell's value list](../../../packages/tables/src/Columns/TextColumn.php) | — (Sylament-only) | done | — | [semantics](#getstateitems-is-the-cells-value-list) · [diverges](#the-collapsed-join-flattens-a-markup-item-where-filament-keeps-it-raw) · [diverges](#formatstateforitem-throws-for-a-format-result-the-pipeline-cannot-produce) |
| [`$template`](../../../packages/tables/src/Columns/TextColumn.php) + [the partial it names](../../../packages/tables/templates/partials/TextColumn/index.html.twig) | [`TextColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [diverges](#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) · [diverges](#the-item-element-is-never-merged-into-the-outer-div) · [diverges](#the-icons-gap-is-a-margin-because-the-line-clamp-owns-the-items-display) |
| `hasStateFormatting()` — the raw-state fast path | [`CanFormatState::hasStateFormatting()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | skipped | — | [diverges](#hasstateformatting-has-nothing-to-guard) |
| `hasBulleted()` / `hasListWithLineBreaks()` / `hasSize()` — the optimized render's predicates | [`TextColumn::canRenderOptimized()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | skipped | — | [diverges](#the-item-element-is-never-merged-into-the-outer-div) |
| [`limit()` / `words()` — character and word truncation](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#limit-composes-slice-rather-than-calling-symfonys-truncate) |
| [`prefix()` / `suffix()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [semantics](#a-markup-state-skips-truncation-and-affixing-escapes-whichever-side-is-not-already-markup) |
| [`html()` / `markdown()` / `commonMarkOptions()` / `commonMarkExtensions()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php), [`CanConfigureCommonMark`](../../../vendor/filament/support/src/Concerns/CanConfigureCommonMark.php) | done | — | [semantics](#html-sanitizes-in-php-and-markdown-converts-before-it-does) · [diverges](#markdown-throws-for-the-package-it-suggests-rather-than-falling-through) · [diverges](#the-commonmark-pair-is-declared-on-canformatstate-not-composed-from-a-support-trait) |
| [`date()` / `dateTime()` / `time()` / `since()`, the four `*Tooltip()` twins and `timezone()` / `getTimezone()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [semantics](#one-timezone-chain-answers-for-every-date-arm) · [diverges](#the-date-family-formats-through-datetimeimmutable-so-only-english-reads-right) |
| `isoDate()` / `isoDateTime()` / `isoTime()` and their three tooltips | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | blocked | i18n | [detail](#the-iso-family-waits-on-i18n-and-only-on-that) · [has-default-data-formatting-settings.md](../support/has-default-data-formatting-settings.md#the-three-iso-defaults-ship-with-no-reader) |
| [`money()` / `numeric()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [semantics](#the-money-and-numeric-arms-are-numberformatter-with-two-sentinels) · [diverges](#numeric-refuses-a-formatter-failure-rather-than-rendering-false) |
| Negative-zero suppression in `money()` / `numeric()` | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | todo | — | [detail](#money-and-numeric-print-a-negative-zero-that-laravels-number-suppresses) |
| The numeric marker class and `tabular-nums` on a `money()` / `numeric()` cell | [`TextColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), [`columns/text.css`](../../../vendor/filament/tables/resources/css/columns/text.css) | todo | — | — |
| [`badge()`](../../../packages/tables/src/Columns/TextColumn.php) — the badge shell | [`TextColumn::badge()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), [`BadgeComponent`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | done | — | [diverges](#a-badged-icon-goes-through-the-badges-own-props-and-loses-iconcolor-doing-it) · [diverges](#a-lists-ul-always-sits-inside-the-outer-div) · [diverges](#alignment-reaches-a-badge-row-through-the-list-not-through-text-align) |
| [`color()`](../../../packages/tables/src/Columns/Concerns/HasColor.php) / [`icon()` / `iconPosition()`](../../../packages/tables/src/Columns/Concerns/HasIcon.php) / [`iconColor()`](../../../packages/tables/src/Columns/Concerns/HasIconColor.php) | [`HasColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php), [`HasIcon`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php), [`HasIconColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasIconColor.php) | done | — | [semantics](#the-colour-and-icon-maps-resolve-last-match-wins-and-false-suppresses-the-state-contract-fallback) · [semantics](#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) · [diverges](#a-badged-icon-goes-through-the-badges-own-props-and-loses-iconcolor-doing-it) · [diverges](#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes) · [diverges](#a-colors-or-icons-closure-is-handed-the-item-state-where-filaments-is-handed-the-columns) |
| [`Colors\Components\TextColumnComponent\ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) — the item's colour policy | [`TextColumnComponent/ItemComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent.php) | done | — | [diverges](#the-item-policy-measures-the-dark-surface-against-gray-900) · [semantics](#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) |
| [`Colors\Components\TextColumnComponent\ItemComponent\IconComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent/IconComponent.php) — the item icon's colour policy | [`TextColumnComponent/ItemComponent/IconComponent.php`](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent/IconComponent.php) | done | — | [semantics](#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) |
| [`description()` — the secondary line above or below](../../../packages/tables/src/Columns/Concerns/HasDescription.php) | [`HasDescription`](../../../vendor/filament/tables/src/Columns/Concerns/HasDescription.php) | done | — | [semantics](#a-description-position-is-compared-never-evaluated) |
| [`size()`](../../../packages/tables/src/Columns/TextColumn.php) / [`weight()`](../../../packages/support/src/Components/Concerns/HasWeight.php) / [`fontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) / [`lineClamp()`](../../../packages/support/src/Components/Concerns/HasLineClamp.php) | [`TextColumn::size()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), [`HasWeight`](../../../vendor/filament/support/src/Concerns/HasWeight.php), [`HasFontFamily`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php), [`HasLineClamp`](../../../vendor/filament/support/src/Concerns/HasLineClamp.php) | done | — | [diverges](#weight-and-fontfamily-are-column-level-where-filaments-are-per-item) · [diverges](#getsizeclass-is-sylament-only-and-the-partial-resolves-the-size-twice) |
| [`wrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | [diverges](#a-lists-ul-always-sits-inside-the-outer-div) |
| [`copyable()` / `copyableState()` / `copyMessage()` / `copyMessageDuration()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) + [the control the partial renders](../../../packages/tables/templates/partials/TextColumn/index.html.twig) | [`CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php), [`TextColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [copyable.md](../support/copyable.md) · [semantics](#the-copy-target-is-the-content-element-and-the-badge-pill-when-there-is-one) |
| [Lists — `bulleted()`, `listWithLineBreaks()`, `limitList()`, `expandableLimitedList()`, `getStateOverListLimitCount()`](../../../packages/tables/src/Columns/TextColumn.php) + [`limited_list_controller.ts`](../../../packages/tables/assets/src/controllers/limited_list_controller.ts) | [`TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [semantics](#getstateitems-is-the-cells-value-list) · [diverges](#the-expand-toggles-render-only-where-there-is-something-to-expand) |
| [`rowIndex()`](../../../packages/tables/src/Columns/TextColumn.php) | [`TextColumn::rowIndex()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [column.md](column.md) · [diverges](#rowindex-refuses-a-cell-with-no-row-loop-rather-than-numbering-it-null) |
| [`text-column.css`](../../../packages/tables/assets/src/styles/components/text-column.css) | [`columns/text.css`](../../../vendor/filament/tables/resources/css/columns/text.css) | done | — | [diverges](#a-lists-ul-always-sits-inside-the-outer-div) · [diverges](#the-icons-gap-is-a-margin-because-the-line-clamp-owns-the-items-display) · [diverges](color-column.md#the-stacked-on-mobile-rule-has-nothing-to-select) |

## Details

### The `iso*` family waits on i18n, and only on that

`isoDate()`, `isoDateTime()`, `isoTime()` and their three tooltips take moment.js locale presets such as `L`, `LLL` and `LT`, which name a *locale's* preferred date shape rather than a token sequence.
`DateTimeImmutable::format()` has no such concept: handing it `'L'` emits the leap-year flag.
Answering them means ICU locale data and a locale to read it under, which is i18n's to supply, so the setters stay blocked rather than shipping a partial port that formats wrongly.
Nothing else blocks them: the three defaults they read are already on [`HasDefaultDataFormattingSettings`](../support/has-default-data-formatting-settings.md), carried unread for exactly this.

### `money()` and `numeric()` print a negative zero that Laravel's `Number` suppresses

Filament formats through Laravel's `Number::currency()` and `Number::format()`, which pass the value through `withoutNegativeZero()` first: a negative amount that formats the same as its absolute value at the chosen precision is printed as zero.
The port calls `NumberFormatter::formatCurrency()` and `NumberFormatter::format()` on the value directly, so `-0.001` renders `-$0.00` under `money('USD')` and `-0.00` under `numeric(decimalPlaces: 2)` where Filament renders `$0.00` and `0.00`.
The `number_format()` arm that a separator argument selects is unaffected, because Filament's calls `number_format()` directly too.

## Semantics

### A `Markup` state skips truncation, and affixing escapes whichever side is not already `Markup`

`formatState()` captures `$isMarkup` before deciding whether to call `truncateState()`, and skips it whenever the state is already `Markup`, either because it arrived that way or because the `HasLabel` unwrap returned one.
That check runs on the state alone, before `affixState()` ever sees a prefix or suffix, so a `Markup` *affix* against a still-plain state does not suppress it: `limit()` / `words()` still truncate the state first, and only then does the affix step decide whether to escape or promote.
Cutting into already-escaped markup mid-tag would corrupt it; cutting a plain string that only later picks up an escaped affix cannot.

`affixState()` then holds one invariant across both directions: the returned value never mixes raw and unescaped text, so whichever of the state or its affix is *not* already `Markup` gets escaped, and the result is promoted to `Markup` either way.
A `Markup` prefix or suffix against a plain state escapes the state and trusts the affix raw; a plain prefix or suffix concatenated onto an already-`Markup` state is escaped before it is appended, trusting the state raw instead.

### The colour and icon maps resolve last match wins, and `false` suppresses the state-contract fallback

`colors()` and `icons()` walk every entry of the given map on every call, with no `break`, so the last entry that matches wins, not the first.
That includes the bare, numeric-keyed default: `['gray', 'success' => 'published']` does not read as *gray unless published*; the default applies unconditionally at its position in the iteration and is only left standing if nothing declared after it also matches the state.
Reordering the array reorders the result.

`getColor()` and `getIcon()` add a second rule at the state-contract fallback: an evaluated `false`, whether from a literal `->color(false)` / `->icon(false)`, a closure that returns `false`, or a `colors()` / `icons()` map that resolves to `false`, returns `null` immediately and never reaches the `$state instanceof ColorContract` / `IconContract` check below it.
An unset colour or icon, the property's `null` default, *does* reach that check.
So `false` does not mean *no colour*, it means *no colour, and do not ask the state either*, where leaving the setter uncalled defers to the state's own `getColor()` / `getIcon()`.
The other boolean, `true`, takes neither path: it [throws](#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes).

### `gray` is a colour on the item and the absence of one on the icon

The two policies the partial names disagree about `gray` on purpose, and the asymmetry is Filament's.
[`ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) implements `HasColor` **only**, so `->color('gray')` emits the full class set, `sy-color`, `sy-color-gray` and the resolved shade classes, and [`text-column.css`](../../../packages/tables/assets/src/styles/components/text-column.css)'s `.sy-color-gray` branch, declared after the `.sy-color` one and matching at the same specificity, paints the item `gray-500` / `dark:gray-400`.
Gray is therefore a *visible* choice on a cell, distinct from leaving the colour unset, which leaves the base `gray-950` / `dark:white`.
[`IconComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent/IconComponent.php) does implement `HasDefaultGrayColor`, so `->iconColor('gray')` emits nothing at all and the icon's own base grey stands.
Adding the contract to `ItemComponent`, the obvious symmetry and what schemas' [`TextComponent`](../schemas/text.md) does, would make `->color('gray')` a silent no-op and leave the `.sy-color-gray` rule dead; [`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php) pins the asymmetry in both directions.

The icon's shade comes from the stylesheet rather than from its policy, which is what an empty `getColorMap()` [means](../support/color-policies.md#an-empty-map-is-a-real-answer-not-a-missing-one): the two carrier classes swap the palette and `text-column.css` reads `text-color-500` off it.
Writing the icon rule the way the item's is written, `text-(--sy-text)`, would compile, ship, and paint nothing, because no class an empty map emits ever sets that variable.

Those stylesheet shades are Filament's own, copied from its `columns/text.css` and `badge.css`: `text-color-500` on an `iconColor()`-coloured item icon, `text-gray-400 dark:text-gray-500` on an uncoloured one, and the same gray pair on a gray badge's icon.
Against the surface each sits on, the table chrome's `bg-white dark:bg-gray-900` on `.sy-ta-ctn` for the item icons and the gray badge's own `bg-gray-50` for its icon, five pairings fall under the 3.0:1 a non-text element owes, all in light mode: the coloured item icon on `primary`, `success` and `warning`, the uncoloured item icon, and the gray badge icon.
They stay as Filament ships them, because raising them is a sweep of both packages' stylesheets rather than anything this column's surface can reach, and no policy ever touches them.
`PolicyShadeGridTest` pins the failing list exactly, over every shipped palette, so it goes red when a shade is raised and when a palette lands that fails a pairing the others pass.
A consumer palette is outside that net: a theme that moves `primary` or `warning` changes which pairings fail.

### A `description()` position is compared, never evaluated

`description($text, position: $position)` sorts its argument with `$position === 'above'`, a raw identity test taken before anything is evaluated, so a **closure** position is compared against a string, never matches, and the description lands *below*.
`description('1997', position: fn (): string => 'above')` therefore renders in the opposite place from the one it names, with nothing said about it.

The signature is what makes the obvious reading wrong: `string|Closure|null $position` declares a closure legal, and every other closure-typed argument on a column is evaluated somewhere.
This one is Filament's, parameter type and comparison both ([`Columns\Concerns\HasDescription`](../../../vendor/filament/tables/src/Columns/Concerns/HasDescription.php)), and it is ported as written rather than repaired, because repairing it is not local: the position picks which of two *properties* the description is stored in, at configuration time, where a closure has no record and no state to answer against, so evaluating it here would either resolve it too early or force the pair of properties to collapse into one that `getDescriptionAbove()` / `getDescriptionBelow()` then have to re-sort on every read.
A caller wanting a conditional position writes the condition around two calls instead.

### One timezone chain answers for every date arm

`getTimezone()` is `evaluate($this->timezone) ?? date_default_timezone_get()`, and every date arm reads the same chain: the per-call `$timezone` argument first, then the column's `timezone()`, then the process default.
Filament forks: `getTimezone()` reads `FilamentTimezone::get()` when `isDateTime()` and `config('app.timezone')` otherwise, and `time()` sets `isTime` but never `isDateTime`, so it lands on the config branch while `dateTime()` lands on the facade one.
There is no `TimezoneManager` here to fork on, so the fork collapses; the forms `DateTimePicker` reads the same process default, which is what makes one chain the consistent answer rather than merely the shorter one.

The five family markers are set the way Filament sets them, asymmetries included: `dateTime()` sets `isDateTime` and then delegates to `date()`, which sets `isDate` as well, so a `dateTime()` column reports true for both.
Reading `isDate()` as *"and not a datetime"* is the mistake the shape invites.

### The money and numeric arms are `NumberFormatter` with two sentinels

`money()` and `numeric()` share three guards before any formatting: a blank state returns `null`, a non-numeric state is passed through unchanged, and the locale is `$locale ?? $table->getDefaultNumberLocale() ?? \Locale::getDefault()`, with a `BackedEnum` unwrapped whichever link answers.
Symfony's `Request::setLocale()` calls `\Locale::setDefault()`, so that tail *is* the request locale under a request, the translation of Filament's `config('app.locale')`.

Two arguments are sentinels rather than plain defaults, and both are easy to misread.
`money()`'s `$divideBy` defaults to **`0`**, and the division runs only when the evaluated value is truthy, so the default divides by nothing rather than by zero.
And `numeric()`'s two separators default to [`ArgumentValue::Default`](../support/enums.md), a pure enum no caller can forge: passing *either* separator, `null` included, routes the whole call to `number_format()`, while leaving both untouched keeps the locale-aware `NumberFormatter`.
A boolean flag could not express that, because `null` is a legal separator value and would be indistinguishable from *not passed*.

Precision follows Laravel's `Number::format()`: `maxDecimalPlaces` sets `MAX_FRACTION_DIGITS` and wins outright over `decimalPlaces`, which otherwise sets `FRACTION_DIGITS`; with neither, the formatter's locale default applies.

### `html()` sanitizes in PHP, and `markdown()` converts before it does

The fork sits in `formatState()` after the `HasLabel` / `BackedEnum` unwrap and before the truncation gate; Filament's sits before its `HasLabel` unwrap, so a label is sanitized here and passed through raw there.
When `isHtml()`, which is true whenever `isMarkdown()` is, Filament's own definition, the state is converted from markdown if asked, then sanitized, then flagged `$isMarkup`.
That flag is the one the pipeline already reads as *skip truncation* (cutting into escaped markup mid-tag would corrupt it) and as *escape the plain side* when an affix joins, so `html()` inherits both behaviours without a line of its own.

The sanitizer runs **last**, always.
A CommonMark converter passes raw HTML through by design, which is what `html_input` configures, so sanitizing before conversion would guard nothing, and it is the only guard standing in front of the `Markup` the template renders raw.
It arrives through a `#[Required]` setter, so a column built with `new` rather than through the container has none and `html()` throws a `LogicException` naming the fix rather than emitting unsanitized markup.

Symfony's `default` sanitizer is what a consumer gets unless it configures otherwise, and it strips relative `href`/`src`, `class` and `style`.
A consumer that wants Filament's allowances configures `framework.html_sanitizer.sanitizers.default`; this port names no sanitizer of its own, so one application-level setting governs every column.

### The copy target is the content element, and the badge pill when there is one

`copyable()` is [support's `CanBeCopied`](../support/can-be-copied.md) and the markup is [support's `clipboard` controller](../support/copyable.md); what this column decides is *which* element carries them.
On a plain item it is a `span.sy-copyable` wrapped around the content alone, not the whole item, so the focus ring hugs the text, and not a `<button>`, because the cell may render inside a record `<a>` where interactive content is invalid.
On a badged item the attributes are spread onto the `Badge` component instead, so the pill *is* the control, which is what Filament does too.

The copied text is `getCopyableState($item)` falling back to the item's **formatted** content, so a `money()` column copies `$1,234.50` rather than `1234.5` unless `copyableState()` says otherwise.
The tooltip rides the same element when both are set, as one `title`.

### `getStateItems()` is the cell's value list

`getStateItems()` is `getFormattedState()`'s per-item successor.
A non-array `Traversable` state, a Doctrine `Collection` or an `ArrayIterator`, is materialized with `iterator_to_array()` first, the same unwrap Filament's own `toEmbeddedHtml()` runs before its `Arr::wrap()` (`$state instanceof Collection`, [`TextColumn.php:285-287`](../../../vendor/filament/tables/src/Columns/TextColumn.php)).
[`wrap()`](../support/helpers.md) itself only spreads arrays and leaves anything else, a `Traversable` included, as one item, so this unwrap has to run first; only then does `wrap($state)` turn a scalar into one item and leave an array, native or just materialized, as many.

Three steps then run, in Filament's order.
The **slice** drops everything past `limitList()`'s limit, unless the column both breaks its list onto lines and is `expandableLimitedList()`, the one shape whose extra values have to reach the DOM for the client to reveal ([divergence](#the-expand-toggles-render-only-where-there-is-something-to-expand)).
`getStateOverListLimitCount()` counts what the limit excludes, off the *unsliced* list, and is what the partial's message reads; it is `ImageColumn`'s `getStateOverLimitCount()` under a name that says which limit it means.
The **collapse** then joins what survives ([why that is the default](#the-collapsed-join-is-filaments-default-not-a-simplification)), every value formatted individually and joined with `', '` into a *single* item, unless the column is badged, breaks onto lines (`bulleted()` forces that, `listWithLineBreaks()` asks for it), or is already single-valued.
Each surviving value is finally paired with its formatted content.

One subtlety in that gate: it counts the values **after** the slice, where Filament counts before it.
The two differ only when a limit cuts a multi-value list down to one, and there the port emits that value formatted on its own while Filament emits it through an identity join: the same text either way, but the port's `state` stays the raw value, so a `colors()` map still matches it.

The collapse is why each entry carries `state` and `content` separately rather than one value doing both jobs.
In the per-item branches, `state` is the **raw** value, what `getColor()` / `getIcon()` / `getSize()` would receive if a caller passed it directly, and `content` is `formatState($value)`'s result.
In the collapsed branch there is no raw per-item value left, because collapsing is itself a formatting step, so `state` and `content` are both the same already-joined, already-formatted string.
The consequence: a `colors()` / `icons()` map keyed on the column's original per-value identifiers (`colors(['success' => 'symfony'])`) matches those identifiers only while the column is badged or single-valued; once a multi-value cell collapses, its one item's `state` is the joined string (`"symfony, forms"`), which cannot match any per-value key in the map.
Badging a multi-value column is what turns per-value colouring back on.

### The collapsed join is Filament's default, not a simplification

A multi-value state collapses to a single comma-joined item by default ([`TextColumn.php:371-385`](../../../vendor/filament/tables/src/Columns/TextColumn.php)); `listWithLineBreaks()` keeps the items as separate lines, `bulleted()` renders them as a `<ul>` with markers, and `limitList()` / `expandableLimitedList()` truncate the set with an expand trigger.
The default is the one a reader mistakes for a missing feature.

A `TextColumn` over `['symfony', 'forms']` renders `symfony, forms` in Filament because `toEmbeddedHtml()` reduces the column's state to one item per list entry *before* any item reaches `formatState()`, so the array `formatState()` itself coerces to JSON is always a single item's own value (a JSON column, say), never the column's whole list.
[`getStateItems()`](#getstateitems-is-the-cells-value-list) makes that same split, so the two coercions never compete: it formats each value on its own and joins the results, and the JSON arm only ever sees a value that is itself an array.
The partial loops the item list, which is why the badged case keeps one element per value while the plain case gets one.

A consumer wanting a *different* join reaches for [`state()`](has-cell-state.md#state-is-getstateusing-under-another-name), and **not** `formatStateUsing()`.
The two read as interchangeable escape hatches and are not: the item list calls `formatState()` **per value**, so `formatStateUsing(fn (array $state) => implode(' · ', $state))` is handed `'symfony'` and then `'forms'`, never the list, and its `array` hint fails on the first one.
`state(fn ($record) => implode(' · ', $record['tags']))` displaces the column's state *ahead of* the split instead, so `getStateItems()` sees one scalar and emits one item carrying the join the consumer wrote.

## Divergences

### The date family formats through `DateTimeImmutable`, so only English reads right

Filament parses with `Carbon::parse()` and prints with `translatedFormat()`, which is `date()`'s token set with month and day *names* translated.
This port normalises with a three-arm match, a `DateTimeInterface` through `DateTimeImmutable::createFromInterface()`, an `int` as `@<timestamp>` and a string through `new DateTimeImmutable($state)`, and prints with `format()`.
Carbon is not adopted: [principles §2](../../internals/principles.md) sends us to the platform mechanism, and the whole of what Carbon adds here is the name translation.

The consequence is bounded: output matches Carbon's for English, and for any other locale the month and day names stay English.
That is i18n's to close, and closing it does not change this API, only what `format()` is replaced with.
`since()` has the same shape one level up: it reads [`diff_for_humans()`](../support/helpers.md), a pure port of `CarbonInterval::forHumans()`'s single-unit cascade, whose words are English by construction.

A fourth arm is a throw rather than a coercion: a float, an array or an object that is not a `DateTimeInterface` names the column in a `LogicException` instead of being pushed through `new DateTimeImmutable((string) $state)`, which Carbon's `parse()` would attempt and fail at less legibly.
A malformed *string* still throws from `DateTimeImmutable` itself, exactly as `parse()` does.

### `markdown()` throws for the package it suggests, rather than falling through

`league/commonmark` is a `suggest` of `sylament/tables`, not a `require`: a [guarded optional integration](../../internals/dependencies.md#the-guarded-optionals-and-their-pins) like flysystem in support.
The guard is `class_exists(GithubFlavoredMarkdownConverter::class)` at the one place the converter is constructed, and its else-arm is a `LogicException` naming the package and the command that installs it.

The alternative, falling through to the unconverted text, would render a document's raw markdown into the cell, sanitized and therefore *plausible*, with nothing in any log.
A column that asked for markdown and silently got none is the failure this refuses.

`markdown()` and `commonMarkOptions()` / `commonMarkExtensions()` themselves are unguarded, since they only assign, so configuring a markdown column on an install without the package is legal right up to the render.
That is deliberate: the throw names the column, which a container-time failure could not.

The absent branch cannot be exercised by uninstalling, so [`CanFormatStateTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php) reads the guard site itself and [`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php) asserts the manifest keeps the package a `suggest`, so deleting the guard or promoting the dependency fails the suite.

### The CommonMark pair is declared on `CanFormatState`, not composed from a support trait

Filament's column and summarizer `CanFormatState` traits both `use` support's `CanConfigureCommonMark` for `commonMarkOptions()`, `getCommonMarkOptions()`, `commonMarkExtensions()` and `getCommonMarkExtensions()`.
The port declares the four members on each of its two copies, [the column's](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) and [the summarizer's](../../../packages/tables/src/Columns/Summarizers/Concerns/CanFormatState.php), where the converter that reads them runs, so support has no `CanConfigureCommonMark`.

### `numeric()` refuses a formatter failure rather than rendering `false`

`NumberFormatter::format()` and `NumberFormatter::formatCurrency()` return `string|false`, and `false` stringifies to `''`.
Filament goes through Laravel's `Number` helpers, which pass that straight out.
Here both `numeric()` and `money()` run their result through one private check that throws a `LogicException` naming the column, so a currency code ICU cannot resolve surfaces as an error rather than as an empty cell, which is indistinguishable from a null state.

### The expand toggles render only where there is something to expand

Filament renders its two `Show N more` / `Show N less` controls whenever `expandableLimitedList()` is set and the list overflows, even when the over-limit items were sliced away, which happens for every combination except `listWithLineBreaks()` *and* expandable.
Clicking them then reveals nothing.

The partial ties the two together instead: the toggles render exactly when the hidden `<li>`s exist, and the static `and N more` message renders otherwise.
The condition is the negation of the slice gate, so the invariant is structural: one expression decides both whether the values survive and whether a control that reveals them appears.

The controls are `<span role="button">` with no served `tabindex`, for the reason [the copy control is](#the-copy-target-is-the-content-element-and-the-badge-pill-when-there-is-one): the cell may sit inside a record `<a>`.
The [`limited-list` controller](../../../packages/tables/assets/src/controllers/limited_list_controller.ts) stamps the `tabindex` at connect, calls `preventDefault()` and `stopPropagation()` so a click does not follow the row's link, and keeps its state in an `expanded` Stimulus value, which a live morph replays, so an expanded cell stays expanded across a re-render.
Filament's Alpine `x-data` scope is per-render and loses it.
Its attributes are spread onto the cell root **after** `extraAttributes()`, as the editable partials spread theirs, so a consumer's own `data-controller` cannot silently unbind the toggles.

### `rowIndex()` refuses a cell with no row loop, rather than numbering it null

Filament's `rowIndex()` closure type-hints `stdClass $rowLoop` and reads `$rowLoop->iteration`, the object Livewire's Blade loop always supplies.
Here [`getRowLoop()`](column.md) is nullable, because a column can be rendered outside the table partial (a test, a card, a future layout), and a loop that carries no index is representable too.

So the closure takes `?stdClass` and throws a `LogicException` naming the column when the index is not an integer, rather than emitting `''` from a null cast.
A column numbering every row `0` is the failure this refuses, and it is one a render test would not catch: the cell renders, it is simply wrong.

The offset itself is Filament's verbatim: `getTableRecordsPerPage()` is consulted and the page multiplication runs **only** when it is numeric, so a table showing `all` records numbers from one on every page rather than multiplying by a string.

### The cell renders a Twig partial where Filament emits an embedded view

Filament's `TextColumn` implements `HasEmbeddedView` and builds its cell in `toEmbeddedHtml()`: raw PHP `ob_start()` output with the attribute bags concatenated inline.
Sylament declares a `$template` and renders through [`ViewComponent::render()`](../../../packages/support/src/Components/ViewComponent.php), the same way every other component in this port does; the contract itself is not ported, for the reasons recorded on [schemas' `Text`](../schemas/text.md).

The consequence worth naming is what happens to `toEmbeddedHtml()`'s **local-variable preamble**.
Filament computes `$state = $this->getState()`, `$state = $this->formatState($state)` and friends at the top of the method, and the markup below reads the locals.
A Twig template has no such preamble: it reads `component.x` per value, and anything it cannot get from a single getter call goes through `getExtraTemplateData()`, the house rule recorded in [`.claude/rules/php.md`](../../../.claude/rules/php.md).
[`getFormattedState()`](#getformattedstate-replaces-the-embedded-views-local-preamble) is that translation for the one value the preamble computed in two steps.

### The item element is never merged into the outer div

Filament's `toEmbeddedHtml()` has two single-element paths.
`canRenderOptimized()` sends a single plain value with no bullets, line breaks, icon, tooltip, copy control, weight, font family, line clamp, size, description, wrap or extra attributes to `toOptimizedHtml()`, which emits one `div.fi-ta-text.fi-ta-text-item`; its `hasBulleted()`, `hasListWithLineBreaks()` and `hasSize()` predicates exist only to feed that test.
Past it, a cell holding a single value with no descriptions, no bullets and no line clamp merges the item element's attributes onto the outer `div` ([`TextColumn.php:497-499`](../../../vendor/filament/tables/src/Columns/TextColumn.php)), falling back to the two-element shape otherwise.
The port always splits: `div.sy.sy-ta-text` wrapping one `span.sy-ta-text-item` per value, whatever the cell holds, so neither path nor the three predicates is ported.

It costs one span per cell.
It buys a single DOM shape, so a selector written against one cell holds for every cell; a single CSS path, so `.sy-ta-text` carries layout and `.sy-ta-text-item` carries type with no rule having to work in both roles; and a template with no branch, where the merged arm would need `$stateItemAttributes` merged into `$attributes` before either is rendered, which a Twig template would have to express as a second copy of the markup.

### A list's `<ul>` always sits inside the outer `div`

A cell emits `<ul>` whenever it has more than one item or is `bulleted()`, which is Filament's own rule, and each value is an `li.sy-ta-text-item` inside it.
Filament makes the `<ul>` the outer element itself when the cell has no descriptions and no over-limit message, and nests it inside a `div` otherwise; the port always nests it, so the two differ in the outer element's tag alone, `div` here and `div` or `ul` there, and the [one-shape rule](#the-item-element-is-never-merged-into-the-outer-div) holds for lists too.
A **single** unbulleted value renders `span.sy-ta-text-item` directly under the `div`.

The stylesheet follows Filament's rules with the `ul&` arm dropped: `.sy-ta-text-has-descriptions` and `.sy-ta-text-list-limited` make the `div` `flex flex-col` with Filament's badge/non-badge gap fork, and on a badged cell `& > ul` takes `flex gap-x-1.5`, `.sy-wrapped > ul` adds `flex-wrap gap-y-1` and `.sy-ta-text-has-line-breaks > ul` swaps to `flex-col gap-y-1`.
So a badge row is its own flex container, the outer `div` stacks only descriptions and the list-limit message around it, and `wrap()` is honoured on every combination.

`white-space` is a separate axis and follows Filament exactly: `.sy-wrapped` sets `whitespace-normal` on the cell, with `& .sy-badge` and `& .sy-ta-text-list-limited-message` pinned `whitespace-nowrap` so only the descriptions around them wrap.
Filament's compound `:not(.fi-ta-text-has-badges.fi-ta-text-has-line-breaks)` guard ports whole, so `whitespace-normal` never pushes a badge below its own bullet.

### A badged icon goes through the `Badge`'s own props, and loses `iconColor()` doing it

Filament builds the badge span by hand inside `toEmbeddedHtml()`, as the item's content attributes ([`TextColumn.php:439-472`](../../../vendor/filament/tables/src/Columns/TextColumn.php)), and encloses `iconBefore` / content / `iconAfter` within it, so the icon it renders for the plain case is the same markup it drops inside the pill, colour attributes and all.
The port renders badges through the real [`Badge`](../support/badge.md) component instead, so the badged branch passes `icon`, `iconSize` and `iconPosition` to its props and lets it place the icon beside its own label container.
Structure matches Filament exactly; one thing does not survive the handoff.

**`iconColor()` has no effect on a badged column.**
Support's `Badge` declares `icon`, `iconAlias`, `iconSize` and `iconPosition` but no icon *colour*, so no colour class ever reaches the icon it renders, although its [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css) carries the `.sy-icon.sy-color { text-color-500 }` rule that would paint one.
Closing this takes an `iconColor` prop on `Badge` feeding `colorAttributes()` into its `generateIconHtml()` call.
Without it a badged icon takes the badge's own icon colour, from `.sy-badge:not(.sy-color) .sy-icon` or the coloured badge's inherited `text-(--sy-text)`: reasonable output, not the configured one.
`iconColor()` on a **non**-badged column is unaffected and fully live.

One consequence is a selector Filament needs and this port does not.
Filament writes `& > .fi-icon, & > span:not(.fi-badge) > .fi-icon` because its item can hold a wrapper span that is *sometimes* the badge, so the icon rule has to exclude that case by name.
Here the plain icon is a direct child of `.sy-ta-text-item` and the badged icon is two levels down inside `.sy-badge`, so the child combinator in `& > .sy-icon` excludes it structurally and no `:not()` is needed.

The partial's locals are named `itemIcon`, `itemIconSize`, `itemSizeValue` and `iconPositionValue` rather than `icon`, `iconSize`, `size` and `iconPosition` only to keep the pass-through mutation-detectable.
A `{% props %}`-declared prop that carries a **default** is not filled from the calling template's context: `PropsNode` resets it to that default whenever `__context` holds the name, so an explicit `:name=` still wins, and every one of [`Badge`](../../../packages/support/templates/components/Badge/index.html.twig)'s props carries one, so the collision would not be load-bearing.
The rule is not general, though: a prop declared *without* a default is filled from the caller in the `<twig:X>…</twig:X>` block form (the self-closing form inherits no context at all, and throws instead), and the distinct names remove the question.

### The icon's gap is a margin, because the line clamp owns the item's `display`

Filament puts nothing between a text column's icon and its value but the whitespace its PHP-echo template prints: `$stateItemIconBeforeHtml`, the state and `$stateItemIconAfterHtml` sit on separate lines of [`toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), and HTML collapses that to one space.
Neither its CSS nor ours declares a gap there.
This port's partial controls whitespace on every tag, so it prints no space, and the gap has to come from CSS.

A margin is what the rest of the toolkit means by this.
`Button`, `Link` and `Badge` each space their icon from their label with `gap-1.5` on a flex or grid container, and `.sy-ta-text-item` is the one place that cannot: the unconditional `line-clamp-(--sy-line-clamp,none)` that makes [`lineClamp()`](../../../packages/support/src/Components/Concerns/HasLineClamp.php) work sets this element's `display` itself, `-webkit-box` with no clamp value and `flow-root` once one lands, so there is never a flex box here to hang a gap on, and taking one by force would break the clamp.
The partial stamps the icon `sy-ta-text-icon-before` or `sy-ta-text-icon-after` from the position it already computes, and `text-column.css` gives each the matching `me-1.5` / `ms-1.5`, the same 6px those container gaps produce, which survives both `display` values.

Both halves are pinned because either one failing alone is silent: the Integration case asserts the partial stamps exactly one position class per position, and [`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php) asserts `text-column.css` defines a rule for both.
A class with no rule, or a rule no class reaches, renders the icon flush against its value with nothing in the console.

### The item policy measures the dark surface against `gray-900`

[`ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) resolves its `dark:text` slot against `$gray[900]` where [Filament's](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent.php) uses `$gray[800]`; the light `text` slot's white surface is Filament's unchanged.
The surface argument is a claim about what is actually behind the text, and the shipped `sy-ta` chrome paints its container `bg-white dark:bg-gray-900` ([`table.css`](../../../packages/tables/assets/src/styles/components/table.css)), so `gray-800` would be measuring against a background this port does not render.
Copying the literal would silently understate the contrast ratio of every dark cell.

The same reasoning is why the surfaces are not schemas' [`TextComponent`](../schemas/text.md)'s either: that one sits on a different container and measures `$gray[50]` / `$gray[700]`.
Revisit if the table chrome's background changes, and re-run [`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php) when it does.

### `getFormattedState()` replaces the embedded view's local preamble

`getFormattedState()` has no Filament counterpart: it is `formatState(getState())` under a name, the translation [named above](#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) for the one value `toEmbeddedHtml()`'s preamble computed in two steps, and one of the [names with no Filament source](../translations.md#names-with-no-filament-source).

It formats the *whole* column state in one call, which is why it is not what [`getStateItems()`](#getstateitems-is-the-cells-value-list) calls: a cell can hold more than one value, so the item list reads `getState()` and `formatState()` directly, per item.
On a multi-value state it is therefore not the value the cell paints.
`formatState()` handed the column's array reaches the array-to-JSON arm, so a `['a', 'b']` state answers `'["a","b"]'` where the rendered cell reads `a, b`, because the join is `getStateItems()`'s, one pass per value.
So this is the column's *state* run through the pipeline once, not a preview of the cell: a caller wanting what the cell shows reads the item list.

### `formatStateUsing()` accepts a literal where Filament accepts only a closure

Filament types `formatStateUsing(?Closure $callback)` and then evaluates `$this->formatStateUsing ?? $state`, so the *null* case falls back to the state and a non-null case is always a closure.
Sylament types the parameter `mixed` and branches on null explicitly, which means `->formatStateUsing('fixed')` is legal and pins every cell to the literal.

This aligns the pair with `state()` / `getStateUsing()` on [`Column`](has-cell-state.md#state-is-getstateusing-under-another-name), which Filament itself types `mixed`: a literal-or-closure setter is the port's default shape, and `evaluate()` returns a non-closure untouched, so the widening costs nothing.
Every closure Filament accepts works unchanged; a literal is the only addition.

### `limit()` composes `slice()` rather than calling Symfony's `truncate()`

`AbstractString::truncate($length, $ellipsis)` counts the ellipsis *toward* `$length`: it slices to `$length` minus the ellipsis's length before appending it, so `truncate(30, '...')` returns 30 characters total.
Laravel's `Str::limit()`, which Filament calls and whose semantics this port preserves, appends the ellipsis *after* the slice, so `limit(30)` returns 30 characters **plus** the ellipsis.
Calling `truncate()` here would silently cost a migrating Filament user three characters per cell, so `truncateState()` composes `slice()` / `trimEnd()` / `append()` by hand instead, and [`CanFormatStateTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php) pins the difference.

A second, inherited delta rides along.
Laravel's `Str::limit()` truncates by *display width* (`mb_strwidth()`, where a fullwidth CJK character consumes two units of the limit), where Sylament's `u($state)->length()` / `slice()` count *grapheme clusters* (`grapheme_strlen()` / `grapheme_substr()`): one unit per user-perceived character regardless of display width, which is not a raw code-point count either.
A migrating Filament user with CJK cell content gets roughly twice as many characters into the same numeric `limit()` here as Laravel gave them.
The port is faithful to `Str::limit()`'s *ellipsis placement*, not its *width counting*; the two are independent behaviours.

### `formatState()` throws for a state it cannot stringify

Filament never explicitly casts state to a string: `formatState()` passes it untouched into `Str::limit()`, string concatenation, and eventually Blade's `{{ }}`, so an object with no `__toString()` reaching that far fails wherever PHP's implicit string coercion first runs, uncontrolled and with no column named in the error.
Sylament's pipeline casts once, explicitly (`(string) $state`, ahead of truncation and affixing), and PHPStan level max will not let that cast stand for a `mixed`-typed value without proof it is safe.
`formatState()` checks `is_scalar($state) || $state instanceof Stringable` immediately before the cast and throws a `LogicException` naming the column when neither holds, the same [fail-fast rule](../../../CLAUDE.md) applied to `color()`/`icon()`'s `true` arm below.
A scalar, a `Stringable` (including `Markup`), an array (already coerced to JSON above), a `HasLabel` (already unwrapped above) and a `BackedEnum` (unwrapped to its backing value immediately after) never reach the check.

That enum unwrap is Filament's, reached from the other end, and it is what keeps the throw narrow.
Filament's pipeline ends in Blade's `{{ }}` → `e()`, which turns a `BackedEnum` into its `->value` before escaping ([`helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php)), so an enum that implements none of support's state contracts still prints there.
Without the same unwrap here the guard would reach it first, and that is the ordinary shape of a Doctrine `enumType` column, because `HasLabel` is opt-in: a page rendering one would fatal.
What is left for the throw is a genuinely unstringable object, an entity, a DTO or a closure, which has no print of its own in either framework.

### `hasStateFormatting()` has nothing to guard

Filament's `hasStateFormatting()` reports whether any of the twelve formatting properties is configured (`formatStateUsing`, the character and word limits, the two affixes, the two HTML flags and the five family markers), so `formatState()` can return the raw state, or a `HasLabel`'s label, without running the pipeline.
The port has no such fast path: the branch it optimises here is a Twig template reading one getter, not a PHP method with a dozen inline steps, and a predicate over a property list invites forgetting a property when a setter is added.
If the pipeline ever grows expensive enough to matter, the memo belongs on `getFormattedState()`, one place with no property list to keep in sync.
The behavioural trace of the missing fast path is [`formatState()` always returning a string](#formatstate-always-returns-a-string-where-filament-passes-a-scalar-through).

### `weight()` and `fontFamily()` are column-level, where Filament's are per item

Filament's `HasWeight` and `HasFontFamily` take `getWeight(mixed $state = null)` / `getFontFamily(mixed $state = null)` and inject `['state' => $state]`, so `toEmbeddedHtml()`'s per-item closure resolves them once per value.
Support's ports [drop the `$state` argument](../support/has-weight.md#getweight-drops-the-state-argument) ([and for the font family](../support/has-font-family.md#getfontfamily-drops-the-state-parameter)), and the partial reads `getWeightClass()` / `getFontFamilyClass()` once per cell.

So a closure named `fn ($state) => …` still compiles against both, but with no explicit injection to satisfy the name, `evaluate()` falls through to [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](column.md)'s ambient `'state'` resolver, which binds `$this->getState()`, the **column's** own state, not an item `getStateItems()` is iterating.
On a badged multi-value column, `weight(fn ($state) => …)` receives the whole array `getState()` returns, once, never a single badge's value; [`TextColumnTest`](../../../packages/tables/tests/Unit/Columns/TextColumnTest.php)'s `'gives a weight closure the column state, not an item'` case pins it.
Reversing it means adding the `$state` parameter and the matching injection to both support traits, which touches every other consumer of `weight()` / `fontFamily()`, schemas' [`Text`](../schemas/text.md) included, and passing the item state from the partial.

### An explicit `true` colour or icon throws, where Filament lets it through and crashes

`color()` / `icon()` accept `bool` in their union, mirroring [`Filament\Tables\Columns\Concerns\HasColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php) and [`HasIcon`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php) exactly, but only `false` carries meaning, as the sentinel the getters test for.
Filament's own getters declare `string | array | null` / `string | BackedEnum | Htmlable | null` and check only `$color === false`, so an evaluated `true`, which the type accepts, falls through to `filled($color)`, which is `true` for a bare `true`, and the getter returns it: a `TypeError` at the return statement, because `true` is not a member of the declared return type.

Sylament's `getColor()` / `getIcon()` test `$color === true` / `$icon === true` ahead of the `false` check and throw a `LogicException` naming the column, `Color of column [%s] must be a palette name, a shade map, or false; true given.` and the icon equivalent, rather than reaching Filament's uncontrolled `TypeError` and rather than swallowing the mistake into `null`.
This is the project's [fail-fast rule](../../../CLAUDE.md): a caller who sets `true` meant something else, so the toolkit says so at the point the mistake was made, in a message naming the column.
Every `false` call site keeps its meaning; a `true` call site could never return successfully in Filament either.

### A `colors()` or `icons()` closure is handed the item state, where Filament's is handed the column's

Both maps admit a `Closure` as an entry's condition, and the port evaluates it with an explicit `['state' => $state]` injection carrying the value `getColor()` / `getIcon()` was called with.
Filament evaluates the same closure as `$column->evaluate($condition)`, with no injection at all, so `fn ($state) => …` falls through to [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](column.md), whose `'state'` arm is `$this->getState()`: the **column's** whole state, once, whatever value the getter is resolving for.

The evidence that this is a vendor defect rather than a design difference is inside the same `foreach`.
The map's literal arm is `$condition === $state`, comparing against the *item* state in both codebases, because that `$state` is the wrapper closure's own injected parameter.
So in Filament `colors(['success' => 'published'])` keys off the item and `colors(['success' => fn ($state) => $state === 'published'])` keys off the column: two arms of one map, written to read as alternatives, answering against different values.
The port makes both arms agree.

Visible where a cell holds more than one value: on a badged column each badge gets its own verdict here and one shared verdict there; on a collapsed cell the closure sees the joined string, which is [what `state` means on a collapsed item](#getstateitems-is-the-cells-value-list).
A single-valued cell, every cell in most tables, cannot tell the two apart, because the item state and the column state are the same value.

### `formatState()` always returns a string, where Filament passes a scalar through

The port casts once, explicitly (`(string) $state`), so `formatState(42)` is `'42'`, `formatState(1.5)` is `'1.5'` and `formatState(true)` is `'1'`.
Filament's returns the scalar untouched: with no formatting configured its `hasStateFormatting()` fast path returns `$state` before any branch runs, and even configured it never casts, so `Str::limit()` and the affix concatenation coerce where they happen to run, and the state reaches Blade as whatever it was.

The cast is [the stringify guard's other half](#formatstate-throws-for-a-state-it-cannot-stringify) and the reason `getStateItems()` can type its `content` entries `string|Markup|null` at all.
It costs nothing at the render, since Twig prints `42` and `'42'` identically, so the difference is only visible to a **caller** that reads `formatState()` or `getFormattedState()` back in PHP and compares with `===`.
The declared return type stays Filament's `mixed` rather than being narrowed to match: `formatStateUsing` is `mixed`, so PHPStan cannot see through `evaluate()`'s generic, which is what [`formatStateForItem()`](#formatstateforitem-throws-for-a-format-result-the-pipeline-cannot-produce) exists to bridge.

### The collapsed join flattens a `Markup` item, where Filament keeps it raw

`getStateItems()`' collapse branch maps `(string) $this->formatStateForItem($value)` over the values and joins the results, so an item that formatted to [`Markup`](../translations.md#idiom) contributes its raw HTML to a **plain** string, and the template escapes plain content, so that item's markup renders as visible tags.
Filament joins through its `$formatState` closure, whose `e()` call [passes an `Htmlable` through with `toHtml()`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php) and escapes everything else, then echoes the joined result raw, so the markup survives the collapse there.

The direction is the safe one, and that is why it is not treated as a bug: the port over-escapes rather than emitting unreviewed HTML, and the per-item branch is unaffected, since a badged or single-valued cell keeps `Markup` as `Markup` in `content` and renders it raw.
What is inconsistent is that the *same* format callback returns raw markup on a badged column and escaped text on the collapsed one beside it.
Closing it means deciding escaping per item inside the join and wrapping the result in `Markup`, precisely what [`affixState()`](#a-markup-state-skips-truncation-and-affixing-escapes-whichever-side-is-not-already-markup) does for one value.

### `getSizeClass()` is Sylament-only, and the partial resolves the size twice

Filament reads `$size = $this->getSize($stateItem)` into a local at the top of its per-item closure and builds the class from that one value.
The port has no local to read from, so `getSize()` answers the enum-or-string union and `getSizeClass()` turns it into `sy-size-<value>` or passes a custom string through, one of the Sylament-only [`*Class()` getters](../translations.md#names-with-no-filament-source) and the only one on this class.

The cost is that the partial calls **both**: `getSize(item.state)` for the value the `Badge` prop and the icon-size choice need, and `getSizeClass(item.state)` for the class, which resolves the size a second time.
So `size(fn ($state) => …)` runs twice per non-badge item where Filament's runs once, invisible for a pure closure and observable for one that counts calls or reads something that moves.

That second call is also what makes `getSizeClass()`'s docblock read as though the template contradicts it.
It says templates never have to type-sniff the enum-vs-string union, and the line above the call does exactly that (`itemSize.value ?? itemSize`), for a different consumer, the raw *value*, which no getter hands over.
Both go away together: a `getExtraTemplateData()` entry resolving the size once per item and passing value and class down would close the double evaluation and leave the docblock true of every line near it, but the hook is per **component**, not per item, and the item loop is the template's.

### `formatStateForItem()` throws for a format result the pipeline cannot produce

`formatStateForItem()` is the private helper every value in `getStateItems()` goes through: it re-tests what `formatState()` returned and throws a `LogicException` naming the column when that is not `string`, `Markup` or `null`.
The throwing arm is **unreachable** against this class as it stands, because the pipeline casts before it returns, so those three are the only shapes it produces.

It exists because `formatState()`'s declared return is Filament's `mixed` and PHPStan cannot narrow through it, since `formatStateUsing` is itself `mixed` and `evaluate()`'s generic binds to nothing useful, while `getStateItems()` promises `content: string|Markup|null` in its own array shape.
The choice at that point is a silent cast, an [inline suppression the codebase does not allow](../../../CLAUDE.md), or a runtime check; the check is the same [fail-fast rule](../../../CLAUDE.md) as the [stringify guard](#formatstate-throws-for-a-state-it-cannot-stringify) and the [`true` colour arm](#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes), and unlike those two it is proof rather than a policy about user input.
It becomes reachable when a subclass overrides `formatState()`, which is public API, and then it names the column instead of failing inside Twig.

### `alignment()` reaches a badge row through the list, not through `text-align`

`alignment()` puts `sy-align-center` / `sy-align-end` / `sy-align-justify` on the cell's outer `div.sy-ta-text`, and [`text-column.css`](../../../packages/tables/assets/src/styles/components/text-column.css) answers each with `text-center` / `text-end` / `text-justify`.
That moves inline content and does nothing to flex items, so each `sy-align-*` arm pairs its `text-*` with a `justify-*` under `& > ul` (`justify-center`, `justify-end`, `justify-between`), which reaches the badges as flex children of the [`<ul>`](#a-lists-ul-always-sits-inside-the-outer-div).
It is Filament's own pairing under `ul&, & ul` with one selector fewer, because this port's root is always the `div`.

A single-value badge is governed by `text-align` alone, and correctly so: with one item there is no `<ul>` and no flex container, and the badge is inline content the alignment moves directly.
