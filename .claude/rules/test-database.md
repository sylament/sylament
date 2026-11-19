---
paths:
  - "/tests/Fixtures/src/Entity/**"
  - "/tests/Infrastructure/Doctrine/**"
  - "/tests/Fixtures/src/*/Doctrine/**"
  - "/tests/Fixtures/config/packages/*.php"
description: How the root suite prepares its database, where its entities and Doctrine fixtures live, and how it counts statements and cleans up after a server test.
---
# Test database rules

- **Leave preparation to the first connection:** `TestDatabaseMiddleware` has `TestDatabase` prepare once per test (SQLite copies a template over the run's file, a server has `ServerDatabase` wipe the run's `<base>_<run>`), so a test that never connects pays nothing; never prepare, wipe or build a schema from a test.
- **Put every entity in the shared `src/Entity/`,** which `config/packages/doctrine.php` maps as one folder: reuse one before adding one, and never change one another test reads, since every area's tests share it; add an entity for a shape none has.
- **Register an area's DBAL type or filter in its `config/packages/<area>.php`** (`dbal.types`, its manager's `filters`) and put the class, with an entity listener and any value they convert, in the area's `Doctrine/`: the area owns both, while `doctrine.php` is shared and Doctrine, not the container, builds each.
- **A fixture column whose custom type extends `StringType`, and a string identifier a join column references, declares `length:`,** because MySQL refuses a `VARCHAR` without one and a join column takes the referenced column's length.
- **Count statements through DoctrineBundle's profiling** (`profiling: true`, read by `statementsRun()`), never a recorder of the suite's own, and keep `TestDatabaseMiddleware` and `EnableForeignKeys` (innermost, SQLite only) above its priority 10: DoctrineBundle wraps the driver from the highest priority out, so this keeps every `PRAGMA` out of the count, while the preparation runs on connections of its own.
- **A test that adds a trigger or helper table on a server** registers its removal with `ServerDatabase::cleanUpBeforeNextWipe()`, as `FailingWrite` does; a wipe empties tables and drops nothing.

Background: `docs/internals/test-infrastructure.md`.
