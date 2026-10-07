# TextColumn (tables)

[`Sylament\Tables\Columns\TextColumn`](../../../packages/tables/src/Columns/TextColumn.php) — the first concrete column of the catalog: a [`Column`](column.md) that renders its cell state as text, plus the formatting pipeline that sits between the raw state and the template.
It ports Filament's [`Filament\Tables\Columns\TextColumn`](../../../vendor/filament/tables/src/Columns/TextColumn.php) and the five `Columns\Concerns\` traits it adds today — [`CanFormatState`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php), [`HasColor`](../../../packages/tables/src/Columns/Concerns/HasColor.php), [`HasDescription`](../../../packages/tables/src/Columns/Concerns/HasDescription.php), [`HasIcon`](../../../packages/tables/src/Columns/Concerns/HasIcon.php) and [`HasIconColor`](../../../packages/tables/src/Columns/Concerns/HasIconColor.php) — folded into the surface table below rather than earning rows upstairs, per [one row per file](../README.md#one-row-per-file-one-file-per-thing).
Everything [`Column`](column.md) carries — the name, the label, the record binding, the state read, the closure injections — is tracked there, not repeated here.

It is a thin subclass on purpose.
Filament's `TextColumn` is ~700 lines of badges, icons, colours, list flattening, date/money/number formatting and the embedded-view render; this is that surface minus the `iso*` family and the deprecated arms, over a state pipeline and a template path.
The gaps are the surface table below.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) — the class, its composed traits and `toEmbeddedHtml()`
- [`tables/src/Columns/Concerns/CanFormatState.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) — the formatting pipeline: `formatStateUsing`, the array-to-JSON coercion, the `HasLabel` unwrap, `limit()` / `words()`, `prefix()` / `suffix()` and their escaping, `html()` / `markdown()`, and the date, money and numeric formatters all port here; the `iso*` family does not yet
- [`tables/src/Columns/Concerns/HasColor.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php) — the colour setter, the `colors()` map and the state-contract fallback
- [`tables/src/Columns/Concerns/HasIcon.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php) — the icon setter, the `icons()` map, the position enum and the state-contract fallback
- [`tables/src/Columns/Concerns/HasIconColor.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasIconColor.php) — the icon-colour setter, evaluated with no map and no state-contract fallback
- [`tables/src/Columns/Concerns/HasDescription.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasDescription.php) — the above/below secondary line

## Surface

Every point of this component.
_Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in [Details](#details), deleted once the row is **done**; **diverges** → the permanent note in [Divergences](#divergences); **semantics** → the permanent note in [Semantics](#semantics).
Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`formatStateUsing()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState::formatStateUsing()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#formatstateusing-accepts-a-literal-where-filament-accepts-only-a-closure) |
| [`formatState(mixed $state)` — the `state` injection](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState::formatState()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#formatstate-throws-for-a-state-it-cannot-stringify) · [diverges](#formatstate-always-returns-a-string-where-filament-passes-a-scalar-through) |
| [`getFormattedState()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | — (Sylament-only) | done | — | [diverges](#getformattedstate-replaces-the-embedded-views-local-preamble) |
| [`getStateItems()` — the cell's value list](../../../packages/tables/src/Columns/TextColumn.php) | — (Sylament-only) | done | — | [semantics](#getstateitems-is-the-cells-value-list) · [diverges](#the-collapsed-join-flattens-a-markup-item-where-filament-keeps-it-raw) · [diverges](#formatstateforitem-throws-for-a-format-result-the-pipeline-cannot-produce) |
| [`$template`](../../../packages/tables/src/Columns/TextColumn.php) + [the partial it names](../../../packages/tables/templates/partials/TextColumn/index.html.twig) | [`TextColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [diverges](#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) · [diverges](#the-item-element-is-never-merged-into-the-outer-div) · [diverges](#the-icons-gap-is-a-margin-because-the-line-clamp-owns-the-items-display) |
| `hasStateFormatting()` — the raw-state fast path | [`CanFormatState::hasStateFormatting()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | skipped | — | [diverges](#hasstateformatting-has-nothing-to-guard) |
| [`limit()` / `words()` — character and word truncation](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#limit-composes-slice-rather-than-calling-symfonys-truncate) |
| [`prefix()` / `suffix()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [semantics](#a-markup-state-skips-truncation-and-affixing-escapes-whichever-side-is-not-already-markup) |
| [`html()` / `markdown()` / `commonMarkOptions()` / `commonMarkExtensions()` — the sanitizer pass](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php), [`Support\Concerns\CanConfigureCommonMark`](../../../vendor/filament/support/src/Concerns/CanConfigureCommonMark.php) | done | — | [semantics](#html-sanitizes-in-php-and-markdown-converts-before-it-does) · [diverges](#markdown-throws-for-the-package-it-suggests-rather-than-falling-through) |
| [`date()` / `dateTime()` / `time()` / `since()`, the four `*Tooltip()` twins and `timezone()` / `getTimezone()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [semantics](#one-timezone-chain-answers-for-every-date-arm) · [diverges](#the-date-family-formats-through-datetimeimmutable-so-only-english-reads-right) |
| `isoDate()` / `isoDateTime()` / `isoTime()` and their three tooltips | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | blocked | i18n — the moment.js locale presets these take have no answer without ICU locale data | the three defaults they read are already carried, unread, by [`HasDefaultDataFormattingSettings`](../support/has-default-data-formatting-settings.md#the-three-iso-defaults-ship-with-no-reader) |
| [`money()` / `numeric()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [semantics](#the-money-and-numeric-arms-are-numberformatter-with-two-sentinels) · [diverges](#numeric-refuses-a-formatter-failure-rather-than-rendering-false) |
| [`badge()`](../../../packages/tables/src/Columns/TextColumn.php) — the badge shell | [`TextColumn::badge()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), [`View\Components\BadgeComponent`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | done | — | [diverges](#a-badged-icon-goes-through-the-badges-own-props-and-loses-iconcolor-doing-it) · [diverges](#the-list-wrapper-appeared-with-the-list-arms-and-closed-a-css-divergence-with-it) · [diverges](#alignment-reaches-a-badge-row-through-the-list-not-through-text-align) |
| [`color()`](../../../packages/tables/src/Columns/Concerns/HasColor.php) / [`icon()` / `iconPosition()`](../../../packages/tables/src/Columns/Concerns/HasIcon.php) / [`iconColor()`](../../../packages/tables/src/Columns/Concerns/HasIconColor.php) | [`Columns\Concerns\HasColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php), [`HasIcon`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php), [`HasIconColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasIconColor.php) | done | — | [semantics](#the-colour-and-icon-maps-resolve-last-match-wins-and-false-suppresses-the-state-contract-fallback) · [semantics](#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) · [diverges](#a-badged-icon-goes-through-the-badges-own-props-and-loses-iconcolor-doing-it) · [diverges](#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes) · [diverges](#a-colors-or-icons-closure-is-handed-the-item-state-where-filaments-is-handed-the-columns) |
| [`description()` — the secondary line above or below](../../../packages/tables/src/Columns/Concerns/HasDescription.php) | [`Columns\Concerns\HasDescription`](../../../vendor/filament/tables/src/Columns/Concerns/HasDescription.php) | done | — | [semantics](#a-description-position-is-compared-never-evaluated) |
| [`size()`](../../../packages/tables/src/Columns/TextColumn.php) / [`weight()`](../../../packages/support/src/Components/Concerns/HasWeight.php) / [`fontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) / [`lineClamp()`](../../../packages/support/src/Components/Concerns/HasLineClamp.php) | [`TextColumn::size()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), [`HasWeight`](../../../vendor/filament/support/src/Concerns/HasWeight.php), [`HasFontFamily`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php), [`HasLineClamp`](../../../vendor/filament/support/src/Concerns/HasLineClamp.php) | done | — | [diverges](#weight-and-fontfamily-are-column-level-where-filaments-are-per-item) · [diverges](#getsizeclass-is-sylament-only-and-the-partial-resolves-the-size-twice) |
| [`wrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | [diverges](#the-list-wrapper-appeared-with-the-list-arms-and-closed-a-css-divergence-with-it) |
| [`copyable()` / `copyableState()` / `copyMessage()` / `copyMessageDuration()`](../../../packages/support/src/Components/Concerns/CanBeCopied.php) + [the control the partial renders](../../../packages/tables/templates/partials/TextColumn/index.html.twig) | [`Support\Concerns\CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php), [`TextColumn.php`'s `x-on:click.prevent.stop`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [copyable.md](../support/copyable.md) · [semantics](#the-copy-target-is-the-content-element-and-the-badge-pill-when-there-is-one) |
| [Lists — `bulleted()`, `listWithLineBreaks()`, `limitList()`, `expandableLimitedList()`, `getStateOverListLimitCount()`](../../../packages/tables/src/Columns/TextColumn.php) + [`limited_list_controller.ts`](../../../packages/tables/assets/src/controllers/limited_list_controller.ts) | [`TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [semantics](#getstateitems-is-the-cells-value-list) · [diverges](#the-expand-toggles-render-only-where-there-is-something-to-expand) |
| [`rowIndex()`](../../../packages/tables/src/Columns/TextColumn.php) | [`TextColumn::rowIndex()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [column.md](column.md) — the `$rowLoop` object it reads · [diverges](#rowindex-refuses-a-cell-with-no-row-loop-rather-than-numbering-it-null) |
| `isoDate()`-adjacent deprecations — `BadgeColumn`, `BooleanColumn`, `enum()`, `getStateUsing()`'s deprecated twins | [`TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | skipped | — | Filament ships these for migration off v2/v3 APIs that never existed here |
| _rest not audited yet_ | | | | |

The state-pipeline rows are covered by [`CanFormatStateTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php) — the array-to-JSON coercion, the `HasLabel` unwrap and the [backed-enum unwrap](#formatstate-throws-for-a-state-it-cannot-stringify) beneath it, the markup flag being re-read off an unwrapped `Markup` label (a second assignment that looks redundant and is what keeps that label out of the truncation), character and word truncation (including the ellipsis-after-length and trailing-whitespace-trim cases), the two **zero** limits as no-ops — the character one because `limit(0)` would otherwise slice the state away and return the ellipsis alone, the word one asserted with an error handler installed, because `words(0)` interpolates `{1,0}` into the quantifier and the failure is the raise rather than the returned string — the pipeline's order at both places a reader could get it wrong (truncation before affixing, so the limit measures the state alone; the character limit before the word limit), prefix/suffix concatenation, and the state/affix markup-escaping matrix — plus [`TextColumnTest`](../../../packages/tables/tests/Unit/Columns/TextColumnTest.php)'s pre-pipeline cases: unformatted state passing through, a format closure receiving both `$state` and `$record`, a literal `formatStateUsing('fixed')` displacing the state, and the collapse boundary itself, pinned with a single-entry array of a non-string so that `state` is still the `int` a colour closure would be handed — which is what a `count($values) >= 1` off-by-one would turn into the formatted string `content` already holds.
Each presentation concern keeps a unit file of its own beside those — [`HasColorTest`](../../../packages/tables/tests/Unit/Columns/Concerns/HasColorTest.php), [`HasIconTest`](../../../packages/tables/tests/Unit/Columns/Concerns/HasIconTest.php) (where `hasIcon()` is asserted on all three arms, the `false` sentinel included, so an unconditional `return false` no longer passes), [`HasDescriptionTest`](../../../packages/tables/tests/Unit/Columns/Concerns/HasDescriptionTest.php) and [`HasIconColorTest`](../../../packages/tables/tests/Unit/Columns/Concerns/HasIconColorTest.php), the last covering the literal, the state-injected closure, an inline shade map, and the deliberate **absence** of the state-contract fallback `color()` and `icon()` both have.
The `$template` row is covered by the Integration [`TextColumnTest`](../../../packages/tables/tests/Integration/Columns/TextColumnTest.php), which renders columns inside a real table and reads the cells back through a `DomCrawler` selecting the full chain (`td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-text`), so the element carrying the state is the asserted one: a dotted path and a `formatStateUsing()` closure landing in the cell, an iterable state joining into one, a null state rendering the outer element empty rather than dropping it, and — reaching one level deeper, into the `.sy-ta-text-item` element the partial always emits, whose tag follows the [list wrapper](#the-list-wrapper-appeared-with-the-list-arms-and-closed-a-css-divergence-with-it) (`span` for a single unbulleted value, `li` inside the `<ul>` otherwise, so a **multi-value badged** cell is `div.sy-ta-text > ul > li.sy-ta-text-item > span.sy-badge` where a single-value one is `div.sy-ta-text > span.sy-ta-text-item > span.sy-badge`) — a badge per value carrying its colour classes, the descriptions bracketing the item above and below, the size/weight/font-family classes with the line-clamp variable, the badged icon landing **inside** the badge rather than beside it, a **non**-badged `->color()` reaching the item policy and a `->iconColor()` reaching the icon policy, the column's alignment class reaching the outer element, and a blank description dropping both its `<p>` and the layout class it would otherwise flip — in the plain-string form and again as a whitespace-only [`Markup`](../renames.md#idiom), which is a different mechanism rather than the same case twice, since `Markup` is `Countable` and only [`filled()`](../support/helpers.md)'s text-semantics carve-out keeps a count-first reading from calling it filled.

The two colour cases are the only render-level reach into the policies, and the badged case cannot stand in for either: the badge branch [skips `ItemComponent` outright](#a-badged-icon-goes-through-the-badges-own-props-and-loses-iconcolor-doing-it), and `colorAttributes()` returns early on a blank colour, so with only a badged `->color('success')` rendered, neither of the partial's two policy class-string literals — nor the container lookup each drives — ran in any test.
The icon one carries the sharper edge: its class-string sits a namespace level deeper than the item's, so a bundle glob that does not reach that depth is a container error at render rather than a quietly uncoloured icon, which is what that case now fails with.

The icon-position case reads the item's inner HTML rather than its element children, and that is deliberate: the content is a *text node*, so `children()` holds the one `<svg>` whichever side it renders on and an assertion over it passes for both orderings.
It asserts `before` and `after` against the same column, each pinned at both ends of the markup — deleting either arm of the template's position check fails exactly one of them.

The two colour policies the partial names have their rows in the [package README](README.md#supporting-surface) rather than here, per [one row per file](../README.md#one-row-per-file-one-file-per-thing); both link back to the notes below.
What pins them is [`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php), the same adversarial-palette sweep schemas runs — every emitted shade class has a rule in support's `utilities.css`, the slot variables those classes set are exactly the ones `text-column.css` reads, and the icon policy emits the two carriers and nothing else.
It also pins the [`gray` asymmetry](#gray-is-a-colour-on-the-item-and-the-absence-of-one-on-the-icon) in **both** directions, that being the one decision here a later reader is most likely to "correct": the item must emit classes for `gray` and the icon must emit none, so re-adding `HasDefaultGrayColor` to `ItemComponent` — which nothing else in the suite notices — now fails.

## Details

Specifics for rows that are not finished — what is already in, what is left, and what the next step is.
Delete a subsection when its row flips to **done**; the table row and any divergence it earned stay behind.

### The `iso*` family waits on i18n, and only on that

`isoDate()`, `isoDateTime()`, `isoTime()` and their three tooltips take moment.js locale presets — `L`, `LLL`, `LT` — which name a *locale's* preferred date shape rather than a token sequence.
`DateTimeImmutable::format()` has no such concept: handing it `'L'` emits the leap-year flag.
Answering them means ICU locale data and a locale to read it under, which is i18n's to supply, so the six setters ship as one **blocked** row rather than as a partial port that formats wrongly.
Nothing else blocks them — the three defaults they read are already on [`HasDefaultDataFormattingSettings`](../support/has-default-data-formatting-settings.md), carried unread for exactly this.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### A `Markup` state skips truncation, and affixing escapes whichever side is not already `Markup`

`formatState()` captures `$isMarkup` before deciding whether to call `truncateState()`, and skips it whenever the state is already `Markup` — either because it arrived that way, or because the `HasLabel` unwrap returned one.
That check runs on the state alone, before `affixState()` ever sees a prefix or suffix, so a `Markup` *affix* against a still-plain state does not suppress it: `limit()` / `words()` still truncate the state first, and only then does the affix step decide whether to escape or promote.
Cutting into already-escaped markup mid-tag would corrupt it; cutting a plain string that only later picks up an escaped affix cannot.
Pinned by [`CanFormatStateTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php)'s `'skips truncation for a markup state'` case.

`affixState()` then holds one invariant across both directions: the returned value never mixes raw and unescaped text, so whichever of the state or its affix is *not* already `Markup` gets escaped, and the result is promoted to `Markup` either way.
A `Markup` prefix or suffix against a plain state escapes the state and trusts the affix raw; a plain prefix or suffix concatenated onto an already-`Markup` state is escaped before it is appended, trusting the state raw instead.
Pinned by the same test file's `'escapes plain state when an affix is markup and returns markup'` and `'escapes a plain affix when the state is markup'` cases.

### The colour and icon maps resolve last match wins, and `false` suppresses the state-contract fallback

`colors()` and `icons()` walk every entry of the given map on every call — there is no `break` — so the last entry that matches wins, not the first.
That includes the bare, numeric-keyed default: `['gray', 'success' => 'published']` does not read as *gray unless published*; the default applies unconditionally at its position in the iteration and is only left standing if nothing declared after it also matches the state.
Reordering the array reorders the result — [`HasColorTest`](../../../packages/tables/tests/Unit/Columns/Concerns/HasColorTest.php) pins the case where two keyed entries match the same state and the second wins.

`getColor()` and `getIcon()` add a second rule at the state-contract fallback: an evaluated `false` — whether from a literal `->color(false)` / `->icon(false)`, a closure that returns `false`, or a `colors()` / `icons()` map that resolves to `false` — returns `null` immediately and never reaches the `$state instanceof ColorContract` / `IconContract` check below it.
An unset colour or icon, the property's `null` default, *does* reach that check.
So `false` does not mean *no colour*, it means *no colour, and do not ask the state either* — where leaving the setter uncalled defers to the state's own `getColor()` / `getIcon()`.
The other boolean, `true`, takes neither path: it throws — see [Divergences](#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes).

### `gray` is a colour on the item and the absence of one on the icon

The two policies the partial names disagree about `gray` on purpose, and the asymmetry is Filament's.
[`ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) implements `HasColor` **only**, so `->color('gray')` emits the full class set — `sy-color`, `sy-color-gray` and the resolved shade classes — and [`text-column.css`](../../../packages/tables/assets/src/styles/components/text-column.css)'s `.sy-color-gray` branch, declared after the `.sy-color` one and matching at the same specificity, paints the item `gray-500` / `dark:gray-400`.
Gray is therefore a *visible* choice on a cell, distinct from leaving the colour unset (which leaves the base `gray-950` / `dark:white`).
[`IconComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent/IconComponent.php) does implement `HasDefaultGrayColor`, so `->iconColor('gray')` emits nothing at all and the icon's own base grey stands.
Adding the contract to `ItemComponent` — the obvious symmetry, and what schemas' [`TextComponent`](../schemas/README.md#supporting-surface) does — would make `->color('gray')` a silent no-op and leave the `.sy-color-gray` rule dead.

The icon's shade comes from the stylesheet rather than from its policy, which is what an empty `getColorMap()` [means](../support/color-policies.md#an-empty-map-is-a-real-answer-not-a-missing-one): the two carrier classes swap the palette and `text-column.css` reads `text-color-500` off it.
Writing the icon rule the way the item's is written — `text-(--sy-text)` — would compile, ship, and paint nothing, because no class an empty map emits ever sets that variable.
`PolicyShadeGridTest` pins the emitted set exactly so a slot added later cannot land without the rule moving with it.

Those stylesheet shades are Filament's own, copied from its `columns/text.css` and `badge.css` — `text-color-500` on an `iconColor()`-coloured item icon, `text-gray-400 dark:text-gray-500` on an uncoloured one, and the same gray pair on a gray badge's icon.
Against the surface each sits on — the table chrome's `bg-white dark:bg-gray-900` on `.sy-ta-ctn` for the item icons, the gray badge's own `bg-gray-50` for its icon — five pairings fall under the 3.0:1 a non-text element owes, all in light mode: the coloured item icon on `primary`, `success` and `warning`, the uncoloured item icon, and the gray badge icon.
They stay as Filament ships them, because raising them is a sweep of both packages' stylesheets rather than anything this column's surface can reach, and no policy ever touches them.
[`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php) pins the failing list exactly, over every shipped palette, so it goes red when a shade is raised and when a palette lands that fails a pairing passing today.
A consumer palette is outside that net: a theme that moves `primary` or `warning` changes which pairings fail.

### A `description()` position is compared, never evaluated

`description($text, position: $position)` sorts its argument with `$position === 'above'` — a raw identity test, taken before anything is evaluated — so a **closure** position is compared against a string, never matches, and the description lands *below*.
`description('1997', position: fn (): string => 'above')` therefore renders in the opposite place from the one it names, with nothing said about it.

The signature is what makes the obvious reading wrong: `string|Closure|null $position` declares a closure legal, and every other closure-typed argument on a column is evaluated somewhere.
This one is Filament's, parameter type and comparison both ([`Columns\Concerns\HasDescription`](../../../vendor/filament/tables/src/Columns/Concerns/HasDescription.php)), and it is ported as written rather than repaired, because repairing it is not local: the position picks which of two *properties* the description is stored in, at configuration time, where a closure has no record and no state to answer against — so evaluating it here would either resolve it too early or force the pair of properties to collapse into one that `getDescriptionAbove()` / `getDescriptionBelow()` then have to re-sort on every read.
A caller wanting a conditional position writes the condition around two calls instead.

### One timezone chain answers for every date arm

`getTimezone()` is `evaluate($this->timezone) ?? date_default_timezone_get()`, and every date arm reads the same chain: the per-call `$timezone` argument first, then the column's `timezone()`, then the process default.
Filament forks — `isDateTime()` reads `FilamentTimezone::get()` while everything else reads `config('app.timezone')`, and `time()` sets `isTime` but never `isDateTime`, so it lands on the config branch while `dateTime()` lands on the facade one.
There is no `TimezoneManager` here to fork on, so the fork collapses; the forms `DateTimePicker` already reads the same process default, which is what makes one chain the consistent answer rather than merely the shorter one.

The five family markers are set the way Filament sets them, asymmetries included: `dateTime()` sets `isDateTime` and then delegates to `date()`, which sets `isDate` as well, so a `dateTime()` column reports true for both.
Reading `isDate()` as *"and not a datetime"* is the mistake the shape invites.

### The money and numeric arms are `NumberFormatter` with two sentinels

`money()` and `numeric()` share three guards before any formatting: a blank state returns `null`, a non-numeric state is passed through unchanged, and the locale is `$locale ?? $table->getDefaultNumberLocale() ?? \Locale::getDefault()` with a `BackedEnum` unwrapped at either end.
Symfony's `Request::setLocale()` calls `\Locale::setDefault()`, so that tail *is* the request locale under a request — the translation of Filament's `config('app.locale')`.

Two arguments are sentinels rather than plain defaults, and both are easy to misread.
`money()`'s `$divideBy` defaults to **`0`**, and the division runs only when the evaluated value is truthy — so the default divides by nothing rather than by zero.
And `numeric()`'s two separators default to [`ArgumentValue::Default`](../support/README.md), a pure enum no caller can forge: passing *either* separator — `null` included — routes the whole call to `number_format()`, while leaving both untouched keeps the locale-aware `NumberFormatter`.
A boolean flag could not express that, because `null` is a legal separator value and would be indistinguishable from *not passed*.

Precision follows Laravel's `Number::format()`: `maxDecimalPlaces` sets `MAX_FRACTION_DIGITS` and wins outright over `decimalPlaces`, which otherwise sets `FRACTION_DIGITS`; with neither, the formatter's locale default applies.

### `html()` sanitizes in PHP, and `markdown()` converts before it does

The fork sits in `formatState()` between the `HasLabel` / `BackedEnum` unwrap and the truncation gate, which is exactly where Filament's sits.
When `isHtml()` — and `isHtml()` is true whenever `isMarkdown()` is, Filament's own definition — the state is converted from markdown if asked, then sanitized, then flagged `$isMarkup`.
That flag is not new: the pipeline already reads it as *skip truncation* (cutting into escaped markup mid-tag would corrupt it) and as *escape the plain side* when an affix joins, so `html()` inherits both behaviours without a line of its own.

The sanitizer runs **last**, always.
A CommonMark converter passes raw HTML through by design — that is what `html_input` configures — so sanitizing before conversion would guard nothing, and it is the only guard standing in front of the `Markup` the template renders raw.
It arrives through a `#[Required]` setter, so a column built with `new` rather than through the container has none and `html()` throws a `LogicException` naming the fix rather than emitting unsanitized markup.

Symfony's `default` sanitizer is what a consumer gets unless it configures otherwise, and it strips relative `href`/`src`, `class` and `style`.
A consumer that wants Filament's allowances configures `framework.html_sanitizer.sanitizers.default` — this port names no sanitizer of its own, so one application-level setting governs every column.

### The copy target is the content element, and the badge pill when there is one

`copyable()` is [support's `CanBeCopied`](../support/can-be-copied.md) and the markup is [support's `clipboard` controller](../support/copyable.md); what this column decides is *which* element carries them.
On a plain item it is a `span.sy-copyable` wrapped around the content alone — not the whole item, so the focus ring hugs the text, and not a `<button>`, because the cell may render inside a record `<a>` where interactive content is invalid.
On a badged item the attributes are spread onto the `Badge` component instead, so the pill *is* the control, which is what Filament does too.

The copied text is `getCopyableState($item)` falling back to the item's **formatted** content — so a `money()` column copies `$1,234.50` rather than `1234.5` unless `copyableState()` says otherwise.
The tooltip rides the same element when both are set, as one `title`.

### `getStateItems()` is the cell's value list

`getStateItems()` is `getFormattedState()`'s per-item successor.
A non-array `Traversable` state — a Doctrine `Collection`, an `ArrayIterator` — is materialized with `iterator_to_array()` first, the same unwrap Filament's own `toEmbeddedHtml()` runs before its `Arr::wrap()` (`$state instanceof Collection`, `TextColumn.php:281-283`).
[`wrap()`](../support/helpers.md) itself only spreads arrays — it leaves anything else, Traversable included, as one item — so this unwrap has to run first; only then does `wrap($state)` turn a scalar into one item and leave an array (native, or just materialized) as many.

Three steps then run, in Filament's order.
The **slice** drops everything past `limitList()`'s limit — unless the column both breaks its list onto lines and is `expandableLimitedList()`, the one shape whose extra values have to reach the DOM for the client to reveal ([divergence](#the-expand-toggles-render-only-where-there-is-something-to-expand)).
`getStateOverListLimitCount()` counts what the limit excludes, off the *unsliced* list, and is what the partial's message reads; it is `ImageColumn`'s `getStateOverLimitCount()` under a name that says which limit it means.
The **collapse** then joins what survives ([why that is the default](#the-collapsed-join-is-filaments-default-not-a-simplification)) — every value formatted individually and joined with `', '` into a *single* item — unless the column is badged, breaks onto lines (`bulleted()` forces that, `listWithLineBreaks()` asks for it), or is already single-valued.
Each surviving value is finally paired with its formatted content.

One subtlety in that gate: it counts the values **after** the slice, where Filament counts before it.
The two differ only when a limit cuts a multi-value list down to one, and there the port emits that value formatted on its own while Filament emits it through an identity join — the same text either way, but the port's `state` stays the raw value, so a `colors()` map still matches it.

The collapse is why each entry carries `state` and `content` separately rather than one value doing both jobs.
In the per-item branches, `state` is the **raw** value — what `getColor()` / `getIcon()` / `getSize()` would receive if a caller passed it directly — and `content` is `formatState($value)`'s result; [`TextColumnTest`](../../../packages/tables/tests/Unit/Columns/TextColumnTest.php)'s `'binds the item state to a per item colour closure when badged'` case reads `$items[$i]['state']` back into `getColor()` rather than assuming it equals `content`, and the two only coincide here because a plain string formats to itself.
In the collapsed branch there is no raw per-item value left — collapsing is itself a formatting step — so `state` and `content` are both the same already-joined, already-formatted string.
The consequence: a `colors()` / `icons()` map keyed on the column's original per-value identifiers (`colors(['success' => 'symfony'])`) matches those identifiers only while the column is badged (or single-valued); once a multi-value cell collapses, its one item's `state` is the joined string (`"symfony, forms"`), which cannot match any per-value key in the map.
Badging a multi-value column is what turns per-value colouring back on — not a bug, the shape the state-item model was built to expose.

This note and [`getFormattedState()`](#getformattedstate-replaces-the-embedded-views-local-preamble)'s sit in different sections despite identical provenance — both members are Sylament-only, and both have rows in [renames.md's no-Filament-source table](../renames.md#names-with-no-filament-source), which is where that shared fact is recorded once.
What splits them is what each note *says*: the other one explains why a member exists at all where Filament had a local variable, which is a deviation in surface; this one explains behaviour that is Filament's inline loop unchanged and only reads wrong.
Neither section claims provenance — read that off renames.md, not off which heading a note landed under.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
Say what would reverse the decision when it is provisional.

### The date family formats through `DateTimeImmutable`, so only English reads right

Filament parses with `Carbon::parse()` and prints with `translatedFormat()`, which is `date()`'s token set with month and day *names* translated.
This port normalises with a three-arm match — a `DateTimeInterface` through `DateTimeImmutable::createFromInterface()`, an `int` as `@<timestamp>`, a string through `new DateTimeImmutable($state)` — and prints with `format()`.
Carbon is not adopted: [principles §2](../../internals/principles.md) sends us to the platform mechanism, and the whole of what Carbon adds here is the name translation.

The consequence is exact and bounded: output is **byte-identical** to Carbon's for English, and for any other locale the month and day names stay English.
That is i18n's to close, and closing it does not change this API — only what `format()` is replaced with.
`since()` has the same shape one level up: it reads [`diff_for_humans()`](../support/helpers.md), a pure port of `CarbonInterval::forHumans()`'s single-unit cascade, whose words are English by construction.

A fourth arm is a throw rather than a coercion: a float, an array or an object that is not a `DateTimeInterface` names the column in a `LogicException` instead of being pushed through `new DateTimeImmutable((string) $state)`, which Carbon's `parse()` would attempt and fail at less legibly.
A malformed *string* still throws from `DateTimeImmutable` itself, exactly as `parse()` does.

### `markdown()` throws for the package it suggests, rather than falling through

`league/commonmark` is a `suggest` of `sylament/tables`, not a `require` — a [guarded optional integration](../../internals/dependencies.md#the-guarded-optionals-and-their-pins) like flysystem in support.
The guard is `class_exists(GithubFlavoredMarkdownConverter::class)` at the one place the converter is constructed, and its else-arm is a `LogicException` naming the package and the command that installs it.

The alternative — falling through to the unconverted text — is what makes this a divergence worth stating: it would render a document's raw markdown into the cell, sanitized and therefore *plausible*, with nothing in any log.
A column that asked for markdown and silently got none is the failure this refuses.

`markdown()` and `commonMarkOptions()` / `commonMarkExtensions()` themselves are unguarded — they only assign — so configuring a markdown column on an install without the package is legal right up to the render.
That is deliberate: the throw names the column, which a container-time failure could not.

The library is present in this repo's root vendor, so the *present* branch is exercised end-to-end.
The absent branch cannot be exercised by uninstalling, so [`CanFormatStateTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php) reads the guard site itself and [`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php) asserts the manifest keeps the package a `suggest` — together, deleting the guard or promoting the dependency fails the suite.

### `numeric()` refuses a formatter failure rather than rendering `false`

`NumberFormatter::format()` and `formatCurrency()` return `string|false`, and `false` stringifies to `''`.
Filament goes through Laravel's `Number` helpers, which pass that straight out.
Here both results run through one private check that throws a `LogicException` naming the column — so a currency code ICU cannot resolve surfaces as an error rather than as an empty cell, which is indistinguishable from a null state.

### The expand toggles render only where there is something to expand

Filament renders its two `Show N more` / `Show N less` controls whenever `expandableLimitedList()` is set and the list overflows — even when the over-limit items were sliced away, which happens for every combination except `listWithLineBreaks()` *and* expandable.
Clicking them then reveals nothing.

The partial ties the two together instead: the toggles render exactly when the hidden `<li>`s exist, and the static `and N more` message renders otherwise.
The condition is the negation of the slice gate, so the invariant is structural rather than remembered — one expression decides both whether the values survive and whether a control that reveals them appears.

The controls are `<span role="button">` with no served `tabindex`, for the reason [the copy control is](#the-copy-target-is-the-content-element-and-the-badge-pill-when-there-is-one): the cell may sit inside a record `<a>`.
The [`limited-list` controller](../../../packages/tables/assets/src/controllers/limited_list_controller.ts) stamps the `tabindex` at connect, calls `preventDefault()` and `stopPropagation()` so a click does not follow the row's link, and keeps its state in an `expanded` Stimulus value — which a live morph replays, so an expanded cell stays expanded across a re-render.
Filament's Alpine `x-data` scope is per-render and loses it.
Its attributes are spread onto the cell root **after** `extraAttributes()`, as the four editable partials spread theirs, so a consumer's own `data-controller` cannot silently unbind the toggles.

### `rowIndex()` refuses a cell with no row loop, rather than numbering it null

Filament's `rowIndex()` closure type-hints `stdClass $rowLoop` and reads `$rowLoop->iteration` — the object Livewire's Blade loop always supplies.
Here [`getRowLoop()`](column.md) is nullable, because a column can be rendered outside the table partial (a test, a card, a future layout), and a loop that carries no index is representable too.

So the closure takes `?stdClass` and throws a `LogicException` naming the column when the index is not an integer, rather than emitting `''` from a null cast.
A column numbering every row `0` is the failure this refuses, and it is one a render test would not catch — the cell renders, it is simply wrong.

The offset itself is Filament's verbatim: `getTableRecordsPerPage()` is consulted and the page multiplication runs **only** when it is numeric, so a table showing `all` records numbers from one on every page rather than multiplying by a string.

### The cell renders a Twig partial where Filament emits an embedded view

Filament v5's `TextColumn` implements `HasEmbeddedView` and builds its cell in `toEmbeddedHtml()` — raw PHP `ob_start()` output with the attribute bags concatenated inline.
Sylament declares a `$template` and renders through [`ViewComponent::render()`](../../../packages/support/src/Components/ViewComponent.php), the same way every other component in this port does; the contract itself is not ported, for the reasons already recorded on [schemas' `Text`](../schemas/text.md).

The consequence worth naming is what happens to `toEmbeddedHtml()`'s **local-variable preamble**.
Filament computes `$state = $this->getState()`, `$state = $this->formatState($state)` and friends at the top of the method and the markup below reads the locals.
A Twig template has no such preamble: it reads `component.x` per value, and anything it cannot get from a single getter call goes through `getExtraTemplateData()` — the house rule recorded in [`.claude/rules/php.md`](../../../.claude/rules/php.md).
`getFormattedState()` below is that translation for the one value the preamble computed in two steps.

### The item element is never merged into the outer div

Filament's `toEmbeddedHtml()` has a fast path: when the cell holds a single value and has no descriptions, no bullets and no line clamp, it merges the item element's attributes onto the outer `div` and emits **one** element ([`TextColumn.php:476`](../../../vendor/filament/tables/src/Columns/TextColumn.php)), falling back to the two-element shape otherwise.
The port always splits — `div.sy.sy-ta-text` wrapping one `span.sy-ta-text-item` per value, whatever the cell holds.

It costs one span per cell.
It buys a single DOM shape, so a selector written against one cell holds for every cell; a single CSS path, so `.sy-ta-text` carries layout and `.sy-ta-text-item` carries type with no rule having to work in both roles; and a template with no branch — the merged arm is where Filament's method needs `$stateItemAttributes` merged into `$attributes` before either is rendered, which a Twig template would have to express as a second copy of the markup.

### The list wrapper appeared with the list arms, and closed a CSS divergence with it

Until the list arms landed, every item sat directly under the outer `div` — the [one-shape rule](#the-item-element-is-never-merged-into-the-outer-div) taken one step further than Filament takes it — and `text-column.css` had to make one container do two jobs at once: `flex-row flex-wrap` so badges flowed along a line, plus `& > .sy-ta-text-description { w-full }` so a description could still claim one of its own.
That worked, and it cost one behavioural delta: a badged cell *with descriptions* wrapped its badges whether or not [`wrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) was set, because a `width: 100%` flex item cannot force a line break in a non-wrapping container.

The list arms removed the reason for it.
A cell now emits `<ul>` whenever it has more than one item or is `bulleted()`, which is Filament's own rule, so the badge row is a nested flex container again and the outer `div` is free to be nothing but a column.
The stylesheet follows: `.sy-ta-text-has-descriptions` and `.sy-ta-text-list-limited` make it `flex flex-col` with Filament's badge/non-badge gap fork, `& > ul` takes `flex gap-x-1.5`, `.sy-wrapped > ul` adds `flex-wrap gap-y-1` and `.sy-ta-text-has-line-breaks > ul` swaps to `flex-col gap-y-1`.
`wrap()` is honoured on every combination, the `w-full` override is gone, and this port and Filament now differ in the outer element's tag alone — `div` here, `div` or `ul` there, depending on which branch of `toEmbeddedHtml()` ran.

The one-shape rule still holds where it was written: a **single** unbulleted value renders `span.sy-ta-text-item` under the `div` rather than being merged into it.
What changed is only that a *list* now gets the wrapper its layout needs.

`white-space` is a separate axis and follows Filament exactly: `.sy-wrapped` sets `whitespace-normal` on the cell, with `& .sy-badge` and `& .sy-ta-text-list-limited-message` pinned `whitespace-nowrap` so only the descriptions around them wrap.
Vendor's compound `:not(.fi-ta-text-has-badges.fi-ta-text-has-line-breaks)` guard now ports whole too — `listWithLineBreaks()` exists, so the second half of it can be present, and the rule stops badges from being pushed below their own bullets.

### A badged icon goes through the `Badge`'s own props, and loses `iconColor()` doing it

Filament builds the badge span by hand inside `toEmbeddedHtml()` and encloses `iconBefore` / content / `iconAfter` within it ([`TextColumn.php:454-467`](../../../vendor/filament/tables/src/Columns/TextColumn.php)), so the icon it renders for the plain case is the same object it drops inside the pill — colour attributes and all.
The port renders badges through the real [`Badge`](../support/badge.md) component instead, so the badged branch passes `icon`, `iconSize` and `iconPosition` to its props and lets it place the icon beside its own label container.
Structure matches Filament exactly; one thing does not survive the handoff.

**`iconColor()` has no effect on a badged column.**
Support's `Badge` declares `icon`, `iconAlias`, `iconSize` and `iconPosition` but no icon *colour*, and its [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css) has no counterpart to Filament's `& .fi-icon.fi-color { @apply text-color-500; }` — so even a coloured icon handed to it would land on an element no rule reads.
Both halves have to move together to close this: an `iconColor` prop feeding `colorAttributes()` into the `Badge`'s `generateIconHtml()` call, and the missing `.sy-icon.sy-color` rule in support's badge stylesheet.
Until then a badged icon takes the badge's own text colour, which is what `.sy-badge:not(.sy-color) .sy-icon` and the coloured badge's inherited `text-(--sy-text)` already give it — reasonable output, not the configured one.
`iconColor()` on a **non**-badged column is unaffected and fully live.

One consequence is a selector Filament needs and this port does not.
Filament writes `& > .fi-icon, & > span:not(.fi-badge) > .fi-icon` because its item can hold a wrapper span that is *sometimes* the badge, so the icon rule has to exclude that case by name.
Here the plain icon is a direct child of `.sy-ta-text-item` and the badged icon is two levels down inside `.sy-badge`, so the child combinator in `& > .sy-icon` excludes it structurally and no `:not()` is needed.
Pinned by the Integration case asserting the item has exactly one element child and the `<svg>` sits under `span.sy-badge`.

A second, smaller thing was checked while resolving this and is worth recording so it is not re-litigated: the partial's locals are named `itemIcon`, `itemIconSize`, `itemSizeValue` and `iconPositionValue` rather than `icon`, `iconSize`, `size` and `iconPosition` **only** to keep the pass-through mutation-detectable.
A `{% props %}`-declared prop that carries a **default** is not filled from the calling template's context — `PropsNode` resets it to that default whenever `__context` holds the name, that being the caller's context minus whatever the tag passed as an attribute, so an explicit `:name=` still wins — and every one of [`Badge`](../../../packages/support/templates/components/Badge/index.html.twig)'s props does, so the collision was never load-bearing here.
Verified by renaming the local back to `icon` and deleting the `:icon` attribute, which renders no icon at all.
The narrowing matters because the rule is not general: a prop declared *without* a default is silently filled from the caller in the `<twig:X>…</twig:X>` block form (the self-closing form inherits no context at all, and throws instead).
The names remove the question permanently rather than leaving the next reader to re-run that probe.

### The icon's gap is a margin, because the line clamp owns the item's `display`

Filament puts nothing between a text column's icon and its value but the whitespace its PHP-echo template happens to print — `$stateItemIconBeforeHtml`, the state and `$stateItemIconAfterHtml` sit on separate lines of [`toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), and HTML collapses that to one space.
Neither its CSS nor ours declares a gap there.
This port's partial controls whitespace on every tag, so it printed none and the icon rendered flush against the value — `⊘Grace Hopper`.
A browser pass is what found it, and no assertion could have: the ordering cases read the item's inner HTML, where a space *between* the two changes neither `toStartWith('<svg')` nor `toEndWith('Airbag')`, so restoring Filament's space would have passed the suite unchanged and been just as easy to reformat away again.

The space was rejected in favour of a margin, because a margin is what the rest of the toolkit already means by this.
`Button`, `Link` and `Badge` each space their icon from their label with `gap-1.5` on a flex or grid container, and `.sy-ta-text-item` is the one place that cannot: the unconditional `line-clamp-(--sy-line-clamp,none)` that makes [`lineClamp()`](../../../packages/support/src/Components/Concerns/HasLineClamp.php) work sets this element's `display` itself — measured `-webkit-box` with no clamp value and `flow-root` once one lands — so there is never a flex box here to hang a gap on, and taking one by force would break the clamp.
The margin survives both, measured 6px in each.
The partial instead stamps the icon `sy-ta-text-icon-before` or `sy-ta-text-icon-after` from the position it already computes, and `text-column.css` gives each the matching `me-1.5` / `ms-1.5`, which is the same 6px those container gaps produce.

Both halves are pinned because either one failing alone is silent, in the way a [missing shade class](#the-item-policy-measures-the-dark-surface-against-gray-900) is: the Integration case asserts the partial stamps exactly one position class per position, and the Unit stylesheet case asserts `text-column.css` defines a rule for both.
A class with no rule, or a rule no class reaches, renders the icon flush again with nothing in the console.

### The item policy measures the dark surface against `gray-900`

[`ItemComponent`](../../../packages/tables/src/Colors/Components/TextColumnComponent/ItemComponent.php) resolves its `dark:text` slot against `$gray[900]` where [Filament's](../../../vendor/filament/tables/src/View/Components/Columns/TextColumnComponent/ItemComponent.php) uses `$gray[800]`; the light `text` slot's white surface is Filament's unchanged.
The surface argument is not a style choice — it is a claim about what is actually behind the text, and the shipped `sy-ta` chrome paints its container `bg-white dark:bg-gray-900` ([`table.css`](../../../packages/tables/assets/src/styles/components/table.css)), so `gray-800` would be measuring against a background this port does not render.
Copying the literal would silently understate the contrast ratio of every dark cell.

The same reasoning is why the surfaces are not schemas' [`TextComponent`](../schemas/README.md#supporting-surface)'s either — that one sits on a different container and measures `$gray[50]` / `$gray[700]`.
Revisit if the table chrome's background changes, and re-run [`PolicyShadeGridTest`](../../../packages/tables/tests/Unit/Colors/Components/PolicyShadeGridTest.php) when it does.

### `getFormattedState()` replaces the embedded view's local preamble

`getFormattedState()` has no Filament counterpart: it is `formatState(getState())` under a name — the translation named above for the one value `toEmbeddedHtml()`'s preamble computed in two steps.
It is the same kind of member as the [names with no Filament source](../renames.md#names-with-no-filament-source) — Sylament-only, nothing to rename from, and so is [`getStateItems()`](#getstateitems-is-the-cells-value-list), which is filed under Semantics for [reasons of what its note says](#getstateitems-is-the-cells-value-list) rather than of where it came from.

Its purpose is the composition, not being any particular caller's seam: a Twig template reaching one getter for the column's whole formatted state, the same translation regardless of which template reaches for it.
It formats the *whole* column state in one call, which is why it is not what [`getStateItems()`](#getstateitems-is-the-cells-value-list) calls — a cell can hold more than one value, so the item list reads `getState()` and `formatState()` directly, per item, rather than through this method.
`getFormattedState()` stays public and behaviourally unchanged either way: not a semantic addition, the composition is Filament's and only the naming is ours, and it stays reachable directly, the same as `getState()` and `formatState($x)`, both of which also stay public.

One value in one call is the whole claim, and on a multi-value state it is not the value the cell paints.
`formatState()` handed the column's array reaches the array-to-JSON arm, so a `['a', 'b']` state answers `'["a","b"]'` where the rendered cell reads `a, b` — the join is `getStateItems()`'s, one pass per value, and it happens nowhere near this method.
So this is the column's *state* run through the pipeline once, not a preview of the cell: a caller wanting what the cell shows reads the item list.

### `formatStateUsing()` accepts a literal where Filament accepts only a closure

Filament types `formatStateUsing(?Closure $callback)` and then evaluates `$this->formatStateUsing ?? $state`, so the *null* case falls back to the state and a non-null case is always a closure.
Sylament types the parameter `mixed` and branches on null explicitly, which means `->formatStateUsing('fixed')` is legal and pins every cell to the literal.

This aligns the pair with `state()` / `getStateUsing()` on [`Column`](column.md#state-is-getstateusing-under-another-name), which Filament itself types `mixed` — a literal-or-closure setter is the port's default shape, and `evaluate()` returns a non-closure untouched, so the widening costs nothing and the narrower type here was the odd one out.
Every closure Filament accepts still works unchanged; only previously-rejected literals are new.

### `limit()` composes `slice()` rather than calling Symfony's `truncate()`

`AbstractString::truncate($length, $ellipsis)` counts the ellipsis *toward* `$length` — it slices to `$length - mb_strlen($ellipsis)` characters before appending it, so `truncate(30, '...')` returns 30 characters total.
Laravel's `Str::limit()` — which Filament calls, and whose semantics this port preserves — appends the ellipsis *after* the slice, so `limit(30)` returns 30 characters **plus** the ellipsis.
Calling `truncate()` here would silently cost a migrating Filament user three characters per cell, so `truncateState()` composes `slice()` / `trimEnd()` / `append()` by hand instead — [`CanFormatStateTest`](../../../packages/tables/tests/Unit/Columns/Concerns/CanFormatStateTest.php) pins the difference.

A second, inherited delta rides along.
Laravel's `Str::limit()` truncates by *display width* (`mb_strwidth()` — a fullwidth CJK character consumes two units of the limit), where Sylament's `u($state)->length()` / `slice()` count *grapheme clusters* (`grapheme_strlen()` / `grapheme_substr()`) — one unit per user-perceived character regardless of display width, which is not the same thing as a raw code-point count either.
A migrating Filament user with CJK cell content gets roughly twice as many characters into the same numeric `limit()` here as Laravel gave them.
Faithful to `Str::limit()`'s *ellipsis-placement*, not its *width-counting* — the two are independent behaviours, and only one made the port.

### `formatState()` throws for a state it cannot stringify

Filament never explicitly casts state to a string — `formatState()` passes it untouched into `Str::limit()`, string concatenation, and eventually Blade's `{{ }}`, so an object with no `__toString()` reaching that far fails wherever PHP's implicit string coercion first runs: uncontrolled, and with no column named in the error.
Sylament's pipeline casts once, explicitly (`(string) $state`, ahead of truncation and affixing), and PHPStan level max will not let that cast stand for a `mixed`-typed value without proof it is safe.
`formatState()` checks `is_scalar($state) || $state instanceof Stringable` immediately before the cast and throws a `LogicException` naming the column when neither holds — the same [fail-fast rule](../../../CLAUDE.md) already applied to `color()`/`icon()`'s `true` arm below.
A scalar, a `Stringable` (including `Markup`), an array (already coerced to JSON above), a `HasLabel` (already unwrapped above) and a `BackedEnum` (unwrapped to its backing value immediately after) never reach the check.

That enum unwrap is Filament's, reached from the other end, and it is what keeps the throw narrow.
Filament's pipeline ends in Blade's `{{ }}` → `e()`, which turns a `BackedEnum` into its `->value` before escaping ([`helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php)), so an enum that implements none of support's state contracts still prints there.
Without the same unwrap here the guard would reach it first — and that is not a corner case but the ordinary shape of a Doctrine `enumType` column, since `HasLabel` is opt-in: a page rendering one would have fatalled.
What is left for the throw is a genuinely unstringable object — an entity, a DTO, a closure — which has no print of its own in either framework.

### `hasStateFormatting()` has nothing to guard

Filament's `hasStateFormatting()` reports whether *any* of twelve formatting properties is configured, so the embedded view can skip `formatState()` and emit the raw state — a fast path over a method that would otherwise run a dozen branches per cell.
All twelve now exist here: `formatStateUsing`, the four truncation properties, the two affixes, the two HTML flags and the four family markers `date()` / `dateTime()` / `time()` / `money()` / `numeric()` set.
So the predicate would no longer be half dead — and it stays **skipped** anyway, for the reason below rather than for the one this paragraph used to give.

Marked **skipped** rather than **todo** because it does not return with the formatting setters either: the branch it optimises is a Twig template reading one getter, not a PHP method with a dozen inline steps.
If the pipeline ever grows expensive enough to matter, the memo belongs on `getFormattedState()` — one place, no property list to keep in sync.

Reassessed twice.
First when `formatState()` grew from a null check plus one `evaluate()` into the full truncation/affix/label pipeline; again when the date, money, numeric and HTML families landed and completed the property list.
Both times the answer was the same: the guard would have more to check than before, but the branch it would optimise is still a Twig template reading one getter, not a hot inline path.
The row stays **skipped**, and the standing invitation to forget a property when adding one is now the *only* argument against it — which is an argument for not writing it, not for writing it carefully.

### The collapsed join is Filament's default, not a simplification

All four of Filament's list shapes ship: a multi-value state collapses to a single comma-joined item by default (`TextColumn.php:366-380`), `listWithLineBreaks()` keeps the items as separate lines, `bulleted()` renders them as a `<ul>` with markers, and `limitList()` / `expandableLimitedList()` truncate the set with an expand trigger.
This section stays because the *default* is the one a reader mistakes for a missing feature.

It is worth naming *why* the collapse is Filament's *default* rather than a simplification: a `TextColumn` over `['symfony', 'forms']` renders `symfony, forms` in Filament because `toEmbeddedHtml()` reduces the column's state to one item per list entry *before* any item reaches `formatState()` — the array `formatState()` itself ever coerces to JSON is always a single item's own value (a JSON column, say), never the column's whole list.
[`getStateItems()`](#getstateitems-is-the-cells-value-list) is where the port makes that same split, so the two coercions no longer compete: it formats each value on its own and joins the results, and the JSON arm only ever sees a value that is itself an array.
The partial loops the item list, which is why the badged case keeps one element per value while the plain case gets one.

A consumer wanting a *different* join reaches for [`state()`](column.md#state-is-getstateusing-under-another-name), and **not** `formatStateUsing()`.
The two read as interchangeable escape hatches and are not: the item list calls `formatState()` **per value**, so `formatStateUsing(fn (array $state) => implode(' · ', $state))` is handed `'symfony'` and then `'forms'` — never the list — and its `array` hint fails on the first one.
`state(fn ($record) => implode(' · ', $record['tags']))` displaces the column's state *ahead of* the split instead, so `getStateItems()` sees one scalar and emits one item carrying the join the consumer wrote.

### `weight()` and `fontFamily()` are column-level, where Filament's are per item

Filament's `HasWeight` and `HasFontFamily` ([`support/src/Concerns/`](../../../vendor/filament/support/src/Concerns)) both declare their getter `getWeight(mixed $state = null)` / `getFontFamily(mixed $state = null)` and inject it the same way `getColor()` / `getIcon()` do — `evaluate($this->weight, ['state' => $state])` — so `toEmbeddedHtml()`'s per-item closure can call `getFontFamily($stateItem)` once per badge.
Sylament's ported [`HasWeight`](../../../packages/support/src/Components/Concerns/HasWeight.php) / [`HasFontFamily`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) predate this column and carry no `$state` parameter at all: `getWeight()` and `getFontFamily()` take zero arguments, so `evaluate()` passes no `'state'` injection for either.

That gap is the reason, not a coincidence: a closure named `fn ($state) => …` still compiles against both, but with no explicit injection to satisfy the name, `evaluate()` falls through to [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](column.md)'s ambient `'state'` resolver instead — which binds to `$this->getState()`, the **column's** own state, not an item `getStateItems()` is iterating.
So a weight or font-family closure never sees what a colour or icon closure does: on a badged multi-value column, `weight(fn ($state) => …)` receives the whole array `getState()` returns, once, never a single badge's value.
Pinned by [`TextColumnTest`](../../../packages/tables/tests/Unit/Columns/TextColumnTest.php)'s `'gives a weight closure the column state, not an item'` case.

Not something this task introduces or fixes — the gap predates the state-item model and sits in `HasWeight` / `HasFontFamily` themselves, both outside this task's files, and schemas' [`Text`](../schemas/text.md) composes the same two traits, so it is not column-specific either.
It surfaces here only because `getStateItems()` is the first caller that iterates a column's state per item at all; nothing before it made the difference observable.
Reversing it means adding the `$state` parameter (and the matching `['state' => $state]` injection) to both support traits, which touches every other consumer of `weight()` / `fontFamily()`, not just this column.

### An explicit `true` colour or icon throws, where Filament lets it through and crashes

`color()` / `icon()` accept `bool` in their union, mirroring [`Filament\Tables\Columns\Concerns\HasColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php) and [`HasIcon`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php) exactly — but only `false` carries meaning, as the sentinel the getters test for.
Filament's own getters declare `string | array | null` / `string | BackedEnum | Htmlable | null` and check only `$color === false`, so an evaluated `true` — nonsensical, but the type accepts it — falls through to `filled($color)`, which is `true` for a bare `true`, and the getter returns it: a `TypeError` at the return statement, since `true` is not a member of the declared return type.
There is no Filament test suite here to check that against — the distributed `filament/tables` package ships `src/`, `resources/`, `docs/` and `stubs/`, and no `tests/` directory at all — so the reading is off the vendor source alone: the union admits `true`, both getters test only for `false`, and neither declared return type has a `bool` arm to return it through.
A latent gap in that union which surfaces as an uncontrolled crash, not a contract anyone relies on.

Sylament's `getColor()` / `getIcon()` test `$color === true` / `$icon === true` ahead of the `false` check and throw a `LogicException` naming the column — `Color of column [%s] must be a palette name, a shade map, or false; true given.` and the icon equivalent — rather than reaching Filament's uncontrolled `TypeError`, and rather than swallowing the mistake into `null`.
This is the project's [fail-fast rule](../../../CLAUDE.md): `true` is not a value to absorb — a caller who sets it meant something else — so the toolkit says so at the point the mistake was made, in a message naming the column, rather than downstream and unnamed.
PHPStan level max stays clean either way (confirmed for both this arm and the `false` arm below it): a `throw` removes `true` from the flow the same way an early `return` does, so nothing is left over against the declared `string|array|null` return type.
Every `false` call site keeps its existing meaning; a `true` call site could never have returned successfully in Filament either, and now fails loudly at its own column instead of silently corrupting a render.

### A `colors()` or `icons()` closure is handed the item state, where Filament's is handed the column's

Both maps admit a `Closure` as an entry's condition, and the port evaluates it with an explicit `['state' => $state]` injection carrying the value `getColor()` / `getIcon()` was called with.
Filament evaluates the same closure as `$column->evaluate($condition)` — no injection at all — so `fn ($state) => …` falls through to [`Column::resolveDefaultClosureDependencyForEvaluationByName()`](column.md), whose `'state'` arm is `$this->getState()`: the **column's** whole state, once, whatever value the getter is resolving for.

The evidence that this is a vendor defect rather than a design difference is inside the same `foreach`.
The map's literal arm is `$condition === $state`, comparing against the *item* state in both codebases, because that `$state` is the wrapper closure's own injected parameter.
So in Filament `colors(['success' => 'published'])` keys off the item and `colors(['success' => fn ($state) => $state === 'published'])` keys off the column — two arms of one map, written to read as alternatives, answering against different values.
The port makes both arms agree, the same way the [`searchable()` closure arm](column.md#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column) was fixed at the cause rather than mirrored.

Visible where a cell holds more than one value: on a badged column each badge gets its own verdict here and one shared verdict there; on a collapsed cell the closure sees the joined string, which is [what `state` means on a collapsed item](#getstateitems-is-the-cells-value-list).
A single-valued cell — every cell in most tables — cannot tell the two apart, since the item state and the column state are the same value.

### `formatState()` always returns a string, where Filament passes a scalar through

The port casts once, explicitly (`(string) $state`), so `formatState(42)` is `'42'`, `formatState(1.5)` is `'1.5'` and `formatState(true)` is `'1'`.
Filament's returns the scalar untouched: with no formatting configured its `hasStateFormatting()` fast path returns `$state` before any branch runs, and even configured it never casts — `Str::limit()` and the affix concatenation coerce where they happen to run, and the state reaches Blade as whatever it was.

The cast is [the stringify guard's other half](#formatstate-throws-for-a-state-it-cannot-stringify) and the reason `getStateItems()` can type its `content` entries `string|Markup|null` at all.
It costs nothing at the render — Twig prints `42` and `'42'` identically — so the difference is only visible to a **caller** that reads `formatState()` or `getFormattedState()` back in PHP and compares with `===`.
The declared return type stays Filament's `mixed` rather than being narrowed to match: `formatStateUsing` is `mixed`, so PHPStan cannot see through `evaluate()`'s generic, which is what [`formatStateForItem()`](#formatstateforitem-throws-for-a-format-result-the-pipeline-cannot-produce) exists to bridge.

### The collapsed join flattens a `Markup` item, where Filament keeps it raw

`getStateItems()`' collapse branch maps `(string) $this->formatStateForItem($value)` over the values and joins the results, so an item that formatted to [`Markup`](../renames.md#idiom) contributes its raw HTML to a **plain** string — and the template escapes plain content, so that item's markup renders as visible tags.
Filament joins through its `$formatState` closure, whose `e()` call [passes an `Htmlable` through with `toHtml()`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php) and escapes everything else, then echoes the joined result raw — so the markup survives the collapse there.

The direction is the safe one and that is why it is not treated as a bug: the port over-escapes rather than emitting unreviewed HTML, and the per-item branch is unaffected — a badged or single-valued cell keeps `Markup` as `Markup` in `content` and renders it raw.
What is inconsistent is that the *same* format callback returns raw markup on a badged column and escaped text on the collapsed one beside it.
Closing it means deciding escaping per item inside the join and wrapping the result in `Markup` — precisely what [`affixState()`](#a-markup-state-skips-truncation-and-affixing-escapes-whichever-side-is-not-already-markup) already does for one value — and it lands with the list setters, which need a per-item join with the same question in it.

### `getSizeClass()` is Sylament-only, and the partial resolves the size twice

Filament reads `$size = $this->getSize($stateItem)` into a local at the top of its per-item closure and builds the class from that one value.
The port has no local to read from, so `getSize()` answers the enum-or-string union and `getSizeClass()` turns it into `sy-size-<value>` or passes a custom string through — one of the Sylament-only [`*Class()` getters](../renames.md#names-with-no-filament-source), and the only one on this class.

The cost is that the partial calls **both**: `getSize(item.state)` for the value the `Badge` prop and the icon-size choice need, and `getSizeClass(item.state)` for the class, which resolves the size a second time.
So `size(fn ($state) => …)` runs twice per non-badge item where Filament's runs once — invisible for a pure closure, observable for one that counts calls or reads something that moves.

That second call is also what makes `getSizeClass()`'s docblock read as though the template contradicts it.
It says templates never have to type-sniff the enum-vs-string union, and the line above the call does exactly that (`itemSize.value ?? itemSize`) — for a different consumer, the raw *value*, which no getter hands over.
Both go away together: a `getExtraTemplateData()` entry resolving the size once per item and passing value and class down would close the double evaluation and leave the docblock true of every line near it.
Not done here because the hook is per **component**, not per item, and the item loop is the template's.

### `formatStateForItem()` throws for a format result the pipeline cannot produce

`formatStateForItem()` is the private helper every value in `getStateItems()` goes through: it re-tests what `formatState()` returned and throws a `LogicException` naming the column when that is not `string`, `Markup` or `null`.
The throwing arm is **unreachable** against this class as it stands — the pipeline casts before it returns, so those three are the only shapes it produces.

It exists because `formatState()`'s declared return is Filament's `mixed` and PHPStan cannot narrow through it — `formatStateUsing` is itself `mixed`, so `evaluate()`'s generic binds to nothing useful — while `getStateItems()` promises `content: string|Markup|null` in its own array shape.
The choice at that point is a silent cast, an [inline suppression the codebase does not allow](../../../CLAUDE.md), or a runtime check; the check is the same [fail-fast rule](../../../CLAUDE.md) as the [stringify guard](#formatstate-throws-for-a-state-it-cannot-stringify) and the [`true` colour arm](#an-explicit-true-colour-or-icon-throws-where-filament-lets-it-through-and-crashes), and unlike those two it is proof rather than a policy about user input.
It becomes reachable the moment a subclass overrides `formatState()` — which is public API — and then it names the column instead of failing inside Twig.

### `alignment()` reaches a badge row through the list, not through `text-align`

`alignment()` puts `sy-align-center` / `sy-align-end` / `sy-align-justify` on the cell's outer `div.sy-ta-text`, and [`text-column.css`](../../../packages/tables/assets/src/styles/components/text-column.css) answers each with `text-center` / `text-end` / `text-justify`.
That moves inline content and does nothing to flex items, so while badges were flex children of the outer `div` the alignment classes landed, matched their rules, and changed nothing — a knowing gap.

The list wrapper closed it.
Badges are now flex children of a `<ul>`, exactly as in Filament, so each `sy-align-*` arm pairs its `text-*` with a `justify-*` under `& > ul` — `justify-center`, `justify-end`, `justify-between` — which is vendor's own pairing under `ul&, & ul` with one selector fewer, since this port's root is always the `div`.

A single-value badge is the one case still governed by `text-align` alone, and correctly so: with one item there is no `<ul>` and no flex container, and the badge is inline content the alignment moves directly.
