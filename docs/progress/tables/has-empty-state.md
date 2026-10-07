---
description: 'The table''s empty state: heading, description, icon, actions, the replacement block, and the block the partial renders (draft)'
ports:
  - vendor/filament/tables/src/Table/Concerns/HasEmptyState.php
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
| [`emptyState()` / `getEmptyState()` — the whole replacement block](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState::emptyState()`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | — |
| [`emptyStateActions()` / `pushEmptyStateActions()` / `getEmptyStateActions()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | — |
| [The empty state block — the replacement short-circuit, the demoted heading and the actions, beside the loading block the not-yet-loaded arm splits off into](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css) | [`index.blade.php`'s `fi-ta-empty-state`](../../../vendor/filament/tables/resources/views/index.blade.php), [`empty-state.css`](../../../vendor/filament/tables/resources/css/empty-state.css) | done | — | [detail](can-defer-loading.md#the-deferred-window-is-server-gated-and-client-closed) · [semantics](#the-empty-states-heading-tag-demotes-itself-when-the-table-has-no-heading) |

## Semantics

### The empty-state texts read the record labels

`getEmptyStateHeading()` falls back to `"No {plural record label}"` and `Table::setUp()` — the first `setUp()` the class declares — stores a default `emptyStateDescription()` closure answering *"Create a `<record label>` to get started."* when the table carries an action named `create`, `null` otherwise.
Both are Filament's, minus the translation layer: Filament reads `__('filament-tables::table.empty.heading', ['model' => …])`, and no message catalogue ships in this port, so the interpolation happens in PHP and the string is English.
The i18n pass turns both into catalogue lookups without changing what they interpolate.

Two consequences worth naming.
The heading says `No records` only where the table has no record class to read — a `records()` table, or one configured with neither; a `query()` table over `Song` says `No songs`, through [`plural()`](../support/helpers.md).
And the description is a *stored closure* rather than a getter fallback, which is what makes `emptyStateDescription(null)` clear it: the setter overwrites the closure, so a caller can turn the default off, exactly as in Filament.
`hasAction('create')` reads the [shared flat map](has-actions.md#the-flat-action-map-keeps-one-trim-and-its-caller), so a `create` action declared as a record, header or toolbar action all count.

### The empty state's heading tag demotes itself when the table has no heading

`emptyStateHeadingTag` is `getHeadingTag(1)` when the table renders a heading of its own and plain `getHeadingTag()` when it does not — so the same empty state is an `<h3>` under a headed table and an `<h2>` under a headless one.
That reads like an inconsistency and is vendor's `$secondLevelHeadingTag` verbatim, computed at the top of the blade the same way: the empty-state heading is the *second* level of the table's own document outline, and where there is no first level there is nothing to sit under.
`rootHeadingLevel()` moves both together, so a table dropped inside a section can push its whole outline down one and keep the relationship.

Vendor computes the variable once at the top of the blade and feeds it to **nine** call sites; this partial feeds two — the empty state and the [group heading](grouping.md).
Of the seven it does not, one is the grid layout's *second* group heading, which arrives with the layout columns; one is the [column manager](column-manager.md), whose panel takes a literal `headingLevel` prop of its own; and the remaining **five are the filters panel**, in all five of its layout positions: vendor hands the computed tag to `<x-filament-tables::filters>` every time.
Ours does not, and that is the deliberate part — the panel takes a literal `headingLevel` of 3 ([`headingTag` → `headingLevel`](../renames.md#renames) is a recorded rename), because it is [fixed chrome](has-filters.md#the-panel-is-fixed-chrome-with-no-action-objects-behind-it) rather than part of the table's outline.
So the empty state is the one place here that reads the demoting tag at all.

## Divergences

### The copy is English and hardcoded, because no i18n layer ships

Filament's `getEmptyStateHeading()` falls back to `__('filament-tables::table.empty.heading', ['model' => $this->getPluralModelLabel()])` — a *translated* string interpolating the table's plural model label.
Sylament interpolates the same label ([semantics](#the-empty-state-texts-read-the-record-labels)) and emits English directly.

Only the translation half is missing, and it is not a table concern: **i18n is not wired anywhere in the port** — no `translation` component is a dependency of any bundle and no message catalogue ships, so there is nothing for a translation key to resolve against.
Every fixed string the toolkit renders sits in the same position, which is why this section is the anchor the other packages' copy rows cite.

The literals read correctly in English rather than making an API claim.
They reverse in one step: an i18n pass turns each into a catalogue lookup.
Until then a consumer wanting other copy sets it explicitly, which is fully ported everywhere it applies.

### The empty state icon getter returns null, and the fallback lives in the template

Filament types `getEmptyStateIcon(): string | BackedEnum` — never null — because the getter itself chains two fallbacks: `FilamentIcon::resolve(TablesIconAlias::EMPTY_STATE)` for the registered alias override, then `Heroicon::OutlinedXMark`.
Sylament types it `string|BackedEnum|Markup|null` and returns only what the user set.

Only the **nullability** differs.
The settable union is Filament's `string | BackedEnum | Htmlable | Closure | null` under the standard `Htmlable` → [`Twig\Markup`](../translations.md#idiom) translation, matching support's own [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) — so a raw `<svg>` handed to `emptyStateIcon()` survives the getter untouched rather than being escaped or rejected.
Filament's own getter return type (`string | BackedEnum`) is narrower than its property union and could not actually carry the `Htmlable` arm it accepts; the getter here admits `Markup` so the two agree.

The chain is not dropped, it moves: the alias hook and the `heroicons:x-mark` default sit next to each other in the table template, which is where the rest of the toolkit already puts them — the same shape support's `Modal` close button uses.
Keeping them in PHP would split one two-step fallback across a getter and a template, and would make the getter lie about whether the user configured anything.
A caller asking *"did the user set an icon?"* gets a straight answer here; a caller wanting the rendered icon reads the template's resolution.
This is permanent, and it is the general policy for icon getters in this port rather than a table-specific choice.
