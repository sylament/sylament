---
description: 'The table''s filters surface: `HasFilters`, the filter indicators, the layouts and the filters chrome (draft)'
ports:
  - vendor/filament/tables/src/Table/Concerns/HasFilters.php
  - vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php
  - vendor/filament/tables/src/Enums/FiltersLayout.php
  - vendor/filament/tables/src/Enums/FiltersResetActionPosition.php
  - vendor/filament/tables/resources/views/components/filters.blade.php
owns:
  - packages/tables/src/Enums/FiltersLayout.php
  - packages/tables/src/Enums/FiltersResetActionPosition.php
  - packages/tables/src/Table/Concerns/HasFilterIndicators.php
  - packages/tables/src/Table/Concerns/HasFilters.php
---
# HasFilters (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The filters trigger — the toolbar dropdown, the funnel icon button and the active-count badge](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `FiltersLayout::Dropdown` branch](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-trigger-badge-is-composed-in-the-table-partial-because-iconbutton-has-no-slot) · [diverges](#the-filters-panel-teleports-because-the-scroll-container-wraps-the-toolbar-too) |
| [The above- and below-content layouts, the collapsible `<details>` and the filters modal](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `AboveContent`, `BelowContent` and `Modal` branches](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-collapsible-panel-is-a-details-element-and-the-modal-opens-through-an-invoker-command) |
| [The filters panel — `<twig:SylamentTables:Filters>`](../../../packages/tables/templates/components/Filters/index.html.twig) | [`views/components/filters.blade.php`](../../../vendor/filament/tables/resources/views/components/filters.blade.php) | done | — | [diverges](#the-panel-is-fixed-chrome-with-no-action-objects-behind-it) |
| [The indicators row — the active-filters label, the chips and the remove-all button](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `fi-ta-filter-indicators` block](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-chip-is-one-badge-and-the-delete-affordance-is-supports) |
| [`filters()` / `pushFilters()` / `getFilters()` / `getFilter()` / `getFiltersFormSchema()` / `deferFilters()` / `hasDeferredFilters()` / `filtersLayout()` / `getFiltersLayout()` / `hasFiltersDropdown()` / `hasFiltersModal()` / `hasFiltersTrigger()` / `hasFiltersAboveContent()` / `hasFiltersBelowContent()` / `hasCollapsibleFilters()` / `getFiltersModalId()` / `filtersFormColumns()` / `getFiltersFormColumns()` / `filtersFormMaxHeight()` / `getFiltersFormMaxHeight()` / `filtersFormWidth()` / `getFiltersFormWidth()` / `filtersResetActionPosition()` / `getFiltersResetActionPosition()` / `isFilterable()` / `getActiveFiltersCount()` / `isFiltered()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) + [`FiltersLayout`](../../../packages/tables/src/Enums/FiltersLayout.php), [`FiltersResetActionPosition`](../../../packages/tables/src/Enums/FiltersResetActionPosition.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php), [`Enums/FiltersLayout`](../../../vendor/filament/tables/src/Enums/FiltersLayout.php), [`Enums/FiltersResetActionPosition`](../../../vendor/filament/tables/src/Enums/FiltersResetActionPosition.php) | in progress | the four before/after-content layouts, and the width-from-columns match | [detail](#an-unshipped-filters-layout-is-refused-where-the-chrome-reads-it) · [detail](#the-filters-form-columns-follow-the-layout-the-width-does-not) |
| [`hiddenFilterIndicators()` / `getFilterIndicators()`](../../../packages/tables/src/Table/Concerns/HasFilterIndicators.php) | [`Table\Concerns\HasFilterIndicators`](../../../vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php) | done | — | [indicator.md](indicator.md#removal-is-structured-not-a-click-handler-string) · [host-can-search-records.md](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) |
| [The three chrome actions — `filtersTriggerAction()` / `getFiltersTriggerAction()` / `filtersApplyAction()` / `getFiltersApplyAction()` / `filtersRemoveAllAction()` / `getFiltersRemoveAllAction()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | done | — | [diverges](#the-trigger-opens-its-chrome-from-the-client-and-the-count-stays-a-template-chip) |
| The filters form seam — `getFiltersForm()` / `filtersFormSchema()` | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | todo | — | — |
| [`persistFiltersInSession()` / `persistsFiltersInSession()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`deselectAllRecordsWhenFiltered()` / `shouldDeselectAllRecordsWhenFiltered()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | done | — | [semantics](#one-filter-setting-drives-three-selection-behaviours) |

## Details

### An unshipped filters layout is refused where the chrome reads it

[`FiltersLayout`](../../../packages/tables/src/Enums/FiltersLayout.php) ships all ten of Filament's cases, and the chrome consumes six.
`Dropdown` puts the filters form behind the toolbar's trigger; `Modal` behind a trigger that opens support's `Modal`; `AboveContent` and `AboveContentCollapsible` render a panel between the indicator row and the table, the collapsible one inside a `<details>`; `BelowContent` renders it after the table and before the pagination; and `Hidden` renders no filter chrome at all while the filters themselves still constrain the query, nothing in the application path consulting the layout.
The other four — `BeforeContent`, `BeforeContentCollapsible`, `AfterContent`, `AfterContentCollapsible` — need chrome the toolkit has not built: vendor's is a sidebar from `lg` up and a floating-ui dropdown below it, one hybrid over two behaviours (R3 §2.3).

The enum ships whole rather than trimmed, because the case *names* are the API a migrating user greps for and a missing case is a fatal `Error: undefined constant` at the call site with nothing to explain it.
What the port refuses instead is the *consumption*, and it does so in `getConsumableFiltersLayout()` — the private probe every layout question in `HasFilters` runs through, and the toolbar reaches at least one of them for any filterable table — with a `LogicException` naming the case and the six that work.
`getFiltersLayout()` stays total: it evaluates the closure arm and returns whatever was set, so a reader, a test or `getFiltersFormColumns()`'s fallback can ask what the table wants without tripping the refusal.

That split is [`PaginationMode::Cursor`'s](can-paginate-records.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) precedent applied a second time — refuse where the value is consumed, not where it is set — and the reason is the same: a `Closure` layout has no value at set time, so a setter-side check would be unenforceable for exactly the callers most likely to need it.
Each refused case lifts as its chrome lands; nothing about the enum changes when it does.

The declaration **order** differs: ours leads with the shipped cases, Filament leads with `AboveContent` and declares `Dropdown` eighth.
The cases are pure — no backing values, no interface — so the only thing that can observe it is `FiltersLayout::cases()`, which nothing reads today.
Left as declared rather than re-sorted to vendor.

### The filters form columns follow the layout, the width does not

`getFiltersFormColumns()` carries Filament's match whole: a configured count wins, and the fallback is `['sm' => 2, 'lg' => 3, 'xl' => 4, '2xl' => 5]` for `AboveContent`, `AboveContentCollapsible` and `BelowContent`, `1` for everything else.
The responsive arm is not a general default — it is the full-width layouts' default, and it is what makes an above-content filter bar look like a bar instead of a column.
The match runs over `getFiltersLayout()` rather than the consumable probe, so a refused layout still answers `1` instead of throwing from a sizing getter.

`getFiltersFormWidth()` stays trimmed in the other direction: Filament's derives a `Width` enum case from the column count for the dropdown panel, and support shipped no `Width` enum when this landed, so the getter is the plain evaluated value with a `null` default — a width the caller states or does not.
The modal layout falls back to `'md'` in the template for the same reason.

[`Sylament\Support\Enums\Width`](../../../packages/support/src/Enums/Width.php) **exists** — all 26 cases, for panels' page and panel width surfaces — so the condition that sentence named has been met and the back-fill is **deferred** rather than blocked: nothing here was retyped, `filtersFormWidth()` still takes and answers a plain string, and re-deriving the column-count match plus narrowing the property is a filters-sized change of its own.
The identical deferral is recorded on [the modal's own ladder](../support/modal.md#the-default-width-is-md-not-sm).

## Semantics

### One filter setting drives three selection behaviours

`deselectAllRecordsWhenFiltered()` reads as a filter setting and files with the filters, Filament's placement kept — but nothing in filtering consults it.
Its three readers are all selection, and only the first is the one the name suggests: the host clears the selection from the filter and search [update tails](host-has-bulk-actions.md#the-selection-is-server-canonical-and-re-derived-on-every-read); `getSelectedTableRecordsQuery()` re-applies the filters and the search to the selected-records query **only while it is on**; and `canTrackDeselectedRecords()` returns false outright when it is off.

The three hang together.
Under the default, any selection that survives is inside the current filters by construction, so constraining the query is safe and closes the select-a-row, filter-it-away, bulk-delete-it hole.
Turn it off and the selection deliberately outlives the filters, so constraining would silently drop records the user still has selected — and an inverted "everything except these" selection has no meaning at all once the set it inverts can drift underneath it, which is why tracking goes with it.

## Divergences

### The trigger badge is composed in the table partial, because `IconButton` has no slot

Filament badges its trigger by calling `->badge($activeFiltersCount)` on the `openFilters` **action**, which the action's own view renders inside the button.
There is no action object here — the v1 chrome is a fixed template — and support's `IconButton` renders exactly one thing, `generateIconHtml()`, with no slot and no children.
So the count is composed one level out: a `.sy-ta-filters-trigger` wrapper holds the icon button and, when the count is above zero, a `.sy-ta-filters-trigger-badge-ctn` span holding an `xs` `Badge`.
A slot on `IconButton` is a possible later support enhancement; it is not built for this.

That container is [`fi-icon-btn-badge-ctn`](../../../vendor/filament/support/resources/css/components/icon-button.css) ported metric for metric — `absolute z-[1] flex w-max -translate-x-1/2 -translate-y-1/2 rounded-md bg-white dark:bg-gray-900`, and the badge inside it at Filament's `badgeSize` default of extra-small, which is what keeps a two-digit count off the 1.25rem funnel underneath it.
Only the two anchor offsets are re-derived, and the composition is why: vendor's container is a child of the icon button, so `start-full top-1` measures against the painted 2.25rem box, while ours is a sibling inside a wrapper that is the button's **margin** box — `.sy-icon-btn`'s `-m-2` insets it 0.5rem on every edge — so the same two points are `start-full` + `ms-2` and `-top-1`.
The wrapper also carries `pointer-events-none`, which vendor needs no equivalent of: a click on a badge nested *inside* the button is a click on the button already.

### The trigger opens its chrome from the client, and the count stays a template chip

`getFiltersTriggerAction()`, `getFiltersApplyAction()` and `getFiltersRemoveAllAction()` are Filament's three, each with its `filters*Action(?Closure)` seam taking the single `action` injection and falling back to the untouched action — and, on two of the three, the port's own chrome is stamped **after** that seam runs, exactly where Filament stamps its own.
That ordering is not incidental: `extraAttributes()` replaces unless told to merge, so a closure setting attributes of its own would otherwise wipe the `command`/`commandfor` pair the modal layout is opened by, leaving a trigger that silently does nothing, and would drop the indicator row's `sy-ta-filter-indicators-remove-all-btn` styling hook from the remove-all button.
Four things differ.

The trigger wires **no click of its own** — `liveComponentClickHandlerEnabled(false)`, vendor's `livewireClickHandlerEnabled(false)` — because the surrounding chrome owns the opening: support's `Dropdown` for the dropdown layout, and for the modal layout an `command="show-modal"` / `commandfor` pair the builder stamps on through `extraAttributes(…, merge: true)` once the customization closure has had the action.
Vendor reaches the same place through Alpine, and carries `modalSubmitAction(false)` plus an `extraModalFooterActions()` pair besides, because its modal *is* the action's own modal; ours is a plain `<twig:SylamentSupport:Modal>` the template renders, so the eighteen modal-customization settings vendor hand-copies off the trigger have nothing to copy onto and are **not** ported.
A caller wanting a different filters modal changes the layout, not the trigger.

The **active-count badge stays a template chip**.
Vendor calls `$filtersTriggerAction->badge($activeFiltersCount)` at each render site; our `Action::badge()` takes no value — it selects the badge *template variant* — and the `badge($value)` family is unported, so porting it here would mean smuggling a whole actions feature into a tables change.
The chip therefore keeps its [existing composition](#the-trigger-badge-is-composed-in-the-table-partial-because-iconbutton-has-no-slot) one level out from the trigger, and the actions trail keeps the row.

The **apply action is sized**, and vendor's is not.
`defaultSize(Size::Small)` is stamped on both the apply and the remove-all buttons here, where Filament sizes the remove-all alone and leaves apply at the default; the modal footer's hand-built Reset `Button` takes an explicit `size="sm"` for the same reason.
That is an addition rather than a port: the three sit two to a footer, and one rendering a size larger than its neighbour read as a mistake in the panel.
`defaultSize()` is the [non-destructive setter](../actions/action.md), so a caller's own `size()` through `filtersApplyAction()` still wins — which is what keeps an addition from being a restriction.

The icons resolve through a closure the action evaluates (`static fn (?IconManager $icons = null) => …`) rather than an injected manager field, because [`CanReorderRecords`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) already declares a `#[Required] setIconManager()` on `Table` and a second trait declaring the same method is a fatal collision.
The parameter is nullable with a `null` default so the Invoker's container arm answers inside DI and its default-value arm answers outside it, which keeps the fallback glyph readable from a unit fixture.

### The collapsible panel is a `<details>` element, and the modal opens through an invoker command

Vendor's `AboveContentCollapsible` is an Alpine `areFiltersOpen` flag toggled by a trigger rendered *below* the form, inside the same container.
Ours is a native `<details>`, the panel its content and the toggle its `<summary>` — zero JavaScript.

The server-rendered `open` is a **seed, not the state**: it is `activeFiltersCount > 0`, and being server-rendered is what makes it survive a morph *the user has not toggled through*.
What carries a manual toggle across one is UX Live's `ExternalMutationTracker`, the same mechanism [the searchable select's id handoff](../forms/select.md#the-hidden-select-lends-its-id-to-the-combobox-button) and [Tabs' active-class writes](../schemas/tabs.md#the-active-tab-is-server-rendered-and-then-owned-by-the-controller) already ride.
The browser's own write of `open` is an attribute mutation on a node inside the host, so it is recorded ([`live_controller.js:1197`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js)) and replayed onto the *incoming* node before idiomorph syncs attributes off it (`:917`, applied at `:1043`) — a user's collapse therefore outlives every later round trip, the `deferFilters(false)` re-render fired by each field edit included, and a re-toggle back to the seeded value cancels the record rather than pinning it (`ChangingItemsTracker::setItem()`, `:955`).
Without that replay the seed would be a defect rather than a default: idiomorph's own `beforeAttributeUpdated` is a no-op (`:422`), so `syncNodeFrom()` (`:546`) would copy the server's opinion straight back over the user's.

The record is durable for the **component's** lifetime, not for one round trip, and the seed is a value rather than a live reference — so a user who collapses the panel while no filter is active keeps it collapsed even after the active count rises and the server starts rendering `open` again.
That is the intended reading of "seed": it decides the first paint and loses to the user from then on, and only a fresh mount (a full page load) hands the decision back to the server.

The residue is support's [accepted gap](../support/dropdown.md#the-open-state-is-a-controller-field-re-asserted-through-a-mutationobserver), inherited on the same terms: an element idiomorph *replaces* rather than morphs carries no record forward, and the `<details>` has no id forcing a match.
No belt is added here — the dropdown could afford one because its controller owned the open state anyway, while a disclosure whose whole point is zero JavaScript would have to grow a controller to hold it.

The summary's funnel is a **hand-built** `IconButton` with `tag="span"`, not the trigger action.
A `<button>` inside a `<summary>` becomes the event's activation target, so the `<details>` would never toggle — the disclosure is the summary's job and nothing interactive may sit inside it.
`getFiltersTriggerAction()` is therefore consumed by the dropdown and modal layouts only, which is also where vendor's `$hasFiltersTrigger` puts it once the before/after layouts are out of scope.

The `Modal` layout opens the same way — from the client, with no server round trip — through the HTML invoker command pair `command="show-modal"` / `commandfor`, pointing at the dialog id `getFiltersModalId()` mints under the table host id.
The dialog is support's `Modal` rendered inline in the partial; `showModal()` puts it in the top layer, so the modal controller's open-attribute observer finds it already `:modal` and returns, and Escape, click-away and focus restoration are the platform's.
That keeps the whole layout inside this lane's templates with no controller of its own.
Vendor's body is the **raw** filters form rather than the `Filters` component, and ours follows it: the component would put a second "Filters" heading and a second reset under the modal's own, so apply and reset sit in the modal footer instead.

**`command`/`commandfor` is Baseline newly-available** — Chrome 135, Firefox 141, Safari 26, September 2025 — and it degrades **silently**: on anything older the trigger is an ordinary `<button>` that does nothing, with no error and no fallback path.
That is the price of a layout with no JavaScript of its own; the alternative is one line in the table controller dispatching support's `sylament-open-modal` window event, which is where this goes if the supported floor ever has to drop below that.

The dialog itself is rendered **inside** the morphed tree, so its open state rides the same `ExternalMutationTracker` replay the `<details>` above does: `showModal()` sets the `open` content attribute, that write is recorded, and the morph re-applies it — the dialog is never de-rendered mid-edit, which is what a `deferFilters(false)` modal (a live request per field change) and the Reset button (a live action fired from inside the open dialog) both depend on.
It does **not** inherit the `<details>`' replacement gap: `getFiltersModalId()` gives it a stable `id`, and idiomorph matches on the id set first, so the dialog is always morphed in place.

Were the attribute ever lost anyway, the controller's own attribute observer could not put it back: it fires on any change to `open` and calls `#normalizeModality()`, which returns on `!this.element.open` — the observer **promotes** a dialog that is already open into the top layer, it does not open a closed one.
That is the half-closed state [that controller's docblock](../support/modal.md) describes.
Two paths would reopen it, and neither is wired here: the `open` `LiveProp`, which this template never sets, and the `sylament-open-modal` window event — the same one-line escape the Baseline paragraph above names, which is why that escape covers both failure modes rather than only the old-browser one.

### The filters panel teleports, because the scroll container wraps the toolbar too

The mount passes `:teleport="true"`; Filament's does not.
The reason is one level below the markup, in which element scrolls.
Filament's horizontal scroll is on `fi-ta-content-ctn` — the wrapper around the table *content*, a sibling **below** the toolbar (`content.css:2`, `@apply relative divide-y … overflow-x-auto`) — and its outer `fi-ta-ctn` clips only when the table has no header at all (`container.css:4`, `&:not(.fi-ta-ctn-with-header) { @apply overflow-hidden; }`), which a filtered table never is: `$hasHeader` is computed with `|| $hasFilters` (`index.blade.php:124`).
So no ancestor of Filament's filters panel clips it, and its dropdown needs no escape.
Ours puts `overflow-x-auto` on `.sy-ta-ctn`, the one container holding toolbar, indicators, table and footer alike — so the panel of a short or narrow table was clipped by the box the table itself is scrolled in.

The fix is the remedy Filament applies to the dropdowns that *are* inside its clipping container: `HasRecordActions` stamps every record `ActionGroup` with `->defaultDropdownTeleport(true)` ([`HasRecordActions.php:107`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php)).
Teleport is [a positioning strategy, not a DOM move](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase) in both codebases — the panel stays exactly where it was rendered and switches to `position: fixed`, which no `overflow` ancestor can clip — so the open-state machinery that survives a live morph (the `hidden` `MutationObserver` and `panelTargetConnected`, [dropdown.md](../support/dropdown.md#the-open-state-is-a-controller-field-re-asserted-through-a-mutationobserver)) is untouched by it, and nothing about the panel's contents or its live wiring changes.

Two things bound the claim.
A fixed element is still clipped by an ancestor that creates a containing block for it — a `transform`, `filter`, `backdrop-filter`, `perspective`, `will-change` or `contain` — and of everything Sylament ships only `.sy-modal-window` has one, so a filtered table rendered *inside a modal* keeps the old behaviour.
Filament inherits exactly that caveat, its teleport being the same strategy.
And the structural alternative stays open: porting `fi-ta-content-ctn` would move the scroll off the container and remove the reason for teleporting at all, which is the shape to reach for if the table ever grows the content wrapper [the partial deliberately omits](table.md#the-partial-emits-no-header-scaffolding-and-no-content-wrapper).

### The panel is fixed chrome, with no action objects behind it

Filament's `filters.blade.php` takes an `applyAction` object and reads `isVisible()` off it; the header reset is a `link` with `tag="button"` built through `prepare_inherited_attributes()`, the footer reset a `button`.
Ours takes both an `applyAction` and the `applyVisible` boolean that gates the footer, and renders the action through `renderComponent()` — Filament's shape, once the [three chrome actions](#the-trigger-opens-its-chrome-from-the-client-and-the-count-stays-a-template-chip) landed.
The two resets stay hand-built support `Link`/`Button`s wired with `stimulus_action('live', 'action', …)`, exactly as vendor's are; `filtersFormSchema()` — the whole-form override closure — is the one seam still deferred.
`Hidden` renders no trigger and no panel while the filtering itself stays applied: the layout is read in the chrome branches and nowhere in the query or indicator lanes, so a hidden filter still constrains the query and still produces its indicators — and the [indicators row](#the-chip-is-one-badge-and-the-delete-affordance-is-supports) renders them, because it is gated on the indicator list alone.

Two smaller shapes belong to the same panel.
`getFiltersFormSchema()` returns its `Group`s **keyed** by filter name where Filament ends on `array_values()` — the keys are what its evaluate call would have handed the dropped override, they survive into `Schema::getComponents()` untouched, and they make one filter's group a direct read rather than a scan.
And the header's `.sy-ta-filters-header-actions` wrapper is a named hook carrying no rule of its own, matching the bare `<div>` Filament has at that spot.

### The chip is one `Badge`, and the delete affordance is support's

Each chip is a single `<twig:SylamentSupport:Badge>` carrying the indicator's label and colour, with the remove control rendered **inside** it — Filament's shape exactly, once support's `Badge` grew the [delete button](../support/badge.md#the-delete-button-is-three-props-not-an-attribute-carrying-slot) it had been missing.
The one translation is where the payload rides: Blade's `<x-slot name="deleteButton" :wire:click="…">` carries its attributes on the slot, a Twig block carries none, so the chip passes `:deletable`, `deleteLabel` and `:deleteAttributes` instead — and what it puts in that bag is `stimulus_action('live', 'action', parameters: …)` built from the [`FilterIndicator` carrier](indicator.md#removal-is-structured-not-a-click-handler-string), where Filament interpolates a `removeLivewireClickHandler()` expression string.
The X is drawn under `SupportIconAlias::BADGE_DELETE_BUTTON`, so the chip's icon is overridable through the same alias a Filament app would use.

The remove-all button follows Filament exactly — the `removeAllFilters` action, an x-mark icon button under the `FILTERS_REMOVE_ALL_BUTTON` alias, rendered only when at least one chip is removable, and the row's one icon button.
Its `.sy-ta-filter-indicators-remove-all-btn` hook rides on the action's `extraAttributes()` rather than a template class, since `renderComponent()` takes no attributes from its call site.
The row is gated on the indicator list being non-empty and on nothing else, so a `Hidden` filters layout still shows its chips.
Five cases in [`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php) pin it: the label and its two chips — each a badge with no badge nested inside it, the second carrying **no** `data-live-field-param` attribute at all, which is what [dropping the null](indicator.md#removal-is-structured-not-a-click-handler-string) buys — a keyed chip carrying both `filterName` and `field`, the search chip on `resetTableSearch`, a non-removable chip with neither a delete button nor a remove-all beside it, and the remove-all appearing the moment one chip is removable.

No chip class carries a CSS rule any more: `.sy-ta-filter-indicator` rides on the badge as a named hook a consuming stylesheet can reach — the same shape as the panel's `-header-actions` wrapper — and everything it used to paint (the capsule tint, ring and end padding that made a badge-beside-a-button read as one control) is the badge's own.
`.sy-ta-filter-indicator-remove-btn` is gone with the button it named; the delete control is support's `.sy-badge-delete-btn`.
`.sy-ta-filter-indicators-remove-all-btn` stays, still rule-less, still taking its whole appearance from `IconButton`.

`filters.css` re-derives the vendor rules it ports ([`container.css`](../../../vendor/filament/tables/resources/css/container.css) `:98-135` and `:187-205`) rather than copying them: the header gains `gap-x-3`, the actions row `flex-wrap items-center`, and the dropdown panel a `20rem` base width vendor expresses one level up, as the table's own `getFiltersFormWidth()` (a `Width` enum defaulting to `Width::ExtraSmall`) handed to the dropdown's `:width`.
The indicators row loosens with it — `px-3 py-1.5` to `px-4 py-3`, `gap-x-3` to a wrapping `gap-3`, `items-start` to `items-center`, and a label without `leading-6 whitespace-nowrap` — but its tint is vendor's again (`bg-gray-50 dark:bg-white/5`), which had moved onto the chip only to make the composed capsule legible and had no reason to stay off the row once the capsule went.

_See also: the global [rename table](../renames.md) — `BelongsToLivewire` → `BelongsToLiveComponent`, `livewire()` → `liveComponent()` and `getLivewire()` → `getLiveComponent()` are the Livewire → UX LiveComponent idiom translation recorded there, not divergences of this component._
