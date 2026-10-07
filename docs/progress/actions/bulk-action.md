---
description: The preset an action takes to run over a table's selected records rather than over one row. (draft)
ports:
  - vendor/filament/actions/src/BulkAction.php
  - vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php
  - vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php
  - vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php
owns:
  - packages/actions/src/BulkAction.php
---
# BulkAction (actions)

[`Sylament\Actions\BulkAction`](../../../packages/actions/src/BulkAction.php) — the preset an action takes to run over a table's **selected records** rather than over one row.
It is a preset, not a type: `setUp()` calls `bulk()` and `accessSelectedRecords()` and nothing else, so `Action::make('x')->bulk()->accessSelectedRecords()` is behaviourally identical, exactly as in Filament.
Every capability it presets lives on [`Action`](action.md) through three concerns audited here — `InteractsWithSelectedRecords`, `CanFetchSelectedRecords` and `CanDeselectRecordsAfterCompletion` — because a plain `Action` may want any of them (a record action that also reports a partial failure, say) and because the two flags are orthogonal: `bulk()` decides **routing** (which of the table's two registries the host resolves the mounted name from), `accessSelectedRecords()` decides **data access** (whether the record readers throw).

[`BulkActionGroup`](../../../packages/actions/src/BulkActionGroup.php) is audited in [`action-group.md`](action-group.md), where the rest of the group surface it presets lives.

Covered by [`BulkActionTest`](../../../packages/actions/tests/Unit/BulkActionTest.php) for the preset, the marker, the accounting and the two readers' gates, by [`ActionTest`](../../../packages/actions/tests/Unit/ActionTest.php) for the gated-off injection arms on an ordinary action, by [`DeleteBulkActionTest`](../../../packages/tables/tests/Integration/DeleteBulkActionTest.php) in the **tables** suite for the minted counters and the two failure toasts they title (a real selection host and a real database are what mint them), by [`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) for the service registration `make()` depends on, by [`ActionsBelongsToTableTest`](../../../packages/tables/tests/Unit/ActionsBelongsToTableTest.php) in the **tables** suite for the `bulk` context key (which needs a real `Table`), and by [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php) in the **notifications** suite for the partial-failure warning arm (which needs both bundles booted).

## Surface

Every member of the preset and of the three concerns it turns on.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`setUp()` — `bulk()` + `accessSelectedRecords()`](../../../packages/actions/src/BulkAction.php) | [`BulkAction.php`](../../../vendor/filament/actions/src/BulkAction.php) | done | — | — |
| [`getExtraAttributes()` — the bulk-only marker](../../../packages/actions/src/BulkAction.php) | [`BulkAction.php`](../../../vendor/filament/actions/src/BulkAction.php) (`x-cloak` + `x-show`) | done | — | [diverges](#bulk-only-visibility-is-a-data-marker-the-table-controller-flips-not-an-alpine-expression) |
| [`bulk()` / `isBulk()`](../../../packages/actions/src/Action.php) (on `Action`) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) | done | — | — |
| [The `bulk` context key](../../../packages/actions/src/Action.php) (`getContext()`) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`getContext()`) | done | — | — |
| [The `getStatus()` selected-records tally](../../../packages/actions/src/Action.php) | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`getStatus()`) | done | — | — |
| [`accessSelectedRecords()` / `canAccessSelectedRecords()` / `hasCanAccessSelectedRecords()`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | done | — | — |
| [`reportBulkProcessingFailure()` / `reportBulkProcessingSuccessfulRecordsCount()` / `reportCompleteBulkProcessingFailure()` / `getTotalSelectedRecordsCount()`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | done | — | [diverges](#the-failure-reports-count-they-do-not-carry-keyed-messages) |
| [`CanNotify`'s counting layer](../../../packages/actions/src/Concerns/CanNotify.php) (`getFailureNotificationNamedInjections()`, the `warning()` arm) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | in progress | the keyed message buckets | [detail](#the-failure-notification-carries-four-injections-of-filaments-nine) |
| [`fetchSelectedRecords()` / `shouldFetchSelectedRecords()`](../../../packages/actions/src/Concerns/CanFetchSelectedRecords.php) | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | done | — | — |
| `chunkSelectedRecords()` / `getSelectedRecordsChunkSize()` | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | skipped | — | [diverges](#chunking-is-not-ported) |
| [`deselectRecordsAfterCompletion()` / `shouldDeselectRecordsAfterCompletion()`](../../../packages/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | [`Concerns/CanDeselectRecordsAfterCompletion.php`](../../../vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | done | — | — |
| The `call()` `finally` that acts on that flag | [`Action.php`](../../../vendor/filament/actions/src/Action.php) (`call()`) | done | — | [semantics](#the-post-call-deselect-reaches-its-host-through-the-nullable-accessor) |
| [`getSelectedRecords()` / `getSelectedRecordsQuery()`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) + their closure-injection arms | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | done | — | [semantics](#the-two-record-readers-share-one-gate-and-one-message) · [diverges](action.md#the-selected-record-injections-are-gated-on-the-access-flag) |
| `getIndividuallyAuthorizedSelectedRecords()` + the grouped denial buckets | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | blocked | `CanBeAuthorized` | [detail](#per-record-authorization-waits-until-authorization-lands) |
| `getBulkProcessingFailureMessages()` + the keyed `bulkProcessingFailureMessages` buckets | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | todo | — | [diverges](#the-failure-reports-count-they-do-not-carry-keyed-messages) |

## Details

### The failure notification carries four injections of Filament's nine

[`CanNotify::getFailureNotificationNamedInjections()`](../../../packages/actions/src/Concerns/CanNotify.php) returns `failureCount`, `isAll`, `successCount` and `totalCount` — the four a counting title closure needs, and the four [`DeleteBulkAction`](../../../vendor/filament/actions/src/DeleteBulkAction.php)'s own `failureNotificationTitle` reads.
Filament returns five more, all keyed off machinery this port does not carry: `authorizationFailureMessages` and `missingAuthorizationFailureMessageCount` (the [unported authorization](#per-record-authorization-waits-until-authorization-lands)), `processingFailureMessages`, `failureMessages` and `missingProcessingFailureMessageCount` (the [keyed message buckets](#the-failure-reports-count-they-do-not-carry-keyed-messages)).
`getFailureNotificationBody()` and the two `missingBulk*NotificationMessage()` setters are absent for the same reason, and the body is additionally an escaping question the port refuses to answer by copying: Filament concatenates `"<p>{$message}</p>"` around values that can come from a user closure, which in Twig is a `Markup`-versus-escaped decision this package has not taken yet.
**Next step:** land the keyed buckets and the body together, with the escaping contract written down first.

### Per-record authorization waits until authorization lands

Filament's `getIndividuallyAuthorizedSelectedRecords()` filters the selected set through a per-record `Gate` check, decrementing the successful counter and bucketing the grouped denial messages in a `finally`.
There is no authorization concern anywhere in the actions port (`CanBeAuthorized` is a **todo** row in the [package README](README.md#features)), so the injection arms resolve plain `getSelectedRecords()` instead, and `shouldAuthorizeIndividualRecords()` has nothing to ask.
**Blocked** on `CanBeAuthorized`, parked like the rest of authorization; port the filter and the buckets together when it lands.

## Semantics

### The two record readers share one gate and one message

Filament writes the `accessSelectedRecords()` gate twice — once in `getSelectedRecords()`, once in `getSelectedRecordsQuery()` — with two messages differing only in *"the selected records"* versus *"the selected records query"*.
The port resolves the host through one private `getSelectedRecordsHost()` both readers call, so both raise the `getSelectedRecords()` wording:

> The action [archive] is attempting to access the selected records from the
> table, but it is not using [accessSelectedRecords()], so they are not
> available.

The same helper carries a check Filament has no room for.
Filament's `getLivewire()` is typed to a Livewire component and its `HasTable` methods are reached without narrowing; ours is typed `InteractsWithActions`, and the two selection members live on tables' [`InteractsWithTable`](../tables/has-table.md), which `sylament/actions` only `suggest`s.
So the helper narrows with an `instanceof` and throws a second `LogicException` naming that contract when the host is not a table host — the same [guarded soft reference](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) `resolveTableAction()` uses, one class over.
An action with **no** live component at all does not reach either message: `getLiveComponent()` throws its own `RuntimeException` first, exactly as Filament's does.

Under `fetchSelectedRecords(false)` the `$records` a body receives are **record keys**, not raw column values, and that distinction bites on a single identifier too: the key pluck mints each row's key through [`RecordKey`](../schemas/record-key.md), so an identifier whose column form is not its printed form — a `uuid` stored `BINARY(16)` on MySQL or SQLite — hands back the printed string rather than the bytes a raw pluck returns.
A body that writes by key therefore **decodes through `IdentifierConstraint`** and never binds one raw, and every shipped one does: `DeleteBulkAction`, `DetachBulkAction` and `DissociateBulkAction` — the detach through the reader's by-key reference, the dissociate through a chunked `in()` on its DQL `UPDATE` builder, and — where the foreign key spans several columns — a chunked `inColumns()` on a DBAL one.
Where a body takes the selection's **shape** for granted the refusal sits outside its `try`, so a host feeding neither entities nor keys is a developer error rather than a danger toast — though only in part.
`DetachBulkAction` has one pre-walk outside its `try`, covering both shapes it accepts; `DissociateBulkAction` moved only its **by-key** walk, and its fetching arm still raises *can only dissociate entities* inside the `try`, where it becomes the danger toast.
That by-key arm needs no second pre-flight for the one statement DQL cannot write at all — nulling a to-one that spans several join columns — because it writes that statement in SQL ([the refusal, and the SQL route that closed it for a foreign key spanning several columns](dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)).

### The post-call deselect reaches its host through the nullable accessor

`call()`'s `finally` is Filament's, minus the second branch — the port's [`CanBeHidden`](action.md#canbehidden-omits-authorization-group-and-table-cache-integration) has no visibility cache to clear — and plus a narrowing:

```php
if ($this->shouldDeselectRecordsAfterCompletion() && ($host = $this->getLiveComponentOrNull()) instanceof InteractsWithTable) {
    $host->deselectAllTableRecords();
}
```

Filament reaches `getLivewire()->deselectAllTableRecords()` unguarded, which is safe there only because the flag is set by bulk actions that always have a Livewire host.
Here the throwing accessor would fatal any detached action carrying the flag — a unit test's `deselectRecordsAfterCompletion()` action, or one built with `new` — so the read goes through [`getLiveComponentOrNull()`](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter), and the `instanceof` is a real narrowing of `?InteractsWithActions` rather than a dead check.
The deselect is in a `finally`, so it also runs when the body throws or halts: a selection is cleared because the action *ran*, not because it succeeded.

## Divergences

### Bulk-only visibility is a data marker the table controller flips, not an Alpine expression

Filament hides a bulk trigger while nothing is selected with two Alpine attributes on the trigger — `x-cloak` plus `x-show="getSelectedRecordsCount()"`, evaluated against the `filamentTable` Alpine component.
There is no Alpine here and no client-authoritative selection count to evaluate against, so the port stamps a **marker** instead: `getExtraAttributes()` returns `['data-sy-table-bulk-only' => true, ...parent::getExtraAttributes()]` — which the attribute bag renders as `data-sy-table-bulk-only="true"`, since it stringifies a boolean on a `data-*` key; the template and the controller both match on the attribute's *presence*, so the rendered value is incidental — and `BulkActionGroup::getExtraDropdownAttributes()` stamps the same marker on the **group's dropdown root** rather than on its trigger.
The server renders `hidden` on every marked element while the table reports no selection, and the `sylament--tables--table` Stimulus controller flips `hidden` as the count changes — so the marker is the contract between the two halves, and the initial paint is correct without waiting for JavaScript, which is what `x-cloak` exists to paper over.

Two shapes are kept from Filament and one is not.
Kept: the parent spread comes **last**, so a caller's own `extraAttributes(['data-sy-table-bulk-only' => …])` wins; and the override hooks are the same two Filament uses, so the attach/detach bulk actions that subclass this base inherit them unchanged.
Not kept: Filament's `getExtraDropdownAttributes()` spreads `parent::getExtraAttributes()` — the *trigger* bag — inside the *dropdown* accessor, which reads as a copy-paste slip and would silently drop a caller's `extraDropdownAttributes()`; the port spreads `parent::getExtraDropdownAttributes()`.

### The failure reports count; they do not carry keyed messages

Filament's three report methods each take `?string $key` and `string|Closure|null $message`, bucketing identical failures into `$bulkProcessingFailureMessages[$key] = ['message' => …, 'count' => …]` so the notification body can render *"3 of 10 could not be deleted"* per reason.
The port's take no arguments: `reportBulkProcessingFailure()` decrements the successful count, `reportBulkProcessingSuccessfulRecordsCount(int $count)` sets it, `reportCompleteBulkProcessingFailure()` zeroes it.
The counting half is what the shipped failure notification reads (a title closure taking `successCount` / `totalCount`), and the message half needs the notification-body escaping contract decided first — so it is deferred rather than half-ported with an argument nothing consumes.
One arithmetic detail goes with it: Filament's `reportBulkProcessingSuccessfulRecordsCount()` also folds the difference into `bulkProcessingFailureWithoutMessageCount`; with no buckets to fold into, the port's is the bare assignment.

### Chunking is not ported

`chunkSelectedRecords()` / `getSelectedRecordsChunkSize()` are dropped, so `CanFetchSelectedRecords` ships as the fetch flag alone — the **fetching** arm, that is.
A by-key arm chunks its own key list — the delete's and the dissociate's — by [the chunk rule](../schemas/record-key.md#the-chunk-rule) rather than by a user-set size, because DQL parse time is superlinear in bound-key count and a platform caps the parameters one statement binds — where the dissociate's statement is SQL, for a foreign key spanning several columns, the cap alone is what its chunks answer.
The two are unrelated mechanisms wearing one word, and only the fetching one is unported.
Two reasons for that.
**No shipped Filament action calls `chunkSelectedRecords()`** — the grep over `vendor/filament/actions/src` returns only the definitions — so the port would be API surface with no consumer on either side.
And the shape it produces is Laravel's: a `LazyCollection` whose count cannot be taken without consuming it, which is why Filament's `getSelectedRecords()` pays a second `COUNT` query for the lazy branch.
Doctrine's equivalent (`toIterable()`, or paginated batches) has different detachment and identity-map semantics, so the port would be inventing rather than translating.
Revisit if a bulk action over a very large selection needs it; the entry point is a `chunk` argument threaded through the table host's `getSelectedTableRecords()`.
