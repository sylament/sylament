# DeleteAction (actions)

[`Sylament\Actions\DeleteAction`](../../../packages/actions/src/DeleteAction.php)
— the prebuilt record delete: a danger-coloured trash trigger that opens a
confirmation modal and, on confirm, removes the record through Doctrine and
reports `success()`. Unlike [`CreateAction`](create-action.md) and
[`EditAction`](edit-action.md) it is **complete**: deleting needs no form, so
there is no modal arm waiting on a schema resolver.

Its bulk sibling
[`DeleteBulkAction`](../../../packages/actions/src/DeleteBulkAction.php) is
audited [at the bottom of this file](#deletebulkaction), the way
[`BulkActionGroup`](action-group.md) rides along with the group it presets: it is
the same delete over a table's selection instead of over one row, so the two are
easier to read — and to keep honest — side by side than a file apart.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php)
  — the class this ports, whole
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait it composes, ported whole (row in
  [README](README.md#features))

Covered by
[`DeleteActionTest`](../../../packages/actions/tests/Unit/DeleteActionTest.php)
for the chrome and the `using()` / status paths, and end to end by
[`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php)
in the **tables** suite — mount a row action, confirm it, and the record is gone
from the database and from the re-render. It sits there for the reason
[`RecordActionsResolutionTest`](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference)
does: only a table host can bind a row's record to the action, and the actions
test kernel has neither a table nor a database.

## Surface

Every member Filament's class carries. _Blocked by_ names what has to land
first. _Detail_ carries a **detail** link to a [Details](#details) write-up, a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DeleteAction.php) (`'delete'`) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | — |
| [`setUp()` label, success-notification title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:trash-20-solid')`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#one-plain-icon-replaces-filaments-table-and-grouped-pair) |
| [The `action()` body — delete then `success()` / `failure()`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | [diverges](#the-delete-goes-through-a-doctrine-manager-registry-not-through-the-record) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [`modalHeading('Delete')`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) (record title interpolated) | in progress | record-title host hook | [detail](#the-modal-heading-is-a-bare-verb-until-a-record-title-exists) |
| `modalIcon()` / `modalSubmitActionLabel()` | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | skipped | — | [diverges](#the-confirmation-chrome-is-left-to-the-defaults) |
| `keyBindings(['mod+d'])` | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | todo | `HasKeyBindings` | — |
| `hidden()` for a soft-deleted record | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | skipped | — | [diverges](#there-is-no-soft-delete-arm-to-hide-behind) |
| Success redirect after the delete | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | in progress | the `EditRecord` page override (M4 · 2) | [detail](#a-delete-on-a-record-page-does-not-redirect-yet) |

## Details

Specifics for rows that are not finished — what is already in, what is left,
and the next step.

### The modal heading is a bare verb until a record title exists

Filament's heading reads _"Delete Airbag"_ — `__('…delete.single.modal.heading',
['label' => $this->getRecordTitle()])`. `getRecordTitle()` is part of the
[unported record-title and model-metadata surface](action.md#the-record-title-and-model-metadata-surface-is-not-ported),
and the host hook that would supply a default
(`getDefaultActionRecordTitle()`) is part of the
[deferred hook cluster](has-actions.md#the-getdefaultaction-host-override-hooks-are-not-ported).
So the port sets the bare `'Delete'`. A caller who wants the record in the
heading passes a closure, exactly as the demo's hand-built delete action does:
`->modalHeading(fn (Article $record): string => sprintf('Delete "%s"?', $record->getTitle()))`.

**Next step:** interpolate the record title once `getRecordTitle()` and its host
hook land.

### A delete on a record page does not redirect yet

In Filament, deleting the record you are editing redirects to the resource
index — `dispatchSuccessRedirect()` reads
`getDefaultActionSuccessRedirectUrl()` off the host page. The mechanism now
ships on this side: [`CanRedirect`](action.md) is composed onto `Action`, the
[host hook](has-actions.md#the-getdefaultaction-host-override-hooks-are-not-ported)
is on the trait and the contract, and a resolved URL leaves the pipeline as a
[returned `RedirectResponse`](has-actions.md#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag).
What is still missing is the **page-side override**: no panels page answers the
hook, so a delete on an `EditRecord` page still re-renders in place and leaves a
page whose record no longer exists. On a table that is exactly right, which is
why nothing else needs it yet. A caller can already opt in per action with
`->successRedirectUrl('/…')`.

**Next step:** the `EditRecord` override — `getDefaultActionSuccessRedirectUrl()`
returning the resource's index URL for a `DeleteAction` — which is
[M4 · 2](../roadmap.md)'s row, not this concern's.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.
These outlive the detail sections above.

### The delete goes through a Doctrine manager registry, not through the record

Filament's body is `static fn (Model $record): ?bool => $record->delete()` —
Eloquent's active record deletes itself. Doctrine records are plain objects
with no persistence behaviour, so the port resolves the record's manager
instead:

```php
$manager = $registry->getManagerForClass($record::class)
    ?? throw new LogicException(sprintf('No Doctrine manager handles %s.', $record::class));

$manager->remove($record);
$manager->flush();

return true;
```

Three consequences worth stating. The registry rather than a single
`EntityManagerInterface` is deliberate: a multi-manager application binds
entities to different managers, and `getManagerForClass()` is the only lookup
that respects that. The `true` return replaces Eloquent's `?bool`, so the
`success()` / `failure()` split still has something to key off — `remove()` +
`flush()` throw rather than report, which means the failure arm is reachable
only through a `using()` closure that returns falsy (pinned by
`DeleteActionTest`). And the record type widens from `Model` to `object`, the
same widening every record-typed signature in the port carries.

### The manager registry is an optional `#[Required]` injection

`setManagerRegistry(?ManagerRegistry $managerRegistry = null)` lives on
[`CanUseDatabaseTransactions`](action.md#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade),
which every `Action` composes, and this class inherits it rather than declaring
a second copy — one setter, one service, whether the registry is wanted for a
transaction or for this body's `getManagerForClass()`. It is nullable
**with a default**, which is what lets a Doctrine-less container compile the
service definition at all: Symfony's autowiring fills an optional argument with
its default when no service matches the type, and throws only when the
parameter is required. A Sylament install with no ORM therefore builds the
`DeleteAction` definition fine, and only a *use* of the default process fails —
loudly, naming the cause:

> DeleteAction needs the container-built instance — register it as a service so
> the manager registry is injected.

That message also covers the other way to reach a null registry: an action built
outside the container. `Action::make()` routes through
[`ComponentResolver`](../support/component.md), so an instance the container
built carries the setter injection; a `new DeleteAction()` (or a `make()` in a
unit test with no kernel) does not. Do not "fix" the nullability — a
non-nullable parameter moves the failure from the one call site that needs
Doctrine to every application that boots the bundle without it.

### The confirmation chrome is left to the defaults

Filament sets `modalIcon(…OutlinedTrash)` and
`modalSubmitActionLabel(__('…delete.label'))` on top of `requiresConfirmation()`.
The port sets neither, because
[`CanOpenModal`'s confirmation fallbacks](action.md#getmodalicons-confirmation-fallback-bypasses-icon-alias-resolution)
already answer both: `getModalIcon()` returns `heroicons:exclamation-triangle`
whenever confirmation is required, and `getModalSubmitActionLabel()` returns
`Confirm`. Setting the Filament values would trade a working default for a
literal that has to become a translation key later; the icon in particular is an
alias lookup (`ActionsIconAlias::DELETE_ACTION_MODAL`) with nothing to resolve
against yet.

[`DeleteBulkAction`](#deletebulkaction) splits the pair: it skips the modal icon
for the same alias reason, but it *does* set `modalSubmitActionLabel('Delete')`,
because the fallback `Confirm` reads wrong under a heading that already says
*Delete selected* — Filament gives the bulk modal its own key there too.

The visible cost, stated so it is not rediscovered as a defect: our delete
confirmation is headed by the generic warning triangle every confirming action
shows, where Filament's is headed by an outlined trash that says *what* is being
confirmed. That is a missing feature rather than an icon-variant slip — the two
cases are `Heroicon::OutlinedExclamationTriangle` and `Heroicon::OutlinedTrash`,
both outline, so [the mini/outline rule](../support/icon-manager.md#an-unprefixed-heroicon-case-is-the-mini-20px-set-and-our-literals-must-spell-it)
has nothing to say about it. It closes with the alias registry, which is what
the row above is skipped on.

### One plain icon replaces Filament's table-and-grouped pair

`icon('heroicons:trash-20-solid')` where Filament sets `tableIcon()` + `groupedIcon()`,
for the reason
[edit-action.md](edit-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair)
records — there is no `HasTableIcon` in the port, and the grouped trigger falls
back to the plain icon.

### The strings are literals, not translation keys

`label('Delete')`, `modalHeading('Delete')` and
`successNotificationTitle('Deleted')` are English literals where Filament reads
`__('filament-actions::delete.single…')` — same call as
[create-action.md](create-action.md#the-strings-are-literals-not-translation-keys).

### There is no soft-delete arm to hide behind

Filament hides the action for an already-trashed record:
`hidden(static fn (Model $record): bool => method_exists($record, 'trashed') && $record->trashed())`.
Doctrine ships no soft-delete concept, and the port has no equivalent (Filament's
own `ForceDeleteAction` / `RestoreAction` are unported for the same reason), so
the guard has nothing to test and is dropped rather than translated into a
`method_exists()` probe for a convention nobody has defined.

### The success toast arrives only when the notifications package is installed

`successNotificationTitle('Deleted')` is set and
[`HasActions::callMountedAction()`](has-actions.md) calls
`sendSuccessNotification()` after a successful call, which builds and sends a
real `Sylament\Notifications\Notification` — but only behind
[`CanNotify`'s guarded soft integration](action.md#the-notification-arm-is-a-guarded-soft-integration-not-a-require):
`sylament/actions` cannot *require* `sylament/notifications` (that package
requires actions back), so it `suggest`s it and guards every use with
`class_exists()`. An install without the package deletes the record and shows no
toast, silently and by design; an install with it gets the success toast with no
configuration on this class. The whole shape — confirm, delete, toast — is
pinned by
[`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php).

What is *not* here is the failure toast's counterpart: `DeleteAction` sets no
`failureNotificationTitle()`, exactly as Filament sets none, so the
[failure arm](action.md#the-notification-arm-is-a-guarded-soft-integration-not-a-require)
stays silent unless a caller titles it.

## DeleteBulkAction

[`Sylament\Actions\DeleteBulkAction`](../../../packages/actions/src/DeleteBulkAction.php)
— the same delete over a table's **selected records**. It extends
[`BulkAction`](bulk-action.md) (so it arrives with `bulk()` and
`accessSelectedRecords()` already on) and composes the same
[`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php)
`DeleteAction` does, so a caller replaces the whole body with `using()`.

**Status:** in progress — the chrome, both delete arms and the counting failure
notification ship; the two `missingBulk*NotificationMessage` hooks wait on the
[keyed failure buckets](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages)
and the swallowed exception has nowhere to be logged — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php)
  — the class this ports, whole

Covered by
[`DeleteBulkActionTest`](../../../packages/actions/tests/Unit/DeleteBulkActionTest.php)
for the chrome, the inherited presets and the failure title's arithmetic, and end
to end by
[`DeleteBulkActionTest`](../../../packages/tables/tests/Integration/DeleteBulkActionTest.php)
in the **tables** suite — the selection arms (keys, tracking, an active search,
an unselectable record), both delete arms, the post-call deselect, the two
failure toasts and the transaction wrap, each of the last written twice so the
contrast with the unwrapped run is what proves it.

### Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DeleteBulkAction.php) (`'delete'`) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading, submit label, success title, `defaultColor('danger')`, the trash icon, `requiresConfirmation()`, `deselectRecordsAfterCompletion()`](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | Filament's two `trans_choice` branches become two `sprintf`s over the same `successCount` / `totalCount` injections — [bulk-action.md](bulk-action.md#the-failure-notification-carries-four-injections-of-filaments-nine) |
| [The `action()` body — remove each, one `flush()`](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [diverges](#the-bulk-delete-is-atomic-where-filaments-is-per-record) |
| [`deleteSelectedRecordsByKey()` — the `fetchSelectedRecords(false)` arm](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) (`getSelectedRecordsQuery()->delete()`) | done | — | [diverges](#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| Reporting the swallowed exception | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) (`report($exception)`) | todo | a logger on `Action` | [detail](#the-swallowed-exception-has-nowhere-to-go-yet) |
| `missingBulkAuthorizationFailureNotificationMessage()` / `missingBulkProcessingFailureNotificationMessage()` | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | blocked | the [keyed failure buckets](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages) | — |
| `modalIcon()` | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | skipped | — | [diverges](#the-confirmation-chrome-is-left-to-the-defaults) |
| `hidden()` for the trashed filter | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | skipped | — | [diverges](#there-is-no-soft-delete-arm-to-hide-behind) |

### The swallowed exception has nowhere to go yet

Filament calls Laravel's `report($exception)` in both of its catch blocks, so the
counting notification is the *user-facing* answer while the log still carries the
cause. `Action` has no logger — a `psr/log` optional setter is a non-`symfony/*`
dependency and needs sign-off — so the port's catch swallows silently and the
danger toast is the only trace. That is the one real cost of the
[atomic body](#the-bulk-delete-is-atomic-where-filaments-is-per-record) and it is
recorded rather than papered over. **Next step:** an optional
`#[Required] ?LoggerInterface` setter on `Action`, on the
[`CanUseDatabaseTransactions` registry pattern](#the-manager-registry-is-an-optional-required-injection),
once the dependency is approved.

### The bulk delete is atomic where Filament's is per-record

Filament deletes inside `$records->each(...)`, catching per record so one failed
row reports a single failure and the rest still go: `$record->delete() ||
$action->reportBulkProcessingFailure()`. That shape presumes Eloquent's
per-statement commit and a global error reporter. Doctrine's unit of work is not
that: the port removes every record and issues **one** `flush()`, so any
`Throwable` means the whole batch failed and the catch calls
`reportCompleteBulkProcessingFailure()` — the danger, persistent
*"Failed to delete N records"* toast — and **swallows**, which is the ported bulk
contract rather than a 500.

A rethrow was considered and rejected: it would skip the notification machinery
the bulk family exists for, and turn one locked row into an unexplained error
response. A per-record Doctrine port was considered too: it needs a flush per
record, a `resetManager()` after each failure and a re-fetch of every entity the
reset detached — complexity the first consumer does not earn. Revisit with the
attach/dissociate bulk actions, whose bodies are association writes with a
different failure shape.

Two smaller shapes go with it. The record loop rejects a non-object record with a
`LogicException` — a `records()` table whose rows are arrays has nothing to
`remove()` — and that throw is caught by the same swallow, so an array-lane table
reports a complete failure instead of fataling. And the manager is resolved from
the **first** record's class through the registry and reused for the rest, rather
than looked up per record: a table's rows are one entity class by construction,
and one manager is what makes the single `flush()` atomic in the first place.

### The by-key arm is a DQL delete over the plucked keys

Under `fetchSelectedRecords(false)` Filament calls
`$action->getSelectedRecordsQuery()->delete()` — Eloquent's builder deletes
straight from the constrained query. Doctrine's DQL `DELETE` accepts no joins,
and the table's selection query carries whatever the filters and the eager
loading put on it, so the port splits it in two: the host has **already** plucked
the selection to keys with the filters and the
[selectability constraint](../tables/has-table.md#the-selection-is-server-canonical-and-re-derived-on-every-read)
applied, and `deleteSelectedRecordsByKey()` issues one
`DELETE <record class> WHERE <identifier> IN (:keys)` over exactly those. The
affected-row count feeds `reportBulkProcessingSuccessfulRecordsCount()`, so the
status is the comparison against the total the reader minted.

Two semantics to know, both shared with Filament's own mass delete: a DQL delete
**bypasses ORM cascades and lifecycle callbacks** (Filament's skips model events
the same way), and it leaves the identity map holding rows that no longer exist —
which is harmless here because the response re-renders from a fresh query. The
arm throws rather than guessing when the table is on the records lane or names no
entity manager: deleting by key is a query-lane capability, and a `records()`
table has no statement to issue.
