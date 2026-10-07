---
description: 'The page half of relation managers: the registrations a resource declares, filtered for the record, and the one component that mounts them.'
ports:
  - vendor/filament/filament/src/Resources/Pages/Concerns/HasRelationManagers.php
owns:
  - packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php
---
# HasRelationManagers (panels)

[`HasRelationManagers`](../../../packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php) is the page half of relation managers: the registrations [`Resource::getRelations()`](../../../packages/panels/src/Resources/Resource.php) declares, filtered to the ones this record shows, and the single component that mounts them.
It is composed by [`EditRecord`](edit-record.md), whose `content()` schema is where the component it builds lands.

Each manager is mounted through schemas' [`LiveComponent`](../schemas/live-component.md) embed, so it is a live component of its own — a table, its actions and its modals — rather than a fragment of the page.
What the page hands it is a record **key** and its own class-string; the [`RelationManager`](relation-manager.md) resolves the owner from those.

The embedded manager is one of **two** ways a record page shows an association.
The other is [`ManageRelatedRecords`](manage-related-records.md), a routed page of its own reached through the record's [sub-navigation](has-sub-navigation.md) rather than a tab, which lists the same records and — with a `$relatedResource` — turns its Create/Edit actions into links into that resource's own [nested pages](nested-resources.md) instead of modals.
Both hosts compose one tier, [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md); what this trait owns is the **embedded** arm alone.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Resource::getRelations()`](../../../packages/panels/src/Resources/Resource.php) | [`Resource::getRelations()`](../../../vendor/filament/filament/src/Resources/Resource.php) | done | — | [diverges](#getrelations-is-an-instance-method-like-every-other-resource-hook) |
| [`$activeRelationManager`](../../../packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php) | `#[Url(as: 'relation')] public ?string $activeRelationManager` | done | — | [semantics](#the-relation-binding-is-the-toolkits-only-static-url-mapping) |
| [`getCachedRelationManagers()` / `getRelationManagers()` / `getAllRelationManagers()`](../../../packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php) | the same three | done | — | [semantics](#the-registrations-are-read-once-per-request-and-filtered-by-the-record) |
| [`normalizeRelationManagerClass()`](../../../packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php) | `normalizeRelationManagerClass()` | done | — | — |
| [`getRelationManagersContentComponent()`](../../../packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php) | `getRelationManagersContentComponent()` | done | — | [semantics](#four-arms-one-component-and-the-registration-keys-are-the-identities) |
| The active-manager defaulting | [`renderingHasRelationManagers()`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/HasRelationManagers.php) | done | — | [diverges](#the-defaulting-is-folded-into-the-builder-and-casts-the-key-to-a-string) |
| [`EditRecord::content()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) + [`schemas()`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | [`EditRecord::content()`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) | in progress | — | [detail](#content-ships-in-a-narrow-shape) |
| `hasCombinedRelationManagerTabsWithContent()` / `getContentTabComponent()` / `getContentTabLabel()` / `getContentTabIcon()` / `getContentTabPosition()` / `Enums\ContentTabPosition` | the same five plus the enum | skipped | — | [diverges](#the-combined-content-tab-does-not-port) |
| The `activeLocale` passthrough to every embed | [`getRelationManagersContentComponent()`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/HasRelationManagers.php) | todo | — | — |
| The trait on `ViewRecord` | Filament composes it on the shared record page | blocked | `ViewRecord` | — |

## Details

### `content()` ships in a narrow shape

Filament's `content()` owns the whole edit-page body: the `Form` component, the form-level `Actions` (Save/Cancel), and the relation managers, with `ContentTabPosition` deciding whether the form itself is a tab beside them.
Ours holds **only** the relation-manager component; the `<form>` element, the record-level error outlet, the Save button and the `Modals` outlet stay in the template — the shared [form variant](form-actions.md) and the base page chrome above it, with [`partials/EditRecord/index.html.twig`](../../../packages/panels/templates/partials/EditRecord/index.html.twig) a child of the variant whose only content is an `after_form` block holding `{{ renderSchema('content') }}`.
`EditRecord::content()` is an **override of the base's** identity [`Pages\Page::content()`](custom-pages.md#content-resolves-by-bare-name-so-an-empty-body-costs-a-live-request-nothing) rather than the only `content()` in the hierarchy — which is what makes the narrowing below a narrowing of Filament's method rather than a different method with the same name.

The reduction is deliberate: the faithful shape needs `Form` and form-level `Actions` — neither ported — plus a rewrite of all three record-page templates, which is work the size of the view page and infolists.
What ships is a strict subset, so the faithful shape is an addition rather than a rewrite.
The one thing the narrow shape fixes in place is placement: the managers render **after** the form element, never inside it, because a manager hosts forms of its own and a nested `<form>` is invalid HTML the browser silently unnests.

`content()` is on `EditRecord` alone.
`CreateRecord` has no record and so no managers to show, and `ListRecords`' faithful `content()` wants `EmbeddedTable` ([relation-manager.md](relation-manager.md#the-manager-renders-a-template-not-a-content-schema)); both keep their deferrals.

### The group arms are not browser-tested for child preservation

The `RelationGroup` arms are covered by the render rig, which renders every child in full; whether a group's embeds keep their state across a parent re-render is only observable in a browser.
The demo registers the single-manager and tabs arms, so those are what a browser pass covers, and the group arms are not.

## Semantics

### The registrations are read once per request and filtered by the record

`getAllRelationManagers()` reads the resource's `getRelations()`; `getRelationManagers()` filters it, asking each manager class `canViewForRecord($record, static::class)` and each `RelationGroup` whether it still names any manager once the owner and the page are set on it; `getCachedRelationManagers()` memoises the filtered list for the request and answers `[]` outright while the page holds no record.
That is Filament's shape unchanged — including the `array_filter()` that **preserves keys**, which the builder below depends on.

The `canViewForRecord()` answer is `true` for every manager until authorization lands ([relation-manager.md](relation-manager.md#every-registered-manager-is-reachable-by-anyone-until-authorization-lands)), so the only registration a *shipped* manager ever loses to the filter is an empty group.
The other branch is real all the same: a consumer overriding the hook gets it.

### Four arms, one component, and the registration keys are the identities

`getRelationManagersContentComponent()` answers exactly one component, so the page template needs no emptiness branch:

- **more than one registration** — a `Tabs` keyed `relationManagerTabs`, carrying `liveComponentProperty('activeRelationManager')` and `contained(false)`.
  Each entry becomes a tab: a manager class through its own `getTabComponent($record, static::class)` (label, icon, badge, badge colour and tooltip, all off the manager's statics), a `RelationGroup` through the group's `getTabComponent()`.
- **no registrations** — a hidden `Group`.
- **one `RelationGroup`** — a plain `Group` holding one embed per manager the group names.
- **one manager** — the embed alone, with no tab bar around it.

The tabs array **keeps the keys `getRelations()` used**.
That is load-bearing twice over: [`Tabs::configureChildSchema()`](../schemas/tabs.md) re-keys every tab from the array key it sits under, and the tab bar decides which tab is active by comparing those keys against the host property.
An `array_values()` anywhere in the builder would leave the tabs keyed `0`, `1`, … while `$activeRelationManager` still held `sections` — the bar would render, no tab would ever light up, and nothing would report an error.

Every embed is keyed too, because an unkeyed embed cannot keep a stable child id across renders ([live-component.md](../schemas/live-component.md)): the plain arms key by the manager class, and a group's embeds by `"{class}-{groupKey}"`, so the same manager registered in two groups still keeps two identities.

A **group's** embed array is the one place the keys are dropped rather than kept: Filament maps its group's managers through `collect()->map()->all()`, which preserves the registration keys, and ours builds a plain `list`.
Nothing reads them — `HasComponents::components()` does not treat an array key as an identity, and the group key is already on every embed's explicit `->key()` — so this is a recorded departure rather than a defect.
It is called out because the sibling array two arms up (the tabs) preserves its keys *on purpose*, and a reader who knows that will wonder why this one does not.

The mount props are `ownerRecord` (the page's record **key**) and `pageClass` (`static::class`), then the manager's own `getDefaultProperties()`, then a `RelationManagerConfiguration`'s `getProperties()` spread last — Filament's order, so a registration's properties win over the manager's defaults.

### The content schema is built on every live request

Naming `content` in `schemas()` puts it in `getCachedSchemas()`, which `HasSchemas`' `#[PreDehydrate]` old-state pass walks on **every** live request of the edit page — so the whole builder runs each time: the resource's `getRelations()`, each manager's `canViewForRecord()`, and every tab's `getTitle()` / `getIcon()` / `getBadge()` / `getBadgeColor()` / `getBadgeTooltip()`.

Those statics must therefore stay O(1).
A counting badge is one query per live request of the page, the accepted ceiling: deferred badges, Filament's answer for anything heavier, [do not port](relation-manager.md#deferred-tab-badges-do-not-port), and nothing here memoises a badge in a static.

### The `relation` binding is the toolkit's only static `url:` mapping

Filament binds the property with `#[Url(as: 'relation')]`, so a tab switch is linkable; ours ships the same binding, and it is the only place in the toolkit that reaches UX's `url:` argument **directly** rather than through a [`modifier:`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page).
The reason is that the name is fixed.
A table's `?page=` has to be disambiguated per host, so its mapping can only be computed at runtime; `?relation=` names one property on one trait that only routed record pages compose, so a `new UrlMapping(as: 'relation')` in the attribute says everything a modifier would and costs no method.
The binding also works on a nested live component: UX's [`RequestInitializeSubscriber`](../../../vendor/symfony/ux-live-component/src/EventListener/RequestInitializeSubscriber.php) hooks `PostMountEvent` for every live component, nested included, so the binding works on a manager embedded in a page as it does on the page itself.
It is a routed page's prop either way, which is why it is bound here and a manager's own [`?tab=`](has-tabs.md) is not.

[`Resource::getIndexUrl()`'s parent arms](nested-resources.md#the-parent-arms-carry-no-relation-parameter) omit the `relation` parameter Filament appends, and [a nested breadcrumb renders unlinked](nested-resources.md#a-crumb-whose-url-the-trail-already-spends-renders-unlinked) because two trail URLs coincide without it.
That omission is a decision rather than a dependency: appending `?relation=<name>` to a breadcrumb URL is a real cost (it is noise in a URL a user copies, and it pins a tab the reader did not ask for) against a real benefit (the crumb links), and it is the one piece of `?relation=` left open.

## Divergences

### `getRelations()` is an instance method, like every other resource hook

Filament's is `public static function getRelations(): array` on the resource class-string.
Ours is an instance method on the [registered resource service](resource.md#resource-is-a-service-not-a-static-class-string-registry), for the reason every other resource hook is: a resource is a container service here, and a static could reach none of its injected dependencies.
The trait reaches it through `getResourceInstance()` rather than `static::getResource()::`.

The signature is otherwise Filament's, including the union of what an entry may be — a manager class-string, a `RelationGroup`, or a `RelationManagerConfiguration`.

### The defaulting is folded into the builder, and casts the key to a string

Filament defaults the active manager in `renderingHasRelationManagers()`, a Livewire lifecycle hook that runs before every render.
UX Live Components has no `rendering*` counterpart, so the same two lines sit at the top of `getRelationManagersContentComponent()` instead — which is called once per render, from `content()`, and therefore keeps the property's **self-healing** property: an `activeRelationManager` the registrations do not name falls back to the first key on every render rather than leaving the page with no active tab.

The move narrows the visibility with it: Filament's `renderingHasRelationManagers()` is `public` because Livewire dispatches lifecycle hooks by name and a `private` one would never be called, whereas ours is a `private` helper of the builder that calls it.
The cost is that a subclass cannot override the defaulting on its own: it overrides `getRelationManagersContentComponent()` instead, which is public and is where the defaulting runs.

One correction rides with the move.
Filament writes `array_key_first($managers)` straight into a `?string` property, so a list-shaped `getRelations()` puts an `int` in it; that only survives because Filament has no `strict_types`.
We cast (`(string) $firstKey`), and an empty registration list still leaves the property `null` rather than `''`.

### The combined content tab does not port

`hasCombinedRelationManagerTabsWithContent()`, the four `getContentTab*()` hooks and the `ContentTabPosition` enum are skipped whole.
They exist to put the page's own form in the tab bar beside the managers, which needs the faithful `content()` above; and Filament ships them behind a hook that returns `false` with no override anywhere in its own source, so a default install never reaches them.

### The server-mode tab bar has no overflow dropdown

`Tabs` renders its overflow dropdown from the client controller, which `liveComponentProperty()` mode deliberately does not load ([tabs.md](../schemas/tabs.md)).
A page registering more managers than fit the bar therefore scrolls rather than collapsing them into a dropdown.
Recorded here rather than fixed, because the fix belongs to `Tabs`' server mode, not to relation managers.
