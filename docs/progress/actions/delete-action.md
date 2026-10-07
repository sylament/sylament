---
description: The prebuilt record delete and its bulk sibling DeleteBulkAction, both removing records through Doctrine behind a confirmation modal.
ports:
  - vendor/filament/actions/src/DeleteAction.php
  - vendor/filament/actions/src/DeleteBulkAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
owns:
  - packages/actions/src/DeleteAction.php
  - packages/actions/src/DeleteBulkAction.php
---
# DeleteAction (actions)

[`Sylament\Actions\DeleteAction`](../../../packages/actions/src/DeleteAction.php) is the prebuilt record delete: a danger-coloured trash trigger that opens a confirmation modal and, on confirm, removes the record through Doctrine and reports `success()`.
[`DeleteBulkAction`](../../../packages/actions/src/DeleteBulkAction.php) is the same delete over a table's selected records.
It extends [`BulkAction`](bulk-action.md), so it arrives with `bulk()` and `accessSelectedRecords()` already on, and both classes compose [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php), so a caller replaces either body with `using()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DeleteAction.php) (`'delete'`) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | — |
| [`setUp()` label, success-notification title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:trash-20-solid')`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair) |
| [The `action()` body — delete then `success()` / `failure()`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | [diverges](#the-delete-goes-through-a-doctrine-manager-registry-not-through-the-record) · [diverges](#the-manager-registry-is-an-optional-required-injection) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [`modalHeading()` — `"Delete {record title}"`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) (record title interpolated) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| `modalIcon()` / `modalSubmitActionLabel()` | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | skipped | — | [diverges](#the-confirmation-chrome-is-left-to-the-defaults) |
| `keyBindings(['mod+d'])` | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | blocked | `HasKeyBindings` | — |
| `hidden()` for a soft-deleted record | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | skipped | — | [diverges](#there-is-no-soft-delete-arm-to-hide-behind) |
| [Success redirect after the delete](../../../packages/panels/src/Resources/Pages/EditRecord.php) (on `EditRecord`) | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | done | — | [semantics](#a-delete-redirects-only-where-the-host-page-answers) |

## Surface — DeleteBulkAction

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DeleteBulkAction.php) (`'delete'`) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | — |
| [`setUp()` chrome](../../../packages/actions/src/DeleteBulkAction.php) — label, modal heading, submit label, success title, colour, icon, confirmation, deselect | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) · [can-notify.md](can-notify.md#the-failure-notification-carries-four-injections-of-filaments-nine) |
| [The `action()` body — remove each, one `flush()`](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [semantics](#both-arms-swallow-and-report-the-same-failure) · [diverges](#the-bulk-delete-is-atomic-where-filaments-is-per-record) |
| [`deleteSelectedRecordsByKey()`](../../../packages/actions/src/DeleteBulkAction.php) — the `fetchSelectedRecords(false)` arm and its override point | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) (`getSelectedRecordsQuery()->delete()`) | done | — | [diverges](#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys) · [chunk rule](../schemas/record-key-contract.md#the-chunk-rule) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| Reporting the swallowed exception | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) (`report($exception)`) | blocked | a logger on `Action` | [detail](#the-swallowed-exception-has-nowhere-to-go-yet) |
| `missingBulkAuthorizationFailureNotificationMessage()` / `missingBulkProcessingFailureNotificationMessage()` | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | blocked | the keyed failure buckets | [bulk-action.md](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages) |
| `modalIcon()` | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | skipped | — | [diverges](#the-confirmation-chrome-is-left-to-the-defaults) |
| `hidden()` for the trashed filter | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | skipped | — | [diverges](#there-is-no-soft-delete-arm-to-hide-behind) |

## Details

### The swallowed exception has nowhere to go yet

Filament calls Laravel's `report($exception)` in its bulk catch blocks, so the counting notification is the *user-facing* answer while the log still carries the cause.
`Action` has no logger, and an optional `#[Required] ?LoggerInterface` setter on the [`CanUseDatabaseTransactions` registry pattern](#the-manager-registry-is-an-optional-required-injection) needs `psr/log`, a non-`symfony/*` dependency that waits on sign-off.
So every bulk body's catch swallows silently, `DeleteBulkAction`'s, [`DetachBulkAction`](detach-action.md)'s and [`DissociateBulkAction`](dissociate-action.md)'s alike, and the danger toast is the only trace.

## Semantics

### The headings interpolate, and fall back to the bare verb

`DeleteAction`'s heading is `($title = $this->getRecordTitle()) === null ? 'Delete' : "Delete {$title}"`, and `DeleteBulkAction`'s is the same shape over `getTitleCasePluralRecordLabel()`: Filament's `__('…delete.single.modal.heading', ['label' => …])` minus the catalogue, the shape `EditAction` and `ReplicateAction` share.
Both getters answer `null` for an action with nothing to name (no record, no record class, no table), so a hostless `DeleteAction` reads `Delete` and a hostless `DeleteBulkAction` reads `Delete selected`, where Filament's catalogue would leave a trailing space.
Bind either to a table, or give it a record or a `recordClass()`, and the heading fills itself: _"Delete Airbag"_, _"Delete selected Songs"_.
Where the title should be quoted or phrased differently, a caller passes a closure: `->modalHeading(fn (Article $record): string => sprintf('Delete "%s"?', $record->getTitle()))`.

### A delete redirects only where the host page answers

`DeleteAction` sets no redirect of its own, so reading the class alone says it never redirects, yet it does on one host.
`dispatchSuccessRedirect()` takes the action's own `successRedirectUrl()` first and falls back to the host's [`getDefaultActionSuccessRedirectUrl()`](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred), and [`EditRecord`](../panels/edit-record.md) answers it for a `DeleteAction` with the resource's index URL, because the record the page is editing is gone once the action has run.
Every other host (a table, a list page, a plain live component) answers `null`, so the same action deletes and re-renders in place, which on a table is right.
Filament draws the line in the same place: its record-page concern answers the hook only for a delete or a force-delete.

The URL leaves the pipeline as a [returned `RedirectResponse`](has-actions.md#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag) from `callMountedAction()`, so the request ends before the page re-renders, which is why the port needs no counterpart to Filament's nulling of a deleted record in `afterActionCalled()` before dehydration.
A caller can have the redirect on any host by naming it per action with `->successRedirectUrl('/…')`.

### Both arms swallow and report the same failure

`DeleteBulkAction`'s by-key statement is wrapped in the same `try { … } catch (Throwable) { reportCompleteBulkProcessingFailure(); }` its hydrating arm carries, and Filament wraps its `getSelectedRecordsQuery()->delete()` identically.
It matters more than the symmetry suggests: a bulk write that is not caught leaves `callMountedAction()`'s own `catch (Throwable)` to roll the transaction back and rethrow, so a foreign key the delete violates would surface as a 500 with no toast at all, against the contract the bulk-notification family exists for.
One transaction means the whole batch failed either way (one chunk is one statement, several chunks are one `transactional()`), so the complete-failure report is the right shape for both.

The catch is **not** total.
Everything a developer can get wrong is resolved before the `try`: the table, its entity manager, its record class and the shape of the keys, so a by-key delete on a `records()`-lane table propagates its `LogicException` instead of answering with the danger toast, the placement [`DissociateBulkAction`](dissociate-action.md#dissociatebulkaction-refuses-before-either-arm-writes) follows too.
The keys are chunked inside the `try`, in the override point: the chunk rule's only refusal, an identifier `IdentifierFields` cannot read, has already run outside it while the selection plucked the keys, for the same manager and class, so a pre-`try` chunk could refuse nothing new.
What the catch still covers is the statements themselves, anything an override of `deleteSelectedRecordsByKey()` throws, and the one guard that can only fire once the statements have run: a DQL delete that answers with something other than a row count.
The hydrating arm keeps its non-object record guard inside its own catch, because that arm has no resolution step to hoist: a `records()` table whose rows are arrays reports a complete failure instead of fataling.
Both swallows lose the exception's detail, which [a logger](#the-swallowed-exception-has-nowhere-to-go-yet) would keep.

## Divergences

### The delete goes through a Doctrine manager registry, not through the record

Filament's body is `static fn (Model $record): ?bool => $record->delete()`: Eloquent's active record deletes itself.
Doctrine records are plain objects with no persistence behaviour, so the port resolves the record's manager instead:

```php
$manager = $registry->getManagerForClass($record::class)
    ?? throw new LogicException(sprintf('No Doctrine manager handles %s.', $record::class));

$manager->remove($record);
$manager->flush();

return true;
```

The registry rather than a single `EntityManagerInterface` is deliberate: a multi-manager application binds entities to different managers, and `getManagerForClass()` is the only lookup that respects that.
The `true` return replaces Eloquent's `?bool`, so the `success()` / `failure()` split still has something to key off; `remove()` and `flush()` throw rather than report, so the failure arm is reachable only through a `using()` closure that returns falsy.
And the record type widens from `Model` to `object`, the same widening every record-typed signature in the port carries.

### The manager registry is an optional `#[Required]` injection

`setManagerRegistry(?ManagerRegistry $managerRegistry = null)` lives on [`CanUseDatabaseTransactions`](can-use-database-transactions.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade), which every `Action` composes, and both delete classes inherit it rather than declaring a second copy: one setter, one service, whether the registry is wanted for a transaction or for a delete body's `getManagerForClass()`.
It is nullable **with a default**, which is what lets a Doctrine-less container compile the service definition at all: Symfony's autowiring fills an optional argument with its default when no service matches the type, and throws only when the parameter is required.
A Sylament install with no ORM therefore builds the `DeleteAction` definition, and only a *use* of the default process fails, loudly, naming the cause:

> DeleteAction needs the container-built instance — register it as a service so the manager registry is injected.

That message also covers the other way to reach a null registry: an action built outside the container.
`Action::make()` routes through [`ComponentResolver`](../support/component.md), so an instance the container built carries the setter injection; a `new DeleteAction()` (or a `make()` in a unit test with no kernel) does not.
A non-nullable parameter would move the failure from the one call site that needs Doctrine to every application that boots the bundle without it.

### The confirmation chrome is left to the defaults

Filament sets `modalIcon(…OutlinedTrash)` and `modalSubmitActionLabel(__('…delete.single.modal.actions.delete.label'))` on top of `requiresConfirmation()`.
`DeleteAction` sets neither, because [`CanOpenModal`'s confirmation fallbacks](can-open-modal.md#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution) answer both: `getModalIcon()` returns `heroicons:exclamation-triangle` whenever confirmation is required, and `getModalSubmitActionLabel()` returns `Confirm`.
The icon is an alias lookup in Filament (`ActionsIconAlias::DELETE_ACTION_MODAL`), and `ActionsIconAlias` is [unported](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection).

`DeleteBulkAction` splits the pair: it skips the modal icon for the same alias reason, but it *does* set `modalSubmitActionLabel('Delete')`, because the fallback `Confirm` reads wrong under a heading that already says *Delete selected*, and Filament gives the bulk modal its own key there too.

The visible cost: the port's delete confirmation is headed by the generic warning triangle every confirming action shows, where Filament's is headed by an outlined trash that says *what* is being confirmed.
That is a missing feature rather than an icon-variant slip: the two cases are `Heroicon::OutlinedExclamationTriangle` and `Heroicon::OutlinedTrash`, both outline, so [the mini/outline rule](../support/icon-manager.md#an-unprefixed-heroicon-case-is-the-mini-20px-set-and-our-literals-must-spell-it) has nothing to say about it.
Porting `ActionsIconAlias` reverses the icon half.

### The strings are literals, not translation keys

`label('Delete')`, `successNotificationTitle('Deleted')` and the `"Delete …"` / `"Delete selected …"` heading phrasings are English literals where Filament reads `__('filament-actions::delete.single…')`, the same call as [create-action.md](create-action.md#the-strings-are-literals-not-translation-keys).
The headings interpolate the record title and the plural label the way Filament's catalogue entries do ([semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb)); only the surrounding phrase is fixed English.
`DeleteBulkAction`'s failure title turns Filament's two `trans_choice` branches into two `sprintf`s over the same `successCount` and `totalCount` injections: `Deleted %d of %d` and `Failed to delete %d records`.

### There is no soft-delete arm to hide behind

Filament hides the action for an already-trashed record: `hidden(static fn (Model $record): bool => method_exists($record, 'trashed') && $record->trashed())`, and hides the bulk action behind its `TrashedFilter` state.
Doctrine ships no soft-delete concept, and the port has no equivalent (Filament's own `ForceDeleteAction` and `RestoreAction` are unported for the same reason), so the guard has nothing to test and is dropped rather than translated into a `method_exists()` probe for a convention nobody has defined.

### The bulk delete is atomic where Filament's is per-record

Filament deletes inside `$records->each(...)`, catching per record so one failed row reports a single failure and the rest still go: `$record->delete() || $action->reportBulkProcessingFailure()`.
That shape presumes Eloquent's per-statement commit and a global error reporter.
Doctrine's unit of work is not that: the port removes every record and issues **one** `flush()`, so any `Throwable` means the whole batch failed, and the catch calls `reportCompleteBulkProcessingFailure()`, the danger, persistent *"Failed to delete N records"* toast, and **swallows**, which is the ported bulk contract rather than a 500.

A rethrow would skip the notification machinery the bulk family exists for and turn one locked row into an unexplained error response.
A per-record Doctrine port needs a flush per record, a `resetManager()` after each failure and a re-fetch of every entity the reset detached, complexity no shipped bulk action needs.
The manager is resolved from the **first** record's class through the registry and reused for the rest, rather than looked up per record: a table's rows are one entity class by construction, and one manager is what makes the single `flush()` atomic.

### The by-key arm is a DQL delete over the plucked keys

Under `fetchSelectedRecords(false)` Filament calls `$action->getSelectedRecordsQuery()->delete()`: Eloquent's builder deletes straight from the constrained query.
Doctrine's DQL `DELETE` accepts no joins, and the table's selection query carries whatever the filters and the eager loading put on it, so the port splits it in two.
The host has **already** plucked the selection to keys with the filters and the [selectability constraint](../tables/host-has-bulk-actions.md#the-selection-is-server-canonical-and-re-derived-on-every-read) applied, and `deleteSelectedRecordsByKey()` issues `DELETE <record class> WHERE <predicate>` over exactly those, the predicate built by [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under `sylament_selected_record_keys`.
The affected-row count feeds `reportBulkProcessingSuccessfulRecordsCount()`, so the status is the comparison against the total the reader minted.

`deleteSelectedRecordsByKey(array $keys): int` is the arm's **override point**, protected and Sylament's own: Filament inlines `getSelectedRecordsQuery()->delete()` and has no member to override.
It is handed the plucked keys, canonical and unique, and never an empty list, because an empty selection reports a count of `0` before it is reached.
It answers the affected-row count, which the action reports as its successful count, so an override that answers less than it was handed makes the action report *Deleted N of M*.
It runs inside the action's `catch (Throwable)`, so an override that throws, such as an audit guard refusing a protected record, is the danger toast with nothing deleted, like any failed by-key delete.
The chunking, the transaction and the per-chunk statement with its cache eviction sit inside it, the per-chunk statement in a private method: an override that calls `parent::` keeps all three, and one that does not takes them on itself.
The method resolves the table's manager and record class a second time, inside itself, through the same memoised reads the pre-`try` resolution makes, so the bare key-list signature costs no statement.

**One statement is the normal case, not the guarantee.**
The key list is split by [`IdentifierConstraint::chunk()`](../schemas/record-key-contract.md#the-chunk-rule) first, and any selection that fits a chunk, which is every selection a page can realistically produce, is exactly one `DELETE`.
When it does not fit, the chunks run inside one DBAL `transactional()`, so that "the whole batch failed" stays true of the toast the arm reports.
A single chunk opens none: one statement is atomic on its own, and `callMountedAction()`'s transaction is **opt-in** (`hasDatabaseTransactions` defaults to `false` and no shipped action sets it), so nothing wraps it either.

Under `fetchSelectedRecords(false)` a table's `$records` are record keys in the [key grammar](../schemas/record-key-contract.md#the-grammar), so a `using()` closure replacing this arm receives strings rather than anything it can `remove()`: encoded keys for a composite table, and the id as a string for a single-identifier one.

Two semantics are shared with Filament's own mass delete: a DQL delete **bypasses ORM cascades and lifecycle callbacks** (Filament's skips model events the same way), and it leaves the identity map holding rows the statement deleted, which is harmless here because the response re-renders from a fresh query.
It does not leave the second-level cache holding them: the statement carries Doctrine's `HINT_CACHE_EVICT`, which evicts the record class's entity region, so `find()` does not serve a deleted record from the cache.
That reach is the record class's: a child row the database itself removes through `ON DELETE CASCADE` sits in its own region and keeps its entry, the reach Doctrine's hint has too.
The entity region alone suffices: a deleted record is never cached again, so a cached collection that lists it misses and reloads.
The hint evicts before its statement runs, inside the chunks' transaction when there are several, so a concurrent request reading a selected record between the eviction and the commit can put it back into a `NONSTRICT_READ_WRITE` region, which serves it until the entry expires: the window Doctrine's own hint has, which only a unit-of-work write closes, its cached persisters evicting again after the transaction.
The arm throws rather than guessing when the table is on the records lane or names no entity manager: deleting by key is a query-lane capability, and a `records()` table has no statement to issue.

**On a database that enforces foreign keys, a row something still references stops the statement.**
So the by-key arm takes along exactly what the database's own `ON DELETE CASCADE` removes, such as a many-to-many join row or a child whose join column declares it.
Anything else that references a selected row makes the `DELETE` fail whole: the single statement, or the chunks' transaction, leaves every row in place, and the danger toast reports the whole batch.
The hydrating arm is unaffected: `remove()` deletes a record's many-to-many join rows itself.
The by-key arm does not clear join rows first, because it keeps Filament's mass-delete semantics, whose `delete()` leaves pivot rows to the database too.
A join table whose columns pointing at the record declare `ON DELETE CASCADE` is cleared by the database; any other is a reason to leave the fetching arm on.
