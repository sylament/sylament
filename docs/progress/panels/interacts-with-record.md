---
description: 'The record a resource page is opened on: the route key on the wire, its lazy scoped resolution, the record title and crumb, and the sub-navigation bridge.'
ports:
  - vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithRecord.php
owns:
  - packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php
---
# InteractsWithRecord (panels)

[`Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) is composed by [`EditRecord`](edit-record.md) and [`ManageRelatedRecords`](manage-related-records.md).
Each composer keeps its own mount tail, form and action hooks; the trait holds what every record page shares.

⚠️ **Not to be confused with [`Sylament\Actions\Concerns\InteractsWithRecord`](../actions/interacts-with-record.md)**, a different object tier that shares three member names.
That one is composed by an `Action` and answers *what an action is acting on* (`getRecord()`, `getRecordTitle()`, `recordTitle()`); this one is composed by a page and answers *what the page is open on*.
Both are Filament's own names in Filament's own places, so neither moves — read the trait by its **import**, never by the bare `use InteractsWithRecord;` in a class body, which is the standing rule for every name this codebase carries in two tiers.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$record`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) + [`dehydrateRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `#[Locked] public Model \| int \| string \| null $record` | done | — | [diverges](#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge) |
| [`mountRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | the `$this->record = $this->resolveRecord($record)` line of each page's `mount()` | done | — | [semantics](#mountrecord-is-the-one-place-the-assignment-order-lives) |
| [`getRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getRecord()` + `resolveRecord()` | done | — | [semantics](#the-resolution-is-lazy-scoped-and-re-run-every-request) |
| [`hasRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) / [`getRecordKey()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `hasRecord()`; no counterpart for the key | done | — | [semantics](#the-record-probes-read-the-wire-value-not-the-resolved-record) |
| [`getRecordTitle()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getRecordTitle()` | done | — | [diverges](edit-record.md#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title) |
| [`getBreadcrumbs()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) — the record's own crumb | `getBreadcrumbs()` | done | — | — |
| The crumb's `canView()` / `canEdit()` gates | the two `elseif` arms | blocked | authorization | [detail](#the-crumb-links-through-an-ungated-ladder) |
| [`getSubNavigation()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getSubNavigation()` | done | — | — |
| [`getSubNavigationParameters()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getSubNavigationParameters()` | done | — | [diverges](#the-sub-navigation-parameters-carry-keys-and-the-parent-chain) |
| `getDefaultActionRecord()` / `getDefaultActionRecordTitle()` / `getDefaultActionSuccessRedirectUrl()` / `getMountedActionSchemaRecord()` — on each record page | the same four, the last as `getMountedActionSchemaModel()` | done | — | [edit-record.md](edit-record.md#surface) · [manage-related-records.md](manage-related-records.md) |
| `afterActionCalled()` — nulling the record after a delete | `afterActionCalled()` | skipped | — | [diverges](#afteractioncalled-is-not-ported-because-the-delete-redirect-ends-the-request-first) |
| `mountCanAuthorizeAccess()` / `hydrateCanAuthorizeAccess()` | the same two | blocked | authorization | [detail](#the-crumb-links-through-an-ungated-ladder) |
| `getWidgetData()` | `getWidgetData()` | blocked | a widgets port (`sylament/widgets` ships no widget) | — |

## Details

### The crumb links through an ungated ladder

Filament's record crumb links to the view page when `canView($record)`, else the edit page when `canEdit($record)`, else nothing; both `mountCanAuthorizeAccess()` and `hydrateCanAuthorizeAccess()` abort 403 before a page renders at all.
With authorization parked, the ladder collapses to `hasPage('view')` → `hasPage('edit')` → unlinked, and neither hook is ported.
So **the crumb links every viewer to a page it does not check they may open**, which is the exposure the resource pages already carry rather than a new one — the pages behind those links are equally ungated.
It closes when authorization lands, in one place: the two `hasPage()` tests in `Resources\Pages\Page::getRecordBreadcrumbUrl()` gain their `can*()` halves.

## Semantics

### `mountRecord()` is the one place the assignment order lives

`mountRecord(int|string $record, array $parentRecords = [])` assigns the parent keys **first** and the record key second, and does nothing else — no resolution, no fill.
Both halves are load-bearing.
The order is a security property written down once rather than in every page's `mount()` ([why](nested-resources.md#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount)): a record page resolves inside its own mount tail, so a chain assigned after that scopes nothing and the first GET would serve a foreign record.
And the emptiness is what lets each page keep its own tail — `EditRecord::mount()` calls `mountRecord()`, then `fillForm()` and `rememberData()`; `ManageRelatedRecords::mount()` calls it and stops.

Filament has no equivalent method because it has no ordering problem: its `mount()` resolves the record eagerly through `resolveRecord()`, which mounts the parent chain itself as its first line.

### The record probes read the wire value, not the resolved record

`hasRecord()` and `getRecordKey()` both answer off the `$record` `LiveProp`, which holds the route key.
Filament's `hasRecord()` is a null check on a resolved model and it has no key probe at all, because its relation-manager embed is handed the model itself.
Ours cannot be: the [`HasRelationManagers`](has-relation-managers.md) embed is a live component whose own `ownerRecord` property is a string on the wire ([relation-manager.md](relation-manager.md#the-owner-rides-the-wire-as-a-route-key-behind-a-dehydration-bridge)).

Reading the raw property rather than `getRecord()` is also what keeps `hasRecord()` cheap and total — it is called on an un-mounted page (the page controller reads a page's title before mounting it), where `getRecord()` throws.

### The resolution is lazy, scoped and re-run every request

`getRecord()` resolves the key through the resource's `resolveRecordRouteBinding($key, $this->getParentRecordScope())` and memoizes on `$resolvedRecord` for the request — a blank key, a key matching nothing and a key **malformed for the record class** all throw `NotFoundHttpException`, which [`PageController` unwraps out of Twig's render error](routing.md#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error) into a real 404.
The scope is null for a page whose resource nests under nothing, so a plain resource page resolves unscoped, by its key alone.

Laziness is what makes the prop safe on the wire: nothing is resolved at dehydration time, and the next request re-resolves the key it was handed — through the parent scope again — rather than trusting anything the previous render decided.

On the default arm — a resource naming no `$recordRouteKeyName` — a malformed key is refused before anything is bound ([the decode](../schemas/record-key.md#a-malformed-key-never-reaches-the-database)): `/admin/articles/abc/edit` is a 404 that runs no statement, a looser spelling such as `05` or `+5` does not resolve article 5, and nothing redirects to the canonical spelling.
That is a claim about the record's own binding and nothing else.
A blank key never reaches the binding at all, being refused a frame earlier; and under a parent the chain above has already been resolved by a statement of its own, the scope being evaluated as the argument.
Two well-formed-looking keys are still a 500 rather than a 404: a canonical integer too wide for its column decodes, prints back to itself, binds and raises the platform's own range error ([the residual](../schemas/record-key.md#an-integer-beyond-the-columns-width-still-raises-the-platforms-range-error)), and a hand-written identifier DBAL type that refuses a malformed string with anything but a `ConversionException` has that exception propagate, the frozen seam catching the one kind ([the policy](../schemas/record-key-contract.md#the-api)).

## Divergences

### The sub-navigation parameters carry keys, and the parent chain

`getSubNavigationParameters()` answers `['record' => $key, ...$parentRecords]` where Filament answers `['record' => $record]` — the resolved model.
Two differences, one forced and one required.

The record is the **route key**, not the record, because the band's URLs are minted through `Resource::getUrl()`, which keys an object anyway; handing it the key skips a resolution the page may not otherwise need (a band renders on every page load, including one whose record nothing else reads).
And the ancestors ride along, because a nested page's sibling pages live under the same chain: without them, every band item on a nested page would raise `MissingMandatoryParametersException` instead of rendering.
Filament fills those from the request instead, through the guessing mechanism [it cannot have here](nested-resources.md#guessing-walks-the-records-own-associations-never-the-request).

### The record prop carries the route key and needs a dehydration bridge

Filament's `InteractsWithRecord` declares `public Model | int | string | null $record` and lets Livewire carry the model.
Ours is `#[LiveProp(dehydrateWith: 'dehydrateRecord')] public ?string $record = null`, and both halves of that are forced by ux-live-component.

**Single type.**
`LiveComponentMetadataFactory::createLivePropMetadata()` throws `LogicException('Union or intersection types are not supported for LiveProps.')` for any union-typed prop, so Filament's `int|string` cannot survive on the property.
It survives on `mount(int|string $record)`, which is not a prop and is the signature a migrating user actually writes.
A route parameter arrives as a string anyway, so the cast in `mountRecord()` is a formality.

**The bridge.**
`LiveComponentHydrator::dehydrate()` reads each prop through Symfony's `PropertyAccessor`, which prefers a same-named **getter** over the property — and `getRecord()` returns the resolved **entity**.
Left alone, the wire would carry an object where a `?string` is declared, and the hydrator would throw on a value it cannot dehydrate.
`dehydrateRecord()` returns `$this->record`, the raw key, and the prop is a scalar on the wire again.
This is the [`docs/internals/php.md` dehydration trap](../../internals/php.md#a-liveprops-getter-shadows-it) in its second shipped form, mirroring tables' [`dehydrateTableSearch`](../tables/host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary); the fix is always the bridge, never renaming the Filament getter.
A composite key is one string too — the `?string` type, the checksum and the bridge carry `1~A=2FB=20C` exactly as they carry `7` — so a composite key asks nothing of this prop, nor of the parent chain's `array<string, string>`, nor of a relation manager's `ownerRecord`.

Typing the prop to the entity and letting `DoctrineEntityHydrationExtension` carry the identifier would sidestep both (the demo's `ArticleForm` does exactly that, and it works).
The port does not, because a resource page must serve **any** record class, and a `LiveProp`'s type is read off the property at compile time, so there is no type to write on a generic base.
The key is the one thing every resource can put on the wire, and `Resource::resolveRecordRouteBinding()` already exists to turn it back into a record through the resource's own query — which is also stricter than a `find()`: a record the query scopes out 404s instead of loading.

`packages/panels/tests/Integration/Resources/Pages/EditRecordTest.php` reads the rendered `data-live-props-value` and asserts `record` is the key string, and `CompositeRecordPagesTest` asserts the same of a two-component one.
Removing the bridge does not fail those two alone: the prop then dehydrates as the resolved **entity**, which the hydrator refuses, so every case that renders a record page goes red with it.

### `afterActionCalled()` is not ported, because the delete redirect ends the request first

Filament's trait overrides `afterActionCalled()` to null `$this->record` once the record does not exist, so Livewire does not try to dehydrate a deleted row after a delete action.
The port's record pages send a header delete away instead: `EditRecord::getDefaultActionSuccessRedirectUrl()` answers the resource's index URL for a `DeleteAction`, and `HasActions` returns that redirect from the live action before any re-render.
With no re-render left to protect, there is nothing for the override to null.
It returns if a record page ever keeps rendering after deleting its own record.
