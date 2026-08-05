# TextColumn (tables)

[`Sylament\Tables\Columns\TextColumn`](../../../packages/tables/src/Columns/TextColumn.php)
— the first concrete column of the catalog: a [`Column`](column.md) that renders
its cell state as text, plus the formatting hook that sits between the raw state
and the template. It ports Filament's
[`Filament\Tables\Columns\TextColumn`](../../../vendor/filament/tables/src/Columns/TextColumn.php)
and the one `Columns\Concerns\` trait it adds today,
[`CanFormatState`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php)
— folded into the surface table below rather than earning a row upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). Everything
[`Column`](column.md) carries — the name, the label, the record binding, the
state read, the closure injections — is tracked there, not repeated here.

It is a thin subclass on purpose. Filament's `TextColumn` is ~700 lines of
badges, icons, colours, list flattening, date/money/number formatting and the
embedded-view render; this is the state pipeline and a template path, and the
gaps are the surface table below.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php)
  — the class, its composed traits and `toEmbeddedHtml()`
- [`tables/src/Columns/Concerns/CanFormatState.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php)
  — the formatting pipeline, of which the `formatStateUsing` hop ports here

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries either **detail** → the write-up in [Details](#details),
deleted once the row is **done**, or **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`formatStateUsing()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState::formatStateUsing()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | done | — | [diverges](#formatstateusing-accepts-a-literal-where-filament-accepts-only-a-closure) |
| [`formatState(mixed $state)` — the `state` injection](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | [`CanFormatState::formatState()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | in progress | — | [detail](#formatstate-is-the-hook-without-the-pipeline-around-it) |
| [`getFormattedState()`](../../../packages/tables/src/Columns/Concerns/CanFormatState.php) | — (Sylament-only) | done | — | [diverges](#getformattedstate-replaces-the-embedded-views-local-preamble) |
| [`$template`](../../../packages/tables/src/Columns/TextColumn.php) + [the partial it names](../../../packages/tables/templates/partials/TextColumn/index.html.twig) | [`TextColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | done | — | [diverges](#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) |
| `hasStateFormatting()` — the raw-state fast path | [`CanFormatState::hasStateFormatting()`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | skipped | — | [diverges](#hasstateformatting-has-nothing-to-guard) |
| `limit()` / `words()` — character and word truncation | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | todo | — | — |
| `prefix()` / `suffix()` | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | todo | — | — |
| `html()` / `markdown()` — the sanitizer pass | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | todo | — | [detail](#formatstate-is-the-hook-without-the-pipeline-around-it) |
| `date()` / `dateTime()` / `time()` / `since()` and the `iso*` variants, `timezone()` | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | todo | — | — |
| `money()` / `numeric()` | [`CanFormatState`](../../../vendor/filament/tables/src/Columns/Concerns/CanFormatState.php) | todo | — | — |
| `badge()` — the badge shell | [`TextColumn::badge()`](../../../vendor/filament/tables/src/Columns/TextColumn.php), [`View\Components\BadgeComponent`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | todo | — | — |
| `color()` / `icon()` / `iconColor()` / `iconPosition()` | [`Columns\Concerns\HasColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasColor.php), [`HasIcon`](../../../vendor/filament/tables/src/Columns/Concerns/HasIcon.php), [`HasIconColor`](../../../vendor/filament/tables/src/Columns/Concerns/HasIconColor.php) | todo | — | — |
| `description()` — the secondary line above or below | [`Columns\Concerns\HasDescription`](../../../vendor/filament/tables/src/Columns/Concerns/HasDescription.php) | todo | — | — |
| `size()` / `weight()` / `fontFamily()` / `lineClamp()` | [`HasWeight`](../../../vendor/filament/support/src/Concerns/HasWeight.php), [`HasFontFamily`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php), [`HasLineClamp`](../../../vendor/filament/support/src/Concerns/HasLineClamp.php) | todo | — | — |
| `wrap()` / `copyable()` | [`CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php), [`CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | todo | — | — |
| Lists — `bulleted()`, `listWithLineBreaks()`, `limitList()`, `expandableLimitedList()` | [`TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | todo | [`separator()`](column.md) | [diverges](#an-iterable-state-renders-as-one-comma-joined-line-and-nothing-else) |
| _rest not audited yet_ | | | | |

The three state-pipeline rows are covered by
[`TextColumnTest`](../../../packages/tables/tests/Unit/Columns/TextColumnTest.php)
— unformatted state passing through, a format closure receiving both `$state` and
`$record`, and a literal `formatStateUsing('fixed')` displacing the state. The
`$template` row is covered by the Integration
[`TextColumnTest`](../../../packages/tables/tests/Integration/Columns/TextColumnTest.php),
which renders columns inside a real table: a dotted path and a `formatStateUsing()`
closure landing in `div.sy-ta-text`, an iterable state arriving comma-joined, and a
null state rendering the element empty rather than dropping it.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### `formatState()` is the hook without the pipeline around it

Filament's `formatState()` is ~60 lines: the `formatStateUsing` evaluation is
one hop inside it, wrapped by an array-to-JSON coercion, the `HasLabel`
enum-label unwrap, a `RichContentAttribute` / `Htmlable` / markdown branch that
runs `Str::sanitizeHtml()`, character and word truncation, prefix and suffix
concatenation with per-branch escaping, and a final `HtmlString` wrap.

Sylament ports the hop and nothing around it — every one of those steps is a
setter whose row is above and unported, so there is nothing yet for the pipeline
to do. Two of them are the load-bearing ones and will not be inlined the Filament
way when they land: `html()`/`markdown()` need a sanitizer decision
(`symfony/html-sanitizer` is not a dependency of any bundle today), and the
`HtmlString` wrap is [`Twig\Markup`](../renames.md#idiom) here, which the
templates already pass through unescaped. Until then a format callback returning
`Markup` is the escape hatch, and it works today.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The cell renders a Twig partial where Filament emits an embedded view

Filament v5's `TextColumn` implements `HasEmbeddedView` and builds its cell in
`toEmbeddedHtml()` — raw PHP `ob_start()` output with the attribute bags
concatenated inline. Sylament declares a `$template` and renders through
[`ViewComponent::render()`](../../../packages/support/src/Components/ViewComponent.php),
the same way every other component in this port does; the contract itself is not
ported, for the reasons already recorded on
[schemas' `Text`](../schemas/text.md).

The consequence worth naming is what happens to `toEmbeddedHtml()`'s
**local-variable preamble**. Filament computes `$state = $this->getState()`,
`$state = $this->formatState($state)` and friends at the top of the method and
the markup below reads the locals. A Twig template has no such preamble: it reads
`component.x` per value, and anything it cannot get from a single getter call
goes through `getExtraTemplateData()` — the house rule recorded in
[CLAUDE.md](../../../CLAUDE.md). `getFormattedState()` below is that translation
for the one value the preamble computed in two steps.

### `getFormattedState()` replaces the embedded view's local preamble

`getFormattedState()` has no Filament counterpart: it is `formatState(getState())`
under a name, so a template can write `component.getFormattedState` instead of
composing the two calls itself. It is the same kind of member as the
[names with no Filament source](../renames.md#names-with-no-filament-source) —
Sylament-only, nothing to rename from — and it exists because a Twig template
reaching one getter is the shape this port renders in, where Filament reached a
local variable its embedded view had already computed.

It is not a semantic addition: the composition is Filament's, only the naming is
ours. A caller wanting one half still calls `getState()` or `formatState($x)`
directly, and both stay public.

### `formatStateUsing()` accepts a literal where Filament accepts only a closure

Filament types `formatStateUsing(?Closure $callback)` and then evaluates
`$this->formatStateUsing ?? $state`, so the *null* case falls back to the state
and a non-null case is always a closure. Sylament types the parameter `mixed` and
branches on null explicitly, which means `->formatStateUsing('fixed')` is legal
and pins every cell to the literal.

This aligns the pair with `state()` / `getStateUsing()` on
[`Column`](column.md#state-is-getstateusing-under-another-name), which Filament
itself types `mixed` — a literal-or-closure setter is the port's default shape,
and `evaluate()` returns a non-closure untouched, so the widening costs nothing
and the narrower type here was the odd one out. Every closure Filament accepts
still works unchanged; only previously-rejected literals are new.

### `hasStateFormatting()` has nothing to guard

Filament's `hasStateFormatting()` reports whether *any* of twelve formatting
properties is configured, so the embedded view can skip `formatState()` and emit
the raw state — a fast path over a method that would otherwise run a dozen
branches per cell. Eleven of those twelve properties do not exist here yet, and
`formatState()` is a null check plus one `evaluate()`, so the predicate would
guard nothing and its twelve-way condition would be a standing invitation to
forget a property when adding one.

Marked **skipped** rather than **todo** because it does not return with the
formatting setters either: the branch it optimises is a Twig template reading one
getter, not a PHP method with a dozen inline steps. If the pipeline ever grows
expensive enough to matter, the memo belongs on `getFormattedState()` — one
place, no property list to keep in sync.

### An iterable state renders as one comma-joined line, and nothing else

Filament's cell has four list shapes: a multi-value state collapses to a single
comma-joined item by default (`TextColumn.php:366-380`), `listWithLineBreaks()`
keeps the items as separate lines, `bulleted()` renders them as a `<ul>`, and
`limitList()` / `expandableLimitedList()` truncate the set with an expand
trigger. Sylament's partial ships the default arm only —
`state is iterable ? state|join(', ') : state` — and the other three have no
setter to reach them.

The collapse is the faithful half, and it is worth naming that it is Filament's
*default*, not a simplification: a `TextColumn` over `['symfony', 'forms']`
renders `symfony, forms` in both codebases. What the Twig one-liner cannot do is
branch on the three flags, and it does not pretend to — a `join` is the whole
behaviour, not a placeholder shaped like one.

Provisional. The three list setters are a **todo** row above, blocked on
[`separator()`](column.md), and when they land the join moves out of the template
into the formatting pipeline where the branch belongs — a template cannot express
per-item formatting, which `listWithLineBreaks()` and `bulleted()` both need
(Filament re-enters `$formatState` per item). Until then, a consumer wanting a
different join writes `formatStateUsing()`, which receives the raw iterable.
