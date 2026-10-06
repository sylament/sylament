# Grouping (tables)

Records gathered under headings: which value a row is keyed by, how that key
reads as a heading, how the query is ordered and narrowed by it, and the chrome
that lets a user choose between the ways a table offers. Like the
[column manager](column-manager.md) it spans every layer of the package —
[`Sylament\Tables\Grouping\Group`](../../../packages/tables/src/Grouping/Group.php)
is the value object (with `Grouping\Concerns\BelongsToTable` folded in),
[`Table\Concerns\CanGroupRecords`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php)
carries the settings and the toolbar trigger,
[`Concerns\CanGroupRecords`](../../../packages/tables/src/Concerns/CanGroupRecords.php)
carries the state on the host and the query pipeline,
[`Concerns\HasBulkActions`](../../../packages/tables/src/Concerns/HasBulkActions.php)
enumerates one group's selectable keys, the
[table partial](../../../packages/tables/templates/partials/Table/index.html.twig)
renders the selector, the group header row and its checkbox, the
[`table` controller](../../../packages/tables/assets/src/controllers/table_controller.ts)
owns collapse and the group tick, and
[`grouping.css`](../../../packages/tables/assets/src/styles/components/grouping.css)
skins all of it.

The summaries that hang off a group boundary are tracked in
[`summarizer.md`](summarizer.md); the per-group summary
row is the only thing the [boundary map](#the-group-boundaries-are-one-php-map-rather-than-loop-carried-template-state)
computes a trailing answer for.

Two traits named `CanGroupRecords` are in play — one per tier — and two classes
named `Group`: this one and
[`Sylament\Schemas\Components\Group`](../schemas/README.md), the layout
component. Read the **import**, never the bare `use`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses). What is left is one alternative
rendering: [`groupsOnly()` reads the page](#groupsonly-still-hydrates-the-page-it-does-not-render)
rather than a distinct-key query.

Reference — the Filament source this is diffed against:

- [`tables/src/Grouping/Group.php`](../../../vendor/filament/tables/src/Grouping/Group.php)
  — the value object, its key/title/description chains and its four query arms
- [`tables/src/Grouping/Concerns/BelongsToTable.php`](../../../vendor/filament/tables/src/Grouping/Concerns/BelongsToTable.php)
- [`tables/src/Table/Concerns/CanGroupRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php)
  — the settings, the deprecated alias and the trigger action
- [`tables/src/Concerns/CanGroupRecords.php`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php)
  — the host state, and `InteractsWithTable::bootedInteractsWithTable()` for its seed
- [`tables/resources/views/index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php)
  — the grouping selector, the group header row, its checkbox and the stripe reset
- [`tables/resources/js/components/table.js`](../../../vendor/filament/tables/resources/js/components/table.js)
  — the Alpine collapse state the Stimulus arm replaces
- [`tables/resources/css/row.css`](../../../vendor/filament/tables/resources/css/row.css),
  [`cell.css`](../../../vendor/filament/tables/resources/css/cell.css),
  [`container.css`](../../../vendor/filament/tables/resources/css/container.css)
  — the `fi-ta-group-*` and `fi-ta-grouping-settings` arms

## Surface

Every point of the feature, across all three tiers and the chrome. _Blocked by_
names what has to land first. _Detail_ carries up to three links: **detail** →
the write-up in [Details](#details), deleted once the row is **done**;
**diverges** → the permanent note in [Divergences](#divergences); **semantics**
→ the permanent note in [Semantics](#semantics). A **skipped** row states its
reason inline instead.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Group::make()` / `id()` / `getId()` / `column()` / `getColumn()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [diverges](#a-blank-id-and-an-id-carrying-a-colon-are-both-refused-by-name) |
| [`label()` / `getLabel()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group::getLabel()`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [semantics](#the-label-fallback-names-the-relation-a-dotted-column-crosses) |
| [`collapsible()` / `isCollapsible()`, `date()` / `isDate()`, `titlePrefixedWithLabel()` / `isTitlePrefixedWithLabel()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [semantics](#the-groups-three-flags-are-plain-bools-where-the-tables-are-closure-evaluable) |
| [`getKeyFromRecordUsing()` / `getKey()` / `getStringKey()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [semantics](#a-value-that-cannot-name-a-group-answers-null-rather-than-failing) |
| [`getTitleFromRecordUsing()` / `getTitle()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group::getTitle()`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [semantics](#the-title-cache-is-keyed-by-string-key-and-bypassed-under-a-title-closure) |
| [`getDescriptionFromRecordUsing()` / `getDescription()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group::getDescription()`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | — |
| `getDescriptionUsing()` | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | skipped | — | `@deprecated Use the getDescriptionFromRecordUsing() method instead.`, and the project has never ported a deprecated Filament surface; the spelling its own notice names ships ([renames.md](../renames.md#renames)) |
| [`orderQuery()` / `orderQueryUsing()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [semantics](#the-group-order-is-appended-first-so-every-later-sort-breaks-ties-inside-a-group) · [table.md](table.md#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery) |
| [`scopeQuery()` / `scopeQueryUsing()` / `scopeQueryByKey()` / `scopeQueryByKeyUsing()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | [diverges](#the-blank-key-scopes-with-one-is-null-where-filaments-or-pair-drops-the-querys-other-predicates) · [diverges](#a-scopequerybykeyusing-return-is-honoured-on-both-paths-where-filament-drops-it-through-scopequery) · [semantics](#a-date-group-is-a-half-open-day-range-bound-with-the-fields-own-type) |
| [`applyEagerLoading()`](../../../packages/tables/src/Grouping/Group.php) | [`Grouping\Group::applyEagerLoading()`](../../../vendor/filament/tables/src/Grouping/Group.php) | done | — | one call to [`RelationJoiner::fetchJoinPath()`](../../../packages/tables/src/Query/RelationJoiner.php), the body [hoisted off the column trait](column.md#relationship-traversal-ships-as-a-value-walk-a-side-store-and-one-kept-member) so the two share an implementation rather than a copy |
| `groupQuery()` / `groupQueryUsing()` | [`Grouping\Group::groupQuery()`](../../../vendor/filament/tables/src/Grouping/Group.php) | skipped | — | [diverges](#groupquery-is-superseded-by-the-per-group-scoped-query) |
| `getRelationship()` / `getRelationshipName()` / `getRelationshipAttribute()` | [`Grouping\Group`](../../../vendor/filament/tables/src/Grouping/Group.php) | skipped | — | The lexical split of a dotted column into *relationship* and *attribute*, which [`RelationPathResolver`](table.md#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) performs against the mapping instead — so all three are steps of a derivation this side does not perform ([renames.md](../renames.md#renames)) |
| [`Grouping\Concerns\BelongsToTable` — `table()` / `getTable()` / `getLiveComponent()`](../../../packages/tables/src/Grouping/Concerns/BelongsToTable.php) | [`Grouping/Concerns/BelongsToTable.php`](../../../vendor/filament/tables/src/Grouping/Concerns/BelongsToTable.php) | done | — | the column trait's copy with the namespace changed, throwing a `LogicException` naming the group id when it is read unmounted |
| [`groups()` / `getGroups()` / `getGroup()`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php) | [`Table\Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | done | — | [semantics](#the-group-memo-hangs-off-a-flag-because-an-empty-set-is-a-legal-memo) |
| [`defaultGroup()` / `getDefaultGroup()` / `isDefaultGroupSelectable()`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php) | [`Table\Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | done | — | [semantics](#a-selectable-default-is-seeded-into-the-property-and-a-non-selectable-one-is-resolved-on-every-read) |
| [`getGrouping()` / `groupsOnly()` / `isGroupsOnly()`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php) | [`Table\Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | done | — | [semantics](#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not) |
| [`groupingSettingsInDropdownOnDesktop()` / `groupingSettingsHidden()` / `groupingDirectionSettingHidden()` / `collapsedGroupsByDefault()` and their four getters](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php) | [`Table\Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | done | — | — |
| `groupsInDropdownOnDesktop()` | [`Table\Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | skipped | — | `@deprecated Use the groupingSettingsInDropdownOnDesktop() method instead.`, one delegating line, and the project has never ported a deprecated Filament surface ([renames.md](../renames.md#renames)) |
| [`groupRecordsTriggerAction()` / `getGroupRecordsTriggerAction()` + `resolveGroupingIcon()` and `ACTIONS_GROUP`](../../../packages/tables/src/Table/Concerns/CanGroupRecords.php) | [`Table\Concerns\CanGroupRecords::getGroupRecordsTriggerAction()`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php) | done | — | [diverges](#the-trigger-drops-vendors-force-enabled-stamp) · [tables-bundle.md](tables-bundle.md#the-alias-set-is-fifteen-constants-of-filaments-23) |
| [`$tableGrouping` + `dehydrateTableGrouping()` + `modifyTableGroupingProp()`](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`Concerns\CanGroupRecords::$tableGrouping`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | done | — | [has-table.md](has-table.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [semantics](#the-state-is-one-non-writable-scalar-and-its-getter-is-what-forces-dehydratewith) |
| [`groupTable()` — the live action both selects write through](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) (the `$tableGrouping` write, hookless) | done | — | [diverges](#a-grouping-change-resets-the-page-and-flushes-the-record-cache) |
| [`mountTableGrouping()` — the `#[PostMount(priority: 2)]` heal and seed](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#a-bookmarked-grouping-beats-a-selectable-default-group) |
| [`getTableGrouping()` / `getTableGroupingColumn()` / `getTableGroupingDirection()`](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | done | — | [semantics](#a-null-direction-is-not-ungrouped) · [has-table.md](has-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| [`applyGroupingToTableQuery()` — the eager load and the order, ahead of every sort](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`Concerns\HasRecords::getFilteredSortedTableQuery()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [semantics](#the-group-order-is-appended-first-so-every-later-sort-breaks-ties-inside-a-group) |
| [`getTableRecordGroupBoundaries()` — the per-record map the partial reads](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`index.blade.php`'s `$previousRecordGroup*` trackers](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-group-boundaries-are-one-php-map-rather-than-loop-carried-template-state) · [semantics](#the-boundary-map-is-keyed-the-way-the-record-array-is) |
| The four `groupsOnly()` PHP gates — the sort, the eager-load loop, the aggregate batch and `isPaginated()` | [`Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php), [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php), [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | [diverges](#the-aggregate-batch-is-skipped-under-groupsonly-where-filament-still-fires-it) · [has-table.md](has-table.md#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) |
| `updatedTableGroupColumn()` | [`Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | skipped | — | A Livewire property hook named for `$tableGroupColumn`, a property Filament no longer has, so upstream it never fires; what it would do — reset the page — is inline in `groupTable()` here ([diverges](#a-grouping-change-resets-the-page-and-flushes-the-record-cache), [renames.md](../renames.md#renames)) |
| A grouping session key | — (Filament persists no grouping) | skipped | — | [semantics](#nothing-persists-a-grouping-in-the-session-and-filament-does-not-either) |
| [The `grouping` URL bind — one `UrlMapping` arm on each routed page](../../../packages/panels/src/Resources/Pages/ListRecords.php) | [`#[Url(as: 'grouping')]` on `ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) and on [`ManageRelatedRecords`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php) | done | — | Both routed pages map it, unprefixed and between `filters` and `tab` — Filament's own declaration order ([manage-related-records.md](../panels/manage-related-records.md)). The nested `RelationManager` binds nothing, here or upstream. [has-table.md](has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) |
| [Grouped selection — `getGroupedSelectableTableRecordKeys()` and the group checkbox](../../../packages/tables/src/Concerns/HasBulkActions.php) | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | done | — | [semantics](#the-whole-universe-arm-costs-one-query-per-group-on-the-page) · [diverges](#both-selection-lanes-key-through-getstringkey-where-filaments-records-lane-reads-the-column-raw) · [has-table.md](has-table.md#the-selection-is-server-canonical-and-re-derived-on-every-read) |
| [`selectGroupsOnly()` / `selectsGroupsOnly()`](../../../packages/tables/src/Table/Concerns/HasBulkActions.php) | [`Table\Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php) | done | — | [table.md](table.md#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) |
| [The toolbar selector — the trigger, its dropdown copy and the inline one](../../../packages/tables/templates/partials/Table/index.html.twig) + [`grouping.css`](../../../packages/tables/assets/src/styles/components/grouping.css) | [`index.blade.php`'s `fi-ta-grouping-settings`](../../../vendor/filament/tables/resources/views/index.blade.php), [`container.css`](../../../vendor/filament/tables/resources/css/container.css) | done | — | [diverges](#the-selectors-two-copies-swap-in-css-rather-than-with-a-utility-class) · [semantics](#the-direction-field-hides-on-the-raw-property-not-on-the-resolved-group) |
| [The group header row — its id, its colspan, the heading, the description and the collapse button](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `fi-ta-group-header-row`](../../../vendor/filament/tables/resources/views/index.blade.php), [`row.css`](../../../vendor/filament/tables/resources/css/row.css) | done | — | [diverges](#the-collapse-button-is-a-real-button-with-a-handler-of-its-own) · [diverges](#the-key-segment-of-every-id-this-row-mints-is-percent-encoded) · [semantics](#the-header-rows-colspan-is-read-off-the-thead-and-corrects-vendors-trailing-one-off) |
| [The collapse state and the group tick — `toggleGroup()`, `group()`, `toggleGroupRecords()` and the two repaint blocks](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`table.js`'s `toggleCollapseGroup()` / `isGroupCollapsed()` / `resetCollapsedGroups()`](../../../vendor/filament/tables/resources/js/components/table.js) | done | — | [diverges](#the-server-paints-the-collapsed-state-where-vendors-alpine-flashes-it-open) · [diverges](#three-client-guards-are-load-bearing-and-no-gate-in-this-repo-runs-them) · [semantics](#collapse-is-keyed-by-title-and-so-is-the-group-break) |
| [The stripe restart at each group](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s stripe reset](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | read off the boundary map's `indexInGroup` rather than a flag reset after each header — [table.md](table.md#surface) |
| `groupsOnly()` rendered from a distinct-key query | [`index.blade.php`'s groups-only branch](../../../vendor/filament/tables/resources/views/index.blade.php) | todo | — | [detail](#groupsonly-still-hydrates-the-page-it-does-not-render) |

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**.

### `groupsOnly()` still hydrates the page it does not render

Under `groupsOnly()` the port renders one summary row per group and no record
rows — which is parity — but it gets the groups by loading the records and
walking their boundaries, exactly as vendor does: the page is unpaginated (the
[four PHP gates](#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not)
see to that), so a groups-only table over ten thousand rows hydrates ten
thousand records whose only product is G boundaries and G summary queries.

The cheaper shape is a `SELECT DISTINCT` of the group keys plus one
representative record per group, and it is a follow-up rather than a gap because
it changes two contracts at once: the empty-state branch stops being "no records"
and becomes "no groups", and `getTitleFromRecordUsing()` — which is handed a
whole record today — would have to be answered from a representative row that
may not be the one the user would expect. Both are decisions, not translations,
so they wait for a table big enough to make the cost real.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### The label fallback names the relation a dotted column crosses

`getLabel()` falls back to
`u($id)->beforeLast('.')->afterLast('.')->kebab()->replace('-', ' ')->replace('_', ' ')->title()`,
Filament's chain step for step over `symfony/string`. The pair in front is the
part worth reading twice: `beforeLast('.')` then `afterLast('.')` takes the
**second-to-last** segment, so `author.name` labels itself *Author* and
`artist.label.name` labels itself *Label* — the relation being grouped by, not
the field being read. A dotless id keeps the whole string, because both methods
return the entire subject when the needle is absent, so `published_at` labels
itself *Published at*. `get_record_label()` is the neighbour that looks like the
tool for this and is not: it takes a class-string.

### The group's three flags are plain bools where the table's are closure-evaluable

`collapsible()`, `date()` and `titlePrefixedWithLabel()` take `bool` and are read
without `evaluate()`, while every flag one tier up (`groupsOnly()`,
`collapsedGroupsByDefault()`, the three settings ones) takes `bool|Closure`. That
asymmetry is Filament's published API shape rather than an oversight here, so it
is preserved: a migrating call that passes a closure to `collapsible()` is a type
error in both codebases.

### A value that cannot name a group answers null rather than failing

`getStringKey()` unwraps a `BackedEnum` to its backing value, formats a
`DateTimeInterface` as `Y-m-d` under `date()`, and otherwise applies the
scalar-or-`Stringable` guard `HasTable::toRecordKey()` uses for record keys —
copied rather than shared, because that helper is `private static` on the host
trait. Everything else answers `null`, which is a real group (the blank one)
rather than an error: Filament's `strval()` fatals on an array and on a pure
enum.

One arm of that guard is load-bearing rather than defensive. A Doctrine
`Collection` **is** `Stringable`, and what `ArrayCollection::__toString()` prints
is `Class@hash` — a different string per instance — so without the explicit
collection arm a to-many column would put every single row in a group of its own,
silently and plausibly. It is refused ahead of the `Stringable` test for that
reason, and a to-many column names no group at all instead.

The second arm is the mirror image, and it exists because the language differs
rather than the design. Filament's `strval($key)` works on a datetime because an
Eloquent date attribute is a `Carbon`, which is `Stringable`; a Doctrine
`DateTimeImmutable` is neither scalar nor `Stringable`, so the bare guard would
answer `null` for every row of a datetime column declared **without** `date()`
and collapse the whole page into one blank-headed group. The arm formats it as
`Y-m-d H:i:s` — the same string Carbon prints, so the key, the heading and the
literal [the equality scope binds](#a-date-group-is-a-half-open-day-range-bound-with-the-fields-own-type)
all agree with upstream, and such a column groups per instant as it does there.
`date()` is still what collapses those instants to a day.

Under `date()` the strictness runs the other way: anything that is not a
`DateTimeInterface` answers `null`, where Filament runs `Carbon::parse()` over
whatever it finds. A `date()` group is declared over a mapped date or datetime
field — that is what makes [the range scope](#a-date-group-is-a-half-open-day-range-bound-with-the-fields-own-type)
and the raw-column ordering work — so a string column under `date()` reads as one
blank group rather than as a parse of something that was never a date.

### The title cache is keyed by string key and bypassed under a title closure

`getTitle()` memoises per string key, the blank key sharing the `''` slot
(`array_key_exists(null, …)` coerces the same way upstream), because a page
renders one heading per group and the read behind it can be a relation hop. The
memo is skipped entirely when `getTitleFromRecordUsing()` is set: that closure is
handed the record and is free to answer differently for two records of one group,
so caching its first answer would be wrong. Both halves are Filament's.

### The group order is appended first, so every later sort breaks ties inside a group

`getFilteredSortedTableQuery()` is
`applySortingToTableQuery($this->applyGroupingToTableQuery($query))` — Filament's
own order. The group's `addOrderBy` therefore lands ahead of the user's sort, the
table's default sort and the identifier tiebreaker, all of which append after it,
so the grouping is the outermost ordering and everything else orders rows
*within* a group. Nothing replaces it: the only `orderBy()` in
`packages/tables/src` is the reorder branch, and reordering nulls the grouping
anyway.

### A `date()` group is a half-open day range bound with the field's own type

`scopeQueryByKey()` under `date()` parses the key with
`DateTimeImmutable::createFromFormat('!Y-m-d', $key)` and narrows with
`>= :from AND < :to` over that day — no `DATE()`, which DQL does not have, and no
native SQL. The two parameters are bound **with the resolved field's DBAL type**,
read off `ClassMetadata::getTypeOfField()`: an untyped `DateTimeImmutable` bound
against a `date_immutable` column compares as text on SQLite and answers the
neighbouring day. An unparsable key scopes to `1 = 0` rather than to everything.
Ordering under `date()` is by the raw column, so ties inside a day resolve by
time — strictly better than a truncating sort, and free.

### The group memo hangs off a flag because an empty set is a legal memo

`getGroups()` memoises behind `private bool $hasCachedGroups` rather than behind
the array itself, since a table declaring no groups memoises `[]` legitimately
and `?:` would rebuild it on every read. It is the shape
`Table\Concerns\HasRecords::$hasCachedRecordClass` already uses on the same
tier — both are `Table` concerns.

### A selectable default is seeded into the property and a non-selectable one is resolved on every read

`getTableGrouping()` returns `null` when the default group is also one of the
offered ones — which reads backwards until the seed is in view.
[`mountTableGrouping()`](#a-bookmarked-grouping-beats-a-selectable-default-group)
writes a *selectable* default into `$tableGrouping` at mount, so the selector
shows it and can offer "no grouping" beside it; from then on the property is the
answer and `null` genuinely means the user chose nothing. A **non**-selectable
default — `defaultGroup('status')` on a table whose `groups()` never lists
`status` — is never written anywhere and is resolved fresh on every read, which
is how a table groups by something the user cannot change. Both are Filament's
arms.

### The state is one non-writable scalar, and its getter is what forces `dehydrateWith`

`$tableGrouping` is `<id>` or `<id>:desc`, the `$tableSort` shape, non-writable so
the only client path to it is the `groupTable()` action — one validation point
instead of one per read. `dehydrateWith: 'dehydrateTableGrouping'` is mandatory
rather than tidy: UX's dehydration pass reads through Symfony's
`PropertyAccessor`, which prefers a same-named **getter** over the property, and
`getTableGrouping()` returns a `Group` object with no property type to hydrate
back into — so without the method the first grouped render throws
`LogicException: The "tableGrouping" property … is missing its property-type`.
`fieldName:` does not escape it, because that renames the wire key rather than the
accessor read. The method also publishes a blank property as `null`, so a page
reached with a bare `?grouping=` does not carry `grouping=` back onto every URL it
rebuilds.

### A null direction is not "ungrouped"

`getTableGroupingDirection()` answers `null` for a blank property *and* for a
suffix that is neither `asc` nor `desc`, but a group resolved from a
non-selectable default carries no property at all — so `null` there means "the
group resolved, the direction did not", and `applyGroupingToTableQuery()` falls
back to `'asc'`. The two readers are deliberately separate: `mountTableGrouping()`
treats a `null` direction beside a non-blank property as damage and clears the
whole value, which is the only place the two meanings have to be told apart.

### The boundary map is keyed the way the record array is

`getTableRecordGroupBoundaries()` returns `array<array-key, …>`, not
`array<string, …>`, because PHP coerces a numeric-string array key to an `int` —
so a table whose record keys are database ids hands back int keys, exactly as
`getTableRecords()` does. A Twig lookup by string key resolves either way;
`array_keys()` does not, which is the thing a reader is likely to assume.

### The whole-universe arm costs one query per group on the page

`getGroupedSelectableTableRecordKeys()` has two arms, both keyed through
`Group::getStringKey()`. Where selection is page-only — which includes every
`records()` table, since `selectsCurrentPageOnly()` is true without a query — it
filters the page collection in PHP and costs nothing. Otherwise it scopes the
filtered query by the group's key and plucks (or hydrates and filters, when a
selectability callback is set), which is **one statement per group header on the
page**, memoised per group for the length of the request. That is the same trade
the ungrouped enumeration makes — no limit, no chunking — and it is what buys a
zero-request group tick on the client, since the keys are written into the
checkbox's `data-sy-group-keys` before the page is sent.

### Collapse is keyed by title, and so is the group break

The controller's `#toggledGroups` set holds *titles*, and `#isGroupCollapsed()` is
`has(title) !== groupsCollapsedByDefault` — one XOR standing in for Filament's
four-branch pair of methods. Keying by title rather than by key is Filament's, at
all eight of its call sites, and it agrees with the other half of the design: the
[boundary](#the-group-boundaries-are-one-php-map-rather-than-loop-carried-template-state)
break is on the title too, string-cast on both sides. So two groups whose keys
differ but whose titles match are one heading *and* collapse together —
consistent rather than coincidental. The checkbox and the summary scope key by
**key** instead, because those two ask a database a question.

The state lives on the controller instance, so it survives every morph and dies
on navigation — Filament's Alpine lifetime exactly. A polling table repaints N
rows per interval, the same cost `sy-selected` already carries.

### The direction field hides on the raw property, not on the resolved group

The direction `<label>` is server-rendered `hidden` while
`getTableGroupingColumn()` is null — the **property**, which is what vendor's
Alpine splits too — rather than while the resolved group is null. The two differ
in exactly one configuration, and it is the one a
[non-selectable default](#a-selectable-default-is-seeded-into-the-property-and-a-non-selectable-one-is-resolved-on-every-read)
creates: the table is grouped, the group select shows `-` because the group in
force is not one it offers, and the direction field stays hidden. Reading the
resolved group there would show a direction select whose change is inert, because
the group select would still send `""`.

### The header row's colspan is read off the thead, and corrects vendor's trailing one-off

`columnsCount` is computed once before `<table>` — one per visible column, one
for the record actions wherever they sit, one for the checkbox in whichever
position it takes — and the header row subtracts the cells it lays out itself:
the group selection cell, and the empty placeholder each of the two two-cell
cases needs beside it. There are two, one per side — `actionsBeforeCells` with
`checkboxLeading`, and `actionsAfterCells` with `checkboxTrailing` — and vendor
subtracts only the leading one
([`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php)
at `:2045-2058`). Its trailing arm therefore emits a row of the right *width*
whose checkbox cell lands in the last slot, under the actions header, one column
right of every record checkbox above it. That is the same one-column-off vendor
has in the summary rows' placeholder order, corrected the same way and for the
same reason — [the summary side](summarizer.md#the-placeholder-cells-follow-the-record-rows-order-not-vendors)
names `AfterCells` + `AfterCells` as its case too, so a table fixing one and
inheriting the other would misalign its group checkbox under exactly the
configuration whose summary placeholders it had just aligned.

The ten combinations of the two action positions and the two checkbox positions,
selection off included, assert both halves: the row's total width against the
`thead`, and the *column index* the group checkbox lands in against the index of
the selection header. The reorder header cell is deliberately not counted: a drag
nulls the grouping, so the two can never coexist.

### The PHP gates of `groupsOnly()` are unconditional where the template's are not

`Table::isGroupsOnly()` is read four times on the PHP side — the sort returns
untouched, the eager-load loop is skipped, the aggregate batch early-returns and
`isPaginated()` answers false — and none of those asks whether a grouping
actually resolved. The template's `isGroupsOnly` does:
`component.isGroupsOnly and group is not null`, so the mode is inert in the
markup until something is grouped. A table that calls `groupsOnly()` and has no
grouping in force therefore renders its record rows **and** loses its pagination
footer. Both halves are vendor-faithful (`Table\Concerns\CanPaginateRecords`
against `index.blade.php`), the asymmetry is vendor's own, and it is only
reachable by declaring `groupsOnly()` with nothing to group by.

Its sibling is worth naming beside it: the per-group summary rows are gated on
the table having a summary, as vendor's are, so `groupsOnly()` with no summarizer
anywhere renders an **empty `<tbody>`** rather than a list of headings. That is
parity, not a bug — a groups-only table exists to show figures per group.

### A `records()` table groups in PHP and is re-ordered by nobody

Grouping works off the query lane too: every key, title and description is read
off the row, the boundary map is built the same way, and the group header rows
render. What does **not** happen is the ordering — `applyGroupingToTableQuery()`
has no query to order, and the eight
[`records()` injections](has-table.md#the-records-injections-match-filaments-eight)
carry no grouping value, here or upstream. So a `records()` closure that groups
must return its rows already in group order, or the same group opens a heading
every time the value comes round again. Filament injects nothing either; the
injection count stays at eight.

### Nothing persists a grouping in the session, and Filament does not either

Every other browsing prop the port persists — the page size, the sort, the search,
the filters, the column state — has a `persists*InSession()` twin because Filament
has one. Grouping has none in either codebase (a grep of the vendor tree for
`persists*InSession` returns filters, search, column searches, sort and columns),
so the absence here is a port of an absence rather than an omission, and
`TableSessionPersistenceTest` pins that a grouping change writes no `…_grouping`
key. The URL is where a grouping is kept, through the
[bind a routed page opts into](has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page).

## Divergences

Deliberate deviations from Filament, each phrased as a statement and addressable
by anchor.

### A blank id and an id carrying a colon are both refused by name

`Group::make()` guards `blank($id)` with a `LogicException` naming the class,
where Filament's `id(string)` lets PHP raise a `TypeError` on `make(null)` — the
same guard `Column::make()` already carries here, and the same wording.

The second refusal has no Filament counterpart at all, because it is the port's
own shape that creates it: the grouping state is **one** scalar,
`<id>:<direction>`, read back by splitting on the first colon, where Filament
keeps `$tableGrouping` beside a separate Alpine `group` / `direction` pair. So an
id holding a colon would resolve to the fragment before it — a group the table
does not offer, which
[degrades to no grouping](#a-selectable-default-is-seeded-into-the-property-and-a-non-selectable-one-is-resolved-on-every-read)
in silence. `id()` throws instead, so `make()` does too, which is the same call
[a column name containing a colon](has-table.md#a-column-name-containing-a-colon-cannot-be-cycled)
gets one feature over — except that one is documented rather than refused,
because a sort token is composed from a name the developer did not choose.

### A bookmarked `?grouping=` beats a selectable default group

Filament seeds `"{$id}:asc"` into its property unconditionally, after Livewire's
`#[Url]` hydration has already run — so `/admin/articles?grouping=status:desc` on
a table whose default group is `author.name` renders grouped by **author**, and
the bookmarked URL is silently discarded. The port's `mountTableGrouping()` seeds
only into a blank property, behind the same `blank()` guard every neighbouring
seed in that method already uses, so the URL wins.

The hook does two things in one pass, in this order. First the **heal**: a
non-blank property whose id is not one the table offers, or whose direction reads
as neither `asc` nor `desc`, is cleared to `null` — the mount edge of the check
`groupTable()` runs at the other writer, since the query string is the one path
that bypasses the action. Then the **seed**. It runs at
`#[PostMount(priority: 2)]`, after UX's URL seeding (which finishes before any
component hook, whatever the priority) and after the session hook at 10, which has
no grouping arm to run; the slot is its own because two hooks on one priority
would resolve by reflection order over the flattened traits, and the ladder is
pinned in `HasTableLivePropsTest`.

### A grouping change resets the page and flushes the record cache

`groupTable()` writes the property, sets `$tablePage = 1` and flushes the cached
records — the shape `sortTable()` already has. Filament resets nothing: its
`updatedTableGroupColumn()` hook is named for `$tableGroupColumn`, a property it
no longer has, so Livewire never calls it and page 4 of the old grouping survives
a regrouping that has made it meaningless. The hook itself is
[not ported](../renames.md#renames) — there is no property for Livewire's naming
convention to bind it to here either, and what it would have done is inline.

### The group boundaries are one PHP map rather than loop-carried template state

Filament tracks `$previousRecordGroupKey` / `$previousRecordGroupTitle` across its
Blade loop and compares each row against the last one it drew. The port builds
`getTableRecordGroupBoundaries()` once per render — key, title, description,
`isFirstOfGroup`, `isLastOfGroup` and `indexInGroup` per record key — and the
partial does one lookup per row.

The Twig tracker pattern would have worked (a `{% set %}` before the loop
survives `strict_variables`; only an unguarded first read of an uninitialised
variable throws), so the map is chosen for what it buys rather than for what it
avoids: it is asserted in Unit without rendering anything, it is where the
[trailing-summary probe](summarizer.md#the-trailing-group-is-followed-past-the-page-break-with-a-record-not-a-scalar)
folds in, and it is the shape `recordActionsByKey` already hands the same loop.
It is memoised for the request and invalidated with `cachedTableRecords`, because
on a summarized table building it costs a statement and two readers ask for it.

### The trigger drops vendor's force-enabled stamp

Vendor stamps `extraAttributes(['class' => 'fi-force-enabled'], merge: true)` onto
the grouping trigger after the modifier closure. No trigger in this package does,
for the reason
[the column manager's records](column-manager.md#the-trigger-drops-vendors-modal-footer-machinery-because-the-chrome-is-the-partials):
nothing here disables an action from the outside, so there is nothing for the
class to re-enable. `->authorize(true)` goes with it — authorization is parked
whole.

The icon is resolved through a **uniquely named** setter pair —
`setGroupingIconManager()` / `resolveGroupingIcon()` — rather than through a
second `#[Required] setIconManager()`: `Table` flattens
`CanReorderRecords`' setter of that name into the same class, and two traits
declaring one non-abstract method is a compile-time collision that stops `Table`
loading at all. A table built outside the container falls back to a bare
`IconManager`, which resolves no alias and answers the shipped glyph.

### `groupQuery()` is superseded by the per-group scoped query

`groupQuery()` and `groupQueryUsing()` have exactly one caller upstream:
`index.blade.php`, where the grouped summary is computed as a single `GROUP BY`
batch and the hook re-roots that batch's query. The port
[runs one scoped query per group instead](summarizer.md#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by),
because DQL has no `DATE()` and no `FROM`-subquery and the string equality a
`GROUP BY` key needs cannot be made to agree with `getStringKey()` for five of
the group kinds — so the hook has nothing left to hook into.
`scopeQueryUsing()` is the seam that survives, and it answers on the same path
the fallback does.

### The blank key scopes with one `IS NULL` where Filament's `OR` pair drops the query's other predicates

For a blank key Filament emits
`whereDoesntHave(...)->orWhereHas(..., fn ($q) => $q->whereNull(...))` — an
`orWhere` with no parentheses around it, which at the SQL level ORs itself past
every filter and search predicate already on the query, so a filtered table
scoping to the blank group can select rows the filters excluded. The port narrows
with a single `andWhere("{$path} IS NULL")`, which computes the same set on a
left-joined relation path (a row with no related row has a null there too) and
cannot escape the predicates around it. Every scope this class adds is `andWhere`
for that reason, and every parameter is `sylament_group_<n>`, counter-suffixed,
because `setParameter()` overwrites a same-named parameter in silence.

### A `scopeQueryByKeyUsing()` return is honoured on both paths, where Filament drops it through `scopeQuery()`

All three closure seams — `orderQueryUsing()`, `scopeQueryUsing()` and
`scopeQueryByKeyUsing()` — hand the closure the builder in `namedInjections` and
then **coalesce**: the builder the closure returned wins, and the one it was
handed stands where it returned nothing, which is what a closure that mutates in
place does. That is Filament's `return $this->evaluate(…) ?? $query;`
([`Group::orderQuery()`](../../../vendor/filament/tables/src/Grouping/Group.php)
at `:350-358`, `scopeQuery()` at `:373-388`, `scopeQueryByKey()` at `:395-407`),
typed rather than loose: the coalesce is `instanceof QueryBuilder`, so a closure
answering something that is not a builder is treated as one that answered
nothing.

Vendor is not consistent about it, and the port is. `scopeQuery()`'s **no-closure**
arm upstream (`:390-392`) calls `$this->scopeQueryByKey($query, …)` and then
returns `$query`, so a `scopeQueryByKeyUsing()` closure's returned builder is
honoured when the method is called directly and dropped when it is reached
through `scopeQuery()` — the per-group summary path. The port returns what
`scopeQueryByKey()` answered, so one closure behaves the same way on both paths.

### Both selection lanes key through `getStringKey()` where Filament's records lane reads the column raw

Filament's grouped key enumeration has two implementations of *which group is this
record in*: the query lane asks the `Group`, and the records lane reads
`$record[$groupColumn]` straight out of the row — which loses `date()`,
`getKeyFromRecordUsing()` and every enum unwrap, and whose closure is typed
`array $record`, so an object record fatals there. Both lanes here ask
`Group::getStringKey()`, so the checkbox and the heading above it cannot disagree
about which rows belong to the group.

### The aggregate batch is skipped under `groupsOnly()` where Filament still fires it

`fillTableAggregateValues()` early-returns under `groupsOnly()`, because no record
row renders and nothing reads a per-record aggregate. Filament fires its aggregate
selects regardless, for cells it will not draw. The port's batch is keyed by the
page's record keys, and under `groupsOnly()` the page is the whole table — so the
skip is worth more here than the same skip would be upstream.

### The collapse button is a real button with a handler of its own

Filament's chevron is decorative: the click handler lives on the wrapping
`div`, so a keyboard user who tabs to the button and presses Enter gets nothing.
The port keeps the whole header clickable **and** binds `toggleGroup` to the
button itself, with `event.stopPropagation()` so one click toggles once — the
repeater's `toggleItemFromButton()` precedent. `aria-expanded` is
server-rendered on the button and rewritten by the repaint.

### The key segment of every id this row mints is percent-encoded

The group header row's id is `<hostId>.table.groups.<key>`, its checkbox's is that
plus `.checkbox`, and the per-group summary row's is
`<hostId>.table.group-summary.<key>` — with the key `url_encode`d in all three.
The literal shape the design called for would put the raw key there, and a group
key is user data: an artist named `Zero 7` mints an id containing a space, which
HTML forbids and `document.querySelector('#…')` cannot address unescaped. Encoding
keeps the ids stable and unique per key (two keys that differ only in a space
still differ after encoding, where a slug would collapse them into one id on two
rows and break the morph), which is what these ids exist for: idiomorph pairs by
`id`, and a header row whose id changed would have its whole `innerHTML` replaced.
The collapse key is `data-sy-group` — the title — and is never encoded.

### The server paints the collapsed state where vendor's Alpine flashes it open

Under `collapsedGroupsByDefault()` on a collapsible group the partial emits
`sy-collapsed` itself — on the header's inner `div` and on every record row —
rather than leaving the first paint to the controller. Vendor's `x-bind:class`
evaluates at Alpine init, so its rows render expanded and collapse a frame later.
The controller agrees with the server on that first paint (the toggled set is
empty, so the XOR answers the default for every title) and the two never fight.

The class lands on the record `<tr>`s and on the header's **inner** element,
never on the header `<tr>` — which would hide the group and the one control that
could expand it again. The client repaint carries the same rule as a
`header === null` test, and both halves are asserted.

### Three client guards are load-bearing and no gate in this repo runs them

The controller's grouping arm — `group()`, `toggleGroup()`,
`toggleGroupRecords()`, the XOR and the two repaint blocks — is covered by `tsc`
and Biome only; this repo has no JavaScript test rig, the standing gap
[`checkbox-list.md` records for forms](../forms/checkbox-list.md#the-controller-is-the-only-behaviour-and-no-test-in-this-repo-runs-it)
and [`tables-bundle.md`](tables-bundle.md#asset-registration) for this controller.
Three guards inside it are invisible to every gate and each ships a visible
regression if it goes: `toggleGroup()`'s `event.stopPropagation()` (without it a
collapsible group toggles twice per button click, which looks like nothing
happening), `group()`'s `#toggledGroups.clear()` (Filament's
`resetCollapsedGroups()` — without it a collapse set from the previous grouping
survives the change and collapses whatever now happens to share a title), and the
repaint's `collapsed && header === null` (without it the header row hides itself
along with its own expand control). The markup they bind to *is* pinned — the
targets, the actions, `data-sy-group-keys`, the server-rendered `checked` and
`sy-collapsed` — so a renamed action or target fails here; the behaviour behind
them is the browser pass's.

The omission failure mode is the controller's own, one degree worse than the
selection one: an app that leaves `@sylament/tables` out of its
`assets/controllers.json` gets a grouping selector whose change never fires and,
under `collapsedGroupsByDefault()`, rows the server painted `sy-collapsed` that
nothing can ever expand.

### The selector's two copies swap in CSS rather than with a utility class

The two selects are captured once and composed into two copies — a dropdown
copy for the narrow viewport, an inline one for the wide — each with vendor's
own markup for that copy: the dropdown labels each select above it, the inline
copy passes *Group by* as the [`InputWrapper`](../support/input-wrapper.md)'s
`prefix` and names the direction with a `sy-sr-only` span, exactly as
[`index.blade.php:432-520`](../../../vendor/filament/tables/resources/views/index.blade.php)
does. The divergence is only *which copy shows*: vendor puts a conditional
`sm:fi-hidden` on the dropdown and `hidden sm:flex` on the inline copy; here
both live in
[`grouping.css`](../../../packages/tables/assets/src/styles/components/grouping.css),
keyed off the `sy-ta-grouping-settings-dropdown-on-desktop` modifier class the
wrapper carries under `groupingSettingsInDropdownOnDesktop()` — a stylesheet rule
rather than a page-wide utility only this one caller would ever want, which is
[the toolkit's standing rule for exactly this](../../internals/styling.md#where-does-a-utility-class-live).

One more delta rides inside both copies: the direction field's visibility
is a server attribute rather than an Alpine binding — `hidden` when the property
names no group, where vendor writes `x-cloak x-show="group"`. The controller
keeps it in step from there.
