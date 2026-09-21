# InteractsWithRecord (panels)

[`Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php)
— the one record a resource page is opened on: the route key the wire carries,
the record it lazily resolves to (scoped to the page's parent chain when there is
one), the title both are read through, the record's own breadcrumb, and the
bridge to the resource's record [sub-navigation](has-sub-navigation.md). It ports
[`Resources/Pages/Concerns/InteractsWithRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithRecord.php)
minus its authorization hooks.

The trait is mostly an extraction rather than new behaviour: the record prop and
its dehydration bridge, `hasRecord()`, `getRecordKey()`, `getRecord()` and
`getRecordTitle()` all shipped on [`EditRecord`](README.md#editrecord) with
M2 · 3 and moved here **unchanged** when
[`ManageRelatedRecords`](manage-related-records.md) needed the same six. What the
trait adds on top is what every record page shares and neither of them had: the
record's own breadcrumb, the sub-navigation bridge, and `mountRecord()`, where
the mount assignment order now lives. `EditRecord` composes
it and keeps only its own form, save and action hooks; `ViewRecord` composes it
at [M5 · 2](../roadmap.md#m5--view-page--infolists), which is the reason the
extraction happened now rather than being reconciled across three copies later.

⚠️ **Not to be confused with
[`Sylament\Actions\Concerns\InteractsWithRecord`](../actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host)**,
a different object tier that shares three member names. That one is composed by
an `Action` and answers *what an action is acting on* (`getRecord()`,
`getRecordTitle()`, `recordTitle()`); this one is composed by a page and answers
*what the page is open on*. Both are Filament's own names in Filament's own
places, so neither moves — read the trait by its **import**, never by the bare
`use InteractsWithRecord;` in a class body, which is the standing rule for every
name this codebase carries in two tiers.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Resources/Pages/Concerns/InteractsWithRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithRecord.php)
  — the whole trait
- [`Resources/Pages/EditRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php)
  — the page that composes it, and where our members lived until this epic

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$record`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) + [`dehydrateRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `#[Locked] public Model \| int \| string \| null $record` | done | — | [diverges](README.md#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge) |
| [`mountRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | the `$this->record = $this->resolveRecord($record)` line of each page's `mount()` | done | — | [semantics](#mountrecord-is-the-one-place-the-assignment-order-lives) |
| [`getRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getRecord()` + `resolveRecord()` | done | — | [semantics](#the-resolution-is-lazy-scoped-and-re-run-every-request) |
| [`hasRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) / [`getRecordKey()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `hasRecord()`; no counterpart for the key | done | — | [semantics](has-relation-managers.md#the-record-probes-read-the-wire-value-not-the-resolved-record) |
| [`getRecordTitle()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getRecordTitle()` | done | — | [diverges](README.md#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title) |
| [`getBreadcrumbs()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) — the record's own crumb | `getBreadcrumbs()` | done | — | [diverges](#the-record-crumb-lands-on-plain-resources-too-and-that-is-new-behaviour) |
| The crumb's `canView()` / `canEdit()` gates | the two `elseif` arms | todo | the authorization epic | [detail](#the-crumb-links-through-an-ungated-ladder) |
| [`getSubNavigation()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getSubNavigation()` | done | — | — |
| [`getSubNavigationParameters()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | `getSubNavigationParameters()` | done | — | [diverges](#the-sub-navigation-parameters-carry-keys-and-the-parent-chain) |
| `getDefaultActionRecord()` / `getDefaultActionRecordTitle()` / `getDefaultActionSuccessRedirectUrl()` / `getMountedActionSchemaRecord()` | the same four, the last as `getMountedActionSchemaModel()` | done | — | they stay on [`EditRecord`](README.md#editrecord) and [`ManageRelatedRecords`](manage-related-records.md), which answer them differently |
| `afterActionCalled()` — nulling the record after a delete | `afterActionCalled()` | skipped | — | [diverges](README.md#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge) — the redirect ends the request first, [as EditRecord records](README.md#editrecord) |
| `mountCanAuthorizeAccess()` / `hydrateCanAuthorizeAccess()` | the same two | todo | the authorization epic | [detail](#the-crumb-links-through-an-ungated-ladder) |
| `getWidgetData()` | `getWidgetData()` | todo | `sylament/widgets` | — |

The extraction is covered by **not** having moved: the whole panels suite passed
with zero test edits at the commit that moved the six members off `EditRecord`, which is what a behaviour-preserving refactor of
test-pinned code looks like. The members' own coverage is where it already was —
[`Integration/Resources/Pages/EditRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordTest.php)
for the wire prop, the two 404 shapes and the trail, and
[`Integration/Resources/Pages/Concerns/HasRelationManagersTest`](../../../packages/panels/tests/Integration/Resources/Pages/Concerns/HasRelationManagersTest.php)
for the key probes an embedded manager is mounted with. What is new here is
covered by
[`Integration/Resources/Pages/NestedResourcePagesTest`](../../../packages/panels/tests/Integration/Resources/Pages/NestedResourcePagesTest.php)
(the scoped resolution, the ancestor trail, the untitled-record arm),
[`Integration/Resources/Pages/ManageRelatedRecordsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ManageRelatedRecordsTest.php)
(the second host) and
[`Unit/Resources/Pages/PageTest`](../../../packages/panels/tests/Unit/Resources/Pages/PageTest.php)
(the band bridge and its parameters).

## Details

### The crumb links through an ungated ladder

Filament's record crumb links to the view page when `canView($record)`, else the
edit page when `canEdit($record)`, else nothing; both `mountCanAuthorizeAccess()`
and `hydrateCanAuthorizeAccess()` abort 403 before a page renders at all. With
authorization parked, the ladder collapses to `hasPage('view')` → `hasPage('edit')`
→ unlinked, and neither hook is ported. So **the crumb links every viewer to a
page it does not check they may open**, which is the exposure the resource pages
already carry rather than a new one — the pages behind those links are equally
ungated. It closes with the authorization epic, in one place: the two `hasPage()`
tests gain their `can*()` halves.

## Semantics

### `mountRecord()` is the one place the assignment order lives

`mountRecord(int|string $record, array $parentRecords = [])` assigns the parent
keys **first** and the record key second, and does nothing else — no resolution,
no fill. Both halves are load-bearing. The order is the D8 security property
written down once rather than in every page's `mount()`
([why](nested-resources.md#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount)):
a record page resolves inside its own mount tail, so a chain assigned after that
scopes nothing and the first GET would serve a foreign record. And the emptiness
is what lets each page keep its own tail — `EditRecord::mount()` calls
`mountRecord()` then `fillForm()`, `ManageRelatedRecords::mount()` calls it and
stops.

Filament has no equivalent method because it has no ordering problem: its
`mount()` resolves the record eagerly through `resolveRecord()`, which mounts the
parent chain itself as its first line.

### The resolution is lazy, scoped and re-run every request

`getRecord()` resolves the key through the resource's
`resolveRecordRouteBinding($key, $this->getParentRecordScope())` and memoizes on
`$resolvedRecord` for the request — a blank key, a key matching nothing and a
key **malformed for the record class** all throw `NotFoundHttpException`, which
[`PageController` unwraps out of Twig's render error](README.md#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error)
into a real 404. The scope is null for a page whose resource nests under nothing,
so a plain resource page resolves exactly as it did before this epic.

Laziness is what makes the prop safe on the wire: nothing is resolved at
dehydration time, and the next request re-resolves the key it was handed —
through the parent scope again — rather than trusting anything the previous
render decided.

The third shape is the one that changed behaviour for URLs that used to work,
and it is worth spelling out rather than discovering. A single-identifier key
encodes to itself, so `/admin/articles/7/edit` is untouched — but a run of
looser spellings resolved article 5 before and is a 404 now, on both platforms
and with no redirect to the canonical one: `05`, a percent-encoded leading
space, a trailing newline and `+5` resolved it on SQLite **and** on PostgreSQL,
and `5.0` on SQLite only, PostgreSQL having answered that one `22P02`, a 500,
exactly as it answered `abc`. Those are examples rather than the whole set — a
trailing space, `5e0` and a NUL-terminated spelling resolved it on SQLite too.
`abc` and `5abc` now reach no database at all, which is how
`/admin/articles/abc/edit` stopped being a 500 on PostgreSQL.

The refusal happens **before** anything is bound: on the default arm — a
resource naming no `$recordRouteKeyName` — a key no record of the class could
mint is answered with no statement of its own. That is a claim about the
record's own binding and nothing else. A blank key never reaches the binding at
all, being refused a frame earlier; and under a parent the chain above has
already been resolved by a statement of its own, the scope being evaluated as
the argument. Two well-formed-looking keys are still a 500 rather than a 404: a
canonical integer too wide for its column decodes, prints back to itself, binds
and raises the platform's own range error
([the residual](../schemas/record-key.md#an-integer-beyond-the-columns-width-still-raises-the-platforms-range-error)),
and a hand-written identifier DBAL type that refuses a malformed string with
anything but a `ConversionException` has that exception propagate, the frozen
seam catching the one kind ([the policy](../schemas/record-key.md#the-api)).

## Divergences

### The record crumb lands on plain resources too, and that is new behaviour

Filament inserts the page's own record crumb behind `$record->exists &&
hasRecordTitle()`, on every record page, nested or not. This port did not — the
M2 · 3 edit page trailed two crumbs (`Articles → Edit`) with a test comment
asserting that Filament did the same, which was simply wrong about vendor. The
port now matches: a resource declaring `$recordTitleAttribute` trails
`Articles → Alpha → Edit`, with the middle crumb linking to the record's view
page, else its edit page, else unlinked.

The blast radius is every titled resource's edit page — in the demo,
`/admin/articles/{id}/edit`, `/admin/authors/{id}/edit` and
`/admin/topics/{id}/edit` each grew a crumb. Create and list pages are untouched
(they carry no record), and a resource with no `recordTitleAttribute` is
untouched (the gate stays closed, which
`NestedResourcePagesTest`'s untitled-record case pins). Recorded as a divergence
from **our own shipped behaviour** rather than from Filament's, because the
change is what removed the divergence: gating the crumb on nesting instead would
have made `/admin/articles/{id}/edit` and
`/admin/articles/{a}/sections/{s}/edit` trail differently for no reason a user
can see.

### The sub-navigation parameters carry keys, and the parent chain

`getSubNavigationParameters()` answers `['record' => $key, ...$parentRecords]`
where Filament answers `['record' => $record]` — the resolved model. Two
differences, one forced and one required.

The record is the **route key**, not the record, because the band's URLs are
minted through `Resource::getUrl()`, which keys an object anyway; handing it the
key skips a resolution the page may not otherwise need (a band renders on every
page load, including one whose record nothing else reads). And the ancestors ride
along, because a nested page's sibling pages live under the same chain: without
them, every band item on a nested page would raise
`MissingMandatoryParametersException` instead of rendering. Filament fills those
from the request instead, through the guessing mechanism
[it cannot have here](nested-resources.md#guessing-walks-the-records-own-associations-never-the-request).
