# Tabs\Tab (schemas)

[`Sylament\Schemas\Components\Tabs\Tab`](../../../packages/schemas/src/Components/Tabs/Tab.php)
is one tab of a [`Tabs`](tabs.md): a label, an optional icon and badge on the nav side, and
a child schema rendered as the panel. It implements `CanConcealComponents` — an inactive
panel is in the DOM but visually hidden, so the fields inside it must not enforce native
constraints — and composes support's `HasBadge`, `HasBadgeTooltip`, `HasIcon` and
`HasIconPosition` with schemas' `HasLabel`.

It also carries the list-page seam Filament uses to scope a tab's records:
`query()`/`modifyQueryUsing()`/`modifyQuery()` and
`excludeQueryWhenResolvingRecord()`. Their consumer arrived with M4 · 3 —
[panels' `HasTabs`](../panels/has-tabs.md) calls `modifyQuery()` from
`modifyQueryWithActiveTab()`, and reads `shouldExcludeQueryWhenResolvingRecord()`
to decide whether a single-record lookup takes the scope too — so the seam is
[live rather than waiting](../panels/has-tabs.md#a-blank-key-and-an-unknown-key-are-both-no-ops-and-that-is-what-heals-a-bookmark).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Tabs/Tab.php`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php)
  — the API and the two `toEmbeddedHtml()` arms

## Surface

Every point of this component. _Blocked by_ names what has to land first. _Detail_ carries
a **diverges** link to the note in [Divergences](#divergences), a **semantics** link to
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `label()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [`Tab::make()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [`setUp()` key closure — `slug(label)::[statePath::]tab`, non-inheritable](../../../packages/schemas/src/Components/Tabs/Tab.php) | [`Tab::setUp()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [tabs.md](tabs.md#the-non-inheritable-key-is-what-keeps-a-childs-absolute-key-unchanged) |
| [`getAllColumns()` container fallback](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [semantics](#the-column-fallback-is-what-makes-a-tab-transparent-to-the-grid) |
| [`canConcealComponents()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [semantics](#an-inactive-panel-is-hidden-not-removed) |
| [`badge()` / `badgeColor()` (support's `HasBadge`)](../../../packages/support/src/Components/Concerns/HasBadge.php) | [`Concerns/HasBadge`](../../../vendor/filament/support/src/Concerns/HasBadge.php) | done | — | — |
| [`badgeTooltip()` (support's `HasBadgeTooltip`)](../../../packages/support/src/Components/Concerns/HasBadgeTooltip.php) | [`Concerns/HasBadgeTooltip`](../../../vendor/filament/support/src/Concerns/HasBadgeTooltip.php) | done | — | [diverges](#the-badge-tooltip-renders-as-a-native-title) |
| [`badgeIcon()` / `getBadgeIcon()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [`badgeIconPosition()` / `getBadgeIconPosition()` — defaults to `Before`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [`deferBadge()` / `isBadgeDeferred()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [tabs.md](tabs.md#deferred-badges-are-keyed-by-tab-key-not-by-array-index) · [diverges](#a-deferred-badge-renders-eagerly-in-server-mode) |
| [`icon()` / `iconPosition()` (support's concerns)](../../../packages/support/src/Components/Concerns/HasIcon.php) | [`Concerns/HasIcon`, `HasIconPosition`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | — |
| [`query()` / `modifyQueryUsing()` / `modifyQuery()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same three](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [diverges](#modifyquery-mutates-the-builder-in-place-and-discards-the-return) |
| [`excludeQueryWhenResolvingRecord()` / `shouldExcludeQueryWhenResolvingRecord()`](../../../packages/schemas/src/Components/Tabs/Tab.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | — |
| [Twig render — panel, `role="tabpanel"`, active class](../../../packages/schemas/templates/partials/Tab/index.html.twig) | [`Tab::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [diverges](#the-panel-points-aria-labelledby-at-its-tab-button) |
| [Empty child schema renders nothing](../../../packages/schemas/templates/partials/Tab/index.html.twig) | [the `empty($childSchema->getComponents())` early return](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | done | — | [semantics](#an-empty-tab-renders-no-panel-but-keeps-its-button) |

Covered by [`TabTest`](../../../packages/schemas/tests/Unit/Components/Tabs/TabTest.php)
(the key, the concealment contract, the column fallback, the badge/icon setters and their
`badge`-injecting closures, the query seam over a real Doctrine `QueryBuilder`) and by
[`TabsRenderTest`](../../../packages/schemas/tests/Integration/Components/TabsRenderTest.php)
for everything the panel and the nav item render.

## Semantics

Faithful ports subtle enough that re-deriving them from the source would plausibly get
them wrong.

### An inactive panel is hidden, not removed

`canConcealComponents()` is unconditionally true: in client mode every tab's panel is in
the DOM and the inactive ones are hidden by CSS (`invisible absolute h-0`), so their inputs
still post with the form and a `required` one would block native submission from a tab the
user cannot see. The contract is what lets a field ask whether it is concealed and drop its
native constraint attributes; server-side validation is untouched either way.

### The column fallback is what makes a tab transparent to the grid

`getAllColumns()` returns the **container's** columns whenever the tab sets none of its
own, instead of the base's `default => 1`. Without it every tab would stack its children in
one column and wrapping a two-column form in tabs would silently reflow it. The override is
Filament's, verbatim.

### An empty tab renders no panel but keeps its button

Filament's `Tab::toEmbeddedHtml()` returns `''` when the child schema has no components;
ours guards the whole panel on `hasContent`, computed from
`getComponents(withHidden: false)`. Either way the tab still appears in the nav and is
still selectable — the panel is simply absent — which is what makes a tab whose content is
entirely hidden by conditions degrade quietly.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The panel points `aria-labelledby` at its tab button

Filament emits `aria-labelledby="{id}"` on the panel — its own id — which names the panel
by itself and tells a screen reader nothing. Ours emits `aria-labelledby="{id}-tab"`,
matching the `id="{id}-tab"` / `aria-controls="{id}"` pair the nav button carries, so the
tablist/tabpanel relationship is complete in both directions. A one-line vendor fidelity
fix, recorded rather than silently corrected.

### `modifyQuery()` mutates the builder in place and discards the return

Filament's Eloquent builder is chainable, so `modifyQuery()` returns
`$this->evaluate(...) ?? $query`. Doctrine's `QueryBuilder` mutates in place, and the port
already made this call one directory over — tables'
[`InteractsWithTableQuery::apply()`](../../../packages/tables/src/Filters/Concerns/InteractsWithTableQuery.php)
evaluates the callback and returns the builder it was handed. `Tab::modifyQuery()` follows
it: the callback's return value is discarded, and a callback that returns a *different*
builder is a no-op rather than a silent swap.

### The badge tooltip renders as a native `title`

Filament binds `badgeTooltip` to tippy through the badge component's `tooltip` prop; the
port has no tooltip library, so it renders a native `title` attribute — the house tooltip
shape, the same call [`icon.md`](icon.md) made.

### A deferred badge renders eagerly in server mode

`deferBadge()` exists so a costly badge count is fetched after the first paint, through the
`getDeferredTabBadges()` seam the tabs controller calls. In
[`liveComponentProperty()`](tabs.md#server-mode-is-the-livewireproperty-arm-translated-to-ux-live)
mode there is no controller on the element, so nothing would ever fill the placeholder:
the badge renders eagerly instead, exactly as a non-deferred one. Filament reaches the same
place by a different route — its server-driven arm has no deferred-badge branch at all.

_See also: the global [rename table](../renames.md) — the `toEmbeddedHtml()` → Twig render
and the Eloquent → Doctrine translation are systematic translations recorded there, not
per-component divergences._
