# ColorColumn (tables)

[`Sylament\Tables\Columns\ColorColumn`](../../../packages/tables/src/Columns/ColorColumn.php)
— a [`Column`](column.md) whose cell is swatches: one rounded square per state
value, painted with the value itself as a `background-color` and named for
assistive technology by the sanitized colour, optionally copyable. It ports
Filament's
[`Filament\Tables\Columns\ColorColumn`](../../../vendor/filament/tables/src/Columns/ColorColumn.php).
Everything [`Column`](column.md) carries — the name, the label, the record
binding, the state read, the placeholder, the tooltips, the closure injections —
is tracked there, not repeated here.

The class adds **no members of its own**: it is two traits and a template name,
exactly as upstream is two traits and a `toEmbeddedHtml()`. Its whole surface is
[`CanBeCopied`](../support/can-be-copied.md), `CanWrap` and the per-item
resolution the partial consumes, `getStateItems()`.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/ColorColumn.php`](../../../vendor/filament/tables/src/Columns/ColorColumn.php)
  — the class and its `toEmbeddedHtml()`, including the comment on the copyable
  swatch's missing name that this port acts on
- [`tables/resources/css/columns/color.css`](../../../vendor/filament/tables/resources/css/columns/color.css)
  — the stylesheet
  [`color-column.css`](../../../packages/tables/assets/src/styles/components/color-column.css)
  is the prefix-renamed port of
- [`support/src/SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php)
  — the `Str::sanitizeCssColor` macro
  [`sanitize_css_color()`](../support/helpers.md#sanitize_css_color-is-the-second-macro-that-lands-as-a-helper)
  ports

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries **diverges** → the permanent note in
[Divergences](#divergences), and **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`copyable()` / `copyableState()` / `copyMessage()` / `copyMessageDuration()` and their getters](../../../packages/support/src/Components/Concerns/CanBeCopied.php) | [`Support\Concerns\CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | done | — | [can-be-copied.md](../support/can-be-copied.md) |
| [`wrap()` / `canWrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`Support\Concerns\CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | [support/README.md](../support/README.md#supporting-surface) |
| [`getStateItems()` — the cell's swatch list](../../../packages/tables/src/Columns/ColorColumn.php) | — (Sylament-only; Filament resolves each item inside `toEmbeddedHtml()`) | done | — | [semantics](#the-copy-values-are-resolved-only-for-a-copyable-swatch) |
| [The sanitized `background-color`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`Str::sanitizeCssColor`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [helpers.md](../support/helpers.md#sanitize_css_color-is-the-second-macro-that-lands-as-a-helper) · [diverges](#a-backed-enum-state-is-unwrapped-before-the-sanitizer-sees-it) |
| [The `role="img"` swatch and its `aria-label`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [semantics](#an-unsanitizable-value-paints-nothing-and-a-plain-swatch-names-nothing) |
| [The copyable swatch — `role="button"`, `aria-label="Copy …"` and the clipboard controller](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [diverges](#the-copyable-swatch-is-named-where-filament-leaves-it-nameless) · [copyable.md](../support/copyable.md) |
| [`$template`](../../../packages/tables/src/Columns/ColorColumn.php) + [the partial it names](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [diverges](text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) |
| [The blank-state placeholder and the empty tooltip](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [column.md](column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) |
| [The per-item tooltip](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | [column.md](column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) |
| [`sy-inline`, the alignment class and the wrapper `extraAttributes()`](../../../packages/tables/templates/partials/ColorColumn/index.html.twig) | [`ColorColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ColorColumn.php) | done | — | — |
| [`color-column.css`](../../../packages/tables/assets/src/styles/components/color-column.css) | [`resources/css/columns/color.css`](../../../vendor/filament/tables/resources/css/columns/color.css) | done | — | [diverges](#the-copyable-focus-ring-lives-in-supports-sheet-rather-than-this-one) · [diverges](#the-stacked-on-mobile-rule-has-nothing-to-select) |

The surface rows are covered by the Unit
[`ColorColumnTest`](../../../packages/tables/tests/Unit/Columns/ColorColumnTest.php)
— the sanitizer over eight state shapes including the declaration-injection
attempt and the backed enum, the list state, the blank state, and the copy
values in all three arms (absent while the column is not copyable, the
`Copied` / 2000 ms defaults, and the configured trio) — and by the Integration
[`ColorColumnTest`](../../../packages/tables/tests/Integration/Columns/ColorColumnTest.php),
which renders columns inside a real table and reads the cell out of
`td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-color`: the painted and named
swatch, the unsanitizable one that paints and names nothing, the copyable one
carrying the clipboard controller's three values and the three activation
bindings with no server-rendered `tabindex`, the copyable **and** unsanitizable
one that paints nothing and still names itself, the wrapped list, the
placeholder, and the per-item `title`.

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

Every row above is settled — **done** — so none has a write-up here now. A row
moving to **in progress** or **blocked** grows one.

## Semantics

Behaviour that is faithful to Filament but easy to misread here.

### An unsanitizable value paints nothing, and a plain swatch names nothing

`sanitize_css_color()` answers `null` for anything that is not unambiguously a
CSS colour, and on a **plain** swatch the partial keys **three** attributes off
that one answer: the `style`, the `role` and the `aria-label`. So a cell holding
`red;position:fixed` renders `<span class="sy-ta-color-item"></span>` — an
empty, unnamed, uncoloured square — rather than a swatch announcing a value the
browser never painted. Naming a colour the page does not show would be worse
than staying silent, which is why the three travel together rather than the
`aria-label` falling back to the raw state.

A **copyable** swatch keys only the `style` off the sanitizer. Its `role` is
`button` for every value, because the copy control exists whatever the colour
is, so the name cannot be dropped with the paint: a nameless `role="button"`
carrying the clipboard controller is a focusable, operable control with no
accessible name. The unsanitizable arm therefore falls back to the colour-free
`Copy colour` — the verb without the value, since there is no value the page
shows to say.

The swatch still renders. It is not dropped from the list, so a row of five
values always shows five squares and a bad one reads as a gap in the row rather
than as a shorter row — the same choice
[`IconColumn`](icon-column.md#an-item-whose-icon-resolves-blank-contributes-nothing-its-name-included)
makes in the opposite direction, and for the opposite reason: an icon carries
its own name, a swatch is a fixed-size box.

### The copy values are resolved only for a copyable swatch

`getStateItems()` evaluates `copyableState`, `copyMessage` and
`copyMessageDuration` behind `isCopyable($value)`, so a non-copyable column
never runs those closures and the item's three copy keys are `null` rather than
the `Copied` / `2000` defaults `CanBeCopied`'s getters would answer. The keys
exist on every item because the partial reads them unconditionally; their being
`null` is what says *this swatch has no copy control*, and `copyable` is the
flag that decides.

`copyableState` falls back to the raw state stringified, so
`->copyable()` alone copies the colour as written in the record — not the
sanitized form. A value the sanitizer rejects is therefore still copyable, and
copies what the record holds, which is what a user clicking a broken swatch
wants to see.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The copyable swatch is named where Filament leaves it nameless

Upstream's own comment says the copyable swatch "is an interactive control that
needs separate treatment (an accessible name and keyboard operability), so it is
not named here" — and then nothing names it: the `role` and `aria-label` are
suppressed for exactly the copyable case, leaving a `<div>` with a click handler,
no role, no name and no keyboard path.

This port does the treatment the comment asks for. A copyable swatch renders
`role="button"`, `aria-label="Copy {colour}"` and the three
[clipboard](../support/copyable.md) activation bindings (`click`,
`keydown.enter`, `keydown.space`); focus is stamped client-side by the
controller, so no `tabindex` is served inside a cell that may sit within a
record `<a>`. A non-copyable swatch keeps `role="img"` and the bare colour as its
name.

The name is the **sanitized** colour wherever there is one, so the two arms'
labels differ only by the verb. Where there is none the arms part: the plain
swatch drops its `role` and its name together and goes inert, while the copyable
one keeps both and names itself `Copy colour`, because the control is still
there to operate.

### A backed enum state is unwrapped before the sanitizer sees it

`sanitize_css_color()` is typed `?string`, and Filament hands its macro the raw
state item — which fatals on a backed enum, since a `BackedEnum` has no string
cast. A colour held as an enum case is an ordinary Doctrine mapping, so
`getSanitizedColor()` reads `->value` off a `BackedEnum` first and hands the
sanitizer `null` for every other non-string. The enum's *value* still has to
survive the sanitizer, so nothing is loosened: `Brand = '#4f46e5'` paints,
`Brand = 'brand'` does not.

### The copyable focus ring lives in support's sheet rather than this one

Filament's `color.css` carries a `&.fi-copyable` branch with
`cursor-pointer outline-none focus-visible:ring-2` and the primary ring colours.
The port drops it, because support's
[`copyable.css`](../../../packages/support/assets/src/styles/components/copyable.css)
already states exactly those declarations on the bare `.sy-copyable` class the
swatch carries — the class the shared
[clipboard control](../support/copyable.md) is styled by everywhere it appears.
Re-stating them here would be a second definition of one rule, and the two would
drift.

### The stacked-on-mobile rule has nothing to select

Upstream's `.fi-ta-table-stacked-on-mobile & { @apply p-0 sm:px-3 sm:py-4 }` is
dropped: the stacked-on-mobile table layout is unported, so the ancestor class
that rule keys off is emitted nowhere. It returns with that layout, not before.
[`icon-column.css`](../../../packages/tables/assets/src/styles/components/icon-column.css)
drops the identical rule for the identical reason.
