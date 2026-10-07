---
description: One tab of a Tabs component, with its label, icon and badge on the nav side and a child schema rendered as the panel, plus support's badge concerns.
ports:
  - vendor/filament/schemas/src/Components/Tabs/Tab.php
  - vendor/filament/support/src/Concerns/HasBadge.php
  - vendor/filament/support/src/Concerns/HasBadgeTooltip.php
owns:
  - packages/schemas/src/Components/Tabs/
  - packages/schemas/templates/partials/Tab/
  - packages/support/src/Components/Concerns/HasBadge.php
  - packages/support/src/Components/Concerns/HasBadgeTooltip.php
---
# Tabs\Tab (schemas)

[`Sylament\Schemas\Components\Tabs\Tab`](../../../packages/schemas/src/Components/Tabs/Tab.php) is one tab of a [`Tabs`](tabs.md): a label, an optional icon and badge on the nav side, and a child schema rendered as the panel.
It implements `CanConcealComponents`, because an inactive panel is in the DOM but visually hidden, so the fields inside it must not enforce native constraints, and composes support's `HasBadge`, `HasBadgeTooltip`, `HasIcon` and `HasIconPosition` with schemas' `HasLabel`.

It also carries the list-page seam Filament uses to scope a tab's records: `query()`/`modifyQueryUsing()`/`modifyQuery()` and `excludeQueryWhenResolvingRecord()`.
Their consumer is panels: [panels' `HasTabs`](../panels/has-tabs.md) calls `modifyQuery()` from `modifyQueryWithActiveTab()` ([how it treats an unknown key](../panels/has-tabs.md#a-blank-key-and-an-unknown-key-are-both-no-ops-and-that-is-what-heals-a-bookmark)), and reads `shouldExcludeQueryWhenResolvingRecord()` to decide whether a single-record lookup takes the scope too.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `label()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [`Tab::make()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [`setUp()` key closure — `slug(label)::[statePath::]tab`, non-inheritable](../../../packages/schemas/src/Components/Tabs/Tab.php) | [`Tab::setUp()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [tabs.md](tabs.md#the-non-inheritable-key-is-what-keeps-a-childs-absolute-key-unchanged) |
| [`getAllColumns()` container fallback](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [semantics](#the-column-fallback-is-what-makes-a-tab-transparent-to-the-grid) |
| [`canConcealComponents()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [`Contracts/CanConcealComponents`](../../../vendor/filament/schemas/src/Components/Contracts/CanConcealComponents.php), deprecated | done | — | [semantics](#an-inactive-panel-is-hidden-not-removed) · [diverges](can-be-concealed.md#the-port-keeps-the-concealing-contract-that-filament-deprecates) |
| [`badge()` / `badgeColor()` / `hasBadge()` (support's `HasBadge`)](../../../packages/support/src/Components/Concerns/HasBadge.php) | [`Concerns/HasBadge`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | done | — | [semantics](#getbadge-casts-an-evaluated-badge-to-string) |
| `indicator()` / `indicatorColor()`, deprecated for `badge()` / `badgeColor()` | [`Concerns/HasBadge`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | skipped | — | [deprecated](../README.md#statuses) |
| [`badgeTooltip()` (support's `HasBadgeTooltip`)](../../../packages/support/src/Components/Concerns/HasBadgeTooltip.php) | [`Concerns/HasBadgeTooltip`](../../../vendor/filament/support/src/Concerns/HasBadgeTooltip.php) | done | — | [diverges](#the-badge-tooltip-renders-as-a-native-title) |
| [`badgeIcon()` / `getBadgeIcon()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [`badgeIconPosition()` / `getBadgeIconPosition()` — defaults to `Before`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [`deferBadge()` / `isBadgeDeferred()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [tabs.md](tabs.md#deferred-badges-are-keyed-by-tab-key-not-by-array-index) · [diverges](#a-deferred-badge-renders-eagerly-in-server-mode) |
| [`icon()` / `iconPosition()` (support's concerns)](../../../packages/support/src/Components/Concerns/HasIcon.php) | [`Concerns/HasIcon`, `HasIconPosition`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | — |
| [`query()` / `modifyQueryUsing()` / `modifyQuery()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same three](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [diverges](#modifyquery-mutates-the-builder-in-place-and-discards-the-return) |
| [`excludeQueryWhenResolvingRecord()` / `shouldExcludeQueryWhenResolvingRecord()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [Twig render — panel, `role="tabpanel"`, active class](../../../packages/schemas/templates/partials/Tab/index.html.twig) | [`Tab::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [diverges](#the-panel-points-aria-labelledby-at-its-tab-button) |
| [Empty child schema renders nothing](../../../packages/schemas/templates/partials/Tab/index.html.twig) | [the `empty($childSchema->getComponents())` early return](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [semantics](#an-empty-tab-renders-no-panel-but-keeps-its-button) |

## Semantics

### An inactive panel is hidden, not removed

`canConcealComponents()` is unconditionally true: in client mode every tab's panel is in the DOM and the inactive ones are hidden by CSS (`invisible absolute h-0`), so their inputs still post with the form and a `required` one would block native submission from a tab the user cannot see.
The [concealing contract](can-be-concealed.md) is what lets a field ask whether it is concealed and drop its native constraint attributes; server-side validation is untouched either way.

### The column fallback is what makes a tab transparent to the grid

`getAllColumns()` returns the **container's** columns whenever the tab sets none of its own, instead of the base's `default => 1`.
Without it every tab would stack its children in one column and wrapping a two-column form in tabs would silently reflow it.
The override is Filament's, verbatim.

### `getBadge()` casts an evaluated badge to string

`badge(3)` stores `'3'`, and `getBadge()` casts a non-null value that a badge closure returns the same way, so `badge(fn () => count($items))` reads back as a string.
Filament's `getBadge()` has no cast and relies on its file's non-strict mode to coerce the number to the `?string` return type; under `declare(strict_types=1)` that return would throw a `TypeError`.
A closure returning an array raises PHP's "Array to string conversion" warning, an `ErrorException` under Symfony's debug error handler, where Filament throws a `TypeError`.
`evaluate()`'s template types the value `?string`, so PHPStan leaves no branch to guard a non-string.

### An empty tab renders no panel but keeps its button

Filament's `Tab::toEmbeddedHtml()` returns `''` when the child schema has no components; ours guards the whole panel on `hasContent`, computed from `getComponents(withHidden: false)`.
Either way the tab still appears in the nav and is still selectable, with the panel simply absent, which is what makes a tab whose content is entirely hidden by conditions degrade quietly.

## Divergences

### The panel points `aria-labelledby` at its tab button

Filament emits `aria-labelledby="{id}"` on the panel, its own id, which names the panel by itself and tells a screen reader nothing.
Ours emits `aria-labelledby="{id}-tab"`, matching the `id="{id}-tab"` / `aria-controls="{id}"` pair the nav button carries, so the tablist/tabpanel relationship is complete in both directions.
A one-line vendor fidelity fix, recorded rather than silently corrected.

### `modifyQuery()` mutates the builder in place and discards the return

Filament's Eloquent builder is chainable, so `modifyQuery()` returns `$this->evaluate(...) ?? $query`.
Doctrine's `QueryBuilder` mutates in place, and tables makes the same call: [`InteractsWithTableQuery::apply()`](../../../packages/tables/src/Filters/Concerns/InteractsWithTableQuery.php) evaluates the callback and returns the builder it was handed.
`Tab::modifyQuery()` follows it: the callback's return value is discarded, and a callback that returns a *different* builder is a no-op rather than a silent swap.

### The badge tooltip renders as a native `title`

Filament binds `badgeTooltip` to tippy through the badge component's `tooltip` prop; the port has no tooltip library, so it renders a native `title` attribute, the house tooltip shape, as [`icon.md`](icon.md) does.

### A deferred badge renders eagerly in server mode

`deferBadge()` exists so a costly badge count is fetched after the first paint, through the `getDeferredTabBadges()` seam the tabs controller calls.
Filament's `livewireProperty()` arm defers too: it renders a placeholder and fetches `getDeferredTabBadges()` from Alpine.
In [`liveComponentProperty()`](tabs.md#server-mode-is-the-livewireproperty-arm-translated-to-ux-live) mode the port puts no controller on the element, so nothing would ever fill the placeholder: the badge renders eagerly instead, exactly as a non-deferred one.
