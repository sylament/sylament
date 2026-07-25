# Cancel (support)

`Sylament\Support\Exception\Cancel` — the control-flow exception an action
closure throws to abort the lifecycle and unmount the action (closing the
modal) without a failure notification. It ports Filament's
`Filament\Support\Exceptions\Cancel`, and `Sylament\Actions\Action` throws it
from `cancel()` for `HasActions` to catch and fall through to unmount.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php)
  — the exception and its DB-transaction rollback control

## Surface

Every point of this exception. _Detail_ carries a **diverges** link to the
permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Marker exception](../../../packages/support/src/Exception/Cancel.php) | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | done | — | — |
| Rollback-transaction control | [`Exceptions/Cancel.php`](../../../vendor/filament/support/src/Exceptions/Cancel.php) | skipped | — | [diverges](#the-db-transaction-rollback-control-is-dropped) |

## Details

Every row above is settled — **done** or **skipped** — so none has a write-up
here now.

## Divergences

### The DB-transaction rollback control is dropped

Filament's `Cancel` carries a `shouldRollbackDatabaseTransaction` flag plus a
`rollBackDatabaseTransaction(bool $condition = true)` fluent setter and a
`shouldRollbackDatabaseTransaction()` reader; a thrower opts the surrounding
transaction into a rollback. The port drops all three and stays a bare marker
exception. Every consumer of that flag lives in `filament/filament` panel pages
([`CreateRecord`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php),
`EditRecord`, `EditProfile`), which wrap saves in a database transaction and
inspect the caught exception to decide whether to roll back — none of that is
ported yet, and the Sylament `HasActions` catch simply falls through to unmount.
The control re-lands with the Doctrine transaction wrapping when a panel page
needs it. Its sibling
[`Halt`](../../../packages/support/src/Exception/Halt.php) drops the same trio
for the same reason and records it in its class docblock.

_See also: the global [rename table](../renames.md) — the `Exceptions\` →
`Exception\` namespace singularization is a systematic translation recorded
there, not a per-component divergence._
