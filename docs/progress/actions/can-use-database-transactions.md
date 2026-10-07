---
description: The trait that wraps an action's run in a Doctrine transaction and resolves the entity manager a record's class belongs to.
ports:
  - vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php
owns:
  - packages/actions/src/Concerns/CanUseDatabaseTransactions.php
---
# CanUseDatabaseTransactions (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`databaseTransaction()` / `hasDatabaseTransactions()`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php) | [`Concerns/CanUseDatabaseTransactions.php`](../../../vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php) | done | — | — |
| [`beginDatabaseTransaction()` / `commitDatabaseTransaction()` / `rollBackDatabaseTransaction()`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php) | [`Concerns/CanUseDatabaseTransactions.php`](../../../vendor/filament/actions/src/Concerns/CanUseDatabaseTransactions.php) | done | — | [diverges](#the-transaction-trio-resolves-a-doctrine-connection-instead-of-a-facade) |
| [`getEntityManagerForRecord()`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php) | — | done | — | [diverges](#the-record-manager-resolver-has-no-filament-counterpart-and-the-table-wins) |

## Divergences

### The transaction trio resolves a Doctrine connection instead of a facade

Filament's `beginDatabaseTransaction()`, `commitDatabaseTransaction()` and `rollBackDatabaseTransaction()` call Laravel's `DB` facade, each returning early unless `databaseTransaction()` was called.
They are imperative rather than a `DB::transaction($callback)` wrapper because the pipeline decides commit or rollback per exception type.
The port keeps that shape and the `false` default exactly, and answers the "which connection?" question Laravel's facade answers globally: [`getDatabaseTransactionConnection()`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php) prefers the table's own query manager (`getTable()?->getQueryEntityManager()?->getConnection()`, reached through the same [guarded soft reference](belongs-to-table.md) the rest of the table surface uses, so nothing is required of a tables-less consumer) and falls back to the default manager of a `#[Required] ?ManagerRegistry`.
Every action inherits that registry setter from here, [`DeleteAction`](delete-action.md) included, so there is one setter and one service.
Both fallback failures throw rather than silently skipping the transaction: no registry (an action built outside the container) names the container in its message, and a non-ORM manager names the type it got.
The ORM's own `wrapInTransaction()` is deliberately not used, because it `close()`s the EntityManager on failure and cannot express the per-exception commit or rollback split the pipeline needs; DBAL 4 nests unconditionally through savepoints, so the begin and commit pair composes with an outer test transaction.

### The record manager resolver has no Filament counterpart, and the table wins

[`getEntityManagerForRecord(object|class-string $recordOrClass): EntityManagerInterface`](../../../packages/actions/src/Concerns/CanUseDatabaseTransactions.php) serves the write actions rather than a transaction: it answers *which manager owns this record's class*, on the same precedence the connection resolver uses, the table's `getQueryEntityManager()` when the action has a query-lane table, else the registry's `getManagerForClass()`.
It lives here because this trait already carries the `#[Required] ?ManagerRegistry` every action inherits, and Filament has nothing to port: Eloquent resolves a model's connection from the model.
It is `protected`, and it throws rather than degrading on each of its three misses: no registry names the container-built instance, no manager for the class names the class, and a non-ORM `ObjectManager` names the type it got.

The precedence is the table's manager over the *record's class*, not merely over the registry: an action naming a `recordClass()` from a different manager than the table it sits on persists into the table's, and the mismatch surfaces as Doctrine's own `MappingException` at persist, loud but with nothing in the message about the table.
A record-class-first precedence would be the other half-wrong (a query-lane table exists precisely to say which manager reads), so the table wins on purpose; name the class on an action outside that table.
