---
description: 'The table''s empty state: its heading, description, icon and actions, the whole-block replacement, and the block the table partial renders'
ports:
  - vendor/filament/tables/src/Table/Concerns/HasEmptyState.php
  - vendor/filament/tables/resources/css/empty-state.css
owns:
  - packages/tables/src/Table/Concerns/HasEmptyState.php
---
# HasEmptyState (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`emptyStateHeading()` / `getEmptyStateHeading()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [semantics](#the-empty-state-texts-read-the-record-labels) |
| [`emptyStateDescription()` / `getEmptyStateDescription()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) + [the `setUp()` default](../../../packages/tables/src/Table.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php), [`Table::setUp()`](../../../vendor/filament/tables/src/Table.php) | done | — | [semantics](#the-empty-state-texts-read-the-record-labels) |
| [`emptyStateIcon()` / `getEmptyStateIcon()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-empty-state-icon-getter-returns-null-and-the-fallback-lives-in-the-template) |
| [`emptyState()` / `getEmptyState()` — the whole replacement block](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState::emptyState()`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-replacement-block-takes-markup-and-no-view) |
| [`emptyStateActions()` / `pushEmptyStateActions()` / `getEmptyStateActions()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [has-actions.md](has-actions.md#mergecachedflatactions-drops-the-overwrite-passthrough-and-its-one-caller-loops-cacheaction) |
| [The empty-state block](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css) | [`index.blade.php`'s `fi-ta-empty-state`](../../../vendor/filament/tables/resources/views/index.blade.php), [`empty-state.css`](../../../vendor/filament/tables/resources/css/empty-state.css) | done | — | [can-defer-loading.md](can-defer-loading.md#the-deferred-window-is-server-gated-and-client-closed) · [semantics](#the-empty-states-heading-tag-demotes-itself-when-the-table-has-no-heading) · [table.md](table.md#every-rule-in-tablecss-is-scoped-under-the-sy-ta-root-one-class-above-vendors) |

## Semantics

### The empty-state texts read the record labels

`getEmptyStateHeading()` falls back to `"No {plural record label}"`, and `Table::setUp()` stores a default `emptyStateDescription()` closure answering *"Create a `<record label>` to get started."* when the table carries an action named `create`, and `null` otherwise.
Both are Filament's interpolations, emitted as [English literals](#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships).

The heading says `No records` only where the table has no record class to read — a `records()` table, or one configured with neither; a `query()` table over `Song` says `No songs`, through [`plural()`](../support/helpers.md).
The description is a *stored closure* rather than a getter fallback, which is what makes `emptyStateDescription(null)` clear it: the setter overwrites the closure, so a caller can turn the default off, as in Filament.
`hasAction('create')` reads the [shared flat map](has-actions.md#mergecachedflatactions-drops-the-overwrite-passthrough-and-its-one-caller-loops-cacheaction), so a `create` action declared as a record, header, toolbar or empty-state action counts.

### The empty state's heading tag demotes itself when the table has no heading

`emptyStateHeadingTag` is `getHeadingTag(1)` when the table renders a heading of its own and plain `getHeadingTag()` when it does not, so the same empty state is an `<h3>` under a headed table and an `<h2>` under a headless one.
That reads like an inconsistency and is vendor's `$secondLevelHeadingTag`, computed at the top of the template the same way: the empty-state heading is the *second* level of the table's own outline, and where there is no first level there is nothing to sit under.
`rootHeadingLevel()` moves both together, so a table dropped inside a section can push its whole outline down one and keep the relationship.

Vendor feeds the computed tag to nine call sites; this partial feeds two, the empty state and the [group heading](grouping.md).
Of the seven it does not, one is the grid layout's group heading, which arrives with the layout columns; one is the [column manager](column-manager.md), whose panel takes a literal `headingLevel` prop; and five are the filters panel in its five layout positions, which takes a literal `headingLevel` of 3 ([a recorded rename](../renames.md#a-tag-name-template-prop-narrows-to-what-it-selects)) because it is [fixed chrome](has-filters.md#the-panel-takes-the-apply-actions-visibility-as-a-prop-and-its-groups-stay-keyed) rather than part of the table's outline.

## Divergences

### The copy is English and hardcoded, because no i18n layer ships

Filament's `getEmptyStateHeading()` falls back to `__('filament-tables::table.empty.heading', ['model' => $this->getPluralModelLabel()])`, a translated string interpolating the table's plural model label, and its default description is the same kind of lookup.
Sylament interpolates the same labels ([semantics](#the-empty-state-texts-read-the-record-labels)) in PHP and emits English directly.

The translation half is not a table concern: the port has [no i18n layer](../../internals/i18n.md), no bundle requires `symfony/translation` and no message catalogue ships, so there is nothing for a translation key to resolve against.
Every fixed string the toolkit renders sits in the same position, which is why the other packages' copy rows cite this entry.
It reverses in the toolkit-wide i18n pass, which turns each literal into a catalogue lookup without changing what it interpolates.

### The empty state icon getter returns null, and the fallback lives in the template

Filament types `getEmptyStateIcon(): string | BackedEnum` — never null — because the getter itself chains two fallbacks: `FilamentIcon::resolve(TablesIconAlias::EMPTY_STATE)` for the registered alias override, then `Heroicon::OutlinedXMark`.
Sylament types it `string|BackedEnum|Markup|null` and returns only what the user set.

The settable union is Filament's `string | BackedEnum | Htmlable | Closure | null` under the standard `Htmlable` → [`Twig\Markup`](../translations.md#idiom) translation, matching support's own [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php), so a raw `<svg>` handed to `emptyStateIcon()` survives the getter untouched.
Filament's own getter return type is narrower than its property union and cannot carry the `Htmlable` arm it accepts; the getter here admits `Markup` so the two agree.

The chain moves rather than disappearing: the `EMPTY_STATE` alias and the `heroicons:x-mark` default sit next to each other in the table partial, the same shape support's `Modal` close button uses.
Keeping them in PHP would split one two-step fallback across a getter and a template, and would make the getter lie about whether the user configured anything.
A caller asking *"did the user set an icon?"* gets a straight answer here; a caller wanting the rendered icon reads the partial's resolution.
This is permanent.

### The replacement block takes `Markup` and no view

Filament's `emptyState()` takes `View | Htmlable | Closure | null`, so a caller can hand it a Blade view.
Sylament's takes `Markup|Closure|null`: the `Htmlable` arm becomes [`Twig\Markup`](../translations.md#idiom), and the Blade `View` arm has no counterpart: a Twig template rendered to `Markup` takes its place.
The partial prints the block in place of the icon, heading, description and actions rather than beside them, as Filament's does.
