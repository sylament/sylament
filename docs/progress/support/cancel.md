# Cancel (support)

`Sylament\Support\Exception\Cancel` — the control-flow exception an action
closure throws to abort the lifecycle and unmount the action (closing the
modal) without a failure notification. It ports Filament's
`Filament\Support\Exceptions\Cancel`, and `Sylament\Actions\Action` throws it
from `cancel()` for `HasActions` to catch and fall through to unmount.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php)
  — the exception and its DB-transaction rollback control

## Surface

Every point of this exception. _Blocked by_ names what has to land first.
_Detail_ links the write-up in [Details](#details), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Marker exception](../../../packages/support/src/Exception/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | done | — | — |
| Rollback-transaction control | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | blocked | panels DB-transaction pages | [detail](#details) |

## Details

### Rollback-transaction control

Filament's `Cancel` carries a `shouldRollbackDatabaseTransaction` flag plus a
`rollBackDatabaseTransaction(bool $condition = true)` fluent setter and a
`shouldRollbackDatabaseTransaction()` reader; a thrower opts the surrounding
transaction into a rollback. Sylament's `Cancel` is a bare marker exception, so
the flag has no reader yet. Every consumer of it lives in `filament/filament`
panel pages ([`CreateRecord`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php),
`EditRecord`, `EditProfile`), which wrap saves in a database transaction and
inspect the caught exception to decide whether to roll back — none of that is
ported yet, and the Sylament `HasActions` catch simply falls through to unmount.
So the control is **blocked**, not dropped: it re-lands — flag, setter, reader,
and the surrounding Doctrine transaction wrapping — in the same change as the
first panel page that needs it, where it finally has behavior to test. Its sibling
[`Halt`](halt.md) is blocked on the same layer for the same reason.

_See also: the global [rename table](../renames.md) — the `Exceptions\` →
`Exception\` namespace singularization is a systematic translation recorded
there, not a per-component divergence._
