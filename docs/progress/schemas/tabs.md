---
description: The Tabs layout component and its CanPersistTab concern, in client mode (a Stimulus class swap) and server mode (a host live property).
ports:
  - vendor/filament/schemas/src/Components/Tabs.php
  - vendor/filament/schemas/src/Components/Concerns/CanPersistTab.php
  - vendor/filament/schemas/resources/css/components/tabs.css
owns:
  - packages/schemas/assets/src/styles/components/tabs.css
  - packages/schemas/src/Components/Concerns/CanPersistTab.php
  - packages/schemas/src/Components/Tabs.php
  - packages/schemas/templates/partials/Tabs/
---
# Tabs (schemas)

[`Sylament\Schemas\Components\Tabs`](../../../packages/schemas/src/Components/Tabs.php) is the tabbed layout component: it takes [`Tab`](tab.md) children, renders support's [tab chrome](../support/tabs.md) as its nav and each tab's child schema as a panel, and hands the switching to the [`sylament--schemas--tabs`](schemas-bundle.md#surface) Stimulus controller.
Its single-use concern [`CanPersistTab`](../../../packages/schemas/src/Components/Concerns/CanPersistTab.php) folds into this file.

Two arms, and which one renders is decided by `liveComponentProperty()`.
**Client mode** (the default) renders every visible tab's panel, marks the one `getActiveTab()` picks active, and attaches the controller — switching is then a class swap in the browser, no round-trip.
**Server mode** (`liveComponentProperty('activeTab')`) renders no controller at all: each tab button writes the host's live property through UX Live's `live#update`, and only the active tab's panel is rendered.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `label()`](../../../packages/schemas/src/Components/Tabs.php) | [`Tabs::make()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | — |
| [`setUp()` key closure — `slug(label)::[statePath::]tabs`, non-inheritable](../../../packages/schemas/src/Components/Tabs.php) | [`Tabs::setUp()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [semantics](#the-non-inheritable-key-is-what-keeps-a-childs-absolute-key-unchanged) |
| [`tabs()`](../../../packages/schemas/src/Components/Tabs.php) | [`Tabs::tabs()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | — |
| [`activeTab()` / `getActiveTab()`](../../../packages/schemas/src/Components/Tabs.php) | [`Tabs::getActiveTab()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [diverges](#the-query-string-arm-compares-the-key-the-url-actually-carries) |
| [`getActiveTabKey()`](../../../packages/schemas/src/Components/Tabs.php) — Sylament-only | — | done | — | [diverges](#two-sylament-only-accessors-carry-what-alpine-read-from-the-client) |
| [`getActiveLiveComponentTab()`](../../../packages/schemas/src/Components/Tabs.php) — Sylament-only | — | done | — | [diverges](#two-sylament-only-accessors-carry-what-alpine-read-from-the-client) |
| [`persistTabInQueryString()` / `getTabQueryStringKey()` / `isTabPersistedInQueryString()`](../../../packages/schemas/src/Components/Tabs.php) | [same three](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [diverges](#the-query-string-arm-compares-the-key-the-url-actually-carries) · [diverges](#the-query-string-arm-resets-on-the-first-live-round-trip-and-is-left-that-way) |
| [`persistTab()` / `isTabPersisted()` (`CanPersistTab`)](../../../packages/schemas/src/Components/Concerns/CanPersistTab.php) | [`Concerns/CanPersistTab.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanPersistTab.php) | done | — | [diverges](#canpersisttabs-property-is-protected-and-the-storage-key-drops-alpines-prefix) |
| [`liveComponentProperty()` / `getLiveComponentProperty()`](../../../packages/schemas/src/Components/Tabs.php) | [`livewireProperty()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [translations.md](../translations.md#idiom) · [semantics](#server-mode-is-the-livewireproperty-arm-translated-to-ux-live) |
| [`configureChildSchema()` re-keying in server mode](../../../packages/schemas/src/Components/Tabs.php) | [`getDefaultChildComponents()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [semantics](#the-re-keying-hook-must-run-before-any-key-is-read) |
| [`scrollable()` / `isScrollable()`](../../../packages/schemas/src/Components/Tabs.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | — |
| [`vertical()` / `isVertical()`](../../../packages/schemas/src/Components/Tabs.php) | [same pair](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | — |
| [`contained()` (support's `CanBeContained`)](../../../packages/support/src/Components/Concerns/CanBeContained.php) | [`Concerns/CanBeContained`](../../../vendor/filament/support/src/Concerns/CanBeContained.php) | done | — | — |
| [`getDeferredTabBadges()` — `#[ExposedLiveComponentMethod]`](../../../packages/schemas/src/Components/Tabs.php) | [`Tabs::getDeferredTabBadges()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [diverges](#deferred-badges-are-keyed-by-tab-key-not-by-array-index) |
| [`hasDeferredBadges()`](../../../packages/schemas/src/Components/Tabs.php) | [same](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | — |
| [`getTabs()`](../../../packages/schemas/src/Components/Tabs.php) | [the `array_filter` inside `toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [semantics](#gettabs-filters-hidden-tabs-out-so-every-index-is-a-visible-position) |
| [Twig render — client arm](../../../packages/schemas/templates/partials/Tabs/index.html.twig) | [`Tabs::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [diverges](#the-active-tab-is-server-rendered-and-then-owned-by-the-controller) · [diverges](#the-tab-wiring-wins-over-a-tabs-extra-attributes-where-filaments-extras-win) |
| [Twig render — server arm](../../../packages/schemas/templates/partials/Tabs/index.html.twig) | [`toEmbeddedHtmlForLivewireProperty()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [semantics](#server-mode-is-the-livewireproperty-arm-translated-to-ux-live) · [semantics](#ux-live-injects-a-value-attribute-onto-every-server-mode-tab-button) · [diverges](#a-deferred-badge-renders-eagerly-in-server-mode) |
| [`scrollable(false)` overflow dropdown](../../../packages/schemas/templates/partials/Tabs/index.html.twig) | [the dropdown branch of `toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [diverges](#the-overflow-dropdown-rides-on-supports-dropdown-and-renders-its-active-trigger-server-side) |
| [`sy-cloak` while persisting](../../../packages/schemas/templates/partials/Tabs/index.html.twig) | [`x-cloak` on the nav](../../../vendor/filament/schemas/src/Components/Tabs.php) | done | — | [diverges](#cloaking-is-limited-to-persisttab-so-no-js-still-shows-a-tab) |
| [`tabs.css`](../../../packages/schemas/assets/src/styles/components/tabs.css) | [`components/tabs.css`](../../../vendor/filament/schemas/resources/css/components/tabs.css) | done | — | — |
| `startRenderHooks()` / `endRenderHooks()` / `getStartRenderHooks()` / `getEndRenderHooks()` / `getRenderHookScopes()` | [`Tabs.php`](../../../vendor/filament/schemas/src/Components/Tabs.php) | blocked | a render-hook registry | [detail](#the-render-hook-trio-waits-on-the-render-hook-design) |
| `HasExtraAlpineAttributes` | [`Concerns/HasExtraAlpineAttributes`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](#alpine-only-surface-is-skipped-not-deferred) · [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| `getTabVisibilityJs()` (`hiddenJs`/`visibleJs` per tab) | [the closure in `toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Tabs.php) | skipped | — | [diverges](#alpine-only-surface-is-skipped-not-deferred) |

## Details

### The render-hook trio waits on the render-hook design

`startRenderHooks()`, `endRenderHooks()` and `getRenderHookScopes()` let a Filament plugin inject markup at named points around the tabs, and `getStartRenderHooks()` / `getEndRenderHooks()` read the first two back for the render.
Nothing in this port has a render-hook registry, a `HasRenderHookScopes` contract or a Twig-side rendering point for one, so porting the setters would ship an API that stores strings and drops them.
Next step: port them with the registry, not before.

## Semantics

### Server mode is the `livewireProperty` arm translated to UX Live

`liveComponentProperty('activeTab')` is Filament's `livewireProperty()`, and the translation is the systematic Livewire → LiveComponent one, not a rename.
Filament's tab button carries `wire:click="$set('activeTab', 'all')"`; ours carries `data-action="live#update" data-model="activeTab" data-value="all"`, because UX Live's `getValueFromElement()` reads `data-value` off a non-input element, which is what `$set()` did.
`data-loading="addAttribute(disabled)"` is the `wire:loading.attr` twin.
The label falls back to the host's `generateTabLabel($key)` when it has that method (a `method_exists` probe, as Filament's), and each `Tab` is re-keyed to its array key so the value written into the property matches.
Its consumers are [panels' `HasTabs`](../panels/has-tabs.md), which builds this mode (`liveComponentProperty('activeTab')`, `contained(false)`) for a listing's record filters and answers the `generateTabLabel()` probe, and panels' `HasRelationManagers`, which binds `activeRelationManager` the same way.

### The non-inheritable key is what keeps a child's absolute key unchanged

`key(..., isInheritable: false)` means the tabs' own key names *it* but never joins its descendants' absolute keys: a `Text` keyed `inner` inside a tab of a root schema keyed `schema` stays `schema.inner`, not `schema.article::tabs.content::tab.inner`.
That is what lets an existing form be wrapped in tabs without renaming a single state path or breaking a `getComponent()` lookup, which the demo's article form relies on.
Both `Tabs` and `Tab` do it, as in Filament, and `TabsTest` pins it.

### The re-keying hook must run before any key is read

Server mode re-keys each `Tab` to its array key.
Ours does it in `configureChildSchema()`, Filament in `getDefaultChildComponents()` — different hooks, same requirement and the same reason: [`HasKey::key()`](../../../packages/schemas/src/Components/Concerns/HasKey.php) does **not** flush a cached absolute key, so re-keying a component something else has already asked for a key returns the stale one forever.
It is safe here only because both hooks run while the child schema is being built, before anything can have read a tab's key.
Moving the loop later — into `getExtraTemplateData()`, say — would look equivalent and silently break nested action keys.

The two hooks differ in what they reach, and that decides where the loop reads its tabs from.
Filament's hook *returns* the component array, so mutating it in place hits the objects the schema then receives.
Ours is handed the finished `Schema`, and the evaluation that produced its components already happened in [`HasChildComponents::getChildSchema()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php).
So the loop reads `$schema->getComponents()` — which preserves the source array keys — and never re-evaluates `$childComponents`: a second evaluation of a `Closure`-valued `tabs()` builds a *fresh* set of tabs, keys those, and throws them away, leaving the rendered tabs with their label-derived keys and no tab matching the host property.
Pinned by `TabsTest`'s closure-valued case.

One deliberate delta from vendor in the same loop: Filament's `$tab->key(strval($tabKey))` takes `key()`'s default `$isInheritable = true`, so in its `livewireProperty` mode a tab's descendants are re-addressed as `{root}.{arrayKey}.{child}`; ours re-keys with `isInheritable: false`, so descendant keys stay `{root}.{child}` in both modes — the same rule that applies everywhere else (a child's absolute key never moves because a tab was renamed), and the relation managers' tabs, which use this mode, inherit it.

### `getTabs()` filters hidden tabs out, so every index is a visible position

`getActiveTab()` is a 1-based index *into the visible tabs*, so `activeTab(2)` means "the second tab you can see", in Filament and here alike.
Filament gets that from `getComponents()`'s default, which drops hidden children and re-indexes.
The port's [`HasComponents::getComponents()`](../../../packages/schemas/src/Concerns/HasComponents.php) defaults the other way, `withHidden: true`, so `getTabs()` asks for `withHidden: false` explicitly and is the one place the visible list is computed, for the active-tab arms, the deferred badges and the render alike.

### UX Live injects a `value` attribute onto every server-mode tab button

`data-model` passed to a Twig component is read by ux-live-component's [`DataModelPropsSubscriber`](../../../vendor/symfony/ux-live-component/src/EventListener/DataModelPropsSubscriber.php), which resolves the named property on the parent live component and adds it to the child's mount data under the binding's child name — `value` by default.
Support's `TabsItem` declares no `value` prop, so it lands as a `value="all"` attribute on every server-mode tab button.
It is inert (a `type="button"` element submits nothing) and the tabs' own wiring reads `data-value`, so it is left alone rather than papered over with a swallowing prop on a support component; `TabsRenderTest` pins it so the extra attribute reads as a known consequence.

## Divergences

### The active tab is server-rendered and then owned by the controller

Filament renders no active state at all: `x-cloak` hides the nav, Alpine picks the tab on init and every class comes from an `x-bind`.
Ours renders the initial state — `sy-active` and `aria-selected` on the tab `getActiveTab()` picks, `sy-active` on its panel — and the Stimulus controller takes ownership from `connect()` on, its class writes recorded by UX Live's `ExternalMutationTracker` and replayed across morphs.
Two consequences worth stating: without JavaScript the component still shows one tab and every panel's inputs stay in the form (Filament shows nothing), and a tab switch is a class swap with no server round-trip in either port.

### Cloaking is limited to `persistTab()`, so no-JS still shows a tab

`sy-cloak` (server-rendered, controller-removed) goes on the root **only** in client mode when `persistTab()` is on *and* the component has an id — the one case where the controller may switch away from the server's choice on connect, so showing the server's tab first would flash.
Filament cloaks its nav always.
This is the same call support's [`Section`](../support/section.md) made for `persistCollapsed`.

### `CanPersistTab`'s property is protected, and the storage key drops Alpine's prefix

Filament declares `public bool | Closure $isTabPersisted` — the only public property on the concern — and nothing reads it from outside.
Ours is `protected`, like every other condition property in this port.
The stored key is `tabs-{id}` rather than Alpine's `_x_{id}`, the same call the section controller made for its collapse state.
One timing delta rides along: the controller writes storage from `#setTab()` only, so a tab the **query string** picked on connect is not persisted until the first click, where Alpine's `$persist` writes on every assignment to `tab`, `init()`'s included.
It is observable only with both arms on at once (`persistTab()` *and* `persistTabInQueryString()`), and then only until that first click.

### The query-string arm compares the key the URL actually carries

Filament's `getActiveTab()` compares the query value with `$tab->getId()` — the **absolute** key — while its JavaScript writes `getKey(isAbsolute: false)` into the URL, so whenever a keyed container sits above the tab (a page form's root schema, say) the server arm never matches and the first tab renders until Alpine corrects it.
Ours compares `getKey(isAbsolute: false)`, the value `data-tab-key` and the URL both carry, so the server renders the right tab on the first paint.
The read goes through a nullable `RequestStack` injected by the `#[Required]` setter (the `NavigationItem` shape): a component built with `new` has none and skips the read, which costs only that first-paint optimisation because the controller re-reads the query string on connect anyway.
The value is read off `query->all()` rather than `query->get()`: `InputBag::get()` throws on an array-shaped `?tab[]=x`, and a hand-edited URL must not take the whole render down.

### The query-string arm resets on the first live round trip, and is left that way

`getActiveTab()`'s query-string read goes through `RequestStack::getMainRequest()`, and on a **live** round trip the main request is the live route's — UX posts to `/_components/…`, and the page's query string is not carried onto it.
So the read finds no `tab` key, falls through to `$this->activeTab`, and the server renders the *declared* tab rather than the one the URL named.

Whether that is visible depends on what the controller has recorded.
On a first paint the controller's `#apply()` writes the same classes and `aria-selected` values the server already rendered, so `ExternalMutationTracker` records nothing and has nothing to replay — which means the first live round trip after a `?tab=`-restored load repaints the strip on tab 1.
A user who has clicked a tab in between is unaffected: that click is a recorded mutation and survives the morph ([the ownership rule above](#the-active-tab-is-server-rendered-and-then-owned-by-the-controller)).

**Filament ships the same defect**, for the same reason — its `getActiveTab()` reads `request()->query()`, and a Livewire round trip's request is likewise not the page's — masked over there only on `EditRecord`, which uses `livewireProperty()` instead and never reaches the query-string branch.
It is recorded rather than fixed because nothing in this port calls `persistTabInQueryString()`: the port's own `Tabs` consumers are [panels' record filters](../panels/has-tabs.md) and the relation managers, which use `liveComponentProperty()` and hold the key in a `LiveProp` that rides every live request — the shape that is [correct by construction](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) and the reason tables bind table state through a `LiveProp` modifier rather than by reading the request at render time.

The fix, when a consumer wants it, is the same mechanism: give `Tabs` a bound host property instead of a request read, or have the controller re-assert `#tab` on `render:finished` the way [tables' controller repaints its selection](../tables/table.md#the-table-controller-absorbs-four-jobs-livewire-does-with-attributes).

### Two Sylament-only accessors carry what Alpine read from the client

`getActiveTabKey()` and `getActiveLiveComponentTab()` have no Filament counterpart: Filament's templates ask Alpine for `tab`, and its server arm reads `$this->getLivewire()->{$property}` inline inside its render method, neither of which a Twig template can reach.
Both are the minimum needed to render the initial state the divergence above describes — the first names the active tab in client mode, the second stringifies the bound host property in server mode.
`getActiveTabKey()` is recorded in [`translations.md`](../translations.md) among the names with no Filament source.

### Deferred badges are keyed by tab key, not by array index

`getDeferredTabBadges()` returns Filament's map shape (`badge`, `badgeColorClasses`, `badgeColorStyles`, `badgeIconHtml`, `badgeIconPosition`, `badgeTooltip`) with one change: the map is keyed by `getKey(isAbsolute: false)` rather than the child schema's original array key.
Filament keys by array key and stamps its client-mode placeholders with a **re-indexed** loop position, so the two desync the moment a hidden tab precedes another or the tabs are declared with string keys.
Ours stamps `data-tab-key` on every placeholder and looks the entry up by it, which cannot drift.
The colour arms are the two Filament has — a string colour becomes `ColorManager::getComponentClasses(BadgeComponent::class, …)`, an array palette becomes `sy-color` plus the inline `getComponentCustomStyles()` declarations — and a badge with no colour defaults to `primary`, as the chrome does, where Filament's map leaves the classes empty.

### A deferred badge renders eagerly in server mode

Filament's server arm defers badges too: its root carries an `x-data` that calls `getDeferredTabBadges()` once, and each placeholder is keyed by the tab's array key.
Server mode here renders no controller, so nothing could fill a placeholder; the partial treats a deferred badge as an ordinary one and evaluates it during the render, which `TabsRenderTest` pins.
The badge therefore costs its query on every server-mode render rather than on a follow-up call.
Reverse it by giving server mode a badge-only controller that calls the seam.

### The tab wiring wins over a tab's extra attributes, where Filament's extras win

Each tab button receives `role`, `aria-selected`, `id`, `aria-controls` and `data-tab-key` first, then spreads `tab.getExtraAttributeBag.toScalarAttributes`, then the controller wiring (the target and action descriptors, or server mode's `live#update` trio); a later key replaces an earlier one, so an extra attribute overrides the ARIA and key props but a user `extraAttributes(['data-action' => …])` on a `Tab` is replaced by the wiring.
Filament merges the tab's extra attributes first and its `role`/`aria-selected`/`data-tab-key`/`x-on:click` map after them, but its attribute bag treats a later merge as defaults, so every extra attribute wins there, the click handler included.
The port's order keeps an extra attribute from unhooking a tab from its controller.
Reverse it by spreading the extra attributes after the wiring.

### The overflow dropdown rides on support's `Dropdown`, and renders its active trigger server-side

`scrollable(false)` ports whole, onto support's [`Dropdown`](../support/dropdown.md)/[`DropdownItem`](../support/dropdown-item.md) rather than Filament's `x-float` panel: the trigger tabs, the ellipsis "more" button and the dropdown list are all in the DOM from the server, and the controller decides what is visible after measuring.
Two differences fall out of the shared chrome.
The active trigger tab renders `sy-active` **and** `aria-current="true"` server-side (Filament binds only the class, in Alpine) because support's `TabsItem` derives `aria-current` from `active`; and the nav's `sy-invisible` guard is added by the controller before its first measurement rather than rendered, so a no-JS page keeps a visible nav.

### Alpine-only surface is skipped, not deferred

`HasExtraAlpineAttributes` (whose skip is settled in [one place](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported)) and the per-tab `hiddenJs`/`visibleJs` visibility expressions have no meaning without Alpine: both exist to hand raw Alpine expressions to `x-bind`.
The port's client-side visibility rules live in the Stimulus controller, and a hidden tab is filtered out server-side by [`Schema`](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders).
Nothing reverses these.
