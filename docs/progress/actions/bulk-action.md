---
description: The preset that runs an action over a table's selected records, and the three selected-records concerns it turns on.
ports:
  - vendor/filament/actions/src/BulkAction.php
  - vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php
  - vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php
  - vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php
owns:
  - packages/actions/src/BulkAction.php
  - packages/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php
  - packages/actions/src/Concerns/CanFetchSelectedRecords.php
  - packages/actions/src/Concerns/InteractsWithSelectedRecords.php
---
# BulkAction (actions)

[`Sylament\Actions\BulkAction`](../../../packages/actions/src/BulkAction.php) is a preset, not a type: `setUp()` calls `bulk()` and `accessSelectedRecords()` and nothing else, so `Action::make('x')->bulk()->accessSelectedRecords()` behaves identically, as in Filament.
Every capability it presets lives on [`Action`](action.md) through the three concerns this file documents, because a plain `Action` may want any of them, and because the two flags are orthogonal: `bulk()` decides **routing** (which of the table's two registries the host resolves the mounted name from), and `accessSelectedRecords()` decides **data access** (whether the record readers throw).
[`BulkActionGroup`](../../../packages/actions/src/BulkActionGroup.php) is documented in [`action-group.md`](action-group.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`setUp()` — `bulk()` + `accessSelectedRecords()`](../../../packages/actions/src/BulkAction.php) | [`BulkAction.php`](../../../vendor/filament/actions/src/BulkAction.php) | done | — | — |
| [`getExtraAttributes()` — the bulk-only marker](../../../packages/actions/src/BulkAction.php) | [`BulkAction.php`](../../../vendor/filament/actions/src/BulkAction.php) (`x-cloak` + `x-show`) | done | — | [diverges](#bulk-only-visibility-is-a-data-marker-the-table-controller-flips-not-an-alpine-expression) |
| [`accessSelectedRecords()` / `canAccessSelectedRecords()` / `hasCanAccessSelectedRecords()`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | done | — | — |
| [The report methods and `getTotalSelectedRecordsCount()`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | done | — | [diverges](#the-failure-reports-count-they-do-not-carry-keyed-messages) |
| [`fetchSelectedRecords()` / `shouldFetchSelectedRecords()`](../../../packages/actions/src/Concerns/CanFetchSelectedRecords.php) | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | done | — | [semantics](#under-fetchselectedrecordsfalse-a-body-receives-record-keys-not-column-values) |
| `chunkSelectedRecords()` / `getSelectedRecordsChunkSize()` | [`Concerns/CanFetchSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/CanFetchSelectedRecords.php) | skipped | — | [diverges](#chunking-is-not-ported) |
| [`deselectRecordsAfterCompletion()` / `shouldDeselectRecordsAfterCompletion()`](../../../packages/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | [`Concerns/CanDeselectRecordsAfterCompletion.php`](../../../vendor/filament/actions/src/Concerns/CanDeselectRecordsAfterCompletion.php) | done | — | [semantics](#the-post-call-deselect-reaches-its-host-through-the-nullable-accessor) |
| [`getSelectedRecords()` / `getSelectedRecordsQuery()`](../../../packages/actions/src/Concerns/InteractsWithSelectedRecords.php) + their closure-injection arms | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | done | — | [semantics](#the-two-record-readers-share-one-gate-and-one-message) · [diverges](action.md#the-selected-record-injections-are-gated-on-the-access-flag) |
| `getIndividuallyAuthorizedSelectedRecords()` + the grouped denial buckets | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | blocked | `CanBeAuthorized` | [detail](#per-record-authorization-waits-until-authorization-lands) |
| `getBulkProcessingFailureMessages()` + the keyed `bulkProcessingFailureMessages` buckets | [`Concerns/InteractsWithSelectedRecords.php`](../../../vendor/filament/actions/src/Concerns/InteractsWithSelectedRecords.php) | todo | — | [diverges](#the-failure-reports-count-they-do-not-carry-keyed-messages) |

## Details

### Per-record authorization waits until authorization lands

Filament's `getIndividuallyAuthorizedSelectedRecords()` filters the selected set through a per-record authorization response, decrementing the successful count and bucketing the grouped denial messages in a `finally`.
The actions port has no `CanBeAuthorized`, so the `records` and `selectedRecords` injection arms resolve plain `getSelectedRecords()`, and there is no `shouldAuthorizeIndividualRecords()` to ask.
The filter and its denial buckets land together with `CanBeAuthorized`.

## Semantics

### The two record readers share one gate and one message

Filament writes the `accessSelectedRecords()` gate twice, once in `getSelectedRecords()` and once in `getSelectedRecordsQuery()`, with two messages differing only in *"the selected records"* versus *"the selected records query"*.
The port resolves the host through one private `getSelectedRecordsHost()` that both readers call, so both raise the `getSelectedRecords()` wording:

> The action [archive] is attempting to access the selected records from the table, but it is not using [accessSelectedRecords()], so they are not available.

The same helper carries a check Filament has no room for.
Filament's `getLivewire()` returns `?object`, and its `HasTable` methods are called on it without narrowing; ours is typed `InteractsWithActions`, and the two selection members live on tables' [`InteractsWithTable`](../tables/has-table.md), which `sylament/actions` only `suggest`s.
So the helper narrows with an `instanceof` and throws a second `LogicException` naming that contract when the host is not a table host, the same [guarded soft reference](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) `resolveTableAction()` uses.
An action with no live component at all reaches neither message: `getLiveComponent()` throws its own `RuntimeException` first, where Filament's `getLivewire()` answers `null` and the call on it fails with an `Error`.

### Under `fetchSelectedRecords(false)` a body receives record keys, not column values

The key pluck mints each row's key through [`RecordKey`](../schemas/record-key.md), so an identifier whose column form is not its printed form, such as a `uuid` stored `BINARY(16)` on MySQL or SQLite, arrives as the printed string rather than the bytes a raw pluck returns.
A body that writes by key therefore decodes each key through the key contract and never binds one raw.
The three shipped bodies do: [`DeleteBulkAction`](delete-action.md#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys) and [`DissociateBulkAction`](dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns) through `IdentifierConstraint`, and [`DetachBulkAction`](detach-action.md#the-no-fetch-arm-detaches-through-references-not-a-join-table-delete) through the reader's `getReference()`.

### The post-call deselect reaches its host through the nullable accessor

`call()`'s `finally` is Filament's, minus the second branch, because the port's [`CanBeHidden`](action.md#canbehidden-omits-authorization-group-and-table-cache-integration) has no visibility cache to clear, and plus a narrowing:

```php
if ($this->shouldDeselectRecordsAfterCompletion() && ($host = $this->getLiveComponentOrNull()) instanceof InteractsWithTable) {
    $host->deselectAllTableRecords();
}
```

Filament reaches `getLivewire()->deselectAllTableRecords()` unguarded, which is safe there only because the flag is set by bulk actions that always have a Livewire host.
Here the throwing accessor would fatal any detached action carrying the flag, such as a unit test's `deselectRecordsAfterCompletion()` action or one built with `new`, so the read goes through [`getLiveComponentOrNull()`](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter), and the `instanceof` is a real narrowing of `?InteractsWithActions` rather than a dead check.
The deselect is in a `finally`, so it also runs when the body throws or halts: a selection is cleared because the action *ran*, not because it succeeded.

## Divergences

### Bulk-only visibility is a data marker the table controller flips, not an Alpine expression

Filament hides a bulk trigger while nothing is selected with two Alpine attributes on the trigger, `x-cloak` plus `x-show="getSelectedRecordsCount()"`, evaluated against the `filamentTable` Alpine component.
There is no Alpine here and no client-authoritative selection count to evaluate against, so the port stamps a **marker** instead: `getExtraAttributes()` returns `['data-sy-table-bulk-only' => true, ...parent::getExtraAttributes()]`, and `BulkActionGroup::getExtraDropdownAttributes()` stamps the same marker on the **group's dropdown root** rather than on its trigger.
The attribute bag renders the marker as `data-sy-table-bulk-only="true"`, but the template and the controller both match on the attribute's *presence*, so the rendered value is incidental.
The server renders `hidden` on every marked element while the table reports no selection, and the `sylament--tables--table` Stimulus controller flips `hidden` as the count changes, so the marker is the contract between the two halves, and the initial paint is correct without waiting for JavaScript, which is what `x-cloak` exists to paper over.

Two shapes are kept from Filament and one is not.
Kept: the parent spread comes **last**, so a caller's own `extraAttributes(['data-sy-table-bulk-only' => …])` wins; and the override hooks are the same two Filament uses, so the delete, detach and dissociate bulk actions that subclass this base inherit them unchanged.
Not kept: Filament's `getExtraDropdownAttributes()` spreads `parent::getExtraAttributes()`, the *trigger* bag, inside the *dropdown* accessor, which reads as a copy-paste slip and would silently drop a caller's `extraDropdownAttributes()`; the port spreads `parent::getExtraDropdownAttributes()`.

### The failure reports count; they do not carry keyed messages

Filament's report methods `reportBulkProcessingFailure()` and `reportCompleteBulkProcessingFailure()` each take `?string $key` and `string|Closure|null $message`, bucketing identical failures into `$bulkProcessingFailureMessages[$key] = ['message' => …, 'count' => …]` so the notification body can render *"3 of 10 could not be deleted"* per reason.
The port's take no arguments: `reportBulkProcessingFailure()` decrements the successful count, `reportBulkProcessingSuccessfulRecordsCount(int $count)` sets it, and `reportCompleteBulkProcessingFailure()` zeroes it.
The counting half is what the shipped failure notification reads (a title closure taking `successCount` and `totalCount`), and the message half needs the notification-body escaping contract decided first, so it is deferred rather than half-ported with an argument nothing consumes.
One arithmetic detail goes with it: Filament's `reportBulkProcessingSuccessfulRecordsCount()` also folds the difference into `bulkProcessingFailureWithoutMessageCount`; with no buckets to fold into, the port's is the bare assignment.
Landing the keyed buckets together with the [failure body](can-notify.md#the-failure-notification-carries-four-injections-of-filaments-nine) reverses this.

### Chunking is not ported

`chunkSelectedRecords()` and `getSelectedRecordsChunkSize()` are dropped, so `CanFetchSelectedRecords` ships as the fetch flag alone.
That is the **fetching** arm's chunking: a by-key arm, the delete's and the dissociate's, chunks its own key list by [the chunk rule](../schemas/record-key-contract.md#the-chunk-rule) rather than by a user-set size, because DQL parse time is superlinear in bound-key count and a platform caps the parameters one statement binds.
The two are unrelated mechanisms wearing one word, and only the fetching one is unported, for two reasons.
**No shipped Filament action calls `chunkSelectedRecords()`**: a grep over `vendor/filament/actions/src` returns only its definition, so the port would be API surface with no consumer on either side.
And the shape it produces is Laravel's: a `LazyCollection` whose count cannot be taken without consuming it, which is why Filament's `getSelectedRecords()` pays a second `COUNT` query for the lazy branch.
Doctrine's equivalent (`toIterable()`, or paginated batches) has different detachment and identity-map semantics, so the port would be inventing rather than translating.
A bulk action over a very large selection would reverse this; the entry point is a `chunk` argument threaded through the table host's `getSelectedTableRecords()`.
