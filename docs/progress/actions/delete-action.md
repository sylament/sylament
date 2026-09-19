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
first. _Detail_ carries a **semantics** link to [Semantics](#semantics), a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DeleteAction.php) (`'delete'`) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | — |
| [`setUp()` label, success-notification title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:trash-20-solid')`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) `tableIcon()` + `groupedIcon()` | done | — | [diverges](#one-plain-icon-replaces-filaments-table-and-grouped-pair) |
| [The `action()` body — delete then `success()` / `failure()`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | done | — | [diverges](#the-delete-goes-through-a-doctrine-manager-registry-not-through-the-record) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [`modalHeading()` — `"Delete {record title}"`](../../../packages/actions/src/DeleteAction.php) | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) (record title interpolated) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| `modalIcon()` / `modalSubmitActionLabel()` | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | skipped | — | [diverges](#the-confirmation-chrome-is-left-to-the-defaults) |
| `keyBindings(['mod+d'])` | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | todo | `HasKeyBindings` | — |
| `hidden()` for a soft-deleted record | [`DeleteAction.php`](../../../vendor/filament/actions/src/DeleteAction.php) | skipped | — | [diverges](#there-is-no-soft-delete-arm-to-hide-behind) |
| Success redirect after the delete | [`Concerns/CanRedirect.php`](../../../vendor/filament/actions/src/Concerns/CanRedirect.php) | done | — | [semantics](#a-delete-redirects-only-where-the-host-page-answers) |

## Semantics

Faithful ports whose obvious reading is wrong. Permanent, unlike the details
above.

### The headings interpolate, and fall back to the bare verb

`DeleteAction`'s heading is
`($title = $this->getRecordTitle()) === null ? 'Delete' : "Delete {$title}"` and
`DeleteBulkAction`'s is the same shape over
`getTitleCasePluralRecordLabel()` — the `ReplicateAction`/`EditAction` shape,
Filament's `__('…delete.single.modal.heading', ['label' => …])` minus the
catalogue.

The fallback is what makes the pair safe to default. Both getters answer `null`
for an action with nothing to name — no record, no record class, no table — so a
hostless `DeleteAction` still reads `Delete` and a hostless `DeleteBulkAction`
still reads `Delete selected`, exactly as before. Bind either to a table, or give
it a record or a `recordClass()`, and the heading fills itself:
_"Delete Airbag"_, _"Delete selected Songs"_. Where the title should be quoted or
phrased differently a caller still passes a closure —
`->modalHeading(fn (Article $record): string => sprintf('Delete "%s"?', $record->getTitle()))`
— which is what the demo does.

### A delete redirects only where the host page answers

`DeleteAction` sets no redirect of its own, and reading the class alone says it
never redirects. It does — on exactly one host. `dispatchSuccessRedirect()`
takes the action's own `successRedirectUrl()` first and falls back to the host's
[`getDefaultActionSuccessRedirectUrl()`](has-actions.md#the-getdefaultaction-host-override-hooks-are-ported-the-authorization-pair-deferred),
and [`EditRecord`](../panels/README.md) is the one page that answers it: for a
`DeleteAction` it returns the resource's index URL, because the record the page
is editing no longer exists once the action has run. Every other host — a table,
a list page, a plain live component — answers `null`, so the same action deletes
and re-renders in place, which on a table is exactly right. Filament draws the
line in the same place: its `InteractsWithRecord` page trait answers the hook
only for a delete or a force-delete, and only `EditRecord` composes the
equivalent here until `ViewRecord` lands.

Two consequences. The URL leaves the pipeline as a
[returned `RedirectResponse`](has-actions.md#a-redirect-leaves-the-pipeline-as-a-returned-response-not-through-a-framework-side-bag)
from `callMountedAction()`, so the request ends before the page re-renders —
which is why the port needs no counterpart to Filament's nulling of a deleted
record before dehydration. And a caller can have the redirect on any host by
naming it per action with `->successRedirectUrl('/…')`.

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

`label('Delete')`, `successNotificationTitle('Deleted')` and the `"Delete …"` /
`"Delete selected …"` heading phrasings are English literals where Filament reads
`__('filament-actions::delete.single…')` — same call as
[create-action.md](create-action.md#the-strings-are-literals-not-translation-keys).
The headings interpolate the record title and the plural label the way Filament's
catalogue entries do
([semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb)); only the
surrounding phrase is fixed English.

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
an unselectable record), both delete arms and
[both of their failure shapes](#both-arms-swallow-and-report-the-same-failure),
the post-call deselect, the two failure toasts and the transaction wrap, each of
the last written twice so the contrast with the unwrapped run is what proves it.
The committing case reads the BEGIN's **position** rather than its presence —
before the selection `SELECT`, which only the action's wrap can manage — because
the ORM's own flush transaction brackets the `DELETE` either way, so bracketing
alone would hold with the flag off. The failing by-key delete rides a second
fixture host, `BulkDeleteArtistsTable`, over a record class other rows point at,
with SQLite's foreign-key enforcement switched on so the DQL statement fails for
real rather than by injection.

### Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DeleteBulkAction.php) (`'delete'`) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading (`"Delete selected {title-case plural record label}"`), submit label, success title, `defaultColor('danger')`, the trash icon, `requiresConfirmation()`, `deselectRecordsAfterCompletion()`](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | Filament's two `trans_choice` branches become two `sprintf`s over the same `successCount` / `totalCount` injections — [bulk-action.md](bulk-action.md#the-failure-notification-carries-four-injections-of-filaments-nine) |
| [The `action()` body — remove each, one `flush()`](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) | done | — | [diverges](#the-bulk-delete-is-atomic-where-filaments-is-per-record) |
| [`deleteSelectedRecordsByKey()` — the `fetchSelectedRecords(false)` arm](../../../packages/actions/src/DeleteBulkAction.php) | [`DeleteBulkAction.php`](../../../vendor/filament/actions/src/DeleteBulkAction.php) (`getSelectedRecordsQuery()->delete()`) | done | — | [diverges](#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys) · the key list is chunked by [the chunk rule](../schemas/record-key.md#the-chunk-rule) |
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
applied, and `deleteSelectedRecordsByKey()` issues
`DELETE <record class> WHERE <predicate>` over exactly those, the predicate
built by
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
under `sylament_selected_record_keys` — `<identifier> IN (:keys)` for a
single-identifier class, byte for byte what this arm always emitted, and a
balanced OR of per-field ANDs for a composite one, which is the only shape DQL
offers since it has no row-value `IN`. The affected-row count feeds
`reportBulkProcessingSuccessfulRecordsCount()`, so the status is the comparison
against the total the reader minted. The method's own signature moved with the
split — `deleteSelectedRecordsByKey(EntityManagerInterface $entityManager,
string $recordClass, array $chunks)` where it took a bare key list and resolved
the table itself — which is a breaking change for a subclass that overrode it;
nothing in either repo does.

**One statement is the normal case, not the guarantee.** The key list is split
by [`IdentifierConstraint::chunk()`](../schemas/record-key.md#the-chunk-rule)
first — 2 000 keys per statement for one identifier field, 500 for two or more —
and any selection that fits a chunk, which is every selection a page can
realistically produce, is still exactly one `DELETE`. When it does not fit, the
chunks run inside one DBAL `transactional()`, so that "the whole batch failed"
stays true of the toast the arm reports. A single chunk opens none: one
statement is atomic on its own, and `callMountedAction()`'s transaction is
**opt-in** — `hasDatabaseTransactions` defaults to `false` and no shipped action
sets it — so there is nothing wrapping it either. `DeleteBulkActionTest` pins
that by finding no `BEGIN` on the unchunked case.

Under `fetchSelectedRecords(false)` a composite table's `$records` are
**encoded keys**, so a `using()` closure replacing this arm receives strings in
the [key grammar](../schemas/record-key.md#the-grammar) rather than anything it
can `remove()` — the same as a single-identifier table, where they have always
been the id as a string.

Two semantics to know, both shared with Filament's own mass delete: a DQL delete
**bypasses ORM cascades and lifecycle callbacks** (Filament's skips model events
the same way), and it leaves the identity map holding rows that no longer exist —
which is harmless here because the response re-renders from a fresh query. The
arm throws rather than guessing when the table is on the records lane or names no
entity manager: deleting by key is a query-lane capability, and a `records()`
table has no statement to issue.

### Both arms swallow and report the same failure

The by-key statement is wrapped in the same
`try { … } catch (Throwable) { reportCompleteBulkProcessingFailure(); }` the
hydrating arm carries, and Filament wraps its `getSelectedRecordsQuery()->delete()`
identically. It matters more here than the symmetry suggests: a bulk write that
is not caught leaves `callMountedAction()`'s own `catch (Throwable)` to roll the
transaction back and rethrow, so a foreign key the delete violates would surface
as a 500 with no toast at all — the exact opposite of the arm three lines below
it, and of the contract the whole bulk-notification family exists for. One
**transaction** means the whole batch failed either way — one chunk is one
statement, several chunks are one `transactional()` — so the complete-failure
report is the right shape for both.

The catch is **not** total, and M7 · 1 is what narrowed it. Everything a
developer can get wrong is resolved before the `try`: the table, its entity
manager, its record class and the chunked shape of the keys, so a by-key delete
on a `records()`-lane table propagates its `LogicException` instead of answering
with the danger toast — [`DissociateBulkAction`](dissociate-action.md) places its
four mapping refusals the same way, and `DeleteBulkActionTest` pins the
placement. What the catch still covers is the statements themselves, plus the one
guard that can only fire once they have run: a DQL delete that answers with
something other than a row count. The hydrating arm keeps its non-object record
guard inside its own catch, since that arm has no resolution step to hoist. Both
swallows still lose the exception's detail, which is the
[open todo](#the-swallowed-exception-has-nowhere-to-go-yet) a logger closes.
